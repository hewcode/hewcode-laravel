<?php

namespace Hewcode\Hewcode\Widgets;

use Closure;
use Hewcode\Hewcode\Lists\Listing;

class ListingWidget extends Widget
{
    protected ?Closure $listingUsing = null;

    protected bool $compact = false;

    public function getType(): string
    {
        return 'listing';
    }

    public function listing(Closure $callback): static
    {
        $this->listingUsing = $callback;

        return $this;
    }

    public function compact(bool $compact = true): static
    {
        $this->compact = $compact;

        return $this;
    }

    protected function getListing(): ?Listing
    {
        if (! $this->listingUsing) {
            return null;
        }

        $listing = Listing::make()
            ->name('widget_listing_'.$this->getName())
            ->scope($this->getName());

        return $this->evaluate($this->listingUsing, ['listing' => $listing]);
    }

    public function toData(): array
    {
        $listing = $this->getListing();

        return array_merge(parent::toData(), [
            'listing' => $listing?->toData(),
            'compact' => $this->compact,
        ]);
    }
}
