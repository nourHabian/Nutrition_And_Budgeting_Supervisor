<?php

namespace App\Enums;

enum PreferenceType:string
{
    case FAVORITE = 'favorite';
    case DISLIKE = 'dislike';
    case ALLERGY = 'allergy';
}