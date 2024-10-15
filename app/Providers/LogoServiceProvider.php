<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use LaravelZero\Framework\Commands\Command;

class LogoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->booted(function () {
            $this->showLogo();
        });
    }

    protected function showLogo(): void
    {
        $logo = "
        .---------------------------------------------------------------------------------------------------------------.
        |                                                                                                               |
        |                                                                                                               |
        |  ██████╗ ██████╗  ██████╗      ██╗███████╗ ██████╗████████╗    ██╗    ██╗██╗███████╗ █████╗ ██████╗ ██████╗   |
        |  ██╔══██╗██╔══██╗██╔═══██╗     ██║██╔════╝██╔════╝╚══██╔══╝    ██║    ██║██║╚══███╔╝██╔══██╗██╔══██╗██╔══██╗  |
        |  ██████╔╝██████╔╝██║   ██║     ██║█████╗  ██║        ██║       ██║ █╗ ██║██║  ███╔╝ ███████║██████╔╝██║  ██║  |
        |  ██╔═══╝ ██╔══██╗██║   ██║██   ██║██╔══╝  ██║        ██║       ██║███╗██║██║ ███╔╝  ██╔══██║██╔══██╗██║  ██║  |
        |  ██║     ██║  ██║╚██████╔╝╚█████╔╝███████╗╚██████╗   ██║       ╚███╔███╔╝██║███████╗██║  ██║██║  ██║██████╔╝  |
        |  ╚═╝     ╚═╝  ╚═╝ ╚═════╝  ╚════╝ ╚══════╝ ╚═════╝   ╚═╝        ╚══╝╚══╝ ╚═╝╚══════╝╚═╝  ╚═╝╚═╝  ╚═╝╚═════╝   |
        |                                                                                                               |
        |                                                                                                               |
        '---------------------------------------------------------------------------------------------------------------'
";
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        $output->writeln("<fg=blue>$logo</>");

        \Laravel\Prompts\info('Version: <fg=blue;options=bold,underscore>'.config('app.version').'</>');
    }
}
