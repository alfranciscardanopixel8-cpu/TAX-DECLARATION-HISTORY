<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DossierTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_fetch_property_dossier(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ASSESSOR]));

        $property = Property::factory()->create();

        $this->getJson("/api/properties/{$property->id}/dossier")
            ->assertOk()
            ->assertJsonStructure([
                'property',
                'tax_declaration_timeline',
                'property_documents',
                'data_entry_timeline',
                'pending_digitization',
                'counts',
            ]);
    }
}
