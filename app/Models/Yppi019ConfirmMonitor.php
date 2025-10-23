<?php
// app/Models/Yppi019ConfirmMonitor.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Yppi019ConfirmMonitor extends Model
{
    protected $table = 'yppi019_confirm_monitor';
    protected $fillable = [
        'aufnr',
        'vornr',
        'meinh',
        'qty_pro',
        'qty_confirm',
        'confirmation_date',
        'operator_nik',
        'operator_name',
        'sap_user',
        'status',
        'status_message',
        'request_payload',
        'sap_return',
        'processed_at'
    ];
    protected $casts = [
        'request_payload' => 'array',
        'sap_return'      => 'array',
        'confirmation_date' => 'date',
        'processed_at'      => 'datetime',
    ];
}
