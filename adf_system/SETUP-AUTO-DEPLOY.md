# 🚀 Setup Auto-Deploy ke Niagahoster

## Status: ✅ GitHub Actions Siap Digunakan

Repository: `https://github.com/icemulre/adf_sytem`

---

## 📋 Langkah Setup (5 Menit)

### 1️⃣ Dapatkan FTP Credentials dari cPanel Niagahoster

Login ke cPanel Niagahoster, catat:
- **FTP Host**: biasanya `ftp.domainmu.com` atau IP server
- **FTP Username**: user FTP kamu (biasanya ada di email hosting)
- **FTP Password**: password FTP
- **FTP Port**: `21` (default)
- **FTP Target**: `/public_html/adf_system` (atau path folder tujuan)

---

### 2️⃣ Tambahkan Secrets di GitHub

1. Buka: https://github.com/icemulre/adf_sytem/settings/secrets/actions
2. Klik **"New repository secret"**
3. Tambahkan **5 secrets** ini:

| Secret Name | Value | Contoh |
|-------------|-------|--------|
| `FTP_HOST` | FTP server host | `ftp.narayanahotel.com` |
| `FTP_USERNAME` | FTP username | `narayanahotel_ftp` |
| `FTP_PASSWORD` | FTP password | `password123` |
| `FTP_PORT` | FTP port | `21` |
| `FTP_TARGET` | Path folder di server | `/public_html/adf_system/` |

**Catatan:** 
- `FTP_TARGET` harus diakhiri dengan `/` (slash)
- Jika FTP root sudah `public_html/adf_system`, maka isi dengan `/` saja

---

### 3️⃣ Test Auto-Deploy

Setelah secrets ditambahkan:

1. **Buat perubahan kecil** di lokal (misal edit README):
   ```bash
   echo "Test deploy" >> README.md
   git add .
   git commit -m "Test: Auto-deploy via GitHub Actions"
   git push origin main
   ```

2. **Cek GitHub Actions**:
   - Buka: https://github.com/icemulre/adf_sytem/actions
   - Akan muncul workflow "🚀 Deploy to Niagahoster"
   - Tunggu hingga ✅ hijau (berhasil) atau ❌ merah (gagal)

3. **Verifikasi di Hosting**:
   - Buka: `https://domainmu.com/adf_system`
   - Cek file README.md ada perubahan

---

### 4️⃣ Manual Deploy (Tanpa Push)

Jika ingin deploy manual tanpa push code:

1. Buka: https://github.com/icemulre/adf_sytem/actions
2. Pilih workflow **"🚀 Deploy to Niagahoster"**
3. Klik **"Run workflow"** → **Run workflow**

---

## 🔍 Troubleshooting

### ❌ Error: "FTP connection failed"
**Solusi:**
- Cek FTP credentials benar (host, username, password)
- Pastikan port `21` atau coba port `22` (FTP over SSH)
- Whitelist IP GitHub Actions di firewall hosting (jika ada)

### ❌ Error: "Permission denied"
**Solusi:**
- Pastikan FTP user punya write permission ke folder target
- Cek `FTP_TARGET` path benar (harus absolut dari FTP root)

### ❌ Error: "Secrets not found"
**Solusi:**
- Pastikan semua 5 secrets sudah ditambahkan di GitHub
- Nama secret **harus exact match** (case-sensitive)
- Re-run workflow setelah menambahkan secrets

---

## 📁 File Yang Di-Exclude dari Deploy

Untuk efisiensi, file berikut **tidak akan** diupload ke hosting:
- `.git/` (folder git)
- `node_modules/` (dependencies)
- `vendor/` (composer)
- `.env` (environment config)
- `test-*.php` (test files)
- `check-*.php` (debug files)
- `debug-*.php` (debug scripts)
- `*.md` (markdown files)
- `deploy-hosting.sh` (deployment script)

---

## ✅ Workflow Berhasil!

Setelah setup selesai:
- Setiap `git push origin main` → otomatis upload ke Niagahoster
- Deployment memakan waktu 1-3 menit
- Tidak perlu SSH/terminal lagi
- Semua otomatis via GitHub Actions

---

## 🎯 Next Steps

1. ✅ Setup secrets di GitHub
2. ✅ Test deploy pertama kali
3. ✅ Verifikasi file terupload ke hosting
4. ✅ Setup database di hosting (gunakan `DEPLOYMENT-CHECKLIST.md`)
5. ✅ Test login Sandra di hosting

---

**Need help?** Check GitHub Actions logs untuk detail error.
