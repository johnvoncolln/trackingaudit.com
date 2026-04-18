<?php

namespace App\Livewire;

use App\Enums\Carrier;
use App\Enums\TrackerStatus;
use App\Models\Tracker;
use Livewire\Component;
use Livewire\WithPagination;

class TrackingTable extends Component
{
    use WithPagination;

    public $search = '';

    public $carrier = '';

    public $filter = '';

    public $dateFrom = '';

    public $dateTo = '';

    public $sortField = 'created_at';

    public $sortDirection = 'desc';

    public int $perPage = 25;

    protected $queryString = [
        'search' => ['except' => ''],
        'carrier' => ['except' => ''],
        'filter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'perPage' => ['except' => 25],
    ];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage($value): void
    {
        if (! in_array((int) $value, [25, 50, 100, 250])) {
            $this->perPage = 25;
        }

        $this->resetPage();
    }

    public function render()
    {
        $carriers = Carrier::values();

        $trackers = Tracker::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('tracking_number', 'like', '%'.$this->search.'%')
                        ->orWhere('reference_id', 'like', '%'.$this->search.'%')
                        ->orWhere('reference_name', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->carrier, function ($query) {
                $query->where('carrier', $this->carrier);
            })
            ->when($this->dateFrom, function ($query) {
                $query->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($query) {
                $query->whereDate('created_at', '<=', $this->dateTo);
            })
            ->when($this->filter, function ($query) {
                $activeStatuses = collect(TrackerStatus::activeStatuses())->map->value->toArray();

                match ($this->filter) {
                    'delivered' => $query->where('status', TrackerStatus::DELIVERED->value),
                    'en_route' => $query->whereIn('status', $activeStatuses),
                    'late' => $query->where('delivery_date', '<', now())
                        ->whereNull('delivered_date')
                        ->whereIn('status', $activeStatuses),
                    'needs_attention' => $query->whereIn('status', [
                        TrackerStatus::FAILURE->value,
                        TrackerStatus::RETURN_TO_SENDER->value,
                        TrackerStatus::ERROR->value,
                    ]),
                    default => null,
                };
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.tracking-table', [
            'trackers' => $trackers,
            'carriers' => $carriers,
        ]);
    }
}
