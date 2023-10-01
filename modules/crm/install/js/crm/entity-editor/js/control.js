BX.namespace("BX.Crm");

if(typeof BX.Crm.EntityEditorControl === "undefined")
{
	/**
	 * @deprecated
	 */
	BX.Crm.EntityEditorControl = BX.UI.EntityEditorControl;
}

if(typeof BX.Crm.EntityEditorField === "undefined")
{
	/**
	 * @extends BX.UI.EntityEditorField
	 * @constructor
	 */
	BX.Crm.EntityEditorField = function()
	{
		BX.Crm.EntityEditorField.superclass.constructor.apply(this);

		this.eventsNamespace = 'BX.Crm.EntityEditorField';
	};
	BX.extend(BX.Crm.EntityEditorField, BX.UI.EntityEditorField);

	if(typeof(BX.Crm.EntityEditorField.messages) === "undefined")
	{
		BX.Crm.EntityEditorField.messages = {};
	}
}

if(typeof BX.Crm.EntityEditorSection === "undefined")
{
	BX.Crm.EntityEditorSection = function()
	{
		BX.Crm.EntityEditorSection.superclass.constructor.apply(this);
		this.eventsNamespace = 'BX.Crm.EntityEditorSection';
	};
	BX.extend(BX.Crm.EntityEditorSection, BX.UI.EntityEditorSection);
	BX.Crm.EntityEditorSection.prototype.createFieldConfigurator = function(params)
	{
		if(!BX.type.isPlainObject(params))
		{
			throw "EntityEditorSection: The 'params' argument must be object.";
		}

		params.mandatoryConfigurator = null;
		var child = BX.prop.get(params, "field", null);
		var attrManager = this._editor.getAttributeManager();
		if(attrManager)
		{
			this._mandatoryConfigurator = attrManager.createFieldConfigurator(
				child,
				BX.UI.EntityFieldAttributeType.required
			);
			params.mandatoryConfigurator = this._mandatoryConfigurator;
		}

		var data = child ? child.getData() : null;

		if (this.getEditor().canChangeCommonConfiguration())
		{
			this._visibilityConfigurator = BX.Crm.EntityFieldVisibilityConfigurator.create(
				this._id,
				{
					editor: child ? child._editor : null,
					config: child ? BX.prop.getObject(data, "visibilityConfigs", null) : null,
					field: child ? child : null,
					restriction: this._editor.getRestriction('userFieldAccessRights')
				}
			);
			params.visibilityConfigurator = this._visibilityConfigurator;
		}
		else
		{
			this._visibilityConfigurator = null;
		}

		this._fieldConfigurator = this.getConfigurationFieldManager().createFieldConfigurator(params, this);

		this.addChild(this._fieldConfigurator, {
			related: child,
			scrollIntoView: true
		});

		if (this._fieldConfigurator instanceof BX.UI.EntityEditorUserFieldConfigurator)
		{
			this._mandatoryConfigurator = params.mandatoryConfigurator;
			BX.addCustomEvent(this._fieldConfigurator, "onSave", BX.delegate(this.onUserFieldConfigurationSave, this));
			BX.addCustomEvent(this._fieldConfigurator, "onCancel", BX.delegate(this.onFieldConfigurationCancel, this));
		}
		else
		{
			BX.addCustomEvent(this._fieldConfigurator, "onSave", BX.delegate(this.onFieldConfigurationSave, this));
			BX.addCustomEvent(this._fieldConfigurator, "onCancel", BX.delegate(this.onFieldConfigurationCancel, this));
		}
	};
	BX.Crm.EntityEditorSection.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorSection();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorText === "undefined")
{
	BX.Crm.EntityEditorText = function()
	{
		BX.Crm.EntityEditorText.superclass.constructor.apply(this);
		this.eventsNamespace = 'BX.Crm.EntityEditorText';
	};
	BX.extend(BX.Crm.EntityEditorText, BX.UI.EntityEditorText);

	BX.Crm.EntityEditorText.prototype.getEditModeHtmlNodes = function()
	{
		var nodes = BX.Crm.EntityEditorText.superclass.getEditModeHtmlNodes.apply(this);

		if(this._editor.isDuplicateControlEnabled())
		{
			var dupControlConfig = this.getDuplicateControlConfig();
			if(dupControlConfig)
			{
				if(!BX.type.isPlainObject(dupControlConfig["field"]))
				{
					dupControlConfig["field"] = {};
				}
				dupControlConfig["field"]["id"] = this.getId();
				dupControlConfig["field"]["element"] = this._input;
				this._editor.getDuplicateManager().registerField(dupControlConfig);
			}
		}

		return nodes;
	};

	BX.Crm.EntityEditorText.prototype.doClearLayout = function(options)
	{
		if(this._editor.isDuplicateControlEnabled())
		{
			var dupControlConfig = this.getDuplicateControlConfig();
			if(dupControlConfig)
			{
				if(!BX.type.isPlainObject(dupControlConfig["field"]))
				{
					dupControlConfig["field"] = {};
				}
				dupControlConfig["field"]["id"] = this.getId();
				this._editor.getDuplicateManager().unregisterField(dupControlConfig);
			}
		}
		BX.Crm.EntityEditorText.superclass.doClearLayout.apply(this, [options]);
	};

	BX.Crm.EntityEditorText.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorText();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorNumber === "undefined")
{
	BX.Crm.EntityEditorNumber = BX.UI.EntityEditorNumber;
}

if(typeof BX.Crm.EntityEditorDatetime === "undefined")
{
	BX.Crm.EntityEditorDatetime = BX.UI.EntityEditorDatetime;
}

if(typeof BX.Crm.EntityEditorBoolean === "undefined")
{
	BX.Crm.EntityEditorBoolean = BX.UI.EntityEditorBoolean;
}

if(typeof BX.Crm.EntityEditorList === "undefined")
{
	BX.Crm.EntityEditorList = BX.UI.EntityEditorList;
}

if(typeof BX.Crm.EntityEditorHtml === "undefined")
{
	BX.Crm.EntityEditorHtml = BX.UI.EntityEditorHtml;
}

if(typeof BX.Crm.EntityEditorMoney === "undefined")
{
	/**
	 * @extends BX.UI.EntityEditorMoney
	 * @constructor
	 */
	BX.Crm.EntityEditorMoney = function()
	{
		BX.Crm.EntityEditorMoney.superclass.constructor.apply(this);
		this._amountManualInput = null;
		this._amountClickHandler = BX.delegate(this.onAmountClick, this);
		this._changeAmountEditModeListener = null;
		this._hasRelatedProducts = false;
		this.classPrefix = 'crm-';
		this.wrapperClassName = "crm-entity-widget-content-block-field-money crm-entity-widget-participants-block";
	};
	BX.extend(BX.Crm.EntityEditorMoney, BX.UI.EntityEditorMoney);
	BX.Crm.EntityEditorMoney.prototype.doInitialize = function()
	{
		BX.Crm.EntityEditorMoney.prototype.superclass.doInitialize.apply(this);

		const ownerInfo = this.getModel().getOwnerInfo();
		if (ownerInfo)
		{
			BX.ajax.runAction("crm.api.entity.canChangeCurrency", {
				data: {
					entityId: ownerInfo.ownerID,
					entityType: ownerInfo.ownerType
				}
			}).then((response) => {
				if (response.data === false)
				{
					this._model.lockField('CURRENCY_ID');
				}
			});
		}

		this._changeAmountEditModeListener = BX.CrmNotifier.create(this);
	};
	BX.Crm.EntityEditorMoney.prototype.getAmountValue = function(defaultValue)
	{
		if(this._mode === BX.UI.EntityEditorMode.edit && this._amountValue)
		{
			return this._amountValue.value;
		}
		return this.getValue(defaultValue);
	};
	BX.Crm.EntityEditorMoney.prototype.getManualOpportunityValue = function()
	{
		if(this._mode === BX.UI.EntityEditorMode.edit && this._amountManualInput)
		{
			return this._amountManualInput.value;
		}
		return this.getManualOpportunity();
	};
	BX.Crm.EntityEditorMoney.prototype.setHasRelatedProducts = function(hasProducts)
	{
		this._hasRelatedProducts = !!hasProducts;
	};
	BX.Crm.EntityEditorMoney.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this._amountManualInput = null;

		BX.Crm.EntityEditorMoney.superclass.layout.apply(this, [options]);

		var amountManualValue = this.getManualOpportunity();
		var canUseManualValue = (amountManualValue != null);

		if (canUseManualValue && this._inputWrapper)
		{
			this._amountManualInput = BX.create("input",
				{
					attrs:
						{
							name: 'IS_MANUAL_OPPORTUNITY',
							type: "hidden",
							value: amountManualValue
						}
				}
			);

			BX.bind(this._inputWrapper, "click", this._amountClickHandler);
			this._inputWrapper.appendChild(this._amountManualInput);
		}

		if(this._mode === BX.UI.EntityEditorMode.view)
		{
			if(this._innerWrapper && BX.Type.isElementNode(this._innerWrapper.firstChild))
			{
				this._innerWrapper.firstChild.classList.add('crm-entity-widget-content-block-inner-text');
				this._innerWrapper.firstChild.classList.add('crm-entity-widget-content-block-inner-text-pay');
			}
			if(this._sumElement)
			{
				this._sumElement.classList.add("crm-entity-widget-content-block-wallet");
			}
		}
	};
	BX.Crm.EntityEditorMoney.prototype.refreshLayout = function(options)
	{
		if(!this._hasLayout)
		{
			return;
		}

		if(!this._isValidLayout)
		{
			BX.Crm.EntityEditorMoney.superclass.refreshLayout.apply(this, arguments);
			return;
		}

		if(this._mode === BX.UI.EntityEditorMode.edit && this._amountInput)
		{
			if (this.getManualOpportunity() !== 'Y')
			{
				var currencyValue = this._currencyEditor
					? this._currencyEditor.currency
					: this._model.getField(this.getCurrencyFieldName());

				if(!BX.type.isNotEmptyString(currencyValue))
				{
					currencyValue = BX.Currency.Editor.getBaseCurrencyId();
				}

				var amountFieldName = this.getAmountFieldName();
				var amountValue = this._model.getField(amountFieldName);
				amountValue = BX.Currency.Editor.trimTrailingZeros(amountValue, currencyValue);
				this._amountValue.value = amountValue;
				this._amountInput.value = BX.Currency.Editor.getFormattedValue(
					amountValue,
					currencyValue
				);
			}

			this.setInputDisabled(this._model.isFieldLocked(amountFieldName));

			if (this._amountManualInput)
			{
				this._amountManualInput.value = this.getManualOpportunity();
			}
		}
		else if(this._mode === BX.UI.EntityEditorMode.view && this._sumElement)
		{
			this._sumElement.innerHTML = this.renderMoney();
		}
	};
	BX.Crm.EntityEditorMoney.prototype.getManualOpportunity = function()
	{
		return this._model.getField("IS_MANUAL_OPPORTUNITY", null);
	};
	BX.Crm.EntityEditorMoney.prototype.isContextMenuEnabled = function()
	{
		if (BX.Crm.EntityEditorMoney.superclass.isContextMenuEnabled.apply(this, arguments))
		{
			return true;
		}
		return this.isLimitedContextMenuEnabled();
	};
	BX.Crm.EntityEditorMoney.prototype.isLimitedContextMenuEnabled = function()
	{
		return (
			this._editor.isFieldsContextMenuEnabled() &&
			!this._editor.canChangeScheme() &&
			!this.getEditor().isReadOnly() &&
			this.getManualOpportunity() === 'Y'
		);
	};
	BX.Crm.EntityEditorMoney.prototype.doPrepareContextMenuItems = function(menuItems)
	{
		if(this.getManualOpportunity() === 'Y')
		{
			var limitedMenu = this.isLimitedContextMenuEnabled();
			if (limitedMenu)
			{
				menuItems.splice(0, menuItems.length);
			}
			else
			{
				menuItems.push({delimiter: true});
			}

			menuItems.push({
				text: 	BX.Crm.EntityEditorMoney.messages.manualOpportunitySetAutomatic,
				onclick: BX.delegate(function()
				{
					if (this._mode !== BX.UI.EntityEditorMode.edit)
					{
						this.switchToSingleEditMode();
					}
					this._changeAmountEditModeListener.notify([false]);
					this.closeContextMenu();
				}, this)
			});
		}
		return menuItems;
	};
	BX.Crm.EntityEditorMoney.prototype.onAmountValueChange = function(v)
	{
		if(this._amountValue)
		{
			var oldValue = parseFloat(this._amountValue.value);
			oldValue = BX.Type.isNumber(oldValue) ? oldValue : 0.00;

			var newValue = parseFloat(v);
			newValue = BX.Type.isNumber(newValue) ? newValue : 0.00;

			if (oldValue === newValue)
			{
				return;
			}
			this._amountValue.value = v;
		}
		var amountManualValue = this.getManualOpportunity();
		var canUseManualValue = (amountManualValue != null);
		if (canUseManualValue && this._amountManualInput)
		{
			var num = (v.length ? parseFloat(v) : 0);
			num = isNaN(num) ? 0 : num;
			if (num > 0 && this._amountManualInput.value === 'N')
			{
				this._amountManualInput.value = 'Y';
			}
			if (num === 0 && this._amountManualInput.value === 'Y' && !this._hasRelatedProducts)
			{
				this._amountManualInput.value = 'N';
			}
		}
	};
	BX.Crm.EntityEditorMoney.prototype.addChangeAmountEditModeListener = function(listener)
	{
		this._changeAmountEditModeListener.addListener(listener);
	};
	BX.Crm.EntityEditorMoney.prototype.onAmountClick = function (e)
	{
		if (e.target === this._amountInput && this._model.isFieldLocked(this.getAmountFieldName()))
		{
			this._changeAmountEditModeListener.notify([true]);
		}
	};
	BX.Crm.EntityEditorMoney.prototype.processModelChange = function(params)
	{
		if(BX.prop.get(params, "originator", null) === this)
		{
			return;
		}

		if(!BX.prop.getBoolean(params, "forAll", false)
			&& BX.prop.getString(params, "name", "") !== this.getAmountFieldName()
			&& BX.prop.getString(params, "name", "") !== 'IS_MANUAL_OPPORTUNITY'
		)
		{
			return;
		}

		this.refreshLayout();

		if (BX.prop.getString(params, "name", "") === 'IS_MANUAL_OPPORTUNITY')
		{
			if (this._amountInput && !this._amountInput.isInputDisabled())
			{
				this._amountInput.focus();
			}
		}
	};
	BX.Crm.EntityEditorMoney.prototype.renderMoney = function()
	{
		var data = this._schemeElement.getData();
		var formattedWithCurrency = this._model.getField(BX.prop.getString(data, "formattedWithCurrency"), "");
		var formatted = this._model.getField(BX.prop.getString(data, "formatted"), "");
		var result = BX.Currency.Editor.trimTrailingZeros(formatted, this._selectedCurrencyValue);

		return formattedWithCurrency.replace(
			formatted,
			"<span class=\"crm-entity-widget-content-block-colums-right\">" + result + "</span>"
		);
	};

	BX.Crm.EntityEditorMoney.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorMoney();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorMoneyPay === "undefined")
{
	BX.Crm.EntityEditorMoneyPay = function()
	{
		BX.Crm.EntityEditorMoneyPay.superclass.constructor.apply(this);
		this._payButton = null;
		this._isPayButtonVisible = null;
		this._paymentDocumentsControl = null;
	};

	BX.Crm.EntityEditorMoneyPay.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorMoneyPay();
		self.initialize(id, settings);
		return self;
	};

	BX.extend(BX.Crm.EntityEditorMoneyPay, BX.Crm.EntityEditorMoney);

	BX.Crm.EntityEditorMoneyPay.prototype.doInitialize = function()
	{
		BX.Crm.EntityEditorMoneyPay.superclass.doInitialize.apply(this);

		this._isPayButtonVisible = BX.prop.getBoolean(this._schemeElement._options, "isPayButtonVisible", true);
		this._isPayButtonControlVisible = (this._model.getField("IS_PAY_BUTTON_CONTROL_VISIBLE", 'Y') === 'Y');

		var ownerTypeId = BX.prop.getInteger(this.getModel()._settings, "entityTypeId", 0);
		var isCopyMode = this._model.getField('IS_COPY_MODE', false);

		var isShowPaymentDocuments = this._schemeElement.getDataBooleanParam("isShowPaymentDocuments", false);
		if (!isCopyMode && isShowPaymentDocuments)
		{
			var paymentDocumentsOptions = {
				IS_USED_INVENTORY_MANAGEMENT: this._model.getField('IS_USED_INVENTORY_MANAGEMENT', false),
				SALES_ORDERS_RIGHTS: this._model.getField('SALES_ORDERS_RIGHTS', {}),
				IS_INVENTORY_MANAGEMENT_RESTRICTED: this._model.getField('IS_INVENTORY_MANAGEMENT_RESTRICTED', false),
				OWNER_TYPE_ID: ownerTypeId,
				OWNER_ID: this._model.getField('ID') ? parseInt(this._model.getField('ID')) : 0,
				CONTEXT: this.getModel().getOwnerInfo().ownerType.toLowerCase(),
				IS_DELIVERY_AVAILABLE: this._schemeElement.getDataBooleanParam('isDeliveryAvailable', false),
				PARENT_CONTEXT: this,
				PHRASES: this._schemeElement.getDataObjectParam('paymentDocumentsPhrases', {}),
				IS_WITH_ORDERS_MODE: this._schemeElement.getDataBooleanParam('isWithOrdersMode', false)
			};
			this._paymentDocumentsControl = new BX.Crm.EntityEditorPaymentDocuments(paymentDocumentsOptions);

			if (this._paymentDocumentsControl.hasContent())
			{
				this._model.lockField('CURRENCY_ID');
			}

			BX.Event.EventEmitter.subscribe(
				'PaymentDocuments.EntityEditor:changeDocuments',
				this.lockCurrencyByPaymentDocuments.bind(this)
			);
		}
	};

	BX.Crm.EntityEditorMoneyPay.prototype.lockCurrencyByPaymentDocuments = function()
	{
		if (!this._paymentDocumentsControl)
		{
			return;
		}

		if (this._paymentDocumentsControl.hasContent())
		{
			this._model.lockField('CURRENCY_ID');
		}
		else
		{
			this._model.unlockField('CURRENCY_ID');
		}

		if (BX.PULL)
		{
			BX.PULL.subscribe({
				moduleId: 'crm',
				command: 'onOrderBound',
				callback: function (params, extra, command)
				{
					if (params.FIELDS.PRODUCT_LIST)
					{
						this.reloadProductList(params.FIELDS.PRODUCT_LIST);
					}
				}.bind(this)
			});
		}
	};

	BX.Crm.EntityEditorMoneyPay.prototype.renderPayButton = function()
	{
		var a = BX.create("button",
			{
				props: { className: "crm-entity-widget-content-block-inner-pay-button ui-btn ui-btn-sm ui-btn-primary" },
				text : this.getMessage('payButtonLabel'),
				attrs : {type: 'button'}
			}
		);

		BX.bind(a, 'click', BX.delegate(function()
		{
			var orderId = this.getLatestOrderId();
			this.startSalescenterApplication(orderId);
		}, this));

		BX.bind(a, "mousedown", function(event)
		{
			BX.PreventDefault(event);
		});

		a.style.display = this._isPayButtonVisible ? '' : 'none';

		return a;
	};

	BX.Crm.EntityEditorMoneyPay.prototype.layout = function(options)
	{
		BX.Crm.EntityEditorMoneyPay.superclass.layout.apply(this, arguments);

		if(
			this._mode === BX.UI.EntityEditorMode.view
			&& this.isNeedToDisplay()
			&& !this.getEditor().isEmbedded()
		)
		{
			this._payButton = this.renderPayButton();
			if (BX.Type.isElementNode(this._innerWrapper.firstChild))
			{
				this._innerWrapper.firstChild.appendChild(this._payButton);
			}
			else
			{
				BX.Dom.clean(this._innerWrapper);
				this._innerWrapper.appendChild(this._payButton);
			}

			if (this._paymentDocumentsControl)
			{
				this._wrapper.appendChild(this._paymentDocumentsControl.render());
				this._paymentDocumentsControl.reloadModel();
			}
		}
	};

	BX.Crm.EntityEditorMoneyPay.prototype.startSalescenterApplication = function(orderId, options)
	{
		if (orderId === undefined)
		{
			orderId = 0;
		}

		if (options === undefined)
		{
			var entityTypeId = BX.prop.getInteger(this.getModel()._settings, 'entityTypeId', 0);
			var ownerInfo = this.getModel().getOwnerInfo();
			var mode = this.getModel().getField('RECEIVE_PAYMENT_MODE');

			options = {
				disableSendButton: this._schemeElement.getDataStringParam('disableSendButton', ''),
				context: 'deal',
				templateMode: 'create',
				mode: entityTypeId === BX.CrmEntityType.enumeration.deal ? mode : 'payment',
				analyticsLabel: 'salescenterClickButtonPay',
				ownerTypeId: entityTypeId,
				ownerId: ownerInfo.ownerID,
				orderId: orderId,
			};
		}

		BX.loadExt('salescenter.manager').then(function() {
			BX.Salescenter.Manager.openApplication(options).then(function(result) {
				if (result)
				{
					if (result.get('deal') || result.get('order') || result.get('entity'))
					{
						this._editor.reload();
					}

					var entityTypeId = this.getModel().getEntityTypeId(), entity = null;
					if (result.get('entity'))
					{
						entity = result.get('entity');
					}
					else if (entityTypeId === BX.CrmEntityType.enumeration.deal)
					{
						entity = result.get('deal');
					}

					if (entity && entity.PRODUCT_LIST)
					{
						this.reloadProductList(entity.PRODUCT_LIST);
					}

					var order = result.get('order');
					if (order && order.id && BX.CrmActivityDelivery)
					{
						var deliveryActivity = BX.CrmActivityDelivery.getInstance();
						deliveryActivity.rememberCurrentOrder(order.id);
					}
				}
			}.bind(this));
		}.bind(this));
	};

	BX.Crm.EntityEditorMoneyPay.prototype.reloadProductList = function(productList)
	{
		this._editor.tapController('PRODUCT_ROW_PROXY', function(controller) {
			if (controller._externalEditor)
			{
				controller._externalEditor.reinitialize(productList);
			}
		});

		this._editor.tapController('PRODUCT_LIST', function(controller) {
			controller.reinitializeProductList();
		});
	}

	BX.Crm.EntityEditorMoneyPay.prototype.doPrepareContextMenuItems = function(menuItems)
	{
		var self = this;

		BX.Crm.EntityEditorMoneyPay.superclass.doPrepareContextMenuItems.apply(this, arguments);

		if (!self._isPayButtonControlVisible)
		{
			return;
		}

		menuItems.unshift({
			text: self._isPayButtonVisible ? this.getMessage('hidePayButton') : this.getMessage('showPayButton'),
			onclick: function()
			{
				self._isPayButtonVisible = !self._isPayButtonVisible;
				self._schemeElement._options.isPayButtonVisible = self._isPayButtonVisible;
				self._payButton.style.display = (self._isPayButtonVisible) ? '' : 'none';
				self.markSchemeAsChanged();
				self.saveScheme();
				self.closeContextMenu();
			}
		});
	};
	BX.Crm.EntityEditorMoneyPay.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorMoneyPay.messages;
		return m.hasOwnProperty(name) ? m[name] : BX.Crm.EntityEditorMoneyPay.superclass.getMessage.apply(this, arguments);
	};

	BX.Crm.EntityEditorMoneyPay.prototype.getLatestOrderId = function()
	{
		var orderList = this._model.getField('ORDER_LIST', []);
		var orderId = 0;

		if (orderList.length && orderList.length > 0)
		{
			orderList.map(function(item){
				orderId = Math.max(orderId, parseInt(item.ORDER_ID));
			});
		}

		return orderId;
	};

	BX.Crm.EntityEditorMoneyPay.prototype.getPaymentDocumentsControl = function()
	{
		return this._paymentDocumentsControl;
	};
}

if(typeof BX.Crm.EntityEditorUser === "undefined")
{
	BX.Crm.EntityEditorUser = function()
	{
		BX.Crm.EntityEditorUser.superclass.constructor.apply(this);
		this._input = null;
		this._editButton = null;
		this._photoElement = null;
		this._nameElement = null;
		this._positionElement = null;
		this._userSelector = null;
		this._selectedData = {};
		this._editButtonClickHandler = BX.delegate(this.onEditBtnClick, this);
	};
	BX.extend(BX.Crm.EntityEditorUser, BX.UI.EntityEditorField);
	BX.Crm.EntityEditorUser.prototype.isSingleEditEnabled = function()
	{
		return true;
	};
	BX.Crm.EntityEditorUser.prototype.getRelatedDataKeys = function()
	{
		return (
			[
				this.getDataKey(),
				this._schemeElement.getDataStringParam("formated", ""),
				this._schemeElement.getDataStringParam("position", ""),
				this._schemeElement.getDataStringParam("showUrl", ""),
				this._schemeElement.getDataStringParam("photoUrl", "")
			]
		);
	};
	BX.Crm.EntityEditorUser.prototype.hasContentToDisplay = function()
	{
		return true;
	};
	BX.Crm.EntityEditorUser.prototype.layout = function(options)
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

		var name = this._schemeElement.getName();
		var title = this._schemeElement.getTitle();
		var value = this._model.getField(name);

		var formattedName = this._model.getSchemeField(this._schemeElement, "formated", "");
		var position = this._model.getSchemeField(this._schemeElement, "position", "");
		var showUrl = this._model.getSchemeField(this._schemeElement, "showUrl", "", "");
		var photoUrl = this._model.getSchemeField(this._schemeElement, "photoUrl", "");

		this._photoElement = BX.create("a",
			{
				props: { className: "crm-widget-employee-avatar-container", target: "_blank" },
				style:
					{
						backgroundImage: BX.type.isNotEmptyString(photoUrl) ? "url('" + encodeURI(photoUrl) + "')" : "",
						backgroundSize: BX.type.isNotEmptyString(photoUrl) ? "30px" : ""
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

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		this._wrapper.appendChild(this.createTitleNode(title));

		var userElement = BX.create("div", { props: { className: "crm-widget-employee-container" } });
		this._editButton = null;
		this._input = null;

		if(this._mode === BX.UI.EntityEditorMode.edit || (this.isEditInViewEnabled() && !this.isReadOnly()))
		{
			this._input = BX.create("input", { attrs: { name: name, type: "hidden", value: value } });
			this._wrapper.appendChild(this._input);

			this._editButton = BX.create("span", { props: { className: "crm-widget-employee-change" }, text: this.getMessage("change") });
			BX.bind(this._editButton, "click", this._editButtonClickHandler);
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

		this._wrapper.appendChild(
			BX.create("div",
				{ props: { className: "crm-entity-widget-content-block-inner" }, children: [ userElement ] }
			)
		);

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
	BX.Crm.EntityEditorUser.prototype.doRegisterLayout = function()
	{
		if(this.isInEditMode()
			&& this.checkModeOption(BX.UI.EntityEditorModeOptions.individual)
		)
		{
			window.setTimeout(BX.delegate(this.openSelector, this), 0);
		}
	};
	BX.Crm.EntityEditorUser.prototype.doClearLayout = function(options)
	{
		this._input = null;
		this._editButton = null;
		this._photoElement = null;
		this._nameElement = null;
		this._positionElement = null;
	};
	BX.Crm.EntityEditorUser.prototype.onEditBtnClick = function(e)
	{
		//If any other control has changed try to switch to edit mode.
		if(this._mode === BX.UI.EntityEditorMode.view && this.isEditInViewEnabled() && this.getEditor().isChanged())
		{
			this.switchToSingleEditMode();
		}
		else
		{
			this.openSelector();
		}
	};
	BX.Crm.EntityEditorUser.prototype.openSelector = function()
	{
		if(!this._userSelector)
		{
			this._userSelector = BX.UI.EntityEditorUserSelector.create(
				this._id,
				{ callback: BX.delegate(this.processItemSelect, this) }
			);
		}

		this._userSelector.open(this._editButton);
	};
	BX.Crm.EntityEditorUser.prototype.processItemSelect = function(selector, item)
	{
		var isViewMode = this._mode === BX.UI.EntityEditorMode.view;
		var editInView = this.isEditInViewEnabled();
		if(isViewMode && !editInView)
		{
			return;
		}

		this._selectedData =
			{
				id: BX.prop.getInteger(item, "entityId", 0),
				photoUrl: BX.prop.getString(item, "avatar", ""),
				formattedNameHtml: BX.prop.getString(item, "name", ""),
				positionHtml: BX.prop.getString(item, "desc", "")
			};

		this._input.value = this._selectedData["id"];
		this._photoElement.style.backgroundImage = this._selectedData["photoUrl"] !== ""
			? "url('" + encodeURI(this._selectedData["photoUrl"]) + "')" : "";
		this._photoElement.style.backgroundSize = this._selectedData["photoUrl"] !== ""
			? "30px" : "";

		this._nameElement.innerHTML = this._selectedData["formattedNameHtml"];
		this._positionElement.innerHTML = this._selectedData["positionHtml"];
		this._userSelector.close();

		if(!isViewMode)
		{
			this.markAsChanged();
		}
		else
		{
			this._editor.saveControl(this);
		}
	};
	BX.Crm.EntityEditorUser.prototype.save = function()
	{
		var data = this._schemeElement.getData();
		if(this._selectedData["id"] > 0)
		{
			var itemId = this._selectedData["id"];

			this._model.setField(
				BX.prop.getString(data, "formated"),
				BX.util.htmlspecialcharsback(this._selectedData["formattedNameHtml"])
			);

			this._model.setField(
				BX.prop.getString(data, "position"),
				this._selectedData["positionHtml"] !== "&nbsp;"
					? BX.util.htmlspecialcharsback(this._selectedData["positionHtml"]) : ""
			);

			this._model.setField(
				BX.prop.getString(data, "showUrl"),
				BX.prop.getString(data, "pathToProfile").replace(/#user_id#/ig, itemId)
			);

			this._model.setField(
				BX.prop.getString(data, "photoUrl"),
				this._selectedData["photoUrl"]
			);

			this._model.setField(this.getName(), itemId);
		}
	};
	BX.Crm.EntityEditorUser.prototype.processModelChange = function(params)
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
	BX.Crm.EntityEditorUser.prototype.getRuntimeValue = function()
	{
		if (this._mode === BX.UI.EntityEditorMode.edit && this._selectedData["id"] > 0)
		{
			return this._selectedData["id"];
		}
		return "";
	};
	BX.Crm.EntityEditorUser.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorUser.messages;
		return (m.hasOwnProperty(name)
				? m[name]
				: BX.Crm.EntityEditorUser.superclass.getMessage.apply(this, arguments)
		);
	};

	if(typeof(BX.Crm.EntityEditorUser.messages) === "undefined")
	{
		BX.Crm.EntityEditorUser.messages = {};
	}
	BX.Crm.EntityEditorUser.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorUser();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorAddress === "undefined")
{
	BX.Crm.EntityEditorAddress = function()
	{
		BX.Crm.EntityEditorAddress.superclass.constructor.apply(this);
		this._innerWrapper = null;
	};
	BX.extend(BX.Crm.EntityEditorAddress, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorAddress.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.UI.EntityEditorModeSwitchType.common;
		if(mode === BX.UI.EntityEditorMode.edit)
		{
			result |= BX.UI.EntityEditorModeSwitchType.button|BX.UI.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorAddress.prototype.getContentWrapper = function()
	{
		return this._innerWrapper;
	};
	BX.Crm.EntityEditorAddress.prototype.hasContentToDisplay = function()
	{
		return(this._mode === BX.UI.EntityEditorMode.edit || this.getViewHtml() !== "");
	};
	BX.Crm.EntityEditorAddress.prototype.getViewHtml = function()
	{
		var viewFieldName = this._schemeElement.getDataStringParam("view", "");
		if(viewFieldName === "")
		{
			viewFieldName = this._schemeElement.getName() + "_HML";
		}
		return this._model.getStringField(viewFieldName, "");
	};
	BX.Crm.EntityEditorAddress.prototype.layout = function(options)
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

		var name = this._schemeElement.getName();
		var title = this.getTitle();
		var fields = this._schemeElement.getDataObjectParam("fields", {});
		var labels = this._schemeElement.getDataObjectParam("labels", {});
		this._innerWrapper = null;

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		this._wrapper.appendChild(this.createTitleNode(title));
		if(this._mode === BX.UI.EntityEditorMode.edit)
		{

			var fieldsContainer = BX.create("div", { attrs: { className: "crm-entity-widget-content-block-inner-address" } } );

			this._innerWrapper = BX.create("div",
				{
					attrs: { className: "crm-entity-widget-content-block-inner" },
					children:
						[
							BX.create("div",
								{
									props: {className: "crm-entity-widget-content-block-field-container"},
									children: [ fieldsContainer ]
								}
							)
						]
				}
			);

			for(var key in fields)
			{
				if(!fields.hasOwnProperty(key))
				{
					return;
				}

				var field = fields[key];
				var label = BX.prop.getString(labels, key, key);
				this.layoutField(key, field, label, fieldsContainer);
			}

			BX.bindDelegate(
				fieldsContainer,
				"bxchange",
				{ tag: [ "input", "textarea" ] },
				this._changeHandler
			);
		}
		else
		{
			if(this.hasContentToDisplay())
			{
				this._innerWrapper = BX.create("div",
					{
						attrs: { className: "crm-entity-widget-content-block-inner" },
						children:
							[
								BX.create("div",
									{
										attrs: { className: "crm-entity-widget-content-block-inner-text" },
										html: this.getViewHtml()
									}
								)
							]
					}
				);
			}
			else
			{
				this._innerWrapper = BX.create(
					"div",
					{
						attrs: { className: "crm-entity-widget-content-block-inner" },
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
		this._hasLayout = true
	};
	BX.Crm.EntityEditorAddress.prototype.layoutField = function(name, field, label, container)
	{
		var alias = BX.prop.getString(field, "NAME", name);
		var value = this._model.getStringField(alias, "");

		container.appendChild(
			BX.create(
				"div",
				{
					props: { className: "crm-entity-widget-content-block-title" },
					children: [
						BX.create(
							"span",
							{
								attrs: { className: "crm-entity-widget-content-block-title-text" },
								text: label
							}
						)
					]
				}
			)
		);

		if(BX.prop.getBoolean(field, "IS_MULTILINE", false))
		{
			container.appendChild(
				BX.create(
					"textarea",
					{
						props: { className: "crm-entity-widget-content-input", name: alias, value: value }
					}
				)
			);
		}
		else
		{
			container.appendChild(
				BX.create(
					"input",
					{
						props: { className: "crm-entity-widget-content-input", name: alias, type: "text", value: value }
					}
				)
			);
		}
	};
	BX.Crm.EntityEditorAddress.prototype.doClearLayout = function(options)
	{
		this._innerWrapper = null;
	};
	BX.Crm.EntityEditorAddress.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorAddress();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorMultifieldItem === "undefined")
{
	BX.Crm.EntityEditorMultifieldItem = function()
	{
		this._id = "";
		this._settings = {};
		this._parent = null;
		this._editor = null;

		this._mode = BX.UI.EntityEditorMode.view;
		this._data = null;
		this._typeId = "";
		this._valueTypeItems = null;

		this._container = null;
		this._wrapper = null;
		this._valueInput = null;
		this._valueTypeInput = null;
		this._valueTypeSelector = null;

		this._deleteButton = null;
		this._deleteButtonHandler = BX.delegate(this.onDeleteButtonClick, this);

		this._isJunked = false;

		this._hasLayout = false;
	};
	BX.Crm.EntityEditorMultifieldItem.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};

				this._parent = BX.prop.get(this._settings, "parent", null);
				this._editor = this._parent.getEditor();

				this._mode = BX.prop.getInteger(this._settings, "mode", BX.UI.EntityEditorMode.view);

				this._typeId = BX.prop.getString(this._settings, "typeId", "");
				this._data = BX.prop.getObject(this._settings, "data", {});
				this._valueTypeItems = BX.prop.getArray(this._settings, "valueTypeItems", []);

				this._container = BX.prop.getElementNode(this._settings, "container", null);
			},
			getId: function()
			{
				return this._id;
			},
			isEmpty: function()
			{
				return BX.util.trim(this.getValue()) === "";
			},
			getTypeId: function()
			{
				return this._typeId;
			},
			getValue: function()
			{
				return BX.prop.getString(this._data, "VALUE", "");
			},
			getValueId: function()
			{
				return BX.prop.getString(this._data, "ID", "");
			},
			getValueTypeId: function()
			{
				var result = BX.prop.getString(this._data, "VALUE_TYPE", "");
				return result !== "" ? result : this.getDefaultValueTypeId();
			},
			getDefaultValueTypeId: function()
			{
				return this._valueTypeItems.length > 0
					? BX.prop.getString(this._valueTypeItems[0], "VALUE") : "";
			},
			getViewData: function()
			{
				return BX.prop.getObject(this._data, "VIEW_DATA", {});
			},
			getCountryCode: function()
			{
				var extraData = BX.prop.getObject(this._data, "VALUE_EXTRA", {});

				return BX.prop.getString(extraData, "COUNTRY_CODE", "");
			},
			resolveValueTypeName: function(valueTypeId)
			{
				if(valueTypeId === "")
				{
					return "";
				}

				for(var i = 0, length = this._valueTypeItems.length; i < length; i++)
				{
					var item = this._valueTypeItems[i];
					if(valueTypeId === BX.prop.getString(item, "VALUE", ""))
					{
						return BX.prop.getString(item, "NAME", valueTypeId);
					}
				}
				return valueTypeId;
			},
			prepareControlName: function(name)
			{
				return this.getTypeId() + "[" + this.getValueId() + "]" + "[" + name + "]";
			},
			getMode: function()
			{
				return this._mode;
			},
			setMode: function(mode)
			{
				this._mode = mode;
			},
			getContainer: function()
			{
				return this._container;
			},
			setContainer: function(container)
			{
				this._container = container;
				if(this._hasLayout)
				{
					this.clearLayout();
				}
			},
			focus: function()
			{
				if(this._valueInput)
				{
					BX.focus(this._valueInput);
					BX.Crm.EditorTextHelper.getCurrent().selectAll(this._valueInput);
				}
			},
			layout: function()
			{
				if(this._hasLayout)
				{
					return;
				}

				this._valueInput = null;
				this._valueTypeInput = null;
				this._valueTypeSelector = null;
				this._valueCountryCode = null;
				this._deleteButton = null;
				var valueTypeId = this.getValueTypeId();
				var value = this.getValue();

				this._wrapper = BX.create("div");
				this._container.appendChild(this._wrapper);

				if(this._mode === BX.UI.EntityEditorMode.edit)
				{
					BX.addClass(this._wrapper, "crm-entity-widget-content-block-field-container crm-entity-widget-content-block-field-container-double");
					this._valueInput = BX.create(
						"input",
						{
							attrs:
								{
									className: "crm-entity-widget-content-input",
									name: this.prepareControlName("VALUE"),
									type: "text",
									value: value
								}
						}
					);
					BX.bind(this._valueInput, "input", BX.delegate(this.onValueChange, this));

					this._valueTypeInput = BX.create(
						"input",
						{
							attrs:
								{
									name: this.prepareControlName("VALUE_TYPE"),
									type: "hidden",
									value: valueTypeId
								}
						}
					);

					this._wrapper.appendChild(BX.create("div", {
						props: {className: "crm-entity-widget-content-input-wrapper"},
						children: [
							this._valueInput,
							this._valueTypeInput,
						]
					}));

					this._valueTypeSelector = BX.create(
						"div",
						{
							props: { className: "crm-entity-widget-content-select" },
							text: this.resolveValueTypeName(valueTypeId),
							events: { click: BX.delegate(this.onValueTypeSelectorClick, this) }
						}
					);

					this._wrapper.appendChild(
						BX.create(
							"div",
							{
								attrs: { className: "crm-entity-widget-content-block-select" },
								children: [ this._valueTypeSelector ]
							}
						)
					);

					this._deleteButton = BX.create(
						"div",
						{ attrs: { className: "crm-entity-widget-content-remove-block" } }
					);
					this._wrapper.appendChild(this._deleteButton);
					BX.bind(this._deleteButton, "click", this._deleteButtonHandler);

					if(this._editor.isDuplicateControlEnabled())
					{
						var dupControlConfig = this._parent.getDuplicateControlConfig();
						if(dupControlConfig)
						{
							if(!BX.type.isPlainObject(dupControlConfig["field"]))
							{
								dupControlConfig["field"] = {};
							}
							dupControlConfig["field"]["id"] = this.getValueId();
							dupControlConfig["field"]["element"] = this._valueInput;
							this._editor.getDuplicateManager().registerField(dupControlConfig);
						}
					}
				}
				else if(this._mode === BX.UI.EntityEditorMode.view && !this.isEmpty())
				{
					BX.addClass(this._wrapper, "crm-entity-widget-content-block-mutlifield");

					var viewData = this.getViewData();
					var html = BX.prop.getString(viewData, "value", "");
					if(html === "")
					{
						html = BX.util.htmlspecialchars(value);
					}

					this._wrapper.appendChild(
						BX.create(
							"span",
							{
								attrs: { className: "crm-entity-widget-content-block-mutlifield-type" },
								text: this.resolveValueTypeName(valueTypeId)
							}
						)
					);

					var contentWrapper = BX.create(
						"span",
						{
							attrs: { className: "crm-entity-widget-content-block-mutlifield-value" },
							html: html
						}
					);
					this._wrapper.appendChild(contentWrapper);

					if(this._parent.getMultifieldType() === "EMAIL")
					{
						var emailLink = contentWrapper.querySelector("a.crm-entity-email");
						if(emailLink)
						{
							BX.bind(emailLink, "click", BX.delegate(this.onEmailClick, this));
						}
					}
				}

				this._hasLayout = true;
			},
			clearLayout: function()
			{
				if(!this._hasLayout)
				{
					return;
				}

				if(this._editor.isDuplicateControlEnabled())
				{
					var dupControlConfig = this._parent.getDuplicateControlConfig();
					if(dupControlConfig)
					{
						if(!BX.type.isPlainObject(dupControlConfig["field"]))
						{
							dupControlConfig["field"] = {};
						}
						dupControlConfig["field"]["id"] = this.getValueId();
						this._editor.getDuplicateManager().unregisterField(dupControlConfig);
					}
				}

				this._wrapper = BX.remove(this._wrapper);
				this._hasLayout = false;
			},
			adjust: function()
			{
				if(this._hasLayout)
				{
					this._wrapper.style.display = this._isJunked ? "none" : "";
				}
			},
			onValueChange: function(e)
			{
				this._parent.processItemChange(this);
			},
			onValueTypeSelectorClick: function(e)
			{
				var menu = [];
				for(var i = 0, length = this._valueTypeItems.length; i < length; i++)
				{
					var item = this._valueTypeItems[i];
					menu.push(
						{
							text: item["NAME"],
							value: item["VALUE"],
							onclick: BX.delegate( this.onValueTypeSelect, this)
						}
					);
				}

				BX.addClass(this._valueTypeSelector, "active");

				BX.PopupMenu.destroy(this._id);
				BX.PopupMenu.show(
					this._id,
					this._valueTypeSelector,
					menu,
					{
						angle: false, width: this._valueTypeSelector.offsetWidth + 'px',
						events: { onPopupClose: BX.delegate(this.onValueTypeMenuClose, this) }
					}
				);

				BX.PopupMenu.currentItem.popupWindow.setWidth(BX.pos(this._valueTypeSelector)["width"]);
			},
			onValueTypeMenuClose: function(e)
			{
				BX.removeClass(this._valueTypeSelector, "active");
			},
			onValueTypeSelect: function(e, item)
			{
				BX.removeClass(this._valueTypeSelector, "active");

				this._valueTypeInput.value = item.value;
				this._valueTypeSelector.innerHTML = BX.util.htmlspecialchars(item.text);

				this._parent.processItemChange(this);
				BX.PopupMenu.destroy(this._id);
			},
			isJunked: function()
			{
				return this._isJunked;
			},
			markAsJunked: function(junked)
			{
				junked = !!junked;
				if(this._isJunked !== junked)
				{
					this._isJunked = junked;
					if(this._isJunked)
					{
						this._valueInput.value = "";
					}
					this.adjust();
				}
			},
			onEmailClick: function(e)
			{
				if(BX.CrmActivityEditor)
				{
					var ownerInfo = this._editor.getOwnerInfo();
					var settings =
						{
							ownerType: ownerInfo["ownerType"],
							ownerID: ownerInfo["ownerID"],
							communications:
								[
									{
										entityType: ownerInfo["ownerType"],
										entityId: ownerInfo["ownerID"],
										type: "EMAIL",
										value: this.getValue()
									}
								]
						};
					BX.CrmActivityEditor.addEmail(settings);
				}
				return BX.PreventDefault(e);
			},
			onDeleteButtonClick: function(e)
			{
				this._parent.processItemDeletion(this);
			}
		};
	BX.Crm.EntityEditorMultifieldItem.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorMultifieldItem();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorMultifieldItemPhone ==="undefined")
{
	BX.Crm.EntityEditorMultifieldItemPhone = function()
	{
		BX.Crm.EntityEditorMultifieldItemPhone.superclass.constructor.apply(this);

		this._maskedPhone = null;
		this._maskedValueInput = null;
		this._countryFlagNode = null;
		this._valueCountryCode = null;
	};

	BX.extend(BX.Crm.EntityEditorMultifieldItemPhone, BX.Crm.EntityEditorMultifieldItem);

	BX.Crm.EntityEditorMultifieldItemPhone.prototype.layout = function ()
	{
		var self = this;
		if (this._hasLayout)
		{
			return;
		}

		this._valueInput = null;
		this._valueTypeInput = null;
		this._valueTypeSelector = null;
		this._valueCountryCode = null;

		var valueTypeId = this.getValueTypeId();
		var value = this.getValue();
		var countryCode = this.getCountryCode();

		this._wrapper = BX.create("div");
		this._container.appendChild(this._wrapper);

		if (this._mode === BX.UI.EntityEditorMode.edit)
		{
			BX.addClass(this._wrapper, "crm-entity-widget-content-block-field-container crm-entity-widget-content-block-field-container-double");

			this._valueInput = BX.create(
				"input",
				{
					attrs: {
						name: this.prepareControlName("VALUE"),
						type: "hidden",
						value: value
					}
				}
			);
			this._wrapper.appendChild(this._valueInput);

			this._valueCountryCode = BX.create(
				"input",
				{
					attrs: {
						name: this.prepareControlName("VALUE_COUNTRY_CODE"),
						type: "hidden",
						value: countryCode
					}
				}
			);
			this._wrapper.appendChild(this._valueCountryCode);

			this._wrapper.appendChild(BX.create("div", {
				props: {className: "crm-entity-widget-content-input-wrapper"},
				children: [
					this._countryFlagNode = BX.create("span", {
						props: {className: "crm-entity-widget-content-country-flag"}
					}),
					this._maskedValueInput = BX.create(
						"input",
						{
							attrs: {
								className: "crm-entity-widget-content-input crm-entity-widget-content-input-phone",
								type: "text",
								value: value
							}
						}
					)
				]
			}));

			var defaultCountry = null;
			if (
				this._parent
				&& this._parent.getSchemeElement()
				&& BX.Type.isPlainObject(this._parent.getSchemeElement()._options)
				&& BX.Type.isStringFilled(this._parent.getSchemeElement()._options.defaultCountry)
			)
			{
				defaultCountry = this._parent.getSchemeElement()._options.defaultCountry
			}

			this._maskedPhone = new BX.Crm.PhoneNumberInput({
				node: this._maskedValueInput,
				flagNode: this._countryFlagNode,
				isSelectionIndicatorEnabled: true,
				searchDialogContextCode: 'CRM_ENTITY_EDITOR_PHONE',
				userDefaultCountry: defaultCountry,
				savedCountryCode: countryCode,
				onChange: function(event)
				{
					if (self._valueInput.value !== event.value)
					{
						self._valueInput.value = event.value;
						if (BX.Crm.PhoneNumberInput.isCountryCodeOnly(self._valueInput.value, event.countryCode))
						{
							self._valueInput.value = '';
						}

						self.onValueChange();
					}
				},
				onCountryChange: function(event)
				{
					self._valueCountryCode.value = event.country;
				}
			});

			this._valueTypeInput = BX.create(
				"input",
				{
					attrs: {
						name: this.prepareControlName("VALUE_TYPE"),
						type: "hidden",
						value: valueTypeId
					}
				}
			);
			this._wrapper.appendChild(this._valueTypeInput);

			this._valueTypeSelector = BX.create(
				"div",
				{
					props: {className: "crm-entity-widget-content-select"},
					text: this.resolveValueTypeName(valueTypeId),
					events: {click: BX.delegate(this.onValueTypeSelectorClick, this)}
				}
			);

			this._wrapper.appendChild(
				BX.create(
					"div",
					{
						attrs: {className: "crm-entity-widget-content-block-select"},
						children: [this._valueTypeSelector]
					}
				)
			);

			this._deleteButton = BX.create(
				"div",
				{ attrs: { className: "crm-entity-widget-content-remove-block" } }
			);
			this._wrapper.appendChild(this._deleteButton);
			BX.bind(this._deleteButton, "click", this._deleteButtonHandler);

			if (this._editor.isDuplicateControlEnabled())
			{
				var dupControlConfig = this._parent.getDuplicateControlConfig();
				if (dupControlConfig)
				{
					if (!BX.type.isPlainObject(dupControlConfig["field"]))
					{
						dupControlConfig["field"] = {};
					}
					dupControlConfig["field"]["id"] = this.getValueId();
					dupControlConfig["field"]["element"] = this._maskedValueInput;
					this._editor.getDuplicateManager().registerField(dupControlConfig);
				}
			}
		}
		else if (this._mode === BX.UI.EntityEditorMode.view && !this.isEmpty())
		{
			BX.addClass(this._wrapper, "crm-entity-widget-content-block-mutlifield");

			var viewData = this.getViewData();
			var html = BX.prop.getString(viewData, "value", "");
			if(html === "")
			{
				html = BX.util.htmlspecialchars(value);
			}

			this._wrapper.appendChild(
				BX.create(
					"span",
					{
						attrs: {className: "crm-entity-widget-content-block-mutlifield-type"},
						text: this.resolveValueTypeName(valueTypeId)
					}
				)
			);

			this._wrapper.appendChild(
				BX.create(
					"span",
					{
						attrs: {className: "crm-entity-widget-content-block-mutlifield-value"},
						html: html
					}
				)
			);
		}

		this._hasLayout = true;
	};
	BX.Crm.EntityEditorMultifieldItemPhone.prototype.focus = function()
	{
		if(this._maskedValueInput)
		{
			BX.focus(this._maskedValueInput);
			BX.Crm.EditorTextHelper.getCurrent().selectAll(this._maskedValueInput);
		}
	};
	BX.Crm.EntityEditorMultifieldItemPhone.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorMultifieldItemPhone();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorMultifield === "undefined")
{
	BX.Crm.EntityEditorMultifield = function()
	{
		BX.Crm.EntityEditorMultifield.superclass.constructor.apply(this);
		this._items = null;
		this._itemWrapper = null;
	};
	BX.extend(BX.Crm.EntityEditorMultifield, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorMultifield.prototype.doInitialize = function()
	{
		this.initializeItems();
	};
	BX.Crm.EntityEditorMultifield.prototype.initializeItems = function()
	{
		var name = this.getName();
		var data = this._model.getField(name, []);
		if(data.length === 0)
		{
			data.push({ "ID": "n0" });
		}

		for(var i = 0, length = data.length; i < length; i++)
		{
			this.addItem(data[i]);
		}
	};
	BX.Crm.EntityEditorMultifield.prototype.findItemIndex = function(item)
	{
		if(!this._items)
		{
			return -1;
		}

		for(var i = 0, length = this._items.length; i < length; i++)
		{
			if(this._items[i] === item)
			{
				return i;
			}
		}

		return -1;
	};
	BX.Crm.EntityEditorMultifield.prototype.resetItems = function()
	{
		if(this._hasLayout)
		{
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				this._items[i].clearLayout();
			}
		}

		this._items = [];
	};
	BX.Crm.EntityEditorMultifield.prototype.deleteItem = function(item)
	{
		if(!this._items)
		{
			return;
		}

		var index = this.findItemIndex(item);
		if(index >= 0)
		{
			this._items[index].markAsJunked(true);
		}
	};
	BX.Crm.EntityEditorMultifield.prototype.reset = function()
	{
		this.resetItems();
		this.initializeItems();
	};
	BX.Crm.EntityEditorMultifield.prototype.hasContentToDisplay = function()
	{
		if(this._mode === BX.UI.EntityEditorMode.edit)
		{
			return true;
		}

		var length = this._items.length;
		if(length === 0)
		{
			return false;
		}

		for(var i = 0; i < length; i++)
		{
			if(!this._items[i].isEmpty())
			{
				return true;
			}
		}
		return false;
	};
	BX.Crm.EntityEditorMultifield.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.UI.EntityEditorModeSwitchType.common;
		if(mode === BX.UI.EntityEditorMode.edit)
		{
			result |= BX.UI.EntityEditorModeSwitchType.button|BX.UI.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorMultifield.prototype.getContentWrapper = function()
	{
		return this._itemWrapper;
	};
	BX.Crm.EntityEditorMultifield.prototype.processModelChange = function(params)
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
	BX.Crm.EntityEditorMultifield.prototype.prepareItemsLayout = function()
	{
		for(var i = 0, length = this._items.length; i < length; i++)
		{
			var item = this._items[i];
			item.setMode(this._mode);
			item.setContainer(this._itemWrapper);
			item.layout();
		}
	};
	BX.Crm.EntityEditorMultifield.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.UI.EntityEditorModeSwitchType.common;
		if(mode === BX.UI.EntityEditorMode.edit)
		{
			result |= BX.UI.EntityEditorModeSwitchType.button|BX.UI.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorMultifield.prototype.getContentWrapper = function()
	{
		return this._itemWrapper;
	};
	BX.Crm.EntityEditorMultifield.prototype.focus = function()
	{
		if(this._items && this._items.length > 0)
		{
			this._items[this._items.length - 1].focus();
		}
	};
	BX.Crm.EntityEditorMultifield.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated({ classNames: [ "crm-entity-widget-content-block-field-multifield" ] });
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		this._itemWrapper = null;

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		this._wrapper.appendChild(this.createTitleNode(this.getTitle()));

		this._itemWrapper = BX.create("div", { attrs: { className: "crm-entity-widget-content-block-inner" } });
		this._wrapper.appendChild(this._itemWrapper);

		if(this.hasContentToDisplay())
		{
			this.prepareItemsLayout();
		}
		else if(this._mode === BX.UI.EntityEditorMode.view)
		{
			this._itemWrapper.appendChild(document.createTextNode(this.getMessage("isEmpty")));
		}

		if(this._mode === BX.UI.EntityEditorMode.edit)
		{
			this._wrapper.appendChild(
				BX.create(
					"div",
					{
						attrs: { className: "crm-entity-widget-content-block-add-field" },
						children:
							[
								BX.create(
									"span",
									{
										attrs: { className: "crm-entity-widget-content-add-field" },
										text: this.getMessage("add"),
										events: { click: BX.delegate(this.onAddButtonClick, this) }
									}
								)
							]
					}
				)
			);
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
	BX.Crm.EntityEditorMultifield.prototype.doClearLayout = function(options)
	{
		for(var i = 0, length = this._items.length; i < length; i++)
		{
			var item = this._items[i];
			item.clearLayout();
			item.setContainer(null);
		}
		this._itemWrapper = null;
	};
	BX.Crm.EntityEditorMultifield.prototype.refreshLayout = function(options)
	{
		if(!this._hasLayout)
		{
			return;
		}

		if(!this._isValidLayout)
		{
			BX.Crm.EntityEditorMultifield.superclass.refreshLayout.apply(this, arguments);
			return;
		}

		this.resetItems();
		BX.cleanNode(this._itemWrapper);

		this.initializeItems();
		if(this.hasContentToDisplay())
		{
			this.prepareItemsLayout();
		}
		else if(this._mode === BX.UI.EntityEditorMode.view)
		{
			this._itemWrapper.appendChild(document.createTextNode(this.getMessage("isEmpty")));
		}
	};
	BX.Crm.EntityEditorMultifield.prototype.getMultifieldType = function()
	{
		return this._schemeElement.getDataStringParam("type", "");
	};
	BX.Crm.EntityEditorMultifield.prototype.addItem = function(data)
	{
		var item;
		var typeId = this._schemeElement.getName();

		if(typeId === 'PHONE')
		{
			item = BX.Crm.EntityEditorMultifieldItemPhone.create(
				"",
				{
					parent: this,
					typeId: this._schemeElement.getName(),
					valueTypeItems: this._schemeElement.getDataArrayParam("items", []),
					data: data
				}
			);
		}
		else
		{
			item = BX.Crm.EntityEditorMultifieldItem.create(
				"",
				{
					parent: this,
					typeId: this._schemeElement.getName(),
					valueTypeItems: this._schemeElement.getDataArrayParam("items", []),
					data: data
				}
			);
		}

		if(this._items === null)
		{
			this._items = [];
		}

		this._items.push(item);

		if(this._hasLayout)
		{
			item.setMode(this._mode);
			item.setContainer(this._itemWrapper);
			item.layout();
		}

		return item;
	};
	BX.Crm.EntityEditorMultifield.prototype.onAddButtonClick = function(e)
	{
		this.addItem({ "ID": "n" + this._items.length.toString() });
	};
	BX.Crm.EntityEditorMultifield.prototype.processItemChange = function(item)
	{
		this.markAsChanged();
	};
	BX.Crm.EntityEditorMultifield.prototype.processItemDeletion = function(item)
	{
		this.deleteItem(item);
		this.markAsChanged();
	};
	BX.Crm.EntityEditorMultifield.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorMultifield();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorProductRowSummary === 'undefined')
{
	/**
	 * @deprecated
	 */
	BX.Crm.EntityEditorProductRowSummary = BX.UI.EntityEditorProductRowSummary;
}

if(typeof BX.Crm.EntityEditorFileStorage === "undefined")
{
	BX.Crm.EntityEditorFileStorage = function()
	{
		BX.Crm.EntityEditorFileStorage.superclass.constructor.apply(this);
		this._uploaderName = "entity_editor_storage_" + this._id.toLowerCase();
		this._dataContainer = null;
		this._uploaderContainer = null;
		this._uploader = null;
	};

	BX.extend(BX.Crm.EntityEditorFileStorage, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorFileStorage.prototype.getStorageTypeId = function()
	{
		return this._model.getIntegerField("STORAGE_TYPE_ID", BX.UI.EditorFileStorageType.undefined);
	};
	BX.Crm.EntityEditorFileStorage.prototype.getStorageElementInfos = function()
	{
		var storageTypeId = this.getStorageTypeId();
		if(storageTypeId === BX.UI.EditorFileStorageType.diskfile)
		{
			return this._model.getArrayField(
				this._schemeElement.getDataStringParam("diskFileInfo", "DISK_FILES"),
				[]
			);
		}

		return [];
	};
	BX.Crm.EntityEditorFileStorage.prototype.hasContentToDisplay = function()
	{
		return(this.getStorageElementInfos().length > 0);
	};
	BX.Crm.EntityEditorFileStorage.prototype.getModeSwitchType = function()
	{
		return BX.UI.EntityEditorModeSwitchType.content;
	};
	BX.Crm.EntityEditorFileStorage.prototype.getContentWrapper = function()
	{
		return this._uploaderContainer;
	};
	BX.Crm.EntityEditorFileStorage.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated({ classNames: [ "crm-entity-widget-content-block-field-filestorage" ] });
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
		if(this._mode === BX.UI.EntityEditorMode.edit)
		{
			this._dataContainer = BX.create("DIV", {});
			this._wrapper.appendChild(this._dataContainer);
		}

		this._uploaderContainer = BX.create(
			"DIV",
			{ attrs: { className: "bx-crm-dialog-activity-webdav-container" } }
		);
		this._wrapper.appendChild(this._uploaderContainer);

		// we have to import into document or file uploading is not going to start by clicking
		this.registerLayout(options);

		var storageTypeId = this.getStorageTypeId();
		if(storageTypeId === BX.UI.EditorFileStorageType.diskfile)
		{
			var uploader = this.getDiskUploader();

			uploader.cleanLayout();
			uploader.setMode(this._mode);
			uploader.clearValues();
			uploader.setValues(this.getStorageElementInfos());
			uploader.layout(this._uploaderContainer);
		}

		if(this.isContextMenuEnabled())
		{
			this._wrapper.appendChild(this.createContextMenuButton());
		}

		if(this.isDragEnabled())
		{
			this.initializeDragDropAbilities();
		}

		this.subscribeOnUploaderChange();

		this._hasLayout = true;
	};
	BX.Crm.EntityEditorFileStorage.prototype.doClearLayout = function(options)
	{
		this._dataContainer = this._uploaderContainer = null;
		this.unSubscribeOnUploaderChange();
	};
	BX.Crm.EntityEditorFileStorage.prototype.getDiskUploader = function()
	{
		if(this._uploader)
		{
			return this._uploader;
		}

		if(typeof(BX.CrmDiskUploader) !== "undefined" &&
			typeof(BX.CrmDiskUploader.items[this._uploaderName]) !== "undefined"
		)
		{
			this._uploader = BX.CrmDiskUploader.items[this._uploaderName];
		}

		if(!this._uploader)
		{
			this._uploader = BX.CrmDiskUploader.create(
				this._uploaderName,
				{
					msg :
						{
							diskAttachFiles : this.getMessage('diskAttachFiles'),
							diskAttachedFiles : this.getMessage('diskAttachedFiles'),
							diskSelectFile : this.getMessage('diskSelectFile'),
							diskSelectFileLegend : this.getMessage('diskSelectFileLegend'),
							diskUploadFile : this.getMessage('diskUploadFile'),
							diskUploadFileLegend : this.getMessage('diskUploadFileLegend')
						}
				}
			)
		}

		return this._uploader;
	};
	BX.Crm.EntityEditorFileStorage.prototype.subscribeOnUploaderChange = function()
	{
		if(this._uploader)
		{
			this._uploader.subscribe('addItem', this._changeHandler);
			this._uploader.subscribe('removeItem', this._changeHandler);
		}
	};
	BX.Crm.EntityEditorFileStorage.prototype.unSubscribeOnUploaderChange = function()
	{
		if(this._uploader)
		{
			this._uploader.unsubscribe('addItem', this._changeHandler);
			this._uploader.unsubscribe('removeItem', this._changeHandler);
		}
	};
	BX.Crm.EntityEditorFileStorage.prototype.getDiskUploaderValues = function()
	{
		var uploader = BX.CrmDiskUploader.items[this._uploaderName];
		return uploader ? uploader.getFileIds() : [];
	};
	BX.Crm.EntityEditorFileStorage.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorFileStorage.messages;
		return m.hasOwnProperty(name) ? m[name] : BX.Crm.EntityEditorFileStorage.superclass.getMessage.apply(this, arguments);
	};
	BX.Crm.EntityEditorFileStorage.prototype.save = function()
	{
		var storageTypeId = this.getStorageTypeId();
		if(storageTypeId === BX.UI.EditorFileStorageType.diskfile)
		{
			this._model.setField(
				this._schemeElement.getDataStringParam("storageElementIds", "STORAGE_ELEMENT_IDS"),
				this.getDiskUploaderValues()
			);
		}
	};
	BX.Crm.EntityEditorFileStorage.prototype.onBeforeSubmit = function()
	{
		if(!this._dataContainer)
		{
			return;
		}

		BX.cleanNode(this._dataContainer, false);

		this._dataContainer.appendChild(
			BX.create(
				"INPUT",
				{
					attrs:
						{
							type: "hidden",
							name: this._schemeElement.getDataStringParam("storageTypeId", "STORAGE_TYPE_ID"),
							value: this.getStorageTypeId()
						}
				}
			)
		);

		var elementFieldName = this._schemeElement.getDataStringParam("storageElementIds", "STORAGE_ELEMENT_IDS");

		var values = this._model.getArrayField(elementFieldName, []);
		if(values.length > 0)
		{
			for(var i = 0, length = values.length; i < length; i++)
			{
				this._dataContainer.appendChild(
					BX.create("INPUT", { attrs: { type: "hidden", name: elementFieldName + "[]", value: values[i] } })
				);
			}
		}
		else
		{
			this._dataContainer.appendChild(
				BX.create("INPUT", { attrs: { type: "hidden", name: elementFieldName, value: "" } })
			);
		}
	};
	if(typeof(BX.Crm.EntityEditorFileStorage.messages) === "undefined")
	{
		BX.Crm.EntityEditorFileStorage.messages = {};
	}
	BX.Crm.EntityEditorFileStorage.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorFileStorage();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorCustom === "undefined")
{
	/**
	 * @deprecated
	 */
	BX.Crm.EntityEditorCustom = BX.UI.EntityEditorCustom;
}

if(typeof BX.Crm.EntityEditorHidden === "undefined")
{
	BX.Crm.EntityEditorHidden = function()
	{
		BX.Crm.EntityEditorHidden.superclass.constructor.apply(this);
		this._input = null;
		this._view = null;
	};

	BX.extend(BX.Crm.EntityEditorHidden, BX.UI.EntityEditorText);

	BX.Crm.EntityEditorHidden.prototype.layout = function(options)
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

		var name = this.getName();
		var title = this.getTitle();
		var value = this.getValue();

		this._input = null;
		this._innerWrapper = null;

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		this._wrapper.appendChild(this.createTitleNode(title));

		if(this.hasContentToDisplay())
		{
			if(this.getLineCount() > 1)
			{
				this._innerWrapper = BX.create(
					"div",
					{
						props: { className: "crm-entity-widget-content-block-inner" },
						html: BX.util.nl2br(BX.util.htmlspecialchars(value))
					}
				);
			}
			else
			{
				this._innerWrapper = BX.create(
					"div",
					{
						props: { className: "crm-entity-widget-content-block-inner" },
						text: value
					}
				);
			}
		}
		else
		{
			this._innerWrapper = BX.create(
				"div",
				{
					props: { className: "crm-entity-widget-content-block-inner" },
					text: this.getMessage("isEmpty")
				}
			);
		}

		if(this._mode === BX.UI.EntityEditorMode.edit)
		{
			this._input = BX.create("input", {
				props: {
					id: 'crm-entity-widget-content-input',
					name: name,
					type: 'hidden',
					value: value
				}
			});

			this._innerWrapper.appendChild(this._input);
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

	BX.Crm.EntityEditorHidden.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorHidden();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof BX.Crm.EntityBindingTracker === "undefined")
{
	BX.Crm.EntityBindingTracker = function()
	{
		this._id = "";
		this._settings = {};
		this._boundEntityInfos = null;
		this._unboundEntityInfos = null;
	};

	BX.Crm.EntityBindingTracker.prototype =
		{
			initialize: function()
			{
				this._boundEntityInfos = [];
				this._unboundEntityInfos = [];
			},
			bind: function(entityInfo)
			{
				if(this.findIndex(entityInfo, this._boundEntityInfos) >= 0)
				{
					return;
				}

				var index = this.findIndex(entityInfo, this._unboundEntityInfos);
				if(index >= 0)
				{
					this._unboundEntityInfos.splice(index, 1);
				}
				else
				{
					this._boundEntityInfos.push(entityInfo);
				}
			},
			unbind: function(entityInfo)
			{
				if(this.findIndex(entityInfo, this._unboundEntityInfos) >= 0)
				{
					return;
				}

				var index = this.findIndex(entityInfo, this._boundEntityInfos);
				if(index >= 0)
				{
					this._boundEntityInfos.splice(index, 1);
				}
				else
				{
					this._unboundEntityInfos.push(entityInfo);
				}
			},
			getBoundEntities: function()
			{
				return this._boundEntityInfos;
			},
			getUnboundEntities: function()
			{
				return this._unboundEntityInfos;
			},
			isBound: function(entityInfo)
			{
				return this.findIndex(entityInfo, this._boundEntityInfos) >= 0;
			},
			isUnbound: function(entityInfo)
			{
				return this.findIndex(entityInfo, this._unboundEntityInfos) >= 0;
			},
			reset: function()
			{
				this._boundEntityInfos = [];
				this._unboundEntityInfos = [];
			},
			findIndex: function(item, collection)
			{
				var id = item.getId();
				for(var i = 0, length = collection.length; i < length; i++)
				{
					if(id === collection[i].getId())
					{
						return i;
					}
				}
				return -1;
			}
		};
	BX.Crm.EntityBindingTracker.create = function()
	{
		var self = new BX.Crm.EntityBindingTracker();
		self.initialize();
		return self;
	};
}

if(typeof BX.Crm.EntityEditorSubsection === "undefined")
{
	BX.Crm.EntityEditorSubsection = function()
	{
		BX.Crm.EntityEditorSubsection.superclass.constructor.apply(this);
	};

	BX.extend(BX.Crm.EntityEditorSubsection, BX.Crm.EntityEditorSection);
	BX.Crm.EntityEditorSubsection.prototype.initialize =  function(id, settings)
	{
		BX.Crm.EntityEditorSubsection.superclass.initialize.call(this, id, settings);
		this.initializeFromModel();
	};

	BX.Crm.EntityEditorSubsection.prototype.ensureWrapperCreated = function(params)
	{
		if(!this._wrapper)
		{
			this._wrapper = BX.create("div");
		}

		return this._wrapper;
	};
	BX.Crm.EntityEditorSubsection.prototype.layout = function(options)
	{
		//Create wrapper
		this._contentContainer = BX.create("div");
		var isViewMode = this._mode === BX.UI.EntityEditorMode.view ;
		this.ensureWrapperCreated();
		this.layoutTitle();

		this._wrapper.appendChild(this._contentContainer);

		var isFieldContextMenuEnabled = false;

		//Layout fields
		for(var i = 0, l = this._fields.length; i < l; i++)
		{
			this.layoutChild(this._fields[i]);
			if(!isFieldContextMenuEnabled && this._fields[i].isContextMenuEnabled())
			{
				isFieldContextMenuEnabled = true;
			}
		}
		if(isFieldContextMenuEnabled)
		{
			BX.addClass(this._contentContainer, "ui-entity-editor-section-content-padding-right");
		}

		this._addChildButton = this._createChildButton = null;

		if (this.isDragEnabled())
		{
			this._dragContainerController = BX.Crm.EditorDragContainerController.create(
				"section_" + this.getId(),
				{
					charge: BX.Crm.EditorFieldDragContainer.create(
						{
							section: this,
							context: this._draggableContextId
						}
					),
					node: this._wrapper
				}
			);
			this._dragContainerController.addDragFinishListener(this._dropHandler);

			this.initializeDragDropAbilities();
		}

		this._addChildButton = this._createChildButton = null;

		if(!isViewMode)
		{
			this.createButtonPanel();
			this._contentContainer.appendChild(this._buttonPanelWrapper);

		}

		this._hasLayout = true;
		this.registerLayout(options);
	};
	BX.Crm.EntityEditorSubsection.prototype.getChildDragScope = function()
	{
		return BX.UI.EditorDragScope.parent;
	};
	BX.Crm.EntityEditorSubsection.prototype.createButtonPanel = function()
	{
		this._buttonPanelWrapper = BX.create("div", {
			props: { className: "crm-entity-widget-content-block" }
		});
	};

	BX.Crm.EntityEditorSubsection.prototype.layoutChild = function(field)
	{
		field.setContainer(this._contentContainer);
		field.setDraggableContextId(this._draggableContextId);
		this.setChildVisible(field);
		//Force layout reset because of animation implementation
		field.releaseLayout();
		field.layout();
		if(this._mode !== BX.UI.EntityEditorMode.view && field.isHeading())
		{
			field.focus();
		}
	};

	BX.Crm.EntityEditorSubsection.prototype.setChildVisible = function(field)
	{
		field.setVisible(BX.prop.getBoolean(field._schemeElement._settings, "isVisible", true));
	};

	BX.Crm.EntityEditorSubsection.prototype.isDragEnabled = function()
	{
		return false;
	};

	BX.Crm.EntityEditorSubsection.prototype.layoutTitle = function()
	{
	};

	BX.Crm.EntityEditorSubsection.prototype.isCreationEnabled = function()
	{
		return false;
	};

	BX.Crm.EntityEditorSubsection.prototype.isContextMenuEnabled = function()
	{
		return false;
	};

	BX.Crm.EntityEditorSubsection.prototype.isRequired = function()
	{
		return true;
	};

	BX.Crm.EntityEditorSubsection.prototype.isNeedToDisplay = function()
	{
		return true;
	};

	BX.Crm.EntityEditorSubsection.prototype.getRuntimeValue = function()
	{
		var data = [];

		for (var i=0; i < this.getChildCount();i++)
		{
			var fieldValue = this._fields[i].getRuntimeValue();

			if (BX.type.isArray(fieldValue))
			{
				for (var key in fieldValue)
				{
					if(fieldValue.hasOwnProperty(key))
					{
						data[key] = fieldValue[key];
					}
				}
			}
			else
			{
				data[this._fields[i].getName()] = fieldValue
			}
		}
		return data;
	};
	BX.Crm.EntityEditorSubsection.prototype.createDragButton = function()
	{
		if(!this._dragButton)
		{
			this._dragButton = BX.create(
				"div",
				{
					props: { className: "crm-entity-widget-content-block-draggable-btn-container" },
					children:
						[
							BX.create(
								"div",
								{
									props: { className: "crm-entity-widget-content-block-draggable-btn" }
								}
							)
						]
				}
			);
		}
		return this._dragButton;
	};
	BX.Crm.EntityEditorSubsection.prototype.initializeDragDropAbilities = function()
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
						contextId: this._draggableContextId,
						scope: this.getDragScope()
					}
				),
				node: this.createDragButton(),
				showControlInDragMode: false,
				ghostOffset: { x: 0, y: 0 }
			}
		);
	};
	BX.Crm.EntityEditorSubsection.prototype.processChildControlChange = function(child, params)
	{
		if(this._isChanged)
		{
			return;
		}

		this.markAsChanged(params);
	};
	BX.Crm.EntityEditorSubsection.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorSubsection();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorRecurring === "undefined")
{
	BX.Crm.EntityEditorRecurring = function()
	{
		BX.Crm.EntityEditorRecurring.superclass.constructor.apply(this);
	};

	BX.extend(BX.Crm.EntityEditorRecurring, BX.Crm.EntityEditorSubsection);
	BX.Crm.EntityEditorRecurring.prototype.initialize =  function(id, settings)
	{
		BX.Crm.EntityEditorRecurring.superclass.initialize.call(this, id, settings);
		var data = this._schemeElement.getData();
		this._schemeFieldData = BX.prop.getObject(data, 'fieldData', {});
		this._enableRecurring = BX.prop.getBoolean(this._schemeElement._settings, "enableRecurring", true);
		this._recurringModel = this._model.getField(this.getName());
	};

	BX.Crm.EntityEditorRecurring.prototype.initializeFromModel =  function()
	{
		BX.Crm.EntityEditorRecurring.superclass.initializeFromModel.call(this);
		var _this = this;
		for (var i = 0, length = this._fields.length; i < length; i++)
		{
			this._fields[i].getValue = function(name){
				if (!BX.type.isNotEmptyString(name))
				{
					name = this.getName();
				}
				return _this.getRecurringFieldValue(name);
			};
		}
	};

	BX.Crm.EntityEditorRecurring.prototype.getRecurringModel =  function()
	{
		var parent = this.getParent();
		if (parent instanceof BX.Crm.EntityEditorRecurring)
		{
			return parent.getRecurringModel();
		}

		return this._recurringModel;
	};
	BX.Crm.EntityEditorRecurring.prototype.isContextMenuEnabled = function()
	{
		return BX.Crm.EntityEditorSubsection.superclass.isContextMenuEnabled.call(this);
	};
	BX.Crm.EntityEditorRecurring.prototype.isNeedToDisplay = function()
	{
		return false;
	};
	BX.Crm.EntityEditorRecurring.prototype.isRequired = function()
	{
		return this._schemeElement && this._schemeElement.isRequired();
	};
	BX.Crm.EntityEditorRecurring.prototype.prepareContextMenuItems = function()
	{
		var results = [];
		results.push({ value: "hide", text: this.getMessage("hide") });

		return results;
	};
	BX.Crm.EntityEditorRecurring.prototype.processContextMenuCommand = function(e, command)
	{
		if(command === "hide")
		{
			window.setTimeout(BX.delegate(this.hide, this), 500);
		}
		else if (this._parent && this._parent.hasAdditionalMenu())
		{
			this._parent.processChildAdditionalMenuCommand(this, command);
		}
		this.closeContextMenu();
	};
	BX.Crm.EntityEditorRecurring.prototype.isDragEnabled = function()
	{
		return BX.Crm.EntityEditorSubsection.superclass.isDragEnabled.call(this);
	};
	BX.Crm.EntityEditorRecurring.prototype.getDragObjectType = function()
	{
		return BX.UI.EditorDragObjectType.field;
	};
	BX.Crm.EntityEditorRecurring.prototype.hasContentToDisplay = function()
	{
		return true;
	};
	BX.Crm.EntityEditorRecurring.prototype.getRecurringMode =  function()
	{
		var parent = this.getParent();
		if (parent instanceof BX.Crm.EntityEditorRecurring)
		{
			return parent.getRecurringMode();
		}

		return this.getRecurringFieldValue('RECURRING[MODE]');
	};

	BX.Crm.EntityEditorRecurring.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorRecurring.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	BX.Crm.EntityEditorRecurring.prototype.processChildControlChange = function(child, params)
	{
		var childName = child.getName();
		var refreshLayout = false;
		var previousValue = child.getValue();
		var changedValue = child.getRuntimeValue();
		if (previousValue !== changedValue)
		{
			switch (childName)
			{
				case 'RECURRING[MODE]':
				case 'RECURRING[MULTIPLE_TYPE_LIMIT]':
				case 'RECURRING[BEGINDATE_TYPE]':
				case 'RECURRING[CLOSEDATE_TYPE]':
					refreshLayout = true;
					break;
				case 'RECURRING[MULTIPLE_TYPE]':
					if (
						previousValue === this.getSchemeFieldValue('MULTIPLE_CUSTOM')
						|| changedValue === this.getSchemeFieldValue('MULTIPLE_CUSTOM')
					)
					{
						refreshLayout = true;
					}
			}
		}
		var recurringModel = this.getRecurringModel();
		this.setChangedValue(childName, changedValue, recurringModel);
		BX.Crm.EntityEditorRecurring.superclass.processChildControlChange.call(this, child, params);
		if (refreshLayout)
		{
			this.refreshLayout();
		}
	};
	BX.Crm.EntityEditorRecurring.prototype.setChangedValue = function(childName, value, model)
	{
		if (typeof value === "object")
		{
			for (var key in value)
			{
				if(value.hasOwnProperty(key))
				{
					this.setChangedValue(key, value[key], model);
				}
			}
		}
		else
		{
			model[childName] = value;
		}
	};
	BX.Crm.EntityEditorRecurring.prototype.layout = function(options)
	{
		//Create wrapper
		this._contentContainer = BX.create("div");

		if (this.isMainSubsection())
		{
			this._contentContainer.classList.add("crm-entity-widget-content");
		}

		var isViewMode = this._mode === BX.UI.EntityEditorMode.view ;
		this.ensureWrapperCreated();
		this.layoutTitle();

		this._wrapper.appendChild(this._contentContainer);

		if (isViewMode)
		{
			var viewNode = BX.create("div", {
				props:{
					className: "crm-entity-widget-content-block crm-entity-widget-content-block-click-editable"
				},
				children: [this.createTitleNode(this.getTitle())]
			});
			this._contentContainer.appendChild(viewNode);

			var textNode = BX.create("div");
			var layoutData = this._schemeElement.getData();
			if (this._schemeElement._promise instanceof BX.Promise)
			{
				this.loadViewText();
				this._schemeElement._promise.then(
					BX.proxy(function() {
						textNode.classList = "crm-entity-widget-content-block-inner";
						textNode.innerHTML = BX.util.htmlspecialchars(layoutData.view.text);
						viewNode.innerHTML = '';
						viewNode.appendChild(textNode);
						this._schemeElement._promise = null;
					}, this)
				);
			}
			else if (BX.type.isNotEmptyString(layoutData.view.text))
			{
				textNode.classList = "crm-entity-widget-content-block-inner";
				textNode.innerHTML = layoutData.view.text;
				viewNode.appendChild(textNode)
			}
			if (this._enableRecurring)
			{
				BX.bind(textNode, "click", BX.delegate(this.toggle, this));
			}

			if(this.isContextMenuEnabled())
			{
				viewNode.appendChild(this.createContextMenuButton());
			}
			if(this.isDragEnabled())
			{
				viewNode.appendChild(this.createDragButton());
				this.initializeDragDropAbilities();
			}
		}
		else if(!this._enableRecurring)
		{
			var viewNode = BX.create("div", {
				props:{
					className: "crm-entity-widget-content-block"
				},
				children: [this.createTitleNode(this.getMessage('modeTitle'))]
			});

			var disabledField = BX.create("div",{
				props: {
					className:'crm-entity-widget-content-block-inner'
				},
				children:[
					BX.create("div",{
						type:"text",
						props: {
							className:'crm-entity-widget-content-input',
							disabled: "disabled"
						},
						text: this.getMessage('notRepeat'),
						events: {
							click: BX.delegate(this.showLicencePopup,this)
						}
					})
				]

			});
			viewNode.appendChild(disabledField);
			var lock = BX.create("button",{
				props: {
					className:'crm-entity-widget-content-block-locked-icon'
				},
				events: {
					click: BX.delegate(this.showLicencePopup,this)
				}
			});
			viewNode.appendChild(lock);
			this._contentContainer.appendChild(viewNode);
		}
		else
		{
			for(var i = 0, l = this._fields.length; i < l; i++)
			{
				this._fields[i].isDragEnabled = function(){
					return false;
				};
				this.layoutChild(this._fields[i]);
			}
		}
		//Layout fields

		this._addChildButton = this._createChildButton = null;
		this._hasLayout = true;
		this.registerLayout(options);
	};
	BX.Crm.EntityEditorRecurring.prototype.createTitleNode = function(title)
	{
		var titleNode = BX.create(
			"div",
			{
				attrs: { className: "crm-entity-widget-content-block-title" },
				children: [
					BX.create(
						"span",
						{
							attrs: { className: "crm-entity-widget-content-block-title-text" },
							text: title
						}
					)
				]
			}
		);

		return titleNode;
	};
	BX.Crm.EntityEditorRecurring.prototype.setChildVisible = function(field)
	{
		var value = false;
		var name = field.getName();
		var mode = this.getRecurringMode();
		if (name === 'RECURRING[MODE]')
		{
			value = true;
		}
		else if (mode === this.getSchemeFieldValue('SINGLE_EXECUTION'))
		{
			switch (name)
			{
				case 'SINGLE_PARAMS':
				case 'RECURRING[BEGINDATE_TYPE]':
				case 'RECURRING[CLOSEDATE_TYPE]':
				case 'SUBTITLE_NEW_ORDER_PARAMS':
				case 'NEW_BEGINDATE':
				case 'NEW_CLOSEDATE':
				case 'RECURRING[CATEGORY_ID]':
					value = true;
					break;
				case 'OFFSET_BEGINDATE':
					if (this.getRecurringFieldValue('RECURRING[BEGINDATE_TYPE]') === this.getSchemeFieldValue('CALCULATED_FIELD_VALUE'))
					{
						value = true;
					}
					break;
				case 'OFFSET_CLOSEDATE':
					if (this.getRecurringFieldValue('RECURRING[CLOSEDATE_TYPE]') === this.getSchemeFieldValue('CALCULATED_FIELD_VALUE'))
					{
						value = true;
					}
					break;
			}
		}
		else if (mode === this.getSchemeFieldValue('MULTIPLE_EXECUTION'))
		{
			switch (name)
			{
				case 'MULTIPLE_PARAMS':
				case 'RECURRING[MULTIPLE_TYPE]':
				case 'RECURRING[CATEGORY_ID]':
				case 'RECURRING[MULTIPLE_DATE_START]':
				case 'MULTIPLE_LIMIT':
				case 'RECURRING[MULTIPLE_TYPE_LIMIT]':
				case 'SUBTITLE_NEW_ORDER_PARAMS':
				case 'NEW_BEGINDATE':
				case 'NEW_CLOSEDATE':
				case 'RECURRING[BEGINDATE_TYPE]':
				case 'RECURRING[CLOSEDATE_TYPE]':
					value = true;
					break;
				case 'MULTIPLE_CUSTOM':
					if (this.getRecurringFieldValue('RECURRING[MULTIPLE_TYPE]') === this.getSchemeFieldValue('MULTIPLE_CUSTOM'))
					{
						value = true;
					}
					break;
				case 'RECURRING[MULTIPLE_DATE_LIMIT]':
					if (this.getRecurringFieldValue('RECURRING[MULTIPLE_TYPE_LIMIT]') === this.getSchemeFieldValue('LIMITED_BY_DATE'))
					{
						value = true;
					}
					break;
				case 'RECURRING[MULTIPLE_TIMES_LIMIT]':
					if (this.getRecurringFieldValue('RECURRING[MULTIPLE_TYPE_LIMIT]') === this.getSchemeFieldValue('LIMITED_BY_TIMES'))
					{
						value = true;
					}
					break;
				case 'OFFSET_BEGINDATE':
					if (this.getRecurringFieldValue('RECURRING[BEGINDATE_TYPE]') === this.getSchemeFieldValue('CALCULATED_FIELD_VALUE'))
					{
						value = true;
					}
					break;
				case 'OFFSET_CLOSEDATE':
					if (this.getRecurringFieldValue('RECURRING[CLOSEDATE_TYPE]') === this.getSchemeFieldValue('CALCULATED_FIELD_VALUE'))
					{
						value = true;
					}
					break;
			}
		}
		field.setVisible(value);
	};
	BX.Crm.EntityEditorRecurring.prototype.getRecurringFieldValue = function(name)
	{
		return BX.prop.get(this.getRecurringModel(), name)
	};
	BX.Crm.EntityEditorRecurring.prototype.getSchemeFieldValue = function(name)
	{
		return BX.prop.get(this._schemeFieldData, name, "")
	};
	BX.Crm.EntityEditorRecurring.prototype.isMainSubsection = function()
	{
		return !(this.getParent() instanceof BX.Crm.EntityEditorRecurring);
	};
	BX.Crm.EntityEditorRecurring.prototype.onBeforeSubmit = function()
	{
		if (this.isMainSubsection())
		{
			this._wrapper.appendChild(
				BX.create('input',{
					props:{
						type: 'hidden',
						name: 'IS_RECURRING',
						value: (this._model.getStringField('IS_RECURRING') === 'Y') ? 'Y' : 'N'
					}
				})
			);
		}
	};
	BX.Crm.EntityEditorRecurring.prototype.save = function()
	{
		if (this.isMainSubsection())
		{
			this._schemeElement._promise = new BX.Promise();
		}
	};
	BX.Crm.EntityEditorRecurring.prototype.loadViewText = function()
	{
		var data = this._schemeElement.getData();
		if (
			BX.type.isPlainObject(data.loaders)
			&& BX.type.isNotEmptyString(data.loaders["url"])
			&& BX.type.isNotEmptyString(data.loaders["action"])
		)
		{
			BX.ajax(
				{
					url: data.loaders["url"],
					method: "POST",
					dataType: "json",
					data: {
						ACTION: data.loaders["action"],
						PARAMS: {ID:this._model.getField('ID')}
					},
					onsuccess: BX.delegate(this.onEntityHintLoad, this)
				}
			);
		}
	};
	BX.Crm.EntityEditorRecurring.prototype.onEntityHintLoad = function(result)
	{
		var entityData = BX.prop.getObject(result, "DATA", null);

		if(!entityData)
		{
			return;
		}
		if (BX.type.isNotEmptyString(entityData.HINT))
		{
			this._schemeElement._data.view.text = entityData.HINT;
		}

		if (this._schemeElement._promise instanceof BX.Promise)
		{
			this._schemeElement._promise.fulfill();
			this._schemeElement._promise = null;
		}
	};
	BX.Crm.EntityEditorRecurring.prototype.showLicencePopup = function(e)
	{
		e.preventDefault();

		if(!B24 || !B24['licenseInfoPopup'])
		{
			return;
		}

		var layoutData = this._schemeElement.getData();
		var restrictionScript = layoutData.restrictScript;
		if (BX.type.isNotEmptyString(restrictionScript))
		{
			eval(restrictionScript);
		}
	};
	BX.Crm.EntityEditorRecurring.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorRecurring();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorRecurringCustomRowField === "undefined")
{
	BX.Crm.EntityEditorRecurringCustomRowField = function()
	{
		BX.Crm.EntityEditorRecurringCustomRowField.superclass.constructor.apply(this);
		// this._currencyEditor = null;
		this._amountInput = null;
		this._selectInput = null;
		this._sumElement = null;
		this._selectContainer = null;
		this._inputWrapper = null;
		this._innerWrapper = null;
		this._selectedValue = "";
		this._selectClickHandler = BX.delegate(this.onSelectorClick, this);
		this._isMesureMenuOpened = false;
	};
	BX.extend(BX.Crm.EntityEditorRecurringCustomRowField, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.UI.EntityEditorModeSwitchType.common;
		if(mode === BX.UI.EntityEditorMode.edit)
		{
			result |= BX.UI.EntityEditorModeSwitchType.button|BX.UI.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.getContentWrapper = function()
	{
		return this._innerWrapper;
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.focus = function()
	{
		if(this._amountInput)
		{
			BX.focus(this._amountInput);
			BX.Crm.EditorTextHelper.getCurrent().selectAll(this._amountInput);
		}
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.getValue = function(defaultValue)
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
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated({ classNames: [ "crm-entity-widget-content-block-field-recurring-custom" ] });
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		var title = this.getTitle();
		var data = this.getData();

		var selectInputName = this.getSelectFieldName();
		this._selectedValue = this.getValue(selectInputName);
		var selectItems = BX.prop.getArray(BX.prop.getObject(data, "select"), "items");
		var selectName = '';
		if(!this._selectedValue)
		{
			var firstItem =  selectItems.length > 0 ? selectItems[0] : null;
			if(firstItem)
			{
				this._selectedValue = firstItem["VALUE"];
				selectName = firstItem["NAME"];
			}
		}
		else
		{
			selectName = this._editor.findOption(
				this._selectedValue,
				selectItems
			);
		}

		var amountInputName = this.getAmountFieldName();
		var amountValue = this.getValue(amountInputName);

		// this._amountValue = null;
		this._amountInput = null;
		this._selectInput = null;
		this._selectContainer = null;
		this._innerWrapper = null;
		this._sumElement = null;

		if(this._mode === BX.UI.EntityEditorMode.edit)
		{
			this._wrapper.appendChild(this.createTitleNode(title));

			this._amountInput = BX.create("input",
				{
					attrs:
						{
							className: "crm-entity-widget-content-input",
							name: amountInputName,
							type: "text",
							value: amountValue
						}
				}
			);
			BX.bind(this._amountInput, "input", this._changeHandler);

			this._selectInput = BX.create("input",
				{
					attrs:
						{
							name: selectInputName,
							type: "hidden",
							value: this._selectedValue
						}
				}
			);

			this._selectContainer = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-select" },
					text: selectName
				}
			);
			BX.bind(this._selectContainer, "click", this._selectClickHandler);

			this._inputWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-input-wrapper" },
					children:
						[
							this._amountInput,
							this._selectInput,
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

		this._wrapper.appendChild(this._innerWrapper);

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.doClearLayout = function(options)
	{
		BX.PopupMenu.destroy(this._id);
		this._amountInput = null;
		this._selectInput = null;
		this._sumElement = null;
		this._selectContainer = null;
		this._inputWrapper = null;
		this._innerWrapper = null;
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.getAmountFieldName = function()
	{
		return this._schemeElement.getDataStringParam("amount", "");
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.getSelectFieldName = function()
	{
		return BX.prop.getString(
			this._schemeElement.getDataObjectParam("select", {}),
			"name",
			""
		);
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.onSelectorClick = function (e)
	{
		this.openListMenu();
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.openListMenu = function()
	{
		if(this._isListMenuOpened)
		{
			return;
		}

		var data = this._schemeElement.getData();
		var selectList = BX.prop.getArray(BX.prop.getObject(data, "select"), "items"); //{NAME, VALUE}

		var key = 0;
		var menu = [];
		while (key < selectList.length)
		{
			menu.push(
				{
					text: selectList[key]["NAME"],
					value: selectList[key]["VALUE"],
					onclick: BX.delegate( this.onSelectItem, this)
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
						onPopupShow: BX.delegate( this.onListMenuOpen, this),
						onPopupClose: BX.delegate( this.onListMenuClose, this)
					}
			}
		);
		BX.PopupMenu.currentItem.popupWindow.setWidth(BX.pos(this._selectContainer)["width"]);
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.closeListMenu = function()
	{
		if(!this._isListMenuOpened)
		{
			return;
		}

		var menu = BX.PopupMenu.getMenuById(this._id);
		if(menu)
		{
			menu.popupWindow.close();
		}
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.onListMenuOpen = function()
	{
		BX.addClass(this._selectContainer, "active");
		this._isListMenuOpened = true;
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.onListMenuClose = function()
	{
		BX.PopupMenu.destroy(this._id);

		BX.removeClass(this._selectContainer, "active");
		this._isListMenuOpened = false;
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.onSelectItem = function(e, item)
	{
		this.closeListMenu();

		this._selectedValue = this._selectInput.value = item.value;
		this._selectContainer.innerHTML = BX.util.htmlspecialchars(item.text);

		this.markAsChanged(
			{
				fieldName: this.getSelectFieldName(),
				fieldValue: this._selectedValue
			}
		);
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.getRuntimeValue = function()
	{
		var data = [];
		if (this._mode === BX.UI.EntityEditorMode.edit)
		{
			if(this._amountInput)
			{
				data[this.getAmountFieldName()] = this._amountInput.value;
			}
			data[this.getSelectFieldName()] = this._selectedValue;

			return data;
		}
		return "";
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.save = function()
	{
		this._model.setField(
			this.getSelectFieldName(),
			this._selectedValue
		);

		if(this._amountInput)
		{
			this._model.setField(this.getAmountFieldName(), this._amountInput.value);
		}
	};
	BX.Crm.EntityEditorRecurringCustomRowField.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorRecurringCustomRowField();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorRecurringSingleField === "undefined")
{
	BX.Crm.EntityEditorRecurringSingleField = function()
	{
		BX.Crm.EntityEditorRecurringSingleField.superclass.constructor.apply(this);
		this._dateInput = null;
	};
	BX.extend(BX.Crm.EntityEditorRecurringSingleField, BX.Crm.EntityEditorRecurringCustomRowField);

	BX.Crm.EntityEditorRecurringSingleField.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated({ classNames: [ "crm-entity-widget-content-block-field-recurring-single" ] });
		this.adjustWrapper();

		if(!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		var title = this.getTitle();
		var data = this.getData();

		var amountInputName = this.getAmountFieldName();
		var amountValue = this.getValue(amountInputName);
		var selectInputName = this.getSelectFieldName();
		this._selectedValue = this.getValue(selectInputName);
		var dateInputName = this.getDateFieldName();
		this._dateValue = this.getValue(dateInputName);

		var selectItems = BX.prop.getArray(BX.prop.getObject(data, "select"), "items");
		var selectName = '';
		if(!this._selectedValue)
		{
			var firstItem =  selectItems.length > 0 ? selectItems[0] : null;
			if(firstItem)
			{
				this._selectedValue = firstItem["VALUE"];
				selectName = firstItem["NAME"];
			}
		}
		else
		{
			selectName = this._editor.findOption(
				this._selectedValue,
				selectItems
			);
		}
		this._amountInput = null;
		this._selectInput = null;
		this._selectContainer = null;
		this._innerWrapper = null;
		this._sumElement = null;

		if(this._mode === BX.UI.EntityEditorMode.edit)
		{
			this._wrapper.appendChild(this.createTitleNode(title));

			this._amountInput = BX.create("input",
				{
					attrs:
						{
							className: "crm-entity-widget-content-input",
							name: amountInputName,
							type: "text",
							value: amountValue
						}
				}
			);
			BX.bind(this._amountInput, "input", this._changeHandler);

			this._selectInput = BX.create("input",
				{
					attrs:
						{
							name: selectInputName,
							type: "hidden",
							value: this._selectedValue
						}
				}
			);

			this._selectContainer = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-select" },
					text: selectName
				}
			);

			this._dateInput = BX.create('input',{
				style:{
					display:'inline-block'
				},
				props:{
					name: dateInputName,
					className:'crm-entity-widget-content-input crm-entity-widget-content-input-date',
					value: this._dateValue
				},
				events: {
					click: function(){
						BX.calendar({node: this, field: this, bTime: false})
					},
					change: BX.delegate(
						function(e){
							this.markAsChanged();
						}, this)
				}
			});

			BX.bind(this._selectContainer, "click", this._selectClickHandler);

			this._inputWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-input-wrapper" },
					children:
						[
							this._amountInput,
							this._selectInput,
							BX.create('div',
								{
									props: { className: "crm-entity-widget-content-block-select" },
									children: [ this._selectContainer ]
								}
							),
							BX.create('span',{ text: this.getMessage('until')}),
							this._dateInput
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

		this._wrapper.appendChild(this._innerWrapper);

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorRecurringCustomRowField.prototype.getDateFieldName = function()
	{
		return this._schemeElement.getDataStringParam("date", "");
	};
	BX.Crm.EntityEditorRecurringSingleField.prototype.getRuntimeValue = function()
	{
		var data = [];
		if (this._mode === BX.UI.EntityEditorMode.edit)
		{
			if(this._amountInput)
			{
				data[this.getAmountFieldName()] = this._amountInput.value;
			}
			data[this.getSelectFieldName()] = this._selectedValue;
			data[this.getDateFieldName()] = this._dateInput.value;

			return data;
		}
		return "";
	};
	BX.Crm.EntityEditorRecurringSingleField.prototype.save = function()
	{
		var data = this._schemeElement.getData();
		this._model.setField(
			BX.prop.getString(BX.prop.getObject(data, "select"), "name"),
			this._selectedValue
		);

		if(this._amountInput)
		{
			this._model.setField(BX.prop.getString(data, "amount"), this._amountInput.value);
		}
		if(this._dateInput)
		{
			this._model.setField(BX.prop.getString(data, "date"), this._dateInput.value);
		}
	};
	BX.Crm.EntityEditorRecurringSingleField.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorRecurringSingleField.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	BX.Crm.EntityEditorRecurringSingleField.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorRecurringSingleField();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorPhone === "undefined")
{
	BX.Crm.EntityEditorPhone = function()
	{
		BX.Crm.EntityEditorPhone.superclass.constructor.apply(this);
		this._dateInput = null;
	};
	BX.extend(BX.Crm.EntityEditorPhone, BX.UI.EntityEditorText);

	BX.Crm.EntityEditorPhone.prototype.getEditModeHtmlNodes = function()
	{
		var value = this.getValue();

		this._input = BX.create("input", { props: { type: "hidden", value: value } });
		if (!this.isVirtual())
		{
			this._input.name = this.getName();
		}

		this._phoneCountryCodeInput = BX.create("input", { props: { type: "hidden" } });
		this._countryFlagNode = BX.create("span", { props: {className: "crm-entity-widget-content-country-flag"}});
		this._maskedPhoneInput = BX.create("input",
			{
				props:
					{
						type: "text",
						className: "crm-entity-widget-content-input crm-entity-widget-content-input-phone",
						autocomplete: "nope"
					}
			}
		);

		var defaultCountry = null;
		if (
			this.getSchemeElement()
			&& BX.Type.isPlainObject(this.getSchemeElement()._options)
			&& BX.Type.isStringFilled(this.getSchemeElement()._options.defaultCountry)
		)
		{
			defaultCountry = this.getSchemeElement()._options.defaultCountry
		}

		var countryCode = BX.prop.getString(this.getExtraData(), "COUNTRY_CODE", "");

		this._maskedPhone = new BX.Crm.PhoneNumberInput({
			node: this._maskedPhoneInput,
			flagNode: this._countryFlagNode,
			isSelectionIndicatorEnabled: true,
			searchDialogContextCode: 'CRM_ENTITY_EDITOR_PHONE',
			userDefaultCountry: defaultCountry,
			savedCountryCode: countryCode,
			onChange: BX.delegate(this.onPhoneNumberChange, this),
			onCountryChange: BX.delegate(this.onPhoneCountryChange, this),
		});

		if (BX.Type.isStringFilled(value))
		{
			this._maskedPhone.setValue(value, countryCode);
		}

		var placeholder = this.isNewEntity()
			? this.getCreationPlaceholder()
			: this.getChangePlaceholder();

		if (placeholder !== '')
		{
			this._maskedPhoneInput.setAttribute("placeholder", placeholder);
		}

		if(this._editor.isDuplicateControlEnabled())
		{
			var dupControlConfig = this.getDuplicateControlConfig();
			if(dupControlConfig)
			{
				if(!BX.type.isPlainObject(dupControlConfig["field"]))
				{
					dupControlConfig["field"] = {};
				}
				dupControlConfig["field"]["id"] = this.getId();
				dupControlConfig["field"]["element"] = this._maskedPhoneInput;
				this._editor.getDuplicateManager().registerField(dupControlConfig);
			}
		}

		return [
			BX.create(
				"div",
				{
					props: { className: "ui-ctl-w100" },
					children: [
						this._countryFlagNode,
						this._maskedPhoneInput,
						this._input,
						this._phoneCountryCodeInput
					]
				}
			)];
	};
	BX.Crm.EntityEditorPhone.prototype.getViewModeHtmlNodes = function()
	{
		var value = this.getValue();
		var phoneNumber = new BX.PhoneNumber();
		phoneNumber.setRawNumber(value);
		return [
			BX.create(
				"div",
				{
					props: { className: "crm-entity-widget-content-block-inner-text" },
					text: phoneNumber.format()
				}
			)];
	};
	BX.Crm.EntityEditorPhone.prototype.doClearLayout = function(options)
	{
		if(this._editor.isDuplicateControlEnabled())
		{
			var dupControlConfig = this.getDuplicateControlConfig();
			if(dupControlConfig)
			{
				if(!BX.type.isPlainObject(dupControlConfig["field"]))
				{
					dupControlConfig["field"] = {};
				}
				dupControlConfig["field"]["id"] = this.getId();
				this._editor.getDuplicateManager().unregisterField(dupControlConfig);
			}
		}

		BX.Crm.EntityEditorPhone.superclass.doClearLayout.apply(this, arguments);
		this._maskedPhoneInput = null;
		this._maskedPhone = null;
		this._phoneCountryCodeInput = null;
	};
	BX.Crm.EntityEditorPhone.prototype.focus = function()
	{
		if(!this._maskedPhoneInput)
		{
			return;
		}

		BX.focus(this._maskedPhoneInput);
		BX.Crm.EditorTextHelper.getCurrent().setPositionAtEnd(this._maskedPhoneInput);
	};

	BX.Crm.EntityEditorPhone.prototype.onPhoneNumberChange = function(event)
	{
		if (!this._input)
		{
			return;
		}

		if (this._input.value !== event.value)
		{
			this._input.value = event.value;
			if (BX.Crm.PhoneNumberInput.isCountryCodeOnly(this._input.value, event.countryCode))
			{
				this._input.value = '';
			}

			this.onChange(event);
		}
	};

	BX.Crm.EntityEditorPhone.prototype.onPhoneCountryChange = function(event)
	{
		if (!this._phoneCountryCodeInput)
		{
			return;
		}

		this._phoneCountryCodeInput.value = event.country;
	}

	BX.Crm.EntityEditorPhone.prototype.setRawRuntimeValue = function(value)
	{
		BX.Crm.EntityEditorPhone.superclass.setRawRuntimeValue.apply(this, arguments);
		if (this._mode === BX.UI.EntityEditorMode.edit && this._maskedPhone)
		{
			this._maskedPhone.setValue(value);
		}
	};
	BX.Crm.EntityEditorPhone.prototype.getInputElement = function()
	{
		return this._maskedPhoneInput;
	};
	BX.Crm.EntityEditorPhone.prototype.getExtraData = function()
	{
		if (!this._model || !this._model._settings || !this._model._settings.extraData)
		{
			return {};
		}

		return BX.prop.getObject(this._model._settings.extraData, "EXTRA", {});
	};
	BX.Crm.EntityEditorPhone.prototype.getPhoneCountryCodeInputValue = function()
	{
		return this._phoneCountryCodeInput.value;
	};
	BX.Crm.EntityEditorPhone.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorPhone();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorRequisiteSelector === "undefined")
{
	BX.Crm.EntityEditorRequisiteSelector = function()
	{
		BX.Crm.EntityEditorRequisiteSelector.superclass.constructor.apply(this);
		this._requisiteId = 0;
		this._bankDetailId = 0;

		this._itemWrappers = {};
		this._itemButtons = {};
		this._itemBankDetailButtons = {};
	};
	BX.extend(BX.Crm.EntityEditorRequisiteSelector, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorRequisiteSelector.prototype.doInitialize = function()
	{
		this._requisiteId = this._model.getIntegerField("REQUISITE_ID", 0);
		this._bankDetailId = this._model.getIntegerField("BANK_DETAIL_ID", 0);
	};
	BX.Crm.EntityEditorRequisiteSelector.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorRequisiteSelector.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	BX.Crm.EntityEditorRequisiteSelector.prototype.getPrefix = function()
	{
		return this._id.toLowerCase() + "_";
	};
	BX.Crm.EntityEditorRequisiteSelector.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		var data = this.getData();
		this._requisiteInfo = BX.CrmEntityRequisiteInfo.create(
			{
				requisiteId: this._requisiteId,
				bankDetailId: this._bankDetailId,
				data: BX.prop.getArray(data, "data", {})
			}
		);

		var items = this._requisiteInfo.getItems();

		this._wrapper = BX.create("div", { props: { className: "crm-entity-requisites-slider-wrapper" } });
		var contentWrapper = BX.create("div", { props: { className: "crm-entity-requisites-slider-content" } });
		this._wrapper.appendChild(contentWrapper);

		var innerContentWrapper = BX.create("div", { props: { className: "crm-entity-requisites-slider-widget-content" } });
		contentWrapper.appendChild(innerContentWrapper);

		var selectContainer = BX.create("div", { props: { className: "crm-entity-requisites-select-container" } });
		innerContentWrapper.appendChild(selectContainer);

		for(var i = 0, length = items.length; i < length; i++)
		{
			selectContainer.appendChild(this.prepareItemLayout(items[i]));
		}

		this.registerLayout(options);
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorRequisiteSelector.prototype.getItemData = function(itemId)
	{
		var items = this._requisiteInfo.getItems();
		for(var i = 0, length = items.length; i < length; i++)
		{
			var itemData = items[i];
			if(itemId === BX.prop.getInteger(itemData, "requisiteId", 0))
			{
				return itemData;
			}
		}
		return null;
	};
	BX.Crm.EntityEditorRequisiteSelector.prototype.prepareItemLayout = function(itemData)
	{
		var viewData = BX.prop.getObject(itemData, "viewData", null);
		if(!viewData)
		{
			return;
		}

		var isSelected = BX.prop.getBoolean(itemData, "selected", false);

		var prefix  = this.getPrefix();
		var itemId = BX.prop.getInteger(itemData, "requisiteId", 0);

		var wrapper = BX.create("label", { props: { className: "crm-entity-requisites-select-item" } });
		wrapper.appendChild(BX.create("strong", { text: BX.prop.getString(viewData, "title", "") }));
		if(isSelected)
		{
			BX.addClass(wrapper, "crm-entity-requisites-select-item-selected");
		}
		this._itemWrappers[itemId] = wrapper;

		var i, length;

		var fields = BX.prop.getArray(viewData, "fields", []);
		for(i = 0, length = fields.length; i < length; i++)
		{
			var field = fields[i];

			var fieldTitle = BX.prop.getString(field, "title", "");
			var fieldValue = BX.prop.getString(field, "textValue", "");

			if(fieldTitle !== "" && fieldValue !== "")
			{
				wrapper.appendChild(BX.create("br"));
				wrapper.appendChild(BX.create("span", { text: fieldTitle + ": " + fieldValue }));
			}
		}

		var button = BX.create("input",
			{
				props:
					{
						type: "radio",
						name: prefix + "requisite",
						checked: isSelected,
						className: "crm-entity-requisites-select-item-field"
					},
				attrs: { "data-requisiteid": itemId }
			}
		);
		wrapper.appendChild(button);
		this._itemButtons[itemId] = button;
		BX.bind(button, "change", BX.delegate(this.onItemChange, this));

		var bankDetailList = BX.prop.getArray(itemData, "bankDetailViewDataList", []);

		if(bankDetailList.length > 0)
		{
			var bankDetailWrapper = BX.create("span",
				{
					props: { className: "crm-entity-requisites-select-item-bank-requisites-container" }
				}
			);
			wrapper.appendChild(bankDetailWrapper);
			bankDetailWrapper.appendChild(
				BX.create("span",
					{
						props: { className: "crm-entity-requisites-select-item-bank-requisites-title" },
						html: this.getMessage("bankDetails")
					}
				)
			);

			var bankDetailContainer = BX.create("span",
				{
					props: { className: "crm-entity-requisites-select-item-bank-requisites-field-container" }
				}
			);
			bankDetailWrapper.appendChild(bankDetailContainer);

			this._itemBankDetailButtons[itemId] = {};
			for(i = 0, length = bankDetailList.length; i < length; i++)
			{
				var bankDetailItem = bankDetailList[i];
				var bankDetailItemId = BX.prop.getInteger(bankDetailItem, "pseudoId", 0);

				var bankDetailViewData = BX.prop.getObject(bankDetailItem, "viewData", null);
				if(!bankDetailViewData)
				{
					continue;
				}

				var isBankDetailItemSelected = isSelected && BX.prop.getBoolean(bankDetailItem, "selected", false);

				var bankDetailItemWrapper = BX.create("label",
					{
						props: { className: "crm-entity-requisites-select-item-bank-requisites-field-item" }
					}
				);
				bankDetailContainer.appendChild(bankDetailItemWrapper);

				var bankDetailButton = BX.create("input",
					{
						props:
							{
								type: "radio",
								name: prefix + "bankrequisite" + itemId,
								checked: isBankDetailItemSelected,
								className: "crm-entity-requisites-select-item-bank-requisites-field"
							},
						attrs:
							{
								"data-requisiteid": itemId,
								"data-bankdetailid": bankDetailItemId
							}
					}
				);
				bankDetailItemWrapper.appendChild(bankDetailButton);
				BX.bind(bankDetailButton, "change", BX.delegate(this.onItemBankDetailChange, this));
				this._itemBankDetailButtons[itemId][bankDetailItemId] = bankDetailButton;

				bankDetailItemWrapper.appendChild(
					document.createTextNode(BX.prop.getString(bankDetailViewData, "title", ""))
				);
			}

			wrapper.appendChild(
				BX.create("span", { style: { display: "block", clear: "both" } })
			);
		}

		return wrapper;

	};
	BX.Crm.EntityEditorRequisiteSelector.prototype.clearLayout = function()
	{
		if(!this._hasLayout)
		{
			return;
		}

		this._wrapper = BX.remove(this._wrapper);
		this._itemWrappers = {};
		this._itemButtons = {};
		this._itemBankDetailButtons = {};

		this._hasLayout = false;
	};
	BX.Crm.EntityEditorRequisiteSelector.prototype.save = function()
	{
		this._model.setField("REQUISITE_ID", this._requisiteId, { originator: this });
		this._model.setField("BANK_DETAIL_ID", this._bankDetailId, { originator: this });
	};
	BX.Crm.EntityEditorRequisiteSelector.prototype.onItemChange = function(e)
	{
		var button = BX.getEventTarget(e);
		if(!button.checked)
		{
			return;
		}

		var requisiteId = parseInt(button.getAttribute("data-requisiteid"));
		if(isNaN(requisiteId) || requisiteId <= 0)
		{
			return;
		}

		this._requisiteId = requisiteId;
		this._bankDetailId = 0;

		var itemData = this.getItemData(this._requisiteId);
		var itemBankDetailList = BX.prop.getArray(itemData, "bankDetailViewDataList", []);
		for(var i = 0, length = itemBankDetailList.length; i < length; i++)
		{
			var itemBankDetailItem = itemBankDetailList[i];
			var itemBankDetailItemId = BX.prop.getInteger(itemBankDetailItem, "pseudoId", 0);
			if(itemBankDetailItemId > 0 && BX.prop.getBoolean(itemBankDetailItem, "selected", false))
			{
				this._bankDetailId = itemBankDetailItemId;
				break;
			}
		}

		for(var key in this._itemWrappers)
		{
			if(!this._itemWrappers.hasOwnProperty(key))
			{
				continue;
			}

			var itemWrapper = this._itemWrappers[key];
			var isSelected = this._requisiteId === parseInt(key);
			if(isSelected)
			{
				BX.addClass(itemWrapper, "crm-entity-requisites-select-item-selected");
			}
			else
			{
				BX.removeClass(itemWrapper, "crm-entity-requisites-select-item-selected");
			}

			if(this._itemButtons.hasOwnProperty(key))
			{
				var itemButton = this._itemButtons[key];
				if(itemButton.checked !== isSelected)
				{
					itemButton.checked = isSelected;
				}
			}

			if(this._itemBankDetailButtons.hasOwnProperty(key))
			{
				var itemBankDetailButtons = this._itemBankDetailButtons[key];
				for(var bankDetailItemId in itemBankDetailButtons)
				{
					if(!itemBankDetailButtons.hasOwnProperty(bankDetailItemId))
					{
						continue;
					}

					var isBankDetailItemSelected = isSelected && this._bankDetailId === parseInt(bankDetailItemId);
					var itemBankDetailButton = itemBankDetailButtons[bankDetailItemId];
					if(itemBankDetailButton.checked !== isBankDetailItemSelected)
					{
						itemBankDetailButton.checked = isBankDetailItemSelected;
					}
				}
			}
		}

		this.markAsChanged();
	};
	BX.Crm.EntityEditorRequisiteSelector.prototype.onItemBankDetailChange = function(e)
	{
		var button = BX.getEventTarget(e);
		if(!button.checked)
		{
			return;
		}

		var requisiteId = parseInt(button.getAttribute("data-requisiteid"));
		if(isNaN(requisiteId) || requisiteId <= 0)
		{
			return;
		}

		if(this._requisiteId !== requisiteId)
		{
			return;
		}

		var bankdetailId = parseInt(button.getAttribute("data-bankdetailid"));
		if(isNaN(bankdetailId) || bankdetailId <= 0)
		{
			return;
		}

		this._bankDetailId = bankdetailId;

	};
	if(typeof(BX.Crm.EntityEditorRequisiteSelector.messages) === "undefined")
	{
		BX.Crm.EntityEditorRequisiteSelector.messages = {};
	}
	BX.Crm.EntityEditorRequisiteSelector.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorRequisiteSelector();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorRequisiteListItem === "undefined")
{
	BX.Crm.EntityEditorRequisiteListItem = function()
	{
		this._id = "";
		this._settings = null;
		this._owner = null;
		this._mode = BX.UI.EntityEditorModeintermediate;

		this._data = null;
		this._requisiteId = 0;

		this._container = null;
		this._wrapper = null;
		this._innerWrapper = null;
		this._editButton = null;
		this._deleteButton = null;

		this._hasLayout = false;
	};

	BX.Crm.EntityEditorRequisiteListItem.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = BX.type.isPlainObject(settings) ? settings : {};

				this._owner = BX.prop.get(this._settings, "owner", null);
				this._mode = BX.prop.getInteger(this._settings, "mode", BX.UI.EntityEditorModeintermediate);

				this._data = BX.prop.getObject(this._settings, "data", {});
				this._requisiteId = BX.prop.getInteger(this._data, "requisiteId", 0);

				this._container = BX.prop.getElementNode(this._settings, "container");
			},
			getId: function()
			{
				return this._id;
			},
			getMessage: function(name)
			{
				return BX.prop.getString(BX.Crm.EntityEditorRequisiteListItem.messages, name, name);
			},
			getRequisiteId: function()
			{
				return this._requisiteId;
			},
			getData: function()
			{
				return this._data;
			},
			setData: function(data)
			{
				this._data = data;
			},
			layout: function(options)
			{
				if(this._hasLayout)
				{
					return;
				}

				var viewData = BX.prop.getObject(this._data, "viewData", null);
				if(!viewData)
				{
					viewData = {};
				}

				var isViewMode = this._mode === BX.UI.EntityEditorMode.view;

				this._wrapper = BX.create(
					"div",
					{ props: { className: "crm-entity-widget-client-requisites-container crm-entity-widget-client-requisites-container-opened" } }
				);

				this._innerWrapper = BX.create("dl", { props: { className: "crm-entity-widget-client-requisites-list" } });

				this.prepareViewLayout(viewData, [ "RQ_ADDR" ]);
				this.prepareFieldViewLayout(viewData, "RQ_ADDR");

				var bankDetails = BX.prop.getArray(this._data, "bankDetailViewDataList", []);
				for(var i = 0, length = bankDetails.length; i < length; i++)
				{
					var bankDetail = bankDetails[i];
					if(!BX.prop.getBoolean(bankDetail, "isDeleted", false))
					{
						this.prepareViewLayout(BX.prop.getObject(bankDetail, "viewData", null), []);
					}
				}

				if(!isViewMode)
				{
					this._deleteButton = BX.create(
						"span",
						{
							props: { className: "crm-entity-widget-client-requisites-remove-icon" },
							events: { click: BX.delegate(this.onRemoveButtonClick, this) }
						}
					);

					this._editButton = BX.create(
						"span",
						{
							props: { className: "crm-entity-widget-client-requisites-edit-icon" },
							events: { click: BX.delegate(this.onEditButtonClick, this) }
						}
					);
				}

				this._wrapper.appendChild(
					BX.create(
						"div",
						{
							props: { className: "crm-entity-widget-client-requisites-inner-container" },
							children: [ this._deleteButton, this._editButton, this._innerWrapper ]
						}
					)
				);

				var anchor = BX.prop.getElementNode(options, "anchor", null);
				if(anchor)
				{
					this._container.insertBefore(this._wrapper, anchor);
				}
				else
				{
					this._container.appendChild(this._wrapper);
				}
				this._hasLayout = true;
			},
			prepareViewLayout: function(viewData, skipFields)
			{
				if(!viewData)
				{
					return;
				}

				var title = BX.prop.getString(viewData, "title", "");
				if(title !== "")
				{
					this._innerWrapper.appendChild(
						BX.create("dt",
							{
								props: { className: "crm-entity-widget-client-requisites-name" },
								text: title
							}
						)
					);
				}

				var i, length;
				var skipMap = {};
				if(BX.type.isArray(skipFields))
				{
					for(i = 0, length = skipFields.length; i < length; i++)
					{
						skipMap[skipFields[i]] = true;
					}
				}

				var fieldContent = [];
				var fields = BX.prop.getArray(viewData, "fields", []);
				for(i = 0, length = fields.length; i < length; i++)
				{
					var field = fields[i];
					var name = BX.prop.getString(field, "name", "");
					if(skipMap.hasOwnProperty(name))
					{
						continue;
					}

					var fieldTitle = BX.prop.getString(field, "title", "");
					var fieldValue = BX.prop.getString(field, "textValue", "");
					if(fieldTitle !== "" && fieldValue !== "")
					{
						fieldContent.push(fieldTitle + ": " + fieldValue);
					}
				}

				this._innerWrapper.appendChild(
					BX.create("dd",
						{
							props: { className: "crm-entity-widget-client-requisites-value" },
							text: fieldContent.join(", ")
						}
					)
				);
			},
			prepareFieldViewLayout: function(viewData, fieldName)
			{
				if(!viewData)
				{
					return;
				}

				var fields = BX.prop.getArray(viewData, "fields", []);
				for(var i = 0, length = fields.length; i < length; i++)
				{
					var field = fields[i];
					var name = BX.prop.getString(field, "name", "");

					if(name !== fieldName)
					{
						continue;
					}

					var title = BX.prop.getString(field, "title", "");
					var text = BX.prop.getString(field, "textValue", "");
					if(title === "" || text === "")
					{
						continue;
					}

					this._innerWrapper.appendChild(
						BX.create("dt",
							{
								props: { className: "crm-entity-widget-client-requisites-name" },
								text: title
							}
						)
					);

					this._innerWrapper.appendChild(
						BX.create("dd",
							{
								props: { className: "crm-entity-widget-client-requisites-value" },
								text: text
							}
						)
					);
				}
			},
			clearLayout: function()
			{
				if(!this._hasLayout)
				{
					return;
				}

				this._wrapper = BX.remove(this._wrapper);
				this._innerWrapper = null;
				this._editButton = null;
				this._deleteButton = null;

				this._hasLayout = false;
			},
			getContainer: function()
			{
				return this._container;
			},
			setContainer: function(container)
			{
				this._container = container;
			},
			getWrapper: function()
			{
				return this._wrapper;
			},
			prepareData: function()
			{
				var value = this._labelInput ? BX.util.trim(this._labelInput.value) : "";
				if(value === "")
				{
					return null;
				}

				var data = { "VALUE": value };
				var id = BX.prop.getInteger(this._data, "ID", 0);
				if(id > 0)
				{
					data["ID"] = id;
				}

				var xmlId = BX.prop.getString(this._data, "XML_ID", "");
				if(id > 0)
				{
					data["XML_ID"] = xmlId;
				}

				return data;
			},
			onEditButtonClick: function(e)
			{
				this._owner.onEditItem(this);
			},
			onRemoveButtonClick: function(e)
			{
				var dlg = BX.UI.EditorAuxiliaryDialog.create(
					this._id,
					{
						title: this.getMessage("deleteTitle"),
						content: this.getMessage("deleteConfirm"),
						buttons:
							[
								{
									id: "accept",
									type: BX.Crm.DialogButtonType.accept,
									text: BX.message("CRM_EDITOR_DELETE"),
									callback: BX.delegate(this.onRemovalConfirmationDialogButtonClick, this)
								},
								{
									id: "cancel",
									type: BX.Crm.DialogButtonType.cancel,
									text: BX.message("CRM_EDITOR_CANCEL"),
									callback: BX.delegate(this.onRemovalConfirmationDialogButtonClick, this)
								}
							]
					}
				);
				dlg.open();
				this._owner.onOpenItemRemovalConfirmation(this);
			},
			onRemovalConfirmationDialogButtonClick: function(button)
			{
				var dlg = button.getDialog();
				if(button.getId() === "accept")
				{
					this._owner.onRemoveItem(this);
				}
				dlg.close();
				this._owner.onCloseItemRemovalConfirmation(this);
			}
		};
	if(typeof(BX.Crm.EntityEditorRequisiteListItem.messages) === "undefined")
	{
		BX.Crm.EntityEditorRequisiteListItem.messages = {};
	}
	BX.Crm.EntityEditorRequisiteListItem.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorRequisiteListItem();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.EntityEditorRequisiteList === "undefined")
{
	BX.Crm.EntityEditorRequisiteList = function()
	{
		BX.Crm.EntityEditorRequisiteList.superclass.constructor.apply(this);
		this._items = null;

		this._data = null;
		this._externalContext = null;
		this._externalEventHandler = null;

		this._createButton = null;

		this._dataInputs = {};
		this._dataSignInputs = {};

		this._itemWrapper = null;
		this._dataWrapper = null;

		this._isPresetMenuOpened = false;
		this._newItemIndex = -1;
		this._sliderUrls = {};
	};
	BX.extend(BX.Crm.EntityEditorRequisiteList, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorRequisiteList.prototype.doInitialize = function()
	{
		this.initializeFromModel();
	};
	BX.Crm.EntityEditorRequisiteList.prototype.initializeFromModel = function()
	{
		var value = this.getValue();
		this._data = BX.type.isArray(value) ? BX.clone(value, true) : [];
		var i, length;
		for(i = 0, length = this._data.length; i < length; i++)
		{
			this.prepareRequisiteData(this._data[i]);
		}

		this._requisiteInfo = BX.CrmEntityRequisiteInfo.create(
			{
				requisiteId: 0,
				bankDetailId: 0,
				data: this._data
			}
		);
	};
	BX.Crm.EntityEditorRequisiteList.prototype.processModelChange = function(params)
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

		this.initializeFromModel();
		this.refreshLayout();
	};
	BX.Crm.EntityEditorRequisiteList.prototype.reset = function()
	{
		this.initializeFromModel();

		//Destroy cached requisite sliders
		for(var key in this._sliderUrls)
		{
			if(this._sliderUrls.hasOwnProperty(key))
			{
				BX.Crm.Page.removeSlider(this._sliderUrls[key]);
			}
		}
		this._sliderUrls = {};
	};
	BX.Crm.EntityEditorRequisiteList.prototype.rollback = function()
	{
		if(this.isChanged())
		{
			this.reset();
		}
	};
	BX.Crm.EntityEditorRequisiteList.prototype.doSetMode = function(mode)
	{
		this.rollback();
	};
	BX.Crm.EntityEditorRequisiteList.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorRequisiteList.messages;
		return (m.hasOwnProperty(name)
				? m[name]
				: BX.Crm.EntityEditorRequisiteList.superclass.getMessage.apply(this, arguments)
		);
	};
	BX.Crm.EntityEditorRequisiteList.prototype.prepareDataInputName = function(requisiteKey, fieldName)
	{
		return this.getName() + "[" + requisiteKey.toString() + "]" + "[" + fieldName + "]";
	};
	BX.Crm.EntityEditorRequisiteList.prototype.prepareRequisiteData = function(data)
	{
		var id = BX.prop.getInteger(data, "requisiteId", 0);
		var pseudoId = BX.prop.getString(data, "pseudoId", "");

		if(id > 0)
		{
			data["key"] = id.toString();
			data["isNew"] = false;
			data["isChanged"] = BX.prop.getBoolean(data, "isChanged", false);
		}
		else
		{
			data["key"] = pseudoId;
			data["isNew"] = true;
			data["isChanged"] = BX.prop.getBoolean(data, "isChanged", true);
		}
		data["isDeleted"] = false;
	};
	BX.Crm.EntityEditorRequisiteList.prototype.findRequisiteDataIndexByKey = function(key)
	{
		for(var i = 0, length = this._data.length; i < length; i++)
		{
			if(BX.prop.getString(this._data[i], "key", 0) === key)
			{
				return i;
			}
		}
		return -1;
	};
	BX.Crm.EntityEditorRequisiteList.prototype.getRequisiteDataByKey = function(key)
	{
		var index = this.findRequisiteDataIndexByKey(key);
		return index >= 0 ? this._data[index] : null;
	};
	BX.Crm.EntityEditorRequisiteList.prototype.setupRequisiteData = function(data)
	{
		var key = BX.prop.getString(data, "key", "");
		if(key === "")
		{
			return;
		}

		var index = this.findRequisiteDataIndexByKey(key);
		if(index >= 0)
		{
			this._data[index] = data;
		}
		else
		{
			this._data.push(data);
		}

		this._requisiteInfo = BX.CrmEntityRequisiteInfo.create(
			{
				requisiteId: 0,
				bankDetailId: 0,
				data: this._data
			}
		);
	};
	BX.Crm.EntityEditorRequisiteList.prototype.refreshRequisiteDataInputs = function()
	{
		if(!this._hasLayout)
		{
			return;
		}

		BX.cleanNode(this._dataWrapper);
		for(var i = 0, length = this._data.length; i < length; i++)
		{
			var item = this._data[i];

			var key = BX.prop.getString(item, "key", "");
			if(key === "")
			{
				continue;
			}

			var isChanged = BX.prop.getBoolean(item, "isChanged", false);
			var isDeleted = BX.prop.getBoolean(item, "isDeleted", false);
			if(!isChanged && !isDeleted)
			{
				continue;
			}

			if(isDeleted)
			{
				this._dataWrapper.appendChild(
					BX.create(
						"input",
						{
							props:
								{
									type: "hidden",
									name: this.prepareDataInputName(key, "DELETED"),
									value: "Y"
								}
						}
					)
				);
			}
			else
			{
				var requisiteDataSign = BX.prop.getString(item, "requisiteDataSign", "");
				if(requisiteDataSign !== "")
				{
					this._dataWrapper.appendChild(
						BX.create(
							"input",
							{
								props:
									{
										type: "hidden",
										name: this.prepareDataInputName(key, "SIGN"),
										value: requisiteDataSign
									}
							}
						)
					);
				}

				var requisiteData = BX.prop.getString(item, "requisiteData", "");
				if(requisiteData !== "")
				{
					this._dataWrapper.appendChild(
						BX.create(
							"input",
							{
								props:
									{
										type: "hidden",
										name: this.prepareDataInputName(key, "DATA"),
										value: requisiteData
									}
							}
						)
					);
				}
			}
		}
	};
	BX.Crm.EntityEditorRequisiteList.prototype.hasContentToDisplay = function()
	{
		if(this._mode === BX.UI.EntityEditorMode.edit)
		{
			return true;
		}
		return this._requisiteInfo && this._requisiteInfo.getItems().length > 0;
	};
	BX.Crm.EntityEditorRequisiteList.prototype.layout = function(options)
	{
		if (this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated();
		this.adjustWrapper();

		this._items = [];

		if (!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		var i, length;
		var itemInfos = this._requisiteInfo.getItems();
		for(i = 0, length = itemInfos.length; i < length; i++)
		{
			var  data = itemInfos[i];
			var item = BX.Crm.EntityEditorRequisiteListItem.create(
				BX.prop.getString(data, "key", ""),
				{
					owner: this,
					mode: this._mode,
					data: data
				}
			);
			this._items.push(item);
		}

		if(this.isInEditMode())
		{
			this._dataWrapper = BX.create("div");
			this._wrapper.appendChild(this._dataWrapper);

			this._wrapper.appendChild(this.createTitleNode(this.getTitle()));
			this._itemWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-block-inner crm-entity-widget-content-block-requisites" } });
			this._wrapper.appendChild(this._itemWrapper);
			for(i = 0, length = this._items.length; i < length; i++)
			{
				this._items[i].setContainer(this._itemWrapper);
				this._items[i].layout();
			}

			this._createButton = BX.create(
				"span",
				{
					props: { className: "crm-entity-widget-client-requisites-add-btn" },
					text: BX.message("CRM_EDITOR_ADD")
				}
			);
			this._itemWrapper.appendChild(this._createButton);
			BX.bind(this._createButton, "click", BX.delegate(this.onCreateButtonClick, this));
		}
		else
		{
			this._wrapper.appendChild(this.createTitleNode(this.getTitle()));
			this._itemWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-block-colums-block" } });
			this._wrapper.appendChild(
				BX.create(
					"div",
					{
						props: { className: "crm-entity-widget-content-block-inner" },
						children: [ this._itemWrapper ]
					}
				)
			);

			this._wrapper.appendChild(this._itemWrapper);
			for(i = 0, length = this._items.length; i < length; i++)
			{
				this._items[i].setContainer(this._itemWrapper);
				this._items[i].layout();
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
	BX.Crm.EntityEditorRequisiteList.prototype.doClearLayout = function(options)
	{
		if(this._items)
		{
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				this._items[i].clearLayout();
			}
		}
		this._items = [];

		this._itemWrapper = null;
		this._createButton = null;
	};
	BX.Crm.EntityEditorRequisiteList.prototype.getItemByIndex = function(index)
	{
		return index >= 0 && index <= (this._items.length - 1) ? this._items[index] : null;
	};
	BX.Crm.EntityEditorRequisiteList.prototype.getItemById = function(requisiteId)
	{
		for(var i = 0, length = this._items.length; i < length; i++)
		{
			var item = this._items[i];
			if(item.getId() === requisiteId)
			{
				return item;
			}
		}
		return null;
	};
	BX.Crm.EntityEditorRequisiteList.prototype.getItemCount = function()
	{
		return this._items.length;
	};
	BX.Crm.EntityEditorRequisiteList.prototype.getItemIndex = function(item)
	{
		for(var i = 0, length = this._items.length; i < length; i++)
		{
			if(this._items[i] === item)
			{
				return i;
			}
		}
		return -1;
	};
	BX.Crm.EntityEditorRequisiteList.prototype.removeItemByIndex = function(index)
	{
		if(index < this._items.length)
		{
			this._items.splice(index, 1);
		}
	};
	BX.Crm.EntityEditorRequisiteList.prototype.removeItem = function(item)
	{
		var index = this.getItemIndex(item);
		if(index < 0)
		{
			return;
		}

		var data = this.getRequisiteDataByKey(item.getId());
		if(data)
		{
			data["isDeleted"] = true;
		}
		item.clearLayout();
		this.removeItemByIndex(index);

		this.refreshRequisiteDataInputs();
		this.markAsChanged();
	};
	BX.Crm.EntityEditorRequisiteList.prototype.openEditor = function(params)
	{
		var requisiteId = BX.prop.getInteger(params, "requisiteId", 0);
		var contextId = this._editor.getContextId();

		var urlParams =
			{
				etype: this._editor.getEntityTypeId(),
				eid: this._editor.getEntityId(),
				external_context_id: contextId
			};

		var presetId = BX.prop.getInteger(params, "presetId", 0);
		if(presetId > 0)
		{
			urlParams["pid"] = presetId;
		}

		var pseudoId = "";
		if(requisiteId <= 0)
		{
			this._newItemIndex++;
			pseudoId = "n" + this._newItemIndex.toString();
			urlParams["pseudo_id"] = pseudoId;
		}

		var url = BX.util.add_url_param(
			this._editor.getRequisiteEditUrl(requisiteId),
			urlParams
		);

		if(!this._externalEventHandler)
		{
			this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
			BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
		}

		if(!this._externalContext)
		{
			this._externalContext = {};
		}

		if(requisiteId > 0)
		{
			this._externalContext[requisiteId] = { requisiteId: requisiteId, url: url };
		}
		else
		{
			this._externalContext[pseudoId] = { pseudoId: pseudoId, url: url };
		}

		if(requisiteId > 0)
		{
			this._sliderUrls[requisiteId] = url;
		}

		BX.Crm.Page.openSlider(url, { width: 950 });
	};

	/*
	BX.Crm.EntityEditorRequisiteList.prototype.loadEditor = function(params)
	{
		var requisiteId = BX.prop.getInteger(params, "requisiteId", 0);
		var contextId = this._editor.getContextId();

		var urlParams =
			{
				etype: this._editor.getEntityTypeId(),
				eid: this._editor.getEntityId(),
				external_context_id: contextId
			};

		var presetId = BX.prop.getInteger(params, "presetId", 0);
		if(presetId > 0)
		{
			urlParams["pid"] = presetId;
		}

		var pseudoId = "";
		if(requisiteId <= 0)
		{
			this._newItemIndex++;
			pseudoId = "n" + this._newItemIndex.toString();
			urlParams["pseudo_id"] = pseudoId;
		}

		var url = BX.util.add_url_param(
			this._editor.getRequisiteEditUrl(requisiteId),
			urlParams
		);

		if(!this._externalEventHandler)
		{
			this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
			BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
		}

		if(!this._externalContext)
		{
			this._externalContext = {};
		}

		if(requisiteId > 0)
		{
			this._externalContext[requisiteId] = { requisiteId: requisiteId, url: url };
		}
		else
		{
			this._externalContext[pseudoId] = { pseudoId: pseudoId, url: url };
		}

		var promise = new top.BX.Promise();
		var onEditorLoad = function(data)
		{
			var node = top.document.createElement("div");
			node.innerHTML = data;
			promise.fulfill(node);
		};
		BX.ajax(
			{
				'method': 'POST',
				'dataType': 'html',
				'url': url,
				'processData': false,
				'data':  {},
				'onsuccess': onEditorLoad
			}
		);

		return promise;
	};
	*/
	BX.Crm.EntityEditorRequisiteList.prototype.onEditItem = function(item)
	{
		this.openEditor( { requisiteId: item.getRequisiteId() });
	};
	BX.Crm.EntityEditorRequisiteList.prototype.onRemoveItem = function(item)
	{
		this.removeItem(item);
	};
	BX.Crm.EntityEditorRequisiteList.prototype.onOpenItemRemovalConfirmation = function(item)
	{
		if(this._singleEditController)
		{
			this._singleEditController.setActiveDelayed(false);
		}
	};
	BX.Crm.EntityEditorRequisiteList.prototype.onCloseItemRemovalConfirmation = function(item)
	{
		if(this._singleEditController)
		{
			this._singleEditController.setActiveDelayed(true);
		}
	};
	BX.Crm.EntityEditorRequisiteList.prototype.onExternalEvent = function(params)
	{
		var key = BX.type.isNotEmptyString(params["key"]) ? params["key"] : "";
		if(key !== "BX.Crm.RequisiteSliderEditor:onSave")
		{
			return;
		}

		var value = BX.type.isPlainObject(params["value"]) ? params["value"] : {};
		var contextId = BX.prop.getString(value, "context", "");
		if(contextId !== this._editor.getContextId())
		{
			return;
		}

		var presetId = BX.prop.getInteger(value, "presetId", 0);
		var pseudoId = BX.prop.getString(value, "pseudoId", "");
		var requisiteId = BX.prop.getInteger(value, "requisiteId", 0);
		var requisiteDataSign = BX.prop.getString(value, "requisiteDataSign", "");
		var requisiteData = BX.prop.getString(value, "requisiteData", "");

		var itemData =
			{
				entityTypeId: this._editor.getEntityTypeId(),
				entityId: this._editor.getEntityId(),
				presetId: presetId,
				pseudoId: pseudoId,
				requisiteId: requisiteId,
				requisiteData: requisiteData,
				requisiteDataSign: requisiteDataSign,
				isChanged: true
			};

		this.prepareRequisiteData(itemData);
		this.setupRequisiteData(itemData);
		this.refreshRequisiteDataInputs();
		this.markAsChanged();

		var requisiteKey = BX.prop.getString(itemData, "key", "");
		var contextData = BX.prop.getObject(this._externalContext, requisiteKey, null);
		if(!contextData)
		{
			return;
		}

		var item = this.getItemById(requisiteKey);
		var layoutOptions;
		if(item)
		{
			item.setData(itemData);
			item.clearLayout();
			layoutOptions = {};
			var itemIndex = this.getItemIndex(item);
			if(itemIndex < (this.getItemCount() - 1))
			{
				layoutOptions["anchor"] = this.getItemByIndex(itemIndex + 1).getWrapper();
			}
			else if(this._createButton)
			{
				layoutOptions["anchor"] = this._createButton;
			}
			item.layout(layoutOptions);
		}
		else
		{
			item = BX.Crm.EntityEditorRequisiteListItem.create(
				requisiteKey,
				{
					owner: this,
					mode: this._mode,
					data: itemData,
					container: this._itemWrapper
				}
			);
			this._items.push(item);
			layoutOptions = {};
			if(this._createButton)
			{
				layoutOptions["anchor"] = this._createButton;
			}
			item.layout(layoutOptions);
		}

		var url = BX.prop.getString(contextData, "url", "");
		if(url !== "")
		{
			BX.Crm.Page.closeSlider(url, true);
		}

		delete this._externalContext[requisiteId];
	};
	BX.Crm.EntityEditorRequisiteList.prototype.onCreateButtonClick = function(e)
	{
		this.togglePresetMenu();
	};
	BX.Crm.EntityEditorRequisiteList.prototype.togglePresetMenu = function()
	{
		if(this._isPresetMenuOpened)
		{
			this.closePresetMenu();
		}
		else
		{
			this.openPresetMenu();
		}
	};
	BX.Crm.EntityEditorRequisiteList.prototype.openPresetMenu = function()
	{
		if(this._isPresetMenuOpened)
		{
			return;
		}

		var menu = [];
		var items = BX.prop.getArray(this._schemeElement.getData(), "presets");
		for(var i = 0, length = items.length; i < length; i++)
		{
			var item = items[i];
			var value = BX.prop.getString(item, "VALUE", i);
			var name = BX.prop.getString(item, "NAME", value);
			menu.push(
				{
					text: name,
					value: value,
					onclick: BX.delegate( this.onPresetSelect, this)
				}
			);
		}

		BX.PopupMenu.show(
			this._id,
			this._createButton,
			menu,
			{
				angle: false,
				events:
					{
						onPopupShow: BX.delegate( this.onPresetMenuShow, this),
						onPopupClose: BX.delegate( this.onPresetMenuClose, this)
					}
			}
		);
		//BX.PopupMenu.currentItem.popupWindow.setWidth(BX.pos(this._selectContainer)["width"]);
	};
	BX.Crm.EntityEditorRequisiteList.prototype.closePresetMenu = function()
	{
		if(!this._isPresetMenuOpened)
		{
			return;
		}

		var menu = BX.PopupMenu.getMenuById(this._id);
		if(menu)
		{
			menu.popupWindow.close();
		}
	};
	BX.Crm.EntityEditorRequisiteList.prototype.onPresetMenuShow = function()
	{
		this._isPresetMenuOpened = true;
	};
	BX.Crm.EntityEditorRequisiteList.prototype.onPresetMenuClose = function()
	{
		BX.PopupMenu.destroy(this._id);
		this._isPresetMenuOpened = false;
	};
	BX.Crm.EntityEditorRequisiteList.prototype.onPresetSelect = function(e, item)
	{
		this.openEditor({ presetId: item.value });
		this.closePresetMenu();
	};
	BX.Crm.EntityEditorRequisiteList.prototype.save = function()
	{
	};
	if(typeof(BX.Crm.EntityEditorRequisiteList.messages) === "undefined")
	{
		BX.Crm.EntityEditorRequisiteList.messages = {};
	}
	BX.Crm.EntityEditorRequisiteList.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorRequisiteList();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof BX.Crm.ClientEditorEntityRequisitePanel === "undefined")
{
	BX.Crm.ClientEditorEntityRequisitePanel = function()
	{
		this._id = "";
		this._settings = {};

		this._editor = null;

		this._entityInfo = null;
		this._requisiteInfo = null;

		this._mode = BX.UI.EntityEditorModeintermediate;

		this._selectedRequisiteId = 0;
		this._selectedBankDetailId = 0;

		this._container = null;
		this._wrapper = null;
		this._contentWrapper = null;

		this._requisiteInput = null;
		this._bankDetailInput = null;

		this._toggleButton = null;
		this._editButton = null;

		this._toggleButtonHandler = BX.delegate(this.onToggleButtonClick, this);
		this._editButtonHandler = BX.delegate(this.onEditButtonClick, this);

		this._isExpanded = false;
		this._hasLayout = false;

		this._externalEventHandler = BX.delegate(this.onExternalEvent, this);

		this._changeNotifier = null;
	};
	BX.Crm.ClientEditorEntityRequisitePanel.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};

				this._editor = BX.prop.get(this._settings, "editor");

				this._container = BX.prop.getElementNode(this._settings, "container", null);
				this._mode = BX.prop.getInteger(this._settings, "mode", 0);

				this._entityInfo = BX.prop.get(this._settings, "entityInfo", null);
				this._requisiteInfo = BX.prop.get(this._settings, "requisiteInfo", null);

				this._selectedRequisiteId = this._requisiteInfo.getRequisiteId();
				this._selectedBankDetailId = this._requisiteInfo.getBankDetailId();

				this._changeNotifier = BX.CrmNotifier.create(this);

				if(BX.Crm.ClientEditorEntityRequisitePanel.options.hasOwnProperty(this._id))
				{
					this._isExpanded = BX.prop.getBoolean(
						BX.Crm.ClientEditorEntityRequisitePanel.options[this._id],
						"expanded",
						false
					);
				}
			},
			getMessage: function(name)
			{
				var m = BX.Crm.ClientEditorEntityRequisitePanel.messages;
				return m.hasOwnProperty(name) ? m[name] : name;
			},
			getContainer: function()
			{
				return this._container;
			},
			setContainer: function(container)
			{
				this._container = container;
			},
			isExpanded: function()
			{
				return this._isExpanded;
			},
			setExpanded: function(expand)
			{
				expand = !!expand;
				if(this._isExpanded === expand)
				{
					return;
				}
				this._isExpanded = expand;

				if(!BX.Crm.ClientEditorEntityRequisitePanel.options.hasOwnProperty(this._id))
				{
					BX.Crm.ClientEditorEntityRequisitePanel.options[this._id] = {};
				}
				BX.Crm.ClientEditorEntityRequisitePanel.options[this._id]["expanded"] = this._isExpanded;

				if(expand)
				{
					BX.addClass(this._wrapper, "crm-entity-widget-client-requisites-container-opened");
				}
				else
				{
					BX.removeClass(this._wrapper, "crm-entity-widget-client-requisites-container-opened");
				}
			},
			toggle: function()
			{
				this.setExpanded(!this._isExpanded);
			},
			addChangeListener: function(listener)
			{
				this._changeNotifier.addListener(listener);
			},
			removeChangeListener: function(listener)
			{
				this._changeNotifier.removeListener(listener);
			},
			layout: function()
			{
				if(this._hasLayout)
				{
					return;
				}

				var requisite = null;
				var bankDetail = null;

				var requisiteId = this._selectedRequisiteId;
				var bankDetailId = this._selectedBankDetailId;

				if(requisiteId > 0)
				{
					requisite = this._requisiteInfo.getItemById(requisiteId);
				}

				if(!requisite)
				{
					requisite = this._requisiteInfo.getSelectedItem();
				}

				if(!requisite)
				{
					requisite = this._requisiteInfo.getFirstItem();
				}

				if(requisite)
				{
					if(bankDetailId > 0)
					{
						bankDetail = this._requisiteInfo.getItemBankDetailById(requisiteId, bankDetailId);
					}
					if(!bankDetail)
					{
						bankDetail = this._requisiteInfo.getSelectedItemBankDetail(requisiteId);
					}
					if(!bankDetail)
					{
						bankDetail = this._requisiteInfo.getFirstItemBankDetail(requisiteId);
					}
				}

				var isViewMode = this._mode === BX.UI.EntityEditorMode.view;

				this._wrapper = BX.create("div", { props: { className: "crm-entity-widget-client-requisites-container" } });
				this._container.appendChild(this._wrapper);

				if(this._isExpanded)
				{
					BX.addClass(this._wrapper, "crm-entity-widget-client-requisites-container-opened");
				}

				if(!isViewMode)
				{
					this._requisiteInput = BX.create("input", { props: { type: "hidden", name: "REQUISITE_ID", value: requisiteId } });
					this._wrapper.appendChild(this._requisiteInput);

					this._bankDetailInput = BX.create("input", { props: { type: "hidden", name: "BANK_DETAIL_ID", value: bankDetailId } });
					this._wrapper.appendChild(this._bankDetailInput);
				}

				if(requisite)
				{
					this._toggleButton = BX.create("a",
						{
							props: { className: "crm-entity-widget-client-requisites-show-btn" },
							text: this.getMessage("toggle").toLowerCase()
						}
					);
					this._wrapper.appendChild(this._toggleButton);
					BX.bind(this._toggleButton, "click", this._toggleButtonHandler);

					var innerWrapper = BX.create("div", { props: { className: "crm-entity-widget-client-requisites-inner-container" } });
					this._wrapper.appendChild(innerWrapper);

					if(!isViewMode)
					{
						this._editButton = BX.create("span",
							{ props: { className: "crm-entity-widget-client-requisites-edit-icon" } }
						);
						this._editButton.setAttribute("data-editor-control-type", "button");

						innerWrapper.appendChild(this._editButton);
						BX.bind(this._editButton, "click", this._editButtonHandler);
					}

					this._contentWrapper = BX.create("dl", { props: { className: "crm-entity-widget-client-requisites-list" } });
					innerWrapper.appendChild(this._contentWrapper);

					//HACK: addresses must be rendered as separate items
					var requisiteView = BX.prop.getObject(requisite, "viewData", null);
					this.prepareItemView(requisiteView, ["RQ_ADDR"]);
					this.prepareItemFieldView(requisiteView, "RQ_ADDR");

					if(bankDetail)
					{
						this.prepareItemView(BX.prop.getObject(bankDetail, "viewData", null));
					}
				}

				this._hasLayout = true;
			},
			prepareItemView: function(viewData, skipFields)
			{
				if(!viewData)
				{
					return;
				}

				var fieldTitle = BX.prop.getString(viewData, "title", "");
				if(fieldTitle !== "")
				{
					this._contentWrapper.appendChild(
						BX.create("dt",
							{
								props: { className: "crm-entity-widget-client-requisites-name" },
								text: fieldTitle
							}
						)
					);
				}

				var i, length;
				var skipMap = {};
				if(BX.type.isArray(skipFields))
				{
					for(i = 0, length = skipFields.length; i < length; i++)
					{
						skipMap[skipFields[i]] = true;
					}
				}

				var fieldContent = [];
				var fields = BX.prop.getArray(viewData, "fields", []);
				for(i = 0, length = fields.length; i < length; i++)
				{
					var field = fields[i];
					var name = BX.prop.getString(field, "name", "");
					if(skipMap.hasOwnProperty(name))
					{
						continue;
					}

					var title = BX.prop.getString(field, "title", "");
					var text = BX.prop.getString(field, "textValue", "");
					if(title !== "" && text !== "")
					{
						fieldContent.push(title + ": " + text);
					}
				}

				this._contentWrapper.appendChild(
					BX.create("dd",
						{
							props: { className: "crm-entity-widget-client-requisites-value" },
							text: fieldContent.join(", ")
						}
					)
				);
			},
			prepareItemFieldView: function(viewData, fieldName)
			{
				if(!viewData)
				{
					return;
				}

				var fields = BX.prop.getArray(viewData, "fields", []);
				for(var i = 0, length = fields.length; i < length; i++)
				{
					var field = fields[i];
					var name = BX.prop.getString(field, "name", "");

					if(name !== fieldName)
					{
						continue;
					}

					var title = BX.prop.getString(field, "title", "");
					var text = BX.prop.getString(field, "textValue", "");
					if(title === "" || text === "")
					{
						continue;
					}

					this._contentWrapper.appendChild(
						BX.create("dt",
							{
								props: { className: "crm-entity-widget-client-requisites-name" },
								text: title
							}
						)
					);

					this._contentWrapper.appendChild(
						BX.create("dd",
							{
								props: { className: "crm-entity-widget-client-requisites-value" },
								text: text
							}
						)
					);
				}
			},
			clearLayout: function()
			{
				if(!this._hasLayout)
				{
					return;
				}

				if(this._toggleButton)
				{
					BX.unbind(this._toggleButton, "click", this._toggleButtonHandler);
					this._toggleButton = null;
				}

				if(this._editButton)
				{
					BX.unbind(this._editButton, "click", this._editButtonHandler);
					this._editButton = null;
				}

				this._isExpanded = false;
				this._requisiteInput = null;
				this._bankDetailInput = null;
				this._contentWrapper = null;
				this._wrapper = BX.remove(this._wrapper);
				this._hasLayout = false;
			},
			refreshLayout: function()
			{
				var expanded = this.isExpanded();
				this.clearLayout();
				this.layout();
				this.setExpanded(expanded);
			},
			getRuntimeValue: function()
			{
				return {
					REQUISITE_ID: this._selectedRequisiteId,
					BANK_DETAIL_ID: this._selectedBankDetailId
				}
			},
			onToggleButtonClick: function(e)
			{
				this.toggle();
				return BX.eventReturnFalse(e);
			},
			onEditButtonClick: function(e)
			{
				if(!this._editor)
				{
					return;
				}

				var url = BX.prop.getString(this._settings, "requisiteSelectUrl", "");
				if(url === "" && BX.type.isFunction(this._editor.getEntityRequisiteSelectUrl))
				{
					url = this._editor.getEntityRequisiteSelectUrl(
						this._entityInfo.getTypeName(),
						this._entityInfo.getId()
					);
				}

				if(url !== "")
				{
					url = BX.util.add_url_param(
						url,
						{
							external_context_id: this._editor.getContextId(),
							requisite_id: this._selectedRequisiteId,
							bank_detail_id: this._selectedBankDetailId
						}
					);

					BX.Crm.Page.openSlider(url);
					BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
				}

				BX.eventCancelBubble(e);
			},
			onExternalEvent: function(params)
			{
				if(this._mode === BX.UI.EntityEditorMode.view)
				{
					return;
				}

				var key = BX.type.isNotEmptyString(params["key"]) ? params["key"] : "";
				var value = BX.type.isPlainObject(params["value"]) ? params["value"] : {};

				if(!(this._editor && this._editor.getContextId() === BX.prop.getString(value, "context")))
				{
					return;
				}

				if(key === "BX.Crm.EntityRequisiteSelector:onCancel")
				{
					BX.removeCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
				}
				else if(key === "BX.Crm.EntityRequisiteSelector:onSave")
				{
					BX.removeCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);

					var requisiteId = BX.prop.getInteger(value, "requisiteId");
					if(requisiteId > 0)
					{
						this._selectedRequisiteId = requisiteId;
						if(this._requisiteInput)
						{
							this._requisiteInput.value = this._selectedRequisiteId;
						}
					}

					var bankDetailId = BX.prop.getInteger(value, "bankDetailId");
					if(bankDetailId)
					{
						this._selectedBankDetailId = bankDetailId;
						if(this._bankDetailInput)
						{
							this._bankDetailInput.value = this._selectedBankDetailId;
						}
					}

					this._changeNotifier.notify(
						[
							{
								requisiteId: this._selectedRequisiteId,
								bankDetailId: this._selectedBankDetailId
							}
						]
					);

					this.refreshLayout();
				}
			}
		};
	if(typeof(BX.Crm.ClientEditorEntityRequisitePanel.messages) === "undefined")
	{
		BX.Crm.ClientEditorEntityRequisitePanel.messages = {};
	}
	BX.Crm.ClientEditorEntityRequisitePanel.options = {};
	BX.Crm.ClientEditorEntityRequisitePanel.create = function(id, settings)
	{
		var self = new BX.Crm.ClientEditorEntityRequisitePanel();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.Crm.RequisiteNavigator) === "undefined")
{
	BX.Crm.RequisiteNavigator = function()
	{
		this._id = null;
		this._settings = {};

		this._requisite = null;
		this._bankDetail = null;
		this._bankDetailList = null;

		this._closingNotifier = null;

		this._nextButton = null;
		this._nextButtonHandler = BX.delegate(this.onNextButtonClick, this);

		this._wrapper = null;
		this._innerWrapper = null;
		this._titleContainer = null;
		this._contentContainer = null;
		this._bankDetailContainer = null;
		this._popup = null;

		this._isOpened = false;
		this._isExpanded = true;
		this._hasLayout = false;
	};

	BX.Crm.RequisiteNavigator.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};

				this._requisiteInfo = BX.prop.get(settings, "requisiteInfo");

				var requisiteId = this._requisiteInfo.getRequisiteId();
				var bankDetailId = this._requisiteInfo.getBankDetailId();

				this._requisite = requisiteId > 0 ? this._requisiteInfo.getItemById(requisiteId) : null;
				if(!this._requisite)
				{
					this._requisite = this._requisiteInfo.getSelectedItem();
				}
				if(!this._requisite)
				{
					this._requisite = this._requisiteInfo.getFirstItem();
				}

				if(this._requisite)
				{
					this._bankDetailList = this._requisiteInfo.getItemBankDetailList(requisiteId);
					if(this._bankDetailList)
					{
						if(bankDetailId > 0)
						{
							this._bankDetail = this._bankDetailList.getItemById(bankDetailId);
						}
						if(!this._bankDetail)
						{
							this._bankDetail = this._bankDetailList.getSelectedItem();
						}
						if(!this._bankDetail)
						{
							this._bankDetail = this._bankDetailList.getFirstItem();
						}
					}
				}

				this._closingNotifier = BX.CrmNotifier.create(this);
			},
			getId: function()
			{
				return this._id;
			},
			getMessage: function(name)
			{
				return BX.prop.getString(BX.Crm.RequisiteNavigator.messages, name, name);
			},
			addClosingListener: function(listener)
			{
				this._closingNotifier.addListener(listener);
			},
			removeClosingListener: function(listener)
			{
				this._closingNotifier.removeListener(listener);
			},
			isOpened: function()
			{
				return this._isOpened;
			},
			open: function(anchor)
			{
				if(this._isOpened)
				{
					return;
				}

				var offsetLeft = 0, offsetTop = 0;
				if(BX.type.isElementNode(anchor))
				{
					offsetLeft = anchor.offsetWidth + 15;
					offsetTop = -(anchor.offsetHeight + 30);
				}

				this._popup = new BX.PopupWindow(
					this._id,
					anchor,
					{
						autoHide: true,
						draggable: false,
						offsetLeft: offsetLeft,
						offsetTop: offsetTop,
						noAllPaddings: true,
						bindOptions: { forceBindPosition: true },
						closeByEsc: true,
						events:
							{
								onPopupShow: BX.delegate(this.onPopupShow, this),
								onPopupClose: BX.delegate(this.onPopupClose, this),
								onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
							},
						content: this.prepareContent()
					}
				);
				//this._popup.setAngle({ position: "left" });
				this._popup.show();
			},
			close: function()
			{
				if(!this._isOpened)
				{
					return;
				}

				if(this._popup)
				{
					this._popup.close();
				}
			},
			prepareContent: function()
			{
				this._wrapper = BX.create("div", { props: { className: "crm-entity-widget-client-requisites-wrap" } });
				this._titleContainer = BX.create("div", { props: { className: "crm-entity-widget-client-requisites-info-box" } });
				this._wrapper.appendChild(this._titleContainer);

				this._requisiteTitleWrapper = BX.create("div", { props: { className: "crm-entity-widget-client-requisites-info-wrapper" } });
				this._titleContainer.appendChild(this._requisiteTitleWrapper);

				this._nextButton = BX.create("div",
					{
						props: { className: "crm-entity-widget-client-requisites-arrow-right" },
						children:
							[
								BX.create("div",
									{ props: { className: "crm-entity-widget-client-requisites-arrow-right-item" } }
								)
							]
					}
				);
				this._titleContainer.appendChild(this._nextButton);
				BX.bind(this._nextButton, "click", this._nextButtonHandler);

				this._contentWrapper = BX.create("div", { props: { className: "crm-entity-widget-client-requisites-box crm-entity-widget-client-requisites-box-active" } });
				this._wrapper.appendChild(this._contentWrapper);

				this._contentInnerWrapper = BX.create("div", { props: { className: "crm-entity-widget-client-requisites-box-inner" } });
				this._contentWrapper.appendChild(this._contentInnerWrapper);

				this._requisiteWrapper = BX.create("div", { props: { className: "crm-entity-widget-client-requisites-list-container" } });
				this._contentInnerWrapper.appendChild(this._requisiteWrapper);

				this._bankDetailWrapper = BX.create("div", { props: { className: "crm-entity-widget-client-requisites-list-container" } });
				this._contentInnerWrapper.appendChild(this._bankDetailWrapper);

				this.renderRequisites();

				return this._wrapper;
			},
			renderTitleFields: function(fields, container)
			{
				for(var i = 0, length = fields.length; i < length; i++)
				{
					var field = fields[i];

					var title = BX.prop.getString(field, "title", "");
					var text = BX.prop.getString(field, "textValue", "");
					if(title === "" || text === "")
					{
						continue;
					}

					container.appendChild(
						BX.create("div",
							{
								props: { className: "crm-entity-widget-client-requisites-info-desc" },
								text: title
							}
						)
					);

					container.appendChild(
						BX.create("div",
							{
								props: { className: "crm-entity-widget-client-requisites-info-content" },
								children:
									[
										BX.create("div",
											{
												props: { className: "crm-entity-widget-client-requisites-info-content-item" },
												text: text
											}
										)
									]
							}
						)
					);
				}
			},
			renderContentFields: function(fields, caption, container)
			{
				var wrapper = BX.create("div", { props: { className: "crm-entity-widget-client-requisites-list" } });
				container.appendChild(wrapper);

				var innerWrapper = BX.create("div",
					{
						props: { className: "crm-entity-widget-client-requisites-item" }
					}
				);
				wrapper.appendChild(innerWrapper);

				innerWrapper.appendChild(
					BX.create("div",
						{
							props: { className: "crm-entity-widget-client-requisites-name" },
							text: caption
						}
					)
				);

				var values = [];
				for(var i = 0, length = fields.length; i < length; i++)
				{
					var field = fields[i];

					var title = BX.prop.getString(field, "title", "");
					var text = BX.prop.getString(field, "textValue", "");

					if(title !== "" && text !== "")
					{
						values.push(title + ": " + text);
					}
				}

				if(values.length > 0)
				{
					innerWrapper.appendChild(
						BX.create("div",
							{
								props: { className: "crm-entity-widget-client-requisites-value" },
								text: values.join(", ")
							}
						)
					);
				}
				else
				{
					BX.addClass(wrapper, "crm-entity-widget-client-requisites-empty-value");
					innerWrapper.appendChild(document.createTextNode(this.getMessage("stub")));
				}
			},
			renderRequisites: function()
			{
				BX.cleanNode(this._requisiteTitleWrapper);
				BX.cleanNode(this._requisiteWrapper);

				this._nextButton.style.display = this._requisiteInfo.getItemCount() > 1 ? "" : "none";

				if(this._requisite)
				{
					var viewData = BX.prop.getObject(this._requisite, "viewData", {});
					var fields = BX.prop.getArray(viewData, "fields", []);
					var titleFields = [];
					var contentFields = [];
					for(var i = 0, length = fields.length; i < length; i++)
					{
						var field = fields[i];
						var fieldName = BX.prop.getString(field, "name", "");
						if(fieldName === "RQ_ADDR")
						{
							titleFields.push(field);
						}
						else
						{
							contentFields.push(field);
						}
					}

					this._requisiteTitleWrapper.appendChild(
						BX.create("div",
							{
								props: { className: "crm-entity-widget-client-requisites-info-title" },
								text: BX.prop.getString(viewData, "title", "")
							}
						)
					);

					this.renderTitleFields(titleFields, this._requisiteTitleWrapper);
					this.renderContentFields(contentFields, "", this._requisiteWrapper);

					this.renderBankDetails();
				}
			},
			renderBankDetails: function()
			{
				BX.cleanNode(this._bankDetailWrapper);

				if(this._bankDetailList && this._bankDetail)
				{
					var viewData = BX.prop.getObject(this._bankDetail, "viewData", {});
					this.renderContentFields(
						BX.prop.getArray(viewData, "fields", []),
						BX.prop.getString(viewData, "title", ""),
						this._bankDetailWrapper
					);

					var bankDetailQty = this._bankDetailList.getItemCount();
					if(bankDetailQty > 1)
					{
						var bankDetailControlContainer = BX.create("div", { props: { className: "crm-entity-widget-client-requisites-control-box" } });
						this._bankDetailWrapper.appendChild(bankDetailControlContainer);

						bankDetailControlContainer.appendChild(
							BX.create("div",
								{
									props: { className: "crm-entity-widget-client-requisites-control-value" },
									text: this.getMessage("legend")
										.replace(/#NUMBER#/gi, this._bankDetailList.getItemIndex(this._bankDetail) + 1).toString()
										.replace(/#TOTAL#/gi, bankDetailQty.toString())
								}
							)
						);
						bankDetailControlContainer.appendChild(
							BX.create("div",
								{
									props: { className: "crm-entity-widget-client-requisites-control-btn" },
									html: this.getMessage("next") + "&rarr;",
									events: { click: BX.delegate(this.onNextBankDetailButtonClick, this) }
								}
							)
						);
					}
				}
			},
			getSelectedItemId: function()
			{
				return this._requisite ? BX.CrmEntityRequisiteInfo.resolveItemId(this._requisite) : 0;
			},
			getSelectedBankDetailId: function()
			{
				return this._bankDetail ? BX.CrmEntityBankDetailList.resolveItemId(this._bankDetail) : 0;
			},
			showNextItem: function()
			{
				if(!(this._requisiteInfo && this._requisite))
				{
					return;
				}

				var count = this._requisiteInfo.getItemCount();
				if(count === 0)
				{
					return;
				}

				var index = this._requisiteInfo.getItemIndex(this._requisite);
				if(index < 0)
				{
					index = 0;
				}

				index++;
				if(index === count)
				{
					index = 0;
				}

				this._requisite = this._requisiteInfo.getItemByIndex(index);

				if(this._requisite)
				{
					var requisiteId = BX.CrmEntityRequisiteInfo.resolveItemId(this._requisite);
					this._bankDetailList = this._requisiteInfo.getItemBankDetailList(requisiteId);
					if(this._bankDetailList)
					{
						this._bankDetail = this._bankDetailList.getSelectedItem();
						if(!this._bankDetail)
						{
							this._bankDetail = this._bankDetailList.getFirstItem();
						}
					}
				}

				this.renderRequisites();
			},
			showNextBankDetail: function()
			{
				if(!(this._bankDetailList && this._bankDetail))
				{
					return;
				}

				var count = this._bankDetailList.getItemCount();
				if(count === 0)
				{
					return;
				}

				var index = this._bankDetailList.getItemIndex(this._bankDetail);
				if(index < 0)
				{
					index = 0;
				}

				index++;
				if(index === count)
				{
					index = 0;
				}

				this._bankDetail = this._bankDetailList.getItemByIndex(index);
				this.renderBankDetails();
			},
			onPopupShow: function()
			{
				this._isOpened = true;
			},
			onPopupClose: function()
			{
				if(this._popup)
				{
					this._popup.destroy();
				}

				this._closingNotifier.notify(
					[
						{
							requisiteId: this.getSelectedItemId(),
							bankDetailId: this.getSelectedBankDetailId()
						}
					]
				);
			},
			onPopupDestroy: function()
			{
				this._isOpened = false;

				this._wrapper = null;
				this._innerWrapper = null;

				this._popup = null;
			},
			onNextButtonClick: function(e)
			{
				this.showNextItem();
			},
			onNextBankDetailButtonClick: function(e)
			{
				this.showNextBankDetail();
			}
		};
	BX.Crm.RequisiteNavigator.options = {};
	if(typeof(BX.Crm.RequisiteNavigator.messages) === "undefined")
	{
		BX.Crm.RequisiteNavigator.messages = {};
	}
	BX.Crm.RequisiteNavigator.create = function(id, settings)
	{
		var self = new BX.Crm.RequisiteNavigator();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorClientLight === "undefined")
{
	BX.Crm.EntityEditorClientLight = function()
	{
		BX.Crm.EntityEditorClientLight.superclass.constructor.apply(this);
		this._map = null;
		this._info = null;

		this._primaryLoaderConfig = null;
		this._secondaryLoaderConfig = null;

		this._dataElements = null;

		this._companyInfos = null;
		this._contactInfos = null;

		this._enableCompanyMultiplicity = false;

		this._companyTitleWrapper = null;
		this._contactTitleWrapper = null;

		this._companySearchBoxes = null;
		this._contactSearchBoxes = null;

		this._companyPanels = null;
		this._contactPanels = null;

		this._companyWrapper = null;
		this._contactWrapper = null;

		this._addCompanyButton = null;
		this._addContactButton = null;

		this._innerWrapper = null;

		this._layoutType = BX.Crm.EntityEditorClientLayoutType.undefined;
		this._visibleClientFields = null;
		this._enableLayoutTypeChange = false;
		this._enableQuickEdit = null;

		this._companyNameChangeHandler = BX.delegate(this.onCompanyNameChange, this);
		this._companyChangeHandler = BX.delegate(this.onCompanyChange, this);
		this._companyDeletionHandler = BX.delegate(this.onCompanyDelete, this);
		this._companyResetHandler = BX.delegate(this.onCompanyReset, this);
		this._contactNameChangeHandler = BX.delegate(this.onContactNameChange, this);
		this._contactChangeHandler = BX.delegate(this.onContactChange, this);
		this._contactDeletionHandler = BX.delegate(this.onContactDelete, this);
		this._contactResetHandler = BX.delegate(this.onContactReset, this);
		this._requisiteChangeHandler = BX.delegate(this.onRequisiteChange, this);
		this._multifieldChangeHandler = BX.delegate(this.onMultifieldChange, this);
		this._changeRequisiteControlData = {};
	};
	BX.extend(BX.Crm.EntityEditorClientLight, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorClientLight.prototype.doInitialize = function()
	{
		BX.Crm.EntityEditorClientLight.superclass.doInitialize.apply(this);
		this._map = this._schemeElement.getDataObjectParam("map", {});

		this.initializeFromModel();
	};
	BX.Crm.EntityEditorClientLight.prototype.initializeFromModel = function()
	{
		this._companyInfos = BX.Collection.create();
		this._contactInfos = BX.Collection.create();

		this._info = this._model.getSchemeField(this._schemeElement, "info", {});
		this.initializeEntityInfos(BX.prop.getArray(this._info, "COMPANY_DATA", []), this._companyInfos);
		this.initializeEntityInfos(BX.prop.getArray(this._info, "CONTACT_DATA", []), this._contactInfos);

		this._enableCompanyMultiplicity = this._schemeElement.getDataBooleanParam("enableCompanyMultiplicity", false);

		var loaders = this._schemeElement.getDataObjectParam("loaders", {});
		this._primaryLoaderConfig = BX.prop.getObject(loaders, "primary", {});
		this._secondaryLoaderConfig = BX.prop.getObject(loaders, "secondary", {});

		//region Layout Type
		this._enableLayoutTypeChange = true;

		var fixedLayoutTypeName = this._schemeElement.getDataStringParam("fixedLayoutType", "");
		if(fixedLayoutTypeName !== "")
		{
			var fixedLayoutType = BX.Crm.EntityEditorClientLayoutType.resolveId(fixedLayoutTypeName);
			if(fixedLayoutType !== BX.Crm.EntityEditorClientLayoutType.undefined)
			{
				this._layoutType = fixedLayoutType;
				this._enableLayoutTypeChange = false;
			}
		}
		//endregion
	};
	BX.Crm.EntityEditorClientLight.prototype.initializeEntityInfos = function(sourceData, collection)
	{
		for(var i = 0, length = sourceData.length; i < length; i++)
		{
			var info = BX.CrmEntityInfo.create(sourceData[i]);
			if(info.getId() > 0)
			{
				collection.add(info);
			}
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.createDataElement = function(key, value)
	{
		var name = BX.prop.getString(this._map, key, "");

		if(name === "")
		{
			return;
		}

		var input = BX.create("input", { attrs: { name: name, type: "hidden" } });
		if(BX.type.isNotEmptyString(value))
		{
			input.value = value;
		}

		if(!this._dataElements)
		{
			this._dataElements = {};
		}

		this._dataElements[key] = input;
		if(this._wrapper)
		{
			this._wrapper.appendChild(input);
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.getMessage = function(name)
	{
		var m = BX.Crm.EntityEditorClientLight.messages;
		return (m.hasOwnProperty(name)
				? m[name]
				: BX.Crm.EntityEditorClientLight.superclass.getMessage.apply(this, arguments)
		);
	};
	BX.Crm.EntityEditorClientLight.prototype.getOwnerTypeName = function()
	{
		return this._editor.getEntityTypeName();
	};
	BX.Crm.EntityEditorClientLight.prototype.getOwnerTypeId = function()
	{
		return this._editor.getEntityTypeId();
	};
	BX.Crm.EntityEditorClientLight.prototype.getOwnerId = function()
	{
		return this._editor.getEntityId();
	};
	BX.Crm.EntityEditorClientLight.prototype.hasCompanies = function()
	{
		return this._companyInfos !== null && this._companyInfos.length() > 0;
	};
	BX.Crm.EntityEditorClientLight.prototype.hasContacts = function()
	{
		return this._contactInfos !== null && this._contactInfos.length() > 0;
	};
	BX.Crm.EntityEditorClientLight.prototype.addCompany = function(entityInfo)
	{
		if(entityInfo instanceof BX.CrmEntityInfo)
		{
			if(!this._companyInfos)
			{
				this._companyInfos = BX.Collection.create();
			}

			this._companyInfos.add(entityInfo);
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.removeCompany = function(entityInfo)
	{
		if(this._companyInfos && (entityInfo instanceof BX.CrmEntityInfo))
		{
			this._companyInfos.remove(entityInfo);
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.addContact = function(entityInfo)
	{
		if(entityInfo instanceof BX.CrmEntityInfo)
		{
			if(!this._contactInfos)
			{
				this._contactInfos = BX.Collection.create();
			}

			this._contactInfos.add(entityInfo);
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.removeContact = function(entityInfo)
	{
		if(this._contactInfos && (entityInfo instanceof BX.CrmEntityInfo))
		{
			this._contactInfos.remove(entityInfo);
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.hasContentToDisplay = function()
	{
		return(
			this.hasCompanies()
			|| (this._contactInfos !== null && this._contactInfos.length() > 0)
		);
	};
	BX.Crm.EntityEditorClientLight.prototype.getContentWrapper = function()
	{
		return this._innerWrapper;
	};
	BX.Crm.EntityEditorClientLight.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.UI.EntityEditorModeSwitchType.common;
		if(mode === BX.UI.EntityEditorMode.edit)
		{
			result |= BX.UI.EntityEditorModeSwitchType.button|BX.UI.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorClientLight.prototype.reset = function()
	{
		this.initializeFromModel();
	};
	BX.Crm.EntityEditorClientLight.prototype.rollback = function()
	{
		if(this.isChanged())
		{
			this.initializeFromModel();
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.getEntityCreateUrl = function(entityTypeName)
	{
		return this._editor.getEntityCreateUrl(entityTypeName);
	};
	BX.Crm.EntityEditorClientLight.prototype.getEntityEditUrl = function(entityTypeName, entityId)
	{
		return this._editor.getEntityEditUrl(entityTypeName, entityId);
	};
	BX.Crm.EntityEditorClientLight.prototype.doSetMode = function(mode)
	{
		this.rollback();
	};
	BX.Crm.EntityEditorClientLight.prototype.doPrepareContextMenuItems = function(menuItems)
	{
		menuItems.push({ delimiter: true });

		if(this._enableLayoutTypeChange)
		{
			var layoutType = this.getLayoutType();
			if(layoutType === BX.Crm.EntityEditorClientLayoutType.companyContact
				|| layoutType === BX.Crm.EntityEditorClientLayoutType.contactCompany
			)
			{
				menuItems.push(
					{
						value: "set_layout_contact",
						text: this.getMessage("disableCompany")
					}
				);

				menuItems.push(
					{
						value: "set_layout_company",
						text: this.getMessage("disableContact")
					}
				);
			}
			else if(layoutType === BX.Crm.EntityEditorClientLayoutType.company)
			{
				menuItems.push(
					{
						value: "set_layout_company_contact",
						text: this.getMessage("enableContact")
					}
				);
			}
			else if(layoutType === BX.Crm.EntityEditorClientLayoutType.contact)
			{
				menuItems.push(
					{
						value: "set_layout_contact_company",
						text: this.getMessage("enableCompany")
					}
				);
			}
			if (this.isClientFieldVisible('ADDRESS'))
			{
				menuItems.push(
					{
						value: "hide_client_field_address",
						text: this.getMessage("disableAddress")
					}
				);
			}
			else
			{
				menuItems.push(
					{
						value: "show_client_field_address",
						text: this.getMessage("enableAddress")
					}
				);
			}
			if (this.isClientFieldVisible('REQUISITES'))
			{
				menuItems.push(
					{
						value: "hide_client_field_requisites",
						text: this.getMessage("disableRequisites")
					}
				);
			}
			else
			{
				menuItems.push(
					{
						value: "show_client_field_requisites",
						text: this.getMessage("enableRequisites")
					}
				);
			}

			if(layoutType === BX.Crm.EntityEditorClientLayoutType.companyContact)
			{
				menuItems.push({ delimiter: true });
				menuItems.push(
					{
						value: "set_layout_contact_company",
						text: this.getMessage("displayContactAtFirst")
					}
				);
			}
			else if(layoutType === BX.Crm.EntityEditorClientLayoutType.contactCompany)
			{
				menuItems.push({ delimiter: true });
				menuItems.push(
					{
						value: "set_layout_company_contact",
						text: this.getMessage("displayCompanyAtFirst")
					}
				);
			}

			menuItems.push({ delimiter: true });
		}

		if(this.isQuickEditEnabled())
		{
			menuItems.push(
				{
					value: "disable_quick_edit",
					text: this.getMessage("disableQuickEdit")
				}
			);
		}
		else
		{
			menuItems.push(
				{
					value: "enable_quick_edit",
					text: this.getMessage("enableQuickEdit")
				}
			);
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.processContextMenuCommand = function(e, command)
	{
		if(command === "set_layout_contact_company")
		{
			window.setTimeout(
				function() { this.setLayoutType(BX.Crm.EntityEditorClientLayoutType.contactCompany) }.bind(this),
				100
			);
		}
		else if(command === "set_layout_company_contact")
		{
			window.setTimeout(
				function() { this.setLayoutType(BX.Crm.EntityEditorClientLayoutType.companyContact) }.bind(this),
				100
			);
		}
		else if(command === "set_layout_contact")
		{
			window.setTimeout(
				function() { this.setLayoutType(BX.Crm.EntityEditorClientLayoutType.contact) }.bind(this),
				100
			);
		}
		else if(command === "set_layout_company")
		{
			window.setTimeout(
				function() { this.setLayoutType(BX.Crm.EntityEditorClientLayoutType.company) }.bind(this),
				100
			);
		}
		else if(command === "hide_client_field_address")
		{
			window.setTimeout(
				function() { this.setClientFieldVisible('ADDRESS', false) }.bind(this),
				100
			);
		}
		else if(command === "show_client_field_address")
		{
			window.setTimeout(
				function() { this.setClientFieldVisible('ADDRESS', true) }.bind(this),
				100
			);
		}
		else if(command === "hide_client_field_requisites")
		{
			window.setTimeout(
				function() { this.setClientFieldVisible('REQUISITES', false) }.bind(this),
				100
			);
		}
		else if(command === "show_client_field_requisites")
		{
			window.setTimeout(
				function() { this.setClientFieldVisible('REQUISITES', true) }.bind(this),
				100
			);
		}
		else if(command === "disable_quick_edit")
		{
			this.enableQuickEdit(false);
		}
		else if(command === "enable_quick_edit")
		{
			this.enableQuickEdit(true);
		}
		BX.Crm.EntityEditorClientLight.superclass.processContextMenuCommand.apply(this, arguments)
	};
	//region Quick Edit
	BX.Crm.EntityEditorClientLight.prototype.isQuickEditEnabled = function()
	{
		if(this._enableQuickEdit === null)
		{
			this._enableQuickEdit = this._editor.getConfigOption("enableQuickEdit", "Y") === "Y";
		}
		return this._enableQuickEdit;
	};
	BX.Crm.EntityEditorClientLight.prototype.enableQuickEdit = function(enable)
	{
		enable = !!enable;

		if(this._enableQuickEdit === null)
		{
			this._enableQuickEdit = this._editor.getConfigOption("enableQuickEdit", "Y") === "Y";
		}

		if(this._enableQuickEdit === enable)
		{
			return;
		}

		this._enableQuickEdit = enable;
		this._editor.setConfigOption("enableQuickEdit", enable ? "Y" : "N");

		var i, length;
		if(this._companySearchBoxes)
		{
			for(i = 0, length = this._companySearchBoxes.length; i < length; i++)
			{
				this._companySearchBoxes[i].enableQuickEdit(enable);
			}
		}

		if(this._contactSearchBoxes)
		{
			for(i = 0, length = this._contactSearchBoxes.length; i < length; i++)
			{
				this._contactSearchBoxes[i].enableQuickEdit(enable);
			}
		}
	};
	//endregion
	//region Layout Type
	BX.Crm.EntityEditorClientLight.prototype.isCompanyEnabled = function()
	{
		var layoutType = this.getLayoutType();
		return (
			layoutType === BX.Crm.EntityEditorClientLayoutType.contactCompany ||
			layoutType === BX.Crm.EntityEditorClientLayoutType.companyContact ||
			layoutType === BX.Crm.EntityEditorClientLayoutType.company
		);
	};
	BX.Crm.EntityEditorClientLight.prototype.isContactEnabled = function()
	{
		var layoutType = this.getLayoutType();
		return (
			layoutType === BX.Crm.EntityEditorClientLayoutType.contactCompany ||
			layoutType === BX.Crm.EntityEditorClientLayoutType.companyContact ||
			layoutType === BX.Crm.EntityEditorClientLayoutType.contact
		);
	};
	BX.Crm.EntityEditorClientLight.prototype.getLayoutType = function()
	{
		if(this._layoutType <= 0)
		{
			var str = this._editor.getConfigOption("client_layout", "");
			var num = parseInt(str);
			if(isNaN(num) || num <= 0)
			{
				num = BX.Crm.EntityEditorClientLayoutType.companyContact;
			}
			this._layoutType = num;
		}
		return this._layoutType;
	};
	BX.Crm.EntityEditorClientLight.prototype.setLayoutType = function(layoutType)
	{
		if(!BX.type.isNumber(layoutType))
		{
			layoutType = parseInt(layoutType);
		}

		if(isNaN(layoutType) || layoutType <= 0)
		{
			return;
		}

		if(layoutType === this._layoutType)
		{
			return;
		}

		this._layoutType = layoutType;

		this._editor.setConfigOption("client_layout", layoutType);
		this.refreshLayout();
	};
	BX.Crm.EntityEditorClientLight.prototype.loadClientVisibleFields = function()
	{
		var savedValue = this._editor.getConfigOption("client_visible_fields", null);
		if(BX.Type.isString(savedValue))
		{
			savedValue = savedValue.split(",");
		}
		else
		{
			savedValue = ['ADDRESS', 'REQUISITES'];
		}
		return savedValue;
	};
	BX.Crm.EntityEditorClientLight.prototype.isClientFieldVisible = function(fieldName)
	{
		if(!BX.Type.isArray(this._visibleClientFields))
		{
			this._visibleClientFields = this.loadClientVisibleFields();
		}
		return (this._visibleClientFields.indexOf(fieldName) > -1);
	};
	BX.Crm.EntityEditorClientLight.prototype.setClientFieldVisible = function(fieldName, visible)
	{
		if(!BX.Type.isArray(this._visibleClientFields))
		{
			this._visibleClientFields = this.loadClientVisibleFields();
		}
		if (visible && this._visibleClientFields.indexOf(fieldName) === -1)
		{
			this._visibleClientFields.push(fieldName);
		}
		if (!visible && this._visibleClientFields.indexOf(fieldName) > -1)
		{
			this._visibleClientFields.splice(this._visibleClientFields.indexOf(fieldName), 1);
		}
		this._editor.setConfigOption("client_visible_fields", this._visibleClientFields.join(','));
		this.refreshLayout();
	};
	BX.Crm.EntityEditorClientLight.prototype.getClientVisibleFieldsList = function(entityTypeName)
	{
		var fieldsParams = this.getClientEditorFieldsParams(entityTypeName);
		var result = ['PHONE', 'EMAIL'];
		if (this.isClientFieldVisible('ADDRESS') && fieldsParams.hasOwnProperty('ADDRESS') && fieldsParams.ADDRESS.isHidden !== true)
		{
			result.push('ADDRESS');
		}
		if (this.isClientFieldVisible('REQUISITES') && fieldsParams.hasOwnProperty('REQUISITES') && fieldsParams.REQUISITES.isHidden !== true)
		{
			result.push('REQUISITES');
		}
		return result;
	};
	//endregion
	BX.Crm.EntityEditorClientLight.prototype.layout = function(options)
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

		if(!this.hasContentToDisplay() && this.isInViewMode())
		{
			this._innerWrapper = BX.create(
				"div",
				{
					props: { className: "crm-entity-widget-inner" },
					text: this.getMessage("isEmpty")
				}
			);
			this._wrapper.appendChild(this._innerWrapper);
		}
		else
		{
			this._innerWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-block-inner" } });
			this._wrapper.appendChild(this._innerWrapper);

			var layoutType = this.getLayoutType();

			if(this.isInEditMode())
			{
				var fieldContainer = BX.create("div", { props: { className: "crm-entity-widget-content-block-field-container" } });
				this._innerWrapper.appendChild(fieldContainer);
				this._innerContainer = BX.create("div", { props: { className: "crm-entity-widget-content-block-field-container-inner" } });
				fieldContainer.appendChild(this._innerContainer);
			}
			else
			{
				BX.addClass(this._wrapper, "crm-entity-widget-participants-block");
				BX.addClass(this._innerWrapper, "crm-entity-widget-inner");
			}

			if(this.isContactEnabled() && this.isCompanyEnabled())
			{
				if(layoutType === BX.Crm.EntityEditorClientLayoutType.contactCompany)
				{
					this.renderContact();
					this.renderCompany();
				}
				else if(layoutType === BX.Crm.EntityEditorClientLayoutType.companyContact)
				{
					this.renderCompany();
					this.renderContact();
				}
			}
			else
			{
				if(this.isContactEnabled())
				{
					this.renderContact();
				}

				if(this.isCompanyEnabled())
				{
					this.renderCompany();
				}
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

		this._entityEditParams = {};
		this._hasLayout = true;
	};
	BX.Crm.EntityEditorClientLight.prototype.createAdditionalWrapperBlock = function()
	{
	};
	BX.Crm.EntityEditorClientLight.prototype.switchToSingleEditMode = function(targetNode)
	{
		this._entityEditParams = {};

		if(this.isInViewMode() && this.isQuickEditEnabled() && BX.type.isElementNode(targetNode))
		{
			var isFound = false;

			if(BX.isParentForNode(this._companyTitleWrapper, targetNode))
			{
				isFound = true;

				this._entityEditParams["enableCompany"] = true;
				this._entityEditParams["companyIndex"] = 0;
			}

			if(!isFound && BX.isParentForNode(this._contactTitleWrapper, targetNode))
			{
				isFound = true;

				this._entityEditParams["enableContact"] = true;
				this._entityEditParams["contactIndex"] = 0;
			}

			var i, length;
			if(!isFound && this._companyPanels !== null)
			{
				for(i = 0, length = this._companyPanels.length; i < length; i++)
				{
					if(this._companyPanels[i].checkOwership(targetNode))
					{
						isFound = true;

						this._entityEditParams["enableCompany"] = true;
						this._entityEditParams["companyIndex"] = i;

						break;
					}
				}
			}

			if(!isFound && this._contactPanels !== null)
			{
				for(i = 0, length = this._contactPanels.length; i < length; i++)
				{
					if(this._contactPanels[i].checkOwership(targetNode))
					{
						isFound = true;

						this._entityEditParams["enableContact"] = true;
						this._entityEditParams["contactIndex"] = i;

						break;
					}
				}
			}

			if(!BX.prop.getBoolean(this._entityEditParams, "enableCompany", false)
				&& !BX.prop.getBoolean(this._entityEditParams, "enableContact", false)
			)
			{
				var layoutType = this.getLayoutType();
				if(layoutType === BX.Crm.EntityEditorClientLayoutType.contact
					|| layoutType === BX.Crm.EntityEditorClientLayoutType.contactCompany
				)
				{
					this._entityEditParams["enableContact"] = true;
					this._entityEditParams["contactIndex"] = 0;
				}
				else if(layoutType === BX.Crm.EntityEditorClientLayoutType.company
					|| layoutType === BX.Crm.EntityEditorClientLayoutType.companyContact
				)
				{
					this._entityEditParams["enableCompany"] = true;
				}
			}
		}
		BX.Crm.EntityEditorClientLight.superclass.switchToSingleEditMode.apply(this, arguments);
	};
	BX.Crm.EntityEditorClientLight.prototype.getEntityInitialMode = function(entityTypeId)
	{
		if(!this.isQuickEditEnabled())
		{
			return BX.Crm.EntityEditorClientMode.select;
		}

		if(!this.checkModeOption(BX.UI.EntityEditorModeOptions.individual))
		{
			return BX.Crm.EntityEditorClientMode.edit;
		}

		return BX.prop.getBoolean(
			this._entityEditParams,
			entityTypeId === BX.CrmEntityType.enumeration.contact ? "enableContact" : "enableCompany",
			false
		) ? BX.Crm.EntityEditorClientMode.edit : BX.Crm.EntityEditorClientMode.select;
	};
	BX.Crm.EntityEditorClientLight.prototype.resolveDataTagName = function(entityTypeName)
	{
		var compoundInfos = this._schemeElement.getDataArrayParam("compound", null);
		if(BX.type.isArray(compoundInfos))
		{
			for(var i = 0, length = compoundInfos.length; i < length; i++)
			{
				if(BX.prop.getString(compoundInfos[i], "entityTypeName", "") === entityTypeName)
				{
					return BX.prop.getString(compoundInfos[i], "tagName", "");
				}
			}
		}
		return "";
	};
	BX.Crm.EntityEditorClientLight.prototype.renderContact = function()
	{
		var caption = this._schemeElement.getDataStringParam("contactLegend", "");
		if(caption === "")
		{
			caption = BX.CrmEntityType.getCaptionByName(BX.CrmEntityType.names.contact);
		}

		this.removeContactAllSearchBoxes();
		this.removeContactAllPanels();
		if(this.isInEditMode())
		{
			this._contactWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-inner-row" } });
			this._innerContainer.appendChild(this._contactWrapper);

			this._contactTitleWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-title" },
					children:
						[
							BX.create("span",
								{
									props: { className: "crm-entity-widget-content-block-title-text" },
									text: caption
								}
							)
						]
				}
			);
			this._contactWrapper.appendChild(this._contactTitleWrapper);

			this._addContactButton = BX.create(
				"span",
				{
					props: { className: "crm-entity-widget-actions-btn-add" },
					text: this.getMessage("addParticipant")
				}
			);
			this._contactWrapper.appendChild(this._addContactButton);
			BX.bind(this._addContactButton, "click", BX.delegate(this.onContactAddButtonClick, this));

			this._contactSearchBoxes = [];
			if(this._contactInfos.length() > 0)
			{
				var mode = this.getEntityInitialMode(BX.CrmEntityType.enumeration.contact);
				var defaultEditIndex = (this._contactInfos.length() > 1 ? -2 : -1);
				var editIndex = mode === BX.Crm.EntityEditorClientMode.edit
					? BX.prop.getInteger(this._entityEditParams, "contactIndex", defaultEditIndex) : defaultEditIndex;

				for(var i = 0, length = this._contactInfos.length(); i < length; i++)
				{
					var currentMode = mode;
					if(currentMode === BX.Crm.EntityEditorClientMode.edit && !(editIndex === i || editIndex === -1))
					{
						currentMode = BX.Crm.EntityEditorClientMode.select
					}

					this.addContactSearchBox(
						this.createContactSearchBox({ entityInfo: this._contactInfos.get(i), mode: currentMode })
					);
				}
			}
			else
			{
				this.addContactSearchBox(this.createContactSearchBox());
			}
		}
		else if(this._contactInfos.length() > 0 && this.isContactEnabled())
		{
			this._contactTitleWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-title" }
				}
			);

			var innerTitleWrapper = BX.create("span",
				{
					props: { className: "crm-entity-widget-content-subtitle-text" },
					children: [ BX.create("span", { text: caption }) ]
				}
			);
			this._contactTitleWrapper.appendChild(innerTitleWrapper);


			if(!this.isReadOnly())
			{
				innerTitleWrapper.appendChild(
					BX.create("span",
						{
							props: { className: "crm-entity-card-widget-title-edit-icon" }
						}
					)
				);
			}

			var innerWrapperContainer = BX.create("div", {
				props: { className: "crm-entity-widget-content-block-inner-container" }
			});

			this._innerWrapper.appendChild(innerWrapperContainer);
			innerWrapperContainer.appendChild(this._contactTitleWrapper);


			var dataTagName = this.resolveDataTagName(BX.CrmEntityType.names.contact);
			if(dataTagName === "")
			{
				dataTagName = "CONTACT_IDS";
			}

			var additionalBlock = BX.create("div", {
				props: { className: "crm-entity-widget-before-action" },
				attrs: { "data-field-tag": dataTagName }
			});
			innerWrapperContainer.appendChild(additionalBlock);


			this._contactPanels = [];
			for(i = 0, length = this._contactInfos.length(); i < length; i++)
			{
				var contactInfo = this._contactInfos.get(i);

				var useExternalRequisiteBinding = this._schemeElement.getDataBooleanParam("useExternalRequisiteBinding", false);
				var contactSettings =
					{
						editor: this,
						entityInfo: contactInfo,
						loaderConfig: BX.prop.getObject(this._primaryLoaderConfig, contactInfo.getTypeName(), null),
						enableEntityTypeCaption: false,
						enableRequisite: false,
						enableCommunications: this._editor.areCommunicationControlsEnabled(),
						enableAddress: this.isClientFieldVisible('ADDRESS'),
						enableTooltip: this._schemeElement.getDataBooleanParam("enableTooltip", true) && this.isClientFieldVisible('REQUISITES'),
						mode: BX.UI.EntityEditorMode.view,
						clientEditorFieldsParams: this.getClientEditorFieldsParams(contactInfo.getTypeName()),
						canChangeDefaultRequisite: !useExternalRequisiteBinding,
						useExternalRequisiteBinding: useExternalRequisiteBinding
					};

				//HACK: Enable requisite selection due to editor is not support it.
				var enableRequisite = i === 0 && !(this.isCompanyEnabled() && this.hasCompanies());
				if(enableRequisite)
				{
					contactSettings['enableRequisite'] = true;
					contactSettings['requisiteBinding'] = this._model.getField("REQUISITE_BINDING", {});
					contactSettings['requisiteSelectUrl'] = this._editor.getEntityRequisiteSelectUrl(
						BX.CrmEntityType.names.contact,
						contactInfo.getId()
					);
					contactSettings['requisiteMode'] = BX.UI.EntityEditorMode.edit;
				}
				if (useExternalRequisiteBinding)
				{
					contactSettings['canChangeDefaultRequisite'] = enableRequisite;
				}
				var categoryParams = BX.prop.getObject(
					this._schemeElement.getDataObjectParam('categoryParams', {}),
					BX.CrmEntityType.enumeration.contact,
					{}
				);
				contactSettings['categoryId'] = BX.prop.getInteger(categoryParams, 'categoryId', 0);

				var permissionToken = this._schemeElement.getDataStringParam('permissionToken', null);
				if (permissionToken)
				{
					contactSettings['permissionToken'] = permissionToken;
				}

				var contactPanel = BX.Crm.ClientEditorEntityPanel.create(
					this._id +  "_" + contactInfo.getId().toString(),
					contactSettings
				);

				this._contactPanels.push(contactPanel);
				contactPanel.setContainer(innerWrapperContainer);
				contactPanel.layout();

				if(enableRequisite)
				{
					contactPanel.addRequisiteChangeListener(this._requisiteChangeHandler);
				}
				contactPanel.addRequisiteListChangeListener(this.onRequisiteListChange.bind(this));
			}
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.renderCompany = function()
	{
		var caption = this._schemeElement.getDataStringParam("companyLegend", "");
		if(caption === "")
		{
			caption = BX.CrmEntityType.getCaptionByName(BX.CrmEntityType.names.company);
		}

		this.removeCompanyAllSearchBoxes();
		this.removeCompanyAllPanels();
		if(this.isInEditMode())
		{
			this._companyWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-inner-row" } });
			this._innerContainer.appendChild(this._companyWrapper);

			this._companyTitleWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-title" },
					children:
						[
							BX.create("span",
								{
									props: { className: "crm-entity-widget-content-block-title-text" },
									text: caption
								}
							)
						]
				}
			);
			this._companyWrapper.appendChild(this._companyTitleWrapper);

			if(this._enableCompanyMultiplicity)
			{
				this._addCompanyButton = BX.create(
					"span",
					{
						props: { className: "crm-entity-widget-actions-btn-add" },
						text: this.getMessage("addParticipant")
					}
				);
				this._companyWrapper.appendChild(this._addCompanyButton);
				BX.bind(this._addCompanyButton, "click", BX.delegate(this.onCompanyAddButtonClick, this));
			}

			this._companySearchBoxes = [];
			if(this._companyInfos.length() > 0)
			{
				var mode = this.getEntityInitialMode(BX.CrmEntityType.enumeration.company);
				var defaultEditIndex = (this._companyInfos.length() > 1 ? -2 : -1);
				var editIndex = mode === BX.Crm.EntityEditorClientMode.edit
					? BX.prop.getInteger(this._entityEditParams, "companyIndex", defaultEditIndex) : defaultEditIndex;

				for(var i = 0, length = this._companyInfos.length(); i < length; i++)
				{
					var currentMode = mode;
					if(currentMode === BX.Crm.EntityEditorClientMode.edit && !(editIndex === i || editIndex === -1))
					{
						currentMode = BX.Crm.EntityEditorClientMode.select
					}

					this.addCompanySearchBox(
						this.createCompanySearchBox({ entityInfo: this._companyInfos.get(i), mode: currentMode })
					);
				}
			}
			else
			{
				this.addCompanySearchBox(this.createCompanySearchBox());
			}
		}
		else if(this.isCompanyEnabled() && this._companyInfos.length() > 0)
		{
			this._companyTitleWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-title" }
				}
			);

			var innerTitleWrapper = BX.create("span",
				{
					props: { className: "crm-entity-widget-content-subtitle-text" },
					children: [ BX.create("span", { text: caption }) ]
				}
			);
			this._companyTitleWrapper.appendChild(innerTitleWrapper);
			if(!this.isReadOnly())
			{
				innerTitleWrapper.appendChild(
					BX.create("span",
						{
							props: { className: "crm-entity-card-widget-title-edit-icon" }
						}
					)
				);
			}



			var innerWrapperContainer = BX.create("div", {
				props: { className: "crm-entity-widget-content-block-inner-container" }
			});

			this._innerWrapper.appendChild(innerWrapperContainer);
			innerWrapperContainer.appendChild(this._companyTitleWrapper);

			var dataTagName = this.resolveDataTagName(BX.CrmEntityType.names.company);
			if(dataTagName === "")
			{
				dataTagName = this._enableCompanyMultiplicity ? "COMPANY_IDS" : "COMPANY_ID";
			}

			var additionalBlock = BX.create("div", {
				props: { className: "crm-entity-widget-before-action" },
				attrs: { "data-field-tag": dataTagName }
			});
			innerWrapperContainer.appendChild(additionalBlock);

			this._companyPanels = [];
			for(i = 0, length = this._companyInfos.length(); i < length; i++)
			{
				var companyInfo = this._companyInfos.get(i);

				var useExternalRequisiteBinding = this._schemeElement.getDataBooleanParam("useExternalRequisiteBinding", false);
				var companySettings =
					{
						editor: this,
						entityInfo: companyInfo,
						loaderConfig: BX.prop.getObject(this._primaryLoaderConfig, companyInfo.getTypeName(), null),
						enableEntityTypeCaption: false,
						enableRequisite: false,
						enableCommunications: this._editor.areCommunicationControlsEnabled(),
						enableAddress: this.isClientFieldVisible('ADDRESS'),
						enableTooltip: this._schemeElement.getDataBooleanParam("enableTooltip", true) && this.isClientFieldVisible('REQUISITES'),
						mode: BX.UI.EntityEditorMode.view,
						clientEditorFieldsParams: this.getClientEditorFieldsParams(companyInfo.getTypeName()),
						canChangeDefaultRequisite: !useExternalRequisiteBinding,
						useExternalRequisiteBinding: useExternalRequisiteBinding
					};

				//HACK: Enable requisite selection due to editor is not support it.
				var enableRequisite = i === 0;
				if(enableRequisite)
				{
					companySettings['requisiteBinding'] = this._model.getField("REQUISITE_BINDING", {});
					companySettings['requisiteSelectUrl'] = this._editor.getEntityRequisiteSelectUrl(
						BX.CrmEntityType.names.company,
						companyInfo.getId()
					);
					companySettings['requisiteMode'] = BX.UI.EntityEditorMode.edit;
				}
				if (useExternalRequisiteBinding)
				{
					companySettings['canChangeDefaultRequisite'] = enableRequisite;
				}
				var categoryParams = BX.prop.getObject(
					this._schemeElement.getDataObjectParam('categoryParams', {}),
					BX.CrmEntityType.enumeration.company,
					{}
				);
				companySettings['categoryId'] = BX.prop.getInteger(categoryParams, 'categoryId', 0);

				var permissionToken = this._schemeElement.getDataStringParam('permissionToken', null);
				if (permissionToken)
				{
					companySettings['permissionToken'] = permissionToken;
				}

				var companyPanel = BX.Crm.ClientEditorEntityPanel.create(
					this._id +  "_" + companyInfo.getId().toString(),
					companySettings
				);

				this._companyPanels.push(companyPanel);
				companyPanel.setContainer(innerWrapperContainer);
				companyPanel.layout();

				if(enableRequisite)
				{
					companyPanel.addRequisiteChangeListener(this._requisiteChangeHandler);
				}
				companyPanel.addRequisiteListChangeListener(this.onRequisiteListChange.bind(this));
			}
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.createCompanySearchBox = function(params)
	{
		var entityInfo = BX.prop.get(params, "entityInfo", null);
		if(entityInfo !== null && !(entityInfo instanceof BX.CrmEntityInfo))
		{
			entityInfo = null;
		}

		var enableCreation = this._schemeElement.getDataBooleanParam('enableCreation', this._editor.canCreateCompany());
		if(enableCreation)
		{
			//Check if creation of company is disabled by configuration.
			enableCreation = BX.prop.getBoolean(
				this._schemeElement.getDataObjectParam("creation", {}),
				BX.CrmEntityType.names.company.toLowerCase(),
				true
			);
		}

		var categoryParams = BX.prop.getObject(
			this._schemeElement.getDataObjectParam('categoryParams', {}),
			BX.CrmEntityType.enumeration.company,
			{}
		);

		return(
			BX.Crm.EntityEditorClientSearchBox.create(
				this._id,
				{
					entityTypeId: BX.CrmEntityType.enumeration.company,
					entityTypeName: BX.CrmEntityType.names.company,
					categoryId: BX.prop.getInteger(categoryParams, 'categoryId', 0),
					extraCategoryIds: BX.prop.getArray(categoryParams, 'extraCategoryIds', []),
					entityInfo: entityInfo,
					enableCreation: enableCreation,
					creationLegend: this._schemeElement.getDataStringParam('creationLegend', ''),
					enableDeletion: false,
					enableQuickEdit: this.isQuickEditEnabled(),
					mode: BX.prop.getInteger(params, "mode", BX.Crm.EntityEditorClientMode.select),
					editor: this._editor,
					loaderConfig: this._primaryLoaderConfig,
					lastEntityInfos: this._model.getSchemeField(this._schemeElement, "lastCompanyInfos", []),
					container: this._companyWrapper,
					placeholder: this.getMessage("companySearchPlaceholder"),
					parentField: this,
					clientEditorEnabled: this._schemeElement.getData().hasOwnProperty('clientEditorFieldsParams'),
					clientEditorFields: this.getClientVisibleFieldsList(BX.CrmEntityType.names.company),
					clientEditorFieldsParams: this.getClientEditorFieldsParams(BX.CrmEntityType.names.company),
					requisiteBinding: this._model.getField("REQUISITE_BINDING", {}),
					isRequired: (this.isRequired() || this.isRequiredByAttribute()),
					enableMyCompanyOnly: this._schemeElement.getDataBooleanParam('enableMyCompanyOnly', false),
					enableRequisiteSelection: this._schemeElement.getDataBooleanParam('enableRequisiteSelection', false),
					permissionToken: this._schemeElement.getDataStringParam('permissionToken', null),
					duplicateControl: this.getDuplicateControlConfig(BX.CrmEntityType.enumeration.company)
				}
			)
		);
	};
	BX.Crm.EntityEditorClientLight.prototype.addCompanySearchBox = function(searchBox, options)
	{
		if(!BX.type.isPlainObject(options))
		{
			options = {};
		}

		this._companySearchBoxes.push(searchBox);

		var layoutOptions = BX.prop.getObject(options, "layoutOptions", {});
		if(this._addCompanyButton)
		{
			layoutOptions["anchor"] = this._addCompanyButton;
		}

		searchBox.layout(layoutOptions);

		searchBox.addResetListener(this._companyResetHandler);
		searchBox.addTitleChangeListener(this._companyNameChangeHandler);
		searchBox.addChangeListener(this._companyChangeHandler);
		searchBox.addDeletionListener(this._companyDeletionHandler);
		searchBox.addMultifieldChangeListener(this._multifieldChangeHandler);

		var enableDeletion = this._companySearchBoxes.length > 1;
		for(var i = 0, length = this._companySearchBoxes.length; i < length; i++)
		{
			this._companySearchBoxes[i].enableDeletion(enableDeletion);
		}

		return searchBox;
	};
	BX.Crm.EntityEditorClientLight.prototype.removeCompanySearchBox = function(searchBox)
	{
		var index = this.findCompanySearchBoxIndex(searchBox);
		if(index < 0)
		{
			return;
		}

		searchBox.removeResetListener(this._companyResetHandler);
		searchBox.removeTitleChangeListener(this._companyNameChangeHandler);
		searchBox.removeChangeListener(this._companyChangeHandler);
		searchBox.removeDeletionListener(this._companyDeletionHandler);
		searchBox.removeMultifieldChangeListener(this._multifieldChangeHandler);

		searchBox.clearLayout();

		this._companySearchBoxes.splice(index, 1);

		var enableDeletion = this._companySearchBoxes.length > 1;
		for(var i = 0, length = this._companySearchBoxes.length; i < length; i++)
		{
			this._companySearchBoxes[i].enableDeletion(enableDeletion);
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.findCompanySearchBoxIndex = function(companySearchBox)
	{
		for(var i = 0, length = this._companySearchBoxes.length; i < length; i++)
		{
			if(companySearchBox === this._companySearchBoxes[i])
			{
				return i;
			}
		}
		return -1;
	};
	BX.Crm.EntityEditorClientLight.prototype.getDuplicateControlConfig = function(entityTypeId)
	{
		let result = {};

		if (BX.CrmEntityType.isDefined(entityTypeId))
		{
			const duplicateControlConfigs = this._schemeElement.getDataObjectParam("duplicateControl", {});

			if (
				duplicateControlConfigs.hasOwnProperty(entityTypeId)
				&& BX.Type.isPlainObject(duplicateControlConfigs[entityTypeId])
			)
			{
				result = duplicateControlConfigs[entityTypeId];
			}
		}

		return result;
	};
	BX.Crm.EntityEditorClientLight.prototype.createContactSearchBox = function(params)
	{
		var entityInfo = BX.prop.get(params, "entityInfo", null);
		if(entityInfo !== null && !(entityInfo instanceof BX.CrmEntityInfo))
		{
			entityInfo = null;
		}

		var enableCreation = this._schemeElement.getDataBooleanParam('enableCreation', this._editor.canCreateContact());
		if(enableCreation)
		{
			//Check if creation of contact is disabled by configuration.
			enableCreation = BX.prop.getBoolean(
				this._schemeElement.getDataObjectParam("creation", {}),
				BX.CrmEntityType.names.contact.toLowerCase(),
				true
			);
		}

		var enableRequisiteSelection =
			this._schemeElement.getDataBooleanParam('enableRequisiteSelection', false)
			&& this._contactSearchBoxes.length === 0
			&& (
				!this._companyInfos
				|| this._companyInfos.length() === 0
			)
		;

		var categoryParams = BX.prop.getObject(
			this._schemeElement.getDataObjectParam('categoryParams', {}),
			BX.CrmEntityType.enumeration.contact,
			{}
		);

		return(
			BX.Crm.EntityEditorClientSearchBox.create(
				this._id,
				{
					entityTypeId: BX.CrmEntityType.enumeration.contact,
					entityTypeName: BX.CrmEntityType.names.contact,
					categoryId: BX.prop.getInteger(categoryParams, 'categoryId', 0),
					extraCategoryIds: BX.prop.getArray(categoryParams, 'extraCategoryIds', []),
					entityInfo: entityInfo,
					enableCreation: enableCreation,
					creationLegend: this._schemeElement.getDataStringParam('creationLegend', ''),
					enableDeletion: BX.prop.getBoolean(params, "enableDeletion", true),
					enableQuickEdit: this.isQuickEditEnabled(),
					mode: BX.prop.getInteger(params, "mode", BX.Crm.EntityEditorClientMode.select),
					editor: this._editor,
					loaderConfig: this._primaryLoaderConfig,
					lastEntityInfos: this._model.getSchemeField(this._schemeElement, "lastContactInfos", []),
					container: this._contactWrapper,
					placeholder: this.getMessage("contactSearchPlaceholder"),
					parentField: this,
					clientEditorEnabled: this._schemeElement.getData().hasOwnProperty('clientEditorFieldsParams'),
					clientEditorFields: this.getClientVisibleFieldsList(BX.CrmEntityType.names.contact),
					clientEditorFieldsParams: this.getClientEditorFieldsParams(BX.CrmEntityType.names.contact),
					requisiteBinding: this._model.getField("REQUISITE_BINDING", {}),
					isRequired: (this.isRequired() || this.isRequiredByAttribute()),
					enableRequisiteSelection: enableRequisiteSelection,
					permissionToken: this._schemeElement.getDataStringParam('permissionToken', null),
					duplicateControl: this.getDuplicateControlConfig(BX.CrmEntityType.enumeration.contact)
				}
			)
		);
	};
	BX.Crm.EntityEditorClientLight.prototype.addContactSearchBox = function(searchBox, options)
	{
		if(!BX.type.isPlainObject(options))
		{
			options = {};
		}

		this._contactSearchBoxes.push(searchBox);

		var layoutOptions = BX.prop.getObject(options, "layoutOptions", {});
		if(this._addContactButton)
		{
			layoutOptions["anchor"] = this._addContactButton;
		}

		searchBox.layout(layoutOptions);

		searchBox.addResetListener(this._contactResetHandler);
		searchBox.addTitleChangeListener(this._contactNameChangeHandler);
		searchBox.addChangeListener(this._contactChangeHandler);
		searchBox.addDeletionListener(this._contactDeletionHandler);
		searchBox.addMultifieldChangeListener(this._multifieldChangeHandler);

		var enableDeletion = this._contactSearchBoxes.length > 1;
		for(var i = 0, length = this._contactSearchBoxes.length; i < length; i++)
		{
			this._contactSearchBoxes[i].enableDeletion(enableDeletion);
		}

		return searchBox;
	};
	BX.Crm.EntityEditorClientLight.prototype.removeContactSearchBox = function(searchBox)
	{
		var index = this.findContactSearchBoxIndex(searchBox);
		if(index < 0)
		{
			return;
		}
		var isNeedToEnableRequisiteSelectionOnFirstContact = (
			index === 0
			&& searchBox._enableRequisiteSelection
			&& this._contactSearchBoxes[1]
		);

		searchBox.removeResetListener(this._contactResetHandler);
		searchBox.removeTitleChangeListener(this._contactNameChangeHandler);
		searchBox.removeChangeListener(this._contactChangeHandler);
		searchBox.removeDeletionListener(this._contactDeletionHandler);
		searchBox.removeMultifieldChangeListener(this._multifieldChangeHandler);

		searchBox.clearLayout();

		this._contactSearchBoxes.splice(index, 1);

		var enableDeletion = this._contactSearchBoxes.length > 1;
		for(var i = 0, length = this._contactSearchBoxes.length; i < length; i++)
		{
			this._contactSearchBoxes[i].enableDeletion(enableDeletion);
		}
		if (isNeedToEnableRequisiteSelectionOnFirstContact)
		{
			this.setRequisiteSelectionEnabledOnFirstContactSearchBox(true);
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.removeContactAllSearchBoxes = function()
	{
		if (BX.Type.isArray(this._contactSearchBoxes))
		{
			for (var i = 0, length = this._contactSearchBoxes.length; i < length; i++)
			{
				var searchBox = this._contactSearchBoxes[i];

				searchBox.removeResetListener(this._contactResetHandler);
				searchBox.removeTitleChangeListener(this._contactNameChangeHandler);
				searchBox.removeChangeListener(this._contactChangeHandler);
				searchBox.removeDeletionListener(this._contactDeletionHandler);
				searchBox.removeMultifieldChangeListener(this._multifieldChangeHandler);

				searchBox.clearLayout();
			}
		}
		this._contactSearchBoxes = [];
	};
	BX.Crm.EntityEditorClientLight.prototype.removeCompanyAllSearchBoxes = function()
	{
		if (BX.Type.isArray(this._companySearchBoxes))
		{
			for (var i = 0, length = this._companySearchBoxes.length; i < length; i++)
			{
				var searchBox = this._companySearchBoxes[i];

				searchBox.removeResetListener(this._companyResetHandler);
				searchBox.removeTitleChangeListener(this._companyNameChangeHandler);
				searchBox.removeChangeListener(this._companyChangeHandler);
				searchBox.removeDeletionListener(this._companyDeletionHandler);
				searchBox.removeMultifieldChangeListener(this._multifieldChangeHandler);

				searchBox.clearLayout();
			}
		}
		this._companySearchBoxes = [];
	};
	BX.Crm.EntityEditorClientLight.prototype.removeCompanyAllPanels = function()
	{
		if (BX.Type.isArray(this._companyPanels))
		{
			for (var i = 0, length = this._companyPanels.length; i < length; i++)
			{
				var panel = this._companyPanels[i];
				panel.clearLayout();
			}
		}
		this._companyPanels = [];
	};
	BX.Crm.EntityEditorClientLight.prototype.removeContactAllPanels = function()
	{
		if (BX.Type.isArray(this._contactPanels))
		{
			for (var i = 0, length = this._contactPanels.length; i < length; i++)
			{
				var panel = this._contactPanels[i];
				panel.clearLayout();
			}
		}
		this._contactPanels = [];
	};
	BX.Crm.EntityEditorClientLight.prototype.findContactSearchBoxIndex = function(contactSearchBox)
	{
		for(var i = 0, length = this._contactSearchBoxes.length; i < length; i++)
		{
			if(contactSearchBox === this._contactSearchBoxes[i])
			{
				return i;
			}
		}
		return -1;
	};
	BX.Crm.EntityEditorClientLight.prototype.save = function()
	{
		this._info["COMPANY_DATA"] = this.saveEntityInfos(this._companySearchBoxes, this._companyInfos);
		this._info["CONTACT_DATA"] = this.saveEntityInfos(this._contactSearchBoxes, this._contactInfos);
	};
	BX.Crm.EntityEditorClientLight.prototype.saveEntityInfos = function(searchBoxes, entityInfos)
	{
		var i, length;

		if(searchBoxes !== null)
		{
			for(i = 0, length = searchBoxes.length; i < length; i++)
			{
				if(searchBoxes[i].isNeedToSave())
				{
					searchBoxes[i].save();
				}
			}
		}

		var data = [];
		if(entityInfos !== null)
		{
			var infoItems = entityInfos.getItems();
			for(i = 0, length = infoItems.length; i < length; i++)
			{
				data.push(infoItems[i].getSettings());
			}
		}
		return data;
	};
	BX.Crm.EntityEditorClientLight.prototype.validate = function(result)
	{
		var isEmpty = !this.hasCompanies() && !this.hasContacts();
		var isRequired = (this.isRequired() || this.isRequiredByAttribute());
		var isValid = !isRequired || !isEmpty;
		if(!isValid)
		{
			this.addValidationErrorToResult(result);
			return false;
		}

		var validator = BX.UI.EntityAsyncValidator.create();
		if(this.isInEditMode())
		{
			var hasValidCompanies = this.validateSearchBoxes(this._companySearchBoxes, validator, result);
			var hasValidContacts = this.validateSearchBoxes(this._contactSearchBoxes, validator, result);
			if (!hasValidCompanies && !hasValidContacts && isRequired)
			{
				this.addValidationErrorToResult(result);
				return false;
			}
		}
		return validator.validate();
	};
	BX.Crm.EntityEditorClientLight.prototype.addValidationErrorToResult = function(result)
	{
		result.addError(BX.UI.EntityValidationError.create({ field: this }));
		this.showRequiredFieldError(this.getContentWrapper());
	};
	BX.Crm.EntityEditorClientLight.prototype.showRequiredFieldError =  function(anchor)
	{
		var requiredFieldErrorMessage = this._schemeElement.getDataStringParam('requiredFieldErrorMessage', '');
		if (requiredFieldErrorMessage)
		{
			this.showError(requiredFieldErrorMessage, anchor);
		}
		else
		{
			BX.Crm.EntityEditorClientLight.superclass.showRequiredFieldError.call(this, anchor);
		}
	};

	BX.Crm.EntityEditorClientLight.prototype.validateSearchBoxes = function(searchBoxes, validator, result)
	{
		var hasValidValue = false;
		var validationResult;
		if (BX.Type.isArray(searchBoxes))
		{
			for(var i = 0, length = searchBoxes.length; i < length; i++)
			{
				validationResult = searchBoxes[i].validate(result);
				validator.addResult(validationResult);
				if (validationResult !== false)
				{
					hasValidValue = true;
				}
			}
		}
		return hasValidValue;
	};
	BX.Crm.EntityEditorClientLight.prototype.doClearLayout = function()
	{
		this.releaseSearchBoxes(this._contactSearchBoxes);
		this.releasePanels(this._contactPanels);
		this.releaseSearchBoxes(this._companySearchBoxes);
		this.releasePanels(this._companyPanels);
	};
	BX.Crm.EntityEditorClientLight.prototype.release = function()
	{
		this.releaseSearchBoxes(this._contactSearchBoxes);
		this.releasePanels(this._contactPanels);
		this.releaseSearchBoxes(this._companySearchBoxes);
		this.releasePanels(this._companyPanels);
	};
	BX.Crm.EntityEditorClientLight.prototype.releaseSearchBoxes = function(searchBoxes)
	{
		if (!BX.Type.isArray(searchBoxes))
		{
			return;
		}
		for(var i = 0, length = searchBoxes.length; i < length; i++)
		{
			searchBoxes[i].release();
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.releasePanels = function(panels)
	{
		if (!BX.Type.isArray(panels))
		{
			return;
		}
		for(var i = 0, length = panels.length; i < length; i++)
		{
			panels[i].release();
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.getClientEditorFieldsParams = function(entityTypeName)
	{
		return BX.prop.getObject(this._schemeElement.getDataObjectParam("clientEditorFieldsParams", {}), entityTypeName, {});
	};
	BX.Crm.EntityEditorClientLight.prototype.onContactAddButtonClick = function(e)
	{
		this.addContactSearchBox(this.createContactSearchBox()).focus();
	};
	BX.Crm.EntityEditorClientLight.prototype.onCompanyAddButtonClick = function(e)
	{
		if(this._enableCompanyMultiplicity)
		{
			this.addCompanySearchBox(this.createCompanySearchBox()).focus();
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.onCompanyReset = function(sender, previousEntityInfo)
	{
		if(previousEntityInfo)
		{
			this.removeCompany(previousEntityInfo);
			this.markAsChanged();
		}

		this.setRequisiteSelectionEnabledOnFirstContactSearchBox(true);
	};
	BX.Crm.EntityEditorClientLight.prototype.onCompanyNameChange = function(sender)
	{
		this.markAsChanged();
	};
	BX.Crm.EntityEditorClientLight.prototype.onCompanyChange = function(sender, currentEntityInfo, previousEntityInfo)
	{
		var isChanged = false;

		if(previousEntityInfo)
		{
			this.removeCompany(previousEntityInfo);
			isChanged = true;
		}

		if(currentEntityInfo)
		{
			this.addCompany(currentEntityInfo);
			isChanged = true;
		}

		if(!isChanged)
		{
			return;
		}

		this.markAsChanged();

		this.setRequisiteSelectionEnabledOnFirstContactSearchBox(false);

		if(!this._enableCompanyMultiplicity)
		{
			if(currentEntityInfo.getId() > 0)
			{
				var entityLoader = BX.prop.getObject(
					this._secondaryLoaderConfig,
					BX.CrmEntityType.names.company,
					null
				);

				if(entityLoader)
				{
					BX.CrmDataLoader.create(
						this._id,
						{
							serviceUrl: entityLoader["url"],
							action: entityLoader["action"],
							params:
								{
									"PRIMARY_TYPE_NAME": BX.CrmEntityType.names.company,
									"PRIMARY_ID": currentEntityInfo.getId(),
									"SECONDARY_TYPE_NAME": BX.CrmEntityType.names.contact,
									"OWNER_TYPE_NAME": this.getOwnerTypeName()
								}
						}
					).load(BX.delegate(this.onContactInfosLoad, this));
				}
			}
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.onCompanyDelete = function(sender, currentEntityInfo)
	{
		if(currentEntityInfo)
		{
			this._companyInfos.remove(currentEntityInfo);
			this.markAsChanged();
		}

		this.removeCompanySearchBox(sender);
	};
	BX.Crm.EntityEditorClientLight.prototype.onContactChange = function(sender, currentEntityInfo, previousEntityInfo)
	{
		var isChanged = false;

		if(previousEntityInfo)
		{
			this.removeContact(previousEntityInfo);
			isChanged = true;
		}

		if(currentEntityInfo)
		{
			this.addContact(currentEntityInfo);
			isChanged = true;
		}

		if(isChanged)
		{
			this.markAsChanged();
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.onContactNameChange = function(sender)
	{
		this.markAsChanged();
	};
	BX.Crm.EntityEditorClientLight.prototype.onContactDelete = function(sender, currentEntityInfo)
	{
		if(currentEntityInfo)
		{
			this._contactInfos.remove(currentEntityInfo);
			this.markAsChanged();
		}

		this.removeContactSearchBox(sender);
	};
	BX.Crm.EntityEditorClientLight.prototype.onContactReset = function(sender, previousEntityInfo)
	{
		if(previousEntityInfo)
		{
			this.removeContact(previousEntityInfo);
			this.markAsChanged();
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.onContactInfosLoad = function(sender, result)
	{
		var i, length;
		var entityInfos = [];
		var entityData = BX.type.isArray(result['ENTITY_INFOS']) ? result['ENTITY_INFOS'] : [];
		for(i = 0, length = entityData.length; i < length; i++)
		{
			entityInfos.push(BX.CrmEntityInfo.create(entityData[i]));
		}

		this._contactInfos.removeAll();
		for(i = 0, length = entityInfos.length; i < length; i++)
		{
			this._contactInfos.add(entityInfos[i]);
		}
		this.markAsChanged();

		this.removeContactAllSearchBoxes();
		if(entityInfos.length > 0)
		{
			for(i = 0, length = entityInfos.length; i < length; i++)
			{
				this.addContactSearchBox(
					this.createContactSearchBox(
						{ entityInfo: entityInfos[i] }
					)
				);
			}
		}
		else
		{
			this.addContactSearchBox(this.createContactSearchBox());
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.onRequisiteChange = function(sender, eventArgs)
	{
		if(this.isInEditMode())
		{
			this.markAsChanged();
		}
		else
		{
			//Save immediately

			if (this._schemeElement.getDataBooleanParam('enableMyCompanyOnly', false))
			{
				this._changeRequisiteControlData = {
					'MC_REQUISITE_ID': BX.prop.getInteger(eventArgs, "requisiteId", 0),
					'MC_BANK_DETAIL_ID': BX.prop.getInteger(eventArgs, "bankDetailId", 0),
					'MYCOMPANY_ID': this._model.getNumberField('MYCOMPANY_ID')
				};
			}
			else
			{
				this._changeRequisiteControlData = {
					'REQUISITE_ID': BX.prop.getInteger(eventArgs, "requisiteId", 0),
					'BANK_DETAIL_ID': BX.prop.getInteger(eventArgs, "bankDetailId", 0)
				};
			}
			this._editor.saveControl(this);

			this._model.setField("REQUISITE_BINDING", null,  { enableNotification: false });
		}
	};

	BX.Crm.EntityEditorClientLight.prototype.prepareSaveData = function(data)
	{
		BX.Crm.EntityEditorClientLight.superclass.prepareSaveData.call(this, data);
		BX.mergeEx(data, this._changeRequisiteControlData);
	};

	// save changes in requisites in model
	BX.Crm.EntityEditorClientLight.prototype.onRequisiteListChange = function(sender, eventArgs)
	{
		var fieldName = this._schemeElement.getDataStringParam("info", "");
		var data = this._model.getInitFieldValue(fieldName, {});
		var entityTypeName = BX.prop.getString(eventArgs, "entityTypeName", "");
		var entityId = BX.prop.getInteger(eventArgs, "entityId", 0);
		var requisites = BX.prop.getArray(eventArgs, "requisites", []);
		var dataType = entityTypeName + '_DATA';

		if (data.hasOwnProperty(dataType) && entityId > 0)
		{
			for (var i = 0; i < data[dataType].length; i++)
			{
				var entity = data[dataType][i];
				if (entity.id == entityId)
				{
					if (!entity.hasOwnProperty('advancedInfo'))
					{
						entity.advancedInfo = {};
					}
					entity.advancedInfo.hasEditRequisiteData = true;
					entity.advancedInfo.requisiteData = requisites;
					data[dataType][i] = entity;
					this._model.setInitFieldValue(fieldName, data);
					break;
				}
			}
		}
	};
	BX.Crm.EntityEditorClientLight.prototype.onMultifieldChange = function(sender)
	{
		this.markAsChanged();
	};
	BX.Crm.EntityEditorClientLight.prototype.prepareEntitySubmitData = function(searchBoxes)
	{
		if(!BX.type.isArray(searchBoxes))
		{
			return [];
		}

		var results = [];
		for(var i = 0, length = searchBoxes.length; i < length; i++)
		{
			var entity = searchBoxes[i].getEntity();
			if(!entity)
			{
				continue;
			}

			var data = {};

			var mode = searchBoxes[i].getMode();
			if(mode === BX.Crm.EntityEditorClientMode.select
				|| (mode === BX.Crm.EntityEditorClientMode.edit && entity.getTitle() !== "")
			)
			{
				data["id"] = entity.getId();
			}
			if(mode === BX.Crm.EntityEditorClientMode.create
				|| (mode === BX.Crm.EntityEditorClientMode.edit && entity.getTitle() !== "")
			)
			{
				data["title"] = entity.getTitle();
				data["multifields"] = entity.getMultifields();
				data["requisites"] = entity.getRequisitesForSave();
				data["categoryId"] = entity.getCategoryId();
			}

			results.push(data);
		}
		return results;
	};
	BX.Crm.EntityEditorClientLight.prototype.onBeforeSubmit = function()
	{
		if (this.getMode() === BX.UI.EntityEditorMode.view)
		{
			return;
		}
		var data = {};
		if(this.isCompanyEnabled())
		{
			data["COMPANY_DATA"] = this.prepareEntitySubmitData(this._companySearchBoxes);
		}
		if(this.isContactEnabled())
		{
			data["CONTACT_DATA"] = this.prepareEntitySubmitData(this._contactSearchBoxes);
		}

		this.createDataElement("data", JSON.stringify(data));
	};
	BX.Crm.EntityEditorClientLight.prototype.setRequisiteSelectionEnabledOnFirstContactSearchBox = function(enableRequisiteSelection)
	{
		if (
			this._layoutType === BX.Crm.EntityEditorClientLayoutType.companyContact
			|| this._layoutType === BX.Crm.EntityEditorClientLayoutType.contactCompany
		)
		{
			for (var index = 0; index < this._contactSearchBoxes.length; index++)
			{
				this._contactSearchBoxes[index].setSelectRequisiteSelectionEnabled(index === 0 && enableRequisiteSelection);
			}
		}
	};
	if(typeof(BX.Crm.EntityEditorClientLight.messages) === "undefined")
	{
		BX.Crm.EntityEditorClientLight.messages = {};
	}
	BX.Crm.EntityEditorClientLight.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorClientLight();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorClient === "undefined")
{
	BX.Crm.EntityEditorClient = function()
	{
		BX.Crm.EntityEditorClient.superclass.constructor.apply(this);
		this._info = null;

		this._enablePrimaryEntity = true;
		this._primaryEntityTypeName = "";
		this._primaryEntityInfo = null;
		this._primaryEntityBindingInfos = null;
		this._primaryEntityEditor = null;

		this._secondaryEntityTypeName = "";
		this._secondaryEntityInfos = null;

		this._secondaryEntityEditor = null;
		this._dataElements = null;
		this._map = null;
		this._bindingTracker = null;

		this._innerWrapper = null;
	};
	BX.extend(BX.Crm.EntityEditorClient, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorClient.prototype.doInitialize = function()
	{
		BX.Crm.EntityEditorClient.superclass.doInitialize.apply(this);
		this._map = this._schemeElement.getDataObjectParam("map", {});
		this.initializeFromModel();
	};
	BX.Crm.EntityEditorClient.prototype.initializeFromModel = function()
	{
		this._info = this._model.getSchemeField(this._schemeElement, "info", {});

		this._enablePrimaryEntity = this._schemeElement.getDataBooleanParam(
			"enablePrimaryEntity",
			true
		);

		if(this._enablePrimaryEntity)
		{
			var primaryEntityData = BX.prop.getObject(this._info, "PRIMARY_ENTITY_DATA", null);
			var primaryEntityInfo = primaryEntityData ? BX.CrmEntityInfo.create(primaryEntityData) : null;

			if(primaryEntityInfo)
			{
				this.setPrimaryEntity(primaryEntityInfo);
			}
			else
			{
				this.setPrimaryEntityTypeName(
					this._schemeElement.getDataStringParam(
						"primaryEntityTypeName",
						BX.CrmEntityType.names.company
					)
				);
			}
		}

		this.setSecondaryEntityTypeName(
			this._schemeElement.getDataStringParam(
				"secondaryEntityTypeName",
				BX.CrmEntityType.names.contact
			)
		);

		var secondaryEntityData = null;
		var secondaryEntityDataKey =  this._schemeElement.getDataStringParam("secondaryEntityInfo", "");
		if(secondaryEntityDataKey !== "")
		{
			secondaryEntityData = this._model.getField(secondaryEntityDataKey, [])
		}
		else
		{
			secondaryEntityData = BX.prop.getArray(this._info, "SECONDARY_ENTITY_DATA", []);
		}

		this._secondaryEntityInfos = BX.Collection.create();
		this._primaryEntityBindingInfos = BX.Collection.create();
		var companyEntityId = primaryEntityInfo && primaryEntityInfo.getTypeName() === BX.CrmEntityType.names.company
			? primaryEntityInfo.getId() : 0;
		var i, length, info;
		for(i = 0, length = secondaryEntityData.length; i < length; i++)
		{
			info = BX.CrmEntityInfo.create(secondaryEntityData[i]);
			if(info.getId() <= 0)
			{
				continue;
			}

			if(companyEntityId > 0 && info.checkEntityBinding(BX.CrmEntityType.names.company, companyEntityId))
			{
				this._primaryEntityBindingInfos.add(info);
			}
			else
			{
				this._secondaryEntityInfos.add(info);
			}
		}
		this._bindingTracker = BX.Crm.EntityBindingTracker.create();
	};
	BX.Crm.EntityEditorClient.prototype.hasContentToDisplay = function()
	{
		return(this._primaryEntityInfo !== null
			|| (this._secondaryEntityInfos !== null && this._secondaryEntityInfos.length() > 0)
		);
	};
	BX.Crm.EntityEditorClient.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.UI.EntityEditorModeSwitchType.common;
		if(mode === BX.UI.EntityEditorMode.edit)
		{
			result |= BX.UI.EntityEditorModeSwitchType.button|BX.UI.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorClient.prototype.getContentWrapper = function()
	{
		return this._innerWrapper;
	};
	BX.Crm.EntityEditorClient.prototype.getEntityCreateUrl = function(entityTypeName)
	{
		return this._editor.getEntityCreateUrl(entityTypeName);
	};
	BX.Crm.EntityEditorClient.prototype.getEntityRequisiteSelectUrl = function(entityTypeName, entityId)
	{
		return this._editor.getEntityRequisiteSelectUrl(entityTypeName, entityId);
	};
	BX.Crm.EntityEditorClient.prototype.reset = function()
	{
		this.initializeFromModel();
	};
	BX.Crm.EntityEditorClient.prototype.rollback = function()
	{
		if(this.isChanged())
		{
			this.initializeFromModel();
		}
	};
	BX.Crm.EntityEditorClient.prototype.doSetMode = function(mode)
	{
		this.rollback();
	};
	BX.Crm.EntityEditorClient.prototype.createDataElement = function(key, value)
	{
		var name = BX.prop.getString(this._map, key, "");

		if(name === "")
		{
			return;
		}

		var input = BX.create("input", { attrs: { name: name, type: "hidden", value: value } });

		if(!this._dataElements)
		{
			this._dataElements = {};
		}

		this._dataElements[key] = input;
		if(this._wrapper)
		{
			this._wrapper.appendChild(input);
		}
	};
	BX.Crm.EntityEditorClient.prototype.layout = function(options)
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

		var title = this._schemeElement.getTitle();

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}


		this._dataElements = {};

		if(!this.hasContentToDisplay() && this.isInViewMode())
		{
			this._wrapper.appendChild(this.createTitleNode(title));
			this._innerWrapper = BX.create(
				"div",
				{
					props: { className: "crm-entity-widget-content-block-inner" },
					text: this.getMessage("isEmpty")
				}
			);
		}
		else
		{
			this._innerWrapper = BX.create("div",{ props: { className: "crm-entity-widget-clients-block" } });
			this._innerWrapper.appendChild(this.createTitleNode(title));

			if(this.isInEditMode())
			{
				if(this._enablePrimaryEntity)
				{
					this.createDataElement("primaryEntityType", this.getPrimaryEntityTypeName());
					this.createDataElement("primaryEntityId", this.getPrimaryEntityId());

					this.createDataElement("unboundSecondaryEntityIds", "");
					this.createDataElement("boundSecondaryEntityIds", "");
				}

				this.createDataElement("secondaryEntityType", this.getSecondaryEntityTypeName());
				this.createDataElement("secondaryEntityIds", this.getAllSecondaryEntityIds().join(","));
			}

			var editorWrapper = BX.create(
				"div",
				{
					props: { className: this.isInEditMode() ? "crm-entity-widget-content-block-clients" : "" }
				}
			);
			this._innerWrapper.appendChild(editorWrapper);

			var primaryEntityAnchor = BX.create("div", {});
			editorWrapper.appendChild(primaryEntityAnchor);

			var loaders = this._schemeElement.getDataObjectParam("loaders", {});
			var primaryLoader = BX.prop.getObject(loaders, "primary", {});
			var secondaryLoader = BX.prop.getObject(loaders, "secondary", {});

			if(this._enablePrimaryEntity)
			{
				this._primaryEntityEditor = BX.Crm.PrimaryClientEditor.create(
					this._id + "_PRIMARY",
					{
						"entityInfo": this._primaryEntityInfo,
						"entityTypeName": this._primaryEntityTypeName,
						"lastEntityInfos":	this._model.getSchemeField(
							this._schemeElement,
							"lastPrimaryEntityInfos",
							[]
						),
						"loaderConfig": primaryLoader,
						"requisiteBinding": this._model.getField("REQUISITE_BINDING", {}),
						"editor": this,
						"mode": this._mode,
						"onChange": BX.delegate(this.onPrimaryEntityChange, this),
						"onDelete": BX.delegate(this.onPrimaryEntityDelete, this),
						"onBindingAdd": BX.delegate(this.onPrimaryEntityBindingAdd, this),
						"onBindingDelete": BX.delegate(this.onPrimaryEntityBindingDelete, this),
						"onBindingRelease": BX.delegate(this.onPrimaryEntityBindingRelease, this),
						"container": editorWrapper,
						"achor": primaryEntityAnchor
					}
				);
				this._primaryEntityEditor.layout();
			}

			var secondaryEntityWrapper = BX.create("div", { props: { className: "crm-entity-widget-participants-container" } });
			editorWrapper.appendChild(secondaryEntityWrapper);
			this._secondaryEntityEditor = BX.Crm.SecondaryClientEditor.create(
				this._id + "_SECONDARY",
				{
					"entityInfos":     this._secondaryEntityInfos.getItems(),
					"entityTypeName":  this._secondaryEntityTypeName,
					"entityLegend":    this._schemeElement.getDataStringParam("secondaryEntityLegend", ""),
					"lastEntityInfos":	this._model.getSchemeField(
						this._schemeElement,
						"lastSecondaryEntityInfos",
						[]
					),
					"primaryLoader":   primaryLoader,
					"secondaryLoader": secondaryLoader,
					"mode":            this._mode,
					"onAdd":           BX.delegate(this.onSecondaryEntityAdd, this),
					"onDelete":        BX.delegate(this.onSecondaryEntityDelete, this),
					"onBeforeAdd":     BX.delegate(this.onSecondaryEntityBeforeAdd, this),
					"editor":          this,
					"container":       secondaryEntityWrapper
				}
			);
			this._secondaryEntityEditor.layout();

			if(this._primaryEntityEditor)
			{
				if(this.isInEditMode())
				{
					this._secondaryEntityEditor.setVisible(this._primaryEntityInfo !== null);
				}
				else
				{
					this._secondaryEntityEditor.setVisible(this._secondaryEntityInfos.length() > 0);
				}
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
	BX.Crm.EntityEditorClient.prototype.doClearLayout = function(options)
	{
		if(this._primaryEntityEditor)
		{
			this._primaryEntityEditor.clearLayout();
			this._primaryEntityEditor = null;
		}

		if(this._secondaryEntityEditor)
		{
			this._secondaryEntityEditor.clearLayout();
			this._secondaryEntityEditor = null;
		}

		for(var key in this._dataElements)
		{
			if(this._dataElements.hasOwnProperty(key))
			{
				BX.remove(this._dataElements[key]);
			}
		}
		this._dataElements = null;

		this._innerWrapper = null;
	};
	BX.Crm.EntityEditorClient.prototype.getOwnerTypeName = function()
	{
		return this._editor.getEntityTypeName();
	};
	BX.Crm.EntityEditorClient.prototype.getOwnerTypeId = function()
	{
		return this._editor.getEntityTypeId();
	};
	BX.Crm.EntityEditorClient.prototype.getOwnerId = function()
	{
		return this._editor.getEntityId();
	};
	BX.Crm.EntityEditorClient.prototype.getPrimaryEntityTypeName = function()
	{
		return this._primaryEntityTypeName;
	};
	BX.Crm.EntityEditorClient.prototype.setPrimaryEntityTypeName = function(entityType)
	{
		if(this._primaryEntityTypeName !== entityType)
		{
			this._primaryEntityTypeName = entityType;
		}
	};
	BX.Crm.EntityEditorClient.prototype.getPrimaryEntityId = function()
	{
		return this._primaryEntityInfo ? this._primaryEntityInfo.getId() : 0;
	};
	BX.Crm.EntityEditorClient.prototype.getPrimaryEntity = function()
	{
		return this._primaryEntityInfo;
	};
	BX.Crm.EntityEditorClient.prototype.setPrimaryEntity = function(entityInfo)
	{
		if(entityInfo instanceof BX.CrmEntityInfo)
		{
			this._primaryEntityInfo = entityInfo;
			this.setPrimaryEntityTypeName(entityInfo.getTypeName());
		}
		else
		{
			this._primaryEntityInfo = null;
		}
		this.markAsChanged();
	};
	BX.Crm.EntityEditorClient.prototype.getPrimaryEntityBindings = function()
	{
		return this._primaryEntityBindingInfos;
	};
	BX.Crm.EntityEditorClient.prototype.getSecondaryEntityTypeName = function()
	{
		return this._secondaryEntityTypeName;
	};
	BX.Crm.EntityEditorClient.prototype.setSecondaryEntityTypeName = function(entityType)
	{
		if(this._secondaryEntityTypeName !== entityType)
		{
			this._secondaryEntityTypeName = entityType;
		}
	};
	//region SecondaryEntities
	BX.Crm.EntityEditorClient.prototype.getSecondaryEntities = function()
	{
		return this._secondaryEntityInfos.getItems();
	};
	BX.Crm.EntityEditorClient.prototype.getSecondaryEntityById = function(id)
	{
		if(!this._secondaryEntityInfos)
		{
			return null;
		}
		return this._secondaryEntityInfos.search(function(item){ return item.getId() === id; });
	};
	BX.Crm.EntityEditorClient.prototype.removeSecondaryEntity = function(entityInfo)
	{
		if(this._secondaryEntityInfos)
		{
			this._secondaryEntityInfos.remove(entityInfo);
			this.markAsChanged();
		}
	};
	BX.Crm.EntityEditorClient.prototype.addSecondaryEntity = function(entityInfo)
	{
		if(this._secondaryEntityInfos)
		{
			this._secondaryEntityInfos.add(entityInfo);
			this.markAsChanged();
		}
	};
	BX.Crm.EntityEditorClient.prototype.onSecondaryEntityDelete = function(editor, entityInfo)
	{
		this.removeSecondaryEntity(entityInfo);
	};
	BX.Crm.EntityEditorClient.prototype.onSecondaryEntityBeforeAdd = function(editor, entityInfo, eventArgs)
	{
		if(this._primaryEntityEditor && this._primaryEntityInfo && this._primaryEntityInfo.getTypeName() === BX.CrmEntityType.names.company)
		{
			var primaryEntityId = this._primaryEntityInfo.getId();
			if(entityInfo.checkEntityBinding(BX.CrmEntityType.names.company, primaryEntityId)
				&& !this._bindingTracker.isUnbound(entityInfo))
			{
				this._primaryEntityEditor.addBinding(
					this._primaryEntityEditor.createBinding(entityInfo)
				);
				eventArgs["cancel"] = true;
			}
		}
	};
	BX.Crm.EntityEditorClient.prototype.onSecondaryEntityAdd = function(editor, entityInfo)
	{
		this.addSecondaryEntity(entityInfo);
	};
	BX.Crm.EntityEditorClient.prototype.onSecondaryEntityBind = function(editor, entityInfo)
	{
		this._secondaryEntityEditor.removeItem(
			this._secondaryEntityEditor.getItemById(entityInfo.getId())
		);

		if(this._primaryEntityEditor)
		{
			this._primaryEntityEditor.addBinding(this._primaryEntityEditor.createBinding(entityInfo));
		}

		this._bindingTracker.bind(entityInfo);
	};
	BX.Crm.EntityEditorClient.prototype.getAllSecondaryEntityIds = function()
	{
		var entityInfos = this.getAllSecondaryEntityInfos();
		var results = [];
		for(var i = 0, length = entityInfos.length; i < length; i++)
		{
			results.push(entityInfos[i].getId());
		}
		return results;
	};
	BX.Crm.EntityEditorClient.prototype.getAllSecondaryEntityInfos = function()
	{
		return (
			[].concat(
				this._primaryEntityBindingInfos.getItems(),
				this._secondaryEntityInfos.getItems()
			)
		);
	};
	//endregion
	//region PrimaryEntityBindings
	BX.Crm.EntityEditorClient.prototype.getPrimaryEntityBindings = function()
	{
		return this._primaryEntityBindingInfos.getItems();
	};
	BX.Crm.EntityEditorClient.prototype.getPrimaryEntityBindingById = function(id)
	{
		if(!this._primaryEntityBindingInfos)
		{
			return null;
		}
		return this._primaryEntityBindingInfos.search(function(item){ return item.getId() === id; });
	};
	BX.Crm.EntityEditorClient.prototype.addPrimaryEntityBinding = function(entityInfo)
	{
		if(this._primaryEntityBindingInfos)
		{
			this._primaryEntityBindingInfos.add(entityInfo);
			this.markAsChanged();
		}
	};
	BX.Crm.EntityEditorClient.prototype.removePrimaryEntityBinding = function(entityInfo)
	{
		if(this._primaryEntityBindingInfos)
		{
			this._primaryEntityBindingInfos.remove(entityInfo);
			this.markAsChanged();
		}
	};
	BX.Crm.EntityEditorClient.prototype.onPrimaryEntityBindingAdd = function(editor, entityInfo)
	{
		this.addPrimaryEntityBinding(entityInfo);
	};
	BX.Crm.EntityEditorClient.prototype.onPrimaryEntityBindingDelete = function(editor, entityInfo)
	{
		this.removePrimaryEntityBinding(entityInfo);
	};
	BX.Crm.EntityEditorClient.prototype.onPrimaryEntityBindingRelease = function(editor, entityInfo)
	{
		this._bindingTracker.unbind(entityInfo);
		this._secondaryEntityEditor.addItem(this._secondaryEntityEditor.createItem(entityInfo));
	};
	//endregion
	BX.Crm.EntityEditorClient.prototype.onPrimaryEntityDelete = function(editor, entityInfo)
	{
		var secondaryEntityInfos = [].concat(this._primaryEntityBindingInfos.getItems(), this._secondaryEntityInfos.getItems());

		this._secondaryEntityInfos = BX.Collection.create();
		this._primaryEntityBindingInfos = BX.Collection.create();

		var primaryEntityInfo = null;
		if(secondaryEntityInfos.length > 0)
		{
			primaryEntityInfo = secondaryEntityInfos.shift();
		}

		this.setPrimaryEntity(primaryEntityInfo);
		this._primaryEntityEditor.setEntity(primaryEntityInfo);

		this._secondaryEntityEditor.setEntities(secondaryEntityInfos);
		this._secondaryEntityEditor.setVisible(primaryEntityInfo !== null);
	};
	BX.Crm.EntityEditorClient.prototype.onPrimaryEntityChange = function(editor, entityInfo)
	{
		this.setPrimaryEntity(entityInfo);

		if(this._primaryEntityTypeName === BX.CrmEntityType.names.company)
		{
			this._bindingTracker.reset();
			this._primaryEntityBindingInfos = BX.Collection.create();

			this._secondaryEntityInfos = BX.Collection.create();
			this._secondaryEntityEditor.clearItems();
			this._secondaryEntityEditor.reloadEntities();
		}

		this._secondaryEntityEditor.setVisible(true);
	};
	BX.Crm.EntityEditorClient.prototype.save = function()
	{
		var i, length, entityInfo;
		var map = this._schemeElement.getDataObjectParam("map", {});

		if(this._enablePrimaryEntity)
		{
			this._model.setMappedField(map, "primaryEntityType", this._primaryEntityTypeName);
			var primaryEntityId = this._primaryEntityInfo ? this._primaryEntityInfo.getId() : 0;
			this._model.setMappedField(map, "primaryEntityId", primaryEntityId);

			if(this._primaryEntityInfo)
			{
				this._info["PRIMARY_ENTITY_DATA"] = this._primaryEntityInfo.getSettings();
			}
			else
			{
				delete  this._info["PRIMARY_ENTITY_DATA"];
			}

			if(primaryEntityId > 0)
			{
				var unboundSecondaryEntities = this._bindingTracker.getUnboundEntities();
				var unboundSecondaryEntityIds = [];
				for(i = 0, length = unboundSecondaryEntities.length; i < length; i++)
				{
					unboundSecondaryEntityIds.push(unboundSecondaryEntities[i].getId());
				}
				if(unboundSecondaryEntityIds.length > 0)
				{
					for(i = 0, length = unboundSecondaryEntityIds.length; i < length; i++)
					{
						entityInfo = this.getSecondaryEntityById(unboundSecondaryEntityIds[i]);
						if(entityInfo)
						{
							entityInfo.removeEntityBinding(this._primaryEntityTypeName, primaryEntityId);
						}
					}
				}
				this._model.setMappedField(map, "unboundSecondaryEntityIds", unboundSecondaryEntityIds.join(","));

				var boundSecondaryEntities = this._bindingTracker.getBoundEntities();
				var boundSecondaryEntityIds = [];
				for(i = 0, length = boundSecondaryEntities.length; i < length; i++)
				{
					boundSecondaryEntityIds.push(boundSecondaryEntities[i].getId());
				}
				if(boundSecondaryEntityIds.length > 0)
				{
					for(i = 0, length = boundSecondaryEntityIds.length; i < length; i++)
					{
						entityInfo = this.getPrimaryEntityBindingById(boundSecondaryEntityIds[i]);
						if(entityInfo)
						{
							entityInfo.addEntityBinding(this._primaryEntityTypeName, primaryEntityId);
						}
					}
				}
				this._model.setMappedField(map, "boundSecondaryEntityIds", boundSecondaryEntityIds.join(","));

				this._bindingTracker.reset();
			}
		}

		this._model.setMappedField(map, "secondaryEntityType", this._secondaryEntityTypeName);
		var secondaryEntityInfos = this.getAllSecondaryEntityInfos();
		var secondaryEntityData = [];
		var secondaryEntityIds = [];
		for(i = 0, length = secondaryEntityInfos.length; i < length; i++)
		{
			entityInfo = secondaryEntityInfos[i];
			secondaryEntityData.push(entityInfo.getSettings());
			secondaryEntityIds.push(entityInfo.getId());
		}
		this._model.setMappedField(map, "secondaryEntityIds", secondaryEntityIds.join(","));
		this._info["SECONDARY_ENTITY_DATA"] = secondaryEntityData;
	};
	BX.Crm.EntityEditorClient.prototype.onBeforeSubmit = function()
	{
		if(!this._dataElements)
		{
			return;
		}

		for(var key in this._dataElements)
		{
			if(!this._dataElements.hasOwnProperty(key))
			{
				continue;
			}
			var name = BX.prop.getString(this._map, key, "");
			if(name !== "")
			{
				this._dataElements[key].value = this._model.getField(name, "");
			}
		}
	};
	BX.Crm.EntityEditorClient.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorClient();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorEntity === "undefined")
{
	/**
	 * @extends BX.Crm.EntityEditorField
	 * @constructor
	 */
	BX.Crm.EntityEditorEntity = function()
	{
		BX.Crm.EntityEditorEntity.superclass.constructor.apply(this);

		this._entityTypeName = "";
		this._entityInfo = null;

		this._entitySelectClickHandler = BX.delegate(this.onEntitySelectClick, this);
		this._entitySelectButton = null;
		this._entitySelector = null;

		this._editorWrapper = null;
		this._entityWrapper = null;
		this._dataInput = null;
		this._requisiteIdInput = null;
		this._bankDetailIdInput = null;
		this._skeleton = null;
		this._requisiteFieldNames = null;
		/**
		 * @type {BX.Crm.EntityEditorRequisiteTooltip}
		 * @protected
		 */
		this._tooltip = null;
		/**
		 * @type {BX.Crm.RequisiteList}
		 * @protected
		 */
		this._requisiteList = null;
		/**
		 * @type {BX.Crm.ClientEditorEntityPanel}
		 * @protected
		 */
		this._item = null;
		/**
		 * @type {BX.Crm.EntityEditorRequisiteEditor}
		 * @protected
		 */
		this._requisiteEditor = null;
	};
	BX.extend(BX.Crm.EntityEditorEntity, BX.Crm.EntityEditorField);
	BX.Crm.EntityEditorEntity.prototype.doInitialize = function()
	{
		BX.Crm.EntityEditorEntity.superclass.doInitialize.apply(this);

		this.initializeFromModel();
		if(this._schemeElement.getDataBooleanParam("enableMyCompanyOnly", false))
		{
			this._requisiteFieldNames = this._schemeElement.getDataObjectParam("requisiteFieldNames", {
				requisiteId: 'MC_REQUISITE_ID',
				bankDetailId: 'MC_BANK_DETAIL_ID',
			});
		}
	};
	BX.Crm.EntityEditorEntity.prototype.initializeFromModel = function()
	{
		var entityInfo = this._model.getSchemeField(this._schemeElement, "info", null);
		if (entityInfo)
		{
			entityInfo = BX.CrmEntityInfo.create(entityInfo);
		}

		this.setEntity(entityInfo);
		this.setEntityTypeName(this._schemeElement.getDataStringParam("entityTypeName", ""));
	};
	BX.Crm.EntityEditorEntity.prototype.initializeTooltip = function()
	{
		if(!this._entityInfo || !this._schemeElement.getDataBooleanParam("enableMyCompanyOnly", false))
		{
			return;
		}
		if(
			!this._tooltip
			&& BX.Crm.EntityEditorRequisiteTooltip
		)
		{
			this._tooltip = BX.Crm.EntityEditorRequisiteTooltip.create(
				this.getId() + '_rq',
				{
					readonly: true,
					canChangeDefaultRequisite: !this.isReadOnly(),
				}
			);
			BX.Event.EventEmitter.subscribe(this._tooltip, 'onSetSelectedRequisite', this.onSetSelectedRequisite.bind(this));
			BX.Event.EventEmitter.subscribe(this._tooltip, 'onEditRequisite', this.onEditRequisite.bind(this));
		}
		if(this._tooltip)
		{
			this._requisiteList = BX.Crm.RequisiteList.create(this._entityInfo.getRequisites());
			this._requisiteEditor = BX.Crm.EntityEditorRequisiteEditor.create(this._id + '_rq_editor', {
				entityTypeId: this._entityInfo.getTypeId(),
				entityId: this._entityInfo.getId(),
				contextId: this._editor.getContextId(),
				requisiteEditUrl: BX.prop.get(this._editor._settings, "requisiteEditUrl")
			});
			this._tooltip.setRequisites(this._requisiteList);
			this._requisiteEditor.setRequisiteList(this._requisiteList);
		}
	};
	BX.Crm.EntityEditorEntity.prototype.bindTitleMouseEvents = function()
	{
		if(this._tooltip && this._item && this._item._hasLayout)
		{
			BX.Event.bind(this._item.getTitleLink(), 'mouseenter', this.onTitleMouseEnter.bind(this));
			BX.Event.bind(this._item.getTitleLink(), 'mouseleave', this.onTitleMouseLeave.bind(this));
		}
	};
	BX.Crm.EntityEditorEntity.prototype.onTitleMouseEnter = function()
	{
		if(this._tooltip && this._item)
		{
			this._tooltip.setBindElement(this._item.getTitleLink(), this._wrapper);
			this._tooltip.showDebounced();
		}
	};
	BX.Crm.EntityEditorEntity.prototype.onTitleMouseLeave = function()
	{
		if(this._tooltip)
		{
			this._tooltip.closeDebounced();
			this._tooltip.cancelShowDebounced();
		}
	};
	BX.Crm.EntityEditorEntity.prototype.onEditRequisite = function(event)
	{
		var params = event.getData();
		var requisite = this._requisiteList.getById(params.id);
		if (requisite)
		{
			this._requisiteEditor.open(requisite, {});
		}
	};
	BX.Crm.EntityEditorEntity.prototype.getSelectedRequisiteAndBankDetailId = function()
	{
		var result = {
			requisiteId: 0,
			bankDetailId: 0
		};
		if(!this._requisiteList)
		{
			return result;
		}
		var requisite = this._requisiteList.getSelected();
		if(requisite)
		{
			result.requisiteId = requisite.getRequisiteId();
			var bankDetail = requisite.getBankDetailById(requisite.getSelectedBankDetailId());
			if(bankDetail)
			{
				result.bankDetailId = bankDetail.id;
			}
		}

		return result;
	};
	BX.Crm.EntityEditorEntity.prototype.onSetSelectedRequisite = function(event)
	{
		var data = event.getData();
		var requisiteIndex = BX.prop.getInteger(data, "id", 0);
		var bankDetailIndex = BX.prop.getInteger(data, "bankDetailId", 0);

		this._requisiteList.setSelected(requisiteIndex, bankDetailIndex);

		if(this.getOwnerId() > 0)
		{
			this._editor.saveControl(this);
		}
	};
	BX.Crm.EntityEditorEntity.prototype.getOwnerTypeName = function()
	{
		return this._editor.getEntityTypeName();
	};
	BX.Crm.EntityEditorEntity.prototype.getOwnerTypeId = function()
	{
		return this._editor.getEntityTypeId();
	};
	BX.Crm.EntityEditorEntity.prototype.getOwnerId = function()
	{
		return this._editor.getEntityId();
	};
	BX.Crm.EntityEditorEntity.prototype.getEntityTypeName = function()
	{
		return this._entityTypeName;
	};
	BX.Crm.EntityEditorEntity.prototype.setEntityTypeName = function(entityType)
	{
		if(this._entityTypeName === entityType)
		{
			return;
		}

		this._entityTypeName = entityType;
		if(this._entitySelector)
		{
			this._entitySelector = null;
		}
	};
	BX.Crm.EntityEditorEntity.prototype.setEntity = function(entityInfo)
	{
		if(this._item)
		{
			if(this._hasLayout)
			{
				this._item.clearLayout();
			}
			this._item = null;
		}

		if(!(entityInfo instanceof BX.CrmEntityInfo))
		{
			this._entityInfo = null;
		}
		else
		{
			this._entityInfo = entityInfo;
			this.setEntityTypeName(this._entityInfo.getTypeName());
			var enableCommunications = (
				this._entityInfo.getTypeId() === BX.CrmEntityType.enumeration.contact
				|| this._entityInfo.getTypeId() === BX.CrmEntityType.enumeration.company
			);
			this._item = BX.Crm.ClientEditorEntityPanel.create(
				this._id +  "_" + this._entityInfo.getId().toString(),
				{
					editor: this,
					entityInfo: this._entityInfo,
					enableEntityTypeCaption: false,
					enableRequisite: true,
					//requisiteBinding: BX.prop.getObject(this._settings, "requisiteBinding", {}),
					mode: this._mode,
					onDelete: BX.delegate(this.onItemDelete, this),
					enableCommunications: enableCommunications
				}
			);

			this.initializeTooltip();

			if(this._hasLayout)
			{
				this._item.setContainer(this._entityWrapper);
				this._item.layout();

				this.bindTitleMouseEvents();
			}
		}
	};
	BX.Crm.EntityEditorEntity.prototype.showSkeleton = function()
	{
		if (this._item)
		{
			this._item.clearLayout();
		}

		if(!this._skeleton)
		{
			this._skeleton = BX.Crm.ClientEditorEntitySkeleton.create(this._id, { container: this._entityWrapper });
		}
		this._skeleton.layout();
	};
	BX.Crm.EntityEditorEntity.prototype.hideSkeleton = function()
	{
		if(this._skeleton)
		{
			this._skeleton.clearLayout();
		}

		if (this._item)
		{
			this._item.setContainer(this._entityWrapper);
			this._item.layout();
		}
	};
	BX.Crm.EntityEditorEntity.prototype.onItemDelete = function(item)
	{
		this.setEntity(null);
		this.markAsChanged();
	};
	BX.Crm.EntityEditorEntity.prototype.reset = function()
	{
		this.initializeFromModel();
	};
	BX.Crm.EntityEditorEntity.prototype.rollback = function()
	{
		if(this.isChanged())
		{
			this.initializeFromModel();
		}
	};
	BX.Crm.EntityEditorEntity.prototype.getContentWrapper = function()
	{
		return this._entityWrapper;
	};
	BX.Crm.EntityEditorEntity.prototype.getModeSwitchType = function(mode)
	{
		var result = BX.UI.EntityEditorModeSwitchType.common;
		if(mode === BX.UI.EntityEditorMode.edit)
		{
			result |= BX.UI.EntityEditorModeSwitchType.button|BX.UI.EntityEditorModeSwitchType.content;
		}
		return result;
	};
	BX.Crm.EntityEditorEntity.prototype.doSetMode = function(mode)
	{
		this.rollback();
		if(this._item)
		{
			this._item.setMode(mode);
		}
	};
	BX.Crm.EntityEditorEntity.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		this.prepareLayout();

		var isViewMode = this._mode === BX.UI.EntityEditorMode.view;

		if(!isViewMode)
		{
			this._entitySelectButton = BX.create("span",
				{
					props: { className: "crm-entity-widget-actions-btn-select" },
					text: this.getMessage("select"),
					events: { click: this._entitySelectClickHandler }
				}
			);

			var actionWrapper = BX.create("div",
				{
					props: { className: "crm-entity-widget-clients-actions-block" },
					children: [ this._entitySelectButton ]
				}
			);

			this._entityWrapper.appendChild(actionWrapper);

			this.appendInputs();
		}

		this.layoutItem();

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
	BX.Crm.EntityEditorEntity.prototype.prepareLayout = function()
	{
		if(this._hasLayout)
		{
			return;
		}

		if (!this._wrapper)
		{
			this._wrapper = BX.create("div", { props: { className: "ui-entity-editor-content-block" } });
		}
		this.adjustWrapper();

		if(this.isDragEnabled())
		{
			this._wrapper.appendChild(this.createDragButton());
		}

		this._wrapper.appendChild(this.createTitleNode(this._schemeElement.getTitle()));

		this._editorWrapper = BX.create("div");
		this._wrapper.appendChild(this._editorWrapper);

		this._entityWrapper = BX.create("div");
		this._editorWrapper.appendChild(this._entityWrapper);
	};
	BX.Crm.EntityEditorEntity.prototype.appendInputs = function()
	{
		if(this._hasLayout || !this._entityWrapper)
		{
			return;
		}
		this._dataInput = BX.create("input", {
			attrs: {
				name: this.getName(),
				type: "hidden",
				value: this.getValue()
			}
		});
		this._entityWrapper.appendChild(this._dataInput);
		if(this._schemeElement.getDataBooleanParam("enableMyCompanyOnly", false))
		{
			this._requisiteIdInput = BX.create("input", {
				attrs: {
					name: this._requisiteFieldNames.requisiteId,
					type: "hidden",
					value: this._model.getIntegerField(this._requisiteFieldNames.requisiteId, 0)
				}
			});
			this._bankDetailIdInput = BX.create("input", {
				attrs: {
					name: this._requisiteFieldNames.bankDetailId,
					type: "hidden",
					value: this._model.getIntegerField(this._requisiteFieldNames.bankDetailId, 0)
				}
			});
			this._entityWrapper.appendChild(this._requisiteIdInput);
			this._entityWrapper.appendChild(this._bankDetailIdInput);
		}
	};
	BX.Crm.EntityEditorEntity.prototype.layoutItem = function()
	{
		var isViewMode = this._mode === BX.UI.EntityEditorMode.view;

		if(this._item)
		{
			this._item.setContainer(this._entityWrapper);
			this._item.layout();

			this.bindTitleMouseEvents();
		}
		else if (isViewMode)
		{
			var emptyItemNode = BX.create("div",
				{
					props: { className: "crm-entity-widget-content-block-inner" },
					text: this.getMessage("isEmpty")
				}
			);
			this._entityWrapper.appendChild(emptyItemNode);
		}
	};
	BX.Crm.EntityEditorEntity.prototype.clearLayout = function(options)
	{
		if(this._item)
		{
			this._item.clearLayout();
		}

		if(!BX.prop.getBoolean(options, "preservePosition", false))
		{
			this._wrapper = BX.remove(this._wrapper);
		}
		else
		{
			BX.removeClass(this._wrapper, "ui-entity-editor-content-block-click-editable");
			BX.removeClass(this._wrapper, "ui-entity-editor-content-block-click-empty");
			this._wrapper = BX.cleanNode(this._wrapper);
			if(this.hasError())
			{
				this.clearError();
			}
		}

		this._entityWrapper = null;
		this._dataInput = null;
		this._requisiteIdInput = null;
		this._bankDetailIdInput = null;

		if(this._entitySelector)
		{
			if(this._entitySelector.isOpened())
			{
				this._entitySelector.close();
			}
			this._entitySelector = null;
		}

		this._hasLayout = false;
	};
	BX.Crm.EntityEditorEntity.prototype.onEntitySelectClick = function(e)
	{
		if(this._entitySelector && this._entitySelector.isOpened())
		{
			this._entitySelector.close();
			return;
		}

		if(!this._entitySelector)
		{
			this._entitySelector = this.createEntitySelector();
		}

		this._entitySelector.open();
	};
	BX.Crm.EntityEditorEntity.prototype.createEntitySelector = function()
	{
		var entityTypeName = this._entityTypeName;
		var parentEntityTypeId = this._schemeElement.getDataIntegerParam(
			'parentEntityTypeId',
			null
		);
		if (parentEntityTypeId && BX.CrmEntityType.isDynamicTypeByTypeId(parentEntityTypeId))
		{
			entityTypeName = 'DYNAMIC';
		}

		return BX.Crm.EntitySelector.create(
			this._id,
			{
				target: this._entitySelectButton,
				entityTypeName: entityTypeName,
				loader: this._schemeElement.getDataObjectParam("loader", null),
				onSelectCallback: BX.delegate(this.onEntitySelect, this),
				onBeforeEntityLoadCallback: BX.delegate(this.showSkeleton, this),
				onAfterEntityLoadCallback: BX.delegate(this.hideSkeleton, this),
				enableMyCompanyOnly: this._schemeElement.getDataBooleanParam("enableMyCompanyOnly", false),
				withRequisites: this._schemeElement.getDataBooleanParam("withRequisites", false),
				enableSearch: this._schemeElement.getDataBooleanParam("enableSearch", true),
				context: this._schemeElement.getDataStringParam("context", null),
				parentEntityTypeId: parentEntityTypeId,
			}
		);
	};
	/**
	 * @param {BX.CrmEntityInfo} entityInfo
	 */
	BX.Crm.EntityEditorEntity.prototype.onEntitySelect = function(entityInfo)
	{
		if(this._entitySelector)
		{
			this._entitySelector.close();
		}

		if(this._entityInfo && this._entityInfo.getId() === entityInfo.getId())
		{
			return;
		}

		this.setEntity(entityInfo);

		this.markAsChanged();
	};
	BX.Crm.EntityEditorEntity.prototype.save = function()
	{
		this._model.setField(this.getName(), this._entityInfo ? this._entityInfo.getId() : 0);
		if(this._requisiteList)
		{
			var selectedData = this.getSelectedRequisiteAndBankDetailId();
			this._model.setField(this._requisiteFieldNames.requisiteId, selectedData.requisiteId);
			this._model.setField(this._requisiteFieldNames.bankDetailId, selectedData.bankDetailId);
		}
	};
	BX.Crm.EntityEditorEntity.prototype.onBeforeSubmit = function()
	{
		if(this._dataInput)
		{
			this._dataInput.value = this._model.getField(this.getName(), "");
		}
		if(this._requisiteList)
		{
			if(this._requisiteIdInput)
			{
				this._requisiteIdInput.value = this._model.getField(this._requisiteFieldNames.requisiteId, "");
			}
			if(this._bankDetailIdInput)
			{
				this._bankDetailIdInput.value = this._model.getField(this._requisiteFieldNames.bankDetailId, "");
			}
		}
	};
	BX.Crm.EntityEditorEntity.prototype.prepareSaveData = function(data)
	{
		BX.Crm.EntityEditorEntity.superclass.prepareSaveData.call(this, data);
		if(this._requisiteList)
		{
			data[this._requisiteFieldNames.requisiteId] = this._model.getField(this._requisiteFieldNames.requisiteId, "");
			data[this._requisiteFieldNames.bankDetailId] = this._model.getField(this._requisiteFieldNames.bankDetailId, "");
		}
	}
	BX.Crm.EntityEditorEntity.prototype.getMessage = function(name)
	{
		var ownMessage = BX.prop.getString(BX.Crm.EntityEditorEntity.messages, name, null);
		if (ownMessage)
		{
			return ownMessage;
		}

		return BX.Crm.EntityEditorEntity.superclass.getMessage.call(this, name);
	};
	if(typeof(BX.Crm.EntityEditorEntity.messages) === "undefined")
	{
		BX.Crm.EntityEditorEntity.messages = {};
	}
	BX.Crm.EntityEditorEntity.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorEntity();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof BX.Crm.EntityEditorEntityTag === "undefined")
{
	/**
	 * @extends BX.Crm.EntityEditorEntity
	 * @constructor
	 */
	BX.Crm.EntityEditorEntityTag = function()
	{
		BX.Crm.EntityEditorEntityTag.superclass.constructor.apply(this);

		this._selectorNode = null;
		this._selectorSearchNode = null;
		this._selectorDialog = null;
		this._lastSearchQuery = null;
		this.onItemSelect = this.onItemSelect.bind(this);
	};
	BX.extend(BX.Crm.EntityEditorEntityTag, BX.Crm.EntityEditorEntity);
	BX.Crm.EntityEditorEntityTag.prototype.layout = function(options)
	{
		if(this._hasLayout)
		{
			return;
		}

		var isViewMode = this._mode === BX.UI.EntityEditorMode.view;

		this.prepareLayout();

		var iconClassName = 'ui-ctl-before ui-icon-border';
		if (this._entityTypeName.toLowerCase().indexOf('dynamic') !== -1)
		{
			iconClassName += ' ui-ctl-icon-crm-dynamic';
		}
		else
		{
			iconClassName += ' ui-ctl-icon-crm-' + this._entityTypeName.toLowerCase();
		}
		this._selectorNode = BX.create('div', {
			attrs: {
				className: 'ui-ctl ui-ctl-before-icon crm-entity-selector-container',
			},
			children: [
				BX.create('div', {
					attrs: {
						className: iconClassName
					}
				})
			]
		});

		this._selectorSearchNode = BX.create('input', {
			attrs: {
				type: 'text',
				className: 'ui-ctl-element ui-ctl-textbox',
				value: this._entityInfo ? this._entityInfo.getTitle() : ''
			},
			events: {
				click: function() {
					this.getSelectorDialog().show();
				}.bind(this),
				input: this.onSearchInput.bind(this)
			}
		});

		this._selectorNode.appendChild(this._selectorSearchNode);
		this.getSelectorDialog().setTargetNode(this._selectorSearchNode);

		this._editorWrapper.appendChild(this._selectorNode);

		if(isViewMode)
		{
			BX.hide(this._selectorNode);
			BX.show(this._entityWrapper);
		}
		else
		{
			BX.show(this._selectorNode);
			BX.hide(this._entityWrapper);

			this.appendInputs();
		}

		this.layoutItem();

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
	BX.Crm.EntityEditorEntityTag.prototype.onSearchInput = function(event)
	{
		var value = event.target.value;
		if (value !== this._lastSearchQuery)
		{
			this._lastSearchQuery = value;
			if (value.length === 0)
			{
				var selectedItems = this.getSelectorDialog().getSelectedItems();
				if (selectedItems[0])
				{
					selectedItems[0].deselect();
					this.onItemDelete();
				}
			}

			this.getSelectorDialog().search(value);
		}
	};
	BX.Crm.EntityEditorEntityTag.prototype.clearLayout = function(options)
	{
		this._selectorDialog = null;

		BX.Crm.EntityEditorEntityTag.superclass.clearLayout.call(this, options);
	};
	BX.Crm.EntityEditorEntityTag.prototype.getModeSwitchType = function()
	{
		return BX.UI.EntityEditorModeSwitchType.content;
	};
	BX.Crm.EntityEditorEntityTag.prototype.getSelectorDialog = function()
	{
		if(!this._selectorDialog)
		{
			var parentEntityTypeId = this._schemeElement.getDataIntegerParam("parentEntityTypeId", null);

			var entityId = (
				BX.CrmEntityType.isDynamicTypeByTypeId(parentEntityTypeId)
				? 'dynamic'
				:  this._entityTypeName.toLowerCase()
			);

			this._selectorDialog = new BX.UI.EntitySelector.Dialog({
				context: this._schemeElement.getDataStringParam("context", null),
				targetNode: this._selectorNode,
				multiple: false,
				entities: [
					{
						id: entityId,
						dynamicLoad: true,
						dynamicSearch: this._schemeElement.getDataBooleanParam("enableSearch", true),
						options: {
							enableMyCompanyOnly: this._schemeElement.getDataBooleanParam("enableMyCompanyOnly", false),
							withRequisites: this._schemeElement.getDataBooleanParam("withRequisites", false),
							entityTypeId: this._schemeElement.getDataIntegerParam("parentEntityTypeId", null),
						},
					},
				],
				id: this._id,
				events: {
					'Item:onSelect': this.onItemSelect,
					'Item:onDeselect': function(event) {
						this.onItemDeselect(event);
					}.bind(this),
				},
				clearSearchOnSelect: true,
				hideOnSelect: true,
				hideOnDeselect: true,
				showAvatars: false,
				height: 200,
				preselectedItems: [
					this._entityInfo ? [entityId, this._entityInfo.getId()] : null
				]
			});
		}

		return this._selectorDialog;
	};
	BX.Crm.EntityEditorEntityTag.prototype.onItemSelect = function(event)
	{
		var entityInfo;
		var item = event.getData().item;
		if(item && item.customData)
		{
			entityInfo = item.customData.get('entityInfo');
			if(entityInfo)
			{
				entityInfo = BX.CrmEntityInfo.create(entityInfo);
			}
		}

		if(entityInfo)
		{
			this.onEntitySelect(entityInfo);
			this._selectorSearchNode.value = entityInfo.getTitle();
		}
	};
	BX.Crm.EntityEditorEntityTag.prototype.onItemDeselect = function(event)
	{
		event.getData().item.getDialog().targetNode.value = '';
		this.onItemDelete({});
	};
	BX.Crm.EntityEditorEntityTag.prototype.getMessage = function(name)
	{
		var ownMessage = BX.prop.getString(BX.Crm.EntityEditorEntityTag.messages, name, null);
		if (ownMessage)
		{
			return ownMessage;
		}

		return BX.Crm.EntityEditorEntityTag.superclass.getMessage.call(this, name);
	};
	BX.Crm.EntityEditorEntityTag.prototype.validate = function(result)
	{
		if(!this.isEditable())
		{
			return true;
		}

		this.clearError();

		if(this.hasValidators())
		{
			return this.executeValidators(result);
		}

		var isValid = (
			!(this.isRequired() || this.isRequiredByAttribute())
			|| BX.util.trim(this._selectorSearchNode.value) !== ''
		);

		if(!isValid)
		{
			result.addError(BX.UI.EntityValidationError.create({ field: this }));
			this.showRequiredFieldError(this._selectorDialog);
		}
	 	return isValid;
	};
	if(typeof(BX.Crm.EntityEditorEntityTag.messages) === "undefined")
	{
		BX.Crm.EntityEditorEntityTag.messages = {};
	}
	BX.Crm.EntityEditorEntityTag.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorEntityTag();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof BX.Crm.EntityEditorDocumentNumber === "undefined")
{
	/**
	 * @extends BX.Crm.EntityEditorText
	 * @constructor
	 */
	BX.Crm.EntityEditorDocumentNumber = function()
	{
		BX.Crm.EntityEditorDocumentNumber.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.EntityEditorDocumentNumber, BX.Crm.EntityEditorText);

	BX.Crm.EntityEditorDocumentNumber.prototype.doPrepareContextMenuItems = function(menuItems)
	{
		BX.Crm.EntityEditorDocumentNumber.superclass.doPrepareContextMenuItems.apply(this, arguments);

		var numeratorSettingsUrl = this._schemeElement.getDataStringParam("numeratorSettingsUrl", null);
		if (numeratorSettingsUrl && BX.SidePanel && BX.SidePanel.Instance)
		{
			menuItems.push({ delimiter: true });

			menuItems.push({
				value: 'openNumeratorSettings',
				text: this.getMessage('numeratorSettingsContextItem')
			});
		}
	};
	BX.Crm.EntityEditorDocumentNumber.prototype.processContextMenuCommand = function(e, command)
	{
		BX.Crm.EntityEditorDocumentNumber.superclass.processContextMenuCommand.apply(this, arguments);

		if (command === 'openNumeratorSettings')
		{
			var numeratorSettingsUrl = this._schemeElement.getDataStringParam("numeratorSettingsUrl", null);
			if (numeratorSettingsUrl && BX.SidePanel && BX.SidePanel.Instance)
			{
				BX.SidePanel.Instance.open(numeratorSettingsUrl, {width: 480});
			}
		}
	};
	BX.Crm.EntityEditorDocumentNumber.prototype.getMessage = function(name)
	{
		var ownMessage = BX.prop.getString(BX.Crm.EntityEditorDocumentNumber.messages, name, null);
		if (ownMessage)
		{
			return ownMessage;
		}

		return BX.Crm.EntityEditorDocumentNumber.superclass.getMessage.call(this, name);
	};
	if (typeof(BX.Crm.EntityEditorDocumentNumber.messages) === "undefined")
	{
		BX.Crm.EntityEditorDocumentNumber.messages = {};
	}
	BX.Crm.EntityEditorDocumentNumber.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorDocumentNumber();
		self.initialize(id, settings);
		return self;
	};
}
