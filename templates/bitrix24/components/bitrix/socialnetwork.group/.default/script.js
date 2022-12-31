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
	this.slider = false;
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
		this.slider = !!params.slider;

		if (BX('bx-group-join-submit'))
		{
			BX.bind(BX('bx-group-join-submit'), 'click', BX.delegate(this.sendJoinRequest, this))
		}
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
			onsuccess: function(responseData) {
				BX.SocialnetworkUICommon.hideButtonWait(BX('bx-group-join-submit'));

				if (
					BX.type.isNotEmptyString(responseData.MESSAGE)
					&& responseData.MESSAGE == 'SUCCESS'
					&& BX.type.isNotEmptyString(responseData.URL)
				)
				{
					BX.addClass(BX('bx-group-join-form'), 'sonet-group-user-request-form-invisible');
					BX.onCustomEvent(window.top, 'sonetGroupEvent', [ {
						code: 'afterJoinRequestSend',
						data: {
							groupId: this.groupId
						}
					} ]);

					if (this.slider)
					{
						location.href = BX.Uri.addParam(responseData.URL, {
							IFRAME: 'Y',
						})
					}
					else
					{
						top.location.href = responseData.URL;
					}
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
			}.bind(this),
			onfailure: function() {
				BX.SocialnetworkUICommon.showError(BX.message('SONET_C6_T_AJAX_ERROR'), BX('bx-group-join-error'));
				BX.SocialnetworkUICommon.hideButtonWait(BX('bx-group-join-submit'));
			}.bind(this)
		});
	},
};

})();