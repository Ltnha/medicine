<?php
session_start();

// 1. Kiểm tra bảo mật Session phân quyền đăng nhập của Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// 2. Kết nối cơ sở dữ liệu MySQL
require_once '../config/config.php';
$conn = getDbConnection();

// 3. XỬ LÝ API AJAX (Lưu mới HOẶC Cập nhật lô thuốc sau khi ký Blockchain thành công)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'save_lo_thuoc') {
        try {
            $id_thuoc = $_POST['id_thuoc'];
            $ma_lo = $_POST['ma_lo'];
            $ma_tra_cuu = $_POST['ma_tra_cuu'];
            $tx_hash = $_POST['tx_hash'];
            $id_cty_dang_ky = $_POST['id_cty_dang_ky'];
            $id_cty_san_xuat = $_POST['id_cty_san_xuat'];
            $ngay_san_xuat = $_POST['ngay_san_xuat'];
            $han_su_dung = $_POST['han_su_dung'];
            $so_luong_ton = $_POST['so_luong_ton'];
            $gia_nhap = $_POST['gia_nhap'];
            $ma_admin = $_SESSION['ma_admin'] ?? 1;

            $insert_query = "INSERT INTO LoThuoc (id_thuoc, ma_lo, ma_tra_cuu, tx_hash, trang_thai_blockchain, ma_admin, id_cty_dang_ky, id_cty_san_xuat, ngay_san_xuat, han_su_dung, so_luong_ton, gia_nhap) 
                             VALUES (?, ?, ?, ?, 'confirmed', ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->execute([$id_thuoc, $ma_lo, $ma_tra_cuu, $tx_hash, $ma_admin, $id_cty_dang_ky, $id_cty_san_xuat, $ngay_san_xuat, $han_su_dung, $so_luong_ton, $gia_nhap]);

            echo json_encode(['success' => true, 'message' => 'Lưu lô thuốc lên hệ thống và Blockchain thành công!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi MySQL Backend: ' . $e->getMessage()]);
        }
        exit();
    } else if ($_POST['action'] === 'update_lo_thuoc') {
        try {
            $id_thuoc = $_POST['id_thuoc'];
            $ma_lo = $_POST['ma_lo'];
            $ma_tra_cuu = $_POST['ma_tra_cuu'];
            $tx_hash = $_POST['tx_hash'];
            $id_cty_dang_ky = $_POST['id_cty_dang_ky'];
            $id_cty_san_xuat = $_POST['id_cty_san_xuat'];
            $ngay_san_xuat = $_POST['ngay_san_xuat'];
            $han_su_dung = $_POST['han_su_dung'];
            $so_luong_ton = $_POST['so_luong_ton'];
            $gia_nhap = $_POST['gia_nhap'];

            $update_query = "UPDATE LoThuoc 
                             SET id_thuoc = ?, ma_lo = ?, tx_hash = ?, id_cty_dang_ky = ?, id_cty_san_xuat = ?, ngay_san_xuat = ?, han_su_dung = ?, so_luong_ton = ?, gia_nhap = ? 
                             WHERE ma_tra_cuu = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->execute([$id_thuoc, $ma_lo, $tx_hash, $id_cty_dang_ky, $id_cty_san_xuat, $ngay_san_xuat, $han_su_dung, $so_luong_ton, $gia_nhap, $ma_tra_cuu]);

            echo json_encode(['success' => true, 'message' => 'Cập nhật lô thuốc và đồng bộ Blockchain thành công!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi MySQL Backend: ' . $e->getMessage()]);
        }
        exit();
    }
}

// 4. XỬ LÝ LỌC, TÌM KIẾM VÀ PHÂN TRANG
$search = trim($_GET['s'] ?? '');
$filter_thuoc = trim($_GET['id_thuoc'] ?? '');
$filter_status = trim($_GET['status'] ?? '');
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10; // Số bản ghi trên mỗi trang
$offset = ($page - 1) * $limit;

// Dựng câu lệnh WHERE linh hoạt
$where_clauses = ["1=1"];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(l.ma_lo LIKE ? OR l.ma_tra_cuu LIKE ? OR t.ten_thuoc LIKE ? OR l.tx_hash LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($filter_thuoc !== '') {
    $where_clauses[] = "l.id_thuoc = ?";
    $params[] = $filter_thuoc;
}

if ($filter_status !== '') {
    $where_clauses[] = "l.trang_thai_blockchain = ?";
    $params[] = $filter_status;
}

$where_sql = implode(" AND ", $where_clauses);

try {
    // Đếm tổng số lượng bản ghi để phân trang
    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM LoThuoc l JOIN Thuoc t ON l.id_thuoc = t.id_thuoc WHERE $where_sql");
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // Truy vấn dữ liệu có phân trang (LIMIT & OFFSET)
    $sql = "SELECT l.*, t.ten_thuoc 
            FROM LoThuoc l 
            JOIN Thuoc t ON l.id_thuoc = t.id_thuoc 
            WHERE $where_sql 
            ORDER BY l.ngay_nhap_kho DESC 
            LIMIT $limit OFFSET $offset";
    $lo_stmt = $conn->prepare($sql);
    $lo_stmt->execute($params);
    $lo_thuoc_list = $lo_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy dữ liệu danh mục thuốc cho dropdown lọc & modal
    $thuoc_stmt = $conn->prepare("SELECT id_thuoc, ten_thuoc FROM Thuoc WHERE trang_thai = 1");
    $thuoc_stmt->execute();
    $thuoc_options = $thuoc_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy danh sách doanh nghiệp
    $dn_stmt = $conn->prepare("SELECT id_doanh_nghiep, ten_doanh_nghiep, loai_hinh FROM DoanhNghiep");
    $dn_stmt->execute();
    $doanh_nghiep_list = $dn_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi kết nối cơ sở dữ liệu: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PharmaChain — Quản lý lô thuốc</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --green-900: #0f3d24;
            --green-700: #137a4a;
            --green-600: #189956;
            --green-50: #e9f7ef;
            --green-100: #d7f0e1;
            --orange-600: #c2650f;
            --orange-50: #fdf1e4;
            --red-600: #d5362f;
            --red-700: #b5271f;
            --red-50: #fdeceb;
            --blue-600: #2b5fd9;
            --blue-50: #eef4ff;
            --gray-900: #1c2430;
            --gray-700: #465066;
            --gray-500: #7c869a;
            --gray-300: #dbe0e8;
            --gray-200: #e9edf2;
            --gray-100: #f2f4f7;
            --gray-50: #f8f9fb;
            --white: #fff;
            --side-bg: #1b212c;
            --side-bg-2: #141922;
            --side-text: #aab3c5;
            --side-active: #232b38;
            --radius-lg: 18px;
            --shadow-card: 0 1px 2px rgba(20, 30, 50, .04), 0 8px 24px -12px rgba(20, 30, 50, .10);
            --shadow-modal: 0 20px 60px -12px rgba(15, 30, 25, .35);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button {
            font-family: inherit;
            cursor: pointer;
        }

        .app {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 256px;
            flex-shrink: 0;
            background: linear-gradient(180deg, var(--side-bg), var(--side-bg-2));
            padding: 20px 14px;
            display: flex;
            flex-direction: column;
            gap: 22px;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 6px 8px 14px;
            border-bottom: 1px solid rgba(255, 255, 255, .07);
        }

        .brand-logo {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(155deg, var(--green-600), var(--green-900));
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-name {
            font-weight: 800;
            font-size: 15.5px;
            color: #fff;
        }

        .brand-sub {
            font-size: 11px;
            color: #7c869a;
        }

        .nav-group {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .nav-label {
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .8px;
            color: #5c667c;
            text-transform: uppercase;
            padding: 14px 12px 6px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 10.5px 12px;
            border-radius: 10px;
            font-size: 13.8px;
            color: var(--side-text);
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, .05);
            color: #fff;
        }

        .nav-item.active {
            background: var(--side-active);
            color: #fff;
            font-weight: 600;
        }

        .logout-link {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12.5px;
            color: #d5362f;
            padding: 8px 12px;
            margin-top: auto;
            border-radius: 8px;
        }

        .main {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 32px;
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            position: sticky;
            top: 0;
            z-index: 20;
        }

        .page-heading {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-heading .icon-wrap {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            background: var(--green-50);
            color: var(--green-700);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .page-title {
            font-size: 19.5px;
            font-weight: 800;
        }

        .content {
            padding: 26px 32px 60px;
            max-width: 1440px;
            width: 100%;
            margin: 0 auto;
        }

        /* Toolbar & Filters */
        .toolbar-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
            padding: 16px 18px;
            margin-bottom: 18px;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }

        .filter-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            flex: 1;
        }

        .form-input {
            border: 1px solid var(--gray-300);
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 13.5px;
            outline: none;
            background: #fff;
        }

        .form-input:focus {
            border-color: var(--green-600);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 13.5px;
            font-weight: 600;
            border-radius: 10px;
            padding: 9px 16px;
            border: 1px solid transparent;
            transition: .15s;
        }

        .btn-primary {
            background: var(--green-700);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--green-900);
        }

        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
            border-color: var(--gray-300);
        }

        .btn-secondary:hover {
            background: var(--gray-200);
        }

        .btn-ghost {
            background: transparent;
            border: 1px solid var(--gray-300);
            color: var(--gray-700);
        }

        .btn-edit {
            background: var(--blue-50);
            color: var(--blue-600);
            border: 1px solid rgba(43, 95, 217, .2);
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 6px;
        }

        .btn-copy {
            background: transparent;
            border: none;
            color: var(--gray-500);
            cursor: pointer;
            padding: 4px 6px;
            border-radius: 4px;
            transition: .15s;
        }

        .btn-copy:hover {
            color: var(--green-700);
            background: var(--green-50);
        }

        .table-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
            overflow: hidden;
        }

        .table-scroll {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
        }

        thead th {
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            color: var(--gray-500);
            background: var(--gray-50);
            padding: 13px 16px;
            border-bottom: 1px solid var(--gray-200);
        }

        tbody td {
            padding: 13px 16px;
            border-bottom: 1px solid var(--gray-100);
            font-size: 13.5px;
            color: var(--gray-900);
        }

        .cell-strong {
            font-weight: 600;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11.3px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .badge-confirmed {
            background: var(--green-50);
            color: var(--green-700);
        }

        .badge-pending {
            background: var(--orange-50);
            color: var(--orange-600);
        }

        .badge-failed {
            background: var(--red-50);
            color: var(--red-600);
        }

        .btn-wallet {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13.5px;
            font-weight: 600;
            border-radius: 10px;
            padding: 9.5px 16px;
            background: var(--white);
            border: 1px solid var(--green-700);
            color: var(--green-700);
        }

        .btn-wallet.connected {
            border-color: var(--blue-600);
            color: var(--blue-600);
            background: var(--blue-50);
        }

        /* Pagination CSS */
        .pagination-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-top: 1px solid var(--gray-200);
            background: var(--white);
        }

        .pagination {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0 8px;
            border-radius: 8px;
            border: 1px solid var(--gray-300);
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-700);
        }

        .page-link.active {
            background: var(--green-700);
            color: #fff;
            border-color: var(--green-700);
        }

        .page-link.disabled {
            opacity: .4;
            pointer-events: none;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 25, 20, .5);
            backdrop-filter: blur(2px);
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 40px 16px;
            z-index: 100;
            overflow-y: auto;
        }

        .modal-overlay.hidden {
            display: none;
        }

        .modal-box {
            background: var(--white);
            border-radius: 22px;
            width: 100%;
            max-width: 860px;
            box-shadow: var(--shadow-modal);
            overflow: hidden;
        }

        .modal-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding: 22px 26px 18px;
            border-bottom: 1px solid var(--gray-100);
        }

        .modal-head h2 {
            font-size: 18px;
            margin: 0;
            font-weight: 800;
        }

        .modal-close {
            width: 32px;
            height: 32px;
            border-radius: 9px;
            border: 1px solid var(--gray-200);
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-500);
        }

        .modal-body {
            padding: 22px 26px;
        }

        .modal-foot {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 16px 26px;
            border-top: 1px solid var(--gray-100);
            background: var(--gray-50);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .form-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-field label {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--gray-700);
        }

        .form-field input,
        .form-field select {
            border: 1px solid var(--gray-300);
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 13.5px;
            outline: none;
            width: 100%;
        }

        .form-field input:disabled {
            background-color: var(--gray-100);
            color: var(--gray-500);
            cursor: not-allowed;
        }
    </style>
</head>

<body>

    <div class="app">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-logo"><i class="fa-solid fa-pills" style="color:#fff;"></i></div>
                <div>
                    <div class="brand-name">PharmaChain</div>
                    <div class="brand-sub">Hệ thống quản trị</div>
                </div>
            </div>
            <nav class="nav-group">
                <div class="nav-label">Điều hướng</div>
                <a class="nav-item" href="dashBoard.php"><i class="fa-solid fa-chart-pie"></i> Tổng quan</a>
                <a class="nav-item" href="quanLyThuoc.php"><i class="fa-solid fa-pills"></i> Quản lý thuốc</a>
                <a class="nav-item active" href="quanLyLoThuoc.php"><i class="fa-solid fa-boxes-stacked"></i> Quản lý lô thuốc</a>
            </nav>
            <a class="logout-link" href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main">
            <header class="topbar">
                <div class="page-heading">
                    <div class="icon-wrap"><i class="fa-solid fa-boxes-stacked"></i></div>
                    <div class="page-title">Quản lý lô thuốc Blockchain</div>
                </div>
                <div>
                    <button id="connectWalletBtn" onclick="connectWallet()" class="btn-wallet">
                        <i class="fa-solid fa-wallet"></i> <span id="walletAddressText">Kết nối ví Admin</span>
                    </button>
                </div>
            </header>

            <section class="content">
                <!-- THANH TÌM KIẾM & BỘ LỌC -->
                <div class="toolbar-card">
                    <form method="GET" action="quanLyLoThuoc.php" class="filter-form">
                        <div class="filter-group">
                            <!-- Ô Tìm Kiếm Từ Khóa -->
                            <input type="text" name="s" class="form-input" style="min-width: 220px;"
                                placeholder="Tìm Mã lô, Mã QR, TxHash..."
                                value="<?php echo htmlspecialchars($search); ?>">

                            <!-- Lọc Theo Tên Thuốc -->
                            <select name="id_thuoc" class="form-input">
                                <option value="">-- Tất cả loại thuốc --</option>
                                <?php foreach ($thuoc_options as $t): ?>
                                    <option value="<?php echo $t['id_thuoc']; ?>" <?php echo $filter_thuoc == $t['id_thuoc'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($t['ten_thuoc']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <!-- Lọc Theo Trạng Thái Blockchain -->
                            <select name="status" class="form-input">
                                <option value="">-- Tất cả trạng thái --</option>
                                <option value="confirmed" <?php echo $filter_status === 'confirmed' ? 'selected' : ''; ?>>Đã xác thực (Confirmed)</option>
                                <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Đang xử lý (Pending)</option>
                                <option value="failed" <?php echo $filter_status === 'failed' ? 'selected' : ''; ?>>Thất bại (Failed)</option>
                            </select>

                            <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Lọc</button>
                            <?php if (!empty($search) || !empty($filter_thuoc) || !empty($filter_status)): ?>
                                <a href="quanLyLoThuoc.php" class="btn btn-ghost"><i class="fa-solid fa-rotate-left"></i> Xóa lọc</a>
                            <?php endif; ?>
                        </div>

                        <button type="button" class="btn btn-primary" onclick="openCreateModal()">
                            <i class="fa-solid fa-plus"></i> Phát hành lô thuốc mới
                        </button>
                    </form>
                </div>

                <!-- BẢNG DANH SÁCH LÔ THUỐC -->
                <div class="table-card">
                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Mã Lô</th>
                                    <th>Tên thuốc</th>
                                    <th>Mã Tra Cứu (QR)</th>
                                    <th>Ngày Sản Xuất</th>
                                    <th>Hạn Sử Dụng</th>
                                    <th>Số Lượng Tồn</th>
                                    <th>Trạng Thái Chuỗi</th>
                                    <th>Mã Giao Dịch (TxHash)</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($lo_thuoc_list)): ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; color: var(--gray-500); padding: 30px;">
                                            Khồng tìm thấy dữ liệu lô thuốc phù hợp!
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($lo_thuoc_list as $lo): ?>
                                        <tr>
                                            <td class="cell-strong"><?php echo htmlspecialchars($lo['ma_lo']); ?></td>
                                            <td class="cell-strong" style="color: var(--green-700);"><?php echo htmlspecialchars($lo['ten_thuoc']); ?></td>
                                            <td class="cell-strong" style="font-family: monospace;"><?php echo htmlspecialchars($lo['ma_tra_cuu']); ?></td>
                                            <td><?php echo $lo['ngay_san_xuat']; ?></td>
                                            <td><?php echo $lo['han_su_dung']; ?></td>
                                            <td><?php echo number_format($lo['so_luong_ton']); ?></td>
                                            <td>
                                                <?php
                                                $st = $lo['trang_thai_blockchain'];
                                                if ($st === 'confirmed') echo '<span class="badge badge-confirmed">Đã xác thực</span>';
                                                else if ($st === 'pending') echo '<span class="badge badge-pending">Đang xử lý</span>';
                                                else echo '<span class="badge badge-failed">Thất bại</span>';
                                                ?>
                                            </td>
                                            <td style="font-family: monospace; font-size: 11.5px; color: var(--gray-500);">
                                                <?php if (!empty($lo['tx_hash'])): ?>
                                                    <span title="<?php echo htmlspecialchars($lo['tx_hash']); ?>">
                                                        <?php echo substr($lo['tx_hash'], 0, 8) . '...' . substr($lo['tx_hash'], -6); ?>
                                                    </span>
                                                    <!-- NÚT COPY NHANH MÃ HASH -->
                                                    <button class="btn-copy" onclick="copyToClipboard('<?php echo htmlspecialchars($lo['tx_hash']); ?>')" title="Copy mã TxHash đầy đủ">
                                                        <i class="fa-regular fa-copy"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span style="color: var(--gray-300);">Chưa có</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-edit" onclick='openEditModal(<?php echo json_encode($lo); ?>)'>
                                                    <i class="fa-solid fa-pen-to-square"></i> Sửa
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- PHÂN TRANG -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination-container">
                            <div style="font-size: 13px; color: var(--gray-500);">
                                Hiển thị <?php echo count($lo_thuoc_list); ?> / <?php echo $total_records; ?> lô thuốc
                            </div>
                            <div class="pagination">
                                <!-- Nút Trước -->
                                <a href="?page=<?php echo $page - 1; ?>&s=<?php echo urlencode($search); ?>&id_thuoc=<?php echo $filter_thuoc; ?>&status=<?php echo $filter_status; ?>"
                                    class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </a>

                                <!-- Các con số trang -->
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&s=<?php echo urlencode($search); ?>&id_thuoc=<?php echo $filter_thuoc; ?>&status=<?php echo $filter_status; ?>"
                                        class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <!-- Nút Sau -->
                                <a href="?page=<?php echo $page + 1; ?>&s=<?php echo urlencode($search); ?>&id_thuoc=<?php echo $filter_thuoc; ?>&status=<?php echo $filter_status; ?>"
                                    class="page-link <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <!-- MODAL FORM PHÁT HÀNH / CHỈNH SỬA -->
    <div class="modal-overlay hidden" id="modalForm">
        <div class="modal-box">
            <div class="modal-head">
                <h2 id="modalTitle">Phát hành lô thuốc mới lên Sổ cái Blockchain</h2>
                <button class="modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <form id="loThuocForm">
                    <input type="hidden" id="form_mode" value="create">

                    <div class="form-grid">
                        <div class="form-field">
                            <label>Chọn loại thuốc sản xuất <span style="color:red;">*</span></label>
                            <select id="id_thuoc" required>
                                <option value="">-- Chọn thuốc --</option>
                                <?php foreach ($thuoc_options as $t): ?>
                                    <option value="<?php echo $t['id_thuoc']; ?>"><?php echo htmlspecialchars($t['ten_thuoc']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Mã số lô thuốc <span style="color:red;">*</span></label>
                            <input type="text" id="ma_lo" placeholder="Ví dụ: LO-2026-09" required>
                        </div>
                        <div class="form-field">
                            <label>Mã tra cứu chuỗi (QR) <span style="color:red;">*</span></label>
                            <input type="text" id="ma_tra_cuu" placeholder="Ví dụ: QR-PHARMA-001" required>
                        </div>
                        <div class="form-field">
                            <label>Giá nhập kho (đ) <span style="color:red;">*</span></label>
                            <input type="number" id="gia_nhap" required>
                        </div>
                        <div class="form-field">
                            <label>Công ty Đăng ký <span style="color:red;">*</span></label>
                            <select id="id_cty_dang_ky" required>
                                <option value="">-- Chọn đơn vị --</option>
                                <?php foreach ($doanh_nghiep_list as $dn): ?>
                                    <?php if ($dn['loai_hinh'] === 'DangKy' || $dn['loai_hinh'] === 'CaHai'): ?>
                                        <option value="<?php echo $dn['id_doanh_nghiep']; ?>"><?php echo htmlspecialchars($dn['ten_doanh_nghiep']); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Nhà máy sản xuất <span style="color:red;">*</span></label>
                            <select id="id_cty_san_xuat" required>
                                <option value="">-- Chọn nhà máy --</option>
                                <?php foreach ($doanh_nghiep_list as $dn): ?>
                                    <?php if ($dn['loai_hinh'] === 'SanXuat' || $dn['loai_hinh'] === 'CaHai'): ?>
                                        <option value="<?php echo $dn['id_doanh_nghiep']; ?>"><?php echo htmlspecialchars($dn['ten_doanh_nghiep']); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Ngày sản xuất <span style="color:red;">*</span></label>
                            <input type="date" id="ngay_san_xuat" required>
                        </div>
                        <div class="form-field">
                            <label>Hạn sử dụng thuốc <span style="color:red;">*</span></label>
                            <input type="date" id="han_su_dung" required>
                        </div>
                        <div class="form-field">
                            <label>Số lượng đóng gói <span style="color:red;">*</span></label>
                            <input type="number" id="so_luong_ton" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-foot">
                <button class="btn btn-ghost" onclick="closeModal()">Hủy</button>
                <button class="btn btn-primary" onclick="publishToBlockchain()">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Ký duyệt &amp; Đẩy lên Blockchain
                </button>
            </div>
        </div>
    </div>

    <!-- JS TƯƠNG TÁC BLOCKCHAIN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ethers/6.7.0/ethers.umd.min.js"></script>
    <script src="../js/blockchain-tracker.js"></script>

    <script>
        // HÀM COPY MÃ TXHASH VÀO CLIPBOARD
        function copyToClipboard(text) {
            if (!text) return;
            navigator.clipboard.writeText(text).then(() => {
                alert("Đã sao chép mã TxHash vào bộ nhớ tạm:\n" + text);
            }).catch(err => {
                console.error("Lỗi khi sao chép:", err);
            });
        }

        function openCreateModal() {
            document.getElementById('form_mode').value = 'create';
            document.getElementById('modalTitle').innerText = 'Phát hành lô thuốc mới lên Sổ cái Blockchain';
            document.getElementById('loThuocForm').reset();

            const maTraCuuInput = document.getElementById('ma_tra_cuu');
            maTraCuuInput.disabled = false;
            document.getElementById('modalForm').classList.remove('hidden');
        }

        function openEditModal(lo) {
            document.getElementById('form_mode').value = 'edit';
            document.getElementById('modalTitle').innerText = 'Chỉnh sửa lô thuốc & Cập nhật Blockchain';

            document.getElementById('id_thuoc').value = lo.id_thuoc;
            document.getElementById('ma_lo').value = lo.ma_lo;
            document.getElementById('ma_tra_cuu').value = lo.ma_tra_cuu;
            document.getElementById('gia_nhap').value = lo.gia_nhap;
            document.getElementById('id_cty_dang_ky').value = lo.id_cty_dang_ky;
            document.getElementById('id_cty_san_xuat').value = lo.id_cty_san_xuat;
            document.getElementById('ngay_san_xuat').value = lo.ngay_san_xuat;
            document.getElementById('han_su_dung').value = lo.han_su_dung;
            document.getElementById('so_luong_ton').value = lo.so_luong_ton;

            const maTraCuuInput = document.getElementById('ma_tra_cuu');
            maTraCuuInput.disabled = true;
            document.getElementById('modalForm').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('modalForm').classList.add('hidden');
        }

        async function publishToBlockchain() {
            if (!userAddress) {
                alert("Bạn cần phải kết nối ví MetaMask trước!");
                return;
            }

            const mode = document.getElementById('form_mode').value;
            const id_thuoc = document.getElementById('id_thuoc').value;
            const ma_lo = document.getElementById('ma_lo').value;
            const ma_tra_cuu = document.getElementById('ma_tra_cuu').value;
            const id_cty_dang_ky = document.getElementById('id_cty_dang_ky').value;
            const id_cty_san_xuat = document.getElementById('id_cty_san_xuat').value;
            const ngay_san_xuat = document.getElementById('ngay_san_xuat').value;
            const han_su_dung = document.getElementById('han_su_dung').value;
            const so_luong_ton = document.getElementById('so_luong_ton').value;
            const gia_nhap = document.getElementById('gia_nhap').value;

            if (!id_thuoc || !ma_lo || !ma_tra_cuu || !id_cty_dang_ky || !id_cty_san_xuat || !ngay_san_xuat || !han_su_dung || !so_luong_ton || !gia_nhap) {
                alert("Vui lòng nhập đầy đủ tất cả các trường dữ liệu!");
                return;
            }

            try {
                alert("Hệ thống chuẩn bị gọi MetaMask ký duyệt, vui lòng xác nhận giao dịch trên cửa sổ ví...");

                let txHash;
                if (mode === 'create') {
                    txHash = await registerBatchOnBlockchain(ma_tra_cuu, ma_lo, id_thuoc, id_cty_dang_ky, id_cty_san_xuat, han_su_dung);
                } else {
                    txHash = await updateBatchOnBlockchain(ma_tra_cuu, ma_lo, id_thuoc, id_cty_dang_ky, id_cty_san_xuat, han_su_dung);
                }

                alert("Giao dịch Blockchain thành công!\nTxHash mới: " + txHash + "\nĐang đồng bộ thông tin vào MySQL...");

                const formData = new FormData();
                formData.append('action', mode === 'create' ? 'save_lo_thuoc' : 'update_lo_thuoc');
                formData.append('id_thuoc', id_thuoc);
                formData.append('ma_lo', ma_lo);
                formData.append('ma_tra_cuu', ma_tra_cuu);
                formData.append('tx_hash', txHash);
                formData.append('id_cty_dang_ky', id_cty_dang_ky);
                formData.append('id_cty_san_xuat', id_cty_san_xuat);
                formData.append('ngay_san_xuat', ngay_san_xuat);
                formData.append('han_su_dung', han_su_dung);
                formData.append('so_luong_ton', so_luong_ton);
                formData.append('gia_nhap', gia_nhap);

                const response = await fetch('quanLyLoThuoc.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    window.location.reload();
                } else {
                    alert("Lỗi khi lưu vào MySQL: " + result.message);
                }

            } catch (error) {
                console.error(error);
                alert("Lỗi giao dịch: " + (error.reason || error.message || "Từ chối ký duyệt!"));
            }
        }
    </script>
</body>

</html>