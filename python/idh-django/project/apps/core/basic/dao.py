from datetime import datetime
from django.db import models
from django.db.models.fields import DateTimeField
from django.db.models.fields import BooleanField

from apps.core.beans import Struct
from apps.core.utils import _import

#{
#    'pk': '',
#    'filter': {},
#    'field': [],
#    'page': {'index': 1, 'limit': 25, 'count': True},
#    'limit': limit,
#    'order': [], => prefix '-' -> desc
#    'model': class model,
#    'data': object model
#}
class BasicDao(object):
    # Default limit jumlah data
    DEFAULT_LIMIT = 50

    # default flag apakah jumlah keseluruhan data disertakan atau tidak
    DEFAULT_COUNT = False

    # Default datetime format
    DEFAULT_DATETIME_FORMAT = '%Y-%m-%d %H:%M:%S'


    def validate(func):
        """
        Merubah input menjadi Struct jika instance dari dict

        """
        def function_wrapper(o, *args, **kwargs):
            if isinstance(o, (dict)):
                o = Struct(o)
            if not hasattr(o, 'model'):
                raise Exception('model is required')
            m = o.model
            if m is None:
                raise Exception('model is None')
            if isinstance(m, str):
                m = _import(m)
            if not issubclass(m, models.Model):
                raise Exception('invalid model type')
            o.model = m
            return func(o, *args, **kwargs)
        return function_wrapper

    def _get_page(o):
        """
        Mendapatkan object page

        :return:
            Struct
        """
        page = Struct()
        page.index = 1
        page.limit = BasicDao.DEFAULT_LIMIT
        page.count = BasicDao.DEFAULT_COUNT
        if hasattr(o, 'page'):
            p = o.page
            if p is not None:
                if isinstance(p, dict):
                    p = Struct(p)
                if hasattr(p, 'index'):
                    if p.index is not None and isinstance(p.index, int):
                        page.index = p.index
                if hasattr(p, 'limit'):
                    if p.limit is not None and isinstance(p.limit, int):
                        page.limit = p.limit
                if hasattr(p, 'count'):
                    if p.count is not None and isinstance(p.count, bool):
                        page.count = p.count
        if page.index < 1:
            page.index = 1
        if page.limit < 1:
            page.limit = BasicDao.DEFAULT_LIMIT
        return page

    def _get_filter(o):
        """
        Mendapatkan filter pencarian
        Format disesuaikan dengan standar django
        Contoh:
            Pencarian di field name yang mengandung kata 'saya', dan status active
            filter = {
                'name__icontains': 'saya',
                'active': True
            }

        :return:
            dict
        """
        filter = {}
        if hasattr(o, 'filter'):
            if o.filter is not None:
                if isinstance(o.filter, dict):
                    filter = o.filter
                elif isinstance(o.filter, Struct):
                    filter = o.filter.__dict__
        return filter

    def _get_order(o):
        """
        Mendapatkan order
        Contoh:
            Mengurutkan berdasarkan name DESC dan id ASC
            order = ['-name', 'id']
            dalam string = '-name, id'

        :return:
            list
        """
        order = []
        if hasattr(o, 'order'):
            if o.order is not None:
                if isinstance(o.order, list):
                    order = o.order
                elif isinstance(o.order, str):
                    order = o.order.split(',')
        return order

    def _get_field(o):
        """
        Mendapatkan daftar nama field

        :return:
            list
        """
        field = None
        if hasattr(o, 'field'):
            if o.field is not None:
                if isinstance(o.field, list):
                    field = o.field
                elif isinstance(o.field, str):
                    field = o.field.split(',')
        return field

    def _copy_model_field(model, target, source):
        """
        Copy Model Field

        :param target:
        :param source:
        :return:
            <Object Model>
        """
        fields = model._meta.get_fields()
        for f in fields:
            clazz = type(f)
            name = f.name
            if hasattr(source, name):
                value = getattr(source, name)
                if value is not None:
                    vtype = type(value)

                    # relation
                    if f.is_relation:
                        tval = getattr(target, name)
                        if tval is None:
                            if isinstance(value, f.related_model):
                                setattr(target, name, value)
                            else:
                                value = str(value)
                                if '' == value:
                                    if f.null:
                                        setattr(target, name, None)
                                else:
                                    if '' == value:
                                        if f.null:
                                            setattr(target, name, None)
                                    else:
                                        rval = f.related_model.objects.get(pk=value)
                                        setattr(target, name, rval)
                        else:
                            if isinstance(value, f.related_model):
                                if tval.pk != value.pk:
                                    setattr(target, value)
                            else:
                                value = str(value)
                                if '' == value:
                                    if f.null:
                                        setattr(target, name, None)
                                else:
                                    if str(tval.pk) != value:
                                        rval = f.related_model.objects.get(pk=value)
                                        setattr(target, rval)

                    # bool
                    elif issubclass(clazz, BooleanField):
                        if isinstance(vtype, bool):
                            setattr(target, name, value)
                        else:
                            value = str(value).strip().lower()
                            if '' == value:
                                if f.null:
                                    setattr(target, name, None)
                            else:
                                if 'true' == value or '1' == value:
                                    setattr(target, name, True)
                                else:
                                    setattr(target, name, False)

                    # datetime
                    elif issubclass(clazz, DateTimeField):
                        if isinstance(vtype, datetime):
                            setattr(target, name, value)
                        else:
                            value = str(value).strip()
                            if '' == value:
                                if f.null:
                                    setattr(target, name, None)
                            elif 'now' == value:
                                setattr(target, name, datetime.now())
                            else:
                                try:
                                    # coba dengan format timestamp
                                    dtime = datetime.fromtimestamp(int(value))
                                except Exception:
                                    dtime = datetime.strptime(value, BasicDao.DEFAULT_DATETIME_FORMAT)
                                setattr(target, name, dtime)
                    else:
                        setattr(target, name, value)
        return target




    @validate
    def page(o):
        """
        Mencari dengan hasil berisi informasi index, limit, dan count(jika flag count = True)
        - index = offset
        - limit = max jumlah data
        - count = jumlah total data

        :return:
            Struct
            {
                'index': <int>,
                'limit': <int>,
                'count': <int>,
                'data': <QuerySet / list>,
            }
            if field is not None:
                data = <list of dict>
            else:
                data = <QuerySet>
        """
        model = o.model
        page = BasicDao._get_page(o)
        filter = BasicDao._get_filter(o)
        order = BasicDao._get_order(o)
        field = BasicDao._get_field(o)
        is_field = field is not None
        is_count = page.count
        delattr(page, 'count')
        if is_count:
            page.count = model.objects.filter(**filter).count()
        min = (page.index - 1) * page.limit
        max = min + page.limit
        if is_field:
            data = model.objects.filter(**filter).select_related().order_by(*order).values(*field)[min : max]
            page.data = list(data)
        else:
            page.data = model.objects.filter(**filter).select_related().order_by(*order)[min : max]
        return page


    @validate
    def list(o):
        """
        Mendapatkan list object
        Untuk ambil semua data tanpa limit, isi limit = 0

        :return:
            if field is not None:
                <list of dict>
            else:
                <QuerySet>
        """
        model = o.model
        filter = BasicDao._get_filter(o)
        order = BasicDao._get_order(o)
        field = BasicDao._get_field(o)
        is_field = field is not None
        limit = BasicDao.DEFAULT_LIMIT
        if hasattr(o, 'limit'):
            if o.limit is not None and isinstance(o.limit, int):
                limit = o.limit
        if limit > 0:
            if is_field:
                data = model.objects.filter(**filter).select_related().order_by(*order).values(*field)[:limit]
                result = list(data)
            else:
                result = model.objects.filter(**filter).select_related().order_by(*order)[:limit]
        else:
            # unlimited
            if is_field:
                data = model.objects.filter(**filter).select_related().order_by(*order).values(*field)
                result = list(data)
            else:
                result = model.objects.filter(**filter).select_related().order_by(*order)
        return result


    @validate
    def get(o):
        """
        Untuk mendapatkan hanya 1 object
        Bisa berdasarkan pk, dan juga bisa berdasarkan filter
        Akan akan error jika data lebih dari 1

        :return:
            if field is not None:
                <dict>
            else:
                <Model>
        """
        model = o.model
        filter = BasicDao._get_filter(o)
        is_filter = len(filter) != 0
        field = BasicDao._get_field(o)
        is_field = field is not None
        if is_filter:
            if is_field:
                data = model.objects.filter(**filter).select_related().values(*field)
                result = list(data)
            else:
                result = model.objects.filter(**filter).select_related()
        else:
            pk = None
            if hasattr(o, 'pk'):
                pk = o.pk
            if pk is None:
                raise Exception('pk is required')
            if is_field:
                data = model.objects.filter(pk=pk).select_related().values(*field)
                result = list(data)
            else:
                result = model.objects.filter(pk=pk).select_related()
        num = len(result)
        if num == 0:
            return None
        if num > 1:
            raise model.MultipleObjectsReturned()
        return result[0]


    @validate
    def create(o):
        """
        Membuat object baru

        :return:
            <Object Model>
        """
        model = o.model
        input = o.data
        if input is None:
            input = model()
        data = model()
        data = BasicDao._copy_model_field(model, data, input)
        data.pk = None
        data.save()
        return data


    @validate
    def update(o):
        """
        Memperbaharui data

        :return:
            <Object Model>
        """
        pk = None
        if hasattr(o, 'pk'):
            pk = o.pk
        if pk is None:
            raise Exception('pk is required')
        model = o.model
        input = o.data
        if input is None:
            input = model()
        input.pk = None
        data = model.objects.get(pk=pk)
        data = BasicDao._copy_model_field(model, data, input)
        data.save()
        return data


    @validate
    def delete(o):
        """
        Menghapus data

        :return:
            <dict>
        """
        pk = None
        if hasattr(o, 'pk'):
            pk = o.pk
        if pk is None:
            raise Exception('pk is required')
        if isinstance(pk, str):
            pk = pk.split(',')
        if not isinstance(pk, list):
            pk = [pk]
        model = o.model
        result = model.objects.filter(pk__in=pk).delete()
        return dict(result[1])