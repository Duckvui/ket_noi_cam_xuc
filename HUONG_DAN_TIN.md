# Chức năng Tin (Story)

## Hành vi

- Dùng tài khoản đăng nhập từ `TrangTuong` và guard `tai_khoan`; server quyết định người đăng, không nhận chủ sở hữu từ client.
- Mỗi tài khoản xuất hiện một lần. Tin của tôi trước; người khác theo Tin mới nhất. Trong mỗi người, Tin cũ chạy trước, trùng thời gian thì theo ID.
- Ảnh/văn bản: 30 giây, ảnh bắt đầu đếm khi tải xong. Video chạy theo thời lượng thực tế và chuyển khi kết thúc. Video tự phát ở chế độ tắt tiếng; có controls để bật tiếng.
- Thanh progress chia đoạn theo số Tin của người đang xem. Previous/Next đi qua ranh giới người dùng; hết Tin của tôi vẫn tiếp tục sang người khác. Hết toàn bộ danh sách hiển thị màn hình đã xem hết.
- Có nút tạm dừng; tạm dừng khi tab ẩn hoặc đang tương tác ô trả lời. Tin hết hạn được loại khỏi danh sách khi đang mở.
- Tạo ảnh/video có preview và kiểm tra định dạng/kích thước; Tin văn bản có 5 màu nền được lưu vào database. Quyền Công khai/Bạn bè/Chỉ mình tôi và API media có kiểm tra quyền được giữ nguyên.
- Avatar dùng lại `AvatarAnimation` và context hiện tại, nên tương thích avatar động và ảnh đại diện.

## File thay đổi trong lần sửa này

| File | Thay đổi |
| --- | --- |
| `backend/app/Http/Controllers/TinController.php` | Bổ sung ID người đăng, avatar hiện tại và màu nền; trả Tin cũ trước, bỏ cắt cứng 100 Tin; validate/lưu màu nền. Giữ cấu trúc `data` dạng mảng để tương thích API cũ. |
| `backend/app/Models/Tin.php` | Thêm `MauNen` vào fillable. |
| `backend/database/migrations/2026_09_16_000000_add_mau_nen_to_tins_table.php` | Thêm cột nullable `MauNen`; không đổi dữ liệu nội dung cũ. |
| `backend/tests/Feature/TinTest.php` | Thêm test ID/chủ sở hữu/tên/avatar mới nhất, thứ tự, hơn 100 Tin, validate và lưu màu nền. |
| `frontend/src/pages/TrangTuong.jsx` | Truyền `user` hiện tại xuống `TinNoiBat`. |
| `frontend/src/services/tinService.js` | Gửi màu nền và hiển thị lỗi validation cụ thể. |
| `frontend/src/services/storyPlayback.js` | Nhóm theo ID, lọc hết hạn, thứ tự và điều hướng xuyên nhóm; cấu hình 30 giây/màu nền. |
| `frontend/src/components/wall/TinNoiBat.jsx` | Danh sách tài khoản, sidebar, quản lý chọn Tin/tạo Tin; chặn phản hồi xem Tin cũ ghi đè Tin mới. |
| `frontend/src/components/wall/TrinhXemTin.jsx` | Viewer tối, progress phân đoạn, timer, video, Previous/Next, tạm dừng, tích hợp hoạt động/trả lời/cảm xúc. |
| `frontend/src/components/wall/TaoTin.jsx` | Hai lựa chọn tạo Tin, upload, preview, màu nền, quyền xem và đăng Tin. |
| `frontend/src/components/wall/TinNoiBat.css` | Bố cục creator/viewer/sidebar và màn hình nhỏ; giới hạn CSS vào khu vực Tin. |
| `frontend/tests/storyPlayback.test.js` | Test nhóm tài khoản cùng tên, thứ tự, hết hạn, biên đầu/cuối, điều hướng hai chiều xuyên nhóm. |
| `frontend/tests/story-preview.html`, `frontend/tests/story-preview.jsx` | Trang thử giao diện với API giả lập, không ghi dữ liệu vào tài khoản thật. Không nằm trong entry build production. |
| `HUONG_DAN_TIN.md` | Hướng dẫn này. |

Project đã có thay đổi chưa commit trước khi thực hiện. Danh sách trên chỉ mô tả phần sửa của lần này, không phải toàn bộ `git diff`.

## API

Không thêm route/API mới. Các API hiện có được tái sử dụng:

- `GET /api/tins`: mảng `data`, bổ sung `idTaiKhoan`, `avatar`, `MauNen`; frontend nhóm theo `idTaiKhoan`. Chỉ Tin còn hạn và được phép xem.
- `POST /api/tins`: thêm trường tùy chọn `MauNen` (`purple`, `blue`, `pink`, `green`, `dark`); không gửi ID chủ sở hữu.
- `GET /api/tins/{id}/media`: ảnh/video có kiểm tra phiên và quyền xem.
- `POST /api/tins/{id}/xem`: ghi nhận xem.
- `POST /api/tins/{id}/cam-xuc`: cảm xúc.
- `POST /api/tins/{id}/tra-loi`: tạo tin nhắn trong cuộc trò chuyện hiện có.

## Migration và chạy

Cần thêm cột vì database cũ chưa có nơi lưu màu nền. Tin cũ có `MauNen = null` được hiển thị nền tím mặc định.

Sau khi bật MySQL của project, chạy từ thư mục `backend`:

```powershell
php artisan migrate --path=database/migrations/2026_09_16_000000_add_mau_nen_to_tins_table.php
```

Lệnh trên chỉ chạy migration mới của chức năng này. Nếu cài project trên database mới, chạy đầy đủ các migration theo hướng dẫn project.

Ban đầu MySQL `127.0.0.1:3306` từ chối kết nối. Trong lần xử lý lỗi `Unknown column 'MauNen'` tiếp theo, đã chạy thành công migration này trên database MySQL `mxhcx` và kiểm tra bảng `tins` có cột `MauNen varchar(20) nullable`. Không chạy migrate:fresh hoặc xóa dữ liệu.

## Kiểm thử

```powershell
# Tại backend
php artisan test
# Tại frontend
node --test tests/storyPlayback.test.js
npm run lint
npm run build
```

Đã đạt: 18 test backend/132 assertions; 3 test logic frontend; build production. Lint còn 3 cảnh báo có sẵn ngoài phần Tin: `ThanhBenPhai.jsx` thiếu dependency `openProfile`, `BaiDang.jsx` có hai import chưa dùng (`updatePost`, `deletePost`).

Khi chạy Vite, mở `/tests/story-preview.html` để thử với dữ liệu giả lập. Đã kiểm tra UI tạo văn bản/chọn nền/preview/đăng, nhóm Tin và chuyển Next/Previous qua tài khoản; chờ 31 giây xác nhận Tin cuối của tôi tự sang Tin đầu của A; Next ở Tin cuối toàn bộ danh sách hiển thị “Bạn đã xem hết tin”. API upload ảnh/video và quyền riêng tư được kiểm tra bằng Laravel tests. Chưa xác minh toàn bộ quy trình với MySQL thật hoặc phát video thực tế trong trình duyệt.

## Lỗi cũ được sửa

- `TinNoiBat.jsx` cũ: render `stories.map` theo từng Tin; không nhóm người đăng.
- Timer ảnh/văn bản cũ và `onEnded` video gọi `setSelected(null)`: đóng viewer thay vì chuyển tiếp, khiến hết Tin của mình là dừng.
- `openStory`/reaction cũ ghi thẳng response async vào `selected`: response chậm có thể đè lên Tin vừa chuyển. Hiện dùng ID được chọn và bỏ phản hồi xem Tin đã rời khỏi.
- `TinController::data` cũ không có ID người đăng/avatar cho frontend nhóm chính xác.
- `TinController::index` cũ mới trước và cắt 100 Tin toàn cục: có thể mất Tin cũ hoặc cả tài khoản trong chuỗi. Hiện trả tất cả Tin hợp lệ trong 24 giờ. Khi hệ thống có lượng Tin lớn cần phân trang theo tài khoản để giảm tải mà không cắt giữa nhóm.
- CSS trả lời cũ đặt `.story-actions` absolute toàn màn hình; override trong `.story-player-footer` đưa phần trả lời về footer của viewer.

Không có ảnh Facebook đính kèm trong yêu cầu nhận được; bố cục được triển khai theo mô tả bằng văn bản.
