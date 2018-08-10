from dicttoxml import dicttoxml
from apps.core.formatter.base import Formatter

class XMLFormatter(Formatter):

    def content_type(self):
        return 'text/xml'

    def content_data(self, data, **kwargs):
        if data is None:
            return ''
        result = super(XMLFormatter, self).fx_dumps(data, **kwargs)
        xml = dicttoxml(result, custom_root = 'model', cdata = False)
        return xml.decode('utf-8')