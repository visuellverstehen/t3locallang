<?php

declare(strict_types=1);

use VV\T3locallang\Controller\AnalyzeController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

ExtensionUtility::registerModule(
    'T3locallang',
    'tools',
    'Pi1',
    '',
    [
        AnalyzeController::class => 'index, collect, export',
    ],
    [
        'access' => 'admin',
        'icon' => 'EXT:t3locallang/Resources/Public/Icons/module.svg',
        'labels' => 'LLL:EXT:t3locallang/Resources/Private/Language/locallang.xlf',
    ]
);
