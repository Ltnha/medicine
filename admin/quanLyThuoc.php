<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
require_once '../config/config.php';

$conn = getDbConnection(); 

$msg_success = "";
$msg_error = "";

// 1. XỬ LÝ AJAX: LẤY CHI TIẾT THUỐC (Cho chức năng Xem & Sửa)
if (isset($_GET['action']) && $_GET['action'] === 'get_detail' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    try {
        $stmt = $conn->prepare("SELECT * FROM Thuoc WHERE id_thuoc = ?");
        $stmt->execute([intval($_GET['id'])]);
        $thuoc = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($thuoc) {
            $thuoc['gia_ban_format'] = number_format($thuoc['gia_ban']) . ' đ';
            echo json_encode(['success' => true, 'data' => $thuoc]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin thuốc.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// 2. XỬ LÝ ACTION: THÊM THUỐC
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_thuoc') {
    $ten_thuoc = trim($_POST['ten_thuoc']);
    $danh_muc = trim($_POST['danh_muc']);
    $ham_luong = trim($_POST['ham_luong']);
    $gia_ban = floatval($_POST['gia_ban']);
    $yeu_cau_ke_don = trim($_POST['yeu_cau_ke_don']);
    $thanh_phan = trim($_POST['thanh_phan']);
    $cong_dung = trim($_POST['cong_dung']);
    $dang_bao_che = trim($_POST['dang_bao_che']);
    $don_vi_tinh = trim($_POST['don_vi_tinh']);

    if (empty($ten_thuoc) || empty($danh_muc) || $gia_ban <= 0) {
        $msg_error = "Vui lòng điền đầy đủ các thông tin bắt buộc!";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO Thuoc (ten_thuoc, danh_muc, ham_luong, gia_ban, yeu_cau_ke_don, thanh_phan, cong_dung, dang_bao_che, don_vi_tinh) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$ten_thuoc, $danh_muc, $ham_luong, $gia_ban, $yeu_cau_ke_don, $thanh_phan, $cong_dung, $dang_bao_che, $don_vi_tinh]);
            $msg_success = "Thêm thuốc mới thành công!";
        } catch (PDOException $e) {
            $msg_error = "Lỗi: " . $e->getMessage();
        }
    }
}

// 3. XỬ LÝ ACTION: CẬP NHẬT THUỐC 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_thuoc') {
    $id_thuoc = intval($_POST['id_thuoc']);
    $ten_thuoc = trim($_POST['ten_thuoc']);
    $danh_muc = trim($_POST['danh_muc']);
    $ham_luong = trim($_POST['ham_luong']);
    $gia_ban = floatval($_POST['gia_ban']);
    $yeu_cau_ke_don = trim($_POST['yeu_cau_ke_don']);
    $thanh_phan = trim($_POST['thanh_phan']);
    $cong_dung = trim($_POST['cong_dung']);
    $dang_bao_che = trim($_POST['dang_bao_che']);
    $don_vi_tinh = trim($_POST['don_vi_tinh']);

    if (empty($ten_thuoc) || empty($danh_muc) || $gia_ban <= 0) {
        $msg_error = "Vui lòng điền thông tin hợp lệ!";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE Thuoc SET ten_thuoc = ?, danh_muc = ?, ham_luong = ?, gia_ban = ?, yeu_cau_ke_don = ?, thanh_phan = ?, cong_dung = ?, dang_bao_che = ?, don_vi_tinh = ? WHERE id_thuoc = ?");
            $stmt->execute([$ten_thuoc, $danh_muc, $ham_luong, $gia_ban, $yeu_cau_ke_don, $thanh_phan, $cong_dung, $dang_bao_che, $don_vi_tinh, $id_thuoc]);
            $msg_success = "Cập nhật thông tin thuốc thành công!";
        } catch (PDOException $e) {
            $msg_error = "Lỗi sửa: " . $e->getMessage();
        }
    }
}

// 4. XỬ LÝ ACTION: XÓA THUỐC (ẨN BẰNG CÁCH ĐỔI TÊN)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_xoa = intval($_GET['id']);
    try {
        // Nối thêm chuỗi [DELETED] vào trước tên thuốc cũ
        $stmt = $conn->prepare("UPDATE Thuoc SET ten_thuoc = CONCAT('[DELETED] ', ten_thuoc) WHERE id_thuoc = ?");
        $stmt->execute([$id_xoa]);
        $msg_success = "Đã xóa thuốc khỏi danh sách thành công!";
    } catch (PDOException $e) {
        $msg_error = "Lỗi: " . $e->getMessage();
    }
}

// 5. XỬ LÝ TÌM KIẾM & LẤY DANH SÁCH (Lọc bỏ thuốc có chữ [DELETED])
$search_query = "";
$params = [];
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = trim($_GET['search']);
    // Thêm điều kiện: ten_thuoc NOT LIKE '[DELETED]%'
    $sql = "SELECT * FROM Thuoc WHERE (ten_thuoc NOT LIKE '[DELETED]%') AND (ten_thuoc LIKE ? OR danh_muc LIKE ?) ORDER BY ngay_tao DESC";
    $params = ["%$search_query%", "%$search_query%"];
} else {
    // Mặc định chỉ lấy các thuốc không có chữ [DELETED]
    $sql = "SELECT * FROM Thuoc WHERE ten_thuoc NOT LIKE '[DELETED]%' ORDER BY ngay_tao DESC";
}
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$thuoc_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>PharmaChain — Quản lý thuốc</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --green-900: #0f3d24; --green-700: #137a4a; --green-600: #189956; --green-50: #e9f7ef; --green-100: #d7f0e1;
            --orange-600: #c2650f; --orange-50: #fdf1e4; --red-600: #d5362f; --red-700: #b5271f; --red-50: #fdeceb; --red-100: #fbdad8;
            --blue-600: #2b5fd9; --blue-50: #eef4ff; --gray-900: #1c2430; --gray-700: #465066; --gray-500: #7c869a;
            --gray-300: #dbe0e8; --gray-200: #e9edf2; --gray-100: #f2f4f7; --gray-50: #f8f9fb; --white: #fff;
            --side-bg: #1b212c; --side-bg-2: #141922; --side-text: #aab3c5; --side-active: #232b38;
            --radius-lg: 18px; --radius-md: 12px; --radius-sm: 8px;
            --shadow-card: 0 1px 2px rgba(20, 30, 50, .04), 0 8px 24px -12px rgba(20, 30, 50, .10);
            --shadow-modal: 0 20px 60px -12px rgba(15, 30, 25, .35);
        }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background: var(--gray-50); color: var(--gray-900); }
        .app { display: flex; min-height: 100vh; }
        /* ===== SIDEBAR ĐỒNG BỘ 100% ===== */
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
        .main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 20px 32px; background: var(--white); border-bottom: 1px solid var(--gray-200); position: sticky; top: 0; z-index: 20; }
        .content { padding: 26px 32px 60px; max-width: 1440px; width: 100%; margin: 0 auto; }
        .table-card { background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius-lg); box-shadow: var(--shadow-card); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        thead th { text-align: left; font-size: 11px; font-weight: 700; color: var(--gray-500); background: var(--gray-50); padding: 15px 16px; border-bottom: 1px solid var(--gray-200); }
        tbody td { padding: 15px 16px; border-bottom: 1px solid var(--gray-100); font-size: 13.5px; }
        tbody tr:hover { background: var(--gray-50); }
        .cell-strong { font-weight: 600; }
        .badge { display: inline-flex; align-items: center; gap: 5px; font-size: 11.3px; font-weight: 700; padding: 4px 10px; border-radius: 20px; }
        .badge-success { background: var(--green-50); color: var(--green-700); }
        .badge-fail { background: var(--red-50); color: var(--red-600); }
        .btn-add-custom { background-color: var(--green-700); color: white; padding: 9.5px 16px; border-radius: 10px; font-weight: 600; font-size: 13.5px; }
        .btn-add-custom:hover { background-color: var(--green-600); }
        .modal-overlay { background-color: rgba(28, 36, 48, 0.5); backdrop-filter: blur(4px); }
    </style>
</head>

<body>
    <div class="app">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
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
                <a class="nav-item" href="dashBoard.php">
                    <i class="fa-solid fa-chart-pie"></i>
                    Tổng quan
                </a>
                <!-- Quản lý thuốc đang active ở trang này -->
                <a class="nav-item active" href="quanLyThuoc.php">
                    <i class="fa-solid fa-pills"></i>
                    Quản lý thuốc
                </a>
                <a class="nav-item" href="quanLyLoThuoc.php">
                    <i class="fa-solid fa-boxes-stacked"></i>
                    Quản lý lô thuốc
                </a>
            </nav>

            <a class="logout-link" href="../logout.php">
                <i class="fa-solid fa-right-from-bracket"></i>
                Đăng xuất
            </a>
        </aside>

        <main class="main">
            <header class="topbar">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-xl" style="background: var(--green-50); color: var(--green-700);"><i class="fa-solid fa-pills text-xl"></i></div>
                    <h2 class="text-xl font-bold">Quản lý danh mục thuốc</h2>
                </div>
                <button onclick="toggleModal('modalAddThuoc', true)" class="btn-add-custom"><i class="fa-solid fa-plus mr-1"></i> Thêm thuốc mới</button>
            </header>

            <section class="content">
                <!-- Bộ lọc & Thanh tìm kiếm -->
                <div class="mb-6 flex flex-col sm:flex-row gap-4 justify-between items-center bg-white p-4 rounded-2xl border border-gray-200 shadow-sm">
                    <form method="GET" action="" class="w-full sm:w-96 flex items-center relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 text-gray-400"></i>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                               placeholder="Tìm thuốc theo tên hoặc danh mục..." 
                               class="w-full pl-10 pr-20 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-emerald-600">
                        <?php if(!empty($search_query)): ?>
                            <a href="quanLyThuoc.php" class="absolute right-16 text-gray-400 hover:text-gray-600 text-xs">Xóa</a>
                        <?php endif; ?>
                        <button type="submit" class="absolute right-1.5 px-3 py-1 bg-gray-900 text-white rounded-lg text-xs font-semibold hover:bg-gray-800">Tìm</button>
                    </form>
                    <?php if(!empty($search_query)): ?>
                        <div class="text-xs text-gray-500 w-full text-left sm:text-right">Kết quả tìm kiếm cho: <strong>"<?php echo htmlspecialchars($search_query); ?>"</strong></div>
                    <?php endif; ?>
                </div>

                <!-- Thông báo trạng thái -->
                <?php if (!empty($msg_success)): ?>
                    <div class="mb-4 p-4 rounded-xl flex items-center gap-3" style="background: var(--green-50); color: var(--green-700); border: 1px solid var(--green-100);"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg_success); ?></div>
                <?php endif; ?>
                <?php if (!empty($msg_error)): ?>
                    <div class="mb-4 p-4 rounded-xl flex items-center gap-3" style="background: var(--red-50); color: var(--red-600); border: 1px solid var(--red-100);"><i class="fa-solid fa-circle-xmark"></i> <?php echo htmlspecialchars($msg_error); ?></div>
                <?php endif; ?>

                <!-- Bảng hiển thị -->
                <div class="table-card">
                    <div class="overflow-x-auto">
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
                                <?php if (empty($thuoc_list)): ?>
                                    <tr><td colspan="6" class="text-center py-10 text-gray-400">Không tìm thấy loại thuốc nào phù hợp.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($thuoc_list as $row): ?>
                                    <tr>
                                        <td class="cell-strong"><?php echo htmlspecialchars($row['ten_thuoc']); ?></td>
                                        <td><?php echo htmlspecialchars($row['danh_muc']); ?></td>
                                        <td><?php echo htmlspecialchars($row['ham_luong']); ?></td>
                                        <td><?php echo number_format($row['gia_ban']); ?> đ</td>
                                        <td>
                                            <?php if(trim($row['yeu_cau_ke_don']) === 'Kê đơn'): ?>
                                                <span class="badge badge-fail">Kê đơn</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">Không kê đơn</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:right;" class="space-x-2">
                                            <button onclick="showDetail(<?php echo $row['id_thuoc']; ?>)" class="text-blue-600 hover:text-blue-800 transition" title="Xem chi tiết"><i class="fa-solid fa-eye"></i></button>
                                            <button onclick="openEditModal(<?php echo $row['id_thuoc']; ?>)" class="text-amber-600 hover:text-amber-800 transition" title="Sửa"><i class="fa-solid fa-pen-to-square"></i></button>
                                            <a href="quanLyThuoc.php?action=delete&id=<?php echo $row['id_thuoc']; ?>" onclick="return confirm('Bạn chắc chắn muốn xóa thuốc này? Dữ liệu liên quan có thể ảnh hưởng.')" class="text-red-600 hover:text-red-800 transition" title="Xóa"><i class="fa-solid fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- ==========================================
         MODAL 1: THÊM THUỐC MỚI
         ========================================== -->
    <div id="modalAddThuoc" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 modal-overlay" onclick="toggleModal('modalAddThuoc', false)"></div>
        <div class="relative w-full max-w-lg p-6 bg-white shadow-2xl rounded-2xl z-10 mx-4 max-h-[90vh] overflow-y-auto" style="box-shadow: var(--shadow-modal);">
            <div class="flex justify-between items-center pb-4 mb-4 border-b border-gray-100">
                <h3 class="text-lg font-bold flex items-center gap-2" style="color: var(--gray-900);"><i class="fa-solid fa-circle-plus text-emerald-700"></i> Thêm thuốc mới vào danh mục</h3>
                <button onclick="toggleModal('modalAddThuoc', false)" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <form action="" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_thuoc">
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Tên thuốc <span class="text-red-500">*</span></label>
                    <input type="text" name="ten_thuoc" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-emerald-600" placeholder="Ví dụ: Hapacol 650 Extra">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Danh mục nhóm thuốc <span class="text-red-500">*</span></label>
                    <input type="text" name="danh_muc" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-emerald-600" placeholder="Thuốc hạ sốt, giảm đau...">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Hàm lượng</label>
                        <input type="text" name="ham_luong" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-emerald-600" placeholder="650mg">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Giá bán (đ) <span class="text-red-500">*</span></label>
                        <input type="number" name="gia_ban" min="0" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-emerald-600" placeholder="50000">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Dạng bào chế</label>
                        <input type="text" name="dang_bao_che" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-emerald-600" placeholder="Viên nén sủi...">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Đơn vị tính</label>
                        <input type="text" name="don_vi_tinh" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-emerald-600" placeholder="Viên / Hộp...">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Phân loại yêu cầu</label>
                    <select name="yeu_cau_ke_don" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-emerald-600 bg-white">
                        <option value="Không kê đơn">Không kê đơn</option>
                        <option value="Kê đơn">Kê đơn</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Thành phần</label>
                    <textarea name="thanh_phan" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-emerald-600" placeholder="Paracetamol..."></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Công dụng</label>
                    <textarea name="cong_dung" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-emerald-600" placeholder="Hạ sốt, giảm đau cơ..."></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" onclick="toggleModal('modalAddThuoc', false)" class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-50">Hủy</button>
                    <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm font-semibold dynamic-bg" style="background-color: var(--green-700);">Lưu lại</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==========================================
         MODAL 2: XEM CHI TIẾT
         ========================================== -->
    <div id="modalDetailThuoc" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 modal-overlay" onclick="toggleModal('modalDetailThuoc', false)"></div>
        <div class="relative w-full max-w-lg p-6 bg-white shadow-2xl rounded-2xl z-10 mx-4 max-h-[90vh] overflow-y-auto" style="box-shadow: var(--shadow-modal);">
            <div class="flex justify-between items-center pb-4 mb-4 border-b border-gray-100">
                <h3 class="text-lg font-bold flex items-center gap-2 text-blue-600"><i class="fa-solid fa-file-medical"></i> Chi tiết thông tin thuốc</h3>
                <button onclick="toggleModal('modalDetailThuoc', false)" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <div class="space-y-3">
                <div><span class="block text-xs font-bold text-gray-400 uppercase">Tên thuốc</span><div id="det_ten_thuoc" class="text-base font-semibold bg-gray-50 px-3 py-2 rounded-lg border">--</div></div>
                <div><span class="block text-xs font-bold text-gray-400 uppercase">Danh mục</span><div id="det_danh_muc" class="text-sm bg-gray-50 px-3 py-2 rounded-lg border">--</div></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><span class="block text-xs font-bold text-gray-400 uppercase">Hàm lượng</span><div id="det_ham_luong" class="text-sm bg-gray-50 px-3 py-2 rounded-lg border">--</div></div>
                    <div><span class="block text-xs font-bold text-gray-400 uppercase">Giá bán</span><div id="det_gia_ban" class="text-sm font-semibold text-emerald-700 bg-gray-50 px-3 py-2 rounded-lg border">--</div></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><span class="block text-xs font-bold text-gray-400 uppercase">Dạng bào chế</span><div id="det_dang_bao_che" class="text-sm bg-gray-50 px-3 py-2 rounded-lg border">--</div></div>
                    <div><span class="block text-xs font-bold text-gray-400 uppercase">Đơn vị tính</span><div id="det_don_vi_tinh" class="text-sm bg-gray-50 px-3 py-2 rounded-lg border">--</div></div>
                </div>
                <div><span class="block text-xs font-bold text-gray-400 uppercase">Yêu cầu đơn thuốc</span><div id="det_yeu_cau" class="pt-1">--</div></div>
                <div><span class="block text-xs font-bold text-gray-400 uppercase">Thành phần</span><div id="det_thanh_phan" class="text-sm bg-gray-50 px-3 py-2 rounded-lg border border-gray-100 whitespace-pre-line">--</div></div>
                <div><span class="block text-xs font-bold text-gray-400 uppercase">Công dụng</span><div id="det_cong_dung" class="text-sm bg-gray-50 px-3 py-2 rounded-lg border border-gray-100 whitespace-pre-line">--</div></div>
            </div>
            <div class="flex justify-end pt-4 mt-4 border-t"><button onclick="toggleModal('modalDetailThuoc', false)" class="px-5 py-2 text-white rounded-lg text-sm font-semibold" style="background-color: var(--gray-700);">Đóng</button></div>
        </div>
    </div>

    <!-- ==========================================
         MODAL 3: SỬA/CẬP NHẬT THÔNG TIN THUỐC
         ========================================== -->
    <div id="modalEditThuoc" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 modal-overlay" onclick="toggleModal('modalEditThuoc', false)"></div>
        <div class="relative w-full max-w-lg p-6 bg-white shadow-2xl rounded-2xl z-10 mx-4 max-h-[90vh] overflow-y-auto" style="box-shadow: var(--shadow-modal);">
            <div class="flex justify-between items-center pb-4 mb-4 border-b border-gray-100">
                <h3 class="text-lg font-bold flex items-center gap-2 text-amber-600"><i class="fa-solid fa-pen-to-square"></i> Cập nhật thông tin thuốc</h3>
                <button onclick="toggleModal('modalEditThuoc', false)" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <form action="" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit_thuoc">
                <input type="hidden" name="id_thuoc" id="edit_id_thuoc">
                
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Tên thuốc <span class="text-red-500">*</span></label>
                    <input type="text" name="ten_thuoc" id="edit_ten_thuoc" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Danh mục nhóm thuốc <span class="text-red-500">*</span></label>
                    <input type="text" name="danh_muc" id="edit_danh_muc" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Hàm lượng</label>
                        <input type="text" name="ham_luong" id="edit_ham_luong" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Giá bán (đ) <span class="text-red-500">*</span></label>
                        <input type="number" name="gia_ban" id="edit_gia_ban" min="0" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Dạng bào chế</label>
                        <input type="text" name="dang_bao_che" id="edit_dang_bao_che" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Đơn vị tính</label>
                        <input type="text" name="don_vi_tinh" id="edit_don_vi_tinh" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Phân loại yêu cầu</label>
                    <select name="yeu_cau_ke_don" id="edit_yeu_cau" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-amber-500 bg-white">
                        <option value="Không kê đơn">Không kê đơn</option>
                        <option value="Kê đơn">Kê đơn</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Thành phần</label>
                    <textarea name="thanh_phan" id="edit_thanh_phan" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-amber-500"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Công dụng</label>
                    <textarea name="cong_dung" id="edit_cong_dung" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-amber-500"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" onclick="toggleModal('modalEditThuoc', false)" class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-50">Hủy</button>
                    <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm font-semibold" style="background-color: var(--orange-600);">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Script quản lý JavaScript -->
    <script>
        function toggleModal(modalId, show) {
            const modal = document.getElementById(modalId);
            if (show) modal.classList.remove('hidden');
            else modal.classList.add('hidden');
        }

        // Xem Chi Tiết bằng Fetch API
        function showDetail(id) {
            fetch(`quanLyThuoc.php?action=get_detail&id=${id}`)
                .then(r => r.json())
                .then(res => {
                    if(res.success) {
                        const d = res.data;
                        document.getElementById('det_ten_thuoc').innerText = d.ten_thuoc;
                        document.getElementById('det_danh_muc').innerText = d.danh_muc;
                        document.getElementById('det_ham_luong').innerText = d.ham_luong || 'N/A';
                        document.getElementById('det_gia_ban').innerText = d.gia_ban_format;
                        document.getElementById('det_dang_bao_che').innerText = d.dang_bao_che || 'N/A';
                        document.getElementById('det_don_vi_tinh').innerText = d.don_vi_tinh || 'N/A';
                        document.getElementById('det_thanh_phan').innerText = d.thanh_phan || 'Chưa cập nhật';
                        document.getElementById('det_cong_dung').innerText = d.cong_dung || 'Chưa cập nhật';
                        
                        document.getElementById('det_yeu_cau').innerHTML = d.yeu_cau_ke_don === 'Kê đơn' 
                            ? '<span class="badge badge-fail">Kê đơn</span>' 
                            : '<span class="badge badge-success">Không kê đơn</span>';
                        
                        toggleModal('modalDetailThuoc', true);
                    }
                });
        }

        // Mở Modal Sửa & Nạp Dữ Liệu Cũ
        function openEditModal(id) {
            fetch(`quanLyThuoc.php?action=get_detail&id=${id}`)
                .then(r => r.json())
                .then(res => {
                    if(res.success) {
                        const d = res.data;
                        document.getElementById('edit_id_thuoc').value = d.id_thuoc;
                        document.getElementById('edit_ten_thuoc').value = d.ten_thuoc;
                        document.getElementById('edit_danh_muc').value = d.danh_muc;
                        document.getElementById('edit_ham_luong').value = d.ham_luong;
                        document.getElementById('edit_gia_ban').value = d.gia_ban;
                        document.getElementById('edit_dang_bao_che').value = d.dang_bao_che;
                        document.getElementById('edit_don_vi_tinh').value = d.don_vi_tinh;
                        document.getElementById('edit_yeu_cau').value = d.yeu_cau_ke_don;
                        document.getElementById('edit_thanh_phan').value = d.thanh_phan;
                        document.getElementById('edit_cong_dung').value = d.cong_dung;
                        
                        toggleModal('modalEditThuoc', true);
                    }
                });
        }
    </script>
</body>
</html>