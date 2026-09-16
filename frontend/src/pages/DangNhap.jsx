import { useState } from "react";
import LoginForm from "../components/FormDangNhap";
import { login } from "../api/authApi";

export default function LoginPage() {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState("");

    const handleLogin = async (data) => {
        try {
            setLoading(true);
            setError("");

            const result = await login(data);

            console.log(result);

            // sau này lưu user/token ở đây
        } catch (err) {
            setError(
                err.response?.data?.message ||
                "Đăng nhập thất bại"
            );
        } finally {
            setLoading(false);
        }
    };

    return (
        <LoginForm
            onSubmit={handleLogin}
            loading={loading}
            error={error}
            onRegister={() => setDangKy(true)}
        />
    );
}