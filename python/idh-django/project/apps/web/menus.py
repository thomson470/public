
def static(request):
    menus = [
        {'id': 1, 'title': 'Home', 'link': '/', 'icon': 'class:fa fa-home', 'parent': None},
        {'id': 2, 'title': '&nbsp;', 'link': '---', 'icon': None, 'parent': None},
        {'id': 3, 'title': 'Samples', 'link': None, 'icon': 'class:fa fa-bullseye', 'parent': None, 'children': [
            {'id': 4, 'title': 'Template', 'link': '/test/template/html/go', 'icon': 'class:fa fa-ticket', 'parent': 3},
            {'id': 5, 'title': 'Test', 'link': None, 'icon': None, 'parent': 3, 'children': [
                {'id': 6, 'title': 'Success', 'link': '/test/biasa/coba/success', 'icon': None, 'parent': 5},
                {'id': 7, 'title': 'Need Login', 'link': '/test/biasa/coba2/need_login', 'icon': None, 'parent': 5},
                {'id': 8, 'title': 'Invalid Method', 'link': '/test/biasa/coba3/inv_method', 'icon': None, 'parent': 5},
            ]},
        ]}
    ]
    if request.user.is_authenticated:
        menus.append(
            {'id': 9, 'title': 'Finance', 'link': None, 'icon': 'class:fa fa-bar-chart', 'parent': None, 'children': [
                {'id': 10, 'title': 'Index', 'link': '/test/finance/index', 'icon': 'class:fa fa-list', 'parent': 9},
                {'id': 11, 'title': 'Member', 'link': '/test/finance/index/member', 'icon': 'class:fa fa-user', 'parent': 9},
                {'id': 12, 'title': 'Report', 'link': '/test/finance/index/report', 'icon': None, 'parent': 9},
            ]}
        )
        menus.append({'id': 13, 'title': '&nbsp;', 'link': '---', 'icon': None, 'parent': None})
        menus.append({'id': 14, 'title': 'Logout', 'link': '/auth/logout', 'icon': 'class:fa fa-sign-out', 'parent': None})
    return menus


def dinamic(request):
    # TODO: Ambil menu user, agar tidak selalu ke db, sebaiknya gunakan cache
    pass