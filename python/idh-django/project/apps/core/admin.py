from django.contrib import admin
from django.contrib.auth.models import Permission
from django.contrib.admin.models import LogEntry
from django.contrib.sessions.models import Session

from apps.core.models.menu import Menu, MenuGroup

# Register your models here.

#
# ADMIN
#
class PermissionAdmin(admin.ModelAdmin):
	fields = ['name', 'content_type', 'codename']
	list_display = ['name', 'content_type', 'codename']
	search_fields = ['name', 'codename']
admin.site.register(Permission, PermissionAdmin)

class LogEntryAdmin(admin.ModelAdmin):
	fields = ['action_time', 'user', 'content_type', 'object_id', 'object_repr', 'action_flag', 'change_message']
	list_display = ['action_time', 'user', 'content_type', 'object_id', 'object_repr', 'action_flag', 'change_message']
	ordering = ['-action_time']
	#list_per_page=50 (default: 100)
	def has_add_permission(self, request):
		return False
admin.site.register(LogEntry, LogEntryAdmin)

class SessionAdmin(admin.ModelAdmin):
	fields = ['session_key', 'session_data', 'expire_date']
	list_display = ['session_key', 'session_data', 'expire_date']
	def has_add_permission(self, request):
		return False
admin.site.register(Session, SessionAdmin)


#
# PROJECT
#
class MenuAdmin(admin.ModelAdmin):
	fields = ['id', 'title', 'icon', 'link', 'active', 'priority', 'created_at', 'updated_at']
	list_display = ['id', 'title', 'icon', 'link', 'active', 'priority', 'parent', 'created_at', 'updated_at']
admin.site.register(Menu, MenuAdmin)

class MenuGroupAdmin(admin.ModelAdmin):
	fields = ['id', 'menu', 'group', 'created_at', 'updated_at']
	list_display = ['id', 'menu', 'group', 'created_at', 'updated_at']
admin.site.register(MenuGroup, MenuGroupAdmin)