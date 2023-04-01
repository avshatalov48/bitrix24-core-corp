function BxCrmInterfaceForm(name, aTabs)
{
	var _this = this;
	this.name = name; // is form ID
	this.aTabs = aTabs;
	this.bExpandTabs = false;
	this.vars = {};
	this.oTabsMeta = {};
	this.aTabsEdit = [];
	this.oFields = {};
	this.menu = new PopupMenu('bxFormMenu_'+this.name, 1010);
	this.settingsMenu = [];
	this.tabSettingsWnd = null;
	this.fieldSettingsWnd = null;
	this.activeTabClass = 'bx-crm-view-tab-active';
	this._form = null;
	this._isSubmitted = false;
	this._enableSigleSubmit = true;
	this._submitConditionsChecked = true;

	this.isVisibleInViewMode = true;
	var container = BX("container_" + this.name.toLowerCase());
	if(container)
	{
		this.isVisibleInViewMode = container.style.display !== "none";
	}

	this.Initialize = function()
	{
		this._form = BX("form_" + this.name);
		if(this._enableSigleSubmit)
		{
			this._submitHandler = BX.delegate(this._OnSubmit, this);
			BX.bind(this._form, 'submit', this._submitHandler);
		}

		BX.onCustomEvent(window, 'CrmInterfaceFormCreated', [ this ]);
	};

	this.EnableSigleSubmit = function(enable)
	{
		enable = !!enable;
		if(this._enableSigleSubmit === enable)
		{
			return;
		}

		if(this._enableSigleSubmit && this._submitHandler)
		{
			BX.unbind(this._form, 'submit', this._submitHandler);
			this._submitHandler = null;
		}

		this._enableSigleSubmit = enable;

		if(this._enableSigleSubmit)
		{
			this._submitHandler = BX.delegate(this._OnSubmit, this);
			BX.bind(this._form, 'submit', this._submitHandler);
		}
	};

	this.GetForm = function()
	{
		return this._form;
	};

	this._OnSubmit = function(e)
	{
		if(!this._enableSigleSubmit)
		{
			return true;
		}

		BX.onCustomEvent(this, "OnSubmitConditionsCheck", [this]);

		if (!this.IsSubmitConditionsChecked())
		{
			return true;
		}

		if(this._isSubmitted)
		{
			return BX.PreventDefault(e);
		}

		this._isSubmitted = true;
		window.setTimeout(BX.delegate(this._LockSubmits, this), 10);
		return true;
	};

	this._LockSubmits = function()
	{
		var saveAndViewBtn = BX(this.name + "_saveAndView");
		if(saveAndViewBtn)
		{
			saveAndViewBtn.disabled = "disabled";
		}

		var saveAndAddBtn = BX(this.name + "_saveAndAdd");
		if(saveAndAddBtn)
		{
			saveAndAddBtn.disabled = "disabled";
		}

		var applyBtn = BX(this.name + "_apply");
		if(applyBtn)
		{
			applyBtn.disabled = "disabled";
		}
	};

	this.GetTabs = function()
	{
		var tabs = BX.findChildren(
			BX(this.name + '_tab_block'),
			{ "tagName": "a", "className": "bx-crm-view-tab" },
			false
		);
		return tabs ? tabs : [];
	};

	this.GetActiveTabId = function()
	{
		var tabs = this.GetTabs();
		for(var i = 0; i < tabs.length; i++)
		{
			var tab = tabs[i];
			if(BX.hasClass(tab, this.activeTabClass))
			{
				return tab.id.substring((this.name + '_tab_').length);
			}
		}

		return '';
	};

	this.GetActiveTabContainer = function()
	{
		return this.GetTabContainer(this.GetActiveTabId());
	};

	this.ShowOnDemand = function(caller)
	{
		var sectionContainer = BX.findParent(caller, { 'tagName':'DIV', 'className':'bx-crm-view-fieldset' });
		var rows = BX.findChildren(sectionContainer, { 'tagName':'tr', 'className':'bx-crm-view-on-demand' }, true);

		if(!BX.type.isArray(rows))
		{
			return;
		}

		for(var i = 0; i < rows.length; i++)
		{
			rows[i].style.display = '';
		}

		if(caller)
		{
			BX.findParent(caller, { 'tagName':'tr', 'className':'bx-crm-view-show-more' }).style.display='none';
		}
	};

	this.GetTabContainer = function(tab_id)
	{
		return BX.type.isNotEmptyString(tab_id) ? BX('inner_tab_' + tab_id) : null;
	};

	this.SelectTab = function(tab_id)
	{
		var div = BX('inner_tab_' + tab_id);

		if(!div || div.style.display != 'none')
			return;

		for (var i = 0, cnt = this.aTabs.length; i < cnt; i++)
		{
			var tab = BX('inner_tab_'+this.aTabs[i]);
			if(!tab)
				continue;

			if(tab.style.display != 'none')
			{
				this.ShowTab(this.aTabs[i], false);
				tab.style.display = 'none';
				break;
			}
		}

		this.ShowTab(tab_id, true);
		div.style.display = 'block';

		var hidden = BX(this.name+'_active_tab');
		if(hidden)
			hidden.value = tab_id;

		BX.onCustomEvent(
			window,
			'BX_CRM_INTERFACE_FORM_TAB_SELECTED',
			[this, this.name, tab_id, div]
		);
	};

	this.ShowTab = function(tab_id, on)
	{
		var id = this.name + '_tab_' + tab_id;
		var tabs = this.GetTabs();
		for(var i = 0; i < tabs.length; i++)
		{
			var tab = tabs[i];
			if(id !== tab.id)
			{
				continue;
			}

			if(on)
			{
				BX.addClass(tab, 'bx-crm-view-tab-active');
				BX.onCustomEvent(this, 'OnTabShow', [ tab_id ]);
			}
			else
			{
				BX.removeClass(tab, 'bx-crm-view-tab-active');
				BX.onCustomEvent(this, 'OnTabHide', [ tab_id ]);
			}

			break;
		}
	};

	this.HoverTab = function(tab_id, on)
	{
		var tab = document.getElementById('tab_'+tab_id);
		if(tab.className == 'bx-tab-selected')
			return;

		document.getElementById('tab_left_'+tab_id).className = (on? 'bx-tab-left-hover':'bx-tab-left');
		tab.className = (on? 'bx-tab-hover':'bx-tab');
		var tab_right = document.getElementById('tab_right_'+tab_id);
		tab_right.className = (on? 'bx-tab-right-hover':'bx-tab-right');
	};

	this.ShowDisabledTab = function(tab_id, disabled)
	{
		var tab = document.getElementById('tab_cont_'+tab_id);
		if(disabled)
		{
			tab.className = 'bx-tab-container-disabled';
			tab.onclick = null;
			tab.onmouseover = null;
			tab.onmouseout = null;
		}
		else
		{
			tab.className = 'bx-tab-container';
			tab.onclick = function(){_this.SelectTab(tab_id);};
			tab.onmouseover = function(){_this.HoverTab(tab_id, true);};
			tab.onmouseout = function(){_this.HoverTab(tab_id, false);};
		}
	};

	this.ToggleTabs = function(bSkipSave)
	{
		this.bExpandTabs = !this.bExpandTabs;

		var a = document.getElementById('bxForm_'+this.name+'_expand_link');
		a.title = (this.bExpandTabs? this.vars.mess.collapseTabs : this.vars.mess.expandTabs);
		a.className = (this.bExpandTabs? a.className.replace(/\s*bx-down/ig, ' bx-up') : a.className.replace(/\s*bx-up/ig, ' bx-down'));

		var div;
		for(var i in this.aTabs)
		{
			var tab_id = this.aTabs[i];
			this.ShowTab(tab_id, false);
			this.ShowDisabledTab(tab_id, this.bExpandTabs);
			div = document.getElementById('inner_tab_'+tab_id);
			div.style.display = (this.bExpandTabs? 'block':'none');
		}
		if(!this.bExpandTabs)
		{
			this.ShowTab(this.aTabs[0], true);
			div = document.getElementById('inner_tab_'+this.aTabs[0]);
			div.style.display = 'block';
		}
		if(bSkipSave !== true)
			BX.ajax.get('/bitrix/components'+this.vars.component_path+'/settings.php?FORM_ID='+this.name+'&action=expand&expand='+(this.bExpandTabs? 'Y':'N')+'&sessid='+this.vars.sessid);
	};

	this.SetTheme = function(menuItem, theme)
	{
		BX.loadCSS(this.vars.template_path+'/themes/'+theme+'/style.css');

		var themeMenu = this.menu.GetMenuByItemId(menuItem.id);
		themeMenu.SetAllItemsIcon('');
		themeMenu.SetItemIcon(menuItem, 'checked');

		BX.ajax.get('/bitrix/components'+_this.vars.component_path+'/settings.php?FORM_ID='+this.name+'&GRID_ID='+this.vars.GRID_ID+'&action=settheme&theme='+theme+'&sessid='+this.vars.sessid);
	};

	this.ShowSettings = function()
	{
		var bCreated = false;
		if(!window['formSettingsDialog'+this.name])
		{
			window['formSettingsDialog'+this.name] = new BX.CDialog({
				'content':'<form name="form_settings_'+this.name+'"></form>',
				'title': this.vars.mess.settingsTitle,
				'width': this.vars.settingWndSize.width,
				'height': this.vars.settingWndSize.height,
				'resize_id': 'InterfaceFormSettingWnd'
			});
			bCreated = true;
		}

		window['formSettingsDialog'+this.name].ClearButtons();
		window['formSettingsDialog'+this.name].SetButtons([
			{
				'title': this.vars.mess.settingsSave,
				'action': function()
				{
					_this.SaveSettings();
					this.parentWindow.Close();
				}
			},
			BX.CDialog.prototype.btnCancel
		]);

		window['formSettingsDialog'+this.name].Show();

		var form = document['form_settings_'+this.name];

		if(bCreated)
			form.appendChild(BX('form_settings_'+this.name));

		//editable data
		var i;
		this.aTabsEdit = [];
		for(i in this.oTabsMeta)
		{
			var fields = [];
			for(var j in this.oTabsMeta[i].fields)
				fields[fields.length] = BX.clone(this.oTabsMeta[i].fields[j]);
			this.aTabsEdit[this.aTabsEdit.length] = BX.clone(this.oTabsMeta[i]);
			this.aTabsEdit[this.aTabsEdit.length-1].fields = fields;
		}

		//tabs
		jsSelectUtils.deleteAllOptions(form.tabs);
		for(i in this.aTabsEdit)
			form.tabs.options[form.tabs.length] = new Option(this.aTabsEdit[i].name, this.aTabsEdit[i].id, false, false);

		//fields
		form.tabs.selectedIndex = 0;
		this.OnSettingsChangeTab();

		//available fields
		this.aAvailableFields = BX.clone(this.oFields);
		jsSelectUtils.deleteAllOptions(form.all_fields);
		for(i in this.aAvailableFields)
			form.all_fields.options[form.all_fields.length] = new Option(this.aAvailableFields[i].name, this.aAvailableFields[i].id, false, false);

		jsSelectUtils.sortSelect(form.all_fields);

		this.HighlightSections(form.all_fields);

		this.ProcessButtons();

		form.tabs.focus();
	};

	this.OnSettingsChangeTab = function()
	{
		var form = document['form_settings_'+this.name];
		var index = form.tabs.selectedIndex;

		jsSelectUtils.deleteAllOptions(form.fields);
		for(var i in this.aTabsEdit[index].fields)
		{
			var opt = new Option(this.aTabsEdit[index].fields[i].name, this.aTabsEdit[index].fields[i].id, false, false);
			if(this.aTabsEdit[index].fields[i].type == 'section')
				opt.className = 'bx-section';
			form.fields.options[form.fields.length] = opt;
		}

		this.ProcessButtons();
	};

	this.TabMoveUp = function()
	{
		var form = document['form_settings_'+this.name];
		var index = form.tabs.selectedIndex;

		if(index > 0)
		{
			var tab1 = BX.clone(this.aTabsEdit[index]);
			var tab2 = BX.clone(this.aTabsEdit[index-1]);
			this.aTabsEdit[index] = tab2;
			this.aTabsEdit[index-1] = tab1;
		}
		jsSelectUtils.moveOptionsUp(form.tabs);
	};

	this.TabMoveDown = function()
	{
		var form = document['form_settings_'+this.name];
		var index = form.tabs.selectedIndex;

		if(index < form.tabs.length-1)
		{
			var tab1 = BX.clone(this.aTabsEdit[index]);
			this.aTabsEdit[index] = BX.clone(this.aTabsEdit[index+1]);
			this.aTabsEdit[index+1] = tab1;
		}
		jsSelectUtils.moveOptionsDown(form.tabs);
	};

	this.TabEdit = function()
	{
		var form = document['form_settings_'+this.name];
		var tabIndex = form.tabs.selectedIndex;

		if(tabIndex < 0)
			return;

		this.ShowTabSettings(this.aTabsEdit[tabIndex],
			function()
			{
				var frm = document['tab_settings_'+_this.name];
				_this.aTabsEdit[tabIndex].name = frm.tab_name.value;
				_this.aTabsEdit[tabIndex].title = frm.tab_title.value;

				form.tabs[tabIndex].text = frm.tab_name.value;
			}
		);
	};

	this.TabAdd = function()
	{
		this.ShowTabSettings({'name':'', 'title':''},
			function()
			{
				var tab_id = 'tab_'+Math.round(Math.random()*1000000);

				var frm = document['tab_settings_'+_this.name];
				_this.aTabsEdit[_this.aTabsEdit.length] = {
					'id': tab_id,
					'name': frm.tab_name.value,
					'title': frm.tab_title.value,
					'fields': []
				};

				var form = document['form_settings_'+_this.name];
				form.tabs[form.tabs.length] = new Option(frm.tab_name.value, tab_id, true, true);
				_this.OnSettingsChangeTab();
			}
		);
	};

	this.TabDelete = function()
	{
		var form = document['form_settings_'+this.name];
		var tabIndex = form.tabs.selectedIndex;

		if(tabIndex < 0)
			return;

		//place to available fields before delete
		var i;
		for(i in this.aTabsEdit[tabIndex].fields)
		{
			this.aAvailableFields[this.aTabsEdit[tabIndex].fields[i].id] = this.aTabsEdit[tabIndex].fields[i];
			jsSelectUtils.addNewOption(form.all_fields, this.aTabsEdit[tabIndex].fields[i].id, this.aTabsEdit[tabIndex].fields[i].name, true, false);
		}

		this.HighlightSections(form.all_fields);

		this.aTabsEdit = BX.util.deleteFromArray(this.aTabsEdit, tabIndex);
		form.tabs.remove(tabIndex);

		if(form.tabs.length > 0)
		{
			i = (tabIndex < form.tabs.length? tabIndex : form.tabs.length-1);
			form.tabs[i].selected = true;
			this.OnSettingsChangeTab();
		}
		else
		{
			jsSelectUtils.deleteAllOptions(form.fields);
			this.ProcessButtons();
		}
	};

	this.ShowTabSettings = function(data, action)
	{
		var wnd = this.tabSettingsWnd;
		if(!wnd)
		{
			this.tabSettingsWnd = wnd = new BX.CDialog({
				'content':'<form name="tab_settings_'+this.name+'">'+
					'<table width="100%">'+
					'<tr>'+
					'<td width="50%" align="right">'+this.vars.mess.tabSettingsName+'</td>'+
					'<td><input type="text" name="tab_name" size="30" value="" style="width:90%"></td>'+
					'</tr>'+
					'<tr>'+
					'<td align="right">'+this.vars.mess.tabSettingsCaption+'</td>'+
					'<td><input type="text" name="tab_title" size="30" value="" style="width:90%"></td>'+
					'</tr>'+
					'</table>'+
					'</form>',
				'title': this.vars.mess.tabSettingsTitle,
				'width': this.vars.tabSettingWndSize.width,
				'height': this.vars.tabSettingWndSize.height,
				'resize_id': 'InterfaceFormTabSettingWnd'
			});
		}
		wnd.ClearButtons();
		wnd.SetButtons([
			{
				'title': this.vars.mess.tabSettingsSave,
				'action': function(){
					action();
					this.parentWindow.Close();
				}
			},
			BX.CDialog.prototype.btnCancel
		]);
		wnd.Show();

		var form = document['tab_settings_'+this.name];
		form.tab_name.value = data.name;
		form.tab_title.value = data.title;
		form.tab_name.focus();
	};

	this.ShowFieldSettings = function(data, action)
	{
		var wnd = this.fieldSettingsWnd;
		if(!wnd)
		{
			this.fieldSettingsWnd = wnd = new BX.CDialog({
				'content':'<form name="field_settings_'+this.name+'">'+
					'<table width="100%">'+
					'<tr>'+
					'<td width="50%" align="right" id="field_name_'+this.name+'"></td>'+
					'<td><input type="text" name="field_name" size="30" value="" style="width:90%"></td>'+
					'</tr>'+
					'</table>'+
					'</form>',
				'width': this.vars.fieldSettingWndSize.width,
				'height': this.vars.fieldSettingWndSize.height,
				'resize_id': 'InterfaceFormFieldSettingWnd'
			});
		}

		wnd.SetTitle(data.type && data.type == 'section'? this.vars.mess.sectSettingsTitle : this.vars.mess.fieldSettingsTitle);
		BX('field_name_'+this.name).innerHTML = (data.type && data.type == 'section'? this.vars.mess.sectSettingsName : this.vars.mess.fieldSettingsName);

		wnd.ClearButtons();
		wnd.SetButtons([
			{
				'title': this.vars.mess.tabSettingsSave,
				'action': function(){
					action();
					this.parentWindow.Close();
				}
			},
			BX.CDialog.prototype.btnCancel
		]);
		wnd.Show();

		var form = document['field_settings_'+this.name];
		form.field_name.value = data.name;
		form.field_name.focus();
	};

	this.FieldEdit = function()
	{
		var form = document['form_settings_'+this.name];
		var tabIndex = form.tabs.selectedIndex;
		var fieldIndex = form.fields.selectedIndex;

		if(tabIndex < 0 || fieldIndex < 0)
			return;

		this.ShowFieldSettings(this.aTabsEdit[tabIndex].fields[fieldIndex],
			function()
			{
				var frm = document['field_settings_'+_this.name];
				_this.aTabsEdit[tabIndex].fields[fieldIndex].name = frm.field_name.value;

				form.fields[fieldIndex].text = frm.field_name.value;
			}
		);
	};

	this.FieldAdd = function()
	{
		var form = document['form_settings_'+this.name];
		var tabIndex = form.tabs.selectedIndex;

		if(tabIndex < 0)
			return;

		this.ShowFieldSettings({'name':'', 'type':'section'},
			function()
			{
				var field_id = 'field_'+Math.round(Math.random()*1000000);
				var frm = document['field_settings_'+_this.name];
				_this.aTabsEdit[tabIndex].fields[_this.aTabsEdit[tabIndex].fields.length] = {
					'id': field_id,
					'name': frm.field_name.value,
					'type': 'section'
				};
				var opt = new Option(frm.field_name.value, field_id, true, true);
				opt.className = 'bx-section';
				form.fields[form.fields.length] = opt;
				_this.ProcessButtons();
			}
		);
	};

	this.FieldsMoveUp = function()
	{
		var form = document['form_settings_'+this.name];
		var tabIndex = form.tabs.selectedIndex;

		var n = form.fields.length;
		for(var i=0; i<n; i++)
		{
			if(form.fields[i].selected && i>0 && form.fields[i-1].selected == false)
			{
				var field1 = BX.clone(this.aTabsEdit[tabIndex].fields[i]);
				this.aTabsEdit[tabIndex].fields[i] = BX.clone(this.aTabsEdit[tabIndex].fields[i-1]);
				this.aTabsEdit[tabIndex].fields[i-1] = field1;

				var option1 = new Option(form.fields[i].text, form.fields[i].value);
				var option2 = new Option(form.fields[i-1].text, form.fields[i-1].value);
				option1.className = form.fields[i].className;
				option2.className = form.fields[i-1].className;
				form.fields[i] = option2;
				form.fields[i].selected = false;
				form.fields[i-1] = option1;
				form.fields[i-1].selected = true;
			}
		}
	};

	this.FieldsMoveDown = function()
	{
		var form = document['form_settings_'+this.name];
		var tabIndex = form.tabs.selectedIndex;

		var n = form.fields.length;
		for(var i=n-1; i>=0; i--)
		{
			if(form.fields[i].selected && i<n-1 && form.fields[i+1].selected == false)
			{
				var field1 = BX.clone(this.aTabsEdit[tabIndex].fields[i]);
				this.aTabsEdit[tabIndex].fields[i] = BX.clone(this.aTabsEdit[tabIndex].fields[i+1]);
				this.aTabsEdit[tabIndex].fields[i+1] = field1;

				var option1 = new Option(form.fields[i].text, form.fields[i].value);
				var option2 = new Option(form.fields[i+1].text, form.fields[i+1].value);
				option1.className = form.fields[i].className;
				option2.className = form.fields[i+1].className;
				form.fields[i] = option2;
				form.fields[i].selected = false;
				form.fields[i+1] = option1;
				form.fields[i+1].selected = true;
			}
		}
	};

	this.FieldsAdd = function()
	{
		var form = document['form_settings_'+this.name];
		var tabIndex = form.tabs.selectedIndex;

		if(tabIndex == -1)
			return;

		var fields = this.aTabsEdit[tabIndex].fields;

		var n = form.all_fields.length, i;
		for(i=0; i<n; i++)
			if(form.all_fields[i].selected)
				fields[fields.length] = {
					'id': form.all_fields[i].value,
					'name': form.all_fields[i].text,
					'type': this.aAvailableFields[form.all_fields[i].value].type
				};

		jsSelectUtils.addSelectedOptions(form.all_fields, form.fields, false, false);
		jsSelectUtils.deleteSelectedOptions(form.all_fields);

		for(i=0, n=form.fields.length; i<n; i++)
			if(fields[i].type == 'section')
				form.fields[i].className = 'bx-section';

		this.ProcessButtons();
	};

	this.FieldsDelete = function()
	{
		var form = document['form_settings_'+this.name];
		var tabIndex = form.tabs.selectedIndex;

		if(tabIndex == -1)
			return;

		var n = form.fields.length;
		var delta = 0;
		for(var i=0; i<n; i++)
		{
			if(form.fields[i].selected)
			{
				this.aAvailableFields[form.fields[i].value] = this.aTabsEdit[tabIndex].fields[i-delta];
				this.aTabsEdit[tabIndex].fields = BX.util.deleteFromArray(this.aTabsEdit[tabIndex].fields, i-delta);
				delta++;
			}
		}

		jsSelectUtils.addSelectedOptions(form.fields, form.all_fields, false, true);
		jsSelectUtils.deleteSelectedOptions(form.fields);

		this.HighlightSections(form.all_fields);

		this.ProcessButtons();
	};

	this.ProcessButtons = function()
	{
		var form = document['form_settings_'+this.name];

		form.add_btn.disabled = (form.all_fields.selectedIndex == -1 || form.tabs.selectedIndex == -1);
		form.del_btn.disabled = form.up_btn.disabled = form.down_btn.disabled = form.field_edit_btn.disabled = (form.fields.selectedIndex == -1);
		form.tab_up_btn.disabled = form.tab_down_btn.disabled = form.tab_edit_btn.disabled = form.tab_del_btn.disabled = form.field_add_btn.disabled = (form.tabs.selectedIndex == -1);
	};

	this.HighlightSections = function(el)
	{
		for(var i=0, n=el.length; i<n; i++)
			if(this.aAvailableFields[el[i].value].type == 'section')
				el[i].className = 'bx-section';
	};

	this.SaveSettings = function()
	{
		var data = {
			'FORM_ID': this.name,
			'action': 'savesettings',
			'sessid': this.vars.sessid,
			'tabs': this.aTabsEdit
		};
		var form = document['form_settings_'+this.name];
		if(form && form['set_default_settings'])
		{
			data.set_default_settings = (form.set_default_settings.checked? 'Y':'N');
			data.delete_users_settings = (form.delete_users_settings.checked? 'Y':'N');
		}
		BX.ajax.post('/bitrix/components'+_this.vars.component_path+'/settings.php', data, function(){_this.Reload()});
	};

	this.SaveSettings = function(options)
	{
		if(!BX.type.isPlainObject(options))
		{
			options = {};
		}

		var callback = BX.type.isFunction(options['callback']) ? options['callback'] : null;
		var data =
			{
				'FORM_ID': this.name,
				'action': 'savesettings',
				'sessid': this.vars.sessid,
				'tabs': this.aTabsEdit
			};

		var form = document['form_settings_'+this.name];
		if(form && form['set_default_settings'])
		{
			data['set_default_settings'] = (form.set_default_settings.checked? 'Y':'N');
			data['delete_users_settings'] = (form.delete_users_settings.checked? 'Y':'N');
		}
		else
		{
			if(BX.type.isBoolean(options['setDefaultSettings']))
			{
				data['set_default_settings'] = options['setDefaultSettings'] ? 'Y' : 'N';
			}

			if(BX.type.isBoolean(options['deleteUserSettings']))
			{
				data['delete_users_settings'] = options['deleteUserSettings'] ? 'Y' : 'N';
			}
		}

		var url = '/bitrix/components' + _this.vars.component_path + '/settings.php';
		if(callback)
		{
			BX.ajax.post(url, data, callback);
		}
		else
		{
			BX.ajax.post(url, data, function(){ _this.Reload(); });
		}
	};

	this.EnableSettings = function(enabled, callback)
	{
		var url = '/bitrix/components' + this.vars.component_path + '/settings.php?FORM_ID=' + this.name + '&action=enable&enabled=' + (enabled? 'Y':'N') + '&sessid=' + this.vars.sessid;

		if(BX.type.isFunction(callback))
		{
			BX.ajax.get(url, callback);
		}
		else
		{
			BX.ajax.get(url, function(){ _this.Reload(); });
		}
	};
	this.Reload = function()
	{
		var ajaxId = this.vars.ajax.AJAX_ID;
		if(ajaxId != '')
		{
			var url = BX.util.remove_url_param(this.vars.current_url, 'bxajaxid');
			if(url[url.length - 1] === '?')
			{
				//remove_url_param fix
				url = url.substr(0, url.length - 1);
			}
			BX.ajax.insertToNode(url + (url.indexOf('?') < 0 ? '?' : '&') + 'bxajaxid=' + ajaxId, 'comp_' + ajaxId);
		}
		else
		{
			window.location = window.location.href;
		}
	};
	this.ReloadActiveTab = function()
	{
		var tabParamName = this.name + '_active_tab';
		var url = BX.util.remove_url_param(this.vars.current_url, tabParamName);
		if(url[url.length - 1] === '?')
		{
			//remove_url_param fix
			url = url.substr(0, url.length - 1);
		}

		url += (url.indexOf('?') < 0 ? '?' : '&') +  tabParamName + '=' + this.GetActiveTabId();

		var ajaxId = this.vars.ajax.AJAX_ID;
		if(ajaxId != '')
		{
			BX.ajax.insertToNode(url + '&bxajaxid=' + ajaxId, 'comp_' + ajaxId);
		}
		else
		{
			window.location = url;
		}
	};
	this.SetViewModeVisibility = function(visible)
	{
		visible = !!visible;
		if(this.isVisibleInViewMode === visible)
		{
			return;
		}

		this.isVisibleInViewMode = visible;

		var container = BX("container_" + this.name.toLowerCase());
		if(container)
		{
			container.style.display = this.isVisibleInViewMode ? "" : "none";
		}

		BX.userOptions.save("main.interface.form", this.name, "show_in_view_mode", visible ? "Y" : "N", false);
	};
	this.IsSubmitConditionsChecked = function()
	{
		return this._submitConditionsChecked;
	};
	this.SetSubmitConditionsFlag = function(checked)
	{
		this._submitConditionsChecked = !!checked;
	};
}

BX.CmrSidebarFieldSelector = function()
{
	this._id = '';
	this._fieldId = '';
	this._currentItem = null;
	this._elem = null;
	this._settings = {};
	this._items = {};
	this._popupMenu = null;
};

BX.CmrSidebarFieldSelector.prototype =
{
	initialize: function(id, fieldId, elem, settings)
	{
		this._id = id;
		this._fieldId = fieldId;
		this._elem = elem;
		this._settings = settings;

		this._items = {};
		var opts = this.getSettings('options', null);
		if(opts)
		{
			for(var i = 0; i < opts.length; i++)
			{
				var opt = opts[i];
				if(BX.type.isNotEmptyString(opt['id']))
				{
					var optId = opt['id'];
					this._items[optId] = BX.CmrSidebarFieldSelectorItem.create(optId, this, { "text": BX.type.isNotEmptyString(opt['caption']) ? opt['caption'] : optId });
				}
			}
		}

		BX.bind(this._elem, 'click', BX.proxy(this._onElementClick, this));

		var button = BX(this.getSettings('buttonId', ''));
		if(button)
		{
			BX.bind(button, 'click', BX.proxy(this._onElementClick, this));
		}
	},
	getSettings: function(name, defaultval)
	{
		var s = this._settings;
		return  s[name] ? s[name] : defaultval;
	},
	getFieldId: function()
	{
		return this._fieldId;
	},
	getCurrentItem: function()
	{
		return this._currentItem;
	},
	setCurrentItemId: function(itemId, save)
	{
		var item = null;
		for(var key in this._items)
		{
			if(!this._items.hasOwnProperty(key))
			{
				continue;
			}

			if(this._items[key].getId() === itemId)
			{
				item = this._items[key];
			}
		}

		if(!item)
		{
			return;
		}

		this._currentItem = item;
		if(this._elem)
		{
			this._elem.innerHTML = item.getTitle();
		}

		save = !!save;
		if(save)
		{
			var editor = BX.CrmInstantEditor.getDefault();
			if(editor)
			{
				editor.saveFieldValue(this._fieldId, item.getId());
			}

			BX.CmrSidebarFieldSelector._synchronize(this);
		}

	},
	_onElementClick: function(e)
	{
		var menuItems = [];
		for(var key in this._items)
		{
			if(!this._items.hasOwnProperty(key))
			{
				continue;
			}

			var item = this._items[key].createMenuItem();
			if(item)
			{
				menuItems.push(item);
			}
		}

		BX.PopupMenu.show(
			this._id,
			this._elem,
			menuItems,
			{ "offsetTop": 0, "offsetLeft": 0 }
		);

		this._popupMenu = BX.PopupMenu.currentItem;
	},
	handleItemChange: function(item)
	{
		if(this._popupMenu && this._popupMenu.popupWindow)
		{
			this._popupMenu.popupWindow.close();
		}

		this.setCurrentItemId(item.getId(), true);
	}
};
BX.CmrSidebarFieldSelector.items = {};
BX.CmrSidebarFieldSelector.create = function(id, fieldId, elem, settings)
{
	var self = new BX.CmrSidebarFieldSelector();
	self.initialize(id, fieldId, elem, settings);
	this.items[id] = self;
	return self;
};

BX.CmrSidebarFieldSelector._synchronize = function(item)
{
	//var type = item.getRegistryEntityType();
	//var id = item.getEntityId();

	var selectedItem = item.getCurrentItem();
	if(!selectedItem)
	{
		return;
	}

	var fieldId = item.getFieldId();
	for(var itemId in this.items)
	{
		if(!this.items.hasOwnProperty(itemId))
		{
			continue;
		}

		var curItem = this.items[itemId];
		if(curItem === item)
		{
			continue;
		}

		if(fieldId === curItem.getFieldId())
		{
			curItem.setCurrentItemId(selectedItem.getId(), false);
		}
	}
};

BX.CmrSidebarFieldSelectorItem = function()
{
	this._id = '';
	this._parent = null;
	this._settings = {};
};

BX.CmrSidebarFieldSelectorItem.prototype =
{
	initialize: function(id, parent, settings)
	{
		this._id = id;
		this._parent = parent;
		this._settings = settings;
	},
	getSettings: function(name, defaultval)
	{
		var s = this._settings;
		return  s[name] ? s[name] : defaultval;

	},
	getId: function()
	{
		return this._id;
	},
	getTitle: function()
	{
		return this.getSettings('text', this._id);
	},
	createMenuItem: function()
	{
		return {
			"text":  this.getTitle(),
			"onclick": BX.proxy(this._onMenuItemClick, this)
		};
	},
	_onMenuItemClick: function()
	{
		if(this._parent)
		{
			this._parent.handleItemChange(this);
		}
	}
};

BX.CmrSidebarFieldSelectorItem.create = function(id, parent, settings)
{
	var self = new BX.CmrSidebarFieldSelectorItem();
	self.initialize(id, parent, settings);
	return self;
};

BX.CrmSidebarUserSelector = function()
{
	this._id = '';
	this._settings = {};
	this._button = null;
	this._container = null;
	this.componentName = '';
	this._componentContainer = null;
	this._componentObj = null;
	this._fieldId = '';
	this._editor = null;
	this._dlg = null;
	this._dlgDisplayed = false;
	this._userInfo = null;
	this._userInfoProvider = null;
	this._enableLazyLoad = false;
	this._isLoaded = false;
	this._serviceUrl = '';
	this._options = {};
	this._userSelectorScriptLoaded = null;
};

BX.CrmSidebarUserSelector.prototype =
{
	initialize: function(id, button, container, componentName, options)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : ('crm_sidebar_user_sel_' + Math.random());
		if(!BX.type.isElementNode(button))
		{
			throw 'BX.CrmSidebarUserSelector: button is not defined';
		}

		this._button = button;

		if(!BX.type.isElementNode(container))
		{
			throw 'BX.CrmSidebarUserSelector: container is not defined';
		}

		this._container = container;

		if(!BX.type.isNotEmptyString(componentName))
		{
			throw 'BX.CrmSidebarUserSelector: componentName is not defined';
		}
		this.componentName = componentName;

		this._options = options ? options : {};
		this._enableLazyLoad = this.getOption('enableLazyLoad', false);
		this._serviceUrl = this.getOption('serviceUrl', '');

		if(!this._enableLazyLoad)
		{
			this._componentContainer = BX(componentName + '_selector_content');
			var objName = 'O_' + componentName;
			if(window[objName])
			{
				this._componentObj = window[objName];
				this._componentObj.onSelect = BX.delegate(this._handleUserSelect, this);
				this._isLoaded = true;
			}
		}

		BX.bind(this._button, 'click', BX.delegate(this._handleButtonClick, this));

		this._fieldId = this.getStringOption('fieldId');
		this._userInfoProvider = BX.CrmUserInfoProvider.getItemById(this.getStringOption('userInfoProviderId'));

		if(this._fieldId !== '')
		{
			var editorId = this.getOption('editorId', '');
			if(editorId !== '')
			{
				var editor = BX.CrmInstantEditor.items[editorId];
				if(editor)
				{
					this._setupEditor(editor);
				}
				else
				{
					BX.addCustomEvent(
						'CrmInstantEditorCreated',
						BX.delegate(this._handleEditorCreation, this)
					);
				}
			}
		}
	},
	openDialog: function()
	{
		this._dlg = new BX.PopupWindow(
			this._id,
			this._button,
			{
				autoHide: true,
				draggable: false,
				closeByEsc: true,
				offsetLeft: 0,
				offsetTop: 0,
				bindOptions: { forceBindPosition: true },
				content : this._componentContainer,
				events:
				{
					onPopupShow: BX.delegate(
						function()
						{
							this._dlgDisplayed = true;
						},
						this
					),
					onPopupClose: BX.delegate(
						function()
						{
							this._dlgDisplayed = false;
							this._dlg.destroy();
						},
						this
					),
					onPopupDestroy: BX.delegate(
						function()
						{
							this._dlg = null;
						},
						this
					)
				}
			}
		);

		this._dlg.show();
	},
	closeDialog: function()
	{
		if(this._dlg)
		{
			this._dlg.close();
		}
	},
	getSetting: function(name, defaultval)
	{
		return this._settings[name] ? this._settings[name] : defaultval;
	},
	getOption: function(name, defaultval)
	{
		return this._options.hasOwnProperty(name) ? this._options[name] : defaultval;
	},
	getStringOption: function(name)
	{
		return BX.type.isNotEmptyString(this._options[name]) ? this._options[name] : '';
	},
	layout: function()
	{
		this._container.href = this._userInfo ? this._userInfo.getProfileUrl() : '#';
		var nameElem = BX.findChild(this._container, { className: "crm-detail-info-resp-name" }, true, false);
		if(nameElem)
		{
			nameElem.innerHTML = BX.util.htmlspecialchars(
				this._userInfo ? this._userInfo.getFullName() : ''
			);
		}

		var postElem = BX.findChild(this._container, { className: "crm-detail-info-resp-descr" }, true, false);
		if(postElem)
		{
			postElem.innerHTML = BX.util.htmlspecialchars(
				this._userInfo ? this._userInfo.getWorkPosition() : ''
			);
		}

		var photoElem = BX.findChild(this._container, { className: "crm-detail-info-resp-img" }, true, false);
		if(photoElem)
		{
			BX.cleanNode(photoElem, false);
			photoElem.appendChild(
				BX.create("IMG",
					{ attrs: { height: "38", width: "38", src: this._userInfo ? this._userInfo.getPhotoUrl() : '' } }
				)
			);
		}
	},
	toggleDialog: function()
	{
		if(this._dlg && this._dlgDisplayed)
		{
			this.closeDialog();
		}
		else
		{
			this.openDialog();
		}
	},
	_handleButtonClick: function()
	{
		if(this._isLoaded)
		{
			this.toggleDialog();
			return;
		}

		if(this._enableLazyLoad && this._serviceUrl !== "")
		{
			this._userSelectorScriptLoaded = BX.delegate(this._handleUserSelectorScriptLoaded, this);
			BX.addCustomEvent("onAjaxSuccessFinish", this._userSelectorScriptLoaded);
			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "html",
					data:
					{
						"MODE": "GET_USER_SELECTOR",
						"NAME": this.componentName
					},
					onsuccess: BX.delegate(this._handleUserSelectorHtmlLoaded, this)
				}
			);
		}
	},
	_handleUserSelectorHtmlLoaded: function(data)
	{
		this._container.parentNode.appendChild(BX.create("DIV", { html: data  }));
		this._isLoaded = true;
	},
	_handleUserSelectorScriptLoaded: function(config)
	{
		if(config["url"] !== this._serviceUrl)
		{
			return;
		}

		BX.removeCustomEvent("onAjaxSuccessFinish", this._userSelectorScriptLoaded);
		this._userSelectorScriptLoaded = null;

		this._componentContainer = BX(this.componentName + "_selector_content");
		var objName = "O_" + this.componentName;
		if(window[objName])
		{
			this._componentObj = window[objName];
			this._componentObj.onSelect = BX.delegate(this._handleUserSelect, this);
		}

		this.openDialog();
	},
	_handleUserSelect: function(user)
	{
		this.closeDialog();

		if(!this._userInfoProvider)
		{
			return;
		}

		var self = this;
		this._userInfoProvider.getInfo(
			user.id,
			function(userInfo)
			{
				self._userInfo = userInfo;
				self.layout();
				if(self._fieldId.length > 0)
				{
					var editor = self._editor;
					if(!editor)
					{
						editor = BX.CrmInstantEditor.getDefault();
					}

					if(editor)
					{
						editor.saveFieldValue(self._fieldId, userInfo.getId());
					}
				}
			}
		);
	},
	_handleEditorCreation: function(editor)
	{
		var editorId = this.getOption('editorId', '');
		if(editorId !== '' && editor.getId() === editorId)
		{
			this._setupEditor(editor);
		}
	},
	_handleEditorFieldValueSaved: function(name, val)
	{
		if(this._fieldId !== name || !this._userInfoProvider)
		{
			return;
		}

		if(this._userInfo && this._userInfo.getId() === val)
		{
			return;
		}

		var self = this;
		this._userInfoProvider.getInfo(
			val,
			function(userInfo)
			{
				self._userInfo = userInfo;
				self.layout();
			}
		);
	},
	_setupEditor: function(editor)
	{
		if(this._editor)
		{
			BX.removeCustomEvent(
				this._editor,
				'CrmInstantEditorFieldValueSaved',
				BX.delegate(this._handleEditorFieldValueSaved, this)
			);
		}

		this._editor = editor;

		if(this._editor)
		{
			BX.addCustomEvent(
				this._editor,
				'CrmInstantEditorFieldValueSaved',
				BX.delegate(this._handleEditorFieldValueSaved, this)
			);
		}
	}
};

BX.CrmSidebarUserSelector.create = function(id, button, container, componentName, options)
{
	var self = new BX.CrmSidebarUserSelector();
	self.initialize(id, button, container, componentName, options);
	return self;
};

BX.CrmUserSearchField = function()
{
	this._id = '';
	this._search_input = null;
	this._data_input = null;
	this._componentName = '';
	this._componentContainer = null;
	this._componentObj = null;
	this._dlg = null;
	this._dlgDisplayed = false;
	this._currentUser = {};
};

BX.CrmUserSearchField.prototype =
{
	initialize: function(id, search_input, data_input, componentName, user)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : ('crm_user_search_field_' + Math.random());

		if(!BX.type.isElementNode(search_input))
		{
			throw  "BX.CrmUserSearchField: 'search_input' is not defined!";
		}
		this._search_input = search_input;

		if(!BX.type.isElementNode(data_input))
		{
			throw  "BX.CrmUserSearchField: 'data_input' is not defined!";
		}
		this._data_input = data_input;

		if(!BX.type.isNotEmptyString(componentName))
		{
			throw  "BX.CrmUserSearchField: 'componentName' is not defined!";
		}
		this._componentName = componentName;

		this._componentContainer = BX(componentName + '_selector_content');
		var objName = 'O_' + componentName;
		if(window[objName])
		{
			this._componentObj = window[objName];
			this._componentObj.onSelect = BX.delegate(this._handleUserSelect, this);
			this._componentObj.searchInput = search_input;

			BX.bind(search_input, 'keyup', BX.proxy(this._handleSearchKey, this));
			BX.bind(search_input, 'focus', BX.proxy(this._handleSearchFocus, this));
			BX.bind(document, 'click', BX.delegate(this._handleExternalClick, this));
		}

		this._currentUser = user ? user : {};
		this._adjustUser();
	},
	openDialog: function()
	{
		this._dlg = new BX.PopupWindow(
			this._id,
			this._search_input,
			{
				autoHide: false,
				draggable: false,
				//closeByEsc: true,
				offsetLeft: 0,
				offsetTop: 0,
				bindOptions: { forceBindPosition: true },
				content : this._componentContainer,
				events:
				{
					onPopupShow: BX.delegate(
						function()
						{
							this._dlgDisplayed = true;
						},
						this
					),
					onPopupClose: BX.delegate(
						function()
						{
							this._dlgDisplayed = false;
							this._dlg.destroy();
						},
						this
					),
					onPopupDestroy: BX.delegate(
						function()
						{
							this._dlg = null;
						},
						this
					)
				}
			}
		);

		this._dlg.show();
	},
	_adjustUser: function()
	{
		this._search_input.value = this._currentUser['name'] ? this._currentUser.name : '';
		this._data_input.value = this._currentUser['id'] ? this._currentUser.id : 0;
	},
	closeDialog: function()
	{
		if(this._dlg)
		{
			this._dlg.close();
		}
	},
	_handleExternalClick: function(e)
	{
		if(!e)
		{
			e = window.event;
		}

		if(e.target !== this._search_input &&
			!BX.findParent(e.target, { attribute:{ id: this._componentName + '_selector_content' } }))
		{
			this._adjustUser();
			this.closeDialog();
		}
	},
	_handleSearchKey: function(e)
	{
		if(!this._dlg || !this._dlgDisplayed)
		{
			this.openDialog();
		}

		this._componentObj.search();
	},
	_handleSearchFocus: function(e)
	{
		if(!this._dlg || !this._dlgDisplayed)
		{
			this.openDialog();
		}

		this._componentObj._onFocus(e);
	},
	_handleUserSelect: function(user)
	{
		this._currentUser = user;
		this._adjustUser();
		this.closeDialog();
	}
};

BX.CrmUserSearchField.items = {};

BX.CrmUserSearchField.create = function(id, search_input, data_input, componentName, user)
{
	var self = new BX.CrmUserSearchField();
	self.initialize(id, search_input, data_input, componentName, user);
	this.items[id] = self;
	return self;
};

BX.CrmUserLinkField = function()
{
	this._settings = {};
	this._container = null;
	this._fieldId = '';
	this._editor = null;
	this._userInfoProvider = null;
	this._userInfo = null;

};

BX.CrmUserLinkField.prototype =
{
	initialize: function(settings)
	{
		this._settings = settings ? settings : {};
		this._container = this.getSetting('container', null);
		if(!this._container)
		{
			this._container = BX(this.getSetting('containerId', ''));
		}

		if(!this._container)
		{
			throw 'BX.CrmUserLinkField: container is not found';
		}

		this._userInfoProvider = BX.CrmUserInfoProvider.getItemById(this.getSetting('userInfoProviderId', ''));
		this._userInfo = this.getSetting('userInfo', null);

		this._fieldId = this.getSetting('fieldId', '');
		if(this._fieldId !== '')
		{
			var editorId = this.getSetting('editorId', '');
			if(editorId !== '')
			{
				var editor = BX.CrmInstantEditor.items[editorId];
				if(editor)
				{
					this._setupEditor(editor);
				}
				else
				{
					BX.addCustomEvent(
						'CrmInstantEditorCreated',
						BX.delegate(this._handleEditorCreation, this)
					);
				}
			}
		}
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	layout: function()
	{
		this._container.href = this._userInfo ? this._userInfo.getProfileUrl() : '#';

		var nameElem = BX.findChild(this._container, { className: 'crm-detail-info-resp-name' }, true, false);
		if(nameElem)
		{
			nameElem.innerHTML = BX.util.htmlspecialchars(
				this._userInfo ? this._userInfo.getFullName() : ''
			);
		}

		var postElem = BX.findChild(this._container, { className: 'crm-detail-info-resp-descr' }, true, false);
		if(postElem)
		{
			postElem.innerHTML = BX.util.htmlspecialchars(
				this._userInfo ? this._userInfo.getWorkPosition() : ''
			);
		}

		var photoElem = BX.findChild(this._container, { className: 'crm-detail-info-resp-img' }, true, false);
		if(photoElem)
		{
			BX.cleanNode(photoElem, false);
			photoElem.appendChild(
				BX.create('IMG',
					{
						attrs: { src: this._userInfo ? this._userInfo.getPhotoUrl() : '' }
					}
				)
			);
		}
	},
	_handleEditorCreation: function(editor)
	{
		var editorId = this.getSetting('editorId', '');
		if(editorId !== '' && editor.getId() === editorId)
		{
			this._setupEditor(editor);
		}
	},
	_setupEditor: function(editor)
	{
		if(this._editor)
		{
			BX.removeCustomEvent(
				this._editor,
				'CrmInstantEditorFieldValueSaved',
				BX.delegate(this._handleEditorFieldValueSaved, this)
			);
		}

		this._editor = editor;

		if(this._editor)
		{
			BX.addCustomEvent(
				this._editor,
				'CrmInstantEditorFieldValueSaved',
				BX.delegate(this._handleEditorFieldValueSaved, this)
			);
		}
	},
	_handleEditorFieldValueSaved: function(name, val)
	{
		if(this._fieldId !== name || !this._userInfoProvider)
		{
			return;
		}

		var self = this;
		this._userInfoProvider.getInfo(
			val,
			function(userInfo)
			{
				self._userInfo = userInfo;
				self.layout();
			}
		);
	}
};

BX.CrmUserLinkField.create = function(settings)
{
	var self = new BX.CrmUserLinkField();
	self.initialize(settings);
	return self;
};

BX.CrmUserInfo = function()
{
	this._data = {};
};

BX.CrmUserInfo.prototype =
{
	initialize: function(data)
	{
		this._data = data ? data : {};
	},
	getId: function()
	{
		return BX.type.isNotEmptyString(this._data['ID']) ? this._data['ID'] : '';
	},
	getProfileUrl: function()
	{
		return BX.type.isNotEmptyString(this._data['USER_PROFILE']) ? this._data['USER_PROFILE'] : '';
	},
	getFullName: function()
	{
		return BX.type.isNotEmptyString(this._data['FULL_NAME']) ? this._data['FULL_NAME'] : '';
	},
	getWorkPosition: function()
	{
		return BX.type.isNotEmptyString(this._data['WORK_POSITION']) ? this._data['WORK_POSITION'] : '';
	},
	getPhotoUrl: function()
	{
		return BX.type.isNotEmptyString(this._data['PERSONAL_PHOTO']) ? this._data['PERSONAL_PHOTO'] : '';
	}
};

BX.CrmUserInfo.items = {};
BX.CrmUserInfo.create = function(data)
{
	var self = new BX.CrmUserInfo();
	self.initialize(data);
	this.items[self.getId()] = self;
	return self;
};

BX.CrmUserInfoProvider = function()
{
	this._id = '';
	this._settings = {};
	this._serviceUrl = '';
	this._items = {};
};

BX.CrmUserInfoProvider.prototype =
{
	initialize: function(id, settings)
	{
		if(!BX.type.isNotEmptyString(id))
		{
			throw 'BX.CrmUserInfoProvider: id is not defined';
		}

		this._id = id;

		this._settings = settings ? settings : {};
		var serviceUrl = this.getSetting('serviceUrl', '');
		if(serviceUrl === '')
		{
			throw 'BX.CrmUserInfoProvider: serviceUrl is not found';
		}

		this._serviceUrl = serviceUrl;
	},
	getId: function()
	{
		return this._id;
	},
	getSetting: function(name, defaultval)
	{
		return this._settings[name] ? this._settings[name] : defaultval;
	},
	getInfo: function(userId, callback)
	{
		if(!BX.type.isString(userId))
		{
			userId = userId.toString();
		}

		if(!BX.type.isNotEmptyString(userId))
		{
			if(BX.type.isFunction(callback))
			{
				callback(null);
			}
			return;
		}

		if(typeof(this._items[userId]) !== 'undefined')
		{
			if(BX.type.isFunction(callback))
			{
				callback(this._items[userId]);
			}
			return;
		}

		var self = this;
		BX.ajax(
			{
				'url': this._serviceUrl,
				'method': 'POST',
				'dataType': 'json',
				'data':
				{
					'MODE': 'GET_USER_INFO',
					'USER_ID': userId,
					'USER_PROFILE_URL_TEMPLATE': this.getSetting('userProfileUrlTemplate', '')
				},
				onsuccess: function(data)
					{
						var item = BX.CrmUserInfo.create(data['USER_INFO'] ? data['USER_INFO'] : {});
						self._items[userId] = item;
						if(BX.type.isFunction(callback))
						{
							callback(item);
						}
					},
				onfailure: function(data)
					{
						self._showError(self.getMessage('generalError'));
						if(BX.type.isFunction(callback))
						{
							callback(null);
						}
					}
			}
		);
	},
	getMessage: function(name)
	{
		var msg = BX.CrmUserInfoProvider.messages;
		return typeof(msg[name]) !== 'undefined' ? msg[name] : '';
	},
	_showError: function(msg)
	{
		alert(msg);
	}
};

BX.CrmUserInfoProvider.items = {};
BX.CrmUserInfoProvider.getItemById = function(id)
{
	return typeof(this.items[id]) ? this.items[id] : null;
};
BX.CrmUserInfoProvider.createIfNotExists = function(id, settings)
{
	if(typeof(this.items[id]) !== 'undefined')
	{
		return this.items[id];
	}

	var self = new BX.CrmUserInfoProvider();
	self.initialize(id, settings);
	this.items[self.getId()] = self;
	return self;
};

if(typeof(BX.CrmUserInfoProvider.messages) === 'undefined')
{
	BX.CrmUserInfoProvider.messages = {};
}

BX.CrmDateLinkField = function()
{
	this._dataElem = null;
	this._viewElem = null;
	this._settings = {};
};

BX.CrmDateLinkField.prototype =
{
	initialize: function(dataElem, viewElem, settings)
	{
		if(!BX.type.isElementNode(dataElem))
		{
			throw "BX.CrmDateLinkField: 'dataElem' is not defined!";
		}
		this._dataElem = dataElem;
		if(BX.type.isElementNode(viewElem))
		{
			this._viewElem = viewElem;
			BX.bind(viewElem, 'click', BX.delegate(this._onViewClick, this));
		}
		else
		{
			BX.bind(dataElem, 'click', BX.delegate(this._onViewClick, this));
		}
		this._settings = settings ? settings : {};
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	//layout: function(){},
	_onViewClick: function(e)
	{
		BX.calendar({ node: (this._viewElem ? this._viewElem : this._dataElem), field: this._dataElem, bTime: this.getSetting('showTime', true), bSetFocus: this.getSetting('setFocusOnShow', true), callback: BX.delegate(this._onCalendarSaveValue, this) });
	},
	_onCalendarSaveValue: function(value)
	{
		var s = BX.calendar.ValueToString(value, this.getSetting('showTime', true), false);
		this._dataElem.value = s;
		if(this._viewElem)
		{
			this._viewElem.innerHTML = s;
		}
	}
};

BX.CrmDateLinkField.create = function(dataElem, viewElem, settings)
{
	var self = new BX.CrmDateLinkField();
	self.initialize(dataElem, viewElem, settings);
	return self;
};

//region BX.CrmCompanyContactData
BX.CrmCompanyContactData = function()
{
	this._companyId = 0;
	this._contactIds = [];
};

BX.CrmCompanyContactData.prototype =
{
	initialize: function(params)
	{
		if(BX.type.isPlainObject(params))
		{
			this._companyId = params.hasOwnProperty("companyId")
				? params["companyId"] : 0;
			this._contactIds = params.hasOwnProperty("contactIds") && BX.type.isArray(params["contactIds"])
				? params["contactIds"] : [];
		}
	},
	getCompanyId: function()
	{
		return this._companyId;
	},
	setCompanyId: function(id)
	{
		this._companyId = id;
	},
	getContactId: function(index)
	{
		return (index >= 0 && index < this._contactIds.length) ? this._contactIds[index] : null;
	},
	findContactIndex: function(id)
	{
		for(var i = 0; i < this._contactIds.length; i++)
		{
			if(this._contactIds[i] === id)
			{
				return i;
			}
		}
		return -1;
	},
	addContactId: function(id)
	{
		if(this.findContactIndex(id) >= 0)
		{
			return false;
		}

		this._contactIds.push(id);
		return true;
	},
	removeContactId: function(id)
	{
		var index = this.findContactIndex(id);
		if(index < 0)
		{
			return false;
		}

		this._contactIds.splice(index, 1);
		return true;
	}
};

BX.CrmCompanyContactData.create = function(params)
{
	var self = new BX.CrmCompanyContactData();
	self.initialize(params);
	return self;
};
//endregion
//region BX.CrmCompanyContactEditor
BX.CrmCompanyContactEditor = function()
{
	this._id = '';
	this._settings = {};
	this._data = {};
};

BX.CrmCompanyContactEditor.prototype =
{
	initialize: function(id, settings)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : "CRM_COMPANY_CONTACT_EDITOR" + Math.random();
		this._settings = settings ? settings : {};

		this._data = this.getSetting("data", null);
		if(!(this._data instanceof BX.CrmCompanyContactData))
		{
			this._data = BX.CrmCompanyContactData.create(null);
		}
	},
	getSetting: function (name, defaultval)
	{
		return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
	},
	layout: function()
	{
	}
};
//endregion

BX.CrmCompanyContactEditor.create = function(id, settings)
{
	var self = new BX.CrmCompanyContactEditor();
	self.initialize(id, settings);
	return self;
};

BX.CrmEntityEditor = function()
{
	this._id = '';
	this._settings = {};
	this._readonly = false;
	this._dlg = null;
	this._data = null;
	this._info = null;
	this._container = null;
	this._selector = null;
	this._advInfoContainer = null;
	this._externalRequestData = null;
	this._externalEventHandler = null;
	this._blockArea = null;
	this._rqLinkedInputId = "";
	this._rqLinkedId = 0;
	this._bdLinkedId = 0;
};

BX.CrmEntityEditor.prototype =
{
	initialize: function(id, settings, data, info)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : 'CRM_ENTITY_EDITOR' + Math.random();
		this._settings = settings ? settings : {};

		if(!data)
		{
			data = this._prepareData();
		}

		if(!data)
		{
			throw "BX.CrmEntityEditor: Could not find data!";
		}

		this._data = data;

		this._info = info ? info : BX.CrmEntityInfo.create();

		var selectorId = this.getSetting('entitySelectorId', '');
		if(obCrm && obCrm[selectorId])
		{
			var selector = this._selector = obCrm[selectorId];
			selector.AddOnSaveListener(BX.delegate(this._onEntitySelect, this));
			//selector.AddOnBeforeSearchListener();
		}

		var c = this._container = BX(this.getSetting('containerId', ''));
		if(!c)
		{
			throw "BX.CrmEntityEditor: Could not find field container!";
		}

		this._advInfoContainer = BX(this.getSetting('containerId', '') + '_descr');

		BX.bind(BX.findChild(c, { className: 'crm-element-item-delete'}, true, false), 'click', BX.delegate(this._onDeleteButtonClick, this));

		var btnChangeIgnore = this.getSetting('buttonChangeIgnore', false);
		if (!btnChangeIgnore)
			BX.bind(BX.findChild(c, { className: 'bx-crm-edit-crm-entity-change'}, true, false), 'click', BX.delegate(this._onChangeButtonClick, this));

		var btnAdd = BX(this.getSetting('buttonAddId', ''));
		BX.bind((btnAdd) ? btnAdd : BX.findChild(c, { className: 'bx-crm-edit-crm-entity-add'}, true, false), 'click', BX.delegate(this._onAddButtonClick, this));

		if (this.getSetting("cardViewMode", false))
		{
			this._rqLinkedInputId = this.getSetting("rqLinkedInputId", "");
			this._bdLinkedInputId = this.getSetting("bdLinkedInputId", "");
			this._setRqLinkedId(
				this.getSetting("rqLinkedId", 0),
				this.getSetting("bdLinkedId", 0),
				this.getSetting("skipInitInput", false)
			);
		}

		var entityId = this._info.getSetting("id", "");
		var pos, numericId = 0;
		if (typeof(entityId) === 'string' && entityId.length > 0)
		{
			if (/^\d+$/.test(entityId))
				numericId = parseInt(entityId);
			else if (/^\w+_\d+$/.test(entityId))
				numericId = parseInt(entityId.replace(/^\w+_/, ""));
			else
				numericId = 0;
		}
		else
		{
			numericId = parseInt(entityId);
		}
		if (numericId > 0)
		{
			this._data.setId(entityId);
			this.layout();
		}
	},
	getId: function()
	{
		return this._id;
	},
	getTypeName: function()
	{
		return this.getSetting('typeName', '');
	},
	getContext: function()
	{
		return this.getSetting('context', '');
	},
	getCreateUrl: function()
	{
		return this.getSetting('createUrl', '');
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	getData: function()
	{
		return this._data;
	},
	getMessage: function(name)
	{
		var msgs = BX.CrmEntityEditor.messages;
		return BX.type.isNotEmptyString(msgs[name]) ? msgs[name] : '';
	},
	openDialog: function(anchor, mode)
	{
		if(this._dlg)
		{
			this._dlg.setData(this._data);
			this._dlg.open(anchor, mode);
			return;
		}

		switch(this.getTypeName())
		{
			case 'CONTACT':
				this._dlg = BX.CrmContactEditDialog.create(
					this._id,
					this.getSetting('dialog', {}),
					this._data,
					BX.delegate(this._onSaveDialogData, this));
				break;
			case 'COMPANY':
				this._dlg = BX.CrmCompanyEditDialog.create(
					this._id,
					this.getSetting('dialog', {}),
					this._data,
					BX.delegate(this._onSaveDialogData, this));
				break;
		}

		if(this._dlg)
		{
			this._dlg.open(anchor, mode);
		}
	},
	closeDialog: function()
	{
		if(this._dlg)
		{
			this._dlg.close();
		}
	},
	isReadOnly: function()
	{
		return this._readonly;
	},
	setReadOnly: function(readonly)
	{
		readonly = !!readonly;
		if(this._readonly === readonly)
		{
			return;
		}

		this._readonly = readonly;

		var deleteButton = BX.findChild(this._container, { className: 'crm-element-item-delete'}, true, false);
		if(deleteButton)
		{
			deleteButton.style.display = readonly ? 'none' : '';
		}

		var buttonsWrapper = BX.findChild(this._container, { className: 'bx-crm-entity-buttons-wrapper'}, true, false);
		if(buttonsWrapper)
		{
			buttonsWrapper.style.display = readonly ? 'none' : '';
		}
	},
	_prepareData: function(settings)
	{
		var typeName = this.getTypeName();
		var enablePrefix = this.getSetting('enableValuePrefix', false);

		if(typeName === 'CONTACT')
		{
			if(settings && enablePrefix)
				settings['id'] = 'C_' + settings['id'];
			return BX.CrmContactData.create(settings);
		}
		if(typeName === 'COMPANY')
		{
			if(settings && enablePrefix)
				settings['id'] = 'CO_' + settings['id'];
			return BX.CrmCompanyData.create(settings);
		}
		if(typeName === 'LEAD')
		{
			if(settings && enablePrefix)
				settings['id'] = 'L_' + settings['id'];
			return BX.CrmLeadData.create(settings);
		}
		if(typeName === 'DEAL')
		{
			if(settings && enablePrefix)
				settings['id'] = 'D_' + settings['id'];
			return BX.CrmDealData.create(settings);
		}
		if(typeName === 'QUOTE')
		{
			if(settings && enablePrefix)
				settings['id'] = 'Q_' + settings['id'];
			return BX.CrmQuoteData.create(settings);
		}
		return null;
	},
	_onDeleteButtonClick: function(e)
	{
		if(this._readonly)
		{
			return;
		}

		var dataInput = BX(this.getSetting('dataInputId', ''));
		if(dataInput)
		{
			dataInput.value = 0;
		}

		if (this.getSetting("cardViewMode", false))
		{
			var rqLinkedInput = BX(this.getSetting('rqLinkedInputId', ''));
			if (rqLinkedInput)
				rqLinkedInput.value = 0;
		}
		else
		{
			BX.cleanNode(BX.findChild(this._container, { className: 'bx-crm-entity-info-wrapper' }, true, false));
			if (this._advInfoContainer)
				BX.cleanNode(this._advInfoContainer);
		}

		BX.onCustomEvent('CrmEntitySelectorChangeValue', [this.getId(), this.getTypeName(), 0, this]);
	},
	_onChangeButtonClick: function(e)
	{
		if(this._readonly)
		{
			return;
		}

		var selector = this._selector;
		if(selector)
		{
			selector.Open();
		}
	},
	_onAddButtonClick: function(e)
	{
		if(this._readonly)
		{
			return;
		}

		this._data.reset();

		var url = this.getCreateUrl();
		var context = this.getContext();

		if(url === '' || context === '')
		{
			this.openDialog(
				BX.findChild(this._container, { className: "bx-crm-edit-crm-entity-add" }, true, false),
				"CREATE"
			);
			return;
		}

		context = (context + "_" + BX.util.getRandomString(6)).toLowerCase();
		url = BX.util.add_url_param(url, { external_context: context });

		if(!this._externalRequestData)
		{
			this._externalRequestData = {};
		}
		this._externalRequestData[context] = { context: context, wnd: window.open(url) };

		if(!this._externalEventHandler)
		{
			this._externalEventHandler = BX.delegate(this._onExternalEvent, this);
			BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
		}
	},
	_onExternalEvent: function(params)
	{
		var key = BX.type.isNotEmptyString(params["key"]) ? params["key"] : "";
		var value = BX.type.isPlainObject(params["value"]) ? params["value"] : {};
		var typeName = BX.type.isNotEmptyString(value["entityTypeName"]) ? value["entityTypeName"] : "";
		var context = BX.type.isNotEmptyString(value["context"]) ? value["context"] : "";

		if(key === "onCrmEntityCreate"
			&& typeName === this.getTypeName()
			&& this._externalRequestData
			&& BX.type.isPlainObject(this._externalRequestData[context]))
		{
			var isCanceled = BX.type.isBoolean(value["isCanceled"]) ? value["isCanceled"] : false;
			if(!isCanceled)
			{
				var entityInfo = BX.type.isPlainObject(value["entityInfo"]) ? value["entityInfo"] : {};

				this._data = this._prepareData(entityInfo);
				this._info = BX.CrmEntityInfo.create(entityInfo);

				var newDataInput = BX(this.getSetting("newDataInputId", ""));
				if(newDataInput)
				{
					newDataInput.value = this._data.getId();
					BX.onCustomEvent(
						"CrmEntitySelectorChangeValue",
						[ this.getId(), this.getTypeName(), this._data.getId(), this ]
					);
				}

				this.layout();
			}

			if(this._externalRequestData[context]["wnd"])
			{
				this._externalRequestData[context]["wnd"].close();
			}

			delete this._externalRequestData[context];
		}
	},
	_onSaveDialogData: function(dialog)
	{
		this._data = this._dlg.getData();

		var url = this.getSetting('serviceUrl', '');
		var action = this.getSetting('actionName', '');

		if(!(BX.type.isNotEmptyString(url) && BX.type.isNotEmptyString(action)))
		{
			return;
		}

		var self = this;
		BX.ajax(
			{
				'url': url,
				'method': 'POST',
				'dataType': 'json',
				'data':
				{
					'ACTION' : action,
					'DATA': this._data.toJSON()
				},
				onsuccess: function(data)
				{

					if(data['ERROR'])
					{
						self._showDialogError(data['ERROR']);
					}
					else if(!data['DATA'])
					{
						self._showDialogError('BX.CrmEntityEditor: Could not find contact data!');
					}
					else
					{
						self._data = self._prepareData(data['DATA']);
						self._info = BX.CrmEntityInfo.create(data['INFO'] ? data['INFO'] : {});

						var newDataInput = BX(self.getSetting('newDataInputId', ''));
						if(newDataInput)
						{
							newDataInput.value = self._data.getId();
							BX.onCustomEvent('CrmEntitySelectorChangeValue', [self.getId(), self.getTypeName(), self._data.getId(), self]);
						}

						self.layout((function(self) {
							self.closeDialog();
						})(self));
					}
				},
				onfailure: function(data)
				{
					self._showDialogError(data['ERROR'] ? data['ERROR'] : self.getMessage('unknownError'));
				}
			}
		);
	},
	layout: function(afterLayout)
	{
		var dataInput = BX(this.getSetting('dataInputId', ''));
		if(dataInput)
		{
			dataInput.value = this._data.getId();
		}

		var cardViewMode = this.getSetting("cardViewMode", false);
		if (cardViewMode)
		{
			var rqLinkedInput = BX(this.getSetting('rqLinkedInputId', ''));
			if (rqLinkedInput)
				rqLinkedInput.value = this._rqLinkedId;

			var bdLinkedInput = BX(this.getSetting('bdLinkedInputId', ''));
			if (bdLinkedInput)
				bdLinkedInput.value = this._bdLinkedId;
		}
		if (cardViewMode && this._advInfoContainer)
		{
			if (this._blockArea)
			{
				var self = this;
				this._blockArea.destroy(function(){
					self._continueLayout(afterLayout);
				});
			}
			else
			{
				this._continueLayout(afterLayout);
			}
		}
		else
		{
			var view = BX.findChild(this._container, { className: 'bx-crm-entity-info-wrapper'}, true, false);
			if(!view)
			{
				return;
			}

			BX.cleanNode(view);
			view.appendChild(
				BX.create(
					'A',
					{
						attrs:
						{
							className: 'bx-crm-entity-info-link',
							href: this._info.getSetting('url', ''),
							target: '_blank'
						},
						text: this._info.getSetting('title', this._data.getId())
					}
				)
			);

			view.appendChild(
				BX.create(
					'SPAN',
					{
						attrs:
						{
							className: 'crm-element-item-delete'
						},
						events:
						{
							click: BX.delegate(this._onDeleteButtonClick, this)
						}
					}
				)
			);

			if (this._advInfoContainer)
			{
				this._advInfoContainer.innerHTML = this._prepareAdvInfoHTML();
			}

			if (typeof(afterLayout) === "function")
				afterLayout(afterLayout);
		}
	},
	_continueLayout: function(afterLayout)
	{
		this._blockArea = null;

		this._blockArea = new BX.Crm.EntityEditorBlockAreaClass({
			editor: this,
			container: this._advInfoContainer,
			nextNode: null,
			entityInfoList: [this._info],
			readOnlyMode: this.isReadOnly(),
			closeBlockHandler: BX.delegate(this._onDeleteButtonClick, this),
			changeSelectedRequisiteHandler: BX.delegate(this._onChangeSelectedRequisite, this),
			changeLinkedRequisiteHandler: BX.delegate(this._onChangeLinkedRequisite, this),
			rqLinkedId: this._rqLinkedId,
			bdLinkedId: this._bdLinkedId
		});

		if (typeof(afterLayout) === "function")
			afterLayout();
	},
	_onEntitySelect: function(settings)
	{
		var typeName = this.getTypeName().toLowerCase();
		var item = settings[typeName] && settings[typeName][0] ? settings[typeName][0] : null;
		var cardViewMode = this.getSetting("cardViewMode", false);
		if(!item)
		{
			if (cardViewMode && this._advInfoContainer && this._blockArea)
				this._blockArea.destroy();

			return;
		}

		this._data.setId(item['id']);
		this._info = BX.CrmEntityInfo.create(item);

		this._setRqLinkedId(0, 0);

		var self = this;
		this.layout(function() {
			BX.onCustomEvent('CrmEntitySelectorChangeValue', [self.getId(), self.getTypeName(), item['id'], self]);
		});
	},
	_showDialogError: function(msg)
	{
		if(this._dlg)
		{
			this._dlg.showError(msg);
		}
	},
	_prepareAdvInfoHTML: function()
	{
		var result = "";
		var type, advInfo, i;
		var contactType = "";
		var phoneItems = [], emailItems = [];

		type = this._info.getSetting("type", null);
		if (type)
		{
			advInfo = this._info.getSetting("advancedInfo", null);
			if (advInfo)
			{
				if (advInfo["contactType"] && advInfo["contactType"]["name"]
					&& typeof(advInfo["contactType"]["name"]) === "string")
				{
					contactType = BX.util.trim(advInfo["contactType"]["name"]);
				}

				if (advInfo["multiFields"] && advInfo["multiFields"] instanceof Array)
				{
					var mf = advInfo["multiFields"];
					for (i = 0; i < mf.length; i++)
					{
						if (mf[i]["TYPE_ID"] && mf[i]["TYPE_ID"] === "PHONE")
						{
							phoneItems.push({"VALUE": BX.util.trim(mf[i]["VALUE"])});
						}
						if (mf[i]["TYPE_ID"] && mf[i]["TYPE_ID"] === "EMAIL")
						{
							emailItems.push({"VALUE": BX.util.trim(mf[i]["VALUE"])});
						}
					}
				}

				switch (type)
				{
					case 'contact':
						if (phoneItems.length > 0)
						{
							result +=
								"<span class=\"crm-offer-info-descrip-tem crm-offer-info-descrip-tel\">" +
								this.getMessage("prefPhone") + ": " + BX.util.htmlspecialchars(phoneItems[0]["VALUE"]) +
								"<a href=\"callto:" + BX.util.htmlspecialchars(phoneItems[0]["VALUE"]) +
								"\" class=\"crm-offer-info-descrip-icon\"></a></span><br/>";
						}
						if (emailItems.length > 0)
						{
							result +=
								"<span class=\"crm-offer-info-descrip-tem crm-offer-info-descrip-imail\">" +
								this.getMessage("prefEmail") + ": " + BX.util.htmlspecialchars(emailItems[0]["VALUE"]) +
								"<a href=\"mailto:" + BX.util.htmlspecialchars(emailItems[0]["VALUE"]) +
								"\" class=\"crm-offer-info-descrip-icon\"></a></span><br/>";
						}
						if (contactType)
						{
							result +=
								"<span class=\"crm-offer-info-descrip-tem crm-offer-info-descrip-type\">" +
								this.getMessage("prefContactType") + ": " + BX.util.htmlspecialchars(contactType) +
								"</span><br/>";
						}
						break;
					case 'company':
					case 'lead':
						if (phoneItems.length > 0)
						{
							result +=
								"<span class=\"crm-offer-info-descrip-tem crm-offer-info-descrip-tel\">" +
								this.getMessage("prefPhone") + ": " + BX.util.htmlspecialchars(phoneItems[0]["VALUE"]) +
								"<a href=\"callto:" + BX.util.htmlspecialchars(phoneItems[0]["VALUE"]) +
								"\" class=\"crm-offer-info-descrip-icon\"></a></span><br/>";
						}
						if (emailItems.length > 0)
						{
							result +=
								"<span class=\"crm-offer-info-descrip-tem crm-offer-info-descrip-imail\">" +
								this.getMessage("prefEmail") + ": " + BX.util.htmlspecialchars(emailItems[0]["VALUE"]) +
								"<a href=\"mailto:" + BX.util.htmlspecialchars(emailItems[0]["VALUE"]) +
								"\" class=\"crm-offer-info-descrip-icon\"></a></span><br/>";
						}
						break;
				}
			}
		}

		return result;
	},
	_onChangeSelectedRequisite: function(entityTypeId, entityId, requisiteId, bankdetailId)
	{
		entityTypeId = parseInt(entityTypeId);
		entityId = parseInt(entityId);
		requisiteId = parseInt(requisiteId);
		bankdetailId = parseInt(bankdetailId);

		if (bankdetailId < 0 || isNaN(bankdetailId))
			bankdetailId = 0;

		var i, advancedInfo, requisiteData, id;

		if (entityTypeId > 0 && entityId > 0 && requisiteId > 0)
		{
			id = parseInt(this._info.getSetting("id", 0));
			if (id === entityId)
			{
				advancedInfo = this._info.getSetting("advancedInfo", null);
				if (BX.type.isPlainObject(advancedInfo) && BX.type.isArray(advancedInfo["requisiteData"]))
				{
					requisiteData = advancedInfo["requisiteData"];
					for (i = 0; i < requisiteData.length; i++)
					{
						if (
							requisiteData[i]["selected"] = (
								entityTypeId === parseInt(requisiteData[i]["entityTypeId"])
								&& entityId === parseInt(requisiteData[i]["entityId"])
								&& requisiteId === parseInt(requisiteData[i]["requisiteId"])
							)
						)
						{
							requisiteData[i]["bankDetailIdSelected"] = bankdetailId;
						}
					}
				}
			}
		}
	},
	_onChangeLinkedRequisite: function(requisiteId, bankDetailId)
	{
		this._setRqLinkedId(requisiteId, bankDetailId);
	},
	_setRqLinkedId: function(requisiteId, bankDetailId, skipSetInputValue)
	{
		skipSetInputValue = !!skipSetInputValue;
		if (this.getSetting("cardViewMode", false))
		{
			var inp;

			this._rqLinkedId = parseInt(requisiteId);
			if (this._rqLinkedId < 0 || isNaN(this._rqLinkedId))
				this._rqLinkedId = 0;
			if (!skipSetInputValue)
			{
				inp = BX(this._rqLinkedInputId);
				if (inp)
					inp.value = this._rqLinkedId;
			}

			this._bdLinkedId = parseInt(bankDetailId);
			if (this._bdLinkedId < 0 || isNaN(this._bdLinkedId))
				this._bdLinkedId = 0;
			if (!skipSetInputValue)
			{
				inp = BX(this._bdLinkedInputId);
				if (inp)
					inp.value = this._bdLinkedId;
			}
		}
	}
};

if(typeof(BX.CrmEntityEditor.messages) === 'undefined')
{
	BX.CrmEntityEditor.messages = {};
}

BX.CrmEntityEditor.items = {};

BX.CrmEntityEditor.create = function(id, settings, data, info)
{
	var self = new BX.CrmEntityEditor();
	self.initialize(id, settings, data, info);
	this.items[id] = self;
	return self;
};

BX.CrmContactEditDialog = function()
{
	this._id = '';
	this._settings = {};
	this._dlg = null;
	this._dlgCfg = {};
	this._data = null;
	this._mode = 'CREATE';
	this._onSaveCallback = null;
};

BX.CrmContactEditDialog.prototype =
{
	initialize: function(id, settings, data, onSaveCallback)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : 'CRM_CONTACT_EDIT_DIALOG_' + Math.random();
		this._settings = settings ? settings : {};
		this._data = data ? data : BX.CrmContactData.create();
		this._onSaveCallback = BX.type.isFunction(onSaveCallback) ? onSaveCallback : null;
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	getData: function()
	{
		return this._data;
	},
	setData: function(data)
	{
		this._data = data ? data : BX.CrmContactData.create();
	},
	isOpened: function()
	{
		return this._dlg && this._dlg.isShown();
	},
	open: function(anchor, mode)
	{
		if(!BX.type.isNotEmptyString(mode) || (mode !== 'CREATE' && mode !== 'EDIT'))
		{
			mode = this._mode;
		}

		if(this._dlg && this._mode === mode)
		{
			if(!this._dlg.isShown())
			{
				this._dlg.show();
			}
			return;
		}

		if(this._mode !== mode)
		{
			this._mode = mode;
		}

		var cfg = this._dlgCfg = {};
		cfg['id'] = this._id;
		this._dlg = new BX.PopupWindow(
			cfg['id'],
			anchor,
			{
				autoHide: false,
				draggable: true,
				offsetLeft: 0,
				offsetTop: 0,
				bindOptions: { forceBindPosition: false },
				closeByEsc: true,
				closeIcon: { top: '10px', right: '15px'},
				titleBar: this.getSetting('title', 'New contact'),
				events:
				{
					//onPopupShow: function(){},
					onPopupClose: BX.delegate(this._onPopupClose, this),
					onPopupDestroy: BX.delegate(this._onPopupDestroy, this)
				},
				content: this._prepareContent(),
				buttons: this._prepareButtons()
			}
		);

		this._dlg.show();
	},
	close: function()
	{
		if(this._dlg)
		{
			this._dlg.close();
		}
	},
	showError: function(msg)
	{
		var errorWrap = BX(this._getElementId('errors'));
		if(errorWrap)
		{
			errorWrap.innerHTML = msg;
			errorWrap.style.display = '';
		}
	},
	_onPopupClose: function()
	{
		this._dlg.destroy();
	},
	_onPopupDestroy: function()
	{
		this._dlg = null;
	},
	_prepareContent: function()
	{
		var wrapper = BX.create(
			'DIV',
			{
				attrs: { className: 'bx-crm-dialog-quick-create-popup' }
			}
		);

		var data = this._data;
		wrapper.appendChild(
			BX.create(
				'DIV',
				{
					attrs:
					{
						className: 'bx-crm-dialog-quick-create-error-wrap',
						style: 'display:none'
					},
					props: { id: this._getElementId('errors') }
				}
			)
		);
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextField({ id: this._getElementId('lastName'), title: this.getSetting('lastNameTitle', 'Last Name'), value: data.getLastName() }));
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextField({ id: this._getElementId('name'), title: this.getSetting('nameTitle', 'Name'), value: data.getName() }));
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextField({ id: this._getElementId('secondName'), title: this.getSetting('secondNameTitle', 'Second Name'), value: data.getSecondName() }));
		if(this.getSetting('enableEmail', true))
		{
			wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextField({ id: this._getElementId('email'), title: this.getSetting('emailTitle', 'E-mail'), value: data.getEmail() }));
		}
		if(this.getSetting('enablePhone', true))
		{
			wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextField({ id: this._getElementId('phone'), title: this.getSetting('phoneTitle', 'Phone'), value: data.getPhone() }));
		}
		if(this.getSetting('enableExport', true))
		{
			if(this._mode === 'CREATE')
			{
				data.markAsExportable(true);
			}

			wrapper.appendChild(BX.CrmPopupWindowHelper.prepareCheckBoxField({ id: this._getElementId('export'), title: this.getSetting('exportTitle', 'Enable Export'), value: data.isExportable() }));
		}
		return wrapper;
	},
	_prepareButtons: function()
	{
		return BX.CrmPopupWindowHelper.prepareButtons(
			[
				{
					type: 'button',
					settings:
					{
						text: this.getSetting('addButtonName', 'Add'),
						className: 'popup-window-button-accept',
						events:
						{
							click : BX.delegate(this._onSaveButtonClick, this)
						}
					}
				},
				{
					type: 'link',
					settings:
					{
						text: this.getSetting('cancelButtonName', 'Cancel'),
						className: 'popup-window-button-link-cancel',
						events:
						{
							click :
								function()
								{
									this.popupWindow.close();
								}
						}
					}
				}
			]
		);
	},
	_getElementId: function(code)
	{
		return this._dlgCfg['id'] + '_' + code;
	},
	_onSaveButtonClick: function()
	{
		this._data.setLastName(BX(this._getElementId('lastName')).value);
		this._data.setName(BX(this._getElementId('name')).value);
		this._data.setSecondName(BX(this._getElementId('secondName')).value);
		if(this.getSetting('enableEmail', true))
		{
			this._data.setEmail(BX(this._getElementId('email')).value);
		}
		if(this.getSetting('enablePhone', true))
		{
			this._data.setPhone(BX(this._getElementId('phone')).value);
		}
		if(this.getSetting('enableExport', true))
		{
			this._data.markAsExportable(BX(this._getElementId('export')).checked);
		}
		if(this._onSaveCallback)
		{
			this._onSaveCallback(this);
		}
	}
};

BX.CrmContactEditDialog.create = function(id, settings, data, onSaveCallback)
{
	var self = new BX.CrmContactEditDialog();
	self.initialize(id, settings, data, onSaveCallback);
	return self;
};

BX.CrmCompanyEditDialog = function()
{
	this._id = '';
	this._settings = {};
	this._dlg = null;
	this._dlgCfg = {};
	this._data = null;
	this._mode = 'CREATE';
	this._onSaveCallback = null;
};

BX.CrmCompanyEditDialog.prototype =
{
	initialize: function(id, settings, data, onSaveCallback)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : 'CRM_COMPANY_EDIT_DIALOG_' + Math.random();
		this._settings = settings ? settings : {};
		this._data = data ? data : BX.CrmCompanyData.create();
		this._onSaveCallback = BX.type.isFunction(onSaveCallback) ? onSaveCallback : null;
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	getData: function()
	{
		return this._data;
	},
	setData: function(data)
	{
		this._data = data ? data : BX.CrmCompanyData.create();
	},
	isOpened: function()
	{
		return this._dlg && this._dlg.isShown();
	},
	open: function(anchor, mode)
	{
		if(!BX.type.isNotEmptyString(mode) || (mode !== 'CREATE' && mode !== 'EDIT'))
		{
			mode = this._mode;
		}

		if(this._dlg && this._mode === mode)
		{
			this._dlg.setContent(this._prepareContent());
			if(!this._dlg.isShown())
			{
				this._dlg.show();
			}
			return;
		}

		if(this._mode !== mode)
		{
			this._mode = mode;
		}

		var cfg = this._dlgCfg = {};
		cfg['id'] = this._id;
		this._dlg = new BX.PopupWindow(
			cfg['id'],
			anchor,
			{
				autoHide: false,
				draggable: true,
				offsetLeft: 0,
				offsetTop: 0,
				bindOptions: { forceBindPosition: false },
				closeByEsc: true,
				closeIcon: { top: '10px', right: '15px'},
				titleBar: this.getSetting('title', 'New company'),
				events:
				{
					//onPopupShow: function(){},
					onPopupClose: BX.delegate(this._onPopupClose, this),
					onPopupDestroy: BX.delegate(this._onPopupDestroy, this)
				},
				content: this._prepareContent(),
				buttons: this._prepareButtons()
			}
		);

		this._dlg.show();
	},
	close: function()
	{
		if(this._dlg)
		{
			this._dlg.close();
		}
	},
	showError: function(msg)
	{
		var errorWrap = BX(this._getElementId('errors'));
		if(errorWrap)
		{
			errorWrap.innerHTML = msg;
			errorWrap.style.display = '';
		}
	},
	_onPopupClose: function()
	{
		this._dlg.destroy();
	},
	_onPopupDestroy: function()
	{
		this._dlg = null;
	},
	_prepareContent: function()
	{
		var wrapper = BX.create(
			'DIV',
			{
				attrs: { className: 'bx-crm-dialog-quick-create-popup' }
			}
		);

		var data = this._data;
		wrapper.appendChild(
			BX.create(
				'DIV',
				{
					attrs:
					{
						className: 'bx-crm-dialog-quick-create-error-wrap',
						style: 'display:none'
					},
					props: { id: this._getElementId('errors') }
				}
			)
		);
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextField({ id: this._getElementId('title'), title: this.getSetting('titleTitle', 'Title'), value: data.getTitle() }));
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareSelectField({ id: this._getElementId('companyType'), title: this.getSetting('companyTypeTitle', 'Company Type'), value: data.getCompanyType(), items: this.getSetting('companyTypeItems', null) } ));
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareSelectField({ id: this._getElementId('industry'), title: this.getSetting('industryTitle', 'Industry'), value: data.getIndustry(), items: this.getSetting('industryItems', null) }));
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextField({ id: this._getElementId('email'), title: this.getSetting('emailTitle', 'E-mail'), value: data.getEmail() }));
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextField({ id: this._getElementId('phone'), title: this.getSetting('phoneTitle', 'Phone'), value: data.getPhone() }));
		return wrapper;
	},
	_prepareButtons: function()
	{
		return BX.CrmPopupWindowHelper.prepareButtons(
			[
				{
					type: 'button',
					settings:
					{
						text: this.getSetting('addButtonName', 'Add'),
						className: 'popup-window-button-accept',
						events:
						{
							click : BX.delegate(this._onSaveButtonClick, this)
						}
					}
				},
				{
					type: 'link',
					settings:
					{
						text: this.getSetting('cancelButtonName', 'Cancel'),
						className: 'popup-window-button-link-cancel',
						events:
						{
							click :
								function()
								{
									this.popupWindow.close();
								}
						}
					}
				}
			]
		);
	},
	_getElementId: function(code)
	{
		return this._dlgCfg['id'] + '_' + code;
	},
	_onSaveButtonClick: function()
	{
		this._data.setTitle(BX(this._getElementId('title')).value);
		this._data.setCompanyType(BX(this._getElementId('companyType')).value);
		this._data.setIndustry(BX(this._getElementId('industry')).value);
		this._data.setEmail(BX(this._getElementId('email')).value);
		this._data.setPhone(BX(this._getElementId('phone')).value);

		if(this._onSaveCallback)
		{
			this._onSaveCallback(this);
		}
	}
};

BX.CrmCompanyEditDialog.create = function(id, settings, data, onSaveCallback)
{
	var self = new BX.CrmCompanyEditDialog();
	self.initialize(id, settings, data, onSaveCallback);
	return self;
};

BX.CrmContactData = function()
{
	this._id = 0;
	this._name = this._secondName = this._lastName = this._email = this._phone = '';
	this._export = null;
};

BX.CrmContactData.prototype =
{
	initialize: function(settings)
	{
		if(!settings)
		{
			return;
		}

		if(settings['id'])
		{
			this.setId(settings['id']);
		}

		if(settings['name'])
		{
			this.setName(settings['name']);
		}

		if(settings['secondName'])
		{
			this.setSecondName(settings['secondName']);
		}

		if(settings['lastName'])
		{
			this.setLastName(settings['lastName']);
		}

		if(settings['email'])
		{
			this.setEmail(settings['email']);
		}

		if(settings['phone'])
		{
			this.setPhone(settings['phone']);
		}

		if(settings['export'])
		{
			this.markAsExportable(settings['export']);
		}
	},
	reset: function()
	{
		this._id = 0;
		this._name = this._secondName = this._lastName = this._email = this._phone = '';
		this._export = null;
	},
	getId: function()
	{
		return this._id;
	},
	setId: function(val)
	{
		this._id = val;
	},
	getName: function()
	{
		return this._name;
	},
	setName: function(val)
	{
		this._name = BX.type.isNotEmptyString(val) ? val : '';
	},
	getSecondName: function()
	{
		return this._secondName;
	},
	setSecondName: function(val)
	{
		this._secondName = BX.type.isNotEmptyString(val) ? val : '';
	},
	getLastName: function()
	{
		return this._lastName;
	},
	setLastName: function(val)
	{
		this._lastName = BX.type.isNotEmptyString(val) ? val : '';
	},
	getEmail: function()
	{
		return this._email;
	},
	setEmail: function(val)
	{
		this._email = BX.type.isNotEmptyString(val) ? val : '';
	},
	getPhone: function()
	{
		return this._phone;
	},
	setPhone: function(val)
	{
		this._phone = BX.type.isNotEmptyString(val) ? val : '';
	},
	isExportable: function()
	{
		return !!this._export;
	},
	markAsExportable: function(val)
	{
		this._export = !!val;
	},
	toJSON: function()
	{
		var result =
			{
				id: this._id,
				name: this._name,
				secondName: this._secondName,
				lastName: this._lastName,
				email: this._email,
				phone: this._phone
			};
		if(this._export !== null)
		{
			result['export'] = this._export ? 'Y' : 'N';
		}
		return result;
	}
};

BX.CrmContactData.create = function(settings)
{
	var self = new BX.CrmContactData();
	self.initialize(settings);
	return self;
};

BX.CrmCompanyData = function()
{
	this._id = 0;
	this._title = this._companyType = this._industry = this._email = this._phone = this._addressLegal = '';
};

BX.CrmCompanyData.prototype =
{
	initialize: function(settings)
	{
		if(!settings)
		{
			return;
		}

		if(settings['id'])
		{
			this.setId(settings['id']);
		}

		if(settings['title'])
		{
			this.setTitle(settings['title']);
		}

		if(settings['companyType'])
		{
			this.setCompanyType(settings['companyType']);
		}

		if(settings['industry'])
		{
			this.setIndustry(settings['industry']);
		}

		if(settings['email'])
		{
			this.setEmail(settings['email']);
		}

		if(settings['phone'])
		{
			this.setPhone(settings['phone']);
		}
	},
	reset: function()
	{
		this._id = 0;
		this._title = this._companyType = this._industry = this._email = this._phone = this._addressLegal = '';
	},
	getId: function()
	{
		return this._id;
	},
	setId: function(val)
	{
		this._id = val;
	},
	getTitle: function()
	{
		return this._title;
	},
	setTitle: function(val)
	{
		this._title = BX.type.isNotEmptyString(val) ? val : '';
	},
	getCompanyType: function()
	{
		return this._companyType;
	},
	setCompanyType: function(val)
	{
		this._companyType = BX.type.isNotEmptyString(val) ? val : '';
	},
	getIndustry: function()
	{
		return this._industry;
	},
	setIndustry: function(val)
	{
		this._industry = BX.type.isNotEmptyString(val) ? val : '';
	},
	getEmail: function()
	{
		return this._email;
	},
	setEmail: function(val)
	{
		this._email = BX.type.isNotEmptyString(val) ? val : '';
	},
	getPhone: function()
	{
		return this._phone;
	},
	setPhone: function(val)
	{
		this._phone = BX.type.isNotEmptyString(val) ? val : '';
	},
	getAddressLegal: function()
	{
		return this._addressLegal;
	},
	setAddressLegal: function(val)
	{
		this._addressLegal = BX.type.isNotEmptyString(val) ? val : '';
	},
	toJSON: function()
	{
		return(
			{
				id: this.id,
				title: this._title,
				companyType: this._companyType,
				industry: this._industry,
				email: this._email,
				phone: this._phone
			}
		);
	}
};

BX.CrmCompanyData.create = function(settings)
{
	var self = new BX.CrmCompanyData();
	self.initialize(settings);
	return self;
};

BX.CrmLeadData = function()
{
	this._id = 0;
	this._title = this._name = this._secondName = this._lastName = this._email = this._phone = '';
};

BX.CrmLeadData.prototype =
{
	initialize: function(settings)
	{
		if(!settings)
		{
			return;
		}

		if(settings['id'])
		{
			this.setId(settings['id']);
		}

		if(settings['title'])
		{
			this.setTitle(settings['title']);
		}

		if(settings['name'])
		{
			this.setName(settings['name']);
		}

		if(settings['secondName'])
		{
			this.setSecondName(settings['secondName']);
		}

		if(settings['lastName'])
		{
			this.setLastName(settings['lastName']);
		}

		if(settings['email'])
		{
			this.setEmail(settings['email']);
		}

		if(settings['phone'])
		{
			this.setPhone(settings['phone']);
		}
	},
	reset: function()
	{
		this._id = 0;
		this._name = this._secondName = this._lastName = this._email = this._phone = '';
	},
	getId: function()
	{
		return this._id;
	},
	setId: function(val)
	{
		this._id = val;
	},
	getName: function()
	{
		return this._name;
	},
	setTitle: function(val)
	{
		this._title = BX.type.isNotEmptyString(val) ? val : '';
	},
	setName: function(val)
	{
		this._name = BX.type.isNotEmptyString(val) ? val : '';
	},
	getSecondName: function()
	{
		return this._secondName;
	},
	setSecondName: function(val)
	{
		this._secondName = BX.type.isNotEmptyString(val) ? val : '';
	},
	getLastName: function()
	{
		return this._lastName;
	},
	setLastName: function(val)
	{
		this._lastName = BX.type.isNotEmptyString(val) ? val : '';
	},
	getEmail: function()
	{
		return this._email;
	},
	setEmail: function(val)
	{
		this._email = BX.type.isNotEmptyString(val) ? val : '';
	},
	getPhone: function()
	{
		return this._phone;
	},
	setPhone: function(val)
	{
		this._phone = BX.type.isNotEmptyString(val) ? val : '';
	},
	toJSON: function()
	{
		return {
			id: this._id,
			name: this._name,
			secondName: this._secondName,
			lastName: this._lastName,
			email: this._email,
			phone: this._phone
		};
	}
};

BX.CrmLeadData.create = function(settings)
{
	var self = new BX.CrmLeadData();
	self.initialize(settings);
	return self;
};

BX.CrmDealData = function()
{
	this._id = this._dealPrice = 0;
	this._title = this._dealType = '';
};

BX.CrmDealData.prototype =
{
	initialize: function(settings)
	{
		if(!settings)
		{
			return;
		}

		if(settings['id'])
		{
			this.setId(settings['id']);
		}

		if(settings['title'])
		{
			this.setTitle(settings['title']);
		}

		if(settings['dealType'])
		{
			this.setDealType(settings['dealType']);
		}

		if(settings['dealPrice'])
		{
			this.setDealType(settings['dealPrice']);
		}
	},
	reset: function()
	{
		this._id = this._dealPrice = 0;
		this._title = this._dealType = '';
	},
	getId: function()
	{
		return this._id;
	},
	setId: function(val)
	{
		this._id = val;
	},
	getTitle: function()
	{
		return this._title;
	},
	setTitle: function(val)
	{
		this._title = BX.type.isNotEmptyString(val) ? val : '';
	},
	getDealType: function()
	{
		return this._dealType;
	},
	setDealType: function(val)
	{
		this._dealType = BX.type.isNotEmptyString(val) ? val : '';
	},
	getDealPrice: function()
	{
		return this._dealPrice;
	},
	setDealPrice: function(val)
	{
		this._dealPrice = BX.type.isNumber(val) ? val : 0;
	}
};

BX.CrmDealData.create = function(settings)
{
	var self = new BX.CrmDealData();
	self.initialize(settings);
	return self;
};

BX.CrmQuoteData = function()
{
	this._id = this._quotePrice = 0;
	this._title = this._quoteType = '';
};

BX.CrmQuoteData.prototype =
{
	initialize: function(settings)
	{
		if(!settings)
		{
			return;
		}

		if(settings['id'])
		{
			this.setId(settings['id']);
		}

		if(settings['title'])
		{
			this.setTitle(settings['title']);
		}

		if(settings['quoteType'])
		{
			this.setQuoteType(settings['quoteType']);
		}

		if(settings['quotePrice'])
		{
			this.setQuoteType(settings['quotePrice']);
		}
	},
	reset: function()
	{
		this._id = this._quotePrice = 0;
		this._title = this._quoteType = '';
	},
	getId: function()
	{
		return this._id;
	},
	setId: function(val)
	{
		this._id = val;
	},
	getTitle: function()
	{
		return this._title;
	},
	setTitle: function(val)
	{
		this._title = BX.type.isNotEmptyString(val) ? val : '';
	},
	getQuoteType: function()
	{
		return this._quoteType;
	},
	setQuoteType: function(val)
	{
		this._quoteType = BX.type.isNotEmptyString(val) ? val : '';
	},
	getQuotePrice: function()
	{
		return this._quotePrice;
	},
	setQuotePrice: function(val)
	{
		this._quotePrice = BX.type.isNumber(val) ? val : 0;
	}
};

BX.CrmQuoteData.create = function(settings)
{
	var self = new BX.CrmQuoteData();
	self.initialize(settings);
	return self;
};

//region BX.CrmCalltoFormat
if(typeof(BX.CrmCalltoFormat) === "undefined")
{
	BX.CrmCalltoFormat =
	{
		undefined: 0,
		standard: 1,
		slashless: 2,
		custom: 3,
		bitrix: 4
	};
}
//endregion
//region BX.CrmEntityInfo
if(typeof(BX.CrmEntityInfo) === "undefined")
{
	BX.CrmEntityInfo = function()
	{
		this._settings = {};
	};
	BX.CrmEntityInfo.prototype =
	{
		initialize: function(settings)
		{
			this._settings = BX.type.isPlainObject(settings) ? settings : {};
		},
		getSettings: function()
		{
			return this._settings;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings[name] ? this._settings[name] : defaultval;
		},
		isNew: function()
		{
			return BX.prop.getInteger(this._settings, "id", 0) <= 0;
		},
		getId: function()
		{
			return BX.prop.getInteger(this._settings, "id", 0);
		},
		getTypeName: function()
		{
			var s = BX.prop.getString(this._settings, "typeName", "");
			if(!BX.type.isNotEmptyString(s))
			{
				s = BX.CrmEntityType.verifyName(BX.prop.getString(this._settings, "type", ""));
			}
			return s;
		},
		getTypeId: function()
		{
			return BX.CrmEntityType.resolveId(this.getTypeName());
		},
		getTypeCaption: function()
		{
			return BX.CrmEntityType.getCaptionByName(this.getTypeName());
		},
		getTitle: function()
		{
			return BX.prop.getString(this._settings, "title", "");
		},
		setTitle: function(title)
		{
			if(BX.type.isString(title))
			{
				this._settings["title"] = title;
			}
		},
		getCategoryId: function()
		{
			return BX.prop.getInteger(this._settings, "categoryId", "");
		},
		setCategoryId: function(categoryId)
		{
			if(BX.type.isInteger(categoryId))
			{
				this._settings["categoryId"] = categoryId;
			}
		},
		getDescription: function()
		{
			return BX.prop.getString(this._settings, "desc", "");
		},
		getShowUrl: function()
		{
			return BX.prop.getString(this._settings, "url", "");
		},
		getImageUrl: function()
		{
			return BX.prop.getString(this._settings, "image", "");
		},
		getLargeImageUrl: function()
		{
			return BX.prop.getString(this._settings, "largeImage", "");
		},
		canUpdate: function()
		{
			return BX.prop.getBoolean(
				BX.prop.getObject(this._settings, "permissions"),
				"canUpdate",
				false
			);
		},
		hasMultiFields: function()
		{
			var advancedInfo = this.getSetting("advancedInfo", null);
			return (
				BX.type.isPlainObject(advancedInfo)
				&& BX.type.isArray(advancedInfo["multiFields"])
				&& advancedInfo["multiFields"].length > 0
			);
		},
		setMultifields: function(multifields)
		{
			if(!BX.type.isArray(multifields))
			{
				return;
			}

			if(!BX.type.isPlainObject(this._settings["advancedInfo"]))
			{
				this._settings["advancedInfo"] = {};
			}
			this._settings["advancedInfo"]["multiFields"] = multifields;
		},
		setRequisites: function(requisites)
		{
			if(!BX.type.isArray(requisites))
			{
				return;
			}
			if(!BX.type.isPlainObject(this._settings["advancedInfo"]))
			{
				this._settings["advancedInfo"] = {};
			}
			this._settings["advancedInfo"]["requisiteData"] = requisites;
			this._settings["advancedInfo"]["hasEditRequisiteData"] = true;
		},
		setRequisitesForSave: function(requisites)
		{
			if(!BX.type.isPlainObject(requisites))
			{
				return;
			}
			this._settings["requisitesForSave"] = requisites;
		},
		getRequisitesForSave: function()
		{
			return BX.prop.getObject(this._settings, "requisitesForSave", {});
		},
		getMultifields: function()
		{
			return BX.prop.getArray(
				BX.prop.getObject(this._settings, "advancedInfo"),
				"multiFields",
				[]
			);
		},
		findIndexMultifieldById: function(id)
		{
			if(id === null || id === undefined)
			{
				return -1;
			}

			if(!BX.type.isString(id))
			{
				id = id.toString();
			}

			var advancedInfo = this.getSetting("advancedInfo", null);
			if(!advancedInfo)
			{
				return -1;
			}

			var fields = BX.prop.getArray(advancedInfo, "multiFields", []);
			for (var i = 0; i < fields.length; i++)
			{
				var field = fields[i];
				if(BX.prop.getString(field, "ID", "") === id)
				{
					return i;
				}
			}

			return -1;
		},
		getMultiField: function(index)
		{
			var advancedInfo = this.getSetting("advancedInfo", null);
			if(!advancedInfo)
			{
				return null;
			}

			var fields = BX.prop.getArray(advancedInfo, "multiFields", []);
			return fields.length > index ? fields[index] : null;
		},
		setMultifield: function(field, index)
		{
			if(!BX.type.isPlainObject(this._settings["advancedInfo"]))
			{
				this._settings["advancedInfo"] = {};
			}

			if(!BX.type.isArray(this._settings["advancedInfo"]["multiFields"]))
			{
				this._settings["advancedInfo"]["multiFields"] = [];
			}

			if(this._settings["advancedInfo"]["multiFields"].length > index)
			{
				this._settings["advancedInfo"]["multiFields"][index] = field;
			}
			else
			{
				throw "Could not find field";
			}
		},
		addMultifield: function(field)
		{
			if(!BX.type.isPlainObject(this._settings["advancedInfo"]))
			{
				this._settings["advancedInfo"] = {};
			}

			if(!BX.type.isArray(this._settings["advancedInfo"]["multiFields"]))
			{
				this._settings["advancedInfo"]["multiFields"] = [];
			}

			this._settings["advancedInfo"]["multiFields"].push(field);
		},
		setMultifieldById: function(field, fieldId)
		{
			var index = this.findIndexMultifieldById(fieldId);
			if(index >= 0)
			{
				this.setMultifield(field, index);
			}
			else
			{
				this.addMultifield(field);
			}
		},
		getMultiFieldsByType: function(type)
		{
			var advancedInfo = this.getSetting("advancedInfo", null);
			if(!(BX.type.isPlainObject(advancedInfo) && BX.type.isArray(advancedInfo["multiFields"])))
			{
				return [];
			}

			var fields = advancedInfo["multiFields"];
			var results = [];
			for (var i = 0; i < fields.length; i++)
			{
				var field = fields[i];
				var fieldType = BX.type.isNotEmptyString(field["TYPE_ID"]) ? field["TYPE_ID"] : "";

				if (fieldType !== type)
				{
					continue;
				}

				if (BX.type.isNotEmptyString(field["VALUE"]))
				{
					results.push({
						"TYPE": type,
						"VALUE": field["VALUE"],
						"VALUE_EXTRA": field["VALUE_EXTRA"] || {},
						"ID": field["ID"],
						"ENTITY_ID": field["ENTITY_ID"],
						"COMPLEX_NAME": field["COMPLEX_NAME"] || "",
						"VALUE_FORMATTED": field["VALUE_FORMATTED"] || field["VALUE"]
					});
				}
			}
			return results;
		},
		getPhones: function()
		{
			return this.getMultiFieldsByType("PHONE");
		},
		getEmails: function()
		{
			return this.getMultiFieldsByType("EMAIL");
		},
		getEntityBindings: function(entityTypeName)
		{
			var advancedInfo = BX.prop.getObject(this._settings, "advancedInfo", {});
			var bindings = BX.prop.getObject(advancedInfo, "bindings", {});
			return BX.prop.getArray(bindings, entityTypeName, []);
		},
		setEntityBindings: function(entityTypeName, bindings)
		{
			var advancedInfo = BX.prop.getObject(this._settings, "advancedInfo", {});

			if(!this._settings.hasOwnProperty("advancedInfo"))
			{
				this._settings["advancedInfo"] = {};
			}
			if(!this._settings["advancedInfo"].hasOwnProperty("bindings"))
			{
				this._settings["advancedInfo"]["bindings"] = {};
			}
			this._settings["advancedInfo"]["bindings"][entityTypeName] = bindings;
		},
		checkEntityBinding: function(entityTypeName, entityId)
		{
			entityId = BX.convert.toNumber(entityId);

			var bindings = this.getEntityBindings(entityTypeName);
			for(var i = 0, length = bindings.length; i < length; i++)
			{
				if(entityId == bindings[i])
				{
					return true;
				}
			}
			return false;
		},
		removeEntityBinding: function(entityTypeName, entityId)
		{
			var bindings = this.getEntityBindings(entityTypeName);
			var index = -1;
			for(var i = 0, length = bindings.length; i < length; i++)
			{
				if(bindings[i] == entityId)
				{
					index = i;
					break;
				}
			}

			if(index >= 0)
			{
				bindings.splice(index, 1);
				this.setEntityBindings(entityTypeName, bindings);
			}
		},
		addEntityBinding: function(entityTypeName, entityId)
		{
			var bindings = this.getEntityBindings(entityTypeName);
			var index = -1;
			for(var i = 0, length = bindings.length; i < length; i++)
			{
				if(bindings[i] == entityId)
				{
					index = i;
					break;
				}
			}

			if(index < 0)
			{
				bindings.push(entityId);
				this.setEntityBindings(entityTypeName, bindings);
			}
		},
		hasRequisites: function()
		{
			var advancedInfo = this.getSetting("advancedInfo", null);
			return (
				BX.type.isPlainObject(advancedInfo)
				&& BX.type.isArray(advancedInfo["requisiteData"])
				&& advancedInfo["requisiteData"].length > 0
			);
		},
		getRequisites: function()
		{
			var advancedInfo = this.getSetting("advancedInfo", null);
			return (
				BX.type.isPlainObject(advancedInfo) && BX.type.isArray(advancedInfo["requisiteData"])
					? advancedInfo["requisiteData"] : []
			);
		},
		hasEditRequisiteData: function()
		{
			var advancedInfo = this.getSetting("advancedInfo", null);
			return (
				BX.type.isPlainObject(advancedInfo) && BX.type.isBoolean(advancedInfo["hasEditRequisiteData"])
					? advancedInfo["hasEditRequisiteData"] : false
			);
		},
		prepareRequisiteData: function(context)
		{
			var requisites = this.getRequisites();
			if(requisites.length === 0)
			{
				return { requisites: [], context: context };
			}

			var contextRequisiteId = 0;
			var contextBankDetailId = 0;
			if(BX.type.isPlainObject(context))
			{
				if(BX.type.isNumber(context["requisiteId"]))
				{
					contextRequisiteId = context["requisiteId"];
				}

				if(BX.type.isNumber(context["bankDetailId"]))
				{
					contextBankDetailId = context["bankDetailId"];
				}
			}

			var linkedIndex = -1;
			var selectedIndex = -1;
			for(var i = 0; i < requisites.length; i++)
			{
				var requisite = requisites[i];
				var requisiteId = BX.type.isNotEmptyString(requisite["requisiteId"])
					? parseInt(requisite["requisiteId"]) : 0;
				requisite["requisiteId"] = requisiteId;

				var entityTypeId = BX.type.isNotEmptyString(requisite["entityTypeId"])
					? parseInt(requisite["entityTypeId"]) : 0;
				requisite["entityTypeId"] = entityTypeId;

				var entityId = BX.type.isNotEmptyString(requisite["entityId"])
					? parseInt(requisite["entityId"]) : 0;
				requisite["entityId"] = entityId;

				var isSelected = BX.type.isBoolean(requisite["selected"]) ? requisite["selected"] : false;
				requisite["selected"] = isSelected;

				var bankDetailIdSelected = 0;
				if(typeof(requisite["bankDetailIdSelected"]) !== "undefined")
				{
					bankDetailIdSelected = parseInt(requisite["bankDetailIdSelected"]);
					if(bankDetailIdSelected < 0 || isNaN(bankDetailIdSelected))
					{
						bankDetailIdSelected = 0;
					}
				}
				requisite["bankDetailIdSelected"] = bankDetailIdSelected;

				var data = BX.type.isNotEmptyString(requisite["requisiteData"])
					? BX.parseJSON(requisite["requisiteData"]) : null;
				if (!BX.type.isPlainObject(data))
				{
					data = {};
				}

				requisite["viewData"] = BX.type.isPlainObject(data["viewData"]) ? data["viewData"] : {};;

				var bankDetailViewDataList = BX.type.isArray(data["bankDetailViewDataList"])
					? data["bankDetailViewDataList"] : [];
				if (bankDetailViewDataList.length > 0)
				{
					var bankDetailLinkedIndex = -1;
					var bankDetailSelectedIndex = -1;
					var bankDetailSelectedIndexPrimary = -1;
					for (var j = 0; j < bankDetailViewDataList.length; j++)
					{
						var bankDetailViewData = bankDetailViewDataList[j];
						var bankDetailId = parseInt(bankDetailViewData["pseudoId"]);
						if (requisiteId === contextRequisiteId && bankDetailId === contextBankDetailId)
						{
							bankDetailLinkedIndex = j;
						}

						if (bankDetailIdSelected > 0 && bankDetailId === bankDetailIdSelected)
						{
							bankDetailSelectedIndexPrimary = j;
						}

						if (bankDetailViewData["selected"])
						{
							bankDetailSelectedIndex = j;
						}
					}

					if (bankDetailLinkedIndex >= 0)
					{
						if (bankDetailLinkedIndex !== bankDetailSelectedIndex)
						{
							if (bankDetailSelectedIndex >= 0)
							{
								bankDetailViewDataList[bankDetailSelectedIndex]["selected"] = false;
							}
							bankDetailViewDataList[bankDetailLinkedIndex]["selected"] = true;
							requisite["bankDetailIdSelected"] = parseInt(
								bankDetailViewDataList[bankDetailLinkedIndex]["pseudoId"]
							);
						}
					}
					else if (bankDetailSelectedIndexPrimary >= 0)
					{
						if (bankDetailSelectedIndexPrimary !== bankDetailSelectedIndex)
						{
							if (bankDetailSelectedIndex >= 0)
							{
								bankDetailViewDataList[bankDetailSelectedIndex]["selected"] = false;
							}
							bankDetailViewDataList[bankDetailSelectedIndexPrimary]["selected"] = true;
							requisite["bankDetailIdSelected"] = parseInt(
								bankDetailViewDataList[bankDetailSelectedIndexPrimary]["pseudoId"]
							);
						}
					}
					else
					{
						if (bankDetailSelectedIndex < 0)
						{
							bankDetailSelectedIndex = 0;
						}
						requisite["bankDetailIdSelected"] = parseInt(
							bankDetailViewDataList[bankDetailSelectedIndex]["pseudoId"]
						);
					}
				}

				requisite["bankDetailViewDataList"] = bankDetailViewDataList;

				if (isSelected)
				{
					selectedIndex = i;
				}

				if (contextRequisiteId == requisiteId)
				{
					linkedIndex = i;
				}
			}

			if (linkedIndex >= 0 && selectedIndex >= 0 && selectedIndex !== linkedIndex)
			{
				requisites[selectedIndex]["selected"] = false;
				requisites[linkedIndex]["selected"] = true;

				selectedIndex = linkedIndex;
			}

			var linkedRequisiteId = 0;
			var linkedBankDetailId = 0;
			if (selectedIndex >= 0)
			{
				var selectedRequisite = requisites[selectedIndex];
				if (selectedRequisite["requisiteId"] > 0)
				{
					linkedRequisiteId = selectedRequisite["requisiteId"];
				}

				if (selectedRequisite["bankDetailIdSelected"] > 0)
				{
					linkedBankDetailId = selectedRequisite["bankDetailIdSelected"];
				}
			}

			if(contextRequisiteId !== linkedRequisiteId || contextBankDetailId !== linkedBankDetailId)
			{
				contextRequisiteId = linkedRequisiteId;
				contextBankDetailId = linkedBankDetailId;
			}

			return (
			{
				requisites: requisites,
				requisiteId: contextRequisiteId,
				bankDetailId: contextBankDetailId
			}
			);
		}
	};
	BX.CrmEntityInfo.equals = function(a, b)
	{
		return (a.getId() === b.getId() && a.getTypeName() === b.getTypeName());
	};
	BX.CrmEntityInfo.getHashCode = function(item)
	{
		return item.getTypeName()  + "_" + item.getId().toString();
	};
	BX.CrmEntityInfo.create = function(settings)
	{
		var self = new BX.CrmEntityInfo();
		self.initialize(settings);
		return self;
	};
}
//endregion

//region BX.CrmEntityBankDetailList
if(typeof(BX.CrmEntityBankDetailList) === "undefined")
{
	BX.CrmEntityBankDetailList = function()
	{
		this._settings = {};
		this._data = null;
		this._items = null;
	};
	BX.CrmEntityBankDetailList.prototype =
	{
		initialize: function(settings)
		{
			if(BX.type.isPlainObject(settings))
			{
				this._settings = settings;
			}

			this._items = BX.prop.getArray(this._settings, "items", []);
		},
		getItemCount: function()
		{
			return this._items.length;
		},
		getItemIndex: function(item)
		{
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				if(this._items[i] === item)
				{
					return i;
				}
			}
			return -1;
		},
		getItemByIndex: function(index)
		{
			return (index >= 0 && index < this._items.length) ? this._items[index] : null;
		},
		getItemById: function(bankDetailId)
		{
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				var bankDetail = this._items[i];
				if(bankDetailId === BX.prop.getInteger(bankDetail, "pseudoId", 0))
				{
					return bankDetail;
				}
			}
			return null;
		},
		getSelectedItem: function()
		{
			for(var i = 0, length = this._items.length.length; i < length; i++)
			{
				var bankDetail = this._items.length[i];
				if(BX.prop.getBoolean(bankDetail, "selected", false))
				{
					return bankDetail;
				}
			}
			return null;
		},
		getFirstItem: function()
		{
			return this._items.length > 0 ? this._items[0] : null;
		}
	};
	BX.CrmEntityBankDetailList.resolveItemId = function(item)
	{
		return BX.prop.getInteger(item, "pseudoId", 0);
	};
	BX.CrmEntityBankDetailList.create = function(settings)
	{
		var self = new BX.CrmEntityBankDetailList();
		self.initialize(settings);
		return self;
	};
}
//endregion

//region BX.CrmEntityRequisiteInfo
if(typeof(BX.CrmEntityRequisiteInfo) === "undefined")
{
	BX.CrmEntityRequisiteInfo = function()
	{
		this._settings = {};
		this._requisiteId = 0;
		this._bankDetailId = 0;
		this._data = null;
		this._items = null;
	};
	BX.CrmEntityRequisiteInfo.prototype =
	{
		initialize: function(settings)
		{
			if(BX.type.isPlainObject(settings))
			{
				this._settings = settings;
			}

			this._items = [];
			this._data = this.getSetting("data");
			if(!BX.type.isArray(this._data))
			{
				this._data = [];
			}

			if(this._data.length === 0)
			{
				this._requisiteId = this._bankDetailId = 0;
				return;
			}

			this._requisiteId = parseInt(this.getSetting("requisiteId", 0));
			this._bankDetailId = parseInt(this.getSetting("bankDetailId", 0));

			var linkedIndex = -1;
			var selectedIndex = -1;
			for(var i = 0; i < this._data.length; i++)
			{
				var item = this._data[i];

				var bankDetailIdSelected = item["bankDetailIdSelected"] = this.parseIntegerParam("bankDetailIdSelected", item);
				var requisiteId = item["requisiteId"] = this.parseIntegerParam("requisiteId", item);
				var entityTypeId = item["entityTypeId"] = this.parseIntegerParam("entityTypeId", item);
				var entityId = item["entityId"] = this.parseIntegerParam("entityId", item);
				var isSelected = item["selected"] = this.parseBooleanParam("selected", item);

				var data = this.parseJsonParam("requisiteData", item);
				item["viewData"] = BX.type.isPlainObject(data["viewData"]) ? data["viewData"] : {};

				var bankDetailViewDataList = BX.type.isArray(data["bankDetailViewDataList"])
					? data["bankDetailViewDataList"] : [];

				var deletedBankDetailList = BX.type.isArray(data["deletedBankDetailList"])
					? data["deletedBankDetailList"] : [];

				var deletedBankDetailMap = {};
				for(var k = 0; k < deletedBankDetailList.length; k++)
				{
					deletedBankDetailMap[deletedBankDetailList[k]] = true;
				}

				if (bankDetailViewDataList.length > 0)
				{
					var bankDetailLinkedIndex = -1;
					var bankDetailSelectedIndex = -1;
					var bankDetailSelectedIndexPrimary = -1;
					for (var j = 0; j < bankDetailViewDataList.length; j++)
					{
						var bankDetailViewData = bankDetailViewDataList[j];
						var bankDetailId = bankDetailViewData["pseudoId"] = this.parseIntegerParam("pseudoId", bankDetailViewData);

						if(BX.prop.getBoolean(deletedBankDetailMap, bankDetailId, false))
						{
							bankDetailViewData["isDeleted"] = true;
							continue;
						}

						if (requisiteId === this._requisiteId && bankDetailId === this._bankDetailId)
						{
							bankDetailLinkedIndex = j;
						}

						if (bankDetailViewData["selected"])
						{
							bankDetailSelectedIndex = j;
						}

						if (bankDetailIdSelected > 0 && bankDetailId === bankDetailIdSelected)
						{
							bankDetailSelectedIndexPrimary = j;
						}
					}

					if (bankDetailLinkedIndex >= 0)
					{
						if (bankDetailLinkedIndex !== bankDetailSelectedIndex)
						{
							if (bankDetailSelectedIndex >= 0)
							{
								bankDetailViewDataList[bankDetailSelectedIndex]["selected"] = false;
							}
							bankDetailViewDataList[bankDetailLinkedIndex]["selected"] = true;
						}

						item["bankDetailIdSelected"] =
							bankDetailViewDataList[bankDetailLinkedIndex]["pseudoId"];
					}
					else if (bankDetailSelectedIndexPrimary >= 0)
					{
						if (bankDetailSelectedIndexPrimary !== bankDetailSelectedIndex)
						{
							if (bankDetailSelectedIndex >= 0)
							{
								bankDetailViewDataList[bankDetailSelectedIndex]["selected"] = false;
							}
							bankDetailViewDataList[bankDetailSelectedIndexPrimary]["selected"] = true;
							item["bankDetailIdSelected"] =
								bankDetailViewDataList[bankDetailSelectedIndexPrimary]["pseudoId"];
						}
					}
					else
					{
						if (bankDetailSelectedIndex < 0)
						{
							bankDetailSelectedIndex = 0;
						}
						item["bankDetailIdSelected"] =
							bankDetailViewDataList[bankDetailSelectedIndex]["pseudoId"];
					}
				}

				item["bankDetailViewDataList"] = bankDetailViewDataList;
				this._items.push(item);

				if (isSelected)
				{
					selectedIndex = i;
				}

				if (this._requisiteId == requisiteId)
				{
					linkedIndex = i;
				}
			}

			if (linkedIndex >= 0 && selectedIndex >= 0 && selectedIndex !== linkedIndex)
			{
				this._items[selectedIndex]["selected"] = false;
				this._items[linkedIndex]["selected"] = true;

				selectedIndex = linkedIndex;
			}

			var linkedRequisiteId = 0;
			var linkedBankDetailId = 0;
			if (selectedIndex >= 0)
			{
				var selectedRequisite = this._items[selectedIndex];
				if (selectedRequisite["requisiteId"] > 0)
				{
					linkedRequisiteId = selectedRequisite["requisiteId"];
				}

				if (selectedRequisite["bankDetailIdSelected"] > 0)
				{
					linkedBankDetailId = selectedRequisite["bankDetailIdSelected"];
				}
			}

			if(this._requisiteId !== linkedRequisiteId || this._bankDetailId !== linkedBankDetailId)
			{
				this._requisiteId = linkedRequisiteId;
				this._bankDetailId = linkedBankDetailId;
			}
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		parseStringParam: function(name, data, defaultval)
		{
			if(typeof(defaultval) === "undefined")
			{
				defaultval = "";
			}

			var val = defaultval;
			if(BX.type.isNotEmptyString(data[name]))
			{
				val = data[name];
			}
			else
			{
				val = data[name].toString();
			}
			return val;
		},
		parseIntegerParam: function(name, data, defaultval)
		{
			if(typeof(defaultval) === "undefined")
			{
				defaultval = 0;
			}

			var val = defaultval;
			if(BX.type.isNumber(data[name]))
			{
				val = data[name];
			}
			else if(BX.type.isNotEmptyString(data[name]))
			{
				val = parseInt(data[name]);
				if(isNaN(val))
				{
					val = defaultval;
				}
			}
			return val;
		},
		parseBooleanParam: function(name, data, defaultval)
		{
			if(typeof(defaultval) === "undefined")
			{
				defaultval = false;
			}

			var val = defaultval;
			if(BX.type.isBoolean(data[name]))
			{
				val = data[name];
			}
			else if(BX.type.isNotEmptyString(data[name]))
			{
				val = data[name].toLowerCase() === "true";
			}
			return val;
		},
		parseJsonParam: function(name, data, defaultval)
		{
			if(typeof(defaultval) === "undefined")
			{
				defaultval = {};
			}

			var val = defaultval;
			if(BX.type.isNotEmptyString(data[name]))
			{
				val = BX.parseJSON(data[name]);
				if (!BX.type.isPlainObject(val))
				{
					val = {};
				}
			}
			return val;
		},
		getRequisiteId: function()
		{
			return this._requisiteId;
		},
		getBankDetailId: function()
		{
			return this._bankDetailId;
		},
		getItems: function()
		{
			return this._items;
		},
		getItemCount: function()
		{
			return this._items.length;
		},
		getItemIndex: function(item)
		{
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				if(this._items[i] === item)
				{
					return i;
				}
			}
			return -1;
		},
		getItemByIndex: function(index)
		{
			return (index >= 0 && index < this._items.length) ? this._items[index] : null;
		},
		getItemByKey: function(keyName, keyValue)
		{
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				var item = this._items[i];
				if(keyValue == BX.prop.getString(item, keyName, ""))
				{
					return item;
				}
			}
			return null;
		},
		getItemById: function(requisiteId)
		{
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				var item = this._items[i];
				if(requisiteId == BX.prop.getInteger(item, "requisiteId", 0))
				{
					return item;
				}
			}
			return null;
		},
		getSelectedItem: function()
		{
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				var item = this._items[i];
				if(BX.prop.getBoolean(item, "selected", false))
				{
					return item;
				}
			}
			return null;
		},
		getFirstItem: function()
		{
			return this._items.length > 0 ? this._items[0] : null;
		},
		getItemBankDetailList: function(requisiteId)
		{
			var item = this.getItemById(requisiteId);
			if(!item)
			{
				return null;
			}

			return BX.CrmEntityBankDetailList.create({ items: BX.prop.getArray(item, "bankDetailViewDataList", []) });
		},
		getItemBankDetailById: function(requisiteId, bankDetailId)
		{
			var item = this.getItemById(requisiteId);
			if(!item)
			{
				return null;
			}

			var bankDetails = BX.prop.getArray(item, "bankDetailViewDataList", []);
			for(var i = 0, length = bankDetails.length; i < length; i++)
			{
				var bankDetail = bankDetails[i];
				if(bankDetailId == BX.prop.getInteger(bankDetail, "pseudoId", 0))
				{
					return bankDetail;
				}
			}
			return null;
		},
		getSelectedItemBankDetail: function(requisiteId)
		{
			var item = this.getItemById(requisiteId);
			if(!item)
			{
				return null;
			}

			var bankDetails = BX.prop.getArray(item, "bankDetailViewDataList", []);
			for(var i = 0, length = bankDetails.length; i < length; i++)
			{
				var bankDetail = bankDetails[i];
				if(BX.prop.getBoolean(bankDetail, "selected", false))
				{
					return bankDetail;
				}
			}
			return null;
		},
		getFirstItemBankDetail: function(requisiteId)
		{
			var item = this.getItemById(requisiteId);
			if(!item)
			{
				return null;
			}

			var bankDetails = BX.prop.getArray(item, "bankDetailViewDataList", []);
			return bankDetails.length > 0 ? bankDetails[0] : null;
		}
	};
	BX.CrmEntityRequisiteInfo.resolveItemId = function(item)
	{
		return BX.prop.getInteger(item, "requisiteId", 0);
	};
	BX.CrmEntityRequisiteInfo.create = function(settings)
	{
		var self = new BX.CrmEntityRequisiteInfo();
		self.initialize(settings);
		return self;
	};
}
//endregion

//region BX.CrmMultipleEntitySummaryView
if(typeof(BX.CrmMultipleEntitySummaryView) === "undefined")
{
	BX.CrmMultipleEntitySummaryView = function()
	{
		this._id = "";
		this._settings = {};

		this._index = 0;
		this._entityCount = 0;

		this._owner = null;

		this._entityType = "";
		this._entityInfos = [];
		this._areEntitiesLoaded = false;
		this._loaderCfg = null;
		this._entitiesLoadHandler = BX.delegate(this.onLoadEntities, this);

		this._hasLayout = false;
		this._enableRequisites = false;
		this._enableRequisiteChange = false;
		this._readOnly = false;

		this._containerId = "";
		this._container = null;
		this._wrapper = null;
		this._controlWrapper = null;
		this._views = [];

		this._navPrevButton = null;
		this._navNextButton = null;

		this._navPrevHandler = BX.delegate(this.onNavPrev, this);
		this._navNextHandler = BX.delegate(this.onNavNext, this);
		this._deleteHandler = BX.delegate(this.onDelete, this);

		this._deletionNotifier = null;
	};
	BX.CrmMultipleEntitySummaryView.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._entityType = this.getSetting("entityType", "");
			if(!BX.type.isNotEmptyString(this._entityType))
			{
				throw "CrmMultipleEntitySummaryView: Could not find 'entityType' parameter in settings.";
			}

			this._container = this.getSetting("container", "");
			if(!BX.type.isElementNode(this._container))
			{
				this._containerId = this.getSetting("containerId", "");
				if(!BX.type.isNotEmptyString(this._containerId))
				{
					throw "CrmMultipleEntitySummaryView: Could not find 'containerId' parameter in settings.";
				}

				this._container = BX(this._containerId);
				if(!BX.type.isElementNode(this._container))
				{
					throw "CrmMultipleEntitySummaryView: Could not find container element.";
				}
			}

			this._entityInfos = this.getSetting("entityInfos", []);
			if(!BX.type.isArray(this._entityInfos))
			{
				throw "CrmMultipleEntitySummaryView: Could not find 'entityInfos' parameter in settings.";
			}
			this._entityCount = parseInt(this.getSetting("count", 0));

			this._areEntitiesLoaded = this._entityInfos.length === this._entityCount;
			if(!this._areEntitiesLoaded)
			{
				this._entityType = this.getSetting("entityType", "");
				if(!BX.type.isNotEmptyString(this._entityType))
				{
					throw "CrmMultipleEntitySummaryView: Could not find 'entityType' parameter in settings.";
				}

				this._owner = this.getSetting("owner", null);
				if(!BX.type.isPlainObject(this._owner))
				{
					throw "CrmMultipleEntitySummaryView: Could not find owner config.";
				}

				this._loaderCfg = this.getSetting("loader", null);
				if(!BX.type.isPlainObject(this._loaderCfg))
				{
					throw "CrmMultipleEntitySummaryView: Could not find loader config.";
				}
			}

			this._readOnly = !!this.getSetting("readOnly");
			this._enableRequisites = !!this.getSetting("enableRequisites", true);
			this._enableRequisiteChange = this._enableRequisites && !this._readOnly ? !!this.getSetting("enableRequisiteChange", true) : false;

			this._deletionNotifier = BX.CrmNotifier.create(this);
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getMessage: function(name)
		{
			var m = BX.CrmMultipleEntitySummaryView.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		getContainerId: function()
		{
			return this._containerId;
		},
		addDeletionListener: function(listener)
		{
			this._deletionNotifier.addListener(listener);
		},
		removeDeletionListener: function(listener)
		{
			this._deletionNotifier.removeListener(listener);
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			var outerWrapper = this._wrapper = BX.create("DIV", { attrs: { className: "crm-client-selector-tabs-wrap-slider-container" } });
			this._container.appendChild(outerWrapper);

			var innerWrapper = BX.create("DIV", { attrs: { className: "crm-client-selector-tabs-wrap-slider-container-overflow" } });
			outerWrapper.appendChild(innerWrapper);

			var controlWrapper = this._controlWrapper = BX.create("DIV",
				{
					attrs: { className: "crm-offer-tabs-wrap-slide-box" },
					style: { width: (100 * this._entityCount).toString() + "%" }
				}
			);
			innerWrapper.appendChild(controlWrapper);

			this._navPrevButton = BX.create("DIV",
				{
					attrs: { className: "crm-offer-tabs-wrap-slider-arrow crm-offer-tabs-wrap-slider-arrow-left" }
				}
			);
			outerWrapper.appendChild(this._navPrevButton);
			BX.bind(this._navPrevButton, "click", this._navPrevHandler);

			this._navNextButton = BX.create("DIV",
				{
					attrs: { className: "crm-offer-tabs-wrap-slider-arrow crm-offer-tabs-wrap-slider-arrow-right" }
				}
			);
			outerWrapper.appendChild(this._navNextButton);
			BX.bind(this._navNextButton, "click", this._navNextHandler);

			this.initializaControls();
			this._hasLayout = true;
		},
		initializaControls: function()
		{
			var width = (100 / this._entityCount).toFixed(5) + "%";
			for(var i = 0; i < this._entityCount; i++)
			{
				var entityInfo = i < this._entityInfos.length ? this._entityInfos[i] : null;
				var isStub = !(entityInfo instanceof BX.CrmEntityInfo) && !BX.type.isPlainObject(entityInfo);

				var view = BX.CrmEntitySummaryView.create(
					this._id + "_" + i.toString(),
					{
						entityInfo: entityInfo,
						requisiteInfo: null,
						container: this._controlWrapper,
						width: width,
						readOnly: this._readOnly,
						enableRequisites: this._enableRequisites,
						enableRequisiteChange: this._enableRequisiteChange,
						isStub: isStub
					}
				);
				this._views.push(view);

				if(!this._readOnly)
				{
					view.addDeletionListener(this._deleteHandler);
				}
				view.layout();
			}
		},
		resetControls: function()
		{
			for(var i = 0; i < this._views.length; i++)
			{
				this._views[i].clearLayout();
			}
			this._views = [];
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			this.resetControls();

			if(this._wrapper)
			{
				this._wrapper = BX.remove(this._wrapper);
			}

			this._hasLayout = false;
		},
		getIndex: function()
		{
			return this._index;
		},
		setIndex: function(index)
		{
			if(this._index === index)
			{
				return;
			}

			this._index = index;
			this.adjust();

			if(!this._areEntitiesLoaded)
			{
				this.loadEntities();
			}
		},
		adjust: function()
		{
			//Rewind index
			var maxIndex = this._entityCount - 1;
			if(this._index < 0)
			{
				this._index = maxIndex;
			}
			else if(this._index > maxIndex)
			{
				this._index = 0;
			}

			//Set up by index
			this._controlWrapper.style.left = this._index > 0 ? ((-100 * this._index).toString() + "%") : "0";
			if(this._index < this._views.length)
			{
				this._views[this._index].adjust();
			}
		},
		loadEntities: function()
		{
			if(this._areEntitiesLoaded)
			{
				return;
			}

			this._dataLoader = BX.CrmDataLoader.create(
				this._id,
				{
					serviceUrl: this._loaderCfg["url"],
					action: this._loaderCfg["action"],
					params:
					{
						"OWNER_TYPE_NAME": this._owner["typeName"],
						"OWNER_ID": this._owner["id"],
						"ENTITY_TYPE_NAME": this._entityType
					}
				}
			);
			this._dataLoader.load(this._entitiesLoadHandler);
		},
		onLoadEntities: function(sender, result)
		{
			this._entityInfos = result["DATA"];
			this._entityCount = this._entityInfos.length;
			this._areEntitiesLoaded = true;

			if(this._hasLayout)
			{
				this.resetControls();
				this.initializaControls();
			}
		},
		onNavPrev: function(e)
		{
			this.setIndex(this._index - 1);
		},
		onNavNext: function(e)
		{
			this.setIndex(this._index + 1);
		},
		onDelete: function(sender)
		{
			this._deletionNotifier.notify([ sender ]);
		}
	};
	BX.CrmMultipleEntitySummaryView.create = function(id, settings)
	{
		var self = new BX.CrmMultipleEntitySummaryView();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmEntitySummaryView
if(typeof(BX.CrmEntitySummaryView) === "undefined")
{
	BX.CrmEntitySummaryView = function()
	{
		this._id = "";
		this._settings = {};
		this._hasLayout = false;
		this._container = null;
		this._entityInfo = null;
		this._requisiteInfo = null;
		this._waiter = null;
		this._width = "";
		this._clientPanel = null;

		this._enableRequisiteChange = false;
		this._enableRequisites = false;
		this._readOnly = false;
		this._isStub = false;

		this._deleteHandler = BX.delegate(this.onDelete, this);
		this._requisiteChangeHandler = BX.delegate(this.onRequisiteChange, this);

		this._deletionNotifier = null;
		this._requisiteChangeNotifier = null;
	};
	BX.CrmEntitySummaryView.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._container = this.getSetting("container");
			if(!BX.type.isElementNode(this._container))
			{

				var containerId = this.getSetting("containerId", "");
				if(BX.type.isNotEmptyString(containerId))
				{
					this._container = BX(containerId);
				}

				if(!BX.type.isElementNode(this._container))
				{
					throw "CrmEntitySummaryView: Could not find 'container' parameter in settings.";
				}

			}

			var entityInfo = this.getSetting("entityInfo");
			this._entityInfo = entityInfo instanceof BX.CrmEntityInfo
				? entityInfo : BX.CrmEntityInfo.create(entityInfo);

			this._width = this.getSetting("width", "");

			this._readOnly = !!this.getSetting("readOnly");
			this._enableRequisites = !!this.getSetting("enableRequisites", true);
			this._enableRequisiteChange = this._enableRequisites && !this._readOnly ? !!this.getSetting("enableRequisiteChange", true) : false;

			if(this._enableRequisites)
			{
				var requisiteInfo = this.getSetting("requisiteInfo");
				if(requisiteInfo instanceof BX.CrmEntityRequisiteInfo)
				{
					this._requisiteInfo = requisiteInfo;
				}
				else
				{
					this._requisiteInfo = BX.CrmEntityRequisiteInfo.create(
						{
							requisiteId: 0,
							bankDetailId: 0,
							data: this._entityInfo.getRequisites()
						}
					);
				}
			}

			this._isStub = !!this.getSetting("isStub");
			this._requisiteChangeNotifier = BX.CrmNotifier.create(this);
			this._deletionNotifier = BX.CrmNotifier.create(this);
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getEntityInfo: function()
		{
			return this._entityInfo;
		},
		getMessage: function(name)
		{
			var m = BX.CrmEntitySummaryView.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		isStub: function()
		{
			return this._isStub;
		},
		adjust: function()
		{
			if(this._isStub && this._waiter)
			{
				this._waiter.firstElementChild.style.minHeight = BX.pos(this._container)["height"] + "px";
			}
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			if(this._isStub)
			{
				this._waiter = BX.create("DIV",
					{
						attrs: { className: "crm-client-selector-tab-block-wrap" },
						children: [ BX.create("DIV", { attrs: { className: "crm-offer-tabs-wrap-slide-waiter" } }) ]
					}
				);

				if(this._width !== "")
				{
					this._waiter.style.width = this._width;
				}
				this._container.appendChild(this._waiter);

				//waiting for completion of animation
				window.setTimeout(BX.delegate(this.adjust, this), 500);
			}
			else if(this._entityInfo.getId() > 0)
			{
				this._clientPanel = BX.CrmClientPanel.create("",
					{
						container: this._container,
						entityInfo: this._entityInfo,
						requisiteInfo: this._requisiteInfo,
						readOnly: this._readOnly,
						enableRequisites: this._enableRequisites,
						requisiteServiceUrl: this.getSetting("requisiteServiceUrl"),
						width: this._width
					}
				);
				if(!this._readOnly)
				{
					this._clientPanel.addDeletionListener(this._deleteHandler);
				}
				if(this._enableRequisites)
				{
					this._clientPanel.addRequisiteChangeListener(this._requisiteChangeHandler);
				}
				this._clientPanel.layout();
			}

			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(this._clientPanel)
			{
				this._clientPanel.clearLayout();
				this._clientPanel = null;
			}

			if(this._waiter)
			{
				this._waiter = BX.remove(this._waiter);
			}

			this._hasLayout = false;
		},
		addRequisiteChangeListener: function(listener)
		{
			this._requisiteChangeNotifier.addListener(listener);
		},
		removeRequisiteChangeListener: function(listener)
		{
			this._requisiteChangeNotifier.removeListener(listener);
		},
		addDeletionListener: function(listener)
		{
			this._deletionNotifier.addListener(listener);
		},
		removeDeletionListener: function(listener)
		{
			this._deletionNotifier.removeListener(listener);
		},
		onDelete: function(sender)
		{
			this._deletionNotifier.notify();
		},
		onRequisiteChange: function(sender, requisiteId, bankDetailId)
		{
			this._requisiteChangeNotifier.notify([requisiteId, bankDetailId]);
		}
	};
	if(typeof(BX.CrmEntitySummaryView.messages) === "undefined")
	{
		BX.CrmMultipleEntitySummaryView.messages = {};
	}
	BX.CrmEntitySummaryView.create = function(id, settings)
	{
		var self = new BX.CrmEntitySummaryView();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmClientPanel
if(typeof(BX.CrmClientPanel) === "undefined")
{
	BX.CrmClientPanel = function()
	{
		this._id = "";
		this._settings = {};
		this._entityInfo = null;
		this._requisiteInfo = null;
		this._readOnly = false;
		this._hasLayout = false;
		this._container = null;
		this._wrapper = null;
		this._width = "";
		this._tabs = {};
		this._multifieldPanel = null;
		this._requisitePanel = null;

		this._activeTab = "";
		this._mainTabHandler = BX.delegate(this.onMainTabClick, this);
		this._requisiteTabHandler = BX.delegate(this.onRequisiteTabClick, this);
		this._requisiteChangeHandler = BX.delegate(this.onRequisiteChange, this);
		this._deleteButtonHandler = BX.delegate(this.onDeleteButtonClick, this);

		this._readOnly = false;
		this._enableRequisites = false;
		this._enableRequisiteChange = false;

		this._requisiteChangeNotifier = null;
		this._deletionNotifier = null;

	};
	BX.CrmClientPanel.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "CRM_CLIENT_PANEL_" + Math.random();
			this._settings = BX.type.isPlainObject(settings) ? settings : {};

			this._container = this.getSetting("container");
			if(!BX.type.isElementNode(this._container))
			{
				throw "CrmClientPanel: Could not find 'container' parameter in settings.";
			}

			this._entityInfo = this.getSetting("entityInfo");
			if(!(this._entityInfo instanceof BX.CrmEntityInfo))
			{
				throw "CrmClientPanel: Could not find 'entityInfo' parameter in settings.";
			}

			this._readOnly = !!this.getSetting("readOnly");
			this._enableRequisites = !!this.getSetting("enableRequisites", true);
			this._enableRequisiteChange = this._enableRequisites && !this._readOnly ? !!this.getSetting("enableRequisiteChange", true) : false;
			this._width = this.getSetting("width", "");

			if(this._enableRequisites)
			{
				this._requisiteInfo = this.getSetting("requisiteInfo");
				if(!(this._requisiteInfo instanceof BX.CrmEntityRequisiteInfo))
				{
					throw "CrmClientPanel: Could not find 'requisiteInfo' parameter in settings.";
				}
			}

			this._requisiteChangeNotifier = BX.CrmNotifier.create(this);
			this._deletionNotifier = BX.CrmNotifier.create(this);
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getMessage: function(name)
		{
			var m = BX.CrmClientPanel.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			var wrapper = this._wrapper = BX.create("DIV", { attrs: { className: "crm-client-selector-tab-block-wrap" } });
			if(this._width !== "")
			{
				this._wrapper.style.width = this._width;
			}
			this._container.appendChild(wrapper);

			var typeName = this._entityInfo.getTypeName();
			if(typeName === BX.CrmEntityType.names.contact)
			{
				BX.addClass(wrapper, "crm-client-selector-tab-contact");
			}
			else if(typeName === BX.CrmEntityType.names.company)
			{
				BX.addClass(wrapper, "crm-client-selector-tab-company");
			}

			var innerWrapper = BX.create("DIV", { attrs: { className: "crm-client-selector-tab-block" } });
			wrapper.appendChild(innerWrapper);

			var titleWrapper = BX.create("DIV", { attrs: { className: "crm-client-selector-tab-title" } });
			innerWrapper.appendChild(titleWrapper);

			var imageWrapper = BX.create("DIV", { attrs: { className: "crm-client-selector-tab-resp-img" } });
			var imageUrl = this._entityInfo.getLargeImageUrl();
			if(imageUrl !== "")
			{
				imageWrapper.appendChild(BX.create("IMG", { attrs: { src: imageUrl } }));
			}
			titleWrapper.appendChild(imageWrapper);

			var denomWrapper = BX.create("DIV", { attrs: { className: "crm-client-selector-tab-title-name-block" } });
			titleWrapper.appendChild(denomWrapper);

			denomWrapper.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-client-selector-tab-title-name" },
						children:
							[
								BX.create("A",
									{
										attrs:
										{
											className: "crm-offer-tab-title-name",
											href: this._entityInfo.getShowUrl(),
											target: "_blank"
										},
										text: this._entityInfo.getTitle()
									}
								)
							]
					}
				)
			);

			denomWrapper.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-client-selector-tab-title-descript" },
						text: this._entityInfo.getDescription()
					}
				)
			);

			if(!this._readOnly)
			{
				var delBtn = BX.create("DIV", { attrs: { className: "crm-client-selector-tab-close-btn" } });
				titleWrapper.appendChild(delBtn);
				BX.bind(delBtn, "click", this._deleteButtonHandler);
			}

			//region Tabs
			var enableMainTab = this._entityInfo.hasMultiFields();
			var enableRequisiteTab = this._enableRequisites && this._entityInfo.hasRequisites();
			if(enableMainTab || enableRequisiteTab)
			{
				var contentWrapper = BX.create("DIV", { attrs: { className: "crm-client-selector-tab-main-cont-wrap" } });
				innerWrapper.appendChild(contentWrapper);
				var tabWrapper = BX.create("DIV", { attrs: { className: "crm-client-selector-tab-list-wrap" } });
				contentWrapper.appendChild(tabWrapper);

				var tabContentWrapper = BX.create("DIV", { attrs: { className: "crm-client-selector-tab-cont-wrap" } });
				contentWrapper.appendChild(tabContentWrapper);

				//region Main tab
				if(enableMainTab)
				{
					var mainTabTitle = "";
					if(typeName === BX.CrmEntityType.names.contact)
					{
						mainTabTitle = this.getMessage("contact");
					}
					else if(typeName === BX.CrmEntityType.names.company)
					{
						mainTabTitle = this.getMessage("company");
					}

					var mainTab = BX.create("SPAN",
						{
							attrs: { className: "crm-offer-tab crm-client-selector-tab-active" },
							children:
								[
									BX.create("SPAN",
										{
											attrs: { className: "crm-client-selector-tab-text" },
											text: mainTabTitle
										}
									)
								]
						}
					);
					tabWrapper.appendChild(mainTab);
					BX.bind(mainTab, "click", this._mainTabHandler);

					var mainTabWrapper = BX.create("DIV", { attrs: { className: "crm-client-selector-tab-cont" } });
					tabContentWrapper.appendChild(mainTabWrapper);
					this._tabs["main"] = { button: mainTab, node: mainTabWrapper };
				}
				//endregion
				//region Requisite tab
				if(enableRequisiteTab)
				{
					var requisiteTabTitle = "";
					if(typeName === BX.CrmEntityType.names.contact)
					{
						requisiteTabTitle = this.getMessage("contactRequisites");
					}
					else if(typeName === BX.CrmEntityType.names.company)
					{
						requisiteTabTitle = this.getMessage("companyRequisites");
					}

					var requisiteTab = BX.create("SPAN",
						{
							attrs: { className: "crm-offer-tab" },
							children:
								[
									BX.create("SPAN",
										{
											attrs: { className: "crm-client-selector-tab-text" },
											text: requisiteTabTitle
										}
									)
								]
						}
					);
					tabWrapper.appendChild(requisiteTab);
					BX.bind(requisiteTab, "click", this._requisiteTabHandler);

					var requisiteTabWrapper = BX.create("DIV", { attrs: { className: "crm-client-selector-tab-cont" } });
					tabContentWrapper.appendChild(requisiteTabWrapper);
					this._tabs["requisite"] = { button: requisiteTab, node: requisiteTabWrapper };
				}
				//endregion
			}

			if(enableMainTab)
			{
				this.setActiveTab("main");
			}
			else if(enableRequisiteTab)
			{
				this.setActiveTab("requisite");
			}
			//endregion
			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(this._multifieldPanel)
			{
				this._multifieldPanel.clearLayout();
				this._multifieldPanel = null;
			}

			if(this._wrapper)
			{
				this._wrapper = BX.remove(this._wrapper);
			}

			this._hasLayout = false;
		},
		setActiveTab: function(tabName)
		{
			if(tabName !== "main" && tabName !== "requisite")
			{
				tabName = "main";
			}

			if(this._activeTab === tabName || typeof(this._tabs[tabName]) === "undefined")
			{
				return;
			}

			var activeTab = this._tabs[tabName];
			if(tabName === "main")
			{
				if(!this._multifieldPanel)
				{
					this._multifieldPanel = BX.CrmClientMultiFieldPanel.create(this._id,
						{
							container: activeTab["node"],
							entityInfo: this._entityInfo
						}
					);
					this._multifieldPanel.layout();
				}
			}
			else if(tabName === "requisite")
			{
				if(!this._requisitePanel)
				{
					this._requisitePanel = BX.CrmClientRequisitePanel.create(this._id,
						{
							container: activeTab["node"],
							entityInfo: this._entityInfo,
							requisiteInfo: this._requisiteInfo,
							requisiteServiceUrl: this.getSetting("requisiteServiceUrl"),
							readOnly: (this._readOnly || !this._enableRequisiteChange)
						}
					);
					this._requisitePanel.addChangeListener(this._requisiteChangeHandler);
					this._requisitePanel.layout();
				}
			}

			for(var key in this._tabs)
			{
				if(!this._tabs.hasOwnProperty(key))
				{
					continue;
				}

				var tab = this._tabs[key];
				if(tabName === key)
				{
					tab["node"].style.display = "";
					BX.addClass(tab["button"], "crm-client-selector-tab-active");
				}
				else
				{
					tab["node"].style.display = "none";
					BX.removeClass(tab["button"], "crm-client-selector-tab-active");
				}
			}

			this._activeTab = tabName;
		},
		addRequisiteChangeListener: function(listener)
		{
			this._requisiteChangeNotifier.addListener(listener);
		},
		removeRequisiteChangeListener: function(listener)
		{
			this._requisiteChangeNotifier.removeListener(listener);
		},
		addDeletionListener: function(listener)
		{
			this._deletionNotifier.addListener(listener);
		},
		removeDeletionListener: function(listener)
		{
			this._deletionNotifier.removeListener(listener);
		},
		onMainTabClick: function(e)
		{
			this.setActiveTab("main");
		},
		onRequisiteTabClick: function(e)
		{
			this.setActiveTab("requisite");
		},
		onDeleteButtonClick: function(e)
		{
			this._deletionNotifier.notify();
		},
		onRequisiteChange: function(sender, requisiteId, bankDetailId)
		{
			this._requisiteChangeNotifier.notify([requisiteId, bankDetailId]);
		}
	};
	if(typeof(BX.CrmClientPanel.messages) === "undefined")
	{
		BX.CrmClientPanel.messages = {};
	}
	BX.CrmClientPanel.create = function(id, settings)
	{
		var self = new BX.CrmClientPanel();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmClientMultiFieldPanel
if(typeof(BX.CrmClientMultiFieldPanel) === "undefined")
{
	BX.CrmClientMultiFieldPanel = function()
	{
		this._id = "";
		this._settings = {};
		this._hasLayout = false;
		this._container = null;
		this._wrapper = null;
		this._entityInfo = null;
		this._items = [];
	};
	BX.CrmClientMultiFieldPanel.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "CRM_CLIENT_MULTIFIELDS" + Math.random();
			this._settings = settings ? settings : {};

			this._entityInfo = this.getSetting("entityInfo");

			if(!(this._entityInfo instanceof BX.CrmEntityInfo))
			{
				throw "CrmClientMultiFieldPanel: Could not find 'entityInfo' parameter in settings.";
			}

			this._container = this.getSetting("container");
			if(!BX.type.isElementNode(this._container))
			{
				throw "CrmClientMultiFieldPanel: Could not find 'container' parameter in settings.";
			}
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getEntityId: function()
		{
			return this._entityInfo.getId();
		},
		getEntityTypeName: function()
		{
			return this._entityInfo.getTypeName();
		},
		createItem: function(fieldInfo, container)
		{
			var item = BX.CrmClientPanelCommunication.create(
				"",
				{
					container: container,
					entityId: this.getEntityId(),
					entityType: this.getEntityTypeName(),
					fieldInfo: fieldInfo
				});
			item.layout();
			this._items.push(item);
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			var wrapper = this._wrapper = BX.create("DIV", { attrs: { className: "crm-client-selector-tab-info" } });
			this._container.appendChild(wrapper);

			var table, row, cell, i;

			table = BX.create("TABLE", { attrs: { className: "crm-client-selector-tab-table" } });
			wrapper.appendChild(table);

			row = table.insertRow(-1);
			var fields, qty;
			//region Phones
			fields = this._entityInfo.getPhones();
			qty = fields.length;
			if(qty > 0)
			{
				cell = row.insertCell(-1);
				cell.className = "crm-client-selector-tab-cell";

				for(i = 0; i < qty; i++)
				{
					this.createItem(fields[i], cell);
				}
			}
			//endregion
			//region Emails
			fields = this._entityInfo.getEmails();
			qty = fields.length;
			if(qty > 0)
			{
				cell = row.insertCell(-1);
				cell.className = "crm-client-selector-tab-cell";

				for(i = 0; i < qty; i++)
				{
					this.createItem(fields[i], cell);
				}
			}
			//endregion

			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(this._items.length > 0)
			{
				for(var i = 0; i < this._items.length; i++)
				{
					this._items[i].clearLayout();
				}
				this._items = [];
			}

			if(this._wrapper)
			{
				this._wrapper = BX.remove(this._wrapper);
			}

			this._hasLayout = false;
		}
	};
	BX.CrmClientMultiFieldPanel.create = function(id, settings)
	{
		var self = new BX.CrmClientMultiFieldPanel();
		self.initialize(id, settings);
		return self;
	}
}
//endregion
//region BX.CrmClientPanelCommunication
if(typeof(BX.CrmClientPanelCommunication) === "undefined")
{
	BX.CrmClientPanelCommunication = function()
	{
		this._id = "";
		this._settings = {};
		this._entityTypeName = "";
		this._entityId = 0;
		this._fieldInfo = {};
		this._hasLayout = false;
		this._container = null;
		this._wrapper = null;
		this._link = null;
		this._button = null;
		this._buttonHandler = BX.delegate(this.onButtonClick, this);
	};
	BX.CrmClientPanelCommunication.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "CRM_CLIENT_COMM" + Math.random();
			this._settings = settings ? settings : {};

			this._fieldInfo = this.getSetting("fieldInfo");
			if(!BX.type.isPlainObject(this._fieldInfo))
			{
				throw "CrmClientPanelCommunication: Could not find 'fieldInfo' parameter in settings.";
			}

			this._entityTypeName = this.getSetting("entityType", "");
			this._entityId = this.getSetting("entityId", 0);

			this._container = this.getSetting("container");
			if(!BX.type.isElementNode(this._container))
			{
				throw "CrmClientPanelCommunication: Could not find 'container' parameter in settings.";
			}
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getTypeName: function()
		{
			return BX.type.isNotEmptyString(this._fieldInfo["TYPE"]) ? this._fieldInfo["TYPE"] : "";
		},
		getValue: function()
		{
			return BX.type.isNotEmptyString(this._fieldInfo["VALUE"]) ? this._fieldInfo["VALUE"] : "";
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			var wrapper = this._wrapper = BX.create("DIV", { attrs: { className: "crm-client-contacts-block" } });
			this._container.appendChild(wrapper);

			var innerWrapper, link, btn;

			var type = this.getTypeName();
			var value = this.getValue();
			var valueHtml = BX.util.htmlspecialchars(value);

			if(type === "PHONE")
			{
				var callToFormat = BX.CrmClientPanelCommunication.callToFormat;
				innerWrapper = BX.create("SPAN",
					{
						attrs: { className: "crm-client-contacts-block-text crm-client-contacts-block-handset" }
					}
				);
				wrapper.appendChild(innerWrapper);

				this._link = BX.create("A",
					{
						attrs: { className: "crm-client-contacts-block-text-tel" },
						props:
							{
								href: (callToFormat === BX.CrmCalltoFormat.standard ? "callto://" : "callto:") + valueHtml,
								title: valueHtml
							},
						text: value
					}
				);

				if(callToFormat === BX.CrmCalltoFormat.bitrix)
				{
					BX.bind(this._link, "click", this._buttonHandler);
				}

				innerWrapper.appendChild(this._link);

				this._button = BX.create("SPAN", { attrs: { className: "crm-client-contacts-block-text-tel-icon" } });
				innerWrapper.appendChild(this._button);
				BX.bind(this._button, "click", this._buttonHandler);
			}
			else if(type === "EMAIL")
			{
				innerWrapper = BX.create("SPAN",
					{
						attrs: { className: "crm-client-contacts-block-text crm-client-contacts-block-handset" }
					}
				);
				wrapper.appendChild(innerWrapper);

				this._link = BX.create("A",
					{
						attrs: { className: "crm-client-contacts-block-text-mail" },
						props:
							{
								href: "mailto:" + valueHtml,
								title: valueHtml
							},
						text: value
					}
				);
				innerWrapper.appendChild(this._link);

				this._button = BX.create("SPAN", { attrs: { className: "crm-client-contacts-block-text-mail-icon" } });
				innerWrapper.appendChild(this._button);
				BX.bind(this._button, "click", this._buttonHandler);
			}

			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(this._link)
			{
				this._link = BX.remove(this._link);
			}

			if(this._button)
			{
				BX.unbind(this._button, "click", this._buttonHandler);
				this._button = BX.remove(this._button);
			}

			if(this._wrapper)
			{
				this._wrapper = BX.remove(this._wrapper);
			}

			this._hasLayout = false;
		},
		onButtonClick: function(e)
		{
			var type = this.getTypeName();

			if(type === "EMAIL")
			{
				this._link.click();
			}
			else if(type === "PHONE" && typeof(BX.CrmSipManager) !== 'undefined')
			{
				BX.CrmSipManager.startCall(
					{
						number: this.getValue(),
						enableInfoLoading: true
					},
					{
						ENTITY_TYPE:  BX.CrmSipManager.resolveSipEntityTypeName(this._entityTypeName),
						ENTITY_ID: this._entityId
					},
					true,
					this._button
				);
			}

			return BX.PreventDefault(e);
		}
	};
	if(typeof(BX.CrmClientPanelCommunication.callToFormat) !== "undefined")
	{
		BX.CrmClientPanelCommunication.callToFormat = BX.CrmCalltoFormat.bitrix;
	}
	BX.CrmClientPanelCommunication.create = function(id, settings)
	{
		var self = new BX.CrmClientPanelCommunication();
		self.initialize(id, settings);
		return self;
	}
}
//endregion
//region BX.CrmClientRequisitePanel
if(typeof(BX.CrmClientRequisitePanel) === "undefined")
{
	BX.CrmClientRequisitePanel = function()
	{
		this._id = "";
		this._settings = {};
		this._hasLayout = false;
		this._container = null;
		this._wrapper = null;
		this._entityInfo = null;
		this._requisiteInfo = null;
		this._requisiteId = 0;
		this._bankDetailId = 0;
		this._readOnly = false;
		this._selectorName = "";
		this._items = null;

		this._changeNotifier = null;

		this._itemSelectHandler = BX.delegate(this.onItemSelect, this);
		this._itemBankDetailSelectHandler = BX.delegate(this.onItemBankDetailSelect, this);
	};
	BX.CrmClientRequisitePanel.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "CRM_CLIENT_REQUISITE" + Math.random();
			this._settings = settings ? settings : {};

			this._entityInfo = this.getSetting("entityInfo");
			if(!(this._entityInfo instanceof BX.CrmEntityInfo))
			{
				throw "CrmClientRequisitePanel: Could not find 'entityInfo' parameter in settings.";
			}

			this._requisiteInfo = this.getSetting("requisiteInfo");
			if(!(this._requisiteInfo instanceof BX.CrmEntityRequisiteInfo))
			{
				throw "CrmClientRequisitePanel: Could not find 'requisiteInfo' parameter in settings.";
			}

			this._requisiteId = this._requisiteInfo.getRequisiteId();
			this._bankDetailId = this._requisiteInfo.getBankDetailId();

			this._container = this.getSetting("container");
			if(!BX.type.isElementNode(this._container))
			{
				throw "CrmClientRequisitePanel: Could not find 'container' parameter in settings.";
			}

			this._readOnly = !!this.getSetting("readOnly");
			this._changeNotifier = BX.CrmNotifier.create(this);
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getRequisiteId: function()
		{
			return this._requisiteId;
		},
		setRequisiteId: function(requisiteId)
		{
			this._requisiteId = requisiteId;
			this._changeNotifier.notify([this._requisiteId, this._bankDetailId]);
		},
		getBankDetailId: function()
		{
			return this._bankDetailId;
		},
		setBankDetailId: function(bankDetailId)
		{
			this._bankDetailId = bankDetailId;
			this._changeNotifier.notify([this._requisiteId, this._bankDetailId]);

			this.saveSettings();
		},
		setup: function(requisiteId, bankDetailId)
		{
			if(this._requisiteId === requisiteId && this._bankDetailId === bankDetailId)
			{
				return;
			}

			this._requisiteId = requisiteId;
			this._bankDetailId = bankDetailId;

			this._changeNotifier.notify([this._requisiteId, this._bankDetailId]);

			this.saveSettings();
		},
		saveSettings: function()
		{
			var url = this.getSetting("requisiteServiceUrl");
			if(!BX.type.isNotEmptyString(url))
			{
				return;
			}

			BX.ajax.post(url,
				{
					action: "savelastselectedrequisite",
					requisiteEntityTypeId: this._entityInfo.getTypeId(),
					requisiteEntityId: this._entityInfo.getId(),
					requisiteId: this._requisiteId,
					bankDetailId: this._bankDetailId
				}
			);
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			var form = this._wrapper = BX.create("FORM");
			this._container.appendChild(form);

			this._items = [];

			var requisites = this._requisiteInfo.getItems();
			var selectorName = this._selectorName = this._id;
			for(var i = 0; i < requisites.length; i++)
			{
				var requisite = requisites[i];

				if(!(BX.type.isNotEmptyString(requisite["viewData"]["title"])) && BX.type.isArray(requisite["viewData"]["fields"]))
				{
					continue;
				}

				var item = BX.CrmClientRequisitePanelItem.create(
					(selectorName + "_" + (this._items.length + 1)),
					{
						name: selectorName,
						container: form,
						data:
						{
							requisiteId: requisite["requisiteId"],
							entityTypeId: requisite["entityTypeId"],
							entityId: requisite["entityId"],
							viewData: requisite["viewData"],
							bankDetailViewDataList: requisite["bankDetailViewDataList"],
							bankDetailIdSelected: requisite["bankDetailIdSelected"]
						},
						isSelected: requisite["selected"],
						readOnly: this._readOnly
					}
				);
				this._items.push(item);

				item.addChangeListener(this._itemSelectHandler);
				item.addBankDetailSelectListener(this._itemBankDetailSelectHandler);

				item.layout();
			}

			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(this._wrapper)
			{
				this._wrapper = BX.remove(this._wrapper);
			}

			this._hasLayout = false;
		},
		addChangeListener: function(listener)
		{
			this._changeNotifier.addListener(listener);
		},
		removeChangeListener: function(listener)
		{
			this._changeNotifier.removeListener(listener);
		},
		onItemSelect: function(sender)
		{
			if(!sender.isSelected())
			{
				return;
			}

			for (var i = 0; i < this._items.length; i++)
			{
				if(this._items[i] !== sender)
				{
					this._items[i].setSelected(false, true);
				}
			}

			this.setup(sender.getRequisiteId(), sender.getBankingDetailId());
		},
		onItemBankDetailSelect: function(sender)
		{
			if(!sender.isSelected())
			{
				return;
			}

			this.setBankDetailId(sender.getBankingDetailId());
		}
	};
	BX.CrmClientRequisitePanel.create = function(id, settings)
	{
		var self = new BX.CrmClientRequisitePanel();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmClientRequisitePanelItem
if(typeof(BX.CrmClientRequisitePanelItem) === "undefined")
{
	BX.CrmClientRequisitePanelItem = function()
	{
		this._id = "";
		this._settings = {};
		this._name = "";
		this._readOnly = false;
		this._data = null;

		this._container = null;
		this._wrapper = null;
		this._selector = null;
		this._hasLayout = false;
		this._readOnly = false;

		this._isHighlighted = false;
		this._isSelected = false;
		this._selectHandler = BX.delegate(this.onSelect, this);
		this._bankDetailSelectHandler = BX.delegate(this.onBankDetailsSelect, this);
		this._clickHandler = BX.delegate(this.onClick, this);

		this._changeNotifier = null;
		this._bankDetailSelectNotifier = null;
	};
	BX.CrmClientRequisitePanelItem.prototype =
	{
		initialize: function (id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "CRM_CLIENT_REQUISITE_ITEM" + Math.random();
			this._settings = settings ? settings : {};

			this._name = this.getSetting("name", "");
			this._container = this.getSetting("container");
			if(!BX.type.isElementNode(this._container))
			{
				throw "CrmClientRequisitePanelItem: Could not find 'container' parameter in settings.";
			}

			this._data = this.getSetting("data");
			if(!BX.type.isPlainObject(this._data))
			{
				throw "CrmClientRequisitePanelItem: Could not find 'data' parameter in settings.";
			}

			this._isSelected = !!this.getSetting("isSelected");

			this._readOnly = !!this.getSetting("readOnly");

			this._changeNotifier = BX.CrmNotifier.create(this);
			this._bankDetailSelectNotifier = BX.CrmNotifier.create(this);
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getMessage: function(name)
		{
			var m = BX.CrmClientRequisitePanelItem.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		getRequisiteId: function()
		{
			return this._data["requisiteId"];
		},
		isSelected: function()
		{
			return this._isSelected;
		},
		setSelected: function(selected, disableNotify)
		{
			selected = !!selected;
			if(this._isSelected === selected)
			{
				return;
			}

			this._isSelected = selected;
			if(this._selector.checked != this._isSelected)
			{
				this._selector.checked = this._isSelected;
			}
			this.setHighlighted(this._isSelected);

			if(!disableNotify)
			{
				this._changeNotifier.notify();
			}
		},
		isHighlighted: function()
		{
			return this._isHighlighted;
		},
		setHighlighted: function(highlighted)
		{
			if(!this._hasLayout)
			{
				return;
			}

			highlighted = !!highlighted;
			if(this._isHighlighted === highlighted)
			{
				return;
			}

			this._isHighlighted = highlighted;
			this.adjust();
		},
		getBankingDetailId: function()
		{
			return this._data["bankDetailIdSelected"];
		},
		setBankingDetailId: function(id)
		{
			if(!BX.type.isNumber(id))
			{
				id = parseInt(id);
			}

			if(isNaN(id) || id < 0)
			{
				id = 0;
			}

			this._data["bankDetailIdSelected"] = id;
		},
		getData: function()
		{
			return this._data;
		},
		save: function()
		{
			this._isSelected = this._selector.checked;
		},
		adjust: function()
		{
			if(this._isSelected)
			{
				BX.addClass(this._wrapper, "crm-offer-requisite-active");
			}
			else
			{
				BX.removeClass(this._wrapper, "crm-offer-requisite-active");
			}
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			this._selector = BX.create("INPUT",
				{
					attrs:
					{
						id: this._id,
						className: "crm-offer-requisite-inp",
						type: "radio",
						name: this._name,
						value: this._data["requisiteId"]
					},
					props: { checked: this._isSelected }
				}
			);

			if(this._readOnly)
			{
				this._selector.disabled = "disabled";
			}
			else
			{
				BX.bind(this._selector, "click", this._selectHandler);
			}

			this._wrapper = BX.create("DIV",
				{
					attrs: { className: "crm-offer-requisite" },
					children:
						[
							BX.create("DIV",
								{
									attrs: { className: "crm-offer-requisite-title" },
									children:
										[
											this._selector,
											BX.create("LABEL",
												{
													attrs:
													{
														className: "crm-offer-requisite-lable",
														"for": this._id
													},
													html: BX.util.htmlspecialchars(this._data["viewData"]["title"])
												}
											)
										]
								}
							)
						]
				}
			);
			this._container.appendChild(this._wrapper);
			if(!this._readOnly)
			{
				BX.bind(this._wrapper, "click", this._clickHandler);
			}

			var fields = this._data["viewData"]["fields"];
			var bankingData = this._data["bankDetailViewDataList"];

			if (fields.length > 0 || bankingData.length > 0)
			{
				var table = BX.create("TABLE", { attrs: { className: "crm-offer-tab-table"} });
				this._wrapper.appendChild(table);
				for (var l = 0; l < fields.length; l++)
				{
					var field = fields[l];
					var row = table.insertRow(-1);

					var cell = row.insertCell(-1);
					cell.className = "crm-offer-tab-cell";
					cell.innerHTML = (field["title"] ? BX.util.htmlspecialchars(field["title"]) : "") + ":";

					cell = row.insertCell(-1);
					cell.className = "crm-offer-tab-cell";
					cell.innerHTML = (field["textValue"]) ? BX.util.nl2br(BX.util.htmlspecialchars(field["textValue"])) : "";
				}

				var bankingNode = this.prepareBankDetails(this._data);
				if (bankingNode)
				{
					row = table.insertRow(-1);
					cell = row.insertCell(-1);
					cell.className = "crm-offer-tab-cell";
					cell.innerHTML = BX.util.htmlspecialchars(this.getMessage("bankDetails") + ":");
					cell = row.insertCell(-1);
					cell.className = "crm-offer-tab-cell";
					cell.appendChild(bankingNode);
				}
			}

			this._hasLayout = true;
			this.setHighlighted(this._isSelected);
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(this._selector && !this._readOnly)
			{
				BX.unbind(this._selector, "click", this._selectHandler);
			}

			if(this._wrapper)
			{
				BX.unbind(this._wrapper, "click", this._clickHandler);
				this._wrapper = BX.remove(this._wrapper);
			}

			this._hasLayout = false;
		},
		prepareBankDetails: function(item)
		{
			if (!BX.type.isPlainObject(item))
			{
				return null;
			}

			var requisiteId = parseInt(item["requisiteId"]);
			var bankingItems = BX.type.isArray(item["bankDetailViewDataList"]) ? item["bankDetailViewDataList"] : [];
			if (!(requisiteId > 0 && bankingItems.length > 0))
			{
				return null;
			}

			var node = null;
			var selectorName = this._id + "_RQ_" + requisiteId + "_BD";
			for (var i = 0; i < bankingItems.length; i++)
			{
				var bankingItem = bankingItems[i];
				if (!(typeof(bankingItem["pseudoId"]) !== "undefined"
					&& BX.type.isPlainObject(bankingItem["viewData"])
					&& BX.type.isNotEmptyString(bankingItem["viewData"]["title"])
					&& BX.type.isArray(bankingItem["viewData"]["fields"])))
				{
					continue;
				}

				var pseudoId = bankingItem["pseudoId"];
				var selectorId = selectorName + "_" + pseudoId;

				if(node === null)
				{
					node = BX.create("DIV");
				}

				var selector = BX.create("INPUT",
					{
						attrs:
						{
							id: selectorId,
							className: "crm-offer-bankdetail-inp",
							type: "radio",
							name: selectorName,
							value: pseudoId
						},
						props: { checked: bankingItem["selected"] === true },
						events: { click: this._bankDetailSelectHandler }
					}
				);

				if(this._readOnly)
				{
					selector.disabled = "disabled";
				}

				node.appendChild(
					BX.create("DIV",
						{
							children:
								[
									selector,
									BX.create("LABEL",
										{
											attrs: { "for": selectorId },
											html: BX.util.htmlspecialchars(bankingItem["viewData"]["title"])
										}
									)
								]
						}
					)
				);
			}
			return node;
		},
		addChangeListener: function(listener)
		{
			this._changeNotifier.addListener(listener);
		},
		removeChangeListener: function(listener)
		{
			this._changeNotifier.removeListener(listener);
		},
		addBankDetailSelectListener: function(listener)
		{
			this._bankDetailSelectNotifier.addListener(listener);
		},
		removeBankDetailListener: function(listener)
		{
			this._bankDetailSelectNotifier.removeListener(listener);
		},
		onSelect: function(e)
		{
			this.setSelected(this._selector.checked);
		},
		onClick: function(e)
		{
			if(!this._readOnly && !this._isSelected)
			{
				this.setSelected(true);
			}
		},
		onBankDetailsSelect: function(e)
		{
			e = e || window.event;
			var target = e.target || e.srcElement;
			if(BX.type.isElementNode(target))
			{
				this.setBankingDetailId(target.value);
				this._bankDetailSelectNotifier.notify();
			}
		}
	};
	if(typeof(BX.CrmClientRequisitePanelItem.messages) === "undefined")
	{
		BX.CrmClientRequisitePanelItem.messages = {};
	}
	BX.CrmClientRequisitePanelItem.create = function(id, settings)
	{
		var self = new BX.CrmClientRequisitePanelItem();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmCompositeClientSelector
if(typeof(BX.CrmCompositeClientSelector) === "undefined")
{
	BX.CrmCompositeClientSelector = function()
	{
		this._id = "";
		this._settings = {};

		this._owner = null;

		this._primaryEntityInfo = null;
		this._secondaryEntityInfos = null;
		this._secondaryEntityTypeName = "";
		this._additionalData = null;

		this._primaryView = null;
		this._auxiliaryGroup = null;

		this._primaryEntityTypeInpuId = "";
		this._primaryEntityInputId = "";
		this._secondaryEntitiesInputId = "";
		this._requisiteInputId = "";
		this._bankDetailInputId = "";

		this._containerId = "";
		this._container = null;
		this._wrapper = null;
		this._waiterWrapper = null;

		this._enableMultiplicity = true;
		this._readOnly = false;
		this._hasLayout = false;

		this._entityTypeName = "";
		this._entitySelectorId = "";
		this._typeSelectorButton = null;
		this._typeSelectorButtonContent = null;
		this._entitySelectorButton = null;
		this._entityCreationButton = null;
		this._selectorMenu = null;

		this._externalRequestData = null;
		this._externalEventHandler = null;

		this._typeSelectorClickHandler = BX.delegate(this.onTypeSelectorClick, this);
		this._entitySelectClickHandler = BX.delegate(this.onEntitySelectClick, this);
		this._entityCreateClickHandler = BX.delegate(this.onEntityCreateClick, this);
		this._typeSelectHandler = BX.delegate(this.onTypeSelect, this);
		this._entitySelectHandler = BX.delegate(this.onEntitySelect, this);
		this._contactGroupChangeHandler = BX.delegate(this.onContactGroupChange, this);
		this._deletePrimaryViewHandler = BX.delegate(this.onPrimaryViewDelete, this);
		this._requisiteChangeHandler = BX.delegate(this.onRequisiteChange, this);
		this._waiterTimeoutId = 0;
	};
	BX.CrmCompositeClientSelector.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._containerId = this.getSetting("containerId", "");
			if(!BX.type.isNotEmptyString(this._containerId))
			{
				throw "CrmCompositeClientSelector: Could not find 'containerId' parameter in settings.";
			}

			this._container = BX(this._containerId);
			if(!BX.type.isElementNode(this._container))
			{
				throw "CrmCompositeClientSelector: Could not find container element.";
			}

			this._owner = this.getSetting("owner", null);
			if(!BX.type.isPlainObject(this._owner))
			{
				throw "CrmCompositeClientSelector: Could not find owner config.";
			}

			this._readOnly = !!this.getSetting("readOnly", false);
			this._enableMultiplicity = !!this.getSetting("enableMultiplicity", true);

			//region Data input IDs
			if(!this._readOnly)
			{
				this._primaryEntityTypeInpuId = this.getSetting("primaryEntityTypeInpuId", "");
				this._primaryEntityInputId = this.getSetting("primaryEntityInputId", "");
				this._secondaryEntitiesInputId = this.getSetting("secondaryEntitiesInputId", "");

				this._requisiteInputId = this.getSetting("requisiteInputId", "");
				this._bankDetailInputId = this.getSetting("bankDetailInputId", "");
			}
			//endregion

			var additionalData = this.getSetting("additionalData");
			this._additionalData = BX.type.isPlainObject(additionalData) ? additionalData : {};

			var primaryEntityData = this.getSetting("primaryEntityData");
			var primaryEntityInfo = BX.type.isPlainObject(primaryEntityData) ? BX.CrmEntityInfo.create(primaryEntityData) : null;
			this.setPrimaryEntity(primaryEntityInfo);
			if(primaryEntityInfo)
			{
				this.setEntityTypeName(primaryEntityInfo.getTypeName());
			}
			else
			{
				this.setEntityTypeName(this.getSetting("primaryEntityType"));
			}

			this._secondaryEntityTypeName = this.getSetting("secondaryEntityType");
			var secondaryEntityData = this.getSetting("secondaryEntityData");
			if(!BX.type.isArray(secondaryEntityData))
			{
				secondaryEntityData = [];
			}

			this._secondaryEntityInfos = [];
			for(var i = 0; i < secondaryEntityData.length; i++)
			{
				var info = BX.CrmEntityInfo.create(secondaryEntityData[i]);
				if(info.getId() > 0)
				{
					this._secondaryEntityInfos.push(info);
				}
			}

			this._entitySelectorId = this._id + "_PRIMARY_ENTITY_SELECTOR";

			var selectorSearchOptions = this.getSetting("selectorSearchOptions", {});
			this._selectorSearchOptions = BX.type.isPlainObject(selectorSearchOptions) ? selectorSearchOptions : {};
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getMessage: function(name)
		{
			var m = BX.CrmCompositeClientSelector.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		getEntityTypeName: function()
		{
			return this._entityTypeName;
		},
		setEntityTypeName: function(entityType)
		{
			if(this._entityTypeName === entityType)
			{
				return;
			}

			this._entityTypeName = entityType;
			if(this._hasLayout)
			{
				this._typeSelectorButtonContent.innerHTML = BX.util.htmlspecialchars(
					BX.CrmEntityType.getCaptionByName(this._entityTypeName) + " "
				);

				this.prepareEntitySelector();
			}
		},
		getPrimaryEntity: function()
		{
			return this._primaryEntityInfo;
		},
		setPrimaryEntity: function(entityInfo)
		{
			var entityTypeInput = BX(this._primaryEntityTypeInpuId);
			var entityIdInput = BX(this._primaryEntityInputId);

			if(this._primaryEntityInfo)
			{
				this._primaryEntityInfo = null;

				if(entityTypeInput)
				{
					entityTypeInput.value = "";
				}

				if(entityIdInput)
				{
					entityIdInput.value = "";
				}
			}

			if(this._primaryEntityRequisiteInfo)
			{
				this._primaryEntityRequisiteInfo = null;
			}

			if(entityInfo instanceof BX.CrmEntityInfo)
			{
				this._primaryEntityInfo = entityInfo;

				if(entityTypeInput)
				{
					entityTypeInput.value = this._primaryEntityInfo.getTypeName();
				}

				if(entityIdInput)
				{
					entityIdInput.value = this._primaryEntityInfo.getId();
				}

				this.prepareRequisiteInfo();
			}

			this.notify("primaryEntity");
		},
		prepareRequisiteInfo: function()
		{
			this._primaryEntityRequisiteInfo = BX.CrmEntityRequisiteInfo.create(
				{
					requisiteId: this.getRequisiteId(),
					bankDetailId: this.getBankDetailId(),
					data: this._primaryEntityInfo.getRequisites()
				}
			);

			if(this._primaryEntityRequisiteInfo.getRequisiteId() !== this.getRequisiteId())
			{
				this.setRequisiteId(this._primaryEntityRequisiteInfo.getRequisiteId());
			}

			if(this._primaryEntityRequisiteInfo.getBankDetailId() !== this.getBankDetailId())
			{
				this.setBankDetailId(this._primaryEntityRequisiteInfo.getBankDetailId());
			}
		},
		findSecondaryEntityById: function(id)
		{
			if(!BX.type.isNumber(id))
			{
				id = parseInt(id);
			}

			if(isNaN(id) || id <= 0)
			{
				return -1;
			}

			for(var i = 0; i < this._secondaryEntityInfos.length; i++)
			{
				if(this._secondaryEntityInfos[i].getId() == id)
				{
					return i;
				}
			}

			return -1;
		},
		getSecondaryEntityById: function(id)
		{
			var index = this.findSecondaryEntityById(id);
			return index >= 0 ? this._secondaryEntityInfos[index] : null;
		},
		addSecondaryEntity: function(entityInfo, index)
		{
			if(!(entityInfo instanceof BX.CrmEntityInfo))
			{
				return false;
			}

			if(index === undefined || index < 0)
			{
				index = this._secondaryEntityInfos.length;
			}

			if(this.findSecondaryEntityById(entityInfo.getId()) >= 0)
			{
				return false;
			}

			if(index === this._secondaryEntityInfos.length)
			{
				this._secondaryEntityInfos.push(entityInfo);
			}
			else
			{
				this._secondaryEntityInfos.splice(index, 0, entityInfo);
			}

			var input = BX(this._secondaryEntitiesInputId);
			if(input)
			{
				input.value = this.getSecondaryEntityIds().join(",");
			}

			this.notify("entity");
			return true;
		},
		moveSecondaryEntity: function(entityInfo, index)
		{
			if(!(entityInfo instanceof BX.CrmEntityInfo))
			{
				return false;
			}

			if(index === undefined || index < 0)
			{
				index = this._secondaryEntityInfos.length;
			}

			var currentIndex = this.findSecondaryEntityById(entityInfo.getId());
			if(currentIndex < 0 || currentIndex === index)
			{
				return false;
			}

			entityInfo = this._secondaryEntityInfos[currentIndex];
			this._secondaryEntityInfos.splice(currentIndex, 1);
			if(index >= this._secondaryEntityInfos.length)
			{
				this._secondaryEntityInfos.push(entityInfo);
			}
			else
			{
				this._secondaryEntityInfos.splice(index, 0, entityInfo);
			}

			var input = BX(this._secondaryEntitiesInputId);
			if(input)
			{
				input.value = this.getSecondaryEntityIds().join(",");
			}

			this.notify("entity");
			return true;
		},
		removeSecondaryEntity: function(entityInfo)
		{
			if(!(entityInfo instanceof BX.CrmEntityInfo))
			{
				return false;
			}

			var index = this.findSecondaryEntityById(entityInfo.getId());
			if(index < 0)
			{
				return false;
			}

			this._secondaryEntityInfos.splice(index, 1);
			var input = BX(this._secondaryEntitiesInputId);
			if(input)
			{
				input.value = this.getSecondaryEntityIds().join(",");
			}
			this.notify("entity");
			return true;
		},
		removeAllSecondaryEntities: function()
		{
			this._secondaryEntityInfos = [];
			var input = BX(this._secondaryEntitiesInputId);
			if(input)
			{
				input.value = "";
			}
			this.notify("entity");
		},
		getSecondaryEntityIds: function()
		{
			var result = [];
			for(var i = 0; i < this._secondaryEntityInfos.length; i++)
			{
				result.push(this._secondaryEntityInfos[i].getId());
			}
			return result;
		},
		getRequisiteId: function()
		{
			return BX.type.isNumber(this._additionalData["requisiteId"]) ? this._additionalData["requisiteId"] : 0;
		},
		setRequisiteId: function(id)
		{
			if(!BX.type.isNumber(id))
			{
				id = parseInt(id);
			}

			if(isNaN(id) || id < 0)
			{
				id = 0;
			}

			if(this._additionalData["requisiteId"] === id)
			{
				return;
			}

			this._additionalData["requisiteId"] = id;
			var input = BX(this._requisiteInputId);
			if(input)
			{
				input.value = id > 0 ? id.toString() : "";
			}
		},
		getBankDetailId: function()
		{
			return BX.type.isNumber(this._additionalData["bankDetailId"]) ? this._additionalData["bankDetailId"] : 0;
		},
		setBankDetailId: function(id)
		{
			if(!BX.type.isNumber(id))
			{
				id = parseInt(id);
			}

			if(isNaN(id) || id < 0)
			{
				id = 0;
			}

			if(this._additionalData["bankDetailId"] === id)
			{
				return;
			}

			this._additionalData["bankDetailId"] = id;
			var input = BX(this._bankDetailInputId);
			if(input)
			{
				input.value = id > 0 ? id.toString() : "";
			}
		},
		getContext: function()
		{
			return this.getSetting("context", "");
		},
		getCreateCompanyUrl: function()
		{
			return this.getSetting("createCompanyUrl", "");
		},
		getCreateContactUrl: function()
		{
			return this.getSetting("createContactUrl", "");
		},
		getCreateUrl: function()
		{
			if(this._entityTypeName === BX.CrmEntityType.names.company)
			{
				return this.getCreateCompanyUrl();
			}
			else if(this._entityTypeName === BX.CrmEntityType.names.contact)
			{
				return this.getCreateContactUrl();
			}

			return "";
		},
		prepareEntitySelector: function()
		{
			if(this._readOnly)
			{
				return;
			}

			if (obCrm[this._entitySelectorId])
			{
				obCrm[this._entitySelectorId].Clear();
				delete obCrm[this._entitySelectorId];
			}

			CRM.Set(
				this._entitySelectorButton,
				this._entitySelectorId,
				"",
				{},
				false,
				false,
				[this.getEntityTypeName().toLowerCase()],
				this.getSetting("selectorMessages"),
				true,
				{
					requireRequisiteData: true,
					searchOptions: this._selectorSearchOptions
				}
			);

			obCrm[this._entitySelectorId].AddOnSaveListener(this._entitySelectHandler);
		},
		prepareViews: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			this.showWaiter();

			if(this._primaryView)
			{
				this._primaryView.clearLayout();
				this._primaryView = null;
			}

			if(this._auxiliaryGroup)
			{
				this._auxiliaryGroup.clearLayout();
				this._auxiliaryGroup = null;
			}

			var secondaryEntityInfos = [];
			if(!this._primaryEntityInfo)
			{
				for(var i = 0; i < this._secondaryEntityInfos.length; i++)
				{
					secondaryEntityInfos.push(this._secondaryEntityInfos[i]);
				}
			}
			else
			{
				var skipPrimary = this._primaryEntityInfo.getTypeName() === BX.CrmEntityType.names.contact;
				for(var j = 0; j < this._secondaryEntityInfos.length; j++)
				{
					var entityInfo = this._secondaryEntityInfos[j];
					if(skipPrimary && BX.CrmEntityInfo.equals(entityInfo, this._primaryEntityInfo))
					{
						continue;
					}
					secondaryEntityInfos.push(entityInfo);
				}

				this._primaryView = BX.CrmEntitySummaryView.create(
					this._id,
					{
						entityInfo: this._primaryEntityInfo,
						requisiteInfo: this._primaryEntityRequisiteInfo,
						requisiteServiceUrl: this.getSetting("requisiteServiceUrl"),
						container: this._wrapper,
						readOnly: this._readOnly
					}
				);
				this._primaryView.addDeletionListener(this._deletePrimaryViewHandler);
				this._primaryView.addRequisiteChangeListener(this._requisiteChangeHandler);

				this._primaryView.layout();
			}

			if(!this._enableMultiplicity && this._primaryEntityInfo && this._primaryEntityInfo.getTypeName() === BX.CrmEntityType.names.contact)
			{
				this.hideWaiterAfter(200);
				return;
			}

			if(this._primaryEntityInfo || secondaryEntityInfos.length > 0)
			{
				var createUrl = this.getCreateContactUrl();
				var createUrlParams = {};
				//Add company binding if required
				if(this._primaryEntityInfo && this._primaryEntityInfo.getTypeName() === BX.CrmEntityType.names.company)
				{
					createUrlParams["company_id"] = this._primaryEntityInfo.getId();
				}

				var enableMarking = (this._primaryEntityInfo
						? this._primaryEntityInfo.getTypeName()
						: this._entityTypeName) === BX.CrmEntityType.names.contact;

				this._auxiliaryGroup = BX.CrmClientPanelGroup.create(
					this._id,
					{
						entityType: this._secondaryEntityTypeName,
						entityInfos: secondaryEntityInfos,
						context: this.getContext(),
						enableMarking: enableMarking,
						createUrl: createUrl,
						createUrlParams: createUrlParams,
						container: this._wrapper,
						selectorMessages: this.getSetting("selectorMessages"),
						messages: this.getSetting("secondaryEntityMessages"),
						enableMultiplicity: this._enableMultiplicity,
						readOnly: this._readOnly
					}
				);
				this._auxiliaryGroup.layout();
				this._auxiliaryGroup.addChangeListener(this._contactGroupChangeHandler);
			}

			this.hideWaiterAfter(200);
		},
		showWaiter: function()
		{
			if(this._waiterTimeoutId > 0)
			{
				window.clearTimeout(this._waiterTimeoutId);
				this._waiterTimeoutId = 0;
			}

			if(this._waiterWrapper)
			{
				return;
			}

			var waiter = BX.create("DIV", { attrs: { className: "crm-offer-tabs-wrap-slide-waiter" } });
			this._waiterWrapper = BX.create("DIV",
				{
					attrs: { className: "crm-client-selector-tab-block-wrap" },
					children: [ waiter ]
				}
			);

			var pos = BX.pos(this._wrapper);
			waiter.style.width = pos["width"] + "px";
			waiter.style.height = pos["height"] + "px";

			this._wrapper.style.display = "none";
			this._container.appendChild(this._waiterWrapper);
		},
		hideWaiterAfter: function(timeout)
		{
			if(this._waiterTimeoutId > 0)
			{
				window.clearTimeout(this._waiterTimeoutId);
				this._waiterTimeoutId = 0;
			}

			this._waiterTimeoutId = window.setTimeout(
				BX.delegate(this.hideWaiter, this),
				timeout > 0 ? timeout : 0
			);
		},
		hideWaiter: function()
		{
			if(!this._hasLayout || !this._waiterWrapper || this._waiterTimeoutId <= 0)
			{
				return;
			}

			this._waiterTimeoutId = 0;
			this._container.removeChild(this._waiterWrapper);
			this._waiterWrapper = null;
			this._wrapper.style.display = "";
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			this._wrapper = BX.create("DIV", { attrs: { className: "crm-client-selector-tabs-wrapper" } });
			this._container.appendChild(this._wrapper);

			if(!this._readOnly)
			{
				var buttonWrapper = BX.create("DIV", { attrs: { className: "crm-client-selector-type" } });
				this._wrapper.appendChild(buttonWrapper);

				this._typeSelectorButton = BX.create("DIV",
					{ attrs: { className: "crm-client-selector-type-item-list selected" } }
				);
				this._typeSelectorButtonContent = BX.create("SPAN",
					{ text: BX.CrmEntityType.getCaptionByName(this.getEntityTypeName()) + " " }
				);
				this._typeSelectorButton.appendChild(this._typeSelectorButtonContent);
				this._typeSelectorButton.appendChild(
					BX.create("SPAN", { attrs: { className: "crm-client-selector-arrow" } })
				);
				buttonWrapper.appendChild(this._typeSelectorButton);

				BX.bind(this._typeSelectorButton, "click", this._typeSelectorClickHandler);

				this._entitySelectorButton = BX.create("DIV",
					{
						attrs: { id: this._entitySelectorId, className: "crm-client-selector-type-item-select" },
						text: this.getMessage("selectButton")
					}
				);
				buttonWrapper.appendChild(this._entitySelectorButton);
				BX.bind(this._entitySelectorButton, "click", this._entitySelectClickHandler);

				this._entityCreationButton = BX.create("DIV",
					{
						attrs: { className: "crm-client-selector-type-item-create"},
						events: { click: this._entityCreateClickHandler },
						text: this.getMessage("createButton")
					}
				);
				buttonWrapper.appendChild(this._entityCreationButton);
				BX.bind(this._entityCreationButton, "click", this._entityCreateClickHandler);
			}

			this._hasLayout = true;
			this.prepareViews();
			this.prepareEntitySelector();
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(this._primaryView)
			{
				this._primaryView.clearLayout();
				this._primaryView = null;
			}

			if(this._auxiliaryGroup)
			{
				this._auxiliaryGroup.clearLayout();
				this._auxiliaryGroup = null;
			}

			this._typeSelectorButtonContent = null;
			if(this._typeSelectorButton)
			{
				BX.unbind(this._typeSelectorButton, "click", this._typeSelectorClickHandler);
				this._typeSelectorButton = null;
			}

			if(this._entitySelectorButton)
			{
				BX.unbind(this._entitySelectorButton, "click", this._entitySelectClickHandler);
				this._entitySelectorButton = null;
			}

			if(this._entityCreationButton)
			{
				BX.unbind(this._entityCreationButton, "click", this._entityCreateClickHandler);
				this._entityCreationButton = null;
			}

			if(this._wrapper)
			{
				this._wrapper = BX.remove(this._wrapper);
			}

			this._hasLayout = false;
		},
		loadSecondaryEntityInfos: function()
		{
			var url = this.getSetting("serviceUrl");
			if(BX.type.isNotEmptyString(url))
			{
				BX.CrmDataLoader.create(
					this._id,
					{
						serviceUrl: url,
						action: "GET_SECONDARY_ENTITY_INFOS",
						params:
						{
							"PRIMARY_TYPE_NAME": this._primaryEntityInfo.getTypeName(),
							"PRIMARY_ID": this._primaryEntityInfo.getId(),
							"SECONDARY_TYPE_NAME": this._secondaryEntityTypeName,
							"OWNER_TYPE_NAME": this._owner["typeName"]
						}
					}
				).load(BX.delegate(this.onLoadSecondaryEntityInfos, this));
			}
		},
		notify: function(target)
		{
			var eventArgs =
			{
				selectorInfo: { type: "combined", id: this._id },
				target: target,
				data:
				{
					primaryEntityTypeName: this._primaryEntityInfo
						? this._primaryEntityInfo.getTypeName() : this._entityTypeName,
					primaryEntityInfo: this._primaryEntityInfo,
					entityTypeName: this._secondaryEntityTypeName,
					entityInfos: this._secondaryEntityInfos
				}
			};
			BX.onCustomEvent("CrmClientSelectorChange", [ this, eventArgs ]);
		},
		openTypeSelectorMenu: function()
		{
			if(!this._selectorMenu)
			{
				this._selectorMenu = BX.CmrSelectorMenu.create(
					this._id,
					{
						items:
							[
								{
									text: BX.CrmEntityType.getCaption(BX.CrmEntityType.enumeration.company),
									value: BX.CrmEntityType.names.company
								},
								{
									text: BX.CrmEntityType.getCaption(BX.CrmEntityType.enumeration.contact),
									value: BX.CrmEntityType.names.contact
								}
							]
					}
				);
				this._selectorMenu.addOnSelectListener(this._typeSelectHandler);
			}

			if(!this._selectorMenu.isOpened())
			{
				this._selectorMenu.open(this._typeSelectorButton);
			}
		},
		onTypeSelectorClick: function(e)
		{
			if(this._readOnly)
			{
				return;
			}

			this.openTypeSelectorMenu();
		},
		onTypeSelect: function(sender, selectedItem)
		{
			if(this._readOnly)
			{
				return;
			}

			this.setEntityTypeName(selectedItem.getValue());
			if(this._selectorMenu.isOpened())
			{
				this._selectorMenu.close();
			}
		},
		onEntitySelectClick: function(e)
		{
			if(this._readOnly)
			{
				return;
			}

			if (obCrm[this._entitySelectorId])
			{
				var selector = obCrm[this._entitySelectorId];
				//HACK: To prevent display of 'minus' icon.
				selector.ClearSelectItems();
				selector.Open();
			}
		},
		onEntityCreateClick: function(e)
		{
			if(this._readOnly)
			{
				return;
			}

			var url = this.getCreateUrl();
			var context = (this.getContext() + "_" + BX.util.getRandomString(6)).toLowerCase();
			if(url === "" || context === "")
			{
				return;
			}

			context = (context + "_" + BX.util.getRandomString(6)).toLowerCase();
			var urlParams = { external_context: context };
			if (BX.type.isPlainObject(this._selectorSearchOptions)
				&& this._selectorSearchOptions["ONLY_MY_COMPANIES"] === 'Y')
			{
				urlParams["mycompany"] = "y";
			}
			url = BX.util.add_url_param(url, urlParams);
			if(!this._externalRequestData)
			{
				this._externalRequestData = {};
			}
			this._externalRequestData[context] = { context: context, wnd: window.open(url) };

			if(!this._externalEventHandler)
			{
				this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
				BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
			}
		},
		onEntitySelect: function(settings)
		{
			if(this._readOnly)
			{
				return;
			}

			var type = this._entityTypeName.toLowerCase();
			var item = settings[type] && settings[type][0] ? settings[type][0] : null;
			if(!item)
			{
				return;
			}

			var entityInfo = BX.CrmEntityInfo.create(item);
			if(this._entityTypeName === BX.CrmEntityType.names.company)
			{
				this.setPrimaryEntity(entityInfo);
				this.removeAllSecondaryEntities();
				this.loadSecondaryEntityInfos();
			}
			else if(this._entityTypeName === BX.CrmEntityType.names.contact)
			{
				if(!this._enableMultiplicity)
				{
					this.removeAllSecondaryEntities();
					this.addSecondaryEntity(entityInfo);
				}
				else
				{
					if(this._primaryEntityInfo)
					{
						var primaryEntityTypeName = this._primaryEntityInfo.getTypeName();
						if(primaryEntityTypeName === BX.CrmEntityType.names.contact)
						{
							this.removeSecondaryEntity(this._primaryEntityInfo);
						}
						else if(primaryEntityTypeName === BX.CrmEntityType.names.company)
						{
							this.removeAllSecondaryEntities();
						}
					}

					if(!this.addSecondaryEntity(entityInfo, 0))
					{
						this.moveSecondaryEntity(entityInfo, 0);
					}
				}
				this.setPrimaryEntity(this.getSecondaryEntityById(entityInfo.getId()));
			}

			this.prepareViews();

		},
		onContactGroupChange: function(sender, eventArgs)
		{
			if(this._readOnly)
			{
				return;
			}

			var action = eventArgs["name"];
			var item = eventArgs["item"];
			var result = true;
			if(action === "add")
			{
				result = this.addSecondaryEntity(item.getEntityInfo(), -1);
			}
			else if(action === "mark")
			{
				this.moveSecondaryEntity(item.getEntityInfo(), 0);
				this.setPrimaryEntity(this.getSecondaryEntityById(item.getEntityId()));
				this.prepareViews();
			}
			else if(action === "remove")
			{
				result = this.removeSecondaryEntity(item.getEntityInfo());
			}
			eventArgs["cancel"] = !result;
		},
		onPrimaryViewDelete: function(sender)
		{
			if(this._readOnly || !this._primaryEntityInfo)
			{
				return;
			}

			if(this._primaryEntityInfo.getTypeName() === BX.CrmEntityType.names.contact)
			{
				this.removeSecondaryEntity(this._primaryEntityInfo);
			}

			var firstSecondaryEntityInfo = this._secondaryEntityInfos.length > 0 ? this._secondaryEntityInfos[0] : null;
			this.setPrimaryEntity(firstSecondaryEntityInfo);
			if(firstSecondaryEntityInfo)
			{
				this.setEntityTypeName(this._secondaryEntityTypeName);
			}
			this.prepareViews();
		},
		onRequisiteChange: function(sender, requisiteId, bankDetailId)
		{
			if(this._readOnly)
			{
				return;
			}

			this.setRequisiteId(requisiteId);
			this.setBankDetailId(bankDetailId);
		},
		onExternalEvent: function(params)
		{
			if(this._readOnly)
			{
				return;
			}

			var key = BX.type.isNotEmptyString(params["key"]) ? params["key"] : "";
			var value = BX.type.isPlainObject(params["value"]) ? params["value"] : {};
			var typeName = BX.type.isNotEmptyString(value["entityTypeName"]) ? value["entityTypeName"] : "";
			var context = BX.type.isNotEmptyString(value["context"]) ? value["context"] : "";

			if(key === "onCrmEntityCreate"
				&& typeName === this._entityTypeName
				&& this._externalRequestData
				&& BX.type.isPlainObject(this._externalRequestData[context]))
			{
				var isCanceled = BX.type.isBoolean(value["isCanceled"]) ? value["isCanceled"] : false;
				if(!isCanceled && BX.type.isPlainObject(value["entityInfo"]))
				{
					var entityInfo = BX.CrmEntityInfo.create(value["entityInfo"]);
					if(this._entityTypeName === BX.CrmEntityType.names.company)
					{
						this.removeAllSecondaryEntities();
						this.setPrimaryEntity(entityInfo);
					}
					else if(this._entityTypeName === BX.CrmEntityType.names.contact)
					{
						if(!this._enableMultiplicity)
						{
							this.removeAllSecondaryEntities();
							this.addSecondaryEntity(entityInfo);
						}
						else
						{
							if(this._primaryEntityInfo)
							{
								var primaryEntityTypeName = this._primaryEntityInfo.getTypeName();
								if(primaryEntityTypeName === BX.CrmEntityType.names.contact)
								{
									this.removeSecondaryEntity(this._primaryEntityInfo);
								}
								else if(primaryEntityTypeName === BX.CrmEntityType.names.company)
								{
									this.removeAllSecondaryEntities();
								}
							}
							this.addSecondaryEntity(entityInfo, 0);
						}
						this.setPrimaryEntity(entityInfo);
					}
					this.prepareViews();
				}
				if(this._externalRequestData[context]["wnd"])
				{
					this._externalRequestData[context]["wnd"].close();
				}
				delete this._externalRequestData[context];
			}
		},
		onLoadSecondaryEntityInfos: function(sender, result)
		{
			var entityData = BX.type.isArray(result['ENTITY_INFOS']) ? result['ENTITY_INFOS'] : [];

			var l = entityData.length;
			if(!this._enableMultiplicity && l > 1)
			{
				l = 1;
			}

			for(var i = 0; i < l; i++)
			{
				this.addSecondaryEntity(BX.CrmEntityInfo.create(entityData[i]));
			}
			this.prepareViews();
		}
	};
	if(typeof(BX.CrmCompositeClientSelector.messages) === "undefined")
	{
		BX.CrmCompositeClientSelector.messages = {};
	}
	BX.CrmCompositeClientSelector.create = function(id, settings)
	{
		var self = new BX.CrmCompositeClientSelector();
		self.initialize(id, settings);
		return self;
	}
}
//endregion
//region BX.CrmMultipleClientSelector
if(typeof(BX.CrmMultipleClientSelector) === "undefined")
{
	BX.CrmMultipleClientSelector = function()
	{
		this._id = "";
		this._settings = {};

		this._entityInfos = null;
		this._entityTypeName = "";
		this._entitySelectorId = "";

		this._containerId = "";
		this._container = null;
		this._wrapper = null;
		this._view = null;

		this._entitiesInputId = "";
		this._entitySelectorButton = null;
		this._entityCreationButton = null;

		this._entitySelectClickHandler = BX.delegate(this.onEntitySelectClick, this);
		this._entityCreateClickHandler = BX.delegate(this.onEntityCreateClick, this);
		this._entitySelectHandler = BX.delegate(this.onEntitySelect, this);
		this._entityDeleteHandler = BX.delegate(this.onEntityDelete, this);

		this._externalRequestData = null;
		this._externalEventHandler = null;

		this._enableEntityCreation = false;
		this._enableRequisites = false;
		this._enableLazyLoad = false;
		this._readOnly = false;
		this._hasLayout = false;
	};
	BX.CrmMultipleClientSelector.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._containerId = this.getSetting("containerId", "");
			if(!BX.type.isNotEmptyString(this._containerId))
			{
				throw "CrmMultipleClientSelector: Could not find 'containerId' parameter in settings.";
			}

			this._container = BX(this._containerId);
			if(!BX.type.isElementNode(this._container))
			{
				throw "CrmMultipleClientSelector: Could not find container element.";
			}

			this._entityTypeName = BX.CrmEntityType.verifyName(this.getSetting("entityType", ""));
			if(this._entityTypeName === BX.CrmEntityType.names.undefined)
			{
				throw "CrmMultipleClientSelector: Could not find 'entityTypeName' parameter in settings.";
			}

			var entityData = this.getSetting("entityData");
			if(!BX.type.isArray(entityData))
			{
				entityData = [];
			}

			this._entityInfos = [];
			for(var i = 0; i < entityData.length; i++)
			{
				var info = BX.CrmEntityInfo.create(entityData[i]);
				if(info.getId() > 0)
				{
					this._entityInfos.push(info);
				}
			}

			this._readOnly = !!this.getSetting("readOnly");
			this._enableLazyLoad = !!this.getSetting("enableLazyLoad");

			if(!this._readOnly && this._enableLazyLoad)
			{
				throw "CrmMultipleClientSelector: Lazy load is supported in read only mode only.";
			}

			this._enableEntityCreation = !!this.getSetting("enableEntityCreation", false);
			this._enableRequisites = !!this.getSetting("enableRequisites", true);
			this._entitiesInputId = this.getSetting("entitiesInputId", "");
			this._entitySelectorId = this._id + "_ENTITY_SELECTOR";

			var selectorSearchOptions = this.getSetting("selectorSearchOptions", {});
			this._selectorSearchOptions = BX.type.isPlainObject(selectorSearchOptions) ? selectorSearchOptions : {};
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getMessage: function(name)
		{
			var m = BX.CrmMultipleClientSelector.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		getContext: function()
		{
			return this.getSetting("context", "");
		},
		getCreateUrl: function()
		{
			return this.getSetting("entityCreateUrl", "");
		},
		getEntityIds: function()
		{
			var result = [];
			for(var i = 0, c = this._entityInfos.length; i < c; i++)
			{
				result.push(this._entityInfos[i].getId());
			}
			return result;
		},
		findEntityInfoById: function(id)
		{
			if(!BX.type.isNumber(id))
			{
				id = parseInt(id);
			}

			if(isNaN(id) || id <= 0)
			{
				return -1;
			}

			for(var i = 0, c = this._entityInfos.length; i < c; i++)
			{
				if(this._entityInfos[i].getId() == id)
				{
					return i;
				}
			}

			return -1;
		},
		getEntityInfo: function(index)
		{
			return index >= 0 && index < this._entityInfos.length ? this._entityInfos[index] : null;
		},
		addEntityInfo: function(entityInfo)
		{
			if(this.findEntityInfoById(entityInfo.getId()) >= 0)
			{
				return -1;
			}

			this._entityInfos.push(entityInfo);

			var input = BX(this._entitiesInputId);
			if(input)
			{
				input.value = this.getEntityIds().join(",");
			}

			this.notify("entity");

			return (this._entityInfos.length - 1);
		},
		removeEntityInfo: function(entityInfo)
		{
			var index = this.findEntityInfoById(entityInfo.getId());
			if(index < 0)
			{
				return false;
			}

			this._entityInfos.splice(index, 1);

			var input = BX(this._entitiesInputId);
			if(input)
			{
				input.value = this.getEntityIds().join(",");
			}

			this.notify("entity");
			return true;
		},
		prepareView: function(params)
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(!BX.type.isPlainObject(params))
			{
				params = {};
			}

			if(this._view)
			{
				this._view.clearLayout();
				this._view = null;
			}

			var entityCount = 0;
			if(!this._enableLazyLoad)
			{
				entityCount = this._entityInfos.length;
			}
			else
			{
				entityCount = parseInt(this.getSetting("entityCount"));
			}

			if(entityCount > 0)
			{
				var settings =
				{
					entityType: this._entityTypeName,
					entityInfos: this._entityInfos,
					count: entityCount,
					container: this._wrapper,
					readOnly: this._readOnly,
					enableRequisites: this._enableRequisites,
					enableRequisiteChange: false
				};

				if(this._enableLazyLoad)
				{
					settings["loader"] = this.getSetting("loader");
					settings["owner"] = this.getSetting("owner");
				}

				this._view = BX.CrmMultipleEntitySummaryView.create(this._id, settings);
				this._view.layout();

				if(!this._readOnly)
				{
					this._view.addDeletionListener(this._entityDeleteHandler);
				}
				if(BX.type.isNumber(params["index"]))
				{
					this._view.setIndex(params["index"]);
				}
			}
		},
		prepareEntitySelector: function()
		{
			if(this._readOnly)
			{
				return;
			}

			if (obCrm[this._entitySelectorId])
			{
				obCrm[this._entitySelectorId].Clear();
				delete obCrm[this._entitySelectorId];
			}

			CRM.Set(
				this._entitySelectorButton,
				this._entitySelectorId,
				"",
				{},
				false,
				false,
				[this._entityTypeName.toLowerCase()],
				this.getSetting("selectorMessages"),
				true,
				{
					requireRequisiteData: this._enableRequisites,
					searchOptions: this._selectorSearchOptions
				}
			);

			obCrm[this._entitySelectorId].AddOnSaveListener(this._entitySelectHandler);
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			this._wrapper = BX.create("DIV", { attrs: { className: "crm-client-selector-tabs-wrapper" } });
			this._container.appendChild(this._wrapper);

			if(!this._readOnly)
			{
				var buttonWrapper = BX.create("DIV", { attrs: { className: "crm-client-selector-type" } });
				this._wrapper.appendChild(buttonWrapper);

				this._entitySelectorButton = BX.create("DIV",
					{
						attrs: { id: this._entitySelectorId, className: "crm-client-selector-type-item-select" },
						text: this.getMessage("selectButton")
					}
				);
				buttonWrapper.appendChild(this._entitySelectorButton);
				BX.bind(this._entitySelectorButton, "click", this._entitySelectClickHandler);

				if(this._enableEntityCreation)
				{
					this._entityCreationButton = BX.create("DIV",
						{
							attrs: { className: "crm-client-selector-type-item-create"},
							events: { click: this._entityCreateClickHandler },
							text: this.getMessage("createButton")
						}
					);
					buttonWrapper.appendChild(this._entityCreationButton);
					BX.bind(this._entityCreationButton, "click", this._entityCreateClickHandler);
				}
			}

			this._hasLayout = true;
			this.prepareView();
			this.prepareEntitySelector();
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(this._entitySelectorButton)
			{
				BX.unbind(this._entitySelectorButton, "click", this._entitySelectClickHandler);
				this._entitySelectorButton = null;
			}

			if(this._entityCreationButton)
			{
				BX.unbind(this._entityCreationButton, "click", this._entityCreateClickHandler);
				this._entityCreationButton = null;
			}

			if(this._wrapper)
			{
				this._wrapper = BX.remove(this._wrapper);
			}

			this._hasLayout = false;
		},
		notify: function(target)
		{
			var eventArgs =
			{
				selectorInfo: { type: "multiple", id: this._id },
				target: target,
				data:
				{
					entityTypeName: this._entityTypeName,
					entityInfos: this._entityInfos
				}
			};
			BX.onCustomEvent("CrmClientSelectorChange", [ this, eventArgs ]);
		},
		onEntitySelectClick: function(e)
		{
			if(this._readOnly)
			{
				return;
			}

			if (obCrm[this._entitySelectorId])
			{
				obCrm[this._entitySelectorId].Open();
			}
		},
		onEntityCreateClick: function(e)
		{
			if(this._readOnly || !this._enableEntityCreation)
			{
				return;
			}

			var url = this.getCreateUrl();
			var context = (this.getContext() + "_" + BX.util.getRandomString(6)).toLowerCase();
			if(url === "" || context === "")
			{
				return;
			}

			context = (context + "_" + BX.util.getRandomString(6)).toLowerCase();
			var urlParams = { external_context: context };
			if (BX.type.isPlainObject(this._selectorSearchOptions)
				&& this._selectorSearchOptions["ONLY_MY_COMPANIES"] === 'Y')
			{
				urlParams["mycompany"] = "y";
			}
			url = BX.util.add_url_param(url, urlParams);
			if(!this._externalRequestData)
			{
				this._externalRequestData = {};
			}
			this._externalRequestData[context] = { context: context, wnd: window.open(url) };

			if(!this._externalEventHandler)
			{
				this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
				BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
			}
		},
		onEntitySelect: function(settings)
		{
			if(this._readOnly)
			{
				return;
			}

			var type = this._entityTypeName.toLowerCase();
			var item = settings[type] && settings[type][0] ? settings[type][0] : null;
			if(!item)
			{
				return;
			}

			var index = this.addEntityInfo(BX.CrmEntityInfo.create(item));
			if(index >= 0)
			{
				this.prepareView({ index: index });
			}
		},
		onEntityDelete: function(sender, view)
		{
			if(this._readOnly)
			{
				return;
			}

			if(this.removeEntityInfo(view.getEntityInfo()))
			{
				this.prepareView();
			}
		},
		onExternalEvent: function(params)
		{
			if(this._readOnly || !this._enableEntityCreation)
			{
				return;
			}

			var key = BX.type.isNotEmptyString(params["key"]) ? params["key"] : "";
			var value = BX.type.isPlainObject(params["value"]) ? params["value"] : {};
			var typeName = BX.type.isNotEmptyString(value["entityTypeName"]) ? value["entityTypeName"] : "";
			var context = BX.type.isNotEmptyString(value["context"]) ? value["context"] : "";

			if(key === "onCrmEntityCreate"
				&& typeName === this._entityTypeName
				&& this._externalRequestData
				&& BX.type.isPlainObject(this._externalRequestData[context]))
			{
				var isCanceled = BX.type.isBoolean(value["isCanceled"]) ? value["isCanceled"] : false;
				if(!isCanceled && BX.type.isPlainObject(value["entityInfo"]))
				{
					var index = this.addEntityInfo(BX.CrmEntityInfo.create(value["entityInfo"]));
					if(index >= 0)
					{
						this.prepareView({ index: index });
					}
				}

				if(this._externalRequestData[context]["wnd"])
				{
					this._externalRequestData[context]["wnd"].close();
				}

				delete this._externalRequestData[context];
			}
		}
	};
	if(typeof(BX.CrmMultipleClientSelector.messages) === "undefined")
	{
		BX.CrmMultipleClientSelector.messages = {};
	}
	BX.CrmMultipleClientSelector.create = function(id, settings)
	{
		var self = new BX.CrmMultipleClientSelector();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmSingleClientSelector
if(typeof(BX.CrmSingleClientSelector) === "undefined")
{
	BX.CrmSingleClientSelector = function()
	{
		this._id = "";
		this._settings = {};

		this._entityInfo = null;
		this._requisiteInfo = null;
		this._additionalData = null;
		this._entityTypeName = "";
		this._entitySelectorId = "";

		this._containerId = "";
		this._container = null;
		this._wrapper = null;
		this._view = null;

		this._entityInputId = "";
		this._requisiteInputId = "";
		this._bankDetailInputId = "";
		this._entitySelectorButton = null;
		this._entityCreationButton = null;

		this._entitySelectClickHandler = BX.delegate(this.onEntitySelectClick, this);
		this._entityCreateClickHandler = BX.delegate(this.onEntityCreateClick, this);
		this._entitySelectHandler = BX.delegate(this.onEntitySelect, this);
		this._entityDeleteHandler = BX.delegate(this.onEntityDelete, this);
		this._entityRequisiteChangeHandler = BX.delegate(this.onEntityRequisiteChange, this);

		this._externalRequestData = null;
		this._externalEventHandler = null;

		this._enableEntityCreation = false;
		this._enableRequisites = false;
		this._enableRequisiteChange = false;
		this._readOnly = false;
		this._hasLayout = false;
	};
	BX.CrmSingleClientSelector.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._containerId = this.getSetting("containerId", "");
			if(!BX.type.isNotEmptyString(this._containerId))
			{
				throw "CrmSingleClientSelector: Could not find 'containerId' parameter in settings.";
			}

			this._container = BX(this._containerId);
			if(!BX.type.isElementNode(this._container))
			{
				throw "CrmSingleClientSelector: Could not find container element.";
			}

			this._entityTypeName = BX.CrmEntityType.verifyName(this.getSetting("entityType", ""));
			if(this._entityTypeName === BX.CrmEntityType.names.undefined)
			{
				throw "CrmSingleClientSelector: Could not find 'entityTypeName' parameter in settings.";
			}

			this._readOnly = !!this.getSetting("readOnly");

			this._enableEntityCreation = !!this.getSetting("enableEntityCreation", false);
			this._enableRequisites = !!this.getSetting("enableRequisites", true);
			this._enableRequisiteChange = !!this.getSetting("enableRequisiteChange", this._enableRequisites);
			this._entityInputId = this.getSetting("entityInputId", "");
			this._requisiteInputId = this.getSetting("requisiteInputId", "");
			this._bankDetailInputId = this.getSetting("bankDetailInputId", "");

			var selectorSearchOptions = this.getSetting("selectorSearchOptions", {});
			this._selectorSearchOptions = BX.type.isPlainObject(selectorSearchOptions) ? selectorSearchOptions : {};

			var additionalData = this.getSetting("additionalData");
			this._additionalData = BX.type.isPlainObject(additionalData) ? additionalData : {};

			var entityData = this.getSetting("entityData");
			if(BX.type.isPlainObject(entityData))
			{
				this.setEntityInfo(BX.CrmEntityInfo.create(entityData));
			}

			this._entitySelectorId = this._id + "_ENTITY_SELECTOR";
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getMessage: function(name)
		{
			var m = BX.CrmSingleClientSelector.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		getContext: function()
		{
			return this.getSetting("context", "");
		},
		getCreateUrl: function()
		{
			return this.getSetting("entityCreateUrl", "");
		},
		getEntityInfo: function()
		{
			return this._entityInfo;
		},
		setEntityInfo: function(entityInfo)
		{
			var input = BX(this._entityInputId);

			if(this._entityInfo)
			{
				this._entityInfo = null;
				if(input)
				{
					input.value = "";
				}
			}

			if(entityInfo instanceof BX.CrmEntityInfo)
			{
				this._entityInfo = entityInfo;
				if(input)
				{
					input.value = this._entityInfo ? this._entityInfo.getId() : "";
				}
			}

			this.prepareRequisiteInfo();

			this.notify("primaryEntity");
		},
		getRequisiteId: function()
		{
			return BX.type.isNumber(this._additionalData["requisiteId"]) ? this._additionalData["requisiteId"] : 0;
		},
		setRequisiteId: function(id)
		{
			if(!BX.type.isNumber(id))
			{
				id = parseInt(id);
			}

			if(isNaN(id) || id < 0)
			{
				id = 0;
			}

			if(this._additionalData["requisiteId"] === id)
			{
				return;
			}

			this._additionalData["requisiteId"] = id;
			var input = BX(this._requisiteInputId);
			if(input)
			{
				input.value = id > 0 ? id.toString() : "";
			}
		},
		getBankDetailId: function()
		{
			return BX.type.isNumber(this._additionalData["bankDetailId"]) ? this._additionalData["bankDetailId"] : 0;
		},
		setBankDetailId: function(id)
		{
			if(!BX.type.isNumber(id))
			{
				id = parseInt(id);
			}

			if(isNaN(id) || id < 0)
			{
				id = 0;
			}

			if(this._additionalData["bankDetailId"] === id)
			{
				return;
			}

			this._additionalData["bankDetailId"] = id;
			var input = BX(this._bankDetailInputId);
			if(input)
			{
				input.value = id > 0 ? id.toString() : "";
			}
		},
		prepareRequisiteInfo: function()
		{
			if(this._entityInfo !== null)
			{
				this._requisiteInfo = BX.CrmEntityRequisiteInfo.create(
					{
						requisiteId: this.getRequisiteId(),
						bankDetailId: this.getBankDetailId(),
						data: this._entityInfo.getRequisites()
					}
				);

				if(this._requisiteInfo.getRequisiteId() !== this.getRequisiteId())
				{
					this.setRequisiteId(this._requisiteInfo.getRequisiteId());
				}

				if(this._requisiteInfo.getBankDetailId() !== this.getBankDetailId())
				{
					this.setBankDetailId(this._requisiteInfo.getBankDetailId());
				}
			}
			else
			{
				this._requisiteInfo = null;

				if(this.getRequisiteId() !== 0)
				{
					this.setRequisiteId(0);
				}

				if(this.getBankDetailId() !== 0)
				{
					this.setBankDetailId(0);
				}
			}
		},
		prepareView: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(this._view)
			{
				this._view.clearLayout();
				this._view = null;
			}

			if(this._entityInfo !== null)
			{
				this._view = BX.CrmEntitySummaryView.create(this._id,
					{
						entityInfo: this._entityInfo,
						requisiteInfo: null,
						container: this._wrapper,
						readOnly: this._readOnly,
						enableRequisites: this._enableRequisites,
						enableRequisiteChange: this._enableRequisiteChange,
						requisiteServiceUrl: this.getSetting("requisiteServiceUrl")
					}
				);
				this._view.layout();

				if(!this._readOnly)
				{
					this._view.addDeletionListener(this._entityDeleteHandler);
					if(this._enableRequisiteChange)
					{
						this._view.addRequisiteChangeListener(this._entityRequisiteChangeHandler);
					}
				}
			}
		},
		prepareEntitySelector: function()
		{
			if(this._readOnly)
			{
				return;
			}

			if (obCrm[this._entitySelectorId])
			{
				obCrm[this._entitySelectorId].Clear();
				delete obCrm[this._entitySelectorId];
			}

			CRM.Set(
				this._entitySelectorButton,
				this._entitySelectorId,
				"",
				{},
				false,
				false,
				[this._entityTypeName.toLowerCase()],
				this.getSetting("selectorMessages"),
				true,
				{
					requireRequisiteData: this._enableRequisites,
					searchOptions: this._selectorSearchOptions
				}
			);

			obCrm[this._entitySelectorId].AddOnSaveListener(this._entitySelectHandler);
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			this._wrapper = BX.create("DIV", { attrs: { className: "crm-client-selector-tabs-wrapper" } });
			this._container.appendChild(this._wrapper);

			if(!this._readOnly)
			{
				var buttonWrapper = BX.create("DIV", { attrs: { className: "crm-client-selector-type" } });
				this._wrapper.appendChild(buttonWrapper);

				this._entitySelectorButton = BX.create("DIV",
					{
						attrs: { id: this._entitySelectorId, className: "crm-client-selector-type-item-select" },
						text: this.getMessage("selectButton")
					}
				);
				buttonWrapper.appendChild(this._entitySelectorButton);
				BX.bind(this._entitySelectorButton, "click", this._entitySelectClickHandler);

				if(this._enableEntityCreation)
				{
					this._entityCreationButton = BX.create("DIV",
						{
							attrs: { className: "crm-client-selector-type-item-create"},
							events: { click: this._entityCreateClickHandler },
							text: this.getMessage("createButton")
						}
					);
					buttonWrapper.appendChild(this._entityCreationButton);
					BX.bind(this._entityCreationButton, "click", this._entityCreateClickHandler);
				}
			}

			this._hasLayout = true;
			this.prepareView();
			this.prepareEntitySelector();
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(this._entitySelectorButton)
			{
				BX.unbind(this._entitySelectorButton, "click", this._entitySelectClickHandler);
				this._entitySelectorButton = null;
			}

			if(this._entityCreationButton)
			{
				BX.unbind(this._entityCreationButton, "click", this._entityCreateClickHandler);
				this._entityCreationButton = null;
			}

			if(this._wrapper)
			{
				this._wrapper = BX.remove(this._wrapper);
			}

			this._hasLayout = false;
		},
		notify: function(target)
		{
			var eventArgs =
			{
				selectorInfo: { type: "single", id: this._id },
				target: target,
				data:
				{
					primaryEntityTypeName: this._entityTypeName,
					primaryEntityInfo: this._entityInfo
				}
			};
			BX.onCustomEvent("CrmClientSelectorChange", [ this, eventArgs ]);
		},
		onEntitySelectClick: function(e)
		{
			if(this._readOnly)
			{
				return;
			}

			if (obCrm[this._entitySelectorId])
			{
				obCrm[this._entitySelectorId].Open();
			}
		},
		onEntityCreateClick: function(e)
		{
			if(this._readOnly || !this._enableEntityCreation)
			{
				return;
			}

			var url = this.getCreateUrl();
			var context = (this.getContext() + "_" + BX.util.getRandomString(6)).toLowerCase();
			if(url === "" || context === "")
			{
				return;
			}

			context = (context + "_" + BX.util.getRandomString(6)).toLowerCase();
			var urlParams = { external_context: context };
			if (BX.type.isPlainObject(this._selectorSearchOptions)
					&& this._selectorSearchOptions["ONLY_MY_COMPANIES"] === 'Y')
			{
				urlParams["mycompany"] = "y";
			}
			url = BX.util.add_url_param(url, urlParams);
			if(!this._externalRequestData)
			{
				this._externalRequestData = {};
			}
			this._externalRequestData[context] = { context: context, wnd: window.open(url) };

			if(!this._externalEventHandler)
			{
				this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
				BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
			}
		},
		onEntitySelect: function(settings)
		{
			if(this._readOnly)
			{
				return;
			}

			var type = this._entityTypeName.toLowerCase();
			var item = settings[type] && settings[type][0] ? settings[type][0] : null;
			if(item)
			{
				this.setEntityInfo(BX.CrmEntityInfo.create(item));
				this.prepareView();
			}
		},
		onEntityDelete: function(sender, view)
		{
			if(this._readOnly)
			{
				return;
			}

			this.setEntityInfo(null);
			this.prepareView();
		},
		onEntityRequisiteChange: function(sender, requisiteId, bankDetailId)
		{
			if(this._readOnly)
			{
				return;
			}

			this.setRequisiteId(requisiteId);
			this.setBankDetailId(bankDetailId);
		},
		onExternalEvent: function(params)
		{
			if(this._readOnly || !this._enableEntityCreation)
			{
				return;
			}

			var key = BX.type.isNotEmptyString(params["key"]) ? params["key"] : "";
			var value = BX.type.isPlainObject(params["value"]) ? params["value"] : {};
			var typeName = BX.type.isNotEmptyString(value["entityTypeName"]) ? value["entityTypeName"] : "";
			var context = BX.type.isNotEmptyString(value["context"]) ? value["context"] : "";

			if(key === "onCrmEntityCreate"
				&& typeName === this._entityTypeName
				&& this._externalRequestData
				&& BX.type.isPlainObject(this._externalRequestData[context]))
			{
				var isCanceled = BX.type.isBoolean(value["isCanceled"]) ? value["isCanceled"] : false;
				if(!isCanceled && BX.type.isPlainObject(value["entityInfo"]))
				{
					this.setEntityInfo(BX.CrmEntityInfo.create(value["entityInfo"]));
					this.prepareView();
				}

				if(this._externalRequestData[context]["wnd"])
				{
					this._externalRequestData[context]["wnd"].close();
				}

				delete this._externalRequestData[context];
			}
		}
	};
	if(typeof(BX.CrmSingleClientSelector.messages) === "undefined")
	{
		BX.CrmSingleClientSelector.messages = {};
	}
	BX.CrmSingleClientSelector.create = function(id, settings)
	{
		var self = new BX.CrmSingleClientSelector();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmClientPanelGroup
if(typeof(BX.CrmClientPanelGroup) === "undefined")
{
	BX.CrmClientPanelGroup = function()
	{
		this._id = "";
		this._settings = {};
		this._customMessages = null;

		this._entityTypeName = "";
		this._entityInfos = [];
		this._items = [];
		this._entitySelectorId = "";

		this._container = null;
		this._wrapper = null;
		this._itemWrapper = null;

		this._enableMarking = false;
		this._enableMultiplicity = true;
		this._readOnly = false;
		this._hasLayout = false;

		this._addButtonClickHandler = BX.delegate(this.onAddButtomClick, this);
		this._createButtonClickHandler = BX.delegate(this.onCreateButtomClick, this);
		this._entitySelectHandler = BX.delegate(this.onEntitySelect, this);
		this._deleteItemHandler = BX.delegate(this.onItemDelete, this);
		this._markItemHandler = BX.delegate(this.onItemMark, this);

		this._externalRequestData = null;
		this._externalEventHandler = null;
		this._changeNotifier = null;
	};
	BX.CrmClientPanelGroup.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._id = BX.type.isNotEmptyString(id) ? id : "CRM_CLIENT_PANEL_GROUP" + Math.random();
			this._settings = BX.type.isPlainObject(settings) ? settings : {};

			this._container = this.getSetting("container");
			if(!BX.type.isElementNode(this._container))
			{
				throw "CrmClientPanelGroup: Could not find 'container' parameter in settings.";
			}

			this._entityTypeName = this.getSetting("entityType", "");
			if(!BX.type.isNotEmptyString(this._entityTypeName))
			{
				throw "CrmClientPanelGroup: Could not find 'entityType' parameter in settings.";
			}

			var entityInfos = this.getSetting("entityInfos");
			if(BX.type.isArray(entityInfos))
			{
				this._entityInfos = entityInfos;
			}

			this._enableMarking = !!this.getSetting("enableMarking", false);
			this._enableMultiplicity = !!this.getSetting("enableMultiplicity", true);
			this._readOnly = !!this.getSetting("readOnly", false);

			this._changeNotifier = BX.CrmNotifier.create(this);

			this._customMessages = this.getSetting("messages");
			if(!BX.type.isPlainObject(this._customMessages))
			{
				this._customMessages = {};
			}
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getMessage: function(name)
		{
			var m = BX.CrmClientPanelGroup.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		getCustomMessage: function(name)
		{
			var m = this._customMessages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		getEntityInfos: function()
		{
			return this._entityInfos;
		},
		getEntityIds: function()
		{
			var result = [];
			for(var i = 0; i < this._entityInfos.length; i++)
			{
				result.push(this._entityInfos[i].getId());
			}
			return result;
		},
		getContext: function()
		{
			return this.getSetting("context", "");
		},
		getCreateUrl: function()
		{
			return this.getSetting("createUrl", "");
		},
		getCreateUrlParams: function()
		{
			var params = this.getSetting("createUrlParams");
			return BX.type.isPlainObject(params) ? params : {};
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			var outerWrapper = this._wrapper = BX.create("DIV", { attrs: { className: "crm-deal-client-selector-container" } });
			this._container.appendChild(outerWrapper);

			outerWrapper.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-deal-client-selector-title" },
						text: this.getCustomMessage("header") + ":"
					}
				)
			);

			var itemWrapper = this._itemWrapper = BX.create("DIV", { attrs: { className: "crm-deal-client-selector-card-list" } });
			outerWrapper.appendChild(itemWrapper);

			var length = this._entityInfos.length;
			if(length > 1 && !this._enableMultiplicity)
			{
				length = 1;
			}

			for(var i = 0; i < length; i++)
			{
				var item = this.createItem(this._entityInfos[i]);
				item.addDeletionListener(this._deleteItemHandler);
				item.addMarkingListener(this._markItemHandler);
				item.layout();
				this._items.push(item);
			}

			outerWrapper.appendChild(BX.create("DIV", { style: { "clear": "both" } }));
			outerWrapper.appendChild(BX.create("BR"));

			if(!this._readOnly)
			{
				var entitySelectorId = this._entitySelectorId = this._id + "_" + this._entityTypeName;
				var addButton = BX.create("A",
					{
						attrs: { id: entitySelectorId, href: "#", className: "crm-deal-client-selector-add-contact" },
						events: { click: this._addButtonClickHandler },
						children: [ BX.create("STRONG", { text: this.getMessage("selectButton").toLowerCase() }) ]
					}
				);
				outerWrapper.appendChild(addButton);

				CRM.Set(
					addButton,
					entitySelectorId,
					"",
					{},
					false,
					false,
					[this._entityTypeName.toLowerCase()],
					this.getSetting("selectorMessages"),
					true,
					{ requireRequisiteData: true }
				);

				obCrm[entitySelectorId].AddOnSaveListener(this._entitySelectHandler);

				var createButton = BX.create("A",
					{
						attrs: { id: entitySelectorId, href: "#", className: "crm-deal-client-selector-create-contact" },
						events: { click: this._createButtonClickHandler },
						children: [ BX.create("STRONG", { text: this.getMessage("createButton").toLowerCase() }) ]
					}
				);
				outerWrapper.appendChild(createButton);
			}
			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(this._items.length > 0)
			{
				for(var i = 0; i < this._items.length; i++)
				{
					var item = this._items[i];
					item.removeDeletionListener(this._deleteItemHandler);
					item.removeMarkingListener(this._markItemHandler);
					item.clearLayout();
				}
				this._items = [];
			}

			if(this._itemWrapper)
			{
				this._itemWrapper = BX.remove(this._itemWrapper);
			}

			if(this._entitySelectorId !== "")
			{
				if (obCrm[this._entitySelectorId])
				{
					obCrm[this._entitySelectorId].Clear();
					delete obCrm[this._entitySelectorId];
				}
				this._entitySelectorId = "";
			}

			if(this._wrapper)
			{
				this._wrapper = BX.remove(this._wrapper);
			}

			this._hasLayout = false;
		},
		createItem: function(entityInfo)
		{
			return (
				BX.CrmClientPanelGroupItem.create("",
					{
						parent: this,
						container: this._itemWrapper,
						entityType: this._entityTypeName,
						entityInfo: entityInfo,
						enableMarking: this._enableMarking,
						readOnly: this._readOnly
					}
				)
			);
		},
		addItem: function(entityInfo)
		{
			var item = this.createItem(entityInfo);
			var eventArgs = { name: "add", item: item, cancel: false  };
			this._changeNotifier.notify([ eventArgs ]);
			if(eventArgs["cancel"])
			{
				return null;
			}

			this._items.push(item);
			item.addDeletionListener(this._deleteItemHandler);
			item.addMarkingListener(this._markItemHandler);
			this._entityInfos.push(entityInfo);

			return item;
		},
		removeItem: function(item)
		{
			var index = this.findItemById(item.getEntityId());
			if(index < 0)
			{
				return false;
			}

			var eventArgs = { name: "remove", item: item, cancel: false  };
			this._changeNotifier.notify([ eventArgs ]);
			if(eventArgs["cancel"])
			{
				return false;
			}

			this._items[index].clearLayout();
			this._items.splice(index, 1);
			this._entityInfos.splice(index, 1);

			return true;
		},
		removeAllItems: function()
		{
			if(this._items.length === 0)
			{
				return;
			}

			for(var i = 0; i < this._items.length; i++)
			{
				var item = this._items[i];
				item.removeDeletionListener(this._deleteItemHandler);
				item.removeMarkingListener(this._markItemHandler);
				item.clearLayout();
			}
			this._items = [];
		},
		findItemById: function(id)
		{
			if(!BX.type.isNumber(id))
			{
				id = parseInt(id);
			}

			if(isNaN(id) || id <= 0)
			{
				return -1;
			}

			for(var i = 0; i < this._items.length; i++)
			{
				if(this._items[i].getEntityId() == id)
				{
					return i;
				}
			}

			return -1;
		},
		isMarkingEnabled: function()
		{
			return this._enableMarking;
		},
		enebleMarking: function(enable)
		{
			enable = !!enable;
			if(this._enableMarking === enable)
			{
				return;
			}

			this._enableMarking = enable;
			for(var i = 0; i < this._items.length; i++)
			{
				this._items[i].enebleMarking(enable);
			}
		},
		addChangeListener: function(listener)
		{
			this._changeNotifier.addListener(listener);
		},
		removeChangeListener: function(listener)
		{
			this._changeNotifier.removeListener(listener);
		},
		onAddButtomClick: function(e)
		{
			if(!this._readonly && obCrm[this._entitySelectorId])
			{
				var selector = obCrm[this._entitySelectorId];
				//HACK: To prevent display of 'minus' icon.
				selector.ClearSelectItems();
				selector.Open();
			}

			return BX.PreventDefault(e);
		},
		onCreateButtomClick: function(e)
		{
			if(this._readonly)
			{
				return BX.PreventDefault(e);
			}

			var url = this.getCreateUrl();
			var context = this.getContext();

			if(url === "" || context === "")
			{
				return BX.PreventDefault(e);
			}

			context = (this.getContext() + "_" + BX.util.getRandomString(6)).toLowerCase();
			var urlParams = { external_context: context };

			var params = this.getCreateUrlParams();
			for(var k in params)
			{
				if(params.hasOwnProperty(k))
				{
					urlParams[k] = params[k];
				}
			}
			url = BX.util.add_url_param(url, urlParams);

			if(!this._externalRequestData)
			{
				this._externalRequestData = {};
			}
			this._externalRequestData[context] = { context: context, wnd: window.open(url) };

			if(!this._externalEventHandler)
			{
				this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
				BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
			}

			return BX.PreventDefault(e);
		},
		onEntitySelect: function(settings)
		{
			if(this._readonly)
			{
				return;
			}

			var type = this._entityTypeName.toLowerCase();
			var data = settings[type] && settings[type][0] ? settings[type][0] : null;
			if(!data)
			{
				return;
			}

			var id = parseInt(data["id"]);
			if(isNaN(id) || id <= 0)
			{
				return;
			}

			if(this.findItemById(id) >= 0)
			{
				return;
			}

			if(!this._enableMultiplicity && this._items.length > 0)
			{
				this.removeItem(this._items[0]);
			}

			var item = this.addItem(BX.CrmEntityInfo.create(data))
			if(item)
			{
				item.layout();
			}
		},
		onItemDelete: function(sender)
		{
			this.removeItem(sender);
		},
		onItemMark: function(sender)
		{
			var eventArgs = { name: "mark", item: sender, cancel: false  };
			this._changeNotifier.notify([ eventArgs ]);
			if(eventArgs["cancel"])
			{
				sender.setMarked(!sender.isMarked());
			}
		},
		onExternalEvent: function(params)
		{
			var key = BX.type.isNotEmptyString(params["key"]) ? params["key"] : "";
			var value = BX.type.isPlainObject(params["value"]) ? params["value"] : {};
			var typeName = BX.type.isNotEmptyString(value["entityTypeName"]) ? value["entityTypeName"] : "";
			var context = BX.type.isNotEmptyString(value["context"]) ? value["context"] : "";

			if(key === "onCrmEntityCreate"
				&& typeName === this._entityTypeName
				&& this._externalRequestData
				&& BX.type.isPlainObject(this._externalRequestData[context]))
			{
				var isCanceled = BX.type.isBoolean(value["isCanceled"]) ? value["isCanceled"] : false;
				if(!isCanceled && BX.type.isPlainObject(value["entityInfo"]))
				{
					if(!this._enableMultiplicity && this._items.length > 0)
					{
						this.removeItem(this._items[0]);
					}

					var item = this.addItem(BX.CrmEntityInfo.create(value["entityInfo"]))
					if(item)
					{
						item.layout();
					}
				}

				if(this._externalRequestData[context]["wnd"])
				{
					this._externalRequestData[context]["wnd"].close();
				}

				delete this._externalRequestData[context];
			}
		}
	};
	if(typeof(BX.CrmClientPanelGroup.messages) === "undefined")
	{
		BX.CrmClientPanelGroup.messages = {};
	}
	BX.CrmClientPanelGroup.create = function(id, settings)
	{
		var self = new BX.CrmClientPanelGroup();
		self.initialize(id, settings);
		return self;
	};
}
//endregion
//region BX.CrmClientPanelGroupItem
if(typeof(BX.CrmClientPanelGroupItem) === "undefined")
{
	BX.CrmClientPanelGroupItem = function()
	{
		this._id = "";
		this._settings = {};
		this._parent = null;

		this._entityTypeName = "";
		this._entityInfo = null;
		this._commynications = [];

		this._container = null;
		this._wrapper = null;

		this._readOnly = false;
		this._enableMarking = false;
		this._isMarked = false;
		this._hasLayout = false;

		this._markButton = null;

		this._deleteButtonHandler = BX.delegate(this.onDeleteButtonClick, this);
		this._markButtonHandler = BX.delegate(this.onMarkButtonClick, this);
		this._deleteNotifier = null;
		this._markNotifier = null;
	};
	BX.CrmClientPanelGroupItem.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._id = BX.type.isNotEmptyString(id) ? id : "CRM_CLIENT_PANEL_GROUP_ITEM" + Math.random();
			this._settings = BX.type.isPlainObject(settings) ? settings : {};

			this._parent = this.getSetting("parent");
			if(!(this._parent instanceof BX.CrmClientPanelGroup))
			{
				throw "CrmClientPanelGroupItem: Could not find 'parent' parameter in settings.";
			}
			this._container = this.getSetting("container");
			if(!BX.type.isElementNode(this._container))
			{
				throw "CrmClientPanelGroupItem: Could not find 'container' parameter in settings.";
			}

			this._entityTypeName = this.getSetting("entityType", "");
			if(!BX.type.isNotEmptyString(this._entityTypeName))
			{
				throw "CrmClientPanelGroupItem: Could not find 'entityType' parameter in settings.";
			}

			this._entityInfo = this.getSetting("entityInfo");
			if(!(this._entityInfo instanceof BX.CrmEntityInfo))
			{
				throw "CrmClientPanelGroupItem: Could not find 'entityInfo' parameter in settings.";
			}

			this._readOnly = !!this.getSetting("readOnly");
			this._enableMarking = !!this.getSetting("enableMarking");

			this._deleteNotifier = BX.CrmNotifier.create(this);
			this._markNotifier = BX.CrmNotifier.create(this);
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getEntityTypeName: function()
		{
			return this._entityTypeName;
		},
		getEntityId: function()
		{
			return this._entityInfo.getId();
		},
		getEntityInfo: function()
		{
			return this._entityInfo;
		},
		isMarkingEnabled: function()
		{
			return this._enableMarking;
		},
		enebleMarking: function(enable)
		{
			enable = !!enable;
			if(this._enableMarking === enable)
			{
				return;
			}

			this._enableMarking = enable;
			if(this._markButton)
			{
				this._markButton.style.display = this._enableMarking ? "" : "none";
			}
		},
		isMarked: function()
		{
			return this._isMarked;
		},
		setMarked: function(marked)
		{
			marked = !!marked;
			if(this._isMarked === marked)
			{
				return;
			}

			this._isMarked = marked;
			this._markNotifier.notify();
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			var wrapper = this._wrapper = BX.create("DIV", { attrs: { className: "crm-deal-client-selector-card" } });
			this._container.appendChild(wrapper);

			var innerWrapper = BX.create("DIV", { attrs: { className: "crm-deal-client-selector-table-list-title" } });
			wrapper.appendChild(innerWrapper);

			if(this._entityTypeName === "CONTACT")
			{
				BX.addClass(innerWrapper, "crm-deal-client-selector-tab-contact");
			}
			else if(this._entityTypeName === "COMPANY")
			{
				BX.addClass(innerWrapper, "crm-deal-client-selector-tab-company");
			}

			var imageWrapper = BX.create("DIV", { attrs: { className: "crm-deal-client-selector-table-list-resp-img" } });
			var imageUrl = this._entityInfo.getLargeImageUrl();
			if(imageUrl !== "")
			{
				imageWrapper.appendChild(BX.create("IMG", { attrs: { src: imageUrl } }));
			}
			innerWrapper.appendChild(imageWrapper);

			var denomWrapper = BX.create("DIV", { attrs: { className: "crm-deal-client-selector-table-list-title-name-block" } });
			innerWrapper.appendChild(denomWrapper);

			var titleWrapper = 	BX.create("DIV",
				{
					attrs: { className: "crm-deal-client-selector-table-list-title-name" },
					children:
						[
							BX.create("A",
								{
									attrs:
									{
										className: "crm-deal-client-selector-title-name",
										href: this._entityInfo.getShowUrl(),
										target: "_blank"
									},
									text: this._entityInfo.getTitle()
								}
							)
						]
				}
			);
			denomWrapper.appendChild(titleWrapper);

			if(!this._readOnly && this._enableMarking)
			{
				this._markButton = BX.create("DIV",
					{
						attrs:
						{
							className: "crm-client-selector-tab-flag",
							title: this._parent.getCustomMessage("markingTitle")
						}
					}
				);
				titleWrapper.appendChild(this._markButton);
				BX.bind(this._markButton, "click", this._markButtonHandler);
			}

			denomWrapper.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-deal-client-selector-table-list-title-descript" },
						text: this._entityInfo.getDescription()
					}
				)
			);

			if(!this._readOnly)
			{
				var delBtn = BX.create("DIV", { attrs: { className: "crm-client-selector-tab-close-btn" } });
				innerWrapper.appendChild(delBtn);
				BX.bind(delBtn, "click", this._deleteButtonHandler);
			}

			var fields, qty, i;
			//region Phones
			fields = this._entityInfo.getPhones();
			qty = fields.length;
			if(qty > 0)
			{
				for(i = 0; i < qty; i++)
				{
					this.createCommunicationItem(fields[i], innerWrapper);
				}
			}
			//endregion
			//region Emails
			fields = this._entityInfo.getEmails();
			qty = fields.length;
			if(qty > 0)
			{
				for(i = 0; i < qty; i++)
				{
					this.createCommunicationItem(fields[i], innerWrapper);
				}
			}
			//endregion

			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(this._markButton)
			{
				BX.bind(this._markButton, "click", this._markButtonHandler);
				this._markButton = BX.remove(this._markButton);
			}

			for(var i = 0; i < this._commynications.length; i++)
			{
				this._commynications[i].clearLayout();
			}
			this._commynications = [];

			if(this._wrapper)
			{
				this._wrapper = BX.remove(this._wrapper);
			}

			this._hasLayout = false;
		},
		addDeletionListener: function(listener)
		{
			this._deleteNotifier.addListener(listener);
		},
		removeDeletionListener: function(listener)
		{
			this._deleteNotifier.removeListener(listener);
		},
		addMarkingListener: function(listener)
		{
			this._markNotifier.addListener(listener);
		},
		removeMarkingListener: function(listener)
		{
			this._markNotifier.removeListener(listener);
		},
		createCommunicationItem: function(fieldInfo, container)
		{
			var comm = BX.CrmClientPanelCommunication.create(
				"",
				{
					container: container,
					entityId: this.getEntityId(),
					entityType: this.getEntityTypeName(),
					fieldInfo: fieldInfo
				}
			);
			comm.layout();
			this._commynications.push(comm);
		},
		onDeleteButtonClick: function(e)
		{
			this._deleteNotifier.notify();
		},
		onMarkButtonClick: function(e)
		{
			this.setMarked(true);
		}
	};
	BX.CrmClientPanelGroupItem.create = function(id, settings)
	{
		var self = new BX.CrmClientPanelGroupItem();
		self.initialize(id, settings);
		return self;
	};
}
//endregion

BX.CrmPopupWindowHelper = {};
BX.CrmPopupWindowHelper.prepareButtons = function(data)
{
	var result = [];
	for(var i = 0; i < data.length; i++)
	{
		var datum = data[i];
		result.push(
			datum['type'] === 'link'
				? new BX.PopupWindowButtonLink(datum['settings'])
				: new BX.PopupWindowButton(datum['settings']));
	}

	return result;
};

BX.CrmPopupWindowHelper.prepareTextField = function(settings)
{
	return BX.create(
		'DIV',
		{
			attrs: { className: 'bx-crm-dialog-quick-create-field' },
			children:
				[
					BX.create(
						'SPAN',
						{
							attrs: { className: 'bx-crm-dialog-quick-create-field-title' },
							text: settings['title'] + ':'
						}
					),
					BX.create(
						'INPUT',
						{
							attrs: { className: 'bx-crm-dialog-quick-create-field-text-input' },
							props: { id: settings['id'], value: settings['value'] }
						}
					)
				]
		}
	);
};

BX.CrmPopupWindowHelper.prepareSelectField = function(settings)
{
	var select = BX.create(
		'SELECT',
		{
			attrs: { className: 'bx-crm-dialog-quick-create-field-select' },
			props: { id: settings['id'] }
		}
	);

	var value = settings['value'] ? settings['value'] : '';

	if(settings['items'])
	{
		for(var i = 0; i < settings['items'].length; i++)
		{
			var item = settings['items'][i];
			var v = item['value'] ? item['value'] : i.toString();

			var option = BX.create(
				'OPTION',
				{
					text: item['text'] ? item['text'] : v,
					props: { value : v }
				}
			);

			if(!BX.browser.isIE)
			{
				select.add(option, null);
			}
			else
			{
				try
				{
					// for IE earlier than version 8
					select.add(option, select.options[null]);
				}
				catch (e)
				{
					select.add(option, null);
				}
			}

			if(v === value)
			{
				option.selected = true;
			}
		}
	}

	return BX.create(
		'DIV',
		{
			attrs: { className: 'bx-crm-dialog-quick-create-field' },
			children:
				[
					BX.create(
						'SPAN',
						{
							attrs: { className: 'bx-crm-dialog-quick-create-field-title' },
							text: settings['title'] + ':'
						}
					),
					select
				]
		}
	);
};

BX.CrmPopupWindowHelper.prepareTextAreaField = function(settings)
{
	return BX.create(
		'DIV',
		{
			attrs: { className: 'bx-crm-dialog-quick-create-field' },
			children:
				[
					BX.create(
						'SPAN',
						{
							attrs: { className: 'bx-crm-dialog-quick-create-field-title' },
							text: settings['title'] + ':'
						}
					),
					BX.create(
						'TEXTAREA',
						{
							attrs: { className: 'bx-crm-dialog-quick-create-field-text-input' },
							props: { id: settings['id'] },
							text: settings['value']
						}
					)
				]
		}
	);
};

BX.CrmPopupWindowHelper.prepareCheckBoxField = function(settings)
{
	var checkbox = BX.create(
		'INPUT',
		{
			attrs: { className: 'bx-crm-dialog-quick-create-field-checkbox' },
			props: { id: settings['id'], type: 'checkbox', checked: (!!settings['value']) ? 'checked' : '' }
		}
	);

	if(!!settings['value'])
	{
		checkbox.checked = true;
	}

	return BX.create(
		'DIV',
		{
			attrs: { className: 'bx-crm-dialog-quick-create-field' },
			children:
				[
					BX.create(
						'LABEL',
						{
							attrs: { className: 'bx-crm-dialog-quick-create-field-checkbox-label' },
							children:
							[
								checkbox,
								BX.create(
									'SPAN',
									{
										attrs: { className: 'bx-crm-dialog-quick-create-field-checkbox-label-text' },
										text: settings['title']
									}
								)
							]
						}
					)
				]
		}
	);
};

BX.CrmPopupWindowHelper.prepareTitle = function(text)
{
	return BX.create(
		'DIV',
		{
			attrs: { className: 'bx-crm-dialog-tittle-wrap' },
			children:
				[
					BX.create(
						'SPAN',
						{
							text: text,
							props: { className: 'bx-crm-dialog-title-text' }
						}
					)
				]
		}
	);
};

BX.CrmEntityDetailViewDialog = function()
{
	this._id = '';
	this._dlg = null;
	this._settings = {};
};

BX.CrmEntityDetailViewDialog.prototype =
{
	initialize: function(id, settings)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : 'CRM_ENTITY_DETAIL_VIEW_DIALOG_' + Math.random();
		this._settings = settings ? settings : {};
	},
	getId: function()
	{
		return this._id;
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	isOpened: function()
	{
		return this._dlg && this._dlg.isShown();
	},
	open: function()
	{
		if(this._dlg)
		{
			if(!this._dlg.isShown())
			{
				this._dlg.show();
			}
			return;
		}

		var container = BX(this.getSetting('containerId'));
		if(!container)
		{
			container = BX.findChild(BX('sidebar'), { 'class': 'crm-entity-info-details-container' }, true, false);
		}

		this._dlg = new BX.PopupWindow(
			this._id,
			null,
			{
				autoHide: false,
				draggable: true,
				offsetLeft: 0,
				offsetTop: 0,
				bindOptions: { forceBindPosition: false },
				closeByEsc: true,
				closeIcon: { top: '10px', right: '15px'},
				titleBar: this.getSetting('title', 'Details'),
				events:
				{
					onAfterPopupShow:  BX.delegate(this._onAfterPopupShow, this),
					onPopupDestroy: BX.delegate(this._onPopupDestroy, this)
				},
				content: container
			}
		);

		this._dlg.show();
	},
	close: function()
	{
		if(this._dlg && this._dlg.isShown())
		{
			this._dlg.close();
		}
	},
	toggle: function()
	{
		this.isOpened() ? this.close() : this.open();
	},
	_onAfterPopupShow: function()
	{
		var sidebarContainer = BX.findChild(BX('sidebar'), { 'class': 'sidebar-block' }, true, false);
		if(!sidebarContainer)
		{
			return;
		}
		var sidebarPos = BX.pos(sidebarContainer);

		var dialogContainer = this._dlg.popupContainer;
		if(!dialogContainer)
		{
			return;
		}
		var dialogPos = BX.pos(dialogContainer);

		dialogContainer.style.top = sidebarPos.top.toString() + 'px';
		dialogContainer.style.left = (sidebarPos.left - dialogPos.width - 1).toString() + 'px';
	},
	_onPopupDestroy: function()
	{
		this._dlg = null;
	}
};

BX.CrmEntityDetailViewDialog.items = {};
BX.CrmEntityDetailViewDialog.create = function(id, settings)
{
	var self = new BX.CrmEntityDetailViewDialog();
	self.initialize(id, settings);
	this.items[self.getId()] = self;
	return self;
};

BX.CrmEntityDetailViewDialog.ensureCreated = function(id, settings)
{
	return typeof(this.items[id]) !== 'undefined' ? this.items[id] : this.create(id, settings);
};

BX.CrmContactEditor = function()
{
	this._id = '';
	this._settings = {};
	this._dlg = null;
	this._clientField = null;
	this._mode = 'CREATE';
};

BX.CrmContactEditor.prototype =
{
	initialize: function(id, settings)
	{
		this._id = id;
		this._settings = settings ? settings : {};

		var initData = this.getSetting('data', null);
		if(initData)
		{
			this._mode = 'EDIT';
		}
		else
		{
			initData = {};
			this._mode = 'CREATE';
		}
		this._data = BX.CrmContactData.create(initData);
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	openDialog: function(anchor)
	{
		if(this._dlg)
		{
			this._dlg.setData(this._data);
			this._dlg.open(anchor);
			return;
		}

		this._dlg = BX.CrmContactEditDialog.create(
			this._id,
			this.getSetting('dialog', {}),
			this._data,
			BX.delegate(this._onSaveDialogData, this));

		if(this._dlg)
		{
			this._dlg.open(anchor, this._mode);
		}
	},
	closeDialog: function()
	{
		if(this._dlg)
		{
			this._dlg.close();
		}
	},
	openExternalFieldEditor: function(field)
	{
		this._clientField = field;
		this.openDialog();
	},
	_onSaveDialogData: function(dialog)
	{
		this._data = this._dlg.getData();

		var url = this.getSetting('serviceUrl', '');
		var action = this.getSetting('actionName', '');

		if(!(BX.type.isNotEmptyString(url) && BX.type.isNotEmptyString(action)))
		{
			return;
		}

		var self = this;
		BX.ajax(
			{
				'url': url,
				'method': 'POST',
				'dataType': 'json',
				'data':
				{
					'ACTION' : action,
					'DATA': this._data.toJSON(),
					'NAME_TEMPLATE': this.getSetting('nameTemplate', '')
				},
				onsuccess: function(data)
				{

					if(data['ERROR'])
					{
						self._showDialogError(data['ERROR']);
					}
					else if(!data['DATA'])
					{
						self._showDialogError('BX.CrmContactEditor: Could not find contact data!');
					}
					else
					{
						self._data = BX.CrmContactData.create(data['DATA']);
						var info = data['INFO'] ? data['INFO'] : {};
						self._clientField.setFieldValue(
							BX.type.isNotEmptyString(info['title'])
								? BX.util.htmlspecialchars(info['title']) : ''
						);
						self.closeDialog();
					}
				},
				onfailure: function(data)
				{
					self._showDialogError(data['ERROR'] ? data['ERROR'] : self.getMessage('unknownError'));
				}
			}
		);
	},
	_showDialogError: function(msg)
	{
		if(this._dlg)
		{
			this._dlg.showError(msg);
		}
	}
};

BX.CrmContactEditor.create = function(id, settings)
{
	var self = new BX.CrmContactEditor();
	self.initialize(id, settings);
	return self;
};

BX.CrmSonetSubscription = function()
{
	this._id = '';
	this._settings = {};
};

BX.CrmSonetSubscription.prototype =
{
	initialize: function(id, settings)
	{
		this._id = id;
		this._settings = settings ? settings : {};
	},
	getSetting: function (name, defaultval)
	{
		return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
	},
	enableSubscription: function(entityId, enable, callback)
	{
		var url = this.getSetting("serviceUrl", "");
		var action = this.getSetting("actionName", "");

		if(!(BX.type.isNotEmptyString(url) && BX.type.isNotEmptyString(action)))
		{
			return;
		}

		var reload = this.getSetting("reload", false);
		//var self = this;
		BX.ajax(
			{
				"url": url,
				"method": "POST",
				"dataType": "json",
				"data":
				{
					"ACTION" : action,
					"ENTITY_TYPE": this.getSetting("entityType", ""),
					"ENTITY_ID": entityId,
					"ENABLE": enable ? "Y" : "N"
				},
				onsuccess: function(data)
				{
					if(BX.type.isFunction(callback))
					{
						callback();
					}
				},
				onfailure: function(data) {}
			}
		);
	},
	subscribe: function(entityId, callback)
	{
		this.enableSubscription(entityId, true, callback);
	},
	unsubscribe: function(entityId, callback)
	{
		this.enableSubscription(entityId, false, callback);
	}
};

BX.CrmSonetSubscription.items = {};
BX.CrmSonetSubscription.create = function(id, settings)
{
	var self = new BX.CrmSonetSubscription();
	self.initialize(id, settings);
	this.items[id] = self;
	return self;
};

if(typeof(BX.CrmFormTabLazyLoader) == "undefined")
{
	BX.CrmFormTabLazyLoader = function()
	{
		this._id = "";
		this._settings = {};
		this._container = null;
		this._wrapper = null;
		this._serviceUrl = "";
		this._formId = "";
		this._tabId = "";
		this._params = {};
		this._formManager = null;

		this._isRequestRunning = false;
		this._isLoaded = false;

		this._waiter = null;
		this._scrollHandler = BX.delegate(this._onWindowScroll, this);
		this._formManagerHandler = BX.delegate(this._onFormManagerCreate, this);
	};

	BX.CrmFormTabLazyLoader.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_lf_disp_" + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._container = BX(this.getSetting("containerID", ""));
			if(!this._container)
			{
				throw "Error: Could not find container.";
			}

			this._wrapper = BX.findParent(this._container, { "tagName": "DIV", "className": "bx-edit-tab-inner" });

			this._serviceUrl = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw "Error. Could not find service url.";
			}

			this._formId = this.getSetting("formID", "");
			if(!BX.type.isNotEmptyString(this._formId))
			{
				throw "Error: Could not find form id.";
			}

			this._tabId = this.getSetting("tabID", "");
			if(!BX.type.isNotEmptyString(this._tabId))
			{
				throw "Error: Could not find tab id.";
			}

			this._params = this.getSetting("params", {});

			var formManager = window["bxForm_" + this._formId];
			if(formManager)
			{
				this.setFormManager(formManager);
			}
			else
			{
				BX.addCustomEvent(window, "CrmInterfaceFormCreated", this._formManagerHandler);
			}
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		load: function()
		{
			if(this._isLoaded)
			{
				return;
			}

			var params = this._params;
			params["FORM_ID"] = this._formId;
			params["TAB_ID"] = this._tabId;

			this._startRequest(params);
		},
		getContainerRect: function()
		{
			var r = this._container.getBoundingClientRect();
			return(
				{
					top: r.top, bottom: r.bottom, left: r.left, right: r.right,
					width: typeof(r.width) !== "undefined" ? r.width : (r.right - r.left),
					height: typeof(r.height) !== "undefined" ? r.height : (r.bottom - r.top)
				}
			);
		},
		isContanerInClientRect: function()
		{
			return this.getContainerRect().top <= document.documentElement.clientHeight;
		},
		setFormManager: function(formManager)
		{
			if(this._formManager === formManager)
			{
				return;
			}

			this._formManager = formManager;
			if(!this._formManager)
			{
				return;
			}

			if(this._formManager.GetActiveTabContainer() === this._wrapper)
			{
				if(this.isContanerInClientRect())
				{
					this.load();
				}
				else
				{
					BX.bind(window, "scroll", this._scrollHandler);
				}
			}
			else
			{
				BX.addCustomEvent(window, 'BX_CRM_INTERFACE_FORM_TAB_SELECTED', BX.delegate(this._onFormTabSelect, this));
			}
		},
		_startRequest: function(params)
		{
			if(this._isRequestRunning)
			{
				return false;
			}

			this._isRequestRunning = true;
			this._waiter = BX.showWait(this._container);
			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "html",
					data:
					{
						"LOADER_ID": this._id,
						"PARAMS": params
					},
					onsuccess: BX.delegate(this._onRequestSuccess, this),
					onfailure: BX.delegate(this._onRequestFailure, this)
				}
			);

			return true;
		},
		_onRequestSuccess: function(data)
		{
			this._isRequestRunning = false;

			if(this._waiter)
			{
				BX.closeWait(this._container, this._waiter);
				this._waiter = null;
			}

			this._container.innerHTML = data;
			this._isLoaded = true;
		},
		_onRequestFailure: function(data)
		{
			this._isRequestRunning = false;

			if(this._waiter)
			{
				BX.closeWait(this._container, this._waiter);
				this._waiter = null;
			}
			this._isLoaded = true;
		},
		_onFormManagerCreate: function(formManager)
		{
			if(formManager["name"] === this._formId)
			{
				BX.removeCustomEvent(window, "CrmInterfaceFormCreated", this._formManagerHandler);
				this.setFormManager(formManager);
			}
		},
		_onFormTabSelect: function(sender, formId, tabId, tabContainer)
		{
			if(this._formId === formId && this._wrapper === tabContainer)
			{
				this.load();
			}
		},
		_onWindowScroll: function(e)
		{
			if(!this._isLoaded && !this._isRequestRunning && this.isContanerInClientRect())
			{
				BX.unbind(window, "scroll", this._scrollHandler);
				this.load();
			}
		}
	};

	BX.CrmFormTabLazyLoader.items = {};
	BX.CrmFormTabLazyLoader.create = function(id, settings)
	{
		var self = new BX.CrmFormTabLazyLoader();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}

if(typeof(BX.CrmCustomDragItem) === "undefined")
{
	BX.CrmCustomDragItem = function()
	{
		this._id = "";
		this._settings = {};
		this._node = null;
		this._ghostNode = null;
		this._ghostOffset = { x: 0, y: 0 };

		this._previousPos = null;
		this._currentPos = null;

		this._enableDrag = true;
		this._isInDragMode = false;
		this._dragNotifier = null;
		this._preserveDocument = false;
		this._bodyOverflow = "";
	};
	BX.CrmCustomDragItem.prototype =
	{
		initialize: function(id, settings)
		{
			if(typeof(jsDD) === "undefined")
			{
				throw "CrmCustomDragItem: Could not find jsDD API.";
			}

			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(8);
			this._settings = settings ? settings : {};

			this._node = this.getSetting("node");
			if(!this._node)
			{
				throw "CrmCustomDragItem: The 'node' parameter is not defined in settings or empty.";
			}

			this._enableDrag = this.getSetting("enableDrag", true);
			this._ghostOffset = this.getSetting("ghostOffset", { x: 0, y: 0 });

			this._dragNotifier = BX.CrmNotifier.create(this);

			this.doInitialize();
			this.bindEvents();
		},
		doInitialize: function()
		{
		},
		release: function()
		{
			this.doRelease();
			this.unbindEvents();
		},
		doRelease: function()
		{
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		bindEvents: function()
		{
			this._node.onbxdragstart = BX.delegate(this._onDragStart, this);
			this._node.onbxdrag = BX.delegate(this._onDrag, this);
			this._node.onbxdragstop = BX.delegate(this._onDragStop, this);
			this._node.onbxdragrelease = BX.delegate(this._onDragRelease, this);

			jsDD.registerObject(this._node);

			this.doBindEvents();
		},
		doBindEvents: function()
		{
		},
		unbindEvents: function()
		{
			delete this._node.onbxdragstart;
			delete this._node.onbxdrag;
			delete this._node.onbxdragstop;
			delete this._node.onbxdragrelease;

			if(BX.type.isFunction(jsDD.unregisterObject))
			{
				jsDD.unregisterObject(this._node);
			}

			this.doUnbindEvents();
		},
		doUnbindEvents: function()
		{
		},
		createGhostNode: function()
		{
			throw "CrmCustomDragItem: The 'createGhostNode' function is not implemented.";
		},
		getGhostNode: function()
		{
			return this._ghostNode;
		},
		removeGhostNode: function()
		{
			throw "CrmCustomDragItem: The 'removeGhostNode' function is not implemented.";
		},
		processDragStart: function()
		{
		},
		processDragPositionChange: function(position)
		{
		},
		processDrag: function(x, y)
		{
		},
		processDragStop: function()
		{
		},
		addDragListener: function(listener)
		{
			this._dragNotifier.addListener(listener);
		},
		removeDragListener: function(listener)
		{
			this._dragNotifier.removeListener(listener);
		},
		getContextId: function()
		{
			return "";
		},
		getContextData: function()
		{
			return {};
		},
		getScrollTop: function()
		{
			var html = document.documentElement;
			var body = document.body;

			var scrollTop = html.scrollTop || body && body.scrollTop || 0;
			scrollTop -= html.clientTop;

			return scrollTop;
		},
		getScrollHeight: function()
		{
			var html = document.documentElement;
			var body = document.body;

			return html.scrollHeight || body && body.scrollHeight || 0;
		},
		isDragDropBinEnabled: function()
		{
			return true;
		},
		_onDragStart: function()
		{
			if(!this._enableDrag)
			{
				return;
			}

			this.createGhostNode();

			var pos = BX.pos(this._node);
			this._ghostNode.style.top = pos.top + "px";
			this._ghostNode.style.left = pos.left + "px";

			this._currentPos = this._previousPos = null;

			this._isInDragMode = true;
			BX.CrmCustomDragItem.currentDragged = this;

			BX.onCustomEvent('CrmDragItemDragStart', [this]);
			this.processDragStart();

			window.setTimeout(BX.delegate(this._prepareDocument, this), 0);
		},
		_onDrag: function(x, y)
		{
			if(!this._isInDragMode)
			{
				return;
			}

			var pos = { x: x, y: y };
			this.processDragPositionChange(pos);

			if(this._ghostNode)
			{
				this._ghostNode.style.top = (pos.y + this._ghostOffset.y) + "px";
				this._ghostNode.style.left = (pos.x + this._ghostOffset.x) + "px";
			}

			this._currentPos = pos;
			if(!this._previousPos)
			{
				this._previousPos = pos;
			}

			this._scrollIfNeed();

			this.processDrag(pos.x, pos.y);
			this._dragNotifier.notify([pos.x, pos.y]);

			this._previousPos = this._currentPos;
		},
		_onDragStop: function(x, y)
		{
			if(!this._isInDragMode)
			{
				return;
			}

			this.removeGhostNode();
			this._isInDragMode = false;
			if(BX.CrmCustomDragItem.currentDragged === this)
			{
				BX.CrmCustomDragItem.currentDragged = null;
			}

			this._currentPos = this._previousPos = null;

			BX.onCustomEvent('CrmDragItemDragStop', [this]);
			this.processDragStop();

			window.setTimeout(BX.delegate(this._resetDocument, this), 0);
		},
		_onDragRelease: function(x, y)
		{
			BX.onCustomEvent('CrmDragItemDragRelease', [this]);
		},
		_prepareDocument: function()
		{
			if(!this._preserveDocument)
			{
				this._bodyOverflow = document.body.style.overflow;
				document.body.style.overflow = "hidden";
			}
		},
		_resetDocument: function()
		{
			if(!this._preserveDocument)
			{
				document.body.style.overflow = this._bodyOverflow;
			}
		},
		_scrollIfNeed: function()
		{
			if(!this._ghostNode)
			{
				return;
			}

			var html = window.document.documentElement;
			var borderTop = html.clientTop;
			var borderBottom = html.clientTop + html.clientHeight;
			var scrollHeight = this.getScrollHeight();

			var offsetY = this._currentPos.y - this._previousPos.y;
			//var offsetX = this._currentPos.x - this._previousPos.x;
			//console.log("offsetY: %d", offsetY);
			if(offsetY === 0)
			{
				return;
			}

			var previousScrollTop = -1;
			for(;;)
			{
				var scrollTop = this.getScrollTop();
				var clientRect = this._ghostNode.getBoundingClientRect();
				//console.log("scrollTop:%d, { top: %d, bottom: %d }, border: { top: %d, bottom: %d }", scrollTop, clientRect.top, clientRect.bottom, borderTop, borderBottom);

				if(offsetY > 0 && ((clientRect.bottom > borderBottom) || (borderBottom - clientRect.bottom) < 64))
				{
					if(scrollTop >= scrollHeight || previousScrollTop === scrollTop)
					{
						break;
					}

					previousScrollTop = scrollTop;
					scrollTop += 1;
					window.scrollTo(0, scrollTop < scrollHeight ? scrollTop : scrollHeight);
					//console.log("scroll bottom: %d->%d", previousScrollTop, scrollTop);
				}
				else if(offsetY < 0 && ((borderTop > clientRect.top) || (clientRect.top - borderTop) < 64))
				{
					if(scrollTop <= 0 || previousScrollTop === scrollTop)
					{
						break;
					}

					previousScrollTop = scrollTop;
					scrollTop -= 1;
					window.scrollTo(0, scrollTop > 0 ? scrollTop : 0);
					//console.log("scroll bottom: %d->%d", previousScrollTop, scrollTop);
				}
				else
				{
					break;
				}
			}
		}
	};
	BX.CrmCustomDragItem.currentDragged = null;
	BX.CrmCustomDragItem.emulateDrag = function()
	{
		jsDD.refreshDestArea();
		if(jsDD.current_node)
		{
			//Emulation of drag event on previous drag position
			jsDD.drag({ clientX: (jsDD.x - jsDD.wndSize.scrollLeft), clientY: (jsDD.y - jsDD.wndSize.scrollTop) });
		}
	};
}
if(typeof(BX.CrmCustomDragContainer) === "undefined")
{
	BX.CrmCustomDragContainer = function()
	{
		this._id = "";
		this._settings = {};
		this._node = null;
		this._itemDragHandler = BX.delegate(this._onItemDrag, this);
		this._draggedItem = null;
		this._dragFinishNotifier = null;
		this._enabled = true;
	};
	BX.CrmCustomDragContainer.prototype =
	{
		initialize: function(id, settings)
		{
			if(typeof(jsDD) === "undefined")
			{
				throw "CrmCustomDragContainer: Could not find jsDD API.";
			}

			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(8);
			this._settings = settings ? settings : {};

			this._node = this.getSetting("node");
			if(!this._node)
			{
				throw "CrmCustomDragContainer: The 'node' parameter is not defined in settings or empty.";
			}

			this._dragFinishNotifier = BX.CrmNotifier.create(this);
			this.doInitialize();
			this.bindEvents();
		},
		doInitialize: function()
		{
		},
		release: function()
		{
			this.doRelease();
			this.unbindEvents();
		},
		doRelease: function()
		{
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		bindEvents: function()
		{
			this._node.onbxdestdraghover = BX.delegate(this._onDragOver, this);
			this._node.onbxdestdraghout = BX.delegate(this._onDragOut, this);
			this._node.onbxdestdragfinish = BX.delegate(this._onDragFinish, this);
			this._node.onbxdragstop = BX.delegate(this._onDragStop, this);
			this._node.onbxdragrelease = BX.delegate(this._onDragRelease, this);

			jsDD.registerDest(this._node, this.getPriority());

			this.doBindEvents();
		},
		doBindEvents: function()
		{
		},
		unbindEvents: function()
		{
			delete this._node.onbxdestdraghover;
			delete this._node.onbxdestdraghout;
			delete this._node.onbxdestdragfinish;
			delete this._node.onbxdragstop;
			delete this._node.onbxdragrelease;

			if(BX.type.isFunction(jsDD.unregisterDest))
			{
				jsDD.unregisterDest(this._node);
			}

			this.doUnbindEvents();
		},
		doUnbindEvents: function()
		{
		},
		createPlaceHolder: function(pos)
		{
			throw "CrmCustomDragContainer: The 'createPlaceHolder' function is not implemented.";
		},
		removePlaceHolder: function()
		{
			throw "CrmCustomDragContainer: The 'removePlaceHolder' function is not implemented.";
		},
		initializePlaceHolder: function(pos)
		{
			this.createPlaceHolder(pos);
			this.refresh();
		},
		releasePlaceHolder: function()
		{
			this.removePlaceHolder();
			this.refresh();
		},
		getPriority: function()
		{
			return BX.CrmCustomDragContainer.defaultPriority;
		},
		addDragFinishListener: function(listener)
		{
			this._dragFinishNotifier.addListener(listener);
		},
		removeDragFinishListener: function(listener)
		{
			this._dragFinishNotifier.removeListener(listener);
		},
		getDraggedItem: function()
		{
			return this._draggedItem;
		},
		setDraggedItem: function(draggedItem)
		{
			if(this._draggedItem === draggedItem)
			{
				return;
			}

			if(this._draggedItem)
			{
				this._draggedItem.removeDragListener(this._itemDragHandler);
			}

			this._draggedItem = draggedItem;

			if(this._draggedItem)
			{
				this._draggedItem.addDragListener(this._itemDragHandler);
			}
		},
		isAllowedContext: function(contextId)
		{
			return true;
		},
		isEnabled: function()
		{
			return this._enabled;
		},
		enable: function(enable)
		{
			enable = !!enable;
			if(this._enabled === enable)
			{
				return;
			}

			this._enabled = enable;
			if(enable)
			{
				jsDD.enableDest(this._node);
			}
			else
			{
				jsDD.disableDest(this._node);
			}
		},
		refresh: function()
		{
			jsDD.refreshDestArea(this._node.__bxddeid);
		},
		processDragOver: function(pos)
		{
			this.initializePlaceHolder(pos);
		},
		processDragOut: function()
		{
			this.releasePlaceHolder();
		},
		processDragStop: function()
		{
			this.releasePlaceHolder();
		},
		processDragRelease: function()
		{
			this.releasePlaceHolder();
		},
		processItemDrop: function()
		{
			this.releasePlaceHolder();
		},
		_onDragOver: function(node, x, y)
		{
			var draggedItem = BX.CrmCustomDragItem.currentDragged;
			if(!draggedItem)
			{
				return;
			}

			if(!this.isAllowedContext(draggedItem.getContextId()))
			{
				return;
			}

			this.setDraggedItem(draggedItem);
			this.processDragOver({ x: x, y: y });
		},
		_onDragOut: function(node, x, y)
		{
			if(!this._draggedItem)
			{
				return;
			}

			this.processDragOut();
			this.setDraggedItem(null);
		},
		_onDragFinish: function(node, x, y)
		{
			if(!this._draggedItem)
			{
				return;
			}

			this._dragFinishNotifier.notify([this._draggedItem, x, y]);

			this.processItemDrop();
			this.setDraggedItem(null);

			BX.CrmCustomDragContainer.refresh();
		},
		_onDragRelease: function(node, x, y)
		{
			if(!this._draggedItem)
			{
				return;
			}

			this.processDragRelease();
			this.setDraggedItem(null);
		},
		_onDragStop: function(node, x, y)
		{
			if(!this._draggedItem)
			{
				return;
			}

			this.processDragStop();
			this.setDraggedItem(null);
		},
		_onItemDrag: function(item, x, y)
		{
			if(!this._draggedItem)
			{
				return;
			}

			this.initializePlaceHolder({ x: x, y: y });
		}
	};
	BX.CrmCustomDragContainer.defaultPriority = 100;
	BX.CrmCustomDragContainer.refresh = function()
	{
		jsDD.refreshDestArea();
	};
}

BX.CrmDragDropBinState = { suspend: 0, wait: 1, ready: 2, open: 3, close: 4 };

if(typeof(BX.CrmDragDropBin) === "undefined")
{
	BX.CrmDragDropBin = function()
	{
		this._state = BX.CrmDragDropBinState.suspend;
		this._chargeItem = null;

		this._enableChargeItem = false;
		this._chargeDragStartHandler = BX.delegate(this._onChargeDragStart, this);
		this._chargeDragStopHandler = BX.delegate(this._onChargeDragStop, this);
		this._chargeDragReleaseHandler = BX.delegate(this._onChargeDragRelease, this);
		this._chargeDragHandler = BX.delegate(this._onChargeDrag, this);

		this._workareaRect = null;

		this._promptingWrapper = null;
		this._closePromptingButtonId = "crm_dd_bin_close_prompting_btn";
		this._closePromptingHandler = BX.delegate(this._onClosePromptingButtonClick, this);

		this._demoButtonId = "crm_dd_bin_demo_btn";
		this._demoHandler = BX.delegate(this._onDemoButtonClick, this);

	};
	BX.extend(BX.CrmDragDropBin, BX.CrmCustomDragContainer);
	BX.CrmDragDropBin.prototype.doInitialize = function()
	{
		BX.addCustomEvent(window, "CrmDragItemDragStart", this._chargeDragStartHandler);
		BX.addCustomEvent(window, "CrmDragItemDragStop", this._chargeDragStopHandler);
		BX.addCustomEvent(window, "CrmDragItemDragRelease", this._chargeDragReleaseHandler);

		this.cacheWorkareaRect();
		BX.bind(window, "resize", BX.delegate(this._onWindowResize, this));
	};
	BX.CrmDragDropBin.prototype.getPriority = function()
	{
		return 10;
	};
	BX.CrmDragDropBin.prototype.createPlaceHolder = function(pos)
	{
	};
	BX.CrmDragDropBin.prototype.removePlaceHolder = function()
	{
	};
	BX.CrmDragDropBin.prototype.processDragOver = function(pos)
	{
		if(this._chargeItem)
		{
			this._enableChargeItem = false;
		}
		this.setState(BX.CrmDragDropBinState.open);
	};
	BX.CrmDragDropBin.prototype.processDragOut = function()
	{
		if(this._chargeItem)
		{
			this._enableChargeItem = true;
		}
		this.setState(BX.CrmDragDropBinState.ready);
	};
	BX.CrmDragDropBin.prototype.processDragStop = function()
	{
		this.setState(BX.CrmDragDropBinState.suspend);
	};
	BX.CrmDragDropBin.prototype.processDragRelease = function()
	{
		this.setState(BX.CrmDragDropBinState.suspend);
	};
	BX.CrmDragDropBin.prototype.processItemDrop = function()
	{
		if(this._chargeItem)
		{
			this._chargeItem.removeDragListener(this._chargeDragHandler);
			this._chargeItem = null;
		}
		this._enableChargeItem = false;

		this.setState(BX.CrmDragDropBinState.close);
		window.setTimeout(BX.delegate(this.reset, this), 1000);
		BX.onCustomEvent(this, "CrmDragDropBinItemDrop", [ this, this.getDraggedItem() ]);
	};
	BX.CrmDragDropBin.prototype.getState = function()
	{
		return this._state;
	};
	BX.CrmDragDropBin.prototype.reset = function()
	{
		this.setState(BX.CrmDragDropBinState.suspend);
	};
	BX.CrmDragDropBin.prototype.setState = function(state)
	{
		state = parseInt(state);
		if(state < BX.CrmDragDropBinState.suspend || state > BX.CrmDragDropBinState.close)
		{
			state = BX.CrmDragDropBinState.suspend;
		}

		if(this._state === state)
		{
			return;
		}

		this._state = state;

		var classNames = ["crm-cart-block-wrap"];
		if(this._state >= BX.CrmDragDropBinState.wait)
		{
			classNames.push("crm-cart-start");
		}
		if(this._state >= BX.CrmDragDropBinState.ready)
		{
			classNames.push("crm-cart-active");
		}
		if(this._state >= BX.CrmDragDropBinState.open)
		{
			classNames.push("crm-cart-hover");
		}
		if(this._state === BX.CrmDragDropBinState.close)
		{
			classNames.push("crm-cart-finish");
		}

		this._node.className = classNames.join(" ");

		window.setTimeout(BX.delegate(BX.CrmCustomDragItem.emulateDrag, this), 400);
		window.setTimeout(BX.delegate(BX.CrmCustomDragItem.emulateDrag, this), 800);
	};
	BX.CrmDragDropBin.prototype._onChargeDragStart = function(item)
	{
		if(!item.isDragDropBinEnabled())
		{
			return;
		}

		this._enableChargeItem = true;
		this._chargeItem = item;
		this._chargeItem.addDragListener(this._chargeDragHandler);

		this.setState(BX.CrmDragDropBinState.wait);
	};
	BX.CrmDragDropBin.prototype._onChargeDragStop = function(item)
	{
		if(!this._enableChargeItem || this._chargeItem !== item)
		{
			return;
		}

		this._chargeItem.removeDragListener(this._chargeDragHandler);
		this._chargeItem = null;
		this._enableChargeItem = false;

		this.setState(BX.CrmDragDropBinState.suspend);
	};
	BX.CrmDragDropBin.prototype._onChargeDragRelease = function(item)
	{
		if(!this._enableChargeItem || this._chargeItem !== item)
		{
			return;
		}

		this._chargeItem.removeDragListener(this._chargeDragHandler);
		this._chargeItem = null;
		this._enableChargeItem = false;

		this.setState(BX.CrmDragDropBinState.suspend);
	};
	BX.CrmDragDropBin.prototype._onChargeDrag = function(item, x, y)
	{
		if(this._enableChargeItem && this._chargeItem === item)
		{
			this.adjust();
		}
	};
	BX.CrmDragDropBin.prototype._onWindowResize = function(e)
	{
		this.cacheWorkareaRect();
	};
	BX.CrmDragDropBin.prototype.cacheWorkareaRect = function()
	{
		var workarea = BX("workarea");
		if(!workarea)
		{
			workarea = document.documentElement;
		}
		this._workareaRect = BX.pos(workarea);
		this._readyThreshold = this._workareaRect.width / 6;
	};
	BX.CrmDragDropBin.prototype.adjust = function()
	{
		if(!this._chargeItem)
		{
			return;
		}

		var ghostNode = this._chargeItem.getGhostNode();
		if(!ghostNode)
		{
			return;
		}

		var ghostRect = BX.pos(ghostNode);
		var isReady = this._state >= BX.CrmDragDropBinState.ready;
		if(isReady !== ((this._workareaRect.right - ghostRect.left) <= this._readyThreshold))
		{
			isReady = !isReady;
			this.setState(isReady ? BX.CrmDragDropBinState.ready : BX.CrmDragDropBinState.wait);
		}
	};
	BX.CrmDragDropBin.prototype.getMessage = function(name, defaultval)
	{
		var m = BX.CrmDragDropBin.messages;
		return m.hasOwnProperty(name) ? m[name] : defaultval;
	};
	BX.CrmDragDropBin.prototype.showPromptingIfRequired = function(container)
	{
		if(BX.localStorage.get("crm_dd_bin_show_prompt") !== "N")
		{
			this.showPrompting(container);
		}
	};
	BX.CrmDragDropBin.prototype.showPrompting = function(container)
	{
		if(this._promptingWrapper)
		{
			return;
		}

		var msg = this.getMessage("prompting");
		msg = msg.replace("#CLOSE_BTN_ID#", this._closePromptingButtonId).replace("#DEMO_BTN_ID#", this._demoButtonId);
		this._promptingWrapper = BX.create("DIV", { attrs: { className: "crm-view-message" }, html: msg });
		container.appendChild(this._promptingWrapper);

		BX.bind(BX(this._closePromptingButtonId), "click", this._closePromptingHandler);
		BX.bind(BX(this._demoButtonId), "click", this._demoHandler);
	};
	BX.CrmDragDropBin.prototype.hidePrompting = function()
	{
		if(!this._promptingWrapper)
		{
			return;
		}

		BX.localStorage.set("crm_dd_bin_show_prompt", "N", 31104000);
		BX.unbind(BX(this._closePromptingButtonId), "click", this._closePromptingHandler);
		BX.unbind(BX(this._demoButtonId), "click", this._demoHandler);
		BX.remove(this._promptingWrapper);
	};
	BX.CrmDragDropBin.prototype.demo = function()
	{
		this.setState(BX.CrmDragDropBinState.wait);

		var self = this;
		window.setTimeout(function(){ self.setState(BX.CrmDragDropBinState.ready); }, 1000);
		window.setTimeout(function(){ self.setState(BX.CrmDragDropBinState.open); }, 1500);
		window.setTimeout(function(){ self.setState(BX.CrmDragDropBinState.close); }, 2000);
	};
	BX.CrmDragDropBin.prototype._onDemoButtonClick = function(e)
	{
		this.demo();
		return BX.PreventDefault(e);
	};
	BX.CrmDragDropBin.prototype._onClosePromptingButtonClick = function(e)
	{
		this.hidePrompting();
		return BX.PreventDefault(e);
	};
	BX.CrmDragDropBin.instance = null;
	BX.CrmDragDropBin.getInstance = function()
	{
		if(this.instance)
		{
			return this.instance;
		}

		var node = BX.create("DIV",
			{
				attrs: { className: "crm-cart-block-wrap" },
				children:
				[
					BX.create("DIV",
						{
							attrs: { className: "crm-cart-block" },
							children:
							[
								BX.create("DIV",
									{
										attrs: { className: "crm-cart-icon" },
										children:
										[
											BX.create("DIV", { attrs: { className: "crm-cart-icon-top" } }),
											BX.create("DIV", { attrs: { className: "crm-cart-icon-body" } })
										]
									}
								)
							]
						}
					)
				]
			}
		);
		document.body.appendChild(node);
		var self = new BX.CrmDragDropBin();
		self.initialize("default", { node: node });
		return (this.instance = self);
	};

	if(typeof(BX.CrmDragDropBin.messages) === "undefined")
	{
		BX.CrmDragDropBin.messages = {};
	}
}

if(typeof(BX.CrmLocalitySearchField) === "undefined")
{
	BX.CrmLocalitySearchField = function()
	{
		this._id = "";
		this._settings = {};
		this._localityType = "";
		this._serviceUrl = "";
		this._searchInput = null;
		this._dataInput = null;
		this._timeoutId = 0;
		this._value = "";
		this._items = [];
		this._menuId = "crm-locality-search";
		this._menu = null;
		this._isRequestStarted = false;

		this._checkHandler = BX.delegate(this.check, this);
		this._keyPressHandler = BX.delegate(this.onKeyPress, this);
		this._menuItemClickHandler = BX.delegate(this.onMenuItemClick, this);
		this._searchCompletionHandler =  BX.delegate(this.onSearchRequestComplete, this);
	};

	BX.CrmLocalitySearchField.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : ('crm_loc_search_field_' + Math.random());
			this._settings = settings ? settings : {};

			this._localityType = this.getSetting("localityType");
			if(!BX.type.isNotEmptyString(this._localityType))
			{
				throw  "BX.CrmLocalitySearchField: localityType is not found!";
			}

			this._serviceUrl = this.getSetting("serviceUrl");
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw  "BX.CrmLocalitySearchField: serviceUrl is not found!";
			}

			this._searchInput = this.findElement("searchInput");
			if(!BX.type.isElementNode(this._searchInput))
			{
				throw  "BX.CrmLocalitySearchField: searchInput is not found!";
			}

			this._dataInput = this.findElement("dataInput");
			if(!BX.type.isElementNode(this._dataInput))
			{
				throw  "BX.CrmLocalitySearchField: dataInputId is not found!";
			}

			BX.bind(this._searchInput, "keyup", BX.proxy(this._keyPressHandler, this));
			BX.bind(document, "click", BX.delegate(this._handleExternalClick, this));
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		findElement: function(paramName)
		{
			var param = this.getSetting(paramName);
			if(BX.type.isElementNode(paramName))
			{
				return param;
			}

			var element = null;
			if(BX.type.isNotEmptyString(param))
			{
				element = BX(param);
				if(!element)
				{
					var elements = document.getElementsByName(param);
					if(elements.length > 0)
					{
						element = elements[0];
					}
				}
			}
			return element !== undefined ? element : null;
		},
		check: function()
		{
			this._timeoutId = 0;
			if(this._value !== this._searchInput.value)
			{
				this._value = this._searchInput.value;
				this._timeoutId = window.setTimeout(this._checkHandler, 750);
			}
			else if(this._value.length >= 2)
			{
				this.startSearchRequest(this._value);
			}
		},
		startSearchRequest: function(needle)
		{
			if(this._isRequestStarted)
			{
				return false;
			}

			this._isRequestStarted = true;

			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION" : "FIND_LOCALITIES",
						"LOCALITY_TYPE": this._localityType,
						"NEEDLE": needle
					},
					onsuccess: this._searchCompletionHandler,
					onfailure: this._searchCompletionHandler
				}
			);
		},
		showMenu: function(items)
		{
			BX.PopupMenu.destroy(this._menuId);

			var menuItems = [];
			for(var i = 0; i < items.length; i++)
			{
				menuItems.push(this.prepareMenuItem(items[i]));
			}

			this._menu = BX.PopupMenu.create(this._menuId, this._searchInput, menuItems, { offsetTop:0, offsetLeft:0 });
			this._menu.popupWindow.show();
		},
		closeMenu: function()
		{
			BX.PopupMenu.destroy(this._menuId);
			this._menu = null;
		},
		prepareMenuItem: function(data)
		{
			var code = BX.type.isNotEmptyString(data["CODE"]) ? data["CODE"] : "";
			if(code === "")
			{
				throw  "BX.CrmLocalitySearchField: could not find item code!";
			}
			var caption = BX.type.isNotEmptyString(data["CAPTION"]) ? data["CAPTION"] : code;
			return { value: code,  text: caption, onclick: this._menuItemClickHandler };
		},
		onMenuItemClick: function(e, item)
		{
			this.selectItem(item);
			this.closeMenu();
		},
		selectItem: function(item)
		{
			this._dataInput.value = item["value"];
			this._searchInput.value = item["text"];
		},
		onKeyPress: function(e)
		{
			if(this._timeoutId !== 0)
			{
				window.clearTimeout(this._timeoutId);
				this._timeoutId = 0;
			}
			this._timeoutId = window.setTimeout(this._checkHandler, 375);
		},
		onSearchRequestComplete: function(result)
		{
			this._isRequestStarted = false;

			var items = typeof(result["DATA"]) !== "undefined" && typeof(result["DATA"]["ITEMS"]) !== "undefined"
				? result["DATA"]["ITEMS"] : [];

			if(items.length > 0)
			{
				this.showMenu(items);
			}
		}
	};

	BX.CrmLocalitySearchField.create = function(id, settings)
	{
		var self = new BX.CrmLocalitySearchField();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmAddressType) === "undefined")
{
	BX.CrmAddressType =
	{
		undefined: 0,
		primary: 1,
		secondary: 2,
		third: 3,
		home: 4,
		work: 5,
		registered: 6,
		custom: 7,
		post: 8,
		beneficiary: 9,
		bank: 10,
		delivery: 11,
		billing: 12
	};
}

if(typeof(BX.CrmMultipleAddressEditor) === "undefined")
{
	BX.CrmMultipleAddressEditor = function()
	{
		this._id = "";
		this._settings = {};

		this._fieldId = "";
		this._formId = "";
		this._scheme = null;
		this._data = null;
		this._currentTypeId = 0;
		this._typeInfos = null;
		this._fieldLabels = null;

		this._fielNameTemplate = "";
		this._serviceUrl = "";

		this._container = null;
		this._createButton = null;
		this._typeMenuButton = null;

		this._createButtonHandler = BX.delegate(this.onCreateButtonClick, this);
		this._typenuButtonHandler = BX.delegate(this.onTypeMenuButtonClick, this);
		this._typeMenuItemClickHandler = BX.delegate(this.onTypeMenuItemClick, this);
		this._typeMenuCloseHandler = BX.delegate(this.onTypeMenuClose, this);
		this._typeMenuId = "";
		this._isTypeMenuOpened = false;
		this._items = {};
		this._entityAddresses = {};
	};

	BX.CrmMultipleAddressEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : ("crm_multiaddr_" + Math.random());
			this._settings = settings ? settings : {};

			this._fieldId = this.getSetting("fieldId", "");
			this._formId = this.getSetting("formId", "");

			this._scheme = this.getSetting("scheme");
			if(!BX.type.isArray(this._scheme))
			{
				throw "BX.CrmMultipleAddressEditor: Could not find a parameter named 'scheme'.";
			}

			this._data = this.getSetting("data");
			if(!BX.type.isPlainObject(this._data))
			{
				throw "BX.CrmMultipleAddressEditor: Could not find a parameter named 'data'.";
			}

			this._typeInfos = this.getSetting("typeInfos");
			if(!BX.type.isArray(this._typeInfos))
			{
				throw "BX.CrmMultipleAddressEditor: Could not find a parameter named 'typeInfos'.";
			}

			this._fieldLabels = this.getSetting("fieldLabels");
			if(!BX.type.isPlainObject(this._fieldLabels))
			{
				throw "BX.CrmMultipleAddressEditor: Could not find a parameter named 'fieldLabels'.";
			}

			this._container = this.getSetting("container");
			if(!BX.type.isElementNode(this._container))
			{
				throw "BX.CrmMultipleAddressEditor: Could not find a parameter named 'container'.";
			}

			this._itemWrapper = BX.findChildByClassName(this._container, "crm-multi-address");

			var createButtonContainer = this.getSetting("createButtonContainer");
			if(BX.type.isElementNode(createButtonContainer))
			{
				this._createButton = BX.findChildByClassName(createButtonContainer, "crm-offer-requisite-option-text");
				if(this._createButton)
				{
					BX.bind(this._createButton, "click", this._createButtonHandler);
				}

				this._typeMenuButton = BX.findChildByClassName(createButtonContainer, "crm-offer-requisite-option-arrow");
				if(this._typeMenuButton)
				{
					BX.bind(this._typeMenuButton, "click", this._typenuButtonHandler);
				}
			}

			this._currentTypeId = this.getSetting("currentTypeId");
			this._fielNameTemplate = this.getSetting("fielNameTemplate", "");
			this._serviceUrl = this.getSetting("serviceUrl", "");

			for(var key in this._data)
			{
				if(!this._data.hasOwnProperty(key))
				{
					continue;
				}

				var typeId = parseInt(key);
				var itemId = this._id + "_" + typeId.toString();

				var containerId = this._fielNameTemplate
					.replace("#TYPE_ID#", typeId)
					.replace("#FIELD_NAME#", "wrapper")
					.toLowerCase();

				this._items[itemId] = BX.CrmMultipleAddressItemEditor.create(
					itemId,
					{
						typeId: typeId,
						fields: this._data[key],
						editor: this,
						container: BX(containerId),
						hasLayout: true,
						isPersistent: true
					}
				);
			}
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getFieldId: function()
		{
			return this._fieldId;
		},
		getFormId: function()
		{
			return this._formId;
		},
		getScheme: function()
		{
			return this._scheme;
		},
		getTypeName: function(typeId)
		{
			for(var i = 0; i < this._typeInfos.length; i++)
			{
				var info = this._typeInfos[i];
				if(info["id"] == typeId)
				{
					return info["name"];
				}
			}

			return typeId;
		},
		getFieldLabel: function(name)
		{
			return this._fieldLabels.hasOwnProperty(name) ? this._fieldLabels[name] : name;
		},
		getFieldNameTemplate: function()
		{
			return this._fielNameTemplate;
		},
		prepareQualifiedName: function(name, params)
		{
			if(this._fielNameTemplate !== "")
			{
				if(!BX.type.isPlainObject(params))
				{
					params = {};
				}

				var typeId = typeof(params["typeId"]) !== "undefined" ? params["typeId"] : this._currentTypeId;
				name = this._fielNameTemplate.replace("#TYPE_ID#", typeId).replace("#FIELD_NAME#", name);
			}
			return name;
		},
		getServiceUrl: function()
		{
			return this._serviceUrl;
		},
		getMessage: function(name)
		{
			var msg = BX.CrmMultipleAddressEditor.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		createItem: function(typeId, originatorId, silent)
		{
			if(!BX.type.isNumber(typeId))
			{
				typeId = parseInt(typeId);
			}

			if(isNaN(typeId) || typeId <= 0)
			{
				typeId = this._currentTypeId;
			}

			if(!BX.type.isString(originatorId))
			{
				originatorId = "";
			}

			var itemContainer = BX.create("DIV",
				{
					attrs: { className: "crm-multi-address-item" }
				}
			);
			this._itemWrapper.appendChild(itemContainer);

			var isPersistent = false;
			var itemId = this._id + "_" + typeId.toString();
			if(this._items.hasOwnProperty(itemId))
			{
				var presetItem = this._items[itemId];
				if(presetItem.isMarkedAsDeleted())
				{
					isPersistent = presetItem.isPersistent();
					this.removeItem(presetItem, true);
				}
				else
				{
					if (!silent)
					{
						window.alert(
							this.getMessage("alreadyExists")
								.replace("#TYPE_NAME#", this.getTypeName(typeId))
						);
					}
					return false;
				}
			}

			var item = BX.CrmMultipleAddressItemEditor.create(
				itemId,
				{
					typeId: typeId,
					fields: {},
					editor: this,
					container: itemContainer,
					hasLayout: false,
					isPersistent: isPersistent,
					originatorId: originatorId
				}
			);
			this._items[itemId] = item;
			item.layout();

			if(this._itemWrapper.style.display === "none")
			{
				this._itemWrapper.style.display = "";
			}

			BX.onCustomEvent(this, "CrmMultipleAddressItemCreated", [this, item]);

			return item;
		},
		removeItem: function(item, forced)
		{
			var itemId = item.getId();
			if(!this._items.hasOwnProperty(itemId))
			{
				return false;
			}

			if(!item.isPersistent() || !!forced)
			{
				item.cleanLayout();
				BX.remove(item.getContainer());
				delete this._items[itemId];
			}

			if(this.getActiveItemCount() === 0)
			{
				this._itemWrapper.style.display = "none";
			}

			return true;
		},
		getItemByTypeId: function(typeId)
		{
			typeId = parseInt(typeId);
			if(isNaN(typeId) || typeId <= 0)
			{
				return null;
			}

			for(var k in this._items)
			{
				if(!this._items.hasOwnProperty(k))
				{
					continue;
				}

				var item = this._items[k];
				if(typeId === item.getTypeId())
				{
					return item;
				}
			}

			return null;
		},
		getActiveItemCount: function()
		{
			var result = 0;
			for(var k in this._items)
			{
				if(this._items.hasOwnProperty(k) && !this._items[k].isMarkedAsDeleted())
				{
					result++;
				}
			}
			return result;
		},
		loadEntityAddress: function(typeId, entityTypeId, entityId, callback)
		{
			if(BX.type.isPlainObject(this._entityAddresses[entityTypeId])
				&& BX.type.isPlainObject(this._entityAddresses[entityTypeId][entityId])
				&& BX.type.isPlainObject(this._entityAddresses[entityTypeId][entityId][typeId]))
			{
				callback(this._entityAddresses[entityTypeId][entityId][typeId]["fields"]);
				return;
			}

			if(!BX.type.isPlainObject(this._entityAddresses[entityTypeId]))
			{
				this._entityAddresses[entityTypeId] = {};
			}

			if(!BX.type.isPlainObject(this._entityAddresses[entityTypeId][entityId]))
			{
				this._entityAddresses[entityTypeId][entityId] = {};
			}

			if(!BX.type.isPlainObject(this._entityAddresses[entityTypeId][entityId][typeId]))
			{
				this._entityAddresses[entityTypeId][entityId][typeId] = {};
			}

			if(BX.type.isFunction(callback))
			{
				if(!BX.type.isArray(this._entityAddresses[entityTypeId][entityId][typeId]["callbacks"]))
				{
					this._entityAddresses[entityTypeId][entityId][typeId]["callbacks"] = [];
				}

				this._entityAddresses[entityTypeId][entityId][typeId]["callbacks"].push(callback);
			}

			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION": "GET_ENTITY_ADDRESS",
						"ENTITY_TYPE_ID": entityTypeId,
						"ENTITY_ID": entityId,
						"TYPE_ID": typeId
					},
					onsuccess: BX.delegate(this.onEntityAddressLoadSuccess, this)
				}
			);
		},

		openTypeMenu: function()
		{
			if(this._isTypeMenuOpened)
			{
				return;
			}

			var menuItems = [];
			for(var i = 0; i < this._typeInfos.length; i++)
			{
				var info = this._typeInfos[i];
				menuItems.push(
					{
						text: info["name"],
						id: info["id"],
						className: "crm-convert-item",
						onclick: this._typeMenuItemClickHandler
					}
				);
			}

			if(typeof(BX.PopupMenu.Data[this._typeMenuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._typeMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._typeMenuId];
			}

			BX.PopupMenu.show(
				this._typeMenuId,
				this._typeMenuButton,
				menuItems,
				{
					autoHide: true,
					offsetLeft: (BX.pos(this._typeMenuButton)["width"] / 2),
					angle: { position: "top", offset: 0 },
					events: { onPopupClose : this._typeMenuCloseHandler }
				}
			);

			this._isTypeMenuOpened = true;
		},
		closeTypeMenu: function()
		{
			if(this._isTypeMenuOpened)
			{
				BX.PopupMenu.destroy(this._typeMenuId);
				this._isTypeMenuOpened = false;
			}
		},
		onCreateButtonClick: function(e)
		{
			this.createItem(this._currentTypeId);
		},
		onTypeMenuButtonClick: function(e)
		{
			if(!this._isTypeMenuOpened)
			{
				this.openTypeMenu();
			}
			else
			{
				this.closeTypeMenu();
			}

			return BX.PreventDefault(e);
		},
		onTypeMenuItemClick: function(e, item)
		{
			this._currentTypeId = parseInt(item["id"]);
			this._createButton.innerHTML = this.getTypeName(this._currentTypeId);
			this.closeTypeMenu();
		},
		onTypeMenuClose: function(e)
		{
			this._isTypeMenuOpened = false;
		},
		onEntityAddressLoadSuccess: function(result)
		{
			if(!BX.type.isPlainObject(result["DATA"]))
			{
				return;
			}

			var data = result["DATA"];
			var entityTypeId = typeof(data["ENTITY_TYPE_ID"]) ? parseInt(data["ENTITY_TYPE_ID"]) : 0;
			var entityId = typeof(data["ENTITY_ID"]) ? parseInt(data["ENTITY_ID"]) : 0;
			if(isNaN(entityTypeId) || entityTypeId <= 0 || isNaN(entityId) || entityId <= 0)
			{
				return;
			}

			if(!BX.type.isPlainObject(this._entityAddresses[entityTypeId]))
			{
				this._entityAddresses[entityTypeId] = {};
			}

			if(!BX.type.isPlainObject(this._entityAddresses[entityTypeId][entityId]))
			{
				this._entityAddresses[entityTypeId][entityId] = {};
			}

			var fields = BX.type.isPlainObject(data["FIELDS"]) ? data["FIELDS"] : null;
			var typeId = fields !== null && typeof(fields['TYPE_ID']) !== "undefined" ? parseInt(fields['TYPE_ID']) : 1;

			if(!BX.type.isPlainObject(this._entityAddresses[entityTypeId][entityId][typeId]))
			{
				this._entityAddresses[entityTypeId][entityId][typeId] = {};
			}

			this._entityAddresses[entityTypeId][entityId][typeId]["fields"] = fields;

			if(BX.type.isArray(this._entityAddresses[entityTypeId][entityId][typeId]["callbacks"]))
			{
				var callbacks = this._entityAddresses[entityTypeId][entityId][typeId]["callbacks"];
				for(var i = 0; i < callbacks.length; i++)
				{
					callbacks[i](fields);
				}
				delete this._entityAddresses[entityTypeId][entityId][typeId]["callbacks"];
			}
		}
	};

	if(typeof(BX.CrmMultipleAddressEditor.messages) === "undefined")
	{
		BX.CrmMultipleAddressEditor.messages =
		{
		};
	}
	BX.CrmMultipleAddressEditor.items = {};
	BX.CrmMultipleAddressEditor.getItemsByFormId = function(formId)
	{
		var item;

		formId = BX.type.isNotEmptyString(formId) ? formId : "";
		if(formId === "")
		{
			return [];
		}

		var results = [];
		for(var k in this.items)
		{
			if(this.items.hasOwnProperty(k))
			{
				item = this.items[k];
				if(item.getFormId() === formId)
				{
					results.push(item);
				}
			}
		}
		return results;
	};
	BX.CrmMultipleAddressEditor.create = function(id, settings)
	{
		var self = new BX.CrmMultipleAddressEditor();
		self.initialize(id, settings);
		BX.CrmMultipleAddressEditor.items[self.getId()] = self;
		return self;
	};
}

if(typeof(BX.CrmMultipleAddressItemEditor) === "undefined")
{
	BX.CrmMultipleAddressItemEditor = function()
	{
		this._id = "";
		this._settings = {};
		this._typeId = 0;
		this._fields = {};
		this._editor = null;
		this._container = null;
		this._isPersistent = false;
		this._isMarkedAsDeleted = false;
		this._hasLayout = false;
		this._originatorId = "";
		this._deleteButton = null;
		this._deleteButtonHandler = BX.delegate(this.onDeleteButtonClick, this);
	};

	BX.CrmMultipleAddressItemEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : ("crm_multiaddr_item_" + Math.random());
			this._settings = settings ? settings : {};

			this._typeId = parseInt(this.getSetting("typeId", 0));
			this._fields = this.getSetting("fields", {});

			this._editor = this.getSetting("editor");
			if(!(this._editor instanceof BX.CrmMultipleAddressEditor))
			{
				throw "BX.CrmMultipleAddressItemEditor: Could not find a parameter named 'editor'.";
			}

			this._container = this.getSetting("container");
			if(!BX.type.isElementNode(this._container))
			{
				throw "BX.CrmMultipleAddressItemEditor: Could not find a parameter named 'container'.";
			}

			this._isPersistent = !!this.getSetting("isPersistent", false);

			this._hasLayout = !!this.getSetting("hasLayout", false);
			if(this._hasLayout)
			{
				this._deleteButton = BX.findChildByClassName(this._container, "crm-offer-title-del");
				if(!BX.type.isElementNode(this._deleteButton))
				{
					throw "BX.CrmMultipleAddressItemEditor: Could not find the 'Delete' button.";
				}

				this.bind();
			}

			this._originatorId = this.getSetting("originatorId", "");
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getMessage: function(name)
		{
			var msg = BX.CrmMultipleAddressItemEditor.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		getTypeId: function()
		{
			return this._typeId;
		},
		getContainer: function()
		{
			return this._container;
		},
		getOriginatorId: function()
		{
			return this._originatorId;
		},
		getFieldControl: function(fieldName)
		{
			var qualifiedName = this._editor.prepareQualifiedName(fieldName, { typeId: this._typeId });
			var ctrls = document.getElementsByName(qualifiedName);
			return ctrls.length > 0 ? ctrls[0] : null;
		},
		getFieldValue: function(fieldName)
		{
			var ctrl = this.getFieldControl(fieldName);
			return ctrl !== null ? ctrl.value : "";
		},
		setFieldValue: function(fieldName, val)
		{
			var ctrl = this.getFieldControl(fieldName);
			if(ctrl !== null)
			{
				ctrl.value = val;
			}
		},
		setup: function(fields)
		{
			if(!BX.type.isPlainObject(fields))
			{
				return;
			}

			for(var k in fields)
			{
				if(fields.hasOwnProperty(k))
				{
					this.setFieldValue(k, fields[k]);
				}
			}
		},
		bind: function()
		{
			if(this._deleteButton)
			{
				BX.bind(this._deleteButton, "click", this._deleteButtonHandler);
			}
		},
		unbind: function()
		{
			if(this._deleteButton)
			{
				BX.unbind(this._deleteButton, "click", this._deleteButtonHandler);
			}
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			var table, row, cell;
			table = BX.create("TABLE", { attrs: { className: "crm-offer-info-table" } });
			this._container.appendChild(table);

			row = table.insertRow(-1);
			cell = row.insertCell(-1);
			cell.colSpan  = "4";

			this._deleteButton = BX.create("SPAN", { attrs: { className: "crm-offer-title-del" } });

			cell.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-offer-title" },
						children:
						[
							BX.create("SPAN",
								{
									attrs: { className: "crm-offer-title-text" },
									text: this._editor.getTypeName(this._typeId)
								}
							),
							BX.create("SPAN",
								{
									attrs: { className: "crm-offer-title-set-wrap" },
									children: [ this._deleteButton ]
								}
							)
						]
					}
				)
			);

			var scheme = this._editor.getScheme();
			for(var i = 0; i < scheme.length; i++)
			{
				var info = scheme[i];
				var type = BX.type.isNotEmptyString(info["type"]) ? info["type"] : "";
				var name = BX.type.isNotEmptyString(info["name"]) ? info["name"] : "";
				var qualifiedName = this._editor.prepareQualifiedName(name, { typeId: this._typeId });
				var val = this._fields.hasOwnProperty(name) ? this._fields[name] : "";

				if(type === "locality")
				{
					row = table.insertRow(-1);
					row.style.display = "none";
					cell = row.insertCell(-1);
					cell.colSpan  = "4";
					cell.appendChild(
						BX.create("INPUT",
							{
								props:
								{
									type: "hidden",
									name: qualifiedName,
									value: val
								}
							}
						)
					);

					var params = BX.type.isPlainObject(info["params"]) ? info["params"] : {};
					var relatedName = BX.type.isNotEmptyString(info["related"]) ? info["related"] : "";
					BX.CrmLocalitySearchField.create(
						qualifiedName,
						{
							localityType: BX.type.isNotEmptyString(params["locality"]) ? params["locality"] : "",
							serviceUrl: this._editor.getServiceUrl(),
							searchInput: this._editor.prepareQualifiedName(relatedName, { typeId: this._typeId }),
							dataInput: qualifiedName
						}
					);
				}
				else
				{
					row = table.insertRow(-1);
					cell = row.insertCell(-1);
					cell.className = "crm-offer-info-left";
					cell.appendChild(BX.create("SPAN", { attrs: { className: "crm-offer-info-label-alignment" } }));
					cell.appendChild(
						BX.create("SPAN",
							{
								attrs: { className: "crm-offer-info-label" },
								text: this._editor.getFieldLabel(name)
							}
						)
					);

					cell = row.insertCell(-1);
					cell.className = "crm-offer-info-right";

					if(type === "multilinetext")
					{
						cell.appendChild(
							BX.create("DIV",
								{
									attrs: { className: "crm-offer-info-data-wrap" },
									children:
										[
											BX.create("TEXTAREA",
												{
													props:
														{
															className: "crm-offer-textarea",
															name: qualifiedName,
															value: val
														}
												}
											)
										]
								}
							)
						);
					}
					else
					{
						cell.appendChild(
							BX.create("DIV",
								{
									attrs: {className: "crm-offer-info-data-wrap"},
									children:
										[
											BX.create("INPUT",
												{
													props:
														{
															className: "crm-offer-item-inp",
															type: "text",
															name: qualifiedName,
															value: val
														}
												}
											)
										]
								}
							)
						);
					}

					cell = row.insertCell(-1);
					cell.className = "crm-offer-info-right-btn";

					cell = row.insertCell(-1);
					cell.className = "crm-offer-last-td";

				}
			}

			this.bind();
			this._hasLayout = true;
		},
		cleanLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			this.unbind();
			BX.cleanNode(this._container);

			this._hasLayout = false;
		},
		isPersistent: function()
		{
			return this._isPersistent;
		},
		markAsDeleted: function()
		{
			if(this._isMarkedAsDeleted)
			{
				return;
			}

			this._isMarkedAsDeleted = true;

			this._container.appendChild(
				BX.create("INPUT",
					{
						props:
						{
							"name": this._editor.prepareQualifiedName("DELETED", { typeId: this._typeId }),
							"type": "hidden",
							"value": "Y"
						}
					}
				)
			);
			this._container.style.display = "none";

			BX.onCustomEvent(this._editor, "CrmMultipleAddressItemMarkAsDeleted", [this._editor, this]);
		},
		isMarkedAsDeleted: function()
		{
			return this._isMarkedAsDeleted;
		},
		setupByEntity: function(entityTypeId, entityId)
		{
			this._editor.loadEntityAddress(
				this.getTypeId(),
				entityTypeId,
				entityId,
				BX.delegate(this.onEntityAddressLoad, this)
			);
		},
		onEntityAddressLoad: function(fields)
		{
			if(!BX.type.isPlainObject(fields))
			{
				return;
			}

			var isEmpty = true;
			var scheme = this._editor.getScheme();
			for(var i = 0, l = scheme.length; i < l; i++)
			{
				var info = scheme[i];
				var name = BX.type.isNotEmptyString(info["name"]) ? info["name"] : "";
				if(BX.type.isNotEmptyString(fields[name]))
				{
					isEmpty = false;
					break;
				}
			}

			if(!isEmpty && window.confirm(this.getMessage("copyConfirmation")))
			{
				this.setup(fields);
			}
		},
		onDeleteButtonClick: function(e)
		{
			if(window.confirm(this.getMessage("deletionConfirmation")))
			{
				this.markAsDeleted();
				this._editor.removeItem(this);
			}
		}
	};

	if(typeof(BX.CrmMultipleAddressItemEditor.messages) === "undefined")
	{
		BX.CrmMultipleAddressItemEditor.messages =
		{
		};
	}

	BX.CrmMultipleAddressItemEditor.create = function(id, settings)
	{
		var self = new BX.CrmMultipleAddressItemEditor();
		self.initialize(id, settings);
		return self;
	};
}

BX.namespace("BX.Crm");

BX.Crm.EntityEditorBlockAreaClass = (function ()
{
	var EntityEditorBlockAreaClass = function (parameters)
	{
		this.editor = parameters.editor;
		this.container = parameters.container;
		this.nextNode = parameters.nextNode;
		this.entityInfoList = parameters.entityInfoList;
		this.readOnlyMode = parameters.readOnlyMode;
		this.closeBlockHandler = parameters.closeBlockHandler;
		this.changeSelectedRequisiteHandler = parameters.changeSelectedRequisiteHandler;
		this.changeLinkedRequisiteHandler = parameters.changeLinkedRequisiteHandler;
		this.rqLinkedId = parameters.rqLinkedId;
		this.bdLinkedId = parameters.bdLinkedId;

		this.wrapper = BX.type.isElementNode(parameters.wrapper) ? parameters.wrapper : null;
		this.visualization = null;

		this.blockList = [];
		this.blockPseudoIdIndex = {};

		this.cleanState = {
			started: false,
			cleanAll: false,
			afterClean: null
		};

		this.initialize();
	};

	EntityEditorBlockAreaClass.prototype = {
		initialize: function()
		{
			if (this.container && this.wrapper)
			{
				this.clean(false, BX.delegate(this.continueInitialize, this));
			}
			else
			{
				this.continueInitialize();
			}
		},
		continueInitialize: function()
		{
			if (this.container)
			{
				if (!this.wrapper)
				{
					this.wrapper = BX.create("DIV", {"attrs": {"class": "crm-offer-tabs-wrapper"}});
				}
				else
				{
					BX.addClass(this.wrapper, "crm-offer-tabs-wrapper");
				}

				if(!this.wrapper.parentNode)
				{
					if (this.nextNode)
						this.container.insertBefore(this.wrapper, this.nextNode);
					else
						this.container.appendChild(this.wrapper);
				}

				this.visualization = new BX.Crm.EntityEditorBlockAreaVisualizationClass(
					{
						blockArea: this,
						tabActiveClass: "crm-offer-tab-active"
					}
				);
			}

			var n = this.entityInfoList.length;
			if (this.entityInfoList instanceof Array && n > 0)
			{
				for (var i = 0; i < n; i++)
				{
					if (this.entityInfoList[i] instanceof BX.CrmEntityInfo)
						this.addBlock(this.entityInfoList[i]);
				}
			}
		},
		getWrapperNode: function()
		{
			return this.wrapper;
		},
		getBlockByPseudoId: function(pseudoId)
		{
			var result = null;

			if (this.blockPseudoIdIndex.hasOwnProperty(pseudoId))
			{
				result = this.blockPseudoIdIndex[pseudoId];
			}

			return result;
		},
		addBlock: function(entityInfo)
		{
			var blockIndex = this.blockList.length;
			var block = new BX.Crm.EntityEditorBlockClass({
				editor: this.editor,
				blockArea: this,
				blockIndex: blockIndex,
				container: this.wrapper,
				nextNode: null,
				entityInfo: entityInfo,
				readOnlyMode: this.readOnlyMode,
				closeBlockHandler: this.closeBlockHandler,
				changeSelectedRequisiteHandler: this.changeSelectedRequisiteHandler,
				changeLinkedRequisiteHandler: BX.delegate(this.changeLinkedRequisite, this),
				rqLinkedId: this.rqLinkedId,
				bdLinkedId: this.bdLinkedId
			});

			if (block)
			{
				this.blockList[blockIndex] = block;
				this.blockPseudoIdIndex[block.getPseudoId()] = blockIndex;

				if (this.visualization)
					this.visualization.addTabBlock(block);
			}
		},
		clean: function(cleanAll, afterClean)
		{
			if (!this.cleanState.started)
			{
				this.cleanState.started = true;
				this.cleanState.cleanAll = !!cleanAll;
				this.cleanState.afterClean = (typeof(afterClean) === "function") ? afterClean : null;

				if (this.blockList.length > 0)
					this.blockList[0].destroy();
				else
					this.continueClean();
			}
		},
		continueClean: function()
		{
			if (this.cleanState.started)
			{
				if (this.blockList.length > 0)
				{
					this.blockList[0].destroy();
				}
				else
				{
					if (this.wrapper)
					{
						if (this.visualization)
							this.visualization = null;
						BX.cleanNode(this.wrapper, this.cleanState.cleanAll);
					}

					var afterClean = this.cleanState.afterClean;

					this.cleanState = {
						cleanAll: false,
						started: false,
						afterClean: null
					};

					if (typeof(afterClean) === "function")
						afterClean();
				}
			}
		},
		onBlockDestroy: function(blockIndex)
		{
			if (blockIndex >= 0 && this.blockList && this.blockList.length > blockIndex)
			{
				if (this.visualization)
					this.visualization.removeTabsBlock(
						this.blockList[blockIndex],
						BX.delegate(this.continueBlockDestroy, this)
					);
				else
					this.continueBlockDestroy(blockIndex);
			}
		},
		changeLinkedRequisite: function(requisiteId, bankDetailId)
		{
			this.rqLinkedId = parseInt(requisiteId);
			if (this.rqLinkedId < 0 || isNaN(this.rqLinkedId))
				this.rqLinkedId = 0;
			this.bdLinkedId = parseInt(bankDetailId);
			if (this.bdLinkedId < 0 || isNaN(this.bdLinkedId))
				this.bdLinkedId = 0;

			if (typeof(this.changeLinkedRequisiteHandler) === "function")
				this.changeLinkedRequisiteHandler(this.rqLinkedId, this.bdLinkedId);
		},
		continueBlockDestroy: function(blockIndex)
		{
			if (blockIndex >= 0 && this.blockList && this.blockList.length > blockIndex)
			{
				var block = this.blockList[blockIndex];

				this.blockList.splice(blockIndex, 1);
				delete this.blockPseudoIdIndex[block.getPseudoId()];
				this.reindexBlocks(blockIndex);

				block.continueDestroy();

				if (this.cleanState.started)
					this.continueClean();
			}
		},
		reindexBlocks: function(indexFrom)
		{
			for (var i = indexFrom; i < this.blockList.length; i++)
			{
				this.blockList[i].setIndex(i);
				this.blockPseudoIdIndex[blockList[i].getPseudoId()] = i;
			}
		},
		destroy: function(afterDestroy)
		{
			this.clean(true, afterDestroy);
		}
	};

	return EntityEditorBlockAreaClass;
})();

BX.Crm.EntityEditorBlockAreaVisualizationClass = (function()
{
	var EntityEditorBlockAreaVisualizationClass = function (parameters)
	{
		this.block = null;
		this.wrapper = null;
		this.activeClass = '';
		this.tabsObjList = [];

		this.init(parameters);
	};

	EntityEditorBlockAreaVisualizationClass.prototype = {
		init : function(params)
		{
			this.blockArea = params.blockArea;
			if (this.blockArea)
				this.wrapper = this.blockArea.getWrapperNode();
			this.activeClass = params.tabActiveClass;
		},
		createTabObj : function(tabNode)
		{
			var btnList,
				contList,
				requisiteItems,
				_this = this;

			var tabsObj =
			{
				mainblock : tabNode,
				innerBlock : tabNode.querySelector('[data-tab-block=tabBlockInner]'),
				contWrap : tabNode.querySelector('[data-tab-block=tabBlockWrap]'),
				/*closeBtn : tabNode.querySelector('[data-tab-block=closeBtn]'),*/
				btnList : [],
				contList : [],
				requisiteItems : []
			};

			btnList = tabNode.querySelectorAll('[data-tab-block=tab-btn]');

			/*BX.bind(tabsObj.closeBtn, 'click', function()
			{
				_this.removeTabsBlock(tabNode);
			});*/


			contList = tabNode.querySelectorAll('[data-tab-block=tab-cont]');

			for(var c=0; c < contList.length; c++)
			{
				tabsObj.contList[c] = contList[c];

				requisiteItems = contList[c].querySelectorAll('[data-tab-block=requisiteItem]');

				if(requisiteItems.length > 0)
					this.bindSwitchRequisiteItems(requisiteItems);
			}

			for(var b=0; b< btnList.length; b++)
			{
				tabsObj.btnList[b] = btnList[b];

				(function(blockNum,tabNum)
				{
					BX.bind(btnList[b], 'click',  function()
					{
						_this.switchTab(blockNum, tabNum);
					});
				})(tabsObj,b);
			}

			this.tabsObjList.push(tabsObj)
		},
		switchTab : function(tabsObj, tabNum)
		{
			tabsObj.contWrap.style.height = tabsObj.contWrap.clientHeight + 'px';

			for(var i=0; i<tabsObj.btnList.length; i++)
			{
				BX.removeClass(tabsObj.btnList[i], this.activeClass);
				tabsObj.contList[i].style.display = 'none';
			}

			tabsObj.contList[tabNum].style.display = 'block';
			BX.addClass(tabsObj.btnList[tabNum], this.activeClass);

			tabsObj.contWrap.style.height = tabsObj.contList[tabNum].offsetHeight + 'px';

		},
		bindSwitchRequisiteItems : function(itemList)
		{
			var inputs, index, nodes, i, j,
				_this = this;

			for(i=0; i<itemList.length; i++)
			{
				index = 0;
				inputs = [];
				inputs[index++] = itemList[i].querySelector('[class=crm-offer-requisite-inp]');
				nodes = itemList[i].querySelectorAll('[class=crm-offer-bankdetail-inp]');
				if (nodes)
				{
					for (j = 0; j < nodes.length; j++)
						inputs[index++] = nodes[j];
				}
				nodes = null;

				(function(item, itemList)
				{
					for (var i = 0; i < inputs.length; i++)
					{
						BX.bind(inputs[i], 'click', function(){
							_this.switchRequisiteItems(item, itemList)
						});
					}
				})(itemList[i], itemList)
			}
		},
		switchRequisiteItems : function(item, itemList)
		{
			var inp;

			for(var i=0; i<itemList.length; i++)
			{
				BX.removeClass(itemList[i], 'crm-offer-requisite-active');
			}

			BX.addClass(item, 'crm-offer-requisite-active');
			if (inp = item.querySelector('[class=crm-offer-requisite-inp]'))
			{
				inp.checked = true;
			}
		},
		removeTabsBlock : function(tabsBlock, continueCallback)
		{
			var blockWrapper = tabsBlock.getWrapperNode();
			if (blockWrapper)
			{
				blockWrapper.style.height = blockWrapper.offsetHeight + 'px';
				var thisBlockCord = blockWrapper.getBoundingClientRect().top,
					nextBlockCord = thisBlockCord,
					itemIndex;

				for(var i=0; i<this.tabsObjList.length; i++)
				{
					if(this.tabsObjList[i].mainblock == blockWrapper)
					{
						if(i<this.tabsObjList.length-1)
							nextBlockCord = this.tabsObjList[i+1].mainblock.getBoundingClientRect().top;

						itemIndex = this.tabsObjList.indexOf(this.tabsObjList[i]);

						this.tabsObjList.splice(itemIndex,1);
					}
				}

				setTimeout(function()
				{
					if(thisBlockCord == nextBlockCord)
						blockWrapper.style.width = 0;

					blockWrapper.style.height = 0;
					blockWrapper.style.opacity = 0;

				},100);

				setTimeout(function()
				{
					blockWrapper.style.display = 'none';
					continueCallback(tabsBlock.getIndex());
				},700)
			}
		},
		addTabBlock : function (tabBlock)
		{
			var blockWrapper = tabBlock.getWrapperNode();
			this.createTabObj(blockWrapper);
			this.showTabObj();
		},
		completeTabObjAnimation: function()
		{
			var mainBlock = this.tabsObjList[this.tabsObjList.length - 1].mainblock;
			BX.removeClass(mainBlock, 'crm-offer-tab-block-hidden');
			mainBlock.style.height = 'auto';
		},
		showTabObj : function()
		{
			var lastIndex = this.tabsObjList.length - 1;
			var mainBlock = this.tabsObjList[lastIndex].mainblock;
			var innerBlock = this.tabsObjList[lastIndex].innerBlock;

			if(BX.pos(innerBlock)['height'] > 0)
			{
				var marginBottom = parseInt(BX.style(innerBlock, 'marginBottom'));
				mainBlock.style.height = innerBlock.offsetHeight + marginBottom +'px';
				BX.bind(mainBlock, 'transitionend', BX.delegate(this.completeTabObjAnimation, this));
			}
			else
			{
				//Skip animation if inner block is hidden
				this.completeTabObjAnimation();
			}
		}
	};

	return EntityEditorBlockAreaVisualizationClass;
})();

BX.Crm.EntityEditorBlockClass = (function ()
{
	var EntityEditorBlockClass = function (parameters)
	{
		this.editor = parameters.editor;
		this.blockArea = parameters.blockArea;
		this.blockIndex = parameters.blockIndex;
		this.container = parameters.container;
		this.nextNode = parameters.nextNode;
		this.entityInfo = parameters.entityInfo;
		this.readOnlyMode = parameters.readOnlyMode;
		this.closeBlockHandler = parameters.closeBlockHandler;
		this.changeSelectedRequisiteHandler = parameters.changeSelectedRequisiteHandler;
		this.changeLinkedRequisiteHandler = parameters.changeLinkedRequisiteHandler;

		this.rqLinkedId = parseInt(parameters.rqLinkedId);
		if (this.rqLinkedId < 0 || isNaN(this.rqLinkedId))
			this.rqLinkedId = 0;

		this.bdLinkedId = parseInt(parameters.bdLinkedId);
		if (this.bdLinkedId < 0 || isNaN(this.bdLinkedId))
			this.bdLinkedId = 0;

		this.ajaxUrl = "/bitrix/components/bitrix/crm.requisite.edit/settings.php";

		this.closeButtonClickHandler = null;
		this.wrapper = null;
		this.wrapperInner = null;
		this.titleNode = null;
		this.closeButtonNode = null;
		this.requisiteIndex = [];

		this.random = Math.random().toString().substring(2);

		this.initialize();
	};

	EntityEditorBlockClass.prototype = {
		initialize: function()
		{
			if (this.container)
			{
				this.clean();

				if (!this.wrapper)
				{
					var typeClass = "";
					if (this.entityInfo instanceof BX.CrmEntityInfo)
					{
						var type = this.entityInfo.getSetting("type", "");
						if (typeof(type) === "string" && type.length > 0)
							typeClass = " crm-offer-tab-" + type;
					}
					this.wrapper = BX.create("DIV", {
						"attrs": {
							"class": "crm-offer-tab-block-wrap" + typeClass + " crm-offer-tab-block-hidden",
							"data-tab-block": "tabBlock"
						}
					});
					if (this.wrapper)
					{
						if (this.nextNode)
							this.container.insertBefore(this.wrapper, this.nextNode);
						else
							this.container.appendChild(this.wrapper);
					}
				}
			}

			if (this.wrapper)
			{
				this.wrapperInner = BX.create(
					"DIV", {"attrs": {"class": "crm-offer-tab-block", "data-tab-block": "tabBlockInner"}}
				);
				if (this.wrapperInner)
				{
					this.wrapper.appendChild(this.wrapperInner);

					// title
					var title, url, description,
						titleLinkNode = null;
					if (this.entityInfo instanceof BX.CrmEntityInfo)
					{
						title = this.entityInfo.getSetting("title", "");
						if (title.length > 0)
						{
							url = this.entityInfo.getSetting("url", "");
							if (BX.type.isNotEmptyString(url))
							{
								titleLinkNode = BX.create(
									"A",
									{
										"attrs": {
											"class": "crm-offer-tab-title-name",
											"href": url,
											"target": "_blank"
										},
										"html": BX.util.htmlspecialchars(title)
									}
								);
							}
							else
							{
								titleLinkNode = BX.create(
									"SPAN",
									{
										"attrs": {"class": "crm-offer-tab-title-name"},
										"html": BX.util.htmlspecialchars(title)
									}
								);
							}
							description = this.entityInfo.getSetting("desc", "");
						}
						if (titleLinkNode)
						{
							this.titleNode = BX.create(
								"DIV",
								{
									"attrs": {"class": "crm-offer-tab-title"},
									"children": [
										BX.create("DIV", {"attrs": {"class": "crm-offer-tab-title-icon"}}),
										BX.create(
											"DIV",
											{
												"attrs": {"class": "crm-offer-tab-title-name-block"},
												"children": [
													titleLinkNode,
													BX.create(
														"DIV",
														{
															"attrs": {"class": "crm-offer-tab-title-descript"},
															"html": BX.util.htmlspecialchars(description)
														}
													)
												]
											}
										)
									]
								}
							);
						}
					}

					if (this.titleNode)
					{
						if (!this.readOnlyMode)
						{
							this.closeButtonNode = BX.create(
								"DIV",
								{
									"attrs": {
										"class": "crm-offer-tab-close-btn",
										"data-tab-block": "closeBtn"
									}
								}
							);
							if (this.closeButtonNode)
							{
								this.titleNode.appendChild(this.closeButtonNode);
								this.closeButtonClickHandler = BX.delegate(this.onCloseButtonClick, this);
								BX.bind(this.closeButtonNode, "click", this.closeButtonClickHandler);
							}
						}
						this.wrapperInner.appendChild(this.titleNode);
					}

					if (this.titleNode)
					{
						var i, j, advancedInfo, entityId, entityTypeName, contactTypeName = "",
							phoneItems = [], emailItems = [], requisiteItems = [];

						advancedInfo = this.entityInfo.getSetting("advancedInfo", null);
						entityId = this.entityInfo.getSetting("id", "");
						entityTypeName = this.entityInfo.getSetting("type", "");
						if (BX.type.isPlainObject(advancedInfo))
						{
							if (entityTypeName === "contact")
							{
								if (BX.type.isPlainObject(advancedInfo["contactType"])
									&& typeof(advancedInfo["contactType"]["name"]) === "string")
								{
									contactTypeName = advancedInfo["contactType"]["name"];
								}
							}
							if (advancedInfo["multiFields"] && advancedInfo["multiFields"] instanceof Array)
							{
								var mf = advancedInfo["multiFields"];
								for (i = 0; i < mf.length; i++)
								{
									if (mf[i]["TYPE_ID"] && mf[i]["TYPE_ID"] === "PHONE")
									{
										phoneItems.push({"VALUE": BX.util.trim(mf[i]["VALUE"])});
									}
									if (mf[i]["TYPE_ID"] && mf[i]["TYPE_ID"] === "EMAIL")
									{
										emailItems.push({"VALUE": BX.util.trim(mf[i]["VALUE"])});
									}
								}
							}
							if (advancedInfo["requisiteData"] instanceof Array)
							{
								var requisiteData, requisiteId, newRequisiteIdLinked, entityTypeId,
									data, viewData, bankDetailViewDataList,
									selected, bankDetailLinkedIndex, newBankDetailIdLinked,
									bankDetailIdSelected, bankDetailSelectedIndex,
									bankDetailSelectedIndexPrior, bankDetailId,
									index = 0, selectedIndex = -1, linkedIndex = -1;

								requisiteData = advancedInfo["requisiteData"];
								newRequisiteIdLinked = this.rqLinkedId;
								for (i = 0; i < requisiteData.length; i++)
								{
									if (typeof(requisiteData[i]["requisiteId"]) !== "undefined")
									{
										requisiteId = parseInt(requisiteData[i]["requisiteId"]);
									}
									else
									{
										requisiteId = 0;
									}

									if (typeof(requisiteData[i]["entityTypeId"]) !== "undefined")
									{
										entityTypeId = parseInt(requisiteData[i]["entityTypeId"]);
									}
									else
									{
										entityTypeId = 0;
									}

									if (typeof(requisiteData[i]["entityId"]) !== "undefined")
									{
										entityId = parseInt(requisiteData[i]["entityId"]);
									}
									else
									{
										entityId = 0;
									}

									if (typeof(requisiteData[i]["requisiteData"]) === "string")
									{
										data = BX.parseJSON(requisiteData[i]["requisiteData"], this);
										if (!BX.type.isPlainObject(data))
											data = {};
									}
									else
									{
										data = {};
									}

									if (BX.type.isPlainObject(data["viewData"]))
										viewData = data["viewData"];
									else
										viewData = {};

									if (typeof(viewData["title"]) === "string" && viewData["title"].length > 0)
									{
										if (BX.type.isArray(data["bankDetailViewDataList"]))
											bankDetailViewDataList = data["bankDetailViewDataList"];
										else
											bankDetailViewDataList = [];

										if (typeof(requisiteData[i]["bankDetailIdSelected"]) !== "undefined")
										{
											bankDetailIdSelected = parseInt(requisiteData[i]["bankDetailIdSelected"]);
											if (bankDetailIdSelected < 0 || isNaN(bankDetailIdSelected))
												bankDetailIdSelected = 0;
										}
										else
										{
											requisiteData[i]["bankDetailIdSelected"] = bankDetailIdSelected = 0;
										}

										if (bankDetailViewDataList.length > 0)
										{
											bankDetailLinkedIndex = -1;
											bankDetailSelectedIndex = -1;
											bankDetailSelectedIndexPrior = -1;
											for (j = 0; j < bankDetailViewDataList.length; j++)
											{
												bankDetailId = parseInt(bankDetailViewDataList[j]["pseudoId"]);
												if (bankDetailId < 0 || isNaN(bankDetailId))
													bankDetailId = 0;
												if (requisiteId === this.rqLinkedId &&
													this.bdLinkedId > 0 && bankDetailId === this.bdLinkedId)
												{
													bankDetailLinkedIndex = j;
												}
												if (bankDetailIdSelected > 0 && bankDetailId === bankDetailIdSelected)
													bankDetailSelectedIndexPrior = j;

												if (bankDetailViewDataList[j]["selected"])
													bankDetailSelectedIndex = j;
											}
											if (bankDetailLinkedIndex >= 0)
											{
												if (bankDetailSelectedIndexPrior !== bankDetailSelectedIndex)
												{
													if (bankDetailSelectedIndex >= 0)
														bankDetailViewDataList[bankDetailSelectedIndex]["selected"] = false;
													bankDetailViewDataList[bankDetailLinkedIndex]["selected"] = true;
													requisiteData[i]["bankDetailIdSelected"] = parseInt(
														bankDetailViewDataList[bankDetailLinkedIndex]["pseudoId"]
													);
												}
											}
											else if (bankDetailSelectedIndexPrior >= 0)
											{
												if (bankDetailSelectedIndexPrior !== bankDetailSelectedIndex)
												{
													if (bankDetailSelectedIndex >= 0)
														bankDetailViewDataList[bankDetailSelectedIndex]["selected"] = false;
													bankDetailViewDataList[bankDetailSelectedIndexPrior]["selected"] = true;
													requisiteData[i]["bankDetailIdSelected"] = parseInt(
														bankDetailViewDataList[bankDetailSelectedIndexPrior]["pseudoId"]
													);
												}
											}
											else
											{
												if (bankDetailSelectedIndex < 0)
													bankDetailSelectedIndex = 0;
												requisiteData[i]["bankDetailIdSelected"] = parseInt(
													bankDetailViewDataList[bankDetailSelectedIndex]["pseudoId"]
												);
											}
										}

										requisiteItems.push({
											"requisiteId": requisiteId,
											"entityTypeId": entityTypeId,
											"entityId": entityId,
											"viewData": viewData,
											"bankDetailViewDataList": bankDetailViewDataList,
											"bankDetailIdSelected": requisiteData[i]["bankDetailIdSelected"],
											"selected": requisiteData[i]["selected"]
										});

										this.requisiteIndex[requisiteId] = {
											"entityTypeId": entityTypeId,
											"entityId": entityId,
											"bankDetailIdSelected": requisiteData[i]["bankDetailIdSelected"]
										};

										if (requisiteData[i]["selected"])
											selectedIndex = index;
										if (this.rqLinkedId > 0 && this.rqLinkedId == requisiteId)
											linkedIndex = index;

										index++;
									}
								}

								if (linkedIndex >= 0)
								{
									if (selectedIndex >= 0 && selectedIndex !== linkedIndex)
									{
										requisiteItems[selectedIndex]["selected"] = false;
										requisiteItems[linkedIndex]["selected"] = true;
										selectedIndex = linkedIndex;
									}
								}

								newRequisiteIdLinked = 0;
								newBankDetailIdLinked = 0;
								if (selectedIndex >= 0)
								{
									newRequisiteIdLinked = parseInt(requisiteItems[selectedIndex]["requisiteId"]);
									if (newRequisiteIdLinked < 0 || isNaN(newRequisiteIdLinked))
										newRequisiteIdLinked = 0;

									newBankDetailIdLinked = parseInt(requisiteItems[selectedIndex]["bankDetailIdSelected"]);
									if (newBankDetailIdLinked < 0 || isNaN(newBankDetailIdLinked))
										newBankDetailIdLinked = 0;
								}

								if (this.rqLinkedId !== newRequisiteIdLinked || this.bdLinkedId !== newBankDetailIdLinked)
								{
									if (typeof(this.changeLinkedRequisiteHandler) === "function")
									{
										this.rqLinkedId = newRequisiteIdLinked;
										this.bdLinkedId = newBankDetailIdLinked;
										this.changeLinkedRequisiteHandler(this.rqLinkedId, this.bdLinkedId);
									}
								}
							}
						}

						var infoDataExists = false, requisiteDataExists = false, contentDataExists = false;
						if ((entityTypeName === "contact" && contactTypeName.length > 0)
							|| phoneItems.length > 0 || emailItems.length > 0)
						{
							infoDataExists = true;
						}
						if (requisiteItems.length > 0)
						{
							requisiteDataExists = true;
						}
						contentDataExists = infoDataExists || requisiteDataExists;

						if (contentDataExists)
						{
							var tabInfo = 1, tabRequisite = 2, activeTab = (infoDataExists) ? tabInfo : tabRequisite;

							var tabInfoTitle = this.getMessage(entityTypeName + "TabTitleAbout"),
								tabRequisiteTitle;

							if (entityTypeName === "company")
							{
								tabRequisiteTitle = this.getMessage("tabTitleCompanyRequisites");
							}
							else    // contact
							{
								tabRequisiteTitle = this.getMessage("tabTitleContactRequisites");
							}

							if (!BX.type.isNotEmptyString(tabInfoTitle))
								tabInfoTitle = 	this.getMessage(entityTypeName + "tabTitleAbout");

							var tabInfoAttrs = {
								"class": "crm-offer-tab" + ((tabInfo === activeTab) ? " crm-offer-tab-active" : ""),
								"data-tab-block": "tab-btn"
							};
							var tabRequisiteAttrs = {
								"class": "crm-offer-tab" + ((tabRequisite === activeTab) ? " crm-offer-tab-active" : ""),
								"data-tab-block": "tab-btn"
							};
							if (!infoDataExists)
								tabInfoAttrs["style"] = "display: none;";
							if (!requisiteDataExists)
								tabRequisiteAttrs["style"] = "display: none;";

							var mainContBlock = null;
							this.wrapperInner.appendChild(
								mainContBlock = BX.create(
									"DIV", {
										"attrs": {"class": "crm-offer-tab-main-cont-wrap"},
										"children": [
											BX.create(
												"DIV", {
													"attrs": {"class": "crm-offer-tab-list-wrap"},
													"children": [
														BX.create(
															"SPAN", {
																"attrs": tabInfoAttrs,
																"html": "<span class=\"crm-offer-tab-text\">" +
																BX.util.htmlspecialchars(tabInfoTitle) + "</span>"
															}
														),
														BX.create(
															"SPAN", {
																"attrs": tabRequisiteAttrs,
																"html": "<span class=\"crm-offer-tab-text\">" +
																BX.util.htmlspecialchars(tabRequisiteTitle) + "</span>"
															}
														)
													]
												}
											)
										]
									}
								)
							);

							if (mainContBlock)
							{
								var infoContBlockAttrs = {
										"class": "crm-offer-tab-cont",
										"data-tab-block": "tab-cont"
									},
									requisiteContBlockAttrs = {
										"class": "crm-offer-tab-cont",
										"data-tab-block": "tab-cont"
									};

								if (tabInfo !== activeTab)
									infoContBlockAttrs["style"] = "display: none;";
								if (tabRequisite !== activeTab)
									requisiteContBlockAttrs["style"] = "display: none;";

								var infoWrapper = null, requisiteWrapper = null;

								mainContBlock.appendChild(
									BX.create(
										"DIV", {
											"attrs": {
												"class": "crm-offer-tab-cont-wrap",
												"data-tab-block": "tabBlockWrap"
											},
											"children": [
												BX.create(
													"DIV", {
														"attrs": infoContBlockAttrs,
														"children": [
															infoWrapper = BX.create(
																"DIV", {
																	"attrs": {"class": "crm-offer-tab-info"},
																	"children": [
																		BX.create(
																			"DIV", {
																				"attrs": {"class": "crm-offer-tab-info-img"}
																			}
																		)
																	]
																}
															)
														]
													}
												),
												BX.create(
													"DIV", {
														"attrs": requisiteContBlockAttrs,
														"children": [
															requisiteWrapper = BX.create("FORM")
														]
													}
												)
											]
										}
									)
								);

								var tableNode, row, cell, bankDetailsNode;

								if (infoWrapper && infoDataExists)
								{
									tableNode = BX.create("TABLE", {"attrs": {"class": "crm-offer-tab-table"}});
									if (tableNode)
									{
										if (entityTypeName === "contact" && contactTypeName.length > 0)
										{
											var contactTypeTitle = this.getMessage("prefContactType");
											row = tableNode.insertRow(-1);
											cell = row.insertCell(-1);
											cell.className = "crm-offer-tab-cell";
											cell.innerHTML = BX.util.htmlspecialchars(contactTypeTitle + ":");
											cell = row.insertCell(-1);
											cell.className = "crm-offer-tab-cell";
											cell.innerHTML = BX.util.htmlspecialchars(contactTypeName);
										}

										if (phoneItems.length > 0)
										{
											var phoneTitle, phoneHtml;

											phoneTitle = this.getMessage("prefPhoneLong");

											var phoneContent = "", phoneNumber = "", onclickContent = "";
											for (i = 0; i < phoneItems.length; i++)
											{
												if (BX.type.isNotEmptyString(phoneItems[i]["VALUE"]))
												{
													phoneNumber = phoneItems[i]["VALUE"];

													phoneContent += "<div class=\"crm-client-contacts-block\">";
													phoneContent += "<span style=\"max-width: 330px;\" class=\"crm-client-contacts-block-text crm-client-contacts-block-handset\">";
													phoneContent += "<a class=\"crm-client-contacts-block-text-tel\" href=\"callto://" + BX.util.urlencode(phoneNumber) + "\">" + BX.util.htmlspecialchars(phoneNumber) + "</a>";
													phoneContent += "</span>";
													phoneContent += "</div>";
												}
											}

											row = tableNode.insertRow(-1);
											cell = row.insertCell(-1);
											cell.className = "crm-offer-tab-cell";
											cell.innerHTML = BX.util.htmlspecialchars(phoneTitle + ":");
											cell = row.insertCell(-1);
											cell.className = "crm-offer-tab-cell";
											cell.innerHTML = phoneContent;
										}

										if (emailItems.length > 0)
										{
											var emailTitle, emailContent = "", emailAddr = "";

											emailTitle = this.getMessage("prefEmail");

											for (i = 0; i < emailItems.length; i++)
											{
												if (BX.type.isNotEmptyString(emailItems[i]["VALUE"]))
												{
													emailAddr = emailItems[i]["VALUE"];

													emailContent += "<div class=\"crm-client-contacts-block\">";
													emailContent += "<a class=\"crm-client-contacts-block-text-tel\" href=\"mailto://" + BX.util.urlencode(emailAddr) + "\">" + BX.util.htmlspecialchars(emailAddr) + "</a>";
													emailContent += "</div>";
												}
											}

											row = tableNode.insertRow(-1);
											cell = row.insertCell(-1);
											cell.className = "crm-offer-tab-cell";
											cell.innerHTML = BX.util.htmlspecialchars(emailTitle + ":");
											cell = row.insertCell(-1);
											cell.className = "crm-offer-tab-cell";
											cell.innerHTML = emailContent;
										}
									}

									infoWrapper.appendChild(tableNode);
								}

								if (requisiteWrapper && requisiteDataExists)
								{
									var requisiteBlockNode,
										requisiteRadioIdPrefix,
										requisiteRadioId;

									if (this.editor)
										requisiteRadioIdPrefix = this.editor.getSetting("containerId", null);
									if (!BX.type.isNotEmptyString(requisiteRadioIdPrefix))
										requisiteRadioIdPrefix = this.random;
									requisiteRadioIdPrefix += "_REQUISITE";

									for (i = 0; i < requisiteItems.length; i++)
									{
										if (BX.type.isPlainObject(requisiteItems[i]["viewData"])
											&& typeof(requisiteItems[i]["requisiteId"]) !== "undefined"
											&& typeof(requisiteItems[i]["selected"]) !== "undefined"
											&& BX.type.isNotEmptyString(requisiteItems[i]["viewData"]["title"])
											&& requisiteItems[i]["viewData"]["fields"] instanceof Array)
										{
											requisiteRadioId = requisiteRadioIdPrefix + "_" + i;
											requisiteBlockNode = BX.create("DIV", {
												"attrs": {
													"class": "crm-offer-requisite" +
													((requisiteItems[i]["selected"] === true) ?
														" crm-offer-requisite-active" : ""),
													"data-tab-block": "requisiteItem"
												},
												"children": [
													BX.create("DIV", {
														"attrs": {"class": "crm-offer-requisite-title"},
														"children": [
															BX.create("INPUT", {
																"attrs": {
																	"id": requisiteRadioId,
																	"class": "crm-offer-requisite-inp",
																	"type": "radio",
																	"name": requisiteRadioIdPrefix,
																	"value": requisiteItems[i]["requisiteId"]
																},
																"props": {
																	"checked": (requisiteItems[i]["selected"] === true)
																},
																"events": {
																	"click": BX.delegate(this.onRequisiteRadioClick, this)
																}
															}),
															BX.create("LABEL", {
																"attrs": {
																	"class": "crm-offer-requisite-lable",
																	"for": requisiteRadioId
																},
																"html": BX.util.htmlspecialchars(
																	requisiteItems[i]["viewData"]["title"]
																)
															})
														]
													})
												]
											});
											if (requisiteBlockNode)
											{
												var requisiteFields = requisiteItems[i]["viewData"]["fields"];

												if (requisiteFields.length > 0 ||
													requisiteItems[i]["bankDetailViewDataList"].length > 0)
												{
													tableNode = BX.create("TABLE", {
														"attrs": {"class": "crm-offer-tab-table"}
													});
													if (tableNode)
													{
														for (j = 0; j < requisiteFields.length; j++)
														{
															row = tableNode.insertRow(-1);
															cell = row.insertCell(-1);
															cell.className = "crm-offer-tab-cell";
															cell.innerHTML =
																((requisiteFields[j]["title"]) ?
																	BX.util.htmlspecialchars(
																		requisiteFields[j]["title"]
																	) : "") + ":";
															cell = row.insertCell(-1);
															cell.className = "crm-offer-tab-cell";
															cell.innerHTML =
																(requisiteFields[j]["textValue"]) ?
																	BX.util.nl2br(
																		BX.util.htmlspecialchars(
																			requisiteFields[j]["textValue"]
																		)
																	) : "";
														}
														bankDetailsNode =
															this.makeBankDetailsNode(requisiteItems[i]);
														if (bankDetailsNode)
														{
															row = tableNode.insertRow(-1);
															cell = row.insertCell(-1);
															cell.className = "crm-offer-tab-cell";
															cell.innerHTML = BX.util.htmlspecialchars(
																this.getMessage("bankDetailsTitle") + ":"
															);
															cell = row.insertCell(-1);
															cell.className = "crm-offer-tab-cell";
															cell.appendChild(bankDetailsNode);
														}
														requisiteBlockNode.appendChild(tableNode);
													}
												}
												requisiteWrapper.appendChild(requisiteBlockNode);
											}
										}
									}
								}
							}
						}
					}
				}
			}
		},
		makeBankDetailsNode: function(requisiteItem)
		{
			var i, cnt, viewDataList, wrapper, requisiteId, pseudoId, radioIdPrefix, radioId;

			if (!BX.type.isPlainObject(requisiteItem))
			{
				return null;
			}

			requisiteId = parseInt(requisiteItem["requisiteId"]);
			if (!(requisiteId > 0
				&& BX.type.isArray(requisiteItem["bankDetailViewDataList"])
				&& requisiteItem["bankDetailViewDataList"].length > 0))
			{
				return null;
			}

			viewDataList = requisiteItem["bankDetailViewDataList"];

			wrapper = BX.create("DIV");
			if (!wrapper)
			{
				return null;
			}

			cnt = 0;
			for (i = 0; i < viewDataList.length; i++)
			{
				if (!(typeof(viewDataList[i]["pseudoId"]) !== "undefined"
					&& BX.type.isPlainObject(viewDataList[i]["viewData"])
					&& BX.type.isNotEmptyString(viewDataList[i]["viewData"]["title"])
					&& BX.type.isArray(viewDataList[i]["viewData"]["fields"])))
				{
					continue;
				}

				pseudoId = viewDataList[i]["pseudoId"];
				if (this.editor)
					radioIdPrefix = this.editor.getSetting("containerId", null);
				if (!BX.type.isNotEmptyString(radioIdPrefix))
					radioIdPrefix = this.random;
				radioIdPrefix += "_RQ_"+requisiteId+"_BD";
				radioId = radioIdPrefix + "_" + pseudoId;

				wrapper.appendChild(
					BX.create("DIV", {
						/*"attrs": {"class": "crm-offer-requisite-title"},*/
						"children": [
							BX.create("INPUT", {
								"attrs": {
									"id": radioId,
									"class": "crm-offer-bankdetail-inp",
									"type": "radio",
									"name": radioIdPrefix,
									"value": "" + requisiteId + "|" + pseudoId
								},
								"props": {
									"checked": viewDataList[i]["selected"] === true
								},
								"events": {
									"click": BX.delegate(this.onBankDetailRadioClick, this)
								}
							}),
							BX.create("LABEL", {
								"attrs": {
									/*"class": "crm-offer-requisite-lable",*/
									"for": radioId
								},
								"html": BX.util.htmlspecialchars(
									viewDataList[i]["viewData"]["title"]
								)
							})
						]
					})
				);
				cnt++;
			}
			if (cnt <= 0)
			{
				BX.cleanNode(wrapper, true);
				wrapper = null;
			}

			return wrapper;
		},
		getMessage: function(name)
		{
			if (this.editor)
				return this.editor.getMessage(name);

			return "";
		},
		getWrapperNode: function()
		{
			return this.wrapper;
		},
		onCloseButtonClick: function()
		{
			if (this.readOnlyMode)
				return;

			if (typeof(this.closeBlockHandler) === "function")
				this.closeBlockHandler();

			this.destroy();
		},
		onRequisiteRadioClick: function(e)
		{
			var requisiteId, entityTypeId, entityId, bankDetailId;

			if(!e)
				e = window.event;

			if (e && BX.type.isDomNode(e.target))
			{
				requisiteId = parseInt(e.target.value);
				if (requisiteId > 0)
				{
					if (this.requisiteIndex[requisiteId])
					{
						entityTypeId = parseInt(this.requisiteIndex[requisiteId].entityTypeId);
						entityId = parseInt(this.requisiteIndex[requisiteId].entityId);
						bankDetailId = parseInt(this.requisiteIndex[requisiteId].bankDetailIdSelected);
						if (bankDetailId < 0 || isNaN(bankDetailId))
							bankDetailId = 0;

						if (entityTypeId > 0 && entityId > 0)
						{
							if (typeof(this.changeSelectedRequisiteHandler) === "function")
								this.changeSelectedRequisiteHandler(entityTypeId, entityId, requisiteId, bankDetailId);

							if (typeof(this.changeLinkedRequisiteHandler) === "function")
								this.changeLinkedRequisiteHandler(requisiteId, bankDetailId);

							var url = BX.util.add_url_param(this.ajaxUrl, {sessid: BX.bitrix_sessid()});
							var data = {
								"action": "savelastselectedrequisite",
								"requisiteEntityTypeId": entityTypeId,
								"requisiteEntityId": entityId,
								"requisiteId": requisiteId,
								"bankDetailId": bankDetailId
							};
							BX.ajax.post(url, data);
						}
					}
				}
			}
		},
		onBankDetailRadioClick: function(e)
		{
			var requisiteId, entityTypeId, entityId, bankDetailId, mr;

			if(!e)
				e = window.event;

			if (e && BX.type.isDomNode(e.target) && BX.type.isNotEmptyString(e.target.value))
			{
				mr = e.target.value.match(/^(\d+)\|(\d+)$/);
				if (mr)
				{
					requisiteId = parseInt(mr[1]);
					bankDetailId = parseInt(mr[2]);
					if (requisiteId > 0)
					{
						if (this.requisiteIndex[requisiteId])
						{
							entityTypeId = parseInt(this.requisiteIndex[requisiteId].entityTypeId);
							entityId = parseInt(this.requisiteIndex[requisiteId].entityId);

							if (entityTypeId > 0 && entityId > 0)
							{
								if (typeof(this.changeSelectedRequisiteHandler) === "function")
									this.changeSelectedRequisiteHandler(entityTypeId, entityId, requisiteId, bankDetailId);

								if (typeof(this.changeLinkedRequisiteHandler) === "function")
									this.changeLinkedRequisiteHandler(requisiteId, bankDetailId);

								var url = BX.util.add_url_param(this.ajaxUrl, {sessid: BX.bitrix_sessid()});
								var data = {
									"action": "savelastselectedrequisite",
									"requisiteEntityTypeId": entityTypeId,
									"requisiteEntityId": entityId,
									"requisiteId": requisiteId,
									"bankDetailId": bankDetailId
								};
								BX.ajax.post(url, data);
							}
						}
					}
				}
			}
		},
		getIndex: function()
		{
			return this.blockIndex;
		},
		setIndex: function(index)
		{
			this.blockIndex = index;
		},
		clean: function(cleanAll)
		{
			cleanAll = !!cleanAll;
			if (this.wrapper)
			{
				if (this.closeButtonNode)
				{
					if (this.closeButtonClickHandler)
					{
						BX.unbind(this.closeButtonNode, "click", this.closeButtonClickHandler);
						this.closeButtonClickHandler = null;
						this.closeButtonNode = null;
						this.requisiteIndex = [];
					}
				}

				BX.cleanNode(this.wrapper, cleanAll);
			}
		},
		destroy: function()
		{
			if (this.blockArea)
				this.blockArea.onBlockDestroy(this.blockIndex);
			else
				this.continueDestroy();
		},
		continueDestroy: function()
		{
			this.clean(true);
		}
	};

	return EntityEditorBlockClass;
})();

if(typeof(BX.Crm.RequisiteBankDetailsArea) === "undefined")
{
	BX.Crm.RequisiteBankDetailsArea = function()
	{
		this._id = null;
		this.formId = "";
		this.container = null;
		this.nextNode = null;
		this.messages = {};
		this.presetCountryId = 0;
		this.fieldList = [];
		this.dataList = [];
		this.fieldNameTemplate = '';
		this.wrapper = null;
		this.blocksContainer = null;

		this.blockList = [];

		this.elementIndex = 0;

		this.cleanState = {
			started: false,
			cleanAll: false,
			afterClean: null
		};

		this.addBlockBtn = null;
		this.addBlockBtnClickHandler = null;

		this.mode = "EDIT";

		this._requisitePopupCloseHandler = BX.delegate(this.onRequisitePopupClose, this);
	};
	BX.Crm.RequisiteBankDetailsArea.prototype =
	{
		initialize: function(id, parameters)
		{
			var i;

			this._id = BX.type.isNotEmptyString(id) ?
				id : "crm_rq_bank_details_area_" + Math.random().toString().substring(2);
			this.formId = parameters.formId || "";
			this.container = parameters.container;
			this.nextNode = parameters.nextNode;
			this.messages = parameters.messages || {};
			if (parameters.hasOwnProperty("presetCountryId"))
			{
				this.presetCountryId = parseInt(parameters.presetCountryId);
				if (this.presetCountryId < 0 || isNaN(this.presetCountryId))
					this.presetCountryId = 0;
			}
			this.fieldList = BX.type.isArray(parameters.fieldList) ? parameters.fieldList : [];
			this.dataList = BX.type.isArray(parameters.dataList) ? parameters.dataList : [];
			this.fieldNameTemplate = BX.type.isNotEmptyString(parameters.fieldNameTemplate) ?
				parameters.fieldNameTemplate : "BANK_DETAILS[#ELEMENT_ID#][#FIELD_NAME#]";
			this.lastInForm = !!parameters.lastInForm;
			this.mode = parameters.mode || "EDIT";

			if (this.container)
			{
				this.clean();

				if (!this.wrapper)
				{
					this.wrapper = BX.create("DIV", {"attrs": {"class": "crm-offer-requisite-block-wrap"}});
					if (this.wrapper)
					{
						if (this.nextNode)
							this.container.insertBefore(this.wrapper, this.nextNode);
						else
							this.container.appendChild(this.wrapper);
					}
					if (this.wrapper)
					{
						if (this.nextNode)
							this.container.insertBefore(this.wrapper, this.nextNode);
						else
							this.container.appendChild(this.wrapper);
					}
				}
			}

			this.elementIndex = 0;

			if (this.wrapper)
			{
				this.wrapper.appendChild(BX.create("SPAN", {
					"attrs": {"class": "crm-offer-requisite-option"},
					children: [
						this.addBlockBtn = BX.create("SPAN", {
							"attrs": {
								"class": "crm-offer-requisite-option-text"
							},
							"text": this.getMessage("addBlockBtnText")
						})
					]
				}));

				this.wrapper.appendChild(BX.create("DIV", {
					"attrs": {"class": "crm-offer-requisite-form-wrap"},
					"children": [
						this.blocksContainer = BX.create("DIV", {
							"attrs": {"class": "crm-multi-address"}
						})
					]
				}));

				if (this.addBlockBtn)
				{
					this.addBlockBtnClickHandler = BX.delegate(this.onAddBlockButtonClick, this);
					BX.bind(this.addBlockBtn, "click", this.addBlockBtnClickHandler);
				}

				if (BX.type.isArray(this.dataList))
				{
					for (i = 0; i < this.dataList.length; i++)
					{
						if (BX.type.isPlainObject(this.dataList[i]))
						{
							id = (this.dataList[i].hasOwnProperty("ID")) ? parseInt(this.dataList[i]["ID"]) : 0;
							this.addBlock(id, this.dataList[i]);
						}
					}
				}
			}

			if (BX.type.isNotEmptyString(this.formId))
				BX.addCustomEvent("CrmRequisitePopupFormManagerClosePopup", this._requisitePopupCloseHandler);
		},
		getId: function()
		{
			return this._id;
		},
		getWrapperNode: function()
		{
			return this.wrapper;
		},
		getMessage: function(msgId)
		{
			return this.messages[msgId];
		},
		onAddBlockButtonClick: function()
		{
			this.addBlock();
		},
		addBlock: function(bankDetailId, bankDetailData)
		{
			var result = 0;

			bankDetailId = parseInt(bankDetailId);
			if (bankDetailId < 0 || isNaN(bankDetailId))
				bankDetailId = 0;

			if (!bankDetailData || !BX.type.isPlainObject(bankDetailData))
				bankDetailData = {};

			var blockIndex = this.blockList.length;
			var nextNode = null;
			if (blockIndex > 0)
				nextNode = this.blockList[blockIndex-1].getWrapperNode();
			var block = new BX.Crm.RequisiteBankDetailsBlock({
				mode: this.mode,
				formId: this.formId,
				bankDetailId: bankDetailId,
				bankDetailData: bankDetailData,
				blockArea: this,
				blockIndex: blockIndex,
				elementIndex: this.elementIndex++,
				container: this.blocksContainer,
				nextNode: nextNode,
				presetCountryId: this.presetCountryId,
				fieldList: this.fieldList,
				fieldNameTemplate: this.fieldNameTemplate
			});

			if (block)
			{
				result = block.getPseudoId();
				this.blockList[blockIndex] = block;
			}

			return result;
		},
		onBlockDestroy: function(blockIndex)
		{
			if (blockIndex >= 0 && this.blockList && this.blockList.length > blockIndex)
			{
				this.blockList.splice(blockIndex, 1);
				this.reindexBlocks(blockIndex);
			}

			if (this.cleanState.started)
				this.continueClean();
		},
		reindexBlocks: function(indexFrom)
		{
			for (var i = indexFrom; i < this.blockList.length; i++)
				this.blockList[i].setIndex(i);
		},
		isLastInForm: function()
		{
			return this.lastInForm;
		},
		getLastBlockIndex: function()
		{
			return this.blockList.length - 1;
		},
		onRequisitePopupClose: function(requisitePopupFormManager)
		{
			var formId = "";

			if (requisitePopupFormManager !== null && typeof(requisitePopupFormManager) === "object")
			{
				formId = requisitePopupFormManager.getFormId();
				if (BX.type.isNotEmptyString(formId))
				{
					formId = formId.replace(/[^a-z0-9_]/ig, "");
					if (formId === this.formId)
						BX.Crm.RequisiteBankDetailsArea.delete(this.getId());
				}
			}
		},
		clean: function(cleanAll, afterClean)
		{
			if (!this.cleanState.started)
			{
				this.cleanState.started = true;
				this.cleanState.cleanAll = !!cleanAll;
				this.cleanState.afterClean = (typeof(afterClean) === "function") ? afterClean : null;

				if (this.blockList.length > 0)
					this.blockList[0].destroy();
				else
					this.continueClean();
			}
		},
		continueClean: function()
		{
			if (this.cleanState.started)
			{
				if (this.blockList.length > 0)
				{
					this.blockList[0].destroy();
				}
				else
				{
					if (this.addBlockBtn)
					{
						if (this.addBlockBtnClickHandler)
						{
							BX.unbind(this.addBlockBtn, "click", this.addBlockBtnClickHandler);
							this.addBlockBtnClickHandler = null;
						}
						this.addBlockBtn = null;
					}

					if (this.wrapper)
					{
						this.blocksContainer = null;
						BX.cleanNode(this.wrapper, this.cleanState.cleanAll);
					}

					var afterClean = this.cleanState.afterClean;

					this.cleanState = {
						cleanAll: false,
						started: false,
						afterClean: null
					};
					this.mode = "EDIT";

					BX.removeCustomEvent("CrmRequisitePopupFormManagerClosePopup", this._requisitePopupCloseHandler);

					if (typeof(afterClean) === "function")
						afterClean();
				}
			}
		},
		destroy: function(afterDestroy)
		{
			this.clean(true, afterDestroy);
		}
	};
	BX.Crm.RequisiteBankDetailsArea.items = {};
	BX.Crm.RequisiteBankDetailsArea.create = function (id, parameters)
	{
		var self = new BX.Crm.RequisiteBankDetailsArea();
		self.initialize(id, parameters);
		this.items[self.getId()] = self;
		return self;
	};
	BX.Crm.RequisiteBankDetailsArea.delete = function(id)
	{
		if (BX.Crm.RequisiteBankDetailsArea.items.hasOwnProperty(id))
		{
			var self = BX.Crm.RequisiteBankDetailsArea.items[id];
			self.destroy();
			delete BX.Crm.RequisiteBankDetailsArea.items[id];
		}
	};
}

if(typeof(BX.Crm.RequisiteBankDetailsBlock) === "undefined")
{
	BX.Crm.RequisiteBankDetailsBlock = function (parameters)
	{
		this.bankDetailId = parseInt(parameters.bankDetailId);
		if (this.bankDetailId < 0)
			this.bankDetailId = 0;

		this.bankDetailData = (BX.type.isPlainObject(parameters.bankDetailData)) ? parameters.bankDetailData : {};

		this.formId = parameters.formId || "";
		this.blockArea = parameters.blockArea;
		this.blockIndex = parameters.blockIndex;
		this.elementIndex = parameters.elementIndex;
		this.container = parameters.container;
		this.nextNode = parameters.nextNode;
		this.fieldList = parameters.fieldList;
		this.fieldNameTemplate = parameters.fieldNameTemplate;
		this.mode = parameters.mode || "EDIT";
		this.wrapper = null;

		this.documentClickHandler = null;

		this.editNameMode = false;
		this.editNameInput = null;
		this.hiddenNameInput = null;
		this.nameLabelNode = null;
		this.editNameBtn = null;
		this.editNameKeyPressHandler = null;
		this.editNameBtnClickHandler = null;

		this.deleteBtn = null;
		this.deleteBtnClickHandler = null;
		this.markedAsDeleted = false;

		this.presetCountryId = 0;
		if (parameters.hasOwnProperty("presetCountryId"))
		{
			this.presetCountryId = parseInt(parameters.presetCountryId);
			if (this.presetCountryId < 0 || isNaN(this.presetCountryId))
				this.presetCountryId = 0;
		}

		this.wrapperId = this.getFormId() + "_BD" + this.getPseudoId();

		this._crmRequisiteBankDetailBlockGetParamsHandler =
			BX.delegate(this.onCrmRequisiteBankDetailBlockGetParams, this);

		this.initialize();

		BX.onCustomEvent("CrmFormBankDetailBlockCreate", [this.prepareBlockParams()]);
	};
	BX.Crm.RequisiteBankDetailsBlock.prototype =
	{
		initialize: function()
		{
			var value, valueNode, valueNodeName, hiddenContainer, node;

			if (this.container)
			{
				this.clean();

				if (!this.wrapper)
				{
					this.wrapper = BX.create(
						"DIV",
						{
							"attrs": {"id": this.wrapperId, "class": "crm-multi-address-item"}
						}
					);
					if (this.wrapper)
					{
						if (this.nextNode)
							this.container.insertBefore(this.wrapper, this.nextNode);
						else
							this.container.appendChild(this.wrapper);
					}
				}
			}

			if (this.wrapper && this.fieldList.length > 0)
			{
				var tableNode, row, cell;

				tableNode = BX.create("TABLE", {"attrs": {"class": "crm-offer-info-table"}});
				hiddenContainer = BX.create("DIV", {"attrs": {"style": "display: none;"}});

				// title
				row = tableNode.insertRow(-1);
				cell = row.insertCell(-1);
				cell.colSpan = "4";
				cell.appendChild(node = BX.create("DIV", {
					"attrs": {"class": "crm-offer-title"},
					"children": [
						this.editNameInput = BX.create("INPUT", {
							"attrs": {
								"class": "crm-item-table-inp",
								"type": "text",
								"placeholder": this.getMessage("fieldNamePlaceHolder"),
								"style": "display: none;"
							}
						}),
						this.nameLabelNode = BX.create("SPAN", {"attrs": {"class": "crm-offer-title-text"}})
					]
				}));
				if (node && this.mode === "EDIT")
				{
					this.editNameMode = false;
					this.documentClickHandler = BX.delegate(this.onDocumentClick, this);
					this.editNameKeyPressHandler = BX.delegate(this.onEditNameKeyPress, this);
					node.appendChild(BX.create("SPAN", {
						"attrs": {"class": "crm-offer-title-set-wrap"},
						"children": [
							this.editNameBtn = BX.create("SPAN", {"attrs": {"class": "crm-offer-title-edit"}}),
							this.deleteBtn = BX.create("SPAN", {"attrs": {"class": "crm-offer-title-del"}})
						]
					}));
					if (this.editNameBtn)
					{
						this.editNameBtnClickHandler = BX.delegate(this.onEditNameButtonClick, this);
						BX.bind(this.editNameBtn, "click", this.editNameBtnClickHandler);
					}
					if (this.deleteBtn)
					{
						this.deleteBtnClickHandler = BX.delegate(this.onDeleteButtonClick, this);
						BX.bind(this.deleteBtn, "click", this.deleteBtnClickHandler);
					}

					BX.addCustomEvent(
						"CrmRequisiteBankDetailBlockGetParams",
						this._crmRequisiteBankDetailBlockGetParamsHandler
					);
				}

				for (var i = 0; i < this.fieldList.length; i++)
				{
					valueNodeName = this.resolveFieldInputName(this.fieldList[i]["name"]);
					if (BX.type.isPlainObject(this.bankDetailData)
						&& this.bankDetailData.hasOwnProperty(this.fieldList[i]["name"]))
					{
						value = this.bankDetailData[this.fieldList[i]["name"]];
					}
					else if (this.fieldList[i].hasOwnProperty("defaultValue"))
					{
						value = this.fieldList[i]["defaultValue"];
					}
					else
					{
						value = "";
					}
					if (this.fieldList[i]["name"] === "NAME")
					{
						if (!BX.type.isNotEmptyString(value))
						{
							value = this.getMessage("bankDetailsTitle") + " " + (this.elementIndex + 1);
						}
						if (this.nameLabelNode)
						{
							BX.adjust(this.nameLabelNode, {"text": value});
						}
						if (hiddenContainer)
						{
							hiddenContainer.appendChild(this.hiddenNameInput = BX.create("INPUT", {
								"attrs": {
									"type": "hidden",
									"name": valueNodeName,
									"value": value
								}
							}));
						}
					}
					else if (hiddenContainer && this.fieldList[i]["type"] === "hidden")
					{
						valueNode = BX.create("INPUT", {
							"attrs": {
								"type": "hidden",
								"name": valueNodeName,
								"value": value
							}
						});
						if (valueNode)
							hiddenContainer.appendChild(valueNode);
					}
					else if (tableNode)
					{
						row = tableNode.insertRow(-1);
						cell = row.insertCell(-1);
						cell.className = "crm-offer-info-left";
						cell.appendChild(BX.create("SPAN", {
							"attrs": {"class": "crm-offer-info-label-alignment"}
						}));
						if (this.fieldList[i]["required"])
						{
							cell.appendChild(BX.create("SPAN", {
								"attrs": {"class": "required"},
								"text": "*"
							}));
						}
						cell.appendChild(BX.create("SPAN", {
							"attrs": {"class": "crm-offer-info-label"},
							"text": this.fieldList[i]["title"] + ":"
						}));
						cell = row.insertCell(-1);
						cell.className = "crm-offer-info-right";
						valueNode = null;
						if (this.fieldList[i]["type"] === "text")
						{
							valueNode = BX.create("INPUT", {
								"attrs": {
									"type": "text",
									"class": "crm-offer-item-inp",
									"name": valueNodeName,
									"value": value
								}
							});
						}
						else if (this.fieldList[i]["type"] === "textarea")
						{
							valueNode = BX.create("TEXTAREA", {
								"attrs": {
									"class": "crm-offer-textarea",
									"name": valueNodeName,
									"cols": 40,
									"rows": 3
								},
								"html": BX.util.htmlspecialchars(value)
							});
						}
						if (valueNode)
							cell.appendChild(valueNode);
						cell = row.insertCell(-1);
						cell.className = "crm-offer-info-right-btn";
						cell = row.insertCell(-1);
						cell.className = "crm-offer-last-td";
					}
				}

				if (tableNode)
					this.wrapper.appendChild(tableNode);
				if (hiddenContainer)
					this.wrapper.appendChild(hiddenContainer);
			}
		},
		getFormId: function()
		{
			return this.formId;
		},
		setIndex: function(index)
		{
			this.blockIndex = index;
		},
		getIndex: function()
		{
			return this.blockIndex;
		},
		getPseudoId: function()
		{
			return (this.bankDetailId > 0) ? this.bankDetailId : "n" + this.elementIndex;
		},
		getWrapperNode: function()
		{
			return this.wrapper;
		},
		getMessage: function(name)
		{
			if (this.blockArea)
				return this.blockArea.getMessage(name);

			return "";
		},
		getFieldList: function()
		{
			return this.fieldList;
		},
		clean: function(cleanAll)
		{
			cleanAll = !!cleanAll;

			if (this.wrapper)
			{
				this.hiddenNameInput = null;
				this.nameLabelNode = null;
				if (this.editNameInput && this.editNameKeyPressHandler)
				{
					BX.unbind(this.editNameInput, "keydown", this.editNameKeyPressHandler);
					this.editNameKeyPressHandler = null;
				}
				this.editNameInput = null;
				this.editNameMode = false;
				if (this.documentClickHandler)
					this.enableDocumentClick(false);
				this.documentClickHandler = null;
				if (this.editNameBtn)
				{
					if (this.editNameBtnClickHandler)
					{
						BX.unbind(this.editNameBtn, "click", this.editNameBtnClickHandler);
						this.editNameBtnClickHandler = null;
					}
					this.editNameBtn = null;
				}
				if (this.deleteBtn)
				{
					if (this.deleteBtnClickHandler)
					{
						BX.unbind(this.deleteBtn, "click", this.deleteBtnClickHandler);
						this.deleteBtnClickHandler = null;
					}
					this.deleteBtn = null;
					this.markedAsDeleted = false;
				}

				BX.removeCustomEvent(
					"CrmRequisiteBankDetailBlockGetParams",
					this._crmRequisiteBankDetailBlockGetParamsHandler
				);

				BX.cleanNode(this.wrapper, cleanAll);
			}
		},
		destroy: function()
		{
			this.clean(true);

			if (this.blockArea)
				this.blockArea.onBlockDestroy(this.blockIndex);
		},
		onEditNameButtonClick: function()
		{
			if (this.editNameMode)
				return;

			this.enableEditNameMode(true);
		},
		enableEditNameMode: function(enable)
		{
			enable = !!enable;

			if (this.editNameMode === enable)
				return;

			this.editNameMode = enable;
			if (this.editNameMode)
			{
				if (this.nameLabelNode)
					this.nameLabelNode.style.display = "none";
				if (this.editNameInput)
				{
					this.editNameInput.style.display = "";
					if (this.hiddenNameInput)
						this.editNameInput.value = this.hiddenNameInput.value;
					this.editNameInput.focus();
					this.editNameInput.setSelectionRange(0, this.editNameInput.value.length);
					this.enableDocumentClick(true);
					BX.bind(this.editNameInput, "keydown", this.editNameKeyPressHandler);
				}
			}
			else
			{
				this.editNameInput.style.display = "none";
				this.nameLabelNode.style.display = "";

				this.enableDocumentClick(false);
				BX.unbind(this.editNameInput, "keydown", this.editNameKeyPressHandler);
			}
		},
		enableDocumentClick: function(enable)
		{
			if(enable)
			{
				var self = this;
				window.setTimeout(function(){ BX.bind(document, "click", self.documentClickHandler); }, 0);
			}
			else
			{
				BX.unbind(document, "click", this.documentClickHandler);
			}
		},
		onDocumentClick: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			var target = BX.getEventTarget(e);
			if(target && this.editNameInput === target)
			{
				return;
			}

			if(!this.editNameMode)
			{
				BX.unbind(document, "click", this.documentClickHandler);
			}
			else
			{
				this.completeEditName(true);
			}
		},
		onEditNameKeyPress: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			if(e.keyCode == 13)
			{
				//Enter
				this.completeEditName(true);
				return BX.eventReturnFalse(e);
			}
			else if(e.keyCode == 27)
			{
				//Esc
				this.completeEditName(false);
				return BX.eventReturnFalse(e);
			}

			return true;
		},
		completeEditName: function(enableSaving)
		{
			if(!this.editNameMode)
			{
				return;
			}

			if(!!enableSaving)
			{
				var name = BX.util.trim(this.editNameInput.value);
				if(name !== "" && this.hiddenNameInput)
				{
					this.hiddenNameInput.value = name;
				}
				if (this.nameLabelNode)
					BX.adjust(this.nameLabelNode, {"text": name});
			}

			this.enableEditNameMode(false);
		},
		onDeleteButtonClick: function()
		{
			this.markAsDeleted();
		},
		resolveFieldInputName: function(fieldName)
		{
			if(this.fieldNameTemplate !== "")
			{
				return this.fieldNameTemplate.replace(
					"#ELEMENT_ID#", this.getPseudoId()
				).replace(
					"#FIELD_NAME#", fieldName
				);
			}

			return fieldName;
		},
		markAsDeleted: function()
		{
			if(this.markedAsDeleted)
			{
				return;
			}

			this.markedAsDeleted = true;

			if(this.wrapper)
			{
				this.wrapper.appendChild(
					BX.create("INPUT",
						{
							props:
							{
								"name": this.resolveFieldInputName("DELETED"),
								"type": "hidden",
								"value": "Y"
							}
						}
					)
				);
				this.wrapper.style.display = "none";
			}

			BX.onCustomEvent("CrmFormBankDetailBlockRemove", [this]);
		},
		isMarkedAsDeleted: function()
		{
			return this.markedAsDeleted;
		},
		onCrmRequisiteBankDetailBlockGetParams: function(callback)
		{
			if (BX.type.isFunction(callback))
				callback(this.prepareBlockParams());
		},
		prepareBlockParams: function ()
		{
			var fieldNameTemplate = "";
			if(this.fieldNameTemplate !== "")
			{
				fieldNameTemplate = this.fieldNameTemplate.replace("#ELEMENT_ID#", this.getPseudoId());
			}
			var countryId = 0;
			if (this.bankDetailData.hasOwnProperty("COUNTRY_ID"))
			{
				countryId = parseInt(this.bankDetailData["COUNTRY_ID"]);
				if (countryId < 0 || isNaN(countryId))
					countryId = 0;
			}
			if (countryId <= 0)
				countryId = this.presetCountryId;
			return {
				bankDetailBlock: this,
				formId: this.formId,
				containerId: this.wrapperId,
				bankDetailPseudoId: this.getPseudoId(),
				countryId: countryId,
				enableFieldMasquerading: this.fieldNameTemplate !== "",
				fieldNameTemplate: fieldNameTemplate
			};
		}
	};
}
