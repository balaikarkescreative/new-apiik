<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Detail Pengajuan Izin</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 10mm 15mm 10mm;
        }
        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }
        body {
            font-size: 9.5px;
            color: #000000;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }

        /* Header Laporan */
        .report-header {
            text-align: center;
            border-bottom: 2px solid #000000;
            padding-bottom: 8px;
            margin-bottom: 12px;
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
            margin-bottom: 12px;
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
            width: 15%;
            font-weight: bold;
        }
        .filter-val {
            color: #000000;
            width: 35%;
        }

        /* Dossier Card */
        .card {
            page-break-inside: avoid;
            border: 1px solid #000000;
            margin-bottom: 12px;
            background-color: #ffffff;
        }
        .card-header {
            background-color: #f2f2f2;
            color: #000000;
            padding: 5px 8px;
            border-bottom: 1px solid #000000;
        }
        .card-header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .card-header-table td {
            padding: 0;
            vertical-align: middle;
            color: #000000;
        }
        .card-id {
            font-size: 11px;
            font-weight: bold;
            color: #000000;
        }
        .card-meta {
            font-size: 8.5px;
            color: #333333;
        }

        /* Status Text (No Box / Badge) */
        .badge {
            font-weight: bold;
            color: #000000;
        }

        /* Card Content */
        .card-body {
            padding: 6px 8px;
        }

        .section-title {
            font-size: 8.5px;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 1px solid #cccccc;
            padding-bottom: 2px;
            margin-top: 5px;
            margin-bottom: 4px;
        }
        .section-title:first-child {
            margin-top: 0;
        }

        /* 2-column detail grid table */
        .grid-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin-bottom: 3px;
        }
        .grid-table td {
            vertical-align: top;
            padding: 1px 3px;
            font-size: 9px;
            word-wrap: break-word;
            word-break: break-all;
        }
        .col-half {
            width: 50%;
        }

        /* Inner key-value table */
        .kv-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }
        .kv-table td {
            padding: 1.5px 2px;
            vertical-align: top;
            font-size: 8.5px;
            color: #000000;
            word-wrap: break-word;
            word-break: break-all;
        }
        .kv-label {
            width: 32%;
            color: #333333;
            font-weight: bold;
        }
        .kv-colon {
            width: 3%;
            color: #000000;
            text-align: left;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        .kv-value {
            width: 65%;
            color: #000000;
            word-wrap: break-word;
            word-break: break-all;
            padding-left: 2px !important;
        }

        /* Note box */
        .note-box {
            background-color: #fafafa;
            border: 1px solid #cccccc;
            border-left: 3px solid #000000;
            padding: 3px 5px;
            font-size: 8px;
            color: #000000;
            margin-top: 2px;
            white-space: pre-line;
            word-wrap: break-word;
            word-break: break-all;
        }
        .empty-note {
            font-style: italic;
            color: #666666;
            font-size: 8px;
            padding: 2px 0;
        }

        /* Revision History Table */
        .rev-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin-top: 3px;
            font-size: 8.5px;
        }
        .rev-table th {
            background-color: #f2f2f2;
            border: 1px solid #000000;
            padding: 3px 5px;
            text-align: left;
            font-weight: bold;
            color: #000000;
            word-wrap: break-word;
            word-break: break-all;
        }
        .rev-table td {
            border: 1px solid #000000;
            padding: 3px 5px;
            vertical-align: top;
            color: #000000;
            word-wrap: break-word;
            word-break: break-all;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-semibold { font-weight: bold; }
    </style>
</head>
<body>
@php
    $showHeader = $showHeader ?? true;
@endphp

@if($showHeader)
    <!-- Header Laporan -->
    <div class="report-header">
        <h1>Balai Kekarantinaan Kesehatan Kelas I Bandung</h1>
        <h2>Laporan Lengkap Dossier Pengajuan Izin Operasional Klinik</h2>
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

    <!-- Content: Dossier Cards -->
    @forelse($data as $row)
        @php
            $statusProgress = \App\Services\Export\IzinExportService::getProgressStatus($row);
            $user = $row->user;
            $dokumen = $row->izinDokumen;
            $visitasi = $row->izinVisitasi;
            $pks = $row->izinDokumenPks;
            $persetujuan = $row->izinPersetujuan;
            $persetujuanIcv = $user?->suratPersetujuanIcv;

            // Visitasi
            $tglVisitasi = $visitasi?->tanggal_visitasi ?? $pks?->tanggal_visitasi;
            $statusVisitasi = $visitasi?->status ?: ($tglVisitasi ? 'Dijadwalkan' : '-');

            // PKS
            $noPks = $pks?->dokumen_pks_bkk 
                ? basename($pks->dokumen_pks_bkk) 
                : ($pks?->dokumen_pks_klinik ? basename($pks->dokumen_pks_klinik) : '-');
            $tglPks = ($pks?->dokumen_pks_bkk && $pks->date_updated)
                ? \Carbon\Carbon::parse($pks->date_updated)->format('d/m/Y')
                : '-';

            // Persetujuan
            $noPersetujuan = $persetujuanIcv?->nomor_surat 
                ?? ($persetujuan?->file_persetujuan ? basename($persetujuan->file_persetujuan) : '-');
            $masaBerlakuPersetujuan = $persetujuanIcv?->masa_berlaku 
                ? \Carbon\Carbon::parse($persetujuanIcv->masa_berlaku)->format('d/m/Y')
                : '-';

            // Riwayat Perbaikan
            $riwayatList = $row->riwayat_perbaikan;
            if (is_string($riwayatList)) {
                $decoded = json_decode($riwayatList, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $riwayatList = $decoded;
                } else {
                    $riwayatList = [];
                }
            }
        @endphp

        <div class="card">
            <!-- Header Bar -->
            <div class="card-header">
                <table class="card-header-table">
                    <tr>
                        <td style="width: 70%;">
                            <span class="card-id">#{{ $row->id }} - {{ $user?->name ?? 'Pemohon' }}</span>
                            <div class="card-meta">
                                Kategori: <strong>{{ $row->kategori_izin ?? '-' }}</strong> | 
                                Tanggal Pengajuan: <strong>{{ $row->tanggal_pengajuan ? \Carbon\Carbon::parse($row->tanggal_pengajuan)->format('d F Y') : '-' }}</strong>
                            </div>
                        </td>
                        <td style="width: 30%;" class="text-right">
                            <span class="badge">
                                {{ $statusProgress }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="card-body">
                <!-- 1. Informasi Faskes & Surat -->
                <div class="section-title">1. Informasi Faskes & Surat Permohonan</div>
                <table class="grid-table">
                    <tr>
                        <td class="col-half">
                            <table class="kv-table">
                                <tr>
                                    <td class="kv-label">Nama Faskes</td>
                                    <td class="kv-colon">:</td>
                                    <td class="kv-value font-semibold">{{ $user?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="kv-label">Kategori / Kota</td>
                                    <td class="kv-colon">:</td>
                                    <td class="kv-value">{{ $user?->kategori ?? '-' }} / {{ $user?->kotakab ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="kv-label">Alamat</td>
                                    <td class="kv-colon">:</td>
                                    <td class="kv-value">{{ $user?->alamat ?? '-' }}</td>
                                </tr>
                            </table>
                        </td>
                        <td class="col-half">
                            <table class="kv-table">
                                <tr>
                                    <td class="kv-label">PJ & Kontak</td>
                                    <td class="kv-colon">:</td>
                                    <td class="kv-value">{{ $user?->pj_akun ?? '-' }} ({{ $user?->kontak_pj ?? '-' }})</td>
                                </tr>
                                <tr>
                                    <td class="kv-label">Izin Operasional</td>
                                    <td class="kv-colon">:</td>
                                    <td class="kv-value">
                                        {{ $user?->nomor_surat_izin_operasional ?? '-' }}
                                        @if($user?->masa_berlaku_surat_izin_operasional)
                                            <span style="font-size: 8px; color: #333333;">(s/d {{ \Carbon\Carbon::parse($user->masa_berlaku_surat_izin_operasional)->format('d/m/Y') }})</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="kv-label">Nomor Surat</td>
                                    <td class="kv-colon">:</td>
                                    <td class="kv-value font-semibold">{{ $row->nomor_surat ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="kv-label">Tanggal Surat</td>
                                    <td class="kv-colon">:</td>
                                    <td class="kv-value">{{ $row->tanggal_surat ? \Carbon\Carbon::parse($row->tanggal_surat)->format('d F Y') : '-' }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <!-- 2. Verifikasi Dokumen & Visitasi -->
                <div class="section-title">2. Hasil Verifikasi Dokumen & Visitasi Lapangan</div>
                <table class="grid-table">
                    <tr>
                        <td class="col-half">
                            <table class="kv-table">
                                <tr>
                                    <td class="kv-label">Verifikasi Dokumen</td>
                                    <td class="kv-colon">:</td>
                                    <td class="kv-value">
                                        @if($dokumen)
                                            <span class="badge">
                                                {{ $dokumen->getHasilLabel() }}
                                            </span>
                                        @else
                                            <span class="badge">Belum Diverifikasi</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="kv-label">Catatan Dokumen</td>
                                    <td class="kv-colon">:</td>
                                    <td class="kv-value">
                                        @if(!empty($dokumen?->catatan))
                                            <div class="note-box">{{ $dokumen->catatan }}</div>
                                        @else
                                            <span class="empty-note">-</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td class="col-half">
                            <table class="kv-table">
                                <tr>
                                    <td class="kv-label">Jadwal Visitasi</td>
                                    <td class="kv-colon">:</td>
                                    <td class="kv-value font-semibold">
                                        {{ $tglVisitasi ? \Carbon\Carbon::parse($tglVisitasi)->format('d F Y') : 'Belum dijadwalkan' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="kv-label">Status Visitasi</td>
                                    <td class="kv-colon">:</td>
                                    <td class="kv-value">{{ $statusVisitasi }}</td>
                                </tr>
                                <tr>
                                    <td class="kv-label">Catatan Visitasi</td>
                                    <td class="kv-colon">:</td>
                                    <td class="kv-value">
                                        @if(!empty($visitasi?->catatan))
                                            <div class="note-box">{{ $visitasi->catatan }}</div>
                                        @else
                                            <span class="empty-note">-</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <!-- 3. PKS & Persetujuan -->
                <div class="section-title">3. Perjanjian Kerja Sama (PKS) & Surat Persetujuan</div>
                <table class="grid-table">
                    <tr>
                        <td class="col-half">
                            <table class="kv-table">
                                <tr>
                                    <td class="kv-label">Dokumen PKS</td>
                                    <td class="kv-colon">:</td>
                                    <td class="kv-value">{{ $noPks }}</td>
                                </tr>
                                <tr>
                                    <td class="kv-label">Tanggal PKS</td>
                                    <td class="kv-colon">:</td>
                                    <td class="kv-value">{{ $tglPks }}</td>
                                </tr>
                            </table>
                        </td>
                        <td class="col-half">
                            <table class="kv-table">
                                <tr>
                                    <td class="kv-label">No. Persetujuan</td>
                                    <td class="kv-colon">:</td>
                                    <td class="kv-value font-semibold">{{ $noPersetujuan }}</td>
                                </tr>
                                <tr>
                                    <td class="kv-label">Masa Berlaku</td>
                                    <td class="kv-colon">:</td>
                                    <td class="kv-value">{{ $masaBerlakuPersetujuan }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <!-- 4. Riwayat Perbaikan (Revisi) -->
                <div class="section-title">4. Riwayat Catatan Perbaikan / Revisi Berkas</div>
                @if(!empty($riwayatList) && count($riwayatList) > 0)
                    <table class="rev-table">
                        <thead>
                            <tr>
                                <th style="width: 5%;" class="text-center">No</th>
                                <th style="width: 25%;">Tanggal & Waktu</th>
                                <th style="width: 70%;">Catatan Perbaikan / Revisi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($riwayatList as $revIdx => $revItem)
                                <tr>
                                    <td class="text-center">{{ $revIdx + 1 }}</td>
                                    <td>
                                        {{ !empty($revItem['tanggal']) ? \Carbon\Carbon::parse($revItem['tanggal'])->format('d/m/Y H:i') : '-' }}
                                    </td>
                                    <td style="white-space: pre-line;">{{ trim($revItem['catatan'] ?? '-') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-note">Tidak ada catatan riwayat perbaikan berkas (pengajuan langsung diproses/tanpa revisi).</div>
                @endif
            </div>
        </div>
    @empty
        <div style="text-align: center; padding: 40px; color: #333333; font-size: 11px; border: 1px dashed #000000;">
            Tidak ada data pengajuan izin yang sesuai dengan filter yang dipilih.
        </div>
    @endforelse

    <!-- Dompdf Page Number Script -->
    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("Helvetica", "normal");
            $size = 8;
            $color = array(0.2, 0.2, 0.2);

            // Page X of Y on bottom right
            $pdf->page_text(480, 815, "Halaman {PAGE_NUM} dari {PAGE_COUNT}", $font, $size, $color);

            // System footer on bottom left
            $pdf->page_text(30, 815, "BKK Kelas I Bandung - Laporan Pengajuan Izin | Dicetak: " . date('d/m/Y H:i'), $font, $size, $color);
        }
    </script>
</body>
</html>
