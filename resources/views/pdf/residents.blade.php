<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Data Penghuni</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        
        .header h1 {
            font-size: 16px;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .header p {
            font-size: 10px;
            color: #666;
        }
        
        .info {
            margin-bottom: 15px;
            font-size: 9px;
        }
        
        .info-row {
            margin-bottom: 3px;
        }
        
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        th {
            background-color: #2c3e50;
            color: white;
            padding: 8px 4px;
            text-align: left;
            font-weight: bold;
            font-size: 8px;
            border: 1px solid #000;
        }
        
        td {
            padding: 6px 4px;
            border: 1px solid #ddd;
            font-size: 8px;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .text-center {
            text-align: center;
        }
        
        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: bold;
        }
        
        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .badge-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .footer {
            margin-top: 20px;
            text-align: right;
            font-size: 8px;
            color: #666;
        }
        
        .no-data {
            text-align: center;
            padding: 30px;
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>DATA PENGHUNI</h1>
        <p>Pondok Pesantren Modern Elfira</p>
    </div>

    <div class="info">
        <div class="info-row">
            <span class="info-label">Diekspor oleh:</span>
            <span>{{ $exported_by }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Tanggal Ekspor:</span>
            <span>{{ $exported_at }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Total Data:</span>
            <span>{{ number_format($residents->count()) }} penghuni</span>
        </div>
        @if(!empty($filters))
        <div class="info-row">
            <span class="info-label">Filter:</span>
            <span>
                @if(isset($filters['gender']))
                    Jenis Kelamin: {{ $filters['gender'] == 'M' ? 'Laki-laki' : 'Perempuan' }}
                @endif
                @if(isset($filters['dorm']))
                    | Cabang: {{ $filters['dorm'] }}
                @endif
                @if(isset($filters['block']))
                    | Komplek: {{ $filters['block'] }}
                @endif
            </span>
        </div>
        @endif
    </div>

    @if($residents->count() > 0)
    <table>
        <thead>
            <tr>
                <th width="3%">No</th>
                <th width="12%">Nama Lengkap</th>
                <th width="10%">Email</th>
                <th width="8%">Kategori</th>
                <th width="5%">Gender</th>
                <th width="9%">NIK</th>
                <th width="7%">NIM</th>
                <th width="8%">No. HP</th>
                <th width="10%">Universitas</th>
                <th width="8%">Tempat Lahir</th>
                <th width="7%">Tgl. Lahir</th>
                <th width="7%">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($residents as $index => $resident)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $resident->residentProfile?->full_name ?? $resident->name ?? '-' }}</td>
                <td>{{ $resident->email ?? '-' }}</td>
                <td>{{ $resident->residentProfile?->residentCategory?->name ?? '-' }}</td>
                <td class="text-center">{{ $resident->residentProfile?->gender == 'M' ? 'L' : ($resident->residentProfile?->gender == 'F' ? 'P' : '-') }}</td>
                <td>{{ $resident->residentProfile?->national_id ?? '-' }}</td>
                <td>{{ $resident->residentProfile?->student_id ?? '-' }}</td>
                <td>{{ $resident->residentProfile?->phone_number ?? '-' }}</td>
                <td>{{ Str::limit($resident->residentProfile?->university_school ?? '-', 20) }}</td>
                <td>{{ $resident->residentProfile?->birth_place ?? '-' }}</td>
                <td class="text-center">
                    {{ $resident->residentProfile?->birth_date ? \Carbon\Carbon::parse($resident->residentProfile->birth_date)->format('d/m/Y') : '-' }}
                </td>
                <td>
                    @php
                        $status = $resident->residentProfile?->status;
                        $badgeClass = match($status) {
                            'active' => 'badge-success',
                            'registered' => 'badge-warning',
                            'inactive' => 'badge-danger',
                            default => 'badge-info'
                        };
                        $statusText = match($status) {
                            'active' => 'Aktif',
                            'registered' => 'Terdaftar',
                            'inactive' => 'Nonaktif',
                            default => '-'
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{ $statusText }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="no-data">
        Tidak ada data penghuni untuk ditampilkan
    </div>
    @endif

    <div class="footer">
        <p>Dicetak pada {{ \Carbon\Carbon::now()->format('d F Y H:i') }} WIB</p>
    </div>
</body>
</html>