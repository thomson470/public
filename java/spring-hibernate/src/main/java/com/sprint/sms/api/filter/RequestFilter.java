package com.sprint.sms.api.filter;

import java.io.IOException;
import java.util.HashMap;
import java.util.Locale;
import java.util.Map;

import javax.servlet.Filter;
import javax.servlet.FilterChain;
import javax.servlet.FilterConfig;
import javax.servlet.ServletException;
import javax.servlet.ServletRequest;
import javax.servlet.ServletResponse;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Repository;
import org.springframework.web.servlet.View;
import org.springframework.web.servlet.ViewResolver;

import com.ideahut.common2.dto.Response;
import com.ideahut.common2.formatter.JSONFormatter;
import com.ideahut.common2.formatter.XMLFormatter;
import com.ideahut.shared2.repo.DaoHandler;
import com.sprint.sms.api.domain.Access;
import com.sprint.sms.api.domain.User;
import com.sprint.sms.api.service.AccessService;
import com.sprint.sms.api.util.RequestUtil;

@Repository
public class RequestFilter implements Filter {
	
	@Autowired
	private AccessService accessService;
	
	@Autowired
	private ViewResolver viewResolver;
	
	
	public void init(FilterConfig arg0) throws ServletException {
		
	}
	
	public void destroy() {
		
	}

	public void doFilter(ServletRequest arg0, ServletResponse arg1, FilterChain chain) throws IOException, ServletException {
		HttpServletRequest request = (HttpServletRequest)arg0;
		HttpServletResponse response = (HttpServletResponse)arg1;
		
		response.setHeader("Access-Control-Allow-Origin", "*");
		response.setHeader("Access-Control-Allow-Headers", "X-Requested-With, Content-Type, Accept, Origin, Authorization, Access-Key");
		response.setHeader("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS");
		
		if ("OPTIONS".equals(request.getMethod())) {
			response.setStatus(HttpServletResponse.SC_OK);
			return;
		}
		
		String path = request.getPathInfo();
		int idx = path.lastIndexOf(".");
		String ext = "";
		if (idx != -1) {
			ext = path.substring(idx + 1);
			path = path.substring(0, idx);
		}
		Response valid = accessService.validatePath(request, path);
		if (valid != null) {
			response.setStatus(HttpServletResponse.SC_OK);
			renderView(path, request, response, valid, ext);
			return;
		}
		boolean isPublic = accessService.getPublicAccess().containsKey(path);
		if (isPublic) {
			DaoHandler.setAuditOff(true);
		} else {
			String key = RequestUtil.getAccessKey(request);
			key = key != null ? key.trim() : "";
			Access access = accessService.getAccess(key);
			User user = access.getUser();
			String auditor = user.getId() + "::" + user.getName();
			DaoHandler.setAuditor(auditor);
		}
		try {
			chain.doFilter(request, response);
		} catch (Exception e) {
			renderView(path, request, response, Response.ERROR("99", e + ""), ext);			
		}
	}
	
	private void renderView(String path, HttpServletRequest request, HttpServletResponse response, Object data, String ext) throws ServletException, IOException {
		try {
			View view = viewResolver.resolveViewName(path, Locale.getDefault());
			if (view != null) {
				Map<String, Object> model = new HashMap<String, Object>();
				model.put("response", data);
				view.render(model, request, response);
			} else {
				byte[] bytes = new byte[0];
				if ("xml".equalsIgnoreCase(ext)) {
					XMLFormatter xf = new XMLFormatter();
					response.setContentType(xf.getContentType());
					bytes = xf.getResultAsByteArray(data);
				} else {
					JSONFormatter jf = new JSONFormatter();
					response.setContentType(jf.getContentType());
					bytes = jf.getResultAsByteArray(data);
				}
				response.setContentLength(bytes.length);
				response.getOutputStream().write(bytes);
				response.getOutputStream().flush();
			}
		} catch (Exception e) {
			throw new ServletException(e);
		}
	}
	
}
