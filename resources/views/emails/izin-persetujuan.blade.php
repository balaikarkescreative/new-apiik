<!DOCTYPE html>
<html>
<head>
    <title>Izin Disetujui</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header {
            background-color: #10b981;
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
        .approval-box {
            background-color: #f0fdf4;
            border: 2px solid #10b981;
            border-radius: 10px;
            padding: 25px;
            margin: 25px 0;
            text-align: center;
        }
        .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        .btn {
            background-color: #10b981;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin: 15px 0;
            font-weight: bold;
        }
        .status-badge {
            background-color: #10b981;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 18px;
            display: inline-block;
            margin: 10px 0;
        }
        .next-steps {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
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
    </style>
</head>
<body>
    <div class="header">
        <div class="celebration">🎉</div>
        <h1>Selamat! Izin Anda Telah Disetujui</h1>
        <div class="status-badge">DISETUJUI</div>
    </div>

    <div class="content">
        <p>Yth. {{ $izin->user->kategori . ' ' . $izin->user->name }},</p>

        <p><strong>Selamat!</strong> Kami dengan senang hati menginformasikan bahwa pengajuan izin Anda telah disetujui dan dapat dilanjutkan ke tahap berikutnya.</p>

        <div class="approval-box">
            <h2 style="color: #10b981; margin-top: 0;">✅ IZIN DISETUJUI</h2>
            <p style="font-size: 16px; margin: 15px 0;">
                Pengajuan izin dengan ID <strong>{{ $izin->id }}</strong> telah melalui seluruh proses verifikasi dan visitasi, serta dinyatakan <strong>DISETUJUI</strong> pada tanggal {{ $izin->izinPersetujuan?->date_updated?->format('d F Y') ?? now()->format('d F Y') }}.
            </p>
        </div>

        <div class="info-box">
            <h3>Detail Pengajuan Izin</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>ID Pengajuan:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $izin->id }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Kategori Izin:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $izin->kategori_izin }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Nomor Surat:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $izin->nomor_surat }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Tanggal Pengajuan:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $izin->tanggal_pengajuan->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Tanggal Verifikasi:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $izin->izinDokumen?->date_updated?->format('d/m/Y') ?? 'Belum diverifikasi' }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Tanggal Visitasi:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $izin->izinDokumenPks?->tanggal_visitasi?->format('d/m/Y') ?? 'Belum dijadwalkan' }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Tanggal Persetujuan:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $izin->izinPersetujuan?->date_updated?->format('d/m/Y') ?? now()->format('d/m/Y') }}</td>
                </tr>
            </table>
        </div>

        {{-- <div class="next-steps">
            <h3 style="color: #92400e; margin-top: 0;">📋 Langkah Selanjutnya</h3>
            <p><strong>Untuk menyelesaikan seluruh proses, Anda perlu:</strong></p>
            <ol style="margin-left: 20px;">
                <li><strong>Menandatangani Perjanjian Kerjasama (PKS)</strong>
                    <ul style="margin-left: 20px; margin-top: 5px;">
                        <li>PKS akan dikirimkan dalam 2-3 hari kerja</li>
                        <li>Silakan tanda tangani dan stempel PKS tersebut</li>
                        <li>Upload file PKS yang sudah ditandatangani melalui sistem</li>
                    </ul>
                </li>
                <li><strong>Menunggu finalisasi dari pihak Dinas Kesehatan</strong></li>
                <li><strong>Setelah PKS selesai, izin resmi dapat mulai beroperasi</strong></li>
            </ol>
        </div> --}}

        <div class="important">
            <h3 style="color: #1e40af; margin-top: 0;">📎 Dokumen Terlampir</h3>
            <p>Email ini dilengkapi dengan dokumen berikut:</p>
            <ul style="margin-left: 20px;">
                <li>🔖 <strong>Surat Persetujuan Resmi</strong> (PDF)</li>
                <li>📋 Dokumen ini sebagai bukti bahwa izin Anda telah disetujui</li>
                <li>💾 Simpan dokumen ini sebagai arsip</li>
            </ul>
        </div>

        <h3>Kontak Informasi</h3>
        <div class="info-box">
            <p>Jika ada pertanyaan mengenai proses selanjutnya:</p>
            <p>
                <strong>📞 Telepon:</strong> {{ config('app.contact_phone', '021-1234567') }}<br>
                <strong>📧 Email:</strong> {{ config('app.contact_email', 'admin@dinkes.go.id') }}<br>
                <strong>💬 WhatsApp:</strong> {{ config('app.whatsapp', '08123456789') }}
            </p>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.url') }}" class="btn">Login ke Sistem</a>
        </div>

        <p><strong>Sekali lagi, selamat atas persetujuan izin Anda!</strong></p>

        <p>Terima kasih atas kesabaran dan kerjasamanya selama proses pengajuan izin.</p>

        <br>
        <p>Hormat kami,<br>
        <strong>APIIK BKK Kelas I Bandung</strong><br>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Semua hak dilindungi.</p>
        <p>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</p>
    </div>
</body>
</html>
