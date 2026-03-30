<?php
// views/matakuliah/detail.php
require_once 'config/database.php';

$mkId       = sanitize($_GET['id'] ?? '');
$semesterId = sanitize($_GET['semester_id'] ?? '');

$mk       = findById('mata_kuliah', $mkId);
$semester = findById('semester', $semesterId);

if (!$mk || !$semester) {
    setFlash('danger', 'Mata kuliah tidak ditemukan!');
    redirect('index.php?page=semester');
}

$absensiList = findWhere('absensi',     'mata_kuliah_id', $mkId);
$tugasList   = findWhere('tugas',       'mata_kuliah_id', $mkId);
$nilaiUjian  = findWhere('nilai_ujian', 'mata_kuliah_id', $mkId);
$dataUjian   = !empty($nilaiUjian) ? $nilaiUjian[0] : null;

$rekap = hitungNilaiAkhir($mkId);

usort($absensiList, fn($a, $b) => $a['pertemuan_ke'] <=> $b['pertemuan_ke']);
usort($tugasList,   fn($a, $b) => strcmp($a['deadline'], $b['deadline']));

$activeTab = $_GET['tab'] ?? 'rekap';

$totalPertemuan = count($absensiList);
$jmlHadir  = count(array_filter($absensiList, fn($a) => $a['status'] === 'hadir'));
$jmlIzin   = count(array_filter($absensiList, fn($a) => $a['status'] === 'izin'));
$jmlSakit  = count(array_filter($absensiList, fn($a) => $a['status'] === 'sakit'));
$jmlAlpha  = count(array_filter($absensiList, fn($a) => $a['status'] === 'alpha'));
$pctHadir  = $totalPertemuan > 0 ? round(($jmlHadir / $totalPertemuan) * 100, 1) : 0;

$rataRataTugas = count($tugasList) > 0
    ? round(array_sum(array_column($tugasList, 'nilai')) / count($tugasList), 2)
    : 0;

$breadcrumbs = [
    ['label' => 'Home',                              'url' => 'index.php'],
    ['label' => 'Semester',                          'url' => 'index.php?page=semester'],
    ['label' => htmlspecialchars($semester['nama']), 'url' => 'index.php?page=matakuliah&semester_id=' . $semesterId],
    ['label' => htmlspecialchars($mk['nama']),       'url' => '#'],
];

require_once 'views/layout/header.php';
?>

<!-- ============================================================ -->
<!-- INFO HEADER MATA KULIAH -->
<!-- ============================================================ -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h4 class="fw-bold mb-1"><?= htmlspecialchars($mk['nama']) ?></h4>
                <div class="d-flex flex-wrap gap-3 text-muted small">
                    <span><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($mk['nama_dosen']) ?></span>
                    <span><i class="bi bi-clock me-1"></i><?= htmlspecialchars($mk['jadwal_hari']) ?>, <?= htmlspecialchars($mk['jadwal_jam']) ?></span>
                    <span><i class="bi bi-bookmark me-1"></i><?= $mk['sks'] ?> SKS</span>
                    <span><i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($semester['nama']) ?></span>
                </div>
            </div>
            <?php if ($rekap['nilai_akhir'] > 0): ?>
            <div class="text-center">
                <div class="display-6 fw-bold" style="color: var(--<?= $rekap['color'] ?>);">
                    <?= $rekap['nilai_akhir'] ?>
                </div>
                <span class="badge <?= $rekap['badge_class'] ?> badge-grade-base px-3 py-2 fs-6">
                    Grade <?= $rekap['grade'] ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- NAVIGASI TAB -->
<!-- ============================================================ -->
<ul class="nav nav-tabs mb-0" id="detailTab">
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'rekap'   ? 'active' : '' ?>"
           href="index.php?page=matakuliah&action=detail&id=<?= $mkId ?>&semester_id=<?= $semesterId ?>&tab=rekap">
            <i class="bi bi-bar-chart me-1"></i>Rekap Nilai
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'absensi' ? 'active' : '' ?>"
           href="index.php?page=matakuliah&action=detail&id=<?= $mkId ?>&semester_id=<?= $semesterId ?>&tab=absensi">
            <i class="bi bi-person-check me-1"></i>Absensi
            <span class="badge bg-secondary ms-1"><?= count($absensiList) ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'tugas'   ? 'active' : '' ?>"
           href="index.php?page=matakuliah&action=detail&id=<?= $mkId ?>&semester_id=<?= $semesterId ?>&tab=tugas">
            <i class="bi bi-file-earmark-text me-1"></i>Tugas
            <span class="badge bg-secondary ms-1"><?= count($tugasList) ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'ujian'   ? 'active' : '' ?>"
           href="index.php?page=matakuliah&action=detail&id=<?= $mkId ?>&semester_id=<?= $semesterId ?>&tab=ujian">
            <i class="bi bi-pencil-square me-1"></i>UTS &amp; UAS
        </a>
    </li>
</ul>

<div class="card border-0 shadow-sm" style="border-radius: 0 0 12px 12px; border-top: none !important;">
    <div class="card-body p-4">

    <!-- ======================================================== -->
    <!-- TAB: REKAP NILAI -->
    <!-- ======================================================== -->
    <?php if ($activeTab === 'rekap'): ?>

        <h5 class="fw-bold mb-4">
            <i class="bi bi-bar-chart-fill text-primary me-2"></i>Rekap Nilai Akhir
        </h5>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card komponen-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-semibold"><i class="bi bi-person-check text-info me-1"></i>Absensi</span>
                            <span class="badge bg-info">Bobot 10%</span>
                        </div>
                        <div class="text-muted small mb-2">
                            Hadir: <?= $rekap['absensi']['jumlah_hadir'] ?> / <?= $rekap['absensi']['total_pertemuan'] ?> pertemuan
                        </div>
                        <div class="progress mb-2" style="height:10px;">
                            <div class="progress-bar bg-info"
                                 style="width:<?= $rekap['absensi']['total_pertemuan'] > 0 ? ($rekap['absensi']['jumlah_hadir']/$rekap['absensi']['total_pertemuan'])*100 : 0 ?>%">
                            </div>
                        </div>
                        <div class="text-end fw-bold text-info">+<?= $rekap['absensi']['nilai_absensi'] ?> poin</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card komponen-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-semibold"><i class="bi bi-file-earmark-text text-warning me-1"></i>Tugas</span>
                            <span class="badge bg-warning">Bobot 20%</span>
                        </div>
                        <div class="text-muted small mb-2">
                            <?= $rekap['tugas']['jumlah_tugas'] ?> tugas &bull; Rata-rata: <?= $rekap['tugas']['rata_rata_tugas'] ?>
                        </div>
                        <div class="progress mb-2" style="height:10px;">
                            <div class="progress-bar bg-warning"
                                 style="width:<?= $rekap['tugas']['rata_rata_tugas'] ?>%">
                            </div>
                        </div>
                        <div class="text-end fw-bold text-warning">+<?= $rekap['tugas']['nilai_tugas'] ?> poin</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card komponen-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-semibold"><i class="bi bi-journal-check text-primary me-1"></i>UTS</span>
                            <span class="badge bg-primary">Bobot 30%</span>
                        </div>
                        <div class="text-muted small mb-2">Nilai Mentah: <?= $rekap['ujian']['nilai_uts_raw'] ?></div>
                        <div class="progress mb-2" style="height:10px;">
                            <div class="progress-bar bg-primary"
                                 style="width:<?= $rekap['ujian']['nilai_uts_raw'] ?>%">
                            </div>
                        </div>
                        <div class="text-end fw-bold text-primary">+<?= $rekap['ujian']['nilai_uts'] ?> poin</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card komponen-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-semibold"><i class="bi bi-journal-bookmark text-success me-1"></i>UAS</span>
                            <span class="badge bg-success">Bobot 40%</span>
                        </div>
                        <div class="text-muted small mb-2">Nilai Mentah: <?= $rekap['ujian']['nilai_uas_raw'] ?></div>
                        <div class="progress mb-2" style="height:10px;">
                            <div class="progress-bar bg-success"
                                 style="width:<?= $rekap['ujian']['nilai_uas_raw'] ?>%">
                            </div>
                        </div>
                        <div class="text-end fw-bold text-success">+<?= $rekap['ujian']['nilai_uas'] ?> poin</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4 border-0 text-white nilai-banner">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="fw-bold mb-1">Nilai Akhir</h5>
                        <p class="mb-0 opacity-75 small">
                            Absensi(<?= $rekap['absensi']['nilai_absensi'] ?>) +
                            Tugas(<?= $rekap['tugas']['nilai_tugas'] ?>) +
                            UTS(<?= $rekap['ujian']['nilai_uts'] ?>) +
                            UAS(<?= $rekap['ujian']['nilai_uas'] ?>)
                        </p>
                    </div>
                    <div class="col-auto text-end">
                        <div class="display-4 fw-bold"><?= $rekap['nilai_akhir'] ?></div>
                        <span class="badge bg-white text-primary fs-5 px-3">
                            Grade <?= $rekap['grade'] ?> &nbsp;|&nbsp; <?= $rekap['angka_mutu'] ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <h6 class="fw-semibold text-muted mb-2">Tabel Konversi Grade Fakultas</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered text-center" style="max-width: 380px;">
                    <thead class="table-light">
                        <tr><th>Skor</th><th>Huruf</th><th>Angka Mutu</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $tabelGrade = [
                            ['skor'=>'≥ 85',    'huruf'=>'A',  'mutu'=>'4.00'],
                            ['skor'=>'80–84',   'huruf'=>'A-', 'mutu'=>'3.70'],
                            ['skor'=>'75–79',   'huruf'=>'B+', 'mutu'=>'3.30'],
                            ['skor'=>'70–74',   'huruf'=>'B',  'mutu'=>'3.00'],
                            ['skor'=>'65–69',   'huruf'=>'B-', 'mutu'=>'2.70'],
                            ['skor'=>'60–64',   'huruf'=>'C+', 'mutu'=>'2.30'],
                            ['skor'=>'55–59',   'huruf'=>'C',  'mutu'=>'2.00'],
                            ['skor'=>'50–54',   'huruf'=>'C-', 'mutu'=>'1.70'],
                            ['skor'=>'40–50',   'huruf'=>'D',  'mutu'=>'1.00'],
                            ['skor'=>'< 40',    'huruf'=>'E',  'mutu'=>'0.00'],
                        ];
                        foreach ($tabelGrade as $row):
                            $isActive = $rekap['grade'] === $row['huruf'];
                        ?>
                        <tr class="<?= $isActive ? 'table-success fw-bold' : '' ?>">
                            <td><?= $row['skor'] ?></td>
                            <td>
                                <span class="badge <?= gradeToBadgeClass($row['huruf']) ?> badge-grade-base">
                                    <?= $row['huruf'] ?>
                                </span>
                            </td>
                            <td><?= $row['mutu'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- ======================================================== -->
    <!-- TAB: ABSENSI -->
    <!-- ======================================================== -->
    <?php elseif ($activeTab === 'absensi'): ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-person-check-fill text-info me-2"></i>Data Absensi
            </h5>
            <button type="button" class="btn btn-info text-white"
                    data-bs-toggle="modal" data-bs-target="#modalTambahAbsensi">
                <i class="bi bi-plus-circle me-1"></i>Tambah Pertemuan
            </button>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="card bg-success bg-opacity-10 border-0 text-center p-2">
                    <div class="fw-bold text-success fs-4"><?= $jmlHadir ?></div>
                    <div class="small text-muted">Hadir</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-info bg-opacity-10 border-0 text-center p-2">
                    <div class="fw-bold text-info fs-4"><?= $jmlIzin ?></div>
                    <div class="small text-muted">Izin</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-warning bg-opacity-10 border-0 text-center p-2">
                    <div class="fw-bold text-warning fs-4"><?= $jmlSakit ?></div>
                    <div class="small text-muted">Sakit</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-danger bg-opacity-10 border-0 text-center p-2">
                    <div class="fw-bold text-danger fs-4"><?= $jmlAlpha ?></div>
                    <div class="small text-muted">Alpha</div>
                </div>
            </div>
        </div>

        <div class="mb-1 small text-muted">
            Persentase Kehadiran: <strong class="text-success"><?= $pctHadir ?>%</strong>
        </div>
        <div class="progress mb-4" style="height:12px;">
            <div class="progress-bar bg-success" style="width:<?= $pctHadir ?>%"></div>
        </div>

        <?php if (empty($absensiList)): ?>
            <div class="text-center py-4 text-muted">
                <i class="bi bi-calendar-x display-3"></i>
                <p class="mt-2">Belum ada data absensi.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Pertemuan</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($absensiList as $abs): ?>
                        <?php
                            $statusColor = match($abs['status']) {
                                'hadir'  => 'success',
                                'izin'   => 'info',
                                'sakit'  => 'warning',
                                'alpha'  => 'danger',
                                default  => 'secondary',
                            };
                        ?>
                        <tr>
                            <td class="fw-semibold">Pertemuan <?= $abs['pertemuan_ke'] ?></td>
                            <td><?= date('d M Y', strtotime($abs['tanggal'])) ?></td>
                            <td>
                                <span class="badge bg-<?= $statusColor ?> text-capitalize">
                                    <?= $abs['status'] ?>
                                </span>
                            </td>
                            <td class="text-muted small"><?= htmlspecialchars($abs['keterangan'] ?: '-') ?></td>
                            <td class="text-center">
                                <a href="actions/process_nilai.php?action=delete_absensi&id=<?= $abs['id'] ?>&mk_id=<?= $mkId ?>&semester_id=<?= $semesterId ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Hapus data absensi pertemuan ini?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    <!-- ======================================================== -->
    <!-- TAB: TUGAS -->
    <!-- ======================================================== -->
    <?php elseif ($activeTab === 'tugas'): ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-file-earmark-text-fill text-warning me-2"></i>Daftar Tugas
            </h5>
            <button type="button" class="btn btn-warning"
                    data-bs-toggle="modal" data-bs-target="#modalTambahTugas">
                <i class="bi bi-plus-circle me-1"></i>Tambah Tugas
            </button>
        </div>

        <?php if (empty($tugasList)): ?>
            <div class="text-center py-4 text-muted">
                <i class="bi bi-file-earmark-x display-3"></i>
                <p class="mt-2">Belum ada data tugas.</p>
            </div>
        <?php else: ?>
            <div class="alert alert-warning d-flex align-items-center mb-3">
                <i class="bi bi-calculator me-2 fs-5"></i>
                <span>
                    Rata-rata Nilai Tugas: <strong><?= $rataRataTugas ?></strong>
                    &rarr; Kontribusi: <strong><?= round($rataRataTugas * 0.20, 2) ?> poin</strong> (20%)
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Nama Tugas</th>
                            <th>Deadline</th>
                            <th>Nilai</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tugasList as $i => $tgs): ?>
                        <?php $isLate = strtotime($tgs['deadline']) < time(); ?>
                        <tr>
                            <td class="text-muted"><?= $i + 1 ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($tgs['nama_tugas']) ?></td>
                            <td>
                                <span class="<?= $isLate ? 'text-danger' : 'text-muted' ?> small">
                                    <?= date('d M Y', strtotime($tgs['deadline'])) ?>
                                    <?php if ($isLate): ?>
                                        <i class="bi bi-exclamation-circle ms-1"></i>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $tgs['nilai'] >= 75 ? 'success' : ($tgs['nilai'] >= 60 ? 'warning' : 'danger') ?> px-3 py-2">
                                    <?= $tgs['nilai'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-warning"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEditTugas"
                                            data-id="<?= $tgs['id'] ?>"
                                            data-nama="<?= htmlspecialchars($tgs['nama_tugas'], ENT_QUOTES) ?>"
                                            data-deadline="<?= $tgs['deadline'] ?>"
                                            data-nilai="<?= $tgs['nilai'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="actions/process_tugas.php?action=delete&id=<?= $tgs['id'] ?>&mk_id=<?= $mkId ?>&semester_id=<?= $semesterId ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Hapus tugas ini?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    <!-- ======================================================== -->
    <!-- TAB: UTS & UAS -->
    <!-- ======================================================== -->
    <?php elseif ($activeTab === 'ujian'): ?>

        <h5 class="fw-bold mb-4">
            <i class="bi bi-pencil-square text-success me-2"></i>Nilai UTS &amp; UAS
        </h5>

        <form method="POST" action="actions/process_nilai.php">
            <input type="hidden" name="action"          value="save_ujian">
            <input type="hidden" name="mata_kuliah_id"  value="<?= $mkId ?>">
            <input type="hidden" name="semester_id"     value="<?= $semesterId ?>">
            <?php if ($dataUjian): ?>
                <input type="hidden" name="id" value="<?= $dataUjian['id'] ?>">
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card border-primary border-2">
                        <div class="card-header bg-primary text-white fw-semibold">
                            <i class="bi bi-journal-check me-2"></i>Ujian Tengah Semester (UTS)
                            <span class="badge bg-white text-primary ms-2">Bobot 30%</span>
                        </div>
                        <div class="card-body p-4">
                            <label class="form-label fw-semibold">Nilai UTS (0–100)</label>
                            <input type="number" name="nilai_uts"
                                   class="form-control form-control-lg text-center"
                                   min="0" max="100"
                                   value="<?= $dataUjian ? $dataUjian['nilai_uts'] : '' ?>"
                                   placeholder="0" required>
                            <?php if ($dataUjian): ?>
                            <div class="mt-2 text-muted small text-center">
                                Kontribusi: <strong class="text-primary"><?= $rekap['ujian']['nilai_uts'] ?> poin</strong>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-success border-2">
                        <div class="card-header bg-success text-white fw-semibold">
                            <i class="bi bi-journal-bookmark me-2"></i>Ujian Akhir Semester (UAS)
                            <span class="badge bg-white text-success ms-2">Bobot 40%</span>
                        </div>
                        <div class="card-body p-4">
                            <label class="form-label fw-semibold">Nilai UAS (0–100)</label>
                            <input type="number" name="nilai_uas"
                                   class="form-control form-control-lg text-center"
                                   min="0" max="100"
                                   value="<?= $dataUjian ? $dataUjian['nilai_uas'] : '' ?>"
                                   placeholder="0" required>
                            <?php if ($dataUjian): ?>
                            <div class="mt-2 text-muted small text-center">
                                Kontribusi: <strong class="text-success"><?= $rekap['ujian']['nilai_uas'] ?> poin</strong>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-end">
                <button type="submit" class="btn btn-success btn-lg px-5">
                    <i class="bi bi-save me-2"></i>
                    <?= $dataUjian ? 'Update Nilai Ujian' : 'Simpan Nilai Ujian' ?>
                </button>
            </div>
        </form>

    <?php endif; ?>

    </div><!-- end card-body -->
</div><!-- end card tab -->

<!-- ============================================================ -->
<!-- SEMUA MODAL DIPINDAH KE LUAR CARD — INI KUNCI PERBAIKANNYA  -->
<!-- ============================================================ -->

<!-- Modal Tambah Absensi -->
<div class="modal fade" id="modalTambahAbsensi" tabindex="-1" aria-labelledby="labelTambahAbsensi" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="actions/process_nilai.php">
                <input type="hidden" name="action"         value="create_absensi">
                <input type="hidden" name="mata_kuliah_id" value="<?= $mkId ?>">
                <input type="hidden" name="semester_id"    value="<?= $semesterId ?>">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="labelTambahAbsensi">
                        <i class="bi bi-person-check me-2"></i>Tambah Absensi
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pertemuan ke-</label>
                        <input type="number" name="pertemuan_ke" class="form-control"
                               value="<?= $totalPertemuan + 1 ?>" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control"
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status Kehadiran</label>
                        <select name="status" class="form-select" required>
                            <option value="hadir">Hadir</option>
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                            <option value="alpha">Alpha</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control" placeholder="Opsional">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info text-white">
                        <i class="bi bi-save me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Tugas -->
<div class="modal fade" id="modalTambahTugas" tabindex="-1" aria-labelledby="labelTambahTugas" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="actions/process_tugas.php">
                <input type="hidden" name="action"         value="create">
                <input type="hidden" name="mata_kuliah_id" value="<?= $mkId ?>">
                <input type="hidden" name="semester_id"    value="<?= $semesterId ?>">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="labelTambahTugas">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Tugas
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Tugas <span class="text-danger">*</span></label>
                        <input type="text" name="nama_tugas" class="form-control"
                               placeholder="contoh: Tugas 1 - Membuat Form" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deadline</label>
                        <input type="date" name="deadline" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nilai (0–100)</label>
                        <input type="number" name="nilai" class="form-control"
                               min="0" max="100" placeholder="85" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-save me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Tugas -->
<div class="modal fade" id="modalEditTugas" tabindex="-1" aria-labelledby="labelEditTugas" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="actions/process_tugas.php">
                <input type="hidden" name="action"         value="update">
                <input type="hidden" name="mata_kuliah_id" value="<?= $mkId ?>">
                <input type="hidden" name="semester_id"    value="<?= $semesterId ?>">
                <input type="hidden" name="id"             id="edit_tugas_id">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="labelEditTugas">
                        <i class="bi bi-pencil-square me-2"></i>Edit Tugas
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Tugas</label>
                        <input type="text" name="nama_tugas" id="edit_tugas_nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deadline</label>
                        <input type="date" name="deadline" id="edit_tugas_deadline" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nilai (0–100)</label>
                        <input type="number" name="nilai" id="edit_tugas_nilai" class="form-control"
                               min="0" max="100" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-save me-1"></i>Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Script khusus halaman ini -->
<script>
// Isi modal Edit Tugas via data-attribute (lebih aman dari onclick inline)
document.addEventListener('DOMContentLoaded', function () {
    var modalEditTugas = document.getElementById('modalEditTugas');
    if (modalEditTugas) {
        modalEditTugas.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            document.getElementById('edit_tugas_id').value       = btn.getAttribute('data-id');
            document.getElementById('edit_tugas_nama').value     = btn.getAttribute('data-nama');
            document.getElementById('edit_tugas_deadline').value = btn.getAttribute('data-deadline');
            document.getElementById('edit_tugas_nilai').value    = btn.getAttribute('data-nilai');
        });
    }
});
</script>

<?php require_once 'views/layout/footer.php'; ?>