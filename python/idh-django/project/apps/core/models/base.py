from django.db import models
from django.db.models.fields.related import ManyToManyField


class Model(models.Model):
    class Meta:
        abstract = True

    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    #def __repr__(self):
    #    return str(self.to_dict(self))

    #def to_dict(self):
    #    print(self.__dict__)
        #opts = self._meta
        #data = {}
        #for f in opts.concrete_fields + opts.many_to_many:
            #if isinstance(f, ManyToManyField):
            #    if self.pk is None:
            #       data[f.name] = []
            #    else:
            #        data[f.name] = list(f.value_from_object(self).values_list('pk', flat=True))
            #else:
            #    data[f.name] = f.value_from_object(self)
        #    print(f.name)
        #return data


#
# TODO: Perlu cari cara bagaimana Model yang punya version (save dengan current_version < saved_version tidak bisa)
#
#class ModelVersion(Model):
#    class Meta:
#        abstract = True

#    version = models.(default = 0)

