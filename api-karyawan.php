
<?php
require_once "connection.php";

header('Content-Type: application/json; charset=utf-8');

$function = $_GET['function'] ?? '';

$allowedFunctions = [
    'getKaryawan',
    'insertKaryawan',
    'getKaryawanById',
    'updateKaryawan',
    'deleteKaryawan'
];

if (!in_array($function, $allowedFunctions, true)) {
    response(400, 'Function tidak valid');
    exit;
}

// Baca data body untuk request PUT.
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $raw = file_get_contents('php://input');
    $putData = [];

    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (stripos($contentType, 'application/json') !== false) {
        $putData = json_decode($raw, true) ?? [];
    } else {
        parse_str($raw, $putData);
    }

    $_POST = $putData;
}

$function();

function response($status, $message, $data = null) {
    http_response_code($status);

    $result = [
        'status' => $status,
        'message' => $message
    ];

    if ($data !== null) {
        $result['data'] = $data;
    }

    echo json_encode($result);
}

function getKaryawan() {
    global $connect;

    $result = $connect->query(
        "SELECT * FROM tbl_karyawan"
    );

    if (!$result) {
        response(500, 'Gagal mengambil data');
        return;
    }

    response(200, 'Success', $result->fetch_all(MYSQLI_ASSOC));
}

function insertKaryawan() {
    global $connect;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        response(405, 'Gunakan method POST');
        return;
    }

    $nama = trim($_POST['nama'] ?? '');
    $jenis = trim($_POST['jenis_kelamin'] ?? '');
    $hp = trim($_POST['handphone'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    if ($nama === '' || $jenis === '' ||
        $hp === '' || $alamat === '') {
        response(400, 'Data karyawan belum lengkap');
        return;
    }

    if (!in_array($jenis, ['Laki-laki', 'Perempuan'], true)) {
        response(400, 'Jenis kelamin tidak valid');
        return;
    }

    $stmt = $connect->prepare(
        "INSERT INTO tbl_karyawan
        (nama, jenis_kelamin, handphone, alamat)
        VALUES (?, ?, ?, ?)"
    );

    $stmt->bind_param("ssss", $nama, $jenis, $hp, $alamat);

    if ($stmt->execute()) {
        response(201, 'Insert Success', [
            'id' => $connect->insert_id
        ]);
    } else {
        response(500, 'Insert Failed');
    }
}

function getKaryawanById() {
    global $connect;

    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$id || $id < 1) {
        response(400, 'ID tidak valid');
        return;
    }

    $stmt = $connect->prepare(
        "SELECT * FROM tbl_karyawan WHERE id = ?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $data = $stmt->get_result()->fetch_assoc();

    if ($data) {
        response(200, 'Success', $data);
    } else {
        response(404, 'Data karyawan tidak ditemukan');
    }
}

function updateKaryawan() {
    global $connect;

    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        response(405, 'Gunakan method PUT');
        return;
    }

    // ID dibaca dari URL.
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    // Data PUT sudah dibaca di bagian atas.
    $nama = trim($_POST['nama'] ?? '');
    $jenis = trim($_POST['jenis_kelamin'] ?? '');
    $hp = trim($_POST['handphone'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    if ($id < 1 || $nama === '' || $jenis === '' ||
        $hp === '' || $alamat === '') {
        response(400, 'Parameter tidak lengkap atau tidak valid');
        return;
    }

    if (!in_array($jenis, ['Laki-laki', 'Perempuan'], true)) {
        response(400, 'Jenis kelamin tidak valid');
        return;
    }

    $stmt = $connect->prepare(
        "UPDATE tbl_karyawan
         SET nama = ?, jenis_kelamin = ?, handphone = ?, alamat = ?
         WHERE id = ?"
    );

    $stmt->bind_param(
        "ssssi",
        $nama,
        $jenis,
        $hp,
        $alamat,
        $id
    );

    if ($stmt->execute()) {
        if ($stmt->affected_rows >= 0) {
            response(200, 'Update Success', [
                'id' => $id
            ]);
        } else {
            response(500, 'Update Failed');
        }
    } else {
        response(500, 'Update Failed');
    }
}

function deleteKaryawan() {
    global $connect;

    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        response(405, 'Gunakan method DELETE');
        return;
    }

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id < 1) {
        response(400, 'ID tidak valid');
        return;
    }

    $stmt = $connect->prepare(
        "DELETE FROM tbl_karyawan WHERE id = ?"
    );

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            response(200, 'Delete Success');
        } else {
            response(404, 'Data karyawan tidak ditemukan');
        }
    } else {
        response(500, 'Delete Failed');
    }
}
?>