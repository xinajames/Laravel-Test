<?php

namespace App\Services;

use App\Enums\QuestionnaireAnswerEnum;
use App\Enums\StoreRatingStepEnum;
use App\Helpers\DateHelper;
use App\Models\Questionnaire;
use App\Models\Store;
use App\Models\StoreRating;
use App\Models\StoreRatingQuestionnaire;
use App\Traits\HandleTransactions;
use App\Traits\ManageActivities;
use App\Traits\ManageFilesystems;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StoreRatingService
{
    use HandleTransactions;
    use ManageActivities;
    use ManageFilesystems;

    public function delete(StoreRating $storeRating): void
    {
        $this->transact(function () use ($storeRating) {
            // Delete Questionnaires first
            StoreRatingQuestionnaire::where('store_rating_id', $storeRating->id)->delete();

            $storeRating->delete();

            if (! $storeRating->is_draft) {
                $this->log($storeRating, 'storeRatings.delete');
            }
        });
    }

    public function generateStoreRatingQuestionnaire($storeId, $user): StoreRating
    {
        return $this->transact(function () use ($storeId, $user) {
            // Step 1: Create the Store Rating
            $storeRating = StoreRating::create([
                'store_id' => $storeId,
                'created_by_id' => $user?->id,
            ]);

            // Step 2: Fetch all questionnaires, sorted properly
            $questionnaires = Questionnaire::with(['category', 'subcategory'])
                ->orderBy('type')
                ->orderBy('category_id')
                ->orderBy('subcategory_id')
                ->orderBy('order')
                ->get();

            // Step 3: Attach all questions with answers (if provided) or null
            foreach ($questionnaires as $questionnaire) {
                StoreRatingQuestionnaire::create([
                    'store_rating_id' => $storeRating->id,
                    'questionnaire_id' => $questionnaire->id,
                    'question' => $questionnaire->question,
                    'order' => $questionnaire->order,
                ]);
            }

            return $storeRating;
        });
    }

    public function updateStoreRatingQuestionnaires(int $storeRatingId, array $answersData): bool
    {
        return $this->transact(function () use ($storeRatingId, $answersData) {
            foreach ($answersData as $data) {
                if (! isset($data['questionnaire_id'])) {
                    continue; // Skip invalid data
                }

                $answer = isset($data['answer']) && QuestionnaireAnswerEnum::tryFrom($data['answer'])
                    ? $data['answer']
                    : null;

                StoreRatingQuestionnaire::where('store_rating_id', $storeRatingId)
                    ->where('questionnaire_id', $data['questionnaire_id'])
                    ->update([
                        'answer' => $answer,
                    ]);
            }

            return true;
        });
    }

    public function update(array $storeRatingData, StoreRating $storeRating, $user = null)
    {
        $user = $user ?? auth()->user();
        $photoService = new PhotoService;

        return $this->transact(function () use ($storeRatingData, $storeRating, $user, $photoService) {
            if (isset($storeRatingData['step']) && $storeRatingData['step'] === StoreRatingStepEnum::Finished()->value) {
                $storeRatingData['rated_at'] = Carbon::now();
            }

            if (! empty($storeRatingData['photos'])) {
                foreach ($storeRatingData['photos'] as $photoData) {
                    if (! empty($photoData['file']) && is_file($photoData['file'])) {
                        $basePath = $this->generateUploadBasePath();
                        $originalFileName = $photoData['file']->getClientOriginalName();
                        $baseFileName = pathinfo($originalFileName, PATHINFO_FILENAME);
                        $fileName = $baseFileName . '-' . uniqid();

                        // Folder path: base/store/{store_id}/ratings/{store_rating_id}
                        $newFilePath = "{$basePath}/store/{$storeRating->store_id}/ratings/{$storeRating->id}/{$originalFileName}";

                        // If updating an existing photo
                        if (! empty($photoData['id'])) {
                            $photo = $storeRating->photos()->find($photoData['id']);
                            if ($photo && ! empty($photo->file_path)) {
                                $this->deleteFile($photo->file_path);
                            }
                            $this->upload($photoData['file'], $newFilePath);
                            $photo->update([
                                'file_path' => $newFilePath,
                                'description' => $photoData['description'] ?? '',
                            ]);
                        } else {
                            // Adding a new photo
                            $data = [
                                'file_name' => $fileName,
                                'description' => $photoData['description'] ?? '',
                                'store_id' => $storeRating->store_id,
                            ];

                            $photoService->createPhoto($photoData['file'], 'store-rating', $storeRating->id, $data);
                        }
                    } elseif (isset($photoData['description']) && ! empty($photoData['id'])) {
                        // If only updating the caption
                        $photo = $storeRating->photos()->find($photoData['id']);
                        if ($photo) {
                            $photo->update([
                                'description' => $photoData['description'],
                            ]);
                        }
                    }
                }
            }

            $storeRating->update($storeRatingData);

            // If the store rating is finalized, recompute the overall rating and log the activity
            if (array_key_exists('is_draft', $storeRatingData) && $storeRatingData['is_draft'] === false) {
                $this->recomputeStoreRating($storeRating->id);

                $this->log($storeRating, 'storeRatings.store', $user);
            }

            return $storeRating;
        });
    }

    public function recomputeStoreRating(int $storeRatingId): bool
    {
        // Since this already uses DB::transaction, we need to modify it to use our transact method
        return $this->transact(function () use ($storeRatingId) {
            // Fetch all questionnaire answers for this Store Rating
            $storeRatingQuestionnaires = StoreRatingQuestionnaire::with([
                'questionnaire.category',
                'questionnaire.subcategory',
            ])
                ->where('store_rating_id', $storeRatingId)
                ->get();

            $scoresPerType = [];
            $totalScore = 0;
            $totalTypes = 0;

            // Group answers by questionnaire type
            foreach ($storeRatingQuestionnaires->groupBy('questionnaire.type') as $type => $questions) {
                $yesCount = 0;
                $validAnswersCount = 0;

                foreach ($questions as $srq) {
                    $answer = $srq->answer;

                    // Skip N/A answers
                    if ($answer === QuestionnaireAnswerEnum::NotApplicable()->value) {
                        continue;
                    }

                    // Count Yes answers
                    if ($answer === QuestionnaireAnswerEnum::Yes()->value) {
                        $yesCount++;
                    }

                    // Count all valid (Yes + No)
                    $validAnswersCount++;
                }

                if ($validAnswersCount > 0) {
                    // Compute % Yes and convert to 5.0 scale
                    $typeScore = ($yesCount / $validAnswersCount) * 5.0;
                    $roundedScore = round($typeScore, 2);

                    $scoresPerType[$type] = $roundedScore;
                    $totalScore += $roundedScore;
                    $totalTypes++;
                }
            }

            // Final overall score across all types
            $overallRating = ($totalTypes > 0) ? ($totalScore / $totalTypes) : 0;

            // Update the Store Rating record
            StoreRating::where('id', $storeRatingId)->update([
                'overall_rating' => round($overallRating, 2),
                'ratings_per_type' => $scoresPerType,
            ]);

            return true;
        });
    }

    public function getStoreRatingQuestionnaires(int $storeRatingId): array
    {
        // Fetch store rating questionnaires with correct sorting
        $storeRatingQuestionnaires = StoreRatingQuestionnaire::query()
            ->join('questionnaires', 'store_rating_questionnaires.questionnaire_id', '=', 'questionnaires.id')
            ->leftJoin('categories as cat', 'questionnaires.category_id', '=', 'cat.id')
            ->leftJoin('categories as subcat', 'questionnaires.subcategory_id', '=', 'subcat.id')
            ->where('store_rating_questionnaires.store_rating_id', $storeRatingId)
            ->orderBy('questionnaires.type')
            ->orderBy('cat.name')
            ->orderBy('subcat.name')
            ->orderBy('store_rating_questionnaires.order')
            ->select(
                'store_rating_questionnaires.*',
                'questionnaires.type',
                'cat.name as category_name',
                'subcat.name as subcategory_name'
            )
            ->with('questionnaire')
            ->get();

        $structuredData = [];
        $remarkService = new RemarkService;

        foreach ($storeRatingQuestionnaires as $srq) {
            $questionnaire = $srq->questionnaire;
            $type = $questionnaire->type;
            $category = $srq->category_name;
            $subcategory = $srq->subcategory_name;

            $questionData = [
                'id' => $srq->id,
                'questionnaire_id' => $srq->questionnaire_id,
                'order' => $srq->order,
                'question' => $srq->question,
                'answer' => $srq->answer,
                'store_rating_id' => $storeRatingId,
                'remarks' => $remarkService->getRemarks($srq),
            ];

            // Structure grouping: type -> category -> subcategory -> questions
            if (! $category) {
                $structuredData[$type][] = $questionData;
            } elseif (! $subcategory) {
                $structuredData[$type][$category][] = $questionData;
            } else {
                if ($subcategory === 'Assessment Areas') {
                    // Ensure 'Assessment Areas' is always first.
                    if (! isset($structuredData[$type][$category]['Assessment Areas'])) {
                        $structuredData[$type][$category] = ['Assessment Areas' => []] + ($structuredData[$type][$category] ?? []);
                    }
                    $structuredData[$type][$category]['Assessment Areas'][] = $questionData;
                } else {
                    $structuredData[$type][$category][$subcategory][] = $questionData;
                }
            }
        }

        return $structuredData;
    }

    public function getStoreRatings(Store $store, int $offset = 0, int $limit = 10)
    {
        return StoreRating::with(['reviewedBy' => function ($query) {
            $query->select('id', 'name');
        }])
            ->where('store_id', $store->id)
            ->where('is_draft', false)
            ->skip($offset)
            ->take($limit)
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($storeRating) {
                return [
                    'id' => $storeRating->id,
                    'overall_rating' => $storeRating->overall_rating,
                    'ratings_per_type' => json_decode($storeRating->ratings_per_type),
                    'reviewed_by' => $storeRating->reviewedBy?->name,
                    'rated_at' => $storeRating->rated_at ? DateHelper::changeDateFormat($storeRating->rated_at) : null,
                    'updated_at' => DateHelper::changeDateFormat($storeRating->updated_at),
                ];
            });
    }

    public function getInformation(StoreRating $storeRating)
    {
        $storeRating->load([
            'store' => function ($query) {
                $query->select('id', 'jbs_name', 'store_code', 'store_group', 'region', 'area', 'district');
            },
            'reviewedBy' => function ($query) {
                $query->select('id', 'name');
            },
        ]);

        $storePhoto = $storeRating->store->photos->first();
        $storeRating->thumbnail = $storePhoto ? $this->retrieveFile($storePhoto->file_path, $storePhoto->disk) : null;
        $storeRating->ratings_per_type = json_decode($storeRating->ratings_per_type);
        $storeRating->rated_at = $storeRating->rated_at ? DateHelper::changeDateFormat($storeRating->rated_at) : null;
        $storeRating->photos = $storeRating->photos()->get()->map(function ($photo) {
            return [
                'id' => $photo->id,
                'preview' => $this->retrieveFile($photo->file_path, $photo->disk),
                'description' => $photo->description,
            ];
        });

        return $storeRating;
    }
}
