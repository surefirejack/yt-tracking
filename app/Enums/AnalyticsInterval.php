<?php

namespace App\Enums;

enum AnalyticsInterval: string
{
    case TWENTY_FOUR_HOURS = '24h';
    case SEVEN_DAYS = '7d';
    case THIRTY_DAYS = '30d';
    case NINETY_DAYS = '90d';
    case ONE_YEAR = '1y';
    case MONTH_TO_DATE = 'mtd';
    case QUARTER_TO_DATE = 'qtd';
    case YEAR_TO_DATE = 'ytd';
    case ALL_TIME = 'all';

    public function label(): string
    {
        return match($this) {
            self::TWENTY_FOUR_HOURS => '24 hours',
            self::SEVEN_DAYS => '7 days',
            self::THIRTY_DAYS => '30 days',
            self::NINETY_DAYS => '90 days',
            self::ONE_YEAR => '1 year',
            self::MONTH_TO_DATE => 'Month to date',
            self::QUARTER_TO_DATE => 'Quarter to date',
            self::YEAR_TO_DATE => 'Year to date',
            self::ALL_TIME => 'All time',
        };
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }

    public static function default(): self
    {
        return self::THIRTY_DAYS;
    }
} 