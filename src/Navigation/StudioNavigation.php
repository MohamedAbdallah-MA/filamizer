<?php

namespace Filamizer\Navigation;

use BackedEnum;
use Closure;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use UnitEnum;

use function Filament\Support\generate_icon_html;

/**
 * The sidebar builder's layout logic.
 *
 * A layout is ['groups' => [...], 'items' => [...]], both in display order:
 *   group: ['id' => 'g_…', 'labels' => ['en' => …, 'ar' => …], 'collapsed' => bool]
 *   item:  ['key' => resource/page class or item key, 'group' => group id or UNGROUPED,
 *           'labels' => per-locale overrides ('' = Filament's label), 'icon' => ?string,
 *           'hidden' => bool]
 *
 * Applying a layout only reorders, renames, regroups and hides the items Filament
 * already built for the current user, so visibility, permissions, URLs, badges and
 * active states all stay Filament's job. Hiding an item is cosmetic, not access control.
 */
class StudioNavigation
{
    public const UNGROUPED = '__ungrouped';

    public const ICON_PATTERN = '/^heroicon-[os]-[a-z0-9-]+$/';

    public const GROUP_ID_PATTERN = '/^g_[a-z0-9_-]{1,40}$/';

    /**
     * @return array<int, string>
     */
    public static function locales(): array
    {
        return config('filamizer.navigation.locales', ['en']);
    }

    /**
     * Filament's own sidebar for the current user, without any saved layout.
     *
     * @return array<NavigationGroup>
     */
    public static function defaultNavigation(): array
    {
        return StudioNavigationManager::withoutLayout(
            fn (): array => filament()->getCurrentOrDefaultPanel()->getNavigation(),
        );
    }

    /**
     * Everything the editor needs to know about the default sidebar.
     *
     * @param  array<NavigationGroup>|null  $navigation
     * @return array{groups: array<string, array{labels: array<string, string>, collapsed: bool}>, items: array<string, array{group: string, labels: array<string, string>, icon: ?string, iconHtml: ?string}>}
     */
    public static function catalog(?array $navigation = null): array
    {
        $groups = [];
        $items = [];

        foreach ($navigation ?? static::defaultNavigation() as $group) {
            $groupItems = collect($group->getItems())->values();
            $groupId = static::UNGROUPED;

            if (filled($group->getLabel())) {
                $labels = static::groupLabels($group, $groupItems->first());
                $groupId = static::groupId($labels[static::locales()[0]] ?? $group->getLabel());

                $groups[$groupId] ??= [
                    'labels' => $labels,
                    'collapsed' => $group->isCollapsed(),
                ];
            }

            foreach ($groupItems as $item) {
                $icon = static::iconName($item->getIcon());

                $items[$item->getKey()] ??= [
                    'group' => $groupId,
                    'labels' => static::itemLabels($item),
                    'icon' => $icon,
                    'iconHtml' => generate_icon_html($item->getIcon())?->toHtml(),
                ];
            }
        }

        return ['groups' => $groups, 'items' => $items];
    }

    /**
     * The layout that reproduces the default sidebar.
     */
    public static function defaultLayout(array $catalog): array
    {
        return [
            'groups' => collect($catalog['groups'])
                ->map(fn (array $group, string $id) => ['id' => $id, 'labels' => $group['labels'], 'collapsed' => $group['collapsed']])
                ->values()
                ->all(),
            'items' => collect($catalog['items'])
                ->map(fn (array $item, string $key) => [
                    'key' => $key,
                    'group' => $item['group'],
                    'labels' => static::emptyLabels(),
                    'icon' => null,
                    'hidden' => false,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * A saved layout brought up to date with the current sidebar: items that no longer
     * exist are dropped, and new items join their default group (created if missing).
     */
    public static function editorState(array $layout, array $catalog): array
    {
        $layout = static::sanitize($layout);

        if ($layout === []) {
            return static::defaultLayout($catalog);
        }

        $groupIds = collect($layout['groups'])->pluck('id')->all();

        $items = collect($layout['items'])
            ->filter(fn (array $item) => isset($catalog['items'][$item['key']]))
            ->values();

        $known = $items->pluck('key')->flip();

        foreach ($catalog['items'] as $key => $item) {
            if ($known->has($key)) {
                continue;
            }

            $group = $item['group'];

            if ($group !== static::UNGROUPED && ! in_array($group, $groupIds, true)) {
                $layout['groups'][] = ['id' => $group, ...$catalog['groups'][$group]];
                $groupIds[] = $group;
            }

            $items->push(['key' => $key, 'group' => $group, 'labels' => static::emptyLabels(), 'icon' => null, 'hidden' => false]);
        }

        $layout['items'] = $items->all();

        return $layout;
    }

    /**
     * Clean a layout coming from the editor or storage. Anything malformed is
     * dropped or reset rather than trusted.
     */
    public static function sanitize(mixed $layout): array
    {
        if (! is_array($layout)) {
            return [];
        }

        $groups = collect(is_array($layout['groups'] ?? null) ? $layout['groups'] : [])
            ->filter(fn ($group) => is_array($group) && is_string($group['id'] ?? null) && preg_match(static::GROUP_ID_PATTERN, $group['id']))
            ->unique('id')
            ->map(fn (array $group) => [
                'id' => $group['id'],
                'labels' => static::cleanLabels($group['labels'] ?? []),
                'collapsed' => (bool) ($group['collapsed'] ?? false),
            ])
            ->values();

        $groupIds = $groups->pluck('id')->all();

        $items = collect(is_array($layout['items'] ?? null) ? $layout['items'] : [])
            ->filter(fn ($item) => is_array($item) && is_string($item['key'] ?? null) && $item['key'] !== '' && strlen($item['key']) <= 255)
            ->unique('key')
            ->map(fn (array $item) => [
                'key' => $item['key'],
                'group' => in_array($item['group'] ?? null, $groupIds, true) ? $item['group'] : static::UNGROUPED,
                'labels' => static::cleanLabels($item['labels'] ?? []),
                'icon' => is_string($item['icon'] ?? null) && preg_match(static::ICON_PATTERN, $item['icon']) ? $item['icon'] : null,
                'hidden' => (bool) ($item['hidden'] ?? false),
            ])
            ->values();

        if ($groups->isEmpty() && $items->isEmpty()) {
            return [];
        }

        return ['groups' => $groups->all(), 'items' => $items->all()];
    }

    /**
     * Rearrange Filament's built sidebar according to a layout.
     *
     * @param  array<NavigationGroup>  $navigation
     * @return array<NavigationGroup>
     */
    public static function apply(array $navigation, array $layout, string $locale): array
    {
        $layout = static::sanitize($layout);

        if ($layout === []) {
            return $navigation;
        }

        /** @var array<string, NavigationItem> $found */
        $found = [];
        $originalGroups = [];

        foreach ($navigation as $group) {
            foreach ($group->getItems() as $item) {
                $found[$item->getKey()] ??= $item;
                $originalGroups[$item->getKey()] ??= $group;
            }
        }

        $buckets = [static::UNGROUPED => []];

        foreach ($layout['groups'] as $group) {
            $buckets[$group['id']] = [];
        }

        $placed = [];

        foreach ($layout['items'] as $sort => $entry) {
            $item = $found[$entry['key']] ?? null;

            if (! $item) {
                continue;
            }

            $placed[$entry['key']] = true;

            if ($entry['hidden']) {
                continue;
            }

            $item = clone $item;
            $item->sort($sort);

            if ($label = static::pickLabel($entry['labels'], $locale)) {
                $item->label($label);
            }

            if ($entry['icon']) {
                $item->icon($entry['icon'])->activeIcon($entry['icon']);
            }

            $buckets[$entry['group']][] = $item;
        }

        // Items the layout doesn't know yet (e.g. a resource added later) keep their
        // original group: a layout group with the same label, or the original group.
        $extraGroups = [];

        foreach ($found as $key => $item) {
            if (isset($placed[$key])) {
                continue;
            }

            $original = $originalGroups[$key];

            if (blank($original->getLabel())) {
                $buckets[static::UNGROUPED][] = $item;

                continue;
            }

            $match = collect($layout['groups'])->first(
                fn (array $group) => static::pickLabel($group['labels'], $locale) === $original->getLabel(),
            );

            if ($match) {
                $buckets[$match['id']][] = $item;

                continue;
            }

            $extraGroups[spl_object_id($original)] ??= ['group' => $original, 'items' => []];
            $extraGroups[spl_object_id($original)]['items'][] = $item;
        }

        $result = [];

        if ($buckets[static::UNGROUPED] !== []) {
            $result[] = NavigationGroup::make()->items($buckets[static::UNGROUPED]);
        }

        foreach ($layout['groups'] as $group) {
            if ($buckets[$group['id']] === []) {
                continue;
            }

            $result[] = NavigationGroup::make(static::pickLabel($group['labels'], $locale) ?? Str::headline($group['id']))
                ->collapsible()
                ->collapsed($group['collapsed'])
                ->items($buckets[$group['id']]);
        }

        foreach ($extraGroups as ['group' => $group, 'items' => $items]) {
            $result[] = (clone $group)->items($items);
        }

        return $result;
    }

    public static function groupId(string $label): string
    {
        $slug = Str::slug($label, '_');

        return 'g_'.(filled($slug) && strlen($slug) <= 30 ? $slug : substr(md5($label), 0, 12));
    }

    public static function pickLabel(array $labels, string $locale): ?string
    {
        if (filled($labels[$locale] ?? null)) {
            return $labels[$locale];
        }

        return collect($labels)->first(fn ($label) => filled($label));
    }

    /**
     * @return array<string, string>
     */
    public static function emptyLabels(): array
    {
        return array_fill_keys(static::locales(), '');
    }

    /**
     * @return array<string, string>
     */
    protected static function cleanLabels(mixed $labels): array
    {
        $max = config('filamizer.navigation.label_max_length', 60);

        return collect(static::locales())
            ->mapWithKeys(fn (string $locale) => [
                $locale => is_string($labels[$locale] ?? null) ? Str::limit(trim(strip_tags($labels[$locale])), $max, '') : '',
            ])
            ->all();
    }

    /**
     * Group labels in every locale, read from the first item's own group definition.
     *
     * @return array<string, string>
     */
    protected static function groupLabels(NavigationGroup $group, ?NavigationItem $item): array
    {
        $class = $item?->getKey();

        return collect(static::locales())
            ->mapWithKeys(function (string $locale) use ($group, $class) {
                $label = null;

                if ($class && class_exists($class) && method_exists($class, 'getNavigationGroup')) {
                    $label = static::inLocale($locale, fn () => static::labelOf($class::getNavigationGroup()));
                }

                return [$locale => $label ?: $group->getLabel()];
            })
            ->all();
    }

    /**
     * Item labels in every locale, read from the resource or page itself.
     *
     * @return array<string, string>
     */
    protected static function itemLabels(NavigationItem $item): array
    {
        $class = $item->getKey();

        return collect(static::locales())
            ->mapWithKeys(function (string $locale) use ($item, $class) {
                $label = null;

                if (class_exists($class) && method_exists($class, 'getNavigationLabel')) {
                    $label = static::inLocale($locale, fn () => $class::getNavigationLabel());
                }

                return [$locale => is_string($label) && filled($label) ? $label : $item->getLabel()];
            })
            ->all();
    }

    protected static function labelOf(mixed $group): ?string
    {
        return match (true) {
            $group instanceof HasLabel => $group->getLabel(),
            $group instanceof UnitEnum => $group->name,
            is_string($group) => $group,
            default => null,
        };
    }

    protected static function iconName(mixed $icon): ?string
    {
        return match (true) {
            $icon instanceof BackedEnum => 'heroicon-'.$icon->value,
            is_string($icon) => $icon,
            $icon instanceof Htmlable, $icon === null => null,
            default => null,
        };
    }

    protected static function inLocale(string $locale, Closure $callback): mixed
    {
        $previous = app()->getLocale();

        try {
            app()->setLocale($locale);

            return rescue($callback, null, false);
        } finally {
            app()->setLocale($previous);
        }
    }
}
