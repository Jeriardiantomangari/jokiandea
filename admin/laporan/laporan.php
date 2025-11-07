<?php
session_start();
include '../../koneksi/koneksi.php';
include '../../koneksi/sidebar.php';

// Cek role admin (DB pakai 'Admin')
if (!isset($_SESSION['role']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
  header("Location: ../login.php");
  exit;
}

// helper aman untuk echo
function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// Ambil semester aktif (opsional)
$rsSem    = mysqli_query($conn, "SELECT id, nama_semester, tahun_ajaran FROM semester WHERE status='Aktif' LIMIT 1");
$semAktif = mysqli_fetch_assoc($rsSem);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Data Praktikum</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- DataTables + jQuery -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

  <!-- FontAwesome (ikon cetak) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- jsPDF + autoTable -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

  <style>
    /* ====== Sederhana & rapih ====== */
    body { font-family: Arial, sans-serif; background:#f7f7f7; }

    .bungkus {
      margin-left:250px;
      margin-top:60px;
      padding:24px;
      min-height:calc(100vh - 60px);
    }

    h2 { margin:0 0 14px; color:#333; }

    .bilah {
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      align-items:center;
      margin-bottom:12px;
    }

    select,
    button {
      padding:8px 10px;
      border:1px solid #d0d0d0;
      border-radius:6px;
      background:#fff;
    }

    button { cursor:pointer; }

    .tombol-cetak {
      background:#28a745;
      color:#fff;
      border:none;
    }

    .tombol-cetak:disabled {
      background:#99d1a9;
      cursor:not-allowed;
    }

    .info-semester {
      margin-bottom:10px;
      color:#333;
    }

    .display { width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.1); table-layout:fixed; }
.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select { padding:6px 10px; border-radius:5px; border:1px solid #ccc; font-size:14px; margin-bottom:5px; }
    .dataTable thead th {  background:#00AEEF; color:#333; }

    

    @media (max-width: 768px){
      .bungkus { margin-left:0; padding:16px; }
      .bilah { flex-direction:column; align-items:stretch; }
      select, button { width:100%; }
    }
  </style>
</head>
<body>
  <div class="bungkus">
    <h2>Data Praktikum</h2>

    <?php if($semAktif): ?>
      <div class="info-semester">
        Semester Aktif:
        <b><?= e($semAktif['nama_semester']) ?></b><b>/</b><b><?= e($semAktif['tahun_ajaran']) ?></b>
      </div>
    <?php endif; ?>

    <div class="bilah">
      <!-- MK -->
      <select id="filter-mk">
        <option value="">Pilih Mata Kuliah</option>
        <?php
          $mk = mysqli_query($conn, "SELECT id, nama_mk FROM matakuliah_praktikum ORDER BY nama_mk ASC");
          while($r = mysqli_fetch_assoc($mk)){
            echo "<option value='".(int)$r['id']."'>".e($r['nama_mk'])."</option>";
          }
        ?>
      </select>

      <!-- Shift/Jadwal (diisi setelah pilih MK) -->
      <select id="filter-shift" disabled>
        <option value="">Pilih Shift</option>
      </select>

      <!-- Cetak -->
      <button id="btn-cetak" class="tombol-cetak" disabled>
        <i class="fa-solid fa-print"></i> Cetak PDF
      </button>
    </div>

    <!-- hint kecil (MK & shift terpilih) -->
    <div id="hint" style="display:none; margin:0 0 10px; color:#114d7a;"></div>

    <div class="kotak-tabel">
      <table id="tabel-peserta" class="display" style="width:100%">
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
    /* ===== Util ===== */
    const hhmm = s => (s||'').toString().slice(0,5);

    /* ===== DataTable ===== */
    let dt;

    $(function(){
      dt = $('#tabel-peserta').DataTable({
        pageLength: 10,
        lengthMenu: [5,10,25,50],
        language: {
          emptyTable:   "Tidak ada data tersedia",
          info:         "Menampilkan _START_-_END_ dari _TOTAL_ data",
          infoEmpty:    "Menampilkan 0-0 dari 0 data",
          infoFiltered: "(disaring dari _MAX_ data)",
          lengthMenu:   "Tampilkan _MENU_ data",
          search:       "Cari:",
          zeroRecords:  "Tidak ditemukan",
          paginate:     { first:"Pertama", last:"Terakhir", next:"Berikutnya", previous:"Sebelumnya" }
        }
      });

      // tombol cetak aktif kalau ada baris
      const togglePrint = () => {
        const ada = dt.rows({filter:'applied'}).count() > 0;
        $('#btn-cetak').prop('disabled', !ada);
      };
      dt.on('draw', togglePrint);

      // Pilih MK -> ambil shift
      $('#filter-mk').on('change', function(){
        const id_mk = $(this).val();

        $('#filter-shift')
          .prop('disabled', true)
          .html('<option value="">Pilih Shift</option>');

        clearTable();

        if(!id_mk){ return; }

        $.post('proses_data_praktikum.php', { aksi:'list_shift', id_mk }, function(raw){
          let res = raw;
          try {
            if (typeof raw === 'string') res = JSON.parse(raw);
          } catch(_){}

          if(res?.error){
            alert(res.error);
            return;
          }

          const shifts = Array.isArray(res?.shifts) ? res.shifts.slice() : [];
          if(!shifts.length){
            $('#filter-shift')
              .prop('disabled', true)
              .html('<option value="">(Shift tidak tersedia)</option>');
            return;
          }

          shifts.sort((a,b)=> String(a.jam_mulai).localeCompare(String(b.jam_mulai)));

          let html = '<option value="">Pilih Shift</option>';
          shifts.forEach(s=>{
            html += `<option value="${s.id}">${(s.hari||'')} ${hhmm(s.jam_mulai)}-${hhmm(s.jam_selesai)}</option>`;
          });

          $('#filter-shift').html(html).prop('disabled', false);

          // pilih otomatis shift pertama (opsional)
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

        if(!id_mk || !id_jadwal){ return; }

        $.post('proses_data_praktikum.php', { aksi:'list_peserta', id_mk, id_jadwal }, function(raw){
          let res = raw;
          try {
            if (typeof raw === 'string') res = JSON.parse(raw);
          } catch(_){}

          if(res?.error){
            alert(res.error);
            return;
          }

          // hint (judul kecil)
          const mk    = res?.meta?.mk    || '';
          const shift = res?.meta?.shift || '';
          const sub   = [mk, shift].filter(Boolean).join(' • ');
          if(sub){
            $('#hint').text(sub).show();
          } else {
            $('#hint').hide().text('');
          }

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

      // Cetak PDF
      $('#btn-cetak').on('click', function(){
        const ns    = window.jspdf && window.jspdf.jsPDF ? window.jspdf : null;
        const jsPDF = ns ? ns.jsPDF : (window.jsPDF || null);

        if(!jsPDF){
          alert('jsPDF belum termuat.');
          return;
        }

        const doc = new jsPDF({ orientation:'landscape', unit:'mm', format:'a4' });
        const cx  = doc.internal.pageSize.getWidth() / 2;

        // judul + subjudul
        doc.setFontSize(14);
        doc.text('Daftar Peserta Praktikum', cx, 14, { align:'center' });

        const sub = $('#hint').is(':visible') ? $('#hint').text() : '';
        if(sub){
          doc.setFontSize(10);
          doc.text(sub, cx, 20, { align:'center' });
        }

        // header
        const headers = [];
        $('#tabel-peserta thead th').each(function(){
          headers.push($(this).text());
        });

        // body
        const body = [];
        $('#tabel-peserta tbody tr').each(function(){
          const row = [];
          $(this).find('td').each(function(){
            row.push($(this).text());
          });
          body.push(row);
        });

        if(typeof doc.autoTable !== 'function'){
          alert('autoTable belum termuat.');
          return;
        }

        doc.autoTable({
          head: [headers],
          body: body,
          startY: 26,
          theme: 'grid',
          headStyles: { fillColor:[139,201,255], textColor:20 },
          styles: { fontSize:9, cellPadding:2 },
          margin: { top:26, left:10, right:10 }
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
