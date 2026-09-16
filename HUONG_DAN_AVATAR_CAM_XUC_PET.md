# Avatar animation, cảm xúc và pet chung

## Kết quả khảo sát trước khi triển khai

- Laravel 13, PHP 8.3; React 19, Vite 8. Backend dùng session guard `tai_khoan`, API nhận middleware `web` và `auth:tai_khoan`.
- Tên bảng thực tế: `tai_khoans`, `avatars`, `avatar_cam_xucs`, `cam_xucs`, `trang_thai_cam_xucs`, `pets`, `tuong_tac_pets`, `ban_bes`. Giữ nguyên bảng, khóa chính tiếng Việt và quan hệ cũ.
- Tái sử dụng các model tương ứng, `AvatarController`, `TrangThaiCamXucController`, `PetController`, `TuongTacPetController` (trước đây là khung trống). Không tạo bảng avatar hoặc danh mục cảm xúc trùng chức năng.
- `avatars` chứa ảnh avatar/ảnh bìa theo tài khoản; bổ sung mã animation trên bản ghi mới nhất. `trang_thai_cam_xucs` đã có trạng thái hiện tại và thời gian; bổ sung quyền hiển thị, giữ lịch sử cũ.
- `avatar_cam_xucs` là ánh xạ ảnh cảm xúc theo tài khoản; giữ nguyên để tương thích dữ liệu cũ. SVG/CSS mới dùng mã avatar và cảm xúc, không cần tạo nhiều bản ghi ảnh.
- `pets` cũ là pet cá nhân. Thêm chủ thứ hai nullable để phân biệt pet chung. Không chuyển pet cá nhân cũ thành pet chung.
- Reverb, Echo và pusher-js đã có; bổ sung xác thực private channel bằng guard hiện tại. Frontend trước đó chủ yếu hiển thị biểu tượng avatar mặc định.
- Tái sử dụng `api.js`, `TrangTuong`, `TrangCaNhan`, `BaiDang`, `KhungDangBai`, `ThanhBenTrai`, `ThanhBenPhai`, `HoatDongTin`. Danh sách người xem tin cũng dùng avatar mới; hình thu nhỏ nội dung tin vẫn giữ nguyên.

## Cách dùng

1. Đăng nhập, mở **Chỉnh sửa avatar & cảm xúc cá nhân** ở trang tường.
2. Chọn Mèo Mochi, Gấu Bông, Thỏ Mây hoặc Cáo Nắng. Chọn Mặc định để dùng ảnh cũ, hoặc biểu tượng mặc định nếu ảnh lỗi.
3. Chọn cảm xúc và quyền **Chỉ mình tôi / Bạn bè**. Avatar của cùng người dùng cập nhật đồng thời ở các vị trí đang hiển thị. Reload vẫn giữ lựa chọn.
4. Trong **Pet cảm xúc chung**, mở **Mời bạn nuôi pet chung**, chọn bạn, tên và loại pet. Người kia đăng nhập để đồng ý hoặc từ chối.
5. Sau khi đồng ý, đúng hai người có thể xem, đổi tên/loại pet và chăm sóc. Mỗi cặp có tối đa một pet chung; mỗi người có thể nuôi cùng các bạn khác nhau.
6. Chọn cảm xúc cá nhân sẽ ghi tác động vào mọi pet chung của người đó, bao gồm tên cảm xúc trong lịch sử chỉ hai chủ pet được xem. Quyền cảm xúc cá nhân kiểm soát avatar/danh sách người dùng; lịch sử pet là dữ liệu dùng chung được giải thích ngay trên giao diện.

## Điểm và animation

- Quy đổi cảm xúc: `backend/config/cam_xuc.php`: Vui +10, Buồn -10, Mệt mỏi -5, Tức giận -8, Lo lắng -5, Bình thường 0.
- Chăm sóc: `backend/config/pet.php`: An ủi +6, Ôm +8, Chơi cùng +10, Cho ăn +5, Nghỉ ngơi +4; cooldown 10 giây/người/pet.
- `QuyDoiDiemPet` giới hạn 0–100 và chia khoảng: 0–20 rất buồn; 21–40 buồn; 41–60 bình thường; 61–80 vui; 81–100 rất vui.
- Pet giữ trạng thái điểm và biểu cảm của lần chọn cảm xúc gần nhất riêng biệt. Khi một người đổi sang mệt/tức giận/lo lắng, biểu cảm phản ánh ngay cảm xúc đó; sau hành động chăm sóc, animation theo khoảng điểm. Bình thường dùng biểu cảm ổn định, không đặt lại điểm đã tích lũy.
- Lịch sử lưu mức điểm thực tế sau giới hạn (ví dụ 99 + 10 thành 100, lịch sử ghi +1).
- Animation là SVG/CSS nội bộ React, không cần thư viện hoặc tải JSON ngoài; hỗ trợ giảm chuyển động của hệ điều hành và ảnh dự phòng.

## An toàn dữ liệu

- Client không được chỉ định chủ avatar/cảm xúc. Backend luôn dùng tài khoản từ session.
- Pet chung có `idTaiKhoan` nhỏ hơn `idTaiKhoan2`, hai tài khoản khác nhau; API không có thao tác thêm hoặc thay thành viên.
- Khóa cặp tài khoản theo thứ tự ID trước khi gửi/duyệt lời mời. Unique index bảo vệ cặp lời mời và cặp pet. Lời mời từ chối có thể gửi lại bằng cùng bản ghi.
- Khóa hàng tài khoản khi cập nhật cảm xúc; khóa các pet theo thứ tự ID; ghi cảm xúc, điểm và lịch sử trong cùng transaction, retry deadlock tối đa 3 lần.
- Chăm sóc khóa hàng pet trước khi kiểm tra cooldown, tính điểm và ghi lịch sử. Client gửi điểm hoặc người nhận giả không ảnh hưởng phép tính.
- Người ngoài nhận 404 khi truy cập/đổi/chăm sóc pet; private channel pet kiểm tra chính sách hai chủ. Không có public channel chứa dữ liệu pet.
- Không chạy `migrate:fresh` trên database dự án. Rollback bỏ cột mới sẽ mất dữ liệu tính năng mới; ưu tiên migration sửa tiến. Các mục cảm xúc đã thêm được giữ lại khi rollback vì bài viết có thể đang tham chiếu.

## API mới

Tất cả có prefix `/api` và yêu cầu đăng nhập. Response theo cấu trúc `data` hiện có.

| Method | Path | Input / kết quả |
|---|---|---|
| GET | `/avatar-animation` | 4 mẫu avatar |
| PUT | `/avatar-animation/me` | `MaAvatarAnimation`: mã hoặc null |
| GET | `/cam-xuc-ca-nhan/danh-muc` | 6 cảm xúc đang sử dụng |
| GET | `/cam-xuc-ca-nhan?ids[]=1&ids[]=2` | Avatar + cảm xúc được phép xem; tối đa 100 ID/lần |
| PUT | `/cam-xuc-ca-nhan/me` | `idCamXuc`, `CheDoHienThi` |
| POST | `/realtime/auth` | `socket_id`, `channel_name`; Echo tự gọi |
| GET | `/loi-moi-nuoi-pet` | Lời mời đang chờ, bạn bè, danh mục pet/hành động |
| POST | `/loi-moi-nuoi-pet` | `idTaiKhoan`, `TenPet`, `LoaiPet` |
| PATCH | `/loi-moi-nuoi-pet/{id}` | `action`: `chap_nhan` / `tu_choi` |
| GET | `/pet-chung` | Pet chung của người đăng nhập |
| GET | `/pet-chung/{id}` | Chi tiết, 2 thành viên, 50 mục lịch sử gần nhất |
| PATCH | `/pet-chung/{id}` | `TenPet`, `LoaiPet` |
| POST | `/pet-chung/{id}/tuong-tac` | `MaHanhDong`: `an_ui`, `om`, `choi_cung`, `cho_an`, `nghi_ngoi` |

Response bài viết/bình luận cũ được bổ sung `idTaiKhoan` để component avatar lấy trạng thái; các trường cũ được giữ.

## Chạy ứng dụng

Bật MySQL của Laragon trước. Hai migration đã chạy thành công trên database `mxhcx` trong lần triển khai này.

```powershell
cd C:\UngDungChiaSe\backend
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8000
```

Terminal frontend:

```powershell
cd C:\UngDungChiaSe\frontend
npm run dev -- --host 127.0.0.1
```

Mở địa chỉ Vite in ra trong terminal. Cấu hình proxy `/api` đang trỏ về cổng 8000. Không thay `.env`, không thêm dependency.

## Realtime và polling

Đã tích hợp Reverb hiện có. Để nhận sự kiện WebSocket, chạy thêm hai terminal backend:

```powershell
php artisan reverb:start
php artisan queue:work --tries=3
```

Giữ cấu hình `BROADCAST_CONNECTION=reverb`, `QUEUE_CONNECTION=database`; các biến `VITE_REVERB_*` phải khớp Reverb. Khi đổi biến Vite, khởi động lại Vite/build.

- `cam-xuc.{id}` và `pet.{id}` là private channel; sự kiện chỉ mang ID để client lấy lại dữ liệu qua API có phân quyền. Không nhúng cảm xúc riêng tư vào payload broadcast.
- `PetDaThayDoi` phát sau commit. Sự kiện không làm lộ lịch sử hoặc điểm qua channel công khai.
- Polling mỗi 15 giây là phương án dự phòng kể cả khi Reverb/queue worker tắt. Gộp ID avatar, chia tối đa 100 ID/lần, dừng polling khi tab ẩn và tải lại khi quay về. Lời mời mới được phát hiện qua polling.
- Tham khảo [Laravel Broadcasting](https://laravel.com/framework/docs/broadcasting) và [khóa hàng trong transaction](https://laravel.com/framework/docs/13.x/queries#pessimistic-locking).

## Kiểm thử

```powershell
cd C:\UngDungChiaSe\backend
php artisan test --compact
php vendor/bin/pint --dirty --format agent

cd C:\UngDungChiaSe\frontend
npm run build
npm run lint
```

Tests sử dụng SQLite `:memory:` từ phpunit.xml, không xóa database MySQL dự án.

Luồng thủ công với 3 tài khoản A, B, C (dùng 2 trình duyệt/profile riêng):

1. A và B là bạn. A chọn avatar/cảm xúc, reload; kiểm tra avatar ở bài viết, bình luận, trang cá nhân, tìm bạn.
2. A chọn quyền Bạn bè: B thấy cảm xúc, C không thấy. Chuyển Chỉ mình tôi: B không còn thấy cảm xúc sau đồng bộ.
3. A mời B. Chưa chấp nhận thì chưa có pet. Thử gửi trùng và B gửi ngược: bị từ chối. B từ chối rồi A gửi lại; B đồng ý.
4. C thử ID pet qua GET/PATCH/POST và private channel: bị chặn. A/B thấy đúng hai chủ, C không xuất hiện.
5. A chọn Buồn: điểm giảm, lịch sử có A, cảm xúc, điểm và thời gian. B chăm sóc: điểm tăng; bấm lại ngay bị cooldown.
6. A/B thao tác gần nhau; lịch sử và điểm phải nhất quán. Chạy nhiều lần ở biên 0/100 để kiểm tra không vượt giới hạn.
7. Mở lịch sử, thử đổi loại pet từ một tài khoản và quan sát tài khoản kia. Thử tắt WebSocket: polling vẫn cập nhật trong khoảng 15 giây khi tab hiển thị.

Đã chạy: migration MySQL, test backend, build/lint frontend; kiểm tra giao diện desktop và lưu/reload avatar bằng tài khoản mẫu. Không coi test SQLite là kiểm thử tải đồng thời trên MySQL; WebSocket hai trình duyệt và stress test concurrent MySQL cần kiểm chứng riêng khi triển khai.

## Danh sách file của thay đổi này

Các đường dẫn dưới đây tính từ `C:\UngDungChiaSe`. Những file đã có thay đổi trước phiên làm việc được giữ lại; danh sách này chỉ ghi file tính năng này tạo/sửa.

### Backend — tạo mới

- `backend/config/cam_xuc.php`
- `backend/config/pet.php`
- `backend/app/Events/CamXucDaThayDoi.php`
- `backend/app/Events/PetDaThayDoi.php`
- `backend/app/Policies/CamXucPolicy.php`
- `backend/app/Policies/PetPolicy.php`
- `backend/app/Services/DichVuCamXuc.php`
- `backend/app/Services/DichVuPet.php`
- `backend/app/Services/QuyDoiDiemPet.php`
- `backend/app/Models/LoiMoiNuoiPet.php`
- `backend/app/Models/LichSuCamXucPet.php`
- `backend/app/Http/Controllers/LoiMoiNuoiPetController.php`
- `backend/database/migrations/2026_09_15_151229_bo_sung_avatar_animation_va_quyen_cam_xuc.php`
- `backend/database/migrations/2026_09_15_152237_bo_sung_pet_chung.php`
- `backend/tests/Feature/AvatarCamXucTest.php`
- `backend/tests/Feature/PetChungTest.php`

### Backend — sửa

- `backend/app/Http/Controllers/AvatarController.php`
- `backend/app/Http/Controllers/TrangThaiCamXucController.php`
- `backend/app/Http/Controllers/BaiVietController.php` (thêm ID tác giả và Pint định dạng)
- `backend/app/Http/Controllers/PetController.php`
- `backend/app/Http/Controllers/TuongTacPetController.php`
- `backend/app/Models/TaiKhoan.php`
- `backend/app/Models/TrangThaiCamXuc.php`
- `backend/app/Models/Pet.php`
- `backend/app/Models/TuongTacPet.php`
- `backend/routes/api.php` (file đã tồn tại nhưng chưa được Git theo dõi trước phiên này)
- `backend/routes/channels.php`

### Frontend — tạo mới

- `frontend/src/services/DichVuAvatar.js`
- `frontend/src/services/DichVuCamXuc.js`
- `frontend/src/services/DichVuPet.js`
- `frontend/src/components/AvatarAnimation.jsx`
- `frontend/src/components/AvatarAnimation.css`
- `frontend/src/components/BoChonAvatar.jsx`
- `frontend/src/components/BoChonCamXuc.jsx`
- `frontend/src/components/ChinhSuaCamXuc.jsx`
- `frontend/src/components/NguCanhCamXuc.jsx`
- `frontend/src/components/NguCanhCamXucContext.js`
- `frontend/src/components/PetAnimation.jsx`
- `frontend/src/components/PetChung.jsx`
- `frontend/src/components/PetChung.css`
- `frontend/src/components/LoiMoiNuoiPet.jsx`
- `frontend/src/components/LichSuTuongTacPet.jsx`

### Frontend — sửa

- `frontend/src/App.jsx`
- `frontend/src/realtime.js`
- `frontend/src/pages/TrangTuong.jsx`
- `frontend/src/pages/TrangCaNhan.jsx`
- `frontend/src/components/wall/BaiDang.jsx`
- `frontend/src/components/wall/KhungDangBai.jsx`
- `frontend/src/components/wall/ThanhBenTrai.jsx`
- `frontend/src/components/wall/ThanhBenPhai.jsx`
- `frontend/src/components/wall/HoatDongTin.jsx` (file đã tồn tại nhưng chưa được Git theo dõi)

### Tài liệu

- `HUONG_DAN_AVATAR_CAM_XUC_PET.md` (file này).
