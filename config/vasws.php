<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VAS Service Catalog
    |--------------------------------------------------------------------------
    |
    | Static catalog of services offered through the VAS Web Service. Keyed
    | by the `id` Selfcare/CCS pass as `serviceid`. Each service owns its own
    | database, named here by `connection` (see `config/database.php`), and
    | services are fully independent — a subscriber may hold any combination
    | of them at once. `package` must match the value stored in that service's
    | `profiles.package` column (max 8 characters).
    |
    | Adding a service is a matter of adding an entry here plus its
    | `DB_<NAME>_*` environment variables; no application code changes.
    |
    */

    'services' => [
        1 => [
            'package' => 'news',
            'connection' => 'news',
            'english_name' => 'News',
            'arabic_name' => 'الأخبار',
        ],
        2 => [
            'package' => 'sport',
            'connection' => 'sport',
            'english_name' => 'Sport',
            'arabic_name' => 'الرياضة',
        ],
    ],

];
