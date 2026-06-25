<?php

namespace App\Events;

use App\Models\Module;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RecordMoved implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Module $module,
        public string $recordId,
        public string $from,
        public string $to,
        public bool $isExternal = false,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('module.'.$this->module->uuid),
        ];
    }

    public function broadcastAs(): string
    {
        return 'record.moved';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'module' => $this->module->uuid,
            'record' => $this->recordId,
            'external_id' => $this->isExternal ? $this->recordId : null,
            'from' => $this->from,
            'to' => $this->to,
        ];
    }
}
