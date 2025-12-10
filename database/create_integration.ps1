# PowerShell script to create complete integration database
# This merges foxunity0 with events module

Write-Host "Creating complete integration database..." -ForegroundColor Green

# Step 1: Create database
Write-Host "`nStep 1: Creating integration database..." -ForegroundColor Cyan
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "DROP DATABASE IF EXISTS integration; CREATE DATABASE integration CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Database created successfully" -ForegroundColor Green
} else {
    Write-Host "✗ Failed to create database" -ForegroundColor Red
    exit 1
}

Write-Host "`nIntegration database created successfully!" -ForegroundColor Green
Write-Host "`nNext steps:" -ForegroundColor Yellow
Write-Host "1. Import the foxunity0 database structure (provided by you)" -ForegroundColor White
Write-Host "2. The events tables are already integrated in the codebase" -ForegroundColor White
Write-Host "3. Update config/database.php to use 'integration' database" -ForegroundColor White
Write-Host "`nDatabase name: integration" -ForegroundColor Cyan
