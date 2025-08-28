<div>
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Lista de Doctores</h3>
        <div class="flex-1 md:max-w-md">
            <div class="flex gap-2">
                <input type="text" wire:model.live.debounce.400ms="search" placeholder="Buscar por nombre, email o matrícula" class="flex-1 rounded-md border-gray-300 shadow-sm text-sm" />
                @if($search)
                    <button wire:click="clearSearch" type="button" class="px-3 py-2 rounded text-sm bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">X</button>
                @endif
            </div>
            <div class="mt-1 text-xs text-gray-500 h-4 flex items-center">
                <span wire:loading.delay.longer.class="inline" wire:target="search">Buscando...</span>
            </div>
        </div>
        <div>
            <button type="button" class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-sm font-medium text-white">Nuevo Doctor</button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Especialidades</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teléfono</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matrícula</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($this->doctors as $doctor)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900">{{ $doctor->user->name ?? 'Sin nombre' }}</td>
                        <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500">{{ $doctor->user->email ?? 'Sin email' }}</td>
                        <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500">
                            <div class="flex flex-wrap gap-1">
                                @forelse($doctor->specialties as $spec)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $spec->name }}</span>
                                @empty
                                    <span class="text-gray-400">Sin especialidades</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500">{{ $doctor->phone ?? 'N/A' }}</td>
                        <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500">{{ $doctor->license_number }}</td>
                        <td class="px-6 py-2 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ ($doctor->user->is_active ?? true) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ($doctor->user->is_active ?? true) ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-6 py-2 whitespace-nowrap text-right text-sm font-medium flex gap-3">
                            <button wire:click="toggleStatus({{ $doctor->id }})" class="text-sm inline-flex items-center rounded-md border border-transparent bg-white px-3 py-1 font-medium text-indigo-600 hover:text-indigo-900">{{ ($doctor->user->is_active ?? true) ? 'Desactivar' : 'Activar' }}</button>
                            <button type="button" class="text-sm inline-flex items-center rounded-md border border-transparent bg-white px-3 py-1 font-medium text-blue-600 hover:text-blue-900">Editar</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-2 text-center text-gray-500">No hay doctores registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $this->doctors->links() }}
    </div>
</div>
