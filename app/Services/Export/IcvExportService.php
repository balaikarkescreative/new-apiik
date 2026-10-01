<?php

namespace App\Services\Export;

use App\Models\Icv;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use setasign\Fpdi\Fpdi;
use Symfony\Component\HttpFoundation\Response;

class IcvExportService
{
    /**
     * Dispatch export request based on requested format.
     */
    public static function export(array $data)
    {
        $query = self::query($data);
        $format = $data['format'] ?? 'excel';

        return match ($format) {
            'excel' => self::downloadExcel($query, $data),
            'csv' => self::downloadCsv($query, $data),
            'pdf' => self::downloadPdf($query, $data),
            default => self::downloadExcel($query, $data),
        };
    }

    /**
     * Count total records matching filter parameters.
     */
    public static function countRecords(array $params): int
    {
        return self::query($params)->count();
    }

    /**
     * Initialize a batch export session for client-orchestrated progress.
     */
    public static function initBatch(array $params, int $chunkSize = 25): array
    {
        $count = self::countRecords($params);

        return ExportBatchService::createBatch('icv', $params, $count, $chunkSize);
    }

    /**
     * Build ICV query with eager loaded relations and filters.
     */
    public static function query(array $params): Builder
    {
        $query = Icv::with([
            'user',
            'icvCekDokumen',
            'icvAlokasi',
            'icvBilling',
            'icvBuktiBayar',
            'icvJadwal',
            'icvSbbk',
        ]);

        // Date range filter
        if (! empty($params['tanggal_mulai']) && ! empty($params['tanggal_selesai'])) {
            $query->whereDate('tanggal_pengajuan', '>=', $params['tanggal_mulai'])
                ->whereDate('tanggal_pengajuan', '<=', $params['tanggal_selesai']);
        } elseif (! empty($params['tanggal_mulai'])) {
            $query->whereDate('tanggal_pengajuan', '>=', $params['tanggal_mulai']);
        } elseif (! empty($params['tanggal_selesai'])) {
            $query->whereDate('tanggal_pengajuan', '<=', $params['tanggal_selesai']);
        }

        // Progress status filter
        if (! empty($params['progress_status']) && $params['progress_status'] !== 'semua') {
            switch ($params['progress_status']) {
                case 'menunggu_verifikasi':
                    $query->where(function ($q) {
                        $q->whereDoesntHave('icvCekDokumen')
                            ->orWhereHas('icvCekDokumen', fn ($sub) => $sub->whereNull('hasil_verif'));
                    });
                    break;
                case 'berkas_lengkap':
                    $query->whereHas('icvCekDokumen', fn ($q) => $q->whereIn('hasil_verif', ['1', 'Berkas Lengkap']));
                    break;
                case 'berkas_tidak_lengkap':
                    $query->whereHas('icvCekDokumen', fn ($q) => $q->whereIn('hasil_verif', ['2', 'Berkas Tidak Lengkap']));
                    break;
                case 'dialokasikan':
                    $query->whereHas('icvAlokasi', fn ($q) => $q->where('icv_disetujui', '>', 0));
                    break;
                case 'menunggu_pembayaran':
                    $query->whereHas('icvBilling', fn ($q) => $q->whereNotNull('kode_billing'));
                    break;
                case 'selesai':
                    $query->whereHas('icvSbbk', fn ($q) => $q->where('sinkarkes', 'Sudah'));
                    break;
            }
        }

        // Kotakab filter (on user relationship)
        if (! empty($params['kotakab'])) {
            $query->whereHas('user', function ($q) use ($params) {
                $q->where('kotakab', $params['kotakab']);
            });
        }

        // Specific user/faskes filter
        if (! empty($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        }

        return $query->orderBy('tanggal_pengajuan', 'desc')->orderBy('id', 'desc');
    }

    /**
     * Query ICV data with eager loaded relations and filters (returns Collection).
     */
    public static function queryData(array $params): Collection
    {
        return self::query($params)->get();
    }

    /**
     * Determine progress status string matching business rules.
     */
    public static function getProgressStatus($record): string
    {
        if ($record->status === 2) {
            return 'Ditolak';
        }

        if ($record->icvSbbk && $record->icvSbbk->sinkarkes === 'Sudah') {
            return 'Selesai';
        }

        if ($record->icvJadwal && $record->icvJadwal->status === 1) {
            return 'ICV Sudah Diambil';
        }

        if ($record->icvJadwal && $record->icvJadwal->jadwal_pengambilan) {
            return 'Jadwal Pengambilan Ditentukan';
        }

        if ($record->icvBuktiBayar && $record->icvBuktiBayar->status === 1) {
            return 'Pembayaran Terverifikasi';
        }

        if ($record->icvBuktiBayar && $record->icvBuktiBayar->file_bukti_bayar) {
            return 'Menunggu Verifikasi Pembayaran';
        }

        if ($record->icvBilling && $record->icvBilling->kode_billing) {
            return 'Menunggu Pembayaran';
        }

        if ($record->icvAlokasi && $record->icvAlokasi->icv_disetujui > 0) {
            return 'ICV Dialokasikan';
        }

        if ($record->icvCekDokumen) {
            if (in_array($record->icvCekDokumen->hasil_verif, ['1', 'Berkas Lengkap'])) {
                return 'Berkas Lengkap - Menunggu Alokasi';
            } elseif (in_array($record->icvCekDokumen->hasil_verif, ['2', 'Berkas Tidak Lengkap'])) {
                return 'Berkas Tidak Lengkap';
            } elseif (in_array($record->icvCekDokumen->hasil_verif, ['3', 'Ditolak'])) {
                return 'Ditolak';
            }
        }

        return 'Menunggu Verifikasi';
    }

    /**
     * Format riwayat_perbaikan as a readable multiline string: [d/m/Y H:i] Catatan.
     */
    public static function formatRiwayatPerbaikan($riwayat): string
    {
        if (empty($riwayat)) {
            return '-';
        }

        if (is_string($riwayat)) {
            $decoded = json_decode($riwayat, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $riwayat = $decoded;
            } else {
                return trim($riwayat);
            }
        }

        if (! is_array($riwayat)) {
            return '-';
        }

        $lines = [];
        foreach ($riwayat as $item) {
            if (is_array($item)) {
                $tgl = ! empty($item['tanggal']) ? Carbon::parse($item['tanggal'])->format('d/m/Y H:i') : '-';
                $catatan = trim($item['catatan'] ?? '');
                $lines[] = "[{$tgl}] {$catatan}";
            } elseif (is_string($item)) {
                $lines[] = trim($item);
            }
        }

        return count($lines) > 0 ? implode("\n", $lines) : '-';
    }

    /**
     * 24 Headers for 'lengkap' report.
     */
    public static function getHeadersLengkap(): array
    {
        return [
            'ID',
            'Tanggal Pengajuan',
            'Nama Faskes/Klinik',
            'Kota/Kabupaten',
            'Alamat',
            'Kontak PJ',
            'Jumlah ICV Diajukan',
            'Status Pengajuan',
            'Progress Status',
            'Hasil Verifikasi Dokumen',
            'Catatan Verifikator Dokumen',
            'Jumlah ICV Dialokasikan',
            'No Seri ICV Awal - Akhir',
            'Kode Billing',
            'Tanggal Billing',
            'Tanggal Kadaluarsa Billing',
            'Status Pembayaran / NTPN',
            'Status Sinkarkes',
            'Riwayat Perbaikan',
            'Tanggal Update',
        ];
    }

    /**
     * Headers for 'ringkas' report.
     */
    public static function getHeadersRingkas(): array
    {
        return [
            'ID',
            'Tanggal Pengajuan',
            'Nama Faskes/Klinik',
            'Kota/Kabupaten',
            'Jumlah ICV Diajukan',
            'Jumlah ICV Dialokasikan',
            'Status Pengajuan',
            'Progress Status',
            'Kode Billing',
            'Status Pembayaran',
            'Metode Pengambilan',
            'Status Sinkarkes',
            'Tanggal Update',
        ];
    }

    /**
     * Map a single ICV record into 24 columns for 'lengkap'.
     */
    public static function mapRowLengkap(Icv $row): array
    {
        $statusPengajuan = match ($row->status) {
            0 => 'Pending',
            1 => 'Disetujui',
            2 => 'Ditolak',
            default => 'Pending',
        };

        // Hasil verifikasi
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

        // Seri ICV
        $seriAwal = $row->icvAlokasi->no_seri_awal ?? ($row->no_seri_awal ?? null);
        $seriAkhir = $row->icvAlokasi->no_seri_akhir ?? ($row->no_seri_akhir ?? null);
        if ($seriAwal && $seriAkhir) {
            $rentangSeri = "{$seriAwal} - {$seriAkhir}";
        } elseif ($seriAwal) {
            $rentangSeri = (string) $seriAwal;
        } elseif (! empty($row->icvAlokasi?->no_seri)) {
            $rentangSeri = (string) $row->icvAlokasi->no_seri;
        } else {
            $rentangSeri = '-';
        }

        // Billing
        $tglBilling = $row->icvBilling?->date_updated ?? $row->icvBilling?->created_at;
        $tglBillingStr = $tglBilling ? Carbon::parse($tglBilling)->format('d/m/Y H:i') : '-';
        $tglExpiredStr = ($row->icvBilling && $tglBilling)
            ? Carbon::parse($tglBilling)->addDays(7)->format('d/m/Y H:i')
            : '-';

        // Status Pembayaran / NTPN
        if ($row->icvBuktiBayar && $row->icvBuktiBayar->status === 1) {
            $ntpn = (! empty($row->icvBilling?->bayar) && $row->icvBilling->bayar !== '0')
                ? " (NTPN: {$row->icvBilling->bayar})"
                : '';
            $statusBayar = 'Lunas'.$ntpn;
        } elseif ($row->icvBuktiBayar && $row->icvBuktiBayar->file_bukti_bayar) {
            $statusBayar = 'Menunggu Verifikasi Pembayaran';
        } elseif ($row->icvBilling && $row->icvBilling->kode_billing) {
            $statusBayar = 'Menunggu Pembayaran';
        } else {
            $statusBayar = 'Belum Terbit Billing';
        }

        // Tanggal update
        $tglUpdate = $row->date_updated ?? $row->updated_at;
        $tglUpdateStr = $tglUpdate ? Carbon::parse($tglUpdate)->format('d/m/Y H:i') : '-';

        return [
            $row->id,
            $row->tanggal_pengajuan ? Carbon::parse($row->tanggal_pengajuan)->format('d/m/Y') : '-',
            $row->user->name ?? '-',
            $row->user->kotakab ?? '-',
            $row->user->alamat ?? '-',
            $row->user->kontak_pj ?: ($row->user->pj_akun ?: '-'),
            $row->jumlah_icv ?? 0,
            $statusPengajuan,
            self::getProgressStatus($row),
            $hasilVerif,
            $row->icvCekDokumen?->catatan ?: '-',
            $row->icvAlokasi ? ($row->icvAlokasi->icv_disetujui ?? 0) : '-',
            $rentangSeri,
            $row->icvBilling?->kode_billing ? (string) $row->icvBilling->kode_billing : '-',
            $tglBillingStr,
            $tglExpiredStr,
            $statusBayar,
            $row->icvSbbk?->sinkarkes ?: 'Belum',
            self::formatRiwayatPerbaikan($row->riwayat_perbaikan),
            $tglUpdateStr,
        ];
    }

    /**
     * Map a single ICV record into core columns for 'ringkas'.
     */
    public static function mapRowRingkas(Icv $row): array
    {
        $statusPengajuan = match ($row->status) {
            0 => 'Pending',
            1 => 'Disetujui',
            2 => 'Ditolak',
            default => 'Pending',
        };

        if ($row->icvBuktiBayar && $row->icvBuktiBayar->status === 1) {
            $statusBayar = 'Lunas';
        } elseif ($row->icvBuktiBayar && $row->icvBuktiBayar->file_bukti_bayar) {
            $statusBayar = 'Menunggu Verifikasi';
        } elseif ($row->icvBilling && $row->icvBilling->kode_billing) {
            $statusBayar = 'Menunggu Pembayaran';
        } else {
            $statusBayar = 'Belum Ada Billing';
        }

        $tglUpdate = $row->date_updated ?? $row->updated_at;
        $tglUpdateStr = $tglUpdate ? Carbon::parse($tglUpdate)->format('d/m/Y H:i') : '-';

        return [
            $row->id,
            $row->tanggal_pengajuan ? Carbon::parse($row->tanggal_pengajuan)->format('d/m/Y') : '-',
            $row->user->name ?? '-',
            $row->user->kotakab ?? '-',
            $row->jumlah_icv ?? 0,
            $row->icvAlokasi ? ($row->icvAlokasi->icv_disetujui ?? 0) : '-',
            $statusPengajuan,
            self::getProgressStatus($row),
            $row->icvBilling?->kode_billing ? (string) $row->icvBilling->kode_billing : '-',
            $statusBayar,
            $row->icvBuktiBayar?->distribusi_icv ? ucfirst($row->icvBuktiBayar->distribusi_icv) : '-',
            $row->icvSbbk?->sinkarkes ?: 'Belum',
            $tglUpdateStr,
        ];
    }

    public static function downloadExcel(Collection|Builder $dataOrQuery, array $params)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        $isLengkap = ($params['tipe_laporan'] ?? 'lengkap') === 'lengkap';
        $allRows = [];

        if ($dataOrQuery instanceof Builder) {
            (clone $dataOrQuery)->chunk(100, function ($chunk) use (&$allRows, $isLengkap) {
                foreach ($chunk as $record) {
                    $allRows[] = $isLengkap ? self::mapRowLengkap($record) : self::mapRowRingkas($record);
                }
            });
        } else {
            foreach ($dataOrQuery as $record) {
                $allRows[] = $isLengkap ? self::mapRowLengkap($record) : self::mapRowRingkas($record);
            }
        }

        $spreadsheet = self::buildSpreadsheetFromRows($allRows, $params['tipe_laporan'] ?? 'lengkap');
        $filename = 'laporan_pengajuan_icv_'.($isLengkap ? 'lengkap_' : 'ringkas_').date('Y-m-d_H-i-s').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Build and style Spreadsheet from row arrays for ICV.
     */
    public static function buildSpreadsheetFromRows(array $allRows, string $tipe = 'lengkap'): Spreadsheet
    {
        $isLengkap = ($tipe === 'lengkap');
        $headers = $isLengkap ? self::getHeadersLengkap() : self::getHeadersRingkas();
        $totalCols = count($headers);
        $lastColLetter = Coordinate::stringFromColumnIndex($totalCols);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan ICV');

        // Header Row
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Header Styling: Navy blue background, white bold text, centered
        $headerRange = "A1:{$lastColLetter}1";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E3A8A'], // Navy Blue
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $textColumns = [
            'Kontak PJ',
            'Kode Billing',
            'No Seri ICV Awal - Akhir',
        ];

        $centerColumns = [
            'ID',
            'Tanggal Pengajuan',
            'Kota/Kabupaten',
            'Jumlah ICV Diajukan',
            'Status Pengajuan',
            'Jumlah ICV Dialokasikan',
            'Kode Billing',
            'Tanggal Billing',
            'Tanggal Kadaluarsa Billing',
            'Status Pembayaran / NTPN',
            'Status Sinkarkes',
            'Kontak PJ',
        ];

        $rowIndex = 2;
        foreach ($allRows as $row) {
            foreach ($row as $colIdx => $val) {
                $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
                $headerName = $headers[$colIdx] ?? '';

                if (in_array($headerName, $textColumns) && $val !== '-' && $val !== null && $val !== '') {
                    $sheet->setCellValueExplicit("{$colLetter}{$rowIndex}", (string) $val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->getStyle("{$colLetter}{$rowIndex}")->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
                } else {
                    $sheet->setCellValue("{$colLetter}{$rowIndex}", $val);
                }

                if (in_array($headerName, $centerColumns)) {
                    $sheet->getStyle("{$colLetter}{$rowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            }
            $sheet->getRowDimension($rowIndex)->setRowHeight(-1);
            $rowIndex++;
        }

        $lastRow = $rowIndex > 2 ? ($rowIndex - 1) : 1;

        // Apply borders
        $sheet->getStyle("A1:{$lastColLetter}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ]);

        // Data rows vertical alignment
        if ($lastRow >= 2) {
            $sheet->getStyle("A2:{$lastColLetter}{$lastRow}")->applyFromArray([
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_TOP,
                ],
            ]);
        }

        // Auto-width and wrap-text rules
        for ($col = 1; $col <= $totalCols; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $headerName = $headers[$col - 1];

            if (in_array($headerName, ['Riwayat Perbaikan', 'Alamat', 'Catatan Verifikator Dokumen'])) {
                $sheet->getColumnDimension($colLetter)->setWidth(35);
                if ($lastRow >= 2) {
                    $sheet->getStyle("{$colLetter}2:{$colLetter}{$lastRow}")->getAlignment()->setWrapText(true);
                }
            } else {
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }
        }

        return $spreadsheet;
    }

    /**
     * Process a single chunk in a batch export for ICV.
     */
    public static function processBatchChunk(string $token, int $chunkIndex): array
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        $batch = ExportBatchService::getBatch($token);
        if (! $batch) {
            throw new \RuntimeException('Sesi ekspor tidak ditemukan atau sudah kadaluarsa.');
        }

        $params = $batch['params'];
        $chunkSize = $batch['chunk_size'];
        $totalCount = $batch['total_count'];
        $totalChunks = $batch['total_chunks'];
        $format = $batch['format'];
        $isLengkap = ($batch['tipe_laporan'] ?? 'lengkap') === 'lengkap';

        $offset = $chunkIndex * $chunkSize;
        $chunkData = self::query($params)->skip($offset)->take($chunkSize)->get();

        if ($format === 'pdf') {
            $view = $isLengkap ? 'reports.laporan-icv-detail' : 'reports.laporan-icv';
            $isFirst = ($chunkIndex === 0);

            $chunkPdf = Pdf::loadView($view, [
                'data' => $chunkData,
                'params' => $params,
                'showHeader' => $isFirst,
                'chunkOffset' => $offset,
                'total_data' => $totalCount,
                'tanggal_mulai' => ! empty($params['tanggal_mulai']) ? Carbon::parse($params['tanggal_mulai'])->format('d/m/Y') : '-',
                'tanggal_selesai' => ! empty($params['tanggal_selesai']) ? Carbon::parse($params['tanggal_selesai'])->format('d/m/Y') : '-',
                'progress_status' => $params['progress_status'] ?? 'semua',
                'kotakab' => $params['kotakab'] ?? null,
                'user_id' => $params['user_id'] ?? null,
                'tipe_laporan' => $batch['tipe_laporan'] ?? 'lengkap',
            ]);

            $chunkPdf->setPaper('a4', $isLengkap ? 'portrait' : 'landscape');

            $tempPath = tempnam(sys_get_temp_dir(), 'icv_chunk_').'.pdf';
            file_put_contents($tempPath, $chunkPdf->output());

            unset($chunkPdf);
            gc_collect_cycles();

            $tempFiles = $batch['temp_files'] ?? [];
            $tempFiles[] = $tempPath;

            ExportBatchService::updateBatch($token, [
                'temp_files' => $tempFiles,
                'completed_chunks' => $chunkIndex + 1,
            ]);
        } elseif ($format === 'excel') {
            $lines = '';
            foreach ($chunkData as $record) {
                $row = $isLengkap ? self::mapRowLengkap($record) : self::mapRowRingkas($record);
                $lines .= json_encode($row)."\n";
            }

            $tempFiles = $batch['temp_files'] ?? [];
            $jsonlPath = $tempFiles[0] ?? null;
            if (! $jsonlPath) {
                $jsonlPath = tempnam(sys_get_temp_dir(), 'icv_rows_').'.jsonl';
                $tempFiles = [$jsonlPath];
            }
            file_put_contents($jsonlPath, $lines, FILE_APPEND);

            ExportBatchService::updateBatch($token, [
                'temp_files' => $tempFiles,
                'completed_chunks' => $chunkIndex + 1,
            ]);
        }

        $processedRecords = min(($chunkIndex + 1) * $chunkSize, $totalCount);
        $percent = (int) round((($chunkIndex + 1) / $totalChunks) * 85);
        $message = sprintf(
            'Memproses bagian %d dari %d (%d dari %d data)...',
            $chunkIndex + 1,
            $totalChunks,
            $processedRecords,
            $totalCount
        );

        return [
            'success' => true,
            'chunk' => $chunkIndex + 1,
            'totalChunks' => $totalChunks,
            'percent' => $percent,
            'message' => $message,
        ];
    }

    /**
     * Finalize the batch export for ICV.
     */
    public static function finalizeBatch(string $token): array
    {
        $batch = ExportBatchService::getBatch($token);
        if (! $batch) {
            throw new \RuntimeException('Sesi ekspor tidak ditemukan atau sudah kadaluarsa.');
        }

        $format = $batch['format'];
        $isLengkap = ($batch['tipe_laporan'] ?? 'lengkap') === 'lengkap';
        $tempFiles = $batch['temp_files'] ?? [];

        if ($format === 'pdf') {
            $filename = 'laporan_pengajuan_icv_'.($isLengkap ? 'lengkap_' : 'ringkas_').date('Y-m-d_H-i-s').'.pdf';
            $finalPath = tempnam(sys_get_temp_dir(), 'icv_final_').'.pdf';

            $fpdi = new Fpdi;
            foreach ($tempFiles as $file) {
                if (file_exists($file)) {
                    $pageCount = $fpdi->setSourceFile($file);
                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $templateId = $fpdi->importPage($pageNo);
                        $size = $fpdi->getTemplateSize($templateId);
                        $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                        $fpdi->useTemplate($templateId);
                    }
                }
            }
            $fpdi->Output('F', $finalPath);

            // Clean up chunk files
            ExportBatchService::cleanupTempFiles($tempFiles);

            ExportBatchService::updateBatch($token, [
                'final_file' => $finalPath,
                'final_name' => $filename,
                'temp_files' => [],
            ]);
        } elseif ($format === 'excel') {
            $filename = 'laporan_pengajuan_icv_'.($isLengkap ? 'lengkap_' : 'ringkas_').date('Y-m-d_H-i-s').'.xlsx';
            $finalPath = tempnam(sys_get_temp_dir(), 'icv_final_').'.xlsx';

            $jsonlPath = $tempFiles[0] ?? null;
            $allRows = [];
            if ($jsonlPath && file_exists($jsonlPath)) {
                $file = fopen($jsonlPath, 'r');
                while (($line = fgets($file)) !== false) {
                    $row = json_decode(trim($line), true);
                    if ($row) {
                        $allRows[] = $row;
                    }
                }
                fclose($file);
                @unlink($jsonlPath);
            }

            $spreadsheet = self::buildSpreadsheetFromRows($allRows, $batch['tipe_laporan'] ?? 'lengkap');
            $writer = new Xlsx($spreadsheet);
            $writer->save($finalPath);

            ExportBatchService::updateBatch($token, [
                'final_file' => $finalPath,
                'final_name' => $filename,
                'temp_files' => [],
            ]);
        }

        return [
            'success' => true,
            'percent' => 100,
            'message' => 'Selesai! Mengunduh dokumen...',
            'downloadUrl' => route('export.download', ['token' => $token]),
            'filename' => $batch['final_name'] ?? 'laporan.pdf',
        ];
    }

    /**
     * Generate streamed CSV response with UTF-8 BOM and chunking support.
     */
    public static function downloadCsv(Collection|Builder $dataOrQuery, array $params)
    {
        $isLengkap = ($params['tipe_laporan'] ?? 'lengkap') === 'lengkap';
        $headers = $isLengkap ? self::getHeadersLengkap() : self::getHeadersRingkas();
        $filename = 'laporan_pengajuan_icv_'.($isLengkap ? 'lengkap_' : 'ringkas_').date('Y-m-d_H-i-s').'.csv';

        $callback = function () use ($dataOrQuery, $headers, $isLengkap) {
            $output = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // Write headers
            fputcsv($output, $headers);

            if ($dataOrQuery instanceof Builder) {
                (clone $dataOrQuery)->chunk(100, function ($chunk) use ($output, $isLengkap) {
                    foreach ($chunk as $record) {
                        $row = $isLengkap ? self::mapRowLengkap($record) : self::mapRowRingkas($record);
                        fputcsv($output, $row);
                    }
                });
            } else {
                foreach ($dataOrQuery as $record) {
                    $row = $isLengkap ? self::mapRowLengkap($record) : self::mapRowRingkas($record);
                    fputcsv($output, $row);
                }
            }

            fclose($output);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Transfer-Encoding' => 'binary',
            'Expires' => '0',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Generate PDF response with chunking and FPDI merge support.
     */
    public static function downloadPdf(Collection|Builder $dataOrQuery, array $params)
    {
        $isLengkap = ($params['tipe_laporan'] ?? 'lengkap') === 'lengkap';
        $view = $isLengkap ? 'reports.laporan-icv-detail' : 'reports.laporan-icv';
        $filename = 'laporan_pengajuan_icv_'.($isLengkap ? 'lengkap_' : 'ringkas_').date('Y-m-d_H-i-s').'.pdf';

        $totalCount = ($dataOrQuery instanceof Builder) ? (clone $dataOrQuery)->count() : $dataOrQuery->count();
        $chunkSize = 25;

        // If dataset is small (<= 25 items) or already a Collection, render directly in single pass
        if ($totalCount <= $chunkSize || ! ($dataOrQuery instanceof Builder)) {
            $data = ($dataOrQuery instanceof Builder) ? (clone $dataOrQuery)->get() : $dataOrQuery;

            $pdf = Pdf::loadView($view, [
                'data' => $data,
                'params' => $params,
                'showHeader' => true,
                'chunkOffset' => 0,
                'total_data' => $totalCount,
                'tanggal_mulai' => ! empty($params['tanggal_mulai']) ? Carbon::parse($params['tanggal_mulai'])->format('d/m/Y') : '-',
                'tanggal_selesai' => ! empty($params['tanggal_selesai']) ? Carbon::parse($params['tanggal_selesai'])->format('d/m/Y') : '-',
                'progress_status' => $params['progress_status'] ?? 'semua',
                'kotakab' => $params['kotakab'] ?? null,
                'user_id' => $params['user_id'] ?? null,
                'tipe_laporan' => $params['tipe_laporan'] ?? 'lengkap',
            ]);

            $pdf->setPaper('a4', $isLengkap ? 'portrait' : 'landscape');

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        }

        // Chunking & Merging using FPDI for large datasets
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        $tempFiles = [];
        $chunkIndex = 0;

        (clone $dataOrQuery)->chunk($chunkSize, function ($chunk) use (
            $view, $params, $isLengkap, $totalCount, $chunkSize, &$tempFiles, &$chunkIndex
        ) {
            $isFirst = ($chunkIndex === 0);
            $chunkPdf = Pdf::loadView($view, [
                'data' => $chunk,
                'params' => $params,
                'showHeader' => $isFirst,
                'chunkOffset' => $chunkIndex * $chunkSize,
                'total_data' => $totalCount,
                'tanggal_mulai' => ! empty($params['tanggal_mulai']) ? Carbon::parse($params['tanggal_mulai'])->format('d/m/Y') : '-',
                'tanggal_selesai' => ! empty($params['tanggal_selesai']) ? Carbon::parse($params['tanggal_selesai'])->format('d/m/Y') : '-',
                'progress_status' => $params['progress_status'] ?? 'semua',
                'kotakab' => $params['kotakab'] ?? null,
                'user_id' => $params['user_id'] ?? null,
                'tipe_laporan' => $params['tipe_laporan'] ?? 'lengkap',
            ]);

            $chunkPdf->setPaper('a4', $isLengkap ? 'portrait' : 'landscape');

            $tempPath = tempnam(sys_get_temp_dir(), 'icv_chunk_').'.pdf';
            file_put_contents($tempPath, $chunkPdf->output());
            $tempFiles[] = $tempPath;

            unset($chunkPdf);
            gc_collect_cycles();
            $chunkIndex++;
        });

        // Merge all chunk PDFs using FPDI
        $fpdi = new Fpdi;
        foreach ($tempFiles as $file) {
            $pageCount = $fpdi->setSourceFile($file);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $fpdi->importPage($pageNo);
                $size = $fpdi->getTemplateSize($templateId);
                $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $fpdi->useTemplate($templateId);
            }
        }

        $mergedTempPath = tempnam(sys_get_temp_dir(), 'icv_merged_').'.pdf';
        $fpdi->Output('F', $mergedTempPath);

        return response()->streamDownload(function () use ($mergedTempPath, $tempFiles) {
            $stream = fopen($mergedTempPath, 'rb');
            if ($stream) {
                fpassthru($stream);
                fclose($stream);
            }
            @unlink($mergedTempPath);
            foreach ($tempFiles as $tf) {
                @unlink($tf);
            }
        }, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
