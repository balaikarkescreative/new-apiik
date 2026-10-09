<!DOCTYPE html>
<html>
<head>
    <title>Penjadwalan Visitasi</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header {
            background-color: #3b82f6;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content { padding: 20px; }
        .info-box {
            background-color: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 20px 0;
        }
        .visitasi-box {
            background-color: #ecfdf5;
            border-left: 4px solid #10b981;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        .btn {
            background-color: #3b82f6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin: 15px 0;
            font-weight: bold;
        }
        .date-highlight {
            background-color: #10b981;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 18px;
            display: inline-block;
            margin: 10px 0;
        }
        .checklist {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
        }
        .important {
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📅 Penjadwalan Visitasi</h1>
        <p>Pengajuan Izin ID: {{ $izin->id }}</p>
    </div>

    <div class="content">
        <p>Yth. {{ $izin->user->kategori . ' ' . $izin->user->name }},</p>

        <p>Selamat! Dokumen pengajuan izin Anda telah dinyatakan lengkap dan telah dijadwalkan untuk visitasi.</p>

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
                @if ($izin->picDokter)
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>PIC Dokter Visitasi:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $izin->picDokter->name }}</td>
                </tr>
                @endif
            </table>
        </div>

        <div class="visitasi-box">
            <h2 style="color: #10b981; margin-top: 0;">🏥 Jadwal Visitasi</h2>
            <div style="text-align: center;">
                <div class="date-highlight">
                    {{ $izin->izinDokumenPks?->tanggal_visitasi?->format('l, d F Y') ?? 'Belum dijadwalkan' }}
                </div>
            </div>
        </div>

        {{-- <div class="checklist">
            <h3 style="color: #92400e; margin-top: 0;">📋 Persiapan Visitasi</h3>
            <p><strong>Pastikan hal-hal berikut tersedia saat visitasi:</strong></p>
            <ul style="margin-left: 20px;">
                <li>✅ Dokumen asli yang telah diajukan</li>
                <li>✅ Surat izin operasional yang masih berlaku</li>
                <li>✅ Dokumen kepemilikan/sewa tempat</li>
                <li>✅ Struktur organisasi dan daftar tenaga medis</li>
                <li>✅ Sertifikat dan izin praktik tenaga medis</li>
                <li>✅ Fasilitas dan peralatan medis sesuai standar</li>
                <li>✅ Dokumen kalibrasi alat medis</li>
                <li>✅ Standar operasional prosedur (SOP)</li>
            </ul>
        </div>

        <div class="important">
            <h3 style="color: #991b1b; margin-top: 0;">⚠️ Penting!</h3>
            <ul style="margin-left: 20px;">
                <li><strong>Kehadiran wajib:</strong> Penanggung jawab klinik/rumah sakit harus hadir</li>
                <li><strong>Reschedule:</strong> Jika ada halangan, hubungi kami minimal 2 hari sebelumnya</li>
                <li><strong>Durasi:</strong> Visitasi berlangsung sekitar 2-3 jam</li>
                <li><strong>Hasil:</strong> Hasil visitasi akan diinformasikan dalam 3-5 hari kerja</li>
            </ul>
        </div>

        <h3>Kontak Tim Visitasi</h3>
        <div class="info-box">
            <p>
                <strong>📞 Telepon:</strong> {{ config('app.contact_phone', '021-1234567') }}<br>
                <strong>📧 Email:</strong> {{ config('app.contact_email', 'visitasi@dinkes.go.id') }}<br>
                <strong>💬 WhatsApp:</strong> {{ config('app.whatsapp', '08123456789') }}
            </p>
        </div> --}}

        <h3>Langkah Selanjutnya</h3>
        <ol>
            <li>Siapkan semua dokumen dan fasilitas sesuai checklist</li>
            <li>Pastikan kehadiran penanggung jawab pada tanggal yang dijadwalkan</li>
            <li>Tim visitasi akan datang sesuai jadwal yang telah ditentukan</li>
            <li>Proses visitasi akan berlangsung 2-3 jam</li>
            <li>Hasil visitasi akan diinformasikan melalui email dan sistem</li>
            <li>Jika lolos visitasi, proses persetujuan akan dilanjutkan</li>
        </ol>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.url') }}" class="btn">Login ke Sistem</a>
        </div>

        <p>Jika ada pertanyaan mengenai visitasi ini, silakan hubungi tim kami melalui kontak di atas.</p>

        <p><strong>Terima kasih atas kerjasamanya.</strong></p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Semua hak dilindungi.</p>
        <p>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</p>
    </div>
</body>
</html>
