<?php

namespace App\Services;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class ResidentPdfExport
{
    public function export(Collection $residents, array $filters = []): \Illuminate\Http\Response
    {
        // Clean data untuk menghindari UTF-8 error
        $residents = $residents->map(function ($resident) {
            if ($resident->residentProfile) {
                $resident->residentProfile->full_name = $this->cleanString($resident->residentProfile->full_name);
                $resident->residentProfile->birth_place = $this->cleanString($resident->residentProfile->birth_place);
                $resident->residentProfile->university_school = $this->cleanString($resident->residentProfile->university_school);
                $resident->residentProfile->guardian_name = $this->cleanString($resident->residentProfile->guardian_name);
            }
            $resident->email = $this->cleanString($resident->email);
            $resident->name = $this->cleanString($resident->name);
            return $resident;
        });

        $data = [
            'residents' => $residents,
            'filters' => $filters,
            'exported_at' => Carbon::now()->format('d F Y H:i'),
            'exported_by' => $this->cleanString(auth()->user()->name),
        ];

        $pdf = Pdf::loadView('pdf.residents', $data)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
                'dpi' => 96,
            ]);

        $filename = 'data-penghuni-' . Carbon::now()->format('Y-m-d-His') . '.pdf';

        return $pdf->download($filename);
    }

    private function cleanString($string)
    {
        if (empty($string)) {
            return $string;
        }
        
        // Remove invalid UTF-8 characters
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        
        // Remove control characters except newline and tab
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $string);
        
        return $string;
    }

    public function stream(Collection $residents, array $filters = []): \Illuminate\Http\Response
    {
        // Clean data
        $residents = $residents->map(function ($resident) {
            if ($resident->residentProfile) {
                $resident->residentProfile->full_name = $this->cleanString($resident->residentProfile->full_name);
                $resident->residentProfile->birth_place = $this->cleanString($resident->residentProfile->birth_place);
                $resident->residentProfile->university_school = $this->cleanString($resident->residentProfile->university_school);
                $resident->residentProfile->guardian_name = $this->cleanString($resident->residentProfile->guardian_name);
            }
            $resident->email = $this->cleanString($resident->email);
            $resident->name = $this->cleanString($resident->name);
            return $resident;
        });

        $data = [
            'residents' => $residents,
            'filters' => $filters,
            'exported_at' => Carbon::now()->format('d F Y H:i'),
            'exported_by' => $this->cleanString(auth()->user()->name),
        ];

        $pdf = Pdf::loadView('pdf.residents', $data)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
                'dpi' => 96,
            ]);

        return $pdf->stream();
    }
}