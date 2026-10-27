<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

if (! function_exists('statusUsiaAset')) {
    function statusUsiaAset(int $tahun): string
    {
        return $tahun >= 2023 ? 'Baru' : 'Lama';
    }
}

if (! function_exists('bersihkanTeks')) {
    function bersihkanTeks(mixed $nilai): string
    {
        return trim(strip_tags((string) $nilai));
    }
}

if (! function_exists('validasiInventaris')) {
    function validasiInventaris(Request $request): array
    {
        $request->merge([
            'kode_barang' => bersihkanTeks($request->input('kode_barang')),
            'nama_barang' => bersihkanTeks($request->input('nama_barang')),
            'nomor_aset' => bersihkanTeks($request->input('nomor_aset')),
        ]);

        return $request->validate([
            'kode_barang' => ['required', 'size:10', 'alpha_num'],
            'nama_barang' => ['required', 'string', 'min:3', 'max:100'],
            'nomor_aset' => ['required', 'string', 'min:5', 'max:30'],
            'tahun_perolehan' => ['required', 'integer', 'between:2000,2026'],
            'ruang_id' => ['required', 'integer'],
        ], [
            'kode_barang.required' => 'Kode barang wajib diisi.',
            'kode_barang.size' => 'Kode barang harus tepat 10 karakter.',
            'kode_barang.alpha_num' => 'Kode barang hanya boleh huruf dan angka.',
            'nama_barang.required' => 'Nama barang wajib diisi.',
            'nama_barang.min' => 'Nama barang minimal 3 karakter.',
            'nama_barang.max' => 'Nama barang maksimal 100 karakter.',
            'nomor_aset.required' => 'Nomor aset wajib diisi.',
            'nomor_aset.min' => 'Nomor aset minimal 5 karakter.',
            'nomor_aset.max' => 'Nomor aset maksimal 30 karakter.',
            'tahun_perolehan.required' => 'Tahun perolehan wajib diisi.',
            'tahun_perolehan.integer' => 'Tahun perolehan harus berupa angka.',
            'tahun_perolehan.between' => 'Tahun perolehan harus antara 2000 dan 2026.',
            'ruang_id.required' => 'Ruang wajib dipilih.',
            'ruang_id.integer' => 'Ruang tidak valid.',
        ]);
    }
}

if (! function_exists('daftarRuang')) {
    function daftarRuang(PDO $pdo): array
    {
        $stmt = $pdo->prepare('SELECT id, nama_ruang FROM ruang ORDER BY nama_ruang');
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (! function_exists('pesanKesalahanInventaris')) {
    function pesanKesalahanInventaris(PDO $pdo, array $data, int $idSendiri = 0): ?string
    {
        $cekRuang = $pdo->prepare('SELECT COUNT(*) FROM ruang WHERE id = :id');
        $cekRuang->execute(['id' => $data['ruang_id']]);

        if ((int) $cekRuang->fetchColumn() === 0) {
            return 'Ruang tidak tersedia.';
        }

        $cekDuplikat = $pdo->prepare(
            'SELECT kode_barang, nomor_aset
             FROM inventaris
             WHERE (kode_barang = :kode OR nomor_aset = :aset)
               AND id <> :id'
        );
        $cekDuplikat->execute([
            'kode' => $data['kode_barang'],
            'aset' => $data['nomor_aset'],
            'id' => $idSendiri,
        ]);
        $baris = $cekDuplikat->fetch(PDO::FETCH_ASSOC);

        if (! $baris) {
            return null;
        }

        return $baris['kode_barang'] === $data['kode_barang']
            ? 'Kode barang sudah digunakan.'
            : 'Nomor aset sudah digunakan.';
    }
}

if (! function_exists('parameterInventaris')) {
    function parameterInventaris(array $data): array
    {
        return [
            'kode' => $data['kode_barang'],
            'nama' => $data['nama_barang'],
            'aset' => $data['nomor_aset'],
            'tahun' => $data['tahun_perolehan'],
            'ruang' => $data['ruang_id'],
        ];
    }
}

Route::get('/inventaris', function (Request $request) {
    $kataKunci = bersihkanTeks($request->query('q', ''));
    $filterRuang = (int) $request->query('ruang_id', 0);

    try {
        $pdo = DB::connection()->getPdo();

        $pernyataan = $pdo->prepare(
            'SELECT i.id, i.kode_barang, i.nama_barang, i.nomor_aset,
                    i.tahun_perolehan, r.nama_ruang
             FROM inventaris AS i
             JOIN ruang AS r ON r.id = i.ruang_id
             WHERE (i.kode_barang LIKE :q1
                    OR i.nama_barang LIKE :q2
                    OR i.nomor_aset LIKE :q3)
               AND (:ruang1 = 0 OR i.ruang_id = :ruang2)
             ORDER BY i.kode_barang'
        );
        $like = '%' . $kataKunci . '%';
        $pernyataan->execute([
            'q1' => $like,
            'q2' => $like,
            'q3' => $like,
            'ruang1' => $filterRuang,
            'ruang2' => $filterRuang,
        ]);

        return view('inventaris', [
            'daftarInventaris' => $pernyataan->fetchAll(PDO::FETCH_ASSOC),
            'daftarRuang' => daftarRuang($pdo),
            'kataKunci' => $kataKunci,
            'filterRuang' => $filterRuang,
            'barangEdit' => null,
        ]);
    } catch (\Throwable $error) {
        report($error);

        return response(
            'Data tidak dapat dibaca. Periksa koneksi dan query.',
            500
        );
    }
})->name('inventaris.index');

Route::post('/inventaris', function (Request $request) {
    $data = validasiInventaris($request);

    try {
        $pdo = DB::connection()->getPdo();

        if ($pesan = pesanKesalahanInventaris($pdo, $data)) {
            return back()->withInput()->with('gagal', $pesan);
        }

        $simpan = $pdo->prepare(
            'INSERT INTO inventaris
             (kode_barang, nama_barang, nomor_aset, tahun_perolehan,
              ruang_id, created_at, updated_at)
             VALUES (:kode, :nama, :aset, :tahun, :ruang, NOW(), NOW())'
        );
        $simpan->execute(parameterInventaris($data));

        return redirect()->route('inventaris.index')->with(
            'berhasil',
            'Data inventaris berhasil ditambahkan.'
        );
    } catch (\Throwable $error) {
        report($error);

        return back()->withInput()->with(
            'gagal',
            'Data gagal disimpan. Periksa koneksi dan data.'
        );
    }
})->name('inventaris.store');

Route::get('/inventaris/{id}/edit', function (int $id) {
    try {
        $pdo = DB::connection()->getPdo();

        $pernyataan = $pdo->prepare(
            'SELECT id, kode_barang, nama_barang, nomor_aset,
                    tahun_perolehan, ruang_id
             FROM inventaris
             WHERE id = :id'
        );
        $pernyataan->execute(['id' => $id]);
        $barang = $pernyataan->fetch(PDO::FETCH_ASSOC);

        if (! $barang) {
            return redirect()->route('inventaris.index')->with(
                'gagal',
                'Data inventaris tidak ditemukan.'
            );
        }

        $pernyataanDaftar = $pdo->prepare(
            'SELECT i.id, i.kode_barang, i.nama_barang, i.nomor_aset,
                    i.tahun_perolehan, r.nama_ruang
             FROM inventaris AS i
             JOIN ruang AS r ON r.id = i.ruang_id
             ORDER BY i.kode_barang'
        );
        $pernyataanDaftar->execute();

        return view('inventaris', [
            'daftarInventaris' => $pernyataanDaftar->fetchAll(PDO::FETCH_ASSOC),
            'daftarRuang' => daftarRuang($pdo),
            'kataKunci' => '',
            'filterRuang' => 0,
            'barangEdit' => $barang,
        ]);
    } catch (\Throwable $error) {
        report($error);

        return response(
            'Data tidak dapat dibaca. Periksa koneksi dan query.',
            500
        );
    }
})->whereNumber('id')->name('inventaris.edit');

Route::put('/inventaris/{id}', function (Request $request, int $id) {
    $data = validasiInventaris($request);

    try {
        $pdo = DB::connection()->getPdo();

        if ($pesan = pesanKesalahanInventaris($pdo, $data, $id)) {
            return back()->withInput()->with('gagal', $pesan);
        }

        $ubah = $pdo->prepare(
            'UPDATE inventaris
             SET kode_barang = :kode, nama_barang = :nama,
                 nomor_aset = :aset, tahun_perolehan = :tahun,
                 ruang_id = :ruang, updated_at = NOW()
             WHERE id = :id'
        );
        $ubah->execute(parameterInventaris($data) + ['id' => $id]);

        return redirect()->route('inventaris.index')->with(
            'berhasil',
            'Data inventaris berhasil diperbarui.'
        );
    } catch (\Throwable $error) {
        report($error);

        return back()->withInput()->with(
            'gagal',
            'Data gagal disimpan. Periksa koneksi dan data.'
        );
    }
})->whereNumber('id')->name('inventaris.update');

Route::delete('/inventaris/{id}', function (int $id) {
    try {
        $hapus = DB::connection()->getPdo()->prepare(
            'DELETE FROM inventaris WHERE id = :id'
        );
        $hapus->execute(['id' => $id]);

        if ($hapus->rowCount() === 0) {
            return redirect()->route('inventaris.index')->with(
                'gagal',
                'Data inventaris tidak ditemukan.'
            );
        }

        return redirect()->route('inventaris.index')->with(
            'berhasil',
            'Data inventaris berhasil dihapus.'
        );
    } catch (\Throwable $error) {
        report($error);

        return redirect()->route('inventaris.index')->with(
            'gagal',
            'Data gagal dihapus. Periksa koneksi dan data.'
        );
    }
})->whereNumber('id')->name('inventaris.destroy');

