;(function ()
{
	'use strict';

	var namespace = BX.namespace('BX.Crm.Tracking.Source');
	if (namespace.Editor)
	{
		return;
	}

	/**
	 * Editor.
	 *
	 */
	function Editor()
	{
		this.context = null;
		this.editor = null;
	}
	Editor.prototype.init = function (params)
	{
		this.context = BX(params.containerId);
		this.isSaved = params.isSaved || false;
		this.componentName = params.componentName || null;
		this.signedParameters = params.signedParameters || null;
		this.pathToExpenses = params.pathToExpenses || null;
		this.provider = params.provider || null;
		this.mess = params.mess || {};

		this.buttonUtmNode = BX('utm-edit-btn');
		BX.bind(this.buttonUtmNode, 'click', this.onEditUtm.bind(this));
		this.buttonUtmNode = BX('utm-add-btn');
		BX.bind(this.buttonUtmNode, 'click', this.onEditUtm.bind(this));
		BX.bind(BX('crm-tracking-expenses'), 'click', this.onEditExpenses.bind(this));

		this.colorPicker = new ColorPicker();
		this.connector = new Connector({manager: this});

		if (BX.UI && BX.UI.TileSelector)
		{
			this.selectorRefDomain = BX.UI.TileSelector.getById('ref-domain');
			if (this.selectorRefDomain)
			{
				BX.addCustomEvent(this.selectorRefDomain, this.selectorRefDomain.events.search, this.onRefDomainSearch.bind(this));
			}

			this.selectorUtmSource = BX.UI.TileSelector.getById('utm-source');
			if (!this.selectorUtmSource)
			{
				throw new Error('Tile selector `utm-source` not found.');
			}
			BX.addCustomEvent(this.selectorUtmSource, this.selectorUtmSource.events.search, this.onUtmSourceSearch.bind(this));
		}


		var utmContentBtn = BX('crm-analytics-source-block-utm-content-btn');
		if (utmContentBtn)
		{
			BX.clipboard.bindCopyClick(utmContentBtn, {text: utmContentBtn.previousElementSibling});
		}
	};
	Editor.prototype.onEditExpenses = function ()
	{
		BX.SidePanel.Instance.open(this.pathToExpenses, {width: 670, cacheable: false});
	};
	Editor.prototype.onEditUtm = function ()
	{
		BX.SidePanel.Instance.open('/crm/tracking/source/utm/', {width: 670, cacheable: false});
	};
	Editor.prototype.onRefDomainSearch = function (value)
	{
		value = (value || '').trim();
		if (window.URL && /^http:|https:/.test(value))
		{
			value = (new URL(value)).hostname || '';
		}

		value.split(',').forEach(function (value){
			value = value.trim();
			if (!value)
			{
				return;
			}

			this.selectorRefDomain.addTile(value, {}, value);
		}, this);
	};
	Editor.prototype.onUtmSourceSearch = function (value)
	{
		value = (value || '').trim();
		if (window.URL && /^http:|https:/.test(value))
		{
			value = (new URL(value)).searchParams.get("utm_source") || '';
		}

		value.replace(/[\s]/g, ',')
			.replace(/[^\w,\-.]/g, '')
			.split(',')
			.forEach(function (value) {
				value = value.trim();
				if (!value)
				{
					return;
				}

				this.selectorUtmSource.addTile(value, {}, value);
			}, this);
	};

	namespace.Editor = new Editor();

	/**
	 * Color picker.
	 *
	 */
	function ColorPicker()
	{
		this.picker = null;
		this.pickerIcon = null;
		this.pickerPopup = null;

		this.init();
	}
	ColorPicker.prototype.init = function()
	{
		this.input = BX("crm-analytics-utm-editor-color-input");
		if (!this.input)
		{
			return;
		}

		this.picker = BX("crm-analytics-utm-editor-color-select");
		this.pickerIcon = BX("crm-analytics-utm-editor-color-icon-value");

		this.setColor(this.input.value);
		BX.bind(this.picker, 'click', this.handlePickerClick.bind(this));
	};
	ColorPicker.prototype.setColor = function(color)
	{
		this.input.value = color;
		this.pickerIcon.style.backgroundColor = color;
	};
	ColorPicker.prototype.handlePickerClick = function()
	{
		if (!this.pickerPopup)
		{
			this.pickerPopup = new BX.ColorPicker({
				bindElement: this.picker,
				onColorSelected: this.setColor.bind(this)
			});
		}

		this.pickerPopup.open();
	};

	/**
	 * Connector.
	 *
	 */
	function Connector(options)
	{
		this.manager = options.manager;

		if (!this.manager.provider || !this.manager.provider.TYPE)
		{
			return;
		}

		this.initUi();
		this.listenSeoAuth();
	}
	Connector.prototype.initUi = function ()
	{
		var context = null;
		this.nodes = {
			blocks: {
				desc: this.getNode('desc', context),
				connect: this.getNode('connect', context),
				connected: this.getNode('connected', context),
				accounts: this.getNode('ad/accounts', context),
				profile: this.getNode('profile', context)
			},
			ad: {
				accounts: {
					view: this.getNode('ad/accounts/view', context),
					data: this.getNode('ad/accounts/data', context)
				},
				client: this.getNode('ad/client', context)
			},
			connect: this.getNode('connect/btn', context),
			disconnect: this.getNode('profile/disconnect', context),
			profile: {
				link: this.getNode('profile/link', context),
				name: this.getNode('profile/name', context),
				pic: this.getNode('profile/pic', context)
			}
		};

		BX.bind(this.nodes.connect, 'click', this.connect.bind(this));
		BX.bind(this.nodes.disconnect, 'click', this.disconnect.bind(this));
		BX.bind(this.nodes.ad.accounts.view, 'change', function () {
			this.nodes.ad.accounts.data.value = this.nodes.ad.accounts.view.value;
		}.bind(this));

		this.initClient();
	};
	Connector.prototype.initClient = function (onlyUpdate)
	{
		var clients = this.manager.provider.CLIENTS;

		if (this.clientSelector)
		{
			this.clientSelector.items = clients;
		}
		else
		{
			this.clientSelector = new BX.Seo.Ads.ClientSelector(this.nodes.blocks.profile, {
				selected: this.manager.provider.PROFILE,
				items: clients,
				canAddItems: true,
				canUnSelectItem: true,
				events: {
					onNewItem: function() {
						BX.Seo.Ads.LoginFactory.getLoginObject(this.manager.provider).login();
					}.bind(this),
					onSelectItem: function(item) {
						this.setProfile(item);
					}.bind(this),
					onUnSelectItem: function() {
						this.setProfile(null);
					}.bind(this),
					onRemoveItem: function(item) {
						this.disconnect(item.CLIENT_ID);
					}.bind(this)
				}
			});
		}

		if (!onlyUpdate)
		{
			this.clientSelector.setSelected(this.manager.provider.PROFILE);
			this.setProfile(this.manager.provider.PROFILE);
		}
		this.clientSelector.enable();
		this.changeAuthDisplay(!clients || clients.length === 0);
	};
	Connector.prototype.setProfile = function (profile)
	{
		if (profile && !profile.CLIENT_ID)
		{
			profile = null;
		}

		this.manager.provider.PROFILE = profile;
		this.nodes.ad.client.value = profile ? profile.CLIENT_ID : '';
		this.requestAccounts();
	};
	Connector.prototype.listenSeoAuth = function ()
	{
		BX.addCustomEvent(
			window,
			'seo-client-auth-result',
			this.onSeoAuth.bind(this)
		);
	};
	Connector.prototype.requestProvider = function()
	{
		this.clientSelector.disable();
		this.request('getProvider', {}, this.updateProvider.bind(this));
	};
	Connector.prototype.updateProvider = function(response)
	{
		var data = response.data || {};
		data.PROFILE = data.PROFILE || null;
		this.manager.provider = data;

		this.initClient();
	};
	Connector.prototype.connect = function ()
	{
		var onCancel = function () {
			if (this.serviceWindow)
			{
				this.serviceWindow.close();
			}
			BX.removeClass(this.nodes.connect, 'ui-btn-wait');
		}.bind(this);

		if (BX.hasClass(this.nodes.connect, 'ui-btn-wait'))
		{
			onCancel();
			return;
		}

		BX.addClass(this.nodes.connect, 'ui-btn-wait');

		/*
		this.serviceWindow = BX.util.popup(this.nodes.connect.href, 800, 600);
		this.serviceWindow.onunload = onCancel;
		*/
	};
	Connector.prototype.disconnect = function (clientId)
	{
		var analyticsLabel =
			!( this.manager.provider.TYPE === "facebook" || this.manager.provider.TYPE === "instagram")
				? {}
				: {
					connect: "FBE",
					action: "disconnect",
					type: "disconnect"
				}
		;
		this.clientSelector.disable();

		this.request(
			'disconnect',
			{clientId: clientId},
			function (response) {
				var profile = this.manager.provider.PROFILE;
				if (profile && profile.CLIENT_ID === clientId)
				{
					this.updateProvider(response);
				}
				else
				{
					this.manager.provider = response.data;
					this.initClient(true);
				}
				this.clientSelector.enable();
			}.bind(this),
			null,
			analyticsLabel
		);
	};
	Connector.prototype.onSeoAuth = function (eventData)
	{
		eventData.reload = false;
		this.requestProvider();
	};
	Connector.prototype.changeAuthDisplay = function(mode)
	{
		this.nodes.blocks.desc.style.display = mode ? '' : 'none';
		this.nodes.blocks.connect.style.display = mode ? '' : 'none';
		this.nodes.blocks.connected.style.display = !mode ? '' : 'none';
		if (this.nodes.blocks.accounts)
		{
			this.nodes.blocks.accounts.style.display = !mode ? '' : 'none';
		}
	};
	Connector.prototype.requestAccounts = function()
	{
		if (!this.manager.provider.HAS_ACCOUNTS)
		{
			return;
		}

		var view = this.nodes.ad.accounts.view;
		view.disabled = true;
		var profile = this.manager.provider.PROFILE;
		if (!profile)
		{
			this.updateAccounts({
				data: []
			});
			view.disabled = true;
			return;
		}

		this.updateAccounts(
			{
				data: [
					{id: '', name: this.manager.mess.loading + '...'}
				]
			},
			true
		);

		this.request(
			'getAccounts',
			{clientId: profile.CLIENT_ID},
			this.updateAccounts.bind(this)
		);
	};
	Connector.prototype.updateAccounts = function(response, doNotUpdateData)
	{
		doNotUpdateData = doNotUpdateData || false;
		var view = this.nodes.ad.accounts.view;
		var data = this.nodes.ad.accounts.data;

		view.innerHTML = '';
		view.disabled = false;
		(response.data || []).forEach(
			function (item) {
				var option = document.createElement("option");
				option.value = item.ID || item.id;
				option.textContent = item.NAME || item.name;
				if (data.value && data.value === option.value)
				{
					option.selected = true;
				}
				view.appendChild(option);
			},
			this
		);
		if (!doNotUpdateData)
		{
			data.value = view.value;
		}
	};
	Connector.prototype.getNode = function(code, context)
	{
		var node = (context || document.body).querySelector('[data-role="crm/tracking/' + code + '"]');
		return node ? node : null;
	};
	Connector.prototype.request = function (action, data, callbackSuccess, callbackFailure, analytics)
	{
		data = data || {};
		data.type = this.manager.provider.TYPE;

		callbackSuccess = callbackSuccess || null;
		callbackFailure = callbackFailure || BX.proxy(this.showErrorPopup, this);
		analytics = analytics || {};

		var self = this;
		BX.ajax.runComponentAction(this.manager.componentName, action, {
			'mode': 'class',
			'signedParameters': this.manager.signedParameters,
			'data': data,
			'analyticsLabel': analytics
		}).then(
			function (response)
			{
				var data = response.data || {};
				if(data.error)
				{
					callbackFailure.apply(self, [data]);
				}
				else if(callbackSuccess)
				{
					callbackSuccess.apply(self, [data]);
				}
			},
			function()
			{
				var data = {'error': true, 'text': ''};
				callbackFailure.apply(self, [data]);
			}
		);
	};
	Connector.prototype.showErrorPopup = function (data)
	{
		data = data || {};
		var text = data.text || this.manager.mess.errorAction;
		var popup = BX.PopupWindowManager.create(
			'crm_ads_rtg_error',
			null,
			{
				autoHide: true,
				lightShadow: true,
				closeByEsc: true,
				overlay: {backgroundColor: 'black', opacity: 500}
			}
		);
		popup.setButtons([
			new BX.PopupWindowButton({
				text: this.manager.mess.dlgBtnClose,
				events: {click: function(){this.popupWindow.close();}}
			})
		]);
		popup.setContent('<span class="crm-ads-rtg-warning-popup-alert">' + text + '</span>');
		popup.show();
	};

})();