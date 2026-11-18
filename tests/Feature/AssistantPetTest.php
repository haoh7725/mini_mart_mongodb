<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AssistantPet;
use Database\Seeders\DatabaseSeeder;
use Tests\Concerns\RefreshMongoDatabase;
use Tests\TestCase;

class AssistantPetTest extends TestCase
{
    use RefreshMongoDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_guests_see_the_default_pet(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('pet-'.AssistantPet::DEFAULT, false)
            ->assertSee(AssistantPet::PETS[AssistantPet::DEFAULT]['greeting']);
    }

    public function test_customer_picks_a_pet_in_profile_and_it_follows_them(): void
    {
        $customer = User::where('email', 'customer@minimart.test')->firstOrFail();

        $this->actingAs($customer)->get(route('account.profile'))->assertSee('Bạn đồng hành')->assertSee('Cà Rốt Cam');
        $this->patch(route('account.pet.update'), ['assistant_pet' => 'carrot'])->assertSessionHasNoErrors();
        $this->assertSame('carrot', $customer->fresh()->assistant_pet);

        $this->get(route('home'))->assertSee('ai-pet-buddy"><svg class="veggie-pet pet-carrot"', false)->assertSee('Hôm nay nấu gì ngon nè?');
        $this->patch(route('account.pet.update'), ['assistant_pet' => 'pizza'])->assertSessionHasErrors('assistant_pet');
        $this->assertSame('carrot', $customer->fresh()->assistant_pet);
    }
}
