<?php
include '../../koneksi/koneksi.php'; 

$aksi = isset($_POST['aksi']) ? $_POST['aksi'] : '';

if ($aksi == 'ambil') {
    $id = intval($_POST['id']);
    $q  = mysqli_query($conn, "SELECT * FROM dosen WHERE id=$id");
    echo json_encode(mysqli_fetch_assoc($q));
    exit;
}
elseif ($aksi == 'hapus') {
    $id = intval($_POST['id']);
    // Hapus data dosen
    mysqli_query($conn, "DELETE FROM dosen WHERE id=$id");
    exit;
}
else {
    $id            = intval($_POST['id']);
    $nidn          = mysqli_real_escape_string($conn, $_POST['nidn']);
    $nama          = mysqli_real_escape_string($conn, $_POST['nama']);
    $prodi         = mysqli_real_escape_string($conn, $_POST['prodi']); 
    $jenis_kelamin = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $alamat        = mysqli_real_escape_string($conn, $_POST['alamat']);
    $no_hp         = mysqli_real_escape_string($conn, $_POST['no_hp']);



    if ($id) {
        // Ambil data lama untuk cek perubahan NIDN & ambil user_id
        $resOld = mysqli_query($conn, "SELECT user_id, nidn AS old_nidn FROM dosen WHERE id=$id");
        $old    = mysqli_fetch_assoc($resOld);
        $old_nidn = $old ? $old['old_nidn'] : null;
        $user_id  = $old ? (int)$old['user_id'] : 0;

        // Update data dosen
        mysqli_query($conn, "UPDATE dosen SET
            nidn='$nidn',
            nama='$nama',
            prodi='$prodi',
            jenis_kelamin='$jenis_kelamin',
            alamat='$alamat',
            no_hp='$no_hp'
            WHERE id=$id
        ");

        // Sinkron ke tabel users
        if ($user_id > 0) {
            if ($old_nidn !== $nidn) {
                // Jika NIDN berubah → username & password ikut berubah ke NIDN baru
                mysqli_query($conn, "
                    UPDATE users
                    SET username = '$nidn',
                        password = '$nidn',
                        nama = '$nama'
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
            mysqli_query($conn, "
                INSERT INTO users (nama, username, password, role)
                VALUES ('$nama', '$nidn', '$nidn', 'DOSEN')
                ON DUPLICATE KEY UPDATE
                    id = LAST_INSERT_ID(id),
                    nama = VALUES(nama),
                    role = 'DOSEN',
                    password = VALUES(password)  -- pastikan password = NIDN
            ");
            $new_user_id = mysqli_insert_id($conn);
            if ($new_user_id > 0) {
                mysqli_query($conn, "UPDATE dosen SET user_id = $new_user_id WHERE id = $id");
            }
        }

    } else {
        // Insert ke tabel dosen
        mysqli_query($conn, "INSERT INTO dosen (nidn, nama, prodi, jenis_kelamin, alamat, no_hp) VALUES (
            '$nidn', '$nama', '$prodi', '$jenis_kelamin', '$alamat', '$no_hp'
        )");
        $id_dosen_baru = mysqli_insert_id($conn);

        // Buat/ambil akun users: username & password awal = NIDN (plain)
        mysqli_query($conn, "
            INSERT INTO users (nama, username, password, role)
            VALUES ('$nama', '$nidn', '$nidn', 'DOSEN')
            ON DUPLICATE KEY UPDATE
                id = LAST_INSERT_ID(id),
                nama = VALUES(nama),
                role = 'DOSEN',
                password = VALUES(password) -- set password = NIDN
        ");
        $user_id_baru = mysqli_insert_id($conn);

        // Tautkan user ke dosen
        if ($user_id_baru > 0) {
            mysqli_query($conn, "UPDATE dosen SET user_id = $user_id_baru WHERE id = $id_dosen_baru");
        }
    }

    exit;
}
?>
