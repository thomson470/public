if (typeof IDH !== 'object') {
	IDH = {};
}
(function () {
	'use strict';
	
	var I = IDH;	
	
	
	/*
	 * VALIDATION 
	 * 
	 */
	if (typeof(I.isFunction) !== 'function') {
		I.isFunction = function(o) {
			return typeof(o) === 'function';
		};
	}	
	if (!I.isFunction(I.isObject)) {
		I.isObject = function(o) {
			return typeof(o) === 'object';
		};
	}
	if (!I.isFunction(I.isDefined)) {
		I.isDefined = function(o) {
			return typeof(o) !== 'undefined';
		};
	}
	if (!I.isFunction(I.isString)) {
		I.isString = function(o) {
			return typeof(o) === 'string';
		};
	}
	if (!I.isFunction(I.isNumber)) {
		I.isNumber = function(o) {
			return typeof(o) === 'number';
		};
	}
	if (!I.isFunction(I.isArray)) {
		I.isArray = function(o) {
			return Object.prototype.toString.apply(o) === '[object Array]';
		};
	}
	
	
	
	
	/*
	 * JSON FUNCTION
	 * - IDH.json.parse(text, reviver) -> string to json
	 * - IDH.json.stringify(o, replacer, space) -> json to string
	 * - IDH.json.toXml(o, tab) -> json to xml
	 */
	if (!I.isObject(I.json)) {
		var rx_one = /^[\],:{}\s]*$/,
	    rx_two = /\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g,
	    rx_three = /"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,
	    rx_four = /(?:^|:|,)(?:\s*\[)+/g,
	    rx_escapable = /[\\\"\u0000-\u001f\u007f-\u009f\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,
	    rx_dangerous = /[\u0000\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,
	    meta = {'\b' :'\\b','\t' :'\\t','\n':'\\n','\f':'\\f','\r':'\\r','"':'\\"','\\':'\\\\'};
		var _IJ = {};
		_IJ.quote = function(string) {
		    rx_escapable.lastIndex = 0;
		    return rx_escapable.test(string) ? '"' + string.replace(rx_escapable, function (a) {
		        var c = meta[a];
		        return typeof c === 'string' ? c : '\\u' + ('0000' + a.charCodeAt(0).toString(16)).slice(-4);
		    }) + '"' : '"' + string + '"';
		};	
		_IJ.str = function (key, holder, gap, mind, indent, replacer) {
		    var i, k = null, v, length, mind = gap, partial, value = holder[key], rep = replacer;
		    if (value && I.isObject(value) && I.isFunction(value.toJSON)) {
		        value = value.toJSON(key);
		    }
		    if (I.isFunction(rep)) {
		        value = rep.call(holder, key, value);
		    }
		    switch (typeof value) {
			    case 'string':
			        return _IJ.quote(value);		
			    case 'number':
			        return isFinite(value) ? String(value) : 'null';				
			    case 'boolean':
			    case 'null':
			        return String(value);		
			    case 'object':
			        if (!value) {
			            return 'null';
			        }
			        gap += indent;
			        partial = [];		
			        if (I.isArray(value)) {
			            length = value.length;
			            for (i = 0; i < length; i += 1) {
			                partial[i] = _IJ.str(i, value) || 'null';
			            }
			            v = partial.length === 0 ? '[]' : gap ? '[\n' + gap + partial.join(',\n' + gap) + '\n' + mind + ']' : '[' + partial.join(',') + ']';
			            gap = mind;
			            return v;
			        }
			        if (I.isObject(rep)) {
			            length = rep.length;
			            for (i = 0; i < length; i += 1) {
			                if (I.isString(rep[i])) {
			                    k = rep[i];
			                    v = _IJ.str(k, value);
			                    if (v) {
			                        partial.push(_IJ.quote(k) + (gap ? ': ' : ':') + v);
			                    }
			                }
			            }
			        } else {
			            for (k in value) {
			                if (Object.prototype.hasOwnProperty.call(value, k)) {
			                    v = _IJ.str(k, value);
			                    if (v) {
			                        partial.push(_IJ.quote(k) + (gap ? ': ' : ':') + v);
			                    }
			                }
			            }
			        }
			        v = partial.length === 0 ? '{}' : gap ? '{\n' + gap + partial.join(',\n' + gap) + '\n' + mind + '}' : '{' + partial.join(',') + '}';
			        gap = mind;
			        return v;
		    }
		};
		I.json = {
			'parse' : function(text, reviver) { /* string to json */
				var j;
				function walk(holder, key) {
					var v, value = holder[key];
					if (I.isObject(value)) {
						for (var k in value) {
							if (Object.prototype.hasOwnProperty.call(value, k)) {
								v = walk(value, k);
								if (I.isDefined(v)) {
									value[k] = v;
								} else {
									delete value[k];
								}
							}
						}
					}
					return reviver.call(holder, key, value);
				}			
				text = String(text);
				rx_dangerous.lastIndex = 0;
				if (rx_dangerous.test(text)) {
					text = text.replace(rx_dangerous,
						function(a) {
							return '\\u' + ('0000' + a.charCodeAt(0).toString(16)).slice(-4);
						});
				}
				if (rx_one.test(text.replace(rx_two, '@').replace(rx_three, ']').replace(rx_four, ''))) {
					j = eval('(' + text + ')');
					return I.isFunction(reviver) ? walk({'' : j}, '') : j;
				}
				throw new SyntaxError('json.parse');
			},
			'stringify' : function(o, replacer, space) { /* json to string */
				var i, gap = '', indent = ' ', mind = '';
				if (I.isNumber(space)) {
					for (i = 0; i < space; i += 1) {
						indent += ' ';
					}
				} else if (I.isString(space)) {
					indent = space;
				}
				if (replacer && !I.isFunction(replacer) && (!I.isObject(replacer) || I.isNumber(replacer.length))) {
					throw new Error('json2string');
				}
				return _IJ.str('', {'' : o}, gap, mind, indent, replacer);
			},
			'toXml' : function(o, tab) {
				var tox = function(v, name, ind) {
					var x = "";
					if (v instanceof Array) {
						for ( var i = 0, n = v.length; i < n; i++) {
							x += ind + tox(v[i], name, ind + "\t") + "\n";
						}
					} else if (I.isObject(v)) {
						var hasChild = false;
						x += ind + "<" + name;
						for (var m in v) {
							if (m.charAt(0) == "@") {
								x += " " + m.substr(1) + "=\"" + v[m].toString() + "\"";
							} else {
								hasChild = true;
							}
						}
						x += hasChild ? ">" : "/>";
						if (hasChild) {
							for ( var m in v) {
								if (m == "#text") {
									xml += v[m];
								} else if (m == "#cdata") {
									xml += "<![CDATA[" + v[m] + "]]>";
								} else if (m.charAt(0) != "@") {
									xml += tox(v[m], m, ind + "\t");
								}
							}
							x += (xml.charAt(xml.length - 1) == "\n" ? ind : "") + "</" + name + ">";
						}
					} else {
						x += ind + "<" + name + ">" + v.toString() + "</" + name + ">";
					}
					return x;
				};
				var xml = '';
				for (var m in o) {
					xml += tox(o[m], m, "");
				}
				return tab ? xml.replace(/\t/g, tab) : xml.replace(/\t|\n/g, "");
			}		
		};
	}
    
	
	
	
	
	/*
	 * XML FUNCTION
	 * - IDH.xml.parse(text) -> string to xml
	 * - IDH.xml.string() -> xml to string
	 * - IDH.xml.json() -> xml to json
	 */
	if (!I.isObject(I.xml)) {
		I.xml = {
			'parse' : function(text) {
				var dom = null;
				if (window.DOMParser) {
					try {
						dom = (new DOMParser()).parseFromString(text, "text/xml");
					} catch (e) {
						dom = null;
					}
				} else if (window.ActiveXObject) {
					try {
						dom = new ActiveXObject('Microsoft.XMLDOM');
						dom.async = false;
						if (!dom.loadXML(text)) { // parse error ..
							dom = null;
						}
					} catch (e) {
						dom = null;
					}
				}
				return dom;
			},
			'toJson' : function(xml, tab) {
				var X = {};
				X.toObj = function(xml) {
					var o = {};
					if (xml.nodeType == 1) {
						if (xml.attributes.length)
							for ( var i = 0; i < xml.attributes.length; i++) {
								o["@" + xml.attributes[i].nodeName] = (xml.attributes[i].nodeValue || "").toString();
							}
						if (xml.firstChild) {
							var textChild = 0, cdataChild = 0, hasElementChild = false;
							for ( var n = xml.firstChild; n; n = n.nextSibling) {
								if (n.nodeType == 1) {
									hasElementChild = true;
								} else if (n.nodeType == 3 && n.nodeValue.match(/[^ \f\n\r\t\v]/)) {
									textChild++;
								} else if (n.nodeType == 4) {
									cdataChild++;
								}
							}
							if (hasElementChild) {
								if (textChild < 2 && cdataChild < 2) {
									X.removeWhite(xml);
									for ( var n = xml.firstChild; n; n = n.nextSibling) {
										if (n.nodeType == 3) { // text node
											o["#text"] = X.escape(n.nodeValue);
										} else if (n.nodeType == 4) {
											o["#cdata"] = X.escape(n.nodeValue);
										} else if (o[n.nodeName]) {
											if (o[n.nodeName] instanceof Array) {
												o[n.nodeName][o[n.nodeName].length] = X.toObj(n);
											} else {
												o[n.nodeName] = [o[n.nodeName], X.toObj(n) ];
											}
										} else {
											o[n.nodeName] = X.toObj(n);
										}
									}
								} else {  /* mixed content */ 
									if (!xml.attributes.length) {
										o = X.escape(X.innerXml(xml));
									} else {
										o["#text"] = X.escape(X.innerXml(xml));
									}
								}
							} else if (textChild) {  /* pure text */ 
								if (!xml.attributes.length) {
									o = X.escape(X.innerXml(xml));
								} else {
									o["#text"] = X.escape(X.innerXml(xml));
								}
							} else if (cdataChild) {  /* cdata */ 
								if (cdataChild > 1) {
									o = X.escape(X.innerXml(xml));
								} else {
									for ( var n = xml.firstChild; n; n = n.nextSibling) {
										o["#cdata"] = X.escape(n.nodeValue);
									}
								}
							}
						}
						if (!xml.attributes.length && !xml.firstChild) {
							o = null;
						}
					} else if (xml.nodeType == 9) {  /* document.node */ 
						o = X.toObj(xml.documentElement);
					} else {
						throw new Error('unhandled node type: ' + xml.nodeType);
					}
					return o;
				};
				X.toJson = function(o, name, ind) {
					var json = name ? ("\"" + name + "\"") : "";
					if (o instanceof Array) {
						for ( var i = 0, n = o.length; i < n; i++) {
							o[i] = X.toJson(o[i], "", ind + "\t");
						}
						json += (name ? ":[" : "[") + (o.length > 1 ? ("\n" + ind + "\t" + o.join(",\n" + ind + "\t") + "\n" + ind) : o.join("")) + "]";
					} else if (o == null) {
						json += (name && ":") + "null";
					} else if (typeof (o) == "object") {
						var arr = [];
						for (var m in o) {
							arr[arr.length] = X.toJson(o[m], m, ind + "\t");
						}
						json += (name ? ":{" : "{") + (arr.length > 1 ? ("\n" + ind + "\t" + arr.join(",\n" + ind + "\t") + "\n" + ind) : arr.join("")) + "}";
					} else if (typeof (o) == "string") {
						json += (name && ":") + "\"" + o.toString() + "\"";
					} else {
						json += (name && ":") + o.toString();
					}
					return json;
				};
				X.innerXml = function(node) {
					var s = "";
					if ("innerHTML" in node) {
						s = node.innerHTML;
					} else {
						var asXml = function(n) {
							var s = "";
							if (n.nodeType == 1) {
								s += "<" + n.nodeName;
								for ( var i = 0; i < n.attributes.length; i++) {
									s += " " + n.attributes[i].nodeName + "=\"" + (n.attributes[i].nodeValue || "").toString() + "\"";
								}
								if (n.firstChild) {
									s += ">";
									for ( var c = n.firstChild; c; c = c.nextSibling) {
										s += asXml(c);
									}
									s += "</" + n.nodeName + ">";
								} else {
									s += "/>";
								}
							} else if (n.nodeType == 3) {
								s += n.nodeValue;
							} else if (n.nodeType == 4) {
								s += "<![CDATA[" + n.nodeValue + "]]>";
							}
							return s;
						};
						for ( var c = node.firstChild; c; c = c.nextSibling) {
							s += asXml(c);
						}
					}
					return s;
				};
				X.escape = function(txt) {
					return txt.replace(/[\\]/g, "\\\\").replace(/[\"]/g, '\\"').replace(/[\n]/g, '\\n').replace(/[\r]/g, '\\r');
				};
				X.removeWhite = function(e) {
					e.normalize();
					for ( var n = e.firstChild; n;) {
						if (n.nodeType == 3) {  /* text node */ 
							if (!n.nodeValue.match(/[^ \f\n\r\t\v]/)) {
								var nxt = n.nextSibling;
								e.removeChild(n);
								n = nxt;
							} else {
								n = n.nextSibling;
							}
						} else if (n.nodeType == 1) {  /* element node */
							X.removeWhite(n);
							n = n.nextSibling;
						} else {
							n = n.nextSibling;
						}
					}
					return e;
				};
				if (xml.nodeType == 9) {
					xml = xml.documentElement;
				}
				var json = X.toJson(X.toObj(X.removeWhite(xml)), xml.nodeName, "\t");
				return "{\n" + tab + (tab ? json.replace(/\t/g, tab) : json.replace(/\t|\n/g,"")) + "\n}";
			}
		};
	}
	
	
	
	
	
	/*
	 * CACHE FUNCTION
	 * - IDH.cache.get(group, key)
	 * - IDH.cache.put(group, key, value)
	 * - IDH.cache.remove(group, key)
	 * - IDH.cache.clear(group)
	 */ 
	if (!I.isObject(I.cache)) {
		var _ICACHE = {};
		I.cache = {
			'get' : function(group, key) {
				if (!I.isDefined(group)) {
					 return null;
				}
				var g = _ICACHE[group];
				if (!I.isObject(g)) {
					g = {};
					_ICACHE[group] = g;
				}
				if (!I.isDefined(key)) {
					return g;
				}
				return g[key];
			},
			'put' : function(group, key, val) {
				if (!I.isDefined(group) || !I.isDefined(key)) {
					 return false;
				}
				var g = _ICACHE[group];
				if (!I.isObject(g)) {
					g = {};
					_ICACHE[group] = g;
				}
				if (I.isObject(key)) {
					for (var k in key) {
						g[k] = key[k];
					}
				} else {
					g[key] = val;
				}
				return true;
			},
			'remove' : function(group, key) {
				if (!I.isDefined(group) || !I.isDefined(key)) {
					 return false;
				}
				var g = _ICACHE[group];
				if (!I.isObject(g)) {
					g = {};
					_ICACHE[group] = g;
				}
				delete g[key];
				return true;
			},
			'clear' : function(group) {
				if (I.isDefined(group)) {
					delete _ICACHE[group];
				} else {
					_ICACHE = {};
				}
			}
		};
	}
	
	
	/*
	 * DICTIONARY FUNCTION
	 * - IDH.dict.add(v)
	 * - IDH.dict.put(k, l)
	 * - IDH.dict.get(k, defval, args)
	 * - IDH.dict.remove(k)
	 * - IDH.dict.clear()
	 */ 
	if (!I.isObject(I.dict)) {
		var _DICT = {};
		I.dict = {
			'add' : function (v) {
				if (I.isObject(v)) {
					for (var k in v) {
						_DICT[k] = v[k];
					}
				}
			},
			'put' : function (k, l) {
				if (k && l) {
					_DICT[k] = l;
				}
			},
			'get' : function (k, defval, args) {
				var w = _DICT[k];
				if (!I.isDefined(w)) {
					if (I.isString(defval)) {
						w = defval;
					} else {
						w = k;
					}
				}
				if (I.isArray(defval)) { // supaya hanya input dua parameter, tanpa string defval   
					args = defval;
				}
				if (I.isArray(args)) {
					for (var i = 0; i < args.length; i++) {
						w = w.replace('{' + i + '}', args[i]);
					}
				}
				return w;
			},
			'remove' : function (k) {
				delete _DICT[k];
			},
			'clear' : function () {
				_DICT = {};
			}
		};
	}
	
	
	/*
	 * SCRIPT FUNCTION
	 * - IDH.script.include(src)
	 * - IDH.script.load(v)
	 * - IDH.script.done(func)
	 */ 
	if (!I.isObject(I.script)) {
		var _IS = {};
		_IS.include = [];
		_IS.done = null;
		_IS.exist = function(src) {
			var s = document.getElementsByTagName("script");
			for ( var i = 0; i < s.length; i++) {
				if (s[i].getAttribute('src') === src) {
					return true;
				}
			}
			return false;
		};
		_IS.load = function(v) {
			if (!v.list || v.list.length == 0) {
				return;
			}
			var src = v.list.shift();
			var async = v.async || false;
			var done = I.script.done;
			if (_IS.exist(src)) {
				if (I.isFunction(done)) {
					done({"OK" : false, 'src': src,'text' : 'loaded'});
				}
				_IS.load(v);
			} else {
				var s = document.createElement('script');
			    s.type = 'text/javascript';
			    s.src = src;
			    document.body.appendChild(s);
			    if (async == false) {
			    	s.onload = function() {
			    		if (I.isFunction(done)) {
			    			done({"OK" : true, 'src': src,'text' : 'success'});
						}
			    		_IS.load(v);
			    	};
			    } else {
			    	if (I.isFunction(done)) {
			    		done({"isOK" : true, 'src': src,'text' : 'success'});
					}
			    	_IS.load(v);
			    }
			}
		};		
		I.script = {
			'include' : function(src) {
				if (I.isArray(src)) {
					for (var i = 0; i < src.length; i++) {
						if (I.isString(src[i])) {
							_IS.include[_IS.include.length] = src[i];
						}
					}
				} else if (I.isString(src)) {
					_IS.include[_IS.include.length] = src;
				}
			},
			'load' : function(v) {
				v = v || {};
				v['list'] = _IS.include.slice();
				_IS.load(v);
			},
			'done' : function(f) {
				if (I.isFunction(f)){
					_IS.done = f;
				} else {
					return _IS.done;
				}
			}
		};
	}
	
	
	
	/*
	 * PAD FUNCTION
	 * - IDH.pad.left(txt, length, char)
	 * - IDH.pad.right(txt, length, char)
	 */ 
	if (!I.isObject(I.pad)) {
		I.pad = {
			'left' : function(t, l, c) {
				var s = t;
		    	if(s.length > len) {
		    		s = s.substring(s.length - l, s.length);
		    	} else {
		    		while (s.length < l) {
		    	        s = c + s;
		    	    }
		    	}
		    	return s;
			},
			'right' : function(t, l, c) {
				var s = t;
		    	if(s.length > l) {
		    		s = s.substring(0, l);
		    	} else {
		    		while(s.length < l) {
		    	        s = s + c;
		    	    }
		    	}
		    	return s;
			}
		};
	}
	
	
	
	/*
	 * URL FUNCTION
	 * - IDH.url.query()
	 * - IDH.url.param.map()
	 * - IDH.url.param.enc()
	 * - IDH.url.param.dec()
	 */ 
	if (!I.isObject(I.url)) {
		I.url = {
			'query' : function(u) {
				var s = '';
				if (I.isDefined(u)) {
					var i = u.indexOf('?');
					if (i !== -1) {
						s = u.substring(i + 1);
					} else {
						s = u;
					}
				} else {
					s = window.location.search;
					s = s.substring(1);
				}
				s = s.trim();
		    	return s;
			},
			'param' : {
				'ENCRYPT': '_enc',
				'map' : function(u) {
					var s = I.url.query(u), p = {};
			    	if(s === ''){
			    		return p;
			    	}
			    	var n = s.split('&'),j,x;
			    	for (var i = 0; i < n.length; i++){
			    		x = n[i];
			    		j = x.indexOf('=');
			    		if (j !== -1) {
			    			p[x.substring(0, j)] = x.substring(j + 1);
			    		}
			    	}
			    	return p;
				},
				'enc' : function(u) {
					var m, id = (new Date()).getTime();
					if (I.isObject(u)) {
						m = u;
					} else {
						m = I.url.param.map(u);
					}
					var s = '', f = 1;
					for (var k in m) {
						s += (f ? '' : '&') + k + '=' + m[k];
						f = 0;
					}
					s = id + '$' + btoa(id + s);
					return s;
				},
				'dec' : function(u) {
					var m = I.url.param.map(u), s = m[I.url.param['ENCRYPT']];
					if (!I.isDefined(s)) {
						if (I.isString(u)) {
							s = u;
						} else {
							s = '';
						}
						s = u || '';
					}
					if (s.trim().length == 0) {
						return {};
					}
					var k = '', i = s.indexOf('$');
					if (i !== -1) {
						k = s.substring(0, i);
						s = s.substring(i + 1);
					}
					if (s && s.length == 0) {
						return {};
					}
					s = atob(s);
					if (!s.startsWith(k)) {
						return {};
					}
					s = s.substring(k.length);
					return I.url.param.map(s);
				}
			}
		};
	}
	
	
	
	/*
	 * FORMAT FUNCTION
	 * - IDH.format.date(data)
	 * - IDH.format.epoch(long)
	 * 
	 */ 
	if (!I.isObject(I.format)) {
		var _FMT = {
			'LZ' : function(x) { 
				return (x < 0 || x > 9 ? "" : "0" ) + x;
			},
			'MONTH_NAMES' : ['January','February','March','April','May','June','July','August','September','October','November','December','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
			'DAY_NAMES' : ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sun','Mon','Tue','Wed','Thu','Fri','Sat']
		};
		I.format = {
			'MONTH_NAMES' : function(v) {
				if (I.isArray(v) && v.length == 24) {
					_FMT.MONTH_NAMES = v;
				}
			},
			'DAY_NAMES' : function(v) {
				if (I.isArray(v) && v.length == 14) {
					_FMT.DAY_NAMES = v;
				}
			},
			'date' : function(date, format){
		    	var f = format || 'EE, dd MMM yyyy  HH:mm:ss', dt = date, res = '', i_f = 0, c = '', token = '';
		    	var y = dt.getYear() + '', M = dt.getMonth() + 1, d = dt.getDate(), E = dt.getDay(), H = dt.getHours(), m = dt.getMinutes(), s = dt.getSeconds();
		    	var v = new Object();
		    	if(y.length < 4){
		    		y = '' + (y - 0 + 1900);
		    	}
		    	v['y'] = '' + y;
		    	v['yyyy'] = y;
		    	v['yy'] = y.substring(2,4);
		    	v['M'] = M;
		    	v['MM'] = _FMT.LZ(M);
		    	v['MMM'] = _FMT.MONTH_NAMES[M-1];
		    	v['NNN'] = _FMT.MONTH_NAMES[M+11];
		    	v['d'] = d;
		    	v['dd'] = _FMT.LZ(d);
		    	v['E'] = _FMT.DAY_NAMES[E+7];
		    	v['EE'] = _FMT.DAY_NAMES[E];
		    	v['H'] = H;
		    	v['HH'] = _FMT.LZ(H);
		    	if (H == 0) {
		    		v['h'] = 12;
		    	} else if (H > 12) {
		    		v['h'] = H - 12;
		    	}else{
		    		v['h'] = H;
		    	}
		    	v['hh'] = _FMT.LZ(v['h']);
		    	if(H > 11) {
		    		v['K'] = H - 12;
		    	}else{
		    		v['K'] = H;
		    	}
		    	v['k'] = H + 1;
		    	v['KK'] = _FMT.LZ(v['K']);
		    	v['kk'] = _FMT.LZ(v['k']);
		    	if(H > 11){
		    		v['a'] = 'PM';
		    	}else{
		    		v['a'] = 'AM';
		    	}
		    	v['m'] = m;
		    	v['mm'] = _FMT.LZ(m);
		    	v['s'] = s;
		    	v['ss'] = _FMT.LZ(s);
		    	while (i_f < f.length) {
		    		c = f.charAt(i_f);
		    		token = '';
		    		while ((f.charAt(i_f) == c) && (i_f < f.length)){
		    			token += f.charAt(i_f++);
		    		}
		    		if (v[token] != null) {
		    			res = res + v[token];
		    		} else {
		    			res = res + token;
		    		}
		    	}
		    	return res;
		    },
		    'epoch' : function(e, format) {
		    	var f = format || 'EE, dd MMM yyyy HH:mm:ss';
		    	var m = parseInt(e);
		    	if (m < 10000000000) {
		    		m *= 1000;
		    	}
		    	var d = new Date();
		    	d.setTime(m);
		    	return I.format.date(d, f);
		    },
		    'numberToMoney' : function(value, decimal, prefix) {
		    	var v = value, p = prefix || '';
	        	v = I.format.roundDecimal(v, decimal) + '';
				if (!I.isDefined(v)) {
					return '';
				};
	        	var rgx = /(\d+)(\d{3})/ , x, x1, x2;
	        	x = v.split('.');
	        	x1 = x[0];
	        	x2 = ',';
	        	if (!x[1]) {
	        	  x2 += '00';
	        	} else {
	        	  x2 += x[1].length == 1 ? x[1] + '0' : x[1];
	        	}
	        	while(rgx.test(x1)){
	        	  x1=x1.replace(rgx,'$1'+'.'+'$2');
	        	}
	        	return p + x1 + x2;
	        	//return p+x1;
		    },
		    'roundDecimal' : function(value, decimal) {
		    	var d = decimal || 2, v=value;
	        	if (!v) {
	        		return v;
	        	}
	        	return Math.round(Math.round(v * Math.pow(10,d+1)) / Math.pow(10,1)) / Math.pow(10,d); 
		    }
		};
	}
	
	
	/*
	 * DIGEST FUNCTION
	 * - IDH.digest.md5(str)
	 * 
	 */ 
	if (!I.isObject(I.digest)) {
		var _DIG = {};
		_DIG.md5cycle = function(x,k) {
			var a = x[0], b = x[1], c = x[2], d = x[3];

			a = _DIG.ff(a, b, c, d, k[0], 7, -680876936);
			d = _DIG.ff(d, a, b, c, k[1], 12, -389564586);
			c = _DIG.ff(c, d, a, b, k[2], 17, 606105819);
			b = _DIG.ff(b, c, d, a, k[3], 22, -1044525330);
			a = _DIG.ff(a, b, c, d, k[4], 7, -176418897);
			d = _DIG.ff(d, a, b, c, k[5], 12, 1200080426);
			c = _DIG.ff(c, d, a, b, k[6], 17, -1473231341);
			b = _DIG.ff(b, c, d, a, k[7], 22, -45705983);
			a = _DIG.ff(a, b, c, d, k[8], 7, 1770035416);
			d = _DIG.ff(d, a, b, c, k[9], 12, -1958414417);
			c = _DIG.ff(c, d, a, b, k[10], 17, -42063);
			b = _DIG.ff(b, c, d, a, k[11], 22, -1990404162);
			a = _DIG.ff(a, b, c, d, k[12], 7, 1804603682);
			d = _DIG.ff(d, a, b, c, k[13], 12, -40341101);
			c = _DIG.ff(c, d, a, b, k[14], 17, -1502002290);
			b = _DIG.ff(b, c, d, a, k[15], 22, 1236535329);

			a = _DIG.gg(a, b, c, d, k[1], 5, -165796510);
			d = _DIG.gg(d, a, b, c, k[6], 9, -1069501632);
			c = _DIG.gg(c, d, a, b, k[11], 14, 643717713);
			b = _DIG.gg(b, c, d, a, k[0], 20, -373897302);
			a = _DIG.gg(a, b, c, d, k[5], 5, -701558691);
			d = _DIG.gg(d, a, b, c, k[10], 9, 38016083);
			c = _DIG.gg(c, d, a, b, k[15], 14, -660478335);
			b = _DIG.gg(b, c, d, a, k[4], 20, -405537848);
			a = _DIG.gg(a, b, c, d, k[9], 5, 568446438);
			d = _DIG.gg(d, a, b, c, k[14], 9, -1019803690);
			c = _DIG.gg(c, d, a, b, k[3], 14, -187363961);
			b = _DIG.gg(b, c, d, a, k[8], 20, 1163531501);
			a = _DIG.gg(a, b, c, d, k[13], 5, -1444681467);
			d = _DIG.gg(d, a, b, c, k[2], 9, -51403784);
			c = _DIG.gg(c, d, a, b, k[7], 14, 1735328473);
			b = _DIG.gg(b, c, d, a, k[12], 20, -1926607734);

			a = _DIG.hh(a, b, c, d, k[5], 4, -378558);
			d = _DIG.hh(d, a, b, c, k[8], 11, -2022574463);
			c = _DIG.hh(c, d, a, b, k[11], 16, 1839030562);
			b = _DIG.hh(b, c, d, a, k[14], 23, -35309556);
			a = _DIG.hh(a, b, c, d, k[1], 4, -1530992060);
			d = _DIG.hh(d, a, b, c, k[4], 11, 1272893353);
			c = _DIG.hh(c, d, a, b, k[7], 16, -155497632);
			b = _DIG.hh(b, c, d, a, k[10], 23, -1094730640);
			a = _DIG.hh(a, b, c, d, k[13], 4, 681279174);
			d = _DIG.hh(d, a, b, c, k[0], 11, -358537222);
			c = _DIG.hh(c, d, a, b, k[3], 16, -722521979);
			b = _DIG.hh(b, c, d, a, k[6], 23, 76029189);
			a = _DIG.hh(a, b, c, d, k[9], 4, -640364487);
			d = _DIG.hh(d, a, b, c, k[12], 11, -421815835);
			c = _DIG.hh(c, d, a, b, k[15], 16, 530742520);
			b = _DIG.hh(b, c, d, a, k[2], 23, -995338651);

			a = _DIG.ii(a, b, c, d, k[0], 6, -198630844);
			d = _DIG.ii(d, a, b, c, k[7], 10, 1126891415);
			c = _DIG.ii(c, d, a, b, k[14], 15, -1416354905);
			b = _DIG.ii(b, c, d, a, k[5], 21, -57434055);
			a = _DIG.ii(a, b, c, d, k[12], 6, 1700485571);
			d = _DIG.ii(d, a, b, c, k[3], 10, -1894986606);
			c = _DIG.ii(c, d, a, b, k[10], 15, -1051523);
			b = _DIG.ii(b, c, d, a, k[1], 21, -2054922799);
			a = _DIG.ii(a, b, c, d, k[8], 6, 1873313359);
			d = _DIG.ii(d, a, b, c, k[15], 10, -30611744);
			c = _DIG.ii(c, d, a, b, k[6], 15, -1560198380);
			b = _DIG.ii(b, c, d, a, k[13], 21, 1309151649);
			a = _DIG.ii(a, b, c, d, k[4], 6, -145523070);
			d = _DIG.ii(d, a, b, c, k[11], 10, -1120210379);
			c = _DIG.ii(c, d, a, b, k[2], 15, 718787259);
			b = _DIG.ii(b, c, d, a, k[9], 21, -343485551);

			x[0] = _DIG.add32(a, x[0]);
			x[1] = _DIG.add32(b, x[1]);
			x[2] = _DIG.add32(c, x[2]);
			x[3] = _DIG.add32(d, x[3]);
		};
		_DIG.cmn = function(q, a, b, x, s, t) {
			a = _DIG.add32(_DIG.add32(a, q), _DIG.add32(x, t));
			return _DIG.add32((a << s) | (a >>> (32 - s)), b);
		};
		_DIG.ff = function(a, b, c, d, x, s, t) {
			return _DIG.cmn((b & c) | ((~b) & d), a, b, x, s, t);
		};
		_DIG.gg = function(a, b, c, d, x, s, t) {
			return _DIG.cmn((b & d) | (c & (~d)), a, b, x, s, t);
		};
		_DIG.hh = function(a, b, c, d, x, s, t) {
			return _DIG.cmn(b ^ c ^ d, a, b, x, s, t);
		};
		_DIG.ii = function(a, b, c, d, x, s, t) {
			return _DIG.cmn(c ^ (b | (~d)), a, b, x, s, t);
		};
		_DIG.md51 = function(s) {
			var n = s.length, state = [ 1732584193, -271733879, -1732584194, 271733878 ], i;
			for (i = 64; i <= s.length; i += 64) {
				_DIG.md5cycle(state, _DIG.md5blk(s.substring(i - 64, i)));
			}
			s = s.substring(i - 64);
			var tail = [ 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 ];
			for (i = 0; i < s.length; i++) {
				tail[i >> 2] |= s.charCodeAt(i) << ((i % 4) << 3);
			}
			tail[i >> 2] |= 0x80 << ((i % 4) << 3);
			if (i > 55) {
				_DIG.md5cycle(state, tail);
				for (i = 0; i < 16; i++) {
					tail[i] = 0;
				}
			}
			tail[14] = n * 8;
			_DIG.md5cycle(state, tail);
			return state;
		};
		_DIG.md5blk = function(s) {
			var md5blks = [], i;
			for (i = 0; i < 64; i += 4) {
				md5blks[i >> 2] = s.charCodeAt(i) + (s.charCodeAt(i + 1) << 8) + (s.charCodeAt(i + 2) << 16) + (s.charCodeAt(i + 3) << 24);
			}
			return md5blks;
		};
		_DIG.hex_chr = '0123456789abcdef'.split('');
		_DIG.rhex = function(n) {
			var s = '', j = 0;
			for (; j < 4; j++) {
				s += _DIG.hex_chr[(n >> (j * 8 + 4)) & 0x0F] + _DIG.hex_chr[(n >> (j * 8)) & 0x0F];
			}
			return s;
		};
		_DIG.hex = function(x) {
			for ( var i = 0; i < x.length; i++) {
				x[i] = _DIG.rhex(x[i]);
			}
			return x.join('');
		};
		_DIG.add32 = function(a, b) {
			return (a + b) & 0xFFFFFFFF;
		};
		
		I.digest = {
			'md5' : function(s) {
				return _DIG.hex(_DIG.md51(s));
			}
		};
			
		if (I.digest.md5('hello') != '5d41402abc4b2a76b9719d911017c592') {
			_DIG.add32 = function(x, y) {
				var lsw = (x & 0xFFFF) + (y & 0xFFFF), msw = (x >> 16) + (y >> 16) + (lsw >> 16);
				return (msw << 16) | (lsw & 0xFFFF);
			};
		}
	}
}());