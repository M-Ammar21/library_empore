@extends('layouts.adminlte', ['title' => 'Dashboard Anggota', 'heading' => 'Dashboard Anggota', 'mode' => 'member'])

@section('content')
<div class="row">
    <div class="col-lg-3 col-md-6">
        <a href="{{ route('member.borrowings') }}" class="text-dark">
            <div class="card stat-card borrowings shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="stat-content">
                        <div class="text-muted">Pinjaman Aktif</div>
                        <h3 class="mb-0">{{ number_format($stats['activeBorrowings']) }}</h3>
                    </div>
                    <i class="stat-icon fas fa-book-open fa-2x text-danger"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 col-md-6">
        <a href="{{ route('member.borrow-request') }}" class="text-dark">
            <div class="card stat-card requests shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="stat-content">
                        <div class="text-muted">Pengajuan Pending</div>
                        <h3 class="mb-0">{{ number_format($stats['pendingRequests']) }}</h3>
                    </div>
                    <i class="stat-icon fas fa-hourglass-half fa-2x text-warning"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 col-md-6">
        <a href="{{ route('member.borrowings') }}" class="text-dark">
            <div class="card stat-card members shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="stat-content">
                        <div class="text-muted">Total Riwayat</div>
                        <h3 class="mb-0">{{ number_format($stats['totalHistory']) }}</h3>
                    </div>
                    <i class="stat-icon fas fa-clock-rotate-left fa-2x text-success"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 col-md-6">
        <a href="{{ route('member.borrow-request') }}" class="text-dark">
            <div class="card stat-card books shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="stat-content">
                        <div class="text-muted">Buku Tersedia</div>
                        <h3 class="mb-0">{{ number_format($stats['availableBooks']) }}</h3>
                    </div>
                    <i class="stat-icon fas fa-book fa-2x text-primary"></i>
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
                    <canvas id="memberActivityTrendChart"></canvas>
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
                    <canvas id="memberRequestStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title mb-0">Status Pinjaman</h3>
            </div>
            <div class="card-body">
                <div style="height: 260px;">
                    <canvas id="memberBorrowingStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title mb-0">Buku Sering Dipinjam</h3>
            </div>
            <div class="card-body">
                <div style="height: 260px;">
                    <canvas id="memberTopBorrowedBooksChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title mb-0">Rekomendasi Buku Tersedia</h3>
                <a href="{{ route('member.borrow-request') }}" class="btn btn-sm btn-outline-primary">
                    Ajukan Pinjaman
                </a>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Judul</th>
                        <th>Penulis</th>
                        <th>Stok</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($recommendedBooks as $book)
                        <tr>
                            <td>{{ $book->code }}</td>
                            <td>{{ $book->title }}</td>
                            <td>{{ $book->author }}</td>
                            <td>
                                <span class="badge badge-{{ $book->stock <= 2 ? 'warning' : 'success' }}">
                                    {{ $book->stock }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-muted text-center py-4">Belum ada buku yang tersedia.</td>
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
                <h3 class="card-title mb-0">Aktivitas Peminjaman Terbaru</h3>
                <a href="{{ route('member.borrowings') }}" class="btn btn-sm btn-outline-primary">
                    Riwayat
                </a>
            </div>
            <div class="card-body">
                @forelse ($latestBorrowings as $borrowing)
                    @php
                        $status = $borrowing->status === 'returned'
                            ? ['class' => 'success', 'label' => 'Dikembalikan']
                            : ['class' => 'info', 'label' => 'Dipinjam'];
                    @endphp
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div class="pr-3">
                            <span class="d-block font-weight-bold">{{ $borrowing->book?->title ?? '-' }}</span>
                            <small class="text-muted">
                                {{ $borrowing->borrow_date?->format('d M Y') ?? '-' }} sampai {{ $borrowing->return_date?->format('d M Y') ?? '-' }}
                            </small>
                        </div>
                        <span class="badge badge-{{ $status['class'] }}">{{ $status['label'] }}</span>
                    </div>
                @empty
                    <div class="text-muted text-center py-4">Belum ada aktivitas peminjaman.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    $(function () {
        const trend = @json($trend);
        const insights = @json($insights);
        const activityDatasets = [
            {
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
        const highestActivityValue = Math.max(0, ...activityDatasets.flatMap((dataset) => dataset.data));

        new Chart(document.getElementById('memberActivityTrendChart'), {
            type: 'line',
            data: {
                labels: trend.labels,
                datasets: activityDatasets
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
                            label: (context) => context.dataset.label + ': ' + context.parsed.y + ' data'
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
                        suggestedMax: highestActivityValue < 5 ? 5 : highestActivityValue + 1,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        new Chart(document.getElementById('memberRequestStatusChart'), {
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

        new Chart(document.getElementById('memberBorrowingStatusChart'), {
            type: 'bar',
            data: {
                labels: insights.borrowingStatuses.labels,
                datasets: [{
                    label: 'Jumlah Pinjaman',
                    data: insights.borrowingStatuses.data,
                    backgroundColor: ['#17a2b8', '#28a745'],
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
                            label: (context) => context.parsed.y + ' pinjaman'
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

        new Chart(document.getElementById('memberTopBorrowedBooksChart'), {
            type: 'bar',
            data: {
                labels: insights.topBorrowedBooks.labels.length ? insights.topBorrowedBooks.labels : ['Belum ada data'],
                datasets: [{
                    label: 'Dipinjam',
                    data: insights.topBorrowedBooks.data.length ? insights.topBorrowedBooks.data : [0],
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
