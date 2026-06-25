<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Converte exceções em respostas JSON consistentes para a API,
 * sem expor stack traces (apenas detalhes leves quando em debug).
 */
class ApiExceptionRenderer
{
    /**
     * Renderiza a exceção como JSON quando a requisição é da API.
     * Retorna null para deixar o Laravel tratar requisições não-API (ex.: web).
     */
    public static function render(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*') && ! $request->expectsJson()) {
            return null;
        }

        if ($e instanceof ValidationException) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], $e->status);
        }

        if ($e instanceof AuthenticationException) {
            return self::payload('Não autenticado.', Response::HTTP_UNAUTHORIZED, $e);
        }

        if ($e instanceof AuthorizationException) {
            return self::payload(
                $e->getMessage() !== '' ? $e->getMessage() : 'Esta ação não é autorizada.',
                Response::HTTP_FORBIDDEN,
                $e,
            );
        }

        // O Laravel encapsula o ModelNotFoundException do route-model-binding
        // em um NotFoundHttpException cuja mensagem vaza o nome do model.
        if ($e instanceof ModelNotFoundException || $e->getPrevious() instanceof ModelNotFoundException) {
            return self::payload('Recurso não encontrado.', Response::HTTP_NOT_FOUND, $e);
        }

        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
            $message = $e->getMessage() !== '' ? $e->getMessage() : self::defaultMessageFor($status);

            return self::payload($message, $status, $e);
        }

        if ($e instanceof RequestExceptionInterface) {
            return self::payload('Requisição inválida.', Response::HTTP_BAD_REQUEST, $e);
        }

        return self::payload('Ocorreu um erro inesperado.', Response::HTTP_INTERNAL_SERVER_ERROR, $e);
    }

    /**
     * Monta o corpo JSON padrão. Em debug, adiciona detalhes leves
     * (classe e mensagem original) — nunca o stack trace.
     */
    private static function payload(string $message, int $status, Throwable $e): JsonResponse
    {
        $body = ['message' => $message];

        if (config('app.debug')) {
            $body['debug'] = [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ];
        }

        return response()->json($body, $status);
    }

    private static function defaultMessageFor(int $status): string
    {
        return match ($status) {
            Response::HTTP_BAD_REQUEST => 'Requisição inválida.',
            Response::HTTP_UNAUTHORIZED => 'Não autenticado.',
            Response::HTTP_FORBIDDEN => 'Esta ação não é autorizada.',
            Response::HTTP_NOT_FOUND => 'Recurso não encontrado.',
            Response::HTTP_METHOD_NOT_ALLOWED => 'Método não permitido.',
            Response::HTTP_TOO_MANY_REQUESTS => 'Muitas requisições. Tente novamente em instantes.',
            default => 'Ocorreu um erro inesperado.',
        };
    }
}
