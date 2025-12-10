<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SuratPersetujuanIcvResource\Pages;
use App\Models\SuratPersetujuanIcv;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class SuratPersetujuanIcvResource extends Resource
{
    protected static ?string $model = SuratPersetujuanIcv::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Surat Persetujuan Pelaksanaan Vaksinasi Internasional';

    protected static ?string $modelLabel = 'Surat Persetujuan Pelaksanaan Vaksinasi Internasional';

    protected static ?string $pluralModelLabel = 'Surat Persetujuan Pelaksanaan Vaksinasi Internasional';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 6;

    public static function canAccess(): bool
    {
        return Auth::check() && (Auth::user()->hasRole('admin') || Auth::user()->hasRole('user') || Auth::user()->hasRole('verifikator1') || Auth::user()->hasRole('verifikator2'));
    }

    public static function canCreate(): bool
    {
        // Hanya admin yang bisa create, user mendapat surat dari flow pengajuan izin
        return Auth::check() && Auth::user()->hasRole('admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Surat')
                    ->schema([
                        Forms\Components\Select::make('id_akun')
                            ->label('User/Akun')
                            ->searchable()
                            ->options(function () {
                                $user = Auth::user();

                                if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
                                    return User::pluck('name', 'id');
                                }

                                return [$user->id => $user->name];
                            })
                            ->default(function () {
                                $user = Auth::user();

                                if (! $user->hasRole('admin') && ! $user->hasRole('super_admin')) {
                                    return $user->id;
                                }

                                return null;
                            })
                            ->disabled(function () {
                                $user = Auth::user();

                                return ! $user->hasRole('admin') && ! $user->hasRole('super_admin');
                            })
                            ->dehydrated(true)
                            ->required(),

                        Forms\Components\TextInput::make('nomor_surat')
                            ->label('Nomor Surat')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('tanggal_surat')
                            ->label('Tanggal Surat')
                            ->required(),

                        Forms\Components\DatePicker::make('masa_berlaku')
                            ->label('Masa Berlaku')
                            ->required(),

                        Forms\Components\FileUpload::make('file_persetujuan')
                            ->label('Upload Surat Persetujuan')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(5 * 1024)
                            ->directory('surat-persetujuan-icv')
                            ->getUploadedFileNameForStorageUsing(function ($file, $get) {
                                $extension = $file->getClientOriginalExtension();
                                $idAkun = $get('id_akun');
                                $klinikName = $idAkun ? (User::find($idAkun)?->name ?? 'Unknown') : 'Unknown';

                                return 'Surat Persetujuan Vaksinasi Internasional - '.$klinikName.' ['.date('dmyHis').'].'.$extension;
                            })
                            ->openable()
                            ->downloadable()
                            ->helperText('Upload file surat persetujuan dalam format PDF (Maksimal 5MB)'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status Approval')
                    ->schema([
                        Forms\Components\Placeholder::make('status_approval_info')
                            ->label('Status')
                            ->content(fn ($record) => $record?->status_approval_label ?? 'Menunggu Approval'),

                        Forms\Components\Placeholder::make('approved_by_info')
                            ->label('Disetujui/Ditolak Oleh')
                            ->content(fn ($record) => $record?->approver?->name ?? '-'),

                        Forms\Components\Placeholder::make('approved_at_info')
                            ->label('Tanggal Approval')
                            ->content(fn ($record) => $record?->approved_at?->translatedFormat('d F Y H:i') ?? '-'),

                        Forms\Components\Placeholder::make('catatan_info')
                            ->label('Catatan')
                            ->content(fn ($record) => $record?->catatan_approval ?? '-'),
                    ])
                    ->visible(fn ($record) => $record !== null)
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                function () {
                    $user = Auth::user();
                    $query = SuratPersetujuanIcv::query()->with(['user', 'approver']);

                    if (! ($user->hasRole('admin') || $user->hasRole('verifikator1') || $user->hasRole('verifikator2'))) {
                        $query->where('id_akun', $user->id);
                    }

                    // Hide surat yang kadaluarsa jika klinik memiliki surat lain yang masih berlaku
                    $query->where(function ($q) {
                        $q->where('masa_berlaku', '>=', now())
                            ->orWhereNotExists(function ($subQuery) {
                                $subQuery->selectRaw(1)
                                    ->from('tbl_surat_persetujuan_icv as other')
                                    ->whereColumn('other.id_akun', 'tbl_surat_persetujuan_icv.id_akun')
                                    ->where('other.masa_berlaku', '>=', now())
                                    ->whereColumn('other.id', '!=', 'tbl_surat_persetujuan_icv.id');
                            });
                    });

                    return $query;
                }
            )
            ->defaultSort('tanggal_surat', 'desc')
            ->searchable()
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_surat')
                    ->label('Tanggal Surat')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('klinik_info')
                    ->label('Klinik/RS & Lokasi')
                    ->getStateUsing(function ($record) {
                        $klinik = $record->user?->name ?? 'N/A';
                        $lokasi = $record->user?->kotakab ?? 'N/A';
                        $klinikTruncated = mb_strlen($klinik) > 25 ? mb_substr($klinik, 0, 25).'...' : $klinik;

                        return '<div title="'.e($klinik).'">'.
                            '<div class="font-medium text-sm">'.e($klinikTruncated).'</div>'.
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
                        return $query->join('users', 'tbl_surat_persetujuan_icv.id_akun', '=', 'users.id')
                            ->orderBy('users.name', $direction);
                    }),

                Tables\Columns\TextColumn::make('nomor_surat')
                    ->label('Nomor Surat')
                    ->searchable()
                    ->sortable()
                    ->limit(20)
                    ->tooltip(fn ($state) => $state),

                Tables\Columns\TextColumn::make('masa_berlaku')
                    ->label('Masa Berlaku')
                    ->date('d M Y')
                    ->sortable()
                    ->color(function ($state) {
                        $masaBerlaku = Carbon::parse($state);
                        $now = now();

                        if ($masaBerlaku < $now) {
                            return 'danger';
                        } elseif ($masaBerlaku->diffInDays($now) <= 30) {
                            return 'warning';
                        }

                        return 'success';
                    }),

                Tables\Columns\TextColumn::make('status_approval')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        // Cek kedaluarsa
                        if ($record->masa_berlaku && $record->masa_berlaku < now()) {
                            return 'Kedaluarsa';
                        }

                        if (! $record->file_persetujuan) {
                            return 'Belum Upload';
                        }

                        return $record->status_approval_label;
                    })
                    ->badge()
                    ->color(function ($record) {
                        // Cek kedaluarsa
                        if ($record->masa_berlaku && $record->masa_berlaku < now()) {
                            return 'danger';
                        }

                        if (! $record->file_persetujuan) {
                            return 'gray';
                        }
                        if ($record->isRejected()) {
                            return 'danger';
                        }
                        if ($record->isApproved()) {
                            return 'success';
                        }

                        return 'warning';
                    }),

                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Disetujui Oleh')
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                Tables\Columns\TextColumn::make('detail_info')
                    ->label('Detail')
                    ->getStateUsing(function ($record) {
                        $createdAt = Carbon::parse($record->created_at);
                        $updatedAt = Carbon::parse($record->updated_at);

                        $details = [];
                        $details[] = 'Dibuat: '.$createdAt->translatedFormat('d M Y');

                        if ($createdAt->ne($updatedAt)) {
                            $details[] = 'Diperbarui: '.$updatedAt->translatedFormat('d M Y');
                        }

                        return implode("\n", $details);
                    })
                    ->html()
                    ->formatStateUsing(function ($state) {
                        $lines = explode("\n", $state);
                        $formatted = array_map(function ($line) {
                            return '<div class="text-xs">'.$line.'</div>';
                        }, $lines);

                        return '<div class="space-y-1">'.implode('', $formatted).'</div>';
                    })
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                DateRangeFilter::make('tanggal_surat')
                    ->label('Tanggal Surat'),

                DateRangeFilter::make('masa_berlaku')
                    ->label('Masa Berlaku'),

                SelectFilter::make('id_akun')
                    ->label('Klinik/RS')
                    ->searchable()
                    ->relationship('user', 'name')
                    ->preload()
                    ->visible(fn () => Auth::user()->hasRole('admin') || Auth::user()->hasRole('verifikator1') || Auth::user()->hasRole('verifikator2')),

                SelectFilter::make('kotakab')
                    ->label('Kota/Kabupaten')
                    ->searchable()
                    ->options(function () {
                        return SuratPersetujuanIcv::join('users', 'tbl_surat_persetujuan_icv.id_akun', '=', 'users.id')
                            ->distinct()
                            ->whereNotNull('users.kotakab')
                            ->orderBy('users.kotakab')
                            ->pluck('users.kotakab', 'users.kotakab');
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        return $query->whereHas('user', function (Builder $query) use ($data) {
                            $query->where('kotakab', $data['value']);
                        });
                    })
                    ->preload()
                    ->visible(fn () => Auth::user()->hasRole('admin') || Auth::user()->hasRole('verifikator1') || Auth::user()->hasRole('verifikator2')),

                SelectFilter::make('status_berlaku')
                    ->label('Status Berlaku')
                    ->options([
                        'berlaku' => 'Berlaku',
                        'akan_kadaluarsa' => 'Akan Kadaluarsa (≤30 hari)',
                        'kadaluarsa' => 'Kadaluarsa',
                        'kadaluarsa_recent' => 'Baru Kadaluarsa (≤30 hari)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        $now = now();

                        return match ($data['value']) {
                            'berlaku' => $query->where('masa_berlaku', '>', $now->copy()->addDays(30)),
                            'akan_kadaluarsa' => $query->whereBetween('masa_berlaku', [$now, $now->copy()->addDays(30)]),
                            'kadaluarsa' => $query->where('masa_berlaku', '<', $now),
                            'kadaluarsa_recent' => $query->whereBetween('masa_berlaku', [$now->copy()->subDays(30), $now]),
                            default => $query,
                        };
                    }),

                SelectFilter::make('tahun')
                    ->label('Tahun Surat')
                    ->options(function () {
                        $years = [];
                        $currentYear = now()->year;
                        for ($i = $currentYear; $i >= $currentYear - 5; $i--) {
                            $years[$i] = $i;
                        }

                        return $years;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        return $query->whereYear('tanggal_surat', $data['value']);
                    }),

                SelectFilter::make('status_approval')
                    ->label('Status Approval')
                    ->options([
                        'pending' => 'Menunggu Approval',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'pending' => $query->where('status_approval', SuratPersetujuanIcv::STATUS_PENDING),
                            'approved' => $query->where('status_approval', SuratPersetujuanIcv::STATUS_APPROVED),
                            'rejected' => $query->where('status_approval', SuratPersetujuanIcv::STATUS_REJECTED),
                            default => $query,
                        };
                    })
                    ->visible(fn () => Auth::user()->hasRole('admin') || Auth::user()->hasRole('verifikator1') || Auth::user()->hasRole('verifikator2')),
            ], FiltersLayout::AboveContent)
            ->persistFiltersInSession()
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->size('sm'),

                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->size('sm')
                    ->visible(function ($record) {
                        $user = Auth::user();

                        // Admin selalu bisa edit
                        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
                            return true;
                        }

                        // User biasa tidak bisa edit jika sudah disetujui atau ditolak
                        if ((int) $record->status_approval === 1 || (int) $record->status_approval === 2) {
                            return false;
                        }

                        return $record->id_akun == $user->id;
                    }),

                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->size('sm')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Surat Persetujuan')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui surat persetujuan ini?')
                    ->form([
                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan (Opsional)')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $user = Auth::user();

                        $record->update([
                            'status_approval' => SuratPersetujuanIcv::STATUS_APPROVED,
                            'catatan_approval' => $data['catatan'] ?? null,
                            'approved_by' => $user->id,
                            'approved_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Berhasil Disetujui')
                            ->success()
                            ->send();
                    })
                    ->visible(function ($record) {
                        $user = Auth::user();

                        if (! $record->file_persetujuan) {
                            return false;
                        }

                        return ($user->hasRole('admin') || $user->hasRole('verifikator1') || $user->hasRole('verifikator2')) && $record->isPending();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->size('sm')
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Surat Persetujuan')
                    ->modalDescription('Apakah Anda yakin ingin menolak surat persetujuan ini?')
                    ->form([
                        Forms\Components\Textarea::make('catatan')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $user = Auth::user();

                        $record->update([
                            'status_approval' => SuratPersetujuanIcv::STATUS_REJECTED,
                            'catatan_approval' => $data['catatan'],
                            'approved_by' => $user->id,
                            'approved_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Surat Persetujuan Ditolak')
                            ->warning()
                            ->send();
                    })
                    ->visible(function ($record) {
                        $user = Auth::user();

                        if (! $record->file_persetujuan) {
                            return false;
                        }

                        return ($user->hasRole('admin') || $user->hasRole('verifikator1') || $user->hasRole('verifikator2')) && $record->isPending();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->size('sm')
                    ->visible(function ($record) {
                        $user = Auth::user();

                        return $user->hasRole('admin') || $user->hasRole('super_admin') || $record->id_akun == $user->id;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->visible(fn () => Auth::user()->hasRole('admin') || Auth::user()->hasRole('super_admin')),

                    Tables\Actions\BulkAction::make('export_selected')
                        ->label('Export Data Terpilih')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->action(function ($records) {
                            \Filament\Notifications\Notification::make()
                                ->title('Export Berhasil')
                                ->body('Data yang dipilih berhasil diekspor.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn () => Auth::user()->hasRole('admin') || Auth::user()->hasRole('super_admin')),
                ]),
            ])
            ->emptyStateHeading('Belum Ada Surat Persetujuan ICV')
            ->emptyStateDescription('Belum ada data surat persetujuan ICV yang masuk ke sistem.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->striped()
            ->paginated([15, 30, 50])
            ->recordUrl(null);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        if ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('verifikator1') || $user->hasRole('verifikator2')) {
            return parent::getEloquentQuery();
        }

        return parent::getEloquentQuery()->where('id_akun', $user->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuratPersetujuanIcvs::route('/'),
            'create' => Pages\CreateSuratPersetujuanIcv::route('/create'),
            'edit' => Pages\EditSuratPersetujuanIcv::route('/{record}/edit'),
        ];
    }
}
