<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    protected $fillable = ['user_id', 'type', 'original_name', 'private_path', 'status', 'lines_read', 'lines_imported', 'lines_rejected', 'errors', 'started_at', 'finished_at'];
    protected $casts = ['errors' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class); }
}
