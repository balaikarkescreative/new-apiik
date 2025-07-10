<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Akun Berhasil Dibuat</title>
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
        <h1>Pengajuan Akun Berhasil Dibuat</h1>
    </div>

    <div class="content">
        <p>Yth. {{ $user->name }},</p>

        <p>Pengajuan akun Anda telah berhasil dibuat dan masuk ke dalam sistem. Berikut adalah detail pengajuan:</p>

        <div class="info-box">
            <h3>Detail Pengajuan Akun</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Nama Faskes:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $user->name }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Kategori:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $user->kategori }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Kota/Kabupaten:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $user->kotakab }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Email:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $user->email }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>No. WhatsApp:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $user->kontak_pj }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Nomor Surat Izin:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $user->nomor_surat_izin_operasional }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Masa Berlaku Izin:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ \Carbon\Carbon::parse($user->masa_berlaku_surat_izin_operasional)->format('d F Y') }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Tanggal Pengajuan:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $registrationDate }}</td>
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
            <li>Tim verifikasi akan meninjau pengajuan Anda</li>
            <li>Anda akan dihubungi melalui WhatsApp untuk konfirmasi</li>
            <li>Setelah disetujui, Anda akan menerima username dan password</li>
            <li>Akun Anda akan diaktivasi dan dapat digunakan</li>
        </ol>

        <div class="info-box">
            <h3>Informasi Penting</h3>
            <ul>
                {{-- <li>Proses verifikasi memerlukan waktu 1-3 hari kerja</li> --}}
                <li>Pastikan nomor WhatsApp yang Anda berikan aktif</li>
                <li>Jika ada pertanyaan, silakan hubungi tim support</li>
            </ul>
        </div>

        <p>Jika ada pertanyaan, silakan hubungi admin.</p>

        <p>Terima kasih atas kepercayaan Anda menggunakan APIIK.</p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Semua hak dilindungi.</p>
        <p>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</p>
    </div>
</body>
</html>
