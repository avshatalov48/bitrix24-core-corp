import {Dom, Text, Type} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {Popup} from 'main.popup';

import {DesktopApi} from 'im.v2.lib.desktop-api';

import Util from '../util';
import { Utils } from 'im.v2.lib.utils';

const Events = {
	onClose: 'onClose',
	onDestroy: 'onDestroy',
	onButtonClick: 'onButtonClick',
};

const InternalEvents = {
	setHasCamera: "CallNotification::setHasCamera",
	contentReady: "CallNotification::contentReady",
	onButtonClick: "CallNotification::onButtonClick",
}

export type IncomingNotificationParams = {
	callerName: string,
	callerAvatar: string,
	callerType: string,
	callerColor: string,
	video: boolean,
	hasCamera: boolean,
	microphoneState: boolean,
	cameraState: boolean,
	zIndex: number,
	onClose: () => void,
	onDestroy: () => void,
	onButtonClick: () => void,
	isMessengerOpen: boolean,
}

export class IncomingNotification extends EventEmitter
{
	static Events = Events;

	constructor(config: IncomingNotificationParams)
	{
		super();
		this.setEventNamespace('BX.Call.IncomingNotification')

		this.popup = null;
		this.window = null;

		this.callerAvatar = Type.isStringFilled(config.callerAvatar) ? config.callerAvatar : "";
		if (Util.isAvatarBlank(this.callerAvatar))
		{
			this.callerAvatar = "";
		}

		this.callerName = config.callerName;
		this.callerType = config.callerType;
		this.callerColor = config.callerColor;
		this.video = config.video;
		this.hasCamera = config.hasCamera === true;
		this.zIndex = config.zIndex;
		this.isMessengerOpen = config.isMessengerOpen;
		this.contentReady = false;
		this.postponedEvents = [];
		this.microphoneState = config.microphoneState;
		this.cameraState = config.cameraState;

		this.#subscribeEvents(config);
		if (DesktopApi.isDesktop())
		{
			this.onButtonClickHandler = this.#onButtonClick.bind(this);
			this.onContentReadyHandler = this.#onContentReady.bind(this);
			DesktopApi.subscribe(InternalEvents.onButtonClick, this.onButtonClickHandler);
			DesktopApi.subscribe(InternalEvents.contentReady, this.onContentReadyHandler);
		}
	};

	#subscribeEvents(config)
	{
		const eventKeys = Object.keys(Events);
		for (let eventName of eventKeys)
		{
			if (Type.isFunction(config[eventName]))
			{
				this.subscribe(Events[eventName], config[eventName])
			}
		}
	}

	show()
	{
		console.log('incoming notification : SHOW');
		if (DesktopApi.isChatWindow())
		{
			console.log('incoming notification : ISDESKTOP');
			const params = {
				video: this.video,
				hasCamera: this.hasCamera,
				callerAvatar: this.callerAvatar,
				callerName: this.callerName,
				callerType: this.callerType,
				callerColor: this.callerColor,
				microphoneState: this.microphoneState,
				cameraState: this.cameraState,
				isMessengerOpen: this.isMessengerOpen,
			};

			if (this.window)
			{
				this.window.BXDesktopWindow.ExecuteCommand("show");
			}
			else
			{
				const js = `
					window.callNotification = new BX.Call.IncomingNotificationContent(${JSON.stringify(params)});
					window.callNotification.showInDesktop();
				`;
				const htmlContent = DesktopApi.prepareHtml("", js);
				this.window = DesktopApi.createTopmostWindow(htmlContent);
			}
		}
		else
		{
			console.log('incoming notification : ISNOTDESKTOP');
			this.content = new IncomingNotificationContent({
				video: this.video,
				hasCamera: this.hasCamera,
				callerAvatar: this.callerAvatar,
				callerName: this.callerName,
				callerType: this.callerType,
				callerColor: this.callerColor,
				microphoneState: this.microphoneState,
				cameraState: this.cameraState,
				isMessengerOpen: this.isMessengerOpen,
				onClose: () => this.emit(Events.onClose),
				onDestroy: () => this.emit(Events.onDestroy),
				onButtonClick: (e) => this.emit(Events.onButtonClick, Object.assign({}, e.data)),
			});
			this.createPopup(this.content.render());
			this.popup.show();

			window.addEventListener('resize', () => {
				this.onResize();
			});
		}
	};

	onResize()
	{
		if (this.popup)
		{
			this.popup.setMaxHeight(document.body.clientHeight);
		}
	}

	createPopup(content)
	{
		this.popup = new Popup({
			id: "bx-messenger-call-notify",
			bindElement: null,
			targetContainer: document.body,
			content: content,
			closeIcon: false,
			noAllPaddings: true,
			zIndex: this.zIndex,
			disableScroll: true,
			maxHeight: document.body.clientHeight,
			offsetLeft: 0,
			offsetTop: 0,
			closeByEsc: false,
			draggable: {restrict: false},
			borderRadius: '25px',
			overlay: {backgroundColor: 'black', opacity: 30},
			events: {
				onPopupClose: () =>
				{
					window.removeEventListener('resize', () =>
					{
						this.onResize();
					});
					this.emit(Events.onClose);
				},
				onPopupDestroy: () => this.popup = null,
			}
		});
	};

	setHasCamera(hasCamera)
	{
		if (this.window)
		{
			// desktop; send event to the window
			if (this.contentReady)
			{
				DesktopApi.emit(InternalEvents.setHasCamera, [hasCamera]);
			}
			else
			{
				this.postponedEvents.push({
					name: InternalEvents.setHasCamera,
					params: [hasCamera]
				})
			}
		}
		else if (this.content)
		{
			this.content.setHasCamera(hasCamera)
		}
	};

	sendPostponedEvents()
	{
		this.postponedEvents.forEach((event) =>
		{
			DesktopApi.emit(event.name, event.params);
		})
		this.postponedEvents = [];
	}

	close()
	{
		if (this.popup)
		{
			this.popup.close();
		}
		if (this.window)
		{
			this.window.BXDesktopWindow.ExecuteCommand("hide");
		}
		this.emit(Events.onClose);
	};

	destroy()
	{
		if (this.popup)
		{
			this.popup.destroy();
			this.popup = null;
		}
		if (this.window)
		{
			this.window.BXDesktopWindow.ExecuteCommand("close");
			this.window = null;
		}
		if (this.content)
		{
			this.content.destroy();
			this.content = null;
		}

		if (DesktopApi.isDesktop())
		{
			DesktopApi.unsubscribe(InternalEvents.onButtonClick, this.onButtonClickHandler);
			DesktopApi.unsubscribe(InternalEvents.contentReady, this.onContentReadyHandler);
		}
		this.emit(Events.onDestroy);

		this.unsubscribeAll(Events.onButtonClick);
		this.unsubscribeAll(Events.onClick);
		this.unsubscribeAll(Events.onDestroy);
	};

	#onButtonClick(event)
	{
		this.emit(Events.onButtonClick, event);
	}

	#onContentReady()
	{
		this.contentReady = true;
		this.sendPostponedEvents();
	}
}

export class IncomingNotificationContent extends EventEmitter
{
	constructor(config)
	{
		super();
		this.setEventNamespace('BX.Call.IncomingNotificationContent');

		this.video = !!config.video;
		this.hasCamera = !!config.hasCamera;
		this.callerAvatar = config.callerAvatar || '';
		this.callerName = config.callerName || BX.message('IM_M_CALL_VIDEO_HD');
		this.callerType = config.callerType || 'chat';
		this.callerColor = config.callerColor || '';
		this.microphoneState = config.microphoneState;
		this.cameraState = config.cameraState;
		this.isMessengerOpen = config.isMessengerOpen;

		this.elements = {
			root: null,
			avatar: null,
			buttons: {
				answerVideo: null
			}
		};

		this.#subscribeEvents(config)
		if (DesktopApi.isDesktop())
		{
			this.onHasCameraHandler = this.#onHasCamera.bind(this);
			DesktopApi.subscribe(InternalEvents.setHasCamera, this.onHasCameraHandler);
			DesktopApi.emitToMainWindow(InternalEvents.contentReady, []);
		}
	};

	#subscribeEvents(config)
	{
		const eventKeys = Object.keys(Events);
		for (let eventName of eventKeys)
		{
			if (Type.isFunction(config[eventName]))
			{
				this.subscribe(Events[eventName], config[eventName])
			}
		}
	}

	render()
	{
		let callerPrefix;

		if (this.video)
		{
			if (this.callerType === 'private')
			{
				callerPrefix = BX.message("IM_M_VIDEO_CALL_FROM");
			}
			else
			{
				callerPrefix = BX.message("IM_M_VIDEO_CALL_FROM_CHAT");
			}
		}
		else
		{
			if (this.callerType === 'private')
			{
				callerPrefix = BX.message("IM_M_CALL_FROM");
			}
			else
			{
				callerPrefix = BX.message("IM_M_CALL_FROM_CHAT");
			}
		}

		let avatarClass = '';
		let avatarImageStyles;
		let avatarImageText = '';

		if (this.callerAvatar)
		{
			avatarImageStyles = {
				backgroundImage: "url('" + this.callerAvatar + "')",
				backgroundColor: '#fff',
				backgroundSize: 'cover',
			}
		}
		else
		{
			const callerType = this.callerType === 'private' ? 'user' : this.callerType;

			avatarClass = 'bx-messenger-panel-avatar-' + callerType;
			avatarImageStyles = {
				backgroundColor: this.callerColor || '#525252',
				backgroundSize: '40px',
				backgroundPosition: 'center center',
			}
			avatarImageText = Utils.text.getFirstLetters(this.callerName).toUpperCase();
		}

		this.elements.root = Dom.create("div", {
			props: {className: "bx-messenger-call-window" + (DesktopApi.isDesktop() ? ' desktop' : '')},
			children: [
				Dom.create("div", {
					props: {className: "bx-messenger-call-window-body"},
					children: [
						Dom.create("div", {
							props: {className: "bx-messenger-call-window-top"},
							children: [
								Dom.create("div", {
									props: {className: "bx-messenger-call-window-photo bx-messenger-videocall-incoming-call-avatar-pulse"},
									children: [
										Dom.create("div", {
											props: {
												className: "bx-messenger-call-window-photo-left " + avatarClass
											},
											children: [
												this.elements.avatar = Dom.create("div", {
													props: {
														className: "bx-messenger-call-window-photo-block"
													},
													style: avatarImageStyles,
													text: avatarImageText,
												}),
											]
										}),
										Dom.create("div", {
											props: {className: "bx-messenger-videocall-incoming-call-pulse-element", style: "animation-delay: -2s;"}
										}),
										Dom.create("div", {
											props: {className: "bx-messenger-videocall-incoming-call-pulse-element", style: "animation-delay: -1.5s;"}
										}),
										Dom.create("div", {
											props: {className: "bx-messenger-videocall-incoming-call-pulse-element", style: "animation-delay: -1s;"}
										}),
										Dom.create("div", {
											props: {className: "bx-messenger-videocall-incoming-call-pulse-element", style: "animation-delay: -0.5s;"}
										}),
									]
								}),
								Dom.create("div", {
									props: {className: "bx-messenger-call-window-title"},
									children: [
										Dom.create("div", {
											props: {className: "bx-messenger-call-window-title-block"},
											children: [
												Dom.create("div", {
													props: {className: "bx-messenger-call-overlay-title-caller-prefix"},
													text: callerPrefix
												}),
												Dom.create("div", {
													props: {className: "bx-messenger-call-overlay-title-caller"},
													text: Text.decode(this.callerName)
												})
											]
										}),
									]
								}),
							]
						}),
						this.elements.windowBottom = Dom.create("div", {
							props: {className: "bx-messenger-call-window-bottom"},
							children: [
								Dom.create("div", {
									props: {className: "bx-messenger-call-window-buttons"},
									children: [
										this.elements.buttonsBlock = Dom.create("div", {
											props: {className: "bx-messenger-call-window-buttons-block"},
											children: []
										}),
									]
								}),
							]
						}),
					]
				})
			]
		});

		this.elements.windowBottom.prepend(...[
			Dom.create("div", {
				props: {className: "bx-messenger-call-window-settings-block"},
				children: [
					Dom.create("div", {
						props: {className: "bx-messenger-call-window-settings-block-title"},
						text: BX.message("CALL_M_INCOMING_NOTIFICATION_SETTINGS_TITLE")
					}),
					Dom.create("div", {
						props: {className: "bx-messenger-call-window-settings-buttons-block"},
						children: [
							Dom.create("div", {
								props: {className: "bx-messenger-call-window-settings-button"},
								children: [
									this.elements.buttons.toggleMicrophoneIcon = Dom.create("div", {
										props: {className: "bx-messenger-call-window-settings-icon microphone-" + (this.microphoneState ? "on" : "off")}
									}),
									Dom.create("div", {
										props: {className: "bx-messenger-call-window-settings-text microphone"},
										text: BX.message("IM_M_CALL_BTN_MIC")
									}),
								],
								events: {click: this.#onMicrophoneSettingClick.bind(this)},
							}),
							Dom.create("div", {
								props: {className: "bx-messenger-call-window-settings-separator"},
							}),
							Dom.create("div", {
								props: {className: "bx-messenger-call-window-settings-button"},
								children: [
									this.elements.buttons.toggleCameraIcon = Dom.create("div", {
										props: {
											className: "bx-messenger-call-window-settings-icon camera-" + (this.cameraState && this.hasCamera ? "on" : "off") + (this.hasCamera ? "" : " bx-messenger-call-window-button-disabled")
										},
									}),
									Dom.create("div", {
										props: {className: "bx-messenger-call-window-settings-text camera"},
										text: BX.message("IM_M_CALL_BTN_CAMERA"),
									}),
								],
								events: {click: this.#onCameraSettingClick.bind(this)}
							}),
						],
					}),
				],
			}),
		]);

		this.elements.buttonsBlock.append(...[
			Dom.create("div", {
				props: {className: "bx-messenger-call-window-button bx-messenger-call-window-button-danger"},
				children: [
					Dom.create("div", {
						props: {
							className: "bx-messenger-call-window-button-icon bx-messenger-call-window-button-icon-phone-down",
							title: BX.message("IM_M_CALL_BTN_DECLINE"),
						}
					}),
				],
				events: {click: this.#onDeclineButtonClick.bind(this)}
			}),
			this.elements.buttons.answer = Dom.create("div", {
				props: {
					className: "bx-messenger-call-window-button" + (this.withBlur ? ' with-blur' : ''),
					title: BX.message("IM_M_CALL_BTN_ANSWER"),
				},
				children: [
					Dom.create("div", {
						props: {className: "bx-messenger-call-window-button-icon bx-messenger-call-window-button-icon-phone-up"}
					}),
				],
				events: {click: this.#onAnswerButtonClick.bind(this)}
			}),
		]);

		return this.elements.root;
	};

	showInDesktop()
	{
		// Workaround to prevent incoming call window from hanging.
		// Without it, there is a possible scenario, when BXDesktopWindow.ExecuteCommand("close") is executed too early
		// (if invite window is closed before appearing), which leads to hanging of the window
		if (window.opener.BXIM?.callController && !window.opener.BXIM.callController.callNotification)
		{
			BXDesktopWindow.ExecuteCommand("close");
			return;
		}

		const width = 450;
		const height = 575;

		this.render();
		document.body.appendChild(this.elements.root);
		DesktopApi.setWindowPosition({
			x: STP_CENTER,
			y: STP_VCENTER,
			width,
			height
		});
	};

	setHasCamera(hasCamera)
	{
		this.hasCamera = !!hasCamera;
	};

	#onHasCamera()
	{

	}

	#onMicrophoneSettingClick()
	{
		this.microphoneState = !this.microphoneState;

		if (this.microphoneState)
		{
			this.elements.buttons.toggleMicrophoneIcon.classList.add('microphone-on');
			this.elements.buttons.toggleMicrophoneIcon.classList.remove('microphone-off');
		}
		else
		{
			this.elements.buttons.toggleMicrophoneIcon.classList.add('microphone-off');
			this.elements.buttons.toggleMicrophoneIcon.classList.remove('microphone-on');
		}
	};

	#onCameraSettingClick()
	{
		this.cameraState = !this.cameraState;

		if (this.cameraState)
		{
			this.elements.buttons.toggleCameraIcon.classList.add('camera-on');
			this.elements.buttons.toggleCameraIcon.classList.remove('camera-off');
		}
		else
		{
			this.elements.buttons.toggleCameraIcon.classList.add('camera-off');
			this.elements.buttons.toggleCameraIcon.classList.remove('camera-on');
		}
	};

	#onAnswerButtonClick()
	{
		if (!this.hasCamera)
		{
			this.cameraState = false;
			this.microphoneState = true;
		}

		if (this.isMessengerOpen && typeof BX.SidePanel !== 'undefined' && BX.SidePanel.Instance.isOpen())
		{
			BX.SidePanel.Instance.close();
		}

		if (DesktopApi.isDesktop())
		{
			DesktopApi.closeWindow();
			DesktopApi.emitToMainWindow(InternalEvents.onButtonClick, [{
				button: 'answer',
				mediaParams: {
					audio: this.microphoneState,
					video: this.cameraState,
				}
			}]);
		}
		else
		{
			this.emit(Events.onButtonClick, {
				button: 'answer',
				mediaParams: {
					audio: this.microphoneState,
					video: this.cameraState,
				}
			});
		}
	};

	#onAnswerWithVideoButtonClick()
	{
		if (!this.hasCamera)
		{
			return;
		}
		if (DesktopApi.isDesktop())
		{
			DesktopApi.closeWindow();
			DesktopApi.emitToMainWindow(InternalEvents.onButtonClick, [{
				button: 'answer',
				mediaParams: {
					audio: true,
					video: true,
				}
			}]);
		}
		else
		{
			this.emit(Events.onButtonClick, {
				button: 'answer',
				mediaParams: {
					audio: true,
					video: true,
				}
			});
		}
	};

	#onDeclineButtonClick()
	{
		if (DesktopApi.isDesktop())
		{
			DesktopApi.closeWindow();
			DesktopApi.emitToMainWindow(InternalEvents.onButtonClick, [{
				button: 'decline'
			}]);
		}
		else
		{
			this.emit(Events.onButtonClick, {
				button: 'decline'
			});
		}
	};

	destroy()
	{
		if (DesktopApi.isDesktop())
		{
			DesktopApi.unsubscribe(InternalEvents.setHasCamera, this.onHasCameraHandler);
		}
		this.unsubscribeAll(Events.onButtonClick);
		this.unsubscribeAll(Events.onClick);
		this.unsubscribeAll(Events.onDestroy);
	}
}
