<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserAvatarController extends Controller
{
    // PUT /users/{user}/avatar
    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user); // Usa tu UserPolicy/BranchPolicy según corresponda

        $validated = $request->validate([
            'avatar' => 'required|image|max:6048', // 6MB; ajusta a tu política
        ]);

        $file = $request->file('avatar');
        $ext  = $file->getClientOriginalExtension();

        // singleFile() reemplaza automáticamente el anterior
        $media = $user->addMediaFromRequest('avatar')
            ->usingFileName("user-{$user->id}-".Str::uuid().".{$ext}")
            // clave: marcar como global (sin branch)
            ->withCustomProperties([
                'branch_id' => null,       // fuerza 'branch-global' en PathGenerator
                'scope'     => 'global',   // metadato útil para auditoría
                'kind'      => 'user-avatar'
            ])
            ->toMediaCollection('avatar');

        return response()->json([
            'message' => 'Avatar actualizado',
            'media_id' => $media->id,
            'url' => $user->getFirstMediaUrl('avatar', 'avatar_md'),
        ]);
    }

    // GET /users/{user}/avatar
    public function show(User $user)
    {
        $this->authorize('view', $user);

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
        $this->authorize('update', $user);

        $user->clearMediaCollection('avatar');

        return response()->json([
            'message' => 'Avatar eliminado',
        ], 200);
    }
}