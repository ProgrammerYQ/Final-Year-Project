Write-Host "========================================" -ForegroundColor Cyan
Write-Host "    Otaku Haven Prototype Server" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Checking for available servers..." -ForegroundColor Yellow
Write-Host ""

# Check for Python
try {
    $pythonVersion = python --version 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Python found! Starting Python server..." -ForegroundColor Green
        Write-Host "🌐 Server will be available at: http://localhost:8080" -ForegroundColor Green
        Write-Host "📁 Serving files from: $PWD" -ForegroundColor Green
        Write-Host "⏹️  Press Ctrl+C to stop the server" -ForegroundColor Yellow
        Write-Host ""
        python -m http.server 8080
        exit
    }
} catch {}

# Check for Python3
try {
    $python3Version = python3 --version 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Python3 found! Starting Python3 server..." -ForegroundColor Green
        Write-Host "🌐 Server will be available at: http://localhost:8080" -ForegroundColor Green
        Write-Host "📁 Serving files from: $PWD" -ForegroundColor Green
        Write-Host "⏹️  Press Ctrl+C to stop the server" -ForegroundColor Yellow
        Write-Host ""
        python3 -m http.server 8080
        exit
    }
} catch {}

# Check for PHP
try {
    $phpVersion = php --version 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ PHP found! Starting PHP server..." -ForegroundColor Green
        Write-Host "🌐 Server will be available at: http://localhost:8000" -ForegroundColor Green
        Write-Host "📁 Serving files from: $PWD" -ForegroundColor Green
        Write-Host "⏹️  Press Ctrl+C to stop the server" -ForegroundColor Yellow
        Write-Host ""
        php -S localhost:8000
        exit
    }
} catch {}

# If no server found
Write-Host "❌ No server found!" -ForegroundColor Red
Write-Host ""
Write-Host "Please install one of the following:" -ForegroundColor Yellow
Write-Host "1. Python: https://www.python.org/downloads/" -ForegroundColor White
Write-Host "2. PHP: https://windows.php.net/download/" -ForegroundColor White
Write-Host "3. Node.js: https://nodejs.org/" -ForegroundColor White
Write-Host ""
Write-Host "Or simply open OtakuHavenProto.html in your browser directly." -ForegroundColor Yellow
Write-Host ""
Read-Host "Press Enter to continue" 