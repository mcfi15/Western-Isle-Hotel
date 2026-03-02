@extends('layouts.backend')

@section('title','Outstanding Payments')

@section('content')
<div class="main-body">
    <div class="container-fluid mt-4">

        <div class="card mb-4">
            <div class="card-body bg-danger text-white">
                <h5>Total Outstanding: ₦{{ number_format($totalDue,2) }}</h5>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <strong>Customers With Pending Payments</strong>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th>Booking No</th>
                            <th>Guest</th>
                            <th>Phone</th>
                            <th>Check-in</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($list as $row)
                        <tr>
                            <td>{{ $row->booking_no }}</td>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->phone }}</td>
                            <td>{{ $row->in_date }}</td>
                            <td>₦{{ number_format($row->total_amount,2) }}</td>
                            <td>₦{{ number_format($row->paid_amount,2) }}</td>
                            <td class="text-danger font-weight-bold">
                                ₦{{ number_format($row->due_amount,2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                No outstanding payments
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

    </div>
</div>
@endsection