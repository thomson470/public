package com.sprint.sms.api.dao;

import java.util.List;

import com.ideahut.common2.annotation.IdhAlias;
import com.ideahut.common2.annotation.IdhAudit;
import com.ideahut.common2.annotation.IdhCache;
import com.ideahut.common2.annotation.IdhDao;
import com.ideahut.common2.annotation.IdhInvoker;
import com.ideahut.shared2.repo.DaoController;
import com.sprint.sms.api.dao.helper.MenuHelper;
import com.sprint.sms.api.domain.Menu;
import com.sprint.sms.api.util.AppConstant;

@IdhDao(domainClass = Menu.class)
@IdhAudit
public interface MenuDao {
	
	@IdhInvoker(target = MenuHelper.class, field = MenuHelper.FIELD_INVOKER_GET_LIST)
	public List<Menu> getList();
	
	@IdhInvoker(target = MenuHelper.class, field = MenuHelper.FIELD_INVOKER_GET_LIST_BY_ROLE_ID)
	public List<Menu> getListByRoleId(Long roleId, Boolean active);
	
	@IdhAlias(DaoController.VIEW)
	Menu get(Long id);
	
	@IdhInvoker(target = MenuHelper.class, field = MenuHelper.FIELD_INVOKER_SORT)
	@IdhCache(clear = AppConstant.CACHE_ACCESS_ROLE)
	public Boolean sort(Long id, boolean moveUp);
	
	@IdhCache(clear = AppConstant.CACHE_ACCESS_ROLE)	
	public Menu save(Menu domain);
	
	@IdhCache(clear = AppConstant.CACHE_ACCESS_ROLE)
	public Menu delete(Long id);
	
	@IdhInvoker(target = MenuHelper.class, field = MenuHelper.FIELD_INVOKER_MAX_PRIORITY)
	public Long getMaxPriority();
	
}

