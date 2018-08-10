package com.sprint.sms.api.service.impl;

import java.io.File;
import java.util.ArrayList;
import java.util.Collections;
import java.util.Date;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.TreeMap;
import java.util.concurrent.Callable;

import javax.servlet.http.HttpServletRequest;

import org.apache.commons.io.FileUtils;
import org.json.JSONArray;
import org.json.JSONObject;
import org.springframework.beans.factory.InitializingBean;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import com.ideahut.common2.dto.Response;
import com.ideahut.shared2.service.CacheService;
import com.sprint.sms.api.access.ApiAccess;
import com.sprint.sms.api.access.MenuAccess;
import com.sprint.sms.api.access.RoleAccess;
import com.sprint.sms.api.dao.AccessDao;
import com.sprint.sms.api.dao.MenuDao;
import com.sprint.sms.api.dao.RoleDao;
import com.sprint.sms.api.dao.RolePathDao;
import com.sprint.sms.api.dao.UserDao;
import com.sprint.sms.api.domain.Access;
import com.sprint.sms.api.domain.Menu;
import com.sprint.sms.api.domain.Role;
import com.sprint.sms.api.domain.RolePath;
import com.sprint.sms.api.domain.User;
import com.sprint.sms.api.service.AccessService;
import com.sprint.sms.api.util.AppConstant;
import com.sprint.sms.api.util.RequestUtil;

@Service
public class AccessServiceImpl implements AccessService, InitializingBean {
	
	@Autowired
	private CacheService cacheService;
	
	@Autowired
	private AccessDao accessDao;
	
	@Autowired
	private MenuDao menuDao;
	
	@Autowired
	private UserDao userDao;
	
	@Autowired
	private RoleDao roleDao;
	
	@Autowired
	private RolePathDao rolePathDao;
	
	@Value("${app.api.file}")
	private File apiFile;
	
	@Value("${app.accessExpired}")
	private Long accessExpiredInSeconds;
	
	@Override
	public void afterPropertiesSet() throws Exception {
		getPublicAccess();
		getPrivateAccess();
	}
	
	@Override
	public Map<String, ApiAccess> getPublicAccess() {
		return cacheService.get(AppConstant.CACHE_API_LIST, AppConstant.API_KEY_PUBLIC, new Callable<Map<String, ApiAccess>>() {
			@Override
			public Map<String, ApiAccess> call() throws Exception {
				return getApiAccess(AppConstant.API_KEY_PUBLIC);
			}
		});
	}

	@Override
	public Map<String, Map<String, ApiAccess>> getPrivateAccess() {
		return cacheService.get(AppConstant.CACHE_API_LIST, AppConstant.API_KEY_PRIVATE, new Callable<Map<String, Map<String, ApiAccess>>>() {
			@Override
			public Map<String, Map<String, ApiAccess>> call() throws Exception {
				Map<String, ApiAccess> apiPrivate = getApiAccess(AppConstant.API_KEY_PRIVATE);
				Map<String, Map<String, ApiAccess>> result = new HashMap<String, Map<String, ApiAccess>>();
				for (String key : apiPrivate.keySet()) {
					String parent = key.substring(1);
					int idx = parent.indexOf("/");
					if (idx != -1) {
						parent = parent.substring(0, idx);
					}
					parent = "/" + parent;
					Map<String, ApiAccess> map = result.get(parent);
					if (map == null) {
						map = new TreeMap<String, ApiAccess>();
						result.put(parent, map);
					}
					map.put(key, apiPrivate.get(key));
				}
				return result;
			}
		});
	}

	@Transactional
	@Override
	public Response validatePath(HttpServletRequest request, String path) {
		Map<String, ApiAccess> apiPublic = getPublicAccess();
		boolean isPublic = apiPublic.containsKey(path);
		if (isPublic) {
			return null;
		}
		String key = RequestUtil.getAccessKey(request);
		key = key != null ? key.trim() : "";
		if (key.length() == 0) {
			return Response.ERROR("90", "Access Key is required");
		}
		Access access = getAccess(key);
		if (access == null) {
			return Response.ERROR("91", "User Access is not found");
		}
		String agent = RequestUtil.getUserAgent(request); // Sebaiknya jangan hanya user-agent tapi juga host / IP
		agent = agent != null ? agent : "";
		if (!agent.equals(access.getAgent())) {
			return Response.ERROR("92", "User Access is not valid");
		}
		if (access.hasExpired()) {
			return Response.ERROR("93", "Access Key has been expired");
		}
		RoleAccess roleAccess = getRoleAccess(access.getUser().getRole().getId());
		if (roleAccess == null) {
			return Response.ERROR("94", "Access Role is not found");
		}
		if (!roleAccess.isAllowedPath(path)) {
			return Response.ERROR("95", "Access Path is not allowed");
		}
		return null;
	}

	@Transactional
	@Override
	public Access getAccess(final String id) {
		return cacheService.get(AppConstant.CACHE_ACCESS_ID, id, new Callable<Access>() {			
			@Override
			public Access call() throws Exception {
				return accessDao.get(id);
			}			
		});
	}

	@Transactional
	@Override
	public RoleAccess getRoleAccess(Long roleId) {
		final Long id = roleId != null ? roleId : -1;
		return cacheService.get(AppConstant.CACHE_ACCESS_ROLE, id, new Callable<RoleAccess>() {
			@Override
			public RoleAccess call() throws Exception {
				List<Menu> menuList = menuDao.getListByRoleId(id, Boolean.TRUE);
				if (menuList == null) {
					return null;
				}
				List<MenuAccess> list = new ArrayList<MenuAccess>();
				Map<Long, MenuAccess> map = new HashMap<Long, MenuAccess>();
				for (Menu m : menuList) {
					MenuAccess sm = mapper(m);
					map.put(sm.getId(), copy(sm));					
					for (Menu c : m.getChildren()) {
						MenuAccess sc = mapper(c);
						sc.setParent(mapper(m));
						for (Menu g : c.getChildren()) {
							MenuAccess sg = mapper(g);
							sg.setParent(mapper(c));
							sg.getParent().setParent(mapper(m));
							sc.getChildren().add(sg);
							map.put(sg.getId(), copy(sg));
						}
						sm.getChildren().add(sc);			
						map.put(sc.getId(), copy(sc));
					}
					list.add(sm);
				}
				RoleAccess o = new RoleAccess();
				o.setId(id);
				Map<String, Set<String>> pathMap = new HashMap<String, Set<String>>();
				if (id > 0) {
					Role role = roleDao.get(id);
					if (role != null) {
						o.setId(role.getId());
						o.setName(role.getName());
						List<RolePath> rolePathList = rolePathDao.findByRoleId(o.getId());
						if (rolePathList != null) {						
							while (!rolePathList.isEmpty()) {
								RolePath rolePath = rolePathList.remove(0);
								String path = rolePath.getPath();
								String parent = path.substring(1);
								int idx = parent.indexOf("/");
								if (idx != -1) {
									parent = parent.substring(0, idx);
								}
								parent = "/" + parent;
								Set<String> set = pathMap.get(parent);
								if (set == null) {
									set = new HashSet<String>();
									pathMap.put(parent, set);
								}
								if (!path.equals(parent)) {
									set.add(path);
								}
							}
						}
					}
				}
				o.setPathMap(Collections.unmodifiableMap(pathMap));
				o.setMenuList(Collections.unmodifiableList(list));
				o.setMenuMap(Collections.unmodifiableMap(map));
				return o;
			}
		});
	}
	
	@Transactional
	@Override
	public User getUser(String name) {
		return userDao.getByName(name);
	}

	@Transactional
	@Override
	public User updateUser(User user) {
		return userDao.save(user);
	}

	@Transactional
	@Override
	public Access removeAccessById(String id) {
		cacheService.remove(AppConstant.CACHE_ACCESS_ID, id);
		return accessDao.delete(id);
	}

	@Transactional
	@Override
	public Access removeAccessByUser(User user) {
		Access access = accessDao.deleteByUser(user);
		if (access != null) {
			cacheService.remove(AppConstant.CACHE_ACCESS_ID, access.getId());
		}
		return access;
	}

	@Transactional
	@Override
	public Access createAccess(User user, String agent) {
		Access access = new Access();
		access.setAgent(agent);
		access.setEntryTime(new Date());
		access.setExpired(System.currentTimeMillis() + (accessExpiredInSeconds * 1000));
		access.setUser(user);
		return accessDao.save(access);
	}
	
	
	
	
	
	
	private MenuAccess mapper(Menu menu) {
		MenuAccess m = new MenuAccess();
		for (String a : menu.getAction()) {
			m.getAction().add(a);
		}
		m.setDescription(menu.getDescription());
		m.setIcon(menu.getIcon());
		m.setId(menu.getId());
		m.setLink(menu.getLink());
		m.setTitle(menu.getTitle());
		return m;		
	}
	
	private MenuAccess copy(MenuAccess sm) {
		MenuAccess m = new MenuAccess();
		for (String a : sm.getAction()) {
			m.getAction().add(a);
		}
		m.setDescription(sm.getDescription());
		m.setIcon(sm.getIcon());
		m.setId(sm.getId());
		m.setLink(sm.getLink());
		m.setTitle(sm.getTitle());		
		MenuAccess parent = sm.getParent();
		if (parent != null) {
			MenuAccess p = new MenuAccess();
			p.setDescription(parent.getDescription());
			p.setIcon(parent.getIcon());
			p.setId(parent.getId());
			p.setLink(parent.getLink());
			p.setTitle(parent.getTitle());
			MenuAccess grandParent = parent.getParent();
			if (grandParent != null) {
				MenuAccess gp = new MenuAccess();
				gp.setDescription(grandParent.getDescription());
				gp.setIcon(grandParent.getIcon());
				gp.setId(grandParent.getId());
				gp.setLink(grandParent.getLink());
				gp.setTitle(grandParent.getTitle());
				p.setParent(gp);
			}
			m.setParent(p);
		}		
		return m;
	}
	
	private Map<String, ApiAccess> getApiAccess(String group) throws Exception {
		String content = FileUtils.readFileToString(apiFile);
		JSONObject jo = new JSONObject(content);
		Map<String, ApiAccess> result = new TreeMap<String, ApiAccess>();
		if (!jo.has(group)) {
			return result;
		}
		JSONArray ja = jo.getJSONArray(group);
		int count = ja.length();
		for (int i = 0; i < count; i++) {
			JSONObject map = ja.getJSONObject(i);
			if (!map.has(AppConstant.API_KEY_PATH)) {
				continue;
			}
			String path = map.getString(AppConstant.API_KEY_PATH).trim();
			if (!path.startsWith("/")) {
				path = "/" + path;
			}
			if (result.containsKey(path)) {
				throw new Exception("Duplicate API path: " + path);
			}
			ApiAccess apiAccess = new ApiAccess();
			if (map.has(AppConstant.API_KEY_DESCRIPTION)) {
				apiAccess.setDescription(map.getString(AppConstant.API_KEY_DESCRIPTION));
			}
			if (map.has(AppConstant.API_KEY_PARAMETER)) {
				Map<String, String> apiParam = new TreeMap<String, String>();
				JSONObject param = map.getJSONObject(AppConstant.API_KEY_PARAMETER);
				for (Object okey : param.keySet()) {
					String key = (String)okey;
					apiParam.put(key, param.getString(key));
				}
				apiAccess.setParameter(apiParam);
			}
			result.put(path, apiAccess);
		}
		return result;
	}
	
}
