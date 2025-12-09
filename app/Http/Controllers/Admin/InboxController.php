<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Inertia\Inertia;

class InboxController extends Controller
{
    public function index()
    {
        $stores = Store::where('is_active', true)
            ->where('is_draft', false)
            ->whereNull('deleted_at')
            ->get();

        return Inertia::render('Admin/Inbox/Index', [
            'stores' => $stores->toArray(),
        ]);
    }
}
