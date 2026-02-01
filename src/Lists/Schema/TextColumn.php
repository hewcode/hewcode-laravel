<?php

namespace Hewcode\Hewcode\Lists\Schema;

use Carbon\Carbon;
use DateTimeInterface;
use Hewcode\Hewcode\Hewcode;

class TextColumn extends Column
{
    /**
     * Format the column value as a date using the configured date format.
     */
    public function date(?string $format = null, bool $relative = false): static
    {
        return $this->formatStateUsing(
            $this->formatDate($format ?? Hewcode::config()->getDateFormat(), $relative)
        );
    }

    /**
     * Format the column value as a datetime using the configured datetime format.
     */
    public function datetime(?string $format = null, bool $relative = false): static
    {
        return $this->formatStateUsing(
            $this->formatDate($format ?? Hewcode::config()->getDatetimeFormat(), $relative)
        );
    }

    /**
     * Limit the column value to a specified number of characters.
     */
    public function limit(int $length = 50, string $end = '...'): static
    {
        return $this->formatStateUsing(function ($state) use ($length, $end) {
            if ($state === null) {
                return null;
            }

            $state = (string) $state;

            if (mb_strlen($state) <= $length) {
                return $state;
            }

            return mb_substr($state, 0, $length).$end;
        });
    }

    /**
     * Create a closure for formatting date/datetime values.
     * This can be reused by third-party developers in different contexts.
     *
     * @param  string  $format  The format string to use
     * @param  bool  $relative  Whether to use relative time format
     */
    protected function formatDate(string $format, bool $relative): \Closure
    {
        return function ($state) use ($format, $relative) {
            if ($state === null) {
                return null;
            }

            $carbonDate = null;

            if ($state instanceof DateTimeInterface) {
                $carbonDate = Carbon::instance($state);
            } elseif (is_string($state)) {
                try {
                    $carbonDate = Carbon::parse($state);
                } catch (\Exception $e) {
                    return $state;
                }
            }

            if ($carbonDate === null) {
                return $state;
            }

            return $relative ? $carbonDate->diffForHumans() : $carbonDate->format($format);
        };
    }
}
