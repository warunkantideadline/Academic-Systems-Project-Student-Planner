<?php
// views/rekap/index.php
require_once 'config/database.php';

$semesters   = readData('semester');
$mataKuliahs = readData('mata_kuliah');

// Hitung rekap per semester
$rekapSemester = [];
foreach ($semesters as $sem) {
    $mkDiSemester = findWhere('mata_kuliah', 'semester_id', $sem['id']);
    $totalSks     = 0;
    $totalBobot   = 0;
    $mkRekap      = [];

    foreach ($mkDiSemester as $mk) {
        $rekap      = hitungNilaiAkhir($mk['id']);
        $sks        = (int)$mk['sks'];
        // Gunakan angka_mutu yang sudah dihitung di hitungNilaiAkhir()
        $angkaMutu  = $rekap['angka_mutu'];

        $totalSks   += $sks;
        $totalBobot += ($angkaMutu * $sks);

        $mkRekap[] = [
            'mk'         => $mk,
            'rekap'      => $rekap,
            'angka_mutu' => $angkaMutu,
        ];
    }

    $ips = ($totalSks > 0) ? round($totalBobot / $totalSks, 2) : 0;

    $rekapSemester[] = [
        'semester'   => $sem,
        'mk_rekap'   => $mkRekap,
        'total_sks'  => $totalSks,
        'total_bobot'=> $totalBobot,
        'ips'        => $ips,
    ];
}

// Hitung IPK kumulatif (semua semester)
$totalSksKumulatif   = array_sum(array_column($rekapSemester, 'total_sks'));
$totalBobotKumulatif = array_sum(array_column($rekapSemester, 'total_bobot'));
$ipk = ($totalSksKumulatif > 0) ? round($totalBobotKumulatif / $totalSksKumulatif, 2) : 0;

// Predikat IPK
$predikat = 'Tidak Memenuhi';
if ($ipk >= 3.51)      $predikat = 'Cumlaude';
elseif ($ipk >= 3.01)  $predikat = 'Sangat Memuaskan';
elseif ($ipk >= 2.76)  $predikat = 'Memuaskan';
elseif ($ipk >= 2.00)  $predikat = 'Cukup';

$breadcrumbs = [
    ['label' => 'Home',         'url' => 'index.php'],
    ['label' => 'Rekap Nilai',  'url' => '#'],
];

require_once 'views/layout/header.php';
?>

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-bar-chart-fill text-primary me-2"></i>Rekap Nilai Akademik
        </h4>
        <p class="text-muted mb-0 small">Ringkasan seluruh nilai mata kuliah, IPS per semester, dan IPK kumulatif.</p>
    </div>
    <a href="index.php?page=semester" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<!-- KARTU IPK KUMULATIF -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 text-white shadow h-100"
             style="background: linear-gradient(135deg, #0d6efd, #6610f2); border-radius: 16px;">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <p class="mb-1 opacity-75 small text-uppercase fw-semibold">IPK Kumulatif</p>
                    <div class="display-3 fw-bold"><?= number_format($ipk, 2) ?></div>
                    <span class="badge bg-white text-primary fs-6 mt-1 px-3 py-2"><?= $predikat ?></span>
                </div>
                <div class="mt-3 opacity-75 small">
                    <i class="bi bi-info-circle me-1"></i>Skala 4.00
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm h-100 text-center p-3">
            <div class="fs-1 fw-bold text-primary"><?= count($semesters) ?></div>
            <div class="text-muted small">Total Semester</div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm h-100 text-center p-3">
            <div class="fs-1 fw-bold text-success"><?= count($mataKuliahs) ?></div>
            <div class="text-muted small">Total Mata Kuliah</div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm h-100 text-center p-3">
            <div class="fs-1 fw-bold text-warning"><?= $totalSksKumulatif ?></div>
            <div class="text-muted small">Total SKS</div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm h-100 text-center p-3">
            <?php
                $allGrades = [];
                foreach ($rekapSemester as $rs) {
                    foreach ($rs['mk_rekap'] as $mkr) {
                        $allGrades[] = $mkr['rekap']['grade'];
                    }
                }
                $gradeACounts = count(array_filter($allGrades, fn($g) => $g === 'A'));
            ?>
            <div class="fs-1 fw-bold text-info"><?= $gradeACounts ?></div>
            <div class="text-muted small">Mata Kuliah A</div>
        </div>
    </div>
</div>

<!-- REKAP PER SEMESTER -->
<?php if (empty($rekapSemester)): ?>
    <div class="text-center py-5 text-muted">
        <i class="bi bi-inbox display-1"></i>
        <p class="mt-3">Belum ada data semester.</p>
    </div>
<?php else: ?>
    <?php foreach ($rekapSemester as $rs): ?>
    <div class="card border-0 shadow-sm mb-4">
        <!-- Header Semester -->
        <div class="card-header border-0 py-3 px-4"
             style="background: linear-gradient(135deg, #e8f0fe, #f0f7ff); border-radius: 12px 12px 0 0 !important;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-0 text-primary">
                        <i class="bi bi-journal-bookmark-fill me-2"></i>
                        <?= htmlspecialchars($rs['semester']['nama']) ?>
                    </h5>
                    <small class="text-muted">
                        <?= htmlspecialchars($rs['semester']['tahun_akademik']) ?>
                        &bull; <?= count($rs['mk_rekap']) ?> Mata Kuliah
                        &bull; <?= $rs['total_sks'] ?> SKS
                    </small>
                </div>
                <div class="text-end">
                    <div class="text-muted small fw-semibold">IPS Semester</div>
                    <div class="fs-3 fw-bold text-primary"><?= number_format($rs['ips'], 2) ?></div>
                </div>
            </div>
        </div>

        <!-- Tabel Mata Kuliah -->
        <div class="card-body p-0">
            <?php if (empty($rs['mk_rekap'])): ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-file-earmark-x me-1"></i>Belum ada mata kuliah di semester ini.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">#</th>
                                <th>Mata Kuliah</th>
                                <th class="text-center">SKS</th>
                                <th class="text-center">Absensi<br><small class="text-muted fw-normal">(10%)</small></th>
                                <th class="text-center">Tugas<br><small class="text-muted fw-normal">(20%)</small></th>
                                <th class="text-center">UTS<br><small class="text-muted fw-normal">(30%)</small></th>
                                <th class="text-center">UAS<br><small class="text-muted fw-normal">(40%)</small></th>
                                <th class="text-center">Nilai Akhir</th>
                                <th class="text-center">Grade</th>
                                <th class="text-center pe-4">Angka Mutu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rs['mk_rekap'] as $i => $mkr): ?>
                            <?php
                                $rekap  = $mkr['rekap'];
                                $mk     = $mkr['mk'];
                                $grade  = $rekap['grade'];
                                $gradeColorMap = [
                                    'A' => 'success',
                                    'B' => 'primary',
                                    'C' => 'warning',
                                    'D' => 'orange',
                                    'E' => 'danger',
                                ];
                                $gradeColor = $gradeColorMap[$grade] ?? 'secondary';
                            ?>
                            <tr>
                                <td class="ps-4 text-muted"><?= $i + 1 ?></td>
                                <td>
                                    <a href="index.php?page=matakuliah&action=detail&id=<?= $mk['id'] ?>&semester_id=<?= $rs['semester']['id'] ?>&tab=rekap"
                                       class="fw-semibold text-decoration-none text-dark">
                                        <?= htmlspecialchars($mk['nama']) ?>
                                    </a>
                                    <div class="text-muted small">
                                        <i class="bi bi-person me-1"></i><?= htmlspecialchars($mk['nama_dosen']) ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary"><?= $mk['sks'] ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="text-info fw-semibold">
                                        <?= $rekap['absensi']['nilai_absensi'] ?>
                                    </span>
                                    <div class="text-muted" style="font-size:11px;">
                                        <?= $rekap['absensi']['jumlah_hadir'] ?>/<?= $rekap['absensi']['total_pertemuan'] ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="text-warning fw-semibold">
                                        <?= $rekap['tugas']['nilai_tugas'] ?>
                                    </span>
                                    <div class="text-muted" style="font-size:11px;">
                                        avg: <?= $rekap['tugas']['rata_rata_tugas'] ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="text-primary fw-semibold">
                                        <?= $rekap['ujian']['nilai_uts'] ?>
                                    </span>
                                    <div class="text-muted" style="font-size:11px;">
                                        raw: <?= $rekap['ujian']['nilai_uts_raw'] ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="text-success fw-semibold">
                                        <?= $rekap['ujian']['nilai_uas'] ?>
                                    </span>
                                    <div class="text-muted" style="font-size:11px;">
                                        raw: <?= $rekap['ujian']['nilai_uas_raw'] ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold fs-6">
                                        <?= $rekap['nilai_akhir'] ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-grade-<?= $grade ?> px-3 py-2 fs-6">
                                        <?= $grade ?>
                                    </span>
                                </td>
                                <td class="text-center pe-4 fw-bold text-primary">
                                    <?= number_format($mkr['angka_mutu'], 2) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <!-- FOOTER: Total SKS & IPS -->
                        <tfoot class="table-light fw-semibold">
                            <tr>
                                <td colspan="2" class="ps-4">Total Semester</td>
                                <td class="text-center"><?= $rs['total_sks'] ?></td>
                                <td colspan="6"></td>
                                <td class="text-center pe-4 text-primary">
                                    IPS: <?= number_format($rs['ips'], 2) ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Progress Bar IPS -->
                <div class="px-4 py-3 border-top">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>IPS Semester: <strong class="text-primary"><?= number_format($rs['ips'], 2) ?></strong></span>
                        <span><?= number_format(($rs['ips'] / 4) * 100, 1) ?>% dari 4.00</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <?php
                            $ipsColor = 'danger';
                            if ($rs['ips'] >= 3.51)     $ipsColor = 'success';
                            elseif ($rs['ips'] >= 3.01) $ipsColor = 'primary';
                            elseif ($rs['ips'] >= 2.76) $ipsColor = 'info';
                            elseif ($rs['ips'] >= 2.00) $ipsColor = 'warning';
                        ?>
                        <div class="progress-bar bg-<?= $ipsColor ?>"
                             style="width: <?= ($rs['ips'] / 4) * 100 ?>%">
                        </div>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- KARTU DISTRIBUSI GRADE -->
    <?php if (!empty($allGrades)): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold border-0 pt-4 pb-0 px-4">
            <i class="bi bi-pie-chart-fill text-primary me-2"></i>Distribusi Grade Keseluruhan
        </div>
        <div class="card-body px-4 pb-4">
            <?php
                $gradeCounts = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0];
                foreach ($allGrades as $g) {
                    if (isset($gradeCounts[$g])) $gradeCounts[$g]++;
                }
                $totalGrades = count($allGrades);
                $gradeDisplayMap = [
                    'A' => ['color' => 'success', 'label' => 'A (≥85)'],
                    'B' => ['color' => 'primary', 'label' => 'B (75–84)'],
                    'C' => ['color' => 'warning', 'label' => 'C (65–74)'],
                    'D' => ['color' => 'orange',  'label' => 'D (55–64)'],
                    'E' => ['color' => 'danger',  'label' => 'E (<55)'],
                ];
            ?>
            <div class="row g-3 mt-1">
                <?php foreach ($gradeCounts as $grade => $count): ?>
                <?php $pct = $totalGrades > 0 ? round(($count / $totalGrades) * 100, 1) : 0; ?>
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-1 small">
                        <span class="fw-semibold">
                            Grade <?= $gradeDisplayMap[$grade]['label'] ?>
                        </span>
                        <span class="text-muted">
                            <?= $count ?> MK (<?= $pct ?>%)
                        </span>
                    </div>
                    <div class="progress" style="height: 18px; border-radius: 8px;">
                        <div class="progress-bar bg-<?= $gradeDisplayMap[$grade]['color'] ?>"
                             style="width: <?= $pct ?>%; border-radius: 8px;"
                             title="<?= $count ?> mata kuliah">
                            <?= $pct > 10 ? $pct . '%' : '' ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- TABEL KONVERSI NILAI REFERENSI -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold border-0 pt-4 pb-0 px-4">
            <i class="bi bi-table text-primary me-2"></i>Tabel Referensi Konversi Nilai & IPK
        </div>
        <div class="card-body px-4 pb-4">
            <div class="row g-4">
                <!-- Tabel Konversi Grade -->
                <div class="col-md-6">
                    <h6 class="text-muted fw-semibold small text-uppercase mb-2">Konversi Skor → Grade → Angka Mutu</h6>
                    <table class="table table-sm table-bordered text-center align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Skor</th>
                                <th>Huruf</th>
                                <th>Angka Mutu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $konversiTabel = [
                                ['skor' => '≥ 85',    'huruf' => 'A',  'nilai' => '4.00'],
                                ['skor' => '80 – 84', 'huruf' => 'A-', 'nilai' => '3.70'],
                                ['skor' => '75 – 79', 'huruf' => 'B+', 'nilai' => '3.30'],
                                ['skor' => '70 – 74', 'huruf' => 'B',  'nilai' => '3.00'],
                                ['skor' => '65 – 69', 'huruf' => 'B-', 'nilai' => '2.70'],
                                ['skor' => '60 – 64', 'huruf' => 'C+', 'nilai' => '2.30'],
                                ['skor' => '55 – 59', 'huruf' => 'C',  'nilai' => '2.00'],
                                ['skor' => '50 – 54', 'huruf' => 'C-', 'nilai' => '1.70'],
                                ['skor' => '40 – 50', 'huruf' => 'D',  'nilai' => '1.00'],
                                ['skor' => '< 40',    'huruf' => 'E',  'nilai' => '0.00'],
                            ];
                            foreach ($konversiTabel as $row):
                                $isActive = isset($rekap) && $rekap['grade'] === $row['huruf'];
                            ?>
                            <tr>
                                <td><?= $row['skor'] ?></td>
                                <td>
                                    <span class="badge badge-grade-<?= str_replace(['+','-'], ['plus','min'], $row['huruf']) ?>
                                                badge-grade-base">
                                        <?= $row['huruf'] ?>
                                    </span>
                                </td>
                                <td class="fw-semibold"><?= $row['nilai'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Tabel Predikat IPK -->
                <div class="col-md-6">
                    <h6 class="text-muted fw-semibold small text-uppercase mb-2">Predikat IPK</h6>
                    <table class="table table-sm table-bordered text-center">
                        <thead class="table-light">
                            <tr><th>IPK</th><th>Predikat</th></tr>
                        </thead>
                        <tbody>
                            <tr class="<?= $ipk >= 3.51 ? 'table-success fw-bold' : '' ?>">
                                <td>3.51 – 4.00</td><td>🎓 Cumlaude</td>
                            </tr>
                            <tr class="<?= ($ipk >= 3.01 && $ipk < 3.51) ? 'table-primary fw-bold' : '' ?>">
                                <td>3.01 – 3.50</td><td>Sangat Memuaskan</td>
                            </tr>
                            <tr class="<?= ($ipk >= 2.76 && $ipk < 3.01) ? 'table-info fw-bold' : '' ?>">
                                <td>2.76 – 3.00</td><td>Memuaskan</td>
                            </tr>
                            <tr class="<?= ($ipk >= 2.00 && $ipk < 2.76) ? 'table-warning fw-bold' : '' ?>">
                                <td>2.00 – 2.75</td><td>Cukup</td>
                            </tr>
                            <tr class="<?= $ipk < 2.00 ? 'table-danger fw-bold' : '' ?>">
                                <td>&lt; 2.00</td><td>Tidak Memenuhi</td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="text-muted small mt-3">
                        <strong>Rumus IPS/IPK:</strong><br>
                        \(\text{IPS} = \dfrac{\sum(\text{Angka Mutu} \times \text{SKS})}{\sum \text{SKS}}\)
                    </p>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php require_once 'views/layout/footer.php'; ?>