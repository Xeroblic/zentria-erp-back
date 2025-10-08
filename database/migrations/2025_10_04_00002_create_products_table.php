<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();

            $table->string('sku', 255);
            $table->string('commercial_sku', 255)->nullable();
            $table->string('barcode', 255)->nullable();
            $table->string('name', 255);

            $table->foreignId('brand_id')->constrained('brands');

            $table->string('product_type', 255)->default('device');
            $table->string('condition_policy', 255)->default('none');
            $table->boolean('serial_tracking')->default(false);
            $table->string('uom', 255)->default('unit');
            $table->integer('warranty_months')->default(0);

            $table->decimal('cost', 14, 2)->default(0);
            $table->decimal('price', 14, 2)->default(0);
            $table->decimal('offer_price', 14, 2)->nullable();

            // PostgreSQL: JSONB
            $table->jsonb('attributes_json')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // índices/únicos
            $table->unique(['branch_id','sku']);
            $table->index(['branch_id','name']);
        });

        // Índice GIN para búsquedas por atributos JSONB
        DB::statement('CREATE INDEX products_attributes_json_gin ON products USING GIN (attributes_json)');
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};