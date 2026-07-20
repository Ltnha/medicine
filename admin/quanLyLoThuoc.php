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

// 3. XỬ LÝ API AJAX (Ghi nhận lô thuốc sau khi đã có tx_hash từ Blockchain)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_lo_thuoc') {
    header('Content-Type: application/json');
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
        $ma_admin = $_SESSION['ma_admin'] ?? 1; // Lấy ID admin từ session

        $insert_query = "INSERT INTO LoThuoc (id_thuoc, ma_lo, ma_tra_cuu, tx_hash, trang_thai_blockchain, ma_admin, id_cty_dang_ky, id_cty_san_xuat, ngay_san_xuat, han_su_dung, so_luong_ton, gia_nhap) 
                         VALUES (?, ?, ?, ?, 'confirmed', ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->execute([$id_thuoc, $ma_lo, $ma_tra_cuu, $tx_hash, $ma_admin, $id_cty_dang_ky, $id_cty_san_xuat, $ngay_san_xuat, $han_su_dung, $so_luong_ton, $gia_nhap]);

        echo json_encode(['success' => true, 'message' => 'Lưu lô thuốc lên hệ thống và Blockchain thành công!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi MySQL Backend: ' . $e->getMessage()]);
    }
    exit();
}

// 4. ĐỌC DỮ LIỆU ĐỂ HIỂN THỊ LÊN GIAO DIỆN
try {
    // Lấy danh sách lô thuốc kèm tên thuốc tương ứng
    $lo_stmt = $conn->prepare("SELECT l.*, t.ten_thuoc FROM LoThuoc l JOIN Thuoc t ON l.id_thuoc = t.id_thuoc ORDER BY l.ngay_nhap_kho DESC");
    $lo_stmt->execute();
    $lo_thuoc_list = $lo_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy danh sách thuốc đang hoạt động để làm dropdown chọn trong Modal
    $thuoc_stmt = $conn->prepare("SELECT id_thuoc, ten_thuoc FROM Thuoc WHERE trang_thai = 1");
    $thuoc_stmt->execute();
    $thuoc_options = $thuoc_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy danh sách doanh nghiệp đối tác (Đăng ký / Sản xuất)
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
    <!-- Thư viện biểu tượng Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Thư viện Ethers.js để tương tác với mạng Blockchain và MetaMask -->
    <script src="https://cdn.jsdelivr.net/npm/ethers@5.7.2/dist/ethers.umd.min.js"></script>

    <style>
        /* Bê nguyên hệ thống CSS gốc của bạn từ quanLyThuoc.html */
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
            --red-100: #fbdad8;
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
            --radius-md: 12px;
            --radius-sm: 8px;
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
            -webkit-font-smoothing: antialiased;
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
            flex-shrink: 0;
        }

        .brand-name {
            font-weight: 800;
            font-size: 15.5px;
            color: #fff;
            letter-spacing: -.2px;
            line-height: 1.1;
        }

        .brand-sub {
            font-size: 11px;
            color: #7c869a;
            margin-top: 2px;
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
            font-weight: 500;
            color: var(--side-text);
            position: relative;
            transition: .15s;
        }

        .nav-item i {
            width: 17px;
            text-align: center;
            flex-shrink: 0;
            opacity: .85;
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

        .nav-item.active::before {
            content: '';
            position: absolute;
            left: -14px;
            top: 8px;
            bottom: 8px;
            width: 4px;
            border-radius: 0 4px 4px 0;
            background: var(--green-600);
        }

        .nav-item.active i {
            opacity: 1;
            color: var(--green-600);
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

        .logout-link:hover {
            background: rgba(213, 54, 47, .1);
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
            gap: 16px;
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
            flex-shrink: 0;
        }

        .page-heading .icon-wrap i {
            font-size: 18px;
        }

        .page-title {
            font-size: 19.5px;
            font-weight: 800;
            letter-spacing: -.3px;
        }

        .content {
            padding: 26px 32px 60px;
            max-width: 1440px;
            width: 100%;
            margin: 0 auto;
        }

        .toolbar-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
            padding: 16px 18px;
            margin-bottom: 18px;
        }

        .toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 13.5px;
            font-weight: 600;
            border-radius: 10px;
            padding: 9.5px 16px;
            border: 1px solid transparent;
            white-space: nowrap;
            transition: .15s;
        }

        .btn-primary {
            background: var(--green-700);
            color: #fff;
            box-shadow: 0 4px 10px -4px rgba(19, 122, 74, .6);
        }

        .btn-primary:hover {
            background: var(--green-900);
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
            min-width: 1020px;
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

        .badge-active {
            background: var(--green-50);
            color: var(--green-700);
        }

        .badge-lowstock {
            background: var(--orange-50);
            color: var(--orange-600);
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
            transition: .15s;
        }

        .btn-wallet.connected {
            border-color: var(--blue-600);
            color: var(--blue-600);
            background: var(--blue-50);
        }

        .icon-brand {
            width: 18px;
            height: 18px;
            border: 2px solid #fff;
            border-radius: 9px;
            transform: rotate(-45deg);
            position: relative;
        }

        .icon-brand::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #fff;
            transform: translateY(-50%);
        }

        /* MODAL CHUẨN ĐÚNG THEO FILE CŨ */
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
            max-width: 760px;
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
            grid-template-columns: 1fr 1fr;
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
        }

        .form-field input:focus,
        .form-field select:focus {
            border-color: var(--green-600);
            box-shadow: 0 0 0 3px var(--green-50);
        }
    </style>
</head>

<body>

    <div class="app">
        <!-- ===== SIDEBAR ===== -->
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-logo">
                    <div class="icon icon-brand"></div>
                </div>
                <div>
                    <div class="brand-name">PharmaChain</div>
                    <div class="brand-sub">Hệ thống quản trị</div>
                </div>
            </div>
            <nav class="nav-group">
                <div class="nav-label">Điều hướng</div>
                <a class="nav-item" href="dashBoard.php"><i class="fa-solid fa-chart-pie"></i> Tổng quan</a>
                <a class="nav-item" href="quanLyThuoc.php"><i class="fa-solid fa-pills"></i> Quản lý thuốc</a>
                <a class="nav-item active" href="quanLyLoThuoc.php"><i class="fa-solid fa-boxes-stacked"></i> Quản lý lô
                    thuốc</a>
            </nav>
            <a class="logout-link" href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
        </aside>

        <!-- ===== MAIN CONTENT ===== -->
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
                <div class="toolbar-card">
                    <div class="toolbar">
                        <div style="font-size: 14px; color: var(--gray-500);">Dữ liệu đồng bộ sổ cái phi tập trung</div>
                        <button class="btn btn-primary" onclick="openModal()" style="margin-left: auto;">
                            <i class="fa-solid fa-plus"></i> Phát hành lô thuốc mới
                        </button>
                    </div>
                </div>

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
                                    <th>Mã Giao Dịch Blockchain (TxHash)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lo_thuoc_list as $lo): ?>
                                    <tr>
                                        <td class="cell-strong">
                                            <?php echo htmlspecialchars($lo['ma_lo']); ?>
                                        </td>
                                        <td class="cell-strong" style="color: var(--green-700);">
                                            <?php echo htmlspecialchars($lo['ten_thuoc']); ?>
                                        </td>
                                        <td class="cell-strong" style="font-family: monospace;">
                                            <?php echo htmlspecialchars($lo['ma_tra_cuu']); ?>
                                        </td>
                                        <td>
                                            <?php echo $lo['ngay_san_xuat']; ?>
                                        </td>
                                        <td>
                                            <?php echo $lo['han_su_dung']; ?>
                                        </td>
                                        <td>
                                            <?php echo number_format($lo['so_luong_ton']); ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-active">Đã xác thực</span>
                                        </td>
                                        <td style="font-family: monospace; font-size: 11.5px; color: var(--gray-500);"
                                            title="<?php echo $lo['tx_hash']; ?>">
                                            <?php echo substr($lo['tx_hash'], 0, 10) . '...' . substr($lo['tx_hash'], -8); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- ===== MODAL PHÁT HÀNH LÔ THUỐC LÊN BLOCKCHAIN ===== -->
    <div class="modal-overlay hidden" id="modalForm">
        <div class="modal-box">
            <div class="modal-head">
                <h2>Phát hành lô thuốc mới lên Sổ cái Blockchain</h2>
                <button class="modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <form id="loThuocForm">
                    <div class="form-grid">
                        <div class="form-field">
                            <label>Chọn loại thuốc sản xuất <span style="color:red;">*</span></label>
                            <select id="id_thuoc" required>
                                <option value="">-- Chọn thuốc --</option>
                                <?php foreach ($thuoc_options as $t): ?>
                                    <option value="<?php echo $t['id_thuoc']; ?>">
                                        <?php echo htmlspecialchars($t['ten_thuoc']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Mã số lô thuốc <span style="color:red;">*</span></label>
                            <input type="text" id="ma_lo" placeholder="Ví dụ: LO-2026-09" required>
                        </div>
                        <div class="form-field">
                            <label>Mã tra cứu chuỗi (In mã QR) <span style="color:red;">*</span></label>
                            <input type="text" id="ma_tra_cuu" placeholder="Ví dụ: QR-PHARMA-001" required>
                        </div>
                        <div class="form-field">
                            <label>Giá nhập kho (đ) <span style="color:red;">*</span></label>
                            <input type="number" id="gia_nhap" required>
                        </div>
                        <div class="form-field">
                            <label>Công ty Đăng ký nhận diện <span style="color:red;">*</span></label>
                            <select id="id_cty_dang_ky" required>
                                <option value="">-- Chọn đơn vị --</option>
                                <?php foreach ($doanh_nghiep_list as $dn): ?>
                                    <?php if ($dn['loai_hinh'] === 'DangKy' || $dn['loai_hinh'] === 'CaHai'): ?>
                                        <option value="<?php echo $dn['id_doanh_nghiep']; ?>">
                                            <?php echo htmlspecialchars($dn['ten_doanh_nghiep']); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Nhà máy chịu trách nhiệm sản xuất <span style="color:red;">*</span></label>
                            <select id="id_cty_san_xuat" required>
                                <option value="">-- Chọn nhà máy --</option>
                                <?php foreach ($doanh_nghiep_list as $dn): ?>
                                    <?php if ($dn['loai_hinh'] === 'SanXuat' || $dn['loai_hinh'] === 'CaHai'): ?>
                                        <option value="<?php echo $dn['id_doanh_nghiep']; ?>">
                                            <?php echo htmlspecialchars($dn['ten_doanh_nghiep']); ?>
                                        </option>
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
                            <label>Số lượng đóng gói phát hành <span style="color:red;">*</span></label>
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
    <!-- Nhúng thư viện Ethers.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ethers/6.7.0/ethers.umd.min.js"></script>
    <!-- Sau đó nhúng file xử lý logic tương tác contract -->
    <script src="../js/blockchain-tracker.js"></script>
    <!-- ===== BLOCKCHAIN WEB3 SCRIPT INTEGRATION ===== -->
    <script>
        // Khai báo cấu hình hợp đồng thông minh đã biên dịch từ ví dụ thư mục bin/
        const CONTRACT_ADDRESS = "0xe0FCdDCd026C179A638953a50fE900D68d903F4a"; // Thay địa chỉ Smart Contract của bạn vào đây
        const CONTRACT_ABI = [
            "function registerLot(string _maLo, string _maTraCuu, uint256 _idThuoc, uint256 _sl) public returns (bytes32)"
        ];

        let userAddress = null;

        window.addEventListener('load', async () => {
            if (window.ethereum) {
                const accounts = await window.ethereum.request({
                    method: 'eth_accounts'
                });
                if (accounts.length > 0) handleWalletConnected(accounts[0]);
            }
        });

        async function connectWallet() {
            if (window.ethereum) {
                try {
                    const accounts = await window.ethereum.request({
                        method: 'eth_requestAccounts'
                    });
                    handleWalletConnected(accounts[0]);
                } catch (error) {
                    alert("Kết nối ví thất bại!");
                }
            } else {
                alert("Vui lòng cài đặt tiện ích MetaMask!");
            }
        }

        function handleWalletConnected(address) {
            userAddress = address;
            document.getElementById('walletAddressText').innerText = address.substring(0, 6) + "..." + address.substring(address.length - 4);
            document.getElementById('connectWalletBtn').classList.add('connected');
        }

        function openModal() {
            document.getElementById('modalForm').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('modalForm').classList.add('hidden');
        }

        // LUỒNG KÝ DUYỆT ĐẨY DỮ LIỆU LÊN CHUỖI KHÉP KÍN
        async function publishToBlockchain() {
            if (!userAddress) {
                alert("Bạn cần phải kết nối ví MetaMask trước khi phát hành lên chuỗi dữ liệu!");
                return;
            }

            // Lấy thông tin từ form
            const id_thuoc = document.getElementById('id_thuoc').value;
            const ma_lo = document.getElementById('ma_lo').value;
            const ma_tra_cuu = document.getElementById('ma_tra_cuu').value;
            const id_cty_dang_ky = document.getElementById('id_cty_dang_ky').value;
            const id_cty_san_xuat = document.getElementById('id_cty_san_xuat').value;
            const ngay_san_xuat = document.getElementById('ngay_san_xuat').value;
            const han_su_dung = document.getElementById('han_su_dung').value;
            const so_luong_ton = document.getElementById('so_luong_ton').value;
            const gia_nhap = document.getElementById('gia_nhap').value;

            if (!id_thuoc || !ma_lo || !ma_tra_cuu || !ngay_san_xuat || !han_su_dung || !so_luong_ton || !gia_nhap) {
                alert("Vui lòng nhập đầy đủ tất cả các trường dữ liệu!");
                return;
            }

            try {
                // Khởi tạo thư viện Ethers kết nối MetaMask làm bằng chứng giao dịch
                const provider = new ethers.providers.Web3Provider(window.ethereum);
                const signer = provider.getSigner();
                const contract = new ethers.Contract(CONTRACT_ADDRESS, CONTRACT_ABI, signer);

                alert("Hệ thống chuẩn bị gọi MetaMask ký duyệt giao dịch, vui lòng xác nhận trên cửa sổ ví...");

                // Gọi hàm ghi dữ liệu vào Smart Contract phi tập trung
                const tx = await contract.registerLot(ma_lo, ma_tra_cuu, id_thuoc, so_luong_ton);

                alert("Giao dịch đang được đẩy lên khối chuỗi. Vui lòng đợi trong giây lát... TxHash: " + tx.hash);

                // Chờ giao dịch đào xong thành công
                await tx.wait();

                // ĐỒNG BỘ DỮ LIỆU: Đẩy ngược tx_hash về lưu MySQL thông qua cơ chế AJAX truyền ngầm
                const formData = new FormData();
                formData.append('action', 'save_lo_thuoc');
                formData.append('id_thuoc', id_thuoc);
                formData.append('ma_lo', ma_lo);
                formData.append('ma_tra_cuu', ma_tra_cuu);
                formData.append('tx_hash', tx.hash); // Lưu bằng chứng chuỗi bảo mật toàn vẹn dữ liệu
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
                    window.location.reload(); // Tải lại trang để cập nhật bảng mới nhất
                } else {
                    alert("Lỗi khi lưu vào MySQL: " + result.message);
                }

            } catch (error) {
                console.error(error);
                alert("Xảy ra lỗi trong quá trình ký duyệt giao dịch Blockchain!");
            }
        }
    </script>
</body>

</html>