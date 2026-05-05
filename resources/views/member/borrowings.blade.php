@extends('layouts.adminlte', ['title' => 'Pinjaman Saya', 'heading' => 'Pinjaman Saya', 'mode' => 'member'])

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Riwayat Peminjaman Saya</h3>
    </div>
    <div class="card-body">
        <div class="form-group mb-3">
            <label>Anggota</label>
            <div class="form-control bg-light">{{ $member->member_code }} - {{ $member->name }}</div>
            <div class="form-hint mt-1">Riwayat ditampilkan dari akun anggota yang sedang login.</div>
        </div>

        <table id="memberBorrowingsTable" class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>Buku</th>
                <th>Pinjam</th>
                <th>Rencana Kembali</th>
                <th>Kembali Aktual</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        const borrowingsTable = $('#memberBorrowingsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('member.borrowings.data') }}',
            dom: '<"row align-items-center mb-3"<"col-md-6"l><"col-md-6"f>>rt<"row align-items-center mt-3"<"col-md-5"i><"col-md-7"p>>',
            columns: [
                { data: 'book_label', name: 'book_label', orderable: false },
                { data: 'borrow_date', name: 'borrow_date' },
                { data: 'return_date', name: 'return_date' },
                { data: 'actual_return_date', name: 'actual_return_date' },
                { data: 'status_badge', name: 'status' },
                { data: 'created_at', name: 'created_at', visible: false, searchable: false }
            ],
            pageLength: 5,
            lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
            searchDelay: 500,
            responsive: true,
            autoWidth: false,
            order: [[5, 'desc']],
            language: {
                search: '',
                searchPlaceholder: 'Cari buku, tanggal, status...',
                lengthMenu: 'Tampilkan _MENU_',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                paginate: { previous: 'Sebelumnya', next: 'Berikutnya' },
                emptyTable: 'Belum ada riwayat peminjaman',
                zeroRecords: 'Data tidak ditemukan',
                processing: 'Memuat data...'
            },
            initComplete: function () {
                $('#memberBorrowingsTable_length select').addClass('custom-select custom-select-sm');
                $('#memberBorrowingsTable_filter input').addClass('form-control-sm').css('min-width', '260px');
            }
        });
    });
</script>
@endpush
