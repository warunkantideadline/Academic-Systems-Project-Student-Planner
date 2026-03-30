<?php
// actions/process_matakuliah.php
session_start();
require_once '../config/database.php';

$action     = $_POST['action'] ?? $_GET['action'] ?? null;
$semesterId = sanitize($_POST['semester_id'] ?? $_GET['semester_id'] ?? '');

switch ($action) {

    // ── CREATE ──────────────────────────────────────────────
    case 'create':
        $nama  = sanitize($_POST['nama']        ?? '');
        $sks   = (int)($_POST['sks']            ?? 0);
        $hari  = sanitize($_POST['jadwal_hari'] ?? '');
        $jam   = sanitize($_POST['jadwal_jam']  ?? '');
        $dosen = sanitize($_POST['nama_dosen']  ?? '');

        if (empty($nama) || empty($hari) || empty($jam) || empty($dosen) || $sks < 1) {
            setFlash('danger', 'Semua field wajib diisi!');
            redirect("../index.php?page=matakuliah&semester_id={$semesterId}");
        }

        $mataKuliahs   = readData('mata_kuliah');
        $mataKuliahs[] = [
            'id'          => generateId('MK'),
            'semester_id' => $semesterId,
            'nama'        => $nama,
            'sks'         => $sks,
            'jadwal_hari' => $hari,
            'jadwal_jam'  => $jam,
            'nama_dosen'  => $dosen,
            'created_at'  => date('Y-m-d'),
        ];
        writeData('mata_kuliah', $mataKuliahs);

        setFlash('success', "Mata kuliah \"{$nama}\" berhasil ditambahkan!");
        redirect("../index.php?page=matakuliah&semester_id={$semesterId}");
        break;

    // ── UPDATE ──────────────────────────────────────────────
    case 'update':
        $id    = sanitize($_POST['id']          ?? '');
        $nama  = sanitize($_POST['nama']        ?? '');
        $sks   = (int)($_POST['sks']            ?? 0);
        $hari  = sanitize($_POST['jadwal_hari'] ?? '');
        $jam   = sanitize($_POST['jadwal_jam']  ?? '');
        $dosen = sanitize($_POST['nama_dosen']  ?? '');

        if (empty($id) || empty($nama)) {
            setFlash('danger', 'Data tidak valid!');
            redirect("../index.php?page=matakuliah&semester_id={$semesterId}");
        }

        // Cek apakah semester selesai untuk proses nilai override
        $semester      = findById('semester', $semesterId);
        $nilaiOverride = null;
        if ($semester && $semester['status'] === 'selesai') {
            $raw = $_POST['nilai_akhir_override'] ?? '';
            if ($raw !== '') {
                $nilaiOverride = max(0, min(100, (float)$raw));
            }
        }

        $mataKuliahs = readData('mata_kuliah');
        foreach ($mataKuliahs as &$mk) {
            if ($mk['id'] === $id) {
                $mk['nama']        = $nama;
                $mk['sks']         = $sks;
                $mk['jadwal_hari'] = $hari;
                $mk['jadwal_jam']  = $jam;
                $mk['nama_dosen']  = $dosen;
                // Simpan override jika ada, hapus jika dikosongkan
                if ($nilaiOverride !== null) {
                    $mk['nilai_akhir_override'] = $nilaiOverride;
                } elseif ($semester && $semester['status'] === 'selesai') {
                    // Field dikirim tapi dikosongkan → hapus override
                    unset($mk['nilai_akhir_override']);
                }
                break;
            }
        }
        unset($mk);
        writeData('mata_kuliah', $mataKuliahs);

        setFlash('success', "Mata kuliah \"{$nama}\" berhasil diperbarui!");
        redirect("../index.php?page=matakuliah&semester_id={$semesterId}");
        break;

    // ── DELETE ──────────────────────────────────────────────
    case 'delete':
        $id = sanitize($_GET['id'] ?? '');

        if (empty($id)) {
            setFlash('danger', 'ID mata kuliah tidak ditemukan!');
            redirect("../index.php?page=matakuliah&semester_id={$semesterId}");
        }

        $mk   = findById('mata_kuliah', $id);
        $nama = $mk['nama'] ?? 'Mata Kuliah';

        deleteWhere('absensi',     'mata_kuliah_id', $id);
        deleteWhere('tugas',       'mata_kuliah_id', $id);
        deleteWhere('nilai_ujian', 'mata_kuliah_id', $id);
        deleteById('mata_kuliah', $id);

        setFlash('success', "Mata kuliah \"{$nama}\" berhasil dihapus!");
        redirect("../index.php?page=matakuliah&semester_id={$semesterId}");
        break;

    default:
        redirect("../index.php?page=semester");
}