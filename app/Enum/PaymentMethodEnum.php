<?php

namespace App\Enum;

enum PaymentMethodEnum: string {
    case SCOTIBANK = '0';
    case BANRESERVAS = '1';
    case POPULAR = '2';
}
