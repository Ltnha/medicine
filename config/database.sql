-- 1. BẢNG DOANH NGHIỆP
CREATE TABLE DoanhNghiep (
    id_doanh_nghiep INT AUTO_INCREMENT PRIMARY KEY,
    ten_doanh_nghiep VARCHAR(255) NOT NULL,
    dia_chi_doanh_nghiep VARCHAR(255) NOT NULL,
    ma_so_thue VARCHAR(50) NOT NULL UNIQUE,
    loai_hinh ENUM('DangKy', 'SanXuat', 'CaHai') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. BẢNG ADMIN (Tài khoản nhân viên/quản trị sở hữu ví Web3)
CREATE TABLE `admin` (
    `ma_admin` INT AUTO_INCREMENT PRIMARY KEY,
    `ma_vi` VARCHAR(42) NOT NULL UNIQUE,
    `id_doanh_nghiep` INT NOT NULL, -- Admin này là nhân viên của công ty nào?
    `role` ENUM('Admin_DoanhNghiep', 'Super_Admin') NOT NULL DEFAULT 'Admin_DoanhNghiep',
    
    -- Khóa ngoại liên kết tới doanh nghiệp sở hữu ví
    CONSTRAINT FK_Admin_DoanhNghiep FOREIGN KEY (id_doanh_nghiep) 
        REFERENCES DoanhNghiep(id_doanh_nghiep)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 3. BẢNG THUOC
CREATE TABLE Thuoc (
    id_thuoc INT AUTO_INCREMENT PRIMARY KEY, 
    ten_thuoc VARCHAR(255) NOT NULL,
    dang_bao_che VARCHAR(100) NOT NULL, -- Ví dụ: Viên nén bao phim, Viên nang mềm
    cong_dung TEXT,
    thanh_phan TEXT,
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. BẢNG LOTHUOC (Chi tiết từng đợt sản xuất được ký duyệt đưa lên chuỗi)
CREATE TABLE LoThuoc (
    id_lo INT AUTO_INCREMENT PRIMARY KEY, 
    so_lo VARCHAR(100) NOT NULL,
    id_thuoc INT NOT NULL,              -- Thuộc loại thuốc nào trong danh mục
    ma_admin INT NOT NULL,              -- Địa chỉ ví cụ thể nào đã bấm nút ký duyệt lô này[cite: 4]
    
    -- Liên kết định danh công ty chịu trách nhiệm thay vì lưu chuỗi text trùng lặp[cite: 4]
    id_cty_dang_ky INT NOT NULL,        -- Doanh nghiệp đăng ký[cite: 4]
    id_cty_san_xuat INT NOT NULL,       -- Nhà máy thực hiện sản xuất[cite: 4]
    
    -- Thông tin vận hành lô hàng
    ngay_san_xuat DATE NOT NULL,
    han_su_dung DATE NOT NULL,
    ngay_nhap_kho TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Trạng thái đối chiếu dữ liệu với Smart Contract
    trang_thai_bao_mat ENUM('safe', 'compromised') NOT NULL DEFAULT 'safe',[cite: 4]

    -- THIẾT LẬP CÁC KHÓA NGOẠI RÀNG BUỘC CHẶT CHẼ
    -- 1. Liên kết danh mục thuốc
    CONSTRAINT FK_LoThuoc_Thuoc FOREIGN KEY (id_thuoc) 
        REFERENCES Thuoc(id_thuoc) 
        ON DELETE CASCADE,[cite: 4]
        
    -- 2. Liên kết nhân viên ký duyệt (Không cho xóa tài khoản nếu đã từng ký lô hàng để bảo toàn lịch sử)
    CONSTRAINT FK_LoThuoc_Admin FOREIGN KEY (ma_admin) 
        REFERENCES `admin`(`ma_admin`)
        ON DELETE RESTRICT,[cite: 4]
        
    -- 3. Liên kết đến công ty đăng ký sở hữu lô
    CONSTRAINT FK_LoThuoc_CtyDangKy FOREIGN KEY (id_cty_dang_ky) 
        REFERENCES DoanhNghiep(id_doanh_nghiep)
        ON DELETE RESTRICT,
        
    -- 4. Liên kết đến nhà máy trực tiếp sản xuất lô thuốc
    CONSTRAINT FK_LoThuoc_CtySanXuat FOREIGN KEY (id_cty_san_xuat) 
        REFERENCES DoanhNghiep(id_doanh_nghiep)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;