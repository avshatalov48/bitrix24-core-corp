;(function ()
{
	'use strict';

	BX.namespace('BX.Salescenter.Cashbox');

	BX.Salescenter.Cashbox.init = function(parameters)
	{
		BX.Salescenter.Cashbox.signedParameters = parameters.signedParameters;
		BX.Salescenter.Cashbox.form = parameters.form;
		BX.Salescenter.Cashbox.errorMessageNode = parameters.errorMessageNode;
		BX.Salescenter.Cashbox.container = parameters.container;
		BX.Salescenter.Cashbox.isProgress = false;
		BX.Salescenter.Cashbox.renderForm();
		BX.Salescenter.Cashbox.bindEvents();
		BX.Salescenter.Cashbox.saveButtonNode = document.getElementById('ui-button-panel-save');
		BX.Salescenter.Cashbox.closeButtonNode = document.getElementById('ui-button-panel-close');
		BX.Salescenter.Cashbox.deleteButtonNode = document.getElementById('ui-button-panel-remove');
		BX.Salescenter.Cashbox.cashboxId = parameters.cashboxId;
		BX.Salescenter.Cashbox.initialFormData = BX.Salescenter.Cashbox.getAllFormData();
	};

	BX.Salescenter.Cashbox.renderForm = function()
	{
		var form = BX.Salescenter.Cashbox.form;
		form.setContainer(BX.Salescenter.Cashbox.container);

		var paramsPage = BX.Salescenter.Cashbox.getPage('cashbox_params');
		var paramsSection = form.renderSection(BX.Salescenter.Form.getByName(form.config, 'parameters'));

		paramsPage.appendChild(paramsSection);

		var settingsPage = BX.Salescenter.Cashbox.getPage('settings');

		for(var index in form.config)
		{
			if(
				index > 0 &&
				form.config.hasOwnProperty(index) &&
				form.config[index].name.indexOf('SETTINGS') >= 0 &&
				form.config[index].name.indexOf('OFD_SETTINGS') < 0
			)
			{
				settingsPage.appendChild(form.renderSection(form.config[index], true));
			}
		}

		// move vat section into tax section
		var vatSection = document.getElementById('cashbox-settings-SETTINGS_VAT');
		var taxSection = document.getElementById('cashbox-settings-SETTINGS_TAX');
		if(vatSection && taxSection)
		{
			var vatSectionTitle = vatSection.firstChild;
			BX.addClass(vatSectionTitle, 'ui-color-light salescenter-angle-icon-after');
			var angle = document.createElement('div');
			BX.addClass(angle, 'salescenter-angle-icon');
			vatSectionTitle.appendChild(angle);
			BX.removeClass(vatSection, 'salescenter-form-settings-section');
			vatSection.removeChild(vatSectionTitle.nextSibling);
			vatSection.style.display = 'none';
			vatSectionTitle.addEventListener('click', function()
			{
				BX.toggle(vatSection);
			});
			taxSection.appendChild(vatSection);
			taxSection.insertBefore(vatSectionTitle, vatSection);
		}

		BX.Salescenter.Cashbox.renderOfdPage();
	};

	BX.Salescenter.Cashbox.renderOfdPage = function()
	{
		var isFilled = false;
		var ofdPage = BX.Salescenter.Cashbox.getPage('ofd_settings');
		if (ofdPage)
		{
			while(ofdPage.firstChild) {
				ofdPage.removeChild(ofdPage.firstChild);
			}
			var form = BX.Salescenter.Cashbox.form;
			for(var index in form.config)
			{
				if(index > 0 && form.config.hasOwnProperty(index) && form.config[index].name.indexOf('OFD_SETTINGS') >= 0)
				{
					ofdPage.appendChild(form.renderSection(form.config[index], true));
					isFilled = true;
				}
			}

			if(!isFilled)
			{
				document.getElementById('salescenter-menu-page-ofd_settings').parentNode.style.display = 'none';
			}
			else
			{
				document.getElementById('salescenter-menu-page-ofd_settings').parentNode.style.display = 'block';
			}
		}
	};

	BX.Salescenter.Cashbox.bindEvents = function()
	{
		var ofdField = BX.Salescenter.Form.getByName(BX.Salescenter.Cashbox.form.fields, 'fields[OFD]');
		if(ofdField)
		{
			var input = BX.Salescenter.Cashbox.form.getFieldInput(ofdField);
			if(input)
			{
				BX.bind(input, 'change', BX.Salescenter.Cashbox.onChangeOfd);
			}
		}
		if(BX.SidePanel.Instance)
		{
			var slider = BX.SidePanel.Instance.getSliderByWindow(window);
			if(slider)
			{
				BX.addCustomEvent(slider, 'SidePanel.Slider:onClose', BX.Salescenter.Cashbox.onCloseSlider.bind(BX.Salescenter.Cashbox))
			}
		}
	};

	BX.Salescenter.Cashbox.onChangeOfd = function()
	{
		if(!BX.Salescenter.Cashbox.isProgress)
		{
			BX.Salescenter.Cashbox.startProgress();
			BX.ajax.runComponentAction('bitrix:salescenter.cashbox', 'getFormConfig', {
				mode: 'class',
				data: BX.Salescenter.Cashbox.form.getData(),
				signedParameters: BX.Salescenter.Cashbox.signedParameters,
			}).then(function(result)
			{
				BX.Salescenter.Cashbox.form.config = result.data.config;
				BX.Salescenter.Cashbox.form.fields = result.data.fields;
				BX.Salescenter.Cashbox.form.data = result.data.data;
				BX.Salescenter.Cashbox.renderOfdPage();
				BX.Salescenter.Cashbox.stopProgress();

			}).catch(function(reason)
			{
				BX.Salescenter.Cashbox.stopProgress();
				BX.Salescenter.Cashbox.showError(reason.errors.pop().message);
			})
		}
	};

	BX.Salescenter.Cashbox.save = function(event)
	{
		event.preventDefault();
		if(!BX.Salescenter.Cashbox.isProgress)
		{
			BX.Salescenter.Cashbox.startProgress();
			
			BX.ajax.runComponentAction('bitrix:salescenter.cashbox', 'save', {
				mode: 'class',
				data: BX.Salescenter.Cashbox.form.getData(),
				signedParameters: BX.Salescenter.Cashbox.signedParameters,
			}).then(function(result)
			{
				BX.Salescenter.Cashbox.stopProgress();
				BX.Salescenter.Cashbox.closeSlider();
			}).catch(function(reason)
			{
				BX.Salescenter.Cashbox.stopProgress();
				var messages = '';
				for(var i in reason.errors)
				{
					messages += reason.errors[i].message + '<br />';
				}
				BX.Salescenter.Cashbox.showError(messages);
			})
		}
	};

	BX.Salescenter.Cashbox.getPages = function()
	{
		return document.querySelectorAll('[data-cashbox-page]');
	};

	BX.Salescenter.Cashbox.getPage = function(name)
	{
		var pages = BX.Salescenter.Cashbox.getPages();
		for (var i=0; i < pages.length; i++)
		{
			if (pages[i].dataset.cashboxPage === name)
			{
				return pages[i];
			}
		}

		return null;
	};

	BX.Salescenter.Cashbox.showPage = function(page)
	{
		var pages = BX.Salescenter.Cashbox.getPages();
		for (var i=0; i < pages.length; i++)
		{
			if ((pages[i].dataset.cashboxPage === page || !pages[i].classList.contains('salescenter-cashbox-page-invisible')) &&
				!(pages[i].dataset.cashboxPage === page && !pages[i].classList.contains('salescenter-cashbox-page-invisible')))
			{
				BX.Salescenter.ComponentAnimation.smoothShowHide(pages[i]);

				if (pages[i].dataset.cashboxPage === page)
				{
					var title = pages[i].dataset.cashboxTitle;

					if (BX('pagetitle') && title !== '')
					{
						BX('pagetitle').innerHTML = title;
					}
				}
			}
		}
	};

	BX.Salescenter.Cashbox.showError = function(text)
	{
		if(BX.Salescenter.Cashbox.errorMessageNode)
		{
			document.getElementById('salescenter-menu-page-cashbox_params').click();
			BX.Salescenter.Cashbox.errorMessageNode.parentNode.style.display = 'block';
			BX.Salescenter.Cashbox.errorMessageNode.innerHTML = text;
		}
	};

	BX.Salescenter.Cashbox.hideError = function()
	{
		if(BX.Salescenter.Cashbox.errorMessageNode)
		{
			BX.Salescenter.Cashbox.errorMessageNode.parentNode.style.display = 'none';
		}
	};

	BX.Salescenter.Cashbox.startProgress = function()
	{
		BX.Salescenter.Cashbox.isProgress = true;
		if(!BX.Salescenter.Cashbox.getLoader().isShown())
		{
			BX.Salescenter.Cashbox.getLoader().show(BX.Salescenter.Cashbox.container);
		}
		BX.Salescenter.Cashbox.hideError();
	};

	BX.Salescenter.Cashbox.stopProgress = function()
	{
		BX.Salescenter.Cashbox.getLoader().hide();
		BX.Salescenter.Cashbox.isProgress = false;
		setTimeout(function()
		{
			BX.removeClass(BX.Salescenter.Cashbox.saveButtonNode, 'ui-btn-wait');
			BX.removeClass(BX.Salescenter.Cashbox.closeButtonNode, 'ui-btn-wait');
			if(BX.Salescenter.Cashbox.deleteButtonNode)
			{
				BX.removeClass(BX.Salescenter.Cashbox.deleteButtonNode, 'ui-btn-wait');
			}
		}, 100);
	};

	BX.Salescenter.Cashbox.getLoader = function()
	{
		if(!this.loader)
		{
			this.loader = new BX.Loader({size: 150});
		}

		return this.loader;
	};

	BX.Salescenter.Cashbox.remove = function(event)
	{
		event.preventDefault();
		if(BX.Salescenter.Cashbox.cashboxId > 0)
		{
			if(!BX.Salescenter.Cashbox.isProgress)
			{
				if(confirm(BX.message('SC_CASHBOX_DELETE_CONFIRM')))
				{
					BX.Salescenter.Cashbox.startProgress();
					BX.ajax.runComponentAction('bitrix:salescenter.cashbox', 'delete', {
						mode: 'class',
						data: {
							id: BX.Salescenter.Cashbox.cashboxId,
						},
					}).then(function()
					{
						BX.Salescenter.Cashbox.stopProgress();
						BX.Salescenter.Cashbox.closeSlider();
					}).catch(function(reason)
					{
						BX.Salescenter.Cashbox.stopProgress();
						BX.Salescenter.Cashbox.showError(reason.errors.pop().message);
					})
				}
			}
		}
	};

	BX.Salescenter.Cashbox.closeSlider = function()
	{
		var savedInput = document.getElementById('salescenter-form-is-saved');
		if(savedInput)
		{
			savedInput.value = 'y';
		}
		if(BX.SidePanel.Instance)
		{
			BX.SidePanel.Instance.getTopSlider().close();
		}
	};

	BX.Salescenter.Cashbox.openHelper = function(event, url)
	{
		if(top.BX.Helper)
		{
			top.BX.Helper.show(url);
		}
		if(event)
		{
			event.preventDefault();
		}
	};

	BX.Salescenter.Cashbox.onCloseSlider = function(event)
	{
		var savedInput = document.getElementById('salescenter-form-is-saved');
		if(savedInput && savedInput.value === 'y')
		{
			return true;
		}
		var formData = this.getAllFormData();
		if (this.initialFormData === formData || this.isClose === true)
		{
			this.isClose = false;
			return false;
		}

		event.action = false;

		if(this.popup)
		{
			this.popup.destroy();

		}

		this.popup = new BX.PopupWindow(
			"salescenter_slider_close_confirmation",
			null,
			{
				autoHide: false,
				draggable: false,
				closeByEsc: false,
				offsetLeft: 0,
				offsetTop: 0,
				zIndex: event.slider.zIndex + 100,
				bindOptions: { forceBindPosition: true },
				titleBar: BX.message('SCP_POPUP_TITLE'),
				content: BX.message('SCP_POPUP_CONTENT'),
				buttons: [
					new BX.PopupWindowButton(
						{
							text : BX.message('SCP_POPUP_BUTTON_CLOSE'),
							className : "ui-btn ui-btn-success",
							events: { click: BX.delegate(this.onCloseConfirmButtonClick.bind(this, 'close')) }
						}
					),
					new BX.PopupWindowButtonLink(
						{
							text : BX.message('SCP_POPUP_BUTTON_CANCEL'),
							className : "ui-btn ui-btn-link",
							events: { click: BX.delegate(this.onCloseConfirmButtonClick.bind(this, 'cancel')) }
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
	};

	BX.Salescenter.Cashbox.onCloseConfirmButtonClick = function(button)
	{
		this.popup.close();

		if(button === "close")
		{
			this.isClose = true;
			BX.SidePanel.Instance.getTopSlider().close();
		}
	};

	BX.Salescenter.Cashbox.getAllFormData = function()
	{
		var formNode = document.getElementsByTagName('form');

		if (formNode && formNode.length > 0)
		{
			var prepared = BX.ajax.prepareForm(formNode[0]),
				i;

			for (i in prepared.data)
			{
				if (prepared.data.hasOwnProperty(i) && i === '')
				{
					delete prepared.data[i];
				}
			}

			return !!prepared && prepared.data ? JSON.stringify(prepared.data) : '';
		}

		return '';
	};

})();