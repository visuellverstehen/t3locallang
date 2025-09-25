<?php

declare(strict_types=1);

namespace VV\T3locallang\Domain\Model;

class Translation
{
    protected string $key = '';
    protected array $translations = [];
    protected bool $used = false;

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function addTranslation(string $translation, string $language = 'default'): void
    {
        $this->translations[$language] = $translation;
    }

    public function setTranslations(array $translations): void
    {
        $this->translations = $translations;
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    public function setUsed(bool $used): void
    {
        $this->used = $used;
    }
}
