<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dataset extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'type';
    protected $keyType = 'string';
    protected $fillable = ['type', 'active_import_id', 'record_count', 'published_at'];
    protected $casts = ['published_at' => 'datetime'];
}
