package com.sprint.sms.api.controller;

import java.util.Arrays;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.TreeMap;
import java.util.concurrent.Callable;

import javax.servlet.http.HttpServletRequest;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;

import com.ideahut.common2.cache.SingleCache;
import com.ideahut.common2.dto.Response;
import com.ideahut.shared2.service.CacheService;
import com.ideahut.shared2.service.LogService.Level;
import com.sprint.sms.api.access.ApiAccess;
import com.sprint.sms.api.base.BaseController;
import com.sprint.sms.api.bean.dummy.Dummy1;
import com.sprint.sms.api.domain.Role;
import com.sprint.sms.api.service.AccessService;
import com.sprint.sms.api.util.AppConstant;
import com.sprint.sms.api.util.ObjectUtil;
import com.sprint.sms.api.util.RequestUtil;

@Controller
@RequestMapping(value = "/test", method = RequestMethod.POST)
public class TestController extends BaseController {
	
	private static final String LOG = TestController.class.getName();
	
	@Autowired
	private AccessService accessService;
	
	@RequestMapping("/object/cascade")
	public Response object__cascade() {
		getLogger().log(LOG, Level.DEBUG, "object__cascade");
		HttpServletRequest request = getRequest();
		Dummy1 obj = RequestUtil.paramsToObject(request, Dummy1.class);
		return Response.SUCCESS(obj);
	}
	
	@RequestMapping("/object/copy")
	public Response object__copy() {
		getLogger().log(LOG, Level.DEBUG, "object__copy");
		Role dest = new Role();
		Role src = new Role();
		src.setName("Nama");
		src.setActive(true);
		
		Map<String, Object> result = new TreeMap<String, Object>();
		result.put("DEST_BEFORE", dest);
		result.put("SRC_BEFORE", src);
		Map<String, List<Integer>> rule = new HashMap<String, List<Integer>>();
		rule.put("name", Arrays.asList(ObjectUtil.NOT_NULL, ObjectUtil.NOT_EMPTY));
		dest = new Role();
		ObjectUtil.copy(Role.class, dest, src, Arrays.asList("id", "version", "entryTime"), rule);
		result.put("DEST_AFTER", dest);
		result.put("SRC_AFTER", src);
		return Response.SUCCESS(result);
	}
	
	@RequestMapping("/convert/params")
	public Response convert__params() {
		getLogger().log(LOG, Level.DEBUG, "convert__params");
		HttpServletRequest request = getRequest();
		Role obj = RequestUtil.paramsToObject(request, Role.class);
		return Response.SUCCESS(obj);
	}
	
	@RequestMapping("/convert/body")
	public Response convert__body() {
		getLogger().log(LOG, Level.DEBUG, "convert__body");
		HttpServletRequest request = getRequest();
		Role obj = RequestUtil.bodyToObject(request, Role.class, AppConstant.TYPE_JSON);
		return Response.SUCCESS(obj);
	}
	
	@RequestMapping("/cache/get")
	public Response cache__get() {
		getLogger().log(LOG, Level.DEBUG, "cache__get");
		HttpServletRequest request = getRequest();
		String id = request.getParameter("id");
		CacheService cache = getCache();
		SingleCache<Long> singleCache = cache.get("TEST", id, new Callable<SingleCache<Long>>() {
			@Override
			public SingleCache<Long> call() throws Exception {
				return new SingleCache<Long>(System.currentTimeMillis());
			}			
		});
		Long val = singleCache != null ? singleCache.getObject() : null;
		return Response.SUCCESS(val);
	}
	
	@RequestMapping("/cache/get/0")
	public Response cache__get__0() {
		getLogger().log(LOG, Level.DEBUG, "cache__get__0");
		HttpServletRequest request = getRequest();
		String id = request.getParameter("id");
		CacheService cache = getCache();
		SingleCache<Long> singleCache = cache.get("TEST", id);
		Long val = singleCache != null ? singleCache.getObject() : null;
		return Response.SUCCESS(val);
	}
	
	@RequestMapping("/cache/remove")
	public Response cache__remove() {
		getLogger().log(LOG, Level.DEBUG, "cache__remove");
		HttpServletRequest request = getRequest();
		String id = request.getParameter("id");
		CacheService cache = getCache();
		cache.remove("TEST", id);
		return Response.SUCCESS();
	}
	
	@RequestMapping("/cache/clear")
	public Response cache__clear() {
		getLogger().log(LOG, Level.DEBUG, "cache__clear");
		CacheService cache = getCache();
		cache.clear("TEST");
		return Response.SUCCESS();
	}
	
	@RequestMapping("/api/private")
	public Response api__private() {
		getLogger().log(LOG, Level.DEBUG, "api__private");
		Map<String, Map<String, ApiAccess>> result = accessService.getPrivateAccess();
		return Response.SUCCESS(result);
	}
	
	@RequestMapping("/api/public")
	public Response api__public() {
		getLogger().log(LOG, Level.DEBUG, "api__public");
		Map<String, ApiAccess> result = accessService.getPublicAccess();
		return Response.SUCCESS(result);
	}
}
