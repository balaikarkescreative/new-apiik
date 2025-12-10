<?php

namespace App\Filament\Resources\IzinResource\Pages;

use App\Filament\Resources\IzinResource;
use App\Mail\IzinPksNotification;
use App\Mail\IzinVisitasiNotification;
use App\Models\Izin;
use App\Models\SuratPersetujuanIcv;
use Filament\Forms\Components\Actions as FormActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class SuratPersetujuan extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = IzinResource::class;

    protected static string $view = 'filament.resources.izin-resource.pages.surat-persetujuan';

    protected static ?string $title = 'Surat Persetujuan';

    protected static ?string $navigationLabel = 'Surat Persetujuan';

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    // public static function shouldRegisterNavigation(): bool
    // {
    //     return auth()->user()?->hasRole('verifaktor1');
    // }

    public function mountCanAuthorizeAccess(): void
    {
        parent::mountCanAuthorizeAccess();

        // Cek apakah user memiliki peran yang sesuai
        if (! auth()->user()->hasRole(['verifikator1'])) {
            // abort(403, 'Akses ditolak. Hanya verifikator 1 yang dapat mengelola surat persetujuan.');
            redirect()->to('/');

        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Izin::query()
                    ->with([
                        'user',
                        'izinDokumen',
                        'izinDokumenPks',
                        'izinPersetujuan',
                    ])
                    ->whereHas('izinDokumen', fn ($q) => $q->where('hasil', 1))
                    // Custom ordering: yang belum ada jadwal visitasi di atas
                    ->leftJoin('tbl_izin_dokumen_pks', 'tbl_izin.id', '=', 'tbl_izin_dokumen_pks.id_izin')
                    ->orderByRaw('CASE
                    WHEN tbl_izin_dokumen_pks.tanggal_visitasi IS NULL THEN 0
                    WHEN tbl_izin_dokumen_pks.tanggal_visitasi IS NOT NULL AND NOT EXISTS (
                        SELECT 1 FROM tbl_izin_persetujuan
                        WHERE tbl_izin_persetujuan.id_izin = tbl_izin.id
                        AND tbl_izin_persetujuan.file_persetujuan IS NOT NULL
                    ) THEN 1
                    WHEN EXISTS (
                        SELECT 1 FROM tbl_izin_persetujuan
                        WHERE tbl_izin_persetujuan.id_izin = tbl_izin.id
                        AND tbl_izin_persetujuan.file_persetujuan IS NOT NULL
                    ) AND tbl_izin_dokumen_pks.dokumen_pks_bkk IS NULL THEN 2
                    ELSE 3
                END')
                    ->orderBy('tbl_izin.tanggal_pengajuan', 'desc')
                    ->select('tbl_izin.*')
            )
            ->columns([
                TextColumn::make('tanggal_pengajuan')
                    ->label('Tanggal Pengajuan')
                    ->description(fn ($record) => $record->nomor_surat)
                    ->formatStateUsing(fn ($state): string => $state->translatedFormat('d F Y'))
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('tanggal_pengajuan', 'like', "%{$search}%")
                            ->orWhere('nomor_surat', 'like', "%{$search}%");
                    }),

                Tables\Columns\TextColumn::make('klinik_info')
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
                        return $query->join('users', 'tbl_izin.user_id', '=', 'users.id')
                            ->orderBy('users.name', $direction)
                            ->select('tbl_izin.*');
                    }),

                TextColumn::make('kategori_izin')
                    ->label('Kategori Izin'),

                // Status tab yang sedang aktif
                TextColumn::make('current_step')
                    ->label('Status Persetujuan')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        return $this->getCurrentStep($record);
                    })
                    ->color(function ($state, $record) {
                        return $this->getCurrentStepColor($record);
                    })
                    ->sortable(query: function ($query, string $direction): \Illuminate\Database\Eloquent\Builder {
                        return $query->leftJoin('tbl_izin_dokumen_pks as dp', 'tbl_izin.id', '=', 'dp.id_izin')
                            ->leftJoin('tbl_izin_persetujuan as ip', 'tbl_izin.id', '=', 'ip.id_izin')
                            ->orderByRaw('CASE
                            WHEN dp.tanggal_visitasi IS NULL THEN 0
                            WHEN dp.tanggal_visitasi IS NOT NULL AND ip.file_persetujuan IS NULL THEN 1
                            WHEN ip.file_persetujuan IS NOT NULL AND dp.dokumen_pks_bkk IS NULL THEN 2
                            ELSE 3
                        END '.$direction)
                            ->select('tbl_izin.*');
                    }),
            ])
            ->actions([
                Action::make('proses_persetujuan')
                    ->label('Proses')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->modalHeading(fn ($record) => 'Proses Surat Persetujuan - '.$record->user->name)
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false) // Tidak menampilkan tombol submit modal
                    ->form($this->getPersetujuanForm())
                    ->fillForm(function ($record) {
                        return [
                            // Basic info
                            'nama_pemohon' => $record->user->name,
                            'tanggal_pengajuan' => $record->tanggal_pengajuan->translatedFormat('d F Y'),
                            'kategori_izin' => $record->kategori_izin,

                            // Tab fields
                            'tanggal_visitasi' => $record->izinDokumenPks?->tanggal_visitasi,
                            'tanggal_persetujuan' => $record->izinPersetujuan?->date_updated ?? now(),
                            'file_persetujuan' => $record->izinPersetujuan?->file_persetujuan,
                            'nomor_surat' => SuratPersetujuanIcv::where('id_akun', $record->user->id)->first()->nomor_surat ?? '',
                            'masa_berlaku' => SuratPersetujuanIcv::where('id_akun', $record->user->id)->first()->masa_berlaku ?? '',
                            'dokumen_pks_bkk' => $record->izinDokumenPks?->dokumen_pks_bkk,
                        ];
                    }),
            ])
            ->striped()
            ->filters([
                // Periode Filter
                DateRangeFilter::make('tanggal_pengajuan')
                    ->label('Periode Pengajuan')
                    ->defaultLast7Days(),

                // Filter lengkap berdasarkan semua kategori izin yang ada
                Tables\Filters\SelectFilter::make('kategori_izin')
                    ->label('Kategori Izin')
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        return \App\Models\Izin::whereHas('izinDokumen', function ($query) {
                            $query->where('hasil', 1);
                        })
                            ->whereNotNull('kategori_izin')
                            ->distinct()
                            ->orderBy('kategori_izin')
                            ->pluck('kategori_izin', 'kategori_izin')
                            ->filter() // Remove empty values
                            ->sort(); // Sort alphabetically
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        return $query->where('kategori_izin', $data['value']);
                    }),

                // Filter berdasarkan User/Pemohon
                Tables\Filters\SelectFilter::make('user')
                    ->label('Pemohon/Klinik')
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        return \App\Models\User::whereHas('roles', function ($query) {
                            $query->where('name', 'user');
                        })
                            ->whereHas('izins', function ($query) {
                                $query->whereHas('izinDokumen', function ($q) {
                                    $q->where('hasil', 1);
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
                Tables\Filters\SelectFilter::make('kotakab')
                    ->label('Kota/Kabupaten')
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        return \App\Models\User::whereHas('roles', function ($query) {
                            $query->where('name', 'user');
                        })
                            ->whereHas('izins', function ($query) {
                                $query->whereHas('izinDokumen', function ($q) {
                                    $q->where('hasil', 1);
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

                // Filter berdasarkan Status Persetujuan (existing filter)
                Tables\Filters\SelectFilter::make('status_persetujuan')
                    ->label('Status Persetujuan')
                    ->options([
                        'belum_visitasi' => 'Perlu Jadwal Visitasi',
                        'belum_persetujuan' => 'Perlu Upload Persetujuan',
                        'belum_pks' => 'Perlu Upload PKS',
                        'selesai' => 'Selesai',
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['value'] === 'belum_visitasi',
                            fn ($q) => $q->whereDoesntHave('izinDokumenPks')
                                ->orWhereHas('izinDokumenPks', function ($subq) {
                                    $subq->whereNull('tanggal_visitasi');
                                })
                        )->when(
                            $data['value'] === 'belum_persetujuan',
                            fn ($q) => $q->whereHas('izinDokumenPks', function ($subq) {
                                $subq->whereNotNull('tanggal_visitasi');
                            })
                                ->whereDoesntHave('izinPersetujuan')
                        )->when(
                            $data['value'] === 'belum_pks',
                            fn ($q) => $q->whereHas('izinPersetujuan', function ($subq) {
                                $subq->whereNotNull('file_persetujuan');
                            })
                                ->whereHas('izinDokumenPks', function ($subq) {
                                    $subq->whereNull('dokumen_pks_bkk');
                                })
                        )->when(
                            $data['value'] === 'selesai',
                            fn ($q) => $q->whereHas('izinPersetujuan', function ($subq) {
                                $subq->whereNotNull('file_persetujuan');
                            })
                                ->whereHas('izinDokumenPks', function ($subq) {
                                    $subq->whereNotNull('dokumen_pks_bkk');
                                })
                        );
                    }),
            ], FiltersLayout::AboveContent);
    }

    // Set default filter untuk menampilkan yang belum ada visitasi
    public function getTableFiltersFormData(): array
    {
        return [
            'status_persetujuan' => 'belum_visitasi',
        ];
    }

    private function getCurrentStep($record): string
    {
        if (! $record->izinDokumenPks?->tanggal_visitasi) {
            return 'Perlu Jadwal Visitasi';
        }
        if (! $record->izinPersetujuan) {
            return 'Perlu Upload Persetujuan';
        }
        if (! $record->izinDokumenPks?->dokumen_pks_bkk) {
            return 'Perlu Upload PKS';
        }

        return 'Selesai';
    }

    private function getCurrentStepColor($record): string
    {
        $state = $this->getCurrentStep($record);

        return match ($state) {
            'Selesai' => 'success',
            'Perlu Jadwal Visitasi' => 'warning',
            default => 'info'
        };
    }

    private function getActiveTab($record): string
    {
        if (! $record->izinDokumenPks?->tanggal_visitasi) {
            return 1;
        }
        if (! $record->izinPersetujuan) {
            return 2;
        }
        if (! $record->izinDokumenPks?->dokumen_pks_bkk) {
            return 3;
        }

        return 1;
    }

    private function getPersetujuanForm(): array
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

                        TextInput::make('kategori_izin')
                            ->label('Kategori Izin')
                            ->disabled()
                            ->dehydrated(false),
                    ]),
                ])
                ->collapsible()
                ->collapsed(),

            // Step-by-step tabs
            Tabs::make('Tahapan Persetujuan')
                ->extraAttributes(['class' => 'flex-wrap'])
                ->activeTab(function ($record) {
                    return $this->getActiveTab($record);
                })
                ->tabs([
                    // Tab 1: Jadwal Visitasi
                    Tabs\Tab::make('visitasi')
                        ->label('1. Jadwal Visitasi')
                        ->icon('heroicon-o-calendar')
                        ->badge(function ($record) {
                            return $record->izinDokumenPks?->tanggal_visitasi ? '✓' : '';
                        })
                        ->badgeColor(function ($record) {
                            return $record->izinDokumenPks?->tanggal_visitasi ? 'success' : 'gray';
                        })
                        ->schema([
                            DatePicker::make('tanggal_visitasi')
                                ->label('Tanggal Visitasi')
                                ->native(false)
                                ->displayFormat('d F Y')
                                ->minDate(now()->addDay())
                                ->helperText(fn ($record) => $record->izinDokumenPks?->tanggal_visitasi ?
                                    'Jadwal visitasi telah ditetapkan pada '.$record->izinDokumenPks->tanggal_visitasi->translatedFormat('d F Y') :
                                    'Tetapkan jadwal visitasi untuk pemohon.'),

                            FormActions::make([
                                FormActions\Action::make('simpan_visitasi')
                                    ->label('Simpan/Update Jadwal')
                                    ->icon('heroicon-o-check')
                                    ->color('success')
                                    // Selalu tampilkan tombol simpan
                                    ->visible(fn ($record) => true)
                                    ->action(function (Get $get, Set $set, $record) {
                                        $tanggalVisitasi = $get('tanggal_visitasi');
                                        if (! $tanggalVisitasi) {
                                            Notification::make()
                                                ->title('Gagal Menetapkan Jadwal')
                                                ->danger()
                                                ->body('Tanggal visitasi harus dipilih.')
                                                ->send();

                                            return;
                                        }

                                        $record->izinDokumenPks()->updateOrCreate(
                                            ['id_izin' => $record->id],
                                            ['tanggal_visitasi' => $tanggalVisitasi]
                                        );

                                        $record = $record->fresh([
                                            'izinDokumenPks',
                                            'user',
                                        ]);

                                        try {
                                            if ($record->user && $record->user->email) {
                                                Mail::to($record->user->email)->send(
                                                    new IzinVisitasiNotification($record)
                                                );
                                                $emailStatus = ' Email penjadwalan visitasi telah dikirim.';
                                            } else {
                                                $emailStatus = ' Email tidak dikirim (user tidak memiliki email).';
                                            }
                                        } catch (\Exception $e) {
                                            error_log('Failed to send visitasi email: '.$e->getMessage());
                                            $emailStatus = ' Email gagal dikirim.';
                                        }

                                        Notification::make()
                                            ->title('Visitasi Berhasil Dijadwalkan')
                                            ->success()
                                            ->body('Tanggal visitasi telah ditetapkan.'.$emailStatus)
                                            ->send();

                                        // Refresh record
                                        $this->redirect(SuratPersetujuan::getUrl());
                                    }),

                                FormActions\Action::make('hapus_visitasi')
                                    ->label('Hapus Jadwal')
                                    ->icon('heroicon-o-trash')
                                    ->color('danger')
                                    ->visible(fn ($record) => $record->izinDokumenPks && $record->izinDokumenPks->tanggal_visitasi)
                                    ->requiresConfirmation()
                                    ->modalHeading('Hapus Jadwal Visitasi')
                                    ->modalDescription('Apakah Anda yakin ingin menghapus jadwal visitasi ini? Tindakan ini tidak dapat dibatalkan.')
                                    ->modalSubmitActionLabel('Ya, Hapus')
                                    ->action(function ($record) {
                                        if ($record->izinDokumenPks) {
                                            // Hapus hanya tanggal visitasi, bukan seluruh record
                                            $record->izinDokumenPks->update([
                                                'tanggal_visitasi' => null,
                                            ]);
                                        }

                                        $record->refresh();

                                        Notification::make()
                                            ->title('Jadwal Visitasi Berhasil Dihapus')
                                            ->success()
                                            ->body('Jadwal visitasi telah dihapus.')
                                            ->send();

                                        $this->redirect(SuratPersetujuan::getUrl());
                                    }),
                            ]),
                        ]),

                    // Tab 2: Upload Persetujuan
                    Tabs\Tab::make('persetujuan')
                        ->label('2. Upload Persetujuan')
                        ->icon('heroicon-o-check-circle')
                        ->disabled(function ($record) {
                            return ! $record->izinDokumenPks || ! $record->izinDokumenPks->tanggal_visitasi;
                        })
                        ->badge(function ($record) {
                            return $record->izinPersetujuan ? '✓' : '';
                        })
                        ->badgeColor(function ($record) {
                            return $record->izinPersetujuan ? 'success' : 'gray';
                        })
                        ->schema([
                            \Filament\Forms\Components\Placeholder::make('persetujuan_disabled_notice')
                                ->content('⚠️ Tab ini akan aktif setelah jadwal visitasi ditetapkan.')
                                ->visible(function ($record) {
                                    return ! $record->izinDokumenPks || ! $record->izinDokumenPks->tanggal_visitasi;
                                }),

                            Grid::make(2)->schema([
                                TextInput::make('visitasi_info')
                                    ->label('Tanggal Visitasi')
                                    ->disabled()
                                    ->formatStateUsing(fn ($record) => $record->izinDokumenPks?->tanggal_visitasi?->translatedFormat('d F Y') ?? 'Belum dijadwalkan')
                                    ->dehydrated(false),
                            ])->visible(fn ($record) => $record->izinDokumenPks?->tanggal_visitasi !== null),

                            DatePicker::make('tanggal_persetujuan')
                                ->label('Tanggal Persetujuan')
                                ->native(false)
                                ->displayFormat('d F Y')
                                ->default(now()),

                            FileUpload::make('file_persetujuan')
                                ->label('Surat Persetujuan')
                                ->acceptedFileTypes(['application/pdf'])
                                ->maxSize(3 * 1024) // Maksimal 3MB
                                ->directory('surat-persetujuan')
                                ->getUploadedFileNameForStorageUsing(function ($file) {
                                    $extension = $file->getClientOriginalExtension();

                                    return 'Surat Persetujuan ['.date('dmyHis').'].'.$extension;
                                })
                                ->openable()
                                ->downloadable()
                                ->helperText(fn ($record) => $record->izinPersetujuan ?
                                    'Surat persetujuan telah diupload. Anda dapat mengganti file jika diperlukan.' :
                                    'Upload file surat persetujuan dalam format PDF (Maksimal 3MB)'),

                            TextInput::make('nomor_surat')
                                ->label('Nomor Surat Persetujuan')
                                ->formatStateUsing(function ($record) {
                                    return SuratPersetujuanIcv::where('id_akun', $record->user->id)->first()->nomor_surat ?? '';
                                })
                                ->required(),

                            DatePicker::make('masa_berlaku')
                                ->label('Masa Berlaku Izin')
                                ->formatStateUsing(function ($record) {
                                    return SuratPersetujuanIcv::where('id_akun', $record->user->id)->first()->masa_berlaku ?? '';
                                })
                                ->native(false)
                                ->required()
                                ->displayFormat('d F Y'),

                            FormActions::make([
                                FormActions\Action::make('simpan_persetujuan')
                                    ->label('Simpan/Update Persetujuan')
                                    ->icon('heroicon-o-check')
                                    ->color('success')
                                    ->visible(fn ($record) => true)
                                    ->action(function (Get $get, Set $set, $record) {
                                        $filePersetujuan = $get('file_persetujuan');
                                        $tanggalPersetujuan = $get('tanggal_persetujuan');
                                        $nomorSurat = $get('nomor_surat');
                                        $masaBerlaku = $get('masa_berlaku');

                                        if (! $filePersetujuan) {
                                            Notification::make()
                                                ->title('Gagal Menyimpan Persetujuan')
                                                ->danger()
                                                ->body('File surat persetujuan harus diupload.')
                                                ->send();

                                            return;
                                        }

                                        if (! $nomorSurat) {
                                            Notification::make()
                                                ->title('Gagal Menyimpan Persetujuan')
                                                ->danger()
                                                ->body('Nomor surat persetujuan harus diisi.')
                                                ->send();

                                            return;
                                        }

                                        if (! $masaBerlaku) {
                                            Notification::make()
                                                ->title('Gagal Menyimpan Persetujuan')
                                                ->danger()
                                                ->body('Masa berlaku izin harus diisi.')
                                                ->send();

                                            return;
                                        }

                                        $userId = $record->user_id;

                                        // UpdateOrCreate SuratPersetujuanIcv
                                        SuratPersetujuanIcv::updateOrCreate(
                                            ['id_akun' => $userId],
                                            [
                                                'nomor_surat' => $nomorSurat,
                                                'tanggal_surat' => $tanggalPersetujuan,
                                                'masa_berlaku' => $masaBerlaku,
                                                'status_approval' => SuratPersetujuanIcv::STATUS_APPROVED,
                                                'approved_by' => auth()->id(),
                                                'approved_at' => now(),
                                            ]
                                        );

                                        // Handle file upload
                                        $filePath = null;
                                        if (is_array($filePersetujuan)) {
                                            $file_uuid = array_key_first($filePersetujuan);
                                            $uploadedFile = $filePersetujuan[$file_uuid];
                                            if (is_string($uploadedFile)) {
                                                // Sudah berupa path file lama
                                                $filePath = $uploadedFile;
                                            } elseif ($uploadedFile && method_exists($uploadedFile, 'getClientOriginalExtension')) {
                                                // File baru diupload
                                                $filePath = \Storage::disk('public')
                                                    ->putFileAs(
                                                        'surat-persetujuan',
                                                        $uploadedFile,
                                                        'Surat Persetujuan '.$record->user->name.' ['.now()->format('dmYHis').'].'.$uploadedFile->getClientOriginalExtension()
                                                    );
                                            }
                                        } elseif (is_string($filePersetujuan)) {
                                            $filePath = $filePersetujuan;
                                        }

                                        if (! $filePath) {
                                            Notification::make()
                                                ->title('Gagal Menyimpan Persetujuan')
                                                ->danger()
                                                ->body('Terjadi kesalahan saat memproses file.')
                                                ->send();

                                            return;
                                        }

                                        $record->izinPersetujuan()->updateOrCreate(
                                            ['id_izin' => $record->id],
                                            [
                                                'date_updated' => $tanggalPersetujuan ?? now(),
                                                'file_persetujuan' => $filePath,
                                                'status' => 1,
                                            ]
                                        );

                                        // Update file_persetujuan di SuratPersetujuanIcv
                                        SuratPersetujuanIcv::where('id_akun', $userId)->update([
                                            'file_persetujuan' => $filePath,
                                        ]);

                                        $record = $record->fresh([
                                            'izinPersetujuan',
                                            'user',
                                        ]);

                                        try {
                                            if ($record->user && $record->user->email) {
                                                \Mail::to($record->user->email)->send(
                                                    new \App\Mail\IzinPersetujuanNotification($record)
                                                );
                                                $emailStatus = ' Email persetujuan telah dikirim.';
                                            } else {
                                                $emailStatus = ' Email tidak dikirim (user tidak memiliki email).';
                                            }
                                        } catch (\Exception $e) {
                                            \Log::error('Failed to send approval email: '.$e->getMessage());
                                            $emailStatus = ' Email gagal dikirim.';
                                            error_log('Failed to send approval email: '.$e->getMessage());
                                        }

                                        Notification::make()
                                            ->title('Persetujuan Berhasil')
                                            ->success()
                                            ->body('Izin telah disetujui dan surat persetujuan telah diupload.'.$emailStatus)
                                            ->send();

                                        $this->redirect(\App\Filament\Resources\IzinResource\Pages\SuratPersetujuan::getUrl());
                                    }),

                                FormActions\Action::make('hapus_persetujuan')
                                    ->label('Hapus Persetujuan')
                                    ->icon('heroicon-o-trash')
                                    ->color('danger')
                                    ->visible(fn ($record) => $record->izinPersetujuan && $record->izinPersetujuan->file_persetujuan)
                                    ->requiresConfirmation()
                                    ->modalHeading('Hapus Persetujuan')
                                    ->modalDescription('Apakah Anda yakin ingin menghapus persetujuan ini? Tindakan ini akan menghapus data persetujuan dan data terkait di Surat Persetujuan ICV. Tindakan ini tidak dapat dibatalkan.')
                                    ->modalSubmitActionLabel('Ya, Hapus')
                                    ->action(function ($record, Get $get) {
                                        $userId = $record->user_id;

                                        // Hapus file persetujuan dari storage jika ada
                                        if ($record->izinPersetujuan && $record->izinPersetujuan->file_persetujuan) {
                                            try {
                                                \Storage::disk('public')->delete($record->izinPersetujuan->file_persetujuan);
                                            } catch (\Exception $e) {
                                                error_log('Failed to delete approval file: '.$e->getMessage());
                                            }
                                        }

                                        // Hapus atau reset data terkait di SuratPersetujuanIcv
                                        $suratPersetujuan = SuratPersetujuanIcv::where('id_akun', $userId)->where('nomor_surat', $get('nomor_surat'))->first();
                                        if ($suratPersetujuan) {
                                            $suratPersetujuan->delete();
                                        }

                                        // Hapus record dari tabel IzinPersetujuan
                                        if ($record->izinPersetujuan) {
                                            $record->izinPersetujuan->delete();
                                        }

                                        $record->refresh();

                                        Notification::make()
                                            ->title('Persetujuan Berhasil Dihapus')
                                            ->success()
                                            ->body('Data persetujuan dan file terkait telah dihapus.')
                                            ->send();

                                        $this->redirect(SuratPersetujuan::getUrl());
                                    }),
                            ]),
                        ]),

                    // Tab 3: Upload PKS
                    Tabs\Tab::make('pks')
                        ->label('3. Upload PKS')
                        ->icon('heroicon-o-document-arrow-up')
                        ->disabled(function ($record) {
                            return ! $record->izinPersetujuan;
                        })
                        ->badge(function ($record) {
                            return $record->izinDokumenPks?->dokumen_pks_bkk ? '✓' : '';
                        })
                        ->badgeColor(function ($record) {
                            return $record->izinDokumenPks?->dokumen_pks_bkk ? 'success' : 'gray';
                        })
                        ->schema([
                            \Filament\Forms\Components\Placeholder::make('pks_disabled_notice')
                                ->content('⚠️ Tab ini akan aktif setelah persetujuan diupload.')
                                ->visible(function ($record) {
                                    return ! $record->izinPersetujuan;
                                }),

                            Grid::make(2)->schema([
                                TextInput::make('persetujuan_info')
                                    ->label('Tanggal Persetujuan')
                                    ->disabled()
                                    ->formatStateUsing(fn ($record) => $record->izinPersetujuan?->date_updated?->translatedFormat('d F Y') ?? 'Belum disetujui')
                                    ->dehydrated(false),

                                TextInput::make('file_persetujuan_info')
                                    ->label('File Persetujuan')
                                    ->disabled()
                                    ->formatStateUsing(function ($record) {
                                        return $record->izinPersetujuan?->file_persetujuan ?
                                            basename($record->izinPersetujuan->file_persetujuan) : 'Belum ada file';
                                    })
                                    ->dehydrated(false),
                            ])->visible(fn ($record) => $record->izinPersetujuan !== null),

                            FileUpload::make('dokumen_pks_bkk')
                                ->label('File Perjanjian Kerjasama')
                                ->acceptedFileTypes(['application/pdf'])
                                ->maxSize(3 * 1024) // Maksimal 3MB
                                ->directory('perjanjian-kerjasama')
                                ->getUploadedFileNameForStorageUsing(function ($file) {
                                    $extension = $file->getClientOriginalExtension();

                                    return 'PKS ['.date('dmyHis').'].'.$extension;
                                })
                                ->openable()
                                ->downloadable()
                                ->helperText(fn ($record) => $record->izinDokumenPks?->dokumen_pks_bkk ?
                                    'PKS telah diupload. Anda dapat mengganti file jika diperlukan.' :
                                    'Upload file PKS dalam format PDF (Maksimal 3MB)'),

                            FormActions::make([
                                FormActions\Action::make('simpan_pks')
                                    ->label('Simpan/Update PKS')
                                    ->icon('heroicon-o-check')
                                    ->color('success')
                                    ->visible(fn ($record) => true)
                                    ->action(function (Get $get, Set $set, $record) {
                                        $pks = $get('dokumen_pks_bkk');

                                        if (! $pks) {
                                            Notification::make()
                                                ->title('Gagal Upload PKS')
                                                ->danger()
                                                ->body('File PKS harus diupload.')
                                                ->send();

                                            return;
                                        }

                                        $file_uuid = array_key_first($pks);
                                        $pksFile = $pks[$file_uuid];

                                        if (is_string($pksFile)) {
                                            // Sudah berupa path file lama
                                            $filePath = $pksFile;
                                        } elseif (is_object($pksFile) && method_exists($pksFile, 'getClientOriginalExtension')) {
                                            // File baru diupload
                                            $filePath = \Storage::disk('public')
                                                ->putFileAs(
                                                    'perjanjian-kerjasama',
                                                    $pksFile,
                                                    'PKS '.$record->user->name.' ['.now()->format('dmYHis').'].'.$pksFile->getClientOriginalExtension()
                                                );
                                        } else {
                                            $filePath = null;
                                        }

                                        if (! $filePath) {
                                            Notification::make()
                                                ->title('Gagal Upload PKS')
                                                ->danger()
                                                ->body('Terjadi kesalahan saat memproses file PKS.')
                                                ->send();

                                            return;
                                        }

                                        $record->izinDokumenPks()->updateOrCreate(
                                            ['id_izin' => $record->id],
                                            [
                                                'dokumen_pks_bkk' => $filePath,
                                                'status_dokumen' => 'sign both',
                                            ]
                                        );

                                        try {
                                            if ($record->user && $record->user->email) {
                                                Mail::to($record->user->email)->send(
                                                    new IzinPksNotification($record)
                                                );
                                                $emailStatus = ' Email notifikasi PKS telah dikirim.';
                                            } else {
                                                $emailStatus = ' Email tidak dikirim (user tidak memiliki email).';
                                            }
                                        } catch (\Exception $e) {
                                            error_log('Failed to send PKS email: '.$e->getMessage());
                                            $emailStatus = ' Email gagal dikirim.';
                                        }

                                        Notification::make()
                                            ->title('PKS Berhasil Diupload')
                                            ->success()
                                            ->body('File Perjanjian Kerjasama telah berhasil diupload.'.$emailStatus)
                                            ->send();

                                        // Refresh record
                                        $this->redirect(SuratPersetujuan::getUrl());

                                    }),

                                FormActions\Action::make('hapus_pks')
                                    ->label('Hapus PKS')
                                    ->icon('heroicon-o-trash')
                                    ->color('danger')
                                    ->visible(fn ($record) => $record->izinDokumenPks && $record->izinDokumenPks->dokumen_pks_bkk)
                                    ->requiresConfirmation()
                                    ->modalHeading('Hapus PKS')
                                    ->modalDescription('Apakah Anda yakin ingin menghapus file PKS ini? Tindakan ini akan menghapus file PKS dan mengatur ulang status dokumen. Tindakan ini tidak dapat dibatalkan.')
                                    ->modalSubmitActionLabel('Ya, Hapus')
                                    ->action(function ($record) {
                                        // Hapus file PKS dari storage jika ada
                                        if ($record->izinDokumenPks && $record->izinDokumenPks->dokumen_pks_bkk) {
                                            try {
                                                \Storage::disk('public')->delete($record->izinDokumenPks->dokumen_pks_bkk);
                                            } catch (\Exception $e) {
                                                error_log('Failed to delete PKS file: '.$e->getMessage());
                                            }
                                        }

                                        // Hapus field dokumen_pks_bkk dan reset status_dokumen
                                        if ($record->izinDokumenPks) {
                                            $record->izinDokumenPks->update([
                                                'dokumen_pks_bkk' => null,
                                                'status_dokumen' => null,
                                            ]);
                                        }

                                        $record->refresh();

                                        Notification::make()
                                            ->title('PKS Berhasil Dihapus')
                                            ->success()
                                            ->body('File PKS telah dihapus dan status dokumen direset.')
                                            ->send();

                                        $this->redirect(SuratPersetujuan::getUrl());
                                    }),
                            ]),
                        ]),
                ]),
        ];
    }
}
