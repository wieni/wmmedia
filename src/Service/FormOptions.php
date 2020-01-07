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

    public function isSelectable(): bool
    {
        return $this->selectable;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function showUsage(): bool
    {
        return $this->showUsage;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function getPagerLimit(): int
    {
        return $this->pagerLimit;
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
