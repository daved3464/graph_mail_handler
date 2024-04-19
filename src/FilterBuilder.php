<?php

declare(strict_types=1);

namespace Hollow3464\GraphMailHandler;

final class FilterBuilder
{
    private string $filters;

    public function __construct(
        private array $params
    ) {}

    private function build()
    {
        $this->filters = join(' ', $this->params);
        return $this->filters;
    }
}
