BX.namespace("BX.Mobile");

BX.Mobile.Profile = (function() {
	"use strict";

	return {

		user: {},
		pullDown : {},
		isWebRTCSupported: false,
		menu: [],
		actionSheet: null,
		userPhotoUrl: false,

		init: function(params)
		{
			if (params)
			{
				this.user = params.user || {};
				this.pullDown = params.pullDown || {};
				this.isWebRTCSupported = params.isWebRTCSupported === true;
				this.menu = BX.type.isArray(params.menu) ? params.menu : [];
				this.userPhotoUrl = BX.type.isNotEmptyString(params.userPhotoUrl) ? params.userPhotoUrl : false;
			}

			BX.ready(BX.proxy(this.ready, this));
		},

		ready: function()
		{
			this.initButtons();
			this.initMenu();
			this.initAvatar();
			this.initStatus();
			this.initPullDown();
		},

		initButtons: function()
		{
			var buttons = [
				this.getVideoButton(),
				this.getAudioButton(),
				this.getTextButton()
			];

			var docFragment = document.createDocumentFragment();
			for (var i = 0; i < buttons.length; i++)
			{
				var button = buttons[i];
				if (button === null)
				{
					continue;
				}

				var buttonNode = BX.create("div", {
					props: { className: "emp-profile-button " + button.className },
					children: [
						BX.create("span", { text : button.title })
					],
					events: {
						touchstart: function(event) {
							BX.addClass(this, "emp-profile-button-selected");
						},
						touchend: function(event) {
							BX.removeClass(this, "emp-profile-button-selected");
						}
					}
				});

				new FastButton(buttonNode, button.click);
				docFragment.appendChild(buttonNode);
			}

			if (docFragment.childNodes.length > 0)
			{
				var btnContainer = BX("emp-profile-buttons");
				BX.addClass(btnContainer, "emp-profile-buttons-" + docFragment.childNodes.length);
				btnContainer.appendChild(docFragment);
			}
			else
			{
				BX.addClass(BX("emp-profile"), "emp-profile-no-buttons")
			}
		},

		getVideoButton: function()
		{
			var button = null;
			if (
				app.enableInVersion(9)
				&& this.isWebRTCSupported
				&& BX.message("USER_ID") != this.user.id
				&& this.user.external_auth_id != 'email'
				&& this.user.external_auth_id != 'bot'
				&& this.user.external_auth_id != 'network'
				&& this.user.external_auth_id != 'imconnector'
			)
			{
				button = {
					className: "emp-profile-video",
					title: BX.message("SONET_VIDEO_CALL"),
					click: BX.proxy(function(event) {
						BXMobileApp.onCustomEvent("onCallInvite", { "userId": this.user.id, "video": true, userData: this.getUserData()}, true);
						BX.eventReturnFalse(event);
					}, this)
				};
			}
			return button;
		},

		getAudioButton: function()
		{
			var menuItems = this.getAudioMenuItems();
			if (menuItems.length < 1)
			{
				return null;
			}

			return {
				className: "emp-profile-audio",
				title: BX.message("SONET_AUDIO_CALL"),
				click: BX.proxy(function(event)
				{
					if(menuItems.length == 1)
					{
						BXMobileApp.onCustomEvent("onCallInvite", { "userId": this.user.id, "video": false, userData: this.getUserData()}, true);
					}
					else
					{
						this.showAudioMenu(menuItems);
					}

					BX.eventReturnFalse(event);
				}, this)
			};
		},

		getAudioMenuItems: function()
		{
			var items = [];

			if (
				app.enableInVersion(9)
				&& this.isWebRTCSupported
				&& BX.message("USER_ID") != this.user.id
				&& this.user.external_auth_id != 'email'
				&& this.user.external_auth_id != 'bot'
				&& this.user.external_auth_id != 'network'
			)
			{
				items.push({
					title: BX.message("SONET_AUDIO_CALL"),
					callback: BX.proxy(function() {
						BXMobileApp.onCustomEvent("onCallInvite", {"userId": this.user.id, video: false, userData: this.getUserData()}, true);
					}, this)
				});
			}

			var phones = [this.user.work_phone, this.user.personal_mobile];
			for (var i = 0; i < phones.length; i++)
			{
				if (BX.type.isNotEmptyString(phones[i]))
				{
					var item = {
						title: phones[i],
						callback: function() {
							BX.MobileTools.phoneTo(this.title);
						}
					};

					item.callback = BX.delegate(item.callback, item);
					items.push(item);
				}
			}

			if (items.length > 0 && !app.enableInVersion(10))
			{
				items.push({
					title: BX.message("MB_CANCEL"),
					callback: function() {

					}
				});
			}

			return items;
		},

		getUserData: function()
		{
			return {
				[this.user.id] : {
					avatar: this.userPhotoUrl,
					name: this.user.name_formatted
				}
			}
		},

		showAudioMenu: function(items)
		{
			if (app.enableInVersion(10))
			{
				if (this.actionSheet === null)
				{
					this.actionSheet = new BXMobileApp.UI.ActionSheet({buttons: items}, "actionSheetId");
				}
				this.actionSheet.show();
			}
			else
			{
				var itemsTitle = [];
				for (var i = 0; i < items.length; i++)
				{
					itemsTitle.push(items[i].title);
				}

				app.confirm({
					callback: function (itemIndex)
					{
						itemIndex = itemIndex > 1 ? itemIndex-1 : 0;
						items[itemIndex]["callback"]();
					},

					title: BX.message("MB_CALL"),
					buttons: itemsTitle
				});
			}
		},

		getTextButton: function()
		{
			if (
				BX.message("USER_ID") == this.user.id
				|| this.user.external_auth_id == 'email'
			)
			{
				return null;
			}

			return {
				className: "emp-profile-text",
				title: BX.message("SONET_MESSAGE"),
				click: BX.proxy(function(event) {

					BX.MobileTools.openChat(this.user.id, {
						name: this.user.name_formatted,
						description: this.user.work_position,
						avatar: this.userPhotoUrl
					});

					BX.eventReturnFalse(event);
				}, this)
			};
		},

		initMenu: function()
		{
			if (this.menu.length > 0)
			{
				app.menuCreate({ items: this.menu });
				BXMobileApp.UI.Page.TopBar.title.setText(BX.message('SONET_TITLE'));
				BXMobileApp.UI.Page.TopBar.title.setCallback(function () {
					app.menuShow();
				});
				BXMobileApp.UI.Page.TopBar.title.show();
			}
		},

		initAvatar: function()
		{
			if (this.userPhotoUrl !== false)
			{
				new FastButton(BX("emp-profile"), BX.proxy(function(event) {
					BXMobileApp.UI.Photo.show({ photos: [{ url: this.userPhotoUrl}]})
				}, this));

			}
		},

		initStatus: function()
		{
			this.updateStatus(this.user.is_online == "Y");
			BXMobileApp.addCustomEvent("onPullOnline", BX.delegate(function(command) {

				var params = command.params;
				command = command.command;

				if ((command == "user_online" || command == "user_status") && this.user.id == params.USER_ID)
				{
					if (command == "user_status")
					{
						window.pageColor = params.COLOR;
						BX.style(BX('emp-profile'), 'background-color', params.COLOR);
						app.exec("setTopBarColors",{background: params.COLOR, titleText:"#ffffff", titleDetailText:"#f0f0f0"});
					}

					this.updateStatus(true);
				}
				else if (command == "user_offline" && this.user.id == params.USER_ID)
				{
					this.updateStatus(false);
				}
				else if (command == "online_list")
				{
					this.updateStatus(typeof(params.USERS[this.user.id]) !== "undefined");
				}

			}, this));
		},

		updateStatus: function(isUserOnline)
		{
			if (this.user.external_auth_id == 'bot')
			{
				isUserOnline = true;
			}

			var statusNode = BX("emp-profile-status", true);
			if (isUserOnline)
			{
				statusNode.className = "emp-profile-status emp-profile-status-online";
				statusNode.innerHTML = BX.message("STATUS_ONLINE");
			}
			else
			{
				statusNode.className = "emp-profile-status emp-profile-status-offline";
				statusNode.innerHTML = BX.message("STATUS_OFFLINE");
			}
		},

		initPullDown: function()
		{

			if (app.enableInVersion(2))
			{
				this.pullDown.action = "RELOAD";
			}
			else
			{
				this.pullDown.callback = function()
				{
					document.location.reload();
				};
			}

			app.pullDown(this.pullDown);
		}

	}

})();
