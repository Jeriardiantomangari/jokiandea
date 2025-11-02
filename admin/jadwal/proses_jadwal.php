<?php
session_start();
include '../../koneksi/koneksi.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
    echo "error|Akses ditolak!";
    exit;
}

$aksi = $_POST['aksi'] ?? '';

if($aksi == 'ambil') {
    // Ambil data jadwal untuk modal edit
    $id = $_POST['id'];
    $q = mysqli_query($conn,"SELECT * FROM jadwal_praktikum WHERE id='$id'");
    $data = mysqli_fetch_assoc($q);
    echo json_encode($data);
    exit;
}

if($aksi == 'hapus') {
    // Hapus jadwal
    $id = $_POST['id'];
    mysqli_query($conn,"DELETE FROM jadwal_praktikum WHERE id='$id'");
    mysqli_query($conn,"DELETE FROM pilihan_jadwal WHERE id_jadwal='$id'"); // hapus peserta juga
    exit;
}

if($aksi == 'simpan' || $aksi == '') {
    $id = $_POST['id'] ?? '';
    $id_mk = $_POST['id_mk'];
    $id_dosen = $_POST['id_dosen'];
    $id_ruangan = $_POST['id_ruangan'];
    $hari = $_POST['hari'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];
    $kuota = $_POST['kuota'];

    // Cek tabrakan Dosen
    $cekDosen = mysqli_query($conn, "
        SELECT * FROM jadwal_praktikum
        WHERE id != '$id'
          AND id_dosen='$id_dosen'
          AND hari='$hari'
          AND ('$jam_mulai' < jam_selesai AND '$jam_selesai' > jam_mulai)
    ");

    // Cek tabrakan Ruangan
    $cekRuangan = mysqli_query($conn, "
        SELECT * FROM jadwal_praktikum
        WHERE id != '$id'
          AND id_ruangan='$id_ruangan'
          AND hari='$hari'
          AND ('$jam_mulai' < jam_selesai AND '$jam_selesai' > jam_mulai)
    ");

    // Pesan lebih informatif
    if(mysqli_num_rows($cekDosen) > 0 && mysqli_num_rows($cekRuangan) > 0){
        $rowDosen = mysqli_fetch_assoc($cekDosen);
        $rowRuangan = mysqli_fetch_assoc($cekRuangan);
        echo "error|Tabrakan jadwal! Dosen '{$rowDosen['id_dosen']}' dan ruangan '{$rowRuangan['id_ruangan']}' sudah terpakai pada hari $hari, jam {$jam_mulai}-{$jam_selesai}.";
        exit;
    } elseif(mysqli_num_rows($cekDosen) > 0){
        $rowDosen = mysqli_fetch_assoc($cekDosen);
        echo "error|Dosen '{$rowDosen['id_dosen']}' sudah memiliki jadwal pada hari $hari, jam {$jam_mulai}-{$jam_selesai}.";
        exit;
    } elseif(mysqli_num_rows($cekRuangan) > 0){
        $rowRuangan = mysqli_fetch_assoc($cekRuangan);
        echo "error|Ruangan '{$rowRuangan['id_ruangan']}' sudah digunakan pada hari $hari, jam {$jam_mulai}-{$jam_selesai}.";
        exit;
    }

    if($id){ 
        // Update jadwal dan ubah juga kuota_awal sesuai input baru
        $q = mysqli_query($conn, "UPDATE jadwal_praktikum SET 
            id_mk='$id_mk',
            id_dosen='$id_dosen',
            id_ruangan='$id_ruangan',
            hari='$hari',
            jam_mulai='$jam_mulai',
            jam_selesai='$jam_selesai',
            kuota='$kuota',
            kuota_awal='$kuota'
            WHERE id='$id'");
        if(!$q) echo "error|Gagal mengupdate data!";
        exit;
    } else { 
        // Insert jadwal baru dengan kuota_awal = kuota
        $q = mysqli_query($conn, "INSERT INTO jadwal_praktikum 
            (id_mk,id_dosen,id_ruangan,hari,jam_mulai,jam_selesai,kuota,kuota_awal)
            VALUES ('$id_mk','$id_dosen','$id_ruangan','$hari','$jam_mulai','$jam_selesai','$kuota','$kuota')");
        if(!$q) echo "error|Gagal menambahkan data!";
        exit;
    }
}
?>
