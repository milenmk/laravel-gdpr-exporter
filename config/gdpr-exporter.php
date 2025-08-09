<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The user model class that will be used for GDPR data export.
    | This should be the fully qualified class name of your user model.
    |
    */
    'user_model' => env('GDPR_USER_MODEL', 'App\Models\User'),

    /*
    |--------------------------------------------------------------------------
    | Safe Relations Detection
    |--------------------------------------------------------------------------
    |
    | Configure how relations are detected for export. You can choose between
    | 'reflection' (automatic detection) or 'whitelist' (explicit list).
    |
    */
    'relations_detection' => [
        'method' => env('GDPR_RELATIONS_METHOD', 'whitelist'), // 'reflection' or 'whitelist'

        /*
        |--------------------------------------------------------------------------
        | Whitelisted Relations
        |--------------------------------------------------------------------------
        |
        | When using 'whitelist' method, only these relations will be loaded
        | and exported. This is the safer approach as it prevents accidental
        | invocation of destructive methods.
        |
        */
        'whitelist' => [
            // Add your safe relation method names here
            // Example: 'posts', 'profile', 'roles', 'permissions'
        ],

        /*
        |--------------------------------------------------------------------------
        | Excluded Methods
        |--------------------------------------------------------------------------
        |
        | When using 'reflection' method, these method names will be excluded
        | from relation detection to prevent calling potentially dangerous methods.
        |
        */
        'excluded_methods' => [
            'delete',
            'destroy',
            'forceDelete',
            'restore',
            'save',
            'update',
            'create',
            'insert',
            'truncate',
            'drop',
            'refresh',
            'reset',
            'migrate',
            'seed',
            'logout',
            'login',
            'authenticate',
            'authorize',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Settings
    |--------------------------------------------------------------------------
    |
    | Configure various export behavior settings.
    |
    */
    'export' => [
        /*
        |--------------------------------------------------------------------------
        | Remove ID Fields
        |--------------------------------------------------------------------------
        |
        | Whether to remove ID fields from the exported data for privacy.
        |
        */
        'remove_ids' => env('GDPR_REMOVE_IDS', true),

        /*
        |--------------------------------------------------------------------------
        | Flatten Pivot Data
        |--------------------------------------------------------------------------
        |
        | Whether to flatten pivot table data in the export.
        |
        */
        'flatten_pivot' => env('GDPR_FLATTEN_PIVOT', true),
    ],
];
