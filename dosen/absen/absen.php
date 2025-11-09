<?php
// ===== Sesi & Koneksi (URUTAN PENTING) =====
session_start();
include '../../koneksi/koneksi.php'; // koneksi aman (tidak output HTML)

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
  $map = ['ok'=>'kotak-info info-berhasil','err'=>'kotak-info info-peringatan'];
  foreach ($map as $k=>$cls) {
    if (!empty($_SESSION['flash'][$k])) {
      foreach ($_SESSION['flash'][$k] as $m) echo '<div class="'.e($cls).'">'.e($m).'</div>';
    }
  }
  unset($_SESSION['flash']);
}

// ===== Akses: hanya dosen =====
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dosen') {
  header("Location: ../index.php", true, 303); exit;
}

// ===== Data dosen & semester aktif =====
// ✅ gunakan ID tabel `dosen`, bukan `users`
$id_dosen = (int)($_SESSION['dosen_id'] ?? 0);

$semAktif = mysqli_fetch_assoc(
  mysqli_query($conn, "SELECT id, nama_semester, tahun_ajaran FROM semester WHERE status='Aktif' LIMIT 1")
);
$id_semester_aktif = $semAktif['id'] ?? null;

// ===== Daftar MK & Shift dosen (semester aktif) =====
$mkList = [];            // [mk_id => nama_mk]
$shiftsByMk = [];        // [mk_id => [ [id, hari, jam_mulai, jam_selesai, ruangan] ]]
$shiftIdToMkId = [];     // [id_jadwal => mk_id] (untuk preselect)
if ($id_semester_aktif && $id_dosen) {
  $q = mysqli_query($conn, "
    SELECT 
      mk.id   AS mk_id, mk.nama_mk,
      jp.id   AS id_jadwal, jp.hari, jp.jam_mulai, jp.jam_selesai,
      COALESCE(r.nama_ruangan,'') AS nama_ruangan
    FROM jadwal_praktikum jp
    JOIN matakuliah_praktikum mk ON mk.id = jp.id_mk
    LEFT JOIN ruangan r ON r.id = jp.id_ruangan
    WHERE jp.id_semester = ".(int)$id_semester_aktif." 
      AND jp.id_dosen    = ".(int)$id_dosen."
    ORDER BY mk.nama_mk ASC, FIELD(jp.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), jp.jam_mulai
  ");
  while($row = mysqli_fetch_assoc($q)){
    $mk_id = (int)$row['mk_id'];
    $mkList[$mk_id] = $row['nama_mk'];
    if (!isset($shiftsByMk[$mk_id])) $shiftsByMk[$mk_id] = [];
    $shiftsByMk[$mk_id][] = [
      'id'   => (int)$row['id_jadwal'],
      'hari' => $row['hari'],
      'mulai'=> substr($row['jam_mulai'],0,5),
      'selesai'=> substr($row['jam_selesai'],0,5),
      'ruangan'=> $row['nama_ruangan'],
    ];
    $shiftIdToMkId[(int)$row['id_jadwal']] = $mk_id;
  }
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

// ====== HANDLE POST (DIPINDAH KE ATAS, EVITASI OUTPUT SEBELUM HEADER) ======
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!verify_csrf()) { flash_add('err','Token CSRF tidak valid.'); header('Location: '.$_SERVER['PHP_SELF'], true, 303); exit; }

  $aksi = $_POST['aksi'] ?? '';

  // --- Mulai sesi ---
  if ($aksi === 'mulai') {
    $id_jadwal = (int)($_POST['id_jadwal'] ?? 0);
    if (!$id_jadwal) { flash_add('err','Pilih Mata Kuliah dan Shift terlebih dahulu.'); header('Location: '.$_SERVER['PHP_SELF'], true, 303); exit; }

    $cek = mysqli_fetch_assoc(mysqli_query($conn, "
      SELECT jp.id FROM jadwal_praktikum jp
      WHERE jp.id = $id_jadwal AND jp.id_dosen = $id_dosen AND jp.id_semester = ".(int)$id_semester_aktif."
      LIMIT 1
    "));
    if (!$cek) { flash_add('err','Shift tidak valid.'); header('Location: '.$_SERVER['PHP_SELF'], true, 303); exit; }

    // Lanjutkan sesi terbuka jika ada
    $open = mysqli_fetch_assoc(mysqli_query($conn, "
      SELECT id FROM absensi_sesi WHERE id_jadwal=$id_jadwal AND id_dosen=$id_dosen AND selesai_at IS NULL LIMIT 1
    "));
    if ($open) {
      $_SESSION['absensi'] = ['sesi_id'=>(int)$open['id'],'id_jadwal'=>$id_jadwal];
      flash_add('ok','Melanjutkan sesi absensi yang masih terbuka.');
      header('Location: '.$_SERVER['PHP_SELF'], true, 303); exit;
    }

    // Buat sesi baru
    $stmt = mysqli_prepare($conn, "INSERT INTO absensi_sesi (id_jadwal,id_dosen) VALUES (?,?)");
    mysqli_stmt_bind_param($stmt,"ii",$id_jadwal,$id_dosen);
    mysqli_stmt_execute($stmt);
    $_SESSION['absensi'] = ['sesi_id'=>mysqli_insert_id($conn),'id_jadwal'=>$id_jadwal];
    flash_add('ok','Sesi absensi dimulai.');
    header('Location: '.$_SERVER['PHP_SELF'], true, 303); exit;
  }

  // --- Selesaikan & simpan ---
  if ($aksi === 'selesai') {
    $sesi_id   = (int)($_SESSION['absensi']['sesi_id'] ?? 0);
    $id_jadwal = (int)($_SESSION['absensi']['id_jadwal'] ?? 0);
    $mhsStatuses = $_POST['mhs'] ?? [];

    // Validasi sesi
    if ($sesi_id <= 0) {
      flash_add('err','Sesi absensi tidak ditemukan atau sudah berakhir. Silakan mulai sesi lagi.');
      header('Location: '.$_SERVER['PHP_SELF'], true, 303); exit;
    }
    $cekSesi = mysqli_fetch_assoc(mysqli_query(
      $conn,
      "SELECT id FROM absensi_sesi WHERE id = $sesi_id AND id_dosen = $id_dosen LIMIT 1"
    ));
    if (!$cekSesi) {
      flash_add('err','Sesi absensi tidak valid (mungkin sudah dihapus). Silakan mulai sesi lagi.');
      header('Location: '.$_SERVER['PHP_SELF'], true, 303); exit;
    }

    // Transaksi untuk konsistensi
    mysqli_begin_transaction($conn);
    try {
      $stmt = mysqli_prepare($conn,"INSERT INTO absensi_detail (id_sesi,id_mahasiswa,status)
        VALUES (?,?,?)
        ON DUPLICATE KEY UPDATE status=VALUES(status), dicatat_pada=NOW()");
      if (!$stmt) throw new Exception('Gagal siapkan statement: '.mysqli_error($conn));

      foreach($mhsStatuses as $id_mhs=>$val){
        $status = ($val==='H'?'Hadir':($val==='A'?'Alpha':($val==='I'?'Izin':null)));
        if(!$status) continue;
        $idm=(int)$id_mhs;
        mysqli_stmt_bind_param($stmt,"iis",$sesi_id,$idm,$status);
        if (!mysqli_stmt_execute($stmt)) throw new Exception('Gagal simpan detail: '.mysqli_stmt_error($stmt));
      }
      mysqli_stmt_close($stmt);

      if (!mysqli_query($conn,"UPDATE absensi_sesi SET selesai_at=NOW() WHERE id=$sesi_id")) {
        throw new Exception('Gagal menutup sesi: '.mysqli_error($conn));
      }

      mysqli_commit($conn);

      $_SESSION['absensi']=['sesi_id'=>null,'id_jadwal'=>null];
      flash_add('ok','Absensi disimpan & sesi ditutup.');
      header('Location: '.$_SERVER['PHP_SELF'], true, 303); exit;

    } catch (Throwable $e) {
      mysqli_rollback($conn);
      flash_add('err','Gagal menyimpan absensi: '.$e->getMessage());
      header('Location: '.$_SERVER['PHP_SELF'], true, 303); exit;
    }
  }
}

// ===== Include komponen UI SETELAH semua kemungkinan header redirect =====
include '../../koneksi/sidebardosen.php';

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

// ===== Preselect MK saat ada sesi berjalan =====
$preselect_mk_id = 0;
if ($sedang_jadwal && isset($shiftIdToMkId[$sedang_jadwal])) {
  $preselect_mk_id = (int)$shiftIdToMkId[$sedang_jadwal];
} else {
  if (!empty($mkList)) $preselect_mk_id = (int)array_key_first($mkList);
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
.tombol-mulai { background: #00AEEF; }
.tombol-selesai { background:#10b981; }

/* ===== Kontrol DataTables ===== */
.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select { padding:6px 10px; border-radius:5px; border:1px solid #ccc; font-size:14px; margin-bottom:5px; margin-top:5px;}

/* ===== Tabel absensi ===== */
.tabel-absensi { width:100%; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,.08); table-layout:fixed; }
.tabel-absensi th { background: #00AEEF; color:#333; text-align:left; padding:12px 15px; }
.tabel-absensi td { padding:12px 15px; border-bottom:1px solid #ddd; border-right:1px solid #eee; }
.tabel-absensi tr:hover { background:#f7fbff; }

/* ===== Info ===== */
.kotak-info{ padding:10px 12px; border-radius:8px; margin:8px 0; background:#fff; border:1px solid #e5e7eb; }
.info-berhasil{  color: #333; }
.info-peringatan{  color: #333; }

/* ===== Aksi (radio) ===== */
.kolom-aksi{ display:flex; gap:8px; justify-content:center; }
.lencana{ display:inline-block; border-radius:5px; padding:3px 6px; font-size:12px; border:1px solid #cbd5e1; background:#f8fafc; cursor:pointer; user-select:none;}
.lencana input{ display:none; }
.lencana.active{ background:#e0f2fe; border-color:#38bdf8; }

/* ====== Panel Filter (MK & Shift) ====== */
.filter-panel{
  background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:14px 16px;
  box-shadow:0 2px 6px rgba(0,0,0,.06);
  display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;
}
.field{ display:flex; flex-direction:column; gap:6px; }
.field label{ font-weight:700; color:#111827; font-size:14px; }

/* Select */
.select-styled{
  appearance:none; background:#fff; border:1px solid #cbd5e1; border-radius:10px;
  padding:10px 42px 10px 12px; font-size:14px; line-height:1.2; min-width:240px; outline:none;
  transition:border-color .2s, box-shadow .2s;
  background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 20 20' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 8 10 12 14 8'/></svg>");
  background-repeat:no-repeat; background-position:right 12px center;
}
.select-styled:hover{ border-color:#94a3b8; }
.select-styled:focus{ border-color:#60a5fa; box-shadow:0 0 0 4px rgba(96,165,250,.2); }

.filter-panel .tombol-umum{ border-radius:10px; font-size:13px; padding:10px 16px; display:inline-flex; gap:8px; align-items:center; }

/* ===== Responsif ===== */
@media screen and (max-width: 768px) {
  .area-utama { margin-left:0; padding:20px; width:100%; text-align:center; }
  .area-utama h2 { text-align:center; }
  .filter-panel{ flex-direction:column; align-items:stretch; gap:10px; }
  .field{ width:100%; }
  .select-styled{ min-width:unset; width:100%; }
  .filter-panel .tombol-umum{ width:100%; justify-content:center; }

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

  <!-- ===== Pilih MK -> Shift + Mulai ===== -->
  <form method="post" class="bar filter-panel" autocomplete="off">
    <input type="hidden" name="aksi" value="mulai">
    <input type="hidden" name="csrf" value="<?= e($CSRF) ?>">

    <div class="field">
      <label for="mk_id"><b>Pilih Mata Kuliah</b></label>
      <select id="mk_id" <?= $sedang_sesi ? 'disabled' : '' ?> class="select-styled">
        <option value="">-- pilih MK --</option>
        <?php foreach($mkList as $mk_id=>$nm): ?>
          <option value="<?= (int)$mk_id ?>" <?= ($preselect_mk_id===(int)$mk_id ? 'selected':'') ?>><?= e($nm) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="id_jadwal"><b>Pilih Shift</b></label>
      <select name="id_jadwal" id="id_jadwal" <?= $sedang_sesi ? 'disabled' : '' ?> required class="select-styled">
        <option value="">-- pilih shift --</option>
        <!-- opsi diisi via JS berdasarkan MK -->
      </select>
    </div>

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
if (window.history.replaceState) {
  window.history.replaceState({}, document.title, window.location.pathname);
}

// ===== Data MK & Shift dari PHP =====
const SHIFTS_BY_MK = <?= json_encode($shiftsByMk, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
const PRESELECT_MK_ID = <?= (int)$preselect_mk_id ?>;
const PRESELECT_SHIFT_ID = <?= (int)$sedang_jadwal ?>;

// Render opsi shift berdasarkan MK
function renderShiftOptions(mkId){
  const shiftSel = document.getElementById('id_jadwal');
  const sedangSesi = <?= $sedang_sesi ? 'true':'false' ?>;
  const list = SHIFTS_BY_MK[mkId] || [];
  shiftSel.innerHTML = '<option value="">-- pilih shift --</option>';
  list.forEach(s => {
    const label = `${s.hari} ${s.mulai} - ${s.selesai}${s.ruangan?(' | '+s.ruangan):''}`;
    const opt = document.createElement('option');
    opt.value = s.id;
    opt.textContent = label;
    if (PRESELECT_SHIFT_ID && s.id === PRESELECT_SHIFT_ID) opt.selected = true;
    shiftSel.appendChild(opt);
  });
  shiftSel.disabled = sedangSesi || list.length === 0;
}

// Inisialisasi pilihan MK & Shift
(function initSelects(){
  const mkSel = document.getElementById('mk_id');
  if (mkSel && mkSel.value) {
    renderShiftOptions(parseInt(mkSel.value,10));
  } else if (PRESELECT_MK_ID) {
    mkSel.value = String(PRESELECT_MK_ID);
    renderShiftOptions(PRESELECT_MK_ID);
  }
  mkSel?.addEventListener('change', (e)=>{
    const mkId = parseInt(e.target.value || '0', 10);
    renderShiftOptions(mkId);
  });
})();

// ===== DataTables =====
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
