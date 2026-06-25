<?php

$en = include __DIR__.'/../lang/en/validation.php';
$json = json_decode(
    file_get_contents('https://raw.githubusercontent.com/Laravel-Lang/lang/main/locales/pt_BR/php.json'),
    true,
);

function applyMap(array $arr, array $map, string $prefix = ''): array
{
    $out = [];
    foreach ($arr as $key => $value) {
        $fullKey = $prefix === '' ? $key : $prefix.'.'.$key;
        if (is_array($value)) {
            $out[$key] = applyMap($value, $map, $fullKey);
        } else {
            $out[$key] = $map[$fullKey] ?? $map[$key] ?? $value;
        }
    }

    return $out;
}

$pt = applyMap($en, $json);
$pt['attributes'] = [
    'email' => 'e-mail',
    'password' => 'senha',
    'name' => 'nome',
    'locale' => 'idioma',
    'current_password' => 'senha atual',
    'password_confirmation' => 'confirmação de senha',
    'is_admin' => 'Admin',
    'roles' => 'grupos',
    'permissions' => 'permissões',
    'status' => 'status',
    'icon' => 'ícone',
    'label' => 'nome de exibição',
    'key' => 'chave',
    'type' => 'tipo',
    'required' => 'obrigatório',
    'default_value' => 'valor padrão',
    'order' => 'ordem',
    'options' => 'opções',
    'values' => 'valores',
    'plan' => 'plano',
    'active' => 'ativo',
    'refresh_token' => 'token de atualização',
];

$langDir = __DIR__.'/../lang/pt_BR';
if (! is_dir($langDir)) {
    mkdir($langDir, 0777, true);
}

file_put_contents($langDir.'/validation.php', "<?php\n\nreturn ".var_export($pt, true).";\n");

file_put_contents($langDir.'/auth.php', <<<'PHP'
<?php

return [
    'failed' => 'As credenciais indicadas não coincidem com as registradas no sistema.',
    'password' => 'A senha informada está incorreta.',
    'throttle' => 'O número limite de tentativas de login foi atingido. Por favor, tente novamente dentro de :seconds segundos.',
];
PHP);

file_put_contents($langDir.'/passwords.php', <<<'PHP'
<?php

return [
    'reset' => 'A senha foi redefinida!',
    'sent' => 'Enviamos o link de redefinição de senha por e-mail.',
    'throttled' => 'Por favor, aguarde antes de tentar novamente.',
    'token' => 'Este token de redefinição de senha é inválido.',
    'user' => 'Não existe nenhum usuário com o e-mail indicado.',
];
PHP);

file_put_contents($langDir.'/pagination.php', <<<'PHP'
<?php

return [
    'previous' => '&laquo; Anterior',
    'next' => 'Próximo &raquo;',
];
PHP);

file_put_contents(__DIR__.'/../lang/pt_BR.json', json_encode([
    'The given data was invalid.' => 'Os dados fornecidos são inválidos.',
    '(and :count more error)' => '(e mais :count erro)',
    '(and :count more errors)' => '(e mais :count erros)',
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo "Arquivos pt_BR gerados.\n";
