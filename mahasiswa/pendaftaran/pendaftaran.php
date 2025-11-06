<?php 
// Inisialisasi sesi & koneksi
session_start();
include '../../koneksi/sidebarmhs.php'; 
include '../../koneksi/koneksi.php'; 

// Validasi role pengguna (mahasiswa)
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'mahasiswa'){
    header("Location: ../login.php");
    exit;
}

// Helper aman untuk escape output HTML
function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// ✅ Ambil data mahasiswa login (pakai mhs_id, bukan id_user)
$id_mahasiswa = (int)($_SESSION['mhs_id'] ?? 0);

// Semester aktif (ambil yang status 'Aktif')
$qSem = mysqli_query($conn, "SELECT id, nama_semester, tahun_ajaran FROM semester WHERE status='Aktif' LIMIT 1");
$semAktif = mysqli_fetch_assoc($qSem);
$id_semester = $semAktif['id'] ?? null;

// Ambil kontrak terbaru pada semester aktif (jika ada)
$kontrak = null;
if ($id_semester){
    $qKon = mysqli_query($conn, "
        SELECT id, status, mk_dikontrak 
        FROM kontrak_mk 
        WHERE id_mahasiswa='$id_mahasiswa' AND id_semester='$id_semester'
        ORDER BY id DESC LIMIT 1
    ");
    $kontrak = mysqli_fetch_assoc($qKon);
}
$status_bayar = $kontrak['status'] ?? null;

// Pecah daftar MK dari kontrak (nama MK dipisah koma)
$mk_list_raw = [];
if (!empty($kontrak['mk_dikontrak'])) {
    foreach(explode(',', $kontrak['mk_dikontrak']) as $n){
        $n = trim($n);
        if($n!=='') $mk_list_raw[] = $n;
    }
}

/* ========================================================================
   - Ambil set JADWAL yang sudah dipilih (berdasarkan id_jadwal), dan penanda per-MK
   ======================================================================== */
$mk_sudah_dipilih = [];          // penanda per MK
$jadwal_sudah_diambil = [];      // penanda per id_jadwal yang sudah dipilih
if ($id_semester){
    $qSudah = mysqli_query($conn, "
        SELECT id_mk, id_jadwal
        FROM pilihan_jadwal 
        WHERE id_mahasiswa='$id_mahasiswa' AND id_semester='$id_semester'
    ");
    while($r = mysqli_fetch_assoc($qSudah)){
        $mk_sudah_dipilih[(int)$r['id_mk']] = true;
        $jadwal_sudah_diambil[(int)$r['id_jadwal']] = true;
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Pilih Jadwal Praktikum</title>

<!-- Pustaka DataTables & jQuery -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- Ikon FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* Area konten utama halaman */
.konten_utama { margin-left:250px; margin-top:60px; padding:30px; min-height:calc(100vh - 60px); background:#f9f9f9; font-family:Arial,sans-serif; }
.konten_utama h2 { margin-bottom:10px; color:#333; }

/* Komponen kotak informasi */
.kotak_info { margin:10px 0 15px 0; padding:10px 12px; border-radius:6px; }
.info_sukses { color:#333  }
 .info_peringatan { color :#ff5252;}
.info_gagal {   color :#ff5252;}

/* Tombol umum & tombol tambah */
.tombol_umum { border:none; border-radius:5px; cursor:pointer; color:white; font-size:12px; transition:0.3s; padding:6px 10px; }
.tombol_umum:hover { opacity:0.9; }
.tombol_tambah { background:#00b4ff; padding:8px 15px; }

/* Tabel data jadwal */
.tabel_data { width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.1); table-layout:fixed; }
.tabel_data th { background:#00AEEF; color:#333; text-align:left; padding:12px 15px; }
.tabel_data td { padding:12px 15px; border-bottom:1px solid #ddd; border-right:1px solid #ddd; }
.tabel_data tr:hover { background:#f7fbff; }

/* Badge status MK */
.lencana { display:inline-block; padding:5px 10px; border-radius:5px; font-size:15px; }
.lencana_sukses { background:#e7f7e9; color:#096a2e; border:1px solid #bde5c8; }
.lencana_peringatan { background:#fff3cd; color:#7a5b00; border:1px solid #ffe08a; }

/* Kontrol bawaan DataTables */
.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select { padding:6px 10px; border-radius:5px; border:1px solid #ccc; font-size:14px; margin-bottom:5px; }

/* Tampilan mobile (<=768px) */
@media screen and (max-width: 768px) {
  .konten_utama { margin-left:0; padding:20px; width:100%; text-align:center; }
  .konten_utama h2 { text-align:center; }
  .tabel_data, thead, tbody, th, td, tr { display:block; }
  thead tr { display:none; }
  tr { margin-bottom:15px; border-bottom:2px solid #000; }
  td { text-align:right; padding-left:50%; position:relative; }
  td::before { content: attr(data-label); position:absolute; left:15px; width:45%; font-weight:bold; text-align:left; }
}
</style>
</head>
<body>
<div class="konten_utama">
  <h2>Pilih Jadwal Praktikum</h2>

  <?php if(!$id_semester): ?>
    <div class="kotak_info info_peringatan">Belum ada <b>Semester</b> berstatus <b>Aktif</b>. Pemilihan jadwal dinonaktifkan.</div>
  <?php elseif(!$kontrak): ?>
    <div class="kotak_info info_peringatan">Anda belum mengirim kontrak/bukti bayar untuk Semester ini.</div>
  <?php elseif($status_bayar === 'Ditolak'): ?>
    <div class="kotak_info info_gagal">Status kontrak pada Semester Aktif: <b>Ditolak</b>. Silakan perbaiki pengajuan kontrak.</div>
  <?php elseif($status_bayar === 'Menunggu'): ?>
    <div class="kotak_info info_peringatan">Status kontrak pada Semester Aktif: <b>Menunggu</b>. Pemilihan jadwal akan aktif setelah <b>Disetujui</b>.</div>
  <?php else: ?>
    <div class="kotak_info info_sukses">Status kontrak pada Semester Aktif: <b>Disetujui</b>. Silakan memilih shift.</div>
  <?php endif; ?>

  <table id="tabel-jadwal" class="tabel_data">
    <thead>
      <tr>
        <th>No</th>
        <th>Kode MK</th>
        <th>Nama MK</th>
        <th>Hari</th>
        <th>Jam</th>
        <th>Pengajar</th>
        <th>Ruangan</th>
        <th>Kuota</th>
        <th>Status MK</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
<?php
$no=1;
$boleh_tampil = $id_semester && $kontrak && $status_bayar === 'Disetujui';

$mk_in_sql = '';
if ($boleh_tampil && count($mk_list_raw) > 0){
    $escaped = [];
    foreach($mk_list_raw as $nm){
        $escaped[] = "'" . mysqli_real_escape_string($conn, $nm) . "'";
    }
    $mk_in_sql = implode(',', $escaped);

    $sql = "
        SELECT jp.*, mk.kode_mk, mk.nama_mk, d.nama as pengajar, r.nama_ruangan
        FROM jadwal_praktikum jp
        LEFT JOIN matakuliah_praktikum mk ON jp.id_mk = mk.id
        LEFT JOIN dosen d ON jp.id_dosen = d.id
        LEFT JOIN ruangan r ON jp.id_ruangan = r.id
        WHERE jp.id_semester = '$id_semester'
          AND mk.nama_mk IN ($mk_in_sql)
          AND jp.kuota > 0
        ORDER BY mk.nama_mk ASC, 
                 FIELD(jp.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'),
                 jp.jam_mulai ASC
    ";
    $q = mysqli_query($conn, $sql);

    while($row = mysqli_fetch_assoc($q)){
        $jam = date('H:i', strtotime($row['jam_mulai'])) . ' - ' . date('H:i', strtotime($row['jam_selesai']));
        $mk_id = (int)$row['id_mk'];

        // Per status per-jadwal
        $id_jadwal_baris = (int)$row['id']; // id dari tabel jadwal_praktikum (alias jp.id)
        $sudah_ambil_jadwal = isset($jadwal_sudah_diambil[$id_jadwal_baris]);

        /* ======================= PERUBAHAN PENTING (AKSI) =======================
           - Jika baris ini yang dipilih  -> tombol: Selesai
           - Jika MK ini sudah punya jadwal lain dipilih -> tombol juga: Selesai (kunci)
           - Jika belum ada pilihan untuk MK ini -> tombol: Pilih
        ======================================================================== */
        $sudah_ambil_mk = isset($mk_sudah_dipilih[$mk_id]);
        ?>
        <tr>
          <td data-label="No"><?= $no++; ?></td>
          <td data-label="Kode MK"><?= e($row['kode_mk']); ?></td>
          <td data-label="Nama MK"><?= e($row['nama_mk']); ?></td>
          <td data-label="Hari"><?= e($row['hari']); ?></td>
          <td data-label="Jam"><?= e($jam); ?></td>
          <td data-label="Pengajar"><?= e($row['pengajar']); ?></td>
          <td data-label="Ruangan"><?= e($row['nama_ruangan']); ?></td>
          <td data-label="Kuota"><?= e($row['kuota']); ?></td>
          <td data-label="Status MK">
            <?php if($sudah_ambil_jadwal): ?>
              <span class="lencana lencana_sukses">Sudah ambil</span>
            <?php else: ?>
              <span class="lencana lencana_peringatan">Belum ambil</span>
            <?php endif; ?>
          </td>
          <td data-label="Aksi">
            <?php if($sudah_ambil_jadwal || $sudah_ambil_mk): ?>
              <span style="color:green; font-weight:bold;">Selesai</span>
            <?php else: ?>
              <button class="tombol_umum tombol_tambah" onclick="pilihJadwal(<?= (int)$row['id']; ?>)">
                <i class="fa-solid fa-check"></i> Pilih
              </button>
            <?php endif; ?>
          </td>
        </tr>
        <?php
    }
}
?>
    </tbody>
  </table>
</div>

<script>
// Inisialisasi DataTables
$(document).ready(function () {
  $('#tabel-jadwal').DataTable({
    pageLength: 10,
    lengthMenu: [5, 10, 25, 50],
    columnDefs: [{ orderable: false, targets: 9 }],
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
      paginate: { first: "Pertama", last: "Terakhir", next: "Berikutnya", previous: "Sebelumnya" }
    }
  });
});

// Aksi: pilih jadwal
function pilihJadwal(id_jadwal){
  if(!confirm("Ambil shift ini?")) return;
  $.post('proses_pilih_jadwal.php', {id_jadwal:id_jadwal}, function(res){
    res = (res||'').toString().trim();
    alert(res.replace(/^ok\|/, ''));
    if(!res.startsWith('error|')) location.reload();
  });
}
</script>
</body>
</html>
