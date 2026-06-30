<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student Directory Custom Report</title>
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
            border-bottom: 2px solid #3b82f6;
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
            background-color: #1e3a8a;
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
            vertical-align: top;
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
        Student Directory - Custom Report
    </div>

    <!-- Student Table -->
    <table class="report-table">
        <thead>
            <tr>
                @foreach ($columns as $col)
                    <th>{{ $columnMap[$col] ?? ucfirst(str_replace('_', ' ', $col)) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($students as $student)
                <tr>
                    @foreach ($columns as $col)
                        <td>
                            @if (in_array($col, [
                                'admission_no', 'gr_no', 'first_name', 'last_name', 'gender', 'blood_group', 
                                'category', 'religion', 'nationality', 'aadhaar_no', 'pen_no', 'udise_no', 
                                'house', 'mobile', 'email', 'address', 'previous_school_name', 'previous_school_tc_no',
                                'emergency_contact_name', 'emergency_contact_mobile', 'emergency_contact_email', 'status'
                            ]))
                                {{ $student->$col }}
                            @elseif ($col === 'date_of_birth')
                                {{ $student->date_of_birth ? $student->date_of_birth->format('Y-m-d') : '' }}
                            @elseif ($col === 'admission_date')
                                {{ $student->admission_date ? $student->admission_date->format('Y-m-d') : '' }}
                            @elseif (in_array($col, ['roll_no', 'class_name', 'section_name', 'academic_year_title']))
                                {{ $student->$col }}
                            @elseif (in_array($col, [
                                'father_name', 'father_mobile', 'father_email', 'mother_name', 'mother_mobile', 
                                'mother_email', 'guardian_name', 'guardian_mobile'
                            ]))
                                {{ $student->parent ? $student->parent->$col : '' }}
                            @else
                                &nbsp;
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" style="text-align: center; padding: 15px;">
                        No student records found matching filters.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generated by EduvoraX ERP. Page 1 of 1
    </div>
</body>
</html>
