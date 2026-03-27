<?php
// app/Models/Transaction.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = ['type','amount','currency','category','description','reference','booking_id','date'];
    protected $casts = ['date'=>'date','amount'=>'float'];
    public function booking() { return $this->belongsTo(Booking::class); }
}
