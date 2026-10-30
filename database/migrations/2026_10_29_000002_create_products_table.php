<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('description', 500)->nullable();
            $table->decimal('price', 12, 2);
            $table->foreignId('currency_id')->index()->constrained()->restrictOnDelete();
            $table->decimal('tax_cost', 12, 2)->default(0);
            $table->decimal('manufacturing_cost', 12, 2)->default(0);
            $table->timestamps();

            $table->index('price');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
