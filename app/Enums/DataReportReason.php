<?php

namespace App\Enums;

enum DataReportReason: string
{
    case Missing = 'missing';
    case Duplicate = 'duplicate';
    case FalseData = 'false_data';
    case Other = 'other';
}
