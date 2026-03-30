<?php
// actions/process_export.php
session_start();
require_once '../config/database.php';

$type = $_GET['type'] ?? '';

// ── EXPORT SEMESTER ────────────────────────────────────────
if ($type === 'semester') {
    $semesters = readData('semester');

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="export_semester_' . date('Ymd_His') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // BOM UTF-8 agar Excel baca encoding dengan benar
    fputs($output, "\xEF\xBB\xBF");

    // Header
    fputcsv($output, ['nama', 'tahun_akademik', 'status']);

    foreach ($semesters as $sem) {
        fputcsv($output, [
            $sem['nama']            ?? '',
            $sem['tahun_akademik']  ?? '',
            $sem['status']          ?? '',
        ]);
    }

    fclose($output);
    exit;
}

// ── EXPORT MATA KULIAH ─────────────────────────────────────
if ($type === 'matakuliah') {
    $mataKuliahs = readData('mata_kuliah');
    $semesters   = readData('semester');

    // Buat map id → nama semester
    $semesterMap = [];
    foreach ($semesters as $s) {
        $semesterMap[$s['id']] = $s['nama'];
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="export_matakuliah_' . date('Ymd_His') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF");

    // Header
    fputcsv($output, ['semester_nama', 'nama', 'sks', 'hari', 'jam', 'dosen', 'nilai_akhir']);

    foreach ($mataKuliahs as $mk) {
        $semNama    = $semesterMap[$mk['semester_id'] ?? ''] ?? '';
        $rekap      = hitungNilaiAkhir($mk['id']);
        $nilaiAkhir = $rekap['nilai_akhir'] > 0 ? $rekap['nilai_akhir'] : '';

        fputcsv($output, [
            $semNama,
            $mk['nama']        ?? '',
            $mk['sks']         ?? '',
            $mk['jadwal_hari'] ?? '',
            $mk['jadwal_jam']  ?? '',
            $mk['nama_dosen']  ?? '',
            $nilaiAkhir,
        ]);
    }

    fclose($output);
    exit;
}

// ── EXPORT SEMUA (ZIP) ─────────────────────────────────────
if ($type === 'semua') {
    // Kalau tidak ada ekstensi zip, fallback ke semester saja
    if (!class_exists('ZipArchive')) {
        header('Location: ../actions/process_export.php?type=semester');
        exit;
    }

    $semesters   = readData('semester');
    $mataKuliahs = readData('mata_kuliah');
    $semesterMap = [];
    foreach ($semesters as $s) $semesterMap[$s['id']] = $s['nama'];

    $tmpDir  = sys_get_temp_dir();
    $zipFile = $tmpDir . '/siakad_export_' . date('Ymd_His') . '.zip';
    $zip     = new ZipArchive();
    $zip->open($zipFile, ZipArchive::CREATE);

    // CSV Semester
    $csvSem = "\xEF\xBB\xBF" . "nama,tahun_akademik,status\n";
    foreach ($semesters as $sem) {
        $csvSem .= implode(',', [
            '"' . str_replace('"', '""', $sem['nama']           ?? '') . '"',
            '"' . str_replace('"', '""', $sem['tahun_akademik'] ?? '') . '"',
            '"' . str_replace('"', '""', $sem['status']         ?? '') . '"',
        ]) . "\n";
    }
    $zip->addFromString('semester.csv', $csvSem);

    // CSV Mata Kuliah
    $csvMk = "\xEF\xBB\xBF" . "semester_nama,nama,sks,hari,jam,dosen,nilai_akhir\n";
    foreach ($mataKuliahs as $mk) {
        $semNama    = $semesterMap[$mk['semester_id'] ?? ''] ?? '';
        $rekap      = hitungNilaiAkhir($mk['id']);
        $nilaiAkhir = $rekap['nilai_akhir'] > 0 ? $rekap['nilai_akhir'] : '';
        $csvMk .= implode(',', [
            '"' . str_replace('"', '""', $semNama)                  . '"',
            '"' . str_replace('"', '""', $mk['nama']        ?? '') . '"',
            '"' . str_replace('"', '""', $mk['sks']         ?? '') . '"',
            '"' . str_replace('"', '""', $mk['jadwal_hari'] ?? '') . '"',
            '"' . str_replace('"', '""', $mk['jadwal_jam']  ?? '') . '"',
            '"' . str_replace('"', '""', $mk['nama_dosen']  ?? '') . '"',
            '"' . str_replace('"', '""', $nilaiAkhir)               . '"',
        ]) . "\n";
    }
    $zip->addFromString('mata_kuliah.csv', $csvMk);

    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="siakad_export_' . date('Ymd_His') . '.zip"');
    header('Content-Length: ' . filesize($zipFile));
    header('Pragma: no-cache');
    readfile($zipFile);
    unlink($zipFile);
    exit;
}

// Fallback
header('Location: ../index.php?page=semester');
exit;