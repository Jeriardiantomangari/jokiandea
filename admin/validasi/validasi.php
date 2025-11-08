<?php 
session_start();
include '../../koneksi/koneksi.php'; 
include '../../koneksi/sidebar.php'; 

// Cek role
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
    header("Location: ../login.php");
    exit;
}

// helper aman untuk echo
function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// Ambil ID semester aktif (jika ada)
$rsSem = mysqli_query($conn, "SELECT id, nama_semester, tahun_ajaran FROM semester WHERE status='Aktif' LIMIT 1");
$semAktif = mysqli_fetch_assoc($rsSem);
$id_semester_aktif = $semAktif['id'] ?? null;
?>
<!-- CDN jQuery dan DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.konten-utama { margin-left:250px; margin-top:60px; padding:30px; background:#f9f9f9; font-family:Arial,sans-serif; }
.tombol { border:none; border-radius:5px; cursor:pointer; color:white; font-size:12px; transition:0.3s; padding:6px 10px; }
.tombol:hover { opacity:0.85; }

  .info-sem{ padding:10px 12px; border-radius:8px; margin:8px 0; background:#fff; border:1px solid #e5e7eb; }
  .info-aktif { color: #333 }
  .info-none { color: #333}

  .tombol-setujui {
    background-color: #28a745;
    margin-bottom: 10px;
    padding: 8px 15px;
  }
  .tombol-tolak {
  background-color: #dc3545; 
  padding: 6px 10px;
   width: 80px;
}
.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select { padding:6px 10px; border-radius:5px; border:1px solid #ccc; font-size:14px; margin-bottom:5px; }

.tabel-data { width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.1); table-layout:fixed; }
.tabel-data th { background:#00AEEF; color:#333; text-align:left; padding:12px 15px; }
.tabel-data td { padding:12px 15px; border-bottom:1px solid #ddd; border-right:1px solid #ddd; }
.tabel-data tr:hover { background:#f1f1f1; }

.pembayaran-cell {
    text-align: center;
}
.pembayaran-cell a i {
    font-size: 30px !important;
}

@media screen and (max-width: 768px) {
  .konten-utama { margin-left:0; padding:20px; width:100%; background-color:#f9f9f9; text-align:center; }
  .konten-utama h2 { text-align:center; }
  .konten-utama .tombol-cetak, .konten-utama .tombol-tambah { display:inline-block; margin:5px auto; }
  .tabel-data, thead, tbody, th, td, tr { display:block; }
  thead tr { display:none; }
  tr { margin-bottom:15px; border-bottom:2px solid #000; }
  td { text-align:right; padding-left:50%; position:relative; }
  td::before { content: attr(data-label); position:absolute; left:15px; width:45%; font-weight:bold; text-align:left; }
  .tombol-edit, .tombol-hapus { width:auto; padding:6px 10px; display:inline-block; margin:3px 2px; }
   .pembayaran-cell {
        text-align: right;        
    }
}
 
</style>

<div class="konten-utama">
  <h2>Validasi Pendaftaran Praktikum</h2>

   <?php if($id_semester_aktif): ?>
    <div class="info-sem info-aktif">
      Semester Aktif: <b><?= e($semAktif['nama_semester']) ?></b><b>/</b><b><?= e($semAktif['tahun_ajaran']) ?></b>
    </div>
  <?php else: ?>
    <div class="info-sem info-none">
      Belum ada <b>Semester</b> berstatus <b>Aktif</b>. Silakan aktifkan/ tambahkan semester terlebih dahulu pada menu Semester.
    </div>
  <?php endif; ?>

  <table id="tabel-validasi" class="tabel-data">
    <thead>
      <tr>
        <th>No</th>
        <th>NIM</th>
        <th>Nama</th>
        <th>No HP</th>
        <th>MK Dikontak</th>
        <th>Bukti Pembayaran</th>
        <th>Status</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $no = 1;
      $query = mysqli_query($conn, "SELECT * FROM kontrak_mk ORDER BY id DESC");
      while ($row = mysqli_fetch_assoc($query)) {
      ?>
      <tr>
        <td data-label="No"><?= $no++; ?></td>
        <td data-label="Nim"><?= htmlspecialchars($row['nim']); ?></td>
        <td data-label="Nama"><?= htmlspecialchars($row['nama']); ?></td>
        <td data-label="Nomor Hp"><?= htmlspecialchars($row['no_hp']); ?></td>
        <td data-label="MK dikontrak"><?= htmlspecialchars($row['mk_dikontrak']); ?></td>
        <td data-label="Bukti Pembayaran" class="pembayaran-cell">
         <?php if (!empty($row['bukti_pembayaran'])) { ?>
  <a href='../../uploads/<?= $row['bukti_pembayaran']; ?>' target='_blank' title="Lihat Bukti">
    <i class="fa-solid fa-file-arrow-down" style="font-size:20px; color:#dc3545;"></i>
  </a>
<?php } else { ?>
  <span style="color:#aaa;">Belum upload</span>
<?php } ?>

        </td>
        <td data-label="Status" >
          <?php 
            if ($row['status'] == 'Disetujui') echo "<span style='color:green;font-weight:bold;'>Disetujui</span>";
            elseif ($row['status'] == 'Ditolak') echo "<span style='color:red;font-weight:bold;'>Ditolak</span>";
            else echo "<span style='color:#999;'>Menunggu</span>";
          ?>
        </td>
        <td data-label="Aksi">
          <button class="tombol tombol-setujui" onclick="ubahStatus(<?= $row['id']; ?>,'setujui')">
            <i class="fa-solid fa-check"></i> Setujui
          </button>
          <button class="tombol tombol-tolak" onclick="ubahStatus(<?= $row['id']; ?>,'tolak')">
            <i class="fa-solid fa-xmark"></i> Tolak
          </button>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
</div>

<script>
// DataTables
$(document).ready(function () {
    $('#tabel-validasi').DataTable({
      "pageLength": 10,
      "lengthMenu": [5, 10, 25, 50],
      "columnDefs": [{
        "orderable": false,"targets": 7 }],
      "language": {
        "decimal": "",
        "emptyTable": "Tidak ada data tersedia",
        "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
        "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
        "infoFiltered": "(disaring dari _MAX_ data total)",
        "lengthMenu": "Tampilkan _MENU_ data",
        "loadingRecords": "Memuat...",
        "processing": "Sedang diproses...",
        "search": "Cari:",
        "zeroRecords": "Tidak ditemukan data yang sesuai",
        "paginate": {
          "first": "Pertama",
          "last": "Terakhir",
          "next": "Berikutnya",
          "previous": "Sebelumnya"
        }
      }
    });
  });


function ubahStatus(id, aksi){
  let pesan = (aksi == 'setujui') ? 'Apakah Anda yakin ingin menyetujui pendaftaran ini?' : 'Apakah Anda yakin ingin menolak pendaftaran ini?';
  if(confirm(pesan)){
    $.post('proses_validasi.php', {id:id, aksi:aksi}, function(){
      alert('Status berhasil diubah!');
      location.reload();
    });
  }
}
</script>
