<?php

namespace App\Livewire;

use App\Enums\Carrier;
use App\Enums\TrackerStatus;
use App\Models\Tracker;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public int $days = 30;

    public function render(): View
    {
        $userId = Auth::id();
        $startDate = now()->subDays($this->days);

        $activeStatusValues = collect(TrackerStatus::activeStatuses())
            ->map(fn (TrackerStatus $s) => $s->value)
            ->toArray();

        $attentionStatusValues = [
            TrackerStatus::FAILURE->value,
            TrackerStatus::RETURN_TO_SENDER->value,
            TrackerStatus::ERROR->value,
        ];

        $stats = Tracker::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('COUNT(*) as total_shipments')
            ->selectRaw(
                'COUNT(CASE WHEN status = ? THEN 1 END) as delivered_count',
                [TrackerStatus::DELIVERED->value]
            )
            ->selectRaw(
                'COUNT(CASE WHEN status IN ('.$this->placeholders($activeStatusValues).') THEN 1 END) as en_route_count',
                $activeStatusValues
            )
            ->selectRaw(
                'COUNT(CASE WHEN status = ? AND delivered_date IS NOT NULL AND delivery_date IS NOT NULL AND DATE(delivered_date) > DATE(delivery_date) THEN 1 END) as late_count',
                [TrackerStatus::DELIVERED->value]
            )
            ->selectRaw(
                'COUNT(CASE WHEN status IN ('.$this->placeholders($attentionStatusValues).') THEN 1 END) as needs_attention_count',
                $attentionStatusValues
            )
            ->selectRaw(
                'COUNT(CASE WHEN status = ? AND delivered_date IS NOT NULL AND delivery_date IS NOT NULL AND DATE(delivered_date) <= DATE(delivery_date) THEN 1 END) as on_time_count',
                [TrackerStatus::DELIVERED->value]
            )
            ->selectRaw(
                'COUNT(CASE WHEN status = ? AND delivered_date IS NOT NULL AND delivery_date IS NOT NULL THEN 1 END) as on_time_eligible_count',
                [TrackerStatus::DELIVERED->value]
            )
            ->first();

        $onTimeRate = $stats->on_time_eligible_count > 0
            ? round(($stats->on_time_count / $stats->on_time_eligible_count) * 100, 1)
            : null;

        $avgDeliveryDays = Tracker::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->where('status', TrackerStatus::DELIVERED->value)
            ->whereNotNull('delivered_date')
            ->whereColumn('delivered_date', '>=', 'created_at')
            ->get(['created_at', 'delivered_date'])
            ->avg(fn (Tracker $t) => abs($t->created_at->diffInSeconds($t->delivered_date)) / 86400);

        if ($avgDeliveryDays !== null) {
            $avgDeliveryDays = round($avgDeliveryDays, 1);
        }

        $carrierBreakdown = Tracker::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('carrier, COUNT(*) as total')
            ->groupBy('carrier')
            ->pluck('total', 'carrier');

        $recentShipments = Tracker::query()
            ->where('user_id', $userId)
            ->latest()
            ->limit(5)
            ->get();

        return view('livewire.dashboard', [
            'totalShipments' => $stats->total_shipments,
            'deliveredCount' => $stats->delivered_count,
            'enRouteCount' => $stats->en_route_count,
            'lateCount' => $stats->late_count,
            'needsAttentionCount' => $stats->needs_attention_count,
            'onTimeRate' => $onTimeRate,
            'avgDeliveryDays' => $avgDeliveryDays,
            'carrierBreakdown' => $carrierBreakdown,
            'carriers' => Carrier::cases(),
            'recentShipments' => $recentShipments,
        ]);
    }

    private function placeholders(array $values): string
    {
        return implode(',', array_fill(0, count($values), '?'));
    }
}
