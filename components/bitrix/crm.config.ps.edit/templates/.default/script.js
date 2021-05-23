BX.crmPaySys = {
	formId: '',
	orderProps: {},
	orderFields: {},
	paymentFields: {},
	userProps: {},
	userFields: {},
	requisiteFields: {},
	bankDetailFields: {},
	companyFields: {},
	userColumnFields: {},
	contactFields: {},
	simpleMode: true,
	template: '',

	init: function(params)
	{
		for(var key in params)
		{
			if(params.hasOwnProperty(key))
			{
				this[key] = params[key];
			}
		}

		this.formObj = BX(this.formId);
		if (this.template)
			BX.crmPSActionFile.reloadPreview(this.template);

		this.bindInputsChange();
	},
	getTemplatePreview : function ()
	{
		var frame = BX('frame');
		frame.parentNode.style.opacity = '0.5';

		var form = BX('form_CRM_PS_EDIT_FORM');
		var data = {
			formData : BX.ajax.prepareForm(form),
			action : 'refresh_template',
			sessid: BX.bitrix_sessid()
		};

		BX.ajax({
			data: data,
			method: 'POST',
			dataType: 'json',
			url: BX.crmPaySys.url+'/ajax.php',
			onsuccess: BX.delegate(
				function(result) {
					if (result.TEMPLATE)
					{
						BX.crmPSActionFile.reloadPreview(result.TEMPLATE);
						BX.crmPaySys.bindInputsChange();
					}
					else
					{
						BX.crmPSActionFile.removeTemplatePreview();
					}

					var frame = BX('frame');
					frame.parentNode.style.opacity = '1';
				}, this),
			onfailure: function() {
				var frame = BX('frame');
				frame.parentNode.style.opacity = '1';

				BX.debug('onfailure: getHandlerTemplate');
			}
		});
	},
	bindInputsChange : function ()
	{
		var tabs = BX('bx-tabs');
		var inputs = BX.findChildren(tabs.nextElementSibling, {tag : 'input'}, true);
		for (var i in inputs)
		{
			if (inputs.hasOwnProperty(i))
				BX.bind(inputs[i], 'change', this.getTemplatePreview);
		}
	},
	switchMode: function()
	{
		var switcher = BX("MODE_SWITCHER");

		if(BX.crmPaySys.simpleMode)
		{
			BX.crmPSPropType.showItems();
			BX.crmPSActionFile.showNoneSimpleRows();
			switcher.innerHTML = BX.message("CRM_PS_HIDE_FIELDS");
		}
		else
		{
			BX.crmPSPropType.hideItems();
			BX.crmPSActionFile.hideNoneSimpleRows();
			switcher.innerHTML = BX.message("CRM_PS_SHOW_FIELDS");
		}

		BX.crmPaySys.simpleMode = !BX.crmPaySys.simpleMode;
	},

	getPrivateKey : function ()
	{
		var data = {
			action: 'get_private_key',
			pay_system_id: BX('ps_id').value,
			sessid: BX.bitrix_sessid()
		};
		BX.ajax({
			data: data,
			method: 'POST',
			dataType: 'json',
			url: BX.crmPaySys.url+'/ajax.php',
			onsuccess: BX.delegate(function(result)
			{
				if(result.ERROR)
					alert(result.ERROR);
				else
					alert(BX.message('CRM_PS_GENERATE_SUCCESS'));
			}, this),
			onfailure: function() {BX.debug('onfailure: BX.crmPaySys.getPrivateKey');}
		});
	}
};

BX.crmPSPersonType = {

	init: function(params)
	{
		for(var key in params)
			this[key] = params[key];

		var persTypeSelector = BX.crmPaySys.formObj["PERSON_TYPE_ID"];
		if(persTypeSelector)
			BX.bind(persTypeSelector, 'change', BX.delegate(BX.crmPSPersonType.onSelect, this));
	},

	getId: function()
	{
		return BX.crmPaySys.formObj["PERSON_TYPE_ID"].value;
	},

	onSelect: function()
	{
		var opFNames = this.getOrderPropsFNames();
		this.replaceOrderPropsSelectors(opFNames);
	},

	getOrderPropsFNames: function()
	{
		var retNames = [];

		for (var i = 0, l = BX.crmPaySys.formObj.elements.length; i< l; i++)
		{
			if(!/^TYPE_/.test(BX.crmPaySys.formObj.elements[i].name))
				continue;

			if(BX.crmPaySys.formObj.elements[i].value != "PROPERTY")
				continue;

			retNames.push(BX.crmPaySys.formObj.elements[i].name.replace(/^TYPE_/, ""));
		}

		return retNames;
	},

	replaceOrderPropsSelectors: function(opFNames)
	{
		var persTypeId = this.getId();

		var actionFileId = BX.crmPSActionFile.getId();
		var actionName = 'bill';
		if (actionFileId.indexOf('quote') >= 0)
			actionName = 'quote';

		var result = Object.assign({}, BX.crmPaySys.orderProps[persTypeId], BX.crmPaySys.userFields[actionName]);
		for(var i = 0, l = opFNames.length; i< l; i++)
			BX.crmPSPropType.insertOptions(BX.crmPaySys.formObj["VALUE1_"+opFNames[i]], result);
	}
};

BX.crmPSPropType = {
	aTabs: {},

	init: function(params)
	{
		for(var key in params)
			this[key] = params[key];

		this.bindEvents();
	},

	bindEvents: function()
	{
		var onSelectFunc = function(){ var selObj = this; BX.delegate(BX.crmPSPropType.onSelect(selObj), this); };

		for (var i = 0, l = BX.crmPaySys.formObj.elements.length; i< l; i++)
		{
			if(!/^TYPE_/.test(BX.crmPaySys.formObj.elements[i].name))
				continue;

			var typeSelector = BX.crmPaySys.formObj.elements[i];

			if(typeSelector)
				BX.bind(typeSelector, 'change', onSelectFunc);
		}
	},

	onSelect: function(selObj)
	{
		this.replaceValueField(selObj);
	},

	replaceValueField: function(selObj)
	{
		var fieldName = selObj.name.replace(/^TYPE_/, "");

		if(selObj.value == "USER")
			this.setUserProps(fieldName);
		else if(selObj.value == "ORDER")
			this.setOrderFields(fieldName);
		else if(selObj.value == "PAYMENT")
			this.setPaymentFields(fieldName);
		else if(selObj.value == "PROPERTY")
			this.setOrderProps(fieldName);
		else if(selObj.value == "REQUISITE")
			this.setRequisiteFields(fieldName);
		else if(selObj.value == "BANK_DETAIL")
			this.setBankDetailFields(fieldName);
		else if(selObj.value == "CRM_COMPANY")
			this.setCompanyFields(fieldName);
		else if(selObj.value == "CRM_CONTACT")
			this.setContactFields(fieldName);
		else if(selObj.value == "MC_REQUISITE")
			this.setRequisiteFields(fieldName);
		else if(selObj.value == "MC_BANK_DETAIL")
			this.setBankDetailFields(fieldName);
		else if(selObj.value == "CRM_MYCOMPANY")
			this.setCompanyFields(fieldName);
		else if(selObj.value == "USER_COLUMN_LIST")
			this.setUserColumnsFields(fieldName);
		else if(selObj.value == "" || selObj.value == "VALUE")
			this.setOtherProps(fieldName);
	},

	setUserProps: function(fieldName)
	{
		this.insertOptions(BX.crmPaySys.formObj["VALUE1_"+fieldName], BX.crmPaySys.userProps);
		this.showSelectorField(fieldName);
	},

	setOrderFields: function(fieldName)
	{
		this.insertOptions(BX.crmPaySys.formObj["VALUE1_"+fieldName], BX.crmPaySys.orderFields);
		this.showSelectorField(fieldName);
	},

	setPaymentFields: function(fieldName)
	{
		this.insertOptions(BX.crmPaySys.formObj["VALUE1_"+fieldName], BX.crmPaySys.paymentFields);
		this.showSelectorField(fieldName);
	},

	setOrderProps: function(fieldName)
	{
		var persTypeId = BX.crmPSPersonType.getId();
		var props = BX.crmPaySys.orderProps[persTypeId];

		var actionFileId = BX.crmPSActionFile.getId();
		var actionName = 'bill';
		if (actionFileId.indexOf('quote') >= 0)
			actionName = 'quote';

		if (typeof(BX.crmPaySys.userFields[actionName]) !== "undefined")
		{
			props = BX.clone(props);
			var userFields = BX.crmPaySys.userFields[actionName];
			for(var k in userFields)
			{
				if(!userFields.hasOwnProperty(k))
				{
					continue;
				}
				props[k] = userFields[k];
			}
		}

		this.insertOptions(BX.crmPaySys.formObj["VALUE1_" + fieldName], props);
		this.showSelectorField(fieldName);
	},

	setRequisiteFields: function(fieldName)
	{
		this.rebuildSelect(BX.crmPaySys.formObj["VALUE1_"+fieldName], BX.crmPaySys.requisiteFields, "");
		this.showSelectorField(fieldName);
	},

	setBankDetailFields: function(fieldName)
	{
		this.rebuildSelect(BX.crmPaySys.formObj["VALUE1_"+fieldName], BX.crmPaySys.bankDetailFields);
		this.showSelectorField(fieldName);
	},

	rebuildSelect: function (select, items, value)
	{
		var opt, optIndex, el, i, j;
		var setSelected = false;
		var bMultiple, bGroup;
		var curGroup = null;

		if (!(value instanceof Array))
			value = [value];
		if (select)
		{
			bMultiple = !!(select.getAttribute('multiple'));
			while (opt = select.lastChild)
				select.removeChild(opt);
			optIndex = 0;
			for (i = 0; i < items.length; i++)
			{
				bGroup = false;
				if (items[i]["type"])
				{
					if (items[i]["type"] === "group")
					{
						el = document.createElement("optgroup");
						el.label = items[i]['title'];
						bGroup = true;
					}
					else if (items[i]["type"] === "endgroup")
					{
						curGroup = null;
						continue;
					}
				}
				else
				{
					el = document.createElement("option");
					el.value = items[i]['id'];
					el.innerHTML = BX.util.htmlspecialchars(items[i]['title']);
				}

				if (!bGroup && curGroup)
					curGroup.appendChild(el);
				else
					select.appendChild(el);

				if (bGroup)
				{
					curGroup = el;
				}
				else
				{
					if (!setSelected || bMultiple)
					{
						for (j = 0; j < value.length; j++)
						{
							if (items[i]['id'] == value[j])
							{
								el.selected = true;
								if (!setSelected)
								{
									setSelected = true;
									select.selectedIndex = optIndex;
								}
								break;
							}
						}
					}
					optIndex++;
				}
			}
		}
	},

	setCompanyFields: function(fieldName)
	{
		this.insertOptions(BX.crmPaySys.formObj["VALUE1_"+fieldName], BX.crmPaySys.companyFields);
		this.showSelectorField(fieldName);
	},

	setUserColumnsFields: function(fieldName)
	{
		this.insertOptions(BX.crmPaySys.formObj["VALUE1_"+fieldName], BX.crmPaySys.userColumnFields);
		this.showSelectorField(fieldName);
	},

	setContactFields: function(fieldName)
	{
		this.insertOptions(BX.crmPaySys.formObj["VALUE1_"+fieldName], BX.crmPaySys.contactFields);
		this.showSelectorField(fieldName);
	},

	setOtherProps: function(fieldName)
	{
		BX.crmPaySys.formObj["VALUE2_"+fieldName].value = '';
		this.showInputField(fieldName);
	},

	showSelectorField: function(fieldName, bHide)
	{
		var bShow = !bHide;
		if (BX("VALUE1_"+fieldName))
			BX("VALUE1_"+fieldName).style.display = bShow ? '' : 'none';

		if (BX("VALUE2_"+fieldName))
			BX("VALUE2_"+fieldName).style.display = bShow ? 'none' : '';
	},

	showInputField: function(fieldName)
	{
		this.showSelectorField(fieldName, true);
	},

	insertOptions: function(selObj, oItems)
	{
		var oldVal = selObj.value;

		selObj.options.length = 0;

		for(var property in oItems)
		{
			var option=document.createElement("option"); //todo: make clone
			option.value=property;
			option.text=oItems[property];
			try
			{
				selObj.add(option, null);
			}
			catch(ex)
			{
				selObj.add(option);
			}
		}

		selObj.value = oldVal;
	},

	hideItems: function(bShow)
	{
		var bHide = !bShow;
		var parents = {};
		var i, typeSelector;

		for (i = 0, l = BX.crmPaySys.formObj.elements.length; i< l; i++)
		{
			if (!/^TYPE_/.test(BX.crmPaySys.formObj.elements[i].name))
				continue;

			typeSelector = BX.crmPaySys.formObj.elements[i];

			if(typeSelector && typeSelector.value != 'SELECT' && typeSelector.value != 'FILE' && typeSelector.value != 'CHECKBOX' && typeSelector.value != 'USER_COLUMN_LIST')
			{
				typeSelector.style.display = bHide ? 'none' : '';
				if (bHide)
				{
					BX.remove(typeSelector.nextElementSibling);
				}
				else
				{
					typeSelector.style.marginBottom = '5px';
					typeSelector.parentNode.insertBefore(BX.create('br'), typeSelector.nextElementSibling);
				}
			}

			if (typeSelector && (typeSelector.value === '' || typeSelector.value === 'SELECT' || typeSelector.value === 'FILE' || typeSelector.value === 'CHECKBOX'))
			{
				var parent = BX.findParent(typeSelector, {className : 'bx-edit-tab-inner'}, true);
				var id = parent.getAttribute('id');
				var entity = id.substring(10);
				parents[entity] = true;
			}
		}

		for (i in this.aTabs)
		{
			if (!parents.hasOwnProperty(this.aTabs[i]))
			{
				var tab = BX('tab_cont_'+this.aTabs[i]);
				tab.style.display = bHide ? 'none' : '';
			}
		}

		if (bHide)
		{
			var hideSelected = false;
			var j;
			for (j in BX.crmPSPropType.aTabs)
			{
				if (!parents.hasOwnProperty(BX.crmPSPropType.aTabs[j]))
				{
					tab = BX('tab_cont_'+BX.crmPSPropType.aTabs[j]);
					tab.style.display = 'none';

					if (hideSelected === false)
						hideSelected = (BX.findChildByClassName(tab, 'bx-tab-selected', true) !== null);
				}
			}

			if (hideSelected)
			{
				for (j in BX.crmPSPropType.aTabs)
				{
					tab = BX('tab_cont_'+BX.crmPSPropType.aTabs[j]);
					if (tab.style.display !== 'none')
					{
						BX.crmPSPropType.ShowTab(BX.crmPSPropType.aTabs[j], true);
						BX.crmPSPropType.SelectTab(BX.crmPSPropType.aTabs[j]);
						break;
					}
				}
			}
		}
	},

	showItems: function()
	{
		this.hideItems(true);
	},

	ShowTab : function(tab_id, on)
	{
		var sel = (on? '-selected':'');
		document.getElementById('tab_cont_'+tab_id).className = 'bx-tab-container'+sel;
		document.getElementById('tab_left_'+tab_id).className = 'bx-tab-left'+sel;
		document.getElementById('tab_'+tab_id).className = 'bx-tab'+sel;
		document.getElementById('tab_right_'+tab_id).className = 'bx-tab-right'+sel;
	},

	HoverTab : function(tab_id, on)
	{
		var tab = document.getElementById('tab_'+tab_id);
		if(tab.className == 'bx-tab-selected')
			return;

		document.getElementById('tab_left_'+tab_id).className = (on? 'bx-tab-left-hover':'bx-tab-left');
		tab.className = (on? 'bx-tab-hover':'bx-tab');
		var tab_right = document.getElementById('tab_right_'+tab_id);
		tab_right.className = (on? 'bx-tab-right-hover':'bx-tab-right');
	},

	SelectTab : function(tab_id)
	{
		var div = document.getElementById('inner_tab_'+tab_id);
		if(div.style.display != 'none')
			return;

		for (var i = 0, cnt = this.aTabs.length; i < cnt; i++)
		{
			var tab = document.getElementById('inner_tab_'+this.aTabs[i]);
			if(tab.style.display != 'none')
			{
				this.ShowTab(this.aTabs[i], false);
				tab.style.display = 'none';
				break;
			}
		}

		this.ShowTab(tab_id, true);
		div.style.display = 'block';

		var hidden = document.getElementById(this.name+'_active_tab');
		if(hidden)
			hidden.value = tab_id;
	}
};

BX.crmPSActionFile = {

	documentHandler: 'invoicedocument',
	arFields: {},
	arFieldsList: {},
	arSavedFields: {},
	typeValuesTmpl: '',
	fileValuesTmpl: '',
	selectValuesTmpl: '',
	userColValuesTmpl: '',
	userPropsListTmpl: '',
	checkboxValuesTmpl: '',
	hiddenFieldList: null,
	groups: null,

	init: function(params)
	{
		this.hiddenFieldList = ['PS_IS_TEST', 'PS_CHANGE_STATUS_PAY', 'PAYPAL_SSL_ENABLE', 'PAYPAL_ON0', 'PAYPAL_ON1', 'PAYPAL_IDENTITY_TOKEN', 'PAYPAL_RETURN', 'PAYPAL_NOTIFY_URL', 'PAYPAL_BUTTON_SRC', 'PAYPAL_BUSINESS'];

		for(var key in params)
			this[key] = params[key];

		if (BX.crmPaySys.simpleMode)
			this.hideNoneSimpleRows();
	},

	getId: function()
	{
		return BX.crmPaySys.formObj["ACTION_FILE"].value;
	},

	getPsMode: function()
	{
		var psModeSelector = BX.crmPaySys.formObj["PS_MODE"];
		if (psModeSelector)
		{
			return psModeSelector.value;
		}

		return null;
	},

	onSelect: function()
	{
		if (BX('SECURITY'))
			BX.remove(BX('SECURITY'));
		this.getAjaxFields();
	},

	onPsModeSelect: function()
	{
		BX.crmPSActionFile.onHandlerModeChange();
		BX.crmPSActionFile.onSelect();
	},

	getAjaxFields: function(params)
	{
		var actionFile = this.getId(),
			psMode = this.getPsMode(),
			data = {
				'id': actionFile,
				'action': 'get_fields',
				'person_type': BX.crmPSPersonType.getId(),
				'sessid': BX.bitrix_sessid()
			};

		if (psMode)
		{
			data.ps_mode = psMode;
		}

		var frame = BX('frame');
		frame.parentNode.style.opacity = '0.5';

		BX.ajax({
			data: data,
			method: 'POST',
			dataType: 'json',
			url: BX.crmPaySys.url+'/ajax.php',
			onsuccess: BX.delegate(function(result) {
				var frame = BX('frame');

				if(result.ERROR)
				{
					BX.debug("BX.crmPSActionFile.getAjaxFields: " + result.ERROR);
				}
				else
				{
					if (result.hasOwnProperty('NAME') && result.NAME)
					{
						for (var i in BX.crmPaySys.formObj.elements)
						{
							if (BX.crmPaySys.formObj.elements.hasOwnProperty(i))
							{
								if (BX.crmPaySys.formObj.elements[i].name == 'NAME')
								{
									BX.crmPaySys.formObj.elements[i].value = result.NAME;
									break;
								}
							}
						}
					}

					BX('MODE_SWITCHER').style.display = 'inline';
					BX('bx-tabs').style.display = 'table';
					BX('bx-tabs').nextElementSibling.style.display = 'table';

					if (result.GROUP_LIST)
						this.groups = result.GROUP_LIST;

					if (result.FIELDS_BY_GROUP)
					{
						BX.crmPSPropType.aTabs = [];
						for (var i in result.FIELDS_BY_GROUP)
						{
							if (result.FIELDS_BY_GROUP.hasOwnProperty(i))
								BX.crmPSPropType.aTabs.push(i);
						}

						this.arFields[this.getId()] = result.FIELDS_BY_GROUP;
						this.insertFields(this.getId(), result.FIELDS_BY_GROUP);
					}

					if (result.FIELDS_LIST)
					{
						this.arFieldsList[this.getId()] = result.FIELDS_LIST;
						this.setFieldsList(result.FIELDS_LIST);
					}

					var psMode = BX.crmPaySys.formObj["PS_MODE"];
					if (psMode)
					{
						BX.remove(psMode.parentNode.parentNode);
					}

					this.onHandlerModeChange();

					var actionFile = BX.crmPaySys.formObj["ACTION_FILE"].parentNode.parentNode;

					if (result.PS_MODE_LIST
						|| BX("ACTION_FILE").value === this.documentHandler
					)
					{
						var tdContent = BX.create('td', { props : {className : 'bx-field-value'}, });
						if (result.hasOwnProperty('PAYMENT_MODE'))
						{
							tdContent.innerHTML = result.PAYMENT_MODE;
						}

						if (BX("ACTION_FILE").value === this.documentHandler)
						{
							var span = BX.create(
								'span',
								{
									text: BX.message('CRM_TEMPLATE_DOCUMENT_ADD'),
									attrs: {
										class: 'bx-button-add-template',
										style: 'margin-left: 5px'
									}
								}
							);
							BX.bind(span, 'click', function () {
								BX.SidePanel.Instance.open(result.INVOICE_DOC_ADD_LINK, {width: 930, events: {onCloseComplete: function() {BX.crmPSActionFile.onSelect(BX("ACTION_FILE"));}}});
							});
							tdContent.appendChild(span);
						}

						var tr = BX.create('tr', {
							children : [
								BX.create(
									'td',
									{
										props : {className : 'bx-field-name bx-padding'},
										text : BX.util.htmlspecialchars(result.PAYMENT_MODE_TITLE)+':'
									}
								),
								tdContent
							]
						});

						BX.insertAfter(tr, actionFile);
					}

					if (result.TEMPLATE)
					{
						this.reloadPreview(result.TEMPLATE);
						BX.crmPaySys.bindInputsChange();
					}
					else
					{
						frame.parentNode.style.display = 'none';
					}
				}

				frame.parentNode.style.opacity = '1';
			}, this),
			onfailure: function() {BX.debug('onfailure: BX.crmPSActionFile.getAjaxFields');}
		});
	},

	onHandlerModeChange: function ()
	{
		var hidden = BX('ACTION_FILE').value.indexOf(this.documentHandler) === 0;

		BX('MODE_SWITCHER').style.display = hidden ? 'none' : 'table';
		BX('bx-tabs').style.display = hidden ? 'none' : 'table';
		BX('bx-tabs').nextElementSibling.style.display = hidden ? 'none' : 'table';
	},

	reloadPreview : function(template)
	{
		var frame = BX('frame');

		frame.parentNode.style.display = 'block';
		var base = BX.create('base');
		base.href = window.location.href;
		frame.contentDocument.open();
		frame.contentDocument.close();
		frame.contentDocument.head.appendChild(base);
		frame.contentDocument.body.innerHTML = template;

		var frameDoc = frame.contentWindow.document;
		if (frameDoc)
		{
			var div = BX.findChild(frameDoc.body, {tag: 'div'});
			if (div)
				div.style.height = '100%';
		}

		frame.onload = function()
		{
			var frameDoc = frame.contentWindow.document;
			if (frameDoc)
			{
				var div = BX.findChild(frameDoc.body, {tag: 'div'});
				if (div)
					div.style.height = '100%';
			}
		}
	},

	removeTemplatePreview: function()
	{
		var frame = BX('frame');
		if (frame)
		{
			BX.remove(frame.parentNode);
		}
	},

	saveFields: function()
	{
		for (var i = 0, l = BX.crmPaySys.formObj.elements.length; i < l; i++)
		{
			if(!/^TYPE_/.test(BX.crmPaySys.formObj.elements[i].name))
				continue;

			var fName = BX.crmPaySys.formObj.elements[i].name.replace(/^TYPE_/, "");

			this.arSavedFields[fName] = {};

			this.arSavedFields[fName].TYPE = BX.crmPaySys.formObj["TYPE_"+fName].value;
			switch (this.arSavedFields[fName].TYPE)
			{
				case 'FILE':
					var fieldPreview = BX(fName+'_preview_img');
					if (fieldPreview)
					{
						this.arSavedFields[fName].SRC    = fieldPreview.getAttribute('src');
						this.arSavedFields[fName].HEIGHT = fieldPreview.getAttribute('height');
						this.arSavedFields[fName].WIDTH  = fieldPreview.getAttribute('width');
					}
					break;
				case 'SELECT':
					var value1 = BX("VALUE1_"+fName);
					var options = {};
					for (var index = 0; index < value1.options.length; index++)
						options[value1.options[index].value] = value1.options[index].text;
					this.arSavedFields[fName].VALUE1 = value1.value;
					this.arSavedFields[fName].OPTIONS = options;
					break;
				case 'CHECKBOX':
					var value1 = BX("VALUE1_"+fName);
					if (value1.checked)
						this.arSavedFields[fName].VALUE1 = 'Y';
					break;
				default:
					var value1 = BX("VALUE1_"+fName);
					var value2 = BX("VALUE2_"+fName);
					if (value1)
						this.arSavedFields[fName].VALUE1 = value1.value;
					if (value2)
						this.arSavedFields[fName].VALUE2 = value2.value;
			}
		}
	},

	restoreFields: function()
	{
		if (this.arSavedFields)
		{
			for (var fName in this.arSavedFields)
			{
				var type = BX("TYPE_"+fName);

				if (type)
					type.value = this.arSavedFields[fName]["TYPE"]

				switch (type.value)
				{
					case 'FILE':
						var fieldPreview = BX(fName+'_preview_img');
						if (fieldPreview && this.arSavedFields[fName] && this.arSavedFields[fName].SRC)
						{
							fieldPreview.setAttribute('src', this.arSavedFields[fName].SRC);
							fieldPreview.setAttribute('height', this.arSavedFields[fName].HEIGHT);
							fieldPreview.setAttribute('width', this.arSavedFields[fName].WIDTH);
						}
						else
						{
							BX(fName + '_preview').style.display = "none";
						}
						break;
					case 'SELECT':
						var value1 = BX("VALUE1_"+fName);
						if (this.arSavedFields[fieldId])
						{
							BX.crmPSPropType.insertOptions(value1, this.arSavedFields[fName].OPTIONS);
							value1.value = this.arSavedFields[fName].VALUE1;
						}
						else
						{
							value1.value = 0;
						}
						break;
					case 'CHECKBOX':
						var value1 = BX("VALUE1_"+fName);
						if (this.arSavedFields[fName].VALUE1 == 'Y')
							value1.checked = true;
						break;
					default:
						var value1 = BX("VALUE1_"+fName);
						var value2 = BX("VALUE2_"+fName);
						value1.value = this.arSavedFields[fName]["VALUE1"];
						if (value2)
							value2.value = this.arSavedFields[fName]["VALUE2"];
				}
			}
		}
	},

	insertFields: function(actId, arFieldsByGroups)
	{
		/*this.saveFields();*/
		this.removeFields();

		var tabs = BX('bx-tabs');
		if (tabs)
		{
			var tab = BX.findChildByClassName(tabs, 'bx-tab-indent', true);
			var lastTab = tab.nextElementSibling;
			for (groupId in arFieldsByGroups)
			{
				var td = BX.create('td', {
					attrs : {
						id : 'tab_cont_'+groupId,
						className : 'bx-tab-container',
						onclick : 'BX.crmPSPropType.SelectTab(\''+groupId+'\');',
						onmouseover : 'BX.crmPSPropType.HoverTab(\''+groupId+'\', true);',
						onmouseout : 'BX.crmPSPropType.HoverTab(\''+groupId+'\', false);'
					}
				});

				var table = BX.create('table', {attrs : {'cellspacing' : '0'}, children : [
						BX.create('tr', {children : [
								BX.create('td', {attrs : {className : 'bx-tab-left', id : 'tab_left_'+groupId}, children : [BX.create('div', {attrs : {className : 'empty'}})]}),
								BX.create('td', {text : this.groups[groupId].NAME, attrs : {className : 'bx-tab', id : 'tab_'+groupId}}),
								BX.create('td', {attrs : {className : 'bx-tab-right', id : 'tab_right_'+groupId}, children : [BX.create('div', {attrs : {className : 'empty'}})]})
							]})
					]});

				td.appendChild(table);

				tab.parentNode.insertBefore(td, lastTab);
			}
		}

		var nextSibling = tabs.nextElementSibling;
		var container = BX.findChild(nextSibling, {tag : 'td'}, true);

		for (var groupId in arFieldsByGroups)
		{
			i = 0;
			var fieldInGroup = 3;

			var wrapper = BX.create('div', {attrs : {id : 'inner_tab_'+groupId, className : 'bx-edit-tab-inner'}, children : [BX.create('div', {attrs : {className : 'bx-edit-table'}, children : [BX.create('table', {attrs : {className : 'bx-edit-table', id : groupId+'_edit_table'}})]})]});
			wrapper.style.display = 'none';
			container.appendChild(wrapper);

			table = BX(groupId+'_edit_table');

			var arFields = arFieldsByGroups[groupId];
			for (var fieldId in arFields)
			{
				if (fieldId === 'USER_COLUMNS')
					continue;

				i++;
				if (i % fieldInGroup === 0 && (fieldId.indexOf('BILLUA_COLUMN_SUM_') >= 0 || fieldId.indexOf('BILLUA_COLUMN_PRICE_') >= 0))
					fieldInGroup = 4;
				else
					fieldInGroup = 3;

				var row = this.makeRow(fieldId, arFields[fieldId]);
				table.appendChild(row);

				if (groupId === 'COLUMN_SETTINGS' && i % fieldInGroup === 0)
				{
					table.appendChild(this.makeEmptyRow());
					i = 0;
				}

				var typeSelector = BX("TYPE_"+fieldId);

				if (!typeSelector)
					continue;

				if (this.arSavedFields[fieldId] && this.arSavedFields[fieldId].TYPE)
					typeSelector.value = this.arSavedFields[fieldId].TYPE;
				else
					typeSelector.value = (arFields[fieldId].TYPE == 'VALUE') ? '' : arFields[fieldId].TYPE;

				BX.crmPSPropType.replaceValueField(typeSelector);

				if (BX.crmPaySys.simpleMode)
				{
					if (typeSelector.value != '' && typeSelector.value != 'SELECT' && typeSelector.value != 'FILE' && typeSelector.value != 'CHECKBOX' && typeSelector.value != 'USER_COLUMN_LIST')
						row.style.display = 'none';

					if (this.hiddenFieldList.indexOf(fieldId) !== -1)
						row.style.display = 'none';

					typeSelector.style.display = 'none';
				}
				else if(typeSelector && typeSelector.value != 'SELECT' && typeSelector.value != 'FILE' && typeSelector.value != 'CHECKBOX' && typeSelector.value != 'USER_COLUMN_LIST')
				{
					typeSelector.style.marginBottom = '5px';
					typeSelector.parentNode.insertBefore(BX.create('br'), typeSelector.nextElementSibling);
				}

				switch (typeSelector.value)
				{
					case 'FILE':
						var fieldPreview = BX(fieldId+'_preview_img');
						if (fieldPreview && this.arSavedFields[fieldId] && this.arSavedFields[fieldId].SRC)
						{
							fieldPreview.setAttribute('src', this.arSavedFields[fieldId].SRC);
							fieldPreview.setAttribute('height', this.arSavedFields[fieldId].HEIGHT);
							fieldPreview.setAttribute('width', this.arSavedFields[fieldId].WIDTH);
						}
						else
						{
							BX(fieldId + '_preview').style.display = "none";
						}
						break;
					case 'SELECT':
						var valueField = BX("VALUE1_"+fieldId);
						if (this.arSavedFields[fieldId])
						{
							BX.crmPSPropType.insertOptions(valueField, this.arSavedFields[fieldId].OPTIONS);
							valueField.value = this.arSavedFields[fieldId].VALUE1;
						}
						else
						{
							BX.crmPSPropType.insertOptions(valueField, arFields[fieldId].VALUE);
						}
						break;
					case 'CHECKBOX':
						var value1Field = BX("VALUE1_"+fieldId);
						if (this.arSavedFields[fieldId])
						{
							if (this.arSavedFields[fieldId].VALUE1 == 'Y')
								value1Field.checked = true;
						}
						else if (arFields[fieldId].VALUE == 'Y')
						{
							value1Field.checked = true;
						}
						break;
					case 'VALUE':
					case '':
						var valueField = BX("VALUE2_"+fieldId);
						if (valueField && valueField != '')
						{
							if (this.arSavedFields[fieldId] && this.arSavedFields[fieldId].VALUE2)
								valueField.value = this.arSavedFields[fieldId].VALUE2
							else if(arFields[fieldId].VALUE)
								valueField.value = arFields[fieldId].VALUE;
							else
								valueField.value = '';
						}
						break;
					default:
						var valueField = BX("VALUE1_"+fieldId);
						if (valueField && valueField != '')
						{
							valueField.value = this.arSavedFields[fieldId] && this.arSavedFields[fieldId].VALUE1
								? this.arSavedFields[fieldId].VALUE1
								: arFields[fieldId].VALUE;
						}
				}
			}
			if (groupId === 'COLUMN_SETTINGS')
			{
				td = BX.create('td', {attrs : {colspan : 2}});
				td.innerHTML = this.userPropsListTmpl;
				table.appendChild(BX.create('tr', {attrs : {id : 'ADD_USER_PROP', style : 'text-align: center;'}, children : [td]}));
			}
		}

		this.hideNoneSimpleRows(!BX.crmPaySys.simpleMode);
		BX.crmPSPropType.bindEvents();
	},

	makeRow: function(fieldId, arField)
	{
		var row = document.createElement("tr"),
			fieldName = document.createElement("td"),
			fieldValue = document.createElement("td");

		fieldName.className = "bx-field-name bx-padding bx-props-field-width";
		fieldName.title = BX.util.htmlspecialchars(arField.DESCR);
		fieldName.innerHTML = BX.util.htmlspecialchars(arField.NAME)+":";
		row.appendChild(fieldName);

		var valueHtml = '';
		switch (arField.TYPE)
		{
			case 'FILE':
				valueHtml = this.fileValuesTmpl.replace(/\#FIELD_ID\#/g, fieldId);
				break;
			case 'SELECT':
				valueHtml = this.selectValuesTmpl.replace(/\#FIELD_ID\#/g, fieldId);
				break;
			case 'CHECKBOX':
				valueHtml = this.checkboxValuesTmpl.replace(/\#FIELD_ID\#/g, fieldId);
				break;
			default:
				valueHtml = this.typeValuesTmpl.replace(/\#FIELD_ID\#/g, fieldId);
		}

		fieldValue.className = "bx-field-value";
		fieldValue.innerHTML = valueHtml;
		row.appendChild(fieldValue);
		return row;
	},

	makeEmptyRow: function ()
	{
		return BX.create('tr', {children : [BX.create('td', {attrs : {colspan : 2}})]});
	},

	removeFields: function()
	{
		var tabs = BX('bx-tabs');
		if (tabs)
		{
			var tab = BX.findChildByClassName(tabs, 'bx-tab-indent', true);
			tab = tab.nextElementSibling;
			while (tab.nextElementSibling)
			{
				var tmp = tab.nextElementSibling;
				BX.remove(tab);
				tab = tmp;
			}
		}

		var props = tabs.nextElementSibling;
		if (props)
		{
			var containers = BX.findChildrenByClassName(props, 'bx-edit-tab-inner', true);
			for (var i in containers)
			{
				if (containers.hasOwnProperty(i))
					BX.remove(containers[i]);
			}
		}

		BX.PopupMenu.destroy('user-props-list');
	},

	setFieldsList: function(list)
	{
		var actListObj = BX.crmPaySys.formObj["PS_ACTION_FIELDS_LIST"];

		if(actListObj)
			actListObj.value = list;
	},

	hideNoneSimpleRows: function(bShow)
	{
		var displayedElements = {};
		var parent = null;
		var bHide = !bShow;
		var parentElement = null;
		for (var i = 0, l = BX.crmPaySys.formObj.elements.length; i< l; i++)
		{
			if(!/^TYPE_/.test(BX.crmPaySys.formObj.elements[i].name))
				continue;

			var typeSelector = BX.crmPaySys.formObj[BX.crmPaySys.formObj.elements[i].name];

			if(!typeSelector || typeSelector.value == '' || typeSelector.value == 'SELECT' || typeSelector.value == 'FILE' || typeSelector.value == 'CHECKBOX' || typeSelector.value == 'USER_COLUMN_LIST')
			{
				var fieldName = BX.crmPaySys.formObj.elements[i].name;
				fieldName = fieldName.substr(5);
				if (this.hiddenFieldList.indexOf(fieldName) === -1)
				{
					parent = BX.findParent(typeSelector, {className : 'bx-edit-tab-inner'}, true);
					var id = parent.getAttribute('id');
					var entity = id.substring(10);
					displayedElements[entity] = true;

					continue;
				}
			}

			var propsTable = typeSelector.parentNode.parentNode;

			if(propsTable)
				propsTable.style.display = bHide ? 'none' : '';

			if (parentElement === null)
				parentElement = propsTable.parentNode;
		}

		var hideSelected = false,
			selected = false;
		var j;

		for (j in BX.crmPSPropType.aTabs)
		{
			var tab = BX('tab_cont_'+BX.crmPSPropType.aTabs[j]);
			if (!displayedElements.hasOwnProperty(BX.crmPSPropType.aTabs[j]))
			{
				tab.style.display = bHide ? 'none' : '';

				if (bHide && hideSelected === false)
					hideSelected = (BX.findChildByClassName(tab, 'bx-tab-selected', true) !== null);
			}
			else if (selected == false)
			{
				selected = (BX.findChildByClassName(tab, 'bx-tab-selected', true) !== null);
			}
		}

		if (hideSelected === true || selected == false)
		{
			for (j in displayedElements)
			{
				if (displayedElements.hasOwnProperty(j))
				{
					BX.crmPSPropType.ShowTab(j, true);
					BX.crmPSPropType.SelectTab(j);
					break;
				}
			}
		}

		if (!parentElement)
			return;

		var flag = true;
		parent = null;
		var children = parentElement.children;

		for (i = 0; i < children.length; i++)
		{
			var element = children[i];

			if (element.childElementCount === 1)
			{
				if (parent !== null && flag === true)
					parent.style.display = bHide ? 'none' : '';

				flag = true;
				parent = element;
			}
			else
			{
				if (flag === true)
					flag = (element.style.display === 'none') || bShow;
			}
		}
	},
	showNoneSimpleRows: function()
	{
		this.hideNoneSimpleRows(true);
	},
	addUserColumn : function (propId, propName)
	{
		var action = BX('ACTION_FILE').value;
		var parent = BX('ADD_USER_PROP');

		var columns = ['NAME', 'SORT', 'ACTIVE'];
		for (var i in columns)
		{
			var columnName = 'USER_COLUMNS['+propId+']['+columns[i]+']';
			if (BX('VALUE1_'+columnName) || BX('VALUE2_'+columnName))
			{
				alert(BX.message('CRM_PROP_ALREADY_EXIST'));
				return;
			}

			var value = '';
			if (columns[i] === 'NAME')
				value = propName;
			else if (columns[i] === 'SORT')
				value = 1000;

			this.arFields[action]['COLUMN_SETTINGS'][columnName] = {
				NAME : BX.message('CRM_COLUMN_'+columns[i])+(columns[i] === 'NAME' ? propName : ''),
				SORT : 1000,
				GROUP : 'COLUMN_SETTINGS',
				TYPE : (columns[i] === 'ACTIVE') ? 'CHECKBOX' : '',
				VALUE : value,
				DESCR : ''
			};

			var row = this.makeRow(columnName, this.arFields[action]['COLUMN_SETTINGS'][columnName]);
			parent.parentNode.insertBefore(row, parent);

			var typeSelector = BX('TYPE_'+columnName);
			BX.crmPSPropType.replaceValueField(typeSelector);
			if (BX.crmPaySys.simpleMode)
				typeSelector.style.display = 'none';

			if (!BX.crmPaySys.simpleMode && columns[i] !== 'ACTIVE')
			{
				typeSelector.style.marginBottom = '5px';
				typeSelector.parentNode.insertBefore(BX.create('br'), typeSelector.nextElementSibling);
			}
			if (columns[i] === 'ACTIVE')
				BX('VALUE1_'+columnName).checked = true;

			var valueField = BX("VALUE2_"+columnName);
			if (valueField && valueField != '')
				valueField.value = this.arFields[action]['COLUMN_SETTINGS'][columnName]['VALUE'];
		}
		parent.parentNode.insertBefore(this.makeEmptyRow(), parent);

		BX.crmPaySys.bindInputsChange();
		BX.crmPaySys.getTemplatePreview();
		BX.PopupMenu.destroy('user-props-list');
	}
};