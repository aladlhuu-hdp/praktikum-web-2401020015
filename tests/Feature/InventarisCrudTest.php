<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventarisCrudTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'inventaris_lab_2401020015']);
        config(['database.connections.mysql.host' => '127.0.0.1']);
        config(['database.connections.mysql.username' => 'root']);
        config(['database.connections.mysql.password' => '']);
        DB::purge('mysql');
    }

    private function dataValid(array $ubah = []): array
    {
        return array_merge([
            'kode_barang' => 'IV00000099',
            'nama_barang' => 'Switch Uji',
            'nomor_aset' => 'AST-LAB-0099',
            'tahun_perolehan' => 2024,
            'ruang_id' => 1,
        ], $ubah);
    }

    private function hapusDataUji(): void
    {
        DB::connection()->getPdo()
            ->prepare("DELETE FROM inventaris WHERE kode_barang IN ('IV00000099', 'IV00000098')")
            ->execute();
    }

    public function test_read_menampilkan_data_awal_dan_ruang(): void
    {
        $this->hapusDataUji();

        $response = $this->get('/inventaris');

        $response->assertOk();
        $response->assertSee('Inventaris Laboratorium');
        $response->assertSee('Tambah Inventaris');
        $response->assertSee('Daftar Inventaris');
        $response->assertSee('IV00000001');
        $response->assertSee('Komputer Praktikum');
        $response->assertSee('Lab Pemrograman');
        $response->assertSee('Lab Jaringan');
    }

    public function test_create_data_valid_berhasil_disimpan(): void
    {
        $this->hapusDataUji();

        $response = $this->post('/inventaris', $this->dataValid());

        $response->assertRedirect('/inventaris');
        $response->assertSessionHas('berhasil', 'Data inventaris berhasil ditambahkan.');
        $this->get('/inventaris')->assertSee('Switch Uji');

        $this->hapusDataUji();
    }

    public function test_create_tidak_valid_menampilkan_error(): void
    {
        $response = $this->from('/inventaris')->post('/inventaris', $this->dataValid([
            'kode_barang' => 'ab',
            'tahun_perolehan' => 1999,
        ]));

        $response->assertRedirect('/inventaris');
        $response->assertSessionHasErrors([
            'kode_barang' => 'Kode barang harus tepat 10 karakter.',
            'tahun_perolehan' => 'Tahun perolehan harus antara 2000 dan 2026.',
        ]);
        $response->assertSessionHasInput('nama_barang', 'Switch Uji');
    }

    public function test_create_field_wajib_kosong(): void
    {
        $response = $this->from('/inventaris')->post('/inventaris', [
            'kode_barang' => '',
            'nama_barang' => '',
            'nomor_aset' => '',
            'tahun_perolehan' => '',
            'ruang_id' => '',
        ]);

        $response->assertSessionHasErrors([
            'kode_barang' => 'Kode barang wajib diisi.',
            'nama_barang' => 'Nama barang wajib diisi.',
            'nomor_aset' => 'Nomor aset wajib diisi.',
            'tahun_perolehan' => 'Tahun perolehan wajib diisi.',
            'ruang_id' => 'Ruang wajib dipilih.',
        ]);
    }

    public function test_create_kode_dan_nomor_aset_duplikat_ditolak(): void
    {
        $this->hapusDataUji();
        $this->post('/inventaris', $this->dataValid());

        $this->from('/inventaris')
            ->post('/inventaris', $this->dataValid(['nomor_aset' => 'AST-LAB-0098']))
            ->assertSessionHas('gagal', 'Kode barang sudah digunakan.');

        $this->from('/inventaris')
            ->post('/inventaris', $this->dataValid(['kode_barang' => 'IV00000098']))
            ->assertSessionHas('gagal', 'Nomor aset sudah digunakan.');

        $this->hapusDataUji();
    }

    public function test_update_berhasil_dan_konflik_ditolak(): void
    {
        $this->hapusDataUji();
        $this->post('/inventaris', $this->dataValid());
        $id = (int) DB::connection()->getPdo()
            ->query("SELECT id FROM inventaris WHERE kode_barang = 'IV00000099'")
            ->fetchColumn();

        $this->get("/inventaris/$id/edit")
            ->assertOk()
            ->assertSee('Edit Inventaris')
            ->assertSee('Switch Uji');

        $this->put("/inventaris/$id", $this->dataValid(['nama_barang' => 'Switch Baru']))
            ->assertSessionHas('berhasil', 'Data inventaris berhasil diperbarui.');
        $this->get('/inventaris')->assertSee('Switch Baru');

        $this->from("/inventaris/$id/edit")
            ->put("/inventaris/$id", $this->dataValid(['kode_barang' => 'IV00000001']))
            ->assertSessionHas('gagal', 'Kode barang sudah digunakan.');

        $this->hapusDataUji();
    }

    public function test_delete_menghapus_data(): void
    {
        $this->hapusDataUji();
        $this->post('/inventaris', $this->dataValid());
        $id = (int) DB::connection()->getPdo()
            ->query("SELECT id FROM inventaris WHERE kode_barang = 'IV00000099'")
            ->fetchColumn();

        $this->delete("/inventaris/$id")
            ->assertSessionHas('berhasil', 'Data inventaris berhasil dihapus.');

        $this->get('/inventaris')->assertDontSee('IV00000099');
    }

    public function test_pencarian_filter_dan_status_usia_aset(): void
    {
        $this->get('/inventaris?q=Router')
            ->assertSee('IV00000003')
            ->assertDontSee('IV00000001');

        $this->get('/inventaris?q=AST-LAB-0002')->assertSee('Proyektor');

        $this->get('/inventaris?ruang_id=2')
            ->assertSee('Router')
            ->assertDontSee('Proyektor');

        $this->assertSame('Baru', statusUsiaAset(2023));
        $this->assertSame('Lama', statusUsiaAset(2022));
    }

    public function test_read_database_error_mengembalikan_500(): void
    {
        config(['database.connections.mysql.database' => 'inventaris_lab_salah']);
        DB::purge('mysql');

        $response = $this->get('/inventaris');

        $response->assertStatus(500);
        $response->assertSee('Data tidak dapat dibaca. Periksa koneksi dan query.');
    }
}
