package com.sprint.sms.api.access;

import java.io.Serializable;
import java.util.ArrayList;
import java.util.HashSet;
import java.util.List;
import java.util.Set;

import com.ideahut.common2.annotation.IdhFormatter;

@IdhFormatter
public class MenuAccess implements Serializable {
	/**
	 * 
	 */
	private static final long serialVersionUID = 8822272953562229833L;
	
	private Long id;
	
	private String title;
	
	private String link;
	
	private String icon;
	
	private String description;
	
	private MenuAccess parent;
	
	private List<MenuAccess> children = new ArrayList<MenuAccess>();
	
	private Set<String> action = new HashSet<String>();	
	

	@IdhFormatter
	public Long getId() {
		return id;
	}

	public void setId(Long id) {
		this.id = id;
	}

	@IdhFormatter
	public String getTitle() {
		return title;
	}

	public void setTitle(String title) {
		this.title = title;
	}

	@IdhFormatter
	public String getLink() {
		return link;
	}

	public void setLink(String link) {
		this.link = link;
	}

	@IdhFormatter
	public String getIcon() {
		return icon;
	}

	public void setIcon(String icon) {
		this.icon = icon;
	}

	@IdhFormatter
	public String getDescription() {
		return description;
	}

	public void setDescription(String description) {
		this.description = description;
	}
	
	@IdhFormatter(field = "id")
	public MenuAccess getParent() {
		return parent;
	}

	public void setParent(MenuAccess parent) {
		this.parent = parent;
	}

	@IdhFormatter
	public List<MenuAccess> getChildren() {
		return children;
	}

	public void setChildren(List<MenuAccess> children) {
		this.children = children;
	}

	@IdhFormatter
	public Set<String> getAction() {
		return action;
	}

	public void setAction(Set<String> action) {
		this.action = action;
	}
	
}
