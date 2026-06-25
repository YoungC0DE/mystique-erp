<?php

namespace Tests\Unit\Enums;

use App\Enums\ModuleStatus;
use PHPUnit\Framework\TestCase;

class ModuleStatusTest extends TestCase
{
    public function test_has_active_and_inactive_states(): void
    {
        $this->assertSame('active', ModuleStatus::ACTIVE->value);
        $this->assertSame('inactive', ModuleStatus::INACTIVE->value);
        $this->assertCount(2, ModuleStatus::cases());
    }
}
