<?php

declare(strict_types=1);

namespace MoonShine\MenuManager;

use Closure;
use Illuminate\Support\Collection;

/**
 * @method static static make(Closure|string $label, iterable $items, string|null $icon = null)
 */
class MenuGroup extends MenuElement
{
    protected string $view = 'moonshine::components.menu.group';

    public function __construct(
        Closure|string $label,
        protected iterable $items = [],
        string $icon = null,
    ) {
        $this->setLabel($label);

        if ($icon) {
            $this->icon($icon);
        }
    }

    public function setItems(iterable $items): static
    {
        $this->items = $items;

        return $this;
    }

    public function items(): MenuElements
    {
        return MenuElements::make($this->items);
    }

    public function isActive(): bool
    {
        foreach ($this->items() as $item) {
            if ($item->isActive()) {
                return true;
            }
        }

        return false;
    }

    public function viewData(): array
    {
        return [
            'items' => $this->items(),
        ];
    }
}
