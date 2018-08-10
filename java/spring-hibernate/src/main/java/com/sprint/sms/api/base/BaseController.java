package com.sprint.sms.api.base;

import javax.servlet.http.HttpServletRequest;

import org.springframework.beans.factory.annotation.Autowired;

import com.ideahut.shared2.service.CacheService;
import com.ideahut.shared2.service.LogService;

public abstract class BaseController {
	
	@Autowired
	private HttpServletRequest request;
	
	@Autowired
	private LogService logger;
	
	@Autowired
	private CacheService cache;
	

	public HttpServletRequest getRequest() {
		return request;
	}

	public LogService getLogger() {
		return logger;
	}

	public CacheService getCache() {
		return cache;
	}
	
}
