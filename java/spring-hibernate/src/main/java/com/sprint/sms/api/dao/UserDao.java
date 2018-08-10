package com.sprint.sms.api.dao;

import com.ideahut.common2.annotation.IdhAudit;
import com.ideahut.common2.annotation.IdhDao;
import com.ideahut.common2.dto.Page;
import com.ideahut.shared2.hibernate.CriteriaVisitor;
import com.ideahut.shared2.hibernate.OrderSpec;
import com.sprint.sms.api.domain.User;

@IdhDao(domainClass = User.class)
@IdhAudit
public interface UserDao {
	
	Page<User> page(Page<User> page, User domain, OrderSpec orderSpec, CriteriaVisitor<User> visitor);
	
	User get(Long id);
	
	User save(User domain);

	User delete(Long id);
	
	public User getByName(String name);
	
}
