<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_catalog_items', function (Blueprint $table) {
            $table->boolean('requires_approval')->default(false)->after('is_active');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->string('approval_status', 20)->nullable()->after('status')->index();
            $table->foreignId('approved_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('approval_note')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['approval_status', 'approved_at', 'approval_note']);
        });

        Schema::table('service_catalog_items', function (Blueprint $table) {
            $table->dropColumn('requires_approval');
        });
    }
};
