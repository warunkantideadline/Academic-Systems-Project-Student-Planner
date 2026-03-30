<?php
// views/home/index.php
require_once 'config/database.php';

$dataIPK   = hitungIPK();
$semesters = readData('semester');
$semuaMK   = readData('mata_kuliah');

$breadcrumbs = [];

// Hitung distribusi grade semua MK
$gradeList = ['A','A-','B+','B','B-','C+','C','C-','D','E'];
$gradeDist = array_fill_keys($gradeList, 0);
$totalMKDinilai = 0;

foreach ($semuaMK as $mk) {
    $rekap = hitungNilaiAkhir($mk['id']);
    if ($rekap['nilai_akhir'] > 0) {
        $g = $rekap['grade'];
        if (isset($gradeDist[$g])) $gradeDist[$g]++;
        $totalMKDinilai++;
    }
}

// Warna per grade
$gradeColors = [
    'A'  => ['bg' => '#10b981', 'badge' => 'A'],
    'A-' => ['bg' => '#34d399', 'badge' => 'Amin'],
    'B+' => ['bg' => '#6366f1', 'badge' => 'Bplus'],
    'B'  => ['bg' => '#4f46e5', 'badge' => 'B'],
    'B-' => ['bg' => '#818cf8', 'badge' => 'Bmin'],
    'C+' => ['bg' => '#f59e0b', 'badge' => 'Cplus'],
    'C'  => ['bg' => '#fbbf24', 'badge' => 'C'],
    'C-' => ['bg' => '#fcd34d', 'badge' => 'Cmin'],
    'D'  => ['bg' => '#f97316', 'badge' => 'D'],
    'E'  => ['bg' => '#ef4444', 'badge' => 'E'],
];

require_once 'views/layout/header.php';
?>

<!-- ============================================================ -->
<!-- HERO IPK -->
<!-- ============================================================ -->
<div class="card border-0 mb-4 ipk-hero-card">
    <div class="card-body p-4 p-md-5">
        <div class="row align-items-center g-4">
            <div class="col-md-auto text-center">
                <div class="ipk-hero-circle">
                    <div class="ipk-hero-number"><?= number_format($dataIPK['ipk'], 2) ?></div>
                    <div class="ipk-hero-label">IPK</div>
                </div>
            </div>
            <div class="col-md">
                <h3 class="fw-bold text-white mb-1">Indeks Prestasi Kumulatif</h3>
                <p class="text-white opacity-75 mb-3">
                    Akumulasi seluruh nilai dari <strong><?= count($semesters) ?> semester</strong>
                    dan <strong><?= count($semuaMK) ?> mata kuliah</strong>
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="ipk-predikat-badge">🎓 <?= $dataIPK['predikat'] ?></span>
                    <span class="ipk-info-badge">
                        <i class="bi bi-award me-1"></i><?= $dataIPK['total_sks'] ?> Total SKS
                    </span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex justify-content-between text-white small mb-1">
                    <span>0.00</span>
                    <span class="fw-bold">Target 4.00</span>
                </div>
                <div class="progress" style="height: 12px; border-radius: 50px; background: rgba(255,255,255,0.2);">
                    <div class="progress-bar bg-white"
                         style="width: <?= ($dataIPK['ipk'] / 4) * 100 ?>%; border-radius: 50px;">
                    </div>
                </div>
                <div class="text-white small mt-1 text-end">
                    <?= round(($dataIPK['ipk'] / 4) * 100, 1) ?>% dari maksimal
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- STAT CARDS -->
<!-- ============================================================ -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 stat-home-card"
             style="background: linear-gradient(135deg,#4f46e5,#6366f1);">
            <div class="card-body p-3 text-center">
                <i class="bi bi-collection fs-2 mb-1"></i>
                <div class="fw-bold fs-3"><?= count($semesters) ?></div>
                <div class="small opacity-75">Semester</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 stat-home-card"
             style="background: linear-gradient(135deg,#06b6d4,#0891b2);">
            <div class="card-body p-3 text-center">
                <i class="bi bi-book fs-2 mb-1"></i>
                <div class="fw-bold fs-3"><?= count($semuaMK) ?></div>
                <div class="small opacity-75">Mata Kuliah</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 stat-home-card"
             style="background: linear-gradient(135deg,#10b981,#059669);">
            <div class="card-body p-3 text-center">
                <i class="bi bi-award fs-2 mb-1"></i>
                <div class="fw-bold fs-3"><?= $dataIPK['total_sks'] ?></div>
                <div class="small opacity-75">Total SKS</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 stat-home-card"
             style="background: linear-gradient(135deg,#f59e0b,#d97706);">
            <div class="card-body p-3 text-center">
                <i class="bi bi-graph-up fs-2 mb-1"></i>
                <div class="fw-bold fs-3"><?= number_format($dataIPK['ipk'], 2) ?></div>
                <div class="small opacity-75">IPK</div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- TABEL IPS + DISTRIBUSI GRADE — 2 KOLOM -->
<!-- ============================================================ -->
<div class="row g-4 mb-4">

    <!-- TABEL IPS PER SEMESTER -->
    <?php if (!empty($dataIPK['per_semester'])): ?>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-bold border-0 pt-4 pb-0 px-4">
                <i class="bi bi-table text-primary me-2"></i>Riwayat IPS per Semester
            </div>
            <div class="card-body px-4 pb-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Semester</th>
                                <th>Tahun</th>
                                <th class="text-center">MK</th>
                                <th class="text-center">SKS</th>
                                <th class="text-center">IPS</th>
                                <th>Progress</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dataIPK['per_semester'] as $row):
                                $ips      = $row['ips'];
                                $ipsColor = 'danger';
                                $ipsLabel = 'Tidak Memenuhi';
                                if ($ips >= 3.51)     { $ipsColor = 'success'; $ipsLabel = 'Cumlaude'; }
                                elseif ($ips >= 3.01) { $ipsColor = 'primary'; $ipsLabel = 'Sangat Memuaskan'; }
                                elseif ($ips >= 2.76) { $ipsColor = 'info';    $ipsLabel = 'Memuaskan'; }
                                elseif ($ips >= 2.00) { $ipsColor = 'warning'; $ipsLabel = 'Cukup'; }
                            ?>
                            <tr>
                                <td class="fw-semibold">
                                    <?= htmlspecialchars($row['semester']['nama']) ?>
                                </td>
                                <td class="text-muted small">
                                    <?= htmlspecialchars($row['semester']['tahun_akademik']) ?>
                                </td>
                                <td class="text-center"><?= $row['jumlah_mk'] ?></td>
                                <td class="text-center"><?= $row['total_sks'] ?></td>
                                <td class="text-center">
                                    <span class="fw-bold text-<?= $ipsColor ?> fs-6">
                                        <?= number_format($ips, 2) ?>
                                    </span>
                                </td>
                                <td style="min-width: 100px;">
                                    <div class="progress" style="height: 7px;">
                                        <div class="progress-bar bg-<?= $ipsColor ?>"
                                             style="width: <?= ($ips / 4) * 100 ?>%">
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $row['semester']['status'] === 'aktif' ? 'success' : 'secondary' ?>">
                                        <?= $row['semester']['status'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="fw-bold">Total / IPK</td>
                                <td class="text-center fw-bold"><?= $dataIPK['total_sks'] ?></td>
                                <td class="text-center">
                                    <span class="badge bg-primary px-3">
                                        <?= number_format($dataIPK['ipk'], 2) ?>
                                    </span>
                                </td>
                                <td colspan="2" class="fw-semibold text-muted small">
                                    🎓 <?= $dataIPK['predikat'] ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- DISTRIBUSI GRADE -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-bold border-0 pt-4 pb-0 px-4">
                <i class="bi bi-bar-chart-fill text-success me-2"></i>Distribusi Grade Keseluruhan
            </div>
            <div class="card-body px-4 pb-4">
                <?php if ($totalMKDinilai === 0): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-inbox display-4 text-muted"></i>
                        <p class="text-muted small mt-2">Belum ada nilai tercatat.</p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Grade</th>
                                <th>Angka Mutu</th>
                                <th class="text-center">Jumlah</th>
                                <th>Proporsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $angkaMutuMap = [
                                'A'  => '4.00', 'A-' => '3.70',
                                'B+' => '3.30', 'B'  => '3.00', 'B-' => '2.70',
                                'C+' => '2.30', 'C'  => '2.00', 'C-' => '1.70',
                                'D'  => '1.00', 'E'  => '0.00',
                            ];
                            foreach ($gradeList as $g):
                                $jumlah  = $gradeDist[$g];
                                $persen  = $totalMKDinilai > 0
                                           ? round(($jumlah / $totalMKDinilai) * 100, 1) : 0;
                                $info    = $gradeColors[$g];
                                $bgColor = $info['bg'];
                                // Teks hitam untuk grade cerah
                                $txtColor = in_array($g, ['C-', 'C']) ? '#1e293b' : '#fff';
                            ?>
                            <tr class="grade-dist-row <?= $jumlah === 0 ? 'opacity-50' : '' ?>">
                                <td>
                                    <span class="badge badge-grade-base badge-grade-<?= $info['badge'] ?>">
                                        <?= $g ?>
                                    </span>
                                </td>
                                <td class="text-muted small fw-semibold">
                                    <?= $angkaMutuMap[$g] ?>
                                </td>
                                <td class="text-center fw-bold"
                                    style="color: <?= $jumlah > 0 ? $bgColor : 'var(--text-muted)' ?>;">
                                    <?= $jumlah ?>
                                </td>
                                <td style="min-width: 90px;">
                                    <?php if ($jumlah > 0): ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 7px;">
                                            <div class="progress-bar"
                                                 style="width: <?= $persen ?>%;
                                                        background: <?= $bgColor ?> !important;
                                                        border-radius: 50px;">
                                            </div>
                                        </div>
                                        <span class="text-muted" style="font-size:0.7rem; white-space:nowrap;">
                                            <?= $persen ?>%
                                        </span>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-muted" style="font-size:0.75rem;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="fw-bold">Total Dinilai</td>
                                <td class="text-center fw-bold text-primary">
                                    <?= $totalMKDinilai ?>
                                </td>
                                <td class="text-muted small">
                                    dari <?= count($semuaMK) ?> MK
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php require_once 'views/layout/footer.php'; ?>