<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">
                    Gestión de Horarios de Doctores
                </h2>
                <button wire:click="openForm" 
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-plus mr-2"></i>Nuevo Horario
                </button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filtrar por Doctor</label>
                    <select wire:model.live="doctorId" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos los doctores</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}">{{ $doctor->user->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Mensajes de estado -->
        @if (session()->has('message'))
            <div class="mx-6 mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                {{ session('message') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mx-6 mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                {{ session('error') }}
            </div>
        @endif

        <!-- Lista de horarios -->
        <div class="px-6 py-4">
            @if($schedules->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Especialidad</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Día</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Horario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duración</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($schedules as $schedule)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $schedule->doctor->user->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $schedule->specialty->name }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $daysOfWeek[$schedule->day_of_week] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} - 
                                        {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $schedule->appointment_duration }} min
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button wire:click="toggleStatus({{ $schedule->id }})" 
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $schedule->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $schedule->is_active ? 'Activo' : 'Inactivo' }}
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button wire:click="editSchedule({{ $schedule->id }})" 
                                                class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button wire:click="deleteSchedule({{ $schedule->id }})" 
                                                class="text-red-600 hover:text-red-900"
                                                onclick="return confirm('¿Está seguro de eliminar este horario?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="mt-4">
                    {{ $schedules->links() }}
                </div>
            @else
                <div class="text-center py-8">
                    <i class="fas fa-calendar-times text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-500">No hay horarios registrados</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal de formulario -->
    @if($showForm)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <!-- Header del modal -->
                    <div class="flex items-center justify-between pb-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ $editingScheduleId ? 'Editar Horario' : 'Nuevo Horario' }}
                        </h3>
                        <button wire:click="closeForm" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Formulario -->
                    <form wire:submit.prevent="save" class="mt-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Doctor -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Doctor *</label>
                                <select wire:model.live="doctorId" 
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('doctorId') border-red-500 @enderror">
                                    <option value="">Seleccione un doctor</option>
                                    @foreach($doctors as $doctor)
                                        <option value="{{ $doctor->id }}">{{ $doctor->user->name }}</option>
                                    @endforeach
                                </select>
                                @error('doctorId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Especialidad -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Especialidad *</label>
                                <select wire:model="specialtyId" 
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('specialtyId') border-red-500 @enderror"
                                        {{ !$selectedDoctor ? 'disabled' : '' }}>
                                    <option value="">Seleccione una especialidad</option>
                                    @if($selectedDoctor)
                                        @foreach($selectedDoctor->specialties as $specialty)
                                            <option value="{{ $specialty->id }}">{{ $specialty->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('specialtyId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                @if(!$selectedDoctor)
                                    <p class="text-gray-500 text-xs mt-1">Primero seleccione un doctor</p>
                                @endif
                            </div>

                            <!-- Día de la semana -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Día de la semana *</label>
                                <select wire:model="dayOfWeek" 
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('dayOfWeek') border-red-500 @enderror">
                                    <option value="">Seleccione un día</option>
                                    @foreach($daysOfWeek as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('dayOfWeek') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Duración de cita -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Duración de cita (minutos) *</label>
                                <select wire:model="appointmentDuration" 
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('appointmentDuration') border-red-500 @enderror">
                                    <option value="15">15 minutos</option>
                                    <option value="30">30 minutos</option>
                                    <option value="45">45 minutos</option>
                                    <option value="60">60 minutos</option>
                                    <option value="90">90 minutos</option>
                                    <option value="120">120 minutos</option>
                                </select>
                                @error('appointmentDuration') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Hora de inicio -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Hora de inicio *</label>
                                <input type="time" wire:model="startTime" 
                                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('startTime') border-red-500 @enderror">
                                @error('startTime') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Hora de fin -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Hora de fin *</label>
                                <input type="time" wire:model="endTime" 
                                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('endTime') border-red-500 @enderror">
                                @error('endTime') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Estado -->
                            <div class="md:col-span-2">
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="isActive" 
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-700">Horario activo</span>
                                </label>
                            </div>
                        </div>

                        <!-- Botones del formulario -->
                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" wire:click="closeForm" 
                                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition duration-200">
                                Cancelar
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-200">
                                {{ $editingScheduleId ? 'Actualizar' : 'Guardar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

<style>
    [x-cloak] { display: none; }
</style>
