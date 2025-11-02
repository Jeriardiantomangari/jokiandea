<?php
include '../../koneksi/koneksi.php'; 

$aksi = isset($_POST['aksi']) ? $_POST['aksi'] : '';

if ($aksi == 'ambil') {
    $id = intval($_POST['id']);
    $q = mysqli_query($conn, "SELECT * FROM ruangan WHERE id = $id");
    echo json_encode(mysqli_fetch_assoc($q));
    exit;
}

elseif ($aksi == 'hapus') {
    $id = intval($_POST['id']);
    mysqli_query($conn, "DELETE FROM ruangan WHERE id = $id");
    exit;
}

else {
    $id = intval($_POST['id']);
    $kode_ruangan = mysqli_real_escape_string($conn, $_POST['kode_ruangan']);
    $nama_ruangan = mysqli_real_escape_string($conn, $_POST['nama_ruangan']);
    $kapasitas = mysqli_real_escape_string($conn, $_POST['kapasitas']);
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi']);

    if ($id) { 
        // Edit data
        $query = "UPDATE ruangan SET 
                    kode_ruangan='$kode_ruangan',
                    nama_ruangan='$nama_ruangan',
                    kapasitas='$kapasitas',
                    lokasi='$lokasi'
                  WHERE id=$id";
        mysqli_query($conn, $query);
    } else { 
        // Tambah data
        $query = "INSERT INTO ruangan (kode_ruangan, nama_ruangan, kapasitas, lokasi) VALUES (
                    '$kode_ruangan', '$nama_ruangan', '$kapasitas', '$lokasi')";
        mysqli_query($conn, $query);
    }
    exit;
}
?>
