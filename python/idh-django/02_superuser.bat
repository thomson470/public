@echo off
@title Shell
SET BIN=env\Scripts
call %BIN%\activate
cd project
python manage.py createsuperuser
pause