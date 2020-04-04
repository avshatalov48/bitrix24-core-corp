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

			this.uiNodes = {
				'avatar': this.containerNode.querySelector('[data-bx-salescenter-auth-avatar]'),
				'name': this.containerNode.querySelector('[data-bx-salescenter-auth-name]'),
				'link': this.containerNode.querySelector('[data-bx-salescenter-auth-link]'),
				'logout': this.containerNode.querySelector('[data-bx-salescenter-auth-logout]')
			};

			this.showBlockByAuth();
			this.bindEvents();
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
			BX.ajax.runComponentAction(
				'bitrix:salescenter.paysystem',
				'logoutProfile',
				{
					mode: 'ajax'
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

		checkAuthStatusAction: function(eventData)
		{
			eventData.reload = false;
			this.checkedAuthStatus = true;

			BX.ajax.runComponentAction(
				'bitrix:salescenter.paysystem',
				'getProfileStatus',
				{
					mode: 'ajax'
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
			}
			else
			{
				this.showBlock(['settings', 'form']);
			}
		},

		toggleLogoutBlock: function()
		{
			this.showBlock(['auth']);
		},

		setProfileData: function(profile)
		{
			if (this.uiNodes.avatar)
			{
				this.uiNodes.avatar.style['background-image'] = 'url(' + profile.PICTURE + ')';
			}
			if (this.uiNodes.name)
			{
				this.uiNodes.name.innerText = profile.NAME;
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
				if (this.auth.PROFILE)
				{
					this.setProfileData(this.auth.PROFILE);
					this.showBlock(['profile', 'settings', 'form']);
				}
				else
				{
					this.showBlock(['settings', 'form']);
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
			var sliderOptions = {
				allowChangeHistory: false,
				events: {
					onLoad: function (e)
					{
						var slider = e.getSlider();
						this.updatePaySystemForm(slider);
					}.bind(this),
					onClose: function (e)
					{
						var slider = e.getSlider(), paySystemFormData;
						paySystemFormData = this.getPaySystemFormData(slider);
						this.updateCommonSettingsForm(paySystemFormData);
					}.bind(this),
					onDestroy: function (e) {
						if (this.paySystemId > 0)
						{
							var slider = e.getSlider(), paySystemFormData;
							paySystemFormData = this.getPaySystemFormData(slider);
							this.updateCommonSettingsForm(paySystemFormData);
						}
						else
						{
							this.closeSlider();
						}
					}.bind(this)
				}
			};

			BX.SidePanel.Instance.open(this.getConnectPath(), sliderOptions);
		},

		closeSlider: function()
		{
			var savedInput = BX('salescenter-form-is-saved');
			if(savedInput)
			{
				savedInput.value = 'y';
			}
			if(this.slider)
			{
				this.slider.close();
			}
		},

		getConnectPath: function()
		{
			var connectPath = '/shop/settings/sale_pay_system_edit/?publicSidePanel=Y';
			if (this.paySystemId > 0)
			{
				connectPath += ("&ID=" + this.paySystemId);
			}
			else if (this.paySystemHandler)
			{
				connectPath += ("&ACTION_FILE=" + this.paySystemHandler);
				if (this.paySystemMode)
				{
					connectPath += ("&PS_MODE=" + this.paySystemMode);
				}
			}

			return connectPath;
		},

		savePaySystemAction: function(e)
		{
			e.preventDefault();

			var analyticsLabel, type;
			if(this.paySystemId > 0)
			{
				analyticsLabel = 'salescenterUpdatePaymentSystem';
			}
			else
			{
				analyticsLabel = 'salescenterAddPaymentSystem';
			}
			if(this.paySystemMode && this.paySystemHandler === 'yandexcheckout')
			{
				type = this.paySystemMode;
			}
			else
			{
				type = this.paySystemHandler;
			}
			BX.ajax.runComponentAction(
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
			).then(
				function(response) {
					BX.removeClass(this.buttonSaveNode, 'ui-btn-wait');
					this.closeSlider();
				}.bind(this),
				function (response) {
					BX.removeClass(this.buttonSaveNode, 'ui-btn-wait');
					this.showError(response.errors);
				}.bind(this)
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
				descriptionFrame,
				isCash,
				canPrintCheck;

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

			canPrintCheck = innerDoc.getElementById('CAN_PRINT_CHECK');
			if (canPrintCheck && commonSettingsFormData.CAN_PRINT_CHECK)
			{
				canPrintCheck.checked = (commonSettingsFormData.CAN_PRINT_CHECK === 'Y');
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
		},

		getPaySystemFormData: function(slider)
		{
			var innerDoc, paySystemFormData;
			innerDoc = this.getSliderDocument(slider);
			paySystemFormData = this.getAllFormDataList(innerDoc);

			return paySystemFormData;
		},

		updateCommonSettingsForm: function(paySystemFormData)
		{
			var name, description, isCash, canPrintCheck;

			name = BX('NAME');
			description = BX('DESCRIPTION');
			isCash = BX('IS_CASH');
			canPrintCheck = BX('CAN_PRINT_CHECK');

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
				canPrintCheck.checked = !!(paySystemFormData.CAN_PRINT_CHECK && paySystemFormData.CAN_PRINT_CHECK === 'Y');
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

			var popup = new BX.PopupWindow(
				"paysystem_error_popup_" + BX.util.getRandomString(),
				null,
				{
					autoHide: false,
					draggable: false,
					closeByEsc: true,
					offsetLeft: 0,
					offsetTop: 0,
					zIndex: 10000,
					bindOptions: {
						forceBindPosition: true
					},
					titleBar: BX.message('SALESCENTER_SP_ERROR_POPUP_TITLE'),
					content: contentNode,
					buttons: [
						new BX.PopupWindowButton({
							'id': 'close',
							'text': BX.message('SALESCENTER_SP_BUTTON_CLOSE'),
							'events': {
								'click': function(){
									popup.close();
								}
							}
						})
					],
					events: {
						onPopupClose: function() {
							this.destroy();
						},
						onPopupDestroy: function() {
							popup = null;
						}
					}
				}
			);
			popup.show();
		},

		showError: function(errors)
		{
			var text = '';

			errors.forEach(function (error) {
				text += error.message + '<br>';
			});

			if(this.errorMessageNode && text)
			{
				this.errorMessageNode.parentNode.style.display = 'block';
				this.errorMessageNode.innerHTML = text;
			}
		},

		getSliderDocument: function(slider)
		{
			var sliderIframe, innerDoc;
			sliderIframe = slider.iframe;
			innerDoc = sliderIframe.contentDocument || sliderIframe.contentWindow.document;

			return innerDoc;
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

			this.popup = new BX.PopupWindow(
				"salescenter_sp_slider_close_confirmation",
				null,
				{
					autoHide: false,
					draggable: false,
					closeByEsc: false,
					offsetLeft: 0,
					offsetTop: 0,
					zIndex: event.slider.zIndex + 100,
					bindOptions: {
						forceBindPosition: true
					},
					titleBar: BX.message('SALESCENTER_SP_POPUP_TITLE'),
					content: BX.message('SALESCENTER_SP_POPUP_CONTENT'),
					buttons: [
						new BX.PopupWindowButton(
							{
								text : BX.message('SALESCENTER_SP_POPUP_BUTTON_CLOSE'),
								className : "ui-btn ui-btn-success",
								events: {
									click: BX.delegate(this.onCloseConfirmButtonClick.bind(this, 'close'))
								}
							}
						),
						new BX.PopupWindowButtonLink(
							{
								text : BX.message('SALESCENTER_SP_POPUP_BUTTON_CANCEL'),
								className : "ui-btn ui-btn-link",
								events: {
									click: BX.delegate(this.onCloseConfirmButtonClick.bind(this, 'cancel'))
								}
							}
						)
					],
					events: {
						onPopupClose: function()
						{
							this.destroy();
						}
					}
				}
			);
			this.popup.show();

			return false;
		},

		onCloseConfirmButtonClick: function(button)
		{
			this.popup.close();
			if (BX.SidePanel.Instance.getTopSlider())
			{
				BX.SidePanel.Instance.getTopSlider().focus();
			}

			if(button === "close")
			{
				this.isClose = true;
				BX.SidePanel.Instance.getTopSlider().close();
			}
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
				var type;
				if(this.paySystemMode && this.paySystemHandler === 'yandexcheckout')
				{
					type = this.paySystemMode;
				}
				else
				{
					type = this.paySystemHandler;
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
		}
	};
})(window);