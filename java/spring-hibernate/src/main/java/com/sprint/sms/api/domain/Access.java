package com.sprint.sms.api.domain;

import javax.persistence.Column;
import javax.persistence.Entity;
import javax.persistence.FetchType;
import javax.persistence.JoinColumn;
import javax.persistence.ManyToOne;
import javax.persistence.Table;

import org.hibernate.annotations.OnDelete;
import org.hibernate.annotations.OnDeleteAction;

import com.ideahut.common2.annotation.IdhFormatter;
import com.ideahut.shared2.domain.base.StringIdEntryDomain;

/*
 * ID yang akan digunakan sebagai AccessKey
 * User unik -> Agent diperoleh dari request header 'User-Agent'
 * Validasi dari Access Key pada saat request adalah Agent
 */

@Entity
@Table(name = "t_access")
@IdhFormatter
public class Access extends StringIdEntryDomain {

	/**
	 * 
	 */
	private static final long serialVersionUID = 231481428978413107L;

	private User user;
	
	private String agent;
	
	private Long expired;

	@ManyToOne(targetEntity = User.class, fetch = FetchType.EAGER)
	@OnDelete(action = OnDeleteAction.CASCADE)
	@JoinColumn(name = "f_user", nullable = false, unique = true)
	@IdhFormatter
	public User getUser() {
		return user;
	}
	
	public void setUser(User user) {
		this.user = user;
	}

	@Column(name = "agent", length = 1024, nullable = false)
	@IdhFormatter
	public String getAgent() {
		return agent;
	}

	public void setAgent(String agent) {
		this.agent = agent;
	}

	@Column(name = "expired", nullable = false)
	@IdhFormatter
	public Long getExpired() {
		return expired;
	}

	public void setExpired(Long expired) {
		this.expired = expired;
	}
	
	public boolean hasExpired() {
		if (expired == null) {
			return true;
		}
		return System.currentTimeMillis() > expired;
	}
	
}