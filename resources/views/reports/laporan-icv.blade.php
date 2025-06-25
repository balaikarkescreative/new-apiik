<!DOCTYPE html>
<html>
<head>
    <title>Laporan Pengajuan ICV</title>
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
        <h2>Laporan Pengajuan ICV</h2>
        <p>Periode: {{ $tanggal_mulai }} s/d {{ $tanggal_selesai }}</p>
    </div>

    <div class="filter-info">
        <p><strong>Filter:</strong></p>
        <p>Progress Status: {{ $progress_status === 'semua' ? 'Semua' : str_replace('_', ' ', ucwords($progress_status)) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tanggal</th>
                <th>Pemohon</th>
                <th>Jumlah ICV</th>
                <th>Status</th>
                <th>Progress Status</th>
                <th>Kota/Kab</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
            @php
                $status = match ($row->status) {
                    0 => 'Pending',
                    1 => 'Disetujui',
                    2 => 'Ditolak',
                    default => 'Tidak Diketahui',
                };

                $progressStatus = '';
                if ($row->status === 2) {
                    $progressStatus = "Ditolak";
                } elseif ($row->icvSbbk && $row->icvSbbk->sinkarkes === "Sudah") {
                    $progressStatus = "Selesai";
                } elseif ($row->icvJadwal && $row->icvJadwal->status === 1) {
                    $progressStatus = "ICV Sudah Diambil";
                } elseif ($row->icvAlokasi) {
                    $progressStatus = "ICV Dialokasikan";
                } elseif ($row->icvCekDokumen) {
                    $progressStatus = $row->icvCekDokumen->hasil_verif;
                } else {
                    $progressStatus = "Menunggu Persetujuan";
                }
            @endphp
            <tr>
                <td>{{ $row->id }}</td>
                <td>{{ $row->tanggal_pengajuan }}</td>
                <td>{{ $row->user->name }}</td>
                <td>{{ $row->jumlah_icv }}</td>
                <td>{{ $status }}</td>
                <td>{{ $progressStatus }}</td>
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
