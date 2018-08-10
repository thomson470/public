@echo off
@title Setup

set PHYTON_PATH=D:\PROGRAMFILES\Python35
set PHYTON_BIN=%PHYTON_PATH%\python.exe
set VIRTUALENV_BIN=%PHYTON_PATH%\Scripts\virtualenv.exe

echo.
echo Starting...
echo.

if not exist "%PHYTON_BIN%" goto ALT_PYTHON_BIN
goto VIRTUALENV

:ALT_PYTHON_BIN
set PHYTON_BIN=%PHYTON_PATH%\python.exe
if not exist "%PHYTON_BIN%" goto NO_PYTHON_BIN
goto VIRTUALENV

:NO_PYTHON_BIN
echo PYTHON is not found
goto END

:VIRTUALENV
if not exist "%VIRTUALENV_BIN%" goto NO_VIRTUALENV_BIN
call "%VIRTUALENV_BIN%" -p "%PHYTON_BIN%" env
goto SETUP_PROJECT

:NO_VIRTUALENV_BIN
echo VIRTUALENV is not found
goto END

:SETUP_PROJECT
env\Scripts\pip install django==2.0.1
mkdir project
env\Scripts\django-admin startproject ___ project
cd project
mkdir apps
cd apps
..\..\env\Scripts\python ..\manage.py startapp core
..\..\env\Scripts\python ..\manage.py startapp api
..\..\env\Scripts\python ..\manage.py startapp web
cd ..
mkdir static
mkdir media
mkdir html
mkdir logs

goto END

:END
echo.
echo Finish
echo.
pause





