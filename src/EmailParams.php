<?php

declare(strict_types=1);

namespace Hollow3464\GraphMailHandler;

final class EmailParams
{
    public function __construct(
        public readonly bool $withAttachments = false,
        public readonly bool $includeAttachments = false
    ) {}
}
