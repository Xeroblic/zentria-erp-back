<?php
namespace App\Models\Concerns;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasModelImages
{
    // Colección principal por modelo (sobrescribir en cada modelo si difiere)
    public static function primaryCollection(): string { return 'main'; }

    public static function galleryCollection(): string { return 'gallery'; }

    public function primaryImage(): ?Media
    {
        return $this->getFirstMedia(static::primaryCollection());
    }

    public function galleryImages()
    {
        return $this->getMedia(static::galleryCollection())
            ->sortBy(fn($m) => $m->getCustomProperty('sort', 0))
            ->values();
    }

    public function primaryImagePayload(): ?array
    {
        $m = $this->primaryImage();
        return $m ? [
            'id'    => $m->id,
            'url'   => $m->getUrl(),
            'thumb' => $m->getUrl('thumb'),
            'alt'   => $m->getCustomProperty('alt'),
        ] : null;
    }

    public function galleryPayload(): array
    {
        return $this->galleryImages()->map(fn($m) => [
            'id'    => $m->id,
            'url'   => $m->getUrl(),
            'thumb' => $m->getUrl('thumb'),
            'alt'   => $m->getCustomProperty('alt'),
            'sort'  => $m->getCustomProperty('sort', 0),
        ])->all();
    }
}
