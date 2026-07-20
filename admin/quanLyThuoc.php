<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
require_once '../config/config.php';

$conn = getDbConnection(); 

// Lấy danh sách thuốc
$stmt = $conn->prepare("SELECT * FROM Thuoc ORDER BY ngay_tao DESC");
$stmt->execute();
$thuoc_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <!-- [GIỮ NGUYÊN CÁC THẺ <head> VÀ STYLE TỪ DASHBOARD.PHP] -->
    <meta charset="UTF-8">
    <title>PharmaChain — Quản lý thuốc</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
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

        input,
        select,
        textarea {
            font-family: inherit;
        }

        .app {
            display: flex;
            min-height: 100vh;
        }

        /* ===== SIDEBAR ===== */
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

        /* ===== MAIN ===== */
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

        /* ===== TABLE ===== */
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
            letter-spacing: .4px;
            text-transform: uppercase;
            color: var(--gray-500);
            background: var(--gray-50);
            padding: 15px 16px;
            border-bottom: 1px solid var(--gray-200);
            white-space: nowrap;
        }

        tbody td {
            padding: 15px 16px;
            border-bottom: 1px solid var(--gray-100);
            font-size: 13.5px;
            color: var(--gray-900);
            vertical-align: middle;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: var(--gray-50);
        }

        .cell-strong {
            font-weight: 600;
            color: var(--gray-900);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11.3px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            letter-spacing: .2px;
        }

        .badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .badge-success {
            background: var(--green-50);
            color: var(--green-700);
        }

        .badge-fail {
            background: var(--red-50);
            color: var(--red-600);
        }

        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: var(--gray-500);
        }

        /* Thêm style nút ví kết nối Metamask dựa theo hệ thống CSS có sẵn */
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

        .btn-wallet:hover {
            background: var(--green-50);
        }

        .btn-wallet.connected {
            border-color: var(--blue-600);
            color: var(--blue-600);
            background: var(--blue-50);
        }

        /* Đèn logo viên thuốc chéo gốc */
        .icon-brand {
            width: 18px;
            height: 18px;
            border: 2px solid #fff;
            border-radius: 9px;
            transform: rotate(-45deg);
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
    </style>
</head>

<body>
    <div class="app">
        <!-- Sidebar giữ nguyên -->
        <aside class="sidebar">
            <!-- Logo và Menu tương tự dashBoard.php -->
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
                <a class="nav-item" href="dashBoard.php"><i class="fa-solid fa-chart-pie"></i> Tổng quan</a>
                <a class="nav-item active" href="quanLyThuoc.php"><i class="fa-solid fa-pills"></i> Quản lý thuốc</a>
                <a class="nav-item" href="quanLyLoThuoc.php"><i class="fa-solid fa-boxes-stacked"></i> Quản lý lô
                    thuốc</a>
            </nav>
            <a class="logout-link" href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
        </aside>

        <main class="main">
            <header class="topbar">
                <div class="page-heading">
                    <div class="icon-wrap"><i class="fa-solid fa-pills"></i></div>
                    <div class="page-title">Quản lý thuốc</div>
                </div>
                <button class="btn btn-primary"><i class="fa-solid fa-plus"></i> Thêm thuốc mới</button>
            </header>

            <section class="content">
                <div class="table-card">
                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tên thuốc</th>
                                    <th>Danh mục</th>
                                    <th>Hàm lượng</th>
                                    <th>Giá bán</th>
                                    <th>Phân loại</th>
                                    <th style="text-align:right;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($thuoc_list as $row): ?>
                                <tr>
                                    <td class="cell-strong">
                                        <?php echo htmlspecialchars($row['ten_thuoc']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($row['danh_muc']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($row['ham_luong']); ?>
                                    </td>
                                    <td>
                                        <?php echo number_format($row['gia_ban']); ?> đ
                                    </td>
                                    <td>
                                        <?php echo $row['yeu_cau_ke_don']; ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <a href="chiTietThuoc.php?id=<?php echo $row['id_thuoc']; ?>"
                                            class="action-btn view">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
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
</body>

</html>