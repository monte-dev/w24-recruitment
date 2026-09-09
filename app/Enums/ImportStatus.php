<?php

namespace App\Enums;

enum ImportStatus: string
{
    case Success = 'success';
    case Partial = 'partial';
    case Failed = 'failed';
}
