package com.sprint.sms.api.util;

import java.lang.reflect.Constructor;
import java.lang.reflect.Method;
import java.text.SimpleDateFormat;
import java.util.Arrays;
import java.util.Collection;
import java.util.Date;
import java.util.List;
import java.util.Map;

import com.ideahut.shared2.domain.base.BaseDomain;
import com.ideahut.shared2.domain.base.BaseEntryDomain;
import com.ideahut.shared2.domain.base.BaseVersionDomain;

public final class ObjectUtil {
	
	private static final SimpleDateFormat dateFormat = new SimpleDateFormat("yyyyMMddHHmmss.SSS");
	
	public static final int NOT_NULL			= 1;
	public static final int NOT_EMPTY			= 2;
	//private static final int NOT_NUMBER			= 3;
	
	private static final List<String> defaultIgnoredField = Arrays.asList(
		BaseDomain.ID, 
		BaseVersionDomain.VERSION,
		BaseEntryDomain.ENTRY
	);
	
	/*
	 * GET DEFAULT IGNORED FIELD
	 */
	public static List<String> getDefaultIgnoredField() {
		return defaultIgnoredField;
	}
	
	/*
	 * copy
	 *   - Memindahkan nilai-nilai field dari $src ke $dest
	 *   - $ignore berisi daftar field yang tidak perlu di-copy
	 *   - $rule berisi kondisi dari $src ke $dest, tediri dari: null, empty, dll
	 */
	public static<T> T copy(Class<T> clazz, T dest, T src, Collection<String> ignore, Map<String, List<Integer>> rule) {
		
		try {
			Method[] methods = clazz.getMethods();
			for (Method mtd : methods) {
				String fld = mtd.getName();
				if (!fld.startsWith("set")) {
					continue;
				}
				fld = fld.substring(3);
				Method getMtd = clazz.getMethod("get" + fld);
				Object getVal = getMtd.invoke(src);
				fld = fld.substring(0, 1).toLowerCase() + fld.substring(1);
				if (canCopy(fld, getVal, ignore, rule)) {
					mtd.invoke(dest, getVal);
				}			
			}
		} catch (Exception e) {
			throw new RuntimeException(e);
		}
		return dest;
	}
	
	private static boolean canCopy(String field, Object val, Collection<String> ignore, Map<String, List<Integer>> rule) {
		if (ignore != null && ignore.contains(field)) {
			return false;
		}
		if (rule == null || !rule.containsKey(field)) {
			return true;
		}
		List<Integer> check = rule.get(field);
		int count = check != null ? check.size() : 0;
		boolean result = true;
		for (int i = 0; i < count; i++) {
			if (!result) {
				return result;
			}
			if (check.get(i) == NOT_NULL && val == null) {
				result = false;
			} 
			else if (check.get(i) == NOT_EMPTY) {
				String str = (String)val;
				if (str == null || str.trim().length() == 0) {
					result = false;
				}
			}
		}
		return result;
	}
	
	
	/*
	 * GET OBJECT VALUE FROM STRING
	 */
	public static Object getValueFromString(Class<?> type, String str) throws Exception {
		
		if (String.class.equals(type)) {
			return str;
		} 
		
		else if (Boolean.class.equals(type)) {
			str = str.trim();
			if (str.length() == 0) {
				return null;
			}
			return new Boolean("1".equals(str) || "true".equalsIgnoreCase(str));
		} 
		
		else if (Number.class.isAssignableFrom(type)) {
			str = str.trim();
			if (str.length() == 0) {
				return null;
			}
			Constructor<?> constructor = type.getConstructor(String.class);
			return constructor.newInstance(str);
		}
		
		else if (type.isEnum()) {
			str = str.trim();
			if (str.length() == 0) {
				return null;
			}
			Method mtd = type.getMethod("valueOf", String.class);
			return mtd.invoke(null, str);
		}
		
		else if (type.isPrimitive()) {
			str = str.trim();
			if (str.length() == 0) {
				return null;
			}
			if (boolean.class.equals(type)) {
				return "1".equals(str) || "true".equalsIgnoreCase(str);
			} else {
				String pName = type.getName();
				pName = pName.substring(0, 1).toUpperCase() + pName.substring(1);
				Class<?> pType = Class.forName("java.lang." + (int.class.equals(type) ? "Integer" : pName));
				String parse = "parse" + (int.class.equals(type) ? "Int" : pName);
				Method mtd = pType.getMethod(parse, String.class);
				return mtd.invoke(null, str);
			}			
		}
		
		else if (Date.class.isAssignableFrom(type)) {
			try {
				return new Date(Long.parseLong(str));
			} catch (Exception e1) {
				try {
					return dateFormat.parse(str);
				} catch (Exception e2) { }					
			}
		}
		
		return null;
	}
}
