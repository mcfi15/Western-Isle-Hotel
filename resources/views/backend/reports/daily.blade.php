@extends('layouts.backend')

@section('title', __('Daily Financial Report'))

@section('content')
<div class="main-body">
    <div class="container-fluid mt-4">

        <!-- FILTER -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="form-inline">
                    <label class="mr-2 font-weight-bold">Select Date:</label>
                    <input type="date" name="date" value="{{ $date }}" class="form-control mr-2">
                    <button class="btn btn-primary">Generate Report</button>
                    <a href="{{ route('reports.daily.pdf',['date'=>$date]) }}" class="btn btn-danger">
                        Export PDF
                    </a>
                    {{-- <a href="{{ route('reports.daily.excel',['date'=>$date]) }}" class="btn btn-success">
                        Export Excel
                    </a> --}}
                </form>
            </div>
        </div>

        <!-- SUMMARY CARDS -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card text-white bg-success shadow-sm">
                    <div class="card-body">
                        <h6>💰 Cash Received</h6>
                        <h4>₦{{ number_format($totalCash,2) }}</h4>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card text-white bg-info shadow-sm">
                    <div class="card-body">
                        <h6>🛏️ Stay Revenue (Check-ins Today)</h6>
                        <h4>₦{{ number_format($totalStayRevenue,2) }}</h4>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card text-white bg-dark shadow-sm">
                    <div class="card-body">
                        <h6 class="text-white">📊 Total Revenue To Date</h6>
                        <h4 class="text-white">₦{{ number_format($grandTotal,2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABS -->
        <ul class="nav nav-tabs" id="reportTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#cash" role="tab">
                    💰 Cash Flow
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#stay" role="tab">
                    🛏️ Stay / Occupancy Report
                </a>
            </li>
        </ul>

        <div class="tab-content mt-3">

            <!-- CASH FLOW TAB -->
            <div class="tab-pane fade show active" id="cash" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-light">
                        <strong>Payments Received on {{ $date }}</strong>
                    </div>

                    <div class="table-responsive">
    <table class="table table-bordered table-striped table-sm mb-0">
        <thead class="thead-dark">
            <tr>
                <th>Booking No</th>
                <th>Guest</th>
                <th>Payment Method</th>
                <th>Amount Paid</th>
                <th>Payment Date</th>
                <th>Handled By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cashList as $row)
            <tr>
                <td>{{ $row->booking_no }}</td>
                <td>{{ $row->name }}</td>
                <td>{{ $row->payment_method ?? 'N/A' }}</td>
                <td>₦{{ number_format($row->paid_amount,2) }}</td>
                <td>{{ $row->payment_date }}</td>
                <td>{{ $row->staff_name ?? 'N/A' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center text-muted">
                    No payments recorded for this date
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
                </div>
            </div>

            <!-- STAY REPORT TAB -->
            <div class="tab-pane fade" id="stay" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-light">
                        <strong>Guests Checking-in on {{ $date }}</strong>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm mb-0">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Booking No</th>
                                    <th>Guest</th>
                                    <th>Room Type</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Revenue Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stayList as $row)
                                <tr>
                                    <td>{{ $row->booking_no }}</td>
                                    <td>{{ $row->name }}</td>
                                    <td>{{ $row->roomtype }}</td>
                                    <td>{{ $row->in_date }}</td>
                                    <td>{{ $row->out_date }}</td>
                                    <td>₦{{ number_format($row->total_amount,2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        No check-ins for this date
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection