<?php
// app/Models/SupportTicket.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $fillable = [
        'user_id','subject','message','category',
        'status','priority','admin_reply','replied_at','closed_at',
    ];
    protected $casts = ['replied_at'=>'datetime','closed_at'=>'datetime'];
    public function user() { return $this->belongsTo(User::class); }
}
