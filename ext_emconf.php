<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 't3locallang',
    'description' => 'An extension that analyzies and compares locallang files.',
    'category' => 'be',
    'author' => 'visuellverstehen',
    'author_email' => 'hello@visuellverstehen.de',
    'author_company' => 'visuellverstehen',
    'state' => 'stable',
    'version' => '0.4.0',
    'clearCacheOnLoad' => 0,
    'constraints' => [
        'depends' => [
            'typo3' => '13.0.0-13.4.99',
        ],
    ],
];
