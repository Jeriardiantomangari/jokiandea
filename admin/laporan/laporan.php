<?php
session_start();
include '../../koneksi/koneksi.php';
include '../../koneksi/sidebar.php';

// Cek role admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit;
}
function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Data Praktikum</title>

<!-- DataTables + jQuery -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- jsPDF + autoTable (untuk cetak PDF seperti halaman dosen) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<style>
/* ==== GAYA MENGIKUTI HALAMAN DOSEN ==== */
.konten-utama{
  margin-left:250px; margin-top:60px; padding:30px;
  min-height:calc(100vh - 60px); background:#f9f9f9; font-family:Arial,sans-serif;
}
.konten-utama h2{ margin-bottom:20px; color:#333; }

.tombol{ border:none; border-radius:5px; cursor:pointer; color:white; font-size:10px; transition:.3s; }
.tombol:hover{ opacity:.85; }
.tombol-cetak{ background:#28a745; margin-right:10px; padding:8px 15px; }
.tombol-reset{ background:#6c757d; margin-right:10px; padding:8px 15px; }

.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select{
  padding:6px 10px; border-radius:5px; border:1px solid #ccc; font-size:14px; margin-bottom:5px;
}

.tabel-praktikum{ width:100%; border-collapse:collapse; background:white;
  border-radius:10px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.1); table-layout:fixed;}
.tabel-praktikum th{ background:#8bc9ff; color:#333; text-align:left; padding:12px 15px; }
.tabel-praktikum td{ padding:12px 15px; border-bottom:1px solid #ddd; border-right:1px solid #ddd; }
.tabel-praktikum tr:hover{ background:#f1f1f1; }

.filters{ display:flex; gap:8px; margin-bottom:12px; align-items:center; flex-wrap:wrap; }
.filters select{ padding:8px 10px; border:1px solid #ccc; border-radius:6px; min-width:220px; }

.hint{
  margin:8px 0 14px; padding:10px 12px; border-radius:8px;
  background:#eef7ff; color:#114d7a; display:none;
}

/* ==== Responsive seperti halaman dosen ==== */
@media screen and (max-width: 768px){
  .konten-utama{ margin-left:0; padding:20px; width:100%; text-align:center; }
  .konten-utama h2{ text-align:center; }
  .tabel-praktikum, thead, tbody, th, td, tr{ display:block; }
  thead tr{ display:none; }
  tr{ margin-bottom:15px; border-bottom:2px solid #000; }
  td{ text-align:right; padding-left:50%; position:relative; }
  td::before{
    content: attr(data-label); position:absolute; left:15px; width:45%; font-weight:bold; text-align:left;
  }
  .tombol-cetak, .tombol-reset{
  display:block;          /* biar tampil satu per satu (full width di mobile) */
  width:50%;             /* penuh selebar layar */
  max-width:100%;         /* hindari meluber */
  margin:6px 0;           /* beri jarak antar tombol */
  font-size:14px;         /* lebih besar, mudah dibaca */
  padding:10px 0;         /* tinggi lebih nyaman di-tap */
  border-radius:8px;      /* sudut lembut */
}

}
</style>
</head>
<body>
<div class="konten-utama">
  <h2>Data Praktikum</h2>

  <!-- tombol -->
  <div class="filters">
    <button id="btn-cetak" class="tombol tombol-cetak" disabled>
      <i class="fa-solid fa-print"></i> Cetak
    </button>
    <button id="btn-reset" class="tombol tombol-reset">
      <i class="fa-solid fa-rotate"></i> Reset
    </button>

    <!-- Dropdown MK -->
    <select id="filter-mk" required>
  <option value="">Pilih Mata Kuliah</option>
  <?php
  $mk = mysqli_query($conn, "SELECT id, kode_mk, nama_mk FROM matakuliah_praktikum ORDER BY nama_mk ASC");
  while($r = mysqli_fetch_assoc($mk)){
    // TAMPILKAN HANYA NAMA MK
    echo "<option value='".(int)$r['id']."'>".e($r['nama_mk'])."</option>";
  }
  ?>
</select>


    <!-- Dropdown Shift -->
    <select id="filter-shift" disabled>
      <option value="">Pilih Shift</option>
    </select>
  </div>

  <!-- info terpilih -->
  <div id="hint" class="hint"></div>

  <!-- tabel -->
  <table id="tabel-peserta" class="tabel-praktikum display" style="width:100%">
    <thead>
      <tr>
        <th>No</th>
        <th>NIM</th>
        <th>Nama</th>
        <th>No HP</th>
        <th>Jenis Kelamin</th>
        <th>Jurusan</th>
        <th>Alamat</th>
        <th>Shift / Waktu</th>
        <th>Pengajar</th>
        <th>Ruangan</th>
        <th>Tgl Daftar</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<script>
  // ====== Util kecil ======
  function hhmm(s){ return (s||'').toString().slice(0,5); }

  // ====== Init DataTable sederhana ======
  let dt;
  $(function(){
    dt = $('#tabel-peserta').DataTable({
      pageLength: 10,
      lengthMenu: [5,10,25,50],
      language: {
        decimal: "",
        emptyTable: "Tidak ada data tersedia",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
        infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
        infoFiltered: "(disaring dari _MAX_ data total)",
        lengthMenu: "Tampilkan _MENU_ data",
        loadingRecords: "Memuat...",
        processing: "Sedang diproses...",
        search: "Cari:",
        zeroRecords: "Tidak ditemukan data yang sesuai",
        paginate: { first:"Pertama", last:"Terakhir", next:"Berikutnya", previous:"Sebelumnya" }
      }
    });

    // Toggle tombol cetak otomatis berdasarkan isi tabel
    dt.on('draw', function(){
      const ada = dt.rows({filter:'applied'}).count() > 0;
      $('#btn-cetak').prop('disabled', !ada);
    });

    // Reset
    $('#btn-reset').on('click', function(){
      $('#filter-mk').val('');
      $('#filter-shift').prop('disabled',true).html('<option value="">Pilih Shift</option>');
      // kosongkan tabel
      dt.clear().draw();
      $('#hint').hide().text('');
      $('#btn-cetak').prop('disabled', true);
    });

    // Pilih MK → ambil shift (tampilkan HARI + JAM saja)
    $('#filter-mk').on('change', function(){
      const id_mk = $(this).val();

      // reset state
      $('#filter-shift').prop('disabled',true).html('<option value="">Pilih Shift</option>');
      dt.clear().draw();
      $('#hint').hide().text('');
      $('#btn-cetak').prop('disabled', true);

      if(!id_mk) return;

      $.post('proses_data_praktikum.php', {aksi:'list_shift', id_mk:id_mk}, function(resRaw){
        let res = resRaw;
        if (typeof resRaw === 'string') { try { res = JSON.parse(resRaw); } catch(_){} }

        if(res.error){ alert(res.error); return; }

        // Bangun opsi: "Hari HH:MM-HH:MM" — urut jam mulai
        const shifts = Array.isArray(res.shifts) ? res.shifts.slice() : [];
        shifts.sort((a,b)=> String(a.jam_mulai).localeCompare(String(b.jam_mulai)));

        if(!shifts.length){
          $('#filter-shift').prop('disabled',true).html('<option value="">Pilih Shift</option>');
          $('#hint').show().text('Shift tidak tersedia.');
          return;
        }

        let html = '<option value="">Pilih Shift</option>';
        shifts.forEach(s=>{
          const hari = (s.hari||'').toString().trim();
          const jam  = `${hhmm(s.jam_mulai)}-${hhmm(s.jam_selesai)}`;
          html += `<option value="${s.id}">${hari} ${jam}</option>`;
        });
        $('#filter-shift').html(html).prop('disabled', false);

        // Auto pilih shift paling pagi (opsi pertama setelah placeholder)
        if($('#filter-shift option').length>1){
          $('#filter-shift').prop('selectedIndex',1).trigger('change');
        }
      }).fail(xhr=>{
        alert('Gagal memuat shift: ' + (xhr.responseText || xhr.statusText));
      });
    });

    // Pilih Shift → ambil peserta → render tbody sederhana
    $('#filter-shift').on('change', function(){
      const id_mk = $('#filter-mk').val();
      const id_jadwal = $(this).val();

      dt.clear().draw();
      $('#btn-cetak').prop('disabled', true);
      $('#hint').hide().text('');

      if(!id_mk || !id_jadwal) return;

      $.post('proses_data_praktikum.php', {aksi:'list_peserta', id_mk:id_mk, id_jadwal:id_jadwal}, function(resRaw){
        let res = resRaw;
        if (typeof resRaw === 'string') { try { res = JSON.parse(resRaw); } catch(_){} }

        if(res.error){ alert(res.error); return; }

        // Info kecil di atas tabel
        const mk    = (res.meta && res.meta.mk)    ? res.meta.mk    : '';
        const shift = (res.meta && res.meta.shift) ? res.meta.shift : '';
        const sub   = [mk, shift].filter(Boolean).join(' • ');
        if(sub){ $('#hint').show().text(sub); }

        // Render rows -> tbody kemudian re-parse DataTable
        const rows = Array.isArray(res.rows) ? res.rows : [];
        const $tb  = $('#tabel-peserta tbody');
        let htmlRows = '';
        rows.forEach(r=>{
          htmlRows += `
            <tr>
              <td>${r.no||''}</td>
              <td>${r.nim||''}</td>
              <td>${r.nama||''}</td>
              <td>${r.no_hp||''}</td>
              <td>${r.jk||''}</td>
              <td>${r.jurusan||''}</td>
              <td>${r.alamat||''}</td>
              <td>${r.shift||''}</td>
              <td>${r.dosen||''}</td>
              <td>${r.ruangan||''}</td>
              <td>${r.tanggal||''}</td>
            </tr>`;
        });
        $tb.html(htmlRows);

        // Sinkronkan DataTable dengan DOM baru
        dt.clear();
        dt.rows.add($('#tabel-peserta tbody tr'));
        dt.draw(); // event 'draw' akan toggle tombol cetak
      })
      .fail(xhr=>{
        alert('Gagal memuat data: ' + (xhr.responseText || xhr.statusText));
      });
    });

    // Cetak (gaya sama seperti Data Dosen)
    $('.tombol-cetak').on('click', function(){
      const ns = (window.jspdf && window.jspdf.jsPDF) ? window.jspdf : null;
      const jsPDF = ns ? ns.jsPDF : (window.jsPDF || null);
      if(!jsPDF){ alert('jsPDF belum termuat. Cek CDN.'); return; }

      const doc = new jsPDF({orientation:'landscape', unit:'mm', format:'a4'});
      const pageW = doc.internal.pageSize.getWidth();
      const cx = pageW/2;

      doc.setFontSize(14);
      doc.text('Daftar Peserta Praktikum', cx, 14, {align:'center'});
      const subtitle = $('#hint').is(':visible') ? $('#hint').text() : '';
      if(subtitle){ doc.setFontSize(10); doc.text(subtitle, cx, 20, {align:'center'}); }

      // Ambil header dari thead
      const headers = [];
      $('#tabel-peserta thead th').each(function(){ headers.push($(this).text()); });

      // Ambil body dari tbody
      const data = [];
      $('#tabel-peserta tbody tr').each(function(){
        const row = [];
        $(this).find('td').each(function(){ row.push($(this).text()); });
        data.push(row);
      });

      if(typeof doc.autoTable !== 'function'){
        alert('Plugin autoTable belum termuat. Cek CDN.');
        return;
      }

      doc.autoTable({
        head: [headers],
        body: data,
        startY: 26,
        theme: 'grid',
        headStyles: { fillColor:[139,201,255], textColor:20 },
        styles: { fontSize: 9, cellPadding: 2 },
        margin: { top: 26, left: 10, right: 10 }
      });

      doc.save('Data_Peserta_Praktikum.pdf');
    });
  });
</script>


</body>
</html>
