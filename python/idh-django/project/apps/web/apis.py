from apps.core.basic.api import ModelApi
from apps.core.models.menu import *


### Add model api here ###

class MenuApi(ModelApi):
    permissions = {
        'page': 'view',
        'list': 'view',
        'get': 'view',
        'create': 'add',
        'update': 'change',
        'delete': 'delete'
    }
    alias = {
        'title': 'judul',
    }
    ignore = {
        'input': ['created_at', 'updated_at'],
        'output': ['menugroup']
    }
    nullable = False
ModelApi.register(Menu, MenuApi)





