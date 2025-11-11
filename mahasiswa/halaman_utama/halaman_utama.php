<?php
session_start();
include '../../koneksi/sidebarmhs.php';
include '../../koneksi/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mahasiswa') {
  header("Location: ../index.php"); exit;
}

function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// ——— Inisialisasi nilai default agar tidak muncul warning ———
$jumlah_mk   = 0;
$mhs         = ['nim' => '-', 'nama' => '-'];
$sem         = ['nama_semester' => '-', 'tahun_ajaran' => '-', 'id' => 0];
$jadwal_list = [];

// Pastikan ID mahasiswa valid (integer)
$id_mhs = (int)($_SESSION['mhs_id'] ?? 0);

// Cek koneksi
if (!isset($conn) || !$conn) {
  // Hard stop bila koneksi tidak ada
  http_response_code(500);
  echo "Koneksi database tidak tersedia.";
  exit;
}

/** Helper kecil untuk prepared statement single row */
function fetch_one(mysqli $conn, string $sql, string $types = '', ...$params): ?array {
  $stmt = $conn->prepare($sql);
  if (!$stmt) return null;
  if ($types) $stmt->bind_param($types, ...$params);
  if (!$stmt->execute()) { $stmt->close(); return null; }
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();
  return $row ?: null;
}

/** Helper kecil untuk prepared statement multi row */
function fetch_all(mysqli $conn, string $sql, string $types = '', ...$params): array {
  $stmt = $conn->prepare($sql);
  if (!$stmt) return [];
  if ($types) $stmt->bind_param($types, ...$params);
  if (!$stmt->execute()) { $stmt->close(); return []; }
  $res = $stmt->get_result();
  $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  $stmt->close();
  return $rows ?: [];
}

// ——— Ambil data mahasiswa (nama dan NIM) ———
if ($id_mhs > 0) {
  $row = fetch_one($conn, "SELECT nama, nim FROM mahasiswa WHERE id = ?", "i", $id_mhs);
  if ($row) $mhs = $row;
}

// ——— Semester aktif ———
$row_sem = fetch_one($conn, "SELECT id, nama_semester, tahun_ajaran FROM semester WHERE status='Aktif' LIMIT 1");
if ($row_sem) {
  $sem = $row_sem;
}
$id_sem_aktif = (int)($sem['id'] ?? 0);

// ——— Kontrak terbaru di semester aktif ———
if ($id_mhs > 0 && $id_sem_aktif > 0) {
  $kontrak = fetch_one(
    $conn,
    "SELECT mk_dikontrak
     FROM kontrak_mk
     WHERE id_mahasiswa = ? AND id_semester = ?
     ORDER BY id DESC LIMIT 1",
    "ii",
    $id_mhs, $id_sem_aktif
  );

  // Ringkasan: jumlah MK dikontrak
  if (!empty($kontrak['mk_dikontrak'])) {
    // Pecah string "1,2,3" menjadi array, bersihkan spasi & kosong
    $arr = array_filter(array_map('trim', explode(',', $kontrak['mk_dikontrak'])));
    $jumlah_mk = count($arr);
  }
}

// ——— Semua jadwal yang sudah diambil (nama mk, hari, jam, ruangan) ———
if ($id_mhs > 0 && $id_sem_aktif > 0) {
  // Catatan: FIELD untuk urutan hari tidak bisa di-bind, jadi kita tulis langsung di SQL
  $jadwal_list = fetch_all(
    $conn,
    "SELECT mk.nama_mk, jp.hari, jp.jam_mulai, jp.jam_selesai, r.nama_ruangan
     FROM pilihan_jadwal pj
     JOIN jadwal_praktikum jp ON jp.id = pj.id_jadwal
     JOIN matakuliah_praktikum mk ON mk.id = jp.id_mk
     LEFT JOIN ruangan r ON r.id = jp.id_ruangan
     WHERE pj.id_mahasiswa = ? AND pj.id_semester = ?
     ORDER BY FIELD(jp.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'),
              jp.jam_mulai",
    "ii",
    $id_mhs, $id_sem_aktif
  );
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1"> 
  <title>Beranda Mahasiswa</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body { 
      margin:0; 
      font-family: Arial, sans-serif; 
      background:#f5f6fa }

    .bungkus { 
      margin-left:250px; 
      margin-top:60px; 
      padding:20px; 
      min-height:calc(100vh - 60px) }

    h2 { 
      margin:0 0 8px; 
      color:#333 }
      
     .halus{ 
      padding:10px 12px; 
      border-radius:8px; 
      margin:0 0 8px; 
      color:#333 ; 
      background:#fff; 
      border:1px solid #e5e7eb; }
  
    .baris { 
      display:flex; 
      gap:12px; 
      flex-wrap:wrap; 
      margin-top:8px; }

    .kartu { 
      background:#fff; 
      border-radius:10px; 
      box-shadow:0 2px 8px rgba(0,0,0,.06); 
      padding:16px; }

    .kartu h4 { 
      margin:0 0 8px; 
      font-size:16px; 
      color:#111 }

    .aksi-cepat { 
      display:flex; 
      gap:8px; 
      flex-wrap:wrap; }

    .tombol { 
      display:inline-flex; 
      align-items:center; 
      gap:8px; 
      padding:10px 14px; 
      border-radius:8px; 
      color:black; 
      text-decoration:none; 
      font-weight:600 }

    .biru { 
      background:#00AEEF } 
    .hijau { 
      background:#10b981 } 
    .kuning { 
      background:#f59e0b }

    .indikator { 
      display:flex; 
      align-items:center; 
      gap:10px }

    .indikator .ikon { 
      width:36px; 
      height:36px; 
      border-radius:10px; 
      background:#e6f4ff; 
      display:flex; 
      align-items:center; 
      justify-content:center }

    .indikator .nilai { 
      font-size:20px; 
      font-weight:800 }

    .daftar-mini { 
      margin:0; 
      padding:0; 
      list-style:none }

    .daftar-mini li { 
      padding:8px 0; 
      border-bottom:1px dashed #e2e8f0 }

    .langkah-langkah { 
      display:grid; 
      grid-template-columns:repeat(5,1fr); 
      gap:10px; 
      counter-reset:stp }

    .langkah { 
      background:#f8fafc; 
      border:1px solid #e2e8f0; 
      border-radius:10px; 
      padding:12px; 
      position:relative }

    .langkah:before {
       counter-increment:stp; 
       content:counter(stp); 
       position:absolute; 
       top:-10px; 
       left:-10px; 
       background:#0ea5e9; 
       color:#fff; 
       width:24px; 
       height:24px; 
       border-radius:999px; 
       display:flex; 
       align-items:center; 
       justify-content:center; 
       font-size:12px }

    .langkah b { 
      display:block; 
      margin-bottom:6px }

    @media screen and (max-width:768px){
      html,body{ 
        width:100%; 
        overflow-x:hidden; }

      *{ box-sizing:border-box; }

      .bungkus{ 
        margin-left:0; 
        padding:16px; 
        min-height:calc(100vh - 60px); }

      h2{ 
        font-size:20px; }

      .baris{ 
        flex-direction:column; 
        gap:10px; }

      .baris>.kartu{ 
        min-width:0!important; 
        width:100%!important; 
        flex:1 1 auto; }

      .kartu{ 
        padding:12px;
         border-radius:12px; }

      .aksi-cepat{ 
        flex-direction:column; 
        gap:10px; }

      .aksi-cepat .tombol{ 
        width:100%; 
        justify-content:center; 
        padding:12px; 
        font-size:14px; }

      .indikator{ 
        gap:8px; }

      .indikator .ikon{
         width:32px; 
         height:32px; }

      .indikator .nilai{ 
        font-size:18px; }

      .langkah-langkah{ 
        grid-template-columns:1fr; 
        gap:8px; }

      .langkah{ 
        padding:12px; }

      .langkah:before{ 
        top:-8px; 
        left:-8px; 
        width:22px; 
        height:22px; 
        font-size:11px; }
    }
  </style>
</head>
<body>
  <div class="bungkus">
    <h2><?= e($mhs['nim'] ?? '-') ?> <?= e($mhs['nama'] ?? '-') ?></h2>
    <p class="halus">
      Semester: <b><?= e($sem['nama_semester'] ?? '-') ?></b> /
      <b><?= e($sem['tahun_ajaran'] ?? '-') ?></b>
    </p>

    <!-- Aksi cepat -->
    <div class="kartu">
      <h4>Aksi Cepat</h4>
      <div class="aksi-cepat">
        <a class="tombol biru" href="../pembayaran/pembayaran.php"><i class="fa-solid fa-file-arrow-up"></i> Upload Bukti</a>
        <a class="tombol hijau" href="../pendaftaran/pendaftaran.php"><i class="fa-solid fa-calendar-check"></i> Pilih Jadwal</a>
        <a class="tombol kuning" href="../jadwal/jadwal.php"><i class="fa-solid fa-table"></i> Jadwal Saya</a>
      </div>
    </div>

    <!-- Ringkasan + Jadwal -->
    <div class="baris">
      <div class="kartu">
        <h4>Ringkasan Saya</h4>
        <div class="indikator">
          <div class="ikon"><i class="fa-solid fa-book-open"></i></div>
          <!-- aman dari warning karena $jumlah_mk selalu terdefinisi -->
          <div>Praktek Dikontrak: <span class="nilai"><?= (int)$jumlah_mk ?></span></div>
        </div>
      </div>

      <div class="kartu">
        <h4>Jadwal</h4>
        <?php if (empty($jadwal_list)): ?>
          <div style="color:#6b7280">Belum ada jadwal yang Anda pilih pada semester ini.</div>
        <?php else: ?>
          <ul class="daftar-mini">
            <?php foreach ($jadwal_list as $j):
              $mulai  = isset($j['jam_mulai'])   ? substr($j['jam_mulai'],  0, 5) : '--:--';
              $selesai= isset($j['jam_selesai']) ? substr($j['jam_selesai'],0, 5) : '--:--';
              $jam    = e($mulai).'–'.e($selesai);
              $ruang  = !empty($j['nama_ruangan']) ? e($j['nama_ruangan']) : '-';
            ?>
              <li><b><?= e($j['nama_mk'] ?? '-') ?></b> — <?= e($j['hari'] ?? '-') ?>, <?= $jam ?> (<?= $ruang ?>)</li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>

    <!-- Tata cara -->
    <div class="kartu" style="margin-top:12px">
      <h4>Tata Cara Pendaftaran Praktikum</h4>
      <div class="langkah-langkah">
        <div class="langkah"><b>Lengkapi Kontrak</b> Isi data dan centang MK yang akan diambil.</div>
        <div class="langkah"><b>Upload Bukti Bayar</b> Format pdf/jpg/png, maks 2MB.</div>
        <div class="langkah"><b>Tunggu Verifikasi</b> Admin memeriksa berkas.</div>
        <div class="langkah"><b>Pilih Shift</b> Pilih 1 shift per MK sesuai kuota.</div>
        <div class="langkah"><b>Cek Jadwal</b> Lihat “Jadwal Saya” & unduh modul bila ada.</div>
      </div>
    </div>
  </div>
</body>
</html>
