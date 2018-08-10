package com.sprint.sms.api.bean.dummy;

import com.ideahut.common2.annotation.IdhFormatter;

@IdhFormatter
public class Dummy2
{
	private String id;
	
	private String name;
	
	private Dummy3 dummy3;

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

	@IdhFormatter
	public Dummy3 getDummy3() {
		return dummy3;
	}

	public void setDummy3(Dummy3 dummy3) {
		this.dummy3 = dummy3;
	}
	
}