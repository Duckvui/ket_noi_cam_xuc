import { useState } from "react";
import "../styles/XacThuc.css";

import FormDangKy from "../components/FormDangKy";
import { dichVuXacThuc } from "../services/dichVuXacThuc";

export default function TrangDangKy({ onLogin, onRegistered }) {
    const [dangXuLy, setDangXuLy] = useState(false);
    const [loi, setLoi] = useState("");

    const dangKy = async (duLieu) => {
        if (dangXuLy) return;
        setDangXuLy(true);
        setLoi("");

        try {
            const result = await dichVuXacThuc.dangKy(duLieu);
            onRegistered(result.data);
        } catch (error) {
            const danhSachLoi = error.response?.data?.errors;
            const loiDauTien = danhSachLoi
                ? Object.values(danhSachLoi)[0]?.[0]
                : null;

            setLoi(
                loiDauTien ||
                error.response?.data?.message ||
                error.message ||
                "Đăng ký không thành công."
            );
        } finally {
            setDangXuLy(false);
        }
    };

    return (
        <main className="trang-xac-thuc">
            <FormDangKy
                khiDangKy={dangKy}
                dangXuLy={dangXuLy}
                loi={loi}
                chuyenDangNhap={onLogin}
            />
        </main>
    );
}
