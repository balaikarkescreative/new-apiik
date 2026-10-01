<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Lengkap Pengajuan ICV</title>
    <style>
        @page {
            margin: 10mm 10mm 12mm 10mm;
            size: a4 portrait;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9.5px;
            line-height: 1.35;
            color: #000000;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* Header Document */
        .report-header {
            width: 100%;
            border-bottom: 2px solid #000000;
            padding-bottom: 8px;
            margin-bottom: 12px;
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
            margin-bottom: 12px;
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
            width: 110px;
        }
        .filter-val {
            color: #000000;
        }

        /* Dossier Card per Application */
        .card {
            border: 1px solid #000000;
            margin-bottom: 12px;
            page-break-inside: avoid;
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
            border: none;
        }
        .card-header-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
            color: #000000;
        }
        .card-header-title {
            font-size: 11px;
            font-weight: bold;
            color: #000000;
        }
        .card-header-meta {
            text-align: right;
            font-size: 8.5px;
            color: #333333;
        }

        .card-body {
            padding: 6px 8px;
        }

        /* Layout Grid via Table */
        .card-grid {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            border: none;
        }
        .card-grid > tbody > tr > td {
            border: none;
            padding: 0;
            vertical-align: top;
            word-wrap: break-word;
            word-break: break-all;
        }
        .grid-col-left {
            width: 50%;
            padding-right: 5px !important;
        }
        .grid-col-right {
            width: 50%;
            padding-left: 5px !important;
        }

        /* Sub-sections */
        .section-box {
            background-color: #ffffff;
            border: 1px solid #cccccc;
            margin-bottom: 6px;
            padding: 5px 6px;
            box-sizing: border-box;
        }
        .section-title {
            font-size: 8.5px;
            font-weight: bold;
            color: #000000;
            border-bottom: 1px solid #cccccc;
            padding-bottom: 2px;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .data-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            border: none;
        }
        .data-table td {
            border: none;
            padding: 1.5px 2px;
            vertical-align: top;
            font-size: 8.5px;
            color: #000000;
            word-wrap: break-word;
            word-break: break-all;
        }
        .data-label {
            width: 32%;
            color: #333333;
            font-weight: bold;
        }
        .data-sep {
            width: 3%;
            color: #000000;
            text-align: left;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        .data-val {
            width: 65%;
            color: #000000;
            word-wrap: break-word;
            word-break: break-all;
            padding-left: 2px !important;
        }

        /* Status Text (No Box / Badge) */
        .badge {
            font-weight: bold;
            color: #000000;
        }

        /* Revisions Table */
        .revision-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin-top: 3px;
            font-size: 8.5px;
        }
        .revision-table th {
            background-color: #f2f2f2;
            color: #000000;
            font-weight: bold;
            border: 1px solid #000000;
            padding: 3px 5px;
            text-align: left;
            word-wrap: break-word;
            word-break: break-all;
        }
        .revision-table td {
            border: 1px solid #000000;
            padding: 3px 5px;
            color: #000000;
            vertical-align: top;
            word-wrap: break-word;
            word-break: break-all;
        }
        .empty-history {
            color: #666666;
            font-style: italic;
            font-size: 8px;
            padding: 2px 0;
        }

        /* Footer */
        .report-footer {
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #000000;
            font-size: 8.5px;
            color: #333333;
            text-align: right;
        }
    </style>
</head>
<body>
@php
    $showHeader = $showHeader ?? true;
@endphp

@if($showHeader)
    {{-- Header Dokumen --}}
    <div class="report-header">
        <table class="report-header-table">
            <tr>
                <td>
                    <h1 class="report-title">Laporan Lengkap Pengajuan ICV</h1>
                    <p class="report-subtitle">Balai Kekarantinaan Kesehatan Kelas I Bandung</p>
                </td>
                <td class="report-meta-right">
                    <strong>Dicetak pada:</strong> {{ now()->format('d/m/Y H:i:s') }}<br>
                    <strong>Total Pengajuan:</strong> {{ $total_data ?? $data->count() }} Data
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

    {{-- Daftar Pengajuan (Dossier / Card Style) --}}
    @forelse($data as $row)
        @php
            $statusPengajuan = match ($row->status) {
                0 => 'Pending',
                1 => 'Disetujui',
                2 => 'Ditolak',
                default => 'Pending',
            };

            $progressStatus = \App\Services\Export\IcvExportService::getProgressStatus($row);

            // Seri ICV
            $seriAwal = $row->icvAlokasi->no_seri_awal ?? ($row->no_seri_awal ?? null);
            $seriAkhir = $row->icvAlokasi->no_seri_akhir ?? ($row->no_seri_akhir ?? null);
            if ($seriAwal && $seriAkhir) {
                $rentangSeri = "{$seriAwal} - {$seriAkhir}";
            } elseif ($seriAwal) {
                $rentangSeri = (string) $seriAwal;
            } elseif (!empty($row->icvAlokasi?->no_seri)) {
                $rentangSeri = (string) $row->icvAlokasi->no_seri;
            } else {
                $rentangSeri = '-';
            }

            // Billing dates
            $tglBilling = $row->icvBilling?->date_updated ?? $row->icvBilling?->created_at;
            $tglBillingStr = $tglBilling ? \Carbon\Carbon::parse($tglBilling)->format('d/m/Y H:i') : '-';
            $tglExpiredStr = ($row->icvBilling && $tglBilling)
                ? \Carbon\Carbon::parse($tglBilling)->addDays(7)->format('d/m/Y H:i')
                : '-';

            // Status Bayar
            if ($row->icvBuktiBayar && $row->icvBuktiBayar->status === 1) {
                $ntpn = (!empty($row->icvBilling?->bayar) && $row->icvBilling->bayar !== '0')
                    ? " (NTPN: {$row->icvBilling->bayar})"
                    : '';
                $statusBayar = 'Lunas' . $ntpn;
            } elseif ($row->icvBuktiBayar && $row->icvBuktiBayar->file_bukti_bayar) {
                $statusBayar = 'Menunggu Verifikasi Pembayaran';
            } elseif ($row->icvBilling && $row->icvBilling->kode_billing) {
                $statusBayar = 'Menunggu Pembayaran';
            } else {
                $statusBayar = 'Belum Terbit Billing';
            }

            // Verifikasi Dokumen
            $hasilVerif = '-';
            if ($row->icvCekDokumen) {
                $hv = $row->icvCekDokumen->hasil_verif;
                if ($hv === '1' || $hv === 'Berkas Lengkap') {
                    $hasilVerif = 'Berkas Lengkap';
                } elseif ($hv === '2' || $hv === 'Berkas Tidak Lengkap') {
                    $hasilVerif = 'Berkas Tidak Lengkap';
                } elseif ($hv === '3' || $hv === 'Ditolak') {
                    $hasilVerif = 'Ditolak';
                } else {
                    $hasilVerif = (string) ($hv ?: '-');
                }
            }

            // Parse riwayat perbaikan
            $riwayatList = [];
            if (!empty($row->riwayat_perbaikan)) {
                if (is_array($row->riwayat_perbaikan)) {
                    $riwayatList = $row->riwayat_perbaikan;
                } elseif (is_string($row->riwayat_perbaikan)) {
                    $decoded = json_decode($row->riwayat_perbaikan, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $riwayatList = $decoded;
                    }
                }
            }
        @endphp

        <div class="card">
            {{-- Header Bar Kartu --}}
            <div class="card-header">
                <table class="card-header-table">
                    <tr>
                        <td class="card-header-title">
                            #{{ $row->id }} &mdash; {{ $row->user->name ?? 'Pemohon' }}
                        </td>
                        <td class="card-header-meta">
                            <span>Tgl: {{ $row->tanggal_pengajuan ? \Carbon\Carbon::parse($row->tanggal_pengajuan)->format('d/m/Y') : '-' }}</span>
                            &nbsp;|&nbsp;
                            <span><strong>{{ $row->jumlah_icv }}</strong> ICV</span>
                            &nbsp;|&nbsp;
                            <span class="badge">{{ $progressStatus }}</span>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="card-body">
                <table class="card-grid">
                    <tbody>
                        <tr>
                            {{-- Kolom Kiri --}}
                            <td class="grid-col-left">
                                {{-- 1. Info Faskes & Pemohon --}}
                                <div class="section-box">
                                    <div class="section-title">Informasi Faskes & Pemohon</div>
                                    <table class="data-table">
                                        <tr>
                                            <td class="data-label">Nama Faskes / Klinik</td>
                                            <td class="data-sep">:</td>
                                            <td class="data-val"><strong>{{ $row->user->name ?? '-' }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td class="data-label">Kota / Kabupaten</td>
                                            <td class="data-sep">:</td>
                                            <td class="data-val">{{ $row->user->kotakab ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="data-label">Alamat</td>
                                            <td class="data-sep">:</td>
                                            <td class="data-val">{{ $row->user->alamat ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="data-label">Kontak / PJ Akun</td>
                                            <td class="data-sep">:</td>
                                            <td class="data-val">
                                                {{ $row->user->kontak_pj ?: ($row->user->pj_akun ?: '-') }}
                                                @if(!empty($row->user->pj_akun) && !empty($row->user->kontak_pj))
                                                    ({{ $row->user->pj_akun }})
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </div>

                                {{-- 2. Verifikasi Dokumen & Alokasi --}}
                                <div class="section-box">
                                    <div class="section-title">Verifikasi Dokumen & Alokasi</div>
                                    <table class="data-table">
                                        <tr>
                                            <td class="data-label">Hasil Verifikasi</td>
                                            <td class="data-sep">:</td>
                                            <td class="data-val">
                                                <span class="badge">{{ $hasilVerif }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="data-label">Catatan Verifikator</td>
                                            <td class="data-sep">:</td>
                                            <td class="data-val" style="white-space: pre-line;">{{ $row->icvCekDokumen?->catatan ?: '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="data-label">ICV Dialokasikan</td>
                                            <td class="data-sep">:</td>
                                            <td class="data-val">
                                                <strong>{{ $row->icvAlokasi ? ($row->icvAlokasi->icv_disetujui ?? 0) : 0 }}</strong> ICV
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="data-label">Rentang Seri ICV</td>
                                            <td class="data-sep">:</td>
                                            <td class="data-val">{{ $rentangSeri }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </td>

                            {{-- Kolom Kanan --}}
                            <td class="grid-col-right">
                                {{-- 3. Billing & Pembayaran --}}
                                <div class="section-box">
                                    <div class="section-title">Billing & Pembayaran</div>
                                    <table class="data-table">
                                        <tr>
                                            <td class="data-label">Kode Billing</td>
                                            <td class="data-sep">:</td>
                                            <td class="data-val">
                                                <strong>{{ $row->icvBilling?->kode_billing ?: '-' }}</strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="data-label">Tanggal Penerbitan</td>
                                            <td class="data-sep">:</td>
                                            <td class="data-val">{{ $tglBillingStr }}</td>
                                        </tr>
                                        <tr>
                                            <td class="data-label">Batas Kadaluarsa</td>
                                            <td class="data-sep">:</td>
                                            <td class="data-val">{{ $tglExpiredStr }}</td>
                                        </tr>
                                        <tr>
                                            <td class="data-label">Status Pembayaran</td>
                                            <td class="data-sep">:</td>
                                            <td class="data-val">
                                                <span class="badge">{{ $statusBayar }}</span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>

                                {{-- 4. Status Sinkarkes --}}
                                <div class="section-box">
                                    <div class="section-title">Status Sinkarkes</div>
                                    <table class="data-table">
                                        <tr>
                                            <td class="data-label">Status Sinkarkes</td>
                                            <td class="data-sep">:</td>
                                            <td class="data-val">
                                                <span class="badge">
                                                    {{ $row->icvSbbk?->sinkarkes ?: 'Belum' }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                {{-- 5. Riwayat Perbaikan (Full Width) --}}
                <div class="section-box" style="margin-bottom: 0;">
                    <div class="section-title">Riwayat Perbaikan Dokumen</div>
                    @if(count($riwayatList) > 0)
                        <table class="revision-table">
                            <thead>
                                <tr>
                                    <th style="width: 5%; text-align: center;">No</th>
                                    <th style="width: 22%;">Tanggal & Waktu</th>
                                    <th style="width: 73%;">Catatan / Keterangan Perbaikan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($riwayatList as $idx => $rItem)
                                    @php
                                        $tglR = !empty($rItem['tanggal']) ? \Carbon\Carbon::parse($rItem['tanggal'])->format('d/m/Y H:i') : '-';
                                        $catR = is_array($rItem) ? ($rItem['catatan'] ?? '-') : (string)$rItem;
                                    @endphp
                                    <tr>
                                        <td style="text-align: center;">{{ $idx + 1 }}</td>
                                        <td>{{ $tglR }}</td>
                                        <td style="white-space: pre-line;">{{ $catR }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-history">Tidak ada riwayat catatan perbaikan untuk pengajuan ini.</div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div style="text-align: center; padding: 40px; color: #333333; background-color: #ffffff; border: 1px dashed #000000;">
            <p style="font-size: 13px; font-weight: bold; margin: 0 0 4px 0;">Tidak Ada Data Pengajuan ICV</p>
            <p style="font-size: 10px; margin: 0;">Tidak ditemukan pengajuan ICV yang sesuai dengan kriteria filter yang dipilih.</p>
        </div>
    @endforelse

    <div class="report-footer">
        BKK Kelas I Bandung &bull; Laporan Pengajuan ICV &bull; Halaman cetak
    </div>

    <!-- Dompdf Page Number Script -->
    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("Helvetica", "normal");
            $size = 8;
            $color = array(0.2, 0.2, 0.2);

            // Page X of Y on bottom right
            $pdf->page_text(480, 815, "Halaman {PAGE_NUM} dari {PAGE_COUNT}", $font, $size, $color);

            // System footer on bottom left
            $pdf->page_text(30, 815, "BKK Kelas I Bandung - Laporan Pengajuan ICV | Dicetak: " . date('d/m/Y H:i'), $font, $size, $color);
        }
    </script>
</body>
</html>
