import json
from apps.core.formatter.base import Formatter

class JSONFormatter(Formatter):

    def content_type(self):
        return 'application/json'

    def content_data(self, data, **kwargs):
        if data is None:
            return ''
        result = super(JSONFormatter, self).fx_dumps(data, **kwargs)
        #return json.dumps(result, indent = 4)
        return json.dumps(result)
