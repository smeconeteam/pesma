<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use App\Models\Institution;
use Filament\PanelProvider;
use Filament\Enums\ThemeMode;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // Get institution data
        $institution = null;
        try {
            $institution = Institution::first();
        } catch (\Exception $e) {
            // Handle error jika tabel belum ada
        }

        // Setup favicon
        $faviconUrl = null;
        if ($institution && $institution->logo_path && Storage::disk('public')->exists($institution->logo_path)) {
            $faviconUrl = asset('storage/' . $institution->logo_path);
        }

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->colors([
                'primary' => Color::Green,
            ])
            ->navigationGroups([
                NavigationGroup::make('Asrama')
                    ->icon('heroicon-o-building-office-2')
                    ->collapsed(true),

                NavigationGroup::make('Penghuni')
                    ->icon('heroicon-o-user-group')
                    ->collapsed(true),

                NavigationGroup::make('Keuangan')
                    ->icon('heroicon-o-banknotes')
                    ->collapsed(true),

                NavigationGroup::make('Pengaturan')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(true),
            ])
            ->authGuard('web')
            ->defaultThemeMode(ThemeMode::Light)

            // Set favicon dinamis
            ->favicon($faviconUrl)

            // User menu items
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label('Profile Saya')
                    ->url(fn(): string => route('filament.admin.resources.profile-saya.index'))
                    ->icon('heroicon-o-user-circle')
                    ->visible(fn(): bool => auth()->user()?->hasRole('main_admin') ?? false),
                'resident_dashboard' => MenuItem::make()
                    ->label(fn(): string => __('navigation.resident_dashboard'))
                    ->url(fn(): string => localizedRoute('dashboard'))
                    ->icon('heroicon-o-home')
                    ->visible(fn(): bool => auth()->user()?->hasRole('resident') ?? false),
            ])

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->brandName(function () {
                try {
                    $institution = Institution::first();

                    if ($institution) {
                        $brandName = $institution->dormitory_name;

                        // Jika ada logo, kembalikan HTML dengan logo dan teks
                        if ($institution->logo_path && Storage::disk('public')->exists($institution->logo_path)) {
                            $logoUrl = asset('storage/' . $institution->logo_path);
                            return new \Illuminate\Support\HtmlString(
                                '<div class="flex items-center gap-3">' .
                                    '<img src="' . $logoUrl . '" alt="Logo" class="h-10 w-auto object-contain" />' .
                                    '<span class="text-xl font-bold tracking-tight">' . e($brandName) . '</span>' .
                                    '</div>'
                            );
                        }

                        return $brandName;
                    }

                    return config('app.name');
                } catch (\Exception $e) {
                    return config('app.name');
                }
            })
            ->brandLogo(null)
            ->brandLogoHeight('auto')
            ->homeUrl('/')
            ->renderHook(
                'panels::user-menu.before',
                fn() => Blade::render(<<<'HTML'
                    <div 
                        class="flex items-center gap-3 px-3 py-2 text-sm border-r border-gray-200 dark:border-gray-700" 
                        x-data="{ 
                            dayName: '',
                            dateStr: '',
                            time: '',
                            updateDateTime() {
                                const now = new Date();
                                
                                // Update jam
                                const hours = String(now.getHours()).padStart(2, '0');
                                const minutes = String(now.getMinutes()).padStart(2, '0');
                                const seconds = String(now.getSeconds()).padStart(2, '0');
                                this.time = hours + ':' + minutes + ':' + seconds;
                                
                                // Update hari dalam bahasa Indonesia
                                const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                                this.dayName = days[now.getDay()];
                                
                                // Update tanggal dalam bahasa Indonesia
                                const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                                               'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                                const day = now.getDate();
                                const month = months[now.getMonth()];
                                const year = now.getFullYear();
                                this.dateStr = day + ' ' + month + ' ' + year;
                            }
                        }" 
                        x-init="
                            updateDateTime();
                            setInterval(() => { updateDateTime(); }, 1000);
                        "
                    >
                        <div class="text-right leading-tight flex gap-2 items-center">
                            <div class="flex flex-row">
                                <div class="font-semibold text-sm" x-text="dayName"></div>,&nbsp;
                                <div class="font-semibold text-sm" x-text="dateStr"></div>
                            </div>

                            <div 
                                class="text-base font-mono font-bold" 
                                x-text="time"
                            >
                            </div>
                        </div>
                    </div>
                HTML)
            )
            ->renderHook(
                'panels::head.end',
                fn() => view('filament.components.favicon')
            )
            ->renderHook(
                'panels::head.end',
                fn() => view('filament.components.clear-navigation-state')
            )
            ->renderHook(
                'panels::styles.before',
                fn() => view('filament.components.navigation-group-styles')
            )
            ->renderHook(
                'panels::body.end',
                fn() => view('filament.components.accordion-navigation')
            );
    }
}
