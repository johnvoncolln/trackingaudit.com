<?php

namespace App\Livewire;

use App\Enums\NotificationFrequency;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;
use Livewire\Component;

class NotificationSettings extends Component
{
    public bool $lateShipmentNotificationsEnabled = false;

    public string $lateShipmentNotificationsFrequency = 'daily';

    public bool $lateShipmentReportEnabled = false;

    public string $lateShipmentReportFrequency = 'daily';

    public ?string $apiToken = null;

    public function mount(): void
    {
        $user = auth()->user();

        $this->lateShipmentNotificationsEnabled = $user->late_shipment_notifications_enabled;
        $this->lateShipmentNotificationsFrequency = $user->late_shipment_notifications_frequency;
        $this->lateShipmentReportEnabled = $user->late_shipment_report_enabled;
        $this->lateShipmentReportFrequency = $user->late_shipment_report_frequency;
        $this->apiToken = $user->api_token;
    }

    public function save(): void
    {
        $this->validate([
            'lateShipmentNotificationsFrequency' => ['required', new Enum(NotificationFrequency::class)],
            'lateShipmentReportFrequency' => ['required', new Enum(NotificationFrequency::class)],
        ]);

        auth()->user()->update([
            'late_shipment_notifications_enabled' => $this->lateShipmentNotificationsEnabled,
            'late_shipment_notifications_frequency' => $this->lateShipmentNotificationsFrequency,
            'late_shipment_report_enabled' => $this->lateShipmentReportEnabled,
            'late_shipment_report_frequency' => $this->lateShipmentReportFrequency,
        ]);

        $this->dispatch('saved');
    }

    public function generateApiToken(): void
    {
        $this->apiToken = auth()->user()->generateApiToken();
    }

    public function render(): View
    {
        return view('livewire.notification-settings', [
            'frequencies' => NotificationFrequency::cases(),
        ]);
    }
}
