<?php

namespace App\Enums;

enum Seasonality:string
{
    case SUMMER = 'الصيف';
    case SPRINT = 'الربيع';
    case WINTER = 'الشتاء';
    case AUTUMN = 'الخريف';
    case ALWAYS = 'مدار السنة';
}