<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>App Settings - GymApp Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-link nav-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </li>
        </ul>
    </nav>

    <!-- Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="/admin/dashboard" class="brand-link">
            <span class="brand-text font-weight-light">GymApp Admin</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <li class="nav-item">
                        <a href="/admin/dashboard" class="nav-link">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/admin/users" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Users</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/admin/videos" class="nav-link">
                            <i class="nav-icon fas fa-video"></i>
                            <p>Videos</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/admin/support" class="nav-link">
                            <i class="nav-icon fas fa-envelope"></i>
                            <p>Support Messages</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/admin/settings" class="nav-link active">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>App Settings</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/admin/notifications" class="nav-link">
                            <i class="nav-icon fas fa-bell"></i>
                            <p>Push Notifications</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content -->
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <h1 class="m-0">App Settings</h1>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                @endif

                <!-- About Section -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle"></i> About App</h3>
                    </div>
                    <form action="/admin/settings/update" method="POST">
                        @csrf
                        <input type="hidden" name="key" value="about">
                        <div class="card-body">
                            <div class="form-group">
                                <label>About Content</label>
                                <textarea name="value" class="form-control" rows="10" required>{{ $about }}</textarea>
                                <small class="form-text text-muted">This content will be displayed in the About section of the app.</small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save About Content
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Privacy Policy Section -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-shield-alt"></i> Privacy & Security Policy</h3>
                    </div>
                    <form action="/admin/settings/update" method="POST">
                        @csrf
                        <input type="hidden" name="key" value="privacy_policy">
                        <div class="card-body">
                            <div class="form-group">
                                <label>Privacy Policy Content</label>
                                <textarea name="value" class="form-control" rows="15" required>{{ $privacy }}</textarea>
                                <small class="form-text text-muted">This content will be displayed in the Privacy & Security section of the app.</small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Privacy Policy
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
