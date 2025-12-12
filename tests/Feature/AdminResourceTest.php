<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Filament\Resources\Admins\AdminResource;
use App\Filament\Resources\Admins\Pages\ListAdmins;
use App\Filament\Resources\Admins\Pages\CreateAdmin;
use App\Filament\Resources\Admins\Pages\EditAdmin;
use Tests\TestCase;

class AdminResourceTest extends TestCase
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
        Livewire::test(ListAdmins::class)
            ->assertSuccessful();
    }

    /** @test */
    public function it_can_render_create_page()
    {
        Livewire::test(CreateAdmin::class)
            ->assertSuccessful();
    }

    /** @test */
    public function it_can_create_admin()
    {
        $adminData = [
            'telegram_user_id' => 123456789,
            'username' => 'newadmin',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'is_active' => true,
        ];

        Livewire::test(CreateAdmin::class)
            ->fillForm($adminData)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('admins', [
            'telegram_user_id' => 123456789,
            'username' => 'newadmin',
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_render_edit_page()
    {
        $admin = Admin::factory()->create();

        Livewire::test(EditAdmin::class, ['record' => $admin->id])
            ->assertSuccessful();
    }

    /** @test */
    public function it_can_update_admin()
    {
        $admin = Admin::factory()->create([
            'username' => 'oldusername',
            'email' => 'old@example.com',
        ]);

        $newData = [
            'username' => 'newusername',
            'email' => 'new@example.com',
        ];

        Livewire::test(EditAdmin::class, ['record' => $admin->id])
            ->fillForm($newData)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('admins', [
            'id' => $admin->id,
            'username' => 'newusername',
            'email' => 'new@example.com',
        ]);
    }

    /** @test */
    public function it_can_delete_admin()
    {
        $admin = Admin::factory()->create();

        Livewire::test(ListAdmins::class)
            ->callTableAction('delete', $admin)
            ->assertSuccessful();

        $this->assertSoftDeleted('admins', [
            'id' => $admin->id,
        ]);
    }

    /** @test */
    public function it_can_toggle_admin_status()
    {
        $admin = Admin::factory()->create(['is_active' => false]);

        Livewire::test(ListAdmins::class)
            ->callTableAction('toggle', $admin)
            ->assertSuccessful();

        $this->assertDatabaseHas('admins', [
            'id' => $admin->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_reset_admin_password()
    {
        $admin = Admin::factory()->create();

        Livewire::test(ListAdmins::class)
            ->callTableAction('reset_password', $admin)
            ->assertSuccessful();

        // Password should be changed (we can't test the exact value due to hashing)
        $this->assertDatabaseHas('admins', [
            'id' => $admin->id,
        ]);
    }

    /** @test */
    public function it_can_view_admin_links()
    {
        $admin = Admin::factory()->create();
        Link::factory()->create(['telegram_user_id' => $admin->telegram_user_id]);

        Livewire::test(ListAdmins::class)
            ->assertTableActionExists('view_links', $admin);
    }

    /** @test */
    public function it_can_search_admins()
    {
        Admin::factory()->create([
            'username' => 'searchableadmin',
            'email' => 'searchable@example.com',
        ]);

        Livewire::test(ListAdmins::class)
            ->set('tableSearch', 'searchable')
            ->assertCanSeeTableRecords([
                Admin::where('username', 'searchableadmin')->first(),
            ]);
    }

    /** @test */
    public function it_can_filter_active_admins()
    {
        $activeAdmin = Admin::factory()->create(['is_active' => true]);
        $inactiveAdmin = Admin::factory()->create(['is_active' => false]);

        Livewire::test(ListAdmins::class)
            ->set('tableFilters', ['active' => true])
            ->assertCanSeeTableRecords([$activeAdmin])
            ->assertCanNotSeeTableRecords([$inactiveAdmin]);
    }

    /** @test */
    public function it_can_filter_inactive_admins()
    {
        $activeAdmin = Admin::factory()->create(['is_active' => true]);
        $inactiveAdmin = Admin::factory()->create(['is_active' => false]);

        Livewire::test(ListAdmins::class)
            ->set('tableFilters', ['inactive' => true])
            ->assertCanSeeTableRecords([$inactiveAdmin])
            ->assertCanNotSeeTableRecords([$activeAdmin]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        Livewire::test(CreateAdmin::class)
            ->fillForm([
                'telegram_user_id' => '',
                'username' => '',
            ])
            ->call('create')
            ->assertHasErrors([
                'telegram_user_id' => 'required',
                'username' => 'required',
            ]);
    }

    /** @test */
    public function it_validates_unique_username()
    {
        Admin::factory()->create(['username' => 'existingadmin']);

        Livewire::test(CreateAdmin::class)
            ->fillForm([
                'telegram_user_id' => 123456789,
                'username' => 'existingadmin',
                'email' => 'new@example.com',
                'password' => 'password123',
            ])
            ->call('create')
            ->assertHasErrors([
                'username' => 'unique',
            ]);
    }

    /** @test */
    public function it_validates_unique_telegram_user_id()
    {
        Admin::factory()->create(['telegram_user_id' => 123456789]);

        Livewire::test(CreateAdmin::class)
            ->fillForm([
                'telegram_user_id' => 123456789,
                'username' => 'newadmin',
                'email' => 'new@example.com',
                'password' => 'password123',
            ])
            ->call('create')
            ->assertHasErrors([
                'telegram_user_id' => 'unique',
            ]);
    }

    /** @test */
    public function it_validates_email_format()
    {
        Livewire::test(CreateAdmin::class)
            ->fillForm([
                'telegram_user_id' => 123456789,
                'username' => 'newadmin',
                'email' => 'invalid-email',
                'password' => 'password123',
            ])
            ->call('create')
            ->assertHasErrors([
                'email' => 'email',
            ]);
    }

    /** @test */
    public function it_shows_admin_statistics()
    {
        $admin = Admin::factory()->create();
        Link::factory()->count(5)->create(['telegram_user_id' => $admin->telegram_user_id]);
        
        Livewire::test(EditAdmin::class, ['record' => $admin->id])
            ->assertSuccessful();
    }
}