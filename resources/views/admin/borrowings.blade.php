@extends('layouts.adminlte', ['title' => 'Peminjaman Buku', 'heading' => 'Peminjaman Buku'])

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Peminjaman Aktif dan Riwayat</h3>
    </div>
    <div class="card-body">
        <table id="borrowingsTable" class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>ID Anggota</th>
                <th>Anggota</th>
                <th>Buku</th>
                <th>Pinjam</th>
                <th>Rencana Kembali</th>
                <th>Kembali Aktual</th>
                <th>Status</th>
                <th style="width:130px;">Aksi</th>
            </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="returnBookModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Pengembalian</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="returnBorrowingId">
                <div class="alert alert-success border-0 shadow-sm">
                    <i class="fas fa-rotate-left mr-2"></i>
                    Tandai buku ini sudah dikembalikan? Stok buku akan bertambah 1.
                </div>
                <dl class="row mb-0">
                    <dt class="col-sm-4">ID Anggota</dt>
                    <dd id="returnMemberCode" class="col-sm-8"></dd>
                    <dt class="col-sm-4">Anggota</dt>
                    <dd id="returnMemberName" class="col-sm-8"></dd>
                    <dt class="col-sm-4">Buku</dt>
                    <dd id="returnBookLabel" class="col-sm-8"></dd>
                    <dt class="col-sm-4">Stok Saat Ini</dt>
                    <dd id="returnBookStock" class="col-sm-8"></dd>
                    <dt class="col-sm-4">Tanggal</dt>
                    <dd id="returnDates" class="col-sm-8"></dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" id="confirmReturnBookBtn" class="btn btn-success">
                    <i class="fas fa-rotate-left mr-1"></i> Returned
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

        const returnUrlTemplate = '{{ route('admin.borrowings.returned', ['borrowing' => ':id']) }}';

        const borrowingsTable = $('#borrowingsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('admin.borrowings.data') }}',
            dom: '<"row align-items-center mb-3"<"col-md-6"l><"col-md-6"f>>rt<"row align-items-center mt-3"<"col-md-5"i><"col-md-7"p>>',
            columns: [
                { data: 'member_code', name: 'member_code', orderable: false },
                { data: 'member_name', name: 'member_name', orderable: false },
                { data: 'book_label', name: 'book_label', orderable: false },
                { data: 'borrow_date', name: 'borrow_date' },
                { data: 'return_date', name: 'return_date' },
                { data: 'actual_return_date', name: 'actual_return_date' },
                { data: 'status_badge', name: 'status' },
                { data: 'created_at', name: 'created_at', visible: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            pageLength: 5,
            lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
            searchDelay: 500,
            responsive: true,
            autoWidth: false,
            order: [[7, 'desc']],
            language: {
                search: '',
                searchPlaceholder: 'Cari ID, anggota, buku, status...',
                lengthMenu: 'Tampilkan _MENU_',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                paginate: { previous: 'Sebelumnya', next: 'Berikutnya' },
                emptyTable: 'Belum ada peminjaman',
                zeroRecords: 'Data tidak ditemukan',
                processing: 'Memuat data...'
            },
            initComplete: function () {
                $('#borrowingsTable_length select').addClass('custom-select custom-select-sm');
                $('#borrowingsTable_filter input').addClass('form-control-sm').css('min-width', '260px');
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
            }
        });

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

        function getBorrowingRowData(button) {
            const $row = $(button).closest('tr');
            const row = $row.hasClass('child') ? $row.prev() : $row;

            return borrowingsTable.row(row).data();
        }

        $('#borrowingsTable').on('click', '.mark-returned', function () {
            const rowData = getBorrowingRowData(this);

            if (! rowData) {
                return;
            }

            $('#returnBorrowingId').val(rowData.id);
            $('#returnMemberCode').text(rowData.member_code);
            $('#returnMemberName').text(rowData.member_name);
            $('#returnBookLabel').text(rowData.book_label);
            $('#returnBookStock').text(rowData.book_stock);
            $('#returnDates').text(rowData.borrow_date + ' sampai ' + rowData.return_date);
            $('#returnBookModal').modal('show');
        });

        $('#confirmReturnBookBtn').on('click', function () {
            const $button = $(this);
            const borrowingId = $('#returnBorrowingId').val();

            $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Memproses');

            $.ajax({
                url: returnUrlTemplate.replace(':id', borrowingId),
                method: 'POST',
                success: function (response) {
                    $('#returnBookModal').modal('hide');
                    borrowingsTable.ajax.reload(null, false);
                    showToast('success', 'Berhasil', response.message);
                },
                error: function (xhr) {
                    const errors = xhr.responseJSON?.errors;
                    const message = errors
                        ? Object.values(errors).flat().join('<br>')
                        : (xhr.responseJSON?.message || 'Status peminjaman gagal diperbarui.');

                    $('#returnBookModal').modal('hide');
                    showToast('error', 'Gagal', message);
                },
                complete: function () {
                    $button.prop('disabled', false).html('<i class="fas fa-rotate-left mr-1"></i> Returned');
                }
            });
        });
    });
</script>
@endpush
