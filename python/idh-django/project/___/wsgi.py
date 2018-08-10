"""
WSGI config for myapp project.

It exposes the WSGI callable as a module-level variable named ``application``.

For more information on this file, see
https://docs.djangoproject.com/en/2.0/howto/deployment/wsgi/
"""

import os
from django.conf import settings
from django.core.wsgi import get_wsgi_application
from dj_static import Cling, MediaCling

os.environ.setdefault("DJANGO_SETTINGS_MODULE", "___.settings")

is_static = False
if hasattr(settings, 'STATIC_URL'):
    if settings.STATIC_URL != '' and settings.STATIC_URL[0:4] != 'http':
        is_static = True

is_media = False
if hasattr(settings, 'MEDIA_URL'):
    if settings.MEDIA_URL != '' and settings.MEDIA_URL[0:4] != 'http':
        is_media = True

application = get_wsgi_application()
if is_static and is_media:
    application = Cling(MediaCling(get_wsgi_application()))
elif is_static:
    application = Cling(get_wsgi_application())
elif is_media:
    application = MediaCling(get_wsgi_application())
