// CẤU HÌNH THÔNG SỐ CONTRACT BẠN ĐÃ DEPLOY TRÊN REMIX
const contractAddress = "0x64235DB5203F9F062Bb36FbF889c0AE33f077886"; //địa chỉ contract
const contractABI = [
    [
	{
		"inputs": [],
		"stateMutability": "nonpayable",
		"type": "constructor"
	},
	{
		"anonymous": false,
		"inputs": [
			{
				"indexed": true,
				"internalType": "string",
				"name": "maTraCuu",
				"type": "string"
			},
			{
				"indexed": false,
				"internalType": "string",
				"name": "maLo",
				"type": "string"
			},
			{
				"indexed": false,
				"internalType": "uint256",
				"name": "idThuoc",
				"type": "uint256"
			}
		],
		"name": "BatchRegistered",
		"type": "event"
	},
	{
		"anonymous": false,
		"inputs": [
			{
				"indexed": true,
				"internalType": "string",
				"name": "maTraCuu",
				"type": "string"
			},
			{
				"indexed": false,
				"internalType": "bool",
				"name": "isCompromised",
				"type": "bool"
			}
		],
		"name": "BatchStatusUpdated",
		"type": "event"
	},
	{
		"inputs": [
			{
				"internalType": "address",
				"name": "_admin",
				"type": "address"
			}
		],
		"name": "addAdmin",
		"outputs": [],
		"stateMutability": "nonpayable",
		"type": "function"
	},
	{
		"inputs": [
			{
				"internalType": "address",
				"name": "",
				"type": "address"
			}
		],
		"name": "admins",
		"outputs": [
			{
				"internalType": "bool",
				"name": "",
				"type": "bool"
			}
		],
		"stateMutability": "view",
		"type": "function"
	},
	{
		"inputs": [
			{
				"internalType": "string",
				"name": "_maTraCuu",
				"type": "string"
			}
		],
		"name": "getBatch",
		"outputs": [
			{
				"internalType": "string",
				"name": "maLo",
				"type": "string"
			},
			{
				"internalType": "uint256",
				"name": "idThuoc",
				"type": "uint256"
			},
			{
				"internalType": "uint256",
				"name": "idCtyDangKy",
				"type": "uint256"
			},
			{
				"internalType": "uint256",
				"name": "idCtySanXuat",
				"type": "uint256"
			},
			{
				"internalType": "uint256",
				"name": "hanSuDung",
				"type": "uint256"
			},
			{
				"internalType": "bool",
				"name": "isCompromised",
				"type": "bool"
			}
		],
		"stateMutability": "view",
		"type": "function"
	},
	{
		"inputs": [],
		"name": "owner",
		"outputs": [
			{
				"internalType": "address",
				"name": "",
				"type": "address"
			}
		],
		"stateMutability": "view",
		"type": "function"
	},
	{
		"inputs": [
			{
				"internalType": "string",
				"name": "_maTraCuu",
				"type": "string"
			},
			{
				"internalType": "string",
				"name": "_maLo",
				"type": "string"
			},
			{
				"internalType": "uint256",
				"name": "_idThuoc",
				"type": "uint256"
			},
			{
				"internalType": "uint256",
				"name": "_idCtyDangKy",
				"type": "uint256"
			},
			{
				"internalType": "uint256",
				"name": "_idCtySanXuat",
				"type": "uint256"
			},
			{
				"internalType": "uint256",
				"name": "_hanSuDung",
				"type": "uint256"
			}
		],
		"name": "registerBatch",
		"outputs": [],
		"stateMutability": "nonpayable",
		"type": "function"
	},
	{
		"inputs": [
			{
				"internalType": "string",
				"name": "_maTraCuu",
				"type": "string"
			},
			{
				"internalType": "bool",
				"name": "_isCompromised",
				"type": "bool"
			}
		],
		"name": "updateBatchStatus",
		"outputs": [],
		"stateMutability": "nonpayable",
		"type": "function"
	}
]
];

let provider;
let signer;
let contract;

// Hàm khởi tạo kết nối ví MetaMask và Contract ở phía trình duyệt
async function initBlockchain() {
    if (typeof window.ethereum !== 'undefined') {
        try {
            // Cú pháp Ethers.js v6 dành cho Browser
            provider = new ethers.BrowserProvider(window.ethereum);
            signer = await provider.getSigner();
            contract = new ethers.Contract(contractAddress, contractABI, signer);
            console.log("Frontend kết nối Blockchain thành công!");
        } catch (error) {
            console.error("Người dùng từ chối kết nối ví hoặc có lỗi:", error);
        }
    } else {
        console.warn("Không tìm thấy MetaMask!");
    }
}

// Tự động kết nối khi trình duyệt tải xong trang
window.addEventListener('load', initBlockchain);

// Hàm gọi MetaMask để ký và lưu lô thuốc lên Blockchain
async function registerBatchOnBlockchain(maTraCuu, maLo, idThuoc, idCtyDangKy, idCtySanXuat, hanSuDung) {
    try {
        if (!contract) await initBlockchain();

        // Đổi ngày sang định dạng số Timestamp để khớp với cấu hình Solidity
        const timestampHSD = Math.floor(new Date(hanSuDung).getTime() / 1000);

        // Kích hoạt MetaMask gọi hàm registerBatch trong Smart Contract
        const tx = await contract.registerBatch(
            maTraCuu,
            maLo,
            Number(idThuoc),
            Number(idCtyDangKy),
            Number(idCtySanXuat),
            timestampHSD
        );

        console.log("Đang xử lý giao dịch... Tx Hash:", tx.hash);
        
        // Chờ Blockchain xác nhận giao dịch thành công (Găm vào Block)
        await tx.wait();
        console.log("Giao dịch đã được xác nhận thành công!");
        
        return tx.hash; // Trả về tx_hash để đẩy tiếp vào MySQL bằng PHP
    } catch (error) {
        console.error("Lỗi khi tương tác với Blockchain:", error);
        throw error;
    }
}

// Hàm đọc dữ liệu công khai từ Blockchain (Không tốn gas)
async function getBatchFromBlockchain(maTraCuu) {
    try {
        if (!contract) await initBlockchain();

        const data = await contract.getBatch(maTraCuu);
        return {
            maLo: data[0],
            idThuoc: Number(data[1]),
            idCtyDangKy: Number(data[2]),
            idCtySanXuat: Number(data[3]),
            hanSuDung: new Date(Number(data[4]) * 1000).toLocaleDateString('vi-VN'),
            isCompromised: data[5]
        };
    } catch (error) {
        console.error("Không tìm thấy dữ liệu cho mã tra cứu:", maTraCuu);
        return null;
    }
}