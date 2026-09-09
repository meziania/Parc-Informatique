<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('entity_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('reference')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency', 3)->default('MAD');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('entity_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('asset_contract', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->timestamp('linked_at')->useCurrent();
            $table->unique(['contract_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_contract');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('suppliers');
    }
};
