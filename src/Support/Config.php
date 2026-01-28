<?php

namespace Hewcode\Hewcode\Support;

class Config
{
    protected array $config = [];

    public function __construct()
    {
        $this->config = [
            'default_panel' => 'app',
            'date_format' => 'M j, Y',
            'datetime_format' => 'M j, Y g:i A',
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function set(string $key, mixed $value): self
    {
        $this->config[$key] = $value;

        return $this;
    }

    public function setDateFormat(string $format): self
    {
        return $this->set('date_format', $format);
    }

    public function setDatetimeFormat(string $format): self
    {
        return $this->set('datetime_format', $format);
    }

    public function getDateFormat(): string
    {
        return $this->get('date_format');
    }

    public function getDatetimeFormat(): string
    {
        return $this->get('datetime_format');
    }

    public function setDefaultPanel(string $panel): self
    {
        return $this->set('default_panel', $panel);
    }

    public function getDefaultPanel(): string
    {
        return $this->get('default_panel');
    }

    public static function instance(): self
    {
        return app(self::class);
    }
}
