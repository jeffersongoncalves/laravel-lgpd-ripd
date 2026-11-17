<?php

namespace JeffersonGoncalves\LgpdRipd\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\Lgpd\Attributes\PersonalData;
use JeffersonGoncalves\Lgpd\Concerns\InteractsWithPersonalData;
use JeffersonGoncalves\Lgpd\Contracts\HasPersonalData;
use JeffersonGoncalves\Lgpd\Enums\DataCategory;

#[PersonalData('name')]
#[PersonalData('diagnosis', category: DataCategory::Sensitive)]
class Patient extends Model implements HasPersonalData
{
    use InteractsWithPersonalData;

    protected $table = 'patients';
}
