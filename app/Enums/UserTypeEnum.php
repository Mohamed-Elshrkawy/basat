<?php

namespace App\Enums;

use App\Traits\EnumTo;

enum UserTypeEnum:string
{
    use EnumTo;

    case Admin      = 'admin';

    case Client     = 'client';

    case Driver     = 'driver';
}
