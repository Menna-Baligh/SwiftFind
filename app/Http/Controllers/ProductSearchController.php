<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductSearchRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductSearchService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductSearchController extends Controller
{
    public function __construct(
        protected ProductSearchService $searchService
    ) {}

    public function __invoke(ProductSearchRequest $request): AnonymousResourceCollection
    {
        $products = $this->searchService->search($request->validated());

        return ProductResource::collection($products);
    }
}
