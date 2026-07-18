let provider;
let signer;
let userAddress = "";

// 1. Hàm kết nối ví
async function connectWallet() {
    if (typeof window.ethereum === 'undefined') {
        alert('Vui lòng cài đặt MetaMask hoặc ví Ethereum tương thích!');
        return;
    }

    try {
        // Khởi tạo Ethers v6 Provider
        provider = new ethers.BrowserProvider(window.ethereum);
        // Yêu cầu tài khoản
        const accounts = await provider.send("eth_requestAccounts", []);
        signer = await provider.getSigner();
        userAddress = accounts[0];

        // Cập nhật giao diện
        document.getElementById('wallet-address').value = userAddress;
        document.getElementById('btn-connect-text').innerText = "Đã kết nối";
        
        // Mở khóa nút Đăng nhập
        const btnLogin = document.getElementById('btn-login');
        btnLogin.disabled = false;
        btnLogin.className = "w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl transition flex items-center justify-center space-x-2 shadow-md cursor-pointer";
    } catch (error) {
        console.error("Lỗi kết nối ví:", error);
        alert("Kết nối ví thất bại.");
    }
}

// 2. Hàm xử lý SIWE Đăng nhập
async function handleSIWELogin() {
    if (!userAddress || !signer) return;

    try {
        // Bước A: Lấy chuỗi Nonce bảo mật từ backend PHP
        const nonceResponse = await fetch('api/get_nonce.php');
        const nonceData = await nonceResponse.json();
        const nonce = nonceData.nonce;

        // Bước B: Dựng thông điệp SIWE chuẩn quy định (EIP-4361)
        const domain = window.location.host;
        const origin = window.location.origin;
        const statement = "Đăng nhập vào hệ thống PharmaChain Portal bằng ví của bạn.";
        
        // Định dạng tin nhắn SIWE bắt buộc phải chuẩn chỉnh từng dòng
        const message = `${domain} wants you to sign in with your Ethereum account:\n` +
                        `${userAddress}\n\n` +
                        `${statement}\n\n` +
                        `URI: ${origin}\n` +
                        `Version: 1\n` +
                        `Chain ID: 1\n` + // 1 cho Ethereum Mainnet
                        `Nonce: ${nonce}\n` +
                        `Issued At: ${new Date().toISOString()}`;

        // Bước C: Yêu cầu người dùng thực hiện Ký bằng ví
        const signature = await signer.signMessage(message);

        // Bước D: Gửi thông điệp + chữ ký lên PHP để xác thực
        const verifyResponse = await fetch('api/verify.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message, signature })
        });

        const verifyResult = await verifyResponse.json();

        if (verifyResult.success) {
            alert('Đăng nhập thành công! Địa chỉ ví: ' + verifyResult.address);
            // Có thể chuyển hướng trang tại đây: window.location.href = 'dashboard.php';
        } else {
            alert('Đăng nhập thất bại: ' + verifyResult.error);
        }

    } catch (error) {
        console.error("Lỗi xử lý SIWE:", error);
        alert("Người dùng đã từ chối ký hoặc có lỗi xảy ra.");
    }
}