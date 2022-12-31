BX.namespace("BX.Crm");

if (typeof BX.Crm.EntityProductListController === "undefined")
{
	BX.Crm.EntityProductListController = function()
	{
		BX.Crm.EntityProductListController.superclass.constructor.apply(this);

		this.productList = null;

		this.currencyId = '';
		this._isManualOpportunity = null;
		this._prevProductCount = 0;
		this._curProductCount = 0;

		this._editorModeChangeHandler = this.onEditorModeChange.bind(this);
		this._editorControlChangeHandler = this.onEditorControlChange.bind(this);
	};
	BX.extend(BX.Crm.EntityProductListController, BX.UI.EntityEditorController);

	BX.Crm.EntityProductListController.prototype.doInitialize = function()
	{
		BX.Crm.EntityProductListController.superclass.doInitialize.apply(this);

		BX.Currency.setCurrencies(BX.prop.getArray(this._config, 'currencyList', []));

		BX.addCustomEvent(window, 'EntityProductListController', this.handleSetProductList.bind(this));
		BX.addCustomEvent(window, 'onEntityDetailsTabShow', this.onTabShow.bind(this));
		BX.addCustomEvent(window, 'Crm.EntityProgress.Saved', BX.delegate(this.onEntityProgressSave, this));
		BX.addCustomEvent(
			window,
			'BX.UI.EntityEditorProductRowSummary:onDetailProductListLinkClick',
			this.detailsProductRowSummaryLinkClickHandler.bind(this)
		);

		BX.addCustomEvent(window,
			'BX.UI.EntityEditorProductRowSummary:onAddNewRowInProductList',
			() => {
				BX.onCustomEvent(window, 'OpenEntityDetailTab', ['tab_products']);
				setTimeout(() => {
					BX.onCustomEvent(window, 'onFocusToProductList');
				}, 500);
			});

		this._editor.addModeChangeListener(this._editorModeChangeHandler);

		this._currencyId = this._model.getField('CURRENCY_ID', '');

		setTimeout(BX.delegate(function()
		{
			var opportunityControl = this.getOpportunityControl();
			if (opportunityControl)
			{
				opportunityControl.addChangeAmountEditModeListener(this.onchangeAmountEditMode.bind(this));
			}
		}, this), 0);
		this._isManualOpportunity = this._model.getField('IS_MANUAL_OPPORTUNITY');

		// TODO: add func from BX.Crm.EntityEditorProductRowProxy.prototype.doInitialize
	};

	BX.Crm.EntityProductListController.prototype.getCurrencyId = function()
	{
		return this._currencyId;
	};

	BX.Crm.EntityProductListController.prototype.onTabShow = function(tab)
	{
		if (tab.getId() === 'tab_products' && this.productList)
		{
			this.productList.handleOnTabShow();
		}
	};

	BX.Crm.EntityProductListController.prototype.onEntityProgressSave = function(sender, data)
	{
		var semantic = BX.prop.getString(data, 'currentSemantics', '');
		if (semantic === 'success' || semantic === 'failure')
		{
			this.reinitializeProductList();
		}
	};

	BX.Crm.EntityProductListController.prototype.detailsProductRowSummaryLinkClickHandler = function()
	{
		BX.onCustomEvent(window, 'OpenEntityDetailTab', ['tab_products']);
	};

	BX.Crm.EntityProductListController.prototype.handleSetProductList = function(event)
	{
		var productList = event.getData()[0];
		this.setProductList(productList);
		this.notifyOpportunityControl();
	};

	BX.Crm.EntityProductListController.prototype.getProductListId = function()
	{
		return this.getConfigStringParam('productListId', '');
	};

	BX.Crm.EntityProductListController.prototype.reinitializeProductList = function()
	{
		if (this.productList)
		{
			this.productList.reloadGrid(false);
		}
	};

	BX.Crm.EntityProductListController.prototype.getProductList = function()
	{
		return this.productList;
	}

	BX.Crm.EntityProductListController.prototype.setProductList = function(productList)
	{
		if (this.productList === productList)
		{
			return;
		}

		if (this.productList)
		{
			this.productList.destroy();
		}

		this.productList = productList;

		if (this.productList)
		{
			this.productList.setController(this);
			this.productList.setForm(this._editor.getFormElement());

			if (this.productList.getCurrencyId() !== this.getCurrencyId())
			{
				this.productList.changeCurrencyId(this.getCurrencyId());
			}

			this._prevProductCount = this._curProductCount = this.productList.getProductCount();
			this.adjustLocks();
		}
	};

	BX.Crm.EntityProductListController.prototype.clearProductList = function()
	{
		this.productList = null;
	};

	BX.Crm.EntityProductListController.prototype.onAfterSave = function()
	{
		BX.Crm.EntityProductListController.superclass.onAfterSave.apply(this);
		this._isManualOpportunity = this._model.getField("IS_MANUAL_OPPORTUNITY", null);
		if (this._manualOpportunityHiddenInput)
		{
			BX.Dom.remove(this._manualOpportunityHiddenInput);
			this._manualOpportunityHiddenInput = null;
		}
		if (this.productList)
		{
			this.productList.removeFormFields();
		}
	};

	BX.Crm.EntityProductListController.prototype.onBeforeSubmit = function()
	{
		var form = this._editor.getFormElement(),
			isManualOpportunity = this._model.getField("IS_MANUAL_OPPORTUNITY", null);

		if (isManualOpportunity !== null)
		{
			if (!BX.findChild(
				form,
				{tagName: "input", attr: {name: 'IS_MANUAL_OPPORTUNITY'}},
				true,
				false
			))
			{
				this._manualOpportunityHiddenInput = BX.create("input",
					{
						attrs:
							{
								name: 'IS_MANUAL_OPPORTUNITY',
								type: "hidden",
								value: isManualOpportunity
							}
					}
				);
				form.appendChild(this._manualOpportunityHiddenInput);
			}
		}

		if (this.productList && (this.isChanged() || this._editor.isNew()))
		{
			this.productList.compileProductData();
		}
	};

	BX.Crm.EntityProductListController.prototype.changeSumTotal = function (totals, needMarkAsChanged, enableSaveButton)
	{
		enableSaveButton = typeof enableSaveButton !== 'undefined' ?  enableSaveButton : true;

		this.adjustTotals(totals, needMarkAsChanged);
		this._prevProductCount = this._curProductCount;
		this._curProductCount = this.productList ? this.productList.getProductCount() : 0;

		if (enableSaveButton)
		{
			this.enableSaveButton();
		}
	};

	BX.Crm.EntityProductListController.prototype.validate = function ()
	{
		if (this.productList && (this.isChanged() || this._editor.isNew()))
		{
			return this.productList.validateSubmit();
		}
	};

	BX.Crm.EntityProductListController.prototype.enableSaveButton = function ()
	{
		if (this._editor._toolPanel)
		{
			this._editor._toolPanel.enableSaveButton();
		}
	};

	BX.Crm.EntityProductListController.prototype.disableSaveButton = function ()
	{
		if (this._editor._toolPanel)
		{
			this._editor._toolPanel.disableSaveButton();
		}
	};

	BX.Crm.EntityProductListController.prototype.productAdd = function ()
	{
		// TODO: change patameters and logic
	};

	BX.Crm.EntityProductListController.prototype.productChange = function (disableSaveButton)
	{
		disableSaveButton = typeof disableSaveButton !== 'undefined' ? disableSaveButton : true;

		this.adjustLocks();
		this.markAsChanged();
		this.notifyOpportunityControl();

		if (disableSaveButton)
		{
			this.disableSaveButton();
		}
	};

	BX.Crm.EntityProductListController.prototype.productRemove = function ()
	{
		// TODO: change patameters and logic
	};

	BX.Crm.EntityProductListController.prototype.changeOffer = function ()
	{
		// TODO: change patameters and logic
	};

	BX.Crm.EntityProductListController.prototype.innerCancel = function()
	{
		BX.onCustomEvent(window, "EntityProductListController:onInnerCancel", [this]);
	};

	BX.Crm.EntityProductListController.prototype.adjustTotals = function(totals, needMarkAsChanged)
	{
		var opportunityControl = this.getOpportunityControl();
		if (
			opportunityControl &&
			opportunityControl.getManualOpportunityValue() === 'Y' &&
			this._prevProductCount === 0 &&
			this.productList.getProductCount() > 0 // only when product was added first time
		)
		{
			var popup = BX.UI.EditorAuxiliaryDialog.getById("manual_opportunity_mode_selector");
			if (popup)
			{
				popup.close();
			}

			BX.UI.EditorAuxiliaryDialog.create(
				"manual_opportunity_mode_selector",
				{
					title: BX.Crm.EntityProductListController.messages.manualOpportunityChangeModeTitle,
					content: BX.Crm.EntityProductListController.messages.manualOpportunityChangeModeText,
					zIndex: 100,
					overlay: true,
					buttons:
						[
							{
								id: "yes",
								type: BX.Crm.DialogButtonType.accept,
								text: BX.Crm.EntityProductListController.messages.manualOpportunityChangeModeYes,
								callback: BX.delegate(function(event)
								{
									event.getDialog().close();
									this.setManualOpportunity(false);
									this.doAdjustTotals(totals);
									if (needMarkAsChanged)
									{
										this.markAsChanged();
									}
								}, this)
							},
							{
								id: "no",
								type: BX.Crm.DialogButtonType.cancel,
								text: BX.Crm.EntityProductListController.messages.manualOpportunityChangeModeNo,
								callback: BX.delegate(function(event)
								{
									event.getDialog().close();
									this.setManualOpportunity(true);
									this.doAdjustTotals(totals);
									if (needMarkAsChanged)
									{
										this.markAsChanged();
									}
								}, this)
							}
						]
				}
			).open();
		}
		else
		{
			this.doAdjustTotals(totals);
			if (needMarkAsChanged)
			{
				this.markAsChanged();
			}
		}
	};

	BX.Crm.EntityProductListController.prototype.doAdjustTotals = function(totals)
	{
		var currencyId = this.getCurrencyId();

		if (this.isManualOpportunity())
		{
			return;
		}

		this._model.setField(
			"FORMATTED_OPPORTUNITY",
			BX.Currency.currencyFormat(totals.totalCost, currencyId, false),
			//totals["FORMATTED_SUM"],
			{ enableNotification: false }
		);

		this._model.setField(
			"FORMATTED_OPPORTUNITY_WITH_CURRENCY",
			BX.Currency.currencyFormat(totals.totalCost, currencyId, true),
			//totals["FORMATTED_SUM_WITH_CURRENCY"],
			{ enableNotification: false }
		);

		this._model.setField(
			"OPPORTUNITY",
			totals.totalCost,
			//totals["SUM"],
			{ enableNotification: true }
		);
	};

	BX.Crm.EntityProductListController.prototype.isManualOpportunity = function()
	{
		return (this._model.getField('IS_MANUAL_OPPORTUNITY') === 'Y');
	};

	BX.Crm.EntityProductListController.prototype.getOpportunityControl = function()
	{
		var opportunityControl = this._editor.getControlByIdRecursive('OPPORTUNITY_WITH_CURRENCY');
		if (opportunityControl instanceof BX.Crm.EntityEditorMoney)
		{
			return opportunityControl;
		}
		return null;
	};

	BX.Crm.EntityProductListController.prototype.notifyOpportunityControl = function()
	{
		var opportunityControl = this.getOpportunityControl();
		if (opportunityControl !== null)
		{
			opportunityControl.setHasRelatedProducts(this.productList ? this.productList.getProductCount() > 0 : false);
		}
	};

	BX.Crm.EntityProductListController.prototype.doAdjustLocks = function()
	{
		if (!this.productList || !this.productList.wasProductsInitiated())
		{
			return;
		}

		if(this.productList.getProductCount() > 0)
		{
			this._model.lockField('OPPORTUNITY');
		}
		else
		{
			this._model.unlockField('OPPORTUNITY');
		}
	};

	BX.Crm.EntityProductListController.prototype.rollback = function()
	{
		BX.Crm.EntityProductListController.superclass.rollback.apply(this);

		this._currencyId = this._model.getField('CURRENCY_ID');

		if (this._isManualOpportunity)
		{
			this.setManualOpportunity(this._isManualOpportunity === 'Y');
		}
		this.adjustLocks();

		if (this._isChanged)
		{
			this._isChanged = false;
		}
	};

	BX.Crm.EntityProductListController.prototype.adjustLocks = function()
	{
		if (!this.isManualOpportunity())
		{
			this.doAdjustLocks();
		}
	};

	BX.Crm.EntityProductListController.prototype.onEditorModeChange = function(sender)
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

	BX.Crm.EntityProductListController.prototype.onEditorControlChange = function(sender, params)
	{
		var name = BX.prop.getString(params, "fieldName", "");
		if(name !== "CURRENCY_ID")
		{
			return;
		}

		this._currencyId = BX.prop.getString(params, "fieldValue");

		if (this.productList)
		{
			this.productList.changeCurrencyId(this.getCurrencyId());
			this.markAsChanged();
		}
	};

	BX.Crm.EntityProductListController.prototype.onchangeAmountEditMode = function(field, isManual)
	{
		if (isManual)
		{
			if (!BX.UI.EditorAuxiliaryDialog.isItemOpened("enable_manual_opportunity_confirmation"))
			{

				BX.UI.EditorAuxiliaryDialog.create(
					"enable_manual_opportunity_confirmation",
					{
						title: BX.Crm.EntityProductListController.messages.manualOpportunityConfirmationTitle,
						content: BX.Crm.EntityProductListController.messages.manualOpportunityConfirmationText,
						zIndex: 100,
						overlay: true,
						buttons:
							[
								{
									id: "yes",
									type: BX.Crm.DialogButtonType.accept,
									text: BX.Crm.EntityProductListController.messages.manualOpportunityConfirmationYes,
									callback: BX.delegate(function(event)
									{
										event.getDialog().close();
										this.setManualOpportunity(true);
									}, this)
								},
								{
									id: "no",
									type: BX.Crm.DialogButtonType.cancel,
									text: BX.Crm.EntityProductListController.messages.manualOpportunityConfirmationNo,
									callback: function(event)
									{
										event.getDialog().close();
									}
								}
							]
					}
				).open();
			}
		}
		else
		{
			this._model.setField("OPPORTUNITY","");
			this.setManualOpportunity(false);

			if (this.productList)
			{
				this.productList.actionUpdateTotalData();
			}
		}
	};

	BX.Crm.EntityProductListController.prototype.setManualOpportunity = function(isManual)
	{
		if (isManual)
		{
			this._model.unlockField("OPPORTUNITY");
		}
		else
		{
			this.doAdjustLocks();
		}
		this._model.setField(
			"IS_MANUAL_OPPORTUNITY",
			isManual ? 'Y' : 'N',
			{ enableNotification: true }
		);
		this.markAsChanged();
	};

	BX.Crm.EntityProductListController.create = function(id, settings)
	{
		var self = new BX.Crm.EntityProductListController();
		self.initialize(id, settings);
		return self;
	}
}
