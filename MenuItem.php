<?php

declare(strict_types=1);

namespace MoonShine\MenuManager;

use Closure;
use MoonShine\Core\Contracts\MenuFiller;
use MoonShine\Support\Attributes;
use MoonShine\Support\Attributes\Icon;
use MoonShine\UI\Components\ActionButton;
use Throwable;

/**
 * @method static static make(Closure|string $label, Closure|MenuFiller|string $filler, string $icon = null, Closure|bool $blank = false)
 */
class MenuItem extends MenuElement
{
    protected string $view = 'moonshine::components.menu.item';

    protected ?Closure $badge = null;

    protected Closure|string|null $url = null;

    protected Closure|bool $blank = false;

    protected ?Closure $whenActive = null;

    protected ActionButton $actionButton;

    final public function __construct(
        Closure|string $label,
        protected Closure|MenuFiller|string $filler,
        string $icon = null,
        Closure|bool $blank = false
    ) {
        parent::__construct();

        $this->setLabel($label);

        if ($icon) {
            $this->icon($icon);
        }

        if ($filler instanceof MenuFiller) {
            $this->resolveFiller($filler);
        } else {
            $this->setUrl($filler);
        }

        $this->blank($blank);

        $this->actionButton = ActionButton::make($label);
    }

    public function changeButton(Closure $callback): self
    {
        $this->actionButton = $callback($this->actionButton);

        return $this;
    }

    protected function resolveFiller(MenuFiller $filler): void
    {
        $this->setUrl(static fn (): string => $filler->getUrl());

        $icon = Attributes::for($filler)
            ->attribute(Icon::class)
            ->attributeProperty('icon')
            ->get();

        if (method_exists($filler, 'getBadge')) {
            $this->badge(static fn () => $filler->getBadge());
        }

        if (! is_null($icon) && $this->getIconValue() === '') {
            $this->icon($icon, $this->isCustomIcon(), $this->getIconPath());
        }
    }

    public function getFiller(): MenuFiller|Closure|string
    {
        return $this->filler;
    }

    public function badge(Closure $callback): static
    {
        $this->badge = $callback;

        return $this;
    }

    public function hasBadge(): bool
    {
        return is_callable($this->badge);
    }

    public function getBadge(): mixed
    {
        return value($this->badge);
    }

    public function whenActive(Closure $when): static
    {
        $this->whenActive = $when;

        return $this;
    }

    public function setUrl(string|Closure|null $url, Closure|bool $blank = false): static
    {
        $this->url = $url;

        $this->blank($blank);

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function getUrl(): string
    {
        return value($this->url) ?? '';
    }

    public function blank(Closure|bool $blankCondition = true): static
    {
        $this->blank = value($blankCondition, $this) ?? true;

        return $this;
    }

    public function isBlank(): bool
    {
        return $this->blank;
    }

    /**
     * @throws Throwable
     */
    public function isActive(): bool
    {
        $filler = $this->getFiller();

        if ($filler instanceof MenuFiller) {
            return $filler->isActive();
        }

        $path = parse_url($this->getUrl(), PHP_URL_PATH) ?? '/';
        $host = parse_url($this->getUrl(), PHP_URL_HOST) ?? '';

        $isActive = function ($path, $host): bool {
            if ($path === '/' && moonshine()->getRequest()->getHost() === $host) {
                return moonshine()->getRequest()->getPath() === $path;
            }

            if ($this->getUrl() === moonshineRouter()->getEndpoints()->home()) {
                return moonshine()->getRequest()->urlIs($this->getUrl());
            }

            return moonshine()->getRequest()->urlIs('*' . $this->getUrl() . '*');
        };

        return is_null($this->whenActive)
            ? $isActive($path, $host)
            : value($this->whenActive, $path, $host, $this);
    }

    protected function prepareBeforeRender(): void
    {
        parent::prepareBeforeRender();

        if ($this->isBlank()) {
            $this->actionButton = $this->actionButton->customAttributes([
                '_target' => '_blank',
            ]);
        }

        if (! $this->isTopMode()) {
            $this->actionButton = $this->actionButton->customAttributes([
                'x-data' => 'navTooltip',
                '@mouseenter' => 'toggleTooltip',
            ]);
        }
    }

    /**
     * @throws Throwable
     */
    public function viewData(): array
    {
        $viewData = [
            'url' => $this->getUrl(),
        ];

        if ($this->hasBadge() && $badge = $this->getBadge()) {
            $viewData['badge'] = $badge;
        }

        $viewData['actionButton'] = $this->actionButton
            ->setUrl($this->getUrl())
            ->customView('moonshine::components.menu.item-link', [
                'url' => $this->getUrl(),
                'label' => $this->getLabel(),
                'icon' => $this->getIcon(6),
                'top' => $this->isTopMode(),
                'badge' => $viewData['badge'] ?? '',
            ]);

        return $viewData;
    }
}
