<?php

namespace App\Services;

use App\Helpers\DateHelper;
use App\Models\Activity;
use App\Models\User;
use App\Support\Filters\FuzzyFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ActivityLogService
{
    public function getDataTable($filters = [], $orders = [], $perPage = 10): LengthAwarePaginator
    {
        $query = Activity::query()
            ->from('activity_log')
            ->leftJoin('users', 'users.id', '=', 'activity_log.causer_id')
            ->leftJoin('user_roles', 'user_roles.id', '=', 'users.user_role_id')
            ->select([
                'activity_log.*',
                'users.name as causer_name',
                'user_roles.type as causer_role',
                'activity_log.properties->title as title',
                'activity_log.properties->activity_type as activity_type',
            ])
            ->with('causer');

        if ($filters) {
            foreach ($filters as $filter) {
                if (isset($filter['column'])) {
                    $columnName = $filter['column'];
                    $operator = $filter['operator'];
                    $value = $filter['value'];

                    $query->where($columnName, $operator, $value);
                }
            }
        }

        $sortColumn = 'activity_log.updated_at';
        if ($orders) {
            foreach ($orders as $column => $data) {
                $sortColumn = $data['column'];
                if ($data['value'] == 'desc') {
                    $sortColumn = '-'.$data['column']; // Hyphen on front means descending
                }
            }
        }

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::custom(
                    'search',
                    new FuzzyFilter(
                        'activity_log.subject_type',
                        'activity_log.description',
                        'activity_log.created_at',
                        'users.name',
                    )
                ),
            ])
            ->defaultSort($sortColumn)
            ->paginate($perPage);

        $data->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'description' => $item->description,
                'title' => $item->title,
                'activity_type' => $item->activity_type,
                'user_name' => $item->causer_name,
                'causer_role' => $item->causer_role,
                'formatted_date' => DateHelper::changeDateTimeFormat($item->created_at, 'M d, Y'),
                'formatted_time' => DateHelper::changeDateTimeFormat($item->created_at, 'h:i A'),
                'profile_photo_url' => optional($item->causer)->profile_photo_url,
            ];
        });

        return $data;
    }

    public function getDataList($field = null): array
    {
        return User::all()->map(function ($user) use ($field) {
            return [
                'id' => $user->id,
                'value' => $user->id,
                'label' => $field ? $user->$field : $user->name,
            ];
        })->unique('value')->values()->toArray();
    }
}
