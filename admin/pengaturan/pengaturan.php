<?php 
session_start();
include '../../koneksi/sidebar.php'; 
include '../../koneksi/koneksi.php'; 

// Cek role admin
if(!isset($_SESSION['role']) || strtolower($_SESSION['role']) != 'admin'){
   header("Location: ../index.php"); exit;
}

// Helper agar aman tampilkan teks
function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

?>
<!-- CDN jQuery dan DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- jsPDF (biarkan ada, walau tidak dipakai di Users) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<style>
/* ====== CSS DARI HALAMAN DOSEN (DITERAPKAN KE USERS) ====== */
.konten-utama { margin-left:250px; margin-top:60px; padding:30px; min-height:calc(100vh - 60px); background:#f9f9f9; font-family:Arial,sans-serif; }
.konten-utama h2 { margin-bottom:20px; color:#333; }

.tombol { border:none; border-radius:5px; cursor:pointer; color:white; font-size:10px; transition:0.3s; }
.tombol:hover { opacity:0.85; }
.tombol-edit {
  background:#007bff;
  width:60px;
  padding:6px 10px;
  margin:3px auto;     /* center */
  display:block;       /* supaya margin auto bekerja */
  text-align:center;   /* ikon + teks rapi */
}

.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select { padding:6px 10px; border-radius:5px; border:1px solid #ccc; font-size:14px; margin-bottom:5px; }

.tabel-dosen { width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.1); table-layout:fixed; }
.tabel-dosen th { background:#00AEEF; color:#333; text-align:left; padding:12px 15px; }
.tabel-dosen td { padding:12px 15px; border-bottom:1px solid #ddd; border-right:1px solid #ddd; }
.tabel-dosen tr:hover { background:#f1f1f1; }

/* Modal (tetap simple seperti versi dosen) */
.kotak-modal { display:none; position:fixed; z-index:300; left:0; top:0; width:100%; height:100vh; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; }
.isi-modal { background:white; padding:25px; border-radius:10px; width:400px; max-width:90%; box-shadow:0 5px 15px rgba(0,0,0,.3); position:relative; text-align:center; }
.isi-modal h3 { margin-bottom:20px; }
.isi-modal input, .isi-modal select { width:100%; padding:10px; margin:5px 0; border:1px solid #ccc; border-radius:6px; }
.isi-modal button { width:100%; padding:10px; border:none; border-radius:6px; background:#007bff; color:white; font-weight:600; cursor:pointer; margin-top:10px; }
.isi-modal button:hover { background:#005fc3; }
.tutup-modal { position:absolute; top:15px; right:15px; cursor:pointer; font-size:18px; color:#666; }
.tutup-modal:hover { color:black; }

/* Tambahan kecil untuk ikon mata di input password (supaya rapi) */
.password-wrapper { position:relative; }
.password-wrapper input { padding-right:40px; }
.password-wrapper i {
  position:absolute; right:12px; top:50%; transform:translateY(-50%);
  cursor:pointer; color:#666; transition:0.3s;
}
.password-wrapper i:hover { color:#007bff; }

/* ====== Responsive sama seperti halaman dosen ====== */
@media screen and (max-width: 768px) {
  .konten-utama { margin-left:0; padding:20px; width:100%; background-color:#f9f9f9; text-align:center; }
  .konten-utama h2 { text-align:center; }

  .tabel-dosen, thead, tbody, th, td, tr { display:block; }
  thead tr { display:none; }

  tr { margin-bottom:15px; border-bottom:2px solid #000; }
  td { text-align:right; padding-left:50%; position:relative; }
  td::before { content: attr(data-label); position:absolute; left:15px; width:45%; font-weight:bold; text-align:left; }

  .tombol-edit {
    width:auto; padding:6px 10px; display:inline-block; margin:3px 2px; /* seperti dosen */
  }
}
</style>

<div class="konten-utama">
  <h2>Data Pengguna</h2>

  <!-- Tabel pakai kelas .tabel-dosen agar styling sama persis -->
  <table id="tabel-users" class="tabel-dosen">
    <thead>
      <tr>
        <th>No.</th>
        <th>Nama</th>
        <th>Username</th>
        <th>Password</th>
        <th>Role</th>
        <th>Dibuat</th>
        <th>Diperbarui</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $no=1;
      $q = mysqli_query($conn,"SELECT id, nama, username, password, role, created_at, updated_at FROM users ORDER BY id ASC");
      while($row = mysqli_fetch_assoc($q)) {
      ?>
      <tr>
        <td data-label="No"><?= $no++; ?></td>
        <td data-label="Nama"><?= e($row['nama']); ?></td>
        <td data-label="Username"><?= e($row['username']); ?></td>
        <td data-label="Password"><?= e($row['password']); ?></td>
        <td data-label="Role"><?= e($row['role']); ?></td>
        <td data-label="Dibuat"><?= e($row['created_at']); ?></td>
        <td data-label="Diperbarui"><?= e($row['updated_at']); ?></td>
        <td data-label="Aksi">
          <button class="tombol tombol-edit" onclick="editUser(<?= (int)$row['id']; ?>)">
            <i class="fa-solid fa-pen-to-square"></i> Edit
          </button>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
</div>

<!-- Modal Edit -->
<div id="modalUser" class="kotak-modal">
  <div class="isi-modal">
    <span class="tutup-modal" onclick="tutupModal()">&times;</span>
    <h3 id="judulModal">Edit Pengguna</h3>
    <form id="formUser" autocomplete="off">
      <input type="hidden" name="aksi" value="simpan">
      <input type="hidden" name="id" id="idUser">

      <label><b>Nama</b></label>
      <input type="text" name="nama" id="nama" placeholder="Nama lengkap" required>

      <label><b>Username</b></label>
      <input type="text" name="username" id="username" placeholder="Username login" required>

      <label><b>Password (kosongkan jika tidak ganti)</b></label>
      <div class="password-wrapper">
        <input type="password" name="password" id="password" placeholder="••••••••">
        <i id="togglePassword" class="fa-solid fa-eye"></i>
      </div>
    

      <label><b>Role</b></label>
      <select name="role" id="role" required>
        <option value="">Pilih Role</option>
        <option value="Admin">Admin</option>
        <option value="Dosen">Dosen</option>
        <option value="Mahasiswa">Mahasiswa</option>
      </select>

      <button type="submit" id="simpanUser">Simpan</button>
    </form>
  </div>
</div>

<script>
// DataTables (kolom 7 = Aksi tak bisa di-sort)
$(function(){
  $('#tabel-users').DataTable({
    pageLength: 10,
    lengthMenu: [5,10,25,50],
    columnDefs: [{ orderable:false, targets: 7 }],
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
      paginate: { first:"Pertama", last:"Terakhir", next:"Berikutnya", previous:"Sebelumnya" }
    }
  });
});

// Toggle show/hide password pada form modal
document.addEventListener('DOMContentLoaded', function(){
  const togglePassword = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('password');

  togglePassword.addEventListener('click', function(){
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    this.classList.toggle('fa-eye-slash');
  });
});

// Modal Edit
function editUser(id){
  $.post('proses_users.php', {aksi:'ambil', id:id}, function(raw){
    let obj = raw;
    if (typeof raw === 'string') { try { obj = JSON.parse(raw); } catch(e){} }
    if (obj && obj.id){
      $('#judulModal').text('Edit Pengguna');
      $('#idUser').val(obj.id);
      $('#nama').val(obj.nama);
      $('#username').val(obj.username);
      $('#password').val(''); // kosongkan (hanya ganti jika diisi)
      $('#role').val(obj.role);
      $('#modalUser').css('display','flex');
    } else {
      alert('Gagal mengambil data pengguna.');
    }
  }).fail(x=> alert('Error: '+(x.responseText||x.statusText)));
}

// Tutup Modal
function tutupModal(){ $('#modalUser').hide(); }

// Simpan
$('#formUser').on('submit', function(e){
  e.preventDefault();
  $.post('proses_users.php', $(this).serialize(), function(raw){
    let res = raw;
    if (typeof raw === 'string') { try { res = JSON.parse(raw); } catch(e){} }
    if(res && res.ok){
      $('#modalUser').hide();
      alert('Data tersimpan.');
      location.reload();
    } else {
      alert(res.error || 'Gagal menyimpan data.');
    }
  }).fail(x=> alert('Error: '+(x.responseText||x.statusText)));
});
</script>
