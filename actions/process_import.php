<?php
// actions/process_import.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';

// Validasi method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('danger', 'Metode request tidak valid.');
    header('Location: ../index.php?page=semester');
    exit;
}

// Validasi file upload
if (empty($_FILES['file']['tmp_name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'File terlalu besar (batas php.ini).',
        UPLOAD_ERR_FORM_SIZE  => 'File terlalu besar (batas form).',
        UPLOAD_ERR_PARTIAL    => 'File hanya terupload sebagian.',
        UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang dipilih.',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan.',
        UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
    ];
    $errCode = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
    setFlash('danger', $uploadErrors[$errCode] ?? 'Upload gagal.');
    header('Location: ../index.php?page=semester');
    exit;
}

$type     = $_POST['type'] ?? '';
$file     = $_FILES['file']['tmp_name'];
$origName = $_FILES['file']['name'];
$ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

if ($ext !== 'csv') {
    setFlash('danger', 'Hanya file CSV yang diperbolehkan.');
    header('Location: ../index.php?page=semester');
    exit;
}

// Baca CSV
$rows = [];
if (($handle = fopen($file, 'r')) !== false) {
    $header = null;
    while (($line = fgetcsv($handle, 2000, ',')) !== false) {
        if (!$header) {
            $line[0] = preg_replace('/^\xEF\xBB\xBF/', '', $line[0]);
            $header  = array_map('trim', $line);
        } else {
            if (count(array_filter($line, fn($v) => trim($v) !== '')) === 0) continue;
            while (count($line) < count($header)) $line[] = '';
            $rows[] = array_combine($header, array_map('trim', $line));
        }
    }
    fclose($handle);
}

if (empty($rows)) {
    setFlash('danger', 'File CSV kosong atau format tidak valid.');
    header('Location: ../index.php?page=semester');
    exit;
}

// ── IMPORT SEMESTER ────────────────────────────────────────
if ($type === 'semester') {
    $required = ['nama', 'tahun_akademik', 'status'];
    foreach ($required as $col) {
        if (!array_key_exists($col, $rows[0])) {
            setFlash('danger', "Kolom wajib tidak ditemukan: <b>$col</b>. Pastikan header CSV benar.");
            header('Location: ../index.php?page=semester');
            exit;
        }
    }

    $existing  = readData('semester');
    $namaExist = array_column($existing, 'nama');
    $added     = 0;
    $skipped   = 0;

    foreach ($rows as $row) {
        $nama = trim($row['nama'] ?? '');
        if (empty($nama)) { $skipped++; continue; }

        if (in_array($nama, $namaExist)) { $skipped++; continue; }

        $status = strtolower(trim($row['status'] ?? 'selesai'));
        if (!in_array($status, ['aktif', 'selesai'])) $status = 'selesai';

        $existing[]  = [
            'id'             => generateId(),
            'nama'           => $nama,
            'tahun_akademik' => trim($row['tahun_akademik'] ?? ''),
            'status'         => $status,
        ];
        $namaExist[] = $nama;
        $added++;
    }

    writeData('semester', $existing);
    setFlash('success', "Import semester berhasil: <b>$added</b> ditambahkan, <b>$skipped</b> dilewati.");
    header('Location: ../index.php?page=semester');
    exit;
}

// ── IMPORT MATA KULIAH ─────────────────────────────────────
if ($type === 'matakuliah') {
    $required = ['semester_nama', 'nama', 'sks'];
    foreach ($required as $col) {
        if (!array_key_exists($col, $rows[0])) {
            setFlash('danger', "Kolom wajib tidak ditemukan: <b>$col</b>. Pastikan header CSV benar.");
            header('Location: ../index.php?page=semester');
            exit;
        }
    }

    $semesters   = readData('semester');
    $semesterMap = [];
    foreach ($semesters as $s) {
        $semesterMap[strtolower(trim($s['nama']))] = $s['id'];
    }

    $mataKuliahs = readData('mata_kuliah');
    $added   = 0;
    $skipped = 0;
    $errors  = [];

    foreach ($rows as $i => $row) {
        $semNama = strtolower(trim($row['semester_nama'] ?? ''));
        $mkNama  = trim($row['nama'] ?? '');

        if (empty($mkNama)) { $skipped++; continue; }

        if (!isset($semesterMap[$semNama])) {
            $errors[] = "Baris " . ($i + 2) . ": Semester \"" . htmlspecialchars($row['semester_nama'] ?? '') . "\" tidak ditemukan.";
            $skipped++;
            continue;
        }

        $sks = max(1, min(6, (int)($row['sks'] ?? 2)));

        $mataKuliahs[] = [
            'id'          => generateId(),
            'semester_id' => $semesterMap[$semNama],
            'nama'        => $mkNama,
            'sks'         => $sks,
            'hari'        => trim($row['hari']  ?? ''),
            'jam'         => trim($row['jam']   ?? ''),
            'dosen'       => trim($row['dosen'] ?? ''),
        ];
        $added++;
    }

    writeData('mata_kuliah', $mataKuliahs);

    $msg = "Import mata kuliah berhasil: <b>$added</b> ditambahkan, <b>$skipped</b> dilewati.";
    if (!empty($errors)) {
        $msg .= '<br><small>' . implode('<br>', $errors) . '</small>';
    }
    setFlash(empty($errors) ? 'success' : 'warning', $msg);
    header('Location: ../index.php?page=semester');
    exit;
}

setFlash('danger', 'Tipe import tidak dikenali.');
header('Location: ../index.php?page=semester');
exit;