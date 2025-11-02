<?php
include '../../koneksi/koneksi.php'; 

$aksi = isset($_POST['aksi']) ? $_POST['aksi'] : '';

if ($aksi == 'ambil') {
    $id = intval($_POST['id']);
    $q = mysqli_query($conn, "SELECT * FROM mahasiswa WHERE id=$id");
    echo json_encode(mysqli_fetch_assoc($q));
    exit;
}

elseif ($aksi == 'hapus') {
    $id = intval($_POST['id']);
    mysqli_query($conn, "DELETE FROM mahasiswa WHERE id=$id");
    exit;
}

else {
    $id = intval($_POST['id']);
    $nim = mysqli_real_escape_string($conn, $_POST['nim']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $jurusan = mysqli_real_escape_string($conn, $_POST['jurusan']); // Farmasi / Analis Kesehatan
    $jenis_kelamin = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Password disimpan langsung (tanpa hash)
    $password_plain = $password;

    if ($id) { // Edit data
        mysqli_query($conn, "UPDATE mahasiswa SET
            nim='$nim',
            nama='$nama',
            jurusan='$jurusan',
            jenis_kelamin='$jenis_kelamin',
            alamat='$alamat',
            no_hp='$no_hp',
            password='$password_plain'
            WHERE id=$id
        ");
    } else { // Tambah data
        mysqli_query($conn, "INSERT INTO mahasiswa (nim, nama, jurusan, jenis_kelamin, alamat, no_hp, password) VALUES (
            '$nim','$nama','$jurusan','$jenis_kelamin','$alamat','$no_hp','$password_plain'
        )");
    }
    exit;
}
?>
