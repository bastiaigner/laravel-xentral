<?php

namespace Bastiaigner\LaravelXentral;

enum FilterOperator: string
{
    /**
     * Filter operators taken from https://developer.xentral.com/reference/filtering-sorting-pagination
     */
    case Equals = 'equals';
    case NotEquals = 'notEquals';
    case In = 'in';
    case NotIn = 'notIn';
    case LesserThan = 'lesserThan';
    case LesserThanOrEqual = 'lesserThanOrEqual';
    case GreaterThan = 'greaterThan';
    case GreaterThanOrEqual = 'greaterThanOrEqual';
    case StartsWith = 'startsWith';
    case EndsWith = 'endsWith';
    case Between = 'between';
}
