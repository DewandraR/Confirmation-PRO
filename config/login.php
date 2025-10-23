<?php

return [
    // Lama sesi aktif (menit) untuk countdown
    'session_minutes' => env('LOGIN_SESSION_MINUTES', 1),

    // Maksimal user yang boleh aktif bersamaan
    'max_active'      => env('LOGIN_MAX_ACTIVE', 1),
];
