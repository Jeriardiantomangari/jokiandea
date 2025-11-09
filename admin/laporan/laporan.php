<?php
session_start();
include '../../koneksi/koneksi.php';
include '../../koneksi/sidebar.php';

// Cek role admin (case-insensitive)
if (!isset($_SESSION['role']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
  header("Location: ../index.php"); exit;
}

// helper aman untuk echo
function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// Ambil semester aktif (opsional)
$semAktif = mysqli_fetch_assoc(
  mysqli_query($conn, "SELECT id, nama_semester, tahun_ajaran FROM semester WHERE status='Aktif' LIMIT 1")
);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Admin - Data Praktikum</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- DataTables + jQuery -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

  <!-- FontAwesome (ikon) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- jsPDF + autoTable -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

  <style>
    /* ====== Global & Layout (seragam dgn halaman laporan) ====== */
    body { font-family: Arial, sans-serif; background:#f9f9f9; }
    .konten-utama { margin-left:250px; margin-top:60px; padding:30px; min-height:calc(100vh - 60px); background:#f9f9f9; }
    .konten-utama h2 { margin-bottom:10px; color:#333; }

    /* Info box */
    .kotak-info{ padding:10px 12px; border-radius:8px; margin:8px 0; background:#fff; border:1px solid #e5e7eb; color:#333; }

    /* ====== Panel Filter (seragam) ====== */
    .filter-panel{
      background:#fff; border:1px solid #e5e7eb; border-radius:12px;
      padding:14px 16px; box-shadow:0 2px 6px rgba(0,0,0,.06);
      display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; margin:10px 0 14px;
    }
    .field{ display:flex; flex-direction:column; gap:6px; }
    .field label{ font-weight:700; color:#111827; font-size:14px; }
    .select-styled{
      appearance:none; -webkit-appearance:none; -moz-appearance:none;
      background:#fff; border:1px solid #cbd5e1; border-radius:10px;
      padding:10px 42px 10px 12px; font-size:14px; line-height:1.2; min-width:240px; outline:none;
    }
    .select-styled:hover{ border-color:#94a3b8; }
    .select-styled:focus{ border-color:#60a5fa; box-shadow:0 0 0 4px rgba(96,165,250,.2); }

    .tombol-umum { border:none; border-radius:10px; cursor:pointer; color:white; font-size:13px; transition:.3s; padding:10px 16px; display:inline-flex; gap:8px; align-items:center; }
    .tombol-umum:hover { opacity:.9; }
    .tombol-cetak { background:#28a745; }
    .tombol-umum:disabled { opacity:.6; cursor:not-allowed; }

    /* ====== Tabel (seragam) ====== */
    .tabel-praktikum { width:100%; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.1); table-layout:fixed; }
    .tabel-praktikum th { background:#00AEEF; color:#333; text-align:left; padding:12px 15px; white-space:nowrap; }
    .tabel-praktikum td { padding:12px 15px; border-bottom:1px solid #ddd; }

    /* DataTables inputs */
    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
      padding:6px 10px; border-radius:5px; border:1px solid #ccc; font-size:14px; margin-bottom:5px;
    }

      /* Responsif */
    @media screen and (max-width: 768px) {
      .konten-utama { margin-left:0; padding:20px; text-align:center; }
      .filter-panel { flex-direction:column; align-items:stretch; gap:10px; }
      .field{ width:100%; }
      .select-styled{ min-width:unset; width:100%; }
      .tombol-umum{ width:100%; justify-content:center; }

      .tabel-laporan-absensi, thead, tbody, th, td, tr { display:block; }
      thead tr { display:none; }
      tr { margin-bottom:15px; border-bottom:2px solid #000; }
      td { text-align:right; padding-left:50%; position:relative; }
      td::before { content: attr(data-label); position:absolute; left:15px; width:45%; font-weight:bold; text-align:left; }
    }
  </style>
</head>
<body>
  <div class="konten-utama">
    <h2>Data Praktikum</h2>

    <?php if($semAktif): ?>
      <div class="kotak-info">
        Semester Aktif: <b><?= e($semAktif['nama_semester']) ?></b><b>/</b><b><?= e($semAktif['tahun_ajaran']) ?></b>
      </div>
    <?php endif; ?>

    <!-- Panel filter -->
    <form onsubmit="return false" class="filter-panel">
      <div class="field">
        <label for="filter-mk"><b>Pilih Mata Kuliah</b></label>
        <select id="filter-mk" class="select-styled">
          <option value="">Pilih Mata Kuliah</option>
          <?php
            $mk = mysqli_query($conn, "SELECT id, nama_mk FROM matakuliah_praktikum ORDER BY nama_mk ASC");
            while($r = mysqli_fetch_assoc($mk)){
              echo "<option value='".(int)$r['id']."'>".e($r['nama_mk'])."</option>";
            }
          ?>
        </select>
      </div>

      <div class="field">
        <label for="filter-shift"><b>Pilih Shift</b></label>
        <select id="filter-shift" class="select-styled" disabled>
          <option value="">Pilih Shift</option>
        </select>
      </div>

      <button id="btn-cetak" type="button" class="tombol-umum tombol-cetak" disabled>
        <i class="fa-solid fa-print"></i> Cetak PDF
      </button>
    </form>

    <!-- hint kecil (MK & shift terpilih) -->
    <div id="hint" class="kotak-info" style="display:none;"></div>

    <div class="kotak-tabel">
      <table id="tabel-peserta" class="tabel-praktikum" style="width:100%">
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
        <tbody><!-- diisi via JS --></tbody>
      </table>
    </div>
  </div>

  <script>
    // Util waktu
    const hhmm = s => (s||'').toString().slice(0,5);

    // DataTable handle
    let dt;

    $(function(){
      // Inisialisasi DataTable
      dt = $('#tabel-peserta').DataTable({
        pageLength: 10,
        lengthMenu: [5,10,25,50],
        ordering: false,
        language: {
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

      // Tambah data-label untuk mode mobile setelah setiap draw
      function setDataLabels(){
        const headers = [];
        $('#tabel-peserta thead th').each(function(){ headers.push($(this).text().trim()); });
        $('#tabel-peserta tbody tr').each(function(){
          $(this).children('td').each(function(i){ $(this).attr('data-label', headers[i] || ''); });
        });
      }
      setDataLabels();
      dt.on('draw', setDataLabels);

      // Toggle tombol cetak sesuai jumlah baris terfilter
      const togglePrint = () => {
        const ada = dt.rows({filter:'applied'}).count() > 0;
        $('#btn-cetak').prop('disabled', !ada);
      };
      dt.on('draw', togglePrint);

      // Pilih MK -> ambil shift
      $('#filter-mk').on('change', function(){
        const id_mk = $(this).val();

        $('#filter-shift').prop('disabled', true).html('<option value="">Pilih Shift</option>');
        clearTable();

        if(!id_mk) return;

        $.post('proses_data_praktikum.php', { aksi:'list_shift', id_mk }, function(raw){
          let res = raw;
          try { if (typeof raw === 'string') res = JSON.parse(raw); } catch(_){}

          if(res?.error){ alert(res.error); return; }

          const shifts = Array.isArray(res?.shifts) ? res.shifts.slice() : [];
          if(!shifts.length){
            $('#filter-shift').prop('disabled', true).html('<option value="">(Shift tidak tersedia)</option>');
            return;
          }

          shifts.sort((a,b)=> String(a.jam_mulai).localeCompare(String(b.jam_mulai)));

          let html = '<option value="">Pilih Shift</option>';
          shifts.forEach(s=>{
            html += `<option value="${s.id}">${(s.hari||'')} ${hhmm(s.jam_mulai)}-${hhmm(s.jam_selesai)}</option>`;
          });

          $('#filter-shift').html(html).prop('disabled', false);

          // Auto-pilih shift pertama (opsional)
          if($('#filter-shift option').length > 1){
            $('#filter-shift').prop('selectedIndex', 1).trigger('change');
          }
        })
        .fail(x => alert('Gagal memuat shift: ' + (x.responseText || x.statusText)));
      });

      // Pilih Shift -> ambil peserta
      $('#filter-shift').on('change', function(){
        const id_mk     = $('#filter-mk').val();
        const id_jadwal = $(this).val();

        clearTable();
        if(!id_mk || !id_jadwal) return;

        $.post('proses_data_praktikum.php', { aksi:'list_peserta', id_mk, id_jadwal }, function(raw){
          let res = raw;
          try { if (typeof raw === 'string') res = JSON.parse(raw); } catch(_){}

          if(res?.error){ alert(res.error); return; }

          // hint (judul kecil)
          const mk    = res?.meta?.mk    || '';
          const shift = res?.meta?.shift || '';
          const sub   = [mk, shift].filter(Boolean).join(' • ');
          if(sub){ $('#hint').text(sub).show(); } else { $('#hint').hide().text(''); }

          // render rows
          const rows  = Array.isArray(res?.rows) ? res.rows : [];
          const tbody = rows.map(r => `
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
            </tr>
          `).join('');

          $('#tabel-peserta tbody').html(tbody);

          // sinkron ke DataTable
          dt.clear();
          dt.rows.add($('#tabel-peserta tbody tr'));
          dt.draw();
        })
        .fail(x => alert('Gagal memuat data: ' + (x.responseText || x.statusText)));
      });

      // Cetak PDF (seragam gaya)
      $('#btn-cetak').on('click', function(){
        const ns    = window.jspdf && window.jspdf.jsPDF ? window.jspdf : null;
        const jsPDF = ns ? ns.jsPDF : (window.jsPDF || null);
        if(!jsPDF){ alert('jsPDF belum termuat.'); return; }

        const doc = new jsPDF({ orientation:'landscape', unit:'mm', format:'a4' });
        const cx  = doc.internal.pageSize.getWidth() / 2;

        // judul + subjudul
        doc.setFontSize(14);
        doc.text('Daftar Peserta Praktikum', cx, 12, { align:'center' });

        const sub = $('#hint').is(':visible') ? $('#hint').text() : '';
        if(sub){ doc.setFontSize(10); doc.text(sub, cx, 18, { align:'center' }); }

        // header
        const headers = [];
        $('#tabel-peserta thead th').each(function(){ headers.push($(this).text()); });

        // body
        const body = [];
        $('#tabel-peserta tbody tr').each(function(){
          const row = [];
          $(this).find('td').each(function(){ row.push($(this).text()); });
          body.push(row);
        });

        if(typeof doc.autoTable !== 'function'){ alert('autoTable belum termuat.'); return; }

        doc.autoTable({
          head: [headers],
          body,
          startY: 24,
          theme: 'grid',
          headStyles: { fillColor:[139,201,255], textColor:20 },
          styles: { fontSize:9, cellPadding:2, cellWidth:'wrap' },
          margin: { top:24, left:10, right:10 }
        });

        doc.save('Data_Peserta_Praktikum.pdf');
      });

      function clearTable(){
        $('#hint').hide().text('');
        $('#tabel-peserta tbody').empty();
        dt.clear().draw();
        $('#btn-cetak').prop('disabled', true);
      }
    });
  </script>
</body>
</html>
