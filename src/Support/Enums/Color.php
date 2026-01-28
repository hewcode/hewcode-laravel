<?php

namespace Hewcode\Hewcode\Support\Enums;

/**
 * Color enum for consistent color usage across components.
 *
 * Supports button variants, badge variants, and background colors.
 */
enum Color: string
{
    case PRIMARY = 'primary';
    case SECONDARY = 'secondary';
    case DESTRUCTIVE = 'destructive';
    case DANGER = 'danger';
    case SUCCESS = 'success';
    case WARNING = 'warning';
    case INFO = 'info';
    case OUTLINE = 'outline';
    case GHOST = 'ghost';
    case LINK = 'link';

    /**
     * Get all color values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Convert Color enum to string value.
     */
    public function toString(): string
    {
        return $this->value;
    }
}
