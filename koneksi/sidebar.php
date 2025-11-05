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
      background-color: #f3f3f3;
      overflow-x: hidden;
    }

    /* === MENU SAMPING === */
    .menu-samping {
      width: 250px;
      background-color: #3a3b3c;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      padding: 30px 0;
      color: #fff;
      position: fixed;
      top: 0;
      bottom: 0;
      left: 0;
      z-index: 200;
      border-right: 1px solid #222;
      transition: transform 0.3s ease;
    }

    .menu-samping.hidden {
      transform: translateX(-100%);
    }

    /* Foto profil */
    .bagian-foto {
      width: 100%;
      display: flex;
      justify-content: center;
      margin-bottom: 30px;
      cursor: pointer;
    }

    .bagian-foto img {
      width: 90px;
      height: 90px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #00b4ff;
      transition: 0.3s;
    }

    .bagian-foto img:hover {
      transform: scale(1.05);
      border-color: #8bc9ff;
    }

    /* Daftar menu */
    .daftar-menu {
      display: flex;
      flex-direction: column;
      width: 100%;
      padding-left: 20px;
    }

    .daftar-menu a,
    .menu-item {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      text-decoration: none;
      color: #fff;
      font-weight: bold;
      font-size: 15px;
      padding: 10px 0;
      transition: all 0.3s ease;
      cursor: pointer;
    }

    .daftar-menu a i,
    .menu-item i:first-child {
      margin-right: 10px;
      width: 20px;
      text-align: center;
    }

    .daftar-menu a:hover,
    .menu-item:hover {
      color: #00b4ff;
    }

    .daftar-menu a.active,
    .menu-item.active span {
      color: #00b4ff;
      font-weight: bold;
    }

    /* Submenu */
    .submenu {
      display: none;
      flex-direction: column;
      margin-left: 30px;
    }

    .submenu a {
      color: #dcdcdc;
      font-weight: bold;
      font-size: 14px;
      padding: 8px 0;
      text-decoration: none;
      transition: 0.3s;
    }

    .submenu a:hover,
    .submenu a.active {
      color: #00b4ff;
    }

    .submenu.active {
      display: flex;
    }

    .menu-item .arrow {
      margin-left: auto;
      margin-right: 20px;
      transition: transform 0.3s;
    }

    .menu-item.active .arrow {
      transform: rotate(90deg);
    }

    /* === MENU ATAS === */
    .menu-atas {
      position: fixed;
      top: 0;
      left: 250px;
      right: 0;
      height: 60px;
      background: #8bc9ff;
      border-bottom: 1px solid #c0c3c6ff;
      display: flex;
      justify-content: flex-end;
      align-items: center;
      padding: 0 10px;
      z-index: 250;
      transition: left 0.3s ease;
    }

    /* Tombol keluar */
    .tombol-keluar {
      background-color: #ff5252;
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: bold;
      transition: 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .tombol-keluar:hover {
      background-color: #d83c3c;
    }

    .tombol-menu {
      display: none !important;
      font-size: 1.6rem;
      color: #333;
      cursor: pointer;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
      .menu-samping {
        transform: translateX(-100%);
      }
      .bagian-foto {
        margin-top: 50px;
        margin-bottom: 20px;
      }
      .menu-samping.active {
        transform: translateX(0);
      }
      .menu-atas {
        left: 0;
        justify-content: space-between;
      }
      .tombol-menu {
        display: block !important;
      }
    }

  </style>
</head>
<body>

  <!-- MENU SAMPING -->
  <div class="menu-samping" id="menuSamping">
    <div class="bagian-foto" id="tombolProfil">
      <img src="logo.png" alt="Foto Profil">
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
    </div>
  </div>

  <!-- MENU ATAS -->
  <div class="menu-atas">
    <i class="fa-solid fa-bars tombol-menu" id="tombolMenu"></i>
    <button class="tombol-keluar" id="keluar">
      <i class="fa-solid fa-right-from-bracket"></i> Keluar
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
      window.location.href = "../index.php";
    });
  </script>

</body>
</html>
