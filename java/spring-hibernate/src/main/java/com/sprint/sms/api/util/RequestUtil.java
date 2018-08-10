package com.sprint.sms.api.util;

import java.io.ByteArrayOutputStream;
import java.io.InputStream;
import java.lang.reflect.Method;
import java.util.ArrayList;
import java.util.Enumeration;
import java.util.Iterator;
import java.util.List;

import javax.servlet.http.HttpServletRequest;

import org.json.JSONObject;

import com.ideahut.common2.dto.Page;
import com.ideahut.common2.util.StringUtil;
import com.ideahut.shared2.domain.base.BaseEntryDomain;
import com.ideahut.shared2.domain.base.BaseEntryVersionDomain;
import com.ideahut.shared2.hibernate.OrderSpec;
import com.ideahut.shared2.hibernate.OrderSpec.OrderType;

public final class RequestUtil {

	/*
	 * paramsToObject
	 *   - Untuk merubah request parameter menjadi Object.
	 *   - Method object yang akan diisi yang memiliki prefix 'set' -> setXXX()
	 *   - Huruf awal nama parameter diubah jadi huruf besar dan ditambahkan prefix 'set'
	 *   - Contoh: Parameter 'name' maka di class object akan dicari method 'setName'
	 */
	public static<T> T paramsToObject(HttpServletRequest request, Class<T> clazz) {
		T object = tryCreateInstance(clazz);
		if (object == null) {
			return null;
		}
		Enumeration<String> en = request.getParameterNames();
		while (en.hasMoreElements()) {
			String key = en.nextElement();
			if (!key.equals(AppConstant.PARAMETER_ACCESS_KEY) &&
				!key.equals(AppConstant.PARAMETER_PAGE_INDEX) &&
				!key.equals(AppConstant.PARAMETER_PAGE_SIZE) &&
				!key.equals(AppConstant.PARAMETER_ORDER)) {
				object = set(clazz, object, key, request.getParameter(key));
			}
		}
		return object;
	}
	
	/*
	 * paramsToPage
	 *   - Untuk merubah parameter request menjadi Page.	 
	 */
	public static<T> Page<T> paramsToPage(HttpServletRequest request, Class<T> clazz) {
		int index;
		try {
			index = Integer.parseInt(request.getParameter(AppConstant.PARAMETER_PAGE_INDEX).trim());
		} catch (Exception e) {
			index = 1;
		}
		int size;
		try {
			size = Integer.parseInt(request.getParameter(AppConstant.PARAMETER_PAGE_SIZE).trim());
		} catch (Exception e) {
			size = AppConstant.PAGE_DEFAULT_SIZE;
		}
		Page<T> page = Page.create(index, size);		
		return page;
	}
	
	/*
	 * paramsToId
	 *   - Untuk mendapatkan parameter ID.	 
	 */
	public static<ID> ID paramsToId(HttpServletRequest request, Class<ID> type) {
		String id = request.getParameter(AppConstant.PARAMETER_ID);
		return getValue(type, id);
	}
	
	/*
	 * paramsToOrder
	 *	- Untuk membuat OrderBy dari parameter request
	 */
	public static<T> OrderSpec paramsToOrder(HttpServletRequest request, Class<T> clazz) {
		String order = request.getParameter(AppConstant.PARAMETER_ORDER);
		order = order != null ? order.trim() : "";
		if (order.length() != 0) {
			OrderSpec orderSpec = null;
			String[] exp = StringUtil.split(order, AppConstant.SPLIT_ORDER_FIELD, false, true);
			for (String s : exp) {
				String[] spec = StringUtil.split(s, AppConstant.SPLIT_ORDER_SPEC, 2, false, true);
				if (s.length() == 0) {
					continue;
				}
				OrderType orderType = OrderType.Ascending;
				if (spec.length == 2 && "desc".equalsIgnoreCase(spec[1])) {
					orderType = OrderType.Descending;
				}
				if(orderSpec == null) {					
					orderSpec = OrderSpec.create(spec[0], orderType);
				} else {
					orderSpec.add(spec[0], orderType);
				}
			}
			if (orderSpec != null) {
				return orderSpec;
			}
		}
		if (clazz != null) {
			if (BaseEntryDomain.class.isAssignableFrom(clazz)) {
				return OrderSpec.create(BaseEntryDomain.ENTRY, OrderType.Descending);
			}
			else if (BaseEntryVersionDomain.class.isAssignableFrom(clazz)) {
				return OrderSpec.create(BaseEntryVersionDomain.ENTRY, OrderType.Descending);
			}
		}
		return null;		
	}
	
	
	/*
	 * bodyToObject
	 *   - Untuk merubah request body menjadi Object.
	 * TODO: 
	 *   Saat ini masih support JSON, untuk tipe lain cari vendor pakai compose :D
	 */
	@SuppressWarnings("rawtypes")
	public static<T> T bodyToObject(HttpServletRequest request, Class<T> clazz, int type) {
		T object = tryCreateInstance(clazz);
		if (object == null) {
			return null;
		}
		byte[] body = null;
		InputStream is = null;
        try {
        	ByteArrayOutputStream baos = new ByteArrayOutputStream();
        	is = request.getInputStream();
        	int i;
        	while ((i = is.read()) != -1) {
        		baos.write(i);
        	}
        	body = baos.toByteArray();
        	baos.close();        	
        } catch (Exception e) {
        	
        } finally {
        	//try { is.close(); } catch (Exception ex) { }
        } 
        if (body == null) {
        	return object;
        }
		if (type == AppConstant.TYPE_JSON) {
			JSONObject jo = new JSONObject(new String(body));
			Iterator iter = jo.keys();
			while (iter.hasNext()) {
				String key = (String)iter.next();
				if (!key.equals(AppConstant.PARAMETER_ACCESS_KEY) &&
					!key.equals(AppConstant.PARAMETER_PAGE_INDEX) &&
					!key.equals(AppConstant.PARAMETER_PAGE_SIZE) &&
					!key.equals(AppConstant.PARAMETER_ORDER)) {
					object = set(clazz, object, key, jo.get(key) + "");
				}
			}
		}
		return object;
	}
	
	
	/*
	 * getAccessKey
	 *   - Untuk mendapatkan Access Key.	 
	 */
	public static String getAccessKey(HttpServletRequest request) {
		String accessKey = request.getHeader(AppConstant.HEADER_ACCESS_KEY);
		if (accessKey != null) {
			return accessKey;
		}
		accessKey = request.getHeader(AppConstant.HEADER_ACCESS_KEY.toLowerCase());
		if (accessKey != null) {
			return accessKey;
		}
		accessKey = request.getParameter(AppConstant.PARAMETER_ACCESS_KEY);
		return accessKey;
	}
	
	/*
	 * getUserAgent
	 *   - Untuk mendapatkan User Agent.	 
	 */
	public static String getUserAgent(HttpServletRequest request) {
		String userAgent = request.getHeader(AppConstant.HEADER_USER_AGENT_SLIM);
		if (userAgent != null) {
			return userAgent;
		}
		userAgent = request.getHeader(AppConstant.HEADER_USER_AGENT);
		if (userAgent != null) {
			return userAgent;
		}
		userAgent = request.getHeader(AppConstant.HEADER_USER_AGENT.toLowerCase());		
		return userAgent;
	}
	
	
	
	
	
	
	
	
	
	
	private static<T> T set(Class<T> clazz, T object, String key, String value) {
		String[] exp = key.split(AppConstant.SPLIT_OBJECT_FIELD);
		int count = exp.length;
		if (count == 1) {
			if (exp[0].length() == 0) {
				return object;
			}
			String suffix = exp[0].substring(0, 1).toUpperCase() + exp[0].substring(1);
			Method mtdGet = getMethod(clazz, "get" + suffix);
			if (mtdGet == null) {
				return object;
			}
			Method mtdSet = getMethod(clazz, "set" + suffix, mtdGet.getReturnType());
			if (mtdSet == null) {
				return object;
			}
			tryInvokeMethod(object, mtdSet, getValue(mtdGet.getReturnType(), value));			
		} else {
			List<Object[]> tmp = new ArrayList<Object[]>();
			tmp.add(new Object[] {object, null});
			for (int i = 0; i < count - 1; i++) {
				String suffix = exp[i].substring(0, 1).toUpperCase() + exp[i].substring(1);
				Object parentObj = tmp.get(i)[0];
				Class<?> parentCls = parentObj.getClass();
				Method mtdGet = getMethod(parentCls, "get" + suffix);
				if (mtdGet == null) {
					return quickReturn(object, tmp);
				}
				Method mtdSet = getMethod(parentCls, "set" + suffix, mtdGet.getReturnType());
				if (mtdSet == null) {
					return quickReturn(object, tmp);
				}
				Object objTmp = tryInvokeMethod(parentObj, mtdGet);
				if (objTmp == null) {
					objTmp = tryCreateInstance(mtdGet.getReturnType());
					if (objTmp == null) {
						return quickReturn(object, tmp);
					}
				}
				tmp.get(i)[1] = mtdSet;
				tmp.add(new Object[] {objTmp, null});
			}
			String suffix = exp[count - 1].substring(0, 1).toUpperCase() + exp[count - 1].substring(1);
			count = tmp.size();
			Object objTmp = tmp.get(count - 1)[0];
			Class<?> clsTmp = objTmp.getClass();
			Method mtdGet = getMethod(clsTmp, "get" + suffix);
			if (mtdGet == null) {
				return quickReturn(object, tmp);
			}
			Method mtdSet = getMethod(clsTmp, "set" + suffix, mtdGet.getReturnType());
			if (mtdSet == null) {
				return quickReturn(object, tmp);
			}
			tryInvokeMethod(objTmp, mtdSet, getValue(mtdGet.getReturnType(), value));
			for (int i = count - 2; i >= 0; i--) {
				tryInvokeMethod(tmp.get(i)[0], (Method)tmp.get(i)[1], tmp.get(i + 1)[0]);				
			}		
			tmp.clear();
			tmp = null;
		}
		return object;
	}
	
	private static<T> T tryCreateInstance(Class<T> clazz) {
		try {
			return clazz.newInstance();
		} catch (Exception $e) { }
		return null;
	}
	
	private static<T> T quickReturn(T object, List<Object[]> tmp) {
		tmp.clear();
		tmp = null;
		return object;
	}
	
	private static Object tryInvokeMethod(Object object, Method method, Object...args) {
		try {
			return method.invoke(object, args);
		} catch (Exception e) { }
		return null;
	}
	
	private static Method getMethod(Class<?> clazz, String name, Class<?>...parameterTypes) {
		try {
			return clazz.getMethod(name, parameterTypes);
		} catch (Exception e) { }
		return null;
	}
	
	@SuppressWarnings("unchecked")
	private static<T> T getValue(Class<T> type, String value) {
		try {
			return (T)ObjectUtil.getValueFromString(type, value);
		} catch (Exception e) { }
		return null;
	}
	
}
