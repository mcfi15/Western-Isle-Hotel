<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Report</title>

    <style>
        body{
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color:#333;
        }

        .header{
            width:100%;
            margin-bottom:15px;
        }

        .header table{
            width:100%;
        }

        .logo{
            width:80px;
        }

        .company-details{
            text-align:right;
        }

        .title{
            text-align:center;
            margin-top:10px;
            margin-bottom:10px;
        }

        .section-title{
            margin-top:20px;
            margin-bottom:8px;
        }

        table{
            width:100%;
            border-collapse: collapse;
        }

        table th{
            background:#f2f2f2;
        }

        table, th, td{
            border:1px solid #000;
        }

        th, td{
            padding:6px;
            text-align:left;
        }

        .text-right{
            text-align:right;
        }

        .footer{
            margin-top:40px;
        }

        .signature{
            margin-top:50px;
        }

        .signature div{
            width:45%;
            display:inline-block;
            text-align:center;
        }

    </style>
</head>

<body>

@php $gtext = gtext(); @endphp

<div class="header">
    <table>
        <tr>
            <td>
                <img class="logo" src="{{ public_path('public/media/'.$gtext['back_logo']) }}" alt="logo">
            </td>

            <td class="company-details">
                <h3 style="margin:0">{{ $gtext['site_title'] }}</h3>
                <div>{{ $gtext['address'] ?? '' }}</div>
                <div>Phone: {{ $gtext['phone'] ?? '' }}</div>
                <div>Email: {{ $gtext['email'] ?? '' }}</div>
            </td>
        </tr>
    </table>
</div>

<hr>

<div class="title">
    <h2>DAILY REPORT</h2>
    <strong>Date:</strong> {{ $date }} <br>
    <small>Generated on: {{ date('d M Y h:i A') }}</small>
</div>

<!-- CASH FLOW SECTION -->
<h3 class="section-title">Cash Flow — ₦{{ number_format($totalCash,2) }}</h3>
<table>
    <thead>
        <tr>
            <th>Booking No</th>
            <th>Customer</th>
            <th>Amount (₦)</th>
            <th>Method</th>
            <th>Payment Date</th>
            <th>Staff</th>
        </tr>
    </thead>

    <tbody>
        @forelse($cashList as $row)
        <tr>
            <td>{{ $row->booking_no }}</td>
            <td>{{ $row->name }}</td>
            <td class="text-right">{{ number_format($row->paid_amount,2) }}</td>
            <td>{{ ucfirst($row->payment_method) }}</td>
            <td>{{ $row->payment_date }}</td>
            <td>{{ $row->staff_name ?? 'N/A' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="text-align:center">No payment records for this date</td>
        </tr>
        @endforelse
    </tbody>

    <tfoot>
        <tr>
            <th colspan="2">TOTAL</th>
            <th class="text-right">₦{{ number_format($totalCash,2) }}</th>
            <th colspan="3"></th>
        </tr>
    </tfoot>
</table>

<!-- STAY / OCCUPANCY SECTION -->
<h3 class="section-title">Stay / Occupancy — ₦{{ number_format($totalStayRevenue,2) }}</h3>
<table>
    <thead>
        <tr>
            <th>Booking No</th>
            <th>Guest</th>
            <th>Room Type</th>
            <th>Check-in</th>
            <th>Check-out</th>
            <th>Revenue</th>
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
            <td class="text-right">{{ number_format($row->total_amount,2) }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="text-align:center">No check-ins for this date</td>
        </tr>
        @endforelse
    </tbody>

    <tfoot>
        <tr>
            <th colspan="2">TOTAL</th>
            <th class="text-right">₦{{ number_format($totalStayRevenue,2) }}</th>
            <th colspan="3"></th>
        </tr>
    </tfoot>
</table>

<div class="footer">

    <div class="signature">
        <div>
            ___________________________<br>
            Prepared By
        </div>

        <div style="float:right">
            ___________________________<br>
            Approved By
        </div>
    </div>

</div>

</body>
</html>