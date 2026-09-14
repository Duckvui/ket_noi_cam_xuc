import axios from "axios";

export const login = async (data) => {
    const response = await axios.post(
        "http://localhost:8000/api/login",
        data
    );

    return response.data;
};