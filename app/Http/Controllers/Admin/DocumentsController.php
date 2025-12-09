<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\DocumentService;
use App\Traits\HasUserPermissions;
use App\Traits\ManageFilesystems;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DocumentsController extends Controller
{
    use HasUserPermissions;
    use ManageFilesystems;

    public function __construct(
        private DocumentService $documentService,
    ) {}

    public function index()
    {
        return Inertia::render('Admin/Documents/Index');
    }

    public function getDataTable($model = null, $id = null)
    {
        if (! request()->wantsJson()) {
            return response()->json([], 406);
        }

        $filters = request('filters', []);
        $orders = request('orders', []);
        $perPage = (int) request('perPage', 10);

        $requestPayload = [];
        if ($model && $id) {
            $requestPayload['model'] = $model;
            $requestPayload['id'] = $id;
        }

        $data = $this->documentService->getDataTable($filters, $orders, $perPage, $requestPayload);

        return response()->json($data);
    }

    public function upload(Request $request)
    {
        $validated = $request->validate([
            'documents' => ['required', 'array', 'max:10'],
            'documents.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
            'model' => ['required', Rule::in(['store', 'franchisee'])],
            'model_id' => ['required', 'integer'],
        ]);

        $this->documentService->store(
            $validated['documents'],
            $validated['model'],
            (int) $validated['model_id']
        );

        return redirect()->back()->with('success', __('alert.document.store.success'));
    }

    public function delete(Document $document)
    {
        $this->documentService->delete($document);

        return redirect()->back()->with('success', __('alert.document.delete.success'));
    }

    public function download(Document $document)
    {
        return $this->downloadFile(
            $document->file_path,
            $document->document_name ?: basename($document->file_path),
            $document->disk
        );
    }
}
