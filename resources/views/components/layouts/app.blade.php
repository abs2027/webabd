<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name_viewport="width=device-width, initial-scale=1.0">
    
    <title>{{ $title ?? 'WEB ABD Group' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">

    <x-navbar />

    <main>
        {{ $slot }}
    </main>
    
    </body>
</html>