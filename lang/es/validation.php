<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser una cadena de texto.',
    'email' => 'El campo :attribute debe ser un correo electrónico válido.',
    'max' => [
        'string' => 'El campo :attribute no debe tener más de :max caracteres.',
    ],
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'unique' => 'El campo :attribute ya ha sido registrado.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'in' => 'El campo :attribute seleccionado no es válido.',
    'lowercase' => 'El campo :attribute debe estar en minúsculas.',
    'current_password' => 'La contraseña es incorrecta.',

    'attributes' => [
        'first_name' => 'nombre',
        'last_name' => 'apellido',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'password_confirmation' => 'confirmación de contraseña',
        'institution' => 'institución',
        'pronoun' => 'pronombre',
        'educational_area' => 'área educativa',
        'educational_level' => 'nivel educativo',
        'recovery_code' => 'código de recuperación',
    ],
];
