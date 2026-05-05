@extends("layouts.adminlte", ["title" => "Dashboard Admin", "heading" => "Dashboard Admin"])

@section("content")
    <div class="row">
        <div class="col-lg-3 col-md-6">
            <a href="{{ route("admin.books") }}" class="text-dark">
                <div class="card stat-card books shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div class="stat-content">
                            <div class="text-muted">Total Buku</div>
                            <h3 class="mb-0">{{ number_format($stats["books"]) }}</h3>
                        </div>
                        <i class="stat-icon fas fa-book fa-2x text-primary"></i>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6">
            <a href="{{ route("admin.members") }}" class="text-dark">
                <div class="card stat-card members shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div class="stat-content">
                            <div class="text-muted">Anggota</div>
                            <h3 class="mb-0">{{ number_format($stats["members"]) }}</h3>
                        </div>
                        <i class="stat-icon fas fa-users fa-2x text-success"></i>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6">
            <a href="{{ route("admin.borrow-requests") }}" class="text-dark">
                <div class="card stat-card requests shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div class="stat-content">
                            <div class="text-muted">Pengajuan Pending</div>
                            <h3 class="mb-0">{{ number_format($stats["pendingRequests"]) }}</h3>
                        </div>
                        <i class="stat-icon fas fa-inbox fa-2x text-warning"></i>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6">
            <a href="{{ route("admin.borrowings") }}" class="text-dark">
                <div class="card stat-card borrowings shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div class="stat-content">
                            <div class="text-muted">Sedang Dipinjam</div>
                            <h3 class="mb-0">{{ number_format($stats["activeBorrowings"]) }}</h3>
                        </div>
                        <i class="stat-icon fas fa-right-left fa-2x text-danger"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Trend Aktivitas 7 Hari</h3>
                    <span class="text-muted small">Pengajuan, peminjaman, dan pengembalian</span>
                </div>
                <div class="card-body">
                    <div style="height: 320px;">
                        <canvas id="activityTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="card-title mb-0">Status Pengajuan</h3>
                </div>
                <div class="card-body">
                    <div style="height: 260px;">
                        <canvas id="requestStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="card-title mb-0">Kesehatan Stok Buku</h3>
                </div>
                <div class="card-body">
                    <div style="height: 260px;">
                        <canvas id="stockHealthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="card-title mb-0">Buku Paling Dipinjam</h3>
                </div>
                <div class="card-body">
                    <div style="height: 260px;">
                        <canvas id="topBorrowedBooksChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Pengajuan Terbaru</h3>
                    <a href="{{ route("admin.borrow-requests") }}" class="btn btn-sm btn-outline-primary">
                        Lihat Semua
                    </a>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID Anggota</th>
                                <th>Anggota</th>
                                <th>Buku</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($latestRequests as $request)
                                @php
                                    $statusMap = [
                                        "approved" => ["class" => "success", "label" => "Disetujui"],
                                        "rejected" => ["class" => "danger", "label" => "Ditolak"],
                                        "pending" => ["class" => "warning", "label" => "Pending"],
                                    ];
                                    $status = $statusMap[$request->status] ?? [
                                        "class" => "secondary",
                                        "label" => ucfirst($request->status),
                                    ];
                                @endphp
                                <tr>
                                    <td>{{ $request->member?->member_code ?? "-" }}</td>
                                    <td>{{ $request->member?->name ?? "-" }}</td>
                                    <td>
                                        <span class="d-block font-weight-bold">{{ $request->book?->title ?? "-" }}</span>
                                        <small class="text-muted">{{ $request->book?->code ?? "-" }}</small>
                                    </td>
                                    <td>{{ $request->borrow_date?->format("d M Y") ?? "-" }}</td>
                                    <td>
                                        <span class="badge badge-{{ $status["class"] }}">
                                            {{ $status["label"] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted text-center py-4">Belum ada pengajuan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Stok Perlu Perhatian</h3>
                    <a href="{{ route("admin.books") }}" class="btn btn-sm btn-outline-primary">
                        Kelola Buku
                    </a>
                </div>
                <div class="card-body">
                    @forelse ($lowStockBooks as $book)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div class="pr-3">
                                <span class="d-block font-weight-bold">{{ $book->title }}</span>
                                <small class="text-muted">{{ $book->code }}</small>
                            </div>
                            <span class="badge badge-{{ $book->stock <= 1 ? "danger" : "warning" }}">
                                {{ $book->stock }} tersisa
                            </span>
                        </div>
                    @empty
                        <div class="text-muted text-center py-4">Semua stok buku masih aman.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push("scripts")
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        $(function() {
            const trend = @json($trend);
            const insights = @json($insights);
            const datasets = [{
                    label: 'Pengajuan',
                    data: trend.requests,
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, .14)',
                    tension: .32,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6
                },
                {
                    label: 'Peminjaman',
                    data: trend.borrowings,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, .10)',
                    tension: .32,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6
                },
                {
                    label: 'Pengembalian',
                    data: trend.returns,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, .10)',
                    tension: .32,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }
            ];
            const highestValue = Math.max(0, ...datasets.flatMap((dataset) => dataset.data));

            new Chart(document.getElementById('activityTrendChart'), {
                type: 'line',
                data: {
                    labels: trend.labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => context.dataset.label + ': ' + context.parsed.y +
                                    ' data'
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            suggestedMax: highestValue < 5 ? 5 : highestValue + 1,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });

            new Chart(document.getElementById('requestStatusChart'), {
                type: 'doughnut',
                data: {
                    labels: insights.requestStatuses.labels,
                    datasets: [{
                        data: insights.requestStatuses.data,
                        backgroundColor: ['#ffc107', '#28a745', '#dc3545'],
                        borderColor: '#fff',
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => context.label + ': ' + context.parsed + ' data'
                            }
                        }
                    }
                }
            });

            new Chart(document.getElementById('stockHealthChart'), {
                type: 'bar',
                data: {
                    labels: insights.stockHealth.labels,
                    datasets: [{
                        label: 'Jumlah Buku',
                        data: insights.stockHealth.data,
                        backgroundColor: ['#dc3545', '#fd7e14', '#ffc107', '#28a745'],
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => context.parsed.y + ' buku'
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });

            new Chart(document.getElementById('topBorrowedBooksChart'), {
                type: 'bar',
                data: {
                    labels: insights.topBorrowedBooks.labels.length ? insights.topBorrowedBooks.labels : [
                        'Belum ada data'
                    ],
                    datasets: [{
                        label: 'Dipinjam',
                        data: insights.topBorrowedBooks.data.length ? insights.topBorrowedBooks
                            .data : [0],
                        backgroundColor: '#dc3545',
                        borderRadius: 6
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => context.parsed.x + ' kali dipinjam'
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                callback: function(value) {
                                    const label = this.getLabelForValue(value);

                                    return label.length > 24 ? label.substring(0, 24) + '...' : label;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
