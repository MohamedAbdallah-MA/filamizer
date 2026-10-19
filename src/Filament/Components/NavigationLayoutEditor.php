<?php

namespace DealArt\FilamentStudio\Filament\Components;

use DealArt\FilamentStudio\Navigation\StudioNavigation;
use Filament\Forms\Components\Field;
use Filament\Support\Icons\Heroicon;

use function Filament\Support\generate_icon_html;

/**
 * Drag-and-drop editor for the sidebar layout (see StudioNavigation for the format).
 */
class NavigationLayoutEditor extends Field
{
    protected string $view = 'filament-studio::navigation-editor';

    protected ?array $catalog = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);

        $this->afterStateHydrated(function (NavigationLayoutEditor $component, mixed $state): void {
            $component->state(StudioNavigation::editorState(is_array($state) ? $state : [], $component->getCatalog()));
        });

        $this->dehydrateStateUsing(fn (mixed $state): array => StudioNavigation::sanitize($state));
    }

    public function getCatalog(): array
    {
        return $this->catalog ??= StudioNavigation::catalog();
    }

    public function getDefaultLayout(): array
    {
        return StudioNavigation::defaultLayout($this->getCatalog());
    }

    /**
     * Rendered icons the editor can show: every default icon plus any icon the
     * saved layout already uses. Icons typed in the editor show once saved.
     *
     * @return array<string, string>
     */
    public function getIconHtml(): array
    {
        $icons = collect($this->getCatalog()['items'])
            ->filter(fn (array $item) => filled($item['icon']) && filled($item['iconHtml']))
            ->mapWithKeys(fn (array $item) => [$item['icon'] => $item['iconHtml']]);

        foreach ($this->getState()['items'] ?? [] as $item) {
            $icon = $item['icon'] ?? null;

            if (is_string($icon) && ! $icons->has($icon) && preg_match(StudioNavigation::ICON_PATTERN, $icon)) {
                $icons[$icon] = rescue(fn () => generate_icon_html($icon)?->toHtml(), null, false);
            }
        }

        return $icons->filter()->all();
    }

    /**
     * Every outline Heroicon, rendered, for the icon picker.
     *
     * @return array<string, string>
     */
    public function getIconOptions(): array
    {
        return collect(Heroicon::cases())
            ->filter(fn (Heroicon $icon) => str_starts_with($icon->name, 'Outlined'))
            ->mapWithKeys(function (Heroicon $icon) {
                $name = 'heroicon-'.$icon->value;

                return [$name => rescue(fn () => generate_icon_html($name)?->toHtml(), null, false)];
            })
            ->filter()
            ->all();
    }
}
