# Flowchart Aplikasi SIMONA

**SIMONA — Sistem Monitoring dan Nilai PKL**
SMK Negeri 6 Surakarta

Dokumen ini berisi flowchart alur kerja seluruh modul aplikasi SIMONA dalam format Mermaid.

---

## 1. Alur Utama Aplikasi

```mermaid
flowchart TD
    A([Pengguna Buka Browser]) --> B[index.php]
    B --> C{Session aktif?}
    C -- Ya, role=admin --> D[/admin/dashboard.php/]
    C -- Ya, role=siswa --> E[/siswa/dashboard.php/]
    C -- Tidak --> F[/auth/login.php/]

    F --> G{Login berhasil?}
    G -- Tidak --> F
    G -- Ya, admin --> D
    G -- Ya, siswa --> E

    D --> H[Kelola Data Siswa]
    D --> I[Verifikasi Jurnal]
    D --> J[Input Nilai]
    D --> K[Kelola Akun Guru]
    D --> L[Lihat Laporan]

    E --> M[Tambah Jurnal]
    E --> N[Edit Jurnal]
    E --> O[Lihat Nilai]

    H & I & J & K & L & M & N & O --> P([Logout])
    P --> F
```

---

## 2. Alur Login Detail

```mermaid
flowchart TD
    A([Buka /auth/login.php]) --> B{Sudah login?}
    B -- Ya, admin --> C[Redirect /admin/dashboard.php]
    B -- Ya, siswa --> D[Redirect /siswa/dashboard.php]
    B -- Tidak --> E[Tampilkan Form Login]

    E --> F[User Submit Form POST]
    F --> G[csrf_verify — validasi token]
    G --> H{Token valid?}
    H -- Tidak --> I[HTTP 403, Hentikan]
    H -- Ya --> J{Username & password diisi?}
    J -- Tidak --> K[Tampilkan error: wajib diisi]
    K --> E
    J -- Ya --> L["SELECT users WHERE username = ?"]
    L --> M{User ditemukan?}
    M -- Tidak --> N[Tampilkan error: username/password salah]
    N --> E
    M -- Ya --> O[password_verify terhadap hash di DB]
    O --> P{Password cocok?}
    P -- Tidak --> N
    P -- Ya --> Q[session_regenerate_id true]
    Q --> R["Simpan ke session: user_id, nama, username, role"]
    R --> S{role = siswa?}
    S -- Ya --> T["SELECT siswa WHERE user_id = ? → simpan siswa_id ke session"]
    T --> U[Redirect /siswa/dashboard.php]
    S -- Tidak, admin --> V[Redirect /admin/dashboard.php]
```

---

## 3. Alur Logout

```mermaid
flowchart TD
    A([User klik Logout]) --> B[/auth/logout.php/]
    B --> C["Kosongkan array: SESSION = []"]
    C --> D{session.use_cookies aktif?}
    D -- Ya --> E[setcookie — hapus cookie session dengan waktu lampau]
    D -- Tidak --> F[Lewati langkah cookie]
    E --> G[session_destroy]
    F --> G
    G --> H[Redirect /auth/login.php]
    H --> I([Selesai])
```

---

## 4. Alur Dashboard Admin

```mermaid
flowchart TD
    A([Buka /admin/dashboard.php]) --> B[require_role admin]
    B --> C{Login & role=admin?}
    C -- Tidak --> D{Sudah login?}
    D -- Tidak --> E[Redirect /auth/login.php]
    D -- Ya, role=siswa --> F[Redirect /siswa/dashboard.php]
    C -- Ya --> G[get_db — ambil koneksi PDO]

    G --> H[Query: COUNT siswa]
    G --> I["Query: COUNT jurnal WHERE status=menunggu"]
    G --> J[Query: COUNT jurnal_harian]
    G --> K[Query: COUNT DISTINCT siswa_id FROM nilai]
    G --> L["Query: 5 jurnal menunggu terbaru JOIN siswa, users"]
    G --> M["Query: jurnal per minggu 8 minggu terakhir GROUP BY YEARWEEK"]
    G --> N["Query: COUNT status diverifikasi, menunggu, ditolak FROM jurnal_harian"]

    H & I & J & K & L & M & N --> O[Encode data ke JSON untuk Chart.js]
    O --> P[Render header.php]
    P --> Q[Tampilkan 4 kartu statistik]
    Q --> R[Render bar chart stacked jurnal per minggu]
    R --> S[Render donut chart status verifikasi]
    S --> T[Tampilkan tabel 5 jurnal pending terbaru]
    T --> U[Render footer.php]
```

---

## 5. Alur Kelola Data Siswa

```mermaid
flowchart TD
    A([Buka /admin/siswa/index.php]) --> B[require_role admin]
    B --> C{Aksi POST hapus_id?}
    C -- Ya --> D[csrf_verify]
    D --> E["DELETE FROM users WHERE id = SELECT user_id FROM siswa"]
    E --> F["set_flash success, redirect index"]

    C -- Tidak --> G[Baca GET: cari, kelas, page]
    G --> H["SELECT COUNT siswa JOIN users WHERE filter"]
    H --> I[Hitung total_pages]
    I --> J["SELECT siswa JOIN users JOIN guru LIMIT per_page OFFSET"]
    J --> K["SELECT DISTINCT kelas untuk dropdown filter"]
    K --> L[Render tabel daftar siswa]
    L --> M{Klik Tambah Siswa?}
    M -- Ya --> N[Buka /admin/siswa/tambah.php]
    L --> O{Klik Edit?}
    O -- Ya --> P[Buka /admin/siswa/edit.php?id=X]
    L --> Q{Klik Hapus & Konfirmasi?}
    Q -- Ya --> C
```

---

## 6. Alur Tambah Jurnal Siswa

```mermaid
flowchart TD
    A([Buka /siswa/jurnal/tambah.php]) --> B[require_role siswa]
    B --> C[Ambil siswa_id dari session]
    C --> D{Method POST?}
    D -- Tidak --> E[Tampilkan form kosong, tanggal default hari ini]
    D -- Ya --> F[csrf_verify]
    F --> G[Baca POST: tanggal, kegiatan, kendala]
    G --> H{Tanggal kosong?}
    H -- Ya --> I[Tambah error: tanggal wajib diisi]
    H -- Tidak --> J{Kegiatan kosong?}
    J -- Ya --> K[Tambah error: kegiatan wajib diisi]
    J -- Tidak --> L["SELECT jurnal WHERE siswa_id = ? AND tanggal = ?"]
    L --> M{Jurnal tanggal ini sudah ada?}
    M -- Ya --> N[Tambah error: duplikat tanggal]
    M -- Tidak --> O{Errors kosong?}
    I & K & N --> O
    O -- Ada error --> P[Tampilkan form + pesan error]
    O -- Tidak ada --> Q["INSERT INTO jurnal_harian: siswa_id, tanggal, kegiatan, kendala"]
    Q --> R["set_flash success"]
    R --> S[Redirect /siswa/jurnal/index.php]
```

---

## 7. Alur Edit Jurnal Siswa

```mermaid
flowchart TD
    A([Buka /siswa/jurnal/edit.php?id=X]) --> B[require_role siswa]
    B --> C[Ambil siswa_id dari session, id dari GET]
    C --> D["SELECT jurnal WHERE id = ? AND siswa_id = ?"]
    D --> E{Jurnal ditemukan?}
    E -- Tidak --> F["set_flash danger, redirect index"]
    E -- Ya --> G{status_verifikasi = diverifikasi?}
    G -- Ya --> H["set_flash danger: tidak bisa edit, redirect index"]
    G -- Tidak --> I{Method POST?}
    I -- Tidak --> J[Isi form dengan data jurnal yang ada]
    I -- Ya --> K[csrf_verify]
    K --> L[Baca POST: tanggal, kegiatan, kendala]
    L --> M{Validasi: tanggal & kegiatan wajib?}
    M -- Gagal --> N[Tampilkan error]
    N --> J
    M -- Lulus --> O["SELECT jurnal WHERE siswa_id = ? AND tanggal = ? AND id != ?"]
    O --> P{Duplikat tanggal?}
    P -- Ya --> Q[Tambah error duplikat]
    Q --> N
    P -- Tidak --> R["UPDATE jurnal_harian: tanggal, kegiatan, kendala, status_verifikasi = menunggu, catatan_admin = NULL"]
    R --> S["set_flash success: jurnal diperbarui dan dikirim ulang"]
    S --> T[Redirect /siswa/jurnal/index.php]
```

---

## 8. Alur Verifikasi Jurnal Admin

```mermaid
flowchart TD
    A([Buka /admin/jurnal/index.php]) --> B[require_role admin]
    B --> C{Method POST?}
    C -- Ya --> D[csrf_verify]
    D --> E[Baca POST: jurnal_id, aksi, catatan_admin]
    E --> F{jurnal_id valid AND aksi in diverifikasi/ditolak?}
    F -- Ya --> G["UPDATE jurnal_harian SET status_verifikasi = aksi, catatan_admin = catatan WHERE id = ?"]
    G --> H["set_flash success"]
    H --> I[Redirect dengan query string yang sama]

    C -- Tidak --> J[Baca GET: cari, status, tgl_dari, tgl_sampai, page]
    J --> K[Sanitasi dan validasi status]
    K --> L["SELECT COUNT jurnal JOIN siswa JOIN users WHERE filter"]
    L --> M[Hitung total_pages]
    M --> N["SELECT jurnal JOIN siswa JOIN users ORDER BY tanggal DESC LIMIT OFFSET"]
    N --> O["SELECT COUNT jurnal WHERE status = menunggu"]
    O --> P[Render filter form]
    P --> Q[Tampilkan tabel jurnal dengan baris detail tersembunyi]
    Q --> R{Klik icon mata?}
    R -- Ya --> S[toggleDetail — tampilkan baris detail inline]
    S --> T{Admin klik Verifikasi atau Tolak?}
    T -- Ya --> C
```

---

## 9. Alur Input Nilai Admin

```mermaid
flowchart TD
    A([Buka /admin/nilai/index.php]) --> B[require_role admin]
    B --> C[Baca GET: cari, kelas, page]
    C --> D["SELECT COUNT siswa JOIN users WHERE filter"]
    D --> E["SELECT siswa JOIN users LIMIT OFFSET"]
    E --> F["SELECT nilai WHERE siswa_id IN ids semua siswa di halaman ini"]
    F --> G[Bangun nilai_map: siswa_id → aspek → nilai]
    G --> H[Render tabel dengan nilai per aspek dan rata-rata]
    H --> I{Klik tombol Input?}
    I -- Ya --> J[Buka /admin/nilai/form.php?siswa_id=X]

    J --> K["SELECT siswa WHERE id = ?"]
    K --> L{Siswa ditemukan?}
    L -- Tidak --> M["set_flash danger, redirect index"]
    L -- Ya --> N["SELECT nilai WHERE siswa_id = ? — ambil nilai existing"]
    N --> O{Method POST?}
    O -- Tidak --> P[Render form slider + input angka untuk 4 aspek]
    O -- Ya --> Q[csrf_verify]
    Q --> R[Validasi semua aspek: wajib diisi, rentang 0-100]
    R --> S{Errors?}
    S -- Ya --> T[Tampilkan pesan error]
    T --> P
    S -- Tidak --> U[db beginTransaction]
    U --> V{Nilai aspek sudah ada di DB?}
    V -- Ya --> W["UPDATE nilai SET nilai, tanggal_input, diberikan_oleh WHERE siswa_id AND aspek"]
    V -- Tidak --> X["INSERT INTO nilai: siswa_id, aspek, nilai, tanggal_input, diberikan_oleh"]
    W & X --> Y{Semua aspek diproses?}
    Y -- Lanjut --> V
    Y -- Selesai --> Z[db commit]
    Z --> AA["set_flash success, redirect /admin/nilai/index.php"]
```

---

## 10. Alur Kelola Akun Guru

```mermaid
flowchart TD
    A([Buka /admin/akun/index.php]) --> B[require_role admin]
    B --> C[Ambil current_user dari session]
    C --> D{Method POST hapus_id?}
    D -- Ya --> E[csrf_verify]
    E --> F{hapus_id = user sendiri?}
    F -- Ya --> G["set_flash danger: tidak bisa hapus diri sendiri"]
    G --> H[Redirect index]
    F -- Tidak --> I["SELECT COUNT siswa WHERE guru_pembimbing_id = hapus_id"]
    I --> J{Masih membimbing siswa?}
    J -- Ya --> K["set_flash danger: masih membimbing siswa"]
    K --> H
    J -- Tidak --> L["DELETE FROM users WHERE id = hapus_id AND role = admin"]
    L --> M["set_flash success"]
    M --> H

    D -- Tidak --> N["SELECT users JOIN COUNT siswa WHERE role = admin GROUP BY id"]
    N --> O[Render tabel daftar akun guru]
    O --> P{Klik Tambah Akun Guru?}
    P -- Ya --> Q[Buka /admin/akun/tambah.php]
    O --> R{Klik Edit?}
    R -- Ya --> S[Buka /admin/akun/edit.php?id=X]
    O --> T{Klik Hapus & Konfirmasi?}
    T -- Ya --> D
```

---

## 11. Alur Edit Akun Guru

```mermaid
flowchart TD
    A([Buka /admin/akun/edit.php?id=X]) --> B[require_role admin]
    B --> C["SELECT users WHERE id = ? AND role = admin"]
    C --> D{Akun ditemukan?}
    D -- Tidak --> E["set_flash danger, redirect /admin/akun/index.php"]
    D -- Ya --> F{Method POST?}
    F -- Tidak --> G[Isi form dengan data akun yang ada]
    F -- Ya --> H[csrf_verify]
    H --> I[Baca POST: nama, password, konfirmasi]
    I --> J{Nama kosong?}
    J -- Ya --> K[Tambah error: nama wajib diisi]
    J -- Tidak --> L{Password baru diisi?}
    L -- Ya --> M{Panjang password < 6?}
    M -- Ya --> N[Tambah error: minimal 6 karakter]
    M -- Tidak --> O{password != konfirmasi?}
    O -- Ya --> P[Tambah error: konfirmasi tidak cocok]
    O -- Tidak --> Q{Errors kosong?}
    K & N & P --> Q
    L -- Tidak --> Q
    Q -- Ada error --> G
    Q -- Tidak ada --> R{Password baru diisi?}
    R -- Ya --> S["UPDATE users SET nama, password_hash WHERE id"]
    R -- Tidak --> T["UPDATE users SET nama WHERE id"]
    S & T --> U["set_flash success, redirect /admin/akun/index.php"]
```

---

## 12. Alur Proteksi Halaman require_role

```mermaid
flowchart TD
    A([Halaman protected dipanggil]) --> B["require_role role_yang_diminta"]
    B --> C["require_login — cek isset SESSION user_id AND role"]
    C --> D{Session user_id & role ada?}
    D -- Tidak --> E["Redirect /auth/login.php"]
    D -- Ya --> F{current_role == role_yang_diminta?}
    F -- Ya --> G[Lanjut eksekusi halaman]
    F -- Tidak --> H{current_role == admin?}
    H -- Ya --> I["Redirect /admin/dashboard.php"]
    H -- Tidak, siswa --> J["Redirect /siswa/dashboard.php"]
```
