import uuid
import base64
from datetime import datetime
from django.http import HttpResponse, HttpResponseRedirect

from django.contrib.auth.models import User as AuthUser
from django.contrib.auth.hashers import check_password
from django.contrib.auth import (
    REDIRECT_FIELD_NAME,
    login as auth_login,
    logout as auth_logout,
)
from django.contrib.auth.forms import AuthenticationForm
from django.contrib.sites.shortcuts import get_current_site
from django.views.decorators.cache import never_cache

from apps.core.beans import Result
from apps.core.basic.access import Access, User as AccessUser
from apps.core.basic.view import BasicView
from apps.core.basic.dao import BasicDao

#
# LOGIN
#
@never_cache
def login(request):
    view_items = request.view_items
    # rest
    if view_items.is_rest:
        formatter = view_items.formatter
        if view_items.use_session and request.user.is_authenticated:
            data = Result.success("LOGGED_IN")
            return BasicView.quick_return(formatter=formatter, data=data, nullable=False)
        parameters = view_items.parameters
        username = ''
        if 'username' in parameters:
            username = parameters['username'][0]
        password = ''
        if 'password' in parameters:
            password = parameters['password'][0]
        if username == '' or password == '':
            data = Result.error(BasicView.CODE_USER_PASS_REQUIRED, 'username and password are required')
            return BasicView.quick_return(formatter=formatter, data=data, nullable=False)
        authUser = BasicDao.get({'model': AuthUser, 'filter': {'username': username}})
        if authUser is None:
            data = Result.error(BasicView.CODE_USER_NOT_FOUND, 'User is not found')
            return BasicView.quick_return(formatter=formatter, data=data, nullable=False)
        if not authUser.is_active:
            data = Result.error(BasicView.CODE_USER_INACTIVE, 'User is not active')
            return BasicView.quick_return(formatter=formatter, data=data, nullable=False)
        pwd_valid = check_password(password, authUser.password)
        if not pwd_valid:
            data = Result.error(BasicView.CODE_INVALID_PASSWORD, 'Invalid password')
            return BasicView.quick_return(formatter=formatter, data=data, nullable=False)
        authUser.last_login = datetime.now()
        authUser.save()
        if view_items.use_session:
            auth_login(request, authUser)
            data = Result.success()
            return BasicView.quick_return(formatter=formatter, data=data, nullable=False)
        else:
            key = str(uuid.uuid1())
            user = AccessUser(user=authUser)
            secret = BasicView.create_secret(request)
            access = Access(user=user, key=key, secret=secret)
            saved = Access.create(key, access)
            if not saved:
                data = Result.error(BasicView.CODE_ACCESS_KEY_REG_FAIL, 'Failed to register Access Key')
                return BasicView.quick_return(formatter=formatter, data=data, nullable=False)
            del access.user.password
            del access.secret
            data = Result.success(access)
            return BasicView.quick_return(formatter=formatter, data=data, nullable=False)
    # web
    else:
        settings = view_items.settings[0]
        redirect_to = ''
        if REDIRECT_FIELD_NAME in view_items.parameters:
            redirect_to = view_items.parameters[REDIRECT_FIELD_NAME][0]
        if request.user.is_authenticated:
            if not '' == redirect_to:
                redirect_to = base64.urlsafe_b64decode(redirect_to).decode()
            if redirect_to == settings['path']['login'] or redirect_to == '':
                return HttpResponseRedirect(settings['path']['home'])
            return HttpResponseRedirect(redirect_to)
        if 'POST' == request.method:
            form = AuthenticationForm(request, data=request.POST)
            if form.is_valid():
                auth_login(request, form.get_user())
                return HttpResponseRedirect(settings['path']['login'] + '?' + REDIRECT_FIELD_NAME + '=' + redirect_to)
        else:
            form = AuthenticationForm(request)

        current_site = get_current_site(request)
        context = {
            'form': form,
            REDIRECT_FIELD_NAME: redirect_to,
            'site': current_site,
            'settings': settings,
            'request': request
        }
        template = view_items.template.login
        return BasicView.quick_return(template=template, context=context, is_rest=False)

#
# LOGOUT
#
@never_cache
@BasicView.verify(private=True)
def logout(request):
    view_items = request.view_items
    # rest
    if view_items.is_rest:
        formatter = view_items.formatter
        if view_items.use_session:
            auth_logout(request)
            data = Result.success()
            return BasicView.quick_return(formatter=formatter, data=data, nullable=False)
        else:
            key = view_items.key
            Access.revoke(key)
            data = Result.success()
            return BasicView.quick_return(formatter=formatter, data=data, nullable=False)
    # web
    else:
        settings = view_items.settings[0]
        auth_logout(request)
        return HttpResponseRedirect(settings['path']['home'])

#
# PROFILE
#
@never_cache
@BasicView.verify(private=True)
def profile(request):
    view_items = request.view_items
    formatter = view_items.formatter
    if view_items.is_rest and not view_items.use_session:
        key = view_items.key
        access = BasicView.get_access(formatter, key)
        if isinstance(access, HttpResponse):
            return access
        if access.user is not None and hasattr(access.user, 'password'):
            del access.user.password
        if hasattr(access, 'secret'):
            del access.secret
        data = Result.success(access)
        return BasicView.quick_return(formatter=formatter, data=data, nullable=False)
    else:
        data = Result.error(BasicView.CODE_NOT_ALLOWED, 'NOT ALLOWED')
        return BasicView.quick_return(formatter=formatter, data=data, nullable=False)
