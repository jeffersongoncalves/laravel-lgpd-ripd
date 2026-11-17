<?php

use Illuminate\Support\Facades\Artisan;
use JeffersonGoncalves\Lgpd\Enums\LegalBasis;
use JeffersonGoncalves\LgpdRecords\Models\ProcessingActivity;
use JeffersonGoncalves\LgpdRipd\Enums\AssessmentStatus;
use JeffersonGoncalves\LgpdRipd\Enums\RiskLevel;
use JeffersonGoncalves\LgpdRipd\Enums\Threat;
use JeffersonGoncalves\LgpdRipd\Facades\LgpdRipd;
use JeffersonGoncalves\LgpdRipd\Models\ImpactAssessment;
use JeffersonGoncalves\LgpdRipd\Tests\Fixtures\Customer;
use JeffersonGoncalves\LgpdRipd\Tests\Fixtures\Patient;

function activity(string $name, LegalBasis $basis, array $models): ProcessingActivity
{
    return ProcessingActivity::create(['name' => $name, 'purpose' => $name, 'legal_basis' => $basis, 'models' => $models]);
}

function assessment(?ProcessingActivity $activity = null): ImpactAssessment
{
    return ImpactAssessment::create([
        'title' => 'Telemedicine',
        'processing_activity_id' => $activity?->getKey(),
        'risks' => [
            ['title' => 'Diagnosis leak', 'threat' => 'data_disclosure', 'likelihood' => '2', 'impact' => '3', 'measures' => 'Encryption at rest', 'residual_likelihood' => '1', 'residual_impact' => '3'],
            ['title' => 'Re-identification in reports', 'threat' => 'identifying', 'likelihood' => 1, 'impact' => 2],
        ],
    ]);
}

it('scores risks by likelihood and impact, before and after measures', function () {
    $risks = assessment()->scoredRisks();

    expect($risks[0])
        ->threat->toBe(Threat::DataDisclosure)
        ->level->toBe(RiskLevel::High)
        ->residual_level->toBe(RiskLevel::Medium)
        ->and($risks[1]['residual_level'])->toBe(RiskLevel::Low)
        ->and(assessment()->highestRisk(residual: false))->toBe(RiskLevel::High)
        ->and(assessment()->highestRisk())->toBe(RiskLevel::Medium)
        ->and(RiskLevel::score(3, 3))->toBe(RiskLevel::High)
        ->and(fn () => RiskLevel::score(4, 1))->toThrow(InvalidArgumentException::class);
});

it('approves, schedules a review and returns to draft when edited', function () {
    $ripd = assessment()->approve('Ana Souza (DPO)');

    expect($ripd->status)->toBe(AssessmentStatus::Approved)
        ->and($ripd->next_review_at->toDateString())->toBe(now()->addYear()->toDateString());

    $this->travel(13)->months();

    expect($ripd->isReviewDue())->toBeTrue()
        ->and(ImpactAssessment::query()->scopes('dueForReview')->count())->toBe(1);

    $ripd->update(['necessity' => 'Only the data needed for the appointment']);

    expect($ripd->fresh())
        ->status->toBe(AssessmentStatus::Draft)
        ->approved_by->toBeNull()
        ->isReviewDue()->toBeFalse();
});

it('needs risks before approval', function () {
    ImpactAssessment::create(['title' => 'Empty'])->approve('DPO');
})->throws(LogicException::class, 'at least one risk');

it('lists activities that may need an assessment', function () {
    $health = activity('Telemedicine', LegalBasis::HealthProtection, [Patient::class]);
    activity('Billing', LegalBasis::Contract, [Customer::class]);
    activity('Fraud scoring', LegalBasis::LegitimateInterest, [Customer::class]);

    expect(collect(LgpdRipd::candidates())->map(fn (array $c) => [$c['activity']->name, $c['reasons']])->all())->toBe([
        ['Fraud scoring', ['legitimate_interest']],
        ['Telemedicine', ['sensitive_data']],
    ]);

    assessment($health);

    expect(LgpdRipd::candidates())->toHaveCount(1);
});

it('reports assessments and candidates', function () {
    assessment(activity('Telemedicine', LegalBasis::HealthProtection, [Patient::class]))->approve('DPO');
    activity('Fraud scoring', LegalBasis::LegitimateInterest, [Customer::class]);

    Artisan::call('lgpd:ripd');
    expect(Artisan::output())->toContain('Telemedicine')->toContain('high (medium)')->toContain('No RIPD for [Fraud scoring]: legitimate_interest');

    Artisan::call('lgpd:ripd', ['--json' => true]);
    expect(json_decode(Artisan::output(), true)[0])
        ->processing_activity->toBe('Telemedicine')
        ->highest_residual_risk->toBe('medium')
        ->risks->{0}->threat->toBe('data_disclosure');
});
