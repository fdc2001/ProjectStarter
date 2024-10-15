<?php

namespace App\Services\System;

class SystemReqs
{
    public function __construct()
    {
    }

    public function checkComposer(): bool
    {
        $return =shell_exec('composer --version 2>&1');

        if (str_contains($return, 'Composer version')) {
            return true;
        }

        return false;
    }

    public function checkDocker(): bool
    {
        $return =shell_exec('docker --version 2>&1');

        if (str_contains($return, 'Docker version')) {
            return true;
        }

        return false;
    }
    public function checkGit(): bool
    {
        $return =shell_exec('git --version 2>&1');

        if (str_contains($return, 'git version')) {
            return true;
        }

        return false;
    }
}
