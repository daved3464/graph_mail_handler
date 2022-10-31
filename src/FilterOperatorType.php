<?php

namespace Hollow3464\GraphMailHandler;

enum FilterOperatorType {
    case Equality;
    case Relational;
    case Lambda;
    case Conditional;
    case Functional;
}