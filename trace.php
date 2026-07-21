<?php
// 1. KẾT NỐI CƠ SỞ DỮ LIỆU
require_once 'config/config.php';
$conn = getDbConnection();

// 2. HÀM LẤY ĐỊA CHỈ IP CHUẨN XÁC
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ipList[0]);
    }
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        return $_SERVER['HTTP_X_REAL_IP'];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// 3. XỬ LÝ NHẬN MÃ QR / MÃ TRA CỨU TỪ URL HOẶC FORM
$ma_tra_cuu = $_GET['qr'] ?? $_POST['med_code'] ?? '';
$thong_tin_lo = null;
$error_message = '';

if (!empty($ma_tra_cuu)) {
    
    // 3.1. TRUY VẤN THÔNG TIN LÔ THUỐC TỪ CSDL PHARMACHAIN
    try {
        $sql = "SELECT 
                    l.*, 
                    t.ten_thuoc, t.danh_muc, t.dang_bao_che, t.thanh_phan, t.ham_luong, t.cong_dung, t.don_vi_tinh, t.gia_ban,
                    dn_dk.ten_doanh_nghiep AS cty_dang_ky,
                    dn_sx.ten_doanh_nghiep AS cty_san_xuat,
                    dn_sx.dia_chi_doanh_nghiep AS dia_chi_san_xuat
                FROM LoThuoc l
                INNER JOIN Thuoc t ON l.id_thuoc = t.id_thuoc
                INNER JOIN DoanhNghiep dn_dk ON l.id_cty_dang_ky = dn_dk.id_doanh_nghiep
                INNER JOIN DoanhNghiep dn_sx ON l.id_cty_san_xuat = dn_sx.id_doanh_nghiep
                WHERE l.ma_tra_cuu = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->execute([$ma_tra_cuu]);
        $thong_tin_lo = $stmt->fetch(PDO::FETCH_ASSOC);

        // Xác định trạng thái tra cứu
        $trang_thai_quet = $thong_tin_lo ? 'thanh_cong' : 'that_bai';

        if (!$thong_tin_lo) {
            $error_message = "CẢNH BÁO: Không tìm thấy dữ liệu cho mã tra cứu <strong>" . htmlspecialchars($ma_tra_cuu) . "</strong>. Mã này có thể là hàng giả hoặc chưa đăng ký!";
        }
    } catch (PDOException $e) {
        $trang_thai_quet = 'that_bai';
        $error_message = "Có lỗi xảy ra trong quá trình truy vấn dữ liệu.";
    }

    // 3.2. LƯU LỊCH SỬ QUÉT (LƯU CẢ THÀNH CÔNG LẪN THẤT BẠI)
    $ip_nguoi_quet = getClientIP();
    $thiet_bi = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    try {
        // Lưu lịch sử kèm trạng thái quét (thanh_cong / that_bai)
        $sql_log = "INSERT INTO LichSuQuet (ma_tra_cuu, ip_nguoi_quet, thiet_bi, trang_thai) VALUES (?, ?, ?, ?)";
        $stmt_log = $conn->prepare($sql_log);
        $stmt_log->execute([$ma_tra_cuu, $ip_nguoi_quet, $thiet_bi, $trang_thai_quet]);
    } catch (PDOException $e) {
        error_log("Lỗi ghi nhận IP quét: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Truy Xuất Nguồn Gốc Thuốc - PharmaChain</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0fdf4;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between">

    <header class="bg-white shadow-sm border-b border-emerald-100">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="bg-emerald-600 text-white p-2 rounded-lg">
                    <i class="fa-solid fa-prescription-bottle-medical text-xl"></i>
                </div>
                <div>
                    <h1 class="font-bold text-lg text-emerald-900 leading-none">PharmaChain</h1>
                    <span class="text-xs text-emerald-600 font-medium">Hệ thống xác thực thuốc thật</span>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-xs bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded-full font-medium">
                    <i class="fa-solid fa-shield-halved mr-1"></i> Đã bảo mật
                </span>
                <a href="index.php" class="inline-flex items-center space-x-2 text-sm bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-2 rounded-xl transition">
                    <i class="fa-solid fa-home"></i>
                    <span>Trang chủ</span>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-2xl mx-auto px-4 py-8 flex-grow w-full">
        
        <!-- FORM TÌM KIẾM TRUY XUẤT -->
        <div class="bg-white rounded-2xl shadow-md p-6 mb-6 border border-emerald-50">
            <h2 class="text-xl font-bold text-gray-800 text-center mb-2">Kiểm Tra Nguồn Gốc Thuốc</h2>
            <p class="text-sm text-gray-500 text-center mb-6">Quét mã QR trên vỏ hộp hoặc nhập mã tra cứu để kiểm tra thông tin xuất xứ.</p>
            
            <form action="trace.php" method="GET" class="space-y-4">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-barcode text-gray-400"></i>
                    </div>
                    <input type="text" name="qr" value="<?= htmlspecialchars($ma_tra_cuu) ?>" placeholder="Nhập mã tra cứu (VD: QR-HONGOC-001)..." required
                           class="block w-full pl-10 pr-12 py-3.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-gray-900 shadow-sm transition">
                    <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center text-emerald-600 hover:text-emerald-700" title="Quét mã QR">
                        <i class="fa-solid fa-qrcode text-xl"></i>
                    </button>
                </div>

                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3.5 px-4 rounded-xl shadow-md transition flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Truy Xuất Thông Tin</span>
                </button>
            </form>
        </div>

        <!-- HIỂN THỊ CẢNH BÁO NẾU KHÔNG CÓ TRONG DATABASE -->
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl text-center shadow-sm mb-6">
                <i class="fa-solid fa-triangle-exclamation mr-1 text-lg"></i>
                <span><?= $error_message ?></span>
            </div>
        <?php endif; ?>

        <!-- KẾT QUẢ TRUY XUẤT NGUỒN GỐC THUỐC -->
        <?php if ($thong_tin_lo): ?>
            <div id="result-container" class="space-y-6">
                
                <!-- THÔNG TIN CHI TIẾT SẢN PHẨM -->
                <div class="bg-white rounded-2xl shadow-md p-6 border border-emerald-50 relative overflow-hidden">
                    <div class="absolute top-0 right-0 bg-emerald-500 text-white text-xs px-3 py-1 rounded-bl-xl font-medium">
                        <i class="fa-solid fa-circle-check mr-1"></i> Sản phẩm chính hãng
                    </div>

                    <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b">Thông Tin Sản Phẩm</h3>
                    
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-400">Tên thuốc:</p>
                            <p class="font-semibold text-gray-800"><?= htmlspecialchars($thong_tin_lo['ten_thuoc']) ?></p>
                        </div>
                        <div>
                            <p class="text-gray-400">Mã lô sản xuất:</p>
                            <p class="font-mono font-semibold text-gray-800"><?= htmlspecialchars($thong_tin_lo['ma_lo']) ?></p>
                        </div>
                        <div>
                            <p class="text-gray-400">Nhà sản xuất:</p>
                            <p class="font-semibold text-gray-800"><?= htmlspecialchars($thong_tin_lo['cty_san_xuat']) ?></p>
                        </div>
                        <div>
                            <p class="text-gray-400">Công ty đăng ký:</p>
                            <p class="font-semibold text-gray-800"><?= htmlspecialchars($thong_tin_lo['cty_dang_ky']) ?></p>
                        </div>
                        <div>
                            <p class="text-gray-400">Ngày sản xuất:</p>
                            <p class="font-semibold text-gray-800"><?= date('d/m/Y', strtotime($thong_tin_lo['ngay_san_xuat'])) ?></p>
                        </div>
                        <div>
                            <p class="text-gray-400">Hạn sử dụng:</p>
                            <p class="font-semibold text-red-600"><?= date('d/m/Y', strtotime($thong_tin_lo['han_su_dung'])) ?></p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-gray-400">Thành phần & Hàm lượng:</p>
                            <p class="font-medium text-gray-700"><?= htmlspecialchars($thong_tin_lo['thanh_phan']) ?></p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-gray-400">Công dụng:</p>
                            <p class="font-medium text-gray-700"><?= htmlspecialchars($thong_tin_lo['cong_dung']) ?></p>
                        </div>
                        <div class="col-span-2 pt-2 border-t">
                            <p class="text-gray-400">Mã giao dịch Blockchain (TxHash):</p>
                            <?php if (!empty($thong_tin_lo['tx_hash'])): ?>
                                <p class="font-mono text-xs text-emerald-600 break-all bg-emerald-50 p-2 rounded-lg mt-1">
                                    <i class="fa-solid fa-link mr-1"></i> <?= htmlspecialchars($thong_tin_lo['tx_hash']) ?>
                                </p>
                            <?php else: ?>
                                <p class="text-xs text-amber-600 font-medium mt-1"><i class="fa-solid fa-clock mr-1"></i> Đang chờ xác nhận trên Blockchain</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- LỊCH SỬ / HÀNH TRÌNH CHUỖI CUNG ỨNG -->
                <div class="bg-white rounded-2xl shadow-md p-6 border border-emerald-50">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Hành Trình Chuỗi Cung Ứng</h3>
                    
                    <div class="relative border-l-2 border-emerald-100 ml-3 space-y-6 pb-2">
                        <div class="relative pl-6">
                            <div class="absolute -left-[9px] top-1.5 bg-emerald-600 h-4 w-4 rounded-full border-4 border-white"></div>
                            <p class="text-xs text-gray-400 font-semibold"><?= date('d/m/Y', strtotime($thong_tin_lo['ngay_san_xuat'])) ?> - Khởi tạo lô</p>
                            <h4 class="font-semibold text-sm text-gray-800">Sản xuất & Đóng gói thành công</h4>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($thong_tin_lo['cty_san_xuat']) ?> (<?= htmlspecialchars($thong_tin_lo['dia_chi_san_xuat']) ?>)</p>
                        </div>
                        <div class="relative pl-6">
                            <div class="absolute -left-[9px] top-1.5 bg-emerald-600 h-4 w-4 rounded-full border-4 border-white"></div>
                            <p class="text-xs text-gray-400 font-semibold"><?= date('d/m/Y', strtotime($thong_tin_lo['ngay_nhap_kho'])) ?></p>
                            <h4 class="font-semibold text-sm text-gray-800">Ghi nhận vào hệ thống kho PharmaChain</h4>
                            <p class="text-xs text-gray-500">Số lượng kho: <?= number_format($thong_tin_lo['so_luong_ton']) ?> <?= htmlspecialchars($thong_tin_lo['don_vi_tinh']) ?></p>
                        </div>
                        <div class="relative pl-6">
                            <div class="absolute -left-[9px] top-1.5 bg-emerald-500 h-4 w-4 rounded-full border-4 border-white"></div>
                            <p class="text-xs text-gray-400 font-semibold">Hiện tại</p>
                            <h4 class="font-semibold text-sm text-gray-800">Sẵn sàng phân phối & Đến tay người tiêu dùng</h4>
                            <p class="text-xs text-gray-500">Đã đăng ký bởi: <?= htmlspecialchars($thong_tin_lo['cty_dang_ky']) ?></p>
                        </div>
                    </div>
                </div>

            </div>
        <?php endif; ?>

    </main>

    <footer class="bg-white border-t border-emerald-100 py-4">
        <p class="text-center text-xs text-gray-400">© 2026 PharmaChain. Ứng dụng quản lý chuỗi cung ứng dược phẩm trên Blockchain.</p>
    </footer>

</body>
</html>