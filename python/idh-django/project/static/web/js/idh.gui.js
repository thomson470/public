(function($){
	
	$.idh = {};
	
	var I = IDH, _ACT = [], $i = $.idh;
	
	$i.sidebar = {
		'check' : function() {
			var wstore = window.sessionStorage;
			if (wstore) {
				$('body').removeClass('sidebar-collapse');
				if (wstore.sidebarCollapse == 1) {
					$('body').addClass('sidebar-collapse');
				}
				var box = '#mSearchBox';
				if ('1' === wstore.searchHide) {
					$(box).removeClass('collapsed-box');
					$(box + ' .btn-box-tool i').attr('class', 'fa fa-plus');
					$(box + ' .box-body').hide();
					$(box + ' .box-footer').hide();					
				} else {
					$(box).addClass('collapsed-box');
					$(box + ' .btn-box-tool i').attr('class', 'fa fa-minus');
					$(box + ' .box-body').show();
					$(box + ' .box-footer').show();					
				}
				$(box + ' .btn-box-tool').click();
				$(box + ' .btn-box-tool').click(function() {
					var b = $(this).find('i').attr('class').indexOf('fa-minus') != -1;
					if (b) {
						wstore.searchHide = 1;
						$(box).removeClass('collapsed-box');
						$(this).find('i').attr('class', 'fa fa-plus');
						$(box + ' .box-body').hide();
						$(box + ' .box-footer').hide();
					} else {
						wstore.searchHide = 0;
						$(box).addClass('collapsed-box');
						$(this).find('i').attr('class', 'fa fa-minus');
						$(box + ' .box-body').show();
						$(box + ' .box-footer').show();
					}
				});				
			}
		},
		'click' : function() {
			var wstore = window.sessionStorage;
			var cls = $('body').attr('class');
			var b = cls.indexOf('sidebar-collapse') != -1;
			if (wstore) {
				wstore.sidebarCollapse = b ? 0 : 1;
			}
		}
	};
	
	$i.action = {
		'val' : function (v) {
			if (I.isDefined(v)) {
				_ACT = v;
			} else {
				return _ACT;
			}
		},			
		'can' : function(v) {
			return _ACT.indexOf(v) != -1;
		}	
	};
	
	
	$i.dialog = {
		'page' : {
			'id' : '',
			'show' : function(p) {
				var m = p.id || '#mAlertDialog';
				$i.dialog.page.id = m;
				$(m + ' #mHeader').attr('class', 'modal-header' + (p.header ? ' ' + p.header : ''));
				$(m + ' #mBtnClose').attr('class', 'btn' + (p.button ? ' ' + p.button : ''));
				$(m + ' #mBtnClose').show();
				$(m + ' .modal-title').html(p.title || '');
				$(m + ' .modal-body').html(p.content || '');
				$(m).modal();
			},
			'hide' : function(f) {
				if(I.isFunction(f)) {
					f();
				}
				$($i.dialog.page.id).modal('hide');
			}
		},	
		'show' : function(title, content, clsHeader, clsButton) {
			var m = '#mAlertDialog';
			$(m + ' #mHeader').attr('class', 'modal-header' + (clsHeader ? ' ' + clsHeader : ''));
			$(m + ' #mBtnOk').attr('class', 'btn' + (clsButton ? ' ' + clsButton : ''));
			$(m + ' #mBtnOk').show();
			$(m + ' .modal-title').html(title);
			$(m + ' .modal-body').html(content);
			$(m).modal();
		},
		'hide' : function() {
			var m = '#mAlertDialog';
			$(m).modal('hide');
		},
		'info' : function(content) {
			$i.dialog.show('<i class="fa fa-info-circle"></i>&nbsp;' + I.dict.get('TITLE_INFO', 'INFO'), content, 'modal-header-info', 'btn-primary');
		},
		'error' : function(content) {
			$i.dialog.show('<i class="fa fa-warning"></i>&nbsp;' + I.dict.get('TITLE_ERROR', 'ERROR'), content, 'modal-header-error', 'btn-danger');
		},
		'confirm' : function(content, onYes) {
			var m = '#mConfirmDialog';
			$(m + ' .modal-title').html('<i class="fa fa-question-circle"></i>&nbsp;' + I.dict.get('TITLE_CONFIRM', 'CONFIRM'));
			$(m + ' .modal-body').html(content);
			$(m).modal();
			$(m + ' .mBtnYes').unbind('click');
			$(m + ' .mBtnYes').click(function() {
				$(m).modal('hide');
				if (typeof (onYes) == 'function') {
					onYes();
				}
			});
		},
		'loader' : function(visible) {
			var v = visible || true, m = '#mLoaderDialog';
			if (v) {
				$(m).modal({
					backdrop:'static',
					keyboard:false
				});
			} else {
				$(m).modal('hide');
			}
		},
		'isError' : function(o) {
			if(o.error) {
				var s = '', e = o.error;
				for (var i = 0; i < e.length; i++) {
					s += (i != 0 ? '<br/>' : '') + e[i].message;
				}
				$i.dialog.error(s);
				return true;
			}
			return false;
		}
	};
	
	$i.crud = {		
		'ajaxParam' : '_ajx',
		'ajaxValue' : function() { return $i.crud.ajaxParam + '=' + (new Date()).getTime(); },
		'remove' : function (p) {
			if (!$i.action.can('DELETE')) {
				$i.dialog.error(I.dict.get('NOT_ALLOWED', 'Not allowed'));
				return;
			}
			var id = p.id || '';
			$i.dialog.confirm(I.dict.get('ASK_CONTINUE', 'Continue ?'), function() {
				if(I.isFunction(p.start)) {
					p.start();
				}
				$.ajax({
					url: p.url + '/' + id + '?' + $i.crud.ajaxValue(),
					type: p.type || 'POST',
					success: function (o) {
						if(I.isFunction(p.done)) {
							p.done();
						}
						if ($i.dialog.isError(o)) {
							return;
						}
						if(I.isFunction(p.list)) {
							p.list();
						} else {
							$i.dialog.info(I.dict.get('SUCCESS_DELETE'));
						}		
					},
					error: function (o) {
						if(I.isFunction(p.done)) {
							p.done();
						}
						$i.dialog.error(I.dict.get('ERROR_DELETE'));
					}
				});	
			});
		},
		'showBox': function(p) {
			$(p.hide).hide();
			if (p.style) {
				$(p.show).show(p.style);
			} else {
				$(p.show).show();
			}
		},
		'reset': function(p) {
			if ($(p.form)[0]) {
				$(p.form).find('input[type="text"], select').each(function() {
					$(this).val('');			
				});
			}
			$(p.table + ' tbody').remove();
			if (I.isFunction(p.clear)) {
				p.clear();
			}
		},
		'index': function(p) {
			$(p.target).val(p.index);
			if(I.isFunction(p.list)) {
				p.list();
			}
		},
		'save': function(p) {
			if (!$i.action.can('SAVE')) {
				$i.dialog.error(I.dict.get('NOT_ALLOWED', 'Not allowed'));
				return;
			}
			var frm = p.form, id = $(frm + ' #mId').val(), isedit = I.isDefined(id) && $.trim(id) != '';
			if(I.isFunction(p.start)) {
				p.start();
			}
			$.ajax({
				url: $(frm).attr('action') + '/' + (isedit ? 'update' : 'create') + (isedit ? '/' + id : ''),
				data: $i.crud.ajaxValue() + '&' + $(frm).serialize(),
				type: $(frm).attr('method'),
				processData: false,
				success: function (o) {
					if(I.isFunction(p.done)) {
						p.done();
					}
					if ($i.dialog.isError(o)) {
						return;
					}
					if (p.showBox && p.hideBox) {
						$i.crud.showBox({'hide':p.hideBox,'show':p.showBox});
					}
					if(typeof(p.list) == 'function') {
						p.list();
					} else {
						$i.dialog.info(I.dict.get('SUCCESS_SAVE', 'SUCCESS'));
					}			
				},
				error: function (o) {
					if(I.isFunction(p.done)) {
						p.done();
					}
					$i.dialog.error(I.dict.get('ERROR_SAVE', 'ERROR'));
				}
			});
		},
		'edit': function(p) {
			var frm = p.form;
			if ($(frm)[0]) {
				$(frm).find('input, textarea, select').each(function() {
					$(this).val('');			
				});
			}
			$(frm + ' #mId').remove();
			var id = p.id;
			if (id) {
				$(frm).append('<input type="hidden" id="mId" value="" />');
				$(frm + ' #mId').val(id);
			}
			
			if (p.data && typeof(p.populate) == 'function') {
				if (p.refresh && id) {
					if(I.isFunction(p.start)) {
						p.start();
					}
					$.ajax({
						url: $(frm).attr('refresh-url') + '/' + id,
						success: function (o) {
							if(I.isFunction(p.done)) {
								p.done();
							}
							if ($i.dialog.isError(o)) {
								return;
							}
							p.populate(o.data, frm);			
						},
						error: function (o) {
							if(I.isFunction(p.done)) {
								p.done();
							}
							$i.dialog.error(I.dict.get('ERROR_EDIT', 'ERROR'));
						}
					});
				} else {
					p.populate(p.data, frm);
				}
			}
			if (p.showBox && p.hideBox) {
				$i.crud.showBox({'hide':p.hideBox,'show':p.showBox});
			}
			if (p.showBox){
				if (p.title) {
					$(p.showBox + ' .box-title').text(p.title);
				} else {
					$(p.showBox + ' .box-title').text(id ? I.dict.get('EDIT', 'EDIT') + ' - ' + id : I.dict.get('NEW', 'NEW'));
				}
			}
		},
		'list': function(p) {
			if(I.isFunction(p.start)) {
				p.start();
			}
			try {
				var pidx = eval($.trim($(p.index).val()));
				var prow = eval($.trim($(p.rows).val()));
				var ptot = eval($(p.total).text());
				if (pidx < 1) {
					pidx = 1;
				}
				if (pidx > ptot) {
					pidx = ptot;
				}
				var frm = p.form;				
				$.ajax({
					url: $(frm).attr('action') + '/' + pidx + '/' + prow,
					data: $i.crud.ajaxValue() + '&' + $(frm).serialize(),
					type: $(frm).attr('method'),
					success: function (o) {
						if(I.isFunction(p.done)) {
							p.done();
						}
						if ($i.dialog.isError(o)) {
							return;
						}
						o = o.data;
						$(p.index).val(o.index);
						$(p.total).text(o.total > 0 ? o.total : 1);
						$(p.table + ' tbody').remove();
						$(p.table).append('<tbody></tbody>');
						o = o.data;
						if (I.isFunction(p.populate)) {
							p.populate(o);
						}
					},
					error: function (o) {
						if(I.isFunction(p.done)) {
							p.done();
						}
						$i.dialog.error(o.status + ' : ' + o.statusText);
					}
				});
			} catch (e) {
				if(I.isFunction(p.done)) {
					p.done();
				}
				$i.dialog.error(e + '');
			}
		},
		
		// Default / Standart CRUD page
		//'test' : function() {
		//	return '<h1>Hello World!</h1><p>Have a nice day!</p>';
		//},
		'doIndex' : function(i) {
			$i.crud.index({target: '#mPageIndex', index: i, list: function(){list();}});			
		},
		'doAdd' : function() {
			$i.crud.edit({'form':'#mEditBox form','hideBox':'.mMainBox','showBox':'#mEditBox'});
		},
		'doReset' : function() {
			$i.crud.reset({'form':'#mSearchBox form', 'table':'#mTable'});
		},
		'doBack' : function() {
			$.idh.crud.showBox({'hide':'.mMainBox','show':'#mRetrieveBox'});
		},
		'doSave' : function() {
			$i.crud.save({
				'form':'#mEditBox form',
				'showBox':'#mRetrieveBox',
				'hideBox':'.mMainBox', 
				'start': function(){$('#mEditBox .overlay').show();}, 
				'done': function(){$('#mEditBox .overlay').hide();},
				'list':function(){list();}
			});
		},
		'doList' : function (pop, colspan) {
			var form = '#mRetrieveBox #mSearchBox form', table = '#mRetrieveBox #mTableBox #mTable';
			$i.crud.list({
				index: '#mRetrieveBox #mPageIndex',
				rows: '#mRetrieveBox #mPageRows',
				total: '#mRetrieveBox #mPageTotal',
				form: form,
				table: table,
				start: function() {
					$('#mRetrieveBox .overlay').show();
				},
				done: function() {
					$('#mRetrieveBox .overlay').hide();
				},
				populate: function(o) {
					if (o.length != 0) {							
						if (IDH.isFunction(pop)) {
							pop(o);
						}
					} else {
						$(table + ' tbody').append('<tr><td align="center" colspan="' + colspan + '">' + IDH.dict.get('NO_DATA') + '</td></tr>');
					}
				}
			});
		}
	};
	
	
	$i.ajax = function(v) {
		var url = v.url || '', suc = v.success, err = v.error, done = v.done, enc = v.enctype, mtd = v.method || 'GET';
		var prm = v.params || {}, com = v.components || [];
		if (url === '') {
			if (I.isFunction(err)) {
				err({'code': '99', 'message' : 'url is required'});
			}
			return false;
		}
		var ct = (new Date()).getTime(), jd='jd_' + ct, jf = 'jf_' + ct, jc = 'jc_' + ct, bd = $('body');
		var style = 'width:0px;height:0px;display:none;';
		bd.append('<div id="' + jd + '" name="' + jd + '" style="' + style + '"></div>');
		bd.append('<form id="' + jc + '" action="' + url + '" ' +
				  'method="' + mtd + '"' + (I.isString(enc) ? ' enctype="' + enc + '"' : '') + '></form>');
		if (I.isObject(prm)) {
			var lid;
			$.each(prm, function(k, v) {
				lid = $('#' + jc + ' input').length; 
				$('#' + jc).append('<input type="hidden" class="prm_' + lid + '" name="' + k + '" value=""/>');
				$('#' + jc + ' .prm_' + lid).val(v);
			});
		}
		if (I.isArray(com)) {
			$.each(com, function(i, v) {
				$('#' + jc).append(v);
			});
		}		
		$('#' + jd).append('<iframe name="'+jf+'" id="'+jf+'" style="'+style+'"></iframe>');
		$('#' + jf).load(function(){
			if (I.isFunction(done)) {
				done();
			}
			try {
				var rt = $(this).contents().find('body').html(), o;
				rt = rt.replace(/<[^>]*?pre[^>]*?>/,'').replace(/<[^>]*?PRE[^>]*?>/,'');
				rt = rt.replace(/<[^>]*?\/[^>]*?pre[^>]*?>/,'').replace(/<[^>]*?\/[^>]*?PRE[^>]*?>/,'');
				o = JSON.parse(rt);
				if (I.isFunction(suc)) {
					suc(o);
				}
			} catch (e) {
				// ignore, it could be a download process
			}
			setTimeout(function(){
				$('#' + jd).remove();
				$('#' + jc).remove();
			}, 100);
		});
		$('#' + jc).hide();				
		$('#' + jc).attr('target', jf);
		try {
			$('#' + jc).submit();
		} catch (e) {
			if (I.isFunction(done)) {
				done();
			}
			if (I.isFunction(err)) {
				err({'code': '99', 'message' : e + ''});
			}
		}
		return false;
	};	
})(jQuery);