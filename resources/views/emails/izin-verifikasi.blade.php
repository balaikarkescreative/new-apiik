<!DOCTYPE html>
<html>
<head>
    <title>Hasil Verifikasi Dokumen Izin</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header {
            background-color: {{ $hasilVerifikasi == 1 ? '#10b981' : '#ef4444' }};
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content { padding: 20px; }
        .info-box {
            background-color: {{ $hasilVerifikasi == 1 ? '#d1fae5' : '#fee2e2' }};
            border-left: 4px solid {{ $hasilVerifikasi == 1 ? '#10b981' : '#ef4444' }};
            padding: 15px;
            margin: 20px 0;
        }
        .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        .btn {
            background-color: {{ $hasilVerifikasi == 1 ? '#10b981' : '#ef4444' }};
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin: 10px 0;
        }
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            color: white;
            background-color: {{ $hasilVerifikasi == 1 ? '#10b981' : '#ef4444' }};
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Hasil Verifikasi Dokumen Izin</h1>
        <div class="status-badge">
            {{ $hasilVerifikasi == 1 ? 'BERKAS LENGKAP' : 'BERKAS TIDAK LENGKAP' }}
        </div>
    </div>

    <div class="content">
        <p>Yth. {{ $izin->user->name }},</p>

        <p>Kami informasikan bahwa verifikasi dokumen untuk pengajuan izin Anda telah selesai dilakukan. Berikut adalah detail hasil verifikasi:</p>

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
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $izin->izinDokumen->date_updated->format('d/m/Y H:i') }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Status Verifikasi:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                        <span class="status-badge" style="font-size: 12px; padding: 4px 8px;">
                            {{ $hasilVerifikasi == 1 ? 'Berkas Lengkap' : 'Berkas Tidak Lengkap' }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        @if($hasilVerifikasi == 1)
            <h3>✅ Selamat! Berkas Anda Lengkap</h3>
            <p>Dokumen yang Anda ajukan telah memenuhi persyaratan dan dinyatakan lengkap.</p>

            <h4>Langkah Selanjutnya:</h4>
            <ol>
                <li>Tim kami akan melakukan penjadwalan visitasi ke lokasi Anda</li>
                <li>Anda akan mendapat notifikasi mengenai jadwal visitasi</li>
                <li>Setelah visitasi selesai, proses persetujuan izin akan dilanjutkan</li>
                <li>Jika disetujui, Anda akan menerima surat persetujuan</li>
            </ol>
        @else
            <h3>❌ Berkas Perlu Diperbaiki</h3>
            <p>Maaf, dokumen yang Anda ajukan masih belum lengkap atau perlu diperbaiki.</p>

            @if($catatan)
                <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 15px 0;">
                    <h4 style="color: #92400e; margin-top: 0;">Catatan dari Verifikator:</h4>
                    <p style="color: #92400e; margin-bottom: 0;">{{ $catatan }}</p>
                </div>
            @endif

            <h4>Yang Perlu Anda Lakukan:</h4>
            <ol>
                <li>Periksa kembali dokumen sesuai catatan di atas</li>
                <li>Lengkapi atau perbaiki dokumen yang kurang</li>
                <li>Login ke sistem untuk mengupload dokumen yang diperbaiki</li>
                <li>Tunggu proses verifikasi ulang</li>
            </ol>
        @endif

        <a href="{{ config('app.url') }}" class="btn">Login ke Sistem</a>

        <p>Jika ada pertanyaan mengenai hasil verifikasi ini, silakan hubungi admin sistem atau customer service kami.</p>

        <p>Terima kasih atas kepercayaan Anda.</p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Semua hak dilindungi.</p>
        <p>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</p>
    </div>
</body>
</html>
