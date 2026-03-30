<?php
// actions/process_tugas.php
session_start();
require_once '../config/database.php';

$action     = $_POST['action'] ?? $_GET['action'] ?? null;
$mkId       = sanitize($_POST['mata_kuliah_id'] ?? $_GET['mk_id'] ?? '');
$semesterId = sanitize($_POST['semester_id']    ?? $_GET['semester_id'] ?? '');

$redirectUrl = '../index.php?page=matakuliah&action=detail&id=' . $mkId . '&semester_id=' . $semesterId . '&tab=tugas';

switch ($action) {

    // ── CREATE ──────────────────────────────────────────────
    case 'create':
        $namaTugas = sanitize($_POST['nama_tugas'] ?? '');
        $deadline  = sanitize($_POST['deadline']   ?? '');
        $nilai     = (int)($_POST['nilai']         ?? 0);

        if (empty($namaTugas) || empty($deadline)) {
            setFlash('danger', 'Nama tugas dan deadline wajib diisi!');
            redirect($redirectUrl);
        }

        if (empty($mkId)) {
            setFlash('danger', 'ID Mata Kuliah tidak ditemukan!');
            redirect('../index.php?page=semester');
        }

        $tugasList   = readData('tugas');
        $newTugas    = [
            'id'             => generateId('TGS'),
            'mata_kuliah_id' => $mkId,
            'nama_tugas'     => $namaTugas,
            'deadline'       => $deadline,
            'nilai'          => max(0, min(100, $nilai)),
            'created_at'     => date('Y-m-d'),
        ];

        $tugasList[] = $newTugas;
        writeData('tugas', $tugasList);

        setFlash('success', "Tugas \"{$namaTugas}\" berhasil ditambahkan!");
        redirect($redirectUrl);
        break;

    // ── UPDATE ──────────────────────────────────────────────
    case 'update':
        $id        = sanitize($_POST['id']         ?? '');
        $namaTugas = sanitize($_POST['nama_tugas'] ?? '');
        $deadline  = sanitize($_POST['deadline']   ?? '');
        $nilai     = (int)($_POST['nilai']         ?? 0);

        if (empty($id) || empty($namaTugas)) {
            setFlash('danger', 'Data tidak valid!');
            redirect($redirectUrl);
        }

        $tugasList = readData('tugas');
        foreach ($tugasList as &$tgs) {
            if ($tgs['id'] === $id) {
                $tgs['nama_tugas'] = $namaTugas;
                $tgs['deadline']   = $deadline;
                $tgs['nilai']      = max(0, min(100, $nilai));
                break;
            }
        }
        unset($tgs);
        writeData('tugas', $tugasList);

        setFlash('success', "Tugas \"{$namaTugas}\" berhasil diperbarui!");
        redirect($redirectUrl);
        break;

    // ── DELETE ──────────────────────────────────────────────
    case 'delete':
        $id = sanitize($_GET['id'] ?? '');

        if (empty($id)) {
            setFlash('danger', 'ID tugas tidak ditemukan!');
            redirect($redirectUrl);
        }

        $tgs  = findById('tugas', $id);
        $nama = $tgs['nama_tugas'] ?? 'Tugas';

        deleteById('tugas', $id);

        setFlash('success', "Tugas \"{$nama}\" berhasil dihapus!");
        redirect($redirectUrl);
        break;

    default:
        redirect('../index.php?page=semester');
}