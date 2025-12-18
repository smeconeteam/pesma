<?php

namespace App\Filament\Resources\AdminAssignmentResource\Pages;

use App\Filament\Resources\AdminAssignmentResource;
use App\Models\User;
use App\Services\AdminPrivilegeService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAdminAssignment extends CreateRecord
{
    protected static string $resource = AdminAssignmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user = User::findOrFail($data['user_id']);
        $type = $data['type'];

        return app(AdminPrivilegeService::class)->assignAdmin($user, $type);
    }
}
