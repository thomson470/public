<?php
namespace App\com\sprint\sms\api\util;

final class AppConstant
{
	
	/*
	 * CACHE
	 */
	const CACHE_ACCESS_ROLE			= "ACCESS_ROLE";	
	const CACHE_ACCESS_ID			= "ACCESS_ID";
	const CACHE_API_LIST			= "API_LIST";	// Untuk menyimpan daftar API yang sudah dibaca dari file
	
	
	/*
	 * PARAMETER
	 */
	const PARAMETER_ID				= "id"; 
	const PARAMETER_ACCESS_KEY		= "p_access";
	const PARAMETER_PAGE_INDEX		= "p_index";
	const PARAMETER_PAGE_SIZE		= "p_size";
	const PARAMETER_ORDER			= "p_order";
	
	/*
	 * SPLIT
	 */
	const SPLIT_ORDER_FIELD			= ",";
	const SPLIT_ORDER_SPEC			= "-";
	const SPLIT_OBJECT_FIELD		= "-";
	
		
	/*
	 * HEADER
	 */
	const HEADER_ACCESS_KEY			= "Access-Key";
	const HEADER_USER_AGENT			= "User-Agent";
	const HEADER_USER_AGENT_SLIM	= "HTTP_USER_AGENT";
	
	
	/*
	 * PAGE
	 */
	const PAGE_DEFAULT_SIZE			= 10;	
	
	
	/*
	 * RESPONSE TYPE
	 */	
	const TYPE_JSON			= 1;
	const TYPE_XML			= 2;
	const TYPE_TEXT			= 3;
	
	
	/*
	 * API KEY
	 */
	const API_KEY_PUBLIC		= "public";
	const API_KEY_PRIVATE		= "private";
	const API_KEY_PATH			= "path";
	const API_KEY_DESCRIPTION	= "description";
	const API_KEY_PARAMETER		= "parameter";
	
	/*
	 * ACCESS
	 */
	const ACCESS_EXPIRED = 900; // 15 Menit
	
}