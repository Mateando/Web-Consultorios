<div class="space-y-6">
    <!-- Mensajes de estado -->
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('message') }}
        </div>
    @endif

    <!-- Filtros y botón de crear -->
    <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <h3 class="text-lg font-medium text-gray-900">Gestión de Citas</h3>
                @can('crear_citas')
                <button wire:click="openCreateForm" class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Nueva Cita</button>
            @endcan
        </div>

        <!-- Filtros -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Fecha</label>
                <input type="date" wire:model="filterDate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Doctor</label>
                <select wire:model="filterDoctor" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Todos los doctores</option>
                    @foreach($doctors as $doctor)
                        <option value="{{ $doctor->id }}">Dr. {{ $doctor->user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Especialidad</label>
                <select wire:model="filterSpecialty" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Todas las especialidades</option>
                    @foreach($specialties as $specialty)
                        <option value="{{ $specialty->id }}">{{ $specialty->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Estado</label>
                <select wire:model="filterStatus" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Todos los estados</option>
                    <option value="programada">Programada</option>
                    <option value="confirmada">Confirmada</option>
                    <option value="completada">Completada</option>
                    <option value="cancelada">Cancelada</option>
                    <option value="no_asistio">No asistió</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Buscar Paciente</label>
                <input type="text" wire:model.debounce.300ms="filterPatient" placeholder="Nombre del paciente" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>
    </div>

    <!-- Lista de citas -->
    <!-- Leyenda de colores -->
    <div class="bg-white p-4 rounded-lg shadow mb-4">
        <h4 class="text-sm font-medium text-gray-700 mb-2">Leyenda de colores</h4>
        <div class="flex flex-wrap items-center gap-4 text-sm">
            <div class="flex items-center gap-2">
                <span class="inline-block h-3 w-3 rounded-full bg-yellow-300 border"></span>
                <span class="text-gray-700">Programada</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-block h-3 w-3 rounded-full bg-blue-300 border"></span>
                <span class="text-gray-700">Confirmada</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-block h-3 w-3 rounded-full bg-green-300 border"></span>
                <span class="text-gray-700">Completada</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-block h-3 w-3 rounded-full bg-red-300 border"></span>
                <span class="text-gray-700">Cancelada</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-block h-3 w-3 rounded-full bg-gray-300 border"></span>
                <span class="text-gray-700">No asistió</span>
            </div>
        </div>
    </div>
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <ul class="divide-y divide-gray-200">
            @forelse($appointments as $appointment)
                <li class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ substr($appointment->patient->user->name, 0, 2) }}
                                    </span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $appointment->patient->user->name }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    Dr. {{ $appointment->doctor->user->name }} - {{ $appointment->doctor->specialty->name }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $appointment->appointment_date->format('d/m/Y H:i') }} 
                                    ({{ $appointment->duration }} min)
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $appointment->reason }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <!-- Estado -->
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @switch($appointment->status)
                                    @case('programada') bg-yellow-100 text-yellow-800 @break
                                    @case('confirmada') bg-blue-100 text-blue-800 @break
                                    @case('completada') bg-green-100 text-green-800 @break
                                    @case('cancelada') bg-red-100 text-red-800 @break
                                    @case('no_asistio') bg-gray-100 text-gray-800 @break
                                    @default bg-gray-100 text-gray-800
                                @endswitch
                            ">
                                {{ ucfirst($appointment->status) }}
                            </span>

                            <!-- Acciones -->
                            <div class="flex space-x-2">
                                @can('editar_citas')
                                    <button wire:click="openEditForm({{ $appointment->id }})" class="text-sm inline-flex items-center rounded-md border border-transparent bg-white px-3 py-1 font-medium text-indigo-600 hover:text-indigo-900">Editar</button>
                                @endcan

                                @if($appointment->status === 'programada' && Auth::user()->can('confirmar_citas'))
                                    <button wire:click="updateStatus({{ $appointment->id }}, 'confirmada')" class="text-sm inline-flex items-center rounded-md border border-transparent bg-white px-3 py-1 font-medium text-green-600 hover:text-green-900">Confirmar</button>
                                @endif

                                @if($appointment->status === 'confirmada' && Auth::user()->hasRole('medico'))
                                    <button wire:click="updateStatus({{ $appointment->id }}, 'completada')" class="text-sm inline-flex items-center rounded-md border border-transparent bg-white px-3 py-1 font-medium text-blue-600 hover:text-blue-900">Completar</button>
                                @endif

                                @if(in_array($appointment->status, ['programada', 'confirmada']) && Auth::user()->can('cancelar_citas'))
                                    <button wire:click="updateStatus({{ $appointment->id }}, 'cancelada')" class="text-sm inline-flex items-center rounded-md border border-transparent bg-white px-3 py-1 font-medium text-red-600 hover:text-red-900">Cancelar</button>
                                @endif

                                @can('eliminar_citas')
                                    <button wire:click="delete({{ $appointment->id }})" wire:confirm="¿Está seguro de que desea eliminar esta cita?" class="text-sm inline-flex items-center rounded-md border border-transparent bg-white px-3 py-1 font-medium text-red-600 hover:text-red-900">Eliminar</button>
                                @endcan
                            </div>
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-6 py-4 text-center text-gray-500">
                    No hay citas que coincidan con los filtros seleccionados.
                </li>
            @endforelse
        </ul>
    </div>

    <!-- Paginación -->
    <div class="px-6 py-3 bg-white border-t border-gray-200">
        {{ $appointments->links() }}
    </div>

    <!-- Modal para crear cita -->
    @if($showCreateForm)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 text-center mb-4">Nueva Cita</h3>
                    
                    <form wire:submit="save" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Paciente -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Paciente</label>
                                <select wire:model="patient_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Seleccionar paciente</option>
                                    @foreach($patients as $patient)
                                        <option value="{{ $patient->id }}">{{ $patient->user->name }}</option>
                                    @endforeach
                                </select>
                                @error('patient_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Especialidad -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Especialidad</label>
                                <select wire:model="specialty_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Seleccionar especialidad</option>
                                    @foreach($specialties as $specialty)
                                        <option value="{{ $specialty->id }}">{{ $specialty->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Doctor -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Doctor</label>
                                <select wire:model="doctor_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Seleccionar doctor</option>
                                    @foreach($availableDoctors as $doctor)
                                        <option value="{{ $doctor->id }}">Dr. {{ $doctor->user->name }}</option>
                                    @endforeach
                                </select>
                                @error('doctor_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Fecha -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fecha</label>
                                <input type="date" wire:model="appointment_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('appointment_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Hora -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Hora</label>
                                <input type="time" wire:model="appointment_time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('appointment_time') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Duración -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Duración (minutos)</label>
                                <select wire:model="duration" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="15">15 minutos</option>
                                    <option value="30">30 minutos</option>
                                    <option value="45">45 minutos</option>
                                    <option value="60">1 hora</option>
                                    <option value="90">1.5 horas</option>
                                    <option value="120">2 horas</option>
                                </select>
                                @error('duration') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Motivo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Motivo de la consulta</label>
                            <input type="text" wire:model="reason" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('reason') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <!-- Notas -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Notas</label>
                            <textarea wire:model="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>

                        <!-- Botones -->
                        <div class="flex items-center justify-end space-x-3 pt-4">
                            <button type="button" wire:click="closeCreateForm" class="inline-flex items-center rounded-md border border-transparent bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancelar</button>
                            <button type="submit" class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Crear Cita</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal para editar cita -->
    @if($showEditForm && $selectedAppointment)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 text-center mb-4">Editar Cita</h3>
                    
                    <form wire:submit="update" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Paciente -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Paciente</label>
                                <select wire:model="patient_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Seleccionar paciente</option>
                                    @foreach($patients as $patient)
                                        <option value="{{ $patient->id }}">{{ $patient->user->name }}</option>
                                    @endforeach
                                </select>
                                @error('patient_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Doctor -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Doctor</label>
                                <select wire:model="doctor_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Seleccionar doctor</option>
                                    @foreach($doctors as $doctor)
                                        <option value="{{ $doctor->id }}">Dr. {{ $doctor->user->name }}</option>
                                    @endforeach
                                </select>
                                @error('doctor_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Fecha -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fecha</label>
                                <input type="date" wire:model="appointment_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('appointment_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Hora -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Hora</label>
                                <input type="time" wire:model="appointment_time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('appointment_time') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Duración -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Duración (minutos)</label>
                                <select wire:model="duration" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="15">15 minutos</option>
                                    <option value="30">30 minutos</option>
                                    <option value="45">45 minutos</option>
                                    <option value="60">1 hora</option>
                                    <option value="90">1.5 horas</option>
                                    <option value="120">2 horas</option>
                                </select>
                                @error('duration') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Estado -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Estado</label>
                                <select wire:model="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="programada">Programada</option>
                                    <option value="confirmada">Confirmada</option>
                                    <option value="completada">Completada</option>
                                    <option value="cancelada">Cancelada</option>
                                    <option value="no_asistio">No asistió</option>
                                </select>
                                @error('status') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Motivo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Motivo de la consulta</label>
                            <input type="text" wire:model="reason" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('reason') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <!-- Notas -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Notas</label>
                            <textarea wire:model="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>

                        <!-- Botones -->
                        <div class="flex items-center justify-end space-x-3 pt-4">
                            <button type="button" wire:click="closeEditForm" class="inline-flex items-center rounded-md border border-transparent bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancelar</button>
                            <button type="submit" class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Actualizar Cita</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
