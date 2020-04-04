;(function() {

"use strict";

BX.namespace("BX.Intranet");

BX.Intranet.NotifyDialog = function(options)
{
	if (!BX.type.isPlainObject(options))
	{
		throw new Error("BX.Intranet.Notify: \"options\" is not an object.");
	}
	if (!BX.type.isArray(options.listUserData))
	{
		throw new Error("BX.Intranet.Notify: \"listUserData\" is not an array.");
	}
	if (!BX.type.isNotEmptyString(options.notificationHandlerUrl))
	{
		throw new Error("BX.Intranet.Notify: \"notificationHandlerUrl\" is required.");
	}

	this.listUserData = options.listUserData;
	this.notificationHandlerUrl = options.notificationHandlerUrl;
	this.postData = options.postData || {};

	var title, header, description, sendButton;
	if(options.popupTexts)
	{
		title = options.popupTexts.hasOwnProperty("title") && BX.type.isNotEmptyString(options.popupTexts.title) ?
			options.popupTexts.title : "Title";
		header = options.popupTexts.hasOwnProperty("header") && BX.type.isNotEmptyString(options.popupTexts.header) ?
			options.popupTexts.header : "Header";
		description = options.popupTexts.hasOwnProperty("description") &&
			BX.type.isNotEmptyString(options.popupTexts.description) ? options.popupTexts.description : "Description";
		sendButton = options.popupTexts.hasOwnProperty("sendButton") &&
			BX.type.isNotEmptyString(options.popupTexts.sendButton) ? options.popupTexts.sendButton : "SendButton";
	}
	else
	{
		title = "Title";
		header = "Header";
		description = "Description";
		sendButton = "SendButton";
	}
	this.popupTexts = {
		"title": title,
		"header": header,
		"description": description,
		"sendButton": sendButton
	};

	this.closePopupAfterNotify = options.closePopupAfterNotify === "Y";

	this.init();
};

BX.Intranet.NotifyDialog.prototype.constructor = BX.Intranet.NotifyDialog;

BX.Intranet.NotifyDialog.prototype.init = function()
{
	this.popup = null;

	this.createPopup();
};

BX.Intranet.NotifyDialog.prototype.setUsersForNotify = function(listUserData)
{
	if (!BX.type.isArray(listUserData))
	{
		throw new Error("BX.Intranet.Notify: \"listUserData\" is not an array.");
	}

	this.listUserData = listUserData;
	this.popup = null;
};

BX.Intranet.NotifyDialog.prototype.show = function()
{
	if (this.popup === null)
	{
		this.createPopup();
	}
	this.popup.show();
};

BX.Intranet.NotifyDialog.prototype.sendNotify = function()
{
	var userId = BX.proxy_context.dataset.id;
	BX.addClass(BX("intranet-notify-dialog-button-"+userId), "webform-small-button-wait");

	this.postData["SITE_ID"] = BX.message("SITE_ID");
	this.postData["sessid"] = BX.bitrix_sessid();
	this.postData["userId"] = userId;
	BX.ajax({
		method: "POST",
		dataType: "json",
		url: this.notificationHandlerUrl,
		data: this.postData,
		onsuccess: BX.proxy(function(response)
		{
			if(response.status === "success")
			{
				BX.hide(BX("intranet-notify-dialog-button-"+userId));
				BX("intranet-notify-dialog-success-"+userId).innerHTML =
					BX.message("INTRANET_NOTIFY_DIALOG_NOTIFY_SUCCESS");
				if(this.closePopupAfterNotify)
				{
					setTimeout(BX.proxy(function() {
						this.popup.destroy();
					}, this), 1000);
				}
			}
			BX.removeClass(BX("intranet-notify-dialog-button-"+userId), "webform-small-button-wait");
		}, this)
	});
};

BX.Intranet.NotifyDialog.prototype.createPopup = function()
{
	this.popup = new BX.PopupWindow("intranet-notify-popup", null, {
		content: this.createContent(),
		overlay: true,
		closeIcon : {
			marginRight: "4px",
			marginTop: "9px"
		},
		closeByEsc: true,
		buttons: [
			new BX.PopupWindowButton({
				text : BX.message("INTRANET_NOTIFY_DIALOG_BUTTON_CLOSE"),
				events : {
					click : BX.proxy(function() {
						this.popup.destroy();
					}, this)
				}
			})
		]
	});

	this.listUserData.forEach(function(userData) {
		BX.bind(BX("intranet-notify-dialog-button-"+userData.id), "click", BX.proxy(this.sendNotify, this));
	}, this);
};

BX.Intranet.NotifyDialog.prototype.createContent = function()
{
	var contentDialog, contentDialogGenerated;
	contentDialogGenerated = BX.create("div", {
		children: [
			BX.create("span", {
				props: {
					className: "intranet-notify-dialog-header"
				},
				children: [
					BX.create("span", {
						props: {
							innerHTML: "!",
							className: "intranet-notify-dialog-exclamation-point-icon"
						}
					}),
					BX.create("span", {
						props: {
							innerHTML: BX.util.htmlspecialchars(this.popupTexts.header)
						}
					})
				]
			}),
			BX.create("div", {
				props: {
					className: "intranet-notify-dialog-description"
				},
				html: BX.util.htmlspecialchars(this.popupTexts.description)
			}),
			BX.create("span", {
				props: {
					className: "intranet-notify-dialog-title-who-notify"},
				html: BX.message("INTRANET_NOTIFY_DIALOG_TITLE_WHO_NOTIFY")
			})
		]
	});

	this.listUserData.forEach(function(userData) {
		var imageContent = [];
		if (userData.img)
		{
			imageContent.push(BX.create("img", {
				attrs: {
					src: userData.img
				}
			}));
		}
		contentDialogGenerated.appendChild(
			BX.create("div", {
				props: {
					className: "intranet-notify-dialog-user"
				},
				children: [
					BX.create("span", {
						props: {
							className: "intranet-notify-dialog-user-avatar"
						},
						children: [
							BX.create("span", {
								props: {
									className: "intranet-notify-dialog-user-avatar-inner"
								},
								children: imageContent
							})
						]
					}),
					BX.create("span", {
						props: {
							className: "intranet-notify-dialog-user-info"
						},
						children: [
							BX.create("span", {
								html: BX.util.htmlspecialchars(userData.name)
							})
						]
					}),
					BX.create("span", {
						props: {
							id: "intranet-notify-dialog-success-"+parseInt(userData.id),
							className: "intranet-notify-dialog-success"
						}
					}),
					BX.create("span", {
						props: {
							id: "intranet-notify-dialog-button-"+parseInt(userData.id),
							className: "webform-small-button intranet-notify-dialog-small-button " +
								"webform-small-button-blue"
						},
						attrs: {
							"data-id": parseInt(userData.id)
						},
						html: BX.util.htmlspecialchars(this.popupTexts.sendButton)
					})
				]
			})
		);
	}, this);

	contentDialog = BX.create("div", {
		props: {
			className: "intranet-notify-dialog-container"
		},
		children: [
			BX.create("div", {
				props: {
					className: "intranet-notify-dialog-title"
				},
				text: this.popupTexts.title
			}),
			BX.create("div", {
				props: {
					className: "intranet-notify-dialog-content"
				},
				children: [contentDialogGenerated]
			})
		]
	});

	return contentDialog;
};

})();