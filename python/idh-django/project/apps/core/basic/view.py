import base64
import logging
from django.contrib.auth import get_user, REDIRECT_FIELD_NAME
from django.conf import settings
from django.http import HttpResponse, HttpResponseRedirect
from django.template import RequestContext
from django.shortcuts import render
from django.contrib.auth.models import User as AuthUser
from django.contrib.auth.hashers import check_password
from apps.core.basic.api import BasicApi, API_USER_ENABLE, ACCESS_TOUCH_ENABLE
from apps.core.beans import Struct, Result
from apps.core.utils import load_module
from apps.core.basic.access import Access, User as AccessUser

logger = logging.getLogger('core')

#
# ViewResponse
#
class ViewResponse(object):
    def __init__(self, *args, **kwargs):
        if 'template' in kwargs:
            self.template = kwargs['template']
        else:
            self.context = None
        if 'context' in kwargs:
            self.context = kwargs['context']
        else:
            self.context = None


#
# ViewTemplate
#
class ViewTemplate(object):

    http_method_names = ['get', 'post', 'put', 'patch', 'delete', 'head', 'options', 'trace']

    def __init__(self, *args, **kwargs):
        for key, value in kwargs.items():
            setattr(self, key, value)

    def call(self, request, *args, **kwargs):
        is_rest = request.view_items.is_rest
        template = None
        if not is_rest:
            if not hasattr(self, 'template'):
                raise Exception('attribute template is not found')
            template = getattr(self, 'template')
        self._allowed_methods = self.http_method_names
        if hasattr(self, 'allowed_methods'):
            self._allowed_methods = getattr(self, 'allowed_methods')
        method = request.method.lower()
        if not method in self._allowed_methods:
            raise Exception('method ' + method + ' is not allowed')
        if not hasattr(self, method):
            raise Exception('method ' + method + ' is not defined')
        function = getattr(self, method)
        if not callable(function):
            raise Exception('method ' + method + ' is not a callable')
        context = function(request)
        if isinstance(context, HttpResponse):
            return context
        return ViewResponse(template = template, context = context)

    def options(self, request, *args, **kwargs):
        allow = [m.upper() for m in self._allowed_methods if hasattr(self, m)]
        response = HttpResponse()
        response['Allow'] = ', '.join(allow)
        response['Content-Length'] = '0'
        return response


#
# BasicView
# Controller / Route
#
class BasicView(object):

    HEADER_RESPONSE_FORMAT = 'HTTP_RESPONSE_FORMAT'
    HEADER_ACCESS_KEY = 'HTTP_ACCESS_KEY'

    CODE_UNSUPPORTED_METHOD = '90'
    CODE_NOT_FOUND = '91'
    CODE_USER_NOT_LOGGED_IN = '92'
    CODE_ACCESS_KEY_REQUIRED = '93'
    CODE_ACCESS_KEY_EXPIRED = '94'
    CODE_UNKNOWN_MODEL = '95'
    CODE_INVALID_ACTION = '96'
    CODE_NOT_ALLOWED = '97'
    CODE_BAD_REQUEST = '98'
    CODE_APP_SYS_ERROR = '99'

    CODE_USER_PASS_REQUIRED = '80'
    CODE_USER_NOT_FOUND = '81'
    CODE_USER_INACTIVE = '82'
    CODE_INVALID_PASSWORD = '83'
    CODE_ACCESS_KEY_REG_FAIL = '84'
    CODE_ACCESS_KEY_INVALID = '85'

    def __init__(self, *args, **kwargs):
        self.is_rest = False
        if 'is_rest' in kwargs and isinstance(kwargs['is_rest'], bool):
            self.is_rest = kwargs['is_rest']

        self.base_path_length = 0
        if 'base_path_length' in kwargs:
            self.base_path_length = int(kwargs['base_path_length'])

        self.template = Struct()
        self.formatter = {}
        if not self.is_rest:
            if not 'template' in kwargs:
                raise Exception('No template founds')
            self.template = Struct(kwargs['template'])
            if not hasattr(self.template, 'home'):
                raise Exception('No home template found')
            if not hasattr(self.template, 'login'):
                raise Exception('No login template found')
            if not hasattr(self.template, 'notfound'):
                raise Exception('No notfound template found')
            if not hasattr(self.template, 'error'):
                raise Exception('No error template found')
            if not hasattr(self.template, 'status'):
                raise Exception('No status template found')
        else:
            if not 'formatter' in kwargs:
                raise Exception('No formatter found')
            for k, v in kwargs['formatter'].items():
                if not isinstance(v, str):
                    continue
                f = load_module(v)
                if f is None:
                    raise Exception('formatter is not found, for: ' + v)
                if k in self.formatter:
                    raise Exception('duplicate formatter, for: ' + k)
                self.formatter[k] = f()
            self.template.home = None
            self.template.login = None
            self.template.notfound = None
            self.template.error = None
            self.template.status = None

        self.use_session = True
        if 'use_session' in kwargs and isinstance(kwargs['use_session'], bool):
            self.use_session = kwargs['use_session']

        self.model_packages = []
        if 'model_packages' in kwargs and isinstance(kwargs['model_packages'], list):
            for mp in kwargs['model_packages']:
                pkg = load_module(mp)
                if pkg is not None:
                    self.model_packages.append(pkg)

        self.route_packages = {}
        if 'route_packages' in kwargs and isinstance(kwargs['route_packages'], dict):
            for k, v in kwargs['route_packages'].items():
                if k in self.route_packages:
                    raise Exception('duplicate route_package, for: ' + k)
                f = load_module(v)
                if f is None:
                    raise Exception('route_package is not found, for: ' + v)
                self.route_packages[k] = f

        self.context_interceptors = {}
        if 'context_interceptors' in kwargs and isinstance(kwargs['context_interceptors'], dict):
            for k, v in kwargs['context_interceptors'].items():
                if k in self.context_interceptors:
                    raise Exception('duplicate context_interceptors, for: ' + k)
                f = load_module(v)
                if f is None:
                    raise Exception('context_interceptors is not found, for: ' + v)
                self.context_interceptors[k] = f

        self.settings = {}
        if hasattr(settings, 'VIEW_SETTINGS'):
            self.settings = settings.VIEW_SETTINGS

    # ROUTE
    #
    def route(self, request):
        view_items = self.view_items(request)
        ctx = Struct({
            'is_rest': self.is_rest,
            'formatter': view_items.formatter,
            'data': None,
            'status': 200,
            'nullable': False,
            'template': None,
            'context': {
                'settings': self.settings,
                'request': request
            },
        })
        if hasattr(view_items, 'error'):
            error = view_items.error
            info = error.info
            error = error.error
            ctx.context.text = info
            ctx.data = error['code'] + ' - ' + error['text'] + ' (' + info + ')'
            ctx.status = 400
            ctx.template = self.template.notfound
        else:
            ctx.template = self.template.home
            path = list(view_items.path)
            del view_items.path
            lenpath = len(path)
            if lenpath > 0:
                package_object = None
                view_items.package_path = []
                for x in range(0, 3):
                    i = 3 - x
                    if lenpath < i:
                        continue
                    p = '/'.join(s for s in path[0:i])
                    if p in self.route_packages:
                        package_object = self.route_packages[p]
                        view_items.package_path = list(path[0:i])
                        del path[0:i]
                        break
                lenpath = len(path)
                if package_object is None:
                    ctx.context.text = '03'
                    ctx.data = Result.error(BasicView.CODE_NOT_FOUND, 'Not Found (03)')
                    ctx.template = self.template.notfound
                elif lenpath == 0:
                    ctx.context.text = '04'
                    ctx.data = Result.error(BasicView.CODE_NOT_FOUND, 'Not Found (04)')
                    ctx.template = self.template.notfound
                else:
                    if not hasattr(package_object, path[0]):
                        ctx.context.text = '05'
                        ctx.data = Result.error(BasicView.CODE_NOT_FOUND, 'Not Found (05)')
                        ctx.template = self.template.notfound
                    else:
                        module_object = getattr(package_object, path[0])
                        view_items.module_path = [path[0]]
                        del path[0]
                        lenpath = len(path)
                        module_idx = -1
                        for i in range(0, lenpath):
                            if not hasattr(module_object, path[i]):
                                break
                            module_object = getattr(module_object, path[i])
                            view_items.module_path.append(path[i])
                            module_idx = i
                        if module_idx != -1:
                            del path[0:module_idx + 1]
                        view_items.function_path = list(path)
                        if not callable(module_object):
                            ctx.context.text = '06'
                            ctx.data = Result.error(BasicView.CODE_NOT_FOUND, 'Not Found (06)')
                            ctx.template = self.template.notfound
                        else:
                            request.view_items = view_items
                            try:
                                is_function = str(type(module_object)) == '<class \'function\'>'
                                if is_function:
                                    result = module_object(request)
                                else:
                                    function_object = module_object(settings=self.settings)
                                    result = function_object.call(request)

                                # Default nullable = True, agar false maka di view_items harus diset
                                ctx.nullable = True
                                if hasattr(request.view_items, 'nullable') and isinstance(request.view_items.nullable, bool):
                                    ctx.nullable = request.view_items.nullable

                                if isinstance(result, HttpResponse):
                                    return result
                                elif isinstance(result, ViewResponse):
                                    ctx.context.context = result.context
                                    ctx.data = result.context
                                    ctx.template = result.template
                                else:
                                    ctx.context.context = result
                                    ctx.data = result
                                    ctx.template = request.view_items.template
                            except Exception as ex:
                                logger.error(ex)
                                ctx.context.error = {'code': '99', 'text': str(ex)}
                                ctx.data = Result.error(BasicView.CODE_APP_SYS_ERROR, str(ex))
                                ctx.nullable = False
                                ctx.template = self.template.error

        try:
            if not self.is_rest:
                for key, func in self.context_interceptors.items():
                    if hasattr(ctx.context, key):
                        continue
                    value = func(request)
                    setattr(ctx.context, key, value)
            kwargs = ctx.__dict__
            return BasicView.quick_return(**kwargs)
        except Exception as ex:
            logger.error(ex)
            ctx.context.error = {'code': '99', 'text': str(ex)}
            ctx.template = self.template.error
            return BasicView.quick_return(**kwargs)


    # MODEL
    #
    def model(self, request):
        self.is_rest = True
        view_items = self.view_items(request)
        if hasattr(view_items, 'error'):
            error = view_items.error
            info = error.info
            error = error.error
            data = error['code'] + ' - ' + error['text'] + ' (' + info + ')'
            return BasicView.quick_return(status=400, data=data, nullable=False, is_rest=self.is_rest)
        formatter = view_items.formatter
        path = view_items.path
        parameters = view_items.parameters

        if len(path) < 2:
            data = Result.error(BasicView.CODE_NOT_FOUND, 'Invalid path')
            return BasicView.quick_return(formatter=formatter, data=data, nullable=False, is_rest=self.is_rest)

        # Model
        model = self.get_model(path[0])
        if model is None:
            data = Result.error(BasicView.CODE_UNKNOWN_MODEL, 'Unknown model: ' + path[0])
            return BasicView.quick_return(formatter=formatter, data=data, nullable=False, is_rest=self.is_rest)
        del path[0]

        # Action
        action = path[0]
        del path[0]
        if not hasattr(BasicApi, action):
            data = Result.error(BasicView.CODE_INVALID_ACTION, 'Invalid action: ' + path[0])
            return BasicView.quick_return(formatter=formatter, data=data, nullable=False, is_rest=self.is_rest)

        # User
        user = None
        if self.use_session:
            user = get_user(request)
            if not request.user.is_authenticated and API_USER_ENABLE:
                data = Result.error(BasicView.CODE_USER_NOT_LOGGED_IN, 'User is not logged in')
                return BasicView.quick_return(formatter=formatter, data=data, nullable=False, is_rest=self.is_rest)
        else:
            key = view_items.key
            if key is None or '' == key:
                if API_USER_ENABLE:
                    data = Result.error(BasicView.CODE_ACCESS_KEY_REQUIRED, 'Access Key is required')
                    return BasicView.quick_return(formatter=formatter, data=data, nullable=False, is_rest=self.is_rest)
            else:
                access = Access.get(key)
                if access is None:
                    if API_USER_ENABLE:
                        data = Result.error(BasicView.CODE_ACCESS_KEY_EXPIRED, 'Access Key is expired')
                        return BasicView.quick_return(formatter=formatter, data=data, nullable=False, is_rest=self.is_rest)
                else:
                    if access.secret is not None and API_USER_ENABLE:
                        secret = BasicView.create_secret(request)
                        if secret != access.secret:
                            data = Result.error(BasicView.CODE_ACCESS_KEY_INVALID, 'Access Key is not valid')
                            return BasicView.quick_return(formatter=formatter, data=data, nullable=False)
                    user = access.user
                    if ACCESS_TOUCH_ENABLE:
                        Access.touch(key)

        # Execute
        command = getattr(BasicApi, action)
        status = 200
        try:
            data = command({
                'model': model,
                'path': path,
                'parameters': parameters,
                'user': user
            })
            result = Result.success(data)
        except BasicApi.NotAllowed:
            result = Result.error(BasicView.CODE_NOT_ALLOWED, 'Not allowed')
        except BasicApi.BadRequest as br:
            result = Result.error(BasicView.CODE_BAD_REQUEST, str(br))
        except BasicApi.AppError as ar:
            logger.error(ar)
            result = Result.error(BasicView.CODE_APP_SYS_ERROR, 'AppError: ' + str(ar))
            status = 500
        except Exception as ex:
            logger.error(ex)
            result = Result.error(BasicView.CODE_APP_SYS_ERROR, 'SysError: ' + str(ex))
            status = 500
        mapi = BasicApi.get_model_api(model)
        kwargs = {}
        if hasattr(mapi, 'alias'):
            kwargs['alias'] = getattr(mapi, 'alias')
        if hasattr(mapi, 'ignore'):
            ignore = getattr(mapi, 'ignore')
            if ignore is not None and 'output' in ignore:
                kwargs['hidden'] = ignore['output']
        if hasattr(mapi, 'nullable'):
            kwargs['nullable'] = getattr(mapi, 'nullable')
        return HttpResponse(formatter.content_data(result, **kwargs), formatter.content_type(), status)

    #
    # Untuk meload model
    #
    def get_model(self, name):
        for pkg in self.model_packages:
            if hasattr(pkg, name):
                return getattr(pkg, name)
        return None


    #
    # VIEW ITEMS
    # Mengambil informasi dari request yang terkait dengan view
    #
    def view_items(self, request):
        items = Struct()
        items.settings = self.settings,
        items.method = request.method
        items.formatter = None
        items.key = ''
        items.is_rest = self.is_rest
        items.use_session = self.use_session

        parameters = dict(request.GET)
        post_params = dict(request.POST)
        for k, v in post_params.items():
            if k in parameters:
                parameters[k].append(v)
            else:
                parameters[k] = v
        items.parameters = parameters

        path = request.path.strip().split('/')
        if path[0] == '':
            del path[0]
        length = len(path)
        if length > 0 and path[length - 1] == '':
            del path[length - 1]
        length = len(path)
        if length < self.base_path_length:
            items.error = Result.error(BasicView.CODE_NOT_FOUND, 'Not Found', '01')
            return items
        all_path = list(path)
        base_path = list(path[0:self.base_path_length])
        del path[0:self.base_path_length]
        items.all_path = all_path
        items.base_path = base_path
        items.path = path

        if self.is_rest:
            lenpath = len(path)
            if lenpath == 0:
                items.error = Result.error(BasicView.CODE_NOT_FOUND, 'Not Found', '02')
                return items
            lstr = path[lenpath - 1]
            lidx = lstr.rfind('.')
            fmt = ''
            if lidx != -1:
                fmt = lstr[lidx + 1:].strip()
                if fmt != '' and fmt in self.formatter:
                    lstr = lstr[:lidx]
                    path[lenpath - 1] = lstr
                else:
                    fmt = ''
            if fmt == '' and BasicView.HEADER_RESPONSE_FORMAT in request.META:
                fmt = request.META[BasicView.HEADER_RESPONSE_FORMAT].strip()
            items.formatter = self.formatter[fmt]
            if BasicView.HEADER_ACCESS_KEY in request.META:
                items.key = request.META[BasicView.HEADER_ACCESS_KEY]
        else:
            items.template = self.template
        return items

    #
    # QUICK RETURN
    # Untuk mengirim response http
    #
    def quick_return(**kwargs):
        is_rest = True
        if 'is_rest' in kwargs:
            is_rest = kwargs['is_rest']
        if is_rest:
            formatter = None
            if 'formatter' in kwargs:
                formatter = kwargs['formatter']
            status = 200
            if 'status' in kwargs:
                status = kwargs['status']
            data = ''
            if 'data' in kwargs:
                data = kwargs['data']
            if formatter is not None:
                content_type = formatter.content_type()
                params = {'data': data}
                if 'nullable' in kwargs and isinstance(kwargs['nullable'], bool):
                    params['nullable'] = kwargs['nullable']
                if 'content_type' in kwargs:
                    content_type = kwargs['content_type']
                return HttpResponse(
                    formatter.content_data(**params),
                    content_type,
                    status
                )
            else:
                content_type = 'text/plain'
                if 'content_type' in kwargs:
                    content_type = kwargs['content_type']
                return HttpResponse(
                    data,
                    content_type,
                    status
                )
        else:
            context = {}
            request = None
            if 'request' in kwargs:
                request = kwargs['request']
            if 'context' in kwargs:
                context = kwargs['context']
                if isinstance(context, Struct):
                    context = context.__dict__
                if request is None and 'request' in context:
                    request = context['request']
            return render(request, kwargs['template'], context)

    #
    # GET ACCESS
    # Untuk mendapatkan object access
    #
    def get_access(formatter, key, request = None):
        if key is None or '' == key:
            data = Result.error(BasicView.CODE_ACCESS_KEY_REQUIRED, 'Access Key is required')
            return BasicView.quick_return(formatter=formatter, data=data, nullable=False)
        access = Access.get(key)
        if access is None:
            data = Result.error(BasicView.CODE_ACCESS_KEY_EXPIRED, 'Access Key is expired')
            return BasicView.quick_return(formatter=formatter, data=data, nullable=False)
        if request is not None and access.secret is not None:
            secret = BasicView.create_secret(request)
            if secret != access.secret:
                data = Result.error(BasicView.CODE_ACCESS_KEY_INVALID, 'Access Key is not valid')
                return BasicView.quick_return(formatter=formatter, data=data, nullable=False)
        return access

    #
    # GET USER AGENT
    #
    def get_user_agent(request):
        user_agent = request.META['HTTP_USER_AGENT']
        return user_agent

    #
    # GET CLIENT IP
    #
    def get_client_ip(request):
        x_forwarded_for = request.META.get('HTTP_X_FORWARDED_FOR')
        if x_forwarded_for:
            ip = x_forwarded_for.split(',')[0]
        else:
            ip = request.META.get('REMOTE_ADDR')
        return ip

    def create_secret(request):
        user_agent = BasicView.get_user_agent(request)
        client_ip = BasicView.get_client_ip(request)
        secret = client_ip + '::' + user_agent
        return secret

    #
    # VERIFY
    # Penanda atau decorator fungsi untuk mengecek keabsahannya.
    # Parameter:
    # - method -> array http method yang boleh
    # - private -> True / False, untuk mengecek apakah harus login (untuk yg use_session=True) atau butuh access-key (use_session=False)
    #
    def verify(**input):
        def decorator(function):
            def wrapper(request, *args, **kwargs):
                view_items = request.view_items
                formatter = view_items.formatter
                use_session = view_items.use_session
                is_rest = view_items.is_rest
                if 'method' in input and isinstance(input['method'], list):
                    method = request.method
                    if not method.upper() in [x.upper() for x in  input['method']]:
                        if is_rest:
                            data = Result.error(BasicView.CODE_UNSUPPORTED_METHOD, 'Method Not Allowed')
                            return BasicView.quick_return(formatter=formatter, data=data, nullable=False, is_rest=is_rest)
                        else:
                            context = {
                                'settings': view_items.settings[0],
                                'request': request,
                                'error': {'status': 405, 'text': 'Method Not Allowed'}
                            }
                            template = view_items.template.status
                            return BasicView.quick_return(template=template, context=context, is_rest=is_rest)
                if 'private' in input and isinstance(input['private'], bool):
                    private = input['private']
                    if private:
                        if view_items.is_rest:
                            if use_session:
                                if not request.user.is_authenticated:
                                    data = Result.error(BasicView.CODE_USER_NOT_LOGGED_IN, 'User is not logged in')
                                    return BasicView.quick_return(formatter=formatter, data=data, nullable=False)
                            else:
                                key = view_items.key
                                access = BasicView.get_access(formatter, key, request)
                                if isinstance(access, HttpResponse):
                                    return access
                                view_items.access = access
                        else:
                            if not request.user.is_authenticated:
                                qstring = request.GET.urlencode()
                                redirect = request.path
                                if qstring != '':
                                    redirect = redirect + '?' + qstring
                                redirect = base64.urlsafe_b64encode(redirect.encode()).decode()
                                login = view_items.settings[0]['path']['login']
                                return HttpResponseRedirect(login + '?' + REDIRECT_FIELD_NAME + '=' + redirect)
                return function(request, *args, **kwargs)
            return wrapper
        return decorator