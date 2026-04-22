<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Dashboard - GymApp</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        .header h1 {
            color: #667eea;
            font-size: 32px;
            margin-bottom: 10px;
        }
        .header p {
            color: #666;
            font-size: 16px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }
        .notification-form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        .notification-form h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .users-table {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow-x: auto;
        }
        .users-table h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #f8f9fa;
            color: #333;
            font-weight: 600;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge.online {
            background: #d4edda;
            color: #155724;
        }
        .badge.offline {
            background: #f8d7da;
            color: #721c24;
        }
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-small:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏋️ GymApp Admin Dashboard</h1>
            <p>Manage users and send push notifications</p>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="number">{{ $totalUsers }}</div>
            </div>
            <div class="stat-card">
                <h3>Active Devices</h3>
                <div class="number">{{ $activeDevices }}</div>
            </div>
            <div class="stat-card">
                <h3>Total Meals</h3>
                <div class="number">{{ $totalMeals }}</div>
            </div>
            <div class="stat-card">
                <h3>Notifications Sent</h3>
                <div class="number" id="notificationCount">0</div>
            </div>
        </div>

        <div class="notification-form">
            <h2>📢 Send Push Notification</h2>
            <div id="alert" class="alert"></div>
            
            <form id="notificationForm">
                @csrf
                <div class="form-group">
                    <label for="recipient">Send To:</label>
                    <select id="recipient" name="recipient" required>
                        <option value="all">All Users</option>
                        <option value="specific">Specific User</option>
                    </select>
                </div>

                <div class="form-group" id="userSelectGroup" style="display: none;">
                    <label for="user_id">Select User:</label>
                    <select id="user_id" name="user_id">
                        <option value="">Choose a user...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="title">Notification Title:</label>
                    <input type="text" id="title" name="title" placeholder="e.g., New Feature Available!" required>
                </div>

                <div class="form-group">
                    <label for="body">Notification Message:</label>
                    <textarea id="body" name="body" placeholder="Enter your notification message here..." required></textarea>
                </div>

                <button type="submit" class="btn" id="sendBtn">
                    Send Notification
                </button>
            </form>
        </div>

        <div class="users-table">
            <h2>👥 Registered Users</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Device</th>
                        <th>FCM Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->device_type ?? 'N/A' }}</td>
                        <td>
                            @if($user->fcm_token)
                                <span class="badge online">Active</span>
                            @else
                                <span class="badge offline">Inactive</span>
                            @endif
                        </td>
                        <td>
                            @if($user->fcm_token)
                                <button class="btn-small" onclick="sendToUser({{ $user->id }}, '{{ $user->name }}')">
                                    Send Notification
                                </button>
                            @else
                                <span style="color: #999;">No device</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const recipientSelect = document.getElementById('recipient');
        const userSelectGroup = document.getElementById('userSelectGroup');
        const notificationForm = document.getElementById('notificationForm');
        const alertBox = document.getElementById('alert');
        const sendBtn = document.getElementById('sendBtn');

        recipientSelect.addEventListener('change', function() {
            if (this.value === 'specific') {
                userSelectGroup.style.display = 'block';
                document.getElementById('user_id').required = true;
            } else {
                userSelectGroup.style.display = 'none';
                document.getElementById('user_id').required = false;
            }
        });

        notificationForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {
                recipient: formData.get('recipient'),
                user_id: formData.get('user_id'),
                title: formData.get('title'),
                body: formData.get('body')
            };

            sendBtn.disabled = true;
            sendBtn.textContent = 'Sending...';

            try {
                const response = await fetch('/admin/send-notification', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.ok) {
                    showAlert('success', result.message || 'Notification sent successfully!');
                    notificationForm.reset();
                    updateNotificationCount();
                } else {
                    showAlert('error', result.error || 'Failed to send notification');
                }
            } catch (error) {
                showAlert('error', 'Network error: ' + error.message);
            } finally {
                sendBtn.disabled = false;
                sendBtn.textContent = 'Send Notification';
            }
        });

        function showAlert(type, message) {
            alertBox.className = 'alert ' + type;
            alertBox.textContent = message;
            alertBox.style.display = 'block';
            setTimeout(() => {
                alertBox.style.display = 'none';
            }, 5000);
        }

        function updateNotificationCount() {
            const countEl = document.getElementById('notificationCount');
            countEl.textContent = parseInt(countEl.textContent) + 1;
        }

        function sendToUser(userId, userName) {
            document.getElementById('recipient').value = 'specific';
            userSelectGroup.style.display = 'block';
            document.getElementById('user_id').value = userId;
            document.getElementById('user_id').required = true;
            document.getElementById('title').value = 'Hello ' + userName + '!';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>
</html>
