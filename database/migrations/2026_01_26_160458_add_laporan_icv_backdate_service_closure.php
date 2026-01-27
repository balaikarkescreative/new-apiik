<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ServiceClosure;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tambahkan service closure untuk back date laporan ICV
        ServiceClosure::updateOrCreate(
            ['service_type' => 'laporan_icv_backdate'],
            [
                'is_closed' => false,
                'closure_reason' => null,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus service closure untuk back date laporan ICV
        ServiceClosure::where('service_type', 'laporan_icv_backdate')->delete();
    }
};
