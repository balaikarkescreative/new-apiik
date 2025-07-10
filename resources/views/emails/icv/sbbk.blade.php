{{-- filepath: /home/syhrl/Documents/new-apiik2/resources/views/emails/icv/sbbk.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>SBBK ICV Diterbitkan - Distribusi Selesai</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .content { padding: 20px; }
        .info-box {
            background-color: #d1fae5;
            border-left: 4px solid #059669;
            padding: 15px;
            margin: 20px 0;
        }
        .completion-box {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 3px solid #059669;
            border-radius: 15px;
            padding: 30px;
            margin: 25px 0;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        .btn {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
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
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 18px;
            display: inline-block;
            margin: 15px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        .next-steps {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .important {
            background-color: #dbeafe;
            border-left: 4px solid #3b82f6;
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
            background-color: #059669;
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
            0% { box-shadow: 0 0 0 0 rgba(5, 150, 105, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(5, 150, 105, 0); }
            100% { box-shadow: 0 0 0 0 rgba(5, 150, 105, 0); }
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
        .sbbk-info {
            background-color: #f0f9ff;
            border: 1px solid #0284c7;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .document-box {
            background-color: #1f2937;
            color: #f3f4f6;
            padding: 20px;
            border-radius: 10px;
            font-family: 'Arial', sans-serif;
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
            border: 2px solid #059669;
        }
        .success-message {
            background-color: #f0fdf4;
            border: 2px solid #22c55e;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="celebration">✅</div>
        <h1>SBBK ICV Telah Diterbitkan!</h1>
        <div class="status-badge">DISTRIBUSI SELESAI</div>
    </div>

    <div class="content">
        <p>Yth. {{ $icv->user->name }},</p>

        <p><strong>Selamat!</strong> Proses distribusi ICV Anda telah selesai. SBBK (Surat Bukti Barang Keluar) telah diterbitkan sebagai bukti penyerahan ICV.</p>

        <div class="completion-box">
            <h2 style="color: #059669; margin-top: 0;">🎉 DISTRIBUSI ICV SELESAI</h2>
            <p style="font-size: 18px; margin: 20px 0; font-weight: 500;">
                ICV telah berhasil diserahkan kepada<br>
                <strong>{{ $icv->user->name }}</strong>
            </p>

            <div class="document-box">
                📄 SBBK ICV Terlampir
            </div>

            <div style="background-color: white; border-radius: 10px; padding: 20px; margin: 20px 0;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 10px 0;">
                    <span style="color: #6b7280;">Jumlah ICV:</span>
                    <span style="font-weight: bold; color: #374151;">{{ number_format($icv->icvAlokasi->icv_disetujui) }} unit</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 10px 0;">
                    <span style="color: #6b7280;">Metode Distribusi:</span>
                    <span style="font-weight: bold; color: #059669; font-size: 20px;">{{ $icv->icvBuktiBayar->distribusi_icv }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 10px 0;">
                    <span style="color: #6b7280;">Tanggal Distribusi:</span>
                    <span style="font-weight: bold; color: #374151;">{{ $icv->icvJadwal->jadwal_pengambilan->translatedFormat('d F Y') }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 10px 0;">
                    <span style="color: #6b7280;">Tanggal SBBK:</span>
                    <span style="font-weight: bold; color: #374151;">{{ $icv->icvSbbk->date_updated->translatedFormat('d F Y') }}</span>
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
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Jumlah ICV Diterima:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ number_format($icv->icvAlokasi->icv_disetujui) }} unit</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Total Pembayaran:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">Rp {{ number_format($icv->icvBilling->nominal, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Metode Distribusi:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">e-ICV melalui aplikasi Sinkarkes</td>
                </tr>
            </table>
        </div>



        <div class="success-message">
            <h3 style="color: #16a34a; margin-top: 0;">🎉 Terima Kasih!</h3>
            <p style="margin: 10px 0;">
                Terima kasih telah menggunakan layanan pengajuan ICV kami.
                Semoga ICV yang telah Anda terima dapat bermanfaat untuk program vaksinasi di wilayah Anda.
            </p>
            <p style="color: #6b7280; font-style: italic; margin: 10px 0;">
                "Vaksinasi adalah investasi terbaik untuk kesehatan masyarakat"
            </p>
        </div>

        <h3>Kontak Informasi</h3>
        <div class="info-box">
            <p>Jika ada pertanyaan terkait SBBK atau penggunaan ICV:</p>
            <p>
                <strong>📞 Telepon:</strong> {{ config('app.contact_phone', '021-1234567') }}<br>
                <strong>📧 Email:</strong> {{ config('app.contact_email', 'admin@dinkes.go.id') }}<br>
                <strong>💬 WhatsApp:</strong> {{ config('app.whatsapp', '08123456789') }}<br>
                <strong>🌐 Website:</strong> {{ config('app.url') }}
            </p>
            <p style="color: #059669; font-weight: bold; margin-top: 15px;">
                💡 Tim kami siap membantu Anda 24/7
            </p>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.url') }}" class="btn">Login ke Sistem</a>
        </div>

        <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-radius: 10px; padding: 25px; margin: 30px 0; text-align: center;">
            <h3 style="color: #047857; margin-top: 0;">✅ Proses Pengajuan ICV Selesai! ✅</h3>
            <p style="font-size: 16px; margin: 15px 0;">
                Jumlah ICV: <strong style="color: #059669;">{{ number_format($icv->icvAlokasi->icv_disetujui) }} unit</strong>
            </p>
            <p style="color: #6b7280; font-style: italic;">
                "Selamat! ICV siap digunakan untuk program vaksinasi Anda"
            </p>
        </div>

        <p>Sekali lagi, terima kasih atas kepercayaan Anda dalam menggunakan layanan kami.</p>

        <br>
        <p>Hormat kami,<br>
        <strong>{{ config('app.name') }}</strong></p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Semua hak dilindungi.</p>
        <p>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</p>
        <p style="margin-top: 10px; color: #059669; font-weight: bold;">✅ SBBK Diterbitkan! Distribusi Selesai! 🎉</p>
    </div>
</body>
</html>
