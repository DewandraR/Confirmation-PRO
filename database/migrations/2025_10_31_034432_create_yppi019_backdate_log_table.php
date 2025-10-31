<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('yppi019_backdate_log', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('AUFNR', 20);
            $t->string('VORNR', 10)->nullable();
            $t->string('PERNR', 20)->nullable();
            $t->decimal('QTY', 18, 3)->nullable();
            $t->string('MEINH', 10)->nullable();
            $t->date('BUDAT');   // posting date
            $t->date('TODAY');   // tanggal eksekusi
            $t->string('ARBPL0', 40)->nullable();
            $t->string('MAKTX', 200)->nullable();
            $t->json('SAP_RETURN')->nullable();
            $t->dateTime('CONFIRMED_AT')->nullable();
            $t->dateTime('CREATED_AT')->useCurrent();

            // Indeks yang berguna
            $t->index(['AUFNR', 'VORNR']);
            $t->index('BUDAT');
            $t->index('TODAY');
            $t->index('PERNR');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yppi019_backdate_log');
    }
};
