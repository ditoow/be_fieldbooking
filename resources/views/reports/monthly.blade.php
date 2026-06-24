<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan MyUGO - {{ $monthName }} {{ $year }}</title>
<style>
  @page { margin: 0; padding: 0; }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body { padding: 35mm 30mm; }

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
    --radius: 10px;
    --radius-pill: 999px;
  }

  body {
    font-family: 'Poppins', 'DejaVu Sans', sans-serif;
    color: var(--ink);
    font-size: 11px;
    line-height: 1.7;
    background: var(--paper);
    padding: 40mm 35mm;
  }

  h1, h2, h3 { font-weight: 600; }

  .header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 32px;
    padding-bottom: 20px;
    border-bottom: 2px solid var(--ink);
  }
  .brand h1 { font-size: 26px; font-weight: 700; }
  .brand h1 span { color: var(--gold); }
  .brand .tagline { font-size: 9px; color: var(--ink-soft); margin-top: 4px; }
  .header .meta { text-align: right; font-size: 9px; color: var(--ink-soft); }
  .header .meta p { margin: 3px 0; }
  .header .meta .period { font-weight: 700; color: var(--ink); font-size: 12px; }

  .intro {
    background: #fff;
    border: 1px solid var(--line);
    border-left: 3px solid var(--gold);
    border-radius: var(--radius);
    padding: 20px 24px;
    margin-bottom: 32px;
    font-size: 11px;
    color: var(--ink-soft);
  }
  .intro strong { color: var(--ink); }

  .section { margin-bottom: 32px; }
  .section h2 {
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.5px;
    color: var(--ink);
    margin-bottom: 16px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--line);
    text-transform: uppercase;
  }

  .stats {
    display: flex;
    gap: 20px;
    margin-bottom: 32px;
  }
  .stat-card {
    flex: 1;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    padding: 22px 20px;
  }
  .stat-card .label {
    font-size: 8px;
    text-transform: uppercase;
    color: var(--ink-soft);
    letter-spacing: 0.8px;
    font-weight: 500;
  }
  .stat-card .value {
    font-size: 22px;
    font-weight: 700;
    color: var(--ink);
    margin-top: 6px;
  }

  .util-grid { width: 100%; }
  .util-row {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 14px;
  }
  .util-row .name {
    width: 150px;
    font-weight: 600;
    font-size: 10px;
    flex-shrink: 0;
  }
  .util-row .bar-track {
    flex: 1;
    height: 16px;
    background: #EFEBE0;
    border-radius: var(--radius-pill);
    overflow: hidden;
  }
  .util-row .bar-fill {
    height: 100%;
    border-radius: var(--radius-pill);
    background: #2D6A4F;
  }
  .util-row .rate {
    width: 40px;
    text-align: right;
    font-weight: 700;
    font-size: 11px;
    flex-shrink: 0;
  }

  .status-row { display: flex; gap: 18px; margin-bottom: 22px; }
  .status-chip {
    flex: 1;
    text-align: center;
    padding: 14px 10px;
    border-radius: var(--radius);
    font-size: 10px;
  }
  .status-chip .n { font-size: 18px; font-weight: 700; display: block; margin-bottom: 4px; }
  .status-chip.success { background: var(--green-soft); color: var(--green-text); }
  .status-chip.warning { background: var(--amber-soft); color: var(--amber-text); }
  .status-chip.danger  { background: var(--red-soft); color: var(--red-text); }

  table { width: 100%; border-collapse: collapse; margin-top: 6px; }
  th {
    background: var(--ink);
    color: #fff;
    padding: 12px 16px;
    text-align: left;
    font-size: 8px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    font-weight: 500;
  }
  td {
    padding: 11px 16px;
    border-bottom: 1px solid var(--line);
    font-size: 10px;
  }
  tr:nth-child(even) td { background: #F6F3EC; }
  .badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: var(--radius-pill);
    font-size: 8px;
    font-weight: 600;
  }
  .badge-success { background: var(--green-soft); color: var(--green-text); }
  .badge-warning { background: var(--amber-soft); color: var(--amber-text); }
  .badge-danger { background: var(--red-soft); color: var(--red-text); }

  .note-box {
    border: 1px dashed var(--line);
    border-radius: var(--radius);
    padding: 18px 20px;
    font-size: 10px;
    color: var(--ink-soft);
    min-height: 50px;
  }
  .note-box .note-label {
    font-size: 8px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--gold);
    font-weight: 700;
    margin-bottom: 8px;
  }

  .footer {
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid var(--line);
    display: flex;
    justify-content: space-between;
    font-size: 9px;
    color: var(--ink-soft);
  }
  .footer p { margin: 2px 0; }
  .footer .ttd { text-align: right; }
  .footer .ttd .sign-line {
    margin-top: 44px;
    border-top: 1px solid var(--ink-soft);
    padding-top: 5px;
    width: 180px;
    display: inline-block;
  }
  .footer .ttd p { font-weight: 600; color: var(--ink); }
</style>
</head>
<body>

<div class="header">
  <div class="brand">
    <h1>My<span>UGO</span></h1>
    <p class="tagline">Sistem Manajemen Fasilitas - Universitas Dian Nuswantoro</p>
  </div>
  <div class="meta">
    <p class="period">{{ $monthName }} {{ $year }}</p>
    <p>Laporan Bulanan &middot; RPT-{{ $year }}{{ $monthPadded }}</p>
    <p>Dicetak {{ now()->format('d F Y, H:i') }} WIB</p>
  </div>
</div>

<div class="intro">
  Sepanjang <strong>{{ $monthName }} {{ $year }}</strong>, tercatat
  <strong>{{ $summary['total_bookings'] }} booking</strong> dari
  <strong>{{ $summary['active_users'] }} pengguna aktif</strong>,
  dengan total pendapatan <strong>Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</strong>.
</div>

<div class="stats">
  <div class="stat-card">
    <div class="label">Total Pendapatan</div>
    <div class="value">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</div>
  </div>
  <div class="stat-card">
    <div class="label">Total Booking</div>
    <div class="value">{{ $summary['total_bookings'] }}</div>
  </div>
  <div class="stat-card">
    <div class="label">Pengguna Aktif</div>
    <div class="value">{{ $summary['active_users'] }}</div>
  </div>
</div>

@if(!empty($utilization))
<div class="section">
  <h2>Utilisasi Fasilitas</h2>
  <div class="util-grid">
    @foreach($utilization as $util)
    <div class="util-row">
      <span class="name">{{ $util['field_name'] }}</span>
      <div class="bar-track">
        <div class="bar-fill" style="width:{{ min($util['rate'], 100) }}%"></div>
      </div>
      <span class="rate">{{ $util['rate'] }}%</span>
    </div>
    @endforeach
  </div>
</div>
@endif

<div class="section">
  <h2>Ringkasan Status Transaksi</h2>
  <div class="status-row">
    <div class="status-chip success"><span class="n">{{ $summary['total_bookings'] }}</span> Total Booking</div>
    <div class="status-chip warning"><span class="n">{{ $summary['active_users'] }}</span> Pengguna Aktif</div>
    <div class="status-chip danger"><span class="n">0</span> Gagal</div>
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
      <tr><td colspan="5" style="text-align:center;color:#999;padding:24px;">Belum ada transaksi tercatat bulan ini.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="section">
  <h2>Catatan Admin</h2>
</div>

<div class="footer">
  <div>
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