<?php

// database/migrations/xxxx_xx_xx_xxxxxx_alter_monitor_request_payload.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('yppi019_confirm_monitor', function (Blueprint $t) {
            $t->longText('request_payload')->nullable()->change();
        });
    }
    public function down(): void
    {
        Schema::table('yppi019_confirm_monitor', function (Blueprint $t) {
            $t->text('request_payload')->nullable()->change(); // atau sesuaikan
        });
    }
};
