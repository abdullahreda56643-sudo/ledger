<?php
declare(strict_types=1);

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'autoload.php';
$GLOBALS['config'] = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

$pdo = Database::connection();
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS unit_categories (
       code VARCHAR(24) PRIMARY KEY,
       name VARCHAR(50) NOT NULL,
       base_unit_name VARCHAR(50) NOT NULL,
       base_unit_symbol VARCHAR(24) NOT NULL,
       sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS units (
       id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
       category_code VARCHAR(24) NOT NULL,
       name VARCHAR(60) NOT NULL,
       symbol VARCHAR(24) NOT NULL,
       factor_to_base DECIMAL(14,6) NOT NULL,
       is_base TINYINT(1) NOT NULL DEFAULT 0,
       is_default TINYINT(1) NOT NULL DEFAULT 0,
       is_active TINYINT(1) NOT NULL DEFAULT 1,
       sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
       created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
       CONSTRAINT fk_units_category FOREIGN KEY (category_code) REFERENCES unit_categories(code),
       CONSTRAINT chk_unit_factor CHECK (factor_to_base > 0),
       UNIQUE INDEX uq_unit_category_symbol (category_code, symbol),
       INDEX idx_units_category_active (category_code, is_active, sort_order)
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$hasColumn = static function (string $column) use ($pdo): bool {
    $query = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'transactions\' AND COLUMN_NAME = :column_name'
    );
    $query->execute(['column_name' => $column]);
    return (int) $query->fetchColumn() > 0;
};

foreach ([
    'quantity' => 'DECIMAL(10,3) NULL',
    'unit_id' => 'SMALLINT UNSIGNED NULL',
    'unit_symbol' => 'VARCHAR(24) NULL',
    'base_quantity' => 'DECIMAL(20,9) NULL',
    'base_unit_symbol' => 'VARCHAR(24) NULL',
] as $column => $definition) {
    if (!$hasColumn($column)) {
        $pdo->exec('ALTER TABLE transactions ADD COLUMN ' . $column . ' ' . $definition);
    }
}

$baseQuantityScale = $pdo->prepare(
    'SELECT NUMERIC_SCALE FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'transactions\' AND COLUMN_NAME = \'base_quantity\''
);
$baseQuantityScale->execute();
if ((int) $baseQuantityScale->fetchColumn() < 9) {
    $pdo->exec('ALTER TABLE transactions MODIFY COLUMN base_quantity DECIMAL(20,9) NULL');
}

$categories = [
    ['mass', 'الوزن', 'كيلوجرام', 'كجم', 1],
    ['count', 'العدد', 'قطعة', 'قطعة', 2],
    ['volume', 'الحجم', 'لتر', 'لتر', 3],
    ['length', 'الطول', 'متر', 'م', 4],
    ['area', 'المساحة', 'متر مربع', 'م²', 5],
];
$insertCategory = $pdo->prepare(
    'INSERT INTO unit_categories (code, name, base_unit_name, base_unit_symbol, sort_order)
     VALUES (:code, :name, :base_unit_name, :base_unit_symbol, :sort_order)
     ON DUPLICATE KEY UPDATE name = VALUES(name), base_unit_name = VALUES(base_unit_name),
                             base_unit_symbol = VALUES(base_unit_symbol), sort_order = VALUES(sort_order)'
);
foreach ($categories as $category) {
    $insertCategory->execute([
        'code' => $category[0],
        'name' => $category[1],
        'base_unit_name' => $category[2],
        'base_unit_symbol' => $category[3],
        'sort_order' => $category[4],
    ]);
}

$units = [
    ['mass', 'ملليجرام', 'ملجم', '0.000001', 0, 1],
    ['mass', 'جرام', 'جم', '0.001000', 0, 2],
    ['mass', 'كيلوجرام', 'كجم', '1.000000', 1, 3],
    ['mass', 'طن', 'طن', '1000.000000', 0, 4],
    ['count', 'قطعة', 'قطعة', '1.000000', 1, 1],
    ['count', 'زوج', 'زوج', '2.000000', 0, 2],
    ['count', 'دستة', 'دستة', '12.000000', 0, 3],
    ['volume', 'ملليلتر', 'مل', '0.001000', 0, 1],
    ['volume', 'لتر', 'لتر', '1.000000', 1, 2],
    ['volume', 'متر مكعب', 'م³', '1000.000000', 0, 3],
    ['length', 'ملليمتر', 'مم', '0.001000', 0, 1],
    ['length', 'سنتيمتر', 'سم', '0.010000', 0, 2],
    ['length', 'متر', 'م', '1.000000', 1, 3],
    ['length', 'كيلومتر', 'كم', '1000.000000', 0, 4],
    ['area', 'ملليمتر مربع', 'مم²', '0.000001', 0, 1],
    ['area', 'سنتيمتر مربع', 'سم²', '0.000100', 0, 2],
    ['area', 'متر مربع', 'م²', '1.000000', 1, 3],
    ['area', 'آر', 'آر', '100.000000', 0, 4],
    ['area', 'هكتار', 'هكتار', '10000.000000', 0, 5],
    ['area', 'كيلومتر مربع', 'كم²', '1000000.000000', 0, 6],
];
$insertUnit = $pdo->prepare(
    'INSERT IGNORE INTO units (category_code, name, symbol, factor_to_base, is_base, is_default, is_active, sort_order)
     VALUES (:category_code, :name, :symbol, :factor_to_base, :is_base, :is_default, 1, :sort_order)'
);
foreach ($units as $unit) {
    $insertUnit->execute([
        'category_code' => $unit[0],
        'name' => $unit[1],
        'symbol' => $unit[2],
        'factor_to_base' => $unit[3],
        'is_base' => $unit[4],
        'is_default' => $unit[4],
        'sort_order' => $unit[5],
    ]);
}

$hasUnitForeignKey = (int) $pdo->query(
    'SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'transactions\'
       AND COLUMN_NAME = \'unit_id\' AND REFERENCED_TABLE_NAME = \'units\''
)->fetchColumn() > 0;
if (!$hasUnitForeignKey) {
    $pdo->exec(
        'ALTER TABLE transactions ADD CONSTRAINT fk_transactions_unit
         FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE RESTRICT'
    );
}

$pdo->exec(
    'UPDATE transactions t
     JOIN units u ON u.category_code = \'mass\' AND u.is_base = 1
     JOIN unit_categories c ON c.code = u.category_code
     SET t.quantity = COALESCE(t.quantity, t.weight),
         t.unit_id = COALESCE(t.unit_id, u.id),
         t.unit_symbol = COALESCE(t.unit_symbol, u.symbol),
         t.base_quantity = COALESCE(t.base_quantity, t.weight),
         t.base_unit_symbol = COALESCE(t.base_unit_symbol, c.base_unit_symbol)
     WHERE t.weight IS NOT NULL AND t.unit_id IS NULL'
);
$defaultQuery = $pdo->prepare(
    'SELECT COUNT(*) FROM units WHERE category_code = :category_code AND is_active = 1 AND is_default = 1'
);
$defaultBase = $pdo->prepare(
    'UPDATE units SET is_default = 1 WHERE category_code = :category_code AND is_base = 1 AND is_active = 1'
);
foreach ($categories as $category) {
    $defaultQuery->execute(['category_code' => $category[0]]);
    if ((int) $defaultQuery->fetchColumn() === 0) {
        $defaultBase->execute(['category_code' => $category[0]]);
    }
}

fwrite(STDOUT, "تم تجهيز أنواع الوحدات وتحديث الحركات القديمة إلى الكيلوجرام دون حذف بيانات." . PHP_EOL);
