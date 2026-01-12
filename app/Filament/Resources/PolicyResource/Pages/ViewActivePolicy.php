<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Filament\Resources\PolicyResource;
use App\Models\Policy;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Carbon;

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
            ->latest('published_at')
            ->first();

        if ($this->record) {
            $publishedAt = $this->record->published_at
                ? Carbon::parse($this->record->published_at)
                    ->timezone('Asia/Jakarta')
                    ->locale('id')
                    ->translatedFormat('d F Y') // <-- tanggal bulan tahun
                : '-';

            $this->form->fill([
                'title'              => $this->record->title,
                'content_html'       => $this->record->content,
                'is_active'          => $this->record->is_active,
                'published_at_label' => $publishedAt,
            ]);
        } else {
            $this->form->fill([
                'title'              => '-',
                'content_html'       => '<p>Belum ada kebijakan aktif.</p>',
                'is_active'          => false,
                'published_at_label' => '-',
            ]);
        }
    }

    public function getTitle(): string
    {
        return 'Kebijakan & Ketentuan';
    }

    public function getBreadcrumbs(): array
    {
        return [
            PolicyResource::getUrl('active') => 'Kebijakan & Ketentuan',
        ];
    }

    protected function getHeaderActions(): array
    {
        if (! $this->record) {
            return [];
        }

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
                    ->label('Isi Kebijakan & Ketentuan')
                    ->view('filament.forms.components.policy-content-view')
                    ->columnSpanFull()
                    ->dehydrated(false),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\TextInput::make('published_at_label')
                    ->label('Tanggal Berlaku')
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }
}
