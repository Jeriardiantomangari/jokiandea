<?php
session_start();
include '../../koneksi/sidebarmhs.php';
include '../../koneksi/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mahasiswa') {
  header("Location: ../login.php"); exit;
}

function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

$id_mhs = (int)($_SESSION['mhs_id'] ?? 0);

/* ---- Semester aktif ---- */
$sem = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT id, nama_semester, tahun_ajaran
   FROM semester WHERE status='Aktif' LIMIT 1"));
$id_sem_aktif = (int)($sem['id'] ?? 0);

/* ---- Kontrak terbaru di semester aktif ---- */
$kontrak = null;
if ($id_sem_aktif) {
  $kontrak = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT mk_dikontrak
    FROM kontrak_mk
    WHERE id_mahasiswa=$id_mhs AND id_semester=$id_sem_aktif
    ORDER BY id DESC LIMIT 1
  "));
}

/* ---- Ringkasan: jumlah MK dikontrak ---- */
$jumlah_mk = 0;
if (!empty($kontrak['mk_dikontrak'])) {
  $arr = array_filter(array_map('trim', explode(',', $kontrak['mk_dikontrak'])));
  $jumlah_mk = count($arr);
}

/* ---- Semua jadwal yang sudah diambil (nama mk, hari, jam, ruangan) ---- */
$jadwal_list = [];
if ($id_sem_aktif) {
  $q = mysqli_query($conn,"
    SELECT mk.nama_mk, jp.hari, jp.jam_mulai, jp.jam_selesai, r.nama_ruangan
    FROM pilihan_jadwal pj
    JOIN jadwal_praktikum jp ON jp.id = pj.id_jadwal
    JOIN matakuliah_praktikum mk ON mk.id = jp.id_mk
    LEFT JOIN ruangan r ON r.id = jp.id_ruangan
    WHERE pj.id_mahasiswa = $id_mhs AND pj.id_semester = $id_sem_aktif
    ORDER BY FIELD(jp.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'),
             jp.jam_mulai
  ");
  while($row = mysqli_fetch_assoc($q)) $jadwal_list[] = $row;
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Beranda Mahasiswa</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  body{margin:0;font-family:Arial,sans-serif;background:#f5f6fa}
  .wrap{margin-left:250px;margin-top:60px;padding:20px;min-height:calc(100vh - 60px)}
  h2{margin:0 0 8px;color:#333}
  .subtle{color:#555;margin:0 0 16px}

  .row{display:flex;gap:12px;flex-wrap:wrap}
  .card{background:#fff;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.06);padding:16px}
  .card h4{margin:0 0 8px;font-size:16px;color:#111}

  .quick{display:flex;gap:8px;flex-wrap:wrap}
  .btn{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:8px;color:#fff;text-decoration:none;font-weight:600}
  .blue{background:#0ea5e9}.green{background:#10b981}.amber{background:#f59e0b}.violet{background:#7c3aed}

  .kpi{display:flex;align-items:center;gap:10px}
  .kpi .icon{width:36px;height:36px;border-radius:10px;background:#e6f4ff;display:flex;align-items:center;justify-content:center}
  .kpi .val{font-size:20px;font-weight:800}

  .mini-list{margin:0;padding:0;list-style:none}
  .mini-list li{padding:8px 0;border-bottom:1px dashed #e2e8f0}

  .steps{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;counter-reset:stp}
  .step{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;position:relative}
  .step:before{counter-increment:stp;content:counter(stp);position:absolute;top:-10px;left:-10px;background:#0ea5e9;color:#fff;width:24px;height:24px;border-radius:999px;display:flex;align-items:center;justify-content:center;font-size:12px}
  .step b{display:block;margin-bottom:6px}

  @media(max-width:1024px){.row{flex-direction:column}.steps{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="wrap">
  <h2>Beranda</h2>
  <p class="subtle">Semester: <b><?= e($sem['nama_semester'] ?? '-') ?></b> / <b><?= e($sem['tahun_ajaran'] ?? '-') ?></b></p>

  <!-- Aksi cepat -->
  <div class="card" style="margin-bottom:12px">
    <h4>Aksi Cepat</h4>
    <div class="quick">
      <a class="btn violet" href="../pembayaran/pembayaran.php"><i class="fa-solid fa-file-arrow-up"></i> Upload Bukti</a>
      <a class="btn green" href="../pendaftaran/pendaftaran.php"><i class="fa-solid fa-calendar-check"></i> Pilih Jadwal</a>
      <a class="btn amber" href="../jadwal/jadwal.php"><i class="fa-solid fa-table"></i> Jadwal Saya</a>
      
    </div>
  </div>

  <!-- Ringkasan + Jadwal -->
  <div class="row">
    <div class="card" style="flex:1;min-width:260px">
      <h4>Ringkasan Saya</h4>
      <div class="kpi">
        <div class="icon"><i class="fa-solid fa-book-open"></i></div>
        <div>Praktek Dikontrak: <span class="val"><?= (int)$jumlah_mk ?></span></div>
      </div>
    </div>

    <div class="card" style="flex:2;min-width:320px">
      <h4>Jadwal</h4>
      <?php if (empty($jadwal_list)): ?>
        <div style="color:#6b7280">Belum ada jadwal yang Anda pilih pada semester ini.</div>
      <?php else: ?>
        <ul class="mini-list">
          <?php foreach ($jadwal_list as $j):
            $jam = e(substr($j['jam_mulai'],0,5)).'–'.e(substr($j['jam_selesai'],0,5));
            $ruang = $j['nama_ruangan'] ? e($j['nama_ruangan']) : '-';
          ?>
            <li><b><?= e($j['nama_mk']) ?></b> — <?= e($j['hari']) ?>, <?= $jam ?> (<?= $ruang ?>)</li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>

  <!-- Tata cara -->
  <div class="card" style="margin-top:12px">
    <h4>Tata Cara Pendaftaran Praktikum</h4>
    <div class="steps">
      <div class="step"><b>Lengkapi Kontrak</b> Isi data dan centang MK yang akan diambil.</div>
      <div class="step"><b>Upload Bukti Bayar</b> Format pdf/jpg/png, maks 2MB.</div>
      <div class="step"><b>Tunggu Verifikasi</b> Admin memeriksa berkas.</div>
      <div class="step"><b>Pilih Shift</b> Pilih 1 shift per MK sesuai kuota.</div>
      <div class="step"><b>Cek Jadwal</b> Lihat “Jadwal Saya” & unduh modul bila ada.</div>
    </div>
  </div>
</div>
</body>
</html>
