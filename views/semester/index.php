<?php
// views/semester/index.php
require_once 'config/database.php';

$semesters   = readData('semester');
$mataKuliahs = readData('mata_kuliah');

$dataIPK = hitungIPK();

function countMataKuliah(string $semesterId, array $mataKuliahs): int {
    return count(array_filter($mataKuliahs, fn($mk) => $mk['semester_id'] === $semesterId));
}

$breadcrumbs = [
    ['label' => 'Home',     'url' => 'index.php'],
    ['label' => 'Semester', 'url' => '#'],
];

require_once 'views/layout/header.php';
?>

<!-- PAGE HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-collection text-primary me-2"></i>Manajemen Semester
        </h4>
        <p class="text-muted mb-0 small">Kelola semester akademik dan lihat IPS setiap semester.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalImport">
            <i class="bi bi-upload me-1"></i>Import CSV
        </button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahSemester">
            <i class="bi bi-plus-circle me-1"></i>Tambah Semester
        </button>
    </div>
</div>

<!-- BANNER IPK -->
<?php if (!empty($semesters)): ?>
<div class="card border-0 mb-4 ipk-banner-card">
    <div class="card-body p-4">
        <div class="row align-items-center g-3">
            <div class="col-auto">
                <div class="ipk-circle">
                    <span class="ipk-number"><?= number_format($dataIPK['ipk'], 2) ?></span>
                    <span class="ipk-label-sm">IPK</span>
                </div>
            </div>
            <div class="col">
                <h5 class="fw-bold text-white mb-1">Indeks Prestasi Kumulatif</h5>
                <p class="mb-2 opacity-75 small">
                    Akumulasi dari seluruh <?= count($semesters) ?> semester yang telah ditempuh
                </p>
                <span class="badge bg-white text-primary fw-semibold px-3 py-2">
                    🎓 <?= $dataIPK['predikat'] ?>
                </span>
            </div>
            <div class="col-md-auto">
                <div class="d-flex gap-4">
                    <div class="text-center text-white">
                        <div class="fw-bold fs-4"><?= $dataIPK['total_sks'] ?></div>
                        <div class="small opacity-75">Total SKS</div>
                    </div>
                    <div class="text-center text-white">
                        <div class="fw-bold fs-4"><?= count($semesters) ?></div>
                        <div class="small opacity-75">Semester</div>
                    </div>
                    <div class="text-center text-white">
                        <div class="fw-bold fs-4"><?= count($mataKuliahs) ?></div>
                        <div class="small opacity-75">Total MK</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- KARTU SEMESTER -->
<?php if (empty($semesters)): ?>
    <div class="text-center py-5">
        <i class="bi bi-inbox display-1 text-muted"></i>
        <p class="mt-3 text-muted">Belum ada data semester. Tambahkan semester pertama Anda!</p>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($semesters as $sem):
            $jumlahMK   = countMataKuliah($sem['id'], $mataKuliahs);
            $ipsData    = hitungIPS($sem['id']);
            $badgeColor = $sem['status'] === 'aktif' ? 'success' : 'secondary';

            $ipsColor = 'danger';
            if ($ipsData['ips'] >= 3.51)     $ipsColor = 'success';
            elseif ($ipsData['ips'] >= 3.01) $ipsColor = 'primary';
            elseif ($ipsData['ips'] >= 2.76) $ipsColor = 'info';
            elseif ($ipsData['ips'] >= 2.00) $ipsColor = 'warning';
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0 semester-card">
                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="semester-icon">
                            <i class="bi bi-journal-bookmark-fill"></i>
                        </div>
                        <span class="badge bg-<?= $badgeColor ?> text-capitalize">
                            <?= htmlspecialchars($sem['status']) ?>
                        </span>
                    </div>

                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($sem['nama']) ?></h5>
                    <p class="text-muted small mb-3">
                        <i class="bi bi-calendar2 me-1"></i>
                        <?= htmlspecialchars($sem['tahun_akademik']) ?>
                    </p>

                    <div class="ips-display mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-semibold text-muted">
                                <i class="bi bi-graph-up me-1"></i>IPS Semester
                            </span>
                            <span class="fw-bold text-<?= $ipsColor ?> fs-5">
                                <?= number_format($ipsData['ips'], 2) ?>
                            </span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-<?= $ipsColor ?>"
                                 style="width: <?= ($ipsData['ips'] / 4) * 100 ?>%">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <span class="text-muted" style="font-size:0.7rem;">0.00</span>
                            <span class="text-muted" style="font-size:0.7rem;">4.00</span>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mb-3">
                        <span class="badge bg-info bg-opacity-15 text-info border border-info-subtle px-2 py-1 small">
                            <i class="bi bi-book me-1"></i><?= $jumlahMK ?> Mata Kuliah
                        </span>
                        <span class="badge bg-secondary bg-opacity-15 text-secondary border border-secondary-subtle px-2 py-1 small">
                            <i class="bi bi-award me-1"></i><?= $ipsData['total_sks'] ?> SKS
                        </span>
                    </div>

                    <div class="d-flex gap-2 mt-auto">
                        <a href="index.php?page=matakuliah&semester_id=<?= $sem['id'] ?>"
                           class="btn btn-primary btn-sm flex-grow-1">
                            <i class="bi bi-eye me-1"></i>Lihat MK
                        </a>
                        <button class="btn btn-outline-warning btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#modalEditSemester"
                                data-id="<?= $sem['id'] ?>"
                                data-nama="<?= htmlspecialchars($sem['nama'], ENT_QUOTES) ?>"
                                data-tahun="<?= htmlspecialchars($sem['tahun_akademik'], ENT_QUOTES) ?>"
                                data-status="<?= $sem['status'] ?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <a href="actions/process_semester.php?action=delete&id=<?= $sem['id'] ?>"
                           class="btn btn-outline-danger btn-sm"
                           onclick="return confirm('Hapus semester ini? Semua data terkait juga akan terhapus!')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>

                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- ============================================================ -->
<!-- MODAL TAMBAH SEMESTER -->
<!-- ============================================================ -->
<div class="modal fade" id="modalTambahSemester" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="actions/process_semester.php">
                <input type="hidden" name="action" value="create">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Semester Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Semester <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control"
                               placeholder="contoh: Semester 1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tahun Akademik <span class="text-danger">*</span></label>
                        <input type="text" name="tahun_akademik" class="form-control"
                               placeholder="contoh: 2024/2025" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="aktif">Aktif</option>
                            <option value="selesai">Selesai</option>
                        </select>
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
<!-- MODAL EDIT SEMESTER -->
<!-- ============================================================ -->
<div class="modal fade" id="modalEditSemester" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="actions/process_semester.php">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_semester_id">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square me-2"></i>Edit Semester
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Semester <span class="text-danger">*</span></label>
                        <input type="text" name="nama" id="edit_semester_nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tahun Akademik <span class="text-danger">*</span></label>
                        <input type="text" name="tahun_akademik" id="edit_semester_tahun" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_semester_status" class="form-select">
                            <option value="aktif">Aktif</option>
                            <option value="selesai">Selesai</option>
                        </select>
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

<!-- ============================================================ -->
<!-- MODAL IMPORT CSV -->
<!-- ============================================================ -->
<div class="modal fade" id="modalImport" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="bi bi-upload me-2"></i>Import Data dari CSV
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <ul class="nav nav-tabs mb-4" id="importTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab"
                                data-bs-target="#tab-semester" type="button" role="tab">
                            <i class="bi bi-collection me-1"></i>Semester
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab"
                                data-bs-target="#tab-mk" type="button" role="tab">
                            <i class="bi bi-book me-1"></i>Mata Kuliah
                        </button>
                    </li>
                </ul>

                <div class="tab-content">

                    <!-- TAB SEMESTER -->
                    <div class="tab-pane fade show active" id="tab-semester" role="tabpanel">
                        <form method="POST" action="actions/process_import.php"
                              enctype="multipart/form-data">
                            <input type="hidden" name="type" value="semester">
                            <div class="alert alert-info border-0 mb-3">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Format kolom:</strong>
                                <code>nama, tahun_akademik, status</code><br>
                                <small>Status: <code>aktif</code> atau <code>selesai</code></small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contoh isi CSV</label>
                                <div class="bg-dark text-success rounded p-3"
                                     style="font-family:monospace;font-size:0.8rem;line-height:1.8;">
                                    nama,tahun_akademik,status<br>
                                    Semester 1,2022/2023,selesai<br>
                                    Semester 6,2025/2026,aktif
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">
                                    Pilih File CSV <span class="text-danger">*</span>
                                </label>
                                <input type="file" name="file" class="form-control"
                                       accept=".csv" required>
                                <div class="form-text">Hanya file .csv</div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-info text-white">
                                    <i class="bi bi-cloud-upload me-1"></i>Upload & Import
                                </button>
                                <a href="assets/contoh/contoh_import_semester.csv"
                                   class="btn btn-outline-secondary" download>
                                    <i class="bi bi-download me-1"></i>Download Contoh
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- TAB MATA KULIAH -->
                    <div class="tab-pane fade" id="tab-mk" role="tabpanel">
                        <form method="POST" action="actions/process_import.php"
                              enctype="multipart/form-data">
                            <input type="hidden" name="type" value="matakuliah">
                            <div class="alert alert-info border-0 mb-3">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Format kolom:</strong>
                                <code>semester_nama, nama, sks, hari, jam, dosen</code><br>
                                <small>
                                    <code>semester_nama</code> harus sama persis
                                    dengan nama semester yang sudah ada.
                                </small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contoh isi CSV</label>
                                <div class="bg-dark text-success rounded p-3"
                                     style="font-family:monospace;font-size:0.8rem;line-height:1.8;">
                                    semester_nama,nama,sks,hari,jam,dosen<br>
                                    Semester 6,Pemrograman Web,3,Senin,08:00,Dr. Budi<br>
                                    Semester 1,Kalkulus,3,Senin,07:00,Prof. Dewi
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">
                                    Pilih File CSV <span class="text-danger">*</span>
                                </label>
                                <input type="file" name="file" class="form-control"
                                       accept=".csv" required>
                                <div class="form-text">Hanya file .csv</div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-info text-white">
                                    <i class="bi bi-cloud-upload me-1"></i>Upload & Import
                                </button>
                                <a href="assets/contoh/contoh_import_matakuliah.csv"
                                   class="btn btn-outline-secondary" download>
                                    <i class="bi bi-download me-1"></i>Download Contoh
                                </a>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script Edit Modal -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEdit = document.getElementById('modalEditSemester');
    if (modalEdit) {
        modalEdit.addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            document.getElementById('edit_semester_id').value    = btn.getAttribute('data-id');
            document.getElementById('edit_semester_nama').value  = btn.getAttribute('data-nama');
            document.getElementById('edit_semester_tahun').value = btn.getAttribute('data-tahun');
            var sel    = document.getElementById('edit_semester_status');
            var status = btn.getAttribute('data-status');
            for (var i = 0; i < sel.options.length; i++) {
                sel.options[i].selected = (sel.options[i].value === status);
            }
        });
    }
});
</script>

<?php require_once 'views/layout/footer.php'; ?>