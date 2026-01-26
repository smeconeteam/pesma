<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Filament\Resources\PolicyResource;
use App\Models\Policy;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;

class ViewActivePolicy extends Page
{
    protected static string $resource = PolicyResource::class;

    protected static string $view = 'filament.resources.policy.pages.view-active-policy';

    public ?Policy $record = null;

    public ?array $data = [];

    public function mount(): void
    {
        $this->record = Policy::query()
            ->where('is_active', true)
            ->latest('updated_at')
            ->first();

        // Jika belum ada record, buat default record
        if (! $this->record) {
            $this->record = Policy::create([
                'title' => 'Ketentuan',
                'content' => '<p>Silakan edit untuk mengisi kebijakan dan ketentuan.</p>',
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);
        }

        $this->form->fill([
            'title'        => $this->record->title,
            'content_html' => $this->record->content,
            'is_active'    => $this->record->is_active,
        ]);
    }

    public function getTitle(): string
    {
        return 'Ketentuan';
    }

    public function getBreadcrumbs(): array
    {
        return [
            PolicyResource::getUrl('active') => 'Ketentuan',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-m-pencil-square')
                ->url(PolicyResource::getUrl('edit', ['record' => $this->record])),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Judul')
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\ViewField::make('content_html')
                    ->label('Isi Ketentuan')
                    ->view('filament.forms.components.policy-content-view')
                    ->columnSpanFull()
                    ->dehydrated(false),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }
}
