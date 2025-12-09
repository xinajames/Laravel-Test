<?php

namespace App\Services;

use App\Enums\UserStatusEnum;
use App\Enums\UserTypeEnum;
use App\Helpers\DateHelper;
use App\Models\User;
use App\Notifications\AdminEmailChangeNotification;
use App\Notifications\AdminInviteNotification;
use App\Notifications\AdminPasswordChangeNotification;
use App\Support\Filters\FuzzyFilter;
use App\Traits\HandleTransactions;
use App\Traits\ManageActivities;
use App\Traits\ManageFilesystems;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserService
{
    use HandleTransactions;
    use ManageActivities;
    use ManageFilesystems;

    public function __construct(
        private PasswordService $passwordService,
    ) {}

    public function storeAdminUser(array $userData, $generatedPassword = null, $user = null)
    {
        $generatedPassword = $generatedPassword ?? $this->passwordService->generatePassword();
        $user = $user ?? auth()->user();

        return $this->transact(function () use ($userData, $generatedPassword, $user) {
            $userData['user_type_id'] = UserTypeEnum::Admin()->value;
            $userData['status'] = UserStatusEnum::Inactive()->value;
            $userData['password'] = Hash::make($generatedPassword);
            $userData['password_updated_at'] = now();

            $admin = User::create($userData);

            $admin->syncRoles([Str::slug($admin->userRole?->type)]);

            $this->log($admin, 'adminUsers.store', $user);

            $notification = new AdminInviteNotification($generatedPassword);
            NotificationService::handleNotification($admin, $notification, false);

            return $admin;
        });
    }

    public function resetPassword(User $team, $newPassword)
    {
        return $this->transact(function () use ($team, $newPassword) {
            $user = $user ?? auth()->user();

            $team->update([
                'password' => Hash::make($newPassword),
                'status' => UserStatusEnum::Inactive()->value,
                'password_updated_at' => now(),
            ]);

            $this->log($team, 'adminUsers.update', $user);

            $notification = new AdminPasswordChangeNotification($newPassword);
            NotificationService::handleNotification($team, $notification, false);

            return $team;
        });
    }

    public function update(array $userData, User $teamMember, $user = null)
    {
        $user = $user ?? auth()->user();
        $basePath = $this->generateUploadBasePath();

        return $this->transact(function () use ($userData, $teamMember, $user, $basePath) {
            if (! empty($userData['photo']) && is_file($userData['photo'])) {
                if (! empty($teamMember->profile_photo)) {
                    $this->deleteFile($teamMember->profile_photo);
                }

                $originalName = $userData['photo']->getClientOriginalName();
                $baseFileName = pathinfo($originalName, PATHINFO_FILENAME);
                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                $fileName = "{$baseFileName}.{$extension}";
                $userData['profile_photo'] = "{$basePath}/user/profile/{$fileName}";
                $this->upload($userData['photo'], $userData['profile_photo']);
            }

            $emailChanged = false;
            if (! empty($userData['email']) && $userData['email'] !== $teamMember->email) {
                $emailChanged = true;
            }

            $teamMember->update($userData);

            // Sync roles
            if (isset($userData['user_role_id'])) {
                $teamMember->syncRoles([Str::slug($teamMember->userRole?->type)]);
            }

            // Send email change notification
            if ($emailChanged) {
                $generatedPassword = $this->passwordService->generatePassword();
                $teamMember->update([
                    'email_verified_at' => null,
                    'password' => Hash::make($generatedPassword),
                    'status' => UserStatusEnum::Inactive()->value,
                    'password_updated_at' => now(),
                ]);

                $notification = new AdminEmailChangeNotification($generatedPassword);
                NotificationService::handleNotification($teamMember, $notification, false);
            }

            $this->log($teamMember, 'adminUsers.update', $user);

            return $teamMember;
        });
    }

    public function delete(User $user)
    {
        $this->transact(function () use ($user) {
            $user->delete();

            $this->log($user, 'adminUsers.delete');
        });
    }

    public function updateIsActive(User $user, $logAction, $isActive = true)
    {
        return $this->transact(function () use ($user, $logAction, $isActive) {
            $user->update([
                'status' => $isActive ? UserStatusEnum::Active() : UserStatusEnum::Deactivated(),
            ]);

            $this->log($user, $logAction);

            return $user;
        });
    }

    public function getAdminsDataTable($filters = [], $orders = [], $perPage = 10)
    {
        $query = User::query()
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'users.status',
                'users.user_role_id',
                'users.created_at',
                'users.updated_at',
                'user_roles.type',
                'users.profile_photo',
            )
            ->leftJoin('user_roles', 'users.user_role_id', '=', 'user_roles.id')
            ->where('user_type_id', UserTypeEnum::Admin()->value)
            ->whereNull('users.deleted_at');

        if ($filters) {
            foreach ($filters as $filter) {
                if (isset($filter['column'])) {
                    $columnName = $filter['column'];
                    $operator = $filter['operator'];
                    $value = $filter['value'];

                    $query->where($columnName, $operator, $value);
                }
            }
        }

        $sortColumn = 'users.name';
        if ($orders) {
            foreach ($orders as $column => $data) {
                $sortColumn = $data['column'];
                if ($data['value'] == 'desc') {
                    $sortColumn = '-'.$data['column']; // Hyphen on front means descending
                }
            }
        }

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::custom(
                    'search',
                    new FuzzyFilter(
                        'name',
                        'email',
                        'user_roles.type',
                    )
                ),
            ])
            ->defaultSort($sortColumn)
            ->paginate($perPage);

        $formattedData = $data->getCollection()->map(function ($data) {
            $data->status_label = UserStatusEnum::getDescription($data->status);
            $data->formatted_created_at = DateHelper::changeDateTimeFormat($data->created_at);
            $data->formatted_updated_at = DateHelper::changeDateTimeFormat($data->updated_at);

            return $data;
        });

        $data->setCollection($formattedData);

        return $data;
    }

    public function updatePassword(array $userData, $user)
    {
        return $this->transact(function () use ($userData, $user) {
            if (! Hash::check($userData['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => 'The current password is incorrect.',
                ]);
            }

            $user->update([
                'password' => Hash::make($userData['password']),
            ]);
        });
    }
}
