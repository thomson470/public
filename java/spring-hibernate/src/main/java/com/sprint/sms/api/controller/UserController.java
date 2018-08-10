package com.sprint.sms.api.controller;

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
import com.ideahut.common2.util.DigestUtil;
import com.ideahut.shared2.hibernate.OrderSpec;
import com.sprint.sms.api.base.BaseController;
import com.sprint.sms.api.dao.RoleDao;
import com.sprint.sms.api.dao.UserDao;
import com.sprint.sms.api.domain.Role;
import com.sprint.sms.api.domain.User;
import com.sprint.sms.api.util.AppConstant;
import com.sprint.sms.api.util.ObjectUtil;
import com.sprint.sms.api.util.RequestUtil;

@Controller
@RequestMapping(value = "/user", method = RequestMethod.POST)
public class UserController extends BaseController {
	
	private static final Set<String> ignoredField;
	private static final Map<String, List<Integer>> rule;
	static {
		ignoredField = new HashSet<String>(ObjectUtil.getDefaultIgnoredField());
		ignoredField.add("role");
		ignoredField.add("name");
		ignoredField.add("password");
		rule = new TreeMap<String, List<Integer>>();
		rule.put("firstName", Arrays.asList(ObjectUtil.NOT_NULL, ObjectUtil.NOT_EMPTY));
		rule.put("active", Arrays.asList(ObjectUtil.NOT_NULL));
		rule.put("lastName", Arrays.asList(ObjectUtil.NOT_NULL));
		rule.put("email", Arrays.asList(ObjectUtil.NOT_NULL, ObjectUtil.NOT_EMPTY));
		rule.put("phone", Arrays.asList(ObjectUtil.NOT_NULL));
		rule.put("avatar", Arrays.asList(ObjectUtil.NOT_NULL));
	}
	
	@Autowired
	private UserDao userDao;
	
	@Autowired
	private RoleDao roleDao;
	
	@RequestMapping("/search")
	@Transactional
	public Response search() {
		HttpServletRequest request = getRequest();
		Page<User> page = RequestUtil.paramsToPage(request, User.class);
		User user = RequestUtil.paramsToObject(request, User.class);
		OrderSpec order = RequestUtil.paramsToOrder(request, User.class);
		Page<User> result = userDao.page(page, user, order, null);
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
		User entity = userDao.get(id);
		return Response.SUCCESS(entity);
	}
	
	@RequestMapping("/create")
	@Transactional
	public Response create() {
		try {
		HttpServletRequest request = getRequest();
		User entity = RequestUtil.paramsToObject(request, User.class);
		String userName = entity.getName();
		if (userName == null || userName.trim().length() == 0) {
			return Response.ERROR("01", "name is required");
		}
		String password = entity.getPassword();
		if (password == null || password.trim().length() == 0) {
			return Response.ERROR("02", "password is required");
		}
		User user = userDao.getByName(userName);
		if (user != null) {
			return Response.ERROR("03", "User name has been exist");
		}		
		Role roleObj = entity.getRole();
		Long roleId = roleObj != null ? roleObj.getId() : null;
		if (roleId == null) {
			return Response.ERROR("04", "Role id is required");
		}
		roleObj = roleDao.get(roleId);
		if (roleObj == null) {
			return Response.ERROR("05", "Role is not found");
		}
		if (entity.getAvatar() == null) {
			entity.setAvatar(Boolean.FALSE);
		}
		entity.setRole(roleObj);
		password = DigestUtil.digest("SHA-256", password);
		entity.setPassword(password);
		entity = userDao.save(entity);
		return Response.SUCCESS(entity);
		} catch (Exception e) {
			e.printStackTrace();
		}
		return null;
	}
	
	@RequestMapping("/update")
	@Transactional
	public Response update() {
		HttpServletRequest request = getRequest();
		User newEntity = RequestUtil.paramsToObject(request, User.class);
		Long id = newEntity.getId();
		if (id == null) {
			return Response.ERROR("01", "id is required");
		}
		User oldEntity = userDao.get(id);
		if (oldEntity == null) {
			return Response.ERROR("02", "User is not found");
		}
		String roleId = request.getParameter("role" + AppConstant.SPLIT_OBJECT_FIELD + Role.ID);
		if (roleId != null) {
			roleId = roleId.trim();
			if (roleId.length() != 0) {
				Long rid = new Long(roleId);
				if (!rid.equals(oldEntity.getRole().getId())) {
					Role role = roleDao.get(rid);
					if (role == null) {
						return Response.ERROR("03", "Role is not found");
					}
					oldEntity.setRole(role);
				}
			}
		}
		String password = newEntity.getPassword();
		if (password != null && password.trim().length() != 0) {
			if (!password.equals(oldEntity.getPassword())) {
				password = DigestUtil.digest("SHA-256", password);
				oldEntity.setPassword(password);
			}
		}		
		oldEntity = ObjectUtil.copy(User.class, oldEntity, newEntity, ignoredField, rule);
		User entity = userDao.save(oldEntity);
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
		User entity = userDao.delete(id);
		return Response.SUCCESS(entity);
	}
	
}
