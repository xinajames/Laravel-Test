<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateStoreRatingRequest;
use App\Models\Photo;
use App\Models\Store;
use App\Models\StoreRating;
use App\Models\User;
use App\Models\UserRole;
use App\Services\PhotoService;
use App\Services\StoreRatingService;
use App\Traits\HasAuditorAccess;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class StoreRatingsController extends Controller
{
    use HasAuditorAccess;

    public function __construct(
        private PhotoService $photoService,
        private StoreRatingService $storeRatingService
    ) {}

    public function handleCreate(Store $store, $start = false): RedirectResponse
    {
        $user = User::find(auth()->user()->id);

        $ongoingStoreRating = $user->ongoingStoreRating()
            ->where('store_id', $store->id)
            ->first();

        if ($start) {
            $this->storeRatingService->delete($ongoingStoreRating);
        }

        if (! $ongoingStoreRating || $start) {
            $ongoingStoreRating = $this->storeRatingService->generateStoreRatingQuestionnaire($store->id, $user);
        }

        return redirect()
            ->route('storeRatings.continue', ['id' => $ongoingStoreRating->id]);
    }

    public function continue($id): InertiaResponse|RedirectResponse
    {
        $storeRating = StoreRating::find($id);

        if (! $storeRating->is_draft) {
            $allowedDate = Carbon::parse($storeRating->rated_at)->addDay();

            if (now()->greaterThan($allowedDate)) {
                return redirect()->route('stores');
            }
        }

        $user = auth()->user();

        if ($storeRating->created_by_id !== $user->id) {
            return redirect()->route('stores')->with('error', 'No store rating data found.');
        }

        return Inertia::render('Admin/StoreRating/Create', [
            'storeRating' => $this->storeRatingService->getInformation($storeRating),
            'questionnaires' => $this->storeRatingService->getStoreRatingQuestionnaires($storeRating->id),
        ]);
    }

    public function show(StoreRating $storeRating): InertiaResponse
    {
        $user = auth()->user();

        $superAdmin = UserRole::where('type', 'Super Admin')->first();

        if ($user->user_role_id !== $superAdmin->id && ! $this->hasStoreAuditorAccess($user, $storeRating->store_id)) {
            abort(403, 'Unauthorized action.');
        }

        return Inertia::render('Admin/StoreRating/Show', [
            'storeRating' => $this->storeRatingService->getInformation($storeRating),
            'questionnaires' => $this->storeRatingService->getStoreRatingQuestionnaires($storeRating->id),
        ]);
    }

    public function handleUpload(StoreRating $storeRating): InertiaResponse
    {
        return Inertia::render('Admin/StoreRating/UploadPhoto', [
            'storeRating' => $this->storeRatingService->getInformation($storeRating),
        ]);
    }

    public function update(UpdateStoreRatingRequest $request, StoreRating $storeRating)
    {
        $validatedData = $request->validated();

        $this->storeRatingService->update($validatedData, $storeRating, auth()->user());

        return redirect()->back();
    }

    public function delete(StoreRating $storeRating)
    {
        $storeRating->photos()->each(function ($photo) {
            $this->photoService->delete($photo);
        });

        $this->storeRatingService->delete($storeRating);

        return redirect()->route('stores')->with('success', __('alert.storeRating.delete.success'));
    }

    public function deletePhoto($id)
    {

        $photo = Photo::find($id);

        $this->photoService->delete($photo);

        return redirect()->back()->with('success', __('alert.photo.delete.success'));
    }
}
