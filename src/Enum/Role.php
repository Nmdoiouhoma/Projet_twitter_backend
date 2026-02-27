<?php

namespace App\Enum;

enum Role: string
{
    case USER = 'ROLE_USER';
    case ADMIN = 'ROLE_ADMIN';
    case EDITOR = 'ROLE_EDITOR';
}
