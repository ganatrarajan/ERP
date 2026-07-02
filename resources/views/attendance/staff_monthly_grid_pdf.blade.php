<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Staff Attendance Report</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            margin-bottom: 15px;
            border-bottom: 2px solid #6366f1;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 18px;
            color: #312e81;
            margin: 0 0 5px 0;
        }
        .header-meta {
            font-size: 11px;
            color: #555;
        }
        .header-meta table {
            width: 100%;
        }
        .header-meta td {
            padding: 2px 0;
        }
        table.attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.attendance-table th, table.attendance-table td {
            border: 1px solid #ddd;
            padding: 2.5px 1px;
            text-align: center;
            font-size: 7px;
        }
        table.attendance-table th {
            background-color: #f3f4f6;
            color: #1f2937;
            font-weight: bold;
        }
        .staff-name {
            text-align: left !important;
            font-weight: bold;
            white-space: nowrap;
        }
        .status-P { background-color: #d1fae5; color: #065f46; font-weight: bold; }
        .status-A { background-color: #fee2e2; color: #991b1b; font-weight: bold; }
        .status-L { background-color: #fef3c7; color: #92400e; font-weight: bold; }
        .status-HD { background-color: #ffedd5; color: #9a3412; font-weight: bold; }
        .status-LV { background-color: #f3e8ff; color: #6b21a8; font-weight: bold; }
        .status-H { background-color: #e0f2fe; color: #075985; font-weight: bold; }
        
        .footer {
            margin-top: 15px;
            font-size: 9px;
            color: #777;
            text-align: right;
        }
        .legend {
            margin-top: 10px;
            font-size: 9px;
        }
        .legend-item {
            display: inline-block;
            margin-right: 15px;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>{{ $school_name }}</h1>
    <div class="header-meta">
        <table>
            <tr>
                <td><strong>Academic Year:</strong> {{ $academic_year }}</td>
                <td><strong>Report Type:</strong> Staff Monthly Attendance Grid</td>
                <td style="text-align: right;"><strong>Month:</strong> {{ $month }}</td>
            </tr>
        </table>
    </div>
</div>

<table class="attendance-table">
    <thead>
        <tr>
            <th style="font-size: 7.5px;">Staff Name</th>
            @for ($d = 1; $d <= $days_in_month; $d++)
                <th style="width: 12px; font-size: 7px;">{{ $d }}</th>
            @endfor
            <th style="width: 12px; font-size: 7px; background-color: #d1fae5;">P</th>
            <th style="width: 12px; font-size: 7px; background-color: #fee2e2;">A</th>
            <th style="width: 12px; font-size: 7px; background-color: #fef3c7;">L</th>
            <th style="width: 12px; font-size: 7px; background-color: #ffedd5;">HD</th>
            <th style="width: 12px; font-size: 7px; background-color: #f3e8ff;">LV</th>
            <th style="width: 12px; font-size: 7px; background-color: #e0f2fe;">H</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($matrix as $row)
            <tr>
                <td class="staff-name">{{ $row['name'] }}</td>
                @for ($d = 1; $d <= $days_in_month; $d++)
                    @php
                        $status = $row['days'][$d] ?? '-';
                        $class = '';
                        if ($status === 'Present') { $status = 'P'; $class = 'status-P'; }
                        elseif ($status === 'Absent') { $status = 'A'; $class = 'status-A'; }
                        elseif ($status === 'Late') { $status = 'L'; $class = 'status-L'; }
                        elseif ($status === 'Half Day') { $status = 'HD'; $class = 'status-HD'; }
                        elseif ($status === 'Leave') { $status = 'LV'; $class = 'status-LV'; }
                        elseif ($status === 'Holiday' || $status === 'H') { $status = 'H'; $class = 'status-H'; }
                    @endphp
                    <td class="{{ $class }}">{{ $status }}</td>
                @endfor
                <td style="background-color: #f9fafb;">{{ $row['stats']['Present'] }}</td>
                <td style="background-color: #f9fafb;">{{ $row['stats']['Absent'] }}</td>
                <td style="background-color: #f9fafb;">{{ $row['stats']['Late'] }}</td>
                <td style="background-color: #f9fafb;">{{ $row['stats']['Half Day'] }}</td>
                <td style="background-color: #f9fafb;">{{ $row['stats']['Leave'] }}</td>
                <td style="background-color: #f9fafb;">{{ $row['stats']['Holiday'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="legend">
    <strong>Legend:</strong>
    <span class="legend-item status-P">P: Present</span>
    <span class="legend-item status-A">A: Absent</span>
    <span class="legend-item status-L">L: Late</span>
    <span class="legend-item status-HD">HD: Half Day</span>
    <span class="legend-item status-LV">LV: Leave</span>
    <span class="legend-item status-H">H: Holiday</span>
</div>

<div class="footer">
    Generated on {{ now()->format('Y-m-d H:i:s') }}
</div>

</body>
</html>
