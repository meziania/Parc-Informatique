<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $logs = AuditLog::query()
            ->with(['actor:id,name,email'])
            ->when(
                $request->string('action')->trim()->value(),
                fn ($query, string $action) => $query->where('action', $action)
            )
            ->when(
                $request->integer('actor_id') ?: null,
                fn ($query, int $actorId) => $query->where('actor_id', $actorId)
            )
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'action_label' => $this->actionLabel($log->action),
                'actor' => $log->actor
                    ? ['id' => $log->actor->id, 'name' => $log->actor->name, 'email' => $log->actor->email]
                    : null,
                'subject_type' => $log->subject_type ? class_basename($log->subject_type) : null,
                'subject_id' => $log->subject_id,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'ip' => $log->ip,
                'created_at' => $log->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'filters' => [
                'action' => $request->string('action')->value() ?: null,
                'actor_id' => $request->integer('actor_id') ?: null,
            ],
            'actions' => collect([
                'user.created' => 'Création de compte',
                'user.updated' => 'Modification de compte',
                'user.role_changed' => 'Changement de rôle',
                'user.activated' => 'Activation',
                'user.deactivated' => 'Désactivation',
                'user.password_reset' => 'Réinit. mot de passe',
            ])->map(fn (string $label, string $value) => [
                'value' => $value,
                'label' => $label,
            ])->values()->all(),
            'actors' => User::query()
                ->where('role', 'admin')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $user) => ['value' => (string) $user->id, 'label' => $user->name]),
        ]);
    }

    private function actionLabel(string $action): string
    {
        return match ($action) {
            'user.created' => 'Création de compte',
            'user.updated' => 'Modification de compte',
            'user.role_changed' => 'Changement de rôle',
            'user.activated' => 'Activation',
            'user.deactivated' => 'Désactivation',
            'user.password_reset' => 'Réinit. mot de passe',
            default => $action,
        };
    }
}
