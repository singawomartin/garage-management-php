<?php
// Simple PHP API for the Garage Management System.
header('Content-Type: application/json; charset=utf-8');

$host = 'localhost';
$dbname = 'garage_management';
$username = 'root';
$password = ''; // Enter your local MySQL password here if you have one.

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Check the settings in api.php and import database.sql.']);
    exit;
}

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}
function getInput() {
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : $_POST;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET' && $action === 'summary') {
        $summary = [
            'customers' => (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn(),
            'vehicles' => (int)$pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn(),
            'jobs' => (int)$pdo->query("SELECT COUNT(*) FROM service_records")->fetchColumn(),
            'pending' => (int)$pdo->query("SELECT COUNT(*) FROM service_records WHERE status <> 'Completed'")->fetchColumn()
        ];
        respond(['success' => true, 'summary' => $summary]);
    }

    if ($method === 'GET' && $action === 'customers') {
        respond(['success' => true, 'data' => $pdo->query("SELECT * FROM customers ORDER BY id DESC")->fetchAll()]);
    }
    if ($method === 'GET' && $action === 'vehicles') {
        $sql = "SELECT v.*, c.full_name AS customer_name FROM vehicles v
                JOIN customers c ON c.id = v.customer_id ORDER BY v.id DESC";
        respond(['success' => true, 'data' => $pdo->query($sql)->fetchAll()]);
    }
    if ($method === 'GET' && $action === 'repairs') {
        $sql = "SELECT s.*, v.registration_number, v.make, v.model, c.full_name AS customer_name
                FROM service_records s JOIN vehicles v ON v.id = s.vehicle_id
                JOIN customers c ON c.id = v.customer_id ORDER BY s.id DESC";
        respond(['success' => true, 'data' => $pdo->query($sql)->fetchAll()]);
    }

    $data = getInput();

    if ($method === 'POST' && $action === 'add_customer') {
        $name = trim($data['full_name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $email = trim($data['email'] ?? '');
        if ($name === '' || $phone === '') respond(['success' => false, 'message' => 'Name and phone are required.'], 422);
        $stmt = $pdo->prepare("INSERT INTO customers (full_name, phone, email) VALUES (?, ?, ?)");
        $stmt->execute([$name, $phone, $email ?: null]);
        respond(['success' => true, 'message' => 'Customer saved successfully.']);
    }

    if ($method === 'POST' && $action === 'add_vehicle') {
        $customerId = (int)($data['customer_id'] ?? 0);
        $make = trim($data['make'] ?? '');
        $model = trim($data['model'] ?? '');
        $registration = strtoupper(trim($data['registration_number'] ?? ''));
        $year = trim((string)($data['year'] ?? ''));
        if (!$customerId || $make === '' || $model === '' || $registration === '') {
            respond(['success' => false, 'message' => 'Select a customer and enter make, model and registration number.'], 422);
        }
        $stmt = $pdo->prepare("INSERT INTO vehicles (customer_id, make, model, registration_number, year) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$customerId, $make, $model, $registration, $year !== '' ? (int)$year : null]);
        respond(['success' => true, 'message' => 'Vehicle saved successfully.']);
    }

    if ($method === 'POST' && $action === 'add_repair') {
        $vehicleId = (int)($data['vehicle_id'] ?? 0);
        $description = trim($data['description'] ?? '');
        $cost = (float)($data['cost'] ?? 0);
        $status = $data['status'] ?? 'Pending';
        if (!$vehicleId || $description === '' || $cost < 0 ||
            !in_array($status, ['Pending', 'In Progress', 'Completed'], true)) {
            respond(['success' => false, 'message' => 'Enter a vehicle, description, valid cost and status.'], 422);
        }
        $stmt = $pdo->prepare("INSERT INTO service_records (vehicle_id, description, cost, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$vehicleId, $description, $cost, $status]);
        respond(['success' => true, 'message' => 'Service job saved successfully.']);
    }

    respond(['success' => false, 'message' => 'Unknown action.'], 404);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') respond(['success' => false, 'message' => 'Duplicate registration number or invalid related record.'], 409);
    respond(['success' => false, 'message' => 'Database operation failed. Check your data and database structure.'], 500);
}
