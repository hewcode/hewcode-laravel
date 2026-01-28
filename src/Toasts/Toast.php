<?php

namespace Hewcode\Hewcode\Toasts;

use Hewcode\Hewcode\Hewcode;

class Toast
{
    protected ?string $title = null;
    protected ?string $message = null;
    protected ?string $type = null;
    protected ?int $duration = null;
    protected bool $dismissible = true;
    protected ToastPosition $position = ToastPosition::TOP_RIGHT;

    public static function make(): static
    {
        return new static();
    }

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function message(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function danger(): static
    {
        return $this->error();
    }

    public function error(): static
    {
        $this->title ??= __('hewcode::hewcode.toasts.error.title');
        $this->message ??= __('hewcode::hewcode.toasts.error.message');

        return $this->type('error');
    }

    public function success(): static
    {
        $this->title ??= __('hewcode::hewcode.toasts.success.title');
        $this->message ??= __('hewcode::hewcode.toasts.success.message');

        return $this->type('success');
    }

    public function info(): static
    {
        return $this->type('info');
    }

    public function warning(): static
    {
        return $this->type('warning');
    }

    public function duration(int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function dismissible(bool $dismissible = true): static
    {
        $this->dismissible = $dismissible;

        return $this;
    }

    public function position(ToastPosition $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function topRight(): static
    {
        return $this->position(ToastPosition::TOP_RIGHT);
    }

    public function topLeft(): static
    {
        return $this->position(ToastPosition::TOP_LEFT);
    }

    public function topCenter(): static
    {
        return $this->position(ToastPosition::TOP_CENTER);
    }

    public function bottomRight(): static
    {
        return $this->position(ToastPosition::BOTTOM_RIGHT);
    }

    public function bottomLeft(): static
    {
        return $this->position(ToastPosition::BOTTOM_LEFT);
    }

    public function bottomCenter(): static
    {
        return $this->position(ToastPosition::BOTTOM_CENTER);
    }

    public function toData(): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'duration' => $this->duration,
            'dismissible' => $this->dismissible,
            'position' => $this->position,
        ];
    }

    public function send(): void
    {
        Hewcode::shareWithResponse('toasts', null, $this->toData());
    }
}
