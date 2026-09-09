<?php

use App\Enums\TicketPriority;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('due_at')->nullable()->after('closed_at')->index();
        });

        $hours = [
            TicketPriority::Urgent->value => 4,
            TicketPriority::High->value => 8,
            TicketPriority::Medium->value => 24,
            TicketPriority::Low->value => 72,
        ];

        foreach ($hours as $priority => $slaHours) {
            DB::table('tickets')
                ->where('priority', $priority)
                ->whereNull('due_at')
                ->update([
                    'due_at' => DB::raw("created_at + interval '{$slaHours} hours'"),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('due_at');
        });
    }
};
