import uuid
import importlib
from datetime import datetime
from django.conf import settings
from django.contrib.contenttypes.models import ContentType
from django.http import HttpResponse
from django.contrib.auth import get_user
from django.contrib.auth.models import AnonymousUser, User as AuthUser
from django.contrib.auth.hashers import check_password
from django.db import models
from apps.core.beans import Struct, Result
from apps.core.formatter.json import JSONFormatter
from apps.core.formatter.xml import XMLFormatter
from apps.core.utils import _import
from apps.core.basic.access import Access, User as AccessUser
from apps.core.basic.dao import BasicDao

MODEL_API_REGISTRY = {}

MODEL_REGISTRY_ENABLE = True

API_USER_ENABLE = True

API_PERMISSION_ENABLE = True

ACCESS_TOUCH_ENABLE = True

if hasattr(settings, 'API_SETTINGS'):
    if 'MODEL_REGISTRY_ENABLE' in settings.API_SETTINGS:
        MODEL_REGISTRY_ENABLE = settings.API_SETTINGS['MODEL_REGISTRY_ENABLE']
    if 'USER_ENABLE' in settings.API_SETTINGS:
        API_USER_ENABLE = settings.API_SETTINGS['USER_ENABLE']
    if 'PERMISSION_ENABLE' in settings.API_SETTINGS:
        API_PERMISSION_ENABLE = settings.API_SETTINGS['PERMISSION_ENABLE']
    if 'ACCESS_TOUCH_ENABLE' in settings.API_SETTINGS:
        ACCESS_TOUCH_ENABLE = settings.API_SETTINGS['ACCESS_TOUCH_ENABLE']

#
# ModelApi
# Untuk pendaftaran Model yang dapat diakses
#
class ModelApi(object):
    def register(model, mapi = None):
        if not issubclass(model, models.Model):
            raise BasicApi.AppError('ModelApi - Invalid model type, for: ' + str(model))
        if mapi is None:
            mapi = ModelApi
        if not issubclass(mapi, ModelApi):
            raise BasicApi.AppError('ModelApi - Invalid type, for: ' + str(mapi))
        if model in MODEL_API_REGISTRY:
            raise BasicApi.AppError('ModelApi - Duplicate entry, for' + str(model))
        mapi.model = model
        if hasattr(mapi, 'alias'):
            reverse_alias = {}
            for k, v in mapi.alias.items():
                if v in reverse_alias:
                    raise BasicApi.AppError('ModelApi - Duplicate alias: ' + v + ', in: ' + str(type(mapi)))
                reverse_alias[v] = k
            mapi.reverse_alias = reverse_alias
        MODEL_API_REGISTRY[model] = mapi
        return mapi

    def unregister(model):
        if model in MODEL_API_REGISTRY:
            del MODEL_API_REGISTRY[model]

    def get(model):
        if model in MODEL_API_REGISTRY:
            return MODEL_API_REGISTRY[model]
        if not MODEL_REGISTRY_ENABLE:
            return ModelApi.register(model)
        return None


#{
#    'user': <User>,
#    'model': <Model>,
#    'path': <list>,
#    'parameters': <dict>,
#    'order': <list>,
#    'filter': <dict>,
#    'field': <list>
#}
class BasicApi(object):

    class NotAllowed(Exception):
        pass

    class BadRequest(Exception):
        pass

    class AppError(Exception):
        pass

    PARAMETER_ORDER = 'p_order'

    PARAMETER_FIELD = 'p_field'

    PARAMETER_FILTER = 'p_filter'

    SPLITTER_FILTER_OBJECT = '|'

    SPLITTER_FILTER_KEY_VALUE = ':'

    def validate(func):
        def function_wrapper(input, *args, **kwargs):
            if isinstance(input, dict):
                o = Struct(input)
            elif isinstance(input, Struct):
                o = Struct(input.__dict__)
            else:
                raise BasicApi.AppError('invalid input parameter type, it should be a Struct or dict')

            # Model
            if not hasattr(o, 'model'):
                raise BasicApi.BadRequest('model is required')
            model = o.model
            if model is None:
                raise BasicApi.BadRequest('model is None')
            if isinstance(model, str):
                model = _import(model)
            o.model = model
            del model

            # API
            alias = {}
            ignore = []
            o.api = ModelApi.get(o.model)
            if o.api is None:
                raise BasicApi.BadRequest('model api is not registered')
            if hasattr(o.api, 'alias'):
                alias = o.api.alias
            if hasattr(o.api, 'ignore'):
                if o.api.ignore is not None and 'input' in o.api.ignore:
                    ignore = o.api.ignore['input']
            len_alias = len(alias)
            len_ignore = len(ignore)

            # User
            if API_USER_ENABLE:
                if not hasattr(o, 'user'):
                    raise BasicApi.AppError('user is required')
                if not o.user.is_active:
                    raise BasicApi.NotAllowed('user is not active')
                if not hasattr(o.user, 'get_all_permissions'):
                    raise BasicApi.AppError('invalid user object')
                if not callable(o.user.get_all_permissions):
                    raise BasicApi.AppError('user.get_all_permissions is not a callable')


            # Path
            path = []
            if hasattr(o, 'path'):
                if o.path is not None and isinstance(o.path, (list, str)):
                    if isinstance(o.path, str):
                        path = o.path.split('/')
                        if path[0] == '':
                            del path[0]
                        l = len(path)
                        if l > 0 and path[l - 1] == '':
                            del path[l - 1]
                    else:
                        path = o.path
            o.path = path
            del path

            # Parameters
            parameters = {}
            if hasattr(o, 'parameters'):
                if o.parameters is not None:
                    if isinstance(o.parameters, Struct):
                        parameters = o.parameters.__dict__
                    elif isinstance(o.parameters, dict):
                        parameters = o.parameters
            for k, v in parameters.items():
                if v is None:
                    v = []
                else:
                    if not isinstance(v, list):
                        v = [v]
                parameters[k] = v
            o.parameters = parameters
            del parameters

            # Order
            order = None
            if hasattr(o, 'order'):
                order = o.order
            elif BasicApi.PARAMETER_ORDER in o.parameters:
                value = o.parameters[BasicApi.PARAMETER_ORDER]
                del o.parameters[BasicApi.PARAMETER_ORDER]
                length = len(value)
                if length == 1 and isinstance(value[0], str):
                    order = value[0]
                else:
                    order = value
                if len_alias != 0 and order is not None:
                    a_order = None
                    if isinstance(order, list):
                        a_order = order
                    elif isinstance(order, str):
                        a_order = order.split(',')
                    if a_order is not None:
                        order = []
                        for field in a_order:
                            field = str(field).strip()
                            is_descending = field[:1] == '-'
                            if is_descending:
                                field = field[1:]
                            if field in o.api.reverse_alias:
                                field = o.api.reverse_alias[field]
                            if is_descending:
                                field = '-' + field
                            order.append(field)
                        del field, is_descending
                    del a_order
                del value, length
            o.order = order
            del order

            # Filter
            filter = None
            if hasattr(o, 'filter'):
                filter = o.filter
            elif BasicApi.PARAMETER_FILTER in o.parameters:
                value = o.parameters[BasicApi.PARAMETER_FILTER]
                del o.parameters[BasicApi.PARAMETER_FILTER]
                length = len(value)
                if length == 1 and isinstance(value[0], str):
                    farr = value[0].split(BasicApi.SPLITTER_FILTER_OBJECT)
                else:
                    farr = value
                filter = {}
                for x in farr:
                    y = str(x).split(BasicApi.SPLITTER_FILTER_KEY_VALUE)
                    if len(y) == 3:
                        field = y[0].strip()
                        if len_alias != 0:
                            if field in o.api.reverse_alias:
                                field = o.api.reverse_alias[field]
                        condition = y[1].strip()
                        if condition != '':
                            filter[field + '__' + condition] = y[2]
                        else:
                            filter[field] = y[2]
                        del field, condition
                del value, farr, length, x
            o.filter = filter
            del filter

            # Field
            field = None
            if hasattr(o, 'field'):
                field = o.field
            elif BasicApi.PARAMETER_FIELD in o.parameters:
                value = o.parameters[BasicApi.PARAMETER_FIELD]
                del o.parameters[BasicApi.PARAMETER_FIELD]
                length = len(value)
                if length == 1 and isinstance(value[0], str):
                    field = value[0]
                else:
                    field = value
                if len_alias != 0:
                    f_field = None
                    if isinstance(field, list):
                        f_field = field
                    elif isinstance(field, str):
                        f_field = field.split(',')
                    field = []
                    if f_field is not None:
                        for x in f_field:
                            x = x.strip()
                            if x in o.api.reverse_alias:
                                x = o.api.reverse_alias[x]
                            field.append(x)
                        del x
                    del f_field
                del value, length
            o.field = field
            del field

            # Data
            m_fields = o.model._meta.get_fields()
            data = o.model()
            for f in m_fields:
                m_name = f.name
                if m_name in ignore:
                    continue
                m_alias = None
                if m_name in alias:
                    m_alias = alias[m_name]
                value = None
                if m_alias is not None and m_alias in o.parameters:
                    value = o.parameters[m_alias]
                if value is None and m_name in o.parameters:
                    value = o.parameters[m_name]
                if value is not None:
                    if len(value) == 0:
                        value = None
                    elif len(value) == 1:
                        value = value[0]
                    setattr(data, m_name, value)
            o.data = data
            del alias, ignore, m_fields, data
            return func(o, *args, **kwargs)
        return function_wrapper


    def is_allow_permission(o, action):
        if not API_PERMISSION_ENABLE:
            return True
        if hasattr(o.api, 'is_only_superuser'):
            ios = o.api.is_only_superuser
            if not callable(ios):
                raise BasicApi.AppError('Attribute is_only_superuser is not a callable: ' + str(o.api))
            if not o.user.is_superuser:
                return False
            is_false = ios(o.user)
            if is_false:
                return False
        if hasattr(o.api, 'permissions'):
            permissions = o.api.permissions
            if permissions is not None and action in permissions:
                actperm = permissions[action]
                lstperm = o.user.get_all_permissions()
                if actperm in lstperm:
                    return True
                ctype = ContentType.objects.get_for_model(o.model)
                actperm = ctype.app_label + '.' + actperm + '_' + ctype.model
                if actperm in lstperm:
                    return True
                else:
                    return False
        return True


    def get_model_api(model):
        return ModelApi.get(model)


    @validate
    def page(o):
        """
        Page
            *) /page/<index>/<limit>
            *) /page/<index>/<limit>/<flag_count>
        <index> = Page Index
        :return:
        """
        if not BasicApi.is_allow_permission(o, 'page'):
            raise BasicApi.NotAllowed('Invalid permission')
        l = len(o.path)
        index = 1
        if l > 0:
            index = int(o.path[0])
        limit = BasicDao.DEFAULT_LIMIT
        if l > 1:
            limit = int(o.path[1])
        count = BasicDao.DEFAULT_COUNT
        if l > 2:
            scount = str(o.path[2]).strip().lower()
            count = '1' == scount or 'true' == scount
        return BasicDao.page({
            'model': o.model,
            'filter': o.filter,
            'field': o.field,
            'order': o.order,
            'data': o.data,
            'page': {'index': index, 'limit': limit, 'count': count},
        })


    @validate
    def list(o):
        """
        List
            *) /list
            *) /list/<limit>
        :return:
        """
        if not BasicApi.is_allow_permission(o, 'list'):
            raise BasicApi.NotAllowed('Invalid permission')
        l = len(o.path)
        limit = BasicDao.DEFAULT_LIMIT
        if l > 0:
            limit = int(o.path[0])
        return BasicDao.list({
            'model': o.model,
            'filter': o.filter,
            'field': o.field,
            'order': o.order,
            'data': o.data,
            'limit': limit
        })


    @validate
    def get(o):
        """
        Get
            *) /get
            *) /get/<pk>
        :return:
        """
        if not BasicApi.is_allow_permission(o, 'get'):
            raise BasicApi.NotAllowed('Invalid permission')
        l = len(o.path)
        pk = None
        if l > 0:
            pk = o.path[0]
        else:
            pk = o.data.pk
        return BasicDao.get({
            'model': o.model,
            'pk': pk,
            'filter': o.filter,
            'field': o.field
        })


    @validate
    def create(o):
        """
        Create
            *) /create
        :return:
        """
        if not BasicApi.is_allow_permission(o, 'create'):
            raise BasicApi.NotAllowed('Invalid permission')
        return BasicDao.create({
            'model': o.model,
            'data': o.data
        })

    @validate
    def update(o):
        """
        Update
            *) /update
            *) /update/<pk>
        :return:
        """
        if not BasicApi.is_allow_permission(o, 'update'):
            raise BasicApi.NotAllowed('Invalid permission')
        l = len(o.path)
        pk = None
        if l > 0:
            pk = o.path[0]
        else:
            pk = o.data.pk
        return BasicDao.update({
            'model': o.model,
            'pk': pk,
            'data': o.data
        })

    @validate
    def delete(o):
        """
        Delete
            *) /delete
            *) /delete/<pk>
        :return:
        """
        if not BasicApi.is_allow_permission(o, 'delete'):
            raise BasicApi.NotAllowed('Invalid permission')
        l = len(o.path)
        pk = None
        if l > 0:
            pk = o.path[0]
        else:
            pk = o.data.pk
        return BasicDao.delete({
            'model': o.model,
            'pk': pk
        })
