<?php
// ===== Sesi & Koneksi =====
session_start();
include '../../koneksi/sidebardosen.php';
include '../../koneksi/koneksi.php';

// ===== Akses: hanya dosen =====
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dosen') {
  header("Location: ../login.php"); exit;
}

// ===== Util HTML escape =====
function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// ===== CSRF =====
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$CSRF = $_SESSION['csrf'];
function verify_csrf(){
  return isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']);
}

// ===== Flash message =====
function flash_add($type, $msg){ $_SESSION['flash'][$type][] = $msg; }
function flash_show(){
  if (empty($_SESSION['flash'])) return;
  // mapping tipe → kelas
  $map = ['ok'=>'kotak-info info-berhasil','err'=>'kotak-info info-peringatan'];
  foreach ($map as $k=>$cls) {
    if (!empty($_SESSION['flash'][$k])) {
      foreach ($_SESSION['flash'][$k] as $m) echo '<div class="'.e($cls).'">'.e($m).'</div>';
    }
  }
  unset($_SESSION['flash']);
}

// ===== Data dosen & semester aktif =====
$id_dosen = (int)($_SESSION['id_user'] ?? 0);
$semAktif = mysqli_fetch_assoc(
  mysqli_query($conn, "SELECT id, nama_semester, tahun_ajaran FROM semester WHERE status='Aktif' LIMIT 1")
);
$id_semester_aktif = $semAktif['id'] ?? null;

// ===== Daftar shift dosen (semester aktif) =====
$jadwalRes = [];
if ($id_semester_aktif && $id_dosen) {
  $qJ = mysqli_query($conn, "
    SELECT jp.id, mk.kode_mk, mk.nama_mk, jp.hari, jp.jam_mulai, jp.jam_selesai, r.nama_ruangan
    FROM jadwal_praktikum jp
    JOIN matakuliah_praktikum mk ON mk.id = jp.id_mk
    LEFT JOIN ruangan r ON r.id = jp.id_ruangan
    WHERE jp.id_semester = ".(int)$id_semester_aktif." AND jp.id_dosen = ".(int)$id_dosen."
    ORDER BY FIELD(jp.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), jp.jam_mulai
  ");
  while($row = mysqli_fetch_assoc($qJ)){ $jadwalRes[] = $row; }
}

// ===== Ambil mahasiswa per jadwal =====
function ambilMahasiswaByJadwal(mysqli $conn, int $id_jadwal, ?int $id_semester): array {
  $data = [];
  $sql = "
    SELECT m.id AS id_mhs, m.nim, m.nama, m.jurusan, m.jenis_kelamin, m.alamat, m.no_hp
    FROM pilihan_jadwal pj
    JOIN mahasiswa m ON m.id = pj.id_mahasiswa
    WHERE pj.id_jadwal = ".(int)$id_jadwal."
      ".($id_semester ? "AND pj.id_semester = ".(int)$id_semester : "")."
    ORDER BY m.nama ASC
  ";
  if ($q = mysqli_query($conn, $sql)) while($r = mysqli_fetch_assoc($q)) $data[] = $r;
  return $data;
}

// ===== State sesi di sesi PHP =====
if (!isset($_SESSION['absensi'])) $_SESSION['absensi'] = ['sesi_id'=>null,'id_jadwal'=>null];

// ====== HANDLE POST ======
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!verify_csrf()) { flash_add('err','Token CSRF tidak valid.'); header('Location: '.$_SERVER['PHP_SELF']); exit; }

  $aksi = $_POST['aksi'] ?? '';

  // --- Mulai sesi ---
  if ($aksi === 'mulai') {
    $id_jadwal = (int)($_POST['id_jadwal'] ?? 0);
    if (!$id_jadwal) { flash_add('err','Pilih shift terlebih dahulu.'); header('Location: '.$_SERVER['PHP_SELF']); exit; }

    $cek = mysqli_fetch_assoc(mysqli_query($conn, "
      SELECT jp.id FROM jadwal_praktikum jp
      WHERE jp.id = $id_jadwal AND jp.id_dosen = $id_dosen AND jp.id_semester = $id_semester_aktif
      LIMIT 1
    "));
    if (!$cek) { flash_add('err','Shift tidak valid.'); header('Location: '.$_SERVER['PHP_SELF']); exit; }

    // Lanjutkan sesi terbuka jika ada
    $open = mysqli_fetch_assoc(mysqli_query($conn, "
      SELECT id FROM absensi_sesi WHERE id_jadwal=$id_jadwal AND id_dosen=$id_dosen AND selesai_at IS NULL LIMIT 1
    "));
    if ($open) {
      $_SESSION['absensi'] = ['sesi_id'=>(int)$open['id'],'id_jadwal'=>$id_jadwal];
      flash_add('ok','Melanjutkan sesi absensi yang masih terbuka.');
      header('Location: '.$_SERVER['PHP_SELF']); exit;
    }

    // Buat sesi baru
    $stmt = mysqli_prepare($conn, "INSERT INTO absensi_sesi (id_jadwal,id_dosen) VALUES (?,?)");
    mysqli_stmt_bind_param($stmt,"ii",$id_jadwal,$id_dosen);
    mysqli_stmt_execute($stmt);
    $_SESSION['absensi'] = ['sesi_id'=>mysqli_insert_id($conn),'id_jadwal'=>$id_jadwal];
    flash_add('ok','Sesi absensi dimulai.');
    header('Location: '.$_SERVER['PHP_SELF']); exit;
  }

  // --- Selesaikan & simpan ---
  if ($aksi === 'selesai') {
    $sesi_id   = (int)($_SESSION['absensi']['sesi_id'] ?? 0);
    $id_jadwal = (int)($_SESSION['absensi']['id_jadwal'] ?? 0);
    $mhsStatuses = $_POST['mhs'] ?? [];

    $stmt = mysqli_prepare($conn,"INSERT INTO absensi_detail (id_sesi,id_mahasiswa,status)
      VALUES (?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status), dicatat_pada=NOW()");
    foreach($mhsStatuses as $id_mhs=>$val){
      $status = ($val==='H'?'Hadir':($val==='A'?'Alpha':($val==='I'?'Izin':null)));
      if(!$status) continue;
      $idm=(int)$id_mhs;
      mysqli_stmt_bind_param($stmt,"iis",$sesi_id,$idm,$status);
      mysqli_stmt_execute($stmt);
    }
    mysqli_stmt_close($stmt);

    mysqli_query($conn,"UPDATE absensi_sesi SET selesai_at=NOW() WHERE id=$sesi_id");
    $_SESSION['absensi']=['sesi_id'=>null,'id_jadwal'=>null];
    flash_add('ok','Absensi disimpan & sesi ditutup.');
    header('Location: '.$_SERVER['PHP_SELF']); exit;
  }
}

// ====== GET DATA UNTUK VIEW ======
$sedang_sesi   = (int)($_SESSION['absensi']['sesi_id'] ?? 0);
$sedang_jadwal = (int)($_SESSION['absensi']['id_jadwal'] ?? 0);
$daftar_mhs    = [];
$mkInfo        = null;

if ($sedang_sesi && $sedang_jadwal) {
  $mkInfo = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT mk.nama_mk, jp.hari, jp.jam_mulai, jp.jam_selesai
    FROM jadwal_praktikum jp
    JOIN matakuliah_praktikum mk ON mk.id = jp.id_mk
    WHERE jp.id = $sedang_jadwal LIMIT 1
  "));
  $daftar_mhs = ambilMahasiswaByJadwal($conn, $sedang_jadwal, $id_semester_aktif);
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Absensi Praktikum</title>

<!-- Pustaka -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* ===== Tata letak utama ===== */
.area-utama { margin-left:250px; margin-top:60px; padding:30px; min-height:calc(100vh - 60px); background:#f9f9f9; font-family:Arial,sans-serif; }
.area-utama h2 { margin-bottom:10px; color:#333; }

/* ===== Tombol ===== */
.tombol-umum { border:none; border-radius:6px; cursor:pointer; color:white; font-size:12px; transition:.2s; padding:8px 12px; }
.tombol-umum:hover { opacity:.9; }
.tombol-mulai { background:#0ea5e9; }
.tombol-selesai { background:#10b981; }

/* ===== Kontrol DataTables (biarkan kelas bawaannya) ===== */
.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select { padding:6px 10px; border-radius:5px; border:1px solid #ccc; font-size:14px; margin-bottom:5px; }

/* ===== Tabel absensi ===== */
.tabel-absensi { width:100%; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,.08); table-layout:fixed; }
.tabel-absensi th { background:#8bc9ff; color:#333; text-align:left; padding:12px 15px; }
.tabel-absensi td { padding:12px 15px; border-bottom:1px solid #ddd; border-right:1px solid #eee; }
.tabel-absensi tr:hover { background:#f7fbff; }

/* ===== Info ===== */
.kotak-info{ padding:10px 12px; border-radius:8px; margin:8px 0; }
.info-berhasil{ background:#ecfeff; border:1px solid #67e8f9; color:#075985; }
.info-peringatan{ background:#fff7ed; border:1px solid #fdba74; color:#7c2d12; }

/* ===== Aksi (radio) ===== */
.kolom-aksi{ display:flex; gap:8px; justify-content:center; }
.lencana{ display:inline-block; border-radius:5px; padding:3px 6px; font-size:12px; border:1px solid #cbd5e1; background:#f8fafc; cursor:pointer; user-select:none;}
.lencana input{ display:none; }
.lencana.active{ background:#e0f2fe; border-color:#38bdf8; }

/* ===== Responsif ===== */
@media screen and (max-width: 768px) {
  .area-utama { margin-left:0; padding:20px; width:100%; text-align:center; }
  .area-utama h2 { text-align:center; }
  .tabel-absensi, thead, tbody, th, td, tr { display:block; }
  thead tr { display:none; }
  tr { margin-bottom:15px; border-bottom:2px solid #000; }
  td { text-align:right; padding-left:50%; position:relative; }
  td::before { content: attr(data-label); position:absolute; left:15px; width:45%; font-weight:bold; text-align:left; }
}
</style>
</head>
<body>
<div class="area-utama">
  <h2>Absensi Praktikum</h2>

  <?php if(!$id_semester_aktif): ?>
    <div class="kotak-info info-peringatan">Belum ada <b>Semester</b> berstatus <b>Aktif</b>.</div>
  <?php else: ?>
    <div class="kotak-info info-berhasil">Semester: <b><?= e($semAktif['nama_semester']) ?></b><b>/</b><b><?= e($semAktif['tahun_ajaran']) ?></b></div>
  <?php endif; ?>

  <?php flash_show(); ?>

  <!-- ===== Pilih Shift + Mulai ===== -->
  <form method="post" class="bar" autocomplete="off" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
    <input type="hidden" name="aksi" value="mulai">
    <input type="hidden" name="csrf" value="<?= e($CSRF) ?>">
    <label for="id_jadwal"><b>Pilih Shift</b></label>
    <select name="id_jadwal" id="id_jadwal" <?= $sedang_sesi ? 'disabled' : '' ?> required>
      <option value="">-- pilih --</option>
      <?php foreach($jadwalRes as $j):
        $jam = substr($j['jam_mulai'],0,5).' - '.substr($j['jam_selesai'],0,5);
        $label = $j['hari'].' '.$jam;
      ?>
        <option value="<?= (int)$j['id'] ?>" <?= $sedang_jadwal===(int)$j['id'] ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>

    <button class="tombol-umum tombol-mulai" type="submit" <?= (!$id_semester_aktif || $sedang_sesi) ? 'disabled' : '' ?>>
      <i class="fa-solid fa-play"></i> Mulai Praktikum
    </button>
  </form>

  <!-- ===== Info sesi berjalan ===== -->
  <?php if($sedang_sesi && $mkInfo): ?>
    <div class="kotak-info info-berhasil">
      <b>Sesi berjalan:</b> <?= e($mkInfo['nama_mk']) ?>, <?= e($mkInfo['hari']) ?>
      <?= e(substr($mkInfo['jam_mulai'],0,5).' - '.substr($mkInfo['jam_selesai'],0,5)) ?>.
    </div>
  <?php endif; ?>

  <?php
    // Flag untuk UI
    $sesiAktif = ($sedang_sesi && $mkInfo && !empty($daftar_mhs));
  ?>

  <?php if ($sesiAktif): ?>
    <!-- ===== MODE: sesi aktif (form simpan) ===== -->
    <form method="post" id="form-absen" autocomplete="off">
      <input type="hidden" name="aksi" value="selesai">
      <input type="hidden" name="csrf" value="<?= e($CSRF) ?>">

      <table id="tabel-absen" class="tabel-absensi">
        <thead>
          <tr>
            <th>No</th>
            <th>NIM</th>
            <th>Nama</th>
            <th>Jurusan</th>
            <th>Jenis Kelamin</th>
            <th>Alamat</th>
            <th>No HP</th>
            <th style="text-align:center;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php $no=1; foreach($daftar_mhs as $m): $id_mhs=(int)$m['id_mhs']; ?>
            <tr>
              <td data-label="No"><?= $no++ ?></td>
              <td data-label="NIM"><?= e($m['nim']) ?></td>
              <td data-label="Nama"><?= e($m['nama']) ?></td>
              <td data-label="Jurusan"><?= e($m['jurusan']) ?></td>
              <td data-label="Jenis Kelamin"><?= e($m['jenis_kelamin']) ?></td>
              <td data-label="Alamat"><?= e($m['alamat']) ?></td>
              <td data-label="No HP"><?= e($m['no_hp']) ?></td>
              <td data-label="Aksi" class="kolom-aksi">
                <label class="lencana"><input type="radio" name="mhs[<?= $id_mhs ?>]" value="H">Hadir</label>
                <label class="lencana"><input type="radio" name="mhs[<?= $id_mhs ?>]" value="A">Alpha</label>
                <label class="lencana"><input type="radio" name="mhs[<?= $id_mhs ?>]" value="I">Izin</label>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div style="margin-top:14px; display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" id="tandai-hadir-semua" class="lencana">Tandai Hadir Semua</button>
        <button type="submit" class="tombol-umum tombol-selesai"><i class="fa-solid fa-check"></i> Selesai Absen</button>
      </div>
    </form>
  <?php else: ?>
    <!-- ===== MODE: belum aktif (tabel kosong) ===== -->
    <table id="tabel-absen" class="tabel-absensi">
      <thead>
        <tr>
          <th>No</th>
          <th>NIM</th>
          <th>Nama</th>
          <th>Jurusan</th>
          <th>Jenis Kelamin</th>
          <th>Alamat</th>
          <th>No HP</th>
          <th style="text-align:center;">Aksi</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  <?php endif; ?>
</div>

<script>
// ===== Perapihan URL (hindari re-submit) =====
window.history.replaceState({}, document.title, window.location.pathname);

// ===== DataTables (kolom Aksi non-sortable) =====
$(document).ready(function () {
    $('#tabel-absen').DataTable({
    pageLength: 10,
    lengthMenu: [5,10,25,50],
    language: {
      decimal: "",
      emptyTable: "Tidak ada data tersedia",
      info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
      infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
      infoFiltered: "(disaring dari _MAX_ data total)",
      lengthMenu: "Tampilkan _MENU_ data",
      loadingRecords: "Memuat...",
      processing: "Sedang diproses...",
      search: "Cari:",
      zeroRecords: "Tidak ditemukan data yang sesuai",
      paginate: { first:"Pertama", last:"Terakhir", next:"Berikutnya", previous:"Sebelumnya" }
    }
  });
});

// ===== Toggle lencana aktif saat pilih status =====
document.querySelectorAll('td.kolom-aksi').forEach(cell=>{
  cell.addEventListener('change',e=>{
    if(e.target && e.target.type==='radio'){
      const name=e.target.name;
      cell.querySelectorAll('input[name="'+name+'"]').forEach(r=>r.closest('.lencana').classList.remove('active'));
      e.target.closest('.lencana').classList.add('active');
    }
  });
});

// ===== Tandai hadir semua =====
document.getElementById('tandai-hadir-semua')?.addEventListener('click',()=>{
  document.querySelectorAll('td.kolom-aksi').forEach(cell=>{
    const radio=cell.querySelector('input[value="H"]');
    if(radio){
      radio.checked=true;
      cell.querySelectorAll('.lencana').forEach(b=>b.classList.remove('active'));
      radio.closest('.lencana').classList.add('active');
    }
  });
});

// ===== Validasi wajib pilih sebelum submit =====
document.getElementById('form-absen')?.addEventListener('submit',e=>{
  let ok=true;
  document.querySelectorAll('td.kolom-aksi').forEach(c=>{
    if(!c.querySelector('input[type="radio"]:checked')) ok=false;
  });
  if(!ok){
    e.preventDefault();
    alert('Masih ada mahasiswa yang belum dipilih statusnya.');
  }
});
</script>
</body>
</html>
