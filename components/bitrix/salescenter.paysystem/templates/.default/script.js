(function(){
	'use strict';

	if (BX.SalecenterPaySystem)
		return;

	BX.SalecenterPaySystem = {
		init: function (parameters) {
			this.slider = null;
			this.paySystemHandler = parameters.paySystemHandler;
			this.paySystemMode = parameters.paySystemMode;
			this.paySystemId = parameters.paySystemId;
			this.containerNode = BX(parameters.containerId);
			this.buttonSaveNode = BX(parameters.buttonSaveId);
			this.formId = parameters.formId;
			this.auth = parameters.auth;
			this.errorMessageNode = BX(parameters.errorMessageId);
			this.paySystemFormData = [];
			this.checkedAuthStatus = false;
			this.settingsFormLinkNameCodeMap = parameters.settingsFormLinkNameCodeMap;
			this.handlerClassName = parameters.handlerClassName;
			this.isExistsSettings = parameters.isExistsSettings;
			this.isExistsOnlyCommonSettings = parameters.isExistsOnlyCommonSettings;
			this.settingsMenu = null;
			this.settingsMenuNode = BX(parameters.settingsMenuId);
			this.buttonPanelId = parameters.buttonPanelId;

			this.uiNodes = {
				'name': this.containerNode.querySelector('[data-bx-salescenter-auth-name]'),
				'link': this.containerNode.querySelector('[data-bx-salescenter-auth-link]'),
				'logout': this.containerNode.querySelector('[data-bx-salescenter-auth-logout]')
			};

			this.initExpertMenu();
			this.showBlockByAuth();
			this.bindEvents();

			var adminSidePanel = top.BX.adminSidePanel || BX.adminSidePanel;
			if (adminSidePanel)
			{
				if (!top.window["adminSidePanel"] || !BX.is_subclass_of(top.window["adminSidePanel"], adminSidePanel))
				{
					top.window["adminSidePanel"] = new adminSidePanel({
						publicMode: true
					});
				}
			}
		},

		bindEvents: function()
		{
			BX.bind(BX('bx-salescenter-add-button'), 'click', BX.proxy(this.openSlider, this));
			BX.bind(BX('bx-salescenter-connect-button'), 'click', BX.proxy(this.openPopup, this));
			BX.bind(BX('LOGOTIP'), 'bxchange', BX.proxy(this.showLogotip, this));
			BX.bind(this.buttonSaveNode, 'click', BX.proxy(this.savePaySystemAction, this));
			BX.bind(this.uiNodes.logout, 'click', BX.proxy(this.logoutAction, this));

			BX.addCustomEvent(window, 'seo-client-auth-result', BX.proxy(this.checkAuthStatusAction, this));
			BX.addCustomEvent('onPopupOpen', BX.proxy(this.onPopupOpenHandler, this));

			BX.addCustomEvent("SidePanel.Slider:onLoad", BX.proxy(this.onLoadSlider, this));
			BX.addCustomEvent("SidePanel.Slider:onClose", BX.proxy(this.onCloseSlider, this));

			BX.addCustomEvent("SidePanel.Slider:onMessage", BX.proxy(this.onMessageSlider, this));

			BX.addCustomEvent("BX.Sale.PaySystem.Registration.Robokassa:onAddSettings", BX.proxy(this.onAddSettings, this));
		},

		initExpertMenu()
		{
			var self = this;

			this.settingsMenu = new BX.Main.Menu({
				bindElement: this.settingsMenuNode,
				angle: true,
				offsetLeft: 20,
				closeByEsc: true,
				items: [],
			});

			this.settingsMenu.addMenuItem({
				text: BX.message('SALESCENTER_SP_PAY_SYSTEM_EXPERT_SETTINGS'),
				onclick: function () {
					self.settingsMenu.close();
					self.openSlider();
				}.bind(this)
			});
			if (this.paySystemId > 0)
			{
				this.settingsMenu.addMenuItem({
					text: BX.message('SALESCENTER_SP_PAY_SYSTEM_DELETE'),
					onclick: function (event) {
						self.settingsMenu.close();
						self.remove(event);
					}.bind(this)
				});
			}

			if (this.isExistsOnlyCommonSettings)
			{
				this.addLogoutMenuItem();
			}
		},

		addLogoutMenuItem()
		{
			var self = this;
			this.settingsMenu.addMenuItem({
				id: 'logoutMenuItem',
				text: BX.message('SALESCENTER_SP_PAY_SYSTEM_SETTINGS_LOGOUT'),
				onclick: function () {
					self.settingsMenu.close();
					self.logoutFromSettings();
				}.bind(this)
			});
		},

		removeLogoutMenuItem()
		{
			this.settingsMenu.removeMenuItem('logoutMenuItem');
		},

		showExpertSettingsMenu(e)
		{
			e.preventDefault();
			this.settingsMenu.show();
		},

		onLoadSlider: function(sidePanelManager)
		{
			this.slider = sidePanelManager.slider;
			top.BX.addCustomEvent("AdminSidePanel:onSendRequest", BX.proxy(this.onSendAdminSidePanelRequest.bind(this, this.slider)));
			var innerDoc = this.getSliderDocument(this.slider);
			this.formData = this.getAllFormDataJson(innerDoc);
		},

		onCloseSlider: function(sidePanelManager)
		{
			this.onCloseSliderPopup(sidePanelManager);
		},

		openPopup: function(e)
		{
			var popupWindow;
			if (this.auth && this.auth.URL)
			{
				popupWindow = BX.util.popup(this.auth.URL, 800, 600);
				if (popupWindow)
				{
					BX.onCustomEvent('onPopupOpen', [popupWindow]);
				}
			}
		},

		onPopupOpenHandler: function(popupWindow)
		{
			var self = this,
				timer = setInterval(function() {
					if(popupWindow.closed) {
						clearInterval(timer);
						if (self.checkedAuthStatus === false)
						{
							self.checkAuthStatusAction();
						}
					}
				}, 1000);
		},

		logoutAction: function()
		{
			BX.UI.Dialogs.MessageBox.confirm(
				BX.message('SALESCENTER_SP_LOGOUT_CONFIRMATION_TEXT'),
				BX.message('SALESCENTER_SP_LOGOUT_CONFIRMATION_TITLE'),
				(messageBox) => {
					messageBox.close();
					BX.ajax.runComponentAction(
						'bitrix:salescenter.paysystem',
						'logoutProfile',
						{
							mode: 'ajax',
							data: {
								type: this.auth.TYPE
							}
						}
					).then(
						function (response)
						{
							this.toggleLogoutBlock();
						}.bind(this),
						function (response)
						{
							this.showErrorPopup(response.errors);
						}.bind(this)
					);
				},
				BX.message('SALESCENTER_SP_LOGOUT_CONFIRMATION_OK'),
				(messageBox) => messageBox.close(),
				BX.message('SALESCENTER_SP_LOGOUT_CONFIRMATION_CANCEL'),
			);
		},

		checkAuthStatusAction: function(eventData)
		{
			eventData.reload = false;
			this.checkedAuthStatus = true;

			BX.ajax.runComponentAction(
				'bitrix:salescenter.paysystem',
				'getProfileStatus',
				{
					mode: 'ajax',
					data: {
						type: this.auth.TYPE
					}
				}
			).then(
				function(response)
				{
					this.toggleAuthBlock(response.data.profile);
				}.bind(this),
				function (response)
				{
					this.showErrorPopup(response.errors);
				}.bind(this)
			);
		},

		toggleAuthBlock: function(profile)
		{
			if (profile)
			{
				this.setProfileData(profile);
				this.showBlock(['profile', 'settings', 'form']);
				BX.removeClass(BX(this.buttonPanelId), 'ui-button-panel-wrapper-hide');
			}
			else
			{
				this.showBlock(['settings', 'form']);
				BX.addClass(BX(this.buttonPanelId), 'ui-button-panel-wrapper-hide');
			}

			if(this.slider)
			{
				this.slider.reload();
			}
		},

		toggleLogoutBlock: function()
		{
			this.showBlock(['auth']);

			if(this.slider)
			{
				this.slider.reload();
			}
		},

		setProfileData: function(profile)
		{
			if (this.uiNodes.name && profile.NAME)
			{
				if (this.auth.TYPE === 'yookassa')
				{
					this.uiNodes.name.innerText = 'Shop ID ' + profile.NAME;
				}
				else
				{
					this.uiNodes.name.innerText = profile.NAME;
				}
			}

			if (this.uiNodes.link)
			{
				if (profile.LINK)
				{
					this.uiNodes.link.setAttribute('href', profile.LINK);
				}
				else
				{
					this.uiNodes.link.removeAttribute('href');
				}
			}
		},

		showBlock: function (blockCodes)
		{
			blockCodes = BX.type.isArray(blockCodes) ? blockCodes : [blockCodes];
			var attributeBlock = 'data-bx-salescenter-block';
			var blockNodes = this.containerNode.querySelectorAll('[' + attributeBlock + ']');
			blockNodes = BX.convert.nodeListToArray(blockNodes);
			blockNodes.forEach(function (blockNode) {
				var code = blockNode.getAttribute(attributeBlock);
				var isShow = BX.util.in_array(code, blockCodes);
				blockNode.style.display = isShow ? 'block' : 'none';
			}, this);
		},

		showBlockByAuth: function()
		{
			if (this.auth.HAS_AUTH)
			{
				this.setProfileData(this.auth.PROFILE);
				if (this.auth.PROFILE)
				{
					this.showBlock(['profile', 'settings', 'form']);
				}
				else
				{
					this.showBlock(['profile']);
				}
			}
			else
			{
				if (this.auth.PROFILE)
				{
					this.setProfileData(this.auth.PROFILE);
					this.showBlock(['profile', 'settings', 'form']);
				}
				else
				{
					if (this.auth.CAN_AUTH)
					{
						this.showBlock(['auth']);
					}
					else
					{
						this.showBlock(['settings', 'form']);
					}
				}
			}
		},

		openSlider: function()
		{
			var firstLoad = true;
			var sliderOptions = {
				allowChangeHistory: false,
				events: {
					onLoad: function (e)
					{
						if (firstLoad)
						{
							var slider = e.getSlider();
							this.updatePaySystemForm(slider);
							firstLoad = false;
						}
					}.bind(this),
					onClose: function (e)
					{
						var slider = e.getSlider(), paySystemFormData;
						paySystemFormData = this.getPaySystemFormData(slider);

						if (paySystemFormData.hasOwnProperty('ID') && paySystemFormData.ID > 0)
						{
							this.paySystemId = paySystemFormData.ID;

							this.updateCommonSettingsForm(paySystemFormData);

							this.savePaySystem().then(function(response) {
								slider.destroy();

								this.slider.url = BX.util.add_url_param(this.slider.url, { ID: this.paySystemId });
								this.slider.setFrameSrc();

								this.slider.reload();
							}.bind(this));
						}
						else
						{
							this.updateCommonSettingsForm(paySystemFormData);
						}
					}.bind(this),
				}
			};

			BX.SidePanel.Instance.open(this.getConnectPath(), sliderOptions);
		},

		onMessageSlider: function (e)
		{
			if (e.getEventId() === 'save')
			{
				if (this.paySystemId > 0)
				{
					this.slider.reload();
				}
				else
				{
					this.closeSlider();
				}
			}
		},

		closeSlider: function()
		{
			var savedInput = BX('salescenter-form-is-saved');
			if (savedInput)
			{
				savedInput.value = 'y';
			}
			if (this.slider)
			{
				this.slider.close();
			}
		},

		getConnectPath: function()
		{
			var connectPath = '/shop/settings/sale_pay_system_edit/?publicSidePanel=Y';
			if (this.paySystemId > 0)
			{
				connectPath += ('&ID=' + this.paySystemId);
			}
			else if (this.paySystemHandler)
			{
				connectPath += ('&ACTION_FILE=' + this.paySystemHandler);
				if (this.paySystemMode)
				{
					connectPath += ('&PS_MODE=' + this.paySystemMode);
				}
			}

			return connectPath;
		},

		savePaySystemAction: function(e)
		{
			e.preventDefault();

			this.savePaySystem().then(
				function(response) {
					BX.removeClass(this.buttonSaveNode, 'ui-btn-wait');
					this.hideError();
					this.closeSlider();
				}.bind(this),
				function (response) {
					BX.removeClass(this.buttonSaveNode, 'ui-btn-wait');
					this.showError(response.errors);

					if (response.data.hasOwnProperty('ID'))
					{
						this.paySystemId = response.data.ID;
						BX('ID').value = this.paySystemId;
					}

					window.scrollTo(0, 0);
				}.bind(this)
			);
		},

		savePaySystem: function()
		{
			var analyticsLabel, type;

			if (this.paySystemId > 0)
			{
				analyticsLabel = 'salescenterUpdatePaymentSystem';
			}
			else
			{
				analyticsLabel = 'salescenterAddPaymentSystem';
			}

			type = this.paySystemHandler;
			if (this.paySystemMode)
			{
				type = type + ':' + this.paySystemMode;
			}

			return BX.ajax.runComponentAction(
				'bitrix:salescenter.paysystem',
				'savePaySystem',
				{
					mode: 'ajax',
					data: this.getSaveData(),
					analyticsLabel: {
						analyticsLabel: analyticsLabel,
						type: type,
					},
				}
			);
		},

		getSaveData: function()
		{
			var saveData = this.getAllFormData(document);

			for (var name in this.paySystemFormData)
			{
				if (this.paySystemFormData.hasOwnProperty(name))
				{
					if (this.isObject(this.paySystemFormData[name]))
					{
						saveData.append(name, JSON.stringify(this.paySystemFormData[name]));
					}
					else
					{
						saveData.append(name, this.paySystemFormData[name]);
					}
				}
			}

			if (this.paySystemFormData.hasOwnProperty('NAME'))
			{
				saveData.append('ACTIVE', saveData.get('ACTIVE'));
				saveData.append('NAME', saveData.get('NAME'));
				saveData.append('DESCRIPTION', saveData.get('DESCRIPTION'));
				saveData.append('IS_CASH', saveData.get('IS_CASH'));
				saveData.append('CAN_PRINT_CHECK', saveData.get('CAN_PRINT_CHECK'));
			}

			return saveData;
		},

		updatePaySystemForm: function(slider)
		{
			var innerDoc,
				eventChange,
				target,
				observer,
				commonSettingsFormData;

			innerDoc = this.getSliderDocument(slider);
			commonSettingsFormData = this.getCommonSettingsFormData();

			if (this.paySystemId > 0)
			{
				this.setPaySystemFormFields(innerDoc, commonSettingsFormData);
			}
			else
			{
				target = innerDoc.getElementById('LOGOTIP').closest('span').firstChild;
				observer = this.elementObserver(target, function(mutation) {
					this.setPaySystemFormFields(innerDoc, commonSettingsFormData);
					observer.disconnect();
				}.bind(this));

				var psAction = innerDoc.getElementById('ACTION_FILE');
				if (psAction)
				{
					eventChange = new Event('change');
					psAction.dispatchEvent(eventChange);
				}
			}
		},

		setPaySystemFormFields: function(innerDoc, commonSettingsFormData)
		{
			var psAction,
				psMode,
				psaName,
				name,
				isCash,
				canPrintCheck,
				fiscalizationTab;

			psaName = innerDoc.getElementById('PSA_NAME');
			if (psaName && commonSettingsFormData.NAME)
			{
				psaName.value = commonSettingsFormData.NAME;
			}

			name = innerDoc.getElementById('NAME');
			if (name && commonSettingsFormData.NAME)
			{
				name.value = commonSettingsFormData.NAME;
			}

			isCash = innerDoc.getElementsByName('IS_CASH');
			if (isCash && commonSettingsFormData.IS_CASH)
			{
				isCash[0].value = commonSettingsFormData.IS_CASH;
			}

			psAction = innerDoc.getElementById('ACTION_FILE');
			if (psAction)
			{
				psAction.closest('tr').style.display = 'none';
			}

			psMode = innerDoc.getElementById('PS_MODE');
			if (psMode)
			{
				psMode.closest('tr').style.display = 'none';
			}

			if (commonSettingsFormData.CAN_PRINT_CHECK_SELF && commonSettingsFormData.CAN_PRINT_CHECK_SELF === 'Y')
			{
				canPrintCheck = innerDoc.getElementById('CAN_PRINT_CHECK');
				if (canPrintCheck)
				{
					canPrintCheck.closest('tr').style.display = 'none';
				}
			}
			else
			{
				canPrintCheck = innerDoc.getElementById('CAN_PRINT_CHECK');
				if (canPrintCheck && commonSettingsFormData.CAN_PRINT_CHECK)
				{
					canPrintCheck.checked = (commonSettingsFormData.CAN_PRINT_CHECK === 'Y');
				}
			}

			fiscalizationTab = innerDoc.getElementById('tab_cont_cashbox_edit');
			if (fiscalizationTab)
			{
				fiscalizationTab.style.display = 'none';
				innerDoc.getElementById('cashbox_edit').style.display = 'none';
			}

			try
			{
				var descriptionEditor = this.lookupDescriptionEditor(innerDoc);
				if (descriptionEditor)
				{
					descriptionEditor.SetEditorContent(BX.util.htmlspecialchars(commonSettingsFormData.DESCRIPTION));
					descriptionEditor.SaveContent();
					if (descriptionEditor.pEditorDocument)
					{
						BX.bind(descriptionEditor.pEditorDocument, 'click', BX.proxy(descriptionEditor.OnClick, descriptionEditor));
						BX.bind(descriptionEditor.pEditorDocument, 'mousedown', BX.proxy(descriptionEditor.OnMousedown, descriptionEditor));
					}
				}
			}
			catch (err)
			{
				//
			}
		},

		getPaySystemFormData: function(slider)
		{
			var innerDoc, paySystemFormData;
			innerDoc = this.getSliderDocument(slider);
			paySystemFormData = this.getAllFormDataList(innerDoc);

			try
			{
				var descriptionEditor = this.lookupDescriptionEditor(innerDoc);
				if (descriptionEditor)
				{
					descriptionEditor.SaveContent();
					paySystemFormData.DESCRIPTION = BX.util.htmlspecialcharsback(descriptionEditor.GetContent());
				}
			}
			catch (err)
			{
				//
			}

			return paySystemFormData;
		},

		updateCommonSettingsForm: function(paySystemFormData)
		{
			var id, name, description, isCash, canPrintCheck;
			var sliderData = this.getCommonSettingsFormData();

			id = BX('ID');
			name = BX('NAME');
			description = BX('DESCRIPTION');
			isCash = BX('IS_CASH');
			canPrintCheck = BX('CAN_PRINT_CHECK');

			if (id && paySystemFormData.ID)
			{
				id.value = paySystemFormData.ID;
			}

			if (name && paySystemFormData.NAME)
			{
				name.value = paySystemFormData.NAME;
			}

			if (description && paySystemFormData.DESCRIPTION)
			{
				description.value = paySystemFormData.DESCRIPTION;
			}

			if (isCash && paySystemFormData.IS_CASH)
			{
				isCash.value = paySystemFormData.IS_CASH;
			}

			if (canPrintCheck)
			{
				if (sliderData.CAN_PRINT_CHECK_SELF && sliderData.CAN_PRINT_CHECK_SELF === 'Y')
				{
					canPrintCheck.checked = true;
				}
				else
				{
					canPrintCheck.checked = !!(paySystemFormData.CAN_PRINT_CHECK && paySystemFormData.CAN_PRINT_CHECK === 'Y');
				}
			}

			this.paySystemFormData = paySystemFormData;
		},

		getCommonSettingsFormData: function()
		{
			var commonSettingsFormData;
			commonSettingsFormData = this.getAllFormDataList(document);
			return commonSettingsFormData;
		},

		onSendAdminSidePanelRequest: function(slider, url)
		{
			if (url.indexOf("action=delete") !== -1)
			{
				slider.close();
			}
		},

		elementObserver: function(target, callback)
		{
			if (!window.MutationObserver)
			{
				return;
			}

			var config = {
				attributes: true,
				childList: true,
				characterData: true
			};
			var observer = new MutationObserver(function(mutations) {
				mutations.forEach(function(mutation) {
					callback(mutation);
				});
			});
			observer.observe(target, config);

			return observer;
		},

		getAllFormDataJson: function(parentNode)
		{
			var fromDataList = this.getAllFormDataList(parentNode);
			return fromDataList ? JSON.stringify(fromDataList) : '';
		},

		getAllFormData: function(parentNode)
		{
			var allFormData = new FormData(),
				fromDataList = this.getAllFormDataList(parentNode);

			for (var i in fromDataList)
			{
				if (fromDataList.hasOwnProperty(i))
				{
					if (this.isObject(fromDataList[i]))
					{
						allFormData.append(i, JSON.stringify(fromDataList[i]))
					}
					else
					{
						allFormData.append(i, fromDataList[i])
					}
				}
			}

			return allFormData;
		},

		getAllFormDataList: function(parentNode)
		{
			var allFormData = {}, i, j, forms;

			if (!parentNode)
			{
				return allFormData;
			}

			forms = parentNode.getElementsByTagName('form');
			for(i = 0; i < forms.length; i++)
			{
				var formData = this.getFormData(forms[i]);
				for (j in formData)
				{
					if (formData.hasOwnProperty(j))
					{
						allFormData[j] = formData[j];
					}
				}
			}

			return allFormData;
		},

		getFormData: function(formNode)
		{
			var prepared = BX.ajax.prepareForm(formNode),
				i;

			for (i in prepared.data)
			{
				if (prepared.data.hasOwnProperty(i) && i === '')
				{
					delete prepared.data[i];
				}
			}

			return !!prepared && prepared.data ? prepared.data : {};
		},

		showErrorPopup: function(errors)
		{
			var contentNode, errorNode = [];
			errors.forEach(function (error) {
				errorNode.push(
					BX.create('p', {
							text: error.message
						}
					)
				);
			});

			if (!errorNode.length)
			{
				return;
			}

			contentNode = BX.create('div', {
					children: errorNode
				}
			);

			BX.UI.Dialogs.MessageBox.alert(
				contentNode,
				BX.Loc.getMessage('SALESCENTER_SP_ERROR_POPUP_TITLE'),
				(messageBox) => messageBox.close(),
				BX.Loc.getMessage('SALESCENTER_SP_BUTTON_CLOSE'),
			);
		},

		showError: function(errors)
		{
			var text = '';

			errors.forEach(function (error) {
				text += error.message + '<br>';
			});

			if (this.errorMessageNode && text)
			{
				this.errorMessageNode.parentNode.style.display = 'block';
				this.errorMessageNode.innerHTML = text;
			}
		},

		hideError: function()
		{
			if (this.errorMessageNode)
			{
				this.errorMessageNode.innerHTML = '';
				this.errorMessageNode.parentNode.style.display = 'none';
			}
		},

		getSliderDocument: function(slider)
		{
			var sliderIframe, innerDoc;
			sliderIframe = slider.iframe;
			innerDoc = sliderIframe.contentDocument || sliderIframe.contentWindow.document;

			return innerDoc;
		},

		lookupDescriptionEditor: function(documentObject)
		{
			try
			{
				var windowObject = documentObject.defaultView || documentObject.parentWindow;
				var descriptionFrameObject = 'LightHTMLEditorhndl_dscr_0';
				if (this.paySystemId && this.paySystemId > 0)
				{
					descriptionFrameObject = 'LightHTMLEditorhndl_dscr_' + this.paySystemId;
				}
				return windowObject[descriptionFrameObject];
			}
			catch (err)
			{
				// assume no editor found
			}
		},

		showLogotip: function(input) {
			if (input.currentTarget.files && input.currentTarget.files[0]) {
				var reader = new FileReader();
				reader.onload = function(e) {
					BX('salescenter-img-preload').src = e.target.result;
				};

				reader.readAsDataURL(input.currentTarget.files[0]);
			}
		},

		onCloseSliderPopup: function(event)
		{
			var sliderDocument = this.getSliderDocument(event.slider);
			var savedInput = sliderDocument.getElementById('salescenter-form-is-saved');
			if(savedInput && savedInput.value === 'y')
			{
				return true;
			}
			var formData = this.getAllFormDataJson(sliderDocument);
			if (this.formData === formData || this.isClose === true)
			{
				this.isClose = false;
				return false;
			}

			event.action = false;

			if (this.isPopupShown)
			{
				return false;
			}

			BX.UI.Dialogs.MessageBox.confirm(
				BX.Loc.getMessage('SALESCENTER_SP_POPUP_CONTENT_MSGVER_1'),
				(messageBox) => {
					this.isPopupShown = false;
					this.isClose = true;

					messageBox.close();

					BX.SidePanel.Instance.getTopSlider().close();
				},
				BX.Loc.getMessage('SALESCENTER_SP_POPUP_BUTTON_CLOSE_MSGVER_1'),
				(messageBox) => {
					this.isPopupShown = false;

					messageBox.close();

					BX.SidePanel.Instance.getTopSlider().focus();
				},
				BX.Loc.getMessage('SALESCENTER_SP_POPUP_BUTTON_STAY'),
			);
			this.isPopupShown = true;

			return false;
		},

		isObject: function(value)
		{
			return value && typeof value === 'object' && value.constructor === Object;
		},

		remove: function(event)
		{
			var buttonRemoveNode = event.target;
			event.preventDefault();
			if(this.paySystemId > 0 && confirm(BX.message('SALESCENTER_SP_PAYSYSTEM_DELETE_CONFIRM')))
			{
				var type = this.paySystemHandler;
				if (this.paySystemMode)
				{
					type = type + ':' + this.paySystemMode;
				}

				BX.ajax.runComponentAction(
					'bitrix:salescenter.paysystem',
					'deletePaySystem',
					{
						mode: 'ajax',
						data: {paySystemId: this.paySystemId},
						analyticsLabel: {
							analyticsLabel: 'salescenterDeletePaymentSystem',
							type: type,
						},
					}
				).then(
					function() {
						BX.removeClass(buttonRemoveNode, 'ui-btn-wait');
						this.closeSlider();
					}.bind(this),
					function (response) {
						BX.removeClass(buttonRemoveNode, 'ui-btn-wait');
						this.showError(response.errors);
					}.bind(this)
				);
			}
			else
			{
				setTimeout(function()
				{
					BX.removeClass(buttonRemoveNode, 'ui-btn-wait');
				}, 100);
			}
		},

		change: function()
		{
			if (this.paySystemId <= 0)
			{
				return;
			}

			var sliderDocument = this.getSliderDocument(this.slider);
			var formData = this.getAllFormDataJson(sliderDocument);
			if (this.formData === formData)
			{
				BX.addClass(BX(this.buttonPanelId), 'ui-button-panel-wrapper-hide');

				return;
			}

			BX.removeClass(BX(this.buttonPanelId), 'ui-button-panel-wrapper-hide');
		},

		openSettingsForm(url)
		{
			BX.SidePanel.Instance.open(url, {
				cacheable: false,
				allowChangeHistory: false,
				width: 500
			});
		},

		onAddSettings(event)
		{
			var data = event.getData();
			if (data && data.handlerClassName)
			{
				this.getPaySystemSettingsData(data.handlerClassName).then(
					function (response)
					{
						var responseData = response.data;

						this.reloadSettingsBlockOnAdd(data.handlerClassName, responseData);

						if (responseData.IS_SETTINGS_EXISTS)
						{
							this.showNotificationOnAddSetting();
						}
					}.bind(this)
				);
			}
		},

		getPaySystemSettingsData(handlerClassName)
		{
			return BX.ajax.runComponentAction(
				'bitrix:salescenter.paysystem',
				'getPaySystemSettingsData',
				{
					mode: 'class',
					data: {
						'handlerClassName': handlerClassName,
					}
				}
			);
		},

		reloadSettingsBlockOnAdd(handlerClassName, responseData)
		{
			var paySystemSettings = responseData.PAY_SYSTEM_ROBOKASSA_SETTINGS;

			if (paySystemSettings && paySystemSettings.IS_SUPPORT_SETTINGS)
			{
				if (paySystemSettings.IS_ONLY_COMMON_SETTINGS_EXISTS)
				{
					this.addLogoutMenuItem();

					var paySystemFormLinkText = this.settingsFormLinkNameCodeMap['DEFAULT'];

					handlerClassName = this.handlerClassName.replace(/\/\//g, "/");
					if (this.settingsFormLinkNameCodeMap.hasOwnProperty(handlerClassName))
					{
						paySystemFormLinkText = this.settingsFormLinkNameCodeMap[handlerClassName]['EXIST'];
					}

					BX('salescenter-settings-paysystem-form-link').text = paySystemFormLinkText;
				}

				if (
					paySystemSettings.IS_SETTINGS_EXISTS
					&& BX('salescenter-settings-paysystem-register-component')
				)
				{
					BX.remove(BX('salescenter-settings-paysystem-register-component'));
				}
			}
		},

		showNotificationOnAddSetting()
		{
			window.top.BX.UI.Notification.Center.notify({
				content: BX.message('SALESCENTER_SP_PAYSYSTEM_SETTINGS_ADD_NOTIFICATION'),
			});
		},

		logoutFromSettings()
		{
			this.deletePaySystemSettings().then(
				function ()
				{
					this.getPaySystemSettingsData(this.handlerClassName).then(
						function (response)
						{
							var responseData = response.data;
							if (!responseData.IS_SETTINGS_EXISTS)
							{
								this.removeLogoutMenuItem();
								this.getRegisterPaySystemComponentAction().then(
									function (response)
									{
										this.reloadSettingsBlockOnLogout(response.data);
									}.bind(this)
								);
							}
						}.bind(this)
					);
			}.bind(this));
		},

		deletePaySystemSettings()
		{
			return BX.ajax.runAction("sale.paysystem.settings.robokassa.delete", {});
		},

		getRegisterPaySystemComponentAction()
		{
			return BX.ajax.runAction("sale.paysystem.settings.robokassa.getRegisterComponent", {});
		},

		reloadSettingsBlockOnLogout(componentData)
		{
			if (BX('salescenter-settings-paysystem-register-component'))
			{
				BX.remove(BX('salescenter-settings-paysystem-register-component'));
			}

			var buttonContainerTop = BX('salescenter-settings-block');
			if (buttonContainerTop)
			{
				var paysystemRegisterComponent = BX.create(
					'span',
					{
						props: {
							id: 'salescenter-settings-paysystem-register-component'
						},
					}
				);

				BX.html(paysystemRegisterComponent, componentData.html);
				buttonContainerTop.prepend(paysystemRegisterComponent);

				var paySystemFormLinkText = this.settingsFormLinkNameCodeMap['DEFAULT'];

				var handlerClassName = this.handlerClassName.replace(/\/\//g, "/");
				if (this.settingsFormLinkNameCodeMap.hasOwnProperty(handlerClassName))
				{
					paySystemFormLinkText = this.settingsFormLinkNameCodeMap[handlerClassName]['NEW'];
				}

				BX('salescenter-settings-paysystem-form-link').text = paySystemFormLinkText;
			}
		}
	};

	if (BX.SalecenterPaySystemCashbox)
		return;

	BX.SalecenterPaySystemCashbox = {
		init: function (parameters) {
			this.paySystemId = parameters.paySystemId;
			this.formNode = BX(parameters.formId);
			this.cashboxContainerInfoNode = BX(parameters.cashboxContainerInfoId);
			this.cashboxContainerNode = BX(parameters.cashboxContainerId);
			this.cashboxWarningContainerNode = BX(parameters.cashboxWarningContainerId);
			this.canPrintCheckNode = BX(parameters.canPrintCheckId);

			this.cashboxSwitchet = null;

			this.containerNodeList = {
				'settings': BX(parameters.containerList.settings),
				'cashboxSettings': BX(parameters.containerList.cashboxSettings),
			};

			this.section = parameters.section;
			this.fields = parameters.fields;

			this.initSwitcher();

			this.renderSettings(this.cashboxContainerNode);
		},

		renderSettings: function (cashboxContainerNode)
		{
			Object.keys(this.section).forEach(function (name) {
				if (this.fields[name])
				{
					var section = this.section[name];
					var sectionType = section.type;

					if (this.containerNodeList[sectionType])
					{
						this.containerNodeList[sectionType].appendChild(this.renderSection(section, this.fields[name]));
					}
					else
					{
						cashboxContainerNode.appendChild(this.renderSection(section, this.fields[name]));
					}
				}
			}.bind(this));
		},

		renderSection: function (section, fields)
		{
			var sectionNode = BX.create('div', {
				props: {
					className: 'salescenter-editor-section-content'
				}
			});

			var title = BX.create('div', {
				props: {
					className: 'salescenter-paysystem-section-title salescenter-paysystem-angle-icon-after'
				},
				children: [
					BX.create('div', {
						props: {
							className: 'salescenter-paysystem-section-title-block',
						},
						children: [
							BX.create('div', {
								props: {
									className: 'ui-title-6',
								},
								text: section.title,
							}),
							section.hint && BX.UI.Hint.createNode(section.hint),
						],
					}),
					BX.create('div', {
						props: {
							className: 'salescenter-paysystem-angle-icon'
						}
					})
				],
			});
			sectionNode.appendChild(title);

			sectionNode.appendChild(BX.create('div', {
				props: {
					className: 'ui-hr'
				}
			}));

			var innerContent = BX.create('div');
			if (section.collapsed === 'Y')
			{
				BX.hide(innerContent);
			}
			sectionNode.appendChild(innerContent);

			if (section.warning)
			{
				innerContent.appendChild(BX.create('div', {
					props: {
						className: 'ui-alert ui-alert-warning'
					},
					text: section.warning
				}));
			}

			fields.forEach(function (field) {
				var blockNode = BX.create('div', {
					props: {
						className: 'salescenter-editor-content-block'
					}
				})

				this.renderField(field).forEach(function (itemNode) {
					blockNode.appendChild(itemNode);
				});

				innerContent.appendChild(blockNode);
			}.bind(this));

			title.addEventListener('click', function()
			{
				BX.toggle(innerContent);
			});

			return sectionNode;
		},

		renderField: function (field)
		{
			if (field.type === 'checkbox')
			{
				return this.renderCheckbox(field);
			}

			if (field.type === 'select')
			{
				return this.renderSelect(field);
			}

			return this.renderString(field);
		},

		renderString: function (field)
		{
			var result = [];

			result.push(BX.create('div', {
				props: {
					className: 'ui-ctl-label-text' + ((field.required) ? ' salescenter-paysystem-control-required' : '')
				},
				text: field.label
			}));

			result.push(BX.create('div', {
				props: {
					className: 'ui-ctl ui-ctl-textbox ui-ctl-w100'
				},
				html: field.input
			}));

			if (field.hint)
			{
				result.push(BX.create('div', {
					props: {
						className: 'ui-ctl-label-text'
					},
					style: {
						marginTop: '5px'
					},
					children: [
						BX.create('span', {
							props: {
								className: 'salescenter-editor-content-logo-hint'
							},
							text: field.hint
						})
					]
				}));
			}

			return result;
		},

		renderSelect: function (field)
		{
			var result = [];

			result.push(BX.create('div', {
				props: {
					className: 'ui-ctl-label-text' + ((field.required) ? ' salescenter-paysystem-control-required' : '')
				},
				text: field.label
			}));

			var dropdown = BX.create('div', {
				props: {
					className: 'ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100'
				},
				children: [
					BX.create('div', {
						props: {
							className: 'ui-ctl-after ui-ctl-icon-angle'
						}
					})
				],
			});

			dropdown.insertAdjacentHTML('beforeend', field.input);
			result.push(dropdown);

			return result;
		},

		renderCheckbox: function (field)
		{
			var result = [];

			var label = BX.create('label', {
				props: {
					className: 'ui-ctl ui-ctl-checkbox ui-ctl-w100'
				},
				html: field.input
			});
			label.appendChild(BX.create('div', {
				props: {
					className: 'ui-ctl-label-text' + ((field.required) ? ' salescenter-paysystem-control-required' : '')
				},
				text: field.label
			}));

			result.push(label);

			return result;
		},

		initSwitcher: function()
		{
			var nodes = this.formNode.getElementsByClassName('js-cashbox-ui-switcher');
			nodes = BX.convert.nodeListToArray(nodes);
			nodes.forEach(function (node) {
				if (node.getAttribute('data-switcher-init'))
				{
					return;
				}

				var switcher = new BX.UI.Switcher({node: node});
				this.cashboxSwitchet = switcher;
				this.canPrintCheckNode.onchange = () => this.onToggleCanPrintCheckNode(this.canPrintCheckNode);

				BX.addCustomEvent(switcher, 'unchecked', BX.proxy(this.onToggleCashboxSwitcher.bind(this, switcher)));
				BX.addCustomEvent(switcher, 'checked', BX.proxy(this.onToggleCashboxSwitcher.bind(this, switcher)));
			}.bind(this));
		},

		onToggleCashboxSwitcher: function (switcher)
		{
			BX.SalecenterPaySystem.change();
			if (switcher.isChecked())
			{
				this.canPrintCheckNode.checked = true;
				this.cashboxContainerInfoNode.classList.remove("salescenter-paysystem-cashbox-block--disabled");
				if (this.cashboxWarningContainerNode)
				{
					BX.hide(this.cashboxWarningContainerNode);
				}

				this.showCashboxSettings();
			}
			else
			{
				this.cashboxContainerInfoNode.classList.add("salescenter-paysystem-cashbox-block--disabled");
				if (this.cashboxWarningContainerNode)
				{
					BX.show(this.cashboxWarningContainerNode);
				}

				this.hideCashboxSettings();
			}
		},

		onToggleCanPrintCheckNode: function (switcherNode)
		{
			// this switcher is just a HTML node, not UI switcher. This node doesn't have an isChecked() method
			if (switcherNode.checked === false)
			{
				this.cashboxSwitchet.check(false);
			}
		},

		showCashboxSettings: function ()
		{
			BX('salescenter-paysystem-cashbox').style.display = 'block';
		},

		hideCashboxSettings: function ()
		{
			BX('salescenter-paysystem-cashbox').style.display = 'none';
		},

		reloadCashboxSettings: function (kkmId)
		{
			var self = this;

			BX.ajax.runComponentAction(
				'bitrix:salescenter.paysystem',
				'reloadCashboxSettings',
				{
					mode: 'class',
					data: {
						'paySystemId': this.paySystemId,
						'kkmId': kkmId.value
					}
				}
			).then(
				function (response)
				{
					if (response.data.hasOwnProperty('IS_FISCALIZATION_ENABLE'))
					{
						var isFiscalizationEnable = response.data.IS_FISCALIZATION_ENABLE;
						self.cashboxSwitchet.check(isFiscalizationEnable);
					}

					self.section = response.data.CASHBOX.section;
					self.fields = response.data.CASHBOX.fields;

					self.resetAllSection();

					if (self.section)
					{
						self.renderSettings(self.cashboxContainerNode);
					}
				}
			);
		},

		resetSection: function(section)
		{
			section.forEach(function (name) {
				var sectionType = this.section[name].type;
				if (this.containerNodeList[sectionType])
				{
					this.containerNodeList[sectionType].innerHTML = '';
				}
			}.bind(this));
		},

		resetAllSection: function ()
		{
			Object.keys(this.containerNodeList).forEach(function (name) {
				this.containerNodeList[name].innerHTML = '';
			}.bind(this));
		}
	};
})(window);
