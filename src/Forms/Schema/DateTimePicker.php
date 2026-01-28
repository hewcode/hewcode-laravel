<?php

namespace Hewcode\Hewcode\Forms\Schema;

class DateTimePicker extends Field
{
    protected bool $time = true;
    protected bool $date = true;
    protected ?string $format = null;
    protected bool $native = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatStateUsing(function ($state) {
            if ($state === null) {
                return null;
            }

            if ($this->date && $this->time) {
                return date('Y-m-d H:i:s', strtotime($state));
            }

            if ($this->time) {
                return date('H:i:s', strtotime($state));
            }

            return date('Y-m-d', strtotime($state));
        });
    }

    public function time(bool $time = true): static
    {
        $this->time = $time;

        return $this;
    }

    public function date(bool $date = true): static
    {
        $this->date = $date;
        return $this;
    }

    public function format(string $format): static
    {
        $this->format = $format;
        return $this;
    }

    public function native(bool $native = true): static
    {
        $this->native = $native;
        return $this;
    }

    protected function getFieldType(): string
    {
        return match (true) {
            $this->time && $this->date => 'datetime-picker',
            $this->time => 'time-picker',
            default => 'date-picker',
        };
    }

    protected function getFieldSpecificData(): array
    {
        return [
            'time' => $this->time,
            'date' => $this->date,
            'format' => $this->format,
            'native' => $this->native,
        ];
    }
}
