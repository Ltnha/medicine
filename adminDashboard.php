<?php
// adminDashboard.php
// Chặn truy cập nếu chưa đăng nhập, và lấy sẵn thông tin ví từ session để hiển thị.
session_start();
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$viDaKetNoi = $_SESSION['ma_vi'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ Thống Quản Trị - PharmaChain</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc; /* Tone xám lạnh chuyên nghiệp */
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between">

    <header class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="bg-blue-600 text-white p-2 rounded-lg">
                    <i class="fa-solid fa-prescription-bottle-medical text-xl"></i>
                </div>
                <div>
                    <h1 class="font-bold text-lg text-slate-800 leading-none">PharmaChain Admin</h1>
                    <span class="text-xs text-blue-600 font-semibold">Cổng thông tin nhà sản xuất</span>
                </div>
            </div>
            
            <div class="flex items-center space-x-3">
                <a href="index.php" class="inline-flex items-center space-x-2 text-sm bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-2 rounded-xl">
                    <i class="fa-solid fa-home"></i>
                    <span>Trang chủ</span>
                </a>

                <div class="flex items-center space-x-2">
                    <div class="relative">
                        <input type="text" id="wallet-address" readonly
                               value="<?= htmlspecialchars($viDaKetNoi) ?>"
                               placeholder="Chưa kết nối"
                               class="w-48 pl-3 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono text-slate-600 focus:outline-none">
                    </div>
                    <div class="text-sm text-emerald-600 bg-emerald-50 px-3 py-2 rounded-xl font-medium">
                        <i class="fa-solid fa-circle-check mr-1"></i>
                        <span id="btn-connect-text">Đã kết nối</span>
                    </div>
                    <a href="logout.php"
                       onclick="return confirm('Bạn có chắc muốn đăng xuất không?')"
                       class="inline-flex items-center space-x-2 text-sm bg-red-50 hover:bg-red-100 text-red-600 px-3 py-2 rounded-xl transition">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>Đăng xuất</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-8 flex-grow w-full">
        
        <!-- <div id="wallet-alert" class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-6 rounded-r-xl">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
                </div>
            </div>
        </div> -->

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-800"><i class="fa-solid fa-file-medical text-blue-600 mr-2"></i>Thêm Lô Thuốc Mới</h2>
                <!-- <span class="text-xs bg-slate-200 text-slate-700 px-2.5 py-1 rounded-full font-mono">Dữ liệu thô</span> -->
            </div>

            <form id="med-form" class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Mã định danh thuốc (ID)</label>
                        <input type="text" placeholder="Ví dụ: MED-2026-991A" required
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Tên biệt dược / Thuốc</label>
                        <input type="text" placeholder="Ví dụ: Amoxicillin 500mg" required
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Mã lô sản xuất (Batch No.)</label>
                        <input type="text" placeholder="Ví dụ: BATCH-AMX-02" required
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nhà máy sản xuất</label>
                        <input type="text" placeholder="Ví dụ: Nhà máy dược phẩm Hậu Giang" required
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Ngày sản xuất</label>
                        <input type="date" required
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm text-slate-600">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Hạn sử dụng</label>
                        <input type="date" required
                               class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm text-slate-600">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Thành phần chi tiết & Tiêu chuẩn bảo quản</label>
                    <textarea rows="3" placeholder="- Hoạt chất chính: ...&#10;- Điều kiện bảo quản: < 30 độ C, tránh ánh nắng trực tiếp."
                              class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"></textarea>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end">
                    <button type="button" onclick="deployToBlockchain()" class="flex items-center space-x-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-3 rounded-xl shadow-lg shadow-emerald-100 transition duration-200">
                        <i class="fa-solid fa-cubes"></i>
                        <span>Đẩy lên Blockchain</span>
                    </button>
                </div>
            </form>
        </div>

    </main>

    <footer class="bg-white border-t border-slate-200 py-4 mt-8">
        <p class="text-center text-xs text-gray-400">© 2026 PharmaChain Console. Quyền quản trị tối cao.</p>
    </footer>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ethers/6.13.5/ethers.umd.min.js"></script>
    <script src="js/auth.js" defer></script>
</body>
</html>