<?php

namespace Tests\Feature;

use App\Enums\TaxeStatut;
use App\Models\Affectation;
use App\Models\Agent;
use App\Models\Operateur;
use App\Models\PaiementTaxe;
use App\Models\Parameters\Fonction;
use App\Models\Parameters\Quartier;
use App\Models\Personne;
use App\Models\Taxe;
use App\Models\TaxeOperateur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MobileTaxCollectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Agent $agent;
    protected Quartier $quartier;
    protected Operateur $operateur;
    protected Taxe $taxe;
    protected TaxeOperateur $taxeOp;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Quartier
        $this->quartier = Quartier::create([
            'id' => 10,
            'nom' => 'Quartier Test Fiscale',
            'code' => 'Q-TEST',
        ]);

        // 2. User & Agent
        $personne = Personne::create([
            'id' => (string) Str::uuid(),
            'nom' => 'Agent',
            'prenom' => 'Test',
            'email' => 'agent.personne@example.com',
            'genre' => 'M',
        ]);

        $fonctionAgent = Fonction::create([
            'id' => (string) Str::uuid(),
            'nom' => 'Enquêteur Terrain',
            'code' => 'ENQ',
            'slug' => 'enqueteur-terrain',
        ]);

        $this->agent = Agent::create([
            'id' => (string) Str::uuid(),
            'personne_id' => $personne->id,
            'fonction_id' => $fonctionAgent->id,
            'matricule' => 'AG-TAX-01',
            'statut' => 'actif',
        ]);

        $this->user = User::create([
            'name' => 'Agent Test User',
            'email' => 'agent.tax@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->agent->update(['user_id' => $this->user->id]);

        // Affectation active dans le quartier 10
        Affectation::create([
            'id' => (string) Str::uuid(),
            'agent_id' => $this->agent->id,
            'fonction_id' => $fonctionAgent->id,
            'quartier_id' => $this->quartier->id,
            'statut' => 'Active',
            'date_debut' => now()->subDays(5),
            'date_fin' => now()->addDays(30),
        ]);

        // 3. Opérateur dans le quartier 10
        $this->operateur = Operateur::create([
            'id' => (string) Str::uuid(),
            'uuid' => (string) Str::uuid(),
            'nom_commercial' => 'Boutique Express',
            'nom_entreprise' => 'Express SARL',
            'quartier_id' => $this->quartier->id,
            'effectif_total' => 5,
        ]);

        // 4. Taxe Municipale
        $this->taxe = Taxe::create([
            'id' => (string) Str::uuid(),
            'uuid' => (string) Str::uuid(),
            'code' => 'PATENT-001',
            'nom' => 'Patente Municipale Annuelle',
            'montant' => 100000.00,
            'mode_calcul' => 'fixe',
            'periodicite' => 'annuelle',
            'actif' => true,
        ]);

        // 5. Taxe affectée à l'opérateur
        $this->taxeOp = TaxeOperateur::create([
            'id' => (string) Str::uuid(),
            'uuid' => (string) Str::uuid(),
            'operateur_id' => $this->operateur->id,
            'taxe_id' => $this->taxe->id,
            'annee_fiscale' => 2026,
            'montant_attendu' => 100000.00,
            'montant_paye' => 0.00,
            'reste_a_payer' => 100000.00,
            'date_limite' => now()->addDays(30),
            'statut' => TaxeStatut::A_PAYER,
        ]);
    }

    /** @test */
    public function agent_can_get_operator_fiscal_situation()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/operators/{$this->operateur->id}/taxes");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.operateur.nom_commercial', 'Boutique Express')
            ->assertJsonPath('data.taxes.0.reste_a_payer', 100000);
    }

    /** @test */
    public function agent_cannot_access_operator_outside_assigned_zone()
    {
        $otherQuartier = Quartier::create([
            'id' => 99,
            'nom' => 'Quartier Interdit',
            'code' => 'Q-OTHER',
        ]);

        $otherOp = Operateur::create([
            'id' => (string) Str::uuid(),
            'uuid' => (string) Str::uuid(),
            'nom_commercial' => 'Magasin Hors Zone',
            'quartier_id' => $otherQuartier->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/operators/{$otherOp->id}/taxes");

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function partial_tax_payment_updates_balance_and_status()
    {
        $payload = [
            'uuid' => (string) Str::uuid(),
            'taxe_operateur_id' => $this->taxeOp->id,
            'montant' => 40000,
            'mode_paiement' => 'Espèces',
            'date_paiement' => '2026-08-18',
            'latitude' => 9.537,
            'longitude' => -13.678,
            'precision_gps' => 5.2,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/tax-payments', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reste_a_payer', 60000);

        $this->taxeOp->refresh();
        $this->assertEquals(40000, $this->taxeOp->montant_paye);
        $this->assertEquals(60000, $this->taxeOp->reste_a_payer);
        $this->assertEquals(TaxeStatut::PARTIELLEMENT_PAYE, $this->taxeOp->statut);
    }

    /** @test */
    public function full_payment_clears_tax_balance_and_sets_status_solde()
    {
        $payload = [
            'uuid' => (string) Str::uuid(),
            'taxe_operateur_id' => $this->taxeOp->id,
            'montant' => 100000,
            'mode_paiement' => 'Mobile Money',
            'reference' => 'MM-99228811',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/tax-payments', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reste_a_payer', 0);

        $this->taxeOp->refresh();
        $this->assertEquals(100000, $this->taxeOp->montant_paye);
        $this->assertEquals(0, $this->taxeOp->reste_a_payer);
        $this->assertEquals(TaxeStatut::SOLDE, $this->taxeOp->statut);
    }

    /** @test */
    public function duplicate_payment_request_with_same_uuid_is_idempotent()
    {
        $paymentUuid = (string) Str::uuid();
        $payload = [
            'uuid' => $paymentUuid,
            'taxe_operateur_id' => $this->taxeOp->id,
            'montant' => 30000,
            'mode_paiement' => 'Espèces',
        ];

        // Premier envoi
        $res1 = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/tax-payments', $payload);
        $res1->assertStatus(201);

        // Deuxième envoi (rejeu)
        $res2 = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/tax-payments', $payload);
        $res2->assertStatus(201);

        // Vérifier qu'un SEUL paiement a été créé en base
        $this->assertEquals(1, PaiementTaxe::where('uuid', $paymentUuid)->count());
        $this->taxeOp->refresh();
        $this->assertEquals(30000, $this->taxeOp->montant_paye);
    }

    /** @test */
    public function overpayment_is_capped_to_remaining_amount()
    {
        $payload = [
            'uuid' => (string) Str::uuid(),
            'taxe_operateur_id' => $this->taxeOp->id,
            'montant' => 150000, // Plus élevé que le reste (100,000)
            'mode_paiement' => 'Espèces',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/tax-payments', $payload);

        $response->assertStatus(201);
        $this->taxeOp->refresh();
        $this->assertEquals(100000, $this->taxeOp->montant_paye);
        $this->assertEquals(0, $this->taxeOp->reste_a_payer);
        $this->assertEquals(TaxeStatut::SOLDE, $this->taxeOp->statut);
    }

    /** @test */
    public function sync_batch_processes_offline_payments()
    {
        $p1 = [
            'uuid' => (string) Str::uuid(),
            'taxe_operateur_id' => $this->taxeOp->id,
            'montant' => 20000,
            'mode_paiement' => 'Espèces',
        ];
        $p2 = [
            'uuid' => (string) Str::uuid(),
            'taxe_operateur_id' => $this->taxeOp->id,
            'montant' => 30000,
            'mode_paiement' => 'Mobile Money',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/tax-payments/sync', [
                'payments' => [$p1, $p2],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.synced');

        $this->taxeOp->refresh();
        $this->assertEquals(50000, $this->taxeOp->montant_paye);
    }

    /** @test */
    public function payment_without_preassigned_taxe_operateur_id_auto_assigns_tax_and_succeeds()
    {
        $newTaxe = Taxe::create([
            'id' => (string) Str::uuid(),
            'uuid' => (string) Str::uuid(),
            'code' => 'ODP-TERR-TEST',
            'nom' => 'Taxe Occupation Domaine Public',
            'montant' => 15000.00,
            'mode_calcul' => 'fixe',
            'periodicite' => 'annuelle',
            'actif' => true,
        ]);

        $payload = [
            'uuid' => (string) Str::uuid(),
            'taxe_operateur_id' => 0,
            'operateur_id' => $this->operateur->id,
            'taxe_code' => 'ODP-TERR-TEST',
            'montant' => 15000,
            'mode_paiement' => 'Espèces',
            'date_paiement' => '2026-09-02',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/tax-payments', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        // Vérifier que la taxe a été automatiquement attribuée à l'opérateur
        $createdTaxeOp = TaxeOperateur::where('operateur_id', $this->operateur->id)
            ->where('taxe_id', $newTaxe->id)
            ->first();

        $this->assertNotNull($createdTaxeOp);
        $this->assertEquals(15000, $createdTaxeOp->montant_paye);
        $this->assertEquals(TaxeStatut::SOLDE, $createdTaxeOp->statut);
    }
}
