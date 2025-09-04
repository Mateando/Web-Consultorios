<?php

return [
    // Mensajes base (puedes extender según necesites; Laravel fallback usará en caso faltantes)
    'required' => 'El campo :attribute es obligatorio.',
    'email' => 'El campo :attribute debe ser un correo válido.',
    'date' => 'El campo :attribute no es una fecha válida.',
    'numeric' => 'El campo :attribute debe ser numérico.',
    'max' => [
        'string' => 'El campo :attribute no debe superar :max caracteres.'
    ],
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.'
    ],
    'unique' => 'El valor de :attribute ya está en uso.',
    'confirmed' => 'La confirmación de :attribute no coincide.',

    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'secondary_email' => 'correo secundario',
        'password' => 'contraseña',
        'phone' => 'teléfono',
        'landline_phone' => 'teléfono fijo',
        'birth_date' => 'fecha de nacimiento',
        'gender' => 'sexo',
        'address' => 'dirección',
        'country' => 'país',
        'province' => 'provincia',
        'city' => 'ciudad',
        'document_type' => 'tipo de documento',
        'document_number' => 'número de documento',
        'patient_type' => 'tipo de paciente',
        'emergency_contact_name' => 'nombre de contacto de emergencia',
        'emergency_contact_phone' => 'teléfono de contacto de emergencia',
        'insurance_provider' => 'obra social / prepaga',
        'insurance_number' => 'número de afiliado',
        'allergies' => 'alergias',
        'medical_conditions' => 'condiciones médicas',
        'medications' => 'medicaciones',
        'blood_type' => 'grupo sanguíneo',
        'height' => 'altura',
        'weight' => 'peso',
        'observations' => 'observaciones',
        'extra_observations' => 'observaciones extra',
    ],
];
