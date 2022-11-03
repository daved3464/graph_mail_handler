<?php

namespace Hollow3464\GraphMailHandler;

class FilterBuilder
{
    private string $filters;

    public function __construct(
        private array $params
    ) {        
    }

    private function build()
    {
        $this->filters = join(' ', $this->params);
        return $this->filters;
    }
}
