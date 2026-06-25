<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AcademicYear;
use App\Models\FeeDiscount;
use App\Models\FeeFineRule;

$schools = App\Models\School::all();

foreach ($schools as $school) {
    $activeYear = AcademicYear::where('school_id', $school->id)
        ->where('is_current', true)
        ->where('is_delete', 0)
        ->first();

    if ($activeYear) {
        $discountCount = FeeDiscount::where('school_id', $school->id)
            ->whereNull('academic_year_id')
            ->update(['academic_year_id' => $activeYear->id]);

        $fineCount = FeeFineRule::where('school_id', $school->id)
            ->whereNull('academic_year_id')
            ->update(['academic_year_id' => $activeYear->id]);

        echo "School: {$school->name} (ID: {$school->id}) - Updated {$discountCount} discounts and {$fineCount} fine rules to Academic Year ID: {$activeYear->id}\n";
    } else {
        echo "School: {$school->name} (ID: {$school->id}) - No active academic year found.\n";
    }
}
echo "Done!\n";
