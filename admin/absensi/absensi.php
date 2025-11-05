<?php
// admin/absensi/laporan_shift.php
session_start();
include '../../koneksi/sidebar.php';   // <-- sesuaikan
include '../../koneksi/koneksi.php';       // <-- sesuaikan

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php"); exit;
}

function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// ===== Ambil semester aktif (default) =====
$semAktif = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT id, nama_semester, tahun_ajaran FROM semester WHERE status='Aktif' LIMIT 1"
));
$id_semester = isset($_GET['id_semester']) ? (int)$_GET['id_semester'] : 0;
if (!$id_semester && $semAktif) { $id_semester = (int)$semAktif['id']; }

// ===== Dropdown MK (hanya yang punya jadwal di semester terpilih) =====
$mkList = [];
if ($id_semester) {
  $q = mysqli_query($conn, "
    SELECT DISTINCT mk.id, mk.nama_mk
    FROM jadwal_praktikum jp
    JOIN matakuliah_praktikum mk ON mk.id = jp.id_mk
    WHERE jp.id_semester = ".(int)$id_semester."
    ORDER BY mk.nama_mk ASC
  ");
  while($r = mysqli_fetch_assoc($q)){ $mkList[] = $r; }
}
$id_mk = isset($_GET['id_mk']) ? (int)$_GET['id_mk'] : 0;
if (!$id_mk && !empty($mkList)) { $id_mk = (int)$mkList[0]['id']; }

// ===== Dropdown Shift berdasarkan MK + Semester =====
$shiftList = [];
if ($id_semester && $id_mk) {
  $q = mysqli_query($conn, "
    SELECT jp.id, mk.nama_mk, jp.hari, jp.jam_mulai, jp.jam_selesai,
           COALESCE(r.nama_ruangan,'-') AS nama_ruangan,
           d.nama AS nama_dosen
    FROM jadwal_praktikum jp
    JOIN matakuliah_praktikum mk ON mk.id = jp.id_mk
    JOIN dosen d ON d.id = jp.id_dosen
    LEFT JOIN ruangan r ON r.id = jp.id_ruangan
    WHERE jp.id_semester = ".(int)$id_semester." AND jp.id_mk = ".(int)$id_mk."
    ORDER BY FIELD(jp.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), jp.jam_mulai
  ");
  while($r = mysqli_fetch_assoc($q)){ $shiftList[] = $r; }
}
$id_jadwal = isset($_GET['id_jadwal']) ? (int)$_GET['id_jadwal'] : 0;
if (!$id_jadwal && !empty($shiftList)) { $id_jadwal = (int)$shiftList[0]['id']; }

// ===== Validasi shift =====
$shiftValid = null;
if ($id_jadwal) {
  $shiftValid = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT jp.id, mk.nama_mk, jp.hari, jp.jam_mulai, jp.jam_selesai,
           COALESCE(r.nama_ruangan,'-') AS nama_ruangan, d.nama AS nama_dosen
    FROM jadwal_praktikum jp
    JOIN matakuliah_praktikum mk ON mk.id = jp.id_mk
    JOIN dosen d ON d.id = jp.id_dosen
    LEFT JOIN ruangan r ON r.id = jp.id_ruangan
    WHERE jp.id = ".(int)$id_jadwal." AND jp.id_semester = ".(int)$id_semester." AND jp.id_mk = ".(int)$id_mk."
    LIMIT 1
  "));
}

// ===== Helper: mahasiswa terdaftar (shift+semester) =====
function ambilMahasiswaByJadwal(mysqli $conn, int $id_jadwal, int $id_semester): array {
  $data = [];
  $sql = "
    SELECT m.id AS id_mhs, m.nim, m.nama
    FROM pilihan_jadwal pj
    JOIN mahasiswa m ON m.id = pj.id_mahasiswa
    WHERE pj.id_jadwal = ".(int)$id_jadwal." AND pj.id_semester = ".(int)$id_semester."
    ORDER BY m.nama ASC
  ";
  if ($q = mysqli_query($conn, $sql)) {
    while($r = mysqli_fetch_assoc($q)){ $data[] = $r; }
  }
  return $data;
}

// ===== Susun tabel: kolom tanggal sesi & baris mahasiswa =====
$tanggalSesi = [];   // header (YYYY-MM-DD)
$statusMap   = [];   // [id_mhs][tgl] => 'Hadir'|'Alpha'|'Izin'
$mhsList     = [];

if ($shiftValid) {
  $resSesi = mysqli_query($conn, "
    SELECT id, DATE(mulai_at) AS tgl
    FROM absensi_sesi
    WHERE id_jadwal = ".(int)$id_jadwal." AND selesai_at IS NOT NULL
    ORDER BY mulai_at ASC
  ");
  $tglSet = []; $sesiIds = [];
  while($r = mysqli_fetch_assoc($resSesi)){
    $tglSet[$r['tgl']] = true;
    $sesiIds[] = (int)$r['id'];
  }
  $tanggalSesi = array_keys($tglSet);
  sort($tanggalSesi);

  $mhsList = ambilMahasiswaByJadwal($conn, $id_jadwal, $id_semester);

  if (!empty($sesiIds)) {
    $in = implode(',', $sesiIds);
    $qDet = mysqli_query($conn, "
      SELECT ad.id_mahasiswa, DATE(s.mulai_at) AS tgl, ad.status
      FROM absensi_detail ad
      JOIN absensi_sesi s ON s.id = ad.id_sesi
      WHERE ad.id_sesi IN ($in)
    ");
    while($d = mysqli_fetch_assoc($qDet)){
      $idm = (int)$d['id_mahasiswa'];
      $tgl = $d['tgl'];
      $status = ucfirst(strtolower(trim((string)$d['status'])));
      if (in_array($status, ['Hadir','Alpha','Izin'])) {
        $statusMap[$idm][$tgl] = $status;
      }
    }
  }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Admin - Laporan Absensi per Shift</title>

<!-- DataTables (tetap dipakai, tapi kita skin dengan CSS kita sendiri) -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<!-- jsPDF untuk Cetak -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<style>
:root{
  --bg: #f5f7fb;
  --card-bg: #ffffff;
  --text: #1f2937;
  --muted: #6b7280;
  --primary: #2c7be5;
  --primary-100:#e8f1ff;
  --success:#16a34a;
  --warn:#f97316;
  --danger:#ef4444;
  --info:#06b6d4;
  --border:#e5e7eb;
  --shadow: 0 6px 20px rgba(17,24,39,.08);
  --radius: 14px;
}

*{ box-sizing: border-box; }
html,body{ height:100%; }
body{
  margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif;
  color:var(--text); background:var(--bg);
}

.konten-utama{
  margin-left:250px; /* sesuaikan dengan sidebar-mu */
  margin-top:60px;   /* sesuaikan dengan topbar-mu   */
  padding:24px;
}
@media (max-width: 992px){
  .konten-utama{ margin-left:0; padding:16px; }
}
.h2{
  margin:0 0 14px; font-weight:700; letter-spacing:.2px;
}

.card{
  background:var(--card-bg);
  border:1px solid var(--border);
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  margin-bottom:16px;
}
.card .card-body{ padding:16px 18px; }

.row{
  display:flex; flex-wrap:wrap; gap:12px;
  align-items:flex-end;
}
.form-group{ display:flex; flex-direction:column; gap:6px; min-width: 220px; }
.label{ font-size:13px; color:var(--muted); font-weight:600; }
.select, .input, .btn{
  border:1px solid var(--border);
  border-radius:10px;
  padding:10px 12px;
  font-size:14px;
  background:#fff; color:var(--text);
  outline:none;
}
.select:focus, .input:focus{ border-color: var(--primary); box-shadow: 0 0 0 2px rgba(44,123,229,.15); }

.btn{ cursor:pointer; user-select:none; }
.btn-primary{ background:var(--primary); color:#fff; border-color:transparent; }
.btn-primary:hover{ filter:brightness(.95); }
.btn-success{ background:var(--success); color:#fff; border-color:transparent; }
.btn-success:disabled{ opacity:.5; cursor:not-allowed; }
.btn-ghost{ background:#fff; color:var(--text); }
.btn-ghost:hover{ background:#f3f4f6; }

.alert{
  border:1px solid var(--border);
  border-radius:10px;
  background:#f9fafb;
  padding:10px 12px;
  color:#0f172a;
}
.alert.info{  border-color:#67e8f9; color:#075985; margin-bottom:10px;}


.badge{
  display:inline-block; font-size:12px; padding:3px 9px; border-radius:999px;
}
.badge.primary{ background:var(--primary-100); color:#1d4ed8; border:1px solid #bfdbfe; }
.badge.ok{ background:#dcfce7; color:#065f46; border:1px solid #86efac; }
.badge.warn{ background:#fee2e2; color:#7f1d1d; border:1px solid #fecaca; }
.badge.info{ background:#e0f2fe; color:#075985; border:1px solid #bae6fd; }

.toolbar{ display:flex; justify-content:space-between; gap:12px; align-items:center; margin-bottom:8px; }

.table-wrap{ overflow:auto; border:1px solid var(--border); border-radius:12px; box-shadow:var(--shadow); background:#fff; }
table.dataTable{ width:100% !important; table-layout:fixed;}
table.dataTable thead th{
  background:#eef6ff; color:#111827; font-weight:700;
  position: sticky; top: 0; z-index: 2;
  border-bottom:1px solid var(--border);
}
table.dataTable tbody td{ border-bottom:1px solid var(--border); }
table.dataTable tbody tr:hover{ background:#fafcff; }

.dt-controls{
  display:flex; justify-content:space-between; gap:12px; align-items:center; margin:10px;;
}
.dataTables_filter input, .dataTables_length select{
  border:1px solid var(--border); border-radius:8px; padding:7px 10px; outline:none;
}
.dataTables_filter input:focus, .dataTables_length select:focus{
  border-color:var(--primary); box-shadow: 0 0 0 2px rgba(44,123,229,.15);
}

/* Mobile tweaks */
@media (max-width: 600px){
  .form-group{ min-width: 100%; }
}
</style>
</head>
<body>
<div class="konten-utama">
  <h2 class="h2">Laporan Absensi per Shift (Admin)</h2>

  <!-- Info Semester -->
  <div class="card">
    <div class="card-body">
      <?php if($id_semester):
        $sem = mysqli_fetch_assoc(mysqli_query($conn,"SELECT nama_semester, tahun_ajaran FROM semester WHERE id=".(int)$id_semester." LIMIT 1"));
      ?>
        <span class="badge primary">Semester</span>
        <b style="margin-left:6px"><?= e($sem['nama_semester'] ?? '-') ?></b>
        <span style="margin:0 8px">/</span>
        <span class="badge" style="background:#fff; border:1px solid var(--border); color:#111827">
          <?= e($sem['tahun_ajaran'] ?? '-') ?>
        </span>
      <?php else: ?>
        <div class="alert warn">Silakan pilih <b>Semester</b> terlebih dahulu.</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Filter -->
  <div class="card">
    <div class="card-body">
      <form method="get" id="form-filter" class="row">
        <div class="form-group">
          <label class="label">Semester</label>
          <select name="id_semester" id="id_semester" class="select" onchange="this.form.submit()">
            <option value="0">-- Pilih Semester --</option>
            <?php
              $qs = mysqli_query($conn,"SELECT id, nama_semester, tahun_ajaran, status FROM semester ORDER BY id DESC");
              while($s = mysqli_fetch_assoc($qs)): ?>
              <option value="<?= (int)$s['id'] ?>" <?= $id_semester===(int)$s['id']?'selected':'' ?>>
                <?= e($s['nama_semester'].' - '.$s['tahun_ajaran'].($s['status']=='Aktif'?' (Aktif)':'')) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="label">Mata Kuliah</label>
          <select name="id_mk" id="id_mk" class="select" onchange="this.form.submit()" <?= !$id_semester || empty($mkList) ? 'disabled':'' ?>>
            <?php if(empty($mkList)): ?>
              <option value="0">-- Tidak ada MK --</option>
            <?php else: foreach($mkList as $mk): ?>
              <option value="<?= (int)$mk['id'] ?>" <?= $id_mk===(int)$mk['id']?'selected':'' ?>>
                <?= e($mk['nama_mk']) ?>
              </option>
            <?php endforeach; endif; ?>
          </select>
        </div>

        <div class="form-group" style="min-width:320px">
          <label class="label">Shift</label>
          <div style="display:flex; gap:8px;">
            <select name="id_jadwal" id="id_jadwal" class="select" onchange="this.form.submit()" <?= (!$id_semester || !$id_mk || empty($shiftList)) ? 'disabled':'' ?>>
              <?php if(empty($shiftList)): ?>
                <option value="0">-- Tidak ada Shift --</option>
              <?php else: foreach($shiftList as $j):
                $jam = substr($j['jam_mulai'],0,5).' - '.substr($j['jam_selesai'],0,5);
                $label = $j['hari'].' '.$jam; ?>
                <option value="<?= (int)$j['id'] ?>" <?= $id_jadwal===(int)$j['id']?'selected':'' ?>>
                  <?= e($label) ?>
                </option>
              <?php endforeach; endif; ?>
            </select>
            <button type="button" class="btn btn-success" id="btn-cetak" <?= (!$shiftValid || empty($mhsList)) ? 'disabled' : '' ?>>Cetak</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Info Shift -->
  <?php if($shiftValid): ?>
    <div class="alert info">
     
      <?php if(empty($tanggalSesi)): ?><span style="margin-left:14px; color:#64748b">Belum ada sesi absensi tersimpan.</span><?php endif; ?>
    </div>
  <?php elseif($id_mk && empty($shiftList)): ?>
    <div class="alert warn">Tidak ada shift untuk MK terpilih pada semester ini.</div>
  <?php endif; ?>

  <!-- Tabel Rekap -->
  <div class="table-wrap">
    <table id="tabel-laporan" class="display" style="width:100%">
      <thead>
        <tr>
          <th>No</th>
          <th>NPM</th>
          <th>Nama</th>
          <?php foreach ($tanggalSesi as $tgl): ?>
            <th><?= e($tgl) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($mhsList)): ?>
          <?php $no=1; foreach($mhsList as $m): $idm=(int)$m['id_mhs']; ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><?= e($m['nim']) ?></td>
              <td><?= e($m['nama']) ?></td>
              <?php foreach ($tanggalSesi as $tgl):
                $s = $statusMap[$idm][$tgl] ?? '';
                $badge = '';
                if     ($s==='Hadir') $badge = '<span class="badge ok">Hadir</span>';
                elseif ($s==='Alpha') $badge = '<span class="badge warn">Alpha</span>';
                elseif ($s==='Izin')  $badge = '<span class="badge info">Izin</span>';
              ?>
                <td><?= $badge ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
// DataTables dengan layout yang rapi + scroll X
$(function(){
  const tbl = $('#tabel-laporan');
  if (tbl.length){
    tbl.DataTable({
      pageLength: 10,
      lengthMenu: [5,10,25,50,100],
      scrollX: true,
      autoWidth: false,
      ordering: false,
      dom:
        '<"dt-controls"lf>' + // l=length, f=filter
        't' +
        '<"dt-controls"ip>',  // i=info, p=pagination
      language: {
        emptyTable: "Tidak ada data",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
        infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
        infoFiltered: "(disaring dari _MAX_ data total)",
        lengthMenu: "Tampilkan _MENU_ data",
        search: "Cari:",
        zeroRecords: "Tidak ditemukan data yang sesuai",
        paginate: { first:"Pertama", last:"Terakhir", next:"Berikutnya", previous:"Sebelumnya" }
      }
    });
  }
});

// Cetak PDF
document.getElementById('btn-cetak')?.addEventListener('click', function(){
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ orientation:'landscape', unit:'mm', format:'a4' });
  doc.setFontSize(13);

  let judul = "Laporan Absensi";
  <?php if($shiftValid): ?>
    judul = "Laporan Absensi: <?= e($shiftValid['nama_mk']) ?> — <?= e($shiftValid['hari']) ?> <?= e(substr($shiftValid['jam_mulai'],0,5).' - '.substr($shiftValid['jam_selesai'],0,5)) ?>";
  <?php endif; ?>
  doc.text(judul, 148.5, 12, {align:"center"});

  // Header
  const headers = [];
  document.querySelectorAll('#tabel-laporan thead th').forEach(th => headers.push(th.textContent.trim()));

  // Body
  const body = [];
  document.querySelectorAll('#tabel-laporan tbody tr').forEach(tr=>{
    const row=[];
    tr.querySelectorAll('td').forEach(td=> row.push(td.textContent.trim()));
    body.push(row);
  });

  doc.autoTable({
    head: [headers],
    body,
    startY: 18,
    theme: 'grid',
    headStyles: { fillColor: [230, 241, 255], textColor: 20 },
    styles: { fontSize: 9, cellWidth: 'wrap' }
  });

  doc.save('Laporan_Absensi_Admin.pdf');
});
</script>
</body>
</html>
