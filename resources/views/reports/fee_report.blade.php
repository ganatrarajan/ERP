<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }}</title>
    <style>
        @page {
            margin: 25px 30px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 0;
            font-size: 10px;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border-bottom: 2px solid #cbd5e1;
            padding-bottom: 12px;
        }
        .school-name {
            font-size: 18px;
            font-weight: bold;
            color: #4f46e5;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .school-info {
            font-size: 9px;
            color: #64748b;
            margin: 2px 0 0 0;
        }
        .report-meta {
            text-align: right;
            font-size: 9px;
            color: #475569;
        }
        .report-title {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            margin: 10px 0 5px 0;
            text-transform: uppercase;
        }
        .report-sub {
            font-size: 9px;
            color: #64748b;
            margin: 0 0 15px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #f8fafc;
            border-bottom: 1px solid #cbd5e1;
            padding: 8px 10px;
            font-weight: bold;
            color: #475569;
            text-transform: uppercase;
            font-size: 8px;
            text-align: left;
        }
        td {
            padding: 7px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
            vertical-align: middle;
        }
        tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-mono {
            font-family: Consolas, Monaco, monospace;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="border: none;">
                <div class="school-name">{{ $school->name ?? 'EduvoraX School' }}</div>
                <div class="school-info">{{ $school->address ?? '' }}</div>
            </td>
            <td class="report-meta" style="border: none;">
                <div>Academic Session: <strong>{{ $academicYearTitle }}</strong></div>
                <div>Generated on: <strong>{{ date('d-M-Y H:i') }}</strong></div>
            </td>
        </tr>
    </table>

    <div class="report-title">{{ $reportTitle }}</div>
    <div class="report-sub">
        Filter Scope: 
        @if($startDate && $endDate)
            Dates: <strong>{{ date('d-M-Y', strtotime($startDate)) }}</strong> to <strong>{{ date('d-M-Y', strtotime($endDate)) }}</strong>
        @else
            All Transactions / Complete History
        @endif
    </div>

    <table>
        <thead>
            <tr>
                @foreach($columns as $col)
                    <th class="{{ in_array($col['key'], ['amount_due', 'amount_paid', 'discount_amount', 'fine_amount', 'total_fee', 'total_paid', 'total_discount', 'total_fine', 'outstanding_balance', 'total_collected']) ? 'text-right' : '' }}">
                        {{ $col['label'] }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
                <tr>
                    @foreach($columns as $col)
                        @php
                            $val = $row[$col['key']] ?? 'N/A';
                            $isNumeric = in_array($col['key'], ['amount_due', 'amount_paid', 'discount_amount', 'fine_amount', 'total_fee', 'total_paid', 'total_discount', 'total_fine', 'outstanding_balance', 'total_collected']);
                        @endphp
                        <td class="{{ $isNumeric ? 'text-right font-mono' : '' }} {{ $col['key'] === 'receipt_number' ? 'font-mono' : '' }}">
                            @if($isNumeric)
                                {{ number_format(floatval($val), 2) }}
                            @else
                                {{ $val }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="text-center" style="padding: 20px; color: #64748b;">
                        No records found matching filters.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
