var CrmWebFormList = function(params)
{
	this.init = function(params)
	{
		this.context = BX(params.context);
		this.canEdit = params.canEdit;
		this.nodeHead = this.context.querySelector('.crm-webform-list-header');
		this.headHideClass = 'crm-webform-title-close';
		this.formAttribute = 'data-bx-crm-webform-item';
		this.formAttributeIsSystem = 'data-bx-crm-webform-item-is-system';
		this.forms = [];

		this.mess = params.mess || {};
		this.viewList = params.viewList || {};
		this.actionList = params.actionList || [];
		var formNodeList = this.context.querySelectorAll('[' + this.formAttribute + ']');
		for(var i = 0; i < formNodeList.length; i++)
		{
			var formNode = formNodeList.item(i);
			var formId = formNode.getAttribute('data-bx-crm-webform-item');
			var isSystem = formNode.getAttribute(this.formAttributeIsSystem) == 'Y';
			this.initForm({
				'caller': this,
				'id': formId,
				'node': formNode,
				'isSystem': isSystem,
				'viewUserOptionName': params.viewUserOptionName,
				'detailPageUrlTemplate': params.detailPageUrlTemplate,
				'actionRequestUrl': params.actionRequestUrl,
				'canUseVoteClient': params.canUseVoteClient
			});
		}

        var hideDescBtnNode = BX('CRM_LIST_DESC_BTN_HIDE');
        if (hideDescBtnNode)
        {
            BX.bind(hideDescBtnNode, 'click', function () {
                BX.addClass(BX('CRM_LIST_DESC_CONT'), 'crm-webform-list-info-hide');
                BX.userOptions.delay = 0;
                BX.userOptions.save('imopenlines', params.viewUserOptionName, 'hide-desc', 'Y');
            });
        }
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

	this.init(params);
};

function CrmWebFormListItem(params)
{
	this.caller = params.caller;
	this.id = params.id;
	this.node = params.node;
	this.isSystem = params.isSystem;
	this.actionRequestUrl = params.actionRequestUrl;
	this.canUseVoteClient = params.canUseVoteClient;
	this.viewUserOptionName = params.viewUserOptionName;
	this.detailPageUrlTemplate = params.detailPageUrlTemplate;

	this.nodeDelete = this.node.querySelector('.copy-to-buffer-button');
	this.nodeCopyToClipboard = this.node.querySelector('.copy-to-clipboard-node');
	this.nodeCopyToClipboardButton = this.node.querySelector('.copy-to-clipboard-button');

	this.nodeDelete = this.node.querySelector('[data-bx-crm-webform-item-delete]');
	this.nodeSettings = this.node.querySelector('[data-bx-crm-webform-item-settings]');
	this.nodeViewSettings = this.node.querySelector('[data-bx-crm-webform-item-view-settings]');
	this.nodeView = this.node.querySelector('[data-bx-crm-webform-item-view]');
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
						popupItem.onclick = BX.proxy(function(){this.redirectToDetailPage(this.id);}, this);
						break;
					case 'copy':
						popupItem.onclick = BX.proxy(this.copy, this);
						break;
					case 'reset_counters':
						popupItem.onclick = BX.proxy(this.resetCounters, this);
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
			this.changeClass(viewInfoNode, 'crm-webform-list-widget-content-item-show', isAdd);
		}

		BX.userOptions.save('imopenlines', this.viewUserOptionName, this.id, view.id);
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

				if(data.limited)
				{
					if(!B24 || !B24['licenseInfoPopup'])
					{
						return;
					}
					BX.UI.InfoHelper.show(this.caller.mess.limitInfoHelper);
				}
				else
				{
					this.showErrorPopup(data);
				}
			}
		);
	},
	createLine: function (event, doNotSend)
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

		if(doNotSend)
		{
			return;
		}

		this.isActiveControlLocked = true;
		this.sendActionRequest(
			'create',
			function(data)
			{
				location.href = this.detailPageUrlTemplate.replace('#ID#', data.config_id);
			},
			function(data)
			{
				data = data || {'error': true, 'text': ''};
				this.isActiveControlLocked = false;

				if(data.limited)
				{
					if(!B24 || !B24['licenseInfoPopup'])
					{
						return;
					}
					BX.UI.InfoHelper.show(this.caller.mess.limitInfoHelper);
				}
				else
				{
					this.showErrorPopup(data);
				}
			}
		);
	},
	redirectToDetailPage: function (formId)
	{
		window.location = this.detailPageUrlTemplate.replace('#ID#', formId).replace('#CONFIG_ID#', formId);
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
			url: this.getActionRequestUrlWithParams(action),
			method: 'POST',
			data: {
				'action': action,
				'config_id': this.id,
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
	getActionRequestUrlWithParams: function(action)
	{
		var url = this.actionRequestUrl;
		if (url.charAt(0) === '/')
		{
			url = window.location.protocol + '//' + window.location.host + url;
		}

		var objUrl = new URL(url);

		switch (action)
		{
			case 'create':
				var ratingRequest = this.canUseVoteClient ? 'Y' : 'N';
				objUrl.searchParams.set('rating-request', ratingRequest);
				break;
		}

		objUrl.searchParams.set('action-line', action);

		return objUrl.href;
	},
	bindControls: function ()
	{
		BX.clipboard.bindCopyClick(this.nodeCopyToClipboardButton, {text: this.nodeCopyToClipboard});

		BX.bind(this.nodeDelete, 'click', BX.proxy(this.delete, this));
		BX.bind(this.activeController.nodeActiveControl, 'click', BX.proxy(this.changeActive, this));
        BX.bind(this.activeController.nodeButton, 'click', BX.proxy(this.changeActive, this));
		if (!this.caller.isBindCreate)
		{
			this.caller.isBindCreate = true;
			BX.bind(this.activeController.nodeCreateControl, 'click', BX.proxy(this.createLine, this));
		}
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
	this.nodeCreateControl = BX('crm-webform-list-create');
	this.nodeDate = this.caller.node.querySelector('[data-bx-crm-webform-item-active-date]');

	this.classDateNow = 'user-container-show-now';
	this.classDateNowState = 'user-container-show-now-deact';
	this.classOn = 'crm-webform-list-on';
	this.classOff = 'crm-webform-list-off';

    this.nodeButton = this.caller.node.querySelector('[data-bx-crm-webform-item-active-btn]');
    this.classBtnOn = 'webform-small-button-transparent';
    this.classBtnOff = 'webform-small-button-accept';
    this.classViewInactive = 'crm-webform-list-widget-inactive';

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
	deactivate: function ()
	{
		BX.removeClass(this.nodeActiveControl, this.classOn);
		BX.addClass(this.nodeActiveControl, this.classOff);
        this.actualizeButton();
		this.actualizeDate();
	},
    actualizeButton: function ()
    {
        var isActive = this.isActive();
        this.caller.changeClass(this.caller.nodeView, this.classViewInactive, isActive);
        this.caller.changeClass(this.nodeButton, this.classBtnOn, !isActive);
        this.caller.changeClass(this.nodeButton, this.classBtnOff, isActive);

        this.nodeButton.innerText = isActive ? this.nodeButton.getAttribute('data-bx-text-on') : this.nodeButton.getAttribute('data-bx-text-off');
    },
	actualizeDate: function ()
	{
		this.caller.changeClass(this.nodeDate, this.classDateNowState, !this.isActive());

		var isNow = (!this.isRevert || this.isNowShowedCounter > 1);
		this.caller.changeClass(this.nodeDate, this.classDateNow, isNow);

		this.isNowShowedCounter++;
	}
};