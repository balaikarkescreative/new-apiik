<?php

namespace App\Filament\Resources\IcvResource\Pages;

use App\Filament\Resources\IcvResource;
use App\Mail\IcvBillingNotification;
use App\Models\Icv;
use App\Models\IcvBilling;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class PenerbitanKodeBilling extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = IcvResource::class;

    protected static string $view = 'filament.resources.icv-resource.pages.penerbitan-kode-billing';

    protected static ?string $title = 'Penerbitan Kode Billing';

    protected static ?string $navigationLabel = 'Penerbitan Billing';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 1;

    public function mountCanAuthorizeAccess(): void
    {
        parent::mountCanAuthorizeAccess();

        // Cek apakah user memiliki peran yang sesuai
        if (! auth()->user()->hasRole(['verifikator1'])) {
            // abort(403, 'Akses ditolak. Hanya verifikator yang dapat menerbitkan kode billing ICV.');
            redirect()->to('/');

        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Icv::query()
                    ->with([
                        'user',
                        'icvAlokasi',
                        'icvBilling',
                        'icvBuktiBayar',
                        'icvJadwal',
                    ])
                    ->whereHas('icvAlokasi', function ($query) {
                        $query->where('icv_disetujui', '>', 0);
                    })
                    // Custom ordering: yang belum ada billing di atas
                    ->leftJoin('tbl_icv_billing', 'tbl_icv.id', '=', 'tbl_icv_billing.id_icv')
                    ->leftJoin('tbl_icv_bukti_bayar', 'tbl_icv.id', '=', 'tbl_icv_bukti_bayar.id_icv')
                    ->orderByRaw('CASE
                    WHEN tbl_icv_billing.kode_billing IS NULL THEN 0
                    WHEN tbl_icv_billing.kode_billing IS NOT NULL AND tbl_icv_bukti_bayar.file_bukti_bayar IS NULL THEN 1
                    WHEN tbl_icv_bukti_bayar.file_bukti_bayar IS NOT NULL THEN 2
                    ELSE 3
                END')
                    ->orderBy('tbl_icv.tanggal_pengajuan', 'desc')
                    ->select('tbl_icv.*')
            )
            ->columns([
                TextColumn::make('tanggal_pengajuan')
                    ->label('Tanggal Pengajuan')
                    ->date()
                    ->formatStateUsing(function ($state) {
                        return $state ? $state->translatedFormat('d/m/Y') : '-';
                    })
                    ->sortable()
                    ->searchable(),

                // Fix this - remove "Tables\Columns\" prefix since TextColumn is already imported
                TextColumn::make('klinik_info')
                    ->label('Klinik/RS & Lokasi')
                    ->getStateUsing(function ($record) {
                        $klinik = $record->user?->name ?? 'N/A';
                        $lokasi = $record->user?->kotakab ?? 'N/A';

                        return '<div>'.
                            '<div class="font-medium text-sm">'.e($klinik).'</div>'.
                            '<div class="text-xs text-gray-500">'.e($lokasi).'</div>'.
                            '</div>';
                    })
                    ->html()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('user', function (Builder $query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('kotakab', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->join('users', 'tbl_icv.user_id', '=', 'users.id')
                            ->orderBy('users.name', $direction)
                            ->select('tbl_icv.*');
                    }),

                TextColumn::make('icvAlokasi.icv_disetujui')
                    ->label('ICV Disetujui')
                    ->sortable(),

                TextColumn::make('icvBilling.kode_billing')
                    ->label('Kode Billing')
                    ->searchable()
                    ->default('Belum Diterbitkan')
                    ->badge()
                    ->color(function ($record) {
                        return $record->icvBilling?->kode_billing ? 'success' : 'danger';
                    }),

                TextColumn::make('icvBilling.nominal')
                    ->label('Nominal')
                    ->money('IDR', locale: 'id')
                    ->default('Rp 0'),

                TextColumn::make('billing_status')
                    ->label('Status Billing')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if (! $record->icvBilling || ! $record->icvBilling->kode_billing) {
                            return 'Belum Ada Billing';
                        }
                        if (! $record->icvBuktiBayar || ! $record->icvBuktiBayar->file_bukti_bayar) {
                            return 'Menunggu Pembayaran';
                        }

                        return 'Sudah Bayar';
                    })
                    ->color(function ($record) {
                        if (! $record->icvBilling || ! $record->icvBilling->kode_billing) {
                            return 'danger';
                        }
                        if (! $record->icvBuktiBayar || ! $record->icvBuktiBayar->file_bukti_bayar) {
                            return 'warning';
                        }

                        return 'success';
                    })
                    ->sortable(query: function ($query, string $direction): \Illuminate\Database\Eloquent\Builder {
                        return $query->leftJoin('tbl_icv_billing as bl', 'tbl_icv.id', '=', 'bl.id_icv')
                            ->leftJoin('tbl_icv_bukti_bayar as bb', 'tbl_icv.id', '=', 'bb.id_icv')
                            ->orderByRaw('CASE
                        WHEN bl.kode_billing IS NULL THEN 0
                        WHEN bl.kode_billing IS NOT NULL AND bb.file_bukti_bayar IS NULL THEN 1
                        WHEN bb.file_bukti_bayar IS NOT NULL THEN 2
                        ELSE 3
                    END '.$direction)
                            ->select('tbl_icv.*');
                    }),
            ])
            ->actions([
                Action::make('terbitkan_billing')
                    ->label('Terbitkan Billing')
                    ->icon('heroicon-o-banknotes')
                    ->color('primary')
                    ->visible(fn ($record) => ! $record->icvBilling?->kode_billing)
                    ->modalHeading(fn ($record) => 'Terbitkan Kode Billing - '.$record->user->name)
                    ->modalWidth('2xl')
                    ->modalSubmitActionLabel('Terbitkan')
                    ->form([
                        Section::make('Informasi Pengajuan')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('nama_pemohon')
                                        ->label('Nama Pemohon')
                                        ->default(fn ($record) => $record->user->name)
                                        ->disabled()
                                        ->dehydrated(false),

                                    TextInput::make('tanggal_pengajuan')
                                        ->label('Tanggal Pengajuan')
                                        ->default(fn ($record) => $record->tanggal_pengajuan?->translatedFormat('d F Y'))
                                        ->disabled()
                                        ->dehydrated(false),

                                    TextInput::make('icv_disetujui')
                                        ->label('ICV Disetujui')
                                        ->default(fn ($record) => $record->icvAlokasi?->icv_disetujui ?? 0)
                                        ->disabled()
                                        ->dehydrated(false),
                                ]),
                            ])
                            ->collapsible(),

                        Section::make('Penerbitan Kode Billing')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('kode_billing')
                                        ->label('Kode Billing')
                                        ->numeric()
                                        ->required()
                                        ->maxLength(50),

                                    TextInput::make('nominal')
                                        ->label('Nominal Pembayaran')
                                        ->numeric()
                                        ->prefix('Rp')
                                        ->required()
                                        ->default(fn ($record) => ($record->icvAlokasi?->icv_disetujui ?? 0) * 25000)
                                        ->helperText(fn ($record) => 'Default: Rp '.number_format(($record->icvAlokasi?->icv_disetujui ?? 0) * 25000, 0, ',', '.')),
                                ]),
                            ]),
                    ])
                    ->action(function (array $data, $record) {
                        // Cek duplikasi kode billing (abaikan pengajuan saat ini)
                        $existingBilling = IcvBilling::where('kode_billing', $data['kode_billing'])
                            ->where('id_icv', '!=', $record->id)
                            ->first();

                        if ($existingBilling) {
                            Notification::make()
                                ->title('Gagal Terbitkan Billing')
                                ->danger()
                                ->body('Kode billing sudah digunakan oleh pengajuan lain.')
                                ->send();

                            return;
                        }

                        // Create billing
                        $record->icvBilling()->updateOrCreate([
                            'id_icv' => $record->id,
                        ], [
                            'kode_billing' => $data['kode_billing'],
                            'nominal' => $data['nominal'],
                            'date_updated' => now(),
                        ]);

                        $record = $record->fresh(['user', 'icvBilling']);

                        // Send email notification
                        $emailStatus = '';
                        try {
                            if ($record->user && $record->user->email) {
                                Mail::to($record->user->email)->send(
                                    new IcvBillingNotification($record, $data['kode_billing'], $data['nominal'])
                                );
                                $emailStatus = ' Email notifikasi billing telah dikirim.';
                            } else {
                                $emailStatus = ' Email tidak dapat dikirim (email user tidak valid).';
                            }
                        } catch (\Exception $e) {
                            error_log('Gagal mengirim email billing ICV: '.$e->getMessage());
                            $emailStatus = ' Email gagal dikirim.';
                        }

                        Notification::make()
                            ->title('Billing Berhasil Diterbitkan')
                            ->success()
                            ->body("Kode billing {$data['kode_billing']} berhasil diterbitkan.{$emailStatus}")
                            ->send();
                    }),

                Action::make('lihat_bukti_bayar')
                    ->label('Lihat Bukti Bayar')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->visible(fn ($record) => $record->icvBuktiBayar?->file_bukti_bayar)
                    ->modalHeading(fn ($record) => 'Bukti Pembayaran - '.$record->user->name)
                    ->modalWidth('3xl')
                    ->modalSubmitAction(false)
                    ->form([
                        Section::make('Informasi Pembayaran')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('kode_billing_info')
                                        ->label('Kode Billing')
                                        ->default(fn ($record) => $record->icvBilling?->kode_billing ?? '-')
                                        ->disabled()
                                        ->dehydrated(false),

                                    TextInput::make('nominal_info')
                                        ->label('Nominal')
                                        ->default(fn ($record) => 'Rp '.number_format($record->icvBilling?->nominal ?? 0, 0, ',', '.'))
                                        ->disabled()
                                        ->dehydrated(false),

                                    TextInput::make('tanggal_upload_info')
                                        ->label('Tanggal Upload')
                                        ->default(fn ($record) => $record->icvBuktiBayar?->date_updated?->translatedFormat('d F Y H:i') ?? '-')
                                        ->disabled()
                                        ->dehydrated(false),
                                ]),

                                Grid::make(2)->schema([
                                    TextInput::make('tanggal_pembayaran_info')
                                        ->label('Tanggal Distribusi')
                                        ->default(fn ($record) => $record->icvJadwal?->jadwal_pengambilan?->translatedFormat('d F Y') ?? 'Belum dibuat')
                                        ->disabled()
                                        ->dehydrated(false),

                                    TextInput::make('catatan_info')
                                        ->label('Catatan')
                                        ->default(fn ($record) => $record->icvBuktiBayar?->catatan ?? 'Tidak ada catatan')
                                        ->disabled()
                                        ->dehydrated(false),
                                ]),
                            ])
                            ->collapsible(),

                        Section::make('File Bukti Pembayaran')
                            ->schema([
                                FileUpload::make('file_bukti_bayar')
                                    ->label('Bukti Pembayaran')
                                    ->downloadable()
                                    ->disk('public')
                                    ->directory('icv/bukti_bayar')
                                    ->default(fn ($record) => $record->icvBuktiBayar?->file_bukti_bayar)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->helperText('Bukti pembayaran yang diunggah oleh klinik'),
                            ]),
                    ]),

                Action::make('edit_billing')
                    ->label('Edit Billing')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->visible(fn ($record) => $record->icvBilling?->kode_billing && ! $record->icvBuktiBayar?->file_bukti_bayar)
                    ->modalHeading(fn ($record) => 'Edit Kode Billing - '.$record->user->name)
                    ->modalWidth('2xl')
                    ->modalSubmitActionLabel('Update')
                    ->form([
                        Section::make('Edit Kode Billing')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('kode_billing')
                                        ->label('Kode Billing')
                                        ->numeric()
                                        ->required()
                                        ->default(fn ($record) => $record->icvBilling?->kode_billing)
                                        ->maxLength(50)
                                        ->helperText('Masukkan kode billing unik'),

                                    TextInput::make('nominal')
                                        ->label('Nominal Pembayaran')
                                        ->numeric()
                                        ->prefix('Rp')
                                        ->required()
                                        ->default(fn ($record) => $record->icvBilling?->nominal)
                                        ->helperText('Ubah nominal pembayaran jika diperlukan'),
                                ]),
                            ]),
                    ])
                    ->fillForm(fn ($record): array => [
                        'kode_billing' => $record->icvBilling?->kode_billing,
                        'nominal' => $record->icvBilling?->nominal,
                    ])
                    ->action(function (array $data, $record) {
                        // Validasi duplikasi kode billing (kecuali untuk record saat ini)
                        $existingBilling = \App\Models\IcvBilling::where('kode_billing', $data['kode_billing'])
                            ->where('id_icv', '!=', $record->id)
                            ->first();

                        if ($existingBilling) {
                            Notification::make()
                                ->title('Gagal Update Billing')
                                ->danger()
                                ->body('Kode billing sudah digunakan oleh pengajuan lain.')
                                ->send();

                            return;
                        }

                        // Update billing
                        $record->icvBilling->update([
                            'kode_billing' => $data['kode_billing'],
                            'nominal' => $data['nominal'],
                            'date_updated' => now(),
                        ]);

                        Notification::make()
                            ->title('Billing Berhasil Diupdate')
                            ->success()
                            ->body("Kode billing berhasil diubah menjadi {$data['kode_billing']}")
                            ->send();
                    }),

                Action::make('hapus_billing')
                    ->label('Hapus Billing')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => 'Hapus Kode Billing - '.$record->user->name)
                    ->modalDescription('Apakah Anda yakin ingin menghapus kode billing ICV ini?')
                    ->modalSubmitActionLabel('Ya, Hapus')
                    ->visible(fn ($record) => $record->icvBilling?->kode_billing !== null)
                    ->action(function ($record) {
                        try {
                            // Hapus data billing
                            $record->icvBilling()?->delete();

                            Notification::make()
                                ->title('Kode Billing Berhasil Dihapus')
                                ->success()
                                ->body('Kode billing ICV berhasil dihapus.')
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal Menghapus Data')
                                ->danger()
                                ->body('Terjadi kesalahan saat menghapus data: '.$e->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->striped()
            ->filters([
                DateRangeFilter::make('tanggal_pengajuan')
                    ->label('Tanggal Pengajuan')
                    ->defaultLast7Days(),

                // Filter berdasarkan User/Pemohon
                SelectFilter::make('user')
                    ->label('Pemohon/Klinik')
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        return \App\Models\User::whereHas('roles', function ($query) {
                            $query->where('name', 'user');
                        })
                            ->whereHas('icvs', function ($query) {
                                $query->whereHas('icvAlokasi', function ($q) {
                                    $q->where('icv_disetujui', '>', 0);
                                });
                            })
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(function ($user) {
                                $kotakab = $user->kotakab ? " ({$user->kotakab})" : '';

                                return [$user->id => $user->name.$kotakab];
                            });
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        return $query->where('user_id', $data['value']);
                    }),

                // Filter berdasarkan Kota/Kabupaten
                SelectFilter::make('kotakab')
                    ->label('Kota/Kabupaten')
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        return \App\Models\User::whereHas('roles', function ($query) {
                            $query->where('name', 'user');
                        })
                            ->whereHas('icvs', function ($query) {
                                $query->whereHas('icvAlokasi', function ($q) {
                                    $q->where('icv_disetujui', '>', 0);
                                });
                            })
                            ->whereNotNull('kotakab')
                            ->distinct()
                            ->orderBy('kotakab')
                            ->pluck('kotakab', 'kotakab');
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        return $query->whereHas('user', function (Builder $query) use ($data) {
                            $query->where('kotakab', $data['value']);
                        });
                    }),

                // Enhanced status billing filter
                SelectFilter::make('status_billing')
                    ->label('Status Billing')
                    ->options([
                        'belum_billing' => 'Belum Ada Billing',
                        'menunggu_bayar' => 'Menunggu Pembayaran',
                        'sudah_bayar' => 'Sudah Upload Bukti Bayar',
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['value'] === 'belum_billing',
                            fn ($q) => $q->whereDoesntHave('icvBilling')
                                ->orWhereHas('icvBilling', function ($subq) {
                                    $subq->whereNull('kode_billing');
                                })
                        )->when(
                            $data['value'] === 'menunggu_bayar',
                            fn ($q) => $q->whereHas('icvBilling', function ($subq) {
                                $subq->whereNotNull('kode_billing');
                            })
                                ->where(function ($subq) {
                                    $subq->whereDoesntHave('icvBuktiBayar')
                                        ->orWhereHas('icvBuktiBayar', function ($subq2) {
                                            $subq2->whereNull('file_bukti_bayar');
                                        });
                                })
                        )->when(
                            $data['value'] === 'sudah_bayar',
                            fn ($q) => $q->whereHas('icvBuktiBayar', function ($subq) {
                                $subq->whereNotNull('file_bukti_bayar');
                            })
                        );
                    }),

                // Filter berdasarkan Range ICV Disetujui
                SelectFilter::make('icv_range')
                    ->label('Range ICV Disetujui')
                    ->options([
                        '1-50' => '1 - 50 ICV',
                        '51-100' => '51 - 100 ICV',
                        '101-200' => '101 - 200 ICV',
                        '201+' => '200+ ICV',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        return $query->whereHas('icvAlokasi', function (Builder $query) use ($data) {
                            switch ($data['value']) {
                                case '1-50':
                                    $query->whereBetween('icv_disetujui', [1, 50]);
                                    break;
                                case '51-100':
                                    $query->whereBetween('icv_disetujui', [51, 100]);
                                    break;
                                case '101-200':
                                    $query->whereBetween('icv_disetujui', [101, 200]);
                                    break;
                                case '201+':
                                    $query->where('icv_disetujui', '>', 200);
                                    break;
                            }
                        });
                    }),
            ], FiltersLayout::AboveContent);
    }

    // Set default filter untuk menampilkan yang belum ada billing
    public function getTableFiltersFormData(): array
    {
        return [
            'status_billing' => 'belum_billing',
        ];
    }

    private function getBillingForm(): array
    {
        return [
            // Basic Information Section
            Section::make('Informasi Pengajuan')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('nama_pemohon')
                            ->label('Nama Pemohon')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('tanggal_pengajuan')
                            ->label('Tanggal Pengajuan')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('jumlah_icv_diminta')
                            ->label('Jumlah ICV Diminta')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('icv_disetujui')
                            ->label('ICV Disetujui')
                            ->disabled()
                            ->dehydrated(false),
                    ]),
                ])
                ->collapsible(),

            // Tabs section
            Tabs::make('Tahapan Billing')
                ->extraAttributes(['class' => 'flex-wrap'])
                ->activeTab(function ($record) {
                    if (! $record->icvBilling || ! $record->icvBilling->kode_billing) {
                        return 1;
                    }
                    if ($record->icvBuktiBayar && $record->icvBuktiBayar->file_bukti_bayar && ! $record->icvBuktiBayar->distribusi_icv) {
                        return 2;
                    }

                    return 2;
                })
                ->tabs([
                    // Tab 1: Penerbitan Billing
                    Tabs\Tab::make('billing')
                        ->label('1. Penerbitan Billing')
                        ->icon('heroicon-o-banknotes')
                        ->badge(function ($record) {
                            return $record->icvBilling?->kode_billing ? '✓' : '';
                        })
                        ->badgeColor(function ($record) {
                            return $record->icvBilling?->kode_billing ? 'success' : 'gray';
                        })
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('kode_billing')
                                    ->label('Kode Billing')
                                    ->numeric()
                                    ->required()
                                    ->disabled(fn ($record) => (bool) $record->icvBilling?->kode_billing)
                                    ->helperText('Masukkan kode billing unik'),

                                TextInput::make('nominal')
                                    ->label('Nominal Pembayaran')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->disabled(fn ($record) => (bool) $record->icvBilling?->kode_billing)
                                    ->helperText(fn ($record) => 'Nominal default = '.number_format($record->icvAlokasi?->icv_disetujui * 25000, 0, ',', '.')),
                            ]),

                            TextInput::make('date_billing')
                                ->label('Tanggal Penerbitan')
                                ->disabled()
                                ->formatStateUsing(fn ($record) => $record->icvBilling?->date_updated?->translatedFormat('d F Y H:i') ?? now()->translatedFormat('d F Y H:i'))
                                ->dehydrated(false),

                            Actions::make([
                                Actions\Action::make('terbitkan_billing')
                                    ->label('Terbitkan Billing')
                                    ->icon('heroicon-o-check')
                                    ->color('success')
                                    ->visible(fn ($record) => ! $record->icvBilling?->kode_billing)
                                    ->action(function (Get $get, Set $set, $record) {
                                        $kodeBilling = $get('kode_billing');
                                        $nominal = $get('nominal');

                                        if (! $kodeBilling || ! $nominal) {
                                            Notification::make()
                                                ->title('Gagal Terbitkan Billing')
                                                ->danger()
                                                ->body('Kode billing dan nominal harus diisi.')
                                                ->send();

                                            return;
                                        }

                                        // Cek duplikasi kode billing (abaikan pengajuan saat ini)
                                        $existingBilling = IcvBilling::where('kode_billing', $kodeBilling)
                                            ->where('id_icv', '!=', $record->id)
                                            ->first();

                                        if ($existingBilling) {
                                            Notification::make()
                                                ->title('Gagal Terbitkan Billing')
                                                ->danger()
                                                ->body('Kode billing sudah digunakan oleh pengajuan lain.')
                                                ->send();

                                            return;
                                        }

                                        // Update or create billing
                                        $record->icvBilling()->updateOrCreate([
                                            'id_icv' => $record->id,
                                        ], [
                                            'kode_billing' => $kodeBilling,
                                            'nominal' => $nominal,
                                            'date_updated' => now(),
                                        ]);

                                        $record = $record->fresh(['user', 'icvBilling']);

                                        // Kirim email notifikasi billing
                                        $emailStatus = '';
                                        try {
                                            if ($record->user && $record->user->email) {
                                                Mail::to($record->user->email)->send(
                                                    new IcvBillingNotification($record, $kodeBilling, $nominal)
                                                );
                                                $emailStatus = ' Email notifikasi billing telah dikirim.';
                                            } else {
                                                $emailStatus = ' Email tidak dapat dikirim (email user tidak valid).';
                                            }
                                        } catch (\Exception $e) {
                                            error_log('Gagal mengirim email billing ICV: '.$e->getMessage());
                                            $emailStatus = ' Email gagal dikirim.';
                                        }

                                        Notification::make()
                                            ->title('Billing Berhasil Diterbitkan')
                                            ->success()
                                            ->body("Kode billing {$kodeBilling} berhasil diterbitkan.{$emailStatus}")
                                            ->send();

                                        $this->redirect(PenerbitanKodeBilling::getUrl());
                                    }),
                            ]),
                        ]),

                    // Tab 2: Verifikasi Pembayaran
                    Tabs\Tab::make('verifikasi')
                        ->label('2. Verifikasi Pembayaran')
                        ->icon('heroicon-o-check-circle')
                        ->disabled(function ($record) {
                            return ! $record->icvBilling ||
                                ! $record->icvBilling->kode_billing ||
                                ! $record->icvBuktiBayar ||
                                ! $record->icvBuktiBayar->file_bukti_bayar;
                        })
                        ->badge(function ($record) {
                            return $record->icvBuktiBayar?->distribusi_icv ? '✓' : '';
                        })
                        ->badgeColor(function ($record) {
                            return $record->icvBuktiBayar?->distribusi_icv ? 'success' : 'gray';
                        })
                        ->schema([
                            \Filament\Forms\Components\Placeholder::make('pembayaran_disabled_notice')
                                ->content('⚠️ Tab ini akan aktif setelah pemohon mengunggah bukti pembayaran.')
                                ->visible(function ($record) {
                                    return ! $record->icvBuktiBayar || ! $record->icvBuktiBayar->file_bukti_bayar;
                                }),

                            TextInput::make('file_bukti_bayar_info')
                                ->label('File Bukti Bayar')
                                ->disabled()
                                ->formatStateUsing(function ($record) {
                                    return $record->icvBuktiBayar?->file_bukti_bayar
                                        ? '<a href="'.Storage::url($record->icvBuktiBayar->file_bukti_bayar).'" target="_blank" class="text-blue-500 underline">Lihat File</a>'
                                        : '-';
                                })
                                ->dehydrated(false),

                            TextInput::make('tanggal_verifikasi_info')
                                ->label('Tanggal Verifikasi')
                                ->disabled()
                                ->formatStateUsing(fn ($record) => $record->icvBuktiBayar?->date_updated?->translatedFormat('d F Y H:i') ?? '-')
                                ->dehydrated(false),

                            TextInput::make('metode_distribusi_info')
                                ->label('Metode Distribusi')
                                ->disabled()
                                ->default(fn ($record) => $record->icvBuktiBayar?->distribusi_icv ? ucfirst($record->icvBuktiBayar->distribusi_icv) : '-')
                                ->dehydrated(false),

                            Textarea::make('catatan')
                                ->label('Catatan')
                                ->disabled()
                                ->default(fn ($record) => $record->icvBuktiBayar?->catatan ?? '-')
                                ->dehydrated(false),
                        ])
                        ->collapsible(),
                ])
                ->columnSpan('full'),
        ];
    }
}
