import { Dom } from 'main.core';
import { Popup } from 'main.popup';
import { EventEmitter } from 'main.core.events'
import '../css/participants-permission-popup.css';
import { Provider } from '../engine/engine';
import 'ui.switcher';
import Util from '../util';

const ParticipantsPermissionPopupEvents = {
	onTurnOffStreamForAll: 'onTurnOffStreamForAll',
};

export class ParticipantsPermissionPopup
{
	constructor(config)
	{
		this.popup = null;

		this.roomPermissions = config.roomPermissions;

		this.elements = {
			root: null,
			roomPermissions: null,
			roomSettings: null,
			allParticipantsDeviceControl: null,
		};

		this.roomSettingsElements = {
			mic: null,
			cam: null,
			screenshare: null,
		};

		this.callbacks = {
			turnOffAllParticipansStream:
				BX.type.isFunction(config.turnOffAllParticipansStream)
					? config.turnOffAllParticipansStream
					: BX.DoNothing,
			onPermissionChanged:
				BX.type.isFunction(config.onPermissionChanged)
					? config.onPermissionChanged
					: BX.DoNothing,
			onPermissionCollapseStateChanged:
				BX.type.isFunction(config.onPermissionCollapseStateChanged)
					? config.onPermissionCollapseStateChanged
					: BX.DoNothing,
			onClose:
				BX.type.isFunction(config.onClose)
					? config.onClose
					: BX.DoNothing,
			onOpen:
				BX.type.isFunction(config.onOpen)
					? config.onOpen
					: BX.DoNothing,
		}

		this.eventEmitter = new EventEmitter(this, 'ParticipantsPermissionPopup');

		this.currentBitrixCall = Util.getCurrentBitrixCall();

		this.isShowPopup =
			Util.isUserControlFeatureEnabled()
			&& this.currentBitrixCall
			&& this.currentBitrixCall.provider !== Provider.Plain;
			/*&&
				!(this.currentBitrixCall.associatedEntity.type === 'chat'
				&& this.currentBitrixCall.associatedEntity.advanced['chatType'] === 'videoconf');*/

		this.isShowRoomSettings = BX.prop.getObject(config, 'showRoomSettings', false);
		
		if (
			!Util.getRoomPermissions().ScreenShareEnabled
			|| !Util.getRoomPermissions().AudioEnabled
			|| !Util.getRoomPermissions().VideoEnabled)
		{
			this.isShowRoomSettings = true;
		}

		let events = BX.prop.getObject(config, 'events', {});
		for (let eventName in events)
		{
			if (!events.hasOwnProperty(eventName))
			{
				continue;
			}

			this.eventEmitter.subscribe(eventName, events[eventName]);
		}
	}

	renderSettingsElements()
	{
		this.roomSettingsElements.screenshare = ParticipantsPermissionSwitcher.create({
			typeOfSetting: 'screenshare',
			canControlPermission: true,
			settingEnabled: Util.getRoomPermissions().ScreenShareEnabled,
			label: BX.message('CALL_CONTROL_PERMISSIONS_IN_CALL_LABEL_SCREENSHARE'),
			events: {
				onSwitch: this.onPermissionChanged.bind(this)
			}

		});
		this.roomSettingsElements.mic = ParticipantsPermissionSwitcher.create({
			typeOfSetting: 'mic',
			canControlPermission: true,
			settingEnabled: Util.getRoomPermissions().AudioEnabled,
			label: BX.message('CALL_CONTROL_PERMISSIONS_IN_CALL_LABEL_MIC'),
			events: {
				onSwitch: this.onPermissionChanged.bind(this)
			}

		});
		this.roomSettingsElements.cam = ParticipantsPermissionSwitcher.create({
			typeOfSetting: 'cam',
			canControlPermission: true,
			settingEnabled: Util.getRoomPermissions().VideoEnabled,
			label: BX.message('CALL_CONTROL_PERMISSIONS_IN_CALL_LABEL_CAM'),
			events: {
				onSwitch: this.onPermissionChanged.bind(this)
			}

		});

		return [
			this.roomSettingsElements.screenshare.render(),
			this.roomSettingsElements.mic.render(),
			this.roomSettingsElements.cam.render(),
		];
	}

	updateStatePermissions()
	{
		if (this.elements.roomSettings)
		{
			const settingsElements = this.renderSettingsElements();
			this.elements.roomSettings.innerHTML = '';
			this.elements.roomSettings.append(...settingsElements);
		}

	}

	toggleRoomSettings(e)
	{
		this.isShowRoomSettings = !this.isShowRoomSettings;

		if (this.isShowRoomSettings)
		{
			this.elements.roomPermissions.classList.add('permissions-uncollapsed');
		}
		else
		{
			this.elements.roomPermissions.classList.remove('permissions-uncollapsed');
		}
		
		this.callbacks.onPermissionCollapseStateChanged(this.isShowRoomSettings);
	}

	getPopupTemplate()
	{
		return Dom.create('div', {
			props: { className: 'bx-call-participants-permission-popup__wrapper' },
			children: [
				Dom.create('div', {
					props: { className: 'bx-call-participants-permission-popup__content' },
					children: [
						this.elements.roomPermissions = Dom.create('div', {
							props: { className: 'bx-call-participants-permission-popup__permissions-wrapper' },
							children: [
								Dom.create('div', {
								props: { className: 'bx-call-participants-permission-popup__permissions-title-wrapper' },
								events: {
									click: () => {
										this.toggleRoomSettings();
									},
								},
								children: [
									Dom.create('span', {
										props: {className: 'bx-call-participants-permission-popup__permissions-title'},
										text: BX.message('CALL_CONTROL_PERMISSIONS_IN_CALL_TITLE'),

									}),
									Dom.create('div', {
										props: { className: 'bx-call-participants-permission-popup__permissions-title-arrow' },
									}),
								]}),

								this.elements.roomSettings = Dom.create('div', {
									props: { className: 'bx-call-participants-permission-popup__permissions' },
								}),
							],
						}),

						Dom.create('div', {
							props: { className: 'bx-call-participants-permission-popup__control-buttons-label' },
							text: BX.message('CALL_ADMIN_TURN_OFF_FOR_ALL_LABEL'),
						}),

						Dom.create('div', {
							props: { className: 'bx-call-participants-permission-popup__control-buttons' },
							children: [

								this.turnOffMicContainer = ParticipantsStreamControlButton.create({
									typeOfStream: 'mic',
									label: BX.message('CALL_TURN_OFF_MIC_FOR_ALL_PARTICIPANTS_MSGVER_1'),
									events: {
										onTurnOffStreamForAll: this.onTurnOffStreamForAll.bind(this)
									}

								}).render(),

								this.turnOffMicContainer = ParticipantsStreamControlButton.create({
									typeOfStream: 'cam',
									label: BX.message('CALL_TURN_OFF_CAM_FOR_ALL_PARTICIPANTS_MSGVER_1'),
									events: {
										onTurnOffStreamForAll: this.onTurnOffStreamForAll.bind(this)
									}

								}).render(),

								this.turnOffMicContainer = ParticipantsStreamControlButton.create({
									typeOfStream: 'screenshare',
									label: BX.message('CALL_TURN_OFF_SCREENSHARE_FOR_ALL_PARTICIPANTS_MSGVER_1'),
									events: {
										onTurnOffStreamForAll: this.onTurnOffStreamForAll.bind(this)
									}

								}).render(),
							],
						}),
					]
				}),
			]
		})
	}

	onPermissionChanged(e)
	{
		this.callbacks.onPermissionChanged(e.data);
	}

	onTurnOffStreamForAll(e)
	{
		this.callbacks.turnOffAllParticipansStream(e);
		this.close();
	}

	close()
	{
		if (this.popup)
		{
			this.popup.close();
		}
	}

	create()
	{
		const participantsButton = document.querySelector('.bx-messenger-videocall-top-button-text.callcontrol');
		const self = this;
		
		if (!participantsButton)
		{
			return;
		}

		if (!this.isShowPopup)
		{
			return;
		}

		this.elements.root = this.getPopupTemplate();

		const settingsElements = this.renderSettingsElements();
		this.elements.roomSettings.innerHTML = '';
		this.elements.roomSettings.append(...settingsElements);
		
		if (this.isShowRoomSettings)
		{
			this.elements.roomPermissions?.classList.add('permissions-uncollapsed');
		}
		else
		{
			this.elements.roomPermissions?.classList.remove('permissions-uncollapsed');
		}
		
		

		this.popup = new Popup({
			className : 'bx-call-participants-permission-popup',
			bindElement: participantsButton,
			targetContainer: document.body,
			content: this.elements.root,
			offsetTop: 10,
			bindOptions: {
				position: 'bottom'
			},
			autoHide: true,
			closeByEsc: true,
			background: 'linear-gradient(327.21deg, #060825 -10.27%, #173855 103.11%)',
			angle: {position: 'top'},
			contentNoPaddings: true,
			animation: 'fading',
			events: {
				onPopupClose: function ()
				{
					self.callbacks.onClose();
					this.destroy();
				},
				onPopupShow: function ()
				{
					self.callbacks.onOpen();
				},
				onPopupDestroy: function ()
				{
					self.popup = null;
				}
			},
		});
	}

	show()
	{
		this.create();
		this.popup?.show();
	}

	toggle()
	{
		if (!this.popup)
		{
			this.show();
			return;
		}

		this.close();
	}
}


const ParticipantsStreamControlButtonEvents = {
	onTurnOffStreamForAll: 'onTurnOffStreamForAll'
};

class ParticipantsStreamControlButton
{
	constructor(config)
	{
		config = BX.type.isObject(config) ? config : {};

		this.label = config.label || '';
		this.typeOfStream = config.typeOfStream || '';
		this.eventEmitter = new EventEmitter(this, 'ParticipantsStreamControl');

		let events = BX.prop.getObject(config, 'events', {});
		for (let eventName in events)
		{
			if (!events.hasOwnProperty(eventName))
			{
				continue;
			}

			this.eventEmitter.subscribe(eventName, events[eventName]);
		}
	}

	static create(config)
	{
		return new ParticipantsStreamControlButton(config);
	}

	render()
	{
		return Dom.create('div', {
			props: {className: 'bx-call-view-participants-control-stream-button'},
			children: [
				Dom.create('div', {
					props: {className: 'bx-call-view-participants-control-stream-button-icon bx-call-view-participants-control-stream-button-icon-' + this.typeOfStream},

				}),
				Dom.create('div', {
					props: {className: 'bx-call-view-participants-control-stream-button-action'},
					text: this.label
				})
			],
			events: {
				click: (e) =>
				{
					this.eventEmitter.emit(ParticipantsStreamControlButtonEvents.onTurnOffStreamForAll, {
						typeOfStream: this.typeOfStream
					})

				}
			},
		});
	}
}

const ParticipantsPermissionSwitcherEvents = {
	onSwitch: 'onSwitch',
};

class ParticipantsPermissionSwitcher
{
	constructor(config)
	{
		config = BX.type.isObject(config) ? config : {};

		this.label = config.label || '';
		this.typeOfSetting = config.typeOfSetting || '';
		this.canControlPermission = config.canControlPermission || false;
		this.settingEnabled = config.settingEnabled || false;
		this.eventEmitter = new EventEmitter(this, 'ParticipantsPermissionSwitcher');

		let events = BX.prop.getObject(config, 'events', {});
		for (let eventName in events)
		{
			if (!events.hasOwnProperty(eventName))
			{
				continue;
			}

			this.eventEmitter.subscribe(eventName, events[eventName]);
		}
	}

	static create(config)
	{
		return new ParticipantsPermissionSwitcher(config);
	}

	onSwitchToggled(e)
	{
		if (!this.canControlPermission)
		{
			return;
		}

		this.settingEnabled = !this.settingEnabled;

		this.eventEmitter.emit(ParticipantsPermissionSwitcherEvents.onSwitch, {
			settingEnabled: this.settingEnabled,
			typeOfSetting: this.typeOfSetting,
		})
	}

	render()
	{
		return Dom.create('div', {
			props: {
				className: 'bx-call-view-participants-permissions-switch-wrapper',
			},
			dataset: {
				id: 'call-participants-permissions-switch-' + this.typeOfSetting,
			},
			children: [
				Dom.create('span', {
					props: {className: 'bx-call-view-participants-permissions-label'},
					text: this.label

				}),
				Dom.create('div', {
					props: {className: 'bx-call-view-participants-permissions-switch'},
					children: [
						(new BX.UI.Switcher({
							size: 'small',
							checked: this.settingEnabled,
							handlers: {
								toggled: this.onSwitchToggled.bind(this)
							},
							disabled: !this.canControlPermission,
						})).getNode()
					]
				}),
			]
		});
	}
}