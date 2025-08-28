<div>
    @if($show)
    <div class="fixed inset-0 bg-gray-900/50 flex items-center justify-center z-50">
        <div class="bg-white w-full max-w-2xl rounded-lg shadow-lg p-6 relative">
            <button class="absolute top-2 right-2 text-gray-500 hover:text-gray-700" wire:click="close">✕</button>
            <h2 class="text-lg font-semibold mb-4">{{ $patientId ? 'Editar Paciente' : 'Nuevo Paciente' }}</h2>

            <div class="flex items-center justify-center mb-4 gap-2">
                @for($i=1;$i<=5;$i++)
                    <div class="h-3 w-3 rounded-full {{ $step >= $i ? 'bg-blue-600' : 'bg-gray-300' }}"></div>
                @endfor
            </div>

            <form wire:submit.prevent="save" class="space-y-4">
                @if($step === 1)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-medium text-gray-700">Nombre</label>
                            <input type="text" wire:model.defer="name" class="mt-1 w-full rounded-md border-gray-300 text-sm" />
                            @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-700">Email</label>
                            <input type="email" wire:model.defer="email" class="mt-1 w-full rounded-md border-gray-300 text-sm" />
                            @error('email') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-700">Tipo Documento</label>
                            <input type="text" wire:model.defer="document_type" class="mt-1 w-full rounded-md border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-700">Nro Documento</label>
                            <input type="text" wire:model.defer="document_number" class="mt-1 w-full rounded-md border-gray-300 text-sm" />
                        </div>
                    </div>
                @elseif($step === 2)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-medium text-gray-700">Teléfono</label>
                            <input type="text" wire:model.defer="phone" class="mt-1 w-full rounded-md border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-700">País</label>
                            <input type="text" wire:model.defer="country" class="mt-1 w-full rounded-md border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-700">Provincia</label>
                            <input type="text" wire:model.defer="province" class="mt-1 w-full rounded-md border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-700">Ciudad</label>
                            <input type="text" wire:model.defer="city" class="mt-1 w-full rounded-md border-gray-300 text-sm" />
                        </div>
                    </div>
                @elseif($step === 3)
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="text-xs font-medium text-gray-700">Dirección</label>
                            <input type="text" wire:model.defer="address" class="mt-1 w-full rounded-md border-gray-300 text-sm" />
                        </div>
                    </div>
                @elseif($step === 4)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-medium text-gray-700">Tipo Paciente</label>
                            <input type="text" wire:model.defer="patient_type" class="mt-1 w-full rounded-md border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-700">Obra Social</label>
                            <input type="text" wire:model.defer="insurance_provider" class="mt-1 w-full rounded-md border-gray-300 text-sm" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs font-medium text-gray-700">Alergias</label>
                            <textarea wire:model.defer="allergies" rows="2" class="mt-1 w-full rounded-md border-gray-300 text-sm"></textarea>
                        </div>
                    </div>
                @elseif($step === 5)
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="text-xs font-medium text-gray-700">Notas</label>
                            <textarea wire:model.defer="notes" rows="3" class="mt-1 w-full rounded-md border-gray-300 text-sm"></textarea>
                        </div>
                        <div class="text-sm text-gray-600">
                            Revisa los datos antes de guardar.
                        </div>
                    </div>
                @endif

                <div class="flex justify-between pt-4 border-t">
                    <div>
                        @if($step > 1)
                            <button type="button" wire:click="prevStep" class="inline-flex items-center rounded-md border border-transparent bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Atrás</button>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        @if($step < 5)
                            <button type="button" wire:click="nextStep" class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Siguiente</button>
                        @else
                            <button type="submit" class="inline-flex items-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700" wire:loading.attr="disabled">Guardar</button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
