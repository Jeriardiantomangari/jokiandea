<?php
include '../../koneksi/koneksi.php';

$aksi = $_POST['aksi'] ?? '';
$id = $_POST['id'] ?? 0;

if($aksi && $id){
    if($aksi == 'setujui'){
        mysqli_query($conn, "UPDATE kontrak_mk SET status='Disetujui' WHERE id='$id'");
    } elseif($aksi == 'tolak'){
        mysqli_query($conn, "UPDATE kontrak_mk SET status='Ditolak' WHERE id='$id'");
    }
}
?>
