{{-- filepath: /home/syhrl/Documents/new-apiik2/resources/views/emails/icv/alokasi.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>ICV Berhasil Dialokasikan</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .content { padding: 20px; }
        .info-box {
            background-color: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 15px;
            margin: 20px 0;
        }
        .allocation-box {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 3px solid #10b981;
            border-radius: 15px;
            padding: 30px;
            margin: 25px 0;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        .btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            display: inline-block;
            margin: 15px 0;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        .status-badge {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
    </style>
</head>
<body>
    <div class="header">
        <div class="celebration">🎉</div>
        <h1>ICV Berhasil Dialokasikan!</h1>
        <div class="status-badge">ALOKASI BERHASIL</div>
    </div>

    <div class="content">
        <p>Yth. {{ $icv->user->name }},</p>

        <p><strong>Kabar gembira!</strong> Kami dengan senang hati menginformasikan bahwa pengajuan ICV Anda telah berhasil dialokasikan dan siap untuk tahap berikutnya.</p>

        <div class="allocation-box">
            <h2 style="color: #10b981; margin-top: 0;">✅ ALOKASI BERHASIL</h2>
            <p style="font-size: 18px; margin: 20px 0; font-weight: 500;">
                ICV telah dialokasikan untuk<br>
                <strong>{{ $icv->user->name }}</strong>
            </p>
            <div style="background-color: white; border-radius: 10px; padding: 20px; margin: 20px 0;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 10px 0;">
                    <span style="color: #6b7280;">Jumlah Diminta:</span>
                    <span style="font-weight: bold; color: #374151;">{{ number_format($icv->jumlah_icv) }} unit</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 10px 0;">
                    <span style="color: #6b7280;">Jumlah Dialokasikan:</span>
                    <span style="font-weight: bold; color: #10b981; font-size: 24px;">{{ number_format($icvDialokasikan) }} unit</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 10px 0;">
                    <span style="color: #6b7280;">Tanggal Alokasi:</span>
                    <span style="font-weight: bold; color: #374151;">{{ $icv->icvAlokasi->date_updated->format('d F Y') }}</span>
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
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Tanggal Verifikasi:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $icv->icvCekDokumen->date_updated->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Tanggal Alokasi:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $icv->icvAlokasi->date_updated->format('d/m/Y H:i') }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Stok Tersedia:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ number_format($icv->icvCekDokumen->stok_vaksin) }} unit</td>
                </tr>
            </table>
        </div>


        <h3>Kontak Informasi</h3>
        <div class="info-box">
            <p>Jika ada pertanyaan mengenai alokasi atau pembayaran:</p>
            <p>
                <strong>📞 Telepon:</strong> {{ config('app.contact_phone', '021-1234567') }}<br>
                <strong>📧 Email:</strong> {{ config('app.contact_email', 'admin@dinkes.go.id') }}<br>
                <strong>💬 WhatsApp:</strong> {{ config('app.whatsapp', '08123456789') }}<br>
                <strong>🌐 Website:</strong> {{ config('app.url') }}
            </p>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.url') }}" class="btn">Login ke Sistem</a>
        </div>

        <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-radius: 10px; padding: 25px; margin: 30px 0; text-align: center;">
            <h3 style="color: #059669; margin-top: 0;">🎊 Selamat! 🎊</h3>
            <p style="font-size: 16px; margin: 15px 0;">
                ICV Anda telah berhasil dialokasikan. Silakan tunggu informasi billing dan lakukan pembayaran tepat waktu.
            </p>
            <p style="color: #6b7280; font-style: italic;">
                "Vaksinasi adalah investasi terbaik untuk kesehatan masyarakat"
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
        <p style="margin-top: 10px; color: #10b981; font-weight: bold;">🎉 Alokasi Berhasil! Tunggu Info Billing! 🎉</p>
    </div>
</body>
</html>
