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
.konten-utama { 
  margin-left:250px; 
  margin-top:60px; 
  padding:30px; 
  min-height:calc(100vh - 60px); 
  background:#f9f9f9; 
  font-family:Arial,sans-serif; }

.konten-utama h2 { 
  margin-bottom:20px; 
  color:#333; }

.tombol { 
  border:none; 
  border-radius:5px; 
  cursor:pointer; 
  color:white; 
  font-size:10px; 
  transition:0.3s; }

.tombol:hover { 
  opacity:0.85; }

.tombol-edit { 
  background:#007bff; 
  width:60px; 
  margin-bottom:3px; 
  padding:6px 10px; }

.tombol-hapus { 
  background:#dc3545; 
  width:60px; 
  padding:6px 10px; }

.tombol-cetak { 
  background:#28a745; 
  margin-right:10px; 
  padding:8px 15px; }

.tombol-tambah { 
  background:#00b4ff; 
  margin-bottom:10px; 
  padding:8px 15px; }

.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select { 
  padding:6px 10px; 
  border-radius:5px; 
  border:1px solid #ccc; 
  font-size:14px; 
  margin-bottom:5px; }

.tabel-ruangan { 
  width:100%; 
  border-collapse:collapse; 
  background:white; 
  border-radius:10px; 
  overflow:hidden; 
  box-shadow:0 2px 6px rgba(0,0,0,0.1); 
  table-layout:fixed; }

.tabel-ruangan th { 
  background:#00AEEF; 
  color:#333; 
  text-align:left; 
  padding:12px 15px; }

.tabel-ruangan td { 
  padding:12px 15px; 
  border-bottom:1px solid #ddd; 
  border-right:1px solid #ddd; }

.tabel-ruangan tr:hover { 
  background:#f1f1f1; }

.kotak-modal { 
  display:none; 
  position:fixed; 
  z-index:300; 
  left:0; 
  top:0; 
  width:100%; 
  height:100vh; 
  background:rgba(0,0,0,0.6); 
  justify-content:center; 
  align-items:center; }

.isi-modal { 
  background:white; 
  padding:25px; 
  border-radius:10px; 
  width:400px; 
  max-width:90%; 
  box-shadow:0 5px 15px rgba(0,0,0,.3); 
  text-align:center; 
  position:relative; }

.isi-modal h3 { 
  margin-bottom:20px; }

.isi-modal input { 
  width:100%; 
  padding:10px; 
  margin:5px 0; 
  border:1px solid #ccc; 
  border-radius:6px; }

.isi-modal button { 
  width:100%; 
  padding:10px; 
  border:none; 
  border-radius:6px; 
  background:#007bff; 
  color:white; 
  font-weight:600; 
  cursor:pointer; 
  margin-top:10px; }

.isi-modal button:hover { 
  background:#005fc3; }

.tutup-modal { 
  position:absolute; 
  top:15px;
  right:15px; 
  cursor:pointer; 
  font-size:18px; 
  color:#666; }
.tutup-modal:hover { 
  color:black; }

@media screen and (max-width: 768px) {
  .konten-utama { 
    margin-left:0; 
    padding:20px; 
    width:100%; 
    background-color:#f9f9f9; 
    text-align:center; }

  .konten-utama h2 { 
    text-align:center; }
  .konten-utama .tombol-cetak, .konten-utama .tombol-tambah { 
    display:inline-block; 
    margin:5px auto; }

  .tabel-ruangan, thead, tbody, th, td, tr { 
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

  .tombol-edit, .tombol-hapus { 
    width:auto; 
    padding:6px 10px; 
    display:inline-block; 
    margin:3px 2px; }
}
</style>

<div class="konten-utama">
  <h2>Data Ruangan</h2>

  <button class="tombol tombol-cetak"><i class="fa-solid fa-print"></i> Cetak</button>
  <button class="tombol tombol-tambah" onclick="tambahRuangan()"><i class="fa-solid fa-plus"></i> Tambah</button>

  <table id="tabel-ruangan" class="tabel-ruangan">
    <thead>
      <tr>
        <th>No.</th>
        <th>Kode Ruangan</th>
        <th>Nama Ruangan</th>
        <th>Kapasitas</th>
        <th>Lokasi</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $no=1;
      $query = mysqli_query($conn,"SELECT * FROM ruangan ORDER BY id ASC");
      while($row=mysqli_fetch_assoc($query)) {
      ?>
      <tr>
        <td data-label="No."><?php echo $no++; ?></td>
        <td data-label="Kode Ruangan"><?php echo $row['kode_ruangan']; ?></td>
        <td data-label="Nama Ruangan"><?php echo $row['nama_ruangan']; ?></td>
        <td data-label="Kapasitas"><?php echo $row['kapasitas']; ?></td>
        <td data-label="Lokasi"><?php echo $row['lokasi']; ?></td>
        <td data-label="Aksi">
          <button class="tombol tombol-edit" onclick="editRuangan(<?php echo $row['id']; ?>)"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
          <button class="tombol tombol-hapus" onclick="hapusRuangan(<?php echo $row['id']; ?>)"><i class="fa-solid fa-trash"></i> Hapus</button>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
</div>

<!-- Modal Tambah/Edit -->
<div id="modalRuangan" class="kotak-modal">
  <div class="isi-modal">
    <span class="tutup-modal" onclick="tutupModal()">&times;</span>
    <h3 id="judulModal">Tambah Ruangan</h3>
    <form id="formRuangan">
      <input type="hidden" name="id" id="idRuangan">
      <input type="text" name="kode_ruangan" id="kode_ruangan" placeholder="Kode Ruangan" required>
      <input type="text" name="nama_ruangan" id="nama_ruangan" placeholder="Nama Ruangan" required>
      <input type="number" name="kapasitas" id="kapasitas" placeholder="Kapasitas" required>
      <input type="text" name="lokasi" id="lokasi" placeholder="Lokasi" required>
      <button type="submit" id="simpanRuangan">Simpan</button>
    </form>
  </div>
</div>

<script>
// DataTables
$(document).ready(function () {
    $('#tabel-ruangan').DataTable({
      "pageLength": 10,
      "lengthMenu": [5, 10, 25, 50],
      "columnDefs": [{
        "orderable": false,"targets": 5 }],
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
// Modal Tambah
function tambahRuangan() {
  $('#formRuangan')[0].reset();
  $('#judulModal').text('Tambah Ruangan');
  $('#modalRuangan').css('display','flex');
}

// Modal Edit
function editRuangan(id) {
  $.post('proses_ruangan.php', {aksi:'ambil',id:id}, function(data){
    let obj = JSON.parse(data);
    $('#judulModal').text('Edit Ruangan');
    $('#idRuangan').val(obj.id);
    $('#kode_ruangan').val(obj.kode_ruangan);
    $('#nama_ruangan').val(obj.nama_ruangan);
    $('#kapasitas').val(obj.kapasitas);
    $('#lokasi').val(obj.lokasi);
    $('#modalRuangan').css('display','flex');
  });
}

// Hapus
function hapusRuangan(id){
  if(confirm('Apakah Anda yakin ingin menghapus data ini?')){
    $.post('proses_ruangan.php',{aksi:'hapus',id:id}, function(){
      alert('Data berhasil dihapus!');
      location.reload();
    });
  }
}

// Tutup Modal
function tutupModal(){ $('#modalRuangan').hide(); }

// Submit Form
$('#formRuangan').submit(function(e){
  e.preventDefault();
  const id = $('#idRuangan').val();
  const pesan = id ? 'Data berhasil diubah!' : 'Data berhasil ditambahkan!';
  $.post('proses_ruangan.php', $(this).serialize(), function(){
    $('#modalRuangan').hide();
    alert(pesan);
    location.reload();
  });
});

// Cetak PDF
$('.tombol-cetak').click(function(){
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ orientation:'portrait', unit:'mm', format:'a4' });
  doc.setFontSize(14);
  doc.text("Data Ruangan", 105, 15, {align:"center"});

  let headers = [];
  $('#tabel-ruangan thead th').each(function(index){ if(index!==5) headers.push($(this).text()); });

  let data = [];
  $('#tabel-ruangan tbody tr').each(function(){
    let rowData=[];
    $(this).find('td').each(function(index){ if(index!==5) rowData.push($(this).text()); });
    data.push(rowData);
  });

  doc.autoTable({ head:[headers], body:data, startY:20, theme:'grid', headStyles:{fillColor:[139,201,255], textColor:20}, styles:{fontSize:10}, margin:{top:20} });
  doc.save('Data_Ruangan.pdf');
});
</script>
