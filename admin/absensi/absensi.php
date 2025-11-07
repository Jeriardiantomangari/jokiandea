<?php
// admin/absensi/laporan_shift.php
session_start();
include '../../koneksi/sidebar.php';   // sesuaikan jika perlu
include '../../koneksi/koneksi.php';   // sesuaikan jika perlu

// Cek role Admin (aman terhadap huruf besar-kecil)
if (!isset($_SESSION['role']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
  header("Location: ../login.php");
  exit;
}

// Helper aman untuk echo
function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// ===== Ambil semester aktif (default) =====
$semAktif = mysqli_fetch_assoc(mysqli_query(
  $conn,
  "SELECT id, nama_semester, tahun_ajaran FROM semester WHERE status='Aktif' LIMIT 1"
));
$id_semester = isset($_GET['id_semester']) ? (int)$_GET['id_semester'] : 0;
if (!$id_semester && $semAktif) { $id_semester = (int)$semAktif['id']; }

// Tidak auto-pilih apapun saat awal
$id_mk     = isset($_GET['id_mk']) ? (int)$_GET['id_mk'] : 0;
$id_jadwal = isset($_GET['id_jadwal']) ? (int)$_GET['id_jadwal'] : 0;

/* =========================================================================
   Dropdown MK (TAMPILKAN SEMUA MK) + info jumlah shift di semester terpilih
   ========================================================================= */
$mkList = [];
if ($id_semester) {
  $q = mysqli_query($conn, "
    SELECT
      mk.id,
      mk.nama_mk,
      COUNT(jp.id) AS jml_shift
    FROM matakuliah_praktikum mk
    LEFT JOIN jadwal_praktikum jp
      ON jp.id_mk = mk.id
     AND jp.id_semester = ".(int)$id_semester."
    GROUP BY mk.id, mk.nama_mk
    ORDER BY mk.nama_mk ASC
  ");
  while ($r = mysqli_fetch_assoc($q)) { $mkList[] = $r; }
}

/* =========================================================================
   Dropdown Shift berdasarkan MK + Semester (tetap sama)
   ========================================================================= */
$shiftList = [];
if ($id_semester && $id_mk) {
  $q = mysqli_query($conn, "
    SELECT
      jp.id,
      mk.nama_mk,
      jp.hari,
      jp.jam_mulai,
      jp.jam_selesai,
      COALESCE(r.nama_ruangan,'-') AS nama_ruangan,
      d.nama AS nama_dosen
    FROM jadwal_praktikum jp
    JOIN matakuliah_praktikum mk ON mk.id = jp.id_mk
    JOIN dosen d ON d.id = jp.id_dosen
    LEFT JOIN ruangan r ON r.id = jp.id_ruangan
    WHERE jp.id_semester = ".(int)$id_semester." AND jp.id_mk = ".(int)$id_mk."
    ORDER BY FIELD(jp.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), jp.jam_mulai
  ");
  while ($r = mysqli_fetch_assoc($q)) { $shiftList[] = $r; }
}

/* =========================================================================
   Validasi shift terpilih
   ========================================================================= */
$shiftValid = null;
if ($id_jadwal) {
  $shiftValid = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
      jp.id,
      mk.nama_mk,
      jp.hari,
      jp.jam_mulai,
      jp.jam_selesai,
      COALESCE(r.nama_ruangan,'-') AS nama_ruangan,
      d.nama AS nama_dosen
    FROM jadwal_praktikum jp
    JOIN matakuliah_praktikum mk ON mk.id = jp.id_mk
    JOIN dosen d ON d.id = jp.id_dosen
    LEFT JOIN ruangan r ON r.id = jp.id_ruangan
    WHERE jp.id = ".(int)$id_jadwal."
      AND jp.id_semester = ".(int)$id_semester."
      AND jp.id_mk = ".(int)$id_mk."
    LIMIT 1
  "));
}

/* =========================================================================
   Helper: mahasiswa terdaftar (shift+semester)
   ========================================================================= */
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
    while ($r = mysqli_fetch_assoc($q)) { $data[] = $r; }
  }
  return $data;
}

/* =========================================================================
   Susun tabel: kolom tanggal sesi & baris mahasiswa
   ========================================================================= */
$tanggalSesi = [];   // header (YYYY-MM-DD)
$statusMap   = [];   // [id_mhs][tgl] => 'Hadir'|'Alpha'|'Izin'
$mhsList     = [];

// Hanya isi data jika MK & Shift sudah dipilih dan valid
if ($id_semester && $id_mk && $id_jadwal && $shiftValid) {
  // Tanggal-tanggal sesi (distinct) untuk jadwal ini
  $resTgl = mysqli_query($conn, "
    SELECT DISTINCT DATE(s.mulai_at) AS tgl
    FROM absensi_detail ad
    JOIN absensi_sesi s ON s.id = ad.id_sesi
    WHERE s.id_jadwal = ".(int)$id_jadwal."
    ORDER BY tgl ASC
  ");
  while ($r = mysqli_fetch_assoc($resTgl)) { $tanggalSesi[] = $r['tgl']; }

  // Ambil list mahasiswa terdaftar
  $mhsList = ambilMahasiswaByJadwal($conn, $id_jadwal, $id_semester);

  // Peta status per (mahasiswa, tanggal)
  $qDet = mysqli_query($conn, "
    SELECT ad.id_mahasiswa, DATE(s.mulai_at) AS tgl, ad.status
    FROM absensi_detail ad
    JOIN absensi_sesi s ON s.id = ad.id_sesi
    WHERE s.id_jadwal = ".(int)$id_jadwal."
  ");
  while ($d = mysqli_fetch_assoc($qDet)) {
    $idm    = (int)$d['id_mahasiswa'];
    $tgl    = $d['tgl'];
    $status = ucfirst(strtolower(trim((string)$d['status'])));
    if (in_array($status, ['Hadir','Alpha','Izin'], true)) {
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
    /* ====== CSS disarikan dari sumber & diterapkan langsung pada tabel ====== */
    body { font-family: Arial, sans-serif; background:#f7f7f7; }

    .konten-utama {
      margin-left:250px;
      margin-top:60px;
      padding:24px;
      min-height:calc(100vh - 60px);
    }

    .h2 { margin:0 0 14px; color:#333; font-weight:700; letter-spacing:.2px; }

    .baris {
      display:flex; flex-wrap:wrap; gap:8px; align-items:center; margin-bottom:12px;
    }

    select, button {
      padding:8px 10px; border:1px solid #d0d0d0; border-radius:6px; background:#fff;
    }
    button { cursor:pointer; }

    #btn-cetak { background:#28a745; color:#fff; border:none; }
    #btn-cetak:disabled { background:#99d1a9; cursor:not-allowed; }

    .kartu .badan-kartu { padding:5px 5px; margin-top:10px; }
    .info-semester { margin-bottom:10px; color:#333; }

    /* ——— Tidak ada pembungkus tabel ——— */
    /* Gaya langsung pada tabel */
    table.display{
      width:100%;
      border-collapse:collapse;
      background:#fff;
      border-radius:10px;
      overflow:hidden;
      box-shadow:0 2px 6px rgba(0,0,0,.1);
      table-layout:fixed;
    }
    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select{
      padding:6px 10px; border-radius:5px; border:1px solid #ccc; font-size:14px; margin-bottom:5px;margin-top:10px;
    }
    table.dataTable thead th{
      background:#00AEEF; color:#333; font-weight:700; white-space:nowrap;
    }

    /* Responsif: gunakan data-label pada TD (penanda dari TH) */
    @media (max-width: 768px){
      .konten-utama { margin-left:0; padding:16px; }
      .baris { flex-direction:column; align-items:stretch; }
      select,button { width:100%; }

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
  </style>
</head>
<body>
  <div class="konten-utama">
    <h2 class="h2">Laporan Absensi per Shift (Admin)</h2>

    <!-- Info Semester -->
    <div class="kartu">
      <div class="badan-kartu">
        <?php if ($id_semester):
          $sem = mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT nama_semester, tahun_ajaran FROM semester WHERE id=".(int)$id_semester." LIMIT 1"
          ));
        ?>
          <span class="lencana utama">Semester Aktif:
           <b><?= e($sem['nama_semester']) ?></b><b>/</b><b><?= e($sem['tahun_ajaran']) ?></b>
           <?php else: ?>
    <div class="info-sem info-none">
      Belum ada <b>Semester</b> berstatus <b>Aktif</b>. Silakan aktifkan/ tambahkan semester terlebih dahulu pada menu Semester.
    </div>
  <?php endif; ?>

    <!-- Filter -->
    <div class="kartu">
      <div class="badan-kartu">
        <form method="get" id="form-filter" class="baris">
          <div class="grup-form">
            <select name="id_semester" id="id_semester" class="pilih" onchange="this.form.submit()">
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

          <div class="grup-form">
            <select
              name="id_mk"
              id="id_mk"
              class="pilih"
              onchange="this.form.submit()"
              <?= (!$id_semester) ? 'disabled' : '' ?>
            >
              <option value="0" <?= $id_mk === 0 ? 'selected' : '' ?>>-- Pilih MK --</option>
              <?php if (!empty($mkList)): foreach ($mkList as $mk):
                $label = $mk['nama_mk'];
                if (isset($mk['jml_shift']) && (int)$mk['jml_shift'] === 0) {
                  $label .= ' (0 shift)';
                }
              ?>
                <option value="<?= (int)$mk['id'] ?>" <?= $id_mk === (int)$mk['id'] ? 'selected' : '' ?>>
                  <?= e($label) ?>
                </option>
              <?php endforeach; endif; ?>
            </select>
          </div>

          <div class="grup-form" style="min-width:320px">
            <div style="display:flex; gap:8px;">
              <select
                name="id_jadwal"
                id="id_jadwal"
                class="pilih"
                onchange="this.form.submit()"
                <?= (!$id_semester || !$id_mk || empty($shiftList)) ? 'disabled' : '' ?>
              >
                <option value="0" <?= $id_jadwal === 0 ? 'selected' : '' ?>>-- Pilih Shift --</option>
                <?php if (!empty($shiftList)): foreach ($shiftList as $j):
                  $jam   = substr($j['jam_mulai'],0,5).' - '.substr($j['jam_selesai'],0,5);
                  $label = $j['hari'].' '.$jam; ?>
                  <option value="<?= (int)$j['id'] ?>" <?= $id_jadwal === (int)$j['id'] ? 'selected' : '' ?>>
                    <?= e($label) ?>
                  </option>
                <?php endforeach; endif; ?>
              </select>

              <button
                type="button"
                class="tombol tombol-sukses"
                id="btn-cetak"
                <?= (!$shiftValid || empty($mhsList)) ? 'disabled' : '' ?>><i class="fa-solid fa-print"></i> Cetak PDF
              </button>

            
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Info Shift -->
    <?php if ($shiftValid): ?>
      <div class="kotak-pesan info">
        <?php if (empty($tanggalSesi)): ?>
          <span style="margin-left:14px; color:#64748b">Belum ada sesi absensi tersimpan.</span>
        <?php endif; ?>
      </div>
    <?php elseif ($id_mk && empty($shiftList)): ?>
      <div class="kotak-pesan peringatan">Tidak ada shift untuk MK terpilih pada semester ini.</div>
    <?php endif; ?>

    <!-- Tabel Rekap (TANPA pembungkus) -->
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
          <?php $no = 1; foreach ($mhsList as $m): $idm = (int)$m['id_mhs']; ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><?= e($m['nim']) ?></td>
              <td><?= e($m['nama']) ?></td>
              <?php foreach ($tanggalSesi as $tgl):
                $s = $statusMap[$idm][$tgl] ?? '';
                $badge = '';
                if     ($s === 'Hadir') $badge = '<span class="lencana ok">Hadir</span>';
                elseif ($s === 'Alpha') $badge = '<span class="lencana peringatan">Alpha</span>';
                elseif ($s === 'Izin')  $badge = '<span class="lencana info">Izin</span>';
              ?>
                <td><?= $badge ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <!-- Biarkan kosong saat awal; DataTables akan menampilkan "Tidak ada data" -->
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <script>
    // Inisialisasi DataTables
    $(function(){
      const $tbl = $('#tabel-laporan');
      if ($tbl.length) {
        const dt = $tbl.DataTable({
          pageLength: 10,
          lengthMenu: [5, 10, 25, 50, 100],
          scrollX: true,
          autoWidth: false,
          ordering: false,
          dom: '<"kontrol-dt"lf>t<"kontrol-dt"ip>',
          language: {
            emptyTable:  "Tidak ada data",
            info:        "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty:   "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered:"(disaring dari _MAX_ data total)",
            lengthMenu:  "Tampilkan _MENU_ data",
            search:      "Cari:",
            zeroRecords: "Tidak ditemukan data yang sesuai",
            paginate:    { first:"Pertama", last:"Terakhir", next:"Berikutnya", previous:"Sebelumnya" }
          }
        });

        // === Penanda kolom di TD (data-label diisi dari TH) ===
        function setDataLabels(){
          const headers = [];
          $('#tabel-laporan thead th').each(function(){ headers.push($(this).text().trim()); });
          $('#tabel-laporan tbody tr').each(function(){
            $(this).children('td').each(function(i){
              $(this).attr('data-label', headers[i] || '');
            });
          });
        }
        // panggil awal dan setiap redraw (mis. paging/filter)
        setDataLabels();
        dt.on('draw', setDataLabels);
      }
    });

    // Cetak PDF
    document.getElementById('btn-cetak')?.addEventListener('click', function(){
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF({ orientation:'landscape', unit:'mm', format:'a4' });
      doc.setFontSize(13);

      let judul = "Laporan Absensi";
      <?php if ($shiftValid): ?>
        judul = "Laporan Absensi: <?= e($shiftValid['nama_mk']) ?> — <?= e($shiftValid['hari']) ?> <?= e(substr($shiftValid['jam_mulai'],0,5).' - '.substr($shiftValid['jam_selesai'],0,5)) ?>";
      <?php endif; ?>

      doc.text(judul, 148.5, 12, { align: "center" });

      // Header
      const headers = [];
      document.querySelectorAll('#tabel-laporan thead th').forEach(th => {
        headers.push(th.textContent.trim());
      });

      // Body
      const body = [];
      document.querySelectorAll('#tabel-laporan tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
          row.push(td.textContent.trim());
        });
        body.push(row);
      });

      if (typeof doc.autoTable !== 'function') {
        alert('Plugin autoTable belum termuat.');
        return;
      }

      doc.autoTable({
        head: [headers],
        body: body,
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
