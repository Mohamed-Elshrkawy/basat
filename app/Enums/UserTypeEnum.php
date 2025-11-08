<?php

namespace App\Enums;

use App\Traits\EnumTo;

enum UserTypeEnum:string
{
    use EnumTo;

    case Admin      = 'admin';

    case Customer     = 'customer';

    case Driver     = 'driver';
}
