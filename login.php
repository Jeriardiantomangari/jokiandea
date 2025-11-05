<?php
session_start();
include 'koneksi/koneksi.php';

$error = '';

if(isset($_POST['role'], $_POST['username'], $_POST['password'])){
    $role = $_POST['role'];
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    if($role == 'mahasiswa'){
        $query = mysqli_query($conn, "SELECT * FROM mahasiswa WHERE nim='$username' AND password='$password'");
        if(mysqli_num_rows($query) == 1){
            $user = mysqli_fetch_assoc($query);
            $_SESSION['login'] = true;
            $_SESSION['role'] = 'mahasiswa';
            $_SESSION['id_user'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['nim'] = $user['nim'];
            header("Location: mahasiswa/pembayaran/pembayaran.php");
            exit;
        }
    } elseif($role == 'dosen'){
        $query = mysqli_query($conn, "SELECT * FROM dosen WHERE nidn='$username' AND password='$password'");
        if(mysqli_num_rows($query) == 1){
            $user = mysqli_fetch_assoc($query);
            $_SESSION['login'] = true;
            $_SESSION['role'] = 'dosen';
            $_SESSION['id_user'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['nidn'] = $user['nidn'];
            header("Location: dosen/jadwal/jadwal.php");
            exit;
        }
    } elseif($role == 'admin'){
        $query = mysqli_query($conn, "SELECT * FROM admin WHERE username='$username' AND password='$password'");
        if(mysqli_num_rows($query) == 1){
            $user = mysqli_fetch_assoc($query);
            $_SESSION['login'] = true;
            $_SESSION['role'] = 'admin';
            $_SESSION['id_user'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['username'] = $user['username'];
            header("Location: admin/dosen/dosen.php");
            exit;
        }
    }

    $error = "Username, password, atau role salah!";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

   <style>
    * {
      box-sizing: border-box;
      font-family: Inter, system-ui, Arial;
    }

    body {
      margin: 0;
      background: #ffffff;
    }

    .wadah {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    .kartu {
      background: #ffffff;
      width: 400px;
      padding: 24px;
      margin: 20px;
      border-radius: 14px;
      color: #000000;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.15);
    }

    .gambar {
      width: 100%;
      height: 300px;
      object-fit: contain;
      margin-bottom: 14px;
    }

    .formulir-masuk label {
      display: block;
      font-size: 13px;
      margin-top: 6px;
    }

    .formulir-masuk input {
      width: 100%;
      outline: none;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid black;
      margin-top: 6px;
    }

    .input-sandi {
      position: relative;
    }

    .input-sandi input {
      padding-right: 40px;
    }

    .tombol-lihat-sandi {
      position: absolute;
      right: 12px;
      margin-top: 3px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      font-size: 18px;
      color: #333;
      opacity: 0.8;
    }

    .pilihan-peran {
      margin-top: 10px;
      font-size: 14px;
    }

    .kelompok-radio {
      display: flex;
      gap: 10px;
    }

    .kelompok-radio label {
      display: flex;
      align-items: center;
      gap: 5px;
      cursor: pointer;
      font-size: 14px;
    }

    .kelompok-radio input[type="radio"] {
      accent-color: #4c6ef5;
      width: 16px;
      height: 16px;
      cursor: pointer;
    }

    .tombol-masuk {
      width: 100%;
      padding: 10px;
      border-radius: 6px;
      border: none;
      margin-top: 14px;
      background: #fff;
      color: blue;
      font-weight: 700;
      cursor: pointer;
      border: 1px solid black;
    }

    .lupa-sandi {
      font-size: 14px;
      text-align: right;
      margin-top: 8px;
      cursor: pointer;
      text-decoration: none;
      display: block;
    }

    /* === Modal === */
    .lapisan-modal {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.5);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }

    .kotak-modal {
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      width: 280px;
      text-align: center;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.3);
    }

    .kotak-modal h2 {
      color: #4c6ef5;
      margin-bottom: 10px;
    }

    .kotak-modal p {
      color: #333;
      font-size: 14px;
    }

    .kotak-modal button {
      margin-top: 15px;
      padding: 8px 16px;
      background: #4c6ef5;
      color: #ffffff;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }

    @media screen and (max-width: 768px) {
      .lupa-sandi {
        margin-top: 20px;
      }
    }
  </style>
</head>

<body>
  <div class="wadah">
    <div class="kartu">
      <img src="gambar/login.png" alt="login" class="gambar" />

      <form action="" method="POST" class="formulir-masuk">
        <?php if($error) echo "<p style='color:red;'>$error</p>"; ?>
        <label><i style="margin-right: 7px; font-size: 15px;" class="fa-solid fa-user"></i> Email/Nidn/Npm </label>
        <input type="text" name="username" placeholder="nama pengguna" required />

        <label><i style="margin-right: 2px; font-size: 15px;" class="fa-solid fa-key"></i> Password </label>
        <div class="input-sandi">
          <input type="password" id="sandi" name="password" placeholder="kata sandi" required />
          <i id="tombolLihatSandi" class="fa-solid fa-eye tombol-lihat-sandi"></i>
        </div>

       <div class="pilihan-peran">
         <label><i class="fa-solid fa-user-gear" style="margin-right: 3px;"></i> Login sebagai:</label>
         <div class="kelompok-radio">
            <label><input type="radio" name="role" value="admin" required> Admin</label>
            <label><input type="radio" name="role" value="dosen" required> Dosen</label>
            <label><input type="radio" name="role" value="mahasiswa" required> Mahasiswa</label>
         </div>
       </div>

        <button type="submit" class="tombol-masuk">Masuk</button>
      </form>

      <div class="lupa-sandi" id="lupaSandi">Lupa Sandi?</div>
    </div>
  </div>

  <!-- Modal -->
  <div class="lapisan-modal" id="lapisanModal">
    <div class="kotak-modal">
      <h2>Informasi</h2>
      <p>Silakan hubungi admin untuk konfirmasi terkait masalah Anda.</p>
      <button id="tutupModal">Tutup</button>
    </div>
  </div>

  <script>
    const tombolLihatSandi = document.getElementById("tombolLihatSandi");
    const inputSandi = document.getElementById("sandi");
    const lupaSandi = document.getElementById("lupaSandi");
    const lapisanModal = document.getElementById("lapisanModal");
    const tutupModal = document.getElementById("tutupModal");

    tombolLihatSandi.addEventListener("click", () => {
      const tipe = inputSandi.getAttribute("type") === "password" ? "text" : "password";
      inputSandi.setAttribute("type", tipe);
      tombolLihatSandi.classList.toggle("fa-eye");
      tombolLihatSandi.classList.toggle("fa-eye-slash");
    });

    lupaSandi.addEventListener("click", () => {
      lapisanModal.style.display = "flex";
    });

    tutupModal.addEventListener("click", () => {
      lapisanModal.style.display = "none";
    });
  </script>
</body>
</html>
