<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
// use App\Models\Booking_manage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\DailyReportExport;
use Maatwebsite\Excel\Facades\Excel;

// use PhpOffice\PhpSpreadsheet\Writer\Pdf;

class ReportController extends Controller
{
    // public function index()
    // {
    //     return view('backend.reports.index');
    // }

    public function dailyReport(Request $request)
    {
        $date = $request->date ?? date('Y-m-d');

        // CASH FLOW (Money received today)
        // $cashList = DB::table('booking_manages')
        //     ->join('users', 'booking_manages.processed_by', '=', 'users.id')
        //     ->select(
        //         'booking_manages.*',
        //         'users.name as staff_name'
        //     )
        //     ->whereDate('payment_date', $date)
        //     ->where('payment_status_id', 1)
        //     ->get();

        $cashList = DB::table('booking_manages')
        ->leftJoin('users', 'booking_manages.processed_by', '=', 'users.id')
        ->leftJoin('payment_method', 'booking_manages.payment_method_id', '=', 'payment_method.id')
        ->select(
            'booking_manages.*',
            'users.name as staff_name',
            'payment_method.method_name as payment_method'
        )
        ->whereDate('booking_manages.payment_date', $date)
        ->where('booking_manages.payment_status_id', 1)
        ->get();

        $totalCash = $cashList->sum('paid_amount');


        // STAY REPORT (Guests arriving today)
        // $stayList = DB::table('booking_manages')
        //     ->join('users', 'booking_manages.processed_by', '=', 'users.id')
        //     ->select(
        //         'booking_manages.*',
        //         'users.name as staff_name'
        //     )
        //     ->whereDate('in_date', $date)
        //     ->where('booking_status_id', 2)
        //     ->get();

        $stayList = DB::table('booking_manages')
        ->join('users', 'booking_manages.processed_by', '=', 'users.id')
        ->leftJoin('rooms', 'booking_manages.roomtype_id', '=', 'rooms.id') // join rooms
        ->select(
            'booking_manages.*',
            'users.name as staff_name',
            'rooms.title as roomtype' // alias the room type
        )
        ->whereDate('in_date', $date)
        ->where('booking_status_id', 2)
        ->get();

        // $paymentMethods = DB::table('booking_manages')
        // ->join('payment_method', 'booking_manages.payment_method_id', '=', 'payment_method.id')
        // ->select(
        //     'payment_method.method_name as payment_method',
        //     DB::raw('SUM(booking_manages.paid_amount) as total')
        // )
        // ->whereDate('booking_manages.payment_date', $date)
        // ->where('booking_manages.payment_status_id', 1)
        // ->groupBy('payment_method.method_name')
        // ->get();

        $totalStayRevenue = $stayList->sum('total_amount');


        // OVERALL TOTAL REVENUE
        $grandTotal = DB::table('booking_manages')
            ->where('payment_status_id', 1)
            ->sum('paid_amount');


        return view('backend.reports.daily', compact(
            'date',
            'cashList',
            'totalCash',
            'stayList',
            'totalStayRevenue',
            'grandTotal',
            // 'paymentMethods'
        ));
    }

 public function monthlyReport(Request $request)
    {
        $month = $request->month ?? date('m');
        $year  = $request->year ?? date('Y');

        $cash = DB::table('booking_manages')
            ->whereMonth('payment_date', $month)
            ->whereYear('payment_date', $year)
            ->where('payment_status_id', 1)
            ->sum('paid_amount');

        $revenue = DB::table('booking_manages')
            ->whereMonth('in_date', $month)
            ->whereYear('in_date', $year)
            ->where('booking_status_id', 2)
            ->sum('total_amount');

        $bookings = DB::table('booking_manages')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        // 👉 detailed list
        $list = DB::table('booking_manages')
    ->leftJoin('users','booking_manages.processed_by','=','users.id')
    ->select('booking_manages.*','users.name as staff_name')

    ->whereMonth('booking_manages.created_at', $month)
    ->whereYear('booking_manages.created_at', $year)

    ->orderBy('booking_manages.created_at','desc')
    ->get();

        return view('backend.reports.monthly', compact(
            'month','year','cash','revenue','bookings','list'
        ));
    }


    public function yearlyReport(Request $request)
    {
        $year = $request->year ?? date('Y');

        $cash = DB::table('booking_manages')
            ->whereYear('payment_date', $year)
            ->where('payment_status_id', 1)
            ->sum('paid_amount');

        $revenue = DB::table('booking_manages')
            ->whereYear('in_date', $year)
            ->where('booking_status_id', 2)
            ->sum('total_amount');

        // monthly breakdown
        $months = [];
        for ($i=1; $i<=12; $i++) {

            $monthlyCash = DB::table('booking_manages')
                ->whereYear('payment_date', $year)
                ->whereMonth('payment_date', $i)
                ->sum('paid_amount');

            $monthlyRevenue = DB::table('booking_manages')
                ->whereYear('in_date', $year)
                ->whereMonth('in_date', $i)
                ->sum('total_amount');

            $months[] = [
                'name' => date('F', mktime(0,0,0,$i,1)),
                'cash' => $monthlyCash,
                'revenue' => $monthlyRevenue
            ];
        }

        return view('backend.reports.yearly', compact('year','cash','revenue','months'));
    }


    public function occupancyReport(Request $request)
    {
        $date = $request->date ?? date('Y-m-d');

        $occupied = DB::table('room_manages')
            ->where('book_status', 1)
            ->whereDate('in_date', '<=', $date)
            ->whereDate('out_date', '>=', $date)
            ->count();

        $totalRooms = DB::table('room_manages')->count();

        $rate = $totalRooms > 0 ? ($occupied/$totalRooms)*100 : 0;

        $rooms = DB::table('room_manages')->get();

        return view('backend.reports.occupancy', compact(
            'date','occupied','totalRooms','rate','rooms'
        ));
    }


    public function outstandingReport()
    {
        $list = DB::table('booking_manages')
            ->where('payment_status_id','!=',1)
            ->where('due_amount','>',0)
            ->orderBy('in_date','asc')
            ->get();

        $totalDue = $list->sum('due_amount');

        return view('backend.reports.outstanding', compact('list','totalDue'));
    }

    
//    public function exportDailyPDF(Request $request)
// {
//     $date = $request->date ?? date('Y-m-d');

//     $cashList = DB::table('booking_manages')
//         ->leftJoin('users', 'booking_manages.processed_by', '=', 'users.id')
//         ->leftJoin('payment_method', 'booking_manages.payment_method_id', '=', 'payment_method.id')
//         ->select(
//             'booking_manages.*',
//             'users.name as staff_name',
//             'payment_method.method_name as payment_method'
//         )
//         ->whereDate('booking_manages.payment_date', $date)
//         ->where('booking_manages.payment_status_id', 1)
//         ->get();

//     $totalCash = $cashList->sum('paid_amount');

//     $pdf = Pdf::loadView('backend.reports.exports.daily_pdf', compact(
//         'cashList', 'totalCash', 'date'
//     ));

//     return $pdf->download("Daily_Report_$date.pdf");
// }

public function exportDailyPDF(Request $request)
{
    $date = $request->date ?? date('Y-m-d');

    $cashList = DB::table('booking_manages')
        ->leftJoin('users','booking_manages.processed_by','=','users.id')
        ->leftJoin('payment_method','booking_manages.payment_method_id','=','payment_method.id')
        ->select(
            'booking_manages.*',
            'users.name as staff_name',
            'payment_method.method_name as payment_method'
        )
        ->whereDate('booking_manages.payment_date', $date)
        ->where('booking_manages.payment_status_id', 1)
        ->get();

    $totalCash = $cashList->sum('paid_amount');

    $pdf = Pdf::loadView('backend.reports.exports.daily_pdf', compact(
        'cashList','totalCash','date'
    ));

    return $pdf->download("Daily_Cash_Report_$date.pdf");
}

public function exportDailyExcel(Request $request)
{
    $date = $request->date ?? date('Y-m-d');

    return Excel::download(new DailyReportExport($date), "Daily_Report_$date.xlsx");
}

}