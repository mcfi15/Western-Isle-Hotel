@extends('layouts.backend')

@section('title', 'Room Occupancy Report')

@section('content')
    <div class="main-body">
        <div class="container-fluid mt-4">

            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="form-inline">
                        <label class="mr-2 font-weight-bold">Select Date:</label>
                        <input type="date" name="date" value="{{ $date }}" class="form-control mr-2">
                        <button class="btn btn-primary">Check</button>
                    </form>
                </div>
            </div>

            <div class="row text-white">
                <div class="col-md-4 mb-3">
                    <div class="card bg-dark">
                        <div class="card-body">
                            <h6 class="text-white">Total Rooms</h6>
                            <h4 class="text-white">{{ $totalRooms }}</h4>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="card bg-success">
                        <div class="card-body">
                            <h6>Occupied Rooms</h6>
                            <h4>{{ $occupied }}</h4>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="card bg-info">
                        <div class="card-body">
                            <h6>Occupancy Rate</h6>
                            <h4>{{ number_format($rate, 2) }}%</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <table class="table table-bordered table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th>Room</th>
                            <th>Status</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rooms as $r)
                            <tr>
                                <td>{{ $r->room_no }}</td>
                                <td>
                                    @if($r->book_status == 1)
                                        <span class="badge badge-success">Occupied</span>
                                    @else
                                        <span class="badge badge-secondary">Free</span>
                                    @endif
                                </td>
                                <td>{{ $r->in_date }}</td>
                                <td>{{ $r->out_date }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
@endsection