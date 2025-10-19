<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserAvatarController extends Controller
{
    // POST /users/{user}/avatar
    public function update(Request $request, User $user)
    {
        // Sin permisos especiales, pero restringido a su propio usuario (o super-admin)
        $actor = Auth::user();
        if (!$actor || (!$actor->hasRole('super-admin') && $actor->id !== $user->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'avatar' => 'required|image|max:6048',
        ]);

        $file = $request->file('avatar');
        $ext  = $file->getClientOriginalExtension();

        $media = $user->addMediaFromRequest('avatar')
            ->usingFileName("user-{$user->id}-" . Str::uuid() . ".{$ext}")
            ->withCustomProperties([
                'branch_id' => null,
                'scope'     => 'global',
                'kind'      => 'user-avatar',
            ])
            ->toMediaCollection('avatar');

        $urlSm = $user->getFirstMediaUrl('avatar', 'avatar_sm');
        $urlMd = $user->getFirstMediaUrl('avatar', 'avatar_md');
        $user->image = $urlMd ?: ($urlSm ?: $media->getUrl());
        $user->save();

        return response()->json([
            'message' => 'Avatar actualizado',
            'media_id' => $media->id,
            'url' => $user->getFirstMediaUrl('avatar', 'avatar_md'),
            'image' => $user->image,
        ]);
    }

    // GET /users/{user}/avatar
    public function show(User $user)
    {
        $media = $user->getFirstMedia('avatar');

        return response()->json([
            'exists' => (bool) $media,
            'url_sm' => $user->getFirstMediaUrl('avatar', 'avatar_sm'),
            'url_md' => $user->getFirstMediaUrl('avatar', 'avatar_md'),
            'url_lg' => $user->getFirstMediaUrl('avatar', 'avatar_lg'),
            'original_url' => $media?->getUrl(),
            'media' => $media?->only(['id','file_name','mime_type','size','custom_properties']),
        ]);
    }

    // DELETE /users/{user}/avatar
    public function destroy(User $user)
    {
        // Sin permisos especiales, pero restringido a su propio usuario (o super-admin)
        $actor = Auth::user();
        if (!$actor || (!$actor->hasRole('super-admin') && $actor->id !== $user->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user->clearMediaCollection('avatar');
        $user->image = null;
        $user->save();

        return response()->json([
            'message' => 'Avatar eliminado',
        ], 200);
    }
}

