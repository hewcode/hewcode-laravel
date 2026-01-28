<?php

namespace Hewcode\Hewcode\Lists\Schema;

use Illuminate\Support\Facades\Storage;

class ImageColumn extends Column
{
    protected ?int $width = null;
    protected ?int $height = null;
    protected bool $circular = false;
    protected ?string $disk = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Format state to convert storage paths to URLs
        $this->formatStateUsing(function ($state) {
            if (!$state) {
                return null;
            }

            $disk = $this->disk ?? config('filesystems.default');

            // If it's already a full URL, return as-is
            if (str_starts_with($state, 'http://') || str_starts_with($state, 'https://')) {
                return $state;
            }

            // Convert storage path to URL
            return Storage::disk($disk)->url($state);
        });
    }

    public function width(?int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function height(?int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function size(?int $size): static
    {
        $this->width = $size;
        $this->height = $size;

        return $this;
    }

    public function circular(bool $circular = true): static
    {
        $this->circular = $circular;

        return $this;
    }

    public function disk(string $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    protected function toFragment(object $record): mixed
    {
        return $this->getValue($record);
    }

    public function toData(): array
    {
        return array_merge(parent::toData(), [
            'columnType' => 'image',
            'width' => $this->width,
            'height' => $this->height,
            'circular' => $this->circular,
        ]);
    }
}
