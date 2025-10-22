<?php

namespace App\Transformers;

use App\Models\User;
use Illuminate\Support\Collection;

class UserTransformer
{
    /**
     * Transform a single user.
     */
    public function transform(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'roles' => $user->roles->pluck('name'),
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    /**
     * Transform user with permissions.
     */
    public function transformWithPermissions(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'roles' => $user->roles->pluck('name'),
            'permissions' => $user->roles->flatMap->permissions->pluck('name')->unique()->values(),
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    /**
     * Transform a collection of users.
     */
    public function transformCollection(Collection $users): Collection
    {
        return $users->map(fn ($user) => $this->transform($user));
    }

    /**
     * Transform user for authentication response.
     */
    public function transformForAuth(User $user, string $token): array
    {
        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'roles' => $user->roles->pluck('name'),
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
    }
}
