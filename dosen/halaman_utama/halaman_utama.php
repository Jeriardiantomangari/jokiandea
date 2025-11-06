<?php
// Sesi & koneksi
session_start();
include '../../koneksi/sidebardosen.php';
include '../../koneksi/koneksi.php';

// Akses: hanya dosen
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dosen') { header("Location: ../login.php"); exit; }
function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// ✅ gunakan ID dari tabel `dosen`, bukan `users`
$id_dosen = (int)($_SESSION['dosen_id'] ?? 0);

// Semester aktif
$semAktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, nama_semester, tahun_ajaran FROM semester WHERE status='Aktif' LIMIT 1"));
$id_semester_aktif = (int)($semAktif['id'] ?? 0);

/* === [BARU] Ambil NIDN & Nama dosen untuk header === */
$dosenInfo = ['nidn'=>'-','nama'=>'-'];
if ($id_dosen) {
  $tmp = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nidn, nama FROM dosen WHERE id = $id_dosen LIMIT 1"));
  if ($tmp) $dosenInfo = $tmp;
}

// KPI (total praktikum/shift/mahasiswa)
$total_praktikum = $total_shift = $total_mhs_all = 0;
if ($id_semester_aktif && $id_dosen) {
  $row = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(DISTINCT mk.id) AS total_praktikum, COUNT(jp.id) AS total_shift
    FROM jadwal_praktikum jp
    JOIN matakuliah_praktikum mk ON mk.id = jp.id_mk
    WHERE jp.id_semester = $id_semester_aktif AND jp.id_dosen = $id_dosen
  "));
  $total_praktikum = (int)($row['total_praktikum'] ?? 0);
  $total_shift     = (int)($row['total_shift'] ?? 0);

  $row2 = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total_mhs
    FROM pilihan_jadwal pj
    JOIN jadwal_praktikum jp ON jp.id = pj.id_jadwal
    WHERE jp.id_dosen = $id_dosen
      AND jp.id_semester = $id_semester_aktif
      AND pj.id_semester = $id_semester_aktif
  "));
  $total_mhs_all = (int)($row2['total_mhs'] ?? 0);
}

// Statistik kehadiran (pie)
$statH = $statA = $statI = 0;
if ($id_semester_aktif && $id_dosen) {
  $statRes = mysqli_query($conn, "
    SELECT LOWER(ad.status) AS st, COUNT(*) AS n
    FROM absensi_detail ad
    JOIN absensi_sesi s ON s.id = ad.id_sesi
    JOIN jadwal_praktikum jp ON jp.id = s.id_jadwal
    WHERE s.id_dosen = $id_dosen AND s.selesai_at IS NOT NULL AND jp.id_semester = $id_semester_aktif
    GROUP BY LOWER(ad.status)
  ");
  while($r = mysqli_fetch_assoc($statRes)){
    if ($r['st']==='hadir') $statH = (int)$r['n'];
    elseif ($r['st']==='alpha') $statA = (int)$r['n'];
    elseif ($r['st']==='izin') $statI = (int)$r['n'];
  }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Dashboard Dosen</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* Area konten utama (tetap) */
.konten-utama{ margin-left:250px; margin-top:60px; padding:30px; min-height:calc(100vh - 60px); background:#f9f9f9; font-family:Arial,sans-serif; width:100%; }
.konten-utama h2{ margin-bottom:30px; color:#333; text-align:center; }

/* Kartu dashboard */
.kartu-dashboard{ display:flex; flex-wrap:wrap; justify-content:center; }
.kartu{
  flex:1 1 calc(50% - 30px); max-width:100%; min-width:250px;
  padding:25px; background:#fff; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.1);
  text-align:center; transition:transform .3s, box-shadow .3s; margin-bottom:20px; margin-right:20px;
}
.kartu:hover{ transform:translateY(-6px); box-shadow:0 6px 14px rgba(0,0,0,0.15); }
.kartu h3{ margin-bottom:15px; color:#555; font-size:20px; }
.kartu p{ font-size:22px; font-weight:bold; color:#007bff; margin-bottom:12px; }
.kartu i{ font-size:32px; color:#888; }

/* Aksi cepat */
.aksi-cepat{ display:flex; gap:10px; flex-wrap:wrap; justify-content:center; margin-top:8px; }
.aksi-cepat a{
  text-decoration:none; display:inline-flex; align-items:center; gap:8px;
  padding:10px 14px; border-radius:8px; color:#fff; font-weight:600;
}
.tombol-biru{ background:#0ea5e9; }
.tombol-hijau{ background:#10b981; }
.tombol-kuning{ background:#f59e0b; }

/* Wadah grafik */
.wadah-grafik{
  width:100%; max-width:700px; margin:0 auto 60px auto; background:#fff; padding:25px;
  border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.1);
}

/* ========================= */
/* ====== RESPONSIVE ======= */
/* ========================= */

/* Tablet landscape & layar sedang */
@media screen and (max-width: 1024px){
  .konten-utama{ margin-left:0; padding:24px; overflow-x:hidden; }
  .konten-utama h2{ font-size:20px; margin-bottom:22px; }
  .kartu-dashboard{ justify-content:center; gap:20px; }
  .kartu{
    flex:1 1 calc(50% - 20px);
    margin-right:0;
  }
  .wadah-grafik{ width:92%; }
}

/* Ponsel / Tablet kecil */
@media screen and (max-width:768px){
  .konten-utama{ margin-left:0; padding:20px; width:100%; text-align:center; overflow-x:hidden; }
  .konten-utama h2{ font-size:18px; margin-bottom:18px; }
  .kartu-dashboard{ gap:16px; }
  .kartu{
    flex:1 1 100%;
    max-width:none;
    margin:auto;
    margin-bottom:16px;
    margin-right:0;
    padding:20px;
  }
  .kartu h3{ font-size:18px; }
  .kartu p{ font-size:20px; }

  .wadah-grafik{ width:95%; padding:18px; }
  /* Pastikan chart terlihat proporsional di mobile */
  .wadah-grafik canvas{ width:100% !important; height:320px !important; }

  /* Hindari overflow horizontal dari elemen inline-block */
  img, canvas{ max-width:100%; height:auto; }
}
</style>
</head>
<body>
<div class="konten-utama">
  <!-- NIDN - Nama -->
  <h2><?= e($dosenInfo['nidn'] ?? '-') ?> - <?= e($dosenInfo['nama'] ?? '-') ?></h2>

  <div class="kartu-dashboard">
    <div class="kartu">
      <h3>Total Praktikum</h3>
      <p><?= number_format($total_praktikum); ?></p>
      <i class="fa-solid fa-book-open"></i>
    </div>

    <div class="kartu">
      <h3>Total Mahasiswa</h3>
      <p><?= number_format($total_mhs_all); ?></p>
      <i class="fa-solid fa-users"></i>
    </div>

    <div class="kartu">
      <h3>Total Shift</h3>
      <p><?= number_format($total_shift); ?></p>
      <i class="fa-solid fa-calendar-days"></i>
    </div>

    <div class="kartu">
      <h3>Aksi Cepat</h3>
      <div class="aksi-cepat">
        <a class="tombol-biru"  href="../absen/absen.php"><i class="fa-solid fa-user-check"></i> Absensi</a>
        <a class="tombol-hijau" href="../jadwal/jadwal.php"><i class="fa-solid fa-table"></i> Jadwal</a>
        <a class="tombol-kuning" href="../laporan/laporan.php"><i class="fa-solid fa-print"></i> Laporan</a>
      </div>
    </div>
  </div>

  <!-- Grafik pai kehadiran -->
  <div class="wadah-grafik">
    <canvas id="pieKehadiran"></canvas>
  </div>
</div>

<script>
// Grafik pie: Hadir/Alpha/Izin
const ctx = document.getElementById('pieKehadiran').getContext('2d');
new Chart(ctx, {
  type: 'pie',
  data: {
    labels: ['Hadir','Alpha','Izin'],
    datasets: [{
      data: [<?= (int)$statH ?>, <?= (int)$statA ?>, <?= (int)$statI ?>],
      backgroundColor: ['#28a745','#dc3545','#ffc107'],
      borderColor: ['#fff','#fff','#fff'],
      borderWidth: 2
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins:{
      legend:{ position:'bottom', labels:{ font:{ size:14 } } },
      tooltip:{ callbacks:{ label:(ctx)=> (ctx.label||'')+': '+(ctx.raw||0).toLocaleString('id-ID') } }
    }
  }
});
</script>
</body>
</html>
