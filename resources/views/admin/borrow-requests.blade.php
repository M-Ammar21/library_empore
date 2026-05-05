@extends('layouts.adminlte', ['title' => 'Pengajuan Peminjaman', 'heading' => 'Pengajuan Peminjaman'])

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Pengajuan</h3>
    </div>
    <div class="card-body">
        <table id="borrowRequestsTable" class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>ID Anggota</th>
                <th>Anggota</th>
                <th>Buku</th>
                <th>Pinjam</th>
                <th>Kembali</th>
                <th>Status</th>
                <th style="width:170px;">Aksi</th>
            </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="processRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="processRequestTitle" class="modal-title">Proses Pengajuan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="processRequestId">
                <input type="hidden" id="processRequestAction">
                <div id="processRequestAlert" class="alert alert-warning border-0 shadow-sm mb-3">
                    <i id="processRequestIcon" class="fas fa-circle-question mr-2"></i>
                    <span id="processRequestMessage"></span>
                </div>
                <dl class="row mb-0">
                    <dt class="col-sm-4">ID Anggota</dt>
                    <dd id="processMemberCode" class="col-sm-8"></dd>
                    <dt class="col-sm-4">Anggota</dt>
                    <dd id="processMemberName" class="col-sm-8"></dd>
                    <dt class="col-sm-4">Buku</dt>
                    <dd id="processBookLabel" class="col-sm-8"></dd>
                    <dt class="col-sm-4">Stok Saat Ini</dt>
                    <dd id="processBookStock" class="col-sm-8"></dd>
                    <dt class="col-sm-4">Tanggal</dt>
                    <dd id="processDates" class="col-sm-8"></dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" id="confirmProcessRequestBtn" class="btn btn-primary">
                    Proses
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

        const approveUrlTemplate = '{{ route('admin.borrow-requests.approve', ['borrowRequest' => ':id']) }}';
        const rejectUrlTemplate = '{{ route('admin.borrow-requests.reject', ['borrowRequest' => ':id']) }}';

        const borrowRequestsTable = $('#borrowRequestsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('admin.borrow-requests.data') }}',
            dom: '<"row align-items-center mb-3"<"col-md-6"l><"col-md-6"f>>rt<"row align-items-center mt-3"<"col-md-5"i><"col-md-7"p>>',
            columns: [
                { data: 'member_code', name: 'member_code', orderable: false },
                { data: 'member_name', name: 'member_name', orderable: false },
                { data: 'book_label', name: 'book_label', orderable: false },
                { data: 'borrow_date', name: 'borrow_date' },
                { data: 'return_date', name: 'return_date' },
                { data: 'status_badge', name: 'status' },
                { data: 'created_at', name: 'created_at', visible: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            pageLength: 5,
            lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
            searchDelay: 500,
            responsive: true,
            autoWidth: false,
            order: [[6, 'desc']],
            language: {
                search: '',
                searchPlaceholder: 'Cari ID, anggota, buku, status...',
                lengthMenu: 'Tampilkan _MENU_',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                paginate: { previous: 'Sebelumnya', next: 'Berikutnya' },
                emptyTable: 'Belum ada pengajuan',
                zeroRecords: 'Data tidak ditemukan',
                processing: 'Memuat data...'
            },
            initComplete: function () {
                $('#borrowRequestsTable_length select').addClass('custom-select custom-select-sm');
                $('#borrowRequestsTable_filter input').addClass('form-control-sm').css('min-width', '260px');
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

        function getRequestRowData(button) {
            const $row = $(button).closest('tr');
            const row = $row.hasClass('child') ? $row.prev() : $row;

            return borrowRequestsTable.row(row).data();
        }

        function openProcessModal(action, rowData) {
            const isApprove = action === 'approve';

            $('#processRequestId').val(rowData.id);
            $('#processRequestAction').val(action);
            $('#processRequestTitle').text(isApprove ? 'Approve Pengajuan' : 'Reject Pengajuan');
            $('#processRequestMessage').text(isApprove
                ? 'Setujui pengajuan ini? Stok buku akan berkurang 1 dan data peminjaman akan dibuat.'
                : 'Tolak pengajuan ini? Stok buku tidak akan berubah.');
            $('#processRequestIcon')
                .removeClass('fa-circle-question fa-check-circle fa-xmark-circle')
                .addClass(isApprove ? 'fa-check-circle' : 'fa-xmark-circle');
            $('#processRequestAlert')
                .removeClass('alert-warning alert-success alert-danger')
                .addClass(isApprove ? 'alert-success' : 'alert-danger');
            $('#processMemberCode').text(rowData.member_code);
            $('#processMemberName').text(rowData.member_name);
            $('#processBookLabel').text(rowData.book_label);
            $('#processBookStock').text(rowData.book_stock);
            $('#processDates').text(rowData.borrow_date + ' sampai ' + rowData.return_date);
            $('#confirmProcessRequestBtn')
                .removeClass('btn-primary btn-success btn-danger')
                .addClass(isApprove ? 'btn-success' : 'btn-danger')
                .html(isApprove
                    ? '<i class="fas fa-check mr-1"></i> Approve'
                    : '<i class="fas fa-xmark mr-1"></i> Reject');

            $('#processRequestModal').modal('show');
        }

        $('#borrowRequestsTable').on('click', '.approve-request', function () {
            const rowData = getRequestRowData(this);

            if (rowData) {
                openProcessModal('approve', rowData);
            }
        });

        $('#borrowRequestsTable').on('click', '.reject-request', function () {
            const rowData = getRequestRowData(this);

            if (rowData) {
                openProcessModal('reject', rowData);
            }
        });

        $('#confirmProcessRequestBtn').on('click', function () {
            const $button = $(this);
            const action = $('#processRequestAction').val();
            const requestId = $('#processRequestId').val();
            const url = action === 'approve'
                ? approveUrlTemplate.replace(':id', requestId)
                : rejectUrlTemplate.replace(':id', requestId);

            $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Memproses');

            $.ajax({
                url: url,
                method: 'POST',
                success: function (response) {
                    $('#processRequestModal').modal('hide');
                    borrowRequestsTable.ajax.reload(null, false);
                    showToast('success', 'Berhasil', response.message);
                },
                error: function (xhr) {
                    const errors = xhr.responseJSON?.errors;
                    const message = errors
                        ? Object.values(errors).flat().join('<br>')
                        : (xhr.responseJSON?.message || 'Pengajuan gagal diproses.');

                    $('#processRequestModal').modal('hide');
                    showToast('error', 'Gagal', message);
                },
                complete: function () {
                    $button.prop('disabled', false);
                }
            });
        });
    });
</script>
@endpush
