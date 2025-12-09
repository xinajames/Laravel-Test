<?php

namespace App\Services;

use App\Helpers\DateHelper;
use App\Models\Franchisee;
use App\Models\Store;
use App\Models\StoreHistory;
use App\Traits\HandleTransactions;

class StoreHistoryService
{
    use HandleTransactions;

    protected array $trackableFields = [
        'last_renewal_date',
        'cgl_expiry_date',
        'fire_expiry_date',
        'insurance_period_covered_date',
        'insurance_expiry_date',
        'contract_of_lease_start_date',
        'contract_of_lease_end_date',
        'escalation',
        'lessor_name',
        'lease_payment_date',
        'notarized_stamp_payment_receipt_number',
        'col_notarized_date',
        'col_notarized_by',
        'maintenance_last_repaint_at',
        'maintenance_last_renovation_at',
        'maintenance_temporary_closed_at',
        'maintenance_temporary_closed_reason',
        'maintenance_reopening_date',
        'maintenance_upgrade_date',
        'maintenance_downgrade_date',
        'maintenance_remarks',
        'maintenance_store_acquired_at',
        'maintenance_store_transferred_at',
        'maintenance_old_franchisee_code',
        'maintenance_old_branch_code',
        'store_type',
        'store_status',  // Add this for royalty calculation
        'cluster_code',
        'jbmis_code',    // Add this for tracking
        'franchisee_id',
    ];

    public function logChanges(Store $store, array $newData, $user)
    {
        $this->transact(function () use ($store, $newData, $user) {
            $oldData = $store->getOriginal();

            $changeLog = [];
            $changeGroupId = null;

            // Check if both cluster_code and jbmis_code are being changed and both original values exist
            $isClusterJbmisChange = false;
            if (array_key_exists('cluster_code', $newData) && array_key_exists('jbmis_code', $newData)) {
                $clusterChanged = ($oldData['cluster_code'] ?? null) != $newData['cluster_code'];
                $jbmisChanged = ($oldData['jbmis_code'] ?? null) != $newData['jbmis_code'];
                
                // Only create a coordinated change group if both original values exist (not null)
                $bothOriginalValuesExist = !is_null($oldData['cluster_code'] ?? null) && !is_null($oldData['jbmis_code'] ?? null);
                
                if (($clusterChanged || $jbmisChanged) && $bothOriginalValuesExist) {
                    $isClusterJbmisChange = true;
                    $changeGroupId = 'cluster_jbmis_' . now()->timestamp . '_' . uniqid();
                }
            }

            foreach ($this->trackableFields as $field) {
                if (array_key_exists($field, $newData)) {
                    $oldValue = $oldData[$field] ?? null;
                    $newValue = $newData[$field];

                    // Log only if the value has changed
                    if ($oldValue != $newValue) {
                        $logEntry = [
                            'store_id' => $store->id,
                            'user_id' => $user->id ?? null,
                            'field' => $field,
                            'old_value' => $oldValue,
                            'new_value' => $newValue,
                            'effective_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        // Add change_group_id if this is a coordinated cluster/jbmis change
                        if ($isClusterJbmisChange && in_array($field, ['cluster_code', 'jbmis_code'])) {
                            $logEntry['change_group_id'] = $changeGroupId;
                        }

                        $changeLog[] = $logEntry;
                    }
                }
            }

            if (! empty($changeLog)) {
                StoreHistory::insert($changeLog);
            }
        });
    }

    public function getTrackedFields(): array
    {
        return $this->trackableFields;
    }

    public function addHistoryEntry(Store $store, array $historyData, $user)
    {
        // Validate that the field is supported for history tracking
        $supportedFields = ['jbmis_code', 'cluster_code'];

        if (!in_array($historyData['field'], $supportedFields)) {
            throw new \InvalidArgumentException('Field is not supported for history tracking.');
        }

        $this->transact(function () use ($store, $historyData, $user) {
            StoreHistory::create([
                'store_id' => $store->id,
                'user_id' => $user->id ?? null,
                'field' => $historyData['field'],
                'old_value' => null, // Always null for manual history entries
                'new_value' => $historyData['new_value'] ?? null,
                'effective_at' => $historyData['effective_at'],
                'created_at' => $historyData['effective_at'],
                'updated_at' => now(),
            ]);
        });
    }

    public function addCoordinatedHistoryEntry(Store $store, array $historyData, $user)
    {
        $this->transact(function () use ($store, $historyData, $user) {
            $changeGroupId = 'manual_cluster_jbmis_' . now()->timestamp . '_' . uniqid();

            // Add cluster_code history entry
            StoreHistory::create([
                'store_id' => $store->id,
                'user_id' => $user->id ?? null,
                'field' => 'cluster_code',
                'old_value' => null, // Always null for manual history entries
                'new_value' => $historyData['cluster_code'],
                'effective_at' => $historyData['effective_at'],
                'change_group_id' => $changeGroupId,
                'created_at' => $historyData['effective_at'],
                'updated_at' => now(),
            ]);

            // Add jbmis_code history entry
            StoreHistory::create([
                'store_id' => $store->id,
                'user_id' => $user->id ?? null,
                'field' => 'jbmis_code',
                'old_value' => null, // Always null for manual history entries
                'new_value' => $historyData['jbmis_code'],
                'effective_at' => $historyData['effective_at'],
                'change_group_id' => $changeGroupId,
                'created_at' => $historyData['effective_at'],
                'updated_at' => now(),
            ]);
        });
    }

    public function getHistory(Store $store, ?string $field = null)
    {
        $query = StoreHistory::where('store_id', $store->id)->with('user')->orderBy('created_at', 'desc');

        if ($field) {
            $query->where('field', $field);
        }

        $history = $query->get();

        return [
            'store_id' => $store->id,
            'history' => $history->map(function ($record) {
                $formattedDate = $record->updated_at
                    ? DateHelper::changeDateTimeFormat($record->updated_at, 'M d, Y') : null;
                $formattedTime = $record->updated_at
                    ? DateHelper::changeDateTimeFormat($record->updated_at, 'h:i A') : null;

                $oldValue = $record->old_value ?? '—';
                $newValue = $record->new_value ?? '—';

                // Customize display for franchisee_id field
                if ($record->field === 'franchisee_id') {
                    $oldFranchisee = $oldValue && $oldValue !== '—' ? \App\Models\Franchisee::find($oldValue) : null;
                    $newFranchisee = $newValue && $newValue !== '—' ? \App\Models\Franchisee::find($newValue) : null;

                    $oldDisplay = $oldFranchisee ? $oldFranchisee->franchisee_code.' - '.$oldFranchisee->full_name : '—';
                    $newDisplay = $newFranchisee ? $newFranchisee->franchisee_code.' - '.$newFranchisee->full_name : '—';

                    $title = "Franchisee assignment changed from [{$oldDisplay}] to [{$newDisplay}].";
                } else {
                    $title = "Data field updated from [{$oldValue}] to [{$newValue}].";
                }

                return [
                    'id' => $record->id,
                    'field' => $record->field,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                    'title' => $title,
                    'description' => null,
                    'user' => $record->user ? [
                        'id' => $record->user->id,
                        'name' => $record->user->name,
                        'type' => $record->user->adminType ?? null,
                        'profile_photo' => $record->user->profile_photo_url,
                    ] : null,
                    'date' => $formattedDate,
                    'time' => $formattedTime,
                    'created_at' => $record->created_at->toDateTimeString(),
                ];
            }),
        ];
    }
}
