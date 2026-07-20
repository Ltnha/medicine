// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract DrugTracker {
    
    // Định nghĩa cấu trúc một Lô thuốc tương ứng với nhu cầu truy xuất
    struct Batch {
        string maLo;
        uint256 idThuoc;
        uint256 idCtyDangKy;
        uint256 idCtySanXuat;
        uint256 hanSuDung;     // Lưu dưới dạng timestamp (uint256)
        bool isCompromised;   // false = an toàn, true = giả mạo
        bool isExist;         // Kiểm tra lô thuốc đã tồn tại chưa
    }

    // Dùng Mapping để tìm kiếm Lô thuốc nhanh chóng bằng ma_tra_cuu
    mapping(string => Batch) private batches;
    
    // Địa chỉ của người quản trị (Deployer) có quyền quản lý danh sách Admin
    address public owner;
    mapping(address => bool) public admins;

    // Các sự kiện (Events) để Web Frontend lắng nghe
    event BatchRegistered(string indexed maTraCuu, string maLo, uint256 idThuoc);
    event BatchStatusUpdated(string indexed maTraCuu, bool isCompromised);

    constructor() {
        owner = msg.sender;
        admins[msg.sender] = true; // Khởi tạo owner cũng là admin
    }

    // 3 hàm xử lý

    //Hàm phân quyền & Quản lý Admin 
    modifier onlyAdmin() {
        require(admins[msg.sender], "Ngoai le: Ban khong phai Admin");
        _; //vị trí giữ chỗ
    }

    function addAdmin(address _admin) public {
        require(msg.sender == owner, "Chi co Owner moi co quyen");
        admins[_admin] = true;
    }

    //Hàm thêm lô thuốc mới
    function registerBatch(
        string memory _maTraCuu,
        string memory _maLo,
        uint256 _idThuoc,
        uint256 _idCtyDangKy,
        uint256 _idCtySanXuat,
        uint256 _hanSuDung
    ) public onlyAdmin {
        require(!batches[_maTraCuu].isExist, "Loi: Ma tra cuu nay da ton tai");

        batches[_maTraCuu] = Batch({
            maLo: _maLo,
            idThuoc: _idThuoc,
            idCtyDangKy: _idCtyDangKy,
            idCtySanXuat: _idCtySanXuat,
            hanSuDung: _hanSuDung,
            isCompromised: false,
            isExist: true
        });

        emit BatchRegistered(_maTraCuu, _maLo, _idThuoc);
    }

    // Hàm cập nhật trạng thái bảo mật (Báo động giả mạo / Thu hồi lô thuốc)
    function updateBatchStatus(string memory _maTraCuu, bool _isCompromised) public onlyAdmin {
        require(batches[_maTraCuu].isExist, "Ma tra cuu khong ton tai");
        
        batches[_maTraCuu].isCompromised = _isCompromised;
        
        emit BatchStatusUpdated(_maTraCuu, _isCompromised);
    }
    //Hàm kiểm tra / truy xuất nguồn gốc
    function getBatch(string memory _maTraCuu) public view returns (
        string memory maLo,
        uint256 idThuoc,
        uint256 idCtyDangKy,
        uint256 idCtySanXuat,
        uint256 hanSuDung,
        bool isCompromised
    ) {
        require(batches[_maTraCuu].isExist, "Ma tra cuu khong ton tai");
        Batch memory b = batches[_maTraCuu];
        return (b.maLo, b.idThuoc, b.idCtyDangKy, b.idCtySanXuat, b.hanSuDung, b.isCompromised);
    }
}