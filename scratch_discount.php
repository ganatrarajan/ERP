<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$student = App\Models\Student::where('admission_no', 'ADM-2026-0012')->first();

if ($student) {
    $discounts = App\Models\FeeDiscount::where('student_id', $student->id)->get();
    echo "Discounts Count: " . $discounts->count() . "\n";
    foreach ($discounts as $d) {
        echo "ID: " . $d->id . "\n";
        echo "Student ID: " . $d->student_id . "\n";
        echo "School ID: " . $d->school_id . "\n";
        echo "Type: " . $d->discount_type . "\n";
        echo "Value: " . $d->discount_value . "\n";
        echo "Status: " . $d->status . "\n";
        echo "Is Delete: " . ($d->is_delete ? 'true' : 'false') . "\n";
        echo "Start Date: " . ($d->start_date ? $d->start_date->format('Y-m-d') : 'null') . "\n";
        echo "End Date: " . ($d->end_date ? $d->end_date->format('Y-m-d') : 'null') . "\n";
    }
} else {
    echo "Student ADM-2026-0012 not found.\n";
}
