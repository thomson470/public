package com.sprint.sms.api.domain;

import javax.persistence.Column;
import javax.persistence.Entity;
import javax.persistence.FetchType;
import javax.persistence.JoinColumn;
import javax.persistence.ManyToOne;
import javax.persistence.Table;
import javax.persistence.UniqueConstraint;

import org.hibernate.annotations.OnDelete;
import org.hibernate.annotations.OnDeleteAction;

import com.ideahut.common2.annotation.IdhFormatter;
import com.ideahut.shared2.domain.base.StringIdEntryDomain;

@Entity
@Table(name = "t_role_path", uniqueConstraints = {@UniqueConstraint(columnNames = {"f_role", "path"})})
@IdhFormatter
public class RolePath extends StringIdEntryDomain {
	/**
	 * 
	 */
	private static final long serialVersionUID = -459517068833168887L;
	
	private Role role;
	
	private String path; // Request Mapping	

	@ManyToOne(targetEntity = Role.class, fetch = FetchType.EAGER)
	@OnDelete(action = OnDeleteAction.CASCADE)
	@JoinColumn(name = "f_role", nullable = false)
	@IdhFormatter(field = "id")
	public Role getRole() {
		return role;
	}

	public void setRole(Role role) {
		this.role = role;
	}
	
	@Column(name = "path", nullable = false)
	@IdhFormatter
	public String getPath() {
		return path;
	}

	public void setPath(String path) {
		this.path = path;
	}
	
}
