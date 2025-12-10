<?php

namespace App\Filament\Pages;

use App\Models\SuratPersetujuanIcv;
use Carbon\Carbon;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class ApprovalSuratPersetujuanIcv extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static string $view = 'filament.pages.approval-surat-persetujuan-icv';

    protected static ?string $title = 'Approval Surat Persetujuan Pelaksanaan Vaksinasi Internasional';

    protected static ?string $navigationLabel = 'Approval Surat Persetujuan Vaksinasi Internasional';

    protected static ?string $navigationGroup = 'ICV';

    protected static ?int $navigationSort = 10;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole(['admin', 'verifikator1', 'verifikator2']);
    }

    public function mount(): void
    {
        if (! auth()->user()->hasRole(['admin', 'verifikator1', 'verifikator2'])) {
            redirect()->to('/');
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SuratPersetujuanIcv::query()
                    ->with(['user', 'approver'])
                    ->whereNotNull('file_persetujuan')
                    ->orderByRaw('CASE WHEN status_approval = 0 THEN 0 ELSE 1 END')
                    ->orderBy('tanggal_surat', 'desc')
            )
            ->columns([
                TextColumn::make('tanggal_surat')
                    ->label('Tanggal Surat')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),

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
                    }),

                TextColumn::make('nomor_surat')
                    ->label('Nomor Surat')
                    ->searchable()
                    ->sortable()
                    ->limit(20)
                    ->tooltip(fn ($state) => $state),

                TextColumn::make('masa_berlaku')
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

                TextColumn::make('status_approval')
                    ->label('Status')
                    ->getStateUsing(fn ($record) => $record->status_approval_label)
                    ->badge()
                    ->color(function ($record) {
                        if ($record->isRejected()) {
                            return 'danger';
                        }
                        if ($record->isApproved()) {
                            return 'success';
                        }

                        return 'warning';
                    }),

                TextColumn::make('approver.name')
                    ->label('Diproses Oleh')
                    ->placeholder('-'),

                TextColumn::make('approved_at')
                    ->label('Tanggal Approval')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status_approval')
                    ->label('Status Approval')
                    ->options([
                        '0' => 'Menunggu Approval',
                        '1' => 'Disetujui',
                        '2' => 'Ditolak',
                    ])
                    ->default('0'),

                SelectFilter::make('id_akun')
                    ->label('Klinik/RS')
                    ->searchable()
                    ->relationship('user', 'name')
                    ->preload(),

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
                    ->preload(),

                DateRangeFilter::make('tanggal_surat')
                    ->label('Tanggal Surat'),
            ], FiltersLayout::AboveContent)
            ->actions([
                Action::make('lihat_file')
                    ->label('Lihat File')
                    ->icon('heroicon-o-document')
                    ->color('info')
                    ->url(fn ($record) => asset('storage/'.$record->file_persetujuan))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->file_persetujuan),

                Action::make('proses_approval')
                    ->label('Proses')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->modalHeading(fn ($record) => 'Approval Surat Persetujuan - '.$record->user?->name)
                    ->modalWidth('2xl')
                    ->form(function ($record) {
                        return [
                            Section::make('Informasi Surat')
                                ->schema([
                                    Grid::make(2)->schema([
                                        Placeholder::make('klinik')
                                            ->label('Klinik/RS')
                                            ->content($record->user?->name ?? '-'),

                                        Placeholder::make('lokasi')
                                            ->label('Lokasi')
                                            ->content($record->user?->kotakab ?? '-'),

                                        Placeholder::make('nomor_surat_info')
                                            ->label('Nomor Surat')
                                            ->content($record->nomor_surat ?? '-'),

                                        Placeholder::make('tanggal_surat_info')
                                            ->label('Tanggal Surat')
                                            ->content($record->tanggal_surat?->translatedFormat('d F Y') ?? '-'),

                                        Placeholder::make('masa_berlaku_info')
                                            ->label('Masa Berlaku')
                                            ->content($record->masa_berlaku?->translatedFormat('d F Y') ?? '-'),

                                        Placeholder::make('file_info')
                                            ->label('File')
                                            ->content(fn () => $record->file_persetujuan ? 'Sudah diupload' : 'Belum diupload'),
                                    ]),
                                ])
                                ->collapsible(),

                            Section::make('Keputusan Approval')
                                ->schema([
                                    Textarea::make('catatan_approval')
                                        ->label('Catatan')
                                        ->rows(3)
                                        ->helperText('Catatan akan disimpan sebagai keterangan approval/penolakan'),
                                ]),
                        ];
                    })
                    ->modalSubmitAction(false)
                    ->extraModalFooterActions(function ($record) {
                        return [
                            Action::make('setujui')
                                ->label('Setujui')
                                ->color('success')
                                ->icon('heroicon-o-check-circle')
                                ->requiresConfirmation()
                                ->modalHeading('Konfirmasi Persetujuan')
                                ->modalDescription('Apakah Anda yakin ingin menyetujui surat persetujuan ini?')
                                ->action(function (array $data) use ($record) {
                                    $record->update([
                                        'status_approval' => SuratPersetujuanIcv::STATUS_APPROVED,
                                        'catatan_approval' => $data['catatan_approval'] ?? null,
                                        'approved_by' => Auth::id(),
                                        'approved_at' => now(),
                                    ]);

                                    Notification::make()
                                        ->title('Berhasil Disetujui')
                                        ->success()
                                        ->send();

                                    $this->redirect(ApprovalSuratPersetujuanIcv::getUrl());
                                }),

                            Action::make('tolak')
                                ->label('Tolak')
                                ->color('danger')
                                ->icon('heroicon-o-x-circle')
                                ->requiresConfirmation()
                                ->modalHeading('Konfirmasi Penolakan')
                                ->modalDescription('Apakah Anda yakin ingin menolak surat persetujuan ini?')
                                ->action(function (array $data) use ($record) {
                                    if (empty($data['catatan_approval'])) {
                                        Notification::make()
                                            ->title('Catatan Diperlukan')
                                            ->body('Silakan isi catatan alasan penolakan.')
                                            ->danger()
                                            ->send();

                                        return;
                                    }

                                    $record->update([
                                        'status_approval' => SuratPersetujuanIcv::STATUS_REJECTED,
                                        'catatan_approval' => $data['catatan_approval'],
                                        'approved_by' => Auth::id(),
                                        'approved_at' => now(),
                                    ]);

                                    Notification::make()
                                        ->title('Surat Persetujuan Ditolak')
                                        ->warning()
                                        ->send();

                                    $this->redirect(ApprovalSuratPersetujuanIcv::getUrl());
                                }),
                        ];
                    })
                    ->visible(fn ($record) => $record->isPending()),

                Action::make('lihat_detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn ($record) => 'Detail Approval - '.$record->user?->name)
                    ->modalWidth('xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->form(function ($record) {
                        return [
                            Section::make('Informasi Surat')
                                ->schema([
                                    Grid::make(2)->schema([
                                        Placeholder::make('klinik')
                                            ->label('Klinik/RS')
                                            ->content($record->user?->name ?? '-'),

                                        Placeholder::make('nomor_surat_info')
                                            ->label('Nomor Surat')
                                            ->content($record->nomor_surat ?? '-'),

                                        Placeholder::make('tanggal_surat_info')
                                            ->label('Tanggal Surat')
                                            ->content($record->tanggal_surat?->translatedFormat('d F Y') ?? '-'),

                                        Placeholder::make('masa_berlaku_info')
                                            ->label('Masa Berlaku')
                                            ->content($record->masa_berlaku?->translatedFormat('d F Y') ?? '-'),
                                    ]),
                                ]),

                            Section::make('Status Approval')
                                ->schema([
                                    Grid::make(2)->schema([
                                        Placeholder::make('status')
                                            ->label('Status')
                                            ->content($record->status_approval_label),

                                        Placeholder::make('diproses_oleh')
                                            ->label('Diproses Oleh')
                                            ->content($record->approver?->name ?? '-'),

                                        Placeholder::make('tanggal_approval')
                                            ->label('Tanggal Approval')
                                            ->content($record->approved_at?->translatedFormat('d F Y H:i') ?? '-'),

                                        Placeholder::make('catatan')
                                            ->label('Catatan')
                                            ->content($record->catatan_approval ?? '-'),
                                    ]),
                                ]),
                        ];
                    })
                    ->visible(fn ($record) => ! $record->isPending()),

                Action::make('ubah_status')
                    ->label('Ubah Status')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->modalHeading(fn ($record) => 'Ubah Status Approval - '.$record->user?->name)
                    ->modalWidth('lg')
                    ->form(function ($record) {
                        return [
                            \Filament\Forms\Components\Select::make('status_approval')
                                ->label('Status Approval')
                                ->options([
                                    SuratPersetujuanIcv::STATUS_PENDING => 'Menunggu Approval',
                                    SuratPersetujuanIcv::STATUS_APPROVED => 'Disetujui',
                                    SuratPersetujuanIcv::STATUS_REJECTED => 'Ditolak',
                                ])
                                ->default($record->status_approval)
                                ->required(),

                            Textarea::make('catatan_approval')
                                ->label('Catatan')
                                ->default($record->catatan_approval)
                                ->rows(3),
                        ];
                    })
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status_approval' => $data['status_approval'],
                            'catatan_approval' => $data['catatan_approval'],
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Status Approval Berhasil Diubah')
                            ->success()
                            ->send();

                        $this->redirect(ApprovalSuratPersetujuanIcv::getUrl());
                    })
                    ->visible(fn ($record) => Auth::user()->hasRole('admin') && ! $record->isPending()),
            ])
            ->striped()
            ->paginated([15, 30, 50])
            ->defaultPaginationPageOption(15);
    }
}
