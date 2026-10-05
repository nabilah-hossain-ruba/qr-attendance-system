@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="bi bi-file-earmark-spreadsheet"></i> Import Courses from CSV</h4>
                    <a href="{{ route('admin.courses') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Courses
                    </a>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Instructions -->
                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle"></i> CSV Import Instructions</h6>
                        <p class="mb-2">This import will extract only the following fields from your CSV file:</p>
                        <ul class="mb-2">
                            <li><strong>Course</strong> - Course code (e.g., ACT201)</li>
                            <li><strong>Section</strong> - Section number (e.g., 01, 02)</li>
                            <li><strong>Faculty</strong> - Faculty initials (e.g., SHO, HMF)</li>
                        </ul>
                        <p class="mb-0">
                            <small class="text-muted">
                                The system will automatically create lecturers if they don't exist and generate course names based on the course codes.
                            </small>
                        </p>
                    </div>

                    <!-- Upload Form -->
                    <form id="csvUploadForm" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="csv_file" class="form-label">Select CSV File</label>
                                    <input type="file" class="form-control" id="csv_file" name="csv_file" 
                                           accept=".csv,.txt" required>
                                    <div class="form-text">
                                        Accepted formats: .csv, .txt (Maximum size: 2MB)
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="mb-3">
                                    <button type="button" class="btn btn-primary" id="previewBtn">
                                        <i class="bi bi-eye"></i> Preview Data
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Loading Spinner -->
                    <div id="loadingSpinner" class="text-center d-none">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Processing CSV file...</p>
                    </div>

                    <!-- Preview Section -->
                    <div id="previewSection" class="d-none">
                        <hr>
                        <h5><i class="bi bi-table"></i> Data Preview</h5>
                        <div id="previewInfo" class="mb-3"></div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="previewTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Course Code</th>
                                        <th>Section</th>
                                        <th>Faculty Initial</th>
                                        <th>Generated Course Name</th>
                                    </tr>
                                </thead>
                                <tbody id="previewTableBody">
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Import Confirmation -->
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Please review the preview above before importing.</strong>
                            This action will create new courses and lecturers in the database.
                        </div>
                        
                        <form action="{{ route('admin.import-csv') }}" method="POST" enctype="multipart/form-data" id="importForm">
                            @csrf
                            <input type="file" name="csv_file" id="hiddenCsvFile" style="display: none;" required>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-download"></i> Import Data
                                </button>
                                <button type="button" class="btn btn-secondary" id="cancelBtn">
                                    <i class="bi bi-x"></i> Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const previewBtn = document.getElementById('previewBtn');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const previewSection = document.getElementById('previewSection');
    const csvFile = document.getElementById('csv_file');
    const hiddenCsvFile = document.getElementById('hiddenCsvFile');
    const cancelBtn = document.getElementById('cancelBtn');

    previewBtn.addEventListener('click', function() {
        if (!csvFile.files[0]) {
            alert('Please select a CSV file first.');
            return;
        }

        const formData = new FormData();
        formData.append('csv_file', csvFile.files[0]);
        formData.append('_token', document.querySelector('input[name="_token"]').value);

        // Show loading
        loadingSpinner.classList.remove('d-none');
        previewSection.classList.add('d-none');

        fetch('{{ route("admin.preview-csv") }}', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            loadingSpinner.classList.add('d-none');
            
            if (data.success) {
                displayPreview(data);
                // Copy file to hidden input for import form
                hiddenCsvFile.files = csvFile.files;
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            loadingSpinner.classList.add('d-none');
            alert('Error processing file: ' + error.message);
        });
    });

    function displayPreview(data) {
        const previewInfo = document.getElementById('previewInfo');
        const tableBody = document.getElementById('previewTableBody');

        previewInfo.innerHTML = `
            <div class="row">
                <div class="col-md-4">
                    <div class="badge bg-primary fs-6">
                        Total Rows: ${data.total_rows}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="badge bg-success fs-6">
                        Valid Rows: ${data.valid_rows}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="badge bg-warning fs-6">
                        Preview: ${data.preview.length} rows
                    </div>
                </div>
            </div>
        `;

        tableBody.innerHTML = '';
        data.preview.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><code>${row.course_code}</code></td>
                <td><span class="badge bg-secondary">${row.section}</span></td>
                <td><strong>${row.faculty_initial}</strong></td>
                <td>${row.generated_course_name}</td>
            `;
            tableBody.appendChild(tr);
        });

        previewSection.classList.remove('d-none');
    }

    cancelBtn.addEventListener('click', function() {
        previewSection.classList.add('d-none');
        csvFile.value = '';
        hiddenCsvFile.value = '';
    });

    // Handle import form submission
    document.getElementById('importForm').addEventListener('submit', function(e) {
        if (!confirm('Are you sure you want to import this data? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
});
</script>

<style>
.table th {
    background-color: #343a40 !important;
    color: white !important;
}

.badge {
    font-size: 0.9em;
}

code {
    background-color: #f8f9fa;
    color: #e83e8c;
    padding: 2px 4px;
    border-radius: 3px;
}
</style>
@endsection
