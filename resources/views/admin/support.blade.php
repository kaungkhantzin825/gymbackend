<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Support Messages - GymApp Admin</title>
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
                        <a href="/admin/support" class="nav-link active">
                            <i class="nav-icon fas fa-envelope"></i>
                            <p>Support Messages</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/admin/settings" class="nav-link">
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
                <h1 class="m-0">Support Messages</h1>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">All Support Messages</h3>
                            </div>
                            <div class="card-body table-responsive p-0">
                                <table class="table table-hover text-nowrap">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>User</th>
                                            <th>Subject</th>
                                            <th>Message</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($messages as $message)
                                        <tr>
                                            <td>{{ $message->id }}</td>
                                            <td>{{ $message->user->name }}</td>
                                            <td>{{ $message->subject }}</td>
                                            <td>{{ Str::limit($message->message, 50) }}</td>
                                            <td>
                                                @if($message->status === 'pending')
                                                    <span class="badge badge-warning">Pending</span>
                                                @elseif($message->status === 'read')
                                                    <span class="badge badge-info">Read</span>
                                                @else
                                                    <span class="badge badge-success">Replied</span>
                                                @endif
                                            </td>
                                            <td>{{ $message->created_at->format('M d, Y') }}</td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="viewMessage({{ $message->id }})">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="7" class="text-center">No support messages yet</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View/Reply Modal -->
<div class="modal fade" id="messageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Support Message</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content loaded via JavaScript -->
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
function viewMessage(id) {
    fetch(`/admin/support/${id}`)
        .then(response => response.json())
        .then(data => {
            const html = `
                <div class="mb-3">
                    <strong>From:</strong> ${data.user.name} (${data.user.email})<br>
                    <strong>Subject:</strong> ${data.subject}<br>
                    <strong>Date:</strong> ${new Date(data.created_at).toLocaleString()}<br>
                    <strong>Status:</strong> <span class="badge badge-${data.status === 'pending' ? 'warning' : data.status === 'read' ? 'info' : 'success'}">${data.status}</span>
                </div>
                <div class="mb-3">
                    <strong>Message:</strong>
                    <div class="border p-3 rounded">${data.message}</div>
                </div>
                ${data.admin_reply ? `
                    <div class="mb-3">
                        <strong>Your Reply:</strong>
                        <div class="border p-3 rounded bg-light">${data.admin_reply}</div>
                    </div>
                ` : ''}
                <div class="mb-3">
                    <strong>Reply:</strong>
                    <textarea class="form-control" id="replyText" rows="4" placeholder="Type your reply here...">${data.admin_reply || ''}</textarea>
                </div>
                <button class="btn btn-primary" onclick="sendReply(${id})">
                    <i class="fas fa-paper-plane"></i> Send Reply
                </button>
            `;
            document.getElementById('modalBody').innerHTML = html;
            $('#messageModal').modal('show');
        });
}

function sendReply(id) {
    const reply = document.getElementById('replyText').value;
    if (!reply) {
        alert('Please enter a reply');
        return;
    }

    fetch(`/admin/support/${id}/reply`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ reply })
    })
    .then(response => response.json())
    .then(data => {
        alert('Reply sent successfully!');
        $('#messageModal').modal('hide');
        location.reload();
    })
    .catch(error => {
        alert('Error sending reply');
    });
}
</script>
</body>
</html>
