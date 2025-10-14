<?php

namespace App\Media;

use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class BranchPathGenerator implements PathGenerator
{
    protected function branchIdFrom(Media $media): string
    {
        return (string)(
            $media->branch_id
            ?? $media->getCustomProperty('branch_id')                        // <-- NUEVO
            ?? ($media->model_type === \App\Models\Branch::class ? $media->model_id : null)
            ?? ( $media->model && property_exists($media->model, 'branch_id') ? $media->model->branch_id : null )
            ?? 'public'
        );
    }

    public function getPath(Media $media): string
    {
        $branchId = $this->branchIdFrom($media);
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