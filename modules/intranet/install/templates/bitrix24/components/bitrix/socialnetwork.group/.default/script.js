;(function(){

"use strict";

BX.namespace("BX.Bitrix24");

if (window["B24SGControl"])
{
	return;
}

window.B24SGControl = function()
{
	this.instance = null;
	this.groupId = null;
	this.groupOpened = false;
	this.waitPopup = null;
	this.waitTimeout = null;
	this.notifyHintPopup = null;
	this.notifyHintTimeout = null;
	this.notifyHintTime = 3000;
	this.favoritesValue = null;
	this.newValue = null;
};

window.B24SGControl.getInstance = function()
{
	if (window.B24SGControl.instance == null)
	{
		window.B24SGControl.instance = new B24SGControl();
	}

	return window.B24SGControl.instance;
};

window.B24SGControl.prototype = {

	init: function(params)
	{
		if (
			typeof params == 'undefined'
			|| typeof params.groupId == 'undefined'
			|| parseInt(params.groupId) <= 0
		)
		{
			return;
		}

		this.groupId = parseInt(params.groupId);
		this.favoritesValue = !!params.favoritesValue;
		this.groupOpened = !!params.groupOpened;

		if (BX('bx-group-join-submit'))
		{
			BX.bind(BX('bx-group-join-submit'), 'click', BX.delegate(this.sendJoinRequest, this))
		}

		BX.addCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuItemAdded", BX.delegate(function() {
			this.favoritesValue = true;
		}, this));

		BX.addCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuItemDeleted", BX.delegate(function() {
			this.favoritesValue = false;
		}, this));

		BX.addCustomEvent('SidePanel.Slider:onMessage', BX.delegate(function(event){
			if (event.getEventId() == 'sonetGroupEvent')
			{
				var eventData = event.getData();

				if (
					eventData.code == 'afterSetFavorites'
					&& typeof eventData.data != 'undefined'
					&& parseInt(eventData.data.groupId) > 0
					&& parseInt(eventData.data.groupId) == this.groupId
					&& typeof eventData.data.value != 'undefined'
				)
				{
					this.favoritesValue = !!eventData.data.value;
				}

				if (
					eventData.code == 'afterSetSubscribe'
					&& typeof eventData.data != 'undefined'
					&& parseInt(eventData.data.groupId) > 0
					&& parseInt(eventData.data.groupId) == this.groupId
					&& typeof eventData.data.value != 'undefined'
				)
				{
					this.setNotifyButton(eventData.data.value, false);
				}

				if (
					event.slider === top.BX.SidePanel.Instance.getSliderByWindow(window)
					&& BX('socialnetwork-group-sidebar-block')
					&& BX.util.in_array(eventData.code, ['afterModeratorAdd', 'afterModeratorRemove', 'afterOwnerSet', 'afterUserExclude' ])
					&& typeof eventData.data != 'undefined'
					&& parseInt(eventData.data.groupId) > 0
					&& parseInt(eventData.data.groupId) == this.groupId
				)
				{
					BX.SocialnetworkUICommon.reloadBlock({
						blockId: 'socialnetwork-group-sidebar-block'
					});
				}
			}
		}, this));
	},

	setSubscribe: function(event)
	{
		var _this = this;

		_this.showWait();

		var action = (!BX.hasClass(BX("group_menu_subscribe_button"), "webform-button-active") ? "set" : "unset");

		BX.ajax({
			url: '/bitrix/components/bitrix/socialnetwork.group_menu/ajax.php',
			method: 'POST',
			dataType: 'json',
			data: {
				groupID: _this.groupId,
				action: (action == 'set' ? 'set' : 'unset'),
				sessid: BX.bitrix_sessid()
			},
			onsuccess: function(data) {
				_this.processSubscribeAJAXResponse(data);
			}
		});
		BX.PreventDefault(event);
	},

	sendJoinRequest: function(event)
	{
		BX.SocialnetworkUICommon.hideError(BX('bx-group-join-error'));
		BX.SocialnetworkUICommon.showButtonWait(BX('bx-group-join-submit'));

		BX.ajax({
			url: BX('bx-group-join-submit').getAttribute('bx-request-url'),
			method: 'POST',
			dataType: 'json',
			data: {
				groupID: this.groupId,
				MESSAGE: (BX('bx-group-join-message') ? BX('bx-group-join-message').value : ''),
				ajax_request: 'Y',
				save: 'Y',
				sessid: BX.bitrix_sessid()
			},
			onsuccess: BX.delegate(function(responseData) {
				BX.SocialnetworkUICommon.hideButtonWait(BX('bx-group-join-submit'));

				if (
					typeof responseData.MESSAGE != 'undefined'
					&& responseData.MESSAGE == 'SUCCESS'
					&& typeof responseData.URL != 'undefined'
				)
				{
					BX.addClass(BX('bx-group-join-form'), 'sonet-group-user-request-form-invisible');
					BX.onCustomEvent(window.top, 'sonetGroupEvent', [ {
						code: 'afterJoinRequestSend',
						data: {
							groupId: this.groupId
						}
					} ]);
					top.location.href = responseData.URL;
				}
				else if (
					typeof responseData.MESSAGE != 'undefined'
					&& responseData.MESSAGE == 'ERROR'
					&& typeof responseData.ERROR_MESSAGE != 'undefined'
					&& responseData.ERROR_MESSAGE.length > 0
				)
				{
					BX.SocialnetworkUICommon.showError(responseData.ERROR_MESSAGE, BX('bx-group-join-error'));
				}
			}, this),
			onfailure: BX.delegate(function() {
				BX.SocialnetworkUICommon.showError(BX.message('SONET_C6_T_AJAX_ERROR'), BX('bx-group-join-error'));
				BX.SocialnetworkUICommon.hideButtonWait(BX('bx-group-join-submit'));
			}, this)
		});
	},

	setFavorites: function(event)
	{
		var _this = this;

		_this.showWait();
		_this.newValue = !_this.favoritesValue;

		BX.ajax({
			url: '/bitrix/components/bitrix/socialnetwork.group_menu/ajax.php',
			method: 'POST',
			dataType: 'json',
			data: {
				groupID: _this.groupId,
				action: (_this.favoritesValue ? 'fav_unset' : 'fav_set'),
				sessid: BX.bitrix_sessid(),
				lang: BX.message('LANGUAGE_ID')
			},
			onsuccess: function(data) {
				_this.processFavoritesAJAXResponse(data);

				if (
					typeof data.NAME != 'undefined'
					&& typeof data.URL != 'undefined'
				)
				{
					BX.onCustomEvent(window, 'BX.Socialnetwork.WorkgroupFavorites:onSet', [{
						id: _this.groupId,
						name: data.NAME,
						url: data.URL,
						extranet: (typeof data.EXTRANET != 'undefined' ? data.EXTRANET : 'N')
					}, _this.newValue]);
				}

			},
			onfailure: function(data) {
			}
		});
		BX.PreventDefault(event);
	},

	setNotifyButton: function(value, showHint)
	{
		showHint = !!showHint;

		var button = BX("group_menu_subscribe_button", true);
		if (button)
		{
			if (value)
			{
				if (showHint)
				{
					this.showNotifyHint(button, BX.message('SGMSubscribeButtonHintOn'));
				}
				BX.adjust(button, { attrs : {title : BX.message('SGMSubscribeButtonTitleOn')} });
				BX.addClass(button, "webform-button-active");
			}
			else
			{
				if (showHint)
				{
					this.showNotifyHint(button, BX.message('SGMSubscribeButtonHintOff'));
				}
				BX.adjust(button, { attrs : {title : BX.message('SGMSubscribeButtonTitleOff')} });
				BX.removeClass(button, "webform-button-active");
			}
		}
	},

	processSubscribeAJAXResponse: function(data)
	{
		var _this = this;

		if (
			typeof data.SUCCESS != 'undefined'
			&& data.SUCCESS == "Y"
		)
		{
			_this.closeWait();

			var button = BX("group_menu_subscribe_button");
			if (button)
			{
				BX.delegate(function() {
					this.setNotifyButton(
						(typeof data.RESULT == 'undefined' || data.RESULT != "N"),
						true
					);
				}, _this)();
			}

			return false;
		}
		else if (BX.type.isNotEmptyString(data.ERROR))
		{
			_this.processAJAXError(data["ERROR"]);
			return false;
		}
	},

	processFavoritesAJAXResponse: function(data)
	{
		var _this = this;

		_this.closeWait();
		if (
			typeof data["SUCCESS"] != 'undefined'
			&& data["SUCCESS"] == "Y"
		)
		{
			_this.favoritesValue = _this.newValue;

		}
		else if (
			typeof data["ERROR"] != 'undefined'
			&& data["ERROR"].length > 0
		)
		{
			_this.processAJAXError(data["ERROR"]);
		}

		return false;
	},

	processAJAXError: function(errorCode)
	{
		var _this = this;

		if (errorCode.indexOf("SESSION_ERROR", 0) === 0)
		{
			_this.showErrorPopup(BX.message('SGMErrorSessionWrong'));
			return false;
		}
		else if (errorCode.indexOf("CURRENT_USER_NOT_AUTH", 0) === 0)
		{
			_this.showErrorPopup(BX.message('SGMErrorCurrentUserNotAuthorized'));
			return false;
		}
		else if (errorCode.indexOf("SONET_MODULE_NOT_INSTALLED", 0) === 0)
		{
			_this.showErrorPopup(BX.message('SGMErrorModuleNotInstalled'));
			return false;
		}
		else
		{
			_this.showErrorPopup(errorCode);
			return false;
		}
	},

	showWait : function(timeout)
	{
		var _this = this;

		if (timeout !== 0)
		{
			return (_this.waitTimeout = setTimeout(function(){
				_this.showWait(0)
			}, 300));
		}

		if (!_this.waitPopup)
		{
			_this.waitPopup = new BX.PopupWindow('sgm_wait', window, {
				autoHide: true,
				lightShadow: true,
				zIndex: 2,
				content: BX.create('DIV', {
					props: {
						className: 'sonet-sgm-wait-cont'
					},
					children: [
						BX.create('DIV', {
							props: {
								className: 'sonet-sgm-wait-icon'
							}
						}),
						BX.create('DIV', {
							props: {
								className: 'sonet-sgm-wait-text'
							},
							html: BX.message('SGMWaitTitle')
						})
					]
				})
			});
		}
		else
		{
			_this.waitPopup.setBindElement(window);
		}

		_this.waitPopup.show();
	},

	closeWait: function()
	{
		if (this.waitTimeout)
		{
			clearTimeout(this.waitTimeout);
			this.waitTimeout = null;
		}

		if (this.waitPopup)
		{
			this.waitPopup.close();
		}
	},

	showNotifyHint: function(el, hint_text)
	{
		var _this = this;

		if (_this.notifyHintTimeout)
		{
			clearTimeout(_this.notifyHintTimeout);
			_this.notifyHintTimeout = null;
		}

		if (_this.notifyHintPopup == null)
		{
			_this.notifyHintPopup = new BX.PopupWindow('sgm_notify_hint', el, {
				autoHide: true,
				lightShadow: true,
				zIndex: 2,
				content: BX.create('DIV', {
					props: {
						className: 'sonet-sgm-notify-hint-content'
					},
					style: {
						display: 'none'
					},
					children: [
						BX.create('SPAN', {
							props: {
								id: 'sgm_notify_hint_text'
							},
							html: hint_text
						})
					]
				}),
				closeByEsc: true,
				closeIcon: false,
				offsetLeft: 19,
				offsetTop: 2
			});

			_this.notifyHintPopup.TEXT = BX('sgm_notify_hint_text');
			_this.notifyHintPopup.setBindElement(el);
		}
		else
		{
			_this.notifyHintPopup.TEXT.innerHTML = hint_text;
			_this.notifyHintPopup.setBindElement(el);
		}

		_this.notifyHintPopup.setAngle({});
		_this.notifyHintPopup.show();

		_this.notifyHintTimeout = setTimeout(function() {
			_this.notifyHintPopup.close();
		}, _this.notifyHintTime);
	},

	showErrorPopup: function(errorText)
	{
		this.closeWait();

		var errorPopup = new BX.PopupWindow('sgm-error' + Math.random(), window, {
			autoHide: true,
			lightShadow: false,
			zIndex: 2,
			content: BX.create('DIV', {props: {'className': 'sonet-sgm-error-text-block'}, html: errorText}),
			closeByEsc: true,
			closeIcon: true
		});
		errorPopup.show();
	}
};

window.BX.SGMSetSubscribe = window.B24SGControl.getInstance().setSubscribe;

})();