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
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Jadwal Praktikum</title>

  <!-- CDN jQuery & DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
  .konten-utama { margin-left:250px; margin-top:60px; padding:30px; min-height:calc(100vh - 60px); background:#f9f9f9; font-family:Arial,sans-serif; }
  .konten-utama h2 { margin-bottom:10px; color:#333; }
  .info-sem { margin-bottom:15px; padding:10px 12px; border-radius:6px; }
  .info-aktif { background:#e7f7e9; color:#096a2e; border:1px solid #bde5c8; }
  .info-none { background:#fff3cd; color:#7a5b00; border:1px solid #ffe08a; }

  .tombol { border:none; border-radius:5px; cursor:pointer; color:white; font-size:12px; transition:0.3s; }
  .tombol:hover { opacity:0.9; }
  .tombol[disabled] { opacity:0.5; cursor:not-allowed; }
  .tombol-edit { background:#007bff; width:60px; margin-bottom:3px; padding:6px 10px; }
  .tombol-hapus { background:#dc3545; width:60px; padding:6px 10px; }
  .tombol-tambah { background:#00b4ff; margin-bottom:10px; padding:8px 15px; }

  .dataTables_wrapper .dataTables_filter input,
  .dataTables_wrapper .dataTables_length select { padding:6px 10px; border-radius:5px; border:1px solid #ccc; font-size:14px; margin-bottom:5px; }
  .tabel-jadwal { width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.1); table-layout:fixed; }
  .tabel-jadwal th { background:#8bc9ff; color:#333; text-align:left; padding:12px 15px; }
  .tabel-jadwal td { padding:12px 15px; border-bottom:1px solid #ddd; border-right:1px solid #ddd; }
  .tabel-jadwal tr:hover { background:#f1f1f1; }

  .kotak-modal { display:none; position:fixed; z-index:300; left:0; top:0; width:100%; height:100vh; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; }
  .isi-modal { background:white; padding:25px; border-radius:10px; width:450px; max-width:90%; box-shadow:0 5px 15px rgba(0,0,0,.3); text-align:center; position:relative; }
  .isi-modal h3 { margin-bottom:20px; }
  .isi-modal input, .isi-modal select { width:100%; padding:10px; margin:5px 0; border:1px solid #ccc; border-radius:6px; }
  .isi-modal button { width:100%; padding:10px; border:none; border-radius:6px; background:#007bff; color:white; font-weight:600; cursor:pointer; margin-top:10px; }
  .isi-modal button:hover { background:#005fc3; }
  .tutup-modal { position:absolute; top:15px; right:15px; cursor:pointer; font-size:18px; color:#666; }
  .tutup-modal:hover { color:black; }

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
</head>
<body>
<div class="konten-utama">
  <h2>Jadwal Praktikum</h2>

  <?php if($id_semester_aktif): ?>
    <div class="info-sem info-aktif">
      Semester Aktif: <b><?= e($semAktif['nama_semester']) ?></b> — <b><?= e($semAktif['tahun_ajaran']) ?></b>
    </div>
  <?php else: ?>
    <div class="info-sem info-none">
      Belum ada <b>Semester</b> berstatus <b>Aktif</b>. Silakan aktifkan/ tambahkan semester terlebih dahulu pada menu Semester.
    </div>
  <?php endif; ?>

  <button class="tombol tombol-tambah" onclick="tambahJadwal()" <?= $id_semester_aktif ? '' : 'disabled' ?>>
    <i class="fa-solid fa-plus"></i> Tambah
  </button>

  <table id="tabel-jadwal" class="tabel-jadwal">
    <thead>
      <tr>
        <th>No.</th>
        <th>Kode MK</th>
        <th>Nama MK</th>
        <th>Hari</th>
        <th>Jam</th>
        <th>Pengajar</th>
        <th>Ruangan</th>
        <th>Kuota Awal</th>
        <th>Peserta</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
<?php
$no = 1;

if ($id_semester_aktif) {
    $query = mysqli_query($conn, "
        SELECT jp.*, mk.kode_mk, mk.nama_mk, d.nama as pengajar, r.nama_ruangan,
               (SELECT COUNT(*) FROM pilihan_jadwal pj WHERE pj.id_jadwal = jp.id) as peserta
        FROM jadwal_praktikum jp
        LEFT JOIN matakuliah_praktikum mk ON jp.id_mk = mk.id
        LEFT JOIN dosen d ON jp.id_dosen = d.id
        LEFT JOIN ruangan r ON jp.id_ruangan = r.id
        WHERE jp.id_semester = {$id_semester_aktif}
        ORDER BY jp.hari, jp.jam_mulai ASC, jp.id ASC
    ");
} else {
    // hasil kosong aman
    $query = mysqli_query($conn, "SELECT 1 WHERE 1=0");
}

while($row = mysqli_fetch_assoc($query)) {
    $mulai   = isset($row['jam_mulai'])   ? date('H:i', strtotime($row['jam_mulai']))   : '00:00';
    $selesai = isset($row['jam_selesai']) ? date('H:i', strtotime($row['jam_selesai'])) : '00:00';
?>
<tr>
    <td data-label="No"><?= $no++; ?></td>
    <td data-label="Kode MK"><?= e($row['kode_mk'] ?? '') ?></td>
    <td data-label="Nama MK"><?= e($row['nama_mk'] ?? '') ?></td>
    <td data-label="Hari"><?= e($row['hari'] ?? '') ?></td>
    <td data-label="Jam"><?= $mulai . ' - ' . $selesai; ?></td>
    <td data-label="Pengajar"><?= e($row['pengajar'] ?? '') ?></td>
    <td data-label="Ruangan"><?= e($row['nama_ruangan'] ?? '') ?></td>
    <td data-label="Kuota Awal"><?= (int)($row['kuota_awal'] ?? 0); ?></td>
    <td data-label="Peserta"><?= (int)($row['peserta'] ?? 0); ?></td>
    <td data-label="Aksi">
        <button class="tombol tombol-edit" onclick="editJadwal(<?= (int)$row['id']; ?>)">
            <i class="fa-solid fa-pen-to-square"></i> Edit
        </button>
        <button class="tombol tombol-hapus" onclick="hapusJadwal(<?= (int)$row['id']; ?>)">
            <i class="fa-solid fa-trash"></i> Hapus
        </button>
    </td>
</tr>
<?php } ?>
</tbody>
  </table>
</div>

<!-- Modal Tambah/Edit Jadwal -->
<div id="modalJadwal" class="kotak-modal">
  <div class="isi-modal">
    <span class="tutup-modal" onclick="tutupModal()">&times;</span>
    <h3 id="judulModal">Tambah Jadwal Praktikum</h3>
    <form id="formJadwal">
      <input type="hidden" name="aksi" value="simpan">
      <input type="hidden" name="id" id="idJadwal">

      <select name="id_mk" id="idMK" required>
        <option value="">Pilih Mata Kuliah</option>
        <?php
        $mk = mysqli_query($conn,"SELECT * FROM matakuliah_praktikum ORDER BY nama_mk ASC");
        while($rowMK = mysqli_fetch_assoc($mk)){
            echo "<option value='".(int)$rowMK['id']."'>".e($rowMK['kode_mk'])." - ".e($rowMK['nama_mk'])."</option>";
        }
        ?>
      </select>

      <select name="id_dosen" id="idDosen" required>
        <option value="">Pilih Pengajar</option>
        <?php
        $dosen = mysqli_query($conn,"SELECT * FROM dosen ORDER BY nama ASC");
        while($rowDosen = mysqli_fetch_assoc($dosen)){
            echo "<option value='".(int)$rowDosen['id']."'>".e($rowDosen['nama'])."</option>";
        }
        ?>
      </select>

      <select name="id_ruangan" id="idRuangan" required>
        <option value="">Pilih Ruangan</option>
        <?php
        $ruangan = mysqli_query($conn,"SELECT * FROM ruangan ORDER BY nama_ruangan ASC");
        while($rowRuangan = mysqli_fetch_assoc($ruangan)){
            echo "<option value='".(int)$rowRuangan['id']."'>".e($rowRuangan['nama_ruangan'])."</option>";
        }
        ?>
      </select>

      <!-- Dropdown hari -->
      <select name="hari" id="hari" required>
        <option value="">Pilih Hari</option>
        <option value="Senin">Senin</option>
        <option value="Selasa">Selasa</option>
        <option value="Rabu">Rabu</option>
        <option value="Kamis">Kamis</option>
        <option value="Jumat">Jumat</option>
        <option value="Sabtu">Sabtu</option>
        <option value="Minggu">Minggu</option>
      </select>

      <input type="text" name="jam_mulai" id="jamMulai" placeholder="Jam mulai Contoh: 09:00" pattern="^([01]\d|2[0-3]):([0-5]\d)$" required>
      <input type="text" name="jam_selesai" id="jamSelesai" placeholder="Jam selesai Contoh: 11:00" pattern="^([01]\d|2[0-3]):([0-5]\d)$" required>

      <input type="number" name="kuota" id="kuota" placeholder="Kuota" min="1" required>

      <button type="submit" id="simpanJadwal">Simpan</button>
    </form>
  </div>
</div>

<script>
$(document).ready(function () {
    $('#tabel-jadwal').DataTable({
        "pageLength": 10,
        "lengthMenu": [5, 10, 25, 50],
        "columnDefs": [{"orderable": false,"targets": 9}],
        "language": {
            "decimal": "", "emptyTable": "Tidak ada data tersedia",
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
            "infoFiltered": "(disaring dari _MAX_ data total)",
            "lengthMenu": "Tampilkan _MENU_ data",
            "loadingRecords": "Memuat...",
            "processing": "Sedang diproses...",
            "search": "Cari:", "zeroRecords": "Tidak ditemukan data yang sesuai",
            "paginate": {"first": "Pertama","last": "Terakhir","next": "Berikutnya","previous": "Sebelumnya"}
        }
    });
});

function tambahJadwal() {
  $('#formJadwal')[0].reset();
  $('#idJadwal').val('');
  $('#judulModal').text('Tambah Jadwal Praktikum');
  $('#modalJadwal').css('display','flex');
}

function editJadwal(id) {
  $.post('proses_jadwal.php', {aksi:'ambil',id:id}, function(data){
    let obj = {};
    try { obj = JSON.parse(data); } catch(e){ alert('Gagal memuat data'); return; }

    $('#judulModal').text('Edit Jadwal Praktikum');
    $('#idJadwal').val(obj.id);
    $('#idMK').val(obj.id_mk);
    $('#idDosen').val(obj.id_dosen);
    $('#idRuangan').val(obj.id_ruangan);

    const mapHari = {
      'senin':'Senin','selasa':'Selasa','rabu':'Rabu','kamis':'Kamis',
      'jumat':'Jumat',"jum'at":'Jumat','sabtu':'Sabtu','minggu':'Minggu'
    };
    const hariNorm = (obj.hari || '').toString().trim().toLowerCase();
    $('#hari').val(mapHari[hariNorm] || obj.hari || '');

    if (obj.jam_mulai)  $('#jamMulai').val(obj.jam_mulai.substring(0,5));
    if (obj.jam_selesai)$('#jamSelesai').val(obj.jam_selesai.substring(0,5));

    $('#kuota').val(obj.kuota_awal); 
    $('#modalJadwal').css('display','flex');
  });
}

function hapusJadwal(id){
  if(confirm('Apakah Anda yakin ingin menghapus data ini?')){
    $.post('proses_jadwal.php',{aksi:'hapus',id:id}, function(res){
      if(res && res.startsWith && res.startsWith('error|')){
        alert(res.split('|')[1] || 'Gagal menghapus data');
      } else {
        alert('Data berhasil dihapus!');
        location.reload();
      }
    });
  }
}

function tutupModal(){ $('#modalJadwal').hide(); }

$('#formJadwal').submit(function(e){
    e.preventDefault();
    let jamMulai = $('#jamMulai').val();
    let jamSelesai = $('#jamSelesai').val();

    if(jamMulai >= jamSelesai){
        alert('Jam selesai harus lebih besar dari jam mulai!');
        return;
    }

    $.post('proses_jadwal.php', $(this).serialize(), function(res){
        res = (res || '').toString().trim();
        if(res.startsWith('error|')){
            alert(res.split('|')[1] || 'Terjadi kesalahan');
        } else {
            $('#modalJadwal').hide();
            const id = $('#idJadwal').val();
            const pesan = id ? 'Data berhasil diubah!' : 'Data berhasil ditambahkan!';
            alert(pesan);
            location.reload();
        }
    });
});
</script>
</body>
</html>
