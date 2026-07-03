<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Teacher Assignment Allocations Directory</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 10px;
            line-height: 1.3;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #6366f1;
            margin-bottom: 15px;
            padding-bottom: 5px;
        }
        .header-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
        .school-name {
            font-size: 16px;
            font-weight: 800;
            color: #1e3a8a;
            margin: 0 0 2px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .school-details {
            font-size: 9px;
            color: #475569;
            margin: 0;
        }
        .report-title-banner {
            text-align: center;
            background-color: #4f46e5;
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 4px 10px;
            margin-bottom: 15px;
            border-radius: 3px;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .report-table th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: 700;
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            text-align: left;
            font-size: 8px;
            text-transform: uppercase;
        }
        .report-table td {
            border: 1px solid #e2e8f0;
            padding: 4px 6px;
            font-size: 8px;
            color: #334155;
            vertical-align: middle;
        }
        .report-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td>
                <div class="school-name">{{ $school->name }}</div>
                <div class="school-details">
                    Email: {{ $school->email ?? 'N/A' }} | Phone: {{ $school->phone ?? 'N/A' }}
                </div>
            </td>
            <td style="text-align: right; font-size: 8px; color: #64748b;">
                Report Date: {{ date('d-M-Y H:i') }}
            </td>
        </tr>
    </table>

    <div class="report-title-banner">
        Teacher Assignment Allocations Directory
    </div>

    <table class="report-table">
        <thead>
            <tr>
                @if (in_array('teacher_name', $columns)) <th>Teacher Name</th> @endif
                @if (in_array('teacher_code', $columns)) <th>Employee ID</th> @endif
                @if (in_array('teacher_email', $columns)) <th>Email</th> @endif
                @if (in_array('academic_session', $columns)) <th>Academic Session</th> @endif
                @if (in_array('class', $columns)) <th>Class</th> @endif
                @if (in_array('section', $columns)) <th>Section</th> @endif
                @if (in_array('subject', $columns)) <th>Subject</th> @endif
                @if (in_array('type', $columns)) <th>Assignment Type</th> @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($assignments as $item)
                <tr>
                    @if (in_array('teacher_name', $columns)) <td>{{ $item->teacher?->name }}</td> @endif
                    @if (in_array('teacher_code', $columns)) <td>{{ $item->teacher?->employee_id ?? 'N/A' }}</td> @endif
                    @if (in_array('teacher_email', $columns)) <td>{{ $item->teacher?->email }}</td> @endif
                    @if (in_array('academic_session', $columns)) <td>{{ $item->academicYear?->title }}</td> @endif
                    @if (in_array('class', $columns)) <td>{{ $item->class?->name }}</td> @endif
                    @if (in_array('section', $columns)) <td>{{ $item->section?->name ?? 'All' }}</td> @endif
                    @if (in_array('subject', $columns)) <td>{{ $item->subject?->name ?? 'Class Teacher Allocation' }}</td> @endif
                    @if (in_array('type', $columns))
                        <td style="font-weight: bold; color: {{ $item->is_class_teacher ? '#d97706' : '#4f46e5' }};">
                            {{ $item->is_class_teacher ? 'Class Teacher' : 'Subject Teacher' }}
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 15px;">
                        No assignments found matching filters.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generated by EduvoraX ERP.
    </div>
</body>
</html>
