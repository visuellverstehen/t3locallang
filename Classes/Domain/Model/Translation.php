<?php
declare(strict_types=1);

namespace VV\T3locallang\Domain\Model;

class Translation
{
    /**
     * @var string
     */
    protected $key = '';

    /**
     * @var array
     */
    protected $translations = [];

    /**
     * @var bool
     */
    protected $used = false;

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey(string $key)
    {
        $this->key = $key;
    }

    /**
     * @return array
     */
    public function getTranslations(): array
    {
        return $this->translations;
    }

    /**
     * @param string $translation
     * @param string $language
     */
    public function addTranslation(string $translation, string $language = 'default')
    {
        $this->translations[$language] = $translation;
    }

    /**
     * @param array $translations
     */
    public function setTranslations(array $translations)
    {
        $this->translations = $translations;
    }

    /**
     * @return bool
     */
    public function isUsed(): bool
    {
        return $this->used;
    }

    /**
     * @param bool $used
     */
    public function setUsed(bool $used)
    {
        $this->used = $used;
    }
}
