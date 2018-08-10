package com.sprint.sms.api.dao;

import java.util.List;

import com.ideahut.common2.annotation.IdhAudit;
import com.ideahut.common2.annotation.IdhCache;
import com.ideahut.common2.annotation.IdhDao;
import com.ideahut.common2.annotation.IdhInvoker;
import com.ideahut.common2.annotation.IdhQuery;
import com.sprint.sms.api.dao.helper.RolePathHelper;
import com.sprint.sms.api.domain.Role;
import com.sprint.sms.api.domain.RolePath;
import com.sprint.sms.api.util.AppConstant;

@IdhDao(domainClass = RolePath.class)
@IdhAudit
public interface RolePathDao {
	
	@IdhQuery("FROM RolePath WHERE role.id = ? ORDER BY path ASC")
	public List<RolePath> findByRoleId(Long roleId);
	
	@IdhQuery("FROM RolePath WHERE role.id = ? AND path LIKE ? ORDER BY path ASC")
	public List<RolePath> findByRoleIdAndPathGroup(Long roleId, String group);
	
	public RolePath getByRoleIdAndPath(Long roleId, String path);
	
	@IdhCache(clear = AppConstant.CACHE_ACCESS_ROLE)
	public RolePath save(RolePath rolePath);

	@IdhCache(clear = AppConstant.CACHE_ACCESS_ROLE)
	public RolePath delete(String id);
	
	@IdhInvoker(target = RolePathHelper.class, field = RolePathHelper.FIELD_INVOKER_DELETE_BY_ROLE_AND_GROUP_AND_PATH_LIST)
	public Integer deleteByRoleAndGroupAndPathList(Role role, String group, List<String> paths);
	
	@IdhInvoker(target = RolePathHelper.class, field = RolePathHelper.FIELD_INVOKER_DELETE_BY_ROLE_AND_GROUP_LIST)
	public Integer deleteByRoleAndGroupList(Role role, List<String> groups);
	
}
