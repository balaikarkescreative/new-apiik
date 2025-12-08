<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VaksinatorResource\Pages;
use App\Models\User;
use App\Models\Vaksinator;
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

class VaksinatorResource extends Resource
{
    protected static ?string $model = Vaksinator::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Vaksinator';

    protected static ?string $pluralModelLabel = 'Data Vaksinator';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 7;

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('user') || auth()->user()->hasRole('admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Vaksinator')
                    ->schema([
                        Forms\Components\Select::make('id_akun')
                            ->label('Klinik/Rumah Sakit')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->user()->hasRole('admin') ? auth()->id() : auth()->id())
                            ->disabled(fn () => auth()->user()->hasRole('admin') ? false : true)
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('nama_vaksinator')
                            ->label('Nama Vaksinator')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Masukkan nama lengkap vaksinator'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Izin Praktek')
                    ->schema([
                        Forms\Components\TextInput::make('nomor_izin_praktek')
                            ->label('Nomor Izin Praktek')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('Contoh: IPV/001/2024'),

                        Forms\Components\DatePicker::make('masa_aktif_izin_praktek')
                            ->label('Masa Aktif Izin Praktek')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->minDate(now())
                            ->helperText('Pilih tanggal berakhirnya izin praktek'),

                        Forms\Components\FileUpload::make('dokumen_izin_praktek')
                            ->label('Dokumen Izin Praktek')
                            ->required()
                            ->disk('public')
                            ->directory('vaksinator/izin-praktek')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(3072)
                            ->downloadable()
                            ->openable()
                            ->helperText('Upload dokumen izin praktek dalam format PDF (maksimal 3MB)')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Sertifikat Vaksinator')
                    ->schema([
                        Forms\Components\TextInput::make('nomor_sertifikat_vaksinator')
                            ->label('Nomor Sertifikat Vaksinator')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('Contoh: SV/001/2024'),

                        Forms\Components\DatePicker::make('masa_aktif_sertifikat')
                            ->label('Masa Aktif Sertifikat')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->minDate(now())
                            ->helperText('Pilih tanggal berakhirnya sertifikat'),

                        Forms\Components\FileUpload::make('dokumen_sertifikat_vaksinator')
                            ->label('Dokumen Sertifikat Vaksinator')
                            ->required()
                            ->disk('public')
                            ->directory('vaksinator/sertifikat')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(3072)
                            ->downloadable()
                            ->openable()
                            ->helperText('Upload dokumen sertifikat vaksinator dalam format PDF (maksimal 3MB)')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                if (! Auth::user()->hasRole('admin')) {
                    $query->join('users', 'tbl_vaksinator.id_akun', '=', 'users.id')
                        ->select('tbl_vaksinator.*', 'users.name as user_name')
                        ->where('tbl_vaksinator.id_akun', Auth::id());
                } else {
                    $query->join('users', 'tbl_vaksinator.id_akun', '=', 'users.id')
                        ->select('tbl_vaksinator.*', 'users.name as user_name');
                }

                return $query->with(['user']);
            })
            ->columns([
                // Compact Vaksinator Info
                Tables\Columns\TextColumn::make('vaksinator_info')
                    ->label('Vaksinator')
                    ->getStateUsing(function ($record) {
                        $nama = $record->nama_vaksinator;
                        $klinik = $record->user->name ?? 'N/A';

                        return "{$nama}\n{$klinik}";
                    })
                    ->html()
                    ->formatStateUsing(function ($state) {
                        $lines = explode("\n", $state);

                        return '<div class="space-y-1">'.
                            '<div class="font-medium text-sm">'.$lines[0].'</div>'.
                            '<div class="text-xs text-gray-500">'.$lines[1].'</div>'.
                            '</div>';
                    })
                    ->searchable([
                        'tbl_vaksinator.nama_vaksinator',
                        'users.name',
                    ])
                    ->sortable(['nama_vaksinator']),

                // Izin Praktek
                Tables\Columns\TextColumn::make('izin_praktek')
                    ->label('Izin Praktek')
                    ->getStateUsing(function ($record) {
                        $nomor = $record->nomor_izin_praktek;
                        $tanggal = Carbon::parse($record->masa_aktif_izin_praktek)->translatedFormat('d M Y');
                        $status = $record->status_izin_praktek;

                        return "{$nomor}\n{$tanggal} • {$status}";
                    })
                    ->html()
                    ->formatStateUsing(function ($state, $record) {
                        $lines = explode("\n", $state);
                        $status = $record->status_izin_praktek;

                        $color = match ($status) {
                            'Aktif' => 'text-green-600',
                            'Akan Habis' => 'text-yellow-600',
                            'Kadaluarsa' => 'text-red-600',
                            default => 'text-gray-600'
                        };

                        return '<div class="space-y-1">'.
                            '<div class="font-medium text-sm">'.$lines[0].'</div>'.
                            '<div class="text-xs '.$color.'">'.$lines[1].'</div>'.
                            '</div>';
                    })
                    ->searchable(['tbl_vaksinator.nomor_izin_praktek'])
                    ->sortable(['masa_aktif_izin_praktek']),

                // Sertifikat Vaksinator
                Tables\Columns\TextColumn::make('sertifikat')
                    ->label('Sertifikat')
                    ->getStateUsing(function ($record) {
                        $nomor = $record->nomor_sertifikat_vaksinator;
                        $tanggal = Carbon::parse($record->masa_aktif_sertifikat)->translatedFormat('d M Y');
                        $status = $record->status_sertifikat;

                        return "{$nomor}\n{$tanggal} • {$status}";
                    })
                    ->html()
                    ->formatStateUsing(function ($state, $record) {
                        $lines = explode("\n", $state);
                        $status = $record->status_sertifikat;

                        $color = match ($status) {
                            'Aktif' => 'text-green-600',
                            'Akan Habis' => 'text-yellow-600',
                            'Kadaluarsa' => 'text-red-600',
                            default => 'text-gray-600'
                        };

                        return '<div class="space-y-1">'.
                            '<div class="font-medium text-sm">'.$lines[0].'</div>'.
                            '<div class="text-xs '.$color.'">'.$lines[1].'</div>'.
                            '</div>';
                    })
                    ->searchable(['tbl_vaksinator.nomor_sertifikat_vaksinator'])
                    ->sortable(['masa_aktif_sertifikat']),

                // Dokumen Izin Praktek
                Tables\Columns\TextColumn::make('dokumen_izin_praktek')
                    ->label('Dok. Izin')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->dokumen_izin_praktek ? 'Ada' : 'Belum')
                    ->color(fn ($record) => $record->dokumen_izin_praktek ? 'success' : 'danger')
                    ->url(fn ($record) => $record->dokumen_izin_praktek ? asset('storage/'.$record->dokumen_izin_praktek) : null)
                    ->openUrlInNewTab(),

                // Dokumen Sertifikat Vaksinator
                Tables\Columns\TextColumn::make('dokumen_sertifikat_vaksinator')
                    ->label('Dok. Sertifikat')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->dokumen_sertifikat_vaksinator ? 'Ada' : 'Belum')
                    ->color(fn ($record) => $record->dokumen_sertifikat_vaksinator ? 'success' : 'danger')
                    ->url(fn ($record) => $record->dokumen_sertifikat_vaksinator ? asset('storage/'.$record->dokumen_sertifikat_vaksinator) : null)
                    ->openUrlInNewTab(),

            ])
            ->filters([
                // Klinik/RS Filter
                SelectFilter::make('id_akun')
                    ->label('Klinik/RS')
                    ->searchable()
                    ->visible(fn () => Auth::user()->hasRole('admin'))
                    ->options(function () {
                        $userIds = Vaksinator::distinct()->pluck('id_akun');

                        return User::whereIn('id', $userIds)
                            ->pluck('name', 'id');
                    }),

                // Status Izin Praktek Filter
                SelectFilter::make('status_izin')
                    ->label('Status Izin Praktek')
                    ->options([
                        'aktif' => 'Aktif',
                        'akan_habis' => 'Akan Habis',
                        'kadaluarsa' => 'Kadaluarsa',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! $data['value']) {
                            return $query;
                        }

                        switch ($data['value']) {
                            case 'aktif':
                                return $query->where('tbl_vaksinator.masa_aktif_izin_praktek', '>', now()->addDays(30));
                            case 'akan_habis':
                                return $query->whereBetween('tbl_vaksinator.masa_aktif_izin_praktek', [now(), now()->addDays(30)]);
                            case 'kadaluarsa':
                                return $query->where('tbl_vaksinator.masa_aktif_izin_praktek', '<', now());
                        }
                    }),

                // Status Sertifikat Filter
                SelectFilter::make('status_sertifikat')
                    ->label('Status Sertifikat')
                    ->options([
                        'aktif' => 'Aktif',
                        'akan_habis' => 'Akan Habis',
                        'kadaluarsa' => 'Kadaluarsa',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! $data['value']) {
                            return $query;
                        }

                        switch ($data['value']) {
                            case 'aktif':
                                return $query->where('tbl_vaksinator.masa_aktif_sertifikat', '>', now()->addDays(30));
                            case 'akan_habis':
                                return $query->whereBetween('tbl_vaksinator.masa_aktif_sertifikat', [now(), now()->addDays(30)]);
                            case 'kadaluarsa':
                                return $query->where('tbl_vaksinator.masa_aktif_sertifikat', '<', now());
                        }
                    }),

                // Alert Filter
                Tables\Filters\Filter::make('akan_kadaluarsa')
                    ->label('Akan Kadaluarsa (30 hari)')
                    ->query(fn (Builder $query) => $query->where(function ($q) {
                        $q->whereBetween('tbl_vaksinator.masa_aktif_izin_praktek', [now(), now()->addDays(30)])
                            ->orWhereBetween('tbl_vaksinator.masa_aktif_sertifikat', [now(), now()->addDays(30)]);
                    }))
                    ->toggle(),
            ], FiltersLayout::AboveContent)
            ->persistFiltersInSession()
            ->defaultSort('date_updated', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->size('sm'),
                Tables\Actions\EditAction::make()
                    ->size('sm'),
                Tables\Actions\DeleteAction::make()
                    ->size('sm'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped()
            ->paginated([15, 30, 50]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVaksinators::route('/'),
            'create' => Pages\CreateVaksinator::route('/create'),
            'view' => Pages\ViewVaksinator::route('/{record}'),
            'edit' => Pages\EditVaksinator::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canViewAny(): bool
    {
        return true;
    }
}
