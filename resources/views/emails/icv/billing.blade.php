{{-- filepath: /home/syhrl/Documents/new-apiik2/resources/views/emails/icv/billing.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>Kode Billing ICV Diterbitkan</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .content { padding: 20px; }
        .info-box {
            background-color: #dbeafe;
            border-left: 4px solid #2563eb;
            padding: 15px;
            margin: 20px 0;
        }
        .billing-box {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 3px solid #2563eb;
            border-radius: 15px;
            padding: 30px;
            margin: 25px 0;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        .btn {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            display: inline-block;
            margin: 15px 0;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            font-size: 16px;
        }
        .status-badge {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 18px;
            display: inline-block;
            margin: 15px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        .payment-steps {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .important {
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .celebration {
            font-size: 64px;
            text-align: center;
            margin: 20px 0;
            animation: bounce 2s infinite;
        }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
        .billing-code {
            background-color: #1f2937;
            color: #f3f4f6;
            padding: 20px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 2px;
            margin: 20px 0;
            text-align: center;
            border: 2px solid #2563eb;
        }
        .warning {
            background-color: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .timeline {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .timeline-item {
            display: flex;
            align-items: center;
            margin: 10px 0;
            padding: 8px;
        }
        .timeline-icon {
            background-color: #10b981;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 12px;
        }
        .current-icon {
            background-color: #2563eb;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 12px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(37, 99, 235, 0); }
            100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
        }
        .pending-icon {
            background-color: #6b7280;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 12px;
        }
        .payment-methods {
            background-color: #f0f9ff;
            border: 1px solid #0ea5e9;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="celebration">💳</div>
        <h1>Kode Billing ICV Diterbitkan!</h1>
        <div class="status-badge">SILAKAN LAKUKAN PEMBAYARAN</div>
    </div>

    <div class="content">
        <p>Yth. {{ $icv->user->name }},</p>

        <p><strong>Kabar baik!</strong> Kode billing untuk pengajuan ICV Anda telah berhasil diterbitkan. Silakan lakukan pembayaran sesuai petunjuk di bawah ini.</p>

        <div class="billing-box">
            <h2 style="color: #2563eb; margin-top: 0;">💳 KODE BILLING DITERBITKAN</h2>
            <p style="font-size: 18px; margin: 20px 0; font-weight: 500;">
                Kode billing untuk<br>
                <strong>{{ $icv->user->name }}</strong>
            </p>

            <div class="billing-code">
                {{ $kodeBilling }}
            </div>

            <div style="background-color: white; border-radius: 10px; padding: 20px; margin: 20px 0;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 10px 0;">
                    <span style="color: #6b7280;">Jumlah ICV:</span>
                    <span style="font-weight: bold; color: #374151;">{{ number_format($icv->icvAlokasi->icv_disetujui) }} unit</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 10px 0;">
                    <span style="color: #6b7280;">Total Pembayaran:</span>
                    <span style="font-weight: bold; color: #2563eb; font-size: 28px;">Rp {{ number_format($nominal, 0, ',', '.') }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 10px 0;">
                    <span style="color: #6b7280;">Tanggal Billing:</span>
                    <span style="font-weight: bold; color: #374151;">{{ $icv->icvBilling->date_updated->format('d F Y') }}</span>
                </div>
            </div>
        </div>

        <div class="info-box">
            <h3>Detail Pengajuan ICV</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>ID Pengajuan:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $icv->id }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Nama Pemohon:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $icv->user->name }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Tanggal Pengajuan:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $icv->tanggal_pengajuan->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Kode Billing:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong style="color: #2563eb;">{{ $kodeBilling }}</strong></td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Nominal:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong style="color: #2563eb;">Rp {{ number_format($nominal, 0, ',', '.') }}</strong></td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Batas Pembayaran:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong style="color: #ef4444;">{{ $icv->icvBilling->date_updated->addDays(7)->format('d F Y') }}</strong></td>
                </tr>
            </table>
        </div>



        <div class="payment-steps">
            <h3 style="color: #92400e; margin-top: 0;">📋 Langkah Pembayaran</h3>
            <ol style="margin-left: 20px;">
                <li><strong>Catat Kode Billing:</strong> {{ $kodeBilling }}</li>
                <li><strong>Pilih Metode Pembayaran:</strong> Sesuai dengan preferensi Anda</li>
                <li><strong>Masukkan Kode Billing:</strong> Pada aplikasi/mesin pembayaran</li>
                <li><strong>Konfirmasi Nominal:</strong> Pastikan sesuai Rp {{ number_format($nominal, 0, ',', '.') }}</li>
                <li><strong>Selesaikan Pembayaran:</strong> Ikuti instruksi pada layar</li>
                <li><strong>Simpan Bukti Bayar:</strong> Screenshot atau cetak bukti transaksi</li>
                <li><strong>Upload Bukti Bayar:</strong> Login ke sistem dan upload bukti pembayaran</li>
            </ol>
        </div>


        <h3>Kontak Informasi</h3>
        <div class="info-box">
            <p>Jika ada pertanyaan mengenai pembayaran atau kode billing:</p>
            <p>
                <strong>📞 Telepon:</strong> {{ config('app.contact_phone', '021-1234567') }}<br>
                <strong>📧 Email:</strong> {{ config('app.contact_email', 'admin@dinkes.go.id') }}<br>
                <strong>💬 WhatsApp:</strong> {{ config('app.whatsapp', '08123456789') }}<br>
                <strong>🌐 Website:</strong> {{ config('app.url') }}
            </p>
            <p style="color: #2563eb; font-weight: bold; margin-top: 15px;">
                💡 Tim support siap membantu Anda 24/7
            </p>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.url') }}" class="btn">Login ke Sistem</a>
        </div>

        <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-radius: 10px; padding: 25px; margin: 30px 0; text-align: center;">
            <h3 style="color: #1d4ed8; margin-top: 0;">💳 Segera Lakukan Pembayaran! 💳</h3>
            <div class="billing-code" style="margin: 20px 0;">
                {{ $kodeBilling }}
            </div>
            <p style="font-size: 16px; margin: 15px 0;">
                Nominal: <strong style="color: #2563eb;">Rp {{ number_format($nominal, 0, ',', '.') }}</strong>
            </p>
            <p style="color: #6b7280; font-style: italic;">
                "Pembayaran tepat waktu mempercepat proses distribusi ICV"
            </p>
        </div>

        <p>Terima kasih atas kepercayaan Anda dalam menggunakan layanan kami.</p>

        <br>
        <p>Hormat kami,<br>
        <strong>{{ config('app.name') }}</strong></p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Semua hak dilindungi.</p>
        <p>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</p>
        <p style="margin-top: 10px; color: #2563eb; font-weight: bold;">💳 Billing Diterbitkan! Segera Bayar! 💳</p>
    </div>
</body>
</html>
