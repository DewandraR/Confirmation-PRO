<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Yppi019Data extends Model
{
    protected $table = 'yppi019_data';
    public $timestamps = false;

    protected $fillable = [
        'AUFNR','VORNRX','PERNR','ARBPL0','DISPO','STEUS','WERKS','CHARG',
        'MATNRX','MAKTX','MEINH','QTY_SPK','WEMNG','QTY_SPX',
        'LTXA1','SNAME','GSTRP','GLTRP','ISDZ','IEDZ','RAW_JSON','fetched_at'
    ];

    protected $casts = [
        'QTY_SPK'    => 'decimal:3',
        'WEMNG'      => 'decimal:3',
        'QTY_SPX'    => 'decimal:3',
        'GSTRP'      => 'date',
        'GLTRP'      => 'date',
        'fetched_at' => 'datetime',
        'RAW_JSON'   => 'array',
    ];

    /* Scopes */
    public function scopeByAufnr($q, ?string $aufnr)
    {
        return $aufnr ? $q->where('AUFNR', $aufnr) : $q;
    }

    public function scopeByPernr($q, ?string $pernr)
    {
        return $pernr ? $q->where('PERNR', $pernr) : $q;
    }

    public function scopeByArbpl($q, ?string $arbpl)
    {
        // di DB kolomnya ARBPL0, di UI: IV_ARBPL
        return $arbpl ? $q->where('ARBPL0', $arbpl) : $q;
    }

    /* Mapper mirip SAP T_DATA1 (kalau dibutuhkan) */
    public function toTData1Array(): array
    {
        return [
            'AUFNR'  => $this->AUFNR,
            'VORNRX' => $this->VORNRX,
            'LTXA1'  => $this->LTXA1,
            'DISPO'  => $this->DISPO,
            'STEUS'  => $this->STEUS,
            'WERKS'  => $this->WERKS,
            'CHARG'  => $this->CHARG,
            'ARBPL0' => $this->ARBPL0,
            'MATNRX' => $this->MATNRX,
            'MAKTX'  => $this->MAKTX,
            'QTY_SPK'=> $this->QTY_SPK,
            'WEMNG'  => $this->WEMNG,
            'QTY_SPX'=> $this->QTY_SPX,
            'MEINH'  => $this->MEINH,
            'PERNR'  => $this->PERNR,
            'SNAME'  => $this->SNAME,
            'GSTRP'  => optional($this->GSTRP)->format('Y-m-d'),
            'GLTRP'  => optional($this->GLTRP)->format('Y-m-d'),
            'ISDZ'   => $this->ISDZ,
            'IEDZ'   => $this->IEDZ,
        ];
        // NB: endpoint material saat ini mengembalikan rows langsung dari Flask,
        // bukan mapping model ini—tapi helper ini tetap berguna bila dibutuhkan.
    }
}
