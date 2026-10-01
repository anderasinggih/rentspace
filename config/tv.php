<?php

return [

    'token' => env('TV_ACCESS_TOKEN', ''),

    'cache_minutes' => (int) env('TV_CACHE_MINUTES', 30),

    'refresh_seconds' => (int) env('TV_REFRESH_SECONDS', 10),

    /*
    | type = channel  -> 15 video terbaru dari channel resmi artis
    | type = playlist -> 15 video terbaru dari playlist
    | Semua ID sudah diverifikasi lewat RSS feed resmi YouTube.
    */
    'sources' => [
        ['name' => 'Drake',          'type' => 'channel',  'id' => 'UCByOQJjav0CUDwxCk-jVNRQ'],
        ['name' => 'Olivia Rodrigo', 'type' => 'channel',  'id' => 'UCy3zgWom-5AGypGX_FVTKpg'],
        ['name' => 'NIKI',           'type' => 'channel',  'id' => 'UCaAWRu53UW815hGTmxvHFbA'],
        ['name' => 'Rich Brian',     'type' => 'channel',  'id' => 'UCkWu5WFf4EYsiV0zepVJ_ww'],
        ['name' => 'Billie Eilish',  'type' => 'channel',  'id' => 'UCiGm_E4ZwYSHV3bcW1pnSeQ'],
        ['name' => '88rising',       'type' => 'channel',  'id' => 'UCZW5lIUz93q_aZIkJPAC0IQ'],
        ['name' => 'Pop 2026',       'type' => 'playlist', 'id' => 'PLDIoUOhQQPlXqz5QZ3dx-lh_p6RcPeKjv'],
        ['name' => 'New Pop 2026',   'type' => 'playlist', 'id' => 'PLDIoUOhQQPlUDNpjEYTTE9tb-QvUHUSMt'],
    ],

];