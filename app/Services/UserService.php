<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class UserService
{
    /**
     * Create a new user with role.
     */
    public function createUser(array $data, ?int $schoolId): User
    {
        return DB::transaction(function () use ($data, $schoolId) {
            $user = User::create([
                'school_id' => $schoolId,
                'name' => $data['name'],
                'email' => $data['email'],
                'mobile' => $data['mobile'] ?? null,
                'password' => Hash::make($data['password']),
                'status' => $data['status'] ?? 'active',
            ]);

            if (isset($data['role'])) {
                $role = Role::findById($data['role'], 'web');
                $user->assignRole($role);
            }

            if ($schoolId) {
                \App\Services\HolidayService::applyHolidaysToNewStaff($schoolId, $user->id);
            }

            return $user;
        });
    }

    /**
     * Update an existing user.
     */
    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $updateData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'mobile' => $data['mobile'] ?? null,
                'status' => $data['status'] ?? $user->status,
            ];

            if (!empty($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }

            $user->update($updateData);

            if (isset($data['role'])) {
                $role = Role::findById($data['role'], 'web');
                $user->syncRoles([$role]);
            }

            return $user;
        });
    }

    /**
     * Delete a user.
     */
    public function deleteUser(User $user): void
    {
        // Don't allow a user to delete themselves
        if (auth()->id() === $user->id) {
            throw new \Exception("You cannot delete your own account.");
        }

        // Prevent deleting the last remaining School Admin for a school
        if ($user->hasRole('School Admin') && $user->school_id !== null) {
            $adminCount = User::whereHas('roles', function ($query) {
                    $query->where('name', 'School Admin');
                })
                ->where('school_id', $user->school_id)
                ->where('id', '!=', $user->id)
                ->count();
            if ($adminCount === 0) {
                throw new \Exception("Cannot delete the only School Admin for this school. At least one admin is required.");
            }
        }

        $user->delete();
    }
}
