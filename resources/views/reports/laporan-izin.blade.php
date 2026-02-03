<!DOCTYPE html>
<html>
<head>
    <title>Laporan Pengajuan Izin</title>
    <style>
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .header { text-align: center; margin-bottom: 20px; }
        .filter-info { margin-bottom: 15px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Pengajuan Izin</h2>
        <p>Periode: {{ $tanggal_mulai }} s/d {{ $tanggal_selesai }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Pemohon</th>
                <th>Kategori</th>
                <th>No. Surat</th>
                <th>Tgl Pengajuan</th>
                <th>Status Progress</th>
                <th>Kota/Kab</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
            @php
                $statusProgress = '';
                if ($row->izinPersetujuan && $row->izinPersetujuan->file_persetujuan) {
                    $statusProgress = "Selesai";
                } elseif ($row->izinDokumenPks && $row->izinDokumenPks->dokumen_pks_bkk) {
                    $statusProgress = "PKS Tersedia - Menunggu Persetujuan";
                } elseif ($row->izinVisitasi && $row->izinVisitasi->tanggal_visitasi) {
                    $statusProgress = "Visitasi Dijadwalkan";
                } elseif ($row->izinDokumen) {
                    switch ($row->izinDokumen->hasil) {
                        case 1: $statusProgress = "Berkas Lengkap"; break;
                        case 2: $statusProgress = "Berkas Tidak Lengkap"; break;
                        default: $statusProgress = "Menunggu Verifikasi"; break;
                    }
                } else {
                    $statusProgress = "Dokumen Diajukan";
                }
            @endphp
            <tr>
                <td>{{ $row->id }}</td>
                <td>{{ $row->user->name }}</td>
                <td>{{ $row->kategori_izin }}</td>
                <td>{{ $row->nomor_surat }}</td>
                <td>{{ $row->tanggal_pengajuan }}</td>
                <td>{{ $statusProgress }}</td>
                <td>{{ $row->user->kotakab ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px; font-size: 12px;">
        <p>Total Data: {{ $data->count() }} pengajuan</p>
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
