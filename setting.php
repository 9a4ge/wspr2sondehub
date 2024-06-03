<?php

return [
    // Uploader parameters (visible on Sondehub as the uploader)
    'uploader_call' => '9A4GE',

    // Log decoded spots ready for upload to local file on disk
    'logspots' => true,
    'logfile' => 'spotslog.csv',

    // Log raw WSPR database queries to file on disk
    'lograwspots' => true,
    'rawlogfile' => 'rawlog.csv',

    // Upload spots to Sondehub
    'uploadspots' => true,

    // Balloon parameters
    'balloons' => [
        [
            'payload' => 'AC3AU-2',
            'freq_band' => '14',
            'callsign_slot' => '0',
            'telemetry_slot' => '2',
            'flight_id_1' => '0',
            'flight_id_3' => '5',
            'ham_call' => 'AC3AU',
            'comment' => 'Yokohama, 7g FL, He',
            'detail' => 'Launch date: 2024-05-10 08:00z',
            'device' => 'Traquito, 20m band',
            'type' => 'Jetpack',
            'tracker_type' => 'traquito'
        ],
        [
            'payload' => 'AC3AU-11',
            'freq_band' => '28',
            'callsign_slot' => '6',
            'telemetry_slot' => '8',
            'flight_id_1' => '0',
            'flight_id_3' => '5',
            'ham_call' => 'AC3AU',
            'comment' => 'No data about balloon type',
            'detail' => 'Launch date: 2024-04-05 07:40z',
            'device' => 'Traquito, 20m band',
            'type' => 'Jetpack',
            'tracker_type' => 'traquito'
        ],
        [
            'payload' => '9A4GE-11',
            'freq_band' => '14',
            'callsign_slot' => '0',
            'telemetry_slot' => '2',
            'ham_call' => '9A4GE',
            'comment' => 'SAG, H2, 5g FL, 6g PL',
            'detail' => 'Launch date: 2024-04-28 04:45z',
            'device' => 'WSPR tracker, 20m band',
            'type' => 'ZachTek',
            'tracker_type' => 'zachtek1'
        ]
    ]
];
