<?php

namespace Drupal\wmmedia\Service;

class FormOptions
{
    public const CONTEXT_OVERVIEW = 'overview';
    public const CONTEXT_BROWSER = 'browser';

    /** @var string */
    protected $context;
    /** @var bool */
    protected $operations;
    /** @var bool */
    protected $selectable;
    /** @var bool */
    protected $showUsage;
    /** @var bool */
    protected $multiple;
    /** @var int */
    protected $pagerLimit;

    public function __construct(
        bool $operations,
        bool $selectable,
        bool $multiple,
        bool $showUsage,
        string $context,
        int $pagerLimit = FileRepository::PAGER_LIMIT
    ) {
        $this->operations = $operations;
        $this->selectable = $selectable;
        $this->multiple = $multiple;
        $this->showUsage = $showUsage;
        $this->context = $context;
        $this->pagerLimit = $pagerLimit;
    }

    public function showOperations(): bool
    {
        return $this->operations;
    }

    public function setShowOperations(bool $value): self
    {
        $this->operations = $value;

        return $this;
    }

    public function isSelectable(): bool
    {
        return $this->selectable;
    }

    public function setSelectable(bool $value): self
    {
        $this->selectable = $value;

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function setMultiple(bool $value): self
    {
        $this->multiple = $value;

        return $this;
    }

    public function showUsage(): bool
    {
        return $this->showUsage;
    }

    public function setShowUsage(bool $value): self
    {
        $this->showUsage = $value;

        return $this;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function setContext(string $value): self
    {
        $this->context = $value;

        return $this;
    }

    public function getPagerLimit(): int
    {
        return $this->pagerLimit;
    }

    public function setPagerLimit(int $value): self
    {
        $this->pagerLimit = $value;

        return $this;
    }

    public static function createForOverview(): self
    {
        return new static(
            true,
            false,
            false,
            true,
            self::CONTEXT_OVERVIEW
        );
    }

    public static function createForBrowser(): self
    {
        return new static(
            false,
            true,
            false,
            false,
            self::CONTEXT_BROWSER,
            10
        );
    }
}
