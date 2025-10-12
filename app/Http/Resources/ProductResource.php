<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request) {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'sku' => $this->sku,
            'commercial_sku' => $this->commercial_sku,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'brand' => $this->whenLoaded('brand', fn() => [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
            ]),
            'product_type' => $this->product_type,
            'warranty_months' => $this->warranty_months,
            'serial_tracking' => $this->serial_tracking,
            'short_description' => $this->short_description,
            'long_description' => $this->long_description,
            'stock' => $this->stock,
            'snippet_description' => $this->snippet_description,
            'cost' => $this->cost,
            'price' => $this->price,
            'offer_price' => $this->offer_price,
            'product_status' => $this->product_status,
            'attributes_json' => $this->attributes_json,
            'is_active' => $this->is_active,
            'category_ids' => $this->whenLoaded('categories', fn() => $this->categories->map(fn($c) => [
                'id' => $c->id, 'name' => $c->name, 'slug' => $c->slug,
            ])),
            'image'   => $this->primaryImagePayload(),
            'gallery' => $this->galleryPayload(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
