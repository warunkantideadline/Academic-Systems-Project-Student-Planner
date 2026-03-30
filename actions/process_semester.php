<?php
// actions/process_semester.php
session_start();
require_once '../config/database.php';

$action = $_POST['action'] ?? $_GET['action'] ?? null;

switch ($action) {

    // ── CREATE ──────────────────────────────────────────────
    case 'create':
        $nama          = sanitize($_POST['nama'] ?? '');
        $tahunAkademik = sanitize($_POST['tahun_akademik'] ?? '');
        $status        = sanitize($_POST['status'] ?? 'aktif');

        if (empty($nama) || empty($tahunAkademik)) {
            setFlash('danger', 'Nama dan Tahun Akademik wajib diisi!');
            redirect('../index.php?page=semester');
        }

        $semesters = readData('semester');
        $newSemester = [
            'id'             => generateId('SMT'),
            'nama'           => $nama,
            'tahun_akademik' => $tahunAkademik,
            'status'         => $status,
            'created_at'     => date('Y-m-d'),
        ];

        $semesters[] = $newSemester;
        writeData('semester', $semesters);

        setFlash('success', "Semester \"{$nama}\" berhasil ditambahkan!");
        redirect('../index.php?page=semester');
        break;

    // ── UPDATE ──────────────────────────────────────────────
    case 'update':
        $id            = sanitize($_POST['id'] ?? '');
        $nama          = sanitize($_POST['nama'] ?? '');
        $tahunAkademik = sanitize($_POST['tahun_akademik'] ?? '');
        $status        = sanitize($_POST['status'] ?? 'aktif');

        if (empty($id) || empty($nama)) {
            setFlash('danger', 'Data tidak valid!');
            redirect('../index.php?page=semester');
        }

        $semesters = readData('semester');
        foreach ($semesters as &$sem) {
            if ($sem['id'] === $id) {
                $sem['nama']           = $nama;
                $sem['tahun_akademik'] = $tahunAkademik;
                $sem['status']         = $status;
                break;
            }
        }
        unset($sem);
        writeData('semester', $semesters);

        setFlash('success', "Semester \"{$nama}\" berhasil diperbarui!");
        redirect('../index.php?page=semester');
        break;

    // ── DELETE ──────────────────────────────────────────────
    case 'delete':
        $id = sanitize($_GET['id'] ?? '');

        if (empty($id)) {
            setFlash('danger', 'ID semester tidak ditemukan!');
            redirect('../index.php?page=semester');
        }

        // Ambil nama semester sebelum dihapus untuk flash message
        $sem = findById('semester', $id);
        $namaSem = $sem['nama'] ?? 'Semester';

        // Cascade delete: hapus semua mata kuliah di semester ini
        $mataKuliahs = findWhere('mata_kuliah', 'semester_id', $id);
        foreach ($mataKuliahs as $mk) {
            deleteWhere('absensi',     'mata_kuliah_id', $mk['id']);
            deleteWhere('tugas',       'mata_kuliah_id', $mk['id']);
            deleteWhere('nilai_ujian', 'mata_kuliah_id', $mk['id']);
        }
        deleteWhere('mata_kuliah', 'semester_id', $id);
        deleteById('semester', $id);

        setFlash('success', "Semester \"{$namaSem}\" dan semua datanya berhasil dihapus!");
        redirect('../index.php?page=semester');
        break;

    default:
        redirect('../index.php?page=semester');
}