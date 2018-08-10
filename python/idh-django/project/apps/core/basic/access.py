from django.conf import settings
from django.contrib.auth.models import User as AuthUser
from apps.core.formatter.base import Formatter
from apps.core.beans import Struct
from apps.core.cache.base import Cache

#
# Cache
#
cache = None
if hasattr(settings, 'CACHE'):
    if 'ACCESS' in settings.CACHE:
       cache =  settings.CACHE['ACCESS']

ACCESS_KEY_PREFIX = 'KEY::'

ACCESS_KEY_EXPIRY = 3600

#USER_REQUIRED_FIELDS =[
#    'id', 'username', 'password', 'first_name', 'last_name',
#    'email', 'is_superuser', 'is_staff', 'is_active', 'groups',
#    'user_permissions', 'date_joined', 'last_login'
#]

class UserParser(Formatter):
    def parse(self, in_user):
        return super(UserParser, self).fx_dumps(in_user)


#
# USER
# User Access yang akan di simpan ke cache
#
class User(object):
    def __init__(self, obj = None, **kwargs):
        in_user = None
        if obj is not None:
            in_user = obj
        elif 'user' in kwargs:
            in_user = kwargs['user']
        if in_user is None:
            raise Exception('user is required')
        if isinstance(in_user, AuthUser):
            group_permissions = in_user.get_group_permissions()
            myuser = UserParser().parse(in_user)
            myuser['group_permissions'] = list(group_permissions)
            user_permissions = []
            for perm in myuser['user_permissions']:
                user_permissions.append(perm['content_type']['app_label'] + '.' + perm['codename'])
            user_permissions = list(set(user_permissions))
            myuser['user_permissions'] = user_permissions
            for group in myuser['groups']:
                permissions = []
                for perm in group['permissions']:
                    permissions.append(perm['content_type']['app_label'] + '.' + perm['codename'])
                permissions = list(set(permissions))
                group['permissions'] = permissions
        else:
            myuser = in_user
        if isinstance(myuser, Struct):
            myuser = myuser.__dict__
        self.__dict__.update(myuser)
        #myfields = list(USER_REQUIRED_FIELDS)
        for k, v in myuser.items():
        #    if k in myfields and isinstance(k, str):
        #        myfields.remove(k)
            if isinstance(v, dict):
                self.__dict__[k] = User(v)

    def get_group_permissions(self):
        return self.group_permissions

    def get_all_permissions(self):
        all_permissions = self.group_permissions + self.user_permissions
        return list(set(all_permissions))


#
# ACCESS
# Fungsi untuk mengelolah Object Access yang ada di cache
#
class Access(object):
    def __init__(self, obj = None, **kwargs):
        if obj is not None:
            data = obj
        else:
            data = kwargs
        if 'user' in data:
            self.user = data['user']
        else:
            self.user = None
        if 'key' in data:
            self.key = data['key']
        else:
            self.key = None
        if 'secret' in data:
            self.secret = data['secret']
        else:
            self.secret = None

    def __iter__(self):
        yield ('user', self.user)
        yield ('key', self.key)
        yield ('secret', self.secret)

    def get(key):
        access = cache.get(ACCESS_KEY_PREFIX, key);
        if access is None:
            return None
        access = eval(access)
        if 'user' in access:
            access['user'] = User(access['user'])
        else:
            access['user'] = None
        return Access(access)

    def create(key, access):
        data = dict(access.__dict__)
        if 'user' in data:
            if hasattr(data['user'], '__dict__'):
                data['user'] = data['user'].__dict__
            else:
                data['user'] = None
        return cache.set(ACCESS_KEY_PREFIX, key, data, ACCESS_KEY_EXPIRY);

    def revoke(key):
        return cache.delete(ACCESS_KEY_PREFIX, key);

    def touch(key):
        return cache.expire(ACCESS_KEY_PREFIX, key, ACCESS_KEY_EXPIRY);
