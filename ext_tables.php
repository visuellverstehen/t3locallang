<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'VV.' . $_EXTKEY,
        'tools',
        'Pi1',
        '',
        [
            'Analyze' => 'index, collect',
        ],
        [
            'access' => 'admin',
            'icon' => 'EXT:t3locallang/Resources/Public/Icons/module.svg',
            'labels' => 'LLL:EXT:t3locallang/Resources/Private/Language/locallang.xlf',
        ]
    );
}
