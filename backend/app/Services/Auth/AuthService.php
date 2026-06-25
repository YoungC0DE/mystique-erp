<?php

namespace App\Services\Auth;

use App\Enums\ActivityAction;
use App\Models\User;
use App\Services\ActivityLog\ActivityLogger;
use App\Support\Cache\UserStructuralCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthService
{
    public function __construct(
        private readonly ActivityLogger $logger,
        private readonly UserStructuralCache $cache,
    ) {}

    /**
     * Autentica o usuário e emite Access Token + Refresh Token.
     *
     * @return array{user: User, tokens: array<string, mixed>}
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $tokens = $this->requestToken([
            'grant_type' => 'password',
            'username' => $email,
            'password' => $password,
            'scope' => '',
        ]);

        $this->logger->log(ActivityAction::LOGIN, 'Usuário autenticado.', user: $user);

        return ['user' => $user, 'tokens' => $tokens];
    }

    /**
     * Registra um novo usuário e emite tokens (auto-login).
     * O primeiro usuário da instalação é promovido a Admin.
     *
     * @return array{user: User, tokens: array<string, mixed>}
     */
    public function register(string $name, string $email, string $password): array
    {
        if (! config('mystique.registration_enabled')) {
            throw new HttpException(403, __('auth.registration_disabled'));
        }

        $isFirstUser = User::count() === 0;

        $user = DB::transaction(function () use ($name, $email, $password, $isFirstUser) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'is_admin' => $isFirstUser,
            ]);

            $this->logger->log(
                ActivityAction::USER_REGISTERED,
                $isFirstUser
                    ? "Primeiro usuário registrado como Admin: {$user->email}."
                    : "Usuário registrado: {$user->email}.",
                subject: $user,
                user: $user,
            );

            return $user;
        });

        $user = $user->load(['roles.permissions', 'permissions']);
        $this->cache->store($user);

        $tokens = $this->requestToken([
            'grant_type' => 'password',
            'username' => $email,
            'password' => $password,
            'scope' => '',
        ]);

        return ['user' => $user, 'tokens' => $tokens];
    }

    /**
     * Renova os tokens a partir de um Refresh Token.
     *
     * @return array<string, mixed>
     */
    public function refresh(string $refreshToken): array
    {
        $tokens = $this->requestToken([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'scope' => '',
        ]);

        $this->logger->log(ActivityAction::TOKEN_REFRESH, 'Tokens renovados.');

        return $tokens;
    }

    /**
     * Revoga o token atual (e seus refresh tokens) do usuário.
     */
    public function logout(User $user): void
    {
        $token = $user->token();

        if ($token) {
            DB::table('oauth_refresh_tokens')
                ->where('access_token_id', $token->getKey())
                ->update(['revoked' => true]);

            $token->revoke();
        }

        $this->logger->log(ActivityAction::LOGOUT, 'Usuário desconectado.', user: $user);
    }

    /**
     * Emite tokens chamando internamente o endpoint oauth/token do Passport.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function requestToken(array $params): array
    {
        $clientId = config('auth_tokens.password_client_id');
        $clientSecret = config('auth_tokens.password_client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            throw ValidationException::withMessages([
                'email' => ['O password grant client do Passport não está configurado.'],
            ]);
        }

        $request = Request::create('/oauth/token', 'POST', array_merge($params, [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]));

        $response = app()->handle($request);

        /** @var array<string, mixed>|null $data */
        $data = json_decode($response->getContent(), true);

        if ($response->getStatusCode() !== 200 || ! is_array($data)) {
            $message = __('auth.failed');

            if (config('app.debug') && is_array($data)) {
                $message = $data['error_description']
                    ?? $data['message']
                    ?? $data['error']
                    ?? $message;
            }

            throw ValidationException::withMessages([
                'email' => [$message],
            ]);
        }

        return $data;
    }
}
