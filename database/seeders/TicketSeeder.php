<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Models\Asset;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $technicien = User::where('email', 'technicien@parc.local')->first();
        $utilisateur = User::where('email', 'utilisateur@parc.local')->first();

        if (! $technicien || ! $utilisateur) {
            return;
        }

        $pcUtilisateur = Asset::where('inventory_number', 'INV-0001')->first();
        $imprimante = Asset::where('inventory_number', 'INV-0004')->first();

        $panne = Ticket::firstOrCreate(
            ['title' => 'Le PC ne démarre plus'],
            [
                'description' => "Depuis ce matin, mon poste affiche un écran noir au démarrage. Le ventilateur tourne mais rien ne s'affiche, même en branchant un autre écran.",
                'type' => TicketType::Incident,
                'priority' => TicketPriority::High,
                'status' => TicketStatus::InProgress,
                'requester_id' => $utilisateur->id,
                'assignee_id' => $technicien->id,
                'asset_id' => $pcUtilisateur?->id,
                'entity_id' => $utilisateur->entity_id,
            ]
        );

        if ($panne->wasRecentlyCreated) {
            $panne->comments()->create([
                'user_id' => $technicien->id,
                'body' => 'Je passe en salle 102 cet après-midi pour tester avec une autre alimentation. En attendant, ne touchez pas au poste.',
            ]);
            $panne->comments()->create([
                'user_id' => $utilisateur->id,
                'body' => "D'accord, merci. J'ai des fichiers importants dessus, j'espère que le disque n'est pas mort…",
            ]);
        }

        Ticket::firstOrCreate(
            ['title' => 'Demande d\'accès au VPN'],
            [
                'description' => "Je pars en déplacement la semaine prochaine et j'aurai besoin d'un accès VPN pour consulter mes mails et le serveur de fichiers.",
                'type' => TicketType::Request,
                'priority' => TicketPriority::Medium,
                'requester_id' => $utilisateur->id,
                'entity_id' => $utilisateur->entity_id,
            ]
        );

        Ticket::firstOrCreate(
            ['title' => 'Bourrages papier répétés sur l\'imprimante du bâtiment A'],
            [
                'description' => 'L\'imprimante HP du couloir bourre au moins une fois par jour. Le papier semble mal entraîné, surtout avec des feuilles 80g.',
                'type' => TicketType::Incident,
                'priority' => TicketPriority::Low,
                'status' => TicketStatus::Resolved,
                'requester_id' => $utilisateur->id,
                'assignee_id' => $technicien->id,
                'asset_id' => $imprimante?->id,
                'entity_id' => $utilisateur->entity_id,
                'solution' => 'Rouleaux d\'entraînement nettoyés et bac papier réajusté. Test de 50 pages sans bourrage. À surveiller, remplacement des rouleaux prévu au prochain passage du fournisseur.',
                'resolved_at' => now()->subDays(2),
            ]
        );

        Ticket::firstOrCreate(
            ['title' => 'Demande d\'un second écran'],
            [
                'description' => 'Je travaille beaucoup sur des tableaux croisés, un second écran me ferait gagner beaucoup de temps.',
                'type' => TicketType::Request,
                'priority' => TicketPriority::Medium,
                'status' => TicketStatus::Assigned,
                'requester_id' => $utilisateur->id,
                'assignee_id' => $technicien->id,
                'entity_id' => $utilisateur->entity_id,
            ]
        );
    }
}
