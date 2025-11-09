<?php
// admin/dashboard.php
session_start();
include '../../koneksi/koneksi.php';
include '../../koneksi/sidebar.php';

if (!isset($_SESSION['role']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
   header("Location: ../index.php"); exit;
}

function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// ===================== SEMESTER AKTIF (opsional) =====================
$semAktif = mysqli_fetch_assoc(
  mysqli_query($conn, "SELECT id, nama_semester, tahun_ajaran FROM semester WHERE status='Aktif' LIMIT 1")
);
$id_sem = (int)($semAktif['id'] ?? 0);

// ===================== KPI =====================
$tot_mk       = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM matakuliah_praktikum"))['c'];
$tot_dosen    = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM dosen"))['c'];
$tot_mhs      = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM mahasiswa"))['c'];
$tot_ruangan  = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM ruangan"))['c'];

$tot_jadwal = $sum_kuota = $tot_peserta = 0;
if ($id_sem) {
  $row = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(id) AS jadwal, COALESCE(SUM(COALESCE(kuota_awal, kuota)),0) AS kuota
    FROM jadwal_praktikum WHERE id_semester = {$id_sem}
  "));
  $tot_jadwal  = (int)($row['jadwal'] ?? 0);
  $sum_kuota   = (int)($row['kuota']  ?? 0);
  $tot_peserta = (int)mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) c FROM pilihan_jadwal WHERE id_semester = {$id_sem}
  "))['c'];
}
$util_pct = ($sum_kuota > 0) ? round(($tot_peserta / $sum_kuota) * 100, 1) : 0.0;

// ===================== DATA UNTUK GRAFIK =====================
// 1) total dosen vs mahasiswa
$grafik_total_labels = ['Dosen','Mahasiswa'];
$grafik_total_values = [$tot_dosen, $tot_mhs];

// 2) per prodi (Farmasi vs Analis Kesehatan) dibandingkan Dosen vs Mahasiswa
$prodi_labels     = ['Farmasi','Analis Kesehatan'];
$dosen_by_prodi   = ['Farmasi'=>0,'Analis Kesehatan'=>0];
$mhs_by_prodi     = ['Farmasi'=>0,'Analis Kesehatan'=>0];

// dosen per prodi
$qD = mysqli_query($conn, "SELECT prodi, COUNT(*) c FROM dosen GROUP BY prodi");
while($r = mysqli_fetch_assoc($qD)){
  $p = $r['prodi']; $c = (int)$r['c'];
  if (isset($dosen_by_prodi[$p])) $dosen_by_prodi[$p] = $c;
}
// mahasiswa per jurusan (jurusan == prodi)
$qM = mysqli_query($conn, "SELECT jurusan AS prodi, COUNT(*) c FROM mahasiswa GROUP BY jurusan");
while($r = mysqli_fetch_assoc($qM)){
  $p = $r['prodi']; $c = (int)$r['c'];
  if (isset($mhs_by_prodi[$p])) $mhs_by_prodi[$p] = $c;
}

// untuk Chart.js
$dosen_prodi_values = array_map(fn($k)=>$dosen_by_prodi[$k], $prodi_labels);
$mhs_prodi_values   = array_map(fn($k)=>$mhs_by_prodi[$k],   $prodi_labels);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Admin - Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

  <style>
    /* ===================== DASAR ===================== */
    body { font-family: Arial, sans-serif; background:#f9f9f9; }
    .konten-utama { margin-left:250px; margin-top:60px; padding:30px; min-height:calc(100vh - 60px); background:#f9f9f9; width:100%; }
    h2 { margin:0 0 8px; color:#333; }
    .kotak-info{ padding:10px 12px; border-radius:8px; margin:8px 0 16px; background:#fff; border:1px solid #e5e7eb; color:#333; }

    /* ===================== GRID KPI ===================== */
    .kpi-grid{ display:grid; grid-template-columns: repeat(6, minmax(0,1fr)); gap:12px; margin-bottom:16px; }

    /* Kartu KPI (ikon + teks) */
    .kartu-kpi{
      background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:14px; box-shadow:0 2px 6px rgba(0,0,0,.06);
      display:flex; align-items:center; gap:12px;
    }
    .ikon-kpi{
      width:40px; height:40px; display:grid; place-items:center; border-radius:10px;
      background:#eef2ff; font-size:18px; color:#1f2937; flex:0 0 auto;
    }
    .info-kpi{ display:flex; flex-direction:column; }
    .label-kpi{ color:#6b7280; font-size:12px; }
    .nilai-kpi{ font-size:22px; font-weight:700; margin-top:2px; color:#111827; }

    /* ===================== PANEL & GRID GRAFIK ===================== */
    .panel{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:14px; box-shadow:0 2px 6px rgba(0,0,0,.06); }
    .panel h3{ margin:0 0 10px; font-size:16px; color:#111827; }
    .grid-grafik{ display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin:6px 0 18px; }

    /* ===================== AKSI CEPAT ===================== */
    .grid-aksi-cepat{ display:grid; grid-template-columns: repeat(5, minmax(0,1fr)); gap:12px; }
    .tautan-aksi{
      display:flex; gap:10px; align-items:center; padding:12px; border-radius:12px; border:1px solid #e5e7eb; background:#fff;
      box-shadow:0 2px 6px rgba(0,0,0,.06); text-decoration:none; color:#111827; transition:.2s;
    }
    .tautan-aksi:hover{ transform: translateY(-2px); }
    .tautan-aksi i{ width:36px; height:36px; display:grid; place-items:center; background:#eff6ff; border-radius:8px; font-size:16px; }

    /* ===================== RESPONSIF (MOBILE) ===================== */
    @media (max-width: 768px){
      .konten-utama{
        margin-left: 0;
        padding: 20px;
        width: 100%;
        background-color: #f9f9f9;
        text-align: initial;
        overflow-x: hidden;

      }

      .sidebar{ display: none !important; } /* jika ada sidebar fixed */

      /* KPI: 2 kolom */
      .kpi-grid{
        grid-template-columns: repeat(2, 1fr);
        min-width: 0;
      }

      /* Grafik: 1 kolom */
      .grid-grafik{
        grid-template-columns: 1fr;
        min-width: 0;
      }

      /* Aksi cepat: 2 kolom */
      .grid-aksi-cepat{
        grid-template-columns: repeat(2, 1fr);
        min-width: 0;
      }

      /* Pastikan elemen tidak melebar */
      .panel, .kartu-kpi, .tautan-aksi{ min-width: 0; }

      /* Chart responsive */
      .panel canvas, canvas{
        display: block;
        width: 100% !important;
        height: auto !important;
      }
    }
  </style>
</head>
<body>
  <div class="konten-utama">
    <h2>Dashboard</h2>

    <?php if($semAktif): ?>
      <div class="kotak-info">
        Semester Aktif: <b><?= e($semAktif['nama_semester']) ?></b><b>/</b><b><?= e($semAktif['tahun_ajaran']) ?></b>
      </div>
    <?php else: ?>
      <div class="kotak-info">Belum ada <b>Semester</b> berstatus <b>Aktif</b>. Beberapa metrik akan kosong.</div>
    <?php endif; ?>

    <!-- ===================== KPI ===================== -->
    <div class="kpi-grid">
      <div class="kartu-kpi">
        <div class="ikon-kpi"><i class="fa-solid fa-book-open"></i></div>
        <div class="info-kpi">
          <div class="label-kpi">Mata Kuliah</div>
          <div class="nilai-kpi"><?= $tot_mk ?></div>
        </div>
      </div>

      <div class="kartu-kpi">
        <div class="ikon-kpi"><i class="fa-solid fa-chalkboard-user"></i></div>
        <div class="info-kpi">
          <div class="label-kpi">Dosen</div>
          <div class="nilai-kpi"><?= $tot_dosen ?></div>
        </div>
      </div>

      <div class="kartu-kpi">
        <div class="ikon-kpi"><i class="fa-solid fa-user-graduate"></i></div>
        <div class="info-kpi">
          <div class="label-kpi">Mahasiswa</div>
          <div class="nilai-kpi"><?= $tot_mhs ?></div>
        </div>
      </div>

      <div class="kartu-kpi">
        <div class="ikon-kpi"><i class="fa-solid fa-door-open"></i></div>
        <div class="info-kpi">
          <div class="label-kpi">Ruangan</div>
          <div class="nilai-kpi"><?= $tot_ruangan ?></div>
        </div>
      </div>

      <div class="kartu-kpi">
        <div class="ikon-kpi"><i class="fa-solid fa-calendar-days"></i></div>
        <div class="info-kpi">
          <div class="label-kpi">Jadwal Praktikum</div>
          <div class="nilai-kpi"><?= $tot_jadwal ?></div>
        </div>
      </div>

      <div class="kartu-kpi">
        <div class="ikon-kpi"><i class="fa-solid fa-users"></i></div>
        <div class="info-kpi">
          <div class="label-kpi">Peserta Terdaftar</div>
          <div class="nilai-kpi"><?= $tot_peserta ?></div>
        </div>
      </div>
    </div>

    <!-- ===================== GRAFIK ===================== -->
    <div class="grid-grafik">
      <div class="panel">
        <h3><i class="fa-solid fa-chart-column"></i> Total Dosen vs Mahasiswa</h3>
        <canvas id="chartTotal" height="140"></canvas>
      </div>
      <div class="panel">
        <h3><i class="fa-solid fa-chart-column"></i> Per Prodi: Dosen vs Mahasiswa</h3>
        <canvas id="chartProdi" height="140"></canvas>
      </div>
    </div>

    <!-- ===================== AKSI CEPAT ===================== -->
    <div class="panel">
      <h3><i class="fa-solid fa-bolt"></i> Aksi Cepat</h3>
      <div class="grid-aksi-cepat">
        <a class="tautan-aksi" href="../dosen/dosen.php"><i class="fa-solid fa-chalkboard-user"></i><span>Data Dosen</span></a>
        <a class="tautan-aksi" href="../mahasiswa/mahasiswa.php"><i class="fa-solid fa-user-graduate"></i><span>Data Mahasiswa</span></a>
        <a class="tautan-aksi" href="../ruangan/ruangan.php"><i class="fa-solid fa-door-open"></i><span>Data Ruangan</span></a>
        <a class="tautan-aksi" href="../mk_praktikum/mk_praktikum.php"><i class="fa-solid fa-book"></i><span>Data MK Praktikum</span></a>
        <a class="tautan-aksi" href="../semester/semester.php"><i class="fa-solid fa-layer-group"></i><span>Data Semester</span></a>
        <a class="tautan-aksi" href="../jadwal/jadwal.php"><i class="fa-solid fa-flask"></i><span>Jadwal Praktikum</span></a>
        <a class="tautan-aksi" href="../validasi/validasi.php"><i class="fa-solid fa-circle-check"></i><span>Validasi Pendaftaran</span></a>
        <a class="tautan-aksi" href="../absensi/absensi.php"><i class="fa-solid fa-clipboard-list"></i><span>Data Absensi</span></a>
        <a class="tautan-aksi" href="../laporan/laporan.php"><i class="fa-solid fa-file-lines"></i><span>Data Praktikum</span></a>
        <a class="tautan-aksi" href="../pengaturan/pengaturan.php"><i class="fa-solid fa-gear"></i><span>Pengaturan Akun</span></a>
      </div>
    </div>
  </div>

<script>
// ===================== Chart: Total Dosen vs Mahasiswa =====================
new Chart(document.getElementById('chartTotal'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($grafik_total_labels, JSON_UNESCAPED_UNICODE) ?>,
    datasets: [{
      label: 'Jumlah',
      data: <?= json_encode($grafik_total_values, JSON_UNESCAPED_UNICODE) ?>,
      borderWidth: 1,
      // Warna per-bar: Dosen (biru), Mahasiswa (merah)
      backgroundColor: ['rgba(54, 162, 235, 0.5)', 'rgba(255, 99, 132, 0.5)'],
      borderColor:    ['rgba(54, 162, 235, 1)',   'rgba(255, 99, 132, 1)']
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display:false } },
    scales: { y: { beginAtZero:true, ticks:{ precision:0 } } }
  }
});

// ===================== Chart: Per Prodi Dosen vs Mahasiswa =====================
new Chart(document.getElementById('chartProdi'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($prodi_labels, JSON_UNESCAPED_UNICODE) ?>,
    datasets: [
      {
        label: 'Dosen',
        data: <?= json_encode($dosen_prodi_values, JSON_UNESCAPED_UNICODE) ?>,
        borderWidth: 1,
        backgroundColor: 'rgba(54, 162, 235, 0.5)', // biru
        borderColor:    'rgba(54, 162, 235, 1)'
      },
      {
        label: 'Mahasiswa',
        data: <?= json_encode($mhs_prodi_values, JSON_UNESCAPED_UNICODE) ?>,
        borderWidth: 1,
        backgroundColor: 'rgba(255, 99, 132, 0.5)', // merah
        borderColor:    'rgba(255, 99, 132, 1)'
      }
    ]
  },
  options: {
    responsive:true,
    plugins:{ legend:{ position:'bottom' } },
    scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } }
  }
});
</script>
</body>
</html>
