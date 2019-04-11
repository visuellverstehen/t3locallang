<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 't3locallang',
    'description' => 'An extension that analyzies and compares locallang files.',
    'category' => 'be',
    'author' => 'visuellverstehen',
    'author_email' => 'kontakt@visuellverstehen.de',
    'author_company' => 'visuellverstehen',
    'state' => 'beta',
    'version' => '0.2.0',
    'clearCacheOnLoad' => 0,
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-9.5.99',
        ]
    ]
];
