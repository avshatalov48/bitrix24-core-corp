// @flow

import { Tag, Loc, Type, ajax, debounce, Text, Runtime } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { MenuManager } from 'main.popup';
import { CurrencyCore } from 'currency.currency-core';
import { MessageBox } from 'ui.dialogs.messagebox';
import { Label, LabelColor } from 'ui.label';
import DocumentManager from './document-manager';
import { OneCPlanRestrictionSlider } from 'catalog.tool-availability-manager';

declare var BX: {[key: string]: any};

type PaymentDocument = {
	ID: number,
	ORDER_ID: number,
	FORMATTED_DATE: string,
	SUM: number,
	TYPE: 'PAYMENT' | 'SHIPMENT' | 'SHIPMENT_DOCUMENT',
	PAID?: 'Y' | 'N',
	STAGE?: 'NOT_PAID' | 'SENT_NO_VIEWED' | 'VIEWED_NO_PAID' | 'PAID' | 'CANCEL' | 'REFUND',
	DELIVERY_NAME?: string,
	DEDUCTED?: 'Y' | 'N',
	EMP_DEDUCTED_ID?: number,
	STATUS?: 'Y' | 'N',
	WAS_CANCELLED?: 'Y' | 'N',
	ACCOUNT_NUMBER?: string,
};

type CheckDocument = {
	TYPE: 'CHECK',
	URL: string,
	TITLE: string,
}

type OrderDocument = {
	ID: number,
	ACCOUNT_NUMBER: string,
	TITLE: string,
	PRICE_FORMAT: string,
}

type CreateRealizationSliderOptions = {
	orderId?: number,
	paymentId?: number,
};

type Phrases = {[string]: string};

type EntityEditorPaymentDocumentsOptions = {
	CONTEXT: string,
	CURRENCY_FORMAT: {},
	CURRENCY_ID: string,
	OWNER_TYPE_ID: number,
	OWNER_ID: number,
	ENTITY_AMOUNT: number,
	DOCUMENTS: Array<PaymentDocument | CheckDocument>,
	ORDERS: Array<OrderDocument>,
	ORDER_IDS: Array<number>,
	PARENT_CONTEXT: StartsSalescenterApp,
	IS_DELIVERY_AVAILABLE: boolean,
	IS_USED_INVENTORY_MANAGEMENT: boolean,
	SALES_ORDERS_RIGHTS: {},
	IS_INVENTORY_MANAGEMENT_RESTRICTED: boolean,
	IS_WITH_ORDERS_MODE: boolean,
	SHOULD_SHOW_CASHBOX_CHECKS: boolean,
	PHRASES: Phrases,
	PAID_AMOUNT: number,
	TOTAL_AMOUNT: number,
};

type Destroyable = {
	destroy(): any
};

type StartsSalescenterApp = {
	startSalescenterApplication(): any
};

const SPECIFIC_REALIZATION_ERROR_CODES = [
	'REALIZATION_ACCESS_DENIED',
	'REALIZATION_CANNOT_DELETE',
	'REALIZATION_ALREADY_DEDUCTED',
	'REALIZATION_NOT_DEDUCTED',
	'REALIZATION_PRODUCT_NOT_FOUND',
	'SHIPMENT_ACCESS_DENIED',
	'PAYMENT_ACCESS_DENIED',
];

const SPECIFIC_ERROR_CODES = [...SPECIFIC_REALIZATION_ERROR_CODES,
	'DEDUCTION_STORE_ERROR1',
	'SALE_PROVIDER_SHIPMENT_QUANTITY_NOT_ENOUGH',
	'SALE_SHIPMENT_EXIST_SHIPPED',
	'SALE_PAYMENT_DELETE_EXIST_PAID',
	'DDCT_DEDUCTION_QUANTITY_STORE_ERROR',
	'CRM_REALIZATION_NOT_ENOUGH_PRODUCTS',
	'BX_ERROR',
];

type AjaxHandler = (...args: Array<any>) => any;

export class EntityEditorPaymentDocuments
{
	_options: EntityEditorPaymentDocumentsOptions;
	_isDeliveryAvailable: boolean;
	_isTerminalAvailable: boolean;
	_parentContext: StartsSalescenterApp;
	_callContext: string;
	_rootNode: HTMLElement;
	_menus: Array<Destroyable>;
	_phrases: Phrases;
	static _rootNodeClass = 'crm-entity-widget-inner crm-entity-widget-inner--payment';

	constructor(options: EntityEditorPaymentDocumentsOptions)
	{
		this._options = options;
		this._phrases = {};
		if (Type.isPlainObject(options.PHRASES))
		{
			this._phrases = options.PHRASES;
		}
		this._isDeliveryAvailable = this._options.IS_DELIVERY_AVAILABLE;
		this._isTerminalAvailable = this._options.IS_TERMINAL_AVAILABLE;
		this._parentContext = options.PARENT_CONTEXT;
		this._callContext = options.CONTEXT;
		this._rootNode = Tag.render`<div class="${this.constructor._rootNodeClass}"></div>`;
		this._menus = [];
		this._isUsedInventoryManagement = this._options.IS_USED_INVENTORY_MANAGEMENT;
		this._salesOrderRights = this._options.SALES_ORDERS_RIGHTS;
		this._isInventoryManagementRestricted = this._options.IS_INVENTORY_MANAGEMENT_RESTRICTED;
		this._isInventoryManagement1cRestricted = this._options.IS_1C_PLAN_RESTRICTED;
		this._isWithOrdersMode = this._options.IS_WITH_ORDERS_MODE;
		this._isInventoryManagementToolEnabled = this._options.IS_INVENTORY_MANAGEMENT_TOOL_ENABLED;
		this._isOnecMode = this._options.IS_ONEC_MODE;
		this._isSalescenterToolEnabled = this._options.IS_SALESCENTER_TOOL_ENABLED;
		this._isTerminalToolEnabled = this._options.IS_TERMINAL_TOOL_ENABLED;
		this._shouldShowCashboxChecks = this._options.SHOULD_SHOW_CASHBOX_CHECKS;

		this._subscribeToGlobalEvents();
	}

	hasContent(): boolean
	{
		return this._docs().length > 0;
	}

	canAddRealization(): boolean
	{
		return this._isUsedInventoryManagement
			&& !this._isWithOrdersMode
			&& this._salesOrderRights?.modify;
	}

	setVisible(visible: boolean): void
	{
		const isHidden = this._rootNode.classList.contains('is-hidden');

		if (visible && isHidden)
		{
			this._rootNode.classList.remove('is-hidden');
		}
		else if (!visible && !isHidden)
		{
			this._rootNode.classList.add('is-hidden');
		}
	}

	render(): HTMLElement
	{
		this._menus.forEach((menu) => menu.destroy());
		this._rootNode.innerHTML = '';
		this._setupCurrencyFormat();
		this._setEmptyState();

		this._rootNode.append(Tag.render`
			<div class="crm-entity-widget-content-block-inner-container">
				<div class="crm-entity-widget-payment">
					${this._renderDetail()}
					${this._renderDocuments()}
					${this._renderAddDocument()}
					${this._renderDelimiter()}
					${this._renderTotalSum()}
				</div>
			</div>
		`);

		return this._rootNode;
	}

	setOptions(options)
	{
		this._options = options;
	}

	reloadModel(onSuccess?: AjaxHandler, onError?: AjaxHandler)
	{
		if (!this._options.OWNER_ID)
		{
			return;
		}

		const data = {
			data: {
				ownerTypeId: this._options.OWNER_TYPE_ID,
				ownerId: this._options.OWNER_ID,
			},
		};

		const successCallback = (response) => {
			this._loading(false);
			if (response.data)
			{
				this.setOptions(response.data);
				this.render();
				if (onSuccess && Type.isFunction(onSuccess))
				{
					onSuccess(response);
				}

				this._emitChangeDocumentsEvent();
			}
			else
			{
				this._showCommonError();
				if (onError && Type.isFunction(onError))
				{
					onError();
				}
			}
		};

		const errorCallback = () => {
			this._loading(false);
			this._showCommonError();
			if (onError && Type.isFunction(onError))
			{
				onError();
			}
		};

		this._loading(true);

		ajax.runAction('crm.api.entity.fetchPaymentDocuments', data).then(successCallback, errorCallback);
	}

	_renderDetail(): HTMLElement
	{
		return Tag.render`
			<div class="crm-entity-widget-payment-detail">
				<div class="crm-entity-widget-payment-detail-caption">${this._getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_TITLE_MSGVER_2')}</div>
				${!this.hasContent() ? this._renderEmptyStateDescription() : ''}
			</div>
		`;
	}

	_renderEmptyStateDescription(): HTMLElement
	{
		const description = this.canAddRealization()
			? Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_EMPTYSTATE_DESCRIPTION_WITH_REALIZATION')
			: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_EMPTYSTATE_DESCRIPTION');

		return Tag.render`
			<div class="crm-entity-widget-payment-detail-description">${description}</div>
		`;
	}

	_renderDelimiter(): HTMLElement
	{
		return Tag.render`<div class="ui-entity-editor-delimiter"></div>`;
	}

	_setEmptyState(): void
	{
		const isSetAsEmpty = this._rootNode.classList.contains('is-empty');

		if (!this.hasContent() && !isSetAsEmpty)
		{
			this._rootNode.classList.add('is-empty');
		}
		else if (this.hasContent() && isSetAsEmpty)
		{
			this._rootNode.classList.remove('is-empty');
		}
	}

	_renderDocuments(): HTMLElement[]
	{
		const nodes = [];
		this._docs().forEach((doc) => {
			if (doc.TYPE === 'PAYMENT' || doc.TYPE === 'TERMINAL_PAYMENT')
			{
				nodes.push(this._renderPaymentDocument(doc));
			}
			else if (doc.TYPE === 'SHIPMENT')
			{
				nodes.push(this._renderDeliveryDocument(doc));
			}
			else if (doc.TYPE === 'SHIPMENT_DOCUMENT')
			{
				nodes.push(this._renderRealizationDocument(doc));
			}
		});

		return nodes;
	}

	_renderPaymentDocument(doc: PaymentDocument): HTMLElement
	{
		const title = Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_PAYMENT_DATE_MSGVER_2')
			.replaceAll(/#date#/gi, doc.FORMATTED_DATE)
			.replaceAll(/#account_number#/gi, doc.ACCOUNT_NUMBER)
			.replaceAll(/#sum#/gi, this._renderMoney(doc.SUM))
		;
		const labelOptions = {
			text: Loc.getMessage(`CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STAGE_${doc.STAGE}`),
			customClass: 'crm-entity-widget-payment-label',
			color: LabelColor.LIGHT,
			fill: true,
		};
		if (doc.STAGE && doc.STAGE === 'PAID')
		{
			labelOptions.color = LabelColor.LIGHT_GREEN;
		}
		else if (doc.STAGE && doc.STAGE === 'VIEWED_NO_PAID')
		{
			labelOptions.color = LabelColor.LIGHT_BLUE;
		}

		if (!labelOptions.text)
		{
			labelOptions.text = doc.PAID === 'Y'
				? Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STAGE_PAID')
				: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STAGE_NOT_PAID');
		}

		let popupMenu;
		const menuItems = [];
		if (this._isDeliveryAvailable && doc.TYPE === 'PAYMENT')
		{
			menuItems.push({
				text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_CHOOSE_DELIVERY'),
				title: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_CHOOSE_DELIVERY'),
				onclick: () => this._chooseDeliverySlider(doc.ORDER_ID),
			});
		}

		const realizationMenuItem = this._getRealizationMenuItem(
			Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_CREATE_REALIZATION'),
			() => this._createRealizationSlider({ paymentId: doc.ID }),
		);
		if (realizationMenuItem)
		{
			menuItems.push(realizationMenuItem);
		}

		if (doc.TYPE === 'PAYMENT')
		{
			menuItems.push({
				text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_RESEND'),
				onclick: () => this._resendPaymentSlider(doc.ORDER_ID, doc.ID),
			});
		}

		menuItems.push({
			text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_CHANGE_PAYMENT_STATUS'),
			items: [
				{
					text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_PAID'),
					className: (doc.PAID === 'Y') ? 'menu-popup-item-accept-sm' : '',
					onclick: () => {
						this._setPaymentPaidStatus(doc, true);
						popupMenu.close();
					},
				},
				{
					text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_NOT_PAID'),
					className: (doc.PAID === 'Y') ? '' : 'menu-popup-item-accept-sm',
					onclick: () => {
						this._setPaymentPaidStatus(doc, false);
						popupMenu.close();
					},
				},
			],
		});

		if (this._shouldShowCashboxChecks)
		{
			menuItems.push({
				text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_CASHBOX_CHECKS'),
				onclick: () => this._openPaymentChecksListSlider(doc.ID),
			});
		}

		menuItems.push({
			text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE'),
			className: (doc.PAID === 'Y') ? 'menu-popup-no-icon crm-entity-widget-payment-menu-item-remove' : '',
			onclick: () => {
				if (doc.PAID === 'Y')
				{
					return false;
				}
				popupMenu.close();
				MessageBox.confirm(
					Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE_PAYMENT_CONFIRM_TEXT_MSGVER_2'),
					(messageBox) => {
						messageBox.close();
						this._removeDocument(doc);
					},
					Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE_PAYMENT_BUTTON_CONFIRM'),
					(messageBox) => messageBox.close(),
					Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE_PAYMENT_BUTTON_BACK'),
				);
			}
		});

		const openSlider = () => {
			if (doc.TYPE === 'TERMINAL_PAYMENT')
			{
				this._viewTerminalPaymentSlider(doc.ORDER_ID, doc.ID);
			}
			else
			{
				this._resendPaymentSlider(doc.ORDER_ID, doc.ID);
			}
		}

		const openMenu = (event) => {
			event.preventDefault();
			popupMenu = MenuManager.create({
				id: `payment-documents-payment-action-${doc.ID}`,
				bindElement: event.target,
				items: menuItems,
			});
			popupMenu.show();

			const removeDocumentMenuItem = popupMenu.itemsContainer.querySelector('.crm-entity-widget-payment-menu-item-remove');
			if (removeDocumentMenuItem)
			{
				removeDocumentMenuItem.setAttribute('data-hint', Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_PAYMENT_REMOVE_TIP_MSGVER_1'));
				removeDocumentMenuItem.setAttribute('data-hint-no-icon', '');
				BX.UI.Hint.init(popupMenu.itemsContainer);
			}

			this._menus.push(popupMenu);
		};

		return Tag.render`
			<div class="crm-entity-widget-payment-detail">
				<a class="ui-link" onclick="${openSlider}">${title}</a>
				<div class="crm-entity-widget-payment-detail-inner">
					<div class="ui-label ui-label-md ui-label-light crm-entity-widget-payment-action" onclick="${openMenu}">
						<span class="ui-label-inner">${Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_ACTIONS_MENU')}</span>
					</div>
					${(new Label(labelOptions)).render()}
				</div>
			</div>
		`;
	}

	_renderDeliveryDocument(doc: PaymentDocument): HTMLElement
	{
		const labelOptions = {
			text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_WAITING_MSGVER_1'),
			customClass: 'crm-entity-widget-payment-label',
			color: LabelColor.LIGHT,
			fill: true,
		};
		if (doc.DEDUCTED === 'Y')
		{
			labelOptions.text = Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_DELIVERED');
			labelOptions.color = LabelColor.LIGHT_GREEN;
		}
		const title = Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DELIVERY_DATE_MSGVER_2')
			.replaceAll(/#date#/gi, doc.FORMATTED_DATE)
			.replaceAll(/#account_number#/gi, doc.ACCOUNT_NUMBER)
			.replaceAll(/#sum#/gi, this._renderMoney(doc.SUM))
			.replaceAll(/#delivery_name#/gi, doc.DELIVERY_NAME)
		;

		let popupMenu;
		const menuItems = [
			{
				text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_CHANGE_DELIVERY_STATUS'),
				items: [
					{
						text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_DELIVERED'),
						className: (doc.DEDUCTED === 'Y') ? 'menu-popup-item-accept-sm' : '',
						onclick: () => {
							this._setShipmentShippedStatus(doc, true);
							popupMenu.close();
						},
					},
					{
						text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_WAITING_MSGVER_1'),
						className: (doc.DEDUCTED === 'Y') ? '' : 'menu-popup-item-accept-sm',
						onclick: () => {
							this._setShipmentShippedStatus(doc, false);
							popupMenu.close();
						},
					},
				],
			},
			{
				text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE'),
				className: (doc.DEDUCTED === 'Y') ? 'menu-popup-no-icon crm-entity-widget-shipment-menu-item-remove' : '',
				onclick: () => {
					if (doc.DEDUCTED === 'Y')
					{
						return false;
					}
					popupMenu.close();
					MessageBox.confirm(
						Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE_DELIVERY_CONFIRM_TEXT_MSGVER_2'),
						(messageBox) => {
							messageBox.close();
							this._removeDocument(doc);
						},
						Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE_DELIVERY_BUTTON_CONFIRM'),
						(messageBox) => messageBox.close(),
						Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE_DELIVERY_BUTTON_BACK'),
					);
				}
			}
		];

		const openSlider = () => this._viewDeliverySlider(doc.ORDER_ID, doc.ID);

		const openMenu = (event) => {
			event.preventDefault();
			popupMenu = MenuManager.create({
				id: `payment-documents-delivery-action-${doc.ID}`,
				bindElement: event.target,
				items: menuItems,
			});
			popupMenu.show();

			const removeDocumentMenuItem = popupMenu.itemsContainer.querySelector('.crm-entity-widget-shipment-menu-item-remove');
			if (removeDocumentMenuItem)
			{
				removeDocumentMenuItem.setAttribute('data-hint', Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_REMOVE_TIP_MSGVER_1'));
				removeDocumentMenuItem.setAttribute('data-hint-no-icon', '');
				BX.UI.Hint.init(popupMenu.itemsContainer);
			}

			this._menus.push(popupMenu);
		};

		return Tag.render`
			<div class="crm-entity-widget-payment-detail">
				<a class="ui-link" onclick="${openSlider}">
					${title}
				</a>
				<div class="crm-entity-widget-payment-detail-inner">
					<div class="ui-label ui-label-md ui-label-light crm-entity-widget-payment-action" onclick="${openMenu}">
						<span class="ui-label-inner">${Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_ACTIONS_MENU')}</span>
					</div>
					${(new Label(labelOptions)).render()}
				</div>
			</div>
		`;
	}

	_renderRealizationDocument(doc: PaymentDocument): HTMLElement
	{
		const labelOptions = {
			customClass: 'crm-entity-widget-payment-label',
			fill: true,
		};
		if (doc.DEDUCTED === 'Y')
		{
			labelOptions.text = Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_STATUS_DEDUCTED');
			labelOptions.color = LabelColor.LIGHT_GREEN;
		}
		else
		if (doc.EMP_DEDUCTED_ID)
		{
			labelOptions.text = Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_STATUS_CANCELLED');
			labelOptions.color = LabelColor.LIGHT_ORANGE;
		}
		else
		{
			labelOptions.text = Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_STATUS_DRAFT');
			labelOptions.color = LabelColor.LIGHT;
		}

		let title = Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_DATE_MSGVER_2')
			.replaceAll(/#date#/gi, doc.FORMATTED_DATE)
			.replaceAll(/#document_id#/gi, doc.ACCOUNT_NUMBER)
		;
		title = BX.util.htmlspecialchars(title);
		title = title.replaceAll(/#sum#/gi, this._renderMoney(doc.SUM));

		let popupMenu;
		const menuItems = [];

		if (this._salesOrderRights?.view)
		{
			if (doc.DEDUCTED === 'Y' && this._salesOrderRights?.conduct && !this._isOnecMode)
			{
				if (this._salesOrderRights?.conduct)
				{
					menuItems.push({
						text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_STATUS_CANCEL'),
						className: (doc.DEDUCTED === 'Y') ? '' : 'menu-popup-item-accept-sm',
						onclick: () => {
							this._setRealizationDeductedStatus(doc, false);
							popupMenu.close();
						},
					});
				}
			}
			else if (doc.DEDUCTED !== 'Y' && this._salesOrderRights?.cancel)
			{
				menuItems.push({
					text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_STATUS_CONDUCT'),
					className: (doc.DEDUCTED === 'Y') ? 'menu-popup-item-accept-sm' : '',
					onclick: () => {
						this._setRealizationDeductedStatus(doc, true);
						popupMenu.close();
					},
				});
			}

			if (this._salesOrderRights?.delete && !(this._isOnecMode && doc.DEDUCTED === 'Y'))
			{
				menuItems.push({
					text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE'),
					className: (doc.DEDUCTED === 'Y') ? 'menu-popup-no-icon crm-entity-widget-realization-menu-item-remove' : '',
					onclick: () => {
						if (doc.DEDUCTED === 'Y')
						{
							return false;
						}
						popupMenu.close();
						MessageBox.confirm(
							Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_CONFIRM_REMOVE_TEXT_MSGVER_1'),
							(messageBox) => {
								messageBox.close();
								this._removeDocument(doc);
							},
							Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_CONFIRM_REMOVE_BUTTON_CONFIRM'),
							(messageBox) => messageBox.close(),
							Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_CONFIRM_REMOVE_BUTTON_BACK'),
						);
					}
				});
			}

			if (this._isOnecMode && doc.DEDUCTED === 'Y')
			{
				menuItems.push({
					text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_OPEN'),
					onclick: () => {
						popupMenu.close();
						this._viewRealizationSlider(doc.ID);
					},
				});
			}
		}

		const openSlider = () => this._viewRealizationSlider(doc.ID);

		const openMenu = (event) => {
			event.preventDefault();
			popupMenu = MenuManager.create({
				id: `payment-documents-realization-action-${doc.ID}`,
				bindElement: event.target,
				items: menuItems,
			});
			popupMenu.show();

			const removeDocumentMenuItem = popupMenu.itemsContainer.querySelector('.crm-entity-widget-realization-menu-item-remove');
			if (removeDocumentMenuItem)
			{
				removeDocumentMenuItem.setAttribute('data-hint', Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REALIZATION_REMOVE_TIP_MSGVER_1'));
				removeDocumentMenuItem.setAttribute('data-hint-no-icon', '');
				BX.UI.Hint.init(popupMenu.itemsContainer);
			}

			this._menus.push(popupMenu);
		};

		const actionMenu =			menuItems.length > 0
			? Tag.render`
				<div class="ui-label ui-label-md ui-label-light crm-entity-widget-payment-action" onclick="${openMenu}">
					<span class="ui-label-inner">${Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_ACTIONS_MENU')}</span>
				</div>
			`
			: ''
		;

		return Tag.render`
			<div class="crm-entity-widget-payment-detail">
				<a class="ui-link" onclick="${openSlider}">
					${title}
				</a>
				<div class="crm-entity-widget-payment-detail-inner">
					${actionMenu}
					${(new Label(labelOptions)).render()}
				</div>
			</div>
		`;
	}

	_renderAddDocument(): HTMLElement
	{
		const latestOrderId = this._latestOrderId();

		const menuItems = [
			{
				text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DOCUMENT_TYPE_PAYMENT'),
				onclick: () => this._createPaymentSlider(latestOrderId),
			}
		];

		if (this._isDeliveryAvailable)
		{
			menuItems.push({
				text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DOCUMENT_TYPE_PAYMENT_DELIVERY'),
				onclick: () => this._createPaymentDeliverySlider(latestOrderId),
			});
		}

		if (this._isTerminalAvailable)
		{
			menuItems.push({
				text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DOCUMENT_TYPE_TERMINAL_PAYMENT'),
				onclick: () => this._createTerminalPaymentSlider(latestOrderId),
			});
		}

		if (this._isDeliveryAvailable)
		{
			menuItems.push({
				text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DOCUMENT_TYPE_DELIVERY'),
				onclick: () => this._createDeliverySlider(latestOrderId),
			});
		}

		const realizationMenuItem = this._getRealizationMenuItem(
			Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DOCUMENT_TYPE_SHIPMENT_DOCUMENT'),
			() => this._createRealizationSlider({ orderId: latestOrderId }),
		);
		if (realizationMenuItem)
		{
			menuItems.push(realizationMenuItem);
		}

		const openMenu = (event) => {
			event.preventDefault();
			const popupMenu = MenuManager.create({
				id: 'payment-documents-create-document-action',
				bindElement: event.target,
				items: menuItems,
			});
			popupMenu.show();
			this._menus.push(popupMenu);
		};

		return Tag.render`
			<div class="crm-entity-widget-payment-add-box">
				<a href="#" class="ui-entity-editor-content-add-lnk" onclick="${openMenu}">
					${Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_CREATE_DOCUMENT_MSGVER_2')}
				</a>
			</div>
		`;
	}

	_getRealizationMenuItem(text: string, onclick: Function): ?Object
	{
		const isAvailableInventoryManagement = this._isUsedInventoryManagement && !this._isWithOrdersMode;
		if (isAvailableInventoryManagement && this._salesOrderRights?.modify)
		{
			const menuItem = {
				text,
			};

			if (this._isOnecMode && this._isInventoryManagement1cRestricted)
			{
				menuItem.onclick = () => {
					OneCPlanRestrictionSlider.show();
				};
				menuItem.className = 'realization-document-tariff-lock';
			}
			else if (!this._isOnecMode && this._isInventoryManagementRestricted)
			{
				menuItem.onclick = () => top.BX.UI.InfoHelper.show('limit_store_crm_integration');
				menuItem.className = 'realization-document-tariff-lock';
			}
			else if (!this._isInventoryManagementToolEnabled)
			{
				menuItem.onclick = () => {
					Runtime.loadExtension('catalog.tool-availability-manager').then((exports) => {
						const { ToolAvailabilityManager } = exports;
						ToolAvailabilityManager.openInventoryManagementToolDisabledSlider();
					});
				};
			}
			else
			{
				menuItem.onclick = onclick;
			}

			return menuItem;
		}

		return null;
	}

	_renderTotalSum(): HTMLElement
	{
		const totalSum = this._options.TOTAL_AMOUNT;

		const node = Tag.render`
			<div class="crm-entity-widget-payment-total">
				<span>
					${this._getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_TOTAL_SUM')}
					<span data-hint="${this._getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_TOTAL_SUM_TOOLTIP')}"></span>
				</span>
				<span class="crm-entity-widget-payment-text">${this._renderMoney(totalSum)}</span>
			</div>
		`;

		BX.UI.Hint.init(node);

		return node;
	}

	_renderMoney(sum: number): string
	{
		const fullPrice = CurrencyCore.currencyFormat(sum, this._options.CURRENCY_ID, true);
		const onlyPrice = CurrencyCore.currencyFormat(sum, this._options.CURRENCY_ID, false);
		const currency = fullPrice.replace(onlyPrice, '').trim();

		return fullPrice.replace(currency, `<span class="crm-entity-widget-payment-currency">${currency}</span>`);
	}

	_docs(): Array<PaymentDocument | CheckDocument>
	{
		if (this._options && this._options.DOCUMENTS && this._options.DOCUMENTS.length > 0)
		{
			return this._options.DOCUMENTS;
		}

		return [];
	}

	_orders(): Array<OrderDocument>
	{
		if (this._options && this._options.ORDERS && this._options.ORDERS.length > 0)
		{
			return this._options.ORDERS;
		}

		return [];
	}

	_context(): StartsSalescenterApp
	{
		return this._parentContext;
	}

	_orderIds(): Array<number>
	{
		if (this._options && this._options.ORDER_IDS && this._options.ORDER_IDS.length > 0)
		{
			return this._options.ORDER_IDS.map((id) => parseInt(id));
		}

		return [];
	}

	// @todo: provide test
	_latestOrderId(): number
	{
		const latestOrder = parseInt(Math.max(...this._orderIds()));

		return latestOrder > 0 ? latestOrder : 0;
	}

	_ownerTypeId(): number
	{
		return this._options.OWNER_TYPE_ID || BX.CrmEntityType.enumeration.deal;
	}

	_defaultCreatePaymentDocumentOptions()
	{
		return {
			context: this._callContext,
			templateMode: 'create',
			ownerTypeId: this._ownerTypeId(),
			ownerId: this._options.OWNER_ID,
		};
	}

	/**
	 * @see #getAnalyticLabels for new analytics, this is for old analytics and will be deprecated in the future
	 * @param labelMode converting to PascalCase before inserting into label template
	 * @returns {string} final analytics label
	 * @private
	 *
	 * Label template: crm#TYPE#PaymentDocuments#MODE#Slider
	 * Type: Deal or DynamicType
	 * Mode: create_payment, create_delivery, view_realization etc.
	 *
	 * Mode converts to PascalCase and inserts into label template
	 *
	 * Example: crmDealPaymentDocumentsCreateDeliverySlider
	 */
	_generateAnalyticsLabel(labelMode: string)
	{
		const labelTemplate = 'crm#TYPE#PaymentDocuments#MODE#Slider';

		let labelEntityType = 'Deal';
		if (BX.CrmEntityType.isDynamicTypeByTypeId(this._ownerTypeId()))
		{
			labelEntityType = 'DynamicType';
		}
		const mode = Text.toPascalCase(labelMode);

		return labelTemplate.replace('#TYPE#', labelEntityType)
			.replace('#MODE#', mode);
	}

	#getAnalyticLabels(type: string): Object
	{
		const labels = {
			tool: 'crm',
			category: 'payments',
			event: 'payment_create_click',
			c_section: 'crm',
			c_sub_section: 'web',
			type,
		};

		return labels;
	}

	_createPaymentSlider(orderId: number)
	{
		if (!this._isSalescenterToolEnabled)
		{
			Runtime.loadExtension('salescenter.tool-availability-manager').then((exports) => {
				const { ToolAvailabilityManager } = exports;
				ToolAvailabilityManager.openSalescenterToolDisabledSlider();
			});

			return;
		}

		const options = this._defaultCreatePaymentDocumentOptions();
		options.mode = 'payment';
		options.analyticsLabel = this._generateAnalyticsLabel('create_payment');
		options.st = this.#getAnalyticLabels('payment');
		options.orderId = orderId;

		this._context().startSalescenterApplication(orderId, options);
	}

	_createDeliverySlider(orderId: number)
	{
		if (!this._isSalescenterToolEnabled)
		{
			Runtime.loadExtension('salescenter.tool-availability-manager').then((exports) => {
				const { ToolAvailabilityManager } = exports;
				ToolAvailabilityManager.openSalescenterToolDisabledSlider();
			});

			return;
		}

		const options = this._defaultCreatePaymentDocumentOptions();
		options.mode = 'delivery';
		options.analyticsLabel = this._generateAnalyticsLabel('create_delivery');
		options.orderId = orderId;

		this._context().startSalescenterApplication(orderId, options);
	}

	_createPaymentDeliverySlider(orderId: number)
	{
		if (!this._isSalescenterToolEnabled)
		{
			Runtime.loadExtension('salescenter.tool-availability-manager').then((exports) => {
				const { ToolAvailabilityManager } = exports;
				ToolAvailabilityManager.openSalescenterToolDisabledSlider();
			});

			return;
		}

		const options = this._defaultCreatePaymentDocumentOptions();
		options.mode = 'payment_delivery';
		options.analyticsLabel = this._generateAnalyticsLabel('create_payment_delivery');
		options.st = this.#getAnalyticLabels('delivery_payment');
		options.orderId = orderId;

		this._context().startSalescenterApplication(orderId, options);
	}

	_createRealizationSlider(createSliderOptions: CreateRealizationSliderOptions)
	{
		const options = {
			context: {
				OWNER_TYPE_ID: this._ownerTypeId(),
				OWNER_ID: this._options.OWNER_ID,
				ORDER_ID: createSliderOptions.orderId || 0,
				PAYMENT_ID: createSliderOptions.paymentId || 0,
			},
			analyticsLabel: this._generateAnalyticsLabel('create_realization'),
			documentType: 'W',
			sliderOptions: {
				customLeftBoundary: 0,
				loader: 'crm-entity-details-loader',
				requestMethod: 'post',
			},
		};

		DocumentManager.openNewRealizationDocument(options).then((result) => {
			this.reloadModel();
			this._reloadOwner();
		});
	}

	_createTerminalPaymentSlider(orderId)
	{
		if (!this._isTerminalToolEnabled)
		{
			Runtime.loadExtension('ui.info-helper').then(() => {
				top.BX.UI.InfoHelper.show('limit_crm_terminal_off');
			});

			return;
		}

		const options = this._defaultCreatePaymentDocumentOptions();
		options.mode = 'terminal_payment';
		options.analyticsLabel = this._generateAnalyticsLabel('create_terminal_payment');
		options.st = this.#getAnalyticLabels('terminal_payment');
		options.orderId = orderId;

		this._context().startSalescenterApplication(orderId, options);
	}

	_chooseDeliverySlider(orderId: number)
	{
		const options = this._defaultCreatePaymentDocumentOptions();
		options.mode = 'delivery';
		options.analyticsLabel = this._generateAnalyticsLabel('choose_delivery');
		options.orderId = orderId;

		this._context().startSalescenterApplication(orderId, options);
	}

	_openPaymentChecksListSlider(paymentId: number)
	{
		BX.SidePanel.Instance.open(
			BX.Uri.addParam('/crm/payment/checks/list.php', {
				owner_id: paymentId,
				owner_type: BX.CrmEntityType.enumeration.orderpayment,
			}),
			{
				width: 1500,
				allowChangeHistory: false,
				cacheable: false,
			},
		);
	}

	_resendPaymentSlider(orderId: number, paymentId: number)
	{
		const options = {
			disableSendButton: '',
			context: 'deal',
			mode: this._ownerTypeId() === BX.CrmEntityType.enumeration.deal ? 'payment_delivery' : 'payment',
			analyticsLabel: this._generateAnalyticsLabel('resend_payment'),
			templateMode: 'view',
			ownerTypeId: this._ownerTypeId(),
			ownerId: this._options.OWNER_ID,
			orderId,
			paymentId,
		};
		this._context().startSalescenterApplication(orderId, options);
	}

	_viewDeliverySlider(orderId: number, shipmentId: number)
	{
		const options = {
			context: this._callContext,
			templateMode: 'view',
			mode: 'delivery',
			analyticsLabel: this._generateAnalyticsLabel('view_delivery'),
			ownerTypeId: this._ownerTypeId(),
			ownerId: this._options.OWNER_ID,
			orderId,
			shipmentId,
		};
		this._context().startSalescenterApplication(orderId, options);
	}

	_viewRealizationSlider(documentId: number)
	{
		const options = {
			ownerTypeId: this._ownerTypeId(),
			ownerId: this._options.OWNER_ID,
			analyticsLabel: this._generateAnalyticsLabel('view_realization'),
			documentId: documentId,
			sliderOptions: {
				customLeftBoundary: 0,
				loader: 'crm-entity-details-loader',
			},
		};

		DocumentManager.openRealizationDetailDocument(documentId, options).then((result) => {
			this._reloadOwner();
		});
	}

	_viewTerminalPaymentSlider(orderId: number, paymentId: number)
	{
		const options = {
			context: 'deal',
			mode: 'terminal_payment',
			analyticsLabel: this._generateAnalyticsLabel('view_terminal_payment'),
			templateMode: 'view',
			ownerTypeId: this._ownerTypeId(),
			ownerId: this._options.OWNER_ID,
			orderId,
			paymentId,
		};
		this._context().startSalescenterApplication(orderId, options);
	}

	_setPaymentPaidStatus(payment: PaymentDocument, isPaid: boolean)
	{
		const strPaid = isPaid ? 'Y' : 'N';
		const stage = isPaid ? 'PAID' : 'CANCEL';

		if (payment.PAID && payment.PAID === strPaid)
		{
			return;
		}

		// positive approach - render success first, then do actual query
		this._docs().forEach((doc) => {
			if (doc.TYPE === 'PAYMENT' && doc.ID === payment.ID)
			{
				doc.PAID = strPaid;
				doc.STAGE = stage;
			}
		});
		this.render();

		const callEventOnSuccess = (response) => {
			EventEmitter.emit('PaymentDocuments.EntityEditor:changePaymentPaidStatus', {
				entityTypeId: this._options.OWNER_TYPE_ID,
				entityId: this._options.OWNER_ID,
			});

			this._emitChangeDocumentsEvent();
		};

		const reloadModelOnError = (response) => {
			this._showErrorOnAction(response);
			this.reloadModel();
		};

		ajax.runAction('crm.order.payment.setPaid', {
			data: {
				id: payment.ID,
				value: strPaid,
			},
		}).then(callEventOnSuccess, reloadModelOnError);
	}

	_setShipmentShippedStatus(shipment: PaymentDocument, isShipped: boolean)
	{
		const strShipped = isShipped ? 'Y' : 'N';

		if (shipment.DEDUCTED && shipment.DEDUCTED === strShipped)
		{
			return;
		}

		this._docs().forEach((doc) => {
			if (doc.TYPE === 'SHIPMENT' && doc.ID === shipment.ID)
			{
				doc.DEDUCTED = strShipped;
			}
		});
		this.render();

		const callEventOnSuccess = (response) => {
			EventEmitter.emit('PaymentDocuments.EntityEditor:changeShipmentShippedStatus', {
				entityTypeId: this._options.OWNER_TYPE_ID,
				entityId: this._options.OWNER_ID,
			});

			this._emitChangeDocumentsEvent();
		};

		const reloadModelOnError = (response) => {
			this._showShipmentStatusError(response, shipment.ID);
			this.reloadModel();
		};

		let actionName = 'crm.order.shipment.setShipped';
		if (this._isUsedInventoryManagement)
		{
			actionName = 'crm.api.realizationdocument.setShipped';
		}

		ajax.runAction(actionName, {
			data: {
				id: shipment.ID,
				value: strShipped,
			},
		}).then(callEventOnSuccess, reloadModelOnError);
	}

	_setRealizationDeductedStatus(shipment: PaymentDocument, isShipped: boolean)
	{
		const strShipped = isShipped ? 'Y' : 'N';

		if (shipment.DEDUCTED && shipment.DEDUCTED === strShipped)
		{
			return;
		}

		this._docs().forEach((doc) => {
			if (doc.TYPE === 'SHIPMENT_DOCUMENT' && doc.ID === shipment.ID)
			{
				doc.DEDUCTED = strShipped;
			}
		});
		this.render();

		const callEventOnSuccess = (response) => {
			EventEmitter.emit('PaymentDocuments.EntityEditor:changeRealizationDeductedStatus', {
				entityTypeId: this._options.OWNER_TYPE_ID,
				entityId: this._options.OWNER_ID,
			});

			this._emitChangeDocumentsEvent();
		};

		const reloadModelOnError = (response) => {
			this._showErrorOnAction(response);
			this.reloadModel();
		};

		ajax.runAction('crm.api.realizationdocument.setShipped', {
			data: {
				id: shipment.ID,
				value: strShipped,
			},
		}).then(callEventOnSuccess, reloadModelOnError);
	}

	_removeDocument(doc: PaymentDocument)
	{
		let action;
		const data = {
			id: doc.ID,
		};

		action = this._resolveRemoveDocumentActionName(doc.TYPE);
		if (!action)
		{
			return;
		}

		if (doc.TYPE === 'SHIPMENT_DOCUMENT')
		{
			data.value = 'N';
		}

		// positive approach - render success first, then do actual query
		this._options.DOCUMENTS = this._options.DOCUMENTS.filter((item) => {
			return !(item.TYPE === doc.TYPE && item.ID === doc.ID);
		});
		this.render();

		const onSuccess = (response) => {
			this._reloadOwner();
			this._emitChangeDocumentsEvent();
		};

		const reloadModelOnError = (response) => {
			this._showErrorOnAction(response);
			this.reloadModel();
		};

		ajax.runAction(action, {
			data,
		}).then(onSuccess, reloadModelOnError);
	}

	_resolveRemoveDocumentActionName(type: string)
	{
		let action = '';

		if (type === 'PAYMENT' || type === 'TERMINAL_PAYMENT')
		{
			action = 'crm.order.payment.delete';
		}
		else if (type === 'SHIPMENT')
		{
			action = 'crm.order.shipment.delete';
		}
		else if (type === 'SHIPMENT_DOCUMENT')
		{
			action = 'crm.api.realizationdocument.setRealization';
		}

		return action;
	}

	_showShipmentStatusError(response, shipmentId)
	{
		let showCommonError = true;

		if (this._isUsedInventoryManagement)
		{
			response.errors.forEach((error) => {
				if (SPECIFIC_ERROR_CODES.has(error.code))
				{
					showCommonError = false;

					let notifyMessage = error.message;
					if (!SPECIFIC_REALIZATION_ERROR_CODES.includes(error.code))
					{
						notifyMessage = BX.util.htmlspecialchars(notifyMessage);
					}

					BX.UI.Notification.Center.notify({
						content: notifyMessage,
						width: 'auto',
						actions: [
							{
								title: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_OPEN_REALIZATION_DOCUMENT'),
								events: {
									click: (event, balloon, action) => {
										this._viewRealizationSlider(shipmentId);
										balloon.close();
									},
								},
							},
						],
					});
				}
			});
		}

		if (showCommonError)
		{
			this._showCommonError();
		}
	}

	_showErrorOnAction(response)
	{
		let showCommonError = true;

		response.errors.forEach((error) => {
			if (SPECIFIC_ERROR_CODES.includes(error.code))
			{
				showCommonError = false;
				this._showSpecificError(error.code, error.message);
			}
		});

		if (showCommonError)
		{
			this._showCommonError();
		}
	}

	_showSpecificError(code, message)
	{
		let notifyMessage = message;
		if (!SPECIFIC_REALIZATION_ERROR_CODES.includes(code))
		{
			notifyMessage = BX.util.htmlspecialchars(notifyMessage);
		}

		BX.UI.Notification.Center.notify({
			content: notifyMessage,
		});
	}

	_showCommonError()
	{
		BX.UI.Notification.Center.notify({
			content: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_COMMON_ERROR_MSGVER_1'),
		});
	}

	_loading(isLoading: boolean)
	{
		if (this._rootNode && this._rootNode.classList)
		{
			if (isLoading)
			{
				this._rootNode.classList.add('is-loading');
			}
			else
			{
				this._rootNode.classList.remove('is-loading');
			}
		}
	}

	_emitChangeDocumentsEvent()
	{
		EventEmitter.emit('PaymentDocuments.EntityEditor:changeDocuments', {
			entityTypeId: this._options.OWNER_TYPE_ID,
			entityId: this._options.OWNER_ID,
		});
	}

	_subscribeToGlobalEvents()
	{
		const events = new Set([
			'salescenter.app:onshipmentcreated',
			'salescenter.app:onpaymentcreated',
			'salescenter.app:onpaymentresend',
			'salescenter.app:onterminalpaymentcreated',
		]);
		const timeout = 500;
		const reloadWidget = debounce(() => {
			this.reloadModel();
		}, timeout);
		const inCompatMode = { compatMode: true };

		let sliderJustClosed = false;

		EventEmitter.subscribe('SidePanel.Slider:onMessage', (event) => {
			const eventId = event.getEventId();
			if (events.has(eventId))
			{
				reloadWidget();
				sliderJustClosed = true;
				setTimeout(() => {
					sliderJustClosed = false;
				}, 2000);
			}
		}, inCompatMode);

		EventEmitter.subscribe('oncrmentityupdate', () => {
			reloadWidget();
		}, inCompatMode);

		EventEmitter.subscribe('onPullEvent-crm', (command, params) => {
			if (command !== 'onOrderSave' || sliderJustClosed)
			{
				return;
			}

			reloadWidget();
		}, inCompatMode);

		EventEmitter.subscribe('onPullEvent-salescenter', (command, params) => {
			if (command !== 'onOrderPaymentViewed')
			{
				return;
			}
			let orderId = false;
			const orderIds = this._orderIds();
			if (params && params.ORDER_ID)
			{
				orderId = parseInt(params.ORDER_ID);
				if (orderIds.includes(orderId))
				{
					reloadWidget();
				}
			}
		}, inCompatMode);
	}

	_setupCurrencyFormat()
	{
		if (this._options && this._options.CURRENCY_ID && this._options.CURRENCY_FORMAT)
		{
			CurrencyCore.setCurrencyFormat(this._options.CURRENCY_ID, this._options.CURRENCY_FORMAT);
		}
	}

	_reloadOwner()
	{
		if (this._parentContext instanceof BX.Crm.EntityEditorMoneyPay)
		{
			this._parentContext._editor.reload();
			this._parentContext._editor.tapController('PRODUCT_LIST', (controller) => {
				controller.reinitializeProductList();
			});
		}
	}

	_getMessage(phrase): ?string
	{
		if (Type.isPlainObject(this._phrases) && Type.isString(this._phrases[phrase]))
		{
			phrase = this._phrases[phrase];
		}

		return Loc.getMessage(phrase);
	}
}
