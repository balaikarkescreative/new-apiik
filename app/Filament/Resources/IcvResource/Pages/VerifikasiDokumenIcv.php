<?php

namespace App\Filament\Resources\IcvResource\Pages;

use App\Filament\Resources\IcvResource;
use App\Models\Icv;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Mail;
use App\Mail\IcvVerifikasiNotification;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Joaopaulolndev\FilamentPdfViewer\Forms\Components\PdfViewerField;
use App\Models\Izin;
use App\Models\LaporanPenggunaanIcv;

class VerifikasiDokumenIcv extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = IcvResource::class;

    protected static string $view = 'filament.resources.icv-resource.pages.verifikasi-dokumen-icv';

    protected static ?string $title = 'Verifikasi Dokumen ICV';

    protected static ?string $navigationLabel = 'Verifikasi Dokumen';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    public function mountCanAuthorizeAccess(): void
    {
        parent::mountCanAuthorizeAccess();

        // Cek apakah user memiliki peran yang sesuai
        if (!auth()->user()->hasRole(['verifikator2'])) {
            redirect()->to('/');
        }
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->getKey();
    }

    protected function getTableQuery(): Builder
    {
        return Icv::query()
            ->with(['user', 'icvCekDokumen'])
            ->where(function (Builder $query) {
                $query->whereDoesntHave('icvCekDokumen')
                    ->orWhereHas('icvCekDokumen', function (Builder $subquery) {
                        $subquery->whereNull('hasil_verif')
                            ->orWhere('hasil_verif', '2');
                    });
            });
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                // Tanggal Pengajuan
                TextColumn::make('tanggal_pengajuan')
                    ->label('Tanggal Pengajuan')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),

                // Klinik/RS + Kota/Kab (Combined) - Fixed sorting
                TextColumn::make('klinik_info')
                    ->label('Klinik/RS & Lokasi')
                    ->getStateUsing(function ($record) {
                        if (!$record || !$record->user) {
                            return '<div><div class="font-medium text-sm">N/A</div><div class="text-xs text-gray-500">N/A</div></div>';
                        }

                        $klinik = $record->user->name ?? 'N/A';
                        $lokasi = $record->user->kotakab ?? 'N/A';

                        return '<div>' .
                            '<div class="font-medium text-sm">' . e($klinik) . '</div>' .
                            '<div class="text-xs text-gray-500">' . e($lokasi) . '</div>' .
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
                            ->select('tbl_icv.*') // Only select main table columns
                            ->orderBy('users.name', $direction);
                    }),

                // Jumlah ICV
                TextColumn::make("jumlah_icv")
                    ->label("Jumlah ICV")
                    ->numeric()
                    ->alignCenter()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                // Status Dokumen dengan warna dan ikon - Fixed sorting
                TextColumn::make('status_dokumen')
                    ->label('Status Verifikasi')
                    ->suffix(function ($record) {
                        if ($record->tanggal_perbaikan && !$record->icvCekDokumen) {
                            return ' (Perbaikan)';
                        }
                        return '';
                    })
                    ->getStateUsing(function ($record) {
                        if (!$record) {
                            return 'Data Tidak Valid';
                        }

                        if (!$record->icvCekDokumen) {
                            return 'Menunggu Verifikasi';
                        }

                        return match ($record->icvCekDokumen->hasil_verif) {
                            '2' => 'Berkas Tidak Lengkap',
                            default => 'Menunggu Verifikasi'
                        };
                    })
                    ->badge()
                    ->color(function ($record) {
                        if (!$record) {
                            return 'gray';
                        }

                        if (!$record->icvCekDokumen) {
                            return 'warning';
                        }

                        return match ($record->icvCekDokumen->hasil_verif) {
                            '2' => 'danger',
                            default => 'warning'
                        };
                    })
                    ->icon(function ($record) {
                        if (!$record) {
                            return 'heroicon-o-exclamation-triangle';
                        }

                        if (!$record->icvCekDokumen) {
                            return 'heroicon-o-clock';
                        }

                        return match ($record->icvCekDokumen->hasil_verif) {
                            '2' => 'heroicon-o-x-circle',
                            default => 'heroicon-o-clock'
                        };
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->leftJoin('tbl_icv_cek_dokumen', 'tbl_icv.id', '=', 'tbl_icv_cek_dokumen.id_icv')
                            ->select('tbl_icv.*')
                            ->orderBy('tbl_icv_cek_dokumen.hasil_verif', $direction);
                    }),

                // Detail Info (toggleable)
                TextColumn::make('detail_info')
                    ->label('Detail Verifikasi')
                    ->getStateUsing(function ($record) {
                        if (!$record) {
                            return 'Data tidak valid';
                        }

                        $details = [];

                        if ($record->icvCekDokumen) {
                            if ($record->icvCekDokumen->stok_vaksin !== null) {
                                $details[] = "Stok: " . number_format($record->icvCekDokumen->stok_vaksin);
                            }

                            if ($record->icvCekDokumen->catatan) {
                                $details[] = "Catatan: " . \Str::limit($record->icvCekDokumen->catatan, 50);
                            }

                            if ($record->icvCekDokumen->date_updated) {
                                $details[] = "Diverifikasi: " . $record->icvCekDokumen->date_updated->translatedFormat('d M Y');
                            }
                        } else {
                            $details[] = "Belum diverifikasi";
                        }

                        return implode("\n", $details);
                    })
                    ->html()
                    ->formatStateUsing(function ($state) {
                        if ($state === 'Belum diverifikasi' || $state === 'Data tidak valid') {
                            return '<span class="text-xs text-gray-400">' . $state . '</span>';
                        }

                        $lines = explode("\n", $state);
                        $formatted = array_map(function ($line) {
                            return '<div class="text-xs">' . e($line) . '</div>';
                        }, $lines);

                        return '<div class="space-y-1">' . implode('', $formatted) . '</div>';
                    })
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                // Date Range Filter - Fixed
                DateRangeFilter::make('tanggal_pengajuan')
                    ->label('Tanggal Pengajuan')
                    ->defaultLast7Days(),

                // Klinik/RS Filter
                SelectFilter::make('user_id')
                    ->label('Klinik/RS')
                    ->searchable()
                    ->relationship('user', 'name')
                    ->preload(),

                // Kota/Kabupaten Filter
                SelectFilter::make('kotakab')
                    ->label('Kota/Kabupaten')
                    ->searchable()
                    ->options(function () {
                        return \App\Models\User::whereHas('icvs')
                            ->distinct()
                            ->whereNotNull('kotakab')
                            ->orderBy('kotakab')
                            ->pluck('kotakab', 'kotakab');
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value']))
                            return $query;

                        return $query->whereHas('user', function (Builder $query) use ($data) {
                            $query->where('kotakab', $data['value']);
                        });
                    })
                    ->preload(),

                // Status Verifikasi Filter
                SelectFilter::make('status_verifikasi')
                    ->label('Status Verifikasi')
                    ->options([
                        'belum_verifikasi' => 'Belum Verifikasi',
                        'perbaikan' => 'Perbaikan',
                        'berkas_tidak_lengkap' => 'Berkas Tidak Lengkap',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value']))
                            return $query;

                        return match ($data['value']) {
                            'perbaikan' => $query
                                ->whereNotNull('tanggal_perbaikan')
                                ->whereDoesntHave('icvCekDokumen'),
                            'belum_verifikasi' => $query->whereDoesntHave('icvCekDokumen')
                                ->orWhereHas('icvCekDokumen', fn($q) => $q->whereNull('hasil_verif')),
                            'berkas_tidak_lengkap' => $query->whereHas('icvCekDokumen', fn($q) => $q->where('hasil_verif', '2')),
                            default => $query,
                        };
                    }),

                // Jumlah ICV Filter
                SelectFilter::make('jumlah_icv_range')
                    ->label('Jumlah ICV')
                    ->options([
                        '1-50' => '1-50',
                        '51-100' => '51-100',
                        '101-200' => '101-200',
                        '200+' => '200+',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value']))
                            return $query;

                        return match ($data['value']) {
                            '1-50' => $query->whereBetween('jumlah_icv', [1, 50]),
                            '51-100' => $query->whereBetween('jumlah_icv', [51, 100]),
                            '101-200' => $query->whereBetween('jumlah_icv', [101, 200]),
                            '200+' => $query->where('jumlah_icv', '>', 200),
                            default => $query,
                        };
                    }),



            ], FiltersLayout::AboveContent)
            ->persistFiltersInSession()
            ->actions([
                Action::make('verifikasi_dokumen')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('primary')
                    ->size('sm')
                    ->modalHeading(fn($record) => 'Verifikasi Dokumen ICV - ' . ($record?->user?->name ?? 'Unknown'))
                    ->modalWidth('6xl')
                    ->modalSubmitAction(false)
                    ->visible(fn($record) => $record && $record->id)
                    ->form(function ($record) {
                        // Add null check for record
                        if (!$record) {
                            return [];
                        }

                        // Calculate sisa ICV and vaksin
                        $sisa_icv = LaporanPenggunaanIcv::where('id_user', $record->user_id)
                            ->where('id_jenis_vaksin', 2)
                            ->where('tgl_pelaporan', '<=', $record->tanggal_pengajuan)
                            ->orderBy('tgl_pelaporan', 'desc')
                            ->first()->sisa_icv ?? 0;

                        $vaccineCategories = [1, 2, 3, 4, 12]; // Define vaccine categories
                        $sisa_vaksin = 0;

                        foreach ($vaccineCategories as $jenisVaksin) {
                            $latestReport = LaporanPenggunaanIcv::where('id_user', $record->user_id)
                                ->where('id_jenis_vaksin', $jenisVaksin)
                                ->where('tgl_pelaporan', '<=', $record->tanggal_pengajuan)
                                ->orderBy('tgl_pelaporan', 'desc')
                                ->first();

                            if ($latestReport) {
                                $sisa_vaksin += $latestReport->sisa_vaksin;
                            }
                        }

                        return [
                            // Informasi Dasar
                            Section::make('Informasi Pengajuan')
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextInput::make('nama_pemohon')
                                            ->label('Nama Pemohon')
                                            ->default($record->user->name ?? 'N/A')
                                            ->disabled()
                                            ->dehydrated(false),

                                        TextInput::make('tanggal_pengajuan')
                                            ->label('Tanggal Pengajuan')
                                            ->default($record->tanggal_pengajuan?->translatedFormat('d F Y') ?? 'N/A')
                                            ->disabled()
                                            ->dehydrated(false),

                                        TextInput::make('jumlah_icv_diminta')
                                            ->label('Jumlah ICV Diminta')
                                            ->default($record->jumlah_icv ?? 0)
                                            ->disabled()
                                            ->dehydrated(false),
                                    ]),
                                    Grid::make(2)->schema([
                                        TextInput::make('sisa_icv')
                                            ->label('Sisa Stok ICV')
                                            ->default($sisa_icv)
                                            ->disabled()
                                            ->dehydrated(false),

                                        TextInput::make('sisa_vaksin')
                                            ->label('Sisa Stok Vaksin')
                                            ->default($sisa_vaksin)
                                            ->disabled()
                                            ->dehydrated(false),
                                    ]),
                                    Grid::make(2)->schema([
                                        TextInput::make('laporan_geotagging_link')
                                            ->label('Link Laporan Geotagging')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->formatStateUsing(function () use ($record) {
                                                $link = $record->user->laporanGeotagging?->google_drive_link;
                                                return $link ?: 'Belum ada';
                                            })
                                            ->suffixAction(
                                                \Filament\Forms\Components\Actions\Action::make('open_geotagging')
                                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                                    ->url(function () use ($record) {
                                                        return $record->user->laporanGeotagging?->google_drive_link;
                                                    })
                                                    ->openUrlInNewTab()
                                                    ->visible(function () use ($record) {
                                                        return !empty($record->user->laporanGeotagging?->google_drive_link);
                                                    })
                                            ),

                                        TextInput::make('link_sosialisasi_link')
                                            ->label('Link Sosialisasi')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->formatStateUsing(function () use ($record) {
                                                $link = $record->user->linkSosialisasi?->link;
                                                return $link ?: 'Belum ada';
                                            })
                                            ->suffixAction(
                                                \Filament\Forms\Components\Actions\Action::make('open_sosialisasi')
                                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                                    ->url(function () use ($record) {
                                                        return $record->user->linkSosialisasi?->link;
                                                    })
                                                    ->openUrlInNewTab()
                                                    ->visible(function () use ($record) {
                                                        return !empty($record->user->linkSosialisasi?->link);
                                                    })
                                            ),
                                    ]),
                                    PdfViewerField::make('file')
                                        ->label('File Persetujuan Izin')
                                        ->fileUrl(function ($record) {
                                            $IzinPersetujuan = Izin::where('user_id', $record->user_id)
                                                ->where('tanggal_pengajuan', '<=', $record->tanggal_pengajuan)
                                                ->whereHas('izinPersetujuan', function ($query) {
                                                    $query->whereNotNull('file_persetujuan')
                                                        ->where('file_persetujuan', '!=', '');
                                                })
                                                ->with([
                                                    'izinPersetujuan' => function ($query) {
                                                        $query->select('id', 'id_izin', 'file_persetujuan', 'status', 'date_updated');
                                                    }
                                                ])
                                                ->orderBy('tanggal_pengajuan', 'desc')
                                                ->first();

                                            return Storage::disk('public')->url($IzinPersetujuan?->izinPersetujuan?->file_persetujuan ?? '');
                                        })
                                ])
                                ->collapsible(),

                            // Dokumen yang Diupload
                            Section::make('Dokumen yang Diupload')
                                ->schema($this->getDocumentFields($record))
                                ->collapsible(),

                            // Form Verifikasi
                            Section::make('Form Verifikasi Dokumen')
                                ->schema([
                                    Select::make('hasil_verif')
                                        ->label('Hasil Verifikasi')
                                        ->options([
                                            '1' => 'Berkas Lengkap',
                                            '2' => 'Berkas Tidak Lengkap',
                                        ])
                                        ->default($record->icvCekDokumen?->hasil_verif)
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            if ($get('hasil_verif') === '2') {
                                                $set('stok_vaksin', 0);
                                            }
                                        }),

                                    // Riwayat Catatan Perbaikan
                                    Section::make('Riwayat Catatan Perbaikan')
                                        ->collapsible()
                                        ->visible(fn($record) => $record && !empty($record->riwayat_perbaikan))
                                        ->schema(function ($record) {
                                            $schema = [];
                                            if ($record) {
                                                foreach (($record->riwayat_perbaikan ?? []) as $index => $item) {
                                                    $schema[] = Placeholder::make("riwayat_{$index}")
                                                        ->label($item['tanggal'] ?? '-')
                                                        ->content($item['catatan'] ?? 'Tidak ada catatan');
                                                }
                                            }
                                            return $schema;
                                        }),

                                    DatePicker::make('tanggal_perbaikan')
                                        ->label('Tanggal Perbaikan')
                                        ->default($record->tanggal_perbaikan ?? null)
                                        ->native(false)
                                        ->displayFormat('d F Y H:i')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->visible(fn($record) => $record && $record->tanggal_perbaikan ? true : false),

                                    Grid::make(2)->schema([
                                        TextInput::make('stok_vaksin')
                                            ->label('Stok Vaksin Tersedia')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default($record->icvCekDokumen?->stok_vaksin ?? 0)
                                            ->visible(fn(Get $get): bool => $get('hasil_verif') === '1')
                                            ->required(fn(Get $get): bool => $get('hasil_verif') === '1'),

                                        TextInput::make('date_updated')
                                            ->label('Tanggal Verifikasi')
                                            ->disabled()
                                            ->formatStateUsing(fn() => now()->translatedFormat('d F Y H:i'))
                                            ->dehydrated(false),
                                    ]),

                                    Textarea::make('catatan')
                                        ->label('Catatan Verifikasi')
                                        ->default($record->icvCekDokumen?->catatan)
                                        ->rows(3)
                                        ->visible(fn(Get $get): bool => $get('hasil_verif') === '2')
                                        ->required(fn(Get $get): bool => $get('hasil_verif') === '2'),

                                    Actions::make([
                                        Actions\Action::make('simpan_verifikasi')
                                            ->label('Simpan Hasil Verifikasi')
                                            ->icon('heroicon-o-check')
                                            ->color('success')
                                            ->action(function (Get $get, Set $set, $record) {
                                                // Add record validation at the beginning
                                                if (!$record || !$record->id) {
                                                    Notification::make()
                                                        ->title('Error')
                                                        ->danger()
                                                        ->body('Data record tidak valid.')
                                                        ->send();
                                                    return;
                                                }

                                                $hasilVerif = $get('hasil_verif');

                                                if (!$hasilVerif) {
                                                    Notification::make()
                                                        ->title('Gagal Menyimpan Verifikasi')
                                                        ->danger()
                                                        ->body('Hasil verifikasi harus dipilih.')
                                                        ->send();
                                                    return;
                                                }

                                                if ($hasilVerif === '1' && !$get('stok_vaksin')) {
                                                    Notification::make()
                                                        ->title('Gagal Menyimpan Verifikasi')
                                                        ->danger()
                                                        ->body('Stok vaksin harus diisi untuk berkas lengkap.')
                                                        ->send();
                                                    return;
                                                }

                                                if ($hasilVerif === '2' && !$get('catatan')) {
                                                    Notification::make()
                                                        ->title('Gagal Menyimpan Verifikasi')
                                                        ->danger()
                                                        ->body('Catatan harus diisi untuk berkas tidak lengkap.')
                                                        ->send();
                                                    return;
                                                }

                                                if ($hasilVerif === '2' && $get('catatan')) {
                                                    $record->tambahCatatanPerbaikan($get('catatan'));
                                                }

                                                try {
                                                    // Simpan hasil verifikasi
                                                    $record->icvCekDokumen()->updateOrCreate(
                                                        ['id_icv' => $record->id],
                                                        [
                                                            'hasil_verif' => $hasilVerif,
                                                            'catatan' => $get('catatan') ?? '',
                                                            'stok_vaksin' => $get('stok_vaksin') ?? 0,
                                                            'jumlah_icv' => $record->jumlah_icv,
                                                            'status' => 1,
                                                            'date_updated' => now(),
                                                        ]
                                                    );

                                                    // PERBAIKAN: Refresh record dengan eager loading sebelum kirim email
                                                    $record = $record->fresh(['user', 'icvCekDokumen']);

                                                    // Kirim email notifikasi verifikasi
                                                    $emailStatus = '';
                                                    try {
                                                        if ($record->user && $record->user->email) {
                                                            Mail::to($record->user->email)->send(
                                                                new IcvVerifikasiNotification(
                                                                    $record,
                                                                    $hasilVerif,
                                                                    $get('catatan')
                                                                )
                                                            );
                                                            $emailStatus = 'Email notifikasi telah dikirim.';
                                                        } else {
                                                            $emailStatus = 'Email tidak dapat dikirim (email user tidak valid).';
                                                        }
                                                    } catch (\Exception $e) {
                                                        error_log('Gagal mengirim email verifikasi ICV: ' . $e->getMessage());
                                                        $emailStatus = 'Email gagal dikirim.';
                                                    }

                                                    $resultText = $hasilVerif == '1' ? 'Berkas Lengkap' : 'Berkas Tidak Lengkap';

                                                    Notification::make()
                                                        ->title('Verifikasi Berhasil Disimpan')
                                                        ->success()
                                                        ->body("{$resultText}. {$emailStatus}")
                                                        ->send();

                                                    // Refresh halaman
                                                    $this->redirect(VerifikasiDokumenIcv::getUrl());

                                                } catch (\Exception $e) {
                                                    Notification::make()
                                                        ->title('Error')
                                                        ->danger()
                                                        ->body('Terjadi kesalahan: ' . $e->getMessage())
                                                        ->send();
                                                }
                                            }),
                                    ]),
                                ]),


                        ];
                    })
            ])
            ->defaultSort('tanggal_pengajuan', 'desc')
            ->striped()
            ->paginated([15, 30, 50]);
    }

    private function getDocumentFields($record): array
    {
        $fields = [];

        // Add null check for record
        if (!$record) {
            return $fields;
        }

        $fileLabels = $record->getFileLabels();

        for ($i = 1; $i <= 7; $i++) {
            $fileField = "file{$i}";

            if (isset($fileLabels[$fileField])) {
                $fields[] = TextInput::make($fileField . '_info')
                    ->label($fileLabels[$fileField])
                    ->disabled()
                    ->formatStateUsing(function ($state) use ($record, $fileField) {
                        if ($record->$fileField) {
                            return basename($record->$fileField);
                        } else {
                            return 'Tidak ada file';
                        }
                    })
                    ->hintAction(
                        FormAction::make("download_{$fileField}")
                            ->icon('heroicon-o-arrow-down-tray')
                            ->label('Unduh File')
                            ->url(fn() => $record->$fileField ? asset('storage/' . $record->$fileField) : '#')
                            ->openUrlInNewTab()
                            ->visible(fn() => $record->$fileField !== null)
                    )
                    ->dehydrated(false);
            }
        }

        return $fields;
    }
}
