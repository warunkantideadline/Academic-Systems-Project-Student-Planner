<?php
// views/matakuliah/index.php
require_once 'config/database.php';

$semesterId = sanitize($_GET['semester_id'] ?? '');

$semester = findById('semester', $semesterId);
if (!$semester) {
    setFlash('danger', 'Semester tidak ditemukan!');
    redirect('index.php?page=semester');
}

$mataKuliahs = findWhere('mata_kuliah', 'semester_id', $semesterId);
$isSelesai   = $semester['status'] === 'selesai';

$breadcrumbs = [
    ['label' => 'Home',                              'url' => 'index.php'],
    ['label' => 'Semester',                          'url' => 'index.php?page=semester'],
    ['label' => htmlspecialchars($semester['nama']), 'url' => '#'],
];

$hariList = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

require_once 'views/layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-book text-primary me-2"></i>
            <?= htmlspecialchars($semester['nama']) ?>
        </h4>
        <p class="text-muted mb-0 small">
            <i class="bi bi-calendar2 me-1"></i><?= htmlspecialchars($semester['tahun_akademik']) ?>
            &nbsp;&bull;&nbsp;
            <span class="badge bg-<?= $isSelesai ? 'secondary' : 'success' ?>">
                <?= htmlspecialchars($semester['status']) ?>
            </span>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?page=semester" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahMK">
            <i class="bi bi-plus-circle me-1"></i>Tambah Mata Kuliah
        </button>
    </div>
</div>

<!-- STATISTIK -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-2 fw-bold text-primary"><?= count($mataKuliahs) ?></div>
            <div class="text-muted small">Total Mata Kuliah</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-2 fw-bold text-success"><?= array_sum(array_column($mataKuliahs, 'sks')) ?></div>
            <div class="text-muted small">Total SKS</div>
        </div>
    </div>
    <?php if ($isSelesai): ?>
    <div class="col-6 col-md-3">
        <?php $ipsData = hitungIPS($semesterId); ?>
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-2 fw-bold text-<?= $ipsData['ips'] >= 3.0 ? 'success' : ($ipsData['ips'] >= 2.0 ? 'warning' : 'danger') ?>">
                <?= number_format($ipsData['ips'], 2) ?>
            </div>
            <div class="text-muted small">IPS Semester</div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($isSelesai): ?>
<div class="alert alert-info border-0 mb-4">
    <i class="bi bi-info-circle me-2"></i>
    Semester ini sudah <strong>selesai</strong>. Kamu dapat mengisi nilai akhir langsung via tombol
    <i class="bi bi-pencil"></i> Edit.
</div>
<?php endif; ?>

<!-- TABEL MATA KULIAH -->
<?php if (empty($mataKuliahs)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox display-1 text-muted"></i>
            <p class="mt-3 text-muted">Belum ada mata kuliah di semester ini.</p>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahMK">
                <i class="bi bi-plus-circle me-1"></i>Tambah Sekarang
            </button>
        </div>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Mata Kuliah</th>
                            <th>SKS</th>
                            <th>Jadwal</th>
                            <th>Dosen</th>
                            <th>Nilai Akhir</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mataKuliahs as $i => $mk):
                            // Gunakan override jika ada, jika tidak hitung otomatis
                            $override   = isset($mk['nilai_akhir_override']) && $mk['nilai_akhir_override'] !== ''
                                          ? (float)$mk['nilai_akhir_override'] : null;
                            $rekap      = hitungNilaiAkhir($mk['id']);
                            $nilaiAkhir = $override !== null ? $override : $rekap['nilai_akhir'];
                            $grade      = skorToGrade($nilaiAkhir);
                        ?>
                        <tr>
                            <td class="ps-4 text-muted"><?= $i + 1 ?></td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($mk['nama']) ?></div>
                                <?php if ($override !== null): ?>
                                    <small class="text-success">
                                        <i class="bi bi-check-circle me-1"></i>Nilai diinput manual
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?= $mk['sks'] ?> SKS</span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-clock me-1"></i>
                                    <?= htmlspecialchars($mk['jadwal_hari']) ?>,
                                    <?= htmlspecialchars($mk['jadwal_jam']) ?>
                                </span>
                            </td>
                            <td class="text-muted small"><?= htmlspecialchars($mk['nama_dosen']) ?></td>
                            <td>
                                <?php if ($nilaiAkhir > 0): ?>
                                    <span class="badge badge-grade-base badge-grade-<?= gradeToBadgeClass($grade) ?> px-3 py-2">
                                        <?= number_format($nilaiAkhir, 2) ?> (<?= $grade ?>)
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">Belum ada data</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center pe-4">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="index.php?page=matakuliah&action=detail&id=<?= $mk['id'] ?>&semester_id=<?= $semesterId ?>"
                                       class="btn btn-sm btn-primary" title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-warning"
                                            title="Edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEditMK"
                                            data-id="<?= $mk['id'] ?>"
                                            data-nama="<?= htmlspecialchars($mk['nama'], ENT_QUOTES) ?>"
                                            data-sks="<?= $mk['sks'] ?>"
                                            data-hari="<?= $mk['jadwal_hari'] ?>"
                                            data-jam="<?= htmlspecialchars($mk['jadwal_jam'], ENT_QUOTES) ?>"
                                            data-dosen="<?= htmlspecialchars($mk['nama_dosen'], ENT_QUOTES) ?>"
                                            data-nilai="<?= $override !== null ? $override : '' ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="actions/process_matakuliah.php?action=delete&id=<?= $mk['id'] ?>&semester_id=<?= $semesterId ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       title="Hapus"
                                       onclick="return confirm('Hapus mata kuliah ini?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- ============================================================ -->
<!-- MODAL TAMBAH MATA KULIAH -->
<!-- ============================================================ -->
<div class="modal fade" id="modalTambahMK" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="actions/process_matakuliah.php">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="semester_id" value="<?= $semesterId ?>">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Mata Kuliah
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Nama Mata Kuliah <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control"
                                   placeholder="contoh: Pemrograman Web" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">SKS <span class="text-danger">*</span></label>
                            <select name="sks" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                <?php for ($s = 1; $s <= 4; $s++): ?>
                                    <option value="<?= $s ?>"><?= $s ?> SKS</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Hari <span class="text-danger">*</span></label>
                            <select name="jadwal_hari" class="form-select" required>
                                <option value="">-- Pilih Hari --</option>
                                <?php foreach ($hariList as $hari): ?>
                                    <option value="<?= $hari ?>"><?= $hari ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Jam <span class="text-danger">*</span></label>
                            <input type="text" name="jadwal_jam" class="form-control"
                                   placeholder="contoh: 08:00 - 10:30" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nama Dosen <span class="text-danger">*</span></label>
                            <input type="text" name="nama_dosen" class="form-control"
                                   placeholder="contoh: Dr. Ahmad" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL EDIT MATA KULIAH -->
<!-- ============================================================ -->
<div class="modal fade" id="modalEditMK" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="actions/process_matakuliah.php">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="semester_id" value="<?= $semesterId ?>">
                <input type="hidden" name="id" id="edit_mk_id">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square me-2"></i>Edit Mata Kuliah
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Nama Mata Kuliah <span class="text-danger">*</span></label>
                            <input type="text" name="nama" id="edit_mk_nama" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">SKS <span class="text-danger">*</span></label>
                            <select name="sks" id="edit_mk_sks" class="form-select" required>
                                <?php for ($s = 1; $s <= 4; $s++): ?>
                                    <option value="<?= $s ?>"><?= $s ?> SKS</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Hari <span class="text-danger">*</span></label>
                            <select name="jadwal_hari" id="edit_mk_hari" class="form-select" required>
                                <?php foreach ($hariList as $hari): ?>
                                    <option value="<?= $hari ?>"><?= $hari ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Jam <span class="text-danger">*</span></label>
                            <input type="text" name="jadwal_jam" id="edit_mk_jam" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nama Dosen <span class="text-danger">*</span></label>
                            <input type="text" name="nama_dosen" id="edit_mk_dosen" class="form-control" required>
                        </div>

                        <?php if ($isSelesai): ?>
                        <!-- NILAI AKHIR — hanya jika semester selesai -->
                        <div class="col-12"><hr class="my-1"></div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-trophy-fill text-warning me-1"></i>
                                Nilai Akhir
                                <span class="badge bg-secondary ms-1">Semester Selesai</span>
                            </label>
                            <input type="number"
                                   name="nilai_akhir_override"
                                   id="edit_mk_nilai"
                                   class="form-control form-control-lg text-center"
                                   min="0" max="100" step="0.01"
                                   placeholder="0 – 100">
                            <div class="form-text">
                                Isi nilai akhir (0–100). Kosongkan jika ingin dihitung otomatis dari absensi & tugas.
                            </div>
                        </div>
                        <!-- Preview Grade -->
                        <div class="col-12">
                            <div class="p-3 rounded-3 bg-light border align-items-center gap-3"
                                 id="gradePreviewBox" style="display:none;">
                                <span class="text-muted small">Grade:</span>
                                <span class="badge badge-grade-base fs-6 px-3 py-2 ms-2"
                                      id="gradePreviewBadge">—</span>
                            </div>
                        </div>
                        <?php endif; ?>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEdit = document.getElementById('modalEditMK');
    if (!modalEdit) return;

    modalEdit.addEventListener('show.bs.modal', function (e) {
        var btn = e.relatedTarget;
        document.getElementById('edit_mk_id').value    = btn.dataset.id;
        document.getElementById('edit_mk_nama').value  = btn.dataset.nama;
        document.getElementById('edit_mk_jam').value   = btn.dataset.jam;
        document.getElementById('edit_mk_dosen').value = btn.dataset.dosen;

        var selSks = document.getElementById('edit_mk_sks');
        for (var i = 0; i < selSks.options.length; i++)
            selSks.options[i].selected = (selSks.options[i].value == btn.dataset.sks);

        var selHari = document.getElementById('edit_mk_hari');
        for (var i = 0; i < selHari.options.length; i++)
            selHari.options[i].selected = (selHari.options[i].value === btn.dataset.hari);

        var nilaiEl = document.getElementById('edit_mk_nilai');
        if (nilaiEl) {
            nilaiEl.value = btn.dataset.nilai || '';
            updateGradePreview();
        }
    });

    var nilaiEl = document.getElementById('edit_mk_nilai');
    if (nilaiEl) nilaiEl.addEventListener('input', updateGradePreview);
});

function updateGradePreview() {
    var nilaiEl = document.getElementById('edit_mk_nilai');
    var box     = document.getElementById('gradePreviewBox');
    var badge   = document.getElementById('gradePreviewBadge');
    if (!nilaiEl || !box || !badge) return;

    var nilai = parseFloat(nilaiEl.value);
    if (isNaN(nilai) || nilaiEl.value === '') {
        box.style.display = 'none';
        return;
    }

    nilai = Math.min(100, Math.max(0, nilai));
    var grade = nilaiToGrade(nilai);
    badge.textContent = grade;
    badge.className   = 'badge badge-grade-base fs-6 px-3 py-2 ms-2 badge-grade-' + gradeClass(grade);
    box.style.display = 'flex';
}

function nilaiToGrade(n) {
    if (n >= 85) return 'A';
    if (n >= 80) return 'A-';
    if (n >= 75) return 'B+';
    if (n >= 70) return 'B';
    if (n >= 65) return 'B-';
    if (n >= 60) return 'C+';
    if (n >= 55) return 'C';
    if (n >= 50) return 'C-';
    if (n >= 40) return 'D';
    return 'E';
}

function gradeClass(g) {
    var map = {
        'A':'A','A-':'Amin','B+':'Bplus','B':'B','B-':'Bmin',
        'C+':'Cplus','C':'C','C-':'Cmin','D':'D','E':'E'
    };
    return map[g] || 'E';
}
</script>

<?php require_once 'views/layout/footer.php'; ?>