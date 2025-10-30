<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('yppi019_confirm_monitor', function (Blueprint $table) {
            // --- metadata dasar / relasi bisnis
            $table->string('plant', 8)->nullable()->after('vornr');                 // WERKS
            $table->string('work_center', 40)->nullable()->after('plant');          // ARBPL0
            $table->string('op_desc', 200)->nullable()->after('work_center');       // LTXA1

            $table->string('material', 40)->nullable()->after('op_desc');           // MATNRX
            $table->string('material_desc', 200)->nullable()->after('material');    // MAKTX
            $table->string('fg_desc', 200)->nullable()->after('material_desc');     // MAKTX0

            $table->string('mrp_controller', 10)->nullable()->after('fg_desc');     // DISPO
            $table->string('control_key', 10)->nullable()->after('mrp_controller'); // STEUS

            $table->string('sales_order', 20)->nullable()->after('control_key');    // KDAUF
            $table->string('so_item', 6)->nullable()->after('sales_order');         // KDPOS (6 digit)
            $table->string('batch_no', 40)->nullable()->after('so_item');           // CHARG

            // tanggal rencana (SSAVD/SSSLD) & menit (LTIMEX)
            $table->date('start_date_plan')->nullable()->after('batch_no');         // dari SSAVD
            $table->date('finish_date_plan')->nullable()->after('start_date_plan'); // dari SSSLD
            $table->decimal('minutes_plan', 18, 3)->nullable()->after('finish_date_plan'); // LTIMEX

            // posting date (BUDAT) agar eksplisit
            $table->date('posting_date')->nullable()->after('confirmation_date');

            // Snapshot lengkap dari baris yang dikonfirmasi (semua field popup)
            $table->json('row_meta')->nullable()->after('request_payload');

            // Indeks yang berguna untuk query
            $table->index(['plant']);
            $table->index(['work_center']);
            $table->index(['material']);
            $table->index(['sales_order', 'so_item']);
            $table->index(['posting_date']);
        });
    }

    public function down(): void
    {
        Schema::table('yppi019_confirm_monitor', function (Blueprint $table) {
            $table->dropIndex(['plant']);
            $table->dropIndex(['work_center']);
            $table->dropIndex(['material']);
            $table->dropIndex(['sales_order', 'so_item']);
            $table->dropIndex(['posting_date']);

            $table->dropColumn([
                'plant','work_center','op_desc',
                'material','material_desc','fg_desc',
                'mrp_controller','control_key',
                'sales_order','so_item','batch_no',
                'start_date_plan','finish_date_plan','minutes_plan',
                'posting_date',
                'row_meta',
            ]);
        });
    }
};
