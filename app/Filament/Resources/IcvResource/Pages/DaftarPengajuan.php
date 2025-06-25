<?php

namespace App\Filament\Resources\IcvResource\Pages;

use App\Filament\Resources\IcvResource;
use App\Models\Icv;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Get;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Enums\FiltersLayout;

use Carbon\Carbon;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class DaftarPengajuan extends Page implements HasTable, HasInfolists
{
    use InteractsWithTable, InteractsWithInfolists;

    protected static string $resource = IcvResource::class;

    protected static string $view = 'filament.resources.icv-resource.pages.daftar-pengajuan';

    protected static ?string $title = 'Daftar Pengajuan ICV';

    protected static ?string $navigationLabel = 'Daftar Pengajuan ICV';

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?int $navigationSort = 2;

    public function mountCanAuthorizeAccess(): void
    {
        parent::mountCanAuthorizeAccess();

        // Cek apakah user memiliki peran yang sesuai
        if (!auth()->user()->hasRole(['user', 'verifikator2'])) {
            // abort(403, 'Akses ditolak. Hanya user dan verifikator yang dapat melihat pengajuan ICV.');
            redirect()->to('/');
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $baseQuery = Icv::query()->with([
                    'user',
                    'icvCekDokumen',
                    'icvAlokasi',
                    'icvBilling',
                    'icvBuktiBayar',
                    'icvJadwal',
                    'icvSbbk',
                ]);

                if (auth()->user()->hasRole('user')) {
                    return $baseQuery->where('user_id', auth()->id());
                }

                return $baseQuery;
            })
            ->columns([
                TextColumn::make('tanggal_pengajuan')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Pemohon')
                    ->getStateUsing(function ($record) {
                        $klinik = $record->user?->name ?? 'Nama tidak tersedia';
                        $lokasi = $record->user?->kotakab ?? 'Lokasi tidak diset';

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
                            ->orderBy('users.name', $direction);
                    })
                    ->visible(fn() => auth()->user()->hasRole('verifikator2')),

                TextColumn::make('jumlah_icv')
                    ->label('Jumlah')
                    ->numeric()
                    ->alignCenter(),

                TextColumn::make('progress_status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if ($record->icvSbbk && $record->icvSbbk->sinkarkes === 'Sudah') {
                            return 'Selesai';
                        }
                        if ($record->icvJadwal && $record->icvJadwal->jadwal_pengambilan) {
                            return 'Dijadwalkan';
                        }
                        if ($record->icvBuktiBayar && $record->icvBuktiBayar->file_bukti_bayar) {
                            return 'Menunggu Verifikasi';
                        }
                        if ($record->icvBilling && $record->icvBilling->kode_billing) {
                            return 'Menunggu Bayar';
                        }
                        if ($record->icvAlokasi && $record->icvAlokasi->icv_disetujui > 0) {
                            return 'Dialokasikan';
                        }
                        if ($record->icvCekDokumen) {
                            return match ($record->icvCekDokumen->hasil_verif) {
                                '1' => 'Berkas OK',
                                '2' => 'Berkas Kurang',
                                default => 'Menunggu Verifikasi'
                            };
                        }
                        return 'Diajukan';
                    })
                    ->color(function ($state) {
                        return match ($state) {
                            'Selesai' => 'success',
                            'Dijadwalkan' => 'info',
                            'Menunggu Verifikasi' => 'warning',
                            'Menunggu Bayar' => 'warning',
                            'Dialokasikan' => 'info',
                            'Berkas OK' => 'success',
                            'Berkas Kurang' => 'danger',
                            default => 'gray',
                        };
                    }),

                TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->getStateUsing(function ($record) {
                        $steps = 0;

                        // Count completed steps (max 8)
                        if ($record->icvCekDokumen && $record->icvCekDokumen->hasil_verif == '1')
                            $steps++;
                        if ($record->icvAlokasi && $record->icvAlokasi->icv_disetujui > 0)
                            $steps++;
                        if ($record->icvBilling && $record->icvBilling->kode_billing)
                            $steps++;
                        if ($record->icvBuktiBayar && $record->icvBuktiBayar->file_bukti_bayar)
                            $steps++;
                        if ($record->icvBuktiBayar && $record->icvBuktiBayar->distribusi_icv)
                            $steps++;
                        if ($record->icvJadwal && $record->icvJadwal->jadwal_pengambilan)
                            $steps++;
                        if ($record->icvSbbk && $record->icvSbbk->file_sbbk)
                            $steps++;
                        if ($record->icvSbbk && $record->icvSbbk->sinkarkes === 'Sudah')
                            $steps++;

                        return $steps . '/8';
                    })
                    ->badge()
                    ->color(function ($state) {
                        $completed = (int) explode('/', $state)[0];
                        return match (true) {
                            $completed >= 8 => 'success',
                            $completed >= 6 => 'info',
                            $completed >= 4 => 'warning',
                            default => 'gray',
                        };
                    }),
            ])
            ->filters([
                SelectFilter::make('progress_status')
                    ->label('Status')
                    ->options([
                        'diajukan' => 'Diajukan',
                        'berkas_kurang' => 'Berkas Kurang',
                        'berkas_ok' => 'Berkas OK',
                        'dialokasikan' => 'Dialokasikan',
                        'menunggu_bayar' => 'Menunggu Bayar',
                        'menunggu_verifikasi' => 'Menunggu Verifikasi',
                        'dijadwalkan' => 'Dijadwalkan',
                        'selesai' => 'Selesai',
                    ])
                    ->query(function ($query, array $data) {
                        if (!($data['value'] ?? null))
                            return $query;

                        return match ($data['value']) {
                            'diajukan' => $query->whereDoesntHave('icvCekDokumen'),
                            'berkas_kurang' => $query->whereHas('icvCekDokumen', fn($q) => $q->where('hasil_verif', '2')),
                            'berkas_ok' => $query->whereHas('icvCekDokumen', fn($q) => $q->where('hasil_verif', '1')),
                            'dialokasikan' => $query->whereHas('icvAlokasi', fn($q) => $q->where('icv_disetujui', '>', 0)),
                            'menunggu_bayar' => $query->whereHas('icvBilling', fn($q) => $q->whereNotNull('kode_billing')),
                            'menunggu_verifikasi' => $query->whereHas('icvBuktiBayar', fn($q) => $q->whereNotNull('file_bukti_bayar')),
                            'dijadwalkan' => $query->whereHas('icvJadwal', fn($q) => $q->whereNotNull('jadwal_pengambilan')),
                            'selesai' => $query->whereHas('icvSbbk', fn($q) => $q->where('sinkarkes', 'Sudah')),
                            default => $query,
                        };
                    }),

                DateRangeFilter::make('tanggal_pengajuan')
                    ->label('Tanggal Pengajuan'),
            ], FiltersLayout::AboveContent)
            ->actions([
                // Lihat Progress Action - COMPLETE with all details and files
                Action::make('lihat_progress')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalSubmitAction(false)
                    ->infolist([
                        InfoSection::make('Timeline Progress ICV')
                            ->schema([
                                TextEntry::make('step1')
                                    ->label('1. Pengajuan Dokumen ICV')
                                    ->getStateUsing(fn($record) => '✅ ' . $record->tanggal_pengajuan->translatedFormat('d F Y')),

                                TextEntry::make('step2')
                                    ->label('2. Verifikasi Dokumen')
                                    ->getStateUsing(function ($record) {
                                        if ($record->icvCekDokumen && $record->icvCekDokumen->hasil_verif) {
                                            $status = match ($record->icvCekDokumen->hasil_verif) {
                                                '1' => '✅ Berkas Lengkap',
                                                '2' => '❌ Berkas Tidak Lengkap',
                                                default => '⏳ Menunggu Verifikasi'
                                            };
                                            $dateText = $record->icvCekDokumen->date_updated ?
                                                ' - ' . $record->icvCekDokumen->date_updated->translatedFormat('d F Y') : '';
                                            return $status . $dateText;
                                        }
                                        return '⏳ Menunggu Verifikasi';
                                    }),

                                TextEntry::make('catatan_verifikasi')
                                    ->label('Catatan Verifikasi')
                                    ->getStateUsing(function ($record) {
                                        if ($record->icvCekDokumen && $record->icvCekDokumen->catatan) {
                                            return $record->icvCekDokumen->catatan;
                                        }
                                        return 'Tidak ada catatan';
                                    })
                                    ->visible(fn($record) => $record->icvCekDokumen && $record->icvCekDokumen->hasil_verif && $record->icvCekDokumen->catatan),

                                TextEntry::make('step3')
                                    ->label('3. Alokasi ICV')
                                    ->getStateUsing(function ($record) {
                                        if ($record->icvAlokasi && $record->icvAlokasi->icv_disetujui > 0) {
                                            return '✅ Dialokasikan: ' . $record->icvAlokasi->icv_disetujui . ' unit';
                                        }
                                        return '⏳ Belum Dialokasikan';
                                    }),

                                TextEntry::make('step4')
                                    ->label('4. Kode Billing')
                                    ->getStateUsing(function ($record) {
                                        if ($record->icvBilling && $record->icvBilling->kode_billing) {
                                            return '✅ Diterbitkan: ' . $record->icvBilling->kode_billing;
                                        }
                                        return '⏳ Belum Diterbitkan';
                                    }),

                                TextEntry::make('nominal_pembayaran')
                                    ->label('Nominal Pembayaran')
                                    ->getStateUsing(function ($record) {
                                        if ($record->icvBilling && $record->icvBilling->nominal) {
                                            return 'Rp ' . number_format($record->icvBilling->nominal, 0, ',', '.');
                                        }
                                        return '-';
                                    })
                                    ->visible(fn($record) => $record->icvBilling && $record->icvBilling->kode_billing),

                                TextEntry::make('step5')
                                    ->label('5. Upload Bukti Bayar')
                                    ->getStateUsing(function ($record) {
                                        if ($record->icvBuktiBayar && $record->icvBuktiBayar->file_bukti_bayar) {
                                            return '✅ Bukti Bayar Diupload';
                                        }
                                        return '⏳ Belum Upload Bukti Bayar';
                                    }),

                                TextEntry::make('file_bukti_bayar_download')
                                    ->label('File Bukti Bayar')
                                    ->getStateUsing(function ($record) {
                                        if ($record->icvBuktiBayar && $record->icvBuktiBayar->file_bukti_bayar) {
                                            $filename = basename($record->icvBuktiBayar->file_bukti_bayar);
                                            $extension = strtolower(pathinfo($record->icvBuktiBayar->file_bukti_bayar, PATHINFO_EXTENSION));
                                            $fileType = match ($extension) {
                                                'pdf' => 'PDF Document',
                                                'jpg', 'jpeg' => 'JPEG Image',
                                                'png' => 'PNG Image',
                                                'gif' => 'GIF Image',
                                                default => 'Unknown File Type'
                                            };
                                            return "{$filename} ({$fileType})";
                                        }
                                        return 'Tidak ada file';
                                    })
                                    ->badge()
                                    ->url(fn($record) => $record->icvBuktiBayar?->file_bukti_bayar ?
                                        asset('storage/' . $record->icvBuktiBayar->file_bukti_bayar) : null)
                                    ->openUrlInNewTab()
                                    ->visible(fn($record) => $record->icvBuktiBayar && $record->icvBuktiBayar->file_bukti_bayar),

                                TextEntry::make('metode_distribusi')
                                    ->label('Metode Distribusi ICV')
                                    ->getStateUsing(function ($record) {
                                        if ($record->icvBuktiBayar && $record->icvBuktiBayar->distribusi_icv) {
                                            return $record->icvBuktiBayar->distribusi_icv;
                                        }
                                        return '-';
                                    })
                                    ->visible(fn($record) => $record->icvBuktiBayar && $record->icvBuktiBayar->file_bukti_bayar),

                                TextEntry::make('step6')
                                    ->label('6. Verifikasi Bukti Bayar')
                                    ->getStateUsing(function ($record) {
                                        if ($record->icvBuktiBayar && $record->icvBuktiBayar->distribusi_icv) {
                                            return '✅ Bukti Bayar Diverifikasi';
                                        }
                                        return '⏳ Menunggu Verifikasi';
                                    }),

                                TextEntry::make('step7')
                                    ->label('7. Jadwal Distribusi')
                                    ->getStateUsing(function ($record) {
                                        if ($record->icvJadwal && $record->icvJadwal->jadwal_pengambilan) {
                                            $metode = $record->icvBuktiBayar?->distribusi_icv === 'Dikirim' ? 'Pengiriman' : 'Pengambilan';
                                            return "✅ Jadwal {$metode}: " . $record->icvJadwal->jadwal_pengambilan->translatedFormat('d F Y');
                                        }
                                        return '⏳ Belum Dijadwalkan';
                                    }),

                                TextEntry::make('step8')
                                    ->label('8. SBBK (Surat Bukti Barang Keluar)')
                                    ->getStateUsing(function ($record) {
                                        if ($record->icvSbbk && $record->icvSbbk->file_sbbk) {
                                            return '✅ SBBK Uploaded';
                                        }
                                        return '⏳ Belum Upload SBBK';
                                    }),

                                TextEntry::make('file_sbbk_download')
                                    ->label('File SBBK')
                                    ->getStateUsing(function ($record) {
                                        if ($record->icvSbbk && $record->icvSbbk->file_sbbk) {
                                            $filename = basename($record->icvSbbk->file_sbbk);
                                            $extension = strtolower(pathinfo($record->icvSbbk->file_sbbk, PATHINFO_EXTENSION));
                                            $fileType = match ($extension) {
                                                'pdf' => 'PDF Document',
                                                'jpg', 'jpeg' => 'JPEG Image',
                                                'png' => 'PNG Image',
                                                'gif' => 'GIF Image',
                                                default => 'Unknown File Type'
                                            };
                                            return "{$filename} ({$fileType})";
                                        }
                                        return 'Tidak ada file';
                                    })
                                    ->badge()
                                    ->url(fn($record) => $record->icvSbbk?->file_sbbk ?
                                        asset('storage/' . $record->icvSbbk->file_sbbk) : null)
                                    ->openUrlInNewTab()
                                    ->visible(fn($record) => $record->icvSbbk && $record->icvSbbk->file_sbbk),

                                TextEntry::make('step9')
                                    ->label('9. Status Sinkarkes')
                                    ->getStateUsing(function ($record) {
                                        if ($record->icvSbbk && $record->icvSbbk->sinkarkes) {
                                            return $record->icvSbbk->sinkarkes === 'Sudah' ?
                                                '✅ Sinkarkes: Sudah' :
                                                '⏳ Sinkarkes: Belum';
                                        }
                                        return '⏳ Belum Update Sinkarkes';
                                    }),
                            ])
                            ->columns(1),
                    ])
                    ->modalWidth('5xl'),

                Action::make('lengkapi_berkas')
                    ->label('Lengkapi')
                    ->icon('heroicon-o-document-plus')
                    ->color('warning')
                    ->size('sm')
                    ->visible(function ($record) {
                        return !auth()->user()->hasRole('verifikator2') &&
                            $record->icvCekDokumen &&
                            $record->icvCekDokumen->hasil_verif == '2';
                    })
                    ->action(fn($record) => redirect()->to(IcvResource::getUrl('edit', ['record' => $record->id]))),

                Action::make('upload_bukti_bayar')
                    ->label('Upload')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->size('sm')
                    ->visible(function ($record) {
                        return !auth()->user()->hasRole('verifikator2') &&
                            $record->icvBilling &&
                            $record->icvBilling->kode_billing &&
                            (!$record->icvBuktiBayar || !$record->icvBuktiBayar->file_bukti_bayar);
                    })
                    ->form([
                        FileUpload::make('file_bukti_bayar')
                            ->label('File Bukti Bayar')
                            ->required()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                            ->maxSize(1024 * 3)
                            ->getUploadedFileNameForStorageUsing(function ($file) {
                                return 'Bukti Bayar ' . auth()->user()->name . " " . now()->format('dmyHis')  . '.' . $file->getClientOriginalExtension();
                            })
                            ->helperText('Format: JPG/PNG/PDF, Maksimal 3MB')
                            ->directory('icv/bukti-bayar'),

                        Select::make('distribusi_icv')
                            ->label('Metode Distribusi')
                            ->options(['Diambil' => 'Diambil', 'Dikirim' => 'Dikirim'])
                            ->required(),

                        Textarea::make('catatan')
                            ->label('Catatan (Opsional)')
                            ->rows(2),
                    ])
                    ->action(function (array $data, $record) {
                        $record->icvBuktiBayar()->updateOrCreate([
                            'id_icv' => $record->id,
                        ], [
                            'file_bukti_bayar' => $data['file_bukti_bayar'],
                            'catatan' => $data['catatan'] ?? '',
                            'distribusi_icv' => $data['distribusi_icv'],
                            'status' => 1,
                            'date_updated' => now(),
                        ]);

                        Notification::make()
                            ->title('Bukti Bayar Berhasil Diupload')
                            ->success()
                            ->send();
                    }),

                Action::make('lihat_jadwal')
                    ->label('Jadwal')
                    ->icon('heroicon-o-calendar-days')
                    ->color('success')
                    ->size('sm')
                    ->visible(function ($record) {
                        return $record->icvJadwal && $record->icvJadwal->jadwal_pengambilan;
                    })
                    ->infolist([
                        InfoSection::make('Detail Jadwal Distribusi')
                            ->schema([
                                TextEntry::make('metode_distribusi_jadwal')
                                    ->label('Metode Distribusi')
                                    ->getStateUsing(fn($record) => $record->icvBuktiBayar?->distribusi_icv ?? 'Diambil')
                                    ->badge(),

                                TextEntry::make('jadwal_distribusi_detail')
                                    ->label('Tanggal Jadwal')
                                    ->getStateUsing(fn($record) => $record->icvJadwal?->jadwal_pengambilan?->translatedFormat('l, d F Y') ?? '-')
                                    ->badge(),

                                TextEntry::make('jumlah_icv_jadwal')
                                    ->label('Jumlah ICV')
                                    ->getStateUsing(fn($record) => $record->icvAlokasi?->icv_disetujui ?? 0),
                            ])
                            ->columns(2),

                        InfoSection::make('Narahubung Kantor Pos')
                            ->schema([
                                TextEntry::make('narahubung_nama')
                                    ->label('Nama Narahubung')
                                    ->getStateUsing(fn() => 'Julie Visanti Hendrayani')
                                    ->icon('heroicon-o-user')
                                    ->iconColor('primary')
                                    ->weight('bold'),

                                TextEntry::make('narahubung_email')
                                    ->label('Email')
                                    ->getStateUsing(fn() => '991437690@posindonesia.co.id')
                                    ->icon('heroicon-o-envelope')
                                    ->iconColor('info')
                                    ->copyable()
                                    ->copyMessage('Email berhasil disalin!')
                                    ->url(fn() => 'mailto:991437690@posindonesia.co.id')
                                    ->openUrlInNewTab(),

                                TextEntry::make('narahubung_telepon')
                                    ->label('Nomor Telepon')
                                    ->getStateUsing(fn() => '+62 812 1494 441')
                                    ->icon('heroicon-o-phone')
                                    ->iconColor('success')
                                    ->copyable()
                                    ->copyMessage('Nomor telepon berhasil disalin!')
                                    ->url(fn() => 'tel:+6281214944441'),
                            ])
                            ->columns(2)
                            ->visible(function ($record) {
                                return $record->icvBuktiBayar?->distribusi_icv === 'Dikirim';
                            })
                            ->collapsible()
                            ->persistCollapsed(false),
                    ])
                    ->modalWidth('3xl'),

                Action::make('lihat_dokumen')
                    ->label('Dokumen')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->size('sm')
                    ->modalSubmitAction(false)
                    ->infolist([

                        TextEntry::make('file1_display')
                            ->label('1. Surat Permohonan')
                            ->getStateUsing(function ($record) {
                                if ($record->file1) {
                                    $filename = basename($record->file1);
                                    return "{$filename} (PDF)";
                                }
                                return 'Tidak ada file';
                            })
                            ->badge()
                            ->color(fn($record) => $record->file1 ? 'success' : 'gray')
                            ->url(fn($record) => $record->file1 ? asset('storage/' . $record->file1) : null)
                            ->openUrlInNewTab(),

                        TextEntry::make('file2_display')
                            ->label('2. Bukti Laporan Ke Dinas Kesehatan')
                            ->getStateUsing(function ($record) {
                                if ($record->file2) {
                                    $filename = basename($record->file2);
                                    return "{$filename} (PDF)";
                                }
                                return 'Tidak ada file';
                            })
                            ->badge()
                            ->color(fn($record) => $record->file2 ? 'success' : 'gray')
                            ->url(fn($record) => $record->file2 ? asset('storage/' . $record->file2) : null)
                            ->openUrlInNewTab(),

                        TextEntry::make('file3_display')
                            ->label('3. Rekap Pemakaian ICV')
                            ->getStateUsing(function ($record) {
                                if ($record->file3) {
                                    $filename = basename($record->file3);
                                    return "{$filename} (PDF)";
                                }
                                return 'Tidak ada file';
                            })
                            ->badge()
                            ->color(fn($record) => $record->file3 ? 'success' : 'gray')
                            ->url(fn($record) => $record->file3 ? asset('storage/' . $record->file3) : null)
                            ->openUrlInNewTab(),

                        TextEntry::make('file4_display')
                            ->label('4. Faktur Pembelian Vaksin')
                            ->getStateUsing(function ($record) {
                                if ($record->file4) {
                                    $filename = basename($record->file4);
                                    return "{$filename} (PDF)";
                                }
                                return 'Tidak ada file';
                            })
                            ->badge()
                            ->color(fn($record) => $record->file4 ? 'success' : 'gray')
                            ->url(fn($record) => $record->file4 ? asset('storage/' . $record->file4) : null)
                            ->openUrlInNewTab(),

                        TextEntry::make('file5_display')
                            ->label('5. Berita Acara Kerusakan ICV')
                            ->getStateUsing(function ($record) {
                                if ($record->file5) {
                                    $filename = basename($record->file5);
                                    return "{$filename} (PDF)";
                                }
                                return 'Tidak ada file';
                            })
                            ->badge()
                            ->color(fn($record) => $record->file5 ? 'success' : 'gray')
                            ->url(fn($record) => $record->file5 ? asset('storage/' . $record->file5) : null)
                            ->openUrlInNewTab(),

                        TextEntry::make('file6_display')
                            ->label('6. Daftar Nama Penerima ICV')
                            ->getStateUsing(function ($record) {
                                if ($record->file6) {
                                    $filename = basename($record->file6);
                                    return "{$filename} (PDF)";
                                }
                                return 'Tidak ada file';
                            })
                            ->badge()
                            ->color(fn($record) => $record->file6 ? 'success' : 'gray')
                            ->url(fn($record) => $record->file6 ? asset('storage/' . $record->file6) : null)
                            ->openUrlInNewTab(),

                        TextEntry::make('file7_display')
                            ->label('7. Screenshot Sisa Stok di Sinkarkes')
                            ->getStateUsing(function ($record) {
                                if ($record->file7) {
                                    $filename = basename($record->file7);
                                    return "{$filename} (PDF)";
                                }
                                return 'Tidak ada file';
                            })
                            ->badge()
                            ->color(fn($record) => $record->file7 ? 'success' : 'gray')
                            ->url(fn($record) => $record->file7 ? asset('storage/' . $record->file7) : null)
                            ->openUrlInNewTab(),


                    ])
                    ->modalWidth('4xl')
                    ->visible(function ($record) {
                        // Visible for users (their own records) and verifikator2 (all records)
                        return auth()->user()->hasRole('verifikator2') ||
                            (auth()->user()->hasRole('user') && $record->user_id === auth()->id());
                    }),
            ])
            ->headerActions([
                Action::make('pengajuan_baru')
                    ->label('Pengajuan Baru')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->visible(fn() => auth()->user()->hasRole('user'))
                    ->action(fn() => redirect()->to(IcvResource::getUrl('create'))),
            ])
            ->defaultSort('tanggal_pengajuan', 'desc')
            ->striped()
            ->paginated([25, 50, 100]);
    }
}
