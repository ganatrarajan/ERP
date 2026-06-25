<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student Attendance Report</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 10px;
        }
        .header {
            margin-bottom: 20px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 20px;
            color: #1e3a8a;
            margin: 0 0 5px 0;
        }
        .header-meta table {
            width: 100%;
        }
        .header-meta td {
            padding: 2px 0;
            font-size: 12px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e1b4b;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .stats-grid {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .stats-grid td {
            border: 1px solid #e2e8f0;
            padding: 10px;
            text-align: center;
            background-color: #f8fafc;
        }
        .stats-val {
            font-size: 16px;
            font-weight: bold;
            color: #3b82f6;
            margin-top: 5px;
        }
        table.attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.attendance-table th, table.attendance-table td {
            border: 1px solid #ddd;
            padding: 6px 10px;
            text-align: left;
        }
        table.attendance-table th {
            background-color: #f3f4f6;
            color: #1f2937;
            font-weight: bold;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }
        .status-Present { background-color: #d1fae5; color: #065f46; }
        .status-Absent { background-color: #fee2e2; color: #991b1b; }
        .status-Late { background-color: #fef3c7; color: #92400e; }
        .status-HalfDay { background-color: #ffedd5; color: #9a3412; }
        .status-Leave { background-color: #f3e8ff; color: #6b21a8; }
        .status-Holiday { background-color: #e0f2fe; color: #075985; }
        .status-H { background-color: #e0f2fe; color: #075985; }
        
        .footer {
            margin-top: 30px;
            font-size: 9px;
            color: #777;
            text-align: right;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>{{ $school_name }}</h1>
    <div class="header-meta">
        <table>
            <tr>
                <td><strong>Student Name:</strong> {{ $student->first_name }} {{ $student->last_name }}</td>
                <td><strong>Admission No:</strong> {{ $student->admission_no }}</td>
            </tr>
            <tr>
                <td><strong>Academic Year:</strong> {{ $academic_year }}</td>
                <td style="text-align: right; font-weight: bold;">STUDENT ATTENDANCE REPORT</td>
            </tr>
        </table>
    </div>
</div>

<div class="section-title">Summary Statistics</div>
<table class="stats-grid">
    <tr>
        <td>
            <div>Total Days</div>
            <div class="stats-val">{{ $stats['total'] }}</div>
        </td>
        <td>
            <div style="color: #059669;">Present</div>
            <div class="stats-val" style="color: #059669;">{{ $stats['Present'] }}</div>
        </td>
        <td>
            <div style="color: #dc2626;">Absent</div>
            <div class="stats-val" style="color: #dc2626;">{{ $stats['Absent'] }}</div>
        </td>
        <td>
            <div style="color: #d97706;">Late</div>
            <div class="stats-val" style="color: #d97706;">{{ $stats['Late'] }}</div>
        </td>
        <td>
            <div style="color: #2563eb;">Leave / Holiday</div>
            <div class="stats-val" style="color: #2563eb;">{{ $stats['Leave'] + $stats['Holiday'] }}</div>
        </td>
    </tr>
</table>

<div class="section-title">Attendance Log</div>
<table class="attendance-table">
    <thead>
        <tr>
            <th style="width: 120px;">Date</th>
            <th style="width: 120px;">Class/Section</th>
            <th style="width: 100px;">Status</th>
            <th>Remarks</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($attendances as $att)
            <tr>
                <td><strong>{{ \Carbon\Carbon::parse($att->attendance_date)->format('F d, Y') }}</strong></td>
                <td>{{ $att->class ? $att->class->name : '-' }} - {{ $att->section ? $att->section->name : '-' }}</td>
                <td>
                    @php
                        $statusClass = str_replace(' ', '', $att->status);
                    @endphp
                    <span class="status-badge status-{{ $statusClass }}">
                        {{ $att->status }}
                    </span>
                </td>
                <td><span style="color: #555; font-style: italic;">{{ $att->remarks ?: '-' }}</span></td>
            </tr>
        @empty
            <tr>
                <td colspan="4" style="text-align: center; color: #999; padding: 20px;">No attendance logs found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    Generated on {{ now()->format('Y-m-d H:i:s') }}
</div>

</body>
</html>
