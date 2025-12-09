<?php

namespace App\Services;

use App\Models\StoreAuditor;
use App\Models\User;
use App\Support\Filters\FuzzyFilter;
use App\Traits\HandleTransactions;
use App\Traits\ManageActivities;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class StoreAuditorService
{
    use HandleTransactions;
    use ManageActivities;

    public function store(array $storeAuditorData, $user = null)
    {
        $user = $user ?? auth()->user();

        return $this->transact(function () use ($storeAuditorData, $user) {
            $storeAuditor = StoreAuditor::create($storeAuditorData);

            $this->log($storeAuditor, 'storeAuditors.store', $user);

            return $storeAuditor;
        });
    }

    public function delete(StoreAuditor $storeAuditor, $user = null)
    {
        $user = $user ?? auth()->user();

        return $this->transact(function () use ($storeAuditor, $user) {
            $storeAuditor->delete();

            $this->log($storeAuditor, 'storeAuditors.delete', $user);

            return $storeAuditor;
        });
    }

    public function getDataTable($filters = [], $orders = [])
    {
        $query = StoreAuditor::query()
            ->with(['user:id,name,email,user_role_id'])
            ->select(
                'store_auditors.id as store_auditor_id',
                'store_auditors.store_id',
                'store_auditors.user_id'
            )
            ->join('users', 'store_auditors.user_id', '=', 'users.id')
            ->join('stores', 'store_auditors.store_id', '=', 'stores.id')
            ->addSelect([
                'auditor_name' => User::selectRaw('CONCAT(name)')
                    ->whereColumn('users.id', 'store_auditors.user_id'),
            ]);

        // Apply store filter
        if (! empty($filters['store_id'])) {
            $query->where('store_auditors.store_id', $filters['store_id']);
        }

        // Apply additional filters
        if ($filters) {
            foreach ($filters as $filter) {
                if (isset($filter['column']) && $filter['column'] !== 'store_id') {
                    $query->where($filter['column'], $filter['operator'], $filter['value']);
                }
            }
        }

        $sortColumn = 'users.updated_at';
        if ($orders) {
            foreach ($orders as $column => $data) {
                $sortColumn = $data['column'];
                if ($data['value'] == 'desc') {
                    $sortColumn = '-'.$data['column'];
                }
            }
        }

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('store_id'),
                AllowedFilter::custom(
                    'search',
                    new FuzzyFilter(
                        'users.email',
                        'users.name',
                    )
                ),
            ])
            ->defaultSort($sortColumn)
            ->get(); // Retrieve all results instead of paginating

        return $data;
    }

    public function getAll($storeId)
    {
        return User::where('user_role_id', 2) // Store-Auditors
            ->whereNotIn('id', function ($query) use ($storeId) {
                $query->select('user_id')
                    ->from('store_auditors')
                    ->where('store_id', $storeId); // Exclude auditors assigned to this store
            })
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => "{$user->name} ({$user->email})",
                ];
            })
            ->toArray();
    }
}
