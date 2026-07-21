CREATE DATABASE IF NOT EXISTS pharmachain_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pharmachain_db;

-- =========================================================================
-- BẢNG 1. DOANH NGHIỆP
-- =========================================================================
CREATE TABLE DoanhNghiep (
    id_doanh_nghiep INT AUTO_INCREMENT PRIMARY KEY,
    ten_doanh_nghiep VARCHAR(255) NOT NULL,
    dia_chi_doanh_nghiep VARCHAR(255) NOT NULL,
    ma_so_thue VARCHAR(50) NOT NULL UNIQUE,
    loai_hinh ENUM('DangKy', 'SanXuat', 'CaHai') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- BẢNG 2. ADMIN (Đã chuyển thành tên bảng thường không dùng dấu bọc)
-- =========================================================================
CREATE TABLE admin (
    ma_admin INT AUTO_INCREMENT PRIMARY KEY,
    ma_vi VARCHAR(42) NOT NULL UNIQUE,
    id_doanh_nghiep INT NOT NULL,
    role ENUM('admin') NOT NULL DEFAULT 'admin',
    
    CONSTRAINT FK_Admin_DoanhNghiep FOREIGN KEY (id_doanh_nghiep) 
        REFERENCES DoanhNghiep(id_doanh_nghiep) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- BẢNG 3. THUỐC (Đã bỏ hoàn toàn trường hình ảnh)
-- =========================================================================
CREATE TABLE Thuoc (
    id_thuoc INT AUTO_INCREMENT PRIMARY KEY,
    danh_muc VARCHAR(255) DEFAULT 'Chưa phân loại',
    ten_thuoc VARCHAR(255) NOT NULL,
    dang_bao_che VARCHAR(100), 
    thanh_phan TEXT NOT NULL, 
    ham_luong VARCHAR(100), 
    cong_dung TEXT NOT NULL, 
    don_vi_tinh VARCHAR(50), 
    gia_ban DECIMAL(12, 2) NOT NULL, 
    yeu_cau_ke_don ENUM('Kê đơn', 'Không kê đơn') NOT NULL DEFAULT 'Không kê đơn', 
    gioi_han_mua INT DEFAULT -1, 
    trang_thai BOOLEAN DEFAULT TRUE, 
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- BẢNG 4. LÔ THUỐC
-- =========================================================================
CREATE TABLE LoThuoc (
    id_lo INT AUTO_INCREMENT PRIMARY KEY,
    id_thuoc INT NOT NULL,
    ma_lo VARCHAR(100) NOT NULL, 
    ma_tra_cuu VARCHAR(64) NOT NULL UNIQUE, 
    tx_hash VARCHAR(66) DEFAULT NULL,
    trang_thai_blockchain ENUM('pending', 'confirmed', 'failed') DEFAULT 'pending', 
    ma_admin INT NOT NULL, 
    id_cty_dang_ky INT NOT NULL, 
    id_cty_san_xuat INT NOT NULL, 
    ngay_san_xuat DATE NOT NULL, 
    han_su_dung DATE NOT NULL, 
    so_luong_ton INT DEFAULT 0, 
    gia_nhap DECIMAL(12, 2) NOT NULL, 
    ngay_nhap_kho TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    trang_thai_bao_mat ENUM('safe', 'compromised') NOT NULL DEFAULT 'safe', 

    CONSTRAINT FK_LoThuoc_Thuoc FOREIGN KEY (id_thuoc) 
        REFERENCES Thuoc(id_thuoc) ON DELETE CASCADE,
    CONSTRAINT FK_LoThuoc_Admin FOREIGN KEY (ma_admin) 
        REFERENCES admin(ma_admin) ON DELETE RESTRICT,
    CONSTRAINT FK_LoThuoc_CtyDangKy FOREIGN KEY (id_cty_dang_ky) 
        REFERENCES DoanhNghiep(id_doanh_nghiep) ON DELETE RESTRICT,
    CONSTRAINT FK_LoThuoc_CtySanXuat FOREIGN KEY (id_cty_san_xuat) 
        REFERENCES DoanhNghiep(id_doanh_nghiep) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================================================================
-- BẢNG 5. LỊCH SỬ QUÉT MÃ QR THUỐC (Lưu IP người quét)
-- =========================================================================
CREATE TABLE IF NOT EXISTS LichSuQuet (
    id_lich_su INT AUTO_INCREMENT PRIMARY KEY,
    ma_tra_cuu VARCHAR(64) NOT NULL,
    ip_nguoi_quet VARCHAR(45) NOT NULL,
    thiet_bi TEXT DEFAULT NULL,
    thoi_gian_quet TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT FK_LichSuQuet_LoThuoc FOREIGN KEY (ma_tra_cuu) 
        REFERENCES LoThuoc(ma_tra_cuu) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- DỮ LIỆU DOANH NGHIỆP
INSERT INTO DoanhNghiep (id_doanh_nghiep, ten_doanh_nghiep, dia_chi_doanh_nghiep, ma_so_thue, loai_hinh) VALUES
(1, 'Công ty Cổ phần Dược phẩm Trung ương 1', 'Số 160 Tôn Đức Thắng, Quận Đống Đa, Hà Nội', '0100100200', 'DangKy'),
(2, 'Nhà máy Dược phẩm DHG - Chi nhánh Hậu Giang', 'KCN Tân Phú Thạnh, Huyện Châu Thành A, Hậu Giang', '1800156789', 'SanXuat'),
(3, 'Tập đoàn Dược phẩm PharmaChain Toàn Cầu', 'Khu công nghệ cao Láng Hòa Lạc, Thạch Thất, Hà Nội', '0109998888', 'CaHai'),
(4, 'Tổng Công Ty Dược Phẩm Việt Nam', 'Hà Nội', '0101010101', 'CaHai');

-- DỮ LIỆU ADMIN
INSERT INTO admin (ma_admin, ma_vi, id_doanh_nghiep, role) VALUES
(1, '0xe0FCdDCd026C179A638953a50fE900D68d903F4a', 2, 'admin'),
(2, '0x0285C31E1Cb022a4637533e75E505E811CBdF869', 1, 'admin'),
(3, '0x919613b93B5dB431255218440F2908b4EB551c7e', 4, 'admin');


-- DỮ LIỆU THUỐC (Đã chuẩn hóa 12 cột tương ứng 12 giá trị sạch)
INSERT INTO Thuoc (id_thuoc, danh_muc, ten_thuoc, dang_bao_che, thanh_phan, ham_luong, cong_dung, don_vi_tinh, gia_ban, yeu_cau_ke_don, gioi_han_mua, trang_thai) VALUES 
(1, 'Thuốc hô hấp', 'Siro HoAstex-S 90ml', 'Siro', 'Húng chanh (45.00g), Núc nác (11.25g), Tinh dầu bạch đàn (119.52mg)', '90ml', 'Siro HoAtex dùng trị ho, giảm ho trong viêm họng, viêm phế quản, viêm khí quản (viêm đường hô hấp)', 'Chai', 53000, 'Không kê đơn', 2, TRUE),
(2, 'Thuốc hô hấp', 'Siro Deslotid OPV', 'Siro', '1ml chứa: Desloratadin (0.5mg)', '60ml', 'Siro Deslotid được chỉ định dùng trong các trường hợp sau: Viêm mũi dị ứng: Hắt hơi, sổ mũi, nghẹt mũi, ngứa mũi họng và ngứa, chảy nước mắt. Phản ứng dị ứng da: Mày đay, ngứa, phát ban.', 'Hộp', 65000, 'Không kê đơn', 2, TRUE),
(3, 'Thuốc hô hấp', 'Thuốc Tocemux', 'Viên nén', '1 viên chứa: Acetylcysteine (200mg)', '10 viên', 'Dùng làm thuốc tiêu chất nhầy trong bệnh nhầy nhớt (mucoviscidosis) (xơ nang tuyến tụy), bệnh lý hô hấp có đờm nhầy quánh như trong viêm phế quản cấp và mạn, và làm sạch thường quy trong mở khí quản.', 'Hộp', 70000, 'Không kê đơn', 5, TRUE),
(4, 'Thuốc hô hấp', 'Thuốc ho người lớn OPC', 'Siro', '90ml chứa: cineol (18.00mg), Hoàng cầm (1.80 ), Bạch linh (1.80 ), Thiên môn đông (2.70 )', '90ml', 'Điều trị các bệnh viêm nhiễm đường hô hấp, các chứng ho gió, ho cảm, ho có đàm, đau họng.', 'Chai', 38000, 'Không kê đơn', 7, TRUE),
(5, 'Thuốc hô hấp', 'Viên ngậm Strepsils Throat Irritation & Cough Reckitt Benckiser', 'Viên ngậm', '1 viên chứa: Ambroxol (15mg)', '12 viên', 'Viên ngậm Strepsils Throat Irritation & Cough Reckitt Benckiser là thuốc làm tan chất nhầy trong các bệnh đường hô hấp có tăng tiết chất nhầy (long đờm). Thuốc cũng được dùng để làm lỏng các chất nhầy đặc trong các bệnh phế quản và phổi cấp và mãn tính.', 'Vỉ', 55000, 'Không kê đơn', 10, TRUE),
(6, 'Thuốc da liễu', 'Kem bôi da Ketoconazol 2% Medipharco', 'Kem bôi', '1g chứa: Ketoconazol (20mg)', '10g', 'Thuốc bôi da Ketoconazol 2% Medipharco được dùng bôi tại chỗ để điều trị các bệnh nấm ở da và niêm mạc (Candida, Trichophyton rubrum, T. mentagrophytes, Epidermophyton floccosum, Malassezia furfur...).', 'Hộp', 11000, 'Không kê đơn', 7, TRUE),
(7, 'Thuốc da liễu', 'Dung dịch PVP - IODINE 10% Danapha', 'Dung dịch', '1 chai chứa: Povidone-iodine (10%)', '20mg', 'Thuốc Pvp - Iodine 10% được chỉ định dùng trong các trường hợp sau: Sát trùng vết thương hoặc vết bỏng bề mặt, mức độ nhẹ. Điều trị hỗ trợ các tình trạng da, niêm mạc tổn thương để tránh nhiễm khuẩn. Sát trùng da, niêm mạc trước khi phẫu thuật. Lau rửa các dụng cụ y tế trước khi tiệt khuẩn', 'Chai', 8500, 'Không kê đơn', -1, TRUE),
(8, 'Thuốc da liễu', 'Thuốc bôi ngoài da Biroxime 1% ', 'Thuốc mỡ', '1g chứa: Clotrimazol (10mg)', '20g', 'Điều trị nấm da chân, nấm kẽ, nấm bẹn, lác đồng tiền. Bệnh nấm Candida do C.albicans.', 'Tuýp', 28000, 'Không kê đơn', 2, TRUE),
(9, 'Thuốc da liễu', 'Dung dịch LeoPovidone 10% ', 'Dung dịch', '1 chai chứa: Povidone-iodine (10%)', '15ml', 'Điều trị các vết thương và ngăn ngừa nhiễm trùng đối với các vi khuẩn nhạy cảm. LeoPovidone có thể được dùng cho các vết bỏng, vết trầy xước.', 'Chai', 16000, 'Không kê đơn', 10, TRUE),
(10, 'Thuốc da liễu', 'Dung dịch dùng ngoài Xanh Methylen 1% ', 'Dung dịch', '10ml chứa: Xanh Methylen (0.1g)', '17ml', 'Dung dịch dùng ngoài Xanh Methylen 1% dùng để điều trị chốc lở, viêm da mủ, điều trị nhiễm virus ngoài da.', 'Lọ', 13000, 'Không kê đơn', 3, TRUE),
(11, 'Thuốc dị ứng', 'Thuốc Cetirizin 10mg ', 'Viên nén', '1 viên chứa: Cetirizin (10mg)', '10 viên', 'Thuốc Cetirizin 10mg Trường Thọ được chỉ định điều trị triệu chứng viêm mũi dị theo mùa hoặc không theo mùa, các bệnh ngứa ngoài da do dị ứng, nổi mề đay mãn tính, bệnh viêm kết mạc dị ứng.', 'Hộp', 40000, 'Không kê đơn', 14, TRUE),
(12, 'Thuốc dị ứng', 'Thuốc Exopadin 60mg Trường Thọ', 'Viên nén', '1 viên chứa: Fexofenadin Hydroclorid (60mg)', '10 viên', 'Viêm mũi dị ứng: Exopadin được chỉ định để điều trị viêm mũi dị ứng theo mùa ở người lớn và trẻ em từ 12 tuổi trở lên. Mày đay vô căn mạn tính: Exopadin được chỉ định để điều trị các biểu hiện ngoài da không biến chứng của mày đay vô căn mạn tính ở người lớn và trẻ em từ 12 tuổi trở lên.', 'Vỉ', 60000, 'Không kê đơn', 10, TRUE),
(13, 'Thuốc dị ứng', 'Thuốc Clorpheniramin 4mg Khapharco', 'Viên nén', '1 viên chứa: Clorpheniramin maleat (4mg)', '10 viên', 'Clorpheniramin maleat được dùng để điều trị triệu chứng các bệnh dị ứng như mày đay, phù mạch, viêm mũi dị ứng, viêm màng tiếp hợp dị ứng và ngứa. Thuốc là thành phần phổ biến trong many chế phẩm để điều trị ho, cảm lạnh.', 'Vỉ', 2000, 'Không kê đơn', 7, TRUE),
(14, 'Thuốc dị ứng', 'Thuốc Allerphast 180mg Mebiphar', 'Viên nén', '1 viên chứa: Fexofenadin Hydroclorid (180mg)', '10 viên', 'Ðiều trị triệu chứng trong viêm mũi dị ứng theo mùa, mày đay mạn tính vô căn ở người lớn và trẻ em trên 6 tuổi', 'Vỉ', 2500, 'Không kê đơn', -1, TRUE),
(15, 'Thuốc dị ứng', 'Thuốc Histalong - L 5mg Dr. Reddy', 'Viên nén', '1 viên chứa: Levocetirizine (5mg)', '10 viên', 'Ðiều trị triệu chứng viêm mũi dị ứng (kể cả viêm mũi dị ứng dai dẳng) and mày đay ở người lớn và trẻ em từ 6 tuổi trở lên.', 'Vỉ', 38000, 'Không kê đơn', -1, TRUE),
(16, 'Miếng dán, cao xoa, dầu', 'Cao Sao Vàng Danapha', 'Cao xoa', 'Camphor (4.128 ), Menthol (0.656 ), Tinh dầu bạc hà (2 ), Tinh dầu tràm (1.408 ), Tinh dầu đinh hương (0.144 )', '16g', 'Cao xoa Sao Vàng chỉ định điều trị trong các trường hợp cảm cúm, đau đầu, sổ mũi, chóng mặt, đau khớp, bị muỗi và côn trùng khác đốt.', 'Hộp', 29000, 'Không kê đơn', 14, TRUE),
(17, 'Miếng dán, cao xoa, dầu', 'Dầu gừng Thái Dương', 'Dầu', '24ml chứa: Gừng (12g), Tinh dầu bạc hà (0.96ml), Methyl salicylate (4.8g), Long não (0.48ml)', '24ml', 'Đau đầu, đau lưng, đau dây thần kinh, đau vai gáy, đau nhức do phong thấp, lòng bàn chân, bàn tay lạnh giá, tê, mỏi. Cảm cúm, ngạt mũi, sổ mũi, đau bụng lạnh, buồn nôn do cảm gió, cảm lạnh, say tàu xe, ngứa do muỗi đốt.', 'Chai', 80000, 'Không kê đơn', 2, TRUE),
(18, 'Miếng dán, cao xoa, dầu', 'Dầu Khuynh Diệp OPC', 'Dầu', 'Eucalyptol (12.44g)', '25ml', 'Phòng, trị cảm cúm, sổ mũi, nghẹt mũi, ho tức ngực, đau bụng, nhức mỏi, nhức đầu, chóng mặt, buồn nôn, côn trùng đốt, trật gân, sưng.', 'Chai', 83000, 'Không kê đơn', -1, TRUE),
(19, 'Miếng dán, cao xoa, dầu', 'Cao dán Salonpas Diclofenac Patch Hisamitsu', 'Miếng dán', 'Diclofenac Sodium (15mg)', '2 miếng', 'Người lớn và trẻ em từ 15 tuổi trở lên: Dùng giảm đau, kháng viêm trong các cơn đau liên quan đến: Đau cơ.Đau vai.Đau lưng.Bầm tím.Bong gân.Căng cơ.Đau khớp.Viêm gân.Đau khuỷu tay.', 'Gói', 45000, 'Không kê đơn', -1, TRUE),
(20, 'Miếng dán, cao xoa, dầu', 'Cao dán Salonsip Gel - Patch Hisamitsu', 'Miếng dán', 'L-menthol (1g), DL-camphor (0.3g), Glycol salicylate (1.25g), Tocopherol acetat (1g)', '3 miếng', 'Cao dán Salonsip Gel - Patch chỉ định dùng cho người lớn và trẻ em từ 30 tháng tuổi trở lên dùng giảm đau, kháng viêm trong các cơn đau liên quan đến: Mỏi cơ, đau cơ, đau vai, đau lưng đơn thuần, bầm tím, bong gân, căng cơ, viêm khớp.', 'Gói', 34000, 'Không kê đơn', 7, TRUE),
(21, 'Thuốc giảm đau hạ sốt', 'Thuốc Actadol 500 Medipharco', 'Viên nén', '1 viên chứa: Paracetamol (500mg)', '10 viên', 'Paracetamol được dùng rộng rãi trong điều trị các chứng đau và sốt từ nhẹ đến vừa. Đau đầu, đau răng, đau bụng kinh, đau cơ... Thuốc có hiệu quả nhất là làm giảm đau cường độ thấp có nguồn gốc không phải nội tạng.', 'Vỉ', 500, 'Không kê đơn', 3, TRUE),
(22, 'Thuốc giảm đau hạ sốt', 'Viên nén Paracetamol Stada 500mg', 'Viên nén', '1 viên chứa: Paracetamol (500mg)', '10 viên', 'Thuốc Paracetamol 500mg được chỉ định điều trị trong các trường hợp sau: Các cơn đau từ nhẹ đến trung bình bao gồm đau đầu, đau nửa đầu, đau thần kinh đau răng, đau họng, đau do hành kinh, đau nhức.', 'Vỉ', 400, 'Không kê đơn', 10, TRUE),
(23, 'Thuốc giảm đau hạ sốt', 'Viên sủi Tovalgan Ef Trường Thọ Pharma', 'Viên sủi', '1 viên chứa: Paracetamol (500mg)', '4 viên', 'Viên nén sủi bọt Tovalgan Ef chứa Paracetamol là một chất giảm đau và hạ sốt được dùng trong các trường hợp: Nhức đầu, đau nhức do cảm lạnh hay cảm cúm, đau họng, đau do hành kinh, đau nhức cơ xương.', 'Vỉ', 40000, 'Không kê đơn', 3, TRUE),
(24, 'Thuốc giảm đau hạ sốt', 'Bột Glotadol 250 Abbott', 'Bột pha', '1 gói chứa: Paracetamol (250mg)Paracetamol', '2.5g', 'Bột pha hỗn dịch uống Glotadol có dùng hạ sốt và giảm các cơn đau do cảm cúm hay cảm lạnh thông thường, đau đầu, đau họng, mọc răng, tiêm ngừa, cắt amiđan.', 'Gói', 48000, 'Không kê đơn', 7, TRUE),
(25, 'Thuốc giảm đau hạ sốt', 'Thuốc Ameflu Không Gây Buồn Ngủ OPV', 'Viên nén', '1 viên chứa: Paracetamol (500mg), Phenylephrine hydrochloride (5mg), Caffeine (25mg)', '10 viên', 'Thuốc Ameflu được chỉ định dùng trong các trường hợp sau: Làm giảm các triệu chứng cảm lạnh và cảm cúm như nhức đầu, đau họng, đau nhức cơ thể, sung huyết mũi, đau xoang trong viêm xoang và sốt.', 'Vỉ', 1100, 'Không kê đơn', 2, TRUE);

-- DỮ LIỆU LÔ THUỐC
INSERT INTO LoThuoc (id_thuoc, ma_lo, ma_tra_cuu, ma_admin, id_cty_dang_ky, id_cty_san_xuat, ngay_san_xuat, han_su_dung, so_luong_ton, gia_nhap, trang_thai_bao_mat) VALUES 
(1, 'LO-2026-001', 'QR-HONGOC-001', 1, 1, 2, '2025-09-22', '2027-03-26', 176, 80882, 'safe'),
(2, 'LO-2026-002', 'QR-HONGOC-002', 1, 1, 2, '2025-07-19', '2030-02-18', 67, 29864, 'safe'),
(3, 'LO-2026-003', 'QR-HONGOC-003', 1, 1, 2, '2026-03-23', '2030-08-29', 195, 2433, 'safe'),
(4, 'LO-2026-004', 'QR-HONGOC-004', 1, 1, 2, '2025-09-03', '2029-12-31', 104, 31143, 'safe'),
(5, 'LO-2026-005', 'QR-HONGOC-005', 1, 1, 2, '2026-05-25', '2027-08-26', 177, 57486, 'safe'),
(6, 'LO-2026-006', 'QR-HONGOC-006', 1, 1, 2, '2025-09-21', '2029-03-31', 16, 60560, 'safe'),
(7, 'LO-2026-007', 'QR-HONGOC-007', 1, 1, 2, '2026-01-28', '2029-11-28', 3, 63166, 'safe'),
(8, 'LO-2026-008', 'QR-HONGOC-008', 1, 1, 2, '2026-04-10', '2026-09-27', 45, 62868, 'safe'),
(9, 'LO-2026-009', 'QR-HONGOC-009', 1, 1, 2, '2026-05-21', '2029-11-21', 53, 19297, 'safe'),
(10, 'LO-2026-010', 'QR-HONGOC-010', 1, 1, 2, '2025-07-02', '2026-05-11', 170, 24810, 'safe'),
(11, 'LO-2026-011', 'QR-HONGOC-011', 1, 1, 2, '2025-11-15', '2027-04-29', 21, 80975, 'safe'),
(12, 'LO-2026-012', 'QR-HONGOC-012', 1, 1, 2, '2025-12-05', '2029-04-11', 125, 68859, 'safe'),
(13, 'LO-2026-013', 'QR-HONGOC-013', 1, 1, 2, '2025-09-02', '2027-02-23', 97, 96241, 'safe'),
(14, 'LO-2026-014', 'QR-HONGOC-014', 1, 1, 2, '2026-02-28', '2027-02-13', 167, 98496, 'safe'),
(15, 'LO-2026-015', 'QR-HONGOC-015', 1, 1, 2, '2025-08-08', '2028-08-14', 21, 54830, 'safe'),
(21, 'LO-2026-016', 'QR-HONGOC-016', 1, 1, 2, '2025-10-07', '2028-04-11', 69, 49816, 'safe'),
(22, 'LO-2026-017', 'QR-HONGOC-017', 1, 1, 2, '2025-09-25', '2027-04-07', 177, 99903, 'safe'),
(23, 'LO-2026-018', 'QR-HONGOC-018', 1, 1, 2, '2026-01-26', '2029-04-22', 171, 61752, 'safe'),
(24, 'LO-2026-019', 'QR-HONGOC-019', 1, 1, 2, '2026-05-11', '2028-04-11', 6, 79506, 'safe'),
(25, 'LO-2026-020', 'QR-HONGOC-020', 1, 1, 2, '2026-01-12', '2028-11-14', 151, 72568, 'safe');
