{{-- filepath: /home/syhrl/Documents/new-apiik2/resources/views/emails/icv/jadwal.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>Jadwal Distribusi ICV Ditetapkan</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header {
            background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .content { padding: 20px; }
        .info-box {
            background-color: #ede9fe;
            border-left: 4px solid #7c3aed;
            padding: 15px;
            margin: 20px 0;
        }
        .schedule-box {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border: 3px solid #7c3aed;
            border-radius: 15px;
            padding: 30px;
            margin: 25px 0;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        .btn {
            background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
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
            background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 18px;
            display: inline-block;
            margin: 15px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        .instructions {
            background-color: #f0f9ff;
            border-left: 4px solid #0284c7;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .important {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
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
        .schedule-date {
            background-color: #1f2937;
            color: #f3f4f6;
            padding: 20px;
            border-radius: 10px;
            font-family: 'Arial', sans-serif;
            font-size: 28px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
            border: 2px solid #7c3aed;
            text-transform: uppercase;
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
            background-color: #7c3aed;
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
            0% { box-shadow: 0 0 0 0 rgba(124, 58, 237, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(124, 58, 237, 0); }
            100% { box-shadow: 0 0 0 0 rgba(124, 58, 237, 0); }
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
        .location-info {
            background-color: #f0f9ff;
            border: 1px solid #0284c7;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .reminder-box {
            background-color: #fef2f2;
            border: 2px solid #ef4444;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="celebration">📅</div>
        <h1>Jadwal Distribusi ICV Ditetapkan!</h1>
        <div class="status-badge">SIAP UNTUK DISTRIBUSI</div>
    </div>

    <div class="content">
        <p>Yth. {{ $icv->user->name }},</p>

        <p><strong>Kabar gembira!</strong> Jadwal distribusi ICV Anda telah ditetapkan. Silakan perhatikan informasi jadwal dan instruksi di bawah ini.</p>

        <div class="schedule-box">
            <h2 style="color: #7c3aed; margin-top: 0;">📅 JADWAL DISTRIBUSI DITETAPKAN</h2>
            <p style="font-size: 18px; margin: 20px 0; font-weight: 500;">
                Jadwal {{ $icv->icvBuktiBayar->distribusi_icv }} ICV untuk<br>
                <strong>{{ $icv->user->name }}</strong>
            </p>

            <div class="schedule-date">
                {{ \Carbon\Carbon::parse($jadwalPengambilan)->translatedFormat('l, d F Y') }}
            </div>

            <div style="background-color: white; border-radius: 10px; padding: 20px; margin: 20px 0;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 10px 0;">
                    <span style="color: #6b7280;">Jumlah ICV:</span>
                    <span style="font-weight: bold; color: #374151;">{{ number_format($icv->icvAlokasi->icv_disetujui) }} unit</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 10px 0;">
                    <span style="color: #6b7280;">Metode Distribusi:</span>
                    <span style="font-weight: bold; color: #7c3aed; font-size: 20px;">{{ $icv->icvBuktiBayar->distribusi_icv }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 10px 0;">
                    <span style="color: #6b7280;">Tanggal Jadwal:</span>
                    <span style="font-weight: bold; color: #374151;">{{ \Carbon\Carbon::parse($jadwalPengambilan)->translatedFormat('d F Y') }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 10px 0;">
                    <span style="color: #6b7280;">Hari:</span>
                    <span style="font-weight: bold; color: #374151;">{{ \Carbon\Carbon::parse($jadwalPengambilan)->translatedFormat('l') }}</span>
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
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $icv->icvBilling->kode_billing }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Jumlah ICV:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ number_format($icv->icvAlokasi->icv_disetujui) }} unit</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Jadwal {{ $icv->icvBuktiBayar->distribusi_icv }}:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong style="color: #7c3aed;">{{ \Carbon\Carbon::parse($jadwalPengambilan)->translatedFormat('l, d F Y') }}</strong></td>
                </tr>
            </table>
        </div>



        <h3>Kontak Informasi</h3>
        <div class="info-box">
            <p>Jika ada pertanyaan mengenai jadwal distribusi:</p>
            <p>
                <strong>📞 Telepon:</strong> {{ config('app.contact_phone', '021-1234567') }}<br>
                <strong>📧 Email:</strong> {{ config('app.contact_email', 'admin@dinkes.go.id') }}<br>
                <strong>💬 WhatsApp:</strong> {{ config('app.whatsapp', '08123456789') }}<br>
                <strong>🌐 Website:</strong> {{ config('app.url') }}
            </p>
            <p style="color: #7c3aed; font-weight: bold; margin-top: 15px;">
                💡 Tim kami siap membantu Anda 24/7
            </p>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.url') }}" class="btn">Login ke Sistem</a>
        </div>

        <div style="background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); border-radius: 10px; padding: 25px; margin: 30px 0; text-align: center;">
            <h3 style="color: #5b21b6; margin-top: 0;">📅 Jadwal Distribusi Ditetapkan! 📅</h3>
            <div class="schedule-date" style="margin: 20px 0; font-size: 20px;">
                {{ \Carbon\Carbon::parse($jadwalPengambilan)->translatedFormat('l, d F Y') }}
            </div>
            <p style="font-size: 16px; margin: 15px 0;">
                Metode: <strong style="color: #7c3aed;">{{ $icv->icvBuktiBayar->distribusi_icv }}</strong>
            </p>
            <p style="color: #6b7280; font-style: italic;">
                "{{ $icv->icvBuktiBayar->distribusi_icv === 'Diambil' ? 'Jangan lupa datang tepat waktu!' : 'Pastikan ada yang menerima di alamat tujuan!' }}"
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
        <p style="margin-top: 10px; color: #7c3aed; font-weight: bold;">📅 Jadwal Ditetapkan! {{ $icv->icvBuktiBayar->distribusi_icv === 'Diambil' ? 'Siap Diambil!' : 'Siap Dikirim!' }} 📅</p>
    </div>
</body>
</html>
