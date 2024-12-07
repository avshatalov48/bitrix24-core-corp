import { Type, Tag, Loc, Dom, Text, ajax, Reflection } from 'main.core';
import { Menu, Popup } from 'main.popup';
import { Messenger } from 'im.public.iframe';
import 'pull.client';

export class ControlButton
{
	constructor(params = {})
	{
		this.container = params.container;

		if (!Type.isDomNode(this.container))
		{
			return;
		}

		this.entityType = params.entityType || '';
		this.entityId = params.entityId || '';

		if (!this.entityType || !this.entityId)
		{
			return;
		}

		this.items = params.items || [];
		this.mainItem = params.mainItem || 'videocall';
		this.entityData = params.entityData || {};
		let analyticsLabelParam = params.analyticsLabel || {};

		if (this.items.length === 0)
		{
			switch (this.entityType)
			{
				case 'task': {
					this.items = ['chat', 'videocall', 'blog_post', 'calendar_event'];

					break;
				}

				case 'calendar_event': {
					this.items = ['chat', 'videocall', 'blog_post', 'task'];

					break;
				}

				case 'workgroup': {
					this.items = ['chat', 'videocall'];

					break;
				}

				default: {
					this.items = ['chat', 'videocall', 'blog_post', 'task', 'calendar_event'];
				}
			}
		}

		this.contextBx = (window.top.BX || window.BX);
		this.sliderId = `controlButton:${this.entityType + this.entityId}${Math.floor(Math.random() * 1000)}`;
		this.isVideoCallEnabled = Reflection.getClass(`${this.contextBx}.Call.Util`)
			? this.contextBx.Call.Util.isWebRTCSupported() : true;
		this.chatLockCounter = 0;

		if (!Type.isPlainObject(analyticsLabelParam))
		{
			analyticsLabelParam = {};
		}

		this.analyticsLabel = {
			entity: this.entityType,
			...analyticsLabelParam,
		};

		this.analytics = params.analytics || {};
		if (!Type.isPlainObject(this.analytics))
		{
			this.analytics = {};
		}

		this.buttonClassName = params.buttonClassName || '';

		this.renderButton();
		this.subscribeEvents();
	}

	destroy()
	{
		this.contextBx.Event.EventEmitter.unsubscribe('BX.Calendar:onEntrySave', this.onCalendarSave);
		this.contextBx.Event.EventEmitter.unsubscribe('SidePanel.Slider:onMessage', this.onPostSave);
	}

	subscribeEvents()
	{
		this.contextBx.Event.EventEmitter.subscribe('BX.Calendar:onEntrySave', this.onCalendarSave.bind(this));
		this.contextBx.Event.EventEmitter.subscribe('SidePanel.Slider:onMessage', this.onPostSave.bind(this));
	}

	onCalendarSave(event)
	{
		if (event instanceof this.contextBx.Event.BaseEvent)
		{
			const data = event.getData();

			if (data.sliderId === this.sliderId)
			{
				const params = {
					postEntityType: this.entityType.toUpperCase(),
					sourceEntityType: this.entityType.toUpperCase(),
					sourceEntityId: this.entityId,
					sourceEntityData: this.entityData,
					entityType: 'CALENDAR_EVENT',
					entityId: data.responseData.entryId,
				};

				this.addEntityComment(params);
			}
		}
	}

	onPostSave(event)
	{
		const [sliderEvent] = event.getCompatData();

		if (sliderEvent.getEventId() === 'Socialnetwork.PostForm:onAdd')
		{
			const data = sliderEvent.getData();
			if (data.originatorSliderId === this.sliderId)
			{
				const params = {
					postEntityType: this.entityType.toUpperCase(),
					sourceEntityType: this.entityType.toUpperCase(),
					sourceEntityId: this.entityId,
					sourceEntityData: this.entityData,
					entityType: 'BLOG_POST',
					entityId: data.successPostId,
				};

				this.addEntityComment(params);
			}
		}
	}

	renderButton()
	{
		const isChatButton = (!this.isVideoCallEnabled || this.mainItem === 'chat');
		const onClickValue = (isChatButton ? this.openChat.bind(this) : this.startVideoCall.bind(this));
		const buttonTitle = (isChatButton ? Loc.getMessage('INTRANET_JS_CONTROL_BUTTON_CHAT') : Loc.getMessage('INTRANET_JS_CONTROL_BUTTON_NAME_MSG_1'));
		const buttonClass = `${isChatButton ? 'ui-btn-icon-chat-blue' : 'ui-btn-icon-camera-blue'} intranet-control-btn ui-btn-light-border ui-btn-icon-inline ${this.buttonClassName}`;

		this.button = (
			this.items.length > 1
				? Tag.render`
					<div class="ui-btn-split ${buttonClass}">
						<button class="ui-btn-main" onclick="${onClickValue}">${buttonTitle}</button>
						<button class="ui-btn-menu" onclick="${this.showMenu.bind(this)}"></button> 
					</div>
				`
				: Tag.render`<button class="ui-btn ${buttonClass}" onclick="${onClickValue}">${buttonTitle}</button>`
		);

		Dom.append(this.button, this.container);
	}

	showLoader()
	{
		Dom.addClass(this.button, 'ui-btn-wait');
	}

	hideLoader()
	{
		Dom.removeClass(this.button, 'ui-btn-wait');
	}

	getAvailableItems()
	{
		return new Promise((resolve, reject) => {
			const availableItems = window.sessionStorage.getItem('b24-controlbutton-available-items');
			if (availableItems)
			{
				resolve(availableItems);

				return;
			}

			this.showLoader();

			ajax.runAction('intranet.controlbutton.getAvailableItems', {
				data: {},
			}).then((response) => {
				window.sessionStorage.setItem('b24-controlbutton-available-items', response.data);
				this.hideLoader();
				resolve(response.data);
			});
		});
	}

	showMenu()
	{
		this.getAvailableItems().then((availableItems) => {
			this.items = this.items.filter((item) => {
				return (item && (availableItems.includes(item)));
			});

			const menuItems = [];

			this.items.forEach((item) => {
				// eslint-disable-next-line default-case
				switch (item)
				{
					case 'videocall':
						if (this.isVideoCallEnabled)
						{
							menuItems.push({
								text: Loc.getMessage('INTRANET_JS_CONTROL_BUTTON_VIDEOCALL'),
								className: 'menu-popup-item-videocall',
								onclick: () => {
									this.startVideoCall();
									this.popupMenu.close();
								},
							});
						}
						break;

					case 'chat':
						menuItems.push({
							text: Loc.getMessage('INTRANET_JS_CONTROL_BUTTON_CHAT'),
							className: 'menu-popup-item-chat',
							onclick: () => {
								this.openChat();
								this.popupMenu.close();
							},
						});
						break;

					case 'task':
						menuItems.push({
							text: Loc.getMessage('INTRANET_JS_CONTROL_BUTTON_TASK'),
							className: 'menu-popup-item-task',
							onclick: () => {
								this.openTaskSlider();
								this.popupMenu.close();
							},
						});
						break;

					case 'calendar_event':
						menuItems.push({
							text: Loc.getMessage('INTRANET_JS_CONTROL_BUTTON_MEETING'),
							className: 'menu-popup-item-meeting',
							onclick: () => {
								this.openCalendarSlider();
								this.popupMenu.close();
							},
						});
						break;

					case 'blog_post':
						menuItems.push({
							text: Loc.getMessage('INTRANET_JS_CONTROL_BUTTON_POST'),
							className: 'menu-popup-item-post',
							onclick: () => {
								this.openPostSlider();
								this.popupMenu.close();
							},
						});
						break;
				}
			});

			this.popupMenu = new Menu({
				bindElement: this.button,
				items: menuItems,
				offsetLeft: 80,
				offsetTop: 5,
			});

			this.popupMenu.show();
		});
	}

	openChat()
	{
		this.showLoader();

		const analytics = this.analytics.openChat || {};

		ajax.runAction('intranet.controlbutton.getChat', this.getAjaxConfig(analytics)).then((response) => {
			if (response.data)
			{
				Messenger.openChat(`chat${parseInt(response.data, 10)}`);
			}

			this.chatLockCounter = 0;
			this.hideLoader();
		}, (response) => {
			if (response.errors[0].code === 'lock_error' && this.chatLockCounter < 4)
			{
				this.chatLockCounter++;
				this.openChat();
			}
			else
			{
				this.showHintPopup(response.errors[0].message);
				this.hideLoader();
			}
		});
	}

	startVideoCall(videoCallContext = null)
	{
		this.showLoader();

		const analytics = this.analytics.startVideoCall || {};
		if (
			Type.isPlainObject(analytics)
			&& Type.isStringFilled(analytics.c_sub_section)
			&& videoCallContext
		)
		{
			analytics.c_sub_section = videoCallContext;
		}

		ajax.runAction('intranet.controlbutton.getVideoCallChat', this.getAjaxConfig(analytics)).then((response) => {
			if (response.data)
			{
				Messenger.startVideoCall(`chat${response.data}`, true);
			}

			this.chatLockCounter = 0;
			this.hideLoader();
		}, (response) => {
			if (response.errors[0].code === 'lock_error' && this.chatLockCounter < 4)
			{
				this.chatLockCounter++;
				this.startVideoCall();
			}
			else
			{
				this.showHintPopup(response.errors[0].message);
				this.hideLoader();
			}
		});
	}

	addEntityComment(params)
	{
		ajax.runAction('socialnetwork.api.livefeed.createEntityComment', {
			data: {
				params,
			},
		});
	}

	openCalendarSlider()
	{
		this.showLoader();

		const analytics = this.analytics.openCalendarSlider || {};

		ajax.runAction('intranet.controlbutton.getCalendarLink', this.getAjaxConfig(analytics)).then((response) => {
			let users = [];

			if (Type.isArrayLike(response.data.userIds))
			{
				users = response.data.userIds.map((userId) => {
					return { id: parseInt(userId, 10), entityId: 'user' };
				});
			}

			new (window.top.BX || window.BX).Calendar.SliderLoader(
				0,
				{
					sliderId: this.sliderId,
					participantsEntityList: users,
					entryName: response.data.name,
					entryDescription: response.data.desc,
				},
			).show();

			this.hideLoader();
		});
	}

	openTaskSlider()
	{
		this.showLoader();

		const analytics = this.analytics.openTaskSlider || {};

		ajax.runAction('intranet.controlbutton.getTaskLink', this.getAjaxConfig(analytics)).then((response) => {
			BX.SidePanel.Instance.open(response.data.link, {
				requestMethod: 'post',
				requestParams: response.data,
			});
			this.hideLoader();
		});
	}

	openPostSlider()
	{
		this.showLoader();

		const analytics = this.analytics.openPostSlider || {};

		ajax.runAction('intranet.controlbutton.getPostLink', this.getAjaxConfig(analytics)).then((response) => {
			BX.SidePanel.Instance.open(
				response.data.link,
				{
					requestMethod: 'post',
					requestParams: {
						POST_TITLE: response.data.title,
						POST_MESSAGE: response.data.message,
						destTo: response.data.destTo,
					},
					data: {
						sliderId: this.sliderId,
					},
				},
			);
			this.hideLoader();
		});
	}

	getAjaxConfig(analytics): Object
	{
		const config = {
			data: {
				entityType: this.entityType,
				entityId: this.entityId,
				entityData: this.entityData,
			},
		};

		if (
			Type.isPlainObject(analytics)
			&& Type.isStringFilled(analytics.event)
			&& Type.isStringFilled(analytics.tool)
		)
		{
			config.analytics = analytics;
		}
		else
		{
			config.analyticsLabel = this.analyticsLabel;
		}

		return config;
	}

	showHintPopup(message)
	{
		if (!message)
		{
			return;
		}

		new Popup(`inviteHint${Text.getRandom(8)}`, this.button, {
			content: message,
			zIndex: 15000,
			angle: true,
			offsetTop: 0,
			offsetLeft: 50,
			closeIcon: false,
			autoHide: true,
			darkMode: true,
			overlay: false,
			maxWidth: 400,
			events: {
				onAfterPopupShow() {
					setTimeout(() => {
						this.close();
					}, 5000);
				},
			},
		}).show();
	}
}
