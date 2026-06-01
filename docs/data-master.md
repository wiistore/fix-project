# Data Master — Barang, Kategori, User (Kasir), Supplier

---

## 1. Use Case Diagram

```mermaid
%%{ init: { 'flowchart': { 'curve': 'linear' } } }%%
flowchart LR
    Admin((Admin))
    Kasir((Kasir))

    subgraph SISTEM["Sistem POS Kopsis — Data Master"]

        subgraph BARANG["Manajemen Barang"]
            UC_BRG_LIST[Lihat Daftar Barang]
            UC_BRG_ADD[Tambah Barang]
            UC_BRG_EDIT[Edit Barang]
            UC_BRG_DEL[Hapus/Nonaktifkan Barang]
            UC_BRG_TOGGLE[Toggle Status Barang]
            UC_BRG_BARCODE[/Generate Barcode/]
            UC_BRG_LABEL[/Cetak Label/]
            UC_BRG_VALIDASI[/Validasi Kode & Barcode Unik/]
        end

        subgraph KATEGORI["Manajemen Kategori"]
            UC_KAT_LIST[Lihat Daftar Kategori]
            UC_KAT_ADD[Tambah Kategori]
            UC_KAT_EDIT[Edit Kategori]
            UC_KAT_DEL[Hapus Kategori]
            UC_KAT_CEK[/Cek Relasi Barang/]
        end

        subgraph SUPPLIER["Manajemen Supplier"]
            UC_SUP_LIST[Lihat Daftar Supplier]
            UC_SUP_ADD[Tambah Supplier]
            UC_SUP_EDIT[Edit Supplier]
            UC_SUP_DEL[Hapus/Nonaktifkan Supplier]
            UC_SUP_TOGGLE[Toggle Status Supplier]
        end

        subgraph USERMGMT["Manajemen User Kasir"]
            UC_USR_LIST[Lihat Daftar User]
            UC_USR_ADD[Tambah Kasir]
            UC_USR_EDIT[Edit Kasir]
            UC_USR_RESET[Reset Password Kasir]
            UC_USR_DEL[Hapus/Nonaktifkan Kasir]
            UC_USR_TOGGLE[Toggle Status Kasir]
            UC_USR_VALIDASI[/Validasi Username & Email Unik/]
        end

        subgraph PROFIL["Profil Kasir"]
            UC_PROFIL_VIEW[Lihat Profil]
            UC_PROFIL_PW[Reset Password Sendiri]
            UC_PROFIL_VERIF[/Verifikasi Password Lama/]
        end
    end

    %% === INCLUDE ===
    UC_BRG_ADD -->|include| UC_BRG_VALIDASI
    UC_BRG_EDIT -->|include| UC_BRG_VALIDASI
    UC_KAT_DEL -->|include| UC_KAT_CEK
    UC_USR_ADD -->|include| UC_USR_VALIDASI
    UC_USR_EDIT -->|include| UC_USR_VALIDASI
    UC_PROFIL_PW -->|include| UC_PROFIL_VERIF

    %% === EXTEND ===
    UC_BRG_BARCODE -.->|extend| UC_BRG_ADD
    UC_BRG_LABEL -.->|extend| UC_BRG_LIST

    %% === ADMIN ===
    Admin --> UC_BRG_LIST
    Admin --> UC_BRG_ADD
    Admin --> UC_BRG_EDIT
    Admin --> UC_BRG_DEL
    Admin --> UC_BRG_TOGGLE
    Admin --> UC_KAT_LIST
    Admin --> UC_KAT_ADD
    Admin --> UC_KAT_EDIT
    Admin --> UC_KAT_DEL
    Admin --> UC_SUP_LIST
    Admin --> UC_SUP_ADD
    Admin --> UC_SUP_EDIT
    Admin --> UC_SUP_DEL
    Admin --> UC_SUP_TOGGLE
    Admin --> UC_USR_LIST
    Admin --> UC_USR_ADD
    Admin --> UC_USR_EDIT
    Admin --> UC_USR_RESET
    Admin --> UC_USR_DEL
    Admin --> UC_USR_TOGGLE

    %% === KASIR ===
    Kasir --> UC_PROFIL_VIEW
    Kasir --> UC_PROFIL_PW
```

---

## 2. Sequence Diagram — Tambah Barang

```mermaid
sequenceDiagram
    title Sequence: Admin Tambah Barang

    actor Admin
    participant Browser
    participant Router
    participant BarangCtrl as BarangController
    participant BrgModel as Barang Model
    participant KatModel as Kategori Model
    participant Session
    participant DB as Database

    Admin->>Browser: Buka /admin/barang/create
    Browser->>Router: GET /admin/barang/create
    Router->>BarangCtrl: create()
    BarangCtrl->>BarangCtrl: requireRole('admin')
    BarangCtrl->>KatModel: getAll()
    KatModel->>DB: SELECT kategori
    DB-->>KatModel: list kategori
    BarangCtrl-->>Browser: Render form tambah barang

    Admin->>Browser: Isi form & klik Simpan
    Browser->>Router: POST /admin/barang/store
    Router->>BarangCtrl: store()
    BarangCtrl->>BarangCtrl: requireRole('admin')
    BarangCtrl->>BarangCtrl: Validasi input

    alt Validasi gagal
        BarangCtrl->>Session: set('_errors', errors)
        BarangCtrl-->>Browser: Redirect kembali ke form
    else Validasi berhasil
        BarangCtrl->>BrgModel: kodeExists(kode_barang)
        BrgModel->>DB: SELECT
        DB-->>BrgModel: false (belum ada)

        BarangCtrl->>BrgModel: barcodeExists(barcode)
        BrgModel->>DB: SELECT
        DB-->>BrgModel: false

        BarangCtrl->>BrgModel: create(data)
        BrgModel->>DB: INSERT INTO barang
        DB-->>BrgModel: OK

        BarangCtrl->>Session: setFlash('success', 'Barang berhasil ditambahkan')
        BarangCtrl-->>Browser: Redirect /admin/barang
    end
```

---

## 3. Sequence Diagram — Tambah Kasir (User)

```mermaid
sequenceDiagram
    title Sequence: Admin Tambah Kasir

    actor Admin
    participant Browser
    participant Router
    participant UserCtrl as UserController
    participant UserModel as User Model
    participant Validator
    participant Security
    participant Session
    participant DB as Database

    Admin->>Browser: Buka /admin/user/create
    Browser->>Router: GET /admin/user/create
    Router->>UserCtrl: create()
    UserCtrl->>UserCtrl: requireRole('admin')
    UserCtrl-->>Browser: Render form tambah kasir

    Admin->>Browser: Isi username, email, password, konfirmasi
    Admin->>Browser: Klik Simpan
    Browser->>Router: POST /admin/user/store
    Router->>UserCtrl: store()
    UserCtrl->>UserCtrl: requireRole('admin')

    UserCtrl->>Validator: validate(data, rules)
    Validator-->>UserCtrl: errors[]

    UserCtrl->>UserModel: usernameExists(username)
    UserModel->>DB: SELECT
    DB-->>UserModel: true/false

    UserCtrl->>UserModel: emailExists(email)
    UserModel->>DB: SELECT
    DB-->>UserModel: true/false

    alt Ada error
        UserCtrl->>Session: set('_errors', errors)
        UserCtrl-->>Browser: Redirect kembali ke form
    else Semua valid
        UserCtrl->>UserModel: createKasir(data)
        UserModel->>Security: passwordHash(password)
        Security-->>UserModel: hashed
        UserModel->>DB: INSERT INTO users (role='kasir')
        DB-->>UserModel: user_id

        UserCtrl->>Session: setFlash('success', 'Kasir berhasil ditambahkan')
        UserCtrl-->>Browser: Redirect /admin/user
    end
```

---

## 4. Sequence Diagram — Kasir Reset Password (Profil)

```mermaid
sequenceDiagram
    title Sequence: Kasir Reset Password Sendiri

    actor Kasir
    participant Browser
    participant Router
    participant KasirCtrl as KasirController
    participant UserModel as User Model
    participant Validator
    participant Security
    participant Session
    participant DB as Database

    Kasir->>Browser: Buka /kasir/profil
    Browser->>Router: GET /kasir/profil
    Router->>KasirCtrl: profil()
    KasirCtrl->>KasirCtrl: requireRole('kasir')
    KasirCtrl->>UserModel: findById(userId)
    UserModel->>DB: SELECT (tanpa password)
    DB-->>UserModel: data user
    KasirCtrl-->>Browser: Render profil (username & email READ-ONLY)

    Note over Browser: Username & Email<br/>TIDAK BISA diedit kasir

    Kasir->>Browser: Isi password lama, baru, konfirmasi
    Kasir->>Browser: Klik Simpan Password
    Browser->>Router: POST /kasir/profil/password
    Router->>KasirCtrl: updatePassword()
    KasirCtrl->>KasirCtrl: requireRole('kasir')

    KasirCtrl->>Validator: validate(data, rules)
    Validator-->>KasirCtrl: errors[]

    KasirCtrl->>UserModel: findByIdWithPassword(userId)
    UserModel->>DB: SELECT termasuk hash
    DB-->>UserModel: data + hash

    KasirCtrl->>Security: passwordVerify(current, hash)
    Security-->>KasirCtrl: true/false

    alt Password lama salah atau validasi gagal
        KasirCtrl->>Session: set('_errors', errors)
        KasirCtrl-->>Browser: Redirect /kasir/profil
    else Semua valid
        KasirCtrl->>UserModel: updateOwnPassword(userId, new_password)
        UserModel->>Security: passwordHash(new_password)
        Security-->>UserModel: hashed
        UserModel->>DB: UPDATE password
        DB-->>UserModel: OK

        KasirCtrl->>Session: setFlash('success', 'Password berhasil diperbarui')
        KasirCtrl-->>Browser: Redirect /kasir/profil
    end
```

---

## 5. Activity Diagram — CRUD Barang

```mermaid
%%{ init: { 'flowchart': { 'curve': 'linear' } } }%%
flowchart TD
    A([Start]) --> B[Admin buka halaman Daftar Barang]
    B --> C{Aksi yang dipilih?}

    C -->|Tambah| D1[Buka form tambah]
    D1 --> D2[Isi kode, nama, kategori, satuan,<br/>harga jual, stok minimum, barcode]
    D2 --> D3[Klik Simpan]
    D3 --> D4{Validasi OK?}
    D4 -->|Tidak| D5[Tampilkan error]
    D5 --> D2
    D4 -->|Ya| D6{Kode/Barcode unik?}
    D6 -->|Tidak| D7[Tampilkan error duplikat]
    D7 --> D2
    D6 -->|Ya| D8[Simpan ke database]
    D8 --> D9[Redirect ke daftar barang + pesan sukses]
    D9 --> Z([Stop])

    C -->|Edit| E1[Buka form edit barang]
    E1 --> E2[Ubah data yang diperlukan]
    E2 --> E3[Klik Simpan]
    E3 --> E4{Validasi & kode unik?}
    E4 -->|Tidak| E5[Tampilkan error]
    E5 --> E2
    E4 -->|Ya| E6[Update ke database]
    E6 --> D9

    C -->|Hapus| F1{Barang punya histori?}
    F1 -->|Ya| F2[Nonaktifkan barang<br/>status = nonaktif]
    F1 -->|Tidak| F3[Hapus permanen dari database]
    F2 --> D9
    F3 --> D9

    C -->|Toggle Status| G1[Ubah status aktif/nonaktif]
    G1 --> D9
```

---

## 6. Activity Diagram — CRUD Kategori

```mermaid
%%{ init: { 'flowchart': { 'curve': 'linear' } } }%%
flowchart TD
    A([Start]) --> B[Admin buka halaman Daftar Kategori]
    B --> C{Aksi yang dipilih?}

    C -->|Tambah| D1[Buka form tambah]
    D1 --> D2[Isi nama dan deskripsi]
    D2 --> D3[Klik Simpan]
    D3 --> D4{Validasi OK?}
    D4 -->|Tidak| D5[Tampilkan error]
    D5 --> D2
    D4 -->|Ya| D6[Simpan ke database]
    D6 --> D9[Redirect ke daftar + pesan sukses]
    D9 --> Z([Stop])

    C -->|Edit| E1[Buka form edit]
    E1 --> E2[Ubah data]
    E2 --> E3[Klik Simpan]
    E3 --> E4{Validasi OK?}
    E4 -->|Tidak| E5[Tampilkan error]
    E5 --> E2
    E4 -->|Ya| E6[Update ke database]
    E6 --> D9

    C -->|Hapus| F1{Kategori masih punya barang?}
    F1 -->|Ya| F2[Tampilkan error:<br/>Tidak bisa dihapus,<br/>masih ada barang terkait]
    F2 --> Z
    F1 -->|Tidak| F3[Hapus dari database]
    F3 --> D9
```

---

## 7. Activity Diagram — CRUD Supplier

```mermaid
%%{ init: { 'flowchart': { 'curve': 'linear' } } }%%
flowchart TD
    A([Start]) --> B[Admin buka halaman Daftar Supplier]
    B --> C{Aksi yang dipilih?}

    C -->|Tambah| D1[Buka form tambah]
    D1 --> D2[Isi nama, kontak, no HP, alamat, keterangan]
    D2 --> D3[Klik Simpan]
    D3 --> D4{Validasi OK?}
    D4 -->|Tidak| D5[Tampilkan error]
    D5 --> D2
    D4 -->|Ya| D6[Simpan ke database]
    D6 --> D9[Redirect ke daftar + pesan sukses]
    D9 --> Z([Stop])

    C -->|Edit| E1[Buka form edit]
    E1 --> E2[Ubah data]
    E2 --> E3[Klik Simpan]
    E3 --> E4{Validasi OK?}
    E4 -->|Tidak| E5[Tampilkan error]
    E5 --> E2
    E4 -->|Ya| E6[Update ke database]
    E6 --> D9

    C -->|Hapus| F1{Supplier punya histori restock?}
    F1 -->|Ya| F2[Nonaktifkan supplier]
    F1 -->|Tidak| F3[Hapus permanen]
    F2 --> D9
    F3 --> D9

    C -->|Toggle Status| G1[Ubah status aktif/nonaktif]
    G1 --> D9
```

---

## 8. Activity Diagram — Kelola User Kasir

```mermaid
%%{ init: { 'flowchart': { 'curve': 'linear' } } }%%
flowchart TD
    A([Start]) --> B[Admin buka halaman Daftar User]
    B --> C{Aksi yang dipilih?}

    C -->|Tambah Kasir| D1[Buka form tambah]
    D1 --> D2[Isi username, email, password, konfirmasi]
    D2 --> D3[Klik Simpan]
    D3 --> D4{Validasi OK?}
    D4 -->|Tidak| D5[Tampilkan error]
    D5 --> D2
    D4 -->|Ya| D6{Username/Email unik?}
    D6 -->|Tidak| D7[Tampilkan error duplikat]
    D7 --> D2
    D6 -->|Ya| D8[Hash password & simpan<br/>role = kasir, is_protected = 0]
    D8 --> D9[Redirect ke daftar + pesan sukses]
    D9 --> Z([Stop])

    C -->|Edit Kasir| E1[Buka form edit]
    E1 --> E2[Ubah username, email, status]
    E2 --> E3[Klik Simpan]
    E3 --> E4{Validasi & unik?}
    E4 -->|Tidak| E5[Tampilkan error]
    E5 --> E2
    E4 -->|Ya| E6[Update ke database]
    E6 --> D9

    C -->|Reset Password| F1[Buka form reset password]
    F1 --> F2[Input password baru + konfirmasi]
    F2 --> F3[Klik Simpan]
    F3 --> F4{Validasi OK?}
    F4 -->|Tidak| F5[Tampilkan error]
    F5 --> F2
    F4 -->|Ya| F6[Hash & update password kasir]
    F6 --> D9

    C -->|Hapus| G1{Kasir punya transaksi?}
    G1 -->|Ya| G2[Nonaktifkan kasir<br/>status = nonaktif]
    G1 -->|Tidak| G3[Hapus permanen]
    G2 --> D9
    G3 --> D9

    C -->|Toggle Status| H1[Ubah status aktif/nonaktif]
    H1 --> D9
```

---

## 9. Activity Diagram — Kasir Reset Password (Profil)

```mermaid
%%{ init: { 'flowchart': { 'curve': 'linear' } } }%%
flowchart TD
    A([Start]) --> B[Kasir buka halaman Profil]
    B --> C[Sistem tampilkan data profil<br/>Username & Email: READ-ONLY]
    C --> D[Kasir isi form:<br/>- Password saat ini<br/>- Password baru min 8 char<br/>- Konfirmasi password baru]
    D --> E[Klik Simpan Password]
    E --> F{Field kosong?}

    F -->|Ya| G[Tampilkan error wajib diisi]
    G --> Z([Stop])

    F -->|Tidak| H{Password baru < 8 karakter?}
    H -->|Ya| I[Tampilkan error minimal 8 karakter]
    I --> Z

    H -->|Tidak| J{Konfirmasi cocok?}
    J -->|Tidak| K[Tampilkan error tidak cocok]
    K --> Z

    J -->|Ya| L[Verifikasi password saat ini]
    L --> M{Password lama benar?}
    M -->|Tidak| N[Tampilkan error password lama salah]
    N --> Z

    M -->|Ya| O[Hash password baru]
    O --> P[Update password di database]
    P --> Q[Tampilkan pesan sukses]
    Q --> R[Redirect ke profil]
    R --> Z
```

> **Catatan**: Kasir **TIDAK BISA** mengubah username maupun email. Hanya Admin yang bisa via menu User Management.
