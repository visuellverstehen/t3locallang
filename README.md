[![Actions](https://github.com/visuellverstehen/t3locallang/workflows/TER/badge.svg)](https://github.com/visuellverstehen/t3locallang/actions)
[![Downloads](https://img.shields.io/packagist/dt/visuellverstehen/t3locallang.svg)](https://packagist.org/packages/visuellverstehen/t3locallang)

# t3locallang

This extension provides a simple BE module for admins, that will analyize locallang files based on the selected extension. It will work with files that follow a specific pattern: `[de].locallang.xlf`, located in the default extension language directory: `EXT:extension/Resources/Private/Language`.

## Important
This repository is work in progress.

## How to use
1. Install TYPO3 extension via [composer](https://packagist.org/packages/visuellverstehen/t3locallang), [TER](https://extensions.typo3.org/extension/t3locallang/) or download and install manually.
2. After installation a new module names `t3locallang` should appear under the admin tools group.
3. After selecting an extension, the module will collect data about missing entries and not used entries.
