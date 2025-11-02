<?php 
session_start();
include '../../koneksi/sidebarmhs.php'; 
include '../../koneksi/koneksi.php'; 

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'mahasiswa'){
    header("Location: ../login.php");
    exit;
}

$id_mahasiswa = $_SESSION['id_user'];

// Ambil daftar MK yang dikontrak mahasiswa
$kontrak = mysqli_query($conn, "SELECT mk_dikontrak FROM kontrak_mk WHERE id_mahasiswa='$id_mahasiswa' ORDER BY id DESC LIMIT 1");
$data_kontrak = mysqli_fetch_assoc($kontrak);

// Jika data tidak ada, gunakan string kosong supaya explode aman
$mk_dikontrak = $data_kontrak['mk_dikontrak'] ?? '';
$mk_kontrak = explode(',', $mk_dikontrak);

?>

<!-- DataTables & jQuery -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.konten-utama { margin-left:250px; margin-top:60px; padding:30px; min-height:calc(100vh - 60px); background:#f9f9f9; font-family:Arial,sans-serif; }
.konten-utama h2 { margin-bottom:20px; color:#333; }

.tombol { border:none; border-radius:5px; cursor:pointer; color:white; font-size:12px; transition:0.3s; padding:6px 10px; }
.tombol:hover { opacity:0.85; }
.tombol-tambah { background:#00b4ff; margin-bottom:10px; }
.tabel-data { width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.1); table-layout:fixed; }
.tabel-data th { background:#8bc9ff; color:#333; text-align:left; padding:12px 15px; }
.tabel-data td { padding:12px 15px; border-bottom:1px solid #ddd; border-right:1px solid #ddd; }
.tabel-data tr:hover { background:#f1f1f1; }

.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select { padding:6px 10px; border-radius:5px; border:1px solid #ccc; font-size:14px; margin-bottom:5px; }

/* Responsive */
@media screen and (max-width: 768px) {
    .konten-utama { margin-left:0; padding:20px; width:100%; text-align:center; }
    .konten-utama h2 { text-align:center; }

    .tabel-data, thead, tbody, th, td, tr { display:block; }
    thead tr { display:none; }
    tr { margin-bottom:15px; border-bottom:2px solid #000; }
    td { text-align:right; padding-left:50%; position:relative; }
    td::before { content: attr(data-label); position:absolute; left:15px; width:45%; font-weight:bold; text-align:left; }
    .tombol-tambah { width:auto; padding:6px 10px; display:inline-block; margin:3px 2px; }
}
</style>

<div class="konten-utama">
  <h2>Pilih Jadwal Praktikum</h2>
  <table id="tabel-jadwal" class="tabel-data">
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
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $no=1;
      $mk_list = "'" . implode("','", array_map('trim', $mk_kontrak)) . "'";
      $query = mysqli_query($conn,"SELECT jp.*, mk.kode_mk, mk.nama_mk, d.nama as pengajar, r.nama_ruangan
        FROM jadwal_praktikum jp
        LEFT JOIN matakuliah_praktikum mk ON jp.id_mk = mk.id
        LEFT JOIN dosen d ON jp.id_dosen = d.id
        LEFT JOIN ruangan r ON jp.id_ruangan = r.id
        WHERE mk.nama_mk IN ($mk_list) AND jp.kuota > 0
        ORDER BY jp.hari, jp.jam_mulai ASC
      ");
      while($row=mysqli_fetch_assoc($query)){
          $cek = mysqli_query($conn,"SELECT * FROM pilihan_jadwal WHERE id_mahasiswa='$id_mahasiswa' AND id_jadwal='".$row['id']."'");
          $sudah_ambil = mysqli_num_rows($cek) > 0;
      ?>
      <tr>
        <td data-label="No"><?= $no++; ?></td>
        <td data-label="Kode MK"><?= $row['kode_mk']; ?></td>
        <td data-label="Nama MK"><?= $row['nama_mk']; ?></td>
        <td data-label="Hari"><?= $row['hari']; ?></td>
         <td data-label="Jam"><?= date('H:i', strtotime($row['jam_mulai'])) . ' - ' . date('H:i', strtotime($row['jam_selesai'])); ?></td>
        <td data-label="Pengajar"><?= $row['pengajar']; ?></td>
        <td data-label="Ruangan"><?= $row['nama_ruangan']; ?></td>
        <td data-label="Kuota"><?= $row['kuota']; ?></td>
        <td data-label="Aksi">
          <?php if($sudah_ambil){ ?>
            <span style="color:green; font-weight:bold;">Sudah Dipilih</span>
          <?php } else { ?>
            <button class="tombol tombol-tambah" onclick="pilihJadwal(<?= $row['id']; ?>)"><i class="fa-solid fa-check"></i> Pilih</button>
          <?php } ?>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
</div>

<script>
$(document).ready(function(){
    $('#tabel-jadwal').DataTable({
      "pageLength": 10,
      "lengthMenu": [5, 10, 25, 50],
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

function pilihJadwal(id_jadwal){
    if(confirm("Apakah Anda yakin memilih jadwal ini?")){
        $.post('proses_pilih_jadwal.php', {id_jadwal:id_jadwal}, function(res){
            alert(res);
            location.reload();
        });
    }
}
</script>
