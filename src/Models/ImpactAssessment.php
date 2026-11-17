<?php

namespace JeffersonGoncalves\LgpdRipd\Models;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\LgpdRecords\Models\ProcessingActivity;
use JeffersonGoncalves\LgpdRipd\Enums\AssessmentStatus;
use JeffersonGoncalves\LgpdRipd\Enums\RiskLevel;
use JeffersonGoncalves\LgpdRipd\Enums\Threat;
use LogicException;

/**
 * Relatório de Impacto à Proteção de Dados Pessoais (LGPD art. 5, XVII and 38).
 *
 * @property string $title
 * @property int|null $processing_activity_id
 * @property string|null $description the processing and the data it collects
 * @property string|null $necessity necessity and proportionality
 * @property array<array-key, array{title: string, threat?: string|null, likelihood: int|string, impact: int|string, measures?: string|null, residual_likelihood?: int|string|null, residual_impact?: int|string|null}>|null $risks
 * @property string|null $dpo_opinion
 * @property AssessmentStatus $status
 * @property Carbon|null $approved_at
 * @property string|null $approved_by
 * @property Carbon|null $next_review_at
 * @property-read ProcessingActivity|null $processingActivity
 */
class ImpactAssessment extends Model
{
    /** Changing any of these after approval sends the assessment back to draft. */
    public const CONTENT = ['title', 'processing_activity_id', 'description', 'necessity', 'risks', 'dpo_opinion'];

    protected $guarded = [];

    protected $attributes = [
        'status' => 'draft',
    ];

    public function getTable(): string
    {
        return config('lgpd-ripd.table', 'lgpd_impact_assessments');
    }

    protected function casts(): array
    {
        return [
            'risks' => 'array',
            'status' => AssessmentStatus::class,
            'approved_at' => 'datetime',
            'next_review_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (ImpactAssessment $assessment): void {
            if ($assessment->getOriginal('status') === AssessmentStatus::Approved && $assessment->isDirty(self::CONTENT)) {
                $assessment->forceFill(['status' => AssessmentStatus::Draft, 'approved_at' => null, 'approved_by' => null, 'next_review_at' => null]);
            }
        });
    }

    /**
     * @return BelongsTo<ProcessingActivity, $this>
     */
    public function processingActivity(): BelongsTo
    {
        return $this->belongsTo(ProcessingActivity::class);
    }

    /**
     * Risks with their inherent level and the residual level after the measures
     * (the inherent one when no residual score was given).
     *
     * @return list<array{title: string, threat: Threat|null, likelihood: int, impact: int, level: RiskLevel, measures: string|null, residual_likelihood: int, residual_impact: int, residual_level: RiskLevel}>
     */
    public function scoredRisks(): array
    {
        return array_map(function (array $risk): array {
            $likelihood = (int) $risk['likelihood'];
            $impact = (int) $risk['impact'];
            $residualLikelihood = (int) ($risk['residual_likelihood'] ?? $likelihood);
            $residualImpact = (int) ($risk['residual_impact'] ?? $impact);

            return [
                'title' => $risk['title'],
                'threat' => isset($risk['threat']) ? Threat::tryFrom($risk['threat']) : null,
                'likelihood' => $likelihood,
                'impact' => $impact,
                'level' => RiskLevel::score($likelihood, $impact),
                'measures' => $risk['measures'] ?? null,
                'residual_likelihood' => $residualLikelihood,
                'residual_impact' => $residualImpact,
                'residual_level' => RiskLevel::score($residualLikelihood, $residualImpact),
            ];
        }, array_values($this->risks ?? []));
    }

    public function highestRisk(bool $residual = true): ?RiskLevel
    {
        $highest = null;

        foreach ($this->scoredRisks() as $risk) {
            $level = $residual ? $risk['residual_level'] : $risk['level'];

            if ($highest === null || $level->weight() > $highest->weight()) {
                $highest = $level;
            }
        }

        return $highest;
    }

    public function approve(string $by): static
    {
        if ($this->scoredRisks() === []) {
            throw new LogicException("Assess at least one risk before approving [{$this->title}].");
        }

        $this->forceFill([
            'status' => AssessmentStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $by,
            'next_review_at' => now()->add(CarbonInterval::make((string) config('lgpd-ripd.review_every', '1 year'))),
        ])->save();

        return $this;
    }

    public function isReviewDue(): bool
    {
        return $this->status === AssessmentStatus::Approved && $this->next_review_at?->isPast() === true;
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeDueForReview(Builder $query): void
    {
        $query->where('status', AssessmentStatus::Approved->value)->where('next_review_at', '<', now());
    }
}
