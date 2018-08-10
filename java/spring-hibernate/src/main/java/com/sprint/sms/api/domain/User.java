package com.sprint.sms.api.domain;

import java.util.Date;

import javax.persistence.CascadeType;
import javax.persistence.Column;
import javax.persistence.Entity;
import javax.persistence.JoinColumn;
import javax.persistence.ManyToOne;
import javax.persistence.Table;
import javax.persistence.Temporal;
import javax.persistence.TemporalType;

import com.ideahut.common2.annotation.IdhFormatter;
import com.ideahut.shared2.domain.base.LongIdEntryVersionDomain;

@Entity
@Table(name = "t_user")
@IdhFormatter
public class User extends LongIdEntryVersionDomain {	
	/**
	 * 
	 */
	private static final long serialVersionUID = -3515522573702790693L;

	private String name;
	
	private String password;
	
	private String firstName;	
	
	private String lastName;
	
	private String email;
	
	private String phone;
	
	private Boolean avatar;
	
	private Boolean active;
	
	private Date lastLoggedIn;
	
	private Date lastLoggedOut;
	
	private Role role;
	
	@Column(name = "name", length = 100, unique = true, nullable = false)
	@IdhFormatter
	public String getName() {
		return name;
	}

	public void setName(String name) {
		this.name = name;
	}

	@Column(name = "password", nullable = false)
	public String getPassword() {
		return password;
	}

	public void setPassword(String password) {
		this.password = password;
	}

	@Column(name = "firstname", length = 100, nullable = false)
	@IdhFormatter
	public String getFirstName() {
		return firstName;
	}

	public void setFirstName(String firstName) {
		this.firstName = firstName;
	}

	@Column(name = "lastname", length = 100)
	@IdhFormatter
	public String getLastName() {
		return lastName;
	}

	public void setLastName(String lastName) {
		this.lastName = lastName;
	}
	
	@Column(name = "email", length = 100)
	@IdhFormatter
	public String getEmail() {
		return email;
	}

	public void setEmail(String email) {
		this.email = email;
	}

	@Column(name = "phone", length = 100)
	@IdhFormatter
	public String getPhone() {
		return phone;
	}

	public void setPhone(String phone) {
		this.phone = phone;
	}
	
	@Column(name = "avatar", nullable = false)
	@IdhFormatter
	public Boolean getAvatar() {
		return avatar;
	}

	public void setAvatar(Boolean avatar) {
		this.avatar = avatar;
	}

	@Column(name = "active", nullable = false)
	@IdhFormatter
	public Boolean getActive() {
		return active;
	}

	public void setActive(Boolean active) {
		this.active = active;
	}

	@Column(name = "last_logged_in")
	@Temporal(TemporalType.TIMESTAMP)
	@IdhFormatter
	public Date getLastLoggedIn() {
		return lastLoggedIn;
	}

	public void setLastLoggedIn(Date lastLoggedIn) {
		this.lastLoggedIn = lastLoggedIn;
	}
	
	@Column(name = "last_logged_out")
	@Temporal(TemporalType.TIMESTAMP)
	@IdhFormatter
	public Date getLastLoggedOut() {
		return lastLoggedOut;
	}

	public void setLastLoggedOut(Date lastLoggedOut) {
		this.lastLoggedOut = lastLoggedOut;
	}

	@ManyToOne(cascade = CascadeType.MERGE)
	@JoinColumn(name = "f_role", nullable = false)
	@IdhFormatter
	public Role getRole() {
		return role;
	}

	public void setRole(Role role) {
		this.role = role;
	}
	
}