<?php

return [
    'array' => ':Attribute muss eine Liste sein.',
    'between' => [
        'numeric' => ':Attribute muss zwischen :min und :max liegen.',
    ],
    'boolean' => ':Attribute muss ja oder nein sein.',
    'confirmed' => 'Die Wiederholung von :attribute stimmt nicht überein.',
    'distinct' => ':Attribute kommt doppelt vor.',
    'email' => ':Attribute muss eine gültige E-Mail-Adresse sein.',
    'exists' => 'Die Auswahl für :attribute ist ungültig.',
    'file' => ':Attribute muss eine Datei sein.',
    'in' => 'Die Auswahl für :attribute ist ungültig.',
    'integer' => ':Attribute muss eine ganze Zahl sein.',
    'max' => [
        'array' => ':Attribute darf höchstens :max Einträge haben.',
        'file' => ':Attribute darf höchstens :max KB groß sein.',
        'string' => ':Attribute darf höchstens :max Zeichen lang sein.',
    ],
    'mimes' => ':Attribute muss vom Typ :values sein.',
    'min' => [
        'array' => ':Attribute braucht mindestens :min Einträge.',
        'string' => ':Attribute muss mindestens :min Zeichen lang sein.',
    ],
    'password' => [
        'letters' => ':Attribute muss mindestens einen Buchstaben enthalten.',
        'mixed' => ':Attribute muss Groß- und Kleinbuchstaben enthalten.',
        'numbers' => ':Attribute muss mindestens eine Zahl enthalten.',
        'symbols' => ':Attribute muss mindestens ein Sonderzeichen enthalten.',
        'uncompromised' => ':Attribute ist in einem Datenleck aufgetaucht. Bitte wähle ein anderes.',
    ],
    'required' => ':Attribute fehlt.',
    'required_if' => ':Attribute fehlt.',
    'string' => ':Attribute muss Text sein.',
    'unique' => ':Attribute ist schon vergeben.',

    'attributes' => [
        'name' => 'Name',
        'email' => 'E-Mail',
        'password' => 'Passwort',
        'theme' => 'Aussehen',
        'criteria' => 'Kriterien',
        'criteria.*' => 'Kriterium',
        'stations' => 'Familien',
        'stations.*' => 'Familie',
        'station_id' => 'Familie',
        'preset_name' => 'Name der Vorlage',
        'photo' => 'Foto',
        'code' => 'Code',
        'action' => 'Aktion',
    ],
];
