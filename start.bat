@echo off
echo ============================================
echo   جامعة المنوفية - تشغيل الخادم المحلي
echo ============================================
echo.

:: Find PHP directory
set "PHP_DIR=%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe"

if exist "%PHP_DIR%\php.exe" (
    echo [OK] تم العثور على PHP في: %PHP_DIR%
) else (
    echo [خطأ] PHP غير موجود في المسار المتوقع.
    echo جاري البحث...
    where php >nul 2>&1
    if %errorlevel% equ 0 (
        echo [OK] PHP موجود في PATH
        set "PHP_DIR="
    ) else (
        echo [خطأ] PHP غير مثبت. قم بتثبيته أولاً.
        pause
        exit /b 1
    )
)

set "PROJECT_DIR=%~dp0"

echo [INFO] مجلد المشروع: %PROJECT_DIR%
echo [INFO] بدء الخادم على http://localhost:8080
echo [INFO] لوحة التحكم: http://localhost:8080/admin/
echo [INFO] اضغط Ctrl+C للإيقاف
echo.

if defined PHP_DIR (
    "%PHP_DIR%\php.exe" -d extension_dir="%PHP_DIR%\ext" -d extension=php_zip.dll -d extension=php_mbstring.dll -S localhost:8080 -t "%PROJECT_DIR%"
) else (
    php -S localhost:8080 -t "%PROJECT_DIR%"
)

pause
