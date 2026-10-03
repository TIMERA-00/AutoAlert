<?php

namespace Tests\Feature;

use App\Enums\BodyType;
use App\Enums\ChatRole;
use App\Livewire\Assistant\ChatWidget;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Assistant\AssistantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class AssistantTest extends TestCase
{
    use RefreshDatabase;

    private function widget(): Testable
    {
        return Livewire::test(ChatWidget::class);
    }

    /**
     * A real, decodable image. UploadedFile::fake()->image() would need the GD
     * extension, which the CI image does not always ship.
     */
    private function photo(string $name = 'voiture.jpg'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(self::JPEG_1PX));
    }

    private const JPEG_1PX = '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0a'
        .'HBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCAABAAEBAREA/8QAFAABAAAAAAAAAAAAAAAAAAAACf/E'
        .'ABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AKp//2Q==';

    // ------------------------------------------------------------- catalogue

    public function test_widget_is_present_on_public_pages(): void
    {
        Vehicle::factory()->published()->create();

        $this->get('/')->assertOk()->assertSee('Assistant AutoAlert', false);
        $this->get('/vehicles')->assertOk()->assertSee('Assistant AutoAlert', false);
    }

    public function test_it_greets_and_offers_starter_questions(): void
    {
        Vehicle::factory()->published()->create(['brand' => 'Toyota', 'price' => 10_000_000]);

        $this->widget()
            ->assertSet('open', '')
            ->assertSee('Que voulez-vous chercher ?')
            ->call('send')
            ->assertSet('notice', null);
    }

    public function test_it_finds_vehicles_from_a_brand_and_budget(): void
    {
        Vehicle::factory()->published()->create(['brand' => 'Toyota', 'model' => 'RAV4', 'price' => 12_000_000, 'mileage' => 60_000]);
        Vehicle::factory()->published()->create(['brand' => 'Renault', 'model' => 'Clio', 'price' => 30_000_000]);

        $component = $this->widget()->set('message', 'un Toyota sous 15 millions')->call('send');

        $last = $component->instance()->transcript()->last();

        $this->assertSame(ChatRole::Assistant, $last->role);
        $this->assertStringContainsString('RAV4', $last->content);
        $this->assertStringNotContainsString('Clio', $last->content);
        $this->assertNotEmpty($last->vehicle_ids);
    }

    public function test_it_admits_when_the_catalogue_has_no_match(): void
    {
        Vehicle::factory()->published()->create(['brand' => 'Toyota', 'price' => 30_000_000]);

        $component = $this->widget()->set('message', 'une Ferrari sous 2 millions')->call('send');

        $this->assertStringContainsString(
            "je n'ai rien",
            mb_strtolower($component->instance()->transcript()->last()->content)
        );
    }

    public function test_it_keeps_criteria_established_earlier_in_the_conversation(): void
    {
        Vehicle::factory()->published()->create([
            'brand' => 'Toyota', 'model' => 'RAV4', 'price' => 12_000_000,
            'body_type' => BodyType::Suv,
        ]);

        $component = $this->widget()
            ->set('message', 'un Toyota')->call('send')
            ->set('message', 'maximum 15 millions')->call('send');

        $this->assertStringContainsString('RAV4', $component->instance()->transcript()->last()->content);
    }

    public function test_it_proposes_an_alert_and_the_visitor_can_accept_it(): void
    {
        $user = User::factory()->create();
        Vehicle::factory()->count(3)->published()->create(['brand' => 'Toyota', 'price' => 12_000_000]);

        $component = Livewire::actingAs($user)
            ->test(ChatWidget::class)
            ->set('message', 'un Toyota sous 20 millions')
            ->call('send');

        $this->assertDatabaseCount('alerts', 0);

        $component->call('acceptDraft')->assertHasNoErrors();

        $this->assertDatabaseCount('alerts', 1);
        $this->assertDatabaseHas('alerts', ['user_id' => $user->id, 'brand' => 'Toyota']);
    }

    public function test_a_guest_is_sent_to_login_with_a_prefilled_form(): void
    {
        Vehicle::factory()->count(2)->published()->create(['brand' => 'Toyota', 'price' => 12_000_000]);

        $component = $this->widget()
            ->set('message', 'un Toyota sous 20 millions')
            ->call('send');

        $component->call('acceptDraft');

        $this->assertSame('Toyota', session('alert_prefill')['brand'] ?? null);
        $this->get('/login')->assertOk();
    }

    // ----------------------------------------------------------------- photo

    public function test_a_photo_without_api_key_asks_for_the_model_instead_of_guessing(): void
    {
        config(['assistant.key' => null, 'assistant.driver' => 'rules']);

        $component = $this->widget()
            ->set('photo', $this->photo())
            ->call('send');

        $last = $component->instance()->transcript()->last();

        $this->assertStringContainsString('OPENAI_API_KEY', $last->content);
        $this->assertSame('unavailable', $last->attachments['identification']['source']);
        $this->assertNull($last->attachments['identification']['brand']);
    }

    public function test_a_photo_is_answered_by_the_vision_model_when_configured(): void
    {
        config(['assistant.key' => 'test-key', 'assistant.driver' => 'openai']);

        Http::fake([
            '*/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'brand' => 'Toyota',
                            'model' => 'RAV4',
                            'year' => 2019,
                            'body_type' => 'SUV',
                            'fuel' => 'PETROL',
                            'transmission' => 'AUTOMATIC',
                            'confidence' => 0.82,
                        ]),
                    ],
                ]],
            ]),
        ]);

        Vehicle::factory()->published()->create([
            'brand' => 'Toyota', 'model' => 'RAV4', 'price' => 11_000_000,
            'body_type' => BodyType::Suv,
        ]);

        $component = $this->widget()
            ->set('photo', $this->photo())
            ->call('send');

        $last = $component->instance()->transcript()->last();

        $this->assertSame('vision', $last->attachments['identification']['source']);
        $this->assertSame('Toyota', $last->attachments['identification']['brand']);
        $this->assertSame('RAV4', $last->attachments['identification']['model']);
        $this->assertNotEmpty($last->vehicle_ids);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/chat/completions'));
    }

    public function test_a_photo_is_rejected_when_it_is_not_an_image(): void
    {
        // Livewire only previews known mimes; a pdf it cannot preview is
        // exactly the kind of file the "image" rule has to reject.
        config(['livewire.temporary_file_upload.preview_mimes' => ['png', 'jpg', 'jpeg', 'pdf']]);

        $this->widget()
            ->set('photo', UploadedFile::fake()->create('note.pdf', 10, 'application/pdf'))
            ->call('send')
            ->assertHasErrors('photo');
    }

    // --------------------------------------------------------- moteur OpenAI

    public function test_the_llm_can_only_reach_the_catalogue_through_the_tool(): void
    {
        config(['assistant.key' => 'test-key', 'assistant.driver' => 'openai']);

        Vehicle::factory()->published()->create(['brand' => 'Toyota', 'model' => 'RAV4', 'price' => 12_000_000]);

        Http::fakeSequence()
            ->push(['choices' => [['message' => [
                'role' => 'assistant',
                'content' => null,
                'tool_calls' => [[
                    'id' => 'call_1',
                    'type' => 'function',
                    'function' => ['name' => 'search_vehicles', 'arguments' => json_encode(['brand' => 'Toyota'])],
                ]],
            ]]]])
            ->push(['choices' => [['message' => [
                'role' => 'assistant',
                'content' => "J'ai trouve un Toyota RAV4 a 12 000 000 FCFA.",
            ]]]]);

        $component = $this->widget()
            ->set('message', 'cherche un Toyota')
            ->call('send');

        $last = $component->instance()->transcript()->last();

        $this->assertSame('openai', $last->engine);
        $this->assertStringContainsString('RAV4', $last->content);
        $this->assertNotEmpty($last->vehicle_ids);
    }

    public function test_an_api_failure_falls_back_to_the_rules_engine(): void
    {
        config(['assistant.key' => 'test-key', 'assistant.driver' => 'openai']);
        Http::fake(['*' => Http::response('rate limited', 429)]);

        Vehicle::factory()->published()->create(['brand' => 'Toyota', 'model' => 'RAV4', 'price' => 12_000_000]);

        $component = $this->widget()
            ->set('message', 'un Toyota sous 20 millions')
            ->call('send');

        $last = $component->instance()->transcript()->last();

        $this->assertSame('rules-fallback', $last->engine);
        $this->assertStringContainsString('RAV4', $last->content);
    }

    // ---------------------------------------------------------- conversations

    public function test_a_visitor_gets_one_resumable_conversation(): void
    {
        Vehicle::factory()->published()->create(['brand' => 'Toyota']);

        $this->widget()->set('message', 'bonjour')->call('send');
        $this->widget()->set('message', 'un Toyota')->call('send');

        $this->assertDatabaseCount('chat_conversations', 1);
        $this->assertSame(4, ChatMessage::count());
    }

    public function test_a_signed_in_visitor_keeps_their_conversation_after_login(): void
    {
        $user = User::factory()->create();
        Vehicle::factory()->published()->create(['brand' => 'Toyota']);

        Livewire::test(ChatWidget::class)
            ->set('message', 'un Toyota')
            ->call('send');

        $conversationId = ChatMessage::query()->value('chat_conversation_id');

        $adopted = app(AssistantService::class)->adoptFor($user);

        $this->assertSame($conversationId, $adopted->id);
        $this->assertSame($user->id, $adopted->user_id);
    }

    public function test_restart_closes_the_previous_conversation(): void
    {
        $this->widget()->set('message', 'bonjour')->call('send');

        $first = ChatMessage::query()->value('chat_conversation_id');

        $this->widget()->call('restart');

        $this->assertSame(ChatConversation::STATUS_RESOLVED, ChatConversation::find($first)->status);
    }

    public function test_a_deep_link_answers_immediately_instead_of_only_prefilling(): void
    {
        Vehicle::factory()->published()->create(['brand' => 'Toyota', 'model' => 'RAV4', 'price' => 12_000_000]);

        $this->get('/?assistant=1&ask=un+Toyota+sous+15+millions')
            ->assertOk()
            ->assertSee('RAV4', false);

        $this->assertDatabaseHas('chat_messages', [
            'role' => ChatRole::User,
            'content' => 'un Toyota sous 15 millions',
        ]);
    }

    public function test_the_composer_is_complete_without_a_photo_selected(): void
    {
        // A stray Blade conditional once swallowed the microphone button: it
        // rendered only once a photo had been picked.
        $this->widget()
            ->assertSee('Parler', false)
            ->assertSee('Envoyer une photo', false)
            ->assertSee('Envoyer', false);
    }

    public function test_empty_messages_are_not_sent(): void
    {
        $this->widget()->call('send');

        $this->assertDatabaseCount('chat_messages', 0);
    }
}
