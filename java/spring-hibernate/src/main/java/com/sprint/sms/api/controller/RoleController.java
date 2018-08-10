package com.sprint.sms.api.controller;

import java.util.ArrayList;
import java.util.Arrays;
import java.util.HashSet;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.TreeMap;

import javax.servlet.http.HttpServletRequest;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Controller;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;

import com.ideahut.common2.dto.Page;
import com.ideahut.common2.dto.Response;
import com.ideahut.shared2.hibernate.OrderSpec;
import com.sprint.sms.api.access.ApiAccess;
import com.sprint.sms.api.base.BaseController;
import com.sprint.sms.api.dao.MenuDao;
import com.sprint.sms.api.dao.RoleDao;
import com.sprint.sms.api.dao.RoleMenuDao;
import com.sprint.sms.api.dao.RolePathDao;
import com.sprint.sms.api.domain.Menu;
import com.sprint.sms.api.domain.Role;
import com.sprint.sms.api.domain.RoleMenu;
import com.sprint.sms.api.domain.RolePath;
import com.sprint.sms.api.service.AccessService;
import com.sprint.sms.api.util.ObjectUtil;
import com.sprint.sms.api.util.RequestUtil;

@Controller
@RequestMapping(value = "/role", method = RequestMethod.POST)
public class RoleController extends BaseController {
	
	private static final Set<String> ignoredField;
	private static final Map<String, List<Integer>> rule;
	static {
		ignoredField = new HashSet<String>(ObjectUtil.getDefaultIgnoredField());
		rule = new TreeMap<String, List<Integer>>();
		rule.put("name", Arrays.asList(ObjectUtil.NOT_NULL, ObjectUtil.NOT_EMPTY));
		rule.put("active", Arrays.asList(ObjectUtil.NOT_NULL));
	}
	
	@Autowired
	private RoleDao roleDao;
	
	@Autowired
	private MenuDao menuDao;
	
	@Autowired
	private RolePathDao rolePathDao;
	
	@Autowired
	private RoleMenuDao roleMenuDao;
	
	@Autowired
	private AccessService accessService;
	
	
	@RequestMapping("/search")
	@Transactional
	public Response search() {
		HttpServletRequest request = getRequest();
		Page<Role> page = RequestUtil.paramsToPage(request, Role.class);
		Role role = RequestUtil.paramsToObject(request, Role.class);
		OrderSpec order = RequestUtil.paramsToOrder(request, Role.class);
		Page<Role> result = roleDao.page(page, role, order, null);
		return Response.SUCCESS(result);
	}
	
	@RequestMapping("/view")
	@Transactional
	public Response view() {
		HttpServletRequest request = getRequest();
		Long id = RequestUtil.paramsToId(request, Long.class);
		if (id == null) {
			return Response.ERROR("01", "id is required");
		}
		Role entity = roleDao.get(id);
		return Response.SUCCESS(entity);
	}
	
	@RequestMapping("/create")
	@Transactional
	public Response create() {
		HttpServletRequest request = getRequest();
		Role entity = RequestUtil.paramsToObject(request, Role.class);
		entity = roleDao.save(entity);
		return Response.SUCCESS(entity);
	}
	
	@RequestMapping("/update")
	@Transactional
	public Response update() {
		HttpServletRequest request = getRequest();
		Role newEntity = RequestUtil.paramsToObject(request, Role.class);
		Long id = newEntity.getId();
		if (id == null) {
			return Response.ERROR("01", "id is required");
		}
		Role oldEntity = roleDao.get(id);
		if (oldEntity == null) {
			return Response.ERROR("02", "Role is not found");
		}
		oldEntity = ObjectUtil.copy(Role.class, oldEntity, newEntity, ignoredField, rule);
		Role entity = roleDao.save(oldEntity);		
		return Response.SUCCESS(entity);
	}
	
	@RequestMapping("/delete")
	@Transactional
	public Response delete() {
		HttpServletRequest request = getRequest();
		Long id = RequestUtil.paramsToId(request, Long.class);
		if (id == null) {
			return Response.ERROR("01", "id is required");
		}
		Role entity = roleDao.delete(id);
		return Response.SUCCESS(entity);		
	}
	
	
	
	
	
	
	@RequestMapping("/path/list")
	@Transactional
	public Response path__list() {
		HttpServletRequest request = getRequest();
		RolePath entity = RequestUtil.paramsToObject(request, RolePath.class);
		Long roleId = entity != null && entity.getRole() != null ? entity.getRole().getId() : null;
		if (roleId == null) {
			return Response.ERROR("01", "Role Id is required");
		}
		String path = entity != null && entity.getPath() != null ? entity.getPath().trim() : "";
		List<RolePath> result;
		if (path.length() != 0) {
			result = rolePathDao.findByRoleIdAndPathGroup(roleId, path + "%");
		} else {
			result = rolePathDao.findByRoleId(roleId);
		}
		
		return Response.SUCCESS(result);
	}
	
	@RequestMapping("/path/save")
	@Transactional
	public Response path__save() {
		HttpServletRequest request = getRequest();
		RolePath entity = RequestUtil.paramsToObject(request, RolePath.class);
		Long roleId = entity != null && entity.getRole() != null ? entity.getRole().getId() : null;
		if (roleId == null) {
			return Response.ERROR("01", "Role Id is required");
		}
		String path = entity != null ? entity.getPath() : null;
		path = path != null ? path.trim() : "";
		if (path.length() == 0) {
			return Response.ERROR("02", "path is required");
		}
		RolePath rolePath = rolePathDao.getByRoleIdAndPath(roleId, path);
		if (rolePath != null) {
			return Response.SUCCESS(rolePath);
		}
		Role role = roleDao.get(roleId);
		if (role == null) {
			return Response.ERROR("03", "Role is not found");
		}
		rolePath = new RolePath();
		rolePath.setPath(path);
		rolePath.setRole(role);
		entity = rolePathDao.save(rolePath);
		return Response.SUCCESS(entity);
	}
	
	@RequestMapping("/path/delete")
	@Transactional
	public Response path__delete() {
		HttpServletRequest request = getRequest();
		String id = RequestUtil.paramsToId(request, String.class);
		if (id == null) {
			return Response.ERROR("01", "id is required");
		}
		RolePath entity = rolePathDao.delete(id);
		return Response.SUCCESS(entity);
	}
	
	@RequestMapping("/path/trash")
	@Transactional
	public Response path__trash() {
		HttpServletRequest request = getRequest();
		RolePath entity = RequestUtil.paramsToObject(request, RolePath.class);
		Role role = entity != null ? entity.getRole() : null;
		if (role != null) {
			Long roleId = role != null ? role.getId() : null;
			if (roleId == null) {
				return Response.ERROR("01", "Role Id is required");
			}
			role = roleDao.get(roleId);
			if (role == null) {
				return Response.ERROR("02", "Role is not found");
			}
		}
		Map<String, Integer> result = new TreeMap<String, Integer>();
		Map<String, Map<String, ApiAccess>> apiPrivate = accessService.getPrivateAccess();
		for (String group : apiPrivate.keySet()) {
			Map<String, ApiAccess> paths = apiPrivate.get(group);
			Integer count = rolePathDao.deleteByRoleAndGroupAndPathList(role, group, new ArrayList<String>(paths.keySet()));
			result.put(group, count);
		}
		Integer count = rolePathDao.deleteByRoleAndGroupList(role, new ArrayList<String>(apiPrivate.keySet()));
		result.put("UNKNOWN", count);
		return Response.SUCCESS(result);
	}
	
	
	
	
	@RequestMapping("/menu/list")
	@Transactional
	public Response menu__list() {
		HttpServletRequest request = getRequest();
		RoleMenu entity = RequestUtil.paramsToObject(request, RoleMenu.class);
		Long roleId = entity != null && entity.getRole() != null ? entity.getRole().getId() : null;
		if (roleId == null) {
			return Response.ERROR("01", "Role Id is required");
		}
		List<RoleMenu> result = roleMenuDao.findByRoleId(roleId);
		return Response.SUCCESS(result);
	}
	
	@RequestMapping("/menu/save")
	@Transactional
	public Response menu__save() {
		HttpServletRequest request = getRequest();
		RoleMenu entity = RequestUtil.paramsToObject(request, RoleMenu.class);
		Long roleId = entity != null && entity.getRole() != null ? entity.getRole().getId() : null;
		if (roleId == null) {
			return Response.ERROR("01", "Role Id is required");
		}
		Long menuId = entity != null && entity.getMenu() != null ? entity.getMenu().getId() : null;
		if (menuId == null) {
			return Response.ERROR("02", "Menu Id is required");
		}
		RoleMenu roleMenu = roleMenuDao.getByRoleIdAndMenuId(roleId, menuId);
		if (roleMenu != null) {
			return Response.SUCCESS(roleMenu);
		}
		Role role = roleDao.get(roleId);
		if (role == null) {
			return Response.ERROR("03", "Role is not found");
		}
		Menu menu = menuDao.get(menuId);
		if (menu == null) {
			return Response.ERROR("04", "Menu is not found");
		}
		String action = entity.getAction();
		action = action != null ? action.trim().replace(" ", "") : "";
		roleMenu = new RoleMenu();
		roleMenu.setRole(role);
		roleMenu.setMenu(menu);
		roleMenu.setAction(action);
		entity = roleMenuDao.save(roleMenu);
		return Response.SUCCESS(entity);
	}
	
	@RequestMapping("/menu/delete")
	@Transactional
	public Response menu__delete() {
		HttpServletRequest request = getRequest();
		String id = RequestUtil.paramsToId(request, String.class);
		if (id == null) {
			return Response.ERROR("01", "id is required");
		}
		RoleMenu entity = roleMenuDao.delete(id);
		return Response.SUCCESS(entity);
	}
	
	
}
