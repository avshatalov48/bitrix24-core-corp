BX.namespace("BX.Crm");

if(typeof BX.Crm.EntityEditorOrderController === "undefined")
{
	BX.Crm.EntityEditorOrderController = function()
	{
		BX.Crm.EntityEditorOrderController.superclass.constructor.apply(this);
		this._loaderController = BX.Crm.EntityEditorOrderLoaderController.create();
		this._productList = null;
		this._isRequesting = false;
		this._isCreateMode = false;
		this._editorModeChangeHandler = BX.delegate(this.onEditorModeChange, this);
		this._editorControlChangeHandler = BX.delegate(this.onEditorControlChange, this);
		this._ajaxOptsPreset = {
			needFormData: true,
			needProductComponentParams: true,
			needToolPanel: true
		};
	};

	BX.extend(BX.Crm.EntityEditorOrderController, BX.UI.EntityEditorController);

	BX.Crm.EntityEditorOrderController.prototype.isProductListLoaded = function()
	{
		return this._productList !== null;
	}

	BX.Crm.EntityEditorOrderController.prototype.setProductList = function(productList)
	{
		this._productList = productList;
	};

	BX.Crm.EntityEditorOrderController.prototype.doInitialize = function()
	{
		BX.Crm.EntityEditorOrderController.superclass.doInitialize.apply(this);
		BX.onCustomEvent(window, "EntityEditorOrderController", [this]);
		BX.addCustomEvent(window, BX.Crm.EntityEvent.names.create, BX.delegate(this.onAfterCreate, this));
		BX.addCustomEvent(window, BX.Crm.EntityEvent.names.update, BX.delegate(this.onAfterUpdate, this));
		BX.addCustomEvent("onPullEvent-crm", BX.delegate(this.onPullEvent, this));
		this._editor.addModeChangeListener(this._editorModeChangeHandler);
		window['ConnectedEntityController'] = this;
		this._model.lockField('PRICE');
		this._isCreateMode = this._model.getField('ID') <= 0;

		if(!this._isCreateMode)
		{
			this._model.lockField('CURRENCY');
		}
		else
		{
			BX.addCustomEvent(window, "onDeliveryExtraServiceValueChange", BX.delegate(this.onDeliveryExtraServiceValueChange, this));
		}

		if (this.getConfigStringParam("isSalesCenterOrder", "") === 'Y')
		{
			this._model.lockField('USER_ID');
			this._model.lockField('TRADING_PLATFORM');
		}
	};

	BX.Crm.EntityEditorOrderController.prototype.onPullEvent = function(command, params)
	{
		if (command !== "onOrderSave" && command !== "onOrderPaymentSave" && command !== "onOrderShipmentSave")
		{
			return;
		}

		if(this._editor.isRequestRunning())
		{
			return;
		}

		if(!params.FIELDS || !params.FIELDS.ID || params.FIELDS.ID !== this._model.getField('ID'))
		{
			return;
		}

		if(command === "onOrderSave") // we can check this case
		{
			var d1 = new Date(BX.parseDate(this._model.getField('DATE_UPDATE')));
			var d2 = new Date(params.FIELDS.DATE_UPDATE);

			if(!d1 || !d2
				|| typeof d1 !== 'object'
				|| typeof d2 !== 'object'
				|| d1.getTime() === d2.getTime())
			{
				return;
			}
		}

		if(!this._editor.isChanged())
		{
			this.loadOrder();
		}
		else
		{
			//todo:
			//alert('Order was just changed'); but what next?
		}
	};

	BX.Crm.EntityEditorOrderController.prototype.onDeliveryExtraServiceValueChange = function()
	{
		this.onDataChanged();
	};

	BX.Crm.EntityEditorOrderController.prototype.onEditorModeChange = function(sender)
	{
		if(this._editor.getMode() === BX.UI.EntityEditorMode.edit)
		{
			this._editor.addControlChangeListener(this._editorControlChangeHandler);
		}
		else
		{
			this._editor.removeControlChangeListener(this._editorControlChangeHandler);
		}
	};

	BX.Crm.EntityEditorOrderController.prototype.onEditorControlChange = function(sender, params)
	{
		var name = BX.prop.getString(params, "fieldName", "");
		if(name !== "CURRENCY")
		{
			return;
		}

		var currencyId = BX.prop.getString(params, "fieldValue", "");

		if(currencyId !== "")
		{
			this._editor._model.setField('CURRENCY', currencyId, {enableNotification: false});
			this.onDataChanged();
		}
	};

	BX.Crm.EntityEditorOrderController.prototype.onAfterCreate = function(params)
	{
		if(BX.prop.getInteger(params, "entityTypeId", 0) !== BX.CrmEntityType.enumeration.order)
		{
			return;
		}

		if(params && params.entityData && this._productList)
		{
			this._productList.setFormData(params.entityData);
		}
	};

	BX.Crm.EntityEditorOrderController.prototype.onAfterUpdate = function(params)
	{
		if(BX.prop.getInteger(params, "entityTypeId", 0) !== BX.CrmEntityType.enumeration.order)
		{
			return;
		}

		if(params && params.entityData && this._productList)
		{
			this._productList.setFormData(params.entityData);
		}
	};

	BX.Crm.EntityEditorOrderController.prototype.hideLoader = function()
	{
		this._loaderController.hideLoader();
	};

	BX.Crm.EntityEditorOrderController.prototype.showLoader = function()
	{
		this._loaderController.showLoader();
	};

	BX.Crm.EntityEditorOrderController.prototype.onProductAdd = function(productId, quantity, useMerge)
	{
		this.ajax(
			'addProduct',
			{data: {PRODUCT_ID: productId, QUANTITY: quantity, USE_MERGE: useMerge || 'Y'}},
			this._ajaxOptsPreset
		);
	};

	BX.Crm.EntityEditorOrderController.prototype.onProductCreate = function(productData)
	{
		if (BX.type.isNotEmptyObject(productData))
		{
			this.ajax(
				'createProduct',
				{data: {PRODUCT_DATA: productData}},
				this._ajaxOptsPreset
			);
		}
	};
	BX.Crm.EntityEditorOrderController.prototype.onProductUpdate = function(basketId, productData)
	{
		if (BX.type.isNotEmptyObject(productData))
		{
			this.ajax(
				'updateProduct', {
					data: {
						PRODUCT_DATA: productData,
						BASKET_ID: basketId
					}
				},
				this._ajaxOptsPreset
			);
		}
	};

	BX.Crm.EntityEditorOrderController.prototype.onChangeDelivery = function(index)
	{
		this.ajax(
			'changeDelivery',
			{data: {INDEX: index}},
			this._ajaxOptsPreset
		);
	};

	BX.Crm.EntityEditorOrderController.prototype.onProductDelete = function(basketCode)
	{
		this.ajax(
			'deleteProduct',
			{data:{BASKET_CODE: basketCode}},
			this._ajaxOptsPreset
		);
	};

	BX.Crm.EntityEditorOrderController.prototype.onProductGroupAction = function(basketCodes, action, forAll)
	{
		this.ajax(
			'productGroup',
			{
				data:{
					BASKET_CODES: basketCodes,
					GROUP_ACTION: action,
					FOR_ALL: forAll
				}
			},
			this._ajaxOptsPreset
		);
	};

	BX.Crm.EntityEditorOrderController.prototype.innerCancel = function()
	{
		BX.onCustomEvent(window, "EntityEditorOrderController:onInnerCancel", [this]);
		var changedData = [];
		for(var i = 0, length = this._editor._activeControls.length; i < length; i++)
		{
			var control = this._editor._activeControls[i];
			if(control.isChanged())
			{
				var value = this.getControlValue(control);
				for (var j in value)
				{
					if (value.hasOwnProperty(j))
					{
						changedData[j] = value[j];
					}
				}
			}
		}

		var options = this._ajaxOptsPreset;
		options.skipMarkAsChanged = true;

		this.ajax(
			'rollback',
			{data:{CHANGED_DATA: changedData}},
			options
		);
	};

	BX.Crm.EntityEditorOrderController.prototype.onCouponDelete = function(coupon)
	{
		this.ajax(
			'deleteCoupon',
			{data:{COUPON: coupon}},
			this._ajaxOptsPreset
		);
	};

	BX.Crm.EntityEditorOrderController.prototype.onCouponAdd = function(coupon)
	{
		this.ajax(
			'addCoupon',
			{data:{COUPON: coupon}},
			this._ajaxOptsPreset
		);
	};

	BX.Crm.EntityEditorOrderController.prototype.onRefreshOrderDataAndSave = function()
	{
		this._editor.getFormElement().appendChild(
			BX.create(
				"input",
				{
					attrs: {
						type: "hidden",
						name: 'REFRESH_ORDER_DATA_AND_SAVE',
						value: 'Y'
					}
				}
			)
		);

		this._editor.save();
	};

	BX.Crm.EntityEditorOrderController.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorOrderController.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};

	BX.Crm.EntityEditorOrderController.prototype.openDetailSlider = function(sliderUrl)
	{
		if(this._editor.isChanged())
		{
			BX.UI.EditorAuxiliaryDialog.create(
				"order_save_confirmation",
				{
					title: this.getMessage('saveChanges'),
					content: this.getMessage('saveConfirm'),
					buttons:
						[
							{
								id: "save",
								type: BX.Crm.DialogButtonType.accept,
								text: this.getMessage('save'),
								callback: BX.proxy(function(button){
										this._editor.saveChanged();
										BX.Crm.Page.openSlider(sliderUrl);
										button.getDialog().close();
									},
									this)
							},
							{
								id: "cancel",
								type: BX.Crm.DialogButtonType.cancel,
								text: this.getMessage('notSave'),
								callback: function(button){
									BX.Crm.Page.openSlider(sliderUrl);
									button.getDialog().close();
								}
							}
						]
				}
			).open();
		}
		else
		{
			BX.Crm.Page.openSlider(sliderUrl);
		}
	};

	BX.Crm.EntityEditorOrderController.prototype.ajax = function(action, ajaxParams, options)
	{
		if(!action)
		{
			throw 'action must be defined!';
		}

		if(this.isLockedSending())
		{
			return;
		}

		this.lockSending();
		options = options || {};

		if(typeof options.showLoader === 'undefined')
		{
			options.showLoader = true;
		}

		if(options.showLoader)
		{
			this._loaderController.showLoader();
		}

		var data = {
			ACTION: action,
			sessid: BX.bitrix_sessid()
		};

		if(options.needFormData)
		{
			data.FORM_DATA = this.demandFormData();
		}

		if(options.needProductComponentParams)
		{
			var context = this._editor.getContext();

			if(context.PRODUCT_COMPONENT_DATA)
			{
				data['PRODUCT_COMPONENT_DATA'] = context.PRODUCT_COMPONENT_DATA;
			}
		}

		if(typeof (ajaxParams.data) === 'object')
		{
			for(var i in ajaxParams.data)
			{
				if(ajaxParams.data.hasOwnProperty(i))
				{
					data[i] = ajaxParams.data[i];
				}
			}
		}

		BX.ajax({
			url: this.getConfigStringParam("serviceUrl", ""),
			method: "POST",
			dataType: "json",
			data: data,
			onsuccess: ajaxParams.onsuccess ? ajaxParams.onsuccess : BX.proxy(function(result) { this.onSendDataSuccess(result, options); }, this),
			onfailure: ajaxParams.onfailure ? ajaxParams.onfailure : BX.delegate(this.onSendDataFailure, this)
		});

		if(options.needToolPanel)
		{
			this._editor.showToolPanel();
		}
	};

	BX.Crm.EntityEditorOrderController.prototype.isLockedSending = function()
	{
		return this._isRequesting;
	};

	BX.Crm.EntityEditorOrderController.prototype.lockSending = function()
	{
		this._isRequesting = true;
	};

	BX.Crm.EntityEditorOrderController.prototype.unlockSending = function()
	{
		this._isRequesting = false;
	};

	BX.Crm.EntityEditorOrderController.prototype.loadOrder = function()
	{
		this.ajax(
			'loadOrder',
			{
				data: {
					ORDER_ID: this._model.getField('ID')
				}
			},
			{
				showLoader: false,
				needToolPanel: false,
				skipMarkAsChanged: true,
				needFormData: false,
				needProductComponentParams: true
			}
		);
	};

	BX.Crm.EntityEditorOrderController.prototype.onSkuSelect = function(params)
	{
		this.ajax(
			'skuSelect',
			{
				data: {
					SKU_PROPS: params.SKU_PROPS,
					PRODUCT_ID: params.PRODUCT_ID,
					SKU_ORDER: params.SKU_ORDER,
					CHANGED_SKU_ID: params.CHANGED_SKU_ID,
					BASKET_CODE: params.BASKET_CODE
				}
			},
			this._ajaxOptsPreset
		);
	};

	BX.Crm.EntityEditorOrderController.prototype.demandFormData = function()
	{
		var formData = BX.clone(this._editor._model.getData()),
			controls = this._editor.getControls(),
			i,
			skipFields = {
				'DELIVERY_SERVICES_LIST': true,
				'EXTRA_SERVICES_HTML': true,
				'STATUS_CONTROL': true
			};

		for (i=0; i < controls.length; i++)
		{
			var controlValues = this.getControlValue(controls[i]);

			for (var key in controlValues)
			{
				if(controlValues.hasOwnProperty(key))
				{
					if(!skipFields[key])
					{
						formData[key] = controlValues[key];
					}
				}
			}
		}

		var form = this._editor.getFormElement();
		var prepared = BX.ajax.prepareForm(form);

		if(this._isCreateMode)
		{
			if(form)
			{
				if(prepared && prepared.data)
				{
					if(prepared.data.SHIPMENT && prepared.data.SHIPMENT[0] && prepared.data.SHIPMENT[0].EXTRA_SERVICES)
					{
						formData.SHIPMENT[0].EXTRA_SERVICES = prepared.data.SHIPMENT[0].EXTRA_SERVICES;
					}
				}
			}
		}
		else
		{
			/*
			 *	For order edit mode, when payments sum are only for reading.
			 *	This prevents collision with order price changing.
			 */
			if(typeof(formData.PAYMENT) !== 'undefined')
			{
				for(i in formData.PAYMENT)
				{
					if(formData.PAYMENT[i]['SUM'])
					{
						delete (formData.PAYMENT[i]['SUM']);
					}
				}
			}
		}

		if(form)
		{
			if(prepared.data.DISCOUNTS && prepared.data.DISCOUNTS.DELIVERY)
			{
				if(!formData.DISCOUNTS)
				{
					formData.DISCOUNTS = {};
				}

				formData.DISCOUNTS.DELIVERY = prepared.data.DISCOUNTS.DELIVERY;
			}
		}

		formData['SITE_ID'] = this.getSiteId();
		var itemsData = {};

		if(this._productList)
		{
			itemsData = BX.mergeEx(itemsData, this._productList.getFormData());
		}

		formData = BX.mergeEx(itemsData, formData);

		return formData;
	};

	BX.Crm.EntityEditorOrderController.prototype.getControlValue = function(field)
	{
		var value = [];

		if (!field instanceof BX.Crm.EntityEditorControl)
			return value;

		var childrenValue = [];

		if (field instanceof BX.Crm.EntityEditorSection || field instanceof BX.UI.EntityEditorColumn)
		{
			var children = field.getChildren();
			for (var i=0; i < field.getChildCount(); i++)
			{
				childrenValue = this.getControlValue(children[i]);
				for (var key in childrenValue)
				{
					if(childrenValue.hasOwnProperty(key))
					{
						value[key] = childrenValue[key];
					}
				}
			}
		}
		else if (field.isChanged())
		{
			value[field.getName()] = field.getRuntimeValue();
		}

		return value;
	};

	BX.Crm.EntityEditorOrderController.prototype.getSiteId = function()
	{
		var context = this._editor.getContext();
		return context.SITE_ID ? context.SITE_ID : '';
	};

	BX.Crm.EntityEditorOrderController.prototype.onDataChanged = function(additional)
	{
		var data = {};

		if (BX.type.isNotEmptyObject(additional))
		{
			if(additional.actionBefore)
			{
				data['ACTION_BEFORE'] = additional.actionBefore;
			}

			if(additional.actionAfter)
			{
				data['ACTION_AFTER'] = additional.actionAfter;
			}

			if(additional.data)
			{
				data['ADDITIONAL_DATA'] = additional.data;
			}
		}

		this.ajax(
			'refreshOrderData',
			{
				data: data,
				onsuccess: BX.proxy(function(result){

					if (BX.type.isNotEmptyObject(additional)
						&& additional.callbackBefore
						&& typeof additional.callbackBefore === "function"
					)
					{
						additional.callbackBefore.call(null, result);
					}

					this.onSendDataSuccess(result);

					if (BX.type.isNotEmptyObject(additional)
						&& additional.callbackAfter
						&& typeof additional.callbackAfter === "function"
					)
					{
						additional.callbackAfter.call(null, result);
					}
				}, this)
			},
			this._ajaxOptsPreset
		);
	};

	BX.Crm.EntityEditorOrderController.prototype.onSendDataSuccess = function(result, options)
	{
		var skipMarkAsChanged = (options && options.skipMarkAsChanged) || false;
		this.unlockSending();
		this._loaderController.hideLoader();
		this._editor._toolPanel.clearErrors();

		if(result)
		{
			if(result.ERROR)
			{
				if(!this._editor._toolPanel.isVisible())
				{
					this._editor._toolPanel.setVisible(true);
				}

				this._editor._toolPanel.addError(result.ERROR);
			}

			if(result.ORDER_DATA)
			{
				this._model.setData(result.ORDER_DATA);

				if(!skipMarkAsChanged)
				{
					this.markAsChanged();
				}

				if(result.ORDER_DATA.PROPERTIES_SCHEME)
				{
					setTimeout(
						BX.delegate(function(){
							BX.onCustomEvent(
								window,
								"Crm.OrderModel.ChangePropertyScheme",
								[ result.ORDER_DATA.PROPERTIES_SCHEME ]
							);
						}, this),
						0
					);
				}

				if(result.ORDER_DATA.SHIPMENT_PROPERTIES_SCHEME)
				{
					setTimeout(
						BX.delegate(function(){
							BX.onCustomEvent(
								window,
								"Crm.ShipmentModel.ChangePropertyScheme",
								[ result.ORDER_DATA.SHIPMENT_PROPERTIES_SCHEME ]
							);
						}, this),
						0
					);
				}

				if (result.ORDER_DATA.USER_PROFILE_LIST)
				{
					var profiles = BX.prop.getArray(result.ORDER_DATA, "USER_PROFILE_LIST");
					var controls = this._editor.getControls();
					for (var i=0; i < controls.length; i++)
					{
						var field = controls[i].getChildById('USER_PROFILE');
						if (field)
						{
							field._items = null;
							field._schemeElement.setData({items: profiles});
							if (BX.type.isNotEmptyString(result.ORDER_DATA.USER_PROFILE))
							{
								this._model.setField('USER_PROFILE', result.ORDER_DATA.USER_PROFILE, { enableNotification: false });
							}
							field.refreshLayout();
						}
					}
				}

				if(this._productList)
				{
					this._productList.setFormData(result);
				}
			}
		}
	};

	BX.Crm.EntityEditorOrderController.prototype.onSendDataFailure = function(type, e)
	{
		this.unlockSending();
		this._loaderController.hideLoader();
		BX.debug(e.message);
	};

	BX.Crm.EntityEditorOrderController.prototype.onBeforeSubmit = function()
	{
		this.setFormField(
			this.getConfigStringParam("dataFieldName", ""),
			'['+JSON.stringify(this.demandFormData())+']'
		);
		if (this.getConfigStringParam("isSalesCenterOrder", "") === 'Y')
		{
			this.setFormField(
				'SALES_CENTER_SESSION_ID',
				this.getConfigStringParam("salesCenterSessionId", "")
			);
		}
	};

	BX.Crm.EntityEditorOrderController.prototype.setFormField = function(fieldName, value)
	{
		var form = this._editor.getFormElement();

		if(form.elements[fieldName])
		{
			form.elements[fieldName].value = value;
		}
		else
		{
			form.appendChild(BX.create("input",	{
				attrs: {type: "hidden", name: fieldName, value: value}
			}));
		}
	};

	BX.Crm.EntityEditorOrderController.prototype.onBeforesSaveControl = function(data)
	{
		data[this.getConfigStringParam("dataFieldName", "")] = JSON.stringify([this.demandFormData()]);
		return data;
	};

	BX.Crm.EntityEditorOrderController.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorOrderController();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorOrderShipmentController === "undefined")
{
	BX.Crm.EntityEditorOrderShipmentController = function()
	{
		BX.Crm.EntityEditorOrderShipmentController.superclass.constructor.apply(this);

		this._loaderController = BX.Crm.EntityEditorOrderLoaderController.create();
		this._productList = null;
		this._isRequesting = false;
	};

	BX.extend(BX.Crm.EntityEditorOrderShipmentController, BX.UI.EntityEditorController);

	BX.Crm.EntityEditorOrderShipmentController.prototype.setProductList = function(productList)
	{
		this._productList = productList;
		this._editor.getFormElement().appendChild(
			BX.create('input', {props: {
				name: 'IS_PRODUCT_LIST_LOADED',
				value: 'Y',
				type: "hidden"
			}})
		);
	};

	BX.Crm.EntityEditorOrderShipmentController.prototype.doInitialize = function()
	{
		BX.onCustomEvent(window, "onEntityEditorOrderShipmentControllerInit", [this]);
		BX.addCustomEvent(window, "onDeliveryExtraServiceValueChange", BX.delegate(this.onDeliveryExtraServiceValueChange, this));
		BX.addCustomEvent(window, "onDeliveryPriceRecalculateClicked", BX.delegate(this.onDeliveryPriceRecalculateClicked, this));
		BX.addCustomEvent(window, BX.Crm.EntityEvent.names.create, BX.delegate(this.onAfterCreate, this));
		BX.addCustomEvent(window, BX.Crm.EntityEvent.names.update, BX.delegate(this.onAfterUpdate, this));
		window['EntityEditorOrderShipmentController'] = this;

		if (this.isLockCurrency())
		{
			this.lockCurrency();
		}
	};

	BX.Crm.EntityEditorOrderShipmentController.prototype.isLockCurrency = function()
	{
		return true;
	}

	BX.Crm.EntityEditorOrderShipmentController.prototype.lockCurrency = function()
	{
		this._model.lockField('CURRENCY');
	}

	BX.Crm.EntityEditorOrderShipmentController.prototype.onAfterCreate = function(params)
	{
		if(BX.prop.getInteger(params, "entityTypeId", 0) !== BX.CrmEntityType.enumeration.ordershipment)
		{
			return;
		}

		if(params && params.entityData && this._productList)
		{
			this._productList.setFormData(params.entityData);
		}
	};

	BX.Crm.EntityEditorOrderShipmentController.prototype.onAfterUpdate = function(params)
	{
		if(BX.prop.getInteger(params, "entityTypeId", 0) !== BX.CrmEntityType.enumeration.ordershipment)
		{
			return;
		}

		if(params && params.entityData && this._productList)
		{
			this._productList.setFormData(params.entityData);
		}
	};

	BX.Crm.EntityEditorOrderShipmentController.prototype.onDeliveryExtraServiceValueChange = function()
	{
		this.onDataChanged();
	};

	BX.Crm.EntityEditorOrderShipmentController.prototype.onDeliveryPriceRecalculateClicked = function()
	{
		this.onDataChanged({}, {showLoader: true});
	};

	BX.Crm.EntityEditorOrderShipmentController.prototype.innerCancel = function()
	{
		this.ajax('rollback', {}, { skipMarkAsChanged: true});
	};

	BX.Crm.EntityEditorOrderShipmentController.prototype.onBeforeSubmit = function()
	{
		var priceDelivery = this._editor.getControlById('PRICE_DELIVERY_WITH_CURRENCY');

		if(priceDelivery && priceDelivery.isChanged())
		{
			this.setCustomPriceDelivery();
		}

		if(this._productList)
		{
			var value = '['+JSON.stringify(this._productList.getFormData())+']',
				form = this._editor.getFormElement(),
				dataFieldName = this.getConfigStringParam("productDataFieldName", "");

			if(form.elements[dataFieldName])
			{
				form.elements[dataFieldName].value = value;
			}
			else
			{
				form.appendChild(
					BX.create(
						"input",
						{
							attrs: {
								type: "hidden",
								name: dataFieldName,
								value: value
							}
						}
					)
				);
			}
		}
	};

	BX.Crm.EntityEditorOrderShipmentController.prototype.onChangeDelivery = function()
	{
		this.ajax('changeDelivery');
	};

	BX.Crm.EntityEditorOrderShipmentController.prototype.setCustomPriceDelivery = function()
	{
		var form = this._editor.getFormElement();

		if(form.elements['CUSTOM_PRICE_DELIVERY'])
		{
			form.elements['CUSTOM_PRICE_DELIVERY'].value = 'Y';
		}
		else
		{
			form.appendChild(
				BX.create(
					"input",
					{
						attrs: {
							type: "hidden",
							name: 'CUSTOM_PRICE_DELIVERY',
							value: 'Y'
						}
					}
				)
			);
		}
	};

	BX.Crm.EntityEditorOrderShipmentController.prototype.onProductAdd = function(basketId)
	{
		this.ajax(
			'addProduct',
			{data: {BASKET_ID: basketId}}
		);
	};

	BX.Crm.EntityEditorOrderShipmentController.prototype.onProductDelete = function(basketCode)
	{
		this.ajax(
			'deleteProduct',
			{data: { BASKET_CODE: basketCode } }
		);
	};

	BX.Crm.EntityEditorOrderShipmentController.prototype.ajax = function(action, ajaxParams, options)
	{
		if(!action)
		{
			throw 'action must be defined!';
		}

		if(this._isRequesting)
		{
			return;
		}

		this._isRequesting = true;

		ajaxParams = ajaxParams || {};
		options = options || {};

		if(typeof options.showLoader === 'undefined')
		{
			options.showLoader = false;
		}

		if(options.showLoader)
		{
			this._loaderController.showLoader();
		}

		var data = {
			ACTION: action,
			sessid: BX.bitrix_sessid()
		};

		data.FORM_DATA = this.demandFormData();

		//todo: check if component is loaded
		var context = this._editor.getContext();

		if(context.PRODUCT_COMPONENT_DATA)
		{
			data['PRODUCT_COMPONENT_DATA'] = context.PRODUCT_COMPONENT_DATA;
		}

		if(typeof (ajaxParams.data) === 'object')
		{
			for(var i in ajaxParams.data)
			{
				if(ajaxParams.data.hasOwnProperty(i))
				{
					data[i] = ajaxParams.data[i];
				}
			}
		}

		BX.ajax({
			url: this.getConfigStringParam("serviceUrl", ""),
			method: "POST",
			dataType: "json",
			data: data,
			onsuccess: ajaxParams.onsuccess ? ajaxParams.onsuccess : BX.proxy(function(result){ this.onSendDataSuccess(result, options)}, this),
			onfailure: ajaxParams.onfailure ? ajaxParams.onfailure : BX.delegate(this.onSendDataFailure, this)
		});
	};

	BX.Crm.EntityEditorOrderShipmentController.prototype.demandFormData = function()
	{
		var formData = this._editor._model.getData(),
			controls = this._editor.getControls(),
			i,
			skipFields = {
				'DELIVERY_SERVICES_LIST': true,
				'EXTRA_SERVICES_DATA': true,
				'STATUS_CONTROL': true
			};

		var context = this._editor.getContext();
		formData['ORDER_ID'] = context.ORDER_ID ? context.ORDER_ID : 0;

		for (i=0; i < controls.length; i++)
		{
			var controlValues = this.getControlValue(controls[i]);

			for (var key in controlValues)
			{
				if(controlValues.hasOwnProperty(key))
				{
					if(!skipFields[key])
					{
						formData[key] = controlValues[key];
					}
				}
			}
		}

		var form = this._editor.getFormElement();

		if(form)
		{
			var prepared = BX.ajax.prepareForm(form);

			if(prepared && prepared.data)
			{
				for(i in prepared.data)
				{
					if(prepared.data.hasOwnProperty(i))
					{
						formData[i] = prepared.data[i];
					}
				}
			}
		}

		if(this._productList)
		{
			formData = BX.mergeEx(this._productList.getFormData(), formData);
		}

		return formData;
	};

	BX.Crm.EntityEditorOrderShipmentController.prototype.getControlValue = function(field)
	{
		var value = [];

		if (!field instanceof BX.Crm.EntityEditorControl)
			return value;

		var childrenValue = [];

		if (field instanceof BX.UI.EntityEditorSection || field instanceof BX.UI.EntityEditorColumn)
		{
			var children = field.getChildren();
			for (var i=0; i < field.getChildCount(); i++)
			{
				childrenValue = this.getControlValue(children[i]);
				for (var key in childrenValue)
				{
					if(childrenValue.hasOwnProperty(key))
					{
						value[key] = childrenValue[key];
					}
				}
			}
		}
		else if (field.isChanged())
		{
			value[field.getName()] = field.getRuntimeValue();
		}

		return value;
	};

	BX.Crm.EntityEditorOrderShipmentController.prototype.onDataChanged = function(ajaxParams, options)
	{
		ajaxParams = ajaxParams || {};
		options = options || {};

		this.ajax('refreshShipmentData', ajaxParams, options);
	};

	BX.Crm.EntityEditorOrderShipmentController.prototype.markAsChangedItem = function()
	{
		this._editor._toolPanel.enableSaveButton();
		this._editor._toolPanel.clearErrors();
		this.markAsChanged();
	};

	BX.Crm.EntityEditorOrderShipmentController.prototype.onSendDataSuccess = function(result, options)
	{
		var toolPanel = this._editor._toolPanel;
		var skipMarkAsChanged = (options && options.skipMarkAsChanged) || false;
		this._isRequesting = false;
		this._loaderController.hideLoader();
		toolPanel.clearErrors();

		if(result)
		{
			if(result.ERROR)
			{
				toolPanel.addError(result.ERROR);

				if(toolPanel.isSaveButtonEnabled())
				{
					toolPanel.disableSaveButton();
				}
				this._editor.showToolPanel();
			}
			if(result.SHIPMENT_DATA)
			{
				if(result.SHIPMENT_DATA.CUSTOM_PRICE_DELIVERY === 'Y')
				{
					this.setCustomPriceDelivery();
				}

				this._model.setData(result.SHIPMENT_DATA);

				if(!skipMarkAsChanged)
				{
					this.markAsChanged();
				}

				if(result.SHIPMENT_DATA.SHIPMENT_PROPERTIES_SCHEME)
				{
					setTimeout(
						BX.delegate(function(){
							BX.onCustomEvent(
								window,
								"Crm.ShipmentModel.ChangePropertyScheme",
								[ result.SHIPMENT_DATA.SHIPMENT_PROPERTIES_SCHEME ]
							);
						}, this),
						0
					);
				}

				if(this._productList !== null)
				{
					this._productList.setFormData(result);
				}

				if(result.SHIPMENT_DATA.DELIVERY_LOGO !== 'undefined')
				{
					var logoField = this._editor.getControlById('DELIVERY_LOGO');

					if(logoField)
					{
						logoField.refreshLayout();
					}
				}

				if(!toolPanel.isSaveButtonEnabled())
				{
					toolPanel.enableSaveButton();
				}
			}
		}
	};

	BX.Crm.EntityEditorOrderShipmentController.prototype.onSendDataFailure = function(type, e)
	{
		this._isRequesting = false;
		this._loaderController.hideLoader();
		BX.debug(e.message);
	};

	BX.Crm.EntityEditorOrderShipmentController.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorOrderShipmentController();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorDocumentOrderShipmentController === "undefined")
{
	BX.Crm.EntityEditorDocumentOrderShipmentController = function()
	{
		BX.Crm.EntityEditorDocumentOrderShipmentController.superclass.constructor.apply(this);

		this._loaderController = BX.Crm.EntityEditorOrderLoaderController.create();
		this._productList = null;
		this._isRequesting = false;
	};

	BX.extend(BX.Crm.EntityEditorDocumentOrderShipmentController, BX.Crm.EntityEditorOrderShipmentController);

	BX.Crm.EntityEditorDocumentOrderShipmentController.prototype.doInitialize = function()
	{
		BX.onCustomEvent(window, "onEntityEditorDocumentOrderShipmentControllerInit", [this]);
		BX.addCustomEvent(window, "onDeliveryExtraServiceValueChange", BX.delegate(this.onDeliveryExtraServiceValueChange, this));
		BX.addCustomEvent(window, "onDeliveryPriceRecalculateClicked", BX.delegate(this.onDeliveryPriceRecalculateClicked, this));
		window['EntityEditorDocumentOrderShipmentController'] = this;

		BX.addCustomEvent(window, 'onDocumentProductChange', BX.delegate(this.onDocumentProductChange, this));
		BX.addCustomEvent(window, 'DocumentProductListController', BX.delegate(this.setProductList, this));

		if (this.isLockCurrency())
		{
			this.lockCurrency();
		}
	};

	BX.Crm.EntityEditorDocumentOrderShipmentController.prototype.isLockCurrency = function()
	{
		return true;
	}

	BX.Crm.EntityEditorDocumentOrderShipmentController.prototype.lockCurrency = function()
	{
		this._model.lockField('CURRENCY');
	}

	BX.Crm.EntityEditorDocumentOrderShipmentController.prototype.onAfterCreate = function(params)
	{};

	BX.Crm.EntityEditorDocumentOrderShipmentController.prototype.onAfterUpdate = function(params)
	{};

	BX.Crm.EntityEditorDocumentOrderShipmentController.prototype.onDeliveryExtraServiceValueChange = function()
	{
		this.onDataChanged();
	};

	BX.Crm.EntityEditorDocumentOrderShipmentController.prototype.onDeliveryPriceRecalculateClicked = function()
	{
		this.onDataChanged({}, {showLoader: true});
	};

	BX.Crm.EntityEditorDocumentOrderShipmentController.prototype.innerCancel = function()
	{
		this.ajax('rollback', {}, { skipMarkAsChanged: true});
	};

	BX.Crm.EntityEditorDocumentOrderShipmentController.prototype.onBeforeSubmit = function()
	{
		var priceDelivery = this._editor.getControlById('PRICE_DELIVERY_WITH_CURRENCY');

		if(priceDelivery && priceDelivery.isChanged())
		{
			this.setCustomPriceDelivery();
		}

		var productDataFieldName = this.getConfigStringParam("productDataFieldName", "");
		this.setFormField(productDataFieldName, '');
		this.setFormField(productDataFieldName,'['+JSON.stringify(this.demandFormData())+']');
	};

	BX.Crm.EntityEditorDocumentOrderShipmentController.prototype.setFormField = function(fieldName, value)
	{
		var form = this._editor.getFormElement();

		if(form.elements[fieldName])
		{
			form.elements[fieldName].value = value;
		}
		else
		{
			form.appendChild(BX.create("input",	{
				attrs: {type: "hidden", name: fieldName, value: value}
			}));
		}
	};

	BX.Crm.EntityEditorDocumentOrderShipmentController.prototype.onChangeDelivery = function()
	{
		this.ajax('changeDelivery');
	};

	BX.Crm.EntityEditorDocumentOrderShipmentController.prototype.setCustomPriceDelivery = function()
	{
		var form = this._editor.getFormElement();

		if(form.elements['CUSTOM_PRICE_DELIVERY'])
		{
			form.elements['CUSTOM_PRICE_DELIVERY'].value = 'Y';
		}
		else
		{
			form.appendChild(
				BX.create(
					'input',
					{
						attrs: {
							type: 'hidden',
							name: 'CUSTOM_PRICE_DELIVERY',
							value: 'Y'
						}
					}
				)
			);
		}
	};

	BX.Crm.EntityEditorDocumentOrderShipmentController.prototype.onProductAdd = function(basketId)
	{};

	BX.Crm.EntityEditorDocumentOrderShipmentController.prototype.onProductDelete = function(basketCode)
	{};

	BX.Crm.EntityEditorDocumentOrderShipmentController.prototype.ajax = function(action, ajaxParams, options)
	{
		if(!action)
		{
			throw 'action must be defined!';
		}

		if(this._isRequesting)
		{
			return;
		}

		this._isRequesting = true;

		ajaxParams = ajaxParams || {};
		options = options || {};

		if(typeof options.showLoader === 'undefined')
		{
			options.showLoader = false;
		}

		if(options.showLoader)
		{
			this._loaderController.showLoader();
		}

		var data = {
			ACTION: action,
			sessid: BX.bitrix_sessid()
		};

		data.FORM_DATA = this.demandFormData();

		if(typeof (ajaxParams.data) === 'object')
		{
			for(var i in ajaxParams.data)
			{
				if(ajaxParams.data.hasOwnProperty(i))
				{
					data[i] = ajaxParams.data[i];
				}
			}
		}

		BX.ajax({
			url: this.getConfigStringParam("serviceUrl", ""),
			method: "POST",
			dataType: "json",
			data: data,
			onsuccess: ajaxParams.onsuccess ? ajaxParams.onsuccess : BX.proxy(function(result){ this.onSendDataSuccess(result, options)}, this),
			onfailure: ajaxParams.onfailure ? ajaxParams.onfailure : BX.delegate(this.onSendDataFailure, this)
		});
	};

	BX.Crm.EntityEditorDocumentOrderShipmentController.prototype.demandFormData = function()
	{
		var formData = this._editor._model.getData(),
			controls = this._editor.getControls(),
			i,
			skipFields = {
				'DELIVERY_SERVICES_LIST': true,
				'EXTRA_SERVICES_DATA': true,
				'STATUS_CONTROL': true
			};

		var context = this._editor.getContext();
		formData['ID'] = context.ID ? context.ID : 0;
		formData['ORDER_ID'] = context.ORDER_ID ? context.ORDER_ID : 0;

		for (i=0; i < controls.length; i++)
		{
			var controlValues = this.getControlValue(controls[i]);

			for (var key in controlValues)
			{
				if(controlValues.hasOwnProperty(key))
				{
					if(!skipFields[key])
					{
						formData[key] = controlValues[key];
					}
				}
			}
		}

		var form = this._editor.getFormElement();

		if(form)
		{
			var prepared = BX.ajax.prepareForm(form);

			if(prepared && prepared.data)
			{
				for(i in prepared.data)
				{
					if(prepared.data.hasOwnProperty(i))
					{
						formData[i] = prepared.data[i];
					}
				}
			}
		}

		if(this._productList)
		{
			formData.PRODUCT = this._productList.getProductsFields();
		}

		return formData;
	};

	BX.Crm.EntityEditorDocumentOrderShipmentController.prototype.getControlValue = function(field)
	{
		var value = [];

		if (!field instanceof BX.Crm.EntityEditorControl)
			return value;

		var childrenValue = [];

		if (field instanceof BX.UI.EntityEditorSection || field instanceof BX.UI.EntityEditorColumn)
		{
			var children = field.getChildren();
			for (var i=0; i < field.getChildCount(); i++)
			{
				childrenValue = this.getControlValue(children[i]);
				for (var key in childrenValue)
				{
					if(childrenValue.hasOwnProperty(key))
					{
						value[key] = childrenValue[key];
					}
				}
			}
		}
		else if (field.isChanged())
		{
			value[field.getName()] = field.getRuntimeValue();
		}

		return value;
	};

	BX.Crm.EntityEditorDocumentOrderShipmentController.prototype.onDataChanged = function(ajaxParams, options)
	{
		ajaxParams = ajaxParams || {};
		options = options || {};

		this.ajax('refreshShipmentData', ajaxParams, options);
	};

	BX.Crm.EntityEditorDocumentOrderShipmentController.prototype.markAsChangedItem = function()
	{
		this._editor._toolPanel.enableSaveButton();
		this._editor._toolPanel.clearErrors();
		this.markAsChanged();
	};

	BX.Crm.EntityEditorDocumentOrderShipmentController.prototype.onDocumentProductChange = function(products)
	{
		var ajaxParams = { data: {PRODUCT: products }};
		this.ajax('changeProduct', ajaxParams);
	};

	BX.Crm.EntityEditorDocumentOrderShipmentController.prototype.setProductList = function(event)
	{
		this._productList = event.getData()[0];

		this._editor.getFormElement().appendChild(
			BX.create(
				'input', {
					props: {
						type: 'hidden',
						name: 'IS_PRODUCT_LIST_LOADED',
						value: 'Y'
					}
				}
			)
		);
	};

	BX.Crm.EntityEditorDocumentOrderShipmentController.prototype.onSendDataSuccess = function(result, options)
	{
		var toolPanel = this._editor._toolPanel;
		var skipMarkAsChanged = (options && options.skipMarkAsChanged) || false;
		this._isRequesting = false;
		this._loaderController.hideLoader();
		toolPanel.clearErrors();

		if(result)
		{
			if(result.ERROR)
			{
				toolPanel.addError(result.ERROR);

				if(toolPanel.isSaveButtonEnabled())
				{
					toolPanel.disableSaveButton();
				}
				this._editor.showToolPanel();
			}
			if(result.SHIPMENT_DATA)
			{
				if(result.SHIPMENT_DATA.CUSTOM_PRICE_DELIVERY === 'Y')
				{
					this.setCustomPriceDelivery();
				}

				this._model.setData(result.SHIPMENT_DATA);

				if(!skipMarkAsChanged)
				{
					this.markAsChanged();
				}

				if(result.SHIPMENT_DATA.SHIPMENT_PROPERTIES_SCHEME)
				{
					setTimeout(
						BX.delegate(function(){
							BX.onCustomEvent(
								window,
								"Crm.ShipmentModel.ChangePropertyScheme",
								[ result.SHIPMENT_DATA.SHIPMENT_PROPERTIES_SCHEME ]
							);
						}, this),
						0
					);
				}

				if(!toolPanel.isSaveButtonEnabled())
				{
					toolPanel.enableSaveButton();
				}
			}
		}
	};

	BX.Crm.EntityEditorDocumentOrderShipmentController.prototype.onSendDataFailure = function(type, e)
	{
		this._isRequesting = false;
		this._loaderController.hideLoader();
	};

	BX.Crm.EntityEditorDocumentOrderShipmentController.prototype.onAfterSave = function ()
	{
		BX.Crm.EntityEditorDocumentOrderShipmentController.superclass.onAfterSave.apply(this);
		var card = BX.Crm.Store.DocumentCard.Document.Instance;
		if (card)
		{
			card.setViewModeButtons(this._editor);
		}

		window.top.BX.onCustomEvent('onEntityEditorDocumentOrderShipmentControllerDocumentSave');
	}

	BX.Crm.EntityEditorDocumentOrderShipmentController.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorDocumentOrderShipmentController();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorOrderExtraServicesWrapper === "undefined")
{
	var mode = '';

	BX.Crm.EntityEditorOrderExtraServicesWrapper = function(initParams)
	{
		mode = initParams.mode;
	};

	BX.Crm.EntityEditorOrderExtraServicesWrapper.prototype.wrapItem = function(item)
	{
		var html = mode === 'view' ? item.VIEW_HTML : item.EDIT_HTML;
		return this.wrapDomNode(item.NAME, createDomNodeFromHtml(html), item.PRICE, item.COST).childNodes;
	};

	BX.Crm.EntityEditorOrderExtraServicesWrapper.prototype.wrapDomNode = function(name, domNode, price, cost)
	{
		domNode = wrap(domNode, price, cost, mode);

		if(name)
		{
			domNode = addName(name, domNode);
		}

		return domNode;
	};

	var addName = function(name, domNode)
	{
		var node = domNode.querySelector('.crm-entity-widget-content-block');

		if(node)
		{
			node.insertBefore(
				BX.create('div', {props: {className: 'crm-entity-widget-content-block-title'}, children:[
						BX.create('span', {props: {className: 'crm-entity-widget-content-block-title-text'}, html: name})
					]}),
				node.children[0]
			);
		}

		return domNode;
	};

	var createDomNodeFromHtml = function(html)
	{
		return BX.create('div',{html: html});
	};

	var wrap = function(domNode, price, cost, mode)
	{
		if(mode === 'edit')
		{
			domNode.querySelectorAll('input[type=checkbox]').forEach(function(item, i, arr){
				wrapCheckbox(item, price, cost);
			}, this);

			domNode.querySelectorAll('input[type=text]').forEach(function(item, i, arr){
				wrapTextInput(item, price, cost);
			}, this);

			domNode.querySelectorAll('select').forEach(function(item, i, arr){
				wrapSelect(item, price, cost);
			}, this);
		}
		else //view
		{
			var textNode, walk = document.createTreeWalker(
				domNode,
				NodeFilter.SHOW_TEXT,
				null,
				false
			);

			while(textNode = walk.nextNode())
			{
				var text = textNode.data,
					newNode = BX.create('span',{html: text});

				textNode.parentNode.replaceChild(
					getWrappedText(newNode, price, cost),
					textNode
				);
			}
		}

		return domNode;
	};

	var getWrappedText = function(textNode, price, cost)
	{
		var result = null;

		if(textNode)
		{
			result = BX.create('div', {props: {className: 'crm-entity-widget-content-block'}, children:[
						BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner'}, children:[
							BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-text'}, children:[
								textNode.innerHTML + (price && cost ? ' (' + cost + ') ' : '')
							]})
						]})
					]});
		}

		return result;
	};

	var wrapCheckbox = function(checkbox, price, cost)
	{
		if(!checkbox)
		{
			return;
		}
		checkbox.className = 'crm-entity-widget-content-checkbox';
		price = price || '&nbsp;';

		checkbox.parentNode.replaceChild(
			BX.create('div', {props: {className: 'crm-entity-widget-content-block crm-entity-widget-content-block-field-checkbox'}, children:[
				BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner'}, children:[
					BX.create('label', {props: {className: 'crm-entity-widget-content-block-checkbox-label'}, children:[
						checkbox.cloneNode(true),
						BX.create('span', {props: {className: 'crm-entity-widget-content-block-checkbox-description'}, html: price})
					]})
				]})
			]}),
			checkbox
		);
	};

	var wrapTextInput = function(textInput, price, cost)
	{
		if(!textInput)
		{
			return;
		}

		textInput.className = 'crm-entity-widget-content-input';

		textInput.parentNode.replaceChild(
			BX.create('div', {props: {className: 'crm-entity-widget-content-block'}, children:[
				BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner crm-entity-widget-content-block-colums-input'}, children:[
					BX.create('div', {props: {className: 'crm-entity-widget-content-block-input-wrapper'}, children:[
						textInput.cloneNode(true),
						BX.create('span', {props: {className: 'crm-entity-widget-content-block-checkbox-description'}, html: 'X ' + price})
					]})
				]})
			]}),
			textInput
		);
	};

	var wrapSelect = function(select, price, cost)
	{
		if(!select)
		{
			return;
		}

		select.className = 'crm-entity-widget-content-input';

		select.parentNode.replaceChild(
			BX.create('div', {props: {className: 'crm-entity-widget-content-block'}, children:[
				BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner crm-entity-widget-content-block-colums-input'}, children:[
					BX.create('div', {props: {className: 'crm-entity-widget-content-block-input-wrapper'}, children:[
						select.cloneNode(true)
					]})
				]})
			]}),
			select
		);
	};
}

if(typeof BX.Crm.EntityEditorOrderPaymentController === "undefined")
{
	BX.Crm.EntityEditorOrderPaymentController = function()
	{
		BX.Crm.EntityEditorOrderPaymentController.superclass.constructor.apply(this);
		this._isRequesting = false;
	};

	BX.extend(BX.Crm.EntityEditorOrderPaymentController, BX.UI.EntityEditorController);

	BX.Crm.EntityEditorOrderPaymentController.prototype.doInitialize = function()
	{
		BX.onCustomEvent(window, "onEntityEditorOrderPaymentControllerInit", [this]);
		window['EntityEditorOrderPaymentController'] = this;
		this._model.lockField('CURRENCY');
	};

	BX.Crm.EntityEditorOrderPaymentController.prototype.ajax = function(action, fields, options)
	{
		if(!action)
		{
			throw 'action must be defined!';
		}

		if(this._isRequesting)
		{
			return;
		}

		this._isRequesting = true;

		fields = fields || {};
		options = options || {};

		var data = {
			ACTION: action,
			sessid: BX.bitrix_sessid()
		};

		data.FORM_DATA = this.demandFormData();

		if(typeof (fields.data) === 'object')
		{
			for(var i in fields.data)
			{
				if(fields.data.hasOwnProperty(i))
				{
					data[i] = fields.data[i];
				}
			}
		}

		BX.ajax({
			url: this.getConfigStringParam("serviceUrl", ""),
			method: "POST",
			dataType: "json",
			data: data,
			onsuccess: fields.onsuccess ? fields.onsuccess : BX.proxy(function(result){ this.onSendDataSuccess(result, options)}, this),
			onfailure: fields.onfailure ? fields.onfailure : BX.delegate(this.onSendDataFailure, this)
		});

		this._isRequesting = false;
	};

	BX.Crm.EntityEditorOrderPaymentController.prototype.onDataChanged = function()
	{
		this.ajax('refreshPaymentData');
	};

	BX.Crm.EntityEditorOrderPaymentController.prototype.demandFormData = function()
	{
		var formData = this._editor._model.getData(),
			controls = this._editor.getControls(),
			i;

		var context = this._editor.getContext();
		formData['ORDER_ID'] = context.ORDER_ID ? context.ORDER_ID : 0;

		for (i=0; i < controls.length; i++)
		{
			var controlValues = this.getControlValue(controls[i]);

			for (var key in controlValues)
			{
				if(controlValues.hasOwnProperty(key))
				{
					formData[key] = controlValues[key];
				}
			}
		}

		return formData;
	};

	BX.Crm.EntityEditorOrderPaymentController.prototype.getControlValue = function(field)
	{
		var value = [];

		if (!field instanceof BX.Crm.EntityEditorControl)
			return value;

		var childrenValue = [];

		if (field instanceof BX.Crm.EntityEditorSection || field instanceof BX.UI.EntityEditorColumn)
		{
			var children = field.getChildren();
			for (var i=0; i < field.getChildCount(); i++)
			{
				childrenValue = this.getControlValue(children[i]);
				for (var key in childrenValue)
				{
					if(childrenValue.hasOwnProperty(key))
					{
						value[key] = childrenValue[key];
					}
				}
			}
		}
		else if (field.isChanged())
		{
			value[field.getName()] = field.getRuntimeValue();
		}

		return value;
	};

	BX.Crm.EntityEditorOrderPaymentController.prototype.onBeforeSubmit = function()
	{
		var value = '['+JSON.stringify(this.demandFormData())+']',
			form = this._editor.getFormElement(),
			dataFieldName = this.getConfigStringParam("dataFieldName", "");

		form.appendChild(
			BX.create(
				"input",
				{
					attrs: {
						type: "hidden",
						name: dataFieldName,
						value: value
					}
				}
			)
		);
	};

	BX.Crm.EntityEditorOrderPaymentController.prototype.innerCancel = function()
	{
		this.ajax('rollback', {}, { skipMarkAsChanged: this._isChanged});
	};

	BX.Crm.EntityEditorOrderPaymentController.prototype.onSendDataSuccess = function(result, options)
	{
		var skipMarkAsChanged = (options && options.skipMarkAsChanged) || false;
		var sendUpdateEvent = (options && options.sendUpdateEvent) || false;

		this._isRequesting = false;
		this._editor._toolPanel.clearErrors();

		if(result)
		{
			if(result.ERROR)
			{
				this._editor._toolPanel.addError(result.ERROR);
			}

			if(result.PAYMENT_DATA)
			{
				this._model.setData(result.PAYMENT_DATA);

				if(!skipMarkAsChanged)
				{
					this.markAsChanged();
				}

				if(sendUpdateEvent)
				{
					window.top.BX.SidePanel.Instance.postMessage(
						window,
						'CrmOrderPayment::Update',
						{
							field: result.PAYMENT_DATA,
							entityTypeId: BX.CrmEntityType.enumeration.orderpayment
						}
					);
				}
			}
		}
	};

	BX.Crm.EntityEditorOrderPaymentController.prototype.onSendDataFailure = function(type, e)
	{
		this._isRequesting = false;
		BX.debug(e.message);
	};

	BX.Crm.EntityEditorOrderPaymentController.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorOrderPaymentController();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof BX.Crm.EntityEditorOrderProductController === "undefined")
{
	BX.Crm.EntityEditorOrderProductController = function()
	{
		BX.Crm.EntityEditorOrderProductController.superclass.constructor.apply(this);
		this._isRequesting = false;
	};

	BX.extend(BX.Crm.EntityEditorOrderProductController, BX.UI.EntityEditorController);

	BX.Crm.EntityEditorOrderProductController.prototype.doInitialize = function()
	{
		this._model.lockField('CURRENCY');
		this._model.setField('CUSTOM_PRICE', 'Y');
	};

	BX.Crm.EntityEditorOrderProductController.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorOrderProductController();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof BX.Crm.EntityEditorPayment === "undefined")
{
	BX.Crm.EntityEditorPayment = function()
	{
		BX.Crm.EntityEditorPayment.superclass.constructor.apply(this);

		this._view = null;
		this._isPaidButtonMenuOpened = {};
		this._paySystemInputs = {};
		this._sumInput = {};
		this._isReturn = {};
		this._logoImg = {};
		this._paidInput = {};
		this._datePaid = {};
		this._currency = {};
		this._paidButtons = {};
		this._documentLinks = {};
		this._documentInnerData = {};
		this._paymentBlocks = {};
		this._documentType = {
			voucher: 1,
			return: 2
		};

		this._isCreateMode = false;
		this._customPaymentSumm = {};

		//view mode
		this._paySystemName = {};
		this._sumFormatted = {};
		this._orderController = null;
	};

	BX.extend(BX.Crm.EntityEditorPayment, BX.Crm.EntityEditorField);

	if(typeof(BX.Crm.EntityEditorPayment.messages) === "undefined")
	{
		BX.Crm.EntityEditorPayment.messages = {};
	}

	BX.Crm.EntityEditorPayment.prototype.getItemField = function(index, name)
	{
		var value = this._model.getField(this.getName()),
			result = '';

		if(value && value[index] && typeof(value[index][name]) !== "undefined" )
		{
			result = value[index][name];
		}

		return result;
	};

	BX.Crm.EntityEditorPayment.prototype.setItemField = function(index, name, value)
	{
		var val = this._model.getField(this.getName());

		if(val && val[index])
		{
			val[index][name] = value;
			this._model.setField(this.getName(), val, { originator: this });
		}
	};

	BX.Crm.EntityEditorPayment.prototype.onPaySystemSelect = function(index)
	{
		this.setItemField(index, 'PAY_SYSTEM_ID', this._paySystemInputs[index].value);
		this.markAsChanged();
		this.getOrderController().onDataChanged();
	};

	BX.Crm.EntityEditorPayment.prototype.getOrderController = function()
	{
		if(this._orderController === null)
		{
			for(var i = 0, length = this._editor._controllers.length; i < length; i++)
			{
				if (this._editor._controllers[i] instanceof BX.Crm.EntityEditorOrderController)
				{
					this._orderController = this._editor._controllers[i];
					break;
				}
			}
		}

		return this._orderController;
	};

	BX.Crm.EntityEditorPayment.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorPayment.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};

	BX.Crm.EntityEditorPayment.prototype.doInitialize = function()
	{
		BX.addCustomEvent('SidePanel.Slider:onMessage', BX.delegate(this.onExternalChange, this));
		BX.addCustomEvent('SidePanel.Slider:onMessage', BX.delegate(this.onPaymentVoucherUpdate, this));
		BX.addCustomEvent('SidePanel.Slider:onMessage', BX.delegate(this.onPaymentVoucherInited, this));
		this._isCreateMode = this._model.getField('ID', 'n0').charAt(0) === 'n';
	};

	BX.Crm.EntityEditorPayment.prototype.createPaymentContent = function(index, value)
	{
		if(this._mode === BX.UI.EntityEditorMode.edit)
		{
			return this.createPaymentContentEdit(index, value);
		}
		else
		{
			return this.createPaymentContentView(index, value);
		}
	};

	BX.Crm.EntityEditorPayment.prototype.processErrors = function(errors)
	{
		if(errors.length)
		{
			var message = '';

			for(var i = 0, l = errors.length - 1; i <= l; i++)
			{
				message += errors[i]+"\n";
			}

			if(message)
			{
				this.showError(message);
				this._editor._toolPanel.addError(message);
			}
		}
	};

	BX.Crm.EntityEditorPayment.prototype.createPaySystemList = function(index, value)
	{
		var _this = this,
			psList = BX.create('select',{
				props: {name: 'PAYMENT['+index+'][PAY_SYSTEM_ID]'},
				events: {
					change: function(){_this.onPaySystemSelect(index, this[this.selectedIndex].value, value)}}
				});

		for(var i in value.PAY_SYSTEMS_LIST)
		{
			if(!value.PAY_SYSTEMS_LIST.hasOwnProperty(i))
			{
				continue;
			}

			var isSelected  = (value.PAY_SYSTEM_ID === value.PAY_SYSTEMS_LIST[i].ID);

			var option = new Option(
				value.PAY_SYSTEMS_LIST[i].NAME,
				value.PAY_SYSTEMS_LIST[i].ID,
				isSelected,
				isSelected
			);

			psList.options.add(option);
		}

		return psList;
	};

	BX.Crm.EntityEditorPayment.prototype.createLogo = function(index, value)
	{
		return BX.create('img', {props: {
				className: 'crm-entity-widget-content-block-inner-order-logo',
				id: 'crm_entity_editor_payment_logotip_'+index,
				src: value.PAY_SYSTEM_LOGO_PATH,
				alt: value.PAY_SYSTEM_NAME
			}});
	};

	BX.Crm.EntityEditorPayment.prototype.createSumInput = function(index, value)
	{
		return BX.create("input",{
			props:{
				className: 'crm-entity-widget-content-input',
				name: 'PAYMENT['+index+'][SUM]',
				id: 'crm_entity_editor_is_payment_sum_'+index,
				type: 'text',
				value: value.FORMATTED_SUM,
				size: 40
			},
			events:{
				change: BX.delegate(function(e){ this.onPaymentSumChanged(e, index); }, this),
				input: BX.delegate(this.onPaymentSumInput, this)
			}
		});
	};

	BX.Crm.EntityEditorPayment.prototype.createPaidInput = function(index, value)
	{
		return BX.create("input",{props:{
				name: 'PAYMENT['+index+'][PAID]',
				id: 'crm_entity_editor_is_payment_paid_input_'+index,
				type: 'hidden',
				value: value.PAID
			}});
	};

	BX.Crm.EntityEditorPayment.prototype.createIsReturnInput = function(index, value)
	{
		return BX.create("input",{props:{
				name: 'PAYMENT['+index+'][IS_RETURN]',
				id: 'crm_entity_editor_is_payment_paid_is_return_'+index,
				type: 'hidden',
				value: value.IS_RETURN
			}});
	};

	BX.Crm.EntityEditorPayment.prototype.createCurrency = function(index, value)
	{
		return BX.create('span', {props: {className: 'crm-entity-widget-content-block-wallet'}, html: value.CURRENCY_NAME});
	};

	BX.Crm.EntityEditorPayment.prototype.creatDatePaid = function(index, value)
	{
		return BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-order-controls-text'}, html: value.DATE_PAID})
	};

	BX.Crm.EntityEditorPayment.prototype.createPaymentContentEdit = function(index, value)
	{
		var _isPaid = (this.getItemField(index, 'PAID') === 'Y');
		this._isPaidButtonMenuOpened[index] = false;
		this._paidButtons[index] = this.createPaymentButton(index, _isPaid);
		this._paySystemInputs[index] = this.createPaySystemList(index, value);
		this._logoImg[index] = this.createLogo(index, value);
		this._sumInput[index] = this.createSumInput(index, value);
		this._paidInput[index] = this.createPaidInput(index, value);
		this._isReturn[index] = this.createIsReturnInput(index, value);
		this._currency[index] = this.createCurrency(index, value);
		this._datePaid[index] = this.creatDatePaid(index, value);

		var content =
			BX.create('div', {props: {className: 'crm-entity-widget-content-block'}, children:[
				BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner'}, children:[
					BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-box'}, children:[
						BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-box-title'}, children:[
							BX.create('span', {props: {className: 'fields enumeration field-wrap'}, style: {width: '100%'}, children:[
								BX.create('span', {props: {className: 'fields enumeration field-item'}, children:[
									this._paySystemInputs[index]
								]})
							]})
						]}),
						BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-box-content-container'}, children:[
							BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-box-content-left'}, children:[
								BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-order-logo-container'}, children:[
									this._logoImg[index]
								]})
							]}),
							BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-box-content-right'}, children:[
								BX.create('div', {props: {className: 'crm-entity-widget-content-block crm-entity-widget-content-block-field-money'}, children:[
									BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner'}, children:[
										BX.create('div', {props: {className: 'crm-entity-widget-content-block-title'}, children:[
											BX.create('span', {props: {className: 'crm-entity-widget-content-block-title-text'}, html: this.getMessage('sum')})
										]}),
										BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner crm-entity-widget-content-block-colums-input'}, children:[
											BX.create('div', {props: {className: 'crm-entity-widget-content-block-input-wrapper'}, children:[
												this._sumInput[index],
												this._currency[index]
											]})
										]})
									]})
								]}),
								BX.create('div', {props: {className: 'crm-entity-widget-content-block crm-entity-widget-content-block-field-custom'}, children:[
									BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner'}, children:[
										BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-order-controls'}, children:[
											BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-order-controls-row'}, children:[
												this._paidButtons[index],
												this._datePaid[index],
												this._paidInput[index],
												this._isReturn[index]
											]})
										]})
									]})
								]})
							]})
						]})
					]})
				]})
			]});

		if (BX.type.isNotEmptyString(value.VOUCHER_INFO))
		{
			text = this.getMessage('documentTitle') + ": "+ value.VOUCHER_INFO;
		}
		else
		{
			text = this.getMessage('addDocument');
		}
		this._documentLinks[value.ID] = BX.create('a', {
			props: {className: 'crm-entity-widget-content-block-edit-action-btn'},
			text: text,
			events: { click: BX.delegate( function(e){this.onAddDocumentClick(e, value.ID, _isPaid)}, this)}
		});

		if (BX.prop.getNumber(value, 'ID', 0) === 0)
		{
			this._documentInnerData[value.ID] = {
				index: index,
				values: {
					PAY_VOUCHER_NUM: BX.prop.getString(value, 'PAY_VOUCHER_NUM', ''),
					PAY_VOUCHER_DATE: BX.prop.getString(value, 'PAY_VOUCHER_DATE', '')
				}
			};
		}

		content.appendChild(
			BX.create('div', {props: {className: 'crm-entity-widget-content-block'}, children:[this._documentLinks[value.ID]]})
		);

		return content;
	};

	BX.Crm.EntityEditorPayment.prototype.onPaymentSumChanged = function(e, index)
	{
		e = e || window.event;
		var target = e.target || e.srcElement,
			_this = this;

		this._customPaymentSumm[index] = true;
		var value = target.value.split(" ").join("");
		_this.setItemField(index, 'SUM', value);
		this._editor.formatMoney(
			value,
			this.getModel().getField('CURRENCY', ''),
			function(result)
			{
				if(result.FORMATTED_SUM)
				{
					_this.setItemField(index, 'FORMATTED_SUM', result.FORMATTED_SUM);
				}

				if(result.FORMATTED_SUM_WITH_CURRENCY)
				{
					_this.setItemField(index, 'FORMATTED_SUM_WITH_CURRENCY', result.FORMATTED_SUM_WITH_CURRENCY);
				}
			}
		);
	};

	BX.Crm.EntityEditorPayment.prototype.onPaymentSumInput = function(e)
	{
		e.target.value = BX.Currency.Editor.getFormattedValue(
			e.target.value,
			this.getModel().getField('CURRENCY', '')
		)
	};

	BX.Crm.EntityEditorPayment.prototype.createPaySystemName = function(index, value)
	{
		return BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-box-title'}, children:[
				BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-box-title-text'}, html: value.PAY_SYSTEM_NAME}),
				BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-box-title-desc'}, children:[
					BX.create('a', {
						style: {cursor: 'pointer'},
						html: value.NUMBER_AND_DATE,
						events:{
							click: BX.proxy(function(){
									this.getOrderController().openDetailSlider(
										'/shop/orders/payment/details/'+parseInt(value.ID)+'/'
									);
								},
								this
							)
						}
					})
				]})
			]});
	};

	BX.Crm.EntityEditorPayment.prototype.openPaymentDetail = function(paymentId)
	{
		if(this._editor.isChanged())
		{
			BX.UI.EditorAuxiliaryDialog.create(
				"order_save_confirmation",
				{
					title: this.getMessage('saveChanges'),
					content: this.getMessage('saveConfirm'),
					buttons:
						[
							{
								id: "save",
								type: BX.Crm.DialogButtonType.accept,
								text: this.getMessage('save'),
								callback: BX.proxy(function(button){
									this._editor.saveChanged();
									BX.Crm.Page.openSlider('/shop/orders/payment/details/'+parseInt(paymentId)+'/');
									button.getDialog().close();
								},
								this)
							},
							{
								id: "cancel",
								type: BX.Crm.DialogButtonType.cancel,
								text: this.getMessage('notSave'),
								callback: function(button){
									BX.Crm.Page.openSlider('/shop/orders/payment/details/'+parseInt(paymentId)+'/');
									button.getDialog().close();
								}
							}
						]
				}
			).open();
		}
		else
		{
			BX.Crm.Page.openSlider('/shop/orders/payment/details/'+parseInt(paymentId)+'/');
		}
	};

	BX.Crm.EntityEditorPayment.prototype.createSumFormatted = function(index, value)
	{
		return BX.create('span', {props: {className: 'crm-entity-widget-content-block-colums-right'}, html: value.FORMATTED_SUM_WITH_CURRENCY});
	};

	BX.Crm.EntityEditorPayment.prototype.createPaymentContentView = function(index, value)
	{
		var _isPaid = (value.PAID === 'Y');
		this._isPaidButtonMenuOpened[index] = false;
		this._paidButtons[index] = this.createPaymentButton(index, _isPaid);
		this._paySystemName[index] = this.createPaySystemName(index, value);
		this._logoImg[index] = this.createLogo(index, value);
		this._sumFormatted[index] = this.createSumFormatted(index, value);
		this._paidInput[index] = this.createPaidInput(index, value);
		this._isReturn[index] = this.createIsReturnInput(index, value);
		this._datePaid[index] = this.creatDatePaid(index, value);

		var content =
			BX.create('div', {props: {className: 'crm-entity-widget-content-block'}, children:[
				BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner'}, children:[
					BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-box'}, children:[
							this._paySystemName[index],
						BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-box-content-container'}, children:[
							BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-box-content-left'}, children:[
								BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-order-logo-container'}, children:[
									this._logoImg[index]
								]})
							]}),
							BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-box-content-right'}, children:[
								BX.create('div', {props: {className: 'crm-entity-widget-content-block crm-entity-widget-content-block-field-money'}, children:[
									BX.create('div', {props: {className: 'crm-entity-widget-content-block-title'}, children:[
										BX.create('span', {props: {className: 'crm-entity-widget-content-block-title-text'}, html: this.getMessage('sum')})
									]}),
									BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner'}, children:[
										BX.create('div', {props: {className: 'crm-entity-widget-content-block-colums-block'}, children:[
												BX.create('div', {props: {className: 'crm-entity-widget-content-block-wallet'}, children:[
													this._sumFormatted[index]
												]})
											]})
										]})
									]}),
									BX.create('div', {props: {className: 'crm-entity-widget-content-block crm-entity-widget-content-block-field-custom'}, children:[
										BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner'}, children:[
											BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-order-controls'}, children:[
												BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-order-controls-row'}, children:[
													this._paidButtons[index],
													this._datePaid[index],
													this._paidInput[index],
													this._isReturn[index]
												]})
											]})
										]})
									]})
								]})
							]})
						]})
					]})
				]});

		if (BX.type.isNotEmptyString(value.VOUCHER_INFO))
		{
			text = this.getMessage('documentTitle') + ": "+ value.VOUCHER_INFO;
		}
		else
		{
			text = this.getMessage('addDocument');
		}
		this._documentLinks[value.ID] = BX.create('a', {
			props: {className: 'crm-entity-widget-content-block-edit-action-btn'},
			text: text,
			events: { click: BX.delegate( function(e){this.onAddDocumentClick(e, value.ID, _isPaid)}, this)}
		});

		content.appendChild(
			BX.create('div', {props: {className: 'crm-entity-widget-content-block'}, children:[this._documentLinks[value.ID]]})
		);

		return content;
	};

	BX.Crm.EntityEditorPayment.prototype.createPaymentButton = function(index, isPaid)
	{
		var paymentButton = BX.create('div', {
			attrs: { style : "margin-top: 10px;" },
			props: {
				className: 'ui-btn-split ui-btn-sm ' + (isPaid ? 'ui-btn-success-light' : 'ui-btn-danger-light'),
				id: 'crm_entity_editor_is_payment_paid_button_'+ index
			},
			children:[
				BX.create('button', {
					props: {
						className: 'ui-btn-main',
						id: 'crm_entity_editor_is_payment_paid_button_main_' + index
					},
					html: (isPaid ? this.getMessage('paymentWasPaid') : this.getMessage('paymentWasNotPaid'))
				}),
				BX.create('button', {props: {className: 'ui-btn-extra'}})
			]});

		BX.bind(paymentButton, "click", BX.delegate(function(e){this.onPaidButtonClick(e, index)}, this));
		return paymentButton;
	};

	BX.Crm.EntityEditorPayment.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		var value = this.getValue();
		this._wrapper = BX.create("div");
		this._view = null;
		var enableDrag = this.isDragEnabled();

		if(enableDrag)
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		var enableContextMenu = this.isContextMenuEnabled();

		if(this.checkIfNotEmpty(value))
		{
			this._view = BX.create('div');

			if(value && value.length)
			{
				for(var i=0, l=value.length; i<l; i++)
				{
					this._paymentBlocks[i] = this.createPaymentContent(i, value[i]);
					this._view.appendChild(this._paymentBlocks[i]);
				}
			}

			this._wrapper.appendChild(this._view);
		}

		if(enableContextMenu)
		{
			this._wrapper.appendChild(this.createContextMenuButton());
		}

		if(enableDrag)
		{
			this.initializeDragDropAbilities();
		}

		this.registerLayout(options);
		this._hasLayout = true;
	};

	BX.Crm.EntityEditorPayment.prototype.setPaidStatus = function(isPaid, isReturn, index)
	{
		this.setItemField(index,'PAID', isPaid);
		this.setItemField(index, 'IS_RETURN', isReturn);
	};

	BX.Crm.EntityEditorPayment.prototype.onPaidButtonClick = function(e, index)
	{
		this.togglePaidButtonMenu(index);
		e.preventDefault();
	};

	BX.Crm.EntityEditorPayment.prototype.onAddDocumentClick = function(e, id, isPaid)
	{
		var voucherLink = this._schemeElement.getDataStringParam("addPaymentDocumentUrl", "");
		var params = {
			paymentId: id,
			paymentType: this._documentType.voucher
		};

		if (BX.type.isNotEmptyObject(this._documentInnerData[id]))
		{
			var docValues = this._documentInnerData[id].values;
			for (var key in docValues)
			{
				if(docValues.hasOwnProperty(key))
				{
					params['ENTITY_DATA['+key+']'] = docValues[key];
				}
			}
		}

		voucherLink = BX.util.add_url_param(
			voucherLink,
			params
		);

		BX.Crm.Page.openSlider(voucherLink, { width: 500 });
	};

	BX.Crm.EntityEditorPayment.prototype.getPaymentIndexById = function(paymentId)
	{
		if(BX.type.isNotEmptyObject(this.getModel().getField('PAYMENT')))
		{
			var payment = this.getModel().getField('PAYMENT');

			for(var i in payment)
			{
				if(payment.hasOwnProperty(i) && payment[i]['ID'] == paymentId)
				{
					return i;
				}
			}
		}

		return false;
	};

	BX.Crm.EntityEditorPayment.prototype.onPaymentVoucherUpdate = function(event)
	{
		if (event.getEventId() === 'CrmOrderPaymentVoucher::Update')
		{
			var eventArgs = event.getData();
			var paymentId = BX.prop.getString(eventArgs, "entityId", 0);
			var index = this.getPaymentIndexById(paymentId);

			if(index === false)
			{
				return;
			}

			if(BX.prop.getInteger(eventArgs, "entityTypeId", 0) !== BX.CrmEntityType.enumeration.orderpayment)
			{
				return;
			}

			if(BX.prop.getString(eventArgs, "source", "") !== "ORDER_PAYMENT")
			{
				return;
			}

			if(BX.type.isDomNode(this._documentLinks[paymentId]))
			{
				var voucherNumber = BX.prop.getString(eventArgs, "PAY_VOUCHER_NUM", "");
				var voucherDate = BX.prop.getString(eventArgs, "PAY_VOUCHER_DATE", "");
				var text = "";
				if (BX.type.isNotEmptyString(voucherNumber))
				{
					var voucherInfo = BX.util.htmlspecialchars(voucherNumber);
					if (BX.type.isNotEmptyString(voucherDate))
					{
						voucherInfo += " " + BX.util.htmlspecialchars(voucherDate);
					}
					text = this.getMessage('documentTitle') + ": "+ voucherInfo;
				}
				else
				{
					text = this.getMessage('addDocument');
				}

				this._documentLinks[paymentId].innerHTML = text;

				if (BX.type.isNotEmptyObject(this._documentInnerData[paymentId]))
				{
					this._documentInnerData[paymentId]['values']['PAY_VOUCHER_NUM'] = voucherNumber;
					this._documentInnerData[paymentId]['values']['PAY_VOUCHER_DATE'] = voucherDate;
				}
			}

			for(var field in eventArgs)
			{
				if(field === 'entityId' || field === 'entityTypeId')
				{
					continue;
				}

				if(eventArgs.hasOwnProperty(field))
				{
					this.setItemField(index, field, BX.prop.getString(eventArgs, field, ""));
				}
			}

			if(!this._isCreateMode)
			{
				if(this._editor.isChanged())
				{
					this.markAsChanged();
					this.getOrderController().onDataChanged();
				}
				else
				{
					var fields = eventArgs;
					fields['PAYMENT_ID'] = paymentId;
					var action = 'setPaymentPaidField';
					if (fields['IS_RETURN'])
					{
						action = 'setPaymentReturnField';
					}
					this.getOrderController().ajax(
						action,
						{
							data: {
								FIELDS: fields,
							},
						},
						{
							skipMarkAsChanged: true,
							needProductComponentParams: this.getOrderController().isProductListLoaded()
						}
					);
				}
			}
		}
	};

	BX.Crm.EntityEditorPayment.prototype.onPaymentVoucherInited = function(event)
	{
		if (event.getEventId() === 'CrmOrderPaymentVoucher::Initialized')
		{
			var eventArgs = event.getData(),
				paymentId = BX.prop.getString(eventArgs, "entityId", 0),
				index = this.getPaymentIndexById(paymentId);

			if(
				(BX.prop.getInteger(eventArgs, "entityTypeId", 0) !== BX.CrmEntityType.enumeration.orderpayment)
				|| !eventArgs.voucherObject
			)
			{
				return;
			}

			var voucherObject = eventArgs.voucherObject;
			voucherObject.setField('PAY_VOUCHER_NUM', this.getItemField(index,'PAY_VOUCHER_NUM'));
			voucherObject.setField('PAY_VOUCHER_DATE', this.getItemField(index,'PAY_VOUCHER_DATE'));
			voucherObject.setField('PAY_RETURN_NUM', this.getItemField(index,'PAY_RETURN_NUM'));
			voucherObject.setField('PAY_RETURN_DATE', this.getItemField(index,'PAY_RETURN_DATE'));
			voucherObject.setField('PAY_RETURN_COMMENT', this.getItemField(index,'PAY_RETURN_COMMENT'));
			voucherObject.setField('PAID', this.getItemField(index,'PAID'));
			voucherObject.setField('source', 'ORDER_PAYMENT');

			if(this.getItemField(index,'PAID') === 'N')
			{
				var isReturn = this.getItemField(index,'IS_RETURN');

				if(isReturn === 'Y' || isReturn === 'P')
				{
					voucherObject.setField('IS_RETURN', isReturn);
				}
			}
		}
	};

	BX.Crm.EntityEditorPayment.prototype.onExternalChange = function(event)
	{
		isChanged = false;
		if (event.getEventId() === 'CrmOrderPayment::Update')
		{
			var eventData = event.getData();
			if (eventData.entityTypeId == BX.CrmEntityType.enumeration.orderpayment)
			{
				var values = this.getValue();
				var isChanged = false;
				for (var i=0;i<values.length;i++)
				{
					if (values[i].ID == eventData.field.ID)
					{
						if (BX.type.isNotEmptyString(eventData.field.PAID))
						{
							values[i].PAID = (eventData.field.PAID === 'Y' ? 'Y' : 'N');
						}
						if (BX.type.isNotEmptyString(eventData.field.FORMATTED_SUM_WITH_CURRENCY))
						{
							values[i].FORMATTED_SUM_WITH_CURRENCY = BX.util.htmlspecialchars(eventData.field.FORMATTED_SUM_WITH_CURRENCY);
						}
						if (BX.type.isNotEmptyString(eventData.field.FORMATTED_SUM))
						{
							values[i].FORMATTED_SUM = BX.util.htmlspecialchars(eventData.field.FORMATTED_SUM);
						}
						if (BX.type.isNotEmptyString(eventData.field.PAY_SYSTEM_NAME))
						{
							values[i].PAY_SYSTEM_NAME = BX.util.htmlspecialchars(eventData.field.PAY_SYSTEM_NAME);
						}
						if (BX.type.isNotEmptyString(eventData.field.PAY_SYSTEM_LOGO))
						{
							values[i].PAY_SYSTEM_LOGO_PATH = eventData.field.PAY_SYSTEM_LOGO;
						}
						if (BX.type.isNotEmptyString(eventData.field.FORMATED_TITLE_WITH_DATE_BILL))
						{
							values[i].NUMBER_AND_DATE = BX.util.htmlspecialchars(eventData.field.FORMATED_TITLE_WITH_DATE_BILL);
						}

						isChanged = true;
					}
				}
			}
		}
		else if (event.getEventId() === 'CrmOrderPayment::Create')
		{
			values = this.getValue();
			eventData = event.getData();
			if (
				eventData.entityTypeId == BX.CrmEntityType.enumeration.orderpayment
				&& (BX.type.isNotEmptyString(eventData.field.ID))
			)
			{
				var newValue = {
					ID: eventData.field.ID,
					PAID: (eventData.field.PAID === 'Y') ? 'Y' : 'N',
					NUMBER_AND_DATE: BX.util.htmlspecialchars(eventData.field.FORMATED_TITLE_WITH_DATE_BILL) || '',
					PAY_SYSTEM_LOGO_PATH:  BX.util.htmlspecialchars(eventData.field.PAY_SYSTEM_LOGO) || '',
					PAY_SYSTEM_NAME:  BX.util.htmlspecialchars(eventData.field.PAY_SYSTEM_NAME) || '',
					FORMATTED_SUM_WITH_CURRENCY: BX.util.htmlspecialchars(eventData.field.FORMATTED_SUM_WITH_CURRENCY) || '',
					FORMATTED_SUM: BX.util.htmlspecialchars(eventData.field.FORMATTED_SUM) || ''
				};

				values.push(newValue);
				isChanged = true;
				this._paymentBlocks[values.length - 1] = this.createPaymentContent(values.length - 1, newValue);
				this._view.appendChild(this._paymentBlocks[values.length - 1]);
			}
		}
		else if (event.getEventId() === 'CrmOrderPayment::Delete')
		{
			values = this.getValue();
			eventData = event.getData();
			if (
				eventData.entityTypeId == BX.CrmEntityType.enumeration.orderpayment
				&& (parseInt(eventData.ID) > 0)
			)
			{
				for (var i in values)
				{
					if (values[i].ID == eventData.ID)
					{
						values.splice(i, 1);
						this._view.removeChild(this._paymentBlocks[i]);
						break;
					}
				}
				isChanged = true;
			}
		}

		if (isChanged)
		{
			this._model.setField(this.getName(), values, { originator: this });
			this.refreshLayout();

			var eventParams = {
				"entityData": this._model.getData()
			};
			BX.onCustomEvent(window, BX.Crm.EntityEvent.names.update, [eventParams]);
		}
	};

	BX.Crm.EntityEditorPayment.prototype.togglePaidButtonMenu = function(index)
	{
		if(this._isPaidButtonMenuOpened[index])
		{
			this.closePaidButtonMenu(index);
		}
		else
		{
			this.openPaidButtonMenu(index);
		}
	};

	BX.Crm.EntityEditorPayment.prototype.closePaidButtonMenu = function(index)
	{
		if(!this._isPaidButtonMenuOpened[index])
		{
			return;
		}

		var menu = BX.PopupMenu.getMenuById(this._id);
		if(menu)
		{
			menu.popupWindow.close();
		}
		this._isPaidButtonMenuOpened[index] = false;
	};

	BX.Crm.EntityEditorPayment.prototype.setPaidButtonView = function(index, isPaid)
	{
		var button = BX('crm_entity_editor_is_payment_paid_button_'+index),
			buttonMain = BX('crm_entity_editor_is_payment_paid_button_main_'+index);

		if(isPaid)
		{
			BX.removeClass(button, 'ui-btn-danger-light');
			BX.addClass(button, 'ui-btn-success-light');
			buttonMain.innerHTML = this.getMessage('paymentWasPaid');
		}
		else
		{
			BX.removeClass(button, 'ui-btn-success-light');
			BX.addClass(button, 'ui-btn-danger-light');
			buttonMain.innerHTML = this.getMessage('paymentWasNotPaid');
		}
	};

	BX.Crm.EntityEditorPayment.prototype.openPaidButtonMenu = function(index)
	{
		var _this = this,
			input = BX('crm_entity_editor_is_payment_paid_input_'+index),
			isReturnInput = BX('crm_entity_editor_is_payment_paid_is_return_'+index);

		var handler = function(e, command){
			var value = BX.prop.getString(command, "value");

			if(value === "CANCEL" || value === "RETURN")
			{
				if(value === "RETURN")
				{
					isReturnInput.value = 'Y';

					if(!_this._isCreateMode)
					{
						BX.Crm.Page.openSlider(_this.getItemField(index, 'PAY_RETURN_URL'), { width: 500 });
					}
				}
				else
				{
					input.value = 'N';
					if(!_this._isCreateMode)
					{
						BX.Crm.Page.openSlider(_this.getItemField(index, 'PAY_CANCEL_URL'), { width: 500 });
					}
				}
			}
			else if(value === "SET_PAID")
			{
				input.value = 'Y';
				isReturnInput.value = 'N';

				if(!_this._isCreateMode)
				{
					if(_this._editor.isChanged())
					{
						_this.setPaidStatus(input.value, isReturnInput.value, index);
						_this.markAsChanged();
						_this.getOrderController().onDataChanged();
					}
					else
					{
						_this.getOrderController().ajax(
							'setPaymentPaidField',
							{ data: {
								FIELDS: {
									PAID: 'Y',
									PAYMENT_ID: _this.getItemField(index, 'ID')
								}
							}},
							{
								skipMarkAsChanged: true,
								needProductComponentParams: _this.getOrderController().isProductListLoaded()
							}
						);
					}
				}
			}

			_this.closePaidButtonMenu(index);
			_this.setPaidStatus(input.value, isReturnInput.value, index);
			_this.setPaidButtonView(index, (input.value === 'Y'));
		};

		if(input.value === 'Y')
		{
			if(this._isCreateMode)
			{
				var menu =
					[
						{value: 'CANCEL', text: this.getMessage('paymentWasNotPaid'), onclick: handler}
					];
			}
			else
			{
				menu =
					[
						{value: 'CANCEL', text: this.getMessage('paymentCancel'), onclick: handler},
						{value: 'RETURN', text: this.getMessage('paymentReturn'), onclick: handler}
					];
			}
		}
		else
		{
			menu =
				[
					{value: 'SET_PAID', text: this.getMessage('paymentWasPaid'), onclick: handler}
				];
		}

		BX.PopupMenu.show(
			this._id,
			this._paidButtons[index],
			menu,
			{
				angle: false,
				events:
					{
						onPopupShow: BX.proxy( function(){ this.onMenuShow(index); }, this),
						onPopupClose: BX.delegate( this.onMenuClose, this)
					}
			}
		);
	};

	BX.Crm.EntityEditorPayment.prototype.onMenuShow = function(index)
	{
		this._isPaidButtonMenuOpened[index] = true;
	};

	BX.Crm.EntityEditorPayment.prototype.onMenuClose = function()
	{
		BX.PopupMenu.destroy(this._id);
	};

	BX.Crm.EntityEditorPayment.prototype.refreshLayout = function()
	{
		if(!this._hasLayout)
		{
			return;
		}

		var value = this._model.getField(this.getName()), newPaymentBlock;
		this.clearError();

		if (this._mode === BX.UI.EntityEditorMode.view)
		{
			for(var i in value)
			{
				if(value.hasOwnProperty(i))
				{
					newPaymentBlock = this.createPaymentContent(i, value[i]);
					this._paymentBlocks[i].parentNode.replaceChild(newPaymentBlock, this._paymentBlocks[i]);
					this._paymentBlocks[i] = newPaymentBlock;
					this._paidInput[i].value = value[i].PAID;
					this._sumFormatted[i] = this.createSumFormatted(i, value[i]);

					if(value[i].ERRORS)
					{
						this.processErrors(value[i].ERRORS);
					}
				}
			}
		}
		else
		{
			for(i in value)
			{
				if(value.hasOwnProperty(i))
				{
					if(typeof (value[i].FORMATTED_SUM) !== 'undefined')
					{
						this._sumInput[i].value = value[i].FORMATTED_SUM;
					}

					if(typeof (value[i].CURRENCY_NAME) !== 'undefined')
					{
						var newCurrency = this.createCurrency(i, value[i]);
						this._currency[i].parentNode.replaceChild(newCurrency, this._currency[i]);
						this._currency[i] = newCurrency;
					}

					newPaymentBlock = this.createPaymentContent(i, value[i]);
					this._paymentBlocks[i].parentNode.replaceChild(newPaymentBlock, this._paymentBlocks[i]);
					this._paymentBlocks[i] = newPaymentBlock;

					this.setPaySystem(i, value[i]);
					this._logoImg[i].src = value[i].PAY_SYSTEM_LOGO_PATH;
					this._datePaid[i].value = value[i].DATE_PAID;
					this._isReturn[i].value = value[i].IS_RETURN;
					this._paidInput[i].value = value[i].PAID;
					this.setPaidButtonView(i, value[i].PAID === 'Y');

					if(value[i].ERRORS)
					{
						this.processErrors(value[i].ERRORS);
					}
				}
			}
		}
	};

	BX.Crm.EntityEditorPayment.prototype.setCurrency = function(index, value)
	{
		if(this._mode === BX.UI.EntityEditorMode.edit)
		{
			this._paySystemInputs[index].value = value.PAY_SYSTEM_ID;
		}
		else
		{
			this._paySystemName[index] = this.createPaySystemName(index, value);
		}
	};

	BX.Crm.EntityEditorPayment.prototype.setPaySystem = function(index, value)
	{
		if(this._mode === BX.UI.EntityEditorMode.edit)
		{
			this._paySystemInputs[index].value = parseInt(value.PAY_SYSTEM_ID) > 0 ? value.PAY_SYSTEM_ID : 0;
		}
		else
		{
			this._paySystemName[index] = this.createPaySystemName(index, value);
		}
	};

	BX.Crm.EntityEditorPayment.prototype.getRuntimeValue = function()
	{
		return this.getModel().getField(this.getName());

	};

	BX.Crm.EntityEditorPayment.prototype.processModelChange = function(params)
	{
		if(BX.prop.get(params, "originator", null) === this)
		{
			return;
		}

		if(!BX.prop.getBoolean(params, "forAll", false)
			&& BX.prop.getString(params, "name", "") !== this.getName()
		)
		{
			return;
		}

		//Change price only during order creation
		if(this._isCreateMode)
		{
			//We are assume only first payment for create mode
			var index = 0;

			if(!this._customPaymentSumm[index])
			{
				var orderModel = this._editor._model;
				this.setItemField(index, 'SUM', orderModel.getField('PRICE'));
				this.setItemField(index, 'FORMATTED_SUM_WITH_CURRENCY', orderModel.getField('FORMATTED_PRICE_WITH_CURRENCY'));
				this.setItemField(index, 'FORMATTED_SUM', orderModel.getField('FORMATTED_PRICE'));
				this.setItemField(index, 'CURRENCY', orderModel.getField('CURRENCY'));
			}
		}

		this.refreshLayout();
	};


	BX.Crm.EntityEditorPayment.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorPayment();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorShipment === "undefined")
{
	BX.Crm.EntityEditorShipment = function()
	{
		BX.Crm.EntityEditorShipment.superclass.constructor.apply(this);
		this._view = null;
		this._isCreateMode = false;
		this._documentLinks = {};
		this._documentInnerData = {};
		this._deliverySelectors = {};
		this._profileSelectors = {};
		this._deliveryInputs = {};
		this._storeSelectors = {};
		this._inputStores = {};
		this._priceDeliveryInputs = {};
		this._logoImg = {};
		this._currency = {};
		this._headBlocks = {};
		this._shipmentBlocks = {};
		this._extraServices = {};
		this._discounts = {};
		this._isDeliveryAllowedCheckboxes = {};
		this._isDeductedCheckboxes = {};
		this._orderController = null;
	};

	BX.extend(BX.Crm.EntityEditorShipment, BX.Crm.EntityEditorField);

	if(typeof(BX.Crm.EntityEditorShipment.messages) === "undefined")
	{
		BX.Crm.EntityEditorShipment.messages = {};
	}
	BX.Crm.EntityEditorShipment.prototype.doInitialize = function()
	{
		BX.addCustomEvent('SidePanel.Slider:onMessage', BX.delegate(this.onExternalChange, this));
		this._isCreateMode = this._model.getField('ID', 0) <= 0;
	};
	BX.Crm.EntityEditorShipment.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorShipment.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};

	BX.Crm.EntityEditorShipment.prototype.getItemField = function(index, name)
	{
		var value = this._model.getField(this.getName()),
			result = '';

		if(value[index] && value[index] && value[index][name])
		{
			result = value[index][name];
		}

		return result;
	};

	BX.Crm.EntityEditorShipment.prototype.setItemField = function(index, name, value)
	{
		var val = this._model.getField(this.getName());

		if(val[index])
		{
			val[index][name] = value;
			this._model.setField(this.getName(), val, { originator: this });
		}
	};

	BX.Crm.EntityEditorShipment.prototype.getOrderController = function()
	{
		if(this._orderController === null)
		{
			for(var i = 0, length = this._editor._controllers.length; i < length; i++)
			{
				if (this._editor._controllers[i] instanceof BX.Crm.EntityEditorOrderController)
				{
					this._orderController = this._editor._controllers[i];
					break;
				}
			}
		}

		return this._orderController;
	};

	BX.Crm.EntityEditorShipment.prototype.createDeliverySelector = function(index)
	{
		return BX.create('select',{
			events: {
				change: BX.proxy(function() { this.onSelectorChange(index, 'delivery'); }, this)
			}
		});
	};

	BX.Crm.EntityEditorShipment.prototype.createProfileSelector = function(index)
	{
		return BX.create('select',{
			events: {
				change: BX.proxy(function() { this.onSelectorChange(index, 'profile'); }, this)
			}
		});
	};

	BX.Crm.EntityEditorShipment.prototype.setOptionsList = function(selector, list, value)
	{
		return BX.Crm.EntityEditorDeliverySelector.prototype.setOptionsList(selector, list, value);
	};

	BX.Crm.EntityEditorShipment.prototype.obtainValueFromSelectors = function(changedObject, deliverySelector, profileSelector, mode)
	{
		return BX.Crm.EntityEditorDeliverySelector.prototype.obtainValueFromSelectors(changedObject, deliverySelector, profileSelector, mode);
	};

	BX.Crm.EntityEditorShipment.prototype.onSelectorChange = function(index, changedObject)
	{
		this._deliveryInputs[index].value = this.obtainValueFromSelectors(
			changedObject,
			this._deliverySelectors[index],
			this._profileSelectors[index],
			this._mode
		);

		this.setItemField(index, 'DELIVERY_ID', this._deliveryInputs[index].value);
		this.markAsChanged();
		this.getOrderController().onChangeDelivery(index);
	};

	BX.Crm.EntityEditorShipment.prototype.getHeadBlock = function(index)
	{
		var result = null;

		if(this._isCreateMode)
		{
			this._deliverySelectors[index] = this.createDeliverySelector(index);

			this.setOptionsList(
				this._deliverySelectors[index],
				this.getItemField(index, 'DELIVERY_SERVICES_LIST'),
				this.getItemField(index, 'DELIVERY_SELECTOR_DELIVERY_ID')
			);

			var profilesList = this.getItemField(index, 'DELIVERY_PROFILES_LIST');

			if(BX.type.isNotEmptyObject(profilesList))
			{
				this._profileSelectors[index] = this.createProfileSelector(index);

				this.setOptionsList(
					this._profileSelectors[index],
					profilesList,
					this.getItemField(index, 'DELIVERY_SELECTOR_PROFILE_ID')
				);
			}
			else
			{
				delete(this._profileSelectors[index]);
			}

			this._deliveryInputs[index] = BX.create("input", {
				props: {
					type: "hidden",
					name: 'SHIPMENT['+index+'][DELIVERY_ID]',
					value: this.getItemField(index, 'DELIVERY_ID')
				}
			});

			var storesList = this.getItemField(index, 'DELIVERY_STORES_LIST');

			if(BX.type.isNotEmptyObject(storesList))
			{
				this._storeSelectors[index] = this.createStoreSelector(index);

				this.setOptionsList(
					this._storeSelectors[index],
					storesList,
					this.getItemField(index, 'DELIVERY_STORE_ID')
				);

				this.createInputStore(index);
			}
			else
			{
				delete(this._storeSelectors[index]);
			}

			result =
				BX.create('div',{ style: {width: '100%'}, children:[
					BX.create('span', {props: {className: 'fields enumeration field-wrap'}, style: {width: '100%'}, children:[
						BX.create("div", {
							props: { className: "crm-entity-widget-content-block-title" },
							style: {marginTop: '-6px'},
							children: [
								BX.create(
									"span",
									{
										attrs: { className: "crm-entity-widget-content-block-title-text" },
										text: this.getMessage('deliveryService')
									}
								)
							]
						}),
						BX.create('span', {props: {className: 'fields enumeration field-item'}, children:
							[
								this._deliverySelectors[index],
								this._deliveryInputs[index]
							]
						})
					]})
				]});

			if(this._profileSelectors[index])
			{
				result.appendChild(
					BX.create('span', {props: {className: 'fields enumeration field-wrap'}, style: {width: '100%'}, children:[
						BX.create("div", {
							props: { className: "crm-entity-widget-content-block-title" },
							style: {marginTop: '3px'},
							children: [
								BX.create(
									"span",
									{
										props: { className: "crm-entity-widget-content-block-title-text" },
										text: this.getMessage('profile')
									}
								)
							]
						}),
						BX.create('span', {props: {className: 'fields enumeration field-item'}, children:
							[
								this._profileSelectors[index]
							]
						})
					]})
				);
			}

			if(this._storeSelectors[index])
			{
				result.appendChild(
					BX.create('span', {props: {className: 'fields enumeration field-wrap'}, style: {marginTop: '20px'}, children:[
						BX.create("div", {
							props: { className: "crm-entity-widget-content-block-title" },
							style: {marginTop: '-16px'},
							children: [
								BX.create(
									"span",
									{
										attrs: { className: "crm-entity-widget-content-block-title-text" },
										text: this.getMessage('deliveryStore')
									}
								)
							]
						}),
						BX.create('span', {props: {className: 'fields enumeration field-item'}, children:[
							this._storeSelectors[index],
							this.getInputStore(index)
						]})
					]}),
					result
				);
			}

			result = BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-box-title'}, children:[
				result
			]});

		}
		else
		{
			result = this.createDeliveryServiceName(index);
		}

		return result;
	};

	BX.Crm.EntityEditorShipment.prototype.createDeliveryServiceName = function(index)
	{
		return BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-box-title'}, children:[
				BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-box-title-text'}, html: this.getItemField(index, 'DELIVERY_SERVICE_NAME')}),
				BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-box-title-desc'}, children:[
						BX.create('a', {
							style: {cursor: 'pointer'},
							html: this.getItemField(index, 'NUMBER_AND_DATE'),
							events:{
								click: BX.proxy(function(){
										this.getOrderController().openDetailSlider(
											'/shop/orders/shipment/details/'+parseInt(this.getItemField(index, 'ID'))+'/'
										);
									},
									this
								)
							}
						})
					]})
			]});
	};

	BX.Crm.EntityEditorShipment.prototype.getInputStore = function(index)
	{
		if(!this._inputStores[index])
		{
			this.createInputStore(index);
		}

		return this._inputStores[index];
	};

	BX.Crm.EntityEditorShipment.prototype.createInputStore = function(index)
	{
		if(this._inputStores[index])
		{
			return;
		}

		this._inputStores[index] = BX.create("input", {
			props: {
				type: "hidden",
				name: 'SHIPMENT['+index+'][DELIVERY_STORE_ID]',
				value: this.getItemField(index, 'DELIVERY_STORE_ID')
			}
		});
	};

	BX.Crm.EntityEditorShipment.prototype.createStoreSelector = function(index)
	{
		return BX.create('select',{
			events: {
				change: BX.proxy(function() { this.onStoreChange(index); }, this)
			}
		});
	};

	BX.Crm.EntityEditorShipment.prototype.onStoreChange = function(index)
	{
		this.getInputStore(index).value = this._storeSelectors[index].value;
		this.setItemField(index, 'DELIVERY_STORE_ID', this._storeSelectors[index].value);
		this.markAsChanged();
	};

	BX.Crm.EntityEditorShipment.prototype.processErrors = function(errors)
	{
		if(errors.length)
		{
			var message = '';

			for(var i = 0, l = errors.length - 1; i <= l; i++)
			{
				message += errors[i]+"\n";
			}

			if(message)
			{
				this.showError(message);
			}
		}
	};

	BX.Crm.EntityEditorShipment.prototype.onPriceDeliveryChange = function(index, value)
	{
		value = value.split(' ').join('');
		this.setItemField(index, 'PRICE_DELIVERY', value);
		this.setItemField(index, 'CUSTOM_PRICE_DELIVERY', 'Y');
		this.getOrderController().onDataChanged();
	};

	BX.Crm.EntityEditorShipment.prototype.onPriceDeliveryInput = function(e)
	{
		e.target.value = BX.Currency.Editor.getFormattedValue(
			e.target.value,
			this.getModel().getField('CURRENCY', '')
		)
	};

	BX.Crm.EntityEditorShipment.prototype.createCurrency = function(index, value)
	{
		return BX.create('span', {props: {className: 'crm-entity-widget-content-block-wallet'}, html: value.CURRENCY_NAME});
	};

	BX.Crm.EntityEditorShipment.prototype.createPriceContent = function(index, value)
	{
		var result = null,
			_this = this;

		this._priceDeliveryInputs[index] = BX.create("input",{
			attrs:{
				className: 'crm-entity-widget-content-input',
				name: 'SHIPMENT['+index+'][PRICE_DELIVERY]',
				id: 'crm_entity_editor_delivery_price_'+index,
				type: 'text',
				value: value.FORMATTED_PRICE_DELIVERY,
				size: 40
			},
			events: {
				change: function(){
					_this.onPriceDeliveryChange(index, this.value);
				},
				input: BX.delegate(_this.onPriceDeliveryInput, _this)
			}
		});

		if(this._isCreateMode)
		{
			this._currency[index] = this.createCurrency(index, value);

			result =
				BX.create('div', {props: {className: 'crm-entity-widget-content-block crm-entity-widget-content-block-field-money'}, children:[
					BX.create('div', {props: {className: 'crm-entity-widget-content-block-title'}, children:[
						BX.create('span', {props: {className: 'crm-entity-widget-content-block-title-text'}, html: this.getMessage('price')})
					]}),
					BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner crm-entity-widget-content-block-colums-input'}, children:[
						BX.create('div', {props: {className: 'crm-entity-widget-content-block-input-wrapper'}, children:[
							this._priceDeliveryInputs[index],
							this._currency[index]
						]})
					]}),
					BX.create('div', {
						props: {className: 'crm-entity-widget-content-block-title price-calculated'},
						style: {display: 'none'},
						children:[
							BX.create('span', {
								props: {
									className: 'crm-entity-widget-content-block-title-text'
								},
								children:[
									BX.create('span', { html: this.getMessage('deliveryPriceCalculated') + ' '}),
									BX.create('span', {
										html: '',
										props: {
											id: 'crm-order-delivery-calculated-price-' + index,
											title: this.getMessage('deliveryPriceCalculatedHint')
										},
										style: { borderBottom: '1px dashed', cursor: 'pointer' },
										events:{ click: BX.proxy(function(){
											this.setItemField(index, 'PRICE_DELIVERY', this.getItemField(index, 'PRICE_DELIVERY_CALCULATED'));
											this._priceDeliveryInputs[index].value = this.getItemField(index, 'FORMATTED_PRICE_DELIVERY_CALCULATED');
											this.setItemField(index, 'CUSTOM_PRICE_DELIVERY', 'N');
											this.getOrderController().onDataChanged();
										},this)}
									})
								]
							})
						]
					}),
					BX.create('div', {
						props: {className: 'crm-entity-widget-content-block-title'},
						children:[
							BX.create('div', {
								props: {
									className: 'ui-btn ui-btn-light-border',
								},
								style: {marginTop: '10px'},
								events: {
									click: function () {
										_this.getOrderController().onDataChanged();
									}
								},
								html: this.getMessage('refresh')
							})
						]
					})
				]});
		}
		else
		{
			result =
				BX.create('div', {props: {className: 'crm-entity-widget-content-block crm-entity-widget-content-block-field-money'}, children:[
					BX.create('div', {props: {className: 'crm-entity-widget-content-block-title'}, children:[
						BX.create('span', {props: {className: 'crm-entity-widget-content-block-title-text'}, html: this.getMessage('price')})
					]}),
					BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner'}, children:[
						BX.create('div', {props: {className: 'crm-entity-widget-content-block-colums-block'}, children:[
							BX.create('div', {props: {className: 'crm-entity-widget-content-block-wallet'}, children:[
								BX.create('span', {
									props: {
										className: 'crm-entity-widget-content-block-colums-right',
										id: 'crm-order-delivery-price-'+index
									},
									html: value.FORMATTED_PRICE_DELIVERY_WITH_CURRENCY
								})
							]})
						]})
					]})
				]});
		}

		return result;
	};

	BX.Crm.EntityEditorShipment.prototype.createExtraServicesContent = function(index, value)
	{
		var result = [],
			mode = this._isCreateMode ? 'edit': 'view',
			wrapper = new BX.Crm.EntityEditorOrderExtraServicesWrapper({mode: mode});

		if(value.EXTRA_SERVICES_DATA && value.EXTRA_SERVICES_DATA.length)
		{
			for(var i = 0, l = value.EXTRA_SERVICES_DATA.length -1; i <= l; i++)
			{
				wrapper.wrapItem(value.EXTRA_SERVICES_DATA[i]).forEach(function(item, i, arr){
					result.push(item);
				});
			}
		}

		return result;
	};

	BX.Crm.EntityEditorShipment.prototype.createDiscountsContent = function(index, value)
	{
		/*
		if(!this._isCreateMode)
		{
			return;
		}
		*/

		var result = [],
			_this = this;

		if(value.DISCOUNTS && value.DISCOUNTS.length)
		{
			for(var i = 0, l = value.DISCOUNTS.length -1; i <= l; i++)
			{
				var discount = value.DISCOUNTS[i],
					input = BX.create('input', {
						props: {
							type: 'checkbox',
							name: 'DISCOUNTS[DELIVERY][' + discount.DISCOUNT_ID + ']',
							value: 'Y',
							checked: discount.APPLY === 'Y'
						},
						attrs: {
							'data-discount-id': discount.DISCOUNT_ID,
							'data-coupon-id': discount.COUPON_ID ? discount.COUPON_ID : '-'
						},
						events: {
							click: function(){ _this.onDiscountClick(this); }
						}
					});

				BX.addCustomEvent(
					'crmOrderProductListDiscountToggle',
					(function(input){
						return function(data)
						{
							if(input.getAttribute('data-discount-id') == data.discountId)
							{
								input.checked = data.isSet;
							}
						}
				})(input));

				var item = BX.create('div', {props: {className: 'crm-entity-widget-content-block crm-entity-widget-content-block-field-checkbox'}, children:[
					BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner'}, children:[
						BX.create('label', {props: {className: 'crm-entity-widget-content-block-checkbox-label'}, children:[
							BX.create('input', {props: {
								type: 'hidden',
								name: 'DISCOUNTS[DELIVERY]['+discount.DISCOUNT_ID+']',
								value: 'N'
							}}),
							input,
							BX.create('span', {
								props: {className: 'crm-entity-widget-content-block-checkbox-description'},
								html: discount.DESCR
							})
						]})
					]})
				]});

				result.push(item);
			}
		}

		return BX.create('div',{children: result});
	};

	BX.Crm.EntityEditorShipment.prototype.onDiscountClick = function(checkbox)
	{
		BX.onCustomEvent('crmOrderDetailDiscountToggle', [{
			discountId: checkbox.getAttribute('data-discount-id'),
			isSet: checkbox.checked
		}]);

		this.getOrderController().onDataChanged();
	};

	BX.Crm.EntityEditorShipment.prototype.createShipmentContent = function(index, value)
	{
		var	statusControl = value.STATUS_CONTROL,
			statusControlData = BX.processHTML(statusControl),
			extraServices = null;

		this._isDeliveryAllowedCheckboxes[index] = BX.create('input', {
			props: {
				className: 'crm-entity-widget-content-checkbox',
				name: 'SHIPMENT['+index+'][ALLOW_DELIVERY]',
				value: 'Y',
				checked: value.ALLOW_DELIVERY === 'Y',
				type: 'checkbox'
			}
		});
		this._isDeductedCheckboxes[index] = BX.create('input', {
			props: {
				className: 'crm-entity-widget-content-checkbox',
				name: 'SHIPMENT['+index+'][DEDUCTED]',
				value: 'Y',
				checked: value.DEDUCTED === 'Y',
				type: 'checkbox'
			}
		});

		this._logoImg[index] = BX.create('img', {props: {
			className: 'crm-entity-widget-content-block-inner-order-logo',
			id: 'crm_entity_editor_delivery_logotip_'+index,
			src: value.DELIVERY_LOGO ? value.DELIVERY_LOGO : '',
			alt: value.DELIVERY_SERVICE_NAME
		}});

		if(!value.DELIVERY_LOGO)
		{
			this._logoImg[index].style.display = 'none';
		}

		this._headBlocks[index] = this.getHeadBlock(index, value);

		if(this._isCreateMode)
		{
			BX.bind(
				this._isDeliveryAllowedCheckboxes[index],
				"change",
				BX.delegate(function(e){ this.setItemField(index, 'ALLOW_DELIVERY', e.target.checked ? 'Y' : 'N', value.ID)}, this)
			);
			BX.bind(
				this._isDeductedCheckboxes[index],
				"change",
				BX.delegate(function(e){this.setItemField(index, 'DEDUCTED', e.target.checked ? 'Y' : 'N', value.ID)}, this)
			);

			this._extraServices[index] = this.createExtraServicesContent(index, value);

			extraServices = BX.create('div', {
				props: {
					id: 'crm-order-shipment-extra-services-'+index,
					className: 'crm-order-shipment-extra-services-'+index
				},
				style:{background: '#f9f9f9', marginLeft: '15px', padding: '10px 0'},
				children: this._extraServices[index]
			});
		}
		else
		{
			BX.bind(
				this._isDeliveryAllowedCheckboxes[index],
				"change",
				BX.delegate(function(e){ this.setField('ALLOW_DELIVERY', e.target.checked ? 'Y' : 'N', value.ID, index)}, this)
			);
			BX.bind(
				this._isDeductedCheckboxes[index],
				"change",
				BX.delegate(function(e){this.setField('DEDUCTED', e.target.checked ? 'Y' : 'N', value.ID, index)}, this)
			);
		}

		this._discounts[index] = this.createDiscountsContent(index, value);

		discounts = BX.create('div', {
			props: {id: 'crm-order-shipment-discounts-'+index},
			style:{background: '#f9f9f9', marginLeft: '15px', width: '100%', marginTop: '20px'},
			children: [this._discounts[index]]
		});

		var content =
			BX.create('div', {props: {className: 'crm-entity-widget-content-block'}, children:[
				BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner'}, children:[
					BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-box'}, children:[
						this._headBlocks[index],
						BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-box-content-container'}, children:[
							BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-box-content-left'}, children:[
								BX.create('div', {props: {
									className: 'crm-entity-widget-content-block-inner-order-logo-container'}, children:[
									this._logoImg[index]
								]})
							]}),
							BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-box-content-right'}, children:[
								BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-order-controls'}, children:[
									this.createPriceContent(index, value),
									BX.create('div', {props: {className: 'crm-entity-widget-content-block crm-entity-widget-content-block-field-checkbox'}, children:[
										BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner'}, children:[
											BX.create('label', {props: {className: 'crm-entity-widget-content-block-checkbox-label'}, children:[
												this._isDeliveryAllowedCheckboxes[index],
												BX.create('span', {props: {className: 'crm-entity-widget-content-block-checkbox-description'}, html: this.getMessage('deliveryAllowed')}),
												BX.create('input', {props: {type: 'hidden', name: 'SHIPMENT['+index+'][ALLOW_DELIVERY]', value: 'N'}})
											]})
										]})
									]}),
									BX.create('div', {props: {className: 'crm-entity-widget-content-block crm-entity-widget-content-block-field-checkbox'}, children:[
										BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner'}, children:[
											BX.create('label', {props: {className: 'crm-entity-widget-content-block-checkbox-label'}, children:[
												this._isDeductedCheckboxes[index],
												BX.create('span', {props: {className: 'crm-entity-widget-content-block-checkbox-description'}, html: this.getMessage('deducted')}),
												BX.create('input', {props: {type: 'hidden', name: 'SHIPMENT['+index+'][DEDUCTED]', value: 'N'}})
											]})
										]})
									]}),
									BX.create('div', {props: {className: 'crm-entity-widget-content-block crm-entity-widget-content-block-field-progress'}, children:[
										BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner'}, html: statusControlData['HTML']})
									]}),
									extraServices,
									discounts
								]})
							]})
						]})
					]})
				]})
			]});

		setTimeout(function(){
				for (var i in statusControlData['SCRIPT'])
				{
					if(!statusControlData['SCRIPT'].hasOwnProperty(i))
						continue;

					BX.evalGlobal(statusControlData['SCRIPT'][i]['JS']);
					delete(statusControlData['SCRIPT'][i]);
				}
			},
			1
		);

		if (BX.type.isNotEmptyString(value.DOCUMENT_INFO))
		{
			text = this.getMessage('documentTitle') + ": "+ value.DOCUMENT_INFO;
		}
		else if(BX.type.isNotEmptyString(value.TRACKING_NUMBER))
		{
			text = this.getMessage('trackingNumberTitle') + ": "+ value.TRACKING_NUMBER;
		}
		else
		{
			text = this.getMessage('addDocument');
		}

		this._documentLinks[value.ID] = BX.create('a', {
			props: {className: 'crm-entity-widget-content-block-edit-action-btn'},
			text: text,
			events: { click: BX.delegate( function(e){this.onAddDocumentClick(e, value.ID)}, this)}
		});

		if (BX.prop.getNumber(value, 'ID', 0) === 0)
		{
			this._documentInnerData[value.ID] = {
				index: index,
				values: {
					TRACKING_NUMBER: BX.prop.getString(value, 'TRACKING_NUMBER', ''),
					DELIVERY_DOC_NUM: BX.prop.getString(value, 'DELIVERY_DOC_NUM', ''),
					DELIVERY_DOC_DATE: BX.prop.getString(value, 'DELIVERY_DOC_DATE', '')
				}
			};
		}

		content.appendChild(
			BX.create('div', {props: {className: 'crm-entity-widget-content-block'}, children:[this._documentLinks[value.ID]]})
		);

		return content;
	};

	BX.Crm.EntityEditorShipment.prototype.onAddDocumentClick = function(e, id)
	{
		var documentLink = this._schemeElement.getDataStringParam("addShipmentDocumentUrl", "");

		var params = {
			shipment_id: id
		};
		if (BX.type.isNotEmptyObject(this._documentInnerData[id]))
		{
			var docValues = this._documentInnerData[id].values;
			for (var key in docValues)
			{
				if(docValues.hasOwnProperty(key))
				{
					params['ENTITY_DATA['+key+']'] = docValues[key];
				}
			}
		}

		documentLink = BX.util.add_url_param(
			documentLink,
			params
		);

		BX.addCustomEvent('SidePanel.Slider:onMessage', BX.delegate(this.onDocumentUpdate, this));
		BX.Crm.Page.openSlider(documentLink, { width: 500 });
	};

	BX.Crm.EntityEditorShipment.prototype.getRuntimeValue = function()
	{
		return this.getModel().getField(this.getName());
	};

	BX.Crm.EntityEditorShipment.prototype.onDocumentUpdate = function(event)
	{
		if (event.getEventId() === 'CrmOrderShipmentDocument::Update')
		{
			var eventArgs = event.getData();
			var shipmentId = BX.prop.getString(eventArgs, "entityId", 0);
			if(BX.prop.getInteger(eventArgs, "entityTypeId", 0) !== BX.CrmEntityType.enumeration.ordershipment
				|| !BX.type.isDomNode(this._documentLinks[shipmentId])
			)
			{
				return;
			}

			var documentNumber = BX.prop.getString(eventArgs, "deliveryDocNum", "");
			var trackingNumber = BX.prop.getString(eventArgs, "trackingNumber", "");
			var documentDate = BX.prop.getString(eventArgs, "deliveryDocDate", "");
			var text = "";
			if (BX.type.isNotEmptyString(documentNumber))
			{
				var documentInfo = BX.util.htmlspecialchars(documentNumber);
				if (BX.type.isNotEmptyString(documentDate))
				{
					documentInfo += " " + BX.util.htmlspecialchars(documentDate);
				}
				text = this.getMessage('documentTitle') + ": "+ documentInfo;
			}
			else
			{
				if (BX.type.isNotEmptyString(trackingNumber))
				{
					text = this.getMessage('trackingNumberTitle') + ": "+ trackingNumber;
				}
				else
				{
					text = this.getMessage('addDocument');
				}
			}

			this._documentLinks[shipmentId].innerHTML = text;

			if (BX.type.isNotEmptyObject(this._documentInnerData[shipmentId]))
			{
				this._documentInnerData[shipmentId]['values']['TRACKING_NUMBER'] = trackingNumber;
				this._documentInnerData[shipmentId]['values']['DELIVERY_DOC_NUM'] = documentNumber;
				this._documentInnerData[shipmentId]['values']['DELIVERY_DOC_DATE'] = documentDate;
				var index = this._documentInnerData[shipmentId]['index'];
				var value = this._model.getField(this.getName());
				if (BX.type.isNotEmptyObject(value[index]))
				{
					value[index]['TRACKING_NUMBER'] = trackingNumber;
					value[index]['DELIVERY_DOC_NUM'] = documentNumber;
					value[index]['DELIVERY_DOC_DATE'] = documentDate;
					this._model.setField(this.getName(), value);
				}
			}
		}
	};

	BX.Crm.EntityEditorShipment.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		var value = this.getValue();

		this._wrapper = BX.create("div");
		this._view = null;

		var enableDrag = this.isDragEnabled();
		if(enableDrag)
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		var enableContextMenu = this.isContextMenuEnabled();

		if(this.checkIfNotEmpty(value))
		{
			this._view = BX.create('div');

			if(value && value.length)
			{
				for(var i=0, l=value.length; i<l; i++)
				{
					this._shipmentBlocks[i] = this.createShipmentContent(i, value[i]);
					this._view.appendChild(this._shipmentBlocks[i]);
				}
			}

			this._wrapper.appendChild(this._view);
		}

		if(enableContextMenu)
		{
			this._wrapper.appendChild(this.createContextMenuButton());
		}

		if(enableDrag)
		{
			this.initializeDragDropAbilities();
		}

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorShipment.prototype.setField = function(fieldName, fieldValue, shipmentId, index)
	{
		if(this._editor.isChanged())
		{
			this.setItemField(index, fieldName, fieldValue);
			this.markAsChanged();
			this.getOrderController().onDataChanged();
		}
		else
		{
			this.getOrderController().ajax(
				'setShipmentField',
				{
					data: {
						SHIPMENT_ID: shipmentId,
						FIELD_NAME: fieldName,
						FIELD_VALUE: fieldValue
					}
				},
				{
					skipMarkAsChanged: true,
					needProductComponentParams: this.getOrderController().isProductListLoaded()
				}
			);
		}
	};

	BX.Crm.EntityEditorShipment.prototype.setCalculatedPrice = function(index, visible, price)
	{
		var container = BX('crm-order-delivery-calculated-price-' + index);

		if(!container)
		{
			return;
		}

		if(price)
		{
			container.innerHTML = price;
		}

		container.parentNode.parentNode.style.display = visible ? '' : 'none';
	};

	BX.Crm.EntityEditorShipment.prototype.refreshLayout = function()
	{
		if(!this._hasLayout)
		{
			return;
		}

		var value = this._model.getField(this.getName());
		this.clearError();

		for(var i in value)
		{
			if(value.hasOwnProperty(i))
			{
				var newHeadBlock = this.getHeadBlock(i);

				this._headBlocks[i].parentNode.replaceChild(newHeadBlock, this._headBlocks[i]);
				this._headBlocks[i] = newHeadBlock;
				this._isDeliveryAllowedCheckboxes[i].checked = value[i].ALLOW_DELIVERY === 'Y';
				this._isDeductedCheckboxes[i].checked = value[i].DEDUCTED === 'Y';

				if(value[i].DELIVERY_LOGO)
				{
					this._logoImg[i].src = value[i].DELIVERY_LOGO;
					this._logoImg[i].style.display = '';
				}
				else
				{
					this._logoImg[i].style.display = 'none';
					this._logoImg[i].src = '';
				}

				if(this._isCreateMode)
				{
					this._priceDeliveryInputs[i].value = value[i].FORMATTED_PRICE_DELIVERY;

					if(value[i].CUSTOM_PRICE_DELIVERY === 'Y' && parseFloat(value[i].PRICE_DELIVERY_CALCULATED) !== parseFloat(value[i].PRICE_DELIVERY))
					{
						this.setCalculatedPrice(i, true, value[i].FORMATTED_PRICE_DELIVERY_CALCULATED_WITH_CURRENCY);
					}
					else
					{
						this.setCalculatedPrice(i, false);
					}

					var newCurrency = this.createCurrency(i, value[i]);
					this._currency[i].parentNode.replaceChild(newCurrency , this._currency[i]);
					this._currency[i] = newCurrency;
					this._extraServices[i] = this.createExtraServicesContent(i, value[i]);
					this.insertExtraServicestoContainer(i, this._extraServices[i]);
				}
				else
				{
					BX('crm-order-delivery-price-'+i).innerHTML = value[i].FORMATTED_PRICE_DELIVERY_WITH_CURRENCY;
				}

				this._discounts[i] = this.createDiscountsContent(i, value[i]);
				this.insertDiscountsToContainer(i, this._discounts[i]);

				if(value[i].ERRORS)
				{
					this.processErrors(value[i].ERRORS);
				}
			}
		}
	};

	BX.Crm.EntityEditorShipment.prototype.insertExtraServicestoContainer = function(index, extraServices)
	{
		var esContainer = BX('crm-order-shipment-extra-services-'+index);

		if(esContainer)
		{
			esContainer.innerHTML = '';

			if(extraServices)
			{
				extraServices.forEach(function(item, i, arr)
				{
					esContainer.appendChild(item);
				});
			}
		}
	};

	BX.Crm.EntityEditorShipment.prototype.insertDiscountsToContainer = function(index, discounts)
	{
		var dContainer = BX('crm-order-shipment-discounts-'+index);

		if(!dContainer || !discounts)
		{
			return;
		}

		if(dContainer.childNodes[0])
		{
			dContainer.replaceChild(discounts, dContainer.childNodes[0]);
		}
		else
		{
			dContainer.appendChild(discounts);
		}
	};

	BX.Crm.EntityEditorShipment.prototype.processModelChange = function(params)
	{
		if(BX.prop.get(params, "originator", null) === this)
		{
			return;
		}

		if(!BX.prop.getBoolean(params, "forAll", false)
			&& BX.prop.getString(params, "name", "") !== this.getName()
		)
		{
			return;
		}

		this.refreshLayout();
	};

	BX.Crm.EntityEditorShipment.prototype.onExternalChange = function(event)
	{
		isChanged = false;
		if (event.getEventId() === 'CrmOrderShipment::Update')
		{
			var eventData = event.getData();
			if (eventData.entityTypeId == BX.CrmEntityType.enumeration.ordershipment)
			{
				var fields = ['DEDUCTED', 'ALLOW_DELIVERY', 'FORMATTED_PRICE_DELIVERY_WITH_CURRENCY', 'DELIVERY_SERVICE_NAME',
				'DELIVERY_SERVICES_LIST', 'DELIVERY_PROFILES_LIST', 'STATUS_CONTROL', 'DELIVERY_LOGO', 'DELIVERY_SELECTOR_DELIVERY_ID',
				'DELIVERY_SELECTOR_PROFILE_ID', 'DELIVERY_ID', 'PRICE_DELIVERY', 'BASE_PRICE_DELIVERY', 'EXTRA_SERVICES_DATA',
				'TRACKING_NUMBER', 'COMMENTS'],
					values = this.getValue(),
					isChanged = false;

				for (var i=0;i<values.length;i++)
				{
					if (values[i].ID == eventData.field.ID)
					{
						for(var j = fields.length-1; j>=0; j--)
						{
							if(typeof (eventData.field[fields[j]]) !== 'undefined')
							{
								values[i][fields[j]] = eventData.field[fields[j]];
							}
						}

						isChanged = true;
					}
				}
			}
		}
		else if (event.getEventId() === 'CrmOrderShipment::Create')
		{
			values = this.getValue();
			eventData = event.getData();
			if (
				eventData.entityTypeId == BX.CrmEntityType.enumeration.ordershipment
				&& (BX.type.isNotEmptyString(eventData.field.ID))
			)
			{
				var newValue = eventData.field;
				values.push(newValue);
				this._shipmentBlocks[values.length - 1] = this.createShipmentContent(values.length - 1, newValue);
				this._view.appendChild(this._shipmentBlocks[values.length - 1]);
				isChanged = true;
			}
		}
		else if (event.getEventId() === 'CrmOrderShipment::Delete')
		{
			values = this.getValue();
			eventData = event.getData();
			if (eventData.entityTypeId == BX.CrmEntityType.enumeration.ordershipment
				&& (parseInt(eventData.ID) > 0)
			)
			{
				for (var i in values)
				{
					if (values[i].ID == eventData.ID)
					{
						values.splice(i, 1);
						this._view.removeChild(this._shipmentBlocks[i]);
						break;
					}
				}
				isChanged = true;
			}
		}

		if (isChanged)
		{
			this._model.setField(this.getName(), values);
			this.refreshLayout();

			var eventParams = {
				"entityData": this._model.getData()
			};
			BX.onCustomEvent(window, BX.Crm.EntityEvent.names.update, [eventParams]);
		}
	};
	BX.Crm.EntityEditorShipment.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorShipment();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorPaymentStatus === "undefined")
{
	/**
	 * @extends BX.UI.EntityEditorField
	 */
	BX.Crm.EntityEditorPaymentStatus = function()
	{
		BX.Crm.EntityEditorPaymentStatus.superclass.constructor.apply(this);
		this._view = null;
		this._isPaidButtonMenuOpened = {};
		this._isCreateMode = false;
		this._paidButtons = {};
		this._paymentController = null;
	};

	BX.extend(BX.Crm.EntityEditorPaymentStatus, BX.Crm.EntityEditorField);

	BX.Crm.EntityEditorPaymentStatus.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		var	value = this.getValue();
		this.ensureWrapperCreated({ classNames: [ "crm-entity-widget-content-block-field-select" ] });
		this.adjustWrapper();
		this._view = null;

		var enableDrag = this.isDragEnabled();
		if(enableDrag)
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		var enableContextMenu = this.isContextMenuEnabled();

		this._view = BX.create('div');
		var isPaid = (value.isPaid === 'Y'),
			index = 0; // It's always for edit page.

		var title = this.getTitle();
		this._wrapper.appendChild(this.createTitleNode(title));
		this._paidButtons[index] = this.createPaymentButton(index, isPaid);

		var content =
			BX.create('div', {props: {className: 'crm-entity-widget-content-block crm-entity-widget-content-block-field-custom'}, children:[
				BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner'}, children:[
					BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-order-controls'}, children:[
						BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-order-controls-row'}, children:[
							this._paidButtons[index],
							BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-order-controls-text'}, html: value.DATE_PAID})
						]})
					]})
				]})
			]});

		this._view.appendChild(content);
		this._wrapper.appendChild(this._view);

		if(enableContextMenu)
		{
			this._wrapper.appendChild(this.createContextMenuButton());
		}

		if(enableDrag)
		{
			this.initializeDragDropAbilities();
		}

		options['preservePosition'] = false;
		this.registerLayout(options);
		this._hasLayout = true;
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.getDragObjectType = function()
	{
		return BX.UI.EditorDragObjectType.field;
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.createPaymentButton = function(index, isPaid)
	{
		var paymentButton = BX.create('div', {
			props: {
				className: 'ui-btn-split ui-btn-sm ' + (isPaid ? 'ui-btn-success-light' : 'ui-btn-danger-light'),
				id: 'crm_entity_editor_is_payment_paid_button_'+ index
			},
			children:[
				BX.create('button', {
					props: {
						className: 'ui-btn-main',
						id: 'crm_entity_editor_is_payment_paid_button_main_' + index
					},
					html: (isPaid ? this.getMessage('paymentWasPaid') : this.getMessage('paymentWasNotPaid'))
				}),
				BX.create('button', {props: {className: 'ui-btn-extra'}})
			]});

		BX.bind(paymentButton, "click", BX.delegate(function(e){this.onPaidButtonClick(e, index)}, this));

		return paymentButton;
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.doInitialize = function()
	{
		BX.addCustomEvent(window, BX.Crm.EntityEvent.names.update, BX.delegate(this.onAfterUpdate, this));
		BX.addCustomEvent('SidePanel.Slider:onMessage', BX.delegate(this.onPaymentVoucherUpdate, this));
		BX.addCustomEvent('SidePanel.Slider:onMessage', BX.delegate(this.onPaymentVoucherInited, this));
		this._isCreateMode = !(this._model._data.ID);
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.onAfterUpdate = function(item)
	{
		if (item.entityData)
		{
			if(this._model._data.hasOwnProperty('STATUS') && BX.type.isPlainObject(this._model._data['STATUS']))
			{
				if(item.entityData.PAID)
				{
					this._model._data['STATUS'].isPaid = item.entityData.PAID;
				}

				if(item.entityData.DATE_PAID)
				{
					this._model._data['STATUS'].datePaid = item.entityData.DATE_PAID;
				}
			}
		}
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.onPaymentVoucherUpdate = function(event)
	{
		if (event.getEventId() === 'CrmOrderPaymentVoucher::Update')
		{
			var eventArgs = event.getData(), controller;

			if(BX.prop.getInteger(eventArgs, "entityTypeId", 0) !== BX.CrmEntityType.enumeration.orderpayment)
			{
				return;
			}

			if(BX.prop.getString(eventArgs, "source", "") !== "PAYMENT")
			{
				return;
			}

			for(var field in eventArgs)
			{
				if(field === 'entityId' || field === 'entityTypeId')
				{
					continue;
				}

				if(eventArgs.hasOwnProperty(field))
				{
					this.getModel().setField(field, BX.prop.getString(eventArgs, field, ""));

					if(controller = this.getEditor().getControlByIdRecursive('field'))
					{
						controller.onModelChange();
					}
				}
			}

			if(!this._isCreateMode)
			{
				if(this._editor.isChanged())
				{
					this.markAsChanged();
					this.getPaymentController().onDataChanged();
				}
				else
				{
					var fields = eventArgs;
					fields['PAYMENT_ID'] = BX.prop.getString(eventArgs, "entityId", 0);
					var action = 'setPaymentPaidField';
					if (fields['IS_RETURN'])
					{
						action = 'setPaymentReturnField';
					}
					this.getPaymentController().ajax(
						action,
						{
							data: {
								FIELDS: fields
							}
						},
						{
							skipMarkAsChanged: true,
							sendUpdateEvent: true
						}
					);
				}
			}
		}
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.onPaymentVoucherInited = function(event)
	{
		if (event.getEventId() === 'CrmOrderPaymentVoucher::Initialized')
		{
			var eventArgs = event.getData();

			if(
				(BX.prop.getInteger(eventArgs, "entityTypeId", 0) !== BX.CrmEntityType.enumeration.orderpayment)
				|| !eventArgs.voucherObject
			)
			{
				return;
			}

			var voucherObject = eventArgs.voucherObject;
			voucherObject.setField('PAY_VOUCHER_NUM', this.getModel().getField('PAY_VOUCHER_NUM'));
			voucherObject.setField('PAY_VOUCHER_DATE', this.getModel().getField('PAY_VOUCHER_DATE'));
			voucherObject.setField('PAY_RETURN_NUM', this.getModel().getField('PAY_RETURN_NUM'));
			voucherObject.setField('PAY_RETURN_DATE', this.getModel().getField('PAY_RETURN_DATE'));
			voucherObject.setField('PAY_RETURN_COMMENT', this.getModel().getField('PAY_RETURN_COMMENT'));
			voucherObject.setField('PAID', this.getModel().getField('PAID'));
			voucherObject.setField('source', 'PAYMENT');

			if(this.getModel().getField('PAID') === 'N')
			{
				var isReturn = this.getModel().getField('IS_RETURN');

				if(isReturn === 'Y' || isReturn === 'P')
				{
					voucherObject.setField('IS_RETURN', isReturn);
				}
			}
		}
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorPaymentStatus.messages;
		return (m.hasOwnProperty(name)
			? m[name]
			: BX.Crm.EntityEditorPaymentStatus.superclass.getMessage.apply(this, arguments)
		);
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.onPaidButtonClick = function(e, index)
	{
		this.togglePaidButtonMenu(index);
		e.preventDefault();
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.togglePaidButtonMenu = function(index)
	{
		if(this._isPaidButtonMenuOpened[index])
		{
			this.closePaidButtonMenu(index);
		}
		else
		{
			this.openPaidButtonMenu(index);
		}
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.closePaidButtonMenu = function(index)
	{
		var menu = BX.PopupMenu.getMenuById(this._id);

		if(menu)
		{
			menu.popupWindow.close();
		}
		this._isPaidButtonMenuOpened[index] = false;
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.setValue = function(value)
	{
		if(!this._model)
		{
			return "";
		}

		return( this._model.setField( this.getName(), value ) );
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.setPaidButtonView = function(index, isPaid)
	{
		var button = BX('crm_entity_editor_is_payment_paid_button_'+index),
			buttonMain = BX('crm_entity_editor_is_payment_paid_button_main_'+index);

		if(!button || !buttonMain)
		{
			return;
		}

		if(isPaid)
		{
			BX.removeClass(button, 'ui-btn-danger-light');
			BX.addClass(button, 'ui-btn-success-light');
			buttonMain.innerHTML = this.getMessage('paymentWasPaid');
		}
		else
		{
			BX.removeClass(button, 'ui-btn-success-light');
			BX.addClass(button, 'ui-btn-danger-light');
			buttonMain.innerHTML = this.getMessage('paymentWasNotPaid');
		}
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.openPaidButtonMenu = function(index)
	{
		var _this = this, menu;

		var handler = function(e, command){
			var value = BX.prop.getString(command, "value");

			if(value === "CANCEL" || value === "RETURN")
			{
				_this.getModel().setField('PAID', 'N');

				if(value === "RETURN")
				{
					_this.getModel().setField('IS_RETURN', 'Y');

					if(!_this._isCreateMode)
					{
						BX.Crm.Page.openSlider(_this.getModel().getStringField('PAY_RETURN_URL'), { width: 500 });
					}
				}
				else
				{
					_this.setValue({
						isPaid: 'N',
						datePaid: _this.getValue().datePaid
					});
					if(!_this._isCreateMode)
					{
						BX.Crm.Page.openSlider(_this.getModel().getStringField('PAY_CANCEL_URL'), { width: 500 });
					}
				}
			}
			else if(value === "SET_PAID")
			{
				_this.setValue({
					isPaid: 'Y',
					datePaid: _this.getValue().datePaid
				});

				_this.getModel().setField('PAID', 'Y');
				_this.getModel().setField('IS_RETURN', 'N');

				if(!_this._isCreateMode)
				{
					if(_this._editor.isChanged())
					{
						_this.markAsChanged();
						_this.getPaymentController().markAsChanged();
						_this.getPaymentController().onDataChanged();
					}
					else
					{
						_this.getPaymentController().ajax(
							'setPaymentPaidField',
							{ data: { FIELDS: {
								PAID: 'Y',
								PAYMENT_ID: _this.getModel().getField('ID')
							}}},
							{
								skipMarkAsChanged: true,
								sendUpdateEvent: true
							}
						);
					}
				}
			}

			_this.closePaidButtonMenu(index);
			_this.setPaidButtonView(index, (_this.getModel().getField('PAID') === 'Y'));
		};

		var value = this.getValue();

		if(value.isPaid === 'Y')
		{
			if(this._isCreateMode)
			{
				menu =
					[
						{value: 'CANCEL', text: this.getMessage('paymentWasNotPaid'), onclick: handler}
					];
			}
			else
			{
				menu =
					[
						{value: 'CANCEL', text: this.getMessage('paymentCancel'), onclick: handler},
						{value: 'RETURN', text: this.getMessage('paymentReturn'), onclick: handler}
					];
			}
		}
		else
		{
			menu =
				[
					{value: 'SET_PAID', text: this.getMessage('paymentWasPaid'), onclick: handler}
				];
		}

		BX.PopupMenu.show(
			this._id,
			this._paidButtons[index],
			menu,
			{
				angle: false,
				events:
				{
					onPopupShow: BX.proxy( function(){ this.onMenuShow(index); }, this),
					onPopupClose: BX.delegate( this.onMenuClose, this)
				}
			}
		);
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.onMenuShow = function(index)
	{
		this._isPaidButtonMenuOpened[index] = true;
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.onMenuClose = function()
	{
		BX.PopupMenu.destroy(this._id);
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.getPaymentController = function()
	{
		if(this._paymentController === null)
		{
			for(var i = 0, length = this._editor._controllers.length; i < length; i++)
			{
				if (this._editor._controllers[i] instanceof BX.Crm.EntityEditorOrderPaymentController)
				{
					this._paymentController = this._editor._controllers[i];
					break;
				}
			}
		}

		return this._paymentController;
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.checkIfNotEmpty = function(value)
	{
		return BX.util.trim(value) !== "";
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.processModelChange = function(params)
	{
		if(BX.prop.get(params, "originator", null) === this)
		{
			return;
		}

		if(!BX.prop.getBoolean(params, "forAll", false)
			&& BX.prop.getString(params, "name", "") !== this.getName()
		)
		{
			return;
		}

		this.setPaidButtonView(0, this.getModel().getField('PAID') === 'Y');
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.doClearLayout = function(options)
	{
		if(!this._hasLayout)
		{
			return;
		}

		this._hasLayout = false;
		this._wrapper = null;
		this._input = null;
		this._view = null;
		this._isPaidButtonMenuOpened = {};
		this._paidButtons = {};
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.ensureWrapperCreated = function(params)
	{
		if(!this._wrapper)
		{
			this._wrapper = BX.create("div", { props: { className: "ui-entity-editor-content-block" } });
		}

		var classNames = BX.prop.getArray(params, "classNames", []);
		for(var i = 0, length = classNames.length;  i < length; i++)
		{
			BX.addClass(this._wrapper, classNames[i]);
		}

		return this._wrapper;
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.adjustWrapper = function()
	{
		if(!this._wrapper)
		{
			return;
		}

		if(this.isInEditMode()
			&& (this.checkModeOption(BX.UI.EntityEditorModeOptions.exclusive)
				|| this.checkModeOption(BX.UI.EntityEditorModeOptions.individual)
			)
		)
		{
			BX.addClass(this._wrapper, "crm-entity-widget-content-block-edit");
		}
		else
		{
			BX.removeClass(this._wrapper, "crm-entity-widget-content-block-edit");
		}

		if(this._layoutAttributes)
		{
			for(var key in this._layoutAttributes)
			{
				if(this._layoutAttributes.hasOwnProperty(key))
				{
					this._wrapper.setAttribute("data-" + key, this._layoutAttributes[key]);
				}
			}
			this._layoutAttributes = null;
		}
		//endregion
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.createTitleNode = function(title)
	{
		this._titleWrapper = BX.create(
			"div",
			{
				attrs: { className: "crm-entity-widget-content-block-title" }
			}
		);

		this.prepareTitleLayout(BX.type.isNotEmptyString(title) ? title : this.getTitle());
		return this._titleWrapper;
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.prepareTitleLayout = function(title)
	{
		if(!this._titleWrapper)
		{
			return;
		}

		this._titleWrapper.appendChild(
			BX.create(
				"span",
				{
					attrs: { className: "crm-entity-widget-content-block-title-text" },
					text: title
				}
			)
		);

		var marker = this.createTitleMarker();
		if(marker)
		{
			this._titleWrapper.appendChild(marker);
		}
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.createTitleMarker = function()
	{
		if(this._mode === BX.UI.EntityEditorMode.view)
		{
			return null;
		}

		if(this.isRequired())
		{
			return BX.create("span", { style: { color: "#f00" }, text: "*" });
		}
		else if(this.isRequiredConditionally())
		{
			return BX.create("span", { text: "*" });
		}
		return null;
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.initializeDragDropAbilities = function()
	{
		if(this._dragItem)
		{
			return;
		}

		this._dragItem = BX.UI.EditorDragItemController.create(
			"field_" +  this.getId(),
			{
				charge: BX.UI.EditorFieldDragItem.create(
					{
						control: this,
						contextId: this._draggableContextId
					}
				),
				node: this.createDragButton(),
				showControlInDragMode: false,
				ghostOffset: { x: 0, y: 0 }
			}
		);
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.onSetPaidFieldFailure = function(errorMessage)
	{
		this.showError(errorMessage);
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.showError =  function(error, anchor)
	{
		BX.Crm.EntityEditorPaymentStatus.superclass.showError.apply(this, arguments);

		if(this._input)
		{
			BX.addClass(this._input, "crm-entity-widget-content-error");
		}
	};

	BX.Crm.EntityEditorPaymentStatus.prototype.clearError =  function()
	{
		BX.Crm.EntityEditorPaymentStatus.superclass.clearError.apply(this);

		if(this._input)
		{
			BX.removeClass(this._input, "crm-entity-widget-content-error");
		}
	};

	BX.Crm.EntityEditorPaymentStatus.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorPaymentStatus();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorPaymentCheck === "undefined")
{
	BX.Crm.EntityEditorPaymentCheck = function()
	{
		BX.Crm.EntityEditorPaymentCheck.superclass.constructor.apply(this);
		this._loader = null;
	};
	BX.extend(BX.Crm.EntityEditorPaymentCheck, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorPaymentCheck.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorPaymentCheck.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	BX.Crm.EntityEditorPaymentCheck.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		var data = this.getValue();

		if(!BX.type.isPlainObject(data))
		{
			return;
		}

		var items = BX.prop.getArray(data, "items", []);
		this._totalCount = BX.prop.getInteger(data, "count", 0);

		this._wrapper = BX.create("div", { props: { className: "ui-entity-editor-content-block" } });

		var length = this._itemCount = items.length;
		var checkNodes = [];
		for(var i = 0; i < length; i++)
		{
			checkNodes.push(this.addCheckRow(items[i], -1));
		}

		if (checkNodes.length === 0)
		{
			var empty = BX.create(
				'div',
				{
					props: { className: "crm-entity-widget-content-nothing-selected-text" },
					text:  this.getMessage('emptyCheckList')
				}
			);
			checkNodes.push(empty);
		}

		this._wrapper.appendChild(
			BX.create(
				"div",
				{
					props: { className: "crm-entity-widget-content-block-products" },
					children: checkNodes
				}
			)
		);


		this.registerLayout(options);
		this._hasLayout = true;
	};

	BX.Crm.EntityEditorPaymentCheck.prototype.addCheckRow = function(item)
	{
		if (parseInt(item['ID']) <= 0)
		{
			return;
		}

		var rowChildren = [];

		var title = BX.create(
			"div",
			{
				props: { className: "crm-entity-widget-content-check-title" },
				children: [
					BX.create(
						"span",
						{
							attrs: { className: "crm-entity-widget-content-block-title-text" },
							text: BX.type.isNotEmptyString(item['TITLE']) ? item['TITLE'] : ""
						}
					),
					BX.create(
						"span",
						{
							attrs: { className: "crm-entity-widget-content-block-title-text" },
							text: BX.type.isNotEmptyString(item['DATE_CREATE']) ? item['DATE_CREATE'] : ""
						}
					)
				]
			}
		);

		var blockFields = [];

		// check title
		blockFields.push(
			BX.create('div', {
				props: {className: 'crm-entity-widget-content-block-inner-box-title'}, children: [

					BX.create('div', {
						props: {className: 'crm-entity-widget-content-block-inner-box-title-text'},
						html: item['TITLE']
					})
				]
			}));

		// check sum
		blockFields.push(
			BX.create('div', {
				props: {className: 'crm-entity-widget-content-block'}, children: [

					BX.create('div', {
						props: {className: 'crm-entity-widget-content-block-inner'}, children: [
							BX.create('div', {
								props: {className: 'crm-entity-widget-content-block'}, children: [
									this.createTitleNode(this.getMessage('titleFieldSum')),
									BX.create('span', {props: {className: 'crm-entity-widget-content-block-colums-right'}, html: item['SUM_WITH_CURRENCY']})
								]
							})
						]
					})
				]
			})
		);

		// date create
		blockFields.push(
			BX.create('div', {
				props: {className: 'crm-entity-widget-content-block'}, children: [

					BX.create('div', {
						props: {className: 'crm-entity-widget-content-block-inner'}, children: [
							BX.create('div', {
								props: {className: 'crm-entity-widget-content-block'}, children: [
									this.createTitleNode(this.getMessage('titleFieldDateCreate')),
									BX.create('span', {html: item['DATE_CREATE']})
								]
							})
						]
					})
				]
			})
		);

		// check type
		blockFields.push(
			BX.create('div', {
				props: {className: 'crm-entity-widget-content-block'}, children: [

					BX.create('div', {
						props: {className: 'crm-entity-widget-content-block-inner'}, children: [

							BX.create('div', {
								props: {className: 'crm-entity-widget-content-block'}, children: [
									this.createTitleNode(this.getMessage('titleFieldType')),
									BX.create('span', {html: item['CHECK_TYPE']})
								]
							})

						]
					})
				]
			})
		);

		if (BX.type.isNotEmptyString(item['CASHBOX_NAME']))
		{
			// cashbox name
			blockFields.push(
				BX.create('div', {
					props: {className: 'crm-entity-widget-content-block'}, children: [

						BX.create('div', {
							props: {className: 'crm-entity-widget-content-block-inner'}, children: [

								BX.create('div', {
									props: {className: 'crm-entity-widget-content-block'}, children: [
										this.createTitleNode(this.getMessage('titleFieldCashBoxName')),
										BX.create('span', {html: item['CASHBOX_NAME']})
									]
								})
							]
						})
					]
				})
			);
		}


		// check status name
		blockFields.push(
			BX.create('div', {
				props: {className: 'crm-entity-widget-content-block'}, children: [

					BX.create('div', {
						props: {className: 'crm-entity-widget-content-block-inner'}, children: [

							BX.create('div', {
								props: {className: 'crm-entity-widget-content-block'}, children: [
									this.createTitleNode(this.getMessage('titleFieldStatus')),
									BX.create('span', {html: item['STATUS_NAME']})
								]
							})
						]
					})
				]
			})
		);

		var content =
			BX.create('div', {props: {className: 'crm-entity-widget-content-block'}, children:[

					BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner'}, children:[

							BX.create('div', {props: {className: 'crm-entity-widget-content-block-inner-box'}, children: blockFields})
						]})
				]});

		rowChildren.push(content);

		return BX.create(
			'div',
			{
				props: { className: "crm-entity-widget-content-nothing-selected-text" },
				children:  rowChildren
			}
		);
	};

	BX.Crm.EntityEditorPaymentCheck.prototype.clearLayout = function()
	{
		if(!this._hasLayout)
		{
			return;
		}

		this._table = null;
		this._moreButton = null;
		this._moreButtonRow = null;
		this._wrapper = BX.remove(this._wrapper);
		this._hasLayout = false;
	};

	if(typeof(BX.Crm.EntityEditorPaymentCheck.messages) === "undefined")
	{
		BX.Crm.EntityEditorPaymentCheck.messages = {};
	}

	BX.Crm.EntityEditorPaymentCheck.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorPaymentCheck();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorOrderPropertyWrapper === "undefined")
{
	BX.Crm.EntityEditorOrderPropertyWrapper = function()
	{
		BX.Crm.EntityEditorOrderPropertyWrapper.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.EntityEditorOrderPropertyWrapper, BX.Crm.EntityEditorSubsection);
	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.initialize =  function(id, settings)
	{
		BX.Crm.EntityEditorOrderPropertyWrapper.superclass.initialize.call(this, id, settings);
		this.setPersonType();
		this._disabledFieldsBlock = null;
		// this._hiddenElements = [];
		this._entityType = BX.prop.getString(this._schemeElement.getData(), 'entityType');
		this._elementsData = BX.prop.getObject(this._schemeElement._settings, "sortedElements", {});

		this._elementData = {
			active: BX.prop.getArray(this._elementsData, "active", {}),
			hidden: BX.prop.getArray(this._elementsData, "hidden", {})
		};

		this._childrenScheme = {
			active: [],
			hidden: []
		};

		this._childrenScheme.active = this.setChildrenScheme(this._elementData.active);
		this._childrenScheme.hidden = this.setChildrenScheme(this._elementData.hidden);

		this.addChildren();

		BX.addCustomEvent(window, BX.Crm.EntityEvent.names.update, BX.delegate(this.onAfterSubmit, this));
		BX.addCustomEvent('SidePanel.Slider:onMessage', BX.delegate(this.onPropertySave, this));

		if (this._entityType === 'order')
		{
			BX.addCustomEvent(window, 'Crm.OrderModel.ChangePropertyScheme', BX.delegate(this.onSchemeChange, this));
		}
		else if (this._entityType === 'shipment')
		{
			BX.addCustomEvent(window, 'Crm.ShipmentModel.ChangePropertyScheme', BX.delegate(this.onSchemeChange, this));
		}
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.setPersonType = function()
	{
		this._personTypeId = BX.prop.getInteger(this._model.getData(), 'PERSON_TYPE_ID');
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.setChildrenScheme = function(elementData)
	{
		var scheme = [];
		for (var i = 0, l = elementData.length; i < l; i++)
		{
			scheme.push(BX.UI.EntitySchemeElement.create(elementData[i]));
		}
		return scheme;
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.addChildren = function()
	{
		var element = BX.UI.EntitySchemeElement.create(
			{
				'name': this._id + '_ACTIVE',
				'type': 'order_property_subsection',
				'entityType': this._entityType,
				'transferable': false,
				'editable': true,
				'isDragEnabled': BX.prop.getBoolean(this._schemeElement._settings, "isDragEnabled", false),
				'elements': [],
				'data': this._schemeElement.getData()
			}
		);

		this._activeElementsBlock = this._editor.createControl(
			element.getType(),
			element.getName(),
			{ schemeElement: element, model: this._model, parent: this, mode: this._mode }
		);

		if(this._activeElementsBlock)
		{
			this.addChild(this._activeElementsBlock, {
				enableSaving: false
			});
			// dirty clutch to prevent saving scheme every time during saving form
			if(this._editor._config)
			{
				this._editor._config._isChanged = false;
			}
		}

		element = BX.UI.EntitySchemeElement.create(
			{
				'name': this._id + '_DISABLED',
				'type': 'order_property_subsection',
				'transferable': false,
				'title': this.getMessage('disabledBlockTitle'),
				'editable': true,
				'isDragEnabled': false,
				'elements': []
			}
		);

		this._disabledFieldsBlock = this._editor.createControl(
			element.getType(),
			element.getName(),
			{ schemeElement: element, model: this._model, parent: this, mode: this._mode }
		);

		this._disabledFieldsBlock._showFieldLink = false;
		if(this._disabledFieldsBlock)
		{
			this.addChild(this._disabledFieldsBlock, {
				enableSaving: false
			});
			// dirty clutch to prevent saving scheme every time during saving form
			if(this._editor._config)
			{
				this._editor._config._isChanged = false;
			}
		}
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.layout = function()
	{
		this.setChildrenFields();
		BX.Crm.EntityEditorOrderPropertyWrapper.superclass.layout.call(this);
		var parent = this.getParent();

		if (parent instanceof BX.Crm.EntityEditorSection && BX.type.isDomNode(parent.getWrapper()))
		{
			parent.getWrapper().classList.add("crm-entity-card-widget-order-properties");
		}
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.setChildrenFields = function()
	{
		this._activeElementsBlock._fields = [];
		this._disabledFieldsBlock._fields = [];
		var elements = this._childrenScheme.active;
		for (var i=0; i < elements.length; i++)
		{
			var parent = null;
			var element = elements[i];
			if (this._personTypeId === element.getDataIntegerParam("personTypeId"))
			{
				parent = this._activeElementsBlock;
				element._isContextMenuEnabled = true;
			}
			else
			{
				var currentValue = BX.prop.getString(this._model.getData(), element.getName());
				if (!BX.type.isNotEmptyString(currentValue))
				{
					continue;
				}
				element._isContextMenuEnabled = false;
				parent = this._disabledFieldsBlock;
			}

			var field = this._editor.createControl(
				element.getType(),
				element.getName(),
				{ schemeElement: element, model: this._model, parent: parent, mode: this._mode }
			);

			if (this._mode !== BX.UI.EntityEditorMode.edit || this._personTypeId !== element.getDataIntegerParam("personTypeId"))
			{
				field.setDragObjectType(BX.UI.EditorDragObjectType.intermediate);
			}
			parent.addChild(field, {
				enableSaving: false
			});
			// dirty clutch to prevent saving scheme every time during saving form
			if(this._editor._config)
			{
				this._editor._config._isChanged = false;
			}
		}
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.initializeFromModel = function()
	{
		this._fields = [];
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.createButtonPanel = function()
	{
		this._buttonPanelWrapper = BX.create("div", {
			props: { className: "ui-entity-editor-content-block" }
		});

		if (this.isCreationEnabled())
		{
			if (this.getEditorUrlTemplate())
			{
				this._createChildButton = BX.create("span",
					{
						props: {className: "crm-entity-widget-content-block-edit-action-btn"},
						text: this.getMessage("createField"),
						events: {click: BX.delegate(this.onCreateUserFieldBtnClick, this)}
					}
				);
				this._buttonPanelWrapper.appendChild(this._createChildButton);
			}

			if (this.getManagerUrlTemplate())
			{
				this._insertChildButton = BX.create("span",
					{
						props: { className: "crm-entity-widget-content-block-edit-action-btn" },
						text: this.getMessage("insertField"),
						events: { click: BX.delegate(this.onInsertPropertyBtnClick, this) }
					}
				);
				this._buttonPanelWrapper.appendChild(this._insertChildButton);
			}
		}

		this._addChildButton = BX.create("span",
			{
				props: { className: "crm-entity-widget-content-block-edit-action-btn" },
				text: this.getMessage("selectField"),
				events: { click: BX.delegate(this.onAddChildBtnClick, this) }
			}
		);
		this.toggleChildButton();
		this._buttonPanelWrapper.appendChild(this._addChildButton);
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.isCreationEnabled = function()
	{
		return true;
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.toggleChildButton = function()
	{
		if (!BX.type.isDomNode(this._addChildButton))
		{
			return;
		}

		var showAddChildButton = false;
		for (var i=0; i < this._childrenScheme.hidden.length;i++)
		{
			if (this._personTypeId === this._childrenScheme.hidden[i].getDataIntegerParam("personTypeId"))
			{
				showAddChildButton = true;
				break;
			}
		}
		if(showAddChildButton)
		{
			this._addChildButton.style.display = "inline-block";
		}
		else
		{
			this._addChildButton.style.display = "none";
		}
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.onSchemeChange = function(data)
	{
		this._elementData = {
			active: BX.prop.getArray(data, "ACTIVE", {}),
			hidden: BX.prop.getArray(data, "HIDDEN", {})
		};
		var personType = BX.prop.getNumber(data, "PERSON_TYPE_ID", 0);
		if (personType > 0)
		{
			this._personTypeId = personType;
			if (this._activeElementsBlock)
			{
				this._activeElementsBlock._personTypeId = personType;
			}
		}
		this._childrenScheme.active = this.setChildrenScheme(this._elementData.active);
		this._childrenScheme.hidden = this.setChildrenScheme(this._elementData.hidden);
		this.toggleChildButton();
		this.refreshLayout();
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.onPropertySave = function(event)
	{
		if (event.getEventId() === 'OrderPropertyEdit::onSave')
		{
			var eventData = event.getData();

			var propertyId = BX.prop.getNumber(eventData.property, 'ID');
			if (!BX.type.isNotEmptyObject(eventData.property) || !propertyId)
			{
				return;
			}
			var property = eventData.property;

			var itemId = 'PROPERTY_' + propertyId;

			for(var i = 0; i < this._editor._activeControls.length; i++)
			{
				if(this._editor._activeControls[i].getId() === itemId)
				{
					this._editor._activeControls.splice(i, 1);
				}
			}

			this.addActiveField(itemId, property);
		}
		else if (event.getEventId() === 'OrderForm::onSave')
		{
			this.getOrderController().ajax(
				'getPropertiesScheme',
				{
					data: {ORDER_ID: this._editor.getEntityId()},
					onsuccess: BX.delegate(this.refreshChildren, this)
				}
			);
		}
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.addActiveField = function(itemId, data)
	{
		var field = this._activeElementsBlock.getChildById(itemId);
		if (field)
		{
			field._schemeElement._isRequired = (data.REQUIRED === 'Y');

			if (data.TYPE === 'STRING')
			{
				field._schemeElement._data.lineCount = data.MULTILINE === 'Y' ? data.ROWS : '1';
			}

			field._schemeElement.setTitle(BX.util.htmlspecialchars(data.NAME));
			if (
				field instanceof BX.UI.EntityEditorList
				||field instanceof BX.UI.EntityEditorMultiList
			)
			{
				if (!BX.type.isArray(data.VARIANTS))
				{
					property.VARIANTS = [];
				}
				var items = [];
				for (var i=0; i<data.VARIANTS.length; i++)
				{
					var value = data.VARIANTS[i].VALUE;
					if (!BX.type.isNotEmptyString(value))
					{
						continue;
					}

					var name = BX.type.isNotEmptyString(data.VARIANTS[i].NAME) ? BX.util.htmlspecialchars(data.VARIANTS[i].NAME) : value;
					items.push({
						'VALUE' : value,
						'NAME' : name
					});
				}
				field._items = items;
			}

			this.refreshLayout();
		}
		else
		{
			this.getOrderController().ajax(
				'preparePropertyScheme',
				{
					data: {PROPERTY: data},
					onsuccess: BX.delegate(this.createProperty, this)
				}
			);
		}
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.getOrderController = function()
	{
		for(var i = 0, length = this._editor._controllers.length; i < length; i++)
		{
			if (this._editor._controllers[i] instanceof BX.Crm.EntityEditorOrderController)
			{
				return this._editor._controllers[i];
			}
		}

		return null;
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.createProperty = function(data)
	{
		this.getOrderController().unlockSending();
		this.getOrderController().hideLoader();
		var info = BX.prop.getObject(data, "FIELD", null);
		if(!info)
		{
			return;
		}
		var element = BX.UI.EntitySchemeElement.create(info);
		this._model.setField(
			element.getName(),
			""
		);
		this._childrenScheme.active.push(element);
		var field = this._editor.createControl(
			element.getType(),
			element.getName(),
			{ schemeElement: element, model: this._model, parent: this, mode: this._mode }
		);
		var showAlways = this._editor.getOption("show_always", "Y") === "Y";
		if(showAlways !== field.checkOptionFlag(BX.UI.EntityEditorControlOptions.showAlways))
		{
			field.toggleOptionFlag(BX.UI.EntityEditorControlOptions.showAlways);
		}

		if (field instanceof BX.UI.EntityEditorCustom)
		{
			this.getOrderController().onDataChanged();
		}
		else
		{
			this._activeElementsBlock.addChild(field);
		}
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.refreshChildren = function(data)
	{
		this.getOrderController().unlockSending();
		this.getOrderController().hideLoader();

		if (
			parseInt(data.ORDER_ID) !== parseInt(this._editor.getEntityId())
			|| !BX.type.isNotEmptyObject(data.PROPERTIES)
		)
		{
			return;
		}

		if (BX.type.isArray(data.PROPERTIES.ACTIVE))
		{
			this._childrenScheme.active = this.setChildrenScheme(BX.prop.getArray(data.PROPERTIES, "ACTIVE", []));
		}

		if (BX.type.isArray(data.PROPERTIES.HIDDEN))
		{
			this._childrenScheme.hidden = this.setChildrenScheme(BX.prop.getArray(data.PROPERTIES, "HIDDEN", []));
		}

		this.refreshLayout();
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.removeChild = function(child)
	{
		this._childrenScheme.hidden.push(child.getSchemeElement());
		var id = child.getId();
		for (var i=0; i < this._childrenScheme.active.length; i++)
		{
			if (id === this._childrenScheme.active[i].getName())
			{
				this._childrenScheme.active.splice(i, 1);
				break;
			}
		}
		this.toggleChildButton();
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.openAddChildMenu = function()
	{
		var schemeElements = [];
		for (var i=0; i < this._childrenScheme.hidden.length;i++)
		{
			if (this._personTypeId === this._childrenScheme.hidden[i].getDataIntegerParam("personTypeId"))
			{
				schemeElements.push(this._childrenScheme.hidden[i]);
			}
		}

		var length = schemeElements.length;
		if(length === 0)
		{
			return;
		}

		var menuItems = [];
		for(i=0; i < length; i++)
		{
			var schemeElement = schemeElements[i];
			menuItems.push({ text: schemeElement.getTitle(), value: schemeElement.getName() });
		}

		if(this._childSelectMenu)
		{
			this._childSelectMenu.setupItems(menuItems);
		}
		else
		{
			this._childSelectMenu = BX.CmrSelectorMenu.create(this._id, { items: menuItems });
			this._childSelectMenu.addOnSelectListener(BX.delegate(this.onChildSelect, this));
		}
		this._childSelectMenu.open(this._addChildButton);
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.onChildSelect = function(sender, item)
	{
		var v = item.getValue();
		if(v === "ACTION.TRANSFER")
		{
			this.openTransferDialog();
			return;
		}

		for (var i=0; i < this._childrenScheme.hidden.length;i++)
		{
			if (this._childrenScheme.hidden[i].getName() === v)
			{
				element = this._childrenScheme.hidden[i];
				this._childrenScheme.hidden = BX.util.deleteFromArray(this._childrenScheme.hidden, i);
				this.toggleChildButton();
				break;
			}
		}
		if(!element)
		{
			return;
		}

		var propertyId = element.getDataStringParam("propertyId");
		var config = {
			SETTINGS : {
				IS_HIDDEN : 'N'
			}
		};
		this._activeElementsBlock.savePropertyConfig(propertyId, config);

		var field = this._editor.createControl(
			element.getType(),
			element.getName(),
			{ schemeElement: element, model: this._model, parent: this, mode: this._mode }
		);

		if(field)
		{
			this._activeElementsBlock.addChild(field);
			this._childrenScheme.active.push(element);
		}
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.onAfterSubmit = function(data)
	{
		var entityData = BX.prop.getObject(data, "entityData", null);
		if (entityData)
		{
			var profiles = BX.prop.getArray(entityData, "USER_PROFILE_LIST");
			this.refreshProfilesList(profiles);
			var personType = BX.prop.getNumber(entityData, "PERSON_TYPE_ID");
			if (this._personTypeId !== personType)
			{
				this.refreshLayout();
			}
		}
		if (this._activeElementsBlock.isSchemeChanged())
		{
			var properties = [];
			var sortedFieldSchemes = [];
			var fields = this._activeElementsBlock._fields;
			for (var i=0; i < fields.length;i++)
			{
				var field = fields[i];
				var propertyId = field._schemeElement.getDataStringParam("propertyId");
				if (BX.type.isNotEmptyString(propertyId))
				{
					properties.push(field._schemeElement.getDataStringParam("propertyId"));
					sortedFieldSchemes.push(field._schemeElement);
				}
			}
			if (properties.length > 0)
			{
				BX.ajax.post(
					BX.prop.getString(this._settings, "serviceUrl", ""),
					{
						PROPERTIES: properties,
						ACTION: "sortProperties"
					}
				);
			}

			for (i=0; i < this._childrenScheme.active.length; i++)
			{
				var elementPropertyId = this._childrenScheme.active[i].getDataStringParam("propertyId");
				if (!BX.util.in_array(elementPropertyId, properties))
				{
					sortedFieldSchemes.push(this._childrenScheme.active[i]);
				}
			}

			this._childrenScheme.active = sortedFieldSchemes;
		}
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.refreshProfilesList = function(profiles)
	{
		if (BX.type.isArray(profiles))
		{
			var controls = this._editor.getControls();
			for (var i=0; i<controls.length; i++)
			{
				var field = controls[i].getChildById('USER_PROFILE');
				if (field)
				{
					field._items = null;
					field._schemeElement.setData({items: profiles});
					field.refreshLayout();
					return;
				}
			}
		}
	};
	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.setDraggableContextId = function(contextId)
	{
	};
	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.showPropertyEditor = function(propertyId)
	{
		propertyId = parseInt(propertyId);

		var urlTemplate = this.getEditorUrlTemplate();
		if (urlTemplate)
		{
			var url = urlTemplate.replace('#property_id#', propertyId).replace('#person_type_id#', this._personTypeId);
			BX.SidePanel.Instance.open(
				url,
				{
					loader: "crm-webform-view-loader",
					cacheable: false
				}
			);
		}
	};
	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.showManagerSlider = function(e)
	{
		var urlTemplate = this.getManagerUrlTemplate();
		if (urlTemplate)
		{
			var url = urlTemplate.replace('#person_type_id#', this._personTypeId);
			BX.SidePanel.Instance.open(url, {loader: "crm-webform-view-loader"});
		}
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.getManagerUrlTemplate = function(e)
	{
		var data = this._schemeElement.getData();
		if (!BX.type.isNotEmptyString(data.managerUrl))
		{
			return null;
		}

		return data.managerUrl;
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.getEditorUrlTemplate = function(e)
	{
		var data = this._schemeElement.getData();
		if (!BX.type.isNotEmptyString(data.editorUrl))
		{
			return null;
		}

		return data.editorUrl;
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.onCreateUserFieldBtnClick = function(e)
	{
		this.showPropertyEditor(0);
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.onInsertPropertyBtnClick = function(child)
	{
		this.showManagerSlider()
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorOrderPropertyWrapper.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};

	BX.Crm.EntityEditorOrderPropertyWrapper.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorOrderPropertyWrapper();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorOrderPropertySubsection === "undefined")
{
	BX.Crm.EntityEditorOrderPropertySubsection = function()
	{
		BX.Crm.EntityEditorOrderPropertySubsection.superclass.constructor.apply(this);
	};

	BX.extend(BX.Crm.EntityEditorOrderPropertySubsection, BX.Crm.EntityEditorSubsection);

	BX.Crm.EntityEditorOrderPropertySubsection.prototype.initialize = function(id, settings)
	{
		BX.Crm.EntityEditorOrderPropertySubsection.superclass.initialize.call(this, id, settings);
		this._personTypeId = BX.prop.getInteger(this._model.getData(), 'PERSON_TYPE_ID');
		this._showFieldLink = true;
		if(this.getChildDragScope() === BX.UI.EditorDragScope.parent)
		{
			this._draggableContextId += "_" + this.getId();
		}
		BX.addCustomEvent(window, 'CrmOrderPropertySetCustom', BX.delegate(this.onChangeCustomProperty, this));
	};

	BX.Crm.EntityEditorOrderPropertySubsection.prototype.getEditPriority = function()
	{
		return BX.UI.EntityEditorPriority.high;
	};

	BX.Crm.EntityEditorOrderPropertySubsection.prototype.layoutChild = function(field)
	{
		BX.Crm.EntityEditorOrderPropertySubsection.superclass.layoutChild.call(this, field);
		var linked = BX.prop.getString(field._schemeElement._settings, "linked");
		if (this._mode !== BX.UI.EntityEditorMode.view
			&& field.isContextMenuEnabled()
			&& BX.type.isNotEmptyString(linked)
			&& this._showFieldLink
		)
		{
			BX.addClass(field._wrapper, 'crm-entity-widget-content-block-linked');
			if (BX.type.isDomNode(field._titleWrapper))
			{
				field._titleWrapper.setAttribute('title', linked);
			}
		}
	};

	BX.Crm.EntityEditorOrderPropertySubsection.prototype.layoutTitle = function()
	{
		if (BX.type.isNotEmptyString(this._schemeElement.getTitle()) && this.getChildCount() > 0)
		{
			var titleWrapper = 	BX.create(
				"div",
				{
					attrs: { className: "crm-entity-card-widget-title" },
					text: this._schemeElement.getTitle()
				}
			);

			if (BX.type.isDomNode(this._contentContainer))
			{
				BX.addClass(this._contentContainer, "crm-entity-widget-content");
			}

			this._wrapper.appendChild(titleWrapper);
		}
	};

	BX.Crm.EntityEditorOrderPropertySubsection.prototype.onFieldConfigurationSave = function(sender, params)
	{
		if(sender !== this._fieldConfigurator)
		{
			return;
		}

		var field = BX.prop.get(params, "field", null);
		if(!field)
		{
			throw "EntityEditorSection. Could not find target field.";
		}

		var label = BX.prop.getString(params, "label", "");
		var showAlways = BX.prop.getBoolean(params, "showAlways", null);
		if(label === "" && showAlways === null)
		{
			this.removeFieldConfigurator();
			return;
		}

		this._fieldConfigurator.setLocked(true);
		field.setTitle(label);
		if(showAlways !== null && showAlways !== field.checkOptionFlag(BX.UI.EntityEditorControlOptions.showAlways))
		{
			field.toggleOptionFlag(BX.UI.EntityEditorControlOptions.showAlways);
		}
		var config = {
			NAME : label,
			SETTINGS : {
				SHOW_ALWAYS : showAlways ? 'Y' : 'N'
			}
		};

		var propertyId = field._schemeElement.getDataStringParam("propertyId");
		this.savePropertyConfig(propertyId, config).then(
			BX.delegate(
				function() { this.removeFieldConfigurator(); },
				this
			)
		)
	};

	BX.Crm.EntityEditorOrderPropertySubsection.prototype.getOrderController = function()
	{
		for(var i = 0, length = this._editor._controllers.length; i < length; i++)
		{
			if (this._editor._controllers[i] instanceof BX.Crm.EntityEditorOrderController)
			{
				return this._editor._controllers[i];
			}
		}

		return null;
	};

	BX.Crm.EntityEditorOrderPropertySubsection.prototype.processChildControlSchemeChange = function(child)
	{
		var propertyId = child._schemeElement.getDataStringParam("propertyId");
		var config = {
			SETTINGS : {
				SHOW_ALWAYS : child.checkOptionFlag(BX.UI.EntityEditorControlOptions.showAlways) ? 'Y' : 'N'
			}
		};
		this.savePropertyConfig(propertyId, config);
	};

	BX.Crm.EntityEditorOrderPropertySubsection.prototype.processDraggedItemDrop = function(dragContainer, draggedItem)
	{
		var containerCharge = dragContainer.getCharge();
		if(!((containerCharge instanceof BX.UI.EditorFieldDragContainer) && containerCharge.getSection() === this))
		{
			return;
		}

		var context = draggedItem.getContextData();
		var contextId = BX.type.isNotEmptyString(context["contextId"]) ? context["contextId"] : "";

		if(contextId !== this.getDraggableContextId())
		{
			return;
		}

		var itemCharge = typeof(context["charge"]) !== "undefined" ?  context["charge"] : null;
		if(!(itemCharge instanceof BX.UI.EditorFieldDragItem))
		{
			return;
		}

		var control = itemCharge.getControl();
		if(!control)
		{
			return;
		}

		var currentIndex = this.getChildIndex(control);
		if(currentIndex < 0)
		{
			return;
		}

		var placeholder = this.getPlaceHolder();
		var placeholderIndex = placeholder ? placeholder.getIndex() : -1;
		if(placeholderIndex < 0)
		{
			return;
		}

		var index = placeholderIndex <= currentIndex ? placeholderIndex : (placeholderIndex - 1);
		if(index !== currentIndex)
		{
			this.moveChild(control, index);
		}
	};

	BX.Crm.EntityEditorOrderPropertySubsection.prototype.onChangeCustomProperty = function(fieldName)
	{
		if (BX.type.isNotEmptyString(fieldName))
		{
			var field = this.getChildById(fieldName);
			if (field)
			{
				var fieldNode = field.getWrapper();

				if (!BX.type.isDomNode(fieldNode))
					return;

				var type = field._schemeElement.getDataStringParam("type");
				if (type === 'LOCATION' || type === 'ADDRESS')
				{
					var input = fieldNode.querySelector('input[name='+fieldName+']');
					if (BX.type.isDomNode(input))
					{
						var current = BX.prop.getString(this._model.getData(), fieldName);
						if (input.value !== current)
						{
							field.setRuntimeValue(input.value);
							field.markAsChanged();
						}
					}
				}
				else if (type === 'FILE')
				{
					var value = BX.prop.getString(this._model.getData(), fieldName);
					field.setRuntimeValue(value);
					field.markAsChanged();
				}
				else
				{
					var value = BX.prop.getString(this._model.getData(), fieldName);
					field.setRuntimeValue(value);
					field.markAsChanged();
				}
			}
		}
	};

	BX.Crm.EntityEditorOrderPropertySubsection.prototype.editChildConfiguration = function(child)
	{
		if (BX.prop.getString(this._schemeElement.getData(), 'entityType') === 'order')
		{
			this.showPropertyEditor(child._schemeElement.getDataStringParam("propertyId"));
		}
		else
		{
			BX.Crm.EntityEditorOrderPropertySubsection.superclass.editChildConfiguration.apply(this, [child]);
		}
	};

	BX.Crm.EntityEditorOrderPropertySubsection.prototype.removeChild = function(child, options)
	{
		if(!BX.type.isPlainObject(options))
		{
			options = {};
		}

		var index = this.getChildIndex(child);
		if(index < 0)
		{
			return;
		}

		if(child.isActive())
		{
			child.setActive(false);
		}

		this._fields.splice(index, 1);

		var processScheme = child.hasScheme();

		if(processScheme)
		{
			child.getSchemeElement().setParent(null);
			if (this._parent)
			{
				this._parent.removeChild(child);
			}
		}

		if(this._hasLayout)
		{
			child.clearLayout();
			child.setContainer(null);
			child.setDraggableContextId("");
		}

		if(processScheme)
		{
			var propertyId = child._schemeElement.getDataStringParam("propertyId");
			var config = {
				SETTINGS : {
					IS_HIDDEN : 'Y'
				}
			};
			this.savePropertyConfig(propertyId, config);
		}
	};

	BX.Crm.EntityEditorOrderPropertySubsection.prototype.savePropertyConfig = function(id, config)
	{
		var promise = new BX.Promise();
		var data =
			{
				PROPERTY_ID: id,
				ACTION: "savePropertyConfig",
				CONFIG: config
			};

		BX.ajax.post(
			BX.prop.getString(this._settings, "serviceUrl", ""),
			data,
			function(){ promise.fulfill(); }
		);
		this._isChanged = false;
		return promise;
	};

	BX.Crm.EntityEditorOrderPropertySubsection.prototype.isDragEnabled = function()
	{
		return (this._mode === BX.UI.EntityEditorMode.edit) && BX.prop.getBoolean(this._schemeElement._settings, "isDragEnabled", false);
	};

	BX.Crm.EntityEditorOrderPropertySubsection.prototype.refreshProfilesList = function(profiles)
	{
		if (BX.type.isArray(profiles))
		{
			var controls = this._editor.getControls();
			for (var i=0; i<controls.length; i++)
			{
				var field = controls[i].getChildById('USER_PROFILE');
				if (field)
				{
					field._items = null;
					field._schemeElement.setData({items: profiles});
					field.refreshLayout();
					return;
				}
			}
		}
	};

	BX.Crm.EntityEditorOrderPropertySubsection.prototype.moveChild = function(child, index)
	{
		var qty = this.getChildCount();
		var lastIndex = qty - 1;
		if(index < 0  || index > qty)
		{
			index = lastIndex;
		}

		var currentIndex = this.getChildIndex(child);
		if(currentIndex < 0 || currentIndex === index)
		{
			return false;
		}

		if(this._hasLayout)
		{
			child.clearLayout();
		}
		this._fields.splice(currentIndex, 1);

		qty--;

		var anchor = null;
		if(this._hasLayout)
		{
			anchor = index < qty
				? this._fields[index].getWrapper()
				: this._buttonPanelWrapper;
		}

		if(index < qty)
		{
			this._fields.splice(index, 0, child);
		}
		else
		{
			this._fields.push(child);
		}

		if(this._hasLayout)
		{
			if(anchor)
			{
				child.layout({ anchor: anchor });
			}
			else
			{
				child.layout();
			}
		}

		this.markSchemeAsChanged();
		this.markAsChanged();
		return true;
	};

	BX.Crm.EntityEditorOrderPropertySubsection.prototype.hasAdditionalMenu = function(e)
	{
		return true;
	};
	BX.Crm.EntityEditorOrderPropertySubsection.prototype.getAdditionalMenu = function(e)
	{
		return !!this.getManagerUrl() ? [{ value: "linkToSettings", text: this.getMessage("linkToSettings") }] : [];
	};
	BX.Crm.EntityEditorOrderPropertySubsection.prototype.showPropertyEditor = function(propertyId)
	{
		propertyId = parseInt(propertyId);
		var data = this._schemeElement.getData();
		if (BX.type.isNotEmptyString(data.editorUrl))
		{
			var url = data.editorUrl.replace('#property_id#', propertyId).replace('#person_type_id#', this._personTypeId);
			BX.SidePanel.Instance.open(url, {loader: "crm-webform-view-loader"});
		}
	};
	BX.Crm.EntityEditorOrderPropertySubsection.prototype.showManagerSlider = function(e)
	{
		var url = this.getManagerUrl();
		if (url)
		{
			BX.SidePanel.Instance.open(url, {loader: "crm-webform-view-loader"});
		}
	};
	BX.Crm.EntityEditorOrderPropertySubsection.prototype.getManagerUrl = function(e)
	{
		var data = this._schemeElement.getData();
		if (!BX.type.isNotEmptyString(data.managerUrl))
		{
			return null;
		}

		return data.managerUrl.replace('#person_type_id#', this._personTypeId);
	};

	BX.Crm.EntityEditorOrderPropertySubsection.prototype.processChildAdditionalMenuCommand = function(child, command)
	{
		if (command === "linkToSettings")
		{
			this.showManagerSlider();
		}
	};
	BX.Crm.EntityEditorOrderPropertySubsection.prototype.onCreateUserFieldBtnClick = function(e)
	{
		this.showPropertyEditor(0);
	};

	BX.Crm.EntityEditorOrderPropertySubsection.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorOrderPropertySubsection.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};

	BX.Crm.EntityEditorOrderPropertySubsection.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorOrderPropertySubsection();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorOrderProductProperty === "undefined")
{
	BX.Crm.EntityEditorOrderProductProperty = function()
	{
		BX.Crm.EntityEditorOrderProductProperty.superclass.constructor.apply(this);
	};

	BX.extend(BX.Crm.EntityEditorOrderProductProperty, BX.Crm.EntityEditorField);

	BX.Crm.EntityEditorOrderProductProperty.prototype.initialize =  function(id, settings)
	{
		BX.Crm.EntityEditorOrderProductProperty.superclass.initialize.call(this, id, settings);
		this._addPropertyLink = null;
		this._innerWrapper = null;
		this._index = 0;
	};

	BX.Crm.EntityEditorOrderProductProperty.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated({ classNames: [ "crm-entity-widget-content-block-field-text" ] });
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		this._innerWrapper = BX.create('div');
		this._wrapper.appendChild(this._innerWrapper);
		var values = BX.prop.get(this._model.getData(), this.getName(), []);
		for (var i in values)
		{
			if(values.hasOwnProperty(i))
			{
				this.layoutProperty(values[i]);
			}
		}

		this._addPropertyLink = BX.create('span',
			{
				attrs: { className: "crm-entity-widget-content-block-edit-action-btn" },
				text: this.getMessage("addProductProperty"),
				events: {
					click: BX.delegate( this.layoutProperty, this)
				}
			}
		);
		this._wrapper.appendChild(this._addPropertyLink);
		this.registerLayout(options);
		this._hasLayout = true;
	};

	BX.Crm.EntityEditorOrderProductProperty.prototype.layoutProperty = function(data)
	{
		var data = data || {};
		if (!BX.type.isDomNode(this._innerWrapper))
			return;
		this._index++;
		var title = this.getMessage("fieldBlockTitle");
		title = title.replace('#INDEX#', this._index);
		var titleWrapper = BX.create(
			"div",
			{
				attrs: { className: "crm-entity-card-widget" },
				children: [
					BX.create(
						"div",
						{
							attrs: { className: "crm-entity-card-widget-title" },
							text: title
						}
					)
				]
			}
		);
		this._innerWrapper.appendChild(titleWrapper);
		var names = ['Name', 'Value', 'Code', 'Sort'];
		for (var i=0; i<names.length; i++)
		{
			var attrs = {
				name: 'PROPERTY[' + this._index + ']['+ names[i].toUpperCase() +']',
				className: "crm-entity-widget-content-input",
				type: "text"
			};

			var value = BX.prop.getString(data, names[i].toUpperCase());
			if (value !== undefined)
			{
				attrs.value = value;
			}
			else if (names[i] === 'Sort')
			{
				attrs.value = 100;
			}

			var _inner = BX.create("div",
				{
					attrs: {className: "crm-entity-widget-content-block-inner"},
					children: [
						this.createTitleNode(this.getMessage("fieldTitle" + names[i])),
						BX.create("input",{	attrs: attrs })
					]
				}
			);
			this._innerWrapper.appendChild(_inner);
		}

		if (BX.type.isDomNode(this._addPropertyLink) && !BX.hasClass(this._addPropertyLink, "crm-entity-widget-content-block-edit-action-btn-added"))
		{
			BX.addClass(this._addPropertyLink, "crm-entity-widget-content-block-edit-action-btn-added")
		}
		this.markAsChanged();
	};
	BX.Crm.EntityEditorOrderProductProperty.prototype.save = function(name)
	{
		if(BX.type.isDomNode(this._wrapper))
		{
			var value = {};
			var inputs = this._wrapper.querySelectorAll('input');
			for (var i=0; i < inputs.length; i++)
			{
				value[inputs[i].name] = inputs[i].value;
			}
			this._model.setField(this.getName(), value, { originator: this });
		}
	};
	BX.Crm.EntityEditorOrderProductProperty.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorOrderProductProperty.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};

	BX.Crm.EntityEditorOrderProductProperty.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorOrderProductProperty();
		self.initialize(id, settings);
		return self;
	};
}

// It is similar to EntityEditorOrderPersonType. Good reason for the unification.
if(typeof BX.Crm.EntityEditorOrderTradingPlatform === "undefined")
{
	BX.Crm.EntityEditorOrderTradingPlatform = function()
	{
		BX.Crm.EntityEditorOrderTradingPlatform.superclass.constructor.apply(this);
	};

	BX.extend(BX.Crm.EntityEditorOrderTradingPlatform, BX.UI.EntityEditorList);

	BX.Crm.EntityEditorOrderTradingPlatform.prototype.initialize = function(id, settings)
	{
		BX.Crm.EntityEditorOrderTradingPlatform.superclass.initialize.call(this, id, settings);
		BX.addCustomEvent(window, BX.Crm.EntityEvent.names.update, BX.delegate(this.onAfterUpdate, this));
		this._baseValue = this.getValue();
	};

	BX.Crm.EntityEditorOrderTradingPlatform.prototype.onItemSelect = function(e, item)
	{
		if (this._selectedValue !== item.value)
		{
			BX.Crm.EntityEditorOrderTradingPlatform.superclass.onItemSelect.apply(this, [e, item]);
			for(var i = 0, length = this._editor._controllers.length; i < length; i++)
			{
				if (this._editor._controllers[i] instanceof BX.Crm.EntityEditorOrderController)
				{
					this._editor._controllers[i].onDataChanged();
				}
			}
		}
		else
		{
			this.closeMenu();
			BX.PopupMenu.destroy(this._id);
		}
	};

	BX.Crm.EntityEditorOrderTradingPlatform.prototype.getDragObjectType = function()
	{
		return BX.UI.EditorDragObjectType.intermediate;
	};

	BX.Crm.EntityEditorOrderTradingPlatform.prototype.onAfterUpdate = function(e, item)
	{
		if (this.isChanged())
		{
			this._baseValue = this.getValue();
		}
	};

	BX.Crm.EntityEditorOrderTradingPlatform.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorOrderTradingPlatform();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorOrderPersonType === "undefined")
{
	BX.Crm.EntityEditorOrderPersonType = function()
	{
		BX.Crm.EntityEditorOrderPersonType.superclass.constructor.apply(this);
	};

	BX.extend(BX.Crm.EntityEditorOrderPersonType, BX.UI.EntityEditorList);

	BX.Crm.EntityEditorOrderPersonType.prototype.initialize = function(id, settings)
	{
		BX.Crm.EntityEditorOrderPersonType.superclass.initialize.call(this, id, settings);
		BX.addCustomEvent(window, BX.Crm.EntityEvent.names.update, BX.delegate(this.onAfterUpdate, this));
		this._baseValue = this.getValue();
	};

	BX.Crm.EntityEditorOrderPersonType.prototype.onItemSelect = function(e, item)
	{
		if (this._selectedValue !== item.value)
		{
			BX.Crm.EntityEditorOrderPersonType.superclass.onItemSelect.apply(this, [e, item]);
			for(var i = 0, length = this._editor._controllers.length; i < length; i++)
			{
				if (
					this._editor._controllers[i] instanceof BX.Crm.EntityEditorOrderController
					&& this._selectedValue !== 'NEW'
				)
				{
					this._editor._controllers[i].onDataChanged();
				}
			}
		}
		else
		{
			this.closeMenu();
			BX.PopupMenu.destroy(this._id);
		}
	};

	BX.Crm.EntityEditorOrderPersonType.prototype.getDragObjectType = function()
	{
		return BX.UI.EditorDragObjectType.intermediate;
	};

	BX.Crm.EntityEditorOrderPersonType.prototype.onAfterUpdate = function(e, item)
	{
		if (this.isChanged())
		{
			this._baseValue = this.getValue();
		}
	};

	BX.Crm.EntityEditorOrderPersonType.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorOrderPersonType();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorOrderUser === "undefined")
{
	BX.Crm.EntityEditorOrderUser = function()
	{
		BX.Crm.EntityEditorOrderUser.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.EntityEditorOrderUser, BX.Crm.EntityEditorClientLight);

	BX.Crm.EntityEditorOrderUser.prototype.doInitialize = function()
	{
		BX.Crm.EntityEditorOrderUser.superclass.doInitialize.apply(this);
		this._selectedValue = null;
		this._searchBox = null;
	};
	BX.Crm.EntityEditorOrderUser.prototype.addContact = function(entityInfo)
	{
		if(entityInfo instanceof BX.CrmEntityInfo)
		{
			this._contactInfos.add(entityInfo);
		}
	};
	BX.Crm.EntityEditorOrderUser.prototype.removeContact = function(entityInfo)
	{
		if(entityInfo instanceof BX.CrmEntityInfo)
		{
			this._contactInfos.remove(entityInfo);
		}
	};
	BX.Crm.EntityEditorOrderUser.prototype.setContacts = function(entityInfos)
	{
		this._contactInfos.removeAll();
		for(var i = 0, length = entityInfos.length; i < length; i++)
		{
			var entityInfo = entityInfos[i];
			if(entityInfo instanceof BX.CrmEntityInfo)
			{
				this._contactInfos.add(entityInfo);
			}
		}
	};
	BX.Crm.EntityEditorOrderUser.prototype.hasContentToDisplay = function()
	{
		return(this._companyInfo !== null
			|| (this._contactInfos !== null && this._contactInfos.length() > 0)
		);
	};
	BX.Crm.EntityEditorOrderUser.prototype.getContentWrapper = function()
	{
		return this._innerWrapper;
	};
	BX.Crm.EntityEditorOrderUser.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.UI.EntityEditorModeSwitchType.common;
		if(mode === BX.UI.EntityEditorMode.edit)
		{
			result |= BX.UI.EntityEditorModeSwitchType.button|BX.UI.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorOrderUser.prototype.rollback = function()
	{
	};
	BX.Crm.EntityEditorOrderUser.prototype.doPrepareContextMenuItems = function(menuItems)
	{
	};
	BX.Crm.EntityEditorOrderUser.prototype.layout = function(options)
	{
		if (this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated();
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		this._wrapper.appendChild(this.createTitleNode(this.getTitle()));

		if(this.isInViewMode())
		{
			var formattedName = this._model.getSchemeField(this._schemeElement, "formated", "");
			var position = this._model.getSchemeField(this._schemeElement, "position", "");
			var showUrl = this._model.getSchemeField(this._schemeElement, "showUrl", "", "");
			var photoUrl = this._model.getSchemeField(this._schemeElement, "photoUrl", "");

			this._photoElement = BX.create("a",
				{
					props: { className: "crm-widget-employee-avatar-container", target: "_blank" },
					style:
						{
							backgroundImage: photoUrl !== "" ? "url('" + encodeURI(photoUrl) + "')" : "",
							backgroundSize: photoUrl !== "" ? "30px" : ""
						}
				}
			);

			this._nameElement = BX.create("a",
				{
					props: { className: "crm-widget-employee-name", target: "_blank" },
					text: formattedName
				}
			);

			if (showUrl !== "")
			{
				this._photoElement.href = showUrl;
				this._nameElement.href = showUrl;
			}

			this._positionElement = BX.create("SPAN",
				{
					props: { className: "crm-widget-employee-position" },
					text: position
				}
			);

			if(this.isInEditMode())
			{
				this._wrapper.appendChild(this.createDragButton());
			}

			var userElement = BX.create("div", { props: { className: "crm-widget-employee-container" } });
			if (this.isEditable())
			{
				this._editButton = BX.create("span", { props: { className: "crm-widget-employee-change" }, text: this.getMessage("change") });
				BX.bind(this._editButton, "click", BX.delegate(this.onEditButtonClick, this));
				userElement.appendChild(this._editButton);
			}

			userElement.appendChild(this._photoElement);
			userElement.appendChild(
				BX.create("span",
					{
						props: { className: "crm-widget-employee-info" },
						children: [ this._nameElement, this._positionElement ]
					}
				)
			);

			this._searchBox = null;

			this._wrapper.appendChild(
				BX.create("div",
					{ props: { className: "crm-entity-widget-content-block-inner" }, children: [ userElement ] }
				)
			);
		}
		else
		{
			this._innerWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-block-inner" } });
			this._wrapper.appendChild(this._innerWrapper);
			BX.addClass(this._innerWrapper, "crm-entity-widget-content-inner");
			var fieldContainer = BX.create("div", { props: { className: "crm-entity-widget-content-block-field-container" } });
			this._innerWrapper.appendChild(fieldContainer);
			this._innerContainer = BX.create("div", { props: { className: "crm-entity-widget-content-block-field-container-inner" } });
			fieldContainer.appendChild(this._innerContainer);
			var name = this._schemeElement.getName();
			this._selectedValue = this._model.getField(name);
			this._contactSearchBoxes = [];
			this._searchBox = this.addSearchBox(this.createSearchBox());
		}

		if(this.isContextMenuEnabled())
		{
			this._wrapper.appendChild(this.createContextMenuButton());
		}

		if(this.isDragEnabled())
		{
			this.initializeDragDropAbilities();
		}

		this.registerLayout(options);

		this._hasLayout = true;
	};

	BX.Crm.EntityEditorOrderUser.prototype.onEditButtonClick = function(event)
	{
		this.switchToSingleEditMode(event.target);
	};
	BX.Crm.EntityEditorOrderUser.prototype.isSingleEditEnabled = function()
	{
		return true;
	}
	BX.Crm.EntityEditorOrderUser.prototype.hasContentToDisplay = function()
	{
		return this.hasValue();
	};
	BX.Crm.EntityEditorOrderUser.prototype.processModelChange = function(params)
	{
		if(BX.prop.get(params, "originator", null) === this)
		{
			return;
		}

		if(!BX.prop.getBoolean(params, "forAll", false)
			&& BX.prop.getString(params, "name", "") !== this.getName()
		)
		{
			return;
		}

		if (BX.prop.getBoolean(params, "forAll", false) && this._searchBox)
		{
			var defaultUserValues = this._model.getSchemeField(this._schemeElement, "defaultUserList", []);
			this._searchBox._searchControl.setItems(defaultUserValues);
		}

		var name = this._schemeElement.getName();
		if (this._selectedValue != this._model.getField(name))
		{
			this.refreshLayout();
		}
	};
	BX.Crm.EntityEditorOrderUser.prototype.addSearchBox = function(searchBox)
	{
		this._contactSearchBoxes.push(searchBox);

		var layoutOptions = {};
		if(this._addContactButton)
		{
			layoutOptions["anchor"] = this._addContactButton;
		}

		searchBox.layout(layoutOptions);

		return searchBox;
	};
	BX.Crm.EntityEditorOrderUser.prototype.createSearchBox = function(params)
	{
		var name = this._schemeElement.getName();
		var value = this._model.getField(name);
		var entityInfo = BX.CrmEntityInfo.create({
			title: this._model.getSchemeField(this._schemeElement, "formated", ""),
			entityId: value,

		});
		if(entityInfo !== null && !(entityInfo instanceof BX.CrmEntityInfo))
		{
			entityInfo = null;
		}
		return(
			BX.Crm.EntityEditorOrderClientSearchBox.create(
				this._id,
				{
					entityInfo: entityInfo,
					enableCreation: false,
					loaderConfig: this._primaryLoaderConfig,
					lastEntityInfos: this._model.getSchemeField(this._schemeElement, "defaultUserList", []),
					container: this._innerContainer,
					enableDeletion: true,
					placeholder: this.getMessage("searchPlaceholder"),
					parentField: this
				}
			)
		);
	};
	BX.Crm.EntityEditorOrderUser.prototype.save = function()
	{
		if(!this.isEditable())
		{
			return;
		}

		this._model.setField(this.getName(), this._selectedValue);
	};
	BX.Crm.EntityEditorOrderUser.prototype.onContactAddButtonClick = function(e)
	{
	};
	BX.Crm.EntityEditorOrderUser.prototype.onCompanyReset = function(sender, previousEntityInfo)
	{
	};
	BX.Crm.EntityEditorOrderUser.prototype.onCompanyChange = function(sender, currentEntityInfo, previousEntityInfo)
	{
	};

	BX.Crm.EntityEditorOrderUser.prototype.onContactInfosLoad = function(sender, result)
	{
	};

	BX.Crm.EntityEditorOrderUser.prototype.getRuntimeValue = function()
	{
		if (this._mode === BX.UI.EntityEditorMode.edit && this._selectedValue)
		{
			return this._selectedValue;
		}
		return "";
	};

	BX.Crm.EntityEditorOrderUser.prototype.onRequisiteChange = function(sender, eventArgs)
	{
	};

	BX.Crm.EntityEditorOrderUser.prototype.onBeforeSubmit = function()
	{
	};

	BX.Crm.EntityEditorOrderUser.prototype.processItemSelect = function(item)
	{
		var isViewMode = this._mode === BX.UI.EntityEditorMode.view;
		var editInView = this.isEditInViewEnabled();
		var id = BX.prop.getInteger(item, "id", 0);
		if(isViewMode && !editInView)
		{
			return;
		}
		this._selectedValue = id;

		if(!isViewMode)
		{
			this.markAsChanged();
		}
		else
		{
			this._editor.saveControl(this);
		}

		if(!isViewMode)
		{
			for(i = 0, length = this._editor._controllers.length; i < length; i++)
			{
				if (this._editor._controllers[i] instanceof BX.Crm.EntityEditorOrderController)
				{
					this._editor._controllers[i].onDataChanged();
				}
			}
		}
	};

	BX.Crm.EntityEditorOrderUser.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorOrderUser.messages;
		return (m.hasOwnProperty(name)
				? m[name]
				: BX.Crm.EntityEditorOrderUser.superclass.getMessage.apply(this, arguments)
		);
	};

	BX.Crm.EntityEditorOrderUser.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorOrderUser();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorOrderClientSearchBox === "undefined")
{
	BX.Crm.EntityEditorOrderClientSearchBox = function()
	{
		this._id = "";
		this._settings = {};

		this._container = null;
		this._wrapper = null;

		this._resetButton = null;

		this._parentField = null;
		this._entityInfo = null;

		this._searchInput = null;
		this._searchControl = null;

		this._loaderConfig = null;

		this._resetButtonHandler = BX.delegate(this.onResetButtonClick, this);
		this._hasLayout = false;
	};

	BX.Crm.EntityEditorOrderClientSearchBox.prototype.initialize = function(id, settings)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
		this._settings = settings ? settings : {};

		this._parentField = BX.prop.get(this._settings, "parentField", null);
		this._container = BX.prop.getElementNode(this._settings, "container", null);

		var entityInfo = BX.prop.get(this._settings, "entityInfo", null);
		if(entityInfo)
		{
			this._entityInfo = entityInfo;
		}

		this._loaderConfig = BX.prop.get(this._settings, "loaderConfig", null);
	};

	BX.Crm.EntityEditorOrderClientSearchBox.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		if(!BX.type.isPlainObject(options))
		{
			options = {};
		}

		this._wrapper = BX.create("div", { props: { className: "crm-entity-widget-content-search-row" } });
		this.innerWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-search-inner" } });

		var anchor = BX.prop.getElementNode(options, "anchor", null);
		if(anchor)
		{
			this._container.insertBefore(this._wrapper, anchor);
		}
		else
		{
			this._container.appendChild(this._wrapper);
		}

		this._wrapper.appendChild(this.innerWrapper);

		var boxWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-search-box" } });
		this.innerWrapper.appendChild(boxWrapper);

		var icon = BX.create("div", { props: { className: "crm-entity-widget-img-box crm-entity-widget-img-contact" } });
		boxWrapper.appendChild(icon);

		this._searchInput = BX.create("input",
			{
				props:
					{
						type: "text",
						placeholder: BX.prop.getString(this._settings, "placeholder", ""),
						className: "crm-entity-widget-content-input crm-entity-widget-content-search-input",
						autocomplete: "off"
					}
			}
		);
		boxWrapper.appendChild(this._searchInput);
		BX.bind(this._searchInput, "focus", this._inputFocusHandler);
		BX.bind(this._searchInput, "blur", this._inputBlurHandler);

		this._resetButton = BX.create("div", { props: { className: "crm-entity-widget-btn-close" } });
		this.innerWrapper.appendChild(this._resetButton);
		BX.bind(this._resetButton, "click", this._resetButtonHandler);

		if(this._entityInfo)
		{
			//Move it in BX.UI.Dropdown
			this._searchInput.value = this._entityInfo.getTitle();
		}

		this._searchControl = new BX.UI.Dropdown({
			searchAction: "crm.api.orderbuyer.search",
			searchOptions: {
				emptyItem: {
					'subtitle': this.getMessage("notFound"),
					'title': ""
				}
			},
			searchResultRenderer: null,
			targetElement: this._searchInput,
			items: BX.prop.getArray(this._settings, "lastEntityInfos", []),
			enableCreation: false,
			showEmptyContainer: true,
			messages:
				{
					notFound: this.getMessage("notFound")
				},
			events:
				{
					onSelect: this.onEntitySelect.bind(this)
				}
		});

		this._hasLayout = true;
	};
	BX.Crm.EntityEditorOrderClientSearchBox.prototype.validate = function(result)
	{
		return true;
	};
	BX.Crm.EntityEditorOrderClientSearchBox.prototype.release = function()
	{
		return true;
	};
	BX.Crm.EntityEditorOrderClientSearchBox.prototype.onEntitySelect = function(sender, item)
	{
		var entityId = BX.prop.getInteger(item, "id", 0);
		var title = BX.prop.getString(item, "title", "");
		if(entityId <= 0)
		{
			return;
		}
		this._searchInput.value = title;
		if (this._parentField instanceof BX.Crm.EntityEditorOrderUser)
		{
			this._parentField.processItemSelect(item);
		}
		this._searchControl.alertEmptyContainer = null;
		this._searchControl.destroyPopupWindow();
	};
	BX.Crm.EntityEditorOrderClientSearchBox.prototype.onResetButtonClick = function(e)
	{
		this._searchInput.value = "";
		if (this._parentField instanceof BX.Crm.EntityEditorOrderUser)
		{
			this._parentField.processItemSelect({
				item: null
			});
		}
	};
	BX.Crm.EntityEditorOrderClientSearchBox.prototype.getMessage = function(name)
	{
		return BX.prop.getString(BX.Crm.EntityEditorOrderClientSearchBox.messages, name);
	};

	if(typeof(BX.Crm.EntityEditorOrderClientSearchBox.messages) === "undefined")
	{
		BX.Crm.EntityEditorOrderClientSearchBox.messages = {};
	}

	BX.Crm.EntityEditorOrderClientSearchBox.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorOrderClientSearchBox();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorOrderClient === "undefined")
{
	BX.Crm.EntityEditorOrderClient = function()
	{
		BX.Crm.EntityEditorOrderClient.superclass.constructor.apply(this);
	};

	BX.extend(BX.Crm.EntityEditorOrderClient, BX.Crm.EntityEditorClientLight);

	BX.Crm.EntityEditorOrderClient.prototype.save = function()
	{
		BX.Crm.EntityEditorOrderClient.superclass.save.apply(this);
		if (this._item && this._item._requisitePanel)
		{
			this._model.setField('REQUISITE_BINDING', this._item._requisitePanel.getRuntimeValue());
		}
	};

	BX.Crm.EntityEditorOrderClient.prototype.getRuntimeValue = function()
	{
		var value = {};
		var fieldName = BX.prop.getString(this._map, "companyId", "");

		if(fieldName !== "" && this._companyInfos && this._companyInfos.length() > 0)
		{
			var companyInfo = this._companyInfos.get(0);
			value[fieldName] = companyInfo.getId();
		}

		var contactIds = [];
		var i, length;

		if(this._contactInfos !== null)
		{
			var infos = this._contactInfos.getItems();
			for(i = 0, length = infos.length; i < length; i++)
			{
				var entityInfo = infos[i];
				contactIds.push(entityInfo.getId());
			}
		}
		fieldName = BX.prop.getString(this._map, "contactIds", "");
		if(fieldName !== "")
		{
			value[fieldName] = contactIds;
		}

		if (this._item && this._item._requisitePanel)
		{
			value['REQUISITE_BINDING'] = this._item._requisitePanel.getRuntimeValue();
		}
		return value;
	};

	BX.Crm.EntityEditorOrderClient.prototype.onCompanyChange = function(sender, currentEntityInfo, previousEntityInfo)
	{
		BX.Crm.EntityEditorOrderClient.superclass.onCompanyChange.call(this, sender, currentEntityInfo, previousEntityInfo);
		if (currentEntityInfo.getId() > 0)
		{
			for (var i = 0, length = this._editor._controllers.length; i < length; i++)
			{
				if (this._editor._controllers[i] instanceof BX.Crm.EntityEditorOrderController)
				{
					this._editor._controllers[i].onDataChanged();
					this._editor._controllers[i].unlockSending();
					return;
				}
			}
		}
	};
	BX.Crm.EntityEditorOrderClient.prototype.onContactChange = function(sender, currentEntityInfo, previousEntityInfo)
	{
		BX.Crm.EntityEditorOrderClient.superclass.onContactChange.call(this, sender, currentEntityInfo, previousEntityInfo);

		for (var i = 0, length = this._editor._controllers.length; i < length; i++)
		{
			if (this._editor._controllers[i] instanceof BX.Crm.EntityEditorOrderController)
			{
				this._editor._controllers[i].onDataChanged();
				this._editor._controllers[i].unlockSending();
				return;
			}
		}
	};
	BX.Crm.EntityEditorOrderClient.prototype.onContactInfosLoad = function(sender, result)
	{
		BX.Crm.EntityEditorOrderClient.superclass.onContactInfosLoad.call(this, sender, result);
		for (var i = 0, length = this._editor._controllers.length; i < length; i++)
		{
			if (this._editor._controllers[i] instanceof BX.Crm.EntityEditorOrderController)
			{
				this._editor._controllers[i].onDataChanged();
				this._editor._controllers[i].unlockSending();
				return;
			}
		}
	};

	BX.Crm.EntityEditorOrderClient.prototype.processModelChange = function(params)
	{
		if(BX.prop.get(params, "originator", null) === this)
		{
			return;
		}

		if(!BX.prop.getBoolean(params, "forAll", false)
			&& BX.prop.getString(params, "name", "") !== this.getName()
		)
		{
			return;
		}

		if (BX.prop.getBoolean(params, "forAll", false) && this._companySearchBox)
		{
			var lastCompanyValues = this._model.getSchemeField(this._schemeElement, "lastCompanyInfos", []);
			if (BX.type.isNotEmptyObject(lastCompanyValues))
			{
				this._companySearchBox._searchControl.setItems(lastCompanyValues);
				this._companySearchBox._searchControl.setDefaultItems(lastCompanyValues);
			}
		}

		var lastContactValues = this._model.getSchemeField(this._schemeElement, "lastContactInfos", []);
		if (BX.prop.getBoolean(params, "forAll", false)
			&& BX.type.isArray(this._contactSearchBoxes)
			&& this._contactSearchBoxes.length > 0
			&& BX.type.isNotEmptyObject(lastContactValues)
		)
		{
			for (var i=0; i<this._contactSearchBoxes.length; i++)
			{
				this._contactSearchBoxes[i]._searchControl.setItems(lastContactValues);
				this._contactSearchBoxes[i]._searchControl.setDefaultItems(lastContactValues);
			}
		}
	};

	BX.Crm.EntityEditorOrderClient.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorOrderClient();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof BX.Crm.EntityEditorMultiList === "undefined")
{
	/**
	 * @deprecated
	 */
	BX.Crm.EntityEditorMultiList = BX.UI.EntityEditorMultiList;
}
if(typeof BX.Crm.EntityEditorOrderQuantity === "undefined")
{
	BX.Crm.EntityEditorOrderQuantity = function()
	{
		BX.Crm.EntityEditorOrderQuantity.superclass.constructor.apply(this);
		// this._currencyEditor = null;
		this._amountInput = null;
		this._measureInput = null;
		this._sumElement = null;
		this._selectContainer = null;
		this._inputWrapper = null;
		this._innerWrapper = null;
		this._selectedMeasureValue = "";
		this._selectorClickHandler = BX.delegate(this.onSelectorClick, this);
		this._isMesureMenuOpened = false;
	};
	BX.extend(BX.Crm.EntityEditorOrderQuantity, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorOrderQuantity.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.UI.EntityEditorModeSwitchType.common;
		if(mode === BX.UI.EntityEditorMode.edit)
		{
			result |= BX.UI.EntityEditorModeSwitchType.button|BX.UI.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorOrderQuantity.prototype.getContentWrapper = function()
	{
		return this._innerWrapper;
	};
	BX.Crm.EntityEditorOrderQuantity.prototype.focus = function()
	{
		if(this._amountInput)
		{
			BX.focus(this._amountInput);
			BX.Crm.EditorTextHelper.getCurrent().selectAll(this._amountInput);
		}
	};
	BX.Crm.EntityEditorOrderQuantity.prototype.getValue = function(defaultValue)
	{
		if(!this._model)
		{
			return "";
		}

		return(
			this._model.getStringField(
				this.getAmountFieldName(),
				(defaultValue !== undefined ? defaultValue : "")
			)
		);
	};
	BX.Crm.EntityEditorOrderQuantity.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated({ classNames: [ "crm-entity-widget-content-block-field-order-quantity" ] });
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		//var name = this.getName();
		var title = this.getTitle();
		var data = this.getData();

		var amountInputName = BX.prop.getString(data, "amount");
		var measureInputName = BX.prop.getString(BX.prop.getObject(data, "measure"), "name");

		this._selectedMeasureValue = this._model.getStringField(
			BX.prop.getString(BX.prop.getObject(data, "measure"), "name", "")
		);

		var measureItems = BX.prop.getArray(BX.prop.getObject(data, "measure"), "items");
		var measureName = '';
		if(!this._selectedMeasureValue)
		{
			var firstItem =  measureItems.length > 0 ? measureItems[0] : null;
			if(firstItem)
			{
				this._selectedMeasureValue = firstItem["VALUE"];
				measureName = firstItem["NAME"];
			}
		}
		else
		{
			measureName = this._editor.findOption(
				this._selectedMeasureValue,
				measureItems
			);
		}

		var amountFieldName = this.getAmountFieldName();
		// var amountValue = this._model.getField(amountFieldName, ""); //SET CURRENT SUM VALUE
		var formatted = this._model.getField(BX.prop.getString(data, "formatted"), ""); //SET FORMATTED VALUE

		// this._amountValue = null;
		this._amountInput = null;
		this._measureInput = null;
		this._selectContainer = null;
		this._innerWrapper = null;
		this._sumElement = null;

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		if(this._mode === BX.UI.EntityEditorMode.edit)
		{
			this._wrapper.appendChild(this.createTitleNode(title));

			// this._amountValue = BX.create("input",
			// 	{
			// 		attrs:
			// 			{
			// 				name: amountInputName,
			// 				type: "hidden",
			// 				value: amountValue
			// 			}
			// 	}
			// );

			this._amountInput = BX.create("input",
				{
					attrs:
						{
							className: "crm-entity-widget-content-input",
							name: amountInputName,
							type: "text",
							value: formatted
						}
				}
			);
			BX.bind(this._amountInput, "input", this._changeHandler);

			if(this._model.isFieldLocked(amountFieldName))
			{
				this._amountInput.disabled = true;
			}

			this._measureInput = BX.create("input",
				{
					attrs:
						{
							name: measureInputName,
							type: "hidden",
							value: this._selectedMeasureValue
						}
				}
			);

			this._selectContainer = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-select" },
					text: measureName
				}
			);
			BX.bind(this._selectContainer, "click", this._selectorClickHandler);

			this._inputWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-input-wrapper" },
					children:
						[
							// this._amountValue,
							this._amountInput,
							this._measureInput,
							BX.create('div',
								{
									props: { className: "crm-entity-widget-content-block-select" },
									children: [ this._selectContainer ]
								}
							)
						]
				}
			);

			this._innerWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-inner crm-entity-widget-content-block-colums-input" },
					children: [ this._inputWrapper ]
				}
			);
		}
		else //this._mode === BX.UI.EntityEditorMode.view
		{
			this._wrapper.appendChild(this.createTitleNode(title));
			if(this.hasContentToDisplay())
			{
				var formattedName = BX.prop.getString(
					this._schemeElement.getDataObjectParam("formattedWithCurrency", {}),
					"name",
					""
				);
				this._sumElement = BX.create("span", { attrs: { className: "crm-entity-widget-content-block-wallet" } });
				this._sumElement.innerHTML = this._model.getField(formattedName, "");//
				this._innerWrapper = BX.create("div",
					{
						props: { className: "crm-entity-widget-content-block-inner" },
						children:
							[
								BX.create("div",
									{
										props: { className: "crm-entity-widget-content-block-colums-block" },
										children:
											[
												BX.create("span",
													{
														props: { className: "crm-entity-widget-content-block-colums" },
														children: [ this._sumElement ]
													}
												)
											]
									}
								)
							]
					}
				);
			}
			else
			{
				this._innerWrapper = BX.create("div",
					{
						props: { className: "crm-entity-widget-content-block-inner" },
						text: this.getMessage("isEmpty")
					}
				);
			}
		}
		this._wrapper.appendChild(this._innerWrapper);

		if(this.isContextMenuEnabled())
		{
			this._wrapper.appendChild(this.createContextMenuButton());
		}

		if(this.isDragEnabled())
		{
			this.initializeDragDropAbilities();
		}

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorOrderQuantity.prototype.doClearLayout = function(options)
	{
		BX.PopupMenu.destroy(this._id);
		// this._amountValue = null;
		this._amountInput = null;
		this._measureInput = null;
		this._sumElement = null;
		this._selectContainer = null;
		this._inputWrapper = null;
		this._innerWrapper = null;
	};

	BX.Crm.EntityEditorOrderQuantity.prototype.getAmountFieldName = function()
	{
		return this._schemeElement.getDataStringParam("amount", "");
	};
	BX.Crm.EntityEditorOrderQuantity.prototype.getMeasureFieldName = function()
	{
		return BX.prop.getString(
			this._schemeElement.getDataObjectParam("measure", {}),
			"name",
			""
		);
	};
	BX.Crm.EntityEditorOrderQuantity.prototype.onSelectorClick = function (e)
	{
		this.openMeasureMenu();
	};
	BX.Crm.EntityEditorOrderQuantity.prototype.openMeasureMenu = function()
	{
		if(this._isMesureMenuOpened)
		{
			return;
		}

		var data = this._schemeElement.getData();
		var measureList = BX.prop.getArray(BX.prop.getObject(data, "measure"), "items"); //{NAME, VALUE}

		var key = 0;
		var menu = [];
		while (key < measureList.length)
		{
			menu.push(
				{
					text: measureList[key]["NAME"],
					value: measureList[key]["VALUE"],
					onclick: BX.delegate( this.onMeasureSelect, this)
				}
			);
			key++
		}

		BX.PopupMenu.show(
			this._id,
			this._selectContainer,
			menu,
			{
				angle: false, width: this._selectContainer.offsetWidth + 'px',
				events:
					{
						onPopupShow: BX.delegate( this.onMeasureMenuOpen, this),
						onPopupClose: BX.delegate( this.onMeasureMenuClose, this)
					}
			}
		);
		BX.PopupMenu.currentItem.popupWindow.setWidth(BX.pos(this._selectContainer)["width"]);
	};
	BX.Crm.EntityEditorOrderQuantity.prototype.closeMeasureMenu = function()
	{
		if(!this._isMesureMenuOpened)
		{
			return;
		}

		var menu = BX.PopupMenu.getMenuById(this._id);
		if(menu)
		{
			menu.popupWindow.close();
		}
	};
	BX.Crm.EntityEditorOrderQuantity.prototype.onMeasureMenuOpen = function()
	{
		BX.addClass(this._selectContainer, "active");
		this._isMesureMenuOpened = true;
	};
	BX.Crm.EntityEditorOrderQuantity.prototype.onMeasureMenuClose = function()
	{
		BX.PopupMenu.destroy(this._id);

		BX.removeClass(this._selectContainer, "active");
		this._isMesureMenuOpened = false;
	};
	BX.Crm.EntityEditorOrderQuantity.prototype.onMeasureSelect = function(e, item)
	{
		this.closeMeasureMenu();

		this._selectedMeasureValue = this._measureInput.value = item.value;
		this._selectContainer.innerHTML = BX.util.htmlspecialchars(item.text);

		this.markAsChanged(
			{
				fieldName: this.getMeasureFieldName(),
				fieldValue: this._selectedMeasureValue
			}
		);
	};
	BX.Crm.EntityEditorOrderQuantity.prototype.processModelLock = function(params)
	{
		var name = BX.prop.getString(params, "name", "");
		if(this.getAmountFieldName() === name)
		{
			this.refreshLayout();
		}
	};
	BX.Crm.EntityEditorOrderQuantity.prototype.validate = function(result)
	{
		if(!(this._mode === BX.UI.EntityEditorMode.edit && this._amountInput))
		{
			throw "BX.Crm.EntityEditorOrderQuantity. Invalid validation context";
		}

		this.clearError();

		if(this.hasValidators())
		{
			return this.executeValidators(result);
		}

		var isValid = !this.isRequired() || BX.util.trim(this._amountInput.value) !== "";
		if(!isValid)
		{
			result.addError(BX.Crm.EntityValidationError.create({ field: this }));
			this.showRequiredFieldError(this._inputWrapper);
		}
		return isValid;
	};
	BX.Crm.EntityEditorOrderQuantity.prototype.showError =  function(error, anchor)
	{
		BX.Crm.EntityEditorOrderQuantity.superclass.showError.apply(this, arguments);
		if(this._amountInput)
		{
			BX.addClass(this._amountInput, "crm-entity-widget-content-error");
		}
	};
	BX.Crm.EntityEditorOrderQuantity.prototype.clearError =  function()
	{
		BX.Crm.EntityEditorOrderQuantity.superclass.clearError.apply(this);
		if(this._amountInput)
		{
			BX.removeClass(this._amountInput, "crm-entity-widget-content-error");
		}
	};
	BX.Crm.EntityEditorOrderQuantity.prototype.getRuntimeValue = function()
	{
		var data = [];
		if (this._mode === BX.UI.EntityEditorMode.edit)
		{
			if(this._amountInput)
			{
				data[ BX.prop.getString(data, "amount")] = this._amountInput.value;
			}
			data[ BX.prop.getString(data, "measure")] = this._selectedMeasureValue;

			return data;
		}
		return "";
	};
	BX.Crm.EntityEditorOrderQuantity.prototype.save = function()
	{
		var data = this._schemeElement.getData();
		this._model.setField(
			BX.prop.getString(BX.prop.getObject(data, "measure"), "name"),
			this._selectedMeasureValue
		);

		if(this._amountInput)
		{
			this._model.setField(BX.prop.getString(data, "amount"), this._amountInput.value);
			this._model.setField(BX.prop.getString(data, "formatted"), this._amountInput.value);
		}
	};

	BX.Crm.EntityEditorOrderQuantity.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorOrderQuantity();
		self.initialize(id, settings);
		return self;
	}
}
if(typeof BX.Crm.EntityEditorOrderPropertyFile === "undefined")
{
	BX.Crm.EntityEditorOrderPropertyFile = function()
	{
		BX.Crm.EntityEditorOrderPropertyFile.superclass.constructor.apply(this);
	};

	BX.extend(BX.Crm.EntityEditorOrderPropertyFile, BX.UI.EntityEditorCustom);

	BX.Crm.EntityEditorOrderPropertyFile.prototype.layout = function(options)
	{
		BX.Crm.EntityEditorOrderPropertyFile.superclass.layout.apply(this, arguments);
		if (this._mode === BX.UI.EntityEditorMode.edit)
		{
			setTimeout(
				BX.delegate(function(){
					var deleteSelectorList = this._innerWrapper.querySelectorAll('input[type="checkbox"]');
					for (var i=0; i < deleteSelectorList.length; i++)
					{
						BX.bind(deleteSelectorList[i], 'change', BX.delegate(function(){
							BX.onCustomEvent('CrmOrderPropertySetCustom', [this.getName()]);
						}, this));
					}
				}, this),
				10
			);
		}
	};

	BX.Crm.EntityEditorOrderPropertyFile.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorOrderPropertyFile();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof BX.Crm.OrderModel === "undefined")
{
	BX.Crm.OrderModel = function()
	{
		BX.Crm.OrderModel.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.OrderModel, BX.Crm.EntityModel);
	BX.Crm.OrderModel.prototype.doInitialize = function()
	{
		BX.addCustomEvent(window, "Crm.EntityProgress.Change", BX.delegate(this.onEntityProgressChange, this));
	};
	BX.Crm.OrderModel.prototype.onEntityProgressChange = function(sender, eventArgs)
	{
		if(BX.prop.getInteger(eventArgs, "entityTypeId", 0) !== this.getEntityTypeId()
			|| BX.prop.getInteger(eventArgs, "entityId", 0) !== this.getEntityId()
		)
		{
			return;
		}

		var stepId = BX.prop.getString(eventArgs, "currentStepId", "");
		if(stepId !== this.getField("STATUS_ID", ""))
		{
			this.setField("STATUS_ID", stepId);
		}
	};
	BX.Crm.OrderModel.prototype.getEntityTypeId = function()
	{
		return BX.CrmEntityType.enumeration.order;
	};
	BX.Crm.OrderModel.prototype.isCaptionEditable = function()
	{
		return false;
	};
	BX.Crm.OrderModel.prototype.getCaption = function()
	{
		var title = this.getField("TITLE");
		return BX.type.isString(title) ? title : "";
	};
	BX.Crm.OrderModel.prototype.setCaption = function(caption)
	{
		this.setField("TITLE", caption);
	};
	BX.Crm.OrderModel.prototype.prepareCaptionData = function(data)
	{
		data["TITLE"] = this.getField("TITLE", "");
	};
	BX.Crm.OrderModel.create = function(id, settings)
	{
		var self = new BX.Crm.OrderModel();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.OrderShipmentModel === "undefined")
{
	BX.Crm.OrderShipmentModel = function()
	{
		BX.Crm.OrderShipmentModel.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.OrderShipmentModel, BX.Crm.EntityModel);
	BX.Crm.OrderShipmentModel.prototype.doInitialize = function()
	{
		BX.addCustomEvent(window, "Crm.EntityProgress.Change", BX.delegate(this.onEntityProgressChange, this));
	};
	BX.Crm.OrderShipmentModel.prototype.onEntityProgressChange = function(sender, eventArgs)
	{
		if(BX.prop.getInteger(eventArgs, "entityTypeId", 0) !== this.getEntityTypeId()
			|| BX.prop.getInteger(eventArgs, "entityId", 0) !== this.getEntityId()
		)
		{
			return;
		}

		var stepId = BX.prop.getString(eventArgs, "currentStepId", "");
		if(stepId !== this.getField("STATUS_ID", ""))
		{
			this.setField("STATUS_ID", stepId);
		}
	};
	BX.Crm.OrderShipmentModel.prototype.isCaptionEditable = function()
	{
		return false;
	};
	BX.Crm.OrderShipmentModel.prototype.getEntityTypeId = function()
	{
		return BX.CrmEntityType.enumeration.ordershipment;
	};
	BX.Crm.OrderShipmentModel.prototype.getCaption = function()
	{
		return this.getField("TITLE", "");
	};
	BX.Crm.OrderShipmentModel.create = function(id, settings)
	{
		var self = new BX.Crm.OrderShipmentModel();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.OrderPaymentModel === "undefined")
{
	BX.Crm.OrderPaymentModel = function()
	{
		BX.Crm.OrderPaymentModel.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.OrderPaymentModel, BX.Crm.EntityModel);
	BX.Crm.OrderPaymentModel.prototype.isCaptionEditable = function()
	{
		return false;
	};
	BX.Crm.OrderPaymentModel.prototype.getEntityTypeId = function()
	{
		return BX.CrmEntityType.enumeration.orderpayment;
	};
	BX.Crm.OrderPaymentModel.prototype.getCaption = function()
	{
		return this.getField("TITLE", "");
	};
	BX.Crm.OrderPaymentModel.create = function(id, settings)
	{
		var self = new BX.Crm.OrderPaymentModel();
		self.initialize(id, settings);
		return self;
	};
}

if (typeof BX.Crm.DocumentOrderShipmentModel === "undefined")
{
	BX.Crm.DocumentOrderShipmentModel = function()
	{
		BX.Crm.DocumentOrderShipmentModel.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.DocumentOrderShipmentModel, BX.Crm.EntityModel);
	BX.Crm.DocumentOrderShipmentModel.prototype.doInitialize = function()
	{
		BX.addCustomEvent(window, "Crm.EntityProgress.Change", BX.delegate(this.onEntityProgressChange, this));
	};
	BX.Crm.DocumentOrderShipmentModel.prototype.onEntityProgressChange = function(sender, eventArgs)
	{
		if (
			BX.prop.getInteger(eventArgs, "entityTypeId", 0) !== this.getEntityTypeId()
			|| BX.prop.getInteger(eventArgs, "entityId", 0) !== this.getEntityId()
		)
		{
			return;
		}

		var stepId = BX.prop.getString(eventArgs, "currentStepId", "");
		if (stepId !== this.getField("STATUS_ID", ""))
		{
			this.setField("STATUS_ID", stepId);
		}
	};
	BX.Crm.DocumentOrderShipmentModel.prototype.isCaptionEditable = function()
	{
		return false;
	};
	BX.Crm.DocumentOrderShipmentModel.prototype.getEntityTypeId = function()
	{
		return BX.CrmEntityType.enumeration.shipmentDocument;
	};
	BX.Crm.DocumentOrderShipmentModel.prototype.getCaption = function()
	{
		return this.getField("TITLE", "");
	};
	BX.Crm.DocumentOrderShipmentModel.create = function(id, settings)
	{
		var self = new BX.Crm.DocumentOrderShipmentModel();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.Crm.EntityEditorOrderUserSelector) === "undefined")
{
	BX.Crm.EntityEditorOrderUserSelector = function()
	{
		BX.Crm.EntityEditorOrderUserSelector.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.EntityEditorOrderUserSelector, BX.UI.EntityEditorUserSelector);

	BX.Crm.EntityEditorOrderUserSelector.prototype.initialize =  function(id, settings)
	{
		BX.Crm.EntityEditorOrderUserSelector.superclass.initialize.call(this, id, settings);
		this._editor = BX.prop.get(settings, 'editor');
		this._url = BX.prop.getString(settings, 'url');
		for(var i = 0, length = this._editor._controllers.length; i < length; i++)
		{
			if (this._editor._controllers[i] instanceof BX.Crm.EntityEditorOrderController)
			{
				this._controller = this._editor._controllers[i];
				break;
			}
		}
	};

	BX.Crm.EntityEditorOrderUserSelector.prototype.open = function(anchor)
	{
		if (!(BX.type.isDomNode(this._editor._formElement) && BX.type.isNotEmptyString(this._url)))
		{
			BX.Crm.EntityEditorOrderUserSelector.superclass.open.call(this, anchor);
			return;
		}
		window.open(
			this._url,
			'',
			'scrollbars=yes,resizable=yes,width=840,height=500,top='+Math.floor((screen.height - 840)/2-14)+',left='+Math.floor((screen.width - 760)/2-5)
		);

		BX.bind(this._editor._formElement[this._id], 'change', BX.delegate(function(){
			this.onChangeUserId(this._editor._formElement[this._id].value);
		}, this));
	};
	BX.Crm.EntityEditorOrderUserSelector.prototype.onChangeUserId = function(userId)
	{
		if (this._controller)
		{
			this._controller.ajax(
				'loadUserInfo',
				{
					data: {
						USER_ID: userId,
						ENTITY_ID: this._editor.getEntityId()
					},
					onsuccess: BX.delegate(this.onLoadUserInfo, this)
				}
			);
		}
	};
	BX.Crm.EntityEditorOrderUserSelector.prototype.onLoadUserInfo = function(result)
	{
		if (this._controller)
		{
			this._controller._isRequesting = false;
			this._controller.hideLoader();
		}

		if(result.ERROR)
		{
			return;
		}
		this.onSelect(result.USER_DATA, 'users');
	};
	BX.Crm.EntityEditorOrderUserSelector.items = {};

	BX.Crm.EntityEditorOrderUserSelector.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorOrderUserSelector(id, settings);
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	}
}

if(typeof BX.Crm.EntityEditorDeliverySelector === "undefined")
{
	//Caution! BX.Crm.EntityEditorShipment.prototype uses some methods from here.
	BX.Crm.EntityEditorDeliverySelector = function()
	{
		BX.Crm.EntityEditorDeliverySelector.superclass.constructor.apply(this);
		this._innerWrapper = null;
		this._deliverySelector = null;
		this._profileSelector = null;
		this._storeSelector = null;
		this._input = null;
		this._inputStore = null;
	};

	BX.extend(BX.Crm.EntityEditorDeliverySelector, BX.Crm.EntityEditorField);

	BX.Crm.EntityEditorDeliverySelector.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.UI.EntityEditorModeSwitchType.common;
		if(mode === BX.UI.EntityEditorMode.edit)
		{
			result |= BX.UI.EntityEditorModeSwitchType.button|BX.UI.EntityEditorModeSwitchType.content;
		}
		return result;
	};


	BX.Crm.EntityEditorDeliverySelector.prototype.getContentWrapper = function()
	{
		return this._innerWrapper;
	};

	BX.Crm.EntityEditorDeliverySelector.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated();
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		var title = this.getTitle();

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		this._wrapper.appendChild(this.createTitleNode(title));

		if(this._mode === BX.UI.EntityEditorMode.edit)
		{
			this._deliverySelector = BX.create('select',{
				events: {
					change: BX.proxy(function() { this.onSelectorChange('delivery'); }, this)
				}
			});

			this.setOptionsList(
				this._deliverySelector,
				this.getModel().getField('DELIVERY_SERVICES_LIST'),
				this.getModel().getIntegerField('DELIVERY_SELECTOR_DELIVERY_ID')
			);

			var profilesList = this.getModel().getField('DELIVERY_PROFILES_LIST');

			if(BX.type.isNotEmptyObject(profilesList))
			{
				this._profileSelector = this.createProfileSelector();

				this.setOptionsList(
					this._profileSelector,
					profilesList,
					this.getModel().getIntegerField('DELIVERY_SELECTOR_PROFILE_ID')
				);
			}

			this._input = BX.create("input", {
				props: {
					type: "hidden",
					name: this.getName(),
					value: this.getValue()
				}
			});

			var storesList = this.getModel().getField('DELIVERY_STORES_LIST');

			if(BX.type.isNotEmptyObject(storesList))
			{
				this._storeSelector = this.createStoreSelector();

				this.setOptionsList(
					this._storeSelector,
					storesList,
					this.getModel().getIntegerField('DELIVERY_STORE_ID')
				);

				this.createInputStore();
			}

			this._innerWrapper = BX.create("div",
			{
				props: { className: "crm-entity-widget-content-block-inner" },
				children: [
					BX.create('span', {props: {className: 'fields enumeration field-wrap'}, children:[
						BX.create('span', {props: {className: 'fields enumeration field-item'}, children:[
							this._deliverySelector,
							this._input
						]})
					]})
				]
			});

			if(this._profileSelector !== null)
			{
				this.insertProfileSelector();
			}

			if(this.getModel().getField('ERRORS'))
			{
				this.processErrors(this.getModel().getField('ERRORS'));
			}
			else
			{
				this.clearError();
			}

			this._wrapper.appendChild(this._innerWrapper);

			if(this._storeSelector !== null)
			{
				this.insertStoreSelector();
			}
		}
		else //this._mode === BX.UI.EntityEditorMode.view
		{
			var name = this.getModel().getStringField('DELIVERY_SERVICE_NAME') || this.getMessage("notSelected");
			this.setDeliveryServiceName(name);

			this._wrapper.appendChild(this._innerWrapper);

			if(parseInt(this.getModel().getField('DELIVERY_STORE_ID')) > 0)
			{
				this.setDeliveryStore();
			}
		}

		if(this.isContextMenuEnabled())
		{
			this._wrapper.appendChild(this.createContextMenuButton());
		}

		if(this.isDragEnabled())
		{
			this.initializeDragDropAbilities();
		}

		this.registerLayout(options);
		this._hasLayout = true;
	};

	BX.Crm.EntityEditorDeliverySelector.prototype.createInputStore = function()
	{
		if(this._inputStore)
		{
			return;
		}

		this._inputStore = BX.create("input", {
			props: {
				type: "hidden",
				name: 'DELIVERY_STORE_ID',
				value: this.getModel().getIntegerField('DELIVERY_STORE_ID')
			}
		});
	};

	BX.Crm.EntityEditorDeliverySelector.prototype.getInputStore = function()
	{
		if(!this._inputStore)
		{
			this.createInputStore();
		}

		return this._inputStore;
	};

	BX.Crm.EntityEditorDeliverySelector.prototype.setDeliveryServiceName = function(name)
	{
		this._innerWrapper = BX.create("div",
			{
				props: { className: "crm-entity-widget-content-block-inner" },
				html: name
			}
		);
	};

	BX.Crm.EntityEditorDeliverySelector.prototype.setDeliveryStore = function()
	{
		this._innerWrapper.parentNode.insertBefore(BX.create("div",
			{
				props: { className: "crm-entity-widget-content-block-inner" },
				children:[
					BX.create("div",{
						html: this.getModel().getField('DELIVERY_STORE_TITLE')
					}),
					BX.create("div",{
							html: this.getModel().getField('DELIVERY_STORE_ADDRESS'),
							style: {fontStyle: 'italic', fontSize: '12px'}
						}
					)
				]
			}),
			this._innerWrapper.nextSibling
		);

		this._innerWrapper.parentNode.insertBefore(BX.create("div",
			{
				props: { className: "crm-entity-widget-content-block-title" },
				children:[
					BX.create("span",
						{
							props: { className: "crm-entity-widget-content-block-title-text" },
							html: this.getMessage('deliveryStore')
						}
					)
				]
			}),
			this._innerWrapper.nextSibling
		);
	};

	BX.Crm.EntityEditorDeliverySelector.prototype.getController = function()
	{
		for(var i = 0, length = this._editor._controllers.length; i < length; i++)
		{
			if (this._editor._controllers[i] instanceof BX.Crm.EntityEditorOrderShipmentController)
			{
				return this._editor._controllers[i];
			}
		}

		return null;
	};

	if(typeof(BX.Crm.EntityEditorDeliverySelector.messages) === "undefined")
	{
		BX.Crm.EntityEditorDeliverySelector.messages = {};
	}

	BX.Crm.EntityEditorDeliverySelector.prototype.onSelectorChange = function(changedObject)
	{
		this._input.value = this.obtainValueFromSelectors(
			changedObject,
			this._deliverySelector,
			this._profileSelector,
			this._mode
		);
		this.markAsChanged();
		this.getController().onChangeDelivery();
	};

	BX.Crm.EntityEditorDeliverySelector.prototype.onStoreChange = function()
	{
		this.getInputStore().value = this._storeSelector.value;
		this.getModel().setField('DELIVERY_STORE_ID', this._storeSelector.value);
		this.markAsChanged();
	};

	BX.Crm.EntityEditorDeliverySelector.prototype.setOptionsList = function(selector, list, value)
	{
		if (selector.childNodes.length > 0)
		{
			for(var i = selector.childNodes.length-1; i >= 0; i--)
			{
				selector.removeChild(selector.childNodes[i])
			}
		}

		for(i in list)
		{
			if(!list.hasOwnProperty(i))
			{
				continue;
			}

			if(typeof (list[i].ITEMS) !== 'undefined')
			{
				selector.appendChild(
					this.createOptionsGroup(
						list[i],
						value
					)
				);
			}
			else
			{
				var isSelected = parseInt(value) === parseInt(list[i].ID);

				var option = new Option(
					list[i].NAME || list[i].TITLE,
					list[i].ID,
					isSelected,
					isSelected
				);

				selector.options.add(option);
			}
		}

		return selector;
	};

	BX.Crm.EntityEditorDeliverySelector.prototype.createOptionsGroup = function(list, value)
	{
		var group = BX.create('optgroup', {props:{
			label: list.NAME
		}});

		for(var i in list.ITEMS)
		{
			if(!list.ITEMS.hasOwnProperty(i))
			{
				continue;
			}

			group.appendChild(
				BX.create('option', {
					props: {
						value: list.ITEMS[i].ID,
						selected: value == list.ITEMS[i].ID
					},
					html: list.ITEMS[i].NAME
				})
			);
		}

		return group;
	};

	BX.Crm.EntityEditorDeliverySelector.prototype.createProfileSelector = function()
	{
		return BX.create('select',{
			events: {
				change: BX.proxy(function() { this.onSelectorChange('profile'); }, this)
			}
		});
	};

	BX.Crm.EntityEditorDeliverySelector.prototype.createStoreSelector = function()
	{
		return BX.create('select',{
			events: {
				change: BX.proxy(function() { this.onStoreChange(); }, this)
			}
		});
	};

	BX.Crm.EntityEditorDeliverySelector.prototype.insertStoreSelector = function()
	{
		if(!this._innerWrapper)
		{
			return;
		}

		if(!this._storeSelector)
		{
			return;
		}

		this._innerWrapper.appendChild(
			BX.create('span', {props: {className: 'fields enumeration field-wrap'}, style: {marginTop: '20px'}, children:[
				BX.create("div", {
					props: { className: "crm-entity-widget-content-block-title" },
					style: {marginTop: '-16px'},
					children: [
						BX.create(
							"span",
							{
								attrs: { className: "crm-entity-widget-content-block-title-text" },
								text: this.getMessage('deliveryStore')
							}
						)
					]
				}),
				BX.create('span', {props: {className: 'fields enumeration field-item'}, children:[
					this._storeSelector,
					this.getInputStore()
				]})
			]}),
			this._innerWrapper
		);
	};

	BX.Crm.EntityEditorDeliverySelector.prototype.insertProfileSelector = function()
	{
		if(!this._innerWrapper)
		{
			return;
		}

		if(!this._profileSelector)
		{
			return;
		}

		this._innerWrapper.appendChild(
			BX.create('span', {props: {className: 'fields enumeration field-wrap'}, style: {marginTop: '20px'}, children:[
				BX.create("div", {
					props: { className: "crm-entity-widget-content-block-title" },
					style: {marginTop: '-16px'},
					children: [
						BX.create(
							"span",
							{
								attrs: { className: "crm-entity-widget-content-block-title-text" },
								text: this.getMessage('deliveryProfile')
							}
						)
					]
				}),
				BX.create('span', {props: {className: 'fields enumeration field-item'}, children:[
					this._profileSelector
				]})
			]}),
			this._innerWrapper
		);
	};

	BX.Crm.EntityEditorDeliverySelector.prototype.removeProfileSelector = function()
	{
		if(!this._profileSelector)
		{
			return;
		}

		this._profileSelector.parentNode.parentNode.parentNode.removeChild(this._profileSelector.parentNode.parentNode);
		this._profileSelector = null;
	};

	BX.Crm.EntityEditorDeliverySelector.prototype.removeStoreSelector = function()
	{
		if(!this._storeSelector)
		{
			return;
		}

		this._storeSelector.parentNode.parentNode.parentNode.removeChild(this._storeSelector.parentNode.parentNode);
		this._storeSelector = null;
	};

	BX.Crm.EntityEditorDeliverySelector.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorDeliverySelector.messages;
		return (m.hasOwnProperty(name)
			? m[name]
			: BX.Crm.EntityEditorDeliverySelector.superclass.getMessage.apply(this, arguments)
		);
	};

	BX.Crm.EntityEditorDeliverySelector.prototype.doClearLayout = function(options)
	{
		this._deliverySelector = null;
		this._profileSelector = null;
		this._storeSelector = null;
		this._innerWrapper = null;
		this._input = null;
		this._inputStore = null;
	};

	BX.Crm.EntityEditorDeliverySelector.prototype.refreshLayout = function(options)
	{
		if(!this._hasLayout)
		{
			return;
		}

		if(!this._isValidLayout)
		{
			BX.Crm.EntityEditorDeliverySelector.superclass.refreshLayout.apply(this, arguments);
			return;
		}

		if(this._mode === BX.UI.EntityEditorMode.edit)
		{
			if(!this._deliverySelector)
			{
				return;
			}

			this.setOptionsList(
				this._deliverySelector,
				this.getModel().getField('DELIVERY_SERVICES_LIST'),
				this.getModel().getField('DELIVERY_SELECTOR_DELIVERY_ID')
			);

			if(!this._profileSelector && BX.type.isNotEmptyObject(this.getModel().getField('DELIVERY_PROFILES_LIST')))
			{
				this._profileSelector = this.createProfileSelector();
				this.insertProfileSelector();
			}
			else if(this._profileSelector && !BX.type.isNotEmptyObject(this.getModel().getField('DELIVERY_PROFILES_LIST')))
			{
				this.removeProfileSelector();
			}

			if(this._profileSelector && BX.type.isNotEmptyObject(this.getModel().getField('DELIVERY_PROFILES_LIST')))
			{
				this.setOptionsList(
					this._profileSelector,
					this.getModel().getField('DELIVERY_PROFILES_LIST'),
					this.getModel().getField('DELIVERY_SELECTOR_PROFILE_ID')
				);
			}

			if(!this._storeSelector && BX.type.isNotEmptyObject(this.getModel().getField('DELIVERY_STORES_LIST')))
			{
				this._storeSelector = this.createStoreSelector();
				this.insertStoreSelector();
			}
			else if(this._storeSelector && !BX.type.isNotEmptyObject(this.getModel().getField('DELIVERY_STORES_LIST')))
			{
				this.removeStoreSelector();
			}

			if(this._storeSelector && BX.type.isNotEmptyObject(this.getModel().getField('DELIVERY_STORES_LIST')))
			{
				this.setOptionsList(
					this._storeSelector,
					this.getModel().getField('DELIVERY_STORES_LIST'),
					this.getModel().getField('DELIVERY_STORE_ID')
				);
			}

			if(this.getModel().getField('ERRORS'))
			{
				this.processErrors(this.getModel().getField('ERRORS'));
			}
			else
			{
				this.clearError();
			}

			this._input.value = this.getValue();
		}
		else
		{
			this.setDeliveryServiceName(this.getModel().getField('DELIVERY_SERVICE_NAME'));

			if(parseInt(this.getModel().getField('DELIVERY_STORE_ID')) > 0)
			{
				this.setDeliveryStore();
			}
		}
	};

	BX.Crm.EntityEditorDeliverySelector.prototype.processErrors = function(errors)
	{
		this.clearError();

		if(errors.length)
		{
			this.showError(errors.join(', '));
		}
	};

	BX.Crm.EntityEditorDeliverySelector.prototype.processModelChange = function(params)
	{
		if(BX.prop.get(params, "originator", null) === this)
		{
			return;
		}

		if(!BX.prop.getBoolean(params, "forAll", false)
			&& BX.prop.getString(params, "name", "") !== this.getName()
		)
		{
			return;
		}

		this.refreshLayout();
	};
	BX.Crm.EntityEditorDeliverySelector.prototype.showError =  function(error, anchor)
	{
		BX.Crm.EntityEditorDeliverySelector.superclass.showError.apply(this, arguments);
		if(this._innerWrapper)
		{
			BX.addClass(this._innerWrapper, "crm-entity-widget-content-error");
		}
	};
	BX.Crm.EntityEditorDeliverySelector.prototype.clearError =  function()
	{
		BX.Crm.EntityEditorDeliverySelector.superclass.clearError.apply(this);
		if(this._innerWrapper)
		{
			BX.removeClass(this._innerWrapper, "crm-entity-widget-content-error");
		}
	};

	BX.Crm.EntityEditorDeliverySelector.prototype.getRuntimeValue = function()
	{
		return (this._mode === BX.UI.EntityEditorMode.edit && this._input
				? BX.util.trim(this._input.value) : ""
		);
	};

	BX.Crm.EntityEditorDeliverySelector.prototype.obtainValueFromSelectors = function(changedObject, deliverySelector, profileSelector, mode)
	{
		var result = '';

		if (mode === BX.UI.EntityEditorMode.edit)
		{
			if(profileSelector && changedObject !== 'delivery')
			{
				var profileId = profileSelector.value;
			}

			if(profileId > 0)
			{
				result = profileId;
			}
			else
			{
				var deliveryId = deliverySelector.value;

				if(deliveryId > 0)
				{
					result = deliveryId;
				}
			}
		}

		return result;
	};

	BX.Crm.EntityEditorDeliverySelector.prototype.save = function()
	{
		if(this._input)
		{
			this.getModel().setField(this.getName(), this._input.value, { originator: this });
		}
	};

	BX.Crm.EntityEditorDeliverySelector.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorDeliverySelector();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorShipmentExtraServices === "undefined")
{
	BX.Crm.EntityEditorShipmentExtraServices = function()
	{
		BX.Crm.EntityEditorDeliverySelector.superclass.constructor.apply(this);
	};

	BX.extend(BX.Crm.EntityEditorShipmentExtraServices, BX.UI.EntityEditorCustom);

	BX.Crm.EntityEditorShipmentExtraServices.prototype.getHtmlContent = function()
	{
		var result = [],
			mode = this.isInEditMode() ? "edit" : "view",
			wrapper = new BX.Crm.EntityEditorOrderExtraServicesWrapper({mode: mode}),
			data = this._model.getField('EXTRA_SERVICES_DATA');

		if(data && data.length)
		{
			for(var i = 0, l = data.length -1; i <= l; i++)
			{
				wrapper.wrapItem(data[i]).forEach(function(item, i, arr){
					result.push(item);
				});
			}
		}

		return BX.create('div',{
			style:{marginLeft: '15px', width: '100%', marginTop: '20px'},
			children: result
		}).outerHTML;
	};

	BX.Crm.EntityEditorShipmentExtraServices.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorShipmentExtraServices();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorCalculatedDeliveryPrice === "undefined")
{
	BX.Crm.EntityEditorCalculatedDeliveryPrice = function()
	{
		BX.Crm.EntityEditorCalculatedDeliveryPrice.superclass.constructor.apply(this);
		this._refreshButton = null;
	};

	BX.extend(BX.Crm.EntityEditorCalculatedDeliveryPrice, BX.Crm.EntityEditorMoney);

	BX.Crm.EntityEditorCalculatedDeliveryPrice.prototype.layout = function(options)
	{
		BX.Crm.EntityEditorCalculatedDeliveryPrice.superclass.layout.apply(this, arguments);

		if (this.getParent().getMode() === BX.UI.EntityEditorMode.edit)
		{
			this._refreshButton = BX.create('div', {
				props: {
					className: 'ui-btn ui-btn-light-border',
				},
				style: {marginLeft: '10px', marginBottom: '3px'},
				events: {
					click: function () {
						BX.onCustomEvent('onDeliveryPriceRecalculateClicked');
					}
				},
				html: this.getMessage('refresh')
			});

			if (BX.Type.isElementNode(this._innerWrapper.firstChild))
			{
				this._innerWrapper.firstChild.appendChild(this._refreshButton);
			}
			else
			{
				BX.Dom.clean(this._innerWrapper);
				this._innerWrapper.appendChild(this._refreshButton);
			}
		}
	};

	BX.Crm.EntityEditorCalculatedDeliveryPrice.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorCalculatedDeliveryPrice.messages;
		return m.hasOwnProperty(name) ? m[name] : BX.Crm.EntityEditorCalculatedDeliveryPrice.superclass.getMessage.apply(this, arguments);
	};

	BX.Crm.EntityEditorCalculatedDeliveryPrice.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorCalculatedDeliveryPrice();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorOrderLoaderController === "undefined")
{
	BX.Crm.EntityEditorOrderLoaderController = function()
	{
		this._loader = null;
	};

	BX.Crm.EntityEditorOrderLoaderController.prototype.isLoaderExists = function()
	{
		return this._loader !== null;
	};

	BX.Crm.EntityEditorOrderLoaderController.prototype.showLoader = function()
	{
		document.body.appendChild(this.getLoader());
	};

	BX.Crm.EntityEditorOrderLoaderController.prototype.hideLoader = function()
	{
		if(this._loader && this._loader.parentNode === document.body)
		{
			document.body.removeChild(this._loader);
		}
	};

	BX.Crm.EntityEditorOrderLoaderController.prototype.getLoader = function()
	{
		if(!this.isLoaderExists())
		{
			this._loader = this.createLoader();
		}

		return this._loader;
	};

	BX.Crm.EntityEditorOrderLoaderController.prototype.createLoader = function()
	{
		//document.createElementNS("http://www.w3.org/2000/svg", "rect")
		var circle1 = document.createElementNS("http://www.w3.org/2000/svg", "circle"),
			circle2 = document.createElementNS("http://www.w3.org/2000/svg", "circle"),
			svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");

		BX.addClass(circle1, 'crm-entity-order-controller-loader-path');
		circle1.setAttributeNS(null, 'cx', '50');
		circle1.setAttributeNS(null, 'cy', '50');
		circle1.setAttributeNS(null, 'r', '20');
		circle1.setAttributeNS(null, 'fill', 'none');
		circle1.setAttributeNS(null, 'stroke-miterlimit', '10');
		BX.addClass(circle2, 'crm-entity-order-controller-loader-inner-path');
		circle2.setAttributeNS(null, 'cx', '50');
		circle2.setAttributeNS(null, 'cy', '50');
		circle2.setAttributeNS(null, 'r', '20');
		circle2.setAttributeNS(null, 'fill', 'none');
		circle1.setAttributeNS(null, 'stroke-miterlimit', '10');
		BX.addClass(svg, 'crm-entity-order-controller-loader-circular');
		svg.setAttributeNS(null, 'viewBox', '25 25 50 50');
		svg.appendChild(circle1);
		svg.appendChild(circle2);

		return	BX.create('div', {props: {className: 'crm-entity-order-controller-loader-wrap'}, children:[
				BX.create('div', {props: {className: 'crm-entity-order-controller-loader-mask'}}),
				BX.create('div', {props: {className: 'crm-entity-order-controller-loader-box'}, children:[
						svg
					]})
			]});
	};

	BX.Crm.EntityEditorOrderLoaderController.create = function()
	{
		return new BX.Crm.EntityEditorOrderLoaderController();
	}
}

if(typeof BX.Crm.EntityEditorPaySystemSelector === "undefined")
{
	BX.Crm.EntityEditorPaySystemSelector = function()
	{
		BX.Crm.EntityEditorPaySystemSelector.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.EntityEditorPaySystemSelector, BX.UI.EntityEditorList);
	BX.Crm.EntityEditorPaySystemSelector.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated({ classNames: [ "ui-entity-editor-field-select" ] });
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		var name = this.getName();
		var title = this.getTitle();

		var value = this.getValue();
		var item = this.getItemByValue(value);
		var isHtmlOption = this.getDataBooleanParam('isHtml', false);
		var containerProps = {};

		if(!item)
		{
			value = this.getMessage("notSelected");
		}
		this._selectedValue = value;

		this._select = null;
		this._selectIcon = null;
		this._innerWrapper = null;

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		if(this._mode === BX.UI.EntityEditorMode.edit)
		{
			this._wrapper.appendChild(this.createTitleNode(title));

			this._input = BX.create("input", { attrs: { name: name, type: "hidden", value: value } });
			this._wrapper.appendChild(this._input);

			containerProps = { props: { className: "ui-ctl-element" }};
			if (isHtmlOption)
			{
				containerProps.html = (item ? item["NAME"] : value);
			}
			else
			{
				containerProps.text = (item ? item["NAME"] : value);
			}

			this._select = BX.create("div", containerProps);
			BX.bind(this._select, "click", this._selectorClickHandler);

			this._selectIcon = BX.create("div",
				{
					attrs: { className: "ui-ctl-after ui-ctl-icon-angle" }
				}
			);

			this._selectContainer = BX.create("div",
				{
					props: {className: "ui-ctl crm-ctl-paysystem-field ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100"},
					children :[
						this._select,
						this._selectIcon
					]
				}
			);

			this._innerWrapper = BX.create("div",
				{
					props: { className: "ui-entity-editor-content-block" },
					children: [ this._selectContainer ]
				}
			);
		}
		else// if(this._mode === BX.UI.EntityEditorMode.view)
		{
			this._wrapper.appendChild(this.createTitleNode(title));

			var text = "";
			if(!this.hasContentToDisplay())
			{
				text = BX.message("UI_ENTITY_EDITOR_FIELD_EMPTY");
			}
			else if(item)
			{
				text = item["NAME"];
			}
			else
			{
				text = value;
			}

			containerProps = {props: { className: "ui-entity-editor-content-block-text" }};

			if (isHtmlOption)
			{
				containerProps.html = text;
			}
			else
			{
				containerProps.text = text;
			}

			this._innerWrapper = BX.create("div",
				{
					props: { className: "ui-entity-editor-content-block crm-ctl-paysystem-field" },
					children:
						[
							BX.create("div", containerProps)
						]
				}
			);
		}
		this._wrapper.appendChild(this._innerWrapper);

		if(this.isContextMenuEnabled())
		{
			this._wrapper.appendChild(this.createContextMenuButton());
		}

		if(this.isDragEnabled())
		{
			this.initializeDragDropAbilities();
		}

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorPaySystemSelector.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorPaySystemSelector();
		self.initialize(id, settings);
		return self;
	}
	BX.Crm.EntityEditorPaySystemSelector.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorPaySystemSelector.messages;
		return (m.hasOwnProperty(name)
				? m[name]
				: BX.Crm.EntityEditorPaySystemSelector.superclass.getMessage.apply(this, arguments)
		);
	};
}