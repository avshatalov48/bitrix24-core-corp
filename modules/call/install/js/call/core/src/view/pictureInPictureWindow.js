import { Dom } from 'main.core';
import { Hardware } from '../call_hardware';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Popup } from 'main.popup';
import { Utils } from 'im.v2.lib.utils';

import * as Buttons from './buttons';
import { CallUser } from './call-user';
import { FloorRequest } from './floor-request';

import '../css/picture-in-picture-window.css'
import { UnsupportedBrowserFeatures } from '../engine/unsupported_features_in_browsers';

export class PictureInPictureWindow {
	constructor(config)
	{
		this.pictureWindow = null;
		this.template = null;
		this.userPanel = null;
		this.actionsPanel = null;
		this.notificationPanel = null;

		this.buttons = {
			microphone: null,
			camera: null,
			returnToCall: null,
			stopScreen: null,
			copilot: null,
		};

		this.preferInitialWindowPlacement = config.preferInitialWindowPlacement;

		this.user = null;
		this.userData = config.currentUser;

		this.buttons = config.buttons || [];
		this.blockedButtons = config.blockedButtons || [];

		this.previousFloorRequestNotifications = config.floorRequestNotifications || [];

		this.callbacks = {
			onButtonClick: BX.type.isFunction(config.onButtonClick) ? config.onButtonClick : BX.DoNothing,
			onClose: BX.type.isFunction(config.onClose) ? config.onClose : BX.DoNothing,
		}

		this.eventEmitter = new EventEmitter(this, 'BX.Call.View');

		this.isMinSize = false;

		this.floorRequestElements = {
			counter: null,
			mainNotification: null,
			popup: null,
			popupTemplate: null,
		};

		this.floorRequestsList = [];
		this.floorRequestTimeForHidden = 5000;
		this.floorRequestTimeout = null;
		this.baseWidthPopup = 244;
	}

	_onButtonClick({ buttonName, event })
	{
		this.callbacks.onButtonClick({
			buttonName,
			event,
		})
	}

	setUserMedia(userId, track)
	{
		if (!this.user || +userId !== this.user.userModel.id)
		{
			return;
		}

		this.user.videoTrack = track;
	};

	setStats(userId, stats)
	{
		if (!this.user || +userId !== this.user.userModel.id)
		{
			return;
		}

		this.user.showStats(stats);
	}

	showFloorRequestNotification(userModel)
	{
		let notification = FloorRequest.create({
			userModel: userModel
		});

		notification.mount(this.notificationPanel);
	}

	setVideoRenderer(userId, mediaRenderer)
	{
		if (!this.user || +userId !== this.user.userModel.id)
		{
			return;
		}

		this.user.videoRenderer = mediaRenderer;
	}

	releaseLocalStream(userId)
	{
		if (!this.user || +userId !== this.user.userModel.id)
		{
			return;
		}

		this.user.releaseStream();
	}

	setCurrentUser(user)
	{

		if (!user)
		{
			return;
		}

		this.userData = user;
		const hasUser = !!this.user;
		const isCurrentUser = this.userData.userModel.id === this.user.userModel.id;

		if (hasUser && !isCurrentUser)
		{
			this.destroyCurrentUser();
		}

		if (!hasUser || !isCurrentUser)
		{
			this.renderUserPanel();
		}
	}

	destroyCurrentUser()
	{
		if (!this.user)
		{
			return;
		}

		this.user.dismount();
		this.user.destroy();
		this.user = null;
	}

	getCurrentUserId()
	{
		return this.user?.userModel.id || null;
	}

	isButtonBlocked(buttonName)
	{
		return this.blockedButtons.includes(buttonName);
	}

	updateBlockButtons(buttonsList)
	{
		this.blockedButtons = buttonsList;
		return this;
	}

	setButtons(buttonsList)
	{
		this.buttons = buttonsList;
		return this;
	}

	updateButtons()
	{
		Dom.clean(this.actionsPanel);
		this.renderButtons();
	}

	getButtonTextBySizePictureWindow(text)
	{
		return this.isMinSize ? "" : text;
	}

	renderButtons()
	{
		if (!this.actionsPanel && this.buttons.length)
		{
			this.actionsPanel = Dom.create('div', {
				props: {
					className: 'bx-call-picture-in-picture-window__actions',
				},
			});
		}

		for (let i = 0; i < this.buttons.length; i++)
		{
			switch (this.buttons[i]) {
				case 'microphone':
					this.buttons.microphone = new Buttons.DeviceButton({
						class: 'microphone',
						backgroundClass: 'bx-call-picture-in-picture-window__button-background',
						text: this.getButtonTextBySizePictureWindow(BX.message('IM_M_CALL_BTN_MIC')),
						enabled: !Hardware.isMicrophoneMuted,
						arrowHidden: true,
						arrowEnabled: false,
						showPointer: true, //todo
						blocked: this.isButtonBlocked('microphone'),
						showLevel: true,
						sideIcon: null,
						onClick: (event) =>
						{
							event.stopPropagation();
							this._onButtonClick({ buttonName: 'microphone', event });
						},
					});

					this.actionsPanel.appendChild(this.buttons.microphone.render());
					break;
				case 'camera':
					this.buttons.camera = new Buttons.DeviceButton({
						class: 'camera',
						backgroundClass: 'bx-call-picture-in-picture-window__button-background',
						text: this.getButtonTextBySizePictureWindow(BX.message('IM_M_CALL_BTN_CAMERA')),
						enabled: Hardware.isCameraOn,
						arrowHidden: true,
						blocked: this.isButtonBlocked('camera'),
						onClick: (event) =>
						{
							event.stopPropagation();
							this._onButtonClick({ buttonName: 'camera', event });
						}
					});

					this.actionsPanel.appendChild(this.buttons.camera.render());
					break;
				case 'returnToCall':
					this.buttons.returnToCall = new Buttons.SimpleButton({
						class: 'go-to-call',
						backgroundClass: 'bx-call-picture-in-picture-window__button-background bx-messenger-videocall-panel-icon-background-go-to-call',
						text: this.getButtonTextBySizePictureWindow(BX.message('CALL_BUTTON_GO_TO_CALL')),
						onClick: (event) =>
						{
							event.stopPropagation();
							this._onButtonClick({ buttonName: 'returnToCall', event });
							window.focus();
						}
					});
					this.actionsPanel.appendChild(this.buttons.returnToCall.render());
					break;
				case 'stop-screen':
					this.buttons.stopScreen = new Buttons.SimpleButton({
						class: 'stop-screen',
						backgroundClass: 'bx-call-picture-in-picture-window__button-background',
						text: this.getButtonTextBySizePictureWindow(BX.message('CALL_BUTTON_STOP_SCREEN')),
						blocked: this.isButtonBlocked('screen'),
						onClick: (event) =>
						{
							event.stopPropagation();
							this._onButtonClick({ buttonName: 'stop-screen', event });
							window.focus();
						}
					});

					this.actionsPanel.appendChild(this.buttons.stopScreen.render());
					break;
				default:
					break;
			}
		}
	}

	renderUserPanel()
	{
		if (!this.userPanel)
		{
			this.userPanel = Dom.create('div', {
				props: {
					className: 'bx-call-picture-in-picture-window__user',
				},
			});
		}

		if (!this.user)
		{
			this.user = new CallUser({
				parentContainer: this.userPanel,
				userModel: this.userData.userModel,
				avatarBackground: this.userData.avatarBackground,
				allowPinButton: false,
				allowBackgroundItem: false,
				allowMaskItem: false,
				alwaysShowName: true,
				flipVideo: this.userData.userModel.localUser,
				hiddenFloorRequest: true,
				hiddenRemoteParticipantButtonMenu: true,
				onClick: (event) =>
				{
					this._onButtonClick({ buttonName: 'returnToCall', event });
					window.focus();
				},
			});
			this.user.mount(this.userPanel);
		}

		if (this.userData.previewRenderer)
		{
			this.user.videoRenderer = this.userData.previewRenderer;
		}

		if (this.userData.videoRenderer)
		{
			this.user.videoRenderer = this.userData.videoRenderer
		}
	}

	renderNotificationPanel()
	{
		if (!this.notificationPanel)
		{
			this.notificationPanel = Dom.create('div', {
				props: { className: 'bx-call-picture-in-picture-window__notifications bx-messenger-videocall-notification-panel' },
			});
		}
	}

	updateFloorRequestsList({ floorRequestsList, user })
	{
		if (floorRequestsList)
		{
			this.floorRequestsList = [...floorRequestsList.map(floorRequestsListItem =>
			{
				return {
					userModel: floorRequestsListItem.userModel,
					avatarBackgroundColor: floorRequestsListItem.avatarBackgroundColor,
					initials: Utils.text.getFirstLetters(floorRequestsListItem.userModel.name).toUpperCase(),
				}
			})];
			this.updateFloorRequestCounter();
			return;
		}

		if (!user)
		{
			return;
		}

		const userInFloorRequestsListIndex = this.floorRequestsList.findIndex(el => el.userModel.id === user.userModel.id);
		const hasUserInFloorRequestsList = userInFloorRequestsListIndex !== -1;
		const isActiveFloorRequestState = user.userModel.floorRequestState;

		if (hasUserInFloorRequestsList === isActiveFloorRequestState)
		{
			return;
		}

		if (isActiveFloorRequestState)
		{
			this.floorRequestsList.push({
				userModel: user.userModel,
				avatarBackgroundColor: user.avatarBackgroundColor,
				initials: Utils.text.getFirstLetters(user.userModel.name).toUpperCase(),
			});
		}

		if (!isActiveFloorRequestState)
		{
			this.floorRequestsList.splice(userInFloorRequestsListIndex, 1);
		}

		this.updateFloorRequestCounter();
	}

	updateFloorRequestCounter(withoutRenderTemplate = false)
	{
		if (this.floorRequestElements.counter)
		{
			this.floorRequestElements.counter.innerText = this.floorRequestsList.length;
			this.updateVisibilityFloorRequestCounter();
		}

		if (!withoutRenderTemplate)
		{
			this.updateTemplateForFloorRequestsPopup();
		}
	}

	getTemplateForFloorRequestsPopup()
	{
		const self = this;

		return Dom.create("div", {
			props: { className: 'bx-call-picture-in-picture-window__popup-template' },
			children: (
				function()
				{
					const items = [];

					self.floorRequestsList.forEach((floorRequestsListItem, floorRequestsListItemIndex) =>
					{
						let avatarElement;

						items.push(
							Dom.create("li", {
								props: { className: 'bx-call-picture-in-picture-window__floor-request-item' },
								children: [
									avatarElement = Dom.create("div", {
										props: {
											className: 'bx-call-picture-in-picture-window__floor-request-avatar',
										},
									}),
									Dom.create("div", {
										props: { className: 'bx-call-picture-in-picture-window__floor-request-name' },
										text: floorRequestsListItem.userModel.name,
									}),
									Dom.create("div", {
										props: { className: 'bx-call-picture-in-picture-window__floor-request-index' },
										text: floorRequestsListItemIndex + 1,
									}),
								]
							}),
						);

						if (floorRequestsListItem.userModel.avatar)
						{
							avatarElement.style.setProperty('--avatar', `url('${floorRequestsListItem.userModel.avatar}')`);
							avatarElement.innerText = '';
						}
						else
						{
							avatarElement.style.setProperty("--avatar-color", floorRequestsListItem.avatarBackgroundColor);
							avatarElement.innerText = floorRequestsListItem.initials;
						}
					});

					return items;
				}
			)(),
		});
	}

	updateTemplateForFloorRequestsPopup()
	{
		const tmpTemplate = this.getTemplateForFloorRequestsPopup();

		if (this.floorRequestElements.template)
		{
			Dom.remove(this.floorRequestElements.template)
		}

		this.floorRequestElements.popupTemplate = tmpTemplate;

		if (!this.floorRequestElements.popup)
		{
			return;
		}

		if (!this.floorRequestsList.length)
		{
			this.floorRequestElements.popup.close();
			return;
		}

		this.floorRequestElements.popup.setContent(this.floorRequestElements.popupTemplate);
	}

	closeFloorRequestMainNotification({ withoutAdditionInList, user, withoutDismount })
	{
		if (this.floorRequestTimeout)
		{
			clearTimeout(this.floorRequestTimeout);
			this.floorRequestTimeout = null;
		}

		if (this.floorRequestElements.mainNotification && !withoutDismount)
		{
			this.floorRequestElements.mainNotification.dismount();
		}

		if (!withoutAdditionInList && user)
		{
			this.updateFloorRequestsList({ user });
		}
	}

	updateFloorRequestState({ userModel, avatarBackgroundColor })
	{
		if (
			this.floorRequestElements.mainNotification
			&& this.floorRequestElements.mainNotification.userModel
			&& this.floorRequestElements.mainNotification.userModel.id === userModel.id
			&& !userModel.floorRequestState)
		{
			this.floorRequestElements.mainNotification = null;
		}

		if (userModel.floorRequestState)
		{
			this.updateFloorRequestMainNotification({
				userModel,
				avatarBackgroundColor
			});
		}

		if (!userModel.floorRequestState)
		{
			this.updateFloorRequestsList({
				user: {
					userModel,
					avatarBackgroundColor,
				}
			});
		}
	}

	updateFloorRequestMainNotification({ userModel, avatarBackgroundColor })
	{
		if (!this.notificationPanel)
		{
			this.renderNotificationPanel();
		}

		if (this.floorRequestElements.mainNotification)
		{
			this.closeFloorRequestMainNotification({
				withoutAdditionInList: false,
				user: {
					userModel: this.floorRequestElements.mainNotification.userModel,
					avatarBackgroundColor: this.floorRequestElements.mainNotification.avatarBackgroundColor,
				},
			});
		}

		this.floorRequestElements.mainNotification = FloorRequest.create({
			userModel: userModel,
			onDestroy: () =>
			{
				this.closeFloorRequestMainNotification({
					withoutAdditionInList: !userModel.floorRequestState,
					user: {
						userModel,
						avatarBackgroundColor
					},
					withoutDismount: true
				});
				this.floorRequestElements.mainNotification = null;
			},
		});

		this.floorRequestElements.mainNotification.mount(this.notificationPanel);

		this.floorRequestTimeout = setTimeout(() => {
			this.closeFloorRequestMainNotification({
				withoutAdditionInList: false,
				user: {
					userModel,
					avatarBackgroundColor,
				},
			})
		}, this.floorRequestTimeForHidden);
	}

	updateVisibilityFloorRequestCounter()
	{
		const isHidden = !this.floorRequestsList.length;

		if (!this.floorRequestElements.counter)
		{
			this.renderFloorRequestCounter();
		}

		const hiddenClass = 'bx-call-picture-in-picture-window__floor-requests_hidden';
		const hasHiddenClass = this.floorRequestElements.counter.classList.contains(hiddenClass);

		if (hasHiddenClass === isHidden)
		{
			return;
		}

		this.floorRequestElements.counter.classList.toggle(hiddenClass);
	}

	onMouseOutCallbackForPopup(event)
	{
		if (!this.floorRequestElements.popup)
		{
			return;
		}

		const composedPath = event.composedPath();
		if (composedPath.some(element => element.classList && (element.classList.contains('bx-call-picture-in-picture-window__floor-requests') || element.classList.contains('bx-call-picture-in-picture-window__popup'))))
		{
			return;
		}

		this.floorRequestElements.popup.close();
	}

	setNewSettingForPopup()
	{
		if (!this.pictureWindow.document.body || !this.floorRequestElements.popup || !this.floorRequestElements.counter)
		{
			return;
		}

		const { clientHeight, clientWidth } = this.pictureWindow.document.body;
		const bindElement = this.floorRequestElements.counter;
		const bindElementPos = bindElement.getBoundingClientRect();
		const { bottom } = bindElementPos;
		const baseOffsetHorizontalPopup = 16;
		const baseOffsetVerticalPopup = baseOffsetHorizontalPopup / 2;
		const maxWidthPopup = clientWidth - baseOffsetHorizontalPopup * 2;
		const widthPopup = this.baseWidthPopup <= maxWidthPopup ? this.baseWidthPopup : maxWidthPopup;
		const heightWidth = clientHeight - bottom - (baseOffsetVerticalPopup * 2);

		this.floorRequestElements.popup.setOffset({
			offsetLeft: clientWidth - baseOffsetHorizontalPopup - widthPopup,
			offsetTop: baseOffsetVerticalPopup,
		});

		this.floorRequestElements.popup.setWidth(widthPopup);
		this.floorRequestElements.popup.setMaxHeight(heightWidth);
	}

	renderFloorRequestCounter()
	{
		if (!!this.floorRequestElements.counter)
		{
			return;
		}

		this.floorRequestElements.counter = Dom.create('div', {
			props: {
				className: 'bx-call-picture-in-picture-window__floor-requests',
			},
			text: this.floorRequestsList.length,
			events: {
				mouseover: (event) => {
					event.stopPropagation();

					const self = this;

					if (!this.floorRequestElements.popup && !this.isMinSize)
					{
						this.updateTemplateForFloorRequestsPopup();
						this.floorRequestElements.popup = new Popup({
							className : "bx-call-picture-in-picture-window__popup",
							bindElement: this.floorRequestElements.counter,
							targetContainer: this.pictureWindow.document.body,
							content: this.floorRequestElements.popupTemplate,
							bindOptions: {
								position: "top"
							},
							autoHide: true,
							closeByEsc: true,
							background: '#ffffff',
							contentBackground: '#ffffff',
							darkMode: false,
							contentNoPaddings: true,
							animation: "fading",
							padding: 0,
							contentBorderRadius: '18px',
							zIndex: 11,
							events: {
								onPopupClose: function ()
								{
									this.destroy();
								},
								onPopupDestroy: function ()
								{
									self.floorRequestElements.popup = null;
								}
							}
						});
					}

					this.setNewSettingForPopup();
					this.floorRequestElements.popup.show();
				},
			}
		});

		this.updateFloorRequestCounter(true);
	}

	render()
	{
		this.renderButtons();
		this.renderUserPanel();
		this.renderNotificationPanel();
		this.renderFloorRequestCounter();
		this.updateFloorRequestsList({
			floorRequestsList: this.previousFloorRequestNotifications,
		})

		this.template = Dom.create('div', {
			props: {
				className: 'bx-call-picture-in-picture-window',
			},
			events: {
				click: (event) =>
				{
					this._onButtonClick({ buttonName: 'returnToCall', event });
				},
			},
		});

		if (!!this.userPanel)
		{
			this.template.appendChild(this.userPanel);
		}

		if (!!this.actionsPanel)
		{
			this.template.appendChild(this.actionsPanel);
		}

		if (!!this.notificationPanel)
		{
			this.template.appendChild(this.notificationPanel);
		}

		if (!!this.floorRequestElements.counter)
		{
			this.template.appendChild(this.floorRequestElements.counter);
		}
	}

	setSizePictureWindow()
	{
		// TODO: debounce/throttle
		if (!this.template)
		{
			return;
		}

		const pictureWindowHeight = this.template.clientHeight;
		const isMinSize = pictureWindowHeight <= 80;

		if (this.isMinSize === isMinSize)
		{
			return;
		}

		if (isMinSize && !!this.floorRequestElements.popup)
		{
			this.floorRequestElements.popup.close();
		}

		this.isMinSize = isMinSize;

		this.updateButtons();
		this.template.classList[this.isMinSize ? 'add' : 'remove']('bx-call-picture-in-picture-window_min');
	}

	setAvatarSettings()
	{
		if (!this.userPanel)
		{
			return;
		}

		const avatarSize = this.userPanel.clientHeight * 0.45;
		const avatarTextSize = avatarSize * 0.45;

		this.userPanel.style.setProperty('--avatar-size', avatarSize + 'px');
		this.userPanel.style.setProperty('--avatar-text-size', avatarTextSize + 'px');
	}

	onMouseMove(event)
	{
		this.onMouseOutCallbackForPopup(event);
	}

	onResize()
	{
		this.setAvatarSettings();
		this.setSizePictureWindow();

		if (this.floorRequestElements.popup)
		{
			this.floorRequestElements.popup.close();
		}
	}

	toggleEvents(isActive)
	{
		const eventName = isActive ? 'addEventListener' : 'removeEventListener';

		if (this.pictureWindow)
		{
			this.pictureWindow[eventName]('pagehide', (event) => {
				this.onClose();
			});

			this.pictureWindow[eventName]('resize', (event) => {
				this.onResize();
			});

			this.pictureWindow[eventName]('mousemove', (event) => {
				this.onMouseMove(event);
			});
		}
	}

	async checkAvailableAndCreate()
	{
		if (this.pictureWindow)
		{
			return this.pictureWindow;
		}

		if (window.documentPictureInPicture?.requestWindow)
		{
			this.render();

			try
			{
				this.pictureWindow = await window.documentPictureInPicture.requestWindow({
					disallowReturnToOpener: true,
					width: 370,
					height: 215,
					preferInitialWindowPlacement: false,
				});

				this.toggleEvents(true);

				[...document.styleSheets].forEach((styleSheet) => {
					try {
						const cssRules = [...styleSheet.cssRules].map((rule) => rule.cssText).join('');
						const style = document.createElement('style');

						style.textContent = cssRules;
						this.pictureWindow.document.head.appendChild(style);
					} catch (e) {
						const link = document.createElement('link');

						link.rel = 'stylesheet';
						link.type = styleSheet.type;
						link.media = styleSheet.media;
						link.href = styleSheet.href;
						this.pictureWindow.document.head.appendChild(link);
					}
				});

				this.pictureWindow.document.body.append(this.template);
			}
			catch (error)
			{
				if (error.name === "NotAllowedError")
				{
					console.warn(
						'It seems you closed the Picture-in-Picture window while selecting a screen to share. ' +
						'We cannot reopen it without your permission. ' +
						'If you need PiP again, please reopen it manually.'
					);
				}
				this.onClose();
			}

		} else {
			this.onClose();
		}
	}

	onClose()
	{
		if (typeof this.callbacks?.onClose === 'function')
		{
			this.callbacks.onClose();
		}

		if (this.template)
		{
			BX.remove(this.template);
		}

		this.destroyCurrentUser();

		this.close();
		this.pictureWindow = null;

		this.template = null;
		this.toggleEvents(false);
	}

	close()
	{
		if (this.pictureWindow)
		{
			this.pictureWindow.close();
		}
	}

	static get isAvailable(): boolean
	{
		return UnsupportedBrowserFeatures.isPiPAvailable
	}
}