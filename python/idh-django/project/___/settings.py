"""
Django settings for myapp project.

Generated by 'django-admin startproject' using Django 2.0.1.

For more information on this file, see
https://docs.djangoproject.com/en/2.0/topics/settings/

For the full list of settings and their values, see
https://docs.djangoproject.com/en/2.0/ref/settings/
"""

import os
import dotenv
from apps.core.cache.base import Cache

# Build paths inside the project like this: os.path.join(BASE_DIR, ...)
BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
dotenv.read_dotenv('.env')


# Quick-start development settings - unsuitable for production
# See https://docs.djangoproject.com/en/2.0/howto/deployment/checklist/

# SECURITY WARNING: keep the secret key used in production secret!
SECRET_KEY = '(l5#1hl%ed!f_i^6df90fw2mb6%^0x9(%x^l$yrcb$54r#059-'

# SECURITY WARNING: don't run with debug turned on in production!
DEBUG = False
try:
    DEBUG = bool(int(os.getenv('DEBUG', 0)))
except:
    pass

ALLOWED_HOSTS = ['*']


ADMIN_ENABLE=True
try:
    ADMIN_ENABLE = bool(int(os.getenv('ADMIN_ENABLE', 1)))
except:
    pass
ADMIN_HEADER = os.getenv('ADMIN_HEADER', 'Project Admin')
ADMIN_TITLE = os.getenv('ADMIN_TITLE', 'Project Admin')


# Application definition

INSTALLED_APPS = [
    'django.contrib.admin',
    'django.contrib.auth',
    'django.contrib.contenttypes',
    'django.contrib.sessions',
    'django.contrib.messages',
    'django.contrib.staticfiles',
	
	'apps.core',
	'apps.web',
]


MIDDLEWARE = [
    'django.middleware.security.SecurityMiddleware',
    'django.contrib.sessions.middleware.SessionMiddleware',
    'django.middleware.common.CommonMiddleware',
    #'django.middleware.csrf.CsrfViewMiddleware',
    'django.contrib.auth.middleware.AuthenticationMiddleware',
    'django.contrib.messages.middleware.MessageMiddleware',
    'django.middleware.clickjacking.XFrameOptionsMiddleware',
]

ROOT_URLCONF = '___.urls'

TEMPLATES = [
    {
        'BACKEND': 'django.template.backends.django.DjangoTemplates',
        'DIRS': ['html'],
        'APP_DIRS': True,
        'OPTIONS': {
            'context_processors': [
                'django.template.context_processors.debug',
                'django.template.context_processors.request',
                'django.contrib.auth.context_processors.auth',
                'django.contrib.messages.context_processors.messages',
            ],
        },
    },
]

WSGI_APPLICATION = '___.wsgi.application'


# Database
# https://docs.djangoproject.com/en/2.0/ref/settings/#databases

DATABASES = {
    'default': {
        'ENGINE': os.getenv('DB_ENGINE', 'django.db.backends.sqlite3'),
        'NAME': os.getenv('DB_NAME'),
        'USER': os.getenv('DB_USER', ''),
        'PASSWORD': os.getenv('DB_PASSWORD', ''),
        'HOST': os.getenv('DB_HOST', ''),
        'PORT': os.getenv('DB_PORT', ''),
    }
}


# Password validation
# https://docs.djangoproject.com/en/2.0/ref/settings/#auth-password-validators

AUTH_PASSWORD_VALIDATORS = [
    {
        'NAME': 'django.contrib.auth.password_validation.UserAttributeSimilarityValidator',
    },
    {
        'NAME': 'django.contrib.auth.password_validation.MinimumLengthValidator',
    },
    {
        'NAME': 'django.contrib.auth.password_validation.CommonPasswordValidator',
    },
    {
        'NAME': 'django.contrib.auth.password_validation.NumericPasswordValidator',
    },
]


# Internationalization
# https://docs.djangoproject.com/en/2.0/topics/i18n/

LANGUAGE_CODE = os.getenv('LANGUAGE_CODE', 'en-us')

TIME_ZONE = os.getenv('TIME_ZONE', 'UTC')

USE_I18N = True

USE_L10N = True

USE_TZ = True


# Static files (CSS, JavaScript, Images)
# https://docs.djangoproject.com/en/2.0/howto/static-files/

STATIC_URL = os.getenv('STATIC_URL', '').strip()
STATIC_ROOT = os.path.join(BASE_DIR, 'static')
#STATICFILES_DIRS = [os.path.join(BASE_DIR, "static"),]

MEDIA_URL = os.getenv('MEDIA_URL', '').strip()
MEDIA_ROOT = os.path.join(BASE_DIR, 'media')


#
# LOGGING
#
LOGGING = {
    'version': 1,
    'disable_existing_loggers': False,
    'formatters': {
        'verbose': {
            'format': '%(levelname)s %(asctime)s %(module)s %(process)d %(thread)d %(message)s'
        },
        'simple': {
            'format': '%(levelname)s %(asctime)s - %(name)s %(message)s'
        },
        'standard': {
            'format' : '%(levelname)s %(asctime)s [%(name)s.%(module)s:%(lineno)s] %(message)s',
            'datefmt': '%Y-%m-%d %H:%M:%S',
        }

    },
    'handlers': {
        'console': {
            'class': 'logging.StreamHandler',
            'level': 'DEBUG',
            'formatter': 'simple',
        },
        'core': {
            'level': 'DEBUG',
            'class': 'logging.handlers.TimedRotatingFileHandler',
            'filename': 'logs/core.log',
            'when': 'D', # this specifies the interval
            'interval': 1, # defaults to 1, only necessary for other values
            'backupCount': 10, # how many backup file to keep, 10 days
            'formatter': 'standard',
        },

    },
    'loggers': {
        'core': {
            'handlers': ['core'],
            'level': os.getenv('CORE_LOG_LEVEL', 'INFO'),
        },
        '': {
            'handlers': ['console'],
            'level': os.getenv('CONSOLE_LOG_LEVEL', 'INFO'),
        }
    },
}


#
# VIEW SETTINGS
# Konstanta-konstanta yang dibutuhkan untuk presentasi / view
#
VIEW_SETTINGS = {
    'title': 'IDH-TEMPLATE',
    'header': 'IDH-TEMPLATE',
    'version': 'v1.0',
    'copyright': '<strong>Copyright &copy; 2018 <a target="_blank" href="mailto:thomson470@gmail.com">Ideahut</a>.</strong> All rights reserved.',
    'author': 'thomson470@gmail.com',
    'static': 'web',
    'skin': '_all-skins.min.css',
    'path': {
        'home': '/',
        'login': '/auth/login',
        'logout': '/auth/logout',
    },
}

#
# API SETTNGS
# - MODEL_REGISTRY_ENABLE -> Hanya yang terdaftar di ModelApi yang akan diproses
# - USER_ENABLE -> pengecekan user sudah login atau punya access key
# - PERMISSION_ENABLE -> pengecekan user permission pada saat mengakses Model (lihat BasicApi)
# - ACCESS_TOUCH_ENABLE -> waktu expiry diupdate setiap mengakses api atau tidak
#
API_SETTINGS = {
    'MODEL_REGISTRY_ENABLE': bool(int(os.getenv('API_MODEL_REGISTRY_ENABLE', 1))),
    'USER_ENABLE': bool(int(os.getenv('API_USER_ENABLE', 1))),
    'PERMISSION_ENABLE': bool(int(os.getenv('API_PERMISSION_ENABLE', 1))),
    'ACCESS_TOUCH_ENABLE': bool(int(os.getenv('API_ACCESS_TOUCH_ENABLE', 1))),
}


#
# CACHE
#
CACHE = {
    'ACCESS': Cache.create(
        provider = 'apps.core.cache.redis.RedisCache',
        prefix = 'ACCESS::',
        config = {
            'host': 'localhost',
            'port': 6380,
            'password': '',
            'db': 0
        }
    ),
}