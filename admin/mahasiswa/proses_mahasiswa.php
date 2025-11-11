<?php
include '../../koneksi/koneksi.php'; 

$aksi = isset($_POST['aksi']) ? $_POST['aksi'] : '';

if ($aksi == 'ambil') {
    $id = intval($_POST['id']);
    $q  = mysqli_query($conn, "SELECT * FROM mahasiswa WHERE id=$id");
    echo json_encode(mysqli_fetch_assoc($q));
    exit;
}
elseif ($aksi == 'hapus') {
    $id = intval($_POST['id']);
    mysqli_query($conn, "DELETE FROM mahasiswa WHERE id=$id");
    exit;
}
else {
    $id            = intval($_POST['id']);
    $nim           = mysqli_real_escape_string($conn, $_POST['nim']);
    $nama          = mysqli_real_escape_string($conn, $_POST['nama']);
    $jurusan       = mysqli_real_escape_string($conn, $_POST['jurusan']);
    $jenis_kelamin = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $alamat        = mysqli_real_escape_string($conn, $_POST['alamat']);
    $no_hp         = mysqli_real_escape_string($conn, $_POST['no_hp']);

    if ($id) {
        // Ambil data lama untuk cek perubahan NIM & ambil user_id
        $resOld  = mysqli_query($conn, "SELECT user_id, nim AS old_nim FROM mahasiswa WHERE id=$id");
        $old     = mysqli_fetch_assoc($resOld);
        $old_nim = $old ? $old['old_nim'] : null;
        $user_id = $old ? (int)$old['user_id'] : 0;

        // Update data mahasiswa
        mysqli_query($conn, "UPDATE mahasiswa SET
            nim='$nim',
            nama='$nama',
            jurusan='$jurusan',
            jenis_kelamin='$jenis_kelamin',
            alamat='$alamat',
            no_hp='$no_hp'
            WHERE id=$id
        ");

        // Sinkron ke tabel users
        if ($user_id > 0) {
            if ($old_nim !== $nim) {
                // Jika NIM berubah → username & password ikut berubah ke NIM baru
                mysqli_query($conn, "
                    UPDATE users
                    SET username = '$nim',
                        password = '$nim',
                        nama = '$nama',
                        role = 'MAHASISWA'
                    WHERE id = $user_id
                ");
            } else {
                // Hanya sinkronkan nama
                mysqli_query($conn, "
                    UPDATE users
                    SET nama = '$nama'
                    WHERE id = $user_id
                ");
            }
        } else {
            // Legacy: belum punya user → buat sekarang
            mysqli_query($conn, "
                INSERT INTO users (nama, username, password, role)
                VALUES ('$nama', '$nim', '$nim', 'MAHASISWA')
                ON DUPLICATE KEY UPDATE
                    id = LAST_INSERT_ID(id),
                    nama = VALUES(nama),
                    role = 'MAHASISWA',
                    password = VALUES(password)
            ");
            $new_user_id = mysqli_insert_id($conn);
            if ($new_user_id > 0) {
                mysqli_query($conn, "UPDATE mahasiswa SET user_id = $new_user_id WHERE id = $id");
            }
        }

    } else {
        // Tambah mahasiswa
        mysqli_query($conn, "INSERT INTO mahasiswa (nim, nama, jurusan, jenis_kelamin, alamat, no_hp) VALUES (
            '$nim', '$nama', '$jurusan', '$jenis_kelamin', '$alamat', '$no_hp'
        )");
        $id_mhs_baru = mysqli_insert_id($conn);

        // Buat/ambil user: username & password awal = NIM (plain)
        mysqli_query($conn, "
            INSERT INTO users (nama, username, password, role)
            VALUES ('$nama', '$nim', '$nim', 'MAHASISWA')
            ON DUPLICATE KEY UPDATE
                id = LAST_INSERT_ID(id),
                nama = VALUES(nama),
                role = 'MAHASISWA',
                password = VALUES(password)
        ");
        $user_id_baru = mysqli_insert_id($conn);

        // Tautkan user ke mahasiswa
        if ($user_id_baru > 0) {
            mysqli_query($conn, "UPDATE mahasiswa SET user_id = $user_id_baru WHERE id = $id_mhs_baru");
        }
    }

    exit;
}
?>
