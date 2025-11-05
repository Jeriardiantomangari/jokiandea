<?php
// Inisialisasi sesi & koneksi
session_start();
include '../../koneksi/sidebardosen.php';
include '../../koneksi/koneksi.php';

// Validasi role (dosen)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dosen') {
  header("Location: ../login.php"); exit;
}

// Helper escape HTML
function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// Semester aktif & identitas dosen
$id_dosen = (int)($_SESSION['id_user'] ?? 0);
$semAktif = mysqli_fetch_assoc(
  mysqli_query($conn, "SELECT id, nama_semester, tahun_ajaran FROM semester WHERE status='Aktif' LIMIT 1")
);
$id_semester_aktif = $semAktif['id'] ?? null;

// Daftar shift dosen (semester aktif)
$jadwalRes = [];
if ($id_semester_aktif && $id_dosen) {
  $qJ = mysqli_query($conn, "
    SELECT jp.id, mk.nama_mk, jp.hari, jp.jam_mulai, jp.jam_selesai, r.nama_ruangan
    FROM jadwal_praktikum jp
    JOIN matakuliah_praktikum mk ON mk.id = jp.id_mk
    LEFT JOIN ruangan r ON r.id = jp.id_ruangan
    WHERE jp.id_semester = ".(int)$id_semester_aktif." AND jp.id_dosen = ".(int)$id_dosen."
    ORDER BY FIELD(jp.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), jp.jam_mulai
  ");
  while($row = mysqli_fetch_assoc($qJ)){ $jadwalRes[] = $row; }
}

// Pilihan shift (GET) + default ke shift pertama
$id_jadwal_pilih = isset($_GET['id_jadwal']) ? (int)$_GET['id_jadwal'] : 0;
if (!$id_jadwal_pilih && !empty($jadwalRes)) $id_jadwal_pilih = (int)$jadwalRes[0]['id'];

// Validasi shift milik dosen di semester aktif
$shiftValid = null;
if ($id_jadwal_pilih) {
  $shiftValid = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT jp.id, mk.nama_mk, jp.hari, jp.jam_mulai, jp.jam_selesai
    FROM jadwal_praktikum jp
    JOIN matakuliah_praktikum mk ON mk.id = jp.id_mk
    WHERE jp.id = $id_jadwal_pilih AND jp.id_dosen = $id_dosen AND jp.id_semester = ".(int)$id_semester_aktif."
    LIMIT 1
  "));
}

// Fungsi ambil mahasiswa terdaftar di shift
function ambilMahasiswaByJadwal(mysqli $conn, int $id_jadwal, ?int $id_semester): array {
  $data = [];
  $sql = "
    SELECT m.id AS id_mhs, m.nim, m.nama
    FROM pilihan_jadwal pj
    JOIN mahasiswa m ON m.id = pj.id_mahasiswa
    WHERE pj.id_jadwal = ".(int)$id_jadwal."
      ".($id_semester ? "AND pj.id_semester = ".(int)$id_semester : "")."
    ORDER BY m.nama ASC
  ";
  if ($q = mysqli_query($conn, $sql)) {
    while($r = mysqli_fetch_assoc($q)){ $data[] = $r; }
  }
  return $data;
}

// Kumpulan tanggal sesi & status
$tanggalSesi = [];   // Daftar tanggal (YYYY-MM-DD) untuk header tabel
$statusMap   = [];   // Peta status: [id_mhs][tanggal] => 'Hadir'/'Alpha'/'Izin'
$mhsList     = [];   // Daftar mahasiswa pada shift


// Isi data jika shift valid
if ($shiftValid) {
  // Tanggal sesi selesai pada shift
  $resSesi = mysqli_query($conn, "
    SELECT id, DATE(mulai_at) AS tgl
    FROM absensi_sesi
    WHERE id_jadwal = $id_jadwal_pilih AND id_dosen = $id_dosen AND selesai_at IS NOT NULL
    ORDER BY mulai_at ASC
  ");
  $tglSet = []; $sesiIds = [];
  while($r = mysqli_fetch_assoc($resSesi)){
    $tglSet[$r['tgl']] = true;
    $sesiIds[] = (int)$r['id'];
  }
  $tanggalSesi = array_keys($tglSet);
  sort($tanggalSesi);

  // Mahasiswa terdaftar
  $mhsList = ambilMahasiswaByJadwal($conn, $id_jadwal_pilih, $id_semester_aktif);

  // Status hadir/alpha/izin per mahasiswa per tanggal
  if ($sesiIds) {
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
      if (in_array($status, ['Hadir','Alpha','Izin'])) $statusMap[$idm][$tgl] = $status;
    }
  }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Laporan Absensi</title>

<!-- jQuery & DataTables (kelas bawaan jangan diubah) -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- Ikon & jsPDF -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<style>
/* Tata letak utama */
.area-utama { margin-left:250px; margin-top:60px; padding:30px; min-height:calc(100vh - 60px); background:#f9f9f9; font-family:Arial,sans-serif; }
.area-utama h2 { margin-bottom:10px; color:#333; }

/* Tombol */
.tombol-umum { border:none; border-radius:5px; cursor:pointer; color:white; font-size:10px; transition:0.3s; }
.tombol-umum:hover { opacity:0.85; }
.tombol-cetak { background:#28a745; padding:8px 15px; }

/* Komponen DataTables (biarkan kelasnya) */
.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select { padding:6px 10px; border-radius:5px; border:1px solid #ccc; font-size:14px; margin-bottom:5px; }

/* Info */
.kotak-info{ padding:10px 12px; border-radius:8px; margin:8px 0; }
.info-berhasil{ background:#ecfeff; border:1px solid #67e8f9; color:#075985; }
.info-peringatan{ background:#fff7ed; border:1px solid #fdba74; color:#7c2d12; }

/* Tabel */
.tabel-laporan-absensi { width:100%; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.1); table-layout:fixed; }
.tabel-laporan-absensi th { background:#8bc9ff; color:#333; text-align:left; padding:12px 15px; white-space:nowrap; }
.tabel-laporan-absensi td { padding:12px 15px; border-bottom:1px solid #ddd; }

/* Responsif */
@media screen and (max-width: 768px) {
  .area-utama { margin-left:0; padding:20px; text-align:center; }
  .area-utama h2 { text-align:center; }
  .tabel-laporan-absensi, thead, tbody, th, td, tr { display:block; }
  thead tr { display:none; }
  tr { margin-bottom:15px; border-bottom:2px solid #000; }
  td { text-align:right; padding-left:50%; position:relative; }
  td::before { content: attr(data-label); position:absolute; left:15px; width:45%; font-weight:bold; text-align:left; }
}
</style>
</head>
<body>
<div class="area-utama">
  <h2>Laporan Absensi</h2>

  <?php if(!$id_semester_aktif): ?>
    <div class="kotak-info info-peringatan">Belum ada <b>Semester</b> berstatus <b>Aktif</b>.</div>
  <?php else: ?>
    <div class="kotak-info info-berhasil">Semester: <b><?= e($semAktif['nama_semester']) ?></b><b>/</b><b><?= e($semAktif['tahun_ajaran']) ?></b></div>
  <?php endif; ?>

  <!-- Pilih shift + tombol cetak -->
  <form method="get" id="form-shift" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin:10px 0 14px;">
    <label for="id_jadwal"><b>Pilih Shift</b></label>
    <select name="id_jadwal" id="id_jadwal" <?= empty($jadwalRes) ? 'disabled' : '' ?> required>
      <?php foreach($jadwalRes as $j):
        $jam = substr($j['jam_mulai'],0,5).' - '.substr($j['jam_selesai'],0,5);
        $label = $j['hari'].' '.$jam;
      ?>
        <option value="<?= (int)$j['id'] ?>" <?= $id_jadwal_pilih===(int)$j['id'] ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>

    <button type="button" class="tombol-umum tombol-cetak" id="btn-cetak" <?= (!$shiftValid || empty($mhsList)) ? 'disabled' : '' ?>>
      <i class="fa-solid fa-print"></i> Cetak
    </button>
  </form>

  <!-- Ringkasan shift -->
  <?php if($shiftValid): ?>
    <div class="kotak-info info-berhasil">
      <b>Shift:</b> <?= e($shiftValid['nama_mk']) ?>, <?= e($shiftValid['hari']) ?> <?= e(substr($shiftValid['jam_mulai'],0,5).' - '.substr($shiftValid['jam_selesai'],0,5)) ?>.
      <?php if(empty($tanggalSesi)): ?>&nbsp;Belum ada sesi absensi tersimpan.<?php endif; ?>
    </div>
  <?php elseif(empty($jadwalRes)): ?>
    <div class="kotak-info info-peringatan">Belum ada jadwal untuk akun Anda pada semester ini.</div>
  <?php endif; ?>

  <!-- Tabel -->
  <table id="tabel-laporan" class="tabel-laporan-absensi">
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
            <td data-label="No"><?= $no++ ?></td>
            <td data-label="NPM"><?= e($m['nim']) ?></td>
            <td data-label="Nama"><?= e($m['nama']) ?></td>
            <?php foreach ($tanggalSesi as $tgl):
              $s = $statusMap[$idm][$tgl] ?? '';
            ?>
              <td data-label="<?= e($tgl) ?>"><?= e($s) ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
// Auto-submit saat ganti shift
document.getElementById('id_jadwal')?.addEventListener('change', ()=> {
  document.getElementById('form-shift').submit();
});

// DataTables
$(document).ready(function () {
  $('#tabel-laporan').DataTable({
    pageLength: 10,
    lengthMenu: [5, 10, 25, 50],
    language: {
      emptyTable: "Tidak ada data tersedia",
      info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
      infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
      infoFiltered: "(disaring dari _MAX_ data total)",
      lengthMenu: "Tampilkan _MENU_ data",
      loadingRecords: "Memuat...",
      processing: "Sedang diproses...",
      search: "Cari:",
      zeroRecords: "Tidak ditemukan data yang sesuai",
      paginate: { first: "Pertama", last: "Terakhir", next: "Berikutnya", previous: "Sebelumnya" }
    }
  });
});

// Cetak PDF dari tabel
document.getElementById('btn-cetak')?.addEventListener('click', function(){
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ orientation:'landscape', unit:'mm', format:'a4' });
  doc.setFontSize(13);

  let judul = "Laporan Absensi";
  <?php if($shiftValid): ?>
    judul = "Laporan Absensi Praktikum: <?= e($shiftValid['nama_mk']) ?>, <?= e($shiftValid['hari']) ?> <?= e(substr($shiftValid['jam_mulai'],0,5).' - '.substr($shiftValid['jam_selesai'],0,5)) ?>";
  <?php endif; ?>
  doc.text(judul, 148.5, 12, {align:"center"});

  // Ambil data tabel
  const headers = [];
  document.querySelectorAll('#tabel-laporan thead th').forEach(th => headers.push(th.textContent.trim()));
  const body = [];
  document.querySelectorAll('#tabel-laporan tbody tr').forEach(tr=>{
    const row=[]; tr.querySelectorAll('td').forEach(td=> row.push(td.textContent.trim())); body.push(row);
  });

  // Render PDF
  doc.autoTable({
    head: [headers],
    body,
    startY: 18,
    theme: 'grid',
    headStyles: { fillColor: [139,201,255], textColor: 20 },
    styles: { fontSize: 9, cellWidth: 'wrap' }
  });

  doc.save('Laporan_Absensi.pdf');
});
</script>
</body>
</html>
