if (typeof(CrmAdsLeadAds) === "undefined")
{
	CrmAdsLeadAds = function (params)
	{
		this.containerId = params.containerId || 'crm-robot-ads-container-' + params.provider.TYPE;
		this.provider = params.provider;
		this.context = params.context;
		this.onRequest = params.onRequest;
		this.mess = params.mess;
		this.mess = params.mess;

		if (params.destroyEventName)
		{
			BX.addCustomEvent(window, params.destroyEventName, BX.proxy(function () {
				this.unbindAll();
				this.cleanInstances();
			}, this));
		}

		this.data = params.data;
		this.accountId = params.accountId;
		this.formId = params.formId;
		this.crmFormId = params.crmFormId;
		this.actionRequestUrl = params.actionRequestUrl;

		this.hasForms = false;
		this.loaded = [];
		this.init();
		this.showBlockByAuth();
		BX.UI.Hint.init(this.context);
	};
	CrmAdsLeadAds.prototype = {
		instances: [],
		attrBlocked: 'data-blocked',
		cleanInstances: function ()
		{
			for (var i = 0, len = this.instances.length; i < len; i++)
			{
				if (!this.instances[i])
				{
					continue;
				}

				this.instances[i].unbindAll();
				delete this.instances[i];
				this.instances[i] = null;
			}
		},
		unbindAll: function ()
		{
			BX.removeCustomEvent(
				window,
				'seo-client-auth-result',
				BX.proxy(this.onSeoAuth, this)
			);
		},
		init: function ()
		{
			this.cleanInstances();
			this.instances.push(this);

			this.containerNode = BX(this.containerId);
			if (!this.containerNode)
			{
				this.containerNode = BX.create('div');
				this.containerNode.id = this.containerId;
			}

			this.insertTemplateIntoNode('settings', this.containerNode);

			this.uiNodes = {
				'avatar': this.containerNode.querySelector('[data-bx-ads-auth-avatar]'),
				'name': this.containerNode.querySelector('[data-bx-ads-auth-name]'),
				'link': this.containerNode.querySelector('[data-bx-ads-auth-link]'),
				'logout': this.containerNode.querySelector('[data-bx-ads-auth-logout]'),
				'account': this.containerNode.querySelector('[data-bx-ads-account]'),
				'groupReg': this.containerNode.querySelector('[data-bx-ads-group-reg]'),
				'groupLogout': this.containerNode.querySelector('[data-bx-ads-group-logout]'),
				'accountLoader': this.containerNode.querySelector('[data-bx-ads-account-loader]'),
				'formName': this.containerNode.querySelector('[data-bx-ads-form-name]'),
				'formLocale': this.containerNode.querySelector('[data-bx-ads-form-locale]'),
				'formSuccessUrl': this.containerNode.querySelector('[data-bx-ads-form-url]'),
				'exportButton': this.containerNode.querySelector('[data-bx-ads-btn-export]'),
				'exportHint': this.containerNode.querySelector('[data-bx-ads-btn-hint]'),
				'exportDate': this.containerNode.querySelector('[data-bx-ads-btn-date]'),
				'formIdNode': this.containerNode.querySelector('[data-bx-ads-form-id]'),
				'form': [],
				'errorNotFound': this.containerNode.querySelector('[data-bx-ads-account-not-found]'),
				'refreshButton': this.containerNode.querySelector('[data-bx-ads-refresh-btn]'),
				'createLinks': BX.convert.nodeListToArray(
					this.containerNode.querySelectorAll('[data-bx-ads-audience-create-link]')
				),
				'autoRemover' : {
					'node': this.containerNode.querySelector('[data-bx-ads-audience-auto-remove]'),
					'checker': this.containerNode.querySelector('[data-bx-ads-audience-auto-remove-checker]'),
					'select': this.containerNode.querySelector('[data-bx-ads-audience-auto-remove-select]')
				}
			};

			this.uiNodes.createLinks.forEach(function (createLink) {
				BX.bind(createLink, 'click', BX.proxy(function () {
					if (!this.hasForms) this.showBlockRefresh();
				}, this));
			}, this);
			BX.bind(this.uiNodes.refreshButton, 'click', BX.proxy(function () {
				this.getProvider();
			}, this));


			if (this.uiNodes.exportButton)
			{
				BX.bind(this.uiNodes.exportButton, 'click', this.onButtonClick.bind(this));
				this.btnState.init(this);
			}

			if (this.uiNodes.groupReg)
			{
				BX.bind(this.uiNodes.groupReg, 'click', this.registerGroup.bind(this));
			}
			if (this.uiNodes.groupLogout)
			{
				BX.bind(this.uiNodes.groupLogout, 'click', this.logoutGroup.bind(this));
			}

			this.loader.init(this);
			BX.bind(this.uiNodes.logout, 'click', BX.proxy(this.logout, this));
			this.listenSeoAuth();
		},
		showBlockByAuth: function ()
		{
			if (this.provider.HAS_AUTH)
			{
				this.showBlockMain();
			}
			else
			{
				this.showBlockLogin();
			}
		},
		listenSeoAuth: function ()
		{
			BX.addCustomEvent(
				window,
				'seo-client-auth-result',
				BX.proxy(this.onSeoAuth, this)
			);
		},
		onSeoAuth: function (eventData)
		{
			eventData.reload = false;
			this.reload();
		},
		reload: function ()
		{
			window.location.reload();
		},
		logout: function ()
		{
			var analyticsLabel =
				!(this.provider.TYPE === "facebook" || this.provider.TYPE === "instagram")
					? {}
					: {
						connect: "FBE",
						action: "disconnect",
						type: "disconnect"
					}
			;
			this.showBlock('loading');
			this.request(
				'logout',
				{},
				BX.delegate(
					function (provider) {
						this.provider = provider;
						this.showBlockByAuth();
					},
					this
				),
				null,
				analyticsLabel
			);
		},
		logoutGroup: function ()
		{
			this.showBlock('loading');
			this.request(
				'logoutGroup',
				{'groupId': this.uiNodes.account.value},
				BX.delegate(
					function () {
						this.reload();
					},
					this
				)
			);
		},
		registerGroup: function ()
		{
			var popup = BX.util.popup('', 800, 600);

			var sendData = {
				'type': this.provider.TYPE,
				'accountId': this.uiNodes.account.value,
				'groupId': this.uiNodes.account.value
			};

			this.request(
				'registerGroup', sendData,
				function (response) {
					popup.location = response['groupAuthUrl'];
				}
			);
		},
		getProvider: function ()
		{
			this.showBlock('loading');
			this.request('getProvider', {}, BX.delegate(function (provider) {
				this.provider = provider;
				this.showBlockByAuth();
			}, this));
		},
		onButtonClick: function ()
		{
			if (this.btnState.isStateExport())
			{
				this.exportForm();
			}
			else
			{
				this.unlinkForm();
			}
		},
		exportForm: function ()
		{
			this.btnState.setWaiting();

			var sendData = {
				'type': this.provider.TYPE,
				'accountId': this.uiNodes.account.value,
				'crmFormId': this.crmFormId,
				'accountName': this.uiNodes.account.options[this.uiNodes.account.selectedIndex].text,
				'crmFormName': this.uiNodes.formName.value,
				'crmFormSuccessUrl': this.uiNodes.formSuccessUrl.value,
				'formLocale': this.uiNodes.formLocale ? this.uiNodes.formLocale.value : null
			};

			this.request(
				'exportForm', sendData,
				this.btnState.animate.bind(this.btnState),
				this.btnState.setReadyToExport.bind(this.btnState)
			);
		},
		unlinkForm: function ()
		{
			this.btnState.setWaiting();

			var sendData = {
				'type': this.provider.TYPE,
				'crmFormId': this.crmFormId
			};
			this.request(
				'unlinkForm', sendData,
				this.btnState.animate.bind(this.btnState),
				this.btnState.setReadyToUnlink.bind(this.btnState)
			);
		},
		btnState: {
			caller: null,
			btn: null,
			hint: null,
			date: null,
			attrState: 'data-bx-state',
			attrTextSend: 'data-bx-text-send',
			attrTextUnlink: 'data-bx-text-disconnect',
			attrTextSuccess: 'data-bx-text-success',
			attrDateTextNow: 'data-bx-text-now',
			attrHintEnabled: 'data-bx-text-enabled',
			attrHintDisabled: 'data-bx-text-disabled',
			classNameWait: 'ui-btn-wait',
			classNameSend: 'ui-btn-primary',
			init: function(caller)
			{
				this.caller = caller;

				this.btn = this.caller.uiNodes.exportButton;
				this.hint = this.caller.uiNodes.exportHint;
				this.date = this.caller.uiNodes.exportDate;
				this.formIdNode = this.caller.uiNodes.formIdNode;

				if (this.isStateExport())
				{
					this.setReadyToExport()
				}
				else
				{
					this.setReadyToUnlink();
				}
			},
			isStateExport: function()
			{
				return !this.btn.getAttribute(this.attrState);
			},
			setState: function(isExport)
			{
				this.btn.setAttribute(this.attrState, isExport ? '' : '1');
				this.changeFieldsEnable();
			},
			setText: function(element, attrName)
			{
				element.innerText = attrName ? element.getAttribute(attrName) : '';
			},
			changeFieldsEnable: function()
			{
				var isDisabled = !this.isStateExport();
				var isAccountBlocked = this.caller.uiNodes.account.hasAttribute(this.caller.attrBlocked);
				this.caller.uiNodes.account.disabled = isAccountBlocked ? true : isDisabled;
				this.caller.uiNodes.formName.disabled = isDisabled;
				this.caller.uiNodes.formSuccessUrl.disabled = isDisabled;
			},
			setWaiting: function()
			{
				BX.removeClass(this.btn, this.classNameSend);
				BX.addClass(this.btn, this.classNameWait);
				this.setText(this.hint, null);
				this.setText(this.date, null);
				this.formIdNode.style.display = 'none';
			},
			removeWaiting: function()
			{
				BX.removeClass(this.btn, this.classNameWait);
			},
			setSuccess: function()
			{
				this.removeWaiting();
				this.setText(this.btn, this.attrTextSuccess);
			},
			setReadyToExport: function()
			{
				this.removeWaiting();
				this.setText(this.btn, this.attrTextSend);
				this.setText(this.hint, this.attrHintEnabled);
				this.setText(this.date, null);
				BX.addClass(this.btn, this.classNameSend);
				this.formIdNode.style.display = 'none';

				this.setState(true);
			},
			setReadyToUnlink: function()
			{
				this.removeWaiting();
				this.setText(this.btn, this.attrTextUnlink);
				this.setText(this.hint, this.attrHintDisabled);
				if (!this.date.innerText)
				{
					this.setText(this.date, this.attrDateTextNow);
				}
				BX.removeClass(this.btn, this.classNameSend);
				this.formIdNode.style.display = '';

				this.setState(false);
			},
			animate: function()
			{
				this.setSuccess();
				setTimeout(this.animateFinal.bind(this), 1000);
			},
			animateFinal: function()
			{
				this.setWaiting();
				var f = this.isStateExport() ? this.setReadyToUnlink : this.setReadyToExport;
				setTimeout(f.bind(this), 300);
			}
		},
		showBlock: function (blockCodes)
		{
			blockCodes = BX.type.isArray(blockCodes) ? blockCodes : [blockCodes];
			var attributeBlock = 'data-bx-ads-block';
			var blockNodes = this.containerNode.querySelectorAll('[' + attributeBlock + ']');
			blockNodes = BX.convert.nodeListToArray(blockNodes);
			blockNodes.forEach(function (blockNode) {
				var code = blockNode.getAttribute(attributeBlock);
				var isShow = BX.util.in_array(code, blockCodes);
				blockNode.style.display = isShow ? 'block' : 'none';
			}, this);
		},
		showBlockRefresh: function ()
		{
			this.showBlock(['auth', 'refresh']);
		},
		showBlockLogin: function ()
		{
			this.showBlock('login');
		},
		showBlockMain: function ()
		{
			if (this.uiNodes.avatar)
			{
				this.uiNodes.avatar.style['background-image'] = 'url(' + this.provider.PROFILE.PICTURE + ')';
			}
			if (this.uiNodes.name)
			{
				this.uiNodes.name.innerText = this.provider.PROFILE.NAME;
			}
			if (this.uiNodes.link)
			{
				if (this.provider.PROFILE.LINK)
				{
					this.uiNodes.link.href = this.provider.PROFILE.LINK;
				}
				else
				{
					this.uiNodes.link.removeAttribute('href');
				}
			}

			this.showBlock(['auth', 'main']);

			this.loadSettings();
		},
		insertTemplateIntoNode: function (templateCode, parentNode, isAppend) {
			isAppend = isAppend || false;
			var defaultTemplateId = 'template-crm-ads-dlg-' + templateCode;
			var templateId = defaultTemplateId + '-' + this.provider.TYPE;
			var templateNode = BX(templateId);
			if (!templateNode)
			{
				templateNode = BX(defaultTemplateId);
			}

			var temporaryContainerNode = BX.create('div');
			temporaryContainerNode.innerHTML = templateNode.innerHTML;

			if (!isAppend)
			{
				parentNode.innerHTML = '';
			}

			var childList = BX.convert.nodeListToArray(temporaryContainerNode.children);
			childList.forEach(function (child) {
				parentNode.appendChild(child);
			});
		},
		onResponse: function (response, callback)
		{
			if (!response.errors || response.errors.length == 0)
			{
				callback.apply(this, [response.data]);
			}
		},
		request: function (action, requestData, callback, callbackFailure, analytics)
		{
			requestData.action = action;
			requestData.type = this.provider.TYPE;
			analytics = analytics || {};

			var callbackFailureWrapper = function(callback, response) {
				this.showErrorPopup(response);
				if (callback)
				{
					callback.apply(this, [response]);
				}
			};
			if (this.onRequest)
			{
				var params = [
					requestData,
					BX.delegate(
						function (response) {
							this.onResponse(response, callback);
						},
						this
					),
					callbackFailureWrapper.bind(this, callbackFailure)
				];
				this.onRequest.apply(this, params);
			}
			else
			{
				this.sendActionRequest(
					action,
					requestData,
					function(response) {
						this.onResponse(response, callback);
					},
					callbackFailureWrapper.bind(this, callbackFailure),
					analytics
				);
			}
		},
		prepareAjaxUrl : function(analytics)
		{
			var query = BX.ajax.prepareData({ analyticsLabel: analytics});

			return this.actionRequestUrl + (query !== "" ? "?" + query : "");
		},
		sendActionRequest: function (action, data, callbackSuccess, callbackFailure, analytics)
		{
			callbackSuccess = callbackSuccess || null;
			callbackFailure = callbackFailure || BX.proxy(this.showErrorPopup, this);
			analytics = analytics || {};

			data = data || data;
			data.sessid = BX.bitrix_sessid();
			BX.ajax({
				url: this.prepareAjaxUrl(analytics),
				method: 'POST',
				data: data,
				timeout: 30,
				dataType: 'json',
				processData: true,
				onsuccess: BX.proxy(function(data){
					data = data || {};
					if(data.error)
					{
						callbackFailure.apply(this, [data]);
					}
					else if(callbackSuccess)
					{
						callbackSuccess.apply(this, [data]);
					}
				}, this),
				onfailure: BX.proxy(function(){
					var data = {'error': true, 'text': ''};
					callbackFailure.apply(this, [data]);
				}, this)
			});
		},
		showErrorPopup: function (data)
		{
			data = data || {};
			var text = data.text || this.mess.errorAction;

			var previousPopup = BX.PopupWindowManager.getCurrentPopup();
			var popupCloseHandler = function (errorPopup, previousPopup) {
				errorPopup.close();
				if (previousPopup)
				{
					previousPopup.show();
				}
			};

			var popup = BX.PopupWindowManager.create(
				'crm_ads_forms_error',
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
					text: this.mess.dlgBtnClose,
					events: {
						click: popupCloseHandler.bind(this, popup, previousPopup)
					}
				})
			]);
			popup.setContent('<span class="crm-ads-forms-warning-alert">' + text + '</span>');
			popup.show();
		},
		loader: {
			init: function (caller) {
				this.caller = caller;
			},
			change: function (loaderNode, inputNode, isShow) {
				loaderNode.style.display = isShow ? '' : 'none';
				if (inputNode)
				{
					var isDisabled = (inputNode.options.length == 0 || isShow);
					if (!isDisabled && !this.caller.btnState.isStateExport())
					{
						isDisabled = true;
					}

					var isAccountBlocked = inputNode.hasAttribute(this.caller.attrBlocked);
					inputNode.disabled = isAccountBlocked ? true : isDisabled;
				}
			},
			forAccount: function (isShow) {
				this.change(this.caller.uiNodes.accountLoader, this.caller.uiNodes.account, isShow);
				this.change(this.caller.uiNodes.exportButton, null, !isShow);
			},
			forForms: function (type, isShow) {
				this.caller.uiNodes.form.forEach(function (audience) {
					this.change(audience.loader, audience.node, isShow);
				}, this);

				if (this.caller.uiNodes.autoRemover.node)
				{
					this.caller.uiNodes.autoRemover.node.style.display = isShow ? 'none' : '';
				}
			}
		},
		loadSettings: function()
		{
			var type = this.provider.TYPE;

			if(BX.util.in_array(type, this.loaded)) return;
			this.loaded.push(type);

			this.loadSettingsAccounts();
		},
		loadSettingsAccounts: function()
		{
			this.loader.forAccount(true);
			this.request('getAccounts', {}, BX.delegate(function(data){
				var dropDownData = data.map(function (accountData) {
					return {
						caption: accountData.name,
						value: accountData.id,
						selected: accountData.id == this.accountId
					};
				}, this);

				this.fillDropDownControl(this.uiNodes.account, dropDownData);
				this.loader.forAccount(false);
				if (dropDownData.length > 0)
				{
					BX.fireEvent(this.uiNodes.account, 'change');
				}
				else
				{
					this.showErrorEmptyAccounts();
				}

			}, this));
		},
		showErrorEmptyAccounts: function()
		{
			this.uiNodes.errorNotFound.style.display = this.hasForms ? 'none' : '';
		},
		fillDropDownControl: function(node, items)
		{
			items = items || [];
			node.innerHTML = '';

			items.forEach(function(item){
				if(!item || !item.caption)
				{
					return;
				}

				var option = document.createElement('option');
				option.value = item.value;
				option.selected = !!item.selected;
				option.innerText = item.caption;
				node.appendChild(option);
			});
		}
	};
}