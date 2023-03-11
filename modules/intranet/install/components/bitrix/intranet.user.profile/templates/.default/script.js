;(function ()
{
	var namespace = BX.namespace('BX.Intranet.UserProfile');
	if (namespace.Manager)
	{
		return;
	}

	namespace.Manager = function(params)
	{
		this.init(params);
	};

	namespace.Manager.prototype = {
		init: function(params)
		{
			this.signedParameters = params.signedParameters;
			this.componentName = params.componentName;
			this.canEditProfile = params.canEditProfile === "Y";
			this.userId = params.userId || "";
			this.userStatus = params.userStatus || "";
			this.isOwnProfile = params.isOwnProfile === "Y";
			this.isSessionAdmin = params.isSessionAdmin === "Y";
			this.urls = params.urls;
			this.isExtranetUser = params.isExtranetUser === "Y";
			this.adminRightsRestricted = params.adminRightsRestricted === "Y";
			this.delegateAdminRightsRestricted = params.delegateAdminRightsRestricted === "Y";
			this.isFireUserEnabled = params.isFireUserEnabled === "Y";
			this.showSonetAdmin = params.showSonetAdmin === "Y";
			this.languageId = params.languageId;
			this.initialFields = params.initialFields;
			this.siteId = params.siteId;
			this.isCloud = params.isCloud === "Y";
			this.isRusCloud = params.isRusCloud === "Y";
			this.isCurrentUserIntegrator = params.isCurrentUserIntegrator === "Y";
			this.personalMobile = this.initialFields["PERSONAL_MOBILE"];
			this.isCurrentUserAdmin = params.isCurrentUserAdmin;

			this.entityEditorInstance = new namespace.EntityEditor({
				managerInstance: this,
				params: params
			});

			BX.ready(function () {
				this.tagsManagerInstance = new namespace.Tags({
					managerInstance: this,
					inputNode: document.getElementById('intranet-user-profile-tags-input'),
					tagsNode: document.getElementById('intranet-user-profile-tags')
				});

				this.stressLevelManagerInstance = new namespace.StressLevel({
					managerInstance: this,
					options: params
				});

				this.gratsManagerInstance = new namespace.Grats({
					managerInstance: this,
					options: params
				});

				this.profilePostManagerInstance = new namespace.ProfilePost({
					managerInstance: this,
					options: params
				});

				this.initAvailableActions();
				this.initAvatarLoader();

				if (this.isCloud)
				{
					this.initGdpr();
				}

				var subordinateMoreButton = BX("intranet-user-profile-subordinate-more");
				if (BX.type.isDomNode(subordinateMoreButton))
				{
					BX.bind(subordinateMoreButton, "click", function () {
						this.loadMoreUsers(subordinateMoreButton);
					}.bind(this));
				}

				var managerMoreButton = BX("intranet-user-profile-manages-more");
				if (BX.type.isDomNode(subordinateMoreButton))
				{
					BX.bind(managerMoreButton, "click", function () {
						this.loadMoreUsers(managerMoreButton);
					}.bind(this));
				}

				//hack for form view button
				var bottomContainer = document.querySelector('.intranet-user-profile-bottom-controls');
				var cardButton = document.getElementById('intranet-user-profile_buttons');
				if (BX.type.isDomNode(bottomContainer) && BX.type.isDomNode(cardButton))
				{
					var cardButtonLink = cardButton.querySelector('.ui-entity-settings-link');
					cardButtonLink.setAttribute('class', 'ui-btn ui-btn-sm ui-btn-light-border ui-btn-themes');
					cardButton.parentNode.removeChild(cardButton);
					bottomContainer.appendChild(cardButtonLink);
				}
			}.bind(this));
		},

		initAvailableActions: function()
		{
			if (!this.userStatus)
				return;

			var actionElement = document.querySelector("[data-role='user-profile-actions-button']");
			if (BX.type.isDomNode(actionElement))
			{
				BX.bind(actionElement, "click", BX.proxy(function () {
					this.showActionPopup(BX.proxy_context);
				}, this));
			}
		},

		initGdpr: function ()
		{
			var gdprInputs = document.querySelectorAll("[data-role='gdpr-input']");
			gdprInputs.forEach(
				function(currentValue, currentIndex, listObj) {
					BX.bind(currentValue, "change", function () {
						this.changeGdpr(currentValue);
					}.bind(this));
				}.bind(this)
			);

			var dropdownTarget = document.querySelector('.intranet-user-profile-column-block-title-dropdown');
			BX.bind(dropdownTarget, "click", function () {
				this.animateGdprBlock(dropdownTarget);
			}.bind(this));
		},

		initAvatarLoader: function()
		{
			if (BX('intranet-user-profile-photo-camera')
				&& BX('intranet-user-profile-photo-file'))
			{
				if (BX.AvatarEditor)
				{
					setTimeout(function() {
						if (BX.AvatarEditor.isCameraAvailable() !== true)
						{
							BX.hide(BX('intranet-user-profile-photo-camera'));
						}
					}, 0);

					var getEditor = function() {
						var editor = BX.AvatarEditor.getInstanceById('intranet-user-profile-photo-file');
						if (editor === null)
						{
							editor = BX.AvatarEditor.createInstance('intranet-user-profile-photo-file', {
								enableCamera: true,
								enableMask: true
							});
							editor.subscribeOnFormIsReady('newPhoto', this.changePhoto.bind(this));
						}
						return editor;
					}.bind(this);

					BX.bind(BX('intranet-user-profile-photo-camera'), 'click', function() { getEditor().show('camera'); });
					BX.bind(BX('intranet-user-profile-photo-file'), 'click', function(){ getEditor().show('file'); });
					BX.bind(BX('intranet-user-profile-photo-mask'), 'click', function(){ getEditor().show('mask'); });
				}
				else
				{
					BX.hide(BX('intranet-user-profile-photo-camera'));
					BX.hide(BX('intranet-user-profile-photo-file'));
					BX.hide(BX('intranet-user-profile-photo-mask'));
				}
			}

			BX.bind(BX("intranet-user-profile-photo-remove"), "click", function () {
				if (BX("intranet-user-profile-photo").style.backgroundImage != "")
				{
					this.showConfirmPopup(BX.message("INTRANET_USER_PROFILE_PHOTO_DELETE_CONFIRM"), this.deletePhoto.bind(this));
				}
			}.bind(this))
		},

		showActionPopup: function(bindElement)
		{
			var menuItems = [];

			if (this.showSonetAdmin)
			{
				menuItems.push({
					text: BX.message(this.isSessionAdmin ? "INTRANET_USER_PROFILE_QUIT_ADMIN_MODE" : "INTRANET_USER_PROFILE_ADMIN_MODE"),
					className: "menu-popup-no-icon",
					onclick: function () {
						this.popupWindow.close();
						__SASSetAdmin();
					}
				});
			}

			if (this.userStatus === "admin" && this.canEditProfile && !this.isOwnProfile)
			{
				menuItems.push({
					text: BX.message("INTRANET_USER_PROFILE_REMOVE_ADMIN_RIGHTS"),
					className: "menu-popup-no-icon",
					onclick: BX.proxy(function () {
						BX.proxy_context.popupWindow.close();
						this.removeAdminRights();
					}, this)
				});
			}

			if (this.userStatus === "employee" && this.canEditProfile && !this.isOwnProfile && !this.isCurrentUserIntegrator)
			{
				var itemText = BX.message("INTRANET_USER_PROFILE_SET_ADMIN_RIGHTS");
				if (this.delegateAdminRightsRestricted)
				{
					itemText+= "<span class='intranet-user-profile-lock-icon'></span>";
				}
				menuItems.push({
					html: itemText,
					onclick: BX.proxy(function () {
						BX.proxy_context.popupWindow.close();
						if (this.adminRightsRestricted)
						{
							if (this.delegateAdminRightsRestricted)
							{
								top.BX.UI.InfoHelper.show('limit_admin_admins');
							}
							else
							{
								this.showConfirmPopup(BX.message("INTRANET_USER_PROFILE_MOVE_ADMIN_RIGHTS_CONFIRM"), this.setAdminRights.bind(this));
							}
						}
						else
						{
							this.setAdminRights();
						}
					}, this)
				});
			}

			if (
				this.isCurrentUserAdmin === 'Y'
				&& this.userStatus !== "fired"
				&& !this.isOwnProfile
				&& !BX.util.in_array(this.userStatus, ['email', 'shop' ])
			)
			{
				itemText = BX.message("INTRANET_USER_PROFILE_FIRE");
				if (!this.isFireUserEnabled && this.userStatus !== "integrator")
				{
					itemText+= "<span class='intranet-user-profile-lock-icon'></span>";
				}

				menuItems.push({
					text: itemText,
					className: "menu-popup-no-icon",
					onclick: BX.proxy(function () {
						BX.proxy_context.popupWindow.close();
						if (!this.isFireUserEnabled && this.userStatus !== "integrator")
						{
							top.BX.UI.InfoHelper.show('limit_dismiss');
						}
						else
						{
							this.showConfirmPopup(BX.message("INTRANET_USER_PROFILE_FIRE_CONFIRM"), this.fireUser.bind(this));
						}
					}, this)
				});
			}

			if (this.isCurrentUserAdmin === 'Y' && this.userStatus === "fired" && !this.isOwnProfile)
			{
				menuItems.push({
					text: BX.message("INTRANET_USER_PROFILE_HIRE"),
					className: "menu-popup-no-icon",
					onclick: BX.proxy(function () {
						BX.proxy_context.popupWindow.close();
						this.showConfirmPopup(BX.message("INTRANET_USER_PROFILE_HIRE_CONFIRM"), this.hireUser.bind(this));
					}, this)
				});
			}

			if (this.userStatus === "invited" && this.canEditProfile && !this.isOwnProfile)
			{
				menuItems.push({
					text: BX.message("INTRANET_USER_PROFILE_REINVITE"),
					className: "menu-popup-no-icon",
					onclick: BX.proxy(function () {
						BX.proxy_context.popupWindow.close();
						this.reinviteUser();
					}, this)
				});

				menuItems.push({
					text: BX.message("INTRANET_USER_PROFILE_DELETE"),
					className: "menu-popup-no-icon",
					onclick: BX.proxy(function () {
						BX.proxy_context.popupWindow.close();
						this.showConfirmPopup(BX.message("INTRANET_USER_PROFILE_DELETE_CONFIRM"), this.deleteUser.bind(this));
					}, this)
				});
			}

			if (this.isExtranetUser && this.canEditProfile && !this.isOwnProfile && this.isCloud)
			{
				menuItems.push({
					text: BX.message("INTRANET_USER_PROFILE_MOVE_TO_INTRANET"),
					className: "menu-popup-no-icon",
					onclick: BX.proxy(function () {
						BX.proxy_context.popupWindow.close();
						this.moveToIntranet();
					}, this)
				});
			}

			if (
				this.isCloud
				&& this.canEditProfile && !this.isOwnProfile
				&& this.userStatus !== "integrator"
			)
			{
				menuItems.push({
					text: BX.message("INTRANET_USER_PROFILE_SET_INEGRATOR_RIGHTS"),
					className: "menu-popup-no-icon",
					onclick: BX.proxy(function () {
						BX.proxy_context.popupWindow.close();
						this.showConfirmPopup(BX.message("INTRANET_USER_PROFILE_SET_INTEGRATOR_RIGHTS_CONFIRM"), this.setIntegratorRights.bind(this));
					}, this)
				});
			}

			BX.PopupMenu.show("user-profile-action-popup", bindElement, menuItems,
			{
				offsetTop: 0,
				offsetLeft: 10,
				angle: true,
				events: {
					onPopupClose: function ()
					{
						BX.PopupMenu.destroy();
					}
				}
			});
		},

		showConfirmPopup: function(text, confirmCallback)
		{
			BX.PopupWindowManager.create({
				id: "intranet-user-profile-confirm-popup",
				content:
					BX.create("div", {
						props : {
							style : "max-width: 450px"
						},
						html: text
					}),
				closeIcon : false,
				lightShadow : true,
				offsetLeft : 100,
				overlay : false,
				contentPadding: 10,
				buttons: [
					new BX.UI.CreateButton({
						text: BX.message("INTRANET_USER_PROFILE_YES"),
						events: {
							click: function (button) {
								button.setWaiting();
								this.context.close();
								confirmCallback();
							}
						}
					}),
					new BX.UI.CancelButton({
						text : BX.message("INTRANET_USER_PROFILE_NO"),
						events : {
							click: function () {
								this.context.close();
							}
						}
					})
				],
				events : {
					onPopupClose: function ()
					{
						this.destroy();
					}
				}
			}).show();
		},

		showFireInvitedUserPopup: function(callback)
		{
			BX.PopupWindowManager.create({
				id: "intranet-user-profile-fire-invited-popup",
				content:
					BX.create("div", {
						props : {
							style : "max-width: 450px"
						},
						html: BX.message('INTRANET_USER_PROFILE_FIRE_INVITED_USER')
					}),
				closeIcon : true,
				lightShadow : true,
				offsetLeft : 100,
				overlay : false,
				contentPadding: 10,
				buttons: [
					new BX.UI.CreateButton({
						text: BX.message("INTRANET_USER_PROFILE_YES"),
						events: {
							click: function (button) {
								button.setWaiting();
								this.context.close();
								callback();
							}
						}
					}),

					new BX.UI.CancelButton({
						text : BX.message("INTRANET_USER_PROFILE_NO"),
						events : {
							click: function () {
								this.context.close();
							}
						}
					})
				]
			}).show();
		},

		showErrorPopup: function(error)
		{
			if (!error)
			{
				return;
			}

			BX.PopupWindowManager.create({
				id: "intranet-user-profile-error-popup",
				content:
					BX.create("div", {
						props : {
							style : "max-width: 450px"
						},
						html: error
					}),
				closeIcon : true,
				lightShadow : true,
				offsetLeft : 100,
				overlay : false,
				contentPadding: 10
			}).show();
		},

		loadMoreUsers: function(button)
		{
			if (!BX.type.isDomNode(button))
			{
				return;
			}

			var block = button.parentNode;

			var items = block.querySelectorAll("[data-role='user-profile-item']");
			var itemsLength = items.length;
			for (var i = 0; i < 4 && i < itemsLength; i++)
			{
				items[i].style.display = "inline-block";
				items[i].setAttribute("data-role", "");
			}

			if (itemsLength - 4 <= 0)
			{
				button.style.display = "none";
			}
			else
			{
				BX.findChild(button).innerHTML = itemsLength - 4;
			}
		},

		changePhoto: function(event)
		{
			var dataObj = (event.getData())['form'];
			var loader = this.showLoader({node: BX("intranet-user-profile-photo"), loader: null, size: 100});

			BX.ajax.runComponentAction(this.componentName, "loadPhoto", {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: dataObj
			})
			.then(
				(response) => {
					if (response.data)
					{
						(top || window).BX.onCustomEvent('BX.Intranet.UserProfile:Avatar:changed', [{
							userId: this.userId,
							url: response.data
						}]);

						BX("intranet-user-profile-photo").style = "background-image: url('" + encodeURI(response.data) + "'); background-size: cover;";
					}
					this.hideLoader({loader: loader});
				},
				(response) => {
					this.hideLoader({loader: loader});
					this.showErrorPopup(response["errors"][0].message);
				}
			);
		},

		deletePhoto: function()
		{
			var loader = this.showLoader({node: BX("intranet-user-profile-photo"), loader: null, size: 100});

			BX.ajax.runComponentAction(this.componentName, "deletePhoto", {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {}
			}).then(function (response) {

				BX("intranet-user-profile-photo").style = "";
				this.hideLoader({loader: loader});

			}.bind(this), function (response) {
				this.hideLoader({loader: loader});
				this.showErrorPopup(response["errors"][0].message);
			}.bind(this));
		},

		setAdminRights: function()
		{
			var loader = this.showLoader({node: BX("intranet-user-profile-wrap"), loader: null, size: 100});

			BX.ajax.runComponentAction(this.componentName, "setAdminRights", {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {}
			}).then(function (response) {
				if (response.data === true)
				{
					BX.SidePanel.Instance.postMessageTop(window, 'userProfileSlider::reloadList', {});
					location.reload();
				}
				else
				{
					this.hideLoader({loader: loader});
					this.showErrorPopup("Error");
				}
			}, function (response) {
				this.hideLoader({loader: loader});
				this.showErrorPopup(response["errors"][0].message);
			}.bind(this));
		},

		setIntegratorRights: function()
		{
			var loader = this.showLoader({node: BX("intranet-user-profile-wrap"), loader: null, size: 100});

			BX.ajax.runComponentAction(this.componentName, "setIntegratorRights", {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {}
			}).then(function (response) {
				if (response.data === true)
				{
					location.reload();
				}
				else
				{
					this.hideLoader({loader: loader});
					this.showErrorPopup("Error");
				}
			}, function (response) {
				this.hideLoader({loader: loader});
				this.showErrorPopup(response["errors"][0].message);
			}.bind(this));
		},

		removeAdminRights: function()
		{
			var loader = this.showLoader({node: BX("intranet-user-profile-wrap"), loader: null, size: 100});

			BX.ajax.runComponentAction(this.componentName, "removeAdminRights", {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {}
			}).then(function (response) {
				if (response.data === true)
				{
					BX.SidePanel.Instance.postMessageTop(window, 'userProfileSlider::reloadList', {});
					location.reload();
				}
				else
				{
					this.hideLoader({loader: loader});
					this.showErrorPopup("Error");
				}
			}, function (response) {
				this.hideLoader({loader: loader});
				this.showErrorPopup(response["errors"][0].message);
			}.bind(this));
		},

		fireUser: function()
		{
			var loader = this.showLoader({node: BX("intranet-user-profile-wrap"), loader: null, size: 100});

			BX.ajax.runComponentAction(this.componentName, "fireUser", {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {}
			}).then(function (response) {
				if (response.data === true)
				{
					BX.SidePanel.Instance.postMessageTop(window, 'userProfileSlider::reloadList', {});
					location.reload();
				}
				else
				{
					this.hideLoader({loader: loader});
					this.showErrorPopup("Error");
				}
			}, function (response) {
				this.hideLoader({loader: loader});
				this.showErrorPopup(response["errors"][0].message);
			}.bind(this));
		},

		hireUser: function()
		{
			var loader = this.showLoader({node: BX("intranet-user-profile-wrap"), loader: null, size: 100});

			BX.ajax.runComponentAction(this.componentName, "hireUser", {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {}
			}).then(function (response) {
				if (response.data === true)
				{
					location.reload();
				}
				else
				{
					this.hideLoader({loader: loader});
					this.showErrorPopup("Error");
				}
			}, function (response) {

				this.hideLoader({loader: loader});
			}.bind(this));
		},

		deleteUser: function()
		{
			var loader = this.showLoader({node: BX("intranet-user-profile-wrap"), loader: null, size: 100});

			BX.ajax.runComponentAction(this.componentName, "deleteUser", {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {}
			}).then(function (response) {
				if (response.data === true)
				{
					BX.SidePanel.Instance.postMessageTop(window, 'userProfileSlider::reloadList', {});
					BX.SidePanel.Instance.close();
				}
				else
				{
					this.hideLoader({loader: loader});
					this.showErrorPopup("Error");
				}
			}, function (response) {
				this.hideLoader({loader: loader});
				//this.showErrorPopup(response["errors"][0].message);
				this.showFireInvitedUserPopup(this.fireUser.bind(this));
			}.bind(this));
		},

		reinviteUser: function()
		{
			var loader = this.showLoader({node: BX("intranet-user-profile-wrap"), loader: null, size: 100});

			BX.ajax.runAction('intranet.controller.invite.reinvite', {
				data: {
					params: {
						userId: this.userId,
						extranet: (this.isExtranetUser == "Y" ? 'Y' : 'N')
					}
				}
			}).then(function (response) {
				this.hideLoader({loader: loader});
				if (response.data.result)
				{
					BX.PopupWindowManager.create('intranet-user-profile-invited-popup', null, {
						content: '<p>'+BX.message("INTRANET_USER_PROFILE_REINVITE_SUCCESS")+'</p>',
						offsetLeft:27,
						offsetTop:7,
						autoHide:true
					}).show();
				}
			}.bind(this), function (response) {
				this.hideLoader({loader: loader});
			}.bind(this));
		},

		moveToIntranet: function(isEmail)
		{
			if (isEmail !== true)
				isEmail = false;

			BX.PopupWindowManager.create("BXExtranet2Intranet", null, {
				autoHide: false,
				zIndex: 0,
				offsetLeft: 0,
				offsetTop: 0,
				overlay : true,
				draggable: {restrict:true},
				closeByEsc: true,
				titleBar: BX.message("INTRANET_USER_PROFILE_MOVE_TO_INTRANET_TITLE"),
				closeIcon: false,
				width: 500,
				buttons: [
					new BX.UI.CreateButton({
						text: BX.message("INTRANET_USER_PROFILE_MOVE"),
						events: {
							click: BX.proxy(function () {
								var button = BX.proxy_context;
								BX.addClass(button.button, "ui-btn-wait");

								var form = BX('moveToIntranetForm');
								if(BX.type.isDomNode(form))
								{
									BX.ajax.runComponentAction(this.componentName, "moveToIntranet", {
										signedParameters: this.signedParameters,
										mode: 'ajax',
										data: {
											departmentId: BX("toIntranetDepartment").value,
											isEmail: isEmail ? "Y" : "N"
										}
									}).then(function (response) {
										if (response.data)
										{
											button.context.setContent(response.data);
											button.context.setButtons([
												new BX.UI.CloseButton({
													events : {
														click: function () {
															location.reload();
														}
													}
												})
											]);
										}
									}, function (response) {
										BX.removeClass(button.button, "ui-btn-wait");

										var form = BX('moveToIntranetForm');
										if(BX.type.isDomNode(form) && !BX("moveToIntranetError"))
										{
											var errorBlock = BX.create("div", {
												attrs: {
													id: "moveToIntranetError",
													class: "ui-alert ui-alert-danger ui-alert-icon-danger"
												},
												children: [
													BX.create("span", {
														attrs: {class: "ui-alert-message"},
														html: response["errors"][0].message
													})
												]
											});
											form.insertBefore(errorBlock, BX.findChild(form));
										}

									}.bind(this));
								}
							}, this)
						}
					}),

					new BX.UI.CancelButton({
						events : {
							click: function () {
								this.context.close();
							}
						}
					})
				],
				events: {
					onAfterPopupShow: BX.proxy(function()
					{
						var popup = BX.proxy_context;
						popup.setContent('<div style="width:450px;height:230px"></div>');

						var loader = this.showLoader({node: popup.contentContainer, loader: null, size: 100});

						BX.ajax.post(
							'/bitrix/tools/b24_extranet2intranet.php',
							{
								USER_ID: this.userId,
								IS_EMAIL: isEmail ? 'Y' : 'N'
							},
							BX.proxy(function(result)
							{
								this.hideLoader({loader: loader});
								popup.setContent(result);
							}, this)
						);
					}, this)
				}
			}).show();
		},

		showLoader: function(params)
		{
			var loader = null;

			if (params.node)
			{
				if (params.loader === null)
				{
					loader = new BX.Loader({
						target: params.node,
						size: params.hasOwnProperty("size") ? params.size : 40
					});
				}
				else
				{
					loader = params.loader;
				}

				loader.show();
			}

			return loader;
		},

		hideLoader: function(params)
		{
			if (params.loader !== null)
			{
				params.loader.hide();
			}

			if (params.node)
			{
				BX.cleanNode(params.node);
			}

			if (params.loader !== null)
			{
				params.loader = null;
			}
		},

		processSliderCloseEvent: function(params)
		{
			BX.addCustomEvent('SidePanel.Slider:onMessage', function(event) {

				if (event.getSlider() != BX.SidePanel.Instance.getSliderByWindow(window))
				{
					return;
				}

				if (event.getEventId() != 'SidePanel.Wrapper:onClose')
				{
					return;
				}

				var data = event.getData();

				if (!BX.type.isNotEmptyObject(data.sliderData))
				{
					return;
				}

				var
					entityType = data.sliderData.get('entityType'),
					entityId = data.sliderData.get('entityId');

				if (
					BX.type.isNotEmptyString(entityType)
					&& entityType == params.entityType
					&& entityId == this.userId
				)
				{
					params.callback();
				}
			}.bind(this));
		},

		changeGdpr: function (inputNode)
		{
			var requestData = {
				type: inputNode.name,
				value: inputNode.checked ? "Y" : "N"
			};

			BX.ajax.runComponentAction(this.componentName, "changeGdpr", {
				signedParameters: this.signedParameters,
				mode: 'class',
				data: requestData
			}).then(function (response) {

			}, function (response) {

			}.bind(this));
		},

		animateGdprBlock: function (element)
		{
			var sliderTarget = document.querySelector('[data-role="' + element.getAttribute('for') + '"]');

			if(element.classList.contains('intranet-user-profile-column-block-title-dropdown--open'))
			{
				element.classList.remove('intranet-user-profile-column-block-title-dropdown--open');
				sliderTarget.style.height = null;
			}
			else
			{
				element.classList.add('intranet-user-profile-column-block-title-dropdown--open');
				sliderTarget.style.height = sliderTarget.firstElementChild.offsetHeight + 'px';
			}
		}
	}
})();
