<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admit Card / Hall Ticket</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #334155;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        .page-border-fixed {
            position: fixed;
            top: 12px;
            bottom: 12px;
            left: 12px;
            right: 12px;
            border: 1px solid #0d9488;
            z-index: -1000;
        }
        .page {
            padding: 25px;
            page-break-after: always;
            box-sizing: border-box;
            position: relative;
        }
        .page:last-child {
            page-break-after: avoid;
        }
        .school-header {
            margin-bottom: 12px;
            border-bottom: 2px solid #0d9488;
            padding-bottom: 10px;
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
            max-height: 50px;
            max-width: 130px;
            display: inline-block;
        }
        .school-name {
            font-size: 17px;
            font-weight: 800;
            color: #0f766e;
            margin: 0 0 2px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .school-details {
            font-size: 9px;
            color: #64748b;
            margin: 0;
            line-height: 1.35;
        }
        .admit-card-title-box {
            text-align: center;
            border: 1px solid #0d9488;
            color: #0f766e;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 6px 12px;
            margin: 10px auto 16px auto;
            width: 55%;
            border-radius: 4px;
            background-color: #f0fdfa;
        }
        .student-info-table {
            width: 100%;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
            border-collapse: collapse;
        }
        .student-info-table td {
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            background-color: #f8fafc;
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
            color: #0f766e;
            font-size: 11px;
            font-weight: bold;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
            margin-top: 15px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .schedule-table th {
            background-color: #0f766e;
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
            padding: 7px 8px;
            border: 1px solid #0f766e;
        }
        .schedule-table td {
            padding: 7px 8px;
            border: 1px solid #e2e8f0;
            color: #334155;
            font-size: 10px;
        }
        .schedule-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-center {
            text-align: center;
        }
        .instructions-box {
            border-left: 4px solid #0d9488;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            padding: 8px 12px;
            margin-top: 15px;
            margin-bottom: 15px;
            border-radius: 0 4px 4px 0;
        }
        .instructions-box h4 {
            margin: 0 0 5px 0;
            color: #0f766e;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .instructions-box ul {
            margin: 0;
            padding-left: 15px;
            color: #475569;
            font-size: 9px;
        }
        .instructions-box li {
            margin-bottom: 3px;
        }
        .signatures-table {
            width: 100%;
            margin-top: 30px;
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
            padding-top: 4px;
            color: #64748b;
            font-weight: 600;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    <div class="page-border-fixed"></div>
    @foreach ($admitCards as $ac)
        <div class="page">
            <!-- Header -->
            <div class="school-header">
                <table class="header-layout-table">
                    <tr>
                        <td style="width: 70%; text-align: left;">
                            <h1 class="school-name">{{ $school->name }}</h1>
                            <p class="school-details">
                                {{ $school->address }}<br>
                                Phone: {{ $school->phone }} | Email: {{ $school->email }}
                            </p>
                        </td>
                        <td style="width: 30%; text-align: right;">
                            @if (isset($logo_base64) && $logo_base64)
                                <img src="{{ $logo_base64 }}" alt="Logo" class="school-logo">
                            @elseif ($school->logo)
                                <img src="{{ $school->logo }}" alt="Logo" class="school-logo">
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Title Box (Different from solid banner) -->
            <div class="admit-card-title-box">
                Admit Card / Hall Ticket<br>
                <span style="font-size: 8px; font-weight: normal; text-transform: none; color: #64748b; display: block; margin-top: 2px;">
                    {{ $exam->name }} (Academic Year: {{ $academic_year->title }})
                </span>
            </div>

            <!-- Student Profile Info -->
            <table class="student-info-table" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="label">Student Name:</td>
                    <td class="value"><strong>{{ $ac['student']->first_name }} {{ $ac['student']->last_name }}</strong></td>
                    <td class="label">Admission No:</td>
                    <td class="value">{{ $ac['admission_no'] }}</td>
                </tr>
                <tr>
                    <td class="label">Class/Section:</td>
                    <td class="value">{{ $class->name }} - {{ $section->name }}</td>
                    <td class="label">Roll Number:</td>
                    <td class="value">{{ $ac['roll_no'] ?: 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Father's Name:</td>
                    <td class="value">{{ $ac['father_name'] }}</td>
                    <td class="label">Gender:</td>
                    <td class="value" style="text-transform: capitalize;">{{ $ac['student']->gender }}</td>
                </tr>
            </table>

            <!-- Timetable / Schedules -->
            <div class="section-title">Exam Timetable & Schedule</div>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th style="width: 45%; text-align: left;">Subject</th>
                        <th class="text-center" style="width: 20%;">Date</th>
                        <th class="text-center" style="width: 22%;">Time</th>
                        <th class="text-center" style="width: 13%;">Max Marks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ac['schedules'] as $sched)
                        <tr>
                            <td>
                                <strong>{{ $sched['subject_name'] }}</strong>
                                <span style="display: block; font-size: 8px; color: #64748b;">{{ $sched['subject_code'] ?: 'N/A' }}</span>
                            </td>
                            <td class="text-center">{{ $sched['exam_date'] }}</td>
                            <td class="text-center">{{ $sched['start_time'] }} - {{ $sched['end_time'] }}</td>
                            <td class="text-center">{{ $sched['max_marks'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Candidate Instructions Box (Different format) -->
            <div class="instructions-box">
                <h4>Candidate Instructions</h4>
                <ul>
                    <li>Candidates must bring a printed copy of this Admit Card to every examination session.</li>
                    <li>Please report to the examination hall at least 15 minutes before the scheduled start time.</li>
                    <li>No electronic devices, cell phones, calculators, or unauthorized materials are permitted inside the hall.</li>
                    <li>Write your admission number and roll number clearly on all answer booklets.</li>
                </ul>
            </div>

            <!-- Signatures -->
            <table class="signatures-table">
                <tr>
                    <td>
                        <div class="sig-line">Candidate Signature</div>
                    </td>
                    <td>
                        <div class="sig-line">Invigilator Signature</div>
                    </td>
                    <td>
                        <div class="sig-line">Principal Signature</div>
                    </td>
                </tr>
            </table>
        </div>
    @endforeach
</body>
</html>
