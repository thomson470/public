package com.sprint.sms.api.controller;

import java.util.Arrays;
import java.util.HashSet;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.TreeMap;

import javax.servlet.http.HttpServletRequest;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Controller;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;

import com.ideahut.common2.dto.Response;
import com.sprint.sms.api.base.BaseController;
import com.sprint.sms.api.dao.MenuDao;
import com.sprint.sms.api.domain.Menu;
import com.sprint.sms.api.util.AppConstant;
import com.sprint.sms.api.util.ObjectUtil;
import com.sprint.sms.api.util.RequestUtil;

@Controller
@RequestMapping(value = "/menu", method = RequestMethod.POST)
public class MenuController extends BaseController {
	
	private static final Set<String> ignoredField;
	private static final Map<String, List<Integer>> rule;
	static {
		ignoredField = new HashSet<String>(ObjectUtil.getDefaultIgnoredField());
		ignoredField.add("parent");
		ignoredField.add("priority");
		rule = new TreeMap<String, List<Integer>>();
		rule.put("title", Arrays.asList(ObjectUtil.NOT_NULL, ObjectUtil.NOT_EMPTY));
		rule.put("link", Arrays.asList(ObjectUtil.NOT_NULL));
		rule.put("icon", Arrays.asList(ObjectUtil.NOT_NULL));
		rule.put("description", Arrays.asList(ObjectUtil.NOT_NULL));
		rule.put("active", Arrays.asList(ObjectUtil.NOT_NULL));
		rule.put("global", Arrays.asList(ObjectUtil.NOT_NULL));
		rule.put("action", Arrays.asList(ObjectUtil.NOT_NULL));
	}
	
	
	@Autowired
	private MenuDao menuDao;
	
	@RequestMapping("/all")
	@Transactional
	public Response all() {
		List<Menu> list = menuDao.getList();
		return Response.SUCCESS(list);
	}
	
	@RequestMapping("/view")
	@Transactional
	public Response view() {
		HttpServletRequest request = getRequest();
		Long id = RequestUtil.paramsToId(request, Long.class);
		if (id == null) {
			return Response.ERROR("01", "id is required");
		}
		Menu entity = menuDao.get(id);
		return Response.SUCCESS(entity);
	}
	
	@RequestMapping("/create")
	@Transactional
	public Response create() {
		HttpServletRequest request = getRequest();
		Menu entity = RequestUtil.paramsToObject(request, Menu.class);
		Menu parentObj = entity.getParent();
		Long parentId = parentObj != null ? parentObj.getId() : null;
		if (parentId != null) {
			parentObj = menuDao.get(parentId);
			if (parentObj == null) {
				return Response.ERROR("01", "Parent is not found");
			}
		} else {
			parentObj = null;
		}
		entity.setParent(parentObj);
		Long priority = menuDao.getMaxPriority();
		entity.setPriority(priority + 1);		
		entity = menuDao.save(entity);
		return Response.SUCCESS(entity);
	}
	
	@RequestMapping("/update")
	@Transactional
	public Response update() {
		HttpServletRequest request = getRequest();
		Menu newEntity = RequestUtil.paramsToObject(request, Menu.class);
		Long id = newEntity.getId();
		if (id == null) {
			return Response.ERROR("01", "id is required");
		}
		Menu oldEntity = menuDao.get(id);
		if (oldEntity == null) {
			return Response.ERROR("02", "Menu is not found");
		}
		String parentId = request.getParameter("parent" + AppConstant.SPLIT_OBJECT_FIELD + Menu.ID);
		if (parentId != null) {
			parentId = parentId.trim();
			if (parentId.length() != 0) {
				Long pid = new Long(parentId);
				if (oldEntity.getParent() == null || !pid.equals(oldEntity.getParent().getId())) {
					Menu parent = menuDao.get(pid);
					if (parent == null) {
						return Response.ERROR("03", "Parent is not found");
					}
					oldEntity.setParent(parent);
				}
			} else {
				oldEntity.setParent(null);
			}
		}
		oldEntity = ObjectUtil.copy(Menu.class, oldEntity, newEntity, ignoredField, rule);				
		Menu entity = menuDao.save(oldEntity);		
		return Response.SUCCESS(entity);
		
		
	}
	
	@RequestMapping("/delete")
	@Transactional
	public Response delete() {
		HttpServletRequest request = getRequest();
		Long id = RequestUtil.paramsToId(request, Long.class);
		if (id == null) {
			return Response.ERROR("01", "id is required");
		}
		Menu entity = menuDao.delete(id);
		return Response.SUCCESS(entity);
	}
	
	@RequestMapping("/sort")
	@Transactional
	public Response sort() {
		HttpServletRequest request = getRequest();
		Long id = RequestUtil.paramsToId(request, Long.class);
		String up = request.getParameter("up");
		boolean moveUp = "1".equals(up) || "true".equalsIgnoreCase(up);
		Boolean result = menuDao.sort(id, moveUp);
		return Response.SUCCESS(result);
	}
	
}
