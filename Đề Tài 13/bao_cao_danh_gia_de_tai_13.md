# BÁO CÁO ĐÁNH GIÁ HIỆN TRẠNG & TIẾN ĐỘ THỰC HIỆN
## ĐỀ TÀI 13: XÂY DỰNG CÔNG CỤ KIỂM TRA LIÊN KẾT VÀ TÀI NGUYÊN BỊ LỖI TRÊN WEBSITE

---

### I. TỔNG QUAN ĐỀ TÀI & PHẠM VI DỰ ÁN

* **Tên đề tài**: Đề tài 13 - *Xây dựng công cụ kiểm tra liên kết và tài nguyên bị lỗi trên website* (Căn cứ theo tài liệu [DE_TAI_BTL.docx](file:///e:/Đề Tài 13/DE_TAI_BTL.docx)).
* **Mục tiêu chính**:
  * Đi qua các trang HTML tĩnh trong phạm vi miền cho phép (crawling).
  * Kiểm tra tính hợp lệ của các liên kết, ảnh, script, stylesheet, anchor fragment.
  * Phân biệt rõ bản chất các loại lỗi (HTTP 404/500, broken image, missing anchor, connection timeout, out-of-scope redirect).
  * Lưu trữ lịch sử lần quét, hỗ trợ lọc kết quả, truy vết tất cả trang nguồn (source pages) trỏ đến một đích lỗi.
  * So sánh giữa 2 lần quét để chỉ ra lỗi mới xuất hiện, lỗi đã được sửa và mục chưa được kiểm tra.
* **Quy chuẩn kỹ thuật**:
  * Tự viết bộ Crawler, URL Normalizer, Link Checker, HTML Parser bằng **PHP thuần** (không dùng framework hay thư viện bên ngoài như Guzzle, Symfony DomCrawler).
  * Cơ sở dữ liệu: MySQL/MariaDB (PDO).
  * Frontend: HTML5, CSS3, JavaScript (Vanilla).

---

### II. HIỆN TRẠNG MÃ NGUỒN HIỆN CÓ

Dự án hiện có cấu trúc thư mục như sau:

```
e:\Đề Tài 13\
├── config/
│   ├── app.php                    # Cấu hình tham số mặc định của Crawler
│   ├── database.php               # Cấu hình kết nối MySQL/MariaDB
│   └── database.example.php       # File mẫu cấu hình CSDL
├── database/
│   ├── migrate.php                # Script tạo 6 bảng CSDL chuẩn
│   └── seed.php                   # Script chèn dữ liệu mẫu (users, site)
├── src/
│   ├── Core/
│   │   ├── Auth.php               # Quản lý phiên đăng nhập, phân quyền user/admin
│   │   ├── Database.php           # Singleton kết nối PDO
│   │   └── Router.php             # Bộ định tuyến request URL dạng regex
│   ├── Models/
│   │   ├── Finding.php            # Tương tác bảng findings (lỗi, thống kê, recheck)
│   │   ├── ScanRun.php            # Tương tác bảng scan_runs, lưu kết quả crawl
│   │   └── Site.php               # Quản lý website cần kiểm tra (CRUD)
│   ├── Services/
│   │   ├── Crawler.php            # Thuật toán BFS duyệt trang, quản lý hàng đợi & giới hạn
│   │   ├── HtmlParser.php         # Bóc tách thẻ a, img, link, script và anchor ID (DOMDocument)
│   │   ├── LinkChecker.php        # Kiểm tra HTTP qua cURL, xác thực timeout, MIME type, anchor
│   │   ├── ScanComparator.php     # Thuật toán so sánh 2 lần quét (lỗi mới, lỗi hết, chưa duyệt)
│   │   └── UrlNormalizer.php      # Chuẩn hóa URL, giải quyết đường dẫn tương đối (RFC 3986)
│   └── Controllers/
│       ├── AuthController.php     # Điều hướng Đăng nhập / Đăng ký / Đăng xuất
│       ├── DashboardController.php# Điều hướng trang Dashboard tổng quan
│       ├── ScanController.php     # Điều hướng tạo lần quét, xem kết quả, so sánh, recheck
│       └── SiteController.php     # Điều hướng quản lý Website khai báo
├── CONTRIBUTING.md                # Quy định đóng góp mã nguồn (PSR-12)
├── DE_TAI_BTL.docx                # Yêu cầu đề tài & Rubric chấm điểm của Giảng viên
├── LICENSE                        # Giấy phép nguồn mở MIT
└── README.md                      # Tài liệu hướng dẫn cài đặt & vận hành
```

---

### III. ĐÁNH GIÁ CHI TIẾT THEO CÁC YÊU CẦU CỦA ĐỀ TÀI

#### 1. Đánh giá 5 chức năng bắt buộc (Mục 2 đề tài - 3.0 điểm)

| Chức năng | Yêu cầu nghiệp vụ | Tình trạng hiện tại | Đánh giá chi tiết |
| :--- | :--- | :---: | :--- |
| **CN 1: Khai báo phạm vi kiểm tra** | Nhập URL bắt đầu (seed URL), domain cho phép, giới hạn số trang, độ sâu, thời gian timeout. | **80% (Backend xong, thiếu View)** | • Đã có Model `Site.php` và Controller `SiteController.php`.<br>• Lưu danh sách domain cho phép dưới dạng JSON array.<br>• Có validate định dạng HTTP/HTTPS URL.<br>• *Chưa có giao diện nhập liệu* (`src/Views/sites/create.php`). |
| **CN 2: Duyệt & thu thập liên kết** | Đọc HTML, lấy thẻ liên kết & ảnh, chuẩn hóa đường dẫn tương đối, tránh duyệt lặp vòng. | **100% (Backend Service)** | • `Crawler.php`: Dùng thuật toán BFS kèm queue `[$url, $depth]` và mảng `$visited` đánh dấu theo URL không fragment.<br>• `HtmlParser.php`: Bóc tách đầy đủ thẻ `<a>`, `<img>`, `<link>`, `<script>`.<br>• `UrlNormalizer.php`: Giải quyết triệt để các dạng `../`, `./`, `/path`, `#anchor`, `?query` theo chuẩn RFC 3986. |
| **CN 3: Kiểm tra tài nguyên & phân loại lỗi** | Kiểm tra trang, ảnh, chuyển hướng, anchor fragment; phân biệt rõ HTTP error, ảnh hỏng, anchor thiếu, timeout. | **100% (Backend Service)** | • `LinkChecker.php`: Dùng cURL tự động theo dõi redirect.<br>• Phân loại chuẩn xác: `http_error` (404/500), `broken_image` (kiểm tra MIME type image/*), `missing_anchor` (đối chiếu ID trong DOM), `timeout` (mã cURL 28), `redirect_out_of_scope`.<br>• Đáp ứng nghiêm ngặt: Timeout được ghi là *"chưa xác định được phản hồi"*, không quy thành 404. |
| **CN 4: Quản lý báo cáo & kiểm tra lại** | Lưu lịch sử quét, theo dõi toàn bộ trang nguồn trỏ đến 1 link, lọc báo cáo, kiểm tra lại (re-check). | **85% (Backend xong, thiếu View)** | • CSDL phân tách 3 cấp: `pages` (trang đã duyệt) $\rightarrow$ `links` (liên kết phát hiện kèm trang nguồn) $\rightarrow$ `findings` (lỗi).<br>• `Finding::getSourcePages()`: Truy vết tất cả các trang nguồn chứa liên kết đích.<br>• `Finding::recheck()`: Cho phép bấm kiểm tra lại ngay một liên kết lỗi.<br>• *Chưa có giao diện hiển thị* (`src/Views/scans/show.php`). |
| **CN 5: So sánh 2 lần quét & xuất báo cáo** | So sánh 2 lần kiểm tra (chỉ ra lỗi mới, lỗi đã hết, lỗi còn, mục chưa kiểm tra); xuất báo cáo. | **70% (Thiếu View & Export)** | • `ScanComparator.php`: Thuật toán so sánh đối chiếu logic 2 lần quét rất chặt chẽ.<br>• Xác định chính xác 4 nhóm: `new_errors`, `resolved_errors`, `persistent_errors`, `not_checked`.<br>• *Chưa có giao diện so sánh* (`src/Views/scans/compare.php`) và *chưa có hàm Export báo cáo ra CSV/HTML/PDF*. |

---

#### 2. Đánh giá 2 yêu cầu kỹ thuật cốt lõi (Mục 3 đề tài - 2.0 điểm)

* **Yêu cầu kỹ thuật 1 (Tránh vòng lặp & Đa nguồn trỏ 1 đích)**:
  * **Trạng thái**: **ĐẠT XUẤT SẮC TRONG CODE LOGIC**.
  * **Minh chứng mã nguồn**:
    * Trong `Crawler.php` (dòng 112 - 115): Sử dụng `UrlNormalizer::removeFragment($currentUrl)` làm định danh trong bảng băm `$this->visited[$normalizedUrl]`. Nếu gặp chu trình trang $A \rightarrow B \rightarrow A$, lần thứ hai duyệt tới $A$ sẽ bị bỏ qua ngay lập tức, triệt tiêu nguy cơ lặp vô hạn.
    * Trong `Crawler.php` (dòng 181 - 188) và `LinkChecker.php` (dòng 63 - 65, 96 - 99): Nhiều trang $A, B, C$ cùng trỏ đến ảnh $X$ hoặc link $Y$, hệ thống lưu vết nguồn vào `$this->sourcePages[$target]` và bảng `links`, nhưng cURL chỉ thực thi kiểm tra mạng đối với $X/Y$ duy nhất một lần nhờ cơ chế in-memory caching `$this->checkedUrls`.

* **Yêu cầu kỹ thuật 2 (Kiểm soát phạm vi, chuyển hướng & xử lý Timeout)**:
  * **Trạng thái**: **ĐẠT XUẤT SẮC TRONG CODE LOGIC**.
  * **Minh chứng mã nguồn**:
    * Trong `LinkChecker.php` (dòng 51 - 60 & 71 - 84): Kiểm tra URL trước khi gửi request. Nếu trang hợp lệ nhưng khi gửi cURL bị redirect (`CURLINFO_EFFECTIVE_URL`) sang một domain không có trong `allowed_domains`, hệ thống ghi nhận lỗi `redirect_out_of_scope` và ngắt truy cập.
    * Trong `LinkChecker.php` (dòng 141 - 152): Bắt lỗi cURL `CURLE_OPERATION_TIMEOUTED` (mã 28). Trạng thái trả về là `timeout`, thông báo: *"Hết thời gian chờ (Xs) - chưa xác định được phản hồi"*, tuyệt đối không tự kết luận là trang 404.

---

#### 3. Đánh giá 5 phép thử bắt buộc (Mục 6 đề tài - 1.5 điểm)

1. **Phép thử 1 (Vòng lặp A $\rightarrow$ B $\rightarrow$ A)**: Thuật toán trong `Crawler.php` đã khống chế tốt thông qua hàng đợi BFS và mảng `$visited`.
2. **Phép thử 2 (Một đích nhiều nguồn)**: Hàm `Finding::getSourcePages()` và bảng `links` lưu đầy đủ `source_page_id` và `line_in_source`.
3. **Phép thử 3 (Phân biệt 404, ảnh hỏng, anchor thiếu, timeout)**: Logic `LinkChecker.php` xử lý riêng rẽ 4 trường hợp này.
4. **Phép thử 4 (Chặn chuyển hướng ngoài phạm vi, ghi nhận mục chưa kiểm tra)**: Khi cạn `max_total_time` hoặc vượt `max_pages`, hàm `markRemainingAsSkipped()` trong `Crawler.php` chủ động gán trạng thái `skipped` ("Chưa kiểm tra - đạt giới hạn").
5. **Phép thử 5 (So sánh phân biệt lỗi mới, lỗi hết, chưa kiểm tra)**: `ScanComparator.php` phân chia rõ 4 mảng dữ liệu.

> [!WARNING]
> **Điểm nghẽn lớn**: Mặc dù logic nghiệp vụ đã viết trong code Service, nhưng toàn bộ thư mục `tests/` (bao gồm `run_tests.php`, `UrlNormalizerTest.php`, `CrawlerTest.php`, `FullScanTest.php`) **chưa được tạo**. Do đó, chưa có kịch bản chạy tự động (automated tests) để nộp và trình diễn trực tiếp cho giảng viên.

---

### IV. NHỮNG THÀNH PHẦN QUAN TRỌNG CÒN THIẾU HOẶC LỖI (GAPS & BUGS)

1. **Thiếu toàn bộ tầng giao diện (`src/Views/`)**:
   * Tất cả Controller hiện tại đều gọi lệnh `require __DIR__ . '/../Views/...'`, nhưng thư mục `Views` không hề tồn tại.
   * Các file View đang thiếu:
     * `src/Views/auth/login.php` & `register.php`
     * `src/Views/dashboard/index.php`
     * `src/Views/sites/index.php`, `create.php`, `show.php`
     * `src/Views/scans/show.php` (Bảng lọc lỗi, xem nguồn, nút Re-check)
     * `src/Views/scans/compare.php` (Bảng so sánh 2 lần quét trực quan)
     * `src/Views/errors/404.php`
     * Layout dùng chung (header, navigation bar, footer).

2. **Thiếu thư mục Web Root & File khởi động (`public/`)**:
   * README hướng dẫn chạy lệnh `php -S localhost:8080 -t public`, nhưng thư mục `public/` chưa có.
   * Cần có file `public/index.php` làm Front Controller để:
     * Bật `session_start()`.
     * Đăng ký bộ nạp tự động class (`spl_autoload_register`).
     * Khai báo toàn bộ danh sách routes cho `Router`.
     * Gọi `$router->dispatch()`.
   * Cần có file `public/.htaccess` hỗ trợ rewrite URL trên Apache/XAMPP.
   * Cần các file tĩnh: `public/css/style.css`, `public/js/main.js`.

3. **Thiếu Website thử nghiệm giả lập (`test-website/`)**:
   * Mục 5 của đề tài yêu cầu: *"Nhóm dựng website thử khoảng 20 trang và 60 liên kết, có ảnh lỗi, mã 404, chuyển hướng, liên kết vòng và mục neo bị thiếu. Lập danh sách lỗi dự kiến."*
   * Hiện tại thư mục `test-website/` hoàn toàn chưa có. Không có website này thì không thể thực hiện Tình huống 1 & Tình huống 2 khi bảo vệ.

4. **Thiếu Tính năng Xuất Báo Cáo (Export)**:
   * Chức năng 5 yêu cầu *"xuất báo cáo kèm vị trí liên kết trong trang nguồn"*. Hiện tại chưa có nút bấm hoặc controller method để xuất dữ liệu ra CSV/Excel/HTML.

5. **Phát hiện lỗi đường dẫn (Bug) trong `src/Core/Database.php`**:
   * Tại dòng 17:
     ```php
     $config = require __DIR__ . '/../config/database.php';
     ```
     Vì file nằm ở `src/Core/`, đường dẫn này sẽ trỏ tới `src/config/database.php` (không tồn tại) $\rightarrow$ Sẽ báo lỗi `Fatal Error: require(): Failed opening required`.
   * Đường dẫn đúng cần là: `__DIR__ . '/../../config/database.php'`.

6. **Chưa khởi tạo Git Repository**:
   * Thư mục hiện tại chưa được khởi tạo Git (`.git` không tồn tại).
   * Tiêu chí Rubric *"Mã nguồn mở, bàn giao"* chiếm **1.5 điểm** đòi hỏi có lịch sử commit, branch, Pull Request, Issue, Code Review và Release. Nếu nộp thư mục tĩnh không có Git sẽ bị mất phần lớn số điểm này.

---

### V. ĐỐI CHIẾU VÀ DỰ PHÓNG ĐIỂM THEO RUBRIC (10 ĐIỂM)

Dựa trên bảng Rubric chính thức trong file [DE_TAI_BTL.docx](file:///e:/Đề Tài 13/DE_TAI_BTL.docx):

| Tiêu chí | Trọng số | Trạng thái hiện tại | Điểm dự kiến hiện tại | Điểm tiềm năng sau khi hoàn thiện |
| :--- | :---: | :--- | :---: | :---: |
| **Năm chức năng (Mục 2 & Mục 4)** | 3.0 điểm | Backend logic đã đạt ~80%, nhưng thiếu 100% Views và chức năng Xuất báo cáo, chưa chạy được 2 tình huống demo. | **1.5 / 3.0** | **3.0 / 3.0** |
| **Kỹ thuật 1 (Crawl, chuẩn hóa URL, vòng lặp)** | 1.0 điểm | Code `Crawler.php`, `UrlNormalizer.php` tự viết, đúng chuẩn RFC, lưu đa nguồn cực kỳ chuẩn xác. | **1.0 / 1.0** | **1.0 / 1.0** |
| **Kỹ thuật 2 (Phạm vi, redirect, timeout)** | 1.0 điểm | Code `LinkChecker.php` kiểm soát domain chặt chẽ, timeout trả đúng định nghĩa không kết luận 404. | **1.0 / 1.0** | **1.0 / 1.0** |
| **Dữ liệu, an toàn** | 1.0 điểm | Schema 6 bảng có quan hệ ràng buộc khoá ngoại tốt, có phân quyền user/admin theo site. | **0.8 / 1.0** | **1.0 / 1.0** |
| **Kiểm thử, sửa lỗi (Mục 6)** | 1.5 điểm | Chưa có website demo 20 trang, chưa có bộ automated tests (`tests/`). | **0.0 / 1.5** | **1.5 / 1.5** |
| **Mã nguồn mở, bàn giao** | 1.5 điểm | Đã có README, CONTRIBUTING, LICENSE. Chưa có Git repo, chưa có commits/PR/Issue. | **0.5 / 1.5** | **1.5 / 1.5** |
| **Đóng góp cá nhân** | 1.0 điểm | Chưa điền danh sách thành viên trong README, chưa có phân công commit. | **0.0 / 1.0** | **1.0 / 1.0** |
| **TỔNG CỘNG** | **10.0 điểm** | Dự án đã có **khung Backend/Core rất vững chắc**, nhưng thiếu toàn bộ phần hiển thị và môi trường kiểm thử. | **4.8 / 10.0** | **10.0 / 10.0** |

---

### VI. ĐỀ XUẤT LỘ TRÌNH HOÀN THIỆN ĐỂ ĐẠT 10/10 ĐIỂM

Để bài tập lớn đạt điểm tối đa khi vấn đáp và chấm nghiệm thu, cần tiến hành các bước theo thứ tự ưu tiên:

1. **Khắc phục lỗi đường dẫn kết nối CSDL**:
   * Sửa đường dẫn cấu hình trong [src/Core/Database.php](file:///e:/Đề Tài 13/src/Core/Database.php#L17) từ `../config/database.php` thành `../../config/database.php`.

2. **Xây dựng tầng Web Entrypoint & Views**:
   * Tạo thư mục `public/` với `index.php` (kèm Autoloader và cấu hình Routes), `.htaccess`, CSS và JavaScript.
   * Xây dựng đầy đủ các view trong `src/Views/`:
     * Trang đăng nhập/đăng ký (`auth/`).
     * Dashboard tổng quan (`dashboard/`).
     * Quản lý website (`sites/`).
     * Chi tiết lần quét với bộ lọc lỗi, nút Re-check bằng AJAX, modal xem danh sách trang nguồn (`scans/show.php`).
     * Giao diện so sánh 2 lần quét trực quan với 4 cột trạng thái (`scans/compare.php`).
     * Tính năng xuất file báo cáo (CSV/HTML).

3. **Xây dựng Website thử nghiệm (`test-website/`)**:
   * Tạo một trang web mẫu chạy độc lập trên cổng `:8888` gồm 20 trang HTML tĩnh liên kết với nhau.
   * Đưa vào các lỗi chủ đích theo đúng yêu cầu đề tài: trang 404, ảnh hỏng (`image-broken.png`), thẻ neo thiếu (`#non-existent-anchor`), vòng lặp (`page-a.html` $\leftrightarrow$ `page-b.html`), kịch bản làm chậm timeout (`delay.php`), và liên kết trỏ sang domain ngoài.
   * Lập sẵn bảng đáp án dự kiến (expected findings) để đối chiếu khi demo.

4. **Viết Bộ kiểm thử tự động (`tests/`)**:
   * Viết `tests/Unit/UrlNormalizerTest.php`: Kiểm tra các ca giải quyết đường dẫn `../`, `./`, `#fragment`.
   * Viết `tests/Unit/CrawlerTest.php`: Kiểm tra thuật toán duyệt không bị lặp vô hạn.
   * Viết `tests/Integration/FullScanTest.php`: Chạy quét thực tế và kiểm tra tính toàn vẹn dữ liệu.
   * Viết `tests/run_tests.php` để chạy tất cả test chỉ bằng một câu lệnh CLI.

5. **Thiết lập Git & Hoàn thiện hồ sơ bàn giao**:
   * Chạy `git init`, tạo các commit chia theo từng chức năng hoặc thành viên trong nhóm.
   * Điền đầy đủ thông tin thành viên nhóm vào file [README.md](file:///e:/Đề Tài 13/README.md#L7-L14).
   * Tạo bản release hoặc đóng gói theo chuẩn bàn giao của đề tài.
