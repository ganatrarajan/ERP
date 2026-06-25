<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Detailed Report Card</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 12px;
            line-height: 1.35;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        .page-border-fixed {
            position: fixed;
            top: 10px;
            bottom: 10px;
            left: 10px;
            right: 10px;
            border: 2px double #1e3a8a;
            z-index: -1000;
        }
        .page {
            padding: 25px;
            page-break-after: always;
            box-sizing: border-box;
        }
        .page:last-child {
            page-break-after: avoid;
        }
        .school-header {
            margin-bottom: 10px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 8px;
        }
        .header-layout-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }
        .header-layout-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
        .school-logo {
            max-height: 55px;
            max-width: 140px;
            display: inline-block;
        }
        .school-name {
            font-size: 20px;
            font-weight: 800;
            color: #1e3a8a;
            margin: 0 0 3px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .school-details {
            font-size: 10px;
            color: #475569;
            margin: 0;
            line-height: 1.4;
        }
        .report-title-banner {
            text-align: center;
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 6px 12px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .student-info-table {
            width: 100%;
            margin-bottom: 10px;
            background-color: #f0f7ff;
            border: 1px solid #bfdbfe;
            padding: 6px;
            border-collapse: collapse;
        }
        .student-info-table td {
            padding: 4px 8px;
            border: none;
            font-size: 11px;
        }
        .label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            color: #475569;
            letter-spacing: 0.5px;
            width: 18%;
        }
        .value {
            font-size: 11px;
            color: #0f172a;
            font-weight: 600;
            width: 32%;
        }
        .section-title {
            color: #1e3a8a;
            font-size: 11px;
            font-weight: bold;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 2px;
            margin-top: 8px;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .marks-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .marks-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
            padding: 6px 5px;
            border: 1px solid #1e3a8a;
        }
        .marks-table td {
            padding: 5px 5px;
            border: 1px solid #e2e8f0;
            color: #334155;
            font-size: 11px;
        }
        .marks-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-center {
            text-align: center;
        }
        .summary-box {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .summary-box td {
            padding: 5px 8px;
            border: 1px solid #cbd5e1;
            color: #334155;
            font-size: 11px;
        }
        .summary-title {
            font-weight: bold;
            background-color: #f8fafc;
            color: #1e3a8a;
        }
        .badge {
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 750;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }
        .badge-pass {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .badge-fail {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .signatures-table {
            width: 100%;
            margin-top: 25px;
        }
        .signatures-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
        }
        .sig-line {
            border-top: 1px solid #cbd5e1;
            width: 70%;
            margin: 0 auto 5px auto;
            padding-top: 6px;
            color: #475569;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    <div class="page-border-fixed"></div>
    @foreach ($reportCards as $rc)
        <div class="page">
            <!-- Header -->
            <div class="school-header">
                <table class="header-layout-table">
                    <tr>
                        <td style="width: 70%; text-align: left;">
                            <h1 class="school-name">{{ $rc['school']->name }}</h1>
                            <p class="school-details">
                                {{ $rc['school']->address }}<br>
                                Phone: {{ $rc['school']->phone }} | Email: {{ $rc['school']->email }}
                            </p>
                        </td>
                        <td style="width: 30%; text-align: right;">
                            @if (isset($logo_base64) && $logo_base64)
                                <img src="{{ $logo_base64 }}" alt="Logo" class="school-logo">
                            @elseif ($rc['school']->logo)
                                <img src="{{ $rc['school']->logo }}" alt="Logo" class="school-logo">
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Banner -->
            <div class="report-title-banner">
                @if (isset($rc['exam2']) && $rc['exam2'])
                    Consolidated Detailed Progress Report<br>
                    <span style="font-size: 9px; font-weight: normal; text-transform: none;">{{ $rc['exam']->name }} &amp; {{ $rc['exam2']->name }}</span>
                @else
                    Detailed Progress Report<br>
                    <span style="font-size: 10px; font-weight: normal; text-transform: none;">{{ $rc['exam']->name }}</span>
                @endif
            </div>

            <!-- Student Profile -->
            <table class="student-info-table" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="label">Student Name:</td>
                    <td class="value"><strong>{{ $rc['student']->first_name }} {{ $rc['student']->last_name }}</strong></td>
                    <td class="label">Admission No:</td>
                    <td class="value">{{ $rc['admission_no'] }}</td>
                </tr>
                <tr>
                    <td class="label">Class/Section:</td>
                    <td class="value">{{ $rc['class']->name }} - {{ $rc['section']->name }}</td>
                    <td class="label">Roll Number:</td>
                    <td class="value">{{ $rc['roll_no'] }}</td>
                </tr>
                <tr>
                    <td class="label">Academic Year:</td>
                    <td class="value">{{ $rc['academic_year']->title }}</td>
                    <td class="label">Attendance:</td>
                    <td class="value">{{ $rc['attendance']['present_days'] }} / {{ $rc['attendance']['total_days'] }} Days ({{ $rc['attendance']['attendance_rate'] }}%)</td>
                </tr>
            </table>

            <!-- Scholastic Performance -->
            <div class="section-title">Part 1: Scholastic Areas</div>
            <table class="marks-table">
                @if (isset($rc['exam2']) && $rc['exam2'])
                    <thead>
                        <tr>
                            <th style="width: 25%;">Subject</th>
                            <th class="text-center" style="width: 15%;">{{ $rc['exam']->name }}<br><span style="font-size: 8px; font-weight: normal;">Obt/Max</span></th>
                            <th class="text-center" style="width: 15%;">{{ $rc['exam2']->name }}<br><span style="font-size: 8px; font-weight: normal;">Obt/Max</span></th>
                            <th class="text-center" style="width: 15%;">Combined Total<br><span style="font-size: 8px; font-weight: normal;">Obt/Max</span></th>
                            <th class="text-center" style="width: 10%;">Combined Grade</th>
                            <th style="width: 20%;">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rc['scholastic_subjects'] as $subj)
                            <tr>
                                <td>
                                    <strong>{{ $subj['subject_name'] }}</strong>
                                    <span style="display: block; font-size: 9px; color: #64748b;">{{ $subj['subject_code'] }}</span>
                                </td>
                                <td class="text-center">
                                    @if (!$subj['exam1_scheduled'])
                                        -
                                    @elseif ($subj['evaluation_type'] === 'grades')
                                        {{ $subj['exam1_grade'] ?: '-' }}
                                    @else
                                        {{ $subj['exam1_obtained_marks'] !== null ? $subj['exam1_obtained_marks'] : '-' }} / {{ $subj['exam1_max_marks'] }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if (!$subj['exam2_scheduled'])
                                        -
                                    @elseif ($subj['evaluation_type'] === 'grades')
                                        {{ $subj['exam2_grade'] ?: '-' }}
                                    @else
                                        {{ $subj['exam2_obtained_marks'] !== null ? $subj['exam2_obtained_marks'] : '-' }} / {{ $subj['exam2_max_marks'] }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($subj['evaluation_type'] === 'grades')
                                        -
                                    @else
                                        {{ $subj['obtained_marks'] !== null ? $subj['obtained_marks'] : '-' }} / {{ $subj['max_marks'] ?: '-' }}
                                    @endif
                                </td>
                                <td class="text-center"><strong>{{ $subj['grade'] }}</strong></td>
                                <td style="font-size: 11px;">{{ $subj['remarks'] ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                @else
                    <thead>
                        <tr>
                            <th style="width: 30%;">Subject</th>
                            <th class="text-center" style="width: 12%;">Max Marks</th>
                            <th class="text-center" style="width: 12%;">Pass Marks</th>
                            <th class="text-center" style="width: 15%;">Marks Obtained</th>
                            <th class="text-center" style="width: 10%;">Grade</th>
                            <th style="width: 21%;">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rc['scholastic_subjects'] as $subj)
                            <tr>
                                <td>
                                    <strong>{{ $subj['subject_name'] }}</strong>
                                    <span style="display: block; font-size: 9px; color: #64748b;">{{ $subj['subject_code'] }}</span>
                                </td>
                                <td class="text-center">{{ $subj['evaluation_type'] === 'grades' ? '-' : $subj['max_marks'] }}</td>
                                <td class="text-center">{{ $subj['evaluation_type'] === 'grades' ? '-' : $subj['passing_marks'] }}</td>
                                <td class="text-center">
                                    @if ($subj['evaluation_type'] === 'grades')
                                        -
                                    @else
                                        {{ $subj['obtained_marks'] !== null ? $subj['obtained_marks'] : '-' }}
                                    @endif
                                </td>
                                <td class="text-center"><strong>{{ $subj['grade'] }}</strong></td>
                                <td style="font-size: 11px;">{{ $subj['remarks'] ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                @endif
            </table>

            <!-- Co-Scholastic Performance -->
            @if (count($rc['co_scholastic_subjects']) > 0)
                <div class="section-title">Part 2: Co-Scholastic Activities</div>
                <table class="marks-table">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Activity/Subject</th>
                            <th class="text-center" style="width: 20%;">Grade</th>
                            <th style="width: 30%;">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rc['co_scholastic_subjects'] as $subj)
                            <tr>
                                <td><strong>{{ $subj['subject_name'] }}</strong></td>
                                <td class="text-center"><strong>{{ $subj['grade'] }}</strong></td>
                                <td style="font-size: 11px;">{{ $subj['remarks'] ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <!-- Results Summary -->
            <div class="section-title">Summary &amp; Decision</div>
            <table class="summary-box">
                @if (isset($rc['exam2']) && $rc['exam2'])
                    <tr>
                        <td class="summary-title" style="width: 20%;">{{ $rc['exam']->name }}:</td>
                        <td style="width: 30%;">
                            @if ($rc['exam1_obtained_marks'] !== null)
                                {{ $rc['exam1_obtained_marks'] }} / {{ $rc['exam1_max_marks'] }} ({{ $rc['exam1_percentage'] }}%, Grade: {{ $rc['exam1_grade'] }})
                            @else
                                -
                            @endif
                        </td>
                        <td class="summary-title" style="width: 20%;">{{ $rc['exam2']->name }}:</td>
                        <td style="width: 30%;">
                            @if ($rc['exam2_obtained_marks'] !== null)
                                {{ $rc['exam2_obtained_marks'] }} / {{ $rc['exam2_max_marks'] }} ({{ $rc['exam2_percentage'] }}%, Grade: {{ $rc['exam2_grade'] }})
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="summary-title">Combined Score:</td>
                        <td><strong>{{ $rc['total_obtained_marks'] }} / {{ $rc['total_max_marks'] }}</strong></td>
                        <td class="summary-title">Combined Percentage:</td>
                        <td><strong>{{ $rc['percentage'] }}%</strong></td>
                    </tr>
                    <tr>
                        <td class="summary-title">Combined Grade:</td>
                        <td><strong>{{ $rc['grade'] }}</strong></td>
                        <td class="summary-title">Result Status:</td>
                        <td>
                            <span class="badge {{ $rc['result'] === 'Pass' ? 'badge-pass' : 'badge-fail' }}">
                                {{ $rc['result'] }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="summary-title">Class Rank:</td>
                        <td colspan="3"><strong>{{ $rc['rank'] }}</strong></td>
                    </tr>
                @else
                    <tr>
                        <td class="summary-title" style="width: 25%;">Total Obtained / Max:</td>
                        <td style="width: 25%;">{{ $rc['total_obtained_marks'] }} / {{ $rc['total_max_marks'] }}</td>
                        <td class="summary-title" style="width: 25%;">Percentage:</td>
                        <td style="width: 25%;"><strong>{{ $rc['percentage'] }}%</strong></td>
                    </tr>
                    <tr>
                        <td class="summary-title">Overall Grade:</td>
                        <td><strong>{{ $rc['grade'] }}</strong></td>
                        <td class="summary-title">Result Status:</td>
                        <td>
                            <span class="badge {{ $rc['result'] === 'Pass' ? 'badge-pass' : 'badge-fail' }}">
                                {{ $rc['result'] }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="summary-title">Class Rank:</td>
                        <td colspan="3"><strong>{{ $rc['rank'] }}</strong></td>
                    </tr>
                @endif
            </table>

            <!-- Grading Scale Legend -->
            @if (isset($grades) && count($grades) > 0)
                <div class="section-title" style="font-size: 10px; margin-top: 10px;">Grading Scale</div>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 10px;">
                    <thead>
                        <tr style="background-color: #f1f5f9;">
                            <th style="border: 1px solid #e2e8f0; padding: 4px; text-align: center; font-weight: bold; color: #475569; width: 15%;">Grade</th>
                            @foreach ($grades as $g)
                                <th style="border: 1px solid #e2e8f0; padding: 4px; text-align: center;">{{ $g->grade }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="border: 1px solid #e2e8f0; padding: 4px; text-align: center; font-weight: bold; color: #475569;">Marks Range</td>
                            @foreach ($grades as $g)
                                <td style="border: 1px solid #e2e8f0; padding: 4px; text-align: center;">
                                    &gt;= {{ number_format($g->min_percentage, 0) }}%
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            @endif

            <!-- Signatures -->
            <table class="signatures-table" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                        <div class="sig-line">Class Teacher</div>
                    </td>
                    <td>
                        <div style="font-size: 10px; color: #64748b; margin-bottom: 10px;">Date of Issue: {{ $printDate }}</div>
                    </td>
                    <td>
                        <div class="sig-line">Principal</div>
                    </td>
                </tr>
            </table>
        </div>
    @endforeach
</body>
</html>
