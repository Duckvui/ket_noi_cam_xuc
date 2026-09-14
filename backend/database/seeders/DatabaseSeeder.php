<?php

namespace Database\Seeders;

use App\Models\Avatar;
use App\Models\AvatarCamXuc;
use App\Models\BaiViet;
use App\Models\BanBe;
use App\Models\BinhLuan;
use App\Models\CamXuc;
use App\Models\CuocTroChuyen;
use App\Models\GuiKetBan;
use App\Models\Pet;
use App\Models\TaiKhoan;
use App\Models\ThanhVienCuocTroChuyen;
use App\Models\TheoDoi;
use App\Models\ThongBao;
use App\Models\ThongTinCaNhan;
use App\Models\TinNhan;
use App\Models\TrangThaiCamXuc;
use App\Models\TrangThaiTaiKhoan;
use App\Models\TuongTacBaiViet;
use App\Models\TuongTacPet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $an = TaiKhoan::updateOrCreate(['TaiKhoan' => 'an.nguyen@example.com'], ['MatKhau' => Hash::make('password'), 'LoaiTaiKhoan' => 'NguoiDung', 'TrangThaiTaiKhoan' => 'Hoat_Dong']);
            $binh = TaiKhoan::updateOrCreate(['TaiKhoan' => 'binh.tran@example.com'], ['MatKhau' => Hash::make('password'), 'LoaiTaiKhoan' => 'NguoiDung', 'TrangThaiTaiKhoan' => 'Hoat_Dong']);
            $chi = TaiKhoan::updateOrCreate(['TaiKhoan' => 'chi.le@example.com'], ['MatKhau' => Hash::make('password'), 'LoaiTaiKhoan' => 'Admin', 'TrangThaiTaiKhoan' => 'Hoat_Dong']);
            $minh = TaiKhoan::updateOrCreate(['TaiKhoan' => 'minh.nguyen@example.com'], ['MatKhau' => Hash::make('password'), 'LoaiTaiKhoan' => 'NguoiDung', 'TrangThaiTaiKhoan' => 'Hoat_Dong']);
            $lan = TaiKhoan::updateOrCreate(['TaiKhoan' => 'lan.pham@example.com'], ['MatKhau' => Hash::make('password'), 'LoaiTaiKhoan' => 'NguoiDung', 'TrangThaiTaiKhoan' => 'Hoat_Dong']);
            $huy = TaiKhoan::updateOrCreate(['TaiKhoan' => 'huy.tran@example.com'], ['MatKhau' => Hash::make('password'), 'LoaiTaiKhoan' => 'NguoiDung', 'TrangThaiTaiKhoan' => 'Hoat_Dong']);
            $mai = TaiKhoan::updateOrCreate(['TaiKhoan' => 'mai.vo@example.com'], ['MatKhau' => Hash::make('password'), 'LoaiTaiKhoan' => 'NguoiDung', 'TrangThaiTaiKhoan' => 'Hoat_Dong']);

            $vui = CamXuc::updateOrCreate(['TenCamXuc' => 'Vui'], ['BieuTuong' => '😊', 'MoTa' => 'Cảm thấy vui vẻ', 'TrangThai' => 'Dang_Su_Dung']);
            $buon = CamXuc::updateOrCreate(['TenCamXuc' => 'Buon'], ['BieuTuong' => '😢', 'MoTa' => 'Cảm thấy buồn', 'TrangThai' => 'Dang_Su_Dung']);

            foreach ([[$an, 'Nguyễn', 'An', '2002-05-12'], [$binh, 'Trần', 'Bình', '2001-11-03'], [$chi, 'Lê', 'Chi', '2000-08-20']] as [$taiKhoan, $ho, $ten, $namSinh]) {
                ThongTinCaNhan::updateOrCreate(['idTaiKhoan' => $taiKhoan->idTaiKhoan], ['Ho' => $ho, 'Ten' => $ten, 'NamSinh' => $namSinh, 'GioiThieu' => "Xin chào, mình là {$ten}."]);
                Avatar::updateOrCreate(['idTaiKhoan' => $taiKhoan->idTaiKhoan], ['AnhAvatar' => "avatars/{$taiKhoan->idTaiKhoan}.jpg", 'AnhBia' => "covers/{$taiKhoan->idTaiKhoan}.jpg", 'NgayCapNhatAvatar' => now(), 'NgayCapNhatAnhBia' => now()]);
                TrangThaiTaiKhoan::updateOrCreate(['idTaiKhoan' => $taiKhoan->idTaiKhoan], ['TrangThaiTaiKhoan' => 'Hoat_Dong']);
            }
            foreach ([[$minh, 'Nguyễn', 'Minh', '2001-08-21', 'Yêu nhiếp ảnh và những chuyến đi ngắn.'], [$lan, 'Phạm', 'Lan', '2002-11-15', 'Chia sẻ những điều tích cực mỗi ngày.'], [$huy, 'Trần', 'Huy', '2000-03-09', 'Mê cà phê, âm nhạc và thú cưng.'], [$mai, 'Võ', 'Mai', '2003-06-18', 'Thích đọc sách và kết nối với bạn mới.']] as [$taiKhoan, $ho, $ten, $namSinh, $gioiThieu]) {
                ThongTinCaNhan::updateOrCreate(['idTaiKhoan' => $taiKhoan->idTaiKhoan], ['Ho' => $ho, 'Ten' => $ten, 'NamSinh' => $namSinh, 'GioiThieu' => $gioiThieu]);
                Avatar::updateOrCreate(['idTaiKhoan' => $taiKhoan->idTaiKhoan], ['AnhAvatar' => "avatars/{$taiKhoan->idTaiKhoan}.jpg", 'AnhBia' => "covers/{$taiKhoan->idTaiKhoan}.jpg", 'NgayCapNhatAvatar' => now(), 'NgayCapNhatAnhBia' => now()]);
                TrangThaiTaiKhoan::updateOrCreate(['idTaiKhoan' => $taiKhoan->idTaiKhoan], ['TrangThaiTaiKhoan' => 'Hoat_Dong']);
            }

            AvatarCamXuc::updateOrCreate(['idTaiKhoan' => $an->idTaiKhoan, 'idCamXuc' => $vui->idCamXuc], ['AnhAvatarCamXuc' => 'avatars/emotions/an-vui.jpg', 'TrangThai' => 'Dang_Su_Dung']);
            TrangThaiCamXuc::updateOrCreate(['idTaiKhoan' => $an->idTaiKhoan, 'idCamXuc' => $vui->idCamXuc, 'TrangThai' => 'Hien_Tai'], ['NoiDung' => 'Hôm nay mình rất vui!', 'MucDoCamXuc' => 9]);
            TrangThaiCamXuc::updateOrCreate(['idTaiKhoan' => $binh->idTaiKhoan, 'idCamXuc' => $buon->idCamXuc, 'TrangThai' => 'Hien_Tai'], ['NoiDung' => 'Một ngày bình yên.', 'MucDoCamXuc' => 6]);

            GuiKetBan::updateOrCreate(['idGuiKetBan' => $an->idTaiKhoan, 'idDuocKetBan' => $chi->idTaiKhoan], ['TrangThaiLoiMoi' => 'Cho_Xac_Nhan']);
            BanBe::updateOrCreate(['idTaiKhoan1' => $an->idTaiKhoan, 'idTaiKhoan2' => $binh->idTaiKhoan], ['TrangThai' => 'Dang_La_Ban']);
            TheoDoi::updateOrCreate(['idTaiKhoan' => $binh->idTaiKhoan, 'idTaiKhoanTheoDoi' => $an->idTaiKhoan], ['TrangThai' => true]);

            $baiViet = BaiViet::updateOrCreate(['idTaiKhoan' => $an->idTaiKhoan, 'NoiDung' => 'Chúc mọi người một ngày thật nhiều năng lượng!'], ['HinhAnh' => 'posts/ngay-moi.jpg', 'idCamXuc' => $vui->idCamXuc, 'CheDoHienThi' => 'Cong_Khai', 'TrangThaiBaiViet' => 'Binh_Thuong']);
            foreach ([[$minh, 'Cuối tuần này mình vừa khám phá một quán cà phê nhỏ rất xinh.'], [$lan, 'Chúc mọi người có một ngày nhẹ nhàng và nhiều niềm vui nhé!'], [$huy, 'Ai cũng cần một khoảng lặng để lắng nghe chính mình.'], [$mai, 'Mới đọc xong một cuốn sách hay, muốn tìm bạn cùng trao đổi.']] as [$taiKhoan, $noiDung]) {
                BaiViet::updateOrCreate(['idTaiKhoan' => $taiKhoan->idTaiKhoan, 'NoiDung' => $noiDung], ['CheDoHienThi' => 'Cong_Khai', 'TrangThaiBaiViet' => 'Binh_Thuong']);
            }
            $binhLuan = BinhLuan::updateOrCreate(['idBaiViet' => $baiViet->idBaiViet, 'idTaiKhoan' => $binh->idTaiKhoan, 'NoiDung' => 'Bài viết tích cực quá!'], ['TrangThai' => 'Binh_Thuong']);
            BinhLuan::updateOrCreate(['idBaiViet' => $baiViet->idBaiViet, 'idTaiKhoan' => $an->idTaiKhoan, 'NoiDung' => 'Cảm ơn Bình nhé!'], ['idBinhLuanCha' => $binhLuan->idBinhLuan, 'TrangThai' => 'Binh_Thuong']);
            TuongTacBaiViet::updateOrCreate(['idBaiViet' => $baiViet->idBaiViet, 'idTaiKhoan' => $binh->idTaiKhoan], ['LoaiTuongTac' => 'Yeu_Thich']);

            $pet = Pet::updateOrCreate(['idTaiKhoan' => $an->idTaiKhoan, 'TenPet' => 'Mochi'], ['LoaiPet' => 'Meo', 'AnhPet' => 'pets/mochi.jpg', 'idCamXucHienTai' => $vui->idCamXuc, 'TrangThaiPet' => 'Vui_ve', 'CapDo' => 3, 'KinhNghiem' => 250]);
            TuongTacPet::updateOrCreate(['idNguoiGui' => $binh->idTaiKhoan, 'idNguoiNhan' => $an->idTaiKhoan, 'idPet' => $pet->idPet, 'LoaiTuongTac' => 'Cho_An'], ['NoiDung' => 'Mochi ăn ngoan nhé!']);

            $cuocTroChuyen = CuocTroChuyen::updateOrCreate(['LoaiCuocTroChuyen' => 'Ca_Nhan'], ['TrangThai' => 'Dang_Hoat_Dong']);
            foreach ([$an, $binh] as $taiKhoan) {
                ThanhVienCuocTroChuyen::updateOrCreate(['idCuocTroChuyen' => $cuocTroChuyen->idCuocTroChuyen, 'idTaiKhoan' => $taiKhoan->idTaiKhoan], ['TrangThai' => 'Dang_Tham_Gia']);
            }
            TinNhan::updateOrCreate(['idCuocTroChuyen' => $cuocTroChuyen->idCuocTroChuyen, 'idNguoiGui' => $an->idTaiKhoan, 'NoiDung' => 'Chào Bình, hôm nay bạn thế nào?'], ['LoaiTinNhan' => 'Van_Ban', 'TrangThaiTinNhan' => 'Da_Xem']);
            ThongBao::updateOrCreate(['idTaiKhoanNhan' => $an->idTaiKhoan, 'idTaiKhoanGui' => $binh->idTaiKhoan, 'LoaiThongBao' => 'Tuong_Tac', 'idDoiTuong' => $baiViet->idBaiViet], ['NoiDung' => 'Bình đã yêu thích bài viết của bạn.', 'DaDoc' => false]);
        });
    }
}
