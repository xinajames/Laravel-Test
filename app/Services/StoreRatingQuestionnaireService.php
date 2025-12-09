<?php

namespace App\Services;

use App\Models\StoreRatingQuestionnaire;
use App\Traits\HandleTransactions;

class StoreRatingQuestionnaireService
{
    use HandleTransactions;

    public function update(array $data, StoreRatingQuestionnaire $storeRatingQuestionnaire, $user = null)
    {
        $user = $user ?? auth()->user();

        return $this->transact(function () use ($data, $storeRatingQuestionnaire) {
            $storeRatingQuestionnaire->update($data);

            return $storeRatingQuestionnaire;
        });
    }
}
