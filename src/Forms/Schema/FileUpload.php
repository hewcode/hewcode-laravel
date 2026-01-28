<?php

namespace Hewcode\Hewcode\Forms\Schema;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FileUpload extends Field
{
    protected array $acceptedFileTypes = [];

    protected ?int $maxSize = null; // in kilobytes

    protected bool $multiple = false;

    protected ?int $maxFiles = null;

    protected bool $image = false;

    protected bool $enablePreview = true;

    protected ?string $disk = null;

    protected ?string $directory = null;

    protected bool $storeFileNames = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Format state for frontend display
        $this->formatStateUsing(function ($state) {
            if (! $state) {
                return $this->multiple ? [] : null;
            }

            $disk = $this->disk ?? config('filesystems.default');

            // Helper to convert path to file metadata
            $pathToMetadata = function ($path) use ($disk) {
                return [
                    'url' => Storage::disk($disk)->url($path),
                    'path' => $path,
                    'filename' => basename($path),
                    'size' => Storage::disk($disk)->exists($path) ? Storage::disk($disk)->size($path) : null,
                ];
            };

            // If it's a JSON string (multiple files stored), decode it
            if ($this->multiple && is_string($state)) {
                $paths = json_decode($state, true) ?? [];

                return array_map($pathToMetadata, $paths);
            }

            // Convert single file path to metadata
            if (is_string($state)) {
                return $pathToMetadata($state);
            }

            return $state;
        });

        // Handle file storage when saving
        $this->dehydrateStateUsing(function ($state) {
            if (! $state) {
                return null;
            }

            // If state is already a path (editing existing), return as-is
            if (is_string($state) && ! ($state instanceof UploadedFile)) {
                return $state;
            }

            // Handle single UploadedFile
            if ($state instanceof UploadedFile) {
                return $this->storeFile($state);
            }

            // Handle metadata object from frontend (single file)
            if (is_array($state) && isset($state['path']) && ! isset($state[0])) {
                // If no new file uploaded, keep existing path
                return $state['path'];
            }

            // Handle multiple files or array of metadata objects
            if (is_array($state)) {
                $paths = [];
                foreach ($state as $file) {
                    if ($file instanceof UploadedFile) {
                        $paths[] = $this->storeFile($file);
                    } elseif (is_string($file)) {
                        // Existing file path
                        $paths[] = $file;
                    } elseif (is_array($file) && isset($file['path'])) {
                        // Metadata object from frontend
                        $paths[] = $file['path'];
                    }
                }

                return $this->multiple ? json_encode($paths) : ($paths[0] ?? null);
            }

            return $state;
        });
    }

    public function acceptedFileTypes(array $types): static
    {
        $this->acceptedFileTypes = $types;

        // Normalize extensions
        $extensions = array_map(fn ($t) => ltrim($t, '.'), $types);

        $this->rules[] = function ($attribute, $value, $fail) use ($extensions) {
            $this->validateFiles($attribute, $value, $fail, function ($file) use ($attribute, $fail, $extensions) {
                $validator = Validator::make(
                    [$attribute => $file],
                    [$attribute => 'mimes:'.implode(',', $extensions)]
                );

                if ($validator->fails()) {
                    $fail($validator->errors()->first($attribute));
                }
            });
        };

        return $this;
    }

    public function maxSize(int $kilobytes): static
    {
        $this->maxSize = $kilobytes;

        $this->rules[] = function ($attribute, $value, $fail) use ($kilobytes) {
            $this->validateFiles($attribute, $value, $fail, function ($file) use ($attribute, $fail, $kilobytes) {
                $validator = Validator::make(
                    [$attribute => $file],
                    [$attribute => "max:$kilobytes"]
                );

                if ($validator->fails()) {
                    $fail($validator->errors()->first($attribute));
                }
            });
        };

        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

        if ($multiple) {
            $this->rules[] = 'array';
        }

        return $this;
    }

    public function maxFiles(int $count): static
    {
        $this->maxFiles = $count;

        if ($this->multiple) {
            $this->rules[] = "max:$count";
        }

        return $this;
    }

    public function image(): static
    {
        $this->image = true;
        $this->acceptedFileTypes(['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp']);

        $this->rules[] = function ($attribute, $value, $fail) {
            $this->validateFiles($attribute, $value, $fail, function ($file) use ($attribute, $fail) {
                $validator = Validator::make(
                    [$attribute => $file],
                    [$attribute => 'image']
                );

                if ($validator->fails()) {
                    $fail($validator->errors()->first($attribute));
                }
            });
        };

        return $this;
    }

    public function enablePreview(bool $enable = true): static
    {
        $this->enablePreview = $enable;

        return $this;
    }

    public function disk(string $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    public function directory(string $directory): static
    {
        $this->directory = $directory;

        return $this;
    }

    public function storeFileNames(bool $storeFileNames = true): static
    {
        $this->storeFileNames = $storeFileNames;

        return $this;
    }

    protected function getFieldType(): string
    {
        return 'file-upload';
    }

    protected function getFieldSpecificData(): array
    {
        return [
            'acceptedFileTypes' => $this->acceptedFileTypes,
            'maxSize' => $this->maxSize,
            'multiple' => $this->multiple,
            'maxFiles' => $this->maxFiles,
            'image' => $this->image,
            'enablePreview' => $this->enablePreview,
            'accept' => $this->buildAcceptString(),
        ];
    }

    protected function buildAcceptString(): string
    {
        if (empty($this->acceptedFileTypes)) {
            return $this->image ? 'image/*' : '*/*';
        }

        return implode(',', array_map(
            fn ($type) => '.'.ltrim($type, '.'),
            $this->acceptedFileTypes
        ));
    }

    protected function storeFile(UploadedFile $file): string
    {
        $disk = $this->disk ?? config('filesystems.default');
        $directory = $this->directory ?? 'uploads';

        if ($this->storeFileNames) {
            return $file->storeAs($directory, $file->getClientOriginalName(), $disk);
        }

        return $file->store($directory, $disk);
    }

    protected function validateFiles(string $attribute, mixed $value, callable $fail, callable $callback): void
    {
        // Skip validation for metadata objects (existing files)
        if (is_array($value) && isset($value['path'])) {
            return;
        }

        if (! $value) {
            return;
        }

        $items = ($this->multiple && is_array($value)) ? $value : [$value];

        foreach ($items as $item) {
            // Skip metadata
            if (is_array($item) && isset($item['path'])) {
                continue;
            }

            if (! ($item instanceof UploadedFile)) {
                $fail("The $attribute must be a file.");

                return;
            }

            $callback($item);
        }
    }
}
