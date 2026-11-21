@echo off
REM Levanta los 6 microservicios ALQUILER13 (puertos 8001 a 8006).
REM Cada servicio se abre en su propia terminal con su carpeta public como raiz.

start "ms_vehiculos :8001" cmd /k "cd /d %~dp0ms_vehiculos && C:\xampp\php\php.exe -S 127.0.0.1:8001 -t public"
start "ms_clientes :8002" cmd /k "cd /d %~dp0ms_clientes && C:\xampp\php\php.exe -S 127.0.0.1:8002 -t public"
start "ms_reservas :8003" cmd /k "cd /d %~dp0ms_reservas && C:\xampp\php\php.exe -S 127.0.0.1:8003 -t public"
start "ms_devoluciones :8004" cmd /k "cd /d %~dp0ms_devoluciones && C:\xampp\php\php.exe -S 127.0.0.1:8004 -t public"
start "ms_historial :8005" cmd /k "cd /d %~dp0ms_historial && C:\xampp\php\php.exe -S 127.0.0.1:8005 -t public"
start "ms_disponibilidad :8006" cmd /k "cd /d %~dp0ms_disponibilidad && C:\xampp\php\php.exe -S 127.0.0.1:8006 -t public"
