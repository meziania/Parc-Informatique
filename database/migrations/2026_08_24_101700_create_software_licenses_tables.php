<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('software_licenses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('vendor')->nullable();
            $table->string('product_key')->nullable();
            $table->unsignedInteger('seats')->default(1);
            $table->date('purchase_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('entity_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('asset_license', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_license_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->unique(['software_license_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_license');
        Schema::dropIfExists('software_licenses');
    }
};
