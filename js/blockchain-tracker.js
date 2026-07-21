// js/blockchain-tracker.js

// 1. CẤU HÌNH CONTRACT
const CONTRACT_ADDRESS = "0xd3575806964f88DF19B07f59aF06b7b6011454C7"; //thay đổi khi deploy contract mới

// ABI dạng mảng 1 lớp chuẩn
const CONTRACT_ABI =[
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
				"internalType": "bytes32",
				"name": "dataHash",
				"type": "bytes32"
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
				"internalType": "bytes32",
				"name": "dataHash",
				"type": "bytes32"
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
				"internalType": "bytes32",
				"name": "_dataHash",
				"type": "bytes32"
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

/**
 * HÀM TẠO MÃ HASH CHUẨN TỪ DỮ LIỆU
 * Quy tắc gộp chuỗi: maLo|idThuoc|idCtyDangKy|idCtySanXuat|hanSuDung
 */
function calculateBatchHash(maLo, idThuoc, idCtyDangKy, idCtySanXuat, hanSuDung) {
    const rawString = `${maLo}|${idThuoc}|${idCtyDangKy}|${idCtySanXuat}|${hanSuDung}`;
    return ethers.solidityPackedKeccak256(["string"], [rawString]);
}

// 4. HÀM ĐĂNG KÝ LÔ THUỐC LÊN BLOCKCHAIN
async function registerBatchOnBlockchain(maTraCuu, maLo, idThuoc, idCtyDangKy, idCtySanXuat, hanSuDung) {
    if (!contract) {
        const ok = await initBlockchain();
        if (!ok) throw new Error("Chưa kết nối được Smart Contract!");
    }

    // 1. Tạo mã Hash từ dữ liệu nhập vào
    const dataHash = calculateBatchHash(maLo, idThuoc, idCtyDangKy, idCtySanXuat, hanSuDung);
    console.log("Chuỗi Hash được tạo ra:", dataHash);

    // 2. Kích hoạt MetaMask gọi hàm registerBatch với dataHash
    const tx = await contract.registerBatch(maTraCuu, dataHash);

    console.log("Đang xử lý... Tx Hash:", tx.hash);
    await tx.wait(); 
    return tx.hash;
}
