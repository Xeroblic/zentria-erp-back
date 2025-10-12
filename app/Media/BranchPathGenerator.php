<?php

namespace App\Media;

use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class BranchPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        // Detecta el branch de forma segura antes de guardar el archivo
        $branchId = $media->branch_id
            ?? ($media->model && property_exists($media->model, 'branch_id') ? $media->model->branch_id : null)
            ?? ($media->model_type === \App\Models\Branch::class ? $media->model_id : null)
            ?? 'public';

        $type = class_basename($media->model_type ?? 'Unknown');

        return "media/branch-{$branchId}/{$type}/{$media->model_id}/";
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media) . 'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media) . 'responsive/';
    }
}