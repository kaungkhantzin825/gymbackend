# PowerShell script to create an admin user
# Run this from the backend directory

Write-Host "Creating Admin User..." -ForegroundColor Green

# Run artisan tinker command to create admin
php artisan tinker --execute="
\$user = App\Models\User::create([
    'name' => 'Admin User',
    'email' => 'admin@gymapp.com',
    'password' => Hash::make('admin123'),
    'role' => 'admin'
]);

\$user->profile()->create([
    'current_weight' => 75,
    'target_weight' => 70,
    'height' => 175,
    'age' => 30
]);

echo 'Admin user created successfully!\n';
echo 'Email: admin@gymapp.com\n';
echo 'Password: admin123\n';
echo 'Role: admin\n';
"

Write-Host "`nAdmin user created!" -ForegroundColor Green
Write-Host "Email: admin@gymapp.com" -ForegroundColor Yellow
Write-Host "Password: admin123" -ForegroundColor Yellow
Write-Host "`nYou can now login and access admin routes." -ForegroundColor Cyan
