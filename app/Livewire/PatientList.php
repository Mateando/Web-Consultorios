<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;

class PatientList extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 6;

    protected $updatesQueryString = ['search'];

    protected $listeners = [
        'patientSaved' => 'refreshList',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function refreshList()
    {
        // Solo para forzar refresco
    }

    public function clearSearch()
    {
        $this->search = '';
    }

    public function getPatientsProperty()
    {
        return Patient::query()
            ->select('patients.*')
            ->with('user')
            ->withCount('appointments')
            ->leftJoin('users', 'users.id', '=', 'patients.user_id')
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($qq) use ($term) {
                    $qq->where('users.name', 'like', $term)
                        ->orWhere('users.email', 'like', $term)
                        ->orWhere('users.document_number', 'like', $term);
                });
            })
            ->orderBy(DB::raw('LOWER(users.name)'))
            ->paginate($this->perPage);
    }

    public function toggleStatus($patientId)
    {
        $patient = Patient::with('user')->find($patientId);
        if ($patient && $patient->user) {
            $patient->user->is_active = !$patient->user->is_active;
            $patient->user->save();
        }
    }

    public function render()
    {
        return view('livewire.patient-list', [
            'patients' => $this->patients,
        ]);
    }
}
