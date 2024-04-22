<?php

declare(strict_types=1);

namespace MoonShine\MenuManager;

use Closure;
use Illuminate\Support\Collection;

/**
 * @extends Collection<int, MenuElement>
 */
final class MenuElements extends Collection
{
    public function topMode(?Closure $condition = null): self
    {
        return $this->transform(function (MenuElement $item) use ($condition): MenuElement {
            $item = clone $item;

            if ($item instanceof MenuGroup) {
                $item->setItems(
                    $item->items()->topMode($condition)
                );
            }

            return $item->topMode($condition);
        });
    }

    public function onlyVisible(): self
    {
        return $this->filter(function (MenuElement $item): bool {
            if ($item instanceof MenuGroup) {
                $item->setItems(
                    $item->items()->onlyVisible()
                );
            }

            return $item->isSee(moonshineRequest());
        });
    }
}
