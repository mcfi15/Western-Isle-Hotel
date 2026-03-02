@extends('layouts.backend')

@section('title', 'Monthly Financial Report')

@section('content')
    <div class="main-body">
        <div class="container-fluid mt-4">

            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="form-inline">
                        <label class="mr-2 font-weight-bold">Month:</label>
                        <input type="month" name="month" class="form-control mr-2"
                               value="{{ $year }}-{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}">
                        <button class="btn btn-primary">Generate</button>
                    </form>
                </div>
            </div>

            <div class="row text-white">
                <div class="col-md-4 mb-3">
                    <div class="card bg-success">
                        <div class="card-body">
                            <h6>💰 Cash Received</h6>
                            <h4>₦{{ number_format($cash, 2) }}</h4>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="card bg-info">
                        <div class="card-body">
                            <h6>🛏️ Stay Revenue</h6>
                            <h4>₦{{ number_format($revenue, 2) }}</h4>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="card bg-dark">
                        <div class="card-body">
                            <h6 class="text-white">📊 Total Bookings</h6>
                            <h4 class="text-white">{{ $bookings }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
        <div class="card-header bg-light">
            <strong>Detailed Monthly Transactions</strong>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>Booking</th>
                        <th>Guest</th>
                        <th>Check-in</th>
                        <th>Paid</th>
                        <th>Total</th>
                        <th>Staff</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($list as $row)
                        <tr>
                            <td>{{ $row->booking_no }}</td>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->in_date }}</td>
                            <td>₦{{ number_format($row->paid_amount, 2) }}</td>
                            <td>₦{{ number_format($row->total_amount, 2) }}</td>
                            <td>{{ $row->staff_name }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        </div>

        </div>
    </div>
@endsection