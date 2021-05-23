var CrmWebFormList = function(params)
{
	this.init = function(params)
	{
		this.context = BX(params.context);
		this.canEdit = params.canEdit;
		this.isFramePopup = params.isFramePopup;
		this.pathToButtonList = params.pathToButtonList;
		this.nodeHead = this.context.querySelector('[data-bx-list-head]');
		this.nodeList = this.context.querySelector('[data-bx-list-items]');
		this.headHideClass = 'crm-webform-title-close';
		this.formAttribute = 'data-bx-crm-webform-item';
		this.formAttributeIsSystem = 'data-bx-crm-webform-item-is-system';
		this.forms = [];

		this.viewUserOptionName = params.viewUserOptionName;
		this.detailPageUrlTemplate = params.detailPageUrlTemplate;
		this.actionRequestUrl = params.actionRequestUrl;

		this.mess = params.mess || {};
		this.viewList = params.viewList || {};
		this.actionList = params.actionList || [];
		var formNodeList = this.context.querySelectorAll('[' + this.formAttribute + ']');
		for(var i = 0; i < formNodeList.length; i++)
		{
			this.initItemByNode(formNodeList.item(i));
		}

		var hideDescBtnNode = BX('CRM_LIST_DESC_BTN_HIDE');
		if (hideDescBtnNode)
		{
			BX.bind(hideDescBtnNode, 'click', function () {
				BX.addClass(BX('CRM_LIST_DESC_CONT'), 'intranet-button-list-info-hide');
				BX.userOptions.delay = 0;
				BX.userOptions.save('crm', params.viewUserOptionName, 'hide-desc', 'Y');
			});
		}

		this.initSlider();
	};

	this.initItemByNode = function(node)
	{
		var buttonId = node.getAttribute(this.formAttribute);
		var isSystem = node.getAttribute(this.formAttributeIsSystem) == 'Y';
		this.initForm({
			'caller': this,
			'id': buttonId,
			'node': node,
			'isSystem': isSystem,
			'viewUserOptionName': this.viewUserOptionName,
			'detailPageUrlTemplate': this.detailPageUrlTemplate,
			'actionRequestUrl': this.actionRequestUrl
		});
	};

	this.onBeforeDeleteForm = function(form)
	{
		var list = this.forms.filter(function(item){
			return item.isSystem == false;
		});
		if(list.length > 1)
		{
			return;
		}

		BX.addClass(this.nodeHead, this.headHideClass)
	};

	this.onAfterDeleteForm = function(form)
	{
		var index = BX.util.array_search(form, this.forms);
		if(index > -1)
		{
			delete this.forms[index];
		}
	};

	this.onRevertDeleteForm = function(form)
	{
		BX.removeClass(this.nodeHead, this.headHideClass)
	};

	this.initForm = function(params)
	{
		var form = new CrmWebFormListItem(params);
		this.forms.push(form);
	};

	this.initSlider = function()
	{
		if (!this.isFramePopup)
		{
			return;
		}

		BX.SidePanel.Instance.bindAnchors({
			rules: [
				{
					condition: [this.detailPageUrlTemplate.replace('#id#', '(\\d+)').replace('#button_id#', '(\\d+)')],
					loader: 'crm-button-view-loader',
					stopParameters: []
				}
			]
		});
	};

	this.init(params);
};

function CrmTiledViewListItemCopier (params)
{
	this.caller = params.caller;
	this.manager = params.manager;
	this.source = params.source;
	this.copiedNode = null;
	this.shadowNode = null;
}
CrmTiledViewListItemCopier.prototype = {
	draw: function ()
	{
		var finishHeight = this.source.node.offsetHeight;
		this.copiedNode = this.source.node.cloneNode(true);
		this.copiedNode.style.height = '0';
		this.copiedNode.style.opacity = '0';
		this.prepareNode();

		var activeController = new CrmWebFormListItemActiveDateController({
			caller: {
				node: this.copiedNode
			}
		});
		activeController.deactivate(true);

		if (this.manager.nodeList.contains(this.source.node))
		{
			this.manager.nodeList.insertBefore(this.copiedNode, this.source.node);
		}
		else
		{
			this.manager.nodeList.insertBefore(this.copiedNode, this.firstChild);
		}

		this.startLoadAnimation();
		var easing = new BX.easing({
			duration: 300,
			start: { height: 0,  opacity: 0 },
			finish: { height: finishHeight,  opacity: 100 },
			transition: BX.easing.transitions.quint,
			step: BX.proxy(function(state) {
				this.copiedNode.style.height = state.height + "px";
				this.copiedNode.style.opacity = state.opacity / 100;
			}, this)
		});
		easing.animate();
	},
	erase: function ()
	{
		var startHeight = this.copiedNode.offsetHeight;
		var easing = new BX.easing({
			duration: 700,
			start: { height: startHeight,  opacity: 100 },
			finish: { height: -1,  opacity: 0 },
			transition: BX.easing.transitions.quint,
			step: BX.proxy(function(state) {
				this.copiedNode.style.height = state.height + "px";
				this.copiedNode.style.opacity = state.opacity / 100;
			}, this),
			complete: BX.proxy(this.remove, this)
		});
		easing.animate();
	},
	remove: function ()
	{
		BX.remove(this.copiedNode);
	},
	getTitleNode: function ()
	{
		if (!this.copiedNode)
		{
			return null;
		}
		return this.copiedNode.querySelector('[data-bx-title]');
	},
	prepareNode: function (params)
	{
		params = params || {};
		this.copiedNode.setAttribute(this.manager.formAttribute, params.id || '0');
		this.copiedNode.setAttribute(this.manager.formAttributeIsSystem, 'N');

		var titleNode = this.getTitleNode();
		if (titleNode)
		{
			titleNode.innerText = params.title || '.  .  .';
		}

		var linkNodes = this.copiedNode.querySelectorAll('[data-bx-edit-link]');
		linkNodes = BX.convert.nodeListToArray(linkNodes);
		linkNodes.forEach(function (linkNode) {
			linkNode.href = params.detailUrl || '';
		});
	},
	init: function (params)
	{
		this.stopLoadAnimation();
		this.prepareNode({id: params.id, title: params.title, detailUrl: params.detailUrl});
		this.manager.initItemByNode(this.copiedNode);
	},
	startLoadAnimation: function ()
	{
		this.copiedNode.style.position = 'relative';
		this.shadowNode = document.createElement('DIV');
		BX.addClass(this.shadowNode, 'crm-tiled-view-list-edit-item-loading-shadow');
		this.copiedNode.insertBefore(this.shadowNode, this.copiedNode.firstChild);

		var titleNode = this.getTitleNode();
		if (titleNode)
		{
			BX.addClass(titleNode, 'crm-tiled-view-list-edit-item-loading');
		}
	},
	stopLoadAnimation: function ()
	{
		var titleNode = this.getTitleNode();
		if (titleNode)
		{
			BX.removeClass(titleNode, 'crm-tiled-view-list-edit-item-loading');
		}

		var easing = new BX.easing({
			duration: 300,
			start: { opacity: 50 },
			finish: { opacity: 70 },
			step: BX.proxy(function(state) {
				this.shadowNode.style.opacity = state.opacity / 100;
			}, this),
			complete: BX.proxy(function () {

				var easing = new BX.easing({
					duration: 300,
					start: { opacity: 70 },
					finish: { opacity: 0 },
					step: BX.proxy(function(state) {
						this.shadowNode.style.opacity = state.opacity / 100;
					}, this),
					complete: BX.proxy(function () {
						BX.remove(this.shadowNode);
						this.copiedNode.style.position = '';
					}, this)
				});
				easing.animate();

			}, this)
		});
		easing.animate();
	}
};

function CrmWebFormListItem(params)
{
	this.caller = params.caller;
	this.id = params.id;
	this.node = params.node;
	this.isSystem = params.isSystem;
	this.actionRequestUrl = params.actionRequestUrl;
	this.viewUserOptionName = params.viewUserOptionName;
	this.detailPageUrlTemplate = params.detailPageUrlTemplate;

	this.nodeDelete = this.node.querySelector('.copy-to-buffer-button');
	this.nodeCopyToClipboard = this.node.querySelector('.copy-to-clipboard-node');
	this.nodeCopyToClipboardButton = this.node.querySelector('.copy-to-clipboard-button');


	this.nodeDelete = this.node.querySelector('[data-bx-crm-webform-item-delete]');
	this.nodeSettings = this.node.querySelector('[data-bx-crm-webform-item-settings]');
	this.nodeViewSettings = this.node.querySelector('[data-bx-crm-webform-item-view-settings]');
	this.nodeView = this.node.querySelector('[data-bx-crm-webform-item-view]');
	this.nodeBtnGetScript = this.node.querySelector('[data-bx-crm-webform-item-btn-getscript]');
	this.isActiveControlLocked = false;

	this.popupSettings = null;
	this.popupViewSettings = null;

	this.activeController = new CrmWebFormListItemActiveDateController({caller: this});
	this.bindControls(params);
}
CrmWebFormListItem.prototype =
{
	showViewSettings: function ()
	{
		var items = [];
		var currentViewType = this.getCurrentViewType();
		for(var code in this.caller.viewList)
		{
			if (!this.caller.viewList.hasOwnProperty(code)) continue;
			var view = this.caller.viewList[code];
			items.push({
				id: code,
				text: view['NAME'],
				className: (
					currentViewType == code
						?
						'view-settings-menu-sort-item-checked'
						:
						'view-settings-menu-sort-item-checked-no'
				),
				onclick: BX.proxy(this.onClickViewSettingsItem, this)
			});
		}

		if(!this.popupViewSettings)
		{
			this.popupViewSettings = this.createPopup(
				'crm_webform_list_view_settings_' + this.id,
				this.nodeViewSettings,
				items
			);
		}
		else
		{
			items.forEach(function(item){
				var menuItem = this.popupViewSettings.getMenuItem(item.id);
				menuItem.className = item.className;
				BX.removeClass(menuItem.layout.item, 'view-settings-menu-sort-item-checked');
				BX.addClass(menuItem.layout.item, menuItem.className);
			}, this);
		}

		this.popupViewSettings.popupWindow.show();
	},
	showSettings: function ()
	{
		if(!this.popupSettings)
		{
			var items = [];
			var actionList = this.caller.actionList[this.isSystem ? 'SYSTEM' : 'USER'];
			for(var code in actionList)
			{
				if (!actionList.hasOwnProperty(code)) continue;
				var item = actionList[code];
				var popupItem = {
					id: item.id,
					text: item.text,
					link: item.url
				};
				switch(popupItem.id)
				{
					case 'view':
					case 'edit':
						popupItem.onclick = BX.proxy(function() {
							this.redirectToDetailPage(this.id);
							this.popupSettings.close();
						}, this);
						break;
					case 'copy':
						popupItem.onclick = BX.proxy(function() {
							this.copy();
							this.popupSettings.close();
						}, this);
						break;
					case 'reset_counters':
						popupItem.onclick = BX.proxy(function() {
							this.resetCounters();
							this.popupSettings.close();
						}, this);
						break;
				}
				items.push(popupItem);
			}

			this.popupSettings = this.createPopup(
				'crm_webform_list_settings_' + this.id,
				this.nodeSettings,
				items,
				{offsetLeft: -30, offsetTop: 10}
			);
		}

		this.popupSettings.popupWindow.show();
	},
	onClickViewSettingsItem: function (event, item)
	{
		var view = this.caller.viewList[item.id];
		view.id = item.id;
		this.closePopup(this.popupViewSettings);
		this.changeViewType(view);
	},
	getCurrentViewType: function ()
	{
		var firstViewId = null;
		for(var viewId in this.caller.viewList)
		{
			if (!this.caller.viewList.hasOwnProperty(viewId)) continue;
			if(!firstViewId) firstViewId = viewId;

			var className = this.caller.viewList[viewId]['CLASS_NAME'];
			if(BX.hasClass(this.nodeView, className))
			{
				return viewId;
			}
		}

		return firstViewId;
	},
	changeViewType: function (view)
	{
		for(var viewId in this.caller.viewList)
		{
			if (!this.caller.viewList.hasOwnProperty(viewId)) continue;

			var className = this.caller.viewList[viewId]['CLASS_NAME'];
			var viewInfoNode = this.nodeView.querySelector('[data-bx-crm-webform-view-info="' + viewId + '"]');

			var isAdd = view.id == viewId;
			this.changeClass(this.nodeView, className, isAdd);
			this.changeClass(viewInfoNode, 'intranet-button-list-widget-content-item-show', isAdd);
		}

		BX.userOptions.save('crm', this.viewUserOptionName, this.id, view.id);
	},
	showErrorPopup: function (data)
	{
		data = data || {};
		var text = data.text || this.caller.mess.errorAction;
		var popup = BX.PopupWindowManager.create(
			'crm_webform_list_error',
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
				text: this.caller.mess.dlgBtnClose,
				events: {click: function(){this.popupWindow.close();}}
			})
		]);
		popup.setContent('<span class="crm-webform-edit-warning-popup-alert">' + text + '</span>');
		popup.show();
	},
	showConfirmPopup: function (data)
	{
		data = data || {};
		var text = data.text || this.caller.mess.confirmAction;
		var popup = BX.PopupWindowManager.create(
			'crm_webform_list_confirm',
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
				text: this.caller.mess.dlgBtnApply,
				className: "popup-window-button-accept",
				events: {click: function(){this.popupWindow.close(); data.action.apply(this, [])}}
			}),
			new BX.PopupWindowButton({
				text: this.caller.mess.dlgBtnCancel,
				events: {click: function(){this.popupWindow.close();}}
			})
		]);
		popup.setContent('<span class="crm-webform-edit-warning-popup-confirm">' + text + '</span>');
		popup.show();
	},
	changeActive: function (event, doNotSend)
	{
		if(!this.caller.canEdit)
		{
			return;
		}

		doNotSend = doNotSend || false;
		if(this.isActiveControlLocked)
		{
			return;
		}

		var needDeactivate = this.activeController.isActive();
		if(needDeactivate)
		{
			this.activeController.deactivate();
		}
		else
		{
			this.activeController.activate();
		}

		if(doNotSend)
		{
			return;
		}

		this.isActiveControlLocked = true;
		this.sendActionRequest(
			(needDeactivate ? 'deactivate' : 'activate'),
			function(data)
			{
				this.isActiveControlLocked = false;
			},
			function(data)
			{
				data = data || {'error': true, 'text': ''};
				this.isActiveControlLocked = false;
				this.activeController.revert();
				this.showErrorPopup(data);
			}
		);
	},

	getDetailPageById: function (id)
	{
		return this.detailPageUrlTemplate.replace('#id#', id).replace('#button_id#', id);
	},

	redirectToDetailPage: function (id, isCopied)
	{
		isCopied = isCopied || false;
		var url = this.getDetailPageById(id);
		if (this.caller.isFramePopup)
		{
			if (!isCopied)
			{
				BX.SidePanel.Instance.open(url);
			}
		}
		else
		{
			window.location = url;
		}
	},

	resetCounters: function ()
	{
		this.sendActionRequest('reset_counters', function(){
			window.location.reload();
		});
	},
	copy: function ()
	{
		var copier = new CrmTiledViewListItemCopier({
			'manager': this.caller,
			'source': this
		});
		copier.draw();
		this.sendActionRequest(
			'copy',
			function(data){
				copier.init({
					id: data.copiedId,
					title: data.copiedName,
					detailUrl: this.getDetailPageById(data.copiedId)
				});
				this.redirectToDetailPage(data.copiedId, true);
			},
			function(){
				copier.erase();
			}
		);
	},
	delete: function ()
	{
		this.showConfirmPopup({
			text: this.caller.mess.deleteConfirmation,
			action: BX.proxy(function(){

				var deleteClassName = 'crm-webform-row-close';
				BX.addClass(this.node, deleteClassName);
				this.caller.onBeforeDeleteForm(this);

				this.sendActionRequest(
					'delete',
					function(data){
						this.caller.onAfterDeleteForm(this);
					},
					function(data){
						BX.removeClass(this.node, deleteClassName);
						this.caller.onRevertDeleteForm(this);
						this.showErrorPopup(data);
					}
				);

			}, this)
		});
	},
	sendActionRequest: function (action, callbackSuccess, callbackFailure)
	{
		callbackSuccess = callbackSuccess || null;
		callbackFailure = callbackFailure || BX.proxy(this.showErrorPopup, this);

		BX.ajax({
			url: this.actionRequestUrl,
			method: 'POST',
			data: {
				'action': action,
				'button_id': this.id,
				'sessid': BX.bitrix_sessid()
			},
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
	showScriptPopup: function ()
	{
		BX.addClass(this.nodeBtnGetScript, 'webform-small-button-wait');
		this.sendActionRequest('show_script', function(data){
				var popup = this.createScriptPopup();
				this.scriptPopup.crmCopyScriptContainer.innerHTML = data.html;
				BX.removeClass(this.nodeBtnGetScript, 'webform-small-button-wait');
				popup.show();
			},
			function (data) {
				BX.removeClass(this.nodeBtnGetScript, 'webform-small-button-wait');
				this.showErrorPopup(data);
			});
	},
	createScriptPopup: function (data)
	{
		if (this.scriptPopup)
		{
			return this.scriptPopup;
		}

		data = data || {};
		var popupContentNode = BX('SCRIPT_CONTAINER');
		this.scriptPopup = BX.PopupWindowManager.create(
			'crm_webform_list_script_popup',
			null,
			{
				titleBar: this.caller.mess.dlgGetScriptTitle,
				content: popupContentNode,
				contentColor: 'white',
				closeIcon: true,
				autoHide: true,
				lightShadow: true,
				closeByEsc: true,
				overlay: {backgroundColor: 'black', opacity: 500}
			}
		);

		this.scriptPopup.crmCopyScriptContainer = popupContentNode.querySelector('[data-bx-webform-script-copy-text]');
		var buttons = [];
		if (BX.clipboard.isCopySupported())
		{
			var copyToClipBoardBtn = new BX.PopupWindowButton({
				text: this.caller.mess.dlgBtnCopyToClipboard,
				className: 'webform-small-button-blue',
				events: {click: function(){}}
			});
			buttons.push(copyToClipBoardBtn);
			BX.clipboard.bindCopyClick(copyToClipBoardBtn.buttonNode, {text: this.scriptPopup.crmCopyScriptContainer});
		}

		buttons.push(new BX.PopupWindowButton({
			text: this.caller.mess.dlgBtnClose,
			events: {click: function(){
				this.popupWindow.close();
			}}
		}));
		this.scriptPopup.setButtons(buttons);

		return this.scriptPopup;
	},
	bindControls: function ()
	{
		BX.clipboard.bindCopyClick(this.nodeCopyToClipboardButton, {text: this.nodeCopyToClipboard});

		BX.bind(this.nodeDelete, 'click', BX.proxy(this.delete, this));
		BX.bind(this.activeController.nodeActiveControl, 'click', BX.proxy(this.changeActive, this));
		BX.bind(this.activeController.nodeButton, 'click', BX.proxy(this.changeActive, this));
		BX.bind(this.nodeSettings, 'click', BX.proxy(this.showSettings, this));
		BX.bind(this.nodeViewSettings, 'click', BX.proxy(this.showViewSettings, this));
		BX.bind(this.nodeBtnGetScript, 'click', BX.proxy(this.showScriptPopup, this));
	},
	changeClass: function (node, className, isAdd)
	{
		isAdd = isAdd || false;
		if(!node)
		{
			return;
		}

		if(isAdd)
		{
			BX.addClass(node, className);
		}
		else
		{
			BX.removeClass(node, className);
		}
	},
	styleDisplay: function (node, isShow, displayValue)
	{
		isShow = isShow || false;
		displayValue = displayValue || '';
		if(!node)
		{
			return;
		}

		node.style.display = isShow ? displayValue : 'none';
	},
	createPopup: function(popupId, button, items, params)
	{
		params = params || {};
		return BX.PopupMenu.create(
			popupId,
			button,
			items,
			{
				autoHide: true,
				offsetLeft: params.offsetLeft ? params.offsetLeft : -21,
				offsetTop: params.offsetTop ? params.offsetTop : -3,
				angle:
				{
					position: "top",
					offset: 42
				},
				events:
				{
					onPopupClose : BX.delegate(this.onPopupClose, this)
				}
			}
		);
	},
	closePopup: function(popup)
	{
		if(popup && popup.popupWindow)
		{
			popup.popupWindow.close();
		}
	},
	onPopupClose: function()
	{

	}
};

function CrmWebFormListItemActiveDateController(params)
{
	this.caller = params.caller;

	this.nodeActiveControl = this.caller.node.querySelector('[data-bx-crm-webform-item-active]');
	this.nodeDate = this.caller.node.querySelector('[data-bx-crm-webform-item-active-date]');

	this.classDateNow = 'user-container-show-now';
	this.classDateNowState = 'user-container-show-now-deact';
	this.classOn = 'intranet-button-list-on';
	this.classOff = 'intranet-button-list-off';

	this.nodeView = this.caller.node.querySelector('[data-bx-crm-webform-item-view]');
	this.nodeButton = this.caller.node.querySelector('[data-bx-crm-webform-item-active-btn]');
	this.classBtnOn = 'webform-small-button-transparent';
	this.classBtnOff = 'webform-small-button-accept';
	this.classViewInactive = 'intranet-button-list-widget-inactive';

	this.isNowShowedCounter = 0;
	this.isRevert = false;
}
CrmWebFormListItemActiveDateController.prototype =
{
	isActive: function ()
	{
		return BX.hasClass(this.nodeButton, this.classBtnOn);
	},
	revert: function ()
	{
		this.isRevert = true;
		this.toggle();

		if(this.isNowShowedCounter < 2)
		{
			this.isNowShowedCounter = 0;
		}
		this.isRevert = false;
	},
	toggle: function ()
	{
		if(this.isActive())
		{
			this.deactivate();
		}
		else
		{
			this.activate();
		}
	},
	activate: function ()
	{
		BX.addClass(this.nodeActiveControl, this.classOn);
		BX.removeClass(this.nodeActiveControl, this.classOff);
		this.actualizeButton();
		this.actualizeDate();
	},
	deactivate: function (force)
	{
		BX.removeClass(this.nodeActiveControl, this.classOn);
		BX.addClass(this.nodeActiveControl, this.classOff);
		this.actualizeButton(force);
		this.actualizeDate();
	},
	actualizeButton: function (forceDeactivate)
	{
		var isActive = forceDeactivate ? true : this.isActive();
		this.changeClass(this.nodeView, this.classViewInactive, isActive);
		this.changeClass(this.nodeButton, this.classBtnOn, !isActive);
		this.changeClass(this.nodeButton, this.classBtnOff, isActive);

		this.nodeButton.innerText = isActive ? this.nodeButton.getAttribute('data-bx-text-on') : this.nodeButton.getAttribute('data-bx-text-off');
	},
	actualizeDate: function ()
	{
		this.changeClass(this.nodeDate, this.classDateNowState, !this.isActive());

		var isNow = (!this.isRevert || this.isNowShowedCounter > 1);
		this.changeClass(this.nodeDate, this.classDateNow, isNow);

		this.isNowShowedCounter++;
	},
	changeClass: function (node, className, isAdd)
	{
		isAdd = isAdd || false;
		if(!node)
		{
			return;
		}

		if(isAdd)
		{
			BX.addClass(node, className);
		}
		else
		{
			BX.removeClass(node, className);
		}
	}
};