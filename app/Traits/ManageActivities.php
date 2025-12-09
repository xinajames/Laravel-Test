<?php

namespace App\Traits;

use App\Helpers\DateHelper;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

trait ManageActivities
{
    public function log($modelObject, $activityType, $user = null, $customDescription = null): void
    {
        $user = $user ?? auth()->user();

        /* Generate description referencing from activity type/ custom description */
        if (! $customDescription) {
            $activityModel = preg_replace('/\..*/', '', $activityType);
            $langData = $this->getActivityLangData($activityModel, $modelObject, $user);

            $description = __('activity.'.$activityType, $langData);
            $title = __('activity.'.$activityType.'.title', $langData);
        } else {
            $description = $customDescription;
        }

        $properties = [
            'title' => $title ?? __('activity.'.$activityType.'.title'),
            'activity_type' => $activityType,
        ];

        // Override the model object as needed
        if ($activityModel === 'storeRatings') {
            $modelObject = $modelObject->store;
        } elseif ($activityModel === 'storeAuditors') {
            $modelObject = $modelObject->store;
        }

        activity()
            ->performedOn($modelObject)
            ->causedBy($user)
            ->withProperties($properties)
            ->log($description);
    }

    protected function getActivityLangData($activity, $modelObject, $user): array
    {
        return match ($activity) {
            'users' => [
                'user' => $modelObject->name,
                'userEmail' => $modelObject->email,
                'userName' => $user->name,
            ],
            'franchisees' => [
                'franchisee' => $modelObject->full_name,
                'userName' => $user->name,
            ],
            'stores' => [
                'store' => $modelObject->jbs_name,
                'userName' => $user->name,
            ],
            'storeRatings' => [
                'store' => $modelObject->store->jbs_name,
                'userName' => $user->name,
            ],
            'storeAuditors' => [
                'storeAuditor' => $modelObject->user->name,
                'store' => $modelObject->store->jbs_name,
                'userName' => $user->name,
            ],
            'reminders' => [
                'reminder' => $modelObject->title,
                'store' => $modelObject->jbs_name ?: ($modelObject->name ?: 'N/A'),
                'userName' => $user->name,
            ],
            'documents' => [
                'document' => $modelObject->document_name,
                'userName' => $user->name,
            ],
            'adminUsers' => [
                'userName' => $user->name,
                'adminName' => $modelObject->name,
                'adminEmail' => $modelObject->email,
            ],
            'userRoles' => [
                'userName' => $user->name,
                'role' => $modelObject->type,
            ],
            default => [
                'userName' => $user->name,
            ],
        };
    }

    public function getActivities($model_id, $type, $offset = null, $limit = null): array
    {
        /* Get activities with a dynamic model */
        return Activity::where('subject_type', $type)
            ->where('subject_id', $model_id)->get()->sortByDesc('id')->skip($offset)->take($limit)->map(
                function ($activity) {
                    return [
                        'id' => $activity->id,
                        'title' => $activity->properties['title'],
                        'description' => $activity->description,
                        'date' => DateHelper::changeDateFormat($activity->created_at),
                        'time' => DateHelper::changeTimeFormat($activity->created_at),
                        'subject_type' => $activity->subject_type,
                        'user' => User::find($activity->causer_id),
                    ];
                }
            )
            ->values()
            ->toArray();
    }
}
