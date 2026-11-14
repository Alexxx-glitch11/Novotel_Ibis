<?php

namespace Tests\Feature;

use App\Models\AdminConfigurationChange;
use App\Models\AuditLog;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Outlet;
use App\Models\OutletContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create(['email' => 'admin@example.test', 'is_active' => true]);
        $this->admin->assignRole($role);
    }

    public function test_admin_can_open_all_eight_pages(): void
    {
        $this->actingAs($this->admin);
        foreach ([
            'admin.dashboard', 'admin.outlets.index', 'admin.tables.index', 'admin.menu.index',
            'admin.users.index', 'admin.content.index', 'admin.configuration.index', 'admin.audit.index',
        ] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_routes_require_authentication_and_admin_role(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $managerRole = Role::create(['name' => 'manager', 'guard_name' => 'web']);
        $manager = User::factory()->create(['email' => 'manager@example.test']);
        $manager->assignRole($managerRole);

        $this->actingAs($manager)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_admin_can_add_outlet_and_area_and_actions_are_audited(): void
    {
        $this->actingAs($this->admin);
        $this->post(route('admin.outlets.store'), ['name' => 'Novotel Food Exchange', 'code' => 'NOV', 'address' => 'Hotel'])->assertSessionHasNoErrors();
        $outlet = Outlet::where('code', 'NOV')->firstOrFail();
        $this->post(route('admin.areas.store'), ['outlets_id' => $outlet->outlets_id, 'name' => 'Indoor'])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('areas', ['outlets_id' => $outlet->outlets_id, 'name' => 'Indoor']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin_outlet_created', 'entity_id' => $outlet->outlets_id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin_area_created']);
    }

    public function test_menu_creation_validates_category_and_preserves_audit(): void
    {
        $this->actingAs($this->admin);
        $outlet = Outlet::create(['name' => 'Novotel', 'code' => 'NOV', 'is_active' => true]);
        $category = MenuCategory::create(['outlets_id' => $outlet->outlets_id, 'name' => 'Makanan', 'is_active' => true]);

        $this->post(route('admin.menu.store'), [
            'outlets_id' => $outlet->outlets_id, 'menu_categories_id' => $category->menu_categories_id,
            'name' => 'Lemon Butter Salmon', 'price' => '165000', 'description' => 'Salmon', 'is_available' => '1',
        ])->assertSessionHasNoErrors();

        $item = MenuItem::where('name', 'Lemon Butter Salmon')->firstOrFail();
        $this->assertSame('165000.00', $item->price);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin_menu_item_created', 'entity_id' => $item->menu_items_id]);
    }

    public function test_table_create_and_update_persist_master_data(): void
    {
        $this->actingAs($this->admin);
        $outlet = Outlet::create(['name' => 'Novotel', 'code' => 'NOV', 'is_active' => true]);
        $area = \App\Models\Area::create(['outlets_id' => $outlet->outlets_id, 'name' => 'Indoor', 'is_active' => true]);
        $this->post(route('admin.tables.store'), [
            'outlets_id' => $outlet->outlets_id, 'areas_id' => $area->areas_id, 'table_code' => 'T01',
            'capacity' => 4, 'min_capacity' => 1, 'position_x' => 25, 'position_y' => 40, 'is_active' => '1',
        ])->assertSessionHasNoErrors();
        $table = \App\Models\Table::where('table_code', 'T01')->firstOrFail();

        $this->put(route('admin.tables.update', $table), [
            'outlets_id' => $outlet->outlets_id, 'areas_id' => $area->areas_id, 'table_code' => 'T01',
            'capacity' => 4, 'min_capacity' => 1, 'position_x' => 30, 'position_y' => 45, 'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tables', ['tables_id' => $table->tables_id, 'position_x' => 30, 'position_y' => 45]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin_table_updated', 'entity_id' => $table->tables_id]);
    }

    public function test_admin_user_creation_hashes_password_and_does_not_log_it(): void
    {
        $this->actingAs($this->admin);
        $role = Role::create(['name' => 'finance', 'guard_name' => 'web']);
        $this->post(route('admin.users.store'), [
            'name' => 'Finance User', 'email' => 'finance@example.test', 'role_id' => $role->id,
            'password' => 'Strong-test-password-2026', 'password_confirmation' => 'Strong-test-password-2026',
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'finance@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('Strong-test-password-2026', $user->password));
        $log = AuditLog::where('entity_type', 'user')->latest('audit_logs_id')->firstOrFail();
        $this->assertStringNotContainsString('Strong-test-password-2026', json_encode([$log->old_values, $log->new_values]));
    }

    public function test_content_create_and_audit_filters_use_database_records(): void
    {
        $this->actingAs($this->admin);
        $outlet = Outlet::create(['name' => 'ibis', 'code' => 'IBS', 'is_active' => true]);
        $this->post(route('admin.content.store'), [
            'outlets_id' => $outlet->outlets_id, 'content_type' => 'promotion', 'title' => 'Weekend Seafood',
            'description' => 'Menu promotion', 'starts_on' => '2026-11-01', 'ends_on' => '2026-11-30', 'status' => 'draft',
        ])->assertSessionHasNoErrors();
        $content = OutletContent::where('title', 'Weekend Seafood')->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin_outlet_content_created', 'entity_id' => $content->outlet_contents_id]);

        $this->get(route('admin.audit.index', ['q' => 'Weekend Seafood']))->assertOk()->assertSee('Weekend Seafood');
    }

    public function test_configuration_is_saved_as_pending_proposal_without_applying_business_rules(): void
    {
        $this->actingAs($this->admin)->post(route('admin.configuration.store'), ['hold_minutes' => 15])->assertSessionHasNoErrors();

        $change = AdminConfigurationChange::where('config_key', 'hold_minutes')->firstOrFail();
        $this->assertSame('pending', $change->status);
        $this->assertSame(15, $change->proposed_value['value']);
        $this->assertSame(10, config('reservation.hold_minutes'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin_configuration_proposed', 'entity_id' => $change->id]);
    }
}
