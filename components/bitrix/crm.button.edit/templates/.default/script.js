var CrmButtonEditor = function(params) {

	this.init = function (params)
	{
		this.id = params.id;
		this.isFrame = params.isFrame;
		this.isSaved = params.isSaved;
		this.reloadList = params.reloadList;
		this.setupWhatsAppLink = params.setupWhatsAppLink;
		this.pathToButtonList = params.pathToButtonList;
		this.defaultWorkTime = params.defaultWorkTime;
		this.dictionaryTypes = params.dictionaryTypes;
		this.mess = params.mess;
		this.canRemoveCopyright = params.canRemoveCopyright || false;
		this.canUseMultiLines = params.canUseMultiLines || false;
		this.actionRequestUrl = params.actionRequestUrl;
		this.langs = params.langs || {};

		/* init button colors editing */
		this.colorEditor = new CrmButtonEditColors({
			caller: this,
			context: BX('BUTTON_COLOR_CONTAINER')
		});

		/* init widget editing */
		this.widgetManager = new CrmButtonEditWidgetManager({
			caller: this,
			context: BX('WIDGET_CONTAINER'),
			dictionaryPathEdit: params.dictionaryPathEdit,
			linesData: params.linesData
		});

		/* init location changing */
		this.locationManager = new CrmButtonEditLocation({
			caller: this,
			context: BX('LOCATION_CONTAINER')
		});

		/* init button view */
		this.locationManager = new CrmButtonEditButton({
			caller: this,
			context: BX('BUTTON_VIEW_CONTAINER')
		});

		/* init hello */
		this.hello = new CrmButtonEditHello({
			caller: this,
			context: BX('HELLO_CONTAINER')
		});

		this.initCopy();

		this.mainForm = BX('crm_button_main_form');
		this.subForm = BX('crm_button_sub_form');
		BX.bind(this.mainForm, 'submit', BX.proxy(this.copySubFormDataToMainForm, this));

		this.initButtons();
		this.initToolTips();
		this.initLanguages();

		if(!this.canRemoveCopyright)
		{
			BX.bind(BX('COPYRIGHT_REMOVED_CONT'), 'click', BX.proxy(function(e) {
				if (BX.type.isNotEmptyString(params.showWebformRestrictionPopup))
				{
					e.preventDefault();
					eval(params.showWebformRestrictionPopup);
					return false;
				}
			}, this));
		}

		if(!this.canUseMultiLines)
		{
			BX.bind(BX('USE_MULTI_LINES'), 'click', BX.proxy(function(e) {
				if (BX.type.isNotEmptyString(params.showWebformRestrictionPopup))
				{
					e.preventDefault();
					eval(params.showMultilinesRestrictionPopup);
					return false;
				}
			}, this));
		}
	};

	this.initButtons = function()
	{
		var _this = this;
		var buttonCont = BX('BUTTON_COLOR_CONTAINER');
		this.loadedButtonCount = buttonCont.querySelectorAll('[data-b24-crm-button-widget]').length;
		this.blockNode = buttonCont.querySelector('[data-b24-crm-button-block]');
		this.blockInnerNode = buttonCont.querySelector('[data-b24-crm-button-block-inner]');
		var WhatsAppSetupNodes = BX('WIDGET_CONTAINER').querySelector('[data-bx-crm-button-item-channel-setup-whatsapp]');
		BX.bind(this.blockNode, 'mouseover', function (e) {
			_this.showButtons();
		});
		BX.bind(this.blockNode, 'mouseout', function (e) {
			_this.hideButtons();
		});


		var save = BX('CRM_BUTTON_SAVE');
		var apply = BX('CRM_BUTTON_APPLY');
		BX.bind(save, 'click', this.onSubmitButtonClick.bind(this, save));
		BX.bind(apply, 'click', this.onSubmitButtonClick.bind(this, apply));
		BX.bind(WhatsAppSetupNodes, 'click',this.openWhatsAppSetup.bind(this))
	};

	this.openWhatsAppSetup = function()
	{
		BX.SidePanel.Instance.open(this.setupWhatsAppLink, {width: 996, allowChangeHistory: false});
	}

	this.onSubmitButtonClick = function(node)
	{
		setTimeout(function () {
			node.disabled = true;
		}, 50);
		BX.addClass(node, 'ui-btn-wait');
	};

	this.initLanguages = function()
	{
		this.languageContext = BX('CRM_BUTTON_LANGUAGES');
		if (!this.languageContext)
		{
			return;
		}

		BX.bind(this.languageContext, 'click', BX.proxy(this.openLanguagePopup, this));
	};

	this.openLanguagePopup = function()
	{
		var langs = this.langs;
		var items = [];
		var _this = this;
		for (var lang in langs)
		{
			(function(lang) {
				items.push({
					text: langs[lang].NAME + (langs[lang].IS_BETA ? ", beta" : ""),
					onclick: function(event, item)
					{
						this.close();
						_this.changeLanguage(lang, langs[lang].NAME);
					}
				});
			})(lang);
		}

		BX.PopupMenu.show(
			"crm-button-language-popup",
			this.languageContext,
			items,
			{
				offsetTop:10,
				offsetLeft:0
			}
		);
	};

	this.changeLanguage = function(languageId, languageName)
	{
		var inputNode = this.languageContext.querySelector('[data-langs-input]');
		var textNode = this.languageContext.querySelector('[data-langs-text]');
		BX.removeClass(this.languageContext, inputNode.value);
		BX.addClass(this.languageContext, languageId);
		inputNode.value = languageId;
		textNode.innerText = languageName;
		textNode.title = languageName;
	};

	this.showButtons = function()
	{
		BX.addClass(this.blockNode, 'b24-crm-button-block-active');
		BX.addClass(this.blockNode, 'b24-crm-button-block-active-' + this.loadedButtonCount);
		BX.removeClass(this.blockInnerNode, 'b24-crm-button-animate-' + this.loadedButtonCount);
	};

	this.hideButtons = function()
	{
		BX.removeClass(this.blockNode, 'b24-crm-button-block-active');
		BX.removeClass(this.blockNode, 'b24-crm-button-block-active-' + this.loadedButtonCount);
		BX.addClass(this.blockInnerNode, 'b24-crm-button-animate-' + this.loadedButtonCount);
	};

	this.copySubFormDataToMainForm = function (e)
	{
		BX.convert.nodeListToArray(this.subForm.elements).forEach(function (element) {

			var name = element.name;
			var value = '';
			var copiedElement = this.mainForm.querySelector('[name="' + name + '"]');
			if(!copiedElement)
			{
				copiedElement = document.createElement('INPUT');
				copiedElement.type = 'hidden';
				copiedElement.name = name;
				this.mainForm.appendChild(copiedElement);
			}

			switch (element.type)
			{
				case 'radio':
				case 'checkbox':
					var sourceElement = this.subForm.querySelector('[name="' + name + '"]:checked');
					if(sourceElement)
					{
						value = sourceElement.value;
					}
					break;
				default:
					value = element.value;
			}

			copiedElement.value = value;

		}, this);

		return true;
	};

	this.initToolTips = function(context)
	{
		context = context || document.body;
		BX.UI.Hint.init(context);
	};

	this.initCopy = function ()
	{
		var context = BX('SCRIPT_CONTAINER');
		if(!context)
		{
			return;
		}

		var buttonAttribute = 'data-bx-webform-script-copy-btn';
		var copyButton = context.querySelector('[' + buttonAttribute + ']');
		var copyButtonText = context.querySelector('[data-bx-webform-script-copy-text]');
		if(!copyButton || !copyButtonText)
		{
			return;
		}

		BX.clipboard.bindCopyClick(copyButton, {text: copyButtonText, offsetLeft: 30});
	};

	this.appendTemplateNode = function(templateId, containerNode, replaceData)
	{
		return CrmButtonEditTemplate.appendNode(templateId, containerNode, replaceData);
	};

	this.getTemplateNode = function(templateId, replaceData)
	{
		return CrmButtonEditTemplate.getNode(templateId, replaceData);
	};

	this.sendActionRequest = function(action, sendData, callbackSuccess, callbackFailure)
	{
		callbackSuccess = callbackSuccess || null;
		callbackFailure = callbackFailure || BX.proxy(this.showErrorPopup, this);

		sendData = sendData || {};
		var needPreparePost = true;
		if (sendData instanceof FormData)
		{
			sendData.append('action', action);
			sendData.append('button_id', this.id);
			sendData.append('sessid', BX.bitrix_sessid());
			needPreparePost = false;
		}
		else
		{
			sendData.action = action;
			sendData.button_id = this.id;
			sendData.sessid = BX.bitrix_sessid();
		}

		BX.ajax({
			url: this.actionRequestUrl,
			method: 'POST',
			data: sendData,
			timeout: 30,
			dataType: 'json',
			processData: true,
			preparePost: needPreparePost,
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
	};

	this.showErrorPopup = function (data)
	{
		data = data || {};
		var text = data.text || this.mess.errorAction;
		var popup = BX.PopupWindowManager.create(
			'crm_button_edit_error',
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
				events: {click: function(){this.popupWindow.close();}}
			})
		]);
		popup.setContent('<span class="crm-button-edit-warning-popup-alert">' + text + '</span>');
		popup.show();
	};

	this.init(params);
};

var CrmButtonEditTemplate = {

	appendNode: function(templateId, containerNode, replaceData)
	{
		var templateNode = CrmButtonEditTemplate.getNode(templateId, replaceData);
		if (!templateNode)
		{
			return null;
		}
		containerNode.appendChild(templateNode);

		return templateNode;
	},

	getNode: function(templateId, replaceData)
	{
		var templateNode = BX('template-crm-button-' + templateId);
		if (!templateNode)
		{
			return null;
		}

		var addNode = document.createElement('div');
		var templateText = templateNode.innerHTML;
		if (replaceData)
		{
			for (var i in replaceData)
			{
				templateText = templateText.replace(new RegExp('%' + i + '%', 'g'), replaceData[i]);
			}
		}
		addNode.innerHTML = templateText;
		addNode = addNode.children[0];

		return addNode;
	}
};

function CrmButtonEditActivationManager(params)
{
	this.attributeItem = 'data-bx-crm-button-item';
	this.attributeActive = 'data-bx-crm-button-item-active';
	this.attributeActiveValue = 'data-bx-crm-button-item-active-val';
	this.attributeChannelSetup = 'data-bx-crm-button-item-channel-setup';
	// this.attributeChannelSetupWhatsApp = 'data-bx-crm-button-item-channel-setup-whatsapp';

	this.context = params.context;

	this.init();
}
CrmButtonEditActivationManager.prototype =
{
	init: function()
	{
		var nodeList = this.context.querySelectorAll('[' + this.attributeActive + ']');
		nodeList = Array.prototype.slice.call(nodeList);
		nodeList.forEach(function (node) {
			BX.bind(node, 'click', BX.delegate(function (){
				this.toggleActive(node);
			}, this));
		}, this);

		var channelSetupNodes = this.context.querySelectorAll('[' + this.attributeChannelSetup + ']');
		channelSetupNodes = Array.prototype.slice.call(channelSetupNodes);
		channelSetupNodes.forEach(function (node) {
			if (node.getAttribute(this.attributeChannelSetup))
			{
				BX.bind(node, 'click', function (e) {
					e.preventDefault();
					BX.SidePanel.Instance.open(this.href, {width: 996, allowChangeHistory: false});
				});
			}
		}, this);

		// var WhatsAppSetupNodes = this.context.querySelector('[' + this.attributeChannelSetupWhatsApp + ']');
		//
		// 	BX.bind(WhatsAppSetupNodes, 'click', function (e) {
		// 		e.preventDefault();
		// 		BX.SidePanel.Instance.open('/contact_center/connector/?ID=whatsappbytwilio', {width: 996, allowChangeHistory: false});
		// 	});
	},

	toggleActive: function(node)
	{
		var itemClass = 'crm-button-edit-channel-lines-container-active';
		var activeClassOn = 'crm-button-edit-channel-lines-title-on';
		var activeClassOff = 'crm-button-edit-channel-lines-title-off';

		var type = node.getAttribute(this.attributeActive);
		var itemNode = this.context.querySelector('[' + this.attributeItem + '="' + type + '"]');
		var valueNode = this.context.querySelector('[' + this.attributeActiveValue + '="' + type + '"]');
		var isActive = BX.hasClass(node, activeClassOn);
		if(isActive)
		{
			BX.addClass(node, activeClassOff);
			BX.removeClass(node, activeClassOn);
			BX.removeClass(itemNode, itemClass);

			valueNode.value ='N';
		}
		else
		{
			BX.addClass(node, activeClassOn);
			BX.removeClass(node, activeClassOff);
			BX.addClass(itemNode, itemClass);

			valueNode.value ='Y';
		}
	}
};

function CrmButtonEditPageManager(params)
{
	this.attributePages = 'data-crm-button-pages';
	this.attributePagesList = 'data-crm-button-pages-list';
	this.attributePagesPage = 'data-crm-button-pages-page';
	this.attributePagesAdd = 'data-crm-button-pages-btn-add';
	this.attributePagesDel = 'data-crm-button-pages-btn-del';

	this.mainObject = params.mainObject;
	this.pageChangeHandler = params.pageChangeHandler;
}
CrmButtonEditPageManager.prototype =
{
	initPages: function(context, replaceData)
	{
		var pageNodeList = context.querySelectorAll('['	+ this.attributePagesList + ']');
		pageNodeList = BX.convert.nodeListToArray(pageNodeList);
		pageNodeList.forEach(function (pageNode) {
			this.initPageButtons(pageNode, replaceData);
		}, this);
	},

	initPageButtons: function(pageNode, replaceData)
	{
		var pagesBtnAddNodeList = pageNode.querySelectorAll('['	+ this.attributePagesAdd + ']');
		pagesBtnAddNodeList = BX.convert.nodeListToArray(pagesBtnAddNodeList);
		pagesBtnAddNodeList.forEach(function (pagesBtnAddNode) {
			BX.bind(pagesBtnAddNode, 'click', BX.delegate(function(){
				this.onClickPageButton(pagesBtnAddNode, true, replaceData);
			}, this));
		}, this);

		var pagesBtnDelNodeList = pageNode.querySelectorAll('['	+ this.attributePagesDel + ']');
		pagesBtnDelNodeList = BX.convert.nodeListToArray(pagesBtnDelNodeList);
		pagesBtnDelNodeList.forEach(function (pagesBtnDelNode) {
			BX.bind(pagesBtnDelNode, 'click', BX.delegate(function(){
				this.onClickPageButton(pagesBtnDelNode, false, replaceData);
			}, this));
		}, this);

		var pagesInputNodeList = pageNode.querySelectorAll('input');
		pagesInputNodeList = BX.convert.nodeListToArray(pagesInputNodeList);
		pagesInputNodeList.forEach(function (pageInputNode) {
			BX.bind(pageInputNode, 'blur', BX.delegate(function(){
				this.onPageChange();
			}, this));
		}, this);
	},

	onPageChange: function()
	{
		if (!this.pageChangeHandler)
		{
			return;
		}

		setTimeout(this.pageChangeHandler, 400);
	},

	onClickPageButton: function(node, isAdd, replaceData)
	{
		if(isAdd)
		{
			//page
			var addNode;
			var pagesNode = BX.findParent(node, {attribute: this.attributePages});
			var templateNode = pagesNode.querySelector('script');
			if (templateNode)
			{
				addNode = document.createElement('div');
				addNode.innerHTML = templateNode.innerHTML;
				addNode = addNode.children[0];
			}
			else
			{
				addNode = this.mainObject.getTemplateNode('page', replaceData);
			}

			this.initPageButtons(addNode, replaceData);

			var listNode = BX.findParent(node, {attribute: this.attributePagesList});
			listNode.appendChild(addNode);
		}
		else
		{
			var delNode = BX.findParent(node, {attribute: this.attributePagesPage});
			BX.remove(delNode);
		}

		this.onPageChange();
	}
};

function CrmButtonEditLocation(params)
{
	this.caller = params.caller;
	this.context = params.context;

	this.attributeItem = 'data-bx-crm-button-loc';
	this.attributeVal = 'data-bx-crm-button-loc-val';
	this.className = 'crm-button-edit-sidebar-button-position-block-active-';

	this.init();
}
CrmButtonEditLocation.prototype =
{
	init: function ()
	{
		this.list = this.context.querySelectorAll('[' + this.attributeItem + ']');
		this.list = BX.convert.nodeListToArray(this.list);

		this.list.forEach(function(locNode){
			BX.bind(locNode, 'click', BX.delegate(function () {
				this.list.forEach(this.deactivate, this);
				this.activate(locNode);
			}, this));
		}, this);
	},

	deactivate: function (locNode)
	{
		this.activate(locNode, true);
	},

	activate: function (locNode, isDeActivate)
	{
		isDeActivate = isDeActivate || false;

		var valNode = locNode.querySelector('[' + this.attributeVal + ']');
		var val = valNode.value;

		if(isDeActivate)
		{
			BX.removeClass(locNode, this.className + val);
		}
		else
		{
			BX.addClass(locNode, this.className + val);
		}
	}
};

function CrmButtonEditColors(params)
{
	this.caller = params.caller;
	this.context = params.context;

	this.attributeBlock = 'data-b24-crm-button-block-button';
	this.attributeBlockBorder = 'data-b24-crm-button-block-border';
	this.attributePulse = 'data-b24-crm-button-pulse';
	this.attributeCont = 'data-b24-crm-button-block-inner';
	this.attributeIconItem = 'data-b24-crm-button-icon';
	this.classNameActive = 'b24-crm-button-inner-item-active';


	this.colorIconNode = BX('ICON_COLOR');
	this.colorBgNode = BX('BACKGROUND_COLOR');

	this.node = this.context.querySelector('[' + this.attributeBlock + ']');
	this.previewNode = this.node.querySelector('[' + this.attributeCont + ']');
	this.borderNode = this.context.querySelector('[' + this.attributeBlockBorder + ']');
	this.pulseNode = this.context.querySelector('[' + this.attributePulse + ']');

	var colorIconNodeList = this.node.querySelectorAll('[' + this.attributeIconItem + ']');
	colorIconNodeList = BX.convert.nodeListToArray(colorIconNodeList);
	this.previewItems = colorIconNodeList.map(function (iconNode) {
		return {
			'type': iconNode.getAttribute(this.attributeIconItem),
			'node': iconNode,
			'iconNode': iconNode.querySelector('path')
		};
	}, this);

	this.init();
}
CrmButtonEditColors.prototype =
{
	init: function()
	{
		this.initColorPicker();
	},

	changeActiveState: function(type, isActive)
	{
		this.previewItems.forEach(function (item) {

			if(item.type != type)
			{
				return;
			}

			if(isActive)
			{
				BX.addClass(item.node, this.classNameActive);
			}
			else
			{
				BX.removeClass(item.node, this.classNameActive);
			}

		}, this);

		this.previewNode.style.background = this.colorBgNode.value;
		this.previewItems.forEach(function (item) {
			item.iconNode.setAttribute('fill', this.colorIconNode.value);
		}, this);
	},

	updateButtonColors: function()
	{
		var bg = this.colorBgNode.value ? this.colorBgNode.value : '#00AEEF';
		this.previewNode.style.background = bg;
		this.borderNode.style.background = bg;
		this.pulseNode.style.border = '1px solid ' + bg;

		this.previewItems.forEach(function (item) {
			item.iconNode.setAttribute(
				'fill',
				this.colorIconNode.value ? this.colorIconNode.value : '#FFFFFF'
			);
		}, this);
	},

	showColorPicker: function(inputElement, bindElement)
	{
		bindElement = bindElement || inputElement;

		this.picker.close();
		this.picker.open({
			'defaultColor': '',
			'allowCustomColor': true,
			'bindElement': bindElement,
			'onColorSelected': this.onColorPicked.bind(this, inputElement)
		});
	},

	onColorPicked: function(element, color)
	{
		if (!color)
		{
			color = '';
		}

		element.value = color;
		var colorBox = BX.nextSibling(element);
		if (colorBox) {
			colorBox.style.background = color;
		}
		BX.fireEvent(element, 'change');
	},

	onColorInputChange: function(element)
	{
		var colorBox = BX.nextSibling(element);
		if (colorBox)
		{
			colorBox.style.background = element.value;
			this.updateButtonColors();
		}
	},

	initColorPicker: function()
	{
		this.picker = new BX.ColorPicker({'popupOptions': {
			'offsetLeft': 15,
			'offsetTop': 5
		}});

		var inputList = this.context.querySelectorAll('[data-web-form-color-picker]');
		inputList = BX.convert.nodeListToArray(inputList);
		for(var i in inputList)
		{
			var inputCtrl = inputList[i];
			var colorBox = BX.nextSibling(inputCtrl);

			var bindedClickHandler = this.showColorPicker.bind(this, inputCtrl, colorBox);
			BX.bind(colorBox, 'click', bindedClickHandler);
			BX.bind(inputCtrl, 'click', bindedClickHandler);
			BX.bind(inputCtrl, "focus", bindedClickHandler);

			BX.bind(inputCtrl, "bxchange", this.onColorInputChange.bind(this, inputCtrl));
			BX.fireEvent(inputCtrl, 'change');
		}
	}

};

function CrmButtonEditWidgetManager(params)
{
	this.caller = params.caller;
	this.context = params.context;

	this.dictionaryPathEdit = params.dictionaryPathEdit;
	this.linesData = params.linesData;

	this.attributeSelect = 'data-bx-crm-button-widget-select';
	this.attributeButtonEdit = 'data-bx-crm-button-widget-btn-edit';
	this.attributeSettingsButton = 'data-crm-button-item-settings-btn';
	this.attributeSettings = 'data-crm-button-item-settings';
	this.attributeSettingsWTButton = 'data-crm-button-item-settings-wt-btn';
	this.attributeSettingsWTText = 'data-crm-button-item-settings-wt-txt';
	this.attributeSettingsWTDefText = 'data-crm-wt-def';
	this.attributeSettingsWT = 'data-crm-button-item-settings-wt';
	this.attributeWorkTime = 'data-crm-button-item-worktime';
	this.attributeWorkTimeButton = 'data-crm-button-item-worktime-btn';
	this.attributeWorkTimeActionRule = 'data-crm-button-item-worktime-action-rule';
	this.attributeWorkTimeActionText = 'data-crm-button-item-worktime-action-text';

	this.init();
}
CrmButtonEditWidgetManager.prototype =
{
	init: function()
	{
		this.pageManager = new CrmButtonEditPageManager({'mainObject': this.caller});
		this.pageManager.initPages(this.context);
		this.initSelect();

		var lineContext = BX('items_openline_container');
		if (lineContext)
		{
			var lineManager = new CrmButtonEditLineManager({
				'caller': this,
				'context': lineContext,
				'linesData': this.linesData
			});
		}

		this.activationManager = new CrmButtonEditActivationManager({context: this.context});


		var settingsBtnNodeList = this.context.querySelectorAll('[' + this.attributeSettingsButton + ']');
		settingsBtnNodeList = BX.convert.nodeListToArray(settingsBtnNodeList);
		settingsBtnNodeList.forEach(function (settingsBtnNode) {
			BX.bind(settingsBtnNode, 'click', BX.proxy(function() {
				var type = settingsBtnNode.getAttribute(this.attributeSettingsButton);
				var settingsNode = this.context.querySelector('[' + this.attributeSettings + '="' + type + '"]');
				BX.toggleClass(settingsNode, 'crm-button-edit-channel-lines-display-options-open');
			}, this));
		}, this);

		var settingsWTBtnNodeList = this.context.querySelectorAll('[' + this.attributeSettingsWTButton + ']');
		settingsWTBtnNodeList = BX.convert.nodeListToArray(settingsWTBtnNodeList);
		settingsWTBtnNodeList.forEach(function (settingsBtnNode) {
			var type = settingsBtnNode.getAttribute(this.attributeSettingsWTButton);
			var settingsNode = this.context.querySelector('[' + this.attributeSettingsWT + '="' + type + '"]');
			BX.bind(settingsBtnNode, 'click', BX.proxy(function() {
				BX.toggleClass(settingsNode, 'crm-button-edit-channel-lines-display-options-open');
			}, this));
		}, this);

		this.initWorkTime();
	},

	listenWorkTimeChanges: function(type, element)
	{
		BX.bind(element, 'change', BX.proxy(function () {
			this.changeWorkTimeLabel(type);
		}, this))
	},

	changeWorkTimeLabel: function(type)
	{
		var settingsNode = this.context.querySelector('[' + this.attributeSettingsWT + '="' + type + '"]');
		var textNode = this.context.querySelector('[' + this.attributeSettingsWTText + '="' + type + '"]');
		var defText = textNode.getAttribute(this.attributeSettingsWTDefText);

		var enabledNode = settingsNode.querySelector('[data-crm-wt-enabled]');
		if (!enabledNode.checked)
		{
			textNode.innerText = defText;
		}
		else
		{
			var timeFromNode = settingsNode.querySelector('[data-crm-wt-time-from]');
			var timeToNode = settingsNode.querySelector('[data-crm-wt-time-to]');
			var dayCaptionNode = settingsNode.querySelector('[data-crm-wt-days-caption]');

			var dayNodes = settingsNode.querySelectorAll('[data-crm-wt-days]:checked');
			dayNodes = BX.convert.nodeListToArray(dayNodes);
			var text = timeFromNode.selectedOptions[0].textContent.trim();
			text += ' - ' + timeToNode.selectedOptions[0].textContent.trim();
			if (dayNodes.length > 0)
			{
				text += ', ' + dayCaptionNode.textContent.toLowerCase() + ': ';
				text += dayNodes.map(function (dayNode) {
					return dayNode.getAttribute('data-crm-wt-day-label');
				}).join(', ');
			}
			textNode.innerText = text;
		}
	},

	initSelect: function()
	{
		var selectNodeList = this.context.querySelectorAll('[' + this.attributeSelect + ']');
		selectNodeList = BX.convert.nodeListToArray(selectNodeList);
		selectNodeList.forEach(function (selectNode) {

			BX.bind(selectNode, 'change', BX.proxy(function(){
				this.onChangeSelect(selectNode);
			}, this));

			this.onChangeSelect(selectNode);

		}, this);
	},

	onChangeSelect: function(node)
	{
		var type = node.getAttribute(this.attributeSelect);
		var isSelected = !!node.value;
		this.caller.colorEditor.changeActiveState(type, isSelected);

		// buttons Edit and Add
		var buttonNode = this.context.querySelector('[' + this.attributeButtonEdit + '="' + type + '"' + ']');
		if(isSelected)
		{
			var item = this.dictionaryPathEdit[type];
			buttonNode.href = item.path.replace(item.id, node.value);
		}
		buttonNode.style.display = isSelected ? 'inline-block' : 'none';

		// Work time
		var workTime = null, connectors = null, phoneNumber = null, formFields = null;
		if (this.caller.dictionaryTypes[type])
		{
			this.caller.dictionaryTypes[type].LIST.forEach(function (item) {
				if (node.value == item.ID)
				{
					workTime = item.WORK_TIME;
					connectors = item.CONNECTORS;
					phoneNumber = item.PHONE_NAME;
					formFields = item.FORM_FIELDS;
				}
			}, this);
		}
		this.changeWorkTime(type, workTime);

		if (type == 'openline')
		{
			var channelNode = BX('openline_channels');
			channelNode.innerHTML = '';
			connectors.forEach(function (connector) {
				var node = document.createElement('span'),
					iconNode = document.createElement('i');
				node.title = connector.name;
				BX.addClass(node, 'crm-button-edit-channel-lines-social-item');
				BX.addClass(node, 'ui-icon');
				BX.addClass(node, 'ui-icon-service-' + connector.code.replace('.', '-'));
				node.appendChild(iconNode);

				channelNode.appendChild(node);
			}, this);
		}
		else if (type == 'callback')
		{
			var numberNode = BX('callback_phone_number');
			numberNode.innerText = phoneNumber;
		}
		else if (type == 'crmform')
		{
			var fieldsNode = BX('crmform_fields');
			fieldsNode.innerText = formFields.map(function (formField) {
				return formField.CAPTION;
			}).join(', ');
		}
	},

	initWorkTime: function ()
	{
		var enabledNodeList = this.context.querySelectorAll('[' + this.attributeWorkTimeButton + ']');
		enabledNodeList = BX.convert.nodeListToArray(enabledNodeList);
		enabledNodeList.forEach(function (enabledNode) {
			var type = enabledNode.getAttribute(this.attributeWorkTimeButton);
			var settingsNode = this.context.querySelector('[' + this.attributeWorkTime + '="' + type + '"]');

			// shadow
			BX.bind(enabledNode, 'change', BX.proxy(function() {
				var shadowNode = settingsNode.querySelector('[data-crm-wt-shadow]');
				shadowNode.style.display = enabledNode.checked ? 'none' : '';
			}, this));


			// caption change
			var timeFromNode = settingsNode.querySelector('[data-crm-wt-time-from]');
			var timeToNode = settingsNode.querySelector('[data-crm-wt-time-to]');
			var dayNodes = settingsNode.querySelectorAll('[data-crm-wt-days]');
			dayNodes = BX.convert.nodeListToArray(dayNodes);

			this.listenWorkTimeChanges(type, enabledNode);
			this.listenWorkTimeChanges(type, timeFromNode);
			this.listenWorkTimeChanges(type, timeToNode);
			dayNodes.forEach(function (dayNode) {
				this.listenWorkTimeChanges(type, dayNode);
			}, this);

			this.changeWorkTimeLabel(type);

			// actions
			var nodeAction = this.context.querySelector('[' + this.attributeWorkTimeActionRule + '="' + type + '"]');
			BX.bind(nodeAction, 'change', BX.proxy(function() {
				var node = this.context.querySelector('[' + this.attributeWorkTimeActionText + '="' + type + '"]');
				node.style.display = nodeAction.value == 'text' ? '' : 'none';
			}, this));

			BX.fireEvent(nodeAction, 'change');

		}, this);
	},

	changeWorkTime: function (type, workTime)
	{
		var tmplName = 'ITEMS[' + type + '][WORK_TIME]';
		var tmplId = 'ITEMS_' + type +'_WORK_TIME';

		var enabledNode = BX(tmplId + '_' + 'ENABLED');
		if (!enabledNode || enabledNode.checked)
		{
			return;
		}

		if (!workTime)
		{
			workTime = this.caller.defaultWorkTime;
		}

		BX(tmplId + '_' + 'TIME_ZONE').value = workTime.TIME_ZONE;
		BX(tmplId + '_' + 'TIME_FROM').value = workTime.TIME_FROM;
		BX(tmplId + '_' + 'TIME_TO').value = workTime.TIME_TO;
		BX(tmplId + '_' + 'HOLIDAYS').value = workTime.HOLIDAYS.join(',');
		var dayNodes = document.getElementsByName(tmplName + '[DAY_OFF][]');
		dayNodes = BX.convert.nodeListToArray(dayNodes);
		dayNodes.forEach(function (dayNode) {
			dayNode.checked = BX.util.in_array(dayNode.value, workTime.DAY_OFF);
		});
	}
};

function CrmButtonEditAvatarEditor(params)
{
	this.caller = params.caller;
	this.context = params.context;

	this.init();
}
CrmButtonEditAvatarEditor.prototype =
{
	init: function()
	{
		this.editButtonNode = this.context.querySelector('[data-crm-button-edit-avatar-edit]');
		BX.bind(this.editButtonNode, 'click', BX.delegate(this.show, this));
		this.avatarContainer = this.context.querySelector('[data-crm-button-edit-avatars]');
		this.attributeCarouselNext = 'data-crm-button-edit-avatar-next';
		this.attributeCarouselPrev = 'data-crm-button-edit-avatar-prev';
		this.attributeAvatar = 'data-crm-button-edit-avatar-edit';
		this.attributeAvatar = 'data-crm-button-edit-avatar-item';
		this.attributeAvatarRemove = 'data-remove';
		this.attributeFileId = 'data-file-id';
		this.attributePath = 'data-path';
		this.attributeView = 'data-view';

		this.getAllAvatarNodes().forEach(this.initAvatar, this);
		this.initCarousel();
	},

	createAvatarNode: function(fileId, path)
	{
		var avatarTemplate = BX('crm_button_edit_template_avatar');
		avatarTemplate = avatarTemplate.innerHTML;
		fileId = BX.util.htmlspecialchars(fileId);
		path = BX.util.htmlspecialchars(path);
		avatarTemplate = avatarTemplate
			.replace('%file_id%', fileId)
			.replace('%path%', path)
			.replace('%path%', path);

		var node = BX.create('DIV');
		node.innerHTML = avatarTemplate;
		node = node.children[0];

		return node;
	},

	initAvatar: function(node)
	{
		var nodeView = node.querySelector('[' + this.attributeView + ']');
		nodeView = nodeView || node;
		BX.bind(nodeView, 'click', BX.delegate(function () {
			this.selectAvatar(node);
		}, this));

		var fileId = node.getAttribute(this.attributeFileId);
		if (fileId)
		{
			var nodeRemove = node.querySelector('[' + this.attributeAvatarRemove + ']');
			BX.bind(nodeRemove, 'click', BX.delegate(function () {
				this.removeFile(fileId, node);
			}, this));
		}
	},

	selectAvatar: function (node)
	{
		var path = node.getAttribute(this.attributePath);
		BX.onCustomEvent(this, 'onSelect', [path]);
	},

	initCarousel: function ()
	{
		if (!this.carouselNextNode)
		{
			this.carouselNextNode = this.context.querySelector('[' + this.attributeCarouselNext + ']');
			this.carouselPrevNode = this.context.querySelector('[' + this.attributeCarouselPrev + ']');
			BX.bind(this.carouselNextNode, 'click', BX.delegate(function () {
				this.turnCarousel('next');
			}, this));
			BX.bind(this.carouselPrevNode, 'click', BX.delegate(function () {
				this.turnCarousel('prev');
			}, this));
		}

		var nodes = this.getUserAvatarNodes();
		var nodeWidth = Math.round(100 / nodes.length);
		nodes.forEach(function (node) {
			node.style.width = nodeWidth + '%';
		}, this);
		this.avatarContainer.style.width = 'calc(66px * ' + nodes.length + ')';
		this.turnCarousel('start');
	},

	turnCarousel: function (pos)
	{
		var nodes = this.getUserAvatarNodes();
		var val;
		var step = 66;
		var maxLeft = -step * (nodes.length - 3);
		var oldLeft = this.avatarContainer.style.left.toString();
		oldLeft = parseInt(oldLeft.replace('px', ''));
		if (isNaN(oldLeft)) oldLeft = 0;

		switch (pos)
		{
			case 'prev':
				val = oldLeft + step;
				break;
			case 'start':
				val = 0;
				break;
			case 'end':
				val = 100000;
				break;
			case 'next':
			default:
				val = oldLeft - step;
				break;
		}

		if (val >= 0)
		{
			val = 0;
		}
		else if (val < maxLeft)
		{
			val = maxLeft;
		}

		this.carouselPrevNode.style.display = (val == 0 || nodes.length <= 3) ? 'none' : '';
		this.carouselNextNode.style.display = (val == maxLeft || nodes.length <= 3) ? 'none' : '';

		this.avatarContainer.style.left = val + (val == 0 ? '' : 'px');
	},

	markCurrentAvatar: function (path)
	{
		var classCurrent = 'selected';
		this.getAllAvatarNodes().forEach(function (node) {
			var nodePath = node.getAttribute(this.attributePath);
			if (nodePath == path)
			{
				BX.addClass(node, classCurrent);
			}
			else
			{
				BX.removeClass(node, classCurrent);
			}
		}, this);

		this.getUserAvatarNodes().forEach(function (node, index) {
			if (index > 2 && BX.hasClass(node, classCurrent))
			{
				this.avatarContainer.insertBefore(
					node,
					this.avatarContainer.children[0]
				);
			}
		}, this);
	},

	getUserAvatarNodeByFileId: function (fileId)
	{
		return this.avatarContainer.querySelector('[' + this.attributeFileId + '="' + fileId + '"]');
	},

	getUserAvatarNodes: function ()
	{
		return BX.convert.nodeListToArray(
			this.avatarContainer.querySelectorAll('[' + this.attributeAvatar + ']')
		);
	},

	getAllAvatarNodes: function ()
	{
		return BX.convert.nodeListToArray(
			this.context.querySelectorAll('[' + this.attributeAvatar + ']')
		);
	},

	showLoader: function()
	{
		BX.addClass(this.editButtonNode, 'loader');
	},

	hideLoader: function()
	{
		BX.removeClass(this.editButtonNode, 'loader');
	},

	onFileAdded: function(data)
	{
		var node = this.createAvatarNode(data.fileId, data.filePath);
		if (this.avatarContainer.children.length > 0)
		{
			this.avatarContainer.insertBefore(node, this.avatarContainer.children[0]);
		}
		else
		{
			this.avatarContainer.appendChild(node);
		}

		this.initAvatar(node);
		this.initCarousel();
		this.selectAvatar(node);
		this.hideLoader();
	},

	onFileRemoved: function(data)
	{
		if (data.fileId && data.fileId > 0)
		{
			var node = this.getUserAvatarNodeByFileId(data.fileId);
		}

		if (data.error)
		{
			this.caller.showErrorPopup(data);
			if (node)
			{
				node.style.display = '';
			}
		}
		else
		{
			BX.remove(node);
		}

		this.initCarousel();
		this.hideLoader();
	},

	addFile: function(blob)
	{
		if (!blob || blob.size <= 0)
		{
			return;
		}

		this.showLoader();
		var fd = new FormData();
		fd.append('avatar_file', blob);
		this.caller.sendActionRequest(
			'addAvatarFile',
			fd,
			BX.proxy(this.onFileAdded, this),
			BX.proxy(function (data) {
				data = data || {error: true};
				this.onFileAdded(data);
			}, this)
		);

	},

	removeFile: function(fileId, node)
	{
		this.showLoader();
		node.style.display = 'none';
		this.caller.sendActionRequest('removeAvatarFile', {'fileId': fileId}, BX.proxy(this.onFileRemoved, this));
	},

	show: function()
	{
		if (!this.editor)
		{
			this.initEditor();
		}

		this.editor.show();
		BX.addCustomEvent(this.editor.popup, "onPopupClose",BX.proxy(this.onEditorPopupClose, this));
	},

	onEditorPopupClose: function()
	{
		BX.onCustomEvent(this, 'onClose', []);
		BX.removeCustomEvent(this.editor.popup, "onPopupClose",BX.proxy(this.onEditorPopupClose, this));
	},

	initEditor: function()
	{
		this.editor = new BX.AvatarEditor();
		BX.addCustomEvent(this.editor, "onApply", BX.delegate(function (blob) {
			if (!blob)
			{
				return;
			}

			this.addFile(blob);
		}, this));
	}
};

function CrmButtonEditHello(params)
{
	this.caller = params.caller;
	this.context = params.context;

	this.blockCounter = 1000;

	this.init();
}
CrmButtonEditHello.prototype =
{
	init: function()
	{
		this.pageManager = new CrmButtonEditPageManager({
			'mainObject': this.caller,
			'pageChangeHandler': BX.proxy(this.onPagesChange, this)
		});
		this.activationManager = new CrmButtonEditActivationManager({context: this.context});
		this.customHelloContainer = BX('HELLO_MY_CONTAINER');
		this.defaultHelloContainer = BX('HELLO_ALL_CONTAINER');

		// init add block button
		BX.bind(this.context.querySelector('[data-b24-crm-hello-add]'), 'click', BX.proxy(this.addBlock, this));

		// init existed blocks
		this.blockAttribute = 'data-b24-crm-hello-block';
		var existedBlocks = this.customHelloContainer.querySelectorAll('[' + this.blockAttribute + ']');
		existedBlocks = BX.convert.nodeListToArray(existedBlocks);
		existedBlocks.forEach(function (existedBlock) {
			this.initBlock(existedBlock, true, false);
		}, this);

		// init default block
		var defaultBlock = this.defaultHelloContainer.querySelector('[data-b24-crm-hello-block]');
		this.initBlock(defaultBlock, true, true);
		this.onPagesChange();

		// init mode selector
		this.modeSelector = this.context.querySelector('[data-b24-crm-hello-mode]');
		BX.bind(this.modeSelector, 'click', BX.proxy(this.changeMode, this));
	},

	changeMode: function ()
	{
		var isInclude = this.modeSelector.value == 'INCLUDE';
		BX('HELLO_ALL_CONTAINER').style.display = isInclude ? 'none' : '';
	},

	addBlock: function ()
	{
		var replaceData = {
			'id': this.blockCounter++,
			'target': 'HELLO[CONDITIONS]',
			'mode': 'INCLUDE'
		};
		var node = this.caller.appendTemplateNode('hello', this.customHelloContainer, replaceData);
		this.initBlock(node);
	},

	initBlock: function (node, isExisted, isCommon)
	{
		isExisted = isExisted || false;
		isCommon = isCommon || false;
		if (!isExisted)
		{
			this.caller.initToolTips(node);
		}

		var replaceData = {
			'target': 'HELLO[CONDITIONS]',
			'type': node.getAttribute(this.blockAttribute),
			'mode': isCommon ? 'EXCLUDE' : 'INCLUDE'
		};
		this.pageManager.initPageButtons(node, replaceData);
		BX.bind(node.querySelector('[data-b24-hello-btn-remove]'), 'click', BX.delegate(function () {
			this.removeBlock(node);
			this.onPagesChange();
		}, this));

		/* init edit name */
		var nameClassName = 'crm-button-edit-name-state';
		var nodeNameText = node.querySelector('[data-b24-hello-name-text]');
		var nodeNameInput = node.querySelector('[data-b24-hello-name-input]');
		BX.bind(node.querySelector('[data-b24-hello-name-btn-edit]'), 'click', BX.delegate(function () {
			BX.addClass(node, nameClassName);
		}, this));
		BX.bind(node.querySelector('[data-b24-hello-name-btn-apply]'), 'click', BX.delegate(function () {
			nodeNameText.innerText = nodeNameInput.value.trim();
			BX.removeClass(node, nameClassName);
		}, this));

		/* init edit text */
		var textClassName = 'crm-button-edit-description-state';
		var nodeTextText = node.querySelector('[data-b24-hello-text-text]');
		var nodeTextInput = node.querySelector('[data-b24-hello-text-input]');
		BX.bind(node.querySelector('[data-b24-hello-text-btn-edit]'), 'click', BX.delegate(function () {
			BX.addClass(node, textClassName);
		}, this));
		BX.bind(node.querySelector('[data-b24-hello-text-btn-apply]'), 'click', BX.delegate(function () {
			nodeTextText.innerText = nodeTextInput.value.trim();
			BX.removeClass(node, textClassName);
		}, this));

		/* init edit text */
		var iconNode = node.querySelector('[data-b24-hello-icon]');
		var iconButtonNode = node.querySelector('[data-b24-hello-icon-btn]');
		var iconInputNode = node.querySelector('[data-b24-hello-icon-input]');
		var iconClickHandler = function () {
			this.currentIconNode = iconNode;
			this.currentIconInputNode = iconInputNode;
			this.showAvatarPopup();
		};
		BX.bind(iconNode, 'click', BX.delegate(iconClickHandler, this));
		BX.bind(iconButtonNode, 'click', BX.delegate(iconClickHandler, this));
	},

	onSelectAvatar: function (path)
	{
		if (path)
		{
			this.currentIconNode.style['background-image'] = "url(" + path + ")";
			this.currentIconInputNode.value = path;
		}

		this.avatarPopup.close();
	},

	showAvatarPopup: function ()
	{
		if (!this.avatarPopup)
		{
			var contentNode = BX('crm_button_edit_avatar_upload');
			this.avatarEditor = new CrmButtonEditAvatarEditor({
				'caller': this.caller,
				'context': contentNode
			});
			BX.addCustomEvent(this.avatarEditor, 'onClose', BX.delegate(this.showAvatarPopup, this));
			BX.addCustomEvent(this.avatarEditor, 'onSelect', BX.delegate(this.onSelectAvatar, this));

			this.avatarPopup = BX.PopupWindowManager.create(
				'crm_button_edit_avatar',
				null,
				{
					autoHide: true,
					lightShadow: true,
					overlay: true,
					closeIcon: true,
					closeByEsc: true,
					angle: true,
					content: contentNode
				}
			);
		}

		this.avatarEditor.markCurrentAvatar(this.currentIconInputNode.value);
		this.avatarPopup.setBindElement(this.currentIconNode);
		this.avatarPopup.show();
	},

	removeBlock: function (node)
	{
		BX.remove(node);
	},

	onPagesChange: function ()
	{
		var excludedPagesNode = this.defaultHelloContainer.querySelector('[data-b24-hello-excluded-pages]');
		if (!excludedPagesNode)
		{
			return;
		}

		var pageNodes = this.customHelloContainer.querySelectorAll('[data-crm-button-pages-list] input');
		pageNodes = BX.convert.nodeListToArray(pageNodes);
		var pages = pageNodes.map(function (pageNode) {
			return pageNode.value;
		}, this);

		excludedPagesNode.innerText = pages.filter(function (page) {
			return !!page;
		}).join("\n");
	}
};

function CrmButtonEditButton(params)
{
	this.caller = params.caller;
	this.context = params.context;

	this.container = this.context.querySelector('[data-b24-crm-button-cont]');
	this.attributeItem = 'data-bx-crm-button-item';

	this.init();
}
CrmButtonEditButton.prototype =
{
	init: function()
	{
		BX.addClass(
			this.context.querySelector('[data-b24-crm-button-pulse]'),
			'b24-widget-button-pulse-animate'
		);

		/*
		BX.bind(
			this.context.querySelector('[data-b24-crm-button-block-button]'),
			'click',
			BX.proxy(this.toggle, this)
		);

		 this.shadow.init({
		 'caller': this,
		 'shadowNode': this.context.querySelector('[data-b24-crm-button-shadow]')
		 });
		*/

		this.animatedNodes = BX.convert.nodeListToArray(this.context.querySelectorAll('[data-b24-crm-button-icon]'));
		this.animate();
	},
	toggle: function()
	{
		var className = 'b24-widget-button-top';

		if (BX.hasClass(this.container, className))
		{
			BX.removeClass(this.container, className);
			this.shadow.hide();
		}
		else
		{
			BX.addClass(this.container, className);
			this.shadow.show();
		}
	},
	animate: function()
	{
		var className = 'b24-widget-button-icon-animation';
		var curIndex = 0;
		this.animatedNodes.forEach(function (node, index) {
			if (BX.hasClass(node, className)) curIndex = index;
			BX.removeClass(node, className);
		}, this);

		curIndex++;
		curIndex = curIndex < this.animatedNodes.length ? curIndex : 0;
		BX.addClass(this.animatedNodes[curIndex], className);

		if (this.animatedNodes.length > 1)
		{
			var _this = this;
			setTimeout(function () {_this.animate();}, 1500);
		}
	},
	shadow: {
		init: function(params)
		{
			this.c = params.caller;
			this.shadowNode = params.shadowNode;

			var _this = this;
			BX.bind(this.shadowNode, 'click', function (e) {
				_this.hide();
			});
			BX.bind(document, 'keyup', function (e) {
				e = e || window.e;
				if (e.keyCode == 27)
				{
					_this.hide();
				}
			});
		},
		show: function()
		{
			BX.addClass(this.shadowNode, 'b24-widget-button-shadow-show');
		},
		hide: function()
		{
			BX.removeClass(this.shadowNode, 'b24-widget-button-shadow-show');
		}
	}
};

function CrmButtonEditLineManager(params)
{
	this.caller = params.caller;
	this.context = params.context;
	this.linesData = params.linesData;
	this.lines = [];

	if (this.linesData && this.linesData.LIST.length > 0)
	{
		this.init();
	}
}
CrmButtonEditLineManager.prototype =
{
	init: function ()
	{
		this.linesData.LIST.forEach(function (item) {
			this.prepareLineData(item);
		}, this);

		this.attributeLine = 'data-line';
		this.externalIdNode = this.context.querySelector('[data-bx-external-id]');
		this.buttonAddNode = this.context.querySelector('[data-bx-add]');
		this.addHintNode = this.context.querySelector('[data-bx-add-desc]');
		this.listNode = this.context.querySelector('[data-bx-list-ext]');
		this.defListNode = this.context.querySelector('[data-bx-list-def]');

		if (this.buttonAddNode)
		{
			BX.bind(this.buttonAddNode, 'click', BX.proxy(this.createLine, this));
		}
		this.initLinesFromContainer(this.defListNode);
		this.initLinesFromContainer(this.listNode);
		this.actualizeButtonAddDisplay();
	},

	saveToHiddenFields: function ()
	{
		var ids = [];
		this.lines.forEach(function (line) {
			ids.push(line.getId());
		}, this);

		this.externalIdNode.value = ids.join(',');
	},

	initLinesFromContainer: function (containerNode)
	{
		if (!containerNode)
		{
			return;
		}

		var nodes = containerNode.querySelectorAll('[' + this.attributeLine + ']');
		nodes = BX.convert.nodeListToArray(nodes);
		nodes.forEach(function (node) {
			var id = node.getAttribute(this.attributeLine);
			var lineData = this.getLineDataById(id);
			this.initLine(node, lineData);
		}, this);
	},

	initLine: function (lineNode, lineData)
	{
		var line = new CrmButtonEditLine({
			caller: this,
			node: lineNode,
			data: lineData
		});
		this.lines.push(line);

		BX.addCustomEvent(line, 'load', BX.proxy(this.onLineLoad, this));
		BX.addCustomEvent(line, 'change', BX.proxy(this.onLineChange, this));
		BX.addCustomEvent(line, 'remove', BX.proxy(this.onLineRemove, this));
		line.load(lineData);

		return line;
	},

	createLine: function ()
	{
		var unused = this.getUnusedLinesData();
		if (unused.length == 0)
		{
			return null;
		}

		var lineData = unused[0];
		var lineNode = CrmButtonEditTemplate.getNode(
			'line',
			{}
		);

		this.initLine(lineNode, lineData);
		this.listNode.appendChild(lineNode);

		this.actualizeButtonAddDisplay();
		this.saveToHiddenFields();
	},

	actualizeButtonAddDisplay: function ()
	{
		if (this.addHintNode)
		{
			this.addHintNode.style.display = this.lines.length > 1 ? 'none' : '';
		}


		if (!this.buttonAddNode)
		{
			return;
		}

		var unused = this.getUnusedLinesData();
		this.buttonAddNode.style.display = (unused.length > 0 ? '' : 'none');
	},

	getUnusedLinesData: function ()
	{
		var ids = this.lines.map(function (line) {
			return line.getId();
		});

		return this.linesData.LIST.filter(function (item) {
			return !BX.util.in_array(item.ID, ids);
		});
	},

	prepareLineData: function (lineData)
	{
		var pathData = this.linesData.PATH_EDIT;
		lineData.PATH_EDIT = pathData.path.replace(pathData.id, lineData.ID);
	},

	getLineDataById: function (lineId)
	{
		var filtered = this.linesData.LIST.filter(function (data) {
			return data.ID == lineId;
		}, this);

		return filtered.length > 0 ? filtered[0] : null;
	},

	updateLineSelects: function ()
	{
		var unused = this.getUnusedLinesData();
		this.lines.forEach(function (line) {
			var lineData = this.getLineDataById(line.getId());
			if (!lineData)
			{
				return;
			}

			var items = BX.util.array_merge([lineData], unused);
			line.fillSelect(items);
		}, this);
	},

	onLineLoad: function (loadedLine)
	{
		this.updateLineSelects();
	},

	onLineChange: function (line, lineId)
	{
		var lineData = this.getLineDataById(lineId);
		if (!lineData)
		{
			return;
		}

		line.load(lineData);
		this.saveToHiddenFields();
	},

	onLineRemove: function (line)
	{
		this.lines = BX.util.deleteFromArray(this.lines, this.lines.indexOf(line));
		this.actualizeButtonAddDisplay();
		this.updateLineSelects();
		this.saveToHiddenFields();
	}
};

function CrmButtonEditLine(params)
{
	this.caller = params.caller;
	this.node = params.node;
	this.data = {};
	this.config = {};

	this.channels = [];

	this.init(params);
}
CrmButtonEditLine.prototype =
{
	getId: function ()
	{
		return this.data.ID;
	},

	initConfig: function (config)
	{
		config = config || '{}';
		try
		{
			config = JSON.parse(config);
		}
		catch (e){}

		if (!BX.type.isPlainObject(config))
		{
			config = {};
		}

		this.config.excluded = config.excluded || [];
	},

	init: function (params)
	{
		this.selectNode = this.node.querySelector('[data-line-list]');
		this.editButtonNode = this.node.querySelector('[data-line-edit]');
		this.addButtonNode = this.node.querySelector('[data-line-add]');
		this.removeButtonNode = this.node.querySelector('[data-line-remove]');
		this.channelsNode = this.node.querySelector('[data-line-channels]');

		this.initConfig(this.node.getAttribute('data-line-config'));
		this.fillDropDownControl(this.selectNode, [this.data]);
		BX.bind(this.selectNode, 'change', BX.proxy(this.onChangeLineSelect, this));
		BX.bind(this.removeButtonNode, 'click', BX.proxy(this.remove, this));
	},

	onChangeLineSelect: function (e)
	{
		this.initConfig();
		BX.onCustomEvent(this, 'change', [this, e.target.value]);
	},

	fillSelect: function (list)
	{
		var items = list.map(function (item) {
			return {
				'value': item.ID,
				'caption': item.NAME,
				'selected': item.ID == this.selectNode.value
			};
		}, this);
		this.fillDropDownControl(this.selectNode, items);
	},

	load: function (data)
	{
		if (!data)
		{
			return;
		}

		this.data = data;
		this.editButtonNode.href = this.data.PATH_EDIT;
		this.channelsNode.innerHTML = '';
		this.data.CONNECTORS.forEach(this.addConnector, this);

		BX.bind(this.editButtonNode, 'click', function (e) {
			e.preventDefault();

			BX.SidePanel.Instance.open(this.href, {width: 996, allowChangeHistory: false});
		});

		BX.bind(this.addButtonNode, 'click', function (e) {
			e.preventDefault();

			BX.SidePanel.Instance.open(this.href, {width: 996, allowChangeHistory: false});
		});

		BX.onCustomEvent(this, 'load', [this]);
	},

	addConnector: function (connector)
	{
		var checked = 'checked';
		if (BX.util.in_array(connector.id, this.config.excluded))
		{
			checked = '';
		}
		var connectorNode = CrmButtonEditTemplate.getNode(
			'connector',
			{
				'lineid': this.getId(),
				'connector': connector.id,
				'icon': connector.icon,
				'checked': checked
			}
		);
		this.channelsNode.appendChild(connectorNode);
		this.initTooltip(connectorNode, connector);
	},

	initTooltip: function (node, connector)
	{
		node = node.querySelector('[data-crm-tooltip]');
		if (!node)
		{
			return;
		}

		var text = connector.title;
		if (text != connector.desc)
		{
			text += ': ' + connector.desc;
		}

		BX.bind(node, 'mouseover', function(){
			BX.UI.Hint.show(this, BX.util.htmlspecialchars(text));
		});
		BX.bind(node, 'mouseout', function(){
			BX.UI.Hint.hide();
		});
	},
	showTooltip: function (node, text)
	{
		if (!this.tooltipPopup)
		{
			var tooltipPopup = new BX.PopupWindow(
				'bx-crm-site-button-openline-tooltip',
				node,
				{
					lightShadow: true,
					autoHide: false,
					darkMode: true,
					offsetLeft: 25,
					offsetTop: 2,
					bindOptions: {position: "top"},
					zIndex: 200
				}
			);
			tooltipPopup.setAngle({offset:13, position: 'bottom'});
			this.__proto__.tooltipPopup = tooltipPopup;
		}

		this.tooltipPopup.setBindElement(node);
		this.tooltipPopup.setContent(text);
		this.tooltipPopup.show();
	},

	remove: function ()
	{
		BX.unbindAll(this);
		BX.unbindAll(this.node);
		BX.remove(this.node);
		BX.onCustomEvent(this, 'remove', [this]);
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