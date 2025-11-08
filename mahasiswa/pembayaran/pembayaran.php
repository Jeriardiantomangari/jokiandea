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

// ✅ PAKAI mhs_id (bukan id_user)
$id_mahasiswa = (int)($_SESSION['mhs_id'] ?? 0);

// ✅ Ambil data mahasiswa berdasarkan id mahasiswa yang login
$mahasiswa = mysqli_query($conn, "SELECT * FROM mahasiswa WHERE id='$id_mahasiswa'");
$data_mahasiswa = mysqli_fetch_assoc($mahasiswa) ?: [];

// Semester aktif
$qSem = mysqli_query($conn, "SELECT id, nama_semester, tahun_ajaran FROM semester WHERE status='Aktif' LIMIT 1");
$semAktif = mysqli_fetch_assoc($qSem);
$id_semester_aktif = $semAktif['id'] ?? null;

// Cek sudah daftar di semester aktif saja
if ($id_semester_aktif) {
  $cek_pendaftaran = mysqli_query($conn, "
      SELECT COUNT(*) AS total 
      FROM kontrak_mk 
      WHERE id_mahasiswa='$id_mahasiswa' AND id_semester='$id_semester_aktif'
  ");
  $data_cek = mysqli_fetch_assoc($cek_pendaftaran);
  $sudah_daftar = (int)($data_cek['total'] ?? 0) > 0;
} else {
  $sudah_daftar = false; // tak ada semester aktif = tak boleh daftar
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Pendaftaran Praktikum</title>

  <!-- Pustaka jQuery dan DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

  <!-- Ikon FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
  /* Area konten utama */
  .konten_utama { margin-left:250px; margin-top:60px; padding:30px; min-height:calc(100vh - 60px); background:#f9f9f9; font-family:Arial,sans-serif; }
  .konten_utama h2 { margin-bottom:10px; color:#333; }

  /* Info semester */
.kotak_info{ padding:10px 12px; border-radius:8px; margin:8px 0; background:#fff; border:1px solid #e5e7eb; }
  .info_sukses { color:#333  }
  .info_peringatan { color :#ff5252;}

  /* Wadah tombol atas */
  .wadah_tombol { display:flex; width:100%; margin-bottom: 15px; }
  .tombol_tambah { background:#00b4ff; color:white; border:none; padding:8px 15px; border-radius:5px; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:0.3s; }
  .tombol_tambah:hover { background:#0096d6; }
  .tombol_tambah[disabled]{ opacity:.5; cursor:not-allowed; }

  /* Pesan selesai */
  .pesan_selesai { color:#333; font-size:15px; text-align:center; margin:0 auto; }

  /* Tombol aksi di tabel */
  .tombol_umum { border:none; border-radius:5px; cursor:pointer; color:white; font-size:10px; transition:0.3s; }
  .tombol_umum:hover { opacity:0.85; }
  .tombol_edit { background:#007bff; width:60px; margin-bottom:3px; padding:6px 10px; }
  .tombol_hapus { background:#dc3545; width:60px; padding:6px 10px; }

  /* Kontrol bawaan DataTables */
  .dataTables_wrapper .dataTables_filter input,
  .dataTables_wrapper .dataTables_length select { padding:6px 10px; border-radius:5px; border:1px solid #ccc; font-size:14px; margin-bottom:5px; }

  /* Tabel data */
  .tabel_data { width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.1); table-layout:fixed; }
  .tabel_data th { background:#00AEEF; color:#333; text-align:left; padding:12px 15px; }
  .tabel_data td { padding:12px 15px; border-bottom:1px solid #ddd; border-right:1px solid #ddd; }
  .tabel_data tr:hover { background:#f1f1f1; }

  /* Modal */
  .kotak_modal { display:none; position:fixed; z-index:300; left:0; top:0; width:100%; height:100vh; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; }
  .isi_modal { background:white; padding:25px; border-radius:10px; width:420px; max-width:90%; box-shadow:0 5px 15px rgba(0,0,0,.3); text-align:left; position:relative; }
  .isi_modal h3 { margin:0 0 12px 0; text-align:center; }
  .isi_modal input, .isi_modal select { width:100%; padding:10px; margin:5px 0; border:1px solid #ccc; border-radius:6px; }
  .isi_modal button { width:100%; padding:10px; border:none; border-radius:6px; background:#007bff; color:white; font-weight:600; cursor:pointer; margin-top:10px; }
  .isi_modal button:hover { background:#005fc3; }
  .tutup_modal { position:absolute; top:15px; right:15px; cursor:pointer; font-size:18px; color:#666; }
  .tutup_modal:hover { color:black; }

  /* Checkbox daftar MK */
  .wadah_checkbox { max-height: 250px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 8px; background-color: #fff; }
  .item_checkbox { display: flex; align-items: center; gap: 8px; padding: 4px 0; cursor: pointer; transition: background-color 0.2s ease; }
  .item_checkbox input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: #007bff; }

  /* Scrollbar custom */
  .wadah_checkbox::-webkit-scrollbar { width: 8px; }
  .wadah_checkbox::-webkit-scrollbar-track { background: #f9f9f9; }
  .wadah_checkbox::-webkit-scrollbar-thumb { background-color: #ccc; border-radius: 8px; }
  .wadah_checkbox::-webkit-scrollbar-thumb:hover { background-color: #aaa; }

  /* Sel ikon pembayaran */
  .sel_pembayaran { text-align:center; }
  .sel_pembayaran a i { font-size:30px !important; color:#dc3545; }

  /* Responsif mobile */
  @media screen and (max-width: 768px) {
    .konten_utama { margin-left:0; padding:20px; width:100%; text-align:center; }
    .konten_utama h2 { text-align:center; }
    .tabel_data, thead, tbody, th, td, tr { display:block; }
    thead tr { display:none; }
    tr { margin-bottom:15px; border-bottom:2px solid #000; }
    td { text-align:right; padding-left:50%; position:relative; }
    td::before { content: attr(data-label); position:absolute; left:15px; width:45%; font-weight:bold; text-align:left; }
    .sel_pembayaran { text-align:right; }
  }
  </style>
</head>
<body>
<div class="konten_utama">
  <h2>Pendaftaran Praktikum</h2>

  <?php if($id_semester_aktif): ?>
    <div class="kotak_info info_sukses">
      Semester: <b><?= e($semAktif['nama_semester']) ?></b><b>/</b><b><?= e($semAktif['tahun_ajaran']) ?></b>
    </div>
  <?php else: ?>
    <div class="kotak_info info_peringatan">
      Belum ada <b>Semester</b> berstatus <b>Aktif</b>. Pendaftaran dinonaktifkan.
    </div>
  <?php endif; ?>

  <?php if($id_semester_aktif && !$sudah_daftar): ?>
    <div class="wadah_tombol" style="justify-content:flex-start;">
      <button class="tombol_tambah" onclick="tambahPendaftaran()">
        <i class="fa-solid fa-plus"></i> Tambah
      </button>
    </div>
  <?php elseif($id_semester_aktif && $sudah_daftar): ?>
    <div class="wadah_tombol" style="justify-content:center;">
      <p class="pesan_selesai">Anda telah mengirimkan kontrak & bukti pembayaran untuk semester aktif ini.</p>
    </div>
  <?php else: ?>
    <div class="wadah_tombol" style="justify-content:flex-start;">
      <button class="tombol_tambah" disabled>
        <i class="fa-solid fa-plus"></i> Tambah
      </button>
    </div>
  <?php endif; ?>

  <table id="tabel-pendaftaran" class="tabel_data">
    <thead>
      <tr>
        <th>No</th>
        <th>Semester</th>
        <th>NIM</th>
        <th>Nama</th>
        <th>No HP</th>
        <th>MK Dikontrak</th>
        <th>Status</th>
        <th>Bukti Pembayaran</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $no=1;
      // Tampilkan seluruh riwayat kontrak mahasiswa (terbaru di atas)
      $query = mysqli_query($conn,"
        SELECT k.*, s.nama_semester, s.tahun_ajaran
        FROM kontrak_mk k
        LEFT JOIN semester s ON s.id = k.id_semester
        WHERE k.id_mahasiswa='$id_mahasiswa'
        ORDER BY k.id DESC
      ");
      while($row=mysqli_fetch_assoc($query)) {
      ?>
      <tr>
        <td data-label="No"><?= $no++; ?></td>
        <td data-label="Semester"><?= e(($row['nama_semester'] ?? '').' '.($row['tahun_ajaran'] ?? '')); ?></td>
        <td data-label="NIM"><?= e($row['nim']); ?></td>
        <td data-label="Nama"><?= e($row['nama']); ?></td>
        <td data-label="No HP"><?= e($row['no_hp']); ?></td>
        <td data-label="MK Dikontrak"><?= e(str_replace(',', ', ', $row['mk_dikontrak'])); ?></td>
        <td data-label="Status">
          <?php 
            if ($row['status'] == 'Disetujui') echo "<span style='color:green; font-weight:bold;'>Disetujui</span>";
            elseif ($row['status'] == 'Ditolak') echo "<span style='color:red; font-weight:bold;'>Ditolak</span>";
            else echo "<span style='color:#999;'>Menunggu</span>";
          ?>
        </td>
        <td data-label="Bukti Pembayaran" class="sel_pembayaran">
          <?php if(!empty($row['bukti_pembayaran'])){ ?>
            <a href='../../uploads/<?= e($row['bukti_pembayaran']); ?>' target='_blank' title="Lihat Bukti">
              <i class="fa-solid fa-file-arrow-down"></i>
            </a>
          <?php } else { ?>
            <span style="color:#aaa;">Belum upload</span>
          <?php } ?>
        </td>
        <td data-label="Aksi">
          <?php 
            // Hanya data di semester aktif & status != Disetujui yang bisa diedit/hapus
            $editable = ($row['id_semester'] == $id_semester_aktif && $row['status'] != 'Disetujui');
            if ($editable) { ?>
              <button class="tombol_umum tombol_edit" onclick="editPendaftaran(<?= (int)$row['id']; ?>)">
                <i class="fa-solid fa-pen-to-square"></i> Edit
              </button>
              <button class="tombol_umum tombol_hapus" onclick="hapusPendaftaran(<?= (int)$row['id']; ?>)">
                <i class="fa-solid fa-trash"></i> Hapus
              </button>
          <?php } else { echo "<span style='color:#666;'>-</span>"; } ?>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
</div>

<!-- Modal Tambah/Edit -->
<div id="modalPendaftaran" class="kotak_modal">
  <div class="isi_modal">
    <span class="tutup_modal" onclick="tutupModal()">&times;</span>
    <h3 id="judulModal">Tambah Pendaftaran</h3>
    <form id="formPendaftaran" enctype="multipart/form-data">
      <input type="hidden" name="aksi" value="simpan">
      <input type="hidden" name="id" id="idPendaftaran">
      <input type="hidden" name="id_mahasiswa" value="<?= (int)($data_mahasiswa['id'] ?? 0); ?>">

      <label>NIM</label>
      <input type="text" name="nim" id="nim" value="<?= e($data_mahasiswa['nim'] ?? ''); ?>" readonly>

      <label>Nama</label>
      <input type="text" name="nama" id="nama" value="<?= e($data_mahasiswa['nama'] ?? ''); ?>" readonly>

      <label>No HP</label>
      <input type="text" name="no_hp" id="no_hp" value="<?= e($data_mahasiswa['no_hp'] ?? ''); ?>" readonly>

      <label class="judul">Mata Kuliah Dikontrak:</label>
      <div id="mkCheckbox" class="wadah_checkbox">
        <?php
          // Ambil daftar mata kuliah (prioritaskan berdasarkan id_semester jika ada)
          if ($id_semester_aktif) {
            $query_mk = mysqli_query($conn, "
              SELECT * FROM matakuliah_praktikum 
              WHERE (id_semester IS NULL OR id_semester='$id_semester_aktif')
              ORDER BY nama_mk ASC
            ");
          } else {
            $query_mk = mysqli_query($conn, "
              SELECT * FROM matakuliah_praktikum 
              ORDER BY nama_mk ASC
            ");
          }

          // Tampilkan checkbox untuk tiap mata kuliah
          while ($mk = mysqli_fetch_assoc($query_mk)) {
            echo '
              <label class="item_checkbox">
                <input type="checkbox" name="mk_dikontrak[]" value="'.e($mk['nama_mk']).'">
                <span>'.e($mk['nama_mk']).'</span>
              </label>
            ';
          }
        ?>
      </div>

      <label>Bukti Pembayaran (pdf/jpg/png, maks 2MB)</label>
      <input type="file" name="bukti_pembayaran" accept=".pdf,.jpg,.jpeg,.png">

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
// Inisialisasi DataTables
$(document).ready(function () {
  $('#tabel-pendaftaran').DataTable({
    pageLength: 10,
    lengthMenu: [5, 10, 25, 50],
    columnDefs: [{ orderable: false, targets: 8 }],
    language: {
      emptyTable: "Tidak ada data tersedia",
      info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
      infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
      lengthMenu: "Tampilkan _MENU_ data",
      search: "Cari:",
      zeroRecords: "Tidak ditemukan data yang sesuai",
      paginate: { next: "Berikutnya", previous: "Sebelumnya" }
    }
  });
});

// Aksi: buka modal tambah pendaftaran
function tambahPendaftaran(){
  <?php if(!$id_semester_aktif){ ?>
    alert('Belum ada semester Aktif.');
    return;
  <?php } ?>
  $('#formPendaftaran')[0].reset();
  $('#judulModal').text('Tambah Pendaftaran');
  $('#idPendaftaran').val('');
  $('#mkCheckbox input').prop('checked', false);
  $('#modalPendaftaran').css('display','flex');
}

// Aksi: buka modal edit pendaftaran
function editPendaftaran(id){
  $.post('proses_pendaftaran.php',{aksi:'ambil',id:id},function(data){
    let obj = {};
    try { obj = JSON.parse(data); } catch(e){ alert('Gagal memuat data'); return; }

    $('#judulModal').text('Edit Pendaftaran');
    $('#idPendaftaran').val(obj.id);
    $('#mkCheckbox input').prop('checked', false);
    if(obj.mk_dikontrak){
      obj.mk_dikontrak.split(',').forEach(function(mk){
        $('#mkCheckbox input[value="'+mk.trim()+'"]').prop('checked', true);
      });
    }
    $('#modalPendaftaran').css('display','flex');
  });
}

// Aksi: hapus pendaftaran
function hapusPendaftaran(id){
  if(confirm('Apakah Anda yakin ingin menghapus data ini?')){
    $.post('proses_pendaftaran.php',{aksi:'hapus',id:id},function(res){
      res = (res||'').toString().trim();
      if(res.startsWith('error|')){ alert(res.split('|')[1]||'Gagal menghapus'); }
      else { alert('Data berhasil dihapus!'); location.reload(); }
    });
  }
}

// Aksi: tutup modal
function tutupModal(){ $('#modalPendaftaran').hide(); }

// Submit form pendaftaran (AJAX)
$('#formPendaftaran').submit(function(e){
  e.preventDefault();

  // Minimal 1 MK dipilih
  if ($('#mkCheckbox input:checked').length === 0){
    alert('Pilih minimal 1 mata kuliah.');
    return;
  }

  var formData = new FormData(this);
  $.ajax({
    url: 'proses_pendaftaran.php',
    type: 'POST',
    data: formData,
    contentType: false,
    processData: false,
    success: function(res){
      res = (res||'').toString().trim();
      if(res.startsWith('error|')){
        alert(res.split('|')[1]||'Terjadi kesalahan');
      } else {
        alert(res.replace('ok|','') || 'Data berhasil disimpan!');
        $('#modalPendaftaran').hide();
        location.reload();
      }
    },
    error: function(){ alert('Gagal mengirim data'); }
  });
});
</script>
</body>
</html>
