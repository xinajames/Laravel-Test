<?php

namespace App\Services;

use App\Models\Store;
use App\Traits\HandleTransactions;

class StoreDocumentService
{
    use HandleTransactions;

    public function addDefaultDocuments(Store $store)
    {
        return $this->transact(function () use ($store) {
            $documents = [
                [
                    'document_name' => 'Franchisee Agreement',
                    'document_group' => 'Legal & Franchisee Agreements',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Supplemental Agreement',
                    'document_group' => 'Legal & Franchisee Agreements',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Amendment to Franchisee Agreement',
                    'document_group' => 'Legal & Franchisee Agreements',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Amendment to Supplemental Agreement',
                    'document_group' => 'Legal & Franchisee Agreements',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Contract of Lease',
                    'document_group' => 'Lease & Property Documents',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Transfer Certificate of Title',
                    'document_group' => 'Lease & Property Documents',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Tax Declaration',
                    'document_group' => 'Lease & Property Documents',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Department of Trade and Industry',
                    'document_group' => 'Business & Compliance Documents',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Business/ Mayor’s Permit',
                    'document_group' => 'Business & Compliance Documents',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Certificate of Registration',
                    'document_group' => 'Business & Compliance Documents',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Food and Drug Administration',
                    'document_group' => 'Business & Compliance Documents',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Authority to Print',
                    'document_group' => 'Business & Compliance Documents',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'CGL Policy',
                    'document_group' => 'Insurance Policies',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Fire Policy',
                    'document_group' => 'Business & Compliance Documents',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'GPA Policy',
                    'document_group' => 'Business & Compliance Documents',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Opening Clearance',
                    'document_group' => 'Other Clearances and Forms',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Closure Clearance',
                    'document_group' => 'Other Clearances and Forms',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Franchisee Clearance (Expansion or Renewal)',
                    'document_group' => 'Other Clearances and Forms',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Store Movement',
                    'document_group' => 'Other Clearances and Forms',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Store Closure Form - BGC',
                    'document_group' => 'Other Clearances and Forms',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Closure Letter',
                    'document_group' => 'Other Clearances and Forms',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Reminder Letter',
                    'document_group' => 'Other Clearances and Forms',
                    'file_type' => null,
                ],
            ];

            foreach ($documents as $document) {
                $store->documents()->create($document);
            }

            return $store;
        });
    }
}
