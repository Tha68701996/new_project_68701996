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

function ensureDayoffTable($conn)
{
	$conn->exec("CREATE TABLE IF NOT EXISTS dayoff (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
		user_id BIGINT UNSIGNED NOT NULL,
		leave_type VARCHAR(50) NOT NULL,
		start_date DATE NOT NULL,
		end_date DATE NOT NULL,
		reason VARCHAR(500) NOT NULL,
		evidence_data LONGBLOB NULL,
		evidence_mime VARCHAR(30) NULL,
		status ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
		created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		INDEX idx_leave_user_status (user_id, status)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$columns = $conn->query("SHOW COLUMNS FROM dayoff")->fetchAll(PDO::FETCH_COLUMN);
	if (!in_array('evidence_data', $columns, true)) {
		$conn->exec("ALTER TABLE dayoff ADD COLUMN evidence_data LONGBLOB NULL");
	}
	if (!in_array('evidence_mime', $columns, true)) {
		$conn->exec("ALTER TABLE dayoff ADD COLUMN evidence_mime VARCHAR(30) NULL");
	}
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
		ensureDayoffTable($conn);

		if (isset($_GET['evidence'])) {
			$evidenceId = filter_var($_GET['evidence'], FILTER_VALIDATE_INT);
			if (!$evidenceId) {
				sendJson(422, ['success' => false, 'message' => 'ไม่พบไฟล์หลักฐาน']);
			}
			$stmt = $conn->prepare("SELECT evidence_data, evidence_mime FROM dayoff WHERE id = :id");
			$stmt->execute(['id' => $evidenceId]);
			$evidence = $stmt->fetch(PDO::FETCH_ASSOC);
			if (!$evidence || !$evidence['evidence_data']) {
				sendJson(404, ['success' => false, 'message' => 'รายการนี้ไม่มีภาพหลักฐาน']);
			}
			sendJson(200, [
				'success' => true,
				'mime' => $evidence['evidence_mime'],
				'image' => base64_encode($evidence['evidence_data']),
			]);
		}

		if ($method === 'GET') {
			$stmt = $conn->query(
				"SELECT d.id, d.user_id AS userId, u.name AS userName, u.email,
					d.leave_type AS type, d.start_date AS startDate,
					d.end_date AS endDate, DATEDIFF(d.end_date, d.start_date) + 1 AS days,
					d.reason, d.status, d.created_at AS createdAt,
					(d.evidence_mime IS NOT NULL) AS hasEvidence
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
	ensureDayoffTable($conn);

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
		if (!empty($_POST)) {
			$data = $_POST;
		}
		$type = trim($data['type'] ?? '');
		$startDate = $data['startDate'] ?? '';
		$endDate = $data['endDate'] ?? '';
		$reason = trim($data['reason'] ?? '');
		$allowedTypes = ['ลาป่วย', 'ลากิจ', 'ลาพักร้อน', 'ลาเหตุฉุกเฉิน', 'ลาอื่น ๆ'];
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

		$imageData = null;
		$imageMime = null;
		$upload = $_FILES['evidence'] ?? null;
		$requiresEvidence = in_array($type, ['ลาป่วย', 'ลาเหตุฉุกเฉิน'], true);
		if ($upload && $upload['error'] !== UPLOAD_ERR_NO_FILE) {
			if ($upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > 5 * 1024 * 1024) {
				sendJson(422, ['success' => false, 'message' => 'อัปโหลดไม่สำเร็จ หรือไฟล์มีขนาดเกิน 5 MB']);
			}
			if (!is_uploaded_file($upload['tmp_name'])) {
				sendJson(422, ['success' => false, 'message' => 'ไฟล์อัปโหลดไม่ถูกต้อง']);
			}
			$fileInfo = new finfo(FILEINFO_MIME_TYPE);
			$imageMime = $fileInfo->file($upload['tmp_name']);
			if (!in_array($imageMime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
				sendJson(422, ['success' => false, 'message' => 'อนุญาตเฉพาะไฟล์ภาพ JPG, PNG หรือ WEBP']);
			}
			$imageData = file_get_contents($upload['tmp_name']);
		}
		if ($requiresEvidence && $imageData === null) {
			sendJson(422, ['success' => false, 'message' => 'กรุณาแนบภาพหลักฐานสำหรับการลาป่วยหรือเหตุฉุกเฉิน']);
		}

		$stmt = $conn->prepare(
			"INSERT INTO dayoff (user_id, leave_type, start_date, end_date, reason, evidence_data, evidence_mime)
			 VALUES (:user_id, :leave_type, :start_date, :end_date, :reason, :evidence_data, :evidence_mime)"
		);
		$stmt->bindValue(':user_id', $userId);
		$stmt->bindValue(':leave_type', $type);
		$stmt->bindValue(':start_date', $startDate);
		$stmt->bindValue(':end_date', $endDate);
		$stmt->bindValue(':reason', $reason);
		$stmt->bindValue(':evidence_data', $imageData, $imageData === null ? PDO::PARAM_NULL : PDO::PARAM_LOB);
		$stmt->bindValue(':evidence_mime', $imageMime, $imageMime === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
		$stmt->execute();
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
