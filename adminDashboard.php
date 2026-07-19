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

    <!-- HEADER -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
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

    <!-- MAIN WRAPPER -->
    <div class="max-w-7xl w-full mx-auto flex flex-1 px-4 py-8 gap-6">
        
        <!-- SIDEBAR -->
        <aside class="w-64 flex-shrink-0">
            <div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-2 sticky top-24 shadow-sm">
                <p class="text-xs font-semibold text-slate-400 px-3 uppercase tracking-wider mb-3">Chức năng</p>
                
                <button onclick="switchTab('add-med-tab')" id="btn-add-med-tab" 
                    class="tab-btn w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition duration-200 bg-blue-50 text-blue-600">
                    <i class="fa-solid fa-plus-circle text-base"></i>
                    <span>Thêm thuốc mới</span>
                </button>

                <button onclick="switchTab('list-med-tab')" id="btn-list-med-tab" 
                    class="tab-btn w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition duration-200 text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                    <i class="fa-solid fa-list-ul text-base"></i>
                    <span>Danh sách thuốc</span>
                </button>
            </div>
        </aside>

        <!-- CONTENT AREA -->
        <main class="flex-grow">
            
            <!-- Tab 1: Thêm thuốc mới -->
            <div id="add-med-tab" class="tab-content block">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="text-lg font-bold text-slate-800"><i class="fa-solid fa-file-medical text-blue-600 mr-2"></i>Thêm Lô Thuốc Mới</h2>
                    </div>

                    <form id="med-form" class="p-6 space-y-6">
                        <!-- Nhóm 1: Thông tin cơ bản lô thuốc -->
                        <div class="border-b border-slate-100 pb-4">
                            <h3 class="text-sm font-bold text-blue-600 uppercase tracking-wide mb-4"><i class="fa-solid fa-circle-info mr-2"></i>Thông tin chung</h3>
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
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Dạng bào chế</label>
                                    <input type="text" placeholder="Ví dụ: Viên nén bao phim, Dung dịch tiêm" required
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
                        </div>

                        <!-- Nhóm 2: Thông tin Doanh nghiệp đăng ký & Sản xuất -->
                        <div class="border-b border-slate-100 pb-4">
                            <h3 class="text-sm font-bold text-blue-600 uppercase tracking-wide mb-4"><i class="fa-solid fa-building mr-2"></i>Đơn vị chịu trách nhiệm</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Tên công ty đăng ký</label>
                                    <input type="text" placeholder="Ví dụ: Công ty Cổ phần Dược phẩm Trung ương 1" required
                                           class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Địa chỉ công ty đăng ký</label>
                                    <input type="text" placeholder="Số 160 Tôn Đức Thắng, Đống Đa, Hà Nội" required
                                           class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Tên công ty sản xuất</label>
                                    <input type="text" placeholder="Ví dụ: Nhà máy Dược phẩm DHG" required
                                           class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Địa chỉ công ty sản xuất</label>
                                    <input type="text" placeholder="KCN Tân Phú Thạnh, Châu Thành A, Hậu Giang" required
                                           class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>
                            </div>
                        </div>

                        <!-- Nhóm 3: Chi tiết, bảo quản và trạng thái bảo mật -->
                        <div class="space-y-6">
                            <h3 class="text-sm font-bold text-blue-600 uppercase tracking-wide mb-2"><i class="fa-solid fa-shield-halved mr-2"></i>Bảo mật & Chi tiết</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Trạng thái bảo mật ban đầu</label>
                                    <select class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm bg-white text-slate-700">
                                        <option value="safe" class="text-emerald-600 font-medium">🛡️ Nguyên vẹn / Chưa đổi</option>
                                        <option value="compromised" class="text-red-600 font-medium">⚠️ Đã bị chỉnh sửa / Thay đổi</option>
                                    </select>
                                    <p class="text-xs text-slate-400 mt-1">Thiết lập trạng thái ban đầu của dữ liệu khi khởi tạo lên chuỗi.</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Thành phần chi tiết & Tiêu chuẩn bảo quản</label>
                                <textarea rows="3" placeholder="- Hoạt chất chính: ...&#10;- Điều kiện bảo quản: < 30 độ C, tránh ánh nắng trực tiếp."
                                          class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"></textarea>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex justify-end">
                            <button type="button" onclick="deployToBlockchain()" class="flex items-center space-x-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-3 rounded-xl shadow-lg shadow-emerald-100 transition duration-200">
                                <i class="fa-solid fa-cubes"></i>
                                <span>Đẩy lên Blockchain</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab 2: Danh sách thuốc -->
            <div id="list-med-tab" class="tab-content hidden">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="text-lg font-bold text-slate-800"><i class="fa-solid fa-list-check text-blue-600 mr-2"></i>Danh Sách Lô Thuốc Đã Đẩy</h2>
                    </div>
                    
                    <div class="p-6 overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-200 text-slate-400 text-xs font-semibold uppercase bg-slate-50/50">
                                    <th class="py-3 px-4">Mã ID</th>
                                    <th class="py-3 px-4">Tên Thuốc</th>
                                    <th class="py-3 px-4">Mã Lô</th>
                                    <th class="py-3 px-4">Công Ty Đăng Ký</th>
                                    <th class="py-3 px-4">Bảo Mật</th>
                                    <th class="py-3 px-4 text-right">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100 text-slate-600">
                                <tr>
                                    <td class="py-3 px-4 font-mono text-xs text-blue-600">MED-2026-991A</td>
                                    <td class="py-3 px-4 font-semibold text-slate-800">Amoxicillin 500mg (Viên nén)</td>
                                    <td class="py-3 px-4">BATCH-AMX-02</td>
                                    <td class="py-3 px-4 text-xs">Dược phẩm TW 1</td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <i class="fa-solid fa-shield mr-1"></i> Nguyên vẹn
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                            <i class="fa-solid fa-link mr-1"></i> On-chain
                                        </span>
                                    </td>
                                </tr>
                                <!-- Dòng dữ liệu ví dụ lúc bị thay đổi -->
                                <tr>
                                    <td class="py-3 px-4 font-mono text-xs text-blue-600">MED-2026-842B</td>
                                    <td class="py-3 px-4 font-semibold text-slate-800">Paracetamol 500mg</td>
                                    <td class="py-3 px-4">BATCH-PAR-05</td>
                                    <td class="py-3 px-4 text-xs">Dược Hậu Giang</td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-red-50 text-red-700 border border-red-200 animate-pulse">
                                            <i class="fa-solid fa-triangle-exclamation mr-1"></i> Đã bị đổi
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                            <i class="fa-solid fa-link mr-1"></i> On-chain
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- FOOTER -->
    <footer class="bg-white border-t border-slate-200 py-4 mt-8">
        <p class="text-center text-xs text-gray-400">© 2026 PharmaChain Console. Quyền quản trị tối cao.</p>
    </footer>

    <!-- FILE SCRIPT ĐÃ ĐƯỢC TÁCH BIỆT -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ethers/6.13.5/ethers.umd.min.js"></script>
    <script src="js/dashboard.js"></script>
    <script src="js/auth.js" defer></script>
</body>
</html>