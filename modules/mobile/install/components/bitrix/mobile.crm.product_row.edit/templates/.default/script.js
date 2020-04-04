if(typeof(BX.CrmProductRowEditor) === "undefined")
{
	BX.CrmProductRowEditor = function()
	{
		this._id = "";
		this._settings = {};
		this._prefix = "";
		this._contextId = "";
		this._data = {};
		this._model = null;
		this._formatResult = null;
		this._refresSumCallbackId = -1;
		this._decrementButton = this._incrementButton = null;
	};

	BX.CrmProductRowEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._prefix = this.getSetting("prefix", "");
			this._contextId = this.getSetting("contextId", "");

			this._model = BX.CrmProductRowModel.create(this.getSetting("data", {}));
			this._initializeData();

			BX.addCustomEvent("onOpenPageAfter", BX.delegate(this._onAfterPageOpen, this));

			var decrementButton = this.resolveElement("quantity_decrement");
			if(decrementButton)
			{
				this._decrementButton = new FastButton(decrementButton, BX.delegate(this._onQuantityDecrementButtonClick, this), false);
				//BX.bind(decrementButton, "click", BX.delegate(this._onQuantityDecrementButtonClick, this));
			}

			var incrementButton = this.resolveElement("quantity_increment");
			if(incrementButton)
			{
				this._incrementButton = new FastButton(incrementButton, BX.delegate(this._onQuantityIncrementButtonClick, this), false);
				//BX.bind(incrementButton, "click", BX.delegate(this._onQuantityIncrementButtonClick, this));
			}

			var quantity = this.resolveElement("quantity");
			if(quantity)
			{
				BX.bind(quantity, "change", BX.delegate(this._onQuantityChange, this));
			}

			var price = this.resolveElement("price");
			if(price)
			{
				BX.bind(price, "change", BX.delegate(this._onPriceChange, this));
			}
		},
		getSetting: function(name, defaultVal)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
		},
		prepareElementId: function(name)
		{
			name = name.toLowerCase();
			return this._prefix !== ''
					? (this._prefix + '_' + name) : name;
		},
		resolveElement: function(name)
		{
			return BX(this.prepareElementId(name));
		},
		reset: function()
		{
			this._initializeData();

			var productName = this.resolveElement("product_name");
			if(productName)
			{
				productName.innerHTML = BX.util.htmlspecialchars(this._data["PRODUCT_NAME"]);
			}

			var quantity = this.resolveElement("quantity");
			if(quantity)
			{
				quantity.value = this._data["QUANTITY"];
			}

			var price = this.resolveElement("price");
			if(price)
			{
				price.value = this._data["PRICE"];
			}

			var formattedSum = this.resolveElement("formatted_sum");
			if(formattedSum)
			{
				formattedSum.innerHTML = this._data["FORMATTED_SUM"];
			}
		},
		initializeFromExternalData: function()
		{
			var self = this;
			BX.CrmMobileContext.getCurrent().getPageParams(
				{
					callback: function(data)
					{
						if(data)
						{
							self._contextId = BX.type.isNotEmptyString(data["contextId"]) ? data["contextId"] : "";
							self._model = BX.CrmProductRowModel.create(typeof(data["modelData"]) !== "undefined" ? data["modelData"] : {});
							self.reset();
						}
					}
				}
			);
		},
		createSaveHandler: function()
		{
			return BX.delegate(this._onSave, this);
		},
		_initializeData: function()
		{
			var m = this._model;
			this._data =
			{
				"PRODUCT_ID": m.getIntParam("PRODUCT_ID"),
				"PRODUCT_NAME": m.getStringParam("PRODUCT_NAME"),
				"CURRENCY_ID": this._model.getStringParam("CURRENCY_ID"),
				"QUANTITY": m.getIntParam("QUANTITY"),
				"PRICE": m.getFloatParam("PRICE"),
				"FORMATTED_PRICE": m.getStringParam("FORMATTED_PRICE"),
				"SUM": m.getIntParam("QUANTITY") * m.getFloatParam("PRICE"),
				"FORMATTED_SUM": this._model.getStringParam("FORMATTED_SUM")
			};

			this._formatResult =
			{
				"QUANTITY": this._data["QUANTITY"],
				"PRICE": this._data["PRICE"],
				"CURRENCY_ID": this._data["CURRENCY_ID"],
				"FORMATTED_PRICE": this._data["FORMATTED_PRICE"],
				"FORMATTED_SUM": this._data["FORMATTED_SUM"]
			}
		},
		_onSave: function()
		{
			this._synchonizeQuantity()
			this._synchonizePrice();

			if(this._formatResult)
			{
				if(this._data["QUANTITY"] !== parseInt(this._formatResult["QUANTITY"])
					|| this._data["PRICE"] !== parseFloat(this._formatResult["PRICE"]))
				{
					this._refreshSum(BX.delegate(this._notify, this), true);
					return;
				}
			}

			this._notify();
		},
		_notify: function()
		{
			var context = BX.CrmMobileContext.getCurrent();
			var eventArgs =
			{
				modelData: this._data,
				contextId: this._contextId
			};

			context.riseEvent("onCrmProductRowEditComplete", eventArgs, 2);
			window.setTimeout(context.createBackHandler(), 0);
		},
		_refreshSum: function(callback, showWait)
		{
			this._refresSumCallbackId = -1;

			showWait = !!showWait;
			if(showWait)
			{
				BX.CrmMobileContext.getCurrent().showWait()
			}

			var self = this;
			BX.ajax(
				{
					url: this.getSetting("serviceUrl", ""),
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION" : "FORMAT_MONEY",
						"QUANTITY": this._data["QUANTITY"],
						"PRICE": this._data["PRICE"],
						"CURRENCY_ID": this._data["CURRENCY_ID"]
					},
					onsuccess: function(data)
					{
						self._formatResult = data;
						self._data["FORMATTED_PRICE"] = BX.type.isNotEmptyString(data["FORMATTED_PRICE"]) ? data["FORMATTED_PRICE"] : "";
						self._data["FORMATTED_SUM"] = BX.type.isNotEmptyString(data["FORMATTED_SUM"]) ? data["FORMATTED_SUM"] : "";
						var elem = self.resolveElement("formatted_sum");
						if(elem)
						{
							elem.innerHTML = self._data["FORMATTED_SUM"];
						}

						if(showWait)
						{
							BX.CrmMobileContext.getCurrent().hideWait()
						}

						if(BX.type.isFunction(callback))
						{
							callback();
						}
					},
					onfailure: function(data)
					{
						if(showWait)
						{
							BX.CrmMobileContext.getCurrent().hideWait()
						}

						if(BX.type.isFunction(callback))
						{
							callback();
						}
					}
				}
			);
		},
		_synchonizePrice: function()
		{
			var price = this.resolveElement("price");
			if(!price)
			{
				return;
			}

			var v = price.value;
			v = v.replace(/,/g, ".").replace(/[^0-9\.]/g, "");
			v = parseFloat(v);

			if(isNaN(v) || v < 0)
			{
				v = 0;
			}
			price.value = v;
			this._data["PRICE"] = v;
			this._data["SUM"] =  this._data["PRICE"] * this._data["QUANTITY"];
		},
		_synchonizeQuantity: function()
		{
			var quantity = this.resolveElement("quantity");
			if(!quantity)
			{
				return;
			}

			var v = quantity.value;
			v = v.replace(/[^0-9]/g, "");
			v = parseInt(v);

			if(isNaN(v) || v < 0)
			{
				v = 0;
			}

			quantity.value = v;
			this._data["QUANTITY"] = v;
			this._data["SUM"] =  this._data["PRICE"] * this._data["QUANTITY"];
		},
		_onQuantityDecrementButtonClick: function(e)
		{
			if(this._refresSumCallbackId > 0)
			{
				window.clearTimeout(this._refresSumCallbackId);
				this._refresSumCallbackId = -1;
			}

			var v = this._data["QUANTITY"];
			if(v <= 0)
			{
				return;
			}

			this._data["QUANTITY"] = --v;

			var quantity = this.resolveElement("quantity");
			if(quantity)
			{
				quantity.value = v;
			}

			this._refresSumCallbackId = window.setTimeout(BX.delegate(this._refreshSum, this), 450);
		},
		_onQuantityIncrementButtonClick: function(e)
		{
			if(this._refresSumCallbackId > 0)
			{
				window.clearTimeout(this._refresSumCallbackId);
				this._refresSumCallbackId = -1;
			}

			var v = this._data["QUANTITY"];
			this._data["QUANTITY"] = ++v;
			var quantity = this.resolveElement("quantity");
			if(quantity)
			{
				quantity.value = v;
			}

			this._refresSumCallbackId = window.setTimeout(BX.delegate(this._refreshSum, this), 450);
		},
		_onQuantityChange: function(e)
		{
			this._synchonizeQuantity();
			this._refreshSum();
		},
		_onPriceChange: function(e)
		{
			this._synchonizePrice();
			this._refreshSum();
		},
		_onAfterPageOpen: function()
		{
			this.initializeFromExternalData();
		}
	};

	BX.CrmProductRowEditor.create = function(id, settings)
	{
		var self = new BX.CrmProductRowEditor();
		self.initialize(id, settings);
		return self;
	}
}
