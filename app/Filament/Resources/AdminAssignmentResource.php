<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminAssignmentResource\Pages;
use App\Models\AdminScope;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use App\Models\Dorm;
use App\Models\Block;


class AdminAssignmentResource extends Resource
{
    protected static ?string $model = AdminScope::class;

    protected static ?string $slug = 'pengangkatan-admin';
    protected static ?string $navigationGroup = 'Penghuni';
    protected static ?string $navigationLabel = 'Pengangkatan Admin';
    protected static ?string $pluralLabel = 'Admin';
    protected static ?string $modelLabel = 'Admin';
    protected static ?int $navigationSort = 50;
    protected static ?string $navigationIcon = null;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'main_admin']) ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('type', ['branch', 'block'])
            ->whereHas('user.roles', fn(Builder $q) => $q->where('name', 'resident')) // hanya resident
            ->whereHas('user.residentProfile', fn(Builder $q) => $q->where('citizenship_status', 'WNI')) // bukan mancanegara
            ->with([
                'user.residentProfile',
                'dorm',
                'block',
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Pengangkatan Admin')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Penghuni')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->visible(fn(string $operation) => $operation === 'create')
                        ->relationship(
                            name: 'user',
                            titleAttribute: 'email',
                            modifyQueryUsing: fn(Builder $query) => $query
                                ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
                                ->whereHas('residentProfile', fn(Builder $q) => $q->where('citizenship_status', 'WNI'))
                                ->whereHas('roomResidents', fn(Builder $q) => $q->whereNull('room_residents.check_out_date'))
                                ->whereDoesntHave('adminScopes', fn(Builder $q) => $q->whereIn('type', ['branch', 'block']))
                                ->with('residentProfile')
                                ->orderBy('email')
                        )
                        ->getOptionLabelFromRecordUsing(function (User $record) {
                            $name = $record->residentProfile?->full_name ?? $record->name;
                            return "{$name} — {$record->email}";
                        })
                        ->helperText('Hanya penghuni domestik yang memiliki kamar aktif dan belum menjadi admin.'),

                    Forms\Components\Placeholder::make('user_display')
                        ->label('Penghuni')
                        ->visible(fn(string $operation) => $operation === 'edit')
                        ->content(function ($record) {
                            $name = $record->user?->residentProfile?->full_name ?? $record->user?->name ?? '-';
                            $email = $record->user?->email ?? '-';
                            return "{$name} — {$email}";
                        }),

                    Forms\Components\Select::make('type')
                        ->label('Role Admin')
                        ->required()
                        ->native(false)
                        ->options([
                            'branch' => 'Admin Cabang',
                            'block'  => 'Admin Komplek',
                        ]),

                    Forms\Components\Toggle::make('show_phone_on_landing')
                        ->label('Tampilkan di Halaman Kontak')
                        ->helperText('Jika diaktifkan, nomor WhatsApp penghuni ini akan ditampilkan di halaman kontak landing page.')
                        ->default(false)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.residentProfile.full_name')
                    ->label('Nama Penghuni')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Role Admin')
                    ->formatStateUsing(fn(string $state) => $state === 'branch' ? 'Admin Cabang' : 'Admin Komplek')
                    ->colors([
                        'warning' => 'branch',
                        'success' => 'block',
                    ]),

                Tables\Columns\TextColumn::make('dorm.name')
                    ->label('Cabang')
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('block.name')
                    ->label('Komplek')
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('show_phone_on_landing')
                    ->label('Tampil di Kontak')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diangkat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe Admin')
                    ->options([
                        'branch' => 'Admin Cabang',
                        'block'  => 'Admin Komplek',
                    ])
                    ->native(false),

                Filter::make('scope')
                    ->label('Lokasi')
                    ->form([
                        Forms\Components\Select::make('dorm_id')
                            ->label('Cabang')
                            ->options(fn () => Dorm::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->reactive()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('block_id', null)),

                        Forms\Components\Select::make('block_id')
                            ->label('Komplek')
                            ->placeholder('Pilih cabang terlebih dahulu')
                            ->options(
                                fn (Forms\Get $get) => Block::query()
                                    ->when($get('dorm_id'), fn (Builder $q, $dormId) => $q->where('dorm_id', $dormId))
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->native(false)
                            ->disabled(fn (Forms\Get $get) => blank($get('dorm_id')))
                            ->helperText(fn (Forms\Get $get) => blank($get('dorm_id'))
                                ? 'Komplek bisa dipilih setelah memilih cabang.'
                                : null),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['dorm_id'] ?? null, fn (Builder $q, $dormId) => $q->where('admin_scopes.dorm_id', $dormId))
                            ->when($data['block_id'] ?? null, fn (Builder $q, $blockId) => $q->where('admin_scopes.block_id', $blockId));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Edit'),

                Tables\Actions\Action::make('revoke')
                    ->label('Cabut Admin')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (AdminScope $record) {
                        // Cek apakah user menjadi penanggung jawab di kamar manapun
                        $assignedRoomsCount = \App\Models\Room::where('contact_person_user_id', $record->user_id)->count();

                        if ($assignedRoomsCount > 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('Gagal Mencabut Admin')
                                ->body("Admin ini masih menjadi penanggung jawab untuk {$assignedRoomsCount} kamar. Mohon ganti penanggung jawab kamar-kamar tersebut terlebih dahulu sebelum mencabut hak admin.")
                                ->danger()
                                ->send();
                            
                            return;
                        }

                        app(\App\Services\AdminPrivilegeService::class)->revokeAdmin($record->user);

                        \Filament\Notifications\Notification::make()
                            ->title('Hak admin dicabut')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAdminAssignments::route('/'),
            'create' => Pages\CreateAdminAssignment::route('/buat'),
            'edit'   => Pages\EditAdminAssignment::route('/{record}/edit'),
        ];
    }
}
