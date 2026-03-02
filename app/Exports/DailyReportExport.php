<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;

class DailyReportExport implements FromCollection
{
    protected $date;

    public function __construct($date)
    {
        $this->date = $date;
    }

    public function collection()
    {
        return DB::table('booking_manages')
            ->whereDate('payment_date', $this->date)
            ->where('payment_status_id', 1)
            ->get([
                'booking_no',
                'customer_name',
                'paid_amount',
                'payment_method',
                'payment_date'
            ]);
    }
}