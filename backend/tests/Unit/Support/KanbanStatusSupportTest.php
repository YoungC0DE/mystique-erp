<?php

namespace Tests\Unit\Support;

use App\Support\DefaultKanbanStatuses;
use App\Support\KanbanStatusAccents;
use PHPUnit\Framework\TestCase;

class KanbanStatusSupportTest extends TestCase
{
    public function test_default_statuses_match_expected_slugs(): void
    {
        $slugs = collect(DefaultKanbanStatuses::definitions())->pluck('slug')->all();

        $this->assertSame(
            ['inputar', 'em_andamento', 'aprovados', 'reprovados'],
            $slugs,
        );
    }

    public function test_accent_colors_for_default_statuses(): void
    {
        $this->assertSame('#94a3b8', KanbanStatusAccents::for('inputar'));
        $this->assertSame('#f59e0b', KanbanStatusAccents::for('em_andamento'));
        $this->assertSame('#22c55e', KanbanStatusAccents::for('aprovados'));
        $this->assertSame('#ef4444', KanbanStatusAccents::for('reprovados'));
    }
}
