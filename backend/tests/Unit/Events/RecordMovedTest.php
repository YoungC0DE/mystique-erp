<?php

namespace Tests\Unit\Events;

use App\Events\RecordMoved;
use App\Models\Module;
use Illuminate\Broadcasting\PrivateChannel;
use PHPUnit\Framework\TestCase;

class RecordMovedTest extends TestCase
{
    private function event(bool $external = false): RecordMoved
    {
        $module = new Module;
        $module->uuid = 'module-uuid';

        return new RecordMoved($module, 'record-uuid', 'inputar', 'em_andamento', $external);
    }

    public function test_it_broadcasts_on_the_private_module_channel(): void
    {
        $channels = $this->event()->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-module.module-uuid', $channels[0]->name);
    }

    public function test_it_uses_the_record_moved_broadcast_name(): void
    {
        $this->assertSame('record.moved', $this->event()->broadcastAs());
    }

    public function test_broadcast_payload_contains_movement_details(): void
    {
        $payload = $this->event()->broadcastWith();

        $this->assertSame([
            'module' => 'module-uuid',
            'record' => 'record-uuid',
            'external_id' => null,
            'from' => 'inputar',
            'to' => 'em_andamento',
        ], $payload);
    }

    public function test_broadcast_payload_includes_external_id_for_integrated_records(): void
    {
        $payload = $this->event(external: true)->broadcastWith();

        $this->assertSame('record-uuid', $payload['external_id']);
    }
}
