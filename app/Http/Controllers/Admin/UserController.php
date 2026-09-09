<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Entity;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->with('entity:id,name')
            ->withCount([
                'assignedTickets as open_tickets_count' => fn ($query) => $query->whereIn('status', [
                    TicketStatus::New->value,
                    TicketStatus::Assigned->value,
                    TicketStatus::InProgress->value,
                ]),
            ])
            ->when(
                $request->string('search')->trim()->value(),
                function ($query, string $search) {
                    $query->where(function ($sub) use ($search) {
                        $sub->where('name', 'ilike', "%{$search}%")
                            ->orWhere('email', 'ilike', "%{$search}%");
                    });
                }
            )
            ->when(
                $request->string('role')->value(),
                fn ($query, string $role) => $query->where('role', $role)
            )
            ->when(
                $request->has('active') && $request->string('active')->value() !== '',
                fn ($query) => $query->where('is_active', $request->boolean('active'))
            )
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'entity_id', 'is_active', 'created_at']);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $this->roleOptions(),
            'filters' => [
                'search' => $request->string('search')->value() ?: null,
                'role' => $request->string('role')->value() ?: null,
                'active' => $request->has('active') && $request->string('active')->value() !== ''
                    ? ($request->boolean('active') ? '1' : '0')
                    : null,
            ],
            'generatedPassword' => $request->session()->get('generated_password'),
            'flashStatus' => $request->session()->get('status'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Form', [
            'user' => null,
            ...$this->formData(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $validated = $this->validated($request);
        $plainPassword = $validated['password'] ?? Str::password(12);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'entity_id' => $validated['entity_id'],
            'is_active' => $validated['is_active'],
            'password' => $plainPassword,
            'email_verified_at' => now(),
        ]);

        $audit->log($request->user(), 'user.created', $user, null, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'entity_id' => $user->entity_id,
            'is_active' => $user->is_active,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Compte créé pour {$user->name}.")
            ->with('generated_password', $plainPassword);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Form', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'entity_id' => $user->entity_id,
                'is_active' => $user->is_active,
            ],
            ...$this->formData(),
        ]);
    }

    public function update(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $validated = $this->validated($request, $user);
        $actor = $request->user();

        abort_if(
            $actor->is($user) && ! $validated['is_active'],
            403,
            'Vous ne pouvez pas désactiver votre propre compte.'
        );

        $this->guardLastAdmin($user, UserRole::from($validated['role']), $validated['is_active']);

        $previous = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'entity_id' => $user->entity_id,
            'is_active' => $user->is_active,
        ];

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'entity_id' => $validated['entity_id'],
            'is_active' => $validated['is_active'],
        ]);

        $next = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'entity_id' => $user->entity_id,
            'is_active' => $user->is_active,
        ];

        if ($previous['role'] !== $next['role']) {
            $audit->log($actor, 'user.role_changed', $user, ['role' => $previous['role']], ['role' => $next['role']]);
        }

        if ($previous['is_active'] !== $next['is_active']) {
            $audit->log(
                $actor,
                $next['is_active'] ? 'user.activated' : 'user.deactivated',
                $user,
                ['is_active' => $previous['is_active']],
                ['is_active' => $next['is_active']],
            );
        }

        $profileChanged = $previous['name'] !== $next['name']
            || $previous['email'] !== $next['email']
            || $previous['entity_id'] !== $next['entity_id'];

        if ($profileChanged) {
            $audit->log($actor, 'user.updated', $user, $previous, $next);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Compte « {$user->name} » mis à jour.");
    }

    public function toggleActive(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $actor = $request->user();
        abort_if($actor->is($user), 403, 'Vous ne pouvez pas désactiver votre propre compte.');

        $willBeActive = ! $user->is_active;
        $this->guardLastAdmin($user, $user->role, $willBeActive);

        $user->update(['is_active' => $willBeActive]);

        $audit->log(
            $actor,
            $willBeActive ? 'user.activated' : 'user.deactivated',
            $user,
            ['is_active' => ! $willBeActive],
            ['is_active' => $willBeActive],
        );

        return back()->with(
            'status',
            $willBeActive
                ? "Compte « {$user->name} » réactivé."
                : "Compte « {$user->name} » désactivé."
        );
    }

    public function resetPassword(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $plainPassword = Str::password(12);

        $user->update(['password' => $plainPassword]);

        $audit->log($request->user(), 'user.password_reset', $user, null, [
            'email' => $user->email,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Mot de passe réinitialisé pour {$user->name}.")
            ->with('generated_password', $plainPassword);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $user = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user),
            ],
            'role' => ['required', Rule::enum(UserRole::class)],
            'entity_id' => ['required', 'exists:entities,id'],
            'is_active' => ['required', 'boolean'],
        ];

        if ($user === null) {
            $rules['password'] = ['nullable', 'string', 'min:8', 'max:255'];
        }

        $validated = $request->validate($rules);
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'roles' => $this->roleOptions(),
            'entities' => Entity::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Entity $entity) => [
                    'id' => $entity->id,
                    'name' => $entity->name,
                ]),
        ];
    }

    private function roleOptions(): array
    {
        return collect(UserRole::cases())
            ->map(fn (UserRole $role) => ['value' => $role->value, 'label' => $role->label()])
            ->all();
    }

    private function guardLastAdmin(User $user, UserRole $newRole, bool $willBeActive): void
    {
        if ($user->role !== UserRole::Admin) {
            return;
        }

        $losingAdmin = $newRole !== UserRole::Admin || ! $willBeActive;
        if (! $losingAdmin) {
            return;
        }

        $otherActiveAdmins = User::query()
            ->where('role', UserRole::Admin)
            ->where('is_active', true)
            ->whereKeyNot($user->id)
            ->exists();

        if (! $otherActiveAdmins) {
            throw ValidationException::withMessages([
                'role' => 'Impossible : il doit rester au moins un administrateur actif.',
            ]);
        }
    }
}
