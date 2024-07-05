<?php

namespace App\Enum;

enum PaymentMethodEnum: string {
    case SCOTIBANK = '0';
    case BANRESERVAS = '1';
    case POPULAR = '2';
    case EMERGENCIA = '3';
    case AHORRO = '4';
}
