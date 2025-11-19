<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Dokumen</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full text-center">
        <div class="mb-4 flex justify-center">
             <!-- Icon Centang Hijau -->
            <svg class="w-20 h-20 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        
        <h1 class="text-2xl font-bold text-gray-800 mb-2">DOKUMEN VALID</h1>
        <p class="text-gray-600 mb-6">Dokumen ini terdaftar resmi di sistem kami.</p>
        
        <div class="text-left bg-gray-50 p-4 rounded border border-gray-200 text-sm">
            <p class="mb-2"><strong>Jenis Dokumen:</strong><br>Berita Acara / Minutes of Meeting</p>
            <p class="mb-2"><strong>Perihal:</strong><br>{{ $addendum->name }}</p>
            <p class="mb-2"><strong>Tanggal:</strong><br>{{ $addendum->date->translatedFormat('d F Y') }}</p>
            <p class="mb-2"><strong>Proyek:</strong><br>{{ $addendum->project->name }}</p>
            <p><strong>Penerbit:</strong><br>{{ $addendum->project->company->name ?? 'PT ABD JAYA FAMILY' }}</p>
        </div>

        <div class="mt-6 text-xs text-gray-400">
            ID Dokumen: #{{ $addendum->id }} <br>
            Dicetak pada: {{ now()->format('d M Y H:i') }}
        </div>
    </div>
</body>
</html>