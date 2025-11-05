<?php
session_start();
include 'koneksi/koneksi.php';

$error = '';

if (isset($_POST['role'], $_POST['username'], $_POST['password'])) {
    // Ambil input
    $role_in   = strtolower(trim($_POST['role']));     // 'admin' | 'dosen' | 'mahasiswa'
    $role      = strtoupper($role_in);                 // 'ADMIN' | 'DOSEN' | 'MAHASISWA'
    $username  = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password  = mysqli_real_escape_string($conn, trim($_POST['password']));

    // 1) Autentikasi via tabel users
    $sql = "
        SELECT id, nama, username, password, role
        FROM users
        WHERE username = '$username'
          AND password = '$password'
          AND role     = '$role'
        LIMIT 1
    ";
    $res = mysqli_query($conn, $sql);

    if ($res && mysqli_num_rows($res) === 1) {
        $u = mysqli_fetch_assoc($res);

        // Set sesi umum
        $_SESSION['login']    = true;
        $_SESSION['role']     = $role_in;      // simpan versi lowercase agar konsisten dg kode lama
        $_SESSION['id_user']  = (int)$u['id']; // id di tabel users
        $_SESSION['nama']     = $u['nama'];
        $_SESSION['username'] = $u['username'];

        // 2) Routing & pengayaan sesi sesuai role
        if ($role_in === 'mahasiswa') {
            // coba berdasarkan user_id (utama), fallback ke NIM = username
            $qM = mysqli_query(
                $conn,
                "SELECT id, nim FROM mahasiswa WHERE user_id = {$u['id']} LIMIT 1"
            );
            if (!$qM || mysqli_num_rows($qM) === 0) {
                $qM = mysqli_query(
                    $conn,
                    "SELECT id, nim FROM mahasiswa WHERE nim = '{$u['username']}' LIMIT 1"
                );
            }
            if ($qM && mysqli_num_rows($qM) === 1) {
                $m = mysqli_fetch_assoc($qM);
                $_SESSION['mhs_id'] = (int)$m['id'];
                $_SESSION['nim']    = $m['nim'];
            }
            header("Location: mahasiswa/pembayaran/pembayaran.php");
            exit;
        }

        if ($role_in === 'dosen') {
            // coba berdasarkan user_id (utama), fallback ke NIDN = username
            $qD = mysqli_query(
                $conn,
                "SELECT id, nidn FROM dosen WHERE user_id = {$u['id']} LIMIT 1"
            );
            if (!$qD || mysqli_num_rows($qD) === 0) {
                $qD = mysqli_query(
                    $conn,
                    "SELECT id, nidn FROM dosen WHERE nidn = '{$u['username']}' LIMIT 1"
                );
            }
            if ($qD && mysqli_num_rows($qD) === 1) {
                $d = mysqli_fetch_assoc($qD);
                $_SESSION['dosen_id'] = (int)$d['id'];
                $_SESSION['nidn']     = $d['nidn'];
            }
            header("Location: dosen/jadwal/jadwal.php");
            exit;
        }

        if ($role_in === 'admin') {
            header("Location: admin/dosen/dosen.php");
            exit;
        }
    }

    // Jika gagal
    $error = "Username, password, atau role salah!";
}
?>
