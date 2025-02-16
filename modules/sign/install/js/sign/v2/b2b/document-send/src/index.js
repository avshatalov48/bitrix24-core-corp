import { Dom, Loc, Reflection, Tag, Text, Type } from 'main.core';
import { EventEmitter, type BaseEvent } from 'main.core.events';
import { MenuManager } from 'main.popup';
import { Api } from 'sign.v2.api';
import { Hint } from 'sign.v2.helper';
import { LangSelector } from 'sign.v2.lang-selector';
import { DocumentSummary } from 'sign.v2.document-summary';
import './style.css';
import type { DocumentSendConfig, DocumentData } from './types/config';

const menuPrefix = 'sign-member-communication';

export type { DocumentSendConfig };

export class DocumentSend extends EventEmitter
{
	entityData;
	communications;
	#api: Api;
	#menus: Array<Menu>;
	#sendContainer: HTMLElement;
	#documentData: DocumentData;
	#langContainer: HTMLElement;
	#config: DocumentSendConfig;
	#langSelector: LangSelector;
	#documentSummary: DocumentSummary;

	constructor(config: DocumentSendConfig)
	{
		super();
		this.setEventNamespace('BX.Sign.V2.DocumentSend');
		this.entityData = {};
		this.communications = {};
		this.#config = config;
		this.#api = new Api();
		this.#langSelector = new LangSelector(
			this.#config.region,
			this.#config.languages,
		);
		this.#documentSummary = new DocumentSummary({
			events: {
				changeTitle: (event: BaseEvent) => {
					const data = event.getData();
					this.#documentData.title = data.title;
					this.emit('changeTitle', data);
				},
				showEditor: (event: BaseEvent) => this.emit('showEditor'),
			},
		});
		this.#langContainer = Tag.render`
			<div class="sign-document-send__lang-container">
				${this.#langSelector.getLayout()}
				<span data-hint="${Loc.getMessage('SIGN_DOCUMENT_LANGUAGE_BUTTON_INFO')}"></span>
			</div>
		`;
		Hint.create(this.#langContainer);
		this.#menus = [];
		this.#documentData = {};
	}

	get documentData(): DocumentData
	{
		return this.#documentData;
	}

	set documentData(documentData: DocumentData)
	{
		const { uid, title } = documentData;
		this.#documentSummary.addItem(uid, { uid, title });

		this.#documentData = documentData;
	}

	getLayout(): HTMLElement
	{
		this.#langSelector.setDocumentUid(this.#documentData.uid);
		this.#sendContainer = Tag.render`
			<div class="sign-document-send">
				<div class="sign-document-send__summary-title-wrapper">
					<p class="sign-document-send__title">
						${Loc.getMessage('SIGN_DOCUMENT_SEND_TITLE')}
					</p>
					${this.#langContainer}
				</div>
				${this.#documentSummary.getLayout()}
				${this.#createParties()}
			</div>
		`;

		return this.#sendContainer;
	}

	#attachMenu(idMeans: HTMLElement, entityData): Menu
	{
		let menuItems = [];
		const menuId = `${menuPrefix}-${entityData.entityTypeId}-${entityData.id}`;
		if (this.#menus[menuId])
		{
			let items = this.#menus[menuId].getMenuItems();
			const swap = (array, from, to) => {
				const tmp = array[to];
				// eslint-disable-next-line no-param-reassign
				array[to] = array[from];
				// eslint-disable-next-line no-param-reassign
				array[from] = tmp;
			};

			while (items.length > 1)
			{
				if (items[0].id === 'show-member')
				{
					swap(items, 0, 1);
					continue;
				}
				this.#menus[menuId].removeMenuItem(items[0].id);
				items = this.#menus[menuId].getMenuItems();
			}
		}

		this.#api.loadCommunications(entityData.uid).then(async (multiFields) => {
			let selectedCommunication = {};
			const restrictions = await this.#api.loadRestrictions();
			const mapper = (communication) => {
				let text = communication.VALUE;
				if (communication?.TYPE === 'PHONE' && BX.PhoneNumberParser)
				{
					BX.PhoneNumberParser.getInstance().parse(communication.VALUE).then((parsedNumber) => {
						text = parsedNumber.format(BX.PhoneNumber.Format.INTERNATIONAL);
					});
				}

				if ((communication?.TYPE === 'PHONE' && restrictions.smsAllowed && selectedCommunication?.TYPE !== 'PHONE')
					|| (communication?.TYPE === 'EMAIL' && Object.keys(selectedCommunication).length === 0))
				{
					selectedCommunication = communication;
				}

				return {
					text,
					onclick: ({ target }) => {
						this.#updateCommunications(entityData, communication);
						// eslint-disable-next-line no-param-reassign
						idMeans.firstElementChild.textContent = text;
						this.#menus[menuId].close();
						this.#updatePhoneAttr(idMeans, communication?.TYPE === 'PHONE' ? communication.VALUE : null);
					},
				};
			};

			menuItems = [
				// eslint-disable-next-line no-unsafe-optional-chaining
				...(multiFields?.EMAIL ? multiFields?.EMAIL.map((element) => mapper(element)) : []),
				// eslint-disable-next-line no-unsafe-optional-chaining
				...(multiFields?.PHONE ? await Promise.all(multiFields?.PHONE.map(async (element) => {
					const item = mapper(element);
					item.text = await this.#formatPhoneNumberForUi(item.text);

					return item;
				})) : []),
			];

			menuItems.map((item) => this.#menus[menuId].addMenuItem(item, null));
			this.#updateCommunications(entityData, selectedCommunication);

			idMeans.firstElementChild.textContent = selectedCommunication?.TYPE === 'PHONE'
				? await this.#formatPhoneNumberForUi(selectedCommunication?.VALUE)
				: selectedCommunication?.VALUE
			;

			this.#updatePhoneAttr(idMeans, selectedCommunication?.TYPE === 'PHONE' ? selectedCommunication?.VALUE : null);
		}).catch(() => {});

		menuItems.push({
			id: 'show-member',
			text: Loc.getMessage('SIGN_DOCUMENT_SEND_OPEN_VIEW'),
			onclick: () => {
				this.#showMemberInfo(idMeans, entityData);
				this.#menus[menuId].close();
			},
		});

		if (!this.#menus[menuId])
		{
			this.#menus[menuId] = MenuManager.create({
				id: menuId,
				items: menuItems,
			});
			const popup = this.#menus[menuId].getPopupWindow();
			popup.setBindElement(idMeans);
		}

		// eslint-disable-next-line no-param-reassign
		idMeans.firstElementChild.textContent = menuItems[0].text;

		return this.#menus[menuId];
	}

	async #formatPhoneNumberForUi(phone: string): Promise<string>
	{
		let phoneFormatted = phone;
		if (BX.PhoneNumberParser)
		{
			phoneFormatted = await BX.PhoneNumberParser.getInstance().parse(phone).then((parsedNumber) => {
				return parsedNumber.format(BX.PhoneNumber.Format.INTERNATIONAL);
			});
		}

		return phoneFormatted;
	}

	#highlightCommunicationWithError(phoneNumber: string): void
	{
		const selectors = [
			`.sign-document-send__party_id-means[data-phone-source="${phoneNumber}"]`,
			`.sign-document-send__party_id-means[data-phone-normalized="${phoneNumber}"]`,
		];
		selectors.forEach((selector) => {
			const aIdMeans = this.#sendContainer.querySelectorAll(selector);
			aIdMeans.forEach((elem) => {
				const wrapper = elem.closest('.sign-document-send__party');
				Dom.addClass(wrapper, '--validation-error');
			});
		});
	}

	async #updatePhoneAttr(elem: Element, phone: string = null): void
	{
		const normalized = await BX.PhoneNumberParser.getInstance().parse(phone).then((parsedNumber) => {
			return parsedNumber.format(BX.PhoneNumber.Format.E164);
		});

		if (Type.isStringFilled(phone))
		{
			Dom.attr(elem, 'data-phone-source', phone);
			Dom.attr(elem, 'data-phone-normalized', normalized);
		}
		else
		{
			elem.removeAttribute('data-phone-source');
			elem.removeAttribute('data-phone-normalized');
		}
	}

	resetCommunicationErrors(): void
	{
		const elems = this.#sendContainer.querySelectorAll('.sign-document-send__party');
		elems.forEach((elem) => {
			Dom.removeClass(elem, '--validation-error');
		});
	}

	#showMemberInfo(idMeans: HTMLElement, entityData: Object)
	{
		const { Instance: slider } = Reflection.getClass('BX.SidePanel');
		slider.open(entityData.url, {
			cacheable: false,
			allowChangeHistory: false,
			events: {
				onClose: () => {
					this.#attachMenu(idMeans, entityData);
				},
			},
		});
	}

	#createParties(): Array<HTMLElement>
	{
		const parties = [
			{
				partyTitle: Loc.getMessage('SIGN_DOCUMENT_SEND_FIRST_PARTY'),
				entityData: this.entityData.company,
			},
			{
				partyTitle: Loc.getMessage('SIGN_DOCUMENT_SEND_PARTNER'),
				entityData: this.entityData.contact,
			},
		];
		Object.keys(MenuManager.Data).forEach((menuId) => {
			if (menuId.includes(menuPrefix))
			{
				MenuManager.destroy(menuId);
			}
		});

		this.#menus = [];

		return parties.map((party) => {
			const { partyTitle, entityData } = party;
			const { title } = entityData;
			const idMeans = Tag.render`
				<span
					class="sign-document-send__party_id-means"
					onclick="${() => menu.show()}"
				>
					<span></span>
				</span>
			`;
			const menu = this.#attachMenu(idMeans, entityData);

			return Tag.render`
				<div class="sign-document-send__party">
					<div class="sign-document-send__party_summary">
						<p class="sign-document-send__party_title">${partyTitle}</p>
						<span class="sign-document-send__party_member-name">
							${Text.encode(title)}
						</span>
						<span class="sign-document-send__party_status">
							${Loc.getMessage('SIGN_DOCUMENT_SEND_NOT_SIGNED')}
						</span>
					</div>
					<div class="sign-document-send__party_id">
						<p class="sign-document-send__party_title">
							${Loc.getMessage('SIGN_DOCUMENT_SEND_PARTY_ID')}
						</p>
						${idMeans}
					</div>
				</div>
			`;
		});
	}

	#updateCommunications(entityData, communication)
	{
		// eslint-disable-next-line @bitrix24/bitrix24-rules/no-typeof
		if (typeof communication === 'undefined')
		{
			return;
		}

		const { TYPE: type, VALUE: value } = communication;
		this.communications = {
			...this.communications,
			[entityData.type]: { type, value },
		};

		this.resetCommunicationErrors();
	}

	async sendForSign(): Promise<boolean>
	{
		try
		{
			const { communications, entityData } = this;
			const entries = Object.entries(communications);
			let allowToComplete = true;
			const restrictions = await this.#api.loadRestrictions();
			for (const [entityType, item] of entries)
			{
				const { type, value } = item;
				const { uid: memberUid } = entityData[entityType];
				if (!restrictions.smsAllowed && type === 'PHONE')
				{
					top.BX.UI.InfoHelper.show('limit_crm_sign_messenger_identification');
					allowToComplete = false;

					continue;
				}

				this.#api.modifyCommunicationChannel(memberUid, type, value);
			}

			const {
				uid: documentUid,
				initiator,
			} = this.#documentData;
			await this.#api.modifyInitiator(documentUid, initiator);
			if (!allowToComplete)
			{
				return false;
			}
			await this.#api.configureDocument(documentUid);
			await this.#checkFillAndStartProgress(documentUid);

			return true;
		}
		catch (e)
		{
			if (Type.isArrayFilled(e?.errors))
			{
				const firstError = e.errors[0];
				e.errors.forEach((error) => {
					if (
						error?.code === firstError.code
						&& (error?.code === 'MEMBER_INVALID_PHONE' || error?.code === 'MEMBER_PHONE_UNSUPPORTED_COUNTRY_CODE')
						&& Type.isStringFilled(error?.customData?.phone)
					)
					{
						this.#highlightCommunicationWithError(error.customData.phone);
					}
				});
			}

			return false;
		}
	}

	async #checkFillAndStartProgress(uid: string): Promise<void>
	{
		let completed = false;
		while (!completed)
		{
			// eslint-disable-next-line no-await-in-loop
			const result = await this.#api.getDocumentFillAndStartProgress(uid);
			completed = result.completed;
			if (!completed)
			{
				// eslint-disable-next-line no-await-in-loop
				await this.#sleep(1000);
			}
		}
	}

	#sleep(ms: Number): Promise
	{
		return new Promise((resolve) => {
			setTimeout(resolve, ms);
		});
	}

	setDocumentsBlock(documents): void
	{
		const documentsObject = Object.fromEntries(documents);
		this.#documentSummary.setItems(documentsObject);
	}
}
