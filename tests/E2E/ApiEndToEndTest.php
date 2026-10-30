<?php

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Process\Process;

/**
 * Servidor HTTP real (php -S) con base de datos SQLite dedicada, para probar la
 * API por red en lugar de por el kernel de Laravel.
 */
final class E2EServer
{
    public const HOST = '127.0.0.1';

    public static string $basePath = '';

    public static string $database = '';

    public static int $port = 0;

    public static ?Process $process = null;

    public static ?Client $client = null;

    public static function boot(): void
    {
        self::$basePath = dirname(__DIR__, 2);
        self::$database = self::$basePath.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'e2e.sqlite';

        if (is_file(self::$database)) {
            unlink(self::$database);
        }

        touch(self::$database);
        self::$port = self::freePort();

        $env = self::environment();

        $migrate = new Process([PHP_BINARY, 'artisan', 'migrate:fresh', '--seed', '--force'], self::$basePath, $env);
        $migrate->setTimeout(180);
        $migrate->mustRun();

        self::$process = new Process(
            [PHP_BINARY, '-S', self::HOST.':'.self::$port, '-t', 'public'],
            self::$basePath,
            $env,
        );
        self::$process->setTimeout(null);
        self::$process->start();

        self::$client = new Client([
            'base_uri' => 'http://'.self::HOST.':'.self::$port.'/api/v1/',
            'http_errors' => false,
            'timeout' => 15,
        ]);

        self::waitUntilReady();
    }

    public static function shutdown(): void
    {
        self::$process?->stop(1);
        self::$process = null;
        self::$client = null;

        if (is_file(self::$database)) {
            unlink(self::$database);
        }
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public static function call(string $method, string $uri, array $options = []): ResponseInterface
    {
        return self::$client->request($method, $uri, $options);
    }

    /**
     * @return array<string, mixed>
     */
    public static function body(ResponseInterface $response): array
    {
        $decoded = json_decode((string) $response->getBody(), true);

        return is_array($decoded) ? $decoded : [];
    }

    public static function currencyId(string $name): int
    {
        return (int) self::pdo()->query("SELECT id FROM currencies WHERE name = '{$name}'")->fetchColumn();
    }

    public static function tableCount(string $table): int
    {
        return (int) self::pdo()->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    }

    private static function pdo(): PDO
    {
        return new PDO('sqlite:'.self::$database);
    }

    /**
     * @return array<string, string>
     */
    private static function environment(): array
    {
        return [
            'APP_ENV' => 'local',
            'APP_DEBUG' => 'false',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => self::$database,
            'DB_URL' => '',
            'CACHE_STORE' => 'database',
            'SESSION_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'MAIL_MAILER' => 'array',
        ];
    }

    private static function freePort(): int
    {
        $socket = stream_socket_server('tcp://'.self::HOST.':0', $errno, $errstr);

        if ($socket === false) {
            throw new RuntimeException("No fue posible reservar un puerto: {$errstr}");
        }

        $address = (string) stream_socket_get_name($socket, false);
        fclose($socket);

        return (int) substr(strrchr($address, ':'), 1);
    }

    private static function waitUntilReady(): void
    {
        $deadline = microtime(true) + 30;

        while (microtime(true) < $deadline) {
            $connection = @fsockopen(self::HOST, self::$port, $errno, $errstr, 0.2);

            if (is_resource($connection)) {
                fclose($connection);

                return;
            }

            usleep(100_000);
        }

        throw new RuntimeException('El servidor HTTP no arranco: '.self::$process?->getErrorOutput());
    }
}

beforeAll(function (): void {
    E2EServer::boot();
});

afterAll(function (): void {
    E2EServer::shutdown();
});

test('recorre el ciclo de vida completo del producto por HTTP real', function () {
    $usd = E2EServer::currencyId('USD');
    $eur = E2EServer::currencyId('EUR');
    $name = 'E2E Laptop '.bin2hex(random_bytes(4));

    $list = E2EServer::call('GET', 'products?per_page=5');
    expect($list->getStatusCode())->toBe(200)
        ->and(E2EServer::body($list))->toHaveKeys(['data', 'links', 'meta']);

    $create = E2EServer::call('POST', 'products', ['json' => [
        'name' => $name,
        'description' => 'Producto creado por la prueba end to end',
        'price' => 1500.00,
        'currency_id' => $usd,
        'tax_cost' => 180.00,
        'manufacturing_cost' => 900.00,
    ]]);

    expect($create->getStatusCode())->toBe(201)
        ->and($create->getHeaderLine('Location'))->toContain('/api/v1/products/');

    $created = E2EServer::body($create);
    $id = $created['data']['id'];

    $show = E2EServer::call('GET', "products/{$id}");
    expect($show->getStatusCode())->toBe(200)
        ->and(E2EServer::body($show)['data']['name'])->toBe($name);

    $update = E2EServer::call('PUT', "products/{$id}", ['json' => [
        'name' => $name.' v2',
        'description' => 'Producto actualizado por la prueba end to end',
        'price' => 1600.00,
        'currency_id' => $usd,
        'tax_cost' => 200.00,
        'manufacturing_cost' => 950.00,
    ]]);

    expect($update->getStatusCode())->toBe(200)
        ->and(E2EServer::body($update)['data']['name'])->toBe($name.' v2');

    $storePrice = E2EServer::call('POST', "products/{$id}/prices", ['json' => ['currency_id' => $eur]]);

    expect($storePrice->getStatusCode())->toBe(201);

    $prices = E2EServer::call('GET', "products/{$id}/prices");
    expect($prices->getStatusCode())->toBe(200)
        ->and(E2EServer::body($prices)['data'])->not->toBeEmpty();

    $delete = E2EServer::call('DELETE', "products/{$id}");
    expect($delete->getStatusCode())->toBe(200)
        ->and(E2EServer::body($delete)['message'])->toBe('Producto eliminado.');

    $missing = E2EServer::call('GET', "products/{$id}");
    expect($missing->getStatusCode())->toBe(404)
        ->and(E2EServer::body($missing)['error'])->toBe('NOT_FOUND');
});

test('devuelve el contrato de errores sobre HTTP real', function () {
    $notFound = E2EServer::call('GET', 'products/999999999999');
    expect($notFound->getStatusCode())->toBe(404)
        ->and(E2EServer::body($notFound)['data'])->toBeNull();

    $methodNotAllowed = E2EServer::call('PATCH', 'products');
    expect($methodNotAllowed->getStatusCode())->toBe(405)
        ->and($methodNotAllowed->getHeaderLine('Allow'))->not->toBe('');

    $unsupported = E2EServer::call('POST', 'products', [
        'body' => 'name=Laptop',
        'headers' => ['Content-Type' => 'text/plain'],
    ]);
    expect($unsupported->getStatusCode())->toBe(415)
        ->and(E2EServer::body($unsupported)['error'])->toBe('UNSUPPORTED_MEDIA_TYPE');

    $invalid = E2EServer::call('POST', 'products', [
        'body' => '{"name":',
        'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
    ]);
    expect($invalid->getStatusCode())->toBe(422)
        ->and(E2EServer::body($invalid)['error'])->toBe('VALIDATION_ERROR');
});

test('neutraliza la inyeccion SQL sobre HTTP real', function (string $payload) {
    $before = E2EServer::tableCount('products');

    $response = E2EServer::call('GET', 'products?'.http_build_query(['search' => $payload]));

    expect($response->getStatusCode())->toBe(200)
        ->and(E2EServer::body($response)['meta']['total'])->toBe(0)
        ->and(E2EServer::tableCount('products'))->toBe($before);
})->with([
    "' OR 1=1 --",
    "'; DROP TABLE products; --",
    '1; DELETE FROM products',
]);

test('limita la tasa de peticiones por IP sobre HTTP real', function () {
    for ($attempt = 0; $attempt < 90; $attempt++) {
        $response = E2EServer::call('GET', 'products?per_page=1');

        if ($response->getStatusCode() === 429) {
            expect($response->getHeaderLine('Retry-After'))->not->toBe('')
                ->and(E2EServer::body($response)['error'])->toBe('TOO_MANY_REQUESTS');

            return;
        }
    }

    expect(true)->toBeFalse('La API no devolvio 429 tras 90 peticiones.');
});
