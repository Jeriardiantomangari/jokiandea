<?php
// admin/absensi/laporan_shift.php
session_start();
include '../../koneksi/sidebar.php';   // <-- sesuaikan
include '../../koneksi/koneksi.php';   // <-- sesuaikan

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

// Tidak auto-pilih apapun saat awal
$id_mk     = isset($_GET['id_mk']) ? (int)$_GET['id_mk'] : 0;
$id_jadwal = isset($_GET['id_jadwal']) ? (int)$_GET['id_jadwal'] : 0;

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

// hanya isi data jika MK & Shift sudah dipilih dan valid
if ($id_semester && $id_mk && $id_jadwal && $shiftValid) {
  // ambil kolom tanggal dari absensi_detail (tanpa syarat selesai_at), khusus jadwal terpilih
  $resTgl = mysqli_query($conn, "
    SELECT DISTINCT DATE(s.mulai_at) AS tgl
    FROM absensi_detail ad
    JOIN absensi_sesi s ON s.id = ad.id_sesi
    WHERE s.id_jadwal = ".(int)$id_jadwal."
    ORDER BY tgl ASC
  ");
  while($r = mysqli_fetch_assoc($resTgl)){
    $tanggalSesi[] = $r['tgl'];
  }

  // Ambil list mahasiswa terdaftar (No/NPM/Nama tetap muncul setelah shift dipilih)
  $mhsList = ambilMahasiswaByJadwal($conn, $id_jadwal, $id_semester);

  // Peta status per (mahasiswa, tanggal)
  $qDet = mysqli_query($conn, "
    SELECT ad.id_mahasiswa, DATE(s.mulai_at) AS tgl, ad.status
    FROM absensi_detail ad
    JOIN absensi_sesi s ON s.id = ad.id_sesi
    WHERE s.id_jadwal = ".(int)$id_jadwal."
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
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Admin - Laporan Absensi per Shift</title>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<style>
/* Tidak pakai variabel CSS, semua warna langsung */
*{ box-sizing: border-box; }
html,body{ height:100%; }
body{
  margin:0;
  font-family: system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial,"Noto Sans","Liberation Sans",sans-serif;
  color:#1f2937;                 /* teks */
  background:#f5f7fb;           /* latar */
}

/* Tata letak utama */
.konten-utama{ margin-left:250px; margin-top:60px; padding:24px; }
@media (max-width: 992px){ .konten-utama{ margin-left:0; padding:16px; } }
.h2{ margin:0 0 14px; font-weight:700; letter-spacing:.2px; }

/* Kartu */
.kartu{
  background:#ffffff;
  border:1px solid #e5e7eb;
  border-radius:14px;
  box-shadow:0 6px 20px rgba(17,24,39,.08);
  margin-bottom:16px;
}
.kartu .badan-kartu{ padding:16px 18px; }

/* Form */
.baris{ display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; }
.grup-form{ display:flex; flex-direction:column; gap:6px; min-width: 220px; }
.label{ font-size:13px; color:#6b7280; font-weight:600; }

.pilih, .masukan, .tombol{
  border:1px solid #e5e7eb;
  border-radius:10px;
  padding:10px 12px;
  font-size:14px;
  background:#ffffff;
  color:#1f2937;
  outline:none;
}
.pilih:focus, .masukan:focus{
  border-color:#2c7be5;
  box-shadow:0 0 0 2px rgba(44,123,229,.15);
}

.tombol{ cursor:pointer; user-select:none; }
.tombol-sukses{ background:#16a34a; color:#ffffff; border-color:transparent; }
.tombol-sukses:disabled{ opacity:.5; cursor:not-allowed; }

/* Kotak pesan */
.kotak-pesan{
  border:1px solid #e5e7eb;
  border-radius:10px;
  background:#f9fafb;
  padding:10px 12px;
  color:#0f172a;
}
.kotak-pesan.info{  border-color:#67e8f9; color:#075985; margin-bottom:10px; }
.kotak-pesan.peringatan{ border-color:#fecaca; color:#7f1d1d; background:#fff; }

/* Lencana */
.lencana{ display:inline-block; font-size:12px; padding:3px 9px; border-radius:999px; }
.lencana.utama{ background:#e8f1ff; color:#1d4ed8; border:1px solid #bfdbfe; }
.lencana.ok{ background:#dcfce7; color:#065f46; border:1px solid #86efac; }
.lencana.peringatan{ background:#fee2e2; color:#7f1d1d; border:1px solid #fecaca; }
.lencana.info{ background:#e0f2fe; color:#075985; border:1px solid #bae6fd; }

/* ====== Tabel (HANYA bagian ini diubah) ====== */
.pembungkus-tabel{
  overflow:auto;
  border:1px solid #e5e7eb;
  border-radius:12px;
  box-shadow:0 6px 20px rgba(17,24,39,.08);
  background:#ffffff;
}
table.dataTable{ width:100% !important; table-layout:fixed;}
/* Header th jadi biru seperti permintaan */
table.dataTable thead th{
  background:#00AEEF;           /* biru header */
  color:#333;                   /* teks header */
  font-weight:700;
  position: sticky; top: 0; z-index: 2;
  border-bottom:1px solid #e5e7eb;
  white-space:nowrap;
}
table.dataTable tbody td{ border-bottom:1px solid #e5e7eb; }
table.dataTable tbody tr:hover{ background:#fafcff; }

/* Kontrol DataTables */
.kontrol-dt{
  display:flex; justify-content:space-between; gap:12px; align-items:center; margin:10px;
}
.dataTables_filter input,.dataTables_length select{
  border:1px solid #e5e7eb; border-radius:8px; padding:7px 10px; outline:none;
}
.dataTables_filter input:focus,.dataTables_length select:focus{
  border-color:#2c7be5; box-shadow:0 0 0 2px rgba(44,123,229,.15);
}

/* ====== Responsif dengan PENANDA data-label (hanya untuk tabel) ====== */
@media screen and (max-width: 768px){
  #tabel-laporan, #tabel-laporan thead, #tabel-laporan tbody, #tabel-laporan th, #tabel-laporan td, #tabel-laporan tr { display:block; }
  #tabel-laporan thead tr{ display:none; }
  #tabel-laporan tr{ margin-bottom:15px; border-bottom:2px solid #000; }
  #tabel-laporan td{
    text-align:right; padding-left:50%; position:relative; border-right:none;
  }
  #tabel-laporan td::before{
    content: attr(data-label);
    position:absolute; left:15px; width:45%; font-weight:bold; text-align:left;
  }
}

/* Mobile tweaks (lainnya tetap) */
@media (max-width: 600px){
  .grup-form{ min-width: 100%; }
}
</style>
</head>
<body>
<div class="konten-utama">
  <h2 class="h2">Laporan Absensi per Shift (Admin)</h2>

  <!-- Info Semester -->
  <div class="kartu">
    <div class="badan-kartu">
      <?php if($id_semester):
        $sem = mysqli_fetch_assoc(mysqli_query($conn,"SELECT nama_semester, tahun_ajaran FROM semester WHERE id=".(int)$id_semester." LIMIT 1"));
      ?>
        <span class="lencana utama">Semester</span>
        <b style="margin-left:6px"><?= e($sem['nama_semester'] ?? '-') ?></b>
        <span style="margin:0 8px">/</span>
        <span class="lencana" style="background:#ffffff; border:1px solid #e5e7eb; color:#111827">
          <?= e($sem['tahun_ajaran'] ?? '-') ?>
        </span>
      <?php else: ?>
        <div class="kotak-pesan peringatan">Silakan pilih <b>Semester</b> terlebih dahulu.</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Filter -->
  <div class="kartu">
    <div class="badan-kartu">
      <form method="get" id="form-filter" class="baris">
        <div class="grup-form">
          <label class="label">Semester</label>
          <select name="id_semester" id="id_semester" class="pilih" onchange="this.form.submit()">
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

        <div class="grup-form">
          <label class="label">Mata Kuliah</label>
          <select name="id_mk" id="id_mk" class="pilih" onchange="this.form.submit()" <?= (!$id_semester || empty($mkList)) ? 'disabled':'' ?>>
            <option value="0" <?= $id_mk===0 ? 'selected':'' ?>>-- Pilih MK --</option>
            <?php if(!empty($mkList)): foreach($mkList as $mk): ?>
              <option value="<?= (int)$mk['id'] ?>" <?= $id_mk===(int)$mk['id']?'selected':'' ?>>
                <?= e($mk['nama_mk']) ?>
              </option>
            <?php endforeach; endif; ?>
          </select>
        </div>

        <div class="grup-form" style="min-width:320px">
          <label class="label">Shift</label>
          <div style="display:flex; gap:8px;">
            <select name="id_jadwal" id="id_jadwal" class="pilih" onchange="this.form.submit()" <?= (!$id_semester || !$id_mk || empty($shiftList)) ? 'disabled':'' ?>>
              <option value="0" <?= $id_jadwal===0 ? 'selected':'' ?>>-- Pilih Shift --</option>
              <?php if(!empty($shiftList)): foreach($shiftList as $j):
                $jam = substr($j['jam_mulai'],0,5).' - '.substr($j['jam_selesai'],0,5);
                $label = $j['hari'].' '.$jam; ?>
                <option value="<?= (int)$j['id'] ?>" <?= $id_jadwal===(int)$j['id']?'selected':'' ?>>
                  <?= e($label) ?>
                </option>
              <?php endforeach; endif; ?>
            </select>
            <button type="button" class="tombol tombol-sukses" id="btn-cetak" <?= (!$shiftValid || empty($mhsList)) ? 'disabled' : '' ?>>Cetak</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Info Shift -->
  <?php if($shiftValid): ?>
    <div class="kotak-pesan info">
      <?php if(empty($tanggalSesi)): ?><span style="margin-left:14px; color:#64748b">Belum ada sesi absensi tersimpan.</span><?php endif; ?>
    </div>
  <?php elseif($id_mk && empty($shiftList)): ?>
    <div class="kotak-pesan peringatan">Tidak ada shift untuk MK terpilih pada semester ini.</div>
  <?php endif; ?>

  <!-- Tabel Rekap -->
  <div class="pembungkus-tabel">
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
              <!-- Tambah penanda data-label pada setiap sel -->
              <td data-label="No"><?= $no++ ?></td>
              <td data-label="NPM"><?= e($m['nim']) ?></td>
              <td data-label="Nama"><?= e($m['nama']) ?></td>
              <?php foreach ($tanggalSesi as $tgl):
                $s = $statusMap[$idm][$tgl] ?? '';
                $badge = '';
                if     ($s==='Hadir') $badge = '<span class="lencana ok">Hadir</span>';
                elseif ($s==='Alpha') $badge = '<span class="lencana peringatan">Alpha</span>';
                elseif ($s==='Izin')  $badge = '<span class="lencana info">Izin</span>';
              ?>
                <td data-label="<?= e($tgl) ?>"><?= $badge ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <!-- Biarkan kosong saat awal; DataTables akan tampil dengan "Tidak ada data" -->
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
// DataTables + scroll X
$(function(){
  const tbl = $('#tabel-laporan');
  if (tbl.length){
    tbl.DataTable({
      pageLength: 10,
      lengthMenu: [5,10,25,50,100],
      scrollX: true,
      autoWidth: false,
      ordering: false,
      dom: '<"kontrol-dt"lf>t<"kontrol-dt"ip>', // class Indonesia
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
