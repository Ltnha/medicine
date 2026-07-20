// verify-service.js
// Dịch vụ Node.js NỘI BỘ — chỉ làm một việc: xác minh chữ ký Ethereum (SIWE).
// KHÔNG session, KHÔNG kết nối DB, KHÔNG cookie.
// Chỉ nên bind vào 127.0.0.1, PHP gọi sang qua HTTP nội bộ (không public ra ngoài internet).

require('dotenv').config({ path: '../.env' });

const express = require('express');
const { ethers } = require('ethers');

const app = express();
app.use(express.json());

// Khóa bí mật dùng để xác thực request đến từ PHP (không phải từ browser)
// Đặt cùng giá trị này trong file cấu hình PHP, và nên đọc từ biến môi trường thay vì hardcode.
const INTERNAL_API_KEY = process.env.INTERNAL_API_KEY;

app.post('/verify-signature', (req, res) => {
    // Kiểm tra request có đúng từ PHP server gọi sang không (không phải từ browser)
    const apiKey = req.headers['x-internal-key'];
    if (apiKey !== INTERNAL_API_KEY) {
        return res.status(403).json({ error: 'Không có quyền truy cập dịch vụ nội bộ này!' });
    }

    const { message, signature, address } = req.body;

    //ktra mã nội bộ để kết nối từ PHP sang Node.js
    if (!INTERNAL_API_KEY || apiKey !== INTERNAL_API_KEY) {
        return res.status(403).json({ error: 'Không có quyền truy cập dịch vụ nội bộ này!' });
    }
    
    if (!message || !signature || !address) {
        return res.status(400).json({ error: 'Thiếu dữ liệu: message, signature, address là bắt buộc!' });
    }

    try {
        // Đây là lý do duy nhất cần Node.js: ethers.js xử lý việc khôi phục địa chỉ từ chữ ký
        const recoveredAddress = ethers.verifyMessage(message, signature);
        const isValid = recoveredAddress.toLowerCase() === address.toLowerCase();

        return res.status(200).json({
            valid: isValid,
            recoveredAddress
        });
    } catch (error) {
        // Chữ ký sai định dạng, message bị hỏng, v.v.
        console.error('Lỗi xác minh chữ ký:', error.message);
        return res.status(400).json({ valid: false, error: 'Chữ ký không hợp lệ hoặc bị lỗi định dạng!' });
    }
});

// Chỉ lắng nghe trên localhost — PHP chạy trên cùng máy chủ (hoặc mạng nội bộ) mới gọi được.
// Nếu PHP và Node chạy khác máy, đổi thành IP nội bộ và chặn ở firewall, không mở ra internet.
app.listen(3001, '127.0.0.1', () => {
    console.log('Verify-signature service (nội bộ) đang chạy tại http://127.0.0.1:3001');
});
