<?php
session_start();
include '../../koneksi/koneksi.php';

header('Content-Type: text/plain; charset=utf-8');

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
    echo "error|Akses ditolak!";
    exit;
}

$aksi = $_POST['aksi'] ?? '';

if($aksi === 'ambil') {
    $id = (int)($_POST['id'] ?? 0);
    $q = mysqli_query($conn,"SELECT * FROM jadwal_praktikum WHERE id='$id'");
    $data = mysqli_fetch_assoc($q);
    header('Content-Type: application/json');
    echo json_encode($data ?: []);
    exit;
}

if($aksi === 'hapus') {
    $id = (int)($_POST['id'] ?? 0);
    if(!$id){ echo "error|ID tidak valid"; exit; }

    // Hapus: pilihan_jadwal terhapus oleh FK CASCADE pada id_jadwal
    $ok = mysqli_query($conn,"DELETE FROM jadwal_praktikum WHERE id='$id'");
    if(!$ok){ echo "error|Gagal menghapus data!"; }
    exit;
}

// default: simpan (insert/update)
$id         = (int)($_POST['id'] ?? 0);
$id_mk      = (int)($_POST['id_mk'] ?? 0);
$id_dosen   = (int)($_POST['id_dosen'] ?? 0);
$id_ruangan = (int)($_POST['id_ruangan'] ?? 0);
$hari       = trim($_POST['hari'] ?? '');
$jam_mulai  = trim($_POST['jam_mulai'] ?? '');
$jam_selesai= trim($_POST['jam_selesai'] ?? '');
$kuota      = (int)($_POST['kuota'] ?? 0);

// Ambil semester aktif (wajib untuk insert)
$sem = mysqli_query($conn, "SELECT id FROM semester WHERE status='Aktif' LIMIT 1");
$semRow = mysqli_fetch_assoc($sem);
$id_semester_aktif = $semRow['id'] ?? null;

if ($id === 0 && !$id_semester_aktif) {
    echo "error|Tidak ada semester aktif. Buat/aktifkan semester terlebih dahulu.";
    exit;
}

// Validasi input dasar
if(!$id_mk || !$id_dosen || !$id_ruangan || !$hari || !$jam_mulai || !$jam_selesai || $kuota < 1){
    echo "error|Lengkapi semua field dan pastikan kuota >= 1";
    exit;
}
if($jam_mulai >= $jam_selesai){
    echo "error|Jam selesai harus lebih besar dari jam mulai";
    exit;
}

// Cek tabrakan Dosen pada hari & jam
$cekDosen = mysqli_query($conn, "
    SELECT 1 FROM jadwal_praktikum
    WHERE id != '$id'
      AND id_dosen = '$id_dosen'
      AND hari = '".mysqli_real_escape_string($conn,$hari)."'
      AND ('$jam_mulai' < jam_selesai AND '$jam_selesai' > jam_mulai)
      ".($id_semester_aktif ? "AND id_semester = '$id_semester_aktif'" : "")."
");

// Cek tabrakan Ruangan pada hari & jam
$cekRuangan = mysqli_query($conn, "
    SELECT 1 FROM jadwal_praktikum
    WHERE id != '$id'
      AND id_ruangan = '$id_ruangan'
      AND hari = '".mysqli_real_escape_string($conn,$hari)."'
      AND ('$jam_mulai' < jam_selesai AND '$jam_selesai' > jam_mulai)
      ".($id_semester_aktif ? "AND id_semester = '$id_semester_aktif'" : "")."
");

if(mysqli_num_rows($cekDosen) > 0 && mysqli_num_rows($cekRuangan) > 0){
    echo "error|Tabrakan jadwal! Dosen & ruangan sudah terpakai di hari $hari, $jam_mulai-$jam_selesai.";
    exit;
} elseif(mysqli_num_rows($cekDosen) > 0){
    echo "error|Dosen sudah memiliki jadwal di hari $hari, $jam_mulai-$jam_selesai.";
    exit;
} elseif(mysqli_num_rows($cekRuangan) > 0){
    echo "error|Ruangan sudah digunakan di hari $hari, $jam_mulai-$jam_selesai.";
    exit;
}

if($id){ 
    // Update: jangan mengubah id_semester (biarkan tetap semester saat dibuat)
    $q = mysqli_query($conn, "UPDATE jadwal_praktikum SET 
        id_mk      = '$id_mk',
        id_dosen   = '$id_dosen',
        id_ruangan = '$id_ruangan',
        hari       = '".mysqli_real_escape_string($conn,$hari)."',
        jam_mulai  = '$jam_mulai',
        jam_selesai= '$jam_selesai',
        kuota      = '$kuota',
        kuota_awal = '$kuota'
        WHERE id   = '$id'
    ");
    if(!$q){ echo "error|Gagal mengupdate data!"; }
    exit;

} else { 
    // Insert: sematkan id_semester aktif
    $q = mysqli_query($conn, "INSERT INTO jadwal_praktikum 
        (id_mk, id_dosen, id_ruangan, id_semester, hari, jam_mulai, jam_selesai, kuota, kuota_awal)
        VALUES
        ('$id_mk','$id_dosen','$id_ruangan','$id_semester_aktif','".mysqli_real_escape_string($conn,$hari)."','$jam_mulai','$jam_selesai','$kuota','$kuota')
    ");
    if(!$q){ echo "error|Gagal menambahkan data!"; }
    exit;
}
