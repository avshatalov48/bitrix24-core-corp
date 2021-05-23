BX.namespace("BX.Crm");

if (typeof(BX.setSelectValue) === "undefined")
{
	BX.setSelectValue = function(select, value)
	{
		var i, j;
		var bFirstSelected = false;
		var bMultiple = !!(select.getAttribute('multiple'));
		if (!(value instanceof Array)) value = [value];
		for (i=0; i<select.options.length; i++)
		{
			for (j in value)
			{
				if (select.options[i].value == value[j])
				{
					if (!bFirstSelected) {bFirstSelected = true; select.selectedIndex = i;}
					select.options[i].selected = true;
					break;
				}
			}
			if (!bMultiple && bFirstSelected) break;
		}
	}
}

if (typeof(BX.setTextContent) === "undefined")
{
	BX.setTextContent = function(element, value)
	{
		if (element)
		{
			if (element.textContent !== undefined)
				element.textContent = value;
			else
				element.innerText = value;
		}
	}
}

if (typeof(BX.CrmProductEditor) === "undefined")
{
	BX.CrmDiscountType =
	{
		undefined: 0,
		monetary: 1,
		percentage: 2
	};
	BX.CrmProductEditor = function ()
	{
		this._id = "";
		this._settings = {};
		this._serviceUrl = "";
		this._currencyId = "";
		this._currencyFormat = "# ?";
		this._clientTypeName = "";

		this._products = [];
		this._dlgId = "";
		this._addedProduct = null;
		this._deletedProduct = null;
		this._lastRowNumber = 0;
		this._productCreateDialog = null;
		this._calculateTotalsTimer = null;
		this._locationID = 0;
		this._topButtons = [];
		this._modeButton = null;
		this._discountExists = false;
		this._taxExists = false;
		this._viewMode = true;

		this._form = null;
		this._placeHolder = null;
		this._dragContainer = null;

		this._choiceBtnEnabled = false;
		this._overlay = null;
		this._hasLayout = false;
	};
	BX.CrmProductEditor.prototype =
	{
		initialize: function (id, config)
		{
			this._id = id;
			this._settings = config ? config : {};

			this._serviceUrl = this.getSetting('serviceUrl', '');
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw 'CrmProductEditor: could not find service URL.';
			}

			var readOnly = this.isReadOnly();
			this._viewMode = !this.getSetting('initEditable', false) || readOnly;

			// location
			this._locationID = this.isLDTaxAllowed() ? this.getSetting("locationID", 0) : 0;
			if (this.isLDTaxAllowed())
				BX.addCustomEvent("CrmProductRowSetLocation", BX.delegate(this._handleChangeLocation, this));

			var items = typeof(this._settings['items']) != 'undefined' ? this._settings['items'] : [];
			for(var i = 0; i < items.length; i++)
			{
				var item = items[i];
				var rowID = item['rowID'];

				var settings = item['settings'];
				settings['readOnly'] = readOnly;
				settings['fields'] = this.getSetting('productFields', []);

				this.getNextRowNumber();
				this._products.push(BX.CrmProduct.create(settings, document.getElementById(rowID), this));
			}

			//this._ajustStyles();

			var choiceBtn = document.getElementById(this.getSetting('choiceBtnID', ''));
			if(choiceBtn)
			{
				this._topButtons.push(choiceBtn);
				BX.bind(
					choiceBtn,
					"click",
					BX.delegate(this.handleChoiceProductButtonClick, this)
				);
				this._choiceBtnEnabled = true;
			}

			var addBtnId = this.getSetting('addBtnID', ''),
				addBtn = BX(addBtnId);

			if(addBtn)
			{
				if (!this.isProductCardEnabled())
				{
					BX.bind(addBtn, "click", BX.delegate(this._handleAddBtnClick, this));
				}

				this._topButtons.push(addBtn);
			}

			var modeBtn = document.getElementById(this.getSetting('modeBtnID', ''));
			if(modeBtn)
			{
				this._modeButton = modeBtn;
				BX.bind(
					modeBtn,
					"click",
					BX.delegate(this.handleSwitchModeButtonClick, this)
				);
			}

			this._currencyId = this.getSetting('currencyID', '');
			this._currencyFormat = this.getSetting('currencyFormat', '# ?');

			this._clientSelectorId = this.getSetting('clientSelectorId', 'CLIENT');

			this._clientTypeName = this.getSetting('clientTypeName', '');
			BX.addCustomEvent(
				"CrmClientSelectorChange",
				BX.delegate(this._handleEntitySelectorChangeValue, this)
			);

			//region Form
			var formID = this.getSetting("formID", '');
			var form = BX.type.isNotEmptyString(formID) ? BX("form_" + formID) : null;
			if(form)
			{
				this.setForm(form);
			}
			//endregion

			var addRowBtn = BX(this.getSetting("addRowBtnID", ""));
			if (addRowBtn)
			{
				BX.bind(addRowBtn, "click", BX.delegate(this.handleProductRowAdd, this));
			}

			BX.addCustomEvent(
				'CrmHandleShowProductEditor',
				BX.delegate(this._onSelectTab, this)
			);

			BX.addCustomEvent(
				'CrmProductSearchDialog_SelectProduct',
				BX.delegate(this.handleProductChoiceById, this)
			);

			BX.addCustomEvent('InitiateInvoiceSumTotalChange', BX.delegate(this._handleRequestFromInvoiceOwner, this));
			BX.addCustomEvent('InvoiceAjaxSubmitResponse', BX.delegate(this._handleInvoiceAjaxSubmitResponse, this));

			if (this.isProductCardEnabled())
			{
				BX.addCustomEvent('SidePanel.Slider:onMessage', this.handleSliderMessage.bind(this));
			}

			this._discountExists = this.getSetting("_discountExistsInit", false);
			this._taxExists = this.getSetting("_taxExistsInit", false);

			this._dragContainer = BX.CrmProdoctRowListDragContainer.create(
				this.getId(),
				{
					editor: this,
					node: this.getTable()
				}
			);
			this._dragContainer.addDragFinishListener(BX.delegate(this._onItemDrop, this));
			if(BX.prop.getBoolean(this._settings, 'initLayout', true))
			{
				this.layout();
			}

			BX.onCustomEvent(window, "ProductRowEditorCreated", [this]);
		},
		_handleAddBtnClick: function()
		{
			var dialogSettings, dialog;

			dialogSettings = BX.clone(this._settings["productCreateDialogSettings"]);
			dialogSettings['initialControl'] = BX(this.getSetting('addBtnID', ''));
			dialogSettings['productAdditionHandler'] = BX.delegate(this.handleProductAdd, this);
			dialog = new BX.CrmProductCreateDialog(dialogSettings);
			dialog.show();
		},
		_handleRequestFromInvoiceOwner: function()
		{
			BX.onCustomEvent('InvoiceSumTotalChange', [this]);
		},
		_handleInvoiceAjaxSubmitResponse: function(params)
		{
			if (params)
			{
				var ob = params['ob'];
				var info = params['info'];
				if (ob && ob === this && info)
				{
					if(typeof(info['TOTALS']) != 'undefined')
					{
						this.refreshTotals(info['TOTALS'])
					}

					if (typeof(info['TAX_LIST']) != 'undefined')
					{
						this.setSetting('LDTaxes', info['TAX_LIST']);
						this.refreshTaxList();
					}
				}
			}
		},
		getNextRowNumber: function()
		{
			return ++this._lastRowNumber;
		},
		getId: function()
		{
			return this._id;
		},
		isReadOnly: function()
		{
			return this.getSetting('readOnly', false);
		},
		setReadOnly: function(readOnly)
		{
			var curMode = this.getSetting('readOnly', false);
			if (readOnly !== curMode)
			{
				this.setSetting('readOnly', readOnly);
				if (readOnly !== this._viewMode)
					this.toggleMode();
				this.layout();
			}
		},
		isInvoiceMode: function()
		{
			return this.getSetting('invoiceMode', false);
		},
		isViewMode: function()
		{
			return this._viewMode;
		},
		getForm: function()
		{
			return this._form;
		},
		setForm: function(form)
		{
			if(this._form === form)
			{
				return;
			}

			this._form = form;
			if(!this._form)
			{
				return;
			}

			BX.bind(this._form, "submit", BX.delegate(this.handleFormSubmit, this));
		},
		getTable: function()
		{
			var tableID = this.getSetting('productContainerID', '');
			return BX.type.isNotEmptyString(tableID) ? BX(tableID) : null;
		},
		getCurrencyElement: function()
		{
			var form = this.getForm();
			return form ? BX.findChild(form, { 'tag':'select', 'attr':{ 'name': 'CURRENCY_ID' } }, true, false) : null;
		},
		getExchRateElement: function()
		{
			var form = this.getForm();
			return form ? BX.findChild(form, { 'tag':'input', 'attr':{ 'name': 'EXCH_RATE' } }, true, false) : null;
		},
		getSetting:function (name, defaultval)
		{
			return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
		},
		setSetting:function(name, value)
		{
			this._settings[name] = value;
		},
		isProductCardEnabled:function()
		{
			return this.getSetting('newProductCard', false) === true;
		},
		reinitialize : function (products)
		{
			var i;

			for (i in this._products)
			{
				if (this._products.hasOwnProperty(i))
				{
					this._products[i].clean();
				}
			}

			this._products = [];

			var table = this.getTable();

			for (i in products)
			{
				if (!products.hasOwnProperty(i))
				{
					continue;
				}
				var row = this._createRow();
				table.tBodies[0].appendChild(row);

				var productElement = BX.CrmProduct.create(
					this._prepareProductSettings(products[i]),
					row,
					this
				);
				productElement.layout();

				this._products[this._products.length++] = productElement;
			}

			this.calculateTotals(false);

		},
		_createRow: function ()
		{
			var rowIdPrefix = this.getSetting("rowIdPrefix", "");
			var exampleRow = BX(rowIdPrefix + "#N#");
			var rowNumber, rowIndex;
			var row = BX.clone(exampleRow, true);
			var nProducts = this._products.length;

			rowNumber = this.getNextRowNumber();
			rowIndex = rowNumber - 1;
			row.id = row.id.replace("#N#", rowIndex);
			row.className = (rowNumber % 2) === 0 ? "crm-items-table-even-row" : "crm-items-table-odd-row";

			BX.findChildren(row, function (el) {
				if (el && BX.type.isElementNode(el))
				{
					if (el.id)
					{
						var elId = el.id;
						if (elId && elId.indexOf("#N#") >= 0)
							el.id = elId.replace("#N#", rowIndex);
					}
					if (el.className === 'crm-item-num')
						BX.setTextContent(el, (nProducts + 1).toString() + ".");
				}
			}, true);

			row.style.display = "";

			return row;

		},
		handleBeforeSearch: function(data)
		{
			//{ 'entityType','postData'};
			if(data['entityType'] == 'product')
			{
				data['postData']['ENABLE_SEARCH_BY_ID'] = 'N';
				if(this._currencyId !== '')
				{
					data['postData']['CURRENCY_ID'] = this._currencyId;
				}

				var exchRate = this.getExchRateElement();
				if(exchRate)
				{
					var s = exchRate.value;
					data['postData']['EXCH_RATE'] = BX.type.isNotEmptyString(s) ? parseFloat(s) : 0.0;
				}

				data['postData']['ENABLE_RAW_PRICES'] = (!!this.getSetting('enableRawCatalogPricing', true)) ? 'Y' : 'N';
			}
		},
		productsToJson: function()
		{
			var json = '';
			if(this._products.length > 0)
			{
				for(var i = 0; i < this._products.length; i++)
				{
					json += (json.length > 0 ? ', ' : '') + this._products[i].toJson();
				}

				json = '[' + json + ']';
			}

			return json;
		},
		handleFormSubmit: function()
		{
			if(!this._form)
			{
				return;
			}

			var fieldName = this.getSetting("dataFieldName", "PRODUCT_ROW_DATA");
			var field = BX.findChild(
				this._form,
				{ tagName: "input", attr: { name: fieldName } },
				true,
				false
			);
			if(!field)
			{
				field = BX.create("input", { attrs: { type: "hidden", name: fieldName } });
				this._form.appendChild(field);
			}

			var settingFieldName = fieldName + "_SETTINGS";
			var settingField = BX.findChild(
				this._form,
				{ tagName: "input", attr: { name: settingFieldName } },
				true,
				false
			);
			if(!settingField)
			{
				settingField = BX.create("input", { attrs: { type: "hidden", name: settingFieldName } });
				this._form.appendChild(settingField);
			}

			if(this._hasLayout)
			{
				this.cleanProductRows();
				this.resortProducts();
			}

			field.value = this.productsToJson();
			settingField.value = '{"ENABLE_DISCOUNT": "' + (this.isDiscountEnabled() ? 'Y' : 'N') + '",' +
				'"ENABLE_TAX": "' + (this.isTaxEnabled() ? 'Y' : 'N') + '"}';

		},
		removeFormFields: function()
		{
			if(!this._form)
			{
				return;
			}
			var fieldName = this.getSetting("dataFieldName", "PRODUCT_ROW_DATA");
			var field = BX.findChild(
				this._form,
				{ tagName: "input", attr: { name: fieldName } },
				true,
				false
			);
			if(field)
			{
				BX.remove(field);
			}
			var settingFieldName = fieldName + "_SETTINGS";
			var settingField = BX.findChild(
				this._form,
				{ tagName: "input", attr: { name: settingFieldName } },
				true,
				false
			);
			if(settingField)
			{
				BX.remove(settingField);
			}
		},
		handleControlSave: function(data)
		{
			var fieldName = this.getSetting("dataFieldName", "PRODUCT_ROW_DATA");
			data[fieldName] = this.productsToJson();
			data[fieldName + "_SETTINGS"] = '{"ENABLE_DISCOUNT": "' + (this.isDiscountEnabled() ? 'Y' : 'N') + '",' +
				'"ENABLE_TAX": "' + (this.isTaxEnabled() ? 'Y' : 'N') + '"}';
			return data;
		},
		handleChoiceProductButtonClick: function(e)
		{
			if (!this._choiceBtnEnabled)
				return;

			this._choiceBtnEnabled = false;

			this.createOverlay(995);

			var choiceBtn = document.getElementById(this.getSetting('choiceBtnID', ''));
			if(choiceBtn)
				BX.addClass(choiceBtn, "webform-small-button-wait");

			var caller = 'crm_productrow_list';
			var jsEventsManagerId = this.getSetting("jsEventsManagerId", "");
			var dlg = BX.CrmProductSearchDialogWindow.create(
				{
					content_url: "/bitrix/components/bitrix/crm.product_row.list/product_choice_dialog.php" +
						"?caller=" + caller + "&JS_EVENTS_MANAGER_ID=" + BX.util.urlencode(jsEventsManagerId) +
						"&sessid=" + BX.bitrix_sessid(),
					closeWindowHandler: BX.delegate(this.handleChoiceProductDialogClose, this),
					showWindowHandler: BX.delegate(this.handleChoiceProductDialogShow, this),
					jsEventsManagerId: jsEventsManagerId,
					height: Math.max(500, window.innerHeight - 400),
					width: Math.max(800, window.innerWidth - 400),
					minHeight: 500,
					minWidth: 800,
					draggable: true,
					resizable: true
				}
			);
			dlg.show();
		},
		handleChoiceProductDialogClose: function()
		{
			this._choiceBtnEnabled = true;
		},
		handleChoiceProductDialogShow: function()
		{
			var choiceBtn = document.getElementById(this.getSetting('choiceBtnID', ''));
			if(choiceBtn)
				BX.removeClass(choiceBtn, "webform-small-button-wait");
			this.removeOverlay();
		},
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
		},
		handleSwitchModeButtonClick: function()
		{
			if (!this._viewMode)
			{
				BX.showWait(this.getSetting('containerID', ''), BX.CrmProductEditorMessages.saving);
				this.cleanProductRows();
				this.resortProducts();
				this.saveProductRows();
			}
			else
			{
				this.toggleMode();
				if (this.getProductCount() === 0)
					this.productRowAdd();
			}
		},
		handleProductChoice: function(data, skipFocus, product)
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
				quantity: product instanceof BX.CrmProduct ? product.getQuantity() : 1.0,
				price: typeof(customData['price']) != 'undefined' ? parseFloat(customData['price']) : 0.0,
				customized: false,
				measureCode: typeof(measure['code']) !== 'undefined' ? parseInt(measure['code']) : 0,
				measureName: typeof(measure['name']) !== 'undefined' ? measure['name'] : '',
				tax: typeof(customData['tax']) !== 'undefined' ? customData['tax'] : {}
			};
			if (this._viewMode)
			{
				this.toggleMode();
			}

			if (product)
			{
				this._setItem(product, itemData);
			}
			else
			{
				this._addItem(itemData, true);
			}

			if (!skipFocus)
			{
				this.focusLastRow();
			}

			obCrm[this._dlgId] && obCrm[this._dlgId].ClearSelectItems();
		},
		handleProductChoiceById: function(productId)
		{
			productId = parseInt(productId);
			if (productId <= 0)
			{
				return;
			}

			var currencyID = this.getCurrencyId();
			var ajaxPageUrl = this.getSetting("productSearchUrl", "");
			BX.ajax({
				'url': ajaxPageUrl,
				'method': 'POST',
				'dataType': 'json',
				'data':
				{
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
		onProductChoiceByIdSuccess: function(response, product)
		{
			var data;
			if (response && response["data"])
			{
				data = response["data"];
				if (data[0])
				{
					data = {"product": [data[0]]};
					this.handleProductChoice(data, true, product);
				}
			}
		},
		onProductChoiceByIdFailure: function(data)
		{
		},
		handleProductSearchSelect: function(product, data)
		{
			if(!product || !data)
			{
				return;
			}

			var customData = typeof(data['customData']) !== 'undefined' ? data['customData'] : {};
			var measure = typeof(customData['measure']) !== 'undefined' ? customData['measure'] : {};
			var itemData =
			{
				id: data['id'],
				name: data['title'],
				quantity: 1.0,
				price: typeof(customData['price']) != 'undefined' ? parseFloat(customData['price']) : 0.0,
				customized: false,
				measureCode: typeof(measure['code']) !== 'undefined' ? parseInt(measure['code']) : 0,
				measureName: typeof(measure['name']) !== 'undefined' ? measure['name'] : '',
				tax: typeof(customData['tax']) !== 'undefined' ? customData['tax'] : {}
			};
			this._setItem(product, itemData);
			//this.focusProductRow(product);
		},
		handleProductDeletion: function(product)
		{
			if(!window.confirm(BX.CrmProductEditorMessages['deletionConfirm']))
			{
				return false;
			}

			this._deleteProduct(product);
			this.calculateTotalsDelayed();

			return true;
		},
		_onDeleteItemRequestSuccess: function(data)
		{
			if(this._processAjaxError(data))
			{
				return;
			}

			if(this._deletedProduct)
			{
				this._deleteProduct(this._deletedProduct);
				this._deletedProduct = null;
			}
			//this._ajustStyles();
			this.calculateTotalsDelayed();
		},
		_ondeleteItemRequestFailure: function(data)
		{
			this._processAjaxError(data);
		},
		handleSliderMessage: function(sliderEvent)
		{
			if (sliderEvent.getEventId() === 'Catalog.ProductCard::onCreate')
			{
				var slider = sliderEvent.getSender();
				if (slider)
				{
					var sliderEventData = sliderEvent.getData();
					var sliderData = slider.getData();

					if (sliderData.has('product'))
					{
						this.handleProductAddFromSlider(sliderEventData, sliderData.get('product'));
					}
					else
					{
						this.handleProductAddFromSlider(sliderEventData);
					}

					if (sliderEventData.sender && sliderEventData.sender._enableCloseConfirmation)
					{
						sliderEventData.sender._enableCloseConfirmation = false;
					}

					slider.close();
				}
			}
		},
		handleProductAddFromSlider: function(data, product)
		{
			if (!data || !data.entityId)
				return;

			BX.ajax({
				'url': this.getSetting('productSearchUrl', ''),
				'method': 'POST',
				'dataType': 'json',
				'data':
					{
						'MODE': 'SEARCH',
						'RESULT_WITH_VALUE': 'Y',
						'CURRENCY_ID': this.getCurrencyId(),
						'ENABLE_RAW_PRICES': 'Y',
						'ENABLE_SEARCH_BY_ID': 'Y',
						'MULTI': 'N',
						'VALUE': data.entityId,
						'LIMIT': 1
					},
				onsuccess: function (response) {
					this.onProductChoiceByIdSuccess(response, product);
				}.bind(this)
			});
		},
		handleProductAdd: function(data)
		{
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
			if (this._viewMode)
				this.toggleMode();
			this._addItem(itemData, true);
			this.focusLastRow();
		},
		handleProductRowAdd: function(e)
		{
			this.productRowAdd();
			return BX.PreventDefault(e);
		},
		productRowAdd: function()
		{
			var itemData =
			{
				id: 0,
				name: "",
				quantity: 1.0,
				price: 0.0,
				customized: true
			};
			if (this._viewMode)
				this.toggleMode();
			this._addItem(itemData, false);

			this.focusLastRow();
		},
		handleProductFocusChange: function(product, focused)
		{
			BX.onCustomEvent("onProductEditorFocusChange", [this, focused]);
		},
		focusLastRow: function()
		{
			var nProducts = this._products.length,
				lastProduct, row, cEdit;
			if (nProducts > 0)
			{
				lastProduct = this._products[nProducts - 1];
				this.focusProductRow(lastProduct);
			}
		},
		focusProductRow: function(product)
		{
			if (product)
			{
				var row = product._container;
				if (row)
				{
					var cEdit = BX(row.id + "_PRODUCT_NAME");
					if (cEdit)
					{
						cEdit.focus();
					}
				}
			}
		},
		getProducts: function()
		{
			return this._products;
		},
		//jsDD
		createPlaceHolder: function(info)
		{
			var productId = info["id"];
			var productIndex = info["index"];
			var table = this.getTable();

			if(productIndex < 0)
			{
				productIndex = this._products.length;
			}

			if(this._placeHolder)
			{
				if(this._placeHolder.getProductIndex() === productIndex)
				{
					return this._placeHolder;
				}

				table.deleteRow(this._placeHolder.getNode().rowIndex);
				this._placeHolder = null;
			}

			this._placeHolder = BX.CrmProductRowListPlaceholder.create(
				{
					editor: this,
					node: table.insertRow(productIndex + 1),
					productId: productId,
					productIndex: productIndex
				}
			);
			this._placeHolder.layout();
			return this._placeHolder;
		},
		getPlaceHolder: function()
		{
			return this._placeHolder;
		},
		removePlaceHolder: function()
		{
			if(this._placeHolder)
			{
				var table = this.getTable();
				table.deleteRow(this._placeHolder.getNode().rowIndex);
				this._placeHolder = null;
			}
		},
		processDraggedItemDrop: function(draggedItem)
		{
			if(this._viewMode)
			{
				return;
			}

			var contextData = draggedItem.getContextData();
			var contextId = BX.type.isNotEmptyString(contextData["contextId"]) ? contextData["contextId"] : "";

			if(contextId !== BX.CrmProdoctRowListDragItem.contextId)
			{
				return;
			}

			var product = typeof(contextData["product"]) !== "undefined" ? contextData["product"] : null;
			if(!product)
			{
				return;
			}

			var table = this.getTable();
			var placeHolder = this.getPlaceHolder();
			if(placeHolder)
			{
				table.tBodies[0].insertBefore(product.getContainer(), placeHolder.getNode());
			}
			else
			{
				table.tBodies[0].appendChild(product.getContainer());
			}

			var oldIndex = this.getProductIndex(product);
			var newIndex = placeHolder.getProductIndex();
			if(oldIndex >= 0 && newIndex >= 0 && oldIndex !== newIndex)
			{
				this._products.splice(oldIndex, 1);
				if(oldIndex < newIndex)
				{
					newIndex--;
				}
				this._products.splice(newIndex, 0, product);
			}

			this._renumProducts(0);
			this.resortProducts();
		},
		_onItemDrop: function(dragContainer, draggedItem, x, y)
		{
			this.processDraggedItemDrop(draggedItem);
		},
		_prepareProductSettings: function(item, product)
		{
			product =  (product && product instanceof BX.CrmProduct) ? product : null;

			var nProducts = this._products.length;

			var itemId = parseInt(item['id']);
			if(isNaN(itemId))
			{
				itemId = 0;
			}

			var itemName = BX.type.isNotEmptyString(item['name']) ? item['name'] : ""/*("[" + itemId.toString() + "]")*/;

			var itemQty = parseFloat(item['quantity']);
			if(isNaN(itemQty))
			{
				itemQty = 1.0;
			}

			var itemPrice = parseFloat(item['price']);
			if(isNaN(itemPrice))
			{
				itemPrice = 0.0;
			}

			var itemMeasureCode = parseInt(item['measureCode']);
			var itemMeasureName = BX.type.isNotEmptyString(item['measureName']) ? item['measureName'] : '';
			if(isNaN(itemMeasureCode) || itemMeasureCode < 0 || itemMeasureName === '')
			{
				var defaultMeasure = this.getSetting('defaultMeasure', null);
				if(defaultMeasure)
				{
					itemMeasureCode = defaultMeasure['CODE'];
					itemMeasureName = defaultMeasure['SYMBOL'];
				}
				else
				{
					itemMeasureCode = 0;
					itemMeasureName = '';
				}
			}

			var discountData = typeof(item['discount']) !== 'undefined' ? item['discount'] : null;
			if (discountData)
			{
				var discountTypeId = typeof(discountData['discountType']) !== 'undefined' ? parseInt(discountData['discountType']) : BX.CrmDiscountType.percentage;
				if (discountTypeId !== BX.CrmDiscountType.percentage
					&& discountTypeId !== BX.CrmDiscountType.monetary)
				{
					discountTypeId = BX.CrmDiscountType.percentage;
				}
				var discountRate = typeof(discountData['discountRate']) !== 'undefined'
					? BX.CrmProduct.round(parseFloat(discountData["discountRate"]), 2)
					: 0.0;
				var discountSum = typeof(discountData['discountSum']) !== 'undefined'
					? BX.CrmProduct.round(parseFloat(discountData["discountSum"]), 2)
					: 0.0;
			}


			var settings =
			{
				'PRODUCT_ID': itemId,
				'PRODUCT_NAME': itemName,
				'QUANTITY': itemQty,
				'MEASURE_CODE': itemMeasureCode,
				'MEASURE_NAME': itemMeasureName,
				'PRICE': itemPrice,
				'PRICE_EXCLUSIVE': itemPrice
			};

			if (discountData)
			{
				settings["DISCOUNT_TYPE_ID"] = discountTypeId;
				settings["DISCOUNT_RATE"] = discountRate;
				settings["DISCOUNT_SUM"] = discountSum;
			}

			var isCustomized = !!item["customized"];
			if(this.isTaxAllowed())
			{
				var taxData = typeof(item['tax']) !== 'undefined' ? item['tax'] : {};
				var itemTaxId = typeof(taxData['id']) !== 'undefined' ? parseInt(taxData['id']) : 0;
				var taxInfo = itemTaxId > 0 ? this.getTaxById(itemTaxId) : null;
				if(!taxInfo && itemId === 0)
				{
					taxInfo = this.getSetting('defaultTax', null);
				}
				settings['TAX_RATE'] = taxInfo ? BX.CrmProduct.round(parseFloat(taxInfo['VALUE']), 2) : 0.0;

				var isTaxInPrice = typeof(taxData['included']) !== 'undefined' ? !!taxData['included'] : false;
				if (isTaxInPrice)
				{
					settings['PRICE_EXCLUSIVE'] = BX.CrmProduct.round(BX.CrmProduct.calculateExclusivePrice(settings['PRICE'], settings['TAX_RATE']), 2);
				}
				else
				{
					settings['PRICE'] = BX.CrmProduct.round(BX.CrmProduct.calculateInclusivePrice(settings['PRICE'], settings['TAX_RATE']), 2);
				}

				if (nProducts > 0 && this.getSetting("taxUniform", true))
				{
					var lastProduct = this._products[nProducts - 1];
					if (lastProduct !== product || nProducts > 1)
					{
						if (lastProduct === product)
							lastProduct = this._products[nProducts - 2];
						var lastTaxIncluded = lastProduct.isTaxIncluded();
						if (lastTaxIncluded !== isTaxInPrice)
						{
							isTaxInPrice = lastTaxIncluded;
							isCustomized = true;
						}
					}
				}
				settings['TAX_INCLUDED'] = isTaxInPrice;
			}
			else
			{
				settings['TAX_RATE'] = 0.0;
				settings['TAX_INCLUDED'] = false;
			}

			//Is true for disable requests to product catalog
			settings['CUSTOMIZED'] = isCustomized;

			var sortNum = 0;
			if (product)
			{
				sortNum = product.getSort();
			}
			else
			{
				for(var j = 0; j < nProducts; j++)
				{
					var curSort = this._products[j].getSort();
					if(sortNum < curSort)
						sortNum = curSort;
				}
				sortNum += 10;
			}
			settings['SORT'] = sortNum;

			settings['readOnly'] = this.isReadOnly();
			settings["fields"] = this.getSetting("productFields", []);

			return settings;
		},
		_addItem: function(item, removeEmptyRow)
		{
			if (!!removeEmptyRow)
				this._removeLonelyEmptyRow();

			var table = this.getTable();
			var rowIdPrefix = this.getSetting("rowIdPrefix", "");
			var exampleRow = BX(rowIdPrefix + "#N#");
			var row = BX.clone(exampleRow, true);
			var fields = this.getSetting("productFields", []);
			var nProducts = this._products.length;
			var rowNumber, rowIndex;

			// prepare ids
			rowNumber = this.getNextRowNumber();
			rowIndex = rowNumber - 1;
			row.id = row.id.replace("#N#", rowIndex);
			row.className = (rowNumber % 2) === 0 ? "crm-items-table-even-row" : "crm-items-table-odd-row";

			BX.findChildren(row, function(el) {
				if (el && BX.type.isElementNode(el))
				{
					if (el.id)
					{
						var elId = el.id;
						if (elId && elId.indexOf("#N#") >= 0)
							el.id = elId.replace("#N#", rowIndex);
					}
					if (el.className === 'crm-item-num')
						BX.setTextContent(el, (nProducts + 1).toString() + ".");
				}
			}, true);

			row.style.display = "";
			table.tBodies[0].appendChild(row);
			/*var row = table.tBodies[0].insertRow(-1);*/

			var settings = this._prepareProductSettings(item);

			var product = BX.CrmProduct.create(settings, row, this);
			this._products[nProducts++] = product;

			if (nProducts === 1)
				this.layout();
			else
				product.layout();

			BX.onCustomEvent(this, 'productAdd', [ { "product": product } ]);

			this.calculateTotalsDelayed();
		},
		_setItem: function(product, item)
		{
			var settings = this._prepareProductSettings(item, product);

			product.setSettings(settings);
			product.layout();

			BX.onCustomEvent(this, 'productSet', [ { "product": product } ]);

			this.calculateTotalsDelayed();
		},
		_onAddItemRequestSuccess: function(data)
		{
			if(this._processAjaxError(data))
			{
				return;
			}

			if(this._addedProduct)
			{
				if(typeof(data['PRODUCT_ROW']) != 'undefined')
				{
					var settings = data['PRODUCT_ROW'];
					if(typeof(settings['ID']) != 'undefined')
					{
						this._addedProduct.setId(settings['ID']);
					}
				}
				this._addedProduct = null;
			}
			this.calculateTotalsDelayed();
		},
		_onAddItemRequestFailure: function(data)
		{
			this._processAjaxError(data);
		},
		_removeLonelyEmptyRow: function()
		{
			var nProducts, productName;

			if (!this._viewMode)
			{
				if (this._products.length === 1 && this._products[0] instanceof BX.CrmProduct)
				{
					productName = BX.util.trim(this._products[0].getProductName());
					if (productName === "")
					{
						this._products[0].clean();
						this._deleteProduct(this._products[0]);
					}
				}
			}
		},
		_deleteProduct: function(product)
		{
			for(var i = 0; i < this._products.length; i++)
			{
				if(this._products[i] == product)
				{
					this._products.splice(i, 1);
					break;
				}
			}

			if (this._products.length === 0)
			{
				BX.onCustomEvent(
					this,
					"sumTotalChange",
					[
						"0.00",
						{
							"TOTAL_SUM_FORMATTED": "",
							"TOTAL_SUM_FORMATTED_SHORT": "",
							"TOTAL_SUM": "0"
						}
					]
				);

				if(this.getSetting("initEditable", false) && this.getSetting("hideModeButton", false))
					this.layoutElements(this._viewMode);
			}

			this._renumProducts(i);

			BX.onCustomEvent(this, 'productRemove', [ { "product": product } ]);
		},
		_renumProducts: function(from)
		{
			from = from ? parseInt(from) : 0;
			for(var i = from; i < this._products.length; i++)
				this._products[i].setNumber(i + 1);
		},
		_onSelectTab: function(productEditor)
		{
			if (productEditor === this)
				BX.CrmProductEditor.arrangeColumns(this, true);
		},
		_onUpdateItemRequestSuccess: function(data)
		{
			if(this._processAjaxError(data))
			{
				return;
			}

			this.calculateTotalsDelayed();
		},
		_onUpdateItemRequestFailure: function(data)
		{
			this._processAjaxError(data);
		},
		_onSaveProductsRequestSuccess: function(data)
		{
			if(this._processAjaxError(data))
			{
				return;
			}

			if(BX.type.isArray(data['PRODUCT_ROW_IDS']))
			{
				//Synchronize product row IDs
				var ids = data['PRODUCT_ROW_IDS'];
				if(ids.length === this._products.length)
				{
					for(var i = 0, l = ids.length; i < l; i++)
					{
						this._products[i].setId(ids[i]);
					}
				}
			}

			if (!this._viewMode)
				this.toggleMode();
			BX.closeWait();
			this.calculateTotalsDelayed();
		},
		_onSaveProductsRequestFailure: function(data)
		{
			BX.closeWait();
			this._processAjaxError(data);
		},
		refreshTaxList: function()
		{
			var taxList = this.getSetting('LDTaxes', []);
			var firsId = this.getSetting('taxValueID', 'total_tax');
			if (firsId)
			{
				var firstItem = BX(firsId);
				firstItem = (firstItem && firstItem.parentNode) ? firstItem.parentNode : null;
				firstItem = (firstItem && firstItem.parentNode) ? firstItem.parentNode : null;
				if (firstItem)
				{
					var next;
					var container = firstItem.parentNode;
					if (container)
					{
						if (taxList && typeof(taxList) === 'object' && taxList.length > 0)
						{
							while (next = BX.findNextSibling(firstItem, {"tag": "tr", "class": "crm-tax-value"}))
							{
								lastSibling = next.sibling;
								container.removeChild(next);
							}
							var lastSibling = firstItem.nextSibling;
							firstItem.style.display = "none";

							var newItem, newTaxValueElement,
								totalTaxDisplay = (
									!this.getSetting("hideAllTaxes", false)
										&& (this.isLDTaxAllowed() || (this.isTaxAllowed() && this.isTaxEnabled()))
									) ? "" : "none";
							for (var i = 0; i < taxList.length; i++)
							{
								newItem = BX.create("TR", {
									"attrs": {
										"class": "crm-view-table-total-value crm-tax-value"
									},
									"children":
										[
											BX.create("TD", {
												"children":
													[
														BX.create("NOBR", {
															"text": BX.util.htmlspecialchars(taxList[i]["TAX_NAME"] + ":")
														})
													]
											}),
											BX.create("TD", {
												"children":
													[
														newTaxValueElement = BX.create("STRONG", {
															"attrs": {"class": "crm-view-table-total-value"},
															"html": taxList[i]["TAX_VALUE"]
														})
													]
											})
										]
								});
								if (newItem)
								{
									if (totalTaxDisplay !== "")
										newItem.style.display = totalTaxDisplay;
									if (i === 0)
									{
										container.removeChild(firstItem);
										newTaxValueElement.setAttribute("id", firsId);
									}
									if (lastSibling)
										container.insertBefore(newItem, lastSibling);
									else
										container.appendChild(newItem);
								}
							}
						}
					}
				}
			}
		},
		calculateTotals: function(needMarkAsChanged)
		{
			if (typeof(needMarkAsChanged) === "undefined")
			{
				needMarkAsChanged = true;
			}

			var productData = [];
			for(var i = 0; i < this._products.length; i++)
			{
				var product = this._products[i];
				product.saveSettings();

				var productId = product.getProductId();
				var item =
				{
					'PRODUCT_ID': productId,
					'PRODUCT_NAME': product.getProductName(),
					'QUANTITY': product.getQuantity(),
					'DISCOUNT_TYPE_ID': product.getDiscountTypeId(),
					'DISCOUNT_RATE': product.getDiscountRate(),
					'DISCOUNT_SUM': product.getDiscountSum(),
					'TAX_RATE': product.getTaxRate(),
					'TAX_INCLUDED': product.isTaxIncluded() ? 'Y' : 'N',
					'PRICE_EXCLUSIVE': product.getExclusivePrice(),
					'PRICE': product.getPrice(),
					'CUSTOMIZED': 'Y'
				};

				productData.push(item);
			}

			var successCallback = this._updateCalculatedTotals;
			if (!needMarkAsChanged)
			{
				successCallback = this._updateNoDemandCalculatedTotals;
			}

			BX.ajax(
				{
					'url': this._serviceUrl,
					'method': 'POST',
					'dataType': 'json',
					'data':
					{
						'MODE': 'CALCULATE_TOTALS',
						'OWNER_TYPE': this.getSetting('ownerType', ''),
						'OWNER_ID': this.getSetting('ownerID', 0),
						'PERMISSION_ENTITY_TYPE': this.getSetting('permissionEntityType', ''),
						'PRODUCTS': productData,
						'CURRENCY_ID': this._currencyId,
						'CLIENT_TYPE_NAME': this.getClientTypeName(),
						'SITE_ID': this.getSetting('siteId', ''),
						'LOCATION_ID': this._locationID,
						'ALLOW_LD_TAX': this.isLDTaxAllowed() ? 'Y' : 'N',
						'LD_TAX_PRECISION': this.getSetting('taxListPercentPrecision', 2)
					},
					onsuccess: BX.delegate(successCallback, this),
					onfailure: BX.delegate(this._onCalculateTotalsRequestFailure, this)
				}
			);
		},
		calculateTotalsDelayed: function()
		{
			if (this._calculateTotalsTimer)
				clearTimeout(this._calculateTotalsTimer);
			this._calculateTotalsTimer = setTimeout(BX.delegate(this._handleCalculateTotalsTimer, this), 1000);
		},
		_handleChangeLocation: function(locationInputId)
		{
			var locationId = 0,
				locationInput = document.getElementsByName(locationInputId)[0];

			if (locationInput && BX.type.isElementNode(locationInput))
			{
				locationId = locationInput.value;

				this._locationID = locationId;
				this.calculateTotalsDelayed();
			}
		},
		_handleEntitySelectorChangeValue: function (sender, eventArgs)
		{
			var target = eventArgs["target"];
			if(target !== "primaryEntity")
			{
				return;
			}

			var selectorId = null;
			if (BX.type.isPlainObject(eventArgs["selectorInfo"])
				&& BX.type.isNotEmptyString(eventArgs["selectorInfo"]["id"]))
			{
				selectorId = eventArgs["selectorInfo"]["id"];
			}
			if (selectorId !== this._clientSelectorId)
			{
				return
			}

			var data = BX.type.isPlainObject(eventArgs["data"]) ? eventArgs["data"] : {};
			var type = BX.type.isNotEmptyString(data["primaryEntityTypeName"])
				? data["primaryEntityTypeName"] : "";
			var value = (data["primaryEntityInfo"] instanceof BX.CrmEntityInfo)
				? data["primaryEntityInfo"].getId() : 0;

			var curType = this.getClientTypeName();
			var newType = curType;

			if (curType === BX.CrmEntityType.names.company)
			{
				if (type === BX.CrmEntityType.names.company && value == 0)
					newType = BX.CrmEntityType.names.contact;
			}
			else
			{
				if (type === BX.CrmEntityType.names.company && value > 0)
					newType = BX.CrmEntityType.names.company;
				else
					newType = BX.CrmEntityType.names.contact;
			}

			if (curType !== newType)
			{
				this.setClientTypeName(newType);
				if (this.isLDTaxAllowed())
					this.calculateTotalsDelayed();
			}
		},
		_handleCalculateTotalsTimer: function()
		{
			this.calculateTotals();
		},
		_updateCalculatedTotals: function(data)
		{
			if(this._processAjaxError(data))
			{
				return;
			}

			if(typeof(data['TOTALS']) != 'undefined')
			{
				this.refreshTotals(data['TOTALS'])
			}

			if (typeof(data['LD_TAXES']) != 'undefined')
			{
				this.setSetting('LDTaxes', data['LD_TAXES']);
				this.refreshTaxList();
			}
		},
		_updateNoDemandCalculatedTotals: function(data)
		{
			if(this._processAjaxError(data))
			{
				return;
			}

			if(typeof(data['TOTALS']) != 'undefined')
			{
				this.refreshTotals(data['TOTALS'], false)
			}

			if (typeof(data['LD_TAXES']) != 'undefined')
			{
				this.setSetting('LDTaxes', data['LD_TAXES']);
				this.refreshTaxList();
			}
		},
		refreshTotals: function(totals, needMarkAsChanged)
		{
			var ttl, s, el;

			el = BX(this.getSetting('TOTAL_BEFORE_DISCOUNT_ID', 'total_before_discount'));
			if(el)
			{
				s = BX.type.isNotEmptyString(totals['TOTAL_BEFORE_DISCOUNT_FORMATTED']) ? totals['TOTAL_BEFORE_DISCOUNT_FORMATTED'] : '';
				ttl = typeof(totals['TOTAL_BEFORE_DISCOUNT']) != 'undefined' ? parseFloat(totals['TOTAL_BEFORE_DISCOUNT']).toFixed(2) : '0.00';
				el.innerHTML = s !== '' ? s : this._currencyFormat.replace(/(^|[^&])#/, '$1' + ttl);
				//BX.onCustomEvent(this, 'totalBeforeDiscountChange', [ttl]);
			}

			el = BX(this.getSetting('TOTAL_DISCOUNT_ID', 'total_discount'));
			if(el)
			{
				s = BX.type.isNotEmptyString(totals['TOTAL_DISCOUNT_FORMATTED']) ? totals['TOTAL_DISCOUNT_FORMATTED'] : '';
				ttl = typeof(totals['TOTAL_DISCOUNT']) != 'undefined' ? parseFloat(totals['TOTAL_DISCOUNT']).toFixed(2) : '0.00';
				this._discountExists = (parseFloat(ttl) !== 0.0);
				el.innerHTML = s !== '' ? s : this._currencyFormat.replace(/(^|[^&])#/, '$1' + ttl);
				//BX.onCustomEvent(this, 'totalDiscountChange', [ttl]);
			}

			el = BX(this.getSetting('TOTAL_BEFORE_TAX_ID', 'total_before_tax'));
			if(el)
			{
				s = BX.type.isNotEmptyString(totals['TOTAL_BEFORE_TAX_FORMATTED']) ? totals['TOTAL_BEFORE_TAX_FORMATTED'] : '';
				ttl = typeof(totals['TOTAL_BEFORE_TAX']) != 'undefined' ? parseFloat(totals['TOTAL_BEFORE_TAX']).toFixed(2) : '0.00';
				el.innerHTML = s !== '' ? s : this._currencyFormat.replace(/(^|[^&])#/, '$1' + ttl);
				//BX.onCustomEvent(this, 'totalBeforeTaxChange', [ttl]);
			}

			el = BX(this.getSetting('taxValueID', 'total_tax'));
			if(el)
			{
				s = BX.type.isNotEmptyString(totals['TOTAL_TAX_FORMATTED']) ? totals['TOTAL_TAX_FORMATTED'] : '';
				ttl = typeof(totals['TOTAL_TAX']) != 'undefined' ? parseFloat(totals['TOTAL_TAX']).toFixed(2) : '0.00';
				this._taxExists = (parseFloat(ttl) !== 0.0);
				el.innerHTML = s !== '' ? s : this._currencyFormat.replace(/(^|[^&])#/, '$1' + ttl);
				//BX.onCustomEvent(this, 'totalTaxChange', [ttl]);
			}

			el = BX(this.getSetting('SUM_TOTAL_ID', 'sum_total'));
			if(el)
			{
				s = BX.type.isNotEmptyString(totals['TOTAL_SUM_FORMATTED']) ? totals['TOTAL_SUM_FORMATTED'] : '';
				ttl = typeof(totals['TOTAL_SUM']) != 'undefined' ? parseFloat(totals['TOTAL_SUM']).toFixed(2) : '0.00';
				el.innerHTML = s !== '' ? s : this._currencyFormat.replace(/(^|[^&])#/, '$1' + ttl);
				BX.onCustomEvent(this, 'sumTotalChange', [ttl, totals, needMarkAsChanged]);
			}
			this.switchTotalElements();
		},
		_onCalculateTotalsRequestFailure: function(data)
		{
			self._processAjaxError(data);
		},
		registerProductDialogId: function(dlgId)
		{
			this._dlgId = dlgId;
		},
		getCurrencyId: function()
		{
			return this._currencyId;
		},
		setCurrencyId: function(currencyId)
		{
			if(this._currencyId === currencyId)
			{
				return;
			}

			if (this._settings["productCreateDialogSettings"])
				this._settings["productCreateDialogSettings"]["ownerCurrencyId"] = currencyId;
			this.calculateProductPrices(currencyId);
		},
		getClientTypeName: function()
		{
			return this._clientTypeName;
		},
		setClientTypeName: function(clientTypeName)
		{
			if(this._clientTypeName === clientTypeName)
			{
				return;
			}

			this._clientTypeName = clientTypeName;
		},
		getProductCount: function()
		{
			return this._products.length;
		},
		convertMoney: function(srcSum, srcCurrencyId, dstCurrencyId, callback)
		{
			var self = this;
			BX.ajax(
				{
					'url': this._serviceUrl,
					'method': 'POST',
					'dataType': 'json',
					'data':
					{
						'MODE' : 'CONVERT_MONEY',
						'OWNER_TYPE': this.getSetting('ownerType', ''),
						'OWNER_ID': this.getSetting('ownerID', 0),
						'PERMISSION_ENTITY_TYPE': this.getSetting('permissionEntityType', ''),
						'DATA':
						{
							'SRC_SUM': srcSum,
							'SRC_CURRENCY_ID': srcCurrencyId,
							'DST_CURRENCY_ID': dstCurrencyId
						},
						'SITE_ID': this.getSetting('siteId', '')
					},
					onsuccess: function(data)
					{
						if(data['SUM'])
						{
							if(self._processAjaxError(data))
							{
								return;
							}

							try
							{
								callback(parseFloat(data['SUM']));
							}
							catch(ex)
							{
							}
						}
					},
					onfailure: function(data)
					{
						self._processAjaxError(data)
					}
				});
		},
		setCurrencyFormat: function(currencyFormat)
		{
			if (typeof(currencyFormat) !== "string" || currencyFormat.length <= 0)
				currencyFormat = "# ?";
			this._currencyFormat = currencyFormat;
			var currencyText = BX.util.trim(currencyFormat.replace('#', ''));
			var discountTypeText = this.getSetting("discountTypeText", []);
			if (discountTypeText[BX.CrmDiscountType.monetary])
				discountTypeText[BX.CrmDiscountType.monetary] = currencyText;

			var cPriceTitle = BX(this.getSetting("priceTitleId", ""));
			if (cPriceTitle)
			{
				var priceTitleText = BX.CrmProductEditorMessages["priceTitleText"];
				priceTitleText = priceTitleText.replace("#CURRENCY#", " (" + currencyText + ")");
				//BX.setTextContent(cPriceTitle, priceTitleText);
				cPriceTitle.innerHTML = priceTitleText;
			}
		},
		calculateProductPrices: function(dstCurrencyId)
		{
			var prevId = this._currencyId;
			this._currencyId = dstCurrencyId;

			var exchRate = this.getExchRateElement();

			var srcData = [];
			var taxAllowed = this.isTaxAllowed();
			var discountTypeId, taxIncluded;
			for(var i = 0; i < this._products.length; i++)
			{
				var p = this._products[i];
				discountTypeId = p.getDiscountTypeId();
				taxIncluded = p.isTaxIncluded();
				srcData.push({
					'ID':p.getSetting('PRODUCT_ID', '0'),
					'PRICE':p.getSetting((taxAllowed && !taxIncluded) ? 'PRICE_NETTO' : 'PRICE_BRUTTO', '0.0'),
					'DISCOUNT_TYPE_ID': discountTypeId,
					'DISCOUNT_VALUE': (discountTypeId === BX.CrmDiscountType.percentage)
						? p.getDiscountRate() : p.getDiscountSum()
				});
			}

			var self = this;
			BX.ajax(
			{
				'url': this._serviceUrl,
				'method': 'POST',
				'dataType': 'json',
				'data':
				{
					'MODE' : 'CALC_PRODUCT_PRICES',
					'OWNER_TYPE': this.getSetting('ownerType', ''),
					'OWNER_ID': this.getSetting('ownerID', 0),
					'PERMISSION_ENTITY_TYPE': this.getSetting('permissionEntityType', ''),
					'DATA':
					{
						'SRC_CURRENCY_ID': prevId,
						'SRC_EXCH_RATE': exchRate ? parseFloat(exchRate.value) : 0,
						'DST_CURRENCY_ID': dstCurrencyId,
						'PRODUCTS': srcData
					},
					'SITE_ID': this.getSetting('siteId', '')
				},
				onsuccess: function(data)
				{
					//if(typeof(data['CURRENCY_ID'])){
					//	currency.value = data['CURRENCY_ID'];
					//}

					if(typeof(data['EXCH_RATE']) && exchRate)
					{
						exchRate.value = parseFloat(data['EXCH_RATE']);
					}

					if(data['PRODUCTS'])
					{
						if(self._processAjaxError(data))
						{
							return;
						}

						var taxAllowed = self.isTaxAllowed();
						var discountTypeId, taxIncluded;
						self.setCurrencyFormat(data['CURRENCY_FORMAT'] ? data['CURRENCY_FORMAT'] : '# ?');
						for(var i = 0; i < data['PRODUCTS'].length; i++)
						{
							var pData = data['PRODUCTS'][i];
							var p = self._products[i];
							discountTypeId = parseInt(pData['DISCOUNT_TYPE_ID']);
							if (discountTypeId !== BX.CrmDiscountType.percentage
								&& discountTypeId !== BX.CrmDiscountType.monetary)
							{
								discountTypeId = BX.CrmDiscountType.percentage
							}
							taxIncluded = p.isTaxIncluded();
							p.setFieldValue(
								(taxAllowed && !taxIncluded) ? 'PRICE_NETTO' : 'PRICE_BRUTTO',
								parseFloat(pData['PRICE']).toFixed(2),
								true
							);
							if (discountTypeId === BX.CrmDiscountType.monetary)
							{
								p.refreshCurrencyText();
								p.setFieldValue(
									'DISCOUNT_SUM',
									parseFloat(pData['DISCOUNT_VALUE']).toFixed(2),
									true
								);
							}
						}
					}

					if(data['PRODUCT_POPUP_ITEMS'] && self._dlgId.length > 0)
					{
						obCrm[self._dlgId].SetPopupItems('product', data['PRODUCT_POPUP_ITEMS']);
					}
				},
				onfailure: function(data)
				{
					self._processAjaxError(data)
				}
			});
		},
		getMeasures: function()
		{
			return this.getSetting('measures', []);
		},
		prepareMeasureOptionData: function()
		{
			var result = [];
			var items = this.getSetting('measures', []);
			for(var i = 0; i < items.length; i++)
			{
				result.push({ 'text': items[i]['SYMBOL'], 'value': items[i]['CODE'] });
			}
			return result;
		},
		isTaxAllowed: function()
		{
			return this.getSetting('allowTax', false);
		},
		isLDTaxAllowed: function()
		{
			return this.getSetting('allowLDTax', false);
		},
		isTaxEnabled: function()
		{
			return this.getSetting('enableTax', false);
		},
		isDiscountEnabled: function()
		{
			return this.getSetting('enableDiscount', false);
		},
		getTaxes: function()
		{
			return this.getSetting('taxes', []);
		},
		prepareTaxOptionData: function()
		{
			var result = [];
			var items = this.getSetting('taxes', []);
			for(var i = 0; i < items.length; i++)
			{
				result.push({ 'text': items[i]['NAME'], 'value': items[i]['VALUE'] });
			}
			return result;
		},
		getTaxById: function(id)
		{
			id = parseInt(id);
			var items = this.getSetting('taxes', []);
			for(var i = 0; i < items.length; i++)
			{
				var item = items[i];
				if(parseInt(item['ID']) == id)
				{
					return item;
				}
			}
			return null;
		},
		getTaxNameByValue: function(value)
		{
			var items = this.getSetting('taxes', []);
			for(var i = 0; i < items.length; i++)
			{
				var item = items[i];
				if(parseFloat(item['VALUE']) == value)
				{
					return items[i]['NAME'];
				}
			}

			return value + '%';
		},
		getTaxIdByValue: function(value)
		{
			var items = this.getSetting('taxes', []);
			for(var i = 0; i < items.length; i++)
			{
				var item = items[i];
				if(parseFloat(item['VALUE']) == value)
				{
					return parseInt(items[i]['ID']);
				}
			}

			return 0;
		},
		_processAjaxError: function(data)
		{
			if(typeof(data['ERROR']) == 'undefined')
			{
				return false;
			}

			var error = data['ERROR'];
			if(typeof(BX.CrmProductEditorErrors[error]) != 'undefined')
			{
				error = BX.CrmProductEditorErrors[error];
			}
			else if(error == 'OWNER_TYPE_NOT_FOUND'
				|| error == 'OWNER_ID_NOT_FOUND'
				|| error == 'SOURCE_DATA_NOT_FOUND'
				|| error == 'ID_NOT_FOUND'
				|| error == 'PRODUCT_ID_NOT_FOUND'
				|| error == 'PRODUCT_ROWS_SAVING_ERROR')
			{
				// Process invalid request errors
				error = BX.CrmProductEditorErrors['INVALID_REQUEST_ERROR'];
			}

			this.showError(error);
			return true;
		},
		showError: function(msg)
		{
			alert(msg);
		},
		layoutElements: function(viewMode)
		{
			viewMode = !!viewMode;

			var readOnly = this.getSetting('readOnly', false),
				table = this.getTable(),
				nProducts = this._products.length;

			var topButtons = this._topButtons;
			for (i=0; i<topButtons.length; i++)
				topButtons[i].style.display = (readOnly || viewMode)  ? 'none' : '';

			var addRowButton = BX(this.getSetting("addRowBtnID", ""));
			if (addRowButton)
				addRowButton.style.display = (readOnly || viewMode) ? 'none' : '';

			this.setModeBtnStyle();

			var blocks = [];
			blocks.push(table);
			blocks.push(BX("crm-top-sale-tab"));
			blocks.push(BX("crm-top-spacer"));
			blocks.push(BX("crm-top-tax-tab"));
			blocks.push(BX(this.getSetting('productTotalContainerID', '')));

			for (var i=0; i<blocks.length; i++)
			{
				if (blocks[i])
					blocks[i].style.display = ((nProducts > 0) ? "" : "none");
			}
		},
		toggleMode: function()
		{
			var products = this._products;

			this.layoutElements(!this._viewMode);

			for (var i=0; i<products.length; i++)
				products[i].toggleMode();

			this._viewMode = !this._viewMode;

			this.setModeBtnStyle();
		},
		layout: function()
		{
			var i,
				nProducts = this._products.length;

			this.layoutElements(this._viewMode);

			for (i=0; i<nProducts; i++)
				this._products[i].layout();

			this._hasLayout = true;

			BX.CrmProductEditor.arrangeColumns(this, true);
		},
		hasLayout: function()
		{
			return this._hasLayout;
		},
		cleanProductRows: function()    // remove product rows with empty names
		{
			if(!this._hasLayout)
			{
				//Layout is not initialized - model can't be changed.
				return;
			}

			var product, i, from = -1;

			var nProductsBefore = this._products.length;
			for (i = nProductsBefore - 1; i >= 0; i--)
			{
				product = this._products[i];
				product.saveSettings();
				if (BX.util.trim(product.getProductName()) === "")
				{
					product.clean();
					this._products.splice(i, 1);
					from = i;
				}
			}
			if (this._products.length === 0 && nProductsBefore > 0)
			{
				BX.onCustomEvent(
					this,
					"sumTotalChange",
					[
						"0.00",
						{
							"TOTAL_SUM_FORMATTED": "",
							"TOTAL_SUM_FORMATTED_SHORT": "",
							"TOTAL_SUM": "0.00"
						}
					]
				);
			}
			if (from >= 0)
				this._renumProducts(from);
		},
		resortProducts: function()
		{
			var i;

			for (i=0; i<this._products.length; i++)
				this._products[i].setSort((i + 1) * 10);
		},
		saveProductRows: function()
		{
			BX.ajax({
				'url': this._serviceUrl,
				'method': 'POST',
				'dataType': 'json',
				'data':
				{
					'MODE': 'SAVE_PRODUCTS',
					'OWNER_TYPE': this.getSetting('ownerType', ''),
					'OWNER_ID': this.getSetting('ownerID', 0),
					'PERMISSION_ENTITY_TYPE': this.getSetting('permissionEntityType', ''),
					'PRODUCT_ROW_DATA': this.productsToJson(),
					'PRODUCT_ROW_SETTINGS': {
						'ENABLE_DISCOUNT': this.isDiscountEnabled() ? 'Y' : 'N',
						'ENABLE_TAX': this.isTaxEnabled() ? 'Y' : 'N'
					},
					'SITE_ID': this.getSetting('siteId', '')
				},
				onsuccess: BX.delegate(this._onSaveProductsRequestSuccess, this),
				onfailure: BX.delegate(this._onSaveProductsRequestFailure, this)
			});
		},
		getProductIndex: function(product)
		{
			for(var i = 0; i < this._products.length; i++)
			{
				if(this._products[i] === product)
				{
					return i;
				}
			}
			return -1;
		},
		getProductIndexByContainer: function(container)
		{
			if(!BX.type.isDomNode(container))
			{
				return null;
			}

			for(var i = 0; i < this._products.length; i++)
			{
				var product = this._products[i];
				if(product.getContainer() === container)
				{
					return i;
				}
			}
			return -1;
		},
		switchTotalElements: function()
		{
			var discountExists = this._discountExists,
				taxExists = this._taxExists,
				taxClassName = "crm-tax-value",
				i, el, sibling,
				totalDiscountDisplay = (this.isDiscountEnabled() || discountExists) ? "" : "none",
				totalTaxDisplay =
					(taxExists
						|| (!this.getSetting("hideAllTaxes", false)
						&& (this.isLDTaxAllowed() || (this.isTaxAllowed() && this.isTaxEnabled())))
					) ? "" : "none";

			var blocks = [
				{"id": "TOTAL_BEFORE_DISCOUNT_ID", "type": "discount"},
				{"id": "TOTAL_DISCOUNT_ID",        "type": "discount"},
				{"id": "TOTAL_BEFORE_TAX_ID",      "type": "tax"},
				{"id": "taxValueID",               "type": "tax"}
			];

			for (i = 0; i < blocks.length; i++)
			{
				el = BX(this.getSetting(blocks[i]["id"], ""));
				if (BX.type.isElementNode(el))
				{
					el = el.parentNode.parentNode;
					if (BX.type.isElementNode(el))
					{
						switch (blocks[i]["type"])
						{
							case "discount":
								el.style.display = totalDiscountDisplay;
								break;
							case "tax":
								el.style.display = totalTaxDisplay;
								if (blocks[i]["id"] === "taxValueID")
								{
									sibling = el;
									while (sibling)
									{
										sibling.style.display = totalTaxDisplay;
										sibling = BX.findNextSibling(sibling, {"class": taxClassName});
									}
								}
								break;
						}
					}
				}
			}
		},
		changeTaxIncludedUniform: function(exceptProduct, state)
		{
			var i,
				products = this._products;

			for (i = 0; i < products.length; i++)
			{
				if (products[i] !== exceptProduct)
					products[i]._emulateTaxIncludedElementChange(state);
			}
		},
		getPathToProductShow: function()
		{
			return this.getSetting("pathToProductShow", "");
		},
		setModeBtnStyle: function()
		{
			if (this._modeButton)
			{
				var readOnly = this.getSetting('readOnly', false),
					hideModeBtn = this.getSetting("hideModeButton", false),
					modeBtnParent = this._modeButton.parentNode,
					nProducts = this._products.length,
					messageId = this._viewMode ? (nProducts ? "crmProductRowBtnEdit" : "crmProductRowBtnAdd") : "crmProductRowBtnEditF",
					modeBtnTextContainer = BX.findChild(this._modeButton, {"attr": {"class": "webform-small-button-text"}});

				this._modeButton.style.display = (readOnly || hideModeBtn) ? 'none' : '';
				if (modeBtnTextContainer)
					BX.setTextContent(modeBtnTextContainer, BX.CrmProductEditorMessages[messageId]);
				if (this._viewMode && nProducts === 0)
				{
					//BX.addClass(this._modeButton, "webform-small-button-accept");
					modeBtnParent.style.cssFloat = "left";
				}
				else
				{
					//BX.removeClass(this._modeButton, "webform-small-button-accept");
					modeBtnParent.style.cssFloat = "right";
				}
			}
		},
		processProductChange: function(product)
		{
			BX.onCustomEvent(this, "productChange", [ { "product": product } ]);
		}
	};
	BX.CrmProductEditor.items = {};
	BX.CrmProductEditor.get = function (id)
	{
		return typeof(this.items[id]) != 'undefined' ? this.items[id] : null;
	};
	BX.CrmProductEditor.getDefault = function ()
	{
		return typeof(this.items['__default']) != 'undefined' ? this.items['__default'] : null;
	};
	BX.CrmProductEditor.create = function (id, config)
	{
		var self = new BX.CrmProductEditor();
		self.initialize(id, config);
		this.items[id] = self;
		if(typeof(this.items['__default']) == 'undefined')
		{
			this.items['__default'] = self;
		}
		return self;
	};
	BX.CrmProductEditor.arrangeColumns = function(productEditor, notEvent)
	{
		if (!productEditor || typeof(productEditor._settings) !== 'object'
			|| !productEditor._settings['containerID'] || !productEditor._settings['productContainerID'])
			return;

		var isTaxAllowed = productEditor.isTaxAllowed(),
			hideTaxIncluded = productEditor.getSetting("hideTaxIncludedColumn", false),
			tax = null,
			sale = BX('crm-top-sale-checkbox'),
			tabWidth = sale.parentNode.parentNode.offsetWidth,
			wrapper = BX(productEditor._settings['containerID']),
			table = BX(productEditor._settings['productContainerID']),
			tableWidth = table.offsetWidth,
			tableRow = table.rows[0],
			nCells = tableRow.cells.length,
			leftSpace = BX('crm-l-space'),
			spacerWidth = BX('crm-top-spacer').offsetWidth,
			onePercent = tableWidth / 100,
			width,
			widthSum = 0,
			correction = 0,
			setWidthTax =  [ 0, 0, 0, 0, 0, 0, 0, 0],
			setWidthSale = [29,10,10,10,10,10,18, 3],
			setWidthAll =  [ 0, 0, 0, 0, 0, 0, 0, 0],
			resetWidth =   [29,16,16,16, 0, 0,19, 3];

		if (isTaxAllowed)
		{
			tax = BX('crm-top-tax-checkbox');
			if (hideTaxIncluded)
			{
				setWidthTax =  [26,13,13,13, 0, 0, 0.5,10, 0,10,11, 3];
				setWidthSale = [24, 8, 8, 8,14,10, 0.5, 0, 0, 0,24, 3];
				setWidthAll =  [24, 8, 8, 8, 9, 8, 0.5,10, 0,10,11, 3];
				resetWidth =   [27,15,15,15, 0, 0, 0.5, 0, 0, 0,24, 3];
			}
			else
			{
				setWidthTax =  [26,13,13,13, 0, 0, 0.5, 7, 7, 7,10, 3];
				setWidthSale = [24, 8, 8, 8,15,15, 0.5, 0, 0, 0,18, 3];
				setWidthAll =  [24, 8, 8, 8,10, 7, 0.5, 7, 7, 7,10, 3];
				resetWidth =   [29,16,16,16, 0, 0, 0.5, 0, 0, 0,19, 3];
			}
		}

		// tax and discount checks
		if (!notEvent)
		{
			var ajaxData = null;
			if (tax && this === tax)
			{
				if (tax.checked !== productEditor.getSetting('enableTax', false))
				{
					productEditor.setSetting('enableTax', tax.checked);
					if (!ajaxData)
						ajaxData = {};
					ajaxData['SHOW_TAX'] = tax.checked ? 'Y' : 'N';
				}
			}
			if (this === sale)
			{
				if (sale.checked !== productEditor.isDiscountEnabled())
				{
					productEditor.setSetting('enableDiscount', sale.checked);
					if (!ajaxData)
						ajaxData = {};
					ajaxData['SHOW_DISCOUNT'] = sale.checked ? 'Y' : 'N';
				}
			}
			var ownerType = productEditor.getSetting('ownerType', '');
			var ownerID = parseInt(productEditor.getSetting('ownerID', 0));
			if (ajaxData && ownerType.length > 0 && ownerID > 0)
			{
				BX.ajax({
					'url': productEditor._serviceUrl,
					'method': 'POST',
					'dataType': 'json',
					'data':
					{
						'MODE': 'SET_OPTION',
						'OWNER_TYPE': ownerType,
						'OWNER_ID': ownerID,
						'DATA': ajaxData,
						'SITE_ID': productEditor.getSetting('siteId', '')
					}
				});
			}
		}

		for(var i = 0; i < nCells; i++)
		{
			tableRow.cells[i].style.width = Math.round(tableRow.cells[i].offsetWidth) +'px';
			setTimeout(function(){BX.addClass(wrapper, 'crm-items-list-anim');}, 50)
		}

		function setWidth(cellsWidth, tax, isTaxAllowed)
		{
			var leftSpaceWidth = leftSpace.offsetWidth;
			leftSpace.style.width = leftSpaceWidth + "px";
			for(var i = 0; i<nCells; i++)
			{
				tableRow.cells[i].style.width = Math.round(tableRow.cells[i].offsetWidth) +'px';

				widthSum += Math.round(cellsWidth[i] * onePercent);

				if(i == 3 && !tax)
				{
					correction = widthSum - Math.round(leftSpaceWidth);
					width = Math.round(cellsWidth[i] * onePercent) - correction;
					if (width < 0)
						width = 0;
					tableRow.cells[i].style.width = width  +'px';
					widthSum -= correction;

				}
				else if (i == 5 && !tax && !isTaxAllowed)
				{
					correction = widthSum - (Math.round(leftSpaceWidth + tabWidth + spacerWidth));
					width = Math.round(cellsWidth[i] * onePercent) - correction;
					if (width < 0)
						width = 0;
					tableRow.cells[i].style.width = width + 'px';
					widthSum -= correction;
				}
				else if(i == 6 && tax && isTaxAllowed)
				{
					if(widthSum != Math.round(leftSpaceWidth + tabWidth + spacerWidth))
					{
						correction = widthSum - (Math.round(leftSpaceWidth + tabWidth + spacerWidth));
						width = Math.round(cellsWidth[i] * onePercent) - correction;
						if (width < 0)
							width = 0;
						tableRow.cells[i].style.width = width + 'px';
						widthSum -= correction;

					}
					else {
						tableRow.cells[i].style.width =  Math.round(cellsWidth[i] * onePercent) + 'px';
					}
				}
				else if (i == 6 && !tax && isTaxAllowed)
				{
					tableRow.cells[i].style.width =  Math.round(spacerWidth) +'px';
					widthSum -= Math.round(cellsWidth[i] * onePercent);
					widthSum += Math.round(spacerWidth);
				}
				else if(i == 7 && !isTaxAllowed)
				{
					width = Math.round(cellsWidth[i] * onePercent) - (widthSum - tableWidth);
					if (width < 0)
						width = 0;
					tableRow.cells[i].style.width =  width +'px';
				}
				else if(i == 11 && isTaxAllowed)
				{
					width = Math.round(cellsWidth[i] * onePercent) - (widthSum - tableWidth);
					if (width < 0)
						width = 0;
					tableRow.cells[i].style.width =  width +'px';
				}
				else
				{
					tableRow.cells[i].style.width =  Math.round(cellsWidth[i] * onePercent)  +'px';
				}
			}
			leftSpace.style.width = "";
		}

		function reset(cellsWidth)
		{
			for(var i = 0; i<nCells; i++){
				tableRow.cells[i].style.width =  Math.round(cellsWidth[i] * onePercent)  +'px'
			}
		}

		if(tax && this == tax)
		{
			if(wrapper.getAttribute('data-tabs') == '')
			{
				BX.addClass(wrapper, 'crm-items-list-tax');
				wrapper.setAttribute('data-tabs', 'tax');
				setTimeout(function(){setWidth(setWidthTax, 1, isTaxAllowed)}, 70)
			}
			else if(wrapper.getAttribute('data-tabs') == 'sale')
			{
				BX.addClass(wrapper, 'crm-items-list-tax');
				wrapper.setAttribute('data-tabs', 'all');
				setTimeout(function(){setWidth(setWidthAll, 0, isTaxAllowed)},70)
			}
			else if(wrapper.getAttribute('data-tabs') == 'tax')
			{
				BX.removeClass(wrapper, 'crm-items-list-tax');
				wrapper.setAttribute('data-tabs', '');
				setTimeout(function(){reset(resetWidth)},70)
			}

			else if(wrapper.getAttribute('data-tabs') == 'all')
			{
				BX.removeClass(wrapper, 'crm-items-list-tax');
				wrapper.setAttribute('data-tabs', 'sale');
				setTimeout(function(){setWidth(setWidthSale, 0, isTaxAllowed)},70)
			}
		}
		else if (this == sale)
		{
			if(wrapper.getAttribute('data-tabs') == '')
			{
				BX.addClass(wrapper, 'crm-items-list-sale');
				wrapper.setAttribute('data-tabs', 'sale');
				setTimeout(function(){setWidth(setWidthSale, 0, isTaxAllowed)}, 70)
			}
			else if(wrapper.getAttribute('data-tabs') == 'tax')
			{
				BX.addClass(wrapper, 'crm-items-list-sale');
				wrapper.setAttribute('data-tabs', 'all');
				setTimeout(function(){setWidth(setWidthAll, 0, isTaxAllowed)},70)
			}
			else if(wrapper.getAttribute('data-tabs') == 'sale')
			{
				BX.removeClass(wrapper, 'crm-items-list-sale');
				wrapper.setAttribute('data-tabs', '');
				setTimeout(function(){reset(resetWidth)},70)
			}

			else if(wrapper.getAttribute('data-tabs') == 'all')
			{
				BX.removeClass(wrapper, 'crm-items-list-sale');
				wrapper.setAttribute('data-tabs', 'tax');
				setTimeout(function(){setWidth(setWidthTax, 1, isTaxAllowed)},70)
			}
		}
		else
		{
			if(wrapper.getAttribute('data-tabs') == '')
				setTimeout(function(){reset(resetWidth)},70);
			else if(wrapper.getAttribute('data-tabs') == 'tax')
			setTimeout(function(){setWidth(setWidthTax, 1, isTaxAllowed)},70);
			else if(wrapper.getAttribute('data-tabs') == 'sale')
				setTimeout(function(){setWidth(setWidthSale, 0, isTaxAllowed)}, 70);
			else if(wrapper.getAttribute('data-tabs') == 'all')
				setTimeout(function(){setWidth(setWidthAll, 0, isTaxAllowed)},70);
		}

		productEditor.switchTotalElements();
	};
	if(typeof(BX.CrmProductEditorMessages) === "undefined")
	{
		BX.CrmProductEditorMessages =
		{
			editButtonTitle: "Edit",
			deleteButtonTitle: "Delete",
			deletionConfirm: "Are you sure you want to delete this product?"
		};
	}
}

if (typeof(BX.CrmProduct) === "undefined")
{
	BX.CrmProduct = function()
	{
		this._viewMode = true;
		this._settings = {};
		this._container = this._editor = null;
		this._fields = {};
		this._fieldValue = {};
		this._elements = {};
		this._isEventsBinds = false;
		this._discountTypeView = 0;
		this._fixProductName = false;
		this._focusedField = null;
		this._productSearch = null;
		this._dragButton = null;
		this._sumValueAfterBlur = null;
		this._hasLayout = false;
		this._keyDownHandler = BX.delegate(this._handleKeyDown, this);
	};
	BX.CrmProduct.prototype =
	{
		initialize: function(settings, row, editor)
		{
			this._editor = editor;

			this.setSettings(settings);

			if(typeof(this._settings['FIXED_PRODUCT_NAME']) === "string" && this._settings['FIXED_PRODUCT_NAME'].length > 0)
				this._fixProductName = true;

			this._container = row;
			this._viewMode = editor._viewMode;
			this._discountTypeView = BX.CrmDiscountType.percentage;

			var deleteBtn = BX.findChild(
				row,
				{'tag': 'span', 'class': 'crm-item-del'},
				true,
				false
			);
			if(deleteBtn)
			{
				BX.bind(deleteBtn,
					'click',
					BX.delegate(this._handleDeleteClick, this)
				);
				deleteBtn.setAttribute('title', BX.CrmProductEditorMessages['deleteButtonTitle']);
			}

			this._dragButton = BX.findChild(
				row,
				{'tag': 'span', 'class': 'crm-item-move-btn'},
				true,
				false
			);

			var rowId = row.id;
			var el, elID, elClass, skip, pel, editBlocks = [];
			var viewBlocks = [];
			if(rowId)
			{
				var fields = this.getSetting('fields', []);
				for(var i = 0; i < fields.length; i++)
				{
					if(fields[i])
					{
						elID = rowId + "_" + fields[i];
						elClass = "crm-item-cell-text";
						skip = false;
						if(fields[i] === "PRODUCT_NAME" && this._fixProductName)
						{
							elID += "_v";
							elClass = "crm-item-cell-view";
							skip = true;
						}
						el = BX(elID);
						if(el)
						{
							pel = el.parentNode;
							if(pel)
							{
								if(BX.hasClass(pel, elClass))
								{
									editBlocks.push(pel);
								}
								else
								{
									pel = pel.parentNode;
									if(pel && BX.hasClass(pel, elClass))
										editBlocks.push(pel);
								}
								if(pel && this._viewMode && !skip)
									pel.style.display = "none";
							}
						}
						elClass = "crm-item-cell-view";
						skip = fields[i] === "PRODUCT_NAME" && this._fixProductName;
						el = BX(rowId + "_" + fields[i] + "_v");
						if(el)
						{
							pel = el.parentNode;
							if(pel)
							{
								if(BX.hasClass(pel, elClass))
								{
									viewBlocks.push(pel);
								}
								else
								{
									pel = pel.parentNode;
									if(pel && BX.hasClass(pel, elClass))
										viewBlocks.push(pel);
								}
								if(pel && !this._viewMode && !skip)
									pel.style.display = "none";
							}
						}
					}
				}
			}
			this._viewBlocks = viewBlocks;
			this._editBlocks = editBlocks;

			var delItem = BX.findChild(this._container, {"attr": {"class": "crm-item-del"}}, true);
			if(delItem)
				delItem.style.display = this._viewMode ? "none" : "";

			// search control
			if(!this._productSearch)
			{
				var inp = BX(this._container.id + "_PRODUCT_NAME");
				var cont = inp.parentNode;
				cont.id = inp.id + "_c";
				this._productSearch = BX.CrmProductSearch.create({
					"AJAX_PAGE": this._editor.getSetting("productSearchUrl", ""),
					"CONTAINER_ID": cont.id,
					"INPUT_ID": this._container.id + "_PRODUCT_NAME",
					"MIN_QUERY_LEN": 3
				}, this);
			}
		},
		initializeDragDropAbilities: function()
		{
			if(this._dragItem)
			{
				return;
			}

			if(!this._dragButton)
			{
				throw "CrmProduct: Could not find drag button.";
			}

			this._dragItem = BX.CrmProdoctRowListDragItem.create(
				this.getId(),
				{
					product: this,
					node: this._dragButton,
					container: this._editor.getTable(),
					showInDragMode: false,
					ghostOffset: {x: -12, y: -12}
				}
			);
		},
		releaseDragDropAbilities: function()
		{
			if(this._dragItem)
			{
				this._dragItem.release();
				this._dragItem = null;
			}
		},
		getRowNumber: function()
		{
			return this._container.rowIndex;
		},
		setNumber: function(number)
		{
			var elements = [];
			elements[0] = BX(this._container.id + "_NUM");
			elements[1] = BX(this._container.id + "_NUM_v");
			for(var i = 0; i < elements.length; i++)
			{
				if(elements[i])
					BX.setTextContent(elements[i], parseInt(number).toString() + ".");
			}

			this._container.className = (number % 2) === 0 ? "crm-items-table-even-row" : "crm-items-table-odd-row";
		},
		getMeasureNameByCode: function(measureCode)
		{
			var measures = this._editor.getSetting("measures", []),
				measureName = "-",
				measure, i;

			if(measures)
			{
				for(i = 0; i < measures.length; i++)
				{

					if(measures[i] && typeof(measures[i]) === "object")
					{
						measure = measures[i];
						if(measure.hasOwnProperty("CODE") && parseInt(measure["CODE"]) === parseInt(measureCode))
						{
							if(measure.hasOwnProperty("SYMBOL") && measure["SYMBOL"].length > 0)
							{
								measureName = measure["SYMBOL"];
								break;
							}
						}
					}
				}
			}

			return measureName;
		},
		getMeasureIdByCode: function(measureCode)
		{
			var measures = this._editor.getSetting("measures", []),
				measureId = 0,
				measure, i;

			if(measures)
			{
				for(i = 0; i < measures.length; i++)
				{

					if(measures[i] && typeof(measures[i]) === "object")
					{
						measure = measures[i];
						if(measure.hasOwnProperty("CODE") && parseInt(measure["CODE"]) === parseInt(measureCode))
						{
							if(measure.hasOwnProperty("ID") && measure["ID"].length > 0)
							{
								measureId = measure["ID"];
								break;
							}
						}
					}
				}
			}

			return measureId;
		},
		setFieldView: function(fieldName, value)
		{
			var controlName, valueChanged = false;

			var isTaxAllowed = this._editor.isTaxAllowed();
			var isTaxIncluded = this.isTaxIncluded();

			if(fieldName === "PRICE_NETTO" || fieldName === "PRICE_BRUTTO")
				valueChanged = true;

			value = (fieldName === "TAX_INCLUDED") ? !!value : value.toString();

			switch(fieldName)
			{
				case "PRICE_NETTO":
				case "PRICE_BRUTTO":
					controlName = "PRICE";
					break;

				case "MEASURE_CODE":
					controlName = "MEASURE";
					break;

				case "DISCOUNT_RATE":
				case "DISCOUNT_SUM":
				case "DISCOUNT_TYPE_ID":
					controlName = "DISCOUNT";
					break;

				default:
					controlName = fieldName;
			}

			var precision = 0, discountTypeView = this._discountTypeView;
			switch(controlName)
			{
				case 'PRICE':
				case 'DISCOUNT_SUM':
				case 'DISCOUNT_SUBTOTAL':
				case 'SUM':
					precision = 2;
					break;

				case 'DISCOUNT':
					if(discountTypeView === BX.CrmDiscountType.monetary)
						precision = 2;
					break;
			}
			if(precision > 0)
				value = this._parseFloat(value, precision, 0.0).toFixed(precision);

			var row = this._container,
				cView = BX(row.id + "_" + controlName + "_v"),
				i;

			var advFieldName, cViewAdv;

			switch(fieldName)
			{
				case "PRODUCT_NAME":
				case "QUANTITY":
				case "SUM":
					if(cView)
						BX.setTextContent(cView, value);
					break;

				case "PRICE_NETTO":
					if(isTaxAllowed && !isTaxIncluded)
					{
						if(cView)
							BX.setTextContent(cView, value);
					}
					break;

				case "PRICE_BRUTTO":
					if(!isTaxAllowed || isTaxIncluded)
					{
						if(cView)
							BX.setTextContent(cView, value);
					}
					break;

				case "MEASURE_CODE":
					var measureName = this.getMeasureNameByCode(value);
					if(cView)
						BX.setTextContent(cView, measureName);
					break;

				case "DISCOUNT_TYPE_ID":
					break;

				case "DISCOUNT_RATE":
					if(discountTypeView === BX.CrmDiscountType.percentage)
					{
						if(cView)
							BX.setTextContent(cView, value);
					}
					break;

				case "DISCOUNT_SUM":
					if(discountTypeView === BX.CrmDiscountType.monetary)
					{
						if(cView)
							BX.setTextContent(cView, value);
					}
					break;

				case "DISCOUNT_SUBTOTAL":
					if(cView)
						BX.setTextContent(cView, value);
					break;

				case "TAX_RATE":
					if(cView)
						BX.setTextContent(cView, value + "%");
					break;

				case "TAX_INCLUDED":
					if(cView)
						BX.setTextContent(cView, value ? BX.CrmProductEditorMessages["yes"] : BX.CrmProductEditorMessages["no"]);
					break;
				case "TAX_SUM":
					if(cView)
						BX.setTextContent(cView, this._parseFloat(value, 2, 0.0).toFixed(2));
					break;
			}
		},
		setFieldValue: function(fieldName, value, riseEvent)
		{
			var controlName, valueChanged = false;

			var isTaxAllowed = this._editor.isTaxAllowed();
			var isTaxIncluded = this.isTaxIncluded();

			if(fieldName === "PRICE_NETTO" || fieldName === "PRICE_BRUTTO")
				valueChanged = true;

			value = (fieldName === "TAX_INCLUDED") ? !!value : value.toString();
			if(this._fieldValue.hasOwnProperty(fieldName) && this._fieldValue[fieldName] === value && !valueChanged)
				return;

			this._fieldValue[fieldName] = value;

			switch(fieldName)
			{
				case "PRICE_NETTO":
				case "PRICE_BRUTTO":
					controlName = "PRICE";
					break;

				case "MEASURE_CODE":
					controlName = "MEASURE";
					break;

				case "DISCOUNT_RATE":
				case "DISCOUNT_SUM":
					controlName = "DISCOUNT";
					break;

				default:
					controlName = fieldName;
			}

			var row = this._container,
				cEdit = BX(row.id + "_" + controlName),
				cView = BX(row.id + "_" + controlName + "_v"),
				i;

			var discountType, advFieldName, cEditAdv, cViewAdv,
				discountTypeView = this._discountTypeView;
			switch(fieldName)
			{
				case "PRODUCT_NAME":
				case "QUANTITY":
				case "DISCOUNT_SUBTOTAL":
				case "SUM":
					if(cEdit)
						cEdit.value = value;
					if(cView)
						BX.setTextContent(cView, value);
					break;

				case "PRICE_NETTO":
					if(isTaxAllowed && !isTaxIncluded)
					{
						if(cEdit)
							cEdit.value = value;
						if(cView)
							BX.setTextContent(cView, value);
					}
					break;

				case "PRICE_BRUTTO":
					if(!isTaxAllowed || isTaxIncluded)
					{
						if(cEdit)
							cEdit.value = value;
						if(cView)
							BX.setTextContent(cView, value);
					}
					break;

				case "MEASURE_CODE":
					if(cEdit)
						BX.setSelectValue(cEdit, value);
					var measureName = this.getMeasureNameByCode(value);
					this._fieldValue["MEASURE_NAME"] = measureName;
					if(cView)
						BX.setTextContent(cView, measureName);
					break;

				case "DISCOUNT_TYPE_ID":
					discountType = parseInt(value);
					if(discountType !== BX.CrmDiscountType.percentage && discountType !== BX.CrmDiscountType.monetary)
						discountType = BX.CrmDiscountType.percentage;

					// switch discount view
					if(parseInt(discountTypeView) !== discountType)
					{
						var discountTypeText = this._editor.getSetting("discountTypeText", []);
						discountTypeView = discountType;
						this._discountTypeView = discountTypeView;
						var discountText = (discountTypeText[discountTypeView] ? discountTypeText[discountTypeView] : '?');
						var discountFieldName = (discountTypeView === BX.CrmDiscountType.percentage ? "DISCOUNT_RATE" : "DISCOUNT_SUM");
						cEdit = BX(row.id + "_DISCOUNT");
						var discountTypeControls = [];
						var formattedValue = this._parseFloat(this._fieldValue[discountFieldName], 2, 0.0).toFixed(2);
						if(cEdit)
						{
							discountTypeControls[0] = BX.findChild(cEdit.parentNode, {"attr": {"class": "crm-item-sale-text"}}, true);
							cEdit.value = (discountTypeView === BX.CrmDiscountType.percentage) ? this._fieldValue[discountFieldName] : formattedValue;
						}
						cView = BX(row.id + "_DISCOUNT_v");
						if(cView)
						{
							discountTypeControls[1] = BX.findChild(cView.parentNode, {"attr": {"class": "crm-item-sale-text"}}, true);
							BX.setTextContent(cView, (discountTypeView === BX.CrmDiscountType.percentage) ? this._fieldValue[discountFieldName] : formattedValue);
						}
						{
							for(i = 0; i < discountTypeControls.length; i++)
							{
								if(discountTypeControls[i])
								{
									//BX.setTextContent(discountTypeControls[i], discountText);
									discountTypeControls[i].innerHTML = discountText;
								}
							}
						}
					}
					break;

				case "DISCOUNT_RATE":
					if(discountTypeView === BX.CrmDiscountType.percentage)
					{
						if(cEdit)
							cEdit.value = value;
						if(cView)
							BX.setTextContent(cView, value);
					}
					break;

				case "DISCOUNT_SUM":
					if(discountTypeView === BX.CrmDiscountType.monetary)
					{
						if(cEdit)
							cEdit.value = value;
						if(cView)
							BX.setTextContent(cView, value);
					}
					break;

				case "TAX_RATE":
					if(cEdit)
					{
						var exists = false;
						for(i = 0; i < cEdit.options.length; i++)
						{
							if(cEdit.options[i].value === value)
							{
								cEdit.selectedIndex = i;
								exists = true;
								break;
							}
						}
						if(!exists)
						{
							var opt = document.createElement("option");
							opt.value = value;
							opt.innerHTML = BX.util.htmlspecialchars(value + "%");
							cEdit.appendChild(opt);
							cEdit.selectedIndex = i;
						}
					}
					if(cView)
						BX.setTextContent(cView, value + "%");
					break;

				case "TAX_INCLUDED":
					if(cEdit)
						cEdit.checked = value;
					if(cView)
						BX.setTextContent(cView, value ? BX.CrmProductEditorMessages["yes"] : BX.CrmProductEditorMessages["no"]);
					break;
				case "TAX_SUM":
					if(cEdit)
						BX.setTextContent(cEdit, this._parseFloat(value, 2, 0.0).toFixed(2));
					if(cView)
						BX.setTextContent(cView, this._parseFloat(value, 2, 0.0).toFixed(2));
					break;
			}

			riseEvent = typeof(riseEvent) === "undefined" ? false : !!riseEvent;
			if(riseEvent)
				this.processFieldValueChange(fieldName);
		},
		layout: function()
		{
			var row = this._container;
			var readOnly = this._editor.getSetting('readOnly', false);
			var productId = this.getSetting('PRODUCT_ID', 0);
			var productName = this.getSetting("PRODUCT_NAME", "");
			var price = this.getSetting('PRICE', 0.0);
			var qty = this.getSetting('QUANTITY', 0.0);
			var isTaxAllowed = this._editor.isTaxAllowed();
			var isTaxEnabled = this._editor.isTaxEnabled();
			var fieldName, fieldValue, cEdit, cView, cSel, i;

			// PRODUCT NAME
			this.setFieldValue("PRODUCT_NAME", productName);

			// PRICE NETTO
			var priceNetto = this.getSetting('PRICE_NETTO', 0.0);
			this.setFieldValue('PRICE_NETTO', priceNetto.toFixed(2));

			// PRICE BRUTTO
			var priceBrutto = this.getSetting('PRICE_BRUTTO', 0.0);
			this.setFieldValue('PRICE_BRUTTO', priceBrutto.toFixed(2));


			// QUANTITY
			var showQuantity = this.getSetting("QUANTITY", 0.0);
			this.setFieldValue("QUANTITY", showQuantity);

			// MEASURE
			var defMeasureCode = "";
			var defMeasureInfo = this._editor.getSetting("defaultMeasure", null);
			if(defMeasureInfo)
			{
				if(defMeasureInfo.hasOwnProperty("CODE"))
					defMeasureCode = defMeasureInfo["CODE"];
			}
			this.setFieldValue("MEASURE_CODE", this.getSetting("MEASURE_CODE", defMeasureCode));

			// DISCOUNT TYPE
			var discountType = this.getDiscountTypeId();
			this.setFieldValue("DISCOUNT_TYPE_ID", discountType);

			// DISCOUNT RATE
			var showDiscount = this.getSetting("DISCOUNT_RATE", 0.0);
			this.setFieldValue("DISCOUNT_RATE", showDiscount);

			// DISCOUNT SUM
			var showDiscountSum = this.getSetting("DISCOUNT_SUM", 0.0);
			this.setFieldValue("DISCOUNT_SUM", showDiscountSum.toFixed(2));

			// DISCOUNT SUBTOTAL
			var showDiscountSubtotal = showQuantity * showDiscountSum;
			this.setFieldValue("DISCOUNT_SUBTOTAL", showDiscountSubtotal.toFixed(2));

			// TAX RATE
			var defTaxRateValue = "0";
			var defTaxRateName = "0%";
			if(isTaxAllowed)
			{

				var defTaxRateInfo = this._editor.getSetting("defaultTax", null);
				if(defTaxRateInfo)
				{
					if(defTaxRateInfo.hasOwnProperty("VALUE"))
					{
						defTaxRateValue = defTaxRateInfo["VALUE"];
						defTaxRateName = defTaxRateInfo["VALUE"] + "%";
					}
				}
			}
			this.setFieldValue("TAX_RATE", this.getSetting("TAX_RATE", defTaxRateValue));

			// TAX INCLUDED
			this.setFieldValue("TAX_INCLUDED", this.isTaxIncluded());

			// TAX SUM
			this.setFieldValue("TAX_SUM", this._round(this.getSetting("TAX_SUM", 0.0), 2));
			// SUM
			this.setFieldValue("SUM", this._calculateSumTotal());

			this._handleProductNameChange(productName);

			if (this._container && BX.type.isDomNode(this._container))
			{
				BX.bind(this._container, "keydown", this._keyDownHandler);
			}

			this._bindEventsHandlers();
			this.initializeDragDropAbilities();

			this._hasLayout = true;
		},
		clean: function()
		{
			if(this._container)
			{
				this._container.parentNode.removeChild(this._container);
				this.releaseDragDropAbilities();

				if (BX.type.isDomNode(this._container))
				{
					BX.unbind(this._container, "keydown", this._keyDownHandler);
				}
			}

			this._hasLayout = false;
		},
		hasLayout: function()
		{
			return this._hasLayout;
		},
		isReadOnly: function()
		{
			return this.getSetting('readOnly', false);
		},
		getContainer: function()
		{
			return this._container;
		},
		toggleMode: function()
		{
			var i, delItem;
			var hideBlocks = this._viewMode ? this._viewBlocks : this._editBlocks;
			var showBlocks = this._viewMode ? this._editBlocks : this._viewBlocks;

			for(i = 0; i < hideBlocks.length; i++)
				hideBlocks[i].style.display = "none";
			for(i = 0; i < showBlocks.length; i++)
				showBlocks[i].style.display = "";

			delItem = BX.findChild(this._container, {"attr": {"class": "crm-item-del"}}, true);
			if(delItem)
				delItem.style.display = this._viewMode ? "" : "none";

			if(!this._viewMode)
			{
				this.saveSettings();
			}

			this._viewMode = !this._viewMode;
		},
		getSetting: function(name, dafaultval)
		{
			return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : dafaultval;
		},
		setSetting: function(name, value)
		{
			this._settings[name] = value;
		},
		saveSettings: function()
		{
			if(!this._hasLayout || this._viewMode)
			{
				return;
			}

			this._settings['PRODUCT_NAME'] = BX.util.trim(BX.prop.getString(this._fieldValue, 'PRODUCT_NAME'));

			this._settings['QUANTITY'] = this._parseFloat(this._fieldValue['QUANTITY'], 4, 0.0);

			this._settings['DISCOUNT_TYPE_ID'] = this._parseInt(this._fieldValue['DISCOUNT_TYPE_ID'], this.getDiscountTypeId());

			this._settings['DISCOUNT_RATE'] = this._parseFloat(this._fieldValue['DISCOUNT_RATE'], 2, 0.0);

			this._settings['DISCOUNT_SUM'] = this._parseFloat(this._fieldValue['DISCOUNT_SUM'], 2, 0.0);

			this._settings['TAX_INCLUDED'] = this._fieldValue['TAX_INCLUDED'];

			this._settings['TAX_RATE'] = this._parseFloat(this._fieldValue['TAX_RATE'], 2, 0.0);

			this._settings['TAX_SUM'] = this._parseFloat(this._fieldValue['TAX_SUM'], 2, 0.0);

			this._settings['MEASURE_CODE'] = parseInt(this._fieldValue['MEASURE_CODE']);

			this._settings['MEASURE_NAME'] = BX.prop.getString(this._fieldValue, 'MEASURE_NAME');
		},
		getSettings: function()
		{
			return this._settings;
		},
		setSettings: function(settings)
		{
			this._settings = settings ? settings : {};

			this._settings['PRODUCT_ID'] = parseInt(this.getSetting('PRODUCT_ID', 0));
			this._settings['PRODUCT_NAME'] = this.getSetting('PRODUCT_NAME', '');

			this._settings['QUANTITY'] = this._round(parseFloat(this.getSetting('QUANTITY', 0.0)), 4);
			this._settings['MEASURE_CODE'] = parseInt(this.getSetting('MEASURE_CODE', 0));
			this._settings['MEASURE_NAME'] = this.getSetting('MEASURE_NAME', '');

			this._settings['DISCOUNT_TYPE_ID'] = parseInt(this.getDiscountTypeId());

			if(this._editor.isInvoiceMode())
				this._settings["DISCOUNT_TYPE_ID"] = BX.CrmDiscountType.monetary;

			this._settings['DISCOUNT_RATE'] = this._round(parseFloat(this.getSetting('DISCOUNT_RATE', 0)), 2);
			this._settings['DISCOUNT_SUM'] = this._round(parseFloat(this.getSetting('DISCOUNT_SUM', 0.0)), 2);
			this._settings['DISCOUNT_SUBTOTAL'] = this._round((this._settings['QUANTITY'] * this._settings['DISCOUNT_SUM']), 2);

			this._settings['TAX_RATE'] = this._round(parseFloat(this.getSetting('TAX_RATE', 0.0)), 2);
			this._settings['TAX_INCLUDED'] = this.isTaxIncluded();

			this._settings['PRICE'] = this._round(parseFloat(this.getSetting('PRICE', 0.0)), 2);
			this._settings['PRICE_EXCLUSIVE'] = this._round(parseFloat(this.getSetting('PRICE_EXCLUSIVE', 0.0)), 2);

			//[PRICE] - price to sale, tax is included, discount is included
			//[PRICE_EXCLUSIVE] - tax is excluded, discount is included
			//[PRICE_NETTO] - tax is excluded, discount is excluded
			//[PRICE_BRUTTO] - tax is included, discount is excluded
			//[TAX_INCLUDED] - is only display mode for price

			if(typeof(this._settings['PRICE_NETTO']) !== "undefined")
			{
				this._settings['PRICE_NETTO'] = this._round(parseFloat(this.getSetting('PRICE_NETTO', 0.0)), 2);
			}
			else
			{
				var exclusivePrice = this._settings['PRICE_EXCLUSIVE'];
				if(this._settings['DISCOUNT_TYPE_ID'] === BX.CrmDiscountType.monetary)
				{
					this._settings['PRICE_NETTO'] = exclusivePrice + this._settings['DISCOUNT_SUM'];
				}
				else
				{
					var discountRate = this._settings['DISCOUNT_RATE'];
					var discountSum = discountRate < 100
						? this._round(this._calculateDiscountByDiscountPrice(exclusivePrice, discountRate), 2)
						: this._settings['DISCOUNT_SUM'];

					this._settings['PRICE_NETTO'] = exclusivePrice + discountSum;
				}
			}

			if(typeof(this._settings['PRICE_BRUTTO']) !== "undefined")
			{
				this._settings['PRICE_BRUTTO'] = this._round(parseFloat(this.getSetting('PRICE_BRUTTO', 0.0)), 2);
			}
			else
			{
				if(this._settings['DISCOUNT_SUM'] == 0.0)
				{
					this._settings['PRICE_BRUTTO'] = this._settings['PRICE'];
				}
				else
				{
					this._settings['PRICE_BRUTTO'] = this._round(BX.CrmProduct.calculateInclusivePrice(this._settings['PRICE_NETTO'], this._settings['TAX_RATE']), 2);
				}
			}

			this._settings['TAX_SUM'] = this.getTaxSum();
			this._settings['CUSTOMIZED'] = !!this.getSetting('CUSTOMIZED', false);
			this._settings['SORT'] = parseInt(this.getSetting('SORT', 0));
		},
		getId: function()
		{
			return parseInt(this.getSetting('ID', 0));
		},
		setId: function(id)
		{
			this._settings['ID'] = parseInt(id);
		},
		setProductId: function(value)
		{
			return this.setSetting('PRODUCT_ID', parseInt(value));
		},
		getProductId: function()
		{
			return this.getSetting('PRODUCT_ID', 0);
		},
		getProductName: function()
		{
			return this.getSetting('PRODUCT_NAME', '');
		},
		getQuantity: function()
		{
			return this.getSetting('QUANTITY', 0.0);
		},
		getMeasureCode: function()
		{
			return this.getSetting('MEASURE_CODE', 0);
		},
		getMeasureName: function()
		{
			return this.getSetting('MEASURE_NAME', '');
		},
		getPrice: function()
		{
			return this.getSetting('PRICE', 0.0);
		},
		getExclusivePrice: function()
		{
			return this.getSetting('PRICE_EXCLUSIVE', 0.0);
		},
		getPriceNetto: function()
		{
			return this.getSetting('PRICE_NETTO', 0.0);
		},
		getPriceBrutto: function()
		{
			return this.getSetting('PRICE_BRUTTO', 0.0);
		},
		getDiscountTypeId: function()
		{
			var discountType = parseInt(this.getSetting('DISCOUNT_TYPE_ID', BX.CrmDiscountType.percentage));
			if(discountType !== BX.CrmDiscountType.percentage
				&& discountType !== BX.CrmDiscountType.monetary)
			{
				discountType = BX.CrmDiscountType.percentage;
			}

			return discountType;
		},
		getDiscountRate: function()
		{
			return this.getSetting('DISCOUNT_RATE', 0.0);
		},
		getDiscountSum: function()
		{
			return this.getSetting('DISCOUNT_SUM', 0.0);
		},
		getDiscountSubtotal: function()
		{
			return this.getSetting('DISCOUNT_SUBTOTAL', 0.0);
		},
		getTaxRate: function()
		{
			return this.getSetting('TAX_RATE', 0.0);
		},
		isTaxIncluded: function()
		{
			return this.getSetting('TAX_INCLUDED', false);
		},
		isCustomized: function()
		{
			return this.getSetting('CUSTOMIZED', false);
		},
		getSort: function()
		{
			return this.getSetting('SORT', 0);
		},
		setSort: function(number)
		{
			return this.setSetting('SORT', number);
		},
		isViewMode: function()
		{
			return this._viewMode;
		},
		toJson: function()
		{
			var json = "";
			var jsonData = {};
			if(this._hasLayout && !this._viewMode)
			{
				this.saveSettings();
			}

			jsonData['ID'] = this.getId();
			jsonData['PRODUCT_NAME'] = this.getSetting(this._fixProductName ? "FIXED_PRODUCT_NAME" : "PRODUCT_NAME", "");
			jsonData['PRODUCT_ID'] = this.getSetting("PRODUCT_ID", 0);
			jsonData['QUANTITY'] = this.getSetting("QUANTITY", 0.0).toFixed(4);
			jsonData['MEASURE_CODE'] = this.getSetting("MEASURE_CODE", 0);
			jsonData['MEASURE_NAME'] = this.getSetting("MEASURE_NAME", "");
			jsonData['PRICE'] = this.getSetting("PRICE", 0.0).toFixed(2);
			jsonData['PRICE_EXCLUSIVE'] = this.getSetting("PRICE_EXCLUSIVE", 0.0).toFixed(2);
			jsonData['PRICE_NETTO'] = this.getSetting("PRICE_NETTO", 0.0).toFixed(2);
			jsonData['PRICE_BRUTTO'] = this.getSetting("PRICE_BRUTTO", 0.0).toFixed(2);
			jsonData['DISCOUNT_TYPE_ID'] = this.getDiscountTypeId();

			var discountRate = this.getSetting("DISCOUNT_RATE", null);
			if(discountRate !== null)
			{
				jsonData['DISCOUNT_RATE'] = discountRate.toFixed(2);
			}
			var discountSum = this.getSetting("DISCOUNT_SUM", null);
			if(discountSum !== null)
			{
				jsonData['DISCOUNT_SUM'] = discountSum.toFixed(2);
			}

			// save original tax rate and "included" flag if taxes is not allowed
			jsonData['TAX_RATE'] = this.getSetting('TAX_RATE', 0.0).toFixed(2);
			jsonData['TAX_INCLUDED'] = this.getSetting('TAX_INCLUDED', false) ? "Y" : "N";
			jsonData['CUSTOMIZED'] = "Y";
			jsonData['SORT'] = parseInt(this.getSetting('SORT', 0));

			return JSON.stringify(jsonData);
		},
		processFieldValueChange: function(fieldId)
		{
			var discount, discountSum, discountSubTotal, exclusivePrice, isTaxIncluded, priceNetto, priceBrutto;
			var discountType = this.getDiscountTypeId();

			if(fieldId !== 'DISCOUNT_SUM' && fieldId !== 'DISCOUNT_RATE' && fieldId !== 'DISCOUNT_SUBTOTAL'
				&& fieldId !== 'DISCOUNT_TYPE_ID' && fieldId !== 'TAX_RATE' && fieldId !== 'TAX_INCLUDED'
				&& fieldId !== 'PRICE_BRUTTO' && fieldId !== 'PRICE_NETTO' && fieldId !== 'QUANTITY'
				&& fieldId !== 'MEASURE_CODE' && fieldId !== 'SUM')
			{
				return;
			}

			if(fieldId === 'DISCOUNT_TYPE_ID')
			{
				this._settings['DISCOUNT_TYPE_ID'] = parseInt(this._fieldValue['DISCOUNT_TYPE_ID']);

				if(parseInt(this._fieldValue[fieldId]) === BX.CrmDiscountType.monetary)
				{
					exclusivePrice = this._settings['PRICE_NETTO'] - this._settings['DISCOUNT_SUM'];
					this._settings['PRICE_EXCLUSIVE'] = exclusivePrice;
					this._settings['PRICE'] = this._round(BX.CrmProduct.calculateInclusivePrice(exclusivePrice, this._settings['TAX_RATE']), 2);
					this._settings['DISCOUNT_RATE'] = this._round(this._calculateDiscountRate(this._settings['PRICE_NETTO'], (this._settings['PRICE_NETTO'] - this._settings['DISCOUNT_SUM'])), 2);
					this.setFieldValue('DISCOUNT_RATE', this._settings['DISCOUNT_RATE']);

					this._settings['DISCOUNT_SUM'] = this._round(this._calculateDiscount(this._settings['PRICE_NETTO'], this._settings['DISCOUNT_RATE']), 2);
					this.setFieldValue('DISCOUNT_SUM', this._settings['DISCOUNT_SUM'].toFixed(2));

					this._settings['DISCOUNT_SUBTOTAL'] = this._round((this._settings['QUANTITY'] * this._settings['DISCOUNT_SUM']), 2);
					this.setFieldValue('DISCOUNT_SUBTOTAL', this._settings['DISCOUNT_SUBTOTAL'].toFixed(2));

					this.setFieldValue('TAX_SUM',  this.getTaxSum());
					this._settings['CUSTOMIZED'] = true;
				}
				else
				{
					exclusivePrice = this._round(this._calculatePrice(this._settings['PRICE_NETTO'], this._settings['DISCOUNT_RATE'], BX.CrmDiscountType.percentage), 2);
					this._settings['PRICE_EXCLUSIVE'] = exclusivePrice;
					this._settings['PRICE'] = this._round(BX.CrmProduct.calculateInclusivePrice(exclusivePrice, this._settings['TAX_RATE']), 2);
					this._settings['DISCOUNT_SUM'] = this._round(this._calculateDiscount(this._settings['PRICE_NETTO'], this._settings['DISCOUNT_RATE']), 2);
					this.setFieldValue('DISCOUNT_SUM', this._settings['DISCOUNT_SUM'].toFixed(2));

					// discount subtotal
					discountSubTotal = this._round((this._settings['QUANTITY'] * this._settings['DISCOUNT_SUM']), 2);
					this.setFieldValue('DISCOUNT_SUBTOTAL', discountSubTotal.toFixed(2));

					this.setFieldValue('TAX_SUM',  this.getTaxSum());
					this._settings['CUSTOMIZED'] = true;
				}
			}

			if(fieldId === 'QUANTITY')
			{
				this._settings['QUANTITY'] = this._parseFloat(this._fieldValue[fieldId], 4, 0.0);

				// discount subtotal
				discountSubTotal = this._round((this._settings['QUANTITY'] * this._settings['DISCOUNT_SUM']), 2);
				this.setFieldValue('DISCOUNT_SUBTOTAL', discountSubTotal.toFixed(2));

				this.setFieldValue('TAX_SUM',  this.getTaxSum());
			}
			else if(fieldId === 'MEASURE_CODE')
			{
				this._settings['MEASURE_CODE'] = parseInt(this._fieldValue[fieldId]);
				this._settings['MEASURE_NAME'] = this._fieldValue['MEASURE_NAME'] = this.getMeasureNameByCode(this._settings['MEASURE_CODE']);
			}
			else if(fieldId === 'PRICE_BRUTTO' || fieldId === 'PRICE_NETTO')
			{
				if(fieldId === 'PRICE_BRUTTO')
				{
					this._settings['PRICE_BRUTTO'] = this._parseFloat(this._fieldValue[fieldId], 2, 0.0);
					this._settings['PRICE_NETTO'] = this._round(BX.CrmProduct.calculateExclusivePrice(this._settings['PRICE_BRUTTO'], this._settings['TAX_RATE']), 2);
					this.setFieldValue('PRICE_NETTO', this._settings['PRICE_NETTO']);
				}
				else
				{
					this._settings['PRICE_NETTO'] = this._parseFloat(this._fieldValue[fieldId], 2, 0.0);
					this._settings['PRICE_BRUTTO'] = this._round(BX.CrmProduct.calculateInclusivePrice(this._settings['PRICE_NETTO'], this._settings['TAX_RATE']), 2);
					this.setFieldValue('PRICE_BRUTTO', this._settings['PRICE_BRUTTO']);
				}

				if(this._settings['DISCOUNT_TYPE_ID'] === BX.CrmDiscountType.percentage)
				{
					if(this._settings['DISCOUNT_RATE'] == 0.0)
					{
						this._settings['PRICE_EXCLUSIVE'] = this._settings['PRICE_NETTO'];
						this._settings['PRICE'] = this._settings['PRICE_BRUTTO'];
						discountSum = 0.0;
					}
					else
					{
						exclusivePrice = this._round(this._calculatePrice(this._settings['PRICE_NETTO'], this._settings['DISCOUNT_RATE'], BX.CrmDiscountType.percentage), 2);

						this._settings['PRICE_EXCLUSIVE'] = exclusivePrice;
						this._settings['PRICE'] = this._round(BX.CrmProduct.calculateInclusivePrice(exclusivePrice, this._settings['TAX_RATE']), 2);
						discountSum = this._settings['PRICE_NETTO'] - exclusivePrice;
					}
					this.setFieldValue('DISCOUNT_SUM', discountSum.toFixed(2));

					discountSubTotal =
						this._parseFloat(this._fieldValue['QUANTITY'], 4, 0.0)
						* this._parseFloat(this._fieldValue['DISCOUNT_SUM'], 2, 0.0);
					this.setFieldValue('DISCOUNT_SUBTOTAL', discountSubTotal.toFixed(2));
				}
				else if(this._settings['DISCOUNT_TYPE_ID'] === BX.CrmDiscountType.monetary)
				{
					if(this._settings['DISCOUNT_SUM'] == 0.0)
					{
						this._settings['PRICE_EXCLUSIVE'] = this._settings['PRICE_NETTO'];
						this._settings['PRICE'] = this._settings['PRICE_BRUTTO'];
						this.setFieldValue('DISCOUNT_RATE', 0.0);
					}
					else
					{
						discountSum = this._settings['DISCOUNT_SUM'];
						exclusivePrice = this._settings['PRICE_NETTO'] - discountSum;

						this._settings['PRICE_EXCLUSIVE'] = exclusivePrice;
						this._settings['PRICE'] = this._round(BX.CrmProduct.calculateInclusivePrice(exclusivePrice, this._settings['TAX_RATE']), 2);
						this.setFieldValue('DISCOUNT_RATE', this._calculateDiscountRate(this._settings['PRICE_NETTO'], exclusivePrice));
					}
				}

				this.setFieldValue('TAX_SUM',  this.getTaxSum());
				this._settings['CUSTOMIZED'] = true;
			}
			else if(fieldId === 'DISCOUNT_RATE')
			{
				this._settings['DISCOUNT_TYPE_ID'] = BX.CrmDiscountType.percentage;
				this.setFieldValue('DISCOUNT_TYPE_ID', this._settings['DISCOUNT_TYPE_ID']);
				this._settings['DISCOUNT_RATE'] = this._parseFloat(this._fieldValue[fieldId], 2, 0.0);

				exclusivePrice = this._calculatePrice(this._settings['PRICE_NETTO'], this._settings['DISCOUNT_RATE'], BX.CrmDiscountType.percentage);

				this._settings['PRICE_EXCLUSIVE'] = this._round(exclusivePrice, 2);
				this._settings['PRICE'] = this._round(BX.CrmProduct.calculateInclusivePrice(exclusivePrice, this._settings['TAX_RATE']), 2);
				this.setFieldValue(
					'DISCOUNT_SUM',
					this._calculateDiscount(this._settings['PRICE_NETTO'], this._settings['DISCOUNT_RATE']).toFixed(2)
				);

				// discount subtotal
				discountSubTotal =
					this._parseFloat(this._fieldValue['QUANTITY'], 4, 0.0)
					* this._parseFloat(this._fieldValue['DISCOUNT_SUM'], 2, 0.0);
				this.setFieldValue('DISCOUNT_SUBTOTAL', discountSubTotal.toFixed(2));

				this.setFieldValue('TAX_SUM',  this.getTaxSum());
				this._settings['CUSTOMIZED'] = true;
			}
			else if(fieldId === 'DISCOUNT_SUM')
			{
				this._settings['DISCOUNT_TYPE_ID'] = BX.CrmDiscountType.monetary;
				this.setFieldValue('DISCOUNT_TYPE_ID', this._settings['DISCOUNT_TYPE_ID']);
				this._settings['DISCOUNT_SUM'] = this._parseFloat(this._fieldValue[fieldId], 2, 0.0);

				exclusivePrice = this._settings['PRICE_NETTO'] - this._settings['DISCOUNT_SUM'];

				this._settings['PRICE_EXCLUSIVE'] = exclusivePrice;
				this._settings['PRICE'] = this._round(BX.CrmProduct.calculateInclusivePrice(exclusivePrice, this._settings['TAX_RATE']), 2);
				this._settings['DISCOUNT_RATE'] = this._round(this._calculateDiscountRate(this._settings['PRICE_NETTO'], (this._settings['PRICE_NETTO'] - this._settings['DISCOUNT_SUM'])), 2);
				this.setFieldValue('DISCOUNT_RATE', this._settings['DISCOUNT_RATE']);

				// discount subtotal
				discountSubTotal =
					this._parseFloat(this._fieldValue['QUANTITY'], 4, 0.0)
					* this._parseFloat(this._fieldValue['DISCOUNT_SUM'], 2, 0.0);
				this.setFieldValue('DISCOUNT_SUBTOTAL', discountSubTotal.toFixed(2));

				this.setFieldValue('TAX_SUM',  this.getTaxSum());
				this._settings['CUSTOMIZED'] = true;
			}
			else if(fieldId === 'TAX_RATE')
			{
				if(this._editor.isTaxAllowed())
				{
					isTaxIncluded = this._settings['TAX_INCLUDED'];

					this._settings['TAX_RATE'] = this._parseFloat(this._fieldValue[fieldId], 2, 0.0);

					if (isTaxIncluded)
					{
						priceNetto = BX.CrmProduct.calculateExclusivePrice(
							this._settings['PRICE_BRUTTO'],
							this._settings['TAX_RATE']
						);
						this._settings['PRICE_NETTO'] = this._round(priceNetto, 2);
					}
					else
					{
						priceNetto = this._settings['PRICE_NETTO'];
						priceBrutto = BX.CrmProduct.calculateInclusivePrice(
							this._settings['PRICE_NETTO'],
							this._settings['TAX_RATE']
						);
						this._settings['PRICE_BRUTTO'] = this._round(priceBrutto, 2);
					}

					discount = discountType === BX.CrmDiscountType.percentage ?
						this._settings['DISCOUNT_RATE'] : this._settings['DISCOUNT_SUM'];
					exclusivePrice = this._calculatePrice(priceNetto, discount, discountType);

					this._settings['PRICE_EXCLUSIVE'] = this._round(exclusivePrice, 2);
					this._settings['PRICE'] = this._round(
						BX.CrmProduct.calculateInclusivePrice(exclusivePrice, this._settings['TAX_RATE']), 2
					);

					if (isTaxIncluded)
					{
						if (discountType === BX.CrmDiscountType.percentage)
						{
							this.setFieldValue('PRICE_NETTO', this._settings['PRICE_NETTO'].toFixed(2));
							this.setFieldValue(
								'DISCOUNT_SUM',
								this._calculateDiscount(this._settings['PRICE_NETTO'], this._settings['DISCOUNT_RATE']).toFixed(2)
							);

							// discount subtotal
							discountSubTotal =
								this._parseFloat(this._fieldValue['QUANTITY'], 4, 0.0)
								* this._parseFloat(this._fieldValue['DISCOUNT_SUM'], 2, 0.0);
							this.setFieldValue('DISCOUNT_SUBTOTAL', discountSubTotal.toFixed(2));
						}
						else
						{
							this._settings['DISCOUNT_RATE'] = this._round(this._calculateDiscountRate(this._settings['PRICE_NETTO'], (this._settings['PRICE_NETTO'] - this._settings['DISCOUNT_SUM'])), 2);
							this.setFieldValue('DISCOUNT_RATE', this._settings['DISCOUNT_RATE']);

							// discount subtotal
							discountSubTotal =
								this._parseFloat(this._fieldValue['QUANTITY'], 4, 0.0)
								* this._parseFloat(this._fieldValue['DISCOUNT_SUM'], 2, 0.0);
							this.setFieldValue('DISCOUNT_SUBTOTAL', discountSubTotal.toFixed(2));
						}
					}
					else
					{
						this.setFieldValue('PRICE_BRUTTO', this._settings['PRICE_BRUTTO'].toFixed(2));
					}

					this.setFieldValue('TAX_SUM',  this.getTaxSum());
					this._settings['CUSTOMIZED'] = true;
				}
			}
			else if(fieldId === 'TAX_INCLUDED')
			{
				if(this._editor.isTaxAllowed())
				{
					isTaxIncluded = this._settings['TAX_INCLUDED'] = !!this._fieldValue[fieldId];
					if(!isTaxIncluded)
					{
						this._settings['PRICE_NETTO'] = this._settings['PRICE_BRUTTO'];
						this._settings['PRICE_BRUTTO'] = this._round(BX.CrmProduct.calculateInclusivePrice(this._settings['PRICE_NETTO'], this._settings['TAX_RATE']), 2);

						this.setFieldValue('PRICE_NETTO', this._settings['PRICE_NETTO'].toFixed(2), true);
					}
					else
					{
						this._settings['PRICE_BRUTTO'] = this._settings['PRICE_NETTO'];
						this._settings['PRICE_NETTO'] = this._round(BX.CrmProduct.calculateExclusivePrice(this._settings['PRICE_BRUTTO'], this._settings['TAX_RATE']), 2);

						this.setFieldValue('PRICE_BRUTTO', this._settings['PRICE_BRUTTO'].toFixed(2), true);
					}
				}
			}
			else if(fieldId === 'DISCOUNT_SUBTOTAL')
			{
				this._settings['DISCOUNT_SUBTOTAL'] = this._parseFloat(this._fieldValue[fieldId], 2, 0.0);
				if(this._round(parseFloat(this._settings['QUANTITY']), 4) === 0.0)
				{
					this._settings['QUANTITY'] = 1.0;
					this.setFieldValue('QUANTITY', 1.0)
				}
				this._settings['DISCOUNT_TYPE_ID'] = BX.CrmDiscountType.monetary;
				this.setFieldValue('DISCOUNT_TYPE_ID', this._settings['DISCOUNT_TYPE_ID']);

				this._settings['DISCOUNT_SUM'] = this._round((this._settings['DISCOUNT_SUBTOTAL'] / this._settings['QUANTITY']), 2);
				this.setFieldValue('DISCOUNT_SUM', this._settings['DISCOUNT_SUM'].toFixed(2));

				exclusivePrice = this._settings['PRICE_NETTO'] - this._settings['DISCOUNT_SUM'];

				this._settings['PRICE_EXCLUSIVE'] = exclusivePrice;
				this._settings['PRICE'] = this._round(BX.CrmProduct.calculateInclusivePrice(exclusivePrice, this._settings['TAX_RATE']), 2);
				this._settings['DISCOUNT_RATE'] = this._round(this._calculateDiscountRate(this._settings['PRICE_NETTO'], exclusivePrice), 2);
				this.setFieldValue('DISCOUNT_RATE', this._settings['DISCOUNT_RATE']);

				// discount subtotal
				this._settings['DISCOUNT_SUBTOTAL'] = this._round(this._settings['QUANTITY'] * this._settings['DISCOUNT_SUM'], 2);
				this._fieldValue['DISCOUNT_SUBTOTAL'] = this._settings['DISCOUNT_SUBTOTAL'].toFixed(2);
				this.setFieldView('DISCOUNT_SUBTOTAL', this._fieldValue['DISCOUNT_SUBTOTAL']);

				this.setFieldValue('TAX_SUM',  this.getTaxSum());
				this._settings['CUSTOMIZED'] = true;
			}
			else if(fieldId === 'SUM')
			{
				this._settings['SUM'] = this._parseFloat(this._fieldValue[fieldId], 2, 0.0);
				if(this._round(parseFloat(this._settings['QUANTITY']), 4) === 0.0)
				{
					this._settings['QUANTITY'] = 1.0;
					this.setFieldValue('QUANTITY', 1.0)
				}

				discountSum = this._settings['PRICE_NETTO'] - (this._settings['SUM'] / (this._settings['QUANTITY'] * (1 + this._settings['TAX_RATE'] / 100)));
				discountSum = this._round(discountSum, 2);

				this._settings['PRICE_EXCLUSIVE'] = this._round((this._settings['PRICE_NETTO'] - discountSum), 2);
				this._settings['PRICE'] = this._round(BX.CrmProduct.calculateInclusivePrice(this._settings['PRICE_EXCLUSIVE'], this._settings['TAX_RATE']), 2);

				this._settings['DISCOUNT_TYPE_ID'] = BX.CrmDiscountType.monetary;
				this.setFieldValue('DISCOUNT_TYPE_ID', this._settings['DISCOUNT_TYPE_ID']);

				this._settings['DISCOUNT_SUM'] = this._round(discountSum, 2);
				this.setFieldValue('DISCOUNT_SUM', this._settings['DISCOUNT_SUM']);

				this._settings['DISCOUNT_RATE'] = this._round(this._calculateDiscountRate(this._settings['PRICE_NETTO'], this._settings['PRICE_EXCLUSIVE']), 2);
				this.setFieldValue('DISCOUNT_RATE', this._settings['DISCOUNT_RATE']);

				this.setFieldValue('PRICE_NETTO', this._settings['PRICE_NETTO'].toFixed(2));
				this.setFieldValue('PRICE_BRUTTO', this._settings['PRICE_BRUTTO'].toFixed(2));

				// discount subtotal
				discountSubTotal =
					this._parseFloat(this._fieldValue['QUANTITY'], 4, 0.0)
					* this._parseFloat(this._fieldValue['DISCOUNT_SUM'], 2, 0.0);
				this.setFieldValue('DISCOUNT_SUBTOTAL', discountSubTotal.toFixed(2));

				this._sumValueAfterBlur = this._calculateSumTotal();

				this.setFieldValue('TAX_SUM',  this.getTaxSum());
				this._settings['CUSTOMIZED'] = true;
			}

			if(fieldId !== 'SUM')
			{
				if(this._settings['TAX_INCLUDED'])
				{
					this.setFieldValue('SUM', (this._settings['PRICE'] * this._settings['QUANTITY']).toFixed(2));
				}
				else
				{
					this.setFieldValue('SUM', this._calculateSumTotal());
				}
			}

			this._editor.calculateTotalsDelayed();
		},
		_bindEventsHandlers: function()
		{
			var self = this,
				row = this._container,
				fieldNames = this.getSetting("fields", []),
				fieldName, cEdit, i, j,
				productNameButton, productNameButtonClass = "crm-item-inp-btn";

			if(this._isEventsBinds)
				return;

			for(i = 0; i < fieldNames.length; i++)
			{
				fieldName = fieldNames[i];
				cEdit = BX(row.id + "_" + fieldName);
				if(cEdit)
				{
					switch(fieldName)
					{
						case "PRODUCT_NAME":
						case "QUANTITY":
							if(fieldName === "PRODUCT_NAME")
							{
								productNameButton = BX.findNextSibling(cEdit, {"class": productNameButtonClass});
								if(productNameButton)
								{
									BX.bind(productNameButton, "click", function(e)
									{
										self._onProductNameButtonClick.apply(self, [this, e])
									})
								}
							}
							if(fieldName !== "PRODUCT_NAME" || !this._fixProductName)
							{
								BX.bind(cEdit, "keyup", function(e)
								{
									self._onElementKeyUp.apply(self, [this, e])
								});
								BX.bind(cEdit, "input", function(e)
								{
									self._onElementChange.apply(self, [this, e])
								});
								BX.bind(cEdit, "focus", function(e)
								{
									self._onElementFocus.apply(self, [this, e])
								});
								BX.bind(cEdit, "blur", function(e)
								{
									self._onElementBlur.apply(self, [this, e])
								});
							}
							break;

						case "DISCOUNT":
							var discountTypeControls = [];
							if(cEdit)
								discountTypeControls[0] = BX.findChild(cEdit.parentNode, {"attr": {"class": "crm-item-sale-text"}}, true);
							var cView = BX(row.id + "_" + fieldName + "_v");
							if(cView)
								discountTypeControls[1] = BX.findChild(cView.parentNode, {"attr": {"class": "crm-item-sale-text"}}, true);
							for(j = 0; j < discountTypeControls.length; j++)
							{
								if(discountTypeControls[j])
									BX.bind(discountTypeControls[j], "click", function(e)
									{
										self._handleChangeDiscountTypeView.apply(self, [this, e]);
									});
							}
							BX.bind(cEdit, "keyup", function(e)
							{
								self._onElementKeyUp.apply(self, [this, e])
							});
							BX.bind(cEdit, "input", function(e)
							{
								self._onElementChange.apply(self, [this, e])
							});
							BX.bind(cEdit, "focus", function(e)
							{
								self._onElementFocus.apply(self, [this, e])
							});
							BX.bind(cEdit, "blur", function(e)
							{
								self._onElementBlur.apply(self, [this, e])
							});
							break;

						case "MEASURE":
						case "TAX_RATE":
							BX.bind(cEdit, "change", function(e)
							{
								self._onElementChange.apply(self, [this, e])
							});
							break;

						case "TAX_INCLUDED":
							BX.bind(cEdit, "click", function(e)
							{
								self._onElementChange.apply(self, [this, e])
							});
							break;

						case 'PRICE':
						case 'DISCOUNT_SUBTOTAL':
						case 'SUM':
							BX.bind(cEdit, "keyup", function(e)
							{
								self._onElementKeyUp.apply(self, [this, e])
							});
							BX.bind(cEdit, "input", function(e)
							{
								self._onElementChange.apply(self, [this, e])
							});
							BX.bind(cEdit, "focus", function(e)
							{
								self._onElementFocus.apply(self, [this, e])
							});
							BX.bind(cEdit, "blur", function(e)
							{
								self._onElementBlur.apply(self, [this, e])
							});
							break;
					}
				}
			}

			this._isEventsBinds = true;
		},
		_handleDeleteClick: function(e)
		{
			if(this.isReadOnly())
			{
				return;
			}

			if(this._editor.handleProductDeletion(this))
			{
				this.clean();
				BX.PreventDefault(e);
			}
		},
		_handleKeyDown: function(e)
		{
			// handle Alt+Enter
			if (!this._editor || !e || typeof(e) !== "object" || e.repeat
				|| e.keyCode !== 13 || e.type !== "keydown" || !e.altKey)
			{
				return;
			}
			this._editor.productRowAdd();

			return e.preventDefault();
		},
		refreshCurrencyText: function()
		{
			var row, discountTypeView, discountTypeText, discountText, discountTypeControls, cEdit, cView;

			discountTypeView = this._discountTypeView;
			if(discountTypeView === BX.CrmDiscountType.monetary)
			{
				row = this._container;
				discountTypeText = this._editor.getSetting("discountTypeText", []);
				discountText = (discountTypeText[discountTypeView] ? discountTypeText[discountTypeView] : '?');
				discountTypeControls = [];

				if(cEdit = BX(row.id + "_DISCOUNT"))
					discountTypeControls[0] = BX.findChild(cEdit.parentNode, {"attr": {"class": "crm-item-sale-text"}}, true);
				if(cView = BX(row.id + "_" + "_DISCOUNT_v"))
					discountTypeControls[1] = BX.findChild(cView.parentNode, {"attr": {"class": "crm-item-sale-text"}}, true);
				for(var i = 0; i < discountTypeControls.length; i++)
				{
					if(discountTypeControls[i])
					{
						//BX.setTextContent(discountTypeControls[i], discountText);
						discountTypeControls[i].innerHTML = discountText;
					}
				}
			}
		},
		//D&D abilities
		createGhostNode: function()
		{
			var node = BX.create("DIV", {attrs: {className: "crm-items-table-draggable-item"}});

			var wrapper = BX.create("DIV", {attrs: {className: "crm-items-table-drag-inner"}});
			wrapper.style.width = BX.pos(this._container).width + "px";
			node.appendChild(wrapper);

			var table = BX.create("TABLE", {attrs: {className: "crm-items-table"}});
			wrapper.appendChild(table);
			var tr = table.insertRow(-1);
			tr.className = "crm-items-table-even-row";
			var cell = tr.insertCell(-1);
			cell.className = "crm-item-cell crm-item-name";
			cell.appendChild(
				BX.create("SPAN",
					{
						attrs: {className: "crm-item-cell-view"},
						children: [
							BX.create("SPAN",
								{
									attrs: {className: "crm-table-name-left"},
									children: [
										BX.create("SPAN", {attrs: {className: "crm-item-move-btn"}}),
										BX.create("SPAN", {
											attrs: {className: "crm-item-num"},
											text: this.getRowNumber().toString() + "."
										})
									]
								}
							),
							BX.create("SPAN",
								{
									attrs: {className: "crm-item-txt-wrap"},
									children: [
										BX.create("DIV", {
											attrs: {className: "crm-item-name-txt"},
											text: this.getProductName()
										})
									]
								}
							)
						]
					}
				)
			);

			cell = tr.insertCell(-1);
			cell.className = "crm-item-cell crm-item-price";
			cell.appendChild(BX.create("SPAN", {attrs: {className: "crm-item-cell-text"}}));

			cell = tr.insertCell(-1);
			cell.className = "crm-item-cell crm-item-qua";
			cell.appendChild(BX.create("SPAN", {attrs: {className: "crm-item-cell-text"}}));

			cell = tr.insertCell(-1);
			cell.className = "crm-item-cell crm-item-unit";
			cell.appendChild(BX.create("SPAN", {attrs: {className: "crm-item-cell-text"}}));

			cell = tr.insertCell(-1);
			cell.className = "crm-item-cell crm-item-sale";
			cell.appendChild(BX.create("SPAN", {attrs: {className: "crm-item-cell-text"}}));

			cell = tr.insertCell(-1);
			cell.className = "crm-item-cell crm-item-total";
			cell.appendChild(BX.create("SPAN", {attrs: {className: "crm-item-cell-text"}}));

			cell = tr.insertCell(-1);
			cell.className = "crm-item-cell crm-item-move";
			cell.appendChild(BX.create("SPAN", {attrs: {className: "crm-item-cell-text"}}));

			return node;
		},
		_handleChangeDiscountTypeView: function(element, e)
		{
			if(element && !this._editor.isInvoiceMode())
			{
				var value = this.getDiscountTypeId();
				if(value === BX.CrmDiscountType.percentage)
					value = BX.CrmDiscountType.monetary
				else
					value = BX.CrmDiscountType.percentage

				if(!this._viewMode)
					this.setFieldValue("DISCOUNT_TYPE_ID", value, true);

				if(e)
					BX.PreventDefault(e);
			}
		},
		_parseFloat: function(s, precision, defaultValue)
		{
			if(typeof(precision) === 'undefined')
			{
				precision = 2;
			}

			if(typeof(defaultValue) === 'undefined')
			{
				defaultValue = 0.0;
			}

			if(!BX.type.isNotEmptyString(s))
			{
				return defaultValue;
			}

			s = s.replace(/^\s+|\s+$/g, '');
			var dot = s.indexOf('.');
			var comma = s.indexOf(',');
			var isNegative = s.indexOf('-') === 0;

			if(dot < 0 && comma >= 0)
			{
				var s1 = s.substr(0, comma);
				var decimalLength = s.length - comma - 1;
				if(decimalLength > 0)
				{
					s1 += '.' + s.substr(comma + 1, decimalLength);
				}
				s = s1;
			}
			s = s.replace(/[^\d\.]+/g, '');
			var f = parseFloat(s);

			if(isNaN(f))
			{
				f = defaultValue;
			}
			if(isNegative)
			{
				f = -f;
			}

			if(precision >= 0)
			{
				f = this._round(f, precision);
			}

			return f;
		},
		_parseInt: function(s, defaultValue)
		{
			if(typeof(defaultValue) === 'undefined')
			{
				defaultValue = 0.0;
			}

			if(!BX.type.isNotEmptyString(s))
			{
				return defaultValue;
			}

			s = s.replace(/^\s+|\s+$/g, '');
			var isNegative = s.indexOf('-') === 0;

			var i = parseInt(s.replace(/[^\d]/g, ''));
			if(isNaN(i))
			{
				i = defaultValue;
			}
			if(isNegative)
			{
				i = -i;
			}

			return i;
		},
		_round: function(v, precision)
		{
			return BX.CrmProduct.round(v, precision);
		},
		_calculateDiscountRate: function(originalPrice, price)
		{
			if(originalPrice === 0.0)
			{
				return 0;
			}

			if(price === 0.0)
			{
				return originalPrice > 0 ? 100.0 : -100.0;
			}

			return this._round(((100 * (originalPrice - price)) / originalPrice), 2);
		},
		_calculateDiscount: function(originalPrice, discountRate)
		{
			return originalPrice * discountRate / 100;
		},
		_calculateDiscountByDiscountPrice: function(discountPrice, discountRate, precision)
		{
			return (100 * discountPrice / (100 - discountRate) - discountPrice);
		},
		_calculatePrice: function(originalPrice, discount, discountType)
		{
			if(discountType === BX.CrmDiscountType.percentage)
			{
				return (originalPrice - ((originalPrice * discount) / 100));
			}
			if(discountType === BX.CrmDiscountType.monetary)
			{
				return (originalPrice - discount);
			}
			return 0.0;
		},
		_calculateSumTotal: function()
		{
			var sum = this._settings['TAX_INCLUDED']
				? this._settings['PRICE'] * this._settings['QUANTITY']
				: BX.CrmProduct.calculateInclusivePrice(
					this._round(this._settings['PRICE_EXCLUSIVE'] * this._settings['QUANTITY'], 2),
					this._settings['TAX_RATE']
				);
			return this._round(sum, 2);
		},
		getTaxSum: function()
		{
			var sum = this._settings['TAX_INCLUDED']
				? (this._settings['PRICE'] * this._settings['QUANTITY']) * (1 - 1 / (1 + this._settings['TAX_RATE'] / 100))
				: (this._settings['PRICE_EXCLUSIVE'] * this._settings['QUANTITY']) * this._settings['TAX_RATE'] / 100;

			return this._round(sum, 2);
		},
		fieldNameByElement: function(element)
		{
			var result = "",
				rowIdPrefix = this._container.id,
				prefixLength = rowIdPrefix.length;

			if(BX.type.isElementNode(element)
				&& element.id && prefixLength > 0
				&& element.id.length > (prefixLength + 1))
			{
				result = element.id.substring(prefixLength + 1);
			}

			return result;
		},
		clearZeroValue: function(fieldName, element)
		{
			if(fieldName && BX.type.isElementNode(element))
			{
				var parsePrecision = -1;
				switch(fieldName)
				{
					case 'PRICE':
					case 'DISCOUNT':
					case 'DISCOUNT_SUBTOTAL':
					case 'SUM':
						parsePrecision = 2;
						break;

					case 'QUANTITY':
						parsePrecision = 4;
						break;
				}
				if(parsePrecision > 0)
				{
					var value = this._parseFloat(element.value, parsePrecision, 0.0);
					if(value === 0.0)
					{
						element.value = '';
					}
				}
			}
		},
		formatElementValue: function(fieldName, element)
		{
			if(fieldName && BX.type.isElementNode(element))
			{
				var parsePrecision = -1;
				var formatPrecision = -1;
				switch(fieldName)
				{
					case 'PRICE':
					case 'DISCOUNT_SUBTOTAL':
					case 'SUM':
						formatPrecision = parsePrecision = 2;
						break;

					case 'QUANTITY':
						parsePrecision = 4;
						break;

					case 'DISCOUNT':
						parsePrecision = 2;
						if(this._discountTypeView === BX.CrmDiscountType.monetary)
							formatPrecision = 2;
						break;
				}
				if(parsePrecision > 0)
				{
					var value;
					if(fieldName === 'DISCOUNT_SUBTOTAL')
						value = this._parseFloat(this._fieldValue['DISCOUNT_SUBTOTAL'], parsePrecision, 0.0);
					else
						value = this._parseFloat(element.value, parsePrecision, 0.0);

					if(formatPrecision >= 0)
						element.value = value.toFixed(formatPrecision);
					else
						element.value = value.toString();
				}
			}
		},
		_onElementKeyUp: function(element, e)
		{
			var fieldName = this.fieldNameByElement(element),
				fieldId;

			if(fieldName)
			{
				var c = e.keyCode;
				if(c === 13 || c === 27 || (c >= 37 && c <= 40) || (c >= 112 && c <= 123))
					return;

				var isTaxAllowed = this._editor.isTaxAllowed();
				var isTaxIncluded = (fieldName === "TAX_INCLUDED") ? !!element.checked : !!this._fieldValue["TAX_INCLUDED"];
				var discountTypeView = this._discountTypeView;

				switch(fieldName)
				{
					case "PRICE":
						if(isTaxAllowed && !isTaxIncluded)
							fieldId = "PRICE_NETTO";
						else
							fieldId = "PRICE_BRUTTO";
						break;

					case "MEASURE":
						fieldId = "MEASURE_CODE";
						break;

					case "DISCOUNT":
						if(discountTypeView === BX.CrmDiscountType.percentage)
							fieldId = "DISCOUNT_RATE";
						else
							fieldId = "DISCOUNT_SUM";
						break;

					default:
						fieldId = fieldName;
				}

				var value = (element.type === "checkbox") ? element.checked : element.value;
				if(this._fieldValue[fieldId] === value)
					return;

				this._fieldValue[fieldId] = value;

				this.setFieldView(fieldId, value);

				if(fieldName === "PRODUCT_NAME")
					this._handleProductNameChange(value);

				this.processFieldValueChange(fieldId);

//				if (fieldName === "PRODUCT_NAME")
//				{
//					this.productSearchDelayed();
//				}
			}
		},
		_onElementChange: function(element, e)
		{
			var fieldName = this.fieldNameByElement(element),
				fieldId;

			if(fieldName)
			{
				var isTaxAllowed = this._editor.isTaxAllowed();
				var isTaxIncluded = (fieldName === "TAX_INCLUDED") ? !!element.checked : !!this._fieldValue["TAX_INCLUDED"];
				var discountTypeView = this._discountTypeView;

				switch(fieldName)
				{
					case "PRICE":
						if(isTaxAllowed && !isTaxIncluded)
							fieldId = "PRICE_NETTO";
						else
							fieldId = "PRICE_BRUTTO";
						break;

					case "MEASURE":
						fieldId = "MEASURE_CODE";
						break;

					case "DISCOUNT":
						if(discountTypeView === BX.CrmDiscountType.percentage)
							fieldId = "DISCOUNT_RATE";
						else
							fieldId = "DISCOUNT_SUM";
						break;

					default:
						fieldId = fieldName;
				}

				var value = (element.type === "checkbox") ? element.checked : element.value;
				if(this._fieldValue[fieldId] === value)
					return;

				this._fieldValue[fieldId] = value;

				this.setFieldView(fieldId, value);

				if(fieldName === "PRODUCT_NAME")
					this._handleProductNameChange(value);

				this.processFieldValueChange(fieldId);

				if(fieldName === 'TAX_INCLUDED' && this._editor.getSetting('taxUniform', true) === true)
					this._editor.changeTaxIncludedUniform(this, value);

				this._editor.processProductChange(this);
			}
		},
		_handleProductNameChange: function(value)
		{
			var row = this._container,
				cEdit = row ? BX(row.id + "_PRODUCT_NAME") : null,
				trimmedValue,
				productID,
				productNameButton,
				productNameButtonClass = "crm-item-inp-btn",
				productNameButtonClassPlus = "crm-item-inp-plus",
				productNameButtonClassArrow = "crm-item-inp-arrow",
				containerPaddingRight = "34px";

			if(cEdit)
			{
				productNameButton = BX.findNextSibling(cEdit, {"class": productNameButtonClass});
				if(productNameButton)
				{
					productID = this.getProductId();
					trimmedValue = BX.util.trim(String(value));
					if(trimmedValue.length > 0)
					{
						if(productID > 0)
						{
							BX.removeClass(productNameButton, productNameButtonClassPlus);
							BX.addClass(productNameButton, productNameButtonClassArrow);
							productNameButton.setAttribute("title", BX.CrmProductEditorMessages["openProductCard"]);
							productNameButton.parentNode.style.paddingRight = containerPaddingRight;
						}
						else
						{
							BX.removeClass(productNameButton, productNameButtonClassArrow);
							if(this._editor.getSetting("canAddProduct", false))
							{
								BX.addClass(productNameButton, productNameButtonClassPlus);
								productNameButton.setAttribute("title", BX.CrmProductEditorMessages["createProduct"]);
								productNameButton.parentNode.style.paddingRight = containerPaddingRight;
							}
							else
							{
								productNameButton.setAttribute("title", "");
								productNameButton.parentNode.style.paddingRight = 0;
							}
						}
					}
					else
					{
						this.setProductId(0);
						BX.removeClass(
							productNameButton,
							productNameButtonClassArrow + " " + productNameButtonClassPlus
						);
						productNameButton.setAttribute("title", "");
						productNameButton.parentNode.style.paddingRight = 0;
					}
				}
			}
		},
		_emulateTaxIncludedElementChange: function(state)
		{
			var fieldId = "TAX_INCLUDED",
				row = this._container,
				cEdit = BX(row.id + "_" + fieldId);
			if(cEdit)
			{
				state = !!state;
				if(cEdit.checked !== state)
				{
					cEdit.checked = state;
					this._fieldValue[fieldId] = state;
					this.setFieldView(fieldId, state);
					this.processFieldValueChange(fieldId);
				}
			}
		},
		_onElementFocus: function(element, e)
		{
			var fieldName = this.fieldNameByElement(element);
			if(fieldName)
			{
				this._focusedField = fieldName;
				if(fieldName === "PRODUCT_NAME" || fieldName === "PRICE" || fieldName === "QUANTITY"
					|| fieldName === "DISCOUNT" || fieldName === "DISCOUNT_SUBTOTAL" || fieldName === "SUM")
				{
					this._editor.handleProductFocusChange(this, true);
				}

				this.clearZeroValue(fieldName, element);
			}
		},
		_onElementBlur: function(element, e)
		{
			var fieldName = this.fieldNameByElement(element);
			if(fieldName)
			{
				if(this._focusedField === fieldName)
					this._focusedField = null;
				if(fieldName === "PRODUCT_NAME" || fieldName === "PRICE" || fieldName === "QUANTITY"
					|| fieldName === "DISCOUNT" || fieldName === "DISCOUNT_SUBTOTAL" || fieldName === "SUM")
				{
					if (fieldName === "SUM" && this._sumValueAfterBlur !== null)
					{
						this.setFieldValue("SUM", this._sumValueAfterBlur);
						this._sumValueAfterBlur = null;
					}
					this._editor.handleProductFocusChange(this, false);
				}

				this.formatElementValue(fieldName, element);
			}
		},
		_onProductNameButtonClick: function(element, e)
		{
			if(BX.hasClass(element, "crm-item-inp-arrow"))
			{
				this._openProductCardUrl(this._getProductShowUrl());
			}
			else if (BX.hasClass(element, "crm-item-inp-plus"))
			{
				if (this._editor.isProductCardEnabled())
				{
					this._openNewProductCardUrl();
				}
				else
				{
					this.createInCatalogViaDialog((BX.type.isDomNode(element)) ? element : null);
				}
			}
		},
		_openNewProductCardUrl: function()
		{
			this.saveSettings();

			var defMeasureId = 0;
			var defMeasureInfo = this._editor.getSetting("defaultMeasure", null);
			if (defMeasureInfo)
			{
				if (defMeasureInfo.hasOwnProperty("ID"))
				{
					defMeasureId = defMeasureInfo["ID"];
				}
			}
			var measureId = this.getMeasureIdByCode(this.getMeasureCode());
			if (measureId === 0)
			{
				measureId = defMeasureId;
			}

			var taxId = 0;
			var taxIncluded = "N";
			if (this._editor.isTaxAllowed())
			{
				var taxRate = parseFloat(this.getTaxRate());
				taxId = this._editor.getTaxIdByValue(taxRate);
				taxIncluded = (taxId !== 0 && this.getSetting('TAX_INCLUDED', false)) ? "Y" : "N";
			}

			var productFields = {
				"NAME": this.getSetting(this._fixProductName ? "FIXED_PRODUCT_NAME" : "PRODUCT_NAME", ""),
				"CURRENCY": this._editor.getCurrencyId(),
				"QUANTITY": this.getQuantity(),
				"PRICE": (taxIncluded === "Y") ? this.getSetting("PRICE_BRUTTO", 0.0).toFixed(2) : this.getSetting("PRICE_NETTO", 0.0).toFixed(2),
				"MEASURE": measureId,
				"VAT_ID": taxId,
				"VAT_INCLUDED": taxIncluded
			};

			var url = this._getProductShowUrlByProductId(0);
			var options;

			if (BX.Reflection.getClass('BX.SidePanel.Instance'))
			{
				var rule = BX.SidePanel.Instance.getUrlRule(url);
				options = rule && rule.options ? BX.clone(rule.options) : {};
				options.requestMethod = 'post';
				options.requestParams = {
					external_fields: productFields
				};
				options.data = {
					product: this
				};
			}

			this._openProductCardUrl(url, options);
		},
		_openProductCardUrl: function(url, options)
		{
			if (BX.type.isNotEmptyString(url))
			{
				if (this._editor.isProductCardEnabled() && BX.Reflection.getClass('BX.SidePanel.Instance'))
				{
					BX.SidePanel.Instance.open(url, options);
				}
				else
				{
					window.open(url);
				}
			}
		},
		_getProductShowUrl: function()
		{
			return this._getProductShowUrlByProductId(this.getProductId());
		},
		_getProductShowUrlByProductId: function(productId)
		{
			var url = null,
				pathTemplate;

			if (this._editor)
			{
				pathTemplate = this._editor.getPathToProductShow();
				if (pathTemplate && typeof (pathTemplate) === "string" && pathTemplate.length > 0)
				{
					productId = parseInt(productId);
					if (BX.type.isNumber(productId))
					{
						url = pathTemplate.replace("#product_id#", productId);
					}
				}
			}

			return url;
		},
		createInCatalog: function()
		{
			var postData, url;
			var measureId, defMeasureId, defMeasureInfo;
			var taxRate, taxId, taxIncluded;

			this.saveSettings();

			defMeasureId = 0;
			defMeasureInfo = this._editor.getSetting("defaultMeasure", null);
			if(defMeasureInfo)
			{
				if(defMeasureInfo.hasOwnProperty("ID"))
					defMeasureId = defMeasureInfo["ID"];
			}
			measureId = this.getMeasureIdByCode(this.getMeasureCode());
			if(measureId === 0)
				measureId = defMeasureId;

			taxId = 0;
			taxIncluded = "N";
			if(this._editor.isTaxAllowed())
			{
				taxRate = parseFloat(this.getTaxRate());
				taxId = this._editor.getTaxIdByValue(taxRate);
				taxIncluded = (taxId !== 0 && this.getSetting('TAX_INCLUDED', false)) ? "Y" : "N";
			}

			postData = {
				"NAME": this.getSetting(this._fixProductName ? "FIXED_PRODUCT_NAME" : "PRODUCT_NAME", ""),
				"DESCRIPTION": "",
				"ACTIVE": "Y",
				"CURRENCY": this._editor.getCurrencyId(),
				"PRICE": (taxIncluded === "Y") ? this.getSetting("PRICE_BRUTTO", 0.0).toFixed(2) : this.getSetting("PRICE_NETTO", 0.0).toFixed(2),
				"MEASURE": measureId,
				"VAT_ID": taxId,
				"VAT_INCLUDED": taxIncluded,
				"SECTION_ID": 0,
				"SORT": 100,
				"sessid": this._editor.getSetting("sessid", ""),
				"ajax": "Y"
			};

			url = String(this._editor.getSetting("pathToProductEdit", "")).replace("#product_id#", "0");

			BX.ajax({
				url: url,
				method: "POST",
				dataType: "json",
				data: postData,
				onsuccess: BX.delegate(this.onCreateInCatalogSuccess, this),
				onfailure: BX.delegate(this.onCreateInCatalogFailure, this)
			});
		},
		onCreateInCatalogSuccess: function(data)
		{
			var productId = 0, err = "",
				row,
				cEdit,
				productNameButton,
				productNameButtonClass = "crm-item-inp-btn",
				productNameButtonClassPlus = "crm-item-inp-plus",
				productNameButtonClassArrow = "crm-item-inp-arrow",
				containerPaddingRight = "34px";

			BX.closeWait();
			err = (data["err"] !== "") ? data["err"] : "";
			productId = (parseInt(data["productId"]) > 0) ? parseInt(data["productId"]) : 0;

			if(productId > 0)
			{
				this.setProductId(productId);

				row = this._container;
				cEdit = row ? BX(row.id + "_PRODUCT_NAME") : null;
				if(cEdit)
				{
					productNameButton = BX.findNextSibling(cEdit, {"class": productNameButtonClass});
					if(productNameButton)
					{
						BX.removeClass(productNameButton, productNameButtonClassPlus);
						BX.addClass(productNameButton, productNameButtonClassArrow);
						productNameButton.setAttribute("title", BX.CrmProductEditorMessages["openProductCard"]);
						productNameButton.parentNode.style.paddingRight = containerPaddingRight;
					}
				}
			}
		},
		onCreateInCatalogFailure: function(data)
		{
		},
		createInCatalogViaDialog: function(initialControl)
		{
			var dialog, dialogSettings, customValues;
			var measureId, defMeasureId, defMeasureInfo;
			var taxRate, taxId, taxIncluded;

			this.saveSettings();

			defMeasureId = 0;
			defMeasureInfo = this._editor.getSetting("defaultMeasure", null);
			if(defMeasureInfo)
			{
				if(defMeasureInfo.hasOwnProperty("ID"))
					defMeasureId = defMeasureInfo["ID"];
			}
			measureId = this.getMeasureIdByCode(this.getMeasureCode());
			if(measureId === 0)
				measureId = defMeasureId;

			taxId = 0;
			taxIncluded = "N";
			if(this._editor.isTaxAllowed())
			{
				taxRate = parseFloat(this.getTaxRate());
				taxId = this._editor.getTaxIdByValue(taxRate);
				taxIncluded = (taxId !== 0 && this.getSetting('TAX_INCLUDED', false)) ? "Y" : "N";
			}

			customValues = {
				"NAME": this.getSetting(this._fixProductName ? "FIXED_PRODUCT_NAME" : "PRODUCT_NAME", ""),
				"CURRENCY": this._editor.getCurrencyId(),
				"PRICE": (taxIncluded === "Y") ? this.getSetting("PRICE_BRUTTO", 0.0).toFixed(2) : this.getSetting("PRICE_NETTO", 0.0).toFixed(2),
				"MEASURE": measureId,
				"VAT_ID": taxId,
				"VAT_INCLUDED": taxIncluded
			};

			dialogSettings = BX.clone(this._editor._settings["productCreateDialogSettings"]);
			dialogSettings['initialControl'] = initialControl;
			dialogSettings['productAdditionHandler'] = BX.delegate(this.handleSetProductFromDialog, this);
			dialogSettings['customValues'] = customValues;
			dialog = new BX.CrmProductCreateDialog(dialogSettings);
			dialog.show();
		},
		handleSetProductFromDialog: function(data)
		{
			var itemData = typeof(data['product']) != 'undefined' && typeof(data['product'][0]) != 'undefined' ? data['product'][0] : null;
			if(!itemData)
				return;

			this.saveSettings();

			var editor = this._editor;
			var quantity = this.getQuantity();
			var discountTypeId = this.getDiscountTypeId();
			var discountValue =
				(discountTypeId === BX.CrmDiscountType.percentage) ? this.getDiscountRate() : this.getDiscountSum();
			editor.handleProductSearchSelect(this, itemData);
			this.setFieldValue("DISCOUNT_TYPE_ID", parseInt(discountTypeId));
			if(discountTypeId === BX.CrmDiscountType.percentage)
				this.setFieldValue("DISCOUNT_RATE", this._round(parseFloat(discountValue), 2), true);
			else
				this.setFieldValue("DISCOUNT_SUM", parseFloat(discountValue).toFixed(2), true);
			this.setFieldValue("QUANTITY", quantity, true);
			//editor.focusProductRow(this);
		}
	};
	BX.CrmProduct.calculateExclusivePrice = function(inclusivePrice, taxRate)
	{
		// Tax is not included in price
		return inclusivePrice / (1 + (taxRate / 100));
	};
	BX.CrmProduct.calculateInclusivePrice = function(exclusivePrice, taxRate)
	{
		// Tax is included in price
		return exclusivePrice * (1 + (taxRate / 100));
	};
	BX.CrmProduct.round = function(value, precision)
	{
		if(!BX.type.isNumber(precision))
		{
			precision = 2;
		}
		var factor = Math.pow(10, precision);
		return (Math.round(value * factor) / factor);
	};
	BX.CrmProduct.create = function(settings, row, editor)
	{
		var self = new BX.CrmProduct();
		self.initialize(settings, row, editor);
		return self;
	};
}
if (typeof(BX.CrmProductSearch) === "undefined")
{
	BX.CrmProductSearch = function()
	{
		this._product = null;
		this._settings = {};
		this.cache = [];
		this.cache_key = null;
		this._data = [];

		this.currentValue = '';
		this.currentRow = -1;
		this.RESULT = null;
		this.CONTAINER = null;
		this.INPUT = null;
		this.WAIT = null;

		this._handleOnChange = true;
		this._onChangeTimer = null;

		this.focused = false;
	};
	BX.CrmProductSearch.prototype =
	{
		initialize: function(config, product)
		{
			this._product = product;
			this._settings = {
				"AJAX_PAGE": config["AJAX_PAGE"],
				"CONTAINER_ID": config["CONTAINER_ID"],
				"INPUT_ID": config["INPUT_ID"],
				"MIN_QUERY_LEN": parseInt(config["MIN_QUERY_LEN"])
			};
			if(config["WAIT_IMAGE"])
				this._settings["WAIT_IMAGE"] = config["WAIT_IMAGE"];
			if (this._settings["MIN_QUERY_LEN"] <= 0)
				this._settings["MIN_QUERY_LEN"] = 3;

			this.CONTAINER = document.getElementById(this._settings["CONTAINER_ID"]);
			this.RESULT = document.body.appendChild(document.createElement("DIV"));
			this.RESULT.className = 'title-search-result';
			this.INPUT = document.getElementById(this._settings["INPUT_ID"]);
			this.currentValue = this.oldValue = this.INPUT.value;
			BX.bind(this.INPUT, 'focus', BX.delegate(this.onFocusGain, this));
			BX.bind(this.INPUT, 'blur', BX.delegate(this.onFocusLost, this));

			BX.bind();
			this.INPUT.onkeydown = BX.delegate(this.onKeyDown, this);

			if(this._settings["WAIT_IMAGE"])
			{
				this.WAIT = document.body.appendChild(document.createElement("DIV"));
				this.WAIT.style.backgroundImage = "url('" + this._settings["WAIT_IMAGE"] + "')";
				if(!BX.browser.IsIE())
					this.WAIT.style.backgroundRepeat = 'none';
				this.WAIT.style.display = 'none';
				this.WAIT.style.position = 'absolute';
				this.WAIT.style.zIndex = '1100';
			}

			BX.bind(this.INPUT, 'bxchange', BX.delegate(this.onChangeDelayed, this));
		},
		showResult: function(result)
		{
			var pos = BX.pos(this.CONTAINER);
			pos.width = pos.right - pos.left;
			this.RESULT.style.position = 'absolute';
			this.RESULT.style.top = (pos.bottom + 2) + 'px';
			this.RESULT.style.left = pos.left + 'px';
			this.RESULT.style.width = pos.width + 'px';
			if(result != null)
				this.RESULT.innerHTML = result;

			if(this.RESULT.innerHTML.length > 0 && this.INPUT.value !== this.currentValue && this.focused)
				this.RESULT.style.display = 'block';
			else
				this.RESULT.style.display = 'none';

			//ajust left column to be an outline
			var th;
			var tbl = BX.findChild(this.RESULT, {'tag':'table','class':'title-search-result'}, true);
			if(tbl) th = BX.findChild(tbl, {'tag':'th'}, true);
			if(th)
			{
				var tbl_pos = BX.pos(tbl);
				tbl_pos.width = tbl_pos.right - tbl_pos.left;

				var th_pos = BX.pos(th);
				th_pos.width = th_pos.right - th_pos.left;
				th.style.width = th_pos.width + 'px';

				this.RESULT.style.width = (pos.width + th_pos.width) + 'px';

				//Move table to left by width of the first column
				this.RESULT.style.left = (pos.left - th_pos.width - 1)+ 'px';

				//Shrink table when it's too wide
				if((tbl_pos.width - th_pos.width) > pos.width)
					this.RESULT.style.width = (pos.width + th_pos.width -1) + 'px';

				//Check if table is too wide and shrink result div to it's width
				tbl_pos = BX.pos(tbl);
				var res_pos = BX.pos(this.RESULT);
				if(res_pos.right > tbl_pos.right)
				{
					this.RESULT.style.width = (tbl_pos.right - tbl_pos.left) + 'px';
				}
			}

			var fade;
			if(tbl) fade = BX.findChild(this.RESULT, {'class':'title-search-fader'}, true);
			if(fade && th)
			{
				res_pos = BX.pos(this.RESULT);
				fade.style.left = (res_pos.right - res_pos.left - 18) + 'px';
				fade.style.width = 18 + 'px';
				fade.style.top = 0 + 'px';
				fade.style.height = (res_pos.bottom - res_pos.top) + 'px';
				fade.style.display = 'block';
			}
		},
		onKeyPress: function(keyCode)
		{
			var tbl = BX.findChild(this.RESULT, {'tag':'table','class':'title-search-result'}, true);
			if(!tbl)
				return false;

			var cnt = tbl.rows.length,
				i;

			switch (keyCode)
			{
				case 27: // escape key - close search div
					this.RESULT.style.display = 'none';
					this.currentRow = -1;
					this.unselectAll();
					this.currentValue = this.INPUT.oldValue = this.INPUT.value;
					return true;

				case 40: // down key - navigate down on search results
					if(this.RESULT.style.display == 'none')
						this.RESULT.style.display = 'block';

					var first = -1;
					for(i = 0; i < cnt; i++)
					{
						if(!BX.findChild(tbl.rows[i], {'class':'title-search-separator'}, true))
						{
							if(first == -1)
								first = i;

							if(this.currentRow < i)
							{
								this.currentRow = i;
								break;
							}
							else if(tbl.rows[i].className == 'title-search-selected')
							{
								tbl.rows[i].className = '';
							}
						}
					}

					if(i == cnt && this.currentRow != i)
						this.currentRow = first;

					tbl.rows[this.currentRow].className = 'title-search-selected';
					return true;

				case 38: // up key - navigate up on search results
					if(this.RESULT.style.display == 'none')
						this.RESULT.style.display = 'block';

					var last = -1;
					for(i = cnt-1; i >= 0; i--)
					{
						if(!BX.findChild(tbl.rows[i], {'class':'title-search-separator'}, true))
						{
							if(last == -1)
								last = i;

							if(this.currentRow > i)
							{
								this.currentRow = i;
								break;
							}
							else if(tbl.rows[i].className == 'title-search-selected')
							{
								tbl.rows[i].className = '';
							}
						}
					}

					if(i < 0 && this.currentRow != i)
						this.currentRow = last;

					tbl.rows[this.currentRow].className = 'title-search-selected';
					return true;

				case 13: // enter key - choose current search result
					if(this.RESULT.style.display == 'block')
					{
						for(i = 0; i < cnt; i++)
						{
							if(this.currentRow == i)
							{
								this.RESULT.style.display = 'none';
								this.currentRow = -1;
								this.unselectAll();
								this.handleSelectItem(i);
							}
						}
					}
					return true;
			}

			return false;
		},
		onTimeout: function()
		{
			this.onChange(function(){
				setTimeout(BX.delegate(this.onTimeout, this), 500);
			});
		},
		onChangeDelayed: function()
		{
			if(this._handleOnChange && this.INPUT.value != this.oldValue)
			{
				this.RESULT.style.display = "none";
				this.RESULT.innerHTML = "";
				if(this.INPUT.value.length >= this._settings["MIN_QUERY_LEN"])
				{
					this.cache_key = this._settings["INPUT_ID"] + '|' + this.INPUT.value;
					if(this.cache[this.cache_key] == null)
					{
						if (this._onChangeTimer)
							clearTimeout(this._onChangeTimer);
						this._onChangeTimer = setTimeout(BX.delegate(this.onChange, this), 500);
						return;
					}
				}
				this.onChange();
			}
		},
		onChange: function()
		{
			if(this._handleOnChange && this.INPUT.value != this.oldValue)
			{
				this.RESULT.style.display = "none";
				this.RESULT.innerHTML = "";
				this.currentValue = null;
				this.oldValue = this.INPUT.value;
				if(this.INPUT.value.length >= this._settings["MIN_QUERY_LEN"])
				{
					this.cache_key = this._settings["INPUT_ID"] + '|' + this.INPUT.value;
					if(this.cache[this.cache_key] == null)
					{
						if(this.WAIT)
						{
							var pos = BX.pos(this.INPUT);
							var height = (pos.bottom - pos.top)-2;
							this.WAIT.style.top = (pos.top+1) + 'px';
							this.WAIT.style.height = height + 'px';
							this.WAIT.style.width = height + 'px';
							this.WAIT.style.left = (pos.right - height + 2) + 'px';
							this.WAIT.style.display = 'block';
						}

						var currencyID = this._product._editor.getCurrencyId();
						BX.ajax({
							'url': this._settings["AJAX_PAGE"],
							'method': 'POST',
							'dataType': 'json',
							'data':
							{
								"MODE": "SEARCH",
								"RESULT_WITH_VALUE" : "Y",
								"CURRENCY_ID": currencyID,
								"ENABLE_RAW_PRICES": "Y",
								"ENABLE_SEARCH_BY_ID": "N",
								"MULTI": "N",
								"VALUE": this.INPUT.value,
								"LIMIT": 5
							},
							onsuccess: BX.delegate(this.onProductSearchResponseSuccess, this),
							onfailure: BX.delegate(this.onProductSearchResponseFailure, this)
						});
					}
					else
					{
						var htmlContent = this.makeHtmlFromResult(this.cache[this.cache_key]);
						this.showResult(htmlContent);
						this.currentRow = -1;
						this.enableMouseEvents();
					}
				}
				else
				{
					this.RESULT.style.display = 'none';
					this.currentRow = -1;
					this.unselectAll();
					this.cache = [];
				}
			}
		},
		onProductSearchResponseSuccess: function(response)
		{
			var htmlContent,
				data = response['data'];

			this.cache_key = this._settings["INPUT_ID"] + '|' + response['searchValue']
			this.cache[this.cache_key] = data;
			if (this.INPUT.value === response['searchValue'])
			{
				htmlContent = this.makeHtmlFromResult(data);
				this.showResult(htmlContent);
			}
			this.currentRow = -1;
			this.enableMouseEvents();
			if(this.WAIT)
				this.WAIT.style.display = 'none';
		},
		onProductSearchResponseFailure: function(data)
		{
		},
		makeHtmlFromResult: function(data)
		{
			var htmlContent = '',
				item,
				i;

			this._data = [];

			if (BX.type.isArray(data) && data.length > 0)
			{
				htmlContent += '<table class="title-search-result">';
				for (i = 0; i<data.length; i++)
				{
					item = data[i];
					if (item && item.hasOwnProperty('title'))
					{
						htmlContent += '<tr id="row_' + i + '">';
						htmlContent += '<td class="title-search-item">' + BX.Text.encode(item["title"]) + '</td>';
						htmlContent += '</tr>';
						this._data[i] = item;
					}
				}
				htmlContent += '</table>';
			}

			return htmlContent;
		},
		unselectAll: function()
		{
			var tbl = BX.findChild(this.RESULT, {'tag':'table','class':'title-search-result'}, true);
			if(tbl)
			{
				var cnt = tbl.rows.length;
				for(var i = 0; i < cnt; i++)
					tbl.rows[i].className = '';
			}
		},
		enableMouseEvents: function()
		{
			var tbl = BX.findChild(this.RESULT, {'tag':'table','class':'title-search-result'}, true);
			var self = this;
			if(tbl)
			{
				var cnt = tbl.rows.length;
				for(var i = 0; i < cnt; i++)
					if(!BX.findChild(tbl.rows[i], {'class':'title-search-separator'}, true))
					{
						tbl.rows[i].id = 'row_' + i;
						tbl.rows[i].onmouseover = function(e) { BX.delegate(self.onMouseOver, self)(this, e); };
						tbl.rows[i].onmouseout = function(e) { BX.delegate(self.onMouseOut, self)(this, e); };
						BX.bind(tbl.rows[i], "click", function() {
							BX.delegate(self.handleSelectItem, self)(this.id.substr(4));
						});
					}
			}
		},
		onMouseOver: function(element, event)
		{
			if(this.currentRow != element.id.substr(4))
			{
				this.unselectAll();
				element.className = 'title-search-selected';
				this.currentRow = element.id.substr(4);
			}
		},
		onMouseOut: function(element, event)
		{
			element.className = '';
			this.currentRow = -1;
		},
		onFocusGain: function()
		{
			this.focused = true;
		},
		onFocusLost: function()
		{
			this.focused = false;
			setTimeout(BX.delegate(this.handleFocusLostTimer, this), 250);
			this.currentValue = this.INPUT.value;
		},
		handleFocusLostTimer: function()
		{
			this.RESULT.style.display = 'none';
		},
		/*onFocusGain: function()
		{
			if(this.RESULT.innerHTML.length)
				this.showResult();
		},*/
		onKeyDown: function(e)
		{
			if(!e)
				e = window.event;

			if (this.RESULT.style.display == 'block')
			{
				if(this.onKeyPress(e.keyCode))
					return BX.PreventDefault(e);
			}
		},
		handleSelectItem: function(itemIndex)
		{
			if (this._product && this._product._editor)
			{
				var editor = this._product._editor;
				this.disableOnChangeHandler();
				this.currentValue = this.oldValue = this._data[itemIndex]["title"];
				editor.handleProductSearchSelect(this._product, this._data[itemIndex]);
				this.enableOnChangeHandler();
			}
		},
		enableOnChangeHandler: function()
		{
			this._handleOnChange = true;
		},
		disableOnChangeHandler: function()
		{
			this._handleOnChange = false;
		}
	};
	BX.CrmProductSearch.create = function (config, product)
	{
		var self = new BX.CrmProductSearch();
		self.initialize(config, product);
		return self;
	};
}

//Placeholders
if(typeof(BX.CrmProductRowListPlaceholder) === "undefined")
{
	BX.CrmProductRowListPlaceholder = function()
	{
		this._settings = null;
		this._node = null;
		this._editor = null;
		this._productId = "";
		this._productIndex = -1;
	};
	BX.CrmProductRowListPlaceholder.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};
			this._node = this.getSetting("node", null);
			this._editor = this.getSetting("editor", null);
			this._productId = this.getSetting("productId", "");
			this._productIndex = parseInt(this.getSetting("productIndex", -1));
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getNode: function()
		{
			return this._node;
		},
		setNode: function(node)
		{
			this._node = node;
		},
		getProductId: function()
		{
			return this._productId;
		},
		getProductIndex: function()
		{
			return this._productIndex;
		},
		layout: function()
		{
			if(!this._node)
			{
				throw "CrmProductRowListPlaceholder: The 'node' is not assigned.";
			}

			var row = this._node;
			row.height = "30px";
			var cell = row.insertCell(-1);
			cell.className = "crm-item-cell";
			cell.colSpan = 8;
		}
	};
	BX.CrmProductRowListPlaceholder.create = function(settings)
	{
		var self = new BX.CrmProductRowListPlaceholder();
		self.initialize(settings);
		return self;
	};
}

//D&D Items
if(typeof(BX.CrmProdoctRowListDragItem) === "undefined")
{
	BX.CrmProdoctRowListDragItem = function()
	{
		BX.CrmProdoctRowListDragItem.superclass.constructor.apply(this);
		this._product = null;
		this._container = null;
		this._showInDragMode = true;
		this._ghostWidth = 0;
		this._ghostHeight = 0;
	};
	BX.extend(BX.CrmProdoctRowListDragItem, BX.CrmCustomDragItem);
	BX.CrmProdoctRowListDragItem.prototype.doInitialize = function()
	{
		this._product = this.getSetting("product");
		if(!this._product)
		{
			throw "CrmProdoctRowListDragItem: The 'product' parameter is not defined in settings or empty.";
		}

		this._container = this.getSetting("container");
		if(!this._container)
		{
			throw "CrmProdoctRowListDragItem: The 'container' parameter is not defined in settings or empty.";
		}

		this._showInDragMode = this.getSetting("showInDragMode", true);
	};
	BX.CrmProdoctRowListDragItem.prototype.getProduct = function()
	{
		return this._product;
	};
	BX.CrmProdoctRowListDragItem.prototype.createGhostNode = function()
	{
		if(this._ghostNode)
		{
			return this._ghostNode;
		}

		this._ghostNode = this._product.createGhostNode();
		document.body.appendChild(this._ghostNode);

		var rect = BX.pos(this._ghostNode);
		this._ghostWidth = rect.width;
		this._ghostHeight = rect.height;
	};
	BX.CrmProdoctRowListDragItem.prototype.removeGhostNode = function()
	{
		if(this._ghostNode)
		{
			document.body.removeChild(this._ghostNode);
			this._ghostNode = null;
		}
	};
	BX.CrmProdoctRowListDragItem.prototype.getContextId = function()
	{
		return BX.CrmProdoctRowListDragItem.contextId;
	};
	BX.CrmProdoctRowListDragItem.prototype.getContextData = function()
	{
		return ({ contextId: BX.CrmProdoctRowListDragItem.contextId, product: this._product });
	};
	BX.CrmProdoctRowListDragItem.prototype.processDragStart = function()
	{
		if(!this._showInDragMode)
		{
			this._product.getContainer().style.display = "none";
			BX.CrmProdoctRowListDragContainer.refresh();
		}
	};
	BX.CrmProdoctRowListDragItem.prototype._onDrag = function(x, y)
	{
		if(!this._isInDragMode)
		{
			return;
		}

		if(this._ghostNode)
		{
			x += this._ghostOffset.x;
			y += this._ghostOffset.y;

			var rect = BX.pos(this._container);
			if(x < rect.left || (x + this._ghostWidth) > rect.right)
			{
				x = rect.left;
			}

			if(y < rect.top)
			{
				y = rect.top;
			}
			else if(y > rect.bottom)
			{
				y = rect.bottom;
			}

			this._ghostNode.style.top = y + "px";
			this._ghostNode.style.left = x + "px";
		}

		this._dragNotifier.notify([x, y]);
	};
	BX.CrmProdoctRowListDragItem.prototype.processDragStop = function()
	{
		if(!this._showInDragMode)
		{
			this._product.getContainer().style.display = "";
			BX.CrmProdoctRowListDragContainer.refresh();
		}
	};
	BX.CrmProdoctRowListDragItem.contextId = "product_row_list_item";
	BX.CrmProdoctRowListDragItem.create = function(id, settings)
	{
		var self = new BX.CrmProdoctRowListDragItem();
		self.initialize(id, settings);
		return self;
	};
}

//D&D Containers
if(typeof(BX.CrmProdoctRowListDragContainer) === "undefined")
{
	BX.CrmProdoctRowListDragContainer = function()
		{
			BX.CrmProdoctRowListDragContainer.superclass.constructor.apply(this);
			this._editor = null;
		};
		BX.extend(BX.CrmProdoctRowListDragContainer, BX.CrmCustomDragContainer);
		BX.CrmProdoctRowListDragContainer.prototype.doInitialize = function()
		{
			this._editor = this.getSetting("editor");
			if(!this._editor)
			{
				throw "CrmProdoctRowListDragContainer: The 'editor' parameter is not defined in settings or empty.";
			}
		};
		BX.CrmProdoctRowListDragContainer.prototype.getEditor = function()
		{
			return this._editor;
		};
		BX.CrmProdoctRowListDragContainer.prototype.createPlaceHolder = function(pos)
		{
			var rect;
			var placeholder = this._editor.getPlaceHolder();
			if(placeholder)
			{
				rect = BX.pos(placeholder.getNode());
				if(pos.y >= rect.top && pos.y <= rect.bottom)
				{
					return;
				}
			}

			var productId = "";
			var productIndex = -1;
			var products = this._editor.getProducts();
			for(var i = 0; i < products.length; i++)
			{
				var product = products[i];
				rect = BX.pos(product.getContainer());
				if(pos.y >= rect.top && pos.y <= rect.bottom)
				{
					if((rect.top + (rect.height / 2) - pos.y) >= 0)
					{
						productId = product.getId();
						productIndex = i;
					}
					else if(i < (products.length - 1))
					{
						productId = products[i + 1].getId();
						productIndex = i + 1;
					}
					break;
				}
			}

			this._editor.createPlaceHolder({ id: productId, index: productIndex });
		};
		BX.CrmProdoctRowListDragContainer.prototype.removePlaceHolder = function()
		{
			this._editor.removePlaceHolder();
		};
		BX.CrmProdoctRowListDragContainer.prototype.isAllowedContext = function(contextId)
		{
			return (contextId === BX.CrmProdoctRowListDragItem.contextId);
		};
		BX.CrmProdoctRowListDragContainer.enable = function(enable, interval)
		{
			interval = parseInt(interval);
			if(interval > 0)
			{
				window.setTimeout(function() { BX.CrmProdoctRowListDragContainer.enable(enable, 0); });
				return;
			}

			for(var k in this.items)
			{
				if(this.items.hasOwnProperty(k))
				{
					this.items[k].enable(enable);
				}
			}
		};
		BX.CrmProdoctRowListDragContainer.refresh = function()
		{
			for(var k in this.items)
			{
				if(this.items.hasOwnProperty(k))
				{
					this.items[k].refresh();
				}
			}
		};
		BX.CrmProdoctRowListDragContainer.items = {};
		BX.CrmProdoctRowListDragContainer.create = function(id, settings)
		{
			var self = new BX.CrmProdoctRowListDragContainer();
			self.initialize(id, settings);
			this.items[self.getId()] = self;
			return self;
		};
}

if (typeof(BX.CrmProductSearchDialogWindow) === "undefined")
{
	BX.CrmProductSearchDialogWindow = function()
	{
		this._settings = {};
		this.popup = null;
		this.random = "";
		this.contentContainer = null;
		this.zIndex = 996;
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
			/*this.contentContainer.style.width = this._settings.width + "px";
			this.contentContainer.style.height = this._settings.height + "px";
			this.contentContainer.style.minWidth = this._settings.minWidth + "px";
			this.contentContainer.style.minHeight= this._settings.minHeight + "px";*/
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
					zIndex: this.zIndex - 1100,
					closeByEsc: true,
					closeIcon: { top: '10px', right: '15px' },
					titleBar: BX.CrmProductEditorMessages["productSearchDialogTitle"],
					events:
					{
						onPopupClose: BX.delegate(this._handleCloseDialog, this),
						onAfterPopupShow: BX.delegate(this._handleAfterShowDialog, this)
					},
					content: this.contentContainer
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
				}
				delete this.eventHandlers[eventName];
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