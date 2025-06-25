<!DOCTYPE html>
<html>
<head>
    <title>Hasil Verifikasi Dokumen ICV</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header {
            background-color: {{ $hasilVerifikasi == '1' ? '#10b981' : '#ef4444' }};
            color: white;
            padding: 25px;
            text-align: center;
        }
        .content { padding: 20px; }
        .info-box {
            background-color: {{ $hasilVerifikasi == '1' ? '#d1fae5' : '#fee2e2' }};
            border-left: 4px solid {{ $hasilVerifikasi == '1' ? '#10b981' : '#ef4444' }};
            padding: 15px;
            margin: 20px 0;
        }
        .result-box {
            background-color: {{ $hasilVerifikasi == '1' ? '#f0fdf4' : '#fef2f2' }};
            border: 2px solid {{ $hasilVerifikasi == '1' ? '#10b981' : '#ef4444' }};
            border-radius: 10px;
            padding: 25px;
            margin: 25px 0;
            text-align: center;
        }
        .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        .btn {
            background-color: {{ $hasilVerifikasi == '1' ? '#10b981' : '#ef4444' }};
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin: 15px 0;
            font-weight: bold;
        }
        .status-badge {
            background-color: {{ $hasilVerifikasi == '1' ? '#10b981' : '#ef4444' }};
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 18px;
            display: inline-block;
            margin: 10px 0;
        }
        .next-steps {
            background-color: {{ $hasilVerifikasi == '1' ? '#ecfdf5' : '#fef3c7' }};
            border-left: 4px solid {{ $hasilVerifikasi == '1' ? '#10b981' : '#f59e0b' }};
            padding: 20px;
            margin: 20px 0;
        }
        .important {
            background-color: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 20px 0;
        }
        .celebration {
            font-size: 48px;
            text-align: center;
            margin: 20px 0;
        }
        .warning {
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        @if($hasilVerifikasi == '1')
            <div class="celebration">✅</div>
            <h1>Selamat! Dokumen ICV Anda Lengkap</h1>
        @else
            <div class="celebration">⚠️</div>
            <h1>Dokumen ICV Perlu Diperbaiki</h1>
        @endif
        <div class="status-badge">
            {{ $hasilVerifikasi == '1' ? 'BERKAS LENGKAP' : 'BERKAS TIDAK LENGKAP' }}
        </div>
    </div>

    <div class="content">
        <p>Yth. {{ $icv->user->name }},</p>

        <p>Kami informasikan bahwa verifikasi dokumen untuk pengajuan ICV Anda telah selesai dilakukan.</p>

        <div class="result-box">
            @if($hasilVerifikasi == '1')
                <h2 style="color: #10b981; margin-top: 0;">🎉 Dokumen Dinyatakan Lengkap</h2>
                <p style="font-size: 16px; margin: 15px 0;">
                    Selamat! Dokumen pengajuan ICV Anda telah memenuhi persyaratan dan dinyatakan <strong>LENGKAP</strong>.
                </p>
            @else
                <h2 style="color: #ef4444; margin-top: 0;">📋 Dokumen Perlu Dilengkapi</h2>
                <p style="font-size: 16px; margin: 15px 0;">
                    Mohon maaf, dokumen pengajuan ICV Anda masih <strong>BELUM LENGKAP</strong> dan perlu diperbaiki.
                </p>
            @endif
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
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Jumlah ICV Diminta:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ number_format($icv->jumlah_icv) }} unit</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Tanggal Verifikasi:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $icv->icvCekDokumen->date_updated->format('d/m/Y H:i') }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Status Verifikasi:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                        <span class="status-badge" style="font-size: 12px; padding: 4px 8px;">
                            {{ $hasilVerifikasi == '1' ? 'Berkas Lengkap' : 'Berkas Tidak Lengkap' }}
                        </span>
                    </td>
                </tr>
                @if($hasilVerifikasi == '1' && $icv->icvCekDokumen->stok_vaksin)
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Stok Vaksin Tersedia:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ number_format($icv->icvCekDokumen->stok_vaksin) }} unit</td>
                </tr>
                @endif
            </table>
        </div>

        @if($hasilVerifikasi == '1')
            <div class="next-steps">
                <h3 style="color: #059669; margin-top: 0;">📋 Langkah Selanjutnya</h3>
                <p><strong>Proses pengajuan ICV Anda akan dilanjutkan dengan tahap berikutnya:</strong></p>
                <ol style="margin-left: 20px;">
                    <li><strong>Alokasi ICV</strong>
                        <ul style="margin-left: 20px; margin-top: 5px;">
                            <li>Tim kami akan menentukan jumlah ICV yang dapat dialokasikan</li>
                            <li>Alokasi berdasarkan stok tersedia dan kebutuhan</li>
                        </ul>
                    </li>
                    <li><strong>Penerbitan Billing</strong>
                        <ul style="margin-left: 20px; margin-top: 5px;">
                            <li>Kode billing akan diterbitkan untuk pembayaran</li>
                            <li>Anda akan menerima informasi nominal dan cara pembayaran</li>
                        </ul>
                    </li>
                    <li><strong>Pembayaran dan Verifikasi</strong></li>
                    <li><strong>Penjadwalan Distribusi ICV</strong></li>
                </ol>
                <p style="color: #059669; font-weight: bold; margin-top: 15px;">
                    ✨ Anda akan mendapat notifikasi email untuk setiap tahap selanjutnya.
                </p>
            </div>
        @else
            @if($catatan)
                <div class="warning">
                    <h3 style="color: #991b1b; margin-top: 0;">📝 Catatan Verifikator</h3>
                    <div style="background-color: white; padding: 15px; border-radius: 5px; border-left: 3px solid #ef4444;">
                        <p style="margin: 0; color: #991b1b; font-weight: 500;">{{ $catatan }}</p>
                    </div>
                </div>
            @endif

            <div class="next-steps">
                <h3 style="color: #92400e; margin-top: 0;">🔧 Yang Perlu Anda Lakukan</h3>
                <ol style="margin-left: 20px;">
                    <li><strong>Periksa Catatan:</strong> Baca dengan teliti catatan verifikator di atas</li>
                    <li><strong>Perbaiki Dokumen:</strong> Lengkapi atau perbaiki dokumen sesuai catatan</li>
                    <li><strong>Upload Ulang:</strong> Login ke sistem dan upload dokumen yang sudah diperbaiki</li>
                    <li><strong>Tunggu Verifikasi:</strong> Tim kami akan memverifikasi ulang dokumen Anda</li>
                </ol>
                <p style="color: #92400e; font-weight: bold; margin-top: 15px;">
                    💡 Tip: Pastikan semua dokumen sesuai dengan persyaratan yang berlaku.
                </p>
            </div>
            @endif

        <h3>Kontak Informasi</h3>
        <div class="info-box">
            <p>Jika ada pertanyaan mengenai verifikasi dokumen:</p>
            <p>
                <strong>📞 Telepon:</strong> {{ config('app.contact_phone', '021-1234567') }}<br>
                <strong>📧 Email:</strong> {{ config('app.contact_email', 'admin@dinkes.go.id') }}<br>
                <strong>💬 WhatsApp:</strong> {{ config('app.whatsapp', '08123456789') }}
            </p>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.url') }}" class="btn">Login ke Sistem</a>
        </div>

        <p>Terima kasih atas kepercayaan Anda dalam menggunakan layanan kami.</p>

        <br>
        <p>Hormat kami,<br>
        <strong>{{ config('app.name') }}</strong></p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Semua hak dilindungi.</p>
        <p>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</p>
        @if($hasilVerifikasi == '1')
            <p style="margin-top: 10px; color: #10b981; font-weight: bold;">🎉 Selamat! Lanjutkan ke tahap berikutnya! 🎉</p>
        @else
            <p style="margin-top: 10px; color: #ef4444; font-weight: bold;">📋 Perbaiki dokumen dan coba lagi! 💪</p>
        @endif
    </div>
</body>
</html>
