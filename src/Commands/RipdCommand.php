<?php

namespace JeffersonGoncalves\LgpdRipd\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\LgpdRipd\Models\ImpactAssessment;
use JeffersonGoncalves\LgpdRipd\RipdManager;

class RipdCommand extends Command
{
    protected $signature = 'lgpd:ripd {--json : Output every assessment as JSON}';

    protected $description = 'List data protection impact assessments (RIPD) and the processing activities that may still need one';

    public function handle(RipdManager $ripd): int
    {
        $assessments = ImpactAssessment::query()->with('processingActivity')->orderBy('title')->get();

        if ($this->option('json')) {
            $this->line((string) json_encode($assessments->map(fn (ImpactAssessment $a): array => $ripd->export($a))->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        if ($assessments->isNotEmpty()) {
            $this->table(
                ['#', 'Title', 'Activity', 'Status', 'Risks', 'Highest (residual)', 'Next review'],
                $assessments->map(fn (ImpactAssessment $a): array => [
                    $a->getKey(),
                    $a->title,
                    $a->processingActivity->name ?? '-',
                    $a->status->value.($a->isReviewDue() ? ' (review due)' : ''),
                    count($a->scoredRisks()),
                    ($a->highestRisk(false)->value ?? '-').' ('.($a->highestRisk()->value ?? '-').')',
                    $a->next_review_at?->toDateString() ?? '-',
                ])->all(),
            );
        }

        $candidates = $ripd->candidates();

        foreach ($candidates as $candidate) {
            $this->warn("No RIPD for [{$candidate['activity']->name}]: ".implode(', ', $candidate['reasons']));
        }

        if ($assessments->isEmpty() && $candidates === []) {
            $this->info('No assessments, and no processing activity that obviously needs one.');
        }

        return self::SUCCESS;
    }
}
