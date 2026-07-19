<?php
session_start();
if (!empty($_SESSION['admin_id'])) {
    header('Location: adminDashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập Web3 - PharmaChain</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ethers/6.13.5/ethers.umd.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between">

    <header class="bg-white border-b border-slate-200">
        <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="bg-gradient-to-tr from-blue-600 to-indigo-600 text-white p-2 rounded-lg">
                    <i class="fa-solid fa-prescription-bottle-medical text-xl"></i>
                </div>
                <div>
                    <h1 class="font-bold text-lg text-slate-800 leading-none">PharmaChain Portal</h1>
                    <span class="text-xs text-blue-600 font-semibold">Xác thực phi tập trung</span>
                </div>
            </div>

            <a href="index.php" class="inline-flex items-center space-x-2 text-sm bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-2 rounded-xl">
                <i class="fa-solid fa-home"></i>
                <span>Trang chủ</span>
            </a>
        </div>
    </header>

    <main class="flex-grow flex items-center justify-center px-4 py-12">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden">
            
            <div class="bg-slate-900 px-6 py-8 text-center relative">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-600/20 to-indigo-600/20 opacity-50"></div>
                <div class="relative z-10">
                    <div class="inline-flex bg-blue-600/10 text-blue-400 p-3 rounded-full mb-3 border border-blue-500/20">
                        <i class="fa-solid fa-key text-2xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-white">Đăng Nhập Với Ethereum</h2>
                    <p class="text-xs text-slate-400 mt-1">Không cần mật khẩu. Xác thực an toàn qua chữ ký số.</p>
                </div>
            </div>

            <div class="p-6 space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Mã địa chỉ ví của bạn</label>
                    <div class="flex gap-2">
                        <div class="relative flex-grow">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fa-solid fa-wallet text-slate-400"></i>
                            </div>
                            <input type="text" id="wallet-address" readonly
                                   placeholder="Vui lòng bấm Kết nối ví..."
                                   class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono text-slate-600 focus:outline-none">
                        </div>
                        
                        <button type="button" onclick="connectWallet()" id="btn-connect"
                                class="flex items-center space-x-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition shrink-0 shadow-sm">
                            <i class="fa-solid fa-plug"></i>
                            <span id="btn-connect-text">Kết nối ví</span>
                        </button>
                    </div>
                </div>

                <button type="button" onclick="handleSIWELogin()" id="btn-login" disabled
                        class="w-full bg-slate-200 text-slate-400 font-bold py-3 px-4 rounded-xl transition flex items-center justify-center space-x-2 cursor-not-allowed">
                    <i class="fa-solid fa-signature"></i>
                    <span>Ký & Đăng Nhập Hệ Thống</span>
                </button>
            </div>

        </div>
    </main>

    <footer class="bg-white border-t border-slate-200 py-4">
        <p class="text-center text-xs text-gray-400">© 2026 PharmaChain Pure Web3 Auth.</p>
    </footer>

    <script src="js/auth.js" defer></script>
</body>
</html>