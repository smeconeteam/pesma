<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Pembayaran - {{ $payment->payment_number }}</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .receipt-wrapper {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        #receipt {
            background: white;
            padding: 40px;
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #10b981;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 15px;
            background: #10b981;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: bold;
            border-radius: 50%;
        }

        .header h1 {
            font-size: 24px;
            color: #059669;
            margin-bottom: 5px;
        }

        .header .subtitle {
            color: #64748b;
            font-size: 14px;
        }

        .logo-img {
            width: 80px;
            height: 80px;
            margin: 0 auto 15px;
            display: block;
            object-fit: contain;
        }

        .title {
            text-align: center;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 15px;
            margin: -40px -40px 30px -40px;
            margin-top: 0;
        }

        .title h2 {
            font-size: 20px;
            font-weight: 600;
        }

        .section {
            margin-bottom: 25px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #059669;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #d1fae5;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #e5e7eb;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #64748b;
            font-size: 14px;
            flex: 0 0 40%;
        }

        .info-value {
            color: #1e293b;
            font-size: 14px;
            font-weight: 500;
            flex: 1;
            text-align: right;
        }

        .amount-highlight {
            background: #d1fae5;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }

        .amount-highlight .amount {
            font-size: 24px;
            font-weight: 700;
            color: #059669;
            font-family: 'Courier New', monospace;
        }

        .members-list {
            background: #f8fafc;
            padding: 15px;
            border-radius: 6px;
            margin-top: 10px;
        }

        .member-item {
            padding: 8px 0;
            display: flex;
            align-items: center;
        }

        .member-item:before {
            content: "•";
            color: #10b981;
            font-weight: bold;
            margin-right: 10px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-verified {
            background: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #64748b;
            font-size: 12px;
        }

        .action-buttons {
            text-align: center;
            padding: 20px;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin: 0 10px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #10b981;
            color: white;
        }

        .btn-primary:hover {
            background: #059669;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #1e293b;
        }

        .btn-secondary:hover {
            background: #d1d5db;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .container {
                max-width: 100%;
            }

            .receipt-wrapper {
                box-shadow: none;
                border-radius: 0;
            }

            .action-buttons {
                display: none;
            }

            #receipt {
                padding: 20px;
            }
        }

        /* Tablet */
        @media (max-width: 1024px) {
            .container {
                padding: 10px;
            }

            #receipt {
                padding: 30px 25px;
            }

            .title {
                margin: -30px -25px 25px -25px;
            }

            .section {
                padding: 15px;
            }
        }

        /* Mobile */
        @media (max-width: 768px) {
            body {
                padding: 2px;
                font-size: 11px;
            }

            .container {
                max-width: 100%;
            }

            .receipt-wrapper {
                margin-bottom: 10px;
            }

            #receipt {
                padding: 10px 8px;
            }

            .title {
                margin: -10px -8px 10px -8px;
                padding: 6px;
            }

            .title h2 {
                font-size: 13px;
            }

            .header {
                padding-bottom: 8px;
                margin-bottom: 10px;
                border-bottom-width: 2px;
            }

            .logo, .logo-img {
                width: 40px;
                height: 40px;
                margin-bottom: 5px;
                font-size: 16px;
            }

            .header h1 {
                font-size: 13px;
                margin-bottom: 2px;
            }

            .header .subtitle {
                font-size: 9px;
            }

            .section {
                margin-bottom: 10px;
                padding: 8px;
                border-radius: 4px;
            }

            .section-title {
                font-size: 11px;
                margin-bottom: 6px;
                padding-bottom: 4px;
                border-bottom-width: 1px;
            }

            .info-row {
                flex-direction: column;
                padding: 5px 0;
                border-bottom-width: 1px;
            }

            .info-label {
                margin-bottom: 2px;
                font-size: 9px;
                color: #6b7280;
            }

            .info-value {
                text-align: left;
                font-size: 10px;
                font-weight: 600;
                line-height: 1.3;
            }

            .amount-highlight {
                padding: 8px;
                margin: 8px 0;
            }

            .amount-highlight .amount {
                font-size: 15px;
            }

            .amount-highlight div:first-child {
                font-size: 9px !important;
            }

            .members-list {
                padding: 8px;
                margin-top: 6px;
            }

            .member-item {
                padding: 3px 0;
                font-size: 9px;
                line-height: 1.3;
            }

            .member-item:before {
                margin-right: 4px;
                font-size: 10px;
            }

            .status-badge {
                padding: 3px 6px;
                font-size: 9px;
            }

            .action-buttons {
                padding: 8px 5px;
            }

            .btn {
                display: block;
                width: 100%;
                margin: 5px 0;
                padding: 8px 12px;
                font-size: 11px;
                border-radius: 4px;
            }

            .footer {
                margin-top: 10px;
                padding-top: 8px;
                font-size: 8px;
                line-height: 1.4;
            }

            .footer p {
                margin-top: 2px !important;
            }
        }

        /* Small Mobile */
        @media (max-width: 480px) {
            body {
                padding: 1px;
            }

            #receipt {
                padding: 8px 6px;
            }

            .title {
                margin: -8px -6px 8px -6px;
                padding: 5px;
            }

            .title h2 {
                font-size: 12px;
            }

            .logo, .logo-img {
                width: 35px;
                height: 35px;
                font-size: 14px;
                margin-bottom: 4px;
            }

            .header h1 {
                font-size: 12px;
            }

            .header .subtitle {
                font-size: 8px;
            }

            .section {
                padding: 6px;
                margin-bottom: 8px;
                border-radius: 3px;
            }

            .section-title {
                font-size: 10px;
                margin-bottom: 5px;
                padding-bottom: 3px;
            }

            .info-row {
                padding: 4px 0;
            }

            .info-label {
                font-size: 8px;
            }

            .info-value {
                font-size: 9px;
            }

            .amount-highlight {
                padding: 6px;
                margin: 6px 0;
            }

            .amount-highlight .amount {
                font-size: 13px;
            }

            .amount-highlight div:first-child {
                font-size: 8px !important;
            }

            .members-list {
                padding: 6px;
            }

            .member-item {
                font-size: 8px;
                padding: 2px 0;
            }

            .btn {
                padding: 7px 10px;
                font-size: 10px;
                margin: 4px 0;
            }

            .footer {
                font-size: 7px;
                margin-top: 8px;
                padding-top: 6px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="receipt-wrapper">
            <div id="receipt">
                <!-- Header -->
                <div class="header">
                    @if($institution && $institution->logo_url)
                        <img src="{{ $institution->logo_url }}" alt="{{ $institution->institution_name ?? 'Logo' }}" class="logo-img">
                    @else
                        <div class="logo">P</div>
                    @endif
                    <h1>{{ $institution->institution_name ?? 'PPM elFIRA' }}</h1>
                    <div class="subtitle">{{ $institution->dormitory_name ?? 'Pondok Pesantren Modern elFIRA Purwokerto' }}</div>
                </div>

                <div class="title">
                    <h2>BUKTI PEMBAYARAN</h2>
                </div>

                <!-- Payment Information -->
                <div class="section">
                    <div class="section-title">Informasi Pembayaran</div>
                    <div class="info-row">
                        <span class="info-label">No. Pembayaran</span>
                        <span class="info-value">{{ $payment->payment_number }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tanggal Pembayaran</span>
                        <span class="info-value">{{ $payment->payment_date->format('d F Y') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="info-value">
                            <span class="status-badge status-{{ $payment->status }}">
                                {{ $payment->status_label }}
                            </span>
                        </span>
                    </div>
                    @if($payment->verified_at)
                    <div class="info-row">
                        <span class="info-label">Diverifikasi oleh</span>
                        <span class="info-value">{{ $payment->verifiedBy->name ?? '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tanggal Verifikasi</span>
                        <span class="info-value">{{ $payment->verified_at->format('d F Y H:i') }}</span>
                    </div>
                    @endif
                </div>

                <!-- Bill Details -->
                <div class="section">
                    <div class="section-title">Detail Tagihan</div>
                    <div class="info-row">
                        <span class="info-label">No. Tagihan</span>
                        <span class="info-value">{{ $payment->bill->bill_number }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Jenis Tagihan</span>
                        <span class="info-value">{{ $payment->bill->billingType->name ?? '-' }}</span>
                    </div>
                    @if($payment->bill->room)
                    <div class="info-row">
                        <span class="info-label">Kamar</span>
                        <span class="info-value">
                            {{ $payment->bill->room->name }} - 
                            {{ $payment->bill->room->block->name }} - 
                            {{ $payment->bill->room->block->dorm->name }}
                        </span>
                    </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">Jatuh Tempo</span>
                        <span class="info-value">{{ $payment->bill->due_date ? $payment->bill->due_date->format('d F Y') : '-' }}</span>
                    </div>
                </div>

                <!-- Payer Information -->
                <div class="section">
                    <div class="section-title">Dibayar Oleh</div>
                    <div class="info-row">
                        <span class="info-label">Nama</span>
                        <span class="info-value">{{ $payment->paid_by_name }}</span>
                    </div>
                    @if($payment->paidByUser)
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value">{{ $payment->paidByUser->email }}</span>
                    </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">Tipe Pembayaran</span>
                        <span class="info-value">{{ $payment->payment_type_label }}</span>
                    </div>

                    @if($payment->is_pic_payment && count($roomMembers) > 0)
                    <div style="margin-top: 15px;">
                        <strong style="color: #059669; font-size: 14px;">Anggota Kamar yang Ditanggung:</strong>
                        <div class="members-list">
                            @foreach($roomMembers as $resident)
                            <div class="member-item">
                                {{ $resident->user->name }}
                                @if($resident->user_id === $payment->paid_by_user_id)
                                <span style="color: #10b981; font-weight: 600; margin-left: 5px;">(PIC)</span>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($payment->is_pic_payment && count($picPaymentDetails) > 0)
                    <div style="margin-top: 15px;">
                        <strong style="color: #059669; font-size: 14px;">Rincian Alokasi PIC:</strong>
                        <div class="members-list">
                            @foreach($picPaymentDetails as $detail)
                            <div class="member-item">
                                {{ $detail['bill_number'] }}: Rp {{ $detail['amount'] }}
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Payment Amount -->
                <div class="section">
                    <div class="section-title">Rincian Pembayaran</div>
                    <div class="info-row">
                        <span class="info-label">Metode Pembayaran</span>
                        <span class="info-value">
                            @if($payment->paymentMethod)
                                {{ match($payment->paymentMethod->kind) {
                                    'qris' => 'QRIS',
                                    'transfer' => 'Transfer Bank',
                                    'cash' => 'Tunai',
                                    default => $payment->paymentMethod->kind
                                } }}
                            @else
                                -
                            @endif
                        </span>
                    </div>
                    @if($payment->bankAccount)
                    <div class="info-row">
                        <span class="info-label">Bank</span>
                        <span class="info-value">{{ $payment->bankAccount->bank_name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">No. Rekening</span>
                        <span class="info-value">{{ $payment->bankAccount->account_number }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Atas Nama</span>
                        <span class="info-value">{{ $payment->bankAccount->account_name }}</span>
                    </div>
                    @endif
                    
                    <div class="amount-highlight">
                        <div style="text-align: center;">
                            <div style="font-size: 14px; color: #64748b; margin-bottom: 5px;">Total Pembayaran</div>
                            <div class="amount">Rp {{ number_format($payment->amount, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="footer">
                    <p>Dokumen ini adalah bukti pembayaran resmi yang dihasilkan oleh sistem.</p>
                    <p style="margin-top: 5px;">Dicetak pada: {{ now()->format('d F Y H:i:s') }}</p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button onclick="window.print()" class="btn btn-secondary">
                Cetak / PDF
            </button>
            <button onclick="downloadReceipt()" class="btn btn-primary">
                Download PNG
            </button>
            <a href="{{ url()->previous() }}" class="btn btn-secondary">
                ← Kembali
            </a>
        </div>
    </div>

    <!-- HTML2Canvas Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <script>
        function downloadReceipt() {
            const receiptElement = document.getElementById('receipt');
            const button = event.target;
            
            // Disable button and show loading state
            button.disabled = true;
            button.textContent = '⏳ Mengunduh...';
            
            html2canvas(receiptElement, {
                scale: 2, // Higher quality
                backgroundColor: '#ffffff',
                logging: false,
                useCORS: true
            }).then(canvas => {
                // Create download link
                const link = document.createElement('a');
                link.download = 'bukti-pembayaran-{{ $payment->payment_number }}.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
                
                // Reset button
                button.disabled = false;
                button.textContent = '📥 Download PNG';
            }).catch(error => {
                console.error('Error generating PNG:', error);
                alert('Gagal mengunduh bukti pembayaran. Silakan coba lagi.');
                
                // Reset button
                button.disabled = false;
                button.textContent = '📥 Download PNG';
            });
        }
    </script>
</body>
</html>
