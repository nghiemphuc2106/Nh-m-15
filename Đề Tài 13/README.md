# 🔗 Link Checker - Công cụ kiểm tra liên kết và tài nguyên bị lỗi trên website

> **Đề tài 13** | Môn học: Các công nghệ nguồn mở trong phát triển ứng dụng web
> **Kiến trúc:** PHP Thuần (Pure PHP - Không dùng framework bên ngoài) | Cơ sở dữ liệu: MySQL/MariaDB | Frontend: HTML5, CSS3, JavaScript Vanilla.

---

## 📖 1. Giới thiệu tổng quan

**Link Checker** là công cụ dành cho quản trị viên, lập trình viên và kiểm thử viên nhằm tự động rà soát toàn diện website để phát hiện:

* **Liên kết bị lỗi (Broken Links / HTTP 404, 500,...)**
* **Hình ảnh bị hỏng (Broken Images)**
* **Thẻ neo không tồn tại (Missing Anchors `#id`)**
* **Hết thời gian chờ (Request Timeout)**
* **Chuyển hướng ra ngoài phạm vi cho phép (Redirect Out of Scope)**

Hệ thống hoạt động dựa trên thuật toán **BFS (Breadth-First Search)** tự viết, kiểm soát chặt chẽ phạm vi miền, loại bỏ nguy cơ lặp vòng vô hạn, hỗ trợ kiểm tra lại (re-check) và so sánh trực quan sự khác biệt giữa 2 lần kiểm tra.

---

## 👥 2. Thành viên nhóm thực hiện

| STT | Họ và tên   |  MSSV  | Vai trò & Phần việc phụ trách                                       |
| :-: | -------------- | :----: | ------------------------------------------------------------------------ |
|  1  | (Họ và tên) | (MSSV) | Trưởng nhóm - Thiết kế kiến trúc, Database, Core Crawler          |
|  2  | (Họ và tên) | (MSSV) | Thành viên - Backend Services, LinkChecker, Thuật toán so sánh      |
|  3  | (Họ và tên) | (MSSV) | Thành viên - Frontend giao diện, Test Website & Kịch bản kiểm thử |

---

## ⚙️ 3. Yêu cầu hệ thống

* **Hệ điều hành:** Windows 10/11 (khuyến nghị) hoặc Linux/macOS.
* **Môi trường web server:** [XAMPP](https://www.apachefriends.org/) (hỗ trợ cài đặt trên ổ `C:\xampp` hoặc `E:\xampp`).
* **PHP:** Phiên bản 8.0 trở lên (yêu cầu các extension: `curl`, `dom`, `mbstring`, `pdo_mysql`, `json`).
* **Cơ sở dữ liệu:** MySQL 5.7+ hoặc MariaDB 10.4+.
* **Trình duyệt:** Google Chrome, Microsoft Edge, Mozilla Firefox.

---

## 📁 4. Cấu trúc thư mục dự án

```text
e:\Đề Tài 13\
├── config/
│   ├── app.php                    # Tham số mặc định của bộ Crawler
│   ├── database.php               # Cấu hình kết nối MySQL/MariaDB chính thức
│   └── database.example.php       # File mẫu cấu hình CSDL dự phòng
├── database/
│   ├── migrate.php                # Script PHP tạo 6 bảng CSDL chuẩn
│   ├── seed.php                   # Script PHP nạp dữ liệu mẫu ban đầu
│   └── schema.sql                 # File mã nguồn SQL thuần (dùng import phpMyAdmin)
├── public/                        # Thư mục Web Root (chứa tài nguyên công khai)
│   ├── css/style.css              # Giao diện CSS hiện đại (Dark/Light card, responsive)
│   ├── js/app.js                  # Xử lý modal trang nguồn, scope preview, AJAX recheck
│   ├── .htaccess                  # Cấu hình rewrite URL cho Apache XAMPP
│   └── index.php                  # Front Controller & nạp class tự động
├── src/
│   ├── Core/                      # Các thành phần cốt lõi của hệ thống
│   │   ├── Auth.php               # Xác thực đăng nhập, phân quyền Admin/User
│   │   ├── Database.php           # Singleton PDO quản lý kết nối CSDL
│   │   └── Router.php             # Bộ định tuyến URL linh hoạt hỗ trợ tham số {id}
│   ├── Models/                    # Tương tác cơ sở dữ liệu
│   │   ├── Finding.php            # Quản lý lỗi phát hiện, truy vết nguồn, Re-check
│   │   ├── ScanRun.php            # Quản lý từng lần quét website
│   │   └── Site.php               # CRUD cấu hình website kiểm tra
│   ├── Services/                  # Nghiệp vụ cốt lõi (Không dùng thư viện ngoài)
│   │   ├── Crawler.php            # Thuật toán BFS duyệt HTML, quản lý hàng đợi & giới hạn
│   │   ├── HtmlParser.php         # Bóc tách DOM thẻ a, img, link, script và anchor ID
│   │   ├── LinkChecker.php        # Kiểm tra mạng qua cURL, phân loại mã lỗi & timeout
│   │   ├── ScanComparator.php     # Thuật toán so sánh đối chiếu 2 lần kiểm tra
│   │   └── UrlNormalizer.php      # Chuẩn hóa URL, xử lý đường dẫn tương đối (RFC 3986)
│   ├── Controllers/               # Bộ điều khiển
│   │   ├── AuthController.php     # Đăng nhập, đăng ký, đăng xuất
│   │   ├── DashboardController.php# Thống kê tổng quan số liệu
│   │   ├── ScanController.php     # Khởi tạo quét, xem kết quả, so sánh, recheck
│   │   └── SiteController.php     # Khai báo và quản lý danh sách website
│   └── Views/                     # Tầng giao diện người dùng (Blade-like PHP Views)
│       ├── auth/                  # Form đăng nhập, đăng ký
│       ├── dashboard/             # Bảng điều khiển thống kê tổng thể
│       ├── errors/                # Giao diện trang 404 thân thiện
│       ├── layouts/               # Layout khung ứng dụng chung (Sidebar, Navbar)
│       ├── scans/                 # Chi tiết báo cáo lần quét, so sánh 2 lần quét
│       └── sites/                 # Danh sách website, form thêm mới và thông tin site
├── test-website/                  # Website mẫu độc lập (chạy cổng 8888 để kiểm thử)
│   ├── blog/                      # Các bài viết mẫu
│   ├── branches/                  # Trang chi nhánh
│   ├── gallery/                   # Trang dự án thư viện ảnh
│   ├── products/                  # Trang sản phẩm
│   ├── services/                  # Trang dịch vụ
│   ├── images/                    # Thư mục hình ảnh
│   └── *.html                     # Hơn 20 trang HTML tĩnh được cấy sẵn các lỗi mẫu
├── tests/                         # Bộ kiểm thử tự động (Automated Test Suite)
│   ├── Integration/
│   │   └── FullScanTest.php       # Kiểm thử toàn trình quá trình quét
│   ├── Unit/
│   │   ├── CrawlerTest.php        # Kiểm thử thuật toán bóc tách và hàng đợi
│   │   └── UrlNormalizerTest.php  # Kiểm thử các trường hợp chuẩn hóa URL (15 cases)
│   └── run_tests.php              # Test Runner chạy toàn bộ kiểm thử
├── run_all.bat                    # Script 1-click khởi động hệ thống (cổng 8080 & 8888)
├── run_xampp.bat                  # Script khởi động chạy trực tiếp qua Apache XAMPP
├── setup_database.bat             # Script 1-click khởi tạo Database tự động
├── run_app.bat                    # Chạy riêng Main Web App (cổng 8080)
├── run_test_website.bat           # Chạy riêng Test Website (cổng 8888)
└── README.md                      # Tài liệu hướng dẫn sử dụng và vận hành
```

---

## 🚀 5. Hướng dẫn cài đặt & Khởi chạy

### Bước 1: Khởi động MySQL trên XAMPP

1. Mở ứng dụng **XAMPP Control Panel**.
2. Nhấn nút **Start** ở dòng **MySQL** (cổng `3306` chuyển sang màu xanh lá).
   *(Nếu muốn chạy qua Apache thì bấm Start cả Apache).*

---

### Bước 2: Khởi tạo Cơ sở dữ liệu

Bạn có thể chọn **1 trong 2 cách** sau:

* **Cách A (1-click tự động - Khuyên dùng):**Click đúp chuột vào file [setup_database.bat](setup_database.bat). Script sẽ tự động nhận diện đường dẫn PHP (hỗ trợ cả `E:\xampp` lẫn `C:\xampp`), tạo database `link_checker`, tạo đầy đủ 6 bảng và nạp sẵn các tài khoản mẫu.
* **Cách B (Nhập qua phpMyAdmin):**
  Mở trình duyệt vào `http://localhost/phpmyadmin` ➔ Bấm vào tab **Import** (Nhập) ➔ Chọn file [database/schema.sql](database/schema.sql) ➔ Bấm **Go**.

---

### Bước 3: Khởi chạy ứng dụng

#### 🟢 CÁCH 1: Khởi chạy 1-Click (Khuyên dùng - Nhanh nhất)

1. **Click đúp chuột** vào file: [run_all.bat](run_all.bat)
2. Hệ thống sẽ tự động bật đồng thời:
   * **Test Website** tại: `http://localhost:8888`
   * **Link Checker App** tại: `http://localhost:8080`
3. Trình duyệt tự động mở:
   👉 **`http://localhost:8080`**

---

#### 🔵 CÁCH 2: Khởi chạy qua Apache XAMPP

1. Trong XAMPP Control Panel, bấm **Start** cả **Apache** và **MySQL**.
2. **Click đúp chuột** vào file: [run_xampp.bat](run_xampp.bat)
3. Mở trình duyệt và truy cập:
   👉 **`http://localhost/link-checker`**

---

## 🔑 6. Tài khoản đăng nhập mặc định

Hệ thống đã nạp sẵn 3 tài khoản với các quyền tương ứng:

|     Vai trò     | Email đăng nhập     |   Mật khẩu   | Quyền hạn                                                                     |
| :--------------: | :--------------------- | :-------------: | :------------------------------------------------------------------------------ |
| **Admin** | `admin@example.com`  | `password123` | Toàn quyền xem và quản lý tất cả website, lịch sử quét của mọi user |
|  **User**  | `user@example.com`   | `password123` | Quản lý website và lần quét do tài khoản tạo                            |
| **Tester** | `tester@example.com` | `password123` | Tài khoản dành cho kiểm thử viên                                          |

---

## 💡 7. Chi tiết các chức năng cốt lõi

### 1. Khai báo phạm vi kiểm tra (Scope & Limits)

* **Seed URL:** Trang bắt đầu duyệt (ví dụ: `http://localhost:8888`).
* **Allowed Domains:** Danh sách tên miền được phép cào dữ liệu HTML để duyệt tiếp.
* **Xem trước phạm vi (Scope Preview):** Phân tích tức thì domain hợp lệ và domain ngoại lai ngay khi gõ URL.
* **Cấu hình giới hạn an toàn:**
  * `max_pages`: Số trang tối đa được quét (mặc định 30).
  * `max_depth`: Độ sâu tối đa từ Seed URL (mặc định 4).
  * `timeout`: Thời gian chờ tối đa cho 1 request (mặc định 10s).
  * `max_total_time`: Tổng thời gian tối đa cho cả đợt quét (mặc định 60s).

### 2. Thuật toán duyệt & Thu thập liên kết

* Sử dụng thuật toán duyệt theo chiều rộng **BFS (Breadth-First Search)** kết hợp hàng đợi FIFO `[$url, $depth]`.
* **Tránh lặp vòng vô hạn ($A \rightarrow B \rightarrow A$):** Băm chuẩn hóa URL loại bỏ phần `#fragment` và lưu vào mảng đánh dấu `$visited[$url]`.
* **Xử lý đa nguồn trỏ về 1 đích ($A, B \rightarrow X$):** Lưu vết đầy đủ tất cả trang nguồn vào bảng `links`, nhưng chỉ gửi request kiểm tra mạng tới đích $X$ đúng **1 lần duy nhất** nhờ bộ đệm in-memory cache `$checkedUrls`.
* **Chuẩn hóa đường dẫn tương đối (RFC 3986):** Xử lý triệt để các dạng `../`, `./`, `/root-path`, `#anchor`, query string.

### 3. Kiểm tra tài nguyên & Phân loại lỗi

Hệ thống sử dụng cURL và phân loại rành mạch các trạng thái:

* `http_error`: Lỗi mã phản hồi HTTP (404 Not Found, 403 Forbidden, 500 Internal Server Error,...).
* `broken_image`: Ảnh lỗi (HTTP lỗi hoặc Header `Content-Type` trả về không phải định dạng `image/*`).
* `missing_anchor`: Thẻ neo `#section-id` không tồn tại trong thuộc tính `id` hoặc `name` của mã HTML trang đích.
* `timeout`: Bắt mã cURL 28 (`CURLE_OPERATION_TIMEOUTED`), thông báo: *"Hết thời gian chờ - chưa xác định được phản hồi"*, **tuyệt đối không quy kết thành lỗi 404**.
* `redirect_out_of_scope`: Phát hiện chuyển hướng ra khỏi phạm vi tên miền cho phép.

### 4. Quản lý báo cáo, Truy vết nguồn & Re-check

* Lưu lịch sử từng đợt quét với các số liệu thống kê trực quan.
* Bộ lọc đa năng theo loại lỗi (`404`, `ảnh hỏng`, `anchor`, `timeout`) và loại liên kết (`anchor`, `image`, `stylesheet`, `script`).
* **Modal Xem trang nguồn (Source Pages Modal):** Bấm xem danh sách tất cả các trang web đang chứa liên kết bị hỏng.
* **Kiểm tra lại (Re-check):** Bấm nút kiểm tra lại tức thì một lỗi cụ thể mà không cần quét lại cả website.

### 5. So sánh 2 lần quét (Comparison Engine)

So sánh đối chiếu giữa 2 lần kiểm tra khác nhau của một website, tự động phân nhóm kết quả thành 4 mục rõ ràng:

1. 🔴 **Lỗi mới xuất hiện (New Errors):** Lỗi có ở lần quét sau nhưng chưa từng có ở lần quét trước.
2. 🟢 **Lỗi đã được khắc phục (Resolved Errors):** Lỗi xuất hiện ở lần trước nhưng đã được sửa xong ở lần này.
3. 🟡 **Lỗi còn tồn tại (Persistent Errors):** Lỗi xuất hiện ở cả hai lần quét.
4. ⚪ **Mục chưa kiểm tra (Not Checked):** Các URL bị bỏ qua do chạm mốc giới hạn số trang hoặc hết thời gian.

---

## 🧪 8. Website thử nghiệm độc lập (`test-website/`)

Website thử nghiệm được viết riêng chạy trên cổng **`http://localhost:8888`** với hơn 20 trang HTML tĩnh, phản ánh các tình huống thực tế:

| Trang               | Nội dung & Kịch bản lỗi được cấy sẵn                                                                                                                     |
| :------------------ | :---------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `index.html`      | Trang chủ: chứa link lỗi 404 (`khong-ton-tai.html`), ảnh hỏng (`khong-co-anh.jpg`), anchor hỏng (`about.html#khong-co-section`), link ngoài phạm vi |
| `about.html`      | Trang giới thiệu: anchor `#team` hợp lệ, link vòng tròn quay lại `index.html`                                                                          |
| `products.html`   | Danh mục sản phẩm: liên kết đến các sản phẩm con                                                                                                        |
| `blog.html`       | Trang tin tức: chứa liên kết bài viết hỏng `blog/post-khong-co.html`                                                                                     |
| `team.html`       | Trang đội ngũ: hình ảnh nhân sự, link liên hệ                                                                                                            |
| `gallery.html`    | Thư viện ảnh: kiểm tra ảnh hợp lệ và ảnh bị thiếu                                                                                                      |
| `contact.html`    | Trang liên hệ: thông tin email, số điện thoại, link chi nhánh                                                                                             |
| `branches/*.html` | Các trang chi nhánh Hà Nội, Đà Nẵng                                                                                                                        |

---

## 🔬 9. Bộ kiểm thử tự động (Automated Tests)

Dự án tích hợp sẵn bộ kiểm thử tự động không phụ thuộc thư viện bên ngoài. Để chạy kiểm thử:

```bash
# Chạy toàn bộ test suite
C:\xampp\php\php.exe tests/run_tests.php
# Hoặc nếu dùng ổ E:
E:\xampp\php\php.exe tests/run_tests.php
```

**Kết quả kiểm thử đạt 100%:**

* ✅ **UrlNormalizerTest:** 15/15 test cases (chuẩn hóa `../`, `./`, anchor, validate URL).
* ✅ **CrawlerTest:** 6/6 test cases (bóc tách thẻ, lọc ảnh, xác định phạm vi domain).
* ✅ **FullScanTest:** Kiểm thử quét toàn trình từ Seed URL đến báo cáo.

---

## 🛠️ 10. Công nghệ sử dụng

* **Ngôn ngữ:** PHP 8.2 (Pure Object-Oriented PHP, không sử dụng framework như Laravel hay Symfony).
* **Cơ sở dữ liệu:** MariaDB / MySQL (Kết nối an toàn thông qua PDO, Prepared Statements chống SQL Injection).
* **Giao diện:** HTML5 Semantic, CSS3 Custom Properties (CSS Variables, Flexbox/Grid), JavaScript ES6 Vanilla.
* **Tiêu chuẩn mã nguồn:** Tuân thủ chuẩn PSR-12, kiến trúc MVC (Model - View - Controller - Service).

---

## 📄 11. Giấy phép

Dự án được phân phối dưới giấy phép mã nguồn mở **MIT License**. Xem chi tiết tại file [LICENSE](LICENSE).
