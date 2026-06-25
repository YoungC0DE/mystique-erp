<?php

namespace App\Services\Module;

use App\Enums\ActivityAction;
use App\Events\RecordMoved;
use App\Models\Module;
use App\Models\ModuleKanbanStatus;
use App\Models\User;
use App\Services\ActivityLog\ActivityLogger;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class StageCallbackService
{
    public function __construct(
        private readonly ExternalBoardReader $boardReader,
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * Dispara callback de etapa para registro integrado (sem persistir no CRM).
     *
     * @return array<string, mixed>
     */
    public function move(Module $module, string $externalId, User $actor, string $toStatusSlug): array
    {
        $module->loadMissing('kanbanStatuses');

        $this->assertCallbackConfigured($module);

        $record = $this->boardReader->findRecord($module, $externalId);

        if ($record === null) {
            throw new NotFoundHttpException(__('modules.external_record_not_found'));
        }

        $fromSlug = $record['status'];

        if ($fromSlug === $toStatusSlug) {
            return $record;
        }

        $fromStatus = $this->resolveStatus($module, $fromSlug);
        $toStatus = $this->resolveStatus($module, $toStatusSlug);

        $payload = [
            'record_id' => $this->normalizeRecordId($externalId),
            'status' => $toStatus->external_value,
            'previous_status' => $fromStatus->external_value,
            'module_slug' => $module->slug,
        ];

        $logContext = [
            'module_id' => $module->uuid,
            'record_id' => $externalId,
            'from' => $fromSlug,
            'to' => $toStatusSlug,
            'callback_url' => $module->callback_url,
        ];

        $this->logger->log(
            ActivityAction::RECORD_STAGE_CALLBACK_SENT,
            "Callback de etapa enviado para o registro {$externalId}.",
            properties: $logContext,
            subject: $module,
            user: $actor,
        );

        try {
            $response = $this->sendRequest($module, $payload);
        } catch (ConnectionException $e) {
            $this->logFailure($module, $actor, $logContext, $e->getMessage());

            throw new HttpException(502, __('modules.stage_callback_failed'), $e);
        } catch (Throwable $e) {
            $this->logFailure($module, $actor, $logContext, $e->getMessage());

            throw $e;
        }

        if ($response->failed()) {
            $this->logFailure($module, $actor, array_merge($logContext, [
                'http_status' => $response->status(),
            ]), $response->body());

            $status = $response->clientError() ? 422 : 502;
            $message = $response->clientError()
                ? __('modules.stage_callback_rejected')
                : __('modules.stage_callback_failed');

            throw new HttpException($status, $message);
        }

        $this->logger->log(
            ActivityAction::RECORD_STAGE_CALLBACK_SUCCESS,
            "Callback de etapa concluído para o registro {$externalId}.",
            properties: array_merge($logContext, ['http_status' => $response->status()]),
            subject: $module,
            user: $actor,
        );

        broadcast(new RecordMoved(
            $module,
            $externalId,
            $fromSlug,
            $toStatusSlug,
            isExternal: true,
        ))->toOthers();

        return [
            'id' => (string) $externalId,
            'status' => $toStatusSlug,
            'values' => $record['values'],
        ];
    }

    private function assertCallbackConfigured(Module $module): void
    {
        if ($module->callback_url === null || trim($module->callback_url) === '') {
            throw ValidationException::withMessages([
                'callback_url' => [__('modules.callback_url_required')],
            ]);
        }

        if (! filter_var($module->callback_url, FILTER_VALIDATE_URL)) {
            throw ValidationException::withMessages([
                'callback_url' => [__('modules.callback_url_invalid')],
            ]);
        }
    }

    private function resolveStatus(Module $module, string $slug): ModuleKanbanStatus
    {
        $status = $module->kanbanStatuses->firstWhere('slug', $slug);

        if ($status === null) {
            throw ValidationException::withMessages([
                'status' => [__('validation.in', ['attribute' => 'status'])],
            ]);
        }

        return $status;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendRequest(Module $module, array $payload): Response
    {
        $timeout = config('mystique.stage_callback.timeout', 15);
        $connectTimeout = config('mystique.stage_callback.connect_timeout', 5);
        $method = strtoupper($module->callback_method ?? 'POST');

        return Http::withOptions([
            'allow_redirects' => false,
            'connect_timeout' => $connectTimeout,
        ])
            ->timeout($timeout)
            ->acceptJson()
            ->send($method, $module->callback_url, ['json' => $payload]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logFailure(Module $module, User $actor, array $context, string $error): void
    {
        $this->logger->log(
            ActivityAction::RECORD_STAGE_CALLBACK_FAILED,
            'Falha no callback de etapa.',
            properties: array_merge($context, ['error' => mb_substr($error, 0, 500)]),
            subject: $module,
            user: $actor,
        );
    }

    private function normalizeRecordId(string $externalId): int|string
    {
        return ctype_digit($externalId) ? (int) $externalId : $externalId;
    }
}
