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

    const thayDoiDuLieu = (e) => {
        const { name, value } = e.target;

        setDuLieu((cu) => ({
            ...cu,
            [name]: value,
        }));
    };

    const xuLyDangKy = (e) => {
        e.preventDefault();

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

    const styleInput = {
        width: "100%",
        padding: "11px 12px",
        marginTop: "6px",
        marginBottom: "14px",
        border: "1px solid #f3b6c8",
        borderRadius: "8px",
        outline: "none",
        boxSizing: "border-box",
    };

    return (
        <form
            onSubmit={xuLyDangKy}
            style={{
                width: "100%",
                maxWidth: "460px",
                margin: "0 auto",
            }}
        >
            <h2
                style={{
                    textAlign: "center",
                    color: "#e75480",
                    marginBottom: "8px",
                }}
            >
                Đăng ký
            </h2>

            <p
                style={{
                    textAlign: "center",
                    color: "#777",
                    marginBottom: "24px",
                }}
            >
                Tạo tài khoản để bắt đầu kết nối
            </p>

            <div>
                <label>Họ và tên</label>

                <input
                    name="HoTen"
                    type="text"
                    value={duLieu.HoTen}
                    onChange={thayDoiDuLieu}
                    placeholder="Nhập họ và tên"
                    disabled={dangXuLy}
                    required
                    style={styleInput}
                />
            </div>

            <div>
                <label>Ngày sinh</label>

                <input
                    name="NamSinh"
                    type="date"
                    value={duLieu.NamSinh}
                    onChange={thayDoiDuLieu}
                    disabled={dangXuLy}
                    style={styleInput}
                />
            </div>

            <div>
                <label>Giới thiệu bản thân</label>

                <textarea
                    name="GioiThieu"
                    rows={3}
                    value={duLieu.GioiThieu}
                    onChange={thayDoiDuLieu}
                    placeholder="Viết vài dòng về bạn..."
                    disabled={dangXuLy}
                    style={{
                        ...styleInput,
                        resize: "vertical",
                    }}
                />
            </div>

            <div>
                <label>Tài khoản</label>

                <input
                    name="TaiKhoan"
                    type="text"
                    value={duLieu.TaiKhoan}
                    onChange={thayDoiDuLieu}
                    placeholder="Email hoặc số điện thoại"
                    disabled={dangXuLy}
                    required
                    style={styleInput}
                />
            </div>

            <div>
                <label>Mật khẩu</label>

                <input
                    name="MatKhau"
                    type="password"
                    value={duLieu.MatKhau}
                    onChange={thayDoiDuLieu}
                    placeholder="Ít nhất 8 ký tự"
                    disabled={dangXuLy}
                    required
                    style={styleInput}
                />
            </div>

            <div>
                <label>Xác nhận mật khẩu</label>

                <input
                    name="MatKhau_confirmation"
                    type="password"
                    value={duLieu.MatKhau_confirmation}
                    onChange={thayDoiDuLieu}
                    placeholder="Nhập lại mật khẩu"
                    disabled={dangXuLy}
                    required
                    style={styleInput}
                />
            </div>

            {(loiNoiBo || loi) && (
                <p
                    style={{
                        color: "#d6336c",
                        marginBottom: "12px",
                    }}
                >
                    {loiNoiBo || loi}
                </p>
            )}

            <button
                type="submit"
                disabled={dangXuLy}
                style={{
                    width: "100%",
                    padding: "12px",
                    border: "none",
                    borderRadius: "8px",
                    background: "#e75480",
                    color: "white",
                    fontWeight: "600",
                    cursor: dangXuLy ? "not-allowed" : "pointer",
                }}
            >
                {dangXuLy ? "Đang đăng ký..." : "Đăng ký"}
            </button>

            <div
                style={{
                    marginTop: "15px",
                    textAlign: "center",
                }}
            >
                <span>Đã có tài khoản? </span>

                <button
                    type="button"
                    onClick={chuyenDangNhap}
                    disabled={dangXuLy}
                    style={{
                        background: "none",
                        border: "none",
                        color: "#e75480",
                        cursor: "pointer",
                        textDecoration: "underline",
                    }}
                >
                    Đăng nhập
                </button>
            </div>
        </form>
    );
}