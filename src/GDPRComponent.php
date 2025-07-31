<?php

declare(strict_types=1);

namespace Milenmk\LaravelGdprExporter;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View as FacadesView;
use Illuminate\View\View;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Milenmk\LaravelGdprExporter\Services\UserDataExporterService;

class GDPRComponent extends Component
{
    public ?string $userData = null;

    protected UserDataExporterService $exporter;

    public function mount(): void
    {
        $this->exporter = new UserDataExporterService();
    }

    public function render(): View
    {
        return FacadesView::make('laravel-gdpr-exporter::livewire.gdpr');
    }

    public function exportUserData(): void
    {
        $this->userData = $this->exporter->exportToJson(Auth::user());
    }

    public function downloadData(string $format): StreamedResponse
    {
        $user = Auth::user();

        return match ($format) {
            'json' => response()->streamDownload(
                fn () => print $this->exporter->exportToJson($user),
                'user-data.json',
                ['Content-Type' => 'application/json']
            ),
            'csv' => $this->exporter->exportToCsv($user),
            'xml' => response()->streamDownload(
                fn () => print $this->exporter->exportToXml($user),
                'user-data.xml',
                ['Content-Type' => 'application/xml']
            ),
            'html' => response()->streamDownload(
                fn () => print $this->exporter->exportToHtml($user),
                'user-data.html',
                ['Content-Type' => 'text/html']
            ),
            default => abort(400, 'Unsupported format'),
        };
    }
}
