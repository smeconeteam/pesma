<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentMethodResource\Pages;
use App\Models\PaymentMethod;
use Filament\Resources\Resource;

class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?string $navigationLabel = 'Metode Pembayaran';
    protected static ?string $pluralLabel = 'Metode Pembayaran';
    protected static ?string $modelLabel = 'Metode Pembayaran';
    protected static ?int $navigationSort = 10;

    /** =========================
     *  ACCESS CONTROL (NO POLICY)
     *  ========================= */
    protected static function isAllowed(): bool
    {
        $user = auth()->user();

        return $user && (
            $user->hasRole('super_admin') ||
            $user->hasRole('main_admin')
        );
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::isAllowed();
    }

    public static function canViewAny(): bool
    {
        return static::isAllowed();
    }

    public static function canCreate(): bool
    {
        return static::isAllowed();
    }

    public static function canEdit($record): bool
    {
        return static::isAllowed();
    }

    public static function canDelete($record): bool
    {
        return static::isAllowed();
    }

    public static function canDeleteAny(): bool
    {
        return static::isAllowed();
    }

    /**
     * Karena kamu ingin 1 halaman untuk mengatur semua metode (QRIS/Transfer/Tunai),
     * kita tidak pakai list/create/edit default.
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePaymentMethods::route('/'),
        ];
    }
}
