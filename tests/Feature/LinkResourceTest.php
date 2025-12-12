<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Link;
use App\Models\ClickLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Filament\Facades\Filament;
use App\Filament\Resources\Links\LinkResource;
use App\Filament\Resources\Links\Pages\ListLinks;
use App\Filament\Resources\Links\Pages\CreateLink;
use App\Filament\Resources\Links\Pages\EditLink;
use Tests\TestCase;

class LinkResourceTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = Admin::factory()->create([
            'username' => 'testadmin',
            'password_hash' => bcrypt('password'),
            'is_active' => true,
        ]);
        
        $this->actingAs($this->admin, 'filament');
    }

    /** @test */
    public function it_can_render_list_page()
    {
        Livewire::test(ListLinks::class)
            ->assertSuccessful();
    }

    /** @test */
    public function it_can_render_create_page()
    {
        Livewire::test(CreateLink::class)
            ->assertSuccessful();
    }

    /** @test */
    public function it_can_create_link()
    {
        $linkData = [
            'short_code' => 'test-link',
            'long_url' => 'https://example.com/test',
            'is_custom' => true,
            'telegram_user_id' => $this->admin->telegram_user_id,
        ];

        Livewire::test(CreateLink::class)
            ->fillForm($linkData)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('links', [
            'short_code' => 'test-link',
            'long_url' => 'https://example.com/test',
            'is_custom' => true,
            'telegram_user_id' => $this->admin->telegram_user_id,
        ]);
    }

    /** @test */
    public function it_can_render_edit_page()
    {
        $link = Link::factory()->create();

        Livewire::test(EditLink::class, ['record' => $link->id])
            ->assertSuccessful();
    }

    /** @test */
    public function it_can_update_link()
    {
        $link = Link::factory()->create([
            'short_code' => 'old-code',
            'long_url' => 'https://example.com/old',
        ]);

        $newData = [
            'short_code' => 'new-code',
            'long_url' => 'https://example.com/new',
        ];

        Livewire::test(EditLink::class, ['record' => $link->id])
            ->fillForm($newData)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('links', [
            'id' => $link->id,
            'short_code' => 'new-code',
            'long_url' => 'https://example.com/new',
        ]);
    }

    /** @test */
    public function it_can_delete_link()
    {
        $link = Link::factory()->create();

        Livewire::test(ListLinks::class)
            ->callTableAction('delete', $link)
            ->assertSuccessful();

        $this->assertSoftDeleted('links', [
            'id' => $link->id,
        ]);
    }

    /** @test */
    public function it_can_toggle_link_status()
    {
        $link = Link::factory()->create(['disabled' => false]);

        Livewire::test(ListLinks::class)
            ->callTableAction('toggle_status', $link)
            ->assertSuccessful();

        $this->assertDatabaseHas('links', [
            'id' => $link->id,
            'disabled' => true,
        ]);
    }

    /** @test */
    public function it_can_search_links()
    {
        Link::factory()->create([
            'short_code' => 'searchable-link',
            'long_url' => 'https://searchable.com',
        ]);

        Livewire::test(ListLinks::class)
            ->set('tableSearch', 'searchable')
            ->assertCanSeeTableRecords([
                Link::where('short_code', 'searchable-link')->first(),
            ]);
    }

    /** @test */
    public function it_can_filter_links_by_status()
    {
        $activeLink = Link::factory()->create(['disabled' => false]);
        $disabledLink = Link::factory()->create(['disabled' => true]);

        Livewire::test(ListLinks::class)
            ->set('tableFilters', ['active' => true])
            ->assertCanSeeTableRecords([$activeLink])
            ->assertCanNotSeeTableRecords([$disabledLink]);
    }

    /** @test */
    public function it_can_filter_custom_links()
    {
        $customLink = Link::factory()->create(['is_custom' => true]);
        $randomLink = Link::factory()->create(['is_custom' => false]);

        Livewire::test(ListLinks::class)
            ->set('tableFilters', ['custom' => true])
            ->assertCanSeeTableRecords([$customLink])
            ->assertCanNotSeeTableRecords([$randomLink]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        Livewire::test(CreateLink::class)
            ->fillForm([
                'short_code' => '',
                'long_url' => '',
            ])
            ->call('create')
            ->assertHasErrors([
                'short_code' => 'required',
                'long_url' => 'required',
            ]);
    }

    /** @test */
    public function it_validates_url_format()
    {
        Livewire::test(CreateLink::class)
            ->fillForm([
                'short_code' => 'test-link',
                'long_url' => 'invalid-url',
            ])
            ->call('create')
            ->assertHasErrors([
                'long_url' => 'url',
            ]);
    }

    /** @test */
    public function it_validates_unique_short_code()
    {
        Link::factory()->create(['short_code' => 'existing-code']);

        Livewire::test(CreateLink::class)
            ->fillForm([
                'short_code' => 'existing-code',
                'long_url' => 'https://example.com/new',
            ])
            ->call('create')
            ->assertHasErrors([
                'short_code' => 'unique',
            ]);
    }
}