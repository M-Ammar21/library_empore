@extends('layouts.adminlte', ['title' => 'Ajukan Peminjaman', 'heading' => 'Ajukan Peminjaman', 'mode' => 'member'])

@section('content')
<style>
    .book-grid {
        display: grid;
        gap: .75rem;
        grid-auto-rows: 1fr;
        grid-template-columns: 1fr;
    }

    @media (min-width: 768px) {
        .book-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 992px) {
        .book-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    .book-card {
        border: 1px solid #dee2e6;
        border-radius: .35rem;
        display: flex;
        flex-direction: column;
        min-height: 380px;
        overflow: hidden;
    }

    .book-card.selected {
        border-color: #dc3545;
        box-shadow: 0 0 0 .12rem rgba(220, 53, 69, .14);
    }

    .book-cover {
        align-items: center;
        background: #6c757d;
        color: #fff;
        display: flex;
        flex: 0 0 170px;
        height: 170px;
        justify-content: center;
        overflow: hidden;
        width: 100%;
    }

    .book-cover img {
        height: 100%;
        object-fit: cover;
        width: 100%;
    }

    .book-body {
        display: flex;
        flex: 1 1 auto;
        flex-direction: column;
        min-width: 0;
        padding: .85rem;
    }

    .book-title,
    .book-author {
        display: -webkit-box;
        line-height: 1.25;
        overflow: hidden;
        overflow-wrap: anywhere;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }

    .book-title {
        font-weight: 700;
        margin-top: .25rem;
        min-height: 2.5em;
    }

    .book-author {
        margin-top: .25rem;
        min-height: 2.5em;
    }

    .book-card .select-book {
        margin-top: auto;
    }

    .latest-requests-scroll {
        max-height: 150px;
        overflow-y: auto;
    }
</style>

<form id="borrowRequestForm">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <h3 class="card-title mb-0">Pilih Buku</h3>
                        </div>
                        <div class="col-md-5 mt-2 mt-md-0">
                            <div class="input-group input-group-sm">
                                <input id="bookSearchInput" type="search" class="form-control" placeholder="Cari buku, kode, penulis...">
                                <div class="input-group-append">
                                    <button id="bookSearchBtn" class="btn btn-primary" type="button">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="borrowRequestAlert" class="alert alert-danger d-none border-0 shadow-sm">
                        <strong class="d-block">Pengajuan belum bisa dikirim</strong>
                        <div id="borrowRequestAlertText"></div>
                    </div>
                    <div id="bookPickerLoading" class="text-muted d-none py-3">Memuat buku...</div>
                    <div id="bookGrid" class="book-grid"></div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <button id="bookPrevBtn" class="btn btn-outline-secondary btn-sm" type="button">
                            <i class="fas fa-chevron-left mr-1"></i> Sebelumnya
                        </button>
                        <span id="bookGridInfo" class="text-muted small"></span>
                        <button id="bookNextBtn" class="btn btn-outline-secondary btn-sm" type="button">
                            Berikutnya <i class="fas fa-chevron-right ml-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Detail Pengajuan</h3>
                </div>
                <div class="card-body">
                    <div id="selectedBookInputs"></div>
                    <div class="form-group">
                        <label>Anggota</label>
                        <div class="form-control bg-light">{{ $member->member_code }} - {{ $member->name }}</div>
                        <div class="form-hint mt-1">Pengajuan diproses dari akun yang sedang login.</div>
                    </div>
                    <div class="form-group">
                        <label>Buku Dipilih</label>
                        <div id="selectedBookCard" class="border rounded p-3 bg-light">
                            <span class="text-muted">Belum ada buku dipilih.</span>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Tanggal Pinjam</label>
                            <input type="date" name="borrow_date" class="form-control" value="{{ now()->toDateString() }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Tanggal Kembali</label>
                            <input type="date" name="return_date" class="form-control" value="{{ now()->addWeek()->toDateString() }}">
                        </div>
                    </div>
                    <button id="sendBorrowRequestBtn" class="btn btn-primary btn-block" type="submit">
                        <i class="fas fa-paper-plane mr-1"></i> Kirim Pengajuan
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Pengajuan Terakhir</h3>
                </div>
                <div id="latestRequestsCard" class="card-body latest-requests-scroll">
                    <span class="text-muted">Memuat pengajuan terakhir...</span>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    $(function () {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            }
        });

        let books = @json($books);
        let bookPage = 1;
        let bookLastPage = 1;
        let bookTotal = books.length;
        let selectedBooks = [];
        const maxSelectedBooks = 5;

        function escapeHtml(value) {
            return $('<div>').text(value ?? '').html();
        }

        function showToast(type, title, message) {
            $(document).Toasts('create', {
                autohide: true,
                autoremove: true,
                delay: 3500,
                position: 'topRight',
                class: type === 'success' ? 'bg-success' : 'bg-danger',
                icon: type === 'success' ? 'fas fa-circle-check' : 'fas fa-circle-exclamation',
                title: title,
                body: message
            });
        }

        function renderBookCover(book) {
            if (book.cover_image_url) {
                return '<div class="book-cover"><img src="' + escapeHtml(book.cover_image_url) + '" alt="Cover buku"></div>';
            }

            return '<div class="book-cover"><i class="fas fa-book fa-2x"></i></div>';
        }

        function updateGridControls() {
            $('#bookPrevBtn').prop('disabled', bookPage <= 1);
            $('#bookNextBtn').prop('disabled', bookPage >= bookLastPage);
            $('#bookGridInfo').text(bookTotal ? 'Halaman ' + bookPage + ' dari ' + bookLastPage + ' / ' + bookTotal + ' buku' : '');
        }

        function renderBooks() {
            if (! books.length) {
                $('#bookGrid').html('<div class="text-muted py-4">Buku tidak ditemukan.</div>');
                updateGridControls();
                return;
            }

            $('#bookGrid').html(books.map(function (book) {
                const selected = selectedBooks.some((selectedBook) => selectedBook.id === book.id);
                const unavailable = book.stock < 1;
                const maxReached = selectedBooks.length >= maxSelectedBooks && ! selected;
                const disabled = selected || unavailable || maxReached;

                return '<div class="book-card ' + (selected ? 'selected' : '') + ' ' + (unavailable ? 'bg-light text-muted' : '') + '">' +
                    renderBookCover(book) +
                    '<div class="book-body">' +
                    '<strong class="d-block">' + escapeHtml(book.code) + '</strong>' +
                    '<span class="book-title">' + escapeHtml(book.title) + '</span>' +
                    '<small class="text-muted book-author">' + escapeHtml(book.author) + ' / ' + escapeHtml(book.publication_year) + '</small>' +
                    '<span class="badge badge-' + (unavailable ? 'danger' : 'success') + ' mt-2 align-self-start">Stok ' + escapeHtml(book.stock) + '</span>' +
                    '<button class="btn ' + (disabled ? 'btn-secondary' : 'btn-primary') + ' btn-sm btn-block select-book" type="button" data-id="' + escapeHtml(book.id) + '" ' + (disabled ? 'disabled' : '') + '>' +
                    (unavailable ? 'Stok Habis' : (selected ? 'Sudah Dipilih' : (maxReached ? 'Maksimal 5 Buku' : 'Pilih Buku'))) +
                    '</button></div></div>';
            }).join(''));

            updateGridControls();
        }

        function renderSelectedBooks() {
            $('#selectedBookInputs').html(selectedBooks.map((book) => '<input type="hidden" name="book_ids[]" value="' + escapeHtml(book.id) + '">').join(''));

            if (! selectedBooks.length) {
                $('#selectedBookCard').html('<span class="text-muted">Belum ada buku dipilih.</span>');
                return;
            }

            $('#selectedBookCard').html(selectedBooks.map(function (book) {
                return '<div class="d-flex justify-content-between align-items-center border-bottom py-2">' +
                    '<div class="pr-2"><strong class="d-block">' + escapeHtml(book.code) + '</strong><span class="d-block">' + escapeHtml(book.title) + '</span></div>' +
                    '<button class="btn btn-outline-danger btn-sm remove-selected-book" type="button" data-id="' + escapeHtml(book.id) + '"><i class="fas fa-xmark"></i></button>' +
                    '</div>';
            }).join(''));
        }

        function loadBooks(page = 1) {
            $('#bookPickerLoading').removeClass('d-none');

            $.get('{{ route('member.books.search') }}', {
                q: $('#bookSearchInput').val(),
                page: page
            }).done(function (response) {
                books = response.data;
                bookPage = response.current_page || 1;
                bookLastPage = response.last_page || 1;
                bookTotal = response.total || 0;
                renderBooks();
            }).always(function () {
                $('#bookPickerLoading').addClass('d-none');
            });
        }

        function loadLatestRequests() {
            $('#latestRequestsCard').html('<span class="text-muted">Memuat pengajuan...</span>');

            $.get('{{ route('member.borrow-request.latest') }}').done(function (response) {
                if (! response.data.length) {
                    $('#latestRequestsCard').html('<span class="text-muted">Belum ada pengajuan untuk akun ini.</span>');
                    return;
                }

                $('#latestRequestsCard').html(response.data.map(function (request) {
                    const badgeClass = request.status === 'approved' ? 'success' : (request.status === 'rejected' ? 'danger' : 'warning');

                    return '<div class="d-flex justify-content-between border-bottom py-2">' +
                        '<div><span class="d-block">' + escapeHtml(request.book_label) + '</span><small class="text-muted">' + escapeHtml(request.borrow_date) + ' sampai ' + escapeHtml(request.return_date) + '</small></div>' +
                        '<span class="badge badge-' + badgeClass + '">' + escapeHtml(request.status) + '</span>' +
                    '</div>';
                }).join(''));
            });
        }

        $('#bookPrevBtn').on('click', () => bookPage > 1 && loadBooks(bookPage - 1));
        $('#bookNextBtn').on('click', () => bookPage < bookLastPage && loadBooks(bookPage + 1));
        $('#bookSearchBtn').on('click', () => loadBooks(1));
        $('#bookSearchInput').on('keyup', (event) => event.key === 'Enter' && loadBooks(1));

        $('#bookGrid').on('click', '.select-book', function () {
            const bookId = Number($(this).data('id'));
            const book = books.find((item) => item.id === bookId);

            if (! book || selectedBooks.length >= maxSelectedBooks) {
                showToast('error', 'Maksimal', 'Maksimal 5 buku dalam satu pengajuan.');
                return;
            }

            selectedBooks.push(book);
            renderSelectedBooks();
            renderBooks();
        });

        $('#selectedBookCard').on('click', '.remove-selected-book', function () {
            const bookId = Number($(this).data('id'));
            selectedBooks = selectedBooks.filter((book) => book.id !== bookId);
            renderSelectedBooks();
            renderBooks();
        });

        $('#borrowRequestForm').on('submit', function (event) {
            event.preventDefault();

            const $button = $('#sendBorrowRequestBtn');
            const $alert = $('#borrowRequestAlert');

            $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Mengirim');
            $alert.addClass('d-none');
            $('#borrowRequestAlertText').empty();

            $.ajax({
                url: '{{ route('member.borrow-request.store') }}',
                method: 'POST',
                data: $(this).serialize(),
                success: function (response) {
                    showToast('success', 'Berhasil', response.message);
                    selectedBooks = [];
                    renderSelectedBooks();
                    renderBooks();
                    loadLatestRequests();
                },
                error: function (xhr) {
                    const errors = xhr.responseJSON?.errors;
                    const message = errors ? Object.values(errors).flat().join('<br>') : (xhr.responseJSON?.message || 'Pengajuan peminjaman gagal dikirim.');

                    $('#borrowRequestAlertText').html(message);
                    $alert.removeClass('d-none');
                },
                complete: function () {
                    $button.prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i> Kirim Pengajuan');
                }
            });
        });

        renderBooks();
        loadLatestRequests();
    });
</script>
@endpush
