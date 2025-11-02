<?php 
session_start();
include '../../koneksi/sidebarmhs.php'; 
include '../../koneksi/koneksi.php'; 

// Pastikan mahasiswa sudah login
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'mahasiswa'){
    header("Location: ../login.php");
    exit;
}

// Ambil data mahasiswa yang login
$id_mahasiswa = $_SESSION['id_user'];
$mahasiswa = mysqli_query($conn, "SELECT * FROM mahasiswa WHERE id='$id_mahasiswa'");
$data_mahasiswa = mysqli_fetch_assoc($mahasiswa);
?>

<!-- CDN jQuery dan DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* ==== Style sama seperti sebelumnya ==== */
.konten-utama { margin-left:250px; margin-top:60px; padding:30px; min-height:calc(100vh - 60px); background:#f9f9f9; font-family:Arial,sans-serif; }
.konten-utama h2 { margin-bottom:20px; color:#333; }
.tombol { border:none; border-radius:5px; cursor:pointer; color:white; font-size:10px; transition:0.3s; }
.tombol:hover { opacity:0.85; }
.tombol-edit { background:#007bff; width:60px; margin-bottom:3px; padding:6px 10px; }
.tombol-hapus { background:#dc3545; width:60px; padding:6px 10px; }
.tombol-tambah { background:#00b4ff; margin-bottom:10px; padding:8px 15px; }

.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select { padding:6px 10px; border-radius:5px; border:1px solid #ccc; font-size:14px; margin-bottom:5px; }

.tabel-data { width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.1); table-layout:fixed; }
.tabel-data th { background:#8bc9ff; color:#333; text-align:left; padding:12px 15px; }
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

.pembayaran-cell {
    text-align: center;
}
.pembayaran-cell a i {
    font-size: 30px !important;
}

@media screen and (max-width: 768px) {
  .konten-utama { margin-left:0; padding:20px; width:100%; background-color:#f9f9f9; text-align:center; }
  .konten-utama h2 { text-align:center; }
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
  <h2>Pendaftaran Praktikum</h2>

    <button class="tombol tombol-tambah" onclick="tambahPendaftaran()"><i class="fa-solid fa-plus"></i> Tambah</button>

  <table id="tabel-pendaftaran" class="tabel-data">
    <thead>
      <tr>
        <th>No</th>
        <th>NIM</th>
        <th>Nama</th>
        <th>No HP</th>
        <th>MK Dikontak</th>
        <th>Status</th>
        <th>Bukti Pembayaran</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $no=1;
      $query = mysqli_query($conn,"SELECT * FROM kontrak_mk WHERE id_mahasiswa='$id_mahasiswa' ORDER BY id DESC");
      while($row=mysqli_fetch_assoc($query)) {
      ?>
      <tr>
        <td data-label="No"><?= $no++; ?></td>
        <td data-label="Nim"><?= $row['nim']; ?></td>
        <td data-label="Nama"><?= $row['nama']; ?></td>
        <td data-label="No Hp"><?= $row['no_hp']; ?></td>
        <td data-label="MK Dikontrak"><?= str_replace(',', ', ', $row['mk_dikontrak']); ?></td>
        <td data-label="Status">
          <?php 
            if ($row['status'] == 'Disetujui') echo "<span style='color:green; font-weight:bold;'>Disetujui</span>";
            elseif ($row['status'] == 'Ditolak') echo "<span style='color:red; font-weight:bold;'>Ditolak</span>";
            else echo "<span style='color:#999;'>Menunggu</span>";
          ?>
        </td>
        <td data-label="Bukti Pembayaran" class="pembayaran-cell">
          <?php if(!empty($row['bukti_pembayaran'])){   ?>
          <a href='../../uploads/<?= $row['bukti_pembayaran']; ?>' target='_blank' title="Lihat Bukti">
           <i class="fa-solid fa-file-arrow-down" style="font-size:20px; color:#dc3545;"></i>
           <?php } else { ?>
  <span style="color:#aaa;">Belum upload</span>
<?php } ?>
       
        </td>
        <td data-label="Aksi">
          <button class="tombol tombol-edit" onclick="editPendaftaran(<?= $row['id']; ?>)">
            <i class="fa-solid fa-pen-to-square"></i> Edit
          </button>
          <button class="tombol tombol-hapus" onclick="hapusPendaftaran(<?= $row['id']; ?>)">
            <i class="fa-solid fa-trash"></i> Hapus
          </button>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
</div>

<!-- Modal Tambah/Edit -->
<div id="modalPendaftaran" class="kotak-modal">
  <div class="isi-modal">
    <span class="tutup-modal" onclick="tutupModal()">&times;</span>
    <h3 id="judulModal">Tambah Pendaftaran</h3>
    <form id="formPendaftaran" enctype="multipart/form-data">
      <input type="hidden" name="id" id="idPendaftaran">
      <input type="hidden" name="id_mahasiswa" value="<?= $data_mahasiswa['id']; ?>">

      <input type="text" name="nim" id="nim" placeholder="NIM" value="<?= $data_mahasiswa['nim']; ?>" readonly>
      <input type="text" name="nama" id="nama" placeholder="Nama" value="<?= $data_mahasiswa['nama']; ?>" readonly>
      <input type="text" name="no_hp" id="no_hp" placeholder="No HP" value="<?= $data_mahasiswa['no_hp']; ?>" readonly>

      <label>Mata Kuliah Dikontak:</label>
      <div id="mkCheckbox">
        <?php
          $query_mk = mysqli_query($conn, "SELECT * FROM matakuliah_praktikum ORDER BY nama_mk ASC");
          while($mk = mysqli_fetch_assoc($query_mk)){
              echo '<label style="display:block;margin:3px 0;">
                      <input type="checkbox" name="mk_dikontrak[]" value="'.$mk['nama_mk'].'"> '.$mk['nama_mk'].'
                    </label>';
          }
        ?>
      </div>

      <input type="file" name="bukti_pembayaran" accept=".pdf,.jpg,.png">

      <select name="status" id="status" style="display:none;">
        <option value="Menunggu" selected>Menunggu</option>
        <option value="Disetujui">Disetujui</option>
        <option value="Ditolak">Ditolak</option>
      </select>

      <button type="submit">Simpan</button>
    </form>
  </div>
</div>

<script>
// DataTables
$(document).ready(function () {
    $('#tabel-pendaftaran').DataTable({
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

function tambahPendaftaran(){
  $('#formPendaftaran')[0].reset();
  $('#judulModal').text('Tambah Pendaftaran');
  $('#idPendaftaran').val('');
  $('#mkCheckbox input').prop('checked', false);
  $('#modalPendaftaran').css('display','flex');
}

function editPendaftaran(id){
  $.post('proses_pendaftaran.php',{aksi:'ambil',id:id},function(data){
    let obj = JSON.parse(data);
    $('#judulModal').text('Edit Pendaftaran');
    $('#idPendaftaran').val(obj.id);

    // Reset checkbox
    $('#mkCheckbox input').prop('checked', false);

    // Centang checkbox sesuai data
    if(obj.mk_dikontrak){
      obj.mk_dikontrak.split(',').forEach(function(mk){
        $('#mkCheckbox input[value="'+mk.trim()+'"]').prop('checked', true);
      });
    }

    $('#modalPendaftaran').css('display','flex');
  });
}

function hapusPendaftaran(id){
  if(confirm('Apakah Anda yakin ingin menghapus data ini?')){
    $.post('proses_pendaftaran.php',{aksi:'hapus',id:id},function(){
      alert('Data berhasil dihapus!');
      location.reload();
    });
  }
}

function tutupModal(){
  $('#modalPendaftaran').hide();
}

$('#formPendaftaran').submit(function(e){
  e.preventDefault();
  var formData = new FormData(this);
  $.ajax({
    url: 'proses_pendaftaran.php',
    type: 'POST',
    data: formData,
    contentType: false,
    processData: false,
    success: function(){
      alert('Data berhasil disimpan!');
      $('#modalPendaftaran').hide();
      location.reload();
    }
  });
});
</script>
