<?php
declare(strict_types=1);

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'autoload.php';

use App\Core\Csrf;
use App\Core\Formatter;
use App\Core\Router;
use App\Core\Url;
use App\Core\View;
use App\Services\OperationPolicy;
use App\Services\UnitService;

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

$tests['unit conversion calculates exact monetary totals'] = static function () use ($assertSame): void {
    $assertSame(
        ['quantity' => '1000', 'base_quantity' => '1', 'amount' => 20000],
        UnitService::calculate('1000', '0.001000', 20000),
        'Gram quantities should convert to kilograms before pricing.'
    );
    $assertSame(
        ['quantity' => '0.5', 'base_quantity' => '0.0005', 'amount' => 5],
        UnitService::calculate('0.5', '0.001', 10000),
        'Fractional quantities should preserve monetary rounding.'
    );
    $assertSame(
        ['quantity' => '0.1', 'base_quantity' => '0.0000001', 'amount' => 1],
        UnitService::calculate('0.1', '0.000001', 10000000),
        'Converted quantities must preserve all nine supported decimal places.'
    );
    $assertSame(null, UnitService::calculate('1', '0', 10000), 'A zero conversion factor must be rejected.');
    $assertSame(null, UnitService::calculate('1001', '1', 10000), 'Quantities above the limit must be rejected.');
};

$tests['transaction quantity descriptions preserve converted unit details'] = static function () use ($assertSame): void {
    $assertSame(
        '500 جم × 200 جنيه/كجم (≈ 0.5 كجم)',
        UnitService::movementDescription([
            'quantity' => '500.000',
            'unit_symbol' => 'جم',
            'base_unit_symbol' => 'كجم',
            'base_quantity' => '0.500000',
            'unit_price' => 20000,
        ]),
        'Transaction details should show the selected and converted quantities.'
    );
    $assertSame('1000', UnitService::formatQuantity('1000'), 'Whole-number quantities must keep trailing integer zeros.');
};

$tests['operation policy matches movement types to party types'] = static function () use ($assertSame): void {
    $assertSame(true, OperationPolicy::supports('customer', 'sell'), 'Customers should allow sales.');
    $assertSame(true, OperationPolicy::supports('customer', 'pay'), 'Customers should allow payments.');
    $assertSame(false, OperationPolicy::supports('customer', 'supply'), 'Customers should not allow supply movements.');
    $assertSame(false, OperationPolicy::supports('customer', 'pay_supplier'), 'Customers should not allow supplier payments.');
    $assertSame(true, OperationPolicy::supports('supplier', 'supply'), 'Suppliers should allow supply movements.');
    $assertSame(true, OperationPolicy::supports('supplier', 'pay_supplier'), 'Suppliers should allow supplier payments.');
    $assertSame(false, OperationPolicy::supports('supplier', 'sell'), 'Suppliers should not allow sales.');
    $assertSame(true, OperationPolicy::supports('both', 'sell'), 'Mixed parties should allow customer movements.');
    $assertSame(true, OperationPolicy::supports('both', 'supply'), 'Mixed parties should allow supplier movements.');
    $assertSame(false, OperationPolicy::supports('unknown', 'sell'), 'Unknown party types should be rejected.');
};

$tests['HTML output escapes unsafe content'] = static function () use ($assertSame): void {
    $assertSame('&lt;script&gt;&quot;x&quot;&lt;/script&gt;', View::e('<script>"x"</script>'), 'HTML escaping failed.');
};

$tests['URLs respect configured application base path'] = static function () use ($assertSame): void {
    $GLOBALS['config'] = ['app' => ['base_path' => '/ledger/public']];
    $assertSame('/ledger/public/parties', Url::to('/parties'), 'Application URL prefix is incorrect.');
    $assertSame('/ledger/public/', Url::to('/'), 'Application root URL is incorrect.');
    $assertSame(
        '/parties',
        Url::requestPath('/ledger/public/parties?filter=customers'),
        'Application route prefix should be removed before dispatch.'
    );
};

$tests['URLs detect the deployed application path'] = static function () use ($assertSame): void {
    $hadConfig = array_key_exists('config', $GLOBALS);
    $previousConfig = $GLOBALS['config'] ?? null;
    $hadScriptName = array_key_exists('SCRIPT_NAME', $_SERVER);
    $previousScriptName = $_SERVER['SCRIPT_NAME'] ?? null;

    try {
        $GLOBALS['config'] = ['app' => ['base_path' => '']];
        $_SERVER['SCRIPT_NAME'] = '/demo/public/index.php';
        $assertSame('/demo/public/statement/8', Url::to('/statement/8'), 'Detected application path is incorrect.');
        $assertSame(
            '/statement/8',
            Url::requestPath('/demo/public/statement/8'),
            'Detected application path should be removed before route dispatch.'
        );
    } finally {
        if ($hadConfig) {
            $GLOBALS['config'] = $previousConfig;
        } else {
            unset($GLOBALS['config']);
        }

        if ($hadScriptName) {
            $_SERVER['SCRIPT_NAME'] = $previousScriptName;
        } else {
            unset($_SERVER['SCRIPT_NAME']);
        }
    }
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
