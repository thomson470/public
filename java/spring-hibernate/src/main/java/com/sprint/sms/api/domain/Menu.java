package com.sprint.sms.api.domain;

import java.util.ArrayList;
import java.util.HashSet;
import java.util.List;
import java.util.Set;

import javax.persistence.Column;
import javax.persistence.Entity;
import javax.persistence.JoinColumn;
import javax.persistence.ManyToOne;
import javax.persistence.Table;
import javax.persistence.Transient;

import com.ideahut.common2.annotation.IdhFormatter;
import com.ideahut.shared2.domain.base.LongIdEntryVersionDomain;

@Entity
@Table(name = "t_menu")
@IdhFormatter
public class Menu extends LongIdEntryVersionDomain {	
	/**
	 * 
	 */
	private static final long serialVersionUID = 5611248316336571900L;

	private String title;
	
	private String link;
	
	private String icon;
	
	private String description;
	
	private Boolean active;
	
	private Menu parent;
	
	private Long priority; 
	
	private Boolean global;
	
	private List<Menu> children = new ArrayList<Menu>();
	
	private Set<String> action = new HashSet<String>();
	

	@Column(name = "title", length = 150, nullable = false)
	@IdhFormatter
	public String getTitle() {
		return title;
	}

	public void setTitle(String title) {
		this.title = title;
	}

	@Column(name = "link")
	@IdhFormatter
	public String getLink() {
		return link;
	}

	public void setLink(String link) {
		this.link = link;
	}

	@Column(name = "icon")
	@IdhFormatter
	public String getIcon() {
		return icon;
	}

	public void setIcon(String icon) {
		this.icon = icon;
	}
	
	@Column(name = "description")
	@IdhFormatter
	public String getDescription() {
		return description;
	}

	public void setDescription(String description) {
		this.description = description;
	}

	@Column(name = "active", nullable = false)
	@IdhFormatter
	public Boolean getActive() {
		return active;
	}

	public void setActive(Boolean active) {
		this.active = active;
	}

	@ManyToOne
	@JoinColumn(name = "f_parent")
	@IdhFormatter(field = Menu.ID)
	public Menu getParent() {
		return parent;
	}

	public void setParent(Menu parent) {
		this.parent = parent;
	}

	@Column(name = "priority", nullable = false)
	@IdhFormatter
	public Long getPriority() {
		return priority;
	}

	public void setPriority(Long priority) {
		this.priority = priority;
	}
	
	@Column(name = "global", nullable = false)
	@IdhFormatter
	public Boolean getGlobal() {
		return global;
	}

	public void setGlobal(Boolean global) {
		this.global = global;
	}

	@Transient
	@IdhFormatter
	public List<Menu> getChildren() {
		return children;
	}

	public void setChildren(List<Menu> children) {
		this.children = children;
	}
	
	@Transient
	@IdhFormatter
	public Set<String> getAction() {
		return action;
	}

	public void setAction(Set<String> action) {
		this.action = action;
	}
	
}
