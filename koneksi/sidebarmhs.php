<?php 
// Dapatkan nama file saat ini
$halaman = basename($_SERVER['PHP_SELF']); 
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Halaman Mahasiswa</title>
   <!-- Ikon FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
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

    .menu-samping {
      width: 250px;
      background: #00AEEF;
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
      transition: transform 0.3s ease;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
    }

    .menu-samping.hidden {
      transform: translateX(-100%);
    }

 
    .bagian-foto {
      width: 100%;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      margin-bottom: 30px;
      cursor: pointer;
      position: relative;
      text-align: center;
    }

    .bagian-foto img {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      object-fit: cover;
      transition: 0.3s;
      display: block;
      margin: 0 auto;

      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
    }

    .daftar-menu {
      display: flex;
      flex-direction: column;
      width: 100%;
      padding-left: 20px;
    }

    .daftar-menu a {
      display: flex;
      align-items: center;
      text-decoration: none;
      color: black;
      font-weight: bold;
      font-size: 17px;
      padding: 10px 0;
      transition: all 0.3s ease;
    }

    .daftar-menu a i {
      margin-right: 10px;
      width: 20px;
      text-align: center;
    }

    .daftar-menu a:hover,
    .daftar-menu a.active {
      color: white;
      font-weight: bold;
    }

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
    .tombol-menu {
      display: none !important;
      font-size: 1.6rem;
      color: #333;
      cursor: pointer;
    }


    @media (max-width: 768px) {
      .menu-samping {
        transform: translateX(-100%);
        width: 250px;
      }

      .bagian-foto {
        margin-top: 80px;
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
    <div class="bagian-foto">
      <img src="../../gambar/ustj.jpg" alt="logo" class="gambar" />
    </div>


    <div class="daftar-menu">
      <a href="../halaman_utama/halaman_utama.php"
        class="<?= strpos($_SERVER['REQUEST_URI'],'halaman_utama.php') !== false ? 'active' : '' ?>">
        <i class="fa-solid fa-house"></i> Halaman Utama
      </a>
      <a href="../pembayaran/pembayaran.php"
        class="<?= strpos($_SERVER['REQUEST_URI'],'pembayaran.php') !== false ? 'active' : '' ?>">
        <i class="fa-solid fa-file-arrow-up"></i> Upload Pembayaran
      </a>
      <a href="../pendaftaran/pendaftaran.php"
        class="<?= strpos($_SERVER['REQUEST_URI'],'pendaftaran.php') !== false ? 'active' : '' ?>">
        <i class="fa-solid fa-file-circle-plus"></i> Pendaftaran Praktikum
      </a>
      <a href="../jadwal/jadwal.php"
        class="<?= strpos($_SERVER['REQUEST_URI'],'jadwal.php') !== false ? 'active' : '' ?>">
        <i class="fa-solid fa-calendar-days"></i> Jadwal Praktikum
      </a>
      <a href="../pengaturan/pengaturan.php"
        class="<?= strpos($_SERVER['REQUEST_URI'],'pengaturan.php') !== false ? 'active' : '' ?>">
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
    tombolMenu.addEventListener("click", () => {
      menuSamping.classList.toggle("active");
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