#
# Struct
# Merubah dict jadi object
# Contoh:
#   adict = {'name': 'Saya', 'active': 1}
#   _get_object(**adict)
#
class Struct(object):
    def __init__(self, adict = {}):
        self.__dict__.update(adict)
        for k, v in adict.items():
            if isinstance(v, dict):
                self.__dict__[k] = Struct(v)
            #if isinstance(v, collections.Iterable):
            #    self.__dict__[k] = [Struct(i) if isinstance(i, dict) else i for i in v]

    def create(adict):
        return Struct(adict)


#
# Result
# Object response generic
#
class Result(object):
    STATUS = Struct({
        'SUCCESS': 'SUCCESS',
        'INPROGRESS': 'INPROGRESS',
        'FAILED': 'FAILED',
        'ERROR': 'ERROR'
    })

    def __init__(self):
        self.status = None
        self.error = None
        self.data = None
        self.info = None

    def __iter__(self):
        yield ('status', self.status)
        yield ('error', self.error)
        yield ('data', self.data)
        yield ('info', self.info)

    def create(status, data = None, error = None, info = None):
        r = Result()
        if isinstance(status, str):
            r.status = status
        r.data = data
        if error is not None and isinstance(error, (dict, list, str)):
            r.error = error
        if info is not None and isinstance(error, (dict, str)):
            r.info = info
        return r

    def success(data = None, info = None):
        return Result.create(Result.STATUS.SUCCESS, data, None, info)

    def error(error = None, info = None):
        return Result.create(Result.STATUS.ERROR, None, error, info)

    def error(code, text = None, info = None):
        de = {'code': code}
        if text is not None and isinstance(text, str):
            de['text'] = text
        return Result.create(Result.STATUS.ERROR, None, de, info)