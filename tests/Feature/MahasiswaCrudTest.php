<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MahasiswaCrudTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'praktikum_web_2401020015']);
        config(['database.connections.mysql.host' => '127.0.0.1']);
        config(['database.connections.mysql.username' => 'root']);
        config(['database.connections.mysql.password' => '']);
        DB::purge('mysql');
    }

    public function test_read_halaman_awal_menampilkan_data_dan_program_studi(): void
    {
        $response = $this->get('/mahasiswa');

        $response->assertOk();
        $response->assertSee('Data Mahasiswa');
        $response->assertSee('Tambah Mahasiswa');
        $response->assertSee('Daftar Mahasiswa');
        $response->assertSee('Teknik Informatika');
        $response->assertSee('Sistem Informasi');
        $response->assertSee('2401020001');
        $response->assertSee('Amira Azza Nuuradiba');
    }

    public function test_create_data_valid_berhasil_disimpan_dan_redirect(): void
    {
        // Bersihkan jika data uji coba sebelumnya ada
        $pdo = DB::connection()->getPdo();
        $stmt = $pdo->prepare('DELETE FROM mahasiswa WHERE nim = :nim');
        $stmt->execute(['nim' => '2201010003']);

        $response = $this->post('/mahasiswa', [
            'nim' => '2201010003',
            'nama' => 'Rina Kurnia',
            'email' => 'rina@example.com',
            'usia' => 20,
            'program_studi_id' => 1,
        ]);

        $response->assertRedirect('/mahasiswa');
        $response->assertSessionHas('berhasil', 'Data mahasiswa berhasil ditambahkan.');

        // Verifikasi langsung ke database
        $cek = $pdo->prepare('SELECT COUNT(*) FROM mahasiswa WHERE nim = :nim');
        $cek->execute(['nim' => '2201010003']);
        $this->assertEquals(1, (int) $cek->fetchColumn());

        // Verifikasi hasil tampil di halaman Read
        $readResponse = $this->get('/mahasiswa');
        $readResponse->assertSee('2201010003');
        $readResponse->assertSee('Rina Kurnia');
        $readResponse->assertSee('rina@example.com');
    }

    public function test_create_nim_duplikat_ditolak(): void
    {
        $response = $this->from('/mahasiswa')->post('/mahasiswa', [
            'nim' => '2201010003',
            'nama' => 'Rina Kurnia Baru',
            'email' => 'rina.baru@example.com',
            'usia' => 20,
            'program_studi_id' => 1,
        ]);

        $response->assertRedirect('/mahasiswa');
        $response->assertSessionHas('gagal', 'NIM atau email sudah digunakan.');
    }

    public function test_create_email_duplikat_ditolak(): void
    {
        $response = $this->from('/mahasiswa')->post('/mahasiswa', [
            'nim' => '2201010999',
            'nama' => 'Orang Lain',
            'email' => 'rina@example.com',
            'usia' => 22,
            'program_studi_id' => 1,
        ]);

        $response->assertRedirect('/mahasiswa');
        $response->assertSessionHas('gagal', 'NIM atau email sudah digunakan.');
    }

    public function test_create_validasi_email_tidak_valid(): void
    {
        $response = $this->from('/mahasiswa')->post('/mahasiswa', [
            'nim' => '2201010004',
            'nama' => 'Nama Test',
            'email' => 'bukan-email-valid',
            'usia' => 20,
            'program_studi_id' => 1,
        ]);

        $response->assertRedirect('/mahasiswa');
        $response->assertSessionHasErrors(['email' => 'Format email tidak valid.']);
    }

    public function test_create_validasi_field_wajib_kosong(): void
    {
        $response = $this->from('/mahasiswa')->post('/mahasiswa', [
            'nim' => '',
            'nama' => '',
            'email' => '',
            'usia' => '',
            'program_studi_id' => '',
        ]);

        $response->assertRedirect('/mahasiswa');
        $response->assertSessionHasErrors([
            'nim' => 'NIM wajib diisi.',
            'nama' => 'Nama wajib diisi.',
            'email' => 'Email wajib diisi.',
            'usia' => 'Usia wajib diisi.',
            'program_studi_id' => 'Program studi wajib dipilih.',
        ]);
    }

    public function test_read_database_error_mengembalikan_500(): void
    {
        config(['database.connections.mysql.database' => 'praktikum_web_salah']);
        DB::purge('mysql');

        $response = $this->get('/mahasiswa');

        $response->assertStatus(500);
        $response->assertSee('Data tidak dapat dibaca. Periksa koneksi dan query.');
    }
}
