package com.sprint.sms.api.access;

import java.io.Serializable;
import java.util.HashMap;
import java.util.Map;

import com.ideahut.common2.annotation.IdhFormatter;

@IdhFormatter
public class ApiAccess implements Serializable {

	/**
	 * 
	 */
	private static final long serialVersionUID = 4639659282867599284L;

	private String description;
	
	private Map<String, String> parameter = new HashMap<String, String>();

	@IdhFormatter
	public String getDescription() {
		return description;
	}

	public void setDescription(String description) {
		this.description = description;
	}

	@IdhFormatter
	public Map<String, String> getParameter() {
		return parameter;
	}

	public void setParameter(Map<String, String> parameter) {
		this.parameter = parameter;
	}
	
}
