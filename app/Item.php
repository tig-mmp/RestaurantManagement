<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;


class Item extends Model
{
	use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'type','description','photo_url','price',
    ];

    public function invoiceItem(){
        return $this->belongsToMany(InvoiceItem::class, 'item_id','id');
    }

    public function order(){
        return $this->belongsToMany(Order::class, 'item_id','id');
    }
}
