<?php

namespace Tests\Feature;

use App\Features\Admin\Livewire\UserList;
use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\Services\GeminiStockGeneratorService;
use App\Features\Auth\Actions\LoginUserAction;
use App\Features\Auth\Actions\RegisterUserAction;
use App\Features\Auth\Livewire\Login;
use App\Features\Auth\Livewire\Register;
use App\Features\Profile\Livewire\Profile;
use App\Features\Profile\Services\ProfileService;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Livewire\Adjust;
use App\Features\Stock\Livewire\Create;
use App\Features\Stock\Livewire\Edit;
use App\Features\Stock\Livewire\StockList;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Models\StockMovement;
use App\Features\Stock\Policies\StockPolicy;
use App\Features\Stock\Services\StockExcelService;
use App\Features\Stock\Services\StockService;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StockSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_status_values_labels_badges_and_boundaries(): void
    {
        $this->assertSame('available', StockStatus::fromQuantity(6, 5)->value);
        $this->assertSame('low_stock', StockStatus::fromQuantity(5, 5)->value);
        $this->assertSame('out_of_stock', StockStatus::fromQuantity(0, 5)->value);
        $this->assertSame('Tersedia', StockStatus::AVAILABLE->label());
        $this->assertSame('Stok Menipis', StockStatus::LOW_STOCK->label());
        $this->assertSame('Habis', StockStatus::OUT_OF_STOCK->label());
        $this->assertStringContainsString('green', StockStatus::AVAILABLE->badgeClass());
        $this->assertStringContainsString('yellow', StockStatus::LOW_STOCK->badgeClass());
        $this->assertStringContainsString('red', StockStatus::OUT_OF_STOCK->badgeClass());
    }

    public function test_member_can_create_edit_and_delete_owned_stock_through_modal(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $this->actingAs($member);

        Livewire::test(StockList::class)
            ->call('openCreate')
            ->set('name', 'Kertas A4')
            ->set('sku', 'PAPER-A4')
            ->set('quantity', 3)
            ->set('unit', 'rim')
            ->set('price', '52000')
            ->set('min_stock', 5)
            ->call('save')
            ->assertHasNoErrors();

        $stock = Stock::query()->where('sku', 'PAPER-A4')->firstOrFail();
        $this->assertSame($member->id, $stock->user_id);
        $this->assertSame('low_stock', $stock->status);

        Livewire::test(StockList::class)
            ->call('openEdit', $stock->id)
            ->set('quantity', 0)
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame('out_of_stock', $stock->fresh()->status);

        Livewire::test(StockList::class)
            ->call('delete', $stock->id)
            ->assertHasNoErrors();
        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_member_cannot_view_or_change_another_members_stock(): void
    {
        $owner = User::factory()->create(['role' => 'member']);
        $otherMember = User::factory()->create(['role' => 'member']);
        $stock = Stock::factory()->create(['user_id' => $owner->id]);
        $this->actingAs($otherMember);

        $this->get(route('stocks.show', $stock))->assertNotFound();
        Livewire::test(StockList::class)
            ->call('openEdit', $stock->id)
            ->assertNotFound();
    }

    public function test_admin_routes_are_read_only_and_member_route_is_forbidden(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);
        $stock = Stock::factory()->create(['user_id' => $member->id]);

        $this->actingAs($admin)->get('/stocks')->assertForbidden();
        $this->get(route('admin.users'))->assertOk();
        $this->get(route('admin.users.stocks', $member))->assertOk();
        $this->get(route('admin.users.stocks', $admin))->assertNotFound();
        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'user_id' => $member->id]);
    }

    public function test_stock_adjustment_updates_status_and_records_movement(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $stock = Stock::factory()->create([
            'user_id' => $member->id,
            'quantity' => 5,
            'min_stock' => 5,
            'status' => 'low_stock',
        ]);

        $adjusted = app(StockService::class)->adjust($member, $stock, 'out', 5, 'Terjual');

        $this->assertSame(0, $adjusted->quantity);
        $this->assertSame('out_of_stock', $adjusted->status);
        $this->assertDatabaseHas('stock_movements', [
            'stock_id' => $stock->id,
            'user_id' => $member->id,
            'type' => 'out',
            'quantity' => 5,
            'note' => 'Terjual',
        ]);
        $this->assertInstanceOf(StockMovement::class, $adjusted->movements()->first());
    }

    public function test_stock_adjustment_rejects_invalid_type_and_quantity_or_excess_outflow(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $stock = Stock::factory()->create(['user_id' => $member->id, 'quantity' => 2]);

        try {
            app(StockService::class)->adjust($member, $stock, 'out', 3);
            $this->fail('Expected an overdraw validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quantity', $exception->errors());
        }

        try {
            app(StockService::class)->adjust($member, $stock, 'sideways', 1);
            $this->fail('Expected invalid adjustment type to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quantity', $exception->errors());
        }
    }

    public function test_stock_excel_imports_valid_rows_and_exports_workbook(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $path = tempnam(sys_get_temp_dir(), 'stock-import-');
        $sheet = new Spreadsheet;
        $sheet->getActiveSheet()->fromArray([
            ['name', 'sku', 'qty', 'unit', 'price', 'min_stock', 'description'],
            ['Mouse USB', 'MOUSE-01', 2, 'pcs', 75000, 3, 'Mouse kantor'],
        ]);
        (new Xlsx($sheet))->save($path);
        $sheet->disconnectWorksheets();
        $upload = new UploadedFile($path, 'items.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $excel = app(StockExcelService::class);
        $this->assertSame(1, $excel->importFor($member, $upload));
        $this->assertDatabaseHas('stocks', [
            'user_id' => $member->id,
            'sku' => 'MOUSE-01',
            'status' => 'low_stock',
        ]);

        $this->actingAs($member)->get(route('stocks.export'))->assertOk()->assertDownload('stocks.xlsx');
    }

    public function test_stock_excel_rejects_missing_headers_and_rolls_back_invalid_rows(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $path = tempnam(sys_get_temp_dir(), 'stock-import-');
        $sheet = new Spreadsheet;
        $sheet->getActiveSheet()->fromArray([
            ['name'],
            ['No quantity column'],
        ]);
        (new Xlsx($sheet))->save($path);
        $sheet->disconnectWorksheets();
        $upload = new UploadedFile($path, 'invalid.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        try {
            app(StockExcelService::class)->importFor($member, $upload);
            $this->fail('Expected missing-header validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('excelFile', $exception->errors());
        }

        $this->assertDatabaseCount('stocks', 0);
    }

    public function test_login_action_authenticates_and_registers_only_member_accounts(): void
    {
        $user = User::factory()->create([
            'username' => 'testlogin',
            'password' => Hash::make('password123'),
        ]);
        request()->setLaravelSession(app('session')->driver());
        app(LoginUserAction::class)->execute([
            'username' => 'testlogin',
            'password' => 'password123',
            'remember' => false,
        ]);
        $this->assertAuthenticatedAs($user);

        $registered = app(RegisterUserAction::class)->execute([
            'name' => 'New Member',
            'username' => 'newmember',
            'email' => 'newmember@example.test',
            'password' => 'password123',
        ]);
        $this->assertSame('member', $registered->role);
        $this->assertTrue(Hash::check('password123', $registered->password));
    }

    public function test_ai_generator_parses_structured_response_and_reports_missing_key(): void
    {
        config(['gemini.api_key' => 'test-key']);
        Http::fake([
            'generativelanguage.googleapis.com/v1beta/interactions' => Http::response([
                'steps' => [[
                    'type' => 'model_output',
                    'content' => [[
                        'type' => 'text',
                        'text' => json_encode(['items' => [[
                            'name' => 'Notebook', 'sku' => null, 'description' => 'Catatan',
                            'quantity' => 0, 'unit' => 'pcs', 'price' => null, 'min_stock' => 5,
                        ]]]),
                    ]],
                ]],
            ]),
        ]);

        $items = app(GeminiStockGeneratorService::class)
            ->generateStockItems('alat tulis', 1);
        $this->assertSame('out_of_stock', $items[0]['status']);
        Http::assertSent(fn ($request) => $request->hasHeader('x-goog-api-key', 'test-key'));

        config(['gemini.api_key' => '']);
        $this->expectException(\RuntimeException::class);
        app(GeminiStockGeneratorService::class)
            ->generateStockItems('alat tulis', 1);
    }

    public function test_auth_pages_register_and_login_through_livewire(): void
    {
        request()->setLaravelSession(app('session')->driver());
        Livewire::test(Register::class)
            ->set('name', 'Fresh Member')
            ->set('username', 'freshmember')
            ->set('email', 'fresh@example.test')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertRedirect(route('stocks.index'));
        $this->assertDatabaseHas('users', ['username' => 'freshmember', 'role' => 'member']);

        auth()->logout();
        request()->setLaravelSession(app('session')->driver());
        Livewire::test(Login::class)
            ->set('username', 'freshmember')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect(route('stocks.index'));
    }

    public function test_login_action_rejects_wrong_password_and_validates_login_form_ids(): void
    {
        User::factory()->create(['username' => 'wrongpass', 'password' => Hash::make('correct-password')]);
        $this->expectException(ValidationException::class);
        app(LoginUserAction::class)->execute(['username' => 'wrongpass', 'password' => 'incorrect']);
    }

    public function test_member_adjust_component_and_stock_detail_render_for_owner(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $stock = Stock::factory()->create(['user_id' => $member->id]);
        $this->actingAs($member);

        $this->get(route('stocks.show', $stock))->assertOk();
        Livewire::test(Adjust::class, ['stock' => $stock])
            ->set('type', 'in')
            ->set('quantity', 4)
            ->set('note', 'Restock')
            ->call('save')
            ->assertRedirect(route('stocks.index'));
    }

    public function test_create_and_edit_pages_persist_status_and_redirect(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $this->actingAs($member);
        Livewire::test(Create::class)
            ->set('name', 'New item')
            ->set('sku', 'NEW-ITEM')
            ->set('price', '1200')
            ->set('coverImage', UploadedFile::fake()->image('new-item.png'))
            ->set('quantity', 0)
            ->set('min_stock', 2)
            ->call('save')
            ->assertRedirect(route('stocks.index'));
        $stock = Stock::query()->where('name', 'New item')->firstOrFail();
        $this->assertSame('out_of_stock', $stock->status);
        Storage::fake('public');
        $stock->update(['cover_image_path' => 'stocks/old-edit.jpg']);
        Storage::disk('public')->put('stocks/old-edit.jpg', 'old');
        Livewire::test(Edit::class, ['stock' => $stock])
            ->set('name', 'Updated item')
            ->set('quantity', 10)
            ->set('coverImage', UploadedFile::fake()->image('edited.png'))
            ->call('save')
            ->assertRedirect(route('stocks.index'));
        Storage::disk('public')->assertMissing('stocks/old-edit.jpg');
        $preservedImagePath = $stock->fresh()->cover_image_path;
        Livewire::test(Edit::class, ['stock' => $stock->fresh()])
            ->set('name', 'Updated again')
            ->set('quantity', 8)
            ->call('save')
            ->assertRedirect(route('stocks.index'));
        $this->assertSame($preservedImagePath, $stock->fresh()->cover_image_path);
        $this->assertSame('available', $stock->fresh()->status);
    }

    public function test_ai_drafts_can_be_generated_selected_and_saved(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $this->actingAs($member);
        app()->instance(AiClientInterface::class, new class implements AiClientInterface
        {
            public function generateStockItems(string $prompt, int $count): array
            {
                return [[
                    'name' => 'Generated item', 'sku' => 'GEN-01', 'description' => 'Draft',
                    'quantity' => 2, 'unit' => 'pcs', 'price' => null, 'min_stock' => 5,
                    'status' => 'low_stock',
                ]];
            }
        });

        Livewire::test(StockList::class)
            ->set('aiPrompt', 'stationery')
            ->set('aiCount', 1)
            ->call('generateWithAi')
            ->assertHasNoErrors()
            ->assertSet('selectedAiItems', [0])
            ->call('saveGeneratedItems')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('stocks', [
            'user_id' => $member->id,
            'sku' => 'GEN-01',
            'status' => 'low_stock',
        ]);
    }

    public function test_create_page_accepts_optional_sku_price_and_cover(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $this->actingAs($member);
        Livewire::test(Create::class)
            ->set('name', 'Simple stock')
            ->set('sku', '')
            ->set('price', '')
            ->set('quantity', 2)
            ->set('min_stock', 5)
            ->call('save')
            ->assertRedirect(route('stocks.index'));
        $this->assertDatabaseHas('stocks', [
            'user_id' => $member->id,
            'name' => 'Simple stock',
            'sku' => null,
            'price' => null,
            'cover_image_path' => null,
        ]);
    }

    public function test_stock_list_search_filter_and_empty_state(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        Stock::factory()->create(['user_id' => $member->id, 'name' => 'Screwdriver', 'status' => 'available']);
        Stock::factory()->create(['user_id' => $member->id, 'name' => 'Hammer', 'status' => 'low_stock']);
        $this->actingAs($member);

        Livewire::test(StockList::class)
            ->set('search', 'Screwdriver')
            ->assertSee('Screwdriver')
            ->assertDontSee('Hammer')
            ->set('search', 'missing-item')
            ->assertSee('Tidak ada barang yang cocok.')
            ->set('search', '')
            ->set('statusFilter', 'low_stock')
            ->assertSee('Hammer')
            ->assertDontSee('Screwdriver');
    }

    public function test_profile_can_update_account_and_password_and_reject_bad_current_password(): void
    {
        $member = User::factory()->create(['role' => 'member', 'username' => 'profile_user', 'password' => Hash::make('old-password')]);
        $this->actingAs($member);

        Livewire::test(Profile::class)
            ->set('name', 'Updated Name')
            ->set('username', $member->username)
            ->set('email', $member->email)
            ->set('currentPassword', 'old-password')
            ->set('password', 'new-password123')
            ->set('password_confirmation', 'new-password123')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame('Updated Name', $member->fresh()->name);
        $this->assertTrue(Hash::check('new-password123', $member->fresh()->password));
    }

    public function test_profile_service_replaces_and_deletes_previous_photo(): void
    {
        Storage::fake('public');
        $member = User::factory()->create(['role' => 'member', 'username' => 'photo_user', 'profile_photo_path' => 'profiles/old.jpg']);
        Storage::disk('public')->put('profiles/old.jpg', 'old');

        app(ProfileService::class)->update(
            $member,
            ['name' => $member->name, 'username' => 'photo_user', 'email' => $member->email],
            UploadedFile::fake()->create('new.jpg', 10, 'image/jpeg'),
        );

        $this->assertDatabaseMissing('users', ['profile_photo_path' => 'profiles/old.jpg']);
        Storage::disk('public')->assertMissing('profiles/old.jpg');
        $this->assertNotNull($member->fresh()->profile_photo_path);
    }

    public function test_admin_user_list_search_updates_page_and_service_returns_stock_rows(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member', 'name' => 'Specific Member']);
        Stock::factory()->create(['user_id' => $member->id]);
        $this->actingAs($admin);

        Livewire::test(UserList::class)
            ->set('search', 'Specific')
            ->assertSee('Specific Member');
        $this->get(route('admin.users.stocks', $member))->assertSee('1');
    }

    public function test_policy_user_model_and_stock_relations_cover_role_and_ownership(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $admin = User::factory()->create(['role' => 'admin']);
        $stock = Stock::factory()->create(['user_id' => $member->id]);

        $policy = new StockPolicy;
        $this->assertTrue($policy->viewAny($member));
        $this->assertTrue($policy->viewAny($admin));
        $this->assertTrue($policy->view($admin, $stock));
        $this->assertTrue($policy->view($member, $stock));
        $this->assertFalse($policy->viewAny(User::factory()->make(['role' => 'guest'])));
        $this->assertTrue($policy->create($member));
        $this->assertFalse($policy->create($admin));
        $this->assertTrue($policy->update($member, $stock));
        $this->assertFalse($policy->update($admin, $stock));
        $this->assertTrue($policy->delete($member, $stock));
        $this->assertSame($member->id, $stock->user->id);
        $this->assertCount(1, $member->stocks);
        $this->assertFalse($member->isAdmin());
        $this->assertTrue($admin->isAdmin());
    }

    public function test_ai_service_reports_provider_failure_and_malformed_payload(): void
    {
        config(['gemini.api_key' => 'test-key']);
        Log::spy();
        Http::fake(['generativelanguage.googleapis.com/v1beta/interactions' => Http::sequence()
            ->push([], 503)
            ->push([], 503)
            ->push([], 503)
            ->push(['steps' => []])]);
        try {
            app(GeminiStockGeneratorService::class)->generateStockItems('office', 1);
            $this->fail('Expected provider failure.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Gemini', $exception->getMessage());
        }

        $this->expectException(\RuntimeException::class);
        app(GeminiStockGeneratorService::class)->generateStockItems('office', 1);
    }

    public function test_stock_list_handles_ai_provider_errors_empty_selection_invalid_selection_and_import(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $this->actingAs($member);
        app()->instance(AiClientInterface::class, new class implements AiClientInterface
        {
            public function generateStockItems(string $prompt, int $count): array
            {
                throw new \RuntimeException('Temporary AI outage.');
            }
        });

        Livewire::test(StockList::class)
            ->set('generatedItems', [['name' => 'item', 'sku' => null, 'quantity' => 1, 'unit' => 'pcs', 'price' => null, 'min_stock' => 5]])
            ->call('saveGeneratedItems')
            ->assertHasErrors('selectedAiItems');

        Livewire::test(StockList::class)
            ->set('aiPrompt', 'tools')
            ->call('generateWithAi')
            ->assertHasErrors('aiPrompt')
            ->set('generatedItems', [['name' => 'x', 'sku' => null, 'quantity' => 0, 'unit' => 'pcs', 'price' => null, 'min_stock' => 5]])
            ->set('selectedAiItems', [99])
            ->call('saveGeneratedItems')
            ->assertHasErrors('selectedAiItems');

        $path = tempnam(sys_get_temp_dir(), 'stock-livewire-import-');
        $sheet = new Spreadsheet;
        $sheet->getActiveSheet()->fromArray([
            ['name', 'qty'],
            ['Desk lamp', 4],
        ]);
        (new Xlsx($sheet))->save($path);
        $sheet->disconnectWorksheets();
        $upload = UploadedFile::fake()->createWithContent('items.xlsx', file_get_contents($path));

        Livewire::test(StockList::class)
            ->set('excelFile', $upload)
            ->call('import')
            ->assertHasNoErrors();
        $this->assertDatabaseHas('stocks', ['user_id' => $member->id, 'name' => 'Desk lamp']);
    }

    public function test_stock_list_create_and_delete_clean_up_cover_images(): void
    {
        Storage::fake('public');
        $member = User::factory()->create(['role' => 'member']);
        $this->actingAs($member);

        Livewire::test(StockList::class)
            ->call('openCreate')
            ->set('name', 'Image item')
            ->set('sku', '')
            ->set('price', '')
            ->set('coverImage', UploadedFile::fake()->image('item.png'))
            ->call('save')
            ->assertHasNoErrors();
        $stock = Stock::query()->where('name', 'Image item')->firstOrFail();
        Storage::disk('public')->assertExists($stock->cover_image_path);
        $oldImagePath = $stock->cover_image_path;
        Livewire::test(StockList::class)
            ->call('openEdit', $stock->id)
            ->set('coverImage', UploadedFile::fake()->image('replacement.png'))
            ->call('save')
            ->assertHasNoErrors();
        Storage::disk('public')->assertMissing($oldImagePath);
        $stock->refresh();

        Livewire::test(StockList::class)->call('delete', $stock->id)->assertHasNoErrors();
        Storage::disk('public')->assertMissing($stock->cover_image_path);
    }

    public function test_excel_import_skips_blank_rows_and_rolls_back_invalid_rows(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $path = tempnam(sys_get_temp_dir(), 'stock-invalid-import-');
        $sheet = new Spreadsheet;
        $sheet->getActiveSheet()->fromArray([
            ['name', 'qty'],
            ['Valid first row', 2],
            [null, null],
            ['Invalid row', -1],
        ]);
        (new Xlsx($sheet))->save($path);
        $sheet->disconnectWorksheets();
        $upload = new UploadedFile($path, 'invalid-row.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        try {
            app(StockExcelService::class)->importFor($member, $upload);
            $this->fail('Expected invalid row validation error.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('excelFile', $exception->errors());
        }
        $this->assertDatabaseCount('stocks', 0);
    }

    public function test_stock_excel_export_applies_search_and_status_filters_and_rejects_admin(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        Stock::factory()->create(['user_id' => $member->id, 'name' => 'Searched item', 'status' => 'low_stock']);
        Stock::factory()->create(['user_id' => $member->id, 'name' => 'Other item', 'status' => 'available']);
        $response = app(StockExcelService::class)->exportFor($member, ['search' => 'Searched', 'status' => 'low_stock']);
        $export = IOFactory::load($response->getFile()->getPathname());
        $this->assertSame('Searched item', $export->getActiveSheet()->getCell('A2')->getValue());
        $this->assertNull($export->getActiveSheet()->getCell('A3')->getValue());
        $export->disconnectWorksheets();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('stocks.export'))->assertForbidden();
    }

    public function test_excel_import_rejects_header_only_workbook(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $path = tempnam(sys_get_temp_dir(), 'stock-header-only-');
        $sheet = new Spreadsheet;
        $sheet->getActiveSheet()->fromArray([['name', 'qty']]);
        (new Xlsx($sheet))->save($path);
        $sheet->disconnectWorksheets();
        $upload = new UploadedFile($path, 'empty-rows.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        try {
            app(StockExcelService::class)->importFor($member, $upload);
            $this->fail('Expected empty workbook validation error.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('excelFile', $exception->errors());
        }
    }

    public function test_stock_and_movement_relations_return_both_parent_models(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $stock = Stock::factory()->create(['user_id' => $member->id]);
        $movement = StockMovement::query()->create([
            'stock_id' => $stock->id, 'user_id' => $member->id,
            'type' => 'in', 'quantity' => 1, 'note' => null,
        ]);
        $this->assertSame($stock->id, $movement->stock->id);
        $this->assertSame($member->id, $movement->user->id);
        $this->assertSame(1, $movement->quantity);
    }

    public function test_app_provider_registers_rate_limit_and_force_https_configuration(): void
    {
        $provider = new AppServiceProvider(app());
        config(['security.force_https' => true]);
        $provider->boot();
        $this->assertSame('https://', URL::formatScheme());
        $limiter = RateLimiter::limiter('app');
        $limit = $limiter(Request::create('/'));
        $this->assertSame(1000, $limit->maxAttempts);
        URL::forceScheme('http');
        config(['security.force_https' => false]);
    }

    public function test_admin_login_redirects_to_user_list(): void
    {
        User::factory()->create(['role' => 'admin', 'username' => 'admin_login', 'password' => Hash::make('password123')]);
        $this->get('/login');
        Livewire::test(Login::class)
            ->set('username', 'admin_login')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect(route('admin.users'));
    }
}
