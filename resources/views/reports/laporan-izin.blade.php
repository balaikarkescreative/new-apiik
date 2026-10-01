<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pengajuan Izin (Ringkas)</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 10mm 15mm 10mm;
        }
        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }
        body {
            font-size: 9px;
            color: #000000;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }

        /* Header */
        .report-header {
            text-align: center;
            border-bottom: 2px solid #000000;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .report-header h1 {
            font-size: 13px;
            color: #000000;
            margin: 0 0 2px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .report-header h2 {
            font-size: 11px;
            color: #000000;
            margin: 0 0 2px 0;
            font-weight: bold;
        }
        .report-header p {
            font-size: 9px;
            color: #333333;
            margin: 0;
        }

        /* Filter Box */
        .filter-box {
            background-color: #ffffff;
            border: 1px solid #000000;
            padding: 5px 8px;
            margin-bottom: 10px;
        }
        .filter-table {
            width: 100%;
            border-collapse: collapse;
        }
        .filter-table td {
            font-size: 8.5px;
            padding: 1.5px 3px;
            vertical-align: top;
            color: #000000;
        }
        .filter-label {
            color: #000000;
            width: 13%;
            font-weight: bold;
        }
        .filter-val {
            color: #000000;
            width: 37%;
        }

        /* Main Data Table */
        .table-data {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            font-size: 8.5px;
        }
        .table-data th {
            background-color: #f2f2f2;
            color: #000000;
            font-weight: bold;
            padding: 5px 4px;
            border: 1px solid #000000;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
            word-break: break-all;
        }
        .table-data td {
            border: 1px solid #000000;
            padding: 4px 5px;
            vertical-align: middle;
            color: #000000;
            word-wrap: break-word;
            word-break: break-all;
        }
        .table-data tr:nth-child(even) {
            background-color: #fcfcfc;
        }

        /* Status Text (No Box / Badge) */
        .badge {
            font-weight: bold;
            color: #000000;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-semibold { font-weight: bold; }
    </style>
</head>
<body>
@php
    $showHeader = $showHeader ?? true;
    $chunkOffset = $chunkOffset ?? 0;
@endphp

@if($showHeader)
    <!-- Header Laporan -->
    <div class="report-header">
        <h1>Balai Kekarantinaan Kesehatan Kelas I Bandung</h1>
        <h2>Laporan Ringkasan Pengajuan Izin Operasional Klinik</h2>
        <p>
            Periode: 
            <strong>{{ !empty($tanggal_mulai) ? \Carbon\Carbon::parse($tanggal_mulai)->format('d/m/Y') : '-' }}</strong> 
            s/d 
            <strong>{{ !empty($tanggal_selesai) ? \Carbon\Carbon::parse($tanggal_selesai)->format('d/m/Y') : '-' }}</strong>
        </p>
    </div>

    <!-- Filter Information -->
    <div class="filter-box">
        <table class="filter-table">
            <tr>
                <td class="filter-label">Kategori Izin</td>
                <td class="filter-val">: {{ $kategori_izin === 'semua' ? 'Semua Kategori' : $kategori_izin }}</td>
                <td class="filter-label">Kota / Kabupaten</td>
                <td class="filter-val">: {{ (!empty($kotakab) && $kotakab !== 'semua') ? $kotakab : 'Semua Wilayah' }}</td>
            </tr>
            <tr>
                <td class="filter-label">Status Progress</td>
                <td class="filter-val">: {{ $status_progress === 'semua' ? 'Semua Status' : ucwords(str_replace('_', ' ', $status_progress)) }}</td>
                <td class="filter-label">Faskes / Pemohon</td>
                <td class="filter-val">: {{ $user_nama ?? 'Semua' }}</td>
            </tr>
            <tr>
                <td class="filter-label">Total Data</td>
                <td class="filter-val">: <strong>{{ $total_data ?? $data->count() }} Pengajuan</strong></td>
                <td class="filter-label">Dicetak Pada</td>
                <td class="filter-val">: {{ now()->translatedFormat('d F Y H:i:s') }}</td>
            </tr>
        </table>
    </div>
@else
    <div style="height: 10px;"></div>
@endif

    <!-- Main Data Table -->
    <table class="table-data">
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th style="width: 4%;">ID</th>
                <th style="width: 8%;">Tgl Pengajuan</th>
                <th style="width: 17%;">Nama Faskes / Pemohon</th>
                <th style="width: 11%;">Kota/Kabupaten</th>
                <th style="width: 9%;">Kategori Izin</th>
                <th style="width: 12%;">Nomor Surat</th>
                <th style="width: 7%;">Tgl Surat</th>
                <th style="width: 9%;">Verifikasi Dokumen</th>
                <th style="width: 8%;">Jadwal Visitasi</th>
                <th style="width: 12%;">Status Progress</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $row)
                @php
                    $statusProgress = \App\Services\Export\IzinExportService::getProgressStatus($row);
                    $dokumen = $row->izinDokumen;
                    $visitasi = $row->izinVisitasi;
                    $pks = $row->izinDokumenPks;
                    $tglVisitasi = $visitasi?->tanggal_visitasi ?? $pks?->tanggal_visitasi;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 + $chunkOffset }}</td>
                    <td class="text-center font-semibold">#{{ $row->id }}</td>
                    <td class="text-center">{{ $row->tanggal_pengajuan ? \Carbon\Carbon::parse($row->tanggal_pengajuan)->format('d/m/Y') : '-' }}</td>
                    <td class="font-semibold">{{ $row->user->name ?? '-' }}</td>
                    <td>{{ $row->user->kotakab ?? '-' }}</td>
                    <td class="text-center">{{ $row->kategori_izin ?? '-' }}</td>
                    <td>{{ $row->nomor_surat ?? '-' }}</td>
                    <td class="text-center">{{ $row->tanggal_surat ? \Carbon\Carbon::parse($row->tanggal_surat)->format('d/m/Y') : '-' }}</td>
                    <td class="text-center">
                        @if($dokumen)
                            <span class="badge">
                                {{ $dokumen->getHasilLabel() }}
                            </span>
                        @else
                            <span class="badge">Belum Diverifikasi</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $tglVisitasi ? \Carbon\Carbon::parse($tglVisitasi)->format('d/m/Y') : '-' }}</td>
                    <td class="text-center">
                        <span class="badge">
                            {{ $statusProgress }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center" style="padding: 20px; color: #333333;">
                        Tidak ada data pengajuan izin yang sesuai dengan filter yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 10px; font-size: 8.5px; color: #333333;">
        <span>Total Data: <strong>{{ $data->count() }}</strong> pengajuan izin</span>
    </div>

    <!-- Dompdf Page Number Script -->
    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("Helvetica", "normal");
            $size = 8;
            $color = array(0.2, 0.2, 0.2);

            // Page X of Y on bottom right (landscape width is 841.89 pt)
            $pdf->page_text(720, 565, "Halaman {PAGE_NUM} dari {PAGE_COUNT}", $font, $size, $color);

            // System footer on bottom left
            $pdf->page_text(30, 565, "BKK Kelas I Bandung - Laporan Ringkasan Pengajuan Izin | Dicetak: " . date('d/m/Y H:i'), $font, $size, $color);
        }
    </script>
</body>
</html>
