package com.sprint.sms.api.dao.helper;

import java.util.ArrayList;
import java.util.Collections;
import java.util.Comparator;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import org.hibernate.Criteria;
import org.hibernate.Session;
import org.hibernate.criterion.DetachedCriteria;
import org.hibernate.criterion.Order;
import org.hibernate.criterion.Projections;
import org.hibernate.criterion.Restrictions;
import org.hibernate.criterion.Subqueries;

import com.ideahut.shared2.hibernate.OrderSpec;
import com.ideahut.shared2.hibernate.OrderSpec.OrderType;
import com.ideahut.shared2.repo.DaoHandler;
import com.ideahut.shared2.repo.DaoManager;
import com.ideahut.shared2.repo.lib.DaoInvoker;
import com.ideahut.shared2.repo.lib.DaoUtil;
import com.ideahut.shared2.repo.lib.DomainIndex;
import com.sprint.sms.api.domain.Menu;
import com.sprint.sms.api.domain.RoleMenu;

public final class MenuHelper {
	
	public static final String FIELD_INVOKER_GET_LIST				= "INVOKER_GET_LIST";
	
	public static final String FIELD_INVOKER_GET_LIST_BY_ROLE_ID	= "INVOKER_GET_LIST_BY_ROLE_ID";
	
	public static final String FIELD_INVOKER_SORT					= "INVOKER_SORT";
	
	public static final String FIELD_INVOKER_MAX_PRIORITY			= "INVOKER_MAX_PRIORITY";
	
	
	
	private static final Comparator<Menu> MENU_PRIORITY = new Comparator<Menu>() {
		@Override
		public int compare(Menu o1, Menu o2) {
			return o1.getPriority().compareTo(o2.getPriority());
		}
	};	
	
	private static final Comparator<Menu> MENU_PARENT = new Comparator<Menu>() {
		@Override
		public int compare(Menu o1, Menu o2) {
			if (o1.getParent() == null) {
		        return (o2.getParent() == null) ? 0 : -1;
		    }
		    if (o2.getParent() == null) {
		        return 1;
		    }
		    return o2.getParent().getPriority() .compareTo(o1.getParent().getPriority());
		}
	};
	
	private static final Comparator<RoleMenu> ROLE_MENU_PARENT = new Comparator<RoleMenu>() {
		@Override
		public int compare(RoleMenu rm1, RoleMenu rm2) {
			Menu o1 = rm1.getMenu(), o2 = rm2.getMenu();
			if (o1.getParent() == null) {
		        return (o2.getParent() == null) ? 0 : -1;
		    }
		    if (o2.getParent() == null) {
		        return 1;
		    }
		    return o2.getParent().getPriority() .compareTo(o1.getParent().getPriority());
		}		
	};

	private MenuHelper() {}
	
	
	/*
	 * INVOKER GET LIST
	 */
	public static final DaoInvoker INVOKER_GET_LIST = new DaoInvoker() {		
		@SuppressWarnings("unchecked")
		@Override
		public <T> T invoke(DaoHandler daoHandler, DomainIndex domainIndex, Object...args) {
			OrderSpec orderSpec = OrderSpec.create("parent", OrderType.Ascending).add("priority", OrderType.Ascending);
			Criteria c = DaoManager.createCriteria(daoHandler.getSession(), daoHandler.getDomainClass(), orderSpec);
			List<Menu> list = c.list();
			if (list == null) {
				return null;
			}
			Collections.sort(list, MENU_PARENT); // Sort parent null ASC (Kasus Oracle parent NULL berada di akhir) 
			Map<Long, Menu> menus = new HashMap<Long, Menu>();
			Map<Long, Map<Long, List<Long>>> struct = new HashMap<Long, Map<Long, List<Long>>>();			
			while (!list.isEmpty()) {
				Menu m = list.remove(0);
				menus.put(m.getId(), m);
				if (m.getParent() == null) {
					struct.put(m.getId(), new HashMap<Long, List<Long>>());
				} else {
					if (m.getParent().getParent() != null) {
						Map<Long, List<Long>> mp = struct.get(m.getParent().getParent().getId());
						if (mp == null) {
							mp = new HashMap<Long, List<Long>>();
							struct.put(m.getParent().getParent().getId(), mp);
						}
						List<Long> ls = mp.get(m.getParent().getId());
						if (ls == null) {
							ls = new ArrayList<Long>();						
						}
						ls.add(m.getId());
						mp.put(m.getParent().getId(), ls);
					} else {
						struct.get(m.getParent().getId()).put(m.getId(), new ArrayList<Long>());
					}				
				}			
			}			
			List<Menu> result = new ArrayList<Menu>();
			for (Long pKey1 : struct.keySet()) {
				Menu pMenu1 = menus.remove(pKey1);
				Map<Long, List<Long>> map = struct.get(pKey1);
				for (Long pKey2 : map.keySet()) {
					Menu pMenu2 = menus.remove(pKey2);
					List<Long> chds = map.get(pKey2);
					while (!chds.isEmpty()) {
						pMenu2.getChildren().add(menus.remove(chds.remove(0)));
					}
					pMenu1.getChildren().add(pMenu2);
				}
				result.add(pMenu1);
			}
			menus.clear();
			struct.clear();			
			Collections.sort(result, MENU_PRIORITY);
			for (Menu m : result) {
				Collections.sort(m.getChildren(), MENU_PRIORITY);
				for (Menu child : m.getChildren()) {
					Collections.sort(child.getChildren(), MENU_PRIORITY);
				}
			}
			return (T)result;
			
		}
	};

	
	/*
	 * INVOKER GET LIST BY ROLE ID
	 */
	public static final DaoInvoker INVOKER_GET_LIST_BY_ROLE_ID = new DaoInvoker() {
		@SuppressWarnings("unchecked")
		@Override
		public <T> T invoke(DaoHandler daoHandler, DomainIndex domainIndex, Object...args) {
			Long roleId = (Long) args[0];
			Boolean active = (Boolean) args[1];
			
			Map<Long, Menu> menus = new HashMap<Long, Menu>();
			Map<Long, Map<Long, List<Long>>> struct = new HashMap<Long, Map<Long, List<Long>>>();
			
			if (roleId != null) {
				DetachedCriteria mdc = DetachedCriteria.forClass(Menu.class);
				if (active != null) {
					mdc.add(Restrictions.eq("active", active));
				}
				mdc.add(Restrictions.not(Restrictions.eq("global", Boolean.TRUE)));
				mdc.setProjection(Projections.property("id"));
				
				Criteria c = DaoUtil.createCriteria(daoHandler.getSession(), RoleMenu.class);
				c.createAlias("menu", "menu");
				c.add(Restrictions.eq("role.id", roleId));
				c.add(Subqueries.propertyIn("menu.id", mdc));
				c.addOrder(Order.asc("menu.parent")).addOrder(Order.asc("menu.priority"));				
				List<RoleMenu> list = c.list();
				if (list != null) {
					Collections.sort(list, ROLE_MENU_PARENT);
					while (!list.isEmpty()) {
						RoleMenu roleMenu = list.remove(0);
						Menu menu = roleMenu.getMenu();
						menu.setAction(roleMenu.getActionAsSet());
						menus.put(menu.getId(), menu);
						if (menu.getParent() == null) {
							struct.put(menu.getId(), new HashMap<Long, List<Long>>());
						} else {
							if (menu.getParent().getParent() != null) {
								Map<Long, List<Long>> menuParent = struct.get(menu.getParent().getParent().getId());
								if (menuParent == null) {
									continue;
								}
								List<Long> lp = menuParent.get(menu.getParent().getId());
								if (lp == null) {
									continue;
								}
								lp.add(menu.getId());
							} else {
								Map<Long, List<Long>> menuParent = struct.get(menu.getParent().getId());
								if (menuParent == null) {
									continue;
								}
								menuParent.put(menu.getId(), new ArrayList<Long>());
							}				
						}			
					}
				}
			}
			
			Criteria criteriaGlobalMenu = DaoUtil.createCriteria(daoHandler.getSession(), Menu.class);
			if (active != null) {
				criteriaGlobalMenu.add(Restrictions.eq("active", active));
			}
			criteriaGlobalMenu.add(Restrictions.eq("global", Boolean.TRUE));
			criteriaGlobalMenu.addOrder(Order.asc("parent")).addOrder(Order.asc("priority"));
			
			List<Menu> listGlobalMenu = criteriaGlobalMenu.list();
			if (listGlobalMenu != null) {
				for (Menu menu : listGlobalMenu) {
					menus.put(menu.getId(), menu);
					if (menu.getParent() == null) {
						Map<Long, List<Long>> parent = struct.get(menu.getId());
						if (parent == null) {
							struct.put(menu.getId(), new HashMap<Long, List<Long>>());							
						}
					} else {
						if (menu.getParent().getParent() != null) {
							Map<Long, List<Long>> menuParent = struct.get(menu.getParent().getParent().getId());
							if (menuParent == null) {
								continue;
							}
							List<Long> lp = menuParent.get(menu.getParent().getId());
							if (lp == null) {
								continue;
							}
							lp.add(menu.getId());
						} else {
							Map<Long, List<Long>> menuParent = struct.get(menu.getParent().getId());
							if (menuParent == null) {
								continue;
							}
							menuParent.put(menu.getId(), new ArrayList<Long>());
						}
					}
					
				}
			}
			
			List<Menu> result = new ArrayList<Menu>();
			for (Long pKey1 : struct.keySet()) {
				Menu pMenu1 = menus.get(pKey1);
				Map<Long, List<Long>> map = struct.get(pKey1);
				for (Long pKey2 : map.keySet()) {
					Menu pMenu2 = menus.get(pKey2);
					//pMenu2.addParentId(pMenu1.getId());				
					List<Long> chds = map.get(pKey2);
					while (!chds.isEmpty()) {
						Menu chd = menus.get(chds.remove(0));
						//chd.addParentId(pMenu2.getId());
						//chd.addParentId(pMenu1.getId());
						pMenu2.getChildren().add(chd);
					}
					pMenu1.getChildren().add(pMenu2);
				}
				result.add(pMenu1);
			}
			menus.clear();
			struct.clear();
			
			Collections.sort(result, MENU_PRIORITY);
			for (Menu m : result) {
				Collections.sort(m.getChildren(), MENU_PRIORITY);
				for (Menu child : m.getChildren()) {
					Collections.sort(child.getChildren(), MENU_PRIORITY);
				}
			}
			return (T)result;
		}
	};
	
	
	/*
	 * INVOKER SORT
	 */
	public static final DaoInvoker INVOKER_SORT = new DaoInvoker() {
		@SuppressWarnings("unchecked")
		@Override
		public <T> T invoke(DaoHandler daoHandler, DomainIndex domainIndex, Object...args) {
			Session session = daoHandler.getSession();
			Long id = (Long) args[0];
			boolean moveUp = Boolean.parseBoolean(args[1] + "");
			Menu m1 = (Menu)session.get(Menu.class, id);
			if (m1 == null) {
				return (T)Boolean.FALSE;
			}
			Criteria c = DaoUtil.createCriteria(session, Menu.class);
			if (m1.getParent() != null) {
				c.add(Restrictions.eq("parent", m1.getParent()));			
			} else {
				c.add(Restrictions.isNull("parent"));
			}
			if (moveUp) {
				c.add(Restrictions.lt("priority", m1.getPriority()));
				c.addOrder(Order.desc("priority"));
			} else {
				c.add(Restrictions.gt("priority", m1.getPriority()));
				c.addOrder(Order.asc("priority"));
			}
			c.setMaxResults(1);
			Menu m2 = (Menu)c.uniqueResult();
			if (m2 == null) {
				return (T)Boolean.FALSE;
			}
			Long priority1 = m1.getPriority();
			Long priority2 = m2.getPriority();
			m1.setPriority(priority2);
			m2.setPriority(priority1);
			session.save(m1);
			session.save(m2);
			return (T)Boolean.TRUE;
		}	
	};
	
	
	/*
	 * INVOKER MAX PRIORITY 
	 */
	public static final DaoInvoker INVOKER_MAX_PRIORITY = new DaoInvoker() {
		@SuppressWarnings("unchecked")
		@Override
		public <T> T invoke(DaoHandler daoHandler, DomainIndex domainIndex, Object...args) {
			Session session = daoHandler.getSession();
			Criteria criteria = DaoUtil.createCriteria(session, Menu.class);
			criteria.setProjection(Projections.max("priority"));
			Long priority = (Long)criteria.uniqueResult();
			if (priority == null) priority = 0l;
			return (T)priority;
		}	
	};
	
}
