# Layar Masjid

Tampilan layar informasi masjid berbasis HTML, CSS, dan JavaScript tanpa framework. Project ini menampilkan jam digital, jadwal salat, logo dan identitas masjid, slideshow konten informasi, serta running text di bagian bawah.

## Fitur

- Jam dan tanggal realtime dengan format bahasa Indonesia.
- Highlight otomatis untuk waktu salat berikutnya.
- Slideshow gambar informasi pada area konten utama.
- Area branding untuk logo, nama masjid, dan alamat.
- Running text untuk pengumuman singkat.
- Struktur frontend sederhana dan mudah diubah.

## Struktur Project

```text
layarmasjid/
├── index.html
├── assets/
│   ├── css/
│   │   ├── style.css
│   │   ├── tokens.css
│   │   ├── base.css
│   │   ├── layout.css
│   │   └── sidebar.css
│   ├── js/
│   │   └── app.js
│   └── img/
│       ├── contoh_logo/
│       ├── contoh_slide/
│       └── aset dekoratif lainnya
└── README.md
```

## Menjalankan Project

Karena file asset menggunakan path `/layarmasjid/...`, project ini paling aman dijalankan dari document root XAMPP:

1. Simpan project di `htdocs/layarmasjid`
2. Jalankan Apache dari XAMPP
3. Buka:

```text
http://localhost/layarmasjid/
```

## Bagian Yang Paling Sering Diubah

### 1. Jadwal salat

Edit array `prayers` di [assets/js/app.js](/Applications/XAMPP/xamppfiles/htdocs/layarmasjid/assets/js/app.js:1)

```js
const prayers = [
  { name: "Subuh", time: "04:36" },
  { name: "Syuruq", time: "05:52" },
  { name: "Dzuhur", time: "11:54" },
  { name: "Ashar", time: "15:14" },
  { name: "Maghrib", time: "17:48" },
  { name: "Isya", time: "18:59" }
];
```

### 2. Slide konten

Edit array `slides` di [assets/js/app.js](/Applications/XAMPP/xamppfiles/htdocs/layarmasjid/assets/js/app.js:10)

Tambahkan atau ganti path gambar sesuai kebutuhan:

```js
const slides = [
  { src: "/layarmasjid/assets/img/contoh_slide/info-a.png", alt: "Slide informasi 1" }
];
```

### 3. Logo, nama masjid, dan alamat

Edit bagian header di [index.html](/Applications/XAMPP/xamppfiles/htdocs/layarmasjid/index.html:64)

- Ganti `src` logo
- Ganti nama masjid pada `<h1>`
- Ganti alamat pada `<p>`

### 4. Running text

Edit isi `.content-ticker-text` di [index.html](/Applications/XAMPP/xamppfiles/htdocs/layarmasjid/index.html:88)

## Catatan

- Saat ini data jadwal dan slide masih statis.
- Jika nanti ingin dibuat dinamis, project ini bisa dikembangkan ke sumber data JSON, API, atau panel admin.
- Path asset saat ini memakai prefix `/layarmasjid/`, jadi jika nama folder project berubah, referensi path di HTML/CSS/JS juga perlu disesuaikan.

## Rencana Pengembangan

- Integrasi jadwal salat dari API atau file konfigurasi.
- Pengaturan slideshow dari dashboard admin.
- Running text dinamis.
- Mode fullscreen untuk TV atau display masjid.
