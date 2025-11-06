<?php
include '../../koneksi/koneksi.php';

header('Content-Type: text/plain; charset=utf-8');

$aksi = $_POST['aksi'] ?? '';

if ($aksi == 'ambil') {
    $id = (int)($_POST['id'] ?? 0);
    $q = mysqli_query($conn, "SELECT * FROM kontrak_mk WHERE id='$id'");
    $data = mysqli_fetch_assoc($q);
    header('Content-Type: application/json');
    echo json_encode($data ?: []);
    exit;
}

if ($aksi == 'hapus') {
    $id = (int)($_POST['id'] ?? 0);
    if(!$id){ echo "error|ID tidak valid"; exit; }

    // Hapus kontrak: pilihan_jadwal.id_kontrak akan jadi NULL (FK SET NULL)
    $ok = mysqli_query($conn, "DELETE FROM kontrak_mk WHERE id='$id'");
    if(!$ok){ echo "error|Gagal menghapus data"; exit; }
    echo "ok|Data dihapus";
    exit;
}

// simpan (insert/update)
$id           = (int)($_POST['id'] ?? 0);
$id_mahasiswa = (int)($_POST['id_mahasiswa'] ?? 0);
$nim          = trim($_POST['nim'] ?? '');
$nama         = trim($_POST['nama'] ?? '');
$no_hp        = trim($_POST['no_hp'] ?? '');
$mk_dikontrak = isset($_POST['mk_dikontrak']) ? implode(',', $_POST['mk_dikontrak']) : '';
$status       = $_POST['status'] ?? 'Menunggu';

// Ambil semester aktif
$semester_query = mysqli_query($conn, "SELECT id FROM semester WHERE status='Aktif' LIMIT 1");
$semester = mysqli_fetch_assoc($semester_query);
$id_semester = $semester['id'] ?? null;

if(!$id_semester){
    echo "error|Tidak ada semester Aktif. Hubungi admin.";
    exit;
}

// Validasi dasar
if(!$id_mahasiswa || $nim==='' || $nama===''){
    echo "error|Data mahasiswa tidak lengkap";
    exit;
}
if($mk_dikontrak===''){
    echo "error|Pilih minimal 1 mata kuliah";
    exit;
}

// Upload bukti pembayaran (opsional saat edit)
$bukti = '';
if (isset($_FILES['bukti_pembayaran']) && is_array($_FILES['bukti_pembayaran']) && ($_FILES['bukti_pembayaran']['name'] ?? '') !== '') {
    $file = $_FILES['bukti_pembayaran'];
    $nama_asli = $file['name'];
    $tmp = $file['tmp_name'];
    $size = (int)$file['size'];

    // Validasi tipe sederhana
    $ext = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));
    $allowed = ['pdf','jpg','jpeg','png'];
    if(!in_array($ext, $allowed)){
        echo "error|Tipe file harus pdf/jpg/jpeg/png";
        exit;
    }
    // Batas 2MB
    if($size > 2*1024*1024){
        echo "error|Ukuran file maksimal 2MB";
        exit;
    }

    // Simpan
    $nama_file = time() . '_' . preg_replace('/[^A-Za-z0-9_\.-]/','_', $nama_asli);
    if(!is_dir('../../uploads/pembayaran/')){ @mkdir('../../uploads/pembayaran/', 0775, true); }
    if(!move_uploaded_file($tmp, '../../uploads/pembayaran/' . $nama_file)){
        echo "error|Gagal mengupload file";
        exit;

    }
    $bukti = $nama_file;
}

if ($id === 0) {
    // Cek duplikat kontrak di semester aktif
    $cek_duplikat = mysqli_query($conn, "
        SELECT id FROM kontrak_mk 
        WHERE id_mahasiswa='$id_mahasiswa' AND id_semester='$id_semester' LIMIT 1
    ");
    if (mysqli_num_rows($cek_duplikat) > 0) {
        echo "error|Anda sudah mengupload kontrak di semester ini";
        exit;
    }

    // Insert baru
    $sql = "INSERT INTO kontrak_mk (id_mahasiswa, id_semester, nim, nama, no_hp, mk_dikontrak, status, bukti_pembayaran)
            VALUES ('$id_mahasiswa', '$id_semester', '".mysqli_real_escape_string($conn,$nim)."', '".mysqli_real_escape_string($conn,$nama)."', '".mysqli_real_escape_string($conn,$no_hp)."', '".mysqli_real_escape_string($conn,$mk_dikontrak)."', '".mysqli_real_escape_string($conn,$status)."', '".mysqli_real_escape_string($conn,$bukti)."')";
    if(!mysqli_query($conn, $sql)){
        echo "error|Gagal menyimpan data";
        exit;
    }
    echo "ok|Data berhasil disimpan";
    exit;

} else {
    // Update: hanya ubah MK & (jika diupload) bukti. Tidak boleh pindah semester.
    if ($bukti != '') {
        $sql = "UPDATE kontrak_mk 
                SET mk_dikontrak='".mysqli_real_escape_string($conn,$mk_dikontrak)."',
                    bukti_pembayaran='".mysqli_real_escape_string($conn,$bukti)."'
                WHERE id='$id'";
    } else {
        $sql = "UPDATE kontrak_mk 
                SET mk_dikontrak='".mysqli_real_escape_string($conn,$mk_dikontrak)."'
                WHERE id='$id'";
    }
    if(!mysqli_query($conn, $sql)){
        echo "error|Gagal mengubah data";
        exit;
    }
    echo "ok|Data berhasil diubah";
    exit;
}