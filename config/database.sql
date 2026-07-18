CREATE TABLE Thuoc (
    id_thuoc INT AUTO_INCREMENT PRIMARY KEY, -- Mã tự tăng
    ten_thuoc VARCHAR(255) NOT NULL,
    cong_dung TEXT,
    thanh_phan TEXT,
    nha_san_xuat VARCHAR(255),
    noi_san_xuat VARCHAR(255),
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE LoThuoc (
    id_lo INT AUTO_INCREMENT PRIMARY KEY, -- Mã tự tăng
    so_lo VARCHAR(100) NOT NULL,
    id_thuoc INT NOT NULL,                -- Khóa ngoại liên kết với bảng Thuoc
    ngay_san_xuat DATE NOT NULL,
    han_su_dung DATE NOT NULL,
    ngay_nhap_kho TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Thiết lập khóa ngoại
    CONSTRAINT FK_LoThuoc_Thuoc FOREIGN KEY (id_thuoc) 
        REFERENCES Thuoc(id_thuoc) 
        ON DELETE CASCADE -- Nếu xóa thuốc, các lô liên quan sẽ tự động xóa theo
);