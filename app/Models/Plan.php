<?php

namespace App\Models;

use App\Traits\HasCreatorTracking;
use App\Traits\HasDomainScope;
use ArPHP\I18N\Arabic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasCreatorTracking, HasDomainScope, HasFactory;

    protected $fillable = [
        'plan_number',
        'submitting_type',
        'submitting_entity_id',
        'submitting_authority_id',
        'geographic_scope_id',
        'administrative_scope_id',
        'created_by',
        'creator_username',
        'creator_entity_id',
        'start_date_g',
        'start_date_h',
        'end_date_g',
        'end_date_h',
        'duration',
    ];

    protected $casts = [
        'start_date_g' => 'date',
        'end_date_g' => 'date',
        'duration' => 'integer',
    ];

    // ---------------------------------------------------------------------
    // Auto-generate plan number
    // ---------------------------------------------------------------------
    protected static function booted()
    {

        static::creating(function ($model) {
            $model->plan_number = self::generatePlanNumber();
        });
    }

    private static function generatePlanNumber()
    {
        try {
            $arPHP = new Arabic;
            $hijriYear = $arPHP->date('Y', time(), 1);
            $prefix = 'MAFWRPLAN'.$hijriYear;

            $lastPlan = self::withoutGlobalScopes()
                ->where('plan_number', 'like', $prefix.'%')
                ->orderBy('plan_number', 'desc')
                ->first();

            $serial = $lastPlan ? intval(substr($lastPlan->plan_number, strlen($prefix))) + 1 : 1;

            return $prefix.str_pad($serial, 2, '0', STR_PAD_LEFT);
        } catch (\Exception $e) {
            return 'PLAN'.date('YmdHis');
        }
    }

    // ---------------------------------------------------------------------
    // Relations
    // ---------------------------------------------------------------------
    public function submittingEntity(): BelongsTo
    {
        return $this->belongsTo(InternalEntity::class, 'submitting_entity_id');
    }

    public function submittingAuthority(): BelongsTo
    {
        return $this->belongsTo(Authority::class, 'submitting_authority_id');
    }

    public function submittingOrganization(): InternalEntity|Authority|null
    {
        return $this->submitting_type === 'external'
            ? $this->submittingAuthority
            : $this->submittingEntity;
    }

    public function getSubmittingOrganizationNameAttribute(): ?string
    {
        $organization = $this->submittingOrganization();

        return $organization?->name ?? $organization?->agency_name;
    }

    public function projects(): HasMany
    {
        return $this->hasMany(PlanProject::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function priority()
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }
}
