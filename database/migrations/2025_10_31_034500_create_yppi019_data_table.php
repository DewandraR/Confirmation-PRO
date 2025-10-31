<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('yppi019_data', function (Blueprint $t) {
            $t->bigIncrements('id');

            // Kunci bisnis / metadata SAP (mengikuti dump, huruf besar)
            $t->string('AUFNR', 20);
            $t->string('VORNRX', 10)->nullable();
            $t->string('PERNR', 20)->nullable();
            $t->string('ARBPL0', 40)->nullable();
            $t->string('DISPO', 10)->nullable();
            $t->string('STEUS', 8)->nullable();
            $t->string('WERKS', 10)->nullable();
            $t->string('KDAUF', 20)->nullable();
            $t->string('KDPOS', 10)->nullable();
            $t->string('CHARG', 20)->nullable();
            $t->string('MATNRX', 40)->nullable();
            $t->string('MAKTX', 200)->nullable();
            $t->string('MAKTX0', 200)->nullable();
            $t->string('MATNR0', 40)->nullable();
            $t->string('MEINH', 10)->nullable();

            // Kuantitas & waktu
            $t->decimal('QTY_SPK', 18, 3)->nullable();
            $t->decimal('WEMNG', 18, 3)->nullable();
            $t->decimal('QTY_SPX', 18, 3)->nullable();
            $t->string('LTXA1', 200)->nullable();
            $t->string('SNAME', 100)->nullable();
            $t->date('GSTRP')->nullable();
            $t->date('GLTRP')->nullable();
            $t->date('SSAVD')->nullable();
            $t->date('SSSLD')->nullable();
            $t->decimal('LTIME', 18, 3)->nullable();
            $t->decimal('LTIMEX', 18, 3)->nullable();
            $t->string('ISDZ', 20)->nullable();
            $t->string('IEDZ', 20)->nullable();

            // RAW JSON & timestamp fetch
            $t->json('RAW_JSON');        // NOT NULL di dump
            $t->dateTime('fetched_at');  // NOT NULL di dump

            // Indeks yang umum dipakai untuk query
            $t->index('AUFNR');
            $t->index('VORNRX');
            $t->index('WERKS');
            $t->index('ARBPL0');
            $t->index(['KDAUF', 'KDPOS']);
            $t->index('SSAVD');
            $t->index('SSSLD');
            $t->index('fetched_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yppi019_data');
    }
};
