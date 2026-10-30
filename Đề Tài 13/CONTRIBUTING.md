# Đóng góp cho dự án Link Checker

Cảm ơn bạn đã quan tâm đến việc đóng góp! Dưới đây là hướng dẫn để tham gia phát triển.

## Quy trình đóng góp

1. **Fork** repository này
2. Tạo **branch** mới cho tính năng hoặc sửa lỗi: `git checkout -b feature/ten-tinh-nang`
3. **Commit** thay đổi với message rõ ràng: `git commit -m "Thêm chức năng XYZ"`
4. **Push** lên branch: `git push origin feature/ten-tinh-nang`
5. Tạo **Pull Request** và mô tả chi tiết thay đổi

## Quy ước code

- Sử dụng **PSR-12** coding standard cho PHP
- Đặt tên biến, hàm bằng **tiếng Anh**, camelCase
- Mỗi class một file riêng
- Viết **comment** cho các hàm phức tạp
- Tách **xử lý logic** khỏi **giao diện**

## Cấu trúc thư mục

```
src/
├── Controllers/    # Xử lý request/response
├── Models/         # Tương tác database
├── Services/       # Logic nghiệp vụ
└── Views/          # Giao diện HTML
```

## Báo lỗi

Khi tạo Issue, vui lòng cung cấp:
- Mô tả lỗi
- Các bước tái tạo lỗi
- Kết quả mong đợi vs kết quả thực tế
- Ảnh chụp màn hình (nếu có)

## Code Review

- Mọi thay đổi đều cần ít nhất **1 review** trước khi merge
- Reviewer kiểm tra: logic, coding style, test coverage
- Sử dụng **Approve** hoặc **Request Changes**
