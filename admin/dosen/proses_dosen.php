<?php
include '../../koneksi/koneksi.php'; 

$aksi = isset($_POST['aksi']) ? $_POST['aksi'] : '';

if($aksi == 'ambil'){
    $id = intval($_POST['id']);
    $q = mysqli_query($conn, "SELECT * FROM dosen WHERE id=$id");
    echo json_encode(mysqli_fetch_assoc($q));
    exit;
}
elseif($aksi == 'hapus'){
    $id = intval($_POST['id']);
    mysqli_query($conn, "DELETE FROM dosen WHERE id=$id");
    exit;
}
else{
    $id = intval($_POST['id']);
    $nidn = mysqli_real_escape_string($conn, $_POST['nidn']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $prodi = mysqli_real_escape_string($conn, $_POST['prodi']); // Farmasi / Analis Kesehatan
    $jenis_kelamin = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Optional: hash password
    //$password_hashed = password_hash($password, PASSWORD_DEFAULT);
    $password_hashed = $password; // jika masih ingin plaintext

    if($id){ // Edit
        mysqli_query($conn, "UPDATE dosen SET
            nidn='$nidn',
            nama='$nama',
            prodi='$prodi',
            jenis_kelamin='$jenis_kelamin',
            alamat='$alamat',
            no_hp='$no_hp',
            password='$password_hashed'
            WHERE id=$id
        ");
    } else { // Tambah
        mysqli_query($conn, "INSERT INTO dosen (nidn,nama,prodi,jenis_kelamin,alamat,no_hp,password) VALUES (
            '$nidn','$nama','$prodi','$jenis_kelamin','$alamat','$no_hp','$password_hashed'
        )");
    }
    exit;
}
?>
