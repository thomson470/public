package com.sprint.sms.api.domain;

import javax.persistence.Column;
import javax.persistence.Entity;
import javax.persistence.Table;

import com.ideahut.common2.annotation.IdhFormatter;
import com.ideahut.shared2.domain.base.LongIdEntryVersionDomain;

@Entity
@Table(name = "t_role")
@IdhFormatter
public class Role extends LongIdEntryVersionDomain {	
	/**
	 * 
	 */
	private static final long serialVersionUID = -4881491628578757068L;
	
	private String name;
	
	private Boolean active;

	@Column(name = "name", length = 150, nullable = false)
	@IdhFormatter
	public String getName() {
		return name;
	}

	public void setName(String name) {
		this.name = name;
	}
	
	@Column(name = "active", nullable = false)
	@IdhFormatter
	public Boolean getActive() {
		return active;
	}

	public void setActive(Boolean active) {
		this.active = active;
	}
	
}
