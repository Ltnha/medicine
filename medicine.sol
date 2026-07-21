// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract DrugTracker {
    
    // Cấu trúc lưu trữ siêu nhẹ: Chỉ lưu mã Hash và Trạng thái bảo mật
    struct Batch {
        bytes32 dataHash;     // Mã băm Keccak-256 chứa toàn bộ dữ liệu lô thuốc
        bool isCompromised;   // false = an toàn, true = giả mạo / bị thu hồi
        bool isExist;         // Kiểm tra lô thuốc đã tồn tại trên chain chưa
    }

    // Mapping từ mã tra cứu (QR) sang dữ liệu Băm
    mapping(string => Batch) private batches;
    
    address public owner;
    mapping(address => bool) public admins;

    event BatchRegistered(string indexed maTraCuu, bytes32 dataHash);
    event BatchStatusUpdated(string indexed maTraCuu, bool isCompromised);

    constructor() {
        owner = msg.sender;
        admins[msg.sender] = true;
    }

    modifier onlyAdmin() {
        require(admins[msg.sender], "Ngoai le: Ban khong phai Admin");
        _;
    }

    function addAdmin(address _admin) public {
        require(msg.sender == owner, "Chi co Owner moi co quyen");
        admins[_admin] = true;
    }

    // Hàm đăng ký mới: Nhận trực tiếp chuỗi bytes32 dataHash đã được băm từ Frontend
    function registerBatch(
        string memory _maTraCuu,
        bytes32 _dataHash
    ) public onlyAdmin {
        require(!batches[_maTraCuu].isExist, "Loi: Ma tra cuu nay da ton tai");

        batches[_maTraCuu] = Batch({
            dataHash: _dataHash,
            isCompromised: false,
            isExist: true
        });

        emit BatchRegistered(_maTraCuu, _dataHash);
    }

    // Hàm cập nhật trạng thái thu hồi / báo động
    function updateBatchStatus(string memory _maTraCuu, bool _isCompromised) public onlyAdmin {
        require(batches[_maTraCuu].isExist, "Ma tra cuu khong ton tai");
        batches[_maTraCuu].isCompromised = _isCompromised;
        emit BatchStatusUpdated(_maTraCuu, _isCompromised);
    }

    // Hàm kiểm tra / đọc dữ liệu từ Blockchain
    function getBatch(string memory _maTraCuu) public view returns (
        bytes32 dataHash,
        bool isCompromised
    ) {
        require(batches[_maTraCuu].isExist, "Ma tra cuu khong ton tai");
        Batch memory b = batches[_maTraCuu];
        return (b.dataHash, b.isCompromised);
    }
}