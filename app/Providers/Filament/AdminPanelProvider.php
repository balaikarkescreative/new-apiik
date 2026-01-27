<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\CombinedAuth;
use App\Filament\Pages\EditProfil;
use App\Filament\Resources\IcvResource;
use App\Filament\Resources\IzinResource;
use App\Http\Middleware\CheckPaktaIntegritas;
use App\Models\Izin;
use Devonab\FilamentEasyFooter\EasyFooterPlugin;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->defaultThemeMode(ThemeMode::System)
            ->breadcrumbs(false)
            ->favicon(Storage::url('assets/site_favicon.png'))
            ->brandName('APIIK')
            ->brandLogo(
                Storage::url('assets/site_logo.png')
            )
            ->id('admin')
            ->path('')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->login(CombinedAuth::class)
            ->loginRouteSlug('home')
            // ->registration(CombinedAuth::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            // ->profile(EditProfile::class, isSimple: false)
            ->userMenuItems(
                [
                    MenuItem::make()
                        ->url(fn () => EditProfil::getUrl())
                        ->icon('heroicon-o-user-circle')
                        ->label('Edit Profil'),
                ]
            )
            ->navigationItems([
                NavigationItem::make('Dasbor')
                    ->icon('heroicon-o-home')
                    ->url('/')
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.pages.dashboard'))
                    ->sort(0),
                // Izin
                NavigationItem::make(function () {
                    $userId = Auth::user()?->id;
                    $existingNewPermit = Izin::where('user_id', $userId)
                        ->where(function ($query) {
                            $query->where('kategori_izin', 'Izin Baru')
                                ->orWhere('kategori_izin', 'Perpanjang Izin');
                        })
                        ->whereHas('izinDokumenPks', function ($query) {
                            $query->whereNotNull('dokumen_pks_bkk');
                        })
                        ->exists();
                    if ($existingNewPermit) {
                        return 'Buat Pengajuan Izin Perpanjang';
                    }

                    return 'Buat Pengajuan Izin Baru';
                })
                    ->icon('heroicon-o-plus')
                    ->visible(fn () => Auth::user()->hasRole('user') || Auth::user()->hasRole('admin'))
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.izin.create'))
                    ->group('Izin')
                    ->url(function (Izin $record) {
                        $userId = Auth::user()?->id;
                        $existingNewPermit = Izin::where('user_id', $userId)
                            ->where(function ($query) {
                                $query->where('kategori_izin', 'Izin Baru')
                                    ->orWhere('kategori_izin', 'Perpanjang Izin');
                            })
                            ->whereHas('izinDokumenPks', function ($query) {
                                $query->whereNotNull('dokumen_pks_bkk');
                            })
                            ->exists();
                        if ($existingNewPermit) {
                            return IzinResource::getUrl('create', ['type' => 'perpanjang']);
                        }

                        return IzinResource::getUrl('create', ['type' => 'baru']);
                    }),
                NavigationItem::make('Daftar Pengajuan Izin')
                    ->icon('heroicon-o-list-bullet')
                    ->group('Izin')
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.izin.daftar-pengajuan'))
                    ->visible(fn () => Auth::user()->hasRole('user'))
                    ->url(fn () => IzinResource::getUrl('daftar-pengajuan')),
                NavigationItem::make('Pengajuan Izin')
                    ->icon('heroicon-o-document-check')
                    ->group('Izin')
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.izin.verifikasi'))
                    ->url(fn () => IzinResource::getUrl('verifikasi'))
                    ->visible(fn () => Auth::user()->hasRole('admin')),
                NavigationItem::make('Surat Persetujuan')
                    ->icon('heroicon-o-document-text')
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.izin.surat-persetujuan'))
                    ->group('Izin')
                    ->url(fn () => IzinResource::getUrl('surat-persetujuan'))
                    ->visible(fn () => Auth::user()->hasRole('verifikator1')),
                NavigationItem::make('Kelola Pengajuan Izin')
                    ->icon('heroicon-o-cog')
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.izin.index'))
                    ->group('Izin')
                    ->url(fn () => IzinResource::getUrl('index'))
                    ->visible(fn () => Auth::user()->hasRole('admin')),
                // ICV
                NavigationItem::make('Buat Pengajuan ICV')
                    ->icon('heroicon-o-plus')
                    ->group('ICV')
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.icv.create'))
                    ->url(fn () => IcvResource::getUrl('create'))
                    ->visible(fn () => Auth::user()->hasRole('user') || Auth::user()->hasRole('admin')),
                NavigationItem::make('Daftar Pengajuan ICV')
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.icv.daftar-pengajuan'))
                    ->icon('heroicon-o-list-bullet')
                    ->group('ICV')
                    ->url(fn () => IcvResource::getUrl('daftar-pengajuan'))
                    ->visible(fn () => Auth::user()->hasRole(['verifikator2', 'user'])),
                NavigationItem::make('Pengajuan ICV')
                    ->icon('heroicon-o-document-check')
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.icv.verifikasi'))
                    ->group('ICV')
                    ->url(fn () => IcvResource::getUrl('verifikasi'))
                    ->visible(fn () => Auth::user()->hasRole('admin')),
                NavigationItem::make('Penerbitan Kode Billing')
                    ->icon('heroicon-o-credit-card')
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.icv.penerbitan-billing'))
                    ->group('ICV')
                    ->url(fn () => IcvResource::getUrl('penerbitan-billing'))
                    ->visible(fn () => Auth::user()->hasRole('verifikator1')),

                NavigationItem::make('Jadwal Distribusi ICV')
                    ->icon('heroicon-o-calendar')
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.icv.jadwal-distribusi'))
                    ->group('ICV')
                    ->url(fn () => IcvResource::getUrl('jadwal-distribusi'))
                    ->visible(fn () => Auth::user()->hasRole('verifikator1') || Auth::user()->hasRole('pos')),
                NavigationItem::make('Surar Bukti Barang Keluar')
                    ->icon('heroicon-o-document-text')
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.icv.upload-sbbk'))
                    ->group('ICV')
                    ->url(fn () => IcvResource::getUrl('upload-sbbk'))
                    ->visible(fn () => Auth::user()->hasRole('verifikator1')),
                NavigationItem::make('Kelola Pengajuan ICV')
                    ->icon('heroicon-o-cog')
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.icv.index'))
                    ->group('ICV')
                    ->url(fn () => IcvResource::getUrl('index'))
                    ->visible(fn () => Auth::user()->hasRole('admin')),

                NavigationItem::make('Verifikasi Dokumen ICV')
                    ->icon('heroicon-o-document-check')
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.icv.verifikasi-dokumen'))
                    ->group('ICV')
                    ->url(fn () => IcvResource::getUrl('verifikasi-dokumen'))
                    ->visible(fn () => Auth::user()->hasRole('verifikator2')),
                NavigationItem::make('Unduh Draft PKS')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->sort(100)
                    ->visible(function(){
                        if (!Auth::check() || !Auth::user()->hasRole('user')){
                            return false;
                        }
                        
                        $latestIzin = Izin::where('user_id', Auth::user()->id)
                            ->latest('tanggal_pengajuan')
                            ->first();
                        
                        $suratPersetujuan = \App\Models\SuratPersetujuanIcv::where('id_akun', Auth::user()->id)
                            ->where('masa_berlaku', '>=', now())
                            ->exists();
                        
                        return $latestIzin 
                            && $latestIzin->izinVisitasi 
                            && $latestIzin->izinVisitasi->status === 'Memenuhi syarat'
                            && $suratPersetujuan;
                    })
                    ->url(fn () => Storage::url('draft-pks/Draft Perjanjian Kerjasama (PKS) Vaksin_OK_OK.doc'), true),
                    // ->visible(fn () => Auth::user()->hasRole('user')),

                NavigationItem::make('File Surat Persetujuan ICV')
                    ->icon('heroicon-o-document-check')
                    ->sort(101)
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.izin.izin-persetujuan'))
                    ->url(fn () => IzinResource::getUrl('izin-persetujuan'))
                    ->visible(fn () => Auth::user()->hasRole('admin') || Auth::user()->hasRole('verifikator1') || Auth::user()->hasRole('user')),
                // User

                // NavigationItem::make('Edit Profil')
                //     ->url(fn() => UserResource::getUrl('edit-profil'))
                //     ->icon('heroicon-o-user-circle')

            ])
            ->discoverResources(
                in: app_path('Filament/Resources'),
                for: 'App\\Filament\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Pages'),
                for: 'App\\Filament\\Pages'
            )
            ->pages([Pages\Dashboard::class])
            ->discoverWidgets(
                in: app_path('Filament/Widgets'),
                for: 'App\\Filament\\Widgets'
            )
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                CheckPaktaIntegritas::class,
            ])
            ->authMiddleware([Authenticate::class])
            ->colors([
                'primary' => Color::Emerald,
                'secondary' => Color::Gray,
                'success' => Color::Green,
                'danger' => Color::Red,
                'warning' => Color::Amber,
                'info' => Color::Blue,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->plugins(
                [

                    EasyFooterPlugin::make()
                        ->hiddenFromPagesEnabled()
                        ->hiddenFromPages(['home']),
                ]
            );
    }
}
