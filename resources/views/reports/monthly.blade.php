<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan MyUGO - {{ $monthName }} {{ $year }}</title>
<style>
  /* Fraunces untuk identitas (serif, ada karakter, kerasa "tulisan manusia" bukan dashboard generik)
     Public Sans untuk isi (rapi dibaca, dipakai juga di laporan-laporan resmi pemerintah).
     Kalau renderer kamu pakai dompdf (bukan Browsershot/Chrome), @import Google Fonts ini
     mungkin tidak ke-load — tinggal hapus baris @import, fallback serif/sans-serif di bawah
     sudah aman dipakai. */
  @import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Public+Sans:wght@400;500;600;700&display=swap');

  @page { margin: 20mm 15mm; }
  * { margin: 0; padding: 0; box-sizing: border-box; }

  :root {
    --ink: #1C2B1E;
    --ink-soft: #4A5A4C;
    --gold: #B8865A;
    --gold-light: #D4A574;
    --paper: #FBF9F4;
    --line: #E4DFD3;
    --green-soft: #EAF1EC;
    --green-text: #2D6A4F;
    --amber-soft: #FBF2E2;
    --amber-text: #8A5A1C;
    --red-soft: #FBEAEA;
    --red-text: #9B3030;
  }

  body {
    font-family: 'Public Sans', 'Segoe UI', Arial, sans-serif;
    color: var(--ink);
    font-size: 11px;
    line-height: 1.6;
    background: var(--paper);
  }

  h1, h2, .brand, .stat-card .value { font-family: 'Fraunces', Georgia, 'Times New Roman', serif; }

  /* ---------- HEADER ---------- */
  .header {
    display: flex; justify-content: space-between; align-items: flex-end;
    margin-bottom: 22px; padding-bottom: 16px; border-bottom: 2px solid var(--ink);
  }
  .brand h1 { font-size: 24px; font-weight: 700; color: var(--ink); }
  .brand h1 span { color: var(--gold); }
  .brand .tagline { font-size: 10px; color: var(--ink-soft); margin-top: 3px; }
  .header .meta { text-align: right; font-size: 10px; color: var(--ink-soft); }
  .header .meta p { margin: 2px 0; }
  .header .meta .period { font-weight: 700; color: var(--ink); font-size: 11px; }

  /* ---------- CATATAN PEMBUKA (narasi, bukan cuma angka) ---------- */
  .intro {
    background: #fff; border: 1px solid var(--line); border-left: 3px solid var(--gold);
    border-radius: 4px; padding: 14px 16px; margin-bottom: 22px; font-size: 11px; color: var(--ink-soft);
  }
  .intro strong { color: var(--ink); }

  .section { margin-bottom: 22px; }
  .section h2 {
    font-size: 13px; font-weight: 600; letter-spacing: 0.3px; color: var(--ink);
    margin-bottom: 10px; padding-bottom: 6px; border-bottom: 1px solid var(--line);
    display: flex; align-items: baseline; gap: 6px;
  }
  .section h2 .accent { color: var(--gold); }

  /* ---------- STAT CARDS ---------- */
  .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 22px; }
  .stat-card {
    background: #fff; border: 1px solid var(--line); border-radius: 6px; padding: 14px;
  }
  .stat-card .label { font-size: 9px; text-transform: uppercase; color: var(--ink-soft); letter-spacing: 0.6px; }
  .stat-card .value { font-size: 21px; font-weight: 700; color: var(--ink); margin-top: 4px; }
  .stat-card .trend { font-size: 9px; margin-top: 5px; font-weight: 600; }
  .trend.up { color: var(--green-text); }
  .trend.down { color: var(--red-text); }
  .trend.flat { color: var(--ink-soft); }

  /* ---------- UTILISASI ---------- */
  .util-highlight {
    display: flex; gap: 10px; margin-bottom: 12px;
  }
  .util-highlight .pill {
    flex: 1; background: var(--green-soft); border-radius: 6px; padding: 8px 12px; font-size: 10px;
  }
  .util-highlight .pill.watch { background: var(--amber-soft); }
  .util-highlight .pill .pill-label { color: var(--ink-soft); font-size: 9px; text-transform: uppercase; letter-spacing: 0.4px; }
  .util-highlight .pill .pill-value { font-weight: 700; color: var(--ink); font-size: 11px; margin-top: 2px; }

  .util-grid { display: grid; gap: 9px; }
  .util-item { display: flex; align-items: center; gap: 10px; }
  .util-item .name { width: 140px; font-weight: 600; font-size: 10px; }
  .util-item .bar-wrap { flex: 1; height: 14px; background: #EFEBE0; border-radius: 7px; overflow: hidden; }
  .util-item .bar { height: 100%; border-radius: 7px; background: linear-gradient(90deg, var(--green-text), var(--gold-light)); }
  .util-item .rate { width: 38px; text-align: right; font-weight: 700; font-size: 11px; }

  /* ---------- RINGKASAN STATUS TRANSAKSI ---------- */
  .status-row { display: flex; gap: 10px; margin-bottom: 14px; }
  .status-chip { flex: 1; text-align: center; padding: 8px; border-radius: 6px; font-size: 10px; }
  .status-chip .n { font-size: 16px; font-weight: 700; display: block; }
  .status-chip.success { background: var(--green-soft); color: var(--green-text); }
  .status-chip.warning { background: var(--amber-soft); color: var(--amber-text); }
  .status-chip.danger  { background: var(--red-soft); color: var(--red-text); }

  /* ---------- TABEL ---------- */
  table { width: 100%; border-collapse: collapse; margin-top: 4px; }
  th { background: var(--ink); color: #fff; padding: 8px 10px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
  td { padding: 7px 10px; border-bottom: 1px solid var(--line); font-size: 10px; }
  tr:nth-child(even) td { background: #F6F3EC; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 600; }
  .badge-success { background: var(--green-soft); color: var(--green-text); }
  .badge-warning { background: var(--amber-soft); color: var(--amber-text); }
  .badge-danger { background: var(--red-soft); color: var(--red-text); }

  /* ---------- CATATAN ADMIN (ruang manual, bukan auto-generated) ---------- */
  .note-box {
    border: 1px dashed var(--line); border-radius: 6px; padding: 12px 14px; font-size: 10px; color: var(--ink-soft);
    min-height: 40px;
  }
  .note-box .note-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--gold); font-weight: 700; margin-bottom: 6px; }

  /* ---------- FOOTER ---------- */
  .footer { margin-top: 30px; padding-top: 14px; border-top: 1px solid var(--line); display: flex; justify-content: space-between; font-size: 9px; color: var(--ink-soft); }
  .footer .ttd { text-align: right; }
  .footer .ttd .sign-line { margin-top: 36px; border-top: 1px solid var(--ink-soft); padding-top: 4px; width: 160px; }
  .footer .ttd p { font-weight: 600; color: var(--ink); }
</style>
</head>
<body>

@php
  // --- Hitung ringkasan status transaksi ---
  $statusCount = ['BERHASIL' => 0, 'MENUNGGU' => 0, 'GAGAL' => 0];
  foreach ($transactions as $trx) {
      $statusCount[$trx['status']] = ($statusCount[$trx['status']] ?? 0) + 1;
  }
  $totalTrx = count($transactions);
  $successRate = $totalTrx > 0 ? round(($statusCount['BERHASIL'] ?? 0) / $totalTrx * 100) : null;

  // --- Cari fasilitas paling laku & paling sepi dari data utilisasi ---
  $topFacility = null;
  $lowFacility = null;
  if (!empty($utilization)) {
      $sortedUtil = collect($utilization)->sortByDesc('rate')->values();
      $topFacility = $sortedUtil->first();
      $lowFacility = $sortedUtil->count() > 1 ? $sortedUtil->last() : null;
  }
@endphp

<div class="header">
  <div class="brand">
    <h1>My<span>UGO</span></h1>
    <p class="tagline">Sistem Manajemen Fasilitas &mdash; Universitas Dian Nuswantoro</p>
  </div>
  <div class="meta">
    <p class="period">{{ $monthName }} {{ $year }}</p>
    <p>Laporan Bulanan &middot; RPT-{{ $year }}{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}</p>
    <p>Dicetak {{ now()->format('d F Y, H:i') }} WIB</p>
  </div>
</div>

<div class="intro">
  Sepanjang <strong>{{ $monthName }} {{ $year }}</strong>, tercatat
  <strong>{{ $summary['total_bookings'] }} booking</strong> dari
  <strong>{{ $summary['active_users'] }} pengguna aktif</strong>,
  dengan total pendapatan <strong>Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</strong>.
  @if($topFacility)
    Fasilitas paling diminati bulan ini adalah <strong>{{ $topFacility['field_name'] }}</strong>
    dengan tingkat keterpakaian {{ $topFacility['rate'] }}%.
  @endif
  @if($successRate !== null)
    Dari seluruh transaksi yang masuk, {{ $successRate }}% berhasil diselesaikan tanpa kendala.
  @endif
</div>

<div class="stats">
  <div class="stat-card">
    <div class="label">Total Pendapatan</div>
    <div class="value">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</div>
    @isset($summary['revenue_growth'])
      <div class="trend {{ $summary['revenue_growth'] > 0 ? 'up' : ($summary['revenue_growth'] < 0 ? 'down' : 'flat') }}">
        {{ $summary['revenue_growth'] > 0 ? '▲' : ($summary['revenue_growth'] < 0 ? '▼' : '—') }}
        {{ abs($summary['revenue_growth']) }}% dari bulan lalu
      </div>
    @endisset
  </div>
  <div class="stat-card">
    <div class="label">Total Booking</div>
    <div class="value">{{ $summary['total_bookings'] }}</div>
    @isset($summary['bookings_growth'])
      <div class="trend {{ $summary['bookings_growth'] > 0 ? 'up' : ($summary['bookings_growth'] < 0 ? 'down' : 'flat') }}">
        {{ $summary['bookings_growth'] > 0 ? '▲' : ($summary['bookings_growth'] < 0 ? '▼' : '—') }}
        {{ abs($summary['bookings_growth']) }}% dari bulan lalu
      </div>
    @endisset
  </div>
  <div class="stat-card">
    <div class="label">Pengguna Aktif</div>
    <div class="value">{{ $summary['active_users'] }}</div>
    @isset($summary['users_growth'])
      <div class="trend {{ $summary['users_growth'] > 0 ? 'up' : ($summary['users_growth'] < 0 ? 'down' : 'flat') }}">
        {{ $summary['users_growth'] > 0 ? '▲' : ($summary['users_growth'] < 0 ? '▼' : '—') }}
        {{ abs($summary['users_growth']) }}% dari bulan lalu
      </div>
    @endisset
  </div>
</div>

@if(!empty($utilization))
<div class="section">
  <h2>Utilisasi Fasilitas</h2>

  <div class="util-highlight">
    @if($topFacility)
    <div class="pill">
      <div class="pill-label">Paling Laku</div>
      <div class="pill-value">{{ $topFacility['field_name'] }} &middot; {{ $topFacility['rate'] }}%</div>
    </div>
    @endif
    @if($lowFacility)
    <div class="pill watch">
      <div class="pill-label">Perlu Perhatian / Promosi</div>
      <div class="pill-value">{{ $lowFacility['field_name'] }} &middot; {{ $lowFacility['rate'] }}%</div>
    </div>
    @endif
  </div>

  <div class="util-grid">
    @foreach($utilization as $util)
    <div class="util-item">
      <span class="name">{{ $util['field_name'] }}</span>
      <div class="bar-wrap"><div class="bar" style="width:{{ $util['rate'] }}%"></div></div>
      <span class="rate">{{ $util['rate'] }}%</span>
    </div>
    @endforeach
  </div>
</div>
@endif

<div class="section">
  <h2>Ringkasan Status Transaksi</h2>
  <div class="status-row">
    <div class="status-chip success">
      <span class="n">{{ $statusCount['BERHASIL'] ?? 0 }}</span> Berhasil
    </div>
    <div class="status-chip warning">
      <span class="n">{{ $statusCount['MENUNGGU'] ?? 0 }}</span> Menunggu
    </div>
    <div class="status-chip danger">
      <span class="n">{{ $statusCount['GAGAL'] ?? 0 }}</span> Gagal
    </div>
  </div>
</div>

<div class="section">
  <h2>Transaksi Terbaru</h2>
  <table>
    <thead>
      <tr><th>ID</th><th>Pengguna</th><th>Layanan</th><th>Tanggal</th><th>Status</th></tr>
    </thead>
    <tbody>
      @forelse($transactions as $trx)
      <tr>
        <td style="font-family:monospace">{{ $trx['id'] }}</td>
        <td>{{ $trx['user_detail'] }}</td>
        <td>{{ $trx['service'] }}</td>
        <td>{{ $trx['date'] }}</td>
        <td><span class="badge badge-{{ $trx['status'] === 'BERHASIL' ? 'success' : ($trx['status'] === 'MENUNGGU' ? 'warning' : 'danger') }}">{{ $trx['status'] }}</span></td>
      </tr>
      @empty
      <tr><td colspan="5" style="text-align:center;color:#999;padding:20px;">Belum ada transaksi tercatat bulan ini.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="section">
  <h2>Catatan Admin</h2>
  <div class="note-box">
    <div class="note-label">Tulis tangan / opsional</div>
    {{ $adminNote ?? '________________________________________________________________' }}
  </div>
</div>

<div class="footer">
  <div>
    <p>Dokumen internal &mdash; tidak untuk disebarluaskan.</p>
    <p>Disusun oleh tim Operasional MyUGO, Universitas Dian Nuswantoro.</p>
  </div>
  <div class="ttd">
    <p>Semarang, {{ now()->format('d F Y') }}</p>
    <div class="sign-line"></div>
    <p>Administrator</p>
  </div>
</div>

</body>
</html>