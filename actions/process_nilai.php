<?php
// actions/process_nilai.php
session_start();
require_once '../config/database.php';

$action     = $_POST['action'] ?? $_GET['action'] ?? null;
$mkId       = sanitize($_POST['mata_kuliah_id'] ?? $_GET['mk_id'] ?? '');
$semesterId = sanitize($_POST['semester_id']    ?? $_GET['semester_id'] ?? '');

switch ($action) {

    // ── SIMPAN / UPDATE NILAI UJIAN (UTS & UAS) ─────────────
    case 'save_ujian':
        $nilaiUts = (int)($_POST['nilai_uts'] ?? 0);
        $nilaiUas = (int)($_POST['nilai_uas'] ?? 0);
        $existId  = sanitize($_POST['id'] ?? '');

        $nilaiUts = max(0, min(100, $nilaiUts));
        $nilaiUas = max(0, min(100, $nilaiUas));

        $nilaiUjianList = readData('nilai_ujian');

        if (!empty($existId)) {
            foreach ($nilaiUjianList as &$nu) {
                if ($nu['id'] === $existId) {
                    $nu['nilai_uts']  = $nilaiUts;
                    $nu['nilai_uas']  = $nilaiUas;
                    $nu['updated_at'] = date('Y-m-d');
                    break;
                }
            }
            unset($nu);
        } else {
            $nilaiUjianList[] = [
                'id'             => generateId('NLU'),
                'mata_kuliah_id' => $mkId,
                'nilai_uts'      => $nilaiUts,
                'nilai_uas'      => $nilaiUas,
                'updated_at'     => date('Y-m-d'),
            ];
        }

        writeData('nilai_ujian', $nilaiUjianList);
        setFlash('success', 'Nilai UTS dan UAS berhasil disimpan!');
        redirect('../index.php?page=matakuliah&action=detail&id=' . $mkId . '&semester_id=' . $semesterId . '&tab=ujian');
        break;

    // ── CREATE ABSENSI ───────────────────────────────────────
    case 'create_absensi':
        $pertemuanKe = (int)($_POST['pertemuan_ke'] ?? 1);
        $tanggal     = sanitize($_POST['tanggal']    ?? date('Y-m-d'));
        $status      = sanitize($_POST['status']     ?? 'hadir');
        $keterangan  = sanitize($_POST['keterangan'] ?? '');

        $validStatus = ['hadir', 'izin', 'sakit', 'alpha'];
        if (!in_array($status, $validStatus)) {
            setFlash('danger', 'Status kehadiran tidak valid!');
            redirect('../index.php?page=matakuliah&action=detail&id=' . $mkId . '&semester_id=' . $semesterId . '&tab=absensi');
        }

        // Cek duplikasi pertemuan ke-X di mata kuliah yang sama
        $existing = findWhere('absensi', 'mata_kuliah_id', $mkId);
        foreach ($existing as $abs) {
            if ((int)$abs['pertemuan_ke'] === $pertemuanKe) {
                setFlash('warning', "Pertemuan ke-{$pertemuanKe} sudah ada! Hapus dulu jika ingin menggantinya.");
                redirect('../index.php?page=matakuliah&action=detail&id=' . $mkId . '&semester_id=' . $semesterId . '&tab=absensi');
            }
        }

        $absensiList   = readData('absensi');
        $absensiList[] = [
            'id'             => generateId('ABS'),
            'mata_kuliah_id' => $mkId,
            'pertemuan_ke'   => $pertemuanKe,
            'tanggal'        => $tanggal,
            'status'         => $status,
            'keterangan'     => $keterangan,
        ];

        writeData('absensi', $absensiList);
        setFlash('success', "Absensi pertemuan ke-{$pertemuanKe} berhasil dicatat!");
        redirect('../index.php?page=matakuliah&action=detail&id=' . $mkId . '&semester_id=' . $semesterId . '&tab=absensi');
        break;

    // ── DELETE ABSENSI ───────────────────────────────────────
    case 'delete_absensi':
        $id = sanitize($_GET['id'] ?? '');

        if (empty($id)) {
            setFlash('danger', 'ID absensi tidak ditemukan!');
            redirect('../index.php?page=matakuliah&action=detail&id=' . $mkId . '&semester_id=' . $semesterId . '&tab=absensi');
        }

        deleteById('absensi', $id);
        setFlash('success', 'Data absensi berhasil dihapus!');
        redirect('../index.php?page=matakuliah&action=detail&id=' . $mkId . '&semester_id=' . $semesterId . '&tab=absensi');
        break;

    default:
        redirect('../index.php?page=semester');
}