<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    protected $fillable = [
        'school_id',
        'title',
        'start_date',
        'end_date',
        'is_current',
        'status',
        'is_delete',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected static function booted()
    {
        static::addGlobalScope('web_erp_filter', function ($builder) {
            // Do not apply to mobile requests
            if (request()->is('api/mobile/*') || request()->is('mobile/*')) {
                return;
            }

            // Get selected/active year ID
            $user = auth()->user();
            if (!$user) {
                return;
            }

            $activeYearId = \App\Support\AcademicYearContext::getActiveAcademicYearId($user->school_id);
            if ($activeYearId) {
                $query = $builder->getQuery();
                $bindings = $query->getRawBindings()['where'] ?? [];
                $bindingIndex = 0;

                foreach ($query->wheres as &$where) {
                    $type = $where['type'] ?? 'Basic';
                    $consumesBindings = 0;

                    if ($type === 'Basic' || $type === 'like') {
                        $consumesBindings = 1;
                    } elseif ($type === 'In' || $type === 'NotIn') {
                        $consumesBindings = count($where['values'] ?? []);
                    } elseif ($type === 'Between' || $type === 'NotBetween') {
                        $consumesBindings = 2;
                    }

                    if (isset($where['column']) && 
                        ($where['column'] === 'is_current' || $where['column'] === 'academic_years.is_current') &&
                        isset($where['value']) && ($where['value'] === true || $where['value'] == 1 || $where['value'] === '1')) {
                        
                        $where['column'] = ($where['column'] === 'academic_years.is_current') ? 'academic_years.id' : 'id';
                        $where['value'] = $activeYearId;
                        if (isset($where['operator'])) {
                            $where['operator'] = '=';
                        }

                        if (isset($bindings[$bindingIndex])) {
                            $bindings[$bindingIndex] = $activeYearId;
                        }
                    }

                    $bindingIndex += $consumesBindings;
                }

                $query->setBindings($bindings, 'where');
            }
        });
    }

    /**
     * Get whether the academic year is current dynamically.
     */
    public function getIsCurrentAttribute($value)
    {
        if (request()->is('api/mobile/*') || request()->is('mobile/*')) {
            $school = $this->school;
            if ($school && $school->mobile_academic_year_id) {
                return $this->id === (int) $school->mobile_academic_year_id;
            }
            return (bool) $value;
        }

        // Only use dynamic web selected academic year context for GET (read) requests
        if (request()->isMethod('GET')) {
            // Bypass context override for academic-years CRUD management endpoints
            if (request()->is('api/academic-years') || request()->is('api/academic-years/*') || request()->is('academic-years') || request()->is('academic-years/*')) {
                return (bool) $value;
            }

            $user = auth()->user();
            if ($user) {
                $activeYearId = \App\Support\AcademicYearContext::getActiveAcademicYearId($user->school_id);
                if ($activeYearId) {
                    return $this->id === $activeYearId;
                }
            }
        }

        return (bool) $value;
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassModel::class, 'academic_year_id');
    }
}
