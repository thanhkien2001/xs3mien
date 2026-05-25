# 📝 GHI CHÚ DỮ LIỆU CẦN BỔ SUNG CHO TRANG CHỦ

## ✅ DỮ LIỆU ĐÃ CÓ TRONG BACKEND (IndexController.php)

1. **$kqxsByRegion** - Kết quả xổ số 3 miền (XSMB, XSMT, XSMN)
   - `date`: Ngày quay thưởng
   - `rows`: Danh sách kết quả theo tỉnh
   - `dauDuoi`: Dữ liệu đầu đuôi lô tô

2. **$megaResult** - Kết quả Mega 6/45
   - `draw_number`: Kỳ quay
   - `draw_date`: Ngày quay
   - `jackpot_amount`: Giá trị Jackpot
   - `numbers`: Các con số trúng thưởng

3. **$powerResult** - Kết quả Power 6/55
   - `draw_number`: Kỳ quay
   - `draw_date`: Ngày quay
   - `jackpot_amount`: Giá trị Jackpot
   - `numbers`: Các con số trúng thưởng

4. **$scheduleSlots** - Lịch mở thưởng (đã có trong view cũ)

## ❌ DỮ LIỆU CẦN BỔ SUNG TRONG BACKEND

### 1. **Soi Cầu 247 Hôm Nay** (Phần đầu trang)
```php
// Cần thêm vào IndexController::indexAction()
$soiCau247 = [
    'lo_kep_dep_nhat' => '77 - 33',
    'cau_2_nhay' => ['43,34', '72,27', '51,15', '30,03', '67,76'],
    'cau_3_cang_lotto' => ['094', '738', '274'],
    'giai_dac_biet_cham' => ['2', '7']
];
$this->view->setVar('soiCau247', $soiCau247);
```

### 2. **Bài Viết Dự Đoán** (Mục dự đoán 3 miền)
```php
// Cần tạo Model hoặc lấy từ database
$duDoanArticles = [
    'xsmb' => [
        'title' => 'Dự đoán XSMB ' . date('d/m/Y'),
        'url' => '/du-doan-xsmb-...',
        'image' => 'public/media/thumb/Thumb-Cate/du-doan-MB-410x215.jpg',
        'excerpt' => 'Dự đoán XSMB...'
    ],
    'xsmt' => [...],
    'xsmn' => [...]
];
```

### 3. **Rồng Bạch Kim**
```php
$rongBachKim = [
    'cau_lo_dep' => [
        ['04', '40'], ['85', '58'], ['35', '53'], ['71', '17'], ['15', '51'],
        ['98', '89'], ['41', '14'], ['05', '50'], ['80', '08'], ['72', '27']
    ],
    'cau_dac_biet' => [
        ['72', '27'], ['78', '87'], ['83', '38'], ['82', '28'], ['97', '79'],
        ['91', '19'], ['52', '25'], ['74', '47'], ['95', '59'], ['26', '62']
    ]
];
```

### 4. **Soi Cầu XSMB Chính Xác 100**
```php
$soiCauXSMB = [
    '0' => ['05', '04', '06'],
    '1' => ['10', '14'],
    '2' => ['21', '23'],
    '3' => ['33', '39', '37'],
    '4' => ['40', '41'],
    '5' => ['55', '57', '51'],
    '6' => ['67', '60', '63'],
    '7' => ['70', '75'],
    '8' => ['89', '80', '86'],
    '9' => ['92', '91']
];
```

### 5. **Lô Kép Khung 3 Ngày**
```php
$loKepKhung3Ngay = [
    ['lo_kep' => '66-99', 'ngay' => '27/12 - 29/12/2025', 'ket_qua' => 'Chờ kết quả'],
    ['lo_kep' => '44-22', 'ngay' => '25/12 - 26/12/2025', 'ket_qua' => 'Ăn lô 22 ngày 2'],
    ['lo_kep' => '66-88', 'ngay' => '23/12 - 24/12/2025', 'ket_qua' => 'Ăn lô 66 ngày 2'],
    // ... thêm các dòng khác
];
```

### 6. **Cầu Bạch Thủ Chạy 4 Ngày** (Thống kê phức tạp)
```php
$cauBachThuChay4Ngay = [
    '0' => ['00' => 1, '02' => 1, '04' => 2, '05' => 1, '09' => 2],
    '1' => ['11' => 1],
    '2' => ['22' => 1, '28' => 1],
    // ... các đầu số khác
];
```

### 7. **Top Nhà Cái** (Sidebar phải)
```php
$topNhaCai = [
    [
        'rank' => 1,
        'name' => 'Me88 THƯỞNG 200% LÊN ĐẾN 15 TRIỆU',
        'image' => 'public/media/banner/me88/428x428.png',
        'rating' => 5,
        'bonus' => 'MIỄN PHÍ 100K TÂN THỦ',
        'url_cuoc' => '#',
        'url_review' => '#'
    ]
];
```

### 8. **Danh Sách Bài Viết Dự Đoán** (Sidebar phải)
```php
// Cần tạo model Article hoặc lấy từ DB
$duDoanArticlesList = Article::find([
    'conditions' => 'category = "du-doan" AND status = "published"',
    'order' => 'created_at DESC',
    'limit' => 6
]);
```

### 9. **Giải Mã Giấc Mơ** (Sidebar phải)
```php
$giacMoArticles = Article::find([
    'conditions' => 'category = "giac-mo" AND status = "published"',
    'order' => 'created_at DESC',
    'limit' => 5
]);
```

### 10. **Banner Quảng Cáo** (Sidebar trái/phải)
```php
$banners = [
    'sidebar_left' => [
        'image' => 'public/media/banner/left/soicauxsmb.png',
        'url' => 'https://soicauxsmb68.com/',
        'alt' => 'soicauxsmb'
    ],
    'sidebar_right' => [
        'image' => 'public/media/banner/right/soi-cau-3-mien.png',
        'url' => '/soi-cau-3-mien-du-doan-xo-so-3-mien.html',
        'alt' => 'soicau247'
    ]
];
```

## 🔧 HƯỚNG DẪN BỔ SUNG

### Bước 1: Tạo các Model cần thiết
- `Articles` - Quản lý bài viết dự đoán, giấc mơ
- `Prediction` - Quản lý dữ liệu soi cầu hàng ngày
- `BachThuStatistics` - Thống kê bạch thủ
- `LoKepTracking` - Theo dõi lô kép

### Bước 2: Cập nhật IndexController
```php
public function indexAction()
{
    // ... code hiện tại ...
    
    // Bổ sung dữ liệu mới
    $this->view->setVar('soiCau247', $this->getSoiCau247Data());
    $this->view->setVar('duDoanArticles', $this->getDuDoanArticles());
    $this->view->setVar('rongBachKim', $this->getRongBachKimData());
    $this->view->setVar('soiCauXSMB', $this->getSoiCauXSMBData());
    $this->view->setVar('loKepKhung3Ngay', $this->getLoKepKhung3NgayData());
    $this->view->setVar('cauBachThuChay4Ngay', $this->getCauBachThuData());
    $this->view->setVar('topNhaCai', $this->getTopNhaCaiData());
    $this->view->setVar('giacMoArticles', $this->getGiacMoArticles());
    $this->view->setVar('banners', $this->getBannersData());
}
```

### Bước 3: Tạo các phương thức helper
Tạo các phương thức private trong IndexController để lấy dữ liệu:
- `getSoiCau247Data()`
- `getDuDoanArticles()`
- `getRongBachKimData()`
- etc...

## 📊 DATABASE TABLES CẦN TẠO

### Table: `predictions` (Dữ liệu soi cầu hàng ngày)
```sql
CREATE TABLE predictions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prediction_date DATE NOT NULL,
    region VARCHAR(10), -- XSMB, XSMT, XSMN
    type VARCHAR(50), -- 'lo_kep', 'cau_2_nhay', 'cau_3_cang', etc
    numbers TEXT, -- JSON array
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Table: `articles` (Bài viết)
```sql
CREATE TABLE articles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    slug VARCHAR(255),
    category VARCHAR(50), -- 'du-doan', 'giac-mo', 'bi-kip'
    excerpt TEXT,
    content TEXT,
    image VARCHAR(255),
    status VARCHAR(20) DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Table: `lo_kep_tracking` (Theo dõi lô kép)
```sql
CREATE TABLE lo_kep_tracking (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lo_kep VARCHAR(10), -- '66-99'
    start_date DATE,
    end_date DATE,
    result VARCHAR(100), -- 'Chờ kết quả', 'Ăn lô 22 ngày 2', 'Trượt'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Table: `bach_thu_statistics` (Thống kê bạch thủ)
```sql
CREATE TABLE bach_thu_statistics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    number VARCHAR(2),
    frequency INT,
    last_appeared DATE,
    days INT DEFAULT 4, -- Số ngày chạy
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 🚀 PRIORITY

**HIGH PRIORITY** (Cần làm ngay):
1. ✅ Kết quả xổ số 3 miền (ĐÃ CÓ)
2. ❌ Dữ liệu Soi cầu 247 hôm nay
3. ❌ Bài viết dự đoán 3 miền
4. ❌ Rồng Bạch Kim

**MEDIUM PRIORITY**:
5. ❌ Soi cầu XSMB 100
6. ❌ Lô kép khung 3 ngày
7. ❌ Top nhà cái

**LOW PRIORITY**:
8. ❌ Cầu bạch thủ chạy 4 ngày
9. ❌ Banner quảng cáo
10. ❌ Giải mã giấc mơ

## 📝 LƯU Ý

- Tất cả dữ liệu nên được cache (Redis/Memcached) để tăng performance
- Sử dụng PHQL và query cache như code hiện tại
- Cập nhật dữ liệu soi cầu hàng ngày thông qua cronjob
- Dữ liệu tĩnh (như top nhà cái, banner) có thể hard-code tạm hoặc lưu trong config

