package com.sprint.sms.api.dao.helper;

import java.util.List;

import org.hibernate.Query;
import org.hibernate.Session;

import com.ideahut.shared2.repo.DaoHandler;
import com.ideahut.shared2.repo.lib.DaoInvoker;
import com.ideahut.shared2.repo.lib.DomainIndex;
import com.sprint.sms.api.domain.Role;
import com.sprint.sms.api.domain.RolePath;

public final class RolePathHelper {

	public static final String FIELD_INVOKER_DELETE_BY_ROLE_AND_GROUP_AND_PATH_LIST		= "INVOKER_DELETE_BY_ROLE_AND_GROUP_AND_PATH_LIST";
	
	public static final String FIELD_INVOKER_DELETE_BY_ROLE_AND_GROUP_LIST				= "INVOKER_DELETE_BY_ROLE_AND_GROUP_LIST";
	
	/*
	 * INVOKER DELETE BY ROLE AND GROUP AND PATH LIST
	 */
	public static final DaoInvoker INVOKER_DELETE_BY_ROLE_AND_GROUP_AND_PATH_LIST = new DaoInvoker() {
		@SuppressWarnings("unchecked")
		public <T> T invoke(DaoHandler daoHandler, DomainIndex domainIndex, Object...args) throws Throwable {
			Role role = (Role)args[0];
			String group = (String)args[1];
			List<String> paths = (List<String>)args[2];
			StringBuilder hql = new StringBuilder()
			.append("DELETE FROM ")
			.append(RolePath.class.getSimpleName())
			.append(" WHERE path LIKE :group ")			
			.append(" AND path NOT IN (:paths)");
			if (role != null) {
				hql.append(" AND role = :role");
			}
			if (!group.endsWith("%")) {
				group = group + "%";
			}
			Query query = daoHandler.getSession().createQuery(hql.toString());
			query.setParameter("group", group);
			query.setParameterList("paths", paths);
			if (role != null) {
				query.setParameter("role", role);
			}
			int result = query.executeUpdate();
			return (T) new Integer(result);
		}		
	};
	
	/*
	 * INVOKER DELETE BY ROLE AND GROUP LIST
	 */
	public static final DaoInvoker INVOKER_DELETE_BY_ROLE_AND_GROUP_LIST = new DaoInvoker() {

		@SuppressWarnings({ "unchecked", "rawtypes" })
		@Override
		public <T> T invoke(DaoHandler daoHandler, DomainIndex domainIndex, Object...args) throws Throwable {
			Role role = (Role)args[0];
			List<String> groups = (List<String>)args[1];
			int size = groups.size();
			Session session = daoHandler.getSession(); 
			StringBuilder hql = new StringBuilder()
			.append("SELECT id FROM ")
			.append(RolePath.class.getSimpleName())
			.append(" WHERE id NOT IN (SELECT id FROM ")
			.append(RolePath.class.getSimpleName())
			.append(" WHERE ");
			for (int i = 0; i < size; i++) {
				hql.append(i == 0 ? "" : " OR ").append("path LIKE ?");
			}
			hql.append(")");
			if (role != null) {
				hql.append(" AND role = ?");
			}
			Query query = session.createQuery(hql.toString());
			for (int i = 0; i < size; i++) {
				query.setParameter(i, groups.get(i) + "%");
			}
			if (role != null) {
				query.setParameter(size, role);
			}
			List ids = query.list();
			int result = 0;
			if (ids != null && !ids.isEmpty()) {
				hql = new StringBuilder()
				.append("DELETE FROM ")
				.append(RolePath.class.getSimpleName())
				.append(" WHERE id IN (:ids)");
				query = session.createQuery(hql.toString());
				query.setParameterList("ids", ids);
				result = query.executeUpdate();
			}
			return (T) new Integer(result);
		}
		
	};
	
}
