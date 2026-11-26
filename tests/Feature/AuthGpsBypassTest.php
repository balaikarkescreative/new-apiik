<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\CombinedAuth;
use App\Filament\Pages\Auth\Login;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthGpsBypassTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);
        Role::firstOrCreate(['name' => 'verifikator1']);

        if (! Schema::hasTable('tbl_surat_persetujuan_icv')) {
            Schema::create('tbl_surat_persetujuan_icv', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_akun')->nullable();
                $table->date('masa_berlaku')->nullable();
            });
        }
    }

    private function createActiveUser(bool $bypassGps = false, string $role = 'user'): User
    {
        $user = User::factory()->create([
            'username' => 'user_'.uniqid(),
            'password' => Hash::make('secret123'),
            'status_akun' => 'Aktif',
            'is_blocked' => false,
            'bypass_gps' => $bypassGps,
        ]);

        $user->assignRole($role);

        return $user;
    }

    public function test_regular_user_cannot_login_without_gps_on_combined_auth(): void
    {
        $user = $this->createActiveUser(bypassGps: false);

        Livewire::test(CombinedAuth::class)
            ->set('loginData.login', $user->email)
            ->set('loginData.password', 'secret123')
            ->set('latitude', null)
            ->set('longitude', null)
            ->set('isGps', false)
            ->call('authenticate')
            ->assertDispatched('gps-required')
            ->assertHasErrors(['loginData.login']);

        $this->assertGuest();

        $this->assertDatabaseHas('login_histories', [
            'login_identifier' => $user->email,
            'status' => 'failed',
            'is_gps' => false,
        ]);
    }

    public function test_exempt_user_can_login_without_gps_on_combined_auth(): void
    {
        $user = $this->createActiveUser(bypassGps: true);

        Livewire::test(CombinedAuth::class)
            ->set('loginData.login', $user->email)
            ->set('loginData.password', 'secret123')
            ->set('latitude', null)
            ->set('longitude', null)
            ->set('isGps', false)
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);

        $history = LoginHistory::where('user_id', $user->id)->latest('id')->first();
        $this->assertNotNull($history);
        $this->assertEquals('success', $history->status);
        $this->assertFalse((bool) $history->is_gps);
    }

    public function test_exempt_user_can_login_without_gps_on_login_page(): void
    {
        $user = $this->createActiveUser(bypassGps: true);

        Livewire::test(Login::class)
            ->set('data.login', $user->email)
            ->set('data.password', 'secret123')
            ->set('latitude', null)
            ->set('longitude', null)
            ->set('isGps', false)
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_exempt_user_with_gps_records_is_gps_true(): void
    {
        $user = $this->createActiveUser(bypassGps: true);

        Livewire::test(CombinedAuth::class)
            ->set('loginData.login', $user->email)
            ->set('loginData.password', 'secret123')
            ->set('latitude', -6.9175)
            ->set('longitude', 107.6191)
            ->set('isGps', true)
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);

        $history = LoginHistory::where('user_id', $user->id)->latest('id')->first();
        $this->assertNotNull($history);
        $this->assertEquals('success', $history->status);
        $this->assertTrue((bool) $history->is_gps);
    }

    public function test_exempt_user_with_wrong_password_still_fails_login(): void
    {
        $user = $this->createActiveUser(bypassGps: true);

        Livewire::test(CombinedAuth::class)
            ->set('loginData.login', $user->email)
            ->set('loginData.password', 'wrong-password')
            ->set('latitude', null)
            ->set('longitude', null)
            ->set('isGps', false)
            ->call('authenticate')
            ->assertHasErrors(['loginData.login']);

        $this->assertGuest();
    }

    public function test_only_admin_can_see_and_toggle_bypass_gps_in_user_resource(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));

        $admin = $this->createActiveUser(bypassGps: false, role: 'admin');
        $target = User::factory()->create([
            'username' => 'user_'.uniqid(),
            'password' => Hash::make('secret123'),
            'status_akun' => 'Aktif',
            'is_blocked' => false,
            'bypass_gps' => false,
            'kategori' => 'Klinik',
            'kotakab' => 'Kota Bandung',
            'nomor_surat_izin_operasional' => '123/TEST/2026',
            'masa_berlaku_surat_izin_operasional' => now()->addYear()->format('Y-m-d'),
            'pertanyaan1' => 'Ya',
            'pertanyaan2' => 'Ya',
            'verifikasi_akun' => 'Diterima',
        ]);
        $target->assignRole('user');

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
            ->assertFormFieldExists('bypass_gps')
            ->assertFormFieldIsVisible('bypass_gps')
            ->set('data.bypass_gps', true)
            ->assertFormSet(['bypass_gps' => true])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertTrue($target->fresh()->bypass_gps);
    }
}
