<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Truy Xuất Nguồn Gốc Thuốc</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0fdf4; /* Tone xanh lá dược phẩm nhẹ nhàng */
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
                <a href="index.php" class="inline-flex items-center space-x-2 text-sm bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-2 rounded-xl">
                    <i class="fa-solid fa-home"></i>
                    <span>Trang chủ</span>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-2xl mx-auto px-4 py-8 flex-grow w-full">
        
        <div class="bg-white rounded-2xl shadow-md p-6 mb-6 border border-emerald-50">
            <h2 class="text-xl font-bold text-gray-800 text-center mb-2">Kiểm Tra Nguồn Gốc Thuốc</h2>
            <p class="text-sm text-gray-500 text-center mb-6">Quét mã QR trên vỏ hộp hoặc nhập mã định danh để kiểm tra thông tin xuất xứ.</p>
            
            <div class="space-y-4">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-barcode text-gray-400"></i>
                    </div>
                    <input type="text" id="med-code" placeholder="Nhập mã định danh thuốc (VD: MED123456)..." 
                           class="block w-full pl-10 pr-12 py-3.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-gray-900 shadow-sm transition">
                    <button class="absolute inset-y-0 right-0 pr-3 flex items-center text-emerald-600 hover:text-emerald-700" title="Quét mã QR">
                        <i class="fa-solid fa-qrcode text-xl"></i>
                    </button>
                </div>

                <button onclick="showInfo()" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3.5 px-4 rounded-xl shadow-md transition flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Truy Xuất Thông Tin</span>
                </button>
            </div>
        </div>

        <div id="result-container" class="hidden space-y-6">
            
            <div class="bg-white rounded-2xl shadow-md p-6 border border-emerald-50 relative overflow-hidden">
                <div class="absolute top-0 right-0 bg-emerald-500 text-white text-xs px-3 py-1 rounded-bl-xl font-medium">
                    <i class="fa-solid fa-circle-check mr-1"></i> Sản phẩm chính hãng
                </div>

                <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b">Thông Tin Sản Phẩm</h3>
                
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-400">Tên thuốc:</p>
                        <p class="font-semibold text-gray-800" id="res-name">Paracetamol 500mg</p>
                    </div>
                    <div>
                        <p class="text-gray-400">Mã lô sản xuất:</p>
                        <p class="font-mono font-semibold text-gray-800" id="res-batch">BATCH-2026-XYZ</p>
                    </div>
                    <div>
                        <p class="text-gray-400">Nhà sản xuất:</p>
                        <p class="font-semibold text-gray-800" id="res-maker">Dược phẩm Tình Thương</p>
                    </div>
                    <div>
                        <p class="text-gray-400">Ngày sản xuất:</p>
                        <p class="font-semibold text-gray-800" id="res-mfg">12/05/2026</p>
                    </div>
                    <div>
                        <p class="text-gray-400">Hạn sử dụng:</p>
                        <p class="font-semibold text-red-600" id="res-exp">12/05/2029</p>
                    </div>
                    <div>
                        <p class="text-gray-400">Trạng thái Blockchain:</p>
                        <p class="font-semibold text-emerald-600"><i class="fa-solid fa-link mr-1"></i> Đã ghi nhận</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-md p-6 border border-emerald-50">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Hành Trình Chuỗi Cung Ứng</h3>
                
                <div class="relative border-l-2 border-emerald-100 ml-3 space-y-6 pb-2">
                    <div class="relative pl-6">
                        <div class="absolute -left-[9px] top-1.5 bg-emerald-600 h-4 w-4 rounded-full border-4 border-white"></div>
                        <p class="text-xs text-gray-400 font-semibold">12/05/2026 - 08:00</p>
                        <h4 class="font-semibold text-sm text-gray-800">Sản xuất & Đóng gói thành công</h4>
                        <p class="text-xs text-gray-500">Nhà máy Bình Dương - Lô hàng đạt chuẩn GMP-WHO.</p>
                    </div>
                    <div class="relative pl-6">
                        <div class="absolute -left-[9px] top-1.5 bg-emerald-600 h-4 w-4 rounded-full border-4 border-white"></div>
                        <p class="text-xs text-gray-400 font-semibold">15/05/2026 - 14:30</p>
                        <h4 class="font-semibold text-sm text-gray-800">Xuất kho phân phối</h4>
                        <p class="text-xs text-gray-500">Vận chuyển bởi Logistics Nhất Tín - Nhiệt độ bảo quản: 24°C.</p>
                    </div>
                    <div class="relative pl-6">
                        <div class="absolute -left-[9px] top-1.5 bg-emerald-500 h-4 w-4 rounded-full border-4 border-white"></div>
                        <p class="text-xs text-gray-400 font-semibold">18/05/2026 - 09:15</p>
                        <h4 class="font-semibold text-sm text-gray-800">Nhập kho nhà thuốc bán lẻ</h4>
                        <p class="text-xs text-gray-500">Nhà thuốc Pharmacity Chi nhánh 1 - Sẵn sàng đến tay người dùng.</p>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <footer class="bg-white border-t border-emerald-100 py-4">
        <p class="text-center text-xs text-gray-400">© 2026 PharmaChain. Ứng dụng quản lý chuỗi cung ứng dược phẩm trên Blockchain.</p>
    </footer>

    <script>
        function showInfo() {
            const resultDiv = document.getElementById('result-container');
            resultDiv.classList.remove('hidden');
            resultDiv.scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>