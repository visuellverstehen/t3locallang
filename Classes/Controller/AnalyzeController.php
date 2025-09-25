<?php

declare(strict_types=1);

namespace VV\T3locallang\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Utility\CsvUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use VV\T3locallang\Domain\Model\Translation;

#[\TYPO3\CMS\Backend\Attribute\AsController]
class AnalyzeController extends ActionController
{
    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly LocalizationFactory $localizationFactory,
        private readonly IconFactory $iconFactory,
        private readonly UriBuilder $backendUriBuilder,
    ) {}

    public function indexAction(ServerRequest $request): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->assign('extensions', ExtensionManagementUtility::getLoadedExtensionListArray());

        return $moduleTemplate->renderResponse('Backend/Index');
    }

    /**
     * Language handling withing the core can be found here:
     * @see \TYPO3\CMS\Core\Localization\LocalizationFactory
     */
    public function collectAction(ServerRequest $request): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->registerDocHeaderButtons($moduleTemplate);
        $extKey = $request->getQueryParams()['extension'];

        $moduleTemplate->assignMultiple([
            'translations' => $this->collectTranslations($extKey),
            'locales' => $this->findLocales($extKey),
        ]);

        return $moduleTemplate->renderResponse('Backend/Collect');
    }

    public function exportAction(ServerRequest $request): ResponseInterface
    {
        $extKey = $request->getQueryParams()['extension'];
        $locales = $this->findLocales($extKey);

        $content = CsvUtility::csvValues(
            array_merge([''], $locales)
        );

        foreach ($this->collectTranslations($extKey) as $translation) {
            if ($translation->isUsed()) {
                $row = [
                    $translation->getKey(),
                ];

                foreach ($locales as $locale) {
                    $row[$locale] = $translation->getTranslations()[$locale];
                }

                $content .= PHP_EOL . CsvUtility::csvValues($row);
            }
        }

        return $this->responseFactory->createResponse()
            ->withHeader('Cache-Control', 'private')
            ->withHeader('Content-Type', 'application/octet-stream; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="export.csv"')
            ->withBody($this->streamFactory->createStream($content));
    }

    protected function collectTranslations(string $extKey): array
    {
        $locales = $this->findLocales($extKey);
        $translations = [];
        $locallangPath = GeneralUtility::getFileAbsFileName(
            'EXT:' . GeneralUtility::camelCaseToLowerCaseUnderscored($extKey) . '/Resources/Private/Language/'
        );
        $locallangFiles = glob($locallangPath . '*locallang.xlf');

        $defaultTranslations = $this->localizationFactory->getParsedData(
            $locallangPath . 'locallang.xlf',
            'default'
        )['default'];

        foreach ($defaultTranslations as $key => $defaultTranslationEntry) {
            $translation = new Translation();
            $translation->setKey($key);
            $translation->addTranslation($defaultTranslationEntry[0]['source']);

            $translations[$key] = $translation;
        }

        foreach ($locallangFiles as $file) {
            $locale = str_replace([$locallangPath, '.locallang.xlf'], '', $file);

            if ($locale !== 'locallang.xlf') {
                $localeTranslanslations = $this->localizationFactory->getParsedData(
                    $file,
                    $locale
                )[$locale];

                foreach ($localeTranslanslations as $key => $localeTranslationEntry) {
                    if (array_key_exists($key, $translations)) {
                        $translations[$key]->addTranslation($localeTranslationEntry[0]['target'], $locale);
                    } else {
                        $translation = new Translation();
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

        exec('grep -r -E "' . implode('|', array_keys($translations)) . '" /var/www/html/src/ --include="*.php" --include="*.html" --include="*.tsconfig" --include="*.typoscript" --include="*.txt"', $hits);

        foreach ($hits as $hit) {
            array_filter($translations, function ($translation) use ($hit) {
                if (strpos($hit, $translation->getKey())) {
                    $translation->setUsed(true);
                    return $translation;
                }
            });
        }

        return $translations;
    }

    protected function findLocales(string $extKey): array
    {
        $locales = ['default'];
        $locallangPath = GeneralUtility::getFileAbsFileName(
            'EXT:' . GeneralUtility::camelCaseToLowerCaseUnderscored($extKey) . '/Resources/Private/Language/'
        );
        $locallangFiles = glob($locallangPath . '*.locallang.xlf');

        foreach ($locallangFiles as $file) {
            $locale = str_replace([$locallangPath, '.locallang.xlf'], '', $file);

            if ($locale !== 'locallang.xlf') {
                $locales[] = $locale;
            }
        }

        return array_unique($locales);
    }

    protected function registerDocHeaderButtons($moduleTemplate): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $lang = $GLOBALS['LANG'];

        // CLOSE button
        $closeButton = $buttonBar->makeLinkButton();
        $closeButton->setShowLabelText(true);
        $closeButton->setHref((string)$this->backendUriBuilder->buildUriFromRoute('tools_t3locallang'));
        $closeButton->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'));
        $closeButton->setIcon(
            $this->iconFactory->getIcon(
                'actions-close',
                Icon::SIZE_SMALL
            )
        );

        // RELOAD button
        $reloadButton = $buttonBar->makeLinkButton();
        $reloadButton->setShowLabelText(true);
        $reloadButton->setHref(
            (string)$this->backendUriBuilder->buildUriFromRoute('tools_t3locallang.collect', [
                'action' => 'collect',
                'extension' => 'pohlco',
            ])
        );
        $reloadButton->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'));
        $reloadButton->setIcon(
            $this->iconFactory->getIcon(
                'actions-refresh',
                Icon::SIZE_SMALL
            )
        );

        // EXPORT button
        $exportButton = $buttonBar->makeLinkButton();
        $exportButton->setShowLabelText(true);
        $exportButton->setHref(
            (string)$this->backendUriBuilder->buildUriFromRoute('tools_t3locallang.export', [
                'extension' => 'pohlco',
            ])
        );
        $exportButton->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.csv'));
        $exportButton->setIcon(
            $this->iconFactory->getIcon(
                'actions-document-export-csv',
                Icon::SIZE_SMALL
            )
        );

        $buttonBar->addButton($closeButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
        $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
        $buttonBar->addButton($exportButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }
}
