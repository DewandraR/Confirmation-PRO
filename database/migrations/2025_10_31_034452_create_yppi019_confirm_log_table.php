<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('yppi019_confirm_log', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('AUFNR', 20);
            $t->string('VORNR', 10)->nullable();
            $t->string('PERNR', 20)->nullable();
            $t->decimal('PSMNG', 18, 0)->nullable();
            $t->string('MEINH', 10)->nullable();
            $t->date('GSTRP')->nullable();
            $t->date('GLTRP')->nullable();
            $t->date('BUDAT')->nullable();
            $t->json('SAP_RETURN')->nullable();
            $t->dateTime('created_at'); // mengikuti dump (tanpa updated_at)

            // Indeks
            $t->index(['AUFNR', 'VORNR']);
            $t->index('BUDAT');
            $t->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yppi019_confirm_log');
    }
};
