<?php

namespace App\Services;

use App\Models\Franchisee;
use App\Traits\HandleTransactions;

class FranchiseeDocumentService
{
    use HandleTransactions;

    public function addDefaultDocuments(Franchisee $franchisee)
    {
        return $this->transact(function () use ($franchisee) {
            $documents = [
                [
                    'document_name' => 'Letter of Intent',
                    'document_group' => 'Personal and Financial Documents',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Franchise Qualification Data Form',
                    'document_group' => 'Personal and Financial Documents',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Valid ID',
                    'document_group' => 'Personal and Financial Documents',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Bank Certificate',
                    'document_group' => 'Personal and Financial Documents',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Medical Certification',
                    'document_group' => 'Personal and Financial Documents',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'BIR 2303 (Corporation)',
                    'document_group' => 'Corporate Documents',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Articles of Incorporation (Corporation)',
                    'document_group' => 'Corporate Documents',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'SEC (Corporation)',
                    'document_group' => 'Corporate Documents',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Deed of Assignment',
                    'document_group' => 'Legal Agreements and Special Documents',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Deed of Sale',
                    'document_group' => 'Legal Agreements and Special Documents',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Memorandum of Agreement',
                    'document_group' => 'Legal Agreements and Special Documents',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Memorandum of Understanding',
                    'document_group' => 'Legal Agreements and Special Documents',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Special Power of Attorney',
                    'document_group' => 'Legal Agreements and Special Documents',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Default Letter',
                    'document_group' => 'Official Correspondence',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Certification Letter',
                    'document_group' => 'Official Correspondence',
                    'file_type' => null,
                ],
                [
                    'document_name' => 'Termination Letter',
                    'document_group' => 'Official Correspondence',
                    'file_type' => null,
                ],
            ];

            foreach ($documents as $document) {
                $franchisee->documents()->create($document);
            }

            return $franchisee;
        });
    }
}
