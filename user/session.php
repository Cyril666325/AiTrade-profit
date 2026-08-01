<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['USERLOGIN'])) {
    header('Location: login.php');
    exit;
}

$userLogin = $_SESSION['USERLOGIN'];

$stmt = $conn->prepare(
    "SELECT id, email, fname, lname, password, phone, gender, country, address,
            occupation, refcode, ref_balance, profit, balance, bonus,
            Id_photo, profile_photo, pin, referral, date, status
     FROM users WHERE refcode = ? LIMIT 1"
);
$stmt->bind_param('s', $userLogin);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$userData) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Expose all user vars to the including page
$userId       = (int)$userData['id'];
$email        = $userData['email'];
$fname        = $userData['fname'];
$lname        = $userData['lname'];
$phone        = $userData['phone'];
$gender       = $userData['gender'];
$country      = $userData['country'];
$address      = $userData['address'];
$occupation   = $userData['occupation'];
$refcode      = $userData['refcode'];
$ref_balance  = (float)$userData['ref_balance'];
$profit       = (float)$userData['profit'];
$balance      = (float)$userData['balance'];
$bonus        = (float)$userData['bonus'];
$id_photo     = $userData['Id_photo'];
$profile_photo= $userData['profile_photo'];
$pin          = $userData['pin'];
$referral     = $userData['referral'];
$joined       = $userData['date'];
$status       = $userData['status'];   // 'Not Verified' | 'submitted' | 'Verified'
$total_deposit = 0.00;

// Calculate lifetime deposits
$dstmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) FROM deposits WHERE userId = ? AND status = 'completed'");
$dstmt->bind_param('i', $userId);
$dstmt->execute();
$dstmt->bind_result($total_deposit);
$dstmt->fetch();
$dstmt->close();
$total_deposit = (float)$total_deposit;
$is_demo = ($email === 'user@aitrading.com');
