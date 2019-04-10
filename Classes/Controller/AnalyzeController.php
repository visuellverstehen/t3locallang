<?php
declare(strict_types=1);

namespace VV\T3locallang\Controller;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

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
     * @see \TYPO3\CMS\Core\Localization\LocalizationFactory
     */
    public function collectAction()
    {
        $this->registerDocHeaderButtons();
        $extKey = $this->request->getArgument('extension');
        $locallangPath = GeneralUtility::getFileAbsFileName(
            'EXT:' . GeneralUtility::camelCaseToLowerCaseUnderscored($extKey) . '/Resources/Private/Language/'
        );
        $locallangFiles = GeneralUtility::getFilesInDir($locallangPath, 'xlf');
        $locales = [];

        foreach ($locallangFiles as $file) {
            $locale = str_replace('.locallang.xlf', '', $file);

            if ($locale === 'locallang.xlf') {
                $locale = 'default';
            }

            $locales[$locale]['notUsed'] = [];
            $locales[$locale]['missing'] = [];
            $locales[$locale]['entries'] = $this->localizationFactory->getParsedData(
                $locallangPath . $file,
                $locale
            )['default'];

            foreach ($locales[$locale]['entries'] as $key => $entry) {
                exec('grep -r "' . $key . '" ' . Environment::getExtensionsPath() . '/' . $extKey . ' --exclude="*.xlf"', $output);

                if (empty($output)) {
                    $locales[$locale]['notUsed'][] = $key;
                }

                $output = null;
            }
        }

        $default = $locales['default'];

        foreach ($locales as $key => $data) {
            if ($key !== 'default') {
                if ($default['entries'] === null || $data['entries'] === null) {
                    $this->addFlashMessage('Couldn\'t compare ' . $file, '', AbstractMessage::WARNING);
                } else {
                    $locales[$key]['missing'] = array_diff(
                        array_keys($default['entries']),
                        array_keys($data['entries'])
                    );

                    $locales[$key]['missing'] = array_diff($locales[$key]['missing'], $default['notUsed']);
                }
            }
        }

        $this->view->assign('locales', $locales);
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
            $lang = $this->getLanguageService();
            $pluginName = $this->request->getPluginName();

            // CLOSE button
            $closeButton = $buttonBar->makeLinkButton();
            $closeButton->setShowLabelText(true);
            $closeButton->setHref((string)$uriBuilder->buildUriFromRoute($pluginName));
            $closeButton->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'));
            $closeButton->setIcon(
                $this->view->getModuleTemplate()->getIconFactory()->getIcon(
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
                $this->view->getModuleTemplate()->getIconFactory()->getIcon(
                    'actions-refresh',
                    Icon::SIZE_SMALL
                )
            );

            $buttonBar->addButton($closeButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
            $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
        }
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
