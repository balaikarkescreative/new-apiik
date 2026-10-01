<?php

namespace App\Http\Controllers;

use App\Services\Export\ExportBatchService;
use App\Services\Export\IcvExportService;
use App\Services\Export\IzinExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportBatchController extends Controller
{
    /**
     * Process a single chunk for batch export.
     */
    public function processChunk(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'chunk_index' => 'required|integer|min:0',
        ]);

        $token = $request->input('token');
        $chunkIndex = (int) $request->input('chunk_index');

        $batch = ExportBatchService::getBatch($token);
        if (! $batch) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi ekspor tidak ditemukan atau sudah kadaluarsa.',
            ], 404);
        }

        try {
            $type = $batch['type'] ?? 'izin';
            $result = ($type === 'icv')
                ? IcvExportService::processBatchChunk($token, $chunkIndex)
                : IzinExportService::processBatchChunk($token, $chunkIndex);

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses bagian dokumen: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Finalize batch export (merge PDFs or finalize spreadsheet).
     */
    public function finalize(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $token = $request->input('token');
        $batch = ExportBatchService::getBatch($token);

        if (! $batch) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi ekspor tidak ditemukan atau sudah kadaluarsa.',
            ], 404);
        }

        try {
            $type = $batch['type'] ?? 'izin';
            $result = ($type === 'icv')
                ? IcvExportService::finalizeBatch($token)
                : IzinExportService::finalizeBatch($token);

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menggabungkan dokumen: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel batch export and clean up temporary files.
     */
    public function cancel(Request $request): JsonResponse
    {
        $token = $request->input('token');
        if ($token) {
            ExportBatchService::deleteBatch($token);
        }

        return response()->json([
            'success' => true,
            'message' => 'Proses ekspor berhasil dibatalkan.',
        ]);
    }

    /**
     * Download the finalized export file.
     */
    public function download(string $token): BinaryFileResponse|JsonResponse
    {
        $batch = ExportBatchService::getBatch($token);

        if (! $batch || empty($batch['final_file']) || ! file_exists($batch['final_file'])) {
            return response()->json([
                'success' => false,
                'message' => 'Berkas hasil ekspor tidak ditemukan atau sudah kadaluarsa.',
            ], 404);
        }

        $filePath = $batch['final_file'];
        $filename = $batch['final_name'] ?? 'laporan.pdf';

        return response()->download($filePath, $filename)->deleteFileAfterSend(true);
    }
}
