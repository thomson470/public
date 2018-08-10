package com.sprint.sms.api.dao;

import com.ideahut.common2.annotation.IdhAudit;
import com.ideahut.common2.annotation.IdhCache;
import com.ideahut.common2.annotation.IdhDao;
import com.ideahut.common2.dto.Page;
import com.ideahut.shared2.hibernate.CriteriaVisitor;
import com.ideahut.shared2.hibernate.OrderSpec;
import com.sprint.sms.api.domain.Role;
import com.sprint.sms.api.util.AppConstant;

@IdhDao(domainClass = Role.class)
@IdhAudit
public interface RoleDao {

	Page<Role> page(Page<Role> page, Role domain, OrderSpec orderSpec, CriteriaVisitor<Role> visitor);
	
	Role get(Long id);

	@IdhCache(remove = AppConstant.CACHE_ACCESS_ROLE, keyField = Role.ID)
	Role save(Role domain);

	@IdhCache(remove = AppConstant.CACHE_ACCESS_ROLE, keyIndex = 0)
	Role delete(Long id);
	
}
