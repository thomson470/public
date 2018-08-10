package com.sprint.sms.api.bean.dummy;

import com.ideahut.common2.annotation.IdhFormatter;

@IdhFormatter
public class Dummy3
{
	private String id;
	
	private String name;

	@IdhFormatter
	public String getId() {
		return id;
	}

	public void setId(String id) {
		this.id = id;
	}

	@IdhFormatter
	public String getName() {
		return name;
	}

	public void setName(String name) {
		this.name = name;
	}
	
}