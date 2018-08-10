package com.sprint.sms.api.dao;

import java.util.List;

import com.ideahut.common2.annotation.IdhAudit;
import com.ideahut.common2.annotation.IdhCache;
import com.ideahut.common2.annotation.IdhDao;
import com.sprint.sms.api.domain.RoleMenu;
import com.sprint.sms.api.util.AppConstant;

@IdhDao(domainClass = RoleMenu.class)
@IdhAudit
public interface RoleMenuDao {
	
	public List<RoleMenu> findByRoleId(Long roleId);
	
	public RoleMenu getByRoleIdAndMenuId(Long roleId, Long menuId);
	
	@IdhCache(clear = AppConstant.CACHE_ACCESS_ROLE)
	public RoleMenu save(RoleMenu menuRole);

	@IdhCache(clear = AppConstant.CACHE_ACCESS_ROLE)
	public RoleMenu delete(String id);
	
}
