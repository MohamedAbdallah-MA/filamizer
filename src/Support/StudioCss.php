<?php

namespace FilamentCustomizer\Support;

use Filament\Support\Assets\Css;

/**
 * Filament versions asset URLs by package version only, so browsers keep a stale
 * stylesheet after the CSS changes within the same release. Adding the file's
 * modification time busts the cache whenever the stylesheet is updated.
 */
class StudioCss extends Css
{
    public function getVersion(): string
    {
        $modified = rescue(fn () => filemtime($this->getPath()), null, false);

        return $modified ? parent::getVersion().'.'.$modified : parent::getVersion();
    }
}
