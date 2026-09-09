<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_installed_softwares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('vendor')->nullable();
            $table->string('version')->nullable();
            $table->foreignId('software_license_id')->nullable()->constrained()->nullOnDelete();
            $table->date('installed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_installed_softwares');
    }
};
