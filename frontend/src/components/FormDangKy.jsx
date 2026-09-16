import { useState } from "react";

export default function FormDangKy({
    khiDangKy,
    dangXuLy = false,
    loi = "",
    chuyenDangNhap,
}) {
    const [duLieu, setDuLieu] = useState({
        HoTen: "",
        NamSinh: "",
        GioiThieu: "",
        TaiKhoan: "",
        MatKhau: "",
        MatKhau_confirmation: "",
    });

    const [loiNoiBo, setLoiNoiBo] = useState("");

    const thayDoiDuLieu = (suKien) => {
        const { name, value } = suKien.target;

        setDuLieu((duLieuCu) => ({
            ...duLieuCu,
            [name]: value,
        }));
    };

    const xuLyDangKy = (suKien) => {
        suKien.preventDefault();
        if (dangXuLy) return;
        setLoiNoiBo("");

        if (duLieu.MatKhau.length < 8) {
            setLoiNoiBo("Mật khẩu phải có ít nhất 8 ký tự.");
            return;
        }

        if (duLieu.MatKhau !== duLieu.MatKhau_confirmation) {
            setLoiNoiBo("Mật khẩu xác nhận không khớp.");
            return;
        }

        khiDangKy({
            ...duLieu,
            HoTen: duLieu.HoTen.trim(),
            TaiKhoan: duLieu.TaiKhoan.trim(),
            NamSinh: duLieu.NamSinh || null,
            GioiThieu: duLieu.GioiThieu.trim() || null,
        });
    };

    return (
        <form className="form-xac-thuc" onSubmit={xuLyDangKy}>
            <h2>Đăng ký</h2>

            <p className="mo-ta-form">
                Tạo tài khoản để bắt đầu kết nối
            </p>

            <h3 className="tieu-de-xac-thuc">Thông tin cá nhân</h3>
            <div className="nhom-nhap">
                <label htmlFor="HoTen">Họ và tên</label>
                <input
                    id="HoTen"
                    name="HoTen"
                    type="text"
                    value={duLieu.HoTen}
                    maxLength={255}
                    autoComplete="name"
                    onChange={thayDoiDuLieu}
                    placeholder="Nhập họ và tên"
                    disabled={dangXuLy}
                    required
                />
            </div>

            <div className="nhom-nhap">
                <label htmlFor="NamSinh">Ngày sinh (không bắt buộc)</label>
                <input
                    id="NamSinh"
                    name="NamSinh"
                    type="date"
                    autoComplete="bday"
                    value={duLieu.NamSinh}
                    onChange={thayDoiDuLieu}
                    disabled={dangXuLy}
                />
            </div>

            <div className="nhom-nhap">
                <label htmlFor="GioiThieu">Giới thiệu bản thân (không bắt buộc)</label>
                <textarea
                    id="GioiThieu"
                    name="GioiThieu"
                    rows={3}
                    maxLength={2000}
                    value={duLieu.GioiThieu}
                    onChange={thayDoiDuLieu}
                    placeholder="Viết vài dòng về bạn..."
                    disabled={dangXuLy}
                />
            </div>

            <h3 className="tieu-de-xac-thuc">Thông tin đăng nhập</h3>
            <div className="nhom-nhap">
                <label htmlFor="TaiKhoan">Tên tài khoản, email hoặc số điện thoại</label>
                <input
                    id="TaiKhoan"
                    name="TaiKhoan"
                    type="text"
                    value={duLieu.TaiKhoan}
                    maxLength={255}
                    autoComplete="username"
                    onChange={thayDoiDuLieu}
                    placeholder="Tên tài khoản"
                    disabled={dangXuLy}
                    required
                />
            </div>

            <div className="hang-mat-khau">
                <div className="nhom-nhap">
                    <label htmlFor="MatKhau">Mật khẩu</label>
                    <input
                        id="MatKhau"
                        name="MatKhau"
                        type="password"
                        autoComplete="new-password"
                        minLength={8}
                        maxLength={72}
                        value={duLieu.MatKhau}
                        onChange={thayDoiDuLieu}
                        placeholder="Ít nhất 8 ký tự"
                        disabled={dangXuLy}
                        required
                    />
                </div>

                <div className="nhom-nhap">
                    <label htmlFor="MatKhau_confirmation">
                        Xác nhận
                    </label>

                    <input
                        id="MatKhau_confirmation"
                        name="MatKhau_confirmation"
                        type="password"
                        autoComplete="new-password"
                        maxLength={72}
                        value={duLieu.MatKhau_confirmation}
                        onChange={thayDoiDuLieu}
                        placeholder="Nhập lại"
                        disabled={dangXuLy}
                        required
                    />
                </div>
            </div>

            {(loiNoiBo || loi) && (
                <p className="thong-bao-loi" role="alert">{loiNoiBo || loi}</p>
            )}

            <button
                className="nut-xac-thuc"
                type="submit"
                disabled={dangXuLy}
            >
                {dangXuLy ? "Đang đăng ký..." : "Đăng ký"}
            </button>

            <p className="chuyen-form">
                Đã có tài khoản?{" "}
                <button type="button" disabled={dangXuLy} onClick={chuyenDangNhap}>
                    Đăng nhập
                </button>
            </p>
        </form>
    );
}
