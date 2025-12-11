<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportJob extends Model
{
    protected $table = 'import_jobs';

    protected $fillable = [
        'filename',
        'status',
        'total',
        'success',
        'failed',
        'error_message',
    ];
}
