# Hoàn thiện kết nối, chat ảnh và streak pet

## Cách hoạt động

- Giữ Laravel, React, database và màu hồng hiện có. Tái sử dụng `TaiKhoan`, `Pet`, `TuongTacPet`, `TinNhan`, `CuocTroChuyen`, `GuiKetBan`, `BanBe`, `TheoDoi`.
- Tìm kiếm header/sidebar dùng chung `TimNguoiDung`, debounce 400 ms, hủy request cũ khi đổi từ khóa. Tìm từng phần của họ tên/tài khoản, loại chính mình và quan hệ chặn hai chiều.
- Điều hướng React dùng hash hiện có: `#/profile/{id}` và `#/messages/{id}`. Có thể tải lại URL, Back/Forward. Không thêm React Router.
- `UserAvatar` bọc `AvatarAnimation`: avatar động, ảnh thật hoặc mặc định; dùng ở header, sidebar, kết quả tìm kiếm, profile, bài viết, bình luận và chat. Tên/avatar dẫn tới profile.
- Profile có gửi/hủy lời mời, chấp nhận/từ chối, hủy bạn, theo dõi/bỏ theo dõi và xác nhận chặn/bỏ chặn. Trạng thái cập nhật từ API; sidebar và tìm kiếm được thông báo để tải lại.
- Chặn là quan hệ có hướng nhưng cấm gửi tin và kết bạn ở cả hai chiều; bỏ follow hai chiều, hủy quan hệ bạn và lời mời. Bài viết/Tin và media của người bị chặn được kiểm tra ở backend. Lịch sử chat cũ vẫn được đọc bởi thành viên; chặn không xóa lịch sử. Bỏ chặn không tự khôi phục bạn bè hoặc follow.
- Chat text, ảnh hoặc text kèm ảnh. Ảnh JPG/JPEG/PNG/WebP tối đa 5 MB; preview/hủy ảnh trước khi gửi; xem ảnh lớn trong dialog. Loại tin giữ enum cũ `Van_Ban`/`Anh`; `Anh` có thể có chú thích trong `NoiDung`.
- File chat lưu trên disk `local`, không public; API media xác minh người đọc thuộc conversation. Không cần `storage:link` cho ảnh chat.
- Chat tải 100 tin mới nhất, có nút tải tin cũ. Khi gửi thành công cập nhật UI ngay, loại trùng theo ID. Subscribe sự kiện riêng của tài khoản; polling 3 giây cho message, 5 giây cho danh sách là dự phòng khi WebSocket chưa chạy.
- Tạo conversation riêng tư qua service chung, khóa hai tài khoản theo ID tăng dần trong transaction để tránh cuộc trò chuyện trùng. Trả lời Story cũng dùng service này, có kiểm tra block và tính streak.
- Pet giữ nguyên hai chủ và unique cặp có sẵn. Tương tác thêm Vuốt ve; lịch sử lưu `CamXucTruoc`, `CamXucSau`. Cơ chế cảm xúc/animation đang có được tái sử dụng (SVG animation, không có Lottie cần thay thế).
- Streak dùng ngày lịch `Asia/Ho_Chi_Minh`, không phải khoảng 24 giờ trượt. Một người chat hoặc chăm pet là đủ. Cùng ngày không tăng; ngày kế tiếp +1; bỏ trọn một ngày thì API trả chuỗi hiện tại bằng 0 và hoạt động tiếp theo bắt đầu 1. `longest_streak`, pet và lịch sử không bị xóa. Không có job xóa pet hoặc reset lịch sử. Giá trị hết hạn được tính khi đọc; cột chuỗi được cập nhật khi có hoạt động.

## Migration

`backend/database/migrations/2026_09_16_020000_bo_sung_streak_chat_chan.php`

- `pets`: `current_streak`, `longest_streak`, `last_activity_date`. Dùng lại `idPet`, `idTaiKhoan`, `idTaiKhoan2`, `NgayTao`, `NgayCapNhat` thay vì tạo bảng pet mới.
- `tuong_tac_pets`: cảm xúc trước và sau.
- `tin_nhans`: `DuongDanTep` nullable.
- Bảng mới `chan_nguoi_dungs`: người chặn, người bị chặn, thời gian và unique cặp. Project chưa có bảng chặn tương ứng.

Migration này **đã chạy thành công trên MySQL hiện tại** trong phiên triển khai. Không xóa dữ liệu cũ. Khi đưa sang database khác, chạy tại `backend`:

```powershell
php artisan migrate
```

## API

Tất cả route sau nằm dưới middleware `auth:tai_khoan`; danh tính người thao tác lấy từ phiên đăng nhập.

| Method + URL | Nội dung |
| --- | --- |
| `GET /api/nguoi-dung/tim-kiem?q=...` | Route mới, dùng controller tìm kiếm hiện tại; trả tên/tài khoản/avatar/trạng thái quan hệ. Route cũ `/api/ket-ban/tim-kiem` vẫn dùng được. |
| `GET /api/nguoi-dung/{id}` | Mở rộng profile với trạng thái bạn bè, lời mời, follow, block, self và ảnh bài viết công khai. |
| `POST /api/nguoi-dung/{id}/quan-he` | Route mới, JSON `{action}`: `request`, `cancel`, `accept`, `reject`, `unfriend`, `follow`, `unfollow`, `block`, `unblock`. Trả profile/trạng thái mới. |
| `GET /api/ket-ban` | Thêm danh sách bạn bè thật; giữ lời mời và gợi ý. |
| `POST /api/ket-ban` | Route cũ; dùng chung service quan hệ, khóa cặp và kiểm tra block. |
| `PATCH /api/ket-ban/{idKetBan}` | Route cũ; chấp nhận/từ chối có kiểm tra đúng người nhận và trạng thái hiện tại. |
| `POST /api/nguoi-dung/{id}/theo-doi` | Route cũ; tái sử dụng kiểm tra quyền chung. |
| `GET /api/cuoc-tro-chuyens` | Danh sách và preview text/ảnh. |
| `POST /api/cuoc-tro-chuyens` | JSON `{recipient_id}`; lấy hoặc tạo conversation hai người. |
| `GET /api/cuoc-tro-chuyens/{id}/tin-nhans?before={messageId}` | 100 tin mới nhất; optional `before` để tải thêm, response có `has_more`. |
| `POST /api/cuoc-tro-chuyens/{id}/tin-nhans` | Mở rộng route cũ: multipart `content` và/hoặc `image`; JSON text cũ vẫn hoạt động. |
| `GET /api/tin-nhans/{id}/media` | Route mới, trả ảnh riêng tư sau kiểm tra membership. |
| `POST /api/tins/{id}/tra-loi` | Dùng service chat chung để tránh trùng conversation, chặn gửi khi block, tính streak. |
| `GET /api/pet-chung`, `GET /api/pet-chung/{id}` | Thêm streak hiện tại/kỷ lục/ngày hoạt động; chi tiết giữ `lich_su`. |
| `POST /api/pet-chung/{id}/tuong-tac` | Route cũ, JSON `MaHanhDong`; sau tương tác hợp lệ ghi streak và trạng thái trước/sau. |

Backend từ chối người ngoài conversation/pet. Không có API nào cho phép giả mạo người gửi qua `user_id`.

## Realtime và lệnh chạy

Không thêm npm dependency; không thay đổi `.env` thật. Tái sử dụng `frontend/src/realtime.js` và Laravel Reverb hiện có.

```powershell
# Backend
php artisan serve --host=127.0.0.1 --port=8000
# Frontend (terminal khác, thư mục frontend)
npm run dev
```

Để nhận thông báo WebSocket thay vì chỉ polling, cấu hình theo host/port/key Reverb hiện có:

```dotenv
# backend/.env — dùng ID/key/secret đang cấu hình của project
BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=database
REVERB_APP_ID=...
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
# Tùy chọn, mặc định đã là ngày Việt Nam
PET_TIMEZONE=Asia/Ho_Chi_Minh

# frontend/.env — chỉ key công khai, KHÔNG đưa APP_SECRET vào frontend
VITE_REVERB_APP_KEY=...
VITE_REVERB_HOST=127.0.0.1
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

```powershell
# Mỗi lệnh một terminal tại backend
php artisan reverb:start --host=127.0.0.1 --port=8080
php artisan queue:work
```

Sau đổi env, restart Vite và chạy `php artisan config:clear`; nếu queue worker đang chạy thì `php artisan queue:restart`. Sự kiện `tin-nhan.da-gui` phát trên private channel `chat-user.{id}` sau commit; chỉ chủ tài khoản được subscribe. Sự kiện chỉ báo conversation ID, client tải nội dung qua API có quyền. Sự kiện pet hiện có được giữ lại.

## File được sửa/thêm

### Backend

- `app/Services/PetStreakService.php` (mới): tính chuỗi ngày, kỷ lục, hoạt động từ chat/chăm pet và khóa pet khi ghi.
- `app/Services/QuanHeNguoiDung.php` (mới): khóa cặp tài khoản, trạng thái quan hệ, gửi/phản hồi lời mời, follow, block và danh sách loại trừ.
- `app/Services/DichVuChat.php` (mới): membership, tái sử dụng conversation, ghi tin, tính streak, thông báo realtime, serialize message.
- `app/Events/TinNhanDaGui.php` (mới): sự kiện private chat sau commit.
- `app/Services/DichVuPet.php`: ghi streak/cảm xúc tương tác, thêm dữ liệu streak; lời mời pet kiểm tra block.
- `app/Http/Controllers/ChatController.php`: chat multipart, pagination, ảnh riêng tư, sử dụng service chung.
- `app/Http/Controllers/GuiKetBanController.php`: search nhiều từ, loại self/block, danh sách bạn, thao tác qua service.
- `app/Http/Controllers/NguoiDungController.php`: trạng thái profile, ảnh bài viết, API quan hệ và kiểm tra block.
- `app/Http/Controllers/BaiVietController.php`: lọc block trong truy vấn nội dung/quyền truy cập bài viết.
- `app/Http/Controllers/TinController.php`: lọc block, tái sử dụng service chat cho trả lời.
- `app/Models/Pet.php`: casts streak/ngày hoạt động.
- `app/Models/TinNhan.php`: cho phép lưu đường dẫn ảnh.
- `app/Models/TuongTacPet.php`: cho phép lưu cảm xúc trước/sau.
- `config/pet.php`: múi giờ và hành động Vuốt ve.
- `routes/api.php`, `routes/channels.php`: route mới và channel riêng có xác thực.
- `database/migrations/2026_09_16_020000_bo_sung_streak_chat_chan.php` (mới): thay đổi schema ở trên.
- `tests/Feature/KetNoiChatStreakTest.php` (mới): luồng kết nối/chat/block/streak/cảm xúc và quyền.

### Frontend

- `src/components/UserAvatar.jsx` (mới): avatar chung và liên kết profile.
- `src/components/TimNguoiDung.jsx` (mới): tìm kiếm dùng chung cho header/sidebar.
- `src/services/nguoiDungService.js` (mới): API, lỗi, cập nhật quan hệ, URL media.
- `src/styles/KetNoi.css` (mới): dropdown, avatar, preview/bubble ảnh/chat; giữ bảng màu hiện tại.
- `src/components/AvatarAnimation.jsx`: fallback ảnh từ user.
- `src/components/PetChung.jsx`: hiển thị streak/kỷ lục/thông báo đứt chuỗi; cập nhật khi gửi chat.
- `src/components/wall/ChatBox.jsx`: preview/upload text + ảnh, gửi state, phân trang, polling/realtime, xem ảnh lớn, điều hướng người chat.
- `src/components/wall/ThanhBenPhai.jsx`: search dùng chung, lời mời/gợi ý/bạn bè từ API thật, trạng thái và liên kết profile.
- `src/components/wall/ThanhBenTrai.jsx`: avatar/tên dẫn tới profile, liên kết trang chủ/bạn bè.
- `src/components/wall/BaiDang.jsx`: avatar/tên tác giả bài viết và bình luận dẫn tới profile.
- `src/components/wall/TinNoiBat.jsx`: tải lại khi quan hệ thay đổi để loại nội dung bị chặn.
- `src/pages/TrangCaNhan.jsx`: các nút quan hệ thật, xác nhận block, trạng thái request, bài viết có ảnh, fallback ảnh bìa.
- `src/pages/TrangTuong.jsx`: header search, hash navigation profile/chat, hủy request profile cũ, cập nhật feed khi block.

## Xác minh

- Các flow backend được kiểm tra trong test bằng database SQLite riêng, không tạo dữ liệu test trong database người dùng: tìm tên đầy đủ, trạng thái bạn bè, follow, block hai chiều, conversation không trùng, text/ảnh/text+ảnh, quyền media, file sai định dạng/quá dung lượng, streak cùng ngày/qua ngày/bỏ ngày/nửa đêm, cảm xúc buồn → an ủi.
- Kiểm tra giao diện thật trên MySQL: đăng nhập tài khoản mẫu, header search `Huy`, dropdown đúng người, mở profile và nút Nhắn tin mở đúng conversation/lịch sử có sẵn. Không gửi tin nhắn thử hoặc thay đổi quan hệ của các tài khoản thật trong bước kiểm tra UI.
- Chưa xác minh end-to-end hai trình duyệt nhận sự kiện WebSocket. Polling dự phòng vẫn có trong code; cần Reverb và queue worker để kiểm tra đường WebSocket.
- Lint có hai cảnh báo cũ ở `BaiDang.jsx`: import `updatePost`, `deletePost` chưa sử dụng. Không sửa các chức năng ngoài phạm vi vì cảnh báo này.
