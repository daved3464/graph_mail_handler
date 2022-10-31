<?php

namespace Hollow3464\GraphMailHandler;

enum EqualityOperator: string
{
    case Equals = 'eq';
    case NotEquals =  'ne';
    case Not = 'not';
    case In = 'in';
    case Has = 'has';

    public function getType()
    {
        return FilterOperatorType::Equality;
    }

    public function fromString(string $op)
    {
        return match ($op) {
            '=' => self::Equals,
            '!=' => self::NotEquals,
            '!' => self::Not,
            'in' => self::In,
            'has' => self::Has
        };
    }
}
