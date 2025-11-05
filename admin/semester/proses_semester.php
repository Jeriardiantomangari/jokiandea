<?php
session_start();
include '../../koneksi/koneksi.php';


$aksi = $_POST['aksi'] ?? '';

/* ====== AMBIL DATA UNTUK MODAL EDIT ====== */
if ($aksi == 'ambil') {
    $id = (int)($_POST['id'] ?? 0);
    $data = mysqli_query($conn, "SELECT * FROM semester WHERE id='$id' LIMIT 1");
    echo json_encode(mysqli_fetch_assoc($data) ?: []);
    exit;
}

/* ====== HAPUS SEMESTER (CASCADE AKAN MEMBERSIHKAN DATA TERKAIT) ====== */
elseif ($aksi == 'hapus') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        mysqli_query($conn, "DELETE FROM semester WHERE id='$id'");
        // karena FK ke semester sudah ON DELETE CASCADE:
        // - kontrak_mk, pilihan_jadwal, jadwal_praktikum semester ini ikut terhapus otomatis
        echo "OK";
    } else {
        echo "ID tidak valid";
    }
    exit;
}

/* ====== TAMBAH / EDIT ====== */
else {
    $id     = $_POST['id'] ?? '';
    $nama   = mysqli_real_escape_string($conn, $_POST['nama_semester'] ?? '');
    $tahun  = mysqli_real_escape_string($conn, $_POST['tahun_ajaran'] ?? '');
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? '');

    if ($nama === '' || $tahun === '' || ($status !== 'Aktif' && $status !== 'Tidak Aktif')) {
        echo "Data tidak lengkap/valid";
        exit;
    }

    // Jika akan mengaktifkan, nonaktifkan semester lain supaya hanya ada 1 aktif
    if ($status === 'Aktif') {
        // Saat tambah: nonaktifkan semua yang aktif
        // Saat edit: nonaktifkan semua yang aktif kecuali dirinya (nanti di bawah)
        mysqli_query($conn, "UPDATE semester SET status='Tidak Aktif' WHERE status='Aktif'" . ($id !== '' ? " AND id <> ".(int)$id : ""));
    }

    if ($id) {
        // EDIT
        $id = (int)$id;

        // Ambil status lama untuk tahu apakah baru dinonaktifkan
        $lama = mysqli_query($conn, "SELECT status FROM semester WHERE id='$id' LIMIT 1");
        $rowLama = mysqli_fetch_assoc($lama);
        $statusLama = $rowLama ? $rowLama['status'] : '';

        mysqli_query($conn, "UPDATE semester 
                             SET nama_semester='$nama',
                                 tahun_ajaran='$tahun',
                                 status='$status'
                             WHERE id='$id'");

        // Jika BARU diubah menjadi "Tidak Aktif", bersihkan data pendaftaran semester ini
        if ($status === 'Tidak Aktif' && $statusLama !== 'Tidak Aktif') {
            mysqli_query($conn, "DELETE FROM pilihan_jadwal   WHERE id_semester='$id'");
            mysqli_query($conn, "DELETE FROM jadwal_praktikum WHERE id_semester='$id'");
            mysqli_query($conn, "DELETE FROM kontrak_mk       WHERE id_semester='$id'");
            // catatan: matakuliah_praktikum dibiarkan (FK SET NULL), modul tetap aman
        }

        echo "OK";
        exit;

    } else {
        // TAMBAH
        mysqli_query($conn, "INSERT INTO semester (nama_semester, tahun_ajaran, status)
                             VALUES ('$nama', '$tahun', '$status')");
        echo "OK";
        exit;
    }
}
