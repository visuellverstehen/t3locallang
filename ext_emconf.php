<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 't3locallang',
    'description' => 'An extension that analyzies and compares locallang files.',
    'category' => 'be',
    'author' => 'visuellverstehen',
    'author_email' => 'kontakt@visuellverstehen.de',
    'author_company' => 'visuellverstehen',
    'state' => 'stable',
    'version' => '0.3.1',
    'clearCacheOnLoad' => 0,
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-11.5.99',
        ]
    ]
];
