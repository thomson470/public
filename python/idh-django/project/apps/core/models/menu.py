from django.db import models
from django.db.models import Max
from .base import Model
from django.contrib.auth.models import Group

class Menu(Model):
    class Meta:
        db_table = 't_menu'

    title = models.CharField(max_length = 255, null = False)
    icon = models.CharField(max_length = 255, null = True)
    link = models.CharField(max_length = 255, null = True)
    active = models.BooleanField(default = True)
    priority = models.IntegerField(null = False)
    parent = models.ForeignKey("self", null = True, on_delete = models.SET_NULL)

    def save(self, *args, **kwargs):
        if not self.priority:
            max = Menu.objects.all().aggregate(Max('priority'))
            p = 0
            if max['priority__max'] is not None:
                p = max['priority__max']
            self.priority = p + 1
        super(Menu, self).save(*args, **kwargs)


class MenuGroup(Model):
    class Meta:
        db_table = 't_menu_group'
        unique_together = ('menu', 'group')

    menu = models.ForeignKey(Menu, null = False, on_delete = models.CASCADE)
    group = models.ForeignKey(Group, null = False, on_delete = models.CASCADE)
