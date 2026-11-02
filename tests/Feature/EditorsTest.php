<?php

namespace Filamizer\Tests\Feature;

use Filamizer\Filament\Pages\ManageAppearance;
use Filamizer\Filament\Pages\MyAppearance;
use Filamizer\Models\StudioUserPreference;
use Filamizer\Settings\StudioSettings;
use Filamizer\FilamizerPlugin;
use Filamizer\Support\StudioPreferences;
use Filamizer\Support\StudioTheme;
use Filamizer\Tests\Fixtures\User;
use Filamizer\Tests\TestCase;
use Filament\Panel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

class EditorsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->install();
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('can_manage_studio')->default(false);
        });
    }

    protected function user(bool $manager = false): User
    {
        return User::create([
            'name' => 'Package user', 'email' => uniqid().'@example.test',
            'password' => bcrypt('password'), 'can_manage_studio' => $manager,
        ]);
    }

    public function test_global_editor_works_without_any_host_settings_page_or_roles(): void
    {
        $this->actingAs($this->user(true), 'studio');
        Livewire::test(ManageAppearance::class)
            ->assertSee('Profiles')
            ->assertSee('Sidebar Builder')
            ->set('data.studio.primary', '#123456')
            ->set('data.studio.dark_primary', '#ABCDEF')
            ->call('save')->assertHasNoFormErrors();

        $settings = app(StudioSettings::class)->refresh();
        $this->assertSame('#123456', $settings->primary);
        $this->assertSame('#ABCDEF', $settings->dark_primary);
        $this->assertDatabaseCount('studio_user_preferences', 0);
    }

    public function test_registered_pages_render_the_full_panel_layout_and_assets(): void
    {
        $this->actingAs($this->user(true), 'studio');
        $this->get(ManageAppearance::getUrl())
            ->assertOk()->assertSee('filamizer-styles', false)->assertSee('filamizer.css', false);
        $this->get(MyAppearance::getUrl())->assertOk()->assertSee('My appearance');
    }

    public function test_guests_and_regular_users_cannot_save_global_settings(): void
    {
        $this->assertFalse(ManageAppearance::canAccess());
        $this->actingAs($this->user(), 'studio');
        $this->assertFalse(ManageAppearance::canAccess());
        Livewire::test(ManageAppearance::class)->assertForbidden();
        FilamizerPlugin::get()->canCustomize(null);
        $this->assertFalse(FilamizerPlugin::get()->userCanCustomize());
    }

    public function test_personal_editor_uses_the_panel_guard_and_keeps_shared_defaults(): void
    {
        $webUser = $this->user();
        $panelUser = $this->user();
        auth()->guard('web')->login($webUser);
        auth()->guard('studio')->login($panelUser);
        StudioPreferences::make()->saveForUser($webUser->id, ['primary' => '#111111']);
        StudioPreferences::make()->saveForUser($panelUser->id, ['primary' => '#222222']);

        $this->assertTrue(MyAppearance::canAccess());
        $this->assertSame('#222222', StudioTheme::color('primary'));
        Livewire::test(MyAppearance::class)
            ->set('data.studio.primary', '#333333')->call('save')->assertHasNoFormErrors();

        $this->assertSame('#333333', StudioUserPreference::where('user_id', $panelUser->id)->first()->data['primary']);
        $this->assertSame('#111111', StudioUserPreference::where('user_id', $webUser->id)->first()->data['primary']);
        $this->assertSame(config('filamizer.defaults.primary'), app(StudioSettings::class)->refresh()->primary);
    }

    public function test_pages_can_be_disabled_independently(): void
    {
        $this->actingAs($this->user(true), 'studio');
        FilamizerPlugin::get()->settingsPage(false);
        $this->assertFalse(ManageAppearance::canAccess());
        $this->assertTrue(MyAppearance::canAccess());
        FilamizerPlugin::get()->personalAppearance(false);
        $this->assertFalse(MyAppearance::canAccess());
    }

    public function test_disabling_editors_omits_their_routes_and_user_menu_item(): void
    {
        $this->actingAs($this->user(true), 'studio');
        $panel = Panel::make()->id('without-editors')->path('without-editors')
            ->plugin(FilamizerPlugin::make()->settingsPage(false)->personalAppearance(false));

        $this->assertNotContains(ManageAppearance::class, $panel->getPages());
        $this->assertNotContains(MyAppearance::class, $panel->getPages());
        $this->assertArrayNotHasKey('studio-appearance', $panel->getUserMenuItems());
    }

    public function test_global_save_rechecks_permission_after_the_page_was_opened(): void
    {
        $this->actingAs($this->user(true), 'studio');
        $page = Livewire::test(ManageAppearance::class)->set('data.studio.primary', '#123456');
        FilamizerPlugin::get()->canCustomize(fn (): bool => false);
        $page->call('save')->assertForbidden();
        $this->assertSame(config('filamizer.defaults.primary'), app(StudioSettings::class)->refresh()->primary);
    }
}
