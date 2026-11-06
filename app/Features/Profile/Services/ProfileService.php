<?php

namespace App\Features\Profile\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    public function update(
        User $user,
        array $data,
        ?UploadedFile $profilePhoto = null,
    ): User {
        $oldPhotoPath = $user->profile_photo_path;
        $newPhotoPath = $profilePhoto?->store('profiles', 'public') ?? $oldPhotoPath;

        $user->update([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'profile_photo_path' => $newPhotoPath,
        ]);

        if ($newPhotoPath !== $oldPhotoPath && $oldPhotoPath) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        if (filled($data['password'] ?? null)) {
            $user->forceFill([
                'password' => Hash::make($data['password']),
            ])->save();
        }

        return $user->refresh();
    }
}
