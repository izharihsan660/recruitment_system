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
        .article { margin-top: 18px; }
        .signatures { width: 100%; margin-top: 48px; }
        .signatures td { width: 50%; text-align: center; padding-top: 48px; }
    </style>
</head>
<body>
    <div class="header"><strong>{{ $pkwt->entity->name }}</strong><br>Kontrak Kerja</div>
    <div class="title">PERJANJIAN KERJA WAKTU TERTENTU</div>

    <p>Pihak pertama adalah {{ $pkwt->entity->name }} yang diwakili oleh {{ $pkwt->companySigner->name }}.</p>
    <p>Pihak kedua adalah {{ $pkwt->application->candidate->name }}.</p>

    <table class="grid">
        <tr><td class="label">Posisi</td><td>{{ $pkwt->position_name }}</td></tr>
        <tr><td class="label">Department</td><td>{{ $pkwt->department }}</td></tr>
        <tr><td class="label">Lokasi Kerja</td><td>{{ $pkwt->work_location }}</td></tr>
        <tr><td class="label">Periode</td><td>{{ $pkwt->start_date?->format('d/m/Y') }} - {{ $pkwt->end_date?->format('d/m/Y') ?: 'Tidak ditentukan' }}</td></tr>
        <tr><td class="label">Durasi</td><td>{{ $pkwt->contract_duration ?: '-' }}</td></tr>
        <tr><td class="label">Gaji Gross</td><td>Rp {{ number_format($pkwt->salary_gross ?? 0, 0, ',', '.') }}</td></tr>
        <tr><td class="label">Gaji Nett</td><td>Rp {{ number_format($pkwt->salary_nett ?? 0, 0, ',', '.') }}</td></tr>
    </table>

    <div class="article"><strong>Pasal 1 - Posisi dan Lokasi</strong><br>Pihak kedua bekerja pada posisi dan lokasi sebagaimana tercantum di atas.</div>
    <div class="article"><strong>Pasal 2 - Periode Kontrak</strong><br>Kontrak berlaku sesuai periode yang tercantum dan dapat diperpanjang sesuai kebijakan perusahaan.</div>
    <div class="article"><strong>Pasal 3 - Kompensasi</strong><br>Kompensasi diberikan sesuai ketentuan payroll perusahaan.</div>

    <table class="signatures">
        <tr>
            <td>Kandidat<br><br><br>{{ $pkwt->application->candidate->name }}</td>
            <td>Company Signer<br><br><br>{{ $pkwt->companySigner->name }}</td>
        </tr>
    </table>
</body>
</html>
