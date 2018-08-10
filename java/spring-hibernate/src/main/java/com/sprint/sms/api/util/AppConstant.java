package com.sprint.sms.api.util;


public final class AppConstant {
	
	private AppConstant() {}

	/*
	 * GENERAL
	 */
	//public static final SimpleDateFormat TRX_DATE_INPUT_FORMAT 		= new SimpleDateFormat("dd/MM/yyyy");
	
	
	/*
	 * TRANSACTION MANAGER ID
	 */
	//public static final String TRXMGR_CORE							= "transactionManager";
	
	
	
	/*
	 * CACHE
	 */
	public static final String CACHE_ACCESS_ID						= "ACCESS_ID";
	public static final String CACHE_ACCESS_ROLE					= "ACCESS_ROLE";
	public static final String CACHE_API_LIST						= "API_LIST";
	
	
	
	/*
	 * PARAMETER
	 */
	public static final String PARAMETER_ID							= "id"; 
	public static final String PARAMETER_ACCESS_KEY					= "p_access";
	public static final String PARAMETER_PAGE_INDEX					= "p_index";
	public static final String PARAMETER_PAGE_SIZE					= "p_size";
	public static final String PARAMETER_ORDER						= "p_order"; // format: p_order=name-asc,id-desc
	
	
	/*
	 * SPLIT
	 */
	public static final String SPLIT_ORDER_FIELD					= ",";
	public static final String SPLIT_ORDER_SPEC						= "-";	
	public static final String SPLIT_OBJECT_FIELD					= "-";
	
		
	/*
	 * HEADER
	 */
	public static final String HEADER_ACCESS_KEY					= "Access-Key";
	public static final String HEADER_USER_AGENT					= "User-Agent";
	public static final String HEADER_USER_AGENT_SLIM				= "HTTP_USER_AGENT";
	
	
	/*
	 * PAGE
	 */
	public static final int PAGE_DEFAULT_SIZE						= 10;
	
	
	/*
	 * TYPE
	 */
	public static final int TYPE_JSON								= 1;
	public static final int TYPE_XML								= 2;
	public static final int TYPE_TEXT								= 3;
	
	
	/*
	 * API KEY
	 */
	public static final String API_KEY_PUBLIC						= "public";
	public static final String API_KEY_PRIVATE						= "private";
	public static final String API_KEY_PATH							= "path";
	public static final String API_KEY_DESCRIPTION					= "description";
	public static final String API_KEY_PARAMETER					= "parameter";
	
	
}
