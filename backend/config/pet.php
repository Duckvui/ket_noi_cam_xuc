<?php

return [
    'timezone' => env('PET_TIMEZONE', 'Asia/Ho_Chi_Minh'),
    'cooldown' => 10,
    'hanh_dong' => [
        'vuot_ve' => ['ten' => 'Vuốt ve', 'diem' => 5, 'loai' => 'Do_Danh'],
        'an_ui' => ['ten' => 'An ủi', 'diem' => 6, 'loai' => 'Do_Danh'],
        'om' => ['ten' => 'Ôm', 'diem' => 8, 'loai' => 'Om'],
        'choi_cung' => ['ten' => 'Chơi cùng', 'diem' => 10, 'loai' => 'Choi_Cung'],
        'cho_an' => ['ten' => 'Cho ăn', 'diem' => 5, 'loai' => 'Cho_An'],
        'nghi_ngoi' => ['ten' => 'Nghỉ ngơi', 'diem' => 4, 'loai' => 'Do_Danh'],
    ],
];
