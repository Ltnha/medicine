// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

contract DrugTracker {
    
    // Định nghĩa các trạng thái tương ứng với thẻ <select> trong HTML
    enum DrugStatus { Created, InTransit, InPharmacy, Sold }

    // Cấu trúc một mốc lịch sử dịch chuyển
    struct TrackingStep {
        DrugStatus status;    // Trạng thái số (0, 1, 2, 3)
        uint256 timestamp;    // Thời gian block ghi nhận
        address updatedBy;    // Địa chỉ ví thực hiện ký xác nhận
    }

    // Cấu trúc tổng quan của lô thuốc
    struct Drug {
        string id;            // Mã chuỗi định danh (VD: DRUG_2026_001)
        bool isInitialized;   // Đánh dấu kiểm tra tồn tại
        TrackingStep[] history; // Toàn bộ hành trình lưu trữ
    }

    // Lưu trữ thông tin trên Blockchain
    mapping(string => Drug) private drugs;

    // Sự kiện sinh ra để ứng dụng lắng nghe
    event DrugCreated(string id, address creator);
    event DrugStatusUpdated(string id, DrugStatus status, address updater);

    // 1. Hàm khởi tạo thuốc (createDrug) - Tương ứng nút "Cho Nhà Máy"
    function createDrug(string memory _id) public {
        require(!drugs[_id].isInitialized, "ID thuoc da ton tai tren he thong!");

        Drug storage newDrug = drugs[_id];
        newDrug.id = _id;
        newDrug.isInitialized = true;

        // Lưu mốc đầu tiên: Trạng thái 0 (Created)
        newDrug.history.push(TrackingStep({
            status: DrugStatus.Created,
            timestamp: block.timestamp,
            updatedBy: msg.sender
        }));

        emit DrugCreated(_id, msg.sender);
    }

    // 2. Hàm cập nhật trạng thái (updateDrug) - Tương ứng Logistics/Nhà thuốc
    function updateDrug(string memory _id, uint8 _statusIndex) public {
        require(drugs[_id].isInitialized, "Mang luoi khong tim thay ID thuoc nay!");
        require(_statusIndex <= 3, "Trang thai cap nhat khong hop le!");
        
        // Kiểm tra xem thuốc đã bán chưa, nếu bán rồi (Sold) thì không được cập nhật tiếp
        uint256 lastIndex = drugs[_id].history.length - 1;
        require(drugs[_id].history[lastIndex].status != DrugStatus.Sold, "Thuoc da duoc ban, khong the thay doi lich trinh!");

        drugs[_id].history.push(TrackingStep({
            status: DrugStatus(_statusIndex),
            timestamp: block.timestamp,
            updatedBy: msg.sender
        }));

        emit DrugStatusUpdated(_id, DrugStatus(_statusIndex), msg.sender);
    }

    // 3. Hàm kiểm tra/tra cứu lịch sử thuốc (checkDrug)
    function checkDrug(string memory _id) public view returns (bool isInitialized, TrackingStep[] memory history) {
        Drug storage d = drugs[_id];
        return (d.isInitialized, d.history);
    }
}