<?php

namespace App\Controllers;

use App\Routing\Attributes\Route;
use App\Core\Database;
use App\Services\ProfileService;
use PDO;

class AdminController extends AbstractController
{
    public function __construct()
    {
    }

    #[Route('/admin', 'GET')]
    public function index(): void
    {
        $this->requireAdmin();

        $db = Database::getInstance();
        $stats = [];
        $stats['users'] = (int)($db->fetchOne('SELECT COUNT(*) AS c FROM users WHERE is_deleted = FALSE')['c'] ?? 0);
        $stats['tickets_open'] = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM support_tickets WHERE status = 'open'")['c'] ?? 0);

        $this->render('admin/dashboard', ['stats' => $stats]);
    }

    #[Route('/admin/tickets', 'GET')]
    public function tickets(): void
    {
        $this->requireAdmin();

        $page = max(1, (int) $this->queryParam('page', 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $db = Database::getInstance();
        $sql = "SELECT st.id,
                   st.user_id,
                   st.subject,
                   st.message,
                   st.status,
                   st.admin_response,
                   st.created_at,
                   st.updated_at,
                   u.name AS user_nickname,
                   u.email AS user_email,
                   u.avatar AS user_avatar
            FROM support_tickets st
            LEFT JOIN users u ON u.id = st.user_id
            ORDER BY st.created_at DESC
            LIMIT :limit OFFSET :offset";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $tickets = $stmt->fetchAll();

        $this->render('admin/tickets', ['tickets' => $tickets, 'page' => $page]);
    }

    #[Route('/admin/tickets/resolve', 'POST')]
    public function resolveTicket(): void
    {
        $this->verifyCsrf();
        $this->requireAdmin();

        $ticketId = (int)$this->bodyParam('ticket_id', 0);
        $response = trim((string)$this->bodyParam('response', ''));
        $action = trim((string)$this->bodyParam('action', 'resolve'));

        if ($ticketId <= 0) {
            $_SESSION['flash_error'] = 'Invalid ticket id';
            $this->redirectBack('/admin');
            return;
        }

        $db = Database::getInstance();
        $profileService = new ProfileService();

        $ticket = $db->fetchOne(
            'SELECT id, user_id, subject, message FROM support_tickets WHERE id = :id LIMIT 1',
            ['id' => $ticketId]
        );

        if (!$ticket) {
            $_SESSION['flash_error'] = 'Ticket not found';
            $this->redirectBack('/admin');
            return;
        }

        $status = 'resolved';
        if ($action === 'reject') {
            $status = 'closed';
        }

        $verificationSubject = 'Запрос на подтверждение статуса студента ИТИС';

        $db->beginTransaction();
        try {
            if ($action === 'resolve' && (string)($ticket['subject'] ?? '') === $verificationSubject) {
                if (!$profileService->approveStudentVerificationTicket($ticketId)) {
                    $db->rollBack();
                    $_SESSION['flash_error'] = 'Не удалось подтвердить студента';
                    $this->redirectBack('/admin');
                    return;
                }
            }

            $updated = $db->execute('UPDATE support_tickets SET status = :status, admin_response = :resp, updated_at = NOW() WHERE id = :id', [
                'status' => $status,
                'resp' => $response,
                'id' => $ticketId
            ]);

            if (!$updated) {
                $db->rollBack();
                $_SESSION['flash_error'] = 'Failed to update ticket';
                $this->redirectBack('/admin');
                return;
            }

            $db->commit();
            $_SESSION['flash_success'] = 'Тикет обработан';
            $this->redirectBack('/admin');
            return;
        } catch (\Throwable $e) {
            if ($db->getConnection()->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['flash_error'] = 'Failed to update ticket';
            $this->redirectBack('/admin');
            return;
        }
    }

    #[Route('/admin/users', 'GET')]
    public function users(): void
    {
        $this->requireAdmin();

        $page = max(1, (int)$this->queryParam('page', 1));
        $perPage = 30;
        $offset = ($page - 1) * $perPage;
        $q = trim((string)$this->queryParam('q', ''));

        $db = Database::getInstance();

        if ($q !== '') {
            $sql = "SELECT id, name, email, role, created_at, is_verified_student, student_group
                    FROM users
                    WHERE (name ILIKE :q OR email ILIKE :q) AND is_deleted = FALSE
                    ORDER BY id DESC LIMIT :limit OFFSET :offset";

            $stmt = $db->getConnection()->prepare($sql);
            $stmt->bindValue('q', "%{$q}%", PDO::PARAM_STR);
            $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $users = $stmt->fetchAll();
        } else {
            $sql = "SELECT id, name, email, role, created_at, is_verified_student, student_group
                    FROM users
                    WHERE is_deleted = FALSE
                    ORDER BY id DESC LIMIT :limit OFFSET :offset";

            $stmt = $db->getConnection()->prepare($sql);
            $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $users = $stmt->fetchAll();
        }

        $totalRow = $db->fetchOne('SELECT COUNT(*) AS c FROM users WHERE is_deleted = FALSE');
        $total = (int)($totalRow['c'] ?? 0);

        $this->render('admin/users', [
            'users' => $users,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'q' => $q
        ]);
    }

    #[Route('/admin/users/role', 'POST')]
    public function changeUserRole(): void
    {
        $this->verifyCsrf();
        $this->requireAdmin();

        $userId = (int)$this->bodyParam('user_id', 0);
        $role = trim((string)$this->bodyParam('role', ''));
        $allowed = ['user', 'admin', 'superadmin'];
        if ($userId <= 0 || !in_array($role, $allowed, true)) {
            $_SESSION['flash_error'] = 'Invalid parameters';
            $this->redirectBack('/admin/users');
            return;
        }

        $db = Database::getInstance();
        $updated = $db->execute('UPDATE users SET role = :role, updated_at = NOW() WHERE id = :id', [
            'role' => $role,
            'id' => $userId
        ]);
        if ($updated) {
            $_SESSION['flash_success'] = 'Role updated';
            $this->redirectBack('/admin/users');
            return;
        } else {
            $_SESSION['flash_error'] = 'Failed to update role';
            $this->redirectBack('/admin/users');
            return;
        }
    }
}
