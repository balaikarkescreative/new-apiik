<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

use App\Models\User;
use App\Models\IcvCekDokumen;
use App\Models\IcvAlokasi;
use App\Models\IcvBilling;
use App\Models\IcvBuktiBayar;
use App\Models\IcvJadwal;
use App\Models\IcvSbbk;

class Icv extends Model
{
    use HasFactory;

    protected $table = "tbl_icv";

    protected $fillable = [
        "user_id",
        "tanggal_pengajuan",
        "jumlah_icv",
        "file1",
        "file2",
        "file3",
        "file4",
        "file5",
        "file6",
        "file7",
        "status",
        "tanggal_perbaikan",
        "riwayat_perbaikan", // Tambahkan kolom baru
        "date_updated",
    ];

    protected $casts = [
        "tanggal_pengajuan" => "date",
        "tanggal_perbaikan" => "datetime",
        "riwayat_perbaikan" => "array", // Cast JSON ke array
        "jumlah_icv" => "integer",
        "status" => "integer",
        "date_updated" => "datetime",
    ];

    protected $dates = ["tanggal_pengajuan", "tanggal_perbaikan", "date_updated"]; // Tambahkan tanggal_perbaikan ke dates

    public const UPDATED_AT = "date_updated";
    public const CREATED_AT = null;

    /**
     * Status constants
     */
    public const STATUS_PENDING = 0; // Pending
    public const STATUS_APPROVED = 1; // Approved
    public const STATUS_REJECTED = 2; // Rejected

    /**
     * File constants
     */
    public const FILE_FIELDS = [
        "file1",
        "file2",
        "file3",
        "file4",
        "file5",
        "file6",
        "file7",
    ];

    /**
     * Relationship ke User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id", "id");
    }

    public function icvCekDokumen(): HasOne
    {
        return $this->hasOne(IcvCekDokumen::class, "id_icv", "id");
    }

    /**
     * Relationship ke IcvAlokasi
     */
    public function icvAlokasi(): HasOne
    {
        return $this->hasOne(IcvAlokasi::class, "id_icv", "id");
    }

    /**
     * Relationship ke IcvBilling
     */
    public function icvBilling(): HasOne
    {
        return $this->hasOne(IcvBilling::class, "id_icv", "id");
    }

    /**
     * Relationship ke IcvBuktiBayar
     */
    public function icvBuktiBayar(): HasOne
    {
        return $this->hasOne(IcvBuktiBayar::class, "id_icv", "id");
    }

    /**
     * Relationship ke IcvJadwal
     */
    public function icvJadwal(): HasOne
    {
        return $this->hasOne(IcvJadwal::class, "id_icv", "id");
    }

    /**
     * Relationship ke IcvSbbk
     */
    public function icvSbbk(): HasOne
    {
        return $this->hasOne(IcvSbbk::class, "id_icv", "id");
    }

    /**
     * Get file labels
     */
    public static function getFileLabels(): array
    {
        return [
            "file1" => "Surat Permohonan",
            "file2" => "Bukti laporan Ke Dinas Kesehatan",
            "file3" => "Rekap Pemakaian ICV",
            "file4" => "Faktur Pembelian Vaksin",
            "file5" => "Berita Acara Kerusakan ICV",
            "file6" => "Daftar Nama Penerima ICV",
            "file7" => "Screenshoot sisa stok di Sinkarkes",
        ];
    }

    /**
     * Get progress steps count
     *
     * @return array
     */
    public function getProgressSteps(): array
    {
        return [
            'verif' => $this->icvCekDokumen?->hasil_verif ? 1 : 0,
            'alokasi' => $this->icvAlokasi?->icv_disetujui ? 1 : 0,
            'billing' => $this->icvBilling?->kode_billing ? 1 : 0,
            'bayar' => $this->icvBuktiBayar?->distribusi_icv ? 1 : 0,
            'jadwal' => $this->icvJadwal?->jadwal_pengambilan ? 1 : 0,
            'sbbk' => $this->icvSbbk?->file_sbbk ? 1 : 0,
            'sinkarkes' => $this->icvSbbk?->sinkarkes === 'Sudah' ? 1 : 0,
        ];
    }

    /**
     * Get verification progress as fraction
     *
     * @return string
     */
    public function getProgressFraction(): string
    {
        $steps = $this->getProgressSteps();
        $completed = array_sum($steps);
        $total = count($steps);

        return "{$completed}/{$total}";
    }

    /**
     * Get progress color based on completion percentage
     *
     * @return string
     */
    public function getProgressColor(): string
    {
        $progress = $this->getProgressFraction();

        // Parse dengan aman menggunakan explode
        $parts = explode('/', $progress);
        if (count($parts) < 2) {
            return 'gray';
        }

        $completed = (int) $parts[0];
        $total = (int) $parts[1];

        if ($completed === $total)
            return 'success';
        if ($completed >= $total * 0.7)
            return 'warning';
        if ($completed >= $total * 0.4)
            return 'info';
        return 'gray';
    }

    /**
     * Get current verification step
     *
     * @return string
     */
    public function getCurrentStep(): string
    {
        if (!$this->icvCekDokumen || !$this->icvCekDokumen->hasil_verif) {
            return 'Verifikasi Dokumen';
        }
        if ($this->icvCekDokumen->hasil_verif == '2') {
            return 'Perbaikan Dokumen';
        }
        if (!$this->icvAlokasi || !$this->icvAlokasi->icv_disetujui) {
            return 'Alokasi ICV';
        }
        if (!$this->icvBilling || !$this->icvBilling->kode_billing) {
            return 'Penerbitan Billing';
        }
        if (!$this->icvBuktiBayar || !$this->icvBuktiBayar->file_bukti_bayar) {
            return 'Menunggu Pembayaran';
        }
        if (!$this->icvBuktiBayar->distribusi_icv) {
            return 'Verifikasi Pembayaran';
        }
        if (!$this->icvJadwal || !$this->icvJadwal->jadwal_pengambilan) {
            return 'Penjadwalan Distribusi';
        }
        if (!$this->icvSbbk || !$this->icvSbbk->file_sbbk) {
            return 'Upload SBBK';
        }
        if ($this->icvSbbk->sinkarkes !== 'Sudah') {
            return 'Update Sinkarkes';
        }
        return 'Selesai';
    }

    /**
     * Get current step color
     *
     * @return string
     */
    public function getCurrentStepColor(): string
    {
        $state = $this->getCurrentStep();

        return match ($state) {
            'Selesai' => 'success',
            'Perbaikan Dokumen' => 'danger',
            'Menunggu Pembayaran' => 'warning',
            default => 'info'
        };
    }

    /**
     * Check if current tab should be active
     *
     * @return int
     */
    public function getActiveTab(): int
    {
        // Aktifkan tab sesuai status terakhir
        if (!$this->icvCekDokumen || !$this->icvCekDokumen->hasil_verif) {
            return 1;
        }
        if ($this->icvCekDokumen->hasil_verif == '2') {
            return 1;
        }
        if (!$this->icvAlokasi || !$this->icvAlokasi->icv_disetujui) {
            return 2;
        }
        if (!$this->icvBilling || !$this->icvBilling->kode_billing) {
            return 3;
        }
        if (!$this->icvBuktiBayar || !$this->icvBuktiBayar->file_bukti_bayar) {
            return 4;
        }
        if (!$this->icvJadwal || !$this->icvJadwal->jadwal_pengambilan) {
            return 5;
        }
        if (!$this->icvSbbk || !$this->icvSbbk->file_sbbk) {
            return 6;
        }
        if ($this->icvSbbk?->sinkarkes !== 'Sudah') {
            return 7;
        }
        return 1;
    }

    /**
     * Tambahkan catatan perbaikan ke riwayat
     */
    public function tambahCatatanPerbaikan(string $catatan): void
    {
        $riwayat = $this->riwayat_perbaikan ?? [];

        $riwayat[] = [
            'tanggal' => now()->format('Y-m-d H:i:s'),
            'catatan' => $catatan,
        ];

        $this->riwayat_perbaikan = $riwayat;
        $this->save();
    }

    /**
     * Get riwayat perbaikan terakhir
     */
    public function getCatatanTerakhir(): ?array
    {
        $riwayat = $this->riwayat_perbaikan ?? [];
        return empty($riwayat) ? null : end($riwayat);
    }
}
