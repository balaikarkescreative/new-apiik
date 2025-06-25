<!DOCTYPE html>
<html>
<head>
    <title>Pengajuan Izin Berhasil Dibuat</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .info-box { background-color: #f8f9fa; border-left: 4px solid #007bff; padding: 15px; margin: 20px 0; }
        .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        .btn { background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Pengajuan Izin Berhasil Dibuat</h1>
    </div>

    <div class="content">
        <p>Yth. {{ $izin->user->kategori . ' ' . $izin->user->name }},</p>

        <p>Pengajuan izin Anda telah berhasil dibuat dan masuk ke dalam sistem. Berikut adalah detail pengajuan:</p>

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
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $izin->tanggal_pengajuan->translatedFormat('d F Y') }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Status:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                        <span style="background-color: #ffc107; color: #856404; padding: 4px 8px; border-radius: 3px;">
                            Menunggu Verifikasi
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        <h3>Langkah Selanjutnya:</h3>
        <ol>
            <li>Pengajuan Anda akan diverifikasi oleh tim admin</li>
            <li>Jika dokumen lengkap, akan dilakukan penjadwalan visitasi</li>
            <li>Setelah visitasi selesai, izin akan diproses lebih lanjut</li>
            <li>Anda akan mendapat notifikasi untuk setiap perubahan status</li>
        </ol>


        <p>Jika ada pertanyaan, silakan hubungi admin.</p>

        <p>Terima kasih atas kepercayaan Anda.</p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Semua hak dilindungi.</p>
        <p>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</p>
    </div>
</body>
</html>
