import { Editor, FilledPlaceholder } from 'crm.template.editor';
import { TourManager } from 'crm.tour-manager';
import { ajax as Ajax, Dom, Event, Loc, Runtime, Tag, Text, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { MenuItem, MenuItemOptions, MenuManager } from 'main.popup';
import { Dialog, DialogOptions } from 'ui.entity-selector';
import { MessageBox } from 'ui.dialogs.messagebox';

import Item from '../../item';
import { createOrUpdatePlaceholder, saveSmsMessage, showChannelManagerSlider } from './internal/actions';
import {
	getCommunicationsItems,
	getNewCommunications,
	getSendersItems,
	getSubmenuStubItems,
	MENU_ITEM_STUB_ID,
	MENU_SETTINGS_ID,
} from './internal/menu-helper';
import { Provider, TemplateItem } from './types';
import Tour from './tour';

import 'ui.design-tokens';
import './layout.css';

const ARTICLE_CODE_SEND_WITH_WHATSAPP = '20526810';

/** @memberof BX.Crm.Timeline.MenuBar */
export default class Whatsapp extends Item
{
	#serviceUrl: string = '';
	#provider: Provider = null;
	#communications: Array = [];

	#sendButton: HTMLElement = null;
	#cancelButton: HTMLElement = null;
	#selectorButton: HTMLElement = null;
	#templatesContainer: HTMLElement = null;
	#templatesContainerTitle: HTMLElement = null;
	#templatesContainerContent: HTMLElement = null;

	#settingsMenu: Menu = null;
	#tplEditor: Editor = null;
	#selectTplDlg: Dialog = null;
	#placeholders: string[];
	#filledPlaceholders: FilledPlaceholder[];

	#canUse: boolean = false;
	#isDemoTemplateSet: boolean = false;
	#isSendRequestRunning: boolean = false;
	#isLocked: boolean = false;
	#isFetchedConfig: boolean = false;

	#template: TemplateItem = null;
	#fromPhoneId: ?string = null;
	#toPhone: ?Object = null;
	#toEntityTypeId: ?number = null;
	#toEntityId: ?number = null;

	#unViewedTourList: string[];

	#fetchConfigPromise: ?Promise = null;

	/**
	 * @override
	 * */
	initializeSettings(): void
	{
		this.#canUse = this.getSetting('canUse');
		this.#serviceUrl = this.getSetting('serviceUrl');
		if (!this.#serviceUrl)
		{
			throw new Error('Whatsapp message sending must be used with serviceUrl');
		}

		this.#unViewedTourList = this.getSetting('unViewedTourList') ?? [];
	}

	/**
	 * @override
	 * */
	createLayout(): HTMLElement
	{
		let iconClass = '--gray';
		let titleMessage = Loc.getMessage('CRM_TIMELINE_SMS_WHATSAPP_HEADER_TITLE_SETUP');
		let description = Loc.getMessage('CRM_TIMELINE_SMS_WHATSAPP_HEADER_DESCRIPTION_SETUP');
		let descriptionClass = '--fixed';
		if (this.#canUse)
		{
			iconClass = '--green';
			titleMessage = Loc.getMessage('CRM_TIMELINE_SMS_WHATSAPP_HEADER_TITLE');
			description = Loc.getMessage('CRM_TIMELINE_SMS_WHATSAPP_HEADER_DESCRIPTION');
			descriptionClass = '';
		}

		return Tag.render`
			<div class="crm-entity-stream-content-whatsapp crm-entity-stream-content-wait-detail --hidden --skeleton">
				<div class="crm-entity-stream-content-whatsapp-container --hidden">
					<div class="crm-entity-stream-content-whatsapp-header">
						<div class="crm-entity-stream-content-whatsapp-header-icon ${iconClass}"></div>
						<div class="crm-entity-stream-content-whatsapp-header-text">
							<div class="crm-entity-stream-content-whatsapp-header-title">
								${titleMessage}
							</div>
							<div class="crm-entity-stream-content-whatsapp-header-description ${descriptionClass}">
								${description}
							</div>
							<div>
								${this.#createHelpLinkContainer()}
							</div>
						</div>
						<div class="crm-entity-stream-content-whatsapp-header-buttons">
							${this.#createHeaderButtons()}
						</div>
					</div>
					${this.#createTemplatesContainer()}
				</div>
				<div class="crm-entity-stream-content-whatsapp-footer --hidden">
					${this.#createFooterButtons()}
				</div>
			</div>
		`;
	}

	/**
	 * @override
	 * */
	initializeLayout()
	{
		super.initializeLayout();

		this.#setTemplate(Runtime.clone(this.getSetting('demoTemplate')));
		this.#subscribeToReceiversChanges();
	}

	/**
	 * @override
	 * */
	showTour()
	{
		if (!this.#isTourAvailable())
		{
			return;
		}

		if (this.#unViewedTourList.includes(Tour.USER_OPTION_PROVIDER_OFF))
		{
			TourManager.getInstance().registerWithLaunch(new Tour({
				itemCode: 'whatsapp',
				title: Loc.getMessage('CRM_TIMELINE_SMS_WHATSAPP_GUIDE_PROVIDER_OFF_TITLE'),
				text: this.getEntityTypeId() === BX.CrmEntityType.enumeration.lead
					? Loc.getMessage('CRM_TIMELINE_SMS_WHATSAPP_GUIDE_PROVIDER_OFF_TEXT_LEAD')
					: Loc.getMessage('CRM_TIMELINE_SMS_WHATSAPP_GUIDE_PROVIDER_OFF_TEXT_DEAL'),
				articleCode: ARTICLE_CODE_SEND_WITH_WHATSAPP,
				userOptionName: Tour.USER_OPTION_PROVIDER_OFF,
			}));
		}
		else if (this.#unViewedTourList.includes(Tour.USER_OPTION_PROVIDER_ON))
		{
			TourManager.getInstance().registerWithLaunch(new Tour({
				itemCode: 'whatsapp',
				title: Loc.getMessage('CRM_TIMELINE_SMS_WHATSAPP_GUIDE_PROVIDER_ON_TITLE'),
				text: this.getEntityTypeId() === BX.CrmEntityType.enumeration.lead
					? Loc.getMessage('CRM_TIMELINE_SMS_WHATSAPP_GUIDE_PROVIDER_ON_TEXT_LEAD')
					: Loc.getMessage('CRM_TIMELINE_SMS_WHATSAPP_GUIDE_PROVIDER_ON_TEXT_DEAL'),
				articleCode: ARTICLE_CODE_SEND_WITH_WHATSAPP,
				userOptionName: Tour.USER_OPTION_PROVIDER_ON,
			}));
		}
	}

	/**
	 * @override
	 * */
	activate(): void
	{
		super.activate();

		// fetch config
		if (this.#isFetchedConfig || !this.getEntityId())
		{
			return;
		}

		this.#isFetchedConfig = false;
		this.#fetchConfigPromise = new Promise((resolve) => {
			Ajax.runAction('crm.api.timeline.whatsapp.getConfig', {
				json: {
					entityTypeId: this.getEntityTypeId(),
					entityId: this.getEntityId(),
				},
			}).then(({ data }) => {
				this.#isFetchedConfig = true;
				this.#prepareParams(data);
				this.#showContent();

				resolve();

				setTimeout(() => {
					if (
						this.supportsLayout()
						&& this.#isTourAvailable()
						&& this.#unViewedTourList.includes(Tour.USER_OPTION_TEMPLATES_READY)
					)
					{
						TourManager.getInstance().registerWithLaunch(new Tour({
							itemCode: 'whatsapp',
							title: Loc.getMessage('CRM_TIMELINE_SMS_WHATSAPP_GUIDE_TEMPLATES_READY_TITLE'),
							text: Loc.getMessage('CRM_TIMELINE_SMS_WHATSAPP_GUIDE_TEMPLATES_READY_TEXT'),
							articleCode: ARTICLE_CODE_SEND_WITH_WHATSAPP,
							userOptionName: Tour.USER_OPTION_TEMPLATES_READY,
							guideBindElement: this.#selectorButton,
						}));

						this.#unViewedTourList = this.#unViewedTourList.filter((name) => name !== Tour.USER_OPTION_TEMPLATES_READY);
					}
				}, 300);
			}).catch(() => {
				this.showNotify(Loc.getMessage('CRM_TIMELINE_GOTOCHAT_CONFIG_ERROR'));

				setTimeout(() => this.emitFinishEditEvent(), 50);
			});
		});
	}

	tryToResend(template: Object, fromId: string, clientData: Object): void
	{
		if (this.#isFetchedConfig)
		{
			this.#prepareToResend(template, fromId, clientData);
		}
		else
		{
			// eslint-disable-next-line promise/catch-or-return
			this.#fetchConfigPromise.then(() => this.#prepareToResend(template, fromId, clientData));
		}
	}

	#prepareParams(data: Object): void
	{
		const { communications, provider } = data;

		this.#provider = provider;
		this.#canUse = this.#provider.canUse;
		this.#communications = communications;

		// set default parameters
		this.#setCommunicationsParams();
		this.#setChannelDefaultPhoneId();
	}

	#prepareToResend(template: Object, fromId: string, clientData: Object): void
	{
		if (!this.#provider)
		{
			throw new Error('Whatsapp provider must be defined');
		}

		const client = this.#communications
			.find((communication: Object) => communication.entityId === clientData.entityId
				&& communication.entityTypeId === clientData.entityTypeId)
		;
		if (Type.isArrayFilled(client.phones) && Type.isStringFilled(clientData.value))
		{
			const toPhone = client.phones.find((row: Object) => row.value === clientData.value);
			if (toPhone)
			{
				this.#toPhone = toPhone;
				this.#toEntityTypeId = client.entityTypeId;
				this.#toEntityId = client.entityId;
			}
		}

		if (Type.isArrayFilled(this.#provider.fromList) && Type.isStringFilled(fromId))
		{
			const from = this.#provider.fromList.find((row: Object) => String(row.id) === fromId);
			if (from)
			{
				this.#fromPhoneId = from.id;
			}
		}

		if (this.#canUse && Type.isPlainObject(template))
		{
			this.#initTemplateSelectDialog({ preselectedItems: [['message_template', template.ORIGINAL_ID]] });
			this.#setTemplate(template);
		}
	}

	#subscribeToReceiversChanges(): void
	{
		EventEmitter.subscribe('BX.Crm.MessageSender.ReceiverRepository:OnReceiversChanged', (event: BaseEvent) => {
			const { item, current } = event.getData();
			if (
				this.getEntityTypeId() !== item?.entityTypeId
				|| this.getEntityId() !== item?.entityId
				|| !Type.isArray(current)
				|| !this.#isFetchedConfig
			)
			{
				return;
			}

			this.#communications = getNewCommunications(current);
			this.#setCommunicationsParams();

			MenuManager.destroy(MENU_SETTINGS_ID);

			this.#applySendButtonState();
			this.#createSettingsMenu();
		});
	}

	// region PRIVATE RENDERERS
	// region PRIVATE DOM MANIPULATIONS METHODS
	#createHelpLinkContainer(): HTMLElement
	{
		const container = Tag.render`
			<a class="crm-entity-stream-content-whatsapp-header-help-link" href="#">
				${Loc.getMessage('CRM_TIMELINE_SMS_WHATSAPP_HEADER_HELP_LINK')}
			</a>
		`;
		Event.bind(container, 'click', () => this.#handleHelpClick(ARTICLE_CODE_SEND_WITH_WHATSAPP));

		return container;
	}

	#createHeaderButtons(): ?HTMLElement
	{
		if (this.#canUse)
		{
			this.#selectorButton = Tag.render`
				<button class="crm-entity-stream-content-whatsapp-header-button-selector">
					<span class="crm-entity-stream-content-whatsapp-header-button-text">
						${Loc.getMessage('CRM_TIMELINE_SMS_WHATSAPP_BUTTON_SELECTOR')}
					</span>
				</button>
			`;
			Event.bind(this.#selectorButton, 'click', () => this.#handleTemplateSelect());

			const settingsButton = Tag.render`
				<button class="ui-btn ui-btn-link ui-btn-xs ui-btn-icon-setting crm-entity-stream-content-whatsapp-header-button-settings">
				</button>
			`;
			Event.bind(settingsButton, 'click', () => this.#handleSettingsMenuClick());

			return Tag.render`
				${this.#selectorButton}
				${settingsButton}
			`;
		}

		return null;
	}

	#createFooterButtons(): HTMLElement
	{
		if (this.#canUse)
		{
			this.#sendButton = Tag.render`
				<button class="ui-btn ui-btn-xs ui-btn-primary ui-btn-round ui-btn-disabled">
					${Loc.getMessage('CRM_TIMELINE_SEND')}
				</button>
			`;
			Event.bind(this.#sendButton, 'click', () => this.#handleSendButtonClick());

			this.#cancelButton = Tag.render`
				<button class="ui-btn ui-btn-xs ui-btn-link">
					${Loc.getMessage('CRM_TIMELINE_CANCEL_BTN')}
				</button>
			`;
			Event.bind(this.#cancelButton, 'click', () => {
				this.#setTemplate(Runtime.clone(this.getSetting('demoTemplate')));
				this.#selectTplDlg = null;
				this.emitFinishEditEvent();
			});

			return Tag.render`
				${this.#sendButton}
				${this.#cancelButton}
			`;
		}

		const setupButton = Tag.render`
			<button class="ui-btn ui-btn-xs ui-btn-primary ui-btn-round">
				${Loc.getMessage('CRM_TIMELINE_CONNECT_BTN')}
			</button>
		`;
		Event.bind(setupButton, 'click', () => showChannelManagerSlider(this.#provider.manageUrl));

		return setupButton;
	}

	#createTemplatesContainer(): HTMLElement
	{
		this.#templatesContainerTitle = Tag.render`
			<div class="crm-entity-stream-content-new-detail-whatsapp-template-title"></div>
		`;

		this.#templatesContainerContent = Tag.render`
			<div class="crm-entity-stream-content-new-detail-whatsapp-template-content"></div>
		`;

		this.#templatesContainer = Tag.render`
			<div class="crm-entity-stream-content-new-detail-whatsapp-template --demo">
				${this.#templatesContainerTitle}
				${this.#templatesContainerContent}
			</div>
		`;

		return this.#templatesContainer;
	}

	#createSettingsMenu(): Menu
	{
		const items = getSubmenuStubItems();

		this.#settingsMenu = MenuManager.create({
			id: MENU_SETTINGS_ID,
			bindElement: document.querySelector('.crm-entity-stream-content-whatsapp-header-button-settings'),
			items: [{
				delimiter: true,
				text: Loc.getMessage('CRM_TIMELINE_MENU_SETTINGS_HEADER'),
			}, {
				id: 'communicationsSubmenu',
				text: Loc.getMessage('CRM_TIMELINE_MENU_SETTINGS_RECEIVER'),
				items,
				events: {
					onSubMenuShow: (event: BaseEvent) => {
						this.#handleShowSubMenu(
							event,
							getCommunicationsItems(
								this.#communications,
								this.#toPhone.id,
								this.#handleCommunicationSelect.bind(this),
							),
						);
					},
				},
			}, {
				id: 'sendersSubmenu',
				text: Loc.getMessage('CRM_TIMELINE_MENU_SETTINGS_SENDER'),
				items,
				disabled: !Type.isArrayFilled(this.#provider.fromList),
				events: {
					onSubMenuShow: (event: BaseEvent) => {
						this.#handleShowSubMenu(
							event,
							getSendersItems(
								this.#provider.fromList,
								this.#fromPhoneId,
								this.#handleSenderPhoneSelect.bind(this),
							),
						);
					},
				},
			}],
		});
	}

	#showContent(): void
	{
		Dom.removeClass(
			document.querySelector('.crm-entity-stream-content-whatsapp-container'),
			'--hidden',
		);

		Dom.removeClass(
			document.querySelector('.crm-entity-stream-content-whatsapp-footer'),
			'--hidden',
		);

		Dom.removeClass(
			document.querySelector('.crm-entity-stream-content-whatsapp'),
			'--skeleton',
		);
	}
	// endregion

	// region SETTERS
	#setTemplate(template: TemplateItem): void
	{
		if (template.ORIGINAL_ID > 0)
		{
			Dom.removeClass(this.#templatesContainer, '--demo');
			this.#isDemoTemplateSet = false;
		}
		else
		{
			// set DEMO template
			Dom.addClass(this.#templatesContainer, '--demo');
			this.#isDemoTemplateSet = true;
		}

		this.#preparePlaceholdersFromTemplate(template);
		this.#templatesContainerTitle.textContent = template.TITLE;
		this.#initTemplateEditor(template);

		this.#template = template;

		this.#applySendButtonState();
	}

	#setCommunicationsParams(): void
	{
		if (this.#isClientPhoneNotSet())
		{
			this.#toPhone = null;
			this.#toEntityTypeId = null;
			this.#toEntityId = null;

			return;
		}

		const defaultCommunication = this.#communications[0];
		if (Type.isArrayFilled(defaultCommunication.phones))
		{
			this.#toPhone = defaultCommunication.phones[0];
			this.#toEntityTypeId = defaultCommunication.entityTypeId;
			this.#toEntityId = defaultCommunication.entityId;
		}
	}

	#setChannelDefaultPhoneId(): void
	{
		if (!this.#provider || !Type.isArrayFilled(this.#provider.fromList))
		{
			return;
		}

		const { fromList } = this.#provider;
		const defaultPhone = fromList.find((item) => item.default);

		this.#fromPhoneId = defaultPhone ? defaultPhone.id : fromList[0].id;
	}

	#applySendButtonState(): void
	{
		const enabled = !this.#isDemoTemplateSet
			&& this.#communications.length > 0
			&& this.#toPhone !== null
			&& this.#template !== null
		;

		if (enabled)
		{
			Dom.removeClass(this.#sendButton, 'ui-btn-disabled');
		}
		else
		{
			Dom.addClass(this.#sendButton, 'ui-btn-disabled');
		}
	}
	// endregion

	// region HANDLERS
	#handleTemplateSelect(): void
	{
		if (!this.#selectTplDlg)
		{
			this.#initTemplateSelectDialog();
		}

		this.#selectTplDlg.show();
	}

	#handleSettingsMenuClick(): void
	{
		if (this.#toPhone === null)
		{
			this.showNotify(Loc.getMessage('CRM_TIMELINE_SMS_WHATSAPP_NO_PHONE_ERROR'));

			return;
		}

		if (!this.#settingsMenu)
		{
			this.#createSettingsMenu();
		}

		this.#settingsMenu.show();
	}

	#handleHelpClick(code: number): void
	{
		if (top.BX.Helper && code > 0)
		{
			top.BX.Helper.show(`redirect=detail&code=${code}`);
		}
	}

	#handleShowSubMenu(event: BaseEvent, items: MenuItemOptions[]): void
	{
		const target: MenuItem = event.getTarget();

		for (const itemOptionsToAdd of items)
		{
			target.getSubMenu()?.addMenuItem(itemOptionsToAdd);
		}

		target.getSubMenu()?.removeMenuItem(MENU_ITEM_STUB_ID);
	}

	#handleSenderPhoneSelect(event: BaseEvent, item: Object): void
	{
		const { id } = item;

		this.#fromPhoneId = id;
		this.#settingsMenu.close();
	}

	#handleCommunicationSelect(event: BaseEvent, item: Object): void
	{
		const { id } = item;

		this.#communications.forEach((communication: Object) => {
			const toPhone = communication.phones.find((phone: Object) => phone.id === id);
			if (toPhone)
			{
				this.#toPhone = toPhone;
				this.#toEntityTypeId = communication.entityTypeId;
				this.#toEntityId = communication.entityId;
			}
		});

		this.#settingsMenu.close();
	}

	#handleApplyPlaceholder(params: Object): void
	{
		if (this.#isDemoTemplateSet)
		{
			return;
		}

		createOrUpdatePlaceholder(
			this.#template?.ORIGINAL_ID ?? null,
			this.getEntityTypeId(),
			this.getEntityCategoryId(),
			params,
		).catch((error) => console.error(error));
	}

	#handleSendButtonClick(): void
	{
		if (this.#isClientPhoneNotSet())
		{
			MessageBox.alert(Loc.getMessage('CRM_TIMELINE_SMS_ERROR_NO_COMMUNICATIONS'));

			return;
		}

		if (!this.#template || this.#isDemoTemplateSet)
		{
			return;
		}

		const text = this.#getTemplateEditorText();
		if (text === '')
		{
			return;
		}

		if (this.#isSendRequestRunning || this.#isLocked)
		{
			return;
		}

		this.setLocked(true);
		this.#isSendRequestRunning = true;
		this.#isLocked = true;

		saveSmsMessage(
			this.#serviceUrl,
			this.#provider.id,
			{
				MESSAGE_FROM: this.#fromPhoneId,
				MESSAGE_TO: this.#toPhone.value,
				MESSAGE_BODY: text,
				MESSAGE_TEMPLATE: this.#template.ID,
				MESSAGE_TEMPLATE_ORIGINAL_ID: this.#template.ORIGINAL_ID,
				MESSAGE_TEMPLATE_WITH_PLACEHOLDER: Type.isPlainObject(this.#placeholders),
				OWNER_TYPE_ID: this.getEntityTypeId(),
				OWNER_ID: this.getEntityId(),
				TO_ENTITY_TYPE_ID: this.#toEntityTypeId,
				TO_ENTITY_ID: this.#toEntityId,
			},
			this.#handleSendSuccess.bind(this),
			this.#handleSendFailure.bind(this),
		).then(
			() => this.setLocked(false),
			() => this.setLocked(false),
		).catch(() => this.setLocked(false));
	}

	#handleSendSuccess(data: Object): void
	{
		this.#isSendRequestRunning = false;
		this.#isLocked = false;

		const error = BX.prop.getString(data, 'ERROR', '');
		if (Type.isStringFilled(error))
		{
			MessageBox.alert(Text.encode(error));

			return;
		}

		this.#setTemplate(Runtime.clone(this.getSetting('demoTemplate')));
		this.#selectTplDlg = null;
		this.emitFinishEditEvent();
	}

	#handleSendFailure(): void
	{
		this.#isSendRequestRunning = false;
		this.#isLocked = false;
	}
	// endregion

	// region TEMPLATES
	getTemplate(): ?TemplateItem
	{
		return this.#isDemoTemplateSet ? null : this.#template;
	}

	#initTemplateEditor(template: TemplateItem): void
	{
		const preview = template?.PREVIEW.replaceAll('\n', '<br>');
		const editorParams = {
			target: this.#templatesContainerContent,
			entityId: this.getEntityId(),
			entityTypeId: this.getEntityTypeId(),
			categoryId: this.getEntityCategoryId(),
			canUsePreview: true,
			onSelect: (params) => this.#handleApplyPlaceholder(params),
		};

		this.#tplEditor = (new Editor(editorParams))
			.setPlaceholders(this.#placeholders)
			.setFilledPlaceholders(this.#filledPlaceholders)
		;

		this.#tplEditor.setBody(preview); // @todo will support other positions too, not only Preview
	}

	#initTemplateSelectDialog(additionalOptions: DialogOptions): void
	{
		const entityTypeId = this.getEntityTypeId();
		const entityId = this.getEntityId();
		const categoryId = this.getEntityCategoryId();

		const defaultOptions: DialogOptions = {
			targetNode: this.#selectorButton,
			multiple: false,
			showAvatars: false,
			dropdownMode: true,
			enableSearch: true,
			context: `SMS-TEMPLATE-SELECTOR-$entityTypeId}-${categoryId}`,
			tagSelectorOptions: {
				textBoxWidth: '100%',
			},
			width: 450,
			entities: [{
				id: 'message_template',
				options: {
					senderId: this.#provider.id,
					entityTypeId,
					entityId,
					categoryId,
				},
			}],
			events: {
				'Item:onSelect': (selectEvent: BaseEvent): void => {
					const item = selectEvent.getData().item;

					this.#setTemplate(item.getCustomData().get('template'));
				},
			},
		};

		const footerData = this.#getFooterData();
		if (Type.isArrayFilled(footerData))
		{
			defaultOptions.footer = footerData;
		}

		this.#selectTplDlg = new Dialog({ ...defaultOptions, ...additionalOptions });
	}

	#preparePlaceholdersFromTemplate(template: TemplateItem): void
	{
		const templatePlaceholders = template.PLACEHOLDERS ?? null;
		if (!Type.isPlainObject(templatePlaceholders))
		{
			this.#placeholders = null;
			this.#filledPlaceholders = null;

			return;
		}

		this.#placeholders = templatePlaceholders;
		if (!Type.isArray(template.FILLED_PLACEHOLDERS))
		{
			// eslint-disable-next-line no-param-reassign
			template.FILLED_PLACEHOLDERS = [];
		}

		this.#filledPlaceholders = template.FILLED_PLACEHOLDERS;
	}

	#getTemplateEditorText(): string
	{
		let text = '';

		if (this.#tplEditor)
		{
			const tplEditorData = this.#tplEditor.getData();
			if (Type.isPlainObject(tplEditorData))
			{
				text = tplEditorData.body; // @todo check position: body or preview
			}
		}

		if (text === '' && this.#template)
		{
			text = this.#template.PREVIEW;
		}

		return text;
	}

	#getFooterData(): Array
	{
		const showForm = () => {
			BX.UI.Feedback.Form.open({
				id: 'b24_crm_timeline_whatsapp_template_suggest_form',
				defaultForm: {
					id: 760,
					lang: 'en',
					sec: 'culzcq',
				},
				forms: [{
					zones: ['ru', 'by', 'kz'],
					id: 758,
					lang: 'ru',
					sec: 'jyafqa',
				}, {
					zones: ['de'],
					id: 764,
					lang: 'de',
					sec: '9h74xf',
				}, {
					zones: ['com.br'],
					id: 766,
					lang: 'com.br',
					sec: 'ddkhcc',
				}, {
					zones: ['es'],
					id: 762,
					lang: 'es',
					sec: '6ni833',
				}, {
					zones: ['en'],
					id: 760,
					lang: 'en',
					sec: 'culzcq',
				}],
			});
		};

		return [
			Tag.render`<span style="width: 100%;"></span>`,
			Tag.render`
				<span onclick="${showForm}" class="ui-selector-footer-link">
					${Loc.getMessage('CRM_TIMELINE_SMS_WHATSAPP_SELECTOR_FOOTER_BUTTON')}
				</span>
			`,
		];
	}
	// endregion

	// region UTILS
	#isTourAvailable(): boolean
	{
		return Type.isArrayFilled(this.#unViewedTourList) && !BX.Crm.EntityEditor.getDefault().isNew();
	}

	#isClientPhoneNotSet(): boolean
	{
		if (this.#communications.length === 0)
		{
			return true;
		}

		return !Type.isArrayFilled(this.#communications[0].phones);
	}
	// endregion
}
