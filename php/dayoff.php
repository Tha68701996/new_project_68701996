<?php
require_once __DIR__ . '/db.php';
session_start();

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $_GET['action'] ?? ($data['action'] ?? '');

function sendJson($statusCode, $payload)
{
	http_response_code($statusCode);
	echo json_encode($payload, JSON_UNESCAPED_UNICODE);
	exit;
}

if ($action === 'logout') {
	$_SESSION = [];
	session_destroy();
	sendJson(200, ['success' => true]);
}

if (empty($_SESSION['user_id'])) {
	sendJson(401, ['success' => false, 'message' => 'กรุณาเข้าสู่ระบบใหม่']);
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($action === 'admin') {
	if (empty($_SESSION['is_manager'])
		|| strtolower($_SESSION['user_email'] ?? '') !== 'suratan999@gmail.com') {
		sendJson(403, ['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึงหน้าผู้ดูแล']);
	}

	try {
		$conn->exec("CREATE TABLE IF NOT EXISTS dayoff (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			user_id BIGINT UNSIGNED NOT NULL,
			leave_type VARCHAR(50) NOT NULL,
			start_date DATE NOT NULL,
			end_date DATE NOT NULL,
			reason VARCHAR(500) NOT NULL,
			status ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			INDEX idx_leave_user_status (user_id, status)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

		if ($method === 'GET') {
			$stmt = $conn->query(
				"SELECT d.id, d.user_id AS userId, u.name AS userName, u.email,
					d.leave_type AS type, d.start_date AS startDate,
					d.end_date AS endDate, DATEDIFF(d.end_date, d.start_date) + 1 AS days,
					d.reason, d.status, d.created_at AS createdAt
				 FROM dayoff d
			 LEFT JOIN users u ON u.user_id = d.user_id
			 WHERE d.status <> 'cancelled'
			 ORDER BY (d.status = 'pending') DESC, d.created_at DESC"
			);
			sendJson(200, ['success' => true, 'requests' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
		}

		if ($method === 'POST') {
			$requestId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
			$status = $data['status'] ?? '';
			if (!$requestId || !in_array($status, ['approved', 'rejected'], true)) {
				sendJson(422, ['success' => false, 'message' => 'ข้อมูลการพิจารณาไม่ถูกต้อง']);
			}

			$stmt = $conn->prepare(
				"UPDATE dayoff SET status = :status
				 WHERE id = :id AND status = 'pending'"
			);
			$stmt->execute(['status' => $status, 'id' => $requestId]);
			if ($stmt->rowCount() === 0) {
				sendJson(409, ['success' => false, 'message' => 'รายการนี้ถูกพิจารณาไปแล้ว']);
			}
			sendJson(200, ['success' => true, 'message' => 'บันทึกผลการพิจารณาแล้ว']);
		}

		sendJson(405, ['success' => false, 'message' => 'ไม่รองรับคำขอนี้']);
	} catch (PDOException $error) {
		sendJson(500, ['success' => false, 'message' => 'ฐานข้อมูลทำงานผิดพลาด: ' . $error->getMessage()]);
	}
}

try {
	// สร้างตารางให้อัตโนมัติหากยังไม่มี
	$conn->exec("CREATE TABLE IF NOT EXISTS dayoff (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
		user_id BIGINT UNSIGNED NOT NULL,
		leave_type VARCHAR(50) NOT NULL,
		start_date DATE NOT NULL,
		end_date DATE NOT NULL,
		reason VARCHAR(500) NOT NULL,
		status ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
		created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		INDEX idx_leave_user_status (user_id, status)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	if ($method === 'GET') {
		$stmt = $conn->prepare(
			"SELECT id, leave_type AS type, start_date AS startDate,
					end_date AS endDate, DATEDIFF(end_date, start_date) + 1 AS days,
					reason, status
			 FROM dayoff
			 WHERE user_id = :user_id AND status <> 'cancelled'
			 ORDER BY created_at DESC"
		);
		$stmt->execute(['user_id' => $userId]);
		sendJson(200, ['success' => true, 'requests' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
	}

	if ($method === 'POST') {
		$type = trim($data['type'] ?? '');
		$startDate = $data['startDate'] ?? '';
		$endDate = $data['endDate'] ?? '';
		$reason = trim($data['reason'] ?? '');
		$allowedTypes = ['ลาป่วย', 'ลากิจ', 'ลาพักร้อน', 'ลาอื่น ๆ'];
		$validDate = function ($value) {
			if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
				return false;
			}
			[$year, $month, $day] = array_map('intval', explode('-', $value));
			return checkdate($month, $day, $year);
		};

		if (!in_array($type, $allowedTypes, true) || !$validDate($startDate)
			|| !$validDate($endDate) || $endDate < $startDate
			|| $reason === '' || strlen($reason) > 2000) {
			sendJson(422, ['success' => false, 'message' => 'กรุณาตรวจสอบประเภท วันลา และเหตุผลให้ถูกต้อง']);
		}

		$stmt = $conn->prepare(
			"INSERT INTO dayoff (user_id, leave_type, start_date, end_date, reason)
			 VALUES (:user_id, :leave_type, :start_date, :end_date, :reason)"
		);
		$stmt->execute([
			'user_id' => $userId,
			'leave_type' => $type,
			'start_date' => $startDate,
			'end_date' => $endDate,
			'reason' => $reason,
		]);
		sendJson(201, ['success' => true, 'message' => 'ส่งคำขอลาเรียบร้อยแล้ว']);
	}

	if ($method === 'DELETE') {
		$requestId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
		if (!$requestId) {
			sendJson(422, ['success' => false, 'message' => 'ไม่พบรายการที่ต้องการยกเลิก']);
		}

		$stmt = $conn->prepare(
			"UPDATE dayoff SET status = 'cancelled'
			 WHERE id = :id AND user_id = :user_id AND status = 'pending'"
		);
		$stmt->execute(['id' => $requestId, 'user_id' => $userId]);
		if ($stmt->rowCount() === 0) {
			sendJson(404, ['success' => false, 'message' => 'ยกเลิกคำขอนี้ไม่ได้ อาจถูกดำเนินการไปแล้ว']);
		}
		sendJson(200, ['success' => true, 'message' => 'ยกเลิกคำขอแล้ว']);
	}

	sendJson(405, ['success' => false, 'message' => 'ไม่รองรับคำขอนี้']);
} catch (PDOException $error) {
	sendJson(500, ['success' => false, 'message' => 'ฐานข้อมูลทำงานผิดพลาด: ' . $error->getMessage()]);
}
