@echo off
@title Deployment
SET BIN=env\Scripts

rem << START >>
for /F "tokens=1" %%i in ('date /t') do set mydate=%%i
set now=%mydate% %time%
echo START DEPLOY %now%
echo ===================================
echo.

rem << Activate virtualenv >>
call %BIN%\activate

rem << Update Requirement >>
%BIN%\pip install -r 01_deploy.txt


rem << Run Migration >>
cd project
python manage.py makemigrations
python manage.py migrate

rem << Collect Static >>
python manage.py collectstatic

rem << END >>
for /F "tokens=1" %%i in ('date /t') do set mydate=%%i
set now=%mydate% %time%
echo.
echo ===================================
echo END DEPLOY %now%
echo.

pause





