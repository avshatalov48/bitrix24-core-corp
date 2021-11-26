var CrmFormEditor = function(params)
{

	this.init = function (params)
	{
		this.jsEventsManagerId = params.jsEventsManagerId;
		this.detailPageUrlTemplate = params.detailPageUrlTemplate;
		this.id = params.id;

		this.userBlockController = new CrmFormEditorUserBlockController(params.userBlocks);

		params.fields = params.fields || [];
		this.actionRequestUrl = params.actionRequestUrl;
		this.signedParamsString = params.signedParamsString;
		this.personType = params.personType;
		this.context = params.context;
		this.templates = params.templates;
		this.path = params.path;
		this.mess = params.mess;
		this.currency = params.currency;

		this.entityDictionary = params.entityDictionary;
		this.schemesDictionary = params.schemesDictionary;
		this.fieldsDictionary = params.fieldsDictionary;
		this.booleanFieldItems = params.booleanFieldItems;
		this.isFramePopup = params.isFramePopup;
		this.isItemWasAdded = params.isItemWasAdded;
		this.fields = [];

		this.fieldTemporaryData = {};

		/* init slider support  */
		this.initSlider();

		/* init helper */
		this.helper = new CrmFormEditorHelper();

		/* init drag&drop fields */
		this.dragdrop = new CrmFormEditorDragDrop(params.dragdrop);
		BX.addCustomEvent(this.dragdrop, 'onSort', BX.delegate(this.onSort, this));

		/* init existed fields */
		this.initExistedFields(params);

		/* init filter of field selector */
		this.popupFieldSettings = new CrmOrderFormEditPopupFieldSettings({
			caller: this,
			content: BX('CRM_ORDER_FORM_POPUP_SETTINGS'),
			container: BX('CRM_ORDER_FORM_POPUP_SETTINGS_CONTAINER')
		});


		/* init filter of field selector */
		this.fieldSelector = new CrmFormEditorFieldSelector({
			caller: this,
			context: BX('FIELD_SELECTOR')
		});

		/* init entity scheme */
		this.entityScheme = new CrmOrderFormEditEntityScheme({
			caller: this,
			context: BX('ENTITY_SCHEME_CONTAINER')
		});

		/* init dependencies */
		this.dependencies = new CrmFormEditorDependencies({
			caller: this,
			relations: params.relations,
			allRelations: params.allRelations,
			relationEntities: params.relationEntities
		});

		/* init preset fields */
		// this.presetFields = new CrmFormEditorFieldPreset({
		// 	caller: this,
		// 	fields: params.presetFields
		// });

		this.destination = new CrmFormEditorDestination({
			'caller': this,
			'container': BX('crm-orderform-edit-responsible')
		});

		this.adsForm = new CrmFormAdsForm({
			'caller': this,
			'container': BX('CRM_ORDERFORM_ADS_FORM')
		});

		/* first fields sorting */
		this.sortFields();

		/* init animation */
		this.initAnimation();

		/* init interface  */
		this.initInterface();

		/* init tooltips  */
		this.initToolTips();

		BX.addCustomEvent('SidePanel.Slider:onMessage', this.listenSliderEvents.bind(this));
	};

	this.initSlider = function()
	{
		if (!this.isFramePopup)
		{
			return;
		}

		if (this.isItemWasAdded)
		{
			this.slider.reloadList();
		}

		this.slider.bindClose(BX('CRM_ORDERFORM_EDIT_TO_LIST_BOTTOM'));
	};

	this.slider = {
		bindClose: function (element)
		{
			BX.bind(element, 'click', this.close);
		},
		close: function (e)
		{
			e.preventDefault();
			window.top.BX.SidePanel.Instance.close();
		},
		open: function (url)
		{
			window.top.BX.SidePanel.Instance.open(url);
		},
		reloadList: function (url)
		{
			window.top.BX.SidePanel.Instance.close();
			window.top.location.reload();
		}
	};

	this.redirectToUrl = function(url)
	{
		if (!this.isFramePopup)
		{
			window.location.href = url;
		}
		else
		{
			this.slider.open(url);
		}
	};

	this.initExistedFields = function(params)
	{
		if(!params.fields)
		{
			return;
		}

		params.fields.forEach(function(fieldParams)
		{
			var field = {
				node: BX(fieldParams.CODE),
				id: fieldParams.ID,
				name: fieldParams.CODE,
				name_id: fieldParams.NAME,
				type: fieldParams.TYPE,
				items: fieldParams.ITEMS || [],
				entity: fieldParams.ENTITY_NAME || '',
				getCaption: this.onGetFieldCaption
			};
			this.initField(field);
			this.initFieldSettings(field);

		}, this);
	};

	this.initInterface = function()
	{
		BX.bind(BX('IS_PAY'), 'click', function(){
			BX.toggleClass(BX('PAY_SYSTEM_CONT'), 'crm-orderform-display-none');
		});
	};

	this.showErrors = function(errors)
	{
		var node = BX('crm-orderform-error');

		if (BX.type.isDomNode(node))
		{
			var html = '';

			errors.forEach(function(error){
				html += '<div class="crm-entity-widget-content-error-text">' + error + '</div>';
			});

			node.innerHTML = html;
			BX.show(node);
			BX.scrollToNode(node);
		}
	};

	this.submitForm = function()
	{
		var fieldCodes = [];

		this.fields.forEach(function(field){
			if(!field.dict['caption'] || !field.dict['entity_name'])
			{
				return;
			}

			fieldCodes.push(field.name);
		}, this);

		var form = BX('crm_orderform_edit_form');
		var that = this;

		BX.addClass(BX('CRM_ORDERFORM_SUBMIT_BUTTON'), 'ui-btn-clock');
		BX.addClass(BX('CRM_ORDERFORM_SUBMIT_APPLY'), 'ui-btn-clock');

		BX.ajax.submitAjax(
			form,
			{
				url: this.actionRequestUrl,
				method: 'POST',
				dataType: 'json',
				data: {
					action: 'saveFormAjax',
					checkFields: fieldCodes,
					signedParamsString: this.signedParamsString
				},
				onsuccess: function(result){
					BX.removeClass(BX('CRM_ORDERFORM_SUBMIT_BUTTON'), 'ui-btn-clock');
					BX.removeClass(BX('CRM_ORDERFORM_SUBMIT_APPLY'), 'ui-btn-clock');

					that.entityScheme.submitButtonEnabled = true;

					if (!result || result.error)
					{
						if (result.error)
						{
							that.showErrors(result.errors);
						}

						return;
					}

					if (result.redirect)
					{
						document.location.reload();
					}
					else
					{
						window.top.BX.SidePanel.Instance.postMessage(
							window,
							'OrderForm::onSave',
							{
								properties: result.properties
							}
						);
						window.top.BX.SidePanel.Instance.close();
					}
				}
			}
		);
	};

	this.initAnimation = function()
	{
		var elementList = document.getElementsByClassName('.crm-orderform-edit-animate');
		elementList = BX.convert.nodeListToArray(elementList);
		elementList.forEach(function(element){
			//get element height and set as max-height
		}, this);
	};

	this.bindEditInline = function(node, className)
	{
		var nameContainerNode = node.querySelector('[data-bx-order-form-lbl-cont]');
		var captionNode = node.querySelector('[data-bx-order-form-lbl-caption]');
		var inputNode = node.querySelector('[data-bx-order-form-btn-caption]');
		var buttonEdit = node.querySelector('[data-bx-order-form-lbl-btn-edit]');
		var buttonApply = node.querySelector('[data-bx-order-form-lbl-btn-apply]');
		BX.bind(buttonEdit, 'click', function(){
			BX.addClass(nameContainerNode, className);
		});
		BX.bind(buttonApply, 'click', function(){
			captionNode.innerText = inputNode.value;
			BX.removeClass(nameContainerNode, className);
		});
	};

	this.sendActionRequest = function (action, requestData, callbackSuccess, callbackFailure)
	{
		callbackSuccess = callbackSuccess || null;
		callbackFailure = callbackFailure || null;
		requestData = requestData || {};

		requestData.action = action;
		requestData.form_id = null;
		requestData.signedParamsString = this.signedParamsString;
		requestData.sessid = BX.bitrix_sessid();

		BX.ajax({
			url: this.actionRequestUrl,
			method: 'POST',
			data: requestData,
			timeout: 30,
			dataType: 'json',
			processData: true,
			onsuccess: BX.proxy(function(data){
				data = data || {};
				if(data.error)
				{
					callbackFailure.apply(this, [data]);
				}
				else if(callbackSuccess)
				{
					callbackSuccess.apply(this, [data]);
				}
			}, this),
			onfailure: BX.proxy(function(){
				var data = {'error': true, 'text': ''};
				callbackFailure.apply(this, [data]);
			}, this)
		});
	};

	this.onGetFieldCaption = function()
	{
		var caption = '';

		var captionNode = document.querySelector('#' + this.name + ' [data-bx-order-form-lbl-caption]');

		if (BX.type.isDomNode(captionNode))
		{
			caption = captionNode.getAttribute('data-bx-order-form-lbl-caption');
		}

		return caption;
	};

	this.getFieldNameList = function()
	{
		var fieldNameList = [];
		this.fields.forEach(function(field){
			fieldNameList.push(field.name_id || field.name);
		});

		return fieldNameList;
	};

	this.fireChangeFieldListEvent = function ()
	{
		BX.onCustomEvent(this, 'change-field-list', [this.fields]);
	};
	this.fireFieldAddedEvent = function (field)
	{
		BX.onCustomEvent(this, 'field-added', [field]);
	};

	this.fireFieldChangeItemsEvent = function (field)
	{
		BX.onCustomEvent(field, 'change-field-items');
		BX.onCustomEvent(this, 'change-field-items', [this.fields, field]);
	};

	this.addFieldByCode = function (fieldCode)
	{
		var dataList = this.fieldsDictionary.filter(function(item){
			return item.name == fieldCode;
		});

		if(dataList[0])
		{
			this.addField(dataList[0]);
		}
	};

	this.addFieldByData = function (data)
	{
		var dataList = this.fieldsDictionary.filter(function(item){
			var found = true;

			for (var i in data)
			{
				if (data.hasOwnProperty(i) && data[i])
				{
					if (item[i] != data[i])
					{
						found = false;
						break;
					}
				}
			}

			return found;
		});

		if(dataList[0])
		{
			this.addField(dataList[0]);
		}
	};

	this.addField = function (params, doNotSort)
	{
		if(!params.duplicated && this.findField(params.name))
		{
			return;
		}

		var templateId = this.templates.field.replace('%type%', params.type);
		if(!this.helper.getTemplate(templateId))
		{
			templateId = this.templates.field.replace('%type%', 'string');
		}

		var isPhone = params.is_phone || params.entity_field_name === 'PHONE';
		var isEmail = params.is_email || params.entity_field_name === 'EMAIL';

		params.entity_field_name = params.entity_field_name || '';

		var isOrderField = params.entity_name === 'ORDER';
		var fieldNode = this.helper.appendNodeByTemplate(
			BX('FIELD_CONTAINER'),
			templateId,
			{
				id: params.id || '',
				code: params.name || '',
				type: params.type,
				name: params.code ? params.code : params.name,
				name_id: params.name ? params.name : params.code,
				caption: params.caption,
				placeholder: params.placeholder || '',
				multiple: params.multiple ? 'Y' : 'N',
				required: params.required ? 'Y' : 'N',
				sort: params.sort !== undefined ? params.sort : 1000,
				props_group_id: params.props_group_id || '0',
				value_type: '',
				value: params.value || '',
				items: '',
				settings_items: '',
				checked: params.checked || '',
				show_multiple: params.multiple ? 'multiple' : '',
				'~show_match_anchor': isOrderField ? 'style="display: none;"' : '',
				show_time: params.show_time || '',
				control_id: params.name ? params.name : this.helper.generateId(),
				url_display_style: params.entity_field_name.substring(0, 3) === 'UF_' ? 'initial' : 'none',
				preset_id: params.preset_id || 0,
				bank_detail: params.bank_detail || 'N',
				address: params.address || 'N',
				address_type: params.address_type || 0,
				user_props: params.user_props || !isOrderField ? 'Y' : 'N',
				is_location: params.is_location ? 'Y' : 'N',
				is_location4tax: params.is_location4tax ? 'Y' : 'N',
				is_profile_name: params.is_profile_name ? 'Y' : 'N',
				is_payer: params.is_payer ? 'Y' : 'N',
				is_email: isEmail ? 'Y' : 'N',
				is_phone: isPhone ? 'Y' : 'N',
				is_zip: params.is_zip ? 'Y' : 'N',
				is_address: params.is_address ? 'Y' : 'N',
				time: params.time ? 'Y' : 'N',
				entity_field_name: params.entity_field_name,
				entity_field_caption: isOrderField ? params.caption : params.original_caption,
				entity_name: params.entity_name,
				entity_caption: params.entity_caption
			}
		);

		if(!fieldNode)
		{
			return;
		}

		var field = {
			type: params.type,
			node: fieldNode,
			name: params.name,
			items: params.items || [],
			entity: params.entity_name,
			getCaption: this.onGetFieldCaption
		};

		if (field.type === 'file' && field.items.length === 0)
		{
			field.items = [{
				name: field.name,
				ID: '',
			}];
		}

		this.initField(field);
		this.addFieldItems(field);
		this.addFieldSettingsItems(field);
		this.initFieldSettings(field);
		this.fireFieldAddedEvent(field);

		if(!doNotSort)
		{
			this.sortFields();
			this.fireChangeFieldListEvent();
		}

		this.fieldSelector.adjustHeight();

		return field;
	};

	this.addFieldItems = function(field, clean)
	{
		clean = clean || false;
		if(field.items.length == 0 || !field.itemsContainer)
		{
			return;
		}

		if(clean)
		{
			field.itemsContainer.innerHTML = '';
		}

		var templateId = this.templates.field.replace('%type%', field.type);
		var itemTemplateId = templateId + '_item';

		if (!BX.type.isArray(field.items))
		{
			field.items = BX.util.array_values(field.items);
		}

		field.items.forEach(function(item, key){
			var updateMode = item.NAME !== undefined;
			var itemCode = updateMode ? item.VALUE : item.ID;
			var itemValue = updateMode ? item.NAME : item.VALUE;
			var values = BX.type.isArray(field.dict.value) ? field.dict.value : [field.dict.value];
			var checked = BX.util.in_array(itemCode, values);

			var fileSrc = BX.message('CRM_ORDERFORM_EDIT_TMPL_FILE_NOT_SELECTED');

			if (item.SRC && item.FILE_NAME)
			{
				fileSrc = '<a href="' + item.SRC + '" title="' + BX.message('CRM_ORDERFORM_EDIT_TMPL_FILE_DOWNLOAD')
					+ '" target="_blank">' + (item.FILE_NAME.length > 35 && ('...' + item.FILE_NAME.substr(-32)) || item.FILE_NAME)
					+ '</a>';
			}

			this.helper.appendNodeByTemplate(
				field.itemsContainer,
				itemTemplateId,
				{
					'name': field.name,
					'item_id': item.ID,
					'item_code': itemCode,
					'item_value': itemValue,
					'checked': checked ? 'checked' : '',
					'selected': checked ? 'selected' : '',
					'field_item_name': field.type + '_' + field.name + '_' + field.randomId + '[]',
					'field_item_id': field.type + '_' + item.ID + this.helper.generateId(),
					'~file_src': fileSrc,
					'list_order': field.dict.multiple ? '[' + key + ']' : '',
				}
			);
		}, this);
	};

	this.getSaleFieldType = function(type)
	{
		var saleType;

		switch (type)
		{
			case 'checkbox':
				saleType = 'Y/N';
				break;
			case 'radio':
			case 'list':
			case 'listCheckbox':
				saleType = 'ENUM';
				break;
			case 'date':
				saleType = 'DATE';
				break;
			case 'file':
				saleType = 'FILE';
				break;
			case 'location':
				saleType = 'LOCATION';
				break;
			case 'address':
				saleType = 'ADDRESS';
				break;
			case 'string':
			case 'text':
			default:
				saleType = 'STRING';
		}

		return saleType;
	};

	this.getCrmFieldType = function(property, currentType)
	{
		var crmType;

		switch (property.TYPE)
		{
			case 'STRING':
			case 'NUMBER':
				if (currentType === 'typed_string')
				{
					crmType = currentType;
				}
				else
				{
					crmType = 'string';
				}
				break;
			case 'Y/N':
				crmType = 'checkbox';
				break;
			case 'ENUM':
				if (property.MULTIELEMENT === 'Y')
				{
					crmType = property.MULTIPLE === 'Y' ? 'listCheckbox' : 'radio';
				}
				else
				{
					crmType = 'list';
				}

				break;
			case 'DATE':
				crmType = 'date';
				break;
			case 'FILE':
				crmType = 'file';
				break;
			case 'LOCATION':
				crmType = 'location';
				break;
			case 'ADDRESS':
				crmType = 'address';
				break;
			default:
				crmType = 'STRING';
		}

		return crmType;
	};

	this.getSaleVariants = function(items)
	{
		var variants = [];
		var sort = 100;

		for (var i in items)
		{
			if (items.hasOwnProperty(i))
			{
				variants.push({
					VALUE: items[i].ID,
					NAME: items[i].NAME,
					SORT: sort
				});
				sort += 100;
			}
		}

		return variants;
	};

	this.convertDataCrmToSale = function(field)
	{
		field.NAME = field.CAPTION;
		field.TYPE = this.getSaleFieldType(field.TYPE);

		if (field.TYPE === 'ENUM')
		{
			field.VARIANTS = this.getSaleVariants(field.ITEMS);
		}

		return field;
	};

	this.initField = function(field)
	{
		if(!field.node)
		{
			return;
		}

		var dictField = this.findDictionaryField(field.name);
		if(!dictField)
		{
			dictField = {
				code: field.name,
				type: field.type,
				required: false,
				multiple: false
			};
		}
		field.dict = dictField;

		field.randomId = this.helper.generateId();

		var nameInput = field.node.querySelector('[data-bx-order-form-btn-caption]');
		var editSliderButton = field.node.querySelector('[data-bx-order-form-btn-slider-edit]');
		var deleteButton = field.node.querySelector('[data-bx-order-form-btn-delete]');
		field.lblCaption = field.node.querySelector('[data-bx-order-form-lbl-caption]');

		field.itemsContainer = field.node.querySelector('[data-bx-order-form-field-display-cont]');

		var _this = this;
		BX.bind(nameInput, 'change', function(){
			field.lblCaption.innerText = nameInput.value;
			_this.fireChangeFieldListEvent();
		});
		BX.bind(editSliderButton, 'click', function(){
			var personTypeId = _this.personType || 0;
			var propertyId = field.id || field.dict.id || 0;

			var url = _this.path.property
				.replace('#person_type_id#', personTypeId)
				.replace('#property_id#', propertyId);
			var options = {
				cacheable: false,
				allowChangeHistory: false
			};

			if (!propertyId)
			{
				var data;

				if (_this.fieldTemporaryData[field.dict.name])
				{
					data = _this.fieldTemporaryData[field.dict.name];
				}
				else
				{
					var formData = BX.ajax.prepareForm(BX('crm_orderform_edit_form'));
					data = formData.data && formData.data.FIELD && formData.data.FIELD[field.dict.name];
					data = _this.convertDataCrmToSale(data);
					data.PERSON_TYPE_ID = personTypeId;
					data.CODE = field.dict.name;
					data.MATCHED = field.dict.entity_name !== 'ORDER' ? 'Y' : 'N';
				}

				if (BX.type.isNotEmptyObject(data))
				{
					options = {
						cacheable: false,
						allowChangeHistory: false,
						requestMethod: 'post',
						requestParams: BX.merge(data, {
							action: 'initialLoadFromRequest',
							sessid: BX.bitrix_sessid()
						})
					};
				}
			}

			window.top.BX.SidePanel.Instance.open(url, options);
		});
		BX.bind(deleteButton, 'click', function(){
			_this.deleteField(field);
		});

		this.dragdrop.addItem(field.node);
		this.fields.push(field);

		BX.addCustomEvent(field, 'change-field-items', BX.proxy(function(){
			var firstChild = null;
			if(field.type == 'product')
			{
				firstChild = field.itemsContainer.children[0];
			}
			field.itemsContainer.innerHTML = '';
			if(firstChild)
			{
				field.itemsContainer.appendChild(firstChild);
			}
			this.addFieldItems(field);
		}, this));

		BX.bind(field.node.querySelector('[data-crm-orderform-name-link]'), 'click', this.showFieldInFieldSelector.bind(this));
	};

	this.showFieldInFieldSelector = function(event)
	{
		var target = BX.getEventTarget(event);
		var name = target.getAttribute('data-crm-orderform-name-link');

		if (!name)
			return;

		var group = field = this.fieldSelector.getFieldNode(name);

		if (BX.type.isDomNode(group))
		{
			while (group = BX.findParent(group, {attrs: this.fieldSelector.attributeGroup}, BX('FIELD_SELECTOR')))
			{
				BX.addClass(group, this.fieldSelector.classGroupShow);
				BX.removeClass(group, this.fieldSelector.classGroupClose);
			}
		}

		if (BX.type.isDomNode(field))
		{
			this.fieldSelector.scrollIntoView(field);

			// ToDo highlighting
			// BX.addClass(field, 'crm-orderform-linked-highlight');
			// setTimeout(function()
			// {
			// 	BX.removeClass(field, 'crm-orderform-linked-highlight');
			// }, 2000);
		}
	};

	this.initFieldSettings = function(field)
	{
		if(!field.node)
		{
			return;
		}

		var typeNode = field.node.querySelector('[data-bx-order-form-field-type]');
		var multipleValueNode = field.node.querySelector('[data-bx-order-form-btn-multiple-value]');
		var multipleCheckboxNode = field.node.querySelector('[data-bx-order-form-btn-multiple]');
		var multipleNode = field.node.querySelector('[data-bx-order-form-btn-multiple-cont]');
		var multipleAddNode = field.node.querySelector('[data-bx-order-form-btn-add]');

		if(multipleNode && multipleValueNode && multipleCheckboxNode)
		{
			if(!field.dict.multiple)
			{
				this.helper.styleDisplay(multipleNode, false);
				multipleValueNode.value = 'N';
			}
			else
			{
				multipleCheckboxNode.checked = multipleValueNode.value == 'Y';

				if(field.dict.type == 'checkbox')
				{
					var clickHandler = function(){
						multipleValueNode.value = multipleCheckboxNode.checked ? 'Y' : 'N';
						typeNode.value = field.type = multipleCheckboxNode.checked ? 'checkbox' : 'radio';
						this.addFieldItems(field, true);
					};
					BX.bind(multipleCheckboxNode, 'click', BX.proxy(clickHandler, this));
					clickHandler.apply(this, []);
				}
				else
				{
					this.helper.styleDisplay(multipleAddNode, multipleCheckboxNode.checked, 'inline-block');
					BX.bind(multipleCheckboxNode, 'click', BX.proxy(function(){
						multipleValueNode.value = multipleCheckboxNode.checked ? 'Y' : 'N';
						this.helper.styleDisplay(multipleAddNode, multipleCheckboxNode.checked, 'inline-block');
					}, this));
				}
			}
		}

		var checkboxValueNodes = BX.convert.nodeListToArray(field.node.querySelectorAll('[data-bx-order-form-btn-checkbox-value]'));
		var checkboxNodes = BX.convert.nodeListToArray(field.node.querySelectorAll('[data-bx-order-form-btn-checkbox]'));

		if (checkboxValueNodes.length === checkboxValueNodes.length)
		{
			checkboxValueNodes.forEach(function(node, index){
				if (node.value === 'Y')
				{
					checkboxNodes[index].checked = true;
				}

				BX.bind(checkboxNodes[index], 'change', function(){
					node.value = checkboxNodes[index].checked ? 'Y' : 'N';
				});
			});
		}

		var fieldType = field.dict['type'] ? field.dict.type : field.type;
		var fieldInitMethod = 'initFieldType' + fieldType.substring(0,1).toUpperCase() + fieldType.substring(1);
		if(this[fieldInitMethod])
		{
			this[fieldInitMethod](field);
		}
	};

	this.addFieldSettingsItems = function(field)
	{
		var settingsItemsContainer = field.node.querySelector('[data-bx-crm-orderform-field-settings-items]');
		if(!settingsItemsContainer || field.items.length == 0 || !field.itemsContainer)
		{
			return;
		}


		var templateId = this.templates.field.replace('%type%', field.type);
		var itemSettingsTemplateId = templateId + '_settings_item';

		field.items.forEach(function(item){
			var isUpdateMode = item.NAME !== undefined;

			var itemId = isUpdateMode ? item.ID : "";
			var itemCode = isUpdateMode ? item.VALUE : item.ID;
			var itemValue = isUpdateMode ? item.VALUE : item.ID;
			var itemName = isUpdateMode ? item.NAME : item.VALUE;

			this.helper.appendNodeByTemplate(
				settingsItemsContainer,
				itemSettingsTemplateId,
				{
					'name': field.name,
					'item_id': itemId,
					'item_code': itemCode,
					'item_value': itemValue,
					'item_name': itemName,
					'field_item_name': field.type + '_' + field.name + '_' + field.randomId + '[]',
					'field_item_id': field.type + '_' + item.ID + this.helper.generateId()
				}
			);

		}, this);
	};

	this.initFieldTypeProduct = function(field)
	{
		field.productSelector = new CrmFormEditorProductSelector({
			jsEventsManagerId: this.jsEventsManagerId,
			context: field.node,
			caller: this,
			id: field.id,
			field: field
		});
	};

	this.initFieldTypeListCheckbox = function(field)
	{
		this.initFieldTypeList(field);
	};

	this.initFieldTypeCheckbox = function(field)
	{
		this.initFieldTypeList(field);
	};

	this.initFieldTypeRadio = function(field)
	{
		this.initFieldTypeList(field);
	};

	this.initFieldTypeList = function(field)
	{
		field.fieldListManager = new CrmFormEditorFieldListSettings({
			caller: this,
			context: field.node,
			type: field.type,
			field: field
		});
	};

	this.initFieldTypeSection = function(field)
	{
		this.bindEditInline(field.node, 'dynamic-field');
	};

	this.initFieldTypeString = function(field)
	{
		var stringTypeNode = field.node.querySelector('[data-bx-order-form-field-string-type]');
		var typeNode = field.node.querySelector('[data-bx-order-form-field-type]');
		if(stringTypeNode)
		{
			stringTypeNode.value = typeNode.value;
			BX.bind(stringTypeNode, 'bxchange', function(){
				typeNode.value = stringTypeNode.value || 'string';
			});
		}
	};

	this.initFieldTypeTyped_string = function(field)
	{
		this.initFieldTypeString(field);

		if(!field.dict['value_type'])
		{
			return;
		}

		var valueTypesNode = field.node.querySelector('[data-bx-order-form-field-string-value-types]');
		var valueTypeNode = field.node.querySelector('[data-bx-order-form-field-string-value-type]');
		if(valueTypesNode)
		{
			var currentItemId = valueTypeNode.value;
			var items = [];
			field.dict.value_type.forEach(function(item){
				if(!currentItemId)
				{
					currentItemId = item.ID;
				}
				items.push({
					caption: item.VALUE,
					value: item.ID,
					selected: (item.ID == currentItemId)
				});
			}, this);
			this.helper.fillDropDownControl(valueTypesNode, items);
			valueTypeNode.value = currentItemId;

			BX.bind(valueTypesNode, 'bxchange', function(){
				valueTypeNode.value = valueTypesNode.value || 'OTHER';
			});
		}
	};

	this.initFieldTypeDouble = function(field)
	{
		this.initFieldTypeString(field);
	};

	this.initFieldTypeInteger = function(field)
	{
		this.initFieldTypeString(field);
	};

	this.findDictionaryField = function(fieldName)
	{
		var fieldsList = this.fieldsDictionary.filter(function(field){
			return (field.name == fieldName);
		});

		if(fieldsList && fieldsList.length > 0)
		{
			return fieldsList[0];
		}

		return null;
	};

	this.findField = function(fieldName)
	{
		var fieldsList = this.fields.filter(function(field){
			return (field.name == fieldName);
		});

		if(fieldsList && fieldsList.length > 0)
		{
			return fieldsList[0];
		}

		return null;
	};

	this.findFieldByNode = function(node)
	{
		var fieldsList = this.fields.filter(function(field){
			return (field.node == node);
		});

		if(fieldsList && fieldsList.length > 0)
		{
			return fieldsList[0];
		}

		return null;
	};

	this.editField = function(field, startEdit)
	{
		this.popupFieldSettings.show(field.settingsContainer);
	};

	this.deleteField = function(field, notSortFields)
	{
		notSortFields = notSortFields || false;
		BX.onCustomEvent(this, 'delete-field', [field]);

		this.dragdrop.removeItem(field.node);
		BX.remove(field.node);

		var itemIndex = BX.util.array_search(field, this.fields);
		if(itemIndex > -1)
		{
			delete this.fields[itemIndex];
		}

		if (!notSortFields)
		{
			this.sortFields();
		}

		this.fireChangeFieldListEvent();

		this.fieldSelector.adjustHeight();
	};

	this.moveField = function(field, insertAfterField)
	{
		this.sortFields();
	};

	this.sortFields = function ()
	{
		this.fields.forEach(function(field){
			field.sortValue = BX.convert.nodeListToArray(field.node.parentNode.children).indexOf(field.node);
		});

		this.fields.sort(function(fieldA, fieldB){
			return fieldA.sortValue > fieldB.sortValue ? 1 : -1;
		});

		var weight = 10;
		this.fields.forEach(function(field){
			field.sort = (field.sortValue + 1) * weight;

			var sortInput = field.node.querySelector('[data-bx-order-form-field-sort]');
			if(sortInput)
			{
				sortInput.value = field.sort;
			}
		});
	};

	this.onSort = function(dragElement, catcherObj)
	{
		//catcherObj.appendChild(dragElement);
		this.sortFields();
	};

	this.initToolTips = function(nodeList)
	{
		this.popupTooltip = {};
		nodeList = nodeList || BX.convert.nodeListToArray(document.body.querySelectorAll(".crm-orderform-context-help"));
		var _this = this;
		for (var i = 0; i < nodeList.length; i++)
		{
			if (nodeList[i].getAttribute('context-help') == 'y')
				continue;

			nodeList[i].setAttribute('data-id', i);
			nodeList[i].setAttribute('context-help', 'y');
			BX.bind(nodeList[i], 'mouseover', function(){
				var id = this.getAttribute('data-id');
				var text = this.getAttribute('data-text');

				_this.showTooltip(id, this, text);
			});
			BX.bind(nodeList[i], 'mouseout', function(){
				var id = this.getAttribute('data-id');
				_this.hideTooltip(id);
			});
		}
	};

	this.showTooltip = function(id, bind, text)
	{
		if (this.popupTooltip[id])
			this.popupTooltip[id].close();

		this.popupTooltip[id] = new BX.PopupWindow('bx-crm-orderform-edit-tooltip', bind, {
			lightShadow: true,
			autoHide: false,
			darkMode: true,
			offsetLeft: 0,
			offsetTop: 2,
			bindOptions: {position: "top"},
			zIndex: 200,
			events : {
				onPopupClose : function() {this.destroy()}
			},
			content : BX.create("div", { attrs : { style : "padding-right: 5px; width: 250px;" }, html: text})
		});
		this.popupTooltip[id].setAngle({offset:13, position: 'bottom'});
		this.popupTooltip[id].show();

		return true;
	};

	this.hideTooltip = function(id)
	{
		this.popupTooltip[id].close();
		this.popupTooltip[id] = null;
	};

	this.listenSliderEvents = function(event)
	{
		if (event.getEventId() === 'OrderPropertyEdit::onSave')
		{
			this.onPropertyEditSave(event);
		}

		if (event.getEventId() === 'OrderPropertyEdit::onApply')
		{
			this.onPropertyEditApply(event);
			this.onPropertyEditSave(event);
		}
	};

	this.onPropertyEditSave = function(event)
	{
		var data = event.getData();
		var property = data.property;

		if (property)
		{
			var findCode = property.MATCH_CODE || property.CODE;
			var field = this.findField(findCode);

			if (!field)
			{
				findCode = 'ORDER_' + findCode;
				field = this.findField(findCode);
			}

			if (!field)
			{
				return;
			}

			// remove current field
			this.deleteField(field, true);

			// add to dictionary from property
			for (var i = 0; i < this.fieldsDictionary.length; i++)
			{
				if (this.fieldsDictionary[i].name === findCode)
				{
					this.fieldsDictionary[i] = BX.merge(this.fieldsDictionary[i], {
						id: property.ID,
						type: this.getCrmFieldType(property, this.fieldsDictionary[i].type),
						caption: property.NAME,
						placeholder: property.DESCRIPTION,
						sort: field.sort,
						code: property.CODE,
						props_group_id: property.PROPS_GROUP_ID,
						is_address: property.IS_ADDRESS === 'Y',
						is_email: property.IS_EMAIL === 'Y',
						is_location: property.IS_LOCATION === 'Y',
						is_location4tax: property.IS_LOCATION4TAX === 'Y',
						is_payer: property.IS_PAYER === 'Y',
						is_phone: property.IS_PHONE === 'Y',
						is_profile_name: property.IS_PROFILE_NAME === 'Y',
						is_zip: property.IS_ZIP === 'Y',
						multiple: property.MULTIPLE === 'Y',
						required: property.REQUIRED === 'Y',
						user_props: property.USER_PROPS === 'Y'
					});
					this.fieldsDictionary[i].value = property.DEFAULT_VALUE;

					if (property.TYPE === 'FILE')
					{
						this.fieldsDictionary[i].items = property.DEFAULT_VALUE || [];
					}
					else
					{
						this.fieldsDictionary[i].items = property.VARIANTS || [];
					}

					if (this.fieldsDictionary[i].type === 'checkbox')
					{
						this.fieldsDictionary[i].checked = property.DEFAULT_VALUE === 'Y' ? 'checked' : '';
					}

					break;
				}
			}

			// render it
			this.addField(this.fieldsDictionary[i]);
			this.fieldSelector.onCallerFieldAdd(field);

			if (field.entity === 'ORDER')
			{
				this.fieldSelector.changeFieldText(field.name, property.NAME);
			}
		}
	};

	this.onPropertyEditApply = function(event)
	{
		var data = event.getData();
		var property = data.property;

		if (property)
		{
			this.fieldTemporaryData[property.CODE] = BX.clone(property);
		}
	};

	this.init(params);
};


function CrmOrderFormEditEntityScheme(params)
{
	this.caller = params.caller;
	this.context = params.context;
	this.submitButtonEnabled = true;
	this.currentEntityPayerType = null;

	this.init();
}
CrmOrderFormEditEntityScheme.prototype =
	{
		init: function ()
		{
			this.helper = BX.CrmFormEditorHelper;
			this.descriptionNode = this.context.querySelector('[data-bx-orderform-edit-scheme-desc]');
			this.descriptionTopNode = BX('ENTITY_SCHEMES_TOP_DESCRIPTION');
			this.radioList = BX.convert.nodeListToArray(this.context.querySelectorAll('[data-bx-order-form-entity-scheme-value]'));
			this.dealCategoryNode = this.context.querySelector('[data-bx-order-form-entity-scheme-deal-cat]');

			var valueNodes = document.getElementsByName('ENTITY_SCHEME');
			this.valueNode = valueNodes[0];

			this.submitButtonNode = BX('CRM_ORDERFORM_SUBMIT_BUTTON');
			BX.bind(this.submitButtonNode, 'click', BX.proxy(this.checkCreateFields, this));
			BX.bind(BX('CRM_ORDERFORM_SUBMIT_APPLY'), 'click', BX.proxy(this.checkCreateFields, this));

			BX.addCustomEvent(this.caller, 'change-field-list', BX.proxy(this.actualizeInvoiceSettings, this));
			this.actualizeInvoiceSettings();
		},

		isInvoiceChecked: function ()
		{
			return false;
		},

		addRequiredFields: function ()
		{
			var fields = this.popupConfirmCreateFields.contentContainer.querySelectorAll('[data-form-required-field]'),
				fieldCode;

			fields = BX.convert.nodeListToArray(fields);

			for (var i = 0; i < fields.length; i++)
			{
				fieldCode = fields[i].getAttribute('data-form-required-field');
				this.caller.addFieldByCode(fieldCode);

				BX.addClass(BX(fieldCode), 'crm-orderform-field-highlight');
			}

			setTimeout(function(){
				for (var i = 0; i < fields.length; i++)
				{
					fieldCode = fields[i].getAttribute('data-form-required-field');
					BX.removeClass(BX(fieldCode), 'crm-orderform-field-highlight');
				}
			}, 2000);
		},

		showPopupCreateProductField: function ()
		{
			if(!this.popupCreateProductField)
			{
				this.popupCreateProductField = BX.PopupWindowManager.create(
					'crm_orderform_edit_create_prod_field',
					null,
					{
						content: this.caller.mess.dlgInvoiceEmptyProduct,
						titleBar: this.caller.mess.dlgInvoiceEmptyProductTitle,
						autoHide: false,
						lightShadow: true,
						closeByEsc: true,
						overlay: {backgroundColor: 'black', opacity: 500}
					}
				);
				this.popupCreateProductField.setButtons([
					new BX.PopupWindowButton({
						text: this.caller.mess.dlgContinue,
						className: 'orderform-small-button-accept',
						events: {
							click: BX.proxy(function(){
								this.popupCreateProductField.close();
							}, this)
						}
					})
				]);
			}

			this.popupCreateProductField.show();
			this.popupCreateProductField.resizeOverlay();
		},

		showPopupCreateFields: function ()
		{
			if(!this.popupConfirmCreateFields)
			{
				this.popupConfirmCreateFields = BX.PopupWindowManager.create(
					'crm_orderform_edit_create_fields',
					null,
					{
						titleBar: this.caller.mess.dlgTitleFieldCreate,
						content: BX('CRM_ORDER_FORM_POPUP_CONFIRM_FIELD_CREATE'),
						autoHide: false,
						lightShadow: true,
						closeByEsc: true,
						overlay: {backgroundColor: 'black', opacity: 500},
						onPopupClose: BX.proxy(this.onClose)
					}
				);
				this.popupConfirmCreateFields.setButtons([
					new BX.PopupWindowButton({
						text: this.caller.mess.dlgCreateFields,
						className: 'orderform-small-button-accept',
						events: {
							click: BX.proxy(function(){
								this.submitButtonEnabled = true;
								BX.removeClass(this.submitButtonNode, 'crm-orderform-submit-button-loader');
								this.popupConfirmCreateFields.close();

								this.addRequiredFields();
							}, this)
						}
					}),
					new BX.PopupWindowButton({
						text: this.caller.mess.dlgCancel,
						className: 'orderform-small-button-cancel',
						events: {
							click: BX.proxy(function(){
								this.submitButtonEnabled = true;
								BX.removeClass(this.submitButtonNode, 'crm-orderform-submit-button-loader');
								this.popupConfirmCreateFields.close();
							}, this)
						}
					})
				]);
			}

			this.popupConfirmCreateFields.show();
		},

		checkCreateFields: function (event)
		{
			if(!this.submitButtonEnabled)
			{
				BX.PreventDefault(event);
				return false;
			}

			var currentScheme = this.getCurrent();

			var hasProductFieldNotEmpty = false;
			var fieldCodes = [];
			var fieldCodeToCaption = {};
			this.caller.fields.forEach(function(field){
				if(field.type == 'product' && field.items.length > 0)
				{
					hasProductFieldNotEmpty = true;
				}

				if(!field.dict['caption'] || !field.dict['entity_name'])
				{
					return;
				}

				fieldCodes.push(field.name);
				fieldCodeToCaption[field.name] = field.dict.caption;
			}, this);


			if(this.isInvoiceChecked() && !hasProductFieldNotEmpty)
			{
				BX.PreventDefault(event);
				this.showPopupCreateProductField();
				return false;
			}

			BX.PreventDefault(event);
			this.submitButtonEnabled = false;
			BX.addClass(this.submitButtonNode, 'crm-orderform-submit-button-loader');

			var popupFieldListCont = BX('CRM_ORDER_FORM_POPUP_CONFIRM_FIELD_CREATE_LIST');

			this.caller.sendActionRequest(
				'checkFieldsAjax',
				{
					schemeId: currentScheme.ID,
					fieldCodes: fieldCodes
				},
				BX.proxy(function(data){
					data = data || {};

					var fieldSyncCaptionList = [];
					var requiredFieldCodes = data.requiredFieldCodes || [];

					if (requiredFieldCodes.length === 0)
					{
						this.caller.submitForm();
						return;
					}

					var _this = this;

					requiredFieldCodes.forEach(function(requiredFieldCode){
						var found = _this.caller.findDictionaryField(requiredFieldCode);

						if (found)
						{
							fieldSyncCaptionList.push(
								'<li class="crm-p-s-f-item" data-form-required-field="' + found.name + '">'
								+ BX.util.htmlspecialchars(found.caption)
								+ ' (' + BX.util.htmlspecialchars(_this.caller.entityDictionary[found.entity_name])
								+ ')</li>'
							);
						}
					});

					popupFieldListCont.innerHTML = fieldSyncCaptionList.join('');
					this.showPopupCreateFields();
				}, this),
				BX.proxy(function()
				{
					popupFieldListCont.innerHTML = fieldCaptionList.join('');
					this.showPopupCreateFields();
				}, this)
			);

			return false;
		},

		setCurrentId: function (value)
		{
			if(this.valueNode)
			{
				this.valueNode.value = value;
			}
		},

		isUsingRequisites: function ()
		{
			return this.hasFieldsRequisite;
		},

		getCurrentPayerEntityType: function ()
		{
			return this.currentEntityPayerType;
		},

		setCurrentPayerEntityType: function (value)
		{
			var changed = this.currentEntityPayerType !== value;

			this.currentEntityPayerType = value;

			if (changed)
			{
				this.onChange();
			}
		},

		_currentPayerEntityType: function (value)
		{
			value = value || null;
			this.invoice.payerTypeNodes.forEach(function(node){
				if(value)
				{
					node.checked = value == node.value;
				}
				else if(node.checked)
				{
					value = node.value;
				}
			}, this);

			return value;
		},

		getCurrent: function ()
		{
			var section = 'BY_NON_INVOICE';
			var schemeId = null;
			this.radioList.forEach(function(radioNode){
				if(!schemeId || radioNode.checked)
				{
					schemeId = radioNode.value;
				}
			});
			return this.caller.schemesDictionary[section][schemeId];
		},

		onChange: function ()
		{
			var scheme = this.getCurrent();
			this.setCurrentId(scheme.ID);
			this.actualizeInvoiceSettings(scheme);
			this.actualizeDealCategory(scheme);

			BX.onCustomEvent(this.caller, 'change-entity-scheme', [scheme]);
		},

		getCurrentWillCreatedEntities: function()
		{
			var scheme = this.getCurrent();
			var entityTypes = [];
			var currentPayerEntityTypeName = this.getCurrentPayerEntityType();
			var hasRequisites = this.isUsingRequisites();

			scheme.ENTITIES.forEach(function(entityTypeName){
				var isPayer = currentPayerEntityTypeName === entityTypeName;
				var isContact = entityTypeName === 'CONTACT';
				var isCompany = entityTypeName === 'COMPANY';
				var isRequisites = entityTypeName === 'REQUISITE';

				if(isContact && !this.hasFieldsContact && !isPayer)
				{
					return;
				}
				if(isCompany && !this.hasFieldsCompany && !isPayer)
				{
					return;
				}
				if(isRequisites && !hasRequisites)
				{
					return;
				}

				if(this.caller.entityDictionary[entityTypeName])
				{
					entityTypes.push(entityTypeName);
				}
			}, this);

			return entityTypes;
		},

		actualizeDealCategory: function()
		{
			var scheme = this.getCurrent();
			var isAdd = BX.util.in_array('DEAL', scheme.ENTITIES);
			this.helper.changeClass(this.dealCategoryNode, 'crm-orderform-edit-animate-show-120', isAdd);
			//this.helper.styleDisplay(this.dealCategoryNode, BX.util.in_array('DEAL', scheme.ENTITIES));
		},

		actualizeTextWillCreatedEntities: function()
		{
			var entityTypeCaptions = [];
			this.getCurrentWillCreatedEntities().forEach(function(entityTypeName){
				entityTypeCaptions.push(this.caller.entityDictionary[entityTypeName]);
			}, this);

			var scheme = this.getCurrent();
			var description = entityTypeCaptions.length > 0 ? entityTypeCaptions.join(', ') : scheme['DESCRIPTION'];
			if(this.descriptionNode)
			{
				this.descriptionNode.innerText = description;
			}
			if(this.descriptionTopNode)
			{
				this.descriptionTopNode.innerText = description;
			}
		},

		actualizePayer: function()
		{
			// actualize payer
			var hasFieldsContact = false, hasFieldsCompany = false, hasFieldsRequisite = false;

			this.caller.fields.forEach(function(field){
				if(field.dict['entity_name'])
				{
					if(field.dict.entity_name === 'CONTACT') hasFieldsContact = true;
					if(field.dict.entity_name === 'COMPANY') hasFieldsCompany = true;
					if(field.dict.entity_name === 'REQUISITE') hasFieldsRequisite = true;
				}
			}, this);

			this.hasFieldsCompany = hasFieldsCompany;
			this.hasFieldsContact = hasFieldsContact;
			this.hasFieldsRequisite = hasFieldsRequisite;

			if(hasFieldsContact)
			{
				this.setCurrentPayerEntityType('CONTACT');
			}
			else if(hasFieldsCompany)
			{
				this.setCurrentPayerEntityType('COMPANY');
			}
			else
			{
				this.setCurrentPayerEntityType(null);
			}
		},

		actualizeInvoiceSettings: function()
		{
			// actualize payer
			this.actualizePayer();

			// actualize description text of entities that will created
			this.actualizeTextWillCreatedEntities();
		}
	};


function CrmOrderFormEditFormButton(params)
{
	this.caller = params.caller;
	this.context = params.context;
	this.helper = BX.CrmFormEditorHelper;

	this.buttonNode = BX('FORM_BUTTON');
	this.buttonInputNode = BX('FORM_BUTTON_INPUT');
	this.buttonTextErrorNode = BX('FORM_BUTTON_INPUT_ERROR');
	this.buttonColorBgNode = BX('BUTTON_COLOR_BG');
	this.buttonColorFontNode = BX('BUTTON_COLOR_FONT');
	this.popupButtonNameNode = BX('CRM_ORDER_FORM_POPUP_BUTTON_NAME');

	this.init();
}
CrmOrderFormEditFormButton.prototype =
	{
		init: function()
		{
			BX.bind(this.buttonNode.parentNode, 'click', BX.proxy(this.editButton, this));
			BX.bind(this.buttonInputNode, 'bxchange', BX.proxy(this.onButtonCaptionChange, this));
			this.initColorPicker();
		},

		editButton: function()
		{
			this.caller.popupFieldSettings.show(this.popupButtonNameNode, BX.proxy(this.onEditButtonClosePopup, this));
		},

		onEditButtonClosePopup: function()
		{
			var isFilled = !!BX.util.trim(this.buttonInputNode.value);
			this.helper.styleDisplay(this.buttonTextErrorNode, !isFilled, 'block');

			return isFilled;
		},

		onButtonCaptionChange: function()
		{
			this.buttonNode.innerText = this.buttonInputNode.value;
		},

		updateButtonColors: function()
		{
			this.buttonNode.style.background = this.buttonColorBgNode.value;
			this.buttonNode.style.color = this.buttonColorFontNode.value;
		},

		initColorPicker: function()
		{
			this.picker = new window.BXColorPicker({'id': "picker", 'name': 'picker'});
			this.picker.Create();

			var _this = this;

			var clickHandler = function ()
			{
				var element = this;
				element.parentNode.appendChild(_this.picker.pCont);
				_this.picker.oPar.OnSelect = BX.proxy(function (color)
				{
					if(!color)
						color = '';

					element.value = color;
					var colorBox = BX.nextSibling(element);
					if(colorBox)
					{
						colorBox.style.background = color;
					}
					BX.fireEvent(element, 'change');
				}, _this);

				_this.picker.pCont.style.display = '';
				_this.picker.Close();
				_this.picker.Open(element);
				_this.picker.pCont.style.display = 'none';

			};

			var changeHandler = function()
			{
				var colorBox = BX.nextSibling(this);
				if (colorBox)
				{
					colorBox.style.background = this.value;
					_this.updateButtonColors();
				}
			};


			var inputList = this.context.querySelectorAll('[data-order-form-color-picker]');
			inputList = BX.convert.nodeListToArray(inputList);
			for(var i in inputList)
			{
				var inputCtrl = inputList[i];
				var colorBox = BX.nextSibling(inputCtrl);

				BX.bind(colorBox, 'click', BX.proxy(clickHandler, inputCtrl));
				BX.bind(inputCtrl, 'click', clickHandler);
				BX.bind(inputCtrl, "focus", clickHandler);

				BX.bind(inputCtrl, "bxchange", BX.delegate(changeHandler, inputCtrl));
				BX.fireEvent(inputCtrl, 'change');
			}
		}

	};


function CrmOrderFormEditPopupFieldSettings(params)
{
	this.caller = params.caller;
	this.popup = null;
	this.popupContent = params.content;
	this.popupContainer = params.container;
	this.popupCloseHandler = null;

	this.fieldContainer = null;
}
CrmOrderFormEditPopupFieldSettings.prototype =
	{
		setAutoHide: function (doAutoHide)
		{
			if(this.popup)
			{
				this.popup.params.autoHide = doAutoHide;
			}
		},

		show: function (fieldContainer, popupCloseHandler)
		{
			this.popupCloseHandler = popupCloseHandler || null;

			this.initPopup();
			this.moveSettingsBack();

			this.fieldContainer = fieldContainer;
			this.moveSettingsOn();
			this.popup.show();
		},

		moveSettingsOn: function ()
		{
			if(!this.fieldContainer)
			{
				return;
			}

			this.replaceSettingsNode(this.fieldContainer, this.popupContainer);
		},

		moveSettingsBack: function ()
		{
			if(!this.fieldContainer)
			{
				return;
			}

			this.replaceSettingsNode(this.popupContainer, this.fieldContainer);
			this.fieldContainer = null;
		},

		_popupClose: function (event)
		{
			if(this.popupCloseHandler && !this.popupCloseHandler.apply(this, []))
			{
				return;
			}

			this.onClose();
			BX.PopupWindow.prototype.close.apply(this.popup, [event]);
		},

		onClose: function ()
		{
			/*
			if(this.popup.isShown())
			{
				this.popup.close();
			}
			*/

			this.moveSettingsBack();
			this.popupCloseHandler = null;
		},

		replaceSettingsNode: function (source, destination)
		{
			if(!source || !source.firstElementChild)
			{
				return;
			}

			destination.appendChild(source.firstElementChild);
		},

		initPopup: function ()
		{
			if(this.popup)
			{
				return;
			}

			var popup = BX.PopupWindowManager.create(
				'crm_orderform_edit_field_settings',
				null,
				{
					content: this.popupContent,
					autoHide: false,
					lightShadow: true,
					closeByEsc: true,
					overlay: {backgroundColor: 'black', opacity: 500},
					zIndex: -400,
					titleBar: this.caller.mess.dlgTitle,
					closeIcon: true,
					//onPopupClose: BX.proxy(this.onClose)
				}
			);
			popup.setButtons([
				new BX.PopupWindowButton({
					text: this.caller.mess.dlgClose,
					className: 'orderform-small-button-accept crm-orderform-edit-popup-button',
					events: {
						click: BX.proxy(function(){
							this.popup.close();
						}, this)
					}
				})
			]);

			popup.close = BX.proxy(this._popupClose, this);
			//BX.addCustomEvent(popup, 'onPopupClose', BX.proxy(this.onClose, this));
			this.popup = popup;
		}
	};

function CrmFormEditorFieldSelector(params)
{
	this.caller = params.caller;
	this.context = params.context;

	this.attributeSearch = 'data-bx-crm-wf-selector-search';
	this.attributeSearchButton = 'data-bx-crm-wf-selector-search-btn';
	this.attributeGroup = 'data-bx-crm-wf-selector-field-group';
	this.attributeField = 'data-bx-crm-wf-selector-field-name';
	this.presetAttributeField = 'data-bx-crm-wf-selector-preset-field';
	this.attributeAddButton = 'data-bx-crm-wf-selector-btn-add';

	this.classFieldSelected = 'crm-orderform-edit-right-inner-list-active';
	this.classSearchActive = 'crm-orderform-edit-constructor-right-search-active';
	this.classGroupShow = 'crm-orderform-edit-open';
	this.classGroupClose = 'crm-orderform-edit-close';

	this.helper = BX.CrmFormEditorHelper;
	this.init(params);
}
CrmFormEditorFieldSelector.prototype =
	{
		getFieldNode: function (fieldName)
		{
			return this.context.querySelector('[' + this.attributeField + '="' + fieldName + '"]');
		},

		init: function (params)
		{
			// init groups
			this.groupNodeList = this.context.querySelectorAll('[' + this.attributeGroup + ']');
			this.groupNodeList = BX.convert.nodeListToArray(this.groupNodeList);
			this.groupNodeList.forEach(this.initGroup, this);

			// init fields
			this.fieldNodeList = this.context.querySelectorAll('[' + this.attributeField + ']');
			this.fieldNodeList = BX.convert.nodeListToArray(this.fieldNodeList);
			this.fieldNodeList.forEach(this.initField, this);

			// init delete callback
			BX.addCustomEvent(this.caller, 'delete-field', BX.proxy(this.onCallerFieldDelete, this));

			// init search button
			this.searchButton = this.context.querySelector('[' + this.attributeSearchButton + ']');
			BX.bind(this.searchButton, 'click', BX.proxy(this.onSearchButtonClick, this));

			// init search input
			this.searchInput = this.context.querySelector('[' + this.attributeSearch + ']');
			BX.bind(this.searchInput, 'bxchange', BX.proxy(this.onSearchChange, this));

			this.adjustHeight();
		},

		adjustHeight: function ()
		{
			var targetNode = document.querySelector('.crm-orderform-edit-constructor-right-list-container');
			var compareByNode = document.querySelector('.crm-orderform-edit-constructor-left-container');

			if (BX.type.isDomNode(targetNode) && BX.type.isDomNode(compareByNode))
			{
				targetNode.style.maxHeight = compareByNode.offsetHeight - 55 + 'px';
			}
		},

		showSearchResult: function (q)
		{
			q = q || '';
			q = q.toLowerCase();
			this.fieldNodeList.forEach(function(fieldNode){
				var hasSubstring = !q ? true : fieldNode.innerText.toLowerCase().indexOf(q) > -1;
				this.helper.styleDisplay(fieldNode, hasSubstring, 'list-item');
			}, this);
		},

		onSearchChange: function ()
		{
			this.showSearchResult(this.searchInput.value);
		},

		onSearchButtonClick: function ()
		{
			var isActive = BX.hasClass(this.searchButton, this.classSearchActive);
			if(isActive)
			{
				this.showSearchResult();
			}
			else
			{
				this.groupNodeList.forEach(function(groupNode){
					BX.addClass(groupNode, this.classGroupShow);
				}, this);

				this.showSearchResult(this.searchInput.value);
			}


			BX.toggleClass(this.searchButton, this.classSearchActive);
			this.searchInput.focus();
		},

		initGroup: function (groupNode)
		{
			BX.bind(groupNode.children[0], 'click', BX.delegate(function() {
				BX.toggleClass(groupNode, this.classGroupShow);
				BX.toggleClass(groupNode, this.classGroupClose);

				if (BX.hasClass(groupNode, this.classGroupShow))
				{
					this.scrollIntoView(groupNode);
				}
			}, this));
		},

		scrollIntoView: function (groupNode)
		{
			var targetNode = document.querySelector('.crm-orderform-edit-constructor-right-list-container');

			var targetPos = BX.pos(targetNode);
			var groupPos = BX.pos(groupNode);

			var top = groupPos.top - targetPos.top + targetNode.scrollTop;
			var bottom = groupPos.bottom - targetPos.top + targetNode.scrollTop;

			var scrollOptions = {};

			if (bottom > targetNode.scrollTop + targetNode.offsetHeight - 18)
			{
				scrollOptions.start = targetNode.scrollTop;

				if (groupPos.height >= targetNode.offsetHeight)
				{
					scrollOptions.finish = top - 5;
				}
				else
				{
					scrollOptions.finish = bottom - targetNode.offsetHeight;
				}
			}

			if (BX.type.isNotEmptyObject(scrollOptions))
			{
				(new BX.easing({
					duration: 300,
					start: {scroll: scrollOptions.start},
					finish: {scroll: scrollOptions.finish},
					transition: BX.easing.makeEaseInOut(BX.easing.transitions.quart),
					step: BX.delegate(function(state){
						targetNode.scrollTo(0, state.scroll);
					}, this)
				})).animate();
			}
		},

		initField: function (fieldNode)
		{
			var fieldName = fieldNode.getAttribute(this.attributeField);
			var filteredFields = this.caller.fields.filter(function(field){
				return field.name == fieldName;
			});
			if(filteredFields.length > 0)
			{
				BX.addClass(fieldNode, this.classFieldSelected);
			}

			BX.bind(fieldNode, 'click', BX.proxy(this.addFieldByClick, this));
		},

		addFieldByClick: function (event)
		{
			BX.addClass(BX.proxy_context, this.classFieldSelected);

			this.caller.addFieldByData({
				name: BX.proxy_context.getAttribute(this.attributeField),
				preset_id: BX.proxy_context.getAttribute(this.presetAttributeField)
			});

			event.preventDefault();
		},

		addSpecialFormField: function (type)
		{
			var fieldParams = {
				entity_caption: '-',
				caption: '',
				type: type,
				name: type + '_' + this.helper.generateId()
			};
			switch(type)
			{
				case 'product':
					fieldParams.caption = this.caller.mess.newFieldProductsCaption;
					fieldParams.id = 'n' + this.helper.generateId();
					break;
				case 'section':
					fieldParams.caption = this.caller.mess.newFieldSectionCaption;
					break;
				case 'hr':
					break;
				case 'br':
					break;
			}
			fieldParams.entity_field_caption = fieldParams.caption;

			return this.caller.addField(fieldParams);
		},

		changeFieldText: function (name, text)
		{
			var fieldNode = this.getFieldNode(name);
			if (BX.type.isDomNode(fieldNode))
			{
				fieldNode.innerHTML = text;
			}
		},

		onCallerFieldAdd: function (field)
		{
			var selectorFieldNode = this.getFieldNode(field.name);
			if(selectorFieldNode)
			{
				BX.addClass(selectorFieldNode, this.classFieldSelected);
			}
		},

		onCallerFieldDelete: function (field)
		{
			var selectorFieldNode = this.getFieldNode(field.name);
			if(selectorFieldNode)
			{
				BX.removeClass(selectorFieldNode, this.classFieldSelected);
			}
		}
	};

function CrmFormEditorDragDrop(params)
{
	this.init(params);
}
CrmFormEditorDragDrop.prototype =
	{
		addItem: function(node)
		{
			this.dragdrop.addDragItem([node]);
			this.dragdrop.addSortableItem(node);
		},

		removeItem: function(node)
		{
			this.dragdrop.removeSortableItem(node);
		},

		init: function(params)
		{
			this.dragdrop = BX.DragDrop.create({

				sortable: {
					rootElem: BX('FIELD_CONTAINER'),
					gagClass: 'gag-class'
				},
				dragActiveClass: 'crm-orderform-field-drag',
				dragItemClassName: 'field-selected',
				dragStart: BX.delegate(function(eventObj, dragElement, event){
					if(!dragElement)
					{
						dragElement = eventObj.dragElement;
					}
					if(!dragElement)
					{
						return;
					}

					// temporary fix: safari doesn't support it
					// if(eventObj.event.dataTransfer && eventObj.event.dataTransfer.setDragImage)
					// {
					// 	var dragIcon = document.createElement('img');
					// 	eventObj.event.dataTransfer.setDragImage(dragIcon, -10, -10);
					// }
				}, this),
				dragEnd: BX.delegate(function(catcherObj, dragElement, event){
					BX.onCustomEvent(this, 'onSort', [dragElement, catcherObj]);
				}, this)
			});
		}
	};




function CrmFormEditorDependencies(params)
{
	params = params || {};
	params.relations = params.relations || [];
	this.helper = BX.CrmFormEditorHelper;

	this.deps = [];
	this.container = BX('DEPENDENCY_CONTAINER');
	this.buttonAdd = BX('DEPENDENCY_BUTTON_ADD');
	this.init(params);
}
CrmFormEditorDependencies.prototype =
	{
		add: function (params)
		{
			var id = 'n' + this.helper.generateId();
			var data = {
				'ID': id,
				'IF_FIELD_CODE': '',
				'IF_VALUE': '',
				'DO_FIELD_CODE': '',
				'DO_ACTION': ''
			};
			var depNode = this.helper.appendNodeByTemplate(this.container, this.caller.templates.dependency, data);

			this.bind(data);

			return depNode;
		},
		bind: function (dep)
		{
			dep.node = BX('DEPENDENCIES_' + dep.ID);

			dep.ifValueNodeCtrlS = BX('DEPENDENCIES_' + dep.ID + '_IF_VALUE_CTRL_S');
			dep.ifValueNodeCtrlI = BX('DEPENDENCIES_' + dep.ID + '_IF_VALUE_CTRL_I');
			dep.ifFieldNodeCtrl = BX('DEPENDENCIES_' + dep.ID + '_IF_FIELD_CODE_CTRL');
			dep.doFieldNodeCtrl = BX('DEPENDENCIES_' + dep.ID + '_DO_FIELD_CODE_CTRL');

			dep.ifValueNode = BX('DEPENDENCIES_' + dep.ID + '_IF_VALUE');
			dep.ifFieldNode = BX('DEPENDENCIES_' + dep.ID + '_IF_FIELD_CODE');
			dep.doFieldNode = BX('DEPENDENCIES_' + dep.ID + '_DO_FIELD_CODE');

			dep.actionNodeCtrl = BX('DEPENDENCIES_' + dep.ID + '_DO_ACTION');
			dep.elseHideTextNode = BX('DEPENDENCIES_' + dep.ID + '_ELSE_HIDE');
			dep.elseShowTextNode = BX('DEPENDENCIES_' + dep.ID + '_ELSE_SHOW');

			var _this = this;
			BX.bind(BX('DEPENDENCIES_' + dep.ID + '_BTN_REMOVE'), 'click', function(){
				_this.remove(dep);
			});
			BX.bind(dep.ifFieldNodeCtrl, 'change', function(){
				if(_this.canChangeFields)
				{
					dep.ifFieldNode.value = this.value;
					_this.actualizeFieldValues(dep);
				}
			});
			BX.bind(dep.doFieldNodeCtrl, 'change', function(){
				if(_this.canChangeFields)
				{
					dep.doFieldNode.value = this.value;
				}

				var caption = '';
				if(this.selectedOptions && this.selectedOptions.length > 0)
				{
					caption = this.selectedOptions[0].innerText;
					caption = '"' + caption + '"';
				}
				dep.elseShowTextNode.children[0].innerText = caption;
				dep.elseHideTextNode.children[0].innerText = caption;
			});
			BX.bind(dep.ifValueNodeCtrlS, 'change', function(){
				var values = [];

				for (var i = 0; i < this.options.length; i++)
				{
					if (this.options[i].selected)
					{
						values.push(this.options[i].value);
					}
				}

				dep.ifValueNode.value = values.join(',');
			});
			BX.bind(dep.ifValueNodeCtrlI, 'change', function(){
				if(_this.canChangeFields)
				{
					dep.ifValueNode.value = this.value;
				}
			});
			BX.bind(dep.actionNodeCtrl, 'change', BX.proxy(function(){
				var isActionHide = dep.actionNodeCtrl.value === 'HIDE';
				this.helper.styleDisplay(dep.elseHideTextNode, !isActionHide);
				this.helper.styleDisplay(dep.elseShowTextNode, isActionHide);
			}, this));

			this.actualize(dep);

			this.deps.push(dep);
		},
		remove: function (dep)
		{
			BX.remove(dep.node);

			var itemIndex = BX.util.array_search(dep, this.deps);
			if(itemIndex > -1)
			{
				delete this.deps[itemIndex];
			}
		},
		actualizeFieldList: function(node, values, isDo)
		{
			this.canChangeFields = false;

			var exceptFieldTypes = ['hr', 'br'];
			var fields = [];
			var defaultOptionText = '';

			isDo = isDo || false;
			if (isDo)
			{
				defaultOptionText = this.caller.mess.selectField;

				this.caller.fields.forEach(function(field){
					if (!BX.util.in_array(field.type, exceptFieldTypes))
					{
						var value = field.name_id || field.name || '';

						fields.push({
							value: value,
							caption: field.getCaption(),
							selected: BX.util.in_array(value, values)
						});
					}
				}, this);
			}
			else
			{
				defaultOptionText = this.caller.mess.selectField;
				this.relationEntities.forEach(function(item){
					fields.push({
						value: item.CODE,
						caption: item.NAME,
						selected: BX.util.in_array(item.CODE, values)
					});
				});
			}

			fields = BX.util.array_merge(
				[{caption: defaultOptionText, value: '', selected: false}],
				fields
			);

			this.helper.fillDropDownControl(node, fields);


			this.canChangeFields = true;
		},
		getRelationItems: function (entityName)
		{
			var items = [];

			for (var i = 0; i < this.relationEntities.length; i++)
			{
				if (this.relationEntities[i].CODE === entityName)
				{
					items = this.relationEntities[i].ITEMS;
					break;
				}
			}

			return items;
		},
		actualizeFieldValues: function (dep)
		{
			this.canChangeFields = false;

			var entityName = dep.ifFieldNode.value;
			if(!entityName) return;

			var isList = true;

			dep.ifValueNodeCtrlI.style.display = isList ? 'none' : '';
			dep.ifValueNodeCtrlS.style.display = !isList ? 'none' : '';

			var values = dep.ifValueNode.value.split(',');

			if(isList)
			{
				var items = this.getRelationItems(entityName);

				items = items.map(function(item){
					return {
						value: item.ID,
						caption: item.VALUE,
						selected: BX.util.in_array(item.ID, values)
					};
				});

				this.helper.fillDropDownControl(dep.ifValueNodeCtrlS, items);
			}

			this.canChangeFields = true;
		},
		actualizeFieldListConditional: function (node, values)
		{
			this.actualizeFieldList(node, values, false);
		},
		actualizeFieldListOperational: function (node, values)
		{
			this.actualizeFieldList(node, values, true);
		},
		actualize: function (dep)
		{
			// get selected dependence field names
			var valueIf = dep.ifFieldNode.value;
			var valueDo = dep.doFieldNode.value;

			// get added field names
			var fieldNameList = this.caller.getFieldNameList();

			if(valueDo && !BX.util.in_array(valueDo, fieldNameList))
			{
				// if field deleted, delete existed dependence
				this.remove(dep);
			}
			else
			{
				// actualize field list in dependency selectors
				this.actualizeFieldListConditional(dep.ifFieldNodeCtrl, [valueIf]);
				this.actualizeFieldValues(dep);
				this.actualizeFieldListOperational(dep.doFieldNodeCtrl, [valueDo]);
			}
		},
		onFieldAdded: function(field)
		{
			var foundDeps = this.deps.filter(function (dep) {
				return (dep && dep.ID === field.name);
			});

			var relations = this.allRelations.filter(function (rel) {
				return rel.DO_FIELD_CODE === field.name;
			});

			if (foundDeps.length === 0 && relations.length > 0)
			{
				this.helper.appendNodeByTemplate(
					this.container,
					this.caller.templates.dependency,
					relations[0]
				);
				this.bind(relations[0]);
			}
		},
		onChangeFormFields: function()
		{
			this.deps.forEach(this.actualize, this);
		},
		init: function (params)
		{
			this.caller = params.caller;
			this.relationEntities = params.relationEntities || {};
			this.helper = new CrmFormEditorHelper();

			this.canChangeFields = true;
			// init add button
			BX.bind(this.buttonAdd, 'click', BX.proxy(this.add, this));

			// init existed deps
			params.relations.forEach(this.bind, this);
			this.allRelations = params.allRelations || [];

			// listen events of changing form fields
			BX.addCustomEvent(this.caller, 'field-added', BX.proxy(this.onFieldAdded, this));
			BX.addCustomEvent(this.caller, 'change-field-list', BX.proxy(this.onChangeFormFields, this));
			BX.addCustomEvent(this.caller, 'change-field-items', BX.proxy(this.onChangeFormFields, this));
		}
	};



function CrmFormEditorFieldPreset(params)
{
	params = params || {};
	params.fields = params.fields || [];

	this.fields = [];
	this.container = BX('PRESET_FIELD_CONTAINER');
	this.buttonAdd = BX('PRESET_FIELD_SELECTOR_BTN');
	this.dealCatrgoryNode = BX('DEAL_CATEGORY');
	this.init(params);
}
CrmFormEditorFieldPreset.prototype =
	{
		isExists: function (code)
		{
			var isExists = false;
			this.fields.forEach(function(field){
				if(field.CODE == code)
				{
					isExists = true;
				}
			});

			return isExists;
		},
		add: function (params)
		{
			if(this.isExists(params.CODE))
			{
				return null;
			}

			var fieldData = this.caller.findDictionaryField(params.CODE);
			if(!fieldData)
			{
				return null;
			}

			var data = {
				'CODE': fieldData.name,
				'ENTITY_CAPTION': fieldData.entity_caption,
				'ENTITY_FIELD_CAPTION': fieldData.caption,
				'ENTITY_NAME': fieldData.entity_name,
				'ENTITY_FIELD_NAME': fieldData.entity_field_name,
				'VALUE': ''
			};
			var node = this.helper.appendNodeByTemplate(this.container, this.caller.templates.presetField, data);
			data.ITEMS = fieldData.items || null;
			this.bind(data);

			return node;
		},
		bind: function (field)
		{
			field.node = BX('FIELD_PRESET_' + field.CODE);

			field.valueNodeCtrl = BX('FIELD_PRESET_' + field.CODE + '_VALUE');
			field.valueNodeCtrlS = BX('FIELD_PRESET_' + field.CODE + '_VALUE_CTRL_S');
			field.valueNodeCtrlI = BX('FIELD_PRESET_' + field.CODE + '_VALUE_CTRL_I');
			field.valueNodeCtrlIMacros = BX('FIELD_PRESET_' + field.CODE + '_VALUE_CTRL_I_M');
			field.valueNodeCtrlIMacrosHint = field.node.querySelector('.crm-orderform-context-help');

			this.caller.initToolTips([field.valueNodeCtrlIMacrosHint]);

			var _this = this;
			BX.bind(BX('FIELD_PRESET_' + field.CODE + '_BTN_REMOVE'), 'click', function(){
				_this.remove(field);
			});

			BX.bind(field.valueNodeCtrlS, 'change', function(){
				if(_this.canChangeFields)
				{
					field.valueNodeCtrl.value = this.value;
				}
			});

			BX.bind(field.valueNodeCtrlI, 'change', function(){
				if(_this.canChangeFields)
				{
					field.valueNodeCtrl.value = this.value;
				}
			});

			BX.bind(field.valueNodeCtrlIMacros, 'click', function(){
				_this.popupMacrosCurrentInput = field.valueNodeCtrlI;
				_this.popupMacros.setBindElement(field.valueNodeCtrlIMacros);
				_this.popupMacros.show();
			});

			if (field.CODE == 'DEAL_STAGE_ID' && this.dealCatrgoryNode)
			{
				BX.bind(this.dealCatrgoryNode, 'click', function(){
					_this.actualize(field);
				});
			}

			this.actualize(field);

			this.fields.push(field);

			if (this.isFieldTypeList(field))
			{
				BX.fireEvent(field.valueNodeCtrlS, 'change');
			}
			else
			{
				BX.fireEvent(field.valueNodeCtrlI, 'change');
			}
		},
		remove: function (field)
		{
			BX.remove(field.node);

			var itemIndex = BX.util.array_search(field, this.fields);
			if(itemIndex > -1)
			{
				delete this.fields[itemIndex];
			}
		},
		isFieldTypeList: function (field)
		{
			var listTypes = ['list', 'checkbox', 'radio'];

			var fieldName = field.CODE;
			if(!fieldName) return false;

			var fieldData = this.caller.findDictionaryField(fieldName);
			if(!fieldData) return false;

			return BX.util.in_array(fieldData.type, listTypes);
		},
		actualize: function (field)
		{
			this.canChangeFields = false;

			var fieldName = field.CODE;
			if(!fieldName) return;

			var fieldData = this.caller.findDictionaryField(fieldName);
			if(!fieldData) return;

			var isList = this.isFieldTypeList(field);
			field.valueNodeCtrlI.style.display = isList ? 'none' : '';
			field.valueNodeCtrlIMacros.style.display = isList ? 'none' : '';
			field.valueNodeCtrlIMacrosHint.style.display = isList ? 'none' : '';
			field.valueNodeCtrlS.style.display = !isList ? 'none' : '';

			var values = [field.valueNodeCtrl.value];
			if(isList)
			{
				var fieldDataItems;
				if (!fieldData.items || fieldData.items.length == 0)
				{
					if (fieldData.type == 'checkbox')
					{
						fieldDataItems = this.caller.booleanFieldItems;
					}
				}
				else
				{
					fieldDataItems = fieldData.items;
				}

				if (fieldDataItems)
				{
					if (fieldName == 'DEAL_STAGE_ID' && this.dealCatrgoryNode)
					{
						var dealCategoryId = this.dealCatrgoryNode.value;
						if (dealCategoryId && fieldData.itemsByCategory && fieldData.itemsByCategory[dealCategoryId])
						{
							fieldDataItems = fieldData.itemsByCategory[dealCategoryId];
						}
					}

					this.helper.fillDropDownControl(
						field.valueNodeCtrlS,
						fieldDataItems.map(function(item){
							return {
								value: item.ID,
								caption: item.VALUE,
								selected: BX.util.in_array(item.ID, values)
							};
						})
					);
				}
			}
			else if(!isList)
			{
				field.valueNodeCtrlI.value = values[0];
			}

			this.canChangeFields = true;
		},

		onChangeEntityScheme: function(schemeData)
		{
			var entityTypes = this.caller.entityScheme.getCurrentWillCreatedEntities();
			entityTypes.push('ACTIVITY');
			var fieldsForDelete = this.fields.filter(function(field){
				return !BX.util.in_array(field.ENTITY_NAME, entityTypes);
			}, this);

			if(fieldsForDelete)
			{
				fieldsForDelete.reverse();
				fieldsForDelete.forEach(function(field){this.remove(field); }, this);
			}

			var firstVisibleOption;
			var optGroupList = BX.convert.nodeListToArray(BX('PRESET_FIELD_SELECTOR').querySelectorAll('optgroup'));
			optGroupList.forEach(function(optGroup){
				var isVisible = BX.util.in_array(optGroup.getAttribute('data-bx-crm-wf-entity'), entityTypes);
				optGroup.style.display = isVisible ? 'block' : 'none';
				if(!firstVisibleOption && isVisible) firstVisibleOption = optGroup.querySelector('option');
			}, this);

			if(firstVisibleOption)
			{
				firstVisibleOption.selected = true;
			}
		},
		onButtonAddClick: function()
		{
			this.add({CODE: BX('PRESET_FIELD_SELECTOR').value});
		},
		init: function (params)
		{
			this.caller = params.caller;
			this.helper = new CrmFormEditorHelper();

			this.canChangeFields = true;
			// init add button
			BX.bind(this.buttonAdd, 'click', BX.proxy(this.onButtonAddClick, this));

			// init existed fields
			if(params.fields)
			{
				params.fields.forEach(this.bind, this);
			}

			// listen events of changing form fields
			BX.addCustomEvent(this.caller, 'change-entity-scheme', BX.proxy(this.onChangeEntityScheme, this));
			this.onChangeEntityScheme(this.caller.entityScheme.getCurrent());

			this.initMacros();
		},
		initMacros: function ()
		{
			this.popupMacrosCurrentInput = null;

			var attributeName = 'data-bx-preset-macros';
			var popupContainer = BX('CRM_ORDER_FORM_POPUP_PRESET_MACROS');
			var macrosNodeList = BX.convert.nodeListToArray(popupContainer.querySelectorAll('[' + attributeName + ']'));
			macrosNodeList.forEach(BX.proxy(function (macrosNode) {
				BX.bind(macrosNode, 'click', BX.delegate(function () {
					if(!this.popupMacrosCurrentInput)
					{
						return;
					}
					this.popupMacrosCurrentInput.value += ' ' + macrosNode.getAttribute(attributeName);
					BX.fireEvent(this.popupMacrosCurrentInput, 'change');
					this.popupMacros.close();
				}, this));
			}, this));

			this.popupMacros = BX.PopupWindowManager.create(
				'crm_orderform_edit_preset_macros',
				null,
				{
					content: popupContainer,
					autoHide: true,
					lightShadow: true,
					closeByEsc: true
				}
			);
		}
	};

function CrmFormEditorUserBlockController(params)
{
	this.context = BX('CRM_ORDERFORM_ADDITIONAL_OPTIONS');
	this.additionalOptionContainer = BX('ADDITIONAL_OPTION_CONTAINER');

	this.attributeOption = 'data-bx-crm-orderform-edit-option';
	this.attributeNav = 'data-bx-crm-orderform-edit-option-nav';
	this.attributePin = 'data-bx-crm-orderform-edit-option-pin';

	this.userOptionPin = params.optionPinName;
	this.blocks = [];

	this.helper = BX.CrmFormEditorHelper;

	var blockNodeList = this.context.querySelectorAll('[' + this.attributeOption + ']');
	blockNodeList = BX.convert.nodeListToArray(blockNodeList);
	blockNodeList.forEach(this.initBlock, this);

	BX.bind(BX('ADDITIONAL_OPTION_BUTTON'), 'click', BX.proxy(function(){
		BX.toggleClass(this.additionalOptionContainer, 'crm-orderform-edit-open');
	}, this));
}
CrmFormEditorUserBlockController.prototype =
	{
		initBlock: function (blockNode)
		{
			var id = blockNode.getAttribute(this.attributeOption);
			var pinNode = blockNode.querySelector('[' + this.attributePin + ']');
			var block = {
				'id': id,
				'node': blockNode,
				'navNode': this.context.querySelector('[' + this.attributeNav + '="' + id + '"]'),
				'pinNode': pinNode,
				'isFixed': this.isBlockPinFixed(pinNode)
			};
			this.blocks.push(block);


			var _this = this;

			//bind block pin
			BX.bind(block.pinNode, 'click', function(){
				_this.onClickBlockPin(block);
			});

			//bind nav button
			BX.bind(block.navNode, 'click', function(e){
				BX.PreventDefault(e);
				_this.highLightBlock(block);
				return true;
			});

			if(block.id == 'ENTITY_SCHEME')
			{
				//bind top nav button to document
				BX.bind(BX('CRM_ORDERFORM_STICKER_ENTITY_SCHEME_NAV'), 'click', function(e){
					BX.PreventDefault(e);
					_this.highLightBlock(block);
					return true;
				});
			}
		},
		isBlockPinFixed: function (pinNode)
		{
			return BX.hasClass(pinNode, 'task-option-fixed-state');
		},
		onClickBlockPin: function (block)
		{
			// set pin state of block
			block.isFixed = !this.isBlockPinFixed(block.pinNode);

			// save pin state of block
			this.saveBlockPinState(block.id, block.isFixed);

			//change icon state indicator
			this.helper.changeClass(block.pinNode, 'task-option-fixed-state', block.isFixed);

			// move block
			this.moveBlock(block);

			// change visibility of nav button
			this.helper.changeClass(block.navNode, 'crm-orderform-display-none', block.isFixed);
		},
		show: function ()
		{
			BX.addClass(this.additionalOptionContainer, 'crm-orderform-edit-open');
		},
		hide: function ()
		{
			BX.removeClass(this.additionalOptionContainer, 'crm-orderform-edit-open');
		},
		highLightBlock: function (block)
		{
			this.show();
			var highlightClassName = 'crm-orderform-edit-highlight';
			BX.addClass(block.node, highlightClassName);

			setTimeout(function(){
				var position = BX.pos(block.node),
					scrollTop = window.scrollY
						|| window.pageYOffset
						|| document.body.scrollTop + (document.documentElement && document.documentElement.scrollTop || 0);

				(new BX.easing({
					duration: 300,
					start: {scroll: scrollTop },
					finish: {scroll: position.top},
					transition: BX.easing.makeEaseInOut(BX.easing.transitions.quart),
					step: BX.delegate(function(state){
						window.scrollTo(0, state.scroll);
					}, this)
				})).animate();
			}, 50);

			setTimeout(function(){
				BX.removeClass(block.node, highlightClassName);
			}, 2000);
		},
		moveBlock: function (block)
		{
			this.show();
			//hide block
			BX.addClass(block.node, 'crm-orderform-display-none');

			//append block
			var target = block.isFixed ? BX('FIXED_OPTION_PLACE') : BX('ADDITIONAL_OPTION_PLACE_' + block.id);
			target.appendChild(block.node);

			//show block
			BX.removeClass(block.node, 'crm-orderform-display-none');
		},
		saveBlockPinState: function (blockId, isPinned)
		{
			BX.userOptions.save('crm', this.userOptionPin, blockId, isPinned ? 'Y' : 'N');
		}
	};



function CrmFormEditorFieldListSettings(params)
{
	this.type = params.type;
	this.context = params.context;
	this.caller = params.caller;
	this.field = params.field;

	this.helper = BX.CrmFormEditorHelper;
	this.nodeItemsContainer = this.context.querySelector('[data-bx-crm-orderform-field-settings-items]');

	this.attributeItem = 'data-bx-crm-orderform-field-settings-item';
	this.attributeItemCheck = 'data-bx-crm-orderform-field-settings-item-check';
	this.attributeItemRadio = 'data-bx-crm-orderform-field-settings-item-radio';
	this.attributeItemClear = 'data-bx-crm-orderform-field-settings-item-clear';
	this.attributeItemInput = 'data-bx-crm-orderform-field-settings-item-input';

	this.init();
}
CrmFormEditorFieldListSettings.prototype =
	{
		init: function()
		{
			if(!this.nodeItemsContainer)
			{
				return;
			}

			var items = this.nodeItemsContainer.querySelectorAll('[' + this.attributeItem + ']');
			items = BX.convert.nodeListToArray(items);
			items.forEach(function(item){
				var clearButton = item.querySelector('[' + this.attributeItemClear + ']');
				var input = item.querySelector('[' + this.attributeItemInput + ']');
				var itemId = item.getAttribute(this.attributeItem);
				BX.bind(clearButton, 'click', function(){
					input.value = '';
					BX.fireEvent(input, 'change');
				});
				BX.bind(input, 'change', BX.proxy(function(){
					this.field.items.forEach(function(fieldItem){
						if(fieldItem.ID == itemId)
						{
							if (fieldItem.NAME !== undefined)
							{
								fieldItem.NAME = input.value;
							}
							else
							{
								fieldItem.VALUE = input.value;
							}

							this.caller.fireFieldChangeItemsEvent(this.field);
						}
					}, this);

				}, this));

			}, this);


			if(this.type == 'checkbox')
			{
				this.showControls(this.attributeItemCheck);
			}
			else if(this.type == 'radio')
			{
				this.showControls(this.attributeItemRadio);
			}

		},

		showControls: function(attribute)
		{
			var controlList = this.nodeItemsContainer.querySelectorAll('[' + attribute + ']')
			controlList = BX.convert.nodeListToArray(controlList);
			controlList.forEach(function(control){
				control.disabled = false;
				this.helper.styleDisplay(control, true);
			}, this);
		}
	};




function CrmFormEditorProductSelector(params)
{
	this.helper = BX.CrmFormEditorHelper;

	this.caller = params.caller;
	this.id = params.id;
	this.field = params.field;
	this.context = params.context;
	this.node = this.context.querySelector('[data-bx-crm-orderform-product]');
	if(!this.node)
	{
		return;
	}

	this.nodeItems = this.node.querySelector('[data-bx-crm-orderform-product-items]');
	this.nodeSelect = this.node.querySelector('[data-bx-crm-orderform-product-select]');
	this.nodeAddRow = this.node.querySelector('[data-bx-crm-orderform-product-add-row]');

	this.attributeItem = 'data-bx-crm-orderform-product-item';
	this.attributeItemDelete = 'data-bx-crm-orderform-product-item-del';
	this.attributeItemInput = 'data-bx-crm-orderform-product-item-input';

	this.random = Math.random();
	this.jsEventsManagerId = params.jsEventsManagerId;
	this.caller = params.caller;
	BX.bind(this.nodeSelect, 'click', BX.proxy(this.onClick, this));
	BX.bind(this.nodeAddRow, 'click', BX.proxy(this.onClickAddRow, this));
	this._choiceBtnEnabled = true;

	BX.addCustomEvent('CrmProductSearchDialog_SelectProduct', BX.proxy(this.onProductClick, this));

	this.isCallSearchDialog = false;
	this.initItems();
}
CrmFormEditorProductSelector.prototype =
	{
		onShow: function(e)
		{
			/*
			var choiceBtn = document.getElementById(this.getSetting('choiceBtnID', ''));
			if(choiceBtn)
				BX.removeClass(choiceBtn, "orderform-small-button-wait");
			*/
			this.isCallSearchDialog = true;
			this.helper.overlay.removeOverlay();
			this.caller.popupFieldSettings.setAutoHide(false);
		},
		onClose: function(e)
		{
			this.isCallSearchDialog = false;
			this._choiceBtnEnabled = true;
			this.caller.popupFieldSettings.setAutoHide(false);
		},
		onClick: function(e)
		{
			if (!this._choiceBtnEnabled)
				return;

			this._choiceBtnEnabled = false;

			this.helper.overlay.createOverlay(2000);

			/*
			var choiceBtn = document.getElementById(this.getSetting('choiceBtnID', ''));
			if(choiceBtn)
				BX.addClass(choiceBtn, "orderform-small-button-wait");


			var caller = 'crm_productrow_list';
			var dlg = BX.CrmProductSearchDialogWindow.create({
				content_url: "/bitrix/components/bitrix/crm.order.matcher.edit/product_choice_dialog.php" +
				"?caller=" + caller + "&JS_EVENTS_MANAGER_ID=" + BX.util.urlencode(this.jsEventsManagerId) +
				"&sessid=" + BX.bitrix_sessid(),
				closeWindowHandler: BX.delegate(this.onClose, this),
				showWindowHandler: BX.delegate(this.onShow, this),
				jsEventsManagerId: this.jsEventsManagerId,
				height: Math.max(500, window.innerHeight - 400),
				width: Math.max(800, window.innerWidth - 400),
				minHeight: 500,
				minWidth: 800,
				draggable: true,
				resizable: true
			});
			dlg.show();*/
		},

		onClickAddRow: function()
		{
			this.addFieldItem({
				'id': 'n' + this.helper.generateId(),
				'name': '',
				'price': ''
			});
		},

		initItems: function()
		{
			this.field.items.forEach(function(fieldItem){
				var itemNode = this.nodeItems.querySelector('[' + this.attributeItem + '="' + fieldItem.ID + '"' + ']');
				if(!itemNode)
				{
					return;
				}

				this.initProductItem(itemNode, fieldItem);

			}, this);
		},


		handleProductChoice: function(data, skipFocus)
		{
			skipFocus = !!skipFocus;
			var item = typeof(data['product']) != 'undefined' && typeof(data['product'][0]) != 'undefined' ? data['product'][0] : null;
			if(!item)
			{
				return;
			}

			var customData = typeof(item['customData']) !== 'undefined' ? item['customData'] : {};
			var measure = typeof(customData['measure']) !== 'undefined' ? customData['measure'] : {};
			var itemData =
				{
					id: item['id'],
					name: item['title'],
					quantity: 1.0,
					price: typeof(customData['price']) != 'undefined' ? parseFloat(customData['price']) : 0.0,
					customized: false,
					measureCode: typeof(measure['code']) !== 'undefined' ? parseInt(measure['code']) : 0,
					measureName: typeof(measure['name']) !== 'undefined' ? measure['name'] : '',
					tax: typeof(customData['tax']) !== 'undefined' ? customData['tax'] : {}
				};

			this.addFieldItem(itemData);
			/*
			 if (this._viewMode)
			 this.toggleMode();
			 this._addItem(itemData, true);
			 if (!skipFocus)
			 this.focusLastRow();
			 */
		},

		addFieldItem: function(itemData)
		{
			var itemNode = this.helper.getNodeByTemplate('tmpl_field_product_settings_item', {
				'id': this.id,
				'name': this.field.name,
				'item_id': itemData.id,
				'item_value': itemData.NAME ? itemData.NAME : itemData.VALUE,
				'item_price': itemData.price,
				'currency_short_name': this.caller.currency.SHORT_NAME
			});

			var fieldItem = {
				'ID': itemData.id,
				'PRICE': itemData.price,
				'VALUE': itemData.name
			};

			this.initProductItem(itemNode, fieldItem);
			this.field.items.push(fieldItem);

			this.nodeItems.appendChild(itemNode);
			this.fireFieldItemsChange();
		},

		fireFieldItemsChange: function()
		{
			this.caller.fireFieldChangeItemsEvent(this.field);
		},

		initProductItem: function(itemNode, fieldItem)
		{
			var itemId = itemNode.getAttribute(this.attributeItem);
			var itemDeleteNode = itemNode.querySelector('[' + this.attributeItemDelete + ']');
			var itemInputNode = itemNode.querySelector('[' + this.attributeItemInput + ']');
			BX.bind(itemDeleteNode, 'click', BX.proxy(function(){
				this.removeProductItem(itemNode, fieldItem);
			}, this));

			BX.bind(itemInputNode, 'change', BX.proxy(function(){
				this.field.items.forEach(function(fieldItem){
					if(fieldItem.ID == itemId)
					{
						fieldItem.VALUE = itemInputNode.value;
						this.caller.fireFieldChangeItemsEvent(this.field);
					}
				}, this);

			}, this));
		},

		removeProductItem: function(itemNode, fieldItem)
		{
			var index =BX.util.array_search(fieldItem, this.field.items);
			if(index > -1)
			{
				this.field.items = BX.util.deleteFromArray(this.field.items, index);
			}
			BX.remove(itemNode);

			this.fireFieldItemsChange();
		},

		onProductClick: function(productId)
		{
			if(!this.isCallSearchDialog)
			{
				return;
			}

			productId = parseInt(productId);
			if (productId <= 0)
			{
				return;
			}

			var currencyID = '';//this.getCurrencyId();
			BX.ajax({
				'url': '/bitrix/components/bitrix/crm.product.list/list.ajax.php',
				'method': 'POST',
				'dataType': 'json',
				'data':
					{
						"sessid": BX.bitrix_sessid(),
						"MODE": "SEARCH",
						"RESULT_WITH_VALUE" : "Y",
						"CURRENCY_ID": currencyID,
						"ENABLE_RAW_PRICES": "Y",
						"ENABLE_SEARCH_BY_ID": "N",
						"MULTI": "N",
						"VALUE": "[" + productId + "]",
						"LIMIT": 1
					},
				onsuccess: BX.delegate(this.onProductChoiceByIdSuccess, this),
				onfailure: BX.delegate(this.onProductChoiceByIdFailure, this)
			});
		},
		onProductChoiceByIdSuccess: function(response)
		{
			var data;
			if (response && response["data"])
			{
				data = response["data"];
				if (data[0])
				{
					data = {"product": [data[0]]};
					this.handleProductChoice(data, true);
				}
			}
		},
		onProductChoiceByIdFailure: function(data)
		{
		}
	};

function CrmFormEditorHelper(){}
CrmFormEditorHelper.prototype =
	{
		generateId: function (min, max)
		{
			min = min || 1000000;
			max = max || 9999999;
			return Math.floor(Math.random() * (max - min)) + min;
		},

		fillDropDownControl: function(node, items)
		{
			items = items || [];
			node.innerHTML = '';

			items.forEach(function(item){
				if(!item || !item.caption)
				{
					return;
				}

				var option = document.createElement('option');
				option.value = item.value;
				option.selected = !!item.selected;
				option.innerText = item.caption;
				node.appendChild(option);
			});
		},

		appendNodeByTemplate: function(container, templateId, replaceData)
		{
			var node = this.getNodeByTemplate(templateId, replaceData);
			if(node)
			{
				var sorted = false;

				if (replaceData.sort)
				{
					var nodes = container.querySelectorAll('[data-crm-orderform-edit-field-container]');
					for (var i = 0; i < nodes.length; i++)
					{
						var sortInput = nodes[i].querySelector('[data-bx-order-form-field-sort]');
						if (!BX.type.isDomNode(sortInput) || (parseInt(replaceData.sort) < parseInt(sortInput.value)))
						{
							container.insertBefore(node, nodes[i]);
							sorted = true;
							break;
						}
					}
				}

				if (!sorted)
				{
					container.appendChild(node);
				}

				BX.ajax.processScripts(this.getScriptsByTemplate(templateId, replaceData));

				if (templateId === 'tmpl_field_location')
				{
					if (typeof window.BX.locationsDeferred != 'undefined')
					{
						for (var k in window.BX.locationsDeferred)
						{
							if (k !== '%control_id%')
							{
								// initialization
								window.BX.locationsDeferred[k].call(this);
								window.BX.locationsDeferred[k] = null;

								// set current location code
								window.BX.locationSelectors[k].setValueByLocationCode(replaceData.value);
							}

							delete(window.BX.locationsDeferred[k]);
						}
					}
				}
			}

			return node;
		},

		getNodeByTemplate: function(id, replaceData)
		{
			var tmpl = this.getTemplate(id, replaceData);
			if(!tmpl)
			{
				return null;
			}

			var div = BX.create('div', {html: tmpl});
			return div.firstElementChild;
		},

		getScriptsByTemplate: function(id, replaceData)
		{
			var tmpl = this.getTemplate(id, replaceData);
			if(!tmpl)
			{
				return null;
			}

			return BX.processHTML(tmpl).SCRIPT;
		},

		getTemplate: function(id, replaceData)
		{
			var tmplNode = BX(id);
			if(!tmplNode)
			{
				return null;
			}

			var tmpl = tmplNode.innerHTML;
			if(replaceData)
			{
				for(var i in replaceData)
				{
					if(!replaceData.hasOwnProperty(i) && replaceData[i] === undefined)
					{
						continue;
					}

					var replaceTo;

					if (i[0] === '~')
					{
						replaceTo = replaceData[i];
						i = i.substr(1);
					}
					else
					{
						replaceTo = BX.util.htmlspecialchars(replaceData[i]);
					}

					tmpl = tmpl.replace(new RegExp('%' + i + '%','g'), replaceTo);
				}
			}

			return tmpl;
		},

		changeClass: function (node, className, isAdd)
		{
			isAdd = isAdd || false;
			if(!node)
			{
				return;
			}

			if(isAdd)
			{
				BX.addClass(node, className);
			}
			else
			{
				BX.removeClass(node, className);
			}
		},

		styleDisplay: function (node, isShow, displayValue)
		{
			isShow = isShow || false;
			displayValue = displayValue || '';
			if(!node)
			{
				return;
			}

			node.style.display = isShow ? displayValue : 'none';
		},

		overlay: {

			createOverlay: function(zIndex)
			{
				zIndex = parseInt(zIndex);
				if (!this._overlay)
				{
					var windowSize = BX.GetWindowScrollSize();
					this._overlay = document.body.appendChild(BX.create("DIV", {
						style: {
							position: 'absolute',
							top: '0px',
							left: '0px',
							zIndex: zIndex || (parseInt(this.DIV.style.zIndex)-2),
							width: windowSize.scrollWidth + "px",
							height: windowSize.scrollHeight + "px"
						}
					}));
					BX.unbind(window, 'resize', BX.proxy(this._resizeOverlay, this));
					BX.bind(window, 'resize', BX.proxy(this._resizeOverlay, this));
				}
			},
			removeOverlay: function()
			{
				if (this._overlay && this._overlay.parentNode)
				{
					this._overlay.parentNode.removeChild(this._overlay);
					BX.unbind(window, 'resize', BX.proxy(this._resizeOverlay, this));
					this._overlay = null;
				}
			},
			_resizeOverlay: function()
			{
				var windowSize = BX.GetWindowScrollSize();
				this._overlay.style.width = windowSize.scrollWidth + "px";
			}
		}
	};
BX.CrmFormEditorHelper = new CrmFormEditorHelper();


if (typeof(BX.CrmProductSearchDialogWindow) === "undefined")
{
	BX.CrmProductSearchDialogWindow = function()
	{
		this._settings = {};
		this.popup = null;
		this.random = "";
		this.contentContainer = null;
		this.zIndex = 100;
		this.jsEventsManager = null;
		this.pos = null;
		this.height = 0;
		this.width = 0;
		this.resizeCorner = null;
	};

	BX.CrmProductSearchDialogWindow.prototype = {
		initialize: function (settings)
		{
			this.random = Math.random().toString().substring(2);

			this._settings = settings ? settings : {};

			var size = BX.CrmProductSearchDialogWindow.size;

			this._settings.width = size.width || this._settings.width || 1100;
			this._settings.height = size.height || this._settings.height || 530;
			this._settings.minWidth = this._settings.minWidth || 500;
			this._settings.minHeight = this._settings.minHeight || 800;
			this._settings.draggable = !!this._settings.draggable || true;
			this._settings.resizable = !!this._settings.resizable || true;
			if (typeof(this._settings.closeWindowHandler) !== "function")
				this._settings.closeWindowHandler = null;
			if (typeof(this._settings.showWindowHandler) !== "function")
				this._settings.showWindowHandler = null;

			this.jsEventsManager = BX.Crm[this._settings.jsEventsManagerId] || null;

			this.contentContainer = BX.create(
				"DIV",
				{
					attrs: {
						className: "crm-catalog",
						style: "display: block; background-color: #f3f6f7; height: " + this._settings.height +
							"px; overflow: hidden; width: " + this._settings.width + "px;"
					}
				}
			);
		},
		_handleCloseDialog: function(popup)
		{
			if(popup)
				popup.destroy();
			this.popup = null;
			if (this.jsEventsManager)
			{
				this.jsEventsManager.unregisterEventHandlers("CrmProduct_SelectSection");
			}
			if (typeof(this._settings.closeWindowHandler) === "function")
				this._settings.closeWindowHandler();
		},
		_handleAfterShowDialog: function(popup)
		{
			popup.popupContainer.style.position = "fixed";
			popup.popupContainer.style.top =
				(parseInt(popup.popupContainer.style.top) - BX.GetWindowScrollPos().scrollTop) + 'px';
			if (typeof(this._settings.showWindowHandler) === "function")
				this._settings.showWindowHandler();
		},
		setContent: function (htmlData)
		{
			if (BX.type.isString(htmlData) && BX.type.isDomNode(this.contentContainer))
				this.contentContainer.innerHTML = htmlData;
		},
		show: function ()
		{
			BX.ajax({
				method: "GET",
				dataType: 'html',
				url: this._settings.content_url,
				data: {},
				skipAuthCheck: true,
				onsuccess: BX.delegate(function(data) {
					this.setContent(data || "&nbsp;");
					this.showWindow();
				}, this),
				onfailure: BX.delegate(function() {
					if (typeof(this._settings.showWindowHandler) === "function")
						this._settings.showWindowHandler();
				}, this)
			});
		},
		showWindow: function ()
		{
			this.popup = new BX.PopupWindow(
				"CrmProductSearchDialogWindow_" + this.random,
				null,
				{
					overlay: {opacity: 82},
					autoHide: false,
					draggable: this._settings.draggable,
					offsetLeft: 0,
					offsetTop: 0,
					bindOptions: { forceBindPosition: false },
					bindOnResize: false,
					zIndex: this.zIndex - 300,
					closeByEsc: true,
					closeIcon: { top: '10px', right: '15px' },
					"titleBar":
						{
							"content": BX.create("SPAN", { "attrs":
									{ "className": "popup-window-titlebar-text" },
								"text": BX.message('CRM_ORDERFORM_EDIT_JS_PRODUCT_CHOICE')
							})
						},
					events:
						{
							onPopupClose: BX.delegate(this._handleCloseDialog, this),
							onAfterPopupShow: BX.delegate(this._handleAfterShowDialog, this)
						},
					"content": this.contentContainer
				}
			);
			if (this.popup.popupContainer)
			{
				this.resizeCorner = BX.create(
					'SPAN',
					{
						attrs: {className: "bx-crm-dialog-resize"},
						events: {mousedown : BX.delegate(this.resizeWindowStart, this)}
					}
				);
				this.popup.popupContainer.appendChild(this.resizeCorner);
				if (!this._settings.resizable)
					this.resizeCorner.style.display = "none";
			}
			this.popup.show();
		},
		setResizable: function(resizable)
		{
			resizable = !!resizable;
			if (this._settings.resizable !== resizable)
			{
				this._settings.resizable = resizable;
				if (this.resizeCorner)
				{
					if (resizable)
						this.resizeCorner.style.display = "inline-block";
					else
						this.resizeCorner.style.display = "none";
				}
			}
		},
		resizeWindowStart: function(e)
		{
			if (!this._settings.resizable)
				return;

			e =  e || window.event;
			BX.PreventDefault(e);

			this.pos = BX.pos(this.contentContainer);

			BX.bind(document, "mousemove", BX.proxy(this.resizeWindowMove, this));
			BX.bind(document, "mouseup", BX.proxy(this.resizeWindowStop, this));

			if (document.body.setCapture)
				document.body.setCapture();

			try { document.onmousedown = false; } catch(e) {}
			try { document.body.onselectstart = false; } catch(e) {}
			try { document.body.ondragstart = false; } catch(e) {}
			try { document.body.style.MozUserSelect = "none"; } catch(e) {}
			try { document.body.style.cursor = "nwse-resize"; } catch(e) {}
		},
		resizeWindowMove: function(e)
		{
			var windowScroll = BX.GetWindowScrollPos();
			var x = e.clientX + windowScroll.scrollLeft;
			var y = e.clientY + windowScroll.scrollTop;

			BX.CrmProductSearchDialogWindow.size.height = this.height = Math.max(y-this.pos.top, this._settings.minHeight);
			BX.CrmProductSearchDialogWindow.size.width = this.width = Math.max(x-this.pos.left, this._settings.minWidth);

			this.contentContainer.style.height = this.height+'px';
			this.contentContainer.style.width = this.width+'px';
		},
		resizeWindowStop: function(e)
		{
			if(document.body.releaseCapture)
				document.body.releaseCapture();

			BX.unbind(document, "mousemove", BX.proxy(this.resizeWindowMove, this));
			BX.unbind(document, "mouseup", BX.proxy(this.resizeWindowStop, this));

			try { document.onmousedown = null; } catch(e) {}
			try { document.body.onselectstart = null; } catch(e) {}
			try { document.body.ondragstart = null; } catch(e) {}
			try { document.body.style.MozUserSelect = ""; } catch(e) {}
			try { document.body.style.cursor = "auto"; } catch(e) {}
		}
	};

	BX.CrmProductSearchDialogWindow.create = function(settings)
	{
		var self = new BX.CrmProductSearchDialogWindow();
		self.initialize(settings);
		return self;
	};
	BX.CrmProductSearchDialogWindow.loadCSS = function(settings)
	{
		BX.ajax({
			method: "GET",
			dataType: 'html',
			url: settings.content_url,
			data: {},
			skipAuthCheck: true
		});
	};


	BX.CrmProductSearchDialogWindow.size = {width: 0, height: 0};
}

BX.namespace("BX.Crm");
if (typeof(BX.Crm.PageEventsManagerClass) === "undefined")
{
	BX.Crm.PageEventsManagerClass = function()
	{
		this._settings = {};
	};

	BX.Crm.PageEventsManagerClass.prototype = {
		initialize: function (settings)
		{
			this._settings = settings ? settings : {};
			this.eventHandlers = {};
		},
		registerEventHandler: function(eventName, eventHandler)
		{
			if (!this.eventHandlers[eventName])
				this.eventHandlers[eventName] = [];
			this.eventHandlers[eventName].push(eventHandler);
			BX.addCustomEvent(this, eventName, eventHandler);
		},
		fireEvent: function(eventName, eventParams)
		{
			BX.onCustomEvent(this, eventName, eventParams);
		},
		unregisterEventHandlers: function(eventName)
		{
			if (this.eventHandlers[eventName])
			{
				for (var i = 0; i < this.eventHandlers[eventName].length; i++)
				{
					BX.removeCustomEvent(this, eventName, this.eventHandlers[eventName][i]);
					delete this.eventHandlers[eventName][i];
				}
			}
		}
	};

	BX.Crm.PageEventsManagerClass.create = function(settings)
	{
		var self = new BX.Crm.PageEventsManagerClass();
		self.initialize(settings);
		return self;
	};
}

function CrmFormEditorDestination (params)
{
	var me = this;

	this.caller = params.caller;
	var container = params.container;

	var config, configString = container.getAttribute('data-config');
	if (configString)
	{
		config = BX.parseJSON(configString);
	}

	if (!BX.type.isPlainObject(config))
		config = {};

	this.container = container;
	this.itemsNode = BX.create('span');
	this.inputBoxNode = BX.create('span', {
		attrs: {
			className: 'feed-add-destination-input-box',
			style: 'display: none;'
		}
	});
	this.inputNode = BX.create('input', {
		props: {
			type: 'text'
		},
		attrs: {
			className: 'feed-add-destination-inp'
		}
	});

	this.inputBoxNode.appendChild(this.inputNode);

	this.tagNode = BX.create('a', {
		attrs: {
			className: 'feed-add-destination-link'
		}
	});

	BX.addClass(container, 'crm-orderform-popup-autocomplete');

	container.appendChild(this.itemsNode);
	container.appendChild(this.inputBoxNode);
	container.appendChild(this.tagNode);

	this.itemTpl = config.itemTpl;

	this.data = null;
	this.dialogId = 'crm_orderform_edit_responsible_';
	this.createValueNode(config.valueInputName || '');
	this.selected = config.selected ? BX.clone(config.selected) : [];
	this.selectOne = !config.multiple;
	this.required = config.required || false;
	this.additionalFields = BX.type.isArray(config.additionalFields) ? config.additionalFields : [];

	BX.bind(this.tagNode, 'focus', function(e) {
		me.openDialog({bByFocusEvent: true});
		return BX.PreventDefault(e);
	});
	BX.bind(this.container, 'click', function(e) {
		me.openDialog();
		return BX.PreventDefault(e);
	});

	this.addItems(this.selected);

	this.tagNode.innerHTML = (
		this.selected.length <= 0
			? this.caller.mess.dlgChoose
			: this.caller.mess.dlgChange
	);
}
CrmFormEditorDestination.prototype = {
	getData: function(next)
	{
		var me = this;

		if (me.ajaxProgress)
			return;

		me.ajaxProgress = true;

		this.caller.sendActionRequest('getDestinationDataAjax', {}, function (response) {
			me.data = response.DATA || {};
			me.ajaxProgress = false;
			me.initDialog(next);
		}, function () {

		});
	},
	initDialog: function(next)
	{
		var i, me = this, data = this.data;

		if (!data)
		{
			me.getData(next);
			return;
		}

		var itemsSelected = {};
		for (i = 0; i < me.selected.length; ++i)
		{
			itemsSelected[me.selected[i].id] = me.selected[i].entityType
		}

		var items = {
			users : data.USERS || {},
			department : data.DEPARTMENT || {},
			departmentRelation : data.DEPARTMENT_RELATION || {},
			bpuserroles : data.ROLES || {}
		};
		var itemsLast =  {
			users: data.LAST.USERS || {},
			bpuserroles : data.LAST.ROLES || {}
		};

		for (i = 0; i < this.additionalFields.length; ++i)
		{
			items.bpuserroles[this.additionalFields[i]['id']] = this.additionalFields[i];
		}

		if (!items["departmentRelation"])
		{
			items["departmentRelation"] = BX.SocNetLogDestination.buildDepartmentRelation(items["department"]);
		}

		if (!me.inited)
		{
			me.inited = true;
			var destinationInput = me.inputNode;
			destinationInput.id = me.dialogId + 'input';

			var destinationInputBox = me.inputBoxNode;
			destinationInputBox.id = me.dialogId + 'input-box';

			var tagNode = this.tagNode;
			tagNode.id = this.dialogId + 'tag';

			var itemsNode = me.itemsNode;

			BX.SocNetLogDestination.init({
				name : me.dialogId,
				searchInput : destinationInput,
				extranetUser :  false,
				bindMainPopup : {node: me.container, offsetTop: '5px', offsetLeft: '15px'},
				bindSearchPopup : {node: me.container, offsetTop : '5px', offsetLeft: '15px'},
				departmentSelectDisable: true,
				sendAjaxSearch: true,
				callback : {
					select : function(item, type, search, bUndeleted)
					{
						me.addItem(item, type);
						if (me.selectOne)
							BX.SocNetLogDestination.closeDialog();
					},
					unSelect : function (item)
					{
						if (me.selectOne)
							return;
						me.unsetValue(item.entityId);
						BX.SocNetLogDestination.BXfpUnSelectCallback.call({
							formName: me.dialogId,
							inputContainerName: itemsNode,
							inputName: destinationInput.id,
							tagInputName: tagNode.id,
							tagLink1: me.caller.mess.dlgChoose,
							tagLink2: me.caller.mess.dlgChange
						}, item)
					},
					openDialog : BX.delegate(BX.SocNetLogDestination.BXfpOpenDialogCallback, {
						inputBoxName: destinationInputBox.id,
						inputName: destinationInput.id,
						tagInputName: tagNode.id
					}),
					closeDialog : BX.delegate(
						BX.SocNetLogDestination.BXfpCloseDialogCallback,
						{
							inputBoxName: destinationInputBox.id,
							inputName: destinationInput.id,
							tagInputName: tagNode.id
						}
					),
					openSearch : BX.delegate(BX.SocNetLogDestination.BXfpOpenDialogCallback, {
						inputBoxName: destinationInputBox.id,
						inputName: destinationInput.id,
						tagInputName: tagNode.id
					}),
					closeSearch : BX.delegate(BX.SocNetLogDestination.BXfpCloseSearchCallback, {
						inputBoxName: destinationInputBox.id,
						inputName: destinationInput.id,
						tagInputName: tagNode.id
					})
				},
				items : items,
				itemsLast : itemsLast,
				itemsSelected : itemsSelected,
				useClientDatabase: false,
				destSort: data.DEST_SORT || {},
				allowAddUser: false
			});

			BX.bind(destinationInput, 'keyup', BX.delegate(BX.SocNetLogDestination.BXfpSearch, {
				formName: me.dialogId,
				inputName: destinationInput.id,
				tagInputName: tagNode.id
			}));
			BX.bind(destinationInput, 'keydown', BX.delegate(BX.SocNetLogDestination.BXfpSearchBefore, {
				formName: me.dialogId,
				inputName: destinationInput.id
			}));

			BX.SocNetLogDestination.BXfpSetLinkName({
				formName: me.dialogId,
				tagInputName: tagNode.id,
				tagLink1: me.caller.mess.dlgChoose,
				tagLink2: me.caller.mess.dlgChange
			});
		}
		next();
	},
	addItem: function(item, type)
	{
		var me = this;
		var destinationInput = this.inputNode;
		var tagNode = this.tagNode;
		var items = this.itemsNode;

		if (!BX.findChild(items, { attr : { 'data-id' : item.id }}, false, false))
		{
			if (me.selectOne && me.inited)
			{
				var toRemove = [];
				for (var i = 0; i < items.childNodes.length; ++i)
				{
					toRemove.push({
						itemId: items.childNodes[i].getAttribute('data-id'),
						itemType: items.childNodes[i].getAttribute('data-type')
					})
				}

				me.initDialog(function() {
					for (var i = 0; i < toRemove.length; ++i)
					{
						BX.SocNetLogDestination.deleteItem(toRemove[i].itemId, toRemove[i].itemType, me.dialogId);
					}
				});

				BX.cleanNode(items);
				me.cleanValue();
			}

			var container = this.createItemNode({
				text: item.name,
				deleteEvents: {
					click: function(e) {
						if (me.selectOne && me.required)
						{
							me.openDialog();
						}
						else
						{
							me.initDialog(function() {
								BX.SocNetLogDestination.deleteItem(item.id, type, me.dialogId);
								BX.remove(container);
								me.unsetValue(item.entityId);
							});
						}
						BX.PreventDefault(e);
					}
				}
			});

			this.setValue(item.entityId);

			container.setAttribute('data-id', item.id);
			container.setAttribute('data-type', type);

			items.appendChild(container);

			if (!item.entityType)
				item.entityType = type;
		}

		destinationInput.value = '';
		tagNode.innerHTML = this.caller.mess.dlgChange;
	},
	addItems: function(items)
	{
		for(var i = 0; i < items.length; ++i)
		{
			this.addItem(items[i], items[i].entityType)
		}
	},
	openDialog: function(params)
	{
		var me = this;
		this.initDialog(function()
		{
			BX.SocNetLogDestination.openDialog(me.dialogId, params);
		})
	},
	destroy: function()
	{
		if (this.inited)
		{
			if (BX.SocNetLogDestination.isOpenDialog())
			{
				BX.SocNetLogDestination.closeDialog();
			}
			BX.SocNetLogDestination.closeSearch();
		}
	},
	createItemNode: function(options)
	{
		return BX.create('span', {
			attrs: {
				className: 'crm-orderform-popup-autocomplete-item'
			},
			children: [
				BX.create('span', {
					attrs: {
						className: 'crm-orderform-popup-autocomplete-name'
					},
					html: options.text || ''
				}),
				BX.create('span', {
					attrs: {
						className: 'crm-orderform-popup-autocomplete-delete'
					},
					events: options.deleteEvents
				})
			]
		});
	},
	createValueNode: function(valueInputName)
	{
		this.valueNode = BX.create('input', {
			props: {
				type: 'hidden',
				name: valueInputName
			}
		});

		this.container.appendChild(this.valueNode);
	},
	setValue: function(value)
	{
		if (/^\d+$/.test(value) !== true)
			return;

		if (this.selectOne)
			this.valueNode.value = value;
		else
		{
			var i, newVal = [], pairs = this.valueNode.value.split(',');
			for (i = 0; i < pairs.length; ++i)
			{
				if (!pairs[i] || value == pairs[i])
					continue;
				newVal.push(pairs[i]);
			}
			newVal.push(value);
			this.valueNode.value = newVal.join(',');
		}

	},
	unsetValue: function(value)
	{
		if (/^\d+$/.test(value) !== true)
			return;

		if (this.selectOne)
			this.valueNode.value = '';
		else
		{
			var i, newVal = [], pairs = this.valueNode.value.split(',');
			for (i = 0; i < pairs.length; ++i)
			{
				if (!pairs[i] || value == pairs[i])
					continue;
				newVal.push(pairs[i]);
			}
			this.valueNode.value = newVal.join(',');
		}
	},
	cleanValue: function()
	{
		this.valueNode.value = '';
	}
};

function CrmFormAdsForm (params)
{
	this.caller = params.caller;
	this.container = params.container;

	this.init();
}
CrmFormAdsForm.prototype = {

	init: function (params)
	{
		if (!this.container || !this.caller.id)
		{
			return;
		}

		this.attributeButton = 'data-bx-ads-button';
		var buttonNodes = this.container.querySelectorAll('[' + this.attributeButton + ']');
		buttonNodes = BX.convert.nodeListToArray(buttonNodes);
		buttonNodes.forEach(function (buttonNode) {
			var adsType = buttonNode.getAttribute(this.attributeButton);
			BX.bind(buttonNode, 'click', BX.proxy(function () {
				this.showAdsSend(adsType, buttonNode.textContent);
			}, this));
		}, this);

		this.adsPopup = null;
	},

	showAdsSend: function (adsType, title)
	{
		var popup = this.createAdsPopup({
			'title': title
		});

		var contentNode = popup.contentContainer.querySelector('[data-bx-ads-content]');
		var loaderNode = popup.contentContainer.querySelector('[data-bx-ads-loader]');

		contentNode.innerHTML = '';
		loaderNode.style.display = '';
		popup.show();

		var _this = this;
		this.caller.sendActionRequest(
			'showAdsSend',
			{
				'formId': this.caller.id,
				'containerNodeId': contentNode.id,
				'adsType': adsType
			},
			function(data)
			{
				var processed = BX.processHTML(data.html);
				var popup = _this.createAdsPopup();
				contentNode.innerHTML = processed.HTML;
				processed.SCRIPT.forEach(function (scriptData) {
					if (scriptData.isInternal)
					{
						BX.evalGlobal(scriptData.JS);
					}
				});
				loaderNode.style.display = 'none';
				popup.show();
			},
			function (data)
			{
				loaderNode.style.display = 'none';
				this.showErrorPopup(data);
			}
		);
	},

	createAdsPopup: function (data)
	{
		if (this.adsPopup)
		{
			return this.adsPopup;
		}

		var templateNode = BX('crm-orderform-list-template-ads-popup');

		data = data || {};
		this.scriptPopup = BX.PopupWindowManager.create(
			'crm_orderform_list_ads_popup',
			null,
			{
				titleBar: data.title || this.caller.mess.dlgTitle,
				contentColor: 'white',
				content: templateNode.innerHTML,
				width: 620,
				closeIcon: true,
				autoHide: true,
				lightShadow: true,
				closeByEsc: true,
				overlay: {backgroundColor: 'black', opacity: 500}
			}
		);

		//var _this = this;
		var buttons = [];
		buttons.push(new BX.PopupWindowButton({
			text: this.caller.mess.dlgClose,
			events: {click: function(){this.popupWindow.close();}}
		}));
		this.scriptPopup.setButtons(buttons);

		return this.scriptPopup;
	}
};
