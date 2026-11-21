<?php

declare(strict_types=1);

return [

    'ownership' => [
        'conflict' => 'More than one package registers the same name, and Statamic keeps only the last registration: :conflicts.',
        'unreadable' => 'The owner check could not read every registration, so a clean result would not cover them: :unreadable.',
    ],

];
