<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'description'    => $this->description,
            'price'          => (float) $this->price,
            'category_id'    => $this->category_id,
            'in_stock'       => $this->stock_quantity > 0,
            'stock_quantity' => $this->stock_quantity,
            'created_at'     => $this->created_at->toDateTimeString(),
        ];
    }
}
