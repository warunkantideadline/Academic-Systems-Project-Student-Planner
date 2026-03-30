<?php
// views/layout/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/auth.php';
requireLogin();

$currentUser = getCurrentUser();
$currentPage = $page ?? ($_GET['page'] ?? 'home');
$semesterId  = $_GET['semester_id'] ?? '';

$semuaSemester = readData('semester');
usort($semuaSemester, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php
        $titles = [
            'home'       => 'Beranda',
            'semester'   => 'Daftar Semester',
            'matakuliah' => 'Mata Kuliah',
            'rekap'      => 'Rekap Nilai',
        ];
        echo ($titles[$currentPage] ?? 'SiAkad') . ' — SiAkad';
        ?>
    </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="app-wrapper">

    <!-- ── SIDEBAR ── -->
    <aside class="sidebar" id="sidebar">

        <!-- Brand -->
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
            <div class="sidebar-brand-text">
                <span class="brand-name">SiAkad</span>
                <span class="brand-sub">Sistem Akademik</span>
            </div>
        </div>

        <!-- Nav -->
        <nav class="sidebar-nav">
            <div class="sidebar-nav-label">Menu Utama</div>

            <a href="index.php"
               class="sidebar-nav-link <?= ($currentPage === 'home' || !isset($_GET['page'])) ? 'active' : '' ?>">
                <i class="bi bi-house-door"></i>
                <span>Beranda</span>
            </a>

            <a href="index.php?page=semester"
               class="sidebar-nav-link <?= $currentPage === 'semester' ? 'active' : '' ?>">
                <i class="bi bi-collection"></i>
                <span>Semester</span>
            </a>

            <a href="index.php?page=rekap"
               class="sidebar-nav-link <?= $currentPage === 'rekap' ? 'active' : '' ?>">
                <i class="bi bi-bar-chart-line"></i>
                <span>Rekap Nilai</span>
            </a>

            <?php if (!empty($semuaSemester)): ?>
            <div class="sidebar-nav-label mt-3">Semester</div>
            <?php foreach ($semuaSemester as $sem): ?>
            <a href="index.php?page=matakuliah&semester_id=<?= $sem['id'] ?>"
               class="sidebar-nav-link sidebar-nav-link-sm
                      <?= ($currentPage === 'matakuliah' && $semesterId === $sem['id']) ? 'active' : '' ?>">
                <i class="bi bi-<?= $sem['status'] === 'aktif' ? 'circle-fill text-success' : 'circle' ?>"
                   style="font-size:0.5rem; margin-top:2px;"></i>
                <span><?= htmlspecialchars($sem['nama']) ?></span>
                <?php if ($sem['status'] === 'aktif'): ?>
                    <span class="sidebar-badge">Aktif</span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </nav>

        <!-- User Info + Logout di sidebar footer -->
        <div class="sidebar-footer" style="flex-direction:column; align-items:flex-start; gap:0.5rem; padding: 1rem 1.25rem;">
            <div class="d-flex align-items-center gap-2" style="width:100%;">
                <div style="width:32px; height:32px; border-radius:50%;
                            background: linear-gradient(135deg,#4f46e5,#7c3aed);
                            display:flex; align-items:center; justify-content:center;
                            font-size:0.9rem; color:#fff; flex-shrink:0;">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div style="flex:1; min-width:0; line-height:1.3;">
                    <div style="font-size:0.8rem; font-weight:600; color:rgba(255,255,255,0.9);
                                white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        <?= htmlspecialchars($currentUser['nama'] ?? 'User') ?>
                    </div>
                    <div style="font-size:0.7rem; color:rgba(255,255,255,0.4);">
                        @<?= htmlspecialchars($currentUser['username'] ?? '') ?>
                    </div>
                </div>
            </div>
            <a href="actions/process_logout.php"
               onclick="return confirm('Yakin ingin keluar?')"
               style="width:100%; display:flex; align-items:center; gap:0.5rem;
                      padding:0.45rem 0.75rem; border-radius:8px; font-size:0.8rem;
                      font-weight:600; color:rgba(255,255,255,0.6);
                      background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.2);
                      text-decoration:none; transition:all 0.2s;"
               onmouseover="this.style.background='rgba(239,68,68,0.25)'; this.style.color='#fca5a5';"
               onmouseout="this.style.background='rgba(239,68,68,0.1)'; this.style.color='rgba(255,255,255,0.6)';">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
            <!-- <div style="font-size:0.7rem; color:rgba(255,255,255,0.25); margin-top:0.25rem;">
                <i class="bi bi-hdd me-1 opacity-50"></i>Storage: JSON
            </div> -->
        </div>

    </aside>

    <!-- ── KONTEN UTAMA ── -->
    <div class="main-content" id="mainContent">

        <!-- Topbar -->
        <header class="content-header">
            <button class="sidebar-toggle d-lg-none" id="sidebarToggle" type="button">
                <i class="bi bi-list"></i>
            </button>

            <div class="content-header-left">
                <?php if (!empty($breadcrumbs) && count($breadcrumbs) > 1): ?>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <?php foreach ($breadcrumbs as $i => $crumb): ?>
                                <?php if ($i < count($breadcrumbs) - 1): ?>
                                    <li class="breadcrumb-item">
                                        <a href="<?= $crumb['url'] ?>"><?= $crumb['label'] ?></a>
                                    </li>
                                <?php else: ?>
                                    <li class="breadcrumb-item active"><?= $crumb['label'] ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                <?php else: ?>
                    <span class="content-header-title">
                        <?php
                        $headerTitles = [
                            'home'       => 'Beranda',
                            'semester'   => 'Daftar Semester',
                            'matakuliah' => 'Mata Kuliah',
                            'rekap'      => 'Rekap Nilai',
                        ];
                        echo $headerTitles[$currentPage] ?? 'SiAkad';
                        ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Kanan: tanggal + info user -->
            <div class="content-header-right ms-auto">
                <span class="header-badge d-none d-md-flex">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= date('d M Y') ?>
                </span>
                <!-- <span class="header-badge">
                    <i class="bi bi-person-circle me-1"></i>
                    <!-- <?= htmlspecialchars($currentUser['nama'] ?? 'User') ?> >
                </span> -->
            </div>
        </header>

        <!-- Flash Message -->
        <?php if ($flash): ?>
        <div class="px-4 pt-3">
            <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'x-circle' : 'info-circle') ?> me-2"></i>
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Konten Halaman -->
        <main class="content-body">