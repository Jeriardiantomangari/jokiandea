<?php 
// Ambil nama file PHP saat ini untuk menandai menu aktif
$halaman = basename($_SERVER['PHP_SELF']); 
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dasbor Admin Laboratorium</title>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
  /* === Reset & Font Global === */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
  display: flex;
  min-height: 100vh;
  background: #f3f3f3;
  overflow-x: hidden;
}

/* === MENU SAMPING (gaya dari snippet 1) === */
.menu-samping {
  width: 250px;
  background: #00AEEF;           /* biru seperti snippet 1 */
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  padding: 30px 0;
  color: #fff;
  position: fixed;
  top: 0; bottom: 0; left: 0;
  z-index: 200;
  transition: transform 0.3s ease;
  box-shadow: 0 2px 8px rgba(0,0,0,.25);
}
.menu-samping.hidden { transform: translateX(-100%); }

/* Foto/logo */
.bagian-foto {
  width: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center; align-items: center;
  margin-bottom: 30px;
  cursor: pointer;
  position: relative; text-align: center;
}
.bagian-foto img {
  width: 100px; height: 100px;
  border-radius: 50%;
  object-fit: cover;
  display: block; margin: 0 auto;
  transition: .3s;
  box-shadow: 0 4px 10px rgba(0,0,0,.25);
}

/* Daftar menu */
.daftar-menu {
  display: flex; flex-direction: column;
  width: 100%;
  padding-left: 20px;
}
.daftar-menu a,
.menu-item {
  display: flex; align-items: center; justify-content: flex-start;
  text-decoration: none;
  color: black;                    /* seperti snippet 1 */
  font-weight: bold;
  font-size: 17px;                 /* seperti snippet 1 */
  padding: 10px 0;
  transition: all .3s ease;
  cursor: pointer;
}
.daftar-menu a i,
.menu-item i:first-child { margin-right: 10px; width: 20px; text-align: center; }

.daftar-menu a:hover,
.daftar-menu a.active,
.menu-item:hover,
.menu-item.active span {
  color: #fff;                     /* hover/active putih seperti snippet 1 */
  font-weight: bold;
}

/* Submenu (tetap ada, hanya warna disesuaikan agar kontras) */
.submenu { display: none; flex-direction: column; margin-left: 30px; }
.submenu a {
  color: black;                  /* lebih kontras di biru */
  font-weight: bold; font-size: 14px;
  padding: 8px 0; text-decoration: none; transition: .3s;
}
.submenu a:hover, .submenu a.active { color: #ffffff; }
.submenu.active { display: flex; }
.menu-item .arrow { margin-left: auto; margin-right: 20px; transition: transform .3s; }
.menu-item.active .arrow { transform: rotate(90deg); }

/* === MENU ATAS (gaya dari snippet 1) === */
.menu-atas {
      position: fixed;
      top: 0;
      left: 250px;
      right: 0;
      height: 60px;
      background: #ffffff;
      border-bottom: none;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
      display: flex;
      justify-content: flex-end;
      align-items: center;
      padding: 0 10px;
      z-index: 250;
      transition: left 0.3s ease;
    }

    .tombol-keluar {
      background: none;
      color: #ff5252;
      border: none;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      transition: color 0.3s ease, transform 0.2s ease;
    }

/* Tombol menu (ikon hamburger) */
.tombol-menu {
  display: none !important;
  font-size: 1.6rem;
  color: #333;
  cursor: pointer;
}

/* === RESPONSIVE seperti snippet 1 === */
@media (max-width: 768px) {
  .menu-samping { transform: translateX(-100%); width: 250px; }
  .bagian-foto { margin-top: 80px; margin-bottom: 20px; }
  .menu-samping.active { transform: translateX(0); }

  .menu-atas { left: 0; justify-content: space-between; }
  .tombol-menu { display: block !important; }
}


  </style>
</head>
<body>

  <!-- MENU SAMPING -->
  <div class="menu-samping" id="menuSamping">
   <div class="bagian-foto">
      <img src="../../gambar/ustj.jpg" alt="ustj" class="gambar" />
    </div>

    <div class="daftar-menu">
      <a href="../halaman_utama/halaman_utama.php" class="<?= $halaman == 'halaman_utama.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-house"></i> Halaman Utama
      </a>

      <!-- MENU DATA -->
      <div class="menu-item" id="dataMenu">
        <span><i class="fa-solid fa-database"></i>Manajemen Data</span>
        <i class="fa-solid fa-angle-right arrow"></i>
      </div>
      <div class="submenu" id="submenuData">
        <a href="../dosen/dosen.php" class="<?= $halaman == 'dosen.php' ? 'active' : '' ?>">Data Dosen</a>
        <a href="../mahasiswa/mahasiswa.php" class="<?= $halaman == 'mahasiswa.php' ? 'active' : '' ?>">Data Mahasiswa</a>
        <a href="../ruangan/ruangan.php" class="<?= $halaman == 'ruangan.php' ? 'active' : '' ?>">Data Ruangan</a>
        <a href="../mk_praktikum/mk_praktikum.php" class="<?= $halaman == 'mk_praktikum.php' ? 'active' : '' ?>">Data MK Praktikum</a>
         <a href="../semester/semester.php" class="<?= $halaman == 'semester.php' ? 'active' : '' ?>">Data Semester</a>
      </div>

      <!-- MENU LAIN DI LUAR SUBMENU -->
      <a href="../jadwal/jadwal.php" class="<?= $halaman == 'jadwal.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-flask"></i> Jadwal Praktikum
      </a>

      <a href="../validasi/validasi.php" class="<?= $halaman == 'validasi.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-circle-check"></i> Validasi Pendaftaran
      </a>

      <a href="../absensi/absensi.php" class="<?= $halaman == 'absensi.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-clipboard-check"></i> Data Absensi
      </a>

      <a href="../laporan/laporan.php" class="<?= $halaman == 'laporan.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-file-circle-check"></i> Data Praktikum
      </a>
        <a href="../pengaturan/pengaturan.php" class="<?= $halaman == 'pengaturan.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-gear"></i> Pengaturan Akun
      </a>
    </div>
  </div>

 <!-- MENU ATAS -->
  <div class="menu-atas">
    <i class="fa-solid fa-bars tombol-menu" id="tombolMenu"></i>
    <button class="tombol-keluar" id="keluar">
      <i class="fa-solid fa-power-off"></i>
    </button>
  </div>

 <script>
  const tombolMenu = document.getElementById("tombolMenu");
  const menuSamping = document.getElementById("menuSamping");
  const dataMenu = document.getElementById("dataMenu");
  const submenuData = document.getElementById("submenuData");

  tombolMenu.addEventListener("click", () => {
    menuSamping.classList.toggle("active");
  });

  dataMenu.addEventListener("click", () => {
    submenuData.classList.toggle("active");
    dataMenu.classList.toggle("active");
  });

  document.getElementById("keluar").addEventListener("click", () => {
    const yakin = confirm("Apakah Anda yakin ingin keluar ?");
    if (yakin) {
      window.location.href = "../../login.html";
    }
  });
</script>


</body>
</html>
