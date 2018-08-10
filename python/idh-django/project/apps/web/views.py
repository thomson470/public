from apps.core.basic.view import ViewResponse, ViewTemplate, BasicView
from apps.core.beans import Result

# Create your views here.

class template(ViewTemplate):
    template = 'test.html'
    def get(self, request, *args, **kwargs):
        view_items = request.view_items
        path_sisa = view_items.function_path
        return {'message': str(path_sisa)}


class biasa(object):

    def coba(request):
        view_items = request.view_items
        path_sisa = view_items.function_path
        return ViewResponse(template = 'test.html', context = {'message': str(path_sisa)})

    @BasicView.verify(private=True)
    def coba2(request):
        request.view_items.template = 'test.html'
        return Result.error('21', 'Test Error')

    @BasicView.verify(method=['post'])
    def coba3(request):
        request.view_items.template = 'test.html'
        return Result.error('22', 'Tes salah method')


class finance(object):
    @BasicView.verify(private=True)
    def index(request):
        view_items = request.view_items
        path_sisa = view_items.function_path
        template = 'finance/'
        if len(path_sisa) == 0:
            template = template + 'index.html'
        else:
            template = template + path_sisa[0] + '.html'
        return ViewResponse(template = template, context = {'message': str(path_sisa)})