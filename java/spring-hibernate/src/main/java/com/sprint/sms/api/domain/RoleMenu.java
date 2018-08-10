package com.sprint.sms.api.domain;

import java.util.Collections;
import java.util.HashSet;
import java.util.Set;

import javax.persistence.Column;
import javax.persistence.Entity;
import javax.persistence.FetchType;
import javax.persistence.JoinColumn;
import javax.persistence.ManyToOne;
import javax.persistence.Table;
import javax.persistence.Transient;
import javax.persistence.UniqueConstraint;

import org.hibernate.annotations.OnDelete;
import org.hibernate.annotations.OnDeleteAction;

import com.ideahut.common2.annotation.IdhFormatter;
import com.ideahut.shared2.domain.base.StringIdDomain;

@Entity
@Table(name = "t_role_menu", uniqueConstraints = {@UniqueConstraint(columnNames = {"f_role", "f_menu"})})
@IdhFormatter
public class RoleMenu extends StringIdDomain {
	/**
	 * 
	 */
	private static final long serialVersionUID = -459517068833168887L;
	
	private Role role;
	
	private Menu menu;
	
	private String action;
	
	private Set<String> actionAsSet;

	@ManyToOne(targetEntity = Role.class, fetch = FetchType.EAGER)
	@OnDelete(action = OnDeleteAction.CASCADE)
	@JoinColumn(name = "f_role", nullable = false)
	@IdhFormatter(field = Role.ID)
	public Role getRole() {
		return role;
	}

	public void setRole(Role role) {
		this.role = role;
	}
	
	@ManyToOne(targetEntity = Menu.class, fetch = FetchType.EAGER)
	@OnDelete(action = OnDeleteAction.CASCADE)
	@JoinColumn(name = "f_menu", nullable = false)
	@IdhFormatter(field = Menu.ID)
	public Menu getMenu() {
		return menu;
	}

	public void setMenu(Menu menu) {
		this.menu = menu;
	}
	
	@Column(name = "action")
	@IdhFormatter
	public String getAction() {
		return action;
	}

	public void setAction(String action) {
		this.action = action;
	}

	@Transient
	public Set<String> getActionAsSet() {
		if (actionAsSet != null) {
			return actionAsSet;
		}
		Set<String> set = new HashSet<String>();
		if (action != null) {
			String[] ss = action.split(",");
			for (String s : ss) {
				s = s.trim();
				if (s.length() != 0) {
					set.add(s);
				}
			}
		}
		actionAsSet = Collections.unmodifiableSet(set);
		return actionAsSet;
	}
	
}
