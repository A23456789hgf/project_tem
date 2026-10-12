<?php

namespace App\Models;

use App\Http\Controllers\Project\Services\ProjectService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ProjectFinancing extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'funding_source_id',
        'authority_id', // تم التغيير من entity_id إلى authority_id
        'financing_type_id',
        'financing_form_id',
        'sub_financing_form_id',
        'financing_amount',
        'financing_percentage',
    ];

    // Relationships
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function fundingSource()
    {
        return $this->belongsTo(FundingSource::class, 'funding_source_id')->withoutGlobalScope('active_only');
    }

    public function authority() // تم التغيير من entity إلى authority
    {
        return $this->belongsTo(Authority::class, 'authority_id')->withoutGlobalScope('active_only');
    }

    public function financingType()
    {
        return $this->belongsTo(FinancingType::class, 'financing_type_id')->withoutGlobalScope('active_only');
    }

    public function financingForm()
    {
        return $this->belongsTo(FinancingForm::class, 'financing_form_id')->withoutGlobalScope('active_only');
    }

    public function subFinancingForm()
    {
        return $this->belongsTo(SubFinancingForm::class, 'sub_financing_form_id')->withoutGlobalScope('active_only');
    }

    // Auto-calculate percentage and record transactions
    protected static function booted()
    {
        static::saving(function (ProjectFinancing $financing) {
            // 🔹 حساب نسبة التمويل فقط وتقييدها بين 0 و 100% لتفادي تجاوز سعة الحقل
            $totalCost = optional(optional($financing->project)->cost)->total_cost ?? 0;
            if ($totalCost > 0) {
                $percentage = ($financing->financing_amount / $totalCost) * 100;
                $financing->financing_percentage = max(0.00, min(100.00, round((float) $percentage, 2)));
            } else {
                $financing->financing_percentage = 0.00;
            }
        });

        // Record transaction on create
        static::created(function (ProjectFinancing $financing) {
            static::recordTransaction($financing, 'create', null, $financing->toArray());
        });

        // Record transaction on update
        static::updated(function (ProjectFinancing $financing) {
            $changes = $financing->getChanges();
            $original = $financing->getOriginal();

            // Only record if financing_amount changed
            if (isset($changes['financing_amount']) || isset($changes['financing_percentage'])) {
                $oldAmount = $original['financing_amount'] ?? 0;
                $newAmount = $changes['financing_amount'] ?? $financing->financing_amount;
                $amountChange = $newAmount - $oldAmount;

                static::recordTransaction($financing, 'update', $original, $financing->toArray(), $amountChange);
            }
        });

        // Record transaction on delete
        static::deleted(function (ProjectFinancing $financing) {
            static::recordTransaction($financing, 'delete', $financing->toArray(), null, -$financing->financing_amount);
        });

        // مزامنة مشروع التمكين تلقائياً عند حفظ أو تعديل أو حذف أي تمويل
        static::saved(function (ProjectFinancing $financing) {
            if ($financing->project_id) {
                $project = $financing->project ?: Project::find($financing->project_id);
                if ($project) {
                    try {
                        app(ProjectService::class)->syncProjectToEmpowermentDepartment($project);
                    } catch (\Exception $e) {
                        Log::error('Failed auto-syncing empowerment on ProjectFinancing saved', [
                            'project_id' => $project->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        });

        static::deleted(function (ProjectFinancing $financing) {
            if ($financing->project_id) {
                $project = $financing->project ?: Project::find($financing->project_id);
                if ($project) {
                    try {
                        app(ProjectService::class)->syncProjectToEmpowermentDepartment($project);
                    } catch (\Exception $e) {
                        Log::error('Failed auto-syncing empowerment on ProjectFinancing deleted', [
                            'project_id' => $project->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        });
    }

    /**
     * Record a transaction for this financing
     */
    protected static function recordTransaction(ProjectFinancing $financing, string $type, ?array $oldValues, ?array $newValues, ?float $amountChange = null)
    {
        // Get current user and IP
        $userId = auth()->id();
        $ipAddress = request()->ip();

        // Skip if no authenticated user (during seeding, etc.)
        if (! $userId) {
            return;
        }

        $description = static::generateTransactionDescription($financing, $type, $amountChange);

        Transaction::create([
            'project_id' => $financing->project_id,
            'user_id' => $userId,
            'transaction_type' => $type,
            'model_type' => 'ProjectFinancing',
            'model_id' => $financing->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'amount_change' => $amountChange,
            'description' => $description,
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * Generate a human-readable description for the transaction
     */
    protected static function generateTransactionDescription(ProjectFinancing $financing, string $type, ?float $amountChange = null): string
    {
        $fundingSource = $financing->fundingSource->name ?? 'غير محدد';
        $authority = $financing->authority->name ?? 'غير محدد';
        $amount = number_format($financing->financing_amount, 2);

        switch ($type) {
            case 'create':
                return "تم إضافة تمويل جديد: {$fundingSource} - {$authority} بمبلغ {$amount} ر.س";
            case 'update':
                if ($amountChange > 0) {
                    return "تم زيادة مبلغ التمويل: {$fundingSource} - {$authority} بمقدار ".number_format($amountChange, 2).' ر.س';
                } elseif ($amountChange < 0) {
                    return "تم تقليل مبلغ التمويل: {$fundingSource} - {$authority} بمقدار ".number_format(abs($amountChange), 2).' ر.س';
                } else {
                    return "تم تحديث بيانات التمويل: {$fundingSource} - {$authority}";
                }
            case 'delete':
                return "تم حذف التمويل: {$fundingSource} - {$authority} بمبلغ {$amount} ر.س";
            default:
                return "تم تعديل التمويل: {$fundingSource} - {$authority}";
        }
    }

    /**
     * Accessor للحصول على اسم الجهة الممولة
     */
    public function getAuthorityNameAttribute()
    {
        return $this->authority ? $this->authority->name : 'غير محدد';
    }

    /**
     * Accessor للحصول على اسم مصدر التمويل
     */
    public function getFundingSourceNameAttribute()
    {
        return $this->fundingSource ? $this->fundingSource->name : 'غير محدد';
    }

    /**
     * Accessor للحصول على اسم نوع التمويل
     */
    public function getFinancingTypeNameAttribute()
    {
        return $this->financingType ? $this->financingType->name : 'غير محدد';
    }

    /**
     * Scope للبحث عن التمويلات حسب مصدر التمويل
     */
    public function scopeByFundingSource($query, $fundingSourceId)
    {
        return $query->where('funding_source_id', $fundingSourceId);
    }

    /**
     * Scope للبحث عن التمويلات حسب الجهة الممولة
     */
    public function scopeByAuthority($query, $authorityId)
    {
        return $query->where('authority_id', $authorityId);
    }

    /**
     * Scope للتمويلات ذات المبلغ أكبر من قيمة معينة
     */
    public function scopeAmountGreaterThan($query, $amount)
    {
        return $query->where('financing_amount', '>', $amount);
    }

    /**
     * الحصول على إجمالي التمويل لمشروع معين
     */
    public static function getTotalFinancingForProject($projectId)
    {
        return static::where('project_id', $projectId)->sum('financing_amount');
    }

    /**
     * التحقق من وجود تمويلات للمشروع
     */
    public static function hasFinancings($projectId)
    {
        return static::where('project_id', $projectId)->exists();
    }
}
