<?php

namespace App\Services\Installation\Packages\traits;

use App\Exceptions\Filament\AlreadyHasMultiTenancyException;
use App\Exceptions\Filament\FailedUploadPanelProviderException;
use App\Exceptions\Filament\PanelProviderNotFoundException;
use Illuminate\Support\Str;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;

trait MultiTenantFeature
{

    /**
     * @throws AlreadyHasMultiTenancyException
     * @throws PanelProviderNotFoundException
     * @throws FailedUploadPanelProviderException
     */
    public function configureMultiTenant(): bool
    {
        try {
            $this->checkPanelContainsMultiTenant($this->getPanelFilePath());
        } catch (AlreadyHasMultiTenancyException|PanelProviderNotFoundException) {
            return false;
        }

        $type = select('Please indicate which type of multi-tenant you prefer?', ['team', 'company', 'organization']);

        match ($type) {
            'team' => $this->configureTeam(),
            'company' => $this->configureCompany(),
            'organization' => $this->configureOrganization()
        };
        $this->migrate(true, true);

        return true;
    }

    /**
     * @throws AlreadyHasMultiTenancyException
     * @throws FailedUploadPanelProviderException
     * @throws PanelProviderNotFoundException
     */
    private function configureEntity(string $entity): void
    {
        $entityLowercase = strtolower($entity);

        info('Configuring ' . $entity . '...');

        $this->generateModel($entity);
        $this->generateMigrations([
            'create_' . $entityLowercase . '_table' => [],
            'change_pk_in_users' => [],
            'create_' . $entityLowercase . '_users_table' => [],
            'add_column_to_users' => [
                'RELATION_NAME' => $entityLowercase,
                'TABLE_TENANT_NAME' => Str::plural($entityLowercase)
            ]
        ]);
        $this->generateUserModelIfNeeded($entityLowercase);
        $this->generateAdditionalStubs($entityLowercase);

        $this->allowMultiTenant($entityLowercase);
        $this->installInvitation($entity);
    }

    private function configureTeam(): void
    {
        $this->configureEntity('Team');
    }

    private function configureCompany(): void
    {
        $this->configureEntity('Company');
    }

    private function configureOrganization(): void
    {
        $this->configureEntity('Organization');
    }

    private function generateUserModelIfNeeded($multiTenantType): void
    {
        $userModelDir = base_path($this->workingDirectory . '/app/Models/User.php');
        if ($userModelDir) {
            if ($this->isNewInstallation) {
                $this->generateUserModel($multiTenantType);
            } else {
                if (confirm('I can replace your User Model Class?')) {
                    $this->generateUserModel($multiTenantType);
                }
            }
        }
    }

    private function generateAdditionalStubs($multiTenantType): void
    {
        $this->generateStub(
            '/stub/database/factories/TenantFactory.stub',
            '/database/factories/',
            ucfirst($multiTenantType) . 'Factory',
            [
                'Class_NAME' => ucfirst($multiTenantType)
            ]
        );
        $this->generateStub(
            '/stub/database/seeders/UserSeeder.stub',
            '/database/seeders/',
            'UserSeeder',
            [
                'Class_NAME' => ucfirst($multiTenantType),
                'RELATION_NAME' => $multiTenantType
            ]
        );
        $this->generateStub(
            '/stub/database/seeders/DatabaseSeeder.stub',
            '/database/seeders/',
            'DatabaseSeeder'
        );
    }

    public function generateUserModel($multiTenantType): void
    {
        $this->generateStub(
            '/stub/app/Models/User.stub',
            '/app/Models',
            'User',
            [
                'RELATION_NAME' => $multiTenantType,
                'RELATION_CLASS' => ucfirst($multiTenantType)
            ]
        );

        $this->generateStub(
            '/stub/database/factories/UserFactory.stub',
            '/database/factories/',
            'UserFactory'
        );
    }

    /**
     * @throws PanelProviderNotFoundException
     * @throws AlreadyHasMultiTenancyException
     * @throws FailedUploadPanelProviderException
     */
    private function allowMultiTenant(string $type): void
    {
        $filePath = $this->getPanelFilePath();

        $this->checkPanelContainsMultiTenant($filePath);

        $content = file_get_contents($filePath);
        $newContent = $this->getContentWithTenantSupport(ucfirst($type));
        $newContent .= $this->addTenantProfile(ucfirst($type));

        $this->writeContentToFile($filePath, $newContent, $content);
    }

    private function addTenantProfile($tenancyClass): string
    {
        $this->generateStub(
            '/stub/app/Filament/Pages/TenantProfile.stub',
            '/app/Filament/Pages/',
            'Edit' . ucfirst($tenancyClass),
            [
                'Class_NAME' => ucfirst($tenancyClass)
            ]
        );

        return PHP_EOL . '->tenantProfile(\App\Filament\Pages\Edit' . ucfirst($tenancyClass) . '::class)';
    }

    private function getContentWithTenantSupport(string $type): string
    {
        return sprintf("->login()\n                 ->tenant(\App\Models\%s::class, 'slug')", $type);
    }

    /**
     * @throws FailedUploadPanelProviderException
     */
    private function writeContentToFile(string $filePath, string $newContent, string $content): void
    {
        $find = '->login()';
        if (!file_put_contents($filePath, str_replace($find, $newContent, $content))) {
            throw new FailedUploadPanelProviderException($this->panelId);
        }
    }
}
