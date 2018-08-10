from django.conf import settings
from django.conf.urls import url
from django.contrib.auth import views as auth_views
from django.views.generic import TemplateView
from apps.core.basic.view import BasicView
import apps.web.apis

FORMATTER = {
    '': 'apps.core.formatter.json.JSONFormatter',
    'json': 'apps.core.formatter.json.JSONFormatter',
    'xml': 'apps.core.formatter.xml.XMLFormatter',
}

ROUTE_PACKAGES = {
    'auth': 'apps.core.basic.auth',
    'test': 'apps.web.views',
}

CONTEXT_INTERCEPTORS = {
    'menus': 'apps.web.menus.static'
}

MODEL_PACKAGES = [
    'apps.core.models.menu',
    'django.contrib.auth.models'
]


#
# NOTE:
# Untuk template view (route) yang menggunakan root path WAJIB dideklarasi paling bawah
# Sepertinya djanggo mengecek berdasarkan urutan deklarasi
#
urlpatterns = [

    # API MODEL
    url(r'api/model/', BasicView(
        is_rest = True,
        use_session = False,
        base_path_length = 2,
        model_packages = MODEL_PACKAGES,
        formatter = FORMATTER,
    ).model, name = "api_model"),

    # API ROUTE
    url(r'api/', BasicView(
        is_rest = True,
        use_session = False,
        base_path_length = 1,
        route_packages = ROUTE_PACKAGES,
        formatter = FORMATTER,
    ).route, name = "api_route"),

    # REST MODEL
    url(r'rest/model/', BasicView(
        is_rest = True,
        base_path_length = 2,
        model_packages = MODEL_PACKAGES,
        formatter = FORMATTER,
    ).model, name = "rest_model"),

    # REST ROUTE
    url(r'rest/', BasicView(
        is_rest = True,
        base_path_length = 1,
        route_packages = ROUTE_PACKAGES,
        formatter = FORMATTER,
    ).route, name = "rest_route"),

    # TEMPLATE
    url(r'^', BasicView(
        template = {
            'home': 'base.html',
            'login': 'auth/login.html',
            'notfound': 'error/404.html',
            'error': 'error/500.html',
            'status': 'error/status.html'
        },
        base_path_length = 0,
        route_packages = ROUTE_PACKAGES,
        context_interceptors = CONTEXT_INTERCEPTORS,
    ).route, name = "route"),

]
