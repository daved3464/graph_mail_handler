<?php

namespace Hollow3464\GraphMailHandler;

class EmailParams
{
    public function __construct(
        public readonly bool $withAttachments,
        public readonly bool $includeAttachments  
    )
    {
    }
}
