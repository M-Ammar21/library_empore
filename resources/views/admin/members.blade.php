@extends("layouts.adminlte", ["title" => "Data Anggota", "heading" => "Data Anggota"])

@section("content")
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Anggota Perpustakaan</h3>
            <div class="card-tools">
                <button id="addMemberBtn" class="btn btn-primary btn-sm" type="button">
                    <i class="fas fa-user-plus mr-1"></i> Tambah Anggota
                </button>
            </div>
        </div>
        <div class="card-body">
            <table id="membersTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID Anggota</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Telepon</th>
                        <th>Alamat</th>
                        <th style="width:110px;">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="memberModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="memberModalTitle" class="modal-title">Form Anggota</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="memberForm">
                    <input type="hidden" id="memberId" name="member_id">
                    <div class="modal-body">
                        <div id="memberFormAlert" class="alert alert-danger d-none border-0 shadow-sm">
                            <div class="d-flex">
                                <i class="fas fa-circle-exclamation mt-1 mr-2"></i>
                                <div>
                                    <strong class="d-block">Data belum bisa disimpan</strong>
                                    <div id="memberFormAlertText"></div>
                                </div>
                            </div>
                        </div>

                        <div id="memberCodeGroup" class="form-group d-none">
                            <label>ID Anggota</label>
                            <input type="text" name="member_code" class="form-control" readonly>
                            <span class="form-hint">ID ini dibuat otomatis dari nama, tanggal, dan nomor data.</span>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Nama</label>
                                <input type="text" name="name" class="form-control" placeholder="Nama anggota">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" placeholder="email@example.test">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Password</label>
                                <div class="input-group">
                                    <input id="memberPassword" type="password" name="password" class="form-control"
                                        placeholder="Isi saat membuat anggota baru">
                                    <div class="input-group-append">
                                        <button id="toggleMemberPassword" class="btn btn-outline-secondary" type="button"
                                            data-toggle="tooltip" title="Lihat password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <span id="passwordHint" class="form-hint">Wajib diisi untuk anggota baru.</span>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Nomor Telepon</label>
                                <input type="text" name="phone" class="form-control" placeholder="08xx">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Alamat</label>
                            <textarea name="address" class="form-control" rows="3" placeholder="Alamat lengkap"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" id="saveMemberBtn" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteMemberModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Hapus Anggota</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="deleteMemberId">
                    <p class="mb-1">Yakin ingin menghapus anggota ini?</p>
                    <strong id="deleteMemberName" class="d-block text-danger"></strong>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <button type="button" id="confirmDeleteMemberBtn" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash mr-1"></i> Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push("scripts")
    <script>
        $(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                }
            });

            const membersTable = $('#membersTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route("admin.members.data") }}',
                dom: '<"row align-items-center mb-3"<"col-md-6"l><"col-md-6"f>>rt<"row align-items-center mt-3"<"col-md-5"i><"col-md-7"p>>',
                columns: [{
                        data: 'member_code',
                        name: 'member_code'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'phone',
                        name: 'phone',
                        defaultContent: '-'
                    },
                    {
                        data: 'address',
                        name: 'address',
                        defaultContent: '-'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        visible: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                pageLength: 5,
                lengthMenu: [
                    [5, 10, 25, 50],
                    [5, 10, 25, 50]
                ],
                searchDelay: 500,
                responsive: true,
                autoWidth: false,
                order: [
                    [5, 'desc']
                ],
                language: {
                    search: '',
                    searchPlaceholder: 'Cari ID, nama, email, telepon, alamat...',
                    lengthMenu: 'Tampilkan _MENU_',
                    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                    paginate: {
                        previous: 'Sebelumnya',
                        next: 'Berikutnya'
                    },
                    emptyTable: 'Belum ada data',
                    zeroRecords: 'Data tidak ditemukan',
                    processing: 'Memuat data...'
                },
                initComplete: function() {
                    $('#membersTable_length select').addClass('custom-select custom-select-sm');
                    $('#membersTable_filter input').addClass('form-control-sm').css('min-width',
                        '260px');
                },
                drawCallback: function() {
                    $('[data-toggle="tooltip"]').tooltip();
                }
            });

            const storeUrl = '{{ route("admin.members.store") }}';
            const updateUrlTemplate = '{{ route("admin.members.update", ["member" => ":id"]) }}';
            const deleteUrlTemplate = '{{ route("admin.members.destroy", ["member" => ":id"]) }}';
            let formMode = 'create';

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

            function resetMemberForm() {
                formMode = 'create';
                $('#memberModalTitle').text('Tambah Anggota');
                $('#memberId').val('');
                $('#memberForm')[0].reset();
                $('#memberPassword').attr('type', 'password');
                $('#toggleMemberPassword').attr('title', 'Lihat password').find('i').removeClass('fa-eye-slash')
                    .addClass('fa-eye');
                $('#memberFormAlert').addClass('d-none');
                $('#memberFormAlertText').empty();
                clearMemberFieldErrors();
                $('#memberCodeGroup').addClass('d-none');
                $('#passwordHint').text('Wajib diisi untuk anggota baru.');
            }

            function getMemberRowData(button) {
                const $row = $(button).closest('tr');
                const row = $row.hasClass('child') ? $row.prev() : $row;

                return membersTable.row(row).data();
            }

            function clearMemberFieldErrors() {
                $('#memberForm .form-control').removeClass('is-invalid');
                $('#memberForm .field-error').remove();
            }

            function showMemberFieldErrors(errors) {
                Object.entries(errors).forEach(function([field, messages]) {
                    const $field = $('[name="' + field + '"]');

                    $field.addClass('is-invalid');
                    $field.after('<p class="field-error text-danger small mb-0 mt-1">' + messages.join(
                        '<br>') + '</p>');
                });
            }

            $('#addMemberBtn').on('click', function() {
                resetMemberForm();
                $('#memberModal').modal('show');
            });

            $('#toggleMemberPassword').on('click', function() {
                const $password = $('#memberPassword');
                const isHidden = $password.attr('type') === 'password';

                $password.attr('type', isHidden ? 'text' : 'password');
                $(this)
                    .attr('title', isHidden ? 'Sembunyikan password' : 'Lihat password')
                    .find('i')
                    .toggleClass('fa-eye', !isHidden)
                    .toggleClass('fa-eye-slash', isHidden);
            });

            $('#membersTable').on('click', '.edit-member', function() {
                const rowData = getMemberRowData(this);

                if (!rowData) {
                    return;
                }

                resetMemberForm();
                formMode = 'edit';
                $('#memberModalTitle').text('Edit Anggota');
                $('#memberId').val(rowData.id);
                $('[name="member_code"]').val(rowData.member_code);
                $('[name="name"]').val(rowData.name);
                $('[name="email"]').val(rowData.email);
                $('[name="phone"]').val(rowData.phone);
                $('[name="address"]').val(rowData.address);
                $('[name="password"]').val('');
                $('#memberCodeGroup').removeClass('d-none');
                $('#passwordHint').text('Kosongkan jika tidak ingin mengganti password.');
                $('#memberModal').modal('show');
            });

            $('#memberForm').on('submit', function(event) {
                event.preventDefault();

                const $button = $('#saveMemberBtn');
                const $alert = $('#memberFormAlert');
                const memberId = $('#memberId').val();
                const url = formMode === 'edit' ?
                    updateUrlTemplate.replace(':id', memberId) :
                    storeUrl;
                const method = formMode === 'edit' ? 'PUT' : 'POST';

                $button.prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan');
                $alert.addClass('d-none');
                $('#memberFormAlertText').empty();
                clearMemberFieldErrors();

                $.ajax({
                    url: url,
                    method: method,
                    data: $(this).serialize(),
                    success: function(response) {
                        $('#memberModal').modal('hide');
                        membersTable.ajax.reload(null, false);
                        showToast('success', 'Berhasil', response.message);
                    },
                    error: function(xhr) {
                        const errors = xhr.responseJSON?.errors;

                        if (errors) {
                            showMemberFieldErrors(errors);
                        } else {
                            $('#memberFormAlertText').text(xhr.responseJSON?.message ||
                                'Data anggota gagal disimpan.');
                            $alert.removeClass('d-none');
                        }
                    },
                    complete: function() {
                        $button.prop('disabled', false).html(
                            '<i class="fas fa-save mr-1"></i> Simpan');
                    }
                });
            });

            $('#membersTable').on('click', '.delete-member', function() {
                const rowData = getMemberRowData(this);

                if (!rowData) {
                    return;
                }

                $('#deleteMemberId').val(rowData.id);
                $('#deleteMemberName').text(rowData.member_code + ' - ' + rowData.name);
                $('#deleteMemberModal').modal('show');
            });

            $('#confirmDeleteMemberBtn').on('click', function() {
                const $button = $(this);
                const memberId = $('#deleteMemberId').val();

                $button.prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin mr-1"></i> Menghapus');

                $.ajax({
                    url: deleteUrlTemplate.replace(':id', memberId),
                    method: 'DELETE',
                    success: function(response) {
                        $('#deleteMemberModal').modal('hide');
                        membersTable.ajax.reload(null, false);
                        showToast('success', 'Berhasil', response.message);
                    },
                    error: function(xhr) {
                        const errors = xhr.responseJSON?.errors;
                        const message = errors ?
                            Object.values(errors).flat().join('<br>') :
                            (xhr.responseJSON?.message || 'Data anggota gagal dihapus.');

                        $('#deleteMemberModal').modal('hide');
                        showToast('error', 'Gagal', message);
                    },
                    complete: function() {
                        $button.prop('disabled', false).html(
                            '<i class="fas fa-trash mr-1"></i> Hapus');
                    }
                });
            });
        });
    </script>
@endpush
