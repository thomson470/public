import redis
from .base import Cache

class RedisCache(Cache):

    def __init__(self, **kwargs):
        super(RedisCache, self).__init__(**kwargs)
        self.connection = redis.StrictRedis(
            host = self.config['host'],
            port = self.config['port'],
            db = self.config['db'],
            password = self.config['password'],
            decode_responses = True
        )

    def doGet(self, group, key):
        return self.connection.get(group + key)

    def doSet(self, group, key, value, expiry = None):
        result = self.connection.set(group + key, value)
        if expiry is not None:
            self.connection.expire(group + key, expiry)
        return result

    def doExpire(self, group, key, expiry=None):
        if expiry is not None:
            return self.connection.expire(group + key, expiry)
        return False

    def doDelete(self, group, key):
        return self.connection.delete(group + key)

    def isExists(self, group, key):
        return self.connection.exists(group + key)