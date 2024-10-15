<?php

namespace App\Services\Installation\Packages\traits;

use function Laravel\Prompts\confirm;

trait InvitationFeature
{
    public function installInvitation($model): void
    {
        if (!confirm('Do you want add invitation feature?')) {
            return;
        }
        $relationName = strtolower($model);
        $this->generateModelAndMigrations($model, $relationName);
        $this->generateServiceAndActions($relationName);
        $this->updateListUsersContent();
        $this->generateMailStubs();
        $this->generateLivewireAndInvitationStubs($relationName);
        $this->updateRoutesContent();
    }

    private function generateModelAndMigrations($model, $relationName): void
    {
        $this->generateModel('Invitation', [
            'RELATION_NAME' => $relationName,
            'RELATION_CLASS' => $model,
            'TABLE_TENANT_NAME' => $model
        ]);
        $this->generateMigrations([
            'create_invitations_table' => [
                'RELATION_CLASS' => $model,
            ]
        ]);
    }

    private function generateServiceAndActions($relationName): void
    {
        $this->generateStub(
            '/stub/app/Services/InvitationService.stub',
            '/app/Services',
            'InvitationService',
            [
                'RELATION_NAME' => $relationName
            ]
        );
        $this->generateStub(
            '/stub/app/Filament/Actions/InviteUserAction.stub',
            '/app/Filament/Actions',
            'InviteUserAction'
        );
    }

    private function updateListUsersContent(): void
    {
        $listUsersFilePath = $this->workingDirectory . '/app/Filament/Resources/UserResource/Pages/ListUsers.php';
        $listUsersContent = file_get_contents($listUsersFilePath);
        $find = 'Actions\CreateAction::make(),';
        $replace = '\App\Filament\Actions\InviteUserAction::make(),';
        $listUsersContent = str_replace($find, $replace, $listUsersContent);
        file_put_contents($listUsersFilePath, $listUsersContent);

        $this->generateStub(
            '/stub/app/Filament/Pages/Invites.stub',
            '/app/Filament/Pages',
            'Invites'
        );

        $this->generateStub(
            '/stub/resources/views/filament/pages/invites.blade.stub',
            '/resources/views/filament/pages/',
            'invites.blade'
        );
    }

    private function generateMailStubs(): void
    {
        $this->generateStub(
            '/stub/app/Mail/InvitationMail.stub',
            '/app/Mail',
            'InvitationMail'
        );
        $this->generateStub(
            '/stub/resources/views/emails/invitation.blade.stub',
            '/resources/views/emails',
            'invitation.blade'
        );
    }

    private function generateLivewireAndInvitationStubs($relationName): void
    {
        $this->generateStub(
            '/stub/app/Livewire/AcceptInvitation.stub',
            '/app/Livewire',
            'AcceptInvitation', [
                'RELATION_NAME' => $relationName
            ]
        );
        $this->generateStub(
            '/stub/resources/views/livewire/accept-invitation.blade.stub',
            '/resources/views/livewire',
            'accept-invitation.blade'
        );
    }

    private function updateRoutesContent(): void
    {
        $routesFilePath = $this->workingDirectory . '/routes/web.php';
        $routesContent = file_get_contents($routesFilePath);
        $routesContent .= "Route::get('/invitations/{invitation}', \App\Livewire\AcceptInvitation::class)
    ->name('invitations.accept');";
        file_put_contents($routesFilePath, $routesContent);
    }
}
