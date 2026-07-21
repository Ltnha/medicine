// js/blockchain-tracker.js

// 1. CẤU HÌNH CONTRACT
const CONTRACT_ADDRESS = "0x16FFaD3183B23D06111852c170a3c8Fd952F4A9e"; //thay đổi khi deploy contract mới

// ABI dạng mảng 1 lớp chuẩn
const CONTRACT_ABI =[
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
		"name": "updateBatch",
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
	}
];

let provider;
let signer;
let contract;
let userAddress = null;

// 2. KHỞI TẠO KẾT NỐI VÍ METAMASK
async function initBlockchain() {
    if (typeof window.ethereum !== 'undefined') {
        try {
            provider = new ethers.BrowserProvider(window.ethereum);
            signer = await provider.getSigner();
            contract = new ethers.Contract(CONTRACT_ADDRESS, CONTRACT_ABI, signer);
            console.log("Kết nối Blockchain thành công!");
            return true;
        } catch (error) {
            console.error("Lỗi kết nối ví:", error);
            return false;
        }
    } else {
        alert("Vui lòng cài đặt tiện ích MetaMask!");
        return false;
    }
}

// 3. HÀM NÚT BẤM KẾT NỐI VÍ
async function connectWallet() {
    if (window.ethereum) {
        try {
            const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
            if (accounts.length > 0) {
                userAddress = accounts[0];
                handleWalletConnected(userAddress);
                await initBlockchain();
            }
        } catch (error) {
            alert("Kết nối ví thất bại!");
        }
    } else {
        alert("Vui lòng cài đặt tiện ích MetaMask!");
    }
}

function handleWalletConnected(address) {
    userAddress = address;
    const btnText = document.getElementById('walletAddressText');
    const btn = document.getElementById('connectWalletBtn');
    if (btnText) btnText.innerText = address.substring(0, 6) + "..." + address.substring(address.length - 4);
    if (btn) btn.classList.add('connected');
}

// Tự động nhận diện nếu ví đã kết nối từ trước
window.addEventListener('load', async () => {
    if (window.ethereum) {
        const accounts = await window.ethereum.request({ method: 'eth_accounts' });
        if (accounts.length > 0) {
            handleWalletConnected(accounts[0]);
            await initBlockchain();
        }
    }
});

// 4. HÀM ĐĂNG KÝ LÔ THUỐC LÊN BLOCKCHAIN
async function registerBatchOnBlockchain(maTraCuu, maLo, idThuoc, idCtyDangKy, idCtySanXuat, hanSuDung) {
    if (!contract) {
        const ok = await initBlockchain();
        if (!ok) throw new Error("Chưa kết nối được Smart Contract!");
    }

    const timestampHSD = Math.floor(new Date(hanSuDung).getTime() / 1000);

    // Kích hoạt MetaMask gọi hàm registerBatch
    const tx = await contract.registerBatch(
        maTraCuu,
        maLo,
        Number(idThuoc),
        Number(idCtyDangKy),
        Number(idCtySanXuat),
        timestampHSD
    );

    console.log("Đang xử lý... Tx Hash:", tx.hash);
    await tx.wait(); // Đợi găm vào Block
    return tx.hash;
}
// chỉnh sửa
async function updateBatchOnBlockchain(maTraCuu, maLo, idThuoc, idCtyDangKy, idCtySanXuat, hanSuDung) {
    if (!contract) await initBlockchain();
    const timestampHSD = Math.floor(new Date(hanSuDung).getTime() / 1000);

    // Ký giao dịch sửa trên MetaMask
    const tx = await contract.updateBatch(
        maTraCuu,
        maLo,
        Number(idThuoc),
        Number(idCtyDangKy),
        Number(idCtySanXuat),
        timestampHSD
    );

    await tx.wait(); // Chờ Blockchain xác nhận
    return tx.hash;  // Trả về mã tx_hash MỚI
}