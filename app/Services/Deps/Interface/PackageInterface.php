<?php

namespace App\Services\Deps\Interface;

interface PackageInterface
{
    public function installPackage(bool $isNewInstallation);

}
