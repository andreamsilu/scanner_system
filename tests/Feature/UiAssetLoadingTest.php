<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\FrontendAssets;
use Illuminate\Routing\Route;
use Illuminate\Support\HtmlString;
use Mockery;
use Tests\TestCase;

class UiAssetLoadingTest extends TestCase
{
    public function test_sign_in_does_not_wait_for_external_fonts_or_legacy_libraries(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Welcome back')
            ->assertDontSee('fonts.bunny.net')->assertDontSee('jquery.min.js')->assertDontSee('dataTables.bootstrap5');
    }

    private function workspace(string $routeName): string
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->forceFill(['id' => 1, 'name' => 'Asset check', 'is_active' => true]);
        $user->shouldReceive('isAdmin')->once()->andReturn(true);
        $this->actingAs($user);
        $route = (new Route('GET', '/asset-check', fn () => null))->name($routeName);
        $this->app['request']->setRouteResolver(fn () => $route);

        return view('layouts.workspace', ['content' => new HtmlString('<p>Layout check</p>')])->render();
    }

    public function test_modern_workspace_omits_compatibility_downloads(): void
    {
        $html = $this->workspace('scanner.dashboard');
        $this->assertStringContainsString('Layout check', $html);
        $this->assertStringNotContainsString('jquery.min.js', $html);
        $this->assertStringNotContainsString('dataTables.bootstrap5', $html);
        $this->assertStringNotContainsString('toastr.min', $html);
        $this->assertStringNotContainsString('fonts.bunny.net', $html);
    }

    public function test_legacy_workspace_keeps_local_classic_scripts_in_dependency_order(): void
    {
        $html = $this->workspace('settings.index');
        $jquery = strpos($html, FrontendAssets::url('jquery.min.js'));
        $table = strpos($html, FrontendAssets::url('jquery.dataTables.min.js'));
        $integration = strpos($html, FrontendAssets::url('dataTables.bootstrap5.min.js'));
        $this->assertNotFalse($jquery);
        $this->assertNotFalse($table);
        $this->assertNotFalse($integration);
        $this->assertLessThan($table, $jquery);
        $this->assertLessThan($integration, $table);
        $this->assertStringNotContainsString('https://cdn.', $html);
        $this->assertStringNotContainsString('fonts.bunny.net', $html);
    }
}
