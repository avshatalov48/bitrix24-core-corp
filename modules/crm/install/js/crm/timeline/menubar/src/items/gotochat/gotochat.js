import { ClientSelector, Communication, CommunicationItem } from 'crm.client-selector';
import { ConditionChecker, Types as SenderTypes } from 'crm.messagesender';
import { ajax, Dom, Event, Loc, Tag, Text, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Loader } from 'main.loader';
import { Menu, MenuItem, MenuItemOptions, MenuManager } from 'main.popup';
import { Dialog } from 'ui.entity-selector';
import 'ui.icon-set.actions';
import { Icon } from 'ui.icon-set.api.core';
import 'ui.icon-set.main';
import 'ui.icon-set.social';
import Context from '../../context';
import Item from '../../item';
import './gotochat.css';
import ServicesConfig from './services-config';
import { Channel, ChatService, Config, Entity, OpenLinesList } from './types';

const MENU_ITEM_STUB_ID = 'stub';
const ACTIVE_MENU_ITEM_CLASS = 'menu-popup-item-accept';
const DEFAULT_MENU_ITEM_CLASS = 'menu-popup-item-none';
const TOOLBAR_CONTAINER_CLASS = 'crm-entity-stream-content-gotochat-toolbar-container';
const BUTTONS_CONTAINER_CLASS = 'crm-entity-stream-content-gotochat-buttons-container';
const CLIENTS_SELECTOR_TITLE_CLASS = 'crm-entity-stream-content-gotochat-clients-selector-title';

const HELP_ARTICLE_CODE = '18114500';

/** @memberof BX.Crm.Timeline.MenuBar */
export default class GoToChat extends Item
{
	#context: Context = null;

	selectedClient: Entity = null;
	settingsMenu: Menu = null;
	channels: Channel[] = [];
	communications: Communication[] = [];
	currentChannelId: ?string = null;
	fromPhoneId: ?string = null;
	toName: ?string = null;
	toPhoneId: ?string = null;
	openLineItems: OpenLinesList = null;
	hasClients: boolean = false;

	isFetchedConfig: boolean = false;
	isSending: boolean = false;

	#chatServiceButtons: Map<string, HTMLElement> = new Map();
	#region: ?string = null;
	#entityEditor: ?BX.Crm.EntityEditor = null;
	marketplaceUrl: string = '';
	#userSelectorDialog: ?BX.UI.EntitySelector.Dialog = null;
	#clientSelector: ?ClientSelector = null;
	#services: {[key: string]: string} = {};

	initialize(context: Context, settings: ?Object): void
	{
		super.initialize(context, settings);

		this.#context = context;

		this.onSelectClient = this.onSelectClient.bind(this);
		this.onSelectClientPhone = this.onSelectClientPhone.bind(this);
		this.onSelectSender = this.onSelectSender.bind(this);
		this.onSelectSenderPhone = this.onSelectSenderPhone.bind(this);
	}

	initializeLayout()
	{
		super.initializeLayout();
		this.#subscribeToReceiversChanges();
	}

	#subscribeToReceiversChanges(): void
	{
		EventEmitter.subscribe('BX.Crm.MessageSender.ReceiverRepository:OnReceiversChanged', (event: BaseEvent) => {
			const { item } = event.getData();

			if (this.getEntityTypeId() !== item?.entityTypeId || this.getEntityId() !== item?.entityId)
			{
				return;
			}

			this.#hideContent();
			this.#removeCurrentClient();

			this.#fetchConfig(true);
		});
	}

	initializeSettings(): void
	{
		this.#region = this.getSetting('region');
	}

	activate()
	{
		super.activate();

		this.#fetchConfig();
	}

	#fetchConfig(force: boolean = false): void
	{
		if (this.isFetchedConfig && !force)
		{
			return;
		}

		this.isFetchedConfig = false;

		if (!this.#context.getEntityId())
		{
			return;
		}

		const ajaxParameters = {
			entityTypeId: this.#context.getEntityTypeId(),
			entityId: this.#context.getEntityId(),
		};

		ajax.runAction('crm.activity.gotochat.getConfig', { data: ajaxParameters })
			.then(({ data }) => {
				this.isFetchedConfig = true;
				this.#prepareParams(data);
				this.#hideLoader();
				this.adjustLayout();
			})
			.catch(() => this.#showNotify(Loc.getMessage('CRM_TIMELINE_GOTOCHAT_CONFIG_ERROR')))
		;
	}

	#prepareParams(data: Config): void
	{
		const {
			currentChannelId,
			channels,
			communications,
			openLineItems,
			marketplaceUrl,
			services,
			hasClients,
		} = data;

		this.currentChannelId = currentChannelId;
		this.channels = channels;

		this.communications = communications;
		this.hasClients = hasClients;
		this.openLineItems = openLineItems;
		this.marketplaceUrl = marketplaceUrl;
		this.#services = services;

		this.#setCommunicationsParams();
		this.#setChannelDefaultPhoneId();
	}

	#setCommunicationsParams(): void
	{
		if (this.communications.length === 0)
		{
			this.toPhoneId = null;
			this.selectedClient = null;
			this.toName = null;

			return;
		}

		const communication = this.communications[0];

		if (Array.isArray(communication.phones) && communication.phones.length > 0)
		{
			this.toPhoneId = communication.phones[0].id;
		}

		this.selectedClient = {
			entityId: communication.entityId,
			entityTypeId: communication.entityTypeId,
		};

		this.toName = communication.caption;
	}

	#setChannelDefaultPhoneId(): void
	{
		const channel = this.#getCurrentChannel();

		if (
			!channel
			|| !Array.isArray(channel.fromList)
			|| channel.fromList.length === 0
		)
		{
			return;
		}

		const { fromList } = channel;
		const defaultPhone = fromList.find((item) => item.default);

		this.fromPhoneId = defaultPhone ? defaultPhone.id : fromList[0].id;
	}

	#getCurrentChannel(): ?Channel
	{
		const channel = this.channels.find((item) => item.id === this.currentChannelId);

		return (channel ?? null);
	}

	createLayout(): HTMLElement
	{
		return Tag.render`<div class="crm-entity-stream-content-new-detail crm-entity-stream-content-new-detail-gotochat --hidden --skeleton">
			<div class="crm-entity-stream-content-new-detail-gotochat-container hidden">
				<div class="crm-entity-stream-content-gotochat-settings-container">
					<div class="crm-entity-stream-content-gotochat-clients-selector-container">
						<div class="${CLIENTS_SELECTOR_TITLE_CLASS}">
							${this.#getClientTitleHtmlElement()}
						</div>
						<div class="crm-entity-stream-content-gotochat-clients-selector-description">
							${Loc.getMessage('CRM_TIMELINE_GOTOCHAT_CLIENT_SELECTOR_DESCRIPTION')}
						</div>
					</div>
					<div class="${TOOLBAR_CONTAINER_CLASS}">
						<button 
							class="ui-btn ui-btn-link ui-btn-xs ui-btn-icon-help"
							onclick="${this.#showHelp}"
						></button>
						<button
							class="ui-btn ui-btn-link ui-btn-xs ui-btn-icon-setting"
							onclick="${this.#showSettingsMenu.bind(this)}"
						></button>
					</div>
				</div>
				${this.#getServiceButtons()}
			</div>
		</div>`;
	}

	#getClientTitleHtmlElement(): HTMLElement
	{
		const clientStart = '<span id="crm-gotochat-client-selector" class="crm-entity-stream-content-gotochat-user-selector-link">';
		const clientFinish = '</span>';
		const titleContainer = Tag.render`
			<span>
				${Loc.getMessage('CRM_TIMELINE_GOTOCHAT_CLIENT_SELECTOR_TITLE', {
					'[client]': clientStart,
					'[/client]': clientFinish,
				})}
			</span>
		`;

		Event.bind(titleContainer.childNodes[0], 'click', this.onToggleClientSelector.bind(this));

		return titleContainer;
	}

	onToggleClientSelector()
	{
		const id = 'client-selector-dialog';
		const { entityTypeId } = this.#getOwnerEntity();
		const context = `CRM_TIMELINE_GOTOCHAT-${entityTypeId}`;

		if (!this.#userSelectorDialog)
		{
			this.#userSelectorDialog = new Dialog({
				id,
				context,
				targetNode: this.#getUserSelectorDialogTargetNode(),
				multiple: false,
				dropdownMode: false,
				showAvatars: true,
				enableSearch: true,
				width: 450,
				zIndex: 2500,
				entities: this.#getClientSelectorEntities(),
				events: {
					'Item:onSelect': this.onSelectClient,
				},
			});
		}

		if (this.#userSelectorDialog.isOpen())
		{
			this.#userSelectorDialog.hide();
		}
		else
		{
			this.#userSelectorDialog.setTargetNode(this.#getUserSelectorDialogTargetNode());
			this.#userSelectorDialog.show();
		}
	}

	#getUserSelectorDialogTargetNode(): HTMLElement
	{
		return document.getElementById('crm-gotochat-client-selector');
	}

	#getClientSelectorEntities(): Array
	{
		const contact = {
			id: 'contact',
			dynamicLoad: true,
			dynamicSearch: true,
			options: {
				showTab: true,
				showPhones: true,
				showMails: true,
			},
		};

		const company = {
			id: 'company',
			dynamicLoad: true,
			dynamicSearch: true,
			options: {
				excludeMyCompany: true,
				showTab: true,
				showPhones: true,
				showMails: true,
			},
		};

		const { entityTypeId } = this.#getOwnerEntity();
		if (entityTypeId === BX.CrmEntityType.enumeration.contact)
		{
			return [company];
		}

		if (entityTypeId === BX.CrmEntityType.enumeration.company)
		{
			return [contact];
		}

		return [contact, company];
	}

	async onSelectClient(event: BaseEvent)
	{
		const { item } = event.getData();

		this.selectedClient = {
			entityId: item.id,
			entityTypeId: BX.CrmEntityType.resolveId(item.entityId),
		};

		const isBound = await this.#bindClient();
		if (isBound)
		{
			this.adjustLayout();
			BX.Crm.EntityEditor.getDefault().reload();
		}
	}

	async #bindClient(): Promise
	{
		const { entityId, entityTypeId } = this.#getOwnerEntity();
		const { entityId: clientId, entityTypeId: clientTypeId } = this.selectedClient;

		const ajaxParams = {
			entityId,
			entityTypeId,
			clientId,
			clientTypeId,
		};

		return new Promise((resolve) => {
			ajax.runAction('crm.activity.gotochat.bindClient', { data: ajaxParams })
				.then(({ data }) => {
					if (!data)
					{
						resolve(false);
					}

					const { channels, communications, currentChannelId } = data;
					this.channels = channels;
					this.communications = communications;
					this.currentChannelId = currentChannelId;

					this.#setCommunicationsParams();
					this.#setChannelDefaultPhoneId();

					resolve(true);
				})
				.catch((data) => {
					if (data.errors.length > 0)
					{
						this.#showNotify(data.errors[0].message);

						return;
					}

					this.#showNotify(Loc.getMessage('CRM_TIMELINE_GOTOCHAT_BIND_CLIENT_ERROR'));
				})
			;
		});
	}

	#getOwnerEntity(): Entity
	{
		const context = this.#context;

		return {
			entityId: context.getEntityId(),
			entityTypeId: context.getEntityTypeId(),
		};
	}

	// eslint-disable-next-line class-methods-use-this
	#showHelp(): void
	{
		top.BX.Helper.show(`redirect=detail&code=${HELP_ARTICLE_CODE}`);
	}

	#showSettingsMenu(): void
	{
		if (!this.selectedClient)
		{
			this.#showNotSelectedClientNotify();

			return;
		}

		if (!this.settingsMenu)
		{
			this.initSettingsMenu();
		}

		this.settingsMenu.show();
	}

	initSettingsMenu(): void
	{
		const menuId = 'crm-gotochat-channels-settings-menu';
		const items = this.#getSubmenuStubItems();

		this.settingsMenu = MenuManager.create({
			id: menuId,
			bindElement: document.querySelector(`.${TOOLBAR_CONTAINER_CLASS}`),
			items: [
				{
					delimiter: true,
					text: Loc.getMessage('CRM_TIMELINE_GOTOCHAT_SETTINGS'),
				},
				{
					id: 'channelSubmenu',
					text: Loc.getMessage('CRM_TIMELINE_GOTOCHAT_SENDER_SELECTOR'),
					items,
					events: {
						onSubMenuShow: (event: BaseEvent) => {
							this.#onSubMenuShow(event, this.getChannelsSubmenuItems());
						},
					},
				},
				{
					id: 'phoneSubmenu',
					text: Loc.getMessage('CRM_TIMELINE_GOTOCHAT_NUMBER_SELECTOR'),
					items,
					disabled: !Type.isArrayFilled(this.getPhoneSubMenuItems()),
					events: {
						onSubMenuShow: (event: BaseEvent) => {
							this.#onSubMenuShow(event, this.getPhoneSubMenuItems());
						},
					},
				},
			],
		});
	}

	// eslint-disable-next-line class-methods-use-this
	#getSubmenuStubItems(): MenuItemOptions[]
	{
		// needed for emitted the onSubMenuShow event
		return [
			{
				id: MENU_ITEM_STUB_ID,
			},
		];
	}

	// eslint-disable-next-line class-methods-use-this
	#onSubMenuShow(event: BaseEvent, items: MenuItemOptions[]): void
	{
		const target: MenuItem = event.getTarget();

		for (const itemOptionsToAdd of items)
		{
			target.getSubMenu()?.addMenuItem(itemOptionsToAdd);
		}

		target.getSubMenu()?.removeMenuItem(MENU_ITEM_STUB_ID);
	}

	onShowClientPhoneSelector()
	{
		const targetNode = document.getElementById('crm-gotochat-client-selector--selected');

		if (this.#clientSelector && this.#clientSelector.isOpen())
		{
			this.#clientSelector.hide();
		}
		else
		{
			this.#clientSelector = ClientSelector.createFromCommunications({
				targetNode,
				communications: this.communications,
				events: {
					onSelect: this.onSelectClientPhone,
				},
			});

			this.#clientSelector
				.setSelected([this.toPhoneId])
				.show()
			;
		}
	}

	onSelectClientPhone(event: BaseEvent): void
	{
		const { item: { id, customData } } = event.getData();

		this.selectedClient = {
			entityId: customData.get('entityId'),
			entityTypeId: customData.get('entityTypeId'),
		};

		this.toName = this.getCurrentCommunication().caption;
		this.toPhoneId = id;

		this.adjustLayout();
	}

	getCurrentPhone(): ?CommunicationItem
	{
		const client = this.getCurrentCommunication();

		if (!client || !Type.isObjectLike(client.phones))
		{
			return null;
		}

		return client.phones.find((phone) => phone.id === this.toPhoneId);
	}

	getCurrentCommunication(): ?Communication
	{
		if (!this.selectedClient)
		{
			return null;
		}

		const { entityTypeId, entityId } = this.selectedClient;

		return this.communications.find(
			(communication) => {
				return (
					Number(communication.entityTypeId) === Number(entityTypeId)
					&& Number(communication.entityId) === Number(entityId)
				);
			},
		);
	}

	adjustLayout(): void
	{
		this.#adjustClientTitle();
		this.#adjustChatServiceButtons();
		this.#showContent();
	}

	#adjustClientTitle(): void
	{
		const client = this.getCurrentCommunication();
		if (!client)
		{
			this.#showContent();
			this.#showAddClientTitle();

			return;
		}

		const phone = this.getCurrentPhone();
		if (!phone)
		{
			/*
			now the situation of the absence of the client's phone
			has not been worked out by the product manager in any way

			@todo need handle this situation
			 */
			this.#showContent();
			this.#showAddClientTitle();

			return;
		}

		const clientElement = Tag.render`
			<span 
				id="crm-gotochat-client-selector--selected" 
				class="crm-entity-stream-content-gotochat-user-selector-link --selected" 
				onclick="${this.onShowClientPhoneSelector.bind(this)}"
			>
				<span 
					class="crm-entity-stream-content-gotochat-client-avatar"
					style="background-image: url('${this.getEntityAvatarPath(client.entityTypeName.toLowerCase())}');"
				>
				</span>
				${Text.encode(client.caption)}, ${Text.encode(phone.valueFormatted)}
				<span class="crm-entity-stream-content-gotochat-client-chevron"></span>
			</span>
		`;
		const titleContainer = Tag.render`
			<span>
				${Loc.getMessage('CRM_TIMELINE_GOTOCHAT_SELECTED_CLIENT_TITLE')}
			</span>
		`;
		const titleElement = titleContainer.firstChild;

		const labelIndex = titleElement.textContent.indexOf('#CLIENT_NAME#');
		titleElement.nodeValue = titleElement.nodeValue.replace('#CLIENT_NAME#', '');

		Dom.insertBefore(clientElement, titleElement.splitText(labelIndex));

		const container = document.querySelector(`.${CLIENTS_SELECTOR_TITLE_CLASS}`);
		Dom.clean(container);
		Dom.append(titleContainer, container);
	}

	// eslint-disable-next-line class-methods-use-this
	#showContent(): void
	{
		Dom.removeClass(
			document.querySelector('.crm-entity-stream-content-new-detail-gotochat-container'),
			'hidden',
		);

		Dom.removeClass(
			document.querySelector('.crm-entity-stream-content-new-detail-gotochat'),
			'--skeleton',
		);
	}

	#showAddClientTitle(): void
	{
		const container = document.querySelector(`.${CLIENTS_SELECTOR_TITLE_CLASS}`);
		Dom.clean(container);
		Dom.append(this.#getClientTitleHtmlElement(), container);
	}

	// eslint-disable-next-line class-methods-use-this
	#hideContent(): void
	{
		Dom.addClass(
			document.querySelector('.crm-entity-stream-content-new-detail-gotochat-container'),
			'hidden',
		);

		Dom.addClass(
			document.querySelector('.crm-entity-stream-content-new-detail-gotochat'),
			'--skeleton',
		);
	}

	#removeCurrentClient(): void
	{
		this.selectedClient = null;
		this.fromPhoneId = null;
	}

	#adjustChatServiceButtons(): void
	{
		const oldContainer = document.querySelector(`.${BUTTONS_CONTAINER_CLASS}`);
		const newContainer = this.#getServiceButtons();

		Dom.replace(oldContainer, newContainer);
	}

	#getServiceButtons(): HTMLElement
	{
		this.#fillChatServiceButtons();

		return Tag.render`
			<div class="${BUTTONS_CONTAINER_CLASS}">
				${[...this.#chatServiceButtons.values()]}
			</div>
		`;
	}

	#fillChatServiceButtons(): void
	{
		ServicesConfig.forEach((service: ChatService) => {
			if (!this.#isServiceSupportedInRegion(service))
			{
				return;
			}

			const button = this.#createChatServiceButton(service);
			this.#chatServiceButtons.set(service.id, button);
		});
	}

	#isServiceSupportedInRegion(service: ChatService): boolean
	{
		if (!service.region || !this.#region)
		{
			return true;
		}

		if (service.region !== this.#region && service.region[0] !== '!')
		{
			return false;
		}

		return (service.region !== `!${this.#region}`);
	}

	#createChatServiceButton(service: ChatService): HTMLElement
	{
		let className = service.commonClass;
		let label = service.connectLabel;

		if (!this.#isAvailableService(service.id))
		{
			className += ' --disabled';
			label = service.soonLabel;
		}
		else if (this.#isServiceSelected(service))
		{
			className += ' --ready';
			label = service.inviteLabel;
		}

		return Tag.render`
			<div 
				class="crm-entity-stream-content-gotochat-button"
				onclick="${this.showRegistrarAndSend.bind(this, service.id)}"
			>
				<button 
					class="crm-entity-stream-content-new-detail-gotochat_button ${className}"
					data-code="${service.id}"
				>
					${this.#renderButtonIcon(service)}
					<span class="crm-entity-stream-content-new-detail-gotochat_button-text">${label}</span>
				</button>
			</div>
		`;
	}

	#renderButtonIcon(service: ChatService | undefined): HTMLElement
	{
		if (!service)
		{
			return '';
		}

		const icon = new Icon({
			icon: service.iconClass,
			size: 40,
			color: this.#getButtonIconColor(service),
		});

		return Tag.render`
			<i class="crm-entity-stream-content-new-detail-gotochat_button-icon">
				${icon.render()}
			</i>
		`;
	}

	#getButtonIconColor(service: ChatService): string
	{
		if (!this.#isAvailableService(service.id))
		{
			return getComputedStyle(document.body).getPropertyValue('--ui-color-base-40');
		}

		if (this.#isServiceSelected(service))
		{
			return getComputedStyle(document.body).getPropertyValue('--ui-color-background-primary');
		}

		return service.iconColor;
	}

	#isServiceSelected(service: ChatService): boolean
	{
		const id = service.checkServiceId ?? service.id;

		return this.openLineItems?.[id]?.selected;
	}

	async showRegistrarAndSend(code: string): Promise<void>
	{
		if (this.isSending || !this.#isAvailableService(code))
		{
			return;
		}

		if (this.#isEntityInEditorMode())
		{
			await this.#showEditorInEditModePopup();
		}

		if (!this.selectedClient && !this.hasClients)
		{
			this.#showNotSelectedClientNotify();

			return;
		}

		if (!this.toPhoneId)
		{
			const content = Loc.getMessage('CRM_TIMELINE_GOTOCHAT_CLIENT_HAVE_NO_PHONE');
			this.#showNotify(content);

			return;
		}

		this.showButtonLoader(code);

		const service = this.#getServiceConfigByCode(code);
		const { entityTypeId } = this.#getOwnerEntity();
		const lineId = await ConditionChecker.checkAndGetLine({
			openLineCode: service.connectorId,
			senderType: this.getSenderType(),
			openLineItems: this.openLineItems,
			serviceId: service.id,
			entityTypeId,
		});

		if (lineId === null)
		{
			this.#restoreButton(code);
		}
		else
		{
			this.send(lineId, code);
		}
	}

	#getServiceConfigByCode(code: string): ?ChatService
	{
		return ServicesConfig.get(code) || null;
	}

	#isAvailableService(code: string): boolean
	{
		return this.#services[code] ?? false;
	}

	#isEntityInEditorMode(): boolean
	{
		return (this.#getEntityEditor().getMode() === BX.UI.EntityEditorMode.edit);
	}

	async #showEditorInEditModePopup(): Promise
	{
		const { entityTypeId } = this.#getOwnerEntity();
		const entityType = BX.CrmEntityType.resolveName(entityTypeId);
		const message = (
			Loc.getMessage(`CRM_TIMELINE_GOTOCHAT_EDITOR_HAVE_UNSAVED_CHANGES_TEXT_${entityType}`)
			|| Loc.getMessage('CRM_TIMELINE_GOTOCHAT_EDITOR_HAVE_UNSAVED_CHANGES_TEXT')
		);

		return new Promise((resolve) => {
			BX.UI.Dialogs.MessageBox.show({
				modal: true,
				message,
				buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
				okCaption: Loc.getMessage('CRM_TIMELINE_GOTOCHAT_EDITOR_HAVE_UNSAVED_CHANGES_SAVE_AND_CONTINUE'),
				onOk: (messageBox) => {
					this.saveEntityEditor();
					messageBox.close();
					resolve();
				},
				cancelCaption: Loc.getMessage('CRM_TIMELINE_GOTOCHAT_EDITOR_HAVE_UNSAVED_CHANGES_FORCE_CONTINUE'),
				onCancel: function(messageBox) {
					messageBox.close();
					resolve();
				},
			});
		});
	}

	saveEntityEditor(): void
	{
		this.#getEntityEditor().saveChanged();
	}

	#getEntityEditor(): BX.Crm.EntityEditor
	{
		if (!this.#entityEditor)
		{
			this.#entityEditor = BX.Crm.EntityEditor.getDefault();
		}

		return this.#entityEditor;
	}

	#showNotSelectedClientNotify(): void
	{
		const content = Loc.getMessage('CRM_TIMELINE_GOTOCHAT_NO_SELECTED_CLIENT');
		this.#showNotify(content);
	}

	// eslint-disable-next-line class-methods-use-this
	#showNotify(content: string): void
	{
		BX.UI.Notification.Center.notify({ content });
	}

	send(lineId: string, code: string): void
	{
		this.isSending = true;

		const { entityTypeId: ownerTypeId, entityId: ownerId } = this.#getOwnerEntity();
		const senderType = this.getSenderType();
		const senderId = this.currentChannelId;
		const from = this.fromPhoneId;
		const to = this.toPhoneId;
		const connectorId = this.#getServiceConfigByCode(code).connectorId;

		const ajaxParameters = {
			ownerTypeId,
			ownerId,
			params: {
				senderType,
				senderId,
				from,
				to,
				lineId,
				connectorId,
			},
		};

		ajax.runAction('crm.activity.gotochat.send', { data: ajaxParameters })
			.then(() => {
				this.isSending = false;

				this.#setOpenLineItemIsSelected(code);
				this.#restoreButton(code);

				this.#showNotify(Loc.getMessage('CRM_TIMELINE_GOTOCHAT_SEND_SUCCESS'));
				this.emitFinishEditEvent();
			})
			.catch((data) => {
				this.isSending = false;
				this.#restoreButton(code);

				if (data.errors.length > 0)
				{
					this.#showNotify(data.errors[0].message);

					return;
				}

				this.#showNotify(Loc.getMessage('CRM_TIMELINE_GOTOCHAT_SEND_ERROR'));
			})
		;
	}

	#setOpenLineItemIsSelected(code: string): void
	{
		const service = this.#getServiceById(code);

		this.openLineItems[service?.checkServiceId ?? code].selected = true;
	}

	#getServiceById(id: string): ?ChatService
	{
		return [...ServicesConfig.values()].find((item) => item.id === id) ?? null;
	}

	#restoreButton(code: string): void
	{
		const oldButton = this.#chatServiceButtons.get(code);
		const newButton = this.#createChatServiceButton(ServicesConfig.get(code));

		this.#chatServiceButtons.set(code, newButton);

		Dom.replace(oldButton, newButton);
	}

	showButtonLoader(code: string): void
	{
		const button = this.#chatServiceButtons.get(code);
		Dom.addClass(button?.firstElementChild, '--loading');
	}

	getSenderType(): string
	{
		return (this.currentChannelId === SenderTypes.bitrix24 ? SenderTypes.bitrix24 : SenderTypes.sms);
	}

	// eslint-disable-next-line class-methods-use-this
	getEntityAvatarPath(entityTypeName: string): string
	{
		// eslint-disable-next-line no-param-reassign
		entityTypeName = entityTypeName.toLowerCase();

		const whiteList = [
			'contact',
			'company',
			'lead',
		];

		if (!whiteList.includes(entityTypeName))
		{
			return '';
		}

		return `/bitrix/images/crm/entity_provider_icons/${entityTypeName}.svg`;
	}

	getChannelsSubmenuItems(): MenuItemOptions[]
	{
		const items = [];
		this.channels.forEach(({ id, shortName: text, canUse, fromList }) => {
			const className = (id === this.currentChannelId ? ACTIVE_MENU_ITEM_CLASS : DEFAULT_MENU_ITEM_CLASS);
			items.push({
				id,
				text,
				className,
				disabled: !canUse || !Type.isArrayFilled(fromList),
				onclick: this.onSelectSender,
			});
		});

		return [
			...items,
			{
				id: 'connectOtherSenderDelimiter',
				delimiter: true,
			},
			{
				id: 'connectOtherSender',
				text: Loc.getMessage('CRM_TIMELINE_GOTOCHAT_CONNECT_OTHER_SENDER_SERVICE'),
				className: DEFAULT_MENU_ITEM_CLASS,
				onclick: () => BX.SidePanel.Instance.open(this.marketplaceUrl),
			},
		];
	}

	onSelectSender(event, item)
	{
		const { id } = item;

		this.currentChannelId = id;

		const channel = this.#getChannelById(id);
		this.fromPhoneId = channel.fromList[0].id;

		this.settingsMenu.destroy();
		this.initSettingsMenu();
	}

	getPhoneSubMenuItems(): Array
	{
		const currentChannel = this.#getChannelById(this.currentChannelId);
		const items = [];

		if (currentChannel)
		{
			currentChannel.fromList.forEach(({ id, name: text }) => {
				const className = (id === this.fromPhoneId ? ACTIVE_MENU_ITEM_CLASS : DEFAULT_MENU_ITEM_CLASS);
				items.push({
					id,
					text,
					className,
					onclick: this.onSelectSenderPhone,
				});
			});
		}

		return items;
	}

	#getChannelById(id: string): Channel
	{
		return this.channels.find((channel) => channel.id === id);
	}

	onSelectSenderPhone(event, item)
	{
		const { id } = item;

		this.fromPhoneId = id;

		this.settingsMenu.destroy();
		this.initSettingsMenu();
	}

	#getLoader(): Loader
	{
		if (!this.loader)
		{
			this.loader = new Loader({
				color: '#2fc6f6',
				size: 36,
			});
		}

		return this.loader;
	}

	onHide(): void
	{
		if (this.loader)
		{
			this.loader.destroy();
		}
	}

	#hideLoader(): void
	{
		if (this.loader)
		{
			void this.loader.hide();
		}
	}
}
