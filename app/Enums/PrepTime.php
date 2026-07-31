<?php

namespace App\Enums;

enum PrepTime:string
{
    case SHORT = 'قليل';
    case MEDIUM = 'متوسط';
    case LONG = 'طويل';
}