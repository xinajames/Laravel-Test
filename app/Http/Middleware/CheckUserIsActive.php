<?php

namespace App\Http\Middleware;

use App\Enums\UserStatusEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (
            $user &&
            in_array($user->status, [
                UserStatusEnum::Deactivated()->value,
                UserStatusEnum::Expired()->value,
            ])
        ) {
            Auth::logout();

            return redirect()->route('login')->withErrors([
                'email' => __('auth.login.restricted'),
            ]);
        }

        return $next($request);
    }
}
