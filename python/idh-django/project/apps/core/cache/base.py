from abc import ABC, abstractmethod
from apps.core.utils import load_module

class Cache(ABC):
    def __init__(self, **kwargs):
        self.prefix = '__CACHE__::'
        if 'prefix' in kwargs and kwargs['prefix'] is not None:
            self.prefix = kwargs['prefix'].strip()
        if 'config' in kwargs and isinstance(kwargs['config'], dict):
            self.config = kwargs['config']

    def get(self, group, key, **kwargs):
        """
        :param group:
        :param key:
        :param **kwargs:
        :return:

        Contoh:
            value = cache.get('TEST', 1, {'callback': callback, 'arguments': ['BOS'], 'expiry': 30, 'nullable': True})
            value = cache.get('TEST', 1)

            'calback'   =>  Fungsi untuk mendapatkan obyek yang akan disimpan di cache
            'arguments' =>  Parameter-parameter yang dibutuhkan oleh 'callback'
            'expiry'    =>  Waktu kadaluarsa dalam detik
            'nullable'  =>  Flag untuk menyimpan obyek null ke cache jika hasil dari 'callback' adalah null,
                            ini dimaksudkan agar tidak perlu melakukan pemanggilan 'callback' jika obyek memang null.
                            Contoh kasus: tidak perlu query ke database lagi jika data memang tidak tersedia
        """
        value = self.doGet(self._groupy(group), str(key))
        if value is not None:
            if '__null__' in value or hasattr(value, '__null__'):
                return None
            return value
        callback = None
        if 'callback' in kwargs and callable(kwargs['callback']):
            callback = kwargs['callback']
        if callback is not None:
            arguments = None
            if 'arguments' in kwargs:
                arguments = kwargs['arguments']
            expiry = None
            if 'expiry' in kwargs:
                expiry = kwargs['expiry']
            value = callback(arguments)
            if value is not None:
                self.doSet(self._groupy(group), str(key), value, expiry)
            else:
                if 'nullable' in kwargs and kwargs['nullable'] == True:
                    self.doSet(self._groupy(group), str(key), {'__null__': 1}, expiry)
        return value

    def set(self, group, key, value, expiry = None):
        """
        :param group:
        :param key:
        :param value:
        :param expiry:
        :return:

        Contoh:
            result = cache.set('TEST', 1, 'oke', 30)
            result => True / False
        """
        return self.doSet(self._groupy(group), str(key), value, expiry)

    def expire(self, group, key, expiry = None):
        """
        :param group:
        :param key:
        :param expiry:
        :return:

        Untuk merubah waktu kadalauarsa obyek di cache
        Contoh:
            result = cache.expire('TEST', 1, 30)
            result => True / False
        """
        return self.doExpire(self._groupy(group), str(key), expiry)

    def delete(self, group, key):
        """
        :param group:
        :param key:
        :return:

        Contoh:
            result = cache.delete('TEST', 1)
            result => True / False
        """
        return self.doDelete(self._groupy(group), str(key))

    def exists(self, group, key):
        """
        :param group:
        :param key:
        :return:

        Contoh:
            result = cache.exists('TEST', 1)
            result => True / False
        """
        return self.isExists(self._groupy(group), str(key))

    def _groupy(self, group):
        return self.prefix + str(group)


    @abstractmethod
    def doGet(self, group, key):
        pass

    @abstractmethod
    def doSet(self, group, key, value, expiry = None):
        pass

    @abstractmethod
    def doExpire(self, group, key, expiry = None):
        pass

    @abstractmethod
    def doDelete(self, group, key):
        pass

    @abstractmethod
    def isExists(self, group, key):
        pass

    #
    #   CACHE={
    #	    'provider': 'apps.core.cache.redis.RedisCache',
    #	    'prefix': 'TEST::',
    #	    'config': {
    #		    'host': 'localhost',
    #		    'port': 6379,
    #		    'password': '',
    #		    'db': 0
    #	    }
    #   }
    def create(**kwargs):
        if 'provider' not in kwargs:
            raise ValueError('Cache provider is empty')
        if 'config' not in kwargs:
            raise ValueError('Cache config is empty or invalid value')
        prefix = None
        if 'prefix' in kwargs:
            prefix = kwargs['prefix']
        cache = load_module(kwargs['provider'])
        if cache is None:
            raise ValueError('Cannot find provider: ' + kwargs['provider'])
        return cache(prefix = prefix, config = kwargs['config'])


