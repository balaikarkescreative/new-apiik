<!-- resources/views/emails/account-activation.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun APIIK Aktif</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header { background-color: #28a745; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .info-box { background-color: #f8f9fa; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; }
        .credentials-box { background-color: #e9ecef; border: 1px solid #dee2e6; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .btn { background-color: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
        .warning-box { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
        .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎉 Selamat! Akun Anda Telah Aktif</h1>
    </div>

    <div class="content">
        <p>Yth. {{ $user->name }},</p>

        <p>Selamat! Akun APIIK Anda telah diaktivasi dan siap digunakan.</p>

        <div class="info-box">
            <h3>Detail Akun</h3>
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
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Status:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                        <span style="background-color: #28a745; color: white; padding: 4px 8px; border-radius: 3px;">
                            ✓ Aktif
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Tanggal Aktivasi:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $activationDate }}</td>
                </tr>
            </table>
        </div>

        <div class="credentials-box">
            <h3>🔑 Informasi Login</h3>
            <table style="width: 100%; margin-top: 10px;">
                <tr>
                    <td style="padding: 5px 0;"><strong>Username:</strong></td>
                    <td style="padding: 5px 0; font-family: monospace; background-color: #f8f9fa; padding: 8px; border-radius: 3px;">{{ $username }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>Password:</strong></td>
                    <td style="padding: 5px 0; font-family: monospace; background-color: #f8f9fa; padding: 8px; border-radius: 3px;">{{ $password }}</td>
                </tr>
            </table>
        </div>

        <div class="warning-box">
            <h3>⚠️ Penting untuk Keamanan</h3>
            <ul>
                <li><strong>Segera ubah password</strong> setelah login pertama kali</li>
                <li><strong>Jangan bagikan</strong> username dan password kepada orang lain</li>
                <li><strong>Logout</strong> setelah selesai menggunakan sistem</li>
                <li><strong>Gunakan password yang kuat</strong> dengan kombinasi huruf, angka, dan simbol</li>
            </ul>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $loginUrl }}" class="btn">🚀 Login Sekarang</a>
        </div>

        <div class="info-box">
            <h3>Fitur yang Tersedia</h3>
            <ul>
                <li>📝 Pengajuan Izin Operasional</li>
                <li>💉 Permintaan ICV</li>
                <li>📊 Monitoring Status Pengajuan</li>
                <li>📋 Riwayat Pengajuan</li>
            </ul>
        </div>

        <h3>Langkah Selanjutnya:</h3>
        <ol>
            <li>Klik tombol "Login Sekarang" atau buka {{ $loginUrl }}</li>
            <li>Masukkan username dan password yang telah diberikan</li>
            <li>Ubah password pada menu edit profil untuk keamanan</li>
            <li>Mulai menggunakan fitur-fitur yang tersedia</li>
        </ol>

        <p>Jika mengalami kesulitan atau ada pertanyaan, silakan hubungi admin melalui WhatsApp di {{ config('app.whatsapp') }}.</p>

        <p>Terima kasih atas kepercayaan Anda menggunakan APIIK.</p>

        <p>Salam,<br>Tim APIIK</p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Semua hak dilindungi.</p>
        <p>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</p>
    </div>
</body>
</html>
