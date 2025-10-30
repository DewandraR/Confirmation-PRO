<?php
// app/Models/Yppi019ConfirmMonitor.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Yppi019ConfirmMonitor extends Model
{
    protected $table = 'yppi019_confirm_monitor';
    protected $casts = [
        'request_payload'  => 'array',
        'sap_return'       => 'array',
        'row_meta'         => 'array',
        'qty_pro'          => 'decimal:3',
        'qty_confirm'      => 'decimal:3',
        'minutes_plan'     => 'decimal:3',
        'posting_date'     => 'date',
        'start_date_plan'  => 'date',
        'finish_date_plan' => 'date',
    ];

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
        'processed_at',

        // kolom baru
        'plant',
        'work_center',
        'op_desc',
        'material',
        'material_desc',
        'fg_desc',
        'mrp_controller',
        'control_key',
        'sales_order',
        'so_item',
        'batch_no',
        'start_date_plan',
        'finish_date_plan',
        'minutes_plan',
        'posting_date',
        'row_meta',
    ];
}
