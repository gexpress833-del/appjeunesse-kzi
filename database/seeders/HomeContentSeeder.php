<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HomeContentSeeder extends Seeder
{
    /**
     * Seed the application's database with the landing page contents.
     */
    public function run(): void
    {
        $contents = [
            [
                'type' => 'verset',
                'title' => 'Le Verset du Jour',
                'content' => 'Souvenez-vous de votre Créateur pendant les jours de votre jeunesse, avant que les jours fâcheux ne viennent.',
                'author_or_reference' => 'Ecclésiaste 12:1',
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'type' => 'verset',
                'title' => 'Le Verset du Jour',
                'content' => 'Que personne ne méprise ta jeunesse, mais sois un modèle pour les fidèles, en parole, en conduite, en amour, en foi, en pureté.',
                'author_or_reference' => '1 Timothée 4:12',
                'is_active' => true,
                'display_order' => 2,
            ],
            [
                'type' => 'temoignage',
                'title' => 'Un Dieu qui fidélise',
                'content' => 'Depuis que j\'ai rejoint la jeunesse de La Parole Éternelle, ma vie de prière a été transformée. Le Seigneur m\'a ouvert des portes que je croyais fermées pour toujours.',
                'author_or_reference' => 'Gracia, Département Chorale',
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'type' => 'live_stream',
                'title' => 'Culte en direct',
                'content' => 'Rejoignez le culte de la jeunesse chaque samedi à partir de 16h30.',
                'author_or_reference' => null,
                'media_url' => null,
                'is_active' => false,
                'display_order' => 1,
            ],
        ];

        foreach ($contents as $content) {
            DB::table('home_contents')->updateOrInsert(
                ['type' => $content['type'], 'author_or_reference' => $content['author_or_reference']],
                array_merge($content, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
