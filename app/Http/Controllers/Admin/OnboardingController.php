<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function index()
    {
        return Inertia::render('Auth/Onboarding');
    }
}
