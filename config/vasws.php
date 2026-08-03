<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VAS Service Catalog
    |--------------------------------------------------------------------------
    |
    | Static catalog of services offered through the VAS Web Service. Keyed
    | by the `id` Selfcare/CCS pass as `serviceid`. `package` must match the
    | value stored in the `profiles.package` column (max 8 characters) for
    | that service.
    |
    */

    'services' => [
        1 => [
            'package' => 'news',
            'english_name' => 'News',
            'arabic_name' => 'الأخبار',
        ],
        2 => [
            'package' => 'sport',
            'english_name' => 'Sport',
            'arabic_name' => 'الرياضة',
        ],
    ],

];
