<?php

namespace App\Livewire;

use App\Enums\Carrier;
use App\Models\Tracker;
use Livewire\Component;
use Livewire\WithPagination;

class TrackingTable extends Component
{
    use WithPagination;

    public $search = '';
    public $carrier = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'carrier' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
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

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $carriers = Carrier::values();

        $trackers = Tracker::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('tracking_number', 'like', '%' . $this->search . '%')
                        ->orWhere('reference_id', 'like', '%' . $this->search . '%')
                        ->orWhere('reference_name', 'like', '%' . $this->search . '%');
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
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        return view('livewire.tracking-table', [
            'trackers' => $trackers,
            'carriers' => $carriers
        ]);
    }
}
