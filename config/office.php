<?php
// config/office.php
return [
    // Daftar kantor (pakai nilai dari .env)
    'sites' => [
        [
            'code'      => 'HQ',
            'name'      => env('OFFICE_NAME', 'Kantor Semarang'),
            'lat'       => (float) env('OFFICE_LAT', 0),
            'lng'       => (float) env('OFFICE_LNG', 0),
            'radius_m'  => (int) env('OFFICE_RADIUS_M', 100),
        ],
        // Tambah kantor kedua bila diisi
        array_filter([
            'code'      => 'BR1',
            'name'      => env('OFFICE2_NAME', 'Kantor Surabaya'),
            'lat'       => env('OFFICE2_LAT') !== null ? (float) env('OFFICE2_LAT') : null,
            'lng'       => env('OFFICE2_LNG') !== null ? (float) env('OFFICE2_LNG') : null,
            'radius_m'  => env('OFFICE2_RADIUS_M') !== null ? (int) env('OFFICE2_RADIUS_M') : null,
        ]) ?: null,
    ],
    'max_accuracy_m' => (int) env('OFFICE_MAX_ACC_M', 200),
];