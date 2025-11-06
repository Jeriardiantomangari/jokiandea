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

// Ambil data mahasiswa login
$id_mahasiswa = (int)($_SESSION['mhs_id'] ?? 0);

// Semester aktif 
$qSem = mysqli_query($conn, "SELECT id, nama_semester, tahun_ajaran FROM semester WHERE status='Aktif' LIMIT 1");
$semAktif   = mysqli_fetch_assoc($qSem);
$id_semester= $semAktif['id'] ?? null;

// Ambil kontrak terbaru pada semester aktif 
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

$mk_list_raw = [];
if (!empty($kontrak['mk_dikontrak'])) {
    foreach(explode(',', $kontrak['mk_dikontrak']) as $n){
        $n = trim($n);
        if($n!=='') $mk_list_raw[] = $n;
    }
}

// Ambil set MK yang sudah dipilih shiftnya 
$mk_sudah_dipilih = [];
if ($id_semester){
    $qSudah = mysqli_query($conn, "
        SELECT DISTINCT id_mk 
        FROM pilihan_jadwal 
        WHERE id_mahasiswa='$id_mahasiswa' AND id_semester='$id_semester'
    ");
    while($r = mysqli_fetch_assoc($qSudah)){
        $mk_sudah_dipilih[(int)$r['id_mk']] = true;
    }
}
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>Jadwal Praktikum</title>

  <!-- Pustaka DataTables & jQuery -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

  <!-- Ikon FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    .konten_utama {
      margin-left: 250px;
      margin-top: 60px;
      padding: 30px;
      min-height: calc(100vh - 60px);
      background: #f9f9f9;
      font-family: Arial, sans-serif;
    }

    .konten_utama h2 {
      margin-bottom: 10px;
      color: #333;
    }

    .kotak_info {
      margin: 10px 0 15px 0;
      padding: 10px 12px;
      border-radius: 6px;
    }

    .info_sukses {
      color: #333
    }

    .info_peringatan {
      color: #ff5252;
    }

    .info_gagal {
      color: #ff5252;
    }

   
    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
      padding: 6px 10px;
      border-radius: 5px;
      border: 1px solid #ccc;
      font-size: 14px;
      margin-bottom: 5px;
    }

 
    .tombol_umum {
      border: none;
      border-radius: 5px;
      cursor: pointer;
      color: white;
      font-size: 12px;
      transition: 0.3s;
      padding: 6px 10px;
    }

    .tombol_umum:hover {
      opacity: 0.9;
    }

    .tombol_tambah {
      background: #00b4ff;
      padding: 8px 15px;
    }

    
    .tabel_data {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      table-layout: fixed;
    }

    .tabel_data th {
      background: #00AEEF;
      color: #333;
      text-align: left;
      padding: 12px 15px;
    }

    .tabel_data td {
      padding: 12px 15px;
      border-bottom: 1px solid #ddd;
      border-right: 1px solid #ddd;
    }

    .tabel_data tr:hover {
      background: #f7fbff;
    }

    .sel_modul {
      text-align: center;
    }

   
    @media screen and (max-width: 768px) {
      .konten_utama {
        margin-left: 0;
        padding: 20px;
        width: 100%;
        text-align: center;
      }

      .konten_utama h2 {
        text-align: center;
      }

    
      .tabel_data,
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

      .sel_modul {
        text-align: right;
      }
    }
  </style>
</head>

<body>
  <div class="konten_utama">
    <!-- Judul halaman -->
    <h2>Jadwal Saya</h2>

    <?php
  $qSem = mysqli_query($conn, "SELECT id, nama_semester, tahun_ajaran FROM semester WHERE status='Aktif' LIMIT 1");
  $semAktif   = mysqli_fetch_assoc($qSem);
  $id_semester= $semAktif['id'] ?? null;
  ?>

    <?php if(!$id_semester): ?>
    <div class="kotak_info info_peringatan">Belum ada <b>Semester</b> berstatus <b>Aktif</b>. Tidak ada jadwal yang
      ditampilkan.</div>
    <?php else: ?>
    <div class="kotak_info info_sukses">
      Semester: <b><?= e($semAktif['nama_semester']); ?></b> / <b><?= e($semAktif['tahun_ajaran']); ?></b>
    </div>
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
          <th>Semester</th>
          <th>SKS</th>
          <th>Modul</th>
        </tr>
      </thead>
      <tbody>
        <?php
      if ($id_semester) {
        $sql = "
          SELECT
            mk.kode_mk, mk.nama_mk, mk.sks, mk.modul,
            mk.semester AS semester_mk,
            jp.hari, jp.jam_mulai, jp.jam_selesai,
            d.nama AS pengajar,
            r.nama_ruangan
          FROM pilihan_jadwal pj
          INNER JOIN jadwal_praktikum jp ON jp.id = pj.id_jadwal
          LEFT JOIN matakuliah_praktikum mk ON mk.id = jp.id_mk
          LEFT JOIN dosen d ON d.id  = jp.id_dosen
          LEFT JOIN ruangan r ON r.id = jp.id_ruangan
          WHERE pj.id_mahasiswa = ".(int)$id_mahasiswa."
            AND pj.id_semester = ".(int)$id_semester."
          ORDER BY
            mk.nama_mk ASC,
            FIELD(jp.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'),
            jp.jam_mulai ASC
        ";
        $q  = mysqli_query($conn, $sql);
        $no = 1;

        if ($q && mysqli_num_rows($q) > 0) {
          while ($row = mysqli_fetch_assoc($q)) {
            $jam_mulai   = !empty($row['jam_mulai']) ? date('H:i', strtotime($row['jam_mulai'])) : '00:00';
            $jam_selesai = !empty($row['jam_selesai']) ? date('H:i', strtotime($row['jam_selesai'])) : '00:00';
            $jam         = $jam_mulai.' - '.$jam_selesai;

            $modul = trim((string)($row['modul'] ?? ''));
            $modul_html = $modul !== ''
              ? "<a href='../../uploads/".e($modul)."' target='_blank' title='Buka Modul'><i class='fa-solid fa-file-arrow-down' style='font-size:30px;color:#dc3545'></i></a>"
              : "<span style='color:#999;'>-</span>";
            ?>
        <tr>
          <td data-label="No"><?= $no++; ?></td>
          <td data-label="Kode MK"><?= e($row['kode_mk'] ?? ''); ?></td>
          <td data-label="Nama MK"><?= e($row['nama_mk'] ?? ''); ?></td>
          <td data-label="Hari"><?= e($row['hari'] ?? ''); ?></td>
          <td data-label="Jam"><?= e($jam); ?></td>
          <td data-label="Pengajar"><?= e($row['pengajar'] ?? ''); ?></td>
          <td data-label="Ruangan"><?= e($row['nama_ruangan'] ?? ''); ?></td>
          <td data-label="Semester"><?= e($row['semester_mk'] ?? ''); ?></td>
          <td data-label="SKS"><?= e($row['sks'] ?? ''); ?></td>
          <td data-label="Modul" class="sel_modul"><?= $modul_html; ?></td>
        </tr>
        <?php
          }
        }
      }
      ?>
      </tbody>
    </table>
  </div>

  <script>
    $(document).ready(function () {
      $('#tabel-jadwal').DataTable({
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        language: {
          emptyTable: "Belum ada jadwal yang Anda pilih pada semester aktif.",
          info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
          infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
          infoFiltered: "(disaring dari _MAX_ data total)",
          lengthMenu: "Tampilkan _MENU_ data",
          search: "Cari:",
          zeroRecords: "Tidak ditemukan data yang sesuai",
          paginate: {
            first: "Pertama",
            last: "Terakhir",
            next: "Berikutnya",
            previous: "Sebelumnya"
          }
        },
        columnDefs: [{
          targets: 9,
          orderable: false
        }]
      });
    });
  </script>
</body>

</html>