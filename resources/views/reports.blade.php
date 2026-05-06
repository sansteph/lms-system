@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-9 col-lg-10 p-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Reports</h2>

                <button class="btn btn-primary">
                    Export Report
                </button>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Student Reports</h6>
                        <h2>120</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Teacher Reports</h6>
                        <h2>25</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Class Reports</h6>
                        <h2>10</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>MIS Reports</h6>
                        <h2>8</h2>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Generate Report</h5>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <select class="form-control">
                                <option>Select Report Type</option>
                                <option>Student Performance</option>
                                <option>Teacher Performance</option>
                                <option>Class-wise Report</option>
                                <option>MIS Report</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <select class="form-control">
                                <option>Select Institute</option>
                                <option>ABC School</option>
                                <option>Bright Future Academy</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <select class="form-control">
                                <option>Select Class</option>
                                <option>VIII - A</option>
                                <option>IX - B</option>
                                <option>X - A</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <input type="date" class="form-control">
                        </div>

                        <div class="col-md-2">
                            <button class="btn btn-success w-100">Generate</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <input type="text" class="form-control mb-3" placeholder="Search reports...">

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Sl. No</th>
                                <th>Report Name</th>
                                <th>Report Type</th>
                                <th>Institute</th>
                                <th>Class</th>
                                <th>Generated Date</th>
                                <th width="180">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>VIII-A Student Performance</td>
                                <td><span class="badge bg-primary">Student</span></td>
                                <td>ABC School</td>
                                <td>VIII - A</td>
                                <td>05-05-2026</td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#">View</a></li>
                                            <li><a class="dropdown-item" href="#">Download</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" onclick="confirmDelete()">Delete</a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>2</td>
                                <td>Teacher Monthly Performance</td>
                                <td><span class="badge bg-warning text-dark">Teacher</span></td>
                                <td>ABC School</td>
                                <td>All</td>
                                <td>05-05-2026</td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown"> 
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#">View</a></li>
                                            <li><a class="dropdown-item" href="#">Download</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" onclick="confirmDelete()">Delete</a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete() {
    if(confirm("Are you sure you want to delete this report?")) {
        alert("Deleted (UI only)");
    }
}
</script>

@endsection