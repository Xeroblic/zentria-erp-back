<?php
namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

trait TogglesActiveFlag
{
    protected function toggleModelActive(Model $model, string $label): JsonResponse
    {
        try {
            // Autorización a nivel de política (recomendado)
            if (method_exists($this, 'authorize')) {
                $this->authorize('update', $model);
            }

            // Si manejas multi-empresa sin Policy, descomenta:
            // if (property_exists($model, 'company_id') && $model->company_id !== auth()->user()->company_id) {
            //     abort(403, 'No autorizado');
            // }

            $model->is_active = !($model->is_active ?? true);
            $model->save();

            return response()->json([
                'success' => true,
                'message' => $model->is_active ? "$label activado" : "$label desactivado",
                'data' => ['is_active' => $model->is_active]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => "Error al cambiar estado de $label",
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
