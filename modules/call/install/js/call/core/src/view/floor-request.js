import {Dom} from 'main.core';
import {EventEmitter} from 'main.core.events';
import { Utils } from 'im.v2.lib.utils';
import { Util } from 'call.core';

export class FloorRequest extends EventEmitter
{
	constructor(config)
	{
		super();
		this.setEventNamespace("BX.Call.FloorRequest");

		this.hideTime = BX.prop.getInteger(config, "hideTime", 10);
		//this.isShowAllowPermissionButton = BX.prop.getBoolean(config, 'isShowAllowPermissionButton', false);
		this.userModel = config.userModel;
		
		this.isShowAllowPermissionButton = this._canChangeSpeakPermission();

		this.elements = {
			root: null,
			avatar: null,
		};

		this.callbacks = {
			onAllowSpeakPermissionClicked:
				BX.type.isFunction(config.onAllowSpeakPermissionClicked)
					? config.onAllowSpeakPermissionClicked
					: BX.DoNothing,
			onDisallowSpeakPermissionClicked:
				BX.type.isFunction(config.onDisallowSpeakPermissionClicked)
					? config.onDisallowSpeakPermissionClicked
					: BX.DoNothing,
			onDestroy:
				BX.type.isFunction(config.onDestroy)
					? config.onDestroy
					: BX.DoNothing,
		};

		this._hideTimeout = null;
		this.container = null;
		this._onUserModelChangedHandler = this._onUserModelChanged.bind(this);
		this.userModel.subscribe("changed", this._onUserModelChangedHandler);
	};

	static create(config)
	{
		return new FloorRequest(config);
	};
	
	_canChangeSpeakPermission()
	{
		return (
			Util.isUserControlFeatureEnabled()
			&& !this.userModel.permissionToSpeak
			&& !Util.getRoomPermissions().AudioEnabled
			&& Util.canControlGiveSpeakPermission()
		);
	}

	mount(container)
	{
		this.container = container;
		this.container.appendChild(this.render());
		this.scheduleDismount();
	};
	
	updatePermissionButtonState()
	{
		this.isShowAllowPermissionButton = this._canChangeSpeakPermission();
		
		if (this.elements)
		{
			BX.remove(this.elements.root);
			this.elements.root = null;
		}
		
		this.container.appendChild(this.render());
	}

	dismount()
	{
		if (this.elements)
		{
			BX.remove(this.elements.root);			
		}

		this.destroy();
	};

	onCloseClicked(event)
	{
		event.stopPropagation();

		if(this.isShowAllowPermissionButton)
		{
			this.callbacks.onDisallowSpeakPermissionClicked(this.userModel);
		}
		this.dismount();
	}

	dismountWithAnimation()
	{
		if (!this.elements.root)
		{
			return;
		}
		this.elements.root.classList.add("closing");

		this.elements.root.addEventListener("animationend", () => this.dismount());
	};

	render()
	{
		if (this.elements.root)
		{
			return this.elements.root;
		}
		
		this.elements.root = Dom.create("div", {
			props: {className: "bx-call-view-floor-request-notification"},
			children: [
				Dom.create("div", {
					props: {className: "bx-call-view-floor-request-notification-icon-container"},
					children: [
						this.elements.avatar = Dom.create("div", {
							props: {className: "bx-call-view-floor-request-notification-avatar"},
							text: '',
						}),
						Dom.create("div", {
							props: {className: "bx-call-view-floor-request-notification-icon bx-messenger-videocall-floor-request-icon"}
						}),
					]
				}),

				this.elements.name = Dom.create("span", {
					props: {className: "bx-call-view-floor-request-notification-text-container"},
					html: BX.message("IM_CALL_WANTS_TO_SAY_" + (this.userModel.gender == "F" ? "F" : "M")).replace("#NAME#", '<span class ="bx-call-view-floor-request-notification-text-name">' + BX.util.htmlspecialchars(this.userModel.name) + '</span>')
				}),

				(this.isShowAllowPermissionButton ? this.createAllowPermissionButton() : null),

				Dom.create("div", {
					props: {className: "bx-call-view-floor-request-notification-close"},
					events: {
						click: this.onCloseClicked.bind(this)
					}
				})
			]
		});

		if (this.userModel.avatar)
		{
			this.elements.avatar.style.setProperty("--avatar", "url('" + this.userModel.avatar + "')");
			this.elements.avatar.innerText = '';
		}
		else
		{
			this.elements.avatar.innerText = Utils.text.getFirstLetters(this.userModel.name).toUpperCase();
		}

		return this.elements.root
	};

	scheduleDismount()
	{
		return;
		this._hideTimeout = setTimeout(this.dismountWithAnimation.bind(this), this.hideTime * 1000);
	};

	createAllowPermissionButton()
	{
		return new BX.UI.Button({
			baseClass: "ui-btn ui-btn-icon-mic",
			text: BX.message("CALL_RAISE_HAND_NOTIFY_ALLOW"),
			size: BX.UI.Button.Size.EXTRA_SMALL,
			color: BX.UI.Button.Color.LIGHT_BORDER,
			noCaps: true,
			round: true,
			events: {
				click: () =>
				{
					this._allowPermissionHandler();
				}
			}
		}).render();
	}

	_allowPermissionHandler()
	{
		this.callbacks.onAllowSpeakPermissionClicked(this.userModel);
		this.dismount();
	};

	_onUserModelChanged(event)
	{
		var eventData = event.data;

		if (eventData.fieldName == "floorRequestState" && !this.userModel.floorRequestState)
		{
			this.dismountWithAnimation();
		}

		if (this.userModel.avatar === '' && this.elements.avatar)
		{
			this.elements.avatar.innerText = Utils.text.getFirstLetters(this.userModel.name).toUpperCase();
		}

		if (this.elements.name)
		{
			this.elements.name.innerHtml = BX.message("IM_CALL_WANTS_TO_SAY_" + (this.userModel.gender == "F" ? "F" : "M")).replace("#NAME#", '<span class ="bx-call-view-floor-request-notification-text-name">' + BX.util.htmlspecialchars(this.userModel.name) + '</span>');
		}
	};

	destroy()
	{
		this.callbacks.onDestroy();

		clearTimeout(this._hideTimeout);
		this._hideTimeout = null;
		this.elements = null;
		if (this.userModel)
		{
			this.userModel.unsubscribe("changed", this._onUserModelChangedHandler);
			this.userModel = null;
		}
		this.emit("onDestroy", {});
	}
}
