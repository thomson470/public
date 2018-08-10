package com.sprint.sms.api.bean.dummy;

import com.ideahut.common2.annotation.IdhFormatter;

@IdhFormatter
public class Dummy1
{
	private String id;
	
	private String name;
	
	private Dummy2 dummy2;

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
	public Dummy2 getDummy2() {
		return dummy2;
	}

	public void setDummy2(Dummy2 dummy2) {
		this.dummy2 = dummy2;
	}
	
}