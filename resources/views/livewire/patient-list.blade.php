<div>
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Lista de Pacientes</h3>
        <div class="flex-1 md:max-w-md">
            <div class="flex gap-2">
                <input type="text" wire:model.live.debounce.400ms="search" placeholder="Buscar por nombre, email o documento" class="flex-1 rounded-md border-gray-300 shadow-sm text-sm" />
                @if($search)
                    <button wire:click="clearSearch" type="button" class="px-3 py-2 rounded text-sm bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">X</button>
                @endif
            </div>
            <div class="mt-1 text-xs text-gray-500 h-4 flex items-center">
                <span wire:loading.delay.longer.class="inline" wire:target="search">Buscando...</span>
            </div>
        </div>
        <div>
            <button type="button" wire:click="$dispatch('openPatientForm')" class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">Nuevo Paciente</button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Documento</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($patients as $patient)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900">{{ $patient->user->name ?? 'Sin nombre' }}</td>
                        <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500">{{ $patient->user->email ?? 'Sin email' }}</td>
                        <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500">{{ $patient->user->document_type }} - {{ $patient->user->document_number ?? 'N/A' }}</td>
                        <td class="px-6 py-2 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ ($patient->user->is_active ?? true) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ($patient->user->is_active ?? true) ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-6 py-2 whitespace-nowrap text-right text-sm font-medium flex gap-3">
                            <button wire:click="toggleStatus({{ $patient->id }})" class="text-sm inline-flex items-center rounded-md border border-transparent bg-white px-3 py-1 font-medium text-gray-700 hover:bg-gray-50">{{ ($patient->user->is_active ?? true) ? 'Desactivar' : 'Activar' }}</button>
                            <button type="button" wire:click="$dispatch('openPatientForm', { patientId: {{ $patient->id }} })" class="text-sm inline-flex items-center rounded-md border border-transparent bg-white px-3 py-1 font-medium text-blue-600 hover:text-blue-900">Editar</button>
                            @php $hasAppointments = ($patient->appointments_count ?? 0) > 0; @endphp
                            <a href="{{ $hasAppointments ? route('patients.appointments', $patient->id) : '#' }}" class="text-sm inline-flex items-center rounded-md border border-transparent px-3 py-1 font-medium {{ $hasAppointments ? 'bg-white text-green-600 hover:text-green-900' : 'bg-gray-100 text-gray-400 cursor-not-allowed' }}">Ver citas</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-2 text-center text-gray-500">No hay pacientes registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $patients->links() }}
    </div>

    <livewire:patient-form />

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('notify', data => {
                if(window.Swal){
                    Swal.fire({
                        toast:true,
                        position:'top-end',
                        timer:2500,
                        timerProgressBar:true,
                        showConfirmButton:false,
                        icon: data.type || 'success',
                        title: data.message || 'Acción realizada'
                    })
                }
            })
            Livewire.on('openPatientForm', payload => {
                Livewire.first().dispatchTo('patient-form', 'open', payload?.patientId || null)
            })
        })
    </script>
</div>
