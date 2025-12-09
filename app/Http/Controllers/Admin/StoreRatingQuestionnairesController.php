<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateStoreRatingQuestionnaireRequest;
use App\Models\StoreRatingQuestionnaire;
use App\Services\StoreRatingQuestionnaireService;

class StoreRatingQuestionnairesController extends Controller
{
    public function __construct(
        private StoreRatingQuestionnaireService $storeRatingQuestionnaireService
    ) {}

    public function update(UpdateStoreRatingQuestionnaireRequest $request, StoreRatingQuestionnaire $storeRatingQuestionnaire)
    {
        $validatedData = $request->validated();

        $this->storeRatingQuestionnaireService->update($validatedData, $storeRatingQuestionnaire, auth()->user());

        return redirect()->back();
    }
}
