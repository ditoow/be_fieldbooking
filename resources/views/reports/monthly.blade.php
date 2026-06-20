<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan MyUGO - {{ $monthName }} {{ $year }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,600;0,700;1,500&family=Public+Sans:wght@400;500;600;700&display=swap');

  @page { margin: 22mm 18mm; }
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
    --radius: 10px;
    --radius-pill: 999px;
  }

  body {
    font-family: 'Public Sans', 'Segoe UI', Arial, sans-serif;
    color: var(--ink);
    font-size: 11.5px;
    line-height: 1.65;
    background: var(--paper);
  }

  h1, h2, .brand, .stat-card .value { font-family: 'EB Garamond', Georgia, 'Times New Roman', serif; }

  .header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 28px; padding-bottom: 20px; border-bottom: 2px solid var(--ink); }
  .brand h1 { font-size: 26px; font-weight: 700; }
  .brand h1 span { color: var(--gold); }
  .brand .tagline { font-size: 10px; color: var(--ink-soft); margin-top: 4px; }
  .header .meta { text-align: right; font-size: 10px; color: var(--ink-soft); }
  .header .meta p { margin: 3px 0; }
  .header .meta .period { font-weight: 700; color: var(--ink); font-size: 12px; }

  .intro { background: #fff; border: 1px solid var(--line); border-left: 3px solid var(--gold); border-radius: var(--radius); padding: 18px 22px; margin-bottom: 28px; font-size: 11.5px; color: var(--ink-soft); }
  .intro strong { color: var(--ink); }

  .section { margin-bottom: 28px; }
  .section h2 { font-size: 14px; font-weight: 600; letter-spacing: 0.2px; color: var(--ink); margin-bottom: 14px; padding-bottom: 8px; border-bottom: 1px solid var(--line); }

  .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 28px; }
  .stat-card { background: #fff; border: 1px solid var(--line); border-radius: var(--radius); padding: 18px 16px; }
  .stat-card .label { font-size: 9px; text-transform: uppercase; color: var(--ink-soft); letter-spacing: 0.6px; }
  .stat-card .value { font-size: 22px; font-weight: 700; color: var(--ink); margin-top: 6px; }

  .util-highlight { display: flex; gap: 14px; margin-bottom: 16px; }
  .util-highlight .pill { flex: 1; background: var(--green-soft); border-radius: var(--radius); padding: 12px 14px; font-size: 10px; }
  .util-highlight .pill.watch { background: var(--amber-soft); }
  .util-highlight .pill .pill-label { color: var(--ink-soft); font-size: 9px; text-transform: uppercase; letter-spacing: 0.4px; }
  .util-highlight .pill .pill-value { font-weight: 700; color: var(--ink); font-size: 11.5px; margin-top: 4px; }

  .util-grid { display: grid; gap: 12px; }
  .util-item { display: flex; align-items: center; gap: 12px; }
  .util-item .name { width: 140px; font-weight: 600; font-size: 10.5px; }
  .util-item .bar-wrap { flex: 1; height: 14px; background: #EFEBE0; border-radius: var(--radius-pill); overflow: hidden; }
  .util-item .bar { height: 100%; border-radius: var(--radius-pill); background: linear-gradient(90deg, var(--green-text), var(--gold-light)); }
  .util-item .rate { width: 38px; text-align: right; font-weight: 700; font-size: 11px; }

  .status-row { display: flex; gap: 14px; margin-bottom: 18px; }
  .status-chip { flex: 1; text-align: center; padding: 12px 10px; border-radius: var(--radius); font-size: 10px; }
  .status-chip .n { font-size: 17px; font-weight: 700; display: block; margin-bottom: 2px; }
  .status-chip.success { background: var(--green-soft); color: var(--green-text); }
  .status-chip.warning { background: var(--amber-soft); color: var(--amber-text); }
  .status-chip.danger  { background: var(--red-soft); color: var(--red-text); }

  table { width: 100%; border-collapse: collapse; margin-top: 4px; }
  th { background: var(--ink); color: #fff; padding: 11px 14px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
  td { padding: 10px 14px; border-bottom: 1px solid var(--line); font-size: 10.5px; }
  tr:nth-child(even) td { background: #F6F3EC; }
  .badge { display: inline-block; padding: 3px 10px; border-radius: var(--radius-pill); font-size: 9px; font-weight: 600; }
  .badge-success { background: var(--green-soft); color: var(--green-text); }
  .badge-warning { background: var(--amber-soft); color: var(--amber-text); }
  .badge-danger { background: var(--red-soft); color: var(--red-text); }

  .note-box { border: 1px dashed var(--line); border-radius: var(--radius); padding: 16px 18px; font-size: 10.5px; color: var(--ink-soft); min-height: 48px; }
  .note-box .note-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--gold); font-weight: 700; margin-bottom: 8px; }

  .footer { margin-top: 36px; padding-top: 18px; border-top: 1px solid var(--line); display: flex; justify-content: space-between; font-size: 9px; color: var(--ink-soft); }
  .footer p { margin: 2px 0; }
  .footer .ttd { text-align: right; }
  .footer .ttd .sign-line { margin-top: 42px; border-top: 1px solid var(--ink-soft); padding-top: 5px; width: 170px; display: inline-block; }
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

  <div class="util-highlight">

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
  <div class="note-box">
    <div class="note-label">Tulis tangan / opsional</div>
    {{ $adminNote ?? '' }}
  </div>
</div>

<div class="footer">
  <div>
    <p>Dokumen internal - tidak untuk disebarluaskan.</p>
    <p>Disusun oleh tim Operasional MyUGO, Universitas Dian Nuswantoro.</p>
  </div>
  <div class="ttd">
    <p>Semarang, {{ now()->format('d F Y') }}</p>
    <div class="sign-line"></div>
    <p>Administrator</p>
  </div>
</div>

<script>window.print();</script>
</body>
</html>
