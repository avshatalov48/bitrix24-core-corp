// @flow

import {Tag, Loc, Type, ajax, debounce} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {MenuManager} from 'main.popup';
import {CurrencyCore} from 'currency.currency-core';
import {MessageBox, MessageBoxButtons} from 'ui.dialogs.messagebox';
import {Label, LabelColor} from 'ui.label';

declare var BX: {[key: string]: any};

type PaymentDocument = {
	ID: number,
	ORDER_ID: number,
	FORMATTED_DATE: string,
	SUM: number,
	TYPE: 'PAYMENT' | 'SHIPMENT',
	PAID?: 'Y' | 'N',
	STAGE?: 'NOT_PAID' | 'SENT_NO_VIEWED' | 'VIEWED_NO_PAID' | 'PAID' | 'CANCEL' | 'REFUND',
	DELIVERY_NAME?: string,
	DEDUCTED?: 'Y' | 'N'
};

type EntityEditorPaymentDocumentsOptions = {
	CURRENCY_FORMAT: {},
	CURRENCY_ID: string,
	DEAL_ID: number,
	DEAL_AMOUNT: number,
	DOCUMENTS: Array<PaymentDocument>,
	ORDER_IDS: Array<number>,
	PARENT_CONTEXT: StartsSalescenterApp,
	IS_DELIVERY_AVAILABLE: boolean
};

type Destroyable = {
	destroy(): any
};

type StartsSalescenterApp = {
	startSalescenterApplication(): any
};

type AjaxHandler = (...args: Array<any>) => any;

export class EntityEditorPaymentDocuments
{
	_options: EntityEditorPaymentDocumentsOptions;
	_isDeliveryAvailable: boolean;
	_parentContext: StartsSalescenterApp;
	_rootNode: HTMLElement;
	_menus: Array<Destroyable>;
	static _rootNodeClass = 'crm-entity-widget-inner crm-entity-widget-inner--payment';

	constructor(options: EntityEditorPaymentDocumentsOptions)
	{
		this._options = options;
		this._isDeliveryAvailable = this._options.IS_DELIVERY_AVAILABLE;
		this._parentContext = options.PARENT_CONTEXT;
		this._rootNode = Tag.render`<div class="${this.constructor._rootNodeClass}"></div>`;
		this._menus = [];

		this._subscribeToGlobalEvents();
	}

	hasContent(): boolean
	{
		return this._docs().length > 0;
	}

	render(): HTMLElement
	{
		this._menus.forEach(menu => menu.destroy());
		this._rootNode.innerHTML = '';
		this._setupCurrencyFormat();

		if (this.hasContent())
		{
			this._rootNode.classList.remove('is-hidden');
			this._rootNode.append(Tag.render`
				<div class="crm-entity-widget-content-block-inner-container">
					<div class="crm-entity-widget-payment">
						${this._renderTitle()}
						${this._renderDocuments()}
						${this._renderAddDocument()}
						${this._renderTotalSum()}
					</div>
				</div>
			`);
		}
		else
		{
			this._rootNode.classList.add('is-hidden');
		}

		return this._rootNode;
	}

	setOptions(options)
	{
		this._options = options;
	}

	reloadModel(onSuccess?: AjaxHandler, onError?: AjaxHandler)
	{
		if (!this._options.DEAL_ID)
		{
			return;
		}

		const data = {
			data: {
				dealId: this._options.DEAL_ID
			}
		};

		const successCallback = response => {
			this._loading(false);
			if (response.data) {
				this.setOptions(response.data);
				this.render();
				if (onSuccess && Type.isFunction(onSuccess)) {
					onSuccess(response);
				}
			} else {
				this._showCommonError();
				if (onError && Type.isFunction(onError)) {
					onError();
				}
			}
		};

		const errorCallback = () => {
			this._loading(false);
			this._showCommonError();
			if (onError && Type.isFunction(onError)) {
				onError();
			}
		};

		this._loading(true);

		ajax.runAction('crm.api.deal.fetchPaymentDocuments', data).then(successCallback, errorCallback);
	}

	_renderTitle(): HTMLElement
	{
		return Tag.render`
			<div class="crm-entity-widget-payment-detail">
				<div class="crm-entity-widget-payment-detail-caption">${Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_TITLE')}</div>
			</div>
		`;
	}

	_renderDocuments(): HTMLElement[]
	{
		const nodes = [];
		this._docs().forEach(doc => {
			if (doc.TYPE === 'PAYMENT')
			{
				nodes.push(this._renderPaymentDocument(doc));
			}
			else if (doc.TYPE === 'SHIPMENT')
			{
				nodes.push(this._renderShipmentDocument(doc));
			}
		});
		return nodes;
	}

	_renderPaymentDocument(doc: PaymentDocument): HTMLElement
	{
		const title = Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_PAYMENT_DATE').replace(/#DATE#/gi, doc.FORMATTED_DATE);
		const sum = Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_PAYMENT_AMOUNT').replace(/#SUM#/gi, this._renderMoney(doc.SUM));
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
		let menuItems = [];
		if (this._isDeliveryAvailable)
		{
			menuItems.push({
				text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_CHOOSE_DELIVERY'),
				title: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_CHOOSE_DELIVERY'),
				onclick: () => this._chooseDeliverySlider(doc.ORDER_ID)
			});
		}

		menuItems.push({
			text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_RESEND'),
			onclick: () => this._resendPaymentSlider(doc.ORDER_ID, doc.ID)
		});
		menuItems.push({
			text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_CHANGE_PAYMENT_STATUS'),
			items: [
				{
					text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_PAID'),
					className: (doc.PAID === 'Y') ? 'menu-popup-item-accept-sm' : '',
					onclick: () => {
						this._setPaymentPaidStatus(doc, true);
						popupMenu.close();
					}
				},
				{
					text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_NOT_PAID'),
					className: (doc.PAID === 'Y') ? '' : 'menu-popup-item-accept-sm',
					onclick: () => {
						this._setPaymentPaidStatus(doc, false);
						popupMenu.close();
					}
				}
			]
		});
		menuItems.push({
			text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE'),
			className: (doc.PAID === 'Y') ? 'menu-popup-no-icon crm-entity-widget-payment-menu-item-remove' : '',
			onclick: () => {
				if (doc.PAID === 'Y')
				{
					return false;
				}
				popupMenu.close();
				MessageBox.show({
					title: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE_CONFIRM_TITLE'),
					message: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE_PAYMENT_CONFIRM_TEXT'),
					modal: true,
					buttons: MessageBoxButtons.OK_CANCEL,
					onOk: messageBox => {
						messageBox.close();
						this._removeDocument(doc);
					},
					onCancel: messageBox => {
						messageBox.close();
					},
				});
			}
		});

		const openSlider = () => this._resendPaymentSlider(doc.ORDER_ID, doc.ID);

		const openMenu = event => {
			event.preventDefault();
			popupMenu = MenuManager.create({
				id: 'payment-documents-payment-action-' + doc.ID,
				bindElement: event.target,
				items: menuItems
			});
			popupMenu.show();

			const removeDocumentMenuItem = popupMenu.itemsContainer.querySelector('.crm-entity-widget-payment-menu-item-remove');
			if (removeDocumentMenuItem)
			{
				removeDocumentMenuItem.setAttribute('data-hint', Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_PAYMENT_REMOVE_TIP'));
				removeDocumentMenuItem.setAttribute('data-hint-no-icon', '');
				BX.UI.Hint.init(popupMenu.itemsContainer);
			}

			this._menus.push(popupMenu);
		};

		return Tag.render`
			<div class="crm-entity-widget-payment-detail">
				<a class="ui-link" onclick="${openSlider}">${title} (${sum})</a>
				<div class="crm-entity-widget-payment-detail-inner">
					<div class="ui-label ui-label-md ui-label-light crm-entity-widget-payment-action" onclick="${openMenu}">
						<span class="ui-label-inner">${Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_ACTIONS_MENU')}</span>
					</div>
					${(new Label(labelOptions)).render()}
				</div>
			</div>
		`;
	}

	_renderShipmentDocument(doc: PaymentDocument): HTMLElement
	{
		const labelOptions = {
			text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_WAITING'),
			customClass: 'crm-entity-widget-payment-label',
			color: LabelColor.LIGHT,
			fill: true,
		};
		if (doc.DEDUCTED === 'Y')
		{
			labelOptions.text = Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_DELIVERED');
			labelOptions.color = LabelColor.LIGHT_GREEN;
		}
		const title = Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DELIVERY_DATE').replace(/#DATE#/gi, doc.FORMATTED_DATE);
		const sum = Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_PAYMENT_AMOUNT').replace(/#SUM#/gi, this._renderMoney(doc.SUM));

		let popupMenu;
		let menuItems = [
			{
				text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_CHANGE_DELIVERY_STATUS'),
				items: [
					{
						text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_DELIVERED'),
						className: (doc.DEDUCTED === 'Y') ? 'menu-popup-item-accept-sm' : '',
						onclick: () => {
							this._setShipmentShippedStatus(doc, true);
							popupMenu.close();
						}
					},
					{
						text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_STATUS_WAITING'),
						className: (doc.DEDUCTED === 'Y') ? '' : 'menu-popup-item-accept-sm',
						onclick: () => {
							this._setShipmentShippedStatus(doc, false);
							popupMenu.close();
						}
					}
				]
			},
			{
				text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE'),
				onclick: () => {
					popupMenu.close();
					MessageBox.show({
						title: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE_CONFIRM_TITLE'),
						message: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_REMOVE_DELIVERY_CONFIRM_TEXT'),
						modal: true,
						buttons: MessageBoxButtons.OK_CANCEL,
						onOk: messageBox => {
							messageBox.close();
							this._removeDocument(doc);
						},
						onCancel: messageBox => {
							messageBox.close();
						},
					});
				}
			}
		];

		const openSlider = () => this._viewDeliverySlider(doc.ORDER_ID, doc.ID);

		const openMenu = event => {
			event.preventDefault();
			popupMenu = MenuManager.create({
				id: 'payment-documents-delivery-action-' + doc.ID,
				bindElement: event.target,
				items: menuItems
			});
			popupMenu.show();
			this._menus.push(popupMenu);
		};

		return Tag.render`
			<div class="crm-entity-widget-payment-detail">
				<a class="ui-link" onclick="${openSlider}">
					${title} (${doc.DELIVERY_NAME}, ${sum})
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

	_renderAddDocument(): HTMLElement
	{
		const latestOrderId = this._latestOrderId();

		let menuItems = [
			{
				text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DOCUMENT_TYPE_PAYMENT'),
				onclick: () => this._context().startSalescenterApplication(latestOrderId)
			}
		];
		if (this._isDeliveryAvailable)
		{
			menuItems.push({
				text: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DOCUMENT_TYPE_DELIVERY'),
				onclick: () => this._createDeliverySlider(latestOrderId)
			});
		}

		const openMenu = event => {
			event.preventDefault();
			const popupMenu = MenuManager.create({
				id: 'payment-documents-create-document-action',
				bindElement: event.target,
				items: menuItems
			});
			popupMenu.show();
			this._menus.push(popupMenu);
		};

		return Tag.render`
			<div class="crm-entity-widget-payment-add-box">
				<a href="#" class="crm-entity-widget-payment-add" onclick="${openMenu}">
					+ ${Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_CREATE_DOCUMENT')}
				</a>
			</div>
		`;
	}

	_calculateTotalSum()
	{
		let totalSum = parseFloat(this._options.DEAL_AMOUNT);
		this._docs().forEach(doc => {
			if (doc.TYPE === 'PAYMENT')
			{
				if (doc.PAID && doc.PAID === 'Y')
				{
					totalSum -= parseFloat(doc.SUM);
				}
			}
		});

		if (totalSum < 0)
		{
			totalSum = 0.0;
		}

		return totalSum;
	}

	_renderTotalSum(): HTMLElement
	{
		let totalSum = this._calculateTotalSum();

		const node = Tag.render`
			<div class="crm-entity-widget-payment-total">
				<span>
					${Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_TOTAL_SUM')}
					<span data-hint="${Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_TOTAL_SUM_TOOLTIP')}"></span>
				</span>
				<span class="crm-entity-widget-payment-text">${this._renderMoney(totalSum)}</span>
			</div>
		`;

		BX.UI.Hint.init(node);

		return node;
	};

	_renderMoney(sum: number): string
	{
		const fullPrice = CurrencyCore.currencyFormat(sum, this._options.CURRENCY_ID, true);
		const onlyPrice = CurrencyCore.currencyFormat(sum, this._options.CURRENCY_ID, false);
		const currency = fullPrice.replace(onlyPrice, '').trim();

		return fullPrice.replace(currency, `<span class="crm-entity-widget-payment-currency">${currency}</span>`);
	}

	_docs(): Array<PaymentDocument>
	{
		if (this._options && this._options.DOCUMENTS && this._options.DOCUMENTS.length)
		{
			return this._options.DOCUMENTS;
		}
		return [];
	}

	_context(): StartsSalescenterApp
	{
		return this._parentContext;
	}

	_orderIds(): Array<number>
	{
		if (this._options && this._options.ORDER_IDS && this._options.ORDER_IDS.length)
		{
			return this._options.ORDER_IDS.map(id => parseInt(id));
		}
		return [];
	}

	// @todo: provide test
	_latestOrderId(): number
	{
		return Math.max(...this._orderIds());
	}

	_dealEntityType(): number
	{
		return BX.CrmEntityType.enumeration.deal;
	}

	_createDeliverySlider(orderId: number)
	{
		const options = {
			context: 'deal',
			templateMode: 'create',
			mode: 'delivery',
			analyticsLabel: 'crmDealPaymentDocumentsCreateDeliverySlider',
			ownerTypeId: this._dealEntityType(),
			ownerId: this._options.DEAL_ID,
			orderId: orderId,
		};
		this._context().startSalescenterApplication(orderId, options);
	}

	_chooseDeliverySlider(orderId: number)
	{
		const options = {
			context: 'deal',
			templateMode: 'create',
			mode: 'delivery',
			analyticsLabel: 'crmDealPaymentDocumentsChooseDeliverySlider',
			ownerTypeId: this._dealEntityType(),
			ownerId: this._options.DEAL_ID,
			orderId: orderId,
		};
		this._context().startSalescenterApplication(orderId, options);
	}

	_resendPaymentSlider(orderId: number, paymentId: number)
	{
		const options = {
			disableSendButton: '',
			context: 'deal',
			mode: 'payment_delivery',
			analyticsLabel: 'crmDealPaymentDocumentsResendPaymentSlider',
			templateMode: 'view',
			ownerTypeId: this._dealEntityType(),
			ownerId: this._options.DEAL_ID,
			orderId: orderId,
			paymentId: paymentId,
		};
		this._context().startSalescenterApplication(orderId, options);
	}

	_viewDeliverySlider(orderId: number, shipmentId: number)
	{
		const options = {
			context: 'deal',
			templateMode: 'view',
			mode: 'delivery',
			analyticsLabel: 'crmDealPaymentDocumentsViewDeliverySlider',
			ownerTypeId: this._dealEntityType(),
			ownerId: this._options.DEAL_ID,
			orderId: orderId,
			shipmentId: shipmentId,
		};
		this._context().startSalescenterApplication(orderId, options);
	}

	_setPaymentPaidStatus(payment: PaymentDocument, isPaid: boolean)
	{
		const strPaid = isPaid ? 'Y' : 'N';
		const stage = isPaid ? 'PAID' : 'CANCEL';

		if (payment.PAID && payment.PAID === strPaid) {
			return;
		}

		// positive approach - render success first, then do actual query
		this._docs().forEach(doc => {
			if (doc.TYPE === 'PAYMENT' && doc.ID === payment.ID) {
				doc.PAID = strPaid;
				doc.STAGE = stage;
			}
		});
		this.render();

		const doNothingOnSuccess = response => {};
		const reloadModelOnError = response => {
			this.reloadModel();
			this._showCommonError();
		};

		ajax.runAction('sale.payment.setpaid', {
			data: {
				id: payment.ID,
				value: strPaid,
			}
		}).then(doNothingOnSuccess, reloadModelOnError);
	}

	_setShipmentShippedStatus(shipment: PaymentDocument, isShipped: boolean)
	{
		const strShipped = isShipped ? 'Y' : 'N';

		if (shipment.DEDUCTED && shipment.DEDUCTED === strShipped) {
			return;
		}

		// positive approach - render success first, then do actual query
		this._docs().forEach(doc => {
			if (doc.TYPE === 'SHIPMENT' && doc.ID === shipment.ID) {
				doc.DEDUCTED = strShipped;
			}
		});
		this.render();

		const doNothingOnSuccess = response => {};
		const reloadModelOnError = response => {
			this.reloadModel();
			this._showCommonError();
		};

		ajax.runAction('sale.shipment.setshipped', {
			data: {
				id: shipment.ID,
				value: strShipped,
			}
		}).then(doNothingOnSuccess, reloadModelOnError);
	}

	_removeDocument(doc: PaymentDocument)
	{
		let action;

		if (doc.TYPE === 'PAYMENT') {
			action = 'sale.payment.delete';
		} else if (doc.TYPE === 'SHIPMENT') {
			action = 'sale.shipment.delete';
		} else {
			return;
		}

		// positive approach - render success first, then do actual query
		this._options.DOCUMENTS = this._options.DOCUMENTS.filter(item => {
			return !(item.TYPE === doc.TYPE && item.ID === doc.ID);
		});
		this.render();

		const doNothingOnSuccess = response => {};
		const reloadModelOnError = response => {
			this.reloadModel();
			this._showCommonError();
		};

		ajax.runAction(action, {
			data: {
				id: doc.ID
			}
		}).then(doNothingOnSuccess, reloadModelOnError);
	}

	_showCommonError()
	{
		BX.UI.Notification.Center.notify({
			content: Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_COMMON_ERROR')
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

	_subscribeToGlobalEvents()
	{
		const events = [
			'salescenter.app:onshipmentcreated',
			'salescenter.app:onpaymentcreated',
			'salescenter.app:onpaymentresend'
		];
		const timeout = 500;
		const reloadWidget = debounce(() => {
			this.reloadModel();
		}, timeout);
		const inCompatMode = {compatMode: true};

		let sliderJustClosed = false;

		EventEmitter.subscribe('SidePanel.Slider:onMessage', event => {
			const eventId = event.getEventId();
			if (events.indexOf(eventId) > -1) {
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
			let orderId = false;
			const orderIds = this._orderIds();
			if (params.FIELDS && params.FIELDS.ID)
			{
				orderId = parseInt(params.FIELDS.ID);
			}
			if (orderId && orderIds.indexOf(orderId) > -1)
			{
				reloadWidget();
			}
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
				if (orderIds.indexOf(orderId) > -1)
				{
					reloadWidget();
				}
			}
		}, inCompatMode);
	}

	_setupCurrencyFormat()
	{
		if (this._options)
		{
			if (this._options.CURRENCY_ID && this._options.CURRENCY_FORMAT)
			{
				CurrencyCore.setCurrencyFormat(this._options.CURRENCY_ID, this._options.CURRENCY_FORMAT);
			}
		}
	}
}
