<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRemarkRequest;
use App\Http\Requests\UpdateRemarkRequest;
use App\Models\Remark;
use App\Services\RemarkService;

class RemarksController extends Controller
{
    public function __construct(private RemarkService $remarkService
    ) {}

    public function store($modelId, $model, StoreRemarkRequest $request)
    {
        $validated = $request->validated();

        $this->remarkService->store($validated, $model, $modelId, auth()->user());

        return redirect()->back();
    }

    public function update(Remark $remark, UpdateRemarkRequest $request)
    {
        $validated = $request->validated();

        $this->remarkService->update($validated, $remark, auth()->user());

        return redirect()->back();
    }

    public function delete(Remark $remark)
    {
        $this->remarkService->delete($remark);

        return redirect()->back();
    }
}
