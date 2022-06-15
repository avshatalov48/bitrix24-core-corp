BX.namespace("BX.Crm");

if (typeof BX.Crm.EntityStoreDocumentProductListController === "undefined")
{
	BX.Crm.EntityStoreDocumentProductListController = function()
	{
		BX.Crm.EntityStoreDocumentProductListController.superclass.constructor.apply(this);
	};
	BX.extend(BX.Crm.EntityStoreDocumentProductListController, BX.UI.EntityEditorController);

	BX.Crm.EntityStoreDocumentProductListController.prototype.doInitialize = function()
	{
		BX.Crm.EntityStoreDocumentProductListController.superclass.doInitialize.apply(this);

		this._setProductListHandler = this.handleSetProductList.bind(this);
		this._tabShowHandler = this.onTabShow.bind(this);

		this._currencyId = this._model.getField('CURRENCY', '');

		BX.addCustomEvent(window, 'DocumentProductListController', this._setProductListHandler);
		BX.addCustomEvent(window, 'onEntityDetailsTabShow', this._tabShowHandler);
	};

	BX.Crm.EntityStoreDocumentProductListController.prototype.handleSetProductList = function (event)
	{
		var productList = event.getData()[0];
		this.setProductList(productList);

		BX.removeCustomEvent('DocumentProductListController', this._setProductListHandler);
	};

	BX.Crm.EntityStoreDocumentProductListController.prototype.reinitializeProductList = function ()
	{
		if (this.productList)
		{
			this.productList.reloadGrid(false);
		}
	};

	BX.Crm.EntityStoreDocumentProductListController.prototype.onTabShow = function (event)
	{
		var tab = event.getData();
		if (tab && tab[0].id === 'tab_products' && this.productList)
		{
			this.productList.handleOnTabShow();
			BX.removeCustomEvent(window, 'onEntityDetailsTabShow', this._tabShowHandler);
			BX.onCustomEvent(window, 'onDocumentProductListTabShow', this);
		}
	};

	BX.Crm.EntityStoreDocumentProductListController.prototype.innerCancel = function ()
	{
		this.rollback();
		if (this.productList)
		{
			this.productList.onInnerCancel();
		}

		this._currencyId = this._model.getField('CURRENCY');

		if (this.productList)
		{
			this.productList.changeCurrencyId(this._currencyId);
		}

		this._isChanged = false;
	};

	BX.Crm.EntityStoreDocumentProductListController.prototype.getCurrencyId = function()
	{
		return this._currencyId;
	};

	BX.Crm.EntityStoreDocumentProductListController.prototype.setProductList = function (productList)
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
		}
	};

	BX.Crm.EntityStoreDocumentProductListController.prototype.onAfterSave = function ()
	{
		BX.Crm.EntityStoreDocumentProductListController.superclass.onAfterSave.apply(this);
		if (this.productList)
		{
			this.productList.removeFormFields();
		}
	};

	BX.Crm.EntityStoreDocumentProductListController.prototype.productChange = function (disableSaveButton)
	{
		disableSaveButton = typeof disableSaveButton !== 'undefined' ?  disableSaveButton : false;
		this.markAsChanged();

		if (disableSaveButton)
		{
			this.disableSaveButton();
		}

		BX.onCustomEvent(window, 'onDocumentProductChange', [this.productList.getProductsFields()]);
	};

	BX.Crm.EntityStoreDocumentProductListController.prototype.onBeforeSubmit = function ()
	{
		if (this._editor.hasOwnProperty('_ajaxForm'))
		{
			var ajaxForm = this._editor._ajaxForm;
			if (ajaxForm.hasOwnProperty('_config'))
			{
				var action = ajaxForm._config.data.ACTION;
			}
		}

		var validated = true;
		if (action === 'saveAndDeduct')
		{
			validated = this.validateProductList();
		}

		if (this.productList && (this.isChanged() || this._editor.isNew()) && validated)
		{
			this.productList.compileProductData();
		}
	};

	BX.Crm.EntityStoreDocumentProductListController.prototype.enableSaveButton = function ()
	{
		if (this._editor.hasOwnProperty('_toolPanel'))
		{
			this._editor._toolPanel.enableSaveButton();
		}
	};

	BX.Crm.EntityStoreDocumentProductListController.prototype.disableSaveButton = function ()
	{
		if (this._editor.hasOwnProperty('_toolPanel'))
		{
			this._editor._toolPanel.disableSaveButton();
		}
	};

	BX.Crm.EntityStoreDocumentProductListController.prototype.setTotal = function (totalData)
	{};

	BX.Crm.EntityStoreDocumentProductListController.prototype.validateProductList = function ()
	{
		this.clearErrorCollection();
		if (this.productList)
		{
			const errorsArray = this.productList.validate();
			if (errorsArray.length > 0)
			{
				this.addErrorCollection(errorsArray);
				this._editor._toolPanel.clearErrors();
				if (this._editor.hasOwnProperty('_toolPanel'))
				{
					BX.onCustomEvent(window, 'onProductsCheckFailed', this._tabShowHandler);
					return false;
				}
			}
		}

		return true;
	};

	BX.Crm.EntityStoreDocumentProductListController.prototype.getErrorCollection = function ()
	{
		if (this.errorCollection instanceof Array)
		{
			return this.errorCollection;
		}
		else
		{
			return [];
		}
	}

	BX.Crm.EntityStoreDocumentProductListController.prototype.addErrorCollection = function (errorCollection)
	{
		if (errorCollection instanceof Array)
		{
			this.errorCollection = [...this.errorCollection, ...errorCollection];
		}
	}

	BX.Crm.EntityStoreDocumentProductListController.prototype.clearErrorCollection = function ()
	{
		this.errorCollection = [];
	}

	BX.Crm.EntityStoreDocumentProductListController.create = function(id, settings)
	{
		var self = new BX.Crm.EntityStoreDocumentProductListController();
		self.initialize(id, settings);
		return self;
	};
}
