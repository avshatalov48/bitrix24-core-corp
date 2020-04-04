BX.namespace('BX.SalesCenter.Component');

if(typeof(BX.SalesCenter.Component.PaymentPayBase) === "undefined")
{
	BX.SalesCenter.Component.PaymentPayBase = function()
	{
		this._id = "";
		this._settings = {};
		this._container = null;
		this._wrapper = null;
		this._url = '';
		this._paysystems = [];
	};
	BX.SalesCenter.Component.PaymentPayBase.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};
			this._isViewMode = this.getSetting('viewMode');
			this._container = BX(this.getSetting('containerId'));
			this._url = this.getSetting('url') || '';
			this._isAllowedSubmitting = (BX.UserConsent === undefined) || this.getSetting('isAllowedSubmitting', false);
			if (!this._container)
				return null;

			if (BX.UserConsent !== undefined)
			{
				var control = BX.UserConsent.load(this._container);
				BX.addCustomEvent(
					control,
					BX.UserConsent.events.accepted,
					function ()
					{
						this._isAllowedSubmitting = true;
					}.bind(this)
				);
				BX.addCustomEvent(
					control,
					BX.UserConsent.events.refused,
					function () {
						this._isAllowedSubmitting = false;
					}.bind(this)
				);
			}

			var paySystemData = BX.prop.getArray(settings, 'paySystemData', []);
			var selectedItemId = BX.prop.getInteger(settings, 'selectedPaySystemId', 0);
			for (var i=0; i < paySystemData.length; i++)
			{
				var fields = paySystemData[i];
				var paySystemElement = BX.SalesCenter.Component.PaySystemItem.create(fields.ID, {
					parent: this,
					fields: fields
				});
				if (parseInt(fields.ID) === selectedItemId)
				{
					paySystemElement.changeSelection(true);
				}
				this._paysystems.push(paySystemElement);
			}

			this.layout();

			if (!this._isViewMode)
			{
				var button = this._container.querySelector(this.getSetting('submitButtonSelector'));
				if (BX.type.isDomNode(button))
				{
					BX.bind(button, 'click', this.submit.bind(this));
				}
			}

			BX.addCustomEvent('onPaySystemAjaxError', BX.proxy(this.addReloadPageButton, this));
		},
		addReloadPageButton: function()
		{
			var resultDiv = BX.create('div', {
				props: {
					className: 'order-payment-buttons-container'
				},
				children: [
					BX.create('button', {
						text: BX.message('SPP_PAY_RELOAD_BUTTON'),
						props: {
							className: 'landing-block-node-button text-uppercase btn btn-lg btn-primary g-font-weight-700 g-font-size-12 g-rounded-50 pl-4 pr-4 pt-3 pb-3'
						},
						events: {
							click: BX.delegate(function(e){
								window.location.reload();
							}, this)
						}
					})
				]
			});

			this._container.appendChild(resultDiv);
		},
		getId: function()
		{
			return this._id;
		},
		layout: function()
		{
		},
		selectItem: function(selected)
		{
		},
		getSelectedItem: function()
		{
			for (var i=0; i < this._paysystems.length; i++)
			{
				var item = this._paysystems[i];
				if (item.isSelected())
				{
					return item;
				}
			}
			return null;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		submit: function(e)
		{
			var selected = this.getSelectedItem();

			BX.onCustomEvent(this.getSetting('consentEventName'), []);

			if (!selected || !this._url || !this._isAllowedSubmitting)
				return false;

			e.target.classList.add('ui-btn-clock');
			this._isAllowedSubmitting = false;

			var url = this._url;
			BX.ajax(
				{
					method: 'POST',
					dataType: 'json',
					url: url,
					data:
						{
							sessid: BX.bitrix_sessid(),
							paysystemId: selected.getId(),
							signedParameters: this.getSetting('signedParameters')
						},
					onsuccess: BX.proxy(this.onAfterPay, this)
				}, this
			);
		},
		onAfterPay: function(result)
		{
			if (!BX.type.isObject(result) || result.status === 'error')
			{
				var resultDiv = document.createElement('div');
				resultDiv.innerHTML = BX.message("SPP_INITIATE_PAY_ERROR_TEXT");
				resultDiv.classList.add("alert");
				resultDiv.classList.add("alert-danger");
				this._container.innerHTML = '';
				this._container.appendChild(resultDiv);
				this.addReloadPageButton();
			}
			else
			{
				var html = BX.type.isString(result.html) ? result.html : '';
				if (html.length === 0)
				{
					this._container.innerHTML = '';
					var fields = BX.prop.getObject(result, 'fields');
					var successMessage = BX.create('div',{
						props: {className: 'alert alert-success'},
						children: [
							BX.create('div',{
								props: {className: 'mb-4'},
								html: '<b>' + BX.message("SPP_EMPTY_TEMPLATE_TITLE") + '</b>'
							}),
							BX.create('div',{
								html: BX.message("SPP_EMPTY_TEMPLATE_SUM_WITH_CURRENCY_FIELD") + " <b>" +  BX.prop.getString(fields, 'SUM_WITH_CURRENCY') + '</b>'
							}),
							BX.create('div',{
								props: {className: 'mb-4'},
								html: BX.message("SPP_EMPTY_TEMPLATE_PAY_SYSTEM_NAME_FIELD") + " <b>" +  BX.prop.getString(fields, 'PAY_SYSTEM_NAME') + '</b>'
							}),

							BX.create('div',{
								html:'<b>' +  BX.message("SPP_EMPTY_TEMPLATE_FOOTER") + '</b>'
							}),
						]
					});
					this._container.appendChild(successMessage);
					this.addReloadPageButton();
				}
				else
				{
					BX.html(this._container, html);
				}
			}
		}
	};
	BX.SalesCenter.Component.PaymentPayBase.instances = {};
	BX.SalesCenter.Component.PaymentPayBase.create = function(id, settings)
	{
		var self = new BX.SalesCenter.Component.PaymentPayBase;
		self.initialize(id, settings);
		this.instances[self.getId()] = self;
		return self;
	}
}

if(typeof(BX.SalesCenter.Component.PaymentPayList) === "undefined")
{
	BX.SalesCenter.Component.PaymentPayList = function()
	{
		BX.SalesCenter.Component.PaymentPayList.superclass.constructor.apply(this);
	};
	BX.extend(BX.SalesCenter.Component.PaymentPayList, BX.SalesCenter.Component.PaymentPayBase);

	BX.SalesCenter.Component.PaymentPayList.prototype.layout = function()
	{
		this._wrapper = this._container.querySelector(this.getSetting('paySystemBlockSelector'));
		if (!BX.type.isDomNode(this._wrapper))
		{
			return null;
		}

		for (var i=0; i < this._paysystems.length; i++)
		{
			var paysystem = this._paysystems[i];
			this._wrapper.appendChild(paysystem.getWrapper());
		}

		this.layoutDescription();
	};

	BX.SalesCenter.Component.PaymentPayList.prototype.layoutDescription = function ()
	{
		if (!this._container)
			return null;

		var block = this._container.querySelector(this.getSetting('descriptionBlockSelector'));
		if (BX.type.isDomNode())
		{
			return null;
		}

		var selected = this.getSelectedItem();
		block.innerHTML = '';
		if (selected)
		{
			block.appendChild(BX.create('DIV',
				{
					props: {className: 'order-payment-method-description-title'},
					text: selected.getName()
				}
			));
			block.appendChild(BX.create('DIV',
				{
					props: {className: 'order-payment-method-description-text'},
					html: BX.util.htmlspecialchars(selected.getDescription())
				}
			));
		}
	};

	BX.SalesCenter.Component.PaymentPayList.prototype.selectItem = function(selected)
	{
		var newId = selected.getId();
		for (var i=0; i < this._paysystems.length; i++)
		{
			var item = this._paysystems[i];
			if (item.getId() === newId)
			{
				item.changeSelection(true);
			}
			else if (item.isSelected())
			{
				item.changeSelection(false);
			}
		}

		this.layoutDescription();
		return null;
	};

	BX.SalesCenter.Component.PaymentPayList.instances = {};
	BX.SalesCenter.Component.PaymentPayList.create = function(id, settings)
	{
		var self = new BX.SalesCenter.Component.PaymentPayList;
		self.initialize(id, settings);
		this.instances[self.getId()] = self;
		return self;
	}
}

if(typeof(BX.SalesCenter.Component.PaymentPayInner) === "undefined")
{
	BX.SalesCenter.Component.PaymentPayInner = function()
	{
		BX.SalesCenter.Component.PaymentPayInner.superclass.constructor.apply(this);
	};
	BX.extend(BX.SalesCenter.Component.PaymentPayInner, BX.SalesCenter.Component.PaymentPayBase);

	BX.SalesCenter.Component.PaymentPayInner.instances = {};
	BX.SalesCenter.Component.PaymentPayInner.create = function(id, settings)
	{
		var self = new BX.SalesCenter.Component.PaymentPayInner;
		self.initialize(id, settings);
		this.instances[self.getId()] = self;
		return self;
	}
}

if(typeof(BX.SalesCenter.Component.PaySystemItem) === "undefined")
{
	BX.SalesCenter.Component.PaySystemItem = function()
	{
		this._id = "";
		this._settings = {};
		this._parent = {};
		this._fields = [];
		this._selected = false;
		this._wrapper = null;
	};
	BX.SalesCenter.Component.PaySystemItem.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};
				this._parent = this.getSetting('parent');
				this._fields = this.getSetting('fields');
				this._wrapper = this.layout();
			},
			getId: function()
			{
				return this._id;
			},
			layout: function()
			{
				return BX.create('div',{
					props: {className: 'order-payment-method-item'},
					children:[
						BX.create('div',{
							props: {
								className: 'order-payment-method-item-block',
								style: "background-image: url('"+BX.util.htmlspecialchars(this._fields['LOGOTIP'])+"');"
							},

						}),
						BX.create('div',{
							props: {className: 'order-payment-method-item-name'},
							text: this.getName()
						}),
					],
					events: { "click": this.onClick.bind(this) }
				});
			},
			onClick: function()
			{
				if (!this._parent)
					return null;

				this._parent.selectItem(this);
			},
			getWrapper: function()
			{
				return this._wrapper;
			},
			getName: function()
			{
				return this._fields['NAME'];
			},
			getDescription: function()
			{
				return this._fields['DESCRIPTION'];
			},
			isSelected: function()
			{
				return this._selected;
			},
			changeSelection: function(value)
			{
				this._selected = value;
				if (value)
				{
					this._wrapper.classList += ' selected';
				}
				else
				{
					BX.removeClass(this._wrapper, ' selected');
				}
			},
			getSetting: function (name, defaultval)
			{
				return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
			}
		};
	BX.SalesCenter.Component.PaySystemItem.instances = {};
	BX.SalesCenter.Component.PaySystemItem.create = function(id, settings)
	{
		var self = new BX.SalesCenter.Component.PaySystemItem;
		self.initialize(id, settings);
		this.instances[self.getId()] = self;
		return self;
	}
}

