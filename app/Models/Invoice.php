<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = ['amount', 'status', 'payment_method', 'bank'];


    public function createInvoice($data)
    {
        return $this->create($data);
    }

}
