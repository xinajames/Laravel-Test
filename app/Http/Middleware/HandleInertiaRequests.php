<?php

namespace App\Http\Middleware;

use App\Traits\HasUserPermissions;
use App\Traits\ManageNotifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    use HasUserPermissions;
    use ManageNotifications;

    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'app' => [
                'version' => Config::get('app.version', '1.0.0'),
                'environment' => Config::get('app.env', 'local'),
            ],
            'auth' => [
                'user' => $user,
                'permissions' => $user ? $this->getModulePermissions() : [],
                'unreadNotificationsCount' => $user ? $this->getCombinedUnreadCount($user) : null,
            ],
            'flash' => function () {
                return [
                    'success' => Session::get('success'),
                    'error' => Session::get('error'),
                    'warning' => Session::get('warning'),
                    'info' => Session::get('info'),
                ];
            },
        ];
    }
}
