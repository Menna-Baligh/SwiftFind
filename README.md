# SwiftFind — Product Search API

SwiftFind is a high-performance RESTful API built with Laravel 11 for searching and filtering e-commerce products. It provides flexible full-text search, multi-attribute filtering, deterministic sorting, and capped pagination designed for low latency and efficient database resource utilization.

---

## 1. Project Overview

- **Purpose**: Provides a fast, reliable search endpoint for product catalogs.
- **Problem Solved**: Replaces unindexed, memory-heavy queries and inefficient `LIKE '%...%'` scans with indexed database-level queries, strict validation, and bounded pagination.
- **Key Features**:
  - Full-text search across product name and description.
  - Price range, category, and inventory status filters.
  - Direction-aligned sorting by price or creation date with tie-breaker stabilization.
  - Bounded pagination (maximum 50 items per page).
  - Standardized JSON responses via Laravel API Resources.

---

## 2. How the Search Works

- **Search Strategy**: Uses MySQL/MariaDB `FULLTEXT` index (`idx_fulltext_name_desc`) in Boolean Mode. Search input terms are sanitized and converted into prefix matching operators (`+word*`) so partial word matches are indexed without full-table scans.
- **Query Building**: Built via Eloquent Query Builder using conditional `when()` clauses and prepared bindings for SQL injection safety. Columns are explicitly selected (`id`, `category_id`, `name`, `description`, `price`, `stock_quantity`, `created_at`) to eliminate excessive memory usage.
- **Sorting & Tie-Breaking**: Allowed sort fields are restricted to a strict whitelist (`price`, `created_at`). Queries apply a secondary `id` tie-breaker using the exact same direction (`orderBy($column, $direction)->orderBy('id', $direction)`). This alignment allows database storage engines to traverse B-tree indexes directly without triggering a temporary filesort.
- **Indexing Optimizations**:
  - `FULLTEXT(name, description)`: Powers keyword searches on textual fields.
  - `INDEX idx_price_id(price, id)`: Powers price filtering and index-backed price sorting.
  - `INDEX idx_cat_stock_price(category_id, stock_quantity, price)`: Optimizes combined category, availability, and price range lookups.
  - `INDEX idx_created_at(created_at)`: Optimizes chronological sorting.
- **Pagination**: Uses Laravel's `LengthAwarePaginator` capped at 50 items per page to prevent memory exhaustion and excessive database offset overhead.

---

## 3. Available Search Filters and Sorting

### Supported Query Parameters

| Parameter | Type | Purpose | Validation / Accepted Values | Default |
| :--- | :--- | :--- | :--- | :--- |
| `q` | String | Searches within name and description | Optional. Maximum 100 characters. | None |
| `min_price` | Numeric | Lower bound for product price | Optional. Numeric `>= 0`. | None |
| `max_price` | Numeric | Upper bound for product price | Optional. Numeric `>= 0`, must be `>= min_price`. | None |
| `category_id` | Integer | Filters by category ID | Optional. Must exist in `categories` table. | None |
| `in_stock` | Boolean | Filters by inventory availability | Optional. Accepts `1`, `0`, `true`, `false`. (`1` = stock > 0, `0` = stock == 0). | None (all items) |
| `sort_by` | String | Attribute to order results by | Optional. Whitelist: `price`, `created_at`. | `created_at` |
| `sort_order` | String | Sort direction | Optional. Accepted: `asc`, `desc`. | `desc` |
| `page` | Integer | Pagination page number | Optional. Minimum `1`. | `1` |
| `per_page` | Integer | Results per page | Optional. Minimum `1`, capped at maximum `50`. | `15` |

### Example Request

```http
GET /api/products/search?q=laptop&category_id=2&min_price=500&max_price=2500&in_stock=1&sort_by=price&sort_order=asc&per_page=15 HTTP/1.1
Host: localhost:8000
Accept: application/json
```

---

## 4. Installation and Setup

### Prerequisites

- **PHP**: `^8.3` (with `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype` extensions)
- **Composer**: `^2.x`
- **Database**: MySQL 8.0+ or MariaDB 10.4+ (InnoDB engine supporting FULLTEXT indexes)

### Step-by-Step Setup

1. **Clone the repository**:
   ```bash
   git clone <repository-url> swiftfind
   cd swiftfind
   ```

2. **Install PHP dependencies**:
   ```bash
   composer install
   ```

3. **Configure Environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Set Database Credentials**:
   Update `.env` with your database details:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=swiftfind
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

5. **Run Migrations**:
   Create tables and database indexes:
   ```bash
   php artisan migrate
   ```

6. **Seed Sample Data**:
   The repository includes seeders for categories and products. To populate the database with sample data:
   ```bash
   php artisan db:seed
   ```
   *(Note: `ProductSeeder` generates sample categories and chunked product entries for testing queries and indexes).*

7. **Start Development Server**:
   ```bash
   php artisan serve
   ```
   The API will be available at `http://127.0.0.1:8000`.

---

## 5. API Usage Example

### Endpoint
```http
GET /api/products/search
```

### Sample Request
```bash
curl -X GET "http://127.0.0.1:8000/api/products/search?q=Wireless&min_price=50&max_price=300&in_stock=1&sort_by=price&sort_order=asc&per_page=2" \
     -H "Accept: application/json"
```

### Sample Response (`200 OK`)
```json
{
  "data": [
    {
      "id": 1420,
      "name": "Wireless Headphones Pro",
      "description": "A durable device designed for gaming, office work, and daily efficiency.",
      "price": 89.99,
      "category_id": 1,
      "in_stock": true,
      "stock_quantity": 45,
      "created_at": "2026-05-18 10:30:00"
    },
    {
      "id": 853,
      "name": "Wireless Charging Stand",
      "description": "A sleek device designed for gaming, office work, and daily efficiency.",
      "price": 120.00,
      "category_id": 1,
      "in_stock": true,
      "stock_quantity": 12,
      "created_at": "2026-07-02 14:15:22"
    }
  ],
  "links": {
    "first": "http://127.0.0.1:8000/api/products/search?page=1",
    "last": "http://127.0.0.1:8000/api/products/search?page=5",
    "prev": null,
    "next": "http://127.0.0.1:8000/api/products/search?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "links": [
      {
        "url": null,
        "label": "&laquo; Previous",
        "active": false
      },
      {
        "url": "http://127.0.0.1:8000/api/products/search?page=1",
        "label": "1",
        "active": true
      },
      {
        "url": "http://127.0.0.1:8000/api/products/search?page=2",
        "label": "2",
        "active": false
      },
      {
        "url": "http://127.0.0.1:8000/api/products/search?page=2",
        "label": "Next &raquo;",
        "active": false
      }
    ],
    "path": "http://127.0.0.1:8000/api/products/search",
    "per_page": 2,
    "to": 2,
    "total": 10
  }
}
```

### Response Notes
- `data`: List of products transformed via `ProductResource`.
- `in_stock`: Computed boolean attribute derived from `stock_quantity > 0`.
- `meta` & `links`: Standard Laravel pagination metadata providing navigation URLs and total counts.

---

## 6. Running Tests

Automated tests are written with Pest PHP and Laravel's Feature testing tools.

To run the test suite:
```bash
php artisan test
```
Or run only the feature suite:
```bash
php artisan test --testsuite=Feature
```

### Test Suite Coverage
The test suite (`tests/Feature/ProductSearchTest.php`) contains 14 automated tests with 54 assertions verifying:
- Full-text keyword searches across product names and descriptions.
- Description-only matching.
- Minimum and maximum price boundary filtering.
- Category filtering and inventory status filtering (`in_stock=1` and `in_stock=0`).
- Simultaneous combined filters.
- Ascending and descending sorting on price and date.
- Validation rules (`min_price > max_price`, invalid category, invalid sort fields).
- Negative and zero page parameter boundaries (`page=0`, `page=-1`).
- Per-page clamping and enforcement of the 50-item limit.
- Maximum query string length validation (`q` > 100 characters).
- Empty query parameters and zero-match responses.
