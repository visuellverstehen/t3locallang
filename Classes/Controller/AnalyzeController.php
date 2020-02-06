<?php
declare(strict_types=1);

namespace VV\T3locallang\Controller;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Utility\CsvUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use VV\T3locallang\Domain\Model\Translation;

class AnalyzeController extends ActionController
{
    /**
     * @var BackendTemplateView
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * @var \TYPO3\CMS\Core\Localization\LocalizationFactory
     */
    protected $localizationFactory = null;

    public function __construct()
    {
        $this->localizationFactory = GeneralUtility::makeInstance(LocalizationFactory::class);
    }

    public function indexAction()
    {
        $extensions = ExtensionManagementUtility::getLoadedExtensionListArray();

        $this->view->assign('extensions', $extensions);
    }

    /**
     * Language handling withing the core can be found here:
     * @see \TYPO3\CMS\Core\Localization\LocalizationFactory
     */
    public function collectAction()
    {
        $this->registerDocHeaderButtons();
        $extKey = $this->request->getArgument('extension');

        $this->view->assign(
            'translations',
            $this->collectTranslations($extKey)
        );
        $this->view->assign(
            'locales',
            $this->findLocales($extKey)
        );
    }

    public function exportAction()
    {
        $this->view = null;

        $extKey = $this->request->getArgument('extension');
        $locales = $this->findLocales($extKey);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=export.csv');

        echo CsvUtility::csvValues(
            array_merge([''], $locales)
        );

        foreach ($this->collectTranslations($extKey) as $translation) {
            if ($translation->isUsed()) {
                $row = [
                    $translation->getKey()
                ];

                foreach ($locales as $locale) {
                    $row[$locale] = $translation->getTranslations()[$locale];
                }

                echo PHP_EOL . CsvUtility::csvValues($row);
            }
        }

        die();
    }

    /**
     * @param string $extKey
     * @return array
     */
    protected function collectTranslations(string $extKey): array
    {
        $locales = $this->findLocales($extKey);
        $translations = [];
        $locallangPath = GeneralUtility::getFileAbsFileName(
            'EXT:' . GeneralUtility::camelCaseToLowerCaseUnderscored($extKey) . '/Resources/Private/Language/'
        );
        $locallangFiles = GeneralUtility::getFilesInDir($locallangPath, 'xlf');

        $defaultTranslations = $this->localizationFactory->getParsedData(
            $locallangPath . 'locallang.xlf',
            'default'
        )['default'];

        foreach ($defaultTranslations as $key => $defaultTranslationEntry) {
            $translation = new Translation;
            $translation->setKey($key);
            $translation->addTranslation($defaultTranslationEntry[0]['source']);

            $translations[$key] = $translation;
        }

        foreach ($locallangFiles as $file) {
            $locale = str_replace('.locallang.xlf', '', $file);

            if ($locale !== 'locallang.xlf') {
                $localeTranslanslations = $this->localizationFactory->getParsedData(
                    $locallangPath . $file,
                    $locale
                )[$locale];

                foreach ($localeTranslanslations as $key => $localeTranslationEntry) {
                    if ($translations[$key]) {
                        $translations[$key]->addTranslation($localeTranslationEntry[0]['target'], $locale);
                    } else {
                        $translation = new Translation;
                        $translation->setKey($key);
                        $translation->setTranslations([
                            'default' => $localeTranslationEntry[0]['source'],
                            $locale => $localeTranslationEntry[0]['target'],
                        ]);

                        $translations[$key] = $translation;
                    }
                }
            }
        }

        $labelKeys = array_map(function($o) {
            return $o->getKey();
        }, $translations);

        exec('grep -r -E "' . implode('|', $labelKeys) . '" ' . Environment::getExtensionsPath() . '/' . $extKey . ' --exclude="*.xlf" --exclude="*.js" --exclude="*.css" --exclude="*.scss"', $hits);

        foreach ($hits as $hit) {
            array_filter($translations, function($translation) use ($hit) {
                if(strpos($hit, $translation->getKey())) {
                    $translation->setUsed(true);
                    return $translation;
                }
            });
        }

        return $translations;
    }

    /**
     * @param string $extKey
     * @return array
     */
    protected function findLocales(string $extKey): array
    {
        $locales = ['default'];
        $locallangPath = GeneralUtility::getFileAbsFileName(
            'EXT:' . GeneralUtility::camelCaseToLowerCaseUnderscored($extKey) . '/Resources/Private/Language/'
        );
        $locallangFiles = GeneralUtility::getFilesInDir($locallangPath, 'xlf');

        foreach ($locallangFiles as $file) {
            $locale = str_replace('.locallang.xlf', '', $file);

            if ($locale !== 'locallang.xlf') {
                $locales[] = $locale;
            }
        }

        return $locales;
    }

    /**
     * Registers the buttons into the docheader
     *
     * @see \TYPO3\CMS\Beuser\Controller\PermissionController->registerDocHeaderButtons()
     * @throws \InvalidArgumentException
     */
    protected function registerDocHeaderButtons()
    {
        if ($this->request->getControllerActionName() === 'collect') {
            $uriBuilder = $this->objectManager->get(UriBuilder::class);
            $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
            $lang = $GLOBALS['LANG'];
            $pluginName = $this->request->getPluginName();
            $iconFactory = $this->view->getModuleTemplate()->getIconFactory();

            // CLOSE button
            $closeButton = $buttonBar->makeLinkButton();
            $closeButton->setShowLabelText(true);
            $closeButton->setHref((string)$uriBuilder->buildUriFromRoute($pluginName));
            $closeButton->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'));
            $closeButton->setIcon(
                $iconFactory->getIcon(
                    'actions-close',
                    Icon::SIZE_SMALL
                )
            );

            // RELOAD button
            $reloadButton = $buttonBar->makeLinkButton();
            $reloadButton->setShowLabelText(true);
            $reloadButton->setHref(
                (string)$uriBuilder->buildUriFromRoute($pluginName, [
                    'tx_' . $this->request->getControllerExtensionKey() . '_' . strtolower($pluginName) . '[controller]' => 'Analyze',
                    'tx_' . $this->request->getControllerExtensionKey() . '_' . strtolower($pluginName) . '[action]' => 'collect',
                    'tx_' . $this->request->getControllerExtensionKey() . '_' . strtolower($pluginName) . '[extension]' => $this->request->getArgument('extension'),
                ])
            );
            $reloadButton->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'));
            $reloadButton->setIcon(
                $iconFactory->getIcon(
                    'actions-refresh',
                    Icon::SIZE_SMALL
                )
            );

            // EXPORT button
            $exportButton = $buttonBar->makeLinkButton();
            $exportButton->setShowLabelText(true);
            $exportButton->setHref(
                (string)$uriBuilder->buildUriFromRoute($pluginName, [
                    'tx_' . $this->request->getControllerExtensionKey() . '_' . strtolower($pluginName) . '[controller]' => 'Analyze',
                    'tx_' . $this->request->getControllerExtensionKey() . '_' . strtolower($pluginName) . '[action]' => 'export',
                    'tx_' . $this->request->getControllerExtensionKey() . '_' . strtolower($pluginName) . '[extension]' => $this->request->getArgument('extension'),
                ])
            );
            $exportButton->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.csv'));
            $exportButton->setIcon(
                $iconFactory->getIcon(
                    'actions-document-export-csv',
                    Icon::SIZE_SMALL
                )
            );

            $buttonBar->addButton($closeButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
            $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
            $buttonBar->addButton($exportButton, ButtonBar::BUTTON_POSITION_RIGHT);
        }
    }
}
