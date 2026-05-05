@extends('layouts.adminlte', ['title' => 'Master Buku', 'heading' => 'Master Buku'])

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Data Buku</h3>
        <div class="card-tools">
            <button id="addBookBtn" class="btn btn-primary btn-sm" type="button">
                <i class="fas fa-plus mr-1"></i> Tambah Buku
            </button>
        </div>
    </div>
    <div class="card-body">
        <table id="booksTable" class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>Kode</th>
                <th>Judul</th>
                <th>Tahun</th>
                <th>Penulis</th>
                <th>Stok</th>
                <th>Dibuat</th>
                <th>Cover</th>
                <th style="width:110px;">Aksi</th>
            </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="bookModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="bookModalTitle" class="modal-title">Form Buku</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="bookForm" enctype="multipart/form-data">
                <input type="hidden" id="bookId" name="book_id">
                <div class="modal-body">
                    <div id="bookFormAlert" class="alert alert-danger d-none border-0 shadow-sm">
                        <div class="d-flex">
                            <i class="fas fa-circle-exclamation mt-1 mr-2"></i>
                            <div>
                                <strong class="d-block">Data belum bisa disimpan</strong>
                                <div id="bookFormAlertText"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Kode Buku</label>
                        <input type="text" name="code" class="form-control" placeholder="BK004">
                    </div>
                    <div class="form-group">
                        <label>Judul Buku</label>
                        <input type="text" name="title" class="form-control" placeholder="Masukkan judul buku">
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Tahun Terbit</label>
                            <input type="number" name="publication_year" class="form-control" placeholder="2026">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Stok</label>
                            <input type="number" name="stock" class="form-control" placeholder="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Penulis</label>
                        <input type="text" name="author" class="form-control" placeholder="Nama penulis">
                    </div>
                    <div class="form-group">
                        <label>Cover Buku</label>
                        <input id="coverImageInput" type="file" name="cover_image" class="form-control" accept="image/*">
                        <div class="form-hint mt-1">Format gambar, maksimal 2MB.</div>
                        <div id="coverPreviewWrap" class="mt-2 d-none">
                            <img id="coverPreview" src="" alt="Preview cover buku" class="img-thumbnail" style="width:96px;height:128px;object-fit:cover;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" id="saveBookBtn" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteBookModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hapus Buku</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="deleteBookId">
                <p class="mb-1">Yakin ingin menghapus buku ini?</p>
                <strong id="deleteBookTitle" class="d-block text-danger"></strong>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                <button type="button" id="confirmDeleteBookBtn" class="btn btn-danger btn-sm">
                    <i class="fas fa-trash mr-1"></i> Hapus
                </button>
            </div>
        </div>
    </div>
</div>
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

        const booksTable = $('#booksTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('admin.books.data') }}',
            dom: '<"row align-items-center mb-3"<"col-md-6"l><"col-md-6"f>>rt<"row align-items-center mt-3"<"col-md-5"i><"col-md-7"p>>',
            columns: [
                { data: 'code', name: 'code' },
                { data: 'title', name: 'title' },
                { data: 'publication_year', name: 'publication_year' },
                { data: 'author', name: 'author' },
                { data: 'stock_badge', name: 'stock' },
                { data: 'created_at', name: 'created_at', visible: false, searchable: false },
                { data: 'cover_image_url', name: 'cover_image_url', visible: false, searchable: false, orderable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            pageLength: 5,
            lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
            searchDelay: 500,
            responsive: true,
            autoWidth: false,
            order: [[5, 'desc']],
            language: {
                search: '',
                searchPlaceholder: 'Cari kode, judul, tahun, penulis...',
                lengthMenu: 'Tampilkan _MENU_',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                paginate: { previous: 'Sebelumnya', next: 'Berikutnya' },
                emptyTable: 'Belum ada data',
                zeroRecords: 'Data tidak ditemukan',
                processing: 'Memuat data...'
            },
            initComplete: function () {
                $('#booksTable_length select').addClass('custom-select custom-select-sm');
                $('#booksTable_filter input').addClass('form-control-sm').css('min-width', '260px');
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
            }
        });

        const storeUrl = '{{ route('admin.books.store') }}';
        const updateUrlTemplate = '{{ route('admin.books.update', ['book' => ':id']) }}';
        const deleteUrlTemplate = '{{ route('admin.books.destroy', ['book' => ':id']) }}';
        let formMode = 'create';

        function resetBookForm() {
            formMode = 'create';
            $('#bookModalTitle').text('Tambah Buku');
            $('#bookId').val('');
            $('#bookForm')[0].reset();
            $('#bookFormAlert').addClass('d-none');
            $('#bookFormAlertText').empty();
            clearBookFieldErrors();
            $('#coverPreview').attr('src', '');
            $('#coverPreviewWrap').addClass('d-none');
        }

        function showToast(type, title, message) {
            const isSuccess = type === 'success';

            $(document).Toasts('create', {
                autohide: true,
                autoremove: true,
                delay: 3500,
                position: 'topRight',
                class: isSuccess ? 'bg-success' : 'bg-danger',
                icon: isSuccess ? 'fas fa-circle-check' : 'fas fa-circle-exclamation',
                title: title,
                body: message
            });
        }

        function getBookRowData(button) {
            const $row = $(button).closest('tr');
            const row = $row.hasClass('child') ? $row.prev() : $row;

            return booksTable.row(row).data();
        }

        function clearBookFieldErrors() {
            $('#bookForm .form-control').removeClass('is-invalid');
            $('#bookForm .field-error').remove();
        }

        function showBookFieldErrors(errors) {
            Object.entries(errors).forEach(function ([field, messages]) {
                const $field = $('[name="' + field + '"]');

                $field.addClass('is-invalid');
                $field.after('<p class="field-error text-danger small mb-0 mt-1">' + messages.join('<br>') + '</p>');
            });
        }

        $('#bookForm').on('submit', function (event) {
            event.preventDefault();

            const $button = $('#saveBookBtn');
            const $alert = $('#bookFormAlert');
            const bookId = $('#bookId').val();
            const url = formMode === 'edit'
                ? updateUrlTemplate.replace(':id', bookId)
                : storeUrl;
            const formData = new FormData(this);

            if (formMode === 'edit') {
                formData.append('_method', 'PUT');
            }

            $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan');
            $alert.addClass('d-none');
            $('#bookFormAlertText').empty();
            clearBookFieldErrors();

            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    $('#bookModal').modal('hide');
                    booksTable.ajax.reload(null, false);
                    showToast('success', 'Berhasil', response.message);
                },
                error: function (xhr) {
                    const errors = xhr.responseJSON?.errors;

                    if (errors) {
                        showBookFieldErrors(errors);
                    } else {
                        $('#bookFormAlertText').text(xhr.responseJSON?.message || 'Data buku gagal disimpan.');
                        $alert.removeClass('d-none');
                    }
                },
                complete: function () {
                    $button.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan');
                }
            });
        });

        $('#addBookBtn').on('click', function () {
            resetBookForm();
            $('#bookModal').modal('show');
        });

        $('#booksTable').on('click', '.edit-book', function () {
            const rowData = getBookRowData(this);

            if (! rowData) {
                return;
            }

            resetBookForm();
            formMode = 'edit';
            $('#bookModalTitle').text('Edit Buku');
            $('#bookId').val(rowData.id);
            $('[name="code"]').val(rowData.code);
            $('[name="title"]').val(rowData.title);
            $('[name="publication_year"]').val(rowData.publication_year);
            $('[name="stock"]').val(rowData.stock);
            $('[name="author"]').val(rowData.author);

            if (rowData.cover_image_url) {
                $('#coverPreview').attr('src', rowData.cover_image_url);
                $('#coverPreviewWrap').removeClass('d-none');
            }

            if (rowData.has_active_borrowings) {
                $('#bookFormAlertText').text('Buku sedang dipinjam. Kode buku tidak bisa diubah, tetapi data lainnya masih bisa diperbarui.');
                $('#bookFormAlert').removeClass('d-none');
            }

            $('#bookModal').modal('show');
        });

        $('#coverImageInput').on('change', function () {
            const file = this.files[0];

            if (! file) {
                $('#coverPreview').attr('src', '');
                $('#coverPreviewWrap').addClass('d-none');
                return;
            }

            $('#coverPreview').attr('src', URL.createObjectURL(file));
            $('#coverPreviewWrap').removeClass('d-none');
        });

        $('#booksTable').on('click', '.delete-book', function () {
            const rowData = getBookRowData(this);

            if (! rowData) {
                return;
            }

            $('#deleteBookId').val(rowData.id);
            $('#deleteBookTitle').text(rowData.code + ' - ' + rowData.title);
            $('#deleteBookModal').modal('show');
        });

        $('#confirmDeleteBookBtn').on('click', function () {
            const $button = $(this);
            const bookId = $('#deleteBookId').val();

            $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menghapus');

            $.ajax({
                url: deleteUrlTemplate.replace(':id', bookId),
                method: 'DELETE',
                success: function (response) {
                    $('#deleteBookModal').modal('hide');
                    booksTable.ajax.reload(null, false);
                    showToast('success', 'Berhasil', response.message);
                },
                error: function (xhr) {
                    const errors = xhr.responseJSON?.errors;
                    const message = errors
                        ? Object.values(errors).flat().join('<br>')
                        : (xhr.responseJSON?.message || 'Data buku gagal dihapus.');

                    $('#deleteBookModal').modal('hide');
                    showToast('error', 'Gagal', message);
                },
                complete: function () {
                    $button.prop('disabled', false).html('<i class="fas fa-trash mr-1"></i> Hapus');
                }
            });
        });
    });
</script>
@endpush
