<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Input;
use App\Core\View;
use PDO;
use JsonException;

final class AuditController
{
    public function index(): void
    {
        Auth::requireRole('owner');
        $pdo = Database::connection();
        $totalEntries = (int) $pdo->query('SELECT COUNT(*) FROM audit_log')->fetchColumn();
        $perPage = 50;
        $page = filter_var(Input::string($_GET, 'page', '1'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $page = $page === false ? 1 : min($page, intdiv(PHP_INT_MAX, $perPage));
        $totalPages = max(1, (int) ceil($totalEntries / $perPage));
        $page = min($page, $totalPages);
        $statement = $pdo->prepare(
            'SELECT a.id, a.action, a.table_name, a.record_id, a.details, a.ip, a.created_at, u.name AS user_name
             FROM audit_log a LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.id DESC LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $statement->execute();
        $entries = $statement->fetchAll(PDO::FETCH_ASSOC);
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
        View::render('audit/index', [
            'entries' => $entries,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'totalEntries' => $totalEntries,
            'pageTitle' => 'سجل التدقيق',
        ]);
    }
}
