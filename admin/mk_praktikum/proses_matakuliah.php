<?php
include '../../koneksi/koneksi.php'; 

$aksi = $_POST['aksi'] ?? '';

if ($aksi == 'ambil') {
    $id = intval($_POST['id']);
    $q = mysqli_query($conn, "SELECT * FROM matakuliah_praktikum WHERE id = $id");
    echo json_encode(mysqli_fetch_assoc($q));
    exit;
}

elseif ($aksi == 'hapus') {
    $id = intval($_POST['id']);
    // hapus file modul juga
    $get = mysqli_query($conn, "SELECT modul FROM matakuliah_praktikum WHERE id=$id");
    $data = mysqli_fetch_assoc($get);
    if ($data && $data['modul'] && file_exists("../uploads/modul/".$data['modul'])) {
        unlink("../../uploads/modul/".$data['modul']);
    }
    mysqli_query($conn, "DELETE FROM matakuliah_praktikum WHERE id = $id");
    exit;
}

else {
    $id = intval($_POST['id']);
    $kode_mk = mysqli_real_escape_string($conn, $_POST['kode_mk']);
    $nama_mk = mysqli_real_escape_string($conn, $_POST['nama_mk']);
    $sks = intval($_POST['sks']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $modul = "";

    // Upload file PDF
if (isset($_FILES['modul']) && $_FILES['modul']['error'] == 0) {
    $target_dir = "../../uploads/modul/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    // Ambil ekstensi file (misalnya .pdf)
    $ext = pathinfo($_FILES['modul']['name'], PATHINFO_EXTENSION);

    // Bersihkan nama mata kuliah dari spasi & karakter khusus
    $nama_mk_bersih = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nama_mk);

    // Buat nama file baru sesuai format yang kamu mau
    $file_name = "Modul_Praktikum_" . $nama_mk_bersih . "." . $ext;

    // Jika file sudah ada, tambahkan waktu biar unik
    if (file_exists($target_dir . $file_name)) {
        $file_name = "Modul_Praktikum_" . $nama_mk_bersih . "_" . time() . "." . $ext;
    }

    $target_file = $target_dir . $file_name;
    move_uploaded_file($_FILES["modul"]["tmp_name"], $target_file);
    $modul = $file_name;
}


    if ($id) {
        // Jika edit
        if ($modul != "") {
            // Hapus file lama
            $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT modul FROM matakuliah_praktikum WHERE id=$id"));
            if ($old && $old['modul'] && file_exists("../../uploads/modul/".$old['modul'])) {
                unlink("../../uploads/modul/".$old['modul']);
            }
            $query = "UPDATE matakuliah_praktikum SET 
                kode_mk='$kode_mk', nama_mk='$nama_mk', sks='$sks', semester='$semester', modul='$modul'
                WHERE id=$id";
        } else {
            $query = "UPDATE matakuliah_praktikum SET 
                kode_mk='$kode_mk', nama_mk='$nama_mk', sks='$sks', semester='$semester'
                WHERE id=$id";
        }
        mysqli_query($conn, $query);
    } else {
        // Tambah baru
        $query = "INSERT INTO matakuliah_praktikum (kode_mk, nama_mk, sks, semester, modul)
                  VALUES ('$kode_mk','$nama_mk','$sks','$semester','$modul')";
        mysqli_query($conn, $query);
    }
    exit;
}
?>
