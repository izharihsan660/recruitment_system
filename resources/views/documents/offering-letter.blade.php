<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; line-height: 1.6; }
        .header { border-bottom: 2px solid #111827; padding-bottom: 12px; margin-bottom: 24px; }
        .title { font-size: 18px; font-weight: bold; text-align: center; margin: 24px 0; }
        .grid { width: 100%; border-collapse: collapse; margin: 12px 0; }
        .grid td { padding: 6px 4px; vertical-align: top; }
        .label { width: 180px; font-weight: bold; }
        .signatures { width: 100%; margin-top: 48px; }
        .signatures td { width: 50%; text-align: center; padding-top: 48px; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; border-top: 1px solid #d1d5db; padding-top: 8px; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <strong>{{ $offering->entity->name }}</strong><br>
        Offering Letter
    </div>

    <p>{{ now()->translatedFormat('d F Y') }}</p>
    <div class="title">SURAT PENAWARAN KERJA</div>

    <p>Kepada Yth. {{ $offering->application->candidate->name }},</p>
    <p>Kami menawarkan posisi berikut sesuai kebutuhan perusahaan:</p>

    <table class="grid">
        <tr><td class="label">Nama Kandidat</td><td>{{ $offering->application->candidate->name }}</td></tr>
        <tr><td class="label">Posisi</td><td>{{ $offering->position_name }}</td></tr>
        <tr><td class="label">Department</td><td>{{ $offering->department }}</td></tr>
        <tr><td class="label">Lokasi Kerja</td><td>{{ $offering->work_location }}</td></tr>
        <tr><td class="label">Tanggal Mulai</td><td>{{ $offering->start_date?->format('d/m/Y') }}</td></tr>
        <tr><td class="label">Durasi Kontrak</td><td>{{ $offering->contract_duration ?: '-' }}</td></tr>
        <tr><td class="label">Gaji Gross</td><td>Rp {{ number_format($offering->salary_gross ?? 0, 0, ',', '.') }}</td></tr>
        <tr><td class="label">Gaji Nett</td><td>Rp {{ number_format($offering->salary_nett ?? 0, 0, ',', '.') }}</td></tr>
    </table>

    <h3>Tunjangan</h3>
    <table class="grid">
        @forelse (($offering->allowances ?? []) as $name => $amount)
            <tr><td class="label">{{ \Illuminate\Support\Str::headline($name) }}</td><td>Rp {{ number_format((int) $amount, 0, ',', '.') }}</td></tr>
        @empty
            <tr><td>Tidak ada tunjangan tambahan.</td></tr>
        @endforelse
    </table>

    <p>Penawaran ini berlaku sampai {{ $offering->expiry_date?->format('d/m/Y') }}.</p>

    <table class="signatures">
        <tr>
            <td>HR Signer<br><br><br>{{ $offering->hrSigner->name }}</td>
            <td>Kandidat<br><br><br>{{ $offering->application->candidate->name }}</td>
        </tr>
    </table>

    <div class="footer">{{ $offering->entity->name }} — Dokumen private sistem rekrutmen.</div>
</body>
</html>
