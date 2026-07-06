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
            ->leftJoin('payment_method', 'booking_manages.payment_method_id', '=', 'payment_method.id')
            ->whereDate('booking_manages.payment_date', $this->date)
            ->where('booking_manages.payment_status_id', 1)
            ->get([
                'booking_manages.booking_no',
                'booking_manages.name',
                'booking_manages.paid_amount',
                'payment_method.method_name as payment_method',
                'booking_manages.payment_date'
            ]);
    }
}