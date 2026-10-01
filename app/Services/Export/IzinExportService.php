<?php

namespace App\Services\Export;

use App\Models\Izin;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use setasign\Fpdi\Fpdi;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IzinExportService
{
    /**
     * Main entry point to export pengajuan izin data.
     */
    public static function export(array $params): mixed
    {
        $query = self::query($params);
        $format = $params['format'] ?? 'excel';

        return match ($format) {
            'excel' => self::downloadExcel($query, $params),
            'csv' => self::downloadCsv($query, $params),
            'pdf' => self::downloadPdf($query, $params),
            default => self::downloadExcel($query, $params),
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

        return ExportBatchService::createBatch('izin', $params, $count, $chunkSize);
    }

    /**
     * Build the query with all eager loading and filters.
     */
    public static function query(array $params): Builder
    {
        $query = Izin::with([
            'user.suratPersetujuanIcv',
            'izinDokumen',
            'izinVisitasi',
            'izinPersetujuan',
            'izinDokumenPks',
        ]);

        // Date range filter
        if (! empty($params['tanggal_mulai']) && ! empty($params['tanggal_selesai'])) {
            $query->whereBetween('tanggal_pengajuan', [$params['tanggal_mulai'], $params['tanggal_selesai']]);
        } elseif (! empty($params['tanggal_mulai'])) {
            $query->whereDate('tanggal_pengajuan', '>=', $params['tanggal_mulai']);
        } elseif (! empty($params['tanggal_selesai'])) {
            $query->whereDate('tanggal_pengajuan', '<=', $params['tanggal_selesai']);
        }

        // Kategori Izin filter
        if (! empty($params['kategori_izin']) && $params['kategori_izin'] !== 'semua') {
            $query->where('kategori_izin', $params['kategori_izin']);
        }

        // Status Progress filter
        if (! empty($params['status_progress']) && $params['status_progress'] !== 'semua') {
            switch ($params['status_progress']) {
                case 'dokumen_diajukan':
                    $query->whereDoesntHave('izinDokumen');
                    break;
                case 'menunggu_verifikasi':
                    $query->whereHas('izinDokumen', fn ($q) => $q->where('hasil', 0));
                    break;
                case 'berkas_lengkap':
                    $query->whereHas('izinDokumen', fn ($q) => $q->where('hasil', 1));
                    break;
                case 'berkas_tidak_lengkap':
                    $query->whereHas('izinDokumen', fn ($q) => $q->where('hasil', 2));
                    break;
                case 'visitasi_dijadwalkan':
                    $query->where(function ($q) {
                        $q->whereHas('izinVisitasi', fn ($sub) => $sub->whereNotNull('tanggal_visitasi'))
                            ->orWhereHas('izinDokumenPks', fn ($sub) => $sub->whereNotNull('tanggal_visitasi'));
                    });
                    break;
                case 'pks_tersedia':
                    $query->whereHas('izinDokumenPks', fn ($q) => $q->whereNotNull('dokumen_pks_bkk'));
                    break;
                case 'disetujui':
                case 'selesai':
                    $query->whereHas('izinPersetujuan', fn ($q) => $q->whereNotNull('file_persetujuan'));
                    break;
            }
        }

        // Kota/Kabupaten filter (filtered on user.kotakab)
        if (! empty($params['kotakab']) && $params['kotakab'] !== 'semua') {
            $query->whereHas('user', fn ($q) => $q->where('kotakab', $params['kotakab']));
        }

        // User / Faskes filter
        if (! empty($params['user_id']) && $params['user_id'] !== 'semua') {
            $query->where('user_id', $params['user_id']);
        }

        return $query->orderBy('tanggal_pengajuan', 'desc');
    }

    /**
     * Determine readable progress status string from record.
     */
    public static function getProgressStatus(Izin $record): string
    {
        // 1. Persetujuan selesai (final step)
        if ($record->izinPersetujuan && $record->izinPersetujuan->file_persetujuan) {
            return 'Selesai';
        }

        // 2. PKS tersedia
        if ($record->izinDokumenPks && $record->izinDokumenPks->dokumen_pks_bkk) {
            return 'PKS Tersedia - Menunggu Persetujuan';
        }

        // 3. Visitasi dijadwalkan
        if ($record->izinVisitasi && $record->izinVisitasi->tanggal_visitasi) {
            return 'Visitasi Dijadwalkan';
        }

        if ($record->izinDokumenPks && $record->izinDokumenPks->tanggal_visitasi) {
            return 'Visitasi Dijadwalkan';
        }

        // 4. Verifikasi dokumen
        if ($record->izinDokumen) {
            return match ((int) $record->izinDokumen->hasil) {
                1 => 'Berkas Lengkap',
                2 => 'Berkas Tidak Lengkap',
                3 => 'Ditolak',
                default => 'Menunggu Verifikasi',
            };
        }

        return 'Dokumen Diajukan';
    }

    /**
     * Format riwayat perbaikan array/json as string: [d/m/Y H:i] Catatan\n...
     */
    public static function formatRiwayatPerbaikan(mixed $riwayat): string
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
                $tanggal = ! empty($item['tanggal'])
                    ? Carbon::parse($item['tanggal'])->format('d/m/Y H:i')
                    : '-';
                $catatan = trim($item['catatan'] ?? '');
                $lines[] = "[{$tanggal}] {$catatan}";
            } elseif (is_string($item)) {
                $lines[] = trim($item);
            }
        }

        return ! empty($lines) ? implode("\n", $lines) : '-';
    }

    /**
     * Column headers for 'lengkap' (26 columns).
     */
    public static function getLengkapColumns(): array
    {
        return [
            'ID',
            'Tanggal',
            'Nama Faskes',
            'Kategori Faskes',
            'Kota/Kab',
            'Alamat',
            'PJ Akun',
            'Kontak PJ',
            'No Izin Operasional',
            'Masa Berlaku Izin Operasional',
            'Kategori Izin',
            'No Surat',
            'Tanggal Surat',
            'Status Progress',
            'Verifikasi Dokumen Hasil',
            'Catatan Dokumen',
            'Jadwal Visitasi',
            'Status Visitasi',
            'Catatan Visitasi',
            'No PKS',
            'Tanggal PKS',
            'No Persetujuan',
            'Masa Berlaku Persetujuan',
            'Riwayat Perbaikan',
            'Tanggal Update',
        ];
    }

    /**
     * Column headers for 'ringkas' (core columns).
     */
    public static function getRingkasColumns(): array
    {
        return [
            'ID',
            'Tanggal Pengajuan',
            'Nama Faskes',
            'Kategori Faskes',
            'Kota/Kab',
            'Kategori Izin',
            'No Surat',
            'Tanggal Surat',
            'Status Progress',
            'Verifikasi Dokumen',
            'Jadwal Visitasi',
            'PKS / Persetujuan',
            'Tanggal Update',
        ];
    }

    /**
     * Map record values for 'lengkap' (26 columns).
     */
    public static function mapRowLengkap(Izin $row): array
    {
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
            ? Carbon::parse($pks->date_updated)->format('d/m/Y')
            : '-';

        // Persetujuan
        $noPersetujuan = $persetujuanIcv?->nomor_surat
            ?? ($persetujuan?->file_persetujuan ? basename($persetujuan->file_persetujuan) : '-');
        $masaBerlakuPersetujuan = $persetujuanIcv?->masa_berlaku
            ? Carbon::parse($persetujuanIcv->masa_berlaku)->format('d/m/Y')
            : '-';

        // Masa berlaku izin operasional faskes
        $masaBerlakuIzinOps = '-';
        if ($user?->masa_berlaku_surat_izin_operasional) {
            $val = $user->masa_berlaku_surat_izin_operasional;
            $masaBerlakuIzinOps = (is_string($val) && strtotime($val))
                ? Carbon::parse($val)->format('d/m/Y')
                : (string) $val;
        }

        return [
            $row->id,
            $row->tanggal_pengajuan ? Carbon::parse($row->tanggal_pengajuan)->format('d/m/Y') : '-',
            $user?->name ?? '-',
            $user?->kategori ?? '-',
            $user?->kotakab ?? '-',
            $user?->alamat ?? '-',
            $user?->pj_akun ?? '-',
            $user?->kontak_pj ?? '-',
            $user?->nomor_surat_izin_operasional ?? '-',
            $masaBerlakuIzinOps,
            $row->kategori_izin ?? '-',
            $row->nomor_surat ?? '-',
            $row->tanggal_surat ? Carbon::parse($row->tanggal_surat)->format('d/m/Y') : '-',
            self::getProgressStatus($row),
            $dokumen ? $dokumen->getHasilLabel() : 'Belum Diverifikasi',
            $dokumen?->catatan ?: '-',
            $tglVisitasi ? Carbon::parse($tglVisitasi)->format('d/m/Y') : '-',
            $statusVisitasi,
            $visitasi?->catatan ?: '-',
            $noPks,
            $tglPks,
            $noPersetujuan,
            $masaBerlakuPersetujuan,
            self::formatRiwayatPerbaikan($row->riwayat_perbaikan),
            $row->date_updated ? Carbon::parse($row->date_updated)->format('d/m/Y H:i') : '-',
        ];
    }

    /**
     * Map record values for 'ringkas'.
     */
    public static function mapRowRingkas(Izin $row): array
    {
        $user = $row->user;
        $dokumen = $row->izinDokumen;
        $visitasi = $row->izinVisitasi;
        $pks = $row->izinDokumenPks;
        $persetujuan = $row->izinPersetujuan;

        $tglVisitasi = $visitasi?->tanggal_visitasi ?? $pks?->tanggal_visitasi;
        $statusPksPersetujuan = '-';
        if ($persetujuan && $persetujuan->file_persetujuan) {
            $statusPksPersetujuan = 'Persetujuan Selesai';
        } elseif ($pks && $pks->dokumen_pks_bkk) {
            $statusPksPersetujuan = 'PKS Tersedia';
        }

        return [
            $row->id,
            $row->tanggal_pengajuan ? Carbon::parse($row->tanggal_pengajuan)->format('d/m/Y') : '-',
            $user?->name ?? '-',
            $user?->kategori ?? '-',
            $user?->kotakab ?? '-',
            $row->kategori_izin ?? '-',
            $row->nomor_surat ?? '-',
            $row->tanggal_surat ? Carbon::parse($row->tanggal_surat)->format('d/m/Y') : '-',
            self::getProgressStatus($row),
            $dokumen ? $dokumen->getHasilLabel() : 'Belum Diverifikasi',
            $tglVisitasi ? Carbon::parse($tglVisitasi)->format('d/m/Y') : '-',
            $statusPksPersetujuan,
            $row->date_updated ? Carbon::parse($row->date_updated)->format('d/m/Y H:i') : '-',
        ];
    }

    /**
     * Download as styled native Excel (.xlsx).
     */
    public static function downloadExcel(Collection|Builder $dataOrQuery, array $params): StreamedResponse
    {
        $tipe = $params['tipe_laporan'] ?? 'lengkap';
        $filename = 'laporan_pengajuan_izin_'.$tipe.'_'.date('Y-m-d_H-i-s').'.xlsx';
        $isLengkap = ($tipe === 'lengkap');
        $totalCount = ($dataOrQuery instanceof Builder) ? (clone $dataOrQuery)->count() : $dataOrQuery->count();

        $allRows = [];
        if ($dataOrQuery instanceof Builder) {
            (clone $dataOrQuery)->chunk(100, function ($chunk) use (&$allRows, $isLengkap) {
                foreach ($chunk as $row) {
                    $allRows[] = $isLengkap ? self::mapRowLengkap($row) : self::mapRowRingkas($row);
                }
            });
        } else {
            foreach ($dataOrQuery as $row) {
                $allRows[] = $isLengkap ? self::mapRowLengkap($row) : self::mapRowRingkas($row);
            }
        }

        $spreadsheet = self::buildSpreadsheetFromRows($allRows, $params, $totalCount, $tipe);
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Build and style a Spreadsheet from row arrays.
     */
    public static function buildSpreadsheetFromRows(array $allRows, array $params, int $totalCount, string $tipe = 'lengkap'): Spreadsheet
    {
        $isLengkap = ($tipe === 'lengkap');
        $columns = $isLengkap ? self::getLengkapColumns() : self::getRingkasColumns();
        $totalCols = count($columns);
        $lastColLetter = self::columnLetter($totalCols);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($isLengkap ? 'Laporan Lengkap' : 'Laporan Ringkas');

        // Document Properties
        $spreadsheet->getProperties()
            ->setCreator('BKK Kelas I Bandung')
            ->setTitle('Laporan Pengajuan Izin')
            ->setSubject('Pengajuan Izin Klinik/Faskes');

        // Title Block
        $sheet->setCellValue('A1', 'LAPORAN PENGAJUAN IZIN OPERASIONAL KLINIK');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color('FF1E3A8A'));

        $tglMulai = ! empty($params['tanggal_mulai']) ? Carbon::parse($params['tanggal_mulai'])->format('d/m/Y') : '-';
        $tglSelesai = ! empty($params['tanggal_selesai']) ? Carbon::parse($params['tanggal_selesai'])->format('d/m/Y') : '-';
        $sheet->setCellValue('A2', "Periode: {$tglMulai} s/d {$tglSelesai} | Tipe: ".($isLengkap ? 'Lengkap (Semua Detail & Riwayat)' : 'Ringkas'));
        $sheet->getStyle('A2')->getFont()->setSize(10)->setItalic(true)->setColor(new Color('FF475569'));

        // Filter details
        $filters = [];
        if (! empty($params['kategori_izin']) && $params['kategori_izin'] !== 'semua') {
            $filters[] = 'Kategori: '.$params['kategori_izin'];
        }
        if (! empty($params['status_progress']) && $params['status_progress'] !== 'semua') {
            $filters[] = 'Status: '.ucwords(str_replace('_', ' ', $params['status_progress']));
        }
        if (! empty($params['kotakab']) && $params['kotakab'] !== 'semua') {
            $filters[] = 'Kota/Kab: '.$params['kotakab'];
        }
        if (! empty($params['user_id']) && $params['user_id'] !== 'semua') {
            $userName = User::find($params['user_id'])?->name;
            if ($userName) {
                $filters[] = 'Faskes: '.$userName;
            }
        }
        $filterText = ! empty($filters) ? implode(' | ', $filters) : 'Filter: Semua Data';
        $sheet->setCellValue('A3', $filterText.' | Total: '.$totalCount.' Pengajuan | Dicetak: '.now()->format('d/m/Y H:i'));
        $sheet->getStyle('A3')->getFont()->setSize(9)->setColor(new Color('FF64748B'));

        // Table Header
        $headerRow = 5;
        $sheet->getRowDimension($headerRow)->setRowHeight(30);

        foreach ($columns as $idx => $colName) {
            $colLetter = self::columnLetter($idx + 1);
            $sheet->setCellValue("{$colLetter}{$headerRow}", $colName);
        }

        $headerRange = "A{$headerRow}:{$lastColLetter}{$headerRow}";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E40AF'], // Rich Royal Blue
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF93C5FD'],
                ],
            ],
        ]);

        $textColumns = [
            'Kontak PJ',
            'No Izin Operasional',
            'No Surat',
            'No PKS',
            'No Persetujuan',
        ];

        $centerColumns = [
            'ID',
            'Tanggal',
            'Tanggal Pengajuan',
            'Kota/Kab',
            'Kontak PJ',
            'Masa Berlaku Izin Operasional',
            'Kategori Izin',
            'Tanggal Surat',
            'Status Progress',
            'Verifikasi Dokumen Hasil',
            'Jadwal Visitasi',
            'Status Visitasi',
            'Tanggal PKS',
            'Masa Berlaku Persetujuan',
            'Tanggal Update',
        ];

        // Data Rows
        $currentRow = $headerRow + 1;
        foreach ($allRows as $index => $rowData) {
            foreach ($rowData as $colIdx => $value) {
                $colLetter = self::columnLetter($colIdx + 1);
                $colName = $columns[$colIdx] ?? '';

                if (in_array($colName, $textColumns) && $value !== '-' && $value !== null && $value !== '') {
                    $sheet->setCellValueExplicit("{$colLetter}{$currentRow}", (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->getStyle("{$colLetter}{$currentRow}")->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
                } else {
                    $sheet->setCellValue("{$colLetter}{$currentRow}", $value);
                }

                if (in_array($colName, $centerColumns)) {
                    $sheet->getStyle("{$colLetter}{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            }

            // Zebra striping
            $rowBg = ($index % 2 === 0) ? 'FFFFFFFF' : 'FFF8FAFC';
            $sheet->getStyle("A{$currentRow}:{$lastColLetter}{$currentRow}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => $rowBg],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FFE2E8F0'],
                    ],
                ],
                'font' => [
                    'size' => 9.5,
                ],
            ]);

            $currentRow++;
        }

        // Set column widths intelligently
        foreach ($columns as $idx => $colName) {
            $colLetter = self::columnLetter($idx + 1);
            if (in_array($colName, ['Alamat', 'Catatan Dokumen', 'No PKS', 'No Persetujuan'])) {
                $sheet->getColumnDimension($colLetter)->setAutoSize(false)->setWidth(35);
            } elseif ($colName === 'Catatan Visitasi') {
                $sheet->getColumnDimension($colLetter)->setAutoSize(false)->setWidth(30);
            } elseif ($colName === 'Riwayat Perbaikan') {
                $sheet->getColumnDimension($colLetter)->setAutoSize(false)->setWidth(45);
            } else {
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }
        }

        return $spreadsheet;
    }

    /**
     * Process a single chunk in a batch export.
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
        $tipe = $batch['tipe_laporan'];

        $offset = $chunkIndex * $chunkSize;
        $chunkData = self::query($params)->skip($offset)->take($chunkSize)->get();

        if ($format === 'pdf') {
            $viewName = ($tipe === 'lengkap')
                ? 'reports.laporan-izin-detail'
                : 'reports.laporan-izin';

            $userFilter = null;
            if (! empty($params['user_id']) && $params['user_id'] !== 'semua') {
                $userFilter = User::find($params['user_id'])?->name;
            }

            $isFirst = ($chunkIndex === 0);
            $chunkPdf = Pdf::loadView($viewName, [
                'data' => $chunkData,
                'params' => $params,
                'showHeader' => $isFirst,
                'chunkOffset' => $offset,
                'total_data' => $totalCount,
                'tanggal_mulai' => $params['tanggal_mulai'] ?? null,
                'tanggal_selesai' => $params['tanggal_selesai'] ?? null,
                'kategori_izin' => $params['kategori_izin'] ?? 'semua',
                'status_progress' => $params['status_progress'] ?? 'semua',
                'kotakab' => $params['kotakab'] ?? 'semua',
                'user_nama' => $userFilter ?? 'Semua',
                'tipe_laporan' => $tipe,
            ]);

            if ($tipe === 'lengkap') {
                $chunkPdf->setPaper('a4', 'portrait');
            } else {
                $chunkPdf->setPaper('a4', 'landscape');
            }

            $tempPath = tempnam(sys_get_temp_dir(), 'izin_chunk_').'.pdf';
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
            $isLengkap = ($tipe === 'lengkap');
            $lines = '';
            foreach ($chunkData as $row) {
                $rowData = $isLengkap ? self::mapRowLengkap($row) : self::mapRowRingkas($row);
                $lines .= json_encode($rowData)."\n";
            }

            $tempFiles = $batch['temp_files'] ?? [];
            $jsonlPath = $tempFiles[0] ?? null;
            if (! $jsonlPath) {
                $jsonlPath = tempnam(sys_get_temp_dir(), 'izin_rows_').'.jsonl';
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
     * Finalize the batch export (merge PDFs or build Excel file).
     */
    public static function finalizeBatch(string $token): array
    {
        $batch = ExportBatchService::getBatch($token);
        if (! $batch) {
            throw new \RuntimeException('Sesi ekspor tidak ditemukan atau sudah kadaluarsa.');
        }

        $format = $batch['format'];
        $tipe = $batch['tipe_laporan'];
        $params = $batch['params'];
        $totalCount = $batch['total_count'];
        $tempFiles = $batch['temp_files'] ?? [];

        if ($format === 'pdf') {
            $filename = 'laporan_pengajuan_izin_'.$tipe.'_'.date('Y-m-d_H-i-s').'.pdf';
            $finalPath = tempnam(sys_get_temp_dir(), 'izin_final_').'.pdf';

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
            $filename = 'laporan_pengajuan_izin_'.$tipe.'_'.date('Y-m-d_H-i-s').'.xlsx';
            $finalPath = tempnam(sys_get_temp_dir(), 'izin_final_').'.xlsx';

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

            $spreadsheet = self::buildSpreadsheetFromRows($allRows, $params, $totalCount, $tipe);
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
     * Download as streamed CSV with UTF-8 BOM.
     */
    public static function downloadCsv(Collection|Builder $dataOrQuery, array $params): StreamedResponse
    {
        $tipe = $params['tipe_laporan'] ?? 'lengkap';
        $filename = 'laporan_pengajuan_izin_'.$tipe.'_'.date('Y-m-d_H-i-s').'.csv';

        $isLengkap = ($tipe === 'lengkap');
        $columns = $isLengkap ? self::getLengkapColumns() : self::getRingkasColumns();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        $callback = function () use ($dataOrQuery, $columns, $isLengkap) {
            $output = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // Write header row
            fputcsv($output, $columns);

            if ($dataOrQuery instanceof Builder) {
                (clone $dataOrQuery)->chunk(100, function ($chunk) use ($output, $isLengkap) {
                    foreach ($chunk as $row) {
                        $values = $isLengkap ? self::mapRowLengkap($row) : self::mapRowRingkas($row);
                        fputcsv($output, $values);
                    }
                });
            } else {
                foreach ($dataOrQuery as $row) {
                    $values = $isLengkap ? self::mapRowLengkap($row) : self::mapRowRingkas($row);
                    fputcsv($output, $values);
                }
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Download as PDF document with chunking and FPDI merge support.
     */
    public static function downloadPdf(Collection|Builder $dataOrQuery, array $params): StreamedResponse
    {
        $tipe = $params['tipe_laporan'] ?? 'lengkap';
        $filename = 'laporan_pengajuan_izin_'.$tipe.'_'.date('Y-m-d_H-i-s').'.pdf';

        $viewName = ($tipe === 'lengkap')
            ? 'reports.laporan-izin-detail'
            : 'reports.laporan-izin';

        $userFilter = null;
        if (! empty($params['user_id']) && $params['user_id'] !== 'semua') {
            $userFilter = User::find($params['user_id'])?->name;
        }

        $totalCount = ($dataOrQuery instanceof Builder) ? (clone $dataOrQuery)->count() : $dataOrQuery->count();
        $chunkSize = 25;

        // If dataset is small (<= 25 items) or already a Collection, render directly in single pass
        if ($totalCount <= $chunkSize || ! ($dataOrQuery instanceof Builder)) {
            $data = ($dataOrQuery instanceof Builder) ? (clone $dataOrQuery)->get() : $dataOrQuery;

            $pdf = Pdf::loadView($viewName, [
                'data' => $data,
                'params' => $params,
                'showHeader' => true,
                'chunkOffset' => 0,
                'total_data' => $totalCount,
                'tanggal_mulai' => $params['tanggal_mulai'] ?? null,
                'tanggal_selesai' => $params['tanggal_selesai'] ?? null,
                'kategori_izin' => $params['kategori_izin'] ?? 'semua',
                'status_progress' => $params['status_progress'] ?? 'semua',
                'kotakab' => $params['kotakab'] ?? 'semua',
                'user_nama' => $userFilter ?? 'Semua',
                'tipe_laporan' => $tipe,
            ]);

            if ($tipe === 'lengkap') {
                $pdf->setPaper('a4', 'portrait');
            } else {
                $pdf->setPaper('a4', 'landscape');
            }

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
            $viewName, $params, $tipe, $userFilter, $totalCount, $chunkSize, &$tempFiles, &$chunkIndex
        ) {
            $isFirst = ($chunkIndex === 0);
            $chunkPdf = Pdf::loadView($viewName, [
                'data' => $chunk,
                'params' => $params,
                'showHeader' => $isFirst,
                'chunkOffset' => $chunkIndex * $chunkSize,
                'total_data' => $totalCount,
                'tanggal_mulai' => $params['tanggal_mulai'] ?? null,
                'tanggal_selesai' => $params['tanggal_selesai'] ?? null,
                'kategori_izin' => $params['kategori_izin'] ?? 'semua',
                'status_progress' => $params['status_progress'] ?? 'semua',
                'kotakab' => $params['kotakab'] ?? 'semua',
                'user_nama' => $userFilter ?? 'Semua',
                'tipe_laporan' => $tipe,
            ]);

            if ($tipe === 'lengkap') {
                $chunkPdf->setPaper('a4', 'portrait');
            } else {
                $chunkPdf->setPaper('a4', 'landscape');
            }

            $tempPath = tempnam(sys_get_temp_dir(), 'izin_chunk_').'.pdf';
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

        $mergedTempPath = tempnam(sys_get_temp_dir(), 'izin_merged_').'.pdf';
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

    /**
     * Helper to convert 1-based column number to Excel column letter (e.g. 1 => A, 26 => Z, 27 => AA).
     */
    private static function columnLetter(int $colNumber): string
    {
        $letter = '';
        while ($colNumber > 0) {
            $mod = ($colNumber - 1) % 26;
            $letter = chr(65 + $mod).$letter;
            $colNumber = (int) (($colNumber - $mod) / 26);
        }

        return $letter;
    }
}
