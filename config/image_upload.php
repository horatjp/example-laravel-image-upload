<?php

return [
    'disk' => 'public',
    'path' => 'images',
    'temp_path' => 'temp',
    'thumbnails' => [
        'small' => ['width' => 150, 'height' => 150],
        'medium' => ['width' => 300, 'height' => 300],
        'large' => ['width' => 800, 'height' => 800],
    ],
    'default_size' => 'medium',
    'max_file_size' => 2048, // KB
];
