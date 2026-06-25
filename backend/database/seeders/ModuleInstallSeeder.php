<?php

namespace Database\Seeders;

use App\Services\Module\ModuleProvisioner;
use Illuminate\Database\Seeder;

class ModuleInstallSeeder extends Seeder
{
    public function run(): void
    {
        app(ModuleProvisioner::class)->provisionDefaultModules();
    }
}
