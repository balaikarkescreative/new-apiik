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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Admin')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('username')
                            ->label('Username')
                            ->required()
                            ->unique(table: 'users', column: 'username', ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(table: 'users', column: 'email', ignoreRecord: true),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->required(fn ($livewire) => $livewire instanceof Pages\CreateAdmin)
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->minLength(8),

                        Forms\Components\Select::make('roles')
                            ->label('Role')
                            ->relationship('roles', 'name')
                            ->options(function () {
                                return Role::whereNot('name', 'user') // Exclude 'user' role
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->required(),
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
                    ->searchable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->color('primary'),

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
