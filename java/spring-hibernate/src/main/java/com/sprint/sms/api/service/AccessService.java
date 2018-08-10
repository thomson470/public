package com.sprint.sms.api.service;

import java.util.Map;

import javax.servlet.http.HttpServletRequest;

import com.ideahut.common2.dto.Response;
import com.sprint.sms.api.access.ApiAccess;
import com.sprint.sms.api.access.RoleAccess;
import com.sprint.sms.api.domain.Access;
import com.sprint.sms.api.domain.User;

public interface AccessService {	
	
	public Map<String, ApiAccess> getPublicAccess();
	
	public Map<String, Map<String, ApiAccess>> getPrivateAccess();
	
	public Response validatePath(HttpServletRequest request, String path);
	
	public Access getAccess(String id);
	
	public RoleAccess getRoleAccess(Long roleId);
	
	
	public User getUser(String name);
	
	public User updateUser(User user);
	
	
	public Access removeAccessById(String id);
	
	public Access removeAccessByUser(User user);
	
	public Access createAccess(User user, String agent);	

}
