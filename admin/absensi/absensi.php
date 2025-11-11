<?php
session_start();
include '../../koneksi/sidebar.php';
include '../../koneksi/koneksi.php';

if (!isset($_SESSION['role']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
  header("Location: ../index.php"); exit;
}

function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// ====== Ambil semester aktif & pilihan dari GET ======
$semAktif = mysqli_fetch_assoc(mysqli_query(
  $conn, "SELECT id, nama_semester, tahun_ajaran FROM semester WHERE status='Aktif' LIMIT 1"
));

$id_semester = isset($_GET['id_semester']) ? (int)$_GET['id_semester'] : 0;
if (!$id_semester && $semAktif) $id_semester = (int)$semAktif['id'];

$id_mk     = isset($_GET['id_mk']) ? (int)$_GET['id_mk'] : 0;
$id_jadwal = isset($_GET['id_jadwal']) ? (int)$_GET['id_jadwal'] : 0;

// ====== Susun daftar MK & shift per MK (untuk semester terpilih) ======
$mkList     = [];      // [mk_id => nama_mk]
$shiftsByMk = [];      // [mk_id => [ [id, hari, mulai, selesai, ruangan, dosen] ]]

if ($id_semester) {
  // Ambil daftar MK yang punya (atau tidak punya) shift di semester tsb
  $qmk = mysqli_query($conn, "
    SELECT DISTINCT mk.id AS mk_id, mk.nama_mk
    FROM matakuliah_praktikum mk
    LEFT JOIN jadwal_praktikum jp
      ON jp.id_mk = mk.id AND jp.id_semester = $id_semester
    ORDER BY mk.nama_mk ASC
  ");
  while($r = mysqli_fetch_assoc($qmk)){
    $mkList[(int)$r['mk_id']] = $r['nama_mk'];
  }

  // Ambil semua shift per MK (semester tsb)
  $qshift = mysqli_query($conn, "
    SELECT
      jp.id AS id_jadwal, jp.id_mk,
      jp.hari, jp.jam_mulai, jp.jam_selesai,
      COALESCE(r.nama_ruangan,'') AS nama_ruangan,
      d.nama AS nama_dosen
    FROM jadwal_praktikum jp
    JOIN dosen d ON d.id = jp.id_dosen
    LEFT JOIN ruangan r ON r.id = jp.id_ruangan
    WHERE jp.id_semester = $id_semester
    ORDER BY FIELD(jp.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'),
             jp.jam_mulai ASC
  ");
  while($s = mysqli_fetch_assoc($qshift)){
    $mkid = (int)$s['id_mk'];
    if (!isset($shiftsByMk[$mkid])) $shiftsByMk[$mkid] = [];
    $shiftsByMk[$mkid][] = [
      'id'      => (int)$s['id_jadwal'],
      'hari'    => $s['hari'],
      'mulai'   => substr($s['jam_mulai'],0,5),
      'selesai' => substr($s['jam_selesai'],0,5),
      'ruangan' => $s['nama_ruangan'],
      'dosen'   => $s['nama_dosen'],
    ];
  }
}

// ====== Default & validasi pilihan MK/Shift ======
// Default MK ke yang pertama bila belum dipilih
if ($id_semester && $id_mk === 0 && !empty($mkList)) {
  $id_mk = (int)array_key_first($mkList);
}

// Jika MK terpilih tidak punya shift, kosongkan id_jadwal
if ($id_mk && (empty($shiftsByMk[$id_mk]) || !is_array($shiftsByMk[$id_mk]))) {
  $id_jadwal = 0;
} else if ($id_mk && $id_jadwal > 0) {
  // Validasi: id_jadwal harus ada di shiftsByMk[id_mk]
  $ok = false;
  foreach($shiftsByMk[$id_mk] ?? [] as $s){
    if ($s['id'] === $id_jadwal) { $ok = true; break; }
  }
  if (!$ok) $id_jadwal = 0;
}
// Auto-pilih shift pertama bila belum ada
if ($id_mk && $id_jadwal === 0 && !empty($shiftsByMk[$id_mk])) {
  $id_jadwal = (int)$shiftsByMk[$id_mk][0]['id'];
}

// ====== Validasi shift terpilih (final check) ======
$shiftValid = null;
if ($id_semester && $id_mk && $id_jadwal) {
  $shiftValid = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT jp.id, mk.nama_mk, jp.hari, jp.jam_mulai, jp.jam_selesai,
           COALESCE(r.nama_ruangan,'') AS nama_ruangan, d.nama AS nama_dosen
    FROM jadwal_praktikum jp
    JOIN matakuliah_praktikum mk ON mk.id = jp.id_mk
    JOIN dosen d ON d.id = jp.id_dosen
    LEFT JOIN ruangan r ON r.id = jp.id_ruangan
    WHERE jp.id = $id_jadwal
      AND jp.id_semester = $id_semester
      AND jp.id_mk = $id_mk
    LIMIT 1
  "));
}

// ====== Data helper: mahasiswa terdaftar ======
function ambilMahasiswaByJadwal(mysqli $conn, int $id_jadwal, int $id_semester): array {
  $data = [];
  $sql = "
    SELECT m.id AS id_mhs, m.nim, m.nama
    FROM pilihan_jadwal pj
    JOIN mahasiswa m ON m.id = pj.id_mahasiswa
    WHERE pj.id_jadwal = ".(int)$id_jadwal."
      AND pj.id_semester = ".(int)$id_semester."
    ORDER BY m.nama ASC
  ";
  if ($q = mysqli_query($conn, $sql)) {
    while ($r = mysqli_fetch_assoc($q)) $data[] = $r;
  }
  return $data;
}

// ====== Susun tabel: tanggal sesi (selesai), status per mhs per tanggal ======
$tanggalSesi = [];   // [YYYY-MM-DD]
$statusMap   = [];   // [id_mhs][tgl] => Hadir|Alpha|Izin
$mhsList     = [];

if ($shiftValid) {
  // Ambil sesi yang sudah selesai
  $resSesi = mysqli_query($conn, "
    SELECT id, DATE(mulai_at) AS tgl
    FROM absensi_sesi
    WHERE id_jadwal = $id_jadwal AND selesai_at IS NOT NULL
    ORDER BY mulai_at ASC
  ");
  $tglSet = []; $sesiIds = [];
  while($r = mysqli_fetch_assoc($resSesi)){
    $tglSet[$r['tgl']] = true;
    $sesiIds[] = (int)$r['id'];
  }
  $tanggalSesi = array_keys($tglSet);
  sort($tanggalSesi);

  // Mahasiswa
  $mhsList = ambilMahasiswaByJadwal($conn, $id_jadwal, $id_semester);

  // Detail status
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
      if (in_array($status, ['Hadir','Alpha','Izin'], true)) {
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

  <!-- DataTables & jQuery -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

  <!-- FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- jsPDF -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

  <style>
    body { 
      font-family: Arial, sans-serif; 
      background:#f9f9f9; }

    .konten-utama { 
      margin-left:250px; 
      margin-top:60px; 
      padding:30px; 
      min-height:calc(100vh - 60px); 
      background:#f9f9f9; }
    .konten-utama h2 {
      margin-bottom:10px; 
      color:#333; }

    .filter-panel{
      background:#fff; 
      border:1px solid #e5e7eb; 
      border-radius:12px;
      padding:14px 16px; 
      box-shadow:0 2px 6px rgba(0,0,0,.06);
      display:flex; 
      gap:12px; 
      flex-wrap:wrap; 
      align-items:flex-end; 
      margin:10px 0 14px;
    }

    .field{ 
      display:flex; 
      flex-direction:column; 
      gap:6px; }

    .field label{ 
      font-weight:700; 
      color:#111827; 
      font-size:14px; }

    .select-styled{
      appearance:none;
      -webkit-appearance:none; 
      -moz-appearance:none;
      background:#fff; 
      border:1px solid #cbd5e1; 
      border-radius:10px;
      padding:10px 42px 10px 12px; 
      font-size:14px; 
      line-height:1.2; 
      min-width:240px; 
      outline:none;
    }
    .select-styled:hover{ 
      border-color:#94a3b8; }
    .select-styled:focus{ 
      border-color:#60a5fa; 
      box-shadow:0 0 0 4px rgba(96,165,250,.2); }

    .tombol-umum { 
      border:none; 
      border-radius:10px; 
      cursor:pointer; 
      color:white; 
      font-size:13px; 
      transition:.3s; 
      padding:10px 16px; 
      display:inline-flex; 
      gap:8px; 
      align-items:center; }

    .tombol-umum:hover { 
      opacity:.9; }
    .tombol-cetak { 
      background:#28a745; }

    .kotak-info{ 
      padding:10px 12px; 
      border-radius:8px; 
      margin:8px 0; 
      background:#fff; 
      border:1px solid #e5e7eb; }
    .info-berhasil{ 
      color:#333; }
    .info-peringatan{ 
      color:#333; }

    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select { 
      padding:6px 10px; 
      border-radius:5px;
      border:1px solid #ccc; 
      font-size:14px; 
      margin-bottom:5px; }
  
    .tabel-laporan-absensi { 
      width:100%; 
      border-collapse:collapse; 
      background:#fff; 
      border-radius:10px; 
      overflow:hidden; 
      box-shadow:0 2px 6px rgba(0,0,0,0.1); 
      table-layout:fixed; }

    .tabel-laporan-absensi th { 
      background:#00AEEF; 
      color:#333; 
      text-align:left; 
      padding:12px 15px; 
      white-space:nowrap; }

    .tabel-laporan-absensi td { 
      padding:12px 15px;
       border-bottom:1px solid #ddd; }

    @media screen and (max-width: 768px) {
      .konten-utama { 
        margin-left:0; 
        padding:20px; 
        text-align:center; }

      .filter-panel { 
        flex-direction:column; 
        align-items:stretch; 
        gap:10px; }

      .field{ 
        width:100%; }

      .select-styled{
         min-width:unset; 
         width:100%; }

      .tombol-umum{ 
        width:100%; 
        justify-content:center; }

      .tabel-laporan-absensi, thead, tbody, th, td, tr { 
        display:block; }

      thead tr { 
        display:none; }

      tr { 
        margin-bottom:15px; 
        border-bottom:2px solid #000; }
      td { 
        text-align:right; 
        padding-left:50%; 
        position:relative; }

      td::before { 
        content: attr(data-label); 
        position:absolute; 
        left:15px; 
        width:45%; 
        font-weight:bold; 
        text-align:left; }
    }
  </style>
</head>
<body>
  <div class="konten-utama">
    <h2>Laporan Absensi per Shift</h2>

    <?php if(!$id_semester && !$semAktif): ?>
      <div class="kotak-info info-peringatan">Belum ada <b>Semester</b> berstatus <b>Aktif</b>. Silakan pilih semester di bawah jika tersedia.</div>
    <?php elseif($id_semester): ?>
      <?php
        $semShow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_semester, tahun_ajaran, status FROM semester WHERE id=$id_semester LIMIT 1"));
      ?>
      <div class="kotak-info info-berhasil">
        Semester: <b><?= e($semShow['nama_semester'] ?? '-') ?></b><b>/</b><b><?= e($semShow['tahun_ajaran'] ?? '-') ?></b>
        <?= (isset($semShow['status']) && $semShow['status']==='Aktif') ? '<b>(Aktif)</b>' : '' ?>
      </div>
    <?php endif; ?>

    <!-- Panel filter -->
    <form method="get" id="form-filter" class="filter-panel">
      <div class="field">
        <label for="id_semester"><b>Pilih Semester</b></label>
        <select name="id_semester" id="id_semester" class="select-styled" onchange="this.form.submit()">
          <option value="0">-- Pilih Semester --</option>
          <?php
            $qs = mysqli_query($conn, "SELECT id, nama_semester, tahun_ajaran, status FROM semester ORDER BY id DESC");
            while ($s = mysqli_fetch_assoc($qs)): ?>
              <option value="<?= (int)$s['id'] ?>" <?= $id_semester === (int)$s['id'] ? 'selected' : '' ?>>
                <?= e($s['nama_semester'].' - '.$s['tahun_ajaran'].($s['status']=='Aktif'?' (Aktif)':'')) ?>
              </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="field">
        <label for="id_mk"><b>Pilih Mata Kuliah</b></label>
        <select name="id_mk" id="id_mk" class="select-styled" <?= (!$id_semester || empty($mkList)) ? 'disabled' : '' ?>>
          <?php foreach($mkList as $mkid => $nmmk): ?>
            <option value="<?= (int)$mkid ?>" <?= $id_mk===(int)$mkid ? 'selected' : '' ?>><?= e($nmmk) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="id_jadwal"><b>Pilih Shift</b></label>
        <select name="id_jadwal" id="id_jadwal" class="select-styled" <?= (!$id_semester || !$id_mk || empty($shiftsByMk[$id_mk])) ? 'disabled' : '' ?>>
          <?php foreach($shiftsByMk[$id_mk] ?? [] as $s):
            $jam   = $s['mulai'].' - '.$s['selesai'];
            $label = $s['hari'].' '.$jam;
            $sel   = ($id_jadwal === (int)$s['id']) ? 'selected' : '';
          ?>
            <option value="<?= (int)$s['id'] ?>" <?= $sel ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <button type="button" class="tombol-umum tombol-cetak" id="btn-cetak" <?= (!$shiftValid || empty($mhsList)) ? 'disabled' : '' ?>>
        <i class="fa-solid fa-print"></i> Cetak
      </button>
    </form>

    <!-- Ringkasan shift -->
    <?php if($shiftValid): ?>
      <div class="kotak-info info-berhasil">
        <b>Shift:</b> <?= e($shiftValid['nama_mk']) ?>, <?= e($shiftValid['hari']) ?>
        <?= e(substr($shiftValid['jam_mulai'],0,5).' - '.substr($shiftValid['jam_selesai'],0,5)) ?>
        <?php if(!empty($shiftValid['nama_ruangan'])): ?> | Ruangan: <?= e($shiftValid['nama_ruangan']) ?><?php endif; ?>
        | Dosen: <?= e($shiftValid['nama_dosen']) ?>.
        <?php if(empty($tanggalSesi)): ?>&nbsp;Belum ada sesi absensi tersimpan/selesai.<?php endif; ?>
      </div>
    <?php elseif($id_semester && empty($mkList)): ?>
      <div class="kotak-info info-peringatan">Belum ada mata kuliah pada semester ini.</div>
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
        <?php if (!empty($mhsList)): $no=1; foreach($mhsList as $m): $idm=(int)$m['id_mhs']; ?>
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
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <script>
    // ====== Auto-submit & defaulting ala acuan ======
    document.getElementById('id_semester')?.addEventListener('change', ()=>{
      // Biarkan server reset default MK & shift
      document.getElementById('id_mk')?.removeAttribute('name');
      document.getElementById('id_jadwal')?.removeAttribute('name');
    });

    document.getElementById('id_mk')?.addEventListener('change', ()=>{
      // Reset shift agar server memilih default shift untuk MK baru
      document.getElementById('id_jadwal')?.removeAttribute('name');
      document.getElementById('form-filter').submit();
    });

    document.getElementById('id_jadwal')?.addEventListener('change', ()=>{
      document.getElementById('form-filter').submit();
    });

    // ====== DataTables ======
    $(document).ready(function () {
      const dt = $('#tabel-laporan').DataTable({
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

      // Set data-label untuk mode mobile setelah draw
      function setDataLabels(){
        const headers = [];
        $('#tabel-laporan thead th').each(function(){ headers.push($(this).text().trim()); });
        $('#tabel-laporan tbody tr').each(function(){
          $(this).children('td').each(function(i){ $(this).attr('data-label', headers[i] || ''); });
        });
      }
      setDataLabels();
      dt.on('draw', setDataLabels);
    });

    // ====== Cetak PDF (jsPDF + autotable) ======
    document.getElementById('btn-cetak')?.addEventListener('click', function(){
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF({ orientation:'landscape', unit:'mm', format:'a4' });
      doc.setFontSize(13);

      let judul = "Laporan Absensi";
      <?php if($shiftValid): ?>
        judul = "Laporan Absensi Praktikum: <?= e($shiftValid['nama_mk']) ?>, <?= e($shiftValid['hari']) ?> <?= e(substr($shiftValid['jam_mulai'],0,5).' - '.substr($shiftValid['jam_selesai'],0,5)) ?> (<?= e($shiftValid['nama_dosen']) ?>)";
      <?php endif; ?>
      doc.text(judul, 148.5, 12, {align:"center"});

      const headers = [];
      document.querySelectorAll('#tabel-laporan thead th').forEach(th => headers.push(th.textContent.trim()));
      const body = [];
      document.querySelectorAll('#tabel-laporan tbody tr').forEach(tr=>{
        const row=[]; tr.querySelectorAll('td').forEach(td=> row.push(td.textContent.trim())); body.push(row);
      });

      doc.autoTable({
        head: [headers],
        body,
        startY: 18,
        theme: 'grid',
        headStyles: { fillColor: [139,201,255], textColor: 20 },
        styles: { fontSize: 9, cellWidth: 'wrap' }
      });

      doc.save('Laporan_Absensi_Admin.pdf');
    });
  </script>
</body>
</html>
