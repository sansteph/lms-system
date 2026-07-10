<?php

return [
    'libreoffice_path' => env(
        'LIBREOFFICE_PATH',
        PHP_OS_FAMILY === 'Windows'
            ? 'C:\Program Files\LibreOffice\program\soffice.exe'
            : 'soffice'
    ),

    'timeout' => (int) env('LIBREOFFICE_TIMEOUT', 90),
];
