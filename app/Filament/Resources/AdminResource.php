<?php

// filepath: /home/syhrl/Documents/new-apiik2/app/Filament/Resources/AdminResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Kelola Admin';

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'Pengaturan User';

    protected static ?int $navigationSort = 90;

    public static function canAccess(): bool
    {
        // Hanya admin yang bisa akses kelola admin
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    public static function getEloquentQuery(): Builder
    {
        // Hanya tampilkan user dengan role admin
        return parent::getEloquentQuery()
            ->whereHas('roles', function ($query) {
                $query->whereNot('name', 'user'); // Exclude users with role 'user'
            });
    }

    public static function isDokterRole($roleState, ?User $record = null): bool
    {
        if ($record && ($record->hasRole('dokter') || ! empty($record->simply_user_id))) {
            if ($roleState === null) {
                return true;
            }
        }

        if (empty($roleState)) {
            return false;
        }

        $dokterRoleId = Role::where('name', 'dokter')->value('id');

        if (is_array($roleState)) {
            return in_array($dokterRoleId, $roleState);
        }

        if (is_numeric($roleState)) {
            return (int) $roleState === (int) $dokterRoleId;
        }

        if ($roleState instanceof Role) {
            return $roleState->name === 'dokter';
        }

        return $roleState === 'dokter';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Admin')
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label('Role')
                            ->relationship('roles', 'name')
                            ->options(function () {
                                return Role::whereNot('name', 'user') // Exclude 'user' role
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->live()
                            ->required()
                            ->columnSpan(2),

                        Forms\Components\Select::make('simply_user_id')
                            ->label('Pilih Akun Pegawai dari SIMPLY')
                            ->placeholder('Ketik nama pegawai, NIP, atau username...')
                            ->helperText('Pilih pegawai dari sistem SIMPLY untuk dijadikan dokter. Data nama, username, email, dan kontak akan terisi otomatis.')
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search): array {
                                return \App\Models\SimplyUser::aktif()
                                    ->where(function ($q) use ($search) {
                                        $q->where('nama', 'like', "%{$search}%")
                                            ->orWhere('username', 'like', "%{$search}%");
                                    })
                                    ->limit(20)
                                    ->get()
                                    ->mapWithKeys(fn (\App\Models\SimplyUser $user) => [
                                        $user->id_user => "{$user->nama} (".($user->username ?: 'No NIP/User').')',
                                    ])
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function ($value): ?string {
                                $user = \App\Models\SimplyUser::find($value);

                                return $user ? "{$user->nama} (".($user->username ?: 'No NIP/User').')' : null;
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (! $state) {
                                    return;
                                }
                                $simplyUser = \App\Models\SimplyUser::find($state);
                                if ($simplyUser) {
                                    $set('name', $simplyUser->nama);
                                    $set('username', $simplyUser->username);
                                    $set('email', filled($simplyUser->email) ? $simplyUser->email : null);
                                    $set('kontak_pj', $simplyUser->no_telp);
                                }
                            })
                            ->required(fn (callable $get, ?User $record) => self::isDokterRole($get('roles'), $record))
                            ->visible(fn (callable $get, ?User $record) => self::isDokterRole($get('roles'), $record))
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->disabled(fn (callable $get, ?User $record) => self::isDokterRole($get('roles'), $record))
                            ->dehydrated()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('username')
                            ->label('Username')
                            ->required()
                            ->unique(table: 'users', column: 'username', ignoreRecord: true)
                            ->disabled(fn (callable $get, ?User $record) => self::isDokterRole($get('roles'), $record))
                            ->dehydrated()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->nullable()
                            ->required(fn (callable $get, ?User $record) => ! self::isDokterRole($get('roles'), $record))
                            ->unique(table: 'users', column: 'email', ignoreRecord: true)
                            ->disabled(fn (callable $get, ?User $record) => self::isDokterRole($get('roles'), $record))
                            ->dehydrated()
                            ->helperText(fn (callable $get, ?User $record) => self::isDokterRole($get('roles'), $record) ? 'Dikosongkan jika akun SIMPLY tidak memiliki email.' : null),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->visible(fn (callable $get, ?User $record) => ! self::isDokterRole($get('roles'), $record))
                            ->required(fn ($livewire, callable $get, ?User $record) => $livewire instanceof Pages\CreateAdmin && ! self::isDokterRole($get('roles'), $record))
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->minLength(8),

                        Forms\Components\Placeholder::make('simply_password_notice')
                            ->label('Informasi Password')
                            ->content('Akun Dokter terhubung dengan database SIMPLY. Password dikelola langsung di sistem SIMPLY (dokter login menggunakan password SIMPLY miliknya).')
                            ->visible(fn (callable $get, ?User $record) => self::isDokterRole($get('roles'), $record)),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Informasi Tambahan')
                    ->schema([
                        Forms\Components\TextInput::make('kontak_pj')
                            ->label('Nomor HP/WhatsApp')
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\Select::make('status_akun')
                            ->label('Status Akun')
                            ->options([
                                'Aktif' => 'Aktif',
                                'Tidak Aktif' => 'Tidak Aktif',
                            ])
                            ->default('Aktif')
                            ->required(),

                        Forms\Components\Hidden::make('verifikasi_akun')
                            ->default('Diterima'),

                        Forms\Components\Hidden::make('date_updated')
                            ->default(now()),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->color(fn ($state) => match (strtolower($state ?? '')) {
                        'admin' => 'danger',
                        'dokter' => 'success',
                        'verifikator1' => 'info',
                        'verifikator2' => 'warning',
                        default => 'primary',
                    }),

                Tables\Columns\TextColumn::make('simply_user_id')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'SIMPLY' : 'Lokal')
                    ->color(fn ($state) => $state ? 'info' : 'gray'),

                Tables\Columns\TextColumn::make('kontak_pj')
                    ->label('Kontak')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status_akun')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Aktif' => 'success',
                        'Tidak Aktif' => 'danger',
                        default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('date_updated')
                    ->label('Terakhir Update')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_akun')
                    ->options([
                        'Aktif' => 'Aktif',
                        'Tidak Aktif' => 'Tidak Aktif',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->button(),

                Tables\Actions\Action::make('toggleStatus')
                    ->label(fn (User $record) => $record->status_akun === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn (User $record) => $record->status_akun === 'Aktif' ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn (User $record) => $record->status_akun === 'Aktif' ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->button()
                    ->visible(fn (User $record) => auth()->user()->id !== $record->id) // Tidak bisa nonaktifkan diri sendiri
                    ->action(function (User $record) {
                        $record->status_akun = $record->status_akun === 'Aktif' ? 'Tidak Aktif' : 'Aktif';
                        $record->date_updated = now();
                        $record->save();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->check()),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdmins::route('/'),
            'create' => Pages\CreateAdmin::route('/create'),
            'edit' => Pages\EditAdmin::route('/{record}/edit'),
        ];
    }
}
