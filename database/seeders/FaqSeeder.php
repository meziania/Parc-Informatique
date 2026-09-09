<?php

namespace Database\Seeders;

use App\Enums\FaqCategory;
use App\Models\FaqArticle;
use App\Models\User;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::where('email', 'technicien@parc.local')->first()
            ?? User::where('email', 'admin@parc.local')->first();

        $articles = [
            [
                'title' => 'Comment réinitialiser mon mot de passe ?',
                'category' => FaqCategory::Account,
                'body' => "1. Sur la page de connexion, cliquez sur « Mot de passe oublié ».\n2. Saisissez votre adresse e-mail professionnelle.\n3. Ouvrez le lien reçu par e-mail (valable 60 minutes).\n4. Choisissez un nouveau mot de passe d'au moins 8 caractères.\n\nSi vous ne recevez pas l'e-mail, vérifiez vos spams ou contactez le support via un ticket.",
            ],
            [
                'title' => 'Mon PC ne démarre plus : que faire avant d\'ouvrir un ticket ?',
                'category' => FaqCategory::Hardware,
                'body' => "Avant d'ouvrir un ticket, essayez ces vérifications rapides :\n\n- Vérifiez que l'alimentation et l'écran sont bien branchés.\n- Essayez un autre câble HDMI/DisplayPort si l'écran reste noir.\n- Maintenez le bouton d'alimentation 10 secondes pour forcer l'extinction, puis rallumez.\n- Notez tout message d'erreur ou bip.\n\nSi rien ne fonctionne, créez un ticket de type Incident et liez votre équipement.",
            ],
            [
                'title' => 'Comment demander un accès VPN ?',
                'category' => FaqCategory::Network,
                'body' => "1. Ouvrez un ticket de type Demande.\n2. Indiquez la période de déplacement et le motif.\n3. Précisez si vous avez déjà un client VPN installé.\n\nLe support valide la demande puis vous envoie les instructions de configuration. Comptez en général 1 à 2 jours ouvrés.",
            ],
            [
                'title' => 'Installer un logiciel non standard',
                'category' => FaqCategory::Software,
                'body' => "Les logiciels hors catalogue doivent être validés par l'IT (licences, sécurité).\n\nOuvrez une Demande en précisant :\n- le nom exact du logiciel et l'éditeur ;\n- le motif métier ;\n- le poste concerné.\n\nN'installez pas de logiciels depuis des sources non officielles.",
            ],
            [
                'title' => 'Que faire en cas d\'imprimante qui bourre ?',
                'category' => FaqCategory::Hardware,
                'body' => "1. Ouvrez le capot et retirez délicatement le papier coincé (dans le sens du trajet papier).\n2. Vérifiez qu'aucun fragment ne reste dans les rouleaux.\n3. Réajustez le bac papier et utilisez du papier 80g si possible.\n4. Relancez une page de test.\n\nSi le problème revient souvent, ouvrez un ticket Incident lié à l'imprimante du lieu.",
            ],
        ];

        foreach ($articles as $article) {
            FaqArticle::firstOrCreate(
                ['title' => $article['title']],
                [
                    ...$article,
                    'is_published' => true,
                    'author_id' => $author?->id,
                ]
            );
        }
    }
}
