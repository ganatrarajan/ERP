<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeReceipt;
use App\Models\School;
use App\Models\StudentAcademicRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReceiptController extends Controller
{
    /**
     * Display a listing of receipts.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('receipt.view');

        $user = $request->user();
        $query = FeeReceipt::with(['collection.student', 'collection.installment']);

        if (!$user->isSuperAdmin()) {
            $query->where('school_id', $user->school_id);
        } elseif ($request->has('school_id')) {
            $query->where('school_id', $request->input('school_id'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('receipt_number', 'like', "%{$search}%");
        }

        if ($request->has('academic_year_id')) {
            $query->whereHas('collection', function ($q) use ($request) {
                $q->where('academic_year_id', $request->input('academic_year_id'));
            });
        }

        if ($request->has('student_id')) {
            $query->whereHas('collection', function ($q) use ($request) {
                $q->where('student_id', $request->input('student_id'));
            });
        }

        $perPage = $request->input('per_page', 10);
        $receipts = $query->orderBy('generated_at', 'desc')->paginate($perPage);

        return response()->json($receipts);
    }

    /**
     * Download or stream PDF receipt.
     */
    public function downloadPdf($id, Request $request)
    {
        $this->authorize('receipt.view');

        $user = $request->user();
        $receipt = FeeReceipt::with(['collection.installment', 'collection.student', 'collection.collectedBy'])->findOrFail($id);

        if (!$user->isSuperAdmin() && $receipt->school_id !== $user->school_id) {
            abort(403, 'This action is unauthorized.');
        }

        $collection = $receipt->collection;
        $student = $collection->student;
        $installment = $collection->installment;
        $school = School::findOrFail($receipt->school_id);

        // Fetch student academic details for the class/section of this collection's academic year
        $academicRecord = StudentAcademicRecord::with(['class', 'section', 'academicYear'])
            ->where('student_id', $student->id)
            ->where('academic_year_id', $collection->academic_year_id)
            ->first();

        $className = $academicRecord && $academicRecord->class ? $academicRecord->class->name : 'N/A';
        $sectionName = $academicRecord && $academicRecord->section ? $academicRecord->section->name : 'N/A';
        $academicYearTitle = $academicRecord && $academicRecord->academicYear ? $academicRecord->academicYear->title : 'N/A';

        // Prepare logo base64 using helper
        $logoBase64 = $this->getLogoBase64($school->logo);

        $pdfData = [
            'school' => $school,
            'receipt' => $receipt,
            'collection' => $collection,
            'student' => $student,
            'installment' => $installment,
            'class_name' => $className,
            'section_name' => $sectionName,
            'academic_year_title' => $academicYearTitle,
            'collected_by_name' => $collection->collectedBy ? $collection->collectedBy->name : 'N/A',
            'logo_base64' => $logoBase64,
        ];

        $pdf = Pdf::loadView('reports.fee_receipt', $pdfData);
        $filename = 'Receipt_' . $receipt->receipt_number . '.pdf';
        
        return $pdf->stream($filename);
    }

    /**
     * Base64 conversion helper for logos to render perfectly inside DomPDF templates.
     */
    private function getLogoBase64($logoUrl)
    {
        if (!$logoUrl) {
            return null;
        }

        if (str_starts_with($logoUrl, 'data:image')) {
            return $logoUrl;
        }

        $path = parse_url($logoUrl, PHP_URL_PATH);
        if ($path) {
            $relativePath = ltrim($path, '/');
            $absolutePath = public_path($relativePath);

            if (file_exists($absolutePath) && is_file($absolutePath)) {
                $type = pathinfo($absolutePath, PATHINFO_EXTENSION);
                $data = file_get_contents($absolutePath);
                return 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        }

        $fallbackPath = public_path($logoUrl);
        if (file_exists($fallbackPath) && is_file($fallbackPath)) {
            $type = pathinfo($fallbackPath, PATHINFO_EXTENSION);
            $data = file_get_contents($fallbackPath);
            return 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        return null;
    }
}
