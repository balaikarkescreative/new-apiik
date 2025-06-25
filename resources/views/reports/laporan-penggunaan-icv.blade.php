<!DOCTYPE html>
<html>
<head>
    <title>Laporan Penggunaan ICV</title>
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
        <h2>Laporan Penggunaan ICV</h2>
        <p>Periode: {{ $tanggal_mulai }} s/d {{ $tanggal_selesai }}</p>
    </div>

    <div class="filter-info">
        <p><strong>Filter:</strong></p>
        <p>User: {{ $user_id === 'semua' ? 'Semua User' : \App\Models\User::find($user_id)?->name ?? 'Tidak Diketahui' }}</p>
        <p>Jenis Vaksin: {{ $jenis_vaksin === 'semua' ? 'Semua' : \App\Models\Vaksin::find($jenis_vaksin)?->jenis_vaksin ?? 'Tidak Diketahui' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>User</th>
                <th>Jenis Vaksin</th>
                <th>Terbit ICV</th>
                <th>Guna Vaksin</th>
                <th>Sisa ICV</th>
                <th>Sisa Vaksin</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
            <tr>
                <td>{{ $row->tgl_pelaporan }}</td>
                <td>{{ $row->user->name }}</td>
                <td>{{ $row->jenisVaksin->jenis_vaksin }}</td>
                <td>{{ $row->jml_terbit_icv }}</td>
                <td>{{ $row->jml_guna_vaksin }}</td>
                <td>{{ $row->sisa_icv }}</td>
                <td>{{ $row->sisa_vaksin }}</td>
                <td>{{ $row->keterangan }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px; font-size: 12px;">
        <p>Total Data: {{ $data->count() }} laporan</p>
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
