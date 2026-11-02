<?php

namespace Filamizer\Navigation;

use Closure;
use Filamizer\FilamizerPlugin;
use Filamizer\Support\StudioTheme;
use Filament\Navigation\NavigationManager;

/**
 * Filament's navigation manager with the saved sidebar layout applied on top of
 * the sidebar Filament builds, only on panels that use the Studio plugin.
 */
class StudioNavigationManager extends NavigationManager
{
    protected bool $isLayoutDisabled = false;

    public function get(): array
    {
        $navigation = parent::get();

        if ($this->isLayoutDisabled || ! FilamizerPlugin::get()) {
            return $navigation;
        }

        $layout = StudioTheme::value('navigation_layout');

        return StudioNavigation::apply($navigation, is_array($layout) ? $layout : [], app()->getLocale());
    }

    /**
     * Run a callback that builds the navigation without the saved layout.
     */
    public static function withoutLayout(Closure $callback): mixed
    {
        $manager = app(NavigationManager::class);

        if (! $manager instanceof static) {
            return $callback();
        }

        $manager->isLayoutDisabled = true;

        try {
            return $callback();
        } finally {
            $manager->isLayoutDisabled = false;
        }
    }
}
