<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->unsigned();
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->timestamps();

            $table->index(['category_id', 'stock_quantity', 'price'], 'idx_cat_stock_price');
            $table->index('created_at', 'idx_created_at');
            $table->index('price', 'idx_price');
        });

        DB::statement('ALTER TABLE products ADD FULLTEXT idx_fulltext_name_desc(name, description)');
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
