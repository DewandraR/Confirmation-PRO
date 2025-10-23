<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('yppi019_confirm_monitor', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('aufnr', 12);
            $t->string('vornr', 4)->nullable();
            $t->string('meinh', 8)->nullable();
            $t->decimal('qty_pro', 18, 3)->nullable();
            $t->decimal('qty_confirm', 18, 3);
            $t->date('confirmation_date');
            $t->string('operator_nik', 32);
            $t->string('operator_name', 120)->nullable();
            $t->string('sap_user', 120)->nullable();
            $t->enum('status', ['PENDING', 'PROCESSING', 'SUCCESS', 'FAILED'])->default('PENDING');
            $t->string('status_message', 600)->nullable();
            $t->json('request_payload')->nullable();
            $t->json('sap_return')->nullable();
            $t->timestamp('processed_at')->nullable();
            $t->timestamps();
            $t->index(['aufnr', 'vornr']);
            $t->index(['operator_nik', 'confirmation_date']);
            $t->index(['status', 'created_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('yppi019_confirm_monitor');
    }
};
