<!DOCTYPE html>
<html lang="id">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? "Library Admin" }}</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css">
        <style>
            body {
                letter-spacing: 0;
            }

            .brand-link .brand-text {
                font-weight: 700;
            }

            .content-wrapper {
                background: #f4f6f9;
            }

            .stat-card {
                border-left: 4px solid transparent;
            }

            .stat-card.books {
                border-color: #dc3545;
            }

            .stat-card.members {
                border-color: #28a745;
            }

            .stat-card.requests {
                border-color: #ffc107;
            }

            .stat-card.borrowings {
                border-color: #dc3545;
            }

            .stat-card .card-body {
                width: 100%;
            }

            .stat-card .stat-content {
                flex: 1 1 auto;
                min-width: 0;
            }

            .stat-card .stat-icon {
                flex: 0 0 auto;
                margin-left: auto;
            }

            .table td,
            .table th {
                vertical-align: middle;
            }

            .badge {
                font-size: .78rem;
            }

            .btn-icon {
                width: 34px;
                height: 34px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .form-hint {
                color: #6c757d;
                font-size: .875rem;
            }

            .main-sidebar {
                background: #2b1d20;
            }

            .layout-navbar-fixed .wrapper .sidebar-dark-danger .brand-link:not([class*=navbar]) {
                background: #8f1d2c;
            }

            .sidebar-dark-danger .nav-sidebar>.nav-item>.nav-link.active,
            .sidebar-light-danger .nav-sidebar>.nav-item>.nav-link.active {
                background: #dc3545;
            }

            .main-sidebar .sidebar {
                display: flex;
                flex-direction: column;
                height: calc(100% - 4rem);
            }

            .sidebar-nav {
                flex: 1 1 auto;
            }

            .sidebar-area {
                border-top: 1px solid rgba(255, 255, 255, .12);
            }

            table.dataTable thead>tr>th.sorting,
            table.dataTable thead>tr>th.sorting_asc,
            table.dataTable thead>tr>th.sorting_desc,
            table.dataTable thead>tr>td.sorting,
            table.dataTable thead>tr>td.sorting_asc,
            table.dataTable thead>tr>td.sorting_desc {
                padding-right: 2rem;
                position: relative;
            }

            table.dataTable thead .sorting:before,
            table.dataTable thead .sorting:after,
            table.dataTable thead .sorting_asc:before,
            table.dataTable thead .sorting_asc:after,
            table.dataTable thead .sorting_desc:before,
            table.dataTable thead .sorting_desc:after {
                bottom: auto;
                display: inline-block;
                font-family: "Font Awesome 6 Free";
                font-size: .72rem;
                font-weight: 900;
                line-height: 1;
                opacity: .28;
                position: absolute;
                right: .75rem;
            }

            table.dataTable thead .sorting:before,
            table.dataTable thead .sorting_asc:before,
            table.dataTable thead .sorting_desc:before {
                content: "\f0d8";
                top: calc(50% - .7rem);
            }

            table.dataTable thead .sorting:after,
            table.dataTable thead .sorting_asc:after,
            table.dataTable thead .sorting_desc:after {
                content: "\f0d7";
                top: calc(50% + .05rem);
            }

            table.dataTable thead .sorting_asc:before,
            table.dataTable thead .sorting_desc:after {
                color: #dc3545;
                opacity: 1;
            }

            .toasts-top-right {
                right: 1.5rem;
                top: 1.25rem;
            }

            .toasts-top-right .toast {
                border: 0;
                border-radius: .35rem;
                box-shadow: 0 .75rem 1.5rem rgba(0, 0, 0, .18);
                min-width: 360px;
            }

            .toasts-top-right .toast-header {
                border-bottom: 1px solid rgba(255, 255, 255, .24);
                padding: .7rem 1rem;
            }

            .toasts-top-right .toast-body {
                font-size: .95rem;
                padding: .9rem 1rem 1rem;
            }

            .btn-primary {
                background-color: #dc3545;
                border-color: #dc3545;
            }

            .btn-primary:hover,
            .btn-primary:focus {
                background-color: #bd2130;
                border-color: #b21f2d;
            }

            .text-primary {
                color: #dc3545 !important;
            }

            .bg-info {
                background-color: #dc3545 !important;
            }

            .profile-trigger {
                background: rgba(220, 53, 69, .08);
                border: 1px solid rgba(220, 53, 69, .18);
                border-radius: 999px;
                color: #495057 !important;
                margin: .35rem .75rem .35rem 0;
                padding: .35rem .75rem !important;
                transition: background-color .15s ease, border-color .15s ease;
            }

            .profile-trigger:hover,
            .profile-trigger:focus {
                background: rgba(220, 53, 69, .14);
                border-color: rgba(220, 53, 69, .32);
            }

            .profile-info-item {
                background: #f8f9fa;
                border: 1px solid #edf0f2;
                border-radius: .35rem;
                padding: .55rem .65rem;
            }

            .profile-info-label {
                color: #6c757d;
                display: block;
                font-size: .72rem;
                font-weight: 700;
                letter-spacing: 0;
                line-height: 1;
                margin-bottom: .35rem;
                text-transform: uppercase;
            }

            .profile-info-value {
                color: #212529;
                display: block;
                font-size: .9rem;
                line-height: 1.25;
                overflow-wrap: anywhere;
                word-break: break-word;
            }
        </style>
    </head>
    @php
        $mode = $mode ?? "admin";
        $activeGuard = $mode === "member" ? "member" : "admin";
        $activeUser = auth($activeGuard)->user();
        $logoutRoute = $mode === "member" ? route("member.logout") : route("admin.logout");
    @endphp

    <body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
        <div class="wrapper">
            <nav class="main-header navbar navbar-expand navbar-white navbar-light">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-widget="pushmenu" href="#" role="button"
                            aria-label="Toggle navigation">
                            <i class="fas fa-bars"></i>
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link profile-trigger d-flex align-items-center" data-toggle="dropdown" href="#"
                            role="button" aria-haspopup="true" aria-expanded="false">
                            <i class="far fa-circle-user mr-1"></i>
                            <span>{{ $activeUser?->name ?? "User" }}</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow-sm" style="min-width: 260px;">
                            <div class="px-3 py-2">
                                <strong class="d-block">{{ $activeUser?->name ?? "User" }}</strong>
                                <small class="text-muted">{{ $mode === "member" ? "Anggota" : "Admin" }}</small>
                            </div>
                            <div class="dropdown-divider"></div>
                            <div class="px-3 py-2">
                                <div class="profile-info-item">
                                    <span class="profile-info-label">ID</span>
                                    <span class="profile-info-value font-weight-bold">
                                        {{ $mode === "member" ? $activeUser?->member_code ?? "-" : $activeUser?->id ?? "-" }}
                                    </span>
                                </div>
                                <div class="profile-info-item mt-2">
                                    <span class="profile-info-label">Email</span>
                                    <span class="profile-info-value">{{ $activeUser?->email ?? "-" }}</span>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <form action="{{ $logoutRoute }}" method="post" class="px-3 py-2 mb-0">
                                @csrf
                                <button class="btn btn-danger btn-sm btn-block" type="submit">
                                    <i class="fas fa-right-from-bracket mr-1"></i> Logout
                                </button>
                            </form>
                        </div>
                    </li>
                </ul>
            </nav>

            <aside class="main-sidebar sidebar-dark-danger elevation-4">
                <a href="{{ $mode === "member" ? route("member.dashboard") : route("admin.dashboard") }}"
                    class="brand-link">
                    <i class="fas fa-book-reader brand-image img-circle elevation-2 d-flex align-items-center justify-content-center bg-danger"
                        style="opacity:.9;width:33px;height:33px;"></i>
                    <span class="brand-text">Library</span>
                </a>

                <div class="sidebar">
                    <nav class="mt-2 sidebar-nav">
                        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                            @if ($mode === "member")
                                <li class="nav-item">
                                    <a href="{{ route("member.dashboard") }}"
                                        class="nav-link {{ request()->routeIs("member.dashboard") ? "active" : "" }}">
                                        <i class="nav-icon fas fa-gauge"></i>
                                        <p>Dashboard</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route("member.borrow-request") }}"
                                        class="nav-link {{ request()->routeIs("member.borrow-request") ? "active" : "" }}">
                                        <i class="nav-icon fas fa-plus-circle"></i>
                                        <p>Ajukan Pinjaman</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route("member.borrowings") }}"
                                        class="nav-link {{ request()->routeIs("member.borrowings") ? "active" : "" }}">
                                        <i class="nav-icon fas fa-clock-rotate-left"></i>
                                        <p>Pinjaman Saya</p>
                                    </a>
                                </li>
                            @else
                                <li class="nav-item">
                                    <a href="{{ route("admin.dashboard") }}"
                                        class="nav-link {{ request()->routeIs("admin.dashboard") ? "active" : "" }}">
                                        <i class="nav-icon fas fa-gauge"></i>
                                        <p>Dashboard</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route("admin.books") }}"
                                        class="nav-link {{ request()->routeIs("admin.books") ? "active" : "" }}">
                                        <i class="nav-icon fas fa-book"></i>
                                        <p>Master Buku</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route("admin.members") }}"
                                        class="nav-link {{ request()->routeIs("admin.members") ? "active" : "" }}">
                                        <i class="nav-icon fas fa-users"></i>
                                        <p>Anggota</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route("admin.borrow-requests") }}"
                                        class="nav-link {{ request()->routeIs("admin.borrow-requests") ? "active" : "" }}">
                                        <i class="nav-icon fas fa-inbox"></i>
                                        <p>Pengajuan</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route("admin.borrowings") }}"
                                        class="nav-link {{ request()->routeIs("admin.borrowings") ? "active" : "" }}">
                                        <i class="nav-icon fas fa-right-left"></i>
                                        <p>Peminjaman</p>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </nav>

                    <div class="user-panel sidebar-area mt-auto pt-3 pb-3 d-flex">
                        <div class="image">
                            <span
                                class="img-circle elevation-2 bg-secondary d-inline-flex align-items-center justify-content-center"
                                style="width:34px;height:34px;">
                                <i class="fas fa-user"></i>
                            </span>
                        </div>
                        <div class="info">
                            <span
                                class="d-block text-white">{{ $activeUser?->name ?? ($mode === "member" ? "Area Anggota" : "Area Admin") }}</span>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="content-wrapper">
                <section class="content-header">
                    <div class="container-fluid">
                        <div class="row mb-2">
                            <div class="col-sm-7">
                                <h1 class="m-0">{{ $heading ?? ($title ?? "Library") }}</h1>
                            </div>
                            <div class="col-sm-5">
                                <ol class="breadcrumb float-sm-right">
                                    <li class="breadcrumb-item"><a
                                            href="{{ $mode === "member" ? route("member.dashboard") : route("admin.dashboard") }}">Home</a>
                                    </li>
                                    <li class="breadcrumb-item active">{{ $heading ?? ($title ?? "Library") }}</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="content">
                    <div class="container-fluid">
                        @yield("content")
                    </div>
                </section>
            </div>

            <footer class="main-footer">
                <strong>Library</strong>
                <span class="text-muted ml-1">{{ $mode === "member" ? "Area anggota" : "Area admin" }} berbasis
                    guard.</span>
            </footer>
        </div>

        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>
        <script>
            $(function() {
                $('.datatable').DataTable({
                    responsive: true,
                    autoWidth: false,
                    pageLength: 5,
                    language: {
                        search: 'Cari:',
                        lengthMenu: 'Tampilkan _MENU_ data',
                        info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                        paginate: {
                            previous: 'Sebelumnya',
                            next: 'Berikutnya'
                        },
                        emptyTable: 'Belum ada data'
                    }
                });

                $('[data-toggle="tooltip"]').tooltip();
            });
        </script>
        @stack("scripts")
    </body>

</html>
