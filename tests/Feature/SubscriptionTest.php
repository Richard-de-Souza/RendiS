<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar perfil básico
        $role = Role::create(['name' => 'User', 'slug' => 'user']);
        
        // Criar usuário de teste
        $this->user = User::factory()->create([
            'role_id' => $role->id,
            'salary' => 5000,
        ]);
    }

    public function test_user_can_view_subscriptions_index()
    {
        $response = $this->actingAs($this->user)->get(route('subscriptions.index'));

        $response->assertStatus(200);
        $response->assertViewIs('subscriptions.index');
    }

    public function test_user_can_create_subscription()
    {
        $subscriptionData = [
            'description' => 'Netflix',
            'amount' => 55.90,
            'due_day' => 10,
            'category' => 'Streaming',
            'type' => 'subscription',
            'start_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)->post(route('subscriptions.store'), $subscriptionData);

        $response->assertRedirect(route('subscriptions.index'));
        $response->assertSessionHas('success', 'Mensalidade cadastrada com sucesso!');
        $this->assertDatabaseHas('subscriptions', [
            'description' => 'Netflix',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_update_subscription()
    {
        $subscription = Subscription::create([
            'user_id' => $this->user->id,
            'description' => 'Internet Antiga',
            'amount' => 100,
            'due_day' => 5,
            'category' => 'Serviços',
            'is_indefinite' => true,
            'start_date' => now(),
            'status' => 'active'
        ]);

        $updateData = [
            'description' => 'Internet Fibra',
            'amount' => 150,
            'due_day' => 15,
            'category' => 'Serviços',
            'type' => 'subscription',
            'start_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)->put(route('subscriptions.update', $subscription), $updateData);

        $response->assertRedirect(route('subscriptions.index'));
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'description' => 'Internet Fibra',
            'amount' => 150,
        ]);
    }

    public function test_user_can_delete_subscription()
    {
        $subscription = Subscription::create([
            'user_id' => $this->user->id,
            'description' => 'Spotify',
            'amount' => 20,
            'due_day' => 1,
            'category' => 'Streaming',
            'is_indefinite' => true,
            'start_date' => now(),
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)->delete(route('subscriptions.destroy', $subscription));

        $response->assertRedirect(route('subscriptions.index'));
        $this->assertDatabaseMissing('subscriptions', ['id' => $subscription->id]);
    }
}
