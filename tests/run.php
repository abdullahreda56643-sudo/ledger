<?php
declare(strict_types=1);

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'autoload.php';

use App\Core\Csrf;
use App\Core\Formatter;
use App\Core\Router;
use App\Core\Url;
use App\Core\View;

$tests = [];
$assertSame = static function (mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . ' (expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . ')'
        );
    }
};
$assertTrue = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$tests['CSRF tokens are 64 hexadecimal characters'] = static function () use ($assertTrue): void {
    $_SESSION = [];
    $assertTrue((bool) preg_match('/^[a-f0-9]{64}$/', Csrf::token()), 'Generated token has an invalid format.');
};

$tests['CSRF token validates only for the current session'] = static function () use ($assertSame): void {
    $_SESSION = [];
    $token = Csrf::token();
    $assertSame(true, Csrf::validate($token), 'Current CSRF token should validate.');
    $assertSame(false, Csrf::validate(str_repeat('0', 64)), 'An unrelated token should be rejected.');
    $assertSame(false, Csrf::validate(null), 'A missing token should be rejected.');
};

$tests['money formatting preserves piasters and grouping'] = static function () use ($assertSame): void {
    $assertSame('123', Formatter::money(12300), 'Whole-pound formatting failed.');
    $assertSame('123.45', Formatter::money('12345'), 'Piaster formatting failed.');
    $assertSame('-1,234.50', Formatter::money(-123450), 'Negative amount formatting failed.');
};

$tests['balance labels cover debit, credit and zero'] = static function () use ($assertSame): void {
    $assertSame('عليه 25', Formatter::balanceLabel(2500), 'Debit label is incorrect.');
    $assertSame('له 25', Formatter::balanceLabel(-2500), 'Credit label is incorrect.');
    $assertSame('متزن', Formatter::balanceLabel(0), 'Zero balance label is incorrect.');
};

$tests['balance classes match balance direction'] = static function () use ($assertSame): void {
    $assertSame('text-ledger-red', Formatter::balanceClass(1), 'Debit class is incorrect.');
    $assertSame('text-ledger-green', Formatter::balanceClass(-1), 'Credit class is incorrect.');
    $assertSame('text-ledger-muted', Formatter::balanceClass(0), 'Balanced class is incorrect.');
};

$tests['transaction types have Arabic labels'] = static function () use ($assertSame): void {
    $assertSame('سحب', Formatter::type('sell'), 'Sell label is incorrect.');
    $assertSame('دفعة من الطرف', Formatter::type('pay'), 'Payment label is incorrect.');
    $assertSame('unknown', Formatter::type('unknown'), 'Unknown types should remain visible.');
};

$tests['HTML output escapes unsafe content'] = static function () use ($assertSame): void {
    $assertSame('&lt;script&gt;&quot;x&quot;&lt;/script&gt;', View::e('<script>"x"</script>'), 'HTML escaping failed.');
};

$tests['URLs respect configured application base path'] = static function () use ($assertSame): void {
    $GLOBALS['config'] = ['app' => ['base_path' => '/ledger/public']];
    $assertSame('/ledger/public/parties', Url::to('/parties'), 'Application URL prefix is incorrect.');
    $assertSame('/ledger/public/', Url::to('/'), 'Application root URL is incorrect.');
};

$tests['router dispatches route parameters and ignores query strings'] = static function () use ($assertSame): void {
    $router = new Router();
    $received = null;
    $router->get('/statement/{id}', static function (string $id) use (&$received): void {
        $received = $id;
    });
    $router->dispatch('GET', '/statement/42?from=2026-01-01');
    $assertSame('42', $received, 'Route parameter was not dispatched correctly.');
};

$tests['router returns 404 for unknown paths'] = static function () use ($assertSame): void {
    $router = new Router();
    http_response_code(200);
    ob_start();
    $router->dispatch('GET', '/missing');
    $body = (string) ob_get_clean();
    $assertSame(404, http_response_code(), 'Unknown route should return HTTP 404.');
    $assertSame('الصفحة غير موجودة.', $body, 'Unknown route response body is incorrect.');
};

$passed = 0;
$failed = 0;
foreach ($tests as $name => $test) {
    try {
        $test();
        $passed++;
        echo "[PASS] {$name}" . PHP_EOL;
    } catch (Throwable $exception) {
        $failed++;
        fwrite(STDERR, "[FAIL] {$name}: {$exception->getMessage()}" . PHP_EOL);
    }
}

echo PHP_EOL . "Tests: {$passed} passed, {$failed} failed." . PHP_EOL;
exit($failed === 0 ? 0 : 1);
