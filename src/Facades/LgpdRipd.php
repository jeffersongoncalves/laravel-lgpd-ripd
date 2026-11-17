<?php

namespace JeffersonGoncalves\LgpdRipd\Facades;

use Illuminate\Support\Facades\Facade;
use JeffersonGoncalves\LgpdRipd\RipdManager;

/**
 * @method static list<array{activity: \JeffersonGoncalves\LgpdRecords\Models\ProcessingActivity, reasons: list<string>}> candidates()
 * @method static array<string, mixed> export(\JeffersonGoncalves\LgpdRipd\Models\ImpactAssessment $assessment)
 *
 * @see RipdManager
 */
class LgpdRipd extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RipdManager::class;
    }
}
