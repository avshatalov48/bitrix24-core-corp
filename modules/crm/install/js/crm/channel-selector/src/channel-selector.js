import {Dom, Tag, Text, Type, Loc, Reflection} from "main.core";
import {Menu, MenuManager} from "main.popup";
import {EventEmitter, BaseEvent} from 'main.core.events';
import {Router} from 'crm.router';
import {Loader} from 'main.loader';
import {Menu as MenuConfigurable} from 'ui.menu-configurable';
import "ui.icons.service";
import "ui.notification";

const CrmActivityEditor = Reflection.namespace('BX.CrmActivityEditor');
const UserOptions = Reflection.namespace('BX.userOptions');
const NotificationCenter = Reflection.namespace('BX.UI.Notification.Center');

const CHANNEL_TYPE_PHONE = 'PHONE';
const CHANNEL_TYPE_EMAIL = 'EMAIL';
const CHANNEL_TYPE_IM = 'IM';

const MAX_VISIBLE_ITEMS = 4;

export type ChannelSelectorParameters = {
	id: ?string,
	title: ?string,
	body: ?string,
	entityTypeId: number,
	entityId: number,
	channels: Array<ChannelData>,
	communications: Object<string, CommunicationData>,
	link: ?string,
	isLinkObtainable: boolean,
	files: Array<number>,
	storageTypeId: ?number,
	activityEditorId: ?string,
	smsUrl: ?string,
	hasVisibleChannels: boolean,
	channelIcons: Array<string>,
	contactCenterUrl: ?string,
};

export type ChannelData = {
	id: string,
	title: string,
	type: string,
	isAvailable: boolean,
	isHidden: ?boolean,
	icon: ?string,
}

export type CommunicationData = {
	ENTITY_ID: string,
	ELEMENT_ID: number,
	TYPE_ID: string,
	VALUE: string,
};

const items = new Map();

/**
 * @memberof BX.Crm.ChannelSelector
 */
export class List extends EventEmitter
{
	layout: {
		container: HTMLElement,
		list: HTMLElement,
		title: HTMLElement,
		settings: HTMLElement,
		footer: HTMLElement,
		link: ?HTMLElement,
		channels: Object<string,HTMLElement>,
	};
	title: string;
	body: string;
	#link: string;
	isLinkObtainable: boolean;
	entityTypeId: number;
	entityId: number;
	channels: Array<ChannelData>;
	files: Array<number>;
	storageTypeId: number;
	activityEditorId: string;
	smsUrl: string;
	communications: Object<string, CommunicationData>;
	#loader: ?Loader;
	#getLinkPromise: ?Promise;
	#menu: Menu;
	#menuConfigurable: MenuConfigurable;

	constructor(parameters: ChannelSelectorParameters)
	{
		super();
		this.title = Type.isStringFilled(parameters.title) ? parameters.title : Loc.getMessage('CRM_CHANNEL_SELECTOR_DEFAULT_TITLE');
		this.body = String(parameters.body);
		this.#link = String(parameters.link);
		this.isLinkObtainable = Text.toBoolean(parameters.isLinkObtainable);
		this.entityTypeId = Text.toInteger(parameters.entityTypeId);
		this.entityId = Text.toInteger(parameters.entityId);
		this.id = Type.isStringFilled(parameters.id) ? parameters.id : this.entityTypeId + '_' + this.entityId + '_' + Math.random().toString().substring(2)
		this.storageTypeId = Text.toInteger(parameters.storageTypeId);
		this.files = Type.isArray(parameters.files) ? parameters.files : [];
		this.activityEditorId = String(parameters.activityEditorId);
		this.smsUrl = String(parameters.smsUrl);
		this.setChannels(parameters.channels);
		this.communications = Type.isPlainObject(parameters.communications) ? parameters.communications : {};
		this.hasVisibleChannels = Text.toBoolean(parameters.hasVisibleChannels);
		this.channelIcons = Type.isArray(parameters.channelIcons) ? parameters.channelIcons : [];
		this.contactCenterUrl = Type.isStringFilled(parameters.contactCenterUrl) ? parameters.contactCenterUrl : '/contact_center/';
		this.layout = {
			channels: {},
		};
		this.setEventNamespace('BX.Crm.ChannelSelector.List');

		if (items.size === 0)
		{
			items.set('default', this);
		}
		items.set(this.id, this);
	}

	setChannels(channels: Array<ChannelData>)
	{
		this.channels = [];
		if (Type.isArray(channels))
		{
			channels.forEach((channel: ChannelData) => {
				this.channels.push(channel);
			});
		}

		return this;
	}

	render(): HTMLElement
	{
		if (this.layout.container)
		{
			return this.layout.container;
		}
		this.layout.title = Tag.render`<div class="crm__channel-selector--title">${Text.encode(this.title)}</div>`;
		if (this.#link || this.isLinkObtainable)
		{
			this.layout.link = Tag.render`<input type="text" class="crm__channel-selector--footer-link-hidden" value="${Text.encode(this.#link)}" />`
			// class="crm__channel-selector--footer --disabled"
			this.layout.footer = Tag.render`<div class="crm__channel-selector--footer" onclick="${this.#handleFooterClick.bind(this)}">
				<div class="crm__channel-selector--footer-copy-link">
					<span class="crm__channel-selector--footer-text">${Loc.getMessage('CRM_CHANNEL_FOOTER_TITLE')}</span>
					${this.layout.link}
				</div>
			</div>`
		}
		else
		{
			this.layout.footer = null;
		}

		this.layout.settings = null;
		if (this.hasVisibleChannels)
		{
			this.layout.settings = Tag.render`<button class="ui-btn ui-btn-xs ui-btn-link ui-btn-icon-setting crm__channel-selector--setting-btn" onclick="${this.#handleSettingsClick.bind(this)}"></button>`;
			this.layout.list = Tag.render`<div class="crm__channel-selector--list"></div>`;

			this.channels.forEach((channel: ChannelData) =>
			{
				const channelNode = this.#renderChannel(channel);
				if (channelNode)
				{
					this.layout.channels[channel.id] = channelNode;
				}
			});
		}
		else
		{
			this.layout.list = Tag.render`<div class="crm__channel-selector--body">
			<div class="crm__channel-selector--networks">
				<div class="crm__channel-selector--networks-title">${Loc.getMessage('CRM_CHANNEL_SELECTOR_NO_ACTIVE_CHANNELS_TEXT')}</div>
				<div class="crm__channel-selector--networks-block">
					${this.channelIcons.map(icon => Tag.render`<span class="crm__channel-selector--network-link --${icon}" onclick="${this.#openContactCenter.bind(this)}"></span>`)}
					<span class="crm__channel-selector--network-link --link" onclick="${this.#openContactCenter.bind(this)}">+ 15</span>
				</div>
				<span class="ui-btn ui-btn-xs ui-btn-primary ui-btn-no-caps ui-btn-round" onclick="${this.#openContactCenter.bind(this)};">${Loc.getMessage('CRM_CHANNEL_SELECTOR_ACTIVATE_CHANNELS')}</span>
			</div>
		</div>`
		}

		this.layout.container = Tag.render`<div class="crm__channel-selector--container">
			<div class="crm__channel-selector--header">
				${this.layout.title}
				${this.layout.settings}
			</div>
			${this.layout.list}
		</div>`;

		if (this.layout.footer)
		{
			this.layout.container.appendChild(this.layout.footer);
		}

		this.adjustAppearance();

		return this.layout.container;
	}

	#renderChannel(channel: ChannelData): HTMLElement
	{
		const channelHandler = (() => {
			this.#handleChannelClick(channel);
		});

		let icon = this.getChannelIcon(channel);

		return Tag.render`<div 
			class="crm__channel-selector--channel"
			onclick="${channelHandler}"
		>
			${(icon ? Tag.render`<div class="crm__channel-selector--channel-icon ${icon}"></div>` : '')}
			<div class="crm__channel-selector--channel-text">
				${Text.encode(channel.title)}
			</div>
			<div class="crm__channel-selector--channel-helper">
				<span class="crm__channel-selector--channel-helper-text">${Loc.getMessage('CRM_CHANNEL_SELECTOR_SEND_BUTTON')}</span>
			</div>
		</div>`;
	}

	adjustAppearance(): void
	{
		if (this.hasVisibleChannels)
		{
			Dom.clean(this.layout.list);
			let allChannelsAreHidden = true;
			this.channels.forEach((channel: ChannelData) =>
			{
				const node = this.layout.channels[channel.id];
				if (node)
				{
					this.layout.list.append(node);
					if (this.#isChannelAvailable(channel))
					{
						Dom.removeClass(node, 'crm__channel-selector--channel-disabled');
					}
					else
					{
						Dom.addClass(node, 'crm__channel-selector--channel-disabled');
					}
					if (channel.isHidden)
					{
						Dom.addClass(node, 'crm__channel-selector--channel-hidden');
					}
					else
					{
						Dom.removeClass(node, 'crm__channel-selector--channel-hidden');
						allChannelsAreHidden = false;
					}
				}
			});
			if (allChannelsAreHidden)
			{
				Dom.addClass(this.layout.list, '--empty');
			}
			else
			{
				Dom.removeClass(this.layout.list, '--empty');
			}
		}
	}

	#getChannelById(id: string): ?ChannelData
	{
		return this.channels.find(channel => channel.id === id);
	}

	#isChannelAvailable(channel: ChannelData): boolean
	{
		if (!channel.isAvailable)
		{
			return false;
		}
		const hasLink = this.isLinkObtainable || Boolean(this.#link);
		const hasFiles = CrmActivityEditor && this.storageTypeId > 0 && this.files.length > 0;
		if (channel.type === CHANNEL_TYPE_PHONE || channel.type === CHANNEL_TYPE_IM)
		{
			return hasLink;
		}
		if (channel.type === CHANNEL_TYPE_EMAIL)
		{
			return hasLink || hasFiles;
		}
		if (
			channel.type === CHANNEL_TYPE_EMAIL
			&& !CrmActivityEditor?.items[this.activityEditorId]
		)
		{
			console.log('Email channel is disabled because the CrmActivityEditor instance is not found');
			return false;
		}

		return channel.isAvailable;
	}

	#getLink(): Promise<string>
	{
		if (this.#link)
		{
			return Promise.resolve(this.#link);
		}
		if (!this.isLinkObtainable)
		{
			return Promise.reject();
		}
		if (this.#getLinkPromise)
		{
			return this.#getLinkPromise;
		}
		this.#getLinkPromise = new Promise((resolve, reject) => {
			this.#showLoader();
			this.emitAsync('getLink').then((result: Array) => {
				result.forEach((link) => {
					this.setLink(link);
				});
				if (!this.#link)
				{
					reject();
				}
				else
				{
					resolve(this.#link);
				}
			}).catch(reject)
			.finally(() => {
				this.#getLinkPromise = null;
				this.#hideLoader();
			});
		});

		return this.#getLinkPromise;
	}

	#handleFooterClick(): void
	{
		if (!this.layout.link)
		{
			return;
		}
		this.#getLink().then((link) => {
			this.layout.link.value = link;
			this.layout.link.focus();
			this.layout.link.setSelectionRange(0, this.layout.link.value.length);
			document.execCommand("copy");

			this.#showNotice(Loc.getMessage('CRM_CHANNEL_PUBLIC_LINK_COPIED_NOTIFICATION_MESSAGE'));
		}).catch((reason: ?string) => {
			this.#showGetLinkErrorNotification(this.layout.footer, reason);
		});
	}

	#handleSettingsClick(): void
	{
		const event = new BaseEvent();
		this.emit('onSettingsClick', event);
		if (event.isDefaultPrevented())
		{
			return;
		}

		this.#openMenu();
	}

	#openMenu(): void
	{
		this.#getMenu().show();
	}

	#getMenuItemsInViewMode(): Array
	{
		const settingsItems = [];
		this.channels.forEach((channel: ChannelData) => {
			if (channel.isHidden)
			{
				settingsItems.push({
					text: channel.title,
					id: channel.id,
					onclick: () => {
						this.#handleChannelClick(channel);
					},
				});
			}
		});
		settingsItems.push({
			delimiter: true
		});
		settingsItems.push({
			text: Loc.getMessage('CRM_COMMON_ACTION_CONFIG'),
			id: 'configure',
			onclick: this.#switchMenuToEditMode.bind(this),
		});

		return settingsItems;
	}

	#getMenu(): Menu
	{
		if (!this.#menu)
		{
			this.#menu = MenuManager.create({
				id: this.id + '-settings-popup',
				bindElement: this.layout.settings,
				items: this.#getMenuItemsInViewMode(),
			});
		}

		return this.#menu;
	}

	#switchMenuToEditMode(): void
	{
		this.#getMenu().close();
		this.#getMenuConfigurable().open().then((result) => {
			if (result.isCanceled)
			{
				return;
			}
			if (Type.isArray(result.items))
			{
				this.#saveSettings(result.items);
				this.#openMenu();
			}
		});
	}

	#saveSettings(items: Array<{id: string, isHidden: boolean}>): void
	{
		const channels = [];
		items.forEach((item) => {
			const channel = this.#getChannelById(item.id);
			if (channel)
			{
				channel.isHidden = item.isHidden;
				channels.push(channel);
			}
		});
		this.setChannels(channels);
		this.adjustAppearance();
		this.#menu.destroy();
		this.#menu = null;

		UserOptions.save("crm", "channel-selector", "items", JSON.stringify(items));
	}

	#switchMenuToViewMode(): void
	{
		this.#menuConfigurable?.close();
		this.#getMenu().show();
	}

	#getMenuItemsInEditMode(): Array
	{
		const items = [];
		this.channels.forEach((channel: ChannelData) => {
			items.push({
				text: channel.title,
				id: channel.id,
				isHidden: channel.isHidden,
			});
		});

		return items;
	}

	#getMenuConfigurable(): MenuConfigurable
	{
		const items = this.#getMenuItemsInEditMode();
		if (!this.#menuConfigurable)
		{
			this.#menuConfigurable = new MenuConfigurable({
				items,
				bindElement: this.layout.settings,
				maxVisibleItems: MAX_VISIBLE_ITEMS,
			});
			this.#menuConfigurable.subscribe('Cancel', () => {
				this.#openMenu();
			});
		}
		else
		{
			this.#menuConfigurable.setItems(items);
		}

		return this.#menuConfigurable;
	}

	#showGetLinkErrorNotification(bindElement: HTMLElement, text: ?string)
	{
		if (!Type.isStringFilled(text))
		{
			text = Loc.getMessage('CRM_CHANNEL_PUBLIC_LINK_NOT_AVAILABLE_NOTIFICATION_MESSAGE');
		}

		this.#showNotice(text);
	}

	#showNotice(content: string)
	{
		if (NotificationCenter)
		{
			NotificationCenter.notify({
				content,
			});
		}
	};

	#handleChannelClick(channel: ChannelData): void
	{
		const event = new BaseEvent();
		event.setData({channel, communications: this.communications[channel.type]});
		this.emit('onChannelClick', event);
		if (event.isDefaultPrevented())
		{
			return;
		}
		if (!this.#isChannelAvailable(channel))
		{
			return;
		}
		if (channel.type === CHANNEL_TYPE_EMAIL)
		{
			this.sendEmail(channel);
			return;
		}
		if (channel.type === CHANNEL_TYPE_PHONE)
		{
			this.sendSms(channel);
			return;
		}
		if (channel.type === CHANNEL_TYPE_IM)
		{
			this.openMessenger(channel);
		}
	}

	setFiles(files: number[]): this
	{
		this.files = Type.isArray(files) ? files : [];

		this.adjustAppearance();

		return this;
	}

	setLink(link: string): this
	{
		this.#link = link ?? null;

		this.adjustAppearance();

		return this;
	}

	sendEmail(channel: ChannelData): void
	{
		if (this.files.length <= 0 || Number(this.storageTypeId) <= 0)
		{
			const channelNode = this.layout.channels[channel.id];
			this.#getLink().then((link) => {
				CrmActivityEditor?.items[this.activityEditorId]?.addEmail({
					'subject': this.body,
					'body': Loc.getMessage('CRM_CHANNEL_SELECTOR_MESSAGE_WITH_LINK', {
						'#MESSAGE#': this.body,
						'#LINK#': link,
					}),
				});
			}).catch((reason) => {
				this.#showGetLinkErrorNotification(channelNode, reason);
			});
		}
		else
		{
			CrmActivityEditor?.items[this.activityEditorId]?.addEmail({
				'subject': this.body,
				'diskfiles': this.files,
				'storageTypeID': this.storageTypeId,
			});
		}
	}

	sendSms(channel: ChannelData): void
	{
		const channelNode = this.layout.channels[channel.id];
		if (!this.smsUrl)
		{
			this.#showGetLinkErrorNotification(channelNode, 'No sms url');
			return;
		}
		this.#getLink().then((link) => {
			Router.openSlider(this.smsUrl, {
				width: 443,
				requestMethod: 'post',
				requestParams: {
					entityTypeId: this.entityTypeId,
					entityId: this.entityId,
					text: Loc.getMessage('CRM_CHANNEL_SELECTOR_MESSAGE_WITH_LINK', {
						'#MESSAGE#': this.body,
						'#LINK#': link,
					}),
					providerId: channel.id,
					isProviderFixed: 'Y',
				}
			});
		}).catch((reason) => {
			this.#showGetLinkErrorNotification(channelNode, reason);
		});
	}

	openMessenger(channel: ChannelData): void
	{
		if (!top.BXIM)
		{
			return;
		}
		if (!this.communications[channel.type])
		{
			return;
		}
		top.BXIM.openMessenger(this.communications[channel.type].VALUE, {RECENT: "N", MENU: "N"});
	}

	#openContactCenter(): void
	{
		Router.openSlider(this.contactCenterUrl).then(() => {
			location.reload();
		});
	}

	getId(): string
	{
		return this.id;
	}

	static getDefault(): ?List
	{
		return items.get('default');
	}

	static getById(id: string): ?List
	{
		return items.get(id);
	}

	#showLoader(): void
	{
		const loader = this.#getLoader();
		if (loader)
		{
			loader.show(this.layout.container);
		}
	}

	#hideLoader(): void
	{
		const loader = this.#getLoader();
		if (loader)
		{
			loader.hide();
		}
	}

	#getLoader(): ?Loader
	{
		if(!this.#loader && Loader)
		{
			this.#loader = new Loader({size: 100, offset: {left: "35%", top: "-25%"}});
		}

		return this.#loader;
	}

	getChannelIcon(channel: ChannelData): ?string
	{
		let icon = channel.icon;
		if (!icon)
		{
			icon = List.getIconByChannelId(channel.id);
		}

		return icon;
	}

	static getIconByChannelId(id: string): ?string
	{
		if (id === CHANNEL_TYPE_EMAIL)
		{
			return '--service-email';
		}
		if (id === CHANNEL_TYPE_IM)
		{
			return '--service-livechat';
		}
		if (id === 'twilio')
		{
			return '--service-whatsapp';
		}
		if (id === 'ednaru' || id === 'ednaruimhpx')
		{
			return '--service-edna';
		}

		return '--service-sms';
	}
}
