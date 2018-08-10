package com.sprint.sms.api.dao;

import com.ideahut.common2.annotation.IdhDao;
import com.sprint.sms.api.domain.Access;
import com.sprint.sms.api.domain.User;

@IdhDao(domainClass = Access.class)
public interface AccessDao {
	
	Access get(String id);
	
	Access getByUserId(Long userId);

	Access save(Access domain);

	Access delete(String id);
	
	Access deleteByUser(User user);	
	
}
