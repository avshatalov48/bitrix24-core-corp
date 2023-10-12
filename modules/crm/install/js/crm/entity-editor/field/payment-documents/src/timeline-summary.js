import {Loc, Tag} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {Label, LabelColor} from 'ui.label';
import {EntityEditorPaymentDocuments} from './entity-editor';
import {CurrencyCore} from 'currency.currency-core';

export class TimelineSummaryDocuments extends EntityEditorPaymentDocuments
{
	static _rootNodeClass = 'crm-entity-stream-content-detail-table crm-entity-stream-content-documents-table';

	render(): HTMLElement {
		this._menus.forEach((menu) => menu.destroy());
		this._rootNode.innerHTML = '';
		this._setupCurrencyFormat();

		if (this.hasContent())
		{
			this._filterSuccessfulDocuments();
			this._rootNode.classList.remove('is-hidden');

			if (this._isWithOrdersMode)
			{
				this._renderDocumentWithOrdersMode();
			}
			else
			{
				this._renderDocumentWithoutOrdersMode();
			}

			let checkExists = this._isCheckExists();
			if (checkExists)
			{
				this._rootNode.append(Tag.render`
					<div class="crm-entity-stream-content-document-table-group">
						${this._renderChecksDocument()}
					</div>
				`);
			}
		}
		else
		{
			this._rootNode.classList.add('is-hidden');
		}

		EventEmitter.emit('PaymentDocuments:render', [this]);

		return this._rootNode;
	}

	_renderDocumentWithOrdersMode()
	{
		let orderDocument = Tag.render`<div></div>`;

		this._orders().forEach(order => {
			let documents = this._renderDocumentsByOrder(order.ID);
			if (documents.length > 0)
			{
				orderDocument.append(this._renderOrderDetailBlock(order));
				documents.forEach(document => {
					orderDocument.append(document);
				});
			}
		});

		this._rootNode.append(Tag.render`
			<div>
				${orderDocument}
				<div class="crm-entity-stream-content-document-table-group">
					${this._renderEntityTotalSum()}
					${this._renderEntityPaidSum()}
				</div>
				<div class="crm-entity-stream-content-document-table-group">
					${this._renderTotalSum()}
				</div>
			</div>
		`);
	}

	_renderDocumentWithoutOrdersMode()
	{
		this._rootNode.append(Tag.render`
			<div>
				${this._renderDocuments()}
				<div class="crm-entity-stream-content-document-table-group">
					${this._renderEntityTotalSum()}
					${this._renderEntityPaidSum()}
				</div>
				<div class="crm-entity-stream-content-document-table-group">
					${this._renderTotalSum()}
				</div>
			</div>
		`);
	}

	_renderDocumentsByOrder(orderId: number): HTMLElement[]
	{
		const nodes = [];

		let orderDocs = this._docs().filter((item) => {
			return item.ORDER_ID === orderId;
		});

		orderDocs.forEach(doc => {
			if (doc.TYPE === 'PAYMENT')
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

	_renderEntityTotalSum() {
		let phrase = 'CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DEAL_SUM';
		if (Number(this._options.OWNER_TYPE_ID) === BX.CrmEntityType.enumeration.smartinvoice)
		{
			phrase = 'CRM_ENTITY_ED_PAYMENT_DOCUMENTS_INVOICE_SUM';
		}

		return Tag.render`
			<div class="crm-entity-stream-content-detail-table-row crm-entity-stream-content-document-table-row">
				<div class="crm-entity-stream-content-detail-description">
					${Loc.getMessage(phrase)}
				</div>
				<div class="crm-entity-stream-content-detail-cost">
					<span>
						${this._renderMoney(this._options.ENTITY_AMOUNT)}
					</span>
				</div>
			</div>
		`;
	}

	_renderEntityPaidSum() {
		return Tag.render`
			<div class="crm-entity-stream-content-detail-table-row crm-entity-stream-content-document-table-row">
				<div class="crm-entity-stream-content-detail-description">
					${Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_ENTITY_PAID_SUM')}
				</div>
				<div class="crm-entity-stream-content-detail-cost">
					<span>
						${this._renderMoney(this._options.PAID_AMOUNT)}
					</span>
				</div>
			</div>
		`;
	}

	_renderPaymentDocument(doc: PaymentDocument): HTMLElement
	{
		const title = Loc.getMessage(
			'CRM_ENTITY_ED_PAYMENT_DOCUMENTS_PAYMENT_DATE_MSGVER_1',
			{
				'#DATE#': doc.FORMATTED_DATE,
				'#ACCOUNT_NUMBER#': doc.ACCOUNT_NUMBER,
			},
		);
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

		const openSlider = () => this._resendPaymentSlider(doc.ORDER_ID, doc.ID);

		return Tag.render`
			<div class="crm-entity-stream-content-detail-table-row">
				<div class="crm-entity-stream-content-document-description">
					<a class="ui-link" onclick="${openSlider}">${title} (${sum})</a>
					<span class="crm-entity-stream-content-document-description__label">
						${(new Label(labelOptions)).render()}
					</span>
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
		const title = Loc.getMessage(
			'CRM_ENTITY_ED_PAYMENT_DOCUMENTS_DELIVERY_DATE_MSGVER_1',
			{
				'#DATE#': doc.FORMATTED_DATE,
				'#ACCOUNT_NUMBER#': doc.ACCOUNT_NUMBER,
			},
		);
		const sum = Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_PAYMENT_AMOUNT').replace(/#SUM#/gi, this._renderMoney(doc.SUM));

		const openSlider = () => this._viewDeliverySlider(doc.ORDER_ID, doc.ID);

		return Tag.render`
			<div class="crm-entity-stream-content-detail-table-row">
				<div class="crm-entity-stream-content-document-description">
					<a class="ui-link" onclick="${openSlider}">
						${title} (${doc.DELIVERY_NAME}, ${sum})
					</a>
					<span class="crm-entity-stream-content-document-description__label">
						${(new Label(labelOptions)).render()}
					</span>
				</div>
			</div>
		`;
	}

	_renderRealizationDocument(doc: PaymentDocument): HTMLElement
	{
		const labelOptions = {
			fill: true,
			customClass: 'crm-entity-widget-payment-label',
		};
		if (doc.DEDUCTED === 'Y')
		{
			labelOptions.text = Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_STATUS_DEDUCTED');
			labelOptions.color = LabelColor.LIGHT_GREEN;
		}
		else
		{
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
		}

		let title = Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_DATE_MSGVER_1').replace(/#DATE#/gi, doc.FORMATTED_DATE);
		title = title.replace(/#DOCUMENT_ID#/gi, doc.ACCOUNT_NUMBER);
		title = BX.util.htmlspecialchars(title);

		const sum = Loc.getMessage('CRM_ENTITY_ED_PAYMENT_DOCUMENTS_SHIPMENT_DOCUMENT_AMOUNT').replace(/#SUM#/gi, this._renderMoney(doc.SUM));

		const openSlider = () => this._viewRealizationSlider(doc.ID);

		return Tag.render`
			<div class="crm-entity-stream-content-detail-table-row">
				<div class="crm-entity-stream-content-document-description">
					<a class="ui-link" onclick="${openSlider}">
						${title} (${sum})
					</a>
					<span class="crm-entity-stream-content-document-description__label">
						${(new Label(labelOptions)).render()}
					</span>
				</div>
			</div>
		`;
	}

	_renderTotalSum(): HTMLElement
	{
		let phrase = 'CRM_ENTITY_ED_PAYMENT_DOCUMENTS_TOTAL_SUM';
		if (Number(this._options.OWNER_TYPE_ID) === BX.CrmEntityType.enumeration.smartinvoice)
		{
			phrase = 'CRM_ENTITY_ED_PAYMENT_DOCUMENTS_TOTAL_INVOICE_SUM';
		}

		return Tag.render`
			<div class="crm-entity-stream-content-detail-table-row crm-entity-stream-content-detail-table-row-total">
				<div class="crm-entity-stream-content-detail-description">
					<span>
						${Loc.getMessage(phrase)}
					</span>
				</div>
				<div class="crm-entity-stream-content-detail-cost">
					<span>
						${this._renderTotalMoney(this._options.TOTAL_AMOUNT)}
					</span>
				</div>
			</div>
		`;
	}

	_renderTotalMoney(sum: number): string
	{
		let fullPrice = CurrencyCore.currencyFormat(sum, this._options.CURRENCY_ID, true);
		const onlyPrice = CurrencyCore.currencyFormat(sum, this._options.CURRENCY_ID, false);
		const currency = fullPrice.replace(onlyPrice, '').trim();

		fullPrice = fullPrice.replace(currency, `<span class="crm-entity-widget-payment-currency">${currency}</span>`);
		fullPrice = fullPrice.replace(onlyPrice, `<b>${onlyPrice}</b>`);

		return fullPrice;
	}

	_renderChecksDocument(): HTMLElement[]
	{
		const nodes = [];

		this._docs().forEach(doc => {
			if (doc.TYPE === 'CHECK')
			{
				nodes.push(this._renderCheckDocument(doc));
			}
		});

		return nodes;
	}

	_renderCheckDocument(doc: CheckDocument): HTMLElement
	{
		let link;

		if (doc.URL)
		{
			link = Tag.render`<a href="${doc.URL}" target="_blank">${doc.TITLE}</a>`;
		}
		else
		{
			link = Tag.render`<span>${doc.TITLE}</span>`;
		}

		return Tag.render`<div class="crm-entity-stream-content-detail-notice">${link}</div>`;
	}

	_renderOrderDetailBlock(doc: OrderDocument): HTMLElement
	{
		return Tag.render`
			<div class="crm-entity-stream-content-document-table-order-group crm-entity-stream-content-detail-table-row">
				<div class="crm-entity-stream-content-detail-description">
					<span>${doc.TITLE}</span>
				</div>
				<div class="crm-entity-stream-content-detail-cost">
					<span class="crm-entity-stream-content-detail-cost-current">${doc.PRICE_FORMAT}</span>
				</div>
			</div>
		`;
	}

	_filterSuccessfulDocuments()
	{
		this._options.DOCUMENTS = this._options.DOCUMENTS.filter((item) => {
			return (
				(item.TYPE === 'PAYMENT' && item.PAID === 'Y')
				|| (item.TYPE === 'SHIPMENT' && item.DEDUCTED === 'Y')
				|| (item.TYPE === 'SHIPMENT_DOCUMENT' && item.DEDUCTED === 'Y')
				|| (item.TYPE === 'CHECK' && item.STATUS === 'Y')
			);
		});
	}

	_isCheckExists()
	{
		let checks = this._options.DOCUMENTS.filter((item) => {
			return item.TYPE === 'CHECK' && item.STATUS === 'Y';
		});

		return checks.length > 1;
	}
}