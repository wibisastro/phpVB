# Gov2 Login

Aplikasi autentikasi SSO (Single Sign-On) untuk phpVB.

## Fitur

- Login via SSO iframe
- Signup akun baru
- Profile pengguna (dari JWT session)
- Forgot password (redirect ke SSO)
- Logout (clear session + redirect)

## Halaman

| URL | Fungsi | Auth |
|-----|--------|------|
| `/{pageID}/login?type=gov2` | Form login SSO | public |
| `/{pageID}/signup` | Form registrasi | public |
| `/{pageID}/profile` | Halaman profil | guest |
| `/{pageID}/forgot` | Reset password | public |

## Catatan

- Login links dari app lain harus pakai pattern `/{pageID}/login?type=gov2`
- Profile menampilkan: account_id, fullname, email, role, email_verified, expired_in
- Tombol "Ganti Password" redirect ke SSO node
