<?php 
session_start();
include '../../koneksi/sidebar.php'; 
include '../../koneksi/koneksi.php'; 
// Cek role
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
   header("Location: ../index.php"); exit;
}
?>

<!-- CDN jQuery dan DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- jsPDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<style>
.konten-utama { margin-left:250px; margin-top:60px; padding:30px; min-height:calc(100vh - 60px); background:#f9f9f9; font-family:Arial,sans-serif; }
.konten-utama h2 { margin-bottom:20px; color:#333; }
.tombol { border:none; border-radius:5px; cursor:pointer; color:white; font-size:10px; transition:0.3s; }
.tombol:hover { opacity:0.85; }
.tombol-edit { background:#007bff; width:60px; margin-bottom:3px; padding:6px 10px; }
.tombol-hapus { background:#dc3545; width:60px; padding:6px 10px; }
.tombol-cetak { background:#28a745; margin-right:10px; padding:8px 15px; }
.tombol-tambah { background:#00b4ff; margin-bottom:10px; padding:8px 15px; }
.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select { padding:6px 10px; border-radius:5px; border:1px solid #ccc; font-size:14px; margin-bottom:5px; }

.tabel-data { width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.1); table-layout:fixed; }
.tabel-data th { background:#00AEEF; color:#333; text-align:left; padding:12px 15px; }
.tabel-data td { padding:12px 15px; border-bottom:1px solid #ddd; border-right:1px solid #ddd; }
.tabel-data tr:hover { background:#f1f1f1; }

.kotak-modal { display:none; position:fixed; z-index:300; left:0; top:0; width:100%; height:100vh; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; }
.isi-modal { background:white; padding:25px; border-radius:10px; width:400px; max-width:90%; box-shadow:0 5px 15px rgba(0,0,0,.3); text-align:center; position:relative; }
.isi-modal h3 { margin-bottom:20px; }
.isi-modal input, .isi-modal select { width:100%; padding:10px; margin:5px 0; border:1px solid #ccc; border-radius:6px; }
.isi-modal button { width:100%; padding:10px; border:none; border-radius:6px; background:#007bff; color:white; font-weight:600; cursor:pointer; margin-top:10px; }
.isi-modal button:hover { background:#005fc3; }
.tutup-modal { position:absolute; top:15px; right:15px; cursor:pointer; font-size:18px; color:#666; }
.tutup-modal:hover { color:black; }
.modul-cell {
    text-align: center;
}
.modul-cell a i {
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
    .modul-cell {
        text-align: right; 
                
    }
   
}

</style>

<div class="konten-utama">
  <h2>Data Mata Kuliah Praktikum</h2>

  <button class="tombol tombol-cetak"><i class="fa-solid fa-print"></i> Cetak</button>
  <button class="tombol tombol-tambah" onclick="tambahMK()"><i class="fa-solid fa-plus"></i> Tambah</button>

  <table id="tabel-mk" class="tabel-data">
    <thead>
      <tr>
        <th>No.</th>
        <th>Kode MK</th>
        <th>Nama MK</th>
        <th>SKS</th>
        <th>Semester</th>
        <th>Modul</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $no=1;
      $query = mysqli_query($conn,"SELECT * FROM matakuliah_praktikum ORDER BY id ASC");
      while($row=mysqli_fetch_assoc($query)) {
      ?>
      <tr>
        <td data-label="No"><?php echo $no++; ?></td>
        <td data-label="Kode MK"><?php echo $row['kode_mk']; ?></td>
        <td data-label="Nama MK"><?php echo $row['nama_mk']; ?></td>
        <td data-label="Sks"><?php echo $row['sks']; ?></td>
        <td data-label="Semester"><?php echo $row['semester']; ?></td>
       <td data-label="Modul" class="modul-cell">
  <?php if ($row['modul']) { ?>
    <a href="../../uploads/modul/<?php echo $row['modul']; ?>" target="_blank" title="Lihat Modul PDF">
      <i class="fa-solid fa-file-arrow-down" style="font-size:22px; color:#e63946;"></i>
    </a>
  <?php } else { ?>
    <span style="color:#aaa;">-</span>
  <?php } ?>
</td>
        <td data-label="Aksi">
          <button class="tombol tombol-edit" onclick="editMK(<?php echo $row['id']; ?>)"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
          <button class="tombol tombol-hapus" onclick="hapusMK(<?php echo $row['id']; ?>)"><i class="fa-solid fa-trash"></i> Hapus</button>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
</div>

<!-- Modal Tambah/Edit -->
<div id="modalMK" class="kotak-modal">
  <div class="isi-modal">
    <span class="tutup-modal" onclick="tutupModal()">&times;</span>
    <h3 id="judulModal">Tambah Mata Kuliah</h3>
    <form id="formMK" enctype="multipart/form-data">
      <input type="hidden" name="id" id="idMK">
      <input type="text" name="kode_mk" id="kode_mk" placeholder="Kode MK" required>
      <input type="text" name="nama_mk" id="nama_mk" placeholder="Nama MK" required>
      <input type="number" name="sks" id="sks" placeholder="Jumlah SKS" required>
      <input type="text" name="semester" id="semester" placeholder="Semester" required>
      <label>Upload Modul (PDF):</label>
      <input type="file" name="modul" id="modul" accept="application/pdf">
      <button type="submit">Simpan</button>
    </form>
  </div>
</div>

<script>
// DataTables
$(document).ready(function () {
    $('#tabel-mk').DataTable({
      "pageLength": 10,
      "lengthMenu": [5, 10, 25, 50],
      "columnDefs": [{
        "orderable": false,"targets": 6 }],
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

function tambahMK(){
  $('#formMK')[0].reset();
  $('#judulModal').text('Tambah Mata Kuliah');
  $('#modalMK').css('display','flex');
}

function editMK(id){
  $.post('proses_matakuliah.php',{aksi:'ambil',id:id},function(data){
    let obj = JSON.parse(data);
    $('#judulModal').text('Edit Mata Kuliah');
    $('#idMK').val(obj.id);
    $('#kode_mk').val(obj.kode_mk);
    $('#nama_mk').val(obj.nama_mk);
    $('#sks').val(obj.sks);
    $('#semester').val(obj.semester);
    $('#modalMK').css('display','flex');
  });
}

function hapusMK(id){
  if(confirm('Apakah Anda yakin ingin menghapus data ini?')){
    $.post('proses_matakuliah.php',{aksi:'hapus',id:id},function(){
      alert('Data berhasil dihapus!');
      location.reload();
    });
  }
}

function tutupModal(){ $('#modalMK').hide(); }

$('#formMK').submit(function(e){
  e.preventDefault();
  let formData = new FormData(this);
  $.ajax({
    url:'proses_matakuliah.php',
    type:'POST',
    data:formData,
    contentType:false,
    processData:false,
    success:function(){
      alert('Data berhasil disimpan!');
      $('#modalMK').hide();
      location.reload();
    }
  });
});
</script>
