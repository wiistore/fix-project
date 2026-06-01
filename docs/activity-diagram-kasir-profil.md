# Activity Diagram - Reset Password Kasir (Halaman Profil)

```mermaid
%%{ init: { 'flowchart': { 'curve': 'linear' } } }%%
flowchart TD
    A([Start]) --> B[Kasir membuka halaman Profil]
    B --> C[Sistem menampilkan data profil<br/>Username & Email read-only]
    C --> D[Kasir mengisi form ganti password:<br/>- Password saat ini<br/>- Password baru min 8 karakter<br/>- Konfirmasi password baru]
    D --> E[Klik tombol 'Simpan Password']
    E --> F{Ada field kosong?}

    F -->|Ya| G[Tampilkan error:<br/>'Wajib diisi']
    G --> Z([Stop])

    F -->|Tidak| H{Password baru < 8 karakter?}
    H -->|Ya| I[Tampilkan error:<br/>'Minimal 8 karakter']
    I --> Z

    H -->|Tidak| J{Konfirmasi = Password baru?}
    J -->|Tidak sama| K[Tampilkan error:<br/>'Konfirmasi tidak cocok']
    K --> Z

    J -->|Sama| L[Sistem verifikasi password saat ini]
    L --> M{Password saat ini benar?}

    M -->|Tidak| N[Tampilkan error:<br/>'Password saat ini salah']
    N --> Z

    M -->|Ya| O[Hash password baru]
    O --> P[Update password di database]
    P --> Q[Tampilkan pesan:<br/>'Password berhasil diperbarui']
    Q --> R[Redirect ke halaman profil]
    R --> Z
```

## Catatan Penting

> **Kasir TIDAK BISA mengubah username maupun email.**
> Fitur tersebut hanya tersedia bagi Admin via menu User Management.
