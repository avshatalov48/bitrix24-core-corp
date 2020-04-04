function BxDiskInterfaceGrid(table_id)
{
	this.oActions = {};
	this.oColsMeta = {};
	this.oColsNames = {};
	this.oEditData = {};
	this.oSaveData = {};
	this.oOptions = {};
	this.oVisibleCols = null;
	this.vars = {};
	this.menu = null;
	this.settingsMenu = [];
	this.filterMenu = [];
	this.checkBoxCount = 0;
	this.bColsChanged = false;
	this.bViewsChanged = false;
	this.oFilterRows = {};
	this.activeRow = null;

	var _this = this;
	this.table_id = table_id;
	this.tableNode = BX(this.table_id);

	this.InitTable = function()
	{
		var tbl = document.getElementById(this.table_id);
		if(!tbl || tbl.rows.length<1 || tbl.rows[0].cells.length<1)
			return;

		var i;
		var nCols = tbl.rows[0].cells.length;

		/*head row actions*/
		for(i=0; i<nCols; i++)
		{
			var j;
			for(j=0; j<2; j++)
			{
				var cell = tbl.rows[j].cells[i];

				cell.onmouseover = function(){_this.HighlightGutter(this, true)};
				cell.onmouseout = function(){_this.HighlightGutter(this, false)};
				if(j==1)
				{
					if(cell.className && (cell.className == 'bx-disk-actions-col' || cell.className == 'bx-disk-checkbox-col'))
						continue;

					//DD handlers
					if(this.vars.user_authorized)
					{
						cell.onbxdragstart = _this.DragStart;
						cell.onbxdragstop = _this.DragStop;
						cell.onbxdrag = _this.Drag;
						cell.onbxdraghout = function(){_this.HighlightGutter(this, false)};
						jsDD.registerObject(cell);

						cell.onbxdestdraghover = _this.DragHover;
						cell.onbxdestdraghout = _this.DragOut;
						cell.onbxdestdragfinish = _this.DragFinish;
						jsDD.registerDest(cell);
					}
				}
			}
		}

		var n = tbl.rows.length;
		for(i=0; i<n; i++)
		{
			var row = tbl.rows[i];

			if(row.className && row.className == 'bx-disk-grid-footer')
				continue;

			/*first and last columns style classes*/
			row.cells[0].className += ' bx-disk-left';
	 		row.cells[row.cells.length-1].className += ' bx-disk-right';

			if(i>=2)
			{
				/*first column checkbox action*/
				var checkbox = row.cells[0].childNodes[0];
				if(checkbox && checkbox.tagName && checkbox.tagName.toUpperCase() == "INPUT" && checkbox.type.toUpperCase() == "CHECKBOX")
				{
					checkbox.onclick = function(){_this.SelectRow(this); _this.EnableActions()};
					BX.bind(row, "click", _this.OnClickRow);
					this.checkBoxCount++;
				}

				/*rows mousover action*/
//				row.onmouseover = function(){_this.HighlightRow(this, true)};
//				row.onmouseout = function(){_this.HighlightRow(this, false)};

//				if(i%2 == 0)
//					row.className += ' bx-odd';
//				else
//					row.className += ' bx-even';
			}
			if(row.oncontextmenu)
				BX.bind(row, "contextmenu", this.OnRowContext);
		}

		if(tbl.rows.length > 2)
		{
			tbl.rows[2].className += ' bx-top';
			var r = tbl.rows[tbl.rows.length-1];
			if(r.className && r.className == 'bx-disk-grid-footer')
				r = tbl.rows[tbl.rows.length-2];
			r.className += ' bx-disk-bottom';
		}
	};

	this.OnRowContext = function(e)
	{
		if(!_this.menu)
			return;

		if(!e)
			e = window.event;
		if(!phpVars.opt_context_ctrl && e.ctrlKey || phpVars.opt_context_ctrl && !e.ctrlKey)
			return;

		var targetElement;
		if(e.target)
			targetElement = e.target;
		else if(e.srcElement)
			targetElement = e.srcElement;

		//column context menu
		var el = targetElement;
		while(el && !(el.tagName && el.tagName.toUpperCase() == 'TD' && el.oncontextmenu))
			el = BX.findParent(el, {tag: "td"});

		var col_menu = null;
		if(el && el.oncontextmenu)
		{
			col_menu = el.oncontextmenu();
			col_menu[col_menu.length] = {'SEPARATOR':true};
		}

		//row context menu
		el = targetElement;
		while(el && !(el.tagName && el.tagName.toUpperCase() == 'TR' && el.oncontextmenu))
			el = BX.findParent(el, {tag: "tr"});

		if(el.tagName.toUpperCase() == 'TR')
		{
			_this.showNewPopupMenu(e, BX.util.array_merge(col_menu, el.oncontextmenu()));
			e.returnValue = false;
			if(e.preventDefault)
				e.preventDefault();
			return;
		}


		var menu = _this.menu;
		menu.PopupHide();

		_this.activeRow = el;
		if(_this.activeRow)
			_this.activeRow.className += ' bx-active';

		menu.OnClose = function()
		{
			if(_this.activeRow)
			{
				_this.activeRow.className = _this.activeRow.className.replace(/\s*bx-active/i, '');
				_this.activeRow = null;
			}
			_this.SaveColumns();
		};

		//combined menu
		var menuItems = BX.util.array_merge(col_menu, el.oncontextmenu());
		if(menuItems.length == 0)
			return;
		menu.SetItems(menuItems);
		menu.BuildItems();

		var arScroll = BX.GetWindowScrollPos();
		var x = e.clientX + arScroll.scrollLeft;
		var y = e.clientY + arScroll.scrollTop;
		var pos = {};
		pos['left'] = pos['right'] = x;
		pos['top'] = pos['bottom'] = y;

		menu.PopupShow(pos);

		e.returnValue = false;
		if(e.preventDefault)
			e.preventDefault();
	};

	this.showNewPopupMenu = function(el, oldItems)
	{
		var reformatItems = [];
		for (var item in oldItems) {
			if (!oldItems.hasOwnProperty(item)) {
				continue;
			}
			if(oldItems[item].hasOwnProperty('SHOW') && oldItems[item]['SHOW'] == false)
			{
				continue;
			}
			if (BX.type.isArray(oldItems[item]["MENU"]))
			{
				reformatItems.push({
					onclick: function(event, item)
					{
						var oldItems = item.submenu;
						var reformatItems = [];
						for (var item in oldItems) {
							if (!oldItems.hasOwnProperty(item)) {
								continue;
							}
							reformatItems.push({
								onclick: oldItems[item]['ONCLICK'],
								text: oldItems[item]['TEXT']
							});
						}
						BX.PopupMenu.show(
							'action_bizproc',
							event,
							reformatItems,
							{
								autoHide: true,
								closeByEsc: true,
								events: {
									onPopupClose: function () {
										BX.PopupMenu.destroy('action_bizproc')
									}
								}
							}
						);
					},
					submenu: oldItems[item]["MENU"],
					text: oldItems[item]['TEXT']
				});
			}
			else
			{
				reformatItems.push({
					onclick: oldItems[item]['ONCLICK'],
					text: oldItems[item]['TEXT']
				});
			}
		}
		BX.PopupMenu.show(
			'grid_action_pm',
			el,
			reformatItems,
			{
				autoHide: true,
				closeByEsc: true,
				events: {
					onAfterPopupShow: function (popupWindow) {
						BX.bindDelegate(BX('popup-window-content-menu-popup-grid_action_pm'), 'click', {className: 'menu-popup-item'}, function (event) {
							popupWindow.close();
						});
					},
					onPopupClose: function () {
						BX.PopupMenu.destroy('grid_action_pm')
					}
				}
			}
		);
	};

	this.ShowActionMenu = function(el, index)
	{
		_this.menu.PopupHide();

		_this.activeRow = BX.findParent(el, {tag: "tr"});
		if(_this.activeRow)
			_this.activeRow.className += ' bx-active';

		this.showNewPopupMenu(el, _this.oActions[index]);

		return;
	};

	this.HighlightGutter = function(cell, on)
	{
		var table = cell.parentNode.parentNode.parentNode;
		var gutter = table.rows[0].cells[cell.cellIndex];
		var bSorted = (gutter.className.indexOf('bx-disk-sorted') != -1);
		if(on)
		{
			if(bSorted)
			{
				gutter.className += ' bx-over-sorted';
				cell.className += ' bx-over-sorted';
			}
			else
			{
				gutter.className += ' bx-over';
				cell.className += ' bx-over';
			}
		}
		else
		{
			if(bSorted)
			{
				gutter.className = gutter.className.replace(/\s*bx-over-sorted/i, '');
				cell.className = cell.className.replace(/\s*bx-over-sorted/i, '');
			}
			else
			{
				gutter.className = gutter.className.replace(/\s*bx-over/i, '');
				cell.className = cell.className.replace(/\s*bx-over/i, '');
			}
		}
	};

	this.HighlightRow = function(row, on)
	{
		if(on)
			row.className += ' bx-over';
		else
			row.className = row.className.replace(/\s*bx-over/i, '');
	};

	this.SelectRow = function(checkbox)
	{
		var row = checkbox.parentNode.parentNode;
		var tbl = row.parentNode.parentNode;
		var span = document.getElementById(tbl.id+'_selected_span');
		var selCount = parseInt(span.innerHTML);

		if(checkbox.checked)
		{
			row.className += ' bx-selected selected';
			selCount++;
		}
		else
		{
			row.className = row.className.replace(/\s*bx-selected/ig, '');
			row.className = row.className.replace(/\s*selected/ig, '');
			selCount--;
		}
		span.innerHTML = selCount.toString();

		var checkAll = document.getElementById(tbl.id+'_check_all');

		if(selCount == this.checkBoxCount)
			checkAll.checked = true;
		else
			checkAll.checked = false;

		if(checkbox.checked)
		{
			BX.onCustomEvent("onSelectRow", [this, selCount, checkbox]);
		}
		else
		{
			BX.onCustomEvent("onUnSelectRow", [this, selCount, checkbox]);
		}
	};

	this.OnClickRow = function(e)
	{
		if(!e)
			e = window.event;
		//if(!e.ctrlKey)
		//	return;
		var obj = (e.target? e.target : (e.srcElement? e.srcElement : null));
		if(!obj)
			return;
		if(!obj.parentNode.cells)
			return;
		var checkbox = obj.parentNode.cells[0].childNodes[0];
		if(checkbox && checkbox.tagName && checkbox.tagName.toUpperCase() == "INPUT" && checkbox.type.toUpperCase() == "CHECKBOX" && !checkbox.disabled)
		{
			checkbox.checked = !checkbox.checked;
			_this.SelectRow(checkbox);
		}
		else
		{
			var tr = BX.findParent(obj, {
				tagName: 'tr',
				className: 'bx-disk-table-body'
			}, this.tableNode);
			if(tr)
			{
				var td = BX.findChild(tr, {
					tagName: 'td',
					className: 'bx-disk-checkbox-col'
				});
				if(td)
				{
					checkbox = BX.findChild(td, {
						tagName: 'input',
						property: {
							type: 'checkbox'
						}
					});
					if(checkbox)
					{
						checkbox.checked = !checkbox.checked;
						_this.SelectRow(checkbox);
					}
				}
			}

		}
		_this.EnableActions();
	};

	this.SelectAllRows = function(checkbox)
	{
		var tbl = document.getElementById(this.table_id);
		var bChecked = checkbox.checked;
		var i;
		var n = tbl.rows.length;
		for(i=2; i<n; i++)
		{
			var box = tbl.rows[i].cells[0].childNodes[0];
			if(box && box.tagName && box.tagName.toUpperCase() == 'INPUT' && box.type.toUpperCase() == "CHECKBOX")
			{
				if(box.checked != bChecked && !box.disabled)
				{
					box.checked = bChecked;
					this.SelectRow(box);
				}
			}
		}
		this.EnableActions();
	};

	this.EnableActions = function()
	{
		var form = document.forms['form_'+this.table_id];
		if(!form) return;

		var bEnabled = this.IsActionEnabled();
		var bEnabledEdit = this.IsActionEnabled('edit');

		if(form.apply) form.apply.disabled = !bEnabled;
		var b = document.getElementById('edit_button_'+this.table_id);
		//if(b) b.className = 'context-button icon action-edit-button'+(bEnabledEdit? '':'-dis');
		b = document.getElementById('delete_button_'+this.table_id);
		//if(b) b.className = 'context-button icon action-delete-button'+(bEnabled? '':'-dis');
	};

	this.IsActionEnabled = function(action)
	{
		var form = document.forms['form_'+this.table_id];
		if(!form) return;

		var bChecked = false;
		var span = document.getElementById(this.table_id+'_selected_span');
		if(span && parseInt(span.innerHTML)>0)
			bChecked = true;

		var elAll = form['action_all_rows_'+this.table_id];
		if(action == 'edit')
			return !(elAll && elAll.checked) && bChecked;
		else
			return (elAll && elAll.checked) || bChecked;
	};

	this.SwitchActionButtons = function(bShow)
	{
		var buttonsTd = document.getElementById("bx_grid_"+this.table_id+"_action_buttons");
		var td = buttonsTd;
		while(td = BX.findNextSibling(td, {tag: 'td'}))
			td.style.display = (bShow? 'none':'');
		buttonsTd.style.display = (bShow? '':'none');


		if(bShow && window.jsDD)
		{
			window.jsDD.Disable();
		}
		else
		{
			window.jsDD.Enable();
		}
	};

	this.getSwitchActionButtonsContainer = function()
	{
		return BX("bx_grid_"+this.table_id+"_action_buttons");
	}

	this.ActionEdit = function(a)
	{
		if(this.IsActionEnabled('edit'))
		{
			var form = document.forms['form_'+this.table_id];
			if(!form)
				return;

			//show form buttons
			this.SwitchActionButtons(true);

			//go through rows and show inputs
			var ids = form['ID[]'];
			if(!ids.length)
				ids = new Array(ids);

			for(var i=0; i<ids.length; i++)
			{
				var el = ids[i];
				if(el.checked)
				{
					var tr = BX.findParent(el, {tag: "tr"});
					BX.denyEvent(tr, 'dblclick');

					//go through columns
					var td = BX.findParent(el, {tag: "td"});
					td = BX.findNextSibling(td, {tag: "td"});
					if(td.className == 'bx-disk-actions-col')
						td = BX.findNextSibling(td, {tag: "td"});

					var row_id = el.value;
					this.oSaveData[row_id] = {};
					for(var col_id in this.oColsMeta)
					{
						if(this.oColsMeta[col_id].editable == true && this.oEditData[row_id][col_id] !== false)
						{
							this.oSaveData[row_id][col_id] = td.innerHTML;
							td.innerHTML = '';

							//insert controls
							var data = this.oEditData[row_id][col_id];
							var name = 'FIELDS['+row_id+']['+col_id+']';
							switch(this.oColsMeta[col_id].type)
							{
								case 'checkbox':
									td.appendChild(BX.create('INPUT', {'props': {
										'type':'hidden',
										'name':name,
										'value':'N'
									}}));
									td.appendChild(BX.create('INPUT', {'props': {
										'type':'checkbox',
										'name':name,
										'value':'Y',
										'checked':(data == 'Y'),
										'defaultChecked':(data == 'Y')
									}}));
									break;
								case 'list':
									var options = [];
									for(var list_val in this.oColsMeta[col_id].items)
									{
										options[options.length] = BX.create('OPTION', {
											'props': {'value':list_val, 'selected':(list_val == data)},
											'text': this.oColsMeta[col_id].items[list_val]}
										);
									}

									td.appendChild(BX.create('SELECT', {
										'props': {'name':name},
										'children': options
									}));
									break;
								case 'date':
									var span = BX.create('SPAN', {'style':{'whiteSpace':'nowrap'}});
									span.appendChild(BX.create('INPUT', {'props': {
										'type':'text',
										'name':name,
										'value':data,
										'size':(this.oColsMeta[col_id].size? this.oColsMeta[col_id].size : 10)
									}}));
									span.appendChild(BX.create('A', {
										'props': {
											'href':'javascript:void(0);',
											'title': this.vars.mess.calend_title
										},
										'html':'<img src="'+this.vars.calendar_image+'" alt="'+this.vars.mess.calend_title+'" class="calendar-icon" onclick="BX.calendar({node:this, field:\''+name+'\', bTime: true, currentTime: \''+this.vars.server_time+'\'});" onmouseover="this.className+=\' calendar-icon-hover\';" onmouseout="this.className = this.className.replace(/\s*calendar-icon-hover/ig, \'\');" border="0"/>'}));
									td.appendChild(span);
									break;
								default:
									var props = {
										className: 'bx-disk-folder-title list',
										'type':'text',
										'name':name,
										'value':data,
										'size':(this.oColsMeta[col_id].size? this.oColsMeta[col_id].size : 15)
									};
									if(this.oColsMeta[col_id].maxlength)
										props.maxLength = this.oColsMeta[col_id].maxlength;
									td.appendChild(BX.create('INPUT', {'props': props}));
									break;
							}
						}
						td = BX.findNextSibling(td, {tag: "td"});
					}
				}
				el.disabled = true;
			}

			form.elements['action_button_'+this.table_id].value = 'edit';
		}
	};

	this.ActionCancel = function()
	{
		var form = document.forms['form_'+this.table_id];
		if(!form)
			return;

		//hide form buttons
		this.SwitchActionButtons(false);

		//go through rows and restore values
		var ids = form['ID[]'];
		if(!ids.length)
			ids = new Array(ids);

		for(var i=0; i<ids.length; i++)
		{
			var el = ids[i];
			if(el.checked)
			{
				var tr = BX.findParent(el, {tag: "tr"});
				BX.allowEvent(tr, 'dblclick');

				//go through columns
				var td = BX.findParent(el, {tag: "td"});
				td = BX.findNextSibling(td, {tag: "td"});
				if(td.className == 'bx-disk-actions-col')
					td = BX.findNextSibling(td, {tag: "td"});

				var row_id = el.value;
				for(var col_id in this.oColsMeta)
				{
					if(this.oColsMeta[col_id].editable == true && this.oEditData[row_id][col_id] !== false)
						td.innerHTML = this.oSaveData[row_id][col_id];

					td = BX.findNextSibling(td, {tag: "td"});
				}
			}
			el.disabled = false;
		}

		form.elements['action_button_'+this.table_id].value = '';
	};

	this.ActionDelete = function()
	{
		var form = document.forms['form_'+this.table_id];
		if(!form)
			return;

		form.elements['action_button_'+this.table_id].value = 'delete';

		BX.submit(form);
	};

	this.SetActionName = function(actionName)
	{
		var form = this.GetForm();
		if(!form)
			return;

		form.elements['action_button_'+this.table_id].value = actionName;
	};

	this.GetForm = function()
	{
		return document.forms['form_'+this.table_id];
	};

	this.GetCheckedCheckboxes = function()
	{
		var checked = [];
		for(var i=0, n=this.GetForm().elements.length; i<n; i++)
		{
			var el = this.GetForm().elements[i];
			if(el.type.toLowerCase() == 'checkbox' && el.checked)
			{
				checked.push(el);
			}
		}

		return checked;
	};

	this.ActionCustom = function(actionName)
	{
		this.SetActionName(actionName);
		BX.submit(form);
	};

	this.DeleteItem = function(field_id, message)
	{
		var checkbox = document.getElementById('ID_' + field_id);
		if(checkbox)
		{
			if(confirm(message))
			{
				var form = document.forms['form_'+this.table_id];
				if(!form)
					return;

				//go through rows and restore values
				var ids = form['ID[]'];
				if(!ids.length)
					ids = new Array(ids);

				for(var i=0; i<ids.length; i++)
				{
					ids[i].checked = false;
				}

				checkbox.checked = true;
				this.ActionDelete();
			}
		}
	};

	this.ForAllClick = function(el)
	{
		if(el.checked && !confirm(this.vars.mess.for_all_confirm))
		{
			el.checked=false;
			return;
		}

		//go through rows
		var ids = el.form['ID[]'];
		if(ids)
		{
			if(!ids.length)
				ids = new Array(ids);
			for(var i=0; i<ids.length; i++)
				ids[i].disabled = el.checked;
		}

		this.EnableActions();
	};

	this.Sort = function(url, sort_state, def_order, args)
	{
		if(sort_state == '')
		{
			var e = null, bControl = false;
			if(args.length > 0)
				e = args[0];
			if(!e)
				e = window.event;
			if(e)
				bControl = e.ctrlKey;
			url += (bControl? (def_order == 'acs'? 'desc':'asc') : def_order);
		}
		else if(sort_state == 'asc')
			url += 'desc';
		else
			url += 'asc';

		this.Reload(url);
	};

	this.InitVisCols = function()
	{
		if(this.oVisibleCols == null)
		{
			this.oVisibleCols = {};
			for(var id in this.oColsMeta)
				this.oVisibleCols[id] = true;
		}
	};

	this.CheckColumn = function(column, menuItem)
	{
		var colMenu = this.menu.GetMenuByItemId(menuItem.id);
		var bShow = !(colMenu.GetItemInfo(menuItem).ICON == 'checked');
		colMenu.SetItemIcon(menuItem, (bShow? 'checked':''));

		this.InitVisCols();
		this.oVisibleCols[column] = bShow;
		this.bColsChanged = true;
	};

	this.HideColumn = function(column)
	{
		this.InitVisCols();
		this.oVisibleCols[column] = false;
		this.bColsChanged = true;
		this.SaveColumns();
	};

	this.ApplySaveColumns = function()
	{
		this.menu.PopupHide();
		this.SaveColumns();
	};

	this.SaveColumns = function(columns)
	{
		var sCols = '';
		if(columns)
		{
			sCols = columns
		}
		else
		{
			if(!_this.bColsChanged)
				return;

			for(var id in _this.oVisibleCols)
				if(_this.oVisibleCols[id])
					sCols += (sCols!=''? ',':'')+id;
		}
		BX.ajax.get('/bitrix/components'+_this.vars.component_path+'/settings.php?GRID_ID='+_this.table_id+'&action=showcolumns&columns='+sCols+'&sessid='+_this.vars.sessid, function(){_this.Reload()});
	};

	this.Reload = function(url)
	{
		jsDD.Disable();

		if(!url)
		{
			url = this.vars.current_url;
		}

		if(this.vars.ajax.AJAX_ID != '')
		{
			BX.ajax.insertToNode(url+(url.indexOf('?') == -1? '?':'&')+'bxajaxid='+this.vars.ajax.AJAX_ID, 'comp_'+this.vars.ajax.AJAX_ID);
		}
		else
		{
			window.location = url;
		}
	};

	this.SetTheme = function(menuItem, theme)
	{
		BX.loadCSS(this.vars.template_path+'/themes/'+theme+'/style.css');
		BX(_this.table_id).className = 'bx-disk-interface-grid bx-disk-interface-grid-theme-'+theme;

		var themeMenu = this.menu.GetMenuByItemId(menuItem.id);
		themeMenu.SetAllItemsIcon('');
		themeMenu.SetItemIcon(menuItem, 'checked');

		BX.ajax.get('/bitrix/components'+_this.vars.component_path+'/settings.php?GRID_ID='+_this.table_id+'&action=settheme&theme='+theme+'&sessid='+_this.vars.sessid);
	};

	this.SetView = function(view_id)
	{
		var filter_id = _this.oOptions.views[view_id].saved_filter;
		var func = (filter_id && _this.oOptions.filters[filter_id]?
			function(){_this.ApplyFilter(filter_id)} :
			function(){_this.Reload()});

		BX.ajax.get('/bitrix/components'+_this.vars.component_path+'/settings.php?GRID_ID='+_this.table_id+'&action=setview&view_id='+view_id+'&sessid='+_this.vars.sessid, func);
	};

	this.EditCurrentView = function()
	{
		this.ShowSettings(this.oOptions.views[this.oOptions.current_view],
			function()
			{
				_this.SaveSettings(_this.oOptions.current_view, true);
			}
		);
	};

	this.AddView = function()
	{
		var view_id = 'view_'+Math.round(Math.random()*1000000);

		var view = {};
		for(var i in this.oOptions.views[this.oOptions.current_view])
			view[i] = this.oOptions.views[this.oOptions.current_view][i];
		view.name = this.vars.mess.viewsNewView;

		this.ShowSettings(view,
			function()
			{
				var data = _this.SaveSettings(view_id);

				_this.oOptions.views[view_id] = {
					'name':data.name,
					'columns':data.columns,
					'sort_by':data.sort_by,
					'sort_order':data.sort_order,
					'page_size':data.page_size,
					'saved_filter':data.saved_filter
				};
				_this.bViewsChanged = true;

				var form = document['views_'+_this.table_id];
				form.views_list.options[form.views_list.length] = new Option((data.name != ''? data.name:_this.vars.mess.viewsNoName), view_id, true, true);
			}
		);
	};

	this.EditView = function(view_id)
	{
		this.ShowSettings(this.oOptions.views[view_id],
			function()
			{
				var data = _this.SaveSettings(view_id);

				_this.oOptions.views[view_id] = {
					'name':data.name,
					'columns':data.columns,
					'sort_by':data.sort_by,
					'sort_order':data.sort_order,
					'page_size':data.page_size,
					'saved_filter':data.saved_filter
				};
				_this.bViewsChanged = true;

				var form = document['views_'+_this.table_id];
				form.views_list.options[form.views_list.selectedIndex].text = (data.name != ''? data.name:_this.vars.mess.viewsNoName);
			}
		);
	};

	this.DeleteView = function(view_id)
	{
		if(!confirm(this.vars.mess.viewsDelete))
			return;

		var form = document['views_'+this.table_id];
		var index = form.views_list.selectedIndex;
		form.views_list.remove(index);
		form.views_list.selectedIndex = (index < form.views_list.length? index : form.views_list.length-1);

		this.bViewsChanged = true;

		BX.ajax.get('/bitrix/components'+this.vars.component_path+'/settings.php?GRID_ID='+this.table_id+'&action=delview&view_id='+view_id+'&sessid='+_this.vars.sessid);
	};

	this.ShowSettings = function(view, action)
	{
		var bCreated = false;
		if(!window['settingsDialog'+this.table_id])
		{
			window['settingsDialog'+this.table_id] = new BX.CDialog({
				'content':'<form name="settings_'+this.table_id+'"></form>',
				'title': this.vars.mess.settingsTitle,
				'width': this.vars.settingWndSize.width,
				'height': this.vars.settingWndSize.height,
				'resize_id': 'InterfaceGridSettingWnd'
			});
			bCreated = true;
		}

		window['settingsDialog'+this.table_id].ClearButtons();
		window['settingsDialog'+this.table_id].SetButtons([
			{
				'title': this.vars.mess.settingsSave,
				'action': function(){
					action();
					this.parentWindow.Close();
				}
			},
			BX.CDialog.prototype.btnCancel
		]);
		window['settingsDialog'+this.table_id].Show();

		var form = document['settings_'+this.table_id];

		if(bCreated)
			form.appendChild(BX('view_settings_'+this.table_id));

		//name
		form.view_name.focus();
		form.view_name.value = view.name;

		//get visible columns
		var aVisCols = [];
		if(view.columns != '')
		{
			aVisCols = view.columns.split(',');
		}
		else
		{
			for(var i in this.oColsMeta)
				aVisCols[aVisCols.length] = i;
		}

		var oVisCols = {}, n;
		for(i=0, n=aVisCols.length; i<n; i++)
			oVisCols[aVisCols[i]] = true;

		//invisible cols
		jsSelectUtils.deleteAllOptions(form.view_all_cols);
		for(i in this.oColsNames)
			if(!oVisCols[i])
				form.view_all_cols.options[form.view_all_cols.length] = new Option(this.oColsNames[i], i, false, false);

		//visible cols
		jsSelectUtils.deleteAllOptions(form.view_cols);
		for(i in oVisCols)
			form.view_cols.options[form.view_cols.length] = new Option(this.oColsNames[i], i, false, false);

		//sorting
		jsSelectUtils.selectOption(form.view_sort_by, view.sort_by);
		jsSelectUtils.selectOption(form.view_sort_order, view.sort_order);

		//page size
		jsSelectUtils.selectOption(form.view_page_size, view.page_size);

		//saved filter
		jsSelectUtils.deleteAllOptions(form.view_filters);
		form.view_filters.options[0] = new Option(this.vars.mess.viewsFilter, '');
		for(i in this.oOptions.filters)
			form.view_filters.options[form.view_filters.length] = new Option(this.oOptions.filters[i].name, i, (i == view.saved_filter), (i == view.saved_filter));

		//common options
		if(form.set_default_settings)
		{
			form.set_default_settings.checked = false;
			form.delete_users_settings.disabled = true;
		}
	};

	this.SaveSettings = function(view_id, doReload)
	{
		var form = document['settings_'+this.table_id];

		var sCols = '';
		var n = form.view_cols.length;
		for(var i=0; i<n; i++)
			sCols += (sCols!=''? ',':'')+form.view_cols[i].value;

		var data = {
			'GRID_ID': this.table_id,
			'view_id': view_id,
			'action': 'savesettings',
			'sessid': this.vars.sessid,
			'name': form.view_name.value,
			'columns': sCols,
			'sort_by': form.view_sort_by.value,
			'sort_order': form.view_sort_order.value,
			'page_size': form.view_page_size.value,
			'saved_filter': form.view_filters.value
		};

		if(form.set_default_settings)
		{
			data.set_default_settings = (form.set_default_settings.checked? 'Y':'N');
			data.delete_users_settings = (form.delete_users_settings.checked? 'Y':'N');
		}

		var handler = null;
		if(doReload === true)
		{
			handler = function()
			{
				if(data.saved_filter && _this.oOptions.filters[data.saved_filter])
				{
					_this.ApplyFilter(data.saved_filter);
				}
				else
				{
					_this.Reload();
				}
			};
		}

		BX.ajax.post('/bitrix/components'+_this.vars.component_path+'/settings.php', data, handler);

		return data;
	};

	this.ReloadViews = function()
	{
		if(_this.bViewsChanged)
			_this.Reload();
	};

	this.ShowViews = function()
	{
		this.bViewsChanged = false;
		var bCreated = false;
		if(!window['viewsDialog'+this.table_id])
		{
			var applyBtn = new BX.CWindowButton({
				'title': this.vars.mess.viewsApply,
				'hint': this.vars.mess.viewsApplyTitle,
				'action': function(){
					var form = document['views_'+_this.table_id];
					if(form.views_list.selectedIndex != -1)
						_this.SetView(form.views_list.value);

					window['bxGrid_'+_this.table_id].bViewsChanged = false;
					this.parentWindow.Close();
				}
			});

			window['viewsDialog'+this.table_id] = new BX.CDialog({
				'content':'<form name="views_'+this.table_id+'"></form>',
				'title': this.vars.mess.viewsTitle,
				'buttons': [applyBtn, BX.CDialog.prototype.btnClose],
				'width': this.vars.viewsWndSize.width,
				'height': this.vars.viewsWndSize.height,
				'resize_id': 'InterfaceGridViewsWnd'
			});

			BX.addCustomEvent(window['viewsDialog'+this.table_id], 'onWindowUnRegister', this.ReloadViews);

			bCreated = true;
		}

		window['viewsDialog'+this.table_id].Show();

		var form = document['views_'+this.table_id];

		if(bCreated)
			form.appendChild(BX('views_list_'+this.table_id));
	};

	/* DD handlers */

	this.DragStart = function()
	{
		var div = document.body.appendChild(document.createElement("DIV"));
		div.style.position = 'absolute';
		div.style.zIndex = 10;
		div.className = 'bx-drag-object';
		div.innerHTML = this.innerHTML;
		div.style.width = this.clientWidth+'px';
		this.__dragCopyDiv = div;
		this.className += ' bx-drag-source';

		var arrowDiv = document.body.appendChild(document.createElement("DIV"));
		arrowDiv.style.position = 'absolute';
		arrowDiv.style.zIndex = 20;
		arrowDiv.className = 'bx-drag-arrow';
		this.__dragArrowDiv = arrowDiv;

		return true;
	};

	this.Drag = function(x, y)
	{
		var div = this.__dragCopyDiv;
		div.style.left = x+'px';
		div.style.top = y+'px';

		return true;
	};

	this.DragStop = function()
	{
		this.className = this.className.replace(/\s*bx-grid-drag-source/ig, "");

		this.__dragCopyDiv.parentNode.removeChild(this.__dragCopyDiv);
		this.__dragCopyDiv = null;

		this.__dragArrowDiv.parentNode.removeChild(this.__dragArrowDiv);
		this.__dragArrowDiv = null;

		return true;
	};

	this.DragHover = function(obDrag, x, y)
	{
		if(BX.hasClass(obDrag, 'draggable'))
		{
			//it's items from grid, tile. We can drag they to breadcrumbs tree.
			return;
		}

		_this.HighlightGutter(this, true);
		this.className += ' bx-drag-over';

		var div = obDrag.__dragArrowDiv;
		var pos = BX.pos(this);

		if(typeof(obDrag.cellIndex) == 'undefined')
		{
			return;
		}

		if(this.cellIndex <= obDrag.cellIndex)
			div.style.left = (pos['left']-6)+'px';
		else
			div.style.left = (pos['right']-6)+'px';
		div.style.top = (pos['top']-12)+'px';

		return true;
	};

	this.DragOut = function(obDrag, x, y)
	{
		if(BX.hasClass(obDrag, 'draggable'))
		{
			//it's items from grid, tile. We can drag they to breadcrumbs tree.
			return;
		}

		if(typeof(obDrag.cellIndex) == 'undefined')
		{
			return;
		}

		_this.HighlightGutter(this, false);
		this.className = this.className.replace(/\s*bx-drag-over/ig, "");

		var div = obDrag.__dragArrowDiv;
		div.style.left = '-1000px';

		return true;
	};

	this.DragFinish = function(obDrag, x, y, e)
	{
		if(obDrag.tagName.toUpperCase() != 'TD')
			return false;
		_this.HighlightGutter(this, false);
		this.className = this.className.replace(/\s*bx-drag-over/ig, "");

		//can't move to itself
		if(this == obDrag)
			return true;

		var tbl = BX(_this.table_id);
		var delta = 0;
		for(var i=0; i < 2; i++)
		{
			var cell = tbl.rows[1].cells[i];
			if(cell.className && (cell.className.indexOf('bx-disk-actions-col') != -1 || cell.className.indexOf('bx-disk-checkbox-col') != -1))
				delta ++;
		}

		var cols = [];
		for(var id in _this.oColsMeta)
			cols[cols.length] = id;

		var index_from = obDrag.cellIndex-delta;
		var index_to = this.cellIndex-delta;

		var tmp = cols[index_from];
		if(index_to < index_from)
		{
			for(i = index_from; i > index_to; i--)
				cols[i] = cols[i-1];
		}
		else
		{
			for(i = index_from; i < index_to; i++)
				cols[i] = cols[i+1];
		}
		cols[index_to] = tmp;

		var sCols = '';
		for(i=0; i<cols.length; i++)
			sCols += (sCols != ''? ',':'')+cols[i];

		_this.SaveColumns(sCols);
		return true;
	};

	/* Filter */

	this.InitFilter = function()
	{
		var row = BX('flt_header_'+this.table_id);
		if(row)
			BX.bind(row, "contextmenu", this.OnRowContext);
	};

	this.SwitchFilterRow = function(row_id, menuItem)
	{
		if(menuItem)
		{
			var colMenu = this.menu.GetMenuByItemId(menuItem.id);
			colMenu.SetItemIcon(menuItem, (this.oFilterRows[row_id]? '':'checked'));
		}
		else
		{
			var mnu = this.filterMenu[0].MENU;
			for(var i=0; i<mnu.length; i++)
			{
				if(mnu[i].ID == 'flt_'+this.table_id+'_'+row_id)
				{
					mnu[i].ICONCLASS = (this.oFilterRows[row_id]? '':'checked');
					break;
				}
			}
		}

		var row = BX('flt_row_'+this.table_id+'_'+row_id);
		row.style.display = (this.oFilterRows[row_id]? 'none':'');
		this.oFilterRows[row_id] = (this.oFilterRows[row_id]? false:true);

		var a = BX('a_minmax_'+this.table_id);
		if(a && a.className.indexOf('bx-filter-max') != -1)
			this.SwitchFilter(a);

		this.SaveFilterRows();
	};

	this.SwitchFilterRows = function(on)
	{
		this.menu.PopupHide();

		var i=0;
		for(var id in this.oFilterRows)
		{
			i++;
			if(i == 1 && on == false)
				continue;
			this.oFilterRows[id] = on;
			var row = BX('flt_row_'+this.table_id+'_'+id);
			row.style.display = (on? '':'none');
		}

		var mnu = this.filterMenu[0].MENU;
		for(i=0; i<mnu.length; i++)
		{
			if(i == 0 && on == false)
				continue;
			if(mnu[i].SEPARATOR == true)
				break;
			mnu[i].ICONCLASS = (on? 'checked':'');
		}

		var a = BX('a_minmax_'+this.table_id);
		if(a && a.className.indexOf('bx-filter-max') != -1)
			this.SwitchFilter(a);

		this.SaveFilterRows();
	};

	this.SaveFilterRows = function()
	{
		var sRows = '';
		for(var id in this.oFilterRows)
			if(this.oFilterRows[id])
				sRows += (sRows!=''? ',':'')+id;

		BX.ajax.get('/bitrix/components'+this.vars.component_path+'/settings.php?GRID_ID='+this.table_id+'&action=filterrows&rows='+sRows+'&sessid='+this.vars.sessid);
	};

	this.SwitchFilter = function(a)
	{
		var on = (a.className.indexOf('bx-filter-min') != -1);
		a.className = (on? 'bx-filter-btn bx-filter-max' : 'bx-filter-btn bx-filter-min');
		a.title = (on? this.vars.mess.filterShow : this.vars.mess.filterHide);

		var row = BX('flt_content_'+this.table_id);
		row.style.display = (on? 'none':'');

		BX.ajax.get('/bitrix/components'+this.vars.component_path+'/settings.php?GRID_ID='+this.table_id+'&action=filterswitch&show='+(on? 'N':'Y')+'&sessid='+this.vars.sessid);
	};

	this.ClearFilter = function(form)
	{
		for(var i=0, n=form.elements.length; i<n; i++)
		{
			var el = form.elements[i];
			switch(el.type.toLowerCase())
			{
				case 'text':
				case 'textarea':
					el.value = '';
					break;
				case 'select-one':
					el.selectedIndex = 0;
					break;
				case 'select-multiple':
					for(var j=0, l=el.options.length; j<l; j++)
						el.options[j].selected = false;
					break;
				case 'checkbox':
					el.checked = false;
					break;
				default:
					break;
			}
			if(el.onchange)
				el.onchange();
		}

		BX.onCustomEvent(form, "onGridClearFilter", []);

		form.clear_filter.value = "Y";

		BX.submit(form);
	};

	this.ShowFilters = function()
	{
		var bCreated = false;
		if(!window['filtersDialog'+this.table_id])
		{
			var applyBtn = new BX.CWindowButton({
				'title': this.vars.mess.filtersApply,
				'hint': this.vars.mess.filtersApplyTitle,
				'action': function(){
					var form = document['filters_'+_this.table_id];
					if(form.filters_list.value)
						_this.ApplyFilter(form.filters_list.value);
					this.parentWindow.Close();
				}
			});

			window['filtersDialog'+this.table_id] = new BX.CDialog({
				'content':'<form name="filters_'+this.table_id+'"></form>',
				'title': this.vars.mess.filtersTitle,
				'buttons': [applyBtn, BX.CDialog.prototype.btnClose],
				'width': this.vars.filtersWndSize.width,
				'height': this.vars.filtersWndSize.height,
				'resize_id': 'InterfaceGridFiltersWnd'
			});

			bCreated = true;
		}

		window['filtersDialog'+this.table_id].Show();

		var form = document['filters_'+this.table_id];
		if(bCreated)
			form.appendChild(BX('filters_list_'+this.table_id));
	};

	this.AddFilter = function(fields)
	{
		if(!fields)
			fields = {};
		var filter_id = 'filter_'+Math.round(Math.random()*1000000);
		var filter = {'name':this.vars.mess.filtersNew, 'fields':fields};

		this.ShowFilterSettings(filter,
			function()
			{
				var data = _this.SaveFilter(filter_id);

				_this.oOptions.filters[filter_id] = {
					'name':data.name,
					'fields':data.fields
				};

				var form = document['filters_'+_this.table_id];
				form.filters_list.options[form.filters_list.length] = new Option((data.name != ''? data.name:_this.vars.mess.viewsNoName), filter_id, true, true);

				if(_this.filterMenu.length == 4) //no saved filters
					_this.filterMenu = BX.util.insertIntoArray(_this.filterMenu, 1, {'SEPARATOR':true});
				var mnuItem = {'ID': 'mnu_'+_this.table_id+'_'+filter_id, 'TEXT': BX.util.htmlspecialchars(data.name), 'TITLE': _this.vars.mess.ApplyTitle, 'ONCLICK':'bxGrid_'+_this.table_id+'.ApplyFilter(\''+filter_id+'\')'};
				_this.filterMenu = BX.util.insertIntoArray(_this.filterMenu, 2, mnuItem);
			}
		);
	};

	this.AddFilterAs = function()
	{
		var form = document.forms['filter_'+this.table_id];
		var fields = this.GetFilterFields(form);
		this.ShowFilters();
		this.AddFilter(fields);
	};

	this.EditFilter = function(filter_id)
	{
		this.ShowFilterSettings(this.oOptions.filters[filter_id],
			function()
			{
				var data = _this.SaveFilter(filter_id);

				_this.oOptions.filters[filter_id] = {
					'name':data.name,
					'fields':data.fields
				};

				var form = document['filters_'+_this.table_id];
				form.filters_list.options[form.filters_list.selectedIndex].text = (data.name != ''? data.name:_this.vars.mess.viewsNoName);

				for(var i=0, n=_this.filterMenu.length; i<n; i++)
				{
					if(_this.filterMenu[i].ID && _this.filterMenu[i].ID == 'mnu_'+_this.table_id+'_'+filter_id)
					{
						_this.filterMenu[i].TEXT = BX.util.htmlspecialchars(data.name);
						break;
					}
				}
			}
		);
	};

	this.DeleteFilter = function(filter_id)
	{
		if(!confirm(this.vars.mess.filtersDelete))
			return;

		var form = document['filters_'+this.table_id];
		var index = form.filters_list.selectedIndex;
		form.filters_list.remove(index);
		form.filters_list.selectedIndex = (index < form.filters_list.length? index : form.filters_list.length-1);

		for(var i=0, n=this.filterMenu.length; i<n; i++)
		{
			if(_this.filterMenu[i].ID && _this.filterMenu[i].ID == 'mnu_'+_this.table_id+'_'+filter_id)
			{
				this.filterMenu = BX.util.deleteFromArray(this.filterMenu, i);
				if(this.filterMenu.length == 5)
					this.filterMenu = BX.util.deleteFromArray(this.filterMenu, 1);
				break;
			}
		}

		delete this.oOptions.filters[filter_id];

		BX.ajax.get('/bitrix/components'+this.vars.component_path+'/settings.php?GRID_ID='+this.table_id+'&action=delfilter&filter_id='+filter_id+'&sessid='+_this.vars.sessid);
	};

	this.ShowFilterSettings = function(filter, action)
	{
		var bCreated = false;
		if(!window['filterSettingsDialog'+this.table_id])
		{
			window['filterSettingsDialog'+this.table_id] = new BX.CDialog({
				'content':'<form name="flt_settings_'+this.table_id+'"></form>',
				'title': this.vars.mess.filterSettingsTitle,
				'width': this.vars.filterSettingWndSize.width,
				'height': this.vars.filterSettingWndSize.height,
				'resize_id': 'InterfaceGridFilterSettingWnd'
			});
			bCreated = true;
		}

		window['filterSettingsDialog'+this.table_id].ClearButtons();
		window['filterSettingsDialog'+this.table_id].SetButtons([
			{
				'title': this.vars.mess.settingsSave,
				'action': function(){
					action();
					this.parentWindow.Close();
				}
			},
			BX.CDialog.prototype.btnCancel
		]);
		window['filterSettingsDialog'+this.table_id].Show();

		var form = document['flt_settings_'+this.table_id];

		if(bCreated)
			form.appendChild(BX('filter_settings_'+this.table_id));

		form.filter_name.focus();
		form.filter_name.value = filter.name;

		this.SetFilterFields(form, filter.fields);
	};

	this.SetFilterFields = function(form, fields)
	{
		for(var i=0, n = form.elements.length; i<n; i++)
		{
			var el = form.elements[i];

			if(el.name == 'filter_name')
				continue;

			var val = fields[el.name] || '';

			switch(el.type.toLowerCase())
			{
				case 'select-one':
				case 'text':
				case 'textarea':
					el.value = val;
					break;
				case 'radio':
				case 'checkbox':
					el.checked = (el.value == val);
					break;
				case 'select-multiple':
					var name = el.name.substr(0, el.name.length - 2);
					var bWasSelected = false;
					for(var j=0, l = el.options.length; j<l; j++)
					{
						var sel = (fields[name]? fields[name]['sel'+el.options[j].value] : null);
						el.options[j].selected = (el.options[j].value == sel);
						if(el.options[j].value == sel)
							bWasSelected = true;
					}
					if(!bWasSelected && el.options.length > 0 && el.options[0].value == '')
						el.options[0].selected = true;
					break;
				default:
					break;
			}
			if(el.onchange)
				el.onchange();
		}
	};

	this.GetFilterFields = function(form)
	{
		var fields = {};
		for(var i=0, n = form.elements.length; i<n; i++)
		{
			var el = form.elements[i];

			if(el.name == 'filter_name')
				continue;

			switch(el.type.toLowerCase())
			{
				case 'select-one':
				case 'text':
				case 'textarea':
					fields[el.name] = el.value;
					break;
				case 'radio':
				case 'checkbox':
					if(el.checked)
						fields[el.name] = el.value;
					break;
				case 'select-multiple':
					var name = el.name.substr(0, el.name.length - 2);
					fields[name] = {};
					for(var j=0, l = el.options.length; j<l; j++)
						if(el.options[j].selected)
							fields[name]['sel'+el.options[j].value] = el.options[j].value;
					break;
				default:
					break;
			}
		}
		return fields;
	};

	this.SaveFilter = function(filter_id)
	{
		var form = document['flt_settings_'+this.table_id];
		var data = {
			'GRID_ID': this.table_id,
			'filter_id': filter_id,
			'action': 'savefilter',
			'sessid': this.vars.sessid,
			'name': form.filter_name.value,
			'fields': this.GetFilterFields(form)
		};

		BX.ajax.post('/bitrix/components'+_this.vars.component_path+'/settings.php', data);

		return data;
	};

	this.ApplyFilter = function(filter_id)
	{
		var form = document.forms['filter_'+this.table_id];
		this.SetFilterFields(form, this.oOptions.filters[filter_id].fields);

		BX.submit(form);
	};

	this.OnDateChange = function(sel)
	{
		var bShowFrom=false, bShowTo=false, bShowHellip=false, bShowDays=false, bShowBr=false;

		if(sel.value == 'interval')
			bShowBr = bShowFrom = bShowTo = bShowHellip = true;
		else if(sel.value == 'before')
			bShowTo = true;
		else if(sel.value == 'after' || sel.value == 'exact')
			bShowFrom = true;
		else if(sel.value == 'days')
			bShowDays = true;

		BX.findNextSibling(sel, {'tag':'span', 'class':'bx-filter-from'}).style.display = (bShowFrom? '':'none');
		BX.findNextSibling(sel, {'tag':'span', 'class':'bx-filter-to'}).style.display = (bShowTo? '':'none');
		BX.findNextSibling(sel, {'tag':'span', 'class':'bx-filter-hellip'}).style.display = (bShowHellip? '':'none');
		BX.findNextSibling(sel, {'tag':'span', 'class':'bx-filter-days'}).style.display = (bShowDays? '':'none');
		var span = BX.findNextSibling(sel, {'tag':'span', 'class':'bx-filter-br'});
		if(span)
			span.style.display = (bShowBr? '':'none');
	};

	this.getRowByCheckBox = function(checkbox)
	{
		return checkbox.parentNode.parentNode;
	};

	this.removeRow = function(objectId, completeCallback)
	{
		var row = this.getRow(objectId);

		(new BX.easing({
			duration : 600,
			start : { opacity: 100, height : row.scrollHeight},
			finish : { opacity : 0, height : 0},
			transition : BX.easing.makeEaseOut(BX.easing.transitions.quad),
			step : function(state) {
				row.style.height = state.height + "px";
				row.style.opacity = state.opacity / 10;
			},
			complete : BX.delegate(function() {
				var checkbox = this.getCheckbox(objectId);
				if(checkbox.checked)
				{
					checkbox.checked = false;
					this.SelectRow(checkbox);
				}
				BX.remove(row);
				completeCallback && completeCallback();
			}, this)
		})).animate();

		var span = BX('bx-disk-total-grid-item');
		if(span)
		{
			BX.adjust(span, {text: '' + (parseInt(span.textContent || span.innerText, 10) - 1)});
		}
	};

	this.getRow = function(objectId)
	{
		var checkbox = this.getCheckbox(objectId);
		return this.getRowByCheckBox(checkbox);
	};

	this.getCheckbox = function(objectId)
	{
		return BX('ID_' + objectId);
	};

	this.getDeleteButton = function()
	{
		return BX('delete_button_'+this.table_id);
	};
}

function BxInterfaceTile(table_id){
	BxInterfaceTile.superclass.constructor.apply(this, arguments);

	this.isTile = true;
	this.tableNode = BX(this.table_id);
	this.tileNode = BX.findChild(this.tableNode, {
		tagName: 'div',
		className: 'bx-disk-interface-tile'
	}, false, false);

	var _this = this;

	this.InitTable = function()
	{
		var items = BX.findChildren(this.tileNode, {
			tagName: 'div',
			className: 'bx-disk-file-container'
		});
		items = items || [];
		for (var j = 0; j < items.length; j++)
		{
			var item = items[j];
			if(item.oncontextmenu)
			{
				BX.bind(item, "contextmenu", this.OnRowContext);
			}
		}
	};

	this.SetActionName = function(actionName)
	{
		var form = this.GetForm();
		if(!form)
			return;

		if(form.elements['action_button_'+this.table_id])
			form.elements['action_button_'+this.table_id].value = actionName;
	};

	this.OnRowContext = function(e)
	{
		if(!_this.menu)
			return;

		if(!e)
			e = window.event;
		if(!phpVars.opt_context_ctrl && e.ctrlKey || phpVars.opt_context_ctrl && !e.ctrlKey)
			return;

		var targetElement = e.target || e.srcElement;

		//column context menu
		var el = targetElement;
		if(!BX.hasClass(el, 'bx-disk-file-container'))
		{
			el = BX.findParent(targetElement, {
				tagName: 'div',
				className: 'bx-disk-file-container'
			}, this.tileNode)
		}

		var menu = _this.menu;
		menu.PopupHide();


		//combined menu
		var menuItems = el.oncontextmenu();
		if(menuItems.length == 0)
			return;

		_this.showNewPopupMenu(e, el.oncontextmenu());
		e.returnValue = false;
		if(e.preventDefault)
			e.preventDefault();
	};

	this.clickAndSelectRow = function(objectId, event)
	{
		var e = event || window.event;
		if(e)
		{
			var target = e.target || e.srcElement;
			if(target.tagName.toUpperCase() == 'A')
			{
				return;
			}
		}
		var checkbox = this.getCheckbox(objectId);
		if(checkbox && checkbox.tagName && checkbox.tagName.toUpperCase() == "INPUT" && checkbox.type.toUpperCase() == "CHECKBOX" && !checkbox.disabled)
		{
			checkbox.checked = !checkbox.checked;
			this.SelectRow(checkbox);
			this.EnableActions();
			BX.PreventDefault(e);
		}
	};

	this.SelectRow = function(checkbox)
	{
		var row = this.getRowByCheckBox(checkbox);
		var span = document.getElementById(this.table_id+'_selected_span');
		var selCount = parseInt(span.innerHTML);

		if(checkbox.checked)
		{
			row.className += ' checked';
			BX.addClass(BX.firstChild(row), 'selected');
			selCount++;
		}
		else
		{
			row.className = row.className.replace(/\s*checked/ig, '');
			BX.removeClass(BX.firstChild(row), 'selected');
			selCount--;
		}
		span.innerHTML = selCount.toString();

		if(checkbox.checked)
		{
			BX.onCustomEvent("onSelectRow", [this, selCount, checkbox]);
		}
		else
		{
			BX.onCustomEvent("onUnSelectRow", [this, selCount, checkbox]);
		}
	};

	this.SelectAllRows = function(bChecked)
	{
		var checkboxes = BX.findChildren(this.tableNode, {
			tagName: 'input',
			attribute: {
				type: 'checkbox'
			}
		}, true);
		if(checkboxes == null)
		{
			return;
		}
		for (var j = 0; j < checkboxes.length; j++)
		{
			var box = checkboxes[j];
			if(box.checked != bChecked && !box.disabled)
			{
				box.checked = bChecked;
				this.SelectRow(box);
			}
		}

		this.EnableActions();
	};

	this.getRowByCheckBox = function(checkbox)
	{
		return checkbox.parentNode.parentNode.parentNode;
	};

	this.ActionEdit = function(a)
	{
		if(this.IsActionEnabled('edit'))
		{
			var form = document.forms['form_'+this.table_id];
			if(!form)
				return;

			//show form buttons
			this.SwitchActionButtons(true);

			//go through rows and show inputs
			var ids = form['ID[]'];
			if(!ids.length)
				ids = new Array(ids);

			for(var i=0; i<ids.length; i++)
			{
				var el = ids[i];
				if(el.checked)
				{
					var row = this.getRowByCheckBox(el);
					var title = BX.findChild(row, {
						tagName: 'a',
						className: 'bx-disk-folder-title'
					}, true);

					if(!title)
					{
						continue;
					}
					BX.denyEvent(title, 'dblclick');
					BX.denyEvent(title, 'click');


					var row_id = el.value;
					this.oSaveData[row_id] = {};

					if(this.oColsMeta['NAME'].editable == true && this.oEditData[row_id]['NAME'] !== false)
					{
						this.oSaveData[row_id]['NAME'] = title.innerHTML;
						title.innerHTML = '';

						//insert controls
						var data = this.oEditData[row_id]['NAME'];
						var name = 'FIELDS['+row_id+']['+'NAME'+']';
						var inputId = 'id-FIELDS-'+row_id+'-'+'NAME'+'';
						if(BX(inputId))
						{
							continue;
						}
						switch(this.oColsMeta['NAME'].type)
						{
							default:
								var props = {
									className: 'bx-disk-folder-title',
									'id': inputId,
									'type':'text',
									'name':name,
									'value':data,
									'size':(this.oColsMeta['NAME'].size? this.oColsMeta['NAME'].size : 15)
								};
								if(this.oColsMeta['NAME'].maxlength)
									props.maxLength = this.oColsMeta['NAME'].maxlength;
								title.parentNode.appendChild(BX.create('INPUT', {'props': props}));
								break;
						}
					}

				}
				el.disabled = true;
			}

			form.elements['action_button_'+this.table_id].value = 'edit';
		}
	};

	this.ActionCancel = function()
	{
		var form = document.forms['form_'+this.table_id];
		if(!form)
			return;

		//hide form buttons
		this.SwitchActionButtons(false);

		//go through rows and restore values
		var ids = form['ID[]'];
		if(!ids.length)
			ids = new Array(ids);

		for(var i=0; i<ids.length; i++)
		{
			var el = ids[i];
			if(el.checked)
			{
				var row = this.getRowByCheckBox(el);
				var title = BX.findChild(row, {
					tagName: 'a',
					className: 'bx-disk-folder-title'
				}, true);
				if(!title)
				{
					continue;
				}

				BX.allowEvent(title, 'dblclick');
				BX.allowEvent(title, 'click');

				var row_id = el.value;
				if(this.oColsMeta['NAME'].editable == true && this.oEditData[row_id]['NAME'] !== false)
				{
					BX.adjust(title, {text: this.oSaveData[row_id]['NAME']});
					var inputId = 'id-FIELDS-'+row_id+'-'+'NAME'+'';
					BX.remove(BX(inputId));
				}
			}
			el.disabled = false;
		}

		form.elements['action_button_'+this.table_id].value = '';
	};

}

BX.extend(BxInterfaceTile, BxDiskInterfaceGrid);
