<?php

namespace App\Services;

use App\Models\Questionnaire;

class QuestionnaireService
{
    public function getStructuredQuestionnaire(): array
    {
        // Fetch all questionnaires with related category and subcategory
        $questionnaires = Questionnaire::with(['category', 'subcategory'])
            ->orderBy('type')
            ->orderBy('category_id')
            ->orderBy('subcategory_id')
            ->orderBy('order')
            ->get();

        $structuredData = [];

        foreach ($questionnaires as $questionnaire) {
            $type = $questionnaire->type;
            $category = $questionnaire->category?->name;
            $subcategory = $questionnaire->subcategory?->name;
            $questionData = [
                'id' => $questionnaire->id,
                'order' => $questionnaire->order,
                'question' => $questionnaire->question,
            ];

            // If no category or subcategory, store directly under type
            if (! $category) {
                $structuredData[$type][] = $questionData;
            } elseif (! $subcategory) {
                $structuredData[$type][$category][] = $questionData;
            } else {
                $structuredData[$type][$category][$subcategory][] = $questionData;
            }
        }

        return $structuredData;
    }
}
