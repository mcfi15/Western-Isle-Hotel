@extends('layouts.backend')

@section('title', 'Yearly Financial Report')

@section('content')
    <div class="main-body">
        <div class="container-fluid mt-4">

            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="form-inline">
                        <label class="mr-2 font-weight-bold">Year:</label>
                        <input type="number" name="year" value="{{ $year }}" class="form-control mr-2">
                        <button class="btn btn-primary">Generate</button>
                    </form>
                </div>
            </div>

            <div class="row text-white">
                <div class="col-md-6 mb-3">
                    <div class="card bg-success">
                        <div class="card-body">
                            <h6>💰 Total Cash Flow</h6>
                            <h4>₦{{ number_format($cash, 2) }}</h4>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <div class="card bg-info">
                        <div class="card-body">
                            <h6>🛏️ Total Revenue Earned</h6>
                            <h4>₦{{ number_format($revenue, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <table class="table table-bordered table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th>Month</th>
                            <th>Cash</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($months as $m)
                            <tr>
                                <td>{{ $m['name'] }}</td>
                                <td>₦{{ number_format($m['cash'], 2) }}</td>
                                <td>₦{{ number_format($m['revenue'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>


        </div>
    </div>
@endsection