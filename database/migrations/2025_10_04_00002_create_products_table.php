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
            $table->integer('warranty_months')->default(0);
            $table->boolean('serial_tracking')->default(false);
            $table->text('short_description')->nullable();
            $table->text('long_description')->nullable();
            
            $table->integer('stock')->default(0);

            $table->text('snippet_description')->nullable();

            $table->decimal('cost', 14, 2)->default(0);
            $table->decimal('price', 14, 2)->default(0);
            $table->decimal('offer_price', 14, 2)->nullable();
            
            $table->string('product_status', 255)->nullable();

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