<?php 
session_start();
include '../../koneksi/sidebar.php'; 
include '../../koneksi/koneksi.php'; 

// Cek role
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
    header("Location: ../login.php");
    exit;
}
?>

<!-- CDN jQuery dan DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.konten-utama {
  margin-left:250px;
  margin-top:60px;
  padding:30px;
  min-height:calc(100vh - 60px);
  background:#f9f9f9;
  font-family:Arial,sans-serif;
}
.konten-utama h2 { margin-bottom:20px; color:#333; }

.tombol { border:none; border-radius:5px; cursor:pointer; color:white; transition:0.3s; }
.tombol:hover { opacity:0.85; }
.tombol-edit { background:#007bff; width:60px; margin-bottom:3px; padding:6px 10px; font-size:12px; }
.tombol-hapus { background:#dc3545; width:60px; padding:6px 10px; font-size:12px; }
.tombol-tambah { background:#00b4ff; margin-bottom:10px; padding:8px 15px; font-size:14px; display:flex; align-items:center; gap:5px; }

.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select { padding:6px 10px; border-radius:5px; border:1px solid #ccc; font-size:14px; margin-bottom:5px; }

.tabel-semester { width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.1); table-layout:fixed; }
.tabel-semester th {
  background:#8bc9ff;
  color:#333;
  padding:12px 15px;
}
.tabel-semester td {
  padding:12px 15px;
  border-bottom:1px solid #ddd;
}

.kotak-modal {
  display:none;
  position:fixed;
  z-index:300;
  left:0; top:0;
  width:100%; height:100vh;
  background:rgba(0,0,0,0.6);
  justify-content:center;
  align-items:center;
}
.isi-modal {
  background:white;
  padding:25px;
  border-radius:10px;
  width:400px;
  max-width:90%;
  box-shadow:0 5px 15px rgba(0,0,0,.3);
  text-align:center;
  position:relative;
}
.isi-modal h3 { margin-bottom:20px; }
.isi-modal input, .isi-modal select {
  width:100%;
  padding:10px;
  margin:5px 0;
  border:1px solid #ccc;
  border-radius:6px;
}
.isi-modal button {
  width:100%;
  padding:10px;
  border:none;
  border-radius:6px;
  background:#007bff;
  color:white;
  font-weight:600;
  cursor:pointer;
  margin-top:10px;
}
.isi-modal button:hover { background:#005fc3; }
.tutup-modal {
  position:absolute;
  top:15px;
  right:15px;
  cursor:pointer;
  font-size:18px;
  color:#666;
}
.tutup-modal:hover { color:black; }

/* Responsive untuk mobile */
  @media screen and (max-width: 768px) {
    .konten-utama {
      margin-left: 0;
      padding: 20px;
      width: 100%;
      background-color: #f9f9f9;
      text-align: center;
    }

    .konten-utama h2 {
      text-align: center;
    }

    .konten-utama .tombol-cetak,
    .konten-utama .tombol-tambah {
      display: inline-block;
      margin: 5px auto;
    }

    .tabel-semester,
    thead,
    tbody,
    th,
    td,
    tr {
      display: block;
    }

    thead tr {
      display: none;
    }

    tr {
      margin-bottom: 15px;
      border-bottom: 2px solid #000;
    }

    td {
      text-align: right;
      padding-left: 50%;
      position: relative;
    }

    td::before {
      content: attr(data-label);
      position: absolute;
      left: 15px;
      width: 45%;
      font-weight: bold;
      text-align: left;
    }

    .tombol-edit,
    .tombol-hapus {
      width: auto;
      padding: 6px 10px;
      display: inline-block;
      margin: 3px 2px;
    }

  }
</style>

<div class="konten-utama">
  <h2>Data Semester</h2>

  <div style="display:flex; justify-content:flex-start; align-items:center; margin-bottom:15px;">
    <button class="tombol tombol-tambah" onclick="tambahSemester()">
      <i class="fa-solid fa-plus"></i> Tambah Semester
    </button>
  </div>

  <table id="tabel-semester" class="tabel-semester">
    <thead>
      <tr>
        <th>No.</th>
        <th>Nama Semester</th>
        <th>Tahun Ajaran</th>
        <th>Status</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $no = 1;
      $query = mysqli_query($conn, "SELECT * FROM semester ORDER BY id DESC");
      while($row = mysqli_fetch_assoc($query)) {
      ?>
      <tr>
        <td data-label="No"><?php echo $no++; ?></td>
        <td data-label="Nama Semester"><?php echo htmlspecialchars($row['nama_semester']); ?></td>
        <td data-label="Tahun Ajaran"><?php echo htmlspecialchars($row['tahun_ajaran']); ?></td>
        <td data-label="Status"><?php echo htmlspecialchars($row['status']); ?></td>
        <td data-label="Aksi">
          <button class="tombol tombol-edit" onclick="editSemester(<?php echo $row['id']; ?>)">
            <i class="fa-solid fa-pen-to-square"></i>
          </button>
          <button class="tombol tombol-hapus" onclick="hapusSemester(<?php echo $row['id']; ?>)">
            <i class="fa-solid fa-trash"></i>
          </button>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
</div>

<!-- Modal Tambah/Edit -->
<div id="modalSemester" class="kotak-modal">
  <div class="isi-modal">
    <span class="tutup-modal" onclick="tutupModal()">&times;</span>
    <h3 id="judulModal">Tambah Semester</h3>
    <form id="formSemester">
      <input type="hidden" name="id" id="idSemester">
      <input type="text" name="nama_semester" id="namaSemester" placeholder="Nama Semester" required>
      <input type="text" name="tahun_ajaran" id="tahunAjaran" placeholder="Tahun Ajaran (contoh: 2025/2026)" required>
      <select name="status" id="status" required>
        <option value="">Pilih Status</option>
        <option value="Aktif">Aktif</option>
        <option value="Tidak Aktif">Tidak Aktif</option>
      </select>
      <button type="submit" id="simpanSemester">Simpan</button>
    </form>
  </div>
</div>

<script>
// DataTables
$(document).ready(function () {
  $('#tabel-semester').DataTable({
    pageLength: 10,
    lengthMenu: [5, 10, 25, 50],
    columnDefs: [{ orderable: false, targets: 4 }],
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

// Modal Tambah
function tambahSemester(){
  $('#formSemester')[0].reset();
  $('#judulModal').text('Tambah Semester');
  $('#modalSemester').css('display','flex');
}

// Modal Edit
function editSemester(id){
  $.post('proses_semester.php', {aksi:'ambil', id:id}, function(data){
    let obj = {};
    try { obj = (typeof data === 'string') ? JSON.parse(data) : data; } catch(e){}
    $('#judulModal').text('Edit Semester');
    $('#idSemester').val(obj.id || '');
    $('#namaSemester').val(obj.nama_semester || '');
    $('#tahunAjaran').val(obj.tahun_ajaran || '');
    $('#status').val(obj.status || '');
    $('#modalSemester').css('display','flex');
  }).fail(function(xhr){
    alert(xhr.responseText || 'Gagal mengambil data.');
  });
}

// Hapus
function hapusSemester(id){
  if(confirm('Hapus semester ini?\nSemua KONTRAK, PILIHAN SHIFT, dan JADWAL di semester ini ikut terhapus (CASCADE).')){
    $.post('proses_semester.php', {aksi:'hapus', id:id}, function(res){
      if ((res||'').trim() === 'OK') {
        alert('Semester berhasil dihapus!');
        location.reload();
      } else {
        alert(res || 'Gagal menghapus semester.');
      }
    }).fail(function(xhr){
      alert(xhr.responseText || 'Gagal menghapus semester.');
    });
  }
}

// Tutup Modal
function tutupModal(){ $('#modalSemester').hide(); }

// Submit Form
$('#formSemester').submit(function(e){
  e.preventDefault();

  const status = $('#status').val();
  if (status === 'Tidak Aktif') {
    const yakin = confirm(
      'Mengubah status ke "Tidak Aktif" akan MENGOSONGKAN semua data pendaftaran (kontrak, pilihan shift, dan jadwal) pada semester ini.\nLanjutkan?'
    );
    if (!yakin) return;
  }

  $.post('proses_semester.php', $(this).serialize(), function(res){
    if ((res||'').trim() === 'OK') {
      $('#modalSemester').hide();
      alert('Data berhasil disimpan!');
      location.reload();
    } else {
      alert(res || 'Gagal menyimpan data.');
    }
  }).fail(function(xhr){
    alert(xhr.responseText || 'Gagal menyimpan data.');
  });
});
</script>

