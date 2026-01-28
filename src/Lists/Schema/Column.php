<?php

namespace Hewcode\Hewcode\Lists\Schema;

use Closure;
use Hewcode\Hewcode\Concerns;
use Hewcode\Hewcode\Contracts;
use Hewcode\Hewcode\Fragments;
use Hewcode\Hewcode\Support\Component;
use Hewcode\Hewcode\Support\Enums\Color;
use Illuminate\Database\Eloquent\Model;

abstract class Column extends Component implements Contracts\HasVisibility
{
    use Concerns\HasVisibility;
    use Concerns\HasLabel;

    protected bool $sortable = false;
    protected bool $searchable = false;
    protected ?Closure $getStateUsing = null;
    protected ?Closure $formatStateUsing = null;
    protected ?string $sortField = null;
    protected ?string $searchField = null;
    protected bool $wrap = false;
    protected bool $badge = false;
    protected ?string $badgeVariant = null;
    protected ?Closure $colorUsing = null;
    protected ?Closure $beforeUsing = null;
    protected ?Closure $afterUsing = null;
    protected ?Closure $omitUsing = null;
    protected ?Model $model = null;
    protected bool $togglable = false;
    protected bool $isToggledHiddenByDefault = false;
    protected string|Closure|null $iconUsing = null;
    protected string $iconPosition = 'before';
    protected int $iconSize = 16;
    protected string $cardRole = 'content';

    public function __construct()
    {
        $this->setUp();
    }

    protected function setUp(): void
    {
        //
    }

    public static function make(string $name): static
    {
        return (new static())->name($name);
    }

    public function sortable(bool $sortable = true, ?string $field = null): static
    {
        $this->sortable = $sortable;
        $this->sortField = $field;

        return $this;
    }

    public function searchable(bool $searchable = true, ?string $field = null): static
    {
        $this->searchable = $searchable;
        $this->searchField = $field;

        return $this;
    }

    public function getStateUsing(Closure $callback): static
    {
        $this->getStateUsing = $callback;

        return $this;
    }

    public function formatStateUsing(Closure $callback): static
    {
        $this->formatStateUsing = $callback;

        return $this;
    }

    public function wrap(bool $wrap = true): static
    {
        $this->wrap = $wrap;

        return $this;
    }

    public function badge(bool $badge = true, ?string $variant = null): static
    {
        $this->badge = $badge;
        $this->badgeVariant = $variant;

        return $this;
    }

    /**
     * Set the column's color.
     *
     * Accepts a direct Color enum/string value, or a callback that returns one.
     *
     * @param Color|string|Closure(mixed $record): (Color|string|null) $color
     */
    public function color(Color|string|Closure $color): static
    {
        if ($color instanceof Color || is_string($color)) {
            $this->colorUsing = fn () => $color;
        } else {
            $this->colorUsing = $color;
        }

        return $this;
    }

    public function before(Closure $callback): static
    {
        $this->beforeUsing = $callback;

        return $this;
    }

    public function after(Closure $callback): static
    {
        $this->afterUsing = $callback;

        return $this;
    }

    public function omit(Closure $callback): static
    {
        $this->omitUsing = $callback;

        return $this;
    }

    public function icon(string|Closure $icon, string $position = 'before', int $size = 16): static
    {
        $this->iconUsing = $icon;
        $this->iconPosition = $position;
        $this->iconSize = $size;

        return $this;
    }

    public function getIcon($record): ?array
    {
        if (!$this->iconUsing) {
            return null;
        }

        $iconName = $this->iconUsing instanceof Closure
            ? $this->evaluate($this->iconUsing, ['record' => $record])
            : $this->iconUsing;

        if (!$iconName) {
            return null;
        }

        return [
            'name' => $iconName,
            'position' => $this->iconPosition,
            'size' => $this->iconSize,
        ];
    }

    public function togglable(bool $togglable = true, bool $isToggledHiddenByDefault = false): static
    {
        $this->togglable = $togglable;
        $this->isToggledHiddenByDefault = $isToggledHiddenByDefault;

        return $this;
    }

    public function asCardTitle(): static
    {
        $this->cardRole = 'title';

        return $this;
    }

    public function asCardSubtitle(): static
    {
        $this->cardRole = 'subtitle';

        return $this;
    }

    public function asCardImage(): static
    {
        $this->cardRole = 'image';

        return $this;
    }

    public function asCardContent(): static
    {
        $this->cardRole = 'content';

        return $this;
    }

    public function hideInCard(): static
    {
        $this->cardRole = 'hidden';

        return $this;
    }

    public function getCardRole(): string
    {
        return $this->cardRole;
    }

    public function model(?Model $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function getSortField(): string
    {
        return $this->sortField ?? $this->name;
    }

    public function getSearchField(): string
    {
        return $this->searchField ?? $this->name;
    }

    public function shouldWrap(): bool
    {
        return $this->wrap;
    }

    public function shouldShowBadge(): bool
    {
        return $this->badge;
    }

    public function getBadgeVariant(): ?string
    {
        return $this->badgeVariant;
    }

    public function getColor(object $record): ?string
    {
        if (! $this->colorUsing) {
            return null;
        }

        $color = $this->evaluate($this->colorUsing, ['record' => $record]);

        // Convert Color enum to string
        if ($color instanceof Color) {
            return $color->value;
        }

        return $color;
    }

    public function getBeforeContent(object $record): ?Fragments\Fragment
    {
        $value = $this->beforeUsing
            ? $this->evaluate($this->beforeUsing, ['record' => $record])
            : null;

        if ($value instanceof Fragments\Fragment) {
            return $value;
        }

        return Fragments\Text::make($value);
    }

    public function getAfterContent(object $record): ?Fragments\Fragment
    {
        $value = $this->afterUsing
            ? $this->evaluate($this->afterUsing, ['record' => $record])
            : null;

        if ($value instanceof Fragments\Fragment) {
            return $value;
        }

        return Fragments\Text::make($value);
    }

    public function isTogglable(): bool
    {
        return $this->togglable;
    }

    public function isToggledHiddenByDefault(): bool
    {
        return $this->isToggledHiddenByDefault;
    }

    public function getValue($record): mixed
    {
        if ($this->omitUsing && $this->evaluate($this->omitUsing, ['record' => $record])) {
            return null;
        }

        $value = $this->getStateUsing
            ? $this->evaluate($this->getStateUsing, ['record' => $record])
            : $this->getDefaultValue($record);

        $value = $this->formatStateUsing
            ? $this->evaluate($this->formatStateUsing, ['state' => $value, 'record' => $record])
            : $value;

        if ($value instanceof Fragments\Fragment) {
            return $value;
        }

        return $value;
    }

    protected function getEvaluationParameters(): array
    {
        $parameters = [];

        if ($this->model !== null) {
            $parameters['model'] = $this->model;
        }

        return $parameters;
    }

    protected function getDefaultValue(mixed $record)
    {
        return data_get($record, $this->name);
    }

    public function toRecordData(object $record): array
    {
        $data = [
            'id' => method_exists($record, 'getKey') ? $record->getKey() : null,
        ];

        $data[$this->getName()] = $this->toFragment($record);

        if ($before = $this->getBeforeContent($record)) {
            $data[$this->getName() . '_before'] = $before;
        }

        if ($after = $this->getAfterContent($record)) {
            $data[$this->getName() . '_after'] = $after;
        }

        if ($color = $this->getColor($record)) {
            $data[$this->getName() . '_color'] = $color;
        }

        if ($icon = $this->getIcon($record)) {
            $data[$this->getName() . '_icon'] = $icon;
        }

        return $data;
    }

    protected function toFragment(object $record): mixed
    {
        $value = $this->getValue($record);

        if ($this->shouldShowBadge() && ! ($value instanceof Fragments\Fragment)) {
            return (new Fragments\Badge(
                $value,
                $this->getColor($record),
                $this->getIcon($record),
                $this->badgeVariant,
            ));
        }

        return Fragments\Text::make($value);
    }

    public function toData(): array
    {
        return array_merge(parent::toData(), [
            'key' => $this->getName(),
            'label' => $this->getLabel(),
            'wrap' => $this->shouldWrap(),
            'badge' => $this->shouldShowBadge(),
            'togglable' => $this->isTogglable(),
            'isToggledHiddenByDefault' => $this->isToggledHiddenByDefault(),
            'hidden' => ! $this->isVisible(),
            'cardRole' => $this->getCardRole(),
        ]);
    }
}
