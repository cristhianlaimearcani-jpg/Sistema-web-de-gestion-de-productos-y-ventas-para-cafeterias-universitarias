@echo off
chcp 65001 >nul
title CafeQR - Instalacion
cd /d "%~dp0"
echo ============================================
echo   CafeQR - instalacion desde cero
echo ============================================
echo Usalo si se borro el proyecto o es una PC nueva.
echo No borra datos: si la base ya tiene usuarios y pedidos, se conservan.
echo.
where php >nul 2>&1
if errorlevel 1 (
    echo No encuentro PHP. Instala XAMPP y agrega C:\xampp\php al PATH.
    pause
    exit /b 1
)
where composer >nul 2>&1
if errorlevel 1 (
    echo No encuentro Composer. Instalalo desde getcomposer.org y vuelve a ejecutar este archivo.
    pause
    exit /b 1
)
echo 1) Instalando las librerias (carpeta vendor)...
if not exist vendor\autoload.php (
    call composer install --no-interaction
    if errorlevel 1 (
        echo Fallo composer install. Revisa tu conexion a internet.
        pause
        exit /b 1
    )
) else (
    echo    Ya estaban instaladas.
)
echo 2) Configuracion (.env)...
if not exist .env (
    copy .env.example .env >nul
    echo    Se creo .env a partir de .env.example
)
findstr /b /c:"APP_KEY=base64" .env >nul 2>&1
if errorlevel 1 php artisan key:generate --force
findstr /b /c:"DB_CONNECTION=sqlite" .env >nul 2>&1
if not errorlevel 1 (
    if not exist database\database.sqlite type nul > database\database.sqlite
)
echo 3) Creando las tablas...
php artisan migrate --force
if errorlevel 1 (
    echo No se pudieron crear las tablas. Si usas MySQL: enciende MySQL en XAMPP,
    echo crea la base de datos en phpMyAdmin y revisa DB_DATABASE, DB_USERNAME y DB_PASSWORD en .env
    pause
    exit /b 1
)
echo 4) Cargando administrador, productos del menu y cupones...
php artisan db:seed --force
php artisan optimize:clear >nul 2>&1
echo.
echo ============================================
echo   LISTO. Administrador: admi7@est.univalle.edu  /  Admi1234@
echo   Ahora ejecuta  iniciar-cafeqr.bat
echo ============================================
pause
