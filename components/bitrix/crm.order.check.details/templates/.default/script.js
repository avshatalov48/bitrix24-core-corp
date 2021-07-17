if(typeof(BX.CrmOrderCheckDetails) === "undefined")
{
	BX.CrmOrderCheckDetails = function()
	{
	};

	BX.CrmOrderCheckDetails.Edit = function()
	{
		this._ajaxUrl = null;
		this._isMultiple = false;
		this._data = {
			MAIN: {},
			ADDITION: [],
			TYPE: null,
			ORDER_ID: null
		};
		this._typeList = [];
		this._mainOptionList = [];
		this._additionOptionList = [];
	};

	BX.CrmOrderCheckDetails.Edit.prototype.initialize = function(settings)
	{
		if (BX.type.isPlainObject(settings.DATA))
		{
			if (BX.type.isPlainObject(settings.DATA.MAIN))
			{
				this.setData('MAIN', settings.DATA.MAIN);
			}
			if (BX.type.isArray(settings.DATA.ADDITION))
			{
				this.setData('ADDITION', settings.DATA.ADDITION);
			}
			this.setData('TYPE', settings.DATA.TYPE);
			this.setData('ORDER_ID', settings.DATA.ORDER_ID);
		}

		this._ajaxUrl = settings.AJAX_URL || '';
		this._showUrl = settings.SHOW_URL || '';
		this._typeOptionList = BX.type.isArray(settings.TYPE_LIST) ? settings.TYPE_LIST : [];
		this._mainOptionList = BX.type.isArray(settings.MAIN_LIST) ? settings.MAIN_LIST : [];
		this.initAdditionList(settings.ADDITION_LIST);
		this._isMultiple = (settings.IS_MULTIPLE === 'Y');
		this._typeBlock = BX('crm-order-check-add-type');
		this._mainBlock = BX('crm-order-check-add-main-entity');
		this._additionBlock = BX('crm-order-check-add-addition-entity');
		this._addCheckButton = BX('add_check_button');
		this._cancelCheckButton = BX('cancel_check_button');
		this._isSaving = false;

		this.layout();
	};
	BX.CrmOrderCheckDetails.Edit.prototype.initAdditionList = function(additions)
	{
		var isObject = (typeof {additions:1} === 'object');
		var countElements = 0;
		this._additionOptionList = isObject ? additions : [];

		if (isObject)
		{
			countElements = Object.keys(this._additionOptionList).length;
		}
		else
		{
			countElements = this._additionOptionList.length;
		}

		this._paymentTypeMap = {};
		for (var i=0; i < countElements; i++)
		{
			if (this._additionOptionList[i] && this._additionOptionList[i]['PAYMENT_SELECTED_TYPE'])
			{
				var value = this._additionOptionList[i]['VALUE'];
				this.setPaymentTypeForItem(value, this._additionOptionList[i]['PAYMENT_SELECTED_TYPE']['CODE']);
			}
		}
	};
	BX.CrmOrderCheckDetails.Edit.prototype.layout = function()
	{
		BX.bind(this._addCheckButton, 'click', BX.delegate( this.save, this));
		BX.bind(this._cancelCheckButton, 'click', BX.delegate( this.cancel, this));
		this.bindMainBlockSelect();
		this.bindTypeBlockSelect();
		this.layoutAdditionBlock();
	};

	BX.CrmOrderCheckDetails.Edit.prototype.bindMainBlockSelect = function()
	{
		BX.bind(this._mainBlock, "click", BX.delegate(function(event){
			var selectorId = this._mainBlock.id + "_selector";
			var selectContainer = event.target;
			var menu = [];
			for (var key = 0; key < this._mainOptionList.length; key++)
			{
				var mainElement = this._mainOptionList[key];
				if (
					BX.type.isNotEmptyString(mainElement["NAME"])
					&& BX.type.isNotEmptyString(mainElement["VALUE"])
					&& BX.type.isNotEmptyString(mainElement["TYPE"])
				)
				{
					menu.push({
						text: mainElement["NAME"],
						value: mainElement["VALUE"],
						type: mainElement["TYPE"],
						onclick: BX.delegate( function(e, item)
						{
							var value = {
								VALUE: item.value,
								TYPE: item.type
							};
							this.setData('MAIN', value);
							this.onChangeData();
							selectContainer.innerHTML = item.text;
							this.onMenuClose(selectContainer, selectorId);
						}, this)
					});
				}
			}

			BX.addClass(selectContainer, "active");

			BX.PopupMenu.show(
				selectorId,
				selectContainer,
				menu,
				{
					angle: false, width: selectContainer.offsetWidth + 'px',
					events: {
						onPopupClose: BX.delegate(
							function ()
							{
								this.onMenuClose(selectContainer, selectorId)
							},
							this
						)
					}
				}
			);
		}, this));
	};

	BX.CrmOrderCheckDetails.Edit.prototype.bindTypeBlockSelect = function()
	{
		BX.bind(this._typeBlock, "click", BX.delegate(function(event){
			var selectorId = this._typeBlock.id + "_selector";
			var selectContainer = event.target;
			var menu = [];
			for (var key = 0; key < this._typeOptionList.length; key++)
			{
				var typeElement = this._typeOptionList[key];
				if (
					BX.type.isNotEmptyString(typeElement["NAME"])
					&& BX.type.isNotEmptyString(typeElement["VALUE"])
				)
				{
					menu.push({
						text: typeElement["NAME"],
						value: typeElement["VALUE"],
						onclick: BX.delegate( function(e, item)
						{
							this.setData('TYPE', item.value);
							this.onChangeData();
							selectContainer.innerHTML = BX.util.htmlspecialchars(item.text);
							this.onMenuClose(selectContainer, selectorId);
						}, this)
					});
				}
			}

			BX.addClass(selectContainer, "active");

			BX.PopupMenu.show(
				selectorId,
				selectContainer,
				menu,
				{
					angle: false, width: selectContainer.offsetWidth + 'px',
					events: {
						onPopupClose: BX.delegate(
							function ()
							{
								this.onMenuClose(selectContainer, selectorId)
							},
							this
						)
					}
				}
			);
		}, this));
	};

	BX.CrmOrderCheckDetails.Edit.prototype.layoutAdditionBlock = function()
	{
		this._additionBlock.innerHTML = '';

		var isObject = (typeof {additions:1} === 'object');
		var additionListLength = 0;

		if (isObject)
		{
			additionListLength = Object.keys(this._additionOptionList).length;
		}
		else
		{
			additionListLength = this._additionOptionList.length;
		}

		for (var i = 0; i <= additionListLength; i++)
		{
			if (!this._additionOptionList[i])
			{
				continue;
			}
			var elementAddition = this._additionOptionList[i];
			var row = BX.create("label", {
				children:[
					BX.create("input",	{
						attrs:
							{
								className: "fields boolean",
								type: 'checkbox',
								'bx-type':  elementAddition['TYPE'],
								value: elementAddition['VALUE']
							}
					}),
					BX.create("span",
						{
							html: elementAddition['NAME']
						}
					)
				],
				events:
					{
						click: BX.delegate(this.onAdditionSelect, this)
					}
			});


			var element = BX.create("DIV",	{
					attrs: { className: "fields boolean field-wrap" },
					children:
						[
							BX.create("span", {
								attrs:	{className: "fields boolean field-item"},
								children: [row]
							})
						]
				}
			);

			if (BX.type.isArray(elementAddition['PAYMENT_TYPES']))
			{
				var selectType = BX.create("span", {
					attrs: {
						className: "crm-entity-widget-content-select",
						'bx-value':  (i).toString()
					},
					text: this._additionOptionList[i]['PAYMENT_TYPES'][0]['NAME'],
					events:
						{
							click: BX.delegate(function(event){
								var selectorId = "check_addition_payment_type_selector";
								var selectContainer = event.target;
								var i = selectContainer.getAttribute('bx-value');
								var paymentTypeList = this._additionOptionList[i]['PAYMENT_TYPES'];
								var menu = [];
								for (var key = 0; key < paymentTypeList.length; key++)
								{
									var paymentType = paymentTypeList[key];
									if (
										BX.type.isNotEmptyString(paymentType["NAME"])
										&& BX.type.isNotEmptyString(paymentType["CODE"])
									)
									{
										menu.push({
											text: paymentType["NAME"],
											value: paymentType["CODE"],
											element: paymentType["ENTITY_ID"],
											onclick: BX.delegate( function(e, item)
											{
												this.setPaymentTypeForItem(item.element, item.value);
												selectContainer.innerHTML = BX.util.htmlspecialchars(item.text);
												this.refreshAdditionList();
												this.onMenuClose(selectContainer, selectorId);
											}, this)
										});
									}
								}

								BX.addClass(selectContainer, "active");

								BX.PopupMenu.show(
									selectorId,
									selectContainer,
									menu,
									{
										angle: false, width: selectContainer.offsetWidth + 'px',
										events: {
											onPopupClose: BX.delegate(
												function ()
												{
													this.onMenuClose(selectContainer, selectorId)
												},
												this
											)
										}
									}
								);
							}, this)
						}
				});
				var selectWrapper = BX.create("label", {children:[selectType]});
				element.appendChild(selectWrapper);
			}

			this._additionBlock.appendChild(element);
		}

		if (additionListLength > 0 && BX.hasClass(BX('crm-order-check-add-addition-title'), 'crm-entity-widget-content-block-hidden-title'))
		{
			BX.removeClass(BX('crm-order-check-add-addition-title'), 'crm-entity-widget-content-block-hidden-title');
		}

		if (additionListLength === 0)
		{
			BX.addClass(BX('crm-order-check-add-addition-title'), 'crm-entity-widget-content-block-hidden-title');
		}
	};

	BX.CrmOrderCheckDetails.Edit.prototype.setData = function(code, value)
	{
		this._data[code] = value
	};
	BX.CrmOrderCheckDetails.Edit.prototype.getPaymentTypeFromMap = function(id)
	{
		return this._paymentTypeMap[id] || null;
	};

	BX.CrmOrderCheckDetails.Edit.prototype.setPaymentTypeForItem = function(id, type)
	{
		this._paymentTypeMap[id] = type
	};
	BX.CrmOrderCheckDetails.Edit.prototype.onChangeData = function()
	{
		var action = 'GET_CHECK_DATA';
		var callback = BX.delegate(this.refreshLayout, this);
		this.sendData(action, callback);
	};
	BX.CrmOrderCheckDetails.Edit.prototype.save = function()
	{
		if (this._isSaving)
		{
			return;
		}
		this._isSaving = true;
		BX.addClass(this._addCheckButton, 'ui-btn-wait');
		var action = 'SAVE_CHECK';
		var callback = BX.delegate(this.onSave, this);
		this.sendData(action, callback);
	};
	BX.CrmOrderCheckDetails.Edit.prototype.cancel = function()
	{
		window.top.BX.SidePanel.Instance.close();
	};
	BX.CrmOrderCheckDetails.Edit.prototype.sendData = function(action, callback)
	{
		var data = this._data;
		data.ACTION = action;
		BX.ajax(
			{
				url: this._ajaxUrl,
				method: 'POST',
				dataType: 'json',
				data: data,
				onsuccess: callback,
				onfailure: BX.delegate(this.showError, this)
			});
	};
	BX.CrmOrderCheckDetails.Edit.prototype.onMenuClose = function(node, selectorId)
	{
		BX.removeClass(node, "active");
		BX.PopupMenu.destroy(selectorId);
	};

	BX.CrmOrderCheckDetails.Edit.prototype.onAdditionSelect = function(e)
	{
		var additionList = [];
		if (!BX.type.isNotEmptyString(e.target.value))
		{
			return;
		}

		var checkedList = this._additionBlock.querySelectorAll('input:checked');
		for (var i = 0; i < checkedList.length; i++)
		{
			var node = checkedList[i];
			node.checked = '';
			if (node.value	&& (this._isMultiple || node.value === e.target.value))
			{
				node.checked = 'checked';
			}
		}
		this.setData('ADDITION', additionList);
		this.refreshAdditionList();
	};
	BX.CrmOrderCheckDetails.Edit.prototype.refreshAdditionList = function()
	{
		var additionList = [];
		var checkedList = this._additionBlock.querySelectorAll('input:checked');
		for (var i = 0; i < checkedList.length; i++)
		{
			var node = checkedList[i];
			if (node.value)
			{
				var paymentType = null;
				if (BX.type.isNotEmptyString(this.getPaymentTypeFromMap(node.value)))
				{
					paymentType = this.getPaymentTypeFromMap(node.value);
				}
				additionList.push({
					TYPE: node.getAttribute('bx-type'),
					VALUE: node.value,
					PAYMENT_TYPE: paymentType
				});
			}
		}
		this.setData('ADDITION', additionList);
	};
	BX.CrmOrderCheckDetails.Edit.prototype.getMessage = function(name)
	{
		var m = BX.CrmOrderCheckDetails.Edit.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	BX.CrmOrderCheckDetails.Edit.prototype.refreshLayout = function(result)
	{
		if (BX.type.isPlainObject(result.DATA))
		{
			this._typeOptionList = result.DATA.CHECK_TYPES;
			this.initAdditionList(result.DATA.ADDITION_LIST);
			this.setData('ADDITION', []);
			this.setData('TYPE', result.DATA.CURRENT_TYPE);

			if (BX.type.isNotEmptyString(result.DATA.CURRENT_TYPE_NAME))
			{
				this._typeBlock.innerHTML = '';
				this._typeBlock.appendChild(
					BX.create("div", {
						attrs: {className: "crm-entity-widget-content-select"},
						text: BX.util.htmlspecialchars(result.DATA.CURRENT_TYPE_NAME)
					})
				);
			}

			this.layoutAdditionBlock();
		}
		else if (BX.type.isNotEmptyString(result.ERROR))
		{
			this.showError(result.ERROR);
		}
	};

	BX.CrmOrderCheckDetails.Edit.prototype.showError =  function(error)
	{
		this.clearError();
		BX('check_form_error_block').appendChild(
			BX.create('div',{
				attrs: {className: 'crm-entity-widget-content-error-text'},
				text: BX.util.htmlspecialchars(error)
			})
		)
	};

	BX.CrmOrderCheckDetails.Edit.prototype.clearError =  function()
	{
		BX('check_form_error_block').innerHTML = "";
	};

	BX.CrmOrderCheckDetails.Edit.prototype.onSave = function(result)
	{
		if (BX.type.isNotEmptyString(result.ERROR))
		{
			this._isSaving = false;
			BX.removeClass(this._addCheckButton, 'ui-btn-wait');
			this.showError(result.ERROR);
			return;
		}

		var id = result.ID;

		var eventData = {
			check_id: id
		};

		window.top.BX.SidePanel.Instance.postMessage(
			window,
			'CrmOrderPaymentCheck::Update',
			eventData
		);

		this.cancel();

	};

	BX.CrmOrderCheckDetails.Edit.create = function(settings)
	{
		var self = new BX.CrmOrderCheckDetails.Edit();
		self.initialize(settings);
		return self;
	};
}