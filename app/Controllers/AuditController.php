<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use PDO;
use JsonException;

final class AuditController
{
    public function index(): void
    {
        Auth::requireRole('owner');
        $entries = Database::connection()->query(
            'SELECT a.id, a.action, a.table_name, a.record_id, a.details, a.ip, a.created_at, u.name AS user_name
             FROM audit_log a LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.id DESC LIMIT 500'
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($entries as &$entry) {
            $entry['details_data'] = [];
            $entry['details_invalid'] = false;
            if ($entry['details'] === null || $entry['details'] === '') {
                continue;
            }
            try {
                $details = json_decode($entry['details'], true, 512, JSON_THROW_ON_ERROR);
                if (is_array($details)) {
                    $entry['details_data'] = $details;
                } else {
                    $entry['details_invalid'] = true;
                }
            } catch (JsonException) {
                $entry['details_invalid'] = true;
            }
        }
        unset($entry);
        View::render('audit/index', ['entries' => $entries, 'pageTitle' => 'سجل التدقيق']);
    }
}
