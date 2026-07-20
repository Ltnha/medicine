create DATABASE pharmachain_db;
use pharmachain_db;
-- 1. BẢNG DOANH NGHIỆP
CREATE TABLE DoanhNghiep (
    id_doanh_nghiep INT AUTO_INCREMENT PRIMARY KEY,
    ten_doanh_nghiep VARCHAR(255) NOT NULL,
    dia_chi_doanh_nghiep VARCHAR(255) NOT NULL,
    ma_so_thue VARCHAR(50) NOT NULL UNIQUE,
    loai_hinh ENUM('DangKy', 'SanXuat', 'CaHai') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. BẢNG ADMIN
CREATE TABLE `admin` (
    `ma_admin` INT AUTO_INCREMENT PRIMARY KEY,
    `ma_vi` VARCHAR(42) NOT NULL UNIQUE,
    `id_doanh_nghiep` INT NOT NULL,
    `role` ENUM('admin') NOT NULL DEFAULT 'admin',
    
    CONSTRAINT FK_Admin_DoanhNghiep FOREIGN KEY (id_doanh_nghiep) 
        REFERENCES DoanhNghiep(id_doanh_nghiep)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 3. BẢNG THUOC
CREATE TABLE Thuoc (
    id_thuoc INT AUTO_INCREMENT PRIMARY KEY, 
    ten_thuoc VARCHAR(255) NOT NULL,
    dang_bao_che VARCHAR(100) NOT NULL,
    cong_dung TEXT,
    thanh_phan TEXT,
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. BẢNG LOTHUOC
CREATE TABLE LoThuoc (
    id_lo INT AUTO_INCREMENT PRIMARY KEY, 
    so_lo VARCHAR(100) NOT NULL,
    id_thuoc INT NOT NULL,
    ma_admin INT NOT NULL,
    

    id_cty_dang_ky INT NOT NULL,
    id_cty_san_xuat INT NOT NULL,
    
    ngay_san_xuat DATE NOT NULL,
    han_su_dung DATE NOT NULL,
    ngay_nhap_kho TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    trang_thai_bao_mat ENUM('safe', 'compromised') NOT NULL DEFAULT 'safe',

    CONSTRAINT FK_LoThuoc_Thuoc FOREIGN KEY (id_thuoc) 
        REFERENCES Thuoc(id_thuoc) 
        ON DELETE CASCADE,

    CONSTRAINT FK_LoThuoc_Admin FOREIGN KEY (ma_admin) 
        REFERENCES `admin`(`ma_admin`)
        ON DELETE RESTRICT,
        
    CONSTRAINT FK_LoThuoc_CtyDangKy FOREIGN KEY (id_cty_dang_ky) 
        REFERENCES DoanhNghiep(id_doanh_nghiep)
        ON DELETE RESTRICT,
        
    CONSTRAINT FK_LoThuoc_CtySanXuat FOREIGN KEY (id_cty_san_xuat) 
        REFERENCES DoanhNghiep(id_doanh_nghiep)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admin` (ma_admin, ma_vi, id_doanh_nghiep, role)
VALUES (1, '0x95222294dd7278aa3ddd389cc1e1d165cc4bafe5', 1, 'admin');