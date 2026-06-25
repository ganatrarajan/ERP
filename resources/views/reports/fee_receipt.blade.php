<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fee Receipt - {{ $receipt->receipt_number }}</title>
    <style>
        @page {
            margin: 15px 20px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #334155;
            margin: 0;
            padding: 0;
            font-size: 11px;
            line-height: 1.35;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .receipt-copy {
            position: relative;
            padding: 10px 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .header-logo {
            width: 70px;
            vertical-align: middle;
        }
        .header-logo img {
            max-height: 50px;
            max-width: 120px;
        }
        .header-school {
            padding-left: 12px;
            vertical-align: middle;
        }
        .school-name {
            font-size: 16px;
            font-weight: bold;
            color: #4f46e5;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .school-info {
            font-size: 10px;
            color: #64748b;
            margin: 2px 0 0 0;
            line-height: 1.3;
        }
        .header-receipt {
            text-align: right;
            vertical-align: middle;
        }
        .copy-badge {
            display: inline-block;
            background-color: #e0e7ff;
            color: #4f46e5;
            font-size: 9px;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 4px;
            text-transform: uppercase;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }
        .receipt-title {
            font-size: 16px;
            font-weight: 800;
            color: #1e293b;
            margin: 0;
            text-transform: uppercase;
        }
        .receipt-num {
            font-size: 11px;
            color: #4f46e5;
            font-weight: bold;
            margin: 3px 0 0 0;
        }
        .divider {
            height: 1px;
            background-color: #e2e8f0;
            margin: 8px 0;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .info-table td {
            padding: 3px 6px;
            vertical-align: top;
        }
        .info-label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            width: 110px;
        }
        .info-value {
            font-weight: bold;
            color: #1e293b;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .details-table th {
            background-color: #f8fafc;
            border-bottom: 2px solid #cbd5e1;
            color: #475569;
            font-weight: bold;
            text-align: left;
            padding: 6px 10px;
            font-size: 10px;
            text-transform: uppercase;
        }
        .details-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }
        .text-right {
            text-align: right;
        }
        .amount-highlight {
            font-weight: 800;
            color: #4f46e5;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .summary-table td {
            padding: 3px 10px;
        }
        .summary-label {
            width: 65%;
            text-align: right;
            color: #64748b;
            font-size: 11px;
        }
        .summary-value {
            width: 35%;
            text-align: right;
            font-weight: bold;
            font-size: 11px;
            color: #1e293b;
        }
        .total-row .summary-label {
            font-weight: 800;
            color: #1e293b;
            font-size: 12px;
            border-top: 1.5px solid #cbd5e1;
            padding-top: 6px;
        }
        .total-row .summary-value {
            font-size: 13px;
            color: #4f46e5;
            font-weight: 800;
            border-top: 1.5px solid #cbd5e1;
            padding-top: 6px;
        }
        .meta-section {
            margin-top: 15px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td {
            vertical-align: top;
            padding: 4px 10px;
        }
        .payment-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
            width: 300px;
        }
        .payment-box-title {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .payment-detail-row {
            margin-bottom: 3px;
            font-size: 11px;
        }
        .payment-detail-label {
            color: #64748b;
            display: inline-block;
            width: 110px;
        }
        .payment-detail-value {
            font-weight: bold;
            color: #1e293b;
        }
        .signature-box {
            text-align: right;
            padding-top: 20px;
        }
        .signature-line {
            width: 140px;
            border-bottom: 1px solid #94a3b8;
            margin-left: auto;
            margin-bottom: 4px;
        }
        .signature-text {
            font-size: 10px;
            color: #64748b;
            padding-right: 5px;
        }
        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            border-top: 1px dashed #e2e8f0;
            padding-top: 6px;
        }
        .rupee {
            font-family: 'DejaVu Sans', sans-serif;
            font-weight: normal !important;
        }
        .cut-divider {
            border-top: 1px dashed #cbd5e1;
            text-align: center;
            margin: 15px 0;
            position: relative;
            height: 10px;
        }
        .cut-divider span {
            position: absolute;
            top: -9px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #ffffff;
            padding: 0 15px;
            font-size: 9px;
            color: #94a3b8;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
    </style>
</head>
<body>

<div class="container">
    @foreach(['School', 'Parent'] as $copy_type)
        <div class="receipt-copy">
            <!-- Header -->
            <table class="header-table">
                <tr>
                    @if($logo_base64)
                        <td class="header-logo">
                            <img src="{{ $logo_base64 }}" alt="Logo">
                        </td>
                    @endif
                    <td class="header-school">
                        <h1 class="school-name">{{ $school->name }}</h1>
                        <p class="school-info">
                            {{ $school->address }}<br>
                            Phone: {{ $school->phone }} | Email: {{ $school->email }}
                        </p>
                    </td>
                    <td class="header-receipt">
                        <span class="copy-badge">{{ $copy_type }} Copy</span>
                        <h2 class="receipt-title">Payment Receipt</h2>
                        <p class="receipt-num">Receipt #: {{ $receipt->receipt_number }}</p>
                    </td>
                </tr>
            </table>

            <div class="divider"></div>

            <!-- Student & Session Info -->
            <table class="info-table">
                <tr>
                    <td style="width: 50%;">
                        <table>
                            <tr>
                                <td class="info-label">Student Name:</td>
                                <td class="info-value">{{ $student->first_name }} {{ $student->last_name }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Admission No:</td>
                                <td class="info-value">{{ $student->admission_no }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Class & Section:</td>
                                <td class="info-value">{{ $class_name }} - {{ $section_name }}</td>
                            </tr>
                        </table>
                    </td>
                    <td style="width: 50%;">
                        <table>
                            <tr>
                                <td class="info-label">Academic Year:</td>
                                <td class="info-value">{{ $academic_year_title }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Date Generated:</td>
                                <td class="info-value">{{ \Carbon\Carbon::parse($receipt->generated_at)->format('d M Y, h:i A') }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Payment Date:</td>
                                <td class="info-value">{{ \Carbon\Carbon::parse($collection->payment_date)->format('d M Y') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- Payment Item Breakdown -->
            <table class="details-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-right">Dues Outstanding</th>
                        <th class="text-right">Discount Applied</th>
                        <th class="text-right">Fine Calculated</th>
                        <th class="text-right">Paid Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-weight: 600; color: #1e293b;">
                            {{ $installment->installment_name }}
                            <span style="display: block; font-size: 10px; font-weight: normal; color: #64748b; margin-top: 3px;">
                                Due Date: {{ \Carbon\Carbon::parse($installment->due_date)->format('d M Y') }}
                            </span>
                        </td>
                        <td class="text-right"><span class="rupee">&#8377;</span>{{ number_format($collection->amount_due, 2) }}</td>
                        <td class="text-right"><span class="rupee">&#8377;</span>{{ number_format($collection->discount_amount, 2) }}</td>
                        <td class="text-right"><span class="rupee">&#8377;</span>{{ number_format($collection->fine_amount, 2) }}</td>
                        <td class="text-right amount-highlight"><span class="rupee">&#8377;</span>{{ number_format($collection->amount_paid, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Totals & Summary Block -->
            <table style="width: 100%;">
                <tr>
                    <td style="width: 50%; vertical-align: top;">
                        <div class="payment-box">
                            <div class="payment-box-title">Transaction Details</div>
                            <div class="payment-detail-row">
                                <span class="payment-detail-label">Payment Mode:</span>
                                <span class="payment-detail-value">{{ $collection->payment_method }}</span>
                            </div>
                            @if($collection->transaction_reference)
                                <div class="payment-detail-row">
                                    <span class="payment-detail-label">Ref / Cheque #:</span>
                                    <span class="payment-detail-value">{{ $collection->transaction_reference }}</span>
                                </div>
                            @endif
                            <div class="payment-detail-row">
                                <span class="payment-detail-label">Collected By:</span>
                                <span class="payment-detail-value">{{ $collected_by_name }}</span>
                            </div>
                            @if($collection->remarks)
                                <div class="payment-detail-row" style="margin-top: 6px; font-style: italic; font-size: 10px; color: #64748b;">
                                    <strong>Remarks:</strong> {{ $collection->remarks }}
                                </div>
                            @endif
                        </div>
                    </td>
                    <td style="width: 50%; vertical-align: top;">
                        <table class="summary-table">
                            <tr>
                                <td class="summary-label">Installment Base Paid:</td>
                                <td class="summary-value"><span class="rupee">&#8377;</span>{{ number_format($collection->amount_paid, 2) }}</td>
                            </tr>
                            @if($collection->discount_amount > 0)
                                <tr>
                                    <td class="summary-label">Discount:</td>
                                    <td class="summary-value" style="color: #16a34a;">- <span class="rupee">&#8377;</span>{{ number_format($collection->discount_amount, 2) }}</td>
                                </tr>
                            @endif
                            @if($collection->fine_amount > 0)
                                <tr>
                                    <td class="summary-label">Fine Charges Paid:</td>
                                    <td class="summary-value" style="color: #dc2626;">+ <span class="rupee">&#8377;</span>{{ number_format($collection->fine_amount, 2) }}</td>
                                </tr>
                            @endif
                            <tr class="total-row">
                                <td class="summary-label">Total Amount Paid:</td>
                                <td class="summary-value"><span class="rupee">&#8377;</span>{{ number_format($collection->amount_paid + $collection->fine_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="summary-label" style="padding-top: 6px; border-top: 1px dashed #cbd5e1; font-weight: bold;">Remaining Dues:</td>
                                <td class="summary-value" style="padding-top: 6px; border-top: 1px dashed #cbd5e1; font-weight: bold; color: {{ ($collection->amount_due - ($collection->amount_paid + $collection->discount_amount)) > 0 ? '#dc2626' : '#16a34a' }};">
                                    <span class="rupee">&#8377;</span>{{ number_format(max(0, $collection->amount_due - ($collection->amount_paid + $collection->discount_amount)), 2) }}
                                    <span style="display: block; font-size: 8px; font-weight: normal; margin-top: 2px;">
                                        {{ ($collection->amount_due - ($collection->amount_paid + $collection->discount_amount)) > 0 ? '(Partially Paid)' : '(Fully Paid)' }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- Signatures -->
            <div class="meta-section">
                <table class="meta-table">
                    <tr>
                        <td style="width: 60%;">
                            <div style="font-size: 10px; color: #64748b; padding-top: 20px;">
                                * This is a computer generated receipt and does not require a physical stamp/signature.
                            </div>
                        </td>
                        <td style="width: 40%;">
                            <div class="signature-box">
                                <div class="signature-line"></div>
                                <div class="signature-text">Authorized Signatory</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Footer -->
            <div class="footer">
                Thank you for your prompt payment! If you have any queries, please contact the school administration office.
            </div>
        </div>

        @if(!$loop->last)
            <div class="cut-divider">
                <span>✂--- CUT HERE FOR {{ strtoupper($copy_type) }} COPY ---✂</span>
            </div>
        @endif
    @endforeach
</div>

</body>
</html>
