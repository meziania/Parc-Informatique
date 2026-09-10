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
        if (! Schema::hasColumn('tickets', 'due_at')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->timestamp('due_at')->nullable()->after('closed_at')->index();
            });
        }

        $hours = [
            TicketPriority::Urgent->value => 4,
            TicketPriority::High->value => 8,
            TicketPriority::Medium->value => 24,
            TicketPriority::Low->value => 72,
        ];

        $driver = Schema::getConnection()->getDriverName();

        foreach ($hours as $priority => $slaHours) {
            $sql = $driver === 'pgsql'
                ? "created_at + interval '{$slaHours} hours'"
                : "datetime(created_at, '+{$slaHours} hours')";

            DB::table('tickets')
                ->where('priority', $priority)
                ->whereNull('due_at')
                ->update([
                    'due_at' => DB::raw($sql),
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tickets', 'due_at')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropColumn('due_at');
            });
        }
    }
};
