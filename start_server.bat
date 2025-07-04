@echo off
echo ========================================
echo    Otaku Haven Prototype Server
echo ========================================
echo.

echo Checking for available servers...
echo.

REM Check for Python
python --version >nul 2>&1
if %errorlevel% == 0 (
    echo ✅ Python found! Starting Python server...
    echo 🌐 Server will be available at: http://localhost:8080
    echo 📁 Serving files from: %CD%
    echo ⏹️  Press Ctrl+C to stop the server
    echo.
    python -m http.server 8080
    goto :end
)

REM Check for Python3
python3 --version >nul 2>&1
if %errorlevel% == 0 (
    echo ✅ Python3 found! Starting Python3 server...
    echo 🌐 Server will be available at: http://localhost:8080
    echo 📁 Serving files from: %CD%
    echo ⏹️  Press Ctrl+C to stop the server
    echo.
    python3 -m http.server 8080
    goto :end
)

REM Check for PHP
php --version >nul 2>&1
if %errorlevel% == 0 (
    echo ✅ PHP found! Starting PHP server...
    echo 🌐 Server will be available at: http://localhost:8000
    echo 📁 Serving files from: %CD%
    echo ⏹️  Press Ctrl+C to stop the server
    echo.
    php -S localhost:8000
    goto :end
)

REM If no server found
echo ❌ No server found!
echo.
echo Please install one of the following:
echo 1. Python: https://www.python.org/downloads/
echo 2. PHP: https://windows.php.net/download/
echo 3. Node.js: https://nodejs.org/
echo.
echo Or simply open OtakuHavenProto.html in your browser directly.
echo.
pause

:end 