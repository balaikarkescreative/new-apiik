<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Ringkas Pengajuan ICV</title>
    <style>
        @page {
            margin: 10mm 10mm 12mm 10mm;
            size: a4 landscape;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            line-height: 1.35;
            color: #000000;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* Header Dokumen */
        .report-header {
            width: 100%;
            border-bottom: 2px solid #000000;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .report-header-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }
        .report-header-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
        .report-title {
            font-size: 13px;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 2px 0;
        }
        .report-subtitle {
            font-size: 9.5px;
            color: #333333;
            margin: 0;
        }
        .report-meta-right {
            text-align: right;
            font-size: 8.5px;
            color: #333333;
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
            border: none;
            font-size: 8.5px;
        }
        .filter-table td {
            border: none;
            padding: 2px 4px;
            vertical-align: top;
            color: #000000;
        }
        .filter-label {
            font-weight: bold;
            color: #000000;
            width: 100px;
        }
        .filter-val {
            color: #000000;
        }

        /* Summary Table */
        .summary-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            font-size: 8.5px;
            margin-bottom: 10px;
        }
        .summary-table th {
            background-color: #f2f2f2;
            color: #000000;
            font-weight: bold;
            border: 1px solid #000000;
            padding: 5px 4px;
            text-align: left;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.2px;
            word-wrap: break-word;
            word-break: break-all;
        }
        .summary-table td {
            border: 1px solid #000000;
            padding: 4px;
            vertical-align: middle;
            color: #000000;
            word-wrap: break-word;
            word-break: break-all;
        }
        .summary-table tr:nth-child(even) td {
            background-color: #fcfcfc;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }

        /* Status Text (No Box / Badge) */
        .badge {
            font-weight: bold;
            color: #000000;
        }

        /* Footer */
        .report-footer {
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #000000;
            font-size: 8.5px;
            color: #333333;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }
        .footer-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
    </style>
</head>
<body>
@php
    $showHeader = $showHeader ?? true;
    $chunkOffset = $chunkOffset ?? 0;
@endphp

@if($showHeader)
    {{-- Header Dokumen --}}
    <div class="report-header">
        <table class="report-header-table">
            <tr>
                <td>
                    <h1 class="report-title">Laporan Ringkas Pengajuan ICV</h1>
                    <p class="report-subtitle">Balai Kekarantinaan Kesehatan Kelas I Bandung</p>
                </td>
                <td class="report-meta-right">
                    <strong>Dicetak pada:</strong> {{ now()->format('d/m/Y H:i:s') }}<br>
                    <strong>Total Data:</strong> {{ $total_data ?? $data->count() }} Pengajuan
                </td>
            </tr>
        </table>
    </div>

    {{-- Filter Metadata Box --}}
    <div class="filter-box">
        <table class="filter-table">
            <tr>
                <td class="filter-label">Periode Pengajuan</td>
                <td style="width: 8px;">:</td>
                <td class="filter-val" style="width: 35%;">
                    {{ $tanggal_mulai }} s/d {{ $tanggal_selesai }}
                </td>
                <td class="filter-label">Kota / Kabupaten</td>
                <td style="width: 8px;">:</td>
                <td class="filter-val">
                    {{ !empty($kotakab) ? $kotakab : 'Semua Kota / Kabupaten' }}
                </td>
            </tr>
            <tr>
                <td class="filter-label">Progress Status</td>
                <td style="width: 8px;">:</td>
                <td class="filter-val">
                    {{ $progress_status === 'semua' ? 'Semua Status' : ucwords(str_replace('_', ' ', $progress_status)) }}
                </td>
                <td class="filter-label">Faskes / Klinik</td>
                <td style="width: 8px;">:</td>
                <td class="filter-val">
                    @if(!empty($user_id))
                        {{ \App\Models\User::find($user_id)?->name ?? 'User #' . $user_id }}
                    @else
                        Semua Faskes / Klinik
                    @endif
                </td>
            </tr>
        </table>
    </div>
@else
    <div style="height: 10px;"></div>
@endif

    {{-- Tabel Ringkas --}}
    <table class="summary-table">
        <thead>
            <tr>
                <th style="width: 3%;" class="text-center">No</th>
                <th style="width: 4%;" class="text-center">ID</th>
                <th style="width: 8%;" class="text-center">Tanggal</th>
                <th style="width: 18%;">Faskes / Klinik</th>
                <th style="width: 12%;">Kota / Kabupaten</th>
                <th style="width: 6%;" class="text-center">Diajukan</th>
                <th style="width: 6%;" class="text-center">Alokasi</th>
                <th style="width: 7%;" class="text-center">Status</th>
                <th style="width: 11%;">Progress</th>
                <th style="width: 9%;" class="text-center">Kode Billing</th>
                <th style="width: 10%;" class="text-center">Status Bayar</th>
                <th style="width: 6%;" class="text-center">Sinkarkes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $idx => $row)
                @php
                    $statusPengajuan = match ($row->status) {
                        0 => 'Pending',
                        1 => 'Disetujui',
                        2 => 'Ditolak',
                        default => 'Pending',
                    };

                    $progressStatus = \App\Services\Export\IcvExportService::getProgressStatus($row);

                    if ($row->icvBuktiBayar && $row->icvBuktiBayar->status === 1) {
                        $statusBayar = 'Lunas';
                    } elseif ($row->icvBuktiBayar && $row->icvBuktiBayar->file_bukti_bayar) {
                        $statusBayar = 'Menunggu Verif';
                    } elseif ($row->icvBilling && $row->icvBilling->kode_billing) {
                        $statusBayar = 'Menunggu Bayar';
                    } else {
                        $statusBayar = 'Belum Ada';
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 + $chunkOffset }}</td>
                    <td class="text-center"><strong>#{{ $row->id }}</strong></td>
                    <td class="text-center">{{ $row->tanggal_pengajuan ? \Carbon\Carbon::parse($row->tanggal_pengajuan)->format('d/m/Y') : '-' }}</td>
                    <td><strong>{{ $row->user->name ?? '-' }}</strong></td>
                    <td>{{ $row->user->kotakab ?? '-' }}</td>
                    <td class="text-center">{{ $row->jumlah_icv ?? 0 }}</td>
                    <td class="text-center">{{ $row->icvAlokasi ? ($row->icvAlokasi->icv_disetujui ?? 0) : '-' }}</td>
                    <td class="text-center">
                        <span class="badge">{{ $statusPengajuan }}</span>
                    </td>
                    <td>
                        <span class="badge">{{ $progressStatus }}</span>
                    </td>
                    <td class="text-center">{{ $row->icvBilling?->kode_billing ?: '-' }}</td>
                    <td class="text-center">
                        <span class="badge">{{ $statusBayar }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge">
                            {{ $row->icvSbbk?->sinkarkes ?: 'Belum' }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center" style="padding: 20px; color: #333333;">
                        Tidak ditemukan pengajuan ICV yang sesuai kriteria filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="report-footer">
        <table class="footer-table">
            <tr>
                <td>Total: <strong>{{ $data->count() }}</strong> pengajuan ICV &bull; Jumlah Total ICV Diajukan: <strong>{{ $data->sum('jumlah_icv') }}</strong></td>
                <td style="text-align: right;">BKK Kelas I Bandung &bull; Halaman cetak</td>
            </tr>
        </table>
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
            $pdf->page_text(30, 565, "BKK Kelas I Bandung - Laporan Ringkasan Pengajuan ICV | Dicetak: " . date('d/m/Y H:i'), $font, $size, $color);
        }
    </script>
</body>
</html>
