(function()
{
	const namespace = BX.namespace('BX.Intranet.UserProfile');
	if (namespace.Manager)
	{
		return;
	}

	namespace.Manager = function(params)
	{
		this.init(params);
	};

	namespace.Manager.prototype = {
		init(params)
		{
			this.signedParameters = params.signedParameters;
			this.componentName = params.componentName;
			this.canEditProfile = params.canEditProfile === 'Y';
			this.userId = params.userId || '';
			this.userStatus = params.userStatus || '';
			this.isCollaber = params.isCollaber === 'Y';
			this.userFullName = params.userFullName || '';
			this.userPhoto = params.userPhoto || '';
			this.isIntegratorUser = params.isIntegratorUser === 'Y';
			this.isOwnProfile = params.isOwnProfile === 'Y';
			this.isSessionAdmin = params.isSessionAdmin === 'Y';
			this.urls = params.urls;
			this.isExtranetUser = params.isExtranetUser === 'Y';
			this.adminRightsRestricted = params.adminRightsRestricted === 'Y';
			this.delegateAdminRightsRestricted = params.delegateAdminRightsRestricted === 'Y';
			this.isFireUserEnabled = params.isFireUserEnabled === 'Y';
			this.showSonetAdmin = params.showSonetAdmin === 'Y';
			this.languageId = params.languageId;
			this.siteId = params.siteId;
			this.isCloud = params.isCloud === 'Y';
			this.isRusCloud = params.isRusCloud === 'Y';
			this.isCurrentUserIntegrator = params.isCurrentUserIntegrator === 'Y';
			this.personalMobile = params.personalMobile;
			this.personalPhoto = params.personalPhoto;
			this.isCurrentUserAdmin = params.isCurrentUserAdmin;
			this.avatarUri = BX.type.isNotEmptyString(params.photo) ? params.photo : null;
			this.userpicUploadAttribute = params.userpicUploadAttribute ?? '';
			this.actionsAvailability = params.actionsAvailability;
			this.rootDepartment = params.rootDepartment;

			this.entityEditorInstance = new namespace.EntityEditor({
				managerInstance: this,
				params,
			});

			BX.ready(() => {
				this.tagsManagerInstance = new namespace.Tags({
					managerInstance: this,
					inputNode: document.getElementById('intranet-user-profile-tags-input'),
					tagsNode: document.getElementById('intranet-user-profile-tags'),
				});

				this.stressLevelManagerInstance = new namespace.StressLevel({
					managerInstance: this,
					options: params,
				});

				this.gratsManagerInstance = new namespace.Grats({
					managerInstance: this,
					options: params,
				});

				this.profilePostManagerInstance = new namespace.ProfilePost({
					managerInstance: this,
					options: params,
				});

				this.initAvatar();
				this.initAvailableActions();
				this.initAvatarLoader();

				if (this.isCloud)
				{
					this.initGdpr();
				}

				const subordinateMoreButton = BX('intranet-user-profile-subordinate-more');
				if (BX.type.isDomNode(subordinateMoreButton))
				{
					BX.bind(subordinateMoreButton, 'click', () => {
						this.loadMoreUsers(subordinateMoreButton);
					});
				}

				const managerMoreButton = BX('intranet-user-profile-manages-more');
				if (BX.type.isDomNode(subordinateMoreButton))
				{
					BX.bind(managerMoreButton, 'click', () => {
						this.loadMoreUsers(managerMoreButton);
					});
				}

				// hack for form view button
				const bottomContainer = document.querySelector('.intranet-user-profile-bottom-controls');
				const cardButton = document.getElementById('intranet-user-profile_buttons');
				if (BX.type.isDomNode(bottomContainer) && BX.type.isDomNode(cardButton))
				{
					const cardButtonLink = cardButton.querySelector('.ui-entity-settings-link');
					cardButtonLink.setAttribute('class', 'ui-btn ui-btn-sm ui-btn-light-border ui-btn-themes');
					cardButton.parentNode.removeChild(cardButton);
					bottomContainer.appendChild(cardButtonLink);
				}

				BX.addCustomEvent("BX.UI.EntityEditorAjax:onSubmit", BX.proxy((fields) => {
					this.userFullName = fields.FULL_NAME;
				}, this));
			});
		},

		initAvatar()
		{
			const avatarOptions = {
				size: 212,
				userpicPath: this.avatarUri ? encodeURI(this.avatarUri) : null,
			};

			if (this.userStatus === 'collaber')
			{
				this.avatar = new BX.UI.AvatarRoundGuest(avatarOptions);
			}
			else if (this.userStatus === 'extranet')
			{
				this.avatar = new BX.UI.AvatarRoundExtranet(avatarOptions);
			}
			else
			{
				this.avatar = new BX.UI.AvatarRound(avatarOptions);
			}

			const avatarWrapper = BX.Tag.render`<div class="intranet-user-profile-photo" id="intranet-user-profile-photo"></div>`;
			this.avatar.renderTo(avatarWrapper);
			const avatarContainer = BX('intranet-user-profile-userpic-avatar');
			BX.Dom.append(avatarWrapper, avatarContainer);

			if (this.canEditProfile || this.isOwnProfile)
			{
				BX.Dom.append(this.getAvatarEditorButtons(), avatarContainer);
				BX.Dom.append(this.getAvatarRemoveButton(), avatarContainer);
			}
		},

		getAvatarEditorButtons()
		{
			return BX.Tag.render`
				<div class="intranet-user-profile-userpic-load">
					<div class="intranet-user-profile-userpic-create" id="intranet-user-profile-photo-camera">
						${BX.message('INTRANET_USER_PROFILE_AVATAR_CAMERA')}
					</div>
					<div class="intranet-user-profile-userpic-upload" id="intranet-user-profile-photo-file" ${this.userpicUploadAttribute}>
						${BX.message('INTRANET_USER_PROFILE_AVATAR_LOAD')}
					</div>
				</div>
			`;
		},

		getAvatarRemoveButton()
		{
			return BX.Tag.render`
				<div class="intranet-user-profile-userpic-remove" id="intranet-user-profile-photo-remove"
					${this.personalPhoto ? '' : 'hidden'}>
				</div>
			`;
		},

		initAvailableActions()
		{
			const actionElement = document.querySelector("[data-role='user-profile-actions-button']");
			if (BX.type.isDomNode(actionElement))
			{
				BX.bind(actionElement, 'click', BX.proxy(function() {
					this.showActionPopup(BX.proxy_context);
				}, this));
			}
		},

		initGdpr()
		{
			const gdprInputs = document.querySelectorAll("[data-role='gdpr-input']");
			gdprInputs.forEach(
				(currentValue, currentIndex, listObj) => {
					BX.bind(currentValue, 'change', () => {
						this.changeGdpr(currentValue);
					});
				},
			);

			const dropdownTarget = document.querySelector('.intranet-user-profile-column-block-title-dropdown');
			BX.bind(dropdownTarget, 'click', () => {
				this.animateGdprBlock(dropdownTarget);
			});
		},

		initAvatarLoader()
		{
			if (BX('intranet-user-profile-photo-camera')
				&& BX('intranet-user-profile-photo-file'))
			{
				if (BX.AvatarEditor)
				{
					setTimeout(() => {
						if (BX.AvatarEditor.isCameraAvailable() !== true)
						{
							BX.hide(BX('intranet-user-profile-photo-camera'));
						}
					}, 0);

					const getEditor = function() {
						let editor = BX.AvatarEditor.getInstanceById('intranet-user-profile-photo-file');
						if (editor === null)
						{
							editor = BX.AvatarEditor.createInstance('intranet-user-profile-photo-file', {
								enableCamera: true,
								enableMask: true,
							});
							editor.subscribeOnFormIsReady('newPhoto', this.changePhoto.bind(this));
						}

						return editor;
					}.bind(this);

					BX.bind(BX('intranet-user-profile-photo-camera'), 'click', () =>
					{ getEditor().show('camera');
					});
					BX.bind(BX('intranet-user-profile-photo-file'), 'click', () =>
                                                                      { getEditor().show('file');
					});
					BX.bind(BX('intranet-user-profile-photo-mask'), 'click', () =>
                                                                      { getEditor().show('mask');
					});
				}
				else
				{
					BX.hide(BX('intranet-user-profile-photo-camera'));
					BX.hide(BX('intranet-user-profile-photo-file'));
					BX.hide(BX('intranet-user-profile-photo-mask'));
				}
			}

			BX.bind(BX('intranet-user-profile-photo-remove'), 'click', (() => {
				if (this.avatar.picPath)
				{
					this.showConfirmPopup(BX.message('INTRANET_USER_PROFILE_PHOTO_DELETE_CONFIRM'), this.deletePhoto.bind(this));
				}
			}));
		},

		showActionPopup(bindElement)
		{
			const menuItems = [];

			if (this.showSonetAdmin)
			{
				menuItems.push({
					text: BX.message(this.isSessionAdmin ? 'INTRANET_USER_PROFILE_QUIT_ADMIN_MODE' : 'INTRANET_USER_PROFILE_ADMIN_MODE'),
					className: 'menu-popup-no-icon',
					onclick() {
						this.popupWindow.close();
						__SASSetAdmin();
					},
				});
			}

			if (this.userStatus === 'admin' && this.canEditProfile && !this.isOwnProfile)
			{
				menuItems.push({
					text: BX.message('INTRANET_USER_PROFILE_REMOVE_ADMIN_RIGHTS'),
					className: 'menu-popup-no-icon',
					onclick: BX.proxy(function() {
						BX.proxy_context.popupWindow.close();
						this.removeAdminRights();
					}, this),
				});
			}

			if (this.userStatus === 'employee' && this.canEditProfile && !this.isOwnProfile && !this.isCurrentUserIntegrator)
			{
				var itemText = BX.message('INTRANET_USER_PROFILE_SET_ADMIN_RIGHTS');
				if (this.delegateAdminRightsRestricted)
				{
					itemText += "<span class='intranet-user-profile-lock-icon'></span>";
				}
				menuItems.push({
					html: itemText,
					onclick: BX.proxy(function() {
						BX.proxy_context.popupWindow.close();
						if (this.adminRightsRestricted)
						{
							if (this.delegateAdminRightsRestricted)
							{
								top.BX.UI.InfoHelper.show('limit_admin_admins');
							}
							else
							{
								this.showConfirmPopup(BX.message('INTRANET_USER_PROFILE_MOVE_ADMIN_RIGHTS_CONFIRM'), this.setAdminRights.bind(this));
							}
						}
						else if (this.isCloud)
						{
							this.getAdminConfirmPopup().show();
						}
						else
						{
							this.setAdminRights();
						}
					}, this),
				});
			}

			if (
				this.actionsAvailability.fire
				&& !BX.util.in_array(this.userStatus, ['email', 'shop', 'visitor', 'fired'])
			)
			{
				itemText = BX.message('INTRANET_USER_PROFILE_FIRE');
				if (!this.isFireUserEnabled && !this.isIntegratorUser)
				{
					itemText += "<span class='intranet-user-profile-lock-icon'></span>";
				}

				menuItems.push({
					text: itemText,
					className: 'menu-popup-no-icon',
					onclick: BX.proxy(function() {
						BX.proxy_context.popupWindow.close();
						if (!this.isFireUserEnabled && !this.isIntegratorUser)
						{
							top.BX.UI.InfoHelper.show('limit_dismiss');
						}
						else
						{
							this.showConfirmPopup(BX.message('INTRANET_USER_PROFILE_FIRE_CONFIRM'), this.fireUser.bind(this));
						}
					}, this),
				});
			}

			if (this.actionsAvailability.restore)
			{
				menuItems.push({
					text: BX.message('INTRANET_USER_PROFILE_HIRE'),
					className: 'menu-popup-no-icon',
					onclick: BX.proxy(function() {
						BX.proxy_context.popupWindow.close();
						this.showConfirmPopup(BX.message('INTRANET_USER_PROFILE_HIRE_CONFIRM'), this.hireUser.bind(this));
					}, this),
				});
			}

			if (this.actionsAvailability.confirm)
			{
				menuItems.push({
					text: BX.message('INTRANET_USER_PROFILE_ACTION_CONFIRM'),
					className: 'menu-popup-no-icon',
					onclick: BX.proxy(function() {
						BX.proxy_context.popupWindow.close();
						this.confirmUserRequest('Y');
					}, this),
				});
			}

			if (this.actionsAvailability.decline)
			{
				menuItems.push({
					text: BX.message('INTRANET_USERPROFILE_ACTION_REFUSE'),
					className: 'menu-popup-no-icon',
					onclick: BX.proxy(function() {
						BX.proxy_context.popupWindow.close();
						this.confirmUserRequest('N');
					}, this),
				});
			}

			if (this.userStatus === 'invited' && this.canEditProfile && !this.isOwnProfile)
			{
				menuItems.push({
					text: BX.message('INTRANET_USER_PROFILE_REINVITE'),
					className: 'menu-popup-no-icon',
					onclick: BX.proxy(function() {
						BX.proxy_context.popupWindow.close();
						this.reinviteUser();
					}, this),
				});
			}

			if (this.actionsAvailability.delete)
			{
				menuItems.push({
					text: BX.message('INTRANET_USER_PROFILE_DELETE'),
					className: 'menu-popup-no-icon',
					onclick: BX.proxy(function() {
						BX.proxy_context.popupWindow.close();
						this.showConfirmPopup(BX.message('INTRANET_USER_PROFILE_DELETE_CONFIRM'), this.deleteUser.bind(this));
					}, this),
				});
			}

			if (this.isExtranetUser && this.canEditProfile && !this.isOwnProfile && this.isCloud)
			{
				menuItems.push({
					text: BX.message('INTRANET_USER_PROFILE_MOVE_TO_INTRANET_MSGVER_1'),
					className: 'menu-popup-no-icon',
					onclick: BX.proxy(function() {
						BX.proxy_context.popupWindow.close();
						if (this.transferToIntranet)
						{
							this.transferToIntranet.destroy();
							this.transferToIntranet = null;
						}
						const data = {
							userType: this.isCollaber ? 'collaber' : 'extranet',
							userName: this.userFullName,
							userPhoto: this.userPhoto,
							componentName: this.componentName,
							signedParameters: this.signedParameters,
							rootDepartment: this.rootDepartment,
						};
						this.transferToIntranet = new BX.Intranet.TransferToIntranetPopup(data);
						this.transferToIntranet.show();
					}, this),
				});
			}

			if (
				this.isCloud
				&& this.canEditProfile && !this.isOwnProfile
				&& !this.isIntegratorUser
				&& !BX.util.in_array(this.userStatus, ['email', 'shop', 'waiting', 'fired', 'visitor'])
			)
			{
				menuItems.push({
					text: BX.message('INTRANET_USER_PROFILE_SET_INEGRATOR_RIGHTS'),
					className: 'menu-popup-no-icon',
					onclick: BX.proxy(function() {
						BX.proxy_context.popupWindow.close();
						this.showConfirmPopup(BX.message('INTRANET_USER_PROFILE_SET_INTEGRATOR_RIGHTS_CONFIRM'), this.setIntegratorRights.bind(this));
					}, this),
				});
			}

			BX.PopupMenu.show(
				'user-profile-action-popup',
				bindElement,
				menuItems,
				{
					offsetTop: 0,
					offsetLeft: 10,
					angle: true,
					events: {
						onPopupClose()
						{
							BX.PopupMenu.destroy();
						},
					},
				},
			);
		},

		showConfirmPopup(text, confirmCallback)
		{
			BX.PopupWindowManager.create({
				id: 'intranet-user-profile-confirm-popup',
				content:
					BX.create('div', {
						props: {
							style: 'max-width: 450px',
						},
						html: text,
					}),
				closeIcon: false,
				lightShadow: true,
				offsetLeft: 100,
				overlay: true,
				contentPadding: 10,
				buttons: [
					new BX.UI.CreateButton({
						text: BX.message('INTRANET_USER_PROFILE_YES'),
						events: {
							click(button, event) {
								confirmCallback();
								event.stopPropagation();
								this.context.close();
							},
						},
					}),
					new BX.UI.CancelButton({
						text: BX.message('INTRANET_USER_PROFILE_NO'),
						events: {
							click() {
								this.context.close();
							},
						},
					}),
				],
				events: {
					onPopupClose()
					{
						this.destroy();
					},
				},
			}).show();
		},

		getAdminConfirmPopup()
		{
			const popup = BX.PopupWindowManager.create({
				id: 'intranet-user-profile-admin-confirm-popup',
				content:
					BX.create('div', {
						props: {
							style: 'max-width: 450px',
						},
						html: BX.message('INTRANET_USER_PROFILE_MOVE_ADMIN_RIGHTS_SECURITY_CONFIRM_DESCRIPTION_MSGVER_1'),
					}),
				closeIcon: false,
				lightShadow: true,
				offsetLeft: 100,
				overlay: true,
				titleBar: BX.message('INTRANET_USER_PROFILE_MOVE_ADMIN_RIGHTS_SECURITY_CONFIRM_TITLE'),
				contentPadding: 10,
				buttons: [
					new BX.UI.CreateButton({
						text: BX.message('INTRANET_USER_PROFILE_CONFIRM_YES_MSGVER_1'),
						events: {
							click: (button, event) => {
								this.setAdminRights();
								event.stopPropagation();
								popup.close();
							},
						},
					}),
					new BX.UI.CreateButton({
						text: BX.message('INTRANET_USER_PROFILE_CONFIRM_YES_INTEGRATOR'),
						events: {
							click: () => {
								this.setIntegratorRights();
								popup.close();
							},
						},
					}),
					new BX.UI.CancelButton({
						text: BX.message('INTRANET_USER_PROFILE_CONFIRM_NO_MSGVER_1'),
						events: {
							click: () => {
								popup.close();
							},
						},
					}),
				],
				events: {
					onPopupClose: () => {
						popup.destroy();
					},
				},
			});

			return popup;
		},

		showFireInvitedUserPopup(callback)
		{
			BX.PopupWindowManager.create({
				id: 'intranet-user-profile-fire-invited-popup',
				content:
					BX.create('div', {
						props: {
							style: 'max-width: 450px',
						},
						html: BX.message('INTRANET_USER_PROFILE_FIRE_INVITED_USER'),
					}),
				closeIcon: true,
				lightShadow: true,
				offsetLeft: 100,
				overlay: false,
				contentPadding: 10,
				buttons: [
					new BX.UI.CreateButton({
						text: BX.message('INTRANET_USER_PROFILE_YES'),
						events: {
							click(button) {
								button.setWaiting();
								this.context.close();
								callback();
							},
						},
					}),

					new BX.UI.CancelButton({
						text: BX.message('INTRANET_USER_PROFILE_NO'),
						events: {
							click() {
								this.context.close();
							},
						},
					}),
				],
			}).show();
		},

		showIntegratorPartnerErrorPopup()
		{
			const popup = BX.PopupWindowManager.create('popup-message', null, {
				id: 'intranet-user-profile-error-popup',
				content:
					BX.create('div', {
						props: {
							style: 'max-width: 450px',
						},
						html: BX.message('INTRANET_USER_PROFILE_INTEGRATOR_ERROR_NOT_PARTNER'),
					}),
				closeIcon: false,
				lightShadow: true,
				offsetLeft: 100,
				overlay: true,
				contentPadding: 10,
				buttons: [
					new BX.UI.CreateButton({
						text: BX.message('INTRANET_USER_PROFILE_INTEGRATOR_ERROR_NOT_PARTNER_CLOSE'),
						events: {
							click()
							{
								this.context.close();
							},
						},
					}),
				],
			});
			popup.show();
		},

		showErrorPopup(error)
		{
			if (!error)
			{
				return;
			}

			BX.PopupWindowManager.create({
				id: 'intranet-user-profile-error-popup',
				content:
					BX.create('div', {
						props: {
							style: 'max-width: 450px',
						},
						html: error,
					}),
				closeIcon: true,
				lightShadow: true,
				offsetLeft: 100,
				overlay: false,
				contentPadding: 10,
			}).show();
		},

		loadMoreUsers(button)
		{
			if (!BX.type.isDomNode(button))
			{
				return;
			}

			const block = button.parentNode;

			const items = block.querySelectorAll("[data-role='user-profile-item']");
			const itemsLength = items.length;
			for (let i = 0; i < 4 && i < itemsLength; i++)
			{
				items[i].style.display = 'inline-block';
				items[i].setAttribute('data-role', '');
			}

			if (itemsLength - 4 <= 0)
			{
				button.style.display = 'none';
			}
			else
			{
				BX.findChild(button).innerHTML = itemsLength - 4;
			}
		},

		changePhoto(event)
		{
			const dataObj = event.getData().form;
			const loader = this.showLoader({ node: BX('intranet-user-profile-photo'), loader: null, size: 100 });

			BX.ajax.runComponentAction(this.componentName, 'loadPhoto', {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: dataObj,
			})
				.then((response) => {
					if (response.data)
					{
						this.avatar.setUserPic(encodeURI(response.data));
						(top || window).BX.onCustomEvent('BX.Intranet.UserProfile:Avatar:changed', [{
							userId: this.userId,
							url: response.data,
						}]);
					}

					this.hideLoader({ loader });
				})
				.catch((response) => {
					this.hideLoader({ loader });
					this.showErrorPopup(response.errors[0].message);
				});

			BX('intranet-user-profile-photo-remove').hidden = false;
		},

		deletePhoto()
		{
			const loader = this.showLoader({ node: BX('intranet-user-profile-photo'), loader: null, size: 100 });

			BX.ajax.runComponentAction(this.componentName, 'deletePhoto', {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {},
			})
				.then((response) => {
					this.avatar.removeUserPic();
					this.hideLoader({ loader });
					BX('intranet-user-profile-photo-remove').hidden = true;
				})
				.catch((response) => {
					this.hideLoader({ loader });
					this.showErrorPopup(response.errors[0].message);
				});
		},

		setAdminRights()
		{
			const loader = this.showLoader({ node: BX('intranet-user-profile-wrap'), loader: null, size: 100 });

			BX.ajax.runComponentAction(this.componentName, 'setAdminRights', {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {},
			}).then((response) => {
				if (response.data === true)
				{
					BX.SidePanel.Instance.postMessageTop(window, 'userProfileSlider::reloadList', {});
					location.reload();
				}
				else
				{
					this.hideLoader({ loader });
					BX.UI.Notification.Center.notify({
						content: 'Error',
						position: 'top-right',
						autoHideDelay: 10000,
					});
					BX.PopupWindowManager.getPopupById('intranet-user-profile-confirm-popup').close();
				}
			}).catch((response) => {
				this.hideLoader({ loader });
				BX.UI.Notification.Center.notify({
					content: response.errors[0].message,
					position: 'top-right',
					autoHideDelay: 10000,
				});
				BX.PopupWindowManager.getPopupById('intranet-user-profile-confirm-popup').close();
			});
		},

		setIntegratorRights()
		{
			const loader = this.showLoader({ node: BX('intranet-user-profile-wrap'), loader: null, size: 100 });

			BX.ajax.runComponentAction(this.componentName, 'setIntegratorRights', {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {},
			}).then(function(response) {
				if (response.data === true)
				{
					location.reload();
				}
				else
				{
					this.hideLoader({ loader });
					this.showErrorPopup('Error');
				}
			}, (response) => {
				this.hideLoader({ loader });
				if (response.errors[0].code === 'INTEGRATOR_ERROR_NOT_PARTNER')
				{
					this.showIntegratorPartnerErrorPopup();
				}
				else
				{
					this.showErrorPopup(response.errors[0].message);
				}
			});
		},

		removeAdminRights()
		{
			const loader = this.showLoader({ node: BX('intranet-user-profile-wrap'), loader: null, size: 100 });

			BX.ajax.runComponentAction(this.componentName, 'removeAdminRights', {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {},
			}).then(function(response) {
				if (response.data === true)
				{
					BX.SidePanel.Instance.postMessageTop(window, 'userProfileSlider::reloadList', {});
					location.reload();
				}
				else
				{
					this.hideLoader({ loader });
					this.showErrorPopup('Error');
				}
			}, (response) => {
				this.hideLoader({ loader });
				this.showErrorPopup(response.errors[0].message);
			});
		},

		fireUser()
		{
			const loader = this.showLoader({ node: BX('intranet-user-profile-wrap'), loader: null, size: 100 });

			BX.ajax.runAction('intranet.v2.User.fire', {
				data: {
					userId: this.userId,
				},
			}).then((response) => {
				if (response.data === true)
				{
					BX.SidePanel.Instance.postMessageTop(window, 'userProfileSlider::reloadList', {});
					location.reload();
				}
				else
				{
					this.hideLoader({ loader });
					this.showErrorPopup('Error');
				}
			}).catch((response) => {
				this.hideLoader({ loader });
				this.showErrorPopup(response.errors[0].message);
			});
		},

		hireUser()
		{
			const loader = this.showLoader({ node: BX('intranet-user-profile-wrap'), loader: null, size: 100 });

			BX.ajax.runAction('intranet.v2.User.restore', {
				data: {
					userId: this.userId,
				},
			}).then((response) => {
				if (response.data === true)
				{
					location.reload();
				}
				else
				{
					this.hideLoader({ loader });
					this.showErrorPopup('Error');
				}
			}).catch((response) => {
				this.hideLoader({ loader });
			});
		},

		confirmUserRequest(confirm)
		{
			const loader = this.showLoader({ node: BX('intranet-user-profile-wrap'), loader: null, size: 100 });
			BX.ajax.runAction('intranet.controller.invite.confirmUserRequest', {
				signedParameters: this.signedParameters,
				data: {
					userId: this.userId,
					isAccept: confirm,
				},
			}).then(function(response) {
				if (response.data === true)
				{
					if (confirm === 'Y')
					{
						location.reload();
					}
					else
					{
						BX.SidePanel.Instance.postMessageTop(window, 'userProfileSlider::reloadList', {});
						BX.SidePanel.Instance.close();
					}
				}
				else
				{
					this.hideLoader({ loader });
					this.showErrorPopup('Error');
				}
			}, (response) => {
				this.hideLoader({ loader });
			});
		},

		deleteUser()
		{
			const loader = this.showLoader({ node: BX('intranet-user-profile-wrap'), loader: null, size: 100 });

			BX.ajax.runAction('intranet.v2.User.delete', {
				data: {
					userId: this.userId,
				},
			}).then((response) => {
				if (response.data === true)
				{
					BX.SidePanel.Instance.postMessageTop(window, 'userProfileSlider::reloadList', {});
					BX.SidePanel.Instance.close();
				}
				else
				{
					this.hideLoader({ loader });
					this.showErrorPopup('Error');
				}
			}).catch((response) => {
				this.hideLoader({ loader });
				this.showFireInvitedUserPopup(this.fireUser.bind(this));
			});
		},

		reinviteUser()
		{
			const loader = this.showLoader({ node: BX('intranet-user-profile-wrap'), loader: null, size: 100 });

			BX.ajax.runAction('intranet.controller.invite.reinvite', {
				data: {
					params: {
						userId: this.userId,
						extranet: (this.isExtranetUser == 'Y' ? 'Y' : 'N'),
					},
				},
			}).then((response) => {
				this.hideLoader({ loader });
				if (response.data.result)
				{
					BX.PopupWindowManager.create('intranet-user-profile-invited-popup', null, {
						content: `<p>${BX.message('INTRANET_USER_PROFILE_REINVITE_SUCCESS')}</p>`,
						offsetLeft: 27,
						offsetTop: 7,
						autoHide: true,
					}).show();
					BX.SidePanel.Instance.postMessageTop(window, 'BX.Bitrix24.EmailConfirmation:showPopup');
				}
			}, (response) => {
				this.hideLoader({ loader });
			});
		},

		showLoader(params)
		{
			let loader = null;

			if (params.node)
			{
				if (params.loader === null)
				{
					loader = new BX.Loader({
						target: params.node,
						size: params.hasOwnProperty('size') ? params.size : 40,
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

		hideLoader(params)
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

		processSliderCloseEvent(params)
		{
			BX.addCustomEvent('SidePanel.Slider:onMessage', (event) => {
				if (event.getSlider() != BX.SidePanel.Instance.getSliderByWindow(window))
				{
					return;
				}

				if (event.getEventId() != 'SidePanel.Wrapper:onClose')
				{
					return;
				}

				const data = event.getData();

				if (!BX.type.isNotEmptyObject(data.sliderData))
				{
					return;
				}

				const
					entityType = data.sliderData.get('entityType');
				const entityId = data.sliderData.get('entityId');

				if (
					BX.type.isNotEmptyString(entityType)
					&& entityType == params.entityType
					&& entityId == this.userId
				)
				{
					params.callback();
				}
			});
		},

		changeGdpr(inputNode)
		{
			const requestData = {
				type: inputNode.name,
				value: inputNode.checked ? 'Y' : 'N',
			};

			BX.ajax.runComponentAction(this.componentName, 'changeGdpr', {
				signedParameters: this.signedParameters,
				mode: 'class',
				data: requestData,
			}).then((response) => {}, (response) => {});
		},

		animateGdprBlock(element)
		{
			const sliderTarget = document.querySelector(`[data-role="${element.getAttribute('for')}"]`);

			if (element.classList.contains('intranet-user-profile-column-block-title-dropdown--open'))
			{
				element.classList.remove('intranet-user-profile-column-block-title-dropdown--open');
				sliderTarget.style.height = null;
			}
			else
			{
				element.classList.add('intranet-user-profile-column-block-title-dropdown--open');
				sliderTarget.style.height = `${sliderTarget.firstElementChild.offsetHeight}px`;
			}
		},
	};
})();
