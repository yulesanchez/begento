<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('currency_id')->index()->constrained()->restrictOnDelete();
            $table->decimal('price', 12, 2);
            $table->decimal('rate_used', 16, 6)->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'currency_id']);
            $table->index(['product_id', 'price']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
