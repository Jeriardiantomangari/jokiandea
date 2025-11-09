<?php
// Inisialisasi sesi & koneksi
session_start();
include '../../koneksi/sidebardosen.php';
include '../../koneksi/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dosen') {
  header("Location: ../index.php"); exit;
}


function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// Identitas dosen 
$id_dosen = (int)($_SESSION['dosen_id'] ?? 0);

// Semester aktif
$semAktif = mysqli_fetch_assoc(
  mysqli_query($conn, "SELECT id, nama_semester, tahun_ajaran FROM semester WHERE status='Aktif' LIMIT 1")
);
$id_semester_aktif = $semAktif['id'] ?? null;
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>Jadwal Praktikum Saya</title>

  <!-- Pustaka -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    .area-utama {
      margin-left: 250px;
      margin-top: 60px;
      padding: 30px;
      min-height: calc(100vh - 60px);
      background: #f9f9f9;
      font-family: Arial, sans-serif;
    }

    .area-utama h2 {
      margin-bottom: 10px;
      color: #333;
    }

       .kotak-info{ padding:10px 12px; border-radius:8px; margin:8px 0; background:#fff; border:1px solid #e5e7eb; }

    .info-berhasil {
      color: #333;
    }

    .info-peringatan {
      color: #333;
    }

    .tabel-jadwal {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      table-layout: fixed;
    }

    .tabel-jadwal th {
      background: #00AEEF;
      color: #333;
      text-align: left;
      padding: 12px 15px;
    }

    .tabel-jadwal td {
      padding: 12px 15px;
      border-bottom: 1px solid #ddd;
      border-right: 1px solid #ddd;
    }

    .tabel-jadwal tr:hover {
      background: #f7fbff;
    }

    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
      padding: 6px 10px;
      border-radius: 5px;
      border: 1px solid #ccc;
      font-size: 14px;
      margin-bottom: 5px;
    }

    .sel_modul {
      text-align: center;
    }

    @media screen and (max-width: 768px) {
      .area-utama {
        margin-left: 0;
        padding: 20px;
        width: 100%;
        text-align: center;
      }

      .area-utama h2 {
        text-align: center;
      }

      .tabel-jadwal,
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
  <div class="area-utama">
    <h2>Jadwal Mengajar Saya</h2>

    <?php if(!$id_semester_aktif): ?>
    <div class="kotak-info info-peringatan">Belum ada <b>Semester</b> berstatus <b>Aktif</b>. Tidak ada jadwal yang
      ditampilkan.</div>
    <?php else: ?>
    <div class="kotak-info info-berhasil">
      Semester: <b><?= e($semAktif['nama_semester']) ?></b><b>/</b><b><?= e($semAktif['tahun_ajaran']) ?></b>
    </div>
    <?php endif; ?>

    <table id="tabel-jadwal" class="tabel-jadwal">
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
// Data jadwal dosen di semester aktif
$no = 1;
if ($id_semester_aktif && $id_dosen) {
  $sql = "
    SELECT
      jp.id,
      mk.kode_mk, mk.nama_mk, mk.sks, mk.modul,
      mk.semester          AS semester_mk,
      jp.hari, jp.jam_mulai, jp.jam_selesai,
      d.nama               AS pengajar,
      r.nama_ruangan
    FROM jadwal_praktikum jp
    INNER JOIN matakuliah_praktikum mk ON mk.id = jp.id_mk
    LEFT  JOIN dosen d                 ON d.id  = jp.id_dosen
    LEFT  JOIN ruangan r               ON r.id  = jp.id_ruangan
    WHERE jp.id_semester = ".(int)$id_semester_aktif."
      AND jp.id_dosen    = ".(int)$id_dosen."
    ORDER BY
      mk.nama_mk ASC,
      FIELD(jp.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'),
      jp.jam_mulai ASC
  ";
  $q = mysqli_query($conn, $sql);

  if ($q && mysqli_num_rows($q) > 0) {
    while($row = mysqli_fetch_assoc($q)){
      $jam_mulai   = substr($row['jam_mulai'],0,5);
      $jam_selesai = substr($row['jam_selesai'],0,5);
      $jam         = $jam_mulai.' - '.$jam_selesai;

      $modul = trim((string)($row['modul'] ?? ''));
      $modul_html = $modul !== ''
        ? "<a href='../../uploads/".e($modul)."' target='_blank' title='Buka Modul'><i class='fa-solid fa-file-arrow-down' style='font-size:30px;color:#dc3545'></i></a>"
        : "<span style=\"color:#999;\">-</span>";
      ?>
        <tr>
          <td data-label="No"><?= $no++; ?></td>
          <td data-label="Kode MK"><?= e($row['kode_mk']); ?></td>
          <td data-label="Nama MK"><?= e($row['nama_mk']); ?></td>
          <td data-label="Hari"><?= e($row['hari']); ?></td>
          <td data-label="Jam"><?= e($jam); ?></td>
          <td data-label="Pengajar"><?= e($row['pengajar'] ?? ''); ?></td>
          <td data-label="Ruangan"><?= e($row['nama_ruangan'] ?? ''); ?></td>
          <td data-label="Semester"><?= e($row['semester_mk'] ?? ''); ?></td>
          <td data-label="SKS"><?= e($row['sks']); ?></td>
          <td data-label="Modul" class="sel_modul"><?= $modul_html; ?></td>
        </tr>
        <?php
    }
  }
}
?>
      </tbody>
    </table>

    <?php
  // Info jika tidak ada baris sama sekali
  if ($id_semester_aktif && $id_dosen) {
    if (!isset($q) || !$q || mysqli_num_rows($q) === 0) {
      echo '<div class="kotak-info info-peringatan" style="margin-top:10px;">Tidak ada jadwal mengajar Anda pada semester ini.</div>';
    }
  }
  ?>
  </div>

  <script>
    // DataTables
    $(document).ready(function () {
      $('#tabel-jadwal').DataTable({
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        columnDefs: [{
          orderable: false,
          targets: 9
        }],
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
          paginate: {
            first: "Pertama",
            last: "Terakhir",
            next: "Berikutnya",
            previous: "Sebelumnya"
          }
        }
      });
    });
  </script>
</body>

</html>