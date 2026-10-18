<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MahasiswaPdoTest extends TestCase
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

    public function test_displays_all_students_when_no_nim_parameter_provided(): void
    {
        $response = $this->get('/mahasiswa');

        $response->assertOk();
        $response->assertSee('Data Mahasiswa');
        $response->assertSee('Menampilkan semua data.');
        $response->assertSee('2401020001');
        $response->assertSee('Amira Azza Nuuradiba');
        $response->assertSee('Teknik Informatika');
    }

    public function test_displays_filtered_student_when_valid_nim_provided(): void
    {
        $response = $this->get('/mahasiswa/2401020001');

        $response->assertOk();
        $response->assertSee('Filter NIM: 2401020001');
        $response->assertSee('Amira Azza Nuuradiba');
        $response->assertDontSee('Akbar Rizki Lingga');
        $response->assertSee('Tampilkan semua data');
    }

    public function test_displays_data_tidak_ditemukan_when_nim_does_not_exist(): void
    {
        $response = $this->get('/mahasiswa/9999999999');

        $response->assertOk();
        $response->assertSee('Filter NIM: 9999999999');
        $response->assertSee('Data tidak ditemukan.');
        $response->assertSee('Tampilkan semua data');
    }

    public function test_returns_500_and_error_message_when_database_fails(): void
    {
        config(['database.connections.mysql.database' => 'praktikum_web_salah']);
        DB::purge('mysql');

        $response = $this->get('/mahasiswa');

        $response->assertStatus(500);
        $response->assertSee('Koneksi atau query basis data gagal. Periksa file .env dan layanan MySQL.');
    }
}
