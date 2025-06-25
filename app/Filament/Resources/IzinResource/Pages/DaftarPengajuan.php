<?php

namespace App\Filament\Resources\IzinResource\Pages;

use App\Filament\Resources\IzinResource;
use App\Models\Izin;
use App\FileLabels;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Grid;
use Filament\Actions\Action as PageAction;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\Grid as InfoGrid;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action as InfoAction;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class DaftarPengajuan extends Page implements HasTable, HasInfolists
{
    use InteractsWithTable, InteractsWithInfolists;
    use FileLabels; // Add this trait

    protected static string $resource = IzinResource::class;

    protected static string $view = 'filament.resources.izin-resource.pages.monitor-progress';

    protected static ?string $title = 'Daftar Pengajuan Izin';

    protected static ?string $navigationLabel = 'Daftar Pengajuan Izin';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 3;

    protected function getHeaderActions(): array
    {
        return [
            PageAction::make('unduh_draft_pks')
                ->label('Unduh Draft PKS')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->link()
                ->url(Storage::url('izin-documents/Draft Perjanjian Kerjasama (PKS).doc'))
                ->openUrlInNewTab(),
        ];
    }

    public function mountCanAuthorizeAccess(): void
    {
        parent::mountCanAuthorizeAccess();

        // Cek apakah user memiliki peran yang sesuai
        if (!auth()->user()->hasRole(['user'])) {
            // abort(403, 'Akses ditolak. Hanya user dan verifikator yang dapat melihat pengajuan ICV.');
            redirect()->to('/');
        }
    }



    public function table(Table $table): Table
    {
        return $table
            ->query(Izin::query()
                ->where('user_id', auth()->id())
                ->with([
                    'user',
                    'izinDokumen',
                    'izinVisitasi',
                    'izinPersetujuan',
                    'izinDokumenPks'
                ]))
            ->columns([
                TextColumn::make('tanggal_pengajuan')
                    ->label('Tanggal Pengajuan')
                    ->description(fn ($record) => $record->nomor_surat)
                    ->date('d F Y')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('kategori_izin')
                    ->label('Kategori Izin')
                    ->badge(),

                TextColumn::make('progress_status')
                    ->label('Status Progress')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        // Cek apakah sudah ada PKS
                        if ($record->izinDokumenPks && $record->izinDokumenPks->dokumen_pks_bkk) {
                            return 'Selesai';
                        }

                        // Cek apakah sudah disetujui
                        if ($record->izinPersetujuan && $record->izinPersetujuan->status == 1) {
                            return 'Disetujui - Menunggu PKS';
                        }

                        // Cek apakah sudah dijadwalkan visitasi (dari izinDokumenPks)
                        if ($record->izinDokumenPks && $record->izinDokumenPks->tanggal_visitasi) {
                            return 'Visitasi Dijadwalkan';
                        }

                        // Cek apakah dokumen sudah diverifikasi
                        if ($record->izinDokumen) {
                            switch ($record->izinDokumen->hasil) {
                                case 1:
                                    return 'Berkas Lengkap';
                                case 2:
                                    return 'Berkas Tidak Lengkap';
                                default:
                                    return 'Menunggu Verifikasi';
                            }
                        }

                        return 'Dokumen Diajukan';
                    })
                    ->color(function ($state) {
                        return match ($state) {
                            'Selesai' => 'success',
                            'Disetujui - Menunggu PKS' => 'info',
                            'Visitasi Dijadwalkan' => 'warning',
                            'Berkas Lengkap' => 'success',
                            'Berkas Tidak Lengkap' => 'danger',
                            'Menunggu Verifikasi' => 'gray',
                            'Dokumen Diajukan' => 'gray',
                            default => 'gray',
                        };
                    }),

                TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->getStateUsing(function ($record) {
                        $progress = 0;

                        // Step 1: Dokumen diajukan (20%)
                        $progress += 20;

                        // Step 2: Dokumen diverifikasi (20%)
                        if ($record->izinDokumen && $record->izinDokumen->hasil > 0) {
                            $progress += 20;
                        }

                        // Step 3: Berkas lengkap (20%)
                        if ($record->izinDokumen && $record->izinDokumen->hasil == 1) {
                            $progress += 20;
                        }

                        // Step 4: Visitasi dijadwalkan (20%)
                        if ($record->izinDokumenPks && $record->izinDokumenPks->tanggal_visitasi) {
                            $progress += 20;
                        }

                        // Step 5: Disetujui (10%)
                        if ($record->izinPersetujuan && $record->izinPersetujuan->status == 1) {
                            $progress += 10;
                        }

                        // Step 6: PKS selesai (10%)
                        if ($record->izinDokumenPks && $record->izinDokumenPks->dokumen_pks_bkk) {
                            $progress += 10;
                        }

                        return $progress . '%';
                    })
                    ->badge()
                    ->color(function ($state) {
                        $percentage = (int) str_replace('%', '', $state);
                        return match (true) {
                            $percentage == 100 => 'success',
                            $percentage >= 80 => 'info',
                            $percentage >= 60 => 'warning',
                            $percentage >= 40 => 'gray',
                            default => 'danger',
                        };
                    }),
            ])
            ->filters([
                SelectFilter::make('progress_status')
                    ->label('Filter Status')
                    ->options([
                        'menunggu_verifikasi' => 'Menunggu Verifikasi',
                        'berkas_lengkap' => 'Berkas Lengkap',
                        'berkas_tidak_lengkap' => 'Berkas Tidak Lengkap',
                        'visitasi_dijadwalkan' => 'Visitasi Dijadwalkan',
                        'disetujui' => 'Disetujui',
                        'selesai' => 'Selesai',
                    ])
                    ->query(function ($query, array $data) {
                        if (!$data['value']) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'menunggu_verifikasi' => $query->whereHas('izinDokumen', fn($q) => $q->where('hasil', 0)),
                            'berkas_lengkap' => $query->whereHas('izinDokumen', fn($q) => $q->where('hasil', 1)),
                            'berkas_tidak_lengkap' => $query->whereHas('izinDokumen', fn($q) => $q->where('hasil', 2)),
                            'visitasi_dijadwalkan' => $query->whereHas('izinVisitasi', fn($q) => $q->whereNotNull('tanggal_visitasi')),
                            'disetujui' => $query->whereHas('izinPersetujuan', fn($q) => $q->where('status', 1)),
                            'selesai' => $query->whereHas('izinDokumenPks', fn($q) => $q->whereNotNull('dokumen_pks_bkk')),
                            default => $query,
                        };
                    }),

                SelectFilter::make('kategori_izin')
                    ->label('Kategori Izin')
                    ->options([
                        'Izin Baru' => 'Izin Baru',
                        'Perpanjang Izin' => 'Perpanjang Izin',
                    ]),
                ], FiltersLayout::AboveContent)
            ->actions([
                // Lihat Progress Action - Now as a separate button
                Action::make('lihat_progress')
                    ->label('Lihat Progress')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalSubmitAction(false)
                    ->infolist([
                        InfoSection::make('Timeline Progress Izin')
                            ->schema([
                                TextEntry::make('step1')
                                    ->label('1. Pengajuan Dokumen')
                                    ->getStateUsing(fn($record) => '✅ ' . $record->tanggal_pengajuan->translatedFormat('d F Y')),

                                TextEntry::make('step2')
                                    ->label('2. Verifikasi Dokumen')
                                    ->getStateUsing(function ($record) {
                                        if ($record->izinDokumen && $record->izinDokumen->hasil > 0) {
                                            $status = match ($record->izinDokumen->hasil) {
                                                1 => '✅ Berkas Lengkap',
                                                2 => '❌ Berkas Tidak Lengkap',
                                                default => '⏳ Menunggu Verifikasi'
                                            };
                                            $dateText = $record->izinDokumen->date_updated ?
                                                ' - ' . $record->izinDokumen->date_updated->translatedFormat('d F Y') : '';
                                            return $status . $dateText;
                                        }
                                        return '⏳ Menunggu Verifikasi';
                                    }),

                                TextEntry::make('catatan_verifikasi')
                                    ->label('Catatan Verifikasi')
                                    ->getStateUsing(function ($record) {
                                        if ($record->izinDokumen && $record->izinDokumen->catatan) {
                                            return $record->izinDokumen->catatan;
                                        }
                                        return 'Tidak ada catatan';
                                    })
                                    ->visible(fn($record) => $record->izinDokumen && $record->izinDokumen->hasil > 0 && $record->izinDokumen->catatan),

                                TextEntry::make('step3')
                                    ->label('3. Penjadwalan Visitasi')
                                    ->getStateUsing(function ($record) {
                                        if ($record->izinDokumenPks && $record->izinDokumenPks->tanggal_visitasi) {
                                            return '✅ Dijadwalkan - ' . $record->izinDokumenPks->tanggal_visitasi->translatedFormat('d F Y');
                                        }
                                        return '⏳ Belum Dijadwalkan';
                                    }),

                                TextEntry::make('step4')
                                    ->label('4. Persetujuan')
                                    ->getStateUsing(function ($record) {
                                        if ($record->izinPersetujuan && $record->izinPersetujuan->status == 1) {
                                            $dateText = $record->izinPersetujuan->date_updated ?
                                                ' - ' . $record->izinPersetujuan->date_updated->translatedFormat('d F Y') : '';
                                            return '✅ Disetujui' . $dateText;
                                        }
                                        return '⏳ Belum Disetujui';
                                    }),

                                TextEntry::make('surat_persetujuan')
                                    ->label('Surat Persetujuan')
                                    ->getStateUsing(function ($record) {
                                        if ($record->izinPersetujuan && $record->izinPersetujuan->file_persetujuan) {
                                            return substr($record->izinPersetujuan->file_persetujuan, strrpos($record->izinPersetujuan->file_persetujuan, '/') + 1);
                                        }
                                        return 'File belum tersedia';
                                    })
                                    ->url(function ($record) {
                                        if ($record->izinPersetujuan && $record->izinPersetujuan->file_persetujuan) {
                                            return asset('storage/' . $record->izinPersetujuan->file_persetujuan);
                                        }
                                        return null;
                                    })
                                    ->badge()
                                    ->openUrlInNewTab()
                                    ->color(function ($record) {
                                        return ($record->izinPersetujuan && $record->izinPersetujuan->file_persetujuan) ? 'success' : 'gray';
                                    })
                                    ->visible(fn($record) => $record->izinPersetujuan && $record->izinPersetujuan->status == 1),

                                TextEntry::make('step5')
                                    ->label('5. Perjanjian Kerjasama')
                                    ->getStateUsing(function ($record) {
                                        if ($record->izinDokumenPks && $record->izinDokumenPks->dokumen_pks_bkk) {
                                            return '✅ PKS Selesai';
                                        }
                                        return '⏳ Belum Upload PKS';
                                    }),

                                TextEntry::make('dokumen_pks')
                                    ->label('Dokumen PKS')
                                    ->getStateUsing(function ($record) {
                                        if ($record->izinDokumenPks && $record->izinDokumenPks->dokumen_pks_bkk) {
                                            return substr($record->izinDokumenPks->dokumen_pks_bkk, strrpos($record->izinDokumenPks->dokumen_pks_bkk, '/') + 1);
                                        }
                                        return 'File belum tersedia';
                                    })
                                    ->url(function ($record) {
                                        if ($record->izinDokumenPks && $record->izinDokumenPks->dokumen_pks_bkk) {
                                            return asset('storage/' . $record->izinDokumenPks->dokumen_pks_bkk);
                                        }
                                        return null;
                                    })
                                    ->badge()
                                    ->openUrlInNewTab()
                                    ->color(function ($record) {
                                        return ($record->izinDokumenPks && $record->izinDokumenPks->dokumen_pks_bkk) ? 'success' : 'gray';
                                    })
                                    ->visible(fn($record) => $record->izinDokumenPks && $record->izinDokumenPks->dokumen_pks_bkk),
                            ])
                            ->columns(1),
                    ])
                    ->modalWidth('5xl'),

                Action::make('lengkapi_berkas')
                    ->label('Lengkapi Berkas')
                    ->icon('heroicon-o-document-plus')
                    ->color('warning')
                    ->visible(function ($record) {
                        // Tampilkan jika berkas tidak lengkap
                        return $record->izinDokumen && $record->izinDokumen->hasil == 2;
                    })
                    ->action(function ($record) {
                        return redirect()->to(IzinResource::getUrl('edit', ['record' => $record->id]));
                    }),

                Action::make('lihat_dokumen')
                    ->label('Dokumen')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->size('sm')
                    ->modalSubmitAction(false)
                    ->infolist([
                        InfoSection::make('Dokumen Pengajuan Izin')
                            ->description('Semua dokumen yang diupload saat pengajuan (format PDF)')
                            ->schema([
                                TextEntry::make('file1_display')
                                    ->label('1. ' . $this->getIzinLabel('file1'))
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
                                    ->label('2. ' . $this->getIzinLabel('file2'))
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
                                    ->label('3. ' . $this->getIzinLabel('file3'))
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
                                    ->label('4. ' . $this->getIzinLabel('file4'))
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
                                    ->label('5. ' . $this->getIzinLabel('file5'))
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
                                    ->label('6. ' . $this->getIzinLabel('file6'))
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
                                    ->label('7. ' . $this->getIzinLabel('file7'))
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

                                TextEntry::make('file8_display')
                                    ->label('8. ' . $this->getIzinLabel('file8'))
                                    ->getStateUsing(function ($record) {
                                        if ($record->file8) {
                                            $filename = basename($record->file8);
                                            return "{$filename} (PDF)";
                                        }
                                        return 'Tidak ada file';
                                    })
                                    ->badge()
                                    ->color(fn($record) => $record->file8 ? 'success' : 'gray')
                                    ->url(fn($record) => $record->file8 ? asset('storage/' . $record->file8) : null)
                                    ->openUrlInNewTab(),

                                TextEntry::make('file9_display')
                                    ->label('9. ' . $this->getIzinLabel('file9'))
                                    ->getStateUsing(function ($record) {
                                        if ($record->file9) {
                                            $filename = basename($record->file9);
                                            return "{$filename} (PDF)";
                                        }
                                        return 'Tidak ada file';
                                    })
                                    ->badge()
                                    ->color(fn($record) => $record->file9 ? 'success' : 'gray')
                                    ->url(fn($record) => $record->file9 ? asset('storage/' . $record->file9) : null)
                                    ->openUrlInNewTab(),

                                TextEntry::make('file10_display')
                                    ->label('10. ' . $this->getIzinLabel('file10'))
                                    ->getStateUsing(function ($record) {
                                        if ($record->file10) {
                                            $filename = basename($record->file10);
                                            return "{$filename} (PDF)";
                                        }
                                        return 'Tidak ada file';
                                    })
                                    ->badge()
                                    ->color(fn($record) => $record->file10 ? 'success' : 'gray')
                                    ->url(fn($record) => $record->file10 ? asset('storage/' . $record->file10) : null)
                                    ->openUrlInNewTab(),

                                TextEntry::make('file11_display')
                                    ->label('11. ' . $this->getIzinLabel('file11'))
                                    ->getStateUsing(function ($record) {
                                        if ($record->file11) {
                                            $filename = basename($record->file11);
                                            return "{$filename} (PDF)";
                                        }
                                        return 'Tidak ada file';
                                    })
                                    ->badge()
                                    ->color(fn($record) => $record->file11 ? 'success' : 'gray')
                                    ->url(fn($record) => $record->file11 ? asset('storage/' . $record->file11) : null)
                                    ->openUrlInNewTab(),

                                TextEntry::make('file12_display')
                                    ->label('12. ' . $this->getIzinLabel('file12'))
                                    ->getStateUsing(function ($record) {
                                        if ($record->file12) {
                                            $filename = basename($record->file12);
                                            return "{$filename} (PDF)";
                                        }
                                        return 'Tidak ada file';
                                    })
                                    ->badge()
                                    ->color(fn($record) => $record->file12 ? 'success' : 'gray')
                                    ->url(fn($record) => $record->file12 ? asset('storage/' . $record->file12) : null)
                                    ->openUrlInNewTab(),

                                TextEntry::make('file13_display')
                                    ->label('13. ' . $this->getIzinLabel('file13'))
                                    ->getStateUsing(function ($record) {
                                        if ($record->file13) {
                                            $filename = basename($record->file13);
                                            return "{$filename} (PDF)";
                                        }
                                        return 'Tidak ada file';
                                    })
                                    ->badge()
                                    ->color(fn($record) => $record->file13 ? 'success' : 'gray')
                                    ->url(fn($record) => $record->file13 ? asset('storage/' . $record->file13) : null)
                                    ->openUrlInNewTab(),

                                TextEntry::make('file14_display')
                                    ->label('14. ' . $this->getIzinLabel('file14'))
                                    ->getStateUsing(function ($record) {
                                        if ($record->file14) {
                                            $filename = basename($record->file14);
                                            return "{$filename} (PDF)";
                                        }
                                        return 'Tidak ada file';
                                    })
                                    ->badge()
                                    ->color(fn($record) => $record->file14 ? 'success' : 'gray')
                                    ->url(fn($record) => $record->file14 ? asset('storage/' . $record->file14) : null)
                                    ->openUrlInNewTab(),


                            ])
                            ->columns(1),
                    ])
                    ->modalWidth('4xl')
                    ->visible(function ($record) {
                        // Only visible for the user who owns the record
                        return $record->user_id === auth()->id();
                    }),
            ])
            ->headerActions([
                Action::make('buat_izin_baru')
                    ->label('Buat Izin Baru')
                    ->icon('heroicon-o-document-plus')
                    ->color('primary')
                    ->visible(function () {
                        // Tampilkan jika belum pernah mengajukan izin baru
                        return !Izin::where('user_id', auth()->id())
                            ->where('kategori_izin', 'Izin Baru')
                            ->exists();
                    })
                    ->action(function () {
                        return redirect()->to(IzinResource::getUrl('create') . '?type=baru');
                    }),

                Action::make('perpanjang_izin')
                    ->label('Perpanjang Izin')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(function () {
                        // Tampilkan jika sudah ada izin baru yang selesai (ada PKS)
                        $latestIzin = Izin::where('user_id', auth()->id())
                            ->where('kategori_izin', 'Izin Baru')
                            ->with('izinDokumenPks')
                            ->orderBy('tanggal_pengajuan', 'desc')
                            ->first();

                        return $latestIzin &&
                            $latestIzin->izinDokumenPks &&
                            $latestIzin->izinDokumenPks->dokumen_pks_bkk;
                    })
                    ->action(function () {
                        return redirect()->to(IzinResource::getUrl('create') . '?type=perpanjang');
                    }),


            ])
            ->emptyStateActions([
                Action::make('buat_pengajuan_pertama')
                    ->label('Buat Pengajuan Izin Pertama')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->action(function () {
                        return redirect()->to(IzinResource::getUrl('create') . '?type=baru');
                    }),
            ])
            ->emptyStateDescription('Anda belum memiliki pengajuan izin. Mulai dengan membuat pengajuan izin baru.')
            ->emptyStateHeading('Belum Ada Pengajuan')
            ->defaultSort('tanggal_pengajuan', 'desc');
    }
}
