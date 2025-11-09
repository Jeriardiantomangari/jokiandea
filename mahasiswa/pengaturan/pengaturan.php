<?php
session_start();
include '../../koneksi/sidebarmhs.php';
include '../../koneksi/koneksi.php';

// Wajib: hanya mahasiswa
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mahasiswa') {
  header("Location: ../index.php"); exit;
}
function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

$id_mhs = (int)($_SESSION['mhs_id'] ?? 0);
if ($id_mhs <= 0) { header("Location: ../login.php"); exit; }

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$token_csrf = $_SESSION['csrf_token'];

// Ambil data mahasiswa + user
$q = mysqli_prepare($conn,"
  SELECT m.nim, m.nama, u.id AS id_user
  FROM mahasiswa m
  LEFT JOIN users u ON u.id = m.user_id
  WHERE m.id = ?
  LIMIT 1
");
mysqli_stmt_bind_param($q, "i", $id_mhs);
mysqli_stmt_execute($q);
$res = mysqli_stmt_get_result($q);
$akun = mysqli_fetch_assoc($res) ?: [];
mysqli_stmt_close($q);

$id_user = (int)($akun['id_user'] ?? 0);
$pesan = ['jenis' => '', 'isi' => ''];


// PROSES GANTI PASSWORD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['token_csrf'] ?? '';
  if (!hash_equals($_SESSION['csrf_token'], $token)) {
    $pesan = ['jenis' => 'gagal', 'isi' => 'Token CSRF tidak valid.'];
  } elseif ($id_user <= 0) {
    $pesan = ['jenis' => 'gagal', 'isi' => 'Akun pengguna tidak valid.'];
  } else {
    $pass_lama = $_POST['password_lama'] ?? '';
    $pass_baru = $_POST['password_baru'] ?? '';
    $pass_konf = $_POST['password_konfirmasi'] ?? '';

    if ($pass_lama === '' || $pass_baru === '' || $pass_konf === '') {
      $pesan = ['jenis' => 'gagal', 'isi' => 'Semua kolom wajib diisi.'];
    } elseif (strlen($pass_baru) < 8) {
      $pesan = ['jenis' => 'gagal', 'isi' => 'Password baru minimal 8 karakter.'];
    } elseif ($pass_baru !== $pass_konf) {
      $pesan = ['jenis' => 'gagal', 'isi' => 'Konfirmasi password tidak cocok.'];
    } else {
      $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id=? LIMIT 1");
      mysqli_stmt_bind_param($stmt, "i", $id_user);
      mysqli_stmt_execute($stmt);
      $rs = mysqli_stmt_get_result($stmt);
      $row = mysqli_fetch_assoc($rs) ?: [];
      mysqli_stmt_close($stmt);

      $pwd_db = $row['password'] ?? '';
      if ($pwd_db !== $pass_lama) {
        $pesan = ['jenis' => 'gagal', 'isi' => 'Password lama salah.'];
      } else {
        $ubah = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
        mysqli_stmt_bind_param($ubah, "si", $pass_baru, $id_user);
        if (mysqli_stmt_execute($ubah)) {
          $pesan = ['jenis' => 'berhasil', 'isi' => 'Password berhasil diganti.'];
        } else {
          $pesan = ['jenis' => 'gagal', 'isi' => 'Gagal mengganti password.'];
        }
        mysqli_stmt_close($ubah);
      }
    }
  }
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengaturan Akun</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f6fa
        }

        .bungkus {
            margin-left: 250px;
            margin-top: 60px;
            padding: 20px;
            min-height: calc(100vh - 60px)
        }

        .pembungkus {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 16px;
            width: 100%;
        }

        .kartu {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .06);
            padding: 16px;
            width: 100%
        }

        label {
            display: block;
            font-weight: 600;
            margin: 8px 0 6px
        }

        .grup-sandi {
            position: relative
        }

        .grup-sandi input {
            width: 100%;
            padding: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff
        }

        .tombol-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #555;
            font-size: 15px;
        }

        .tombol-utama {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #0ea5e9;
            color: #fff;
            border: none;
            padding: 10px 14px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer
        }

        .pesan {
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 12px
        }

        .pesan.berhasil {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #34d399
        }

        .pesan.gagal {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fca5a5
        }

        @media screen and (max-width: 768px) {
            .bungkus {
                margin-left: 0;
                padding: 20px;
                width: 100%;
                text-align: center;
            }
            
            .pembungkus {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 12px;
                width: 100%;
            }

            .kartu {
                width: 100%;
                max-width: 640px;
                margin: 0 auto;
                text-align: left;
            }

            h2,
            .kartu h3 {
                text-align: center;
            }
            .grup-sandi .tombol-toggle {
                right: 8px;
            }
        }
    </style>
</head>

<body>
    <div class="bungkus">

        <?php if($pesan['isi']): ?>
        <div class="pesan <?= $pesan['jenis']==='berhasil' ? 'berhasil' : 'gagal' ?>">
            <?= e($pesan['isi']) ?>
        </div>
        <?php endif; ?>

        <div class="pembungkus">
            <div class="kartu">
                <h3><i class="fa-solid fa-shield-halved"></i> Keamanan Akun</h3>
                <form method="post" autocomplete="off">
                    <input type="hidden" name="token_csrf" value="<?= e($token_csrf) ?>">

                    <label>Password Sekarang</label>
                    <div class="grup-sandi">
                        <input type="password" name="password_lama" id="sandi_lama" required>
                        <button type="button" class="tombol-toggle" onclick="tukar('sandi_lama', this)">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>

                    <label>Password Baru (min. 8 karakter)</label>
                    <div class="grup-sandi">
                        <input type="password" name="password_baru" id="sandi_baru" minlength="8" required>
                        <button type="button" class="tombol-toggle" onclick="tukar('sandi_baru', this)">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>

                    <label>Konfirmasi Password Baru</label>
                    <div class="grup-sandi">
                        <input type="password" name="password_konfirmasi" id="sandi_konf" minlength="8" required>
                        <button type="button" class="tombol-toggle" onclick="tukar('sandi_konf', this)">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>

                    <div style="margin-top:10px">
                        <button class="tombol-utama" type="submit"><i class="fa-solid fa-key"></i> Ganti
                            Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function tukar(id, tombol) {
            const input = document.getElementById(id);
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
            tombol.querySelector('i').classList.toggle('fa-eye');
            tombol.querySelector('i').classList.toggle('fa-eye-slash');
        }
    </script>
</body>

</html>