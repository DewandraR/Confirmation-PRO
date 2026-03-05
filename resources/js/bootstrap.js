import axios from "axios";
window.axios = axios;

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

// 1. Wajib tambahkan ini agar Axios mengirimkan cookie session kembali ke server
window.axios.defaults.withCredentials = true;

// 2. Wajib tangkap token CSRF dari meta tag HTML dan masukkan ke header Axios
let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common["X-CSRF-TOKEN"] = token.content;
} else {
    console.error(
        "CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token",
    );
}
