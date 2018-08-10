package com.sprint.sms.api.access;

import java.io.Serializable;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.Set;

import com.ideahut.common2.annotation.IdhFormatter;

@IdhFormatter
public class RoleAccess implements Serializable {
	
	/**
	 * 
	 */
	private static final long serialVersionUID = 2627232198404269622L;
	
	private Long id;
	
	private String name;

	private List<MenuAccess> menuList = new ArrayList<MenuAccess>();
	
	private Map<Long, MenuAccess> menuMap = new HashMap<Long, MenuAccess>();
	
	private Map<String, Set<String>> pathMap = new HashMap<String, Set<String>>(); // dipisahkan dengan parent path agar pencarian lebih baik :))

	@IdhFormatter
	public Long getId() {
		return id;
	}

	public void setId(Long id) {
		this.id = id;
	}

	@IdhFormatter
	public String getName() {
		return name;
	}

	public void setName(String name) {
		this.name = name;
	}

	@IdhFormatter
	public List<MenuAccess> getMenuList() {
		return menuList;
	}

	public void setMenuList(List<MenuAccess> menuList) {
		this.menuList = menuList;
	}

	@IdhFormatter
	public Map<Long, MenuAccess> getMenuMap() {
		return menuMap;
	}

	public void setMenuMap(Map<Long, MenuAccess> menuMap) {
		this.menuMap = menuMap;
	}

	@IdhFormatter
	public Map<String, Set<String>> getPathMap() {
		return pathMap;
	}

	public void setPathMap(Map<String, Set<String>> pathMap) {
		this.pathMap = pathMap;
	}

	public boolean isAllowedPath(String path) {
		if (path == null || path.length() == 0) {
			return false;
		}
		if (!path.startsWith("/")) {
			path = "/" + path;
		}
		String parent = path.substring(1);
		int pos = parent.indexOf("/");
		if (pos != -1) {
			parent = parent.substring(0, pos);
		}
		parent = "/" + parent;
		Set<String> pathSet = pathMap.get(parent);
		if (pathSet == null) {
			return false;
		}
		if (pathSet.isEmpty()) {
			return path.equals(parent);
		}
		return pathSet.contains(path);
	}
	
}
