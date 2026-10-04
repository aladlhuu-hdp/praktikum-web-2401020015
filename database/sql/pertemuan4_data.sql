USE praktikum_web_2401020167;

INSERT INTO program_studi (nama_prodi) VALUES
    ('Teknik Informatika'),
    ('Sistem Informasi');

INSERT INTO mahasiswa
    (nim, nama, email, usia, program_studi_id)
VALUES
    ('2401020051', 'Abdul Hafidz',
     'abdulhafidz@gmail.com', 21, 2),
    ('2401020022', 'Azizul Rizky Mahadi',
     '2401020022@student.umrah.ac.id', 40, 1),
    ('2401020035', 'Dzaky Ribal Faiz',
     '2401020035@student.umrah.ac.id', 21, 2),
    ('2401020111', 'Data Sementara',
     'sementaraaja@example.com', 18, 2);

UPDATE mahasiswa
SET email = '2401020051@student.umrah.ac.id'
WHERE nim = '2401020051';

DELETE FROM mahasiswa
WHERE nim = '2401020111';

SELECT
    m.nim,
    m.nama,
    m.email,
    m.usia,
    p.nama_prodi
FROM mahasiswa AS m
JOIN program_studi AS p
    ON p.id = m.program_studi_id
ORDER BY m.nim;
