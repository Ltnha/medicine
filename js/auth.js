let provider;
let signer;
let userAddress = "";

// 1. Hàm kết nối ví (không đổi)
async function connectWallet() {
    if (typeof window.ethereum === 'undefined') {
        alert('Vui lòng cài đặt MetaMask hoặc ví Ethereum tương thích!');
        return;
    }

    try {
        provider = new ethers.BrowserProvider(window.ethereum);
        const accounts = await provider.send("eth_requestAccounts", []);
        signer = await provider.getSigner();
        userAddress = accounts[0];

        document.getElementById('wallet-address').value = userAddress;
        document.getElementById('btn-connect-text').innerText = "Đã kết nối";

        const btnLogin = document.getElementById('btn-login');
        btnLogin.disabled = false;
        btnLogin.className = "w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl transition flex items-center justify-center space-x-2 shadow-md cursor-pointer";
    } catch (error) {
        console.error("Lỗi kết nối ví:", error);
        alert("Kết nối ví thất bại.");
    }
}

// 2. Hàm xử lý SIWE Đăng nhập — giờ gọi PHP thay vì gọi Node trực tiếp
async function handleSIWELogin() {
    if (!userAddress || !signer) return;

    try {
        // BƯỚC 1: Lấy Nonce từ PHP (PHP tự lưu vào PHP session, cookie PHPSESSID)
        const nonceResponse = await fetch('nonce.php', {
            credentials: 'include'
        });
        const nonceData = await nonceResponse.json();
        const nonce = nonceData.nonce;

        // Dựng thông điệp SIWE chuẩn
        const domain = window.location.host;
        const origin = window.location.origin;
        const statement = "Đăng nhập vào hệ thống PharmaChain Portal bằng ví của bạn.";

        const message = `${domain} wants you to sign in with your Ethereum account:\n` +
                        `${userAddress}\n\n` +
                        `${statement}\n\n` +
                        `URI: ${origin}\n` +
                        `Version: 1\n` +
                        `Chain ID: 1\n` +
                        `Nonce: ${nonce}\n` +
                        `Issued At: ${new Date().toISOString()}`;

        // Ký thông điệp qua ví
        const signature = await signer.signMessage(message);

        // BƯỚC 2: Gửi cho PHP xử lý (PHP sẽ tự gọi Node để verify chữ ký ở phía server)
        const verifyResponse = await fetch('verify.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ message, signature, address: userAddress })
        });

        const verifyResult = await verifyResponse.json();

        if (verifyResult.success) {
            alert('Đăng nhập thành công! Quyền: ' + verifyResult.role);
            window.location.href = '../admin/dashBoard.php';
        } else {
            alert('Đăng nhập thất bại: ' + (verifyResult.error || 'Không rõ nguyên nhân'));
        }

    } catch (error) {
        console.error("Lỗi xử lý SIWE:", error);
        alert("Người dùng đã từ chối ký hoặc có lỗi xảy ra.");
    }
}
