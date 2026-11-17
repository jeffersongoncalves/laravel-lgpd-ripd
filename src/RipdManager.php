<?php

namespace JeffersonGoncalves\LgpdRipd;

use JeffersonGoncalves\Lgpd\Enums\DataCategory;
use JeffersonGoncalves\Lgpd\Enums\LegalBasis;
use JeffersonGoncalves\Lgpd\Lgpd;
use JeffersonGoncalves\LgpdRecords\Models\ProcessingActivity;
use JeffersonGoncalves\LgpdRipd\Models\ImpactAssessment;

class RipdManager
{
    public function __construct(protected Lgpd $lgpd) {}

    /**
     * Processing activities with no assessment that the ANPD is likely to ask one for:
     * sensitive data (art. 38) or legitimate interest (art. 10, §3).
     *
     * @return list<array{activity: ProcessingActivity, reasons: list<string>}>
     */
    public function candidates(): array
    {
        $sensitiveModels = collect($this->lgpd->inventory())
            ->where('category', DataCategory::Sensitive->value)
            ->pluck('model')
            ->unique()
            ->all();

        $assessed = ImpactAssessment::query()->whereNotNull('processing_activity_id')->pluck('processing_activity_id')->all();

        $candidates = [];

        foreach (ProcessingActivity::query()->whereNotIn('id', $assessed)->orderBy('name')->get() as $activity) {
            $reasons = [];

            if (array_intersect($activity->models ?? [], $sensitiveModels) !== []) {
                $reasons[] = 'sensitive_data';
            }

            if ($activity->legal_basis === LegalBasis::LegitimateInterest) {
                $reasons[] = 'legitimate_interest';
            }

            if ($reasons !== []) {
                $candidates[] = ['activity' => $activity, 'reasons' => $reasons];
            }
        }

        return $candidates;
    }

    /**
     * @return array<string, mixed>
     */
    public function export(ImpactAssessment $assessment): array
    {
        return [
            'title' => $assessment->title,
            'processing_activity' => $assessment->processingActivity?->name,
            'description' => $assessment->description,
            'necessity' => $assessment->necessity,
            'risks' => array_map(fn (array $risk): array => [
                ...$risk,
                'threat' => $risk['threat']?->value,
                'level' => $risk['level']->value,
                'residual_level' => $risk['residual_level']->value,
            ], $assessment->scoredRisks()),
            'highest_residual_risk' => $assessment->highestRisk()?->value,
            'dpo_opinion' => $assessment->dpo_opinion,
            'status' => $assessment->status->value,
            'approved_at' => $assessment->approved_at?->toIso8601String(),
            'approved_by' => $assessment->approved_by,
            'next_review_at' => $assessment->next_review_at?->toIso8601String(),
        ];
    }
}
