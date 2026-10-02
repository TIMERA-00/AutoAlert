<x-layouts.app>
    <div class="container-app max-w-3xl py-12">
        <h1 class="text-3xl font-bold tracking-tight text-ink-900">Contact</h1>
        <p class="mt-3 text-ink-600">
            Une question sur une annonce, une source a proposer, un probleme technique ? Ecrivez nous.
        </p>

        <div class="card mt-8 space-y-5 p-6">
            @auth
                <form method="POST" action="{{ route('contact.store') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label class="label" for="subject">Objet</label>
                        <input id="subject" name="subject" required class="input" placeholder="Objet de votre message">
                    </div>
                    <div>
                        <label class="label" for="message">Message</label>
                        <textarea id="message" name="message" rows="6" required class="input"></textarea>
                    </div>
                    <button type="submit" class="btn-primary">Envoyer le message</button>
                </form>
            @else
                <p class="text-sm text-ink-600">
                    <a href="{{ route('login') }}" class="font-medium text-brand-700 hover:underline">Connectez-vous</a>
                    pour nous ecrire, ou utilisez l adresse email directement.
                </p>
            @endauth
        </div>

        <div class="mt-6 text-sm text-ink-500">
            <p>Email : contact@autoalert.dev</p>
            <p class="mt-1">Telephone : +221 00 000 00 00</p>
        </div>
    </div>
</x-layouts.app>
