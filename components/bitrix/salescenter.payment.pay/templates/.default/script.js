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
		this._paySystemId = null;
		this._allowPaymentRedirect = null;
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
			this._paySystemId = BX.prop.getInteger(settings, 'paySystemId', 0);
			this._allowPaymentRedirect = BX.prop.getBoolean(settings, 'allowPaymentRedirect', true);
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
			for (var i=0; i < paySystemData.length; i++)
			{
				var fields = paySystemData[i];
				var paySystemElement = BX.SalesCenter.Component.PaySystemItem.create(fields.ID, {
					parent: this,
					fields: fields
				});
				if (BX.type.isDomNode(paySystemElement.getWrapper()) && !this._isViewMode)
				{
					BX.bind(paySystemElement.getWrapper(), 'click', this.submit.bind(this));
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

			BX.addCustomEvent('onPaySystemAjaxError', BX.proxy(this.showPaymentError, this));
			BX.addCustomEvent('onPaySystemUpdateTemplate', BX.proxy(this.autoSubmit, this));
		},
		addReloadPageButton: function()
		{
			var resultDiv = BX.create('div', {
				props: {
					className: 'order-payment-buttons-container'
				},
				children: [
					BX.create('button', {
						text: BX.message('SPP_PAY_RELOAD_BUTTON_NEW'),
						props: {
							className: 'order-payment-button-reload'
						},
						events: {
							click: BX.delegate(function(e){
								e.target.disabled = true;
								window.location.reload();
							}, this)
						}
					})
				]
			});

			this._container.appendChild(resultDiv);
		},
		autoSubmit: function ()
		{
			var handlerForm = this._container.querySelector('form'),
				canAutoSubmit = true, element, i,
				autoSubmitTypes = ['hidden', 'submit'];

			if (handlerForm)
			{
				for (i = 0; i < handlerForm.elements.length; i++)
				{
					element = handlerForm.elements[i];
					if (element instanceof HTMLInputElement)
					{
						if (autoSubmitTypes.indexOf(element.type) === -1)
						{
							canAutoSubmit = false;
							break;
						}
					}
				}

				if (canAutoSubmit)
				{
					HTMLFormElement.prototype.submit.call(handlerForm);
				}
			}
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
			var selected = this.getSelectedItem(),
				paysystemId;

			BX.onCustomEvent(this.getSetting('consentEventName'), []);

			paysystemId = Number.parseInt((selected) ? selected.getId() : this._paySystemId);
			if (!paysystemId || !this._url || !this._isAllowedSubmitting)
			{
				if (selected)
				{
					selected.changeSelection(false);
				}

				return false;
			}

			e.target.disabled = true;
			this._isAllowedSubmitting = false;

			var payButton = selected._wrapper.querySelector('.order-payment-method-item-button');
			this.showLoader(payButton);

			var url = this._url;
			BX.ajax(
				{
					method: 'POST',
					dataType: 'json',
					url: url,
					data:
						{
							sessid: BX.bitrix_sessid(),
							paysystemId: paysystemId,
							returnUrl: this.getSetting('returnUrl'),
							signedParameters: this.getSetting('signedParameters'),
						},
					onsuccess: BX.proxy(this.onAfterPay, this)
				}, this
			);
		},
		onAfterPay: function(result)
		{
			if (!BX.type.isObject(result) || result.status === 'error')
			{
				this.showPaymentError(result.errors);
			}
			else
			{
				var url = BX.type.isString(result.url) ? result.url : '',
					html = BX.type.isString(result.html) ? result.html : '';

				if (url && this._allowPaymentRedirect)
				{
					window.location.href = url;
				}
				else
				{
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
						BX.html(this._container, html).then(function(){
							this.addReloadPageButton()

							if (this._allowPaymentRedirect)
							{
								this.autoSubmit();
							}
						}.bind(this));
					}
				}
			}
		},

		showPaymentError: function(errors)
		{
			var errorsList = [
				BX.message('SPP_INITIATE_PAY_ERROR_TEXT_HEADER'),
			];
			if (errors)
			{
				for (var errorCode in errors)
				{
					if (errors.hasOwnProperty(errorCode))
					{
						errorsList.push(errors[errorCode]);
					}
				}
			}

			errorsList.push(BX.message('SPP_INITIATE_PAY_ERROR_TEXT_FOOTER'));

			var resultDiv = document.createElement('div');
			resultDiv.innerHTML = errorsList.join('<br />');
			resultDiv.classList.add("alert");
			resultDiv.classList.add("alert-danger");
			this._container.innerHTML = '';
			this._container.appendChild(resultDiv);

			this.addReloadPageButton();
		},

		showLoader: function(payButton)
		{
			payButton.classList.add('order-payment-loader');
			payButton.innerHTML = '';

			(new BX.Loader({
				target: payButton,
				size: 24,
				color: '#fff',
				mode: 'inline'
			})).show();
		},
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

		if (this._isViewMode)
		{
			this.layoutDescription();
		}
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
					html: selected.getDescription()
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

		if (this._isViewMode)
		{
			this.layoutDescription();
		}
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
		this._paySystemId = null;
	};
	BX.SalesCenter.Component.PaySystemItem.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};
				this._parent = this.getSetting('parent');
				this._isViewMode = this._parent.getSetting('viewMode');
				this._fields = this.getSetting('fields');
				this._wrapper = this.layout();
				this._paySystemId = BX.prop.getInteger(settings, 'paySystemId', 0);
			},
			getId: function()
			{
				return this._id;
			},
			layout: function()
			{
				var additionalClass = this._isViewMode ? 'info-mode' : 'pay-mode';
				var logoBlock;

				if (this._fields['LOGOTIP'])
				{
					logoBlock = BX.create('img', {
						props: {className: 'order-payment-method-item-img'},
						attrs: {
							src: BX.util.htmlspecialchars(this._fields['LOGOTIP'])
						}
					});
				}
				else
				{
					var paySystemName = this.getName();
					if (paySystemName.length > 20)
					{
						paySystemName = paySystemName.substring(0, 17) + '...';
					}
					logoBlock = BX.create('div', {
						props: { className: 'order-payment-pay-system-name' },
						text: BX.util.htmlspecialchars(paySystemName)
					});
				}

				return BX.create('div', {
					props: {
						className: 'order-payment-method-item-block ' + additionalClass,
					},
					children: [
						logoBlock,
						BX.create('div', {
							props: {className: 'order-payment-method-item-button ' + additionalClass},
							text: this._isViewMode ? BX.message('SPP_INFO_BUTTON') : BX.message('SPP_PAY_BUTTON')
						}),
					],
					events: {"click": this.onClick.bind(this)}
				});
			},
			onClick: function ()
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

