<?php
/**
 * Database connection & persistence layer for AFHMC Web Application (Single Form Architecture)
 */

function getDbConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $host = '127.0.0.1';
    $db   = 'afhmc_db';
    $user = 'root';
    $pass = '';

    // Attempt MySQL connection first
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db`");
        createTables($pdo, 'mysql');
        return $pdo;
    } catch (Exception $e) {
        // Fallback to SQLite file database if MySQL service is inactive
        $dbDir = __DIR__ . '/data';
        if (!file_exists($dbDir)) {
            mkdir($dbDir, 0777, true);
        }
        $sqliteFile = $dbDir . '/afhmc.db';
        try {
            $pdo = new PDO("sqlite:" . $sqliteFile, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            createTables($pdo, 'sqlite');
            return $pdo;
        } catch (Exception $e2) {
            error_log("Database connection error: " . $e2->getMessage());
            return null;
        }
    }
}

function createTables($pdo, $driver = 'mysql') {
    $autoInc = ($driver === 'sqlite') ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';

    $sqlUnified = "CREATE TABLE IF NOT EXISTS afhmc_applications (
        id $autoInc,
        submitted_at VARCHAR(50),
        membership_type VARCHAR(50),
        title VARCHAR(20),
        rank VARCHAR(50),
        first_name VARCHAR(100),
        last_name VARCHAR(100),
        address TEXT,
        occupation VARCHAR(100),
        nationality VARCHAR(50),
        official_address TEXT,
        passport_nic VARCHAR(50),
        dob VARCHAR(30),
        telephone VARCHAR(30),
        mobile VARCHAR(30),
        email VARCHAR(100),
        signature VARCHAR(100),
        recommendation TEXT,
        category VARCHAR(50),
        date_issue VARCHAR(30),
        date_expiry VARCHAR(30),
        date_received VARCHAR(30),
        receipt_no VARCHAR(50),
        amount_paid VARCHAR(50),
        card_number VARCHAR(50),
        commander VARCHAR(100),
        approval_date VARCHAR(30),
        gender VARCHAR(20),
        age VARCHAR(10),
        cardio_1 VARCHAR(10),
        cardio_2 VARCHAR(10),
        cardio_3 VARCHAR(10),
        cardio_4 VARCHAR(10),
        cardio_5 VARCHAR(10),
        cardio_6 VARCHAR(10),
        cardio_7 VARCHAR(10),
        cardio_8 VARCHAR(10),
        cardio_9 VARCHAR(10),
        cardio_10 VARCHAR(10),
        medications TEXT,
        back_problem VARCHAR(100),
        orthopedic TEXT,
        hernia VARCHAR(10),
        surgeries VARCHAR(10),
        surgery_details TEXT,
        stress VARCHAR(20),
        activity VARCHAR(50),
        last_exam VARCHAR(50),
        health_signature VARCHAR(100),
        health_date VARCHAR(30),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    // Admins table for secure database-backed login
    $sqlAdmin = "CREATE TABLE IF NOT EXISTS afhmc_admins (
        id $autoInc,
        username VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlAdmin);

    $pdo->exec($sqlUnified);

}

function saveFullApplication($data) {
    $pdo = getDbConnection();
    if (!$pdo) return false;

    // Composite Date of Birth if entered as individual D/M/Y fields
    $dob = trim(($data['dob_d'] ?? '') . '/' . ($data['dob_m'] ?? '') . '/' . ($data['dob_y'] ?? ''), '/');
    if (empty($dob)) {
        $dob = $data['dob'] ?? '';
    }

    $orthopedic = is_array($data['orthopedic'] ?? null) ? implode(', ', $data['orthopedic']) : ($data['orthopedic'] ?? '');
    $backProblem = is_array($data['back_problem'] ?? null) ? implode(', ', $data['back_problem']) : ($data['back_problem'] ?? '');

    $sql = "INSERT INTO afhmc_applications (
        submitted_at, membership_type, title, rank, first_name, last_name,
        address, occupation, nationality, official_address, passport_nic, dob,
        telephone, mobile, email, signature, recommendation, category, date_issue,
        date_expiry, date_received, receipt_no, amount_paid, card_number, commander, approval_date,
        gender, age, cardio_1, cardio_2, cardio_3, cardio_4, cardio_5, cardio_6, cardio_7, cardio_8, cardio_9, cardio_10,
        medications, back_problem, orthopedic, hernia, surgeries, surgery_details,
        stress, activity, last_exam, health_signature, health_date
    ) VALUES (
        :submitted_at, :membership_type, :title, :rank, :first_name, :last_name,
        :address, :occupation, :nationality, :official_address, :passport_nic, :dob,
        :telephone, :mobile, :email, :signature, :recommendation, :category, :date_issue,
        :date_expiry, :date_received, :receipt_no, :amount_paid, :card_number, :commander, :approval_date,
        :gender, :age, :cardio_1, :cardio_2, :cardio_3, :cardio_4, :cardio_5, :cardio_6, :cardio_7, :cardio_8, :cardio_9, :cardio_10,
        :medications, :back_problem, :orthopedic, :hernia, :surgeries, :surgery_details,
        :stress, :activity, :last_exam, :health_signature, :health_date
    )";

    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        ':submitted_at'     => date('Y-m-d H:i:s'),
        ':membership_type'  => $data['membership_type'] ?? '',
        ':title'            => $data['title'] ?? '',
        ':rank'             => $data['rank'] ?? '',
        ':first_name'       => $data['first_name'] ?? '',
        ':last_name'        => $data['last_name'] ?? '',
        ':address'          => $data['address'] ?? '',
        ':occupation'       => $data['occupation'] ?? '',
        ':nationality'      => $data['nationality'] ?? '',
        ':official_address' => $data['official_address'] ?? '',
        ':passport_nic'     => $data['passport_nic'] ?? '',
        ':dob'              => $dob,
        ':telephone'        => $data['telephone'] ?? '',
        ':mobile'           => $data['mobile'] ?? '',
        ':email'            => $data['email'] ?? '',
        ':signature'        => $data['signature'] ?? '',
        ':recommendation'   => $data['recommendation'] ?? '',
        ':category'         => $data['category'] ?? '',
        ':date_issue'       => $data['date_issue'] ?? '',
        ':date_expiry'      => $data['date_expiry'] ?? '',
        ':date_received'    => $data['date_received'] ?? '',
        ':receipt_no'       => $data['receipt_no'] ?? '',
        ':amount_paid'      => $data['amount_paid'] ?? '',
        ':card_number'      => $data['card_number'] ?? '',
        ':commander'        => $data['commander'] ?? '',
        ':approval_date'    => $data['approval_date'] ?? '',
        ':gender'           => $data['gender'] ?? '',
        ':age'              => $data['age'] ?? '',
        ':cardio_1'         => $data['cardio_1'] ?? '',
        ':cardio_2'         => $data['cardio_2'] ?? '',
        ':cardio_3'         => $data['cardio_3'] ?? '',
        ':cardio_4'         => $data['cardio_4'] ?? '',
        ':cardio_5'         => $data['cardio_5'] ?? '',
        ':cardio_6'         => $data['cardio_6'] ?? '',
        ':cardio_7'         => $data['cardio_7'] ?? '',
        ':cardio_8'         => $data['cardio_8'] ?? '',
        ':cardio_9'         => $data['cardio_9'] ?? '',
        ':cardio_10'        => $data['cardio_10'] ?? '',
        ':medications'      => $data['medications'] ?? '',
        ':back_problem'     => $backProblem,
        ':orthopedic'       => $orthopedic,
        ':hernia'           => $data['hernia'] ?? '',
        ':surgeries'        => $data['surgeries'] ?? '',
        ':surgery_details'  => $data['surgery_details'] ?? '',
        ':stress'           => $data['stress'] ?? '',
        ':activity'         => $data['activity'] ?? '',
        ':last_exam'        => $data['last_exam'] ?? '',
        ':health_signature' => $data['health_signature'] ?? '',
        ':health_date'      => $data['health_date'] ?? ''
    ]);
}
