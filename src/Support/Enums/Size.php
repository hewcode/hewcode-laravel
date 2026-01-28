<?php

namespace Hewcode\Hewcode\Support\Enums;

/**
 * Size enum for consistent sizing across components.
 *
 * Maps to Tailwind's max-width scale: xs, sm, md, lg, xl, 2xl, 3xl, 4xl, 5xl, 6xl, 7xl
 */
enum Size: string
{
    case EXTRA_SMALL = 'xs';
    case SMALL = 'sm';
    case MEDIUM = 'md';
    case LARGE = 'lg';
    case EXTRA_LARGE = 'xl';
    case TWO_XL = '2xl';
    case THREE_XL = '3xl';
    case FOUR_XL = '4xl';
    case FIVE_XL = '5xl';
    case SIX_XL = '6xl';
    case SEVEN_XL = '7xl';

    /**
     * Aliases for convenience.
     */
    public static function xs(): self
    {
        return self::EXTRA_SMALL;
    }

    public static function sm(): self
    {
        return self::SMALL;
    }

    public static function md(): self
    {
        return self::MEDIUM;
    }

    public static function lg(): self
    {
        return self::LARGE;
    }

    public static function xl(): self
    {
        return self::EXTRA_LARGE;
    }

    public static function _2xl(): self
    {
        return self::TWO_XL;
    }

    public static function _3xl(): self
    {
        return self::THREE_XL;
    }

    public static function _4xl(): self
    {
        return self::FOUR_XL;
    }

    public static function _5xl(): self
    {
        return self::FIVE_XL;
    }

    public static function _6xl(): self
    {
        return self::SIX_XL;
    }

    public static function _7xl(): self
    {
        return self::SEVEN_XL;
    }

    /**
     * Get all size values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Convert Size enum to string value.
     */
    public function toString(): string
    {
        return $this->value;
    }
}
