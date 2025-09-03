<x-layouts.livewire>
    <div class="max-w-4xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-semibold">Citas de {{ $patient->user->name ?? 'Paciente' }}</h2>
            <p class="text-sm text-gray-500">Documento: {{ $patient->user->document_type ?? '' }} - {{ $patient->user->document_number ?? '' }}</p>
        </div>
        <div>
            <a href="{{ route('patients.index') }}" class="inline-flex items-center rounded-md border border-transparent bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Volver</a>
        </div>
    </div>

    @if(session('error'))
        <div class="mb-4 text-red-700">{{ session('error') }}</div>
    @endif

    @if($appointments->isEmpty())
        <div class="p-6 bg-white rounded shadow text-gray-600">No se encontraron citas para este paciente.</div>
    @else
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
                @foreach($appointments as $appointment)
                    <li class="px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-indigo-600">{{ optional($appointment->doctor->user)->name ?? 'Sin médico asignado' }}</p>
                                <p class="mt-1 text-sm text-gray-500">{{ $appointment->appointment_date->format('d/m/Y H:i') }} - {{ ucfirst($appointment->status) }}</p>
                                @if($appointment->reason)
                                    <p class="mt-1 text-sm text-gray-500">Motivo: {{ $appointment->reason }}</p>
                                @endif
                            </div>
                                                        <div class="flex items-center gap-2">
                                                                <a href="{{ route('appointments.show', $appointment->id) }}" class="text-sm text-blue-600 hover:text-blue-900">Ver</a>
                                                                <a href="{{ route('appointments.print', $appointment->id) }}" target="_blank" class="text-sm text-gray-600 hover:text-gray-900 inline-flex items-center" title="Imprimir" aria-label="Imprimir">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2V9a2 2 0 012-2h16a2 2 0 012 2v7a2 2 0 01-2 2h-2M6 18v4h12v-4" />
                                                                        </svg>
                                                                </a>
                                                        </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
    </div>
</x-layouts.livewire>
