@php
    /** @var \Illuminate\Support\Collection $profiles */
@endphp

<div class="fi-studio-profiles">
    <div class="fi-studio-profiles-header">
        <span class="fi-studio-nav-group-title">{{ __('Profiles') }}</span>
        <span class="fi-studio-notice">{{ __('A profile stores a whole look. Click one to apply it.') }}</span>
    </div>

    @if ($profiles->isEmpty())
        <p class="fi-studio-nav-empty">{{ __('No profiles yet. Set up a look, then use "Save as profile".') }}</p>
    @else
        <div class="fi-studio-profile-list">
            @foreach ($profiles as $profile)
                @php($data = $profile->data ?? [])
                <div class="fi-studio-profile" wire:key="studio-profile-{{ $profile->id }}">
                    <button type="button" class="fi-studio-profile-apply" wire:click="applyStudioProfile({{ $profile->id }})">
                        <span class="fi-studio-profile-swatches">
                            @foreach ([$data['primary'] ?? null, $data['background'] ?? null, $data['dark_primary'] ?? null, $data['dark_background'] ?? null] as $color)
                                @if (\DealArt\FilamentStudio\Support\StudioTheme::isHex($color))
                                    <span class="fi-studio-profile-swatch" style="background-color: {{ $color }}"></span>
                                @endif
                            @endforeach
                        </span>
                        <span class="fi-studio-profile-name">{{ $profile->name }}</span>
                        <span class="fi-studio-profile-meta">
                            {{ $profile->isShared() ? __('Shared') : __('Yours') }}
                            · {{ __(config('filament-studio.skins.'.($data['skin'] ?? 'classic'), 'Classic')) }}
                        </span>
                    </button>

                    @if ($canDelete($profile))
                        <button
                            type="button"
                            class="fi-studio-nav-icon-btn fi-studio-nav-danger"
                            wire:click="deleteStudioProfile({{ $profile->id }})"
                            wire:confirm="{{ __('Delete this profile?') }}"
                            title="{{ __('Delete profile') }}"
                        >
                            <x-filament::icon icon="heroicon-m-trash" />
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
