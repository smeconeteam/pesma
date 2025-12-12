<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResidentResource\Pages;
use App\Models\Block;
use App\Models\Dorm;
use App\Models\Room;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ResidentResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationLabel = 'Penghuni';
    protected static ?string $pluralLabel = 'Penghuni';
    protected static ?string $modelLabel = 'Penghuni';
    protected static ?int $navigationSort = 30;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
            ->with(['residentProfile', 'roomResidents.room.block']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Profil Penghuni')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),

                    Forms\Components\TextInput::make('profile.full_name')
                        ->label('Nama Lengkap')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('profile.national_id')
                        ->label('NIK')
                        ->maxLength(16)
                        ->minLength(16)
                        ->rule('digits:16') // hanya angka & harus 16 digit
                        ->helperText('16 digit, hanya angka.')
                        ->extraInputAttributes([
                            'inputmode' => 'numeric',
                            'pattern'   => '[0-9]*',
                        ]),

                    Forms\Components\TextInput::make('profile.student_id')
                        ->label('NIM')
                        ->maxLength(50),

                    Forms\Components\Select::make('profile.gender')
                        ->label('Jenis Kelamin')
                        ->options([
                            'M' => 'Laki-laki',
                            'F' => 'Perempuan',
                        ])
                        ->native(false),

                    Forms\Components\TextInput::make('profile.birth_place')
                        ->label('Tempat Lahir')
                        ->maxLength(100),

                    Forms\Components\DatePicker::make('profile.birth_date')
                        ->label('Tanggal Lahir')
                        ->native(false),

                    Forms\Components\TextInput::make('profile.university_school')
                        ->label('Universitas/Sekolah')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('profile.phone_number')
                        ->label('No. HP')
                        ->maxLength(15)
                        ->rule('regex:/^\d+$/') // hanya angka
                        ->helperText('Hanya angka, tanpa spasi/tanda +.')
                        ->extraInputAttributes([
                            'inputmode' => 'numeric',
                            'pattern'   => '[0-9]*',
                        ]),

                    Forms\Components\TextInput::make('profile.guardian_name')
                        ->label('Nama Wali')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('profile.guardian_phone_number')
                        ->label('No. HP Wali')
                        ->maxLength(15)
                        ->rule('regex:/^\d+$/')
                        ->helperText('Hanya angka, tanpa spasi/tanda +.')
                        ->extraInputAttributes([
                            'inputmode' => 'numeric',
                            'pattern'   => '[0-9]*',
                        ]),

                    Forms\Components\FileUpload::make('profile.photo_path')
                        ->label('Foto')
                        ->directory('residents')
                        ->image()
                        ->imageEditor()
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Penempatan Kamar')
                ->description('Data ini akan membuat record di room_residents saat disimpan.')
                ->columns(2)
                ->schema([
                    // untuk filter saja (tidak disimpan)
                    Forms\Components\Select::make('dorm_id')
                        ->label('Cabang (Dorm)')
                        ->options(fn() => Dorm::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->native(false)
                        ->reactive()
                        ->dehydrated(false)
                        ->afterStateUpdated(function (Forms\Set $set) {
                            $set('block_id', null);
                            $set('room.room_id', null);
                        }),

                    // untuk filter saja (tidak disimpan)
                    Forms\Components\Select::make('block_id')
                        ->label('Blok')
                        ->options(
                            fn(Forms\Get $get) => Block::query()
                                ->where('dorm_id', $get('dorm_id'))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->native(false)
                        ->reactive()
                        ->dehydrated(false)
                        ->disabled(fn(Forms\Get $get) => blank($get('dorm_id')))
                        ->afterStateUpdated(function (Forms\Set $set) {
                            $set('room.room_id', null);
                        }),

                    Forms\Components\Select::make('room.room_id')
                        ->label('Kamar')
                        ->required()
                        ->searchable()
                        ->native(false)
                        ->options(function (Forms\Get $get) {
                            $blockId = $get('block_id');

                            if (blank($blockId)) return [];

                            return Room::query()
                                ->where('block_id', $blockId)
                                ->where('is_active', true)
                                ->orderBy('code')
                                ->get()
                                ->mapWithKeys(function (Room $room) {
                                    $label = trim(($room->code ?? '') . ' ' . ($room->number ? "({$room->number})" : ''));
                                    $label .= " — Kap: {$room->capacity}";
                                    return [$room->id => $label];
                                })
                                ->toArray();
                        }),

                    Forms\Components\DatePicker::make('room.check_in_date')
                        ->label('Tanggal Masuk')
                        ->required()
                        ->default(now()->toDateString())
                        ->native(false),

                    Forms\Components\Toggle::make('room.is_pic')
                        ->label('Jadikan PIC?')
                        ->default(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('residentProfile.full_name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('residentProfile.national_id')
                    ->label('NIK')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('residentProfile.student_id')
                    ->label('NIM')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('residentProfile.phone_number')
                    ->label('No. HP')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('residentProfile.gender')
                    ->label('Gender')
                    ->formatStateUsing(fn(?string $state) => $state === 'M' ? 'Laki-laki' : ($state === 'F' ? 'Perempuan' : '-'))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('current_room')
                    ->label('Kamar Aktif')
                    ->getStateUsing(function (User $record) {
                        $active = $record->roomResidents()
                            ->whereNull('check_out_date')
                            ->with('room')
                            ->latest('check_in_date')
                            ->first();

                        if (! $active?->room) return '-';
                        $room = $active->room;
                        return ($room->code ?? '-') . ($room->number ? " ({$room->number})" : '');
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Dihapus')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),

                SelectFilter::make('gender')
                    ->label('Gender')
                    ->options(['M' => 'Laki-laki', 'F' => 'Perempuan'])
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $value) {
                            $q->whereHas('residentProfile', fn(Builder $p) => $p->where('gender', $value));
                        });
                    }),

                SelectFilter::make('dorm_id')
                    ->label('Cabang (Dorm)')
                    ->options(fn() => Dorm::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function (Builder $q, $dormId) {
                            $q->whereHas('roomResidents', function (Builder $rr) use ($dormId) {
                                $rr->whereNull('check_out_date')
                                    ->whereHas('room.block', fn(Builder $b) => $b->where('dorm_id', $dormId));
                            });
                        });
                    }),

                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Lihat'),

                Tables\Actions\EditAction::make()->label('Edit'),

                Tables\Actions\DeleteAction::make()->label('Hapus'), // soft delete

                Tables\Actions\RestoreAction::make()->label('Pulihkan'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListResidents::route('/'),
            'create' => Pages\CreateResident::route('/create'),
            'edit'   => Pages\EditResident::route('/{record}/edit'),
        ];
    }
}
