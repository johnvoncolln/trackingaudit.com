<?php

namespace Tests\Feature;

use App\Livewire\NotificationSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_is_accessible_by_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/settings')
            ->assertStatus(200)
            ->assertSeeLivewire(NotificationSettings::class);
    }

    public function test_settings_page_redirects_unauthenticated_user(): void
    {
        $this->get('/settings')
            ->assertRedirect('/login');
    }

    public function test_can_enable_late_shipment_notifications(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationSettings::class)
            ->set('lateShipmentNotificationsEnabled', true)
            ->set('lateShipmentNotificationsFrequency', 'weekly')
            ->call('save')
            ->assertDispatched('saved');

        $user->refresh();
        $this->assertTrue($user->late_shipment_notifications_enabled);
        $this->assertSame('weekly', $user->late_shipment_notifications_frequency);
    }

    public function test_can_enable_late_shipment_report(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationSettings::class)
            ->set('lateShipmentReportEnabled', true)
            ->set('lateShipmentReportFrequency', 'monthly')
            ->call('save')
            ->assertDispatched('saved');

        $user->refresh();
        $this->assertTrue($user->late_shipment_report_enabled);
        $this->assertSame('monthly', $user->late_shipment_report_frequency);
    }

    public function test_validates_frequency_is_valid_enum(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationSettings::class)
            ->set('lateShipmentReportFrequency', 'invalid')
            ->call('save')
            ->assertHasErrors(['lateShipmentReportFrequency']);
    }

    public function test_can_generate_api_token(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->api_token);

        Livewire::actingAs($user)
            ->test(NotificationSettings::class)
            ->assertSet('apiToken', null)
            ->call('generateApiToken')
            ->assertNotSet('apiToken', null);

        $user->refresh();
        $this->assertNotNull($user->api_token);
    }

    public function test_can_regenerate_api_token(): void
    {
        $user = User::factory()->create(['api_token' => 'old-token-value']);

        Livewire::actingAs($user)
            ->test(NotificationSettings::class)
            ->assertSet('apiToken', 'old-token-value')
            ->call('generateApiToken');

        $user->refresh();
        $this->assertNotSame('old-token-value', $user->api_token);
        $this->assertNotNull($user->api_token);
    }

    public function test_settings_persist_after_save(): void
    {
        $user = User::factory()->create([
            'late_shipment_notifications_enabled' => true,
            'late_shipment_notifications_frequency' => 'monthly',
            'late_shipment_report_enabled' => true,
            'late_shipment_report_frequency' => 'weekly',
        ]);

        Livewire::actingAs($user)
            ->test(NotificationSettings::class)
            ->assertSet('lateShipmentNotificationsEnabled', true)
            ->assertSet('lateShipmentNotificationsFrequency', 'monthly')
            ->assertSet('lateShipmentReportEnabled', true)
            ->assertSet('lateShipmentReportFrequency', 'weekly');
    }
}
