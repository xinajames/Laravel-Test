<?php

namespace App\Http\Middleware;

use App\Enums\UserStatusEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserOnboarding
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
            (
                $user->status === UserStatusEnum::Inactive()->value ||
                is_null($user->email_verified_at)
            ) &&
            ! $request->routeIs('onboarding')
        ) {
            return redirect()->route('onboarding');
        }

        return $next($request);
    }
}
