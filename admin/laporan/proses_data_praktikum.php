<?php
session_start();
include '../../koneksi/koneksi.php';

// ===== Pastikan output JSON bersih =====
while (ob_get_level()) { ob_end_clean(); }
ob_start();
header('Content-Type: application/json; charset=utf-8');
// Jangan tampilkan notice/warning ke output (agar JSON tidak rusak)
ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  echo json_encode(['error' => 'Akses ditolak']); exit;
}

function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

$aksi = $_POST['aksi'] ?? '';

// (Opsional) ambil semester aktif; kalau tidak ada, tetap lanjut
$semRes = mysqli_query($conn, "SELECT id, nama_semester, tahun_ajaran FROM semester WHERE status='Aktif' LIMIT 1");
$semAktif = $semRes ? mysqli_fetch_assoc($semRes) : null;
$id_semester = $semAktif['id'] ?? null;

if ($aksi === 'list_shift') {
  $id_mk = (int)($_POST['id_mk'] ?? 0);
  if ($id_mk <= 0) { echo json_encode(['error'=>'MK tidak valid']); exit; }

  $mkRes = mysqli_query($conn, "SELECT kode_mk, nama_mk FROM matakuliah_praktikum WHERE id=$id_mk LIMIT 1");
  if(!$mkRes){ echo json_encode(['error'=>'DB error MK: '.mysqli_error($conn)]); exit; }
  $rMK = mysqli_fetch_assoc($mkRes);
  $mkLabel = $rMK ? ($rMK['kode_mk'].' - '.$rMK['nama_mk']) : '';

  $sql = "SELECT jp.id, jp.hari, jp.jam_mulai, jp.jam_selesai, r.nama_ruangan, d.nama AS dosen
          FROM jadwal_praktikum jp
          LEFT JOIN ruangan r ON r.id = jp.id_ruangan
          LEFT JOIN dosen d   ON d.id = jp.id_dosen
          WHERE jp.id_mk = $id_mk";
  if ($id_semester) { $sql .= " AND jp.id_semester = ".(int)$id_semester; }

  // urutkan shift dari jam paling pagi → lalu urut hari
  $sql .= " ORDER BY jp.jam_mulai ASC, FIELD(jp.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu')";

  $q = mysqli_query($conn, $sql);
  if(!$q){ echo json_encode(['error'=>'DB error shift: '.mysqli_error($conn)]); exit; }

  $rows = [];
  while($s = mysqli_fetch_assoc($q)){
    $label = ($s['hari']??'-').' '.substr($s['jam_mulai'],0,5).'-'.substr($s['jam_selesai'],0,5)
            .' • Ruang '.($s['nama_ruangan']??'-')
            .' • '.($s['dosen'] ? 'Dosen '.$s['dosen'] : 'Dosen -');
    $rows[] = ['id'=>(int)$s['id'], 'label'=>$label];
  }

  echo json_encode(['shifts'=>$rows, 'mk'=>$mkLabel]); exit;
}

if ($aksi === 'list_peserta') {
  $id_mk = (int)($_POST['id_mk'] ?? 0);
  $id_jadwal = (int)($_POST['id_jadwal'] ?? 0);
  if($id_mk<=0 || $id_jadwal<=0){
    echo json_encode(['rows'=>[], 'meta'=>['mk'=>'','shift'=>''], 'error'=>'MK/Shift wajib dipilih']); exit;
  }

  $rMK = mysqli_fetch_assoc(mysqli_query($conn, "SELECT kode_mk, nama_mk FROM matakuliah_praktikum WHERE id=$id_mk"));
  $mkNama = $rMK['nama_mk'] ?? '';
  $mkKode = $rMK['kode_mk'] ?? '';

  $infoShift = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT jp.hari, jp.jam_mulai, jp.jam_selesai, r.nama_ruangan, d.nama AS dosen
    FROM jadwal_praktikum jp
    LEFT JOIN ruangan r ON r.id = jp.id_ruangan
    LEFT JOIN dosen d   ON d.id = jp.id_dosen
    WHERE jp.id = $id_jadwal
    LIMIT 1
  "));
  $labelShift = '';
  if($infoShift){
    $labelShift = ($infoShift['hari']??'-').' '.substr($infoShift['jam_mulai'],0,5).'-'.substr($infoShift['jam_selesai'],0,5)
                .' • Ruang '.($infoShift['nama_ruangan']??'-')
                .' • '.($infoShift['dosen']?'Dosen '.$infoShift['dosen']:'Dosen -');
  }

  $condSem = $id_semester ? " AND pj.id_semester = ".(int)$id_semester." " : "";

  $sql = "
    SELECT
      mhs.nim, mhs.nama, mhs.no_hp, mhs.jenis_kelamin, mhs.jurusan, mhs.alamat,
      pj.tanggal_daftar,
      r.nama_ruangan,
      d.nama AS dosen,
      jp.hari, jp.jam_mulai, jp.jam_selesai
    FROM pilihan_jadwal pj
    INNER JOIN mahasiswa mhs       ON mhs.id = pj.id_mahasiswa
    INNER JOIN jadwal_praktikum jp ON jp.id = pj.id_jadwal
    LEFT  JOIN ruangan r           ON r.id = jp.id_ruangan
    LEFT  JOIN dosen d             ON d.id = jp.id_dosen
    WHERE pj.id_mk = $id_mk
      AND pj.id_jadwal = $id_jadwal
      $condSem
    ORDER BY mhs.nama ASC, pj.tanggal_daftar ASC
  ";
  $q = mysqli_query($conn, $sql);
  if(!$q){ echo json_encode(['rows'=>[], 'meta'=>['mk'=>$mkKode.' - '.$mkNama, 'shift'=>$labelShift], 'error'=>'DB error peserta: '.mysqli_error($conn)]); exit; }

  $rows = []; $no = 1;
  while($r = mysqli_fetch_assoc($q)){
    $jam = substr($r['jam_mulai'],0,5).'-'.substr($r['jam_selesai'],0,5);
    $rows[] = [
      'no'      => $no++,
      'nim'     => e($r['nim']),
      'nama'    => e($r['nama']),
      'no_hp'   => e($r['no_hp']),
      'jk'      => e($r['jenis_kelamin']),
      'jurusan' => e($r['jurusan']),
      'alamat'  => e($r['alamat']),
      'shift'   => e(($r['hari']??'-').' '.$jam),
      'dosen'   => e($r['dosen'] ?? '-'),
      'ruangan' => e($r['nama_ruangan'] ?? '-'),
      'tanggal' => e($r['tanggal_daftar'] ? date('Y-m-d H:i', strtotime($r['tanggal_daftar'])) : '-'),
    ];
  }

  echo json_encode(['rows'=>$rows, 'meta'=>['mk'=>$mkKode.' - '.$mkNama, 'shift'=>$labelShift]]); exit;
}

echo json_encode(['error'=>'Aksi tidak dikenal']); exit;
