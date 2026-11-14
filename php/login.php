<?php
include 'db.php'; // ไฟล์เชื่อมต่อ DB
session_start();

$data = json_decode(file_get_contents("php://input"), true);

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (!$email || !$password) {
    echo json_encode([
        "success" => false,
        "message" => "กรอกข้อมูลไม่ครบ"
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->bindParam(":email", $email);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {

        // รองรับทั้งคอลัมน์ password_hash และ password
        $passwordColumn = isset($user['password']) ? 'password' : 'password_hash';
        $storedPassword = $user[$passwordColumn] ?? '';
        $passwordIsValid = is_string($storedPassword)
            && password_verify($password, $storedPassword);

        // ถ้าฐานข้อมูลเก็บรหัสผ่านเดิมเป็นข้อความ ให้แฮชใหม่ทันทีหลังเข้าสู่ระบบสำเร็จ
        if (
            !$passwordIsValid
            && is_string($storedPassword)
            && password_get_info($storedPassword)['algo'] === null
            && hash_equals($storedPassword, $password)
        ) {
            $passwordIsValid = true;
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $update = $conn->prepare(
                "UPDATE users SET `$passwordColumn` = :password WHERE user_id = :user_id"
            );
            $update->execute([
                'password' => $hashedPassword,
                'user_id' => $user['user_id'],
            ]);
        }

        if ($passwordIsValid) {

            session_regenerate_id(true);
            $managerEmail = 'suratan999@gmail.com';
            $isManager = strtolower($user['email']) === $managerEmail;
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['is_manager'] = $isManager;

            echo json_encode([
                "success" => true,
                "message" => "Login สำเร็จ",
                "user" => [
                    "id" => $user['user_id'],
                    "email" => $user['email'],
                    "name" => $user['name'],
                    "isManager" => $isManager
                ]
            ]);

        } else {
            echo json_encode([
                "success" => false,
                "message" => "รหัสผ่านไม่ถูกต้อง"
            ]);
        }

    } else {
        echo json_encode([
            "success" => false,
            "message" => "ไม่พบผู้ใช้งาน"
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}