<?php

return [
    'tools_t3locallang' => [
        'parent' => 'tools',
        'access' => 'admin',
        'path' => '/module/tools/t3locallang',
        'labels' => 'LLL:EXT:t3locallang/Resources/Private/Language/locallang.xlf',
        'extensionName' => 'T3localang',
        'icon' => 'EXT:t3locallang/Resources/Public/Icons/module.svg',
        'routes' => [
            '_default' => [
                'target' => \VV\T3locallang\Controller\AnalyzeController::class . '::indexAction',
            ],
            'collect' => [
                'target' => \VV\T3locallang\Controller\AnalyzeController::class . '::collectAction',
            ],
            'export' => [
                'target' => \VV\T3locallang\Controller\AnalyzeController::class . '::exportAction',
                // 'methods' => ['POST'],
            ],
        ],
    ],
];
