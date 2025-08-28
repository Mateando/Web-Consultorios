<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Doctor;
use Illuminate\Support\Facades\DB;

class DoctorList extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 6;

    protected $updatesQueryString = ['search'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function clearSearch()
    {
        $this->search = '';
    }

    public function getDoctorsProperty()
    {
        return Doctor::query()
            ->select('doctors.*')
            ->with(['user', 'specialties'])
            ->leftJoin('users', 'users.id', '=', 'doctors.user_id')
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($qq) use ($term) {
                    $qq->where('users.name', 'like', $term)
                        ->orWhere('users.email', 'like', $term)
                        ->orWhere('doctors.license_number', 'like', $term);
                });
            })
            ->orderBy(DB::raw('LOWER(users.name)'))
            ->paginate($this->perPage);
    }

    public function toggleStatus($doctorId)
    {
        $doctor = Doctor::with('user')->find($doctorId);
        if ($doctor && $doctor->user) {
            $doctor->user->is_active = !$doctor->user->is_active;
            $doctor->user->save();
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Estado actualizado correctamente'
            ]);
        }
    }

    public function render()
    {
        return view('livewire.doctor-list');
    }
}
