<x-layouts.app>
    <div class="container-app max-w-4xl py-12">
        <h1 class="text-3xl font-bold tracking-tight text-ink-900">Comment ca marche</h1>
        <p class="mt-3 text-ink-600">
            AutoAlert centralise les annonces et surveille le marche pour vous. {{ number_format($stats['vehicles']) }}
            vehicules sont actuellement disponibles.
        </p>

        <div class="mt-10 space-y-10">
            @foreach ([
                ['1. Cherchez dans le catalogue', 'Filtrez par marque, prix, annee, kilometrage, carburant, boite, carrosserie et ville. Chaque carte renvoie vers l annonce originale chez le vendeur.'],
                ['2. Creez une alerte', 'Cliquez sur « Creer une alerte » et decrivez votre recherche : marque, modele, budget maximum, kilometrage, type de carburant. Vous pouvez creer autant d alertes que vous voulez.'],
                ['3. Recevez les correspondances', 'Des qu un vehicule correspond, une notification apparait dans votre tableau de bord. Selon vos preferences, vous la recevez aussi par email, ou dans un resume quotidien ou hebdomadaire.'],
                ['4.suivez vos favoris', 'Enregistrez les vehicules qui vous interessent pour les retrouver d un clic, meme apres plusieurs semaines de recherche.'],
            ] as [$title, $text])
                <div>
                    <h2 class="text-lg font-semibold text-ink-900">{{ $title }}</h2>
                    <p class="mt-2 text-ink-600">{{ $text }}</p>
                </div>
            @endforeach
        </div>

        <div class="card mt-12 p-6">
            <h2 class="font-semibold text-ink-900">Questions frequentes</h2>
            <dl class="mt-4 space-y-5">
                @foreach ([
                    ['Les prix sont-ils fiables ?', 'AutoAlert affiche les informations publiees par la source. Le prix definitif se confirme aupres du vendeur : utilisez le bouton « Voir l annonce originale ».'],
                    ['Comment fonctionne la detection de doublons ?', 'Lors d un import, le systeme compare l URL et les caracteristiques du vehicule aux annonces existantes et vous alerte avant de creer une fiche.'],
                    ['Puis-je arreter de recevoir des notifications ?', 'Oui : desactivez une alerte ou modifiez vos canaux de notification depuis la page Preferences.'],
                    ['Les annonces sont-elles achetees sur AutoAlert ?', 'Non. AutoAlert centralise et transmet : la transaction se fait directement avec le vendeur.'],
                ] as [$question, $answer])
                    <div>
                        <dt class="font-medium text-ink-900">{{ $question }}</dt>
                        <dd class="mt-1 text-sm text-ink-600">{{ $answer }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
</x-layouts.app>
