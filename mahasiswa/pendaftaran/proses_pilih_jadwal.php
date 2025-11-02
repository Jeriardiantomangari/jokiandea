<?php
session_start();
include '../../koneksi/koneksi.php'; 

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'mahasiswa'){
    echo "Akses ditolak!";
    exit;
}

$id_mahasiswa = $_SESSION['id_user'];
$id_jadwal = $_POST['id_jadwal'];

// Ambil info jadwal yang dipilih
$jadwal = mysqli_query($conn,"SELECT id_mk, kuota FROM jadwal_praktikum WHERE id='$id_jadwal'");
$data_jadwal = mysqli_fetch_assoc($jadwal);

if(!$data_jadwal){
    echo "Jadwal tidak ditemukan!";
    exit;
}

$id_mk = $data_jadwal['id_mk'];
$kuota = $data_jadwal['kuota'];

// Cek apakah mahasiswa sudah memilih jadwal ini
$cek_sama = mysqli_query($conn,"SELECT * FROM pilihan_jadwal WHERE id_mahasiswa='$id_mahasiswa' AND id_jadwal='$id_jadwal'");
if(mysqli_num_rows($cek_sama) > 0){
    echo "Anda sudah memilih jadwal ini!";
    exit;
}

// Cek apakah mahasiswa sudah memilih MK ini di jam lain
$cek_mk = mysqli_query($conn,"SELECT pj.id_jadwal, jp.hari, jp.jam_mulai, jp.jam_selesai
    FROM pilihan_jadwal pj
    JOIN jadwal_praktikum jp ON pj.id_jadwal = jp.id
    WHERE pj.id_mahasiswa='$id_mahasiswa' AND jp.id_mk='$id_mk'");
if(mysqli_num_rows($cek_mk) > 0){
    echo "Anda sudah memilih mata kuliah ini di jam lain!";
    exit;
}

// Cek kuota
if($kuota <= 0){
    echo "Kuota jadwal sudah penuh!";
    exit;
}

// Simpan pilihan mahasiswa
mysqli_query($conn,"INSERT INTO pilihan_jadwal (id_mahasiswa,id_jadwal) VALUES ('$id_mahasiswa','$id_jadwal')");

// Kurangi kuota
mysqli_query($conn,"UPDATE jadwal_praktikum SET kuota=kuota-1 WHERE id='$id_jadwal'");

echo "Jadwal berhasil dipilih!";
?>
