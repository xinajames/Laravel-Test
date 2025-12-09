<?php

namespace App\Services;

use App\Helpers\DateHelper;
use App\Models\Remark;
use App\Models\StoreRatingQuestionnaire;
use App\Traits\HandleTransactions;
use App\Traits\ManageActivities;

class RemarkService
{
    use HandleTransactions;
    use ManageActivities;

    public function store(array $remarkData, $model, $modelId, $user = null)
    {
        $user = $user ?: auth()->user();

        return $this->transact(function () use ($remarkData, $model, $modelId, $user) {
            $model = match ($model) {
                'store-rating-questionnaire' => StoreRatingQuestionnaire::find($modelId),
            };

            $remark = $model->remarks()->create([
                'remark' => $remarkData['remark'],
                'remarked_by' => $user->id,
            ]);

            $this->log($model, 'remarks.store', $user);

            return $remark;
        });
    }

    public function update(array $remarkData, Remark $remark, $user = null): Remark
    {
        $user = $user ?: auth()->user();

        return $this->transact(function () use ($remarkData, $remark, $user) {
            $remark->update($remarkData);

            $this->log($remark, 'remarks.update', $user);

            return $remark;
        });
    }

    public function delete(Remark $remark, $user = null): void
    {
        $user = $user ?: auth()->user();

        $this->transact(function () use ($remark, $user) {
            $remark->delete();

            $this->log($remark, 'remarks.delete', $user);
        });
    }

    public function getRemarks($model): array
    {
        return $model->remarks()->orderBy('created_at', 'desc')->get()->map(
            function ($remark) {
                return [
                    'date' => DateHelper::changeDateFormat($remark->updated_at, 'F j, Y • g:ia'),
                    'user_name' => $remark->remarkedBy->name,
                    'message' => $remark->remark,
                    'id' => $remark->id,
                    'created_by' => $remark->remarked_by,
                    'profile_photo_url' => $remark->remarkedBy?->profile_photo_url,
                ];
            }
        )->toArray();
    }
}
