<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanPenggunaanIcvResource\Pages;
use App\Models\LaporanPenggunaanIcv;
use App\Models\Vaksin;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class LaporanPenggunaanIcvResource extends Resource
{
    protected static ?string $model = LaporanPenggunaanIcv::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Laporan Harian Penggunaan ICV';

    protected static ?string $pluralLabel = 'Laporan Harian Penggunaan ICV';

    protected static ?string $slug = 'laporan-penggunaan-icv';

    public static function canAccess(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('verifikator2') || auth()->user()->hasRole('user'));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('tgl_pelaporan')
                    ->label('Tanggal Pelaporan')
                    ->native(false)

                    ->required(),

                Forms\Components\Select::make('id_user')
                    ->label('User')
                    ->searchable()
                    ->options(function () {
                        $user = Auth::user();

                        // Jika admin atau verifikator2, tampilkan semua user
                        if ($user->hasRole('admin') || $user->hasRole('verifikator2')) {
                            return \App\Models\User::pluck('name', 'id');
                        }

                        // Jika user biasa, hanya tampilkan dirinya sendiri
                        return [$user->id => $user->name];
                    })
                    ->default(function () {
                        $user = Auth::user();

                        // Jika bukan admin/verifikator2, set default ke user yang login
                        if (! $user->hasRole('admin') && ! $user->hasRole('verifikator2')) {
                            return $user->id;
                        }

                        return null;
                    })
                    ->disabled(function () {
                        $user = Auth::user();

                        // Disable jika bukan admin/verifikator2
                        return ! $user->hasRole('admin') && ! $user->hasRole('verifikator2');
                    })
                    ->dehydrated(true) // Pastikan field ini tetap dikirim meski disabled
                    ->required(),

                Forms\Components\Select::make('jenis_vaksin')
                    ->label('Jenis Vaksin')
                    ->multiple()
                    ->options(\App\Models\Vaksin::pluck('jenis_vaksin', 'id'))
                    ->required()
                    ->live(),

                Forms\Components\Fieldset::make('Data Vaksin')
                    ->label('')
                    ->schema(function (callable $get) {
                        $selected = $get('jenis_vaksin') ?? [];
                        $fields = [];
                        foreach ($selected as $vaksinId) {
                            $vaksin = \App\Models\Vaksin::find($vaksinId);
                            $label = $vaksin ? $vaksin->jenis_vaksin : 'Vaksin';
                            $fields[] = Forms\Components\Fieldset::make($label)
                                ->schema([
                                    Forms\Components\TextInput::make("data.{$vaksinId}.jml_terbit_icv")
                                        ->label('Jumlah Penerbitan ICV hari ini')
                                        ->live()
                                        ->numeric()
                                        ->required(),
                                    Forms\Components\TextInput::make("data.{$vaksinId}.sisa_icv")
                                        ->label('Sisa ICV hari ini')
                                        ->numeric()
                                        ->required(),
                                    Forms\Components\TextInput::make("data.{$vaksinId}.jml_guna_vaksin")
                                        ->label('Jumlah Penggunaan Vaksin')
                                        ->live()
                                        ->numeric()
                                        ->required(),
                                    Forms\Components\TextInput::make("data.{$vaksinId}.sisa_vaksin")
                                        ->label('Sisa Vaksin hari ini')
                                        ->numeric()
                                        ->required(),
                                    Forms\Components\Textarea::make("data.{$vaksinId}.keterangan")
                                        ->label('Catatan')
                                        ->live()
                                        ->visible(
                                            fn ($get) => $get("data.{$vaksinId}.jml_terbit_icv") !== $get("data.{$vaksinId}.jml_guna_vaksin")
                                        )
                                        ->placeholder('Isi jika ada perbedaan antara jumlah terbit dan jumlah guna'),
                                ]);
                        }

                        return $fields;
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (\Illuminate\Database\Eloquent\Builder $query) {
                if (! Auth::user()?->hasRole('admin') && ! Auth::user()?->hasRole('verifikator2')) {
                    $query->where('id_user', Auth::id())->with(['user', 'jenisVaksin']);
                }

                return $query->orderBy('tgl_pelaporan', 'desc')->with(['user', 'jenisVaksin']);
            })
            ->headerActions([
                Tables\Actions\Action::make('downloadLaporan')
                    ->label('Download Laporan')
                    ->visible(fn () => Auth::user()?->hasRole(['admin', 'verifikator2']))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->form([
                        Forms\Components\DatePicker::make('tanggal_mulai')
                            ->label('Tanggal Mulai')
                            ->native(false)

                            ->required(),
                        Forms\Components\DatePicker::make('tanggal_selesai')
                            ->label('Tanggal Selesai')
                            ->native(false)

                            ->required(),
                        Forms\Components\Select::make('user_id')
                            ->label('Filter User')
                            ->searchable()
                            ->options(function () {
                                $userIds = LaporanPenggunaanIcv::distinct()->pluck('id_user');
                                $options = \App\Models\User::whereIn('id', $userIds)
                                    ->pluck('name', 'id'); // key adalah id, value adalah name

                                // Tambahkan opsi "Semua User" di awal
                                return ['semua' => 'Semua User'] + $options->toArray();
                            })
                            ->default(function () {
                                // Jika admin, default ke 'semua', jika user biasa default ke ID mereka
                                return Auth::user()?->hasRole('admin') || Auth::user()?->hasRole('verifikator2') ? 'semua' : Auth::id();
                            })
                            ->required(),
                        Forms\Components\Select::make('jenis_vaksin')
                            ->label('Jenis Vaksin')
                            ->options(\App\Models\Vaksin::pluck('jenis_vaksin', 'id')->prepend('Semua', 'semua'))
                            ->default('semua'),
                        Forms\Components\Select::make('format')
                            ->label('Format File')
                            ->options([
                                'excel' => 'Excel (.xlsx)',
                                'csv' => 'CSV (.csv)',
                            ])
                            ->default('excel')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        return static::downloadLaporanIcv($data);
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('tgl_pelaporan')
                    ->label('Tanggal Pelaporan')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Klinik/RS')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('jenisVaksin.jenis_vaksin')
                    ->label('Jenis Vaksin')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('jml_terbit_icv')
                    ->label('Terbit ICV')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('jml_guna_vaksin')
                    ->label('Guna Vaksin')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sisa_icv')
                    ->label('Sisa ICV')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sisa_vaksin')
                    ->label('Sisa Vaksin')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Catatan')
                    ->searchable(),
            ])
            ->filters([
                // Date Range Filter
                DateRangeFilter::make('tgl_pelaporan')
                    ->label('Tanggal Pelaporan'),

                // Klinik/RS Filter - Fixed
                SelectFilter::make('id_user')
                    ->label('Klinik/RS')
                    ->visible(function () {
                        return Auth::user()?->hasRole('admin') || Auth::user()?->hasRole('verifikator2');
                    })
                    ->searchable()
                    ->options(function () {
                        $userIds = LaporanPenggunaanIcv::distinct()->pluck('id_user');

                        return \App\Models\User::whereIn('id', $userIds)
                            ->pluck('name', 'id');
                    }),

                // Jenis Vaksin Filter
                SelectFilter::make('id_jenis_vaksin')
                    ->label('Jenis Vaksin')
                    ->searchable()
                    ->options(\App\Models\Vaksin::pluck('jenis_vaksin', 'id')),
            ], layout: FiltersLayout::AboveContent)
            ->defaultSort('tgl_pelaporan', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporanPenggunaanIcvs::route('/'),
            'create' => Pages\CreateLaporanPenggunaanIcv::route('/create'),
            'edit' => Pages\EditLaporanPenggunaanIcv::route('/{record}/edit'),
        ];
    }

    public static function downloadLaporanIcv(array $data)
    {
        $query = LaporanPenggunaanIcv::with(['user', 'jenisVaksin'])
            ->whereBetween('tgl_pelaporan', [$data['tanggal_mulai'], $data['tanggal_selesai']]);

        // Filter berdasarkan jenis vaksin
        if ($data['jenis_vaksin'] !== 'semua') {
            $query->where('id_jenis_vaksin', $data['jenis_vaksin']);
        }

        // Filter berdasarkan user
        if ($data['user_id'] !== 'semua') {
            $query->where('id_user', $data['user_id']); // Gunakan user_id dari form
        } elseif (! Auth::user()?->hasRole('admin') && ! Auth::user()?->hasRole('verifikator2')) {
            // Jika bukan admin/verifikator2, hanya tampilkan data mereka sendiri
            $query->where('id_user', Auth::id());
        }

        $laporan = $query->orderBy('tgl_pelaporan', 'desc')->get();

        switch ($data['format']) {
            case 'excel':
                return static::downloadExcelIcv($laporan, $data);
            case 'csv':
                return static::downloadCsvIcv($laporan, $data);
            case 'pdf':
                return static::downloadPdfIcv($laporan, $data);
            default:
                return static::downloadExcelIcv($laporan, $data);
        }
    }

    protected static function downloadExcelIcv(Collection $data, array $params)
    {
        $filename = 'laporan_penggunaan_icv_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Transfer-Encoding' => 'binary',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        $callback = function () use ($data) {
            $output = fopen('php://output', 'w');

            // BOM untuk UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header
            fputcsv($output, [
                'Tanggal Pelaporan',
                'User',
                'Kategori',
                'Kota/Kabupaten',
                'Jenis Vaksin',
                'Jumlah Terbit ICV',
                'Jumlah Guna Vaksin',
                'Sisa ICV',
                'Sisa Vaksin',
                'Catatan',
            ]);

            // Data
            foreach ($data as $row) {
                fputcsv($output, [
                    $row->tgl_pelaporan,
                    $row->user->name ?? '',
                    $row->user->kategori ?? '',
                    $row->user->kotakab ?? '',
                    $row->jenisVaksin->jenis_vaksin ?? '',
                    $row->jml_terbit_icv,
                    $row->jml_guna_vaksin,
                    $row->sisa_icv,
                    $row->sisa_vaksin,
                    $row->keterangan ?? '',
                ]);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected static function downloadCsvIcv(Collection $data, array $params)
    {
        return static::downloadExcelIcv($data, $params);
    }
}
