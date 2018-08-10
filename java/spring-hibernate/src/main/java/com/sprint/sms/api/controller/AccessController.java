package com.sprint.sms.api.controller;

import java.lang.reflect.Field;
import java.lang.reflect.Modifier;
import java.util.ArrayList;
import java.util.Collections;
import java.util.Date;
import java.util.List;
import java.util.Map;
import java.util.TreeMap;

import javax.servlet.http.HttpServletRequest;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;

import com.ideahut.common2.dto.Response;
import com.ideahut.common2.util.DigestUtil;
import com.ideahut.shared2.repo.DaoHandler;
import com.sprint.sms.api.access.ApiAccess;
import com.sprint.sms.api.access.MenuAccess;
import com.sprint.sms.api.access.RoleAccess;
import com.sprint.sms.api.base.BaseController;
import com.sprint.sms.api.domain.Access;
import com.sprint.sms.api.domain.Role;
import com.sprint.sms.api.domain.User;
import com.sprint.sms.api.service.AccessService;
import com.sprint.sms.api.util.AppConstant;
import com.sprint.sms.api.util.RequestUtil;

@Controller
@RequestMapping(value = "/access", method = RequestMethod.POST)
public class AccessController extends BaseController {

	@Autowired
	private AccessService accessService;
	
	@Value("${app.signOnAudit}")
	private Boolean signOnAudit;
	
	/*
	 * LOGIN
	 */
	@RequestMapping("/login")
	public Response login() {
		HttpServletRequest request = getRequest();
		String uname = request.getParameter("uname");
		if (uname == null || uname.trim().length() == 0) {
			return Response.ERROR("01", "uname is required");
		}
		String upass = request.getParameter("upass");
		if (upass == null || upass.trim().length() == 0) {
			return Response.ERROR("02", "upass is required");
		}
		String utime = request.getParameter("utime");;
		if (utime == null || utime.trim().length() == 0) {
			return Response.ERROR("03", "utime is required");
		}
		
		User user = accessService.getUser(uname);
		if (user == null) {
			return Response.ERROR("04", "user is not found");
		}
		String genPass = user.getName() + utime + user.getPassword();
		genPass = DigestUtil.digest("SHA-256", genPass);
		if (!upass.equals(genPass)) {
			return Response.ERROR("05", "upass is not valid");
		}
		Role role = user.getRole();
		if (role == null || !role.getActive()) {
			return Response.ERROR("06", "Role is not active");
		}
		if (!user.getActive()) {
			return Response.ERROR("07", "User is not active");
		}
		accessService.removeAccessByUser(user);
		String agent = RequestUtil.getUserAgent(request);
		Access access = accessService.createAccess(user, agent);
		
		boolean isAudit = signOnAudit != null ? signOnAudit.booleanValue() : false;
		if (isAudit) {
			DaoHandler.setAuditOff(false);
			DaoHandler.setAuditAction("LOGIN");
		}
		user.setLastLoggedIn(new Date());
		accessService.updateUser(user);
		
		return Response.SUCCESS(access.getId());
	}
	
	/*
	 * LOGOUT
	 */
	@RequestMapping("/logout")
	public Response logout() {
		HttpServletRequest request = getRequest();
		String accessKey = RequestUtil.getAccessKey(request);
		if (accessKey == null) {
			return Response.ERROR("01", "Access key is required");
		}
		Access access = accessService.removeAccessById(accessKey);
		if (access != null) {
			boolean isAudit = signOnAudit != null ? signOnAudit.booleanValue() : false;
			if (isAudit) {
				DaoHandler.setAuditOff(false);
				DaoHandler.setAuditAction("LOGOUT");
			}
			User user = access.getUser();
			user.setLastLoggedOut(new Date());
			accessService.updateUser(user);
		}
		return Response.SUCCESS();
	}
	
	
	/*
	 * MENU
	 */
	@RequestMapping("/menu")
	public Response menu() {
		HttpServletRequest request = getRequest();
		String accessKey = RequestUtil.getAccessKey(request);
		Long roleId = null;
		if (accessKey != null) {
			Access access = accessService.getAccess(accessKey);
			if (access != null && !access.hasExpired()) {
				roleId = access.getUser().getId();
			}
		}
		List<MenuAccess> menus;
		RoleAccess roleAccess = accessService.getRoleAccess(roleId);		
		if (roleAccess != null) {
			menus = roleAccess.getMenuList();
		} else {
			menus = new ArrayList<MenuAccess>();
		}
		return Response.SUCCESS(menus);
	}
	
	/*
	 * PROFILE
	 */
	@RequestMapping("/profile")
	public Response profile() {
		HttpServletRequest request = getRequest();
		String accessKey = RequestUtil.getAccessKey(request);
		if (accessKey == null) {
			return Response.ERROR("01", "Access key is required");
		}
		Access access = accessService.getAccess(accessKey);
		if (access == null) {
			return Response.ERROR("01", "Access key is not found");
		}
		User user = access.getUser();
		return Response.SUCCESS(user);
	}
	
	/*
	 * CACHE CLEAR
	 *   Untuk membersihkan/menghapus group cache object dari memory
	 */
	@RequestMapping("/cache/clear")
	public Response cache__clear() {
		HttpServletRequest request = getRequest();
		String group = request.getParameter("group");
		String[] list = (group != null ? group.trim().toUpperCase() : "").split(",");
		Map<String, String> map = new TreeMap<String, String>();
		for (String grp : list) {
			grp = grp.trim();
			if (grp.length() == 0) {
				continue;
			}
			try {
				getCache().clear(grp);
				map.put(grp, Response.Status.SUCCESS.name());
			} catch (Exception e) {
				map.put(grp, Response.Status.FAILED.name() + ": " + e);
			}
		}		
		return Response.SUCCESS(map);
	}
	
	
	/*
	 * API GROUP
	 *   Untuk mendapatkan daftar API group
	 */
	@RequestMapping("/api/group")
	public Response api__group() {
		Map<String, Map<String, ApiAccess>> apiPrivate = accessService.getPrivateAccess();
		List<String> result = new ArrayList<String>(apiPrivate.keySet());
		Collections.sort(result);
		return Response.SUCCESS(result);
	}
	
	/*
	 * API LIST
	 *   Untuk mendapatkan daftar API list
	 */
	@RequestMapping("/api/list")
	public Response api__list() {
		HttpServletRequest request = getRequest();
		String group = request.getParameter("group");
		if (group == null) {
			return Response.ERROR("01", "Group is required");
		}
		Map<String, Map<String, ApiAccess>> apiPrivate = accessService.getPrivateAccess();
		Map<String, ApiAccess> result = apiPrivate.get(group);
		return Response.SUCCESS(result);
	}
	
	
	/*
	 * APP CONSTANT
	 *   Untuk mendapatkan konstanta yang ada di AppConstant
	 */
	@RequestMapping("/app/constant")
	public Response app__constant() {
		HttpServletRequest request = getRequest();
		String name = request.getParameter("name");
		name = name != null ? name.trim() : "";
		String prefix = request.getParameter("prefix");
		prefix = prefix != null ? prefix.trim() : "";
		Class<?> cls = AppConstant.class;
		Map<String, Object> result = new TreeMap<String, Object>();
		if (name.length() != 0) {
			try {
				Field f = cls.getField(name);
				int m = f.getModifiers();
				if (Modifier.isFinal(m) && Modifier.isStatic(m) ) { 
					Object value = f.get(null);
					result.put(f.getName(), value);
				}
			} catch (Exception e) {	}			
		}
		else if (prefix.length() != 0) {
			Field[] fields = cls.getFields();
			for (Field f : fields) {
				if (!f.getName().startsWith(prefix)) {
					continue;
				}
				int m = f.getModifiers();
				if (Modifier.isFinal(m) && Modifier.isStatic(m) ) { 
					Object value = getFieldValue(f);
					result.put(f.getName(), value);
				}
			}			
		} 
		else {
			Field[] fields = cls.getFields();
			for (Field f : fields) {
				int m = f.getModifiers();
				if (Modifier.isFinal(m) && Modifier.isStatic(m) ) { 
					Object value = getFieldValue(f);
					result.put(f.getName(), value);
				}
			}
		}
		return Response.SUCCESS(result);
	}
	
	private Object getFieldValue(Field field) {
		try {
			return field.get(null);
		} catch (Exception e) {}
		return null;
	}
	
}
