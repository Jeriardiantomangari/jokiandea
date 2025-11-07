<?php
// proses_data_praktikum.php
session_start();
require_once '../../koneksi/koneksi.php';

// Pastikan hanya Admin
if (!isset($_SESSION['role']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
  header('Content-Type: application/json; charset=utf-8');
  http_response_code(403);
  echo json_encode(['error' => 'Tidak diizinkan.']);
  exit;
}

header('Content-Type: application/json; charset=utf-8');

$aksi = $_POST['aksi'] ?? '';

// Helper aman
function jfail($msg) {
  echo json_encode(['error' => $msg]);
  exit;
}

if ($aksi === 'list_shift') {
  // INPUT
  $id_mk = isset($_POST['id_mk']) ? (int)$_POST['id_mk'] : 0;
  if ($id_mk <= 0) jfail('id_mk tidak valid.');

  // Ambil semua jadwal untuk MK tsb
  $sql = "SELECT 
            jp.id, jp.hari, jp.jam_mulai, jp.jam_selesai
          FROM jadwal_praktikum jp
          WHERE jp.id_mk = ?
          ORDER BY FIELD(jp.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'),
                   jp.jam_mulai ASC";
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, 'i', $id_mk);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);

  $shifts = [];
  while ($row = mysqli_fetch_assoc($res)) {
    $shifts[] = [
      'id'         => (int)$row['id'],
      'hari'       => $row['hari'],
      'jam_mulai'  => (string)$row['jam_mulai'],
      'jam_selesai'=> (string)$row['jam_selesai']
    ];
  }

  echo json_encode(['shifts' => $shifts]);
  exit;
}

if ($aksi === 'list_peserta') {
  $id_mk     = isset($_POST['id_mk']) ? (int)$_POST['id_mk'] : 0;
  $id_jadwal = isset($_POST['id_jadwal']) ? (int)$_POST['id_jadwal'] : 0;
  if ($id_mk <= 0 || $id_jadwal <= 0) jfail('Parameter tidak lengkap.');

  // Ambil meta untuk judul kecil
  $metaSql = "SELECT 
                mk.nama_mk,
                jp.hari, jp.jam_mulai, jp.jam_selesai,
                r.nama_ruangan,
                d.nama AS nama_dosen
              FROM jadwal_praktikum jp
              JOIN matakuliah_praktikum mk ON mk.id = jp.id_mk
              JOIN ruangan r ON r.id = jp.id_ruangan
              JOIN dosen d   ON d.id = jp.id_dosen
              WHERE jp.id = ? AND mk.id = ?";
  $ms = mysqli_prepare($conn, $metaSql);
  mysqli_stmt_bind_param($ms, 'ii', $id_jadwal, $id_mk);
  mysqli_stmt_execute($ms);
  $mres = mysqli_stmt_get_result($ms);
  $meta = mysqli_fetch_assoc($mres);

  $mkTitle   = $meta ? $meta['nama_mk'] : '';
  $shiftInfo = '';
  if ($meta) {
    $jm = substr((string)$meta['jam_mulai'], 0, 5);
    $js = substr((string)$meta['jam_selesai'], 0, 5);
    $shiftInfo = $meta['hari']." {$jm}-{$js}";
  }

  // Ambil peserta yang mendaftar pada MK & jadwal tsb
  $sql = "SELECT
            m.nim, m.nama, m.no_hp, m.jenis_kelamin, m.jurusan, m.alamat,
            jp.hari, jp.jam_mulai, jp.jam_selesai,
            d.nama AS pengajar,
            r.nama_ruangan AS ruangan,
            DATE_FORMAT(pj.tanggal_daftar, '%Y-%m-%d %H:%i') AS tanggal_daftar
          FROM pilihan_jadwal pj
          JOIN mahasiswa m             ON m.id = pj.id_mahasiswa
          JOIN jadwal_praktikum jp     ON jp.id = pj.id_jadwal
          JOIN matakuliah_praktikum mk ON mk.id = jp.id_mk
          JOIN dosen d                 ON d.id = jp.id_dosen
          JOIN ruangan r               ON r.id = jp.id_ruangan
          WHERE mk.id = ? AND jp.id = ?
          ORDER BY m.nim ASC, pj.tanggal_daftar ASC";

  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, 'ii', $id_mk, $id_jadwal);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);

  $rows = [];
  $no = 1;
  while ($row = mysqli_fetch_assoc($res)) {
    $jm = substr((string)$row['jam_mulai'], 0, 5);
    $js = substr((string)$row['jam_selesai'], 0, 5);
    $rows[] = [
      'no'      => $no++,
      'nim'     => $row['nim'],
      'nama'    => $row['nama'],
      'no_hp'   => $row['no_hp'],
      'jk'      => $row['jenis_kelamin'],
      'jurusan' => $row['jurusan'],
      'alamat'  => $row['alamat'],
      'shift'   => $row['hari']." {$jm}-{$js}",
      'dosen'   => $row['pengajar'],
      'ruangan' => $row['ruangan'],
      'tanggal' => $row['tanggal_daftar']
    ];
  }

  echo json_encode([
    'meta' => [
      'mk'    => $mkTitle,
      'shift' => $shiftInfo
    ],
    'rows' => $rows
  ]);
  exit;
}

// Aksi tidak dikenal
echo json_encode(['error' => 'aksi tidak dikenal']);
