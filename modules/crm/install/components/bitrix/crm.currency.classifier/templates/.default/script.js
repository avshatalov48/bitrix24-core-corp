BX.CurrencyClassifierClass = (function ()
{
	var lids = [];
	var currencies = [];
	var baseLanguage = null;
	var primaryFormMode = null;

	var CurrencyClassifierClass = function(parameters)
	{
		lids = parameters.lids;
		currencies = parameters.currencies;
		baseLanguage = parameters.baseLanguage;
		primaryFormMode = parameters.primaryFormMode;

		this.changeMode(false);
		this.bindElements(parameters);
		this.showErrors(parameters.errors);
		this.getCurrenciesList(parameters);
		this.fillFields(parameters);
		this.initSlider(parameters);
	};

	CurrencyClassifierClass.prototype.changeMode = function(fromSwitcher)
	{
		BX('form_mode_switcher').setAttribute('disabled', true);

		var targetFormMode = BX('target_form_mode').value;
		if (targetFormMode === 'ADD')
		{
			BX('target_form_mode').value = 'EDIT';
			BX('current_form_mode').value = 'ADD';

			if (fromSwitcher)
			{
				BX('add_form').style.opacity = 0;
				CurrencyClassifierClass.fadeOut(BX('edit_form'), BX('add_form'));
			}
			else
			{
				BX('add_form').className += ' crm-table-active';
				BX('form_mode_switcher').removeAttribute('disabled');
				CurrencyClassifierClass.setFormFocus('add');
			}
			BX('form_mode_switcher').innerHTML = BX.message('CRM_CURRENCY_CLASSIFIER_FORM_SWITCHER_MANUALLY');
		}
		else if (targetFormMode === 'EDIT')
		{
			BX('target_form_mode').value = 'ADD';
			BX('current_form_mode').value = 'EDIT';

			if (fromSwitcher)
			{
				BX('edit_form').style.opacity = 0;
				CurrencyClassifierClass.fadeOut(BX('add_form'), BX('edit_form'));
			}
			else
			{
				BX('edit_form').className += ' crm-table-active';
				BX('form_mode_switcher').removeAttribute('disabled');
				CurrencyClassifierClass.setFormFocus('edit');
			}
			BX('form_mode_switcher').innerHTML = BX.message('CRM_CURRENCY_CLASSIFIER_FORM_SWITCHER_CLASSIFIER');
		}
	};

	CurrencyClassifierClass.prototype.bindElements = function(parameters)
	{
		var separators = parameters.separators;
		var formatTemplates = parameters.formatTemplates;
		var existingCurrencies = parameters.existingCurrencies;
		var isFramePopup = parameters.isFramePopup;
		var pathToCurrencyList = parameters.pathToCurrencyList;

		if (isFramePopup)
			CurrencyClassifierClass.slider.bindClose(BX('cancel'));
		else
			BX.bind(BX('cancel'), 'click', function()
			{
				window.top.location = pathToCurrencyList;
			});

		BX.bind(BX('add_classifier_currency_id'), 'change', function ()
		{
			CurrencyClassifierClass.prototype.fillFields({
				index: BX('add_classifier_currency_id').value,
				lastIndex: BX('add_sym_code').value
			});
		});

		BX.bind(BX('add_classifier_currency_needle'), 'keyup', function ()
		{
			CurrencyClassifierClass.prototype.getCurrenciesList({
				index: BX('add_classifier_currency_id').value,
				lastIndex: BX('add_sym_code').value,
				needle: BX('add_classifier_currency_needle').value
			});
		});

		BX.bind(BX('form_mode_switcher'), 'click', function()
		{
			CurrencyClassifierClass.prototype.changeMode(true);
		});

		BX.bind(BX('edit_sym_code'), 'keyup', function()
		{
			CurrencyClassifierClass.synchronizeSymCodeFields(BX('edit_sym_code'), BX('edit_nominal_sym_code'));
		});

		lids.forEach(function(lid)
		{
			var elements = {
				expandButton : BX('expand_button_' + lid),
				hiddenInputContent: BX('expandable_content_hidden_input_' + lid),
				content : BX('expandable_content_' + lid),
				thousandsVariant : BX('edit_thousands_variant_' + lid),
				thousandsSep : BX('edit_thousands_sep_' + lid),
				formatTemplate : BX('edit_format_template_' + lid),
				decPoint : BX('edit_dec_point_' + lid)
			};

			BX.bind(elements.expandButton, 'click', function()
			{
				if (CurrencyClassifierClass.isContentHidden(elements.hiddenInputContent))
					CurrencyClassifierClass.expandContent(elements.content, elements.hiddenInputContent, elements.expandButton);
				else
					CurrencyClassifierClass.hideContent(elements.content, elements.hiddenInputContent, elements.expandButton);
			});

			BX.bind(elements.thousandsVariant, 'change', function()
			{
				CurrencyClassifierClass.checkOwnThousandsVariant(elements);
				CurrencyClassifierClass.fillFormatTemplateByFields(elements, separators, formatTemplates);
			});

			BX.bind(elements.formatTemplate, 'change', function()
			{
				CurrencyClassifierClass.fillFieldsByFormatTemplate(elements, separators);
			});

			BX.bind(elements.decPoint, 'keyup', function()
			{
				CurrencyClassifierClass.fillFormatTemplateByFields(elements, separators, formatTemplates);
			});
		});

		var buttons = [BX('save'), BX('apply')];

		buttons.forEach(function(button)
		{
			BX.bind(button, 'click', function ()
			{
				var actionName = button.id;
				var isEditMode = BX('current_form_mode').value === 'EDIT';
				var currentCurrency = isEditMode ? BX('edit_sym_code').value : BX('add_sym_code').innerHTML;

				if (!existingCurrencies[currentCurrency] || (primaryFormMode === 'EDIT' && isEditMode))
				{
					BX(actionName).className += ' webform-small-button-wait';
					BX.submit(BX('currency_add'), actionName);
				}
				else
					CurrencyClassifierClass.prototype.showPopupWindow(actionName);
			});
		});
	};

	CurrencyClassifierClass.prototype.showErrors = function(errors)
	{
		var formMode = BX('current_form_mode').value.toLowerCase() + '_';

		Object.keys(errors).forEach(function(type)
		{
			Object.keys(errors[type]).forEach(function(upperName)
			{
				var name = upperName.toLowerCase();
				var lang = '';

				if (type !== 'GENERAL')
				{
					lang = '_' + type.toLowerCase();
					if (name !== 'full_name')
						if (CurrencyClassifierClass.isContentHidden(BX('expandable_content_hidden_input' + lang)))
							CurrencyClassifierClass.expandContent(BX('expandable_content' + lang), BX('expandable_content_hidden_input' + lang), BX('expand_button' + lang));
				}

				BX(formMode + name + lang).className += ' crm-field-error';

				var errorText = BX.create('div', {
					props: {
						className: 'crm-entity-widget-content-error-text',
						innerHTML: errors[type][upperName]
					}
				});

				BX(formMode + name + '_container' + lang).appendChild(errorText);
			});
		});
	};

	CurrencyClassifierClass.prototype.getCurrenciesList = function(parameters)
	{
		var needle = parameters.needle;
		var index = parameters.index;
		var lastIndex = parameters.lastIndex;

		var select = BX('add_classifier_currency_id');

		if (index === null || index === "")
			index = lastIndex;

		select.options.length = 0;

		Object.keys(currencies).forEach(function (key)
		{
			var haystack = currencies[key][baseLanguage.toUpperCase()]['FULL_NAME'];

			if (haystack.toLowerCase().indexOf(needle.toLowerCase()) !== -1)
			{
				var option = document.createElement("option");
				option.value = key;
				option.text = haystack;
				if (key === index)
					option.selected = true;
				select.appendChild(option);
			}
		});
	};

	CurrencyClassifierClass.prototype.fillFields = function(parameters)
	{
		var index = parameters.index;
		var lastIndex = parameters.lastIndex;

		if (index === null)
			index = lastIndex;

		var currency = currencies[index];

		BX('add_num_code').innerHTML = currency['NUM_CODE'];
		BX('add_sym_code').innerHTML = currency['SYM_CODE'];
		BX('add_hidden_sym_code').value = currency['SYM_CODE'];
		BX('add_nominal_sym_code').innerHTML = currency['SYM_CODE'];

		lids.forEach(function(lid)
		{
			var currencyLang = currency[lid.toUpperCase()];

			var decimals = parseInt(currencyLang['DECIMALS']);
			var separator = currencyLang['THOUSANDS_SEP'];

			var thousandsValue = Math.floor(Math.random() * (90)) + 10;
			var afterThousandsValue = Math.floor(Math.random() * (900)) + 100;
			var decimalValue = Math.floor(Math.random() * (9 * Math.pow(10, decimals - 1))) + Math.pow(10, decimals - 1);

			var replaceValue = thousandsValue + separator + afterThousandsValue + currencyLang['DEC_POINT'] + decimalValue;

			if (decimals === 0)
				replaceValue = thousandsValue + separator + afterThousandsValue;

			BX('add_full_name_' + lid).innerHTML = currencyLang['FULL_NAME'];
			BX('add_example_' + lid).innerHTML = currencyLang['FORMAT_STRING'].replace('#VALUE#', replaceValue);
		});
	};

	CurrencyClassifierClass.prototype.initSlider = function(parameters)
	{
		var isFramePopup = parameters.isFramePopup;
		var closeSlider = parameters.closeSlider;

		if (isFramePopup && closeSlider)
			CurrencyClassifierClass.slider.close();
	};

	CurrencyClassifierClass.prototype.showPopupWindow = function(actionName)
	{
		var popup = new BX.PopupWindow(
			"popup_window",
			null,
			{
				content: '<label class="popup-window-message">' + BX.message('CRM_CURRENCY_CLASSIFIER_FORM_POPUP_WINDOW_MESSAGE') + '</label>',
				closeByEsc: true,
				closeIcon: false,
				autoHide: true,
				titleBar: {
					content: BX.create("label", {
						html: BX.message('CRM_CURRENCY_CLASSIFIER_FORM_POPUP_WINDOW_TITLE'),
						'props': {'className': 'popup-window-title'}
					})
				},
				overlay: {backgroundColor: '#000', opacity: 25},
				draggable: false,
				events: {
					onPopupClose: function()
					{
						this.destroy();
					}
				},
				buttons: [
					new BX.PopupWindowButton({
						text: BX.message('CRM_CURRENCY_CLASSIFIER_FORM_POPUP_WINDOW_YES'),
						className: "popup-window-button-accept",
						events: {
							click: function()
							{
								BX(actionName).className += ' webform-small-button-wait';
								BX.submit(BX('currency_add'), actionName);
							}
						}
					}),
					new BX.PopupWindowButton({
						text: BX.message('CRM_CURRENCY_CLASSIFIER_FORM_POPUP_WINDOW_NO'),
						events: {
							click: function()
							{
								this.popupWindow.close();
							}
						}
					})
				]
			});

		popup.show();
	};

	CurrencyClassifierClass.setFormFocus = function(formMode)
	{
		var element = (formMode === 'add') ? BX('add_classifier_currency_needle') : BX('edit_sym_code');

		var length = element.value.length;
		element.focus();
		element.setSelectionRange(length, length);
	};

	CurrencyClassifierClass.fillFormatTemplateByFields = function(elements, separators, formatTemplates)
	{
		var thousandsVariantKey = elements.thousandsVariant.value;
		var decPointKey = elements.decPoint.value;

		Object.keys(separators).forEach(function(key)
		{
			if (separators[key] === decPointKey)
			{
				decPointKey = key;
			}
		});

		var formatTemplateValue = thousandsVariantKey + decPointKey;

		if (Object.keys(formatTemplates).indexOf(formatTemplateValue) === -1)
			elements.formatTemplate.value = '-';
		else
			elements.formatTemplate.value = formatTemplateValue;
	};

	CurrencyClassifierClass.fillFieldsByFormatTemplate = function(elements, separators)
	{
		if (elements.formatTemplate.value === '-')
			return;

		var thousandsVariantKey = elements.formatTemplate.value.substring(0, 1);
		var decPointKey = elements.formatTemplate.value.substring(1, 2);

		elements.thousandsVariant.value = thousandsVariantKey;
		elements.decPoint.value = separators[decPointKey];

		CurrencyClassifierClass.checkOwnThousandsVariant(elements);
	};

	CurrencyClassifierClass.checkOwnThousandsVariant = function(elements)
	{
		if (elements.thousandsVariant.value === 'OWN')
			elements.thousandsSep.removeAttribute('readonly');
		else
			elements.thousandsSep.setAttribute('readonly', true);
	};

	CurrencyClassifierClass.synchronizeSymCodeFields = function(editSymCode, editNominalSymCode)
	{
		if (editSymCode.value.length > 3)
			editSymCode.value = editSymCode.value.substring(0, 3);

		editNominalSymCode.innerHTML = editSymCode.value;
	};

	CurrencyClassifierClass.isContentHidden = function(hiddenInputContent)
	{
		return hiddenInputContent.value === 'N';
	};

	CurrencyClassifierClass.expandContent = function(content, hiddenInputContent, expandButton)
	{
		content.className += ' crm-expandable-content-active';
		expandButton.innerHTML = BX.message('CRM_CURRENCY_CLASSIFIER_FIELD_ALL_PARAMETERS_HIDE');
		hiddenInputContent.value = 'Y';
	};

	CurrencyClassifierClass.hideContent = function(content, hiddenInputContent, expandButton)
	{
		content.className = content.className.replace(' crm-expandable-content-active', '');
		expandButton.innerHTML = BX.message('CRM_CURRENCY_CLASSIFIER_FIELD_ALL_PARAMETERS_SHOW');
		hiddenInputContent.value = 'N';
	};

	CurrencyClassifierClass.fadeOut = function(fadeOutTarget, fadeInTarget)
	{
		var fadeOutInterval = setInterval(function()
		{
			if (!fadeOutTarget.style.opacity)
			{
				fadeOutTarget.style.opacity = 1;
			}
			if (fadeOutTarget.style.opacity < 0.05)
			{
				clearInterval(fadeOutInterval);
				fadeInTarget.className += ' crm-table-active';
				fadeOutTarget.className = fadeOutTarget.className.replace(' crm-table-active', '');
				CurrencyClassifierClass.fadeIn(fadeInTarget);
			}
			else
			{
				fadeOutTarget.style.opacity -= 0.05;
			}
		}, 20);
	};

	CurrencyClassifierClass.fadeIn = function(fadeInTarget)
	{
		var opacity = 0;
		var fadeInInterval = setInterval(function()
		{
			if (!fadeInTarget.style.opacity)
			{
				fadeInTarget.style.opacity = 0;
			}
			if (fadeInTarget.style.opacity > 0.95)
			{
				clearInterval(fadeInInterval);
				BX('form_mode_switcher').removeAttribute('disabled');
				CurrencyClassifierClass.setFormFocus(fadeInTarget.id.replace('_form', ''));
			}
			else
			{
				opacity += 0.05;
				fadeInTarget.style.opacity = opacity;
			}
		}, 20);
	};

	CurrencyClassifierClass.slider =
	{
		bindClose: function(element)
		{
			BX.bind(element, 'click', this.close);
		},
		close: function()
		{
			window.top.BX.SidePanel.Instance.close();
		}
	};

	return CurrencyClassifierClass;
})();