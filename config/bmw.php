<?php

// BMW CarData (EU/UK) — read-only telemetry API. Endpoints from the official
// customer portal docs and swagger-customer-api-v1.json.
return [
    'client_id' => env('BMW_CLIENT_ID'),

    // Shared secret required by the /api/bmw routes (X-Api-Key header or ?key= query param).
    'api_key' => env('BMW_API_KEY'),
    'device_code_url' => 'https://customer.bmwgroup.com/gcdm/oauth/device/code',
    'token_url' => 'https://customer.bmwgroup.com/gcdm/oauth/token',
    'api_base' => 'https://api-cardata.bmwgroup.com',
    'scope' => 'authenticate_user openid cardata:api:read cardata:streaming:read',
    'token_file' => storage_path('app/bmw-tokens.json'),

    // Default VIN / container used by `bmw:check` and the HTTP endpoints when not passed explicitly.
    'vin' => env('BMW_VIN'),
    'container_id' => env('BMW_CONTAINER_ID'),

    // Push notifications via ntfy (https://ntfy.sh, free iOS app).
    'ntfy_server' => env('NTFY_SERVER', 'https://ntfy.sh'),
    'ntfy_topic' => env('NTFY_TOPIC'),

    // Descriptor from BMW's Customer Telematics Data Catalogue.
    // Values: SECURED | LOCKED | SELECTIVE-LOCKED | UNLOCKED | INVALID | UNKNOWN
    'lock_descriptor' => 'vehicle.cabin.door.lock.status',
    'locked_values' => ['SECURED', 'LOCKED'],

    // Descriptors requested when creating the "security" container.
    'security_descriptors' => [
        'vehicle.cabin.door.lock.status',
        'vehicle.cabin.door.row1.driver.isOpen',
        'vehicle.cabin.door.row1.passenger.isOpen',
        'vehicle.cabin.door.row2.driver.isOpen',
        'vehicle.cabin.door.row2.passenger.isOpen',
        'vehicle.cabin.window.row1.driver.status',
        'vehicle.cabin.window.row1.passenger.status',
        'vehicle.cabin.window.row2.driver.status',
        'vehicle.cabin.window.row2.passenger.status',
        'vehicle.body.trunk.isOpen',
        'vehicle.body.trunk.isLocked',
        'vehicle.body.hood.isOpen',
        'vehicle.cabin.infotainment.navigation.currentLocation.latitude',
        'vehicle.cabin.infotainment.navigation.currentLocation.longitude',
    ],
];
