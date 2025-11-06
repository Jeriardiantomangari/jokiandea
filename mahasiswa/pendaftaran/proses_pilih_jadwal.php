<?php
session_start();
include '../../koneksi/koneksi.php'; 

header('Content-Type: text/plain; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'mahasiswa') {
    echo "error|Akses ditolak!";
    exit;
}

//  gunakan mhs_id 
$id_mahasiswa = (int)($_SESSION['mhs_id'] ?? 0);
$id_jadwal    = (int)($_POST['id_jadwal'] ?? 0);

if(!$id_mahasiswa || !$id_jadwal){
    echo "error|Data tidak valid";
    exit;
}

// Semester aktif
$sem = mysqli_query($conn, "SELECT id FROM semester WHERE status='Aktif' LIMIT 1");
$semRow = mysqli_fetch_assoc($sem);
$id_semester = $semRow['id'] ?? null;
if(!$id_semester){
    echo "error|Tidak ada Semester Aktif";
    exit;
}

// Kontrak mahasiswa pada semester aktif HARUS ada & Disetujui
$qKon = mysqli_query($conn, "
    SELECT id, status, mk_dikontrak 
    FROM kontrak_mk 
    WHERE id_mahasiswa='$id_mahasiswa' AND id_semester='$id_semester'
    ORDER BY id DESC LIMIT 1
");
$kontrak = mysqli_fetch_assoc($qKon);
if(!$kontrak){
    echo "error|Anda belum mengirim kontrak pada Semester Aktif";
    exit;
}
if($kontrak['status'] !== 'Disetujui'){
    echo "error|Kontrak belum Disetujui";
    exit;
}
$id_kontrak = (int)$kontrak['id'];

// Ambil jadwal & pastikan berada pada Semester Aktif
$qJ = mysqli_query($conn, "
    SELECT jp.id, jp.id_mk, jp.kuota, jp.id_semester, mk.nama_mk
    FROM jadwal_praktikum jp
    LEFT JOIN matakuliah_praktikum mk ON mk.id = jp.id_mk
    WHERE jp.id='$id_jadwal' LIMIT 1
");
$J = mysqli_fetch_assoc($qJ);
if(!$J){
    echo "error|Jadwal tidak ditemukan";
    exit;
}
if((int)$J['id_semester'] !== (int)$id_semester){
    echo "error|Jadwal bukan untuk Semester Aktif";
    exit;
}

$id_mk  = (int)$J['id_mk'];
$nama_mk = $J['nama_mk'] ?? '';

// Pastikan MK ini memang ada di daftar kontrak mahasiswa
$mk_dikontrak = array_filter(array_map('trim', explode(',', (string)$kontrak['mk_dikontrak'])));
$mk_in_kontrak = in_array($nama_mk, $mk_dikontrak, true);
if(!$mk_in_kontrak){
    echo "error|MK ini tidak ada di kontrak Anda";
    exit;
}

// Cek sudah memilih shift untuk MK ini pada semester aktif
$cek_dup = mysqli_query($conn, "
    SELECT id FROM pilihan_jadwal
    WHERE id_mahasiswa='$id_mahasiswa' AND id_semester='$id_semester' AND id_mk='$id_mk'
    LIMIT 1
");
if(mysqli_fetch_assoc($cek_dup)){
    echo "error|Anda sudah memilih shift untuk MK ini";
    exit;
}

// Transaksi untuk amankan kuota
mysqli_begin_transaction($conn);

try {
    // Kurangi kuota kalau masih ada
    $upd = mysqli_query($conn, "
        UPDATE jadwal_praktikum 
        SET kuota = kuota - 1
        WHERE id = '$id_jadwal' AND kuota > 0
    ");
    if(!$upd || mysqli_affected_rows($conn) < 1){
        throw new Exception("Kuota jadwal sudah habis");
    }

    // Simpan pilihan
    $ins = mysqli_query($conn, "
        INSERT INTO pilihan_jadwal (id_mahasiswa, id_kontrak, id_jadwal, id_mk, id_semester)
        VALUES ('$id_mahasiswa', '$id_kontrak', '$id_jadwal', '$id_mk', '$id_semester')
    ");
    if(!$ins){
        throw new Exception("Gagal menyimpan pilihan");
    }

    mysqli_commit($conn);
    echo "ok|Jadwal berhasil dipilih!";
} catch (Exception $e){
    mysqli_rollback($conn);
    echo "error|".$e->getMessage();
}