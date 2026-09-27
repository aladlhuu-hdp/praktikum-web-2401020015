<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form Mahasiswa</title>
</head>
<body>
    <h1>Form Mahasiswa</h1>

    @if ($errors->any())
        <h2>Data belum valid</h2>
        <ul>
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="/form-mahasiswa" novalidate>
        @csrf
        <p>
            <label for="nama">Nama:</label><br>
            <input id="nama" type="text" name="nama" value="{{ old('nama') }}">
        </p>
        <p>
            <label for="email">Email:</label><br>
            <input id="email" type="email" name="email" value="{{ old('email') }}">
        </p>
        <p>
            <label for="usia">Usia:</label><br>
            <input id="usia" type="number" name="usia" value="{{ old('usia') }}">
        </p>
        <p>
            <label for="nim">NIM:</label><br>
            <input id="nim" type="text" name="nim" value="{{ old('nim') }}">
        </p>
        <button type="submit">Kirim</button>
    </form>
</body>
</html>
