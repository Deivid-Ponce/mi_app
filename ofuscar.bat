@echo off

if not exist js_ofuscado mkdir js_ofuscado

for %%f in (js\*.js) do (
    echo Ofuscando %%~nxf

    javascript-obfuscator "%%f" ^
    --output "js_ofuscado\%%~nxf" ^
    --compact true ^
    --string-array true ^
    --string-array-encoding rc4 ^
    --disable-console-output true
)

echo.
echo =====================================
echo OFUSCACION TERMINADA
echo =====================================
pause