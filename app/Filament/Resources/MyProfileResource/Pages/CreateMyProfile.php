<?php

namespace App\Filament\Resources\MyProfileResource\Pages;

use App\Filament\Resources\MyProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMyProfile extends CreateRecord
{
    protected static string $resource = MyProfileResource::class;
}
