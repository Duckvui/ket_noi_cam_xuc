import { useState } from "react";

export default function LoginForm({ onSubmit, loading, error }) {
    const [taiKhoan, setTaiKhoan] = useState("");
    const [matKhau, setMatKhau] = useState("");

    const handleSubmit = (e) => {
        e.preventDefault();

        onSubmit({
            TaiKhoan: taiKhoan,
            MatKhau: matKhau,
        });
    };

    return (
        <form onSubmit={handleSubmit}>
            <div>
                <label>Tài khoản</label>
                <input
                    type="text"
                    value={taiKhoan}
                    onChange={(e) => setTaiKhoan(e.target.value)}
                    placeholder="Email hoặc số điện thoại"
                />
            </div>

            <div>
                <label>Mật khẩu</label>
                <input
                    type="password"
                    value={matKhau}
                    onChange={(e) => setMatKhau(e.target.value)}
                    placeholder="Nhập mật khẩu"
                />
            </div>

            {error && <p>{error}</p>}

            <button type="submit" disabled={loading}>
                {loading ? "Đang đăng nhập..." : "Đăng nhập"}
            </button>

            <div style={{ marginTop: "15px" }}>
                <span>Chưa có tài khoản? </span>

                <button
                    type="button"
                    style={{
                        background: "none",
                        border: "none",
                        cursor: "pointer",
                        textDecoration: "underline",
                    }}
                >
                    Đăng ký tài khoản mới
                </button>
            </div>
        </form>
    );
}