<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Http;
use App\Core\Input;
use App\Core\View;
use App\Models\Unit;
use App\Services\AuditService;
use App\Services\UnitService;

final class UnitSettingsController
{
    public function index(): void
    {
        Auth::requireRole('owner');
        $categories = Unit::categories();
        $unitsByCategory = [];
        foreach (Unit::all() as $unit) {
            $unitsByCategory[$unit['category_code']][] = $unit;
        }
        foreach ($categories as &$category) {
            $category['units'] = $unitsByCategory[$category['code']] ?? [];
        }
        unset($category);

        View::render('settings/units', [
            'categories' => $categories,
            'errors' => Http::takeFlash('errors', []),
            'success' => Http::takeFlash('success'),
            'old' => Http::takeFlash('old', []),
            'pageTitle' => 'إعدادات الوحدات',
        ]);
    }

    public function store(): void
    {
        Auth::requireRole('owner');
        $name = trim(Input::string($_POST, 'name'));
        $symbol = trim(Input::string($_POST, 'symbol'));
        $categoryCode = Input::string($_POST, 'category_code');
        $factor = UnitService::normalizeFactor(Input::string($_POST, 'factor_to_base'));
        $categoryCodes = array_column(Unit::categories(), 'code');
        $errors = [];

        if (!in_array($categoryCode, $categoryCodes, true)) {
            $errors[] = 'اختر فئة وحدات صحيحة.';
        }
        if ($name === '' || mb_strlen($name) > 60) {
            $errors[] = 'اسم الوحدة مطلوب وبحد أقصى 60 حرفًا.';
        }
        if ($symbol === '' || mb_strlen($symbol) > 24) {
            $errors[] = 'رمز الوحدة مطلوب وبحد أقصى 24 حرفًا.';
        }
        if ($factor === null) {
            $errors[] = 'أدخل معامل تحويل موجبًا لا يتجاوز 1,000,000 وبحد أقصى 6 خانات عشرية.';
        }
        if ($errors !== []) {
            Http::flash('errors', $errors);
            Http::flash('old', [
                'name' => $name,
                'symbol' => $symbol,
                'category_code' => $categoryCode,
                'factor_to_base' => Input::string($_POST, 'factor_to_base'),
            ]);
            Http::redirect('/settings/units');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $categoryLock = $pdo->prepare('SELECT code FROM unit_categories WHERE code = :code FOR UPDATE');
            $categoryLock->execute(['code' => $categoryCode]);
            $currentDefaultId = Unit::defaultId($categoryCode);
            $makeDefault = Input::string($_POST, 'is_default') === '1' || $currentDefaultId === null;
            if ($makeDefault) {
                $clearDefaults = $pdo->prepare('UPDATE units SET is_default = 0 WHERE category_code = :category_code');
                $clearDefaults->execute(['category_code' => $categoryCode]);
            }

            $unitId = Unit::create([
                'category_code' => $categoryCode,
                'name' => $name,
                'symbol' => $symbol,
                'factor_to_base' => $factor,
                'is_default' => (int) $makeDefault,
            ]);
            AuditService::record('unit_created', 'units', $unitId, [
                'category' => $categoryCode,
                'symbol' => $symbol,
            ]);
            $pdo->commit();
        } catch (\PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($exception->getCode() === '23000') {
                Http::flash('errors', ['هذا الرمز مستخدم بالفعل داخل فئة الوحدات.']);
                Http::flash('old', [
                    'name' => $name,
                    'symbol' => $symbol,
                    'category_code' => $categoryCode,
                    'factor_to_base' => Input::string($_POST, 'factor_to_base'),
                ]);
                Http::redirect('/settings/units');
            }
            throw $exception;
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }

        Http::flash('success', 'تمت إضافة الوحدة.');
        Http::redirect('/settings/units');
    }

    public function update(string $id): void
    {
        Auth::requireRole('owner');
        $unitId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $unit = $unitId === false ? null : Unit::find((int) $unitId);
        if ($unit === null) {
            Http::flash('errors', ['لم يتم العثور على الوحدة المطلوبة.']);
            Http::redirect('/settings/units');
        }

        $name = trim(Input::string($_POST, 'name'));
        $symbol = trim(Input::string($_POST, 'symbol'));
        $factor = (int) $unit['is_base'] === 1
            ? '1.000000'
            : UnitService::normalizeFactor(Input::string($_POST, 'factor_to_base'));
        $active = Input::string($_POST, 'is_active') === '1';
        $requestedDefault = Input::string($_POST, 'is_default') === '1';
        $errors = [];
        if ($name === '' || mb_strlen($name) > 60) {
            $errors[] = 'اسم الوحدة مطلوب وبحد أقصى 60 حرفًا.';
        }
        if ($symbol === '' || mb_strlen($symbol) > 24) {
            $errors[] = 'رمز الوحدة مطلوب وبحد أقصى 24 حرفًا.';
        }
        if ($factor === null) {
            $errors[] = 'أدخل معامل تحويل موجبًا لا يتجاوز 1,000,000 وبحد أقصى 6 خانات عشرية.';
        }
        if ((int) $unit['is_base'] === 1 && !$active) {
            $errors[] = 'لا يمكن إيقاف وحدة الأساس للفئة.';
        }
        if ($requestedDefault && !$active) {
            $errors[] = 'فعّل الوحدة قبل تعيينها كوحدة افتراضية.';
        }
        if ($errors !== []) {
            Http::flash('errors', $errors);
            Http::redirect('/settings/units');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $categoryLock = $pdo->prepare('SELECT code FROM unit_categories WHERE code = :code FOR UPDATE');
            $categoryLock->execute(['code' => $unit['category_code']]);
            $unitLock = $pdo->prepare('SELECT id FROM units WHERE id = :id FOR UPDATE');
            $unitLock->execute(['id' => (int) $unitId]);

            $currentDefaultId = Unit::defaultId((string) $unit['category_code']);
            if ($requestedDefault) {
                $defaultId = (int) $unitId;
            } elseif ($currentDefaultId === (int) $unitId) {
                $defaultId = Unit::baseId((string) $unit['category_code']) ?? (int) $unitId;
            } else {
                $defaultId = $currentDefaultId ?? Unit::baseId((string) $unit['category_code']) ?? (int) $unitId;
            }

            $clearDefaults = $pdo->prepare('UPDATE units SET is_default = 0 WHERE category_code = :category_code');
            $clearDefaults->execute(['category_code' => $unit['category_code']]);
            $setDefault = $pdo->prepare(
                'UPDATE units SET is_default = 1 WHERE id = :id AND is_active = 1'
            );
            $setDefault->execute(['id' => $defaultId]);

            Unit::update((int) $unitId, [
                'name' => $name,
                'symbol' => $symbol,
                'factor_to_base' => $factor,
                'is_active' => (int) $active,
                'is_default' => (int) ($defaultId === (int) $unitId),
            ]);
            AuditService::record('unit_updated', 'units', (int) $unitId, [
                'category' => $unit['category_code'],
                'symbol' => $symbol,
                'is_active' => $active,
                'is_default' => $defaultId === (int) $unitId,
            ]);
            $pdo->commit();
        } catch (\PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($exception->getCode() === '23000') {
                Http::flash('errors', ['هذا الرمز مستخدم بالفعل داخل فئة الوحدات.']);
                Http::redirect('/settings/units');
            }
            throw $exception;
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }

        Http::flash('success', 'تم حفظ إعدادات الوحدة.');
        Http::redirect('/settings/units');
    }
}
