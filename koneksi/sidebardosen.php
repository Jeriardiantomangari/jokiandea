<?php 
// Dapatkan nama file saat ini
$halaman = basename($_SERVER['PHP_SELF']); 
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dasbor Dosen</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    body { display:flex; min-height:100vh; background:#f3f3f3; overflow-x:hidden; }

    .menu-samping {
      width:250px; background:#3a3b3c; display:flex; flex-direction:column; align-items:flex-start;
      padding:30px 0; color:#fff; position:fixed; top:0; bottom:0; left:0; z-index:200;
      border-right:1px solid #222; transition: transform 0.3s ease;
    }
    .menu-samping.hidden { transform:translateX(-100%); }

    .bagian-foto { width:100%; display:flex; justify-content:center; margin-bottom:30px; cursor:pointer; }
    .bagian-foto img { width:90px; height:90px; border-radius:50%; object-fit:cover; border:3px solid #00b4ff; transition:0.3s; }
    .bagian-foto img:hover { transform:scale(1.05); border-color:#8bc9ff; }

    .daftar-menu { display:flex; flex-direction:column; width:100%; padding-left:20px; }
    .daftar-menu a {
      display:flex; align-items:center; text-decoration:none; color:#fff;
      font-weight:bold; font-size:15px; padding:10px 0; transition:all 0.3s ease;
    }
    .daftar-menu a i { margin-right:10px; width:20px; text-align:center; }
    .daftar-menu a:hover, .daftar-menu a.active { color:#00b4ff; font-weight:bold; }

    .menu-atas {
      position: fixed; top:0; left:250px; right:0; height:60px;
      background:#8bc9ff; border-bottom:1px solid #c0c3c6ff;
      display:flex; justify-content:flex-end; align-items:center; padding:0 10px; z-index:250;
      transition: left 0.3s ease;
    }

    .tombol-keluar {
      background-color:#ff5252; color:white; border:none; padding:10px 15px;
      border-radius:6px; cursor:pointer; font-weight:bold; display:flex; align-items:center; gap:8px; transition:0.3s;
    }
    .tombol-keluar:hover { background-color:#d83c3c; }
    .tombol-menu { display:none !important; font-size:1.6rem; color:#333; cursor:pointer; }

    @media (max-width:768px){
      .menu-samping { transform:translateX(-100%); margin-top:60px; }
      .menu-samping.active { transform:translateX(0); }
      .menu-atas { left:0; justify-content:space-between; }
      .tombol-menu { display:block !important; }
    }
  </style>
</head>
<body>

  <!-- MENU SAMPING -->
  <div class="menu-samping" id="menuSamping">
    <div class="bagian-foto">
      <img src="logo.png" alt="Logo">
    </div>

    <div class="daftar-menu">
      <a href="../halaman_utama/halaman_utama.php" class="<?= strpos($_SERVER['REQUEST_URI'],'halaman_utama.php') !== false ? 'active' : '' ?>">
        <i class="fa-solid fa-house"></i> Halaman Utama
      </a>
      <a href="../jadwal/jadwal.php" class="<?= strpos($_SERVER['REQUEST_URI'],'jadwal.php') !== false ? 'active' : '' ?>">
        <i class="fa-solid fa-calendar-days"></i> Jadwal Praktikum
      </a>
      <a href="../absen/absen.php" class="<?= strpos($_SERVER['REQUEST_URI'],'absen.php') !== false ? 'active' : '' ?>">
        <i class="fa-solid fa-file-invoice-dollar"></i> Absensi
      </a>
      <a href="../laporan/laporan.php" class="<?= strpos($_SERVER['REQUEST_URI'],'laporan.php') !== false ? 'active' : '' ?>">
        <i class="fa-solid fa-file-circle-plus"></i> Laporan
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
    tombolMenu.addEventListener("click", ()=>{ menuSamping.classList.toggle("active"); });
    document.getElementById("keluar").addEventListener("click", ()=>{ window.location.href="../index.php"; });
  </script>

</body>
</html>
