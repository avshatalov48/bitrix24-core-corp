BX.namespace("BX.Mobile.Crm.ProductEditor");
BX.Mobile.Crm.ProductEditor = {
	products: [],
	productsContainerNode: "",
	isEditMode: false,
	onProductSelectEventName: "",
	ajaxUrl: "",
	_settings: {},
	_currencyId: "",
	_currencyFormat: '# ?',
	_locationID: 0,

	init: function(params)
	{
		BX.CrmDiscountType =
		{
			undefined: 0,
			monetary: 1,
			percentage: 2
		};

		if (typeof params === "object")
		{
			this.products = params.products || [];
			this.productsContainerNode = params.productsContainerNode || "";
			this.onProductSelectEventName = params.onProductSelectEventName || "";
			this.ajaxUrl = params.ajaxUrl || "";
			this._settings = params.settings || {};
			this.isEditMode = params.isEditMode == "Y" ? true : false;

			this._currencyId = this.getSetting('currencyID', '');
			this._currencyFormat = this.getSetting('currencyFormat', '# ?');
		}

		// location
		this._locationID = this.isLDTaxAllowed() ? this.getSetting("locationID", 0) : 0;
		if (this.isLDTaxAllowed())
			BX.addCustomEvent("CrmProductRowSetLocation", BX.delegate(this._handleChangeLocation, this));

		this._clientTypeName = this.getSetting('clientTypeName', '');
		BX.addCustomEvent(
			"CrmEntitySelectorChangeValue",
			BX.delegate(this._handleEntitySelectorChangeValue, this)
		);

		if (this.products)
		{
			for(var i=0; i<this.products.length; i++)
			{
				if (!this.products[i].DATA_ROLE)
					this.products[i].DATA_ROLE = this.products[i].ID;

				this.products[i].FORMATTED_PRICE = this._currencyFormat.replace(/(^|[^&])#/g, this.products[i].PRICE);

				this.generateProductHtml(this.products[i]);
			}
		}

		if (this.isEditMode)
		{
			BXMobileApp.addCustomEvent(this.onProductSelectEventName, BX.proxy(function (data) {
				this.addProduct(data);
			}, this));
		}
	},

	addProduct: function(product)
	{
		if (typeof product !== "object")
			return;

		if (product.PRODUCT_ID)
		{
			product.QUANTITY = 1;
			product.DATA_ROLE = product.ID + "_" + Math.random();

			this.products.push(product);
			this.generateProductHtml(product);

			this.calculateTotals();
		}
	},

	generateProductHtml: function(product)
	{
		var childrenNodes = [];
		if (this.isEditMode)
		{
			childrenNodes.push(
				BX.create("i", {
					attrs: {className: "crm-mobile-product-view-block-menu-container"},
					children: [
						BX.create("span", {
							attrs: {className: "crm-mobile-product-view-block-menu"}
						})
					],
					events: {
						"click": function () {
							BX.Mobile.Crm.ProductEditor.showProductActionMenu(this);
						}
					}
				})
			);
		}

		if (product.hasOwnProperty('PRICE_NETTO') || product.hasOwnProperty('PRICE_BRUTTO'))
		{
			var taxAllowed = this.isTaxAllowed();
			var taxIncluded = this.isTaxIncluded();

			var curPrice = (taxAllowed && !taxIncluded) ? product.PRICE_NETTO : product.PRICE_BRUTTO;
			product.FORMATTED_PRICE = this._currencyFormat.replace(/(^|[^&])#/g, curPrice);

			var ttl = typeof(product.QUANTITY * curPrice) != 'undefined' ? parseFloat(product.QUANTITY * curPrice).toFixed(2) : '0.00';
		}
		else
		{
			var ttl = typeof(product.QUANTITY * product.PRICE) != 'undefined' ? parseFloat(product.QUANTITY * product.PRICE).toFixed(2) : '0.00';
		}

		var sum =  this._currencyFormat.replace(/(^|[^&])#/g, ttl);

		childrenNodes.push(
			BX.create("span", {
				attrs: {className: "crm-mobile-product-view-block-photo"},
				children: [
					BX.create("img", {
						attrs: {src : "/bitrix/components/bitrix/crm.product_row.list/templates/mobile/images/icon-product.png"}
					})
				]
			}),
			BX.create("span", {
				html: BX.util.htmlspecialchars(product.PRODUCT_NAME),
				attrs: {className: "crm-mobile-product-view-block-name-title"}
			}),
			BX.create("span", {
				html: "<span data-role='productPrice'>" + product.FORMATTED_PRICE +  "</span> * " + "<span data-role='productQuantity'>" + product.QUANTITY + "</span> " + product.MEASURE_NAME,
				attrs: {className: "crm-mobile-product-view-block-col-price"}
			})
		);

		var childrenBlocks = [];

		childrenBlocks.push(
			BX.create("div", {
				attrs: {className: "crm-mobile-product-view-block-container"},
				children: childrenNodes
			})
		);

		if (this.isEditMode) // quantity
		{
			var childrenNodesEdit = [];

			childrenNodesEdit.push(
				BX.create("span", {
					attrs: {"data-role": "sumWithQuantity", className: "crm-mobile-product-view-block-price"},
					html: sum
				}),
				BX.create("span", {
					attrs: {className: "crm-mobile-product-view-block-icon-minus"},
					events: {
						"click": BX.proxy(function () {
							this.changeProductQuantity(product.DATA_ROLE, "decrement")
						}, this)
					}
				}),
				BX.create("input", {
					attrs: {
						"data-role": "productQuantityInput",
						className: "crm-mobile-product-view-block-item-col",
						value: product.QUANTITY,
						name: "productQuantityInput",
						type: "number",
						pattern: "[0-9]*",
						style: "width: 30px; text-align: center;"
					},
					events: {
						keyup : function () {
							this.changeProductQuantity(product.DATA_ROLE, "value");
						}.bind(this)
					}
				}),
				BX.create("span", {
					attrs: {className: "crm-mobile-product-view-block-icon-plus"},
					events: {
						"click": BX.proxy(function () {
							this.changeProductQuantity(product.DATA_ROLE, "increment")
						}, this)
					}
				})
			);

			childrenBlocks.push(
				BX.create("div", {
					attrs: {className: "crm-mobile-product-view-block-controls"},
					children: childrenNodesEdit
				})
			);
		}

		var newProduct = BX.create('div', {
			attrs: {
				className: "crm-mobile-product-view-block",
				"data-role": product.DATA_ROLE
			},
			children: childrenBlocks
		});

		if (this.productsContainerNode)
		{
			this.productsContainerNode.appendChild(newProduct);

			if (this.productsContainerNode.style.display == "none")
			{
				this.productsContainerNode.style.display = "block";
			}
		}
	},

	changeProductQuantity : function(dataRole, type)
	{
		if (type !== "increment" && type !== "decrement" && type !== "value")
			return;

		var productNode = this.productsContainerNode.querySelector("[data-role='"+ dataRole +"']");
		var quantityInput = productNode.querySelector("[data-role='productQuantityInput']");

		if (typeof quantityInput === "object")
		{
			var curQuantity = quantityInput.value;

			if (type == "decrement" && curQuantity > 1)
				curQuantity--;
			else if (type == "increment")
				curQuantity++;
			else if (type == "value" && curQuantity == 0)
				return;

			var quantityNode = productNode.querySelector("[data-role='productQuantity']");
			if (typeof quantityNode === "object")
				quantityNode.innerHTML = curQuantity;

			quantityInput.value = curQuantity;

			for (i=0; i<this.products.length; i++)
			{
				if (this.products[i].DATA_ROLE == dataRole)
				{
					this.products[i].QUANTITY = curQuantity;

					if (this.products[i].PRICE)
					{
						var sumWithQuantityNode = productNode.querySelector("[data-role='sumWithQuantity']");
						if (sumWithQuantityNode)
						{
							var ttl = typeof(curQuantity * this.products[i].PRICE) != 'undefined' ? parseFloat(curQuantity * this.products[i].PRICE).toFixed(2) : '0.00';
							var sum =  this._currencyFormat.replace(/(^|[^&])#/g, ttl);

							sumWithQuantityNode.innerHTML = sum;
						}
					}
					break;
				}
			}

			this.calculateTotals();
		}
	},

	showProductActionMenu : function(element)
	{
		new BXMobileApp.UI.ActionSheet({
				buttons: [
					/*{
						title: BX.message("CRM_JS_EDIT"),
						callback: function()
						{
							BX.Mobile.Crm.ProductEditor.editProduct(element);
						}
					},*/
					{
						title: BX.message("CRM_JS_DELETE"),
						callback: function()
						{
							BX.Mobile.Crm.ProductEditor.deleteProduct(element.parentNode.parentNode);
						}
					}
				]
			}, 'actionSheetStatus'
		).show();
	},

	deleteProduct : function(productContainer)
	{
		var dataRole = productContainer.getAttribute("data-role");
		for(var i=0; i<this.products.length; i++)
		{
			if (this.products[i].DATA_ROLE == dataRole)
			{
				this.products.splice(i,1);
				break;
			}
		}
		if (this.products.length == 0)
		{
			this.productsContainerNode.style.display = "none";
		}

		BX.remove(productContainer);

		this.calculateTotals();
	},

//======= calculate
	getSetting:function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	setSetting:function(name, value)
	{
		this._settings[name] = value;
	},

	calculateTotals: function()
	{
		/*var productData = [];
		for(var i = 0; i < this.products.length; i++)
		{
			var product = this.products[i];
			//product.saveSettings();

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
		}*/

		BX.ajax({
			'url': this.ajaxUrl,
			'method': 'POST',
			'dataType': 'json',
			'data':
			{
				'MODE': 'CALCULATE_TOTALS',
				'OWNER_TYPE': this.getSetting('ownerType', ''),
				'OWNER_ID': this.getSetting('ownerID', 0),
				'PRODUCTS': this.products,//productData,
				'CURRENCY_ID': this._currencyId,
				'CLIENT_TYPE_NAME': this.getClientTypeName(),
				'SITE_ID': this.getSetting('siteId', ''),
				'LOCATION_ID': this._locationID,
				'ALLOW_LD_TAX': this.isLDTaxAllowed() ? 'Y' : 'N',
				'LD_TAX_PRECISION': this.getSetting('taxListPercentPrecision', 2)
			},
			onsuccess: BX.delegate(this._onCalculateTotalsRequestSuccess, this),
			onfailure: BX.delegate(this._onCalculateTotalsRequestFailure, this)
		});
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
	_handleEntitySelectorChangeValue: function (/*id,*/ type, value)
	{
		if (type !== "COMPANY" && type !== "CONTACT")
			return;

		var curType = this.getClientTypeName();
		var newType = curType;

		if (curType === "COMPANY")
		{
			if (type === "COMPANY" && value == 0)
				newType = "CONTACT";
		}
		else
		{
			if (type === "COMPANY" && value > 0)
				newType = "COMPANY";
			else
				newType = "CONTACT";
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
	_onCalculateTotalsRequestSuccess: function(data)
	{
		/*if(this._processAjaxError(data))
		{
			return;
		}*/

		if (!typeof data === 'object')
			return;

		if(typeof(data['TOTALS']) != 'undefined')
		{
			this.refreshTotals(data['TOTALS'])
		}

		if (typeof(data['LD_TAXES']) != 'undefined')
		{
			this.setSetting('LDTaxes', data['LD_TAXES']);
			this.refreshTaxList(data['LD_TAXES']);
		}
	},
	refreshTotals: function(totals)
	{
		var ttl, s, el;

		el = BX(this.getSetting('TOTAL_BEFORE_DISCOUNT_ID', 'total_before_discount'));
		if(el)
		{
			s = BX.type.isNotEmptyString(totals['TOTAL_BEFORE_DISCOUNT_FORMATTED']) ? totals['TOTAL_BEFORE_DISCOUNT_FORMATTED'] : '';
			ttl = typeof(totals['TOTAL_BEFORE_DISCOUNT']) != 'undefined' ? parseFloat(totals['TOTAL_BEFORE_DISCOUNT']).toFixed(2) : '0.00';
			el.innerHTML = s !== '' ? s : this._currencyFormat.replace(/(^|[^&])#/g, ttl);
			//BX.onCustomEvent(this, 'totalBeforeDiscountChange', [ttl]);
		}

		el = BX(this.getSetting('TOTAL_DISCOUNT_ID', 'total_discount'));
		if(el)
		{
			s = BX.type.isNotEmptyString(totals['TOTAL_DISCOUNT_FORMATTED']) ? totals['TOTAL_DISCOUNT_FORMATTED'] : '';
			ttl = typeof(totals['TOTAL_DISCOUNT']) != 'undefined' ? parseFloat(totals['TOTAL_DISCOUNT']).toFixed(2) : '0.00';
			this._discountExists = (parseFloat(ttl) !== 0.0);
			el.innerHTML = s !== '' ? s : this._currencyFormat.replace(/(^|[^&])#/g, ttl);
			//BX.onCustomEvent(this, 'totalDiscountChange', [ttl]);
		}

		el = BX(this.getSetting('TOTAL_BEFORE_TAX_ID', 'total_before_tax'));
		if(el)
		{
			s = BX.type.isNotEmptyString(totals['TOTAL_BEFORE_TAX_FORMATTED']) ? totals['TOTAL_BEFORE_TAX_FORMATTED'] : '';
			ttl = typeof(totals['TOTAL_BEFORE_TAX']) != 'undefined' ? parseFloat(totals['TOTAL_BEFORE_TAX']).toFixed(2) : '0.00';
			el.innerHTML = s !== '' ? s : this._currencyFormat.replace(/(^|[^&])#/g, ttl);
			//BX.onCustomEvent(this, 'totalBeforeTaxChange', [ttl]);
		}

		el = BX(this.getSetting('taxValueID', 'total_tax'));
		if(el)
		{
			s = BX.type.isNotEmptyString(totals['TOTAL_TAX_FORMATTED']) ? totals['TOTAL_TAX_FORMATTED'] : '';
			ttl = typeof(totals['TOTAL_TAX']) != 'undefined' ? parseFloat(totals['TOTAL_TAX']).toFixed(2) : '0.00';
			this._taxExists = (parseFloat(ttl) !== 0.0);
			el.innerHTML = s !== '' ? s : this._currencyFormat.replace(/(^|[^&])#/g, ttl);
			//BX.onCustomEvent(this, 'totalTaxChange', [ttl]);
		}

		el = BX(this.getSetting('SUM_TOTAL_ID', 'sum_total'));

		if(el)
		{
			s = BX.type.isNotEmptyString(totals['TOTAL_SUM_FORMATTED']) ? totals['TOTAL_SUM_FORMATTED'] : '';
			ttl = typeof(totals['TOTAL_SUM']) != 'undefined' ? parseFloat(totals['TOTAL_SUM']).toFixed(2) : '0.00';
			el.innerHTML = s !== '' ? s : this._currencyFormat.replace(/(^|[^&])#/g, ttl);
			BX.onCustomEvent(this, 'sumTotalChange', [ttl]);
		}

		this.switchTotalElements();
	},

	refreshTaxList: function(taxList)
	{
		var taxList = taxList;
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

	switchTotalElements: function()
	{
		if (BX(this.getSetting("productTotalContainerID", "")))
		{
			if (this.products.length > 0)
				BX(this.getSetting("productTotalContainerID", "")).style.display = 'block';
			else
				BX(this.getSetting("productTotalContainerID", "")).style.display = 'none';
		}

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

	_onCalculateTotalsRequestFailure: function(data)
	{
		BX.Mobile.Crm.showErrorAlert(BX.message("CRM_INVALID_REQUEST_ERROR"));
		//self._processAjaxError(data);
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

	getClientTypeName: function()
	{
		return this._clientTypeName;
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

	setClientTypeName: function(clientTypeName)
	{
		if(this._clientTypeName === clientTypeName)
		{
			return;
		}

		this._clientTypeName = clientTypeName;
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
	//	this._currencyId = currencyId;
//		if (this._settings["productCreateDialogSettings"])
//			this._settings["productCreateDialogSettings"]["ownerCurrencyId"] = currencyId;
		this.calculateProductPrices(currencyId);
	},

	setCurrencyFormat: function(currencyFormat)
	{
		if (typeof(currencyFormat) !== "string" || currencyFormat.length <= 0)
			currencyFormat = "# ?";
		this._currencyFormat = currencyFormat;
	},

	getForm: function()
	{
		var formID = this.getSetting('formID', '');
		return BX.type.isNotEmptyString(formID) ? BX('form_' + formID) : null;
	},

	getExchRateElement: function()
	{
		var form = this.getForm();
		return form ? BX.findChild(form, { 'tag':'input', 'attr':{ 'name': 'EXCH_RATE' } }, true, false) : null;
	},

	calculateProductPrices: function(dstCurrencyId)
	{
		var prevId = this._currencyId;
		this._currencyId = dstCurrencyId;

		var exchRate = this.getExchRateElement();

		var srcData = [];
		var taxAllowed = this.isTaxAllowed();
		var taxIncluded = this.isTaxIncluded();
	 	var discountTypeId;
		for(var i = 0; i < this.products.length; i++)
		{
			this.products[i].PRICE = (taxAllowed && !taxIncluded) ? this.products[i].PRICE_NETTO : this.products[i].PRICE_BRUTTO;
		/*	var p = this.products[i];
			discountTypeId = p.DISCOUNT_TYPE_ID;
		//	taxIncluded = p.isTaxIncluded();
			srcData.push({
				'ID': p.ID,
				'PRICE': (taxAllowed && !taxIncluded) ? p.PRICE_NETTO : p.PRICE_BRUTTO,
				'DISCOUNT_TYPE_ID': p.DISCOUNT_TYPE_ID,
				'DISCOUNT_VALUE': (discountTypeId === BX.CrmDiscountType.percentage) ? p.DISCOUNT_RATE : p.DISCOUNT_SUM
			});*/
		}

		var self = this;
		BX.ajax(
		{
			'url': this.ajaxUrl,
			'method': 'POST',
			'dataType': 'json',
			'data':
			{
				'MODE' : 'CALC_PRODUCT_PRICES',
				'OWNER_TYPE': this.getSetting('ownerType', ''),
				'OWNER_ID': this.getSetting('ownerID', 0),
				'DATA':
				{
					'SRC_CURRENCY_ID': prevId,
					'SRC_EXCH_RATE': exchRate ? parseFloat(exchRate.value) : 0,
					'DST_CURRENCY_ID': dstCurrencyId,
					'PRODUCTS': this.products
				},
				'SITE_ID': this.getSetting('siteId', '')
			},
			onsuccess: BX.proxy(function(data)
			{
				if (typeof data !== 'object')
					return;

				if(data['PRODUCTS'])
				{
					var taxAllowed = this.isTaxAllowed();
					var taxIncluded = this.isTaxIncluded();
					var discountTypeId;
					this.setCurrencyFormat(data['CURRENCY_FORMAT'] ? data['CURRENCY_FORMAT'] : '# ?');

					for(var i = 0; i < data['PRODUCTS'].length; i++)
					{
						var curProduct = data['PRODUCTS'][i];

						var productNode = this.productsContainerNode.querySelector("[data-role='" + data['PRODUCTS'][i].DATA_ROLE + "']");
						if (!productNode)
							break;

						var priceNode = productNode.querySelector("[data-role='productPrice']");

						if (priceNode)
						{
							var newPrice = parseFloat(curProduct.PRICE).toFixed(2);
							data['PRODUCTS'][i][(taxAllowed && !taxIncluded) ? 'PRICE_NETTO' : 'PRICE_BRUTTO'] = newPrice;
							data['PRODUCTS'][i]['PRICE_EXCLUSIVE'] = newPrice;

							var curPrice = (taxAllowed && !taxIncluded) ? curProduct.PRICE_NETTO : curProduct.PRICE_BRUTTO;

							var formatCurPrice = typeof(curPrice) != 'undefined' ? parseFloat(curPrice).toFixed(2) : '0.00';

							priceNode.innerHTML = this._currencyFormat.replace(/(^|[^&])#/g, formatCurPrice);

							var priceWithQuantityNode = productNode.querySelector("[data-role='sumWithQuantity']");
							if (priceWithQuantityNode)
							{
								var formatCurPrice = typeof(curProduct.QUANTITY * curPrice) != 'undefined' ? parseFloat(curProduct.QUANTITY * curPrice).toFixed(2) : '0.00';
								var sum =  this._currencyFormat.replace(/(^|[^&])#/g, formatCurPrice);

								priceWithQuantityNode.innerHTML = sum;
							}
						}
					}

					this.products = data['PRODUCTS'];

					this.calculateTotals();
				}

			}, this),
			onfailure: function(data)
			{
			}
		});
	}
};