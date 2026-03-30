<?php
// config/database.php

define('BASE_DATA_PATH', __DIR__ . '/../data/');
define('DATA_PATH', BASE_DATA_PATH);

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'];
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
define('BASE_URL', $protocol . '://' . $host . $basePath . '/');

// ── Tentukan path data berdasarkan user yang login ──────────
function getUserDataPath(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $username = $_SESSION['user_username'] ?? null;
    if (!$username) return BASE_DATA_PATH;
    $path = BASE_DATA_PATH . $username . '/';
    // Buat folder jika belum ada
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        // Inisialisasi file JSON kosong
        $files = ['semester','mata_kuliah','absensi','tugas','nilai_ujian'];
        foreach ($files as $f) {
            if (!file_exists($path . $f . '.json')) {
                file_put_contents($path . $f . '.json', '[]');
            }
        }
    }
    return $path;
}

function getDataFiles(): array {
    $path = getUserDataPath();
    return [
        'semester'    => $path . 'semester.json',
        'mata_kuliah' => $path . 'mata_kuliah.json',
        'absensi'     => $path . 'absensi.json',
        'tugas'       => $path . 'tugas.json',
        'nilai_ujian' => $path . 'nilai_ujian.json',
        // users selalu di root data/
        'users'       => BASE_DATA_PATH . 'users.json',
    ];
}

// ============================================================
// FUNGSI DASAR BACA / TULIS JSON
// ============================================================

function readData(string $key): array {
    $dataFiles = getDataFiles();
    $path = $dataFiles[$key] ?? null;
    if (!$path || !file_exists($path)) return [];
    $content = file_get_contents($path);
    $decoded = json_decode($content, true);
    return is_array($decoded) ? $decoded : [];
}

function writeData(string $key, array $data): bool {
    $dataFiles = getDataFiles();
    $path = $dataFiles[$key] ?? null;
    if (!$path) return false;
    $json = json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($path, $json) !== false;
}

// ============================================================
// FUNGSI GENERATOR ID UNIK
// ============================================================

function generateId(string $prefix = ''): string {
    return strtoupper($prefix) . '_' . substr(uniqid(), -8) . rand(10, 99);
}

// ============================================================
// FUNGSI HELPER PENCARIAN DATA
// ============================================================

function findById(string $key, string $id): ?array {
    $data = readData($key);
    foreach ($data as $item) {
        if (isset($item['id']) && $item['id'] === $id) return $item;
    }
    return null;
}

function findWhere(string $key, string $field, $value): array {
    $data = readData($key);
    return array_values(array_filter($data, function ($item) use ($field, $value) {
        return isset($item[$field]) && $item[$field] === $value;
    }));
}

function deleteById(string $key, string $id): bool {
    $data     = readData($key);
    $filtered = array_values(array_filter($data, fn($item) => $item['id'] !== $id));
    return writeData($key, $filtered);
}

function deleteWhere(string $key, string $field, $value): bool {
    $data     = readData($key);
    $filtered = array_values(array_filter($data, function ($item) use ($field, $value) {
        return !isset($item[$field]) || $item[$field] !== $value;
    }));
    return writeData($key, $filtered);
}

// ============================================================
// FUNGSI KONVERSI GRADE & ANGKA MUTU
// ============================================================

function skorToGrade(float $skor): string {
    if ($skor >= 85)      return 'A';
    elseif ($skor >= 80)  return 'A-';
    elseif ($skor >= 75)  return 'B+';
    elseif ($skor >= 70)  return 'B';
    elseif ($skor >= 65)  return 'B-';
    elseif ($skor >= 60)  return 'C+';
    elseif ($skor >= 55)  return 'C';
    elseif ($skor >= 50)  return 'C-';
    elseif ($skor >= 40)  return 'D';
    else                  return 'E';
}

function gradeToAngkaMutu(string $grade): float {
    $tabel = [
        'A'  => 4.00, 'A-' => 3.70,
        'B+' => 3.30, 'B'  => 3.00, 'B-' => 2.70,
        'C+' => 2.30, 'C'  => 2.00, 'C-' => 1.70,
        'D'  => 1.00, 'E'  => 0.00,
    ];
    return $tabel[$grade] ?? 0.00;
}

function gradeToColor(string $grade): string {
    $map = [
        'A'  => 'success', 'A-' => 'success',
        'B+' => 'primary', 'B'  => 'primary', 'B-' => 'primary',
        'C+' => 'warning', 'C'  => 'warning', 'C-' => 'warning',
        'D'  => 'orange',  'E'  => 'danger',
    ];
    return $map[$grade] ?? 'secondary';
}

function gradeToBadgeClass(string $grade): string {
    $map = [
        'A'  => 'A',    'A-' => 'Amin',
        'B+' => 'Bplus','B'  => 'B',   'B-' => 'Bmin',
        'C+' => 'Cplus','C'  => 'C',   'C-' => 'Cmin',
        'D'  => 'D',    'E'  => 'E',
    ];
    return $map[$grade] ?? 'E';
}

// ============================================================
// FUNGSI KALKULASI NILAI AKHIR
// ============================================================

function hitungNilaiAbsensi(string $mataKuliahId): array {
    $absensi     = findWhere('absensi', 'mata_kuliah_id', $mataKuliahId);
    $total       = count($absensi);
    $jumlahHadir = count(array_filter($absensi, fn($a) => $a['status'] === 'hadir'));
    $nilaiAbsensi = ($total > 0) ? ($jumlahHadir / $total) * 10 : 0;
    return [
        'jumlah_hadir'    => $jumlahHadir,
        'total_pertemuan' => $total,
        'nilai_absensi'   => round($nilaiAbsensi, 2),
    ];
}

function hitungNilaiTugas(string $mataKuliahId): array {
    $tugas      = findWhere('tugas', 'mata_kuliah_id', $mataKuliahId);
    $jumlah     = count($tugas);
    $totalNilai = array_sum(array_column($tugas, 'nilai'));
    $rataRata   = ($jumlah > 0) ? $totalNilai / $jumlah : 0;
    $nilaiTugas = $rataRata * 0.20;
    return [
        'jumlah_tugas'    => $jumlah,
        'rata_rata_tugas' => round($rataRata, 2),
        'nilai_tugas'     => round($nilaiTugas, 2),
    ];
}

function hitungNilaiUjian(string $mataKuliahId): array {
    $ujian  = findWhere('nilai_ujian', 'mata_kuliah_id', $mataKuliahId);
    $data   = !empty($ujian) ? $ujian[0] : null;
    $utsRaw = $data['nilai_uts'] ?? 0;
    $uasRaw = $data['nilai_uas'] ?? 0;
    return [
        'nilai_uts_raw' => $utsRaw,
        'nilai_uas_raw' => $uasRaw,
        'nilai_uts'     => round($utsRaw * 0.30, 2),
        'nilai_uas'     => round($uasRaw * 0.40, 2),
    ];
}

function hitungNilaiAkhir(string $mataKuliahId): array {
    // Cek override dulu
    $mk = findById('mata_kuliah', $mataKuliahId);
    if ($mk && isset($mk['nilai_akhir_override']) && $mk['nilai_akhir_override'] !== '') {
        $nilaiAkhir = (float)$mk['nilai_akhir_override'];
        $grade      = skorToGrade($nilaiAkhir);
        $angkaMutu  = gradeToAngkaMutu($grade);
        return [
            'absensi'     => ['jumlah_hadir'=>0,'total_pertemuan'=>0,'nilai_absensi'=>0],
            'tugas'       => ['jumlah_tugas'=>0,'rata_rata_tugas'=>0,'nilai_tugas'=>0],
            'ujian'       => ['nilai_uts_raw'=>0,'nilai_uas_raw'=>0,'nilai_uts'=>0,'nilai_uas'=>0],
            'nilai_akhir' => $nilaiAkhir,
            'grade'       => $grade,
            'angka_mutu'  => $angkaMutu,
            'badge_class' => gradeToBadgeClass($grade),
            'color'       => gradeToColor($grade),
            'is_override' => true,
        ];
    }

    // Hitung normal
    $absensi    = hitungNilaiAbsensi($mataKuliahId);
    $tugas      = hitungNilaiTugas($mataKuliahId);
    $ujian      = hitungNilaiUjian($mataKuliahId);
    $nilaiAkhir = round(
        $absensi['nilai_absensi'] +
        $tugas['nilai_tugas']     +
        $ujian['nilai_uts']       +
        $ujian['nilai_uas'], 2
    );
    $grade     = skorToGrade($nilaiAkhir);
    $angkaMutu = gradeToAngkaMutu($grade);

    return [
        'absensi'     => $absensi,
        'tugas'       => $tugas,
        'ujian'       => $ujian,
        'nilai_akhir' => $nilaiAkhir,
        'grade'       => $grade,
        'angka_mutu'  => $angkaMutu,
        'badge_class' => gradeToBadgeClass($grade),
        'color'       => gradeToColor($grade),
        'is_override' => false,
    ];
}

// ============================================================
// FUNGSI HITUNG IPS & IPK
// ============================================================

function hitungIPS(string $semesterId): array {
    $mataKuliahs = findWhere('mata_kuliah', 'semester_id', $semesterId);
    $totalSks    = 0;
    $totalBobot  = 0;
    $detail      = [];

    foreach ($mataKuliahs as $mk) {
        $rekap     = hitungNilaiAkhir($mk['id']);
        $sks       = (int)($mk['sks'] ?? 0);
        $angkaMutu = $rekap['angka_mutu'];
        $bobot     = $angkaMutu * $sks;
        $totalSks   += $sks;
        $totalBobot += $bobot;
        $detail[] = [
            'mk'         => $mk,
            'rekap'      => $rekap,
            'sks'        => $sks,
            'angka_mutu' => $angkaMutu,
            'bobot'      => $bobot,
        ];
    }

    $ips = ($totalSks > 0) ? round($totalBobot / $totalSks, 2) : 0.00;
    return [
        'ips'         => $ips,
        'total_sks'   => $totalSks,
        'total_bobot' => round($totalBobot, 2),
        'jumlah_mk'   => count($mataKuliahs),
        'detail'      => $detail,
    ];
}

function hitungIPK(): array {
    $semesters   = readData('semester');
    $totalSks    = 0;
    $totalBobot  = 0;
    $perSemester = [];

    foreach ($semesters as $sem) {
        $ipsData     = hitungIPS($sem['id']);
        $totalSks   += $ipsData['total_sks'];
        $totalBobot += $ipsData['total_bobot'];
        $perSemester[] = [
            'semester'    => $sem,
            'ips'         => $ipsData['ips'],
            'total_sks'   => $ipsData['total_sks'],
            'total_bobot' => $ipsData['total_bobot'],
            'jumlah_mk'   => $ipsData['jumlah_mk'],
        ];
    }

    $ipk = ($totalSks > 0) ? round($totalBobot / $totalSks, 2) : 0.00;

    $predikat      = 'Tidak Memenuhi';
    $predikatColor = 'danger';
    if ($ipk >= 3.51)      { $predikat = 'Cumlaude';          $predikatColor = 'success'; }
    elseif ($ipk >= 3.01)  { $predikat = 'Sangat Memuaskan';  $predikatColor = 'primary'; }
    elseif ($ipk >= 2.76)  { $predikat = 'Memuaskan';         $predikatColor = 'info'; }
    elseif ($ipk >= 2.00)  { $predikat = 'Cukup';             $predikatColor = 'warning'; }

    return [
        'ipk'            => $ipk,
        'total_sks'      => $totalSks,
        'total_bobot'    => round($totalBobot, 2),
        'predikat'       => $predikat,
        'predikat_color' => $predikatColor,
        'per_semester'   => $perSemester,
    ];
}

// ============================================================
// FUNGSI FLASH MESSAGE
// ============================================================

function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ============================================================
// FUNGSI REDIRECT & SANITASI
// ============================================================

function redirect(string $url): void {
    header("Location: $url");
    exit();
}

function sanitize($input): string {
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}