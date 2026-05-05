<!DOCTYPE html>
<html lang="id">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Ajukan Peminjaman</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
        <style>
            body {
                background: #f4f6f9;
                letter-spacing: 0;
            }

            .public-shell {
                margin: 0 auto;
                max-width: 1180px;
                padding: 1.25rem;
            }

            .page-bar {
                align-items: center;
                display: flex;
                justify-content: space-between;
                margin-bottom: 1rem;
            }

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
                height: 100%;
                min-height: 380px;
                overflow: hidden;
                padding: 0;
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

            .book-code,
            .book-title,
            .book-author {
                overflow-wrap: anywhere;
                word-break: break-word;
            }

            .book-title {
                display: -webkit-box;
                font-weight: 500;
                line-height: 1.25;
                margin-top: .25rem;
                min-height: 2.5em;
                overflow: hidden;
                -webkit-box-orient: vertical;
                -webkit-line-clamp: 2;
            }

            .book-author {
                display: -webkit-box;
                line-height: 1.25;
                margin-top: .25rem;
                min-height: 2.5em;
                overflow: hidden;
                -webkit-box-orient: vertical;
                -webkit-line-clamp: 2;
            }

            .book-card .select-book {
                margin-top: auto;
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

            .form-hint {
                color: #6c757d;
                font-size: .875rem;
            }

            .latest-requests-scroll {
                max-height: 150px;
                overflow-y: auto;
            }
        </style>
    </head>

    <body>
        <div class="public-shell">
            <div class="page-bar">
                <div>
                    <h1 class="h3 mb-0">Ajukan Peminjaman</h1>
                    <span class="text-muted">Pilih buku, atur tanggal, lalu masukkan ID anggota.</span>
                </div>
                <a href="{{ route("login") }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-right-to-bracket mr-1"></i> Login
                </a>
            </div>

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
                                            <input id="bookSearchInput" type="search" class="form-control"
                                                placeholder="Cari buku, kode, penulis...">
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
                                    <label>Buku Dipilih</label>
                                    <div id="selectedBookCard" class="border rounded p-3 bg-light">
                                        <span class="text-muted">Belum ada buku dipilih.</span>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Tanggal Pinjam</label>
                                        <input type="date" name="borrow_date" class="form-control"
                                            value="{{ now()->toDateString() }}">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Tanggal Kembali</label>
                                        <input type="date" name="return_date" class="form-control"
                                            value="{{ now()->addWeek()->toDateString() }}">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>ID Anggota</label>
                                    <input id="memberCodeInput" type="text" name="member_code" class="form-control"
                                        placeholder="Contoh: AG-DEMO-20260505-0001">
                                    <div class="form-hint mt-1">ID anggota wajib diisi untuk mengirim peminjaman.</div>
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
                                <span class="text-muted">Masukkan ID anggota untuk melihat pengajuan terakhir.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
        <script>
            $(function() {
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

                function renderBookCover(book) {
                    if (book.cover_image_url) {
                        return '<div class="book-cover"><img src="' + escapeHtml(book.cover_image_url) +
                            '" alt="Cover buku"></div>';
                    }

                    return '<div class="book-cover"><i class="fas fa-book fa-2x"></i></div>';
                }

                function renderBooks() {
                    if (!books.length) {
                        $('#bookGrid').html('<div class="text-muted py-4">Buku tidak ditemukan.</div>');
                        updateGridControls();
                        return;
                    }

                    $('#bookGrid').html(books.map(function(book) {
                        const selected = selectedBooks.some(function(selectedBook) {
                            return selectedBook.id === book.id;
                        });
                        const unavailable = book.stock < 1;
                        const maxReached = selectedBooks.length >= maxSelectedBooks && !selected;
                        const disabled = selected || unavailable || maxReached;

                        return '<div class="book-card ' + (selected ? 'selected' : '') + ' ' + (
                                unavailable ? 'bg-light text-muted' : '') + '">' +
                            renderBookCover(book) +
                            '<div class="book-body">' +
                            '<strong class="d-block book-code">' + escapeHtml(book.code) + '</strong>' +
                            '<span class="d-block book-title">' + escapeHtml(book.title) + '</span>' +
                            '<small class="text-muted d-block book-author">' + escapeHtml(book.author) +
                            ' / ' + escapeHtml(book.publication_year) + '</small>' +
                            '<span class="badge badge-' + (unavailable ? 'danger' : 'success') +
                            ' mt-2 align-self-start">Stok ' + escapeHtml(book.stock) + '</span>' +
                            '<button class="btn ' + (disabled ? 'btn-secondary' : 'btn-primary') +
                            ' btn-sm btn-block select-book" type="button" data-id="' + escapeHtml(book
                                .id) + '" ' + (disabled ? 'disabled' : '') + '>' +
                            (unavailable ? 'Stok Habis' : (selected ? 'Sudah Dipilih' : (maxReached ?
                                'Maksimal 5 Buku' : 'Pilih Buku'))) +
                            '</button>' +
                            '</div>' +
                            '</div>';
                    }).join(''));

                    updateGridControls();
                }

                function updateGridControls() {
                    $('#bookPrevBtn').prop('disabled', bookPage <= 1);
                    $('#bookNextBtn').prop('disabled', bookPage >= bookLastPage);
                    $('#bookGridInfo').text(bookTotal ? 'Halaman ' + bookPage + ' dari ' + bookLastPage + ' / ' +
                        bookTotal + ' buku' : '');
                }

                function renderSelectedBooks() {
                    $('#selectedBookInputs').html(selectedBooks.map(function(book) {
                        return '<input type="hidden" name="book_ids[]" value="' + escapeHtml(book.id) +
                            '">';
                    }).join(''));

                    if (!selectedBooks.length) {
                        $('#selectedBookCard').html('<span class="text-muted">Belum ada buku dipilih.</span>');
                        return;
                    }

                    $('#selectedBookCard').html(selectedBooks.map(function(book) {
                        return '<div class="d-flex justify-content-between align-items-center border-bottom py-2">' +
                            '<div class="pr-2">' +
                            '<strong class="d-block">' + escapeHtml(book.code) + '</strong>' +
                            '<span class="d-block">' + escapeHtml(book.title) + '</span>' +
                            '</div>' +
                            '<button class="btn btn-outline-danger btn-sm remove-selected-book" type="button" data-id="' +
                            escapeHtml(book.id) + '">' +
                            '<i class="fas fa-xmark"></i>' +
                            '</button>' +
                            '</div>';
                    }).join(''));

                    $('#selectedBookCard').removeClass('border-danger');
                    $('#selectedBookCard').siblings('.field-error').remove();
                }

                function loadBooks(page = 1) {
                    $('#bookPickerLoading').removeClass('d-none');

                    $.get('{{ route("member.books.search") }}', {
                        q: $('#bookSearchInput').val(),
                        page: page
                    }).done(function(response) {
                        books = response.data;
                        bookPage = response.current_page || 1;
                        bookLastPage = response.last_page || 1;
                        bookTotal = response.total || 0;
                        renderBooks();
                    }).always(function() {
                        $('#bookPickerLoading').addClass('d-none');
                    });
                }

                function statusBadge(status) {
                    const badgeClass = status === 'approved' ? 'success' : (status === 'rejected' ? 'danger' :
                        'warning');

                    return '<span class="badge badge-' + badgeClass + '">' + escapeHtml(status) + '</span>';
                }

                function loadLatestRequests() {
                    const memberCode = $('#memberCodeInput').val().trim();

                    if (!memberCode) {
                        $('#latestRequestsCard').html(
                            '<span class="text-muted">Masukkan ID anggota untuk melihat pengajuan terakhir.</span>');
                        return;
                    }

                    $('#latestRequestsCard').html('<span class="text-muted">Memuat pengajuan...</span>');

                    $.get('{{ route("member.borrow-request.latest") }}', {
                        member_code: memberCode
                    }).done(function(response) {
                        if (!response.data.length) {
                            $('#latestRequestsCard').html(
                                '<span class="text-muted">Belum ada pengajuan untuk ID anggota ini.</span>');
                            return;
                        }

                        $('#latestRequestsCard').html(response.data.map(function(request) {
                            return '<div class="d-flex justify-content-between border-bottom py-2">' +
                                '<div>' +
                                '<span class="d-block">' + escapeHtml(request.book_label) +
                                '</span>' +
                                '<small class="text-muted">' + escapeHtml(request.borrow_date) +
                                ' sampai ' + escapeHtml(request.return_date) + '</small>' +
                                '</div>' +
                                statusBadge(request.status) +
                                '</div>';
                        }).join(''));
                    }).fail(function() {
                        $('#latestRequestsCard').html(
                            '<span class="text-danger">Pengajuan terakhir gagal dimuat.</span>');
                    });
                }

                function clearBorrowRequestFieldErrors() {
                    $('#borrowRequestForm .form-control').removeClass('is-invalid');
                    $('#borrowRequestForm .field-error').remove();
                    $('#selectedBookCard').removeClass('border-danger');
                }

                function showBorrowRequestFieldErrors(errors) {
                    Object.entries(errors).forEach(function([field, messages]) {
                        if (field === 'book_ids' || field.startsWith('book_ids.')) {
                            $('#selectedBookCard').addClass('border-danger');
                            $('#selectedBookCard').after('<p class="field-error text-danger small mb-0 mt-1">' +
                                messages.join('<br>') + '</p>');
                            return;
                        }

                        const $field = $('[name="' + field + '"]');

                        $field.addClass('is-invalid');
                        $field.after('<p class="field-error text-danger small mb-0 mt-1">' + messages.join(
                            '<br>') + '</p>');
                    });
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

                $('#bookPrevBtn').on('click', function() {
                    if (bookPage > 1) {
                        loadBooks(bookPage - 1);
                    }
                });

                $('#bookNextBtn').on('click', function() {
                    if (bookPage < bookLastPage) {
                        loadBooks(bookPage + 1);
                    }
                });

                $('#bookGrid').on('click', '.select-book', function() {
                    const bookId = Number($(this).data('id'));
                    const book = books.find(function(item) {
                        return item.id === bookId;
                    });

                    if (!book) {
                        return;
                    }

                    if (selectedBooks.length >= maxSelectedBooks) {
                        showToast('error', 'Maksimal', 'Maksimal 5 buku dalam satu pengajuan.');
                        return;
                    }

                    selectedBooks.push(book);
                    renderSelectedBooks();
                    renderBooks();
                });

                $('#selectedBookCard').on('click', '.remove-selected-book', function() {
                    const bookId = Number($(this).data('id'));

                    selectedBooks = selectedBooks.filter(function(book) {
                        return book.id !== bookId;
                    });
                    renderSelectedBooks();
                    renderBooks();
                });

                $('#bookSearchBtn').on('click', function() {
                    loadBooks(1);
                });
                $('#bookSearchInput').on('keyup', function(event) {
                    if (event.key === 'Enter') {
                        loadBooks(1);
                    }
                });
                $('#memberCodeInput').on('change blur', loadLatestRequests);

                $('#borrowRequestForm').on('submit', function(event) {
                    event.preventDefault();

                    const $button = $('#sendBorrowRequestBtn');
                    const $alert = $('#borrowRequestAlert');

                    $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Mengirim');
                    $alert.addClass('d-none');
                    $('#borrowRequestAlertText').empty();
                    clearBorrowRequestFieldErrors();

                    $.ajax({
                        url: '{{ route("member.borrow-request.store") }}',
                        method: 'POST',
                        data: $(this).serialize(),
                        success: function(response) {
                            showToast('success', 'Berhasil', response.message);
                            selectedBooks = [];
                            renderSelectedBooks();
                            renderBooks();
                            loadLatestRequests();
                        },
                        error: function(xhr) {
                            const errors = xhr.responseJSON?.errors;

                            if (errors) {
                                showBorrowRequestFieldErrors(errors);
                            } else {
                                $('#borrowRequestAlertText').text(xhr.responseJSON?.message ||
                                    'Pengajuan peminjaman gagal dikirim.');
                                $alert.removeClass('d-none');
                            }
                        },
                        complete: function() {
                            $button.prop('disabled', false).html(
                                '<i class="fas fa-paper-plane mr-1"></i> Kirim Pengajuan');
                        }
                    });
                });

                renderBooks();
            });
        </script>
    </body>

</html>
