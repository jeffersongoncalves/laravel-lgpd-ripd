<?php

namespace JeffersonGoncalves\LgpdRipd\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\Lgpd\Attributes\PersonalData;
use JeffersonGoncalves\Lgpd\Concerns\InteractsWithPersonalData;
use JeffersonGoncalves\Lgpd\Contracts\HasPersonalData;

#[PersonalData('email', retention: '5 years')]
#[PersonalData('tax_id')]
class Customer extends Model implements HasPersonalData
{
    use InteractsWithPersonalData;

    protected $table = 'customers';
}
