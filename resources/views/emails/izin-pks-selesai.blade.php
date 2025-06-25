<!DOCTYPE html>
<html>
<head>
    <title>Dokumen PKS Selesai</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .content { padding: 20px; }
        .info-box {
            background-color: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 15px;
            margin: 20px 0;
        }
        .completion-box {
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
        .operational-info {
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
    </style>
</head>
<body>
    <div class="header">
        <div class="celebration">🎉</div>
        <h1>Selamat! Proses Izin Telah Selesai</h1>
        {{-- <div class="status-badge">SIAP BEROPERASI</div> --}}
    </div>

    <div class="content">
        <p>Yth. {{ $izin->user->name }},</p>

        <p><strong>Selamat!</strong> Kami dengan bangga menginformasikan bahwa seluruh proses pengajuan izin Anda telah selesai dan Perjanjian Kerjasama (PKS) telah ditandatangani kedua belah pihak.</p>

        <div class="completion-box">
            <h2 style="color: #10b981; margin-top: 0;">✅ PROSES SELESAI</h2>
            <p style="font-size: 18px; margin: 20px 0; font-weight: 500;">
                <strong>{{ $izin->user->name }}</strong><br>
                Resmi mendapat izin
            </p>
            <div style="background-color: white; border-radius: 10px; padding: 20px; margin: 20px 0;">
                <h3 style="color: #059669; margin: 0;">ID Izin: {{ $izin->id }}</h3>
                <p style="margin: 5px 0; color: #6b7280;">{{ $izin->kategori_izin }}</p>
                <p style="margin: 5px 0; color: #6b7280;">PKS Ditandatangani: {{ now()->format('d F Y') }}</p>
            </div>
        </div>

        <div class="info-box">
            <h3>Ringkasan Proses</h3>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-icon">✓</div>
                    <div>
                        <strong>Pengajuan:</strong> {{ $izin->tanggal_pengajuan->format('d/m/Y') }}
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-icon">✓</div>
                    <div>
                        <strong>Verifikasi Dokumen:</strong> {{ $izin->izinDokumen?->date_updated?->format('d/m/Y') ?? 'Belum diverifikasi' }}
                        - Berkas Lengkap
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-icon">✓</div>
                    <div>
                        <strong>Visitasi:</strong> {{ $izin->izinDokumenPks?->tanggal_visitasi?->format('d/m/Y') ?? 'Belum dijadwalkan' }}
                        - Lulus Visitasi
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-icon">✓</div>
                    <div>
                        <strong>Persetujuan:</strong> {{ $izin->izinPersetujuan?->date_updated?->format('d/m/Y') ?? 'Belum disetujui' }}
                        - Izin Disetujui
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-icon">✓</div>
                    <div>
                        <strong>PKS Selesai:</strong> {{ now()->format('d/m/Y') }}
                        - Perjanjian Kerjasama Ditandatangani
                    </div>
                </div>
            </div>
        </div>

        {{-- <div class="important">
            <h3 style="color: #1e40af; margin-top: 0;">📋 Kewajiban dan Ketentuan</h3>
            <p><strong>Sebagai pemegang izin, Anda wajib:</strong></p>
            <ul style="margin-left: 20px;">
                <li><strong>Melaporkan Kegiatan:</strong> Sesuai dengan jadwal pelaporan yang ditetapkan</li>
                <li><strong>Menjaga Standar:</strong> Mempertahankan standar pelayanan dan fasilitas</li>
                <li><strong>Update Dokumen:</strong> Memperbarui dokumen yang akan kadaluarsa</li>
                <li><strong>Monitoring:</strong> Bekerjasama dalam proses monitoring berkala</li>
                <li><strong>Kepatuhan:</strong> Mematuhi semua ketentuan dalam PKS</li>
            </ul>
        </div> --}}

        <div class="important">
            <h3 style="color: #1e40af; margin-top: 0;">📎 Dokumen Terlampir</h3>
            <p>Email ini dilengkapi dengan dokumen berikut:</p>
            <ul style="margin-left: 20px;">
                <li>📄 <strong>Perjanjian Kerjasama (PKS)</li>
                <li>💾 Simpan dokumen ini sebagai arsip resmi</li>
                <li>🔐 Dokumen ini adalah bukti sah izin operasional Anda</li>
            </ul>
        </div>

        {{-- <h3>Jadwal Pelaporan</h3>
        <div class="info-box">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Laporan Bulanan:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">Setiap tanggal 5 bulan berikutnya</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Laporan Triwulan:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">Setiap 3 bulan sekali</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Laporan Tahunan:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">Setiap akhir tahun</td>
                </tr>
            </table>
        </div> --}}

        <h3>Kontak Informasi</h3>
        <div class="info-box">
            <p>Untuk pertanyaan operasional atau pelaporan:</p>
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

        <br>
        <p>Terima kasih atas dedikasi Anda dalam memberikan pelayanan kesehatan yang berkualitas.</p>

        <br>
        <p>Hormat kami,<br>
        <strong>APIIK BKK Kelas I Bandung</strong><br>

    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Semua hak dilindungi.</p>
        <p>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</p>
        <p style="margin-top: 10px; color: #10b981; font-weight: bold;">🏥 Selamat Beroperasi & Sukses Selalu! 🏥</p>
    </div>
</body>
</html>
