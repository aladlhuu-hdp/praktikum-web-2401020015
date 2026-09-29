USE praktikum_web;

INSERT INTO program_studi (nama_prodi) VALUES
    ('Teknik Informatika'),
    ('Sistem Informasi');

INSERT INTO mahasiswa
    (nim, nama, email, usia, program_studi_id)
VALUES
    ('2401020001', 'Amira Azza Nuuradiba',
     '2401020001@student.umrah.ac.id', 20, 1),
    ('2401020003', 'Akbar Rizki Lingga',
     '2401020003@student.umrah.ac.id', 19, 1),
    ('2401020010', 'Bayu Adhandika',
     '2401020010@student.umrah.ac.id', 21, 2),
    ('2401020015', 'Data Sementara',
     'sementara@example.com', 18, 2);

UPDATE mahasiswa
SET email = '2401020001@student.umrah.ac.id'
WHERE nim = '2401020001';

DELETE FROM mahasiswa
WHERE nim = '2401020015';

SELECT m.nim, m.nama, m.email, m.usia,
       p.nama_prodi
FROM mahasiswa AS m
JOIN program_studi AS p
    ON p.id = m.program_studi_id
ORDER BY m.nim;
