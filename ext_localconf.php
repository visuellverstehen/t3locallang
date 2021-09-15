<?php

defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup('
    module.tx_t3locallang {
        view {
            templateRootPaths {
                10 = EXT:t3locallang/Resources/Private/Backend/Templates/
            }
            layoutRootPaths {
               10 = EXT:t3locallang/Resources/Private/Backend/Layouts/
            }
        }
    }
');
