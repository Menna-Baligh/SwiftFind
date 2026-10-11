<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProductSearchService
{
    protected array $allowedSortColumns = [
        'price'      => 'price',
        'created_at' => 'created_at',
    ];

    public function search(array $filters): LengthAwarePaginator
    {
        $query = Product::query()
            ->select([
                'id',
                'category_id',
                'name',
                'description',
                'price',
                'stock_quantity',
                'created_at',
            ]);

        $this->applyFulltextSearch($query, $filters['q'] ?? null);
        $this->applyPriceFilter($query, $filters['min_price'] ?? null, $filters['max_price'] ?? null);
        $this->applyCategoryFilter($query, $filters['category_id'] ?? null);
        $this->applyStockFilter($query, $filters['in_stock'] ?? null);
        $this->applySorting($query, $filters['sort_by'] ?? 'created_at', $filters['sort_order'] ?? 'desc');

        $perPage = min((int) ($filters['per_page'] ?? 15), 50);

        return $query->paginate($perPage);
    }

    protected function applyFulltextSearch(Builder $query, ?string $searchTerm): void
    {
        if (blank($searchTerm)) {
            return;
        }

        $formattedSearch = $this->formatFulltextTerm($searchTerm);

        $query->whereRaw(
            'MATCH(name, description) AGAINST(? IN BOOLEAN MODE)',
            [$formattedSearch]
        );
    }

    protected function formatFulltextTerm(string $term): string
    {
        $words = array_filter(explode(' ', trim($term)));

        return implode(' ', array_map(fn ($word) => '+' . $word . '*', $words));
    }

    protected function applyPriceFilter(Builder $query, ?float $minPrice, ?float $maxPrice): void
    {
        $query->when(! is_null($minPrice), fn (Builder $q) => $q->where('price', '>=', $minPrice))
                ->when(! is_null($maxPrice), fn (Builder $q) => $q->where('price', '<=', $maxPrice));
    }

    protected function applyCategoryFilter(Builder $query, ?int $categoryId): void
    {
        $query->when(! is_null($categoryId), fn (Builder $q) => $q->where('category_id', $categoryId));
    }

    protected function applyStockFilter(Builder $query, ?bool $inStock): void
    {
        if (is_null($inStock)) {
            return;
        }

        if ($inStock) {
            $query->where('stock_quantity', '>', 0);
        } else {
            $query->where('stock_quantity', '=', 0);
        }
    }

    protected function applySorting(Builder $query, string $sortBy, string $sortOrder): void
    {
        $column = $this->allowedSortColumns[$sortBy] ?? 'created_at';
        $direction = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($column, $direction)
                ->orderBy('id', 'desc');
    }
}
