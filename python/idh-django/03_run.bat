@echo off
@title Run Server
SET BIN=env\Scripts
call %BIN%\activate
cd project
python manage.py runserver