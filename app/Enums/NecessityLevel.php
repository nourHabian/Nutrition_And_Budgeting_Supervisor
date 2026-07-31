<?php

namespace App\Enums;

enum NecessityLevel:string
{
    case REQUIRED = 'إلزامي';
    case OPTIONAL = 'اختياري';
}