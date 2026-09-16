import { api } from "../api";

export const dichVuXacThuc = {
    dangNhap(duLieu) {
        return api.post("/login", duLieu);
    },

    async dangKy(duLieu) {
        const response = await api("/auth/register", {
            method: "POST",
            headers: { "Content-Type": "application/json", Accept: "application/json" },
            body: JSON.stringify(duLieu),
        });
        const result = await response.json().catch(() => null);
        if (!response.ok) {
            throw new Error(response.status === 404
                ? "Chức năng đăng ký tài khoản chưa được kết nối với máy chủ."
                : Object.values(result?.errors || {}).flat()[0] || result?.message || "Đăng ký không thành công.");
        }
        return result;
    },

    dangXuat() {
        return api.post("/logout");
    },

    layNguoiDungHienTai() {
        return api.get("/user");
    },
};
