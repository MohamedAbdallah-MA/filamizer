@php
    /** @var \Illuminate\Support\Collection $profiles */
@endphp

<div class="fi-studio-profiles">
    @if ($profiles->isEmpty())
        <p class="fi-studio-nav-empty">{{ __('No profiles yet. Set up a look, then use "Save as profile".') }}</p>
    @else
        <div class="fi-studio-profile-list">
            @foreach ($profiles as $profile)
                @php($data = $profile->data ?? [])
                <div class="fi-studio-profile" wire:key="studio-profile-{{ $profile->id }}">
                    <button
                        type="button"
                        class="fi-studio-profile-apply"
                        wire:click="applyStudioProfile({{ $profile->id }})"
                        wire:loading.attr="disabled"
                        wire:target="applyStudioProfile"
                    >
                        <span class="fi-studio-profile-name">{{ $profile->name }}</span>
                        <span class="fi-studio-profile-meta">
                            {{ $profile->isShared() ? __('Shared') : __('Yours') }}
                        </span>
                        <span class="fi-studio-profile-palette-groups">
                            @foreach (['' => __('Light'), 'dark_' => __('Dark')] as $prefix => $label)
                                <span class="fi-studio-profile-palette" title="{{ __(config('filamizer.skins.'.($data[$prefix.'skin'] ?? $data['skin'] ?? 'classic'), 'Classic')) }}">
                                    <span class="fi-studio-profile-meta">{{ $label }}</span>
                                    <span class="fi-studio-profile-swatches" aria-hidden="true">
                                        @foreach ([$data[$prefix.'primary'] ?? null, $data[$prefix.'background'] ?? null] as $color)
                                            @if (\Filamizer\Support\StudioTheme::isHex($color))
                                                <span class="fi-studio-profile-swatch" style="background-color: {{ $color }}"></span>
                                            @endif
                                        @endforeach
                                    </span>
                                </span>
                            @endforeach
                        </span>
                    </button>

                    @if ($canDelete($profile))
                        <button
                            type="button"
                            class="fi-studio-nav-icon-btn fi-studio-nav-danger"
                            wire:click="deleteStudioProfile({{ $profile->id }})"
                            wire:confirm="{{ __('Delete this profile?') }}"
                            wire:loading.attr="disabled"
                            wire:target="deleteStudioProfile"
                            title="{{ __('Delete profile') }}"
                            aria-label="{{ __('Delete profile :name', ['name' => $profile->name]) }}"
                        >
                            <x-filament::icon icon="heroicon-m-trash" />
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
