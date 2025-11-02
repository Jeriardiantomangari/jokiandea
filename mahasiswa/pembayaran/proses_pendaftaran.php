<?php
include '../../koneksi/koneksi.php';

$aksi = $_POST['aksi'] ?? '';

if($aksi == 'ambil'){
    $id = $_POST['id'];
    $query = mysqli_query($conn, "SELECT * FROM kontrak_mk WHERE id='$id'");
    $data = mysqli_fetch_assoc($query);
    echo json_encode($data);
    exit;
}

if($aksi == 'hapus'){
    $id = $_POST['id'];
    mysqli_query($conn, "DELETE FROM kontrak_mk WHERE id='$id'");
    exit;
}

// Tambah atau edit
$id = $_POST['id'] ?? '';
$id_mahasiswa = $_POST['id_mahasiswa'];
$nim = $_POST['nim'];
$nama = $_POST['nama'];
$no_hp = $_POST['no_hp'];
$mk_dikontrak = isset($_POST['mk_dikontrak']) ? implode(',', $_POST['mk_dikontrak']) : '';
$status = $_POST['status'] ?? 'Menunggu';

// Upload file bukti
$bukti = '';
if(isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['name'] != ''){
    $file = $_FILES['bukti_pembayaran'];
    $nama_file = time().'_'.$file['name'];
    move_uploaded_file($file['tmp_name'], '../../uploads/'.$nama_file);
    $bukti = $nama_file;
}

if($id == ''){ // Tambah
    $sql = "INSERT INTO kontrak_mk (id_mahasiswa,nim,nama,no_hp,mk_dikontrak,status,bukti_pembayaran)
            VALUES ('$id_mahasiswa','$nim','$nama','$no_hp','$mk_dikontrak','$status','$bukti')";
    mysqli_query($conn, $sql);
}else{ // Edit
    if($bukti != ''){
        $sql = "UPDATE kontrak_mk SET mk_dikontrak='$mk_dikontrak', bukti_pembayaran='$bukti' WHERE id='$id'";
    }else{
        $sql = "UPDATE kontrak_mk SET mk_dikontrak='$mk_dikontrak' WHERE id='$id'";
    }
    mysqli_query($conn, $sql);
}

?>
