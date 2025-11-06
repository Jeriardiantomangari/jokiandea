<?php
session_start();
header('Content-Type: application/json');
include '../../koneksi/koneksi.php';

// pastikan hanya admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
  echo json_encode(['error' => 'Unauthorized']); 
  exit;
}

$aksi = $_POST['aksi'] ?? $_GET['aksi'] ?? '';

function out($arr){ echo json_encode($arr); exit; }

// ========== 1. Ambil data satu user ==========
if ($aksi === 'ambil') {
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) out(['error'=>'ID tidak valid']);

  $stmt = $conn->prepare("SELECT id, nama, username, role FROM users WHERE id=? LIMIT 1");
  if(!$stmt) out(['error'=>'Gagal menyiapkan query']);
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  $stmt->close();

  if(!$row) out(['error'=>'Data tidak ditemukan']);
  out($row);
}

// ========== 2. Update data user (Edit) ==========
if ($aksi === 'simpan') {
  $id       = (int)($_POST['id'] ?? 0);
  $nama     = trim($_POST['nama'] ?? '');
  $username = trim($_POST['username'] ?? '');
  $password = trim($_POST['password'] ?? ''); // boleh kosong
  $role     = trim($_POST['role'] ?? '');

  if ($id <= 0) out(['error'=>'ID tidak valid']);
  if ($nama==='' || $username==='' || $role==='') out(['error'=>'Semua field wajib diisi.']);

  // cek duplikasi username kecuali diri sendiri
  $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username=? AND id<>?");
  $stmt->bind_param('si', $username, $id);
  $stmt->execute();
  $stmt->bind_result($ada);
  $stmt->fetch();
  $stmt->close();
  if ($ada > 0) out(['error'=>'Username sudah digunakan.']);

  if ($password !== '') {
    // versi sesuai database kamu (plain text)
    // kalau mau aman: ganti jadi $password = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE users SET nama=?, username=?, password=?, role=?, updated_at=NOW() WHERE id=?");
    $stmt->bind_param('ssssi', $nama, $username, $password, $role, $id);
  } else {
    $stmt = $conn->prepare("UPDATE users SET nama=?, username=?, role=?, updated_at=NOW() WHERE id=?");
    $stmt->bind_param('sssi', $nama, $username, $role, $id);
  }

  $ok = $stmt && $stmt->execute();
  if($stmt) $stmt->close();
  if(!$ok) out(['error'=>'Gagal menyimpan data.']);
  out(['ok'=>true]);
}

// ========== 3. Hapus user ==========
if ($aksi === 'hapus') {
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) out(['error'=>'ID tidak valid']);

  // opsional: jangan hapus user yang sedang login
  if (!empty($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $id) {
    out(['error'=>'Tidak bisa menghapus akun yang sedang login.']);
  }

  $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
  $stmt->bind_param('i', $id);
  $ok = $stmt && $stmt->execute();
  if($stmt) $stmt->close();
  if(!$ok) out(['error'=>'Gagal menghapus data.']);
  out(['ok'=>true]);
}

out(['error'=>'Aksi tidak dikenal.']);
