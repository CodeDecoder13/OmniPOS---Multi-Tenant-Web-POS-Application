<?php

namespace App\Enums;

enum PromotionType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';
    case BuyXGetY = 'buy_x_get_y';
    case Student = 'student';
    case PWD = 'pwd';
    case SeniorCitizen = 'senior_citizen';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Percentage',
            self::Fixed => 'Fixed Amount',
            self::BuyXGetY => 'Buy X Get Y',
            self::Student => 'Student Discount',
            self::PWD => 'PWD Discount',
            self::SeniorCitizen => 'Senior Citizen Discount',
        };
    }

    public function isPreset(): bool
    {
        return in_array($this, [self::Student, self::PWD, self::SeniorCitizen]);
    }
}
